<?php
/**
 * AlmaSEO Sitemap WP-CLI Commands
 * 
 * WP-CLI commands for sitemap management
 * 
 * @package AlmaSEO
 * @since 4.7.0
 */

if (!defined('WP_CLI')) {
    return;
}

class Alma_Sitemap_CLI {
    
    /**
     * Build sitemaps
     * 
     * ## OPTIONS
     * 
     * [--mode=<mode>]
     * : Build mode (static or dynamic)
     * default: static
     * 
     * [--no-gzip]
     * : Skip gzip compression
     * 
     * [--force]
     * : Force build even if locked
     * 
     * ## EXAMPLES
     * 
     *     wp almaseo sitemaps build
     *     wp almaseo sitemaps build --mode=static --no-gzip
     *     wp almaseo sitemaps build --force
     * 
     * @when after_wp_load
     */
    public function build($args, $assoc_args) {
        $mode = isset($assoc_args['mode']) ? $assoc_args['mode'] : 'static';
        $gzip = !isset($assoc_args['no-gzip']);
        $force = isset($assoc_args['force']);
        
        WP_CLI::log('Starting sitemap build...');
        WP_CLI::log('Mode: ' . $mode);
        WP_CLI::log('Gzip: ' . ($gzip ? 'enabled' : 'disabled'));
        
        // Load dependencies
        require_once dirname(__FILE__) . '/class-alma-sitemap-manager.php';
        require_once dirname(__FILE__) . '/class-alma-sitemap-writer.php';
        
        $writer = new Alma_Sitemap_Writer();
        
        // Check lock
        if ($writer->is_locked() && !$force) {
            WP_CLI::error('Build already in progress. Use --force to override.');
        }
        
        if ($mode === 'static') {
            $this->build_static($writer, $gzip);
        } else {
            WP_CLI::log('Dynamic mode - sitemaps will be generated on demand.');
            WP_CLI::success('Dynamic mode configured.');
        }
    }
    
    /**
     * Build static sitemaps
     */
    private function build_static($writer, $gzip = true) {
        $start_time = microtime(true);
        
        // Start build
        $result = $writer->start_build();
        if (is_wp_error($result)) {
            WP_CLI::error($result->get_error_message());
        }
        
        $manager = Alma_Sitemap_Manager::get_instance();
        $providers = $manager->get_providers();
        
        $total_urls = 0;
        $sitemaps = array();
        
        // Progress bar
        $progress = \WP_CLI\Utils\make_progress_bar('Building sitemaps', count($providers));
        
        foreach ($providers as $name => $provider_class) {
            WP_CLI::log('Processing provider: ' . $name);
            
            $provider_start = microtime(true);
            $urls = $writer->generate_with_seek($provider_class, $name);
            $provider_time = round((microtime(true) - $provider_start) * 1000);
            
            WP_CLI::log(sprintf(
                '  - %s: %d URLs in %dms',
                $name,
                $urls,
                $provider_time
            ));
            
            $total_urls += $urls;
            
            // Add to sitemap index
            if ($urls > 0) {
                $manifest = $writer->get_manifest();
                if ($manifest) {
                    foreach ($manifest['files'] as $file) {
                        if (strpos($file['url'], 'sitemap-' . $name) !== false) {
                            $sitemaps[] = array(
                                'loc' => $file['url'],
                                'lastmod' => date('c')
                            );
                        }
                    }
                }
            }
            
            $progress->tick();
        }
        
        $progress->finish();
        
        // Write index
        WP_CLI::log('Writing sitemap index...');
        $writer->write_index($sitemaps);
        
        // Finalize
        $stats = $writer->finalize_build();
        
        $duration = round((microtime(true) - $start_time) * 1000);
        
        // Output summary
        WP_CLI::success(sprintf(
            'Build complete! %d files, %d URLs in %dms',
            $stats['files'],
            $stats['urls'],
            $duration
        ));
        
        // Detailed stats
        if (!empty($stats['by_provider'])) {
            WP_CLI::log("\nProvider Statistics:");
            foreach ($stats['by_provider'] as $provider => $pstats) {
                WP_CLI::log(sprintf(
                    '  %s: %d files, %d URLs, %dms',
                    $provider,
                    $pstats['files'],
                    $pstats['urls'],
                    $pstats['ms']
                ));
            }
        }
    }
    
