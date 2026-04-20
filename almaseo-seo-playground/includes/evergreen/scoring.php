<?php
/**
 * AlmaSEO Evergreen Feature - Scoring & Detection
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Calculate evergreen status for a single post
 * 
 * @param int $post_id Post ID
 * @return string|false Status or false on failure
 */
function almaseo_eg_calculate_single_post($post_id) {
    $post = get_post($post_id);
    if (!$post) {
        return false;
    }
    
    // Get post ages
    if (!function_exists('almaseo_get_post_ages')) {
        return false;
    }
    
    $ages = almaseo_get_post_ages($post);
    $settings = almaseo_eg_get_settings();
    
    // Default to evergreen
    $status = ALMASEO_EG_STATUS_EVERGREEN;
    $reasons = array();
    
    // Check if it's in grace period (new content)
    if ($ages['published_days'] < $settings['grace_days']) {
        $status = ALMASEO_EG_STATUS_EVERGREEN;
        $reasons[] = 'grace_period';
    }
    // Check for stale (very old)
    elseif ($ages['updated_days'] > $settings['stale_days']) {
        $status = ALMASEO_EG_STATUS_STALE;
        $reasons[] = 'old_content';
    }
    // Check for watch (getting old)
    elseif ($ages['updated_days'] > $settings['watch_days']) {
        $status = ALMASEO_EG_STATUS_WATCH;
        $reasons[] = 'aging_content';
    }
    
    // Check traffic decline if GSC data available
    if (function_exists('almaseo_eg_get_clicks')) {
        $clicks = almaseo_eg_get_clicks($post_id);
        if ($clicks['clicks_90d'] > 0 && $clicks['clicks_prev90d'] > 0) {
            $trend = almaseo_compute_trend($clicks['clicks_90d'], $clicks['clicks_prev90d']);
            
            if ($trend < $settings['decline_pct']) {
                // Significant traffic decline
                if ($status === ALMASEO_EG_STATUS_EVERGREEN) {
                    $status = ALMASEO_EG_STATUS_WATCH;
                    $reasons[] = 'traffic_decline';
                } elseif ($status === ALMASEO_EG_STATUS_WATCH) {
                    $status = ALMASEO_EG_STATUS_STALE;
                    $reasons[] = 'severe_traffic_decline';
                }
            }
        }
    }
    
    // Save the status
    update_post_meta($post_id, ALMASEO_EG_META_STATUS, $status);
    update_post_meta($post_id, '_almaseo_eg_status_reasons', $reasons);
    update_post_meta($post_id, '_almaseo_eg_last_calculated', time());
    
    return $status;
}

/**
 * Check if post title or body contains seasonal phrases
 * 
 * @param WP_Post|int $post Post object or ID
 * @return bool True if seasonal content detected
 */
function almaseo_is_seasonal_title_body($post) {
    if (is_numeric($post)) {
        $post = get_post($post);
    }
    
    if (!$post) {
        return false;
    }
    
    // Seasonal patterns (case-insensitive)
    $seasonal_patterns = array(
        'black friday',
        'cyber monday',
        'valentine',
        'christmas',
        'halloween',
        'thanksgiving',
        'easter',
        'new year',
        '202\d', // Years 2020-2029
        '\bjan\b|\bjanuary\b',
        '\bfeb\b|\bfebruary\b',
        '\bmar\b|\bmarch\b',
        '\bapr\b|\bapril\b',
        '\bmay\b',
        '\bjun\b|\bjune\b',
        '\bjul\b|\bjuly\b',
        '\baug\b|\baugust\b',
        '\bsep\b|\bseptember\b|\bsept\b',
        '\boct\b|\boctober\b',
        '\bnov\b|\bnovember\b',
        '\bdec\b|\bdecember\b',
        'summer sale',
        'winter sale',
        'spring sale',
        'fall sale',
        'back to school',
        'end of year',
        'holiday'
    );
    
    $pattern = '/(' . implode('|', $seasonal_patterns) . ')/i';
    
    // Check title
    if (preg_match($pattern, $post->post_title)) {
        return true;
    }
    
    // Check content
    if (preg_match($pattern, $post->post_content)) {
        return true;
    }
    
    return false;
}

/**
 * Detect explicit dates in text
 * 
 * @param string $str Text to check
 * @return bool True if dates detected
 */
