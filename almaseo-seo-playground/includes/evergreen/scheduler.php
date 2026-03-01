<?php
/**
 * AlmaSEO Evergreen Feature - Scheduler & Cron
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register cron schedules
 */
function almaseo_eg_cron_schedules($schedules) {
    if (!isset($schedules['weekly'])) {
        $schedules['weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display' => __('Once Weekly', 'almaseo')
        );
    }
    
    return $schedules;
}

/**
 * Schedule the weekly cron event
 */
function almaseo_eg_schedule_cron() {
    if (!wp_next_scheduled(ALMASEO_EG_CRON_EVENT)) {
        wp_schedule_event(time(), 'weekly', ALMASEO_EG_CRON_EVENT);
    }
}

/**
 * Unschedule the cron event
 */
function almaseo_eg_unschedule_cron() {
    $timestamp = wp_next_scheduled(ALMASEO_EG_CRON_EVENT);
    if ($timestamp) {
        wp_unschedule_event($timestamp, ALMASEO_EG_CRON_EVENT);
    }
}

/**
 * Process posts in batches
 * 
 * @param int $batch_size Number of posts to process
 * @param int $page Page number for pagination
 * @return array ['processed' => int, 'total' => int, 'has_more' => bool]
 */
function almaseo_eg_process_posts_batch($batch_size = 50, $page = 1) {
    // Include required files
    require_once dirname(__FILE__) . '/constants.php';
    require_once dirname(__FILE__) . '/meta.php';
    require_once dirname(__FILE__) . '/scoring.php';
    
    // Query for UNANALYZED posts specifically
    $args = array(
        'post_type' => array('post', 'page'),
        'post_status' => 'publish',
        'posts_per_page' => $batch_size,
        'paged' => $page,
        'orderby' => 'modified',
        'order' => 'ASC', // Process oldest modified first
        'fields' => 'ids',
        'meta_query' => array(
            array(
                'key' => ALMASEO_EG_META_STATUS,
                'compare' => 'NOT EXISTS'
            )
        )
    );
    
    $query = new WP_Query($args);
    $total = $query->found_posts;
    $processed = 0;
    $errors = array();
    
    if ($query->have_posts()) {
        foreach ($query->posts as $post_id) {
            try {
                // Score the post
                $result = almaseo_score_evergreen($post_id);
                
                if (!empty($result['status'])) {
                    // Get old status for transition tracking
                    $old_status = almaseo_eg_get_status($post_id);
                    
                    // Update status
                    almaseo_eg_set_status($post_id, $result['status']);
                    almaseo_eg_set_last_checked($post_id);
                    
                    // Update notes if seasonal detected
                    if (!empty($result['metrics']['seasonal'])) {
                        $notes = almaseo_eg_get_notes($post_id);
                        $notes['seasonal'] = true;
                        almaseo_eg_set_notes($post_id, $notes);
                    }
                    
                    // Track transition
                    if ($old_status !== $result['status'] && !empty($old_status)) {
                        do_action('almaseo_eg_status_transition', $post_id, $old_status, $result['status'], $result['metrics']);
                    }
                    
                    $processed++;
                } else {
                    $errors[] = "Post $post_id: No status returned from scoring";
                }
            } catch (Exception $e) {
                $errors[] = "Post $post_id: " . $e->getMessage();
            }
        }
    }
    
    wp_reset_postdata();
    
    return array(
        'processed' => $processed,
        'total' => $total,
        'has_more' => ($page * $batch_size) < $total,
        'errors' => $errors
    );
}

/**
 * Weekly cron job callback
 */
function almaseo_eg_weekly_cron() {
    // Process posts in batches
    $page = 1;
    $max_pages = 10; // Limit to 500 posts per cron run
    $total_processed = 0;
    
    do {
        $result = almaseo_eg_process_posts_batch(50, $page);
        $total_processed += $result['processed'];
        $page++;
    } while ($result['has_more'] && $page <= $max_pages);
    
    // Log the processing
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf('[AlmaSEO Evergreen] Weekly cron processed %d posts', $total_processed));
    }
    
    // Generate digest (stub for now)
    almaseo_eg_generate_digest();
}

/**
 * Manual recalculation for all posts
 * 
 * @param int $limit Maximum posts to process
 * @return array ['processed' => int, 'total' => int]
 */
function almaseo_eg_recalc_all($limit = 200) {
    $processed = 0;
    $page = 1;
    $max_pages = ceil($limit / 50);
    $all_errors = array();
    $last_result = null;
    
    do {
        $result = almaseo_eg_process_posts_batch(50, $page);
        $processed += $result['processed'];
        if (!empty($result['errors'])) {
            $all_errors = array_merge($all_errors, $result['errors']);
        }
        $last_result = $result;
        $page++;
    } while ($result['has_more'] && $page <= $max_pages && $processed < $limit);
    
    return array(
        'processed' => $processed,
        'total' => isset($last_result['total']) ? $last_result['total'] : 0,
        'errors' => $all_errors
    );
}

