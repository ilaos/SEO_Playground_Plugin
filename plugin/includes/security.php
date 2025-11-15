<?php
/**
 * AlmaSEO Security Functions
 * Version: 2.1.0
 * Description: Security functions for data sanitization and access control
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Check if current user can edit posts
 */
function almaseo_user_can_edit_posts() {
    return current_user_can('edit_posts');
}

/**
 * Check if current user can manage SEO settings
 */
function almaseo_user_can_manage_seo() {
    return current_user_can('edit_posts') || current_user_can('edit_pages');
}

/**
 * Verify nonce for AJAX requests
 */
function almaseo_verify_nonce($nonce, $action = 'almaseo_nonce') {
    if (!wp_verify_nonce($nonce, $action)) {
        wp_die(__('Security check failed', 'almaseo-seo-playground'));
        return false;
    }
    return true;
}

/**
 * Sanitize SEO title
 */
function almaseo_sanitize_seo_title($title) {
    $title = sanitize_text_field($title);
    $title = wp_strip_all_tags($title);
    $title = substr($title, 0, 60); // Limit to 60 characters
    return $title;
}

/**
 * Sanitize meta description
 */
function almaseo_sanitize_meta_description($description) {
    $description = sanitize_textarea_field($description);
    $description = wp_strip_all_tags($description);
    $description = substr($description, 0, 160); // Limit to 160 characters
    return $description;
}

/**
 * Sanitize SEO notes
 */
function almaseo_sanitize_notes($notes) {
    $notes = sanitize_textarea_field($notes);
    $notes = wp_kses_post($notes); // Allow basic HTML
    $notes = substr($notes, 0, 1000); // Limit to 1000 characters
    return $notes;
}

/**
 * Sanitize schema type
 */
function almaseo_sanitize_schema_type($type) {
    $allowed_types = array(
        'None', 'Article', 'BlogPosting', 'NewsArticle', 
        'Product', 'Recipe', 'Event', 'Course', 
        'JobPosting', 'LocalBusiness', 'Organization',
        'Person', 'Review', 'HowTo', 'FAQ'
    );
    
    if (in_array($type, $allowed_types)) {
        return $type;
    }
    
    return 'None';
}

/**
 * Sanitize URL
 */
function almaseo_sanitize_url($url) {
    return esc_url_raw($url);
}

/**
 * Escape output for display
 */
function almaseo_esc_output($text, $type = 'text') {
    switch ($type) {
        case 'html':
            return wp_kses_post($text);
        case 'attr':
            return esc_attr($text);
        case 'url':
            return esc_url($text);
        case 'js':
            return esc_js($text);
        case 'textarea':
            return esc_textarea($text);
        default:
            return esc_html($text);
    }
}

/**
 * Validate and sanitize AJAX data
 */
function almaseo_sanitize_ajax_data($data) {
    $sanitized = array();
    
    foreach ($data as $key => $value) {
        switch ($key) {
            case 'seo_title':
                $sanitized[$key] = almaseo_sanitize_seo_title($value);
                break;
            case 'meta_description':
                $sanitized[$key] = almaseo_sanitize_meta_description($value);
                break;
            case 'seo_notes':
                $sanitized[$key] = almaseo_sanitize_notes($value);
                break;
            case 'schema_type':
                $sanitized[$key] = almaseo_sanitize_schema_type($value);
                break;
            case 'canonical_url':
            case 'og_image':
                $sanitized[$key] = almaseo_sanitize_url($value);
                break;
            case 'post_id':
            case 'user_id':
                $sanitized[$key] = absint($value);
                break;
            case 'robots_meta':
                $sanitized[$key] = sanitize_text_field($value);
                break;
            default:
                $sanitized[$key] = sanitize_text_field($value);
        }
    }
    
    return $sanitized;
}

/**
 * Rate limiting for API requests
 */
function almaseo_check_rate_limit($user_id = null) {
    if (!$user_id) {
        $user_id = get_current_user_id();
    }
    
    $transient_key = 'almaseo_rate_limit_' . $user_id;
    $requests = get_transient($transient_key);
    
    if ($requests === false) {
        set_transient($transient_key, 1, 60); // 1 minute window
        return true;
    }
    
    if ($requests >= 30) { // Max 30 requests per minute
        return false;
    }
    
    set_transient($transient_key, $requests + 1, 60);
    return true;
}

/**
 * Log security events
 */
function almaseo_log_security_event($event_type, $details = array()) {
    if (!defined('WP_DEBUG') || !WP_DEBUG) {
        return;
    }
    
    $log_entry = array(
        'timestamp' => current_time('mysql'),
        'user_id' => get_current_user_id(),
        'ip_address' => $_SERVER['REMOTE_ADDR'] ?? '',
        'event_type' => $event_type,
        'details' => $details
    );
    
    error_log('AlmaSEO Security: ' . json_encode($log_entry));
}

/**
 * Validate post type for SEO features
 */
function almaseo_is_valid_post_type($post_type) {
    $allowed_post_types = apply_filters('almaseo_allowed_post_types', array('post', 'page'));
    return in_array($post_type, $allowed_post_types);
}

/**
 * Check if SEO Playground should be displayed
 */
function almaseo_should_display_seo_playground() {
    // Check user capability
    if (!almaseo_user_can_manage_seo()) {
        return false;
    }
    
    // Check if on edit screen
    $screen = get_current_screen();
    if (!$screen || !in_array($screen->base, array('post', 'page'))) {
        return false;
    }
    
    // Check post type
    if (!almaseo_is_valid_post_type($screen->post_type)) {
        return false;
    }
    
    return true;
}

/**
 * Sanitize file upload
 */
function almaseo_sanitize_file_upload($file) {
    $allowed_types = array('jpg', 'jpeg', 'png', 'gif', 'webp');
    $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_types)) {
        return new WP_Error('invalid_file_type', __('Invalid file type', 'almaseo-seo-playground'));
    }
    
    // Check file size (max 2MB)
    if ($file['size'] > 2097152) {
        return new WP_Error('file_too_large', __('File size exceeds 2MB limit', 'almaseo-seo-playground'));
    }
    
    return $file;
}

/**
 * Generate secure token
 */
function almaseo_generate_secure_token($length = 32) {
    return wp_generate_password($length, false);
}

/**
 * Validate connection token
 */
function almaseo_validate_connection_token($token) {
    $stored_token = get_option('almaseo_connection_token');
    
    if (!$stored_token || !$token) {
        return false;
    }
    
    return hash_equals($stored_token, $token);
}