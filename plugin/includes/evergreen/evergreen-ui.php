<?php
/**
 * AlmaSEO Evergreen - UI Components
 * 
 * Handles all UI-related functionality including columns, filters, and AJAX handlers
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler for refreshing evergreen state
 */
function almaseo_eg_ajax_refresh() {
    check_ajax_referer('almaseo_eg_ajax', 'nonce');
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error('Unauthorized');
    }
    
    // Force refresh GSC data if available
    almaseo_eg_refresh_gsc_data($post_id);
    
    // Score the post
    $result = almaseo_score_evergreen($post_id);
    
    if (!empty($result['status'])) {
        // Update status
        almaseo_eg_set_status($post_id, $result['status']);
        almaseo_eg_set_last_checked($post_id);
        
        // Get additional data for response
        $post = get_post($post_id);
        $ages = almaseo_get_post_ages($post);
        $clicks = almaseo_eg_get_clicks($post_id);
        $trend = almaseo_compute_trend($clicks['clicks_90d'], $clicks['clicks_prev90d']);
        
        wp_send_json_success(array(
            'state' => $result['status'],
            'state_label' => almaseo_eg_get_state_label($result['status']),
            'state_color' => almaseo_eg_get_state_color($result['status']),
            'days_since_update' => $ages['updated_days'],
            'traffic_drop' => $trend,
            'last_checked' => current_time('mysql')
        ));
    } else {
        wp_send_json_error('Failed to analyze post');
    }
}
add_action('wp_ajax_almaseo_eg_refresh', 'almaseo_eg_ajax_refresh');

/**
 * Refresh GSC data for a post
 * 
 * @param int $post_id Post ID
 */
function almaseo_eg_refresh_gsc_data($post_id) {
    // Get post URL
    $url = get_permalink($post_id);
    if (!$url) {
        return;
    }
    
    // Check if GSC integration exists
    if (function_exists('almaseo_gsc_get_url_metrics')) {
        // Fetch fresh data from GSC
        $current_data = almaseo_gsc_get_url_metrics($url, 90);
        $previous_data = almaseo_gsc_get_url_metrics($url, 90, 90);
        
        if ($current_data && $previous_data) {
            // Update clicks data
            almaseo_eg_set_clicks(
                $post_id,
                isset($current_data['clicks']) ? $current_data['clicks'] : 0,
                isset($previous_data['clicks']) ? $previous_data['clicks'] : 0
            );
        }
    }
}

/**
 * Get state label
 * 
 * @param string $state State
 * @return string
 */
function almaseo_eg_get_state_label($state) {
    switch ($state) {
        case ALMASEO_EG_STATUS_EVERGREEN:
            return __('Evergreen', 'almaseo');
        case ALMASEO_EG_STATUS_WATCH:
            return __('Watch', 'almaseo');
        case ALMASEO_EG_STATUS_STALE:
            return __('Stale', 'almaseo');
        default:
            return __('Unknown', 'almaseo');
    }
}

/**
 * Get state color
 * 
 * @param string $state State
 * @return string
 */
function almaseo_eg_get_state_color($state) {
    switch ($state) {
        case ALMASEO_EG_STATUS_EVERGREEN:
            return '#10b981';
        case ALMASEO_EG_STATUS_WATCH:
            return '#f59e0b';
        case ALMASEO_EG_STATUS_STALE:
            return '#ef4444';
        default:
            return '#6b7280';
    }
}

/**
 * Add quick action links to post row
 */
function almaseo_eg_post_row_actions($actions, $post) {
    if (!in_array($post->post_type, array('post', 'page'))) {
        return $actions;
    }
    
    if (!current_user_can('edit_post', $post->ID)) {
        return $actions;
    }
    
    // Add refresh action
    $actions['evergreen_refresh'] = sprintf(
        '<a href="#" class="almaseo-eg-refresh" data-post-id="%d">%s</a>',
        $post->ID,
        __('Refresh Evergreen', 'almaseo')
    );
    
    // Add mark as refreshed action for stale posts
    $status = almaseo_eg_get_status($post->ID);
    if ($status === ALMASEO_EG_STATUS_STALE) {
        $actions['evergreen_mark'] = sprintf(
            '<a href="#" class="almaseo-eg-mark-refreshed" data-post-id="%d">%s</a>',
            $post->ID,
            __('Mark as Refreshed', 'almaseo')
        );
    }
    
    return $actions;
}

// Dashboard widget functionality moved to widget.php to avoid duplication

/**
 * Add admin notices for state changes
 */
