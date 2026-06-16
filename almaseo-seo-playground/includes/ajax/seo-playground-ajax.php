<?php
/**
 * AlmaSEO SEO Playground AJAX Handlers
 *
 * Handles all AJAX requests for the SEO Playground metabox features:
 * connection check, re-optimization, AI rewrite, content brief,
 * FAQ generation, post insight, keyword intelligence, snippets,
 * GSC keywords, schema analysis, meta health, notes, and more.
 *
 * @package AlmaSEO
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// AJAX handler for checking connection status
add_action('wp_ajax_seo_playground_check_connection', 'seo_playground_ajax_check_connection');
if (!function_exists('seo_playground_ajax_check_connection')) {
function seo_playground_ajax_check_connection() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Check connection status
    $is_connected = seo_playground_is_alma_connected();
    
    wp_send_json_success(array(
        'connected' => $is_connected,
        'message' => $is_connected ? 'Connected to AlmaSEO' : 'Not connected to AlmaSEO'
    ));
}
} // end function_exists guard: seo_playground_ajax_check_connection

// AJAX handler for re-optimization check
if (!function_exists('seo_playground_ajax_reoptimize_check')) {
function seo_playground_ajax_reoptimize_check() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Rate limit API requests
    if (!almaseo_check_rate_limit()) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait a moment and try again.'));
        return;
    }

    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get post data
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
    $content = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';
    $site_url = isset($_POST['site_url']) ? esc_url_raw(wp_unslash($_POST['site_url'])) : '';
    
    // Validate required data
    if (!$post_id || !$title || !$content || !$site_url) {
        wp_send_json_error(array('message' => 'Missing required data'));
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_app_password', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'post_id' => $post_id,
        'title' => $title,
        'content' => $content,
        'site_url' => $site_url
    );
    
    // Make API request to AlmaSEO
    $response = wp_remote_post(ALMASEO_API_BASE_URL . '/posts/reoptimize-check', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30,
        'data_format' => 'body'
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'API request failed: ' . $response->get_error_message()));
        return;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status_code !== 200) {
        wp_send_json_error(array('message' => 'API request failed with status: ' . $status_code));
        return;
    }
    
    $data = json_decode($body, true);
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from API'));
        return;
    }
    
    // Return the re-optimization data
    wp_send_json_success($data);
}
} // end function_exists guard: seo_playground_ajax_reoptimize_check
add_action('wp_ajax_seo_playground_reoptimize_check', 'seo_playground_ajax_reoptimize_check');

// AJAX handler for AI rewrite
if (!function_exists('seo_playground_ajax_rewrite')) {
function seo_playground_ajax_rewrite() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Rate limit API requests
    if (!almaseo_check_rate_limit()) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait a moment and try again.'));
        return;
    }

    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Check tier and usage limits
    if (!almaseo_can_use_ai_features()) {
        wp_send_json_error(array(
            'message' => 'Alma features require Pro or Max tier. Please upgrade to access premium tools.',
            'tier' => almaseo_get_user_tier()
        ));
        return;
    }
    
    // Check remaining generations for Pro tier
    $user_tier = almaseo_get_user_tier();
    if ($user_tier === 'pro') {
        $generations = almaseo_get_remaining_generations();
        if ($generations['remaining'] <= 0) {
            wp_send_json_error(array(
                'message' => 'You have reached your monthly Alma generation limit. Please upgrade to Max for unlimited access.',
                'remaining' => 0
            ));
            return;
        }
    }
    
    // Get rewrite data
    $input = isset($_POST['input']) ? sanitize_textarea_field(wp_unslash($_POST['input'])) : '';
    $type = isset($_POST['type']) ? sanitize_text_field(wp_unslash($_POST['type'])) : '';
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $site_url = isset($_POST['site_url']) ? esc_url_raw(wp_unslash($_POST['site_url'])) : '';
    
    // Validate required data
    if (empty($input) || empty($type) || !$post_id || !$site_url) {
        wp_send_json_error(array('message' => 'Missing required data'));
        return;
    }
    
    // Validate type
    $valid_types = array('paragraph', 'title', 'description');
    if (!in_array($type, $valid_types)) {
        wp_send_json_error(array('message' => 'Invalid rewrite type'));
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_app_password', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'input' => $input,
        'type' => $type,
        'post_id' => $post_id,
        'site_url' => $site_url
    );
    
    // Make API request to AlmaSEO
    $response = wp_remote_post(ALMASEO_API_BASE_URL . '/rewrite', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30,
        'data_format' => 'body'
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'API request failed: ' . $response->get_error_message()));
        return;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status_code !== 200) {
        wp_send_json_error(array('message' => 'API request failed with status: ' . $status_code));
        return;
    }
    
    $data = json_decode($body, true);
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from API'));
        return;
    }
    
    // Track usage
    almaseo_track_ai_usage('content_rewrite');
    
    // Get updated generation info
    $generations = almaseo_get_remaining_generations();
    
    // Return the rewritten content with usage info
    wp_send_json_success(array(
        'rewritten' => $data['rewritten'] ?? $data,
        'usage' => array(
            'remaining' => $generations['remaining'],
            'total' => $generations['total'],
            'tier' => $user_tier
        )
    ));
}
} // end function_exists guard: seo_playground_ajax_rewrite
add_action('wp_ajax_seo_playground_rewrite', 'seo_playground_ajax_rewrite');

// AJAX handler for content brief generation
if (!function_exists('seo_playground_ajax_generate_brief')) {
function seo_playground_ajax_generate_brief() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Rate limit API requests
    if (!almaseo_check_rate_limit()) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait a moment and try again.'));
        return;
    }

    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get brief data
    $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
    $keywords = isset($_POST['keywords']) ? sanitize_text_field(wp_unslash($_POST['keywords'])) : '';
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $site_url = isset($_POST['site_url']) ? esc_url_raw(wp_unslash($_POST['site_url'])) : '';
    
    // Validate required data
    if (empty($title) || !$post_id || !$site_url) {
        wp_send_json_error(array('message' => 'Missing required data'));
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_app_password', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'title' => $title,
        'keywords' => $keywords,
        'post_id' => $post_id,
        'site_url' => $site_url
    );
    
    // Make API request to AlmaSEO
    $response = wp_remote_post(ALMASEO_API_BASE_URL . '/brief/suggest', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30,
        'data_format' => 'body'
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'API request failed: ' . $response->get_error_message()));
        return;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status_code !== 200) {
        wp_send_json_error(array('message' => 'API request failed with status: ' . $status_code));
        return;
    }
    
    $data = json_decode($body, true);
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from API'));
        return;
    }
    
    // Return the brief data
    wp_send_json_success($data);
}
} // end function_exists guard: seo_playground_ajax_generate_brief
add_action('wp_ajax_seo_playground_generate_brief', 'seo_playground_ajax_generate_brief');

// AJAX handler for FAQ generation
if (!function_exists('seo_playground_ajax_generate_faqs')) {
function seo_playground_ajax_generate_faqs() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Rate limit API requests
    if (!almaseo_check_rate_limit()) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait a moment and try again.'));
        return;
    }

    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get FAQ data with null safety
    $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
    $content = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $site_url = isset($_POST['site_url']) ? esc_url_raw(wp_unslash($_POST['site_url'])) : '';
    
    // Validate required data
    if (empty($title) || empty($content) || !$post_id || !$site_url) {
        wp_send_json_error(array('message' => 'Missing required data'));
        return;
    }
    
    // Validate content length (minimum 100 words) with null safety
    $content_text = !empty($content) ? wp_strip_all_tags($content) : '';
    $word_count = str_word_count($content_text);
    if ($word_count < 100) {
        wp_send_json_error(array('message' => 'Content must be at least 100 words to generate meaningful FAQs'));
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_app_password', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'title' => $title,
        'content' => $content,
        'post_id' => $post_id,
        'site_url' => $site_url
    );
    
    // Make API request to AlmaSEO
    $response = wp_remote_post(ALMASEO_API_BASE_URL . '/faqs/suggest', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30,
        'data_format' => 'body'
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'API request failed: ' . $response->get_error_message()));
        return;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status_code !== 200) {
        wp_send_json_error(array('message' => 'API request failed with status: ' . $status_code));
        return;
    }
    
    $data = json_decode($body, true);
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from API'));
        return;
    }
    
    // Return the FAQ data
    wp_send_json_success($data);
}
} // end function_exists guard: seo_playground_ajax_generate_faqs
add_action('wp_ajax_seo_playground_generate_faqs', 'seo_playground_ajax_generate_faqs');

// AJAX handler for post intelligence
add_action('wp_ajax_seo_playground_post_insight', 'seo_playground_ajax_post_insight');
if (!function_exists('seo_playground_ajax_post_insight')) {
function seo_playground_ajax_post_insight() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Rate limit API requests
    if (!almaseo_check_rate_limit()) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait a moment and try again.'));
        return;
    }

    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get post data
    $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
    $keywords = isset($_POST['keywords']) ? sanitize_text_field(wp_unslash($_POST['keywords'])) : '';
    $content = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';
    $site_url = isset($_POST['site_url']) ? esc_url_raw(wp_unslash($_POST['site_url'])) : '';

    // Validate required data
    if (!$title || !$content || !$site_url) {
        wp_send_json_error(array('message' => 'Missing required data'));
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_app_password', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'title' => $title,
        'keywords' => $keywords,
        'content' => $content,
        'post_id' => $post_id,
        'site_url' => $site_url
    );
    
    // Make API request
    $response = wp_remote_post(ALMASEO_API_BASE_URL . '/post-insight', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Failed to connect to AlmaSEO API: ' . $response->get_error_message()));
        return;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from AlmaSEO API'));
        return;
    }
    
    if (isset($data['error'])) {
        wp_send_json_error(array('message' => $data['error']));
        return;
    }
    
    if (!isset($data['insight']) || empty($data['insight'])) {
        wp_send_json_error(array('message' => 'No insight data received from API'));
        return;
    }
    
    // Save the insight to post meta for persistence
    update_post_meta($post_id, '_seo_playground_post_insight', sanitize_textarea_field($data['insight']));
    update_post_meta($post_id, '_seo_playground_post_insight_timestamp', current_time('mysql'));
    
    wp_send_json_success(array(
        'insight' => esc_html($data['insight']),
        'timestamp' => current_time('mysql'),
        'message' => 'Post intelligence generated successfully'
    ));
}
} // end function_exists guard: seo_playground_ajax_post_insight

// AJAX handler for getting existing post insight
add_action('wp_ajax_seo_playground_get_post_insight', 'seo_playground_ajax_get_post_insight');
if (!function_exists('seo_playground_ajax_get_post_insight')) {
function seo_playground_ajax_get_post_insight() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Rate limit API requests
    if (!almaseo_check_rate_limit()) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait a moment and try again.'));
        return;
    }

    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get post ID
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID'));
        return;
    }
    
    // Get existing insight from post meta
    $insight = get_post_meta($post_id, '_seo_playground_post_insight', true);
    $timestamp = get_post_meta($post_id, '_seo_playground_post_insight_timestamp', true);
    
    if ($insight) {
        wp_send_json_success(array(
            'insight' => esc_html($insight),
            'timestamp' => $timestamp ? $timestamp : current_time('mysql')
        ));
    } else {
        wp_send_json_error(array('message' => 'No existing insight found'));
    }
}
} // end function_exists guard: seo_playground_ajax_get_post_insight

// AJAX handler for keyword intelligence
add_action('wp_ajax_seo_playground_keyword_intelligence', 'seo_playground_ajax_keyword_intelligence');
if (!function_exists('seo_playground_ajax_keyword_intelligence')) {
function seo_playground_ajax_keyword_intelligence() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Rate limit API requests
    if (!almaseo_check_rate_limit()) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait a moment and try again.'));
        return;
    }

    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get post data
    $keyword = isset($_POST['keyword']) ? sanitize_text_field(wp_unslash($_POST['keyword'])) : '';
    $site_url = isset($_POST['site_url']) ? esc_url_raw(wp_unslash($_POST['site_url'])) : '';

    // Validate required data
    if (!$keyword || !$site_url) {
        wp_send_json_error(array('message' => 'Missing required data'));
        return;
    }
    
    // Validate keyword length
    if (strlen($keyword) < 2 || strlen($keyword) > 100) {
        wp_send_json_error(array('message' => 'Keyword must be between 2 and 100 characters'));
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_app_password', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'keyword' => $keyword,
        'post_id' => $post_id,
        'site_url' => $site_url
    );
    
    // Make API request
    $response = wp_remote_post(ALMASEO_API_BASE_URL . '/keyword-insight', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Failed to connect to AlmaSEO API: ' . $response->get_error_message()));
        return;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from AlmaSEO API'));
        return;
    }
    
    if (isset($data['error'])) {
        wp_send_json_error(array('message' => $data['error']));
        return;
    }
    
    // Validate response structure
    $required_fields = array('intent', 'difficulty', 'related_terms', 'tip');
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            wp_send_json_error(array('message' => 'Missing required field: ' . $field));
            return;
        }
    }
    
    // Save the keyword intelligence to post meta for persistence
    update_post_meta($post_id, '_seo_playground_keyword_intelligence', sanitize_textarea_field(json_encode($data)));
    update_post_meta($post_id, '_seo_playground_keyword_intelligence_timestamp', current_time('mysql'));
    update_post_meta($post_id, '_seo_playground_keyword_intelligence_keyword', sanitize_text_field($keyword));
    
    wp_send_json_success(array(
        'intent' => esc_html($data['intent']),
        'difficulty' => esc_html($data['difficulty']),
        'related_terms' => esc_html($data['related_terms']),
        'tip' => esc_html($data['tip']),
        'timestamp' => current_time('mysql'),
        'message' => 'Keyword intelligence generated successfully'
    ));
}
} // end function_exists guard: seo_playground_ajax_keyword_intelligence

// AJAX handler for getting existing keyword intelligence
add_action('wp_ajax_seo_playground_get_keyword_intelligence', 'seo_playground_ajax_get_keyword_intelligence');
if (!function_exists('seo_playground_ajax_get_keyword_intelligence')) {
function seo_playground_ajax_get_keyword_intelligence() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Rate limit API requests
    if (!almaseo_check_rate_limit()) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait a moment and try again.'));
        return;
    }

    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get post ID
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID'));
        return;
    }
    
    // Get existing keyword intelligence from post meta
    $intelligence_json = get_post_meta($post_id, '_seo_playground_keyword_intelligence', true);
    $timestamp = get_post_meta($post_id, '_seo_playground_keyword_intelligence_timestamp', true);
    $keyword = get_post_meta($post_id, '_seo_playground_keyword_intelligence_keyword', true);
    
    if ($intelligence_json) {
        $intelligence = json_decode($intelligence_json, true);
        if ($intelligence && is_array($intelligence)) {
            wp_send_json_success(array(
                'intelligence' => array(
                    'intent' => esc_html($intelligence['intent']),
                    'difficulty' => esc_html($intelligence['difficulty']),
                    'related_terms' => esc_html($intelligence['related_terms']),
                    'tip' => esc_html($intelligence['tip'])
                ),
                'timestamp' => $timestamp ? $timestamp : current_time('mysql'),
                'keyword' => $keyword ? esc_html($keyword) : ''
            ));
        }
    }
    
    wp_send_json_error(array('message' => 'No existing keyword intelligence found'));
}
} // end function_exists guard: seo_playground_ajax_get_keyword_intelligence

// AJAX handler for saved snippets - Get snippets
if (!function_exists('seo_playground_ajax_get_snippets')) {
function seo_playground_ajax_get_snippets() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Rate limit API requests
    if (!almaseo_check_rate_limit()) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait a moment and try again.'));
        return;
    }

    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get current user ID
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'User not authenticated'));
        return;
    }
    
    // Get saved snippets from user meta
    $snippets_json = get_user_meta($user_id, '_seo_playground_saved_snippets', true);
    $snippets = array();
    
    if ($snippets_json) {
        $snippets = json_decode($snippets_json, true);
        if (!is_array($snippets)) {
            $snippets = array();
        }
    }
    
    wp_send_json_success(array('snippets' => $snippets));
}
} // end function_exists guard: seo_playground_ajax_get_snippets
add_action('wp_ajax_seo_playground_get_snippets', 'seo_playground_ajax_get_snippets');

// AJAX handler for saved snippets - Save snippet
if (!function_exists('seo_playground_ajax_save_snippet')) {
function seo_playground_ajax_save_snippet() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Rate limit API requests
    if (!almaseo_check_rate_limit()) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait a moment and try again.'));
        return;
    }

    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get current user ID
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'User not authenticated'));
        return;
    }
    
    // Get and validate snippet data
    $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
    $content = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';
    $snippet_id = isset($_POST['snippet_id']) ? sanitize_text_field(wp_unslash($_POST['snippet_id'])) : ''; // Empty for new, existing ID for edit
    
    if (empty($title) || empty($content)) {
        wp_send_json_error(array('message' => 'Title and content are required'));
        return;
    }
    
    // Get existing snippets
    $snippets_json = get_user_meta($user_id, '_seo_playground_saved_snippets', true);
    $snippets = array();
    
    if ($snippets_json) {
        $snippets = json_decode($snippets_json, true);
        if (!is_array($snippets)) {
            $snippets = array();
        }
    }
    
    // Create new snippet or update existing
    if (empty($snippet_id)) {
        // Create new snippet
        $new_snippet = array(
            'id' => uniqid('snippet_'),
            'title' => $title,
            'content' => $content,
            'created' => current_time('mysql'),
            'updated' => current_time('mysql')
        );
        $snippets[] = $new_snippet;
    } else {
        // Update existing snippet
        $found = false;
        foreach ($snippets as &$snippet) {
            if ($snippet['id'] === $snippet_id) {
                $snippet['title'] = $title;
                $snippet['content'] = $content;
                $snippet['updated'] = current_time('mysql');
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            wp_send_json_error(array('message' => 'Snippet not found'));
            return;
        }
    }
    
    // Save updated snippets
    $result = update_user_meta($user_id, '_seo_playground_saved_snippets', json_encode($snippets));
    
    if ($result === false) {
        wp_send_json_error(array('message' => 'Failed to save snippet'));
        return;
    }
    
    wp_send_json_success(array(
        'message' => 'Snippet saved successfully',
        'snippets' => $snippets
    ));
}
} // end function_exists guard: seo_playground_ajax_save_snippet
add_action('wp_ajax_seo_playground_save_snippet', 'seo_playground_ajax_save_snippet');

// AJAX handler for saved snippets - Delete snippet
if (!function_exists('seo_playground_ajax_delete_snippet')) {
function seo_playground_ajax_delete_snippet() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Rate limit API requests
    if (!almaseo_check_rate_limit()) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait a moment and try again.'));
        return;
    }

    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get current user ID
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'User not authenticated'));
        return;
    }
    
    // Get snippet ID to delete
    $snippet_id = isset($_POST['snippet_id']) ? sanitize_text_field(wp_unslash($_POST['snippet_id'])) : '';
    
    if (empty($snippet_id)) {
        wp_send_json_error(array('message' => 'Snippet ID is required'));
        return;
    }
    
    // Get existing snippets
    $snippets_json = get_user_meta($user_id, '_seo_playground_saved_snippets', true);
    $snippets = array();
    
    if ($snippets_json) {
        $snippets = json_decode($snippets_json, true);
        if (!is_array($snippets)) {
            $snippets = array();
        }
    }
    
    // Remove the snippet
    $found = false;
    foreach ($snippets as $key => $snippet) {
        if ($snippet['id'] === $snippet_id) {
            unset($snippets[$key]);
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        wp_send_json_error(array('message' => 'Snippet not found'));
        return;
    }
    
    // Re-index array
    $snippets = array_values($snippets);
    
    // Save updated snippets
    $result = update_user_meta($user_id, '_seo_playground_saved_snippets', json_encode($snippets));
    
    if ($result === false) {
        wp_send_json_error(array('message' => 'Failed to delete snippet'));
        return;
    }
    
    wp_send_json_success(array(
        'message' => 'Snippet deleted successfully',
        'snippets' => $snippets
    ));
}
} // end function_exists guard: seo_playground_ajax_delete_snippet
add_action('wp_ajax_seo_playground_delete_snippet', 'seo_playground_ajax_delete_snippet');

// AJAX handler for getting Google Search Console keywords
add_action('wp_ajax_seo_playground_get_gsc_keywords', 'seo_playground_ajax_get_gsc_keywords');
if (!function_exists('seo_playground_ajax_get_gsc_keywords')) {
function seo_playground_ajax_get_gsc_keywords() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Rate limit API requests
    if (!almaseo_check_rate_limit()) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait a moment and try again.'));
        return;
    }

    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get post data
    $permalink = isset($_POST['permalink']) ? esc_url_raw(wp_unslash($_POST['permalink'])) : '';
    $site_url = isset($_POST['site_url']) ? esc_url_raw(wp_unslash($_POST['site_url'])) : '';

    // Validate required data
    if (!$permalink || !$site_url) {
        wp_send_json_error(array('message' => 'Missing required data'));
        return;
    }
    
    // Check if we have cached data (24-hour cache)
    $cached_data = get_transient('gsc_keywords_' . $post_id);
    if ($cached_data !== false) {
        wp_send_json_success($cached_data);
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_app_password', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'permalink' => $permalink,
        'post_id' => $post_id,
        'site_url' => $site_url
    );
    
    // Make API request to AlmaSEO
    $response = wp_remote_post(ALMASEO_API_BASE_URL . '/gsc/keywords', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Failed to connect to AlmaSEO API: ' . $response->get_error_message()));
        return;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from AlmaSEO API'));
        return;
    }
    
    if (isset($data['error'])) {
        wp_send_json_error(array('message' => $data['error']));
        return;
    }
    
    // Validate response structure
    if (!isset($data['keywords']) || !is_array($data['keywords'])) {
        wp_send_json_error(array('message' => 'No keyword data received'));
        return;
    }
    
    // Cache the data for 24 hours
    $cache_data = array(
        'keywords' => $data['keywords'],
        'date_range' => isset($data['date_range']) ? $data['date_range'] : 'Last 28 days',
        'timestamp' => current_time('mysql'),
        'message' => 'GSC keywords loaded successfully'
    );
    
    set_transient('gsc_keywords_' . $post_id, $cache_data, 24 * HOUR_IN_SECONDS);
    
    // Also save to post meta for persistence
    update_post_meta($post_id, '_seo_playground_gsc_keywords', sanitize_textarea_field(json_encode($cache_data)));
    update_post_meta($post_id, '_seo_playground_gsc_keywords_timestamp', current_time('mysql'));
    
    wp_send_json_success($cache_data);
}
} // end function_exists guard: seo_playground_ajax_get_gsc_keywords

// AJAX handler for refreshing GSC keywords (clears cache)
add_action('wp_ajax_seo_playground_refresh_gsc_keywords', 'seo_playground_ajax_refresh_gsc_keywords');
if (!function_exists('seo_playground_ajax_refresh_gsc_keywords')) {
function seo_playground_ajax_refresh_gsc_keywords() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Rate limit API requests
    if (!almaseo_check_rate_limit()) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait a moment and try again.'));
        return;
    }

    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get post ID
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID'));
        return;
    }
    
    // Clear the cache
    delete_transient('gsc_keywords_' . $post_id);
    
    wp_send_json_success(array('message' => 'Cache cleared successfully'));
}
} // end function_exists guard: seo_playground_ajax_refresh_gsc_keywords

// AJAX handler for getting schema analysis
add_action('wp_ajax_seo_playground_get_schema_analysis', 'seo_playground_ajax_get_schema_analysis');
if (!function_exists('seo_playground_ajax_get_schema_analysis')) {
function seo_playground_ajax_get_schema_analysis() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    // Rate limit API requests
    if (!almaseo_check_rate_limit()) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait a moment and try again.'));
        return;
    }

    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }

    // Get post data
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    // Check per-post capability
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    $site_url = isset($_POST['site_url']) ? esc_url_raw(wp_unslash($_POST['site_url'])) : '';
    $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
    $content = isset($_POST['content']) ? sanitize_textarea_field(wp_unslash($_POST['content'])) : '';
    $schema_type = isset($_POST['schema_type']) ? sanitize_text_field(wp_unslash($_POST['schema_type'])) : '';
    
    // Validate required data
    if (!$post_id || !$site_url || !$schema_type || $schema_type === 'None') {
        wp_send_json_error(array('message' => 'Missing required data or invalid schema type'));
        return;
    }
    
    // Check if we have cached data (12-hour cache for schema analysis)
    $cached_data = get_transient('schema_analysis_' . $post_id . '_' . md5($schema_type));
    if ($cached_data !== false) {
        wp_send_json_success($cached_data);
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_app_password', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'post_id' => $post_id,
        'site_url' => $site_url,
        'title' => $title,
        'content' => $content,
        'schema_type' => $schema_type
    );
    
    // Make API request to AlmaSEO
    $response = wp_remote_post(ALMASEO_API_BASE_URL . '/schema/analyze', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Failed to connect to AlmaSEO API: ' . $response->get_error_message()));
        return;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from AlmaSEO API'));
        return;
    }
    
    if (isset($data['error'])) {
        wp_send_json_error(array('message' => $data['error']));
        return;
    }
    
    // Validate response structure
    if (!isset($data['analysis']) || empty($data['analysis'])) {
        wp_send_json_error(array('message' => 'No analysis received from API'));
        return;
    }
    
    // Cache the data for 12 hours
    $cache_data = array(
        'analysis' => $data['analysis'],
        'schema_type' => $schema_type,
        'timestamp' => current_time('mysql'),
        'message' => 'Schema analysis completed successfully'
    );
    
    set_transient('schema_analysis_' . $post_id . '_' . md5($schema_type), $cache_data, 12 * HOUR_IN_SECONDS);
    
    // Also save to post meta for persistence
    update_post_meta($post_id, '_seo_playground_schema_analysis', sanitize_textarea_field(json_encode($cache_data)));
    update_post_meta($post_id, '_seo_playground_schema_analysis_timestamp', current_time('mysql'));
    
    wp_send_json_success($cache_data);
}
} // end function_exists guard: seo_playground_ajax_get_schema_analysis

// AJAX handler for refreshing schema analysis (clears cache)
add_action('wp_ajax_seo_playground_refresh_schema_analysis', 'seo_playground_ajax_refresh_schema_analysis');
if (!function_exists('seo_playground_ajax_refresh_schema_analysis')) {
function seo_playground_ajax_refresh_schema_analysis() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }
    
    // Rate limit API requests
    if (!almaseo_check_rate_limit()) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait a moment and try again.'));
        return;
    }

    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    // Get post ID and schema type
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $schema_type = isset($_POST['schema_type']) ? sanitize_text_field(wp_unslash($_POST['schema_type'])) : '';
    
    if (!$post_id || !$schema_type || $schema_type === 'None') {
        wp_send_json_error(array('message' => 'Invalid post ID or schema type'));
        return;
    }
    
    // Clear the cache
    delete_transient('schema_analysis_' . $post_id . '_' . md5($schema_type));
    
    wp_send_json_success(array('message' => 'Cache cleared successfully'));
}
} // end function_exists guard: seo_playground_ajax_refresh_schema_analysis

// AJAX handler for getting meta health analysis
add_action('wp_ajax_seo_playground_get_meta_health', 'seo_playground_ajax_get_meta_health');
if (!function_exists('seo_playground_ajax_get_meta_health')) {
function seo_playground_ajax_get_meta_health() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    // Rate limit API requests
    if (!almaseo_check_rate_limit()) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait a moment and try again.'));
        return;
    }

    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }

    // Get post data
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    // Check per-post capability
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    $site_url = isset($_POST['site_url']) ? esc_url_raw(wp_unslash($_POST['site_url'])) : '';
    $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';
    $focus_keyword = isset($_POST['focus_keyword']) ? sanitize_text_field(wp_unslash($_POST['focus_keyword'])) : '';
    
    // Validate required data
    if (!$post_id || !$site_url || !$title || !$description) {
        wp_send_json_error(array('message' => 'Missing required data: title and description are required'));
        return;
    }
    
    // Check if we have cached data (12-hour cache for meta health)
    $cached_data = get_transient('meta_health_' . $post_id . '_' . md5($title . $description . $focus_keyword));
    if ($cached_data !== false) {
        wp_send_json_success($cached_data);
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_app_password', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'post_id' => $post_id,
        'site_url' => $site_url,
        'title' => $title,
        'description' => $description,
        'focus_keyword' => $focus_keyword
    );
    
    // Make API request to AlmaSEO
    $response = wp_remote_post(ALMASEO_API_BASE_URL . '/meta/analyze', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Failed to connect to AlmaSEO API: ' . $response->get_error_message()));
        return;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from AlmaSEO API'));
        return;
    }
    
    if (isset($data['error'])) {
        wp_send_json_error(array('message' => $data['error']));
        return;
    }
    
    // Validate response structure
    if (!isset($data['score']) || !isset($data['feedback'])) {
        wp_send_json_error(array('message' => 'Invalid response structure from API'));
        return;
    }
    
    // Validate score range
    $score = intval($data['score']);
    if ($score < 0 || $score > 100) {
        wp_send_json_error(array('message' => 'Invalid score received from API'));
        return;
    }
    
    // Cache the data for 12 hours
    $cache_data = array(
        'score' => $score,
        'feedback' => $data['feedback'],
        'title' => $title,
        'description' => $description,
        'focus_keyword' => $focus_keyword,
        'timestamp' => current_time('mysql'),
        'message' => 'Meta health analysis completed successfully'
    );
    
    set_transient('meta_health_' . $post_id . '_' . md5($title . $description . $focus_keyword), $cache_data, 12 * HOUR_IN_SECONDS);
    
    // Also save to post meta for persistence
    update_post_meta($post_id, '_seo_playground_meta_score', $score);
    update_post_meta($post_id, '_seo_playground_meta_feedback', sanitize_textarea_field($data['feedback']));
    update_post_meta($post_id, '_seo_playground_meta_timestamp', current_time('mysql'));
    
    wp_send_json_success($cache_data);
}
} // end function_exists guard: seo_playground_ajax_get_meta_health

// AJAX handler for refreshing meta health analysis (clears cache)
add_action('wp_ajax_seo_playground_refresh_meta_health', 'seo_playground_ajax_refresh_meta_health');
if (!function_exists('seo_playground_ajax_refresh_meta_health')) {
function seo_playground_ajax_refresh_meta_health() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }
    
    // Rate limit API requests
    if (!almaseo_check_rate_limit()) {
        wp_send_json_error(array('message' => 'Too many requests. Please wait a moment and try again.'));
        return;
    }

    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    // Get post ID and metadata
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $title = isset($_POST['title']) ? sanitize_text_field(wp_unslash($_POST['title'])) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';
    $focus_keyword = isset($_POST['focus_keyword']) ? sanitize_text_field(wp_unslash($_POST['focus_keyword'])) : '';
    
    if (!$post_id || !$title || !$description) {
        wp_send_json_error(array('message' => 'Invalid post ID or missing metadata'));
        return;
    }
    
    // Clear the cache
    delete_transient('meta_health_' . $post_id . '_' . md5($title . $description . $focus_keyword));
    
    wp_send_json_success(array('message' => 'Cache cleared successfully'));
}
} // end function_exists guard: seo_playground_ajax_refresh_meta_health

// AJAX handler for marking post as reoptimized
add_action('wp_ajax_seo_playground_mark_as_reoptimized', 'seo_playground_ajax_mark_as_reoptimized');
if (!function_exists('seo_playground_ajax_mark_as_reoptimized')) {
function seo_playground_ajax_mark_as_reoptimized() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    // Get post ID and check per-post capability
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    // Update the last refresh timestamp
    $current_time = current_time('mysql');
    update_post_meta($post_id, '_seo_playground_last_refresh', $current_time);
    
    // Update post modified date directly to avoid creating revisions
    global $wpdb;
    $wpdb->update(
        $wpdb->posts,
        array(
            'post_modified' => $current_time,
            'post_modified_gmt' => get_gmt_from_date($current_time)
        ),
        array('ID' => $post_id),
        array('%s', '%s'),
        array('%d')
    );
    clean_post_cache($post_id);
    
    wp_send_json_success(array(
        'message' => 'Post marked as reoptimized successfully',
        'timestamp' => $current_time,
        'formatted_time' => gmdate('F j, Y \a\t g:i A', strtotime($current_time))
    ));
}
} // end function_exists guard: seo_playground_ajax_mark_as_reoptimized

// AJAX handler for saving SEO note
add_action('wp_ajax_seo_playground_save_note', 'seo_playground_ajax_save_note');
if (!function_exists('seo_playground_ajax_save_note')) {
function seo_playground_ajax_save_note() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    // Get post ID and check per-post capability
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $note_content = isset($_POST['note_content']) ? sanitize_textarea_field(wp_unslash($_POST['note_content'])) : '';

    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    // Save the note content
    update_post_meta($post_id, '_seo_playground_seo_note', $note_content);
    
    // Save the timestamp
    $current_time = current_time('mysql');
    update_post_meta($post_id, '_seo_playground_seo_note_timestamp', $current_time);
    
    wp_send_json_success(array(
        'message' => 'Note saved successfully',
        'timestamp' => $current_time,
        'formatted_time' => gmdate('F j, Y \a\t g:i A', strtotime($current_time)),
        'character_count' => strlen($note_content)
    ));
}
} // end function_exists guard: seo_playground_ajax_save_note

// AJAX handler for getting SEO note
add_action('wp_ajax_seo_playground_get_note', 'seo_playground_ajax_get_note');
if (!function_exists('seo_playground_ajax_get_note')) {
function seo_playground_ajax_get_note() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    // Get post ID and check per-post capability
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    // Get the note content and timestamp
    $note_content = get_post_meta($post_id, '_seo_playground_seo_note', true);
    $timestamp = get_post_meta($post_id, '_seo_playground_seo_note_timestamp', true);
    
    wp_send_json_success(array(
        'note_content' => $note_content,
        'timestamp' => $timestamp,
        'formatted_time' => $timestamp ? gmdate('F j, Y \a\t g:i A', strtotime($timestamp)) : '',
        'character_count' => strlen($note_content)
    ));
}
} // end function_exists guard: seo_playground_ajax_get_note

// ════════════════════════════════════════════════════════════════
// GSC Page Data — Fetch Search Console data for a specific page
// ════════════════════════════════════════════════════════════════

add_action('wp_ajax_almaseo_fetch_gsc_page_data', 'almaseo_ajax_fetch_gsc_page_data');
if (!function_exists('almaseo_ajax_fetch_gsc_page_data')) {
function almaseo_ajax_fetch_gsc_page_data() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'almaseo_gsc_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Check connection
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }

    // Get parameters
    $page_url = isset($_POST['page_url']) ? esc_url_raw(wp_unslash($_POST['page_url'])) : '';
    $days = isset($_POST['days']) ? intval($_POST['days']) : 28;

    if (empty($page_url)) {
        wp_send_json_error(array('message' => 'page_url is required'));
        return;
    }

    // Clamp days to allowed values
    if (!in_array($days, array(7, 28, 90), true)) {
        $days = 28;
    }

    // Get stored credentials
    $app_password = get_option('almaseo_app_password', '');
    $username = get_option('almaseo_connected_user', '');
    $site_url = get_site_url();

    if (empty($app_password) || empty($username)) {
        wp_send_json_error(array('message' => 'Missing credentials'));
        return;
    }

    // Check transient cache (5 min TTL per page+days combo)
    $cache_key = 'almaseo_gsc_' . md5($page_url . '_' . $days);
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        wp_send_json_success($cached);
        return;
    }

    // Call AlmaSEO Dashboard API
    $api_url = 'https://api.almaseo.com/api/plugin/gsc-page-data';

    $response = wp_remote_post($api_url, array(
        'timeout' => 30,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($username . ':' . $app_password)
        ),
        'body' => wp_json_encode(array(
            'site_url'  => $site_url,
            'page_url'  => $page_url,
            'days'      => $days
        ))
    ));

    if (is_wp_error($response)) {
        wp_send_json_error(array(
            'message' => 'Could not reach AlmaSEO server: ' . $response->get_error_message()
        ));
        return;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $result = json_decode($response_body, true);

    if ($response_code === 401) {
        wp_send_json_error(array('message' => 'Authentication failed. Please reconnect your site.'));
        return;
    }

    if ($response_code !== 200 || empty($result['success'])) {
        $error_msg = isset($result['error']) ? $result['error'] : 'Unexpected error from AlmaSEO API';
        wp_send_json_error(array('message' => $error_msg));
        return;
    }

    // Cache successful responses for 5 minutes
    $data = isset($result['data']) ? $result['data'] : $result;
    set_transient($cache_key, $data, 5 * MINUTE_IN_SECONDS);

    wp_send_json_success($data);
}
} // end function_exists guard: almaseo_ajax_fetch_gsc_page_data

// ════════════════════════════════════════════════════════════════
// GA Page Data — Fetch Google Analytics (GA4) data for a specific page
// Mirrors almaseo_ajax_fetch_gsc_page_data; calls the dashboard's
// /api/plugin/ga-page-data endpoint with Basic Auth.
// ════════════════════════════════════════════════════════════════

add_action('wp_ajax_almaseo_fetch_ga_page_data', 'almaseo_ajax_fetch_ga_page_data');
if (!function_exists('almaseo_ajax_fetch_ga_page_data')) {
function almaseo_ajax_fetch_ga_page_data() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'almaseo_ga_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
        return;
    }

    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }

    // Check connection
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }

    // Get parameters
    $page_url = isset($_POST['page_url']) ? esc_url_raw(wp_unslash($_POST['page_url'])) : '';
    $days = isset($_POST['days']) ? intval($_POST['days']) : 28;

    if (empty($page_url)) {
        wp_send_json_error(array('message' => 'page_url is required'));
        return;
    }

    // Clamp days to allowed values
    if (!in_array($days, array(7, 28, 90), true)) {
        $days = 28;
    }

    // Get stored credentials
    $app_password = get_option('almaseo_app_password', '');
    $username = get_option('almaseo_connected_user', '');
    $site_url = get_site_url();

    if (empty($app_password) || empty($username)) {
        wp_send_json_error(array('message' => 'Missing credentials'));
        return;
    }

    // Check transient cache (5 min TTL per page+days combo)
    $cache_key = 'almaseo_ga_' . md5($page_url . '_' . $days);
    $cached = get_transient($cache_key);
    if ($cached !== false) {
        wp_send_json_success($cached);
        return;
    }

    // Call AlmaSEO Dashboard API
    $api_url = 'https://api.almaseo.com/api/plugin/ga-page-data';

    $response = wp_remote_post($api_url, array(
        'timeout' => 30,
        'headers' => array(
            'Content-Type'  => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($username . ':' . $app_password)
        ),
        'body' => wp_json_encode(array(
            'site_url'  => $site_url,
            'page_url'  => $page_url,
            'days'      => $days
        ))
    ));

    if (is_wp_error($response)) {
        wp_send_json_error(array(
            'message' => 'Could not reach AlmaSEO server: ' . $response->get_error_message()
        ));
        return;
    }

    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    $result = json_decode($response_body, true);

    if ($response_code === 401) {
        wp_send_json_error(array('message' => 'Authentication failed. Please reconnect your site.'));
        return;
    }

    if ($response_code !== 200 || empty($result['success'])) {
        $error_msg = isset($result['error']) ? $result['error'] : 'Unexpected error from AlmaSEO API';
        wp_send_json_error(array('message' => $error_msg));
        return;
    }

    // Cache successful responses for 5 minutes
    $data = isset($result['data']) ? $result['data'] : $result;
    set_transient($cache_key, $data, 5 * MINUTE_IN_SECONDS);

    wp_send_json_success($data);
}
} // end function_exists guard: almaseo_ajax_fetch_ga_page_data

/* ─────────────────────────────────────────────────────────────────────────
 * Schema JSON-LD Preview — backend-rendered preview using the same builders
 * the frontend uses, so the editor preview matches what's actually emitted
 * (or would be emitted if the global Advanced Schema gate were on).
 *
 * Returns the would-be JSON-LD plus diagnostics about each gate, so when a
 * type doesn't show up on the frontend the editor can tell the user *why*.
 * ──────────────────────────────────────────────────────────────────────── */
