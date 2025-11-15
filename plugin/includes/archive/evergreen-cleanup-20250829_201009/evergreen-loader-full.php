<?php
/**
 * AlmaSEO Evergreen Feature - Full Loader
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Full Evergreen Loader
 */
class AlmaSEO_Evergreen_Loader {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Plugin directory
     */
    private $plugin_dir;
    
    /**
     * Plugin URL
     */
    private $plugin_url;
    
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
        $this->plugin_dir = plugin_dir_path(dirname(dirname(__FILE__)));
        $this->plugin_url = plugin_dir_url(dirname(dirname(__FILE__)));
        
        // Check if safe to load
        if (!$this->is_safe_to_load()) {
            return;
        }
        
        // Load after WordPress is ready
        add_action('init', array($this, 'init'));
    }
    
    /**
     * Check if safe to load
     */
    private function is_safe_to_load() {
        // Don't load during plugin activation
        if (defined('WP_SANDBOX_SCRAPING') && WP_SANDBOX_SCRAPING) {
            return false;
        }
        
        // Don't load if doing activation
        if (isset($_GET['action']) && $_GET['action'] === 'activate') {
            return false;
        }
        
        // Check available memory
        $memory_limit = $this->get_memory_limit();
        $memory_usage = memory_get_usage(true);
        $available = $memory_limit - $memory_usage;
        
        // Need at least 20MB free
        if ($available < 20 * 1024 * 1024) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Get memory limit in bytes
     */
    private function get_memory_limit() {
        $memory_limit = ini_get('memory_limit');
        if (preg_match('/^(\d+)(.)$/', $memory_limit, $matches)) {
            if ($matches[2] == 'M') {
                return $matches[1] * 1024 * 1024;
            } else if ($matches[2] == 'K') {
                return $matches[1] * 1024;
            } else if ($matches[2] == 'G') {
                return $matches[1] * 1024 * 1024 * 1024;
            }
        }
        return 134217728; // Default 128MB
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Load modules
        $this->load_modules();
        
        // Set up hooks
        $this->setup_hooks();
    }
    
    /**
     * Load modules
     */
    private function load_modules() {
        // Core modules - check existence first
        $constants_file = $this->plugin_dir . 'includes/evergreen/constants.php';
        $functions_file = $this->plugin_dir . 'includes/evergreen/functions.php';
        $scoring_file = $this->plugin_dir . 'includes/evergreen/scoring.php';
        
        if (file_exists($constants_file)) {
            require_once $constants_file;
        }
        
        if (file_exists($functions_file)) {
            require_once $functions_file;
        }
        
        if (file_exists($scoring_file)) {
            require_once $scoring_file;
        }
        
        // Admin modules
        if (is_admin()) {
            $admin_file = $this->plugin_dir . 'includes/evergreen/admin.php';
            $widget_file = $this->plugin_dir . 'includes/evergreen/widget.php';
            $dashboard_file = $this->plugin_dir . 'includes/evergreen/dashboard.php';
            
            if (file_exists($admin_file)) {
                require_once $admin_file;
                AlmaSEO_Evergreen_Admin::get_instance();
            }
            
            if (file_exists($widget_file)) {
                require_once $widget_file;
                AlmaSEO_Evergreen_Widget::get_instance();
            }
            
            if (file_exists($dashboard_file)) {
                require_once $dashboard_file;
                AlmaSEO_Evergreen_Dashboard::get_instance();
            }
        }
        
        // Cron module
        $cron_file = $this->plugin_dir . 'includes/evergreen/cron.php';
        if (file_exists($cron_file)) {
            require_once $cron_file;
            AlmaSEO_Evergreen_Cron::get_instance();
        }
        
        // REST API module
        $rest_file = $this->plugin_dir . 'includes/evergreen/rest-api.php';
        if (file_exists($rest_file)) {
            require_once $rest_file;
            AlmaSEO_Evergreen_REST_API::get_instance();
        }
    }
    
    /**
     * Setup hooks
     */
    private function setup_hooks() {
        // Enqueue editor assets
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
        
        // Admin assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
    }
    
    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        global $post;
        
        // Only load for supported post types
        $supported_post_types = array('post', 'page');
        $post_type = get_post_type();
        
        if (!$post_type || !in_array($post_type, $supported_post_types)) {
            return;
        }
        
        // Register the panel script
        wp_enqueue_script(
            'almaseo-evergreen-panel',
            $this->plugin_url . 'assets/js/evergreen-panel.js',
            array(
                'wp-plugins',
                'wp-edit-post',
                'wp-editor',
                'wp-element',
                'wp-components',
                'wp-data',
                'wp-i18n',
                'wp-api-fetch'
            ),
            '2.2.0',
            true
        );
        
        // Localize settings
        wp_localize_script('almaseo-evergreen-panel', 'almaseoEvergreenSettings', array(
            'supportedPostTypes' => $supported_post_types,
            'apiRoot' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'postId' => $post ? $post->ID : 0,
            'thresholds' => array(
                'watch_days' => get_option('almaseo_eg_watch_days', 180),
                'stale_days' => get_option('almaseo_eg_stale_days', 365),
                'watch_traffic_drop' => get_option('almaseo_eg_watch_traffic_drop', 20),
                'stale_traffic_drop' => get_option('almaseo_eg_stale_traffic_drop', 40)
            )
        ));
        
        // Add inline CSS for the panel
        wp_add_inline_style('wp-edit-post', '
            .almaseo-evergreen-panel .components-panel__body-title {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            .almaseo-evergreen-panel .components-panel__row {
                margin-bottom: 12px;
            }
            .almaseo-evergreen-panel .dashicons {
                vertical-align: middle;
            }
        ');
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        global $post_type;
        
        // Determine if we should load assets
        $should_enqueue = false;
        
        if ($hook === 'edit.php' && in_array($post_type, array('post', 'page'))) {
            $should_enqueue = true;
        }
        
        if (in_array($hook, array('post.php', 'post-new.php', 'index.php'))) {
            $should_enqueue = true;
        }
        
        if (strpos($hook, 'almaseo-evergreen') !== false) {
            $should_enqueue = true;
        }
        
        if (!$should_enqueue) {
            return;
        }
        
        // Enqueue CSS
        wp_enqueue_style(
            'almaseo-evergreen',
            $this->plugin_url . 'assets/css/evergreen.css',
            array(),
            '2.2.0'
        );
        
        // Enqueue JS for admin pages
        wp_enqueue_script(
            'almaseo-evergreen-admin',
            $this->plugin_url . 'assets/js/evergreen-tooltips.js',
            array('jquery'),
            '2.2.0',
            true
        );
        
        // Localize script
        wp_localize_script('almaseo-evergreen-admin', 'almaseoEvergreen', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('almaseo_eg_ajax'),
            'i18n' => array(
                'updating' => __('Updating...', 'almaseo'),
                'refreshed' => __('Refreshed!', 'almaseo'),
                'error' => __('Error', 'almaseo'),
                'recalculating' => __('Recalculating...', 'almaseo'),
                'recalculated' => __('Recalculated!', 'almaseo')
            )
        ));
    }
    
    /**
     * Activation hook
     */
    public static function activate() {
        // Create default options
        add_option('almaseo_eg_enabled', true);
        add_option('almaseo_eg_watch_days', 180);
        add_option('almaseo_eg_stale_days', 365);
        add_option('almaseo_eg_watch_traffic_drop', 20);
        add_option('almaseo_eg_stale_traffic_drop', 40);
        
        // Schedule cron event
        if (!wp_next_scheduled('almaseo_eg_weekly_recalculation')) {
            wp_schedule_event(time(), 'weekly', 'almaseo_eg_weekly_recalculation');
        }
    }
    
    /**
     * Deactivation hook
     */
    public static function deactivate() {
        // Remove scheduled cron
        $timestamp = wp_next_scheduled('almaseo_eg_weekly_recalculation');
        if ($timestamp) {
            wp_unschedule_event($timestamp, 'almaseo_eg_weekly_recalculation');
        }
    }
}

// Initialize
add_action('plugins_loaded', function() {
    AlmaSEO_Evergreen_Loader::get_instance();
}, 20);

// Register activation/deactivation hooks if plugin file is defined
if (defined('ALMASEO_PLUGIN_FILE')) {
    register_activation_hook(ALMASEO_PLUGIN_FILE, array('AlmaSEO_Evergreen_Loader', 'activate'));
    register_deactivation_hook(ALMASEO_PLUGIN_FILE, array('AlmaSEO_Evergreen_Loader', 'deactivate'));
}