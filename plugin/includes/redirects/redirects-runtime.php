<?php
/**
 * AlmaSEO Redirects Runtime - Front-end redirect execution
 * 
 * @package AlmaSEO
 * @subpackage Redirects
 * @since 6.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AlmaSEO_Redirects_Runtime {
    
    /**
     * Initialize the runtime handler
     */
    public static function init() {
        // Hook very early to catch requests before template loading
        add_action('template_redirect', array(__CLASS__, 'handle_redirect'), 1);
    }
    
    /**
     * Handle redirect execution
     */
    public static function handle_redirect() {
        // Skip admin, AJAX, and cron requests
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Skip if user is logged in and has manage_options (avoid admin redirect loops)
        if (current_user_can('manage_options') && isset($_GET['almaseo_redirect_test'])) {
            return;
        }
        
        // Get the current request path
        $request_path = self::get_request_path();
        if (!$request_path) {
            return;
        }
        
        // Get enabled redirects from cache or database
        $redirects = self::get_cached_redirects();
        
        // Check if we have a redirect for this path
        if (!isset($redirects[$request_path])) {
            return;
        }
        
        $redirect = $redirects[$request_path];
        
        // Prevent redirect loops
        if (self::would_create_loop($request_path, $redirect['target'])) {
            error_log('AlmaSEO Redirects: Potential redirect loop detected for ' . $request_path);
            return;
        }
        
        // Record the hit (async if possible)
        self::record_hit_async($redirect['id']);
        
        // Build the final target URL
        $target_url = self::build_target_url($redirect['target']);
        
        // Perform the redirect
        wp_redirect($target_url, intval($redirect['status']));
        exit;
    }
    
    /**
     * Get the current request path
     * 
     * @return string|false
     */
    private static function get_request_path() {
        // Get the request URI
        $request_uri = $_SERVER['REQUEST_URI'];
        
        // Remove query string
        $path = strtok($request_uri, '?');
        
        // Remove any subfolder from path if WordPress is in a subdirectory
        $home_path = parse_url(home_url(), PHP_URL_PATH);
        if ($home_path && $home_path !== '/') {
            $path = preg_replace('#^' . preg_quote($home_path, '#') . '#', '', $path);
        }
        
        // Normalize the path
        if (!class_exists('AlmaSEO_Redirects_Model')) {
            require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
        }
        
        return AlmaSEO_Redirects_Model::normalize_path($path);
    }
    
    /**
     * Get cached redirects or fetch from database
     * 
     * @return array
     */
    private static function get_cached_redirects() {
        $redirects = get_transient('almaseo_enabled_redirects');
        
        if ($redirects === false) {
            if (!class_exists('AlmaSEO_Redirects_Model')) {
                require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
            }
            
            $redirects = AlmaSEO_Redirects_Model::get_enabled_redirects();
            
            // Cache for 1 hour
            set_transient('almaseo_enabled_redirects', $redirects, HOUR_IN_SECONDS);
        }
        
        return $redirects;
    }
    
    /**
     * Check if redirect would create a loop
     * 
     * @param string $source
     * @param string $target
     * @return bool
     */
    private static function would_create_loop($source, $target) {
        // If target is an absolute URL, check its path
        if (filter_var($target, FILTER_VALIDATE_URL)) {
            $target_host = parse_url($target, PHP_URL_HOST);
            $site_host = parse_url(home_url(), PHP_URL_HOST);
            
            // If different domain, no loop possible
            if ($target_host !== $site_host) {
                return false;
            }
            
            // Get the path from the URL
            $target = parse_url($target, PHP_URL_PATH);
        }
        
        // Normalize target path
        if (!class_exists('AlmaSEO_Redirects_Model')) {
            require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
        }
        
        $target = AlmaSEO_Redirects_Model::normalize_path($target);
        
        // Check if source and target are the same
        return $source === $target;
    }
    
    /**
     * Build the final target URL
     * 
     * @param string $target
     * @return string
     */
    private static function build_target_url($target) {
        // If it's already an absolute URL, return as-is
        if (filter_var($target, FILTER_VALIDATE_URL)) {
            return $target;
        }
        
        // Build site-relative URL
        return home_url($target);
    }
    
    /**
     * Record hit asynchronously if possible
     * 
     * @param int $redirect_id
     */
    private static function record_hit_async($redirect_id) {
        // Try to use wp_schedule_single_event for async processing
        if (!wp_next_scheduled('almaseo_record_redirect_hit', array($redirect_id))) {
            wp_schedule_single_event(time(), 'almaseo_record_redirect_hit', array($redirect_id));
        }
        
        // Fallback to direct recording (still fast)
        if (!class_exists('AlmaSEO_Redirects_Model')) {
            require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
        }
        
        AlmaSEO_Redirects_Model::record_hit($redirect_id);
    }
}

// Hook for async hit recording
add_action('almaseo_record_redirect_hit', function($redirect_id) {
    if (!class_exists('AlmaSEO_Redirects_Model')) {
        require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
    }
    
    AlmaSEO_Redirects_Model::record_hit($redirect_id);
});

// Initialize the runtime handler
AlmaSEO_Redirects_Runtime::init();