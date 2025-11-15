<?php
/**
 * AlmaSEO Users Sitemap Provider
 * 
 * @package AlmaSEO
 * @since 4.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Provider_Users {
    
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
        $total = $this->get_total_users();
        $per_page = $this->settings['links_per_sitemap'];
        return (int) ceil($total / $per_page);
    }
    
    /**
     * Get total users count (authors with published posts)
     */
    private function get_total_users() {
        global $wpdb;
        
        // Only include users who have published posts
        $count = $wpdb->get_var("
            SELECT COUNT(DISTINCT u.ID)
            FROM {$wpdb->users} u
            WHERE EXISTS (
                SELECT 1 FROM {$wpdb->posts} p
                WHERE p.post_author = u.ID
                AND p.post_status = 'publish'
                AND p.post_type IN ('post', 'page')
            )
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->usermeta} um
                WHERE um.user_id = u.ID
                AND um.meta_key = '_almaseo_robots_noindex'
                AND um.meta_value = '1'
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
        
        // Query users with published posts
        $users = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT u.ID, u.user_nicename, u.display_name,
                (SELECT COUNT(*) FROM {$wpdb->posts} p 
                 WHERE p.post_author = u.ID 
                 AND p.post_status = 'publish'
                 AND p.post_type IN ('post', 'page')) as post_count,
                (SELECT MAX(p.post_modified_gmt) FROM {$wpdb->posts} p 
                 WHERE p.post_author = u.ID 
                 AND p.post_status = 'publish'
                 AND p.post_type IN ('post', 'page')) as last_post_modified
            FROM {$wpdb->users} u
            WHERE EXISTS (
                SELECT 1 FROM {$wpdb->posts} p
                WHERE p.post_author = u.ID
                AND p.post_status = 'publish'
                AND p.post_type IN ('post', 'page')
            )
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->usermeta} um
                WHERE um.user_id = u.ID
                AND um.meta_key = '_almaseo_robots_noindex'
                AND um.meta_value = '1'
            )
            ORDER BY post_count DESC, u.ID ASC
            LIMIT %d OFFSET %d
        ", $per_page, $offset));
        
        $urls = array();
        
        foreach ($users as $user) {
            $author_url = get_author_posts_url($user->ID, $user->user_nicename);
            
            $url_data = array(
                'loc' => $author_url,
                'changefreq' => $this->calculate_changefreq($user->last_post_modified),
                'priority' => $this->calculate_priority($user->post_count)
            );
            
            if ($user->last_post_modified) {
                $url_data['lastmod'] = date('c', strtotime($user->last_post_modified));
            }
            
            // Add author avatar as image if available
            $avatar_url = get_avatar_url($user->ID, array('size' => 512));
            if ($avatar_url && !strpos($avatar_url, 'gravatar.com/avatar/0')) {
                $url_data['images'] = array(
                    array(
                        'loc' => $avatar_url,
                        'title' => $user->display_name
                    )
                );
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
            SELECT MAX(last_post_modified)
            FROM (
                SELECT MAX(p.post_modified_gmt) as last_post_modified
                FROM {$wpdb->users} u
                INNER JOIN {$wpdb->posts} p ON u.ID = p.post_author
                WHERE p.post_status = 'publish'
                AND p.post_type IN ('post', 'page')
                AND NOT EXISTS (
                    SELECT 1 FROM {$wpdb->usermeta} um
                    WHERE um.user_id = u.ID
                    AND um.meta_key = '_almaseo_robots_noindex'
                    AND um.meta_value = '1'
                )
                GROUP BY u.ID
                ORDER BY COUNT(p.ID) DESC, u.ID ASC
                LIMIT %d OFFSET %d
            ) as subset
        ", $per_page, $offset));
        
        return $last_modified ? date('c', strtotime($last_modified)) : null;
    }
    
    /**
     * Calculate priority based on post count
     */
    private function calculate_priority($post_count) {
        // Prolific authors get higher priority
        if ($post_count > 50) {
            return 0.8;
        } elseif ($post_count > 20) {
            return 0.6;
        } elseif ($post_count > 10) {
            return 0.5;
        } else {
            return 0.3;
        }
    }
    
    /**
     * Calculate change frequency based on last post
     */
    private function calculate_changefreq($last_modified) {
        if (!$last_modified) {
            return 'yearly';
        }
        
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