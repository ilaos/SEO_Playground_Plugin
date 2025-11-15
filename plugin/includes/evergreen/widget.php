<?php
/**
 * AlmaSEO Evergreen Feature - Dashboard Widget
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register dashboard widget
 */
function almaseo_eg_add_dashboard_widget() {
    wp_add_dashboard_widget(
        'almaseo_evergreen_widget',
        __('Evergreen Content Health', 'almaseo'),
        'almaseo_eg_dashboard_widget_content'
    );
}

/**
 * Dashboard widget content
 */
function almaseo_eg_dashboard_widget_content() {
    // Get stats
    $stats = almaseo_eg_get_stats();
    
    // Calculate percentages
    $total = max(1, $stats['total']); // Avoid division by zero
    $evergreen_pct = round(($stats['evergreen'] / $total) * 100, 1);
    $watch_pct = round(($stats['watch'] / $total) * 100, 1);
    $stale_pct = round(($stats['stale'] / $total) * 100, 1);
    
    ?>
    <!-- Status Overview -->
    <div style="margin-bottom: 20px;">
        <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
            <div style="text-align: center; flex: 1;">
                <div style="font-size: 24px; font-weight: bold; color: #00a32a;">
                    <?php echo esc_html($stats['evergreen']); ?>
                </div>
                <div style="font-size: 12px; color: #666;">
                    🟢 <?php _e('Evergreen', 'almaseo'); ?>
                </div>
                <div style="font-size: 11px; color: #999;">
                    <?php echo esc_html($evergreen_pct); ?>%
                </div>
            </div>
            
            <div style="text-align: center; flex: 1; border-left: 1px solid #dcdcde; border-right: 1px solid #dcdcde;">
                <div style="font-size: 24px; font-weight: bold; color: #dba617;">
                    <?php echo esc_html($stats['watch']); ?>
                </div>
                <div style="font-size: 12px; color: #666;">
                    🟡 <?php _e('Watch', 'almaseo'); ?>
                </div>
                <div style="font-size: 11px; color: #999;">
                    <?php echo esc_html($watch_pct); ?>%
                </div>
            </div>
            
            <div style="text-align: center; flex: 1;">
                <div style="font-size: 24px; font-weight: bold; color: #d63638;">
                    <?php echo esc_html($stats['stale']); ?>
                </div>
                <div style="font-size: 12px; color: #666;">
                    🔴 <?php _e('Stale', 'almaseo'); ?>
                </div>
                <div style="font-size: 11px; color: #999;">
                    <?php echo esc_html($stale_pct); ?>%
                </div>
            </div>
        </div>
        
        <!-- Progress Bar -->
        <div style="height: 20px; background: #f0f0f1; border-radius: 10px; overflow: hidden; display: flex;">
            <?php if ($stats['evergreen'] > 0): ?>
            <div style="width: <?php echo esc_attr($evergreen_pct); ?>%; background: #00a32a; transition: width 0.3s;"></div>
            <?php endif; ?>
            
            <?php if ($stats['watch'] > 0): ?>
            <div style="width: <?php echo esc_attr($watch_pct); ?>%; background: #dba617; transition: width 0.3s;"></div>
            <?php endif; ?>
            
            <?php if ($stats['stale'] > 0): ?>
            <div style="width: <?php echo esc_attr($stale_pct); ?>%; background: #d63638; transition: width 0.3s;"></div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Top Stale Posts -->
    <?php
    $stale_posts = get_posts(array(
        'post_type' => array('post', 'page'),
        'post_status' => 'publish',
        'posts_per_page' => 5,
        'meta_key' => ALMASEO_EG_META_STATUS,
        'meta_value' => ALMASEO_EG_STATUS_STALE,
        'orderby' => 'modified',
        'order' => 'ASC'
    ));
    
    if ($stale_posts):
    ?>
    <div style="border-top: 1px solid #dcdcde; padding-top: 15px;">
        <h4 style="margin: 0 0 10px 0; font-size: 13px; font-weight: 600;">
            <?php _e('Top 5 Posts Needing Refresh', 'almaseo'); ?>
        </h4>
        
        <table style="width: 100%; font-size: 12px;">
            <?php foreach ($stale_posts as $post): 
                $ages = almaseo_get_post_ages($post);
            ?>
            <tr>
                <td style="padding: 5px 0;">
                    <a href="<?php echo get_edit_post_link($post->ID); ?>" 
                       style="text-decoration: none; color: #2271b1;">
                        <?php echo esc_html(wp_trim_words($post->post_title, 6)); ?>
                    </a>
                </td>
                <td style="padding: 5px 0; text-align: right; color: #666;">
                    <small><?php echo sprintf(__('%dd ago', 'almaseo'), $ages['updated_days']); ?></small>
                </td>
                <td style="padding: 5px 0; text-align: right;">
                    <button class="button button-small almaseo-eg-widget-refresh" 
                            data-post-id="<?php echo esc_attr($post->ID); ?>"
                            style="font-size: 11px; padding: 0 8px; height: 22px; line-height: 20px;">
                        <?php _e('Refresh', 'almaseo'); ?>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php else: ?>
    <div style="border-top: 1px solid #dcdcde; padding-top: 15px; text-align: center; color: #666;">
        <p style="margin: 0;">
            ✨ <?php _e('All content is fresh!', 'almaseo'); ?>
        </p>
    </div>
    <?php endif; ?>
    
    <!-- Actions -->
    <div style="border-top: 1px solid #dcdcde; margin-top: 15px; padding-top: 15px; display: flex; gap: 10px;">
        <a href="<?php echo admin_url('admin.php?page=almaseo-evergreen'); ?>" 
           class="button button-primary" style="flex: 1; text-align: center;">
            <?php _e('View All', 'almaseo'); ?>
        </a>
        
        <a href="<?php echo admin_url('edit.php?evergreen_filter=stale'); ?>" 
           class="button" style="flex: 1; text-align: center;">
            <?php _e('View Stale', 'almaseo'); ?>
        </a>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Handle refresh button clicks
        $('.almaseo-eg-widget-refresh').on('click', function(e) {
            e.preventDefault();
            
            var $btn = $(this);
            var postId = $btn.data('post-id');
            
            $btn.prop('disabled', true).text('...');
            
            $.post(ajaxurl, {
                action: 'almaseo_eg_mark_refreshed',
                post_id: postId,
                nonce: '<?php echo wp_create_nonce('almaseo_eg_ajax'); ?>'
            }, function(response) {
                if (response.success) {
                    $btn.text('✓');
                    $btn.closest('tr').fadeOut();
                } else {
                    $btn.prop('disabled', false).text('<?php _e('Refresh', 'almaseo'); ?>');
                }
            });
        });
    });
    </script>
    <?php
}

/**
 * Initialize widget hooks
 */
function almaseo_eg_init_widget() {
    add_action('wp_dashboard_setup', 'almaseo_eg_add_dashboard_widget');
}
add_action('admin_init', 'almaseo_eg_init_widget');