<?php
/**
 * GSC Monitor – Admin Controller
 *
 * Registers the "GSC Monitor" submenu page, enqueues assets,
 * and handles the Pro feature gate.
 *
 * @package AlmaSEO
 * @since   7.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_GSC_Monitor_Controller {

    const SLUG = 'almaseo-gsc-monitor';

    /**
     * Wire up hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 42 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    /**
     * Add submenu page under AlmaSEO.
     */
    public static function register_menu() {
        add_submenu_page(
            'seo-playground',
            __( 'GSC Monitor', 'almaseo' ),
            __( 'GSC Monitor', 'almaseo' ),
            'manage_options',
            self::SLUG,
            array( __CLASS__, 'render' )
        );
    }

    /**
     * Render the admin page.
     */
    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'almaseo' ) );
        }

        // Pro gate.
        if ( function_exists( 'almaseo_feature_available' ) && ! almaseo_feature_available( 'gsc_monitor' ) ) {
            if ( function_exists( 'almaseo_render_feature_locked' ) ) {
                almaseo_render_feature_locked( 'gsc_monitor' );
            }
            return;
        }

        require_once plugin_dir_path( dirname( __DIR__ ) ) . 'admin/pages/gsc-monitor.php';
    }

    /**
     * Enqueue CSS + JS on our admin page only.
     */
    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, self::SLUG ) === false ) {
            return;
        }

        $base = plugin_dir_url( dirname( __DIR__ ) );
        $ver  = defined( 'ALMASEO_VERSION' ) ? ALMASEO_VERSION : '7.5.0';

        wp_enqueue_style(
            'almaseo-gsc-monitor',
            $base . 'assets/css/gsc-monitor.css',
            array(),
            $ver
        );

        wp_enqueue_script(
            'almaseo-gsc-monitor',
            $base . 'assets/js/gsc-monitor.js',
            array( 'wp-api-fetch' ),
            $ver,
            true
        );

        wp_localize_script( 'almaseo-gsc-monitor', 'almaseoGSC', array(
            'restBase'   => rest_url( 'almaseo/v1/gsc-monitor' ),
            'nonce'      => wp_create_nonce( 'wp_rest' ),
            'adminUrl'   => admin_url( 'admin.php?page=' . self::SLUG ),
            'noFindings' => __( 'No findings yet. Findings will appear here when the AlmaSEO dashboard detects changes in your Google Search Console data.', 'almaseo' ),
            'tabs'       => array(
                'indexation_drift' => __( 'Indexation', 'almaseo' ),
                'rich_result_loss' => __( 'Rich Results', 'almaseo' ),
                'snippet_rewrite'  => __( 'Snippets', 'almaseo' ),
            ),
        ) );
    }
}
