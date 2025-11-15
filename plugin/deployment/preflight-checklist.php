<?php
/**
 * AlmaSEO v5.0.0 Preflight Checklist
 * Comprehensive deployment validation system
 */

namespace AlmaSEO\Deployment;

class PreflightChecker {
    private $checks = [];
    private $results = [];
    private $critical_issues = [];
    private $warnings = [];
    
    public function __construct() {
        $this->register_checks();
    }
    
    private function register_checks() {
        $this->checks = [
            'server_requirements' => [
                'label' => 'Server Requirements',
                'checks' => [
                    'php_version' => ['PHP Version ≥ 7.4', [$this, 'check_php_version']],
                    'wp_version' => ['WordPress ≥ 6.3', [$this, 'check_wp_version']],
                    'memory_limit' => ['Memory Limit ≥ 256M', [$this, 'check_memory_limit']],
                    'wp_cli' => ['WP-CLI Available', [$this, 'check_wp_cli']],
                    'xml_support' => ['XML Extension', [$this, 'check_xml_support']],
                    'gzip_support' => ['Gzip Support', [$this, 'check_gzip_support']]
                ]
            ],
            'folder_permissions' => [
                'label' => 'Folder Permissions',
                'checks' => [
                    'uploads_dir' => ['Uploads Directory', [$this, 'check_uploads_dir']],
                    'sitemap_dir' => ['Sitemap Directory', [$this, 'check_sitemap_dir']],
                    'cache_dir' => ['Cache Directory', [$this, 'check_cache_dir']],
                    'logs_dir' => ['Logs Directory', [$this, 'check_logs_dir']]
                ]
            ],
            'cache_configuration' => [
                'label' => 'Cache Configuration',
                'checks' => [
                    'wp_rocket' => ['WP Rocket Exclusions', [$this, 'check_wp_rocket']],
                    'litespeed' => ['LiteSpeed Cache', [$this, 'check_litespeed']],
                    'cloudflare' => ['Cloudflare Rules', [$this, 'check_cloudflare']],
                    'w3tc' => ['W3 Total Cache', [$this, 'check_w3tc']]
                ]
            ],
            'cron_system' => [
                'label' => 'Cron System',
                'checks' => [
                    'wp_cron' => ['WP-Cron Status', [$this, 'check_wp_cron']],
                    'real_cron' => ['System Cron', [$this, 'check_real_cron']],
                    'scheduled_tasks' => ['Scheduled Tasks', [$this, 'check_scheduled_tasks']]
                ]
            ],
            'plugin_conflicts' => [
                'label' => 'Plugin Conflicts',
                'checks' => [
                    'yoast' => ['Yoast SEO', [$this, 'check_yoast']],
                    'rankmath' => ['Rank Math', [$this, 'check_rankmath']],
                    'aioseo' => ['All in One SEO', [$this, 'check_aioseo']],
                    'other_sitemaps' => ['Other Sitemap Plugins', [$this, 'check_other_sitemaps']]
                ]
            ],
            'installation' => [
                'label' => 'Installation Status',
                'checks' => [
                    'plugin_active' => ['Plugin Activated', [$this, 'check_plugin_active']],
                    'database_tables' => ['Database Tables', [$this, 'check_database_tables']],
                    'permalinks' => ['Permalinks Flushed', [$this, 'check_permalinks']],
                    'rewrite_rules' => ['Rewrite Rules', [$this, 'check_rewrite_rules']]
                ]
            ],
            'configuration' => [
                'label' => 'Configuration',
                'checks' => [
                    'takeover_mode' => ['Takeover Mode OFF', [$this, 'check_takeover_mode']],
                    'storage_mode' => ['Static Storage ON', [$this, 'check_storage_mode']],
                    'gzip_enabled' => ['Gzip Compression ON', [$this, 'check_gzip_enabled']],
                    'indexnow' => ['IndexNow Configuration', [$this, 'check_indexnow']]
                ]
            ],
            'sitemap_validation' => [
                'label' => 'Sitemap Validation',
                'checks' => [
                    'static_build' => ['Static Build Complete', [$this, 'check_static_build']],
                    'xml_valid' => ['XML Validation', [$this, 'check_xml_valid']],
                    'urls_accessible' => ['URLs Accessible', [$this, 'check_urls_accessible']],
                    'compression' => ['Gzip Files Created', [$this, 'check_compression']]
                ]
            ],
            'robots_txt' => [
                'label' => 'Robots.txt',
                'checks' => [
                    'sitemap_entry' => ['Sitemap Entry Present', [$this, 'check_robots_sitemap']],
                    'no_duplicates' => ['No Duplicate Entries', [$this, 'check_robots_duplicates']],
                    'correct_url' => ['Correct Sitemap URL', [$this, 'check_robots_url']]
                ]
            ],
            'auto_updates' => [
                'label' => 'Auto-Updates',
                'checks' => [
                    'endpoint' => ['Update Endpoint', [$this, 'check_update_endpoint']],
                    'channel' => ['Beta Channel', [$this, 'check_update_channel']],
                    'connectivity' => ['Server Connectivity', [$this, 'check_update_connectivity']]
                ]
            ]
        ];
    }
    