function almaseo_detect_dates_in_text($str) {
    // Patterns for explicit dates
    $date_patterns = array(
        '\b20\d{2}\b', // Years 2000-2099
        '\b19\d{2}\b', // Years 1900-1999
        '\d{1,2}[\/\-]\d{1,2}[\/\-]\d{2,4}', // Date formats like 12/25/2023
        '\d{1,2}\s+(jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)[a-z]*\s+\d{2,4}', // 25 December 2023
        '(january|february|march|april|may|june|july|august|september|october|november|december)\s+\d{1,2},?\s+\d{4}', // December 25, 2023
    );
    
    $pattern = '/(' . implode('|', $date_patterns) . ')/i';
    
    return preg_match($pattern, $str) ? true : false;
}

/**
 * Get post age metrics
 * 
 * @param WP_Post|int $post Post object or ID
 * @return array ['published_days' => int, 'updated_days' => int]
 */
function almaseo_get_post_ages($post) {
    if (is_numeric($post)) {
        $post = get_post($post);
    }
    
    if (!$post) {
        return array('published_days' => 0, 'updated_days' => 0);
    }
    
    $now = current_time('timestamp', true); // GMT timestamp
    
    // Published age
    $published_timestamp = strtotime($post->post_date_gmt);
    $published_days = 0;
    if ($published_timestamp) {
        $published_days = floor(($now - $published_timestamp) / DAY_IN_SECONDS);
    }
    
    // Updated age
    $updated_timestamp = strtotime($post->post_modified_gmt);
    $updated_days = 0;
    if ($updated_timestamp) {
        $updated_days = floor(($now - $updated_timestamp) / DAY_IN_SECONDS);
    }
    
    return array(
        'published_days' => max(0, $published_days),
        'updated_days' => max(0, $updated_days)
    );
}

/**
 * Compute trend from click data
 * 
 * @param int $clicks_90d Clicks in last 90 days
 * @param int $clicks_prev90d Clicks in previous 90 days
 * @return float Percentage change (negative means decline)
 */
function almaseo_compute_trend($clicks_90d, $clicks_prev90d) {
    $clicks_90d = (int) $clicks_90d;
    $clicks_prev90d = (int) $clicks_prev90d;
    
    // If no previous data, treat as stable (0% change)
    if ($clicks_prev90d == 0) {
        if ($clicks_90d > 0) {
            return 100.0; // New traffic is 100% growth
        }
        return 0.0; // No data = stable
    }
    
    // Calculate percentage change
    $change = (($clicks_90d - $clicks_prev90d) / $clicks_prev90d) * 100;
    
    return round($change, 1);
}

/**
 * Score a post for evergreen status
 * 
 * @param WP_Post|int $post Post object or ID
 * @return array ['status' => string, 'reasons' => array, 'metrics' => array]
 */
