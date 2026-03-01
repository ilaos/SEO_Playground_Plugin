<?php
/**
 * AlmaSEO Sitemap Validator
 * 
 * Validates sitemap structure, format, and compliance
 * 
 * @package AlmaSEO
 * @since 4.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Sitemap_Validator {
    
    /**
     * Run full validation suite
     * 
     * @return array Validation results
     */
    public static function run() {
        $results = array(
            'timestamp' => time(),
            'index_status' => self::validate_index(),
            'urlset_checks' => self::validate_urlsets(),
            'media_checks' => self::validate_media(),
            'news_checks' => self::validate_news(),
            'conflicts' => self::check_conflicts(),
            'recommendations' => self::get_recommendations()
        );
        
        // Store results in health
        self::update_health($results);
        
        return $results;
    }
    
    /**
     * Validate sitemap index
     */
    private static function validate_index() {
        $index_url = home_url('/almaseo-sitemap.xml');
        $manager = Alma_Sitemap_Manager::get_instance();
        $providers = $manager->get_providers();
        
        $status = array(
            'ok' => true,
            'msg' => __('Sitemap index is valid', 'almaseo'),
            'files' => 0,
            'issues' => array()
        );
        
        // Count expected files
        foreach ($providers as $provider) {
            $status['files'] += $provider->get_max_pages();
        }
        $status['files']++; // Add index itself
        
        // Test index accessibility
        $response = wp_remote_head($index_url, array('timeout' => 5));
        
        if (is_wp_error($response)) {
            $status['ok'] = false;
            $status['msg'] = __('Sitemap index is not accessible', 'almaseo');
            $status['issues'][] = $response->get_error_message();
        } else {
            $status_code = wp_remote_retrieve_response_code($response);
            if ($status_code !== 200) {
                $status['ok'] = false;
                $status['msg'] = sprintf(__('Sitemap index returned status %d', 'almaseo'), $status_code);
            }
        }
        
        // Check XML structure (simulate request)
        ob_start();
        $responder = new Alma_Sitemap_Responder($manager);
        $reflection = new ReflectionMethod($responder, 'render_index');
        $reflection->setAccessible(true);
        $reflection->invoke($responder);
        $xml_content = ob_get_clean();
        
        // Validate XML
        $xml_valid = self::validate_xml_structure($xml_content);
        if (!$xml_valid['ok']) {
            $status['ok'] = false;
            $status['issues'] = array_merge($status['issues'], $xml_valid['errors']);
        }
        
        return $status;
    }
    
    /**
     * Validate URL sets
     */
    private static function validate_urlsets() {
        $manager = Alma_Sitemap_Manager::get_instance();
        $providers = $manager->get_providers();
        $settings = $manager->get_settings();
        
        $checks = array(
            'well_formed' => true,
            'url_count' => 0,
            'max_links_ok' => true,
            'gzip_ok' => true,
            'lastmod_iso8601' => array('ok' => true, 'samples' => array()),
            'issues' => array()
        );
        
        // Check first page of each provider
        foreach ($providers as $name => $provider) {
            if ($provider->get_max_pages() > 0) {
                $urls = $provider->get_urls(1);
                $checks['url_count'] += count($urls);
                
                // Check if respecting links_per_sitemap
                if (count($urls) > $settings['links_per_sitemap']) {
                    $checks['max_links_ok'] = false;
                    $checks['issues'][] = sprintf(
                        __('Provider %s exceeds links_per_sitemap limit', 'almaseo'),
                        $name
                    );
                }
                
                // Check lastmod format
                foreach (array_slice($urls, 0, 3) as $url) {
                    if (!empty($url['lastmod'])) {
                        $is_valid = self::validate_date($url['lastmod']);
                        if (!$is_valid) {
                            $checks['lastmod_iso8601']['ok'] = false;
                            $checks['lastmod_iso8601']['samples'][] = $url['lastmod'];
                        }
                    }
                }
            }
        }
        
        // Check gzip support
        if (function_exists('gzencode')) {
            $checks['gzip_ok'] = true;
        } else {
            $checks['gzip_ok'] = false;
            $checks['issues'][] = __('Gzip compression not available', 'almaseo');
        }
        
        return $checks;
    }
    
    /**
     * Validate media sitemaps
     */
    public static function validate_media() {
        $manager = Alma_Sitemap_Manager::get_instance();
        $settings = $manager->get_settings();
        
        $checks = array(
            'image' => array(
                'enabled' => $settings['media']['image']['enabled'] ?? false,
                'count' => 0,
                'issues' => array()
            ),
            'video' => array(
                'enabled' => $settings['media']['video']['enabled'] ?? false,
                'count' => 0,
                'issues' => array()
            )
        );
        
        // Check image provider
        if ($checks['image']['enabled']) {
            $image_provider = $manager->get_provider('image');
            if ($image_provider) {
                // Sample first page
                $urls = $image_provider->get_urls(1);
                foreach ($urls as $url) {
                    if (!empty($url['images'])) {
                        $checks['image']['count'] += count($url['images']);
                        
                        // Validate image URLs
                        foreach ($url['images'] as $img) {
                            if (empty($img['loc'])) {
                                $checks['image']['issues'][] = 'Missing image location';
                            } elseif (!filter_var($img['loc'], FILTER_VALIDATE_URL)) {
                                $checks['image']['issues'][] = 'Invalid image URL: ' . $img['loc'];
                            }
                        }
                    }
                }
            }
        }
        
        // Check video provider
        if ($checks['video']['enabled']) {
            $video_provider = $manager->get_provider('video');
            if ($video_provider) {
                // Sample first page
                $urls = $video_provider->get_urls(1);
                foreach ($urls as $url) {
                    if (!empty($url['videos'])) {
                        $checks['video']['count'] += count($url['videos']);
                        
                        // Validate required video fields
                        foreach ($url['videos'] as $vid) {
                            if (empty($vid['thumbnail_loc'])) {
                                $checks['video']['issues'][] = 'Missing video thumbnail';
                            }
                            if (empty($vid['title'])) {
                                $checks['video']['issues'][] = 'Missing video title';
                            }
                            if (empty($vid['description'])) {
                                $checks['video']['issues'][] = 'Missing video description';
                            }
                            if (empty($vid['content_loc']) && empty($vid['player_loc'])) {
                                $checks['video']['issues'][] = 'Missing video content or player location';
                            }
                        }
                    }
                }
            }
        }
        
        return $checks;
    }
    
    /**
     * Validate news sitemap
     */
    public static function validate_news() {
        $manager = Alma_Sitemap_Manager::get_instance();
        $settings = $manager->get_settings();
        
        $checks = array(
            'enabled' => $settings['news']['enabled'] ?? false,
            'items' => 0,
            'window_hours' => $settings['news']['window_hours'] ?? 48,
            'issues' => array(),
            'samples' => array()
        );
        
        if (!$checks['enabled']) {
            return $checks;
        }
        
        $news_provider = $manager->get_provider('news');
        if (!$news_provider) {
            $checks['issues'][] = 'News provider not initialized';
            return $checks;
        }
        
        // Get stats
        $stats = $news_provider->get_stats();
        $checks['items'] = $stats['items'];
        
        // Sample first page
        $urls = $news_provider->get_urls(1);
        $current_time = time();
        
        foreach ($urls as $url) {
            if (!empty($url['news'])) {
                // Check required fields
                if (empty($url['news']['publication']['name'])) {
                    $checks['issues'][] = 'Missing publisher name';
                }
                if (empty($url['news']['publication']['language'])) {
                    $checks['issues'][] = 'Missing language';
                }
                if (empty($url['news']['publication_date'])) {
                    $checks['issues'][] = 'Missing publication date for: ' . $url['loc'];
                }
                if (empty($url['news']['title'])) {
                    $checks['issues'][] = 'Missing title for: ' . $url['loc'];
                }
                
                // Check if within window
                if (!empty($url['news']['publication_date'])) {
                    $pub_time = strtotime($url['news']['publication_date']);
                    $age_hours = ($current_time - $pub_time) / 3600;
                    
                    if ($age_hours > $checks['window_hours']) {
                        $checks['issues'][] = sprintf(
                            'Article older than %d hours: %s (%.1f hours old)',
                            $checks['window_hours'],
                            $url['loc'],
                            $age_hours
                        );
                    }
                }
                
                // Collect sample
                if (count($checks['samples']) < 3) {
                    $checks['samples'][] = array(
                        'url' => $url['loc'],
                        'title' => $url['news']['title'] ?? '',
                        'date' => $url['news']['publication_date'] ?? ''
                    );
                }
            }
        }
        
        // Check if exceeding max_items
        if ($checks['items'] > $settings['news']['max_items']) {
            $checks['issues'][] = sprintf(
                'Item count (%d) exceeds max_items setting (%d)',
                $checks['items'],
                $settings['news']['max_items']
            );
        }
        
        // Store validation result
        if (!isset($settings['health'])) {
            $settings['health'] = array();
        }
        $settings['health']['news'] = array(
            'validated_at' => time(),
            'ok' => empty($checks['issues']),
            'items' => $checks['items'],
            'issues' => $checks['issues'],
            'samples' => $checks['samples']
        );
        update_option('almaseo_sitemap_settings', $settings, false);
        
        return $checks;
    }
    
    /**
     * Check for conflicts with other SEO plugins
     */
    private static function check_conflicts() {
        $conflicts = array();
        
        // Check Yoast
        if (defined('WPSEO_VERSION')) {
            $yoast_options = get_option('wpseo', array());
            if (!empty($yoast_options['enable_xml_sitemap'])) {
                $conflicts[] = array(
                    'plugin' => 'Yoast SEO',
                    'issue' => __('XML Sitemaps are enabled in Yoast SEO', 'almaseo'),
                    'severity' => 'high'
                );
            }
        }
        
        // Check RankMath
        if (class_exists('RankMath')) {
            $rm_options = get_option('rank-math-options-sitemap', array());
            if (!isset($rm_options['sitemap_enable']) || $rm_options['sitemap_enable']) {
                $conflicts[] = array(
                    'plugin' => 'Rank Math',
                    'issue' => __('XML Sitemaps are enabled in Rank Math', 'almaseo'),
                    'severity' => 'high'
                );
            }
        }
        
        // Check All in One SEO
        if (defined('AIOSEO_VERSION')) {
            $aioseo_options = get_option('aioseo_options', array());
            if (!empty($aioseo_options['sitemap']['general']['enable'])) {
                $conflicts[] = array(
                    'plugin' => 'All in One SEO',
                    'issue' => __('XML Sitemaps are enabled in AIOSEO', 'almaseo'),
                    'severity' => 'high'
                );
            }
        }
        
        // Check WordPress Core sitemaps
        if (function_exists('wp_sitemaps_get_server')) {
            $wp_sitemaps = wp_sitemaps_get_server();
            if ($wp_sitemaps && !has_filter('wp_sitemaps_enabled', '__return_false')) {
                $conflicts[] = array(
                    'plugin' => 'WordPress Core',
                    'issue' => __('WordPress Core sitemaps are enabled', 'almaseo'),
                    'severity' => 'medium'
                );
            }
        }
        
        return $conflicts;
    }
    
    /**
     * Get recommendations
     */
    private static function get_recommendations() {
        $recommendations = array();
        $settings = get_option('almaseo_sitemap_settings', array());
        
        // Check if takeover is safe
        $conflicts = self::check_conflicts();
        if (!empty($conflicts) && !empty($settings['takeover'])) {
            $recommendations[] = array(
                'type' => 'warning',
                'message' => __('Disable takeover mode due to conflicts with other plugins', 'almaseo')
            );
        }
        
        // Check links per sitemap
        if ($settings['links_per_sitemap'] > 5000) {
            $recommendations[] = array(
                'type' => 'info',
                'message' => __('Consider reducing links per sitemap to improve performance', 'almaseo')
            );
        }
        
        // Check IndexNow
        if (empty($settings['indexnow']['enabled'])) {
            $recommendations[] = array(
                'type' => 'tip',
                'message' => __('Enable IndexNow for instant search engine notifications', 'almaseo')
            );
        }
        
        return $recommendations;
    }
    
    /**
     * Validate XML structure
     */
    private static function validate_xml_structure($xml_content) {
        $result = array('ok' => true, 'errors' => array());
        
        // Use libxml to validate
        libxml_use_internal_errors(true);
        $doc = new DOMDocument();
        
        if (!$doc->loadXML($xml_content)) {
            $result['ok'] = false;
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                $result['errors'][] = trim($error->message);
            }
            libxml_clear_errors();
        }
        
        return $result;
    }
    
    /**
     * Validate date format for lastmod
     * 
     * @param string $date Date string
     * @return bool
     */
    public static function validate_date($date) {
        // W3C datetime format (ISO 8601)
        $patterns = array(
            '/^\d{4}-\d{2}-\d{2}$/',                              // YYYY-MM-DD
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}[+-]\d{2}:\d{2}$/', // Full with timezone
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z$/',          // UTC with Z
            '/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{3}Z$/'    // With milliseconds
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $date)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Validate changefreq value
     * 
     * @param string $freq Frequency value
     * @return bool
     */
    public static function validate_changefreq($freq) {
        $valid_values = array('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never');
        return in_array($freq, $valid_values, true);
    }
    
    /**
     * Validate priority value
     * 
     * @param float $priority Priority value
     * @return bool
     */
    public static function validate_priority($priority) {
        return is_numeric($priority) && $priority >= 0.0 && $priority <= 1.0;
    }
    
    /**
     * Update health status
     */
    private static function update_health($results) {
        $settings = get_option('almaseo_sitemap_settings', array());
        
        if (!isset($settings['health'])) {
            $settings['health'] = array();
        }
        
        $settings['health']['last_validation'] = $results;
        
        update_option('almaseo_sitemap_settings', $settings);
    }
    
    /**
     * Check if post should be included
     * 
     * @param int $post_id Post ID
     * @return bool
     */
    public static function should_include_post($post_id) {
        // Check post status
        if (get_post_status($post_id) !== 'publish') {
            return false;
        }
        
        // Check if password protected
        if (post_password_required($post_id)) {
            return false;
        }
        
        // Check for noindex meta
        $noindex = get_post_meta($post_id, '_almaseo_robots_noindex', true);
        if ($noindex) {
            return false;
        }
        
        // Check Yoast noindex if available
        if (function_exists('YoastSEO')) {
            $yoast_noindex = get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true);
            if ($yoast_noindex == '1') {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check if term should be included
     * 
     * @param int $term_id Term ID
     * @param string $taxonomy Taxonomy name
     * @return bool
     */
    public static function should_include_term($term_id, $taxonomy) {
        // Check if taxonomy is public
        $tax_obj = get_taxonomy($taxonomy);
        if (!$tax_obj || !$tax_obj->public) {
            return false;
        }
        
        // Check for noindex meta
        $noindex = get_term_meta($term_id, '_almaseo_robots_noindex', true);
        if ($noindex) {
            return false;
        }
        
        // Check if term has posts
        $term = get_term($term_id, $taxonomy);
        if (!$term || is_wp_error($term) || $term->count == 0) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if user should be included
     * 
     * @param int $user_id User ID
     * @return bool
     */
    public static function should_include_user($user_id) {
        // Check for noindex meta
        $noindex = get_user_meta($user_id, '_almaseo_robots_noindex', true);
        if ($noindex) {
            return false;
        }
        
        // Check if user has published posts
        $post_count = count_user_posts($user_id, array('post', 'page'), true);
        if ($post_count == 0) {
            return false;
        }
        
        return true;
    }
}