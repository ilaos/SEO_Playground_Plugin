<?php
/**
 * AlmaSEO Redirects Model - CRUD Operations
 * 
 * @package AlmaSEO
 * @subpackage Redirects
 * @since 6.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AlmaSEO_Redirects_Model {
    
    /**
     * Get table name
     */
    private static function get_table_name() {
        global $wpdb;
        return $wpdb->prefix . 'almaseo_redirects';
    }

    /**
     * HTTP status codes this module supports. Kept in sync with the import
     * redirects mapper (which writes 307/308/410/451 into the same table) and
     * the runtime handler.
     *
     * @return int[]
     */
    public static function allowed_statuses() {
        return array( 301, 302, 307, 308, 410, 451 );
    }

    /**
     * Whether a status is a "gone" code that has no redirect target.
     *
     * @param int $status
     * @return bool
     */
    public static function is_gone_status( $status ) {
        return in_array( (int) $status, array( 410, 451 ), true );
    }

    /**
     * Coerce an arbitrary status into a supported code (defaults to 301).
     *
     * @param mixed $status
     * @return int
     */
    public static function sanitize_status( $status ) {
        $status = (int) $status;
        return in_array( $status, self::allowed_statuses(), true ) ? $status : 301;
    }
    
    /**
     * Get all redirects with optional filters
     * 
     * @param array $args Query arguments
     * @return array
     */
    public static function get_redirects($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'per_page' => 20,
            'page' => 1,
            'orderby' => 'created_at',
            'order' => 'DESC',
            'search' => '',
            'is_enabled' => null
        );
        
        $args = wp_parse_args($args, $defaults);
        $table = self::get_table_name();
        
        // Build WHERE clause
        $where = array('1=1');
        $prepare_values = array();
        
        if (!empty($args['search'])) {
            $where[] = "(source LIKE %s OR target LIKE %s)";
            $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
            $prepare_values[] = $search_term;
            $prepare_values[] = $search_term;
        }
        
        if ($args['is_enabled'] !== null) {
            $where[] = "is_enabled = %d";
            $prepare_values[] = intval($args['is_enabled']);
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Get total count
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $count_query = "SELECT COUNT(*) FROM $table WHERE $where_clause";
        if (!empty($prepare_values)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- dynamically built with safe placeholders
            $count_query = $wpdb->prepare($count_query, $prepare_values);
        }
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared above
        $total = $wpdb->get_var($count_query);
        
        // Build main query
        $offset = ($args['page'] - 1) * $args['per_page'];

        // Whitelist orderby column and direction to prevent SQL injection.
        // These must match the actual table columns.
        $allowed_orderby = array('id', 'source', 'target', 'status', 'hits', 'last_hit', 'created_at', 'updated_at', 'is_enabled');
        $allowed_order = array('ASC', 'DESC');
        $orderby_col = in_array($args['orderby'], $allowed_orderby, true) ? $args['orderby'] : 'created_at';
        $order_dir = in_array(strtoupper($args['order']), $allowed_order, true) ? strtoupper($args['order']) : 'DESC';
        $orderby = "$orderby_col $order_dir";

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $query = "SELECT * FROM $table WHERE $where_clause ORDER BY $orderby LIMIT %d OFFSET %d";

        // Add pagination values to prepare array
        $prepare_values[] = intval($args['per_page']);
        $prepare_values[] = intval($offset);

        if (!empty($prepare_values)) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- dynamically built with safe placeholders
            $query = $wpdb->prepare($query, $prepare_values);
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared above
        $results = $wpdb->get_results($query, ARRAY_A);
        
        return array(
            'items' => $results,
            'total' => $total,
            'pages' => ceil($total / $args['per_page'])
        );
    }
    
    /**
     * Get a single redirect by ID
     * 
     * @param int $id
     * @return array|null
     */
    public static function get_redirect($id) {
        global $wpdb;
        
        $table = self::get_table_name();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $query = $wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id);

        return $wpdb->get_row($query, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query prepared on the line above
    }
    
    /**
     * Get redirect by source path
     * 
     * @param string $source
     * @return array|null
     */
    public static function get_redirect_by_source($source) {
        global $wpdb;
        
        $table = self::get_table_name();
        $source = self::normalize_path($source);
        
        $query = $wpdb->prepare(
            "SELECT * FROM $table WHERE source = %s AND is_enabled = 1", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name derived from $wpdb->prefix, not user input
            $source
        );

        return $wpdb->get_row($query, ARRAY_A); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- $query prepared above
    }
    
    /**
     * Create a new redirect
     * 
     * @param array $data
     * @return int|false Insert ID or false on failure
     */
    public static function create_redirect($data) {
        global $wpdb;
        
        $table = self::get_table_name();

        // Normalize and validate
        $source = self::normalize_path($data['source']);
        $status = self::sanitize_status(isset($data['status']) ? $data['status'] : 301);

        if (!$source) {
            return false;
        }

        // "Gone" codes (410/451) have no meaningful target; everything else
        // requires a valid target URL or path.
        if (self::is_gone_status($status)) {
            $target = isset($data['target']) && trim($data['target']) !== ''
                ? ( self::validate_target($data['target']) ?: '' )
                : '';
        } else {
            $target = self::validate_target(isset($data['target']) ? $data['target'] : '');
            if (!$target) {
                return false;
            }
        }

        // Check for duplicate source
        if (self::source_exists($source)) {
            return false;
        }

        $insert_data = array(
            'source' => $source,
            'target' => $target,
            'status' => $status,
            'is_enabled' => isset($data['is_enabled']) ? intval($data['is_enabled']) : 1,
            'hits' => 0,
            'last_hit' => null,
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );
        
        $result = $wpdb->insert(
            $table,
            $insert_data,
            array('%s', '%s', '%d', '%d', '%d', '%s', '%s', '%s')
        );
        
        if ($result) {
            self::clear_cache();
            return $wpdb->insert_id;
        }
        
        return false;
    }
    
    /**
     * Update a redirect
     * 
     * @param int $id
     * @param array $data
     * @return bool
     */
    public static function update_redirect($id, $data) {
        global $wpdb;
        
        $table = self::get_table_name();
        $update_data = array();
        $format = array();
        
        // Handle source update
        if (isset($data['source'])) {
            $source = self::normalize_path($data['source']);
            if (!$source) {
                return false;
            }
            
            // Check for duplicate (excluding current record)
            if (self::source_exists($source, $id)) {
                return false;
            }
            
            $update_data['source'] = $source;
            $format[] = '%s';
        }
        
        // Determine the resulting status (provided value, or the existing one)
        // so target validation knows whether a target is required.
        $effective_status = null;
        if (isset($data['status'])) {
            $effective_status = self::sanitize_status($data['status']);
        }

        // Handle target update
        if (isset($data['target'])) {
            $target_raw = trim($data['target']);
            $is_gone = ($effective_status !== null)
                ? self::is_gone_status($effective_status)
                : self::is_gone_status(self::current_status($id));

            if ($target_raw === '' && $is_gone) {
                // Clearing the target is allowed for 410/451.
                $update_data['target'] = '';
                $format[] = '%s';
            } else {
                $target = self::validate_target($data['target']);
                if (!$target) {
                    return false;
                }
                $update_data['target'] = $target;
                $format[] = '%s';
            }
        }

        // Handle status update
        if ($effective_status !== null) {
            $update_data['status'] = $effective_status;
            $format[] = '%d';
        }
        
        // Handle enabled status
        if (isset($data['is_enabled'])) {
            $update_data['is_enabled'] = intval($data['is_enabled']);
            $format[] = '%d';
        }
        
        // Always update the updated_at timestamp
        $update_data['updated_at'] = current_time('mysql');
        $format[] = '%s';
        
        $result = $wpdb->update(
            $table,
            $update_data,
            array('id' => $id),
            $format,
            array('%d')
        );
        
        if ($result !== false) {
            self::clear_cache();
            return true;
        }
        
        return false;
    }
    
    /**
     * Delete a redirect
     * 
     * @param int $id
     * @return bool
     */
    public static function delete_redirect($id) {
        global $wpdb;
        
        $table = self::get_table_name();
        $result = $wpdb->delete($table, array('id' => $id), array('%d'));
        
        if ($result) {
            self::clear_cache();
            return true;
        }
        
        return false;
    }
    
    /**
     * Toggle redirect enabled status
     * 
     * @param int $id
     * @return bool
     */
    public static function toggle_redirect($id) {
        global $wpdb;
        
        $redirect = self::get_redirect($id);
        if (!$redirect) {
            return false;
        }
        
        $new_status = $redirect['is_enabled'] ? 0 : 1;
        
        return self::update_redirect($id, array('is_enabled' => $new_status));
    }
    
    /**
     * Record a hit for a redirect
     * 
     * @param int $id
     * @return bool
     */
    public static function record_hit($id) {
        global $wpdb;
        
        $table = self::get_table_name();
        
        $query = $wpdb->prepare(
            "UPDATE $table SET hits = hits + 1, last_hit = %s WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name derived from $wpdb->prefix, not user input
            current_time('mysql'),
            $id
        );
        
        return $wpdb->query($query) !== false; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared above
    }
    
    /**
     * Get the stored status code for a redirect (used during partial updates).
     *
     * @param int $id
     * @return int
     */
    private static function current_status($id) {
        global $wpdb;
        $table = self::get_table_name();
        $status = $wpdb->get_var($wpdb->prepare(
            "SELECT status FROM $table WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name derived from $wpdb->prefix, not user input
            $id
        ));
        return (int) $status;
    }

    /**
     * Aggregate statistics for the dashboard panel. Computed in SQL so the
     * totals are accurate regardless of how many redirects exist (the old
     * approach fetched rows client-side and silently capped/failed).
     *
     * @return array
     */
    public static function get_stats() {
        global $wpdb;
        $table = self::get_table_name();

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $total      = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table");
        $active     = (int) $wpdb->get_var("SELECT COUNT(*) FROM $table WHERE is_enabled = 1");
        $total_hits = (int) $wpdb->get_var("SELECT COALESCE(SUM(hits), 0) FROM $table");
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        $today_start = current_time('Y-m-d') . ' 00:00:00';
        $today = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM $table WHERE last_hit >= %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name derived from $wpdb->prefix, not user input
            $today_start
        ));

        return array(
            'total'      => $total,
            'active'     => $active,
            'total_hits' => $total_hits,
            'today'      => $today,
        );
    }

    /**
     * Get all enabled redirects (for caching)
     *
     * @return array
     */
    public static function get_enabled_redirects() {
        global $wpdb;
        
        $table = self::get_table_name();
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $query = "SELECT id, source, target, status FROM $table WHERE is_enabled = 1 LIMIT 5000";

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- no user input, table name from $wpdb->prefix
        $results = $wpdb->get_results($query, ARRAY_A);
        
        // Index by source for quick lookup
        $indexed = array();
        foreach ($results as $redirect) {
            $indexed[$redirect['source']] = $redirect;
        }
        
        return $indexed;
    }
    
    /**
     * Check if a source path already exists
     * 
     * @param string $source
     * @param int $exclude_id Optional ID to exclude from check
     * @return bool
     */
    private static function source_exists($source, $exclude_id = null) {
        global $wpdb;
        
        $table = self::get_table_name();
        $source = self::normalize_path($source);
        
        if ($exclude_id) {
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE source = %s AND id != %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name derived from $wpdb->prefix, not user input
                $source,
                $exclude_id
            );
        } else {
            $query = $wpdb->prepare(
                "SELECT COUNT(*) FROM $table WHERE source = %s", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name derived from $wpdb->prefix, not user input
                $source
            );
        }
        
        return $wpdb->get_var($query) > 0; // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared above
    }
    
    /**
     * Normalize a path
     * 
     * @param string $path
     * @return string|false
     */
    public static function normalize_path($path) {
        // Remove any protocol and domain
        $path = preg_replace('#^https?://[^/]+#', '', $path);
        
        // Ensure leading slash
        if (substr($path, 0, 1) !== '/') {
            $path = '/' . $path;
        }
        
        // Remove double slashes
        $path = preg_replace('#/+#', '/', $path);
        
        // Remove trailing slash unless it's the root
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }
        
        // Validate it's a valid path
        if (!preg_match('#^/[^<>"\s]*$#', $path)) {
            return false;
        }
        
        return $path;
    }
    
    /**
     * Validate a target URL or path
     * 
     * @param string $target
     * @return string|false
     */
    private static function validate_target($target) {
        // Check if it's an absolute URL
        if (filter_var($target, FILTER_VALIDATE_URL)) {
            return esc_url_raw($target);
        }
        
        // Otherwise treat as relative path
        $normalized = self::normalize_path($target);
        if ($normalized) {
            return $normalized;
        }
        
        return false;
    }
    
    /**
     * Clear the redirects cache
     */
    private static function clear_cache() {
        delete_transient('almaseo_enabled_redirects');
    }
}