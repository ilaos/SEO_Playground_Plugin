<?php
/**
 * AlmaSEO Evergreen Feature - Meta Helpers
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get Evergreen status for a post
 * 
 * @param int $post_id Post ID
 * @return string Status (evergreen|watch|stale) or empty string
 */
function almaseo_eg_get_status($post_id) {
    return get_post_meta($post_id, ALMASEO_EG_META_STATUS, true) ?: '';
}

/**
 * Set Evergreen status for a post
 * 
 * @param int $post_id Post ID
 * @param string $status Status (evergreen|watch|stale)
 * @return bool Success
 */
function almaseo_eg_set_status($post_id, $status) {
    $valid_statuses = array(
        ALMASEO_EG_STATUS_EVERGREEN,
        ALMASEO_EG_STATUS_WATCH,
        ALMASEO_EG_STATUS_STALE
    );
    
    if (!in_array($status, $valid_statuses, true)) {
        return false;
    }
    
    return update_post_meta($post_id, ALMASEO_EG_META_STATUS, $status);
}

/**
 * Get last checked datetime for a post
 * 
 * @param int $post_id Post ID
 * @return string Datetime (Y-m-d H:i:s) or empty string
 */
function almaseo_eg_get_last_checked($post_id) {
    return get_post_meta($post_id, ALMASEO_EG_META_LAST_CHECKED, true) ?: '';
}

/**
 * Set last checked datetime for a post
 * 
 * @param int $post_id Post ID
 * @param string $datetime Datetime (Y-m-d H:i:s), defaults to current
 * @return bool Success
 */
function almaseo_eg_set_last_checked($post_id, $datetime = null) {
    if ($datetime === null) {
        $datetime = current_time('mysql');
    }
    
    return update_post_meta($post_id, ALMASEO_EG_META_LAST_CHECKED, $datetime);
}

/**
 * Get clicks data for a post
 * 
 * @param int $post_id Post ID
 * @return array ['clicks_90d' => int, 'clicks_prev90d' => int]
 */
function almaseo_eg_get_clicks($post_id) {
    return array(
        'clicks_90d' => (int) get_post_meta($post_id, ALMASEO_EG_META_CLICKS_90D, true),
        'clicks_prev90d' => (int) get_post_meta($post_id, ALMASEO_EG_META_CLICKS_PREV90D, true)
    );
}

/**
 * Set clicks data for a post
 * 
 * @param int $post_id Post ID
 * @param int $clicks_90d Clicks in last 90 days
 * @param int $clicks_prev90d Clicks in previous 90 days
 * @return bool Success
 */
function almaseo_eg_set_clicks($post_id, $clicks_90d, $clicks_prev90d) {
    $result1 = update_post_meta($post_id, ALMASEO_EG_META_CLICKS_90D, (int) $clicks_90d);
    $result2 = update_post_meta($post_id, ALMASEO_EG_META_CLICKS_PREV90D, (int) $clicks_prev90d);
    
    return $result1 && $result2;
}

/**
 * Get the dashboard AI freshness analysis for a post.
 *
 * Returns the LLM staleness analysis pushed by the AlmaSEO dashboard, decoded
 * and normalized. The `is_current` flag reports whether the post content still
 * matches the content that was analyzed (drift detection) — callers should
 * fall back to the local heuristic when it is false.
 *
 * @param int $post_id Post ID.
 * @return array|null { score, summary, findings[], content_hash, analyzed_at,
 *                      is_current } or null when no analysis is stored.
 */
function almaseo_eg_get_ai_freshness($post_id) {
    $raw = get_post_meta($post_id, ALMASEO_EG_META_AI_FRESHNESS, true);
    if (empty($raw)) {
        return null;
    }

    $data = is_array($raw) ? $raw : json_decode($raw, true);
    if (!is_array($data) || !isset($data['score'])) {
        return null;
    }

    $post         = get_post($post_id);
    $current_hash = $post ? md5((string) $post->post_content) : '';
    $stored_hash  = isset($data['content_hash']) ? (string) $data['content_hash'] : '';

    return array(
        'score'        => max(0, min(100, (int) $data['score'])),
        'summary'      => isset($data['summary']) ? (string) $data['summary'] : '',
        'findings'     => isset($data['findings']) && is_array($data['findings']) ? $data['findings'] : array(),
        'content_hash' => $stored_hash,
        'analyzed_at'  => isset($data['analyzed_at']) ? (string) $data['analyzed_at'] : '',
        'is_current'   => ($stored_hash !== '' && $stored_hash === $current_hash),
    );
}

/**
 * Get notes for a post
 *
 * @param int $post_id Post ID
 * @return array ['broken_links' => int, 'seasonal' => bool, 'pinned' => bool]
 */
function almaseo_eg_get_notes($post_id) {
    $notes_json = get_post_meta($post_id, ALMASEO_EG_META_NOTES, true);
    
    $defaults = array(
        'broken_links' => 0,
        'seasonal' => false,
        'pinned' => false
    );
    
    if (empty($notes_json)) {
        return $defaults;
    }
    
    $notes = json_decode($notes_json, true);
    
    if (!is_array($notes)) {
        return $defaults;
    }
    
    return array_merge($defaults, $notes);
}

/**
 * Set notes for a post
 * 
 * @param int $post_id Post ID
 * @param array $notes ['broken_links' => int, 'seasonal' => bool, 'pinned' => bool]
 * @return bool Success
 */