function almaseo_score_evergreen($post) {
    if (is_numeric($post)) {
        $post_id = $post;
        $post = get_post($post);
    } else {
        $post_id = $post->ID;
    }
    
    if (!$post) {
        return array(
            'status' => '',
            'reasons' => array('Invalid post'),
            'metrics' => array()
        );
    }
    
    // Get settings
    $settings = get_option(ALMASEO_EG_SETTINGS_OPTION, array());
    $watch_days = isset($settings['watch_days']) ? (int) $settings['watch_days'] : ALMASEO_EG_DEFAULT_WATCH_DAYS;
    $stale_days = isset($settings['stale_days']) ? (int) $settings['stale_days'] : ALMASEO_EG_DEFAULT_STALE_DAYS;
    $watch_traffic_drop = isset($settings['watch_traffic_drop']) ? (float) $settings['watch_traffic_drop'] : ALMASEO_EG_DEFAULT_WATCH_TRAFFIC_DROP;
    $stale_traffic_drop = isset($settings['stale_traffic_drop']) ? (float) $settings['stale_traffic_drop'] : ALMASEO_EG_DEFAULT_STALE_TRAFFIC_DROP;
    
    // Get metrics
    $ages = almaseo_get_post_ages($post);
    $clicks = almaseo_eg_get_clicks($post_id);
    $notes = almaseo_eg_get_notes($post_id);
    $trend = almaseo_compute_trend($clicks['clicks_90d'], $clicks['clicks_prev90d']);
    
    // Check if seasonal
    $seasonal = almaseo_is_seasonal_title_body($post);
    if (!$seasonal && isset($notes['seasonal'])) {
        $seasonal = $notes['seasonal'];
    }
    
    // Check for explicit dates
    $has_dates = almaseo_detect_dates_in_text($post->post_title . ' ' . $post->post_content);
    
    $metrics = array(
        'published_days' => $ages['published_days'],
        'updated_days' => $ages['updated_days'],
        'trend' => $trend,
        'seasonal' => $seasonal,
        'has_dates' => $has_dates,
        'broken_links' => isset($notes['broken_links']) ? $notes['broken_links'] : 0,
        'pinned' => isset($notes['pinned']) ? $notes['pinned'] : false
    );
    
    $reasons = array();
    $status = ALMASEO_EG_STATUS_EVERGREEN; // Default
    
    // Check if manually pinned
    if ($metrics['pinned']) {
        return array(
            'status' => ALMASEO_EG_STATUS_EVERGREEN,
            'reasons' => array(__('Manually pinned as evergreen', 'almaseo-seo-playground')),
            'metrics' => $metrics
        );
    }
    
    // Determine status based on rules
    // Rule: Stale if updated_days > 365 OR traffic down > 40%
    if ($ages['updated_days'] > $stale_days) {
        $status = ALMASEO_EG_STATUS_STALE;
        $reasons[] = sprintf(__('Not updated in %d days', 'almaseo-seo-playground'), $ages['updated_days']);
    }
    
    if ($trend < 0 && abs($trend) >= $stale_traffic_drop) {
        $status = ALMASEO_EG_STATUS_STALE;
        $reasons[] = sprintf(__('Traffic declined by %.1f%%', 'almaseo-seo-playground'), abs($trend));
    }
    
    // Rule: Watch if updated_days > 180 OR traffic down > 20% (but not stale)
    if ($status !== ALMASEO_EG_STATUS_STALE) {
        if ($ages['updated_days'] > $watch_days) {
            $status = ALMASEO_EG_STATUS_WATCH;
            $reasons[] = sprintf(__('Updated %d days ago', 'almaseo-seo-playground'), $ages['updated_days']);
        } else if ($trend < 0 && abs($trend) >= $watch_traffic_drop) {
            $status = ALMASEO_EG_STATUS_WATCH;
            $reasons[] = sprintf(__('Traffic down %.1f%%', 'almaseo-seo-playground'), abs($trend));
        }
    }
    
    // Rule: Evergreen if updated_days <= 180 AND traffic not declining significantly
    if ($ages['updated_days'] <= $watch_days && ($trend >= 0 || abs($trend) < $watch_traffic_drop)) {
        if ($status !== ALMASEO_EG_STATUS_STALE) {
            $status = ALMASEO_EG_STATUS_EVERGREEN;
            if ($ages['updated_days'] <= 30) {
                $reasons[] = __('Recently updated', 'almaseo-seo-playground');
            }
            if ($trend > 0) {
                $reasons[] = sprintf(__('Traffic up %.1f%%', 'almaseo-seo-playground'), $trend);
            }
        }
    }
    
    // Special rule for seasonal content
    if ($seasonal && $status === ALMASEO_EG_STATUS_STALE) {
        $status = ALMASEO_EG_STATUS_WATCH;
        $reasons[] = __('Seasonal content', 'almaseo-seo-playground');
    }
    
    // Add seasonal note
    if ($seasonal) {
        $reasons[] = __('Contains seasonal keywords', 'almaseo-seo-playground');
    }
    
    // Add date note
    if ($has_dates) {
        $reasons[] = __('Contains explicit dates', 'almaseo-seo-playground');
    }
    
    // Add broken links note
    if ($metrics['broken_links'] > 0) {
        $reasons[] = sprintf(__('%d broken links', 'almaseo-seo-playground'), $metrics['broken_links']);
    }
    
    // Default reason if none
    if (empty($reasons)) {
        switch ($status) {
            case ALMASEO_EG_STATUS_EVERGREEN:
                $reasons[] = __('Content is fresh', 'almaseo-seo-playground');
                break;
            case ALMASEO_EG_STATUS_WATCH:
                $reasons[] = __('Monitor for updates', 'almaseo-seo-playground');
                break;
            case ALMASEO_EG_STATUS_STALE:
                $reasons[] = __('Needs refresh', 'almaseo-seo-playground');
                break;
        }
    }
    
    return array(
        'status' => $status,
        'reasons' => $reasons,
        'metrics' => $metrics
    );
}

/**
 * Get Evergreen settings with defaults
 *
 * @return array Settings array
 */
