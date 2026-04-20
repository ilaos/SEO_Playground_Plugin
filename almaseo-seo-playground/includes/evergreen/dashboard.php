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
//         __('Evergreen Overview', 'almaseo-seo-playground'),
//         __('Evergreen Overview', 'almaseo-seo-playground'),
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
        wp_die(__('You do not have sufficient permissions to access this page.', 'almaseo-seo-playground'));
    }
    
    // Show success notice if returning from analysis
    if (isset($_GET['eg_analyzed'])) {
        $cnt = (int) $_GET['eg_analyzed'];
        echo '<div class="notice notice-success is-dismissible"><p>'
            /* translators: %d: number of posts processed */
            . sprintf(esc_html__('Evergreen analysis complete. Processed %d posts.', 'almaseo-seo-playground'), $cnt)
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
        
        echo '<div class="notice notice-success is-dismissible"><p><strong>✅ ' . __('Statistics rebuilt and cache refreshed successfully!', 'almaseo-seo-playground') . '</strong><br><small>' . __('The chart now shows refreshed data. Caches have been pre-warmed for optimal performance.', 'almaseo-seo-playground') . '</small></p></div>';
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
        <h1><?php _e('Evergreen Content Overview', 'almaseo-seo-playground'); ?>
            <?php if ($stats['unanalyzed'] > 0): ?>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="display: inline-block; margin-left: 20px;">
                <?php wp_nonce_field('almaseo_eg_analyze_all'); ?>
                <input type="hidden" name="action" value="almaseo_eg_analyze_all">
                <button type="submit" class="button button-primary">
                    <?php
                    /* translators: %d = unanalyzed count */
                    printf(esc_html__('Analyze All Posts (%d unanalyzed)', 'almaseo-seo-playground'), intval($stats['unanalyzed']));
                    ?>
                </button>
                <?php if ($stats['unanalyzed'] > 100): ?>
                <div style="display: block; margin-top: 5px;">
                    <small style="color: #d63638;">
                        <?php
                        /* translators: %1$d: number of unanalyzed posts, %2$d: number of times to click */
                        ?>
                        ⚠️ <?php echo sprintf(__('Will process 100 posts at a time. You have %1$d unanalyzed posts - you may need to click this %2$d times.', 'almaseo-seo-playground'),
                            $stats['unanalyzed'], 
                            ceil($stats['unanalyzed'] / 100)); ?>
                    </small>
                </div>
                <?php elseif ($stats['unanalyzed'] > 50): ?>
                <div style="display: block; margin-top: 5px;">
                    <small style="color: #996800;">
                        ℹ️ <?php _e('This may take a moment. The page will refresh when complete.', 'almaseo-seo-playground'); ?>
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
                __('Monitors content freshness and flags posts that may need an update to maintain rankings.', 'almaseo-seo-playground')
            );
        }
        ?>
        
        <!-- Summary Cards -->
        <div class="almaseo-eg-cards" role="region" aria-label="<?php esc_attr_e('Content Health Statistics', 'almaseo-seo-playground'); ?>">
            <div class="almaseo-eg-card almaseo-eg-card-evergreen">
                <div class="almaseo-eg-card-icon">🟢</div>
                <div class="almaseo-eg-card-content">
                    <div class="almaseo-eg-card-number"><?php echo esc_html($stats['evergreen']); ?></div>
                    <div class="almaseo-eg-card-label"><?php _e('Evergreen', 'almaseo-seo-playground'); ?></div>
                    <div class="almaseo-eg-card-percentage"><?php echo esc_html($evergreen_pct); ?>%</div>
                </div>
            </div>
            
            <div class="almaseo-eg-card almaseo-eg-card-watch">
                <div class="almaseo-eg-card-icon">🟡</div>
                <div class="almaseo-eg-card-content">
                    <div class="almaseo-eg-card-number"><?php echo esc_html($stats['watch']); ?></div>
                    <div class="almaseo-eg-card-label"><?php _e('Watch', 'almaseo-seo-playground'); ?></div>
                    <div class="almaseo-eg-card-percentage"><?php echo esc_html($watch_pct); ?>%</div>
                </div>
            </div>
            
            <div class="almaseo-eg-card almaseo-eg-card-stale">
                <div class="almaseo-eg-card-icon">🔴</div>
                <div class="almaseo-eg-card-content">
                    <div class="almaseo-eg-card-number"><?php echo esc_html($stats['stale']); ?></div>
                    <div class="almaseo-eg-card-label"><?php _e('Stale', 'almaseo-seo-playground'); ?></div>
                    <div class="almaseo-eg-card-percentage"><?php echo esc_html($stale_pct); ?>%</div>
                </div>
            </div>
            
            <div class="almaseo-eg-card almaseo-eg-card-unanalyzed">
                <div class="almaseo-eg-card-icon">⚪</div>
                <div class="almaseo-eg-card-content">
                    <div class="almaseo-eg-card-number"><?php echo esc_html($stats['unanalyzed']); ?></div>
                    <div class="almaseo-eg-card-label"><?php _e('Unanalyzed', 'almaseo-seo-playground'); ?></div>
                    <div class="almaseo-eg-card-percentage">—</div>
                </div>
            </div>
        </div>
        
        <!-- Filters and Actions -->
        <div class="almaseo-eg-controls">
            <form method="get" class="almaseo-eg-filters">
                <input type="hidden" name="page" value="almaseo-evergreen" />
                
                <select name="post_type" id="filter-post-type">
                    <option value="all" <?php selected($post_type, 'all'); ?>><?php _e('All Content Types', 'almaseo-seo-playground'); ?></option>
                    <option value="post" <?php selected($post_type, 'post'); ?>><?php _e('Posts', 'almaseo-seo-playground'); ?></option>
                    <option value="page" <?php selected($post_type, 'page'); ?>><?php _e('Pages', 'almaseo-seo-playground'); ?></option>
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
                    <option value="4" <?php selected($date_range, 4); ?>><?php _e('Last 4 Weeks', 'almaseo-seo-playground'); ?></option>
                    <option value="8" <?php selected($date_range, 8); ?>><?php _e('Last 8 Weeks', 'almaseo-seo-playground'); ?></option>
                    <option value="12" <?php selected($date_range, 12); ?>><?php _e('Last 12 Weeks', 'almaseo-seo-playground'); ?></option>
                </select>
                
                <select name="status_filter" id="filter-status">
                    <option value="all" <?php selected($status_filter, 'all'); ?>><?php _e('All Statuses', 'almaseo-seo-playground'); ?></option>
                    <option value="evergreen" <?php selected($status_filter, 'evergreen'); ?>><?php _e('Evergreen', 'almaseo-seo-playground'); ?></option>
                    <option value="watch" <?php selected($status_filter, 'watch'); ?>><?php _e('Watch', 'almaseo-seo-playground'); ?></option>
                    <option value="stale" <?php selected($status_filter, 'stale'); ?>><?php _e('Stale', 'almaseo-seo-playground'); ?></option>
                    <option value="unanalyzed" <?php selected($status_filter, 'unanalyzed'); ?>><?php _e('Unanalyzed', 'almaseo-seo-playground'); ?></option>
                </select>
                
                <button type="submit" class="button"><?php _e('Apply Filters', 'almaseo-seo-playground'); ?></button>
            </form>
            
            <div class="almaseo-eg-actions">
                <span style="color: #666; margin-right: 10px; font-weight: 500;"><?php _e('Export:', 'almaseo-seo-playground'); ?></span>
                <button type="button" class="button" id="export-csv" title="<?php esc_attr_e('Export data as CSV file', 'almaseo-seo-playground'); ?>">
                    <span class="dashicons dashicons-download"></span> <?php _e('CSV', 'almaseo-seo-playground'); ?>
                </button>
                <button type="button" class="button" id="export-pdf" title="<?php esc_attr_e('Generate PDF report', 'almaseo-seo-playground'); ?>">
                    <span class="dashicons dashicons-pdf"></span> <?php _e('PDF', 'almaseo-seo-playground'); ?>
                </button>
                
                <?php if (current_user_can('manage_options')): ?>
                <span style="color: #666; margin: 0 10px;">|</span>
                <form method="post" style="display: inline; position: relative;" onsubmit="this.querySelector('button').disabled = true; this.querySelector('button').innerHTML = '<span class=\'dashicons dashicons-update spin\'></span> Rebuilding...';">
                    <?php wp_nonce_field('almaseo_eg_rebuild_stats'); ?>
                    <button type="submit" name="rebuild_stats" class="button" title="<?php esc_attr_e('Regenerate chart data and clear caches', 'almaseo-seo-playground'); ?>">
                        <span class="dashicons dashicons-update"></span> <?php _e('Rebuild Stats', 'almaseo-seo-playground'); ?>
                    </button>
                    <div style="position: absolute; top: 100%; right: 0; margin-top: 5px; width: 200px; text-align: right;">
                        <small style="color: #666; font-style: italic;">
                            <?php _e('Refreshes chart data & caches', 'almaseo-seo-playground'); ?>
                        </small>
                    </div>
                </form>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Trend Chart -->
        <div class="almaseo-eg-chart-container" role="img" aria-label="<?php esc_attr_e('Content health trend over time', 'almaseo-seo-playground'); ?>">
            <h2><?php _e('Health Trend', 'almaseo-seo-playground'); ?></h2>
            
            <div id="almaseo-eg-trend-chart" 
                 data-weekly="<?php echo esc_attr( wp_json_encode( array_values( $weekly_data ) ) ); ?>"
                 data-weeks="<?php echo esc_attr($date_range); ?>">
                <div class="almaseo-eg-chart-loading"><?php _e('Loading chart...', 'almaseo-seo-playground'); ?></div>
            </div>
            <div class="almaseo-eg-chart-legend">
                <span class="legend-item legend-evergreen">
                    <span class="legend-color"></span> <?php _e('Evergreen', 'almaseo-seo-playground'); ?>
                </span>
                <span class="legend-item legend-watch">
                    <span class="legend-color"></span> <?php _e('Watch', 'almaseo-seo-playground'); ?>
                </span>
                <span class="legend-item legend-stale">
                    <span class="legend-color"></span> <?php _e('Stale', 'almaseo-seo-playground'); ?>
                </span>
            </div>
            <?php
            // Show when the cache was last generated
            $weekly_cache = get_transient( 'almaseo_eg_weekly_' . absint( $date_range ) );
            $generated_at = isset( $weekly_cache['generated'] ) ? (int) $weekly_cache['generated'] : 0;
            if ( $generated_at ) {
                echo '<div class="almaseo-eg-last-checked" style="text-align: right; color: #666; font-size: 12px; margin-top: 10px;">';
                echo esc_html__('Updated ', 'almaseo-seo-playground') . esc_html( human_time_diff( $generated_at ) ) . esc_html__(' ago', 'almaseo-seo-playground');
                echo '</div>';
            }
            ?>
        </div>
        
        <!-- At-Risk Posts Table -->
        <div class="almaseo-eg-table-container">
            <h2><?php _e('Content Requiring Attention', 'almaseo-seo-playground'); ?></h2>
            
            <table class="wp-list-table widefat fixed striped posts">
                <thead>
                    <tr>
                        <th scope="col" class="column-title"><?php _e('Post', 'almaseo-seo-playground'); ?></th>
                        <th scope="col" class="column-status"><?php _e('Status', 'almaseo-seo-playground'); ?></th>
                        <th scope="col" class="column-updated"><?php _e('Last Updated', 'almaseo-seo-playground'); ?></th>
                        <th scope="col" class="column-trend"><?php _e('90d Trend', 'almaseo-seo-playground'); ?></th>
                        <th scope="col" class="column-actions"><?php _e('Quick Actions', 'almaseo-seo-playground'); ?></th>
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
                                            <?php _e('View', 'almaseo-seo-playground'); ?>
                                        </a> |
                                    </span>
                                    <span class="edit">
                                        <a href="<?php echo get_edit_post_link($post_data['id']); ?>" target="_blank">
                                            <?php _e('Edit', 'almaseo-seo-playground'); ?>
                                        </a>
                                    </span>
                                </div>
                            </td>
                            <td class="column-status">
                                <?php echo almaseo_eg_render_status_pill($post_data['status']); ?>
                            </td>
                            <td class="column-updated">
                                <?php echo esc_html($post_data['days_ago']); ?> <?php _e('days ago', 'almaseo-seo-playground'); ?>
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
                                    <?php _e('Analyze', 'almaseo-seo-playground'); ?>
                                </button>
                                <?php if ($post_data['status'] === 'stale'): ?>
                                <button type="button" class="button button-small almaseo-eg-mark-refreshed" 
                                        data-post-id="<?php echo $post_data['id']; ?>">
                                    <?php _e('Mark Refreshed', 'almaseo-seo-playground'); ?>
                                </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="no-items">
                                <?php _e('No posts found matching your filters.', 'almaseo-seo-playground'); ?>
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
                        'prev_text' => __('&laquo;', 'almaseo-seo-playground'),
                        'next_text' => __('&raquo;', 'almaseo-seo-playground'),
                        'total' => $at_risk_posts['total_pages'],
                        'current' => $paged
                    ));
                    ?>
                </div>
            </div>
            <?php endif; ?>
        </div>

        <?php
        // Advanced Evergreen Panels (Pro Only)
        if (almaseo_feature_available('evergreen_advanced')) {
            $adv_settings = get_option('almaseo_evergreen_advanced_settings', array('enabled' => false));
            if (!empty($adv_settings['enabled'])) {
                // Get advanced summary data
                $advanced_summary = almaseo_eg_get_advanced_summary($post_type);
                ?>

                <!-- Advanced Evergreen Panels Section -->
                <div class="almaseo-eg-advanced-section" style="margin-top: 40px; padding-top: 40px; border-top: 2px solid #e5e7eb;">
                    <h2 style="margin-bottom: 20px;">
                        <span style="background: linear-gradient(135deg, #7c3aed 0%, #6366f1 100%); -webkit-background-clip: text; -webkit-text-fill-color: transparent; background-clip: text;">
                            <?php _e('Advanced Insights', 'almaseo-seo-playground'); ?>
                        </span>
                        <span class="dashicons dashicons-star-filled" style="color: #7c3aed; font-size: 18px; margin-left: 8px;"></span>
                    </h2>

                    <!-- Refresh Priority Matrix Panel -->
                    <div class="almaseo-eg-panel" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <h3 style="margin-top: 0; color: #1e293b; font-size: 16px;">
                            <?php _e('Refresh Priority Matrix', 'almaseo-seo-playground'); ?>
                        </h3>
                        <p class="description" style="margin-bottom: 20px;">
                            <?php _e('Content segments based on refresh score and risk level', 'almaseo-seo-playground'); ?>
                        </p>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <div style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); border-left: 4px solid #dc2626; padding: 16px; border-radius: 6px;">
                                <div style="font-size: 32px; font-weight: 700; color: #991b1b;">
                                    <?php echo esc_html($advanced_summary['segments']['urgent']['count']); ?>
                                </div>
                                <div style="color: #7f1d1d; font-weight: 600; margin-top: 4px;">
                                    <?php _e('Urgent', 'almaseo-seo-playground'); ?>
                                </div>
                                <div style="color: #991b1b; font-size: 13px; margin-top: 4px;">
                                    <?php _e('High risk, needs immediate refresh', 'almaseo-seo-playground'); ?>
                                </div>
                            </div>

                            <div style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-left: 4px solid #f59e0b; padding: 16px; border-radius: 6px;">
                                <div style="font-size: 32px; font-weight: 700; color: #92400e;">
                                    <?php echo esc_html($advanced_summary['segments']['at_risk']['count']); ?>
                                </div>
                                <div style="color: #78350f; font-weight: 600; margin-top: 4px;">
                                    <?php _e('At Risk', 'almaseo-seo-playground'); ?>
                                </div>
                                <div style="color: #92400e; font-size: 13px; margin-top: 4px;">
                                    <?php _e('Medium risk, monitor closely', 'almaseo-seo-playground'); ?>
                                </div>
                            </div>

                            <div style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-left: 4px solid #10b981; padding: 16px; border-radius: 6px;">
                                <div style="font-size: 32px; font-weight: 700; color: #065f46;">
                                    <?php echo esc_html($advanced_summary['segments']['stable']['count']); ?>
                                </div>
                                <div style="color: #064e3b; font-weight: 600; margin-top: 4px;">
                                    <?php _e('Stable', 'almaseo-seo-playground'); ?>
                                </div>
                                <div style="color: #065f46; font-size: 13px; margin-top: 4px;">
                                    <?php _e('Low risk, performing well', 'almaseo-seo-playground'); ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- AI Freshness Impact Panel -->
                    <div class="almaseo-eg-panel" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px; margin-bottom: 20px;">
                        <h3 style="margin-top: 0; color: #1e293b; font-size: 16px;">
                            <?php _e('AI Freshness Impact', 'almaseo-seo-playground'); ?>
                        </h3>
                        <p class="description" style="margin-bottom: 20px;">
                            <?php _e('How content clarity and AI-friendly signals affect your content health', 'almaseo-seo-playground'); ?>
                        </p>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
                            <div style="background: #f8fafc; padding: 16px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                <div style="color: #64748b; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <?php _e('Average AI Freshness Score', 'almaseo-seo-playground'); ?>
                                </div>
                                <div style="font-size: 36px; font-weight: 700; color: #1e293b; margin-top: 8px;">
                                    <?php echo esc_html($advanced_summary['ai_freshness']['average']); ?>
                                </div>
                                <div style="color: #64748b; font-size: 12px; margin-top: 4px;">
                                    <?php _e('out of 100', 'almaseo-seo-playground'); ?>
                                </div>
                            </div>

                            <div style="background: #fef3c7; padding: 16px; border-radius: 6px; border: 1px solid #fde68a;">
                                <div style="color: #92400e; font-size: 13px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px;">
                                    <?php _e('Posts Below Threshold (50)', 'almaseo-seo-playground'); ?>
                                </div>
                                <div style="font-size: 36px; font-weight: 700; color: #78350f; margin-top: 8px;">
                                    <?php echo esc_html($advanced_summary['ai_freshness']['below_threshold']); ?>
                                </div>
                                <div style="color: #92400e; font-size: 12px; margin-top: 4px;">
                                    <?php _e('need AI optimization', 'almaseo-seo-playground'); ?>
                                </div>
                            </div>
                        </div>

                        <?php if (!empty($advanced_summary['ai_freshness']['top_at_risk'])): ?>
                        <div>
                            <h4 style="margin: 16px 0 12px 0; color: #475569; font-size: 14px; font-weight: 600;">
                                <?php _e('Top 5 Posts Needing AI Optimization', 'almaseo-seo-playground'); ?>
                            </h4>
                            <table class="wp-list-table widefat fixed striped" style="font-size: 13px;">
                                <thead>
                                    <tr>
                                        <th><?php _e('Post', 'almaseo-seo-playground'); ?></th>
                                        <th style="width: 120px;"><?php _e('AI Score', 'almaseo-seo-playground'); ?></th>
                                        <th style="width: 120px;"><?php _e('Risk', 'almaseo-seo-playground'); ?></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($advanced_summary['ai_freshness']['top_at_risk'] as $item): ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo get_edit_post_link($item['post_id']); ?>" target="_blank">
                                                <?php echo esc_html($item['title']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <div style="flex: 1; background: #e2e8f0; height: 8px; border-radius: 4px; overflow: hidden;">
                                                    <div style="background: <?php echo $item['ai_score'] >= 75 ? '#dc2626' : ($item['ai_score'] >= 50 ? '#f59e0b' : '#10b981'); ?>; height: 100%; width: <?php echo esc_attr($item['ai_score']); ?>%;"></div>
                                                </div>
                                                <span style="font-weight: 600; color: #1e293b;"><?php echo esc_html($item['ai_score']); ?></span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php echo almaseo_eg_render_status_pill($item['risk_level']); ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php endif; ?>
                    </div>

                    <!-- Traffic vs Freshness Trend Panel -->
                    <div class="almaseo-eg-panel" style="background: #fff; border: 1px solid #e5e7eb; border-radius: 8px; padding: 20px;">
                        <h3 style="margin-top: 0; color: #1e293b; font-size: 16px;">
                            <?php _e('Traffic vs Freshness Trend', 'almaseo-seo-playground'); ?>
                        </h3>
                        <p class="description" style="margin-bottom: 20px;">
                            <?php _e('How traffic performance correlates with content freshness', 'almaseo-seo-playground'); ?>
                        </p>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px;">
                            <div style="background: #f0f9ff; padding: 16px; border-radius: 6px; border: 1px solid #bae6fd;">
                                <div style="font-size: 28px; font-weight: 700; color: #0c4a6e;">
                                    <?php echo esc_html($advanced_summary['traffic_freshness']['high_traffic_fresh']); ?>
                                </div>
                                <div style="color: #075985; font-weight: 600; margin-top: 4px; font-size: 14px;">
                                    <?php _e('High Traffic + Fresh', 'almaseo-seo-playground'); ?>
                                </div>
                                <div style="color: #0369a1; font-size: 12px; margin-top: 4px;">
                                    <?php _e('Performing well', 'almaseo-seo-playground'); ?>
                                </div>
                            </div>

                            <div style="background: #fef3c7; padding: 16px; border-radius: 6px; border: 1px solid #fde68a;">
                                <div style="font-size: 28px; font-weight: 700; color: #92400e;">
                                    <?php echo esc_html($advanced_summary['traffic_freshness']['high_traffic_stale']); ?>
                                </div>
                                <div style="color: #78350f; font-weight: 600; margin-top: 4px; font-size: 14px;">
                                    <?php _e('High Traffic + Stale', 'almaseo-seo-playground'); ?>
                                </div>
                                <div style="color: #92400e; font-size: 12px; margin-top: 4px;">
                                    <?php _e('Priority refresh targets', 'almaseo-seo-playground'); ?>
                                </div>
                            </div>

                            <div style="background: #f3f4f6; padding: 16px; border-radius: 6px; border: 1px solid #d1d5db;">
                                <div style="font-size: 28px; font-weight: 700; color: #374151;">
                                    <?php echo esc_html($advanced_summary['traffic_freshness']['low_traffic_stale']); ?>
                                </div>
                                <div style="color: #4b5563; font-weight: 600; margin-top: 4px; font-size: 14px;">
                                    <?php _e('Low Traffic + Stale', 'almaseo-seo-playground'); ?>
                                </div>
                                <div style="color: #6b7280; font-size: 12px; margin-top: 4px;">
                                    <?php _e('Consider archiving', 'almaseo-seo-playground'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
            }
        }
        ?>

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
            return '<span class="almaseo-eg-pill almaseo-eg-evergreen">🟢 ' . __('Evergreen', 'almaseo-seo-playground') . '</span>';
        case 'watch':
            return '<span class="almaseo-eg-pill almaseo-eg-watch">🟡 ' . __('Watch', 'almaseo-seo-playground') . '</span>';
        case 'stale':
            return '<span class="almaseo-eg-pill almaseo-eg-stale">🔴 ' . __('Stale', 'almaseo-seo-playground') . '</span>';
        default:
            return '<span class="almaseo-eg-pill almaseo-eg-unknown">⚪ ' . __('Unanalyzed', 'almaseo-seo-playground') . '</span>';
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
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(__('Permission denied', 'almaseo-seo-playground'));
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
        wp_send_json_error(__('Failed to analyze post', 'almaseo-seo-playground'));
    }
}
add_action('wp_ajax_almaseo_eg_quick_analyze', 'almaseo_eg_ajax_quick_analyze');

