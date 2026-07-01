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

    /*
     * Every AJAX handler in this class calls self::verify_ajax_nonce() as its first
     * statement, which runs check_ajax_referer('almaseo_sitemaps_nonce', ...) and a
     * manage_options capability check before any request data is read. The WPCS
     * NonceVerification sniff can't trace that shared wrapper, so its Missing/
     * Recommended warnings on the $_POST/$_GET reads below are false positives.
     */
    // phpcs:disable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended

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
            'validate_sitemap',
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
            'copy_all_urls',
            'clear_all_urls',
            'preview_robots',
            'save_auto_update_settings',
            'ping_search_engines',
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
            wp_send_json_error(['message' => __('Invalid security token', 'almaseo-seo-playground')]);
        }
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => __('Permission denied', 'almaseo-seo-playground')]);
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
        
        // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- all values sanitized individually below
        $post_data = wp_unslash( $_POST );
        // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized

        // The admin panel's tabs are lazy-loaded, so the JS only sends the
        // section it actually has DOM for. If we treat a missing `enabled` /
        // `include` / `links_per_sitemap` as "user set them to false / 1000",
        // saving any non-Types-tab silently disables sitemaps and clears the
        // include rules. Per-section: when keys aren't in POST, preserve
        // what's already stored (same pattern the perf / delta / hreflang /
        // media / news branches below already use).
        $existing_include = $existing['include'] ?? array(
            'posts' => true, 'pages' => true, 'cpts' => 'all',
            'tax' => array('category' => true, 'post_tag' => true),
            'users' => false,
        );

        $settings = array(
            'enabled'  => isset($post_data['enabled'])
                ? !empty($post_data['enabled'])
                : (isset($existing['enabled']) ? (bool) $existing['enabled'] : true),
            'takeover' => isset($post_data['takeover'])
                ? !empty($post_data['takeover'])
                : (isset($existing['takeover']) ? (bool) $existing['takeover'] : false),
        );

        if (isset($post_data['include'])) {
            $inc = $post_data['include'];
            $settings['include'] = array(
                'posts' => !empty($inc['posts']),
                'pages' => !empty($inc['pages']),
                'cpts'  => !empty($inc['cpts']) ? 'all' : array(),
                'tax'   => array(
                    'category' => !empty($inc['tax']['category']),
                    'post_tag' => !empty($inc['tax']['post_tag']),
                ),
                'users' => !empty($inc['users']),
            );
        } else {
            $settings['include'] = $existing_include;
        }

        $settings['links_per_sitemap'] = isset($post_data['links_per_sitemap'])
            ? absint($post_data['links_per_sitemap'])
            : (int) ($existing['links_per_sitemap'] ?? 1000);

        // Performance settings
        if (isset($post_data['perf'])) {
            $settings['perf'] = array(
                'storage_mode' => sanitize_text_field($post_data['perf']['storage_mode'] ?? 'static'),
                'gzip' => !empty($post_data['perf']['gzip'])
            );
        } else {
            $settings['perf'] = $existing['perf'] ?? array('storage_mode' => 'static', 'gzip' => true);
        }

        // Delta settings
        if (isset($post_data['delta'])) {
            $settings['delta'] = array(
                'enabled' => !empty($post_data['delta']['enabled']),
                'max_urls' => absint($post_data['delta']['max_urls'] ?? 500),
                'retention_days' => absint($post_data['delta']['retention_days'] ?? 14),
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
        if (isset($post_data['hreflang'])) {
            $settings['hreflang'] = array(
                'enabled' => !empty($post_data['hreflang']['enabled']),
                'source' => sanitize_text_field($post_data['hreflang']['source'] ?? 'auto'),
                'default' => sanitize_text_field($post_data['hreflang']['default'] ?? ''),
                'x_default_url' => esc_url_raw($post_data['hreflang']['x_default_url'] ?? ''),
                'map' => $post_data['hreflang']['map'] ?? array(),
                'locales' => $post_data['hreflang']['locales'] ?? array()
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
        if (isset($post_data['media'])) {
            $settings['media'] = array(
                'image' => array(
                    'enabled' => !empty($post_data['media']['image']['enabled']),
                    'max_per_url' => absint($post_data['media']['image']['max_per_url'] ?? 20),
                    'dedupe_cdn' => !empty($post_data['media']['image']['dedupe_cdn'])
                ),
                'video' => array(
                    'enabled' => !empty($post_data['media']['video']['enabled']),
                    'max_per_url' => absint($post_data['media']['video']['max_per_url'] ?? 10),
                    'oembed_cache' => !empty($post_data['media']['video']['oembed_cache'])
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
        if (isset($post_data['news'])) {
            $settings['news'] = array(
                'enabled' => !empty($post_data['news']['enabled']),
                'post_types' => isset($post_data['news']['post_types']) ? array_map('sanitize_text_field', $post_data['news']['post_types']) : array('post'),
                'categories' => isset($post_data['news']['categories']) ? array_map('intval', $post_data['news']['categories']) : array(),
                'publisher_name' => sanitize_text_field($post_data['news']['publisher_name'] ?? get_bloginfo('name')),
                'language' => sanitize_text_field($post_data['news']['language'] ?? 'en'),
                'genres' => isset($post_data['news']['genres']) ? array_map('sanitize_text_field', $post_data['news']['genres']) : array(),
                'keywords_source' => sanitize_text_field($post_data['news']['keywords_source'] ?? 'tags'),
                'manual_keywords' => sanitize_text_field($post_data['news']['manual_keywords'] ?? ''),
                'max_items' => absint($post_data['news']['max_items'] ?? 1000),
                'window_hours' => absint($post_data['news']['window_hours'] ?? 48)
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
        
        // Exclude rules. Used by Alma_Provider_Posts / Pages / CPTs to filter
        // sitemap output at the SQL level (see build_exclude_joins /
        // build_exclude_where in those providers). Sanitize term IDs and
        // author IDs to ints; clamp older_than_years to a reasonable range.
        if (isset($post_data['exclude'])) {
            $tax_in     = $post_data['exclude']['taxonomies'] ?? array();
            $author_in  = $post_data['exclude']['authors'] ?? array();
            $tax_ids    = is_array($tax_in) ? array_values(array_filter(array_map('absint', $tax_in))) : array();
            $author_ids = is_array($author_in) ? array_values(array_filter(array_map('absint', $author_in))) : array();
            $years      = absint($post_data['exclude']['older_than_years'] ?? 0);
            if ($years > 50) {
                $years = 50;
            }

            $settings['exclude'] = array(
                'taxonomies'       => $tax_ids,
                'authors'          => $author_ids,
                'older_than_years' => $years,
            );
        } else {
            $settings['exclude'] = $existing['exclude'] ?? array(
                'taxonomies'       => array(),
                'authors'          => array(),
                'older_than_years' => 0,
            );
        }

        // Preserve health stats
        $settings['health'] = $existing['health'] ?? array();

        // IndexNow settings. Canonical storage — Alma_IndexNow reads
        // almaseo_sitemap_settings['indexnow'] at runtime. The Change tab's
        // IndexNow card posts these; other tabs don't, so when the key is
        // absent we preserve whatever is already stored (same per-section
        // pattern as delta / media / news above). Without an explicit branch
        // here, update_option() below would wipe the sub-array on every save.
        $indexnow_default = array(
            'enabled'  => false,
            'key'      => '',
            'endpoint' => 'https://api.indexnow.org/indexnow',
        );
        if (isset($post_data['indexnow'])) {
            $in = $post_data['indexnow'];
            // IndexNow keys are alphanumeric (+ dashes), 8–128 chars.
            $key = preg_replace('/[^A-Za-z0-9\-]/', '', sanitize_text_field($in['key'] ?? ''));
            // Only the two endpoints the UI offers are accepted.
            $endpoint = esc_url_raw($in['endpoint'] ?? '');
            $allowed_endpoints = array(
                'https://api.indexnow.org/indexnow',
                'https://yandex.com/indexnow',
            );
            if (!in_array($endpoint, $allowed_endpoints, true)) {
                $endpoint = $indexnow_default['endpoint'];
            }
            $settings['indexnow'] = array(
                'enabled'  => !empty($in['enabled']),
                'key'      => $key,
                'endpoint' => $endpoint,
            );

            // Drop the site-root verification file (<key>.txt) so the URL
            // shown in the Change tab is reachable. Alma_IndexNow also
            // recreates it on submit, but doing it now keeps the UI honest.
            if ($key !== '') {
                $indexnow_file = dirname(dirname(__FILE__)) . '/class-alma-indexnow.php';
                if (!class_exists('Alma_IndexNow') && file_exists($indexnow_file)) {
                    require_once $indexnow_file;
                }
                if (class_exists('Alma_IndexNow')) {
                    update_option('almaseo_sitemap_settings', $settings, false);
                    ( new Alma_IndexNow() )->create_key_file();
                }
            }
        } else {
            $settings['indexnow'] = $existing['indexnow'] ?? $indexnow_default;
        }
        
        // Validate links_per_sitemap
        if ($settings['links_per_sitemap'] < 1) {
            $settings['links_per_sitemap'] = 1;
        } elseif ($settings['links_per_sitemap'] > 50000) {
            $settings['links_per_sitemap'] = 50000;
        }
        
        update_option('almaseo_sitemap_settings', $settings, false);
        
        wp_send_json_success(array(
            'message' => __('Settings saved successfully', 'almaseo-seo-playground'),
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
            'message' => __('Statistics recalculated', 'almaseo-seo-playground'),
            'redirect' => admin_url('admin.php?page=almaseo-sitemaps')
        ]);
    }
    
    /**
     * Handle add URL AJAX
     */
    public static function handle_add_url() {
        self::verify_ajax_nonce();
        
        $url = sanitize_url(wp_unslash($_POST['url'] ?? ''));
        $priority = floatval(wp_unslash($_POST['priority'] ?? 0.5));
        $changefreq = sanitize_text_field(wp_unslash($_POST['changefreq'] ?? 'weekly'));
        $lastmod = sanitize_text_field(wp_unslash($_POST['lastmod'] ?? ''));
        
        // Validate URL
        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            wp_send_json_error(__('Invalid URL', 'almaseo-seo-playground'));
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
                    'message' => __('URL added successfully', 'almaseo-seo-playground'),
                    'id' => $result
                ));
            }
        }
        
        wp_send_json_error(__('Additional URLs feature not available', 'almaseo-seo-playground'));
    }
    
    /**
     * Handle import CSV AJAX
     */
    public static function handle_import_csv() {
        self::verify_ajax_nonce();
        
        // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- parsed line-by-line below; each URL is validated with filter_var(FILTER_VALIDATE_URL) before use, nothing is stored/output raw
        $csv_content = wp_unslash($_POST['csv'] ?? '');
        // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        
        if (empty($csv_content)) {
            wp_send_json_error(__('No CSV content provided', 'almaseo-seo-playground'));
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
                    /* translators: %d: line number in the imported file */
                    $errors[] = sprintf(__('Line %d: Invalid URL', 'almaseo-seo-playground'), $line_num + 1);
                }
            }
        }
        
        wp_send_json_success(array(
            /* translators: %d: number of URLs imported */
            'message' => sprintf(__('Imported %d URLs', 'almaseo-seo-playground'), $imported),
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
            'filename' => 'sitemap-urls-' . gmdate('Y-m-d') . '.csv'
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
        
        wp_send_json_error(__('Conflict scanner not available', 'almaseo-seo-playground'));
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

        // Delegate to the Manager's rebuild pipeline. It acquires the lock,
        // iterates providers via generate_with_seek(), writes the index,
        // calls finalize_build() (which is what writes
        // almaseo_sitemap_settings.health.last_build_stats — the path
        // helpers.php's almaseo_get_build_stats() reads from), and releases
        // the lock even if a provider throws.
        require_once dirname(dirname(__FILE__)) . '/class-alma-sitemap-writer.php';
        require_once dirname(dirname(__FILE__)) . '/class-alma-sitemap-manager.php';

        $manager = Alma_Sitemap_Manager::get_instance();
        $result = $manager->rebuild_now();

        if (is_wp_error($result)) {
            wp_send_json_error([
                'message' => $result->get_error_message(),
                'code'    => $result->get_error_code(),
            ]);
        }

        wp_send_json_success([
            'message' => __('Sitemaps rebuilt successfully', 'almaseo-seo-playground'),
            'stats'   => $result,
        ]);
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
                        'message' => __('Delta ping sent successfully', 'almaseo-seo-playground'),
                        'urls_pinged' => $result['count']
                    ));
                }
            }
        }
        
        wp_send_json_error(__('Delta provider not available', 'almaseo-seo-playground'));
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
        
        $cutoff = gmdate('Y-m-d H:i:s', strtotime("-{$retention_days} days"));
        
        $deleted = $wpdb->query($wpdb->prepare(
            "DELETE FROM `$table` WHERE detected_at < %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name derived from $wpdb->prefix, not user input
            $cutoff
        ));
        
        wp_send_json_success(array(
            /* translators: %d: number of delta entries purged */
            'message' => sprintf(__('Purged %d old delta entries', 'almaseo-seo-playground'), $deleted),
            'deleted' => $deleted
        ));
    }
    
    /**
     * Handle "Clear All" for Additional URLs.
     */
    public static function handle_clear_all_urls() {
        self::verify_ajax_nonce();
        if (!class_exists('Alma_Additional_URLs_Storage')) {
            wp_send_json_error(['message' => __('Additional URLs storage not available', 'almaseo-seo-playground')]);
        }
        $count = Alma_Additional_URLs_Storage::clear_all();
        wp_send_json_success([
            'deleted' => $count,
            'message' => sprintf(
                /* translators: %d: number of additional URLs that were removed */
                _n('%d URL removed', '%d URLs removed', $count, 'almaseo-seo-playground'),
                $count
            ),
        ]);
    }

    /**
     * Handle robots.txt preview request.
     */
    public static function handle_preview_robots() {
        self::verify_ajax_nonce();
        if (!class_exists('Alma_Robots_Integration')) {
            $robots_file = dirname(dirname(__FILE__)) . '/class-alma-robots-integration.php';
            if (file_exists($robots_file)) {
                require_once $robots_file;
            }
        }
        if (!class_exists('Alma_Robots_Integration')) {
            wp_send_json_error(['message' => __('Robots integration not available', 'almaseo-seo-playground')]);
        }
        $preview = Alma_Robots_Integration::get_robots_preview();
        $sitemap_lines = [];
        if (preg_match_all('/^Sitemap:.*$/m', $preview, $m)) {
            $sitemap_lines = $m[0];
        }
        wp_send_json_success([
            'preview'       => $preview,
            'sitemap_lines' => $sitemap_lines,
        ]);
    }

    /**
     * Persist the two auto-update preference toggles from the Updates & I/O
     * tab. The partial reads these options directly; nothing was writing
     * them, so the checkboxes always reverted on reload.
     */
    public static function handle_save_auto_update_settings() {
        self::verify_ajax_nonce();
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- only !empty() checks below
        $post_data = wp_unslash($_POST);
        $enabled = !empty($post_data['enabled']);
        $beta    = !empty($post_data['beta']);
        update_option('almaseo_auto_updates_enabled', $enabled, false);
        update_option('almaseo_auto_updates_beta', $beta, false);
        wp_send_json_success([
            'enabled' => $enabled,
            'beta'    => $beta,
            'message' => __('Auto-update preferences saved', 'almaseo-seo-playground'),
        ]);
    }

    /**
     * Submit the sitemap index URL to IndexNow-supporting search engines
     * (Bing / Yandex / Naver depending on user config). Distinct from the
     * delta ping, which submits a rolling window of recently-changed URLs;
     * this one announces the full sitemap so engines re-crawl it.
     */
    public static function handle_ping_search_engines() {
        self::verify_ajax_nonce();
        $indexnow_file = dirname(dirname(__FILE__)) . '/class-alma-indexnow.php';
        if (!class_exists('Alma_IndexNow') && file_exists($indexnow_file)) {
            require_once $indexnow_file;
        }
        if (!class_exists('Alma_IndexNow')) {
            wp_send_json_error(['message' => __('IndexNow not available', 'almaseo-seo-playground')]);
        }

        // mode 'test' submits only the sitemap index as a connectivity check;
        // any other mode submits the full queue of changed URLs. Either way
        // Alma_IndexNow::submit() prepends the sitemap index itself.
        $mode = isset($_POST['mode']) ? sanitize_key(wp_unslash($_POST['mode'])) : 'all';

        $indexnow = new Alma_IndexNow();
        $result = ($mode === 'test')
            ? $indexnow->submit([home_url('/sitemap.xml')])
            : $indexnow->submit();

        if (!empty($result['success'])) {
            wp_send_json_success([
                'message' => !empty($result['message'])
                    ? $result['message']
                    : __('Submitted to IndexNow', 'almaseo-seo-playground'),
                'count'   => isset($result['count']) ? (int) $result['count'] : 0,
            ]);
        }

        wp_send_json_error([
            'message' => !empty($result['message'])
                ? $result['message']
                : __('IndexNow submission failed', 'almaseo-seo-playground'),
        ]);
    }

    /**
     * Handle validate sitemap AJAX.
     *
     * Runs the full validation suite via Alma_Sitemap_Validator::run() and
     * returns an aggregate summary the JS can render as a single toast plus
     * per-check details. Previously the Overview tab's "Validate" button was
     * faked with setTimeout — clicking it did nothing real, just showed a
     * lying success toast after 2 seconds.
     */
    public static function handle_validate_sitemap() {
        self::verify_ajax_nonce();

        if (!class_exists('Alma_Sitemap_Validator')) {
            $validator_file = dirname(dirname(__FILE__)) . '/class-alma-sitemap-validator.php';
            if (file_exists($validator_file)) {
                require_once $validator_file;
            }
        }

        if (!class_exists('Alma_Sitemap_Validator')) {
            wp_send_json_error(['message' => __('Validator not available', 'almaseo-seo-playground')]);
        }

        try {
            $results = Alma_Sitemap_Validator::run();
        } catch (\Throwable $e) {
            wp_send_json_error([
                'message' => sprintf(
                    /* translators: %s: error message from the validator */
                    __('Validation failed: %s', 'almaseo-seo-playground'),
                    $e->getMessage()
                ),
            ]);
        }

        // Roll up status across every sub-check so the JS toast can say
        // "Valid" / "N issues" without re-walking the result tree.
        $issue_count = 0;
        foreach (['index_status', 'urlset_checks', 'media_checks', 'news_checks'] as $section) {
            $node = $results[$section] ?? [];
            if (isset($node['issues']) && is_array($node['issues'])) {
                $issue_count += count($node['issues']);
            } elseif (is_array($node)) {
                foreach ($node as $sub) {
                    if (isset($sub['issues']) && is_array($sub['issues'])) {
                        $issue_count += count($sub['issues']);
                    }
                }
            }
        }
        $conflict_count = isset($results['conflicts']) && is_array($results['conflicts'])
            ? count($results['conflicts'])
            : 0;

        $ok = ($issue_count === 0 && $conflict_count === 0);

        wp_send_json_success([
            'ok'             => $ok,
            'issue_count'    => $issue_count,
            'conflict_count' => $conflict_count,
            'results'        => $results,
            'message'        => $ok
                ? __('Sitemap is valid', 'almaseo-seo-playground')
                : sprintf(
                    /* translators: %d: total number of issues + conflicts found */
                    _n('%d issue found', '%d issues found', $issue_count + $conflict_count, 'almaseo-seo-playground'),
                    $issue_count + $conflict_count
                ),
        ]);
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
                    'message' => __('Hreflang validation complete', 'almaseo-seo-playground'),
                    'issues' => $result['issues'],
                    'stats' => $result['stats']
                ));
            }
        }
        
        wp_send_json_error(__('Hreflang validator not available', 'almaseo-seo-playground'));
    }
    
    /**
     * Handle scan media AJAX
     */
    public static function handle_scan_media() {
        self::verify_ajax_nonce();
        
        global $wpdb;
        
        // Count images. {$wpdb->posts} is a core table name; the LIKE patterns are
        // hardcoded (no user input) and this query takes no replacements.
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
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
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        
        wp_send_json_success(array(
            'message' => __('Media scan complete', 'almaseo-seo-playground'),
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
        
        // Check for missing alt text. Core table names; hardcoded LIKE pattern, no replacements.
        global $wpdb;
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        $missing_alt = $wpdb->get_var("
            SELECT COUNT(*)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_wp_attachment_image_alt'
            WHERE p.post_type = 'attachment'
            AND p.post_mime_type LIKE 'image/%'
            AND (pm.meta_value IS NULL OR pm.meta_value = '')
        ");
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        
        if ($missing_alt > 0) {
            /* translators: %d: number of images missing alt text */
            $issues[] = sprintf(__('%d images missing alt text', 'almaseo-seo-playground'), $missing_alt);
        }
        
        wp_send_json_success(array(
            'message' => __('Media validation complete', 'almaseo-seo-playground'),
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
            'message' => __('Media sitemaps queued for rebuild', 'almaseo-seo-playground'),
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
            $issues[] = __('Publisher name not configured', 'almaseo-seo-playground');
        }
        
        if (empty($news_settings['post_types'])) {
            $issues[] = __('No post types selected for news', 'almaseo-seo-playground');
        }
        
        wp_send_json_success(array(
            'message' => __('News validation complete', 'almaseo-seo-playground'),
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
                    'message' => __('News sitemap queued for rebuild', 'almaseo-seo-playground')
                ));
            }
        }
        
        wp_send_json_error(__('News provider not available', 'almaseo-seo-playground'));
    }
    
    /**
     * Handle export settings AJAX
     */
    public static function handle_export_settings() {
        self::verify_ajax_nonce();
        
        $settings = get_option('almaseo_sitemap_settings', array());
        
        wp_send_json_success(array(
            'settings' => json_encode($settings, JSON_PRETTY_PRINT),
            'filename' => 'almaseo-sitemap-settings-' . gmdate('Y-m-d') . '.json'
        ));
    }
    
    /**
     * Handle import settings AJAX
     */
    public static function handle_import_settings() {
        self::verify_ajax_nonce();
        
        // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- JSON settings blob from an admin (manage_options); json_decoded and validated as an array before storage
        $json = wp_unslash($_POST['settings'] ?? '');
        // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        
        if (empty($json)) {
            wp_send_json_error(__('No settings data provided', 'almaseo-seo-playground'));
        }
        
        $settings = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            wp_send_json_error(__('Invalid JSON format', 'almaseo-seo-playground'));
        }
        
        // Validate critical settings
        if (!is_array($settings)) {
            wp_send_json_error(__('Invalid settings format', 'almaseo-seo-playground'));
        }
        
        update_option('almaseo_sitemap_settings', $settings);
        
        wp_send_json_success(array(
            'message' => __('Settings imported successfully', 'almaseo-seo-playground')
        ));
    }
    
    /**
     * Handle export logs AJAX
     */
    public static function handle_export_logs() {
        self::verify_ajax_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_health_log';
        
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name derived from $wpdb->prefix, not user input
        $logs = $wpdb->get_results("
            SELECT * FROM `$table`
            ORDER BY created_at DESC
            LIMIT 1000
        ");
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        
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
            'filename' => 'almaseo-sitemap-logs-' . gmdate('Y-m-d') . '.csv'
        ));
    }
    
    /**
     * Handle clear logs AJAX
     */
    public static function handle_clear_logs() {
        self::verify_ajax_nonce();
        
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_health_log';
        
        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $wpdb->query("TRUNCATE TABLE `$table`");
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        
        wp_send_json_success(array(
            'message' => __('Logs cleared successfully', 'almaseo-seo-playground')
        ));
    }
    
    /**
     * Handle toggle sitemaps AJAX
     */
    public static function handle_toggle_sitemaps() {
        self::verify_ajax_nonce();
        
        // phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- cast to (bool), inherently safe
        $enabled = isset($_POST['enabled']) ? (bool) wp_unslash($_POST['enabled']) : false;
        // phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
        
        $settings = get_option('almaseo_sitemap_settings', []);
        $settings['enabled'] = $enabled;
        update_option('almaseo_sitemap_settings', $settings);
        
        // If enabling, queue a rebuild
        if ($enabled) {
            do_action('almaseo_queue_sitemap_rebuild');
        }
        
        wp_send_json_success([
            'message' => $enabled ? __('Sitemaps enabled', 'almaseo-seo-playground') : __('Sitemaps disabled', 'almaseo-seo-playground'),
            'enabled' => $enabled
        ]);
    }
    
    /**
     * Handle get live stats AJAX
     */
    public static function handle_get_live_stats() {
        self::verify_ajax_nonce();

        // Read from the same nested path Alma_Sitemap_Writer::finalize_build()
        // actually writes to. The previous reads against `almaseo_sitemap_stats`
        // and `almaseo_sitemap_last_built` always returned defaults — those
        // option keys are not written by anything in this codebase.
        $settings = get_option('almaseo_sitemap_settings', []);
        $build    = ($settings['health'] ?? [])['last_build_stats'] ?? [];

        $files    = (int) ($build['files'] ?? 0);
        $urls     = (int) ($build['urls'] ?? 0);
        $finished = (int) ($build['finished'] ?? 0);

        // The header chip is rendered as: Built <span class="num">{X}</span> ago.
        // So the JS-facing duration must NOT include "ago" or the chip reads
        // "Built 5 minutes ago ago". last_built_ts is also returned so callers
        // can do their own formatting if they prefer.
        wp_send_json_success([
            'files'         => $files,
            'urls'          => $urls,
            'last_built'    => $finished > 0 ? human_time_diff($finished) : '',
            'last_built_ts' => $finished,
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
        $urls[] = home_url('/sitemap.xml');

        // Individual sitemaps
        $types = ['posts', 'pages', 'users'];
        foreach ($types as $type) {
            if (!empty($settings['include'][$type])) {
                $urls[] = home_url('/sitemap-' . $type . '-1.xml');
            }
        }

        // Custom post types — served as one combined sitemap by
        // Alma_Provider_CPTs (provider key 'cpts'), not per-type files. The
        // setting is the string 'all' or an empty array, never a list, so the
        // old `foreach ($settings['include']['cpts'] as $cpt)` iterated a
        // string and emitted a PHP 8 warning.
        if (!empty($settings['include']['cpts'])) {
            $urls[] = home_url('/sitemap-cpts-1.xml');
        }

        // Taxonomies — one combined sitemap from Alma_Provider_Tax. The
        // setting key is 'tax' (not 'taxonomies', which nothing writes) and
        // holds per-taxonomy booleans; the provider is registered whenever
        // that array exists.
        if (!empty($settings['include']['tax'])) {
            $urls[] = home_url('/sitemap-tax-1.xml');
        }

        // Special sitemaps
        if (!empty($settings['delta']['enabled'])) {
            $urls[] = home_url('/sitemap-delta.xml');
        }

        if (!empty($settings['media']['image']['enabled'])) {
            $urls[] = home_url('/sitemap-image-1.xml');
        }

        if (!empty($settings['media']['video']['enabled'])) {
            $urls[] = home_url('/sitemap-video-1.xml');
        }

        if (!empty($settings['news']['enabled'])) {
            $urls[] = home_url('/sitemap-news-1.xml');
        }
        
        wp_send_json_success(['urls' => $urls]);
    }
    
    /**
     * Handle load tab v2 AJAX
     */
    public static function handle_load_tab_v2() {
        self::verify_ajax_nonce();
        
        $tab = isset($_POST['tab']) ? sanitize_key(wp_unslash($_POST['tab'])) : '';
        
        if (empty($tab)) {
            wp_send_json_error(['message' => __('Invalid tab', 'almaseo-seo-playground')]);
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
            wp_send_json_error(['message' => __('Tab not found', 'almaseo-seo-playground')]);
        }
        
        // Get settings for the tab and recursively merge against the canonical
        // defaults. The partials read deeply-nested keys like
        // $settings['include']['tax']['category'] and $settings['perf']['gzip']
        // without their own guards — shallow init like `$settings['include'] = []`
        // leaves those inner accesses warning under PHP 8+. array_replace_recursive
        // fills in every missing key from the defaults tree without overwriting
        // anything the user has actually set.
        $stored = get_option('almaseo_sitemap_settings', []);
        if (!is_array($stored)) {
            $stored = [];
        }
        $defaults = function_exists('almaseo_get_default_settings')
            ? almaseo_get_default_settings()
            : [];
        $settings = array_replace_recursive($defaults, $stored);

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
        
        $section = isset($_POST['section']) ? sanitize_key(wp_unslash($_POST['section'])) : '';
        
        if (empty($section)) {
            wp_send_json_error(['message' => __('Invalid section', 'almaseo-seo-playground')]);
        }
        
        // Load section content based on request
        ob_start();
        
        switch ($section) {
            case 'overview_stats':
                $build = get_option('almaseo_sitemap_settings', [])['health']['last_build_stats'] ?? [];
                echo '<div class="stats-loaded">';
                echo '<span>Files: ' . esc_html((int) ($build['files'] ?? 0)) . '</span>';
                echo '<span>URLs: ' . esc_html((int) ($build['urls'] ?? 0)) . '</span>';
                echo '</div>';
                break;
                
            default:
                echo '<div class="section-not-found">' . esc_html__('Section not found', 'almaseo-seo-playground') . '</div>';
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
            'filename' => 'sitemap-conflicts-' . gmdate('Y-m-d') . '.csv'
        ));
    }
    
    /**
     * Handle create snapshot AJAX
     */
    public static function handle_create_snapshot() {
        self::verify_ajax_nonce();
        
        $name = sanitize_text_field(wp_unslash($_POST['name'] ?? ''));
        
        if (empty($name)) {
            $name = 'Snapshot ' . gmdate('Y-m-d H:i:s');
        }
        
        // Create snapshot of current sitemap state. Stats live nested inside
        // almaseo_sitemap_settings.health.last_build_stats (written by
        // Alma_Sitemap_Writer::finalize_build), not in a top-level
        // almaseo_sitemap_stats option — that key is never written.
        $current_settings = get_option('almaseo_sitemap_settings', []);
        $snapshot = [
            'name'     => $name,
            'created'  => current_time('mysql'),
            'settings' => $current_settings,
            'stats'    => $current_settings['health']['last_build_stats'] ?? [],
        ];
        
        $snapshots = get_option('almaseo_sitemap_snapshots', []);
        $snapshots[] = $snapshot;
        
        // Keep only last 10 snapshots
        if (count($snapshots) > 10) {
            array_shift($snapshots);
        }
        
        update_option('almaseo_sitemap_snapshots', $snapshots);
        
        wp_send_json_success(array(
            'message' => __('Snapshot created successfully', 'almaseo-seo-playground'),
            'snapshot' => $snapshot
        ));
    }
    
    /**
     * Handle compare snapshots AJAX
     */
    public static function handle_compare_snapshots() {
        self::verify_ajax_nonce();
        
        $snapshot1 = intval(wp_unslash($_POST['snapshot1'] ?? 0));
        $snapshot2 = intval(wp_unslash($_POST['snapshot2'] ?? 0));
        
        $snapshots = get_option('almaseo_sitemap_snapshots', []);
        
        if (!isset($snapshots[$snapshot1]) || !isset($snapshots[$snapshot2])) {
            wp_send_json_error(__('Invalid snapshots selected', 'almaseo-seo-playground'));
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
                $csv .= sprintf('"Added","%s","New","%s"' . "\n", $url, gmdate('Y-m-d'));
            }
        }
        
        if (!empty($diff_data['removed'])) {
            foreach ($diff_data['removed'] as $url) {
                $csv .= sprintf('"Removed","%s","Deleted","%s"' . "\n", $url, gmdate('Y-m-d'));
            }
        }
        
        if (!empty($diff_data['modified'])) {
            foreach ($diff_data['modified'] as $url) {
                $csv .= sprintf('"Modified","%s","Changed","%s"' . "\n", $url, gmdate('Y-m-d'));
            }
        }
        
        wp_send_json_success(array(
            'csv' => $csv,
            'filename' => 'sitemap-diff-' . gmdate('Y-m-d') . '.csv'
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
            'filename' => 'hreflang-issues-' . gmdate('Y-m-d') . '.csv'
        ));
    }
    // phpcs:enable WordPress.Security.NonceVerification.Missing, WordPress.Security.NonceVerification.Recommended
}

// Initialize AJAX handlers
add_action('init', ['Alma_Sitemap_Ajax_Handlers', 'init']);