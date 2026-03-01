<?php
/**
 * AlmaSEO News Sitemap Provider
 * 
 * Google News sitemap with rolling 48-hour window
 * 
 * @package AlmaSEO
 * @since 4.11.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Provider_News {
    
    /**
     * Settings
     */
    private $settings;
    
    /**
     * News settings
     */
    private $news_settings;
    
    /**
     * Constructor
     */
    public function __construct($settings = null) {
        $this->settings = $settings ?: get_option('almaseo_sitemap_settings', array());
        $this->news_settings = $this->settings['news'] ?? array(
            'enabled' => false,
            'post_types' => array('post'),
            'categories' => array(),
            'publisher_name' => get_bloginfo('name'),
            'language' => 'en',
            'genres' => array(),
            'keywords_source' => 'tags',
            'manual_keywords' => '',
            'max_items' => 1000,
            'window_hours' => 48
        );
    }
    
    /**
     * Get maximum number of pages
     */
    public function get_max_pages() {
        if (!$this->news_settings['enabled']) {
            return 0;
        }
        
        $total = $this->get_total_news_items();
        $per_page = $this->settings['links_per_sitemap'];
        
        return (int) ceil($total / $per_page);
    }
    
    /**
     * Get total news items in window
     */
    private function get_total_news_items() {
        global $wpdb;
        
        $window_date = gmdate('Y-m-d H:i:s', time() - ($this->news_settings['window_hours'] * 3600));
        $post_types = $this->news_settings['post_types'];
        
        if (empty($post_types)) {
            return 0;
        }
        
        // Build query
        $query = "
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            WHERE p.post_status = 'publish'
            AND p.post_type IN ('" . implode("','", array_map('esc_sql', $post_types)) . "')
            AND p.post_date_gmt > %s
            AND p.post_password = ''
        ";
        
        // Add category filter if specified
        if (!empty($this->news_settings['categories'])) {
            $query .= " AND EXISTS (
                SELECT 1 FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tr.object_id = p.ID
                AND tt.taxonomy = 'category'
                AND tt.term_id IN (" . implode(',', array_map('intval', $this->news_settings['categories'])) . ")
            )";
        }
        
        // Add category exclusion filter
        if (!empty($this->news_settings['exclude_categories'])) {
            $query .= " AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tr.object_id = p.ID
                AND tt.taxonomy = 'category'
                AND tt.term_id IN (" . implode(',', array_map('intval', $this->news_settings['exclude_categories'])) . ")
            )";
        }
        
        $count = $wpdb->get_var($wpdb->prepare($query, $window_date));
        
        // Apply max_items limit
        return min((int) $count, $this->news_settings['max_items']);
    }
    
    /**
     * Get URLs for specific page
     */
    public function get_urls($page = 1) {
        global $wpdb;
        
        if (!$this->news_settings['enabled']) {
            return array();
        }
        
        $per_page = $this->settings['links_per_sitemap'];
        $offset = ($page - 1) * $per_page;
        $window_date = gmdate('Y-m-d H:i:s', time() - ($this->news_settings['window_hours'] * 3600));
        $post_types = $this->news_settings['post_types'];
        
        if (empty($post_types)) {
            return array();
        }
        
        // Build query
        $query = "
            SELECT p.ID, p.post_title, p.post_date_gmt, p.post_modified_gmt
            FROM {$wpdb->posts} p
            WHERE p.post_status = 'publish'
            AND p.post_type IN ('" . implode("','", array_map('esc_sql', $post_types)) . "')
            AND p.post_date_gmt > %s
            AND p.post_password = ''
        ";
        
        // Add category filter (include)
        if (!empty($this->news_settings['categories'])) {
            $query .= " AND EXISTS (
                SELECT 1 FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tr.object_id = p.ID
                AND tt.taxonomy = 'category'
                AND tt.term_id IN (" . implode(',', array_map('intval', $this->news_settings['categories'])) . ")
            )";
        }
        
        // Add category exclusion filter
        if (!empty($this->news_settings['exclude_categories'])) {
            $query .= " AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tr.object_id = p.ID
                AND tt.taxonomy = 'category'
                AND tt.term_id IN (" . implode(',', array_map('intval', $this->news_settings['exclude_categories'])) . ")
            )";
        }
        
        $query .= " ORDER BY p.post_date_gmt DESC LIMIT %d OFFSET %d";
        
        $posts = $wpdb->get_results($wpdb->prepare($query, $window_date, $per_page, $offset));
        
        $urls = array();
        
        foreach ($posts as $post) {
            // Check noindex
            $noindex = get_post_meta($post->ID, '_almaseo_robots_noindex', true);
            if ($noindex) {
                continue;
            }
            
            // Build news data
            $news_data = $this->build_news_data($post);
            
            $url_data = array(
                'loc' => get_permalink($post->ID),
                'lastmod' => $post->post_modified_gmt,
                'news' => $news_data
            );
            
            $urls[] = $url_data;
            
            // Stop if we've hit max_items
            if (count($urls) >= $this->news_settings['max_items']) {
                break;
            }
        }
        
        return $urls;
    }
    
    /**
     * Get news data for a post (public for testing)
     */
    public function get_news_data($post) {
        return $this->build_news_data($post);
    }
    
    /**
     * Build news data for a post
     */
    private function build_news_data($post) {
        $news = array(
            'publication' => array(
                'name' => $this->news_settings['publisher_name'],
                'language' => $this->news_settings['language']
            ),
            'publication_date' => gmdate('c', strtotime($post->post_date_gmt)),
            'title' => $post->post_title
        );
        
        // Add genres if specified
        if (!empty($this->news_settings['genres'])) {
            $news['genres'] = implode(',', $this->news_settings['genres']);
        }
        
        // Add keywords
        $keywords = $this->get_keywords($post->ID);
        if (!empty($keywords)) {
            $news['keywords'] = $keywords;
        }
        
        return $news;
    }
    
    /**
     * Get keywords for a post
     */
    private function get_keywords($post_id) {
        if ($this->news_settings['keywords_source'] === 'manual') {
            // Use manual keywords for all posts
            return $this->news_settings['manual_keywords'];
        }
        
        // Get tags
        $tags = wp_get_post_tags($post_id, array('fields' => 'names'));
        
        if (empty($tags)) {
            // Fall back to manual keywords if no tags
            return $this->news_settings['manual_keywords'];
        }
        
        // Limit to 10 keywords per Google's guidance
        $tags = array_slice($tags, 0, 10);
        
        return implode(', ', $tags);
    }
    
    /**
     * Get last modified date for page
     */
    public function get_last_modified($page = 1) {
        global $wpdb;
        
        $per_page = $this->settings['links_per_sitemap'];
        $offset = ($page - 1) * $per_page;
        $window_date = gmdate('Y-m-d H:i:s', time() - ($this->news_settings['window_hours'] * 3600));
        $post_types = $this->news_settings['post_types'];
        
        if (empty($post_types)) {
            return null;
        }
        
        $query = "
            SELECT MAX(post_modified_gmt)
            FROM (
                SELECT p.post_modified_gmt
                FROM {$wpdb->posts} p
                WHERE p.post_status = 'publish'
                AND p.post_type IN ('" . implode("','", array_map('esc_sql', $post_types)) . "')
                AND p.post_date_gmt > %s
                AND p.post_password = ''
        ";
        
        if (!empty($this->news_settings['categories'])) {
            $query .= " AND EXISTS (
                SELECT 1 FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tr.object_id = p.ID
                AND tt.taxonomy = 'category'
                AND tt.term_id IN (" . implode(',', array_map('intval', $this->news_settings['categories'])) . ")
            )";
        }
        
        $query .= " ORDER BY p.post_date_gmt DESC LIMIT %d OFFSET %d
            ) as subset
        ";
        
        $last_modified = $wpdb->get_var($wpdb->prepare($query, $window_date, $per_page, $offset));
        
        return $last_modified ? date('c', strtotime($last_modified)) : null;
    }
    
    /**
     * Check if provider supports news
     */
    public function supports_news() {
        return true;
    }
    
    /**
     * Get items using seek pagination (for static generation)
     */
    public function get_items_seek($last_id = 0, $limit = 1000, $args = array()) {
        global $wpdb;
        
        $window_date = gmdate('Y-m-d H:i:s', time() - ($this->news_settings['window_hours'] * 3600));
        $post_types = $this->news_settings['post_types'];
        
        if (empty($post_types)) {
            return array();
        }
        
        $query = "
            SELECT p.ID, p.post_title, p.post_date_gmt, p.post_modified_gmt
            FROM {$wpdb->posts} p
            WHERE p.post_status = 'publish'
            AND p.post_type IN ('" . implode("','", array_map('esc_sql', $post_types)) . "')
            AND p.post_date_gmt > %s
            AND p.post_password = ''
            AND p.ID > %d
        ";
        
        if (!empty($this->news_settings['categories'])) {
            $query .= " AND EXISTS (
                SELECT 1 FROM {$wpdb->term_relationships} tr
                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
                WHERE tr.object_id = p.ID
                AND tt.taxonomy = 'category'
                AND tt.term_id IN (" . implode(',', array_map('intval', $this->news_settings['categories'])) . ")
            )";
        }
        
        $query .= " ORDER BY p.ID ASC LIMIT %d";
        
        $posts = $wpdb->get_results($wpdb->prepare($query, $window_date, $last_id, $limit));
        
        // Apply max_items limit
        $total_so_far = isset($args['total_so_far']) ? $args['total_so_far'] : 0;
        $remaining = $this->news_settings['max_items'] - $total_so_far;
        
        if ($remaining <= 0) {
            return array();
        }
        
        return array_slice($posts, 0, $remaining);
    }
    
    /**
     * Get URL data for a single item
     */
    public function get_url_data($post) {
        // Check noindex
        $noindex = get_post_meta($post->ID, '_almaseo_robots_noindex', true);
        if ($noindex) {
            return null;
        }
        
        $news_data = $this->build_news_data($post);
        
        return array(
            'loc' => get_permalink($post->ID),
            'lastmod' => $post->post_modified_gmt,
            'news' => $news_data
        );
    }
    
    /**
     * Get stats
     */
    public function get_stats() {
        $items_count = $this->get_total_news_items();
        $window_date = gmdate('Y-m-d H:i:s', time() - ($this->news_settings['window_hours'] * 3600));
        
        return array(
            'items' => $items_count,
            'window_hours' => $this->news_settings['window_hours'],
            'window_start' => $window_date,
            'post_types' => $this->news_settings['post_types'],
            'categories' => $this->news_settings['categories'],
            'last_build' => get_option('almaseo_news_last_build', 0)
        );
    }
    
    /**
     * Trigger rebuild on post update
     */
    public static function maybe_rebuild_on_post_update($post_id, $post = null) {
        // Get settings
        $settings = get_option('almaseo_sitemap_settings', array());
        $news_settings = $settings['news'] ?? array();
        
        if (empty($news_settings['enabled'])) {
            return;
        }
        
        // Check if post is eligible
        if (!$post) {
            $post = get_post($post_id);
        }
        
        if ($post->post_status !== 'publish') {
            return;
        }
        
        if (!in_array($post->post_type, $news_settings['post_types'])) {
            return;
        }
        
        // Check if within window
        $window_hours = $news_settings['window_hours'] ?? 48;
        $post_age_hours = (time() - strtotime($post->post_date_gmt)) / 3600;
        
        if ($post_age_hours > $window_hours) {
            return;
        }
        
        // Check categories if filtered
        if (!empty($news_settings['categories'])) {
            $post_categories = wp_get_post_categories($post_id);
            $intersect = array_intersect($post_categories, $news_settings['categories']);
            if (empty($intersect)) {
                return;
            }
        }
        
        // Queue or trigger rebuild
        if ($settings['perf']['storage_mode'] === 'static') {
            // For static mode, set a flag for next cron run
            update_option('almaseo_news_needs_rebuild', true, false);
        }
    }
}