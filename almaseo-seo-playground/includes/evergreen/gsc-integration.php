<?php
/**
 * AlmaSEO Evergreen - Google Search Console Integration
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get GSC metrics for a URL
 * 
 * @param string $url URL to fetch metrics for
 * @param int $days Number of days to fetch
 * @param int $offset Days offset (for previous period)
 * @return array|false Metrics array or false on failure
 */
function almaseo_gsc_get_url_metrics($url, $days = 90, $offset = 0) {
    // Check if GSC credentials are configured
    $credentials = get_option('almaseo_gsc_credentials');
    
    if (empty($credentials) || empty($credentials['client_id']) || empty($credentials['client_secret'])) {
        // Return mock data for testing if not configured
        if (defined('WP_DEBUG') && WP_DEBUG) {
            return almaseo_gsc_get_mock_data($url, $days, $offset);
        }
        return false;
    }
    
    // Prepare date range
    $end_date = date('Y-m-d', strtotime("-{$offset} days"));
    $start_date = date('Y-m-d', strtotime("-" . ($days + $offset) . " days"));
    
    // Build API request
    $site_url = get_site_url();
    $api_url = 'https://www.googleapis.com/webmasters/v3/sites/' . urlencode($site_url) . '/searchAnalytics/query';
    
    $request_body = array(
        'startDate' => $start_date,
        'endDate' => $end_date,
        'dimensions' => array('page'),
        'dimensionFilterGroups' => array(
            array(
                'filters' => array(
                    array(
                        'dimension' => 'page',
                        'operator' => 'equals',
                        'expression' => $url
                    )
                )
            )
        ),
        'rowLimit' => 1
    );
    
    // Get access token
    $access_token = almaseo_gsc_get_access_token();
    
    if (!$access_token) {
        return false;
    }
    
    // Make API request
    $response = wp_remote_post($api_url, array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $access_token,
            'Content-Type' => 'application/json'
        ),
        'body' => wp_json_encode($request_body),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        error_log('AlmaSEO GSC Error: ' . $response->get_error_message());
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (empty($data['rows'])) {
        return array(
            'clicks' => 0,
            'impressions' => 0,
            'ctr' => 0,
            'position' => 0
        );
    }
    
    $row = $data['rows'][0];
    
    return array(
        'clicks' => isset($row['clicks']) ? intval($row['clicks']) : 0,
        'impressions' => isset($row['impressions']) ? intval($row['impressions']) : 0,
        'ctr' => isset($row['ctr']) ? floatval($row['ctr']) : 0,
        'position' => isset($row['position']) ? floatval($row['position']) : 0
    );
}

/**
 * Get mock data for testing
 * 
 * @param string $url URL
 * @param int $days Days
 * @param int $offset Offset
 * @return array Mock data
 */
function almaseo_gsc_get_mock_data($url, $days, $offset) {
    // Generate consistent mock data based on URL hash
    $seed = crc32($url . $days . $offset);
    srand($seed);
    
    // Generate realistic-looking data
    $base_clicks = rand(50, 500);
    $base_impressions = $base_clicks * rand(10, 30);
    
    // Add some variation based on offset (older data tends to be different)
    if ($offset > 0) {
        $variation = rand(80, 120) / 100;
        $base_clicks = intval($base_clicks * $variation);
        $base_impressions = intval($base_impressions * $variation);
    }
    
    return array(
        'clicks' => $base_clicks,
        'impressions' => $base_impressions,
        'ctr' => round($base_clicks / max($base_impressions, 1), 4),
        'position' => rand(5, 50) / 10
    );
}

/**
 * Get GSC access token
 * 
 * @return string|false Access token or false on failure
 */
