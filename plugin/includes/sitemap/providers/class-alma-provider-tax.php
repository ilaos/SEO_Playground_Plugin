<?php
/**
 * AlmaSEO Taxonomies Sitemap Provider
 * 
 * @package AlmaSEO
 * @since 4.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Provider_Tax {
    
    /**
     * Settings
     */
    private $settings;
    
    /**
     * Supported taxonomies
     */
    private $taxonomies = array();
    
    /**
     * Constructor
     */
    public function __construct($settings) {
        $this->settings = $settings;
        $this->init_taxonomies();
    }
    
    /**
     * Initialize supported taxonomies
     */
    private function init_taxonomies() {
        $tax_settings = $this->settings['include']['tax'];
        
        if (!is_array($tax_settings)) {
            return;
        }
        
        foreach ($tax_settings as $taxonomy => $enabled) {
            if ($enabled && taxonomy_exists($taxonomy)) {
                $tax_obj = get_taxonomy($taxonomy);
                
                // Only include public taxonomies
                if ($tax_obj && $tax_obj->public && $tax_obj->publicly_queryable) {
                    $this->taxonomies[] = $taxonomy;
                }
            }
        }
    }
    
    /**
     * Get maximum number of pages
     */
    public function get_max_pages() {
        if (empty($this->taxonomies)) {
            return 0;
        }
        
        $total = $this->get_total_terms();
        $per_page = $this->settings['links_per_sitemap'];
        return (int) ceil($total / $per_page);
    }
    
    /**
     * Get total terms count across all taxonomies
     */
    private function get_total_terms() {
        global $wpdb;
        
        if (empty($this->taxonomies)) {
            return 0;
        }
        
        $tax_placeholder = implode(',', array_fill(0, count($this->taxonomies), '%s'));
        
        $query = $wpdb->prepare("
            SELECT COUNT(DISTINCT t.term_id)
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy IN ($tax_placeholder)
            AND tt.count > 0
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->termmeta} tm
                WHERE tm.term_id = t.term_id
                AND tm.meta_key = '_almaseo_robots_noindex'
                AND tm.meta_value = '1'
            )
        ", ...$this->taxonomies);
        
        $count = $wpdb->get_var($query);
        
        return (int) $count;
    }
    
    /**
     * Get URLs for specific page
     */
    public function get_urls($page = 1) {
        global $wpdb;
        
        if (empty($this->taxonomies)) {
            return array();
        }
        
        $per_page = $this->settings['links_per_sitemap'];
        $offset = ($page - 1) * $per_page;
        
        $tax_placeholder = implode(',', array_fill(0, count($this->taxonomies), '%s'));
        $query_args = array_merge($this->taxonomies, array($per_page, $offset));
        
        $terms = $wpdb->get_results($wpdb->prepare("
            SELECT t.term_id, t.slug, tt.taxonomy, tt.count
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy IN ($tax_placeholder)
            AND tt.count > 0
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->termmeta} tm
                WHERE tm.term_id = t.term_id
                AND tm.meta_key = '_almaseo_robots_noindex'
                AND tm.meta_value = '1'
            )
            ORDER BY tt.taxonomy ASC, tt.count DESC
            LIMIT %d OFFSET %d
        ", ...$query_args));
        
        $urls = array();
        
        foreach ($terms as $term) {
            $term_link = get_term_link((int) $term->term_id, $term->taxonomy);
            
            if (is_wp_error($term_link)) {
                continue;
            }
            
            $url_data = array(
                'loc' => $term_link,
                'changefreq' => $this->calculate_changefreq($term->count),
                'priority' => $this->calculate_priority($term->taxonomy, $term->count)
            );
            
            // Get last modified based on latest post in this term
            $last_modified = $this->get_term_last_modified($term->term_id, $term->taxonomy);
            if ($last_modified) {
                $url_data['lastmod'] = $last_modified;
            }
            
            $urls[] = $url_data;
        }
        
        return $urls;
    }
    
    /**
     * Get last modified date for a term based on its posts
     */
    private function get_term_last_modified($term_id, $taxonomy) {
        global $wpdb;
        
        $last_modified = $wpdb->get_var($wpdb->prepare("
            SELECT MAX(p.post_modified_gmt)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            WHERE tt.term_id = %d
            AND tt.taxonomy = %s
            AND p.post_status = 'publish'
        ", $term_id, $taxonomy));
        
        return $last_modified ? date('c', strtotime($last_modified)) : null;
    }
    
    /**
     * Get last modified date for page
     */
    public function get_last_modified($page = 1) {
        global $wpdb;
        
        if (empty($this->taxonomies)) {
            return null;
        }
        
        // Get the most recent post modification across all terms on this page
        $per_page = $this->settings['links_per_sitemap'];
        $offset = ($page - 1) * $per_page;
        
        $tax_placeholder = implode(',', array_fill(0, count($this->taxonomies), '%s'));
        $query_args = array_merge($this->taxonomies, array($per_page, $offset));
        
        $last_modified = $wpdb->get_var($wpdb->prepare("
            SELECT MAX(last_post_modified)
            FROM (
                SELECT MAX(p.post_modified_gmt) as last_post_modified
                FROM {$wpdb->terms} t
                INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                INNER JOIN {$wpdb->term_relationships} tr ON tt.term_taxonomy_id = tr.term_taxonomy_id
                INNER JOIN {$wpdb->posts} p ON tr.object_id = p.ID
                WHERE tt.taxonomy IN ($tax_placeholder)
                AND tt.count > 0
                AND p.post_status = 'publish'
                AND NOT EXISTS (
                    SELECT 1 FROM {$wpdb->termmeta} tm
                    WHERE tm.term_id = t.term_id
                    AND tm.meta_key = '_almaseo_robots_noindex'
                    AND tm.meta_value = '1'
                )
                GROUP BY t.term_id
                ORDER BY tt.taxonomy ASC, tt.count DESC
                LIMIT %d OFFSET %d
            ) as subset
        ", ...$query_args));
        
        return $last_modified ? date('c', strtotime($last_modified)) : null;
    }
    
    /**
     * Calculate priority based on taxonomy and post count
     */
    private function calculate_priority($taxonomy, $count) {
        // Categories and tags with many posts get higher priority
        if (in_array($taxonomy, array('category', 'post_tag'))) {
            if ($count > 10) {
                return 0.8;
            } elseif ($count > 5) {
                return 0.6;
            }
        }
        
        // Default priority
        return 0.4;
    }
    
    /**
     * Calculate change frequency based on post count
     */
    private function calculate_changefreq($count) {
        // More active categories change more frequently
        if ($count > 20) {
            return 'daily';
        } elseif ($count > 10) {
            return 'weekly';
        } else {
            return 'monthly';
        }
    }
    
    /**
     * Get items using seek pagination (Phase 4)
     */
    public function get_items_seek($last_id = 0, $limit = 1000, $args = array()) {
        global $wpdb;
        
        $taxonomies = $this->get_enabled_taxonomies();
        $tax_sql = "'" . implode("','", array_map('esc_sql', $taxonomies)) . "'";
        
        $terms = $wpdb->get_results($wpdb->prepare("
            SELECT t.term_id, t.name, tt.taxonomy, tt.count
            FROM {$wpdb->terms} t
            INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
            WHERE tt.taxonomy IN ($tax_sql)
            AND tt.count > 0
            AND t.term_id > %d
            AND NOT EXISTS (
                SELECT 1 FROM {$wpdb->termmeta} tm
                WHERE tm.term_id = t.term_id
                AND tm.meta_key = '_almaseo_robots_noindex'
                AND tm.meta_value = '1'
            )
            ORDER BY t.term_id ASC
            LIMIT %d
        ", $last_id, $limit));
        
        return $terms;
    }
    
    /**
     * Get URL data for a single term
     */
    public function get_url_data($term) {
        $url_data = array(
            'loc' => get_term_link($term->term_id, $term->taxonomy),
            'changefreq' => $this->calculate_changefreq($term->count),
            'priority' => 0.5
        );
        
        // Get last modified from latest post in term
        global $wpdb;
        $last_modified = $wpdb->get_var($wpdb->prepare("
            SELECT MAX(p.post_modified_gmt)
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            WHERE tr.term_taxonomy_id = %d
            AND p.post_status = 'publish'
        ", $term->term_id));
        
        if ($last_modified) {
            $url_data['lastmod'] = $last_modified;
        }
        
        return $url_data;
    }
    
    /**
     * Get enabled taxonomies
     */
    private function get_enabled_taxonomies() {
        $taxonomies = array();
        $tax_settings = $this->settings['include']['tax'];
        
        if (is_array($tax_settings)) {
            foreach ($tax_settings as $tax => $enabled) {
                if ($enabled) {
                    $taxonomies[] = $tax;
                }
            }
        }
        
        return $taxonomies;
    }
}