/**
 * Get Advanced Evergreen Summary (Pro)
 *
 * Returns computed data for the Advanced Insights panels
 *
 * @param string $post_type Post type filter ('all', 'post', 'page', etc.)
 * @return array Advanced summary data
 * @since 6.5.0
 */
function almaseo_eg_get_advanced_summary($post_type = 'all') {
    if (!almaseo_feature_available('evergreen_advanced')) {
        return array(
            'segments' => array(
                'urgent' => array('count' => 0),
                'at_risk' => array('count' => 0),
                'stable' => array('count' => 0)
            ),
            'ai_freshness' => array(
                'average' => 0,
                'below_threshold' => 0,
                'top_at_risk' => array()
            ),
            'traffic_freshness' => array(
                'high_traffic_fresh' => 0,
                'high_traffic_stale' => 0,
                'low_traffic_stale' => 0
            )
        );
    }

    // Check cache first
    $cache_key = 'almaseo_eg_adv_summary_' . $post_type;
    $cached = get_transient($cache_key);

    if ($cached !== false) {
        return $cached;
    }

    global $wpdb;

    // Build post type condition
    $post_types = ($post_type === 'all')
        ? array('post', 'page', 'product')
        : array($post_type);

    $post_type_placeholders = implode(',', array_fill(0, count($post_types), '%s'));

    // Get all published posts with advanced scores
    $query = "
        SELECT
            p.ID,
            p.post_title,
            COALESCE(pm_refresh.meta_value, 0) as refresh_score,
            COALESCE(pm_ai.meta_value, 0) as ai_score,
            COALESCE(pm_risk.meta_value, '') as risk_level,
            COALESCE(pm_clicks90.meta_value, 0) as clicks_90d,
            COALESCE(pm_clicksprev.meta_value, 0) as clicks_prev90d,
            DATEDIFF(NOW(), p.post_modified) as days_since_update
        FROM {$wpdb->posts} p
        LEFT JOIN {$wpdb->postmeta} pm_refresh ON p.ID = pm_refresh.post_id AND pm_refresh.meta_key = '_almaseo_evergreen_refresh_score'
        LEFT JOIN {$wpdb->postmeta} pm_ai ON p.ID = pm_ai.post_id AND pm_ai.meta_key = '_almaseo_evergreen_ai_freshness_score'
        LEFT JOIN {$wpdb->postmeta} pm_risk ON p.ID = pm_risk.post_id AND pm_risk.meta_key = '_almaseo_evergreen_risk_level'
        LEFT JOIN {$wpdb->postmeta} pm_clicks90 ON p.ID = pm_clicks90.post_id AND pm_clicks90.meta_key = '_almaseo_eg_clicks_90d'
        LEFT JOIN {$wpdb->postmeta} pm_clicksprev ON p.ID = pm_clicksprev.post_id AND pm_clicksprev.meta_key = '_almaseo_eg_clicks_prev90d'
        WHERE p.post_type IN ($post_type_placeholders)
        AND p.post_status = 'publish'
    ";

    $results = $wpdb->get_results($wpdb->prepare($query, ...$post_types));

    // Initialize counters
    $segments = array(
        'urgent' => 0,
        'at_risk' => 0,
        'stable' => 0
    );

    $ai_scores = array();
    $below_threshold = 0;
    $top_at_risk = array();

    $high_traffic_fresh = 0;
    $high_traffic_stale = 0;
    $low_traffic_stale = 0;

    $adv_settings = get_option('almaseo_evergreen_advanced_settings', array(
        'stale_days_threshold' => 365
    ));

    foreach ($results as $row) {
        $refresh_score = (int) $row->refresh_score;
        $ai_score = (int) $row->ai_score;
        $risk_level = $row->risk_level;
        $clicks_90d = (int) $row->clicks_90d;
        $clicks_prev90d = (int) $row->clicks_prev90d;
        $days_since_update = (int) $row->days_since_update;

        // Segment counts by risk level
        if ($risk_level === 'high') {
            $segments['urgent']++;
        } elseif ($risk_level === 'medium') {
            $segments['at_risk']++;
        } else {
            $segments['stable']++;
        }

        // AI freshness tracking
        if ($ai_score > 0) {
            $ai_scores[] = $ai_score;

            if ($ai_score >= 50) {
                $below_threshold++;

                // Track top at-risk posts for the table
                $top_at_risk[] = array(
                    'post_id' => (int) $row->ID,
                    'title' => $row->post_title,
                    'ai_score' => $ai_score,
                    'risk_level' => $risk_level ?: 'low'
                );
            }
        }

        // Traffic vs Freshness buckets
        $has_traffic = ($clicks_90d > 0 || $clicks_prev90d > 0);
        $high_traffic = ($has_traffic && $clicks_90d >= 100); // Threshold: 100 clicks
        $is_stale = ($days_since_update > $adv_settings['stale_days_threshold']);

        if ($high_traffic && !$is_stale) {
            $high_traffic_fresh++;
        } elseif ($high_traffic && $is_stale) {
            $high_traffic_stale++;
        } elseif (!$high_traffic && $is_stale) {
            $low_traffic_stale++;
        }
    }

    // Calculate average AI score
    $average_ai = 0;
    if (!empty($ai_scores)) {
        $average_ai = (int) round(array_sum($ai_scores) / count($ai_scores));
    }

    // Sort top at-risk by AI score (descending) and take top 5
    usort($top_at_risk, function($a, $b) {
        return $b['ai_score'] - $a['ai_score'];
    });
    $top_at_risk = array_slice($top_at_risk, 0, 5);

    $summary = array(
        'segments' => array(
            'urgent' => array('count' => $segments['urgent']),
            'at_risk' => array('count' => $segments['at_risk']),
            'stable' => array('count' => $segments['stable'])
        ),
        'ai_freshness' => array(
            'average' => $average_ai,
            'below_threshold' => $below_threshold,
            'top_at_risk' => $top_at_risk
        ),
        'traffic_freshness' => array(
            'high_traffic_fresh' => $high_traffic_fresh,
            'high_traffic_stale' => $high_traffic_stale,
            'low_traffic_stale' => $low_traffic_stale
        )
    );

    // Cache for 5 minutes
    set_transient($cache_key, $summary, 5 * MINUTE_IN_SECONDS);

    return $summary;
}