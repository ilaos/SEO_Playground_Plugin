<?php
/**
 * AlmaSEO Health Score Feature - Loader
 * 
 * @package AlmaSEO
 * @subpackage Health
 * @since 6.8.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('ALMASEO_HEALTH_SCORE_META', '_almaseo_health_score');
define('ALMASEO_HEALTH_BREAKDOWN_META', '_almaseo_health_breakdown');
define('ALMASEO_HEALTH_UPDATED_META', '_almaseo_health_updated_at');
define('ALMASEO_FOCUS_KEYWORD_META', '_almaseo_focus_keyword');

/**
 * Initialize Health Score feature
 */
class AlmaSEO_Health_Loader {
    
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
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        require_once dirname(__FILE__) . '/weights.php';
        require_once dirname(__FILE__) . '/analyzer.php';
        
        if (is_admin()) {
            require_once dirname(__FILE__) . '/ui.php';
            require_once dirname(__FILE__) . '/compact-view.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Calculate on save (priority 25 to run after main save at default priority 10)
        add_action('save_post', array($this, 'calculate_on_save'), 25, 2);
        
        // AJAX handlers
        add_action('wp_ajax_almaseo_health_recalculate', array($this, 'ajax_recalculate'));
        add_action('wp_ajax_almaseo_health_refresh', array($this, 'ajax_health_refresh'));
        add_action('wp_ajax_almaseo_health_live_update', array($this, 'ajax_live_update'));
        add_action('wp_ajax_almaseo_health_draft_meta_desc', array($this, 'ajax_draft_meta_desc'));
        add_action('wp_ajax_almaseo_get_keyword_suggestions', array($this, 'ajax_get_keyword_suggestions'));
        
        // Enqueue scripts
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Calculate health score on post save
     */
    public function calculate_on_save($post_id, $post) {
        // Skip autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check post type
        if (!in_array($post->post_type, array('post', 'page'))) {
            return;
        }
        
        // Skip revisions
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Clear any health cache/transients for this post
        delete_transient('almaseo_health_' . $post_id);
        delete_transient('almaseo_health_cache_' . $post_id);
        
        // Calculate and save
        $result = almaseo_health_calculate($post_id);
        
        update_post_meta($post_id, ALMASEO_HEALTH_SCORE_META, $result['score']);
        update_post_meta($post_id, ALMASEO_HEALTH_BREAKDOWN_META, json_encode($result['breakdown']));
        update_post_meta($post_id, ALMASEO_HEALTH_UPDATED_META, current_time('timestamp'));
    }
    
    /**
     * AJAX handler for recalculation
     */
    public function ajax_recalculate() {
        check_ajax_referer('almaseo_health_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die();
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || !get_post($post_id)) {
            wp_send_json_error('Invalid post ID');
        }
        
        // Calculate
        $result = almaseo_health_calculate($post_id);
        
        // Save to meta
        update_post_meta($post_id, ALMASEO_HEALTH_SCORE_META, $result['score']);
        update_post_meta($post_id, ALMASEO_HEALTH_BREAKDOWN_META, json_encode($result['breakdown']));
        update_post_meta($post_id, ALMASEO_HEALTH_UPDATED_META, current_time('timestamp'));
        
        // Return result
        wp_send_json_success(array(
            'score' => $result['score'],
            'breakdown' => $result['breakdown'],
            'updated_at' => human_time_diff(current_time('timestamp')) . ' ' . __('ago', 'almaseo')
        ));
    }
    
    /**
     * AJAX handler for stable health refresh - returns complete data
     */
    public function ajax_health_refresh() {
        // Set headers to prevent caching
        nocache_headers();
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        check_ajax_referer('almaseo_health_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die();
        }
        
        $post_id = intval($_POST['post_id']);
        $token = isset($_POST['token']) ? intval($_POST['token']) : 0;
        $reason = isset($_POST['reason']) ? sanitize_text_field($_POST['reason']) : 'manual';
        
        if (!$post_id || !get_post($post_id)) {
            wp_send_json_error('Invalid post ID');
        }
        
        // Clear any cached data for this post
        delete_transient('almaseo_health_' . $post_id);
        delete_transient('almaseo_health_cache_' . $post_id);
        
        // Save any updated meta fields if provided
        if (isset($_POST['meta_fields']) && is_array($_POST['meta_fields'])) {
            $meta_fields = $_POST['meta_fields'];
            
            if (isset($meta_fields['title'])) {
                update_post_meta($post_id, '_almaseo_title', sanitize_text_field($meta_fields['title']));
            }
            if (isset($meta_fields['description'])) {
                update_post_meta($post_id, '_almaseo_description', sanitize_textarea_field($meta_fields['description']));
            }
            if (isset($meta_fields['focus_keyword'])) {
                update_post_meta($post_id, '_almaseo_focus_keyword', sanitize_text_field($meta_fields['focus_keyword']));
            }
        }
        
        // Force fresh calculation - no caching
        $result = almaseo_health_calculate($post_id);
        
        // Save to meta
        update_post_meta($post_id, ALMASEO_HEALTH_SCORE_META, $result['score']);
        update_post_meta($post_id, ALMASEO_HEALTH_BREAKDOWN_META, json_encode($result['breakdown']));
        update_post_meta($post_id, ALMASEO_HEALTH_UPDATED_META, current_time('timestamp'));
        
        // Get SERP preview data
        $post = get_post($post_id);
        $title = get_post_meta($post_id, '_almaseo_title', true);
        if (empty($title)) {
            $title = $post->post_title;
        }
        
        $description = get_post_meta($post_id, '_almaseo_description', true);
        if (empty($description)) {
            $description = $post->post_excerpt;
        }
        if (empty($description)) {
            $description = wp_trim_words(wp_strip_all_tags($post->post_content), 25);
        }
        
        // Get URL for display
        $url = get_permalink($post_id);
        $url_display = str_replace(array('http://', 'https://'), '', $url);
        
        // Return complete health panel data
        wp_send_json_success(array(
            'score' => $result['score'],
            'breakdown' => $result['breakdown'],
            'updated_at' => human_time_diff(current_time('timestamp')) . ' ' . __('ago', 'almaseo'),
            'serp_preview' => array(
                'title' => almaseo_truncate_for_serp($title, 60),
                'description' => almaseo_truncate_for_serp($description, 160),
                'url' => $url,
                'url_display' => $url_display
            ),
            'token' => $token,
            'reason' => $reason,
            'timestamp' => current_time('timestamp')
        ));
    }
    
    /**
     * AJAX handler for live updates (no page refresh needed)
     */
    public function ajax_live_update() {
        check_ajax_referer('almaseo_health_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die();
        }
        
        $post_id = intval($_POST['post_id']);
        
        if (!$post_id || !get_post($post_id)) {
            wp_send_json_error('Invalid post ID');
        }
        
        // Save any updated meta fields if provided
        if (isset($_POST['meta_fields'])) {
            $meta_fields = $_POST['meta_fields'];
            
            if (isset($meta_fields['title'])) {
                update_post_meta($post_id, '_almaseo_title', sanitize_text_field($meta_fields['title']));
            }
            if (isset($meta_fields['description'])) {
                update_post_meta($post_id, '_almaseo_description', sanitize_textarea_field($meta_fields['description']));
            }
            if (isset($meta_fields['focus_keyword'])) {
                update_post_meta($post_id, '_almaseo_focus_keyword', sanitize_text_field($meta_fields['focus_keyword']));
            }
        }
        
        // Calculate with latest data
        $result = almaseo_health_calculate($post_id);
        
        // Save to meta
        update_post_meta($post_id, ALMASEO_HEALTH_SCORE_META, $result['score']);
        update_post_meta($post_id, ALMASEO_HEALTH_BREAKDOWN_META, json_encode($result['breakdown']));
        update_post_meta($post_id, ALMASEO_HEALTH_UPDATED_META, current_time('timestamp'));
        
        // Get SERP preview data
        $title = get_post_meta($post_id, '_almaseo_title', true);
        if (empty($title)) {
            $post = get_post($post_id);
            $title = $post->post_title;
        }
        
        $description = get_post_meta($post_id, '_almaseo_description', true);
        if (empty($description)) {
            $post = get_post($post_id);
            $description = wp_trim_words(wp_strip_all_tags($post->post_content), 25);
        }
        
        // Return comprehensive result
        wp_send_json_success(array(
            'score' => $result['score'],
            'breakdown' => $result['breakdown'],
            'updated_at' => human_time_diff(current_time('timestamp')) . ' ' . __('ago', 'almaseo'),
            'serp_preview' => array(
                'title' => almaseo_truncate_for_serp($title, 60),
                'description' => almaseo_truncate_for_serp($description, 160),
                'url' => get_permalink($post_id)
            )
        ));
    }
    
    /**
     * AJAX handler for keyword suggestions
     */
    public function ajax_get_keyword_suggestions() {
        check_ajax_referer('almaseo_health_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die();
        }
        
        $post_id = intval($_POST['post_id']);
        $api_key = get_option('almaseo_api_key');
        
        if (empty($api_key)) {
            wp_send_json_error(array(
                'message' => __('Not connected to AlmaSEO Dashboard', 'almaseo'),
                'is_connected' => false
            ));
            return;
        }
        
        // Try to get keywords from AlmaSEO API
        $response = wp_remote_post('https://almaseo.com/api/keywords', array(
            'body' => array(
                'api_key' => $api_key,
                'post_id' => $post_id,
                'url' => get_permalink($post_id)
            ),
            'timeout' => 10
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => __('Failed to fetch keywords from API', 'almaseo'),
                'is_connected' => true
            ));
            return;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (!empty($data['keywords'])) {
            wp_send_json_success(array(
                'keywords' => $data['keywords'],
                'is_connected' => true
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('No keywords available', 'almaseo'),
                'is_connected' => true
            ));
        }
    }
    
    /**
     * AJAX handler for drafting meta description
     */
    public function ajax_draft_meta_desc() {
        check_ajax_referer('almaseo_health_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die();
        }
        
        $post_id = intval($_POST['post_id']);
        $post = get_post($post_id);
        
        if (!$post) {
            wp_send_json_error('Invalid post');
        }
        
        // Get first paragraph
        $content = apply_filters('the_content', $post->post_content);
        $content = wp_strip_all_tags($content);
        
        // Extract first 160 chars or first sentence
        $sentences = preg_split('/[.!?]+/', $content, 2, PREG_SPLIT_NO_EMPTY);
        $draft = '';
        
        if (!empty($sentences[0])) {
            $draft = trim($sentences[0]);
            if (strlen($draft) > 160) {
                $draft = substr($draft, 0, 157) . '...';
            }
        }
        
        wp_send_json_success(array('draft' => $draft));
    }
    
    /**
     * Get score tooltip text
     */
    private function get_score_tooltip_text() {
        $weights = almaseo_health_get_weights();
        $labels = almaseo_health_get_signal_labels();
        
        $tooltip = __('SEO Health Score Breakdown:', 'almaseo') . "\n";
        foreach ($weights as $signal => $weight) {
            $label = isset($labels[$signal]) ? $labels[$signal] : ucfirst($signal);
            $tooltip .= "• {$label}: {$weight} points\n";
        }
        $tooltip .= "\n" . __('Total: 100 points (weighted by importance)', 'almaseo');
        
        return $tooltip;
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        global $post_type;
        
        // Only on post/page edit screens
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        if (!in_array($post_type, array('post', 'page'))) {
            return;
        }
        
        // CSS - use namespaced version
        wp_enqueue_style(
            'almaseo-health',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/health-namespaced.css',
            array(),
            '4.2.4'
        );
        
        // JS - use main health.js file
        wp_enqueue_script(
            'almaseo-health',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/health.js',
            array('jquery'),
            '4.2.4',
            true
        );
        
        // Add debug flag if enabled
        if (defined('ALMASEO_DEV_DEBUG') && ALMASEO_DEV_DEBUG) {
            wp_add_inline_script('almaseo-health', 'window.ALMASEO_DEV_DEBUG = true;', 'before');
        }
        
        // Localize
        wp_localize_script('almaseo-health', 'almaseoHealth', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('almaseo_health_nonce'),
            'readingSettingsUrl' => admin_url('options-reading.php'),
            'i18n' => array(
                'recalculating' => __('Recalculating...', 'almaseo'),
                'recalculated' => __('Score updated!', 'almaseo'),
                'error' => __('Error calculating score', 'almaseo'),
                'draft_copied' => __('Draft description copied to field', 'almaseo'),
                'unsaved_changes' => __('Changes not saved—click Update to keep them', 'almaseo'),
                'unsaved_changes_improved' => __('You have unsaved changes. Click "Update" to save your SEO improvements.', 'almaseo'),
                'score_tooltip' => $this->get_score_tooltip_text(),
                'connect_for_keywords' => __('Connect to AlmaSEO to unlock live keyword suggestions', 'almaseo'),
                'no_keywords' => __('No keyword suggestions available', 'almaseo'),
                'keyword_error' => __('Failed to load keyword suggestions', 'almaseo')
            )
        ));
    }
}

// Initialize
add_action('plugins_loaded', function() {
    if (class_exists('AlmaSEO_Health_Loader')) {
        AlmaSEO_Health_Loader::get_instance();
    }
}, 25);