<?php
/**
 * AlmaSEO Evergreen - Admin Post Actions
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 5.9.1
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Handle the Analyze All Posts action via admin-post.php
 */
add_action('admin_post_almaseo_eg_analyze_all', function () {
    // Verify nonce
    check_admin_referer('almaseo_eg_analyze_all');
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Forbidden', 'almaseo'), 403);
    }
    
    // Ensure required files are loaded
    $evergreen_dir = dirname(__FILE__);
    
    // Load constants first
    if (file_exists($evergreen_dir . '/constants.php')) {
        require_once $evergreen_dir . '/constants.php';
    }
    
    // Load meta functions
    if (file_exists($evergreen_dir . '/meta.php')) {
        require_once $evergreen_dir . '/meta.php';
    }
    
    // Load scoring functions
    if (file_exists($evergreen_dir . '/scoring.php')) {
        require_once $evergreen_dir . '/scoring.php';
    }
    
    // Load scheduler/batch processing
    if (file_exists($evergreen_dir . '/scheduler.php')) {
        require_once $evergreen_dir . '/scheduler.php';
    }
    
    // Run the analysis
    $processed = 0;
    
    if (function_exists('almaseo_eg_recalc_all')) {
        // Use the existing batch processing function
        $result = almaseo_eg_recalc_all(100); // Process up to 100 posts
        $processed = isset($result['processed']) ? (int) $result['processed'] : 0;
    } elseif (function_exists('almaseo_eg_process_posts_batch')) {
        // Fallback to direct batch processing
        $result = almaseo_eg_process_posts_batch(100, 1);
        $processed = isset($result['processed']) ? (int) $result['processed'] : 0;
    }
    
    // Clear all dashboard caches
    delete_transient('almaseo_eg_dash_cache_all');
    delete_transient('almaseo_eg_dash_cache_post');
    delete_transient('almaseo_eg_dash_cache_page');
    
    // Bust weekly caches so the chart updates on redirect
    delete_transient('almaseo_eg_weekly_4');
    delete_transient('almaseo_eg_weekly_8');
    delete_transient('almaseo_eg_weekly_12');
    
    // Redirect back with a flash param
    $back = menu_page_url('almaseo-evergreen', false);
    if (!$back) {
        $back = admin_url('admin.php?page=almaseo-evergreen');
    }
    
    wp_safe_redirect(add_query_arg(array('eg_analyzed' => $processed), $back));
    exit;
});

/**
 * Handle the Rebuild Stats action via admin-post.php (optional improvement)
 */
add_action('admin_post_almaseo_eg_rebuild_stats', function () {
    // Verify nonce
    check_admin_referer('almaseo_eg_rebuild_stats');
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Forbidden', 'almaseo'), 403);
    }
    
    // Load required functions
    $evergreen_dir = dirname(__FILE__);
    if (file_exists($evergreen_dir . '/dashboard.php')) {
        require_once $evergreen_dir . '/dashboard.php';
    }
    if (file_exists($evergreen_dir . '/functions.php')) {
        require_once $evergreen_dir . '/functions.php';
    }
    
    // Rebuild stats
    if (function_exists('almaseo_eg_rebuild_weekly_snapshots')) {
        almaseo_eg_rebuild_weekly_snapshots();
    }
    
    // Clear all week caches
    delete_transient('almaseo_eg_weekly_4');
    delete_transient('almaseo_eg_weekly_8');
    delete_transient('almaseo_eg_weekly_12');
    
    // Prewarm caches
    if (function_exists('almaseo_eg_get_weekly_snapshots_cached')) {
        almaseo_eg_get_weekly_snapshots_cached(4);
        almaseo_eg_get_weekly_snapshots_cached(8);
        almaseo_eg_get_weekly_snapshots_cached(12);
    }
    
    // Redirect back with success message
    $back = menu_page_url('almaseo-evergreen', false);
    if (!$back) {
        $back = admin_url('admin.php?page=almaseo-evergreen');
    }
    
    wp_safe_redirect(add_query_arg(array('eg_rebuilt' => '1'), $back));
    exit;
});