/**
 * Generate weekly digest (stub)
 */
function almaseo_eg_generate_digest() {
    // Get stats
    $stats = almaseo_eg_get_stats();
    
    // Get stale posts
    $stale_posts = get_posts(array(
        'post_type' => array('post', 'page'),
        'post_status' => 'publish',
        'posts_per_page' => 10,
        'meta_key' => ALMASEO_EG_META_STATUS,
        'meta_value' => ALMASEO_EG_STATUS_STALE,
        'orderby' => 'modified',
        'order' => 'ASC'
    ));
    
    // Build HTML digest
    $html = '<div style="font-family: sans-serif; max-width: 600px; margin: 0 auto;">';
    $html .= '<h2>' . __('AlmaSEO Evergreen Weekly Digest', 'almaseo') . '</h2>';
    $html .= '<p>' . sprintf(__('Generated: %s', 'almaseo'), current_time('F j, Y g:i a')) . '</p>';
    
    // Stats section
    $html .= '<h3>' . __('Content Health Overview', 'almaseo') . '</h3>';
    $html .= '<ul>';
    $html .= '<li>' . sprintf(__('🟢 Evergreen: %d posts', 'almaseo'), $stats['evergreen']) . '</li>';
    $html .= '<li>' . sprintf(__('🟡 Watch: %d posts', 'almaseo'), $stats['watch']) . '</li>';
    $html .= '<li>' . sprintf(__('🔴 Stale: %d posts', 'almaseo'), $stats['stale']) . '</li>';
    $html .= '</ul>';
    
    // Stale posts section
    if (!empty($stale_posts)) {
        $html .= '<h3>' . __('Posts Needing Attention', 'almaseo') . '</h3>';
        $html .= '<ol>';
        foreach ($stale_posts as $post) {
            $ages = almaseo_get_post_ages($post);
            $html .= '<li>';
            $html .= '<strong>' . esc_html($post->post_title) . '</strong><br>';
            $html .= sprintf(__('Last updated: %d days ago', 'almaseo'), $ages['updated_days']);
            $html .= ' | <a href="' . get_edit_post_link($post->ID) . '">' . __('Edit', 'almaseo') . '</a>';
            $html .= '</li>';
        }
        $html .= '</ol>';
    }
    
    $html .= '</div>';
    
    // Store digest
    update_option(ALMASEO_EG_DIGEST_OPTION, $html);
    
    // Trigger action for future email sending
    do_action('almaseo_eg_digest_generated', $html, $stats);
    
    return $html;
}

/**
 * Get Evergreen statistics
 * 
 * @return array ['total' => int, 'evergreen' => int, 'watch' => int, 'stale' => int]
 */
function almaseo_eg_get_stats() {
    global $wpdb;
    
    $counts = $wpdb->get_results($wpdb->prepare("
        SELECT meta_value as status, COUNT(*) as count
        FROM {$wpdb->postmeta} pm
        INNER JOIN {$wpdb->posts} p ON pm.post_id = p.ID
        WHERE pm.meta_key = %s
        AND p.post_status = 'publish'
        AND p.post_type IN ('post', 'page')
        GROUP BY meta_value
    ", ALMASEO_EG_META_STATUS));
    
    $stats = array(
        'total' => 0,
        'evergreen' => 0,
        'watch' => 0,
        'stale' => 0
    );
    
    foreach ($counts as $row) {
        $count = (int) $row->count;
        $stats['total'] += $count;
        
        switch ($row->status) {
            case ALMASEO_EG_STATUS_EVERGREEN:
                $stats['evergreen'] = $count;
                break;
            case ALMASEO_EG_STATUS_WATCH:
                $stats['watch'] = $count;
                break;
            case ALMASEO_EG_STATUS_STALE:
                $stats['stale'] = $count;
                break;
        }
    }
    
    return $stats;
}

/**
 * Hook for status transitions (stub)
 * 
 * @param int $post_id Post ID
 * @param string $from Old status
 * @param string $to New status
 * @param array $metrics Post metrics
 */
function almaseo_eg_notify_transition($post_id, $from, $to, $metrics) {
    // Stub for future notification system
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log(sprintf(
            '[AlmaSEO Evergreen] Post %d transitioned from %s to %s',
            $post_id,
            $from,
            $to
        ));
    }
}

/**
 * Initialize scheduler hooks
 */
function almaseo_eg_init_scheduler() {
    add_filter('cron_schedules', 'almaseo_eg_cron_schedules');
    add_action(ALMASEO_EG_CRON_EVENT, 'almaseo_eg_weekly_cron');
    add_action('almaseo_eg_status_transition', 'almaseo_eg_notify_transition', 10, 4);
}
add_action('init', 'almaseo_eg_init_scheduler');