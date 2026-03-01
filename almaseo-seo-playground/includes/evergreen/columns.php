<?php
/**
 * AlmaSEO Evergreen Feature - Post List Columns
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add Evergreen column to post list
 */
function almaseo_eg_add_columns($columns) {
    $new_columns = array();
    
    foreach ($columns as $key => $value) {
        $new_columns[$key] = $value;
        
        // Add after title column
        if ($key === 'title') {
            $new_columns['evergreen'] = __('Evergreen', 'almaseo');
        }
    }
    
    return $new_columns;
}

/**
 * Display Evergreen column content
 */
function almaseo_eg_column_content($column_name, $post_id) {
    if ($column_name !== 'evergreen') {
        return;
    }
    
    $status = almaseo_eg_get_status($post_id);
    $ages = almaseo_get_post_ages($post_id);
    $clicks = almaseo_eg_get_clicks($post_id);
    $trend = almaseo_compute_trend($clicks['clicks_90d'], $clicks['clicks_prev90d']);
    $notes = almaseo_eg_get_notes($post_id);
    
    // Build tooltip text
    $tooltip_parts = array();
    $tooltip_parts[] = sprintf(__('Last updated: %d days ago', 'almaseo'), $ages['updated_days']);
    
    if ($trend != 0) {
        $tooltip_parts[] = sprintf(__('Trend: %+.1f%%', 'almaseo'), $trend);
    }
    
    if ($notes['seasonal']) {
        $tooltip_parts[] = __('Seasonal: Yes', 'almaseo');
    }
    
    if ($notes['pinned']) {
        $tooltip_parts[] = __('Pinned', 'almaseo');
    }
    
    $tooltip = implode(' • ', $tooltip_parts);
    
    // Display pill badge with data attribute for tooltip
    switch ($status) {
        case ALMASEO_EG_STATUS_EVERGREEN:
            echo '<span class="almaseo-eg-pill almaseo-eg-evergreen" data-eg-tooltip="' . esc_attr($tooltip) . '" tabindex="0">';
            echo '🟢 ' . __('Evergreen', 'almaseo');
            if ($notes['pinned']) {
                echo ' 📌';
            }
            echo '</span>';
            break;
            
        case ALMASEO_EG_STATUS_WATCH:
            echo '<span class="almaseo-eg-pill almaseo-eg-watch" data-eg-tooltip="' . esc_attr($tooltip) . '" tabindex="0">';
            echo '🟡 ' . __('Watch', 'almaseo');
            echo '</span>';
            break;
            
        case ALMASEO_EG_STATUS_STALE:
            echo '<span class="almaseo-eg-pill almaseo-eg-stale" data-eg-tooltip="' . esc_attr($tooltip) . '" tabindex="0">';
            echo '🔴 ' . __('Stale', 'almaseo');
            echo '</span>';
            break;
            
        default:
            $default_tooltip = __('Not analyzed yet', 'almaseo');
            echo '<span class="almaseo-eg-pill almaseo-eg-unknown" data-eg-tooltip="' . esc_attr($default_tooltip) . '" tabindex="0">';
            echo '⚪ ' . __('Unknown', 'almaseo');
            echo '</span>';
            break;
    }
}

/**
 * Make Evergreen column sortable
 */
function almaseo_eg_sortable_columns($columns) {
    $columns['evergreen'] = 'evergreen_status';
    return $columns;
}

/**
 * Handle sorting by Evergreen status
 */
