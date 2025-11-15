<?php
/**
 * AlmaSEO 404 Tracker - Module Loader
 * 
 * @package AlmaSEO
 * @subpackage 404Tracker
 * @since 6.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AlmaSEO_404_Loader {
    
    /**
     * Initialize the 404 tracker module
     */
    public static function init() {
        // Load dependencies
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
        require_once $base_path . '404-install.php';
        
        // Load model (CRUD operations)
        require_once $base_path . '404-model.php';
        
        // Load controller (business logic)
        require_once $base_path . '404-controller.php';
        
        // Load capture logic (front-end only)
        if (!is_admin()) {
            require_once $base_path . '404-capture.php';
        }
    }
    
    /**
     * Initialize module components
     */
    private static function initialize_components() {
        // Initialize controller (handles admin menu, assets, REST API)
        AlmaSEO_404_Controller::init();
        
        // Check and update database on admin
        if (is_admin()) {
            almaseo_check_404_db();
        }
    }
    
    /**
     * Activation hook
     */
    public static function activate() {
        // Install database table
        require_once plugin_dir_path(__FILE__) . '404-install.php';
        almaseo_install_404_table();
    }
    
    /**
     * Deactivation hook
     */
    public static function deactivate() {
        // Clear transients
        delete_transient('almaseo_404_stats');
        delete_transient('almaseo_404_top_referrer');
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
        require_once plugin_dir_path(__FILE__) . '404-install.php';
        almaseo_uninstall_404_table();
    }
}

// Initialize the module
add_action('init', array('AlmaSEO_404_Loader', 'init'), 5);