<?php
/**
 * AlmaSEO Import Controller
 *
 * Admin menu, asset enqueue for the Import/Migration page.
 *
 * @package AlmaSEO
 * @since   8.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Import_Controller {

    const SLUG = 'almaseo-import';

    /**
     * Initialize controller.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ), 14 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    /**
     * Register admin submenu.
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'seo-playground',
            __( 'Import SEO Data', 'almaseo' ),
            __( 'Import / Export', 'almaseo' ),
            'manage_options',
            self::SLUG,
            array( __CLASS__, 'render_page' )
        );
    }

    /**
     * Enqueue assets.
     */
    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, self::SLUG ) === false ) {
            return;
        }

        wp_enqueue_style(
            'almaseo-import',
            ALMASEO_URL . 'assets/css/import.css',
            array(),
            ALMASEO_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'almaseo-import',
            ALMASEO_URL . 'assets/js/import.js',
            array( 'wp-api-fetch' ),
            ALMASEO_PLUGIN_VERSION,
            true
        );

        wp_localize_script( 'almaseo-import', 'almaseoImport', array(
            'restBase'     => rest_url( 'almaseo/v1/import/' ),
            'nonce'        => wp_create_nonce( 'wp_rest' ),
            'importStatus' => get_option( 'almaseo_import_status', array() ),
            'strings'      => array(
                'detecting'    => __( 'Detecting...', 'almaseo' ),
                'importing'    => __( 'Importing...', 'almaseo' ),
                'done'         => __( 'Import complete!', 'almaseo' ),
                'error'        => __( 'An error occurred.', 'almaseo' ),
                'noData'       => __( 'No data found from any SEO plugin.', 'almaseo' ),
                'confirmStart' => __( 'Start importing data? Existing AlmaSEO data will not be overwritten unless you check "Overwrite existing".', 'almaseo' ),
                'processed'    => __( 'Posts Processed', 'almaseo' ),
                'imported'     => __( 'Fields Imported', 'almaseo' ),
                'skipped'      => __( 'Fields Skipped', 'almaseo' ),
            ),
        ) );
    }

    /**
     * Render admin page.
     */
    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'almaseo' ) );
        }

        require_once ALMASEO_PATH . 'admin/pages/import.php';
    }
}