function almaseo_gsc_get_access_token() {
    // Check for cached token
    $token_data = get_transient('almaseo_gsc_access_token');
    
    if ($token_data && !empty($token_data['token'])) {
        return $token_data['token'];
    }
    
    // Get refresh token
    $credentials = get_option('almaseo_gsc_credentials');
    
    if (empty($credentials['refresh_token'])) {
        return false;
    }
    
    // Request new access token
    $response = wp_remote_post('https://oauth2.googleapis.com/token', array(
        'body' => array(
            'client_id' => $credentials['client_id'],
            'client_secret' => $credentials['client_secret'],
            'refresh_token' => $credentials['refresh_token'],
            'grant_type' => 'refresh_token'
        ),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        return false;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (empty($data['access_token'])) {
        return false;
    }
    
    // Cache token (expires in 1 hour)
    set_transient('almaseo_gsc_access_token', array(
        'token' => $data['access_token'],
        'expires' => time() + 3600
    ), 3500);
    
    return $data['access_token'];
}

/**
 * Test GSC connection
 * 
 * @return bool True if connected
 */
function almaseo_gsc_test_connection() {
    $credentials = get_option('almaseo_gsc_credentials');
    
    if (empty($credentials)) {
        return false;
    }
    
    $token = almaseo_gsc_get_access_token();
    
    return !empty($token);
}

/**
 * Batch fetch GSC data for multiple URLs
 * 
 * @param array $urls Array of URLs
 * @param int $days Days to fetch
 * @return array URL => metrics mapping
 */
function almaseo_gsc_batch_fetch($urls, $days = 90) {
    $results = array();
    
    // Process in batches of 5 to avoid rate limiting
    $chunks = array_chunk($urls, 5);
    
    foreach ($chunks as $chunk) {
        foreach ($chunk as $url) {
            $current = almaseo_gsc_get_url_metrics($url, $days, 0);
            $previous = almaseo_gsc_get_url_metrics($url, $days, $days);
            
            $results[$url] = array(
                'current' => $current,
                'previous' => $previous
            );
        }
        
        // Small delay between batches
        if (count($chunks) > 1) {
            usleep(500000); // 0.5 second delay
        }
    }
    
    return $results;
}

/**
 * Update GSC data for a post
 * 
 * @param int $post_id Post ID
 * @return bool Success
 */
function almaseo_gsc_update_post_data($post_id) {
    $url = get_permalink($post_id);
    
    if (!$url) {
        return false;
    }
    
    // Fetch current and previous period data
    $current = almaseo_gsc_get_url_metrics($url, 90, 0);
    $previous = almaseo_gsc_get_url_metrics($url, 90, 90);
    
    if (!$current || !$previous) {
        return false;
    }
    
    // Update post meta
    almaseo_eg_set_clicks(
        $post_id,
        $current['clicks'],
        $previous['clicks']
    );
    
    return true;
}

/**
 * Schedule GSC data updates
 */
function almaseo_gsc_schedule_updates() {
    if (!wp_next_scheduled('almaseo_gsc_update_data')) {
        wp_schedule_event(time(), 'daily', 'almaseo_gsc_update_data');
    }
}
add_action('init', 'almaseo_gsc_schedule_updates');

/**
 * Run GSC data updates
 */
function almaseo_gsc_run_updates() {
    // Get posts that need GSC data update
    $args = array(
        'post_type' => array('post', 'page'),
        'post_status' => 'publish',
        'posts_per_page' => 50,
        'meta_query' => array(
            'relation' => 'OR',
            array(
                'key' => ALMASEO_EG_META_CLICKS_90D,
                'compare' => 'NOT EXISTS'
            ),
            array(
                'key' => ALMASEO_EG_META_LAST_CHECKED,
                'value' => date('Y-m-d H:i:s', strtotime('-7 days')),
                'compare' => '<',
                'type' => 'DATETIME'
            )
        ),
        'fields' => 'ids'
    );
    
    $query = new WP_Query($args);
    
    if ($query->have_posts()) {
        foreach ($query->posts as $post_id) {
            almaseo_gsc_update_post_data($post_id);
            
            // Small delay to avoid rate limiting
            usleep(200000); // 0.2 second delay
        }
    }
}
add_action('almaseo_gsc_update_data', 'almaseo_gsc_run_updates');