add_action('wp_ajax_almaseo_get_schema_preview', 'almaseo_ajax_get_schema_preview');
if (!function_exists('almaseo_ajax_get_schema_preview')) {
function almaseo_ajax_get_schema_preview() {
    $post_id = isset($_POST['post_id']) ? absint(wp_unslash($_POST['post_id'])) : 0;
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }

    $post = get_post($post_id);
    if (!$post instanceof WP_Post) {
        wp_send_json_error(array('message' => 'Post not found'));
    }

    $settings = get_option('almaseo_schema_advanced_settings', array(
        'enabled'              => false,
        'site_represents'      => 'organization',
        'site_name'            => '',
        'site_logo_url'        => '',
        'site_social_profiles' => array(),
    ));
    $diag = array(
        'feature_available'   => function_exists('almaseo_feature_available') ? (bool) almaseo_feature_available('schema_advanced') : false,
        'global_enabled'      => !empty($settings['enabled']),
        'per_post_disable'    => (bool) get_post_meta($post_id, '_almaseo_schema_disable', true),
        'meta_schema_type'    => (string) get_post_meta($post_id, '_almaseo_schema_type', true),
        'meta_schema_primary' => (string) get_post_meta($post_id, '_almaseo_schema_primary_type', true),
    );

    $type = function_exists('almaseo_determine_schema_type')
        ? almaseo_determine_schema_type($post, $settings)
        : ($diag['meta_schema_primary'] ?: ($diag['meta_schema_type'] ?: 'Article'));
    $diag['resolved_type'] = $type;

    $graph = array();
    if (function_exists('almaseo_build_knowledge_graph_node')) {
        $kg = almaseo_build_knowledge_graph_node($settings);
        if ($kg) {
            $graph[] = $kg;
        }
    }
    if (function_exists('almaseo_build_primary_schema_node')) {
        $primary = almaseo_build_primary_schema_node($post, $settings);
        if ($primary) {
            $graph[] = $primary;
        }
    }

    $data = array(
        '@context' => 'https://schema.org',
        '@graph'   => $graph,
    );

    $blockers = array();
    if (!$diag['feature_available']) {
        $blockers[] = 'Schema (Advanced) is gated to Pro — almaseo_feature_available("schema_advanced") returned false.';
    }
    if (!$diag['global_enabled']) {
        $blockers[] = 'Global "Advanced Schema → Enabled" toggle is OFF (option almaseo_schema_advanced_settings.enabled).';
    }
    if ($diag['per_post_disable']) {
        $blockers[] = 'Per-post override _almaseo_schema_disable is set on this post.';
    }
    if (empty($graph)) {
        $blockers[] = 'No nodes were built (knowledge graph + primary node both empty).';
    }

    wp_send_json_success(array(
        'json'              => wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        'type'              => $type,
        'diagnostics'       => $diag,
        'frontend_blockers' => $blockers,
    ));
}
} // end function_exists guard: almaseo_ajax_get_schema_preview

