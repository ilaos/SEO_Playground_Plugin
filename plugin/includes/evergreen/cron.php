<?php
/**
 * AlmaSEO Evergreen Cron Jobs
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Evergreen Cron Class
 */
class AlmaSEO_Evergreen_Cron {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Schedule events
        add_action('init', array($this, 'schedule_events'));
        
        // Hook into cron events
        add_action('almaseo_eg_weekly_recalculation', array($this, 'run_weekly_recalculation'));
        add_action('almaseo_eg_daily_snapshot', array($this, 'take_daily_snapshot'));
        add_action('almaseo_eg_refresh_weeklies', array($this, 'refresh_weekly_caches'));
    }
    
    /**
     * Schedule cron events
     */
    public function schedule_events() {
        // Weekly recalculation
        if (!wp_next_scheduled('almaseo_eg_weekly_recalculation')) {
            wp_schedule_event(time(), 'weekly', 'almaseo_eg_weekly_recalculation');
        }
        
        // Daily snapshot
        if (!wp_next_scheduled('almaseo_eg_daily_snapshot')) {
            wp_schedule_event(time(), 'daily', 'almaseo_eg_daily_snapshot');
        }
        
        // Daily cache refresh for weekly snapshots
        if (!wp_next_scheduled('almaseo_eg_refresh_weeklies')) {
            wp_schedule_event(time() + HOUR_IN_SECONDS, 'daily', 'almaseo_eg_refresh_weeklies');
        }
    }
    
    /**
     * Run weekly recalculation
     */
    public function run_weekly_recalculation() {
        // Check if feature is enabled
        if (!get_option('almaseo_eg_enabled', true)) {
            return;
        }
        
        // Get all published posts
        $posts = get_posts(array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'numberposts' => -1,
            'fields' => 'ids'
        ));
        
        // Load scoring functions
        if (!function_exists('almaseo_eg_calculate_single_post')) {
            $plugin_dir = plugin_dir_path(dirname(dirname(__FILE__)));
            if (file_exists($plugin_dir . 'includes/evergreen/scoring.php')) {
                require_once $plugin_dir . 'includes/evergreen/scoring.php';
            } else {
                error_log('[AlmaSEO] Evergreen: scoring.php not found for cron job');
                return;
            }
        }
        
        $processed = 0;
        $errors = 0;
        
        foreach ($posts as $post_id) {
            try {
                if (function_exists('almaseo_eg_calculate_single_post')) {
                    almaseo_eg_calculate_single_post($post_id);
                    $processed++;
                }
            } catch (Exception $e) {
                error_log('[AlmaSEO] Evergreen cron error for post ' . $post_id . ': ' . $e->getMessage());
                $errors++;
            }
            
            // Prevent timeout
            if ($processed % 50 === 0) {
                set_time_limit(30);
            }
        }
        
        // Log completion
        update_option('almaseo_eg_last_cron_run', array(
            'time' => time(),
            'processed' => $processed,
            'errors' => $errors
        ));
        
        // Take snapshot after recalculation
        $this->take_weekly_snapshot();
    }
    
    /**
     * Take daily snapshot for tracking
     */
    public function take_daily_snapshot() {
        // Get current stats
        if (!function_exists('almaseo_eg_get_dashboard_stats')) {
            return;
        }
        
        $stats = almaseo_eg_get_dashboard_stats();
        
        // Get existing snapshots
        $snapshots = get_option('almaseo_eg_daily_snapshots', array());
        
        // Add new snapshot
        $snapshots[] = array(
            'timestamp' => time(),
            'date' => current_time('Y-m-d'),
            'evergreen' => $stats['evergreen'],
            'watch' => $stats['watch'],
            'stale' => $stats['stale'],
            'total' => $stats['total']
        );
        
        // Keep only last 90 days
        if (count($snapshots) > 90) {
            $snapshots = array_slice($snapshots, -90);
        }
        
        update_option('almaseo_eg_daily_snapshots', $snapshots);
    }
    
    /**
     * Take weekly snapshot
     */
    private function take_weekly_snapshot() {
        // Get current stats
        if (!function_exists('almaseo_eg_get_dashboard_stats')) {
            return;
        }
        
        $stats = almaseo_eg_get_dashboard_stats();
        
        // Get existing snapshots
        $snapshots = get_option('almaseo_eg_weekly_snapshots', array());
        
        // Add new snapshot
        $snapshots[] = array(
            'timestamp' => time(),
            'date' => current_time('Y-m-d'),
            'evergreen' => $stats['evergreen'],
            'watch' => $stats['watch'],
            'stale' => $stats['stale'],
            'total' => $stats['total']
        );
        
        // Keep only last 12 weeks
        if (count($snapshots) > 12) {
            $snapshots = array_slice($snapshots, -12);
        }
        
        update_option('almaseo_eg_weekly_snapshots', $snapshots);
    }
    
    /**
     * Refresh weekly snapshot caches
     */
    public function refresh_weekly_caches() {
        // Load the cached function if not available
        if (!function_exists('almaseo_eg_get_weekly_snapshots_cached')) {
            $plugin_dir = plugin_dir_path(dirname(dirname(__FILE__)));
            if (file_exists($plugin_dir . 'includes/evergreen/functions.php')) {
                require_once $plugin_dir . 'includes/evergreen/functions.php';
            }
        }
        
        // Refresh all cache ranges
        foreach (array(4, 8, 12) as $weeks) {
            // Delete old cache
            delete_transient("almaseo_eg_weekly_{$weeks}");
            
            // Regenerate cache
            if (function_exists('almaseo_eg_get_weekly_snapshots_cached')) {
                almaseo_eg_get_weekly_snapshots_cached($weeks);
            }
        }
        
        // Log the refresh
        update_option('almaseo_eg_last_cache_refresh', time());
    }
    
    /**
     * Clear all scheduled events (for deactivation)
     */
    public static function clear_scheduled_events() {
        $events = array(
            'almaseo_eg_weekly_recalculation',
            'almaseo_eg_daily_snapshot',
            'almaseo_eg_refresh_weeklies'
        );
        
        foreach ($events as $event) {
            $timestamp = wp_next_scheduled($event);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $event);
            }
        }
    }
}

// Initialize only if constants are defined
if (defined('ALMASEO_EG_STATUS_EVERGREEN')) {
    AlmaSEO_Evergreen_Cron::get_instance();
}