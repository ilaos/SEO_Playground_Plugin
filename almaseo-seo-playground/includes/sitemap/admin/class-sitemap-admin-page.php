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
            __('Sitemaps', 'almaseo'),
            __('Sitemaps', 'almaseo'),
            'manage_options',
            'almaseo-sitemaps',
            [$this, 'render_page']
        );
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        if ($hook !== 'seo-playground_page_almaseo-sitemaps') {
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
        
        // Localize script
        wp_localize_script('almaseo-sitemaps', 'almaseo_sitemaps', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('almaseo_sitemaps_nonce'),
            'strings' => [
                'saving' => __('Saving...', 'almaseo'),
                'saved' => __('Settings saved', 'almaseo'),
                'error' => __('An error occurred', 'almaseo'),
                'rebuilding' => __('Rebuilding sitemaps...', 'almaseo'),
                'rebuilt' => __('Sitemaps rebuilt successfully', 'almaseo'),
                'copied' => __('URL copied to clipboard', 'almaseo'),
                'confirm_disable' => __('Are you sure you want to disable sitemaps?', 'almaseo')
            ]
        ]);
    }
    
    /**
     * Render admin page
     */
    public function render_page() {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'almaseo'));
        }
        
        // Load the screen controller
        require_once __DIR__ . '/sitemaps-screen-v2.php';
        
        // Render the screen
        if (function_exists('almaseo_render_sitemaps_screen_v2')) {
            almaseo_render_sitemaps_screen_v2();
        } else {
            echo '<div class="error"><p>' . __('Unable to load sitemap interface.', 'almaseo') . '</p></div>';
        }
    }
}

// Initialize admin page
if (is_admin()) {
    new Alma_Sitemap_Admin_Page();
}