<?php
/**
 * AlmaSEO Evergreen REST API
 * 
 * Provides REST endpoints for the Evergreen panel
 * 
 * @package AlmaSEO
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Evergreen REST API Class
 */
class AlmaSEO_Evergreen_REST_API {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Register routes immediately since we're already in rest_api_init
        $this->register_routes();
    }
    
    /**
     * Register REST routes
     */
    public function register_routes() {
        $namespace = 'almaseo/v1';
        
        // Test endpoint to verify REST API is working
        register_rest_route($namespace, '/evergreen/test', array(
            'methods' => 'GET',
            'callback' => function() {
                return array('success' => true, 'message' => 'REST API is working');
            },
            'permission_callback' => '__return_true'
        ));
        
        // Get status endpoint
        register_rest_route($namespace, '/evergreen/status/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_status'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Recalculate endpoint
        register_rest_route($namespace, '/evergreen/recalculate/(?P<id>\d+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'recalculate_status'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Mark refreshed endpoint
        register_rest_route($namespace, '/evergreen/mark-refreshed/(?P<id>\d+)', array(
            'methods' => 'POST',
            'callback' => array($this, 'mark_refreshed'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Bulk operations endpoint
        register_rest_route($namespace, '/evergreen/bulk', array(
            'methods' => 'POST',
            'callback' => array($this, 'bulk_operation'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'action' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return in_array($param, array('recalculate', 'reset', 'export'));
                    }
                ),
                'post_ids' => array(
                    'required' => false,
                    'validate_callback' => function($param) {
                        return is_array($param);
                    }
                )
            )
        ));
    }
    
    /**
     * Check permission
     */
    public function check_permission($request) {
        // First check if user is logged in
        if (!is_user_logged_in()) {
            return false;
        }
        
        // Check if user can edit the post
        $post_id = $request->get_param('id');
        
        if ($post_id) {
            // More lenient check - allow if user can edit ANY posts
            return current_user_can('edit_posts') || current_user_can('edit_post', $post_id);
        }
        
        // For bulk operations, check general capability
        return current_user_can('edit_posts');
    }
    
    /**
     * Get evergreen status
     */
    public function get_status($request) {
        $post_id = intval($request->get_param('id'));
        
        // Check if post exists
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('not_found', 'Post not found', array('status' => 404));
        }
        
        // Load evergreen functions if needed
        if (!function_exists('almaseo_eg_get_post_status')) {
            $plugin_dir = plugin_dir_path(dirname(dirname(__FILE__)));
            require_once $plugin_dir . 'includes/evergreen/functions.php';
        }
        
        // Get current status
        $status = almaseo_eg_get_post_status($post_id);
        $last_calculated = get_post_meta($post_id, '_almaseo_eg_last_calculated', true);
        $reasons = get_post_meta($post_id, '_almaseo_eg_status_reasons', true);
        $score = get_post_meta($post_id, '_almaseo_eg_score', true);
        
        // Get age data
        $ages = almaseo_eg_calculate_post_ages($post_id);
        
        // Get traffic data if available
        $traffic_data = null;
        if (function_exists('almaseo_eg_get_post_traffic')) {
            $traffic_data = almaseo_eg_get_post_traffic($post_id);
        }
        
        return array(
            'success' => true,
            'data' => array(
                'status' => $status,
                'last_calculated' => $last_calculated,
                'reasons' => $reasons ?: array(),
                'score' => $score,
                'ages' => $ages,
                'traffic' => $traffic_data,
                'thresholds' => array(
                    'watch_days' => get_option('almaseo_eg_watch_days', 180),
                    'stale_days' => get_option('almaseo_eg_stale_days', 365),
                    'watch_traffic_drop' => get_option('almaseo_eg_watch_traffic_drop', 20),
                    'stale_traffic_drop' => get_option('almaseo_eg_stale_traffic_drop', 40)
                )
            )
        );
    }
    
    /**
     * Mark post as refreshed
     */
    public function mark_refreshed($request) {
        $post_id = intval($request->get_param('id'));
        
        // Check if post exists
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('not_found', 'Post not found', array('status' => 404));
        }
        
        // Ensure constants are defined
        if (!defined('ALMASEO_EG_META_STATUS')) {
            define('ALMASEO_EG_META_STATUS', '_almaseo_evergreen_status');
        }
        if (!defined('ALMASEO_EG_STATUS_EVERGREEN')) {
            define('ALMASEO_EG_STATUS_EVERGREEN', 'evergreen');
        }
        
        // Update status to evergreen
        update_post_meta($post_id, ALMASEO_EG_META_STATUS, ALMASEO_EG_STATUS_EVERGREEN);
        update_post_meta($post_id, '_almaseo_eg_last_calculated', time());
        update_post_meta($post_id, '_almaseo_eg_refreshed', time());
        update_post_meta($post_id, '_almaseo_eg_status_reasons', array('manually_refreshed'));
        
        // Log the refresh
        $this->log_recalculation($post_id, ALMASEO_EG_STATUS_EVERGREEN, 'manual_refresh');
        
        return array(
            'success' => true,
            'data' => array(
                'status' => ALMASEO_EG_STATUS_EVERGREEN,
                'last_calculated' => time(),
                'message' => __('Content marked as refreshed', 'almaseo')
            )
        );
    }
    
    /**
     * Recalculate status
     */
    public function recalculate_status($request) {
        $post_id = intval($request->get_param('id'));
        
        // Check if post exists
        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('not_found', 'Post not found', array('status' => 404));
        }
        
        // Load evergreen functions if needed
        if (!function_exists('almaseo_eg_calculate_single_post')) {
            $plugin_dir = plugin_dir_path(dirname(dirname(__FILE__)));
            require_once $plugin_dir . 'includes/evergreen/scoring.php';
        }
        
        // Recalculate
        $result = almaseo_eg_calculate_single_post($post_id);
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => 'Failed to recalculate status'
            );
        }
        
        // Get updated data
        $status = almaseo_eg_get_post_status($post_id);
        $last_calculated = time();
        $reasons = get_post_meta($post_id, '_almaseo_eg_status_reasons', true);
        $score = get_post_meta($post_id, '_almaseo_eg_score', true);
        
        // Update last calculated time
        update_post_meta($post_id, '_almaseo_eg_last_calculated', $last_calculated);
        
        // Log the recalculation
        $this->log_recalculation($post_id, $status, 'manual');
        
        return array(
            'success' => true,
            'data' => array(
                'status' => $status,
                'last_calculated' => $last_calculated,
                'reasons' => $reasons ?: array(),
                'score' => $score
            )
        );
    }
    
    /**
     * Bulk operation
     */
    public function bulk_operation($request) {
        $action = $request->get_param('action');
        $post_ids = $request->get_param('post_ids');
        
        switch ($action) {
            case 'recalculate':
                return $this->bulk_recalculate($post_ids);
                
            case 'reset':
                return $this->bulk_reset($post_ids);
                
            case 'export':
                return $this->export_data($post_ids);
                
            default:
                return new WP_Error('invalid_action', 'Invalid action', array('status' => 400));
        }
    }
    
    /**
     * Bulk recalculate
     */
    private function bulk_recalculate($post_ids = null) {
        // Load evergreen functions if needed
        if (!function_exists('almaseo_eg_calculate_single_post')) {
            $plugin_dir = plugin_dir_path(dirname(dirname(__FILE__)));
            require_once $plugin_dir . 'includes/evergreen/scoring.php';
        }
        
        // Get posts to recalculate
        if (empty($post_ids)) {
            $post_ids = get_posts(array(
                'post_type' => array('post', 'page'),
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids'
            ));
        }
        
        $processed = 0;
        $failed = 0;
        
        foreach ($post_ids as $post_id) {
            $result = almaseo_eg_calculate_single_post($post_id);
            if ($result !== false) {
                $processed++;
            } else {
                $failed++;
            }
        }
        
        return array(
            'success' => true,
            'data' => array(
                'processed' => $processed,
                'failed' => $failed,
                'total' => count($post_ids)
            )
        );
    }
    
    /**
     * Bulk reset
     */
    private function bulk_reset($post_ids = null) {
        // Get posts to reset
        if (empty($post_ids)) {
            $post_ids = get_posts(array(
                'post_type' => array('post', 'page'),
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids',
                'meta_key' => '_almaseo_eg_status',
                'meta_compare' => 'EXISTS'
            ));
        }
        
        $processed = 0;
        
        foreach ($post_ids as $post_id) {
            delete_post_meta($post_id, '_almaseo_eg_status');
            delete_post_meta($post_id, '_almaseo_eg_last_calculated');
            delete_post_meta($post_id, '_almaseo_eg_status_reasons');
            delete_post_meta($post_id, '_almaseo_eg_score');
            delete_post_meta($post_id, '_almaseo_eg_traffic_data');
            $processed++;
        }
        
        return array(
            'success' => true,
            'data' => array(
                'processed' => $processed
            )
        );
    }
    
    /**
     * Export data
     */
    private function export_data($post_ids = null) {
        // Load evergreen functions if needed
        if (!function_exists('almaseo_eg_get_post_status')) {
            $plugin_dir = plugin_dir_path(dirname(dirname(__FILE__)));
            require_once $plugin_dir . 'includes/evergreen/functions.php';
        }
        
        // Get posts to export
        if (empty($post_ids)) {
            $post_ids = get_posts(array(
                'post_type' => array('post', 'page'),
                'post_status' => 'publish',
                'numberposts' => -1,
                'fields' => 'ids',
                'meta_key' => '_almaseo_eg_status',
                'meta_compare' => 'EXISTS'
            ));
        }
        
        $data = array();
        
        foreach ($post_ids as $post_id) {
            $post = get_post($post_id);
            if (!$post) continue;
            
            $status = almaseo_eg_get_post_status($post_id);
            $last_calculated = get_post_meta($post_id, '_almaseo_eg_last_calculated', true);
            $score = get_post_meta($post_id, '_almaseo_eg_score', true);
            $ages = almaseo_eg_calculate_post_ages($post_id);
            
            $data[] = array(
                'id' => $post_id,
                'title' => $post->post_title,
                'url' => get_permalink($post_id),
                'status' => $status,
                'score' => $score,
                'published_days' => $ages['published_days'],
                'updated_days' => $ages['updated_days'],
                'last_calculated' => $last_calculated ? date('Y-m-d H:i:s', $last_calculated) : ''
            );
        }
        
        return array(
            'success' => true,
            'data' => $data
        );
    }
    
    /**
     * Log recalculation
     */
    private function log_recalculation($post_id, $status, $trigger = 'manual') {
        // Get existing log
        $log = get_option('almaseo_eg_recalculation_log', array());
        
        // Add entry
        $log[] = array(
            'time' => time(),
            'post_id' => $post_id,
            'status' => $status,
            'trigger' => $trigger,
            'user_id' => get_current_user_id()
        );
        
        // Keep only last 100 entries
        if (count($log) > 100) {
            $log = array_slice($log, -100);
        }
        
        // Save log
        update_option('almaseo_eg_recalculation_log', $log);
    }
}

// Initialize
AlmaSEO_Evergreen_REST_API::get_instance();