/* ────────────────────────────────────────────────────────────────────────
 * LocalBusiness schema — "Fill from AlmaSEO" support.
 *
 * Returns the connected site's business profile (already cached locally by
 * AlmaSEO\Connection\Site_Profile, pulled from the dashboard) mapped onto the
 * LocalBusiness metabox field names, so the editor can one-click populate
 * name/address/phone/email/Google Maps URL instead of re-typing data that
 * already lives on the client's AlmaSEO dashboard profile.
 * ──────────────────────────────────────────────────────────────────────── */
add_action('wp_ajax_almaseo_lb_fill_from_profile', 'almaseo_ajax_lb_fill_from_profile');
if (!function_exists('almaseo_ajax_lb_fill_from_profile')) {
function almaseo_ajax_lb_fill_from_profile() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
    }
    check_ajax_referer('almaseo_lb_fill', 'nonce');

    if (!class_exists('AlmaSEO\\Connection\\Site_Profile')) {
        wp_send_json_error(array('message' => 'The AlmaSEO connection module is unavailable.'));
    }

    // Refresh the cached profile if it's stale, then read it.
    \AlmaSEO\Connection\Site_Profile::ensure_fresh();
    $profile = \AlmaSEO\Connection\Site_Profile::profile_data();

    if (empty($profile) || !is_array($profile)) {
        wp_send_json_error(array('message' => 'No business profile is available from the AlmaSEO dashboard for this site.'));
    }

    $areas = '';
    if (!empty($profile['service_areas']) && is_array($profile['service_areas'])) {
        $areas = implode(', ', $profile['service_areas']);
    }

    // Keys are the LocalBusiness metabox input name="" attributes.
    $fields = array(
        'almaseo_lb_phone'          => isset($profile['phone']) ? $profile['phone'] : '',
        'almaseo_lb_email'          => isset($profile['email']) ? $profile['email'] : '',
        'almaseo_lb_street'         => isset($profile['street_address']) ? $profile['street_address'] : '',
        'almaseo_lb_city'           => isset($profile['city']) ? $profile['city'] : '',
        'almaseo_lb_state'          => isset($profile['state']) ? $profile['state'] : '',
        'almaseo_lb_zip'            => isset($profile['zip_code']) ? $profile['zip_code'] : '',
        'almaseo_lb_google_profile' => isset($profile['google_maps_url']) ? $profile['google_maps_url'] : '',
        'almaseo_lb_area_served'    => $areas,
    );

    // Opening hours, if the dashboard sourced them from Google Business Profile.
    // Shape: { day => { open: 'HH:MM', close: 'HH:MM' } }.
    $hours = (isset($profile['opening_hours']) && is_array($profile['opening_hours']))
        ? $profile['opening_hours']
        : array();

    if (!array_filter($fields) && empty($hours)) {
        wp_send_json_error(array('message' => 'The dashboard profile for this site has no address, contact details, or hours yet. Add them on the AlmaSEO dashboard, then try again.'));
    }

    wp_send_json_success(array('fields' => $fields, 'hours' => $hours));
}
} // end function_exists guard: almaseo_ajax_lb_fill_from_profile