function almaseo_eg_set_notes($post_id, $notes) {
    $defaults = array(
        'broken_links' => 0,
        'seasonal' => false,
        'pinned' => false
    );
    
    $notes = array_merge($defaults, $notes);
    
    // Ensure proper types
    $notes['broken_links'] = (int) $notes['broken_links'];
    $notes['seasonal'] = (bool) $notes['seasonal'];
    $notes['pinned'] = (bool) $notes['pinned'];
    
    $json = wp_json_encode($notes);
    
    return update_post_meta($post_id, ALMASEO_EG_META_NOTES, $json);
}

/**
 * Check if a post is pinned as evergreen
 * 
 * @param int $post_id Post ID
 * @return bool True if pinned
 */
function almaseo_eg_is_pinned($post_id) {
    $notes = almaseo_eg_get_notes($post_id);
    return !empty($notes['pinned']);
}

/**
 * Pin or unpin a post as evergreen
 * 
 * @param int $post_id Post ID
 * @param bool $pinned Whether to pin
 * @return bool Success
 */
function almaseo_eg_set_pinned($post_id, $pinned = true) {
    $notes = almaseo_eg_get_notes($post_id);
    $notes['pinned'] = (bool) $pinned;
    
    $result = almaseo_eg_set_notes($post_id, $notes);
    
    // If pinning, also set status to evergreen
    if ($pinned && $result) {
        almaseo_eg_set_status($post_id, ALMASEO_EG_STATUS_EVERGREEN);
    }
    
    return $result;
}

/**
 * Mark a post as refreshed (reset to evergreen)
 * 
 * @param int $post_id Post ID
 * @return bool Success
 */
function almaseo_eg_mark_refreshed($post_id) {
    $result1 = almaseo_eg_set_status($post_id, ALMASEO_EG_STATUS_EVERGREEN);
    $result2 = almaseo_eg_set_last_checked($post_id);
    
    return $result1 && $result2;
}

/**
 * Get all Evergreen meta for a post
 * 
 * @param int $post_id Post ID
 * @return array All meta data
 */
function almaseo_eg_get_all_meta($post_id) {
    $clicks = almaseo_eg_get_clicks($post_id);
    $notes = almaseo_eg_get_notes($post_id);
    
    return array(
        'status' => almaseo_eg_get_status($post_id),
        'last_checked' => almaseo_eg_get_last_checked($post_id),
        'clicks_90d' => $clicks['clicks_90d'],
        'clicks_prev90d' => $clicks['clicks_prev90d'],
        'broken_links' => $notes['broken_links'],
        'seasonal' => $notes['seasonal'],
        'pinned' => $notes['pinned']
    );
}

/**
 * Clear all Evergreen meta for a post
 *
 * @param int $post_id Post ID
 * @return bool Success
 */
function almaseo_eg_clear_meta($post_id) {
    delete_post_meta($post_id, ALMASEO_EG_META_STATUS);
    delete_post_meta($post_id, ALMASEO_EG_META_LAST_CHECKED);
    delete_post_meta($post_id, ALMASEO_EG_META_CLICKS_90D);
    delete_post_meta($post_id, ALMASEO_EG_META_CLICKS_PREV90D);
    delete_post_meta($post_id, ALMASEO_EG_META_NOTES);

    // Advanced fields (Pro)
    delete_post_meta($post_id, '_almaseo_evergreen_refresh_score');
    delete_post_meta($post_id, '_almaseo_evergreen_ai_freshness_score');
    delete_post_meta($post_id, '_almaseo_evergreen_risk_level');
    delete_post_meta($post_id, '_almaseo_evergreen_last_refresh_reason');
    delete_post_meta($post_id, '_almaseo_evergreen_manual_priority');

    return true;
}

/**
 * Register Advanced Evergreen meta fields (Pro)
 * Registers meta fields for REST API and Gutenberg access
 *
 * @since 6.5.0
 */
function almaseo_register_evergreen_advanced_meta_fields() {
    $post_types = function_exists('almaseo_eg_get_supported_post_types')
        ? almaseo_eg_get_supported_post_types()
        : array('post', 'page');

    foreach ($post_types as $post_type) {
        // Refresh Score (0-100)
        register_post_meta($post_type, '_almaseo_evergreen_refresh_score', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'integer',
            'default' => 0,
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            },
            'sanitize_callback' => function($value) {
                return max(0, min(100, absint($value)));
            }
        ));

        // AI Freshness Score (0-100)
        register_post_meta($post_type, '_almaseo_evergreen_ai_freshness_score', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'integer',
            'default' => 0,
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            },
            'sanitize_callback' => function($value) {
                return max(0, min(100, absint($value)));
            }
        ));

        // Risk Level (low|medium|high)
        register_post_meta($post_type, '_almaseo_evergreen_risk_level', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'default' => '',
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            },
            'sanitize_callback' => function($value) {
                $valid = array('low', 'medium', 'high');
                return in_array($value, $valid, true) ? $value : '';
            }
        ));

        // Last Refresh Reason (free text)
        register_post_meta($post_type, '_almaseo_evergreen_last_refresh_reason', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'default' => '',
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            },
            'sanitize_callback' => 'sanitize_text_field'
        ));

        // Manual Priority (0-3: 0=auto, 1=low, 2=medium, 3=high)
        register_post_meta($post_type, '_almaseo_evergreen_manual_priority', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'integer',
            'default' => 0,
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            },
            'sanitize_callback' => function($value) {
                return max(0, min(3, absint($value)));
            }
        ));
    }
}
add_action('init', 'almaseo_register_evergreen_advanced_meta_fields');