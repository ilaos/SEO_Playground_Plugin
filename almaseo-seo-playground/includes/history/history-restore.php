<?php
/**
 * AlmaSEO Metadata History - Restore Functions
 * 
 * @package AlmaSEO
 * @subpackage History
 * @since 6.8.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Restore a specific version
 */
function almaseo_history_restore_version($post_id, $version_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . ALMASEO_HISTORY_TABLE;
    
    // Get the snapshot to restore
    $snapshot = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND post_id = %d",
        $version_id, $post_id
    ));
    
    if (!$snapshot) {
        return false;
    }
    
    // Decode snapshot data
    $fields = json_decode($snapshot->snapshot_json, true);
    
    if (!$fields) {
        return false;
    }
    
    // Set flag to prevent capture loop
    define('ALMASEO_RESTORING_HISTORY', true);
    
    // Restore each field with correct meta keys
    foreach ($fields as $key => $value) {
        // Map to correct meta keys
        if ($key === 'seo_title') {
            $meta_key = '_almaseo_title';
        } elseif ($key === 'seo_description') {
            $meta_key = '_almaseo_description';
        } elseif ($key === 'focus_keyword') {
            $meta_key = '_almaseo_focus_keyword';
        } elseif ($key === 'schema_json') {
            $meta_key = '_almaseo_schema_json';
        } else {
            $meta_key = '_almaseo_' . $key;
        }
        
        if (empty($value)) {
            delete_post_meta($post_id, $meta_key);
        } else {
            update_post_meta($post_id, $meta_key, $value);
        }
    }
    
    // Remove flag
    if (defined('ALMASEO_RESTORING_HISTORY')) {
        // Can't undefine, but the request will end soon
    }
    
    // Create a new snapshot with source='restore'
    almaseo_history_capture_snapshot($post_id, 'restore');
    
    // Trigger action
    do_action('almaseo_meta_history_restored', $post_id, $snapshot->version);
    
    // Return current values for UI refresh
    return array(
        'success' => true,
        'restored_version' => $snapshot->version,
        'current_fields' => almaseo_history_get_tracked_fields($post_id),
        'message' => sprintf(__('Restored to version %d', 'almaseo'), $snapshot->version)
    );
}

/**
 * Get snapshot by ID
 */
function almaseo_history_get_snapshot($snapshot_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . ALMASEO_HISTORY_TABLE;
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d",
        $snapshot_id
    ));
}

/**
 * Get snapshot by version
 */
function almaseo_history_get_snapshot_by_version($post_id, $version) {
    global $wpdb;
    $table_name = $wpdb->prefix . ALMASEO_HISTORY_TABLE;
    
    return $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE post_id = %d AND version = %d",
        $post_id, $version
    ));
}

/**
 * Delete a snapshot
 */
function almaseo_history_delete_snapshot($snapshot_id, $post_id) {
    global $wpdb;
    $table_name = $wpdb->prefix . ALMASEO_HISTORY_TABLE;
    
    // Verify ownership
    $snapshot = $wpdb->get_row($wpdb->prepare(
        "SELECT * FROM $table_name WHERE id = %d AND post_id = %d",
        $snapshot_id, $post_id
    ));
    
    if (!$snapshot) {
        return false;
    }
    
    // Delete the snapshot
    $deleted = $wpdb->delete(
        $table_name,
        array('id' => $snapshot_id),
        array('%d')
    );
    
    return $deleted > 0;
}

/**
 * Export snapshot as JSON
 */
function almaseo_history_export_snapshot($snapshot_id) {
    $snapshot = almaseo_history_get_snapshot($snapshot_id);
    
    if (!$snapshot) {
        return false;
    }
    
    $data = json_decode($snapshot->snapshot_json, true);
    
    // Add metadata
    $export = array(
        'version' => $snapshot->version,
        'created_at' => $snapshot->created_at,
        'source' => $snapshot->source,
        'fields' => $data,
        'export_date' => gmdate('Y-m-d H:i:s'),
        'post_id' => $snapshot->post_id
    );
    
    return wp_json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}

/**
 * Import snapshot from JSON
 */
function almaseo_history_import_snapshot($post_id, $json_data) {
    $data = json_decode($json_data, true);
    
    if (!$data || !isset($data['fields'])) {
        return false;
    }
    
    // Set flag to prevent capture loop during import
    define('ALMASEO_RESTORING_HISTORY', true);
    
    // Import each field
    foreach ($data['fields'] as $key => $value) {
        $meta_key = '_almaseo_' . $key;
        
        if (empty($value)) {
            delete_post_meta($post_id, $meta_key);
        } else {
            update_post_meta($post_id, $meta_key, $value);
        }
    }
    
    // Create a new snapshot with source='import'
    $version = almaseo_history_capture_snapshot($post_id, 'import');
    
    return array(
        'success' => true,
        'version' => $version,
        'message' => __('Metadata imported successfully', 'almaseo')
    );
}

/**
 * Compare two field values and generate diff
 */
function almaseo_history_generate_diff($old_value, $new_value) {
    if ($old_value === $new_value) {
        return array(
            'type' => 'unchanged',
            'old' => $old_value,
            'new' => $new_value
        );
    }
    
    // For short strings, just show old/new
    if (strlen($old_value) < 100 && strlen($new_value) < 100) {
        return array(
            'type' => 'simple',
            'old' => $old_value,
            'new' => $new_value
        );
    }
    
    // For longer text, try to generate word-level diff
    $old_words = explode(' ', $old_value);
    $new_words = explode(' ', $new_value);
    
    // Simple diff algorithm (could be enhanced with a proper diff library)
    $diff = array(
        'type' => 'word-diff',
        'old' => $old_value,
        'new' => $new_value,
        'changes' => array()
    );
    
    // Mark changed sections (simplified)
    $max_len = max(count($old_words), count($new_words));
    for ($i = 0; $i < $max_len; $i++) {
        $old_word = isset($old_words[$i]) ? $old_words[$i] : '';
        $new_word = isset($new_words[$i]) ? $new_words[$i] : '';
        
        if ($old_word !== $new_word) {
            $diff['changes'][] = array(
                'position' => $i,
                'old' => $old_word,
                'new' => $new_word
            );
        }
    }
    
    return $diff;
}

/**
 * Format schema JSON for display
 */
function almaseo_history_format_schema($schema_json) {
    if (empty($schema_json)) {
        return '';
    }
    
    $decoded = json_decode($schema_json, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        return $schema_json; // Return as-is if not valid JSON
    }
    
    // Pretty print with 2-space indent
    return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
}