function almaseo_eg_admin_notices() {
    // Only show on dashboard and post list pages
    $screen = get_current_screen();
    if (!in_array($screen->id, array('dashboard', 'edit-post', 'edit-page'))) {
        return;
    }
    
    // Get recent state changes
    $logs = get_option('almaseo_evergreen_logs', array());
    $recent_logs = array_slice(array_reverse($logs), 0, 5);
    
    // Show only recent changes (last 24 hours)
    $cutoff = time() - DAY_IN_SECONDS;
    $recent_changes = array();
    
    foreach ($recent_logs as $log) {
        if ($log['timestamp'] > $cutoff && $log['new_state'] !== ALMASEO_EG_STATUS_EVERGREEN) {
            $recent_changes[] = $log;
        }
    }
    
    if (empty($recent_changes)) {
        return;
    }
    ?>
    <div class="notice notice-warning is-dismissible almaseo-eg-notice">
        <p><strong><?php _e('Evergreen Content Updates:', 'almaseo'); ?></strong></p>
        <ul style="margin: 5px 0 5px 20px;">
            <?php foreach ($recent_changes as $change): ?>
            <li>
                <?php
                $state_emoji = $change['new_state'] === ALMASEO_EG_STATUS_STALE ? '🔴' : '🟡';
                printf(
                    __('%s "%s" is now %s', 'almaseo'),
                    $state_emoji,
                    esc_html($change['post_title']),
                    '<strong>' . almaseo_eg_get_state_label($change['new_state']) . '</strong>'
                );
                ?>
                <a href="<?php echo get_edit_post_link($change['post_id']); ?>" style="margin-left: 5px;">
                    <?php _e('Edit', 'almaseo'); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php
}

/**
 * Add settings page
 */
function almaseo_eg_add_settings_page() {
    add_submenu_page(
        'seo-playground',
        __('Evergreen Tracker', 'almaseo'),
        __('Evergreen Tracker', 'almaseo'),
        'manage_options',
        'almaseo-evergreen',
        'almaseo_eg_settings_page_content'
    );
}

/**
 * Settings page content
 */
