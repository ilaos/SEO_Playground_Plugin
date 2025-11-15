<?php
/**
 * AlmaSEO 404 Tracker - Model (CRUD Operations)
 * 
 * @package AlmaSEO
 * @subpackage 404Tracker
 * @since 6.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AlmaSEO_404_Model {
    
    /**
     * Get 404 logs with filters
     */
    public static function get_logs($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'search' => '',
            'ignored' => null,
            'from' => '',
            'to' => '',
            'page' => 1,
            'per_page' => 20,
            'orderby' => 'last_seen',
            'order' => 'DESC'
        );
        
        $args = wp_parse_args($args, $defaults);
        $table = $wpdb->prefix . 'almaseo_404_log';
        
        // Build WHERE clause
        $where = array('1=1');
        $prepare_args = array();
        
        // Search filter
        if (!empty($args['search'])) {
            $search = '%' . $wpdb->esc_like($args['search']) . '%';
            $where[] = "(path LIKE %s OR referrer LIKE %s OR user_agent LIKE %s)";
            $prepare_args[] = $search;
            $prepare_args[] = $search;
            $prepare_args[] = $search;
        }
        
        // Ignored filter
        if ($args['ignored'] !== null) {
            $where[] = "is_ignored = %d";
            $prepare_args[] = $args['ignored'] ? 1 : 0;
        }
        
        // Date range filters
        if (!empty($args['from'])) {
            $where[] = "last_seen >= %s";
            $prepare_args[] = $args['from'] . ' 00:00:00';
        }
        
        if (!empty($args['to'])) {
            $where[] = "last_seen <= %s";
            $prepare_args[] = $args['to'] . ' 23:59:59';
        }
        
        $where_clause = implode(' AND ', $where);
        
        // Get total count
        $count_query = "SELECT COUNT(*) FROM $table WHERE $where_clause";
        if (!empty($prepare_args)) {
            $count_query = $wpdb->prepare($count_query, $prepare_args);
        }
        $total = $wpdb->get_var($count_query);
        
        // Build main query
        $orderby = in_array($args['orderby'], ['path', 'hits', 'first_seen', 'last_seen']) ? $args['orderby'] : 'last_seen';
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        $offset = ($args['page'] - 1) * $args['per_page'];
        
        $query = "SELECT * FROM $table WHERE $where_clause ORDER BY $orderby $order LIMIT %d OFFSET %d";
        $prepare_args[] = $args['per_page'];
        $prepare_args[] = $offset;
        
        if (!empty($prepare_args)) {
            $query = $wpdb->prepare($query, $prepare_args);
        }
        
        $items = $wpdb->get_results($query, ARRAY_A);
        
        // Process items
        foreach ($items as &$item) {
            // Unpack IP if present
            if ($item['ip']) {
                $item['ip_display'] = @inet_ntop($item['ip']);
            } else {
                $item['ip_display'] = null;
            }
            
            // Parse referrer domain
            if ($item['referrer']) {
                $parsed = parse_url($item['referrer']);
                $item['referrer_domain'] = isset($parsed['host']) ? $parsed['host'] : '';
            } else {
                $item['referrer_domain'] = '';
            }
            
            // Truncate user agent for display
            if ($item['user_agent'] && strlen($item['user_agent']) > 100) {
                $item['user_agent_display'] = substr($item['user_agent'], 0, 100) . '...';
            } else {
                $item['user_agent_display'] = $item['user_agent'];
            }
        }
        
        return array(
            'items' => $items,
            'total' => $total,
            'pages' => ceil($total / $args['per_page'])
        );
    }
    
    /**
     * Get single log entry
     */
    public static function get_log($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'almaseo_404_log';
        $log = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE id = %d",
            $id
        ), ARRAY_A);
        
        if ($log && $log['ip']) {
            $log['ip_display'] = @inet_ntop($log['ip']);
        }
        
        return $log;
    }
    
    /**
     * Toggle ignored status
     */
    public static function toggle_ignored($id, $ignored = true) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'almaseo_404_log';
        $result = $wpdb->update(
            $table,
            array('is_ignored' => $ignored ? 1 : 0),
            array('id' => $id),
            array('%d'),
            array('%d')
        );
        
        // Clear cache
        delete_transient('almaseo_404_stats');
        
        return $result !== false;
    }
    
    /**
     * Delete log entry
     */
    public static function delete_log($id) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'almaseo_404_log';
        $result = $wpdb->delete(
            $table,
            array('id' => $id),
            array('%d')
        );
        
        // Clear cache
        delete_transient('almaseo_404_stats');
        delete_transient('almaseo_404_top_referrer');
        
        return $result !== false;
    }
    
    /**
     * Bulk update ignored status
     */
    public static function bulk_toggle_ignored($ids, $ignored = true) {
        global $wpdb;
        
        if (empty($ids) || !is_array($ids)) {
            return false;
        }
        
        $table = $wpdb->prefix . 'almaseo_404_log';
        $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));
        
        $query = $wpdb->prepare(
            "UPDATE $table SET is_ignored = %d WHERE id IN ($ids_placeholder)",
            array_merge(array($ignored ? 1 : 0), $ids)
        );
        
        $result = $wpdb->query($query);
        
        // Clear cache
        delete_transient('almaseo_404_stats');
        
        return $result !== false;
    }
    
    /**
     * Bulk delete logs
     */
    public static function bulk_delete($ids) {
        global $wpdb;
        
        if (empty($ids) || !is_array($ids)) {
            return false;
        }
        
        $table = $wpdb->prefix . 'almaseo_404_log';
        $ids_placeholder = implode(',', array_fill(0, count($ids), '%d'));
        
        $query = $wpdb->prepare(
            "DELETE FROM $table WHERE id IN ($ids_placeholder)",
            $ids
        );
        
        $result = $wpdb->query($query);
        
        // Clear cache
        delete_transient('almaseo_404_stats');
        delete_transient('almaseo_404_top_referrer');
        
        return $result !== false;
    }
    
    /**
     * Get statistics
     */
    public static function get_stats() {
        // Check cache
        $stats = get_transient('almaseo_404_stats');
        if ($stats !== false) {
            return $stats;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_404_log';
        
        // Calculate date ranges
        $today_start = current_time('Y-m-d') . ' 00:00:00';
        $seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days', current_time('timestamp')));
        
        // Get stats
        $stats = array();
        
        // Total 404s last 7 days (not ignored)
        $stats['total_7d'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(hits) FROM $table WHERE last_seen >= %s AND is_ignored = 0",
            $seven_days_ago
        )) ?: 0;
        
        // Unique paths last 7 days (not ignored)
        $stats['unique_7d'] = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(DISTINCT path) FROM $table WHERE last_seen >= %s AND is_ignored = 0",
            $seven_days_ago
        )) ?: 0;
        
        // Today's 404s (not ignored)
        $stats['today'] = $wpdb->get_var($wpdb->prepare(
            "SELECT SUM(hits) FROM $table WHERE last_seen >= %s AND is_ignored = 0",
            $today_start
        )) ?: 0;
        
        // Total ignored
        $stats['ignored'] = $wpdb->get_var(
            "SELECT COUNT(*) FROM $table WHERE is_ignored = 1"
        ) ?: 0;
        
        // Cache for 1 hour
        set_transient('almaseo_404_stats', $stats, HOUR_IN_SECONDS);
        
        return $stats;
    }
    
    /**
     * Get top referrer domain
     */
    public static function get_top_referrer() {
        // Check cache
        $top_referrer = get_transient('almaseo_404_top_referrer');
        if ($top_referrer !== false) {
            return $top_referrer;
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_404_log';
        
        $seven_days_ago = date('Y-m-d H:i:s', strtotime('-7 days', current_time('timestamp')));
        
        // Get all referrers from last 7 days
        $referrers = $wpdb->get_col($wpdb->prepare(
            "SELECT referrer FROM $table WHERE last_seen >= %s AND is_ignored = 0 AND referrer IS NOT NULL AND referrer != ''",
            $seven_days_ago
        ));
        
        if (empty($referrers)) {
            $top_referrer = 'None';
        } else {
            // Count domains
            $domains = array();
            foreach ($referrers as $referrer) {
                $parsed = parse_url($referrer);
                if (isset($parsed['host'])) {
                    $domain = $parsed['host'];
                    if (!isset($domains[$domain])) {
                        $domains[$domain] = 0;
                    }
                    $domains[$domain]++;
                }
            }
            
            if (empty($domains)) {
                $top_referrer = 'None';
            } else {
                // Get top domain
                arsort($domains);
                $top_referrer = key($domains);
            }
        }
        
        // Cache for 1 hour
        set_transient('almaseo_404_top_referrer', $top_referrer, HOUR_IN_SECONDS);
        
        return $top_referrer;
    }
    
    /**
     * Prepare data for redirect creation
     */
    public static function prepare_redirect_data($id) {
        $log = self::get_log($id);
        
        if (!$log) {
            return false;
        }
        
        // Build source path
        $source = $log['path'];
        if (!empty($log['query'])) {
            $source .= '?' . $log['query'];
        }
        
        return array(
            'source' => $source,
            'target' => '', // Admin will fill this
            'status' => 301,
            'note' => sprintf(
                __('Created from 404 log: %d hits, last seen %s', 'almaseo'),
                $log['hits'],
                $log['last_seen']
            )
        );
    }
}