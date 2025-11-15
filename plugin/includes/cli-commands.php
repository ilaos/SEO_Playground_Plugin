<?php
/**
 * AlmaSEO WP-CLI Commands
 * Comprehensive CLI interface for v5.0.0
 */

if (!defined('WP_CLI') || !WP_CLI) {
    return;
}

// Include deployment tools
require_once plugin_dir_path(__FILE__) . '../deployment/preflight-checklist.php';
require_once plugin_dir_path(__FILE__) . '../deployment/automated-tests.php';

/**
 * Main AlmaSEO CLI Command
 */
class AlmaSEO_CLI_Command {
    
    /**
     * Display AlmaSEO status and configuration
     * 
     * ## EXAMPLES
     * 
     *     wp almaseo status
     */
    public function status() {
        WP_CLI::log('AlmaSEO Status');
        WP_CLI::log(str_repeat('=', 50));
        
        // Version info
        $version = get_plugin_data(ALMASEO_PLUGIN_FILE)['Version'];
        WP_CLI::log("Version: $version");
        
        // Configuration
        $config = [
            'Sitemap Storage' => get_option('almaseo_sitemap_storage', 'dynamic'),
            'Takeover Mode' => get_option('almaseo_sitemap_takeover', false) ? 'ON' : 'OFF',
            'Gzip Enabled' => get_option('almaseo_sitemap_gzip', true) ? 'YES' : 'NO',
            'IndexNow' => get_option('almaseo_indexnow_enabled', false) ? 'ENABLED' : 'DISABLED',
            'Update Channel' => get_option('almaseo_update_channel', 'stable')
        ];
        
        WP_CLI::log("\nConfiguration:");
        foreach ($config as $key => $value) {
            WP_CLI::log("  $key: $value");
        }
        
        // Sitemap stats
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_sitemap_urls';
        $total_urls = $wpdb->get_var("SELECT COUNT(*) FROM $table");
        
        WP_CLI::log("\nSitemap Statistics:");
        WP_CLI::log("  Total URLs: $total_urls");
        
        // Last build
        $last_build = get_option('almaseo_last_sitemap_build');
        if ($last_build) {
            $age = human_time_diff($last_build, current_time('timestamp'));
            WP_CLI::log("  Last Build: $age ago");
        }
        
        // Check for conflicts
        $conflicts = [];
        if (defined('WPSEO_VERSION')) $conflicts[] = 'Yoast SEO';
        if (defined('RANK_MATH_VERSION')) $conflicts[] = 'Rank Math';
        if (defined('AIOSEO_VERSION')) $conflicts[] = 'AIOSEO';
        
        if (!empty($conflicts)) {
            WP_CLI::log("\nDetected Conflicts:");
            WP_CLI::log("  " . implode(', ', $conflicts));
        }
        
        WP_CLI::success('Status check complete');
    }
    
    /**
     * View recent log entries
     * 
     * ## OPTIONS
     * 
     * [--lines=<lines>]
     * : Number of lines to display
     * default: 20
     * 
     * [--type=<type>]
     * : Log type (error, info, debug)
     * 
     * ## EXAMPLES
     * 
     *     wp almaseo logs
     *     wp almaseo logs --lines=50
     *     wp almaseo logs --type=error
     */
    public function logs($args, $assoc_args) {
        $lines = isset($assoc_args['lines']) ? intval($assoc_args['lines']) : 20;
        $type = isset($assoc_args['type']) ? $assoc_args['type'] : null;
        
        $upload_dir = wp_upload_dir();
        $log_file = $upload_dir['basedir'] . '/almaseo/logs/almaseo.log';
        
        if (!file_exists($log_file)) {
            WP_CLI::warning('No log file found');
            return;
        }
        
        $logs = file($log_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $logs = array_slice($logs, -$lines);
        
        if ($type) {
            $logs = array_filter($logs, function($line) use ($type) {
                return stripos($line, "[$type]") !== false;
            });
        }
        
        if (empty($logs)) {
            WP_CLI::log('No matching log entries');
            return;
        }
        
        foreach ($logs as $line) {
            WP_CLI::log($line);
        }
        
        WP_CLI::success(count($logs) . ' log entries displayed');
    }
}

/**
 * Sitemap Management Commands
 */
class AlmaSEO_Sitemaps_Command {
    