function almaseo_eg_get_settings() {
    $defaults = array(
        'watch_days' => ALMASEO_EG_DEFAULT_WATCH_DAYS,
        'stale_days' => ALMASEO_EG_DEFAULT_STALE_DAYS,
        'decline_pct' => ALMASEO_EG_DEFAULT_DECLINE_PCT,
        'grace_days' => ALMASEO_EG_DEFAULT_GRACE_DAYS,
        'enable_digest' => false
    );

    $settings = get_option(ALMASEO_EG_SETTINGS_OPTION, array());

    return array_merge($defaults, $settings);
}

/**
 * Compute AI Freshness Score (Advanced/Pro)
 *
 * Analyzes content quality, clarity, and AI-friendly signals to determine
 * how "fresh" the content appears to AI models.
 *
 * @param int|WP_Post $post Post ID or object
 * @return int Score from 0-100 (higher = needs more refresh)
 * @since 6.5.0
 */
function almaseo_eg_compute_ai_freshness_score($post) {
    if (!almaseo_feature_available('evergreen_advanced')) {
        return 0;
    }

    if (is_numeric($post)) {
        $post_id = $post;
        $post = get_post($post);
    } else {
        $post_id = $post->ID;
    }

    if (!$post) {
        return 0;
    }

    $score = 0;

    // Factor 1: Content age (0-40 points)
    // Older content gets higher score (needs more refresh)
    $ages = almaseo_get_post_ages($post);
    $updated_days = $ages['updated_days'];

    if ($updated_days > 730) { // > 2 years
        $score += 40;
    } elseif ($updated_days > 365) { // > 1 year
        $score += 30;
    } elseif ($updated_days > 180) { // > 6 months
        $score += 20;
    } elseif ($updated_days > 90) { // > 3 months
        $score += 10;
    }

    // Factor 2: Content has explicit dates (0-20 points)
    if (almaseo_detect_dates_in_text($post->post_title . ' ' . $post->post_content)) {
        $score += 20;
    }

    // Factor 3: Seasonal content (0-15 points)
    if (almaseo_is_seasonal_title_body($post)) {
        $score += 15;
    }

    // Factor 4: Content length and quality signals (0-25 points)
    $content_length = str_word_count(wp_strip_all_tags($post->post_content));

    if ($content_length < 300) {
        // Very short content needs freshening
        $score += 25;
    } elseif ($content_length < 600) {
        $score += 15;
    } elseif ($content_length > 3000) {
        // Very long content may be outdated
        $score += 10;
    }

    // Factor 5: Check for broken links indicator (0-10 points)
    $notes = almaseo_eg_get_notes($post_id);
    if (isset($notes['broken_links']) && $notes['broken_links'] > 0) {
        $score += min(10, $notes['broken_links'] * 2);
    }

    // Cap at 100
    return min(100, $score);
}

/**
 * Compute Refresh Score (Advanced/Pro)
 *
 * Combines AI freshness, traffic trends, and content age using weighted formula.
 * Higher score = higher priority for refresh.
 *
 * @param int|WP_Post $post Post ID or object
 * @return int Score from 0-100
 * @since 6.5.0
 */
