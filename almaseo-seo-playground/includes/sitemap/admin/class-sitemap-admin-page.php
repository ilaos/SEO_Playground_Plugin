<?php
/**
 * Sitemap Admin Page - Simplified
 * 
 * Main admin page controller for sitemap functionality
 * 
 * @package AlmaSEO
 * @since 5.5.1
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Sitemap_Admin_Page {
    
    /**
     * Constructor
     */
    public function __construct() {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        
        // Load AJAX handlers
        require_once __DIR__ . '/class-sitemap-ajax-handlers.php';
        
        // Load helper functions if not already loaded
        $helpers_file = dirname(__DIR__) . '/helpers.php';
        if (file_exists($helpers_file)) {
            require_once $helpers_file;
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'seo-playground',
            __('Sitemaps', 'almaseo-seo-playground'),
            __('Sitemaps', 'almaseo-seo-playground'),
            'manage_options',
            'almaseo-sitemaps',
            [$this, 'render_page']
        );
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        // The hook prefix is sanitize_title() of the parent menu's *title*,
        // not its slug — currently "AlmaSEO SEO Playground" → "almaseo-seo-playground".
        // Match by suffix so a parent-title rename doesn't strip our assets.
        if (strpos($hook, '_page_almaseo-sitemaps') === false) {
            return;
        }
        
        // Enqueue dashicons
        wp_enqueue_style('dashicons');
        
        // Enqueue tab styles (consolidated version)
        $tabs_css_file = ALMASEO_PATH . 'assets/css/sitemaps-tabs-consolidated.css';
        if (file_exists($tabs_css_file)) {
            wp_enqueue_style(
                'almaseo-sitemaps-tabs',
                ALMASEO_URL . 'assets/css/sitemaps-tabs-consolidated.css',
                ['dashicons'],
                filemtime($tabs_css_file)
            );
        }
        
        // Enqueue main sitemap styles (consolidated version)
        $main_css_file = ALMASEO_PATH . 'assets/css/sitemaps-consolidated.css';
        if (file_exists($main_css_file)) {
            wp_enqueue_style(
                'almaseo-sitemaps-consolidated',
                ALMASEO_URL . 'assets/css/sitemaps-consolidated.css',
                ['almaseo-sitemaps-tabs'],
                filemtime($main_css_file)
            );
        }
        
        // Enqueue JavaScript (consolidated version)
        $tabs_js_file = ALMASEO_PATH . 'assets/js/sitemaps-tabs-consolidated.js';
        if (file_exists($tabs_js_file)) {
            wp_enqueue_script(
                'almaseo-sitemaps-tabs',
                ALMASEO_URL . 'assets/js/sitemaps-tabs-consolidated.js',
                ['jquery'],
                filemtime($tabs_js_file),
                true
            );
        }
        
        // Enqueue main sitemap JavaScript (consolidated version)
        $main_js_file = ALMASEO_PATH . 'assets/js/sitemaps-consolidated.js';
        if (file_exists($main_js_file)) {
            wp_enqueue_script(
                'almaseo-sitemaps',
                ALMASEO_URL . 'assets/js/sitemaps-consolidated.js',
                ['jquery', 'almaseo-sitemaps-tabs'],
                filemtime($main_js_file),
                true
            );
        }
        
        // Localize script. Object name is camelCase to match the rest of the
        // plugin's localize calls (almaseoAdmin, almaseoWoo, almaseoInternalLinks,
        // almaseoImport, almaseoHistory, almaseoDH, almaseoGSC, almaseoWizard)
        // and to match what sitemaps-consolidated.js actually reads. Field names
        // are camelCase for the same reason — JS reads .ajaxUrl, .sitemapUrl,
        // .i18n.*, .settings.* directly.
        $urls = function_exists('almaseo_get_index_urls') ? almaseo_get_index_urls() : ['primary' => home_url('/almaseo-sitemap.xml')];
        wp_localize_script('almaseo-sitemaps', 'almaseoSitemaps', [
            'ajaxUrl'    => admin_url('admin-ajax.php'),
            'nonce'      => wp_create_nonce('almaseo_sitemaps_nonce'),
            'sitemapUrl' => $urls['primary'],
            'settings'   => get_option('almaseo_sitemap_settings', []),
            'i18n'       => [
                'saving'        => __('Saving...', 'almaseo-seo-playground'),
                'saved'         => __('Settings saved', 'almaseo-seo-playground'),
                'error'         => __('An error occurred', 'almaseo-seo-playground'),
                'rebuilding'    => __('Rebuilding sitemaps...', 'almaseo-seo-playground'),
                'rebuilt'       => __('Sitemaps rebuilt successfully', 'almaseo-seo-playground'),
                'copied'        => __('URL copied to clipboard', 'almaseo-seo-playground'),
                'enabled'       => __('Enabled', 'almaseo-seo-playground'),
                'disabled'      => __('Disabled', 'almaseo-seo-playground'),
                'recalculating' => __('Recalculating...', 'almaseo-seo-playground'),
                'recalculated'  => __('Recalculate', 'almaseo-seo-playground'),
                'lastBuilt'     => __('Last Built:', 'almaseo-seo-playground'),
                'files'         => __('Files:', 'almaseo-seo-playground'),
                'urls'          => __('URLs:', 'almaseo-seo-playground'),
                'confirmDisable' => __('Are you sure you want to disable sitemaps?', 'almaseo-seo-playground'),
            ],
        ]);
    }
    
    /**
     * Render admin page
     */
    public function render_page() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'almaseo-seo-playground'));
        }
        
        // Load the screen controller
        require_once __DIR__ . '/sitemaps-screen-v2.php';
        
        // Render the screen
        if (function_exists('almaseo_render_sitemaps_screen_v2')) {
            almaseo_render_sitemaps_screen_v2();
        } else {
            echo '<div class="error"><p>' . esc_html__('Unable to load sitemap interface.', 'almaseo-seo-playground') . '</p></div>';
        }
    }
}

// Initialize admin page
if (is_admin()) {
    new Alma_Sitemap_Admin_Page();
}