<?php
/**
 * WooCommerce Sitemap Integration
 *
 * @package AlmaSEO
 * @subpackage WooCommerce
 * @since 6.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AlmaSEO_Woo_Sitemap
 * 
 * Integrates WooCommerce products into XML sitemaps
 */
class AlmaSEO_Woo_Sitemap {
    
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
        
        if (!$settings['enable_product_sitemap']) {
            return;
        }
        
        // Add products to sitemap
        add_filter('almaseo_sitemap_post_types', array($this, 'add_product_post_type'));
        add_filter('almaseo_sitemap_taxonomies', array($this, 'add_product_taxonomies'));
        
        // Customize product URLs in sitemap
        add_filter('almaseo_sitemap_entry', array($this, 'customize_product_entry'), 10, 3);
        
        // Add product images to sitemap
        add_filter('almaseo_sitemap_urlset', array($this, 'add_image_namespace'));
        add_filter('almaseo_sitemap_entry_images', array($this, 'add_product_images'), 10, 2);
        
        // Exclude specific products
        add_filter('almaseo_sitemap_exclude_post', array($this, 'exclude_products'), 10, 2);
        
        // Add sitemap index entries
        add_filter('almaseo_sitemap_index', array($this, 'add_sitemap_index_entries'));
    }
    
    /**
     * Add product post type to sitemap
     */
    public function add_product_post_type($post_types) {
        if (!in_array('product', $post_types)) {
            $post_types[] = 'product';
        }
        return $post_types;
    }
    
    /**
     * Add product taxonomies to sitemap
     */
    public function add_product_taxonomies($taxonomies) {
        $product_taxonomies = array('product_cat', 'product_tag');
        
        foreach ($product_taxonomies as $tax) {
            if (!in_array($tax, $taxonomies)) {
                $taxonomies[] = $tax;
            }
        }
        
        return $taxonomies;
    }
    
    /**
     * Customize product entry in sitemap
     */
    public function customize_product_entry($entry, $post, $type) {
        if ($post->post_type !== 'product') {
            return $entry;
        }
        
        $settings = AlmaSEO_Woo_Loader::get_settings();
        $product = wc_get_product($post->ID);
        
        if (!$product) {
            return $entry;
        }
        
        // Set priority
        $entry['priority'] = $settings['product_sitemap_priority'];
        
        // Set change frequency
        $entry['changefreq'] = $settings['product_sitemap_changefreq'];
        
        // Adjust priority based on product status
        if ($product->is_featured()) {
            $entry['priority'] = min(1.0, $entry['priority'] + 0.1);
        }
        
        if ($product->is_on_sale()) {
            $entry['priority'] = min(1.0, $entry['priority'] + 0.1);
            $entry['changefreq'] = 'daily'; // Update more frequently for sale items
        }
        
        if (!$product->is_in_stock()) {
            $entry['priority'] = max(0.1, $entry['priority'] - 0.2);
        }
        
        // Add last modified date
        $last_modified = get_post_modified_time('c', true, $post->ID);
        if ($last_modified) {
            $entry['lastmod'] = $last_modified;
        }
        
        // Add product images
        $images = $this->get_product_images_for_sitemap($product);
        if (!empty($images)) {
            $entry['images'] = $images;
        }
        
        return $entry;
    }
    
    /**
     * Add image namespace to sitemap
     */
    public function add_image_namespace($namespaces) {
        if (!isset($namespaces['image'])) {
            $namespaces['image'] = 'http://www.google.com/schemas/sitemap-image/1.1';
        }
        return $namespaces;
    }
    
    /**
     * Add product images to sitemap entry
     */
    public function add_product_images($images, $post_id) {
        $post = get_post($post_id);
        
        if (!$post || $post->post_type !== 'product') {
            return $images;
        }
        
        $product = wc_get_product($post_id);
        
        if (!$product) {
            return $images;
        }
        
        return $this->get_product_images_for_sitemap($product);
    }
    
    /**
     * Get product images for sitemap
     */
    private function get_product_images_for_sitemap($product) {
        $images = array();
        
        // Main product image
        $main_image_id = $product->get_image_id();
        if ($main_image_id) {
            $image_data = $this->get_image_data($main_image_id, $product->get_name());
            if ($image_data) {
                $images[] = $image_data;
            }
        }
        
        // Gallery images
        $gallery_ids = $product->get_gallery_image_ids();
        foreach ($gallery_ids as $image_id) {
            $image_data = $this->get_image_data($image_id, $product->get_name());
            if ($image_data) {
                $images[] = $image_data;
            }
        }
        
        // Variable product images
        if ($product->is_type('variable')) {
            $variations = $product->get_available_variations();
            foreach ($variations as $variation_data) {
                if (!empty($variation_data['image_id'])) {
                    $variation = wc_get_product($variation_data['variation_id']);
                    if ($variation) {
                        $variation_name = $variation->get_name();
                        $image_data = $this->get_image_data($variation_data['image_id'], $variation_name);
                        if ($image_data) {
                            $images[] = $image_data;
                        }
                    }
                }
            }
        }
        
        return $images;
    }
    
    /**
     * Get image data for sitemap
     */
    private function get_image_data($image_id, $title = '') {
        $image_url = wp_get_attachment_image_url($image_id, 'full');
        
        if (!$image_url) {
            return null;
        }
        
        $image_data = array(
            'src' => $image_url,
            'title' => $title ?: get_the_title($image_id)
        );
        
        // Add caption if available
        $caption = wp_get_attachment_caption($image_id);
        if ($caption) {
            $image_data['caption'] = $caption;
        }
        
        return $image_data;
    }
    
    /**
     * Exclude products from sitemap
     */
    public function exclude_products($exclude, $post) {
        if ($post->post_type !== 'product') {
            return $exclude;
        }
        
        // Exclude products marked as noindex
        $noindex = get_post_meta($post->ID, '_almaseo_woo_noindex', true);
        if ($noindex) {
            return true;
        }
        
        // Exclude hidden products
        $product = wc_get_product($post->ID);
        if ($product && $product->get_catalog_visibility() === 'hidden') {
            return true;
        }
        
        // Exclude private products
        if ($post->post_status === 'private') {
            return true;
        }
        
        return $exclude;
    }
    
    /**
     * Add sitemap index entries
     */
    public function add_sitemap_index_entries($entries) {
        // Add product sitemap
        $product_count = wp_count_posts('product');
        if ($product_count->publish > 0) {
            $entries[] = array(
                'loc' => home_url('/sitemap-products.xml'),
                'lastmod' => $this->get_last_modified_date('product')
            );
        }
        
        // Add product category sitemap
        $category_count = wp_count_terms('product_cat');
        if ($category_count > 0) {
            $entries[] = array(
                'loc' => home_url('/sitemap-product-categories.xml'),
                'lastmod' => $this->get_last_modified_date('product_cat')
            );
        }
        
        // Add product tag sitemap
        $tag_count = wp_count_terms('product_tag');
        if ($tag_count > 0) {
            $entries[] = array(
                'loc' => home_url('/sitemap-product-tags.xml'),
                'lastmod' => $this->get_last_modified_date('product_tag')
            );
        }
        
        return $entries;
    }
    
    /**
     * Get last modified date for post type or taxonomy
     */
    private function get_last_modified_date($type) {
        global $wpdb;
        
        if (post_type_exists($type)) {
            // For post types
            $last_modified = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(post_modified_gmt) FROM {$wpdb->posts} 
                WHERE post_type = %s AND post_status = 'publish'",
                $type
            ));
        } else {
            // For taxonomies
            $last_modified = $wpdb->get_var($wpdb->prepare(
                "SELECT MAX(p.post_modified_gmt) 
                FROM {$wpdb->posts} p
                INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tt.taxonomy = %s AND p.post_status = 'publish'",
                $type
            ));
        }
        
        return $last_modified ? date('c', strtotime($last_modified)) : date('c');
    }
    
    /**
     * Generate product sitemap
     */
    public static function generate_product_sitemap() {
        $settings = AlmaSEO_Woo_Loader::get_settings();
        
        if (!$settings['enable_product_sitemap']) {
            return false;
        }
        
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"';
        $sitemap .= ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
        
        // Get products
        $args = array(
            'post_type' => 'product',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_almaseo_woo_noindex',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_almaseo_woo_noindex',
                    'value' => '1',
                    'compare' => '!='
                )
            )
        );
        
        $products = get_posts($args);
        
        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);
            
            if (!$product || $product->get_catalog_visibility() === 'hidden') {
                continue;
            }
            
            $sitemap .= "\t<url>\n";
            $sitemap .= "\t\t<loc>" . get_permalink($product_post->ID) . "</loc>\n";
            $sitemap .= "\t\t<lastmod>" . get_post_modified_time('c', true, $product_post->ID) . "</lastmod>\n";
            $sitemap .= "\t\t<changefreq>" . esc_html($settings['product_sitemap_changefreq']) . "</changefreq>\n";
            
            // Priority
            $priority = $settings['product_sitemap_priority'];
            if ($product->is_featured()) {
                $priority = min(1.0, $priority + 0.1);
            }
            $sitemap .= "\t\t<priority>" . number_format($priority, 1) . "</priority>\n";
            
            // Images
            $instance = self::get_instance();
            $images = $instance->get_product_images_for_sitemap($product);
            
            foreach ($images as $image) {
                $sitemap .= "\t\t<image:image>\n";
                $sitemap .= "\t\t\t<image:loc>" . esc_url($image['src']) . "</image:loc>\n";
                if (!empty($image['title'])) {
                    $sitemap .= "\t\t\t<image:title>" . esc_html($image['title']) . "</image:title>\n";
                }
                if (!empty($image['caption'])) {
                    $sitemap .= "\t\t\t<image:caption>" . esc_html($image['caption']) . "</image:caption>\n";
                }
                $sitemap .= "\t\t</image:image>\n";
            }
            
            $sitemap .= "\t</url>\n";
        }
        
        $sitemap .= "</urlset>";
        
        return $sitemap;
    }
    
    /**
     * Generate category sitemap
     */
    public static function generate_category_sitemap() {
        $settings = AlmaSEO_Woo_Loader::get_settings();
        
        if (!$settings['enable_product_sitemap']) {
            return false;
        }
        
        $sitemap = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        
        // Get categories
        $categories = get_terms(array(
            'taxonomy' => 'product_cat',
            'hide_empty' => true,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_almaseo_woo_term_noindex',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_almaseo_woo_term_noindex',
                    'value' => '1',
                    'compare' => '!='
                )
            )
        ));
        
        foreach ($categories as $category) {
            $sitemap .= "\t<url>\n";
            $sitemap .= "\t\t<loc>" . get_term_link($category) . "</loc>\n";
            $sitemap .= "\t\t<changefreq>" . esc_html($settings['category_sitemap_changefreq']) . "</changefreq>\n";
            $sitemap .= "\t\t<priority>" . esc_html($settings['category_sitemap_priority']) . "</priority>\n";
            $sitemap .= "\t</url>\n";
        }
        
        $sitemap .= "</urlset>";
        
        return $sitemap;
    }
}