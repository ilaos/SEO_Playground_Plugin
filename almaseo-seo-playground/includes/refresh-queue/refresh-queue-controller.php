<?php
/**
 * Refresh Queue – Admin Controller
 *
 * Registers the "Refresh Queue" submenu page, enqueues assets,
 * and handles the Pro feature gate.
 *
 * @package AlmaSEO
 * @since   7.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Refresh_Queue_Controller {

    const SLUG = 'almaseo-refresh-queue';

    /**
     * Wire up hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 35 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    /**
     * Add submenu page under AlmaSEO.
     */
    public static function register_menu() {
        add_submenu_page(
            'seo-playground',
            __( 'Refresh Queue', 'almaseo-seo-playground' ),
            __( 'Refresh Queue', 'almaseo-seo-playground' ),
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
        if ( function_exists( 'almaseo_feature_available' ) && ! almaseo_feature_available( 'refresh_queue' ) ) {
            if ( function_exists( 'almaseo_render_feature_locked' ) ) {
                almaseo_render_feature_locked( 'refresh_queue' );
            }
            return;
        }

        require_once plugin_dir_path( dirname( __DIR__ ) ) . 'admin/pages/refresh-queue.php';
    }

    /**
     * Enqueue CSS + JS on our admin page only.
     */
    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, self::SLUG ) === false ) {
            return;
        }

        $base = plugin_dir_url( dirname( __DIR__ ) );
        $ver  = defined( 'ALMASEO_VERSION' ) ? ALMASEO_VERSION : '7.2.0';

        wp_enqueue_style(
            'almaseo-refresh-queue',
            $base . 'assets/css/refresh-queue.css',
            array(),
            $ver
        );

        wp_enqueue_script(
            'almaseo-refresh-queue',
            $base . 'assets/js/refresh-queue.js',
            array( 'wp-api-fetch' ),
            $ver,
            true
        );

        wp_localize_script( 'almaseo-refresh-queue', 'almaseoRQ', array(
            'restBase'         => rest_url( 'almaseo/v1/refresh-queue' ),
            'nonce'            => wp_create_nonce( 'wp_rest' ),
            'adminUrl'         => admin_url( 'admin.php?page=' . self::SLUG ),
            'refreshDraftsUrl' => admin_url( 'admin.php?page=almaseo-refresh-drafts' ),
            'noItems'          => __( 'No posts have been scored yet. Click Recalculate to analyze your content.', 'almaseo-seo-playground' ),
        ) );
    }
}
