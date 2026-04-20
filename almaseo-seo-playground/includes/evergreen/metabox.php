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
        __('Evergreen Content Health', 'almaseo-seo-playground'),
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
            echo '🟢 ' . __('Evergreen', 'almaseo-seo-playground');
            echo '</span>';
            break;
            
        case ALMASEO_EG_STATUS_WATCH:
            echo '<span style="display: inline-block; padding: 8px 16px; background: #fff3cd; color: #856404; border-radius: 20px; font-weight: bold;">';
            echo '🟡 ' . __('Watch', 'almaseo-seo-playground');
            echo '</span>';
            break;
            
        case ALMASEO_EG_STATUS_STALE:
            echo '<span style="display: inline-block; padding: 8px 16px; background: #f8d7da; color: #721c24; border-radius: 20px; font-weight: bold;">';
            echo '🔴 ' . __('Stale', 'almaseo-seo-playground');
            echo '</span>';
            break;
            
        default:
            echo '<span style="display: inline-block; padding: 8px 16px; background: #e2e3e5; color: #383d41; border-radius: 20px; font-weight: bold;">';
            echo '⚪ ' . __('Not Analyzed', 'almaseo-seo-playground');
            echo '</span>';
            break;
    }
    echo '</div>';
    
    // Display last recalculated timestamp
    if ($last_checked) {
        $checked_time = strtotime($last_checked);
        $time_ago = human_time_diff($checked_time, current_time('U'));
        ?>
        <div style="text-align: center; margin-bottom: 15px;">
            <span style="font-size: 12px; color: #666;" 
                  title="<?php esc_attr_e('This shows when Evergreen status was last recalculated.', 'almaseo-seo-playground'); ?>"
                  id="almaseo-eg-last-checked">
                <?php
                /* translators: %s: human-readable time difference (e.g. "2 hours") */
                echo sprintf(__('Last recalculated: %s ago', 'almaseo-seo-playground'), $time_ago); ?>
            </span>
        </div>
        <?php
    } else {
        ?>
        <div style="text-align: center; margin-bottom: 15px;">
            <span style="font-size: 12px; color: #999;" 
                  title="<?php esc_attr_e('This shows when Evergreen status was last recalculated.', 'almaseo-seo-playground'); ?>"
                  id="almaseo-eg-last-checked">
                <?php esc_html_e('Not yet analyzed', 'almaseo-seo-playground'); ?>
            </span>
        </div>
        <?php
    }
    
    // Display metrics
    ?>
    <div style="margin-bottom: 15px;">
        <h4 style="margin-bottom: 10px;"><?php esc_html_e('Metrics', 'almaseo-seo-playground'); ?></h4>
        
        <table style="width: 100%; font-size: 13px;">
            <tr>
                <td style="padding: 4px 0;"><strong><?php esc_html_e('Published:', 'almaseo-seo-playground'); ?></strong></td>
                <td style="padding: 4px 0; text-align: right;">
                    <?php
                    /* translators: %d: number of days */
                    echo sprintf(_n('%d day ago', '%d days ago', $ages['published_days'], 'almaseo-seo-playground'), $ages['published_days']); ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 4px 0;"><strong><?php esc_html_e('Updated:', 'almaseo-seo-playground'); ?></strong></td>
                <td style="padding: 4px 0; text-align: right;">
                    <?php
                    /* translators: %d: number of days */
                    echo sprintf(_n('%d day ago', '%d days ago', $ages['updated_days'], 'almaseo-seo-playground'), $ages['updated_days']); ?>
                </td>
            </tr>
            <?php if ($clicks['clicks_90d'] > 0 || $clicks['clicks_prev90d'] > 0): ?>
            <tr>
                <td style="padding: 4px 0;"><strong><?php esc_html_e('Traffic Trend:', 'almaseo-seo-playground'); ?></strong></td>
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
                <td style="padding: 4px 0;"><strong><?php esc_html_e('Seasonal:', 'almaseo-seo-playground'); ?></strong></td>
                <td style="padding: 4px 0; text-align: right;">
                    <span style="color: #dba617;">📅 <?php esc_html_e('Yes', 'almaseo-seo-playground'); ?></span>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    
    <!-- Reasons -->
    <?php if (!empty($scoring['reasons'])): ?>
    <div style="margin-bottom: 15px;">
        <h4 style="margin-bottom: 10px;"><?php esc_html_e('Analysis', 'almaseo-seo-playground'); ?></h4>
        <ul style="margin: 0; padding-left: 20px; font-size: 13px;">
            <?php foreach ($scoring['reasons'] as $reason): ?>
            <li><?php echo esc_html($reason); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
    
    <!-- Actions -->
    <div style="border-top: 1px solid #dcdcde; padding-top: 15px;">
        <h4 style="margin-bottom: 10px;"><?php esc_html_e('Actions', 'almaseo-seo-playground'); ?></h4>
        
        <!-- Pin as Evergreen -->
        <p>
            <label style="display: flex; align-items: center; gap: 5px;">
                <input type="checkbox" name="almaseo_eg_pinned" value="1" 
                       <?php checked($notes['pinned']); ?>>
                <span><?php esc_html_e('📌 Pin as Evergreen (override)', 'almaseo-seo-playground'); ?></span>
            </label>
        </p>
        
        <!-- Mark as Refreshed -->
        <p>
            <button type="button" class="button button-secondary" id="almaseo-eg-mark-refreshed"
                    data-post-id="<?php echo esc_attr($post->ID); ?>"
                    style="width: 100%;">
                <?php esc_html_e('✅ Mark as Refreshed', 'almaseo-seo-playground'); ?>
            </button>
        </p>
        
        <!-- Analyze Now -->
        <p>
            <button type="button" class="button" id="almaseo-eg-analyze-now"
                    data-post-id="<?php echo esc_attr($post->ID); ?>"
                    style="width: 100%;">
                <?php esc_html_e('🔄 Analyze Now', 'almaseo-seo-playground'); ?>
            </button>
        </p>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Mark as refreshed
        $('#almaseo-eg-mark-refreshed').on('click', function() {
            var $btn = $(this);
            var postId = $btn.data('post-id');
            
            $btn.prop('disabled', true).text('<?php esc_html_e('Updating...', 'almaseo-seo-playground'); ?>');
            
            $.post(ajaxurl, {
                action: 'almaseo_eg_mark_refreshed',
                post_id: postId,
                nonce: '<?php echo wp_create_nonce('almaseo_eg_ajax'); ?>'
            }, function(response) {
                if (response.success) {
                    $btn.text('<?php esc_html_e('✅ Marked as Refreshed!', 'almaseo-seo-playground'); ?>');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    $btn.prop('disabled', false).text('<?php esc_html_e('✅ Mark as Refreshed', 'almaseo-seo-playground'); ?>');
                    alert(response.data || '<?php esc_html_e('Error updating status', 'almaseo-seo-playground'); ?>');
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

    // Auto-recalculate evergreen status when post is saved
    // This ensures the status updates based on the new post_modified date
    if (function_exists('almaseo_eg_calculate_single_post')) {
        almaseo_eg_calculate_single_post($post_id);
    }
}

/**
 * AJAX handler for mark refreshed
 */
function almaseo_eg_ajax_mark_refreshed() {
    check_ajax_referer('almaseo_eg_ajax', 'nonce');

    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;

    if (!$post_id || !current_user_can('edit_post', $post_id)) {
        wp_send_json_error(__('Permission denied', 'almaseo-seo-playground'));
    }
    
    $result = almaseo_eg_mark_refreshed($post_id);
    
    if ($result) {
        wp_send_json_success(__('Post marked as refreshed', 'almaseo-seo-playground'));
    } else {
        wp_send_json_error(__('Failed to update status', 'almaseo-seo-playground'));
    }
}

/**
 * AJAX handler for analyze post
 */
function almaseo_eg_ajax_analyze_post() {
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
        
        // Update notes if seasonal detected
        if (!empty($result['metrics']['seasonal'])) {
            $notes = almaseo_eg_get_notes($post_id);
            $notes['seasonal'] = true;
            almaseo_eg_set_notes($post_id, $notes);
        }
        
        wp_send_json_success(__('Analysis complete', 'almaseo-seo-playground'));
    } else {
        wp_send_json_error(__('Failed to analyze post', 'almaseo-seo-playground'));
    }
}

/**
 * Auto-recalculate evergreen status on any post save
 * This runs on ALL post saves, not just when the meta box is present
 */
function almaseo_eg_auto_recalculate_on_save($post_id, $post, $update) {
    // Skip if this is not an update (new posts get grace period anyway)
    if (!$update) {
        return;
    }

    // Skip autosaves and revisions
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }

    if (wp_is_post_revision($post_id)) {
        return;
    }

    // Only process published posts
    if ($post->post_status !== 'publish') {
        return;
    }

    // Only process posts and pages (you can add more post types if needed)
    $allowed_types = array('post', 'page');
    if (!in_array($post->post_type, $allowed_types)) {
        return;
    }

    // Recalculate evergreen status based on new modified date
    if (function_exists('almaseo_eg_calculate_single_post')) {
        almaseo_eg_calculate_single_post($post_id);
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

    // Auto-recalculate on any post save (priority 20 runs after default save actions)
    add_action('save_post', 'almaseo_eg_auto_recalculate_on_save', 20, 3);
}
add_action('admin_init', 'almaseo_eg_init_metabox');