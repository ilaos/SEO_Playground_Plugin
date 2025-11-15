<?php
/**
 * AlmaSEO Evergreen Feature - Post Meta Box
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register meta box
 */
function almaseo_eg_add_meta_box() {
    add_meta_box(
        'almaseo_evergreen',
        __('Evergreen Content Health', 'almaseo'),
        'almaseo_eg_meta_box_content',
        array('post', 'page'),
        'side',
        'default'
    );
}

/**
 * Meta box content
 */
function almaseo_eg_meta_box_content($post) {
    // Add nonce
    wp_nonce_field('almaseo_eg_meta_box', 'almaseo_eg_meta_box_nonce');
    
    // Get current data
    $status = almaseo_eg_get_status($post->ID);
    $ages = almaseo_get_post_ages($post);
    $clicks = almaseo_eg_get_clicks($post->ID);
    $trend = almaseo_compute_trend($clicks['clicks_90d'], $clicks['clicks_prev90d']);
    $notes = almaseo_eg_get_notes($post->ID);
    $last_checked = almaseo_eg_get_last_checked($post->ID);
    
    // Score the post
    $scoring = almaseo_score_evergreen($post);
    
    // Display status badge
    echo '<div style="text-align: center; margin-bottom: 15px;">';
    switch ($status) {
        case ALMASEO_EG_STATUS_EVERGREEN:
            echo '<span style="display: inline-block; padding: 8px 16px; background: #d4edda; color: #155724; border-radius: 20px; font-weight: bold;">';
            echo '🟢 ' . __('Evergreen', 'almaseo');
            echo '</span>';
            break;
            
        case ALMASEO_EG_STATUS_WATCH:
            echo '<span style="display: inline-block; padding: 8px 16px; background: #fff3cd; color: #856404; border-radius: 20px; font-weight: bold;">';
            echo '🟡 ' . __('Watch', 'almaseo');
            echo '</span>';
            break;
            
        case ALMASEO_EG_STATUS_STALE:
            echo '<span style="display: inline-block; padding: 8px 16px; background: #f8d7da; color: #721c24; border-radius: 20px; font-weight: bold;">';
            echo '🔴 ' . __('Stale', 'almaseo');
            echo '</span>';
            break;
            
        default:
            echo '<span style="display: inline-block; padding: 8px 16px; background: #e2e3e5; color: #383d41; border-radius: 20px; font-weight: bold;">';
            echo '⚪ ' . __('Not Analyzed', 'almaseo');
            echo '</span>';
            break;
    }
    echo '</div>';
    
    // Display last recalculated timestamp
    if ($last_checked) {
        $checked_time = strtotime($last_checked);
        $time_ago = human_time_diff($checked_time, current_time('timestamp'));
        ?>
        <div style="text-align: center; margin-bottom: 15px;">
            <span style="font-size: 12px; color: #666;" 
                  title="<?php esc_attr_e('This shows when Evergreen status was last recalculated.', 'almaseo'); ?>"
                  id="almaseo-eg-last-checked">
                <?php echo sprintf(__('Last recalculated: %s ago', 'almaseo'), $time_ago); ?>
            </span>
        </div>
        <?php
    } else {
        ?>
        <div style="text-align: center; margin-bottom: 15px;">
            <span style="font-size: 12px; color: #999;" 
                  title="<?php esc_attr_e('This shows when Evergreen status was last recalculated.', 'almaseo'); ?>"
                  id="almaseo-eg-last-checked">
                <?php _e('Not yet analyzed', 'almaseo'); ?>
            </span>
        </div>
        <?php
    }
    
    // Display metrics
    ?>
    <div style="margin-bottom: 15px;">
        <h4 style="margin-bottom: 10px;"><?php _e('Metrics', 'almaseo'); ?></h4>
        
        <table style="width: 100%; font-size: 13px;">
            <tr>
                <td style="padding: 4px 0;"><strong><?php _e('Published:', 'almaseo'); ?></strong></td>
                <td style="padding: 4px 0; text-align: right;">
                    <?php echo sprintf(_n('%d day ago', '%d days ago', $ages['published_days'], 'almaseo'), $ages['published_days']); ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 4px 0;"><strong><?php _e('Updated:', 'almaseo'); ?></strong></td>
                <td style="padding: 4px 0; text-align: right;">
                    <?php echo sprintf(_n('%d day ago', '%d days ago', $ages['updated_days'], 'almaseo'), $ages['updated_days']); ?>
                </td>
            </tr>
            <?php if ($clicks['clicks_90d'] > 0 || $clicks['clicks_prev90d'] > 0): ?>
            <tr>
                <td style="padding: 4px 0;"><strong><?php _e('Traffic Trend:', 'almaseo'); ?></strong></td>
                <td style="padding: 4px 0; text-align: right;">
                    <?php 
                    $trend_color = $trend < 0 ? '#d63638' : '#00a32a';
                    echo '<span style="color: ' . $trend_color . '; font-weight: bold;">';
                    echo $trend >= 0 ? '+' : '';
                    echo esc_html($trend) . '%';
                    echo '</span>';
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if ($scoring['metrics']['seasonal']): ?>
            <tr>
                <td style="padding: 4px 0;"><strong><?php _e('Seasonal:', 'almaseo'); ?></strong></td>
                <td style="padding: 4px 0; text-align: right;">
                    <span style="color: #dba617;">📅 <?php _e('Yes', 'almaseo'); ?></span>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <!-- Reasons -->
    <?php if (!empty($scoring['reasons'])): ?>
    <div style="margin-bottom: 15px;">
        <h4 style="margin-bottom: 10px;"><?php _e('Analysis', 'almaseo'); ?></h4>
        <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
            <?php foreach ($scoring['reasons'] as $reason): ?>
            <li><?php echo esc_html($reason); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Actions -->
    <div style="border-top: 1px solid #dcdcde; padding-top: 15px;">
        <h4 style="margin-bottom: 10px;"><?php _e('Actions', 'almaseo'); ?></h4>
        
        <!-- Pin as Evergreen -->
        <p>
            <label style="display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" name="almaseo_eg_pinned" value="1" 
                       <?php checked($notes['pinned']); ?>>
                <span><?php _e('📌 Pin as Evergreen (override)', 'almaseo'); ?></span>
            </label>
        </p>
        
        <!-- Mark as Refreshed -->
        <p>
            <button type="button" class="button button-secondary" id="almaseo-eg-mark-refreshed"
                    data-post-id="<?php echo esc_attr($post->ID); ?>"
                    style="width: 100%;">
                <?php _e('✅ Mark as Refreshed', 'almaseo'); ?>
            </button>
        </p>
        
        <!-- Analyze Now -->
        <p>
            <button type="button" class="button" id="almaseo-eg-analyze-now"
                    data-post-id="<?php echo esc_attr($post->ID); ?>"
                    style="width: 100%;">
                <?php _e('🔄 Analyze Now', 'almaseo'); ?>
            </button>
        </p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Mark as refreshed
        $('#almaseo-eg-mark-refreshed').on('click', function() {
            var $btn = $(this);
            var postId = $btn.data('post-id');
            
            $btn.prop('disabled', true).text('<?php _e('Updating...', 'almaseo'); ?>');
            
            $.post(ajaxurl, {
                action: 'almaseo_eg_mark_refreshed',
                post_id: postId,
                nonce: '<?php echo wp_create_nonce('almaseo_eg_ajax'); ?>'
            }, function(response) {
                if (response.success) {
                    $btn.text('<?php _e('✅ Marked as Refreshed!', 'almaseo'); ?>');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    $btn.prop('disabled', false).text('<?php _e('✅ Mark as Refreshed', 'almaseo'); ?>');
                    alert(response.data || '<?php _e('Error updating status', 'almaseo'); ?>');
                }
            });
        });
        
        // Analyze now handler moved to evergreen.js for better integration
    });
    </script>
    <?php
}