    // Server Requirements Checks
    private function check_php_version() {
        $required = '7.4.0';
        $current = PHP_VERSION;
        $passed = version_compare($current, $required, '>=');
        
        return [
            'status' => $passed ? 'pass' : 'fail',
            'message' => "PHP $current" . ($passed ? ' ✓' : " (requires ≥ $required)"),
            'critical' => true
        ];
    }
    
    private function check_wp_version() {
        global $wp_version;
        $required = '6.3';
        $passed = version_compare($wp_version, $required, '>=');
        
        return [
            'status' => $passed ? 'pass' : 'fail',
            'message' => "WordPress $wp_version" . ($passed ? ' ✓' : " (requires ≥ $required)"),
            'critical' => true
        ];
    }
    
    private function check_memory_limit() {
        $memory = ini_get('memory_limit');
        $bytes = $this->convert_to_bytes($memory);
        $required = 256 * 1024 * 1024; // 256M
        $passed = $bytes >= $required;
        
        return [
            'status' => $passed ? 'pass' : 'warning',
            'message' => "Memory: $memory" . ($passed ? ' ✓' : ' (recommend ≥ 256M)'),
            'critical' => false
        ];
    }
    
    private function check_wp_cli() {
        $has_cli = defined('WP_CLI') && WP_CLI;
        $cli_available = $this->is_wp_cli_available();
        
        return [
            'status' => $cli_available ? 'pass' : 'warning',
            'message' => $cli_available ? 'WP-CLI available ✓' : 'WP-CLI not detected (optional)',
            'critical' => false
        ];
    }
    
    private function check_xml_support() {
        $has_xml = extension_loaded('xml') && extension_loaded('xmlwriter');
        
        return [
            'status' => $has_xml ? 'pass' : 'fail',
            'message' => $has_xml ? 'XML extensions loaded ✓' : 'XML extensions missing',
            'critical' => true
        ];
    }
    
    private function check_gzip_support() {
        $has_gzip = function_exists('gzopen') && function_exists('gzwrite');
        
        return [
            'status' => $has_gzip ? 'pass' : 'warning',
            'message' => $has_gzip ? 'Gzip functions available ✓' : 'Gzip not available',
            'critical' => false
        ];
    }
    
    // Folder Permission Checks
    private function check_uploads_dir() {
        $upload_dir = wp_upload_dir();
        $base_dir = $upload_dir['basedir'];
        $writable = is_writable($base_dir);
        
        return [
            'status' => $writable ? 'pass' : 'fail',
            'message' => $writable ? 'Uploads directory writable ✓' : 'Uploads not writable',
            'critical' => true
        ];
    }
    
    private function check_sitemap_dir() {
        $upload_dir = wp_upload_dir();
        $sitemap_dir = $upload_dir['basedir'] . '/almaseo/sitemaps';
        
        if (!file_exists($sitemap_dir)) {
            wp_mkdir_p($sitemap_dir);
        }
        
        $writable = is_writable($sitemap_dir);
        
        return [
            'status' => $writable ? 'pass' : 'fail',
            'message' => $writable ? 'Sitemap directory ready ✓' : 'Cannot write to sitemap directory',
            'critical' => true
        ];
    }
    
