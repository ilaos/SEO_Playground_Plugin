<?php
/**
 * AlmaSEO Redirects Module Loader
 * 
 * @package AlmaSEO
 * @subpackage Redirects
 * @since 6.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AlmaSEO_Redirects_Loader {
    
    /**
     * Initialize the redirects module
     */
    public static function init() {
        // For now, always load the module - Pro check will be done at feature usage
        // This ensures the menu appears for admins
        
        // Load required files
        self::load_dependencies();
        
        // Initialize components
        self::initialize_components();
    }
    
    /**
     * Load module dependencies
     */
    private static function load_dependencies() {
        $base_path = plugin_dir_path(__FILE__);
        
        // Load install/database handler
        require_once $base_path . 'redirects-install.php';
        
        // Load model (CRUD operations)
        require_once $base_path . 'redirects-model.php';
        
        // Load controller (business logic)
        require_once $base_path . 'redirects-controller.php';

        // Load intelligence engine (v7.6.0+)
        require_once $base_path . 'redirects-intelligence.php';

        // Load trash handler (redirect-on-delete prompt, admin only)
        if (is_admin()) {
            require_once $base_path . 'redirects-trash-handler.php';
        }

        // Load runtime handler (front-end redirects)
        if (!is_admin()) {
            require_once $base_path . 'redirects-runtime.php';
        }
    }
    
    /**
     * Initialize module components
     */
    private static function initialize_components() {
        // Initialize controller (handles admin menu, assets, REST API)
        AlmaSEO_Redirects_Controller::init();

        // Initialize trash handler (redirect-on-delete prompt)
        if (is_admin()) {
            AlmaSEO_Redirects_Trash_Handler::init();
        }

        // Check and update database on admin
        if (is_admin()) {
            almaseo_check_redirects_db();
        }
    }
    
    /**
     * Activation hook
     */
    public static function activate() {
        // Install database table
        require_once plugin_dir_path(__FILE__) . 'redirects-install.php';
        almaseo_install_redirects_table();
        
        // Flush rewrite rules to ensure redirects work
        flush_rewrite_rules();
    }
    
    /**
     * Deactivation hook
     */
    public static function deactivate() {
        // Clear transients
        delete_transient('almaseo_enabled_redirects');
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Uninstall hook
     */
    public static function uninstall() {
        // Only run if explicitly uninstalling
        if (!defined('WP_UNINSTALL_PLUGIN')) {
            return;
        }
        
        // Remove database table
        require_once plugin_dir_path(__FILE__) . 'redirects-install.php';
        almaseo_uninstall_redirects_table();
        
        // Remove options
        delete_option('almaseo_redirects_db_version');
        delete_transient('almaseo_enabled_redirects');
    }
}

// Initialize the module
add_action('init', array('AlmaSEO_Redirects_Loader', 'init'), 5);