<?php
/**
 * AlmaSEO Evergreen Feature - Safe Loader (Ultra-Minimal)
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Safe Evergreen Loader - Minimal memory footprint
 */
class AlmaSEO_Evergreen_Loader_Safe {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Flag to track if we should load
     */
    private static $should_load = false;
    
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
        // Check if we're in a safe context to load
        self::$should_load = $this->is_safe_to_load();
        
        if (!self::$should_load) {
            return;
        }
        
        // Only load after WordPress is fully loaded
        add_action('wp_loaded', array($this, 'delayed_init'), 20);
    }
    
    /**
     * Check if it's safe to load the feature
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
        
        // Don't load in non-admin contexts (frontend, AJAX, cron)
        if (!is_admin() && !wp_doing_ajax()) {
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
     * Delayed initialization
     */
    public function delayed_init() {
        // Double-check we're still in admin
        if (!is_admin() && !wp_doing_ajax()) {
            return;
        }
        
        // Load only core dependencies
        $this->load_core_dependencies();
        
        // Set up conditional loading
        add_action('admin_init', array($this, 'conditional_load'), 10);
    }
    
    /**
     * Load only essential files
     */
    private function load_core_dependencies() {
        require_once dirname(__FILE__) . '/constants.php';
        require_once dirname(__FILE__) . '/meta.php';
    }
    
    /**
     * Conditionally load components based on current page
     */
    public function conditional_load() {
        global $pagenow;
        
        // Skip if not on relevant pages
        $relevant_pages = array('edit.php', 'post.php', 'post-new.php', 'index.php', 'admin.php');
        if (!in_array($pagenow, $relevant_pages)) {
            return;
        }
        
        // Check if on our settings page
        $is_evergreen_page = isset($_GET['page']) && strpos($_GET['page'], 'almaseo-evergreen') !== false;
        
        // Load scoring for post pages
        if (in_array($pagenow, array('post.php', 'post-new.php')) || $is_evergreen_page) {
            if (file_exists(dirname(__FILE__) . '/scoring.php')) {
                require_once dirname(__FILE__) . '/scoring.php';
            }
        }
        
        // Load columns for post list
        if ($pagenow === 'edit.php') {
            if (file_exists(dirname(__FILE__) . '/columns.php')) {
                require_once dirname(__FILE__) . '/columns.php';
            }
        }
        
        // Load metabox for post edit
        if (in_array($pagenow, array('post.php', 'post-new.php'))) {
            if (file_exists(dirname(__FILE__) . '/metabox.php')) {
                require_once dirname(__FILE__) . '/metabox.php';
            }
        }
        
        // Load widget for dashboard
        if ($pagenow === 'index.php') {
            if (file_exists(dirname(__FILE__) . '/widget.php')) {
                require_once dirname(__FILE__) . '/widget.php';
            }
        }
        
        // Load admin-post actions (needs to be available globally for admin-post.php)
        if (file_exists(dirname(__FILE__) . '/actions.php')) {
            require_once dirname(__FILE__) . '/actions.php';
        }
        
        // Load admin pages
        if ($is_evergreen_page) {
            if (file_exists(dirname(__FILE__) . '/admin.php')) {
                require_once dirname(__FILE__) . '/admin.php';
            }
            if ($_GET['page'] === 'almaseo-evergreen-dashboard') {
                if (file_exists(dirname(__FILE__) . '/dashboard.php')) {
                    require_once dirname(__FILE__) . '/dashboard.php';
                }
                if (file_exists(dirname(__FILE__) . '/export.php')) {
                    require_once dirname(__FILE__) . '/export.php';
                }
            }
        }
        
        // Load UI components conditionally
        if ($pagenow === 'edit.php' || $is_evergreen_page) {
            if (file_exists(dirname(__FILE__) . '/evergreen-ui.php')) {
                require_once dirname(__FILE__) . '/evergreen-ui.php';
            }
        }
        
        // Enqueue assets only where needed
        add_action('admin_enqueue_scripts', array($this, 'conditional_enqueue'));
    }
    
    /**
     * Conditionally enqueue assets
     */
    public function conditional_enqueue($hook) {
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
        
        // Enqueue CSS - we're in /includes/evergreen/, need to go up 2 levels
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
                'error' => __('Error', 'almaseo')
            )
        ));
    }
    
    /**
     * Activation hook - minimal operations only
     */
    public static function activate() {
        // Don't do anything during activation to avoid memory issues
        // Settings will be created on first actual load
    }
    
    /**
     * Deactivation hook
     */
    public static function deactivate() {
        // Load constants to get the cron event name
        if (file_exists(dirname(__FILE__) . '/constants.php')) {
            require_once dirname(__FILE__) . '/constants.php';
            
            // Remove scheduled cron if it exists
            if (defined('ALMASEO_EG_CRON_EVENT')) {
                $timestamp = wp_next_scheduled(ALMASEO_EG_CRON_EVENT);
                if ($timestamp) {
                    wp_unschedule_event($timestamp, ALMASEO_EG_CRON_EVENT);
                }
            }
        }
    }
    
    /**
     * Check if feature is enabled
     */
    public static function is_enabled() {
        return self::$should_load;
    }
}

// Initialize only after plugins are loaded
add_action('plugins_loaded', function() {
    // Skip if memory is too low
    $memory_limit = wp_convert_hr_to_bytes(ini_get('memory_limit'));
    $memory_usage = memory_get_usage(true);
    $available = $memory_limit - $memory_usage;
    
    // Need at least 10MB to even try
    if ($available < 10 * 1024 * 1024) {
        return;
    }
    
    AlmaSEO_Evergreen_Loader_Safe::get_instance();
}, 30);

// Register activation/deactivation hooks if plugin file is defined
if (defined('ALMASEO_PLUGIN_FILE')) {
    register_activation_hook(ALMASEO_PLUGIN_FILE, array('AlmaSEO_Evergreen_Loader_Safe', 'activate'));
    register_deactivation_hook(ALMASEO_PLUGIN_FILE, array('AlmaSEO_Evergreen_Loader_Safe', 'deactivate'));
}