    private function check_cache_dir() {
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/almaseo/cache';
        
        if (!file_exists($cache_dir)) {
            wp_mkdir_p($cache_dir);
        }
        
        $writable = is_writable($cache_dir);
        
        return [
            'status' => $writable ? 'pass' : 'warning',
            'message' => $writable ? 'Cache directory ready ✓' : 'Cache directory not writable',
            'critical' => false
        ];
    }
    
    private function check_logs_dir() {
        $upload_dir = wp_upload_dir();
        $logs_dir = $upload_dir['basedir'] . '/almaseo/logs';
        
        if (!file_exists($logs_dir)) {
            wp_mkdir_p($logs_dir);
        }
        
        $writable = is_writable($logs_dir);
        
        return [
            'status' => $writable ? 'pass' : 'warning',
            'message' => $writable ? 'Logs directory ready ✓' : 'Logs directory not writable',
            'critical' => false
        ];
    }
    
    // Cache Configuration Checks
    private function check_wp_rocket() {
        if (!defined('WP_ROCKET_VERSION')) {
            return [
                'status' => 'skip',
                'message' => 'WP Rocket not installed',
                'critical' => false
            ];
        }
        
        $exclusions = get_option('wp_rocket_settings', []);
        $has_exclusion = $this->check_cache_exclusion($exclusions, 'sitemap');
        
        return [
            'status' => $has_exclusion ? 'pass' : 'warning',
            'message' => $has_exclusion ? 'WP Rocket configured ✓' : 'Add sitemap exclusion to WP Rocket',
            'critical' => false
        ];
    }
    
    private function check_litespeed() {
        if (!defined('LSCWP_V')) {
            return [
                'status' => 'skip',
                'message' => 'LiteSpeed Cache not installed',
                'critical' => false
            ];
        }
        
        return [
            'status' => 'warning',
            'message' => 'LiteSpeed detected - verify sitemap exclusions',
            'critical' => false
        ];
    }
    
    private function check_cloudflare() {
        $has_cf = defined('CLOUDFLARE_PLUGIN_DIR') || 
                  class_exists('\\CF\\WordPress\\Plugin');
        
        if (!$has_cf) {
            return [
                'status' => 'skip',
                'message' => 'Cloudflare not detected',
                'critical' => false
            ];
        }
        
        return [
            'status' => 'warning',
            'message' => 'Cloudflare detected - verify page rules for sitemaps',
            'critical' => false
        ];
    }
    
    private function check_w3tc() {
        if (!defined('W3TC')) {
            return [
                'status' => 'skip',
                'message' => 'W3 Total Cache not installed',
                'critical' => false
            ];
        }
        
        return [
            'status' => 'warning',
            'message' => 'W3TC detected - verify sitemap exclusions',
            'critical' => false
        ];
    }
    
    // Cron System Checks
    private function check_wp_cron() {
        $disabled = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
        
        if ($disabled) {
            return [
                'status' => 'warning',
                'message' => 'WP-Cron disabled (ensure system cron configured)',
                'critical' => false
            ];
        }
        
        return [
            'status' => 'pass',
            'message' => 'WP-Cron enabled ✓',
            'critical' => false
        ];
    }
    
    private function check_real_cron() {
        $has_system_cron = $this->detect_system_cron();
        
        return [
            'status' => $has_system_cron ? 'pass' : 'info',
            'message' => $has_system_cron ? 'System cron detected ✓' : 'Using WP-Cron',
            'critical' => false
        ];
    }
    
    private function check_scheduled_tasks() {
        $tasks = [
            'almaseo_sitemap_build',
            'almaseo_sitemap_delta',
            'almaseo_news_sitemap',
            'almaseo_check_updates'
        ];
        
        $scheduled = 0;
        foreach ($tasks as $task) {
            if (wp_next_scheduled($task)) {
                $scheduled++;
            }
        }
        
        return [
            'status' => $scheduled > 0 ? 'pass' : 'warning',
            'message' => "$scheduled/" . count($tasks) . " tasks scheduled",
            'critical' => false
        ];
    }
    
