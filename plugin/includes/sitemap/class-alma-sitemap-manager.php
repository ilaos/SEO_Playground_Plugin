<?php
/**
 * AlmaSEO Sitemap Manager
 * 
 * Main controller for sitemap functionality
 * 
 * @package AlmaSEO
 * @since 4.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Sitemap_Manager {
    
    /**
     * Singleton instance
     */
    private static $instance = null;
    
    /**
     * Registered providers
     */
    private $providers = array();
    
    /**
     * Settings
     */
    private $settings = array();
    
    /**
     * Responder instance
     */
    private $responder = null;
    
    /**
     * Get singleton instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->load_settings();
        $this->load_dependencies();
        $this->init();
    }
    
    /**
     * Load settings with defaults
     */
    private function load_settings() {
        $defaults = array(
            'enabled' => true,
            'takeover' => false,
            'include' => array(
                'posts' => true,
                'pages' => true,
                'cpts' => 'all',
                'tax' => array(
                    'category' => true,
                    'post_tag' => true
                ),
                'users' => false
            ),
            'links_per_sitemap' => 1000,
            'perf' => array(
                'storage_mode' => 'static',
                'gzip' => true
            ),
            'health' => array(
                'last_build_stats' => array(
                    'started' => 0,
                    'finished' => 0,
                    'duration_ms' => 0,
                    'files' => 0,
                    'urls' => 0,
                    'by_provider' => array()
                ),
                'delta_submit' => array(
                    'time' => 0,
                    'count' => 0,
                    'success' => false
                )
            ),
            'delta' => array(
                'enabled' => true,
                'max_urls' => 500,
                'retention_days' => 14,
                'min_ping_interval' => 900
            ),
            'hreflang' => array(
                'enabled' => false,
                'source' => 'auto',
                'default' => '',
                'x_default_url' => '',
                'map' => array(),
                'locales' => array()
            ),
            'media' => array(
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
            ),
            'news' => array(
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
            )
        );
        
        $saved = get_option('almaseo_sitemap_settings', array());
        
        // Deep merge for nested arrays
        if (isset($saved['health'])) {
            $defaults['health'] = array_merge($defaults['health'], $saved['health']);
            unset($saved['health']);
        }
        
        $this->settings = wp_parse_args($saved, $defaults);
    }
    
    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Load the autoloader if not already loaded
        if (!class_exists('Alma_Sitemap_Autoloader')) {
            require_once __DIR__ . '/class-sitemap-autoloader.php';
        }
        
        // The autoloader will handle loading classes as needed
        // For WP-CLI, ensure the CLI class is available
        if (defined('WP_CLI') && WP_CLI) {
            Alma_Sitemap_Autoloader::load_class('Alma_Sitemap_CLI');
        }
        
        // Load all providers to ensure they're available for get_providers()
        // This is more efficient than 10 individual require_once statements
        Alma_Sitemap_Autoloader::load_all_providers();
    }
    
    /**
     * Initialize
     */
    private function init() {
        // Load admin page if in admin
        if (is_admin()) {
            require_once __DIR__ . '/admin/class-sitemap-admin-page.php';
        }
        
        if (!$this->settings['enabled']) {
            return;
        }
        
        // Initialize responder for routing
        $this->responder = new Alma_Sitemap_Responder($this);
        
        // Register providers based on settings
        $this->register_providers();
        
        // Hook into init for rewrite rules
        add_action('init', array($this, 'add_rewrite_rules'), 1);
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this->responder, 'handle_request'), 1);
        
        // Phase 4: Cron hooks for static rebuilds
        add_action('almaseo_sitemaps_rebuild', array($this, 'cron_rebuild'));
        
        // Schedule cron if not already scheduled
        if (!wp_next_scheduled('almaseo_sitemaps_rebuild')) {
            wp_schedule_event(time(), 'daily', 'almaseo_sitemaps_rebuild');
        }
        
        // Phase 5D: Hourly news refresh
        add_action('almaseo_news_refresh', array($this, 'cron_news_refresh'));
        if (!wp_next_scheduled('almaseo_news_refresh')) {
            wp_schedule_event(time(), 'hourly', 'almaseo_news_refresh');
        }
        
        // Hook for post updates
        add_action('save_post', array('Alma_Provider_News', 'maybe_rebuild_on_post_update'), 10, 2);
        
        // Phase 6: Initialize components
        Alma_HTML_Sitemap::init();
        Alma_Robots_Integration::init();
        
        // Add developer hooks
        $this->add_developer_hooks();
    }
    
    /**
     * Register providers based on settings
     */
    private function register_providers() {
        $include = $this->settings['include'];
        
        // Posts provider
        if (!empty($include['posts'])) {
            $this->providers['posts'] = new Alma_Provider_Posts($this->settings);
        }
        
        // Pages provider
        if (!empty($include['pages'])) {
            $this->providers['pages'] = new Alma_Provider_Pages($this->settings);
        }
        
        // CPTs provider
        if ($include['cpts'] === 'all' || !empty($include['cpts'])) {
            $this->providers['cpts'] = new Alma_Provider_CPTs($this->settings);
        }
        
        // Taxonomies provider
        if (!empty($include['tax'])) {
            $this->providers['tax'] = new Alma_Provider_Tax($this->settings);
        }
        
        // Users provider
        if (!empty($include['users'])) {
            $this->providers['users'] = new Alma_Provider_Users($this->settings);
        }
        
        // Additional URLs provider (always active if URLs exist)
        $extra_provider = new Alma_Provider_Extra($this->settings);
        if ($extra_provider->get_max_pages() > 0) {
            $this->providers['additional'] = $extra_provider;
        }
        
        // Delta provider (Phase 5A)
        if (!empty($this->settings['delta']['enabled'])) {
            $this->providers['delta'] = new Alma_Provider_Delta($this->settings);
        }
        
        // Image provider (Phase 5C)
        if (!empty($this->settings['media']['image']['enabled'])) {
            $this->providers['image'] = new Alma_Provider_Image($this->settings);
        }
        
        // Video provider (Phase 5C)
        if (!empty($this->settings['media']['video']['enabled'])) {
            $this->providers['video'] = new Alma_Provider_Video($this->settings);
        }
        
        // News provider (Phase 5D)
        if (!empty($this->settings['news']['enabled'])) {
            $this->providers['news'] = new Alma_Provider_News($this->settings);
        }
    }
    
    /**
     * Add rewrite rules
     */
    public function add_rewrite_rules() {
        // Main sitemap index
        add_rewrite_rule(
            '^almaseo-sitemap\.xml$',
            'index.php?almaseo_sitemap=index',
            'top'
        );
        
        // Provider sitemaps with pagination
        add_rewrite_rule(
            '^almaseo-sitemap-([a-z\-]+)-([0-9]+)\.xml$',
            'index.php?almaseo_sitemap=$matches[1]&sitemap_page=$matches[2]',
            'top'
        );
        
        // Delta sitemap (can have pagination)
        add_rewrite_rule(
            '^almaseo-sitemap-delta\.xml$',
            'index.php?almaseo_sitemap=delta',
            'top'
        );
    }
    
    /**
     * Add query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'almaseo_sitemap';
        $vars[] = 'sitemap_page';
        return $vars;
    }
    
    /**
     * Get all providers
     */
    public function get_providers() {
        return $this->providers;
    }
    
    /**
     * Get specific provider
     */
    public function get_provider($name) {
        return isset($this->providers[$name]) ? $this->providers[$name] : null;
    }
    
    /**
     * Get settings
     */
    public function get_settings() {
        return $this->settings;
    }
    
    /**
     * Get links per sitemap
     */
    public function get_links_per_sitemap() {
        return absint($this->settings['links_per_sitemap']);
    }
    
    /**
     * Activation hook
     */
    public static function activate() {
        $instance = self::get_instance();
        $instance->add_rewrite_rules();
        flush_rewrite_rules();
        
        // Create additional URLs table
        if (class_exists('Alma_Additional_URLs_Storage')) {
            Alma_Additional_URLs_Storage::create_table();
        }
    }
    
    /**
     * Deactivation hook
     */
    public static function deactivate() {
        flush_rewrite_rules();
        
        // Clear cron
        wp_clear_scheduled_hook('almaseo_sitemaps_rebuild');
        wp_clear_scheduled_hook('almaseo_news_refresh');
    }
    
    /**
     * Cron rebuild handler
     */
    public function cron_rebuild() {
        // Only rebuild if in static mode
        if ($this->settings['perf']['storage_mode'] !== 'static') {
            return;
        }
        
        $writer = new Alma_Sitemap_Writer();
        
        // Check if already building
        if ($writer->is_locked()) {
            return;
        }
        
        // Start build
        $result = $writer->start_build();
        if (is_wp_error($result)) {
            error_log('AlmaSEO Sitemap rebuild failed: ' . $result->get_error_message());
            return;
        }
        
        $sitemaps = array();
        
        // Generate for each provider
        foreach ($this->providers as $name => $provider) {
            $provider_class = get_class($provider);
            $urls = $writer->generate_with_seek($provider_class, $name);
            
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
        }
        
        // Write index
        $writer->write_index($sitemaps);
        
        // Finalize
        $stats = $writer->finalize_build();
        
        // Log if debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                'AlmaSEO Sitemap rebuild complete: %d files, %d URLs in %dms',
                $stats['files'],
                $stats['urls'],
                $stats['duration_ms']
            ));
        }
    }
    
    /**
     * Get storage mode
     */
    public function get_storage_mode() {
        return $this->settings['perf']['storage_mode'];
    }
    
    /**
     * Is gzip enabled
     */
    public function is_gzip_enabled() {
        return $this->settings['perf']['gzip'];
    }
    
    /**
     * Add developer hooks and filters
     */
    private function add_developer_hooks() {
        /**
         * Filter whether to include a URL in sitemaps
         * 
         * @since 4.12.0
         * @param bool $include Whether to include the URL
         * @param string $url The URL being considered
         * @param string $type The type of URL (post, page, term, user)
         */
        add_filter('almaseo_sitemaps_include_url', function($include, $url, $type) {
            return $include;
        }, 10, 3);
        
        /**
         * Filter provider arguments
         * 
         * @since 4.12.0
         * @param array $args Provider arguments
         * @param string $provider Provider name
         */
        add_filter('almaseo_sitemaps_provider_args', function($args, $provider) {
            return $args;
        }, 10, 2);
        
        /**
         * Filter whether delta should track a change
         * 
         * @since 4.12.0
         * @param bool $track Whether to track
         * @param string $url The URL that changed
         * @param string $reason The reason for change
         */
        add_filter('almaseo_delta_should_track', function($track, $url, $reason) {
            return $track;
        }, 10, 3);
        
        /**
         * Filter hreflang locales
         * 
         * @since 4.12.0
         * @param array $locales Array of locale => hreflang mappings
         */
        add_filter('almaseo_hreflang_locales', function($locales) {
            return $locales;
        });
    }
    
    /**
     * Check capabilities for management
     * 
     * @return bool
     */
    public static function can_manage() {
        // Multisite network admin check
        if (is_multisite() && is_network_admin()) {
            return current_user_can('manage_network_options');
        }
        
        // Regular site admin check
        return current_user_can('manage_options');
    }
    
    /**
     * Get network settings (multisite)
     * 
     * @return array
     */
    public static function get_network_settings() {
        if (!is_multisite()) {
            return array();
        }
        
        return get_site_option('almaseo_network_sitemap_settings', array(
            'allow_per_site' => true,
            'force_settings' => false,
            'default_settings' => array()
        ));
    }
    
    /**
     * Cron news refresh handler (Phase 5D)
     */
    public function cron_news_refresh() {
        // Only rebuild if in static mode and news is enabled
        if ($this->settings['perf']['storage_mode'] !== 'static') {
            return;
        }
        
        if (empty($this->settings['news']['enabled'])) {
            return;
        }
        
        // Check if rebuild is needed
        $needs_rebuild = get_option('almaseo_news_needs_rebuild', false);
        $last_build = get_option('almaseo_news_last_build', 0);
        $age_hours = (time() - $last_build) / 3600;
        
        // Rebuild if flagged or if it's been over an hour
        if (!$needs_rebuild && $age_hours < 1) {
            return;
        }
        
        $writer = new Alma_Sitemap_Writer();
        
        // Check if already building
        if ($writer->is_locked()) {
            return;
        }
        
        // Start build
        $result = $writer->start_build();
        if (is_wp_error($result)) {
            error_log('AlmaSEO News rebuild failed: ' . $result->get_error_message());
            return;
        }
        
        // Generate news sitemap
        $news_provider = $this->get_provider('news');
        if ($news_provider) {
            $urls = $writer->generate_with_seek('Alma_Provider_News', 'news');
            
            // Update last build time
            update_option('almaseo_news_last_build', time(), false);
            delete_option('almaseo_news_needs_rebuild');
        }
        
        // Finalize
        $stats = $writer->finalize_build();
        
        // Log the refresh
        Alma_Health_Log::log_news_refresh($urls, $stats['duration_ms']);
    }
}