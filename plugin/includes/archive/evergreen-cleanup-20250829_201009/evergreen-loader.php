<?php
/**
 * AlmaSEO Evergreen Feature - Main Loader
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize Evergreen feature
 */
class AlmaSEO_Evergreen_Loader {
    
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
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Load required files
     */
    private function load_dependencies() {
        // Core files
        require_once dirname(__FILE__) . '/constants.php';
        require_once dirname(__FILE__) . '/meta.php';
        require_once dirname(__FILE__) . '/scoring.php';
        require_once dirname(__FILE__) . '/scheduler.php';
        require_once dirname(__FILE__) . '/gsc-integration.php';
        
        // Admin only files
        if (is_admin()) {
            require_once dirname(__FILE__) . '/admin.php';
            require_once dirname(__FILE__) . '/columns.php';
            require_once dirname(__FILE__) . '/metabox.php';
            require_once dirname(__FILE__) . '/widget.php';
            require_once dirname(__FILE__) . '/evergreen-ui.php';
            require_once dirname(__FILE__) . '/dashboard.php';
            require_once dirname(__FILE__) . '/export.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Activation/Deactivation - only register if constant is defined
        if (defined('ALMASEO_PLUGIN_FILE')) {
            register_activation_hook(ALMASEO_PLUGIN_FILE, array($this, 'activate'));
            register_deactivation_hook(ALMASEO_PLUGIN_FILE, array($this, 'deactivate'));
        }
        
        // Enqueue scripts and styles
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        
        // Initialize cron
        add_action('init', array($this, 'maybe_schedule_cron'));
        
        // Add capabilities
        add_action('admin_init', array($this, 'add_capabilities'));
    }
    
    /**
     * Activation hook
     */
    public function activate() {
        // Schedule cron
        almaseo_eg_schedule_cron();
        
        // Create default settings
        $defaults = array(
            'watch_days' => ALMASEO_EG_DEFAULT_WATCH_DAYS,
            'stale_days' => ALMASEO_EG_DEFAULT_STALE_DAYS,
            'decline_pct' => ALMASEO_EG_DEFAULT_DECLINE_PCT,
            'grace_days' => ALMASEO_EG_DEFAULT_GRACE_DAYS,
            'enable_digest' => false
        );
        
        if (!get_option(ALMASEO_EG_SETTINGS_OPTION)) {
            add_option(ALMASEO_EG_SETTINGS_OPTION, $defaults);
        }
        
        // Process initial batch of posts
        if (!get_option('almaseo_eg_initial_scan')) {
            $this->run_initial_scan();
        }
    }
    
    /**
     * Deactivation hook
     */
    public function deactivate() {
        // Unschedule cron
        almaseo_eg_unschedule_cron();
    }
    
    /**
     * Maybe schedule cron
     */
    public function maybe_schedule_cron() {
        if (!wp_next_scheduled(ALMASEO_EG_CRON_EVENT)) {
            almaseo_eg_schedule_cron();
        }
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        global $post_type;
        
        // Only load on post/page list screens
        $load_on_list = ($hook === 'edit.php' && in_array($post_type, array('post', 'page')));
        
        // Also load on other relevant admin pages
        $evergreen_pages = array(
            'post.php', // Edit post
            'post-new.php', // New post
            'index.php', // Dashboard
            'seo-playground_page_almaseo-evergreen', // Evergreen page
            'seo-playground_page_almaseo-evergreen-dashboard' // Evergreen Dashboard
        );
        
        $load_on_page = in_array($hook, $evergreen_pages);
        
        // Check if on dashboard page specifically
        $is_dashboard_page = ($hook === 'seo-playground_page_almaseo-evergreen-dashboard');
        
        // Bail if not on a relevant page
        if (!$load_on_list && !$load_on_page) {
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
        
        // Also load tooltips if they exist
        if (file_exists(dirname(dirname(dirname(__FILE__))) . '/assets/js/evergreen-tooltips.js')) {
            wp_enqueue_script(
                'almaseo-evergreen-tooltips',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/evergreen-tooltips.js',
                array('jquery'),
                '1.5.0',
                true
            );
        }
        
        // Dashboard-specific assets
        if ($is_dashboard_page) {
            wp_enqueue_style(
                'almaseo-evergreen-dashboard',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/evergreen-dashboard.css',
                array('almaseo-evergreen'),
                '1.5.0'
            );
            
            wp_enqueue_script(
                'almaseo-evergreen-dashboard',
                plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/evergreen-dashboard.js',
                array('jquery', 'almaseo-evergreen'),
                '1.5.0',
                true
            );
        }
        
        // Localize script
        wp_localize_script('almaseo-evergreen', 'almaseoEvergreen', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('almaseo_eg_ajax'),
            'evergreenUrl' => admin_url('admin.php?page=almaseo-evergreen'),
            'i18n' => array(
                'updating' => __('Updating...', 'almaseo'),
                'refreshed' => __('Refreshed!', 'almaseo'),
                'error' => __('Error', 'almaseo'),
                'failed' => __('Failed', 'almaseo'),
                'evergreen' => __('Evergreen', 'almaseo'),
                'watch' => __('Watch', 'almaseo'),
                'stale' => __('Stale', 'almaseo'),
                'refreshed_tooltip' => __('Just refreshed • Trend: 0%', 'almaseo'),
                'last_recalculated_just_now' => __('Last recalculated: just now', 'almaseo'),
                'analyzing' => __('Analyzing...', 'almaseo'),
                'analyze_now' => __('Analyze Now', 'almaseo'),
                'analysis_complete' => __('Analysis Complete!', 'almaseo'),
                'error_analyzing' => __('Error analyzing post', 'almaseo'),
                'network_error' => __('Network error occurred', 'almaseo'),
                'no_chart_data' => __('No data available for chart', 'almaseo'),
                'applying' => __('Applying...', 'almaseo'),
                'analyze' => __('Analyze', 'almaseo')
            )
        ));
    }
    
    /**
     * Add capabilities
     */
    public function add_capabilities() {
        $role = get_role('administrator');
        if ($role) {
            $role->add_cap('manage_evergreen_content');
        }
        
        $role = get_role('editor');
        if ($role) {
            $role->add_cap('manage_evergreen_content');
        }
    }
    
    /**
     * Run initial scan of posts
     */
    private function run_initial_scan() {
        // Process first 50 posts
        $result = almaseo_eg_process_posts_batch(50, 1);
        
        // Mark as completed
        update_option('almaseo_eg_initial_scan', time());
        
        // Log result
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf(
                '[AlmaSEO Evergreen] Initial scan completed: %d posts processed',
                $result['processed']
            ));
        }
    }
    
    /**
     * Check if Evergreen is enabled
     */
    public static function is_enabled() {
        // Could add a setting to disable the feature
        return apply_filters('almaseo_evergreen_enabled', true);
    }
    
    /**
     * Get supported post types
     */
    public static function get_supported_post_types() {
        return apply_filters('almaseo_evergreen_post_types', array('post', 'page'));
    }
}

// Initialize the loader - ensure we're ready
add_action('plugins_loaded', function() {
    // Only initialize if we're in admin or doing AJAX
    if (!is_admin() && !wp_doing_ajax()) {
        return;
    }
    
    if (class_exists('AlmaSEO_Evergreen_Loader')) {
        AlmaSEO_Evergreen_Loader::get_instance();
    }
}, 20);