    // Plugin Conflict Checks
    private function check_yoast() {
        $active = defined('WPSEO_VERSION');
        
        if (!$active) {
            return [
                'status' => 'skip',
                'message' => 'Yoast SEO not active',
                'critical' => false
            ];
        }
        
        $takeover = get_option('almaseo_sitemap_takeover', false);
        
        return [
            'status' => !$takeover ? 'pass' : 'warning',
            'message' => 'Yoast detected' . (!$takeover ? ' (takeover OFF ✓)' : ' (takeover ON!)'),
            'critical' => false
        ];
    }
    
    private function check_rankmath() {
        $active = defined('RANK_MATH_VERSION');
        
        if (!$active) {
            return [
                'status' => 'skip',
                'message' => 'Rank Math not active',
                'critical' => false
            ];
        }
        
        $takeover = get_option('almaseo_sitemap_takeover', false);
        
        return [
            'status' => !$takeover ? 'pass' : 'warning',
            'message' => 'Rank Math detected' . (!$takeover ? ' (takeover OFF ✓)' : ' (takeover ON!)'),
            'critical' => false
        ];
    }
    
    private function check_aioseo() {
        $active = defined('AIOSEO_VERSION');
        
        if (!$active) {
            return [
                'status' => 'skip',
                'message' => 'AIOSEO not active',
                'critical' => false
            ];
        }
        
        $takeover = get_option('almaseo_sitemap_takeover', false);
        
        return [
            'status' => !$takeover ? 'pass' : 'warning',
            'message' => 'AIOSEO detected' . (!$takeover ? ' (takeover OFF ✓)' : ' (takeover ON!)'),
            'critical' => false
        ];
    }
    
    private function check_other_sitemaps() {
        $sitemap_plugins = [
            'google-sitemap-generator/sitemap.php',
            'xml-sitemap-feed/xml-sitemap.php',
            'bwp-google-xml-sitemaps/bwp-simple-gxs.php'
        ];
        
        $conflicts = [];
        foreach ($sitemap_plugins as $plugin) {
            if (is_plugin_active($plugin)) {
                $conflicts[] = basename(dirname($plugin));
            }
        }
        
        if (empty($conflicts)) {
            return [
                'status' => 'pass',
                'message' => 'No conflicting sitemap plugins ✓',
                'critical' => false
            ];
        }
        
        return [
            'status' => 'warning',
            'message' => 'Conflicts: ' . implode(', ', $conflicts),
            'critical' => false
        ];
    }
    
    // Installation Checks
    private function check_plugin_active() {
        $active = is_plugin_active('almaseo-seo-playground/alma-seoconnector.php');
        
        return [
            'status' => $active ? 'pass' : 'fail',
            'message' => $active ? 'Plugin activated ✓' : 'Plugin not active',
            'critical' => true
        ];
    }
    
    private function check_database_tables() {
        global $wpdb;
        
        $tables = [
            $wpdb->prefix . 'almaseo_sitemap_urls',
            $wpdb->prefix . 'almaseo_sitemap_builds',
            $wpdb->prefix . 'almaseo_indexnow_log'
        ];
        
        $missing = [];
        foreach ($tables as $table) {
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
                $missing[] = str_replace($wpdb->prefix, '', $table);
            }
        }
        
        if (empty($missing)) {
            return [
                'status' => 'pass',
                'message' => 'All database tables present ✓',
                'critical' => false
            ];
        }
        
