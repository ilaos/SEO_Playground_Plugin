<?php
/**
 * AlmaSEO Posts Sitemap Provider
 * 
 * @package AlmaSEO
 * @since 4.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Provider_Posts {
    
    /**
     * Settings
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct($settings) {
        $this->settings = $settings;
    }
    
    /**
     * Get maximum number of pages
     */
    public function get_max_pages() {
        $total = $this->get_total_posts();
        $per_page = $this->settings['links_per_sitemap'];
        return (int) ceil($total / $per_page);
    }
    
    /**
     * Get total posts count
     */
    private function get_total_posts() {
        global $wpdb;
        
        $count = $wpdb->get_var("
            SELECT COUNT(ID) 
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
            AND p.post_password = ''
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} pm
                WHERE pm.post_id = p.ID
                AND pm.meta_key = '_almaseo_robots_noindex'
                AND pm.meta_value = '1'
            )
        ");
        
        return (int) $count;
    }
    
    /**
     * Get URLs for specific page
     */
    public function get_urls($page = 1) {
        global $wpdb;
        
        $per_page = $this->settings['links_per_sitemap'];
        $offset = ($page - 1) * $per_page;
        
        // Query posts efficiently
        $posts = $wpdb->get_results($wpdb->prepare("
            SELECT ID, post_modified_gmt, post_date_gmt
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
            AND p.post_password = ''
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} pm
                WHERE pm.post_id = p.ID
                AND pm.meta_key = '_almaseo_robots_noindex'
                AND pm.meta_value = '1'
            )
            ORDER BY p.post_modified_gmt DESC
            LIMIT %d OFFSET %d
        ", $per_page, $offset));
        
        $urls = array();
        
        foreach ($posts as $post) {
            $url_data = array(
                'loc' => get_permalink($post->ID),
                'lastmod' => $post->post_modified_gmt,
                'changefreq' => $this->calculate_changefreq($post->post_modified_gmt),
                'priority' => 0.7
            );
            
            // Add featured image if exists
            $thumbnail_id = get_post_thumbnail_id($post->ID);
            if ($thumbnail_id) {
                $image_url = wp_get_attachment_url($thumbnail_id);
                if ($image_url) {
                    $url_data['images'] = array(
                        array(
                            'loc' => $image_url,
                            'title' => get_the_title($post->ID)
                        )
                    );
                }
            }
            
            // Phase 5B: Add hreflang alternates
            $hreflang_settings = $this->settings['hreflang'] ?? array();
            if (!empty($hreflang_settings['enabled'])) {
                require_once dirname(dirname(__FILE__)) . '/class-alma-hreflang.php';
                $hreflang = new Alma_Hreflang($hreflang_settings);
                $alternates = $hreflang->get_post_alternates($post->ID, 'post');
                if (!empty($alternates)) {
                    $url_data['alternates'] = $alternates;
                }
            }
            
            $urls[] = $url_data;
        }
        
        return $urls;
    }
    
    /**
     * Get last modified date for page
     */
    public function get_last_modified($page = 1) {
        global $wpdb;
        
        $per_page = $this->settings['links_per_sitemap'];
        $offset = ($page - 1) * $per_page;
        
        $last_modified = $wpdb->get_var($wpdb->prepare("
            SELECT MAX(post_modified_gmt)
            FROM (
                SELECT post_modified_gmt
                FROM {$wpdb->posts} p
                WHERE p.post_type = 'post'
                AND p.post_status = 'publish'
                AND p.post_password = ''
                AND NOT EXISTS (
                    SELECT 1 FROM {$wpdb->postmeta} pm
                    WHERE pm.post_id = p.ID
                    AND pm.meta_key = '_almaseo_robots_noindex'
                    AND pm.meta_value = '1'
                )
                ORDER BY p.post_modified_gmt DESC
                LIMIT %d OFFSET %d
            ) as subset
        ", $per_page, $offset));
        
        return $last_modified ? date('c', strtotime($last_modified)) : null;
    }
    
    /**
     * Calculate change frequency based on last modified
     */
    private function calculate_changefreq($last_modified) {
        $days_ago = (time() - strtotime($last_modified)) / DAY_IN_SECONDS;
        
        if ($days_ago < 1) {
            return 'hourly';
        } elseif ($days_ago < 7) {
            return 'daily';
        } elseif ($days_ago < 30) {
            return 'weekly';
        } elseif ($days_ago < 365) {
            return 'monthly';
        } else {
            return 'yearly';
        }
    }
    
    /**
     * Check if provider supports images
     */
    public function supports_images() {
        return true;
    }
    
    /**
     * Get items using seek pagination (Phase 4)
     * More efficient for large datasets
     */
    public function get_items_seek($last_id = 0, $limit = 1000, $args = array()) {
        global $wpdb;
        
        $posts = $wpdb->get_results($wpdb->prepare("
            SELECT ID, post_modified_gmt, post_date_gmt
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'post'
            AND p.post_status = 'publish'
            AND p.post_password = ''
            AND p.ID > %d
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} pm
                WHERE pm.post_id = p.ID
                AND pm.meta_key = '_almaseo_robots_noindex'
                AND pm.meta_value = '1'
            )
            ORDER BY p.ID ASC
            LIMIT %d
        ", $last_id, $limit));
        
        return $posts;
    }
    
    /**
     * Get URL data for a single item
     */
    public function get_url_data($post) {
        $url_data = array(
            'loc' => get_permalink($post->ID),
            'lastmod' => $post->post_modified_gmt,
            'changefreq' => $this->calculate_changefreq($post->post_modified_gmt),
            'priority' => 0.7
        );
        
        // Add featured image if exists
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        if ($thumbnail_id) {
            $image_url = wp_get_attachment_url($thumbnail_id);
            if ($image_url) {
                $url_data['images'] = array(
                    array(
                        'loc' => $image_url,
                        'title' => get_the_title($post->ID)
                    )
                );
            }
        }
        
        return $url_data;
    }
}