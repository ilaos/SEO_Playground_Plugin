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
    
    return true;
}