<?php
/**
 * WooCommerce Schema Generator
 *
 * @package AlmaSEO
 * @subpackage WooCommerce
 * @since 6.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AlmaSEO_Woo_Schema
 * 
 * Generates Product schema markup for WooCommerce products
 */
class AlmaSEO_Woo_Schema {
    
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
        // Add schema output to head
        add_action('wp_head', array($this, 'output_product_schema'), 15);
        
        // Filter existing schema if needed
        add_filter('almaseo_schema_output', array($this, 'modify_schema_output'), 10, 2);
        
        // Add schema to product loops
        add_action('woocommerce_after_shop_loop_item', array($this, 'add_loop_item_schema'));
    }
    
    /**
     * Output product schema
     */
    public function output_product_schema() {
        // Only on single product pages
        if (!is_singular('product')) {
            return;
        }
        
        global $post;
        $product = wc_get_product($post->ID);
        
        if (!$product) {
            return;
        }
        
        // Get settings
        $settings = AlmaSEO_Woo_Loader::get_settings();
        
        if (!$settings['enable_product_schema']) {
            return;
        }
        
        // Build schema
        $schema = $this->build_product_schema($product);
        
        if ($schema) {
            echo "\n<!-- AlmaSEO WooCommerce Product Schema -->\n";
            echo '<script type="application/ld+json">' . wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
            echo "\n<!-- /AlmaSEO WooCommerce Product Schema -->\n";
        }
    }
    
    /**
     * Build product schema
     */
    public function build_product_schema($product) {
        if (!$product) {
            return null;
        }
        
        $settings = AlmaSEO_Woo_Loader::get_settings();
        
        // Basic product schema
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->get_name(),
            'description' => $this->get_product_description($product),
            'sku' => $product->get_sku(),
            'mpn' => $product->get_sku(),
            'url' => get_permalink($product->get_id())
        );
        
        // Add brand if available
        $brand = $this->get_product_brand($product);
        if ($brand) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name' => $brand
            );
        }
        
        // Add images
        $images = $this->get_product_images($product);
        if (!empty($images)) {
            $schema['image'] = count($images) === 1 ? $images[0] : $images;
        }
        
        // Add price and offers
        if ($settings['show_price_in_schema'] && $product->get_price()) {
            $schema['offers'] = $this->build_offers_schema($product, $settings);
        }
        
        // Add reviews and ratings
        if ($settings['show_reviews_in_schema'] && $product->get_review_count() > 0) {
            $schema['aggregateRating'] = $this->build_rating_schema($product);
            $schema['review'] = $this->build_reviews_schema($product);
        }
        
        // Add additional properties for variable products
        if ($product->is_type('variable')) {
            $schema = $this->add_variable_product_schema($schema, $product);
        }
        
        // Add category
        $categories = wp_get_post_terms($product->get_id(), 'product_cat');
        if (!empty($categories)) {
            $schema['category'] = $categories[0]->name;
        }
        
        // Add weight and dimensions if available
        if ($product->get_weight()) {
            $schema['weight'] = array(
                '@type' => 'QuantitativeValue',
                'value' => $product->get_weight(),
                'unitCode' => get_option('woocommerce_weight_unit')
            );
        }
        
        if ($product->has_dimensions()) {
            $schema['depth'] = array(
                '@type' => 'QuantitativeValue',
                'value' => $product->get_length(),
                'unitCode' => get_option('woocommerce_dimension_unit')
            );
            $schema['width'] = array(
                '@type' => 'QuantitativeValue',
                'value' => $product->get_width(),
                'unitCode' => get_option('woocommerce_dimension_unit')
            );
            $schema['height'] = array(
                '@type' => 'QuantitativeValue',
                'value' => $product->get_height(),
                'unitCode' => get_option('woocommerce_dimension_unit')
            );
        }
        
        // Add GTIN/EAN if available
        $gtin = get_post_meta($product->get_id(), '_almaseo_woo_gtin', true);
        if ($gtin) {
            $schema['gtin'] = $gtin;
        }
        
        // Allow filtering
        return apply_filters('almaseo_woo_product_schema', $schema, $product);
    }
    
    /**
     * Get product description
     */
    private function get_product_description($product) {
        // Try short description first
        $description = $product->get_short_description();
        
        // Fall back to excerpt
        if (empty($description)) {
            $description = $product->get_description();
        }
        
        // Clean and trim
        $description = wp_strip_all_tags($description);
        $description = wp_trim_words($description, 50);
        
        return $description ?: $product->get_name();
    }
    
    /**
     * Get product brand
     */
    private function get_product_brand($product) {
        // Check for brand taxonomy (common in many themes)
        $brand_taxonomies = array('product_brand', 'brand', 'pa_brand');
        
        foreach ($brand_taxonomies as $taxonomy) {
            if (taxonomy_exists($taxonomy)) {
                $terms = wp_get_post_terms($product->get_id(), $taxonomy);
                if (!empty($terms) && !is_wp_error($terms)) {
                    return $terms[0]->name;
                }
            }
        }
        
        // Check for brand attribute
        $attributes = $product->get_attributes();
        if (isset($attributes['pa_brand'])) {
            $brand_terms = wc_get_product_terms($product->get_id(), 'pa_brand', array('fields' => 'names'));
            if (!empty($brand_terms)) {
                return $brand_terms[0];
            }
        }
        
        // Fall back to site name
        return get_bloginfo('name');
    }
    
    /**
     * Get product images
     */
    private function get_product_images($product) {
        $images = array();
        
        // Main image
        $main_image_id = $product->get_image_id();
        if ($main_image_id) {
            $image_url = wp_get_attachment_image_url($main_image_id, 'full');
            if ($image_url) {
                $images[] = $image_url;
            }
        }
        
        // Gallery images
        $gallery_ids = $product->get_gallery_image_ids();
        foreach ($gallery_ids as $image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            if ($image_url) {
                $images[] = $image_url;
            }
        }
        
        // Ensure we have at least one image
        if (empty($images)) {
            $images[] = wc_placeholder_img_src('full');
        }
        
        return $images;
    }
    
    /**
     * Build offers schema
     */
    private function build_offers_schema($product, $settings) {
        $offers = array(
            '@type' => 'Offer',
            'priceCurrency' => get_woocommerce_currency(),
            'url' => get_permalink($product->get_id()),
            'priceValidUntil' => date('c', strtotime('+1 year'))
        );
        
        // Price
        if ($product->is_on_sale()) {
            $offers['price'] = $product->get_sale_price();
        } else {
            $offers['price'] = $product->get_regular_price();
        }
        
        // Availability
        if ($settings['show_stock_in_schema']) {
            if ($product->is_in_stock()) {
                $offers['availability'] = 'https://schema.org/InStock';
                
                // Add stock quantity if managing stock
                if ($product->managing_stock()) {
                    $stock_qty = $product->get_stock_quantity();
                    if ($stock_qty !== null) {
                        $offers['inventoryLevel'] = array(
                            '@type' => 'QuantitativeValue',
                            'value' => $stock_qty
                        );
                    }
                }
            } else {
                $offers['availability'] = 'https://schema.org/OutOfStock';
            }
        }
        
        // Seller
        $offers['seller'] = array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url()
        );
        
        // Shipping details if available
        $shipping_class = $product->get_shipping_class();
        if ($shipping_class) {
            $offers['shippingDetails'] = array(
                '@type' => 'OfferShippingDetails',
                'shippingLabel' => $shipping_class
            );
        }
        
        // Return policy
        if (wc_get_page_id('terms') > 0) {
            $offers['hasMerchantReturnPolicy'] = array(
                '@type' => 'MerchantReturnPolicy',
                'url' => get_permalink(wc_get_page_id('terms'))
            );
        }
        
        return $offers;
    }
    
    /**
     * Build rating schema
     */
    private function build_rating_schema($product) {
        return array(
            '@type' => 'AggregateRating',
            'ratingValue' => $product->get_average_rating(),
            'reviewCount' => $product->get_review_count(),
            'bestRating' => '5',
            'worstRating' => '1'
        );
    }
    
    /**
     * Build reviews schema
     */
    private function build_reviews_schema($product) {
        $reviews = array();
        
        // Get recent reviews
        $args = array(
            'post_id' => $product->get_id(),
            'status' => 'approve',
            'type' => 'review',
            'number' => 5
        );
        
        $comments = get_comments($args);
        
        foreach ($comments as $comment) {
            $rating = get_comment_meta($comment->comment_ID, 'rating', true);
            
            if ($rating) {
                $reviews[] = array(
                    '@type' => 'Review',
                    'reviewRating' => array(
                        '@type' => 'Rating',
                        'ratingValue' => $rating,
                        'bestRating' => '5',
                        'worstRating' => '1'
                    ),
                    'author' => array(
                        '@type' => 'Person',
                        'name' => $comment->comment_author
                    ),
                    'datePublished' => date('c', strtotime($comment->comment_date)),
                    'reviewBody' => wp_trim_words($comment->comment_content, 50)
                );
            }
        }
        
        return $reviews;
    }
    
    /**
     * Add variable product schema
     */
    private function add_variable_product_schema($schema, $product) {
        $variations = $product->get_available_variations();
        
        if (empty($variations)) {
            return $schema;
        }
        
        $offers = array();
        $min_price = null;
        $max_price = null;
        
        foreach ($variations as $variation_data) {
            $variation = wc_get_product($variation_data['variation_id']);
            
            if (!$variation) {
                continue;
            }
            
            $price = $variation->get_price();
            
            if ($price) {
                if ($min_price === null || $price < $min_price) {
                    $min_price = $price;
                }
                if ($max_price === null || $price > $max_price) {
                    $max_price = $price;
                }
                
                $offer = array(
                    '@type' => 'Offer',
                    'price' => $price,
                    'priceCurrency' => get_woocommerce_currency(),
                    'availability' => $variation->is_in_stock() ? 
                        'https://schema.org/InStock' : 
                        'https://schema.org/OutOfStock'
                );
                
                // Add SKU if different
                if ($variation->get_sku() && $variation->get_sku() !== $product->get_sku()) {
                    $offer['sku'] = $variation->get_sku();
                }
                
                $offers[] = $offer;
            }
        }
        
        if (!empty($offers)) {
            // Use AggregateOffer for variable products
            $schema['offers'] = array(
                '@type' => 'AggregateOffer',
                'lowPrice' => $min_price,
                'highPrice' => $max_price,
                'priceCurrency' => get_woocommerce_currency(),
                'offerCount' => count($offers),
                'offers' => $offers
            );
        }
        
        return $schema;
    }
    
    /**
     * Modify existing schema output
     */
    public function modify_schema_output($schema, $context) {
        // Only modify on product pages
        if (!is_singular('product')) {
            return $schema;
        }
        
        // If schema already has Product type, don't duplicate
        if (isset($schema['@type']) && $schema['@type'] === 'Product') {
            return $schema;
        }
        
        // Get product
        global $post;
        $product = wc_get_product($post->ID);
        
        if ($product) {
            // Add our product schema
            $product_schema = $this->build_product_schema($product);
            
            // Merge with existing schema
            if (is_array($schema) && isset($schema['@graph'])) {
                $schema['@graph'][] = $product_schema;
            } else {
                // Convert to graph format
                $schema = array(
                    '@context' => 'https://schema.org',
                    '@graph' => array($schema, $product_schema)
                );
            }
        }
        
        return $schema;
    }
    
    /**
     * Add schema to product loops
     */
    public function add_loop_item_schema() {
        if (!is_shop() && !is_product_category() && !is_product_tag()) {
            return;
        }
        
        global $product;
        
        if (!$product) {
            return;
        }
        
        // Build minimal schema for list items
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->get_name(),
            'url' => get_permalink($product->get_id()),
            'image' => wp_get_attachment_image_url($product->get_image_id(), 'woocommerce_thumbnail')
        );
        
        if ($product->get_price()) {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => $product->get_price(),
                'priceCurrency' => get_woocommerce_currency(),
                'availability' => $product->is_in_stock() ? 
                    'https://schema.org/InStock' : 
                    'https://schema.org/OutOfStock'
            );
        }
        
        echo '<script type="application/ld+json" class="almaseo-woo-loop-schema">';
        echo wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo '</script>';
    }
}