/**
 * Save meta box data
 */
function almaseo_eg_save_meta_box($post_id) {
    // Check nonce
    if (!isset($_POST['almaseo_eg_meta_box_nonce']) || 
        !wp_verify_nonce($_POST['almaseo_eg_meta_box_nonce'], 'almaseo_eg_meta_box')) {
        return;
    }
    
    // Check autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Check permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Save pinned status
    $pinned = isset($_POST['almaseo_eg_pinned']) ? true : false;
    almaseo_eg_set_pinned($post_id, $pinned);
}

/**
 * AJAX handler for mark refreshed
 */
function almaseo_eg_ajax_mark_refreshed() {
    check_ajax_referer('almaseo_eg_ajax', 'nonce');
    
    $post_id = intval($_POST['post_id']);
    
    if (!current_user_can('edit_post', $post_id)) {
        wp_send_json_error(__('Permission denied', 'almaseo'));
    }
    
    $result = almaseo_eg_mark_refreshed($post_id);
    
    if ($result) {
        wp_send_json_success(__('Post marked as refreshed', 'almaseo'));
    } else {
        wp_send_json_error(__('Failed to update status', 'almaseo'));
    }
}

/**
 * AJAX handler for analyze post
 */
function almaseo_eg_ajax_analyze_post() {
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
        
        // Update notes if seasonal detected
        if (!empty($result['metrics']['seasonal'])) {
            $notes = almaseo_eg_get_notes($post_id);
            $notes['seasonal'] = true;
            almaseo_eg_set_notes($post_id, $notes);
        }
        
        wp_send_json_success(__('Analysis complete', 'almaseo'));
    } else {
        wp_send_json_error(__('Failed to analyze post', 'almaseo'));
    }
}

/**
 * Initialize metabox hooks
 */
function almaseo_eg_init_metabox() {
    add_action('add_meta_boxes', 'almaseo_eg_add_meta_box');
    add_action('save_post', 'almaseo_eg_save_meta_box');
    add_action('wp_ajax_almaseo_eg_mark_refreshed', 'almaseo_eg_ajax_mark_refreshed');
    add_action('wp_ajax_almaseo_eg_analyze_post', 'almaseo_eg_ajax_analyze_post');
}
add_action('admin_init', 'almaseo_eg_init_metabox');