        return [
            'status' => 'fail',
            'message' => 'Missing tables: ' . implode(', ', $missing),
            'critical' => true
        ];
    }
    
    private function check_permalinks() {
        $structure = get_option('permalink_structure');
        
        if (empty($structure)) {
            return [
                'status' => 'fail',
                'message' => 'Pretty permalinks not enabled',
                'critical' => true
            ];
        }
        
        return [
            'status' => 'pass',
            'message' => 'Permalinks configured ✓',
            'critical' => false
        ];
    }
    
    private function check_rewrite_rules() {
        global $wp_rewrite;
        $rules = $wp_rewrite->rules;
        
        $has_sitemap_rules = false;
        foreach ($rules as $pattern => $rewrite) {
            if (strpos($pattern, 'sitemap') !== false) {
                $has_sitemap_rules = true;
                break;
            }
        }
        
        return [
            'status' => $has_sitemap_rules ? 'pass' : 'warning',
            'message' => $has_sitemap_rules ? 'Rewrite rules present ✓' : 'Flush permalinks needed',
            'critical' => false
        ];
    }
    
    // Configuration Checks
    private function check_takeover_mode() {
        $takeover = get_option('almaseo_sitemap_takeover', false);
        
        return [
            'status' => !$takeover ? 'pass' : 'warning',
            'message' => !$takeover ? 'Takeover mode OFF ✓' : 'Takeover mode ON (beta default: OFF)',
            'critical' => false
        ];
    }
    
    private function check_storage_mode() {
        $mode = get_option('almaseo_sitemap_storage', 'dynamic');
        
        return [
            'status' => $mode === 'static' ? 'pass' : 'warning',
            'message' => $mode === 'static' ? 'Static storage enabled ✓' : "Storage mode: $mode",
            'critical' => false
        ];
    }
    
    private function check_gzip_enabled() {
        $gzip = get_option('almaseo_sitemap_gzip', true);
        
        return [
            'status' => $gzip ? 'pass' : 'info',
            'message' => $gzip ? 'Gzip compression enabled ✓' : 'Gzip compression disabled',
            'critical' => false
        ];
    }
    
    private function check_indexnow() {
        $enabled = get_option('almaseo_indexnow_enabled', false);
        $key = get_option('almaseo_indexnow_key', '');
        
        if (!$enabled) {
            return [
                'status' => 'info',
                'message' => 'IndexNow disabled (configure when ready)',
                'critical' => false
            ];
        }
        
        if (empty($key)) {
            return [
                'status' => 'warning',
                'message' => 'IndexNow enabled but no key set',
                'critical' => false
            ];
        }
        
        return [
            'status' => 'pass',
            'message' => 'IndexNow configured ✓',
            'critical' => false
        ];
    }
    
    // Sitemap Validation Checks
    private function check_static_build() {
        $upload_dir = wp_upload_dir();
        $sitemap_dir = $upload_dir['basedir'] . '/almaseo/sitemaps';
        $index_file = $sitemap_dir . '/sitemap-index.xml';
        
        if (!file_exists($index_file)) {
            return [
                'status' => 'warning',
                'message' => 'No static build found (run build)',
                'critical' => false
            ];
        }
        
        $age = time() - filemtime($index_file);
        $age_hours = round($age / 3600, 1);
        
        return [
            'status' => $age < 86400 ? 'pass' : 'warning',
            'message' => "Static build age: {$age_hours}h",
            'critical' => false
        ];
    }
    
    private function check_xml_valid() {
        $sitemap_url = home_url('/almaseo-sitemap.xml');
        $response = wp_remote_get($sitemap_url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return [
                'status' => 'fail',
                'message' => 'Cannot fetch sitemap: ' . $response->get_error_message(),
                'critical' => true
            ];
        }
        
        $body = wp_remote_retrieve_body($response);
        $valid = $this->validate_xml($body);
        
        return [
            'status' => $valid ? 'pass' : 'fail',
            'message' => $valid ? 'XML validation passed ✓' : 'Invalid XML structure',
            'critical' => true
        ];
    }
    
    private function check_urls_accessible() {
        $sitemap_url = home_url('/almaseo-sitemap.xml');
        $response = wp_remote_head($sitemap_url, ['timeout' => 10]);
        
        if (is_wp_error($response)) {
            return [
                'status' => 'fail',
                'message' => 'Sitemap not accessible',
                'critical' => true
            ];
        }
        
        $code = wp_remote_retrieve_response_code($response);
        
        return [
            'status' => $code === 200 ? 'pass' : 'fail',
            'message' => "HTTP $code" . ($code === 200 ? ' ✓' : ' (expected 200)'),
            'critical' => true
        ];
    }
    
    private function check_compression() {
        $upload_dir = wp_upload_dir();
        $sitemap_dir = $upload_dir['basedir'] . '/almaseo/sitemaps';
        
        $gz_files = glob($sitemap_dir . '/*.xml.gz');
        $count = count($gz_files);
        
        return [
            'status' => $count > 0 ? 'pass' : 'info',
            'message' => "$count gzip files found",
            'critical' => false
        ];
    }
    
    // Robots.txt Checks
    private function check_robots_sitemap() {
        $robots = $this->get_robots_content();
        $has_sitemap = strpos($robots, 'Sitemap:') !== false;
        
        return [
            'status' => $has_sitemap ? 'pass' : 'warning',
            'message' => $has_sitemap ? 'Sitemap directive present ✓' : 'No sitemap in robots.txt',
            'critical' => false
        ];
    }
    
    private function check_robots_duplicates() {
        $robots = $this->get_robots_content();
        $lines = explode("\n", $robots);
        $sitemap_lines = array_filter($lines, function($line) {
            return stripos($line, 'Sitemap:') === 0;
        });
        
        $count = count($sitemap_lines);
        
        return [
            'status' => $count <= 1 ? 'pass' : 'warning',
            'message' => $count <= 1 ? 'No duplicates ✓' : "$count sitemap entries found",
            'critical' => false
        ];
    }
    
    private function check_robots_url() {
        $robots = $this->get_robots_content();
        $expected = home_url('/almaseo-sitemap.xml');
        
        if (strpos($robots, $expected) !== false) {
            return [
                'status' => 'pass',
                'message' => 'Correct URL ✓',
                'critical' => false
            ];
        }
        
        return [
            'status' => 'warning',
            'message' => 'Sitemap URL mismatch',
            'critical' => false
        ];
    }
    
    // Auto-Update Checks
    private function check_update_endpoint() {
        $endpoint = get_option('almaseo_update_endpoint', '');
        
        if (empty($endpoint)) {
            return [
                'status' => 'info',
                'message' => 'No update endpoint configured',
                'critical' => false
            ];
        }
        
        return [
            'status' => 'pass',
            'message' => 'Update endpoint: ' . parse_url($endpoint, PHP_URL_HOST),
            'critical' => false
        ];
    }
    
    private function check_update_channel() {
        $channel = get_option('almaseo_update_channel', 'stable');
        
        return [
            'status' => $channel === 'beta' ? 'pass' : 'info',
            'message' => "Update channel: $channel",
            'critical' => false
        ];
    }
    
    private function check_update_connectivity() {
        $endpoint = get_option('almaseo_update_endpoint', '');
        
        if (empty($endpoint)) {
            return [
                'status' => 'skip',
                'message' => 'No endpoint to test',
                'critical' => false
            ];
        }
        
        $response = wp_remote_head($endpoint, ['timeout' => 5]);
        
        if (is_wp_error($response)) {
            return [
                'status' => 'warning',
                'message' => 'Cannot reach update server',
                'critical' => false
            ];
        }
        
        return [
            'status' => 'pass',
            'message' => 'Update server reachable ✓',
            'critical' => false
        ];
    }
    
    // Helper Methods
    private function convert_to_bytes($val) {
        $val = trim($val);
        $last = strtolower($val[strlen($val)-1]);
        $num = (int)$val;
        
        switch($last) {
            case 'g': $num *= 1024;
            case 'm': $num *= 1024;
            case 'k': $num *= 1024;
        }
        
        return $num;
    }
    
    private function is_wp_cli_available() {
        if (defined('WP_CLI') && WP_CLI) {
            return true;
        }
        
        exec('which wp 2>/dev/null', $output, $return);
        return $return === 0;
    }
    
    private function detect_system_cron() {
        if (!function_exists('exec')) {
            return false;
        }
        
        exec('crontab -l 2>/dev/null | grep wp-cron.php', $output, $return);
        return !empty($output);
    }
    
    private function check_cache_exclusion($settings, $pattern) {
        if (!is_array($settings)) {
            return false;
        }
        
        $json = json_encode($settings);
        return stripos($json, $pattern) !== false;
    }
    
    private function validate_xml($xml_string) {
        if (empty($xml_string)) {
            return false;
        }
        
        libxml_use_internal_errors(true);
        $doc = simplexml_load_string($xml_string);
        
        if ($doc === false) {
            return false;
        }
        
        return true;
    }
    
    private function get_robots_content() {
        $robots_url = home_url('/robots.txt');
        $response = wp_remote_get($robots_url, ['timeout' => 5]);
        
        if (is_wp_error($response)) {
            return '';
        }
        
        return wp_remote_retrieve_body($response);
    }
    
    // Main Execution
    public function run_all_checks() {
        $this->results = [];
        $this->critical_issues = [];
        $this->warnings = [];
        
        foreach ($this->checks as $category => $data) {
            $this->results[$category] = [
                'label' => $data['label'],
                'checks' => []
            ];
            
            foreach ($data['checks'] as $check_id => $check_data) {
                list($label, $callback) = $check_data;
                
                try {
                    $result = call_user_func($callback);
                    $result['label'] = $label;
                    
                    $this->results[$category]['checks'][$check_id] = $result;
                    
                    if ($result['status'] === 'fail' && !empty($result['critical'])) {
                        $this->critical_issues[] = $label . ': ' . $result['message'];
                    } elseif ($result['status'] === 'warning') {
                        $this->warnings[] = $label . ': ' . $result['message'];
                    }
                    
                } catch (\Exception $e) {
                    $this->results[$category]['checks'][$check_id] = [
                        'label' => $label,
                        'status' => 'error',
                        'message' => 'Check failed: ' . $e->getMessage(),
                        'critical' => false
                    ];
                }
            }
        }
        
        return [
            'results' => $this->results,
            'critical_issues' => $this->critical_issues,
            'warnings' => $this->warnings,
            'passed' => empty($this->critical_issues)
        ];
    }
    
    public function get_summary() {
        $total = 0;
        $passed = 0;
        $failed = 0;
        $warnings = 0;
        $skipped = 0;
        
        foreach ($this->results as $category) {
            foreach ($category['checks'] as $check) {
                $total++;
                switch($check['status']) {
                    case 'pass':
                        $passed++;
                        break;
                    case 'fail':
                        $failed++;
                        break;
                    case 'warning':
                        $warnings++;
                        break;
                    case 'skip':
                    case 'info':
                        $skipped++;
                        break;
                }
            }
        }
        
        return [
            'total' => $total,
            'passed' => $passed,
            'failed' => $failed,
            'warnings' => $warnings,
            'skipped' => $skipped,
            'success_rate' => $total > 0 ? round(($passed / $total) * 100, 1) : 0
        ];
    }
    
    public function format_report($format = 'text') {
        if ($format === 'json') {
            return json_encode($this->results, JSON_PRETTY_PRINT);
        }
        
        $output = [];
        $output[] = str_repeat('=', 70);
        $output[] = 'ALMASEO v5.0.0 PREFLIGHT CHECK REPORT';
        $output[] = 'Generated: ' . date('Y-m-d H:i:s');
        $output[] = str_repeat('=', 70);
        $output[] = '';
        
        foreach ($this->results as $category => $data) {
            $output[] = '## ' . $data['label'];
            $output[] = str_repeat('-', 50);
            
            foreach ($data['checks'] as $check) {
                $icon = $this->get_status_icon($check['status']);
                $output[] = sprintf('  %s %s: %s', 
                    $icon, 
                    $check['label'], 
                    $check['message']
                );
            }
            $output[] = '';
        }
        
        if (!empty($this->critical_issues)) {
            $output[] = '## CRITICAL ISSUES (Must Fix)';
            $output[] = str_repeat('-', 50);
            foreach ($this->critical_issues as $issue) {
                $output[] = '  ✗ ' . $issue;
            }
            $output[] = '';
        }
        
        if (!empty($this->warnings)) {
            $output[] = '## WARNINGS (Review)';
            $output[] = str_repeat('-', 50);
            foreach ($this->warnings as $warning) {
                $output[] = '  ⚠ ' . $warning;
            }
            $output[] = '';
        }
        
        $summary = $this->get_summary();
        $output[] = '## SUMMARY';
        $output[] = str_repeat('-', 50);
        $output[] = sprintf('  Total Checks: %d', $summary['total']);
        $output[] = sprintf('  Passed: %d (%.1f%%)', $summary['passed'], $summary['success_rate']);
        $output[] = sprintf('  Failed: %d', $summary['failed']);
        $output[] = sprintf('  Warnings: %d', $summary['warnings']);
        $output[] = sprintf('  Skipped: %d', $summary['skipped']);
        $output[] = '';
        
        $output[] = empty($this->critical_issues) 
            ? '✓ PREFLIGHT CHECK PASSED - Ready for deployment'
            : '✗ PREFLIGHT CHECK FAILED - Address critical issues before deploying';
        
        $output[] = str_repeat('=', 70);
        
        return implode("\n", $output);
    }
    
    private function get_status_icon($status) {
        switch($status) {
            case 'pass':
                return '✓';
            case 'fail':
                return '✗';
            case 'warning':
                return '⚠';
            case 'skip':
                return '○';
            case 'info':
                return 'ℹ';
            case 'error':
                return '✖';
            default:
                return '•';
        }
    }
}

