<?php
/**
 * Featured Snippet Targeting – Admin Controller
 *
 * Registers the "Snippet Targets" submenu page, enqueues assets,
 * and handles the Pro feature gate.
 *
 * @package AlmaSEO
 * @since   7.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Snippet_Targets_Controller {

    const SLUG = 'almaseo-snippet-targets';

    /**
     * Wire up hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 46 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    /**
     * Add submenu page under AlmaSEO.
     */
    public static function register_menu() {
        add_submenu_page(
            'seo-playground',
            __( 'Snippet Targets', 'almaseo-seo-playground' ),
            __( 'Snippet Targets', 'almaseo-seo-playground' ),
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
        if ( function_exists( 'almaseo_feature_available' ) && ! almaseo_feature_available( 'snippet_targeting' ) ) {
            if ( function_exists( 'almaseo_render_feature_locked' ) ) {
                almaseo_render_feature_locked( 'snippet_targeting' );
            }
            return;
        }

        require_once plugin_dir_path( dirname( __DIR__ ) ) . 'admin/pages/snippet-targets.php';
    }

    /**
     * Enqueue CSS + JS on our admin page only.
     */
    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, self::SLUG ) === false ) {
            return;
        }

        $base = plugin_dir_url( dirname( __DIR__ ) );
        $ver  = defined( 'ALMASEO_VERSION' ) ? ALMASEO_VERSION : '7.9.0';

        wp_enqueue_style(
            'almaseo-snippet-targets',
            $base . 'assets/css/snippet-targets.css',
            array(),
            $ver
        );

        wp_enqueue_script(
            'almaseo-snippet-targets',
            $base . 'assets/js/snippet-targets.js',
            array( 'wp-api-fetch' ),
            $ver,
            true
        );

        wp_localize_script( 'almaseo-snippet-targets', 'almaseoST', array(
            'restBase'   => rest_url( 'almaseo/v1/snippet-targets' ),
            'nonce'      => wp_create_nonce( 'wp_rest' ),
            'noTargets'  => __( 'No snippet opportunities yet. Connect your site to the AlmaSEO dashboard to receive featured snippet targeting opportunities.', 'almaseo-seo-playground' ),
        ) );
    }
}