    /**
     * Build sitemap
     * 
     * ## OPTIONS
     * 
     * [--mode=<mode>]
     * : Build mode (static, dynamic)
     * default: static
     * 
     * [--force]
     * : Force rebuild even if recent
     * 
     * ## EXAMPLES
     * 
     *     wp almaseo sitemaps build
     *     wp almaseo sitemaps build --mode=static --force
     */
    public function build($args, $assoc_args) {
        $mode = isset($assoc_args['mode']) ? $assoc_args['mode'] : 'static';
        $force = isset($assoc_args['force']);
        
        if (!$force) {
            $last_build = get_option('almaseo_last_sitemap_build');
            if ($last_build && (time() - $last_build) < 3600) {
                WP_CLI::confirm('Sitemap was built recently. Continue?');
            }
        }
        
        WP_CLI::log("Building $mode sitemap...");
        
        $generator = new \AlmaSEO\Sitemap\Generator();
        
        if ($mode === 'static') {
            $result = $generator->build_static();
        } else {
            $result = $generator->generate_index();
        }
        
        if ($result) {
            update_option('almaseo_last_sitemap_build', time());
            WP_CLI::success('Sitemap built successfully');
            
            // Show statistics
            global $wpdb;
            $table = $wpdb->prefix . 'almaseo_sitemap_urls';
            $stats = $wpdb->get_results("
                SELECT url_type, COUNT(*) as count 
                FROM $table 
                GROUP BY url_type
            ");
            
            WP_CLI::log("\nSitemap Statistics:");
            foreach ($stats as $stat) {
                WP_CLI::log("  {$stat->url_type}: {$stat->count} URLs");
            }
        } else {
            WP_CLI::error('Failed to build sitemap');
        }
    }
    
    /**
     * Validate sitemap
     * 
     * ## OPTIONS
     * 
     * [--url=<url>]
     * : Specific sitemap URL to validate
     * 
     * [--fix]
     * : Attempt to fix issues
     * 
     * ## EXAMPLES
     * 
     *     wp almaseo sitemaps validate
     *     wp almaseo sitemaps validate --fix
     */
    public function validate($args, $assoc_args) {
        $url = isset($assoc_args['url']) ? $assoc_args['url'] : home_url('/almaseo-sitemap.xml');
        $fix = isset($assoc_args['fix']);
        
        WP_CLI::log('Validating sitemap...');
        
        // Fetch sitemap
        $response = wp_remote_get($url, ['timeout' => 30]);
        
        if (is_wp_error($response)) {
            WP_CLI::error('Cannot fetch sitemap: ' . $response->get_error_message());
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        $code = wp_remote_retrieve_response_code($response);
        
        if ($code !== 200) {
            WP_CLI::error("HTTP $code error");
            return;
        }
        
        // Validate XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            WP_CLI::error('XML validation failed:');
            foreach (array_slice($errors, 0, 5) as $error) {
                WP_CLI::log("  Line {$error->line}: {$error->message}");
            }
            
            if ($fix) {
                WP_CLI::log('Attempting to rebuild sitemap...');
                $this->build([], ['mode' => 'static', 'force' => true]);
            }
            return;
        }
        
        // Check structure
        $namespaces = $xml->getNamespaces();
        $sitemap_count = 0;
        $url_count = 0;
        
        if (isset($xml->sitemap)) {
            // Index file
            foreach ($xml->sitemap as $sitemap) {
                $sitemap_count++;
            }
            WP_CLI::log("✓ Valid sitemap index with $sitemap_count sitemaps");
        } elseif (isset($xml->url)) {
            // URL set
            foreach ($xml->url as $url) {
                $url_count++;
            }
            WP_CLI::log("✓ Valid URL sitemap with $url_count URLs");
        } else {
            WP_CLI::warning('Unknown sitemap structure');
        }
        
        // Check accessibility of sub-sitemaps
        if ($sitemap_count > 0) {
            WP_CLI::log("\nChecking sub-sitemaps...");
            $failed = 0;
            
            foreach ($xml->sitemap as $sitemap) {
                $loc = (string)$sitemap->loc;
                $response = wp_remote_head($loc, ['timeout' => 5]);
                
                if (is_wp_error($response)) {
                    WP_CLI::warning("  ✗ $loc - " . $response->get_error_message());
                    $failed++;
                } else {
                    $code = wp_remote_retrieve_response_code($response);
                    if ($code === 200) {
                        WP_CLI::log("  ✓ $loc");
                    } else {
                        WP_CLI::warning("  ✗ $loc - HTTP $code");
                        $failed++;
                    }
                }
            }
            
            if ($failed > 0 && $fix) {
                WP_CLI::log('Rebuilding static sitemaps...');
                $this->build([], ['mode' => 'static', 'force' => true]);
            }
        }
        
        WP_CLI::success('Validation complete');
    }
    
    /**
     * Clear sitemap cache
     * 
     * ## OPTIONS
     * 
     * [--all]
     * : Clear all cache including database
     * 
     * ## EXAMPLES
     * 
     *     wp almaseo sitemaps clear
     *     wp almaseo sitemaps clear --all
     */
    public function clear($args, $assoc_args) {
        $all = isset($assoc_args['all']);
        
        WP_CLI::log('Clearing sitemap cache...');
        
        // Clear files
        $upload_dir = wp_upload_dir();
        $sitemap_dir = $upload_dir['basedir'] . '/almaseo/sitemaps';
        
        if (file_exists($sitemap_dir)) {
            $files = glob($sitemap_dir . '/*.{xml,gz}', GLOB_BRACE);
            $count = count($files);
            
            foreach ($files as $file) {
                unlink($file);
            }
            
            WP_CLI::log("Deleted $count sitemap files");
        }
        
        // Clear database
        if ($all) {
            global $wpdb;
            $table = $wpdb->prefix . 'almaseo_sitemap_urls';
            $wpdb->query("TRUNCATE TABLE $table");
            WP_CLI::log('Cleared sitemap database');
        }
        
        // Clear transients
        delete_transient('almaseo_sitemap_index');
        delete_transient('almaseo_sitemap_urls');
        
        // Reset build time
        delete_option('almaseo_last_sitemap_build');
        
        WP_CLI::success('Cache cleared');
    }
    
    /**
     * Submit sitemap to search engines
     * 
     * ## OPTIONS
     * 
     * [--engines=<engines>]
     * : Comma-separated list of engines (google,bing)
     * default: google,bing
     * 
     * ## EXAMPLES
     * 
     *     wp almaseo sitemaps ping
     *     wp almaseo sitemaps ping --engines=google
     */
    public function ping($args, $assoc_args) {
        $engines = isset($assoc_args['engines']) ? explode(',', $assoc_args['engines']) : ['google', 'bing'];
        $sitemap_url = home_url('/almaseo-sitemap.xml');
        
        WP_CLI::log('Pinging search engines...');
        
        $results = [];
        
        foreach ($engines as $engine) {
            $engine = trim($engine);
            
            switch($engine) {
                case 'google':
                    $ping_url = 'https://www.google.com/ping?sitemap=' . urlencode($sitemap_url);
                    break;
                case 'bing':
                    $ping_url = 'https://www.bing.com/ping?sitemap=' . urlencode($sitemap_url);
                    break;
                default:
                    WP_CLI::warning("Unknown engine: $engine");
                    continue 2;
            }
            
            $response = wp_remote_get($ping_url, ['timeout' => 10]);
            
            if (is_wp_error($response)) {
                WP_CLI::warning("$engine: " . $response->get_error_message());
            } else {
                $code = wp_remote_retrieve_response_code($response);
                if ($code === 200) {
                    WP_CLI::log("✓ $engine: Success");
                } else {
                    WP_CLI::warning("$engine: HTTP $code");
                }
            }
        }
        
        WP_CLI::success('Ping complete');
    }
}

/**
 * Cache Management Commands
 */
class AlmaSEO_Cache_Command {
    
