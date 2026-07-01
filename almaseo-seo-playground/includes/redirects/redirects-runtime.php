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
        if (current_user_can('manage_options') && isset($_GET['almaseo_redirect_test'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- front-end isset() check of a test flag; changes no state
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
        $status   = intval($redirect['status']);

        // 410 Gone / 451 Unavailable: send the status code without a Location
        // header. wp_redirect() only accepts 3xx codes and would call wp_die()
        // on these, so they must be handled separately.
        if ($status === 410 || $status === 451) {
            self::record_hit($redirect['id']);
            self::serve_gone($status);
            exit;
        }

        // Prevent redirect loops
        if (self::would_create_loop($request_path, $redirect['target'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AlmaSEO Redirects: Potential redirect loop detected for ' . $request_path); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- debug-only logging, gated behind WP_DEBUG
            }
            return;
        }

        // Record the hit (async if possible)
        self::record_hit($redirect['id']);

        // Build the final target URL
        $target_url = self::build_target_url($redirect['target']);

        // Perform the redirect
        wp_redirect(esc_url_raw($target_url), $status); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect -- Intentional: redirects may target external URLs configured by admin
        exit;
    }

    /**
     * Emit a 410 Gone / 451 Unavailable response with a minimal body.
     *
     * @param int $status 410 or 451.
     */
    private static function serve_gone($status) {
        nocache_headers();
        status_header($status);
        header('X-Robots-Tag: noindex');
        header('Content-Type: text/html; charset=utf-8');

        $label = ($status === 451)
            ? __('Unavailable For Legal Reasons', 'almaseo-seo-playground')
            : __('Gone', 'almaseo-seo-playground');

        echo '<!DOCTYPE html><html><head><meta charset="utf-8"><title>'
            . esc_html($status . ' ' . $label)
            . '</title><meta name="robots" content="noindex"></head><body><h1>'
            . esc_html($status . ' ' . $label)
            . '</h1><p>' . esc_html__('This content is no longer available.', 'almaseo-seo-playground')
            . '</p></body></html>';
    }
    
    /**
     * Get the current request path
     * 
     * @return string|false
     */
    private static function get_request_path() {
        // Get the request URI
        $request_uri = isset($_SERVER['REQUEST_URI']) ? sanitize_text_field(wp_unslash($_SERVER['REQUEST_URI'])) : '';
        
        // Remove query string
        $path = strtok($request_uri, '?');
        
        // Remove any subfolder from path if WordPress is in a subdirectory
        $home_path = wp_parse_url(home_url(), PHP_URL_PATH);
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
            $target_host = wp_parse_url($target, PHP_URL_HOST);
            $site_host = wp_parse_url(home_url(), PHP_URL_HOST);
            
            // If different domain, no loop possible
            if ($target_host !== $site_host) {
                return false;
            }
            
            // Get the path from the URL
            $target = wp_parse_url($target, PHP_URL_PATH);
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
     * Record a redirect hit.
     *
     * This used to defer the increment to a single wp-cron event guarded by
     * wp_next_scheduled(), which silently DROPPED hits: WordPress dedupes
     * identical hook+args events within a ~10-minute window, and the
     * wp_next_scheduled() guard skipped BOTH scheduling and direct recording
     * while an event was pending — so a busy redirect recorded at most one hit
     * per cron cycle, and none at all when WP-Cron was disabled. A hit is a
     * single atomic `hits = hits + 1` UPDATE, so there is no reason to defer it;
     * record it directly and synchronously for an accurate count.
     *
     * @param int $redirect_id
     */
    private static function record_hit($redirect_id) {
        if (!class_exists('AlmaSEO_Redirects_Model')) {
            require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
        }

        AlmaSEO_Redirects_Model::record_hit($redirect_id);
    }
}

// Back-compat: drain any hit-recording events still queued from older versions
// (which scheduled this hook). Nothing schedules it anymore; this just records
// those stragglers once, then WP removes the fired single events.
add_action('almaseo_record_redirect_hit', function($redirect_id) {
    if (!class_exists('AlmaSEO_Redirects_Model')) {
        require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
    }

    AlmaSEO_Redirects_Model::record_hit($redirect_id);
});

// Initialize the runtime handler
AlmaSEO_Redirects_Runtime::init();