// CLI Command if WP-CLI available
if (defined('WP_CLI') && WP_CLI) {
    class AlmaSEO_Preflight_Command {
        /**
         * Run preflight checks
         * 
         * ## OPTIONS
         * 
         * [--format=<format>]
         * : Output format (text, json)
         * default: text
         * 
         * [--category=<category>]
         * : Run specific category only
         * 
         * [--fix]
         * : Attempt to auto-fix issues
         * 
         * ## EXAMPLES
         * 
         *     wp almaseo preflight
         *     wp almaseo preflight --format=json
         *     wp almaseo preflight --category=server_requirements
         *     wp almaseo preflight --fix
         */
        public function __invoke($args, $assoc_args) {
            $checker = new PreflightChecker();
            
            $format = \WP_CLI\Utils\get_flag_value($assoc_args, 'format', 'text');
            $category = \WP_CLI\Utils\get_flag_value($assoc_args, 'category', null);
            $fix = \WP_CLI\Utils\get_flag_value($assoc_args, 'fix', false);
            
            if ($fix) {
                WP_CLI::log('Attempting auto-fixes...');
                $this->auto_fix();
            }
            
            WP_CLI::log('Running preflight checks...');
            $results = $checker->run_all_checks();
            
            if ($format === 'json') {
                WP_CLI::log(json_encode($results, JSON_PRETTY_PRINT));
            } else {
                WP_CLI::log($checker->format_report('text'));
            }
            
            if (!empty($results['critical_issues'])) {
                WP_CLI::error('Preflight check failed with critical issues', false);
            } elseif (!empty($results['warnings'])) {
                WP_CLI::warning('Preflight check passed with warnings');
            } else {
                WP_CLI::success('Preflight check passed!');
            }
        }
        
        private function auto_fix() {
            // Auto-fix what we can
            
            // 1. Create directories
            $upload_dir = wp_upload_dir();
            $dirs = [
                $upload_dir['basedir'] . '/almaseo',
                $upload_dir['basedir'] . '/almaseo/sitemaps',
                $upload_dir['basedir'] . '/almaseo/cache',
                $upload_dir['basedir'] . '/almaseo/logs'
            ];
            
            foreach ($dirs as $dir) {
                if (!file_exists($dir)) {
                    wp_mkdir_p($dir);
                    WP_CLI::log("Created directory: $dir");
                }
            }
            
            // 2. Flush rewrite rules
            flush_rewrite_rules();
            WP_CLI::log('Flushed rewrite rules');
            
            // 3. Set safe defaults
            update_option('almaseo_sitemap_takeover', false);
            update_option('almaseo_sitemap_storage', 'static');
            update_option('almaseo_sitemap_gzip', true);
            update_option('almaseo_indexnow_enabled', false);
            WP_CLI::log('Applied safe default settings');
            
            // 4. Schedule cron tasks
            if (!wp_next_scheduled('almaseo_sitemap_build')) {
                wp_schedule_event(time(), 'twicedaily', 'almaseo_sitemap_build');
                WP_CLI::log('Scheduled sitemap build task');
            }
        }
    }
    
    WP_CLI::add_command('almaseo preflight', 'AlmaSEO_Preflight_Command');
}