/* ────────────────────────────────────────────────────────────────────────
 * Person schema panel — "Fill from user".
 * Maps a chosen WordPress user's profile onto the Person schema metabox
 * fields so an editor can one-click populate name/job title/photo/links
 * instead of retyping them. Reuses the same author profile meta
 * (almaseo_author_job_title / almaseo_author_image / almaseo_author_same_as)
 * that powers the linked-author schema, so the two stay consistent.
 * Note: the Person node's display name comes from the page title, so it is
 * intentionally not returned here.
 * ──────────────────────────────────────────────────────────────────────── */
add_action('wp_ajax_almaseo_person_fill_from_user', 'almaseo_ajax_person_fill_from_user');
if (!function_exists('almaseo_ajax_person_fill_from_user')) {
function almaseo_ajax_person_fill_from_user() {
    if (!check_ajax_referer('almaseo_person_fill', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }
    if (!current_user_can('edit_posts') || !current_user_can('list_users')) {
        wp_send_json_error(array('message' => 'Insufficient permissions.'));
    }

    $user_id = isset($_POST['user_id']) ? absint($_POST['user_id']) : 0;
    $user    = $user_id ? get_userdata($user_id) : false;
    if (!$user) {
        wp_send_json_error(array('message' => 'User not found.'));
    }

    // Given/family name: prefer the dedicated WP name fields, fall back to
    // splitting the display name.
    $first = get_user_meta($user_id, 'first_name', true);
    $last  = get_user_meta($user_id, 'last_name', true);
    if (!$first && !$last && $user->display_name) {
        $parts = preg_split('/\s+/', trim($user->display_name), 2);
        $first = isset($parts[0]) ? $parts[0] : '';
        $last  = isset($parts[1]) ? $parts[1] : '';
    }

    // Photo: explicit author photo, else avatar/Gravatar.
    $image = get_user_meta($user_id, 'almaseo_author_image', true);
    if (!$image && function_exists('get_avatar_url')) {
        $image = get_avatar_url($user_id, array('size' => 192));
    }

    // sameAs: the profile website plus the AlmaSEO author social URLs.
    $same_as = array();
    if (!empty($user->user_url)) {
        $same_as[] = $user->user_url;
    }
    $extra = get_user_meta($user_id, 'almaseo_author_same_as', true);
    if ($extra) {
        foreach (preg_split('/\r\n|\r|\n/', $extra) as $line) {
            $line = trim($line);
            if ($line !== '') {
                $same_as[] = $line;
            }
        }
    }
    $same_as = array_values(array_unique(array_filter($same_as)));

    // Keys are the Person metabox input name="" attributes.
    $fields = array(
        'almaseo_person_given_name'  => $first,
        'almaseo_person_family_name' => $last,
        'almaseo_person_job_title'   => get_user_meta($user_id, 'almaseo_author_job_title', true),
        'almaseo_person_email'       => $user->user_email,
        'almaseo_person_image'       => $image,
        'almaseo_person_same_as'     => implode("\n", $same_as),
    );

    wp_send_json_success(array('fields' => $fields));
}
} // end function_exists guard: almaseo_ajax_person_fill_from_user

/* ────────────────────────────────────────────────────────────────────────
 * Overview "Reoptimize" — re-run the per-post optimization scorecard.
 *
 * The Overview table's Score/Status columns are derived from an 8-check
 * scorecard computed from the post's current SEO meta + content. The
 * Reoptimize button re-evaluates that scorecard live (no dashboard / no
 * remote API) so the row reflects edits made since the page was loaded.
 *
 * almaseo_overview_compute_scorecard() is the single source of truth shared
 * by the Overview template (admin/pages/overview.php) and this handler, so
 * the two never drift apart.
 * ──────────────────────────────────────────────────────────────────────── */
if (!function_exists('almaseo_overview_compute_scorecard')) {
function almaseo_overview_compute_scorecard($post_id) {
    $post_id = absint($post_id);

    $seo_title           = get_post_meta($post_id, '_almaseo_title', true);
    $seo_description     = get_post_meta($post_id, '_almaseo_description', true);
    $schema_type         = get_post_meta($post_id, '_almaseo_schema_type', true);
    $keyword_suggestions = get_post_meta($post_id, '_almaseo_kw_suggestions', true);
    $focus_keyword       = get_post_meta($post_id, '_almaseo_focus_keyword', true);

    $content = (string) get_post_field('post_content', $post_id);

    // Two checks have no per-post data source in the current architecture and
    // are always false (internal_links, ai_rewrite); reoptimization is always
    // true. Kept identical to the template's original inline logic.
    $checks = array(
        'seo_title'        => ! empty($seo_title),
        'meta_description' => ! empty($seo_description),
        'focus_keywords'   => ! empty($focus_keyword) || ! empty($keyword_suggestions),
        'internal_links'   => false,
        'schema_type'      => ! empty($schema_type) && $schema_type !== 'none',
        'ai_rewrite'       => false,
        'content_length'   => strlen($content) >= 300,
        'reoptimization'   => true,
    );

    $passed = count(array_filter($checks));
    $total  = count($checks);
    $pct    = $total > 0 ? (int) round(($passed / $total) * 100) : 0;

    if ($passed >= 6) {
        $status = 'Fully Optimized';
        $status_class = 'status-optimized';
        $status_data  = 'optimized';
    } elseif ($passed >= 4) {
        $status = 'Needs Review';
        $status_class = 'status-review';
        $status_data  = 'needs-review';
    } else {
        $status = 'Missing Data';
        $status_class = 'status-missing';
        $status_data  = 'missing-data';
    }

    $last_ai_action = 'None';
    if (! empty($keyword_suggestions)) {
        $last_ai_action = 'Keyword Suggestions';
    } elseif (! empty($seo_title) || ! empty($seo_description)) {
        $last_ai_action = 'Metadata';
    } elseif (! empty($schema_type) && $schema_type !== 'none') {
        $last_ai_action = 'Schema';
    }

    return array(
        'passed'         => $passed,
        'total'          => $total,
        'percentage'     => $pct,
        'status'         => $status,
        'status_class'   => $status_class,
        'status_data'    => $status_data,
        'last_ai_action' => $last_ai_action,
        'checks'         => $checks,
    );
}
}

add_action('wp_ajax_seo_playground_overview_reoptimize', 'seo_playground_ajax_overview_reoptimize');
if (!function_exists('seo_playground_ajax_overview_reoptimize')) {
function seo_playground_ajax_overview_reoptimize() {
    if (!isset($_POST['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['nonce'])), 'almaseo_overview_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
    }

    $post_id = isset($_POST['post_id']) ? intval(wp_unslash($_POST['post_id'])) : 0;
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
    }

    wp_send_json_success(almaseo_overview_compute_scorecard($post_id));
}
} // end function_exists guard: seo_playground_ajax_overview_reoptimize
