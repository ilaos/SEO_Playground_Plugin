<?php
/**
 * AlmaSEO Robots.txt and Headers Integration
 * 
 * Adds sitemap hints to robots.txt and HTTP headers
 * 
 * @package AlmaSEO
 * @since 4.12.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Robots_Integration {
    
    /**
     * Initialize
     */
    public static function init() {
        $settings = get_option('almaseo_sitemap_settings', array());
        
        if (empty($settings['enabled'])) {
            return;
        }
        
        // Add to robots.txt
        add_filter('robots_txt', array(__CLASS__, 'add_to_robots_txt'), 10, 2);
        
        // Add HTTP headers on sitemap responses
        add_action('template_redirect', array(__CLASS__, 'add_http_headers'), 1);
        
        // Add link header to all pages
        add_action('wp_head', array(__CLASS__, 'add_link_tag'), 1);
        add_action('template_redirect', array(__CLASS__, 'add_link_header'), 1);
    }
    
    /**
     * Add sitemap to robots.txt
     * 
     * @param string $output Robots.txt content
     * @param bool $public Whether site is public
     * @return string Modified robots.txt
     */
    public static function add_to_robots_txt($output, $public) {
        if (!$public) {
            return $output;
        }
        
        $sitemap_url = home_url('/almaseo-sitemap.xml');
        
        // Check if sitemap is already in output
        if (strpos($output, $sitemap_url) !== false) {
            return $output;
        }
        
        // Check for any existing sitemap directive
        if (preg_match('/^Sitemap:/mi', $output)) {
            // Add our sitemap after existing ones
            $output = preg_replace(
                '/(Sitemap:[^\n]+)$/mi',
                "$1\nSitemap: $sitemap_url",
                $output,
                1
            );
        } else {
            // Add at the end
            $output = trim($output) . "\n\nSitemap: $sitemap_url\n";
        }
        
        /**
         * Filter robots.txt sitemap URL
         * 
         * @since 4.12.0
         * @param string $output Modified robots.txt content
         * @param string $sitemap_url Sitemap URL
         */
        return apply_filters('almaseo_robots_txt_output', $output, $sitemap_url);
    }
    
    /**
     * Add HTTP headers on sitemap responses
     */
    public static function add_http_headers() {
        // Check if this is a sitemap request
        $sitemap = get_query_var('almaseo_sitemap');
        
        if (empty($sitemap)) {
            return;
        }
        
        // Add custom header
        $index_url = home_url('/almaseo-sitemap.xml');
        header('X-AlmaSEO-Sitemaps: index=' . $index_url);
        
        // Add cache headers
        header('Cache-Control: max-age=3600, must-revalidate');
        header('X-Robots-Tag: noindex, follow');
        
        // Add CORS header for sitemap consumption
        $allowed_origins = array(
            'https://www.google.com',
            'https://www.bing.com',
            'https://search.google.com'
        );
        
        $origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
        if (in_array($origin, $allowed_origins)) {
            header('Access-Control-Allow-Origin: ' . $origin);
        }
    }
    
    /**
     * Add link tag to HTML head
     */
    public static function add_link_tag() {
        if (is_admin()) {
            return;
        }
        
        $sitemap_url = home_url('/almaseo-sitemap.xml');
        echo '<link rel="sitemap" type="application/xml" title="Sitemap" href="' . esc_url($sitemap_url) . '" />' . "\n";
    }
    
    /**
     * Add Link header for sitemap discovery
     */
    public static function add_link_header() {
        if (is_admin() || !is_singular()) {
            return;
        }
        
        $sitemap_url = home_url('/almaseo-sitemap.xml');
        header('Link: <' . $sitemap_url . '>; rel="sitemap"');
    }
    
    /**
     * Get robots.txt preview
     * 
     * @return string
     */
    public static function get_robots_preview() {
        // Get current robots.txt
        ob_start();
        do_robots();
        $robots = ob_get_clean();
        
        // Strip HTML if any
        $robots = strip_tags($robots);
        
        return $robots;
    }
    
    /**
     * Test if sitemap is accessible
     * 
     * @return bool|WP_Error
     */
    public static function test_sitemap_access() {
        $sitemap_url = home_url('/almaseo-sitemap.xml');
        
        $response = wp_remote_head($sitemap_url, array(
            'timeout' => 10,
            'redirection' => 5,
            'user-agent' => 'AlmaSEO/1.0 (Sitemap Test)'
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code === 200) {
            return true;
        }
        
        return new WP_Error(
            'sitemap_access_failed',
            sprintf(__('Sitemap returned HTTP %d', 'almaseo'), $code)
        );
    }
}