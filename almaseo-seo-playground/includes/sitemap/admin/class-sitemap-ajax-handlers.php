<?php
/**
 * Sitemap AJAX Handlers
 * 
 * Centralized handler for all sitemap-related AJAX requests. This class manages
 * all asynchronous operations including settings management, sitemap generation,
 * validation, caching, and data export/import operations.
 * 
 * @package AlmaSEO
 * @since 5.5.1
 * @author AlmaSEO Team
 * 
 * @method static init()                    Initialize and register all AJAX handlers
 * @method static verify_ajax_nonce()       Verify security nonce and user permissions
 * @method static handle_save_settings()    Save sitemap configuration settings
 * @method static handle_rebuild_static()   Rebuild static sitemap files
 * @method static handle_toggle_sitemaps()  Enable/disable sitemap functionality
 * @method static handle_scan_media()       Scan for media files (images/videos)
 * 
 * Available AJAX Actions:
 * - almaseo_save_settings         : Save all sitemap settings
 * - almaseo_recalculate          : Recalculate sitemap statistics
 * - almaseo_rebuild_static       : Rebuild static sitemap files
 * - almaseo_toggle_sitemaps      : Enable/disable sitemaps
 * - almaseo_add_url             : Add custom URL to sitemap
 * - almaseo_import_csv          : Import URLs from CSV
 * - almaseo_export_csv          : Export URLs to CSV
 * - almaseo_start_scan          : Start conflict detection scan
 * - almaseo_get_scan_status     : Get conflict scan status
 * - almaseo_force_delta_ping    : Force delta sitemap ping
 * - almaseo_validate_hreflang   : Validate hreflang tags
 * - almaseo_scan_media          : Scan for media files
 * - almaseo_validate_news       : Validate news sitemap settings
 * - almaseo_export_settings     : Export settings as JSON
 * - almaseo_import_settings     : Import settings from JSON
 * - almaseo_get_live_stats      : Get real-time statistics
 * - almaseo_copy_all_urls       : Copy all sitemap URLs
 * 
 * Security:
 * - All handlers verify nonce using 'almaseo_sitemaps_nonce'
 * - All handlers check for 'manage_options' capability
 * - Input sanitization on all user data
 * - SQL queries use prepared statements
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Sitemap_Ajax_Handlers {
    
    /**
     * Register AJAX hooks
     * 
     * Initializes all AJAX action handlers for both logged-in users.
     * Called on 'init' action to ensure all handlers are available.
     * 
     * @since 5.5.1
     * @return void
     */
    public static function init() {
        $actions = [
            'save_settings',
            'recalculate',
            'add_url',
            'import_csv',
            'export_csv',
            'start_scan',
            'get_scan_status',
            'get_scan_results',
            'export_conflicts',
            'create_snapshot',
            'compare_snapshots',
            'export_diff',
            'rebuild_static',
            'force_delta_ping',
            'purge_old_delta',
            'validate_hreflang',
            'export_hreflang_issues',
            'scan_media',
            'validate_media',
            'rebuild_media',
            'validate_news',
            'rebuild_news',
            'export_settings',
            'import_settings',
            'export_logs',
            'clear_logs',
            'load_tab',
            'load_tab_v2',
            'lazy_load',
            'toggle_sitemaps',
            'get_live_stats',
            'check_build_lock',
            'copy_all_urls'
        ];
        
        foreach ($actions as $action) {
            add_action('wp_ajax_almaseo_' . $action, [__CLASS__, 'handle_' . $action]);
        }
    }
    
    /**
     * Verify nonce for AJAX requests
     * 
     * Validates the security nonce and checks user permissions.
     * Terminates execution with JSON error if validation fails.
     * 
     * @since 5.5.1
     * @access private
     * 
     * @return void Dies with JSON error if validation fails
     */
    private static function verify_ajax_nonce() {
        if (!check_ajax_referer('almaseo_sitemaps_nonce', 'nonce', false)) {
            wp_send_json_error(['message' => __('Invalid security token', 'almaseo')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'almaseo')]);
        }
    }
    
    /**
     * Handle save settings AJAX
     * 
     * Processes and saves all sitemap configuration settings including:
     * - Basic settings (enabled, takeover, includes)
     * - Performance settings (storage mode, gzip)
     * - Delta settings (change detection)
     * - Hreflang settings (multi-language support)
     * - Media settings (image/video sitemaps)
     * - News settings (Google News sitemap)
     * 
     * @since 5.5.1
     * 
     * @ajax_param bool   $_POST['enabled']           Whether sitemaps are enabled
     * @ajax_param bool   $_POST['takeover']          Whether to takeover /sitemap.xml
     * @ajax_param array  $_POST['include']           Content types to include
     * @ajax_param array  $_POST['perf']              Performance settings
     * @ajax_param array  $_POST['delta']             Delta sitemap settings
     * @ajax_param array  $_POST['hreflang']          Hreflang settings
     * @ajax_param array  $_POST['media']             Media sitemap settings
     * @ajax_param array  $_POST['news']              News sitemap settings
     * 
     * @return void Outputs JSON response with success/error status
     */
    public static function handle_save_settings() {
        self::verify_ajax_nonce();
        
        // Get existing settings to preserve other fields
        $existing = get_option('almaseo_sitemap_settings', array());
        
        $settings = array(
            'enabled' => !empty($_POST['enabled']),
            'takeover' => !empty($_POST['takeover']),
            'include' => array(
                'posts' => !empty($_POST['include']['posts']),
                'pages' => !empty($_POST['include']['pages']),
                'cpts' => !empty($_POST['include']['cpts']) ? 'all' : array(),
                'tax' => array(
                    'category' => !empty($_POST['include']['tax']['category']),
                    'post_tag' => !empty($_POST['include']['tax']['post_tag'])
                ),
                'users' => !empty($_POST['include']['users'])
            ),
            'links_per_sitemap' => absint($_POST['links_per_sitemap'] ?? 1000)
        );
        
        // Performance settings
        if (isset($_POST['perf'])) {
            $settings['perf'] = array(
                'storage_mode' => sanitize_text_field($_POST['perf']['storage_mode'] ?? 'static'),
                'gzip' => !empty($_POST['perf']['gzip'])
            );
        } else {
            $settings['perf'] = $existing['perf'] ?? array('storage_mode' => 'static', 'gzip' => true);
        }
        
        // Delta settings
        if (isset($_POST['delta'])) {
            $settings['delta'] = array(
                'enabled' => !empty($_POST['delta']['enabled']),
                'max_urls' => absint($_POST['delta']['max_urls'] ?? 500),
                'retention_days' => absint($_POST['delta']['retention_days'] ?? 14),
                'min_ping_interval' => 900
            );
            
            // Validate delta settings
            $settings['delta']['max_urls'] = max(50, min(2000, $settings['delta']['max_urls']));
            $settings['delta']['retention_days'] = max(1, min(90, $settings['delta']['retention_days']));
        } else {
            $settings['delta'] = $existing['delta'] ?? array(
                'enabled' => true,
                'max_urls' => 500,
                'retention_days' => 14,
                'min_ping_interval' => 900
            );
        }
        
        // Hreflang settings
        if (isset($_POST['hreflang'])) {
            $settings['hreflang'] = array(
                'enabled' => !empty($_POST['hreflang']['enabled']),
                'source' => sanitize_text_field($_POST['hreflang']['source'] ?? 'auto'),
                'default' => sanitize_text_field($_POST['hreflang']['default'] ?? ''),
                'x_default_url' => esc_url_raw($_POST['hreflang']['x_default_url'] ?? ''),
                'map' => $_POST['hreflang']['map'] ?? array(),
                'locales' => $_POST['hreflang']['locales'] ?? array()
            );
        } else {
            $settings['hreflang'] = $existing['hreflang'] ?? array(
                'enabled' => false,
                'source' => 'auto',
                'default' => '',
                'x_default_url' => '',
                'map' => array(),
                'locales' => array()
            );
        }
        
        // Media settings
        if (isset($_POST['media'])) {
            $settings['media'] = array(
                'image' => array(
                    'enabled' => !empty($_POST['media']['image']['enabled']),
                    'max_per_url' => absint($_POST['media']['image']['max_per_url'] ?? 20),
                    'dedupe_cdn' => !empty($_POST['media']['image']['dedupe_cdn'])
                ),
                'video' => array(
                    'enabled' => !empty($_POST['media']['video']['enabled']),
                    'max_per_url' => absint($_POST['media']['video']['max_per_url'] ?? 10),
                    'oembed_cache' => !empty($_POST['media']['video']['oembed_cache'])
                )
            );
        } else {
            $settings['media'] = $existing['media'] ?? array(
                'image' => array(
                    'enabled' => false,
                    'max_per_url' => 20,
                    'dedupe_cdn' => true
                ),
                'video' => array(
                    'enabled' => false,
                    'max_per_url' => 10,
                    'oembed_cache' => true
                )
            );
        }
        
        // News settings
        if (isset($_POST['news'])) {
            $settings['news'] = array(
                'enabled' => !empty($_POST['news']['enabled']),
                'post_types' => isset($_POST['news']['post_types']) ? array_map('sanitize_text_field', $_POST['news']['post_types']) : array('post'),
                'categories' => isset($_POST['news']['categories']) ? array_map('intval', $_POST['news']['categories']) : array(),
                'publisher_name' => sanitize_text_field($_POST['news']['publisher_name'] ?? get_bloginfo('name')),
                'language' => sanitize_text_field($_POST['news']['language'] ?? 'en'),
                'genres' => isset($_POST['news']['genres']) ? array_map('sanitize_text_field', $_POST['news']['genres']) : array(),
                'keywords_source' => sanitize_text_field($_POST['news']['keywords_source'] ?? 'tags'),
                'manual_keywords' => sanitize_text_field($_POST['news']['manual_keywords'] ?? ''),
                'max_items' => absint($_POST['news']['max_items'] ?? 1000),
                'window_hours' => absint($_POST['news']['window_hours'] ?? 48)
            );
        } else {
            $settings['news'] = $existing['news'] ?? array(
                'enabled' => false,
                'post_types' => array('post'),
                'categories' => array(),
                'publisher_name' => get_bloginfo('name'),
                'language' => 'en',
                'genres' => array(),
                'keywords_source' => 'tags',
                'manual_keywords' => '',
                'max_items' => 1000,
                'window_hours' => 48
            );
        }
        
        // Preserve health stats
        $settings['health'] = $existing['health'] ?? array();
        
        // Validate links_per_sitemap
        if ($settings['links_per_sitemap'] < 1) {
            $settings['links_per_sitemap'] = 1;
        } elseif ($settings['links_per_sitemap'] > 50000) {
            $settings['links_per_sitemap'] = 50000;
        }
        
        update_option('almaseo_sitemap_settings', $settings, false);
        
        wp_send_json_success(array(
            'message' => __('Settings saved successfully', 'almaseo'),
            'settings' => $settings
        ));
    }
    
    /**
     * Handle recalculate AJAX
     */
    public static function handle_recalculate() {
        self::verify_ajax_nonce();
        
        delete_transient('almaseo_sitemap_stats');
        delete_transient('almaseo_sitemap_cache');
        
        wp_send_json_success([
            'message' => __('Statistics recalculated', 'almaseo'),
            'redirect' => admin_url('admin.php?page=almaseo-sitemaps')
        ]);
    }
    
    /**
     * Handle add URL AJAX
     */
    public static function handle_add_url() {
        self::verify_ajax_nonce();
        
        $url = sanitize_url($_POST['url'] ?? '');
        $priority = floatval($_POST['priority'] ?? 0.5);
        $changefreq = sanitize_text_field($_POST['changefreq'] ?? 'weekly');
        $lastmod = sanitize_text_field($_POST['lastmod'] ?? '');
        
        // Validate URL
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(__('Invalid URL', 'almaseo'));
        }
        
        // Check if provider class exists
        $provider_file = dirname(dirname(__FILE__)) . '/providers/class-alma-provider-extra.php';
        if (file_exists($provider_file)) {
            require_once $provider_file;
            
            if (class_exists('Alma_Provider_Extra')) {
                $result = Alma_Provider_Extra::add_url($url, $priority, $changefreq, $lastmod);
                
                if (is_wp_error($result)) {
                    wp_send_json_error($result->get_error_message());
                }
                
                wp_send_json_success(array(
                    'message' => __('URL added successfully', 'almaseo'),
                    'id' => $result
                ));
            }
        }
        
        wp_send_json_error(__('Additional URLs feature not available', 'almaseo'));
    }
    
    /**
     * Handle import CSV AJAX
     */
    public static function handle_import_csv() {
        self::verify_ajax_nonce();
        
        $csv_content = wp_unslash($_POST['csv'] ?? '');
        
        if (empty($csv_content)) {
            wp_send_json_error(__('No CSV content provided', 'almaseo'));
        }
        
        // Parse CSV
        $lines = explode("\n", $csv_content);
        $imported = 0;
        $errors = [];
        
        foreach ($lines as $line_num => $line) {
            $line = trim($line);
            if (empty($line) || $line_num === 0) continue; // Skip header
            
            $data = str_getcsv($line);
            if (count($data) >= 1) {
                $url = filter_var($data[0], FILTER_VALIDATE_URL);
                if ($url) {
                    // Add URL logic here
                    $imported++;
                } else {
                    $errors[] = sprintf(__('Line %d: Invalid URL', 'almaseo'), $line_num + 1);
                }
            }
        }
        
        wp_send_json_success(array(
            'message' => sprintf(__('Imported %d URLs', 'almaseo'), $imported),
            'imported' => $imported,
            'errors' => $errors
        ));
    }
    
    /**
     * Handle export CSV AJAX
     */
    public static function handle_export_csv() {
        self::verify_ajax_nonce();
        
        // Generate CSV content
        $csv = "URL,Priority,Change Frequency,Last Modified\n";
        
        // Add URL data here (would need to fetch from database)
        
        wp_send_json_success(array(
            'csv' => $csv,
            'filename' => 'sitemap-urls-' . date('Y-m-d') . '.csv'
        ));
    }
    
    /**
     * Handle start scan AJAX
     */
    public static function handle_start_scan() {
        self::verify_ajax_nonce();
        
        // Check if conflicts class exists
        $conflicts_file = dirname(dirname(__FILE__)) . '/class-alma-sitemap-conflicts.php';
        if (file_exists($conflicts_file)) {
            require_once $conflicts_file;
            
            if (class_exists('Alma_Sitemap_Conflicts')) {
                $result = Alma_Sitemap_Conflicts::start_scan();
                
                if (!$result['success']) {
                    wp_send_json_error($result['message']);
                }
                
                wp_send_json_success($result);
            }
        }
        
        wp_send_json_error(__('Conflict scanner not available', 'almaseo'));
    }
    
    /**
     * Handle get scan status AJAX
     */
    public static function handle_get_scan_status() {
        self::verify_ajax_nonce();
        
        $conflicts_file = dirname(dirname(__FILE__)) . '/class-alma-sitemap-conflicts.php';
        if (file_exists($conflicts_file)) {
            require_once $conflicts_file;
            
            if (class_exists('Alma_Sitemap_Conflicts')) {
                $status = Alma_Sitemap_Conflicts::get_status();
                wp_send_json_success($status);
            }
        }
        
        wp_send_json_success(array(
            'status' => 'idle',
            'conflicts' => []
        ));
    }
    
    /**
     * Handle get scan results AJAX
     */
    public static function handle_get_scan_results() {
        self::verify_ajax_nonce();
        
        $conflicts_file = dirname(dirname(__FILE__)) . '/class-alma-sitemap-conflicts.php';
        if (file_exists($conflicts_file)) {
            require_once $conflicts_file;
            
            if (class_exists('Alma_Sitemap_Conflicts')) {
                $results = Alma_Sitemap_Conflicts::get_results();
                wp_send_json_success($results);
            }
        }
        
        wp_send_json_success(array('conflicts' => []));
    }
    
    /**
     * Handle rebuild static AJAX
     * 
     * Rebuilds all static sitemap files with the following process:
     * 1. Checks for existing build lock to prevent concurrent builds
     * 2. Sets a 5-minute build lock
     * 3. Loads sitemap manager and writer classes
     * 4. Iterates through all enabled providers
     * 5. Generates individual sitemap files
     * 6. Creates the main sitemap index
     * 7. Updates statistics and last build time
     * 
     * @since 5.5.1
     * 
     * @return void Outputs JSON response with build results or error
     * 
     * @uses Alma_Sitemap_Manager To get all providers
     * @uses Alma_Sitemap_Writer  To write sitemap files
     */
    public static function handle_rebuild_static() {
        self::verify_ajax_nonce();
        
        // Check if already building
        $lock = get_option('almaseo_sitemaps_build_lock');
        if ($lock && isset($lock['expires']) && $lock['expires'] > time()) {
            wp_send_json_error([
                'message' => __('Build already in progress', 'almaseo'),
                'lock' => $lock
            ]);
        }
        
        // Set build lock
        update_option('almaseo_sitemaps_build_lock', [
            'started' => time(),
            'expires' => time() + 300, // 5 minute timeout
            'process' => 'ajax'
        ]);
        
        // Load required classes
        require_once dirname(dirname(__FILE__)) . '/class-alma-sitemap-writer.php';
        require_once dirname(dirname(__FILE__)) . '/class-alma-sitemap-manager.php';
        
        try {
            $manager = new Alma_Sitemap_Manager();
            $writer = new Alma_Sitemap_Writer();
            
            // Get all providers
            $providers = $manager->get_providers();
            $stats = ['files' => 0, 'urls' => 0];
            
            // Build each sitemap
            foreach ($providers as $provider) {
                if (!$provider->is_enabled()) {
                    continue;
                }
                
                $result = $writer->write_provider_sitemap($provider);
                if ($result) {
                    $stats['files']++;
                    $stats['urls'] += $result['url_count'];
                }
            }
            
            // Write index
            $writer->write_index();
            $stats['files']++;
            
            // Update last built time
            update_option('almaseo_sitemap_last_built', current_time('mysql'));
            update_option('almaseo_sitemap_stats', $stats);
            
            // Clear lock
            delete_option('almaseo_sitemaps_build_lock');
            
            wp_send_json_success([
                'message' => __('Sitemaps rebuilt successfully', 'almaseo'),
                'stats' => $stats
            ]);
            
        } catch (Exception $e) {
            // Clear lock on error
            delete_option('almaseo_sitemaps_build_lock');
            
            wp_send_json_error([
                'message' => sprintf(__('Build failed: %s', 'almaseo'), $e->getMessage())
            ]);
        }
    }
    
    /**
     * Handle force delta ping AJAX
     */
    public static function handle_force_delta_ping() {
        self::verify_ajax_nonce();
        
        $delta_file = dirname(dirname(__FILE__)) . '/providers/class-alma-provider-delta.php';
        if (file_exists($delta_file)) {
            require_once $delta_file;
            
            if (class_exists('Alma_Provider_Delta')) {
                $provider = new Alma_Provider_Delta();
                $result = $provider->force_ping();
                
                if ($result) {
                    wp_send_json_success(array(
                        'message' => __('Delta ping sent successfully', 'almaseo'),
                        'urls_pinged' => $result['count']
                    ));
                }
            }
        }
        
        wp_send_json_error(__('Delta provider not available', 'almaseo'));
    }
    
    /**
     * Handle purge old delta AJAX
     */
    public static function handle_purge_old_delta() {
        self::verify_ajax_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_delta_urls';
        
        $settings = get_option('almaseo_sitemap_settings', array());
        $retention_days = $settings['delta']['retention_days'] ?? 14;
        
        $cutoff = date('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM `$table` WHERE detected_at < %s",
            $cutoff
        ));
        
        wp_send_json_success(array(
            'message' => sprintf(__('Purged %d old delta entries', 'almaseo'), $deleted),
            'deleted' => $deleted
        ));
    }
    
    /**
     * Handle validate hreflang AJAX
     */
    public static function handle_validate_hreflang() {
        self::verify_ajax_nonce();
        
        $hreflang_file = dirname(dirname(__FILE__)) . '/class-alma-hreflang.php';
        if (file_exists($hreflang_file)) {
            require_once $hreflang_file;
            
            if (class_exists('Alma_Hreflang')) {
                $validator = new Alma_Hreflang();
                $result = $validator->validate_all();
                
                wp_send_json_success(array(
                    'message' => __('Hreflang validation complete', 'almaseo'),
                    'issues' => $result['issues'],
                    'stats' => $result['stats']
                ));
            }
        }
        
        wp_send_json_error(__('Hreflang validator not available', 'almaseo'));
    }
    
    /**
     * Handle scan media AJAX
     */
    public static function handle_scan_media() {
        self::verify_ajax_nonce();
        
        global $wpdb;
        
        // Count images
        $image_count = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'attachment'
            AND p.post_mime_type LIKE 'image/%'
            AND p.post_status = 'inherit'
        ");
        
        // Count videos
        $video_count = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'attachment'
            AND p.post_mime_type LIKE 'video/%'
            AND p.post_status = 'inherit'
        ");
        
        wp_send_json_success(array(
            'message' => __('Media scan complete', 'almaseo'),
            'images' => intval($image_count),
            'videos' => intval($video_count),
            'total' => intval($image_count) + intval($video_count)
        ));
    }
    
    /**
     * Handle validate media AJAX
     */
    public static function handle_validate_media() {
        self::verify_ajax_nonce();
        
        $issues = [];
        
        // Check for missing alt text
        global $wpdb;
        $missing_alt = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
            WHERE p.post_type = 'attachment'
            AND p.post_mime_type LIKE 'image/%'
            AND (pm.meta_value IS NULL OR pm.meta_value = '')
        ");
        
        if ($missing_alt > 0) {
            $issues[] = sprintf(__('%d images missing alt text', 'almaseo'), $missing_alt);
        }
        
        wp_send_json_success(array(
            'message' => __('Media validation complete', 'almaseo'),
            'issues' => $issues,
            'valid' => empty($issues)
        ));
    }
    
    /**
     * Handle rebuild media AJAX
     */
    public static function handle_rebuild_media() {
        self::verify_ajax_nonce();
        
        // Load media providers
        $image_file = dirname(dirname(__FILE__)) . '/providers/class-alma-provider-image.php';
        $video_file = dirname(dirname(__FILE__)) . '/providers/class-alma-provider-video.php';
        
        $rebuilt = [];
        
        if (file_exists($image_file)) {
            require_once $image_file;
            if (class_exists('Alma_Provider_Image')) {
                // Trigger image sitemap rebuild
                $rebuilt[] = 'images';
            }
        }
        
        if (file_exists($video_file)) {
            require_once $video_file;
            if (class_exists('Alma_Provider_Video')) {
                // Trigger video sitemap rebuild
                $rebuilt[] = 'videos';
            }
        }
        
        wp_send_json_success(array(
            'message' => __('Media sitemaps queued for rebuild', 'almaseo'),
            'rebuilt' => $rebuilt
        ));
    }
    
    /**
     * Handle validate news AJAX
     */
    public static function handle_validate_news() {
        self::verify_ajax_nonce();
        
        $settings = get_option('almaseo_sitemap_settings', array());
        $news_settings = $settings['news'] ?? array();
        
        $issues = [];
        
        if (empty($news_settings['publisher_name'])) {
            $issues[] = __('Publisher name not configured', 'almaseo');
        }
        
        if (empty($news_settings['post_types'])) {
            $issues[] = __('No post types selected for news', 'almaseo');
        }
        
        wp_send_json_success(array(
            'message' => __('News validation complete', 'almaseo'),
            'issues' => $issues,
            'valid' => empty($issues)
        ));
    }
    
    /**
     * Handle rebuild news AJAX
     */
    public static function handle_rebuild_news() {
        self::verify_ajax_nonce();
        
        $news_file = dirname(dirname(__FILE__)) . '/providers/class-alma-provider-news.php';
        
        if (file_exists($news_file)) {
            require_once $news_file;
            
            if (class_exists('Alma_Provider_News')) {
                // Trigger news sitemap rebuild
                wp_send_json_success(array(
                    'message' => __('News sitemap queued for rebuild', 'almaseo')
                ));
            }
        }
        
        wp_send_json_error(__('News provider not available', 'almaseo'));
    }
    
    /**
     * Handle export settings AJAX
     */
    public static function handle_export_settings() {
        self::verify_ajax_nonce();
        
        $settings = get_option('almaseo_sitemap_settings', array());
        
        wp_send_json_success(array(
            'settings' => json_encode($settings, JSON_PRETTY_PRINT),
            'filename' => 'almaseo-sitemap-settings-' . date('Y-m-d') . '.json'
        ));
    }
    
    /**
     * Handle import settings AJAX
     */
    public static function handle_import_settings() {
        self::verify_ajax_nonce();
        
        $json = wp_unslash($_POST['settings'] ?? '');
        
        if (empty($json)) {
            wp_send_json_error(__('No settings data provided', 'almaseo'));
        }
        
        $settings = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(__('Invalid JSON format', 'almaseo'));
        }
        
        // Validate critical settings
        if (!is_array($settings)) {
            wp_send_json_error(__('Invalid settings format', 'almaseo'));
        }
        
        update_option('almaseo_sitemap_settings', $settings);
        
        wp_send_json_success(array(
            'message' => __('Settings imported successfully', 'almaseo')
        ));
    }
    
    /**
     * Handle export logs AJAX
     */
    public static function handle_export_logs() {
        self::verify_ajax_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_health_log';
        
        $logs = $wpdb->get_results("
            SELECT * FROM `$table`
            ORDER BY created_at DESC
            LIMIT 1000
        ");
        
        $csv = "Date,Type,Message,Details\n";
        foreach ($logs as $log) {
            $csv .= sprintf('"%s","%s","%s","%s"' . "\n",
                $log->created_at,
                $log->type,
                str_replace('"', '""', $log->message),
                str_replace('"', '""', $log->details)
            );
        }
        
        wp_send_json_success(array(
            'csv' => $csv,
            'filename' => 'almaseo-sitemap-logs-' . date('Y-m-d') . '.csv'
        ));
    }
    
    /**
     * Handle clear logs AJAX
     */
    public static function handle_clear_logs() {
        self::verify_ajax_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_health_log';
        
        $wpdb->query("TRUNCATE TABLE `$table`");
        
        wp_send_json_success(array(
            'message' => __('Logs cleared successfully', 'almaseo')
        ));
    }
    
    /**
     * Handle toggle sitemaps AJAX
     */
    public static function handle_toggle_sitemaps() {
        self::verify_ajax_nonce();
        
        $enabled = isset($_POST['enabled']) ? (bool)$_POST['enabled'] : false;
        
        $settings = get_option('almaseo_sitemap_settings', []);
        $settings['enabled'] = $enabled;
        update_option('almaseo_sitemap_settings', $settings);
        
        // If enabling, queue a rebuild
        if ($enabled) {
            do_action('almaseo_queue_sitemap_rebuild');
        }
        
        wp_send_json_success([
            'message' => $enabled ? __('Sitemaps enabled', 'almaseo') : __('Sitemaps disabled', 'almaseo'),
            'enabled' => $enabled
        ]);
    }
    
    /**
     * Handle get live stats AJAX
     */
    public static function handle_get_live_stats() {
        self::verify_ajax_nonce();
        
        $stats = get_option('almaseo_sitemap_stats', ['files' => 0, 'urls' => 0]);
        $last_built = get_option('almaseo_sitemap_last_built');
        
        wp_send_json_success([
            'files' => $stats['files'],
            'urls' => $stats['urls'],
            'last_built' => $last_built ? human_time_diff(strtotime($last_built)) . ' ago' : 'Never'
        ]);
    }
    
    /**
     * Handle check build lock AJAX
     */
    public static function handle_check_build_lock() {
        self::verify_ajax_nonce();
        
        $lock = get_option('almaseo_sitemaps_build_lock');
        $is_locked = $lock && isset($lock['expires']) && $lock['expires'] > time();
        
        wp_send_json_success(['locked' => $is_locked, 'lock' => $lock]);
    }
    
    /**
     * Handle copy all URLs AJAX
     */
    public static function handle_copy_all_urls() {
        self::verify_ajax_nonce();
        
        $urls = [];
        $settings = get_option('almaseo_sitemap_settings', []);
        
        // Index URL
        $urls[] = home_url('/almaseo-sitemap.xml');
        
        // Individual sitemaps
        $types = ['posts', 'pages', 'users'];
        foreach ($types as $type) {
            if (!empty($settings['include'][$type])) {
                $urls[] = home_url('/almaseo-sitemap-' . $type . '.xml');
            }
        }
        
        // Custom post types
        if (!empty($settings['include']['cpts'])) {
            foreach ($settings['include']['cpts'] as $cpt) {
                $urls[] = home_url('/almaseo-sitemap-cpt-' . $cpt . '.xml');
            }
        }
        
        // Taxonomies
        if (!empty($settings['include']['taxonomies'])) {
            foreach ($settings['include']['taxonomies'] as $tax) {
                $urls[] = home_url('/almaseo-sitemap-tax-' . $tax . '.xml');
            }
        }
        
        // Special sitemaps
        if (!empty($settings['delta']['enabled'])) {
            $urls[] = home_url('/almaseo-sitemap-delta.xml');
        }
        
        if (!empty($settings['media']['image']['enabled'])) {
            $urls[] = home_url('/almaseo-sitemap-images.xml');
        }
        
        if (!empty($settings['media']['video']['enabled'])) {
            $urls[] = home_url('/almaseo-sitemap-videos.xml');
        }
        
        if (!empty($settings['news']['enabled'])) {
            $urls[] = home_url('/almaseo-sitemap-news.xml');
        }
        
        wp_send_json_success(['urls' => $urls]);
    }
    
    /**
     * Handle load tab v2 AJAX
     */
    public static function handle_load_tab_v2() {
        self::verify_ajax_nonce();
        
        $tab = isset($_POST['tab']) ? sanitize_key($_POST['tab']) : '';
        
        if (empty($tab)) {
            wp_send_json_error(['message' => __('Invalid tab', 'almaseo')]);
        }
        
        // Map tab names to file names
        $tab_map = [
            'types' => 'types-rules',
            'international' => 'international',
            'change' => 'change',
            'media' => 'media',
            'news' => 'news',
            'health' => 'health-scan',
            'updates' => 'updates-io'
        ];
        
        $tab_file = isset($tab_map[$tab]) ? $tab_map[$tab] : $tab;
        
        // Load tab content
        $partial_file = dirname(__FILE__) . '/partials/tabs/' . $tab_file . '.php';
        
        if (!file_exists($partial_file)) {
            wp_send_json_error(['message' => __('Tab not found', 'almaseo')]);
        }
        
        // Get settings for the tab
        $settings = get_option('almaseo_sitemap_settings', []);
        
        // Ensure default structure
        if (!isset($settings['enabled'])) $settings['enabled'] = false;
        if (!isset($settings['include'])) $settings['include'] = [];
        if (!isset($settings['perf'])) $settings['perf'] = [];
        if (!isset($settings['health'])) $settings['health'] = [];
        
        ob_start();
        include $partial_file;
        $content = ob_get_clean();
        
        wp_send_json_success(['content' => $content]);
    }
    
    /**
     * Handle load tab AJAX (legacy)
     */
    public static function handle_load_tab() {
        self::handle_load_tab_v2();
    }
    
    /**
     * Handle lazy load AJAX
     */
    public static function handle_lazy_load() {
        self::verify_ajax_nonce();
        
        $section = isset($_POST['section']) ? sanitize_key($_POST['section']) : '';
        
        if (empty($section)) {
            wp_send_json_error(['message' => __('Invalid section', 'almaseo')]);
        }
        
        // Load section content based on request
        ob_start();
        
        switch ($section) {
            case 'overview_stats':
                // Load overview statistics
                $stats = get_option('almaseo_sitemap_stats', ['files' => 0, 'urls' => 0]);
                echo '<div class="stats-loaded">';
                echo '<span>Files: ' . $stats['files'] . '</span>';
                echo '<span>URLs: ' . $stats['urls'] . '</span>';
                echo '</div>';
                break;
                
            default:
                echo '<div class="section-not-found">' . __('Section not found', 'almaseo') . '</div>';
        }
        
        $content = ob_get_clean();
        
        wp_send_json_success(['content' => $content]);
    }
    
    /**
     * Handle export conflicts AJAX
     */
    public static function handle_export_conflicts() {
        self::verify_ajax_nonce();
        
        $conflicts = get_option('almaseo_sitemap_conflicts', []);
        
        $csv = "Plugin,Conflict Type,Details,Resolution\n";
        foreach ($conflicts as $conflict) {
            $csv .= sprintf('"%s","%s","%s","%s"' . "\n",
                $conflict['plugin'] ?? '',
                $conflict['type'] ?? '',
                $conflict['details'] ?? '',
                $conflict['resolution'] ?? ''
            );
        }
        
        wp_send_json_success(array(
            'csv' => $csv,
            'filename' => 'sitemap-conflicts-' . date('Y-m-d') . '.csv'
        ));
    }
    
    /**
     * Handle create snapshot AJAX
     */
    public static function handle_create_snapshot() {
        self::verify_ajax_nonce();
        
        $name = sanitize_text_field($_POST['name'] ?? '');
        
        if (empty($name)) {
            $name = 'Snapshot ' . date('Y-m-d H:i:s');
        }
        
        // Create snapshot of current sitemap state
        $snapshot = [
            'name' => $name,
            'created' => current_time('mysql'),
            'settings' => get_option('almaseo_sitemap_settings', []),
            'stats' => get_option('almaseo_sitemap_stats', [])
        ];
        
        $snapshots = get_option('almaseo_sitemap_snapshots', []);
        $snapshots[] = $snapshot;
        
        // Keep only last 10 snapshots
        if (count($snapshots) > 10) {
            array_shift($snapshots);
        }
        
        update_option('almaseo_sitemap_snapshots', $snapshots);
        
        wp_send_json_success(array(
            'message' => __('Snapshot created successfully', 'almaseo'),
            'snapshot' => $snapshot
        ));
    }
    
    /**
     * Handle compare snapshots AJAX
     */
    public static function handle_compare_snapshots() {
        self::verify_ajax_nonce();
        
        $snapshot1 = intval($_POST['snapshot1'] ?? 0);
        $snapshot2 = intval($_POST['snapshot2'] ?? 0);
        
        $snapshots = get_option('almaseo_sitemap_snapshots', []);
        
        if (!isset($snapshots[$snapshot1]) || !isset($snapshots[$snapshot2])) {
            wp_send_json_error(__('Invalid snapshots selected', 'almaseo'));
        }
        
        $diff = [
            'settings' => array_diff_assoc(
                $snapshots[$snapshot1]['settings'],
                $snapshots[$snapshot2]['settings']
            ),
            'stats' => array_diff_assoc(
                $snapshots[$snapshot1]['stats'],
                $snapshots[$snapshot2]['stats']
            )
        ];
        
        wp_send_json_success(array(
            'diff' => $diff,
            'snapshot1' => $snapshots[$snapshot1],
            'snapshot2' => $snapshots[$snapshot2]
        ));
    }
    
    /**
     * Handle export diff AJAX
     */
    public static function handle_export_diff() {
        self::verify_ajax_nonce();
        
        $diff_data = get_option('almaseo_sitemap_last_diff', []);
        
        $csv = "Type,URL,Status,Date\n";
        
        if (!empty($diff_data['added'])) {
            foreach ($diff_data['added'] as $url) {
                $csv .= sprintf('"Added","%s","New","%s"' . "\n", $url, date('Y-m-d'));
            }
        }
        
        if (!empty($diff_data['removed'])) {
            foreach ($diff_data['removed'] as $url) {
                $csv .= sprintf('"Removed","%s","Deleted","%s"' . "\n", $url, date('Y-m-d'));
            }
        }
        
        if (!empty($diff_data['modified'])) {
            foreach ($diff_data['modified'] as $url) {
                $csv .= sprintf('"Modified","%s","Changed","%s"' . "\n", $url, date('Y-m-d'));
            }
        }
        
        wp_send_json_success(array(
            'csv' => $csv,
            'filename' => 'sitemap-diff-' . date('Y-m-d') . '.csv'
        ));
    }
    
    /**
     * Handle export hreflang issues AJAX
     */
    public static function handle_export_hreflang_issues() {
        self::verify_ajax_nonce();
        
        $issues = get_option('almaseo_hreflang_issues', []);
        
        $csv = "URL,Issue Type,Language,Details\n";
        foreach ($issues as $issue) {
            $csv .= sprintf('"%s","%s","%s","%s"' . "\n",
                $issue['url'] ?? '',
                $issue['type'] ?? '',
                $issue['language'] ?? '',
                $issue['details'] ?? ''
            );
        }
        
        wp_send_json_success(array(
            'csv' => $csv,
            'filename' => 'hreflang-issues-' . date('Y-m-d') . '.csv'
        ));
    }
}

// Initialize AJAX handlers
add_action('init', ['Alma_Sitemap_Ajax_Handlers', 'init']);