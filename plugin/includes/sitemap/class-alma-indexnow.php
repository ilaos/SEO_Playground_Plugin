<?php
/**
 * AlmaSEO IndexNow Integration
 * 
 * Handles IndexNow API submissions for instant indexing
 * 
 * @package AlmaSEO
 * @since 4.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_IndexNow {
    
    /**
     * Queue transient key
     */
    const QUEUE_KEY = 'almaseo_indexnow_queue';
    
    /**
     * Maximum URLs in queue
     */
    const MAX_QUEUE_SIZE = 100;
    
    /**
     * Settings
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->load_settings();
        $this->init_hooks();
    }
    
    /**
     * Load settings
     */
    private function load_settings() {
        $sitemap_settings = get_option('almaseo_sitemap_settings', array());
        
        $defaults = array(
            'enabled' => false,
            'key' => '',
            'endpoint' => 'https://api.indexnow.org/indexnow'
        );
        
        $this->settings = wp_parse_args(
            $sitemap_settings['indexnow'] ?? array(),
            $defaults
        );
    }
    
    /**
     * Initialize hooks for change tracking
     */
    private function init_hooks() {
        // Track post changes
        add_action('save_post', array($this, 'track_post_change'), 10, 3);
        add_action('deleted_post', array($this, 'track_post_deletion'));
        add_action('transition_post_status', array($this, 'track_status_change'), 10, 3);
        
        // Track term changes
        add_action('created_term', array($this, 'track_term_change'));
        add_action('edited_term', array($this, 'track_term_change'));
        add_action('delete_term', array($this, 'track_term_change'));
    }
    
    /**
     * Track post changes
     */
    public function track_post_change($post_id, $post, $update) {
        // Skip autosaves, revisions, and non-public posts
        if (wp_is_post_autosave($post_id) || wp_is_post_revision($post_id)) {
            return;
        }
        
        if ($post->post_status !== 'publish') {
            return;
        }
        
        $permalink = get_permalink($post_id);
        if ($permalink) {
            $this->add_to_queue($permalink);
        }
    }
    
    /**
     * Track post deletion
     */
    public function track_post_deletion($post_id) {
        $permalink = get_permalink($post_id);
        if ($permalink) {
            $this->add_to_queue($permalink);
        }
    }
    
    /**
     * Track post status changes
     */
    public function track_status_change($new_status, $old_status, $post) {
        if ($new_status === 'publish' || $old_status === 'publish') {
            $permalink = get_permalink($post->ID);
            if ($permalink) {
                $this->add_to_queue($permalink);
            }
        }
    }
    
    /**
     * Track term changes
     */
    public function track_term_change($term_id) {
        $term = get_term($term_id);
        if (!is_wp_error($term)) {
            $term_link = get_term_link($term);
            if (!is_wp_error($term_link)) {
                $this->add_to_queue($term_link);
            }
        }
    }
    
    /**
     * Add URL to queue
     */
    private function add_to_queue($url) {
        $queue = get_transient(self::QUEUE_KEY) ?: array();
        
        // Deduplicate
        if (!in_array($url, $queue)) {
            $queue[] = $url;
            
            // Limit queue size
            if (count($queue) > self::MAX_QUEUE_SIZE) {
                $queue = array_slice($queue, -self::MAX_QUEUE_SIZE);
            }
            
            set_transient(self::QUEUE_KEY, $queue, DAY_IN_SECONDS);
        }
    }
    
    /**
     * Get queued URLs
     */
    public function get_queue() {
        return get_transient(self::QUEUE_KEY) ?: array();
    }
    
    /**
     * Clear queue
     */
    public function clear_queue() {
        delete_transient(self::QUEUE_KEY);
    }
    
    /**
     * Generate new API key
     */
    public static function generate_key() {
        return wp_generate_password(32, false);
    }
    
    /**
     * Create key file in root
     */
    public function create_key_file() {
        if (empty($this->settings['key'])) {
            return array('success' => false, 'message' => __('No IndexNow key configured', 'almaseo'));
        }
        
        $key = $this->settings['key'];
        $filename = $key . '.txt';
        $filepath = ABSPATH . $filename;
        
        // Write key file
        $result = file_put_contents($filepath, $key);
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => sprintf(__('Failed to create key file at %s', 'almaseo'), $filepath)
            );
        }
        
        return array(
            'success' => true,
            'message' => __('IndexNow key file created successfully', 'almaseo'),
            'path' => $filepath,
            'url' => home_url('/' . $filename)
        );
    }
    
    /**
     * Submit URLs to IndexNow
     * 
     * @param array $urls URLs to submit (if empty, uses queue + sitemap index)
     * @return array Result with success status and message
     */
    public function submit($urls = array()) {
        // Check if enabled
        if (!$this->settings['enabled']) {
            return array(
                'success' => false,
                'message' => __('IndexNow is not enabled', 'almaseo')
            );
        }
        
        // Check for key
        if (empty($this->settings['key'])) {
            return array(
                'success' => false,
                'message' => __('IndexNow key not configured', 'almaseo')
            );
        }
        
        // Ensure key file exists
        $key_file_result = $this->create_key_file();
        if (!$key_file_result['success']) {
            return $key_file_result;
        }
        
        // Build URL list
        if (empty($urls)) {
            $urls = $this->get_queue();
        }
        
        // Always include sitemap index
        $sitemap_url = home_url('/almaseo-sitemap.xml');
        if (!in_array($sitemap_url, $urls)) {
            array_unshift($urls, $sitemap_url);
        }
        
        // Also check for takeover sitemap
        $sitemap_settings = get_option('almaseo_sitemap_settings', array());
        if (!empty($sitemap_settings['takeover'])) {
            $takeover_url = home_url('/sitemap.xml');
            if (!in_array($takeover_url, $urls)) {
                $urls[] = $takeover_url;
            }
        }
        
        // Limit to 10000 URLs per IndexNow spec
        if (count($urls) > 10000) {
            $urls = array_slice($urls, 0, 10000);
        }
        
        // Prepare request body
        $body = array(
            'host' => parse_url(home_url(), PHP_URL_HOST),
            'key' => $this->settings['key'],
            'keyLocation' => home_url('/' . $this->settings['key'] . '.txt'),
            'urlList' => $urls
        );
        
        // Submit to IndexNow
        $response = wp_remote_post($this->settings['endpoint'], array(
            'timeout' => 10,
            'headers' => array(
                'Content-Type' => 'application/json; charset=utf-8'
            ),
            'body' => wp_json_encode($body)
        ));
        
        // Handle response
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('IndexNow submission error: ' . $response->get_error_message());
            }
            
            return array(
                'success' => false,
                'message' => sprintf(__('IndexNow submission failed: %s', 'almaseo'), $response->get_error_message())
            );
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        
        // IndexNow returns 200/202 on success
        if ($status_code === 200 || $status_code === 202) {
            // Clear queue on success
            $this->clear_queue();
            
            // Update health status
            $this->update_health_status('success', count($urls), $status_code);
            
            return array(
                'success' => true,
                'message' => sprintf(
                    __('Successfully submitted %d URLs to IndexNow', 'almaseo'),
                    count($urls)
                ),
                'count' => count($urls),
                'status' => $status_code
            );
        }
        
        // Handle specific error codes
        $error_message = $this->get_error_message($status_code, $body);
        
        // Update health status
        $this->update_health_status('error', 0, $status_code, $error_message);
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('IndexNow returned %d: %s', $status_code, $body));
        }
        
        return array(
            'success' => false,
            'message' => $error_message,
            'status' => $status_code
        );
    }
    
    /**
     * Get error message for status code
     */
    private function get_error_message($status_code, $body) {
        switch ($status_code) {
            case 400:
                return __('Invalid request format', 'almaseo');
            case 403:
                return __('Invalid or missing API key', 'almaseo');
            case 422:
                return __('URLs do not belong to this host or key location is invalid', 'almaseo');
            case 429:
                return __('Too many requests - please try again later', 'almaseo');
            default:
                return sprintf(__('IndexNow returned status %d', 'almaseo'), $status_code);
        }
    }
    
    /**
     * Update health status
     */
    private function update_health_status($status, $count, $http_code, $message = '') {
        $settings = get_option('almaseo_sitemap_settings', array());
        
        if (!isset($settings['health'])) {
            $settings['health'] = array();
        }
        
        $settings['health']['indexnow_last_submit'] = array(
            'timestamp' => time(),
            'status' => $status,
            'count' => $count,
            'http_code' => $http_code,
            'message' => $message
        );
        
        update_option('almaseo_sitemap_settings', $settings);
    }
    
    /**
     * Check if key file exists
     */
    public function key_file_exists() {
        if (empty($this->settings['key'])) {
            return false;
        }
        
        $filepath = ABSPATH . $this->settings['key'] . '.txt';
        return file_exists($filepath);
    }
    
    /**
     * Delete key file
     */
    public function delete_key_file() {
        if (empty($this->settings['key'])) {
            return false;
        }
        
        $filepath = ABSPATH . $this->settings['key'] . '.txt';
        if (file_exists($filepath)) {
            return unlink($filepath);
        }
        
        return true;
    }
}