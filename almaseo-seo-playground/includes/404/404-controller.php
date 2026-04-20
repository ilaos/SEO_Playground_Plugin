<?php
/**
 * AlmaSEO 404 Tracker - Controller
 * 
 * @package AlmaSEO
 * @subpackage 404Tracker
 * @since 6.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AlmaSEO_404_Controller {
    
    /**
     * Initialize controller
     */
    public static function init() {
        // Add admin menu
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'), 25);
        
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
        
        // Register REST routes
        add_action('rest_api_init', array(__CLASS__, 'register_rest_routes'));
        
        // Check database on admin init
        if (is_admin()) {
            add_action('admin_init', array(__CLASS__, 'check_database'));
        }
    }
    
    /**
     * Check and update database
     */
    public static function check_database() {
        require_once dirname(__FILE__) . '/404-install.php';
        almaseo_check_404_db();
    }
    
    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'seo-playground',
            __('404 Intelligence', 'almaseo-seo-playground'),
            __('404 Intelligence', 'almaseo-seo-playground'),
            'manage_options',
            'almaseo-404-logs',
            array(__CLASS__, 'render_admin_page')
        );
    }
    
    /**
     * Render admin page
     */
    public static function render_admin_page() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'almaseo-seo-playground'));
        }
        
        // Include the admin page template
        $admin_page = dirname(dirname(dirname(__FILE__))) . '/admin/pages/404-logs.php';
        if (file_exists($admin_page)) {
            require_once $admin_page;
        } else {
            echo '<div class="error"><p>' . __('404 Logs admin page not found.', 'almaseo-seo-playground') . '</p></div>';
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public static function enqueue_admin_assets($hook) {
        // Only load on our page
        if ( strpos( $hook, 'almaseo-404-logs' ) === false ) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'almaseo-404-logs',
            plugins_url('assets/css/404-logs.css', dirname(dirname(__FILE__))),
            array(),
            ALMASEO_PLUGIN_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'almaseo-404-logs',
            plugins_url('assets/js/404-logs.js', dirname(dirname(__FILE__))),
            array('jquery'),
            ALMASEO_PLUGIN_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('almaseo-404-logs', 'almaseo404', array(
            'apiUrl' => rest_url('almaseo/v1/404s'),
            'redirectUrl' => admin_url('admin.php?page=almaseo-redirects'),
            'nonce' => wp_create_nonce('wp_rest'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this 404 log?', 'almaseo-seo-playground'),
                'confirmBulkDelete' => __('Are you sure you want to delete the selected logs?', 'almaseo-seo-playground'),
                'error' => __('An error occurred. Please try again.', 'almaseo-seo-playground'),
                'success' => __('Operation completed successfully.', 'almaseo-seo-playground'),
                'loading' => __('Loading...', 'almaseo-seo-playground'),
                'noSelection' => __('Please select at least one item.', 'almaseo-seo-playground'),
                'createRedirect' => __('Create Redirect', 'almaseo-seo-playground'),
                'ignore' => __('Ignore', 'almaseo-seo-playground'),
                'unignore' => __('Unignore', 'almaseo-seo-playground'),
                'delete' => __('Delete', 'almaseo-seo-playground')
            )
        ));

        // Intelligence JS (v7.6.0+)
        wp_enqueue_script(
            'almaseo-404-intelligence',
            plugins_url('assets/js/404-intelligence.js', dirname(dirname(__FILE__))),
            array('jquery', 'almaseo-404-logs'),
            defined( 'ALMASEO_VERSION' ) ? ALMASEO_VERSION : '7.6.0',
            true
        );
    }
    
    /**
     * Register REST routes
     */
    public static function register_rest_routes() {
        // List 404s
        register_rest_route('almaseo/v1', '/404s', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'rest_get_logs'),
            'permission_callback' => array(__CLASS__, 'rest_permission_check'),
            'args' => array(
                'search' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'ignored' => array(
                    'sanitize_callback' => function($value) {
                        return $value === 'true' || $value === '1' ? true : ($value === 'false' || $value === '0' ? false : null);
                    }
                ),
                'from' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'to' => array(
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'page' => array(
                    'sanitize_callback' => 'absint',
                    'default' => 1
                ),
                'per_page' => array(
                    'sanitize_callback' => 'absint',
                    'default' => 20,
                    'validate_callback' => function($value) {
                        return $value > 0 && $value <= 100;
                    },
                )
            )
        ));
        
        // Toggle ignore status
        register_rest_route('almaseo/v1', '/404s/(?P<id>\d+)/ignore', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'rest_ignore_log'),
            'permission_callback' => array(__CLASS__, 'rest_permission_check'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Toggle unignore status
        register_rest_route('almaseo/v1', '/404s/(?P<id>\d+)/unignore', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'rest_unignore_log'),
            'permission_callback' => array(__CLASS__, 'rest_permission_check'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Delete log
        register_rest_route('almaseo/v1', '/404s/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array(__CLASS__, 'rest_delete_log'),
            'permission_callback' => array(__CLASS__, 'rest_permission_check'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Bulk actions
        register_rest_route('almaseo/v1', '/404s/bulk', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'rest_bulk_action'),
            'permission_callback' => array(__CLASS__, 'rest_permission_check')
        ));
        
        // Prepare redirect data
        register_rest_route('almaseo/v1', '/404s/(?P<id>\d+)/to-redirect', array(
            'methods' => 'POST',
            'callback' => array(__CLASS__, 'rest_to_redirect'),
            'permission_callback' => array(__CLASS__, 'rest_permission_check'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                )
            )
        ));
        
        // Get stats
        register_rest_route('almaseo/v1', '/404s/stats', array(
            'methods' => 'GET',
            'callback' => array(__CLASS__, 'rest_get_stats'),
            'permission_callback' => array(__CLASS__, 'rest_permission_check')
        ));

        /* ── Intelligence endpoints (v7.6.0+) ── */

        // Dashboard push: impact data.
        register_rest_route('almaseo/v1', '/404s/push-impact', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'rest_push_impact'),
            'permission_callback' => 'almaseo_api_auth_check',
            'args'                => array(
                'items' => array(
                    'type'     => 'array',
                    'required' => true,
                    'items'    => array( 'type' => 'object' ),
                ),
            ),
        ));

        // Get redirect suggestions for a 404.
        register_rest_route('almaseo/v1', '/404s/(?P<id>\d+)/suggestions', array(
            'methods'             => 'GET',
            'callback'            => array(__CLASS__, 'rest_get_suggestions'),
            'permission_callback' => array(__CLASS__, 'rest_permission_check'),
        ));

        // Get spike alerts.
        register_rest_route('almaseo/v1', '/404s/spikes', array(
            'methods'             => 'GET',
            'callback'            => array(__CLASS__, 'rest_get_spikes'),
            'permission_callback' => array(__CLASS__, 'rest_permission_check'),
        ));

        // Get high-impact 404s.
        register_rest_route('almaseo/v1', '/404s/high-impact', array(
            'methods'             => 'GET',
            'callback'            => array(__CLASS__, 'rest_get_high_impact'),
            'permission_callback' => array(__CLASS__, 'rest_permission_check'),
        ));
    }
    
    /**
     * REST permission check
     */
    public static function rest_permission_check() {
        return current_user_can('manage_options');
    }
    
    /**
     * REST: Get logs
     */
    public static function rest_get_logs($request) {
        require_once dirname(__FILE__) . '/404-model.php';
        
        $args = array(
            'search' => $request->get_param('search'),
            'ignored' => $request->get_param('ignored'),
            'from' => $request->get_param('from'),
            'to' => $request->get_param('to'),
            'page' => $request->get_param('page'),
            'per_page' => $request->get_param('per_page')
        );
        
        $result = AlmaSEO_404_Model::get_logs($args);
        
        return rest_ensure_response($result);
    }
    
    /**
     * REST: Ignore log
     */
    public static function rest_ignore_log($request) {
        require_once dirname(__FILE__) . '/404-model.php';
        
        $id = $request->get_param('id');
        $result = AlmaSEO_404_Model::toggle_ignored($id, true);
        
        if ($result) {
            return rest_ensure_response(array('success' => true));
        } else {
            return new WP_Error('update_failed', __('Failed to update log.', 'almaseo-seo-playground'), array('status' => 500));
        }
    }
    
    /**
     * REST: Unignore log
     */
    public static function rest_unignore_log($request) {
        require_once dirname(__FILE__) . '/404-model.php';
        
        $id = $request->get_param('id');
        $result = AlmaSEO_404_Model::toggle_ignored($id, false);
        
        if ($result) {
            return rest_ensure_response(array('success' => true));
        } else {
            return new WP_Error('update_failed', __('Failed to update log.', 'almaseo-seo-playground'), array('status' => 500));
        }
    }
    
    /**
     * REST: Delete log
     */
    public static function rest_delete_log($request) {
        require_once dirname(__FILE__) . '/404-model.php';
        
        $id = $request->get_param('id');
        $result = AlmaSEO_404_Model::delete_log($id);
        
        if ($result) {
            return rest_ensure_response(array('success' => true));
        } else {
            return new WP_Error('delete_failed', __('Failed to delete log.', 'almaseo-seo-playground'), array('status' => 500));
        }
    }
    
    /**
     * REST: Bulk action
     */
    public static function rest_bulk_action($request) {
        require_once dirname(__FILE__) . '/404-model.php';
        
        $action = $request->get_param('action');
        $ids = $request->get_param('ids');
        
        if (!is_array($ids) || empty($ids)) {
            return new WP_Error('invalid_ids', __('Invalid IDs provided.', 'almaseo-seo-playground'), array('status' => 400));
        }
        
        // Sanitize IDs
        $ids = array_map('absint', $ids);
        
        $result = false;
        
        switch ($action) {
            case 'ignore':
                $result = AlmaSEO_404_Model::bulk_toggle_ignored($ids, true);
                break;
            case 'unignore':
                $result = AlmaSEO_404_Model::bulk_toggle_ignored($ids, false);
                break;
            case 'delete':
                $result = AlmaSEO_404_Model::bulk_delete($ids);
                break;
            default:
                return new WP_Error('invalid_action', __('Invalid action.', 'almaseo-seo-playground'), array('status' => 400));
        }
        
        if ($result) {
            return rest_ensure_response(array('success' => true));
        } else {
            return new WP_Error('bulk_failed', __('Bulk operation failed.', 'almaseo-seo-playground'), array('status' => 500));
        }
    }
    
    /**
     * REST: Prepare redirect data
     */
    public static function rest_to_redirect($request) {
        require_once dirname(__FILE__) . '/404-model.php';
        
        $id = $request->get_param('id');
        $data = AlmaSEO_404_Model::prepare_redirect_data($id);
        
        if ($data) {
            return rest_ensure_response($data);
        } else {
            return new WP_Error('not_found', __('404 log not found.', 'almaseo-seo-playground'), array('status' => 404));
        }
    }
    
    /**
     * REST: Get stats
     */
    public static function rest_get_stats() {
        require_once dirname(__FILE__) . '/404-model.php';

        $stats = AlmaSEO_404_Model::get_stats();
        $stats['top_referrer'] = AlmaSEO_404_Model::get_top_referrer();

        return rest_ensure_response($stats);
    }

    /* ──────────────── Intelligence endpoints (v7.6.0+) ── */

    /**
     * REST: Push impact data from dashboard.
     */
    public static function rest_push_impact( $request ) {
        $items = $request->get_param( 'items' );

        if ( ! is_array( $items ) || empty( $items ) ) {
            return new WP_Error( 'invalid_payload', 'items must be a non-empty array.', array( 'status' => 400 ) );
        }

        $counts = AlmaSEO_404_Intelligence::process_impact_push( $items );

        return rest_ensure_response( $counts );
    }

    /**
     * REST: Get redirect suggestions for a specific 404 log.
     */
    public static function rest_get_suggestions( $request ) {
        require_once dirname(__FILE__) . '/404-model.php';

        $id  = absint( $request['id'] );
        $log = AlmaSEO_404_Model::get_log( $id );

        if ( ! $log ) {
            return new WP_Error( 'not_found', '404 log not found.', array( 'status' => 404 ) );
        }

        $suggestions = AlmaSEO_404_Intelligence::get_suggestions( $log['path'] );

        // If dashboard pushed a suggested target, prepend it.
        if ( ! empty( $log['suggested_target'] ) ) {
            array_unshift( $suggestions, array(
                'post_id' => null,
                'url'     => $log['suggested_target'],
                'title'   => 'Dashboard suggestion',
                'score'   => 100,
                'reason'  => 'Suggested by AlmaSEO dashboard',
            ) );
        }

        return rest_ensure_response( $suggestions );
    }

    /**
     * REST: Get spike alerts.
     */
    public static function rest_get_spikes() {
        $spikes = AlmaSEO_404_Intelligence::detect_spikes();

        return rest_ensure_response( $spikes );
    }

    /**
     * REST: Get high-impact 404s (sorted by impact_score).
     */
    public static function rest_get_high_impact() {
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_404_log';

        $results = $wpdb->get_results(
            "SELECT id, path, hits, impact_score, impressions, clicks, suggested_target, last_seen
             FROM {$table}
             WHERE is_ignored = 0 AND impact_score IS NOT NULL AND impact_score > 0
             ORDER BY impact_score DESC
             LIMIT 20",
            ARRAY_A
        );

        return rest_ensure_response( $results ? $results : array() );
    }
}