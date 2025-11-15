<?php
/**
 * AlmaSEO Evergreen Feature - Ultra Safe Loader
 * 
 * Minimal loader that won't crash even if files are missing
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 2.4.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Ultra Safe Evergreen Loader
 */
class AlmaSEO_Evergreen_Loader_Ultra_Safe {
    
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
     * Loaded modules tracking
     */
    private $loaded_modules = array();
    
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
        add_action('init', array($this, 'init'), 20);
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
        
        return true;
    }
    
    /**
     * Initialize
     */
    public function init() {
        // Load core files only
        $this->load_core_files();
        
        // Setup basic hooks
        $this->setup_hooks();
        
        // Log what we've loaded
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[AlmaSEO] Evergreen modules loaded: ' . implode(', ', $this->loaded_modules));
        }
    }
    
    /**
     * Load core files safely
     */
    private function load_core_files() {
        // List of core files to try loading
        $core_files = array(
            'constants.php' => 'Constants',
            'functions.php' => 'Functions',
            'meta.php' => 'Meta'
        );
        
        foreach ($core_files as $file => $name) {
            $file_path = $this->plugin_dir . 'includes/evergreen/' . $file;
            if (file_exists($file_path)) {
                try {
                    require_once $file_path;
                    $this->loaded_modules[] = $name;
                } catch (Exception $e) {
                    error_log('[AlmaSEO] Failed to load evergreen/' . $file . ': ' . $e->getMessage());
                }
            }
        }
        
        // Try to load admin files if in admin
        if (is_admin()) {
            $this->try_load_admin_files();
        }
        
        // Try to load REST API
        $this->try_load_rest_api();
    }
    
    /**
     * Try to load admin files
     */
    private function try_load_admin_files() {
        // List of admin files that might have classes
        $admin_files = array(
            'admin.php',
            'widget.php',
            'dashboard.php',
            'columns.php',
            'metabox.php'
        );
        
        foreach ($admin_files as $file) {
            $file_path = $this->plugin_dir . 'includes/evergreen/' . $file;
            if (file_exists($file_path)) {
                try {
                    require_once $file_path;
                    $this->loaded_modules[] = basename($file, '.php');
                    
                    // Try to instantiate classes if they exist
                    $this->try_instantiate_class($file);
                } catch (Exception $e) {
                    // Silently fail - don't crash the site
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('[AlmaSEO] Could not load ' . $file . ': ' . $e->getMessage());
                    }
                }
            }
        }
    }
    
    /**
     * Try to instantiate a class from a file
     */
    private function try_instantiate_class($file) {
        // Map files to their expected class names
        $class_map = array(
            'admin.php' => 'AlmaSEO_Evergreen_Admin',
            'widget.php' => 'AlmaSEO_Evergreen_Widget',
            'dashboard.php' => 'AlmaSEO_Evergreen_Dashboard',
            'cron.php' => 'AlmaSEO_Evergreen_Cron',
            'rest-api.php' => 'AlmaSEO_Evergreen_REST_API'
        );
        
        if (isset($class_map[$file])) {
            $class_name = $class_map[$file];
            
            // Check if class exists before trying to instantiate
            if (class_exists($class_name)) {
                // Check if class has get_instance method
                if (method_exists($class_name, 'get_instance')) {
                    try {
                        call_user_func(array($class_name, 'get_instance'));
                    } catch (Exception $e) {
                        // Silently fail
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log('[AlmaSEO] Could not instantiate ' . $class_name . ': ' . $e->getMessage());
                        }
                    }
                }
            }
        }
    }
    
    /**
     * Try to load REST API
     */
    private function try_load_rest_api() {
        $rest_file = $this->plugin_dir . 'includes/evergreen/rest-api.php';
        if (file_exists($rest_file)) {
            try {
                require_once $rest_file;
                $this->loaded_modules[] = 'REST API';
                $this->try_instantiate_class('rest-api.php');
            } catch (Exception $e) {
                // Silently fail
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[AlmaSEO] Could not load REST API: ' . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Setup hooks
     */
    private function setup_hooks() {
        // Only setup hooks if we have the necessary functions
        if (!function_exists('almaseo_eg_get_post_status')) {
            return;
        }
        
        // Enqueue assets conditionally
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        }
        
        // Editor assets
        add_action('enqueue_block_editor_assets', array($this, 'enqueue_editor_assets'));
    }
    
    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only enqueue where needed
        $allowed_hooks = array('edit.php', 'post.php', 'post-new.php', 'index.php');
        $is_evergreen_page = strpos($hook, 'almaseo-evergreen') !== false;
        
        if (!in_array($hook, $allowed_hooks) && !$is_evergreen_page) {
            return;
        }
        
        // Check if CSS file exists before enqueuing
        $css_file = $this->plugin_dir . 'assets/css/evergreen.css';
        if (file_exists($css_file)) {
            wp_enqueue_style(
                'almaseo-evergreen',
                $this->plugin_url . 'assets/css/evergreen.css',
                array(),
                '2.4.0'
            );
        }
        
        // Check if JS file exists before enqueuing
        $js_file = $this->plugin_dir . 'assets/js/evergreen-tooltips.js';
        if (file_exists($js_file)) {
            wp_enqueue_script(
                'almaseo-evergreen-tooltips',
                $this->plugin_url . 'assets/js/evergreen-tooltips.js',
                array('jquery'),
                '2.4.0',
                true
            );
            
            // Localize script
            wp_localize_script('almaseo-evergreen-tooltips', 'almaseoEvergreen', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('almaseo_eg_ajax')
            ));
        }
    }
    
    /**
     * Enqueue editor assets
     */
    public function enqueue_editor_assets() {
        // Only load for supported post types
        $post_type = get_post_type();
        if (!in_array($post_type, array('post', 'page'))) {
            return;
        }
        
        // Check if panel JS exists before enqueuing
        $panel_js = $this->plugin_dir . 'assets/js/evergreen-panel.js';
        if (file_exists($panel_js)) {
            wp_enqueue_script(
                'almaseo-evergreen-panel',
                $this->plugin_url . 'assets/js/evergreen-panel.js',
                array(
                    'wp-plugins',
                    'wp-edit-post',
                    'wp-element',
                    'wp-components',
                    'wp-data',
                    'wp-i18n'
                ),
                '2.4.0',
                true
            );
            
            // Localize settings
            wp_localize_script('almaseo-evergreen-panel', 'almaseoEvergreenSettings', array(
                'supportedPostTypes' => array('post', 'page'),
                'apiRoot' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest')
            ));
        }
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
    }
    
    /**
     * Deactivation hook
     */
    public static function deactivate() {
        // Clean up scheduled events if they exist
        $events = array('almaseo_eg_weekly_recalculation', 'almaseo_eg_daily_snapshot');
        foreach ($events as $event) {
            $timestamp = wp_next_scheduled($event);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $event);
            }
        }
    }
}

// Initialize
add_action('plugins_loaded', function() {
    AlmaSEO_Evergreen_Loader_Ultra_Safe::get_instance();
}, 20);

// Register activation/deactivation hooks if plugin file is defined
if (defined('ALMASEO_PLUGIN_FILE')) {
    register_activation_hook(ALMASEO_PLUGIN_FILE, array('AlmaSEO_Evergreen_Loader_Ultra_Safe', 'activate'));
    register_deactivation_hook(ALMASEO_PLUGIN_FILE, array('AlmaSEO_Evergreen_Loader_Ultra_Safe', 'deactivate'));
}