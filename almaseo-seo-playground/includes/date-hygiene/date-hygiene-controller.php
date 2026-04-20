<?php
/**
 * Date Hygiene Scanner – Admin Controller
 *
 * Registers the "Date Hygiene" submenu page, enqueues assets,
 * and handles the Pro feature gate.
 *
 * @package AlmaSEO
 * @since   7.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Date_Hygiene_Controller {

    const SLUG = 'almaseo-date-hygiene';

    /**
     * Wire up hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 38 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    /**
     * Add submenu page under AlmaSEO.
     */
    public static function register_menu() {
        add_submenu_page(
            'seo-playground',
            __( 'Date Hygiene', 'almaseo-seo-playground' ),
            __( 'Date Hygiene', 'almaseo-seo-playground' ),
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
        if ( function_exists( 'almaseo_feature_available' ) && ! almaseo_feature_available( 'date_hygiene' ) ) {
            if ( function_exists( 'almaseo_render_feature_locked' ) ) {
                almaseo_render_feature_locked( 'date_hygiene' );
            }
            return;
        }

        require_once plugin_dir_path( dirname( __DIR__ ) ) . 'admin/pages/date-hygiene.php';
    }

    /**
     * Enqueue CSS + JS on our admin page only.
     */
    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, self::SLUG ) === false ) {
            return;
        }

        $base = plugin_dir_url( dirname( __DIR__ ) );
        $ver  = defined( 'ALMASEO_VERSION' ) ? ALMASEO_VERSION : '7.3.0';

        wp_enqueue_style(
            'almaseo-date-hygiene',
            $base . 'assets/css/date-hygiene.css',
            array(),
            $ver
        );

        wp_enqueue_script(
            'almaseo-date-hygiene',
            $base . 'assets/js/date-hygiene.js',
            array( 'wp-api-fetch' ),
            $ver,
            true
        );

        wp_localize_script( 'almaseo-date-hygiene', 'almaseoDH', array(
            'restBase'    => rest_url( 'almaseo/v1/date-hygiene' ),
            'nonce'       => wp_create_nonce( 'wp_rest' ),
            'adminUrl'    => admin_url( 'admin.php?page=' . self::SLUG ),
            'noFindings'  => __( 'No findings yet. Click Scan Now to analyze your content for stale references.', 'almaseo-seo-playground' ),
            'currentYear' => (int) gmdate( 'Y' ),
        ) );
    }
}
