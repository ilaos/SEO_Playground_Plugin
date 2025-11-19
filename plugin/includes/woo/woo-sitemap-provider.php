<?php
/**
 * AlmaSEO WooCommerce Sitemap Provider
 *
 * Provides WooCommerce products and taxonomies to the sitemap system.
 * Pro feature only.
 *
 * @package AlmaSEO
 * @subpackage WooCommerce
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Provider_WC_Products {

    private $priority = 0.6;
    private $cat_priority = 0.5;

    /**
     * Get sitemap entries
     *
     * @return array Array of sitemap entries
     */
    public function get_entries() {
        // Only load if Pro feature is available
        if ( ! almaseo_feature_available('woocommerce') ) {
            return array();
        }

        // Only load if WooCommerce is active
        if ( ! class_exists('WooCommerce') ) {
            return array();
        }

        $entries = array();

        // Get settings
        $noindex_products = get_option('almaseo_wc_noindex_products', false);
        $noindex_cats = get_option('almaseo_wc_noindex_product_cats', false);
        $noindex_tags = get_option('almaseo_wc_noindex_product_tags', false);

        // Get priority settings (optional)
        $this->priority = (float) get_option('almaseo_wc_product_priority', 0.6);
        $this->cat_priority = (float) get_option('almaseo_wc_category_priority', 0.5);

        // Add products if not noindexed
        if (!$noindex_products) {
            $entries = array_merge($entries, $this->get_product_entries());
        }

        // Add product categories if not noindexed
        if (!$noindex_cats) {
            $entries = array_merge($entries, $this->get_category_entries());
        }

        // Add product tags if not noindexed
        if (!$noindex_tags) {
            $entries = array_merge($entries, $this->get_tag_entries());
        }

        return $entries;
    }

    /**
     * Get product entries
     *
     * @return array
     */
    private function get_product_entries() {
        $entries = array();

        // Query products
        $args = array(
            'post_type'      => 'product',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'orderby'        => 'modified',
            'order'          => 'DESC',
            'meta_query'     => array(
                array(
                    'key'     => '_almaseo_wc_noindex',
                    'compare' => 'NOT EXISTS',
                ),
            ),
        );

        $products = get_posts($args);

        foreach ($products as $product_id) {
            // Get product object
            $product = wc_get_product($product_id);

            if (!$product || !$product->is_visible()) {
                continue;
            }

            // Get last modified date
            $post = get_post($product_id);
            $lastmod = $post->post_modified;

            // Check for custom canonical (skip if set)
            $canonical = get_post_meta($product_id, '_almaseo_wc_canonical', true);
            if ($canonical && $canonical !== get_permalink($product_id)) {
                continue; // Skip products with external canonical
            }

            $entries[] = array(
                'loc'        => get_permalink($product_id),
                'lastmod'    => $lastmod,
                'changefreq' => 'weekly',
                'priority'   => $this->priority,
                'images'     => $this->get_product_images($product),
            );
        }

        return $entries;
    }

    /**
     * Get product category entries
     *
     * @return array
     */
    private function get_category_entries() {
        $entries = array();

        $terms = get_terms(array(
            'taxonomy'   => 'product_cat',
            'hide_empty' => true,
        ));

        if (is_wp_error($terms) || empty($terms)) {
            return $entries;
        }

        foreach ($terms as $term) {
            // Get term link
            $term_link = get_term_link($term);

            if (is_wp_error($term_link)) {
                continue;
            }

            // Get last modified date of most recent product in category
            $latest_product = get_posts(array(
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'product_cat',
                        'field'    => 'term_id',
                        'terms'    => $term->term_id,
                    ),
                ),
            ));

            $lastmod = !empty($latest_product) ? get_post($latest_product[0])->post_modified : current_time('mysql');

            $entries[] = array(
                'loc'        => $term_link,
                'lastmod'    => $lastmod,
                'changefreq' => 'weekly',
                'priority'   => $this->cat_priority,
            );
        }

        return $entries;
    }

    /**
     * Get product tag entries
     *
     * @return array
     */
    private function get_tag_entries() {
        $entries = array();

        $terms = get_terms(array(
            'taxonomy'   => 'product_tag',
            'hide_empty' => true,
        ));

        if (is_wp_error($terms) || empty($terms)) {
            return $entries;
        }

        foreach ($terms as $term) {
            // Get term link
            $term_link = get_term_link($term);

            if (is_wp_error($term_link)) {
                continue;
            }

            // Get last modified date
            $latest_product = get_posts(array(
                'post_type'      => 'product',
                'post_status'    => 'publish',
                'posts_per_page' => 1,
                'fields'         => 'ids',
                'orderby'        => 'modified',
                'order'          => 'DESC',
                'tax_query'      => array(
                    array(
                        'taxonomy' => 'product_tag',
                        'field'    => 'term_id',
                        'terms'    => $term->term_id,
                    ),
                ),
            ));

            $lastmod = !empty($latest_product) ? get_post($latest_product[0])->post_modified : current_time('mysql');

            $entries[] = array(
                'loc'        => $term_link,
                'lastmod'    => $lastmod,
                'changefreq' => 'monthly',
                'priority'   => 0.4,
            );
        }

        return $entries;
    }

    /**
     * Get product images for sitemap
     *
     * @param WC_Product $product
     * @return array
     */
    private function get_product_images($product) {
        $images = array();

        // Main image
        $main_image_id = $product->get_image_id();
        if ($main_image_id) {
            $image_url = wp_get_attachment_image_url($main_image_id, 'full');
            if ($image_url) {
                $images[] = array(
                    'loc'   => $image_url,
                    'title' => $product->get_name(),
                );
            }
        }

        // Gallery images (limit to 5 for performance)
        $gallery_ids = array_slice($product->get_gallery_image_ids(), 0, 5);
        foreach ($gallery_ids as $image_id) {
            $image_url = wp_get_attachment_image_url($image_id, 'full');
            if ($image_url) {
                $images[] = array(
                    'loc'   => $image_url,
                    'title' => get_the_title($image_id),
                );
            }
        }

        return $images;
    }

    /**
     * Get provider name
     *
     * @return string
     */
    public function get_name() {
        return 'WooCommerce Products';
    }

    /**
     * Get provider slug
     *
     * @return string
     */
    public function get_slug() {
        return 'wc-products';
    }
}
