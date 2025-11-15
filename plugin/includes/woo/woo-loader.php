<?php
/**
 * WooCommerce SEO Loader
 *
 * @package AlmaSEO
 * @subpackage WooCommerce
 * @since 6.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AlmaSEO_Woo_Loader
 * 
 * Loads WooCommerce SEO features for Pro users
 */
class AlmaSEO_Woo_Loader {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
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
        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
            return;
        }
        
        // Check if user has Pro tier
        if (function_exists('almaseo_is_pro') && !almaseo_is_pro()) {
            return;
        }
        
        // Load modules
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Check if WooCommerce is active
     */
    private function is_woocommerce_active() {
        return class_exists('WooCommerce') || function_exists('WC');
    }
    
    /**
     * Load dependencies
     */
    private function load_dependencies() {
        // Load WooCommerce meta handler
        require_once plugin_dir_path(__FILE__) . 'woo-meta.php';
        
        // Load WooCommerce schema handler
        require_once plugin_dir_path(__FILE__) . 'woo-schema.php';
        
        // Load WooCommerce breadcrumbs
        require_once plugin_dir_path(__FILE__) . 'woo-breadcrumbs.php';
        
        // Load WooCommerce sitemap integration
        require_once plugin_dir_path(__FILE__) . 'woo-sitemap.php';
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize modules
        AlmaSEO_Woo_Meta::get_instance();
        AlmaSEO_Woo_Schema::get_instance();
        AlmaSEO_Woo_Breadcrumbs::get_instance();
        AlmaSEO_Woo_Sitemap::get_instance();
        
        // Add settings tab
        add_filter('almaseo_settings_tabs', array($this, 'add_settings_tab'));
        add_action('almaseo_settings_content_woocommerce', array($this, 'render_settings_page'));
        
        // Enqueue admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Add WooCommerce tab to settings
     */
    public function add_settings_tab($tabs) {
        $tabs['woocommerce'] = array(
            'title' => __('WooCommerce SEO', 'almaseo'),
            'icon' => 'dashicons-cart',
            'capability' => 'manage_woocommerce',
            'pro' => true
        );
        return $tabs;
    }
    
    /**
     * Render WooCommerce settings page
     */
    public function render_settings_page() {
        // Check capability
        if (!current_user_can('manage_woocommerce') && !current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'almaseo'));
        }
        
        // Include settings page
        $settings_file = plugin_dir_path(dirname(dirname(__FILE__))) . 'admin/pages/settings-woo.php';
        if (file_exists($settings_file)) {
            include $settings_file;
        } else {
            echo '<div class="notice notice-info"><p>' . __('WooCommerce SEO settings coming soon!', 'almaseo') . '</p></div>';
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        global $post;
        
        // Only load on product pages and WooCommerce term pages
        $screen = get_current_screen();
        
        if (($screen && $screen->post_type === 'product') || 
            (isset($_GET['taxonomy']) && in_array($_GET['taxonomy'], array('product_cat', 'product_tag')))) {
            
            // Enqueue CSS
            wp_enqueue_style(
                'almaseo-woo-meta',
                plugins_url('assets/css/woo-meta.css', dirname(dirname(__FILE__))),
                array(),
                ALMASEO_VERSION
            );
            
            // Enqueue JS
            wp_enqueue_script(
                'almaseo-woo-meta',
                plugins_url('assets/js/woo-meta.js', dirname(dirname(__FILE__))),
                array('jquery'),
                ALMASEO_VERSION,
                true
            );
            
            // Localize script
            wp_localize_script('almaseo-woo-meta', 'almaseoWoo', array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('almaseo_woo_nonce'),
                'strings' => array(
                    'title_placeholder' => __('Product Title', 'almaseo'),
                    'description_placeholder' => __('Product description will appear here...', 'almaseo'),
                    'char_remaining' => __('characters remaining', 'almaseo'),
                    'char_over' => __('characters over limit', 'almaseo')
                )
            ));
        }
    }
    
    /**
     * Get WooCommerce SEO settings
     */
    public static function get_settings() {
        $defaults = array(
            'enable_product_schema' => true,
            'enable_breadcrumbs' => true,
            'enable_product_sitemap' => true,
            'product_sitemap_priority' => '0.8',
            'product_sitemap_changefreq' => 'daily',
            'category_sitemap_priority' => '0.6',
            'category_sitemap_changefreq' => 'weekly',
            'show_price_in_schema' => true,
            'show_stock_in_schema' => true,
            'show_reviews_in_schema' => true,
            'breadcrumb_separator' => ' / ',
            'breadcrumb_home_text' => __('Home', 'almaseo'),
            'breadcrumb_shop_text' => __('Shop', 'almaseo')
        );
        
        $settings = get_option('almaseo_woo_settings', array());
        return wp_parse_args($settings, $defaults);
    }
    
    /**
     * Save WooCommerce SEO settings
     */
    public static function save_settings($settings) {
        // Sanitize settings
        $clean_settings = array();
        
        // Boolean settings
        $boolean_keys = array(
            'enable_product_schema',
            'enable_breadcrumbs',
            'enable_product_sitemap',
            'show_price_in_schema',
            'show_stock_in_schema',
            'show_reviews_in_schema'
        );
        
        foreach ($boolean_keys as $key) {
            $clean_settings[$key] = isset($settings[$key]) ? (bool) $settings[$key] : false;
        }
        
        // Text settings
        $text_keys = array(
            'product_sitemap_priority',
            'product_sitemap_changefreq',
            'category_sitemap_priority',
            'category_sitemap_changefreq',
            'breadcrumb_separator',
            'breadcrumb_home_text',
            'breadcrumb_shop_text'
        );
        
        foreach ($text_keys as $key) {
            if (isset($settings[$key])) {
                $clean_settings[$key] = sanitize_text_field($settings[$key]);
            }
        }
        
        return update_option('almaseo_woo_settings', $clean_settings);
    }
}

// Initialize if Pro user and WooCommerce is active
add_action('plugins_loaded', function() {
    if (function_exists('almaseo_is_pro') && almaseo_is_pro()) {
        AlmaSEO_Woo_Loader::get_instance();
    }
}, 20);