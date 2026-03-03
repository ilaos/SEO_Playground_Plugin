<?php
/**
 * AlmaSEO Internal Links Module Loader
 *
 * Bootstraps the internal-links feature:
 *   - Loads dependencies (install, model, controller, engine)
 *   - Initialises admin & front-end components
 *   - Provides activation / deactivation / uninstall hooks
 *
 * @package AlmaSEO
 * @subpackage InternalLinks
 * @since 6.6.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Internal_Links_Loader {

    /**
     * Initialize the internal links module
     */
    public static function init() {
        self::load_dependencies();
        self::initialize_components();
    }

    /**
     * Load module dependencies
     */
    private static function load_dependencies() {
        $base = plugin_dir_path( __FILE__ );

        // Database installer
        require_once $base . 'internal-links-install.php';

        // CRUD model
        require_once $base . 'internal-links-model.php';

        // Admin controller (menu, assets, REST wiring)
        require_once $base . 'internal-links-controller.php';

        // Orphan page detection (v7.7.0+)
        require_once $base . 'internal-links-orphan.php';

        // Front-end content engine (only needed on the public site)
        if ( ! is_admin() ) {
            require_once $base . 'internal-links-engine.php';
        }
    }

    /**
     * Initialize module components
     */
    private static function initialize_components() {
        // Controller handles admin menu, assets, REST API registration
        AlmaSEO_Internal_Links_Controller::init();

        // Front-end engine
        if ( ! is_admin() && class_exists( 'AlmaSEO_Internal_Links_Engine' ) ) {
            AlmaSEO_Internal_Links_Engine::init();
        }

        // Check / create database table on admin
        if ( is_admin() ) {
            almaseo_check_internal_links_db();
        }
    }

    /* ------------------------------------------------------------------
     * Lifecycle hooks
     * ----------------------------------------------------------------*/

    /**
     * Activation hook
     */
    public static function activate() {
        require_once plugin_dir_path( __FILE__ ) . 'internal-links-install.php';
        almaseo_install_internal_links_table();
    }

    /**
     * Deactivation hook
     */
    public static function deactivate() {
        delete_transient( 'almaseo_internal_links_cache' );
    }

    /**
     * Uninstall hook
     */
    public static function uninstall() {
        if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
            return;
        }

        require_once plugin_dir_path( __FILE__ ) . 'internal-links-install.php';
        almaseo_uninstall_internal_links_table();

        delete_option( 'almaseo_internal_links_settings' );
        delete_option( 'almaseo_internal_links_db_version' );
        delete_transient( 'almaseo_internal_links_cache' );
    }
}

// Bootstrap the module on `init` (priority 5 = early, before most content filters)
add_action( 'init', array( 'AlmaSEO_Internal_Links_Loader', 'init' ), 5 );
