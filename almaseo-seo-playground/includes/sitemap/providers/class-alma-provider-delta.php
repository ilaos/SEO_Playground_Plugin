<?php
/**
 * AlmaSEO Delta Sitemap Provider
 * 
 * Provides recently changed URLs for delta sitemap
 * 
 * @package AlmaSEO
 * @since 4.8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Provider_Delta {
    
    /**
     * Settings
     */
    private $settings;
    
    /**
     * Ring buffer option name
     */
    const RING_OPTION = 'almaseo_delta_ring';
    
    /**
     * Constructor
     */
    public function __construct($settings) {
        $this->settings = $settings;
        $this->init_hooks();
    }
    
    /**
     * Initialize change capture hooks
     */
    private function init_hooks() {
        // Only hook if delta is enabled
        if (empty($this->settings['delta']['enabled'])) {
            return;
        }
        
        // Post status transitions
        add_action('transition_post_status', array($this, 'handle_post_transition'), 10, 3);
        add_action('save_post', array($this, 'handle_save_post'), 10, 2);
        add_action('deleted_post', array($this, 'handle_deleted_post'), 10, 2);
        add_action('wp_trash_post', array($this, 'handle_trash_post'));
        add_action('untrash_post', array($this, 'handle_untrash_post'));
        
        // Term changes
        add_action('created_term', array($this, 'handle_term_change'), 10, 3);
        add_action('edited_term', array($this, 'handle_term_change'), 10, 3);
        add_action('delete_term', array($this, 'handle_term_change'), 10, 4);
    }
    
    /**
     * Get maximum number of pages
     */
    public function get_max_pages() {
        $ring = $this->get_ring();
        $per_page = $this->settings['links_per_sitemap'];
        return (int) ceil(count($ring) / $per_page);
    }
    
    /**
     * Get URLs for specific page
     */
    public function get_urls($page = 1) {
        $ring = $this->get_ring();
        $per_page = $this->settings['links_per_sitemap'];
        $offset = ($page - 1) * $per_page;
        
        // Slice the ring for this page
        $items = array_slice($ring, $offset, $per_page);
        
        $urls = array();
        foreach ($items as $item) {
            // Double-check the URL is still valid
            if ($this->is_url_valid($item['url'])) {
                $urls[] = array(
                    'loc' => $item['url'],
                    'lastmod' => $item['lastmod'],
                    'changefreq' => 'hourly', // Recent changes are frequent
                    'priority' => 0.8 // Higher priority for recent changes
                );
            }
        }
        
        return $urls;
    }
    
    /**
     * Get last modified date for page
     */
    public function get_last_modified($page = 1) {
        $ring = $this->get_ring();
        if (empty($ring)) {
            return null;
        }
        
        // Most recent item's timestamp
        return date('c', $ring[0]['ts']);
    }
    
    /**
     * Get ring buffer
     */
    private function get_ring() {
        $ring = get_option(self::RING_OPTION, array());
        
        // Purge old entries
        $ring = $this->purge_old_entries($ring);
        
        return $ring;
    }
    
    /**
     * Purge entries older than retention days
     */
    private function purge_old_entries($ring) {
        $retention_days = $this->settings['delta']['retention_days'] ?? 14;
        $cutoff = time() - ($retention_days * DAY_IN_SECONDS);
        
        $filtered = array();
        foreach ($ring as $entry) {
            if ($entry['ts'] > $cutoff) {
                $filtered[] = $entry;
            }
        }
        
        // Update if changed
        if (count($filtered) !== count($ring)) {
            update_option(self::RING_OPTION, $filtered, false);
        }
        
        return $filtered;
    }
    
    /**
     * Add URL to ring buffer
     */
    public function add_to_ring($url, $reason = 'updated') {
        // Validate URL
        if (!$this->is_url_valid($url)) {
            return false;
        }
        
        $ring = get_option(self::RING_OPTION, array());
        $max_urls = $this->settings['delta']['max_urls'] ?? 500;
        
        // Create entry
        $entry = array(
            'url' => $url,
            'lastmod' => date('c'),
            'reason' => $reason,
            'ts' => time()
        );
        
        // Remove existing entry for this URL (dedupe)
        $ring = array_filter($ring, function($item) use ($url) {
            return $item['url'] !== $url;
        });
        
        // Add new entry at beginning
        array_unshift($ring, $entry);
        
        // Trim to max size
        if (count($ring) > $max_urls) {
            $ring = array_slice($ring, 0, $max_urls);
        }
        
        // Save
        update_option(self::RING_OPTION, $ring, false);
        
        // Also add to IndexNow queue if enabled
        if (!empty($this->settings['indexnow']['enabled'])) {
            $this->add_to_indexnow($url);
        }
        
        // Schedule debounced ping
        $this->schedule_ping();
        
        return true;
    }
    
    /**
     * Handle post status transition
     */
    public function handle_post_transition($new_status, $old_status, $post) {
        // Only track public post types
        if (!$this->is_post_eligible($post)) {
            return;
        }
        
        // Determine reason
        $reason = 'updated';
        if ($old_status === 'auto-draft' || $old_status === 'draft') {
            if ($new_status === 'publish') {
                $reason = 'created';
            }
        } elseif ($old_status === 'trash' && $new_status === 'publish') {
            $reason = 'restored';
        }
        
        // Add to ring if published
        if ($new_status === 'publish') {
            $this->add_to_ring(get_permalink($post->ID), $reason);
        }
    }
    
    /**
     * Handle save post
     */
    public function handle_save_post($post_id, $post) {
        // Skip autosaves and revisions
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        
        if ($post->post_status === 'publish' && $this->is_post_eligible($post)) {
            $this->add_to_ring(get_permalink($post_id), 'updated');
        }
    }
    
    /**
     * Handle deleted post
     */
    public function handle_deleted_post($post_id, $post) {
        // Remove from ring if present
        $ring = get_option(self::RING_OPTION, array());
        $permalink = get_permalink($post_id);
        
        $ring = array_filter($ring, function($item) use ($permalink) {
            return $item['url'] !== $permalink;
        });
        
        update_option(self::RING_OPTION, $ring, false);
    }
    
    /**
     * Handle trash post
     */
    public function handle_trash_post($post_id) {
        $post = get_post($post_id);
        if ($post) {
            $this->handle_deleted_post($post_id, $post);
        }
    }
    
    /**
     * Handle untrash post
     */
    public function handle_untrash_post($post_id) {
        $post = get_post($post_id);
        if ($post && $post->post_status === 'publish' && $this->is_post_eligible($post)) {
            $this->add_to_ring(get_permalink($post_id), 'restored');
        }
    }
    
    /**
     * Handle term changes
     */
    public function handle_term_change($term_id, $tt_id = null, $taxonomy = null) {
        // Check if taxonomy is in sitemaps
        $tax_settings = $this->settings['include']['tax'] ?? array();
        
        if (!isset($tax_settings[$taxonomy]) || !$tax_settings[$taxonomy]) {
            return;
        }
        
        // Best effort: add term archive URL
        $term_link = get_term_link($term_id, $taxonomy);
        if (!is_wp_error($term_link)) {
            $this->add_to_ring($term_link, 'term_changed');
        }
        
        // Also try to update affected posts (limited to recent ones)
        global $wpdb;
        $post_ids = $wpdb->get_col($wpdb->prepare("
            SELECT p.ID 
            FROM {$wpdb->posts} p
            INNER JOIN {$wpdb->term_relationships} tr ON p.ID = tr.object_id
            WHERE tr.term_taxonomy_id = %d
            AND p.post_status = 'publish'
            AND p.post_type IN ('post', 'page')
            ORDER BY p.post_modified_gmt DESC
            LIMIT 10
        ", $tt_id));
        
        foreach ($post_ids as $post_id) {
            $this->add_to_ring(get_permalink($post_id), 'term_changed');
        }
    }
    
    /**
     * Check if post is eligible for delta
     */
    private function is_post_eligible($post) {
        // Check post type
        if (!in_array($post->post_type, array('post', 'page')) && 
            !$this->is_cpt_included($post->post_type)) {
            return false;
        }
        
        // Check if has password
        if (!empty($post->post_password)) {
            return false;
        }
        
        // Check noindex
        $noindex = get_post_meta($post->ID, '_almaseo_robots_noindex', true);
        if ($noindex) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if CPT is included
     */
    private function is_cpt_included($post_type) {
        $cpt_setting = $this->settings['include']['cpts'] ?? '';
        
        if ($cpt_setting === 'all') {
            $cpt = get_post_type_object($post_type);
            return $cpt && $cpt->public;
        }
        
        return false;
    }
    
    /**
     * Validate URL
     */
    private function is_url_valid($url) {
        // Must be same host
        $site_host = parse_url(home_url(), PHP_URL_HOST);
        $url_host = parse_url($url, PHP_URL_HOST);
        
        return $site_host === $url_host;
    }
    
    /**
     * Add to IndexNow queue
     */
    private function add_to_indexnow($url) {
        $queue = get_option('almaseo_indexnow_queue', array());
        
        // Dedupe
        if (!in_array($url, $queue)) {
            $queue[] = $url;
            update_option('almaseo_indexnow_queue', $queue, false);
        }
    }
    
    /**
     * Schedule debounced ping
     */
    private function schedule_ping() {
        $min_interval = $this->settings['delta']['min_ping_interval'] ?? 900;
        
        // Check if already scheduled
        $next = wp_next_scheduled('almaseo_delta_ping');
        $now = time();
        
        if (!$next || ($next - $now) > $min_interval) {
            // Schedule in min_interval seconds
            wp_schedule_single_event($now + $min_interval, 'almaseo_delta_ping');
        }
    }
    
    /**
     * Execute ping (called by cron)
     */
    public static function execute_ping() {
        $settings = get_option('almaseo_sitemap_settings', array());
        
        // Check if enabled
        if (empty($settings['delta']['enabled']) || empty($settings['indexnow']['enabled'])) {
            return;
        }
        
        // Get URLs from ring
        $ring = get_option(self::RING_OPTION, array());
        if (empty($ring)) {
            return;
        }
        
        // Prepare URLs (limit to IndexNow batch size)
        $urls = array();
        $limit = 100; // IndexNow limit
        
        foreach (array_slice($ring, 0, $limit) as $entry) {
            $urls[] = $entry['url'];
        }
        
        // Also add sitemap index
        array_unshift($urls, home_url('/almaseo-sitemap.xml'));
        array_unshift($urls, home_url('/almaseo-sitemap-delta.xml'));
        
        // Submit via IndexNow
        require_once dirname(dirname(__FILE__)) . '/class-alma-indexnow.php';
        $indexnow = new Alma_IndexNow();
        $result = $indexnow->submit_batch($urls);
        
        // Record status
        if (!isset($settings['health'])) {
            $settings['health'] = array();
        }
        
        $settings['health']['delta_submit'] = array(
            'time' => time(),
            'count' => count($urls),
            'success' => !is_wp_error($result)
        );
        
        update_option('almaseo_sitemap_settings', $settings, false);
    }
    
    /**
     * Get items using seek pagination (for static generation)
     */
    public function get_items_seek($last_id = 0, $limit = 1000, $args = array()) {
        // For delta, we just return the ring items
        $ring = $this->get_ring();
        
        // Since ring is already limited, just return all
        return array_map(function($entry) {
            return (object)array(
                'url' => $entry['url'],
                'lastmod' => $entry['lastmod'],
                'reason' => $entry['reason'],
                'ts' => $entry['ts']
            );
        }, $ring);
    }
    
    /**
     * Get URL data for a single item
     */
    public function get_url_data($item) {
        return array(
            'loc' => $item->url,
            'lastmod' => $item->lastmod,
            'changefreq' => 'hourly',
            'priority' => 0.8
        );
    }
    
    /**
     * Get ring buffer stats
     */
    public static function get_stats() {
        $ring = get_option(self::RING_OPTION, array());
        $settings = get_option('almaseo_sitemap_settings', array());
        
        // Purge old first
        $retention_days = $settings['delta']['retention_days'] ?? 14;
        $cutoff = time() - ($retention_days * DAY_IN_SECONDS);
        
        $active = array_filter($ring, function($entry) use ($cutoff) {
            return $entry['ts'] > $cutoff;
        });
        
        return array(
            'count' => count($active),
            'max' => $settings['delta']['max_urls'] ?? 500,
            'oldest' => !empty($active) ? end($active)['ts'] : null,
            'newest' => !empty($active) ? $active[0]['ts'] : null
        );
    }
}

// Register cron hook
add_action('almaseo_delta_ping', array('Alma_Provider_Delta', 'execute_ping'));