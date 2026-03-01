<?php
/**
 * AlmaSEO Metadata History - Capture Functions
 * 
 * @package AlmaSEO
 * @subpackage History
 * @since 6.8.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get tracked fields for a post
 */
function almaseo_history_get_tracked_fields($post_id) {
    $fields = array(
        'seo_title' => get_post_meta($post_id, '_almaseo_title', true),
        'seo_description' => get_post_meta($post_id, '_almaseo_description', true),
        'focus_keyword' => get_post_meta($post_id, '_almaseo_focus_keyword', true),
        'schema_json' => get_post_meta($post_id, '_almaseo_schema_json', true)
    );
    
    // Apply filter for extensibility
    return apply_filters('almaseo_meta_history_tracked_fields', $fields, $post_id);
}

/**
 * Normalize field data for consistent hashing
 */
function almaseo_history_normalize_fields($fields) {
    $normalized = array();
    
    foreach ($fields as $key => $value) {
        if ($key === 'schema_json') {
            // Minify JSON
            if (!empty($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    // Re-encode without spaces
                    $value = wp_json_encode($decoded, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
                } else {
                    // Not valid JSON, just trim
                    $value = trim($value);
                }
            } else {
                $value = '';
            }
        } else {
            // Trim and collapse whitespace
            $value = trim($value);
            $value = preg_replace('/\s+/', ' ', $value);
        }
        
        $normalized[$key] = $value;
    }
    
    // Ensure stable key order
    ksort($normalized);
    
    return $normalized;
}

/**
 * Create snapshot hash
 */
function almaseo_history_create_hash($normalized_fields) {
    $json = wp_json_encode($normalized_fields, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    return sha1($json);
}

/**
 * Get last snapshot for a post
 */
function almaseo_history_get_last_snapshot($post_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . ALMASEO_HISTORY_TABLE;
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name 
        WHERE post_id = %d 
        ORDER BY version DESC 
        LIMIT 1",
        $post_id
    ));
}

/**
 * Get all snapshots for a post
 */
function almaseo_history_get_snapshots($post_id, $limit = 20) {
    global $wpdb;
    $table_name = $wpdb->prefix . ALMASEO_HISTORY_TABLE;
    
    $snapshots = $wpdb->get_results($wpdb->prepare(
        "SELECT * FROM $table_name 
        WHERE post_id = %d 
        ORDER BY version DESC 
        LIMIT %d",
        $post_id,
        $limit
    ));
    
    return $snapshots;
}

/**
 * Capture a snapshot
 */
function almaseo_history_capture_snapshot($post_id, $source = 'auto') {
    // Get current fields
    $fields = almaseo_history_get_tracked_fields($post_id);
    
    // Normalize for comparison
    $normalized = almaseo_history_normalize_fields($fields);
    
    // Create hash
    $hash = almaseo_history_create_hash($normalized);
    
    // Get last snapshot
    $last = almaseo_history_get_last_snapshot($post_id);
    
    // Check if changed
    if ($last && $last->snapshot_hash === $hash) {
        // No changes, skip
        return false;
    }
    
    // Check if should capture (filter)
    $should_capture = apply_filters('almaseo_meta_history_should_capture', true, $post_id, $normalized, $last);
    if (!$should_capture) {
        return false;
    }
    
    // Prepare snapshot data
    $snapshot_json = wp_json_encode($normalized, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $size_bytes = strlen($snapshot_json);
    $version = $last ? ($last->version + 1) : 1;
    
    // Insert new snapshot
    global $wpdb;
    $table_name = $wpdb->prefix . ALMASEO_HISTORY_TABLE;
    
    $inserted = $wpdb->insert(
        $table_name,
        array(
            'post_id' => $post_id,
            'version' => $version,
            'created_at' => gmdate('Y-m-d H:i:s'),
            'user_id' => get_current_user_id() ?: null,
            'source' => $source,
            'snapshot_json' => $snapshot_json,
            'snapshot_hash' => $hash,
            'size_bytes' => $size_bytes
        ),
        array('%d', '%d', '%s', '%d', '%s', '%s', '%s', '%d')
    );
    
    if ($inserted) {
        // Enforce cap
        almaseo_history_enforce_cap($post_id);
        
        // Trigger action
        do_action('almaseo_meta_history_snapshot_created', $post_id, $version, $normalized);
        
        return $version;
    }
    
    return false;
}

/**
 * Enforce version cap per post
 */
function almaseo_history_enforce_cap($post_id) {
    $cap = apply_filters('almaseo_meta_history_cap', 20);
    
    global $wpdb;
    $table_name = $wpdb->prefix . ALMASEO_HISTORY_TABLE;
    
    // Count current versions
    $count = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE post_id = %d",
        $post_id
    ));
    
    if ($count > $cap) {
        // Delete oldest versions
        $to_delete = $count - $cap;
        
        $wpdb->query($wpdb->prepare(
            "DELETE FROM $table_name 
            WHERE post_id = %d 
            ORDER BY version ASC 
            LIMIT %d",
            $post_id,
            $to_delete
        ));
    }
}

/**
 * Get changed fields between two snapshots
 */
function almaseo_history_get_changed_fields($snapshot1, $snapshot2) {
    if (!$snapshot1 || !$snapshot2) {
        return array();
    }
    
    $fields1 = json_decode($snapshot1->snapshot_json, true);
    $fields2 = json_decode($snapshot2->snapshot_json, true);
    
    $changed = array();
    
    foreach ($fields2 as $key => $value) {
        $old_value = isset($fields1[$key]) ? $fields1[$key] : '';
        if ($value !== $old_value) {
            $changed[] = $key;
        }
    }
    
    return $changed;
}

/**
 * Format field name for display
 */
function almaseo_history_format_field_name($field) {
    $names = array(
        'seo_title' => 'Title',
        'seo_description' => 'Description',
        'focus_keyword' => 'Keyword',
        'schema_json' => 'Schema'
    );
    
    return isset($names[$field]) ? $names[$field] : ucfirst(str_replace('_', ' ', $field));
}

/**
 * Get compare data for two versions
 */
function almaseo_history_get_compare_data($post_id, $from_version, $to_version) {
    global $wpdb;
    $table_name = $wpdb->prefix . ALMASEO_HISTORY_TABLE;
    
    // Get both snapshots
    $from = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE post_id = %d AND version = %d",
        $post_id, $from_version
    ));
    
    $to = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE post_id = %d AND version = %d",
        $post_id, $to_version
    ));
    
    if (!$from || !$to) {
        return false;
    }
    
    // Decode snapshots
    $from_data = json_decode($from->snapshot_json, true);
    $to_data = json_decode($to->snapshot_json, true);
    
    // Build compare data
    $compare = array(
        'from' => array(
            'version' => $from->version,
            'created_at' => $from->created_at,
            'user_id' => $from->user_id,
            'source' => $from->source,
            'fields' => $from_data
        ),
        'to' => array(
            'version' => $to->version,
            'created_at' => $to->created_at,
            'user_id' => $to->user_id,
            'source' => $to->source,
            'fields' => $to_data
        ),
        'changes' => almaseo_history_get_changed_fields($from, $to)
    );
    
    return $compare;
}