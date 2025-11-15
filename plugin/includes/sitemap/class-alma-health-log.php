<?php
/**
 * AlmaSEO Health Log
 * 
 * Lightweight logging system for sitemap events
 * 
 * @package AlmaSEO
 * @since 4.12.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Health_Log {
    
    const LOG_OPTION = 'almaseo_sitemap_logs';
    const MAX_ENTRIES = 50;
    
    /**
     * Log an event
     * 
     * @param string $type Event type (build, validate, indexnow, news_refresh, etc)
     * @param string $message Event message
     * @param array $data Additional data
     */
    public static function log($type, $message, $data = array()) {
        $logs = get_option(self::LOG_OPTION, array());
        
        // Add new entry
        $entry = array(
            'timestamp' => time(),
            'type' => $type,
            'message' => $message,
            'data' => $data,
            'user' => get_current_user_id()
        );
        
        // Prepend to array (newest first)
        array_unshift($logs, $entry);
        
        // Trim to max entries
        $logs = array_slice($logs, 0, self::MAX_ENTRIES);
        
        // Save
        update_option(self::LOG_OPTION, $logs, false);
        
        /**
         * Action after logging an event
         * 
         * @since 4.12.0
         * @param array $entry Log entry
         */
        do_action('almaseo_sitemap_log_entry', $entry);
    }
    
    /**
     * Get logs
     * 
     * @param string $type Filter by type (optional)
     * @param int $limit Number of entries to return
     * @return array
     */
    public static function get_logs($type = '', $limit = 50) {
        $logs = get_option(self::LOG_OPTION, array());
        
        if (!empty($type)) {
            $logs = array_filter($logs, function($log) use ($type) {
                return $log['type'] === $type;
            });
        }
        
        return array_slice($logs, 0, $limit);
    }
    
    /**
     * Clear logs
     */
    public static function clear_logs() {
        delete_option(self::LOG_OPTION);
        self::log('system', __('Logs cleared', 'almaseo'));
    }
    
    /**
     * Export logs as CSV
     * 
     * @return string CSV content
     */
    public static function export_csv() {
        $logs = self::get_logs();
        
        if (empty($logs)) {
            return '';
        }
        
        // CSV headers
        $csv = "Timestamp,Type,Message,User,Data\n";
        
        foreach ($logs as $log) {
            $user = $log['user'] ? get_userdata($log['user']) : null;
            $username = $user ? $user->user_login : 'System';
            $data_json = !empty($log['data']) ? json_encode($log['data']) : '';
            
            $csv .= sprintf(
                '"%s","%s","%s","%s","%s"' . "\n",
                gmdate('Y-m-d H:i:s', $log['timestamp']),
                esc_attr($log['type']),
                esc_attr($log['message']),
                esc_attr($username),
                esc_attr($data_json)
            );
        }
        
        return $csv;
    }
    
    /**
     * Log build event
     */
    public static function log_build($provider, $urls, $duration_ms) {
        self::log('build', sprintf(
            __('Built %s sitemap: %d URLs in %dms', 'almaseo'),
            $provider,
            $urls,
            $duration_ms
        ), array(
            'provider' => $provider,
            'urls' => $urls,
            'duration_ms' => $duration_ms
        ));
    }
    
    /**
     * Log validation event
     */
    public static function log_validation($type, $ok, $issues = 0) {
        $message = $ok ? 
            sprintf(__('%s validation passed', 'almaseo'), ucfirst($type)) :
            sprintf(__('%s validation failed: %d issues', 'almaseo'), ucfirst($type), $issues);
        
        self::log('validate', $message, array(
            'type' => $type,
            'ok' => $ok,
            'issues' => $issues
        ));
    }
    
    /**
     * Log IndexNow submission
     */
    public static function log_indexnow($urls_count, $success) {
        $message = $success ?
            sprintf(__('IndexNow: Submitted %d URLs', 'almaseo'), $urls_count) :
            sprintf(__('IndexNow: Failed to submit %d URLs', 'almaseo'), $urls_count);
        
        self::log('indexnow', $message, array(
            'urls' => $urls_count,
            'success' => $success
        ));
    }
    
    /**
     * Log news refresh
     */
    public static function log_news_refresh($items, $duration_ms) {
        self::log('news_refresh', sprintf(
            __('News sitemap refreshed: %d items in %dms', 'almaseo'),
            $items,
            $duration_ms
        ), array(
            'items' => $items,
            'duration_ms' => $duration_ms
        ));
    }
    
    /**
     * Log error
     */
    public static function log_error($context, $error_message) {
        self::log('error', sprintf(
            __('Error in %s: %s', 'almaseo'),
            $context,
            $error_message
        ), array(
            'context' => $context,
            'error' => $error_message
        ));
    }
    
    /**
     * Get log statistics
     */
    public static function get_stats() {
        $logs = self::get_logs();
        $stats = array(
            'total' => count($logs),
            'by_type' => array(),
            'last_24h' => 0,
            'last_week' => 0
        );
        
        $now = time();
        $day_ago = $now - 86400;
        $week_ago = $now - 604800;
        
        foreach ($logs as $log) {
            // Count by type
            if (!isset($stats['by_type'][$log['type']])) {
                $stats['by_type'][$log['type']] = 0;
            }
            $stats['by_type'][$log['type']]++;
            
            // Count recent
            if ($log['timestamp'] > $day_ago) {
                $stats['last_24h']++;
            }
            if ($log['timestamp'] > $week_ago) {
                $stats['last_week']++;
            }
        }
        
        return $stats;
    }
}