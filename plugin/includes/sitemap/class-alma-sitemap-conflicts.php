<?php
/**
 * AlmaSEO Sitemap Conflict Detector
 * 
 * Scans sitemap URLs for issues like 404s, robots blocks, noindex, canonical mismatches
 * 
 * @package AlmaSEO
 * @since 4.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Sitemap_Conflicts {
    
    /**
     * Batch size for processing
     */
    const BATCH_SIZE = 200;
    
    /**
     * Option key for storing results
     */
    const OPTION_KEY = 'almaseo_sitemap_conflicts';
    
    /**
     * Robots.txt cache
     */
    private static $robots_rules = null;
    
    /**
     * Start a new scan
     */
    public static function start_scan() {
        // Check if scan already running
        $current = get_option(self::OPTION_KEY, array());
        if (!empty($current['started']) && empty($current['finished'])) {
            return array(
                'success' => false,
                'message' => __('Scan already in progress', 'almaseo')
            );
        }
        
        // Get all URLs from sitemaps
        $urls = self::get_all_urls();
        
        if (empty($urls)) {
            return array(
                'success' => false,
                'message' => __('No URLs found in sitemaps', 'almaseo')
            );
        }
        
        // Initialize scan data
        $scan_data = array(
            'run_id' => wp_generate_uuid4(),
            'started' => time(),
            'finished' => false,
            'current_batch' => 0,
            'total_urls' => count($urls),
            'totals' => array('checked' => 0, 'issues' => 0),
            'items' => array(),
            'queue' => $urls
        );
        
        update_option(self::OPTION_KEY, $scan_data, false);
        
        // Schedule first batch
        wp_schedule_single_event(time() + 1, 'almaseo_scan_batch', array($scan_data['run_id']));
        
        return array(
            'success' => true,
            'message' => __('Scan started', 'almaseo'),
            'run_id' => $scan_data['run_id'],
            'total_urls' => count($urls)
        );
    }
    
    /**
     * Process a batch of URLs
     */
    public static function process_batch($run_id) {
        ignore_user_abort(true);
        set_time_limit(60);
        
        $scan_data = get_option(self::OPTION_KEY, array());
        
        // Verify run ID
        if (empty($scan_data['run_id']) || $scan_data['run_id'] !== $run_id) {
            return;
        }
        
        // Already finished?
        if (!empty($scan_data['finished'])) {
            return;
        }
        
        // Get batch from queue
        $batch = array_splice($scan_data['queue'], 0, self::BATCH_SIZE);
        
        if (empty($batch)) {
            // Scan complete
            $scan_data['finished'] = time();
            update_option(self::OPTION_KEY, $scan_data, false);
            return;
        }
        
        // Load robots.txt rules once
        if (self::$robots_rules === null) {
            self::$robots_rules = self::parse_robots_txt();
        }
        
        // Process each URL
        foreach ($batch as $url) {
            $issues = self::check_url($url);
            $scan_data['totals']['checked']++;
            
            if (!empty($issues['issues'])) {
                $scan_data['totals']['issues']++;
                $scan_data['items'][] = $issues;
            }
        }
        
        // Update progress
        $scan_data['current_batch']++;
        update_option(self::OPTION_KEY, $scan_data, false);
        
        // Schedule next batch if more URLs
        if (!empty($scan_data['queue'])) {
            wp_schedule_single_event(time() + 2, 'almaseo_scan_batch', array($run_id));
        } else {
            // Mark as finished
            $scan_data['finished'] = time();
            update_option(self::OPTION_KEY, $scan_data, false);
        }
    }
    
    /**
     * Check a single URL for issues
     */
    private static function check_url($url) {
        $result = array(
            'url' => $url,
            'issues' => array(),
            'http' => null,
            'detail' => null
        );
        
        // 1. Check HTTP status
        $http_check = self::check_http_status($url);
        if ($http_check['issue']) {
            $result['issues'][] = $http_check['issue'];
            $result['http'] = $http_check['status'];
            $result['detail'] = $http_check['detail'];
        }
        
        // 2. Check robots.txt blocking
        if (self::is_blocked_by_robots($url)) {
            $result['issues'][] = 'robots_block';
        }
        
        // 3. Check for noindex (if it's a WP object)
        $noindex_check = self::check_noindex($url);
        if ($noindex_check) {
            $result['issues'][] = 'noindex';
            $result['detail'] = $noindex_check;
        }
        
        // 4. Check canonical mismatch
        $canonical_check = self::check_canonical($url);
        if ($canonical_check) {
            $result['issues'][] = 'canonical_mismatch';
            $result['detail'] = $canonical_check;
        }
        
        return $result;
    }
    
    /**
     * Check HTTP status
     */
    private static function check_http_status($url) {
        $result = array('issue' => null, 'status' => null, 'detail' => null);
        
        // Try HEAD request first
        $response = wp_remote_head($url, array(
            'timeout' => 3,
            'redirection' => 0,
            'sslverify' => false
        ));
        
        // If HEAD fails with 405, try GET
        if (is_wp_error($response)) {
            $result['issue'] = 'http_error';
            $result['detail'] = $response->get_error_message();
            return $result;
        }
        
        $status = wp_remote_retrieve_response_code($response);
        $result['status'] = $status;
        
        if ($status === 405) {
            // Method not allowed, try GET
            $response = wp_remote_get($url, array(
                'timeout' => 3,
                'redirection' => 0,
                'sslverify' => false,
                'method' => 'HEAD'
            ));
            
            if (!is_wp_error($response)) {
                $status = wp_remote_retrieve_response_code($response);
                $result['status'] = $status;
            }
        }
        
        // Flag problematic status codes
        if ($status >= 300 && $status < 400) {
            $result['issue'] = 'http_redirect';
            $result['detail'] = sprintf(__('Redirects (%d)', 'almaseo'), $status);
        } elseif ($status >= 400 && $status < 500) {
            $result['issue'] = 'http_' . $status;
            $result['detail'] = sprintf(__('Client error (%d)', 'almaseo'), $status);
        } elseif ($status >= 500) {
            $result['issue'] = 'http_5xx';
            $result['detail'] = sprintf(__('Server error (%d)', 'almaseo'), $status);
        }
        
        return $result;
    }
    
    /**
     * Parse robots.txt
     */
    private static function parse_robots_txt() {
        $robots_url = home_url('/robots.txt');
        $response = wp_remote_get($robots_url, array('timeout' => 3));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $body = wp_remote_retrieve_body($response);
        $lines = explode("\n", $body);
        $rules = array();
        $current_agent = null;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // User-agent directive
            if (preg_match('/^User-agent:\s*(.+)$/i', $line, $matches)) {
                $agent = trim($matches[1]);
                if ($agent === '*' || strcasecmp($agent, 'Googlebot') === 0) {
                    $current_agent = $agent;
                } else {
                    $current_agent = null;
                }
            }
            
            // Disallow directive
            if ($current_agent && preg_match('/^Disallow:\s*(.+)$/i', $line, $matches)) {
                $path = trim($matches[1]);
                if (!empty($path)) {
                    $rules[] = $path;
                }
            }
        }
        
        return $rules;
    }
    
    /**
     * Check if URL is blocked by robots.txt
     */
    private static function is_blocked_by_robots($url) {
        if (empty(self::$robots_rules)) {
            return false;
        }
        
        $path = parse_url($url, PHP_URL_PATH);
        if (empty($path)) {
            $path = '/';
        }
        
        foreach (self::$robots_rules as $rule) {
            // Simple path-prefix matching
            if (strpos($path, $rule) === 0) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check for noindex meta
     */
    private static function check_noindex($url) {
        // Try to get post ID from URL
        $post_id = url_to_postid($url);
        
        if ($post_id) {
            // Check our meta
            if (get_post_meta($post_id, '_almaseo_robots_noindex', true)) {
                return 'AlmaSEO noindex';
            }
            
            // Check Yoast
            $yoast = get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true);
            if (in_array($yoast, array('1', 1, 'noindex'), true)) {
                return 'Yoast noindex';
            }
            
            // Check Rank Math
            $rankmath = get_post_meta($post_id, 'rank_math_robots', true);
            if (is_array($rankmath) && in_array('noindex', $rankmath)) {
                return 'Rank Math noindex';
            }
            
            // Check AIOSEO
            if (get_post_meta($post_id, '_aioseo_robots_noindex', true)) {
                return 'AIOSEO noindex';
            }
        }
        
        return false;
    }
    
    /**
     * Check canonical URL
     */
    private static function check_canonical($url) {
        $post_id = url_to_postid($url);
        
        if ($post_id) {
            $canonical = wp_get_canonical_url($post_id);
            
            if ($canonical && $canonical !== $url) {
                // Normalize URLs for comparison
                $url_normalized = untrailingslashit(strtolower($url));
                $canonical_normalized = untrailingslashit(strtolower($canonical));
                
                if ($url_normalized !== $canonical_normalized) {
                    return sprintf(__('Canonical: %s', 'almaseo'), $canonical);
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get all URLs from sitemaps
     */
    private static function get_all_urls() {
        $urls = array();
        $manager = Alma_Sitemap_Manager::get_instance();
        $providers = $manager->get_providers();
        
        foreach ($providers as $provider) {
            $max_pages = $provider->get_max_pages();
            for ($page = 1; $page <= $max_pages; $page++) {
                $page_urls = $provider->get_urls($page);
                foreach ($page_urls as $url_data) {
                    $urls[] = $url_data['loc'];
                }
            }
        }
        
        return array_unique($urls);
    }
    
    /**
     * Get scan status
     */
    public static function get_status() {
        $scan_data = get_option(self::OPTION_KEY, array());
        
        if (empty($scan_data['started'])) {
            return array(
                'status' => 'idle',
                'message' => __('No scan running', 'almaseo')
            );
        }
        
        if (empty($scan_data['finished'])) {
            $progress = $scan_data['totals']['checked'] / max(1, $scan_data['total_urls']) * 100;
            return array(
                'status' => 'running',
                'progress' => round($progress, 1),
                'checked' => $scan_data['totals']['checked'],
                'total' => $scan_data['total_urls'],
                'issues' => $scan_data['totals']['issues']
            );
        }
        
        return array(
            'status' => 'complete',
            'checked' => $scan_data['totals']['checked'],
            'issues' => $scan_data['totals']['issues'],
            'duration' => $scan_data['finished'] - $scan_data['started']
        );
    }
    
    /**
     * Get scan results
     */
    public static function get_results($filter = 'all', $page = 1, $per_page = 50) {
        $scan_data = get_option(self::OPTION_KEY, array());
        
        if (empty($scan_data['items'])) {
            return array(
                'items' => array(),
                'total' => 0,
                'pages' => 0
            );
        }
        
        $items = $scan_data['items'];
        
        // Apply filter
        if ($filter !== 'all') {
            $items = array_filter($items, function($item) use ($filter) {
                return in_array($filter, $item['issues']);
            });
        }
        
        // Pagination
        $total = count($items);
        $offset = ($page - 1) * $per_page;
        $items = array_slice($items, $offset, $per_page);
        
        return array(
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $per_page)
        );
    }
    
    /**
     * Export results to CSV
     */
    public static function export_csv() {
        $scan_data = get_option(self::OPTION_KEY, array());
        
        if (empty($scan_data['items'])) {
            return false;
        }
        
        $csv = "URL,Issues,HTTP Status,Details\n";
        
        foreach ($scan_data['items'] as $item) {
            $csv .= sprintf(
                '"%s","%s","%s","%s"' . "\n",
                $item['url'],
                implode(', ', $item['issues']),
                $item['http'] ?: '',
                $item['detail'] ?: ''
            );
        }
        
        return $csv;
    }
    
    /**
     * Clear scan data
     */
    public static function clear() {
        delete_option(self::OPTION_KEY);
    }
}

// Register cron hook
add_action('almaseo_scan_batch', array('Alma_Sitemap_Conflicts', 'process_batch'));