    /**
     * Validate sitemaps
     * 
     * ## OPTIONS
     * 
     * [--url=<url>]
     * : Specific sitemap URL to validate
     * 
     * ## EXAMPLES
     * 
     *     wp almaseo sitemaps validate
     *     wp almaseo sitemaps validate --url=https://example.com/almaseo-sitemap.xml
     * 
     * @when after_wp_load
     */
    public function validate($args, $assoc_args) {
        WP_CLI::log('Validating sitemaps...');
        
        require_once dirname(__FILE__) . '/class-alma-sitemap-validator.php';
        
        $validator = new Alma_Sitemap_Validator();
        
        if (isset($assoc_args['url'])) {
            $url = $assoc_args['url'];
        } else {
            $url = home_url('/almaseo-sitemap.xml');
        }
        
        WP_CLI::log('Validating: ' . $url);
        
        // Fetch and validate
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'redirection' => 5,
            'user-agent' => 'AlmaSEO WP-CLI Validator'
        ));
        
        if (is_wp_error($response)) {
            WP_CLI::error('Failed to fetch sitemap: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $http_code = wp_remote_retrieve_response_code($response);
        
        if ($http_code !== 200) {
            WP_CLI::error('HTTP error: ' . $http_code);
        }
        
        // Parse XML
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($body);
        
        if ($xml === false) {
            $errors = libxml_get_errors();
            WP_CLI::error('XML parsing failed: ' . $errors[0]->message);
        }
        
        // Check if index or urlset
        if ($xml->getName() === 'sitemapindex') {
            WP_CLI::log('Type: Sitemap Index');
            $sitemap_count = count($xml->sitemap);
            WP_CLI::log('Sitemaps: ' . $sitemap_count);
            
            // Validate each child sitemap
            $progress = \WP_CLI\Utils\make_progress_bar('Validating child sitemaps', $sitemap_count);
            
            $total_urls = 0;
            $errors = array();
            
            foreach ($xml->sitemap as $sitemap) {
                $loc = (string)$sitemap->loc;
                
                $child_response = wp_remote_get($loc, array(
                    'timeout' => 30,
                    'user-agent' => 'AlmaSEO WP-CLI Validator'
                ));
                
                if (!is_wp_error($child_response)) {
                    $child_body = wp_remote_retrieve_body($child_response);
                    $child_xml = simplexml_load_string($child_body);
                    
                    if ($child_xml && $child_xml->getName() === 'urlset') {
                        $url_count = count($child_xml->url);
                        $total_urls += $url_count;
                        
                        if ($url_count > 50000) {
                            $errors[] = $loc . ' exceeds 50,000 URL limit';
                        }
                    } else {
                        $errors[] = $loc . ' is not a valid urlset';
                    }
                } else {
                    $errors[] = $loc . ' failed to load';
                }
                
                $progress->tick();
            }
            
            $progress->finish();
            
            WP_CLI::log('Total URLs: ' . $total_urls);
            
            if (!empty($errors)) {
                WP_CLI::warning('Validation issues found:');
                foreach ($errors as $error) {
                    WP_CLI::log('  - ' . $error);
                }
                exit(1);
            }
            
        } else if ($xml->getName() === 'urlset') {
            WP_CLI::log('Type: URL Set');
            $url_count = count($xml->url);
            WP_CLI::log('URLs: ' . $url_count);
            
            if ($url_count > 50000) {
                WP_CLI::warning('Exceeds 50,000 URL limit!');
                exit(1);
            }
        }
        
        WP_CLI::success('Validation passed!');
    }
    
    /**
     * Submit sitemaps to search engines
     * 
     * ## OPTIONS
     * 
     * [--indexnow]
     * : Submit via IndexNow
     * 
     * [--google]
     * : Submit to Google
     * 
     * [--bing]
     * : Submit to Bing
     * 
     * ## EXAMPLES
     * 
     *     wp almaseo sitemaps submit
     *     wp almaseo sitemaps submit --indexnow
     *     wp almaseo sitemaps submit --google --bing
     * 
     * @when after_wp_load
     */
    public function submit($args, $assoc_args) {
        WP_CLI::log('Submitting sitemaps...');
        
        $sitemap_url = home_url('/almaseo-sitemap.xml');
        
        if (isset($assoc_args['indexnow'])) {
            $this->submit_indexnow($sitemap_url);
        }
        
        if (isset($assoc_args['google'])) {
            $this->submit_google($sitemap_url);
        }
        
        if (isset($assoc_args['bing'])) {
            $this->submit_bing($sitemap_url);
        }
        
        // Default: submit to all if no specific flag
        if (!isset($assoc_args['indexnow']) && 
            !isset($assoc_args['google']) && 
            !isset($assoc_args['bing'])) {
            $this->submit_google($sitemap_url);
            $this->submit_bing($sitemap_url);
        }
        
        WP_CLI::success('Sitemap submission complete!');
    }
    
    /**
     * Submit via IndexNow
     */
    private function submit_indexnow($url) {
        WP_CLI::log('Submitting via IndexNow...');
        
        require_once dirname(__FILE__) . '/class-alma-indexnow.php';
        
        $indexnow = new Alma_IndexNow();
        $result = $indexnow->submit_url($url);
        
        if (is_wp_error($result)) {
            WP_CLI::warning('IndexNow submission failed: ' . $result->get_error_message());
        } else {
            WP_CLI::success('IndexNow submission successful!');
        }
    }
    
    /**
     * Submit to Google
     */
    private function submit_google($url) {
        WP_CLI::log('Submitting to Google...');
        
        $ping_url = 'https://www.google.com/ping?sitemap=' . urlencode($url);
        
        $response = wp_remote_get($ping_url, array(
            'timeout' => 30,
            'user-agent' => 'AlmaSEO/' . ALMASEO_VERSION
        ));
        
        if (is_wp_error($response)) {
            WP_CLI::warning('Google submission failed: ' . $response->get_error_message());
        } else {
            $code = wp_remote_retrieve_response_code($response);
            if ($code === 200) {
                WP_CLI::success('Google submission successful!');
            } else {
                WP_CLI::warning('Google submission returned code: ' . $code);
            }
        }
    }
    
    /**
     * Submit to Bing
     */
    private function submit_bing($url) {
        WP_CLI::log('Submitting to Bing...');
        
        $ping_url = 'https://www.bing.com/ping?sitemap=' . urlencode($url);
        
        $response = wp_remote_get($ping_url, array(
            'timeout' => 30,
            'user-agent' => 'AlmaSEO/' . ALMASEO_VERSION
        ));
        
        if (is_wp_error($response)) {
            WP_CLI::warning('Bing submission failed: ' . $response->get_error_message());
        } else {
            $code = wp_remote_retrieve_response_code($response);
            if ($code === 200) {
                WP_CLI::success('Bing submission successful!');
            } else {
                WP_CLI::warning('Bing submission returned code: ' . $code);
            }
        }
    }
    
    /**
     * Show sitemap status
     * 
     * ## EXAMPLES
     * 
     *     wp almaseo sitemaps status
     * 
     * @when after_wp_load
     */
    public function status($args, $assoc_args) {
        WP_CLI::log('Sitemap Status');
        WP_CLI::log('==============');
        
        $settings = get_option('almaseo_sitemap_settings', array());
        
        // Check if enabled
        $enabled = isset($settings['enabled']) ? $settings['enabled'] : true;
        WP_CLI::log('Enabled: ' . ($enabled ? 'Yes' : 'No'));
        
        // Storage mode
        $mode = isset($settings['perf']['storage_mode']) ? $settings['perf']['storage_mode'] : 'dynamic';
        WP_CLI::log('Storage Mode: ' . $mode);
        
        // Gzip
        $gzip = isset($settings['perf']['gzip']) ? $settings['perf']['gzip'] : true;
        WP_CLI::log('Gzip: ' . ($gzip ? 'Enabled' : 'Disabled'));
        
        // Build stats
        if (isset($settings['health']['last_build_stats'])) {
            $stats = $settings['health']['last_build_stats'];
            
            WP_CLI::log("\nLast Build:");
            WP_CLI::log('  Files: ' . $stats['files']);
            WP_CLI::log('  URLs: ' . $stats['urls']);
            WP_CLI::log('  Duration: ' . $stats['duration_ms'] . 'ms');
            
            if ($stats['started']) {
                $built = date('Y-m-d H:i:s', $stats['started']);
                WP_CLI::log('  Built: ' . $built);
            }
        } else {
            WP_CLI::log("\nNo build statistics available.");
        }
        
        // Check for lock
        $lock = get_option('almaseo_sitemaps_build_lock');
        if ($lock && isset($lock['expires']) && $lock['expires'] > time()) {
            WP_CLI::warning('Build currently in progress!');
        }
        
        // Check static files
        $upload_dir = wp_upload_dir();
        $storage_path = $upload_dir['basedir'] . '/almaseo/sitemaps/current/';
        
        if (file_exists($storage_path . 'manifest.json')) {
            $manifest = json_decode(file_get_contents($storage_path . 'manifest.json'), true);
            
            WP_CLI::log("\nStatic Files:");
            WP_CLI::log('  Total Files: ' . count($manifest['files']));
            WP_CLI::log('  Total URLs: ' . $manifest['total_urls']);
            
            $total_size = 0;
            $total_gzip_size = 0;
            
            foreach ($manifest['files'] as $file) {
                $total_size += $file['bytes'];
                
                $gz_path = $file['path'] . '.gz';
                if (file_exists($gz_path)) {
                    $total_gzip_size += filesize($gz_path);
                }
            }
            
            WP_CLI::log('  XML Size: ' . size_format($total_size));
            
            if ($total_gzip_size > 0) {
                WP_CLI::log('  Gzip Size: ' . size_format($total_gzip_size));
                $compression = round((1 - ($total_gzip_size / $total_size)) * 100, 1);
                WP_CLI::log('  Compression: ' . $compression . '%');
            }
        } else {
            WP_CLI::log("\nNo static files found.");
        }
    }
}

// Register commands
WP_CLI::add_command('almaseo sitemaps', 'Alma_Sitemap_CLI');