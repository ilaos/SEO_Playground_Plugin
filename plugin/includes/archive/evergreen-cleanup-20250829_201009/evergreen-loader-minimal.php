<?php
/**
 * AlmaSEO Evergreen Feature - Minimal Loader (Memory Optimized)
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Minimal Evergreen Loader - Loads only essential components
 */
class AlmaSEO_Evergreen_Loader_Minimal {
    
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
        // Only load in admin
        if (!is_admin() && !wp_doing_ajax()) {
            return;
        }
        
        // Load only core files initially
        $this->load_core_dependencies();
        
        // Conditionally load other components
        add_action('admin_init', array($this, 'load_admin_components'), 5);
        add_action('wp_ajax_almaseo_eg_mark_refreshed', array($this, 'load_ajax_handlers'), 5);
        add_action('wp_ajax_almaseo_eg_analyze_post', array($this, 'load_ajax_handlers'), 5);
    }
    
    /**
     * Load only essential files
     */
    private function load_core_dependencies() {
        // Constants are always needed
        require_once dirname(__FILE__) . '/constants.php';
        
        // Meta functions are commonly used
        require_once dirname(__FILE__) . '/meta.php';
    }
    
    /**
     * Load admin components when needed
     */
    public function load_admin_components() {
        global $pagenow;
        
        // Get current screen if available
        $screen = function_exists('get_current_screen') ? get_current_screen() : null;
        
        // Determine what to load based on context
        $is_post_list = ($pagenow === 'edit.php');
        $is_post_edit = in_array($pagenow, array('post.php', 'post-new.php'));
        $is_dashboard = ($pagenow === 'index.php');
        $is_evergreen_page = isset($_GET['page']) && strpos($_GET['page'], 'almaseo-evergreen') !== false;
        
        // Load scoring if we need it
        if ($is_post_list || $is_post_edit || $is_evergreen_page) {
            require_once dirname(__FILE__) . '/scoring.php';
        }
        
        // Load columns for post list
        if ($is_post_list) {
            require_once dirname(__FILE__) . '/columns.php';
        }
        
        // Load metabox for post edit
        if ($is_post_edit) {
            require_once dirname(__FILE__) . '/metabox.php';
        }
        
        // Load widget for dashboard
        if ($is_dashboard) {
            require_once dirname(__FILE__) . '/widget.php';
        }
        
        // Load settings/dashboard pages
        if ($is_evergreen_page) {
            require_once dirname(__FILE__) . '/admin.php';
            
            if ($_GET['page'] === 'almaseo-evergreen-dashboard') {
                require_once dirname(__FILE__) . '/dashboard.php';
                require_once dirname(__FILE__) . '/export.php';
            }
        }
        
        // Load UI components if needed
        if ($is_post_list || $is_evergreen_page) {
            require_once dirname(__FILE__) . '/evergreen-ui.php';
        }
        
        // Schedule cron if needed
        if (!wp_next_scheduled(ALMASEO_EG_CRON_EVENT)) {
            require_once dirname(__FILE__) . '/scheduler.php';
            almaseo_eg_schedule_cron();
        }
        
        // Enqueue assets if on relevant pages
        if ($is_post_list || $is_post_edit || $is_dashboard || $is_evergreen_page) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        }
    }
    
    /**
     * Load AJAX handlers on demand
     */
    public function load_ajax_handlers() {
        // Load dependencies for AJAX operations
        if (!function_exists('almaseo_score_evergreen')) {
            require_once dirname(__FILE__) . '/scoring.php';
        }
        
        // Load the metabox file which contains AJAX handlers
        if (!function_exists('almaseo_eg_ajax_mark_refreshed')) {
            require_once dirname(__FILE__) . '/metabox.php';
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        global $post_type;
        
        // Determine if we should load assets
        $should_load = false;
        
        if ($hook === 'edit.php' && in_array($post_type, array('post', 'page'))) {
            $should_load = true;
        }
        
        if (in_array($hook, array('post.php', 'post-new.php', 'index.php'))) {
            $should_load = true;
        }
        
        if (strpos($hook, 'almaseo-evergreen') !== false) {
            $should_load = true;
        }
        
        if (!$should_load) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'almaseo-evergreen',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/evergreen.css',
            array(),
            '1.5.0'
        );
        
        // Enqueue JS
        wp_enqueue_script(
            'almaseo-evergreen',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/evergreen.js',
            array('jquery'),
            '1.5.0',
            true
        );
        
        // Localize script
        wp_localize_script('almaseo-evergreen', 'almaseoEvergreen', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('almaseo_eg_ajax'),
            'i18n' => array(
                'updating' => __('Updating...', 'almaseo'),
                'refreshed' => __('Refreshed!', 'almaseo'),
                'error' => __('Error', 'almaseo'),
                'analyzing' => __('Analyzing...', 'almaseo'),
                'analyze_now' => __('Analyze Now', 'almaseo')
            )
        ));
    }
    
    /**
     * Activation hook
     */
    public static function activate() {
        // Load constants first
        require_once dirname(__FILE__) . '/constants.php';
        
        // Create default settings
        $defaults = array(
            'watch_days' => 180,
            'stale_days' => 365,
            'watch_traffic_drop' => 20,
            'stale_traffic_drop' => 40,
            'decline_pct' => -30,
            'grace_days' => 90,
            'enable_digest' => false
        );
        
        if (!get_option(ALMASEO_EG_SETTINGS_OPTION)) {
            add_option(ALMASEO_EG_SETTINGS_OPTION, $defaults);
        }
    }
    
    /**
     * Deactivation hook
     */
    public static function deactivate() {
        // Load constants first
        require_once dirname(__FILE__) . '/constants.php';
        
        // Remove scheduled cron
        $timestamp = wp_next_scheduled(ALMASEO_EG_CRON_EVENT);
        if ($timestamp) {
            wp_unschedule_event($timestamp, ALMASEO_EG_CRON_EVENT);
        }
    }
}

// Initialize on plugins_loaded with proper priority
add_action('plugins_loaded', function() {
    if (class_exists('AlmaSEO_Evergreen_Loader_Minimal')) {
        AlmaSEO_Evergreen_Loader_Minimal::get_instance();
    }
}, 20);

// Register activation/deactivation hooks if plugin file is defined
if (defined('ALMASEO_PLUGIN_FILE')) {
    register_activation_hook(ALMASEO_PLUGIN_FILE, array('AlmaSEO_Evergreen_Loader_Minimal', 'activate'));
    register_deactivation_hook(ALMASEO_PLUGIN_FILE, array('AlmaSEO_Evergreen_Loader_Minimal', 'deactivate'));
}