    /**
     * Clear all AlmaSEO caches
     * 
     * ## OPTIONS
     * 
     * [--type=<type>]
     * : Cache type (all, transients, files, database)
     * default: all
     * 
     * ## EXAMPLES
     * 
     *     wp almaseo cache clear
     *     wp almaseo cache clear --type=transients
     */
    public function clear($args, $assoc_args) {
        $type = isset($assoc_args['type']) ? $assoc_args['type'] : 'all';
        
        WP_CLI::log("Clearing $type cache...");
        
        $cleared = [];
        
        // Clear transients
        if ($type === 'all' || $type === 'transients') {
            global $wpdb;
            $count = $wpdb->query("
                DELETE FROM {$wpdb->options} 
                WHERE option_name LIKE '_transient_almaseo%' 
                OR option_name LIKE '_transient_timeout_almaseo%'
            ");
            $cleared[] = "$count transients";
        }
        
        // Clear files
        if ($type === 'all' || $type === 'files') {
            $upload_dir = wp_upload_dir();
            $cache_dir = $upload_dir['basedir'] . '/almaseo/cache';
            
            if (file_exists($cache_dir)) {
                $files = glob($cache_dir . '/*');
                $count = count($files);
                array_map('unlink', $files);
                $cleared[] = "$count cache files";
            }
        }
        
        // Clear database cache
        if ($type === 'all' || $type === 'database') {
            global $wpdb;
            $table = $wpdb->prefix . 'almaseo_cache';
            if ($wpdb->get_var("SHOW TABLES LIKE '$table'") == $table) {
                $count = $wpdb->query("TRUNCATE TABLE $table");
                $cleared[] = "database cache";
            }
        }
        
        if (empty($cleared)) {
            WP_CLI::warning('No cache to clear');
        } else {
            WP_CLI::success('Cleared: ' . implode(', ', $cleared));
        }
    }
    
    /**
     * Display cache statistics
     * 
     * ## EXAMPLES
     * 
     *     wp almaseo cache stats
     */
    public function stats() {
        WP_CLI::log('Cache Statistics');
        WP_CLI::log(str_repeat('=', 50));
        
        // Transients
        global $wpdb;
        $transient_count = $wpdb->get_var("
            SELECT COUNT(*) FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_almaseo%'
        ");
        WP_CLI::log("Transients: $transient_count");
        
        // File cache
        $upload_dir = wp_upload_dir();
        $cache_dir = $upload_dir['basedir'] . '/almaseo/cache';
        
        if (file_exists($cache_dir)) {
            $files = glob($cache_dir . '/*');
            $count = count($files);
            $size = 0;
            foreach ($files as $file) {
                $size += filesize($file);
            }
            $size_mb = round($size / 1024 / 1024, 2);
            WP_CLI::log("Cache Files: $count files ({$size_mb} MB)");
        }
        
        // Sitemap files
        $sitemap_dir = $upload_dir['basedir'] . '/almaseo/sitemaps';
        
        if (file_exists($sitemap_dir)) {
            $xml_files = glob($sitemap_dir . '/*.xml');
            $gz_files = glob($sitemap_dir . '/*.gz');
            
            $total_files = count($xml_files) + count($gz_files);
            $total_size = 0;
            
            foreach (array_merge($xml_files, $gz_files) as $file) {
                $total_size += filesize($file);
            }
            
            $size_mb = round($total_size / 1024 / 1024, 2);
            WP_CLI::log("Sitemap Files: $total_files files ({$size_mb} MB)");
            
            if (count($gz_files) > 0 && count($xml_files) > 0) {
                $xml_size = array_sum(array_map('filesize', $xml_files));
                $gz_size = array_sum(array_map('filesize', $gz_files));
                $compression = round((1 - $gz_size / $xml_size) * 100, 1);
                WP_CLI::log("Compression Ratio: {$compression}%");
            }
        }
        
        WP_CLI::success('Stats complete');
    }
}

/**
 * IndexNow Commands
 */
class AlmaSEO_IndexNow_Command {
    
    /**
     * Configure IndexNow
     * 
     * ## OPTIONS
     * 
     * [--enable]
     * : Enable IndexNow
     * 
     * [--disable]
     * : Disable IndexNow
     * 
     * [--key=<key>]
     * : Set API key
     * 
     * [--generate]
     * : Generate new key
     * 
     * ## EXAMPLES
     * 
     *     wp almaseo indexnow --enable --generate
     *     wp almaseo indexnow --disable
     */
    public function __invoke($args, $assoc_args) {
        if (isset($assoc_args['enable'])) {
            update_option('almaseo_indexnow_enabled', true);
            WP_CLI::log('IndexNow enabled');
        }
        
        if (isset($assoc_args['disable'])) {
            update_option('almaseo_indexnow_enabled', false);
            WP_CLI::log('IndexNow disabled');
        }
        
        if (isset($assoc_args['generate'])) {
            $key = wp_generate_password(32, false);
            update_option('almaseo_indexnow_key', $key);
            WP_CLI::log("Generated key: $key");
            WP_CLI::log("Key file URL: " . home_url("/$key.txt"));
        }
        
        if (isset($assoc_args['key'])) {
            $key = $assoc_args['key'];
            update_option('almaseo_indexnow_key', $key);
            WP_CLI::log("Key set: $key");
        }
        
        // Show status
        $enabled = get_option('almaseo_indexnow_enabled', false);
        $key = get_option('almaseo_indexnow_key', '');
        
        WP_CLI::log("\nCurrent Status:");
        WP_CLI::log("  Enabled: " . ($enabled ? 'YES' : 'NO'));
        WP_CLI::log("  Has Key: " . (!empty($key) ? 'YES' : 'NO'));
        
        if (!empty($key)) {
            WP_CLI::log("  Key File: " . home_url("/$key.txt"));
        }
        
        WP_CLI::success('Configuration complete');
    }
    
    /**
     * Submit URLs to IndexNow
     * 
     * ## OPTIONS
     * 
     * [--url=<url>]
     * : Specific URL to submit
     * 
     * [--recent=<hours>]
     * : Submit URLs modified in last N hours
     * default: 24
     * 
     * [--all]
     * : Submit all URLs
     * 
     * ## EXAMPLES
     * 
     *     wp almaseo indexnow submit
     *     wp almaseo indexnow submit --url=https://site.com/page
     *     wp almaseo indexnow submit --recent=48
     */
    public function submit($args, $assoc_args) {
        $enabled = get_option('almaseo_indexnow_enabled', false);
        $key = get_option('almaseo_indexnow_key', '');
        
        if (!$enabled) {
            WP_CLI::error('IndexNow is not enabled');
            return;
        }
        
        if (empty($key)) {
            WP_CLI::error('No IndexNow key configured');
            return;
        }
        
        $urls = [];
        
        if (isset($assoc_args['url'])) {
            $urls[] = $assoc_args['url'];
        } elseif (isset($assoc_args['all'])) {
            global $wpdb;
            $table = $wpdb->prefix . 'almaseo_sitemap_urls';
            $urls = $wpdb->get_col("SELECT url FROM $table");
        } else {
            $hours = isset($assoc_args['recent']) ? intval($assoc_args['recent']) : 24;
            $since = date('Y-m-d H:i:s', strtotime("-$hours hours"));
            
            global $wpdb;
            $table = $wpdb->prefix . 'almaseo_sitemap_urls';
            $urls = $wpdb->get_col($wpdb->prepare(
                "SELECT url FROM $table WHERE last_modified > %s",
                $since
            ));
        }
        
        if (empty($urls)) {
            WP_CLI::warning('No URLs to submit');
            return;
        }
        
        WP_CLI::log('Submitting ' . count($urls) . ' URLs to IndexNow...');
        
        // Submit in batches
        $batches = array_chunk($urls, 100);
        $success = 0;
        $failed = 0;
        
        foreach ($batches as $batch) {
            $result = $this->submit_batch($batch, $key);
            if ($result) {
                $success += count($batch);
                WP_CLI::log('  Batch submitted: ' . count($batch) . ' URLs');
            } else {
                $failed += count($batch);
                WP_CLI::warning('  Batch failed: ' . count($batch) . ' URLs');
            }
        }
        
        WP_CLI::success("Submitted $success URLs, $failed failed");
    }
    
    private function submit_batch($urls, $key) {
        $endpoint = 'https://api.indexnow.org/indexnow';
        
        $body = json_encode([
            'host' => parse_url(home_url(), PHP_URL_HOST),
            'key' => $key,
            'keyLocation' => home_url("/$key.txt"),
            'urlList' => $urls
        ]);
        
        $response = wp_remote_post($endpoint, [
            'headers' => ['Content-Type' => 'application/json'],
            'body' => $body,
            'timeout' => 30
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        
        // Log submission
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_indexnow_log';
        $wpdb->insert($table, [
            'urls' => json_encode($urls),
            'status' => $code === 200 ? 'success' : 'failed',
            'response_code' => $code,
            'submitted_at' => current_time('mysql')
        ]);
        
        return $code === 200;
    }
}

// Register commands
WP_CLI::add_command('almaseo', 'AlmaSEO_CLI_Command');
WP_CLI::add_command('almaseo sitemaps', 'AlmaSEO_Sitemaps_Command');
WP_CLI::add_command('almaseo cache', 'AlmaSEO_Cache_Command');
WP_CLI::add_command('almaseo indexnow', 'AlmaSEO_IndexNow_Command');

// Register deployment commands from other files
if (class_exists('\\AlmaSEO\\Deployment\\PreflightChecker')) {
    WP_CLI::add_command('almaseo preflight', 'AlmaSEO_Preflight_Command');
}

if (class_exists('\\AlmaSEO\\Testing\\AutomatedTester')) {
    WP_CLI::add_command('almaseo test', 'AlmaSEO_Test_Command');
}