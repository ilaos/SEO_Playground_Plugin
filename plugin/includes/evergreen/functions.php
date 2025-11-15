<?php
/**
 * AlmaSEO Evergreen Common Functions
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get post evergreen status
 * 
 * @param int $post_id Post ID
 * @return string Status (evergreen, watch, stale, or empty)
 */
if (!function_exists('almaseo_eg_get_post_status')) {
function almaseo_eg_get_post_status($post_id) {
    $status = get_post_meta($post_id, '_almaseo_eg_status', true);
    
    if (empty($status)) {
        return '';
    }
    
    // Validate status
    $valid_statuses = array(
        ALMASEO_EG_STATUS_EVERGREEN,
        ALMASEO_EG_STATUS_WATCH,
        ALMASEO_EG_STATUS_STALE
    );
    
    if (!in_array($status, $valid_statuses)) {
        return '';
    }
    
    return $status;
}
}

/**
 * Set post evergreen status
 * 
 * @param int $post_id Post ID
 * @param string $status New status
 * @return bool Success
 */
if (!function_exists('almaseo_eg_set_post_status')) {
function almaseo_eg_set_post_status($post_id, $status) {
    // Validate status
    $valid_statuses = array(
        ALMASEO_EG_STATUS_EVERGREEN,
        ALMASEO_EG_STATUS_WATCH,
        ALMASEO_EG_STATUS_STALE
    );
    
    if (!in_array($status, $valid_statuses)) {
        return false;
    }
    
    return update_post_meta($post_id, '_almaseo_eg_status', $status);
}
}

/**
 * Calculate post ages
 * 
 * @param int $post_id Post ID
 * @return array Ages data
 */
if (!function_exists('almaseo_eg_calculate_post_ages')) {
function almaseo_eg_calculate_post_ages($post_id) {
    $post = get_post($post_id);
    
    if (!$post) {
        return array(
            'published_days' => 0,
            'updated_days' => 0
        );
    }
    
    $now = current_time('timestamp');
    $published = strtotime($post->post_date_gmt);
    $modified = strtotime($post->post_modified_gmt);
    
    $published_days = floor(($now - $published) / DAY_IN_SECONDS);
    $updated_days = floor(($now - $modified) / DAY_IN_SECONDS);
    
    return array(
        'published_days' => max(0, $published_days),
        'updated_days' => max(0, $updated_days)
    );
}
}

/**
 * Get post traffic data (stub - implement with GSC integration)
 * 
 * @param int $post_id Post ID
 * @return array Traffic data
 */
if (!function_exists('almaseo_eg_get_post_traffic')) {
function almaseo_eg_get_post_traffic($post_id) {
    // Get cached traffic data
    $traffic_data = get_post_meta($post_id, '_almaseo_eg_traffic_data', true);
    
    if (empty($traffic_data)) {
        return array(
            'current' => 0,
            'previous' => 0,
            'change_percent' => 0
        );
    }
    
    return $traffic_data;
}
}

// Note: almaseo_eg_get_dashboard_stats is defined in dashboard.php

// Note: almaseo_eg_get_at_risk_posts is defined in dashboard.php

// Note: almaseo_eg_get_weekly_snapshots is defined in dashboard.php

/**
 * Generate mock snapshots for demo purposes
 * 
 * @param string $date_range Date range
 * @return array Mock snapshots
 */
if (!function_exists('almaseo_eg_generate_mock_snapshots')) {
function almaseo_eg_generate_mock_snapshots($date_range = '30') {
    $snapshots = array();
    $weeks = intval($date_range) / 7;
    
    for ($i = $weeks; $i >= 0; $i--) {
        $timestamp = strtotime('-' . ($i * 7) . ' days');
        
        // Generate realistic looking data with trends
        $base_evergreen = 45 + ($weeks - $i) * 2;
        $base_watch = 30 - ($weeks - $i);
        $base_stale = 25 - ($weeks - $i);
        
        $snapshots[] = array(
            'timestamp' => $timestamp,
            'date' => date('Y-m-d', $timestamp),
            'evergreen' => $base_evergreen + rand(-5, 5),
            'watch' => $base_watch + rand(-3, 3),
            'stale' => $base_stale + rand(-2, 2)
        );
    }
    
    return $snapshots;
}
}

/**
 * Format relative time
 * 
 * @param int $timestamp Timestamp
 * @return string Formatted time
 */
if (!function_exists('almaseo_eg_format_relative_time')) {
function almaseo_eg_format_relative_time($timestamp) {
    if (empty($timestamp)) {
        return 'Never';
    }
    
    $now = current_time('timestamp');
    $diff = $now - $timestamp;
    
    if ($diff < 60) {
        return 'Just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return sprintf('%d minute%s ago', $mins, $mins !== 1 ? 's' : '');
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return sprintf('%d hour%s ago', $hours, $hours !== 1 ? 's' : '');
    } elseif ($diff < 2592000) {
        $days = floor($diff / 86400);
        return sprintf('%d day%s ago', $days, $days !== 1 ? 's' : '');
    } else {
        return date('M j, Y', $timestamp);
    }
}
}

/**
 * Get weekly snapshots with caching
 * 
 * @param int $weeks Number of weeks to retrieve
 * @return array Weekly snapshot data
 */
if (!function_exists('almaseo_eg_get_weekly_snapshots_cached')) {
function almaseo_eg_get_weekly_snapshots_cached( $weeks = 12 ) {
    $key = 'almaseo_eg_weekly_' . absint( $weeks );
    $cached = get_transient( $key );
    if ( $cached && ! empty( $cached['data'] ) ) {
        return $cached['data']; // serve immediately
    }

    // compute fresh if no cache
    $data = almaseo_eg_get_weekly_snapshots( $weeks );

    // store with timestamp (12h TTL; adjust to taste)
    set_transient( $key, array(
        'data'      => $data,
        'generated' => time(),
    ), 12 * HOUR_IN_SECONDS );

    return $data;
}
}