function almaseo_eg_column_orderby($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    if ($query->get('orderby') === 'evergreen_status') {
        // Set up meta query to include posts without status
        $meta_query = $query->get('meta_query');
        if (!is_array($meta_query)) {
            $meta_query = array();
        }
        
        // Add relation if not exists
        if (!empty($meta_query) && !isset($meta_query['relation'])) {
            $meta_query['relation'] = 'AND';
        }
        
        $query->set('meta_query', $meta_query);
        
        // Custom order: evergreen first, then watch, then stale, then unanalyzed
        add_filter('posts_orderby', function($orderby) use ($query) {
            global $wpdb;
            
            $order = $query->get('order') === 'desc' ? 'DESC' : 'ASC';
            
            // Join with postmeta to get evergreen status
            add_filter('posts_join', function($join) use ($wpdb) {
                $meta_key = ALMASEO_EG_META_STATUS;
                if (strpos($join, 'eg_sort_meta') === false) {
                    $join .= " LEFT JOIN {$wpdb->postmeta} AS eg_sort_meta 
                              ON ({$wpdb->posts}.ID = eg_sort_meta.post_id 
                              AND eg_sort_meta.meta_key = '{$meta_key}')";
                }
                return $join;
            }, 10, 1);
            
            // Build the ORDER BY clause
            if ($order === 'ASC') {
                // Ascending: Evergreen first, then Watch, then Stale
                $orderby = "
                    CASE 
                        WHEN eg_sort_meta.meta_value = 'evergreen' THEN 1
                        WHEN eg_sort_meta.meta_value = 'watch' THEN 2
                        WHEN eg_sort_meta.meta_value = 'stale' THEN 3
                        WHEN eg_sort_meta.meta_value IS NULL THEN 4
                        ELSE 5
                    END ASC, {$wpdb->posts}.post_modified DESC
                ";
            } else {
                // Descending: Stale first, then Watch, then Evergreen
                $orderby = "
                    CASE 
                        WHEN eg_sort_meta.meta_value = 'stale' THEN 1
                        WHEN eg_sort_meta.meta_value = 'watch' THEN 2
                        WHEN eg_sort_meta.meta_value = 'evergreen' THEN 3
                        WHEN eg_sort_meta.meta_value IS NULL THEN 4
                        ELSE 5
                    END ASC, {$wpdb->posts}.post_modified DESC
                ";
            }
            
            return $orderby;
        }, 10, 1);
    }
}

/**
 * Add filter dropdown
 */
function almaseo_eg_add_filter_dropdown() {
    global $typenow;
    
    if (!in_array($typenow, array('post', 'page'))) {
        return;
    }
    
    $selected = isset($_GET['evergreen_filter']) ? sanitize_text_field($_GET['evergreen_filter']) : '';
    ?>
    <select name="evergreen_filter" id="evergreen_filter">
        <option value=""><?php _e('All Evergreen Status', 'almaseo'); ?></option>
        <option value="evergreen" <?php selected($selected, 'evergreen'); ?>>
            <?php _e('🟢 Evergreen', 'almaseo'); ?>
        </option>
        <option value="watch" <?php selected($selected, 'watch'); ?>>
            <?php _e('🟡 Watch', 'almaseo'); ?>
        </option>
        <option value="stale" <?php selected($selected, 'stale'); ?>>
            <?php _e('🔴 Stale', 'almaseo'); ?>
        </option>
        <option value="old" <?php selected($selected, 'old'); ?>>
            <?php _e('Updated > 6 months', 'almaseo'); ?>
        </option>
    </select>
    <?php
}

/**
 * Apply filter to query
 */
function almaseo_eg_apply_filter($query) {
    if (!is_admin() || !$query->is_main_query()) {
        return;
    }
    
    if (empty($_GET['evergreen_filter'])) {
        return;
    }
    
    $filter = sanitize_text_field($_GET['evergreen_filter']);
    
    switch ($filter) {
        case 'evergreen':
        case 'watch':
        case 'stale':
            $query->set('meta_key', ALMASEO_EG_META_STATUS);
            $query->set('meta_value', $filter);
            break;
            
        case 'old':
            // Posts updated more than 6 months ago
            $six_months_ago = date('Y-m-d H:i:s', strtotime('-6 months'));
            $query->set('date_query', array(
                array(
                    'column' => 'post_modified',
                    'before' => $six_months_ago
                )
            ));
            break;
    }
}

/**
 * Initialize column hooks
 */
function almaseo_eg_init_columns() {
    add_filter('manage_posts_columns', 'almaseo_eg_add_columns');
    add_filter('manage_pages_columns', 'almaseo_eg_add_columns');
    add_action('manage_posts_custom_column', 'almaseo_eg_column_content', 10, 2);
    add_action('manage_pages_custom_column', 'almaseo_eg_column_content', 10, 2);
    add_filter('manage_edit-post_sortable_columns', 'almaseo_eg_sortable_columns');
    add_filter('manage_edit-page_sortable_columns', 'almaseo_eg_sortable_columns');
    add_action('pre_get_posts', 'almaseo_eg_column_orderby');
    add_action('restrict_manage_posts', 'almaseo_eg_add_filter_dropdown');
    add_action('pre_get_posts', 'almaseo_eg_apply_filter');
}
add_action('admin_init', 'almaseo_eg_init_columns');