function almaseo_eg_compute_refresh_score($post) {
    if (!almaseo_feature_available('evergreen_advanced')) {
        return 0;
    }

    if (is_numeric($post)) {
        $post_id = $post;
        $post = get_post($post);
    } else {
        $post_id = $post->ID;
    }

    if (!$post) {
        return 0;
    }

    // Get advanced settings
    $adv_settings = get_option('almaseo_evergreen_advanced_settings', array(
        'enabled' => false,
        'ai_freshness_weight' => 30,
        'traffic_trend_weight' => 40,
        'age_weight' => 30,
        'stale_days_threshold' => 365,
        'gsc_window_days' => 90
    ));

    if (!$adv_settings['enabled']) {
        return 0;
    }

    // Get weights (normalize to ensure they sum to 100)
    $ai_weight = (float) $adv_settings['ai_freshness_weight'];
    $traffic_weight = (float) $adv_settings['traffic_trend_weight'];
    $age_weight = (float) $adv_settings['age_weight'];

    $total_weight = $ai_weight + $traffic_weight + $age_weight;
    if ($total_weight <= 0) {
        $total_weight = 100;
        $ai_weight = 30;
        $traffic_weight = 40;
        $age_weight = 30;
    }

    // Normalize weights
    $ai_weight = ($ai_weight / $total_weight) * 100;
    $traffic_weight = ($traffic_weight / $total_weight) * 100;
    $age_weight = ($age_weight / $total_weight) * 100;

    // Component 1: AI Freshness Score (0-100)
    $ai_freshness = almaseo_eg_compute_ai_freshness_score($post);

    // Component 2: Traffic Trend Score (0-100)
    // More negative trend = higher score (needs refresh)
    $traffic_score = 0;
    $clicks = almaseo_eg_get_clicks($post_id);

    if ($clicks['clicks_90d'] > 0 || $clicks['clicks_prev90d'] > 0) {
        $trend = almaseo_compute_trend($clicks['clicks_90d'], $clicks['clicks_prev90d']);

        if ($trend < -50) {
            $traffic_score = 100; // Severe decline
        } elseif ($trend < -30) {
            $traffic_score = 80;
        } elseif ($trend < -15) {
            $traffic_score = 60;
        } elseif ($trend < 0) {
            $traffic_score = 40;
        } elseif ($trend < 15) {
            $traffic_score = 20; // Stable
        } else {
            $traffic_score = 0; // Growing
        }
    } else {
        // No traffic data = medium score
        $traffic_score = 50;
    }

    // Component 3: Age Score (0-100)
    // Older content = higher score
    $ages = almaseo_get_post_ages($post);
    $updated_days = $ages['updated_days'];
    $stale_threshold = $adv_settings['stale_days_threshold'];

    if ($updated_days >= $stale_threshold * 2) {
        $age_score = 100;
    } elseif ($updated_days >= $stale_threshold) {
        $age_score = 80;
    } elseif ($updated_days >= $stale_threshold * 0.5) {
        $age_score = 60;
    } elseif ($updated_days >= $stale_threshold * 0.25) {
        $age_score = 40;
    } else {
        $age_score = max(0, ($updated_days / ($stale_threshold * 0.25)) * 40);
    }

    // Calculate weighted score
    $refresh_score = (
        ($ai_freshness * $ai_weight / 100) +
        ($traffic_score * $traffic_weight / 100) +
        ($age_score * $age_weight / 100)
    );

    return (int) round(min(100, max(0, $refresh_score)));
}

/**
 * Determine Risk Level (Advanced/Pro)
 *
 * Classifies content risk based on refresh score and thresholds.
 *
 * @param int|WP_Post $post Post ID or object
 * @return string 'low', 'medium', or 'high'
 * @since 6.5.0
 */
function almaseo_eg_determine_risk_level($post) {
    if (!almaseo_feature_available('evergreen_advanced')) {
        return 'low';
    }

    // Get refresh score
    $refresh_score = almaseo_eg_compute_refresh_score($post);

    if ($refresh_score === 0) {
        return 'low';
    }

    // Get thresholds
    $adv_settings = get_option('almaseo_evergreen_advanced_settings', array(
        'high_risk_threshold' => 75,
        'medium_risk_threshold' => 50
    ));

    $high_threshold = (int) $adv_settings['high_risk_threshold'];
    $medium_threshold = (int) $adv_settings['medium_risk_threshold'];

    if ($refresh_score >= $high_threshold) {
        return 'high';
    } elseif ($refresh_score >= $medium_threshold) {
        return 'medium';
    } else {
        return 'low';
    }
}

/**
 * Update Advanced Evergreen Scores for a Post (Advanced/Pro)
 *
 * Computes and saves all advanced scoring meta fields.
 * Call this when recalculating evergreen metrics.
 *
 * @param int|WP_Post $post Post ID or object
 * @return bool Success
 * @since 6.5.0
 */
function almaseo_eg_update_advanced_scores($post) {
    if (!almaseo_feature_available('evergreen_advanced')) {
        return false;
    }

    if (is_numeric($post)) {
        $post_id = $post;
        $post = get_post($post);
    } else {
        $post_id = $post->ID;
    }

    if (!$post) {
        return false;
    }

    // Compute scores
    $ai_freshness = almaseo_eg_compute_ai_freshness_score($post);
    $refresh_score = almaseo_eg_compute_refresh_score($post);
    $risk_level = almaseo_eg_determine_risk_level($post);

    // Update meta fields
    update_post_meta($post_id, '_almaseo_evergreen_ai_freshness_score', $ai_freshness);
    update_post_meta($post_id, '_almaseo_evergreen_refresh_score', $refresh_score);
    update_post_meta($post_id, '_almaseo_evergreen_risk_level', $risk_level);

    return true;
}