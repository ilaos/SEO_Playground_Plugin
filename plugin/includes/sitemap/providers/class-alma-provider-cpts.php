<?php
/**
 * AlmaSEO Custom Post Types Sitemap Provider
 * 
 * @package AlmaSEO
 * @since 4.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Provider_CPTs {
    
    /**
     * Settings
     */
    private $settings;
    
    /**
     * Supported post types
     */
    private $post_types = array();
    
    /**
     * Constructor
     */
    public function __construct($settings) {
        $this->settings = $settings;
        $this->init_post_types();
    }
    
    /**
     * Initialize supported post types
     */
    private function init_post_types() {
        // Get all public custom post types
        $args = array(
            'public' => true,
            '_builtin' => false
        );
        
        $cpts = get_post_types($args, 'names');
        
        // Filter based on settings
        if (isset($this->settings['include']['cpts']) && $this->settings['include']['cpts'] === 'all') {
            $this->post_types = $cpts;
        } elseif (isset($this->settings['include']['cpts']) && is_array($this->settings['include']['cpts'])) {
            $this->post_types = array_intersect($cpts, $this->settings['include']['cpts']);
        } else {
            // Default to empty if no settings
            $this->post_types = array();
        }
        
        // Exclude any that shouldn't be indexed
        $this->post_types = array_filter($this->post_types, function($post_type) {
            $post_type_obj = get_post_type_object($post_type);
            return $post_type_obj && $post_type_obj->public && !$post_type_obj->exclude_from_search;
        });
    }
    
    /**
     * Get maximum number of pages
     */
    public function get_max_pages() {
        if (empty($this->post_types)) {
            return 0;
        }
        
        $total = $this->get_total_posts();
        $per_page = $this->settings['links_per_sitemap'];
        return (int) ceil($total / $per_page);
    }
    
    /**
     * Get total posts count across all CPTs
     */
    private function get_total_posts() {
        global $wpdb;
        
        if (empty($this->post_types)) {
            return 0;
        }
        
        $post_types_placeholder = implode(',', array_fill(0, count($this->post_types), '%s'));
        
        $query = $wpdb->prepare("
            SELECT COUNT(ID) 
            FROM {$wpdb->posts} p
            WHERE p.post_type IN ($post_types_placeholder)
            AND p.post_status = 'publish'
            AND p.post_password = ''
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} pm
                WHERE pm.post_id = p.ID
                AND pm.meta_key = '_almaseo_robots_noindex'
                AND pm.meta_value = '1'
            )
        ", ...$this->post_types);
        
        $count = $wpdb->get_var($query);
        
        return (int) $count;
    }
    
    /**
     * Get URLs for specific page
     */
    public function get_urls($page = 1) {
        global $wpdb;
        
        if (empty($this->post_types)) {
            return array();
        }
        
        $per_page = $this->settings['links_per_sitemap'];
        $offset = ($page - 1) * $per_page;
        
        $post_types_placeholder = implode(',', array_fill(0, count($this->post_types), '%s'));
        
        // Prepare query with post types and pagination
        $query_args = array_merge($this->post_types, array($per_page, $offset));
        
        $posts = $wpdb->get_results($wpdb->prepare("
            SELECT ID, post_type, post_modified_gmt, post_date_gmt
            FROM {$wpdb->posts} p
            WHERE p.post_type IN ($post_types_placeholder)
            AND p.post_status = 'publish'
            AND p.post_password = ''
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->postmeta} pm
                WHERE pm.post_id = p.ID
                AND pm.meta_key = '_almaseo_robots_noindex'
                AND pm.meta_value = '1'
            )
            ORDER BY p.post_type ASC, p.post_modified_gmt DESC
            LIMIT %d OFFSET %d
        ", ...$query_args));
        
        $urls = array();
        
        foreach ($posts as $post) {
            $url_data = array(
                'loc' => get_permalink($post->ID),
                'lastmod' => $post->post_modified_gmt,
                'changefreq' => $this->calculate_changefreq($post->post_modified_gmt),
                'priority' => $this->calculate_priority($post->post_type)
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
            
            $urls[] = $url_data;
        }
        
        return $urls;
    }
    
    /**
     * Get last modified date for page
     */
    public function get_last_modified($page = 1) {
        global $wpdb;
        
        if (empty($this->post_types)) {
            return null;
        }
        
        $per_page = $this->settings['links_per_sitemap'];
        $offset = ($page - 1) * $per_page;
        
        $post_types_placeholder = implode(',', array_fill(0, count($this->post_types), '%s'));
        $query_args = array_merge($this->post_types, array($per_page, $offset));
        
        $last_modified = $wpdb->get_var($wpdb->prepare("
            SELECT MAX(post_modified_gmt)
            FROM (
                SELECT post_modified_gmt
                FROM {$wpdb->posts} p
                WHERE p.post_type IN ($post_types_placeholder)
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
        ", ...$query_args));
        
        return $last_modified ? date('c', strtotime($last_modified)) : null;
    }
    
    /**
     * Calculate priority based on post type
     */
    private function calculate_priority($post_type) {
        // Products and important CPTs get higher priority
        if (in_array($post_type, array('product', 'service', 'portfolio'))) {
            return 0.8;
        }
        
        // Default priority for other CPTs
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
}