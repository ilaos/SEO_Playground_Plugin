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
        // Check if Pro feature is available
        if ( ! almaseo_feature_available('woocommerce') ) {
            return;
        }

        // Check if WooCommerce is active
        if (!$this->is_woocommerce_active()) {
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
        // Load WooCommerce metabox (product SEO fields)
        if (file_exists(plugin_dir_path(__FILE__) . 'woo-metabox.php')) {
            require_once plugin_dir_path(__FILE__) . 'woo-metabox.php';
        }

        // Load WooCommerce schema handler
        if (file_exists(plugin_dir_path(__FILE__) . 'woo-schema.php')) {
            require_once plugin_dir_path(__FILE__) . 'woo-schema.php';
        }

        // Load WooCommerce sitemap provider
        if (file_exists(plugin_dir_path(__FILE__) . 'woo-sitemap-provider.php')) {
            require_once plugin_dir_path(__FILE__) . 'woo-sitemap-provider.php';
        }

        // Load WooCommerce meta handler (if exists)
        if (file_exists(plugin_dir_path(__FILE__) . 'woo-meta.php')) {
            require_once plugin_dir_path(__FILE__) . 'woo-meta.php';
        }

        // Load WooCommerce breadcrumbs (if exists)
        if (file_exists(plugin_dir_path(__FILE__) . 'woo-breadcrumbs.php')) {
            require_once plugin_dir_path(__FILE__) . 'woo-breadcrumbs.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Initialize schema handler
        if (class_exists('AlmaSEO_Woo_Schema')) {
            AlmaSEO_Woo_Schema::get_instance();
        }

        // Initialize optional modules (if they exist)
        if (class_exists('AlmaSEO_Woo_Meta')) {
            AlmaSEO_Woo_Meta::get_instance();
        }
        if (class_exists('AlmaSEO_Woo_Breadcrumbs')) {
            AlmaSEO_Woo_Breadcrumbs::get_instance();
        }

        // Register sitemap provider
        add_filter('almaseo_sitemap_providers', array($this, 'register_sitemap_provider'));

        // Add robots.txt rules for noindexed products
        add_filter('robots_txt', array($this, 'add_robots_txt_rules'), 10, 2);

        // Add meta robots tags for noindexed products
        add_action('wp_head', array($this, 'add_noindex_meta'), 1);

        // Enqueue admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Register WooCommerce sitemap provider
     */
    public function register_sitemap_provider($providers) {
        if (class_exists('Alma_Provider_WC_Products')) {
            $providers['wc-products'] = new Alma_Provider_WC_Products();
        }
        return $providers;
    }

    /**
     * Add robots.txt rules for noindexed products
     */
    public function add_robots_txt_rules($output, $public) {
        // Only add rules if products are globally noindexed
        $noindex_products = get_option('almaseo_wc_noindex_products', false);

        if ($noindex_products) {
            $output .= "\n# AlmaSEO WooCommerce SEO - Noindex Products\n";
            $output .= "User-agent: *\n";
            $output .= "Disallow: /product/\n";
            $output .= "Disallow: /*add-to-cart=\n";
        }

        return $output;
    }

    /**
     * Add noindex meta tag for products
     */
    public function add_noindex_meta() {
        // Check if on single product page
        if (!is_singular('product')) {
            return;
        }

        global $post;

        // Check global noindex setting
        $global_noindex = get_option('almaseo_wc_noindex_products', false);

        // Check per-product noindex
        $product_noindex = get_post_meta($post->ID, '_almaseo_wc_noindex', true);

        if ($global_noindex || $product_noindex) {
            echo '<meta name="robots" content="noindex, follow" />' . "\n";
        }

        // Check product categories noindex
        if (is_product_category() && get_option('almaseo_wc_noindex_product_cats', false)) {
            echo '<meta name="robots" content="noindex, follow" />' . "\n";
        }

        // Check product tags noindex
        if (is_product_tag() && get_option('almaseo_wc_noindex_product_tags', false)) {
            echo '<meta name="robots" content="noindex, follow" />' . "\n";
        }
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

// Initialize if Pro feature available and WooCommerce is active
add_action('plugins_loaded', function() {
    if (almaseo_feature_available('woocommerce')) {
        AlmaSEO_Woo_Loader::get_instance();
    }
}, 20);