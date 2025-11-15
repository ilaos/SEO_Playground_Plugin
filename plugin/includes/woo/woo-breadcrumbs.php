<?php
/**
 * WooCommerce Breadcrumbs Integration
 *
 * @package AlmaSEO
 * @subpackage WooCommerce
 * @since 6.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AlmaSEO_Woo_Breadcrumbs
 * 
 * Enhanced breadcrumbs for WooCommerce with schema markup
 */
class AlmaSEO_Woo_Breadcrumbs {
    
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
        // Get settings
        $settings = AlmaSEO_Woo_Loader::get_settings();
        
        if (!$settings['enable_breadcrumbs']) {
            return;
        }
        
        // Replace WooCommerce breadcrumbs
        remove_action('woocommerce_before_main_content', 'woocommerce_breadcrumb', 20);
        add_action('woocommerce_before_main_content', array($this, 'render_breadcrumbs'), 20);
        
        // Add breadcrumb schema
        add_action('wp_head', array($this, 'output_breadcrumb_schema'), 10);
        
        // Customize WooCommerce breadcrumb args
        add_filter('woocommerce_breadcrumb_defaults', array($this, 'customize_breadcrumb_defaults'));
        
        // Add shortcode
        add_shortcode('almaseo_woo_breadcrumbs', array($this, 'breadcrumb_shortcode'));
    }
    
    /**
     * Render breadcrumbs
     */
    public function render_breadcrumbs() {
        if (!$this->should_show_breadcrumbs()) {
            return;
        }
        
        $breadcrumbs = $this->get_breadcrumbs();
        
        if (empty($breadcrumbs)) {
            return;
        }
        
        $settings = AlmaSEO_Woo_Loader::get_settings();
        
        echo '<nav class="almaseo-woo-breadcrumbs" aria-label="' . esc_attr__('Breadcrumb', 'almaseo') . '">';
        echo '<ol class="breadcrumb">';
        
        $total = count($breadcrumbs);
        $current = 0;
        
        foreach ($breadcrumbs as $crumb) {
            $current++;
            $is_last = ($current === $total);
            
            echo '<li class="breadcrumb-item' . ($is_last ? ' active' : '') . '"';
            
            if ($is_last) {
                echo ' aria-current="page"';
            }
            
            echo '>';
            
            if (!$is_last && !empty($crumb['url'])) {
                echo '<a href="' . esc_url($crumb['url']) . '">' . esc_html($crumb['text']) . '</a>';
            } else {
                echo '<span>' . esc_html($crumb['text']) . '</span>';
            }
            
            if (!$is_last) {
                echo '<span class="breadcrumb-separator">' . esc_html($settings['breadcrumb_separator']) . '</span>';
            }
            
            echo '</li>';
        }
        
        echo '</ol>';
        echo '</nav>';
    }
    
    /**
     * Get breadcrumbs
     */
    private function get_breadcrumbs() {
        $breadcrumbs = array();
        $settings = AlmaSEO_Woo_Loader::get_settings();
        
        // Home
        $breadcrumbs[] = array(
            'text' => $settings['breadcrumb_home_text'],
            'url' => home_url('/')
        );
        
        // Shop page
        if (is_shop()) {
            $breadcrumbs[] = array(
                'text' => $settings['breadcrumb_shop_text'],
                'url' => ''
            );
        }
        // Product category
        elseif (is_product_category()) {
            // Shop
            $breadcrumbs[] = array(
                'text' => $settings['breadcrumb_shop_text'],
                'url' => get_permalink(wc_get_page_id('shop'))
            );
            
            // Parent categories
            $current_term = get_queried_object();
            $ancestors = get_ancestors($current_term->term_id, 'product_cat');
            
            if (!empty($ancestors)) {
                $ancestors = array_reverse($ancestors);
                foreach ($ancestors as $ancestor_id) {
                    $ancestor = get_term($ancestor_id, 'product_cat');
                    $breadcrumbs[] = array(
                        'text' => $ancestor->name,
                        'url' => get_term_link($ancestor)
                    );
                }
            }
            
            // Current category
            $breadcrumbs[] = array(
                'text' => $current_term->name,
                'url' => ''
            );
        }
        // Product tag
        elseif (is_product_tag()) {
            // Shop
            $breadcrumbs[] = array(
                'text' => $settings['breadcrumb_shop_text'],
                'url' => get_permalink(wc_get_page_id('shop'))
            );
            
            // Current tag
            $current_term = get_queried_object();
            $breadcrumbs[] = array(
                'text' => sprintf(__('Products tagged "%s"', 'almaseo'), $current_term->name),
                'url' => ''
            );
        }
        // Single product
        elseif (is_product()) {
            // Shop
            $breadcrumbs[] = array(
                'text' => $settings['breadcrumb_shop_text'],
                'url' => get_permalink(wc_get_page_id('shop'))
            );
            
            // Product categories
            global $post;
            $terms = wc_get_product_terms($post->ID, 'product_cat', array('orderby' => 'parent', 'order' => 'DESC'));
            
            if (!empty($terms)) {
                $main_term = $terms[0];
                $ancestors = get_ancestors($main_term->term_id, 'product_cat');
                
                if (!empty($ancestors)) {
                    $ancestors = array_reverse($ancestors);
                    foreach ($ancestors as $ancestor_id) {
                        $ancestor = get_term($ancestor_id, 'product_cat');
                        $breadcrumbs[] = array(
                            'text' => $ancestor->name,
                            'url' => get_term_link($ancestor)
                        );
                    }
                }
                
                $breadcrumbs[] = array(
                    'text' => $main_term->name,
                    'url' => get_term_link($main_term)
                );
            }
            
            // Product name
            $breadcrumbs[] = array(
                'text' => get_the_title(),
                'url' => ''
            );
        }
        // Cart
        elseif (is_cart()) {
            $breadcrumbs[] = array(
                'text' => __('Cart', 'almaseo'),
                'url' => ''
            );
        }
        // Checkout
        elseif (is_checkout()) {
            if (!is_order_received_page()) {
                $breadcrumbs[] = array(
                    'text' => __('Checkout', 'almaseo'),
                    'url' => ''
                );
            } else {
                $breadcrumbs[] = array(
                    'text' => __('Order Received', 'almaseo'),
                    'url' => ''
                );
            }
        }
        // My Account
        elseif (is_account_page()) {
            $breadcrumbs[] = array(
                'text' => __('My Account', 'almaseo'),
                'url' => ''
            );
            
            // Add endpoint if applicable
            $current_endpoint = WC()->query->get_current_endpoint();
            if ($current_endpoint) {
                $endpoint_title = WC()->query->get_endpoint_title($current_endpoint);
                if ($endpoint_title) {
                    $breadcrumbs[] = array(
                        'text' => $endpoint_title,
                        'url' => ''
                    );
                }
            }
        }
        
        return apply_filters('almaseo_woo_breadcrumbs', $breadcrumbs);
    }
    
    /**
     * Should show breadcrumbs
     */
    private function should_show_breadcrumbs() {
        // Don't show on homepage
        if (is_front_page()) {
            return false;
        }
        
        // Check if WooCommerce page
        if (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page()) {
            return false;
        }
        
        return apply_filters('almaseo_woo_show_breadcrumbs', true);
    }
    
    /**
     * Output breadcrumb schema
     */
    public function output_breadcrumb_schema() {
        if (!$this->should_show_breadcrumbs()) {
            return;
        }
        
        $breadcrumbs = $this->get_breadcrumbs();
        
        if (empty($breadcrumbs)) {
            return;
        }
        
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array()
        );
        
        $position = 0;
        
        foreach ($breadcrumbs as $crumb) {
            $position++;
            
            $item = array(
                '@type' => 'ListItem',
                'position' => $position,
                'name' => $crumb['text']
            );
            
            if (!empty($crumb['url'])) {
                $item['item'] = $crumb['url'];
            }
            
            $schema['itemListElement'][] = $item;
        }
        
        echo "\n<!-- AlmaSEO WooCommerce Breadcrumb Schema -->\n";
        echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
        echo "\n<!-- /AlmaSEO WooCommerce Breadcrumb Schema -->\n";
    }
    
    /**
     * Customize breadcrumb defaults
     */
    public function customize_breadcrumb_defaults($defaults) {
        $settings = AlmaSEO_Woo_Loader::get_settings();
        
        $defaults['delimiter'] = '<span class="breadcrumb-separator">' . esc_html($settings['breadcrumb_separator']) . '</span>';
        $defaults['wrap_before'] = '<nav class="almaseo-woo-breadcrumbs woocommerce-breadcrumb" aria-label="' . esc_attr__('Breadcrumb', 'almaseo') . '">';
        $defaults['wrap_after'] = '</nav>';
        $defaults['before'] = '<span class="breadcrumb-item">';
        $defaults['after'] = '</span>';
        $defaults['home'] = $settings['breadcrumb_home_text'];
        
        return $defaults;
    }
    
    /**
     * Breadcrumb shortcode
     */
    public function breadcrumb_shortcode($atts) {
        $atts = shortcode_atts(array(
            'separator' => null,
            'home_text' => null,
            'shop_text' => null
        ), $atts, 'almaseo_woo_breadcrumbs');
        
        // Temporarily override settings if attributes provided
        if ($atts['separator'] || $atts['home_text'] || $atts['shop_text']) {
            add_filter('almaseo_woo_breadcrumbs', function($breadcrumbs) use ($atts) {
                $settings = AlmaSEO_Woo_Loader::get_settings();
                
                if ($atts['home_text'] && !empty($breadcrumbs[0])) {
                    $breadcrumbs[0]['text'] = $atts['home_text'];
                }
                
                return $breadcrumbs;
            });
        }
        
        ob_start();
        $this->render_breadcrumbs();
        return ob_get_clean();
    }
    
    /**
     * Get breadcrumb trail for external use
     */
    public static function get_breadcrumb_trail() {
        $instance = self::get_instance();
        return $instance->get_breadcrumbs();
    }
    
    /**
     * Render inline CSS for breadcrumbs
     */
    public static function render_breadcrumb_styles() {
        ?>
        <style>
        .almaseo-woo-breadcrumbs {
            padding: 15px 0;
            margin-bottom: 20px;
            font-size: 14px;
            color: #666;
        }
        
        .almaseo-woo-breadcrumbs ol.breadcrumb {
            list-style: none;
            padding: 0;
            margin: 0;
            display: flex;
            flex-wrap: wrap;
            align-items: center;
        }
        
        .almaseo-woo-breadcrumbs .breadcrumb-item {
            display: inline-flex;
            align-items: center;
        }
        
        .almaseo-woo-breadcrumbs .breadcrumb-item a {
            color: #0073aa;
            text-decoration: none;
            transition: color 0.2s;
        }
        
        .almaseo-woo-breadcrumbs .breadcrumb-item a:hover {
            color: #005177;
            text-decoration: underline;
        }
        
        .almaseo-woo-breadcrumbs .breadcrumb-item.active {
            color: #333;
            font-weight: 500;
        }
        
        .almaseo-woo-breadcrumbs .breadcrumb-separator {
            margin: 0 8px;
            color: #999;
        }
        
        @media (max-width: 768px) {
            .almaseo-woo-breadcrumbs {
                font-size: 13px;
            }
            
            .almaseo-woo-breadcrumbs .breadcrumb-separator {
                margin: 0 5px;
            }
        }
        </style>
        <?php
    }
}