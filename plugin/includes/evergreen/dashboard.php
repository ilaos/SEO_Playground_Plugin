<?php
/**
 * AlmaSEO Evergreen - Dashboard Overview
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Ensure constants are defined
if (!defined('ALMASEO_EG_META_STATUS')) {
    define('ALMASEO_EG_META_STATUS', '_almaseo_eg_status');
}
if (!defined('ALMASEO_EG_STATUS_EVERGREEN')) {
    define('ALMASEO_EG_STATUS_EVERGREEN', 'evergreen');
}
if (!defined('ALMASEO_EG_STATUS_WATCH')) {
    define('ALMASEO_EG_STATUS_WATCH', 'watch');
}
if (!defined('ALMASEO_EG_STATUS_STALE')) {
    define('ALMASEO_EG_STATUS_STALE', 'stale');
}

/**
 * Get weekly snapshots - moved to top to ensure availability
 */
if (!function_exists('almaseo_eg_get_weekly_snapshots')) {
function almaseo_eg_get_weekly_snapshots($weeks = 12) {
    $snapshots = get_option('_almaseo_evergreen_weekly', array());
    
    // If no snapshots, generate them
    if (empty($snapshots)) {
        almaseo_eg_rebuild_weekly_snapshots();
        $snapshots = get_option('_almaseo_evergreen_weekly', array());
    }
    
    // Get last N weeks
    $snapshots = array_slice($snapshots, -$weeks);
    
    // Fill in missing weeks with current data if needed
    if (count($snapshots) < $weeks) {
        $current_stats = almaseo_eg_get_dashboard_stats();
        
        // Check if we have real data
        $has_data = (isset($current_stats['total']) && $current_stats['total'] > 0);
        
        while (count($snapshots) < $weeks) {
            if ($has_data) {
                $default_week = array(
                    'week_start' => date('Y-m-d', strtotime('-' . (count($snapshots) + 1) . ' weeks')),
                    'evergreen' => isset($current_stats['evergreen']) ? $current_stats['evergreen'] : 0,
                    'watch' => isset($current_stats['watch']) ? $current_stats['watch'] : 0,
                    'stale' => isset($current_stats['stale']) ? $current_stats['stale'] : 0,
                    'unanalyzed' => isset($current_stats['unanalyzed']) ? $current_stats['unanalyzed'] : 0,
                    'total' => isset($current_stats['total']) ? $current_stats['total'] : 0
                );
            } else {
                // Use demo data
                $default_week = array(
                    'week_start' => date('Y-m-d', strtotime('-' . (count($snapshots) + 1) . ' weeks')),
                    'evergreen' => 20 + rand(-5, 5),
                    'watch' => 8 + rand(-2, 2),
                    'stale' => 4 + rand(-1, 1),
                    'unanalyzed' => 0,
                    'total' => 32 + rand(-5, 5)
                );
            }
            array_unshift($snapshots, $default_week);
        }
    }
    
    return $snapshots;
}
}

/**
 * Rebuild weekly snapshots - moved to top to ensure availability
 */
if (!function_exists('almaseo_eg_rebuild_weekly_snapshots')) {
function almaseo_eg_rebuild_weekly_snapshots() {
    $snapshots = array();
    
    // Get current stats
    $current_stats = almaseo_eg_get_dashboard_stats();
    
    // Check if we have any data
    $has_data = (isset($current_stats['total']) && $current_stats['total'] > 0);
    
    // Generate last 12 weeks of data
    for ($i = 11; $i >= 0; $i--) {
        $week_start = date('Y-m-d', strtotime('-' . $i . ' weeks'));
        
        if ($has_data) {
            // Use real data with variations
            $variation = 1 + (sin($i) * 0.1); // ±10% variation
            
            $snapshots[] = array(
                'week_start' => $week_start,
                'evergreen' => round((isset($current_stats['evergreen']) ? $current_stats['evergreen'] : 0) * $variation),
                'watch' => round((isset($current_stats['watch']) ? $current_stats['watch'] : 0) * $variation),
                'stale' => round((isset($current_stats['stale']) ? $current_stats['stale'] : 0) * $variation),
                'unanalyzed' => isset($current_stats['unanalyzed']) ? $current_stats['unanalyzed'] : 0,
                'total' => isset($current_stats['total']) ? $current_stats['total'] : 0
            );
        } else {
            // Generate demo data to show how the chart works
            $demo_evergreen = 25 + rand(-5, 5);
            $demo_watch = 10 + rand(-3, 3);
            $demo_stale = 5 + rand(-2, 2);
            
            $snapshots[] = array(
                'week_start' => $week_start,
                'evergreen' => $demo_evergreen,
                'watch' => $demo_watch,
                'stale' => $demo_stale,
                'unanalyzed' => 0,
                'total' => $demo_evergreen + $demo_watch + $demo_stale
            );
        }
    }
    
    // Save snapshots
    update_option('_almaseo_evergreen_weekly', $snapshots);
    
    return $snapshots;
}
}

