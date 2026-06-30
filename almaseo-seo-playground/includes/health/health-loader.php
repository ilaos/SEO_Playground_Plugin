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
        require_once dirname(__FILE__) . '/headline-analyzer.php';
        require_once dirname(__FILE__) . '/readability.php';

        // Dashboard REST endpoints for health enhancements
        if (file_exists(dirname(__FILE__) . '/headline-analyzer-rest.php')) {
            require_once dirname(__FILE__) . '/headline-analyzer-rest.php';
            AlmaSEO_Headline_Analyzer_REST::init();
        }
        if (file_exists(dirname(__FILE__) . '/readability-rest.php')) {
            require_once dirname(__FILE__) . '/readability-rest.php';
            AlmaSEO_Readability_REST::init();
        }

        if (is_admin()) {
            require_once dirname(__FILE__) . '/ui.php';
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
        update_post_meta($post_id, ALMASEO_HEALTH_UPDATED_META, current_time('U'));
    }
    
    /**
     * AJAX handler for recalculation
     */
    public function ajax_recalculate() {
        check_ajax_referer('almaseo_health_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_die();
        }
        
        $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;

        // Per-post capability check: this handler writes meta to $post_id, so
        // edit_posts alone isn't enough — require edit access to THIS post.
        // (current_user_can('edit_post', $bad_id) is false, so this also
        // covers the non-existent-post case the old !get_post() guard handled.)
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Invalid post ID or insufficient permissions');
        }

        // Calculate
        $result = almaseo_health_calculate($post_id);
        
        // Save to meta
        update_post_meta($post_id, ALMASEO_HEALTH_SCORE_META, $result['score']);
        update_post_meta($post_id, ALMASEO_HEALTH_BREAKDOWN_META, json_encode($result['breakdown']));
        update_post_meta($post_id, ALMASEO_HEALTH_UPDATED_META, current_time('U'));
        
        // Return result
        wp_send_json_success(array(
            'score' => $result['score'],
            'breakdown' => $result['breakdown'],
            'updated_at' => human_time_diff(current_time('U')) . ' ' . esc_html__('ago', 'almaseo-seo-playground')
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
        
        $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;
        $token = isset($_POST['token']) ? intval(wp_unslash($_POST['token'])) : 0;
        $reason = isset($_POST['reason']) ? sanitize_text_field(wp_unslash($_POST['reason'])) : 'manual';

        // Per-post capability check: this handler writes the SEO title,
        // description and focus keyword to $post_id, so require edit access to
        // THIS post — edit_posts alone would let a contributor overwrite any
        // post's meta tags.
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Invalid post ID or insufficient permissions');
        }

        // Clear any cached data for this post
        delete_transient('almaseo_health_' . $post_id);
        delete_transient('almaseo_health_cache_' . $post_id);

        // Save any updated meta fields if provided
        if (isset($_POST['meta_fields']) && is_array($_POST['meta_fields'])) {
            $meta_fields = array_map('sanitize_text_field', wp_unslash($_POST['meta_fields']));
            
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
        update_post_meta($post_id, ALMASEO_HEALTH_UPDATED_META, current_time('U'));
        
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
            'updated_at' => human_time_diff(current_time('U')) . ' ' . esc_html__('ago', 'almaseo-seo-playground'),
            'serp_preview' => array(
                'title' => almaseo_truncate_for_serp($title, 60),
                'description' => almaseo_truncate_for_serp($description, 160),
                'url' => $url,
                'url_display' => $url_display
            ),
            'token' => $token,
            'reason' => $reason,
            'timestamp' => current_time('U')
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
        
        $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;

        // Per-post capability check: this handler writes the SEO title,
        // description and focus keyword to $post_id, so require edit access to
        // THIS post rather than the generic edit_posts capability.
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Invalid post ID or insufficient permissions');
        }

        // Save any updated meta fields if provided
        if (isset($_POST['meta_fields'])) {
            $meta_fields = array_map('sanitize_text_field', wp_unslash($_POST['meta_fields']));
            
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
        update_post_meta($post_id, ALMASEO_HEALTH_UPDATED_META, current_time('U'));
        
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
            'updated_at' => human_time_diff(current_time('U')) . ' ' . esc_html__('ago', 'almaseo-seo-playground'),
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
        
        $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;

        // Require edit access to the specific post (its title/URL are sent to
        // the keyword API) — don't leak another author's post on the shared nonce.
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error(array('message' => 'Invalid post ID or insufficient permissions'));
            return;
        }

        // AlmaSEO-powered keyword suggestions: real monthly search volume +
        // competition pulled from the dashboard. Free / disconnected sites keep
        // the local Google Suggest autocomplete on the focus-keyword field; this
        // volume panel is the connected-AlmaSEO enhancement (the local field stays free).
        $username  = get_option('almaseo_connected_user', '');
        $password  = get_option('almaseo_app_password', '');
        $connected = ! empty($username) && ! empty($password);
        if (function_exists('seo_playground_is_alma_connected')) {
            $connected = $connected && seo_playground_is_alma_connected();
        }

        if (! $connected) {
            wp_send_json_error(array(
                'message'      => __('Keyword help runs locally on the focus-keyword field. Connect AlmaSEO to also see the keywords you already rank for in Google Search Console, with real impressions and average position.', 'almaseo-seo-playground'),
                'is_connected' => false
            ));
            return;
        }

        // Seed the engine with the keyword the user is currently typing (sent
        // live from the editor), falling back to the saved focus keyword, then
        // the post title. This lets the panel update without a save+reload.
        $seed = isset($_POST['keyword']) ? sanitize_text_field(wp_unslash($_POST['keyword'])) : '';
        if (! is_string($seed) || strlen(trim($seed)) < 2) {
            $seed = get_post_meta($post_id, '_almaseo_focus_keyword', true);
        }
        if (! is_string($seed) || strlen(trim($seed)) < 2) {
            $seed = get_the_title($post_id);
        }

        $response = wp_remote_post('https://api.almaseo.com/api/plugin/keyword-suggestions', array(
            'timeout' => 15,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($username . ':' . $password),
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode(array(
                'site_url'      => get_site_url(),
                'query'         => is_string($seed) ? $seed : '',
                'focus_keyword' => is_string($seed) ? $seed : '',
                'post_title'    => get_the_title($post_id),
            )),
        ));

        if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
            wp_send_json_error(array(
                'message'      => __('Could not reach AlmaSEO just now — try refreshing in a moment.', 'almaseo-seo-playground'),
                'is_connected' => true
            ));
            return;
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (! empty($data['keywords'])) {
            wp_send_json_success(array(
                'keywords'         => $data['keywords'],
                'is_connected'     => true,
                'has_real_metrics' => ! empty($data['has_real_metrics']),
                'enrichment_nudge' => isset($data['enrichment_nudge']) ? $data['enrichment_nudge'] : ''
            ));
        } else {
            wp_send_json_error(array(
                'message'      => __('No keyword suggestions yet — add a focus keyword to get AlmaSEO-powered ideas with real search volume.', 'almaseo-seo-playground'),
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
        
        $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;

        // Require edit access to the specific post — this reads its content to
        // draft a description, so don't expose another author's post.
        if (!$post_id || !current_user_can('edit_post', $post_id)) {
            wp_send_json_error('Invalid post ID or insufficient permissions');
        }

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
        
        $tooltip = __('SEO Health Score Breakdown:', 'almaseo-seo-playground') . "\n";
        foreach ($weights as $signal => $weight) {
            $label = isset($labels[$signal]) ? $labels[$signal] : ucfirst($signal);
            $tooltip .= "• {$label}: {$weight} points\n";
        }
        $tooltip .= "\n" . esc_html__('Total: 100 points (weighted by importance)', 'almaseo-seo-playground');
        
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
            ALMASEO_PLUGIN_VERSION
        );

        // JS - use main health.js file
        wp_enqueue_script(
            'almaseo-health',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/health.js',
            array('jquery'),
            ALMASEO_PLUGIN_VERSION,
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
                'recalculating' => __('Recalculating...', 'almaseo-seo-playground'),
                'recalculated' => __('Score updated!', 'almaseo-seo-playground'),
                'error' => __('Error calculating score', 'almaseo-seo-playground'),
                'draft_copied' => __('Draft description copied to field', 'almaseo-seo-playground'),
                'unsaved_changes' => __('Changes not saved—click Update to keep them', 'almaseo-seo-playground'),
                'unsaved_changes_improved' => __('You have unsaved changes. Click "Update" to save your SEO improvements.', 'almaseo-seo-playground'),
                'score_tooltip' => $this->get_score_tooltip_text(),
                'connect_for_keywords' => __('Keyword suggestions run locally in WordPress. Connect AlmaSEO to also see real search-volume and competition data next to every keyword.', 'almaseo-seo-playground'),
                'no_keywords' => __('No keyword suggestions available', 'almaseo-seo-playground'),
                'keyword_error' => __('Failed to load keyword suggestions', 'almaseo-seo-playground')
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