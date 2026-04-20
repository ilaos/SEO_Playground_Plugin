<?php
/**
 * Schema Drift Monitor – Admin Controller
 *
 * Registers the "Schema Drift" submenu page, enqueues assets,
 * and handles the Pro feature gate.
 *
 * @package AlmaSEO
 * @since   7.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Schema_Drift_Controller {

    const SLUG = 'almaseo-schema-drift';

    /**
     * Wire up hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 44 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    /**
     * Add submenu page under AlmaSEO.
     */
    public static function register_menu() {
        add_submenu_page(
            'seo-playground',
            __( 'Schema Drift', 'almaseo-seo-playground' ),
            __( 'Schema Drift', 'almaseo-seo-playground' ),
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
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'almaseo-seo-playground' ) );
        }

        // Pro gate.
        if ( function_exists( 'almaseo_feature_available' ) && ! almaseo_feature_available( 'schema_drift' ) ) {
            if ( function_exists( 'almaseo_render_feature_locked' ) ) {
                almaseo_render_feature_locked( 'schema_drift' );
            }
            return;
        }

        require_once plugin_dir_path( dirname( __DIR__ ) ) . 'admin/pages/schema-drift.php';
    }

    /**
     * Enqueue CSS + JS on our admin page only.
     */
    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, self::SLUG ) === false ) {
            return;
        }

        $base = plugin_dir_url( dirname( __DIR__ ) );
        $ver  = defined( 'ALMASEO_VERSION' ) ? ALMASEO_VERSION : '7.8.0';

        wp_enqueue_style(
            'almaseo-schema-drift',
            $base . 'assets/css/schema-drift.css',
            array(),
            $ver
        );

        wp_enqueue_script(
            'almaseo-schema-drift',
            $base . 'assets/js/schema-drift.js',
            array( 'wp-api-fetch' ),
            $ver,
            true
        );

        wp_localize_script( 'almaseo-schema-drift', 'almaseoSD', array(
            'restBase'    => rest_url( 'almaseo/v1/schema-drift' ),
            'nonce'       => wp_create_nonce( 'wp_rest' ),
            'noFindings'  => __( 'No drift findings yet. Capture a baseline and run a scan to detect schema changes.', 'almaseo-seo-playground' ),
        ) );
    }
}
