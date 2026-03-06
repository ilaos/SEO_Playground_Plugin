<?php
/**
 * AlmaSEO Redirects Controller - Business logic and menu registration
 * 
 * @package AlmaSEO
 * @subpackage Redirects
 * @since 6.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AlmaSEO_Redirects_Controller {
    
    /**
     * Initialize the controller
     */
    public static function init() {
        // Add admin menu
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'), 20);
        
        // Enqueue admin scripts and styles
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
        
        // Initialize REST API
        add_action('rest_api_init', array(__CLASS__, 'register_rest_routes'));
        
        // Check database on admin init
        if (is_admin()) {
            require_once plugin_dir_path(__FILE__) . 'redirects-install.php';
        }
    }
    
    /**
     * Add admin menu item
     */
    public static function add_admin_menu() {
        // Don't check Pro tier here - just capability
        // The menu will only be visible to users with manage_options anyway
        
        add_submenu_page(
            'seo-playground',  // Changed from 'almaseo-dashboard' to match parent menu
            __('Redirect Manager', 'almaseo'),
            __('Redirects', 'almaseo'),
            'manage_options',
            'almaseo-redirects',
            array(__CLASS__, 'render_admin_page')
        );
    }
    
    /**
     * Render the admin page
     */
    public static function render_admin_page() {
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'almaseo'));
        }

        // Include the admin page template
        require_once plugin_dir_path(dirname(dirname(__FILE__))) . 'admin/pages/redirects.php';
    }
    
    /**
     * Enqueue admin assets
     */
    public static function enqueue_admin_assets($hook) {
        // Only load on our page
        if ( strpos( $hook, 'almaseo-redirects' ) === false ) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'almaseo-redirects',
            plugins_url('assets/css/redirects.css', dirname(dirname(__FILE__))),
            array(),
            '6.1.0'
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'almaseo-redirects',
            plugins_url('assets/js/redirects.js', dirname(dirname(__FILE__))),
            array('jquery', 'wp-api'),
            '6.1.0',
            true
        );
        
        // Localize script
        wp_localize_script('almaseo-redirects', 'almaseoRedirects', array(
            'apiUrl' => rest_url('almaseo/v1/redirects'),
            'nonce' => wp_create_nonce('wp_rest'),
            'homeUrl' => home_url(),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this redirect?', 'almaseo'),
                'confirmBulkDelete' => __('Are you sure you want to delete the selected redirects?', 'almaseo'),
                'error' => __('An error occurred. Please try again.', 'almaseo'),
                'success' => __('Operation completed successfully.', 'almaseo'),
                'invalidSource' => __('Source path must start with /', 'almaseo'),
                'invalidTarget' => __('Please enter a valid URL or path starting with /', 'almaseo'),
                'duplicateSource' => __('A redirect with this source path already exists.', 'almaseo'),
                'loopDetected' => __('This would create a redirect loop.', 'almaseo')
            )
        ));
    }
    
    /**
     * Register REST API routes
     */
    public static function register_rest_routes() {
        require_once plugin_dir_path(__FILE__) . 'redirects-rest.php';
        $rest_controller = new AlmaSEO_Redirects_REST();
        $rest_controller->register_routes();

        /* ── Intelligence endpoints (v7.6.0+) ── */

        // Dashboard push: traffic recovery data.
        register_rest_route('almaseo/v1', '/redirects/push-traffic', array(
            'methods'             => 'POST',
            'callback'            => array(__CLASS__, 'rest_push_traffic'),
            'permission_callback' => 'almaseo_api_auth_check',
            'args'                => array(
                'items' => array(
                    'type'     => 'array',
                    'required' => true,
                    'items'    => array( 'type' => 'object' ),
                ),
            ),
        ));

        // Detect redirect chains.
        register_rest_route('almaseo/v1', '/redirects/chains', array(
            'methods'             => 'GET',
            'callback'            => array(__CLASS__, 'rest_detect_chains'),
            'permission_callback' => function () { return current_user_can( 'manage_options' ); },
        ));

        // Traffic recovery report.
        register_rest_route('almaseo/v1', '/redirects/recovery', array(
            'methods'             => 'GET',
            'callback'            => array(__CLASS__, 'rest_recovery_report'),
            'permission_callback' => function () { return current_user_can( 'manage_options' ); },
        ));
    }
    
    /**
     * Check if Pro features are enabled
     *
     * Validate redirect data
     * 
     * @param array $data
     * @param int $exclude_id Optional ID to exclude from duplicate check
     * @return array|WP_Error
     */
    public static function validate_redirect_data($data, $exclude_id = null) {
        $errors = new WP_Error();
        
        // Validate source
        if (empty($data['source'])) {
            $errors->add('invalid_source', __('Source path is required.', 'almaseo'));
        } else {
            require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
            $normalized_source = AlmaSEO_Redirects_Model::normalize_path($data['source']);
            
            if (!$normalized_source) {
                $errors->add('invalid_source', __('Invalid source path. Must start with /.', 'almaseo'));
            } else {
                // Check for duplicates
                global $wpdb;
                $table = $wpdb->prefix . 'almaseo_redirects';
                
                $query = "SELECT COUNT(*) FROM $table WHERE source = %s";
                $params = array($normalized_source);
                
                if ($exclude_id) {
                    $query .= " AND id != %d";
                    $params[] = $exclude_id;
                }
                
                $exists = $wpdb->get_var($wpdb->prepare($query, $params));
                
                if ($exists > 0) {
                    $errors->add('duplicate_source', __('A redirect with this source path already exists.', 'almaseo'));
                }
            }
        }
        
        // Validate target
        if (empty($data['target'])) {
            $errors->add('invalid_target', __('Target URL is required.', 'almaseo'));
        } else {
            // Check if it's a valid URL or path
            if (!filter_var($data['target'], FILTER_VALIDATE_URL)) {
                // Try as a path
                require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
                $normalized_target = AlmaSEO_Redirects_Model::normalize_path($data['target']);
                
                if (!$normalized_target) {
                    $errors->add('invalid_target', __('Invalid target. Must be a valid URL or path starting with /.', 'almaseo'));
                }
                
                // Check for potential loops
                if (isset($normalized_source) && $normalized_source === $normalized_target) {
                    $errors->add('redirect_loop', __('Source and target cannot be the same.', 'almaseo'));
                }
            }
        }
        
        // Validate status
        if (isset($data['status']) && !in_array($data['status'], array(301, 302))) {
            $errors->add('invalid_status', __('Status must be 301 or 302.', 'almaseo'));
        }
        
        if ($errors->has_errors()) {
            return $errors;
        }
        
        return true;
    }
    
    /* ──────────────── Intelligence REST callbacks (v7.6.0+) ── */

    /**
     * REST: Push traffic recovery data from dashboard.
     */
    public static function rest_push_traffic( $request ) {
        $items = $request->get_param( 'items' );

        if ( ! is_array( $items ) || empty( $items ) ) {
            return new WP_Error( 'invalid_payload', 'items must be a non-empty array.', array( 'status' => 400 ) );
        }

        $counts = AlmaSEO_Redirects_Intelligence::process_traffic_push( $items );

        return rest_ensure_response( $counts );
    }

    /**
     * REST: Detect redirect chains.
     */
    public static function rest_detect_chains() {
        $chains = AlmaSEO_Redirects_Intelligence::detect_chains();

        return rest_ensure_response( $chains );
    }

    /**
     * REST: Traffic recovery report.
     */
    public static function rest_recovery_report() {
        $report = AlmaSEO_Redirects_Intelligence::get_recovery_report();

        return rest_ensure_response( $report );
    }

    /**
     * Export redirects to CSV
     * 
     * @return string CSV content
     */
    public static function export_redirects() {
        require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
        
        $redirects = AlmaSEO_Redirects_Model::get_redirects(array(
            'per_page' => -1 // Get all
        ));
        
        // Use php://temp stream with fputcsv for proper CSV escaping
        $stream = fopen('php://temp', 'r+');
        fputcsv($stream, array('Source', 'Target', 'Status', 'Enabled', 'Hits', 'Last Hit', 'Created', 'Updated'));

        foreach ($redirects['items'] as $redirect) {
            fputcsv($stream, array(
                $redirect['source'],
                $redirect['target'],
                $redirect['status'],
                $redirect['is_enabled'],
                $redirect['hits'],
                $redirect['last_hit'] ?: 'Never',
                $redirect['created_at'],
                $redirect['updated_at']
            ));
        }

        rewind($stream);
        $csv = stream_get_contents($stream);
        fclose($stream);

        return $csv;
    }
}