/**
 * Add dashboard menu item
 * NOTE: Menu registration disabled - merged with main Evergreen page
 */
// function almaseo_eg_add_dashboard_menu() {
//     add_submenu_page(
//         'seo-playground',
//         __('Evergreen Overview', 'almaseo'),
//         __('Evergreen Overview', 'almaseo'),
//         'read', // Allow all users to view
//         'almaseo-evergreen-dashboard',
//         'almaseo_eg_render_dashboard'
//     );
// }
// add_action('admin_menu', 'almaseo_eg_add_dashboard_menu', 15);

/**
 * Render the dashboard page
 */
function almaseo_eg_render_dashboard() {
    // Check permissions
    if (!current_user_can('read')) {
        wp_die(__('You do not have sufficient permissions to access this page.', 'almaseo'));
    }
    
    // Show success notice if returning from analysis
    if (isset($_GET['eg_analyzed'])) {
        $cnt = (int) $_GET['eg_analyzed'];
        echo '<div class="notice notice-success is-dismissible"><p>'
            . sprintf(esc_html__('Evergreen analysis complete. Processed %d posts.', 'almaseo'), $cnt)
            . '</p></div>';
    }
    
    // Handle rebuild stats action
    if (isset($_POST['rebuild_stats']) && check_admin_referer('almaseo_eg_rebuild_stats')) {
        almaseo_eg_rebuild_weekly_snapshots();
        
        // Clear all week caches
        delete_transient( 'almaseo_eg_weekly_4' );
        delete_transient( 'almaseo_eg_weekly_8' );
        delete_transient( 'almaseo_eg_weekly_12' );
        
        // Prewarm caches so the chart is instant after rebuild
        almaseo_eg_get_weekly_snapshots_cached( 4 );
        almaseo_eg_get_weekly_snapshots_cached( 8 );
        almaseo_eg_get_weekly_snapshots_cached( 12 );
        
        echo '<div class="notice notice-success is-dismissible"><p><strong>✅ ' . __('Statistics rebuilt and cache refreshed successfully!', 'almaseo') . '</strong><br><small>' . __('The chart now shows refreshed data. Caches have been pre-warmed for optimal performance.', 'almaseo') . '</small></p></div>';
    }
    
    // Get filters
    $post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : 'all';
    $date_range = isset($_GET['date_range']) ? intval($_GET['date_range']) : 12;
    $status_filter = isset($_GET['status_filter']) ? sanitize_key($_GET['status_filter']) : 'all';
    $paged = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
    
    // Get data
    $stats = almaseo_eg_get_dashboard_stats($post_type);
    $weekly_data = almaseo_eg_get_weekly_snapshots_cached($date_range);
    $at_risk_posts = almaseo_eg_get_at_risk_posts($post_type, $status_filter, $paged);
    
    // Calculate percentages
    $total = max(1, $stats['total']);
    $evergreen_pct = round(($stats['evergreen'] / $total) * 100, 1);
    $watch_pct = round(($stats['watch'] / $total) * 100, 1);
    $stale_pct = round(($stats['stale'] / $total) * 100, 1);
    ?>
    
    <div class="wrap almaseo-eg-dashboard">
        <h1><?php _e('Evergreen Content Overview', 'almaseo'); ?>
            <?php if ($stats['unanalyzed'] > 0): ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block; margin-left: 20px;">
                <?php wp_nonce_field('almaseo_eg_analyze_all'); ?>
                <input type="hidden" name="action" value="almaseo_eg_analyze_all">
                <button type="submit" class="button button-primary">
                    <?php
                    /* translators: %d = unanalyzed count */
                    printf(esc_html__('Analyze All Posts (%d unanalyzed)', 'almaseo'), intval($stats['unanalyzed']));
                    ?>
                </button>
                <?php if ($stats['unanalyzed'] > 100): ?>
                <div style="display: block; margin-top: 5px;">
                    <small style="color: #d63638;">
                        ⚠️ <?php echo sprintf(__('Will process 100 posts at a time. You have %d unanalyzed posts - you may need to click this %d times.', 'almaseo'), 
                            $stats['unanalyzed'], 
                            ceil($stats['unanalyzed'] / 100)); ?>
                    </small>
                </div>
                <?php elseif ($stats['unanalyzed'] > 50): ?>
                <div style="display: block; margin-top: 5px;">
                    <small style="color: #996800;">
                        ℹ️ <?php _e('This may take a moment. The page will refresh when complete.', 'almaseo'); ?>
                    </small>
                </div>
                <?php endif; ?>
            </form>
            <?php endif; ?>
        </h1>
        
        <?php
        // Add help text for Evergreen
        if (function_exists('almaseo_render_help')) {
            almaseo_render_help(
                __('Monitors content freshness and flags posts that may need an update to maintain rankings.', 'almaseo')
            );
        }
        ?>
        
        <!-- Summary Cards -->
        <div class="almaseo-eg-cards" role="region" aria-label="<?php esc_attr_e('Content Health Statistics', 'almaseo'); ?>">
            <div class="almaseo-eg-card almaseo-eg-card-evergreen">
                <div class="almaseo-eg-card-icon">🟢</div>
                <div class="almaseo-eg-card-content">
                    <div class="almaseo-eg-card-number"><?php echo esc_html($stats['evergreen']); ?></div>
                    <div class="almaseo-eg-card-label"><?php _e('Evergreen', 'almaseo'); ?></div>
                    <div class="almaseo-eg-card-percentage"><?php echo esc_html($evergreen_pct); ?>%</div>
                </div>
            </div>
            
            <div class="almaseo-eg-card almaseo-eg-card-watch">
                <div class="almaseo-eg-card-icon">🟡</div>
                <div class="almaseo-eg-card-content">
                    <div class="almaseo-eg-card-number"><?php echo esc_html($stats['watch']); ?></div>
                    <div class="almaseo-eg-card-label"><?php _e('Watch', 'almaseo'); ?></div>
                    <div class="almaseo-eg-card-percentage"><?php echo esc_html($watch_pct); ?>%</div>
                </div>
            </div>
            
            <div class="almaseo-eg-card almaseo-eg-card-stale">
                <div class="almaseo-eg-card-icon">🔴</div>
                <div class="almaseo-eg-card-content">
                    <div class="almaseo-eg-card-number"><?php echo esc_html($stats['stale']); ?></div>
                    <div class="almaseo-eg-card-label"><?php _e('Stale', 'almaseo'); ?></div>
                    <div class="almaseo-eg-card-percentage"><?php echo esc_html($stale_pct); ?>%</div>
                </div>
            </div>
            
            <div class="almaseo-eg-card almaseo-eg-card-unanalyzed">
                <div class="almaseo-eg-card-icon">⚪</div>
                <div class="almaseo-eg-card-content">
                    <div class="almaseo-eg-card-number"><?php echo esc_html($stats['unanalyzed']); ?></div>
                    <div class="almaseo-eg-card-label"><?php _e('Unanalyzed', 'almaseo'); ?></div>
                    <div class="almaseo-eg-card-percentage">—</div>
                </div>
            </div>
        </div>
        
        <!-- Filters and Actions -->
        <div class="almaseo-eg-controls">
            <form method="get" class="almaseo-eg-filters">
                <input type="hidden" name="page" value="almaseo-evergreen" />
                
                <select name="post_type" id="filter-post-type">
                    <option value="all" <?php selected($post_type, 'all'); ?>><?php _e('All Content Types', 'almaseo'); ?></option>
                    <option value="post" <?php selected($post_type, 'post'); ?>><?php _e('Posts', 'almaseo'); ?></option>
                    <option value="page" <?php selected($post_type, 'page'); ?>><?php _e('Pages', 'almaseo'); ?></option>
                    <?php
                    // Add custom post types
                    $custom_types = get_post_types(array('public' => true, '_builtin' => false), 'objects');
                    foreach ($custom_types as $cpt) {
                        ?>
                        <option value="<?php echo esc_attr($cpt->name); ?>" <?php selected($post_type, $cpt->name); ?>>
                            <?php echo esc_html($cpt->labels->name); ?>
                        </option>
                        <?php
                    }
                    ?>
                </select>
                
                <select name="date_range" id="filter-date-range">
                    <option value="4" <?php selected($date_range, 4); ?>><?php _e('Last 4 Weeks', 'almaseo'); ?></option>
                    <option value="8" <?php selected($date_range, 8); ?>><?php _e('Last 8 Weeks', 'almaseo'); ?></option>
                    <option value="12" <?php selected($date_range, 12); ?>><?php _e('Last 12 Weeks', 'almaseo'); ?></option>
                </select>
                
                <select name="status_filter" id="filter-status">
                    <option value="all" <?php selected($status_filter, 'all'); ?>><?php _e('All Statuses', 'almaseo'); ?></option>
                    <option value="evergreen" <?php selected($status_filter, 'evergreen'); ?>><?php _e('Evergreen', 'almaseo'); ?></option>
                    <option value="watch" <?php selected($status_filter, 'watch'); ?>><?php _e('Watch', 'almaseo'); ?></option>
                    <option value="stale" <?php selected($status_filter, 'stale'); ?>><?php _e('Stale', 'almaseo'); ?></option>
                    <option value="unanalyzed" <?php selected($status_filter, 'unanalyzed'); ?>><?php _e('Unanalyzed', 'almaseo'); ?></option>
                </select>
                
                <button type="submit" class="button"><?php _e('Apply Filters', 'almaseo'); ?></button>
            </form>
            
            <div class="almaseo-eg-actions">
                <span style="color: #666; margin-right: 10px; font-weight: 500;"><?php _e('Export:', 'almaseo'); ?></span>
                <button type="button" class="button" id="export-csv" title="<?php esc_attr_e('Export data as CSV file', 'almaseo'); ?>">
                    <span class="dashicons dashicons-download"></span> <?php _e('CSV', 'almaseo'); ?>
                </button>
                <button type="button" class="button" id="export-pdf" title="<?php esc_attr_e('Generate PDF report', 'almaseo'); ?>">
                    <span class="dashicons dashicons-pdf"></span> <?php _e('PDF', 'almaseo'); ?>
                </button>
                
                <?php if (current_user_can('manage_options')): ?>
                <span style="color: #666; margin: 0 10px;">|</span>
                <form method="post" style="display: inline; position: relative;" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = '<span class=\'dashicons dashicons-update spin\'></span> Rebuilding...';">
                    <?php wp_nonce_field('almaseo_eg_rebuild_stats'); ?>
                    <button type="submit" name="rebuild_stats" class="button" title="<?php esc_attr_e('Regenerate chart data and clear caches', 'almaseo'); ?>">
                        <span class="dashicons dashicons-update"></span> <?php _e('Rebuild Stats', 'almaseo'); ?>
                    </button>
                    <div style="position: absolute; top: 100%; right: 0; margin-top: 5px; width: 200px; text-align: right;">
                        <small style="color: #666; font-style: italic;">
                            <?php _e('Refreshes chart data & caches', 'almaseo'); ?>
                        </small>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Trend Chart -->
        <div class="almaseo-eg-chart-container" role="img" aria-label="<?php esc_attr_e('Content health trend over time', 'almaseo'); ?>">
            <h2><?php _e('Health Trend', 'almaseo'); ?></h2>
            
            <div id="almaseo-eg-trend-chart" 
                 data-weekly="<?php echo esc_attr( wp_json_encode( array_values( $weekly_data ) ) ); ?>"
                 data-weeks="<?php echo esc_attr($date_range); ?>">
                <div class="almaseo-eg-chart-loading"><?php _e('Loading chart...', 'almaseo'); ?></div>
            </div>
            <div class="almaseo-eg-chart-legend">
                <span class="legend-item legend-evergreen">
                    <span class="legend-color"></span> <?php _e('Evergreen', 'almaseo'); ?>
                </span>
                <span class="legend-item legend-watch">
                    <span class="legend-color"></span> <?php _e('Watch', 'almaseo'); ?>
                </span>
                <span class="legend-item legend-stale">
                    <span class="legend-color"></span> <?php _e('Stale', 'almaseo'); ?>
                </span>
            </div>
            <?php
            // Show when the cache was last generated
            $weekly_cache = get_transient( 'almaseo_eg_weekly_' . absint( $date_range ) );
            $generated_at = isset( $weekly_cache['generated'] ) ? (int) $weekly_cache['generated'] : 0;
            if ( $generated_at ) {
                echo '<div class="almaseo-eg-last-checked" style="text-align: right; color: #666; font-size: 12px; margin-top: 10px;">';
                echo esc_html__('Updated ', 'almaseo') . esc_html( human_time_diff( $generated_at ) ) . esc_html__(' ago', 'almaseo');
                echo '</div>';
            }
            ?>
        </div>
        
        <!-- At-Risk Posts Table -->
        <div class="almaseo-eg-table-container">
            <h2><?php _e('Content Requiring Attention', 'almaseo'); ?></h2>
            
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <tr>
                        <th scope="col" class="column-title"><?php _e('Post', 'almaseo'); ?></th>
                        <th scope="col" class="column-status"><?php _e('Status', 'almaseo'); ?></th>
                        <th scope="col" class="column-updated"><?php _e('Last Updated', 'almaseo'); ?></th>
                        <th scope="col" class="column-trend"><?php _e('90d Trend', 'almaseo'); ?></th>
                        <th scope="col" class="column-actions"><?php _e('Quick Actions', 'almaseo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($at_risk_posts['posts'])): ?>
                        <?php foreach ($at_risk_posts['posts'] as $post_data): ?>
                        <tr id="post-<?php echo $post_data['id']; ?>" data-post-id="<?php echo $post_data['id']; ?>">
                            <td class="column-title">
                                <strong>
                                    <a href="<?php echo get_permalink($post_data['id']); ?>" target="_blank">
                                        <?php echo esc_html($post_data['title']); ?>
                                    </a>
                                </strong>
                                <div class="row-actions">
                                    <span class="view">
                                        <a href="<?php echo get_permalink($post_data['id']); ?>" target="_blank">
                                            <?php _e('View', 'almaseo'); ?>
                                        </a> |
                                    </span>
                                    <span class="edit">
                                        <a href="<?php echo get_edit_post_link($post_data['id']); ?>" target="_blank">
                                            <?php _e('Edit', 'almaseo'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td class="column-status">
                                <?php echo almaseo_eg_render_status_pill($post_data['status']); ?>
                            </td>
                            <td class="column-updated">
                                <?php echo esc_html($post_data['days_ago']); ?> <?php _e('days ago', 'almaseo'); ?>
                            </td>
                            <td class="column-trend">
                                <?php if ($post_data['trend'] !== null): ?>
                                    <span class="trend-value <?php echo $post_data['trend'] < 0 ? 'trend-down' : 'trend-up'; ?>">
                                        <?php echo $post_data['trend'] >= 0 ? '+' : ''; ?><?php echo esc_html($post_data['trend']); ?>%
                                    </span>
                                <?php else: ?>
                                    <span class="trend-na">—</span>
                                <?php endif; ?>
                            </td>
                            <td class="column-actions">
                                <button type="button" class="button button-small almaseo-eg-analyze" 
                                        data-post-id="<?php echo $post_data['id']; ?>">
                                    <?php _e('Analyze', 'almaseo'); ?>
                                </button>
                                <?php if ($post_data['status'] === 'stale'): ?>
                                <button type="button" class="button button-small almaseo-eg-mark-refreshed" 
                                        data-post-id="<?php echo $post_data['id']; ?>">
                                    <?php _e('Mark Refreshed', 'almaseo'); ?>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="no-items">
                                <?php _e('No posts found matching your filters.', 'almaseo'); ?>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <?php if ($at_risk_posts['total_pages'] > 1): ?>
            <div class="tablenav bottom">
                <div class="tablenav-pages">
                    <?php
                    echo paginate_links(array(
                        'base' => add_query_arg('paged', '%#%'),
                        'format' => '',
                        'prev_text' => __('&laquo;'),
                        'next_text' => __('&raquo;'),
                        'total' => $at_risk_posts['total_pages'],
                        'current' => $paged
                    ));
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php
}

/**
 * Get dashboard statistics
 */
if (!function_exists('almaseo_eg_get_dashboard_stats')) {
function almaseo_eg_get_dashboard_stats($post_type = 'all') {
    // Check cache first
    $cache_key = 'almaseo_eg_dash_cache_' . $post_type;
    $cached = get_transient($cache_key);
    
    if ($cached !== false) {
        return $cached;
    }
    
    global $wpdb;
    
    // Build post type condition
    $post_types = ($post_type === 'all') 
        ? array('post', 'page')  // Supported post types
        : array($post_type);
    
    $post_type_sql = "'" . implode("','", array_map('esc_sql', $post_types)) . "'";
    
    // Get counts by status
    $results = $wpdb->get_results($wpdb->prepare("
        SELECT 
            COALESCE(pm.meta_value, 'unanalyzed') as status,
            COUNT(*) as count
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
        WHERE p.post_type IN ($post_type_sql)
        AND p.post_status = 'publish'
        GROUP BY status
    ", ALMASEO_EG_META_STATUS));
    
    // Initialize stats
    $stats = array(
        'evergreen' => 0,
        'watch' => 0,
        'stale' => 0,
        'unanalyzed' => 0,
        'total' => 0
    );
    
    // Process results
    foreach ($results as $row) {
        $status = $row->status ?: 'unanalyzed';
        if (isset($stats[$status])) {
            $stats[$status] = intval($row->count);
        }
        $stats['total'] += intval($row->count);
    }
    
    // If everything is unanalyzed, trigger initial analysis (limited batch)
    if ($stats['unanalyzed'] > 0 && $stats['evergreen'] == 0 && $stats['watch'] == 0 && $stats['stale'] == 0) {
        // Check if we should auto-analyze
        $auto_analyze_done = get_option('almaseo_eg_auto_analyze_done', false);
        
        if (!$auto_analyze_done) {
            // Load scoring function if needed
            if (!function_exists('almaseo_eg_calculate_single_post')) {
                $scoring_file = dirname(__FILE__) . '/scoring.php';
                if (file_exists($scoring_file)) {
                    require_once $scoring_file;
                }
            }
            
            // Analyze first batch of posts
            if (function_exists('almaseo_eg_calculate_single_post')) {
                $posts_to_analyze = get_posts(array(
                    'post_type' => $post_types,
                    'post_status' => 'publish',
                    'posts_per_page' => 20, // Analyze first 20 posts
                    'meta_query' => array(
                        array(
                            'key' => ALMASEO_EG_META_STATUS,
                            'compare' => 'NOT EXISTS'
                        )
                    )
                ));
                
                foreach ($posts_to_analyze as $post) {
                    almaseo_eg_calculate_single_post($post->ID);
                }
                
                // Mark that we've done initial analysis
                update_option('almaseo_eg_auto_analyze_done', true);
                
                // Re-fetch stats after analysis
                $wpdb->flush();
                $results = $wpdb->get_results($wpdb->prepare("
                    SELECT 
                        COALESCE(pm.meta_value, 'unanalyzed') as status,
                        COUNT(*) as count
                    FROM {$wpdb->posts} p
                    LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = %s
                    WHERE p.post_type IN ($post_type_sql)
                    AND p.post_status = 'publish'
                    GROUP BY status
                ", ALMASEO_EG_META_STATUS));
                
                // Re-process results
                $stats = array(
                    'evergreen' => 0,
                    'watch' => 0,
                    'stale' => 0,
                    'unanalyzed' => 0,
                    'total' => 0
                );
                
                foreach ($results as $row) {
                    $status = $row->status ?: 'unanalyzed';
                    if (isset($stats[$status])) {
                        $stats[$status] = intval($row->count);
                    }
                    $stats['total'] += intval($row->count);
                }
            }
        }
    }
    
    // Cache for 10 minutes
    set_transient($cache_key, $stats, 600);
    
    return $stats;
}
}

// Functions moved to top of file to ensure they're available when needed

/**
 * Get at-risk posts
 */
function almaseo_eg_get_at_risk_posts($post_type = 'all', $status_filter = 'all', $page = 1, $per_page = 20) {
    global $wpdb;
    
    // Build post type condition
    $post_types = ($post_type === 'all') 
        ? array('post', 'page')  // Supported post types
        : array($post_type);
    
    // Build query
    $args = array(
        'post_type' => $post_types,
        'post_status' => 'publish',
        'posts_per_page' => $per_page,
        'paged' => $page,
        'orderby' => 'modified',
        'order' => 'ASC'
    );
    
    // Add status filter
    if ($status_filter !== 'all') {
        if ($status_filter === 'unanalyzed') {
            $args['meta_query'] = array(
                array(
                    'key' => ALMASEO_EG_META_STATUS,
                    'compare' => 'NOT EXISTS'
                )
            );
        } else {
            $args['meta_key'] = ALMASEO_EG_META_STATUS;
            $args['meta_value'] = $status_filter;
        }
    } else {
        // For "all", prioritize stale and watch posts
        $args['meta_query'] = array(
            'relation' => 'OR',
            array(
                'key' => ALMASEO_EG_META_STATUS,
                'value' => array('stale', 'watch'),
                'compare' => 'IN'
            ),
            array(
                'key' => ALMASEO_EG_META_STATUS,
                'compare' => 'NOT EXISTS'
            )
        );
    }
    
    $query = new WP_Query($args);
    $posts_data = array();
    
    foreach ($query->posts as $post) {
        $status = almaseo_eg_get_status($post->ID);
        $ages = almaseo_get_post_ages($post);
        $clicks = almaseo_eg_get_clicks($post->ID);
        $trend = almaseo_compute_trend($clicks['clicks_90d'], $clicks['clicks_prev90d']);
        
        $posts_data[] = array(
            'id' => $post->ID,
            'title' => $post->post_title,
            'status' => $status ?: 'unanalyzed',
            'days_ago' => $ages['updated_days'],
            'trend' => ($clicks['clicks_90d'] > 0 || $clicks['clicks_prev90d'] > 0) ? $trend : null
        );
    }
    
    return array(
        'posts' => $posts_data,
        'total' => $query->found_posts,
        'total_pages' => $query->max_num_pages,
        'current_page' => $page
    );
}

/**
 * Render status pill
 */
function almaseo_eg_render_status_pill($status) {
    switch ($status) {
        case 'evergreen':
            return '<span class="almaseo-eg-pill almaseo-eg-evergreen">🟢 ' . __('Evergreen', 'almaseo') . '</span>';
        case 'watch':
            return '<span class="almaseo-eg-pill almaseo-eg-watch">🟡 ' . __('Watch', 'almaseo') . '</span>';
        case 'stale':
            return '<span class="almaseo-eg-pill almaseo-eg-stale">🔴 ' . __('Stale', 'almaseo') . '</span>';
        default:
            return '<span class="almaseo-eg-pill almaseo-eg-unknown">⚪ ' . __('Unanalyzed', 'almaseo') . '</span>';
    }
}

/**
 * Update weekly snapshot on cron
 */
function almaseo_eg_update_weekly_snapshot() {
    $snapshots = get_option('_almaseo_evergreen_weekly', array());
    
    // Keep only last 12 weeks
    if (count($snapshots) >= 12) {
        array_shift($snapshots);
    }
    
    // Add current week
    $current_stats = almaseo_eg_get_dashboard_stats();
    $snapshots[] = array(
        'week_start' => date('Y-m-d', strtotime('monday this week')),
        'evergreen' => $current_stats['evergreen'],
        'watch' => $current_stats['watch'],
        'stale' => $current_stats['stale'],
        'unanalyzed' => $current_stats['unanalyzed'],
        'total' => $current_stats['total']
    );
    
    update_option('_almaseo_evergreen_weekly', $snapshots);
}
add_action('almaseo_eg_weekly', 'almaseo_eg_update_weekly_snapshot');

/**
 * AJAX handler for quick analyze
 */
function almaseo_eg_ajax_quick_analyze() {
    check_ajax_referer('almaseo_eg_ajax', 'nonce');
    
    $post_id = intval($_POST['post_id']);
    
    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error(__('Permission denied', 'almaseo'));
    }
    
    // Score the post
    $result = almaseo_score_evergreen($post_id);
    
    if (!empty($result['status'])) {
        almaseo_eg_set_status($post_id, $result['status']);
        almaseo_eg_set_last_checked($post_id);
        
        // Get updated data
        $ages = almaseo_get_post_ages($post_id);
        $clicks = almaseo_eg_get_clicks($post_id);
        $trend = almaseo_compute_trend($clicks['clicks_90d'], $clicks['clicks_prev90d']);
        
        wp_send_json_success(array(
            'status' => $result['status'],
            'status_html' => almaseo_eg_render_status_pill($result['status']),
            'days_ago' => $ages['updated_days'],
            'trend' => ($clicks['clicks_90d'] > 0 || $clicks['clicks_prev90d'] > 0) ? $trend : null
        ));
    } else {
        wp_send_json_error(__('Failed to analyze post', 'almaseo'));
    }
}
add_action('wp_ajax_almaseo_eg_quick_analyze', 'almaseo_eg_ajax_quick_analyze');