<?php
/**
 * AlmaSEO 404 Tracker - Runtime Capture
 * 
 * @package AlmaSEO
 * @subpackage 404Tracker
 * @since 6.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AlmaSEO_404_Capture {
    
    /**
     * Ignored extensions (static assets)
     */
    private static $ignored_extensions = [
        'css', 'js', 'map', 'json', 'png', 'jpg', 'jpeg', 'gif', 'svg', 'webp',
        'ico', 'woff', 'woff2', 'ttf', 'eot', 'otf', 'xml', 'txt'
    ];
    
    /**
     * Ignored paths (common bot/scan paths)
     */
    private static $ignored_paths = [
        '/wp-login.php',
        '/xmlrpc.php',
        '/wp-json/wp/v2/users',
        '/favicon.ico',
        '/robots.txt',
        '/apple-touch-icon.png',
        '/apple-touch-icon-precomposed.png',
        '/.well-known/',
        '/ads.txt',
        '/sitemap.xml'
    ];
    
    /**
     * Initialize capture
     */
    public static function init() {
        // Hook early to catch 404s
        add_action('template_redirect', array(__CLASS__, 'capture_404'), 1);
    }
    
    /**
     * Capture 404 errors
     */
    public static function capture_404() {
        // Skip if not a 404
        if (!is_404()) {
            return;
        }
        
        // Skip admin, AJAX, REST, cron
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Skip REST requests
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return;
        }
        
        // Get request data
        $path = self::get_request_path();
        $query = self::get_query_string();
        
        // Apply ignore rules
        if (self::should_ignore($path)) {
            return;
        }
        
        // Get additional data
        $referrer = isset($_SERVER['HTTP_REFERER']) ? sanitize_text_field($_SERVER['HTTP_REFERER']) : null;
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? substr(sanitize_text_field($_SERVER['HTTP_USER_AGENT']), 0, 512) : null;
        $ip = self::get_client_ip();
        
        // Log the 404
        self::log_404($path, $query, $referrer, $user_agent, $ip);
    }
    
    /**
     * Get request path
     */
    private static function get_request_path() {
        $request_uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        
        // Parse URL to get path
        $parsed = parse_url($request_uri);
        $path = isset($parsed['path']) ? $parsed['path'] : '/';
        
        // Remove site subdirectory if in subdirectory install
        $site_url = parse_url(home_url());
        if (isset($site_url['path']) && $site_url['path'] !== '/') {
            $path = str_replace($site_url['path'], '', $path);
        }
        
        // Normalize path
        $path = '/' . ltrim($path, '/');
        $path = preg_replace('#/+#', '/', $path); // Collapse multiple slashes
        
        return sanitize_text_field($path);
    }
    
    /**
     * Get query string (limited to 255 chars)
     */
    private static function get_query_string() {
        if (empty($_SERVER['QUERY_STRING'])) {
            return null;
        }
        
        $query = sanitize_text_field($_SERVER['QUERY_STRING']);
        
        // Limit to 255 characters
        if (strlen($query) > 255) {
            $query = substr($query, 0, 255);
        }
        
        return $query;
    }
    
    /**
     * Check if path should be ignored
     */
    private static function should_ignore($path) {
        // Check if it's an admin path
        if (strpos($path, '/wp-admin/') === 0) {
            return true;
        }
        
        // Check ignored paths
        foreach (self::$ignored_paths as $ignored) {
            if (strpos($path, $ignored) === 0) {
                return true;
            }
        }
        
        // Check file extension
        $extension = pathinfo($path, PATHINFO_EXTENSION);
        if ($extension && in_array(strtolower($extension), self::$ignored_extensions)) {
            return true;
        }
        
        // Apply custom filter for additional ignore rules
        return apply_filters('almaseo_404_should_ignore', false, $path);
    }
    
    /**
     * Get client IP (GDPR-safe)
     */
    private static function get_client_ip() {
        // Check if IP tracking is enabled (GDPR compliance)
        if (!apply_filters('almaseo_404_track_ip', false)) {
            return null;
        }
        
        // Get IP address
        $ip = '';
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            // Cloudflare
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Load balancer
            $ips = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $ip = trim($ips[0]);
        } elseif (isset($_SERVER['HTTP_X_REAL_IP'])) {
            // Nginx proxy
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            // Direct connection
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        // Validate and pack IP
        if ($ip) {
            $packed = @inet_pton($ip);
            if ($packed !== false) {
                return $packed;
            }
        }
        
        return null;
    }
    
    /**
     * Log 404 to database
     */
    private static function log_404($path, $query, $referrer, $user_agent, $ip) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'almaseo_404_log';
        $now = current_time('mysql');
        
        // Check if this path+query already exists
        $existing = $wpdb->get_row($wpdb->prepare(
            "SELECT id, hits FROM $table WHERE path = %s AND (query = %s OR (query IS NULL AND %s IS NULL))",
            $path,
            $query,
            $query
        ));
        
        if ($existing) {
            // Update existing record
            $update_data = array(
                'hits' => $existing->hits + 1,
                'last_seen' => $now
            );
            
            // Update referrer if provided and not empty
            if ($referrer) {
                $update_data['referrer'] = $referrer;
            }
            
            // Update user agent if provided
            if ($user_agent) {
                $update_data['user_agent'] = $user_agent;
            }
            
            // Update IP if provided
            if ($ip) {
                $update_data['ip'] = $ip;
            }
            
            $wpdb->update(
                $table,
                $update_data,
                array('id' => $existing->id)
            );
        } else {
            // Insert new record
            $wpdb->insert(
                $table,
                array(
                    'path' => $path,
                    'query' => $query,
                    'referrer' => $referrer,
                    'user_agent' => $user_agent,
                    'ip' => $ip,
                    'hits' => 1,
                    'first_seen' => $now,
                    'last_seen' => $now,
                    'is_ignored' => 0
                ),
                array('%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%d')
            );
        }
        
        // Clear stats cache
        delete_transient('almaseo_404_stats');
        delete_transient('almaseo_404_top_referrer');
    }
}

// Initialize capture
add_action('init', array('AlmaSEO_404_Capture', 'init'));