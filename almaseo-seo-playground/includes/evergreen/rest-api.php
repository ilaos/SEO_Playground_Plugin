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
        
        // Test endpoint to verify REST API is working (admin only)
        register_rest_route($namespace, '/evergreen/test', array(
            'methods' => 'GET',
            'callback' => function() {
                return array('success' => true, 'message' => 'REST API is working');
            },
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            }
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

        // Advanced Summary endpoint (Pro)
        register_rest_route($namespace, '/evergreen/advanced-summary', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_advanced_summary'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'post_type' => array(
                    'required' => false,
                    'default' => 'all',
                    'sanitize_callback' => 'sanitize_key'
                )
            )
        ));

        // Filtered Listing endpoint with Advanced filters (Pro)
        register_rest_route($namespace, '/evergreen/list', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_filtered_list'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'post_type' => array(
                    'required' => false,
                    'default' => 'all',
                    'sanitize_callback' => 'sanitize_key'
                ),
                'eg_status' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_text_field',
                    'description' => 'Filter by evergreen status. Accepts comma-separated values: stale,watch,evergreen'
                ),
                'risk_level' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => function($param) {
                        return empty($param) || in_array($param, array('low', 'medium', 'high'));
                    }
                ),
                'ai_freshness_min' => array(
                    'required' => false,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param >= 0 && $param <= 100;
                    }
                ),
                'ai_freshness_max' => array(
                    'required' => false,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param >= 0 && $param <= 100;
                    }
                ),
                'bucket' => array(
                    'required' => false,
                    'sanitize_callback' => 'sanitize_key',
                    'validate_callback' => function($param) {
                        return empty($param) || in_array($param, array('high_traffic_fresh', 'high_traffic_stale', 'low_traffic_stale'));
                    }
                ),
                'page' => array(
                    'required' => false,
                    'default' => 1,
                    'sanitize_callback' => 'absint'
                ),
                'per_page' => array(
                    'required' => false,
                    'default' => 20,
                    'sanitize_callback' => 'absint',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0 && $param <= 100;
                    }
                )
            )
        ));
    }
    
    /**
     * Check permission (Application Password-friendly)
     *
     * Only relies on capabilities, not explicit cookie/session checks.
     * WordPress sets current user correctly for Application Password auth via Basic Auth,
     * so current_user_can() works seamlessly.
     *
     * @param WP_REST_Request $request Request object
     * @return bool Whether the user has permission
     */
    public function check_permission($request) {
        // Check if user can edit the specific post (if post_id provided)
        $post_id = $request->get_param('id');

        if ($post_id) {
            return current_user_can('edit_post', $post_id);
        }

        // For bulk/list operations, check general capability
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
                'message' => __('Content marked as refreshed', 'almaseo-seo-playground')
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
                'last_calculated' => $last_calculated ? gmdate('Y-m-d H:i:s', $last_calculated) : ''
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

    /**
     * Get Advanced Summary (Pro)
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_advanced_summary($request) {
        // Check Pro tier
        if (!almaseo_feature_available('evergreen_advanced')) {
            return new WP_Error(
                'evergreen_advanced_required',
                __('Advanced Evergreen features require Pro or Agency tier.', 'almaseo-seo-playground'),
                array('status' => 403)
            );
        }

        $post_type = $request->get_param('post_type');

        // Load dashboard functions if needed
        if (!function_exists('almaseo_eg_get_advanced_summary')) {
            $plugin_dir = plugin_dir_path(dirname(dirname(__FILE__)));
            require_once $plugin_dir . 'includes/evergreen/dashboard.php';
        }

        $summary = almaseo_eg_get_advanced_summary($post_type);

        return rest_ensure_response($summary);
    }

    /**
     * Get Filtered List (Pro)
     *
     * @param WP_REST_Request $request Request object
     * @return WP_REST_Response|WP_Error Response object or error
     */
    public function get_filtered_list($request) {
        // Check Pro tier for advanced filters
        $has_advanced_filters = (
            !empty($request->get_param('risk_level')) ||
            !empty($request->get_param('ai_freshness_min')) ||
            !empty($request->get_param('ai_freshness_max')) ||
            !empty($request->get_param('bucket'))
        );

        if ($has_advanced_filters && !almaseo_feature_available('evergreen_advanced')) {
            return new WP_Error(
                'evergreen_advanced_required',
                __('Advanced filtering requires Pro or Agency tier.', 'almaseo-seo-playground'),
                array('status' => 403)
            );
        }

        global $wpdb;

        // Get parameters
        $post_type = $request->get_param('post_type');
        $eg_status_param = $request->get_param('eg_status');
        $risk_level = $request->get_param('risk_level');
        $ai_freshness_min = $request->get_param('ai_freshness_min');
        $ai_freshness_max = $request->get_param('ai_freshness_max');
        $bucket = $request->get_param('bucket');
        $page = max(1, (int) $request->get_param('page'));
        $per_page = min(100, max(1, (int) $request->get_param('per_page')));

        $offset = ($page - 1) * $per_page;

        // Build post type condition
        $post_types = ($post_type === 'all')
            ? array('post', 'page', 'product')
            : array($post_type);

        $post_type_placeholders = implode(',', array_fill(0, count($post_types), '%s'));

        // Build WHERE conditions
        $where_conditions = array("p.post_type IN ($post_type_placeholders)", "p.post_status = 'publish'");
        $prepare_values = $post_types;

        // Evergreen status filter (eg_status) - supports comma-separated values
        if (!empty($eg_status_param)) {
            $allowed_statuses = array('stale', 'watch', 'evergreen');
            $requested_statuses = array_map('trim', explode(',', $eg_status_param));
            $valid_statuses = array_intersect($requested_statuses, $allowed_statuses);

            if (!empty($valid_statuses)) {
                $status_placeholders = implode(',', array_fill(0, count($valid_statuses), '%s'));
                $where_conditions[] = "pm_status.meta_value IN ($status_placeholders)";
                $prepare_values = array_merge($prepare_values, $valid_statuses);
            }
        }

        // Risk level filter
        if (!empty($risk_level)) {
            $where_conditions[] = "pm_risk.meta_value = %s";
            $prepare_values[] = $risk_level;
        }

        // AI Freshness range filter
        if (!empty($ai_freshness_min)) {
            $where_conditions[] = "CAST(COALESCE(pm_ai.meta_value, 0) AS UNSIGNED) >= %d";
            $prepare_values[] = $ai_freshness_min;
        }
        if (!empty($ai_freshness_max)) {
            $where_conditions[] = "CAST(COALESCE(pm_ai.meta_value, 0) AS UNSIGNED) <= %d";
            $prepare_values[] = $ai_freshness_max;
        }

        // Bucket filter (requires more complex logic)
        if (!empty($bucket)) {
            $adv_settings = get_option('almaseo_evergreen_advanced_settings', array(
                'stale_days_threshold' => 365
            ));

            switch ($bucket) {
                case 'high_traffic_fresh':
                    $where_conditions[] = "CAST(COALESCE(pm_clicks90.meta_value, 0) AS UNSIGNED) >= 100";
                    $where_conditions[] = "DATEDIFF(NOW(), p.post_modified) <= " . (int) $adv_settings['stale_days_threshold'];
                    break;
                case 'high_traffic_stale':
                    $where_conditions[] = "CAST(COALESCE(pm_clicks90.meta_value, 0) AS UNSIGNED) >= 100";
                    $where_conditions[] = "DATEDIFF(NOW(), p.post_modified) > " . (int) $adv_settings['stale_days_threshold'];
                    break;
                case 'low_traffic_stale':
                    $where_conditions[] = "CAST(COALESCE(pm_clicks90.meta_value, 0) AS UNSIGNED) < 100";
                    $where_conditions[] = "DATEDIFF(NOW(), p.post_modified) > " . (int) $adv_settings['stale_days_threshold'];
                    break;
            }
        }

        $where_sql = implode(' AND ', $where_conditions);

        // Build query
        $query = "
            SELECT
                p.ID,
                p.post_title,
                p.post_name,
                p.post_type,
                p.post_modified,
                COALESCE(pm_refresh.meta_value, 0) as refresh_score,
                COALESCE(pm_ai.meta_value, 0) as ai_score,
                COALESCE(pm_risk.meta_value, '') as risk_level,
                COALESCE(pm_status.meta_value, '') as eg_status,
                COALESCE(pm_clicks90.meta_value, 0) as clicks_90d,
                COALESCE(pm_clicksprev.meta_value, 0) as clicks_prev90d,
                DATEDIFF(NOW(), p.post_modified) as days_since_update
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_refresh ON p.ID = pm_refresh.post_id AND pm_refresh.meta_key = '_almaseo_evergreen_refresh_score'
            LEFT JOIN {$wpdb->postmeta} pm_ai ON p.ID = pm_ai.post_id AND pm_ai.meta_key = '_almaseo_evergreen_ai_freshness_score'
            LEFT JOIN {$wpdb->postmeta} pm_risk ON p.ID = pm_risk.post_id AND pm_risk.meta_key = '_almaseo_evergreen_risk_level'
            LEFT JOIN {$wpdb->postmeta} pm_status ON p.ID = pm_status.post_id AND pm_status.meta_key = '_almaseo_eg_status'
            LEFT JOIN {$wpdb->postmeta} pm_clicks90 ON p.ID = pm_clicks90.post_id AND pm_clicks90.meta_key = '_almaseo_eg_clicks_90d'
            LEFT JOIN {$wpdb->postmeta} pm_clicksprev ON p.ID = pm_clicksprev.post_id AND pm_clicksprev.meta_key = '_almaseo_eg_clicks_prev90d'
            WHERE $where_sql
            ORDER BY CAST(COALESCE(pm_refresh.meta_value, 0) AS UNSIGNED) DESC, p.post_modified DESC
            LIMIT %d OFFSET %d
        ";

        $prepare_values[] = $per_page;
        $prepare_values[] = $offset;

        $results = $wpdb->get_results($wpdb->prepare($query, ...$prepare_values));

        // Count total
        $count_query = "
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm_risk ON p.ID = pm_risk.post_id AND pm_risk.meta_key = '_almaseo_evergreen_risk_level'
            LEFT JOIN {$wpdb->postmeta} pm_ai ON p.ID = pm_ai.post_id AND pm_ai.meta_key = '_almaseo_evergreen_ai_freshness_score'
            LEFT JOIN {$wpdb->postmeta} pm_clicks90 ON p.ID = pm_clicks90.post_id AND pm_clicks90.meta_key = '_almaseo_eg_clicks_90d'
            WHERE $where_sql
        ";

        // Remove limit/offset values for count
        $count_values = array_slice($prepare_values, 0, -2);
        $total = (int) $wpdb->get_var($wpdb->prepare($count_query, ...$count_values));

        // Format results
        $posts = array();
        foreach ($results as $row) {
            // Calculate traffic trend percentage
            $clicks_90d = (int) $row->clicks_90d;
            $clicks_prev90d = (int) $row->clicks_prev90d;
            $traffic_trend = 0;
            if ($clicks_prev90d > 0) {
                $traffic_trend = round(
                    (($clicks_90d - $clicks_prev90d) / $clicks_prev90d) * 100,
                    1
                );
            }

            $posts[] = array(
                'id' => (int) $row->ID,
                'title' => $row->post_title,
                'slug' => $row->post_name,
                'post_type' => $row->post_type,
                'modified' => $row->post_modified,
                'days_since_update' => (int) $row->days_since_update,
                'refresh_score' => (int) $row->refresh_score,
                'ai_score' => (int) $row->ai_score,
                'risk_level' => $row->risk_level,
                'eg_status' => $row->eg_status,
                'clicks_90d' => $clicks_90d,
                'clicks_prev90d' => $clicks_prev90d,
                'traffic_trend' => $traffic_trend,
                'edit_link' => get_edit_post_link($row->ID, 'raw'),
                'permalink' => get_permalink($row->ID)
            );
        }

        return rest_ensure_response(array(
            'posts' => $posts,
            'total' => $total,
            'total_pages' => ceil($total / $per_page),
            'page' => $page,
            'per_page' => $per_page
        ));
    }
}

// Initialize
AlmaSEO_Evergreen_REST_API::get_instance();