function almaseo_eg_settings_page_content() {
    // Handle form submission
    if (isset($_POST['almaseo_eg_save_settings']) && check_admin_referer('almaseo_eg_settings')) {
        $settings = array(
            'watch_days' => intval($_POST['watch_days']),
            'stale_days' => intval($_POST['stale_days']),
            'watch_traffic_drop' => floatval($_POST['watch_traffic_drop']),
            'stale_traffic_drop' => floatval($_POST['stale_traffic_drop']),
            'enable_digest' => isset($_POST['enable_digest'])
        );
        
        update_option(ALMASEO_EG_SETTINGS_OPTION, $settings);
        
        echo '<div class="notice notice-success"><p>' . __('Settings saved successfully!', 'almaseo') . '</p></div>';
    }
    
    // Handle manual refresh
    if (isset($_POST['almaseo_eg_refresh_all']) && check_admin_referer('almaseo_eg_refresh')) {
        $result = almaseo_eg_recalc_all(100);
        
        echo '<div class="notice notice-success"><p>';
        printf(__('Refreshed %d posts successfully!', 'almaseo'), $result['processed']);
        echo '</p></div>';
    }
    
    // Get current settings
    $settings = almaseo_eg_get_settings();
    $stats = almaseo_eg_get_stats();
    ?>
    <div class="wrap">
        <h1><?php _e('Evergreen Content Tracker', 'almaseo'); ?></h1>
        
        <!-- Stats Overview -->
        <div class="almaseo-eg-stats-card" style="margin: 20px 0;">
            <h2><?php _e('Content Health Overview', 'almaseo'); ?></h2>
            
            <div class="almaseo-eg-progress">
                <?php
                $total = $stats['total'] ?: 1;
                $evergreen_pct = ($stats['evergreen'] / $total) * 100;
                $watch_pct = ($stats['watch'] / $total) * 100;
                $stale_pct = ($stats['stale'] / $total) * 100;
                ?>
                <div class="almaseo-eg-progress-segment almaseo-eg-progress-evergreen" 
                     style="width: <?php echo $evergreen_pct; ?>%;">
                    <?php if ($evergreen_pct > 10): echo round($evergreen_pct) . '%'; endif; ?>
                </div>
                <div class="almaseo-eg-progress-segment almaseo-eg-progress-watch" 
                     style="width: <?php echo $watch_pct; ?>%;">
                    <?php if ($watch_pct > 10): echo round($watch_pct) . '%'; endif; ?>
                </div>
                <div class="almaseo-eg-progress-segment almaseo-eg-progress-stale" 
                     style="width: <?php echo $stale_pct; ?>%;">
                    <?php if ($stale_pct > 10): echo round($stale_pct) . '%'; endif; ?>
                </div>
            </div>
            
            <table class="widefat" style="margin-top: 20px;">
                <thead>
                    <tr>
                        <th><?php _e('Status', 'almaseo'); ?></th>
                        <th><?php _e('Count', 'almaseo'); ?></th>
                        <th><?php _e('Percentage', 'almaseo'); ?></th>
                        <th><?php _e('Action', 'almaseo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><span class="almaseo-eg-pill almaseo-eg-evergreen">🟢 <?php _e('Evergreen', 'almaseo'); ?></span></td>
                        <td><?php echo esc_html($stats['evergreen']); ?></td>
                        <td><?php echo round($evergreen_pct, 1); ?>%</td>
                        <td><a href="<?php echo admin_url('edit.php?evergreen_filter=evergreen'); ?>"><?php _e('View', 'almaseo'); ?></a></td>
                    </tr>
                    <tr>
                        <td><span class="almaseo-eg-pill almaseo-eg-watch">🟡 <?php _e('Watch', 'almaseo'); ?></span></td>
                        <td><?php echo esc_html($stats['watch']); ?></td>
                        <td><?php echo round($watch_pct, 1); ?>%</td>
                        <td><a href="<?php echo admin_url('edit.php?evergreen_filter=watch'); ?>"><?php _e('View', 'almaseo'); ?></a></td>
                    </tr>
                    <tr>
                        <td><span class="almaseo-eg-pill almaseo-eg-stale">🔴 <?php _e('Stale', 'almaseo'); ?></span></td>
                        <td><?php echo esc_html($stats['stale']); ?></td>
                        <td><?php echo round($stale_pct, 1); ?>%</td>
                        <td><a href="<?php echo admin_url('edit.php?evergreen_filter=stale'); ?>"><?php _e('View', 'almaseo'); ?></a></td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Settings -->
        <div class="almaseo-eg-stats-card">
            <h2><?php _e('Settings', 'almaseo'); ?></h2>
            
            <form method="post" action="">
                <?php wp_nonce_field('almaseo_eg_settings'); ?>
                
                <table class="form-table">
                    <tr>
                        <th scope="row">
                            <label for="watch_days"><?php _e('Watch Threshold (Days)', 'almaseo'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="watch_days" name="watch_days" 
                                   value="<?php echo esc_attr($settings['watch_days']); ?>" 
                                   min="30" max="365" />
                            <p class="description">
                                <?php _e('Posts not updated for this many days will be marked as "Watch"', 'almaseo'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="stale_days"><?php _e('Stale Threshold (Days)', 'almaseo'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="stale_days" name="stale_days" 
                                   value="<?php echo esc_attr($settings['stale_days']); ?>" 
                                   min="90" max="730" />
                            <p class="description">
                                <?php _e('Posts not updated for this many days will be marked as "Stale"', 'almaseo'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="watch_traffic_drop"><?php _e('Watch Traffic Drop (%)', 'almaseo'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="watch_traffic_drop" name="watch_traffic_drop" 
                                   value="<?php echo esc_attr($settings['watch_traffic_drop']); ?>" 
                                   min="10" max="50" step="5" />
                            <p class="description">
                                <?php _e('Traffic drop percentage to trigger "Watch" status', 'almaseo'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <label for="stale_traffic_drop"><?php _e('Stale Traffic Drop (%)', 'almaseo'); ?></label>
                        </th>
                        <td>
                            <input type="number" id="stale_traffic_drop" name="stale_traffic_drop" 
                                   value="<?php echo esc_attr($settings['stale_traffic_drop']); ?>" 
                                   min="20" max="80" step="5" />
                            <p class="description">
                                <?php _e('Traffic drop percentage to trigger "Stale" status', 'almaseo'); ?>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row">
                            <?php _e('Email Digest', 'almaseo'); ?>
                        </th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_digest" value="1" 
                                       <?php checked($settings['enable_digest']); ?> />
                                <?php _e('Send weekly email digest of stale content', 'almaseo'); ?>
                            </label>
                        </td>
                    </tr>
                </table>
                
                <p class="submit">
                    <input type="submit" name="almaseo_eg_save_settings" class="button-primary" 
                           value="<?php _e('Save Settings', 'almaseo'); ?>" />
                </p>
            </form>
        </div>
        
        <!-- Manual Actions -->
        <div class="almaseo-eg-stats-card" style="margin-top: 20px;">
            <h2><?php _e('Manual Actions', 'almaseo'); ?></h2>
            
            <form method="post" action="" style="display: inline;">
                <?php wp_nonce_field('almaseo_eg_refresh'); ?>
                <p>
                    <input type="submit" name="almaseo_eg_refresh_all" class="button" 
                           value="<?php _e('Refresh All Posts (First 100)', 'almaseo'); ?>" 
                           onclick="return confirm('<?php _e('This will recalculate the status for up to 100 posts. Continue?', 'almaseo'); ?>');" />
                    <span class="description" style="margin-left: 10px;">
                        <?php _e('Manually recalculate evergreen status for all posts', 'almaseo'); ?>
                    </span>
                </p>
            </form>
        </div>
    </div>
    <?php
}

/**
 * Initialize UI hooks
 */
function almaseo_eg_init_ui() {
    // Post row actions
    add_filter('post_row_actions', 'almaseo_eg_post_row_actions', 10, 2);
    add_filter('page_row_actions', 'almaseo_eg_post_row_actions', 10, 2);
    
    // Admin notices
    add_action('admin_notices', 'almaseo_eg_admin_notices');
    
    // Settings page
    add_action('admin_menu', 'almaseo_eg_add_settings_page', 20);
}
add_action('admin_init', 'almaseo_eg_init_ui');