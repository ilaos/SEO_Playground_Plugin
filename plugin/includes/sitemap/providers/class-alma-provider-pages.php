<?php
/**
 * AlmaSEO Pages Sitemap Provider
 * 
 * @package AlmaSEO
 * @since 4.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Provider_Pages {
    
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
        $total = $this->get_total_pages();
        $per_page = $this->settings['links_per_sitemap'];
        return (int) ceil($total / $per_page);
    }
    
    /**
     * Get total pages count
     */
    private function get_total_pages() {
        global $wpdb;
        
        $count = $wpdb->get_var("
            SELECT COUNT(ID) 
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'page'
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
        
        // Query pages efficiently
        $pages = $wpdb->get_results($wpdb->prepare("
            SELECT ID, post_modified_gmt, post_parent, menu_order
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'page'
            AND p.post_status = 'publish'
            AND p.post_password = ''
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} pm
                WHERE pm.post_id = p.ID
                AND pm.meta_key = '_almaseo_robots_noindex'
                AND pm.meta_value = '1'
            )
            ORDER BY p.post_parent ASC, p.menu_order ASC, p.post_title ASC
            LIMIT %d OFFSET %d
        ", $per_page, $offset));
        
        $urls = array();
        
        foreach ($pages as $page_obj) {
            // Calculate priority based on hierarchy
            $priority = $this->calculate_priority($page_obj);
            
            $url_data = array(
                'loc' => get_permalink($page_obj->ID),
                'lastmod' => $page_obj->post_modified_gmt,
                'changefreq' => $this->calculate_changefreq($page_obj->post_modified_gmt),
                'priority' => $priority
            );
            
            // Add featured image if exists
            $thumbnail_id = get_post_thumbnail_id($page_obj->ID);
            if ($thumbnail_id) {
                $image_url = wp_get_attachment_url($thumbnail_id);
                if ($image_url) {
                    $url_data['images'] = array(
                        array(
                            'loc' => $image_url,
                            'title' => get_the_title($page_obj->ID)
                        )
                    );
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
                WHERE p.post_type = 'page'
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
     * Calculate priority based on page hierarchy
     */
    private function calculate_priority($page) {
        // Homepage gets highest priority
        if ($page->ID == get_option('page_on_front')) {
            return 1.0;
        }
        
        // Top level pages get higher priority
        if (isset($page->post_parent) && $page->post_parent == 0) {
            return 0.8;
        }
        
        // Child pages get medium priority
        return 0.6;
    }
    
    /**
     * Calculate change frequency based on last modified
     */
    private function calculate_changefreq($last_modified) {
        $days_ago = (time() - strtotime($last_modified)) / DAY_IN_SECONDS;
        
        if ($days_ago < 7) {
            return 'weekly';
        } elseif ($days_ago < 30) {
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
     */
    public function get_items_seek($last_id = 0, $limit = 1000, $args = array()) {
        global $wpdb;
        
        $pages = $wpdb->get_results($wpdb->prepare("
            SELECT ID, post_modified_gmt, post_date_gmt
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'page'
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
        
        return $pages;
    }
    
    /**
     * Get URL data for a single item
     */
    public function get_url_data($page) {
        $url_data = array(
            'loc' => get_permalink($page->ID),
            'lastmod' => $page->post_modified_gmt,
            'changefreq' => $this->calculate_changefreq($page->post_modified_gmt),
            'priority' => $this->calculate_priority($page)
        );
        
        // Add featured image if exists
        $thumbnail_id = get_post_thumbnail_id($page->ID);
        if ($thumbnail_id) {
            $image_url = wp_get_attachment_url($thumbnail_id);
            if ($image_url) {
                $url_data['images'] = array(
                    array(
                        'loc' => $image_url,
                        'title' => get_the_title($page->ID)
                    )
                );
            }
        }
        
        return $url_data;
    }
    
}