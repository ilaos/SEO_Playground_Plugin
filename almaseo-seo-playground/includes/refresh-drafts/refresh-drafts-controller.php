<?php
/**
 * Refresh Drafts – Admin Controller
 *
 * Adds the "Content Refresh" submenu page under the AlmaSEO top-level
 * menu and enqueues the module's CSS / JS only on that screen.
 *
 * @package AlmaSEO
 * @since   7.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Refresh_Drafts_Controller {

    /** Menu slug for the list page. */
    const SLUG = 'almaseo-refresh-drafts';

    /**
     * Hook into WordPress.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 30 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue' ) );
    }

    /* ──────────────────────── menu ── */

    /**
     * Register the submenu page under AlmaSEO.
     */
    public static function register_menu() {
        add_submenu_page(
            'seo-playground',             // parent slug
            'Content Refresh',            // page title
            'Content Refresh',            // menu title
            'manage_options',             // capability
            self::SLUG,                   // menu slug
            array( __CLASS__, 'render' )  // callback
        );
    }

    /* ──────────────────────── render ── */

    /**
     * Route to the correct template (list vs. review).
     */
    public static function render() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'almaseo' ) );
        }

        // Pro gate
        if ( function_exists( 'almaseo_feature_available' ) && ! almaseo_feature_available( 'refresh_drafts' ) ) {
            if ( function_exists( 'almaseo_render_feature_locked' ) ) {
                almaseo_render_feature_locked( 'refresh_drafts' );
            }
            return;
        }

        $review_id = isset( $_GET['review'] ) ? absint( $_GET['review'] ) : 0;

        if ( $review_id ) {
            if ( class_exists( 'AlmaSEO_Refresh_Draft_Model' ) ) {
                $draft = AlmaSEO_Refresh_Draft_Model::get( $review_id );
                if ( ! $draft ) {
                    wp_die( 'Refresh draft not found.' );
                }
            }
            include dirname( dirname( __DIR__ ) ) . '/admin/pages/refresh-drafts-review.php';
        } else {
            include dirname( dirname( __DIR__ ) ) . '/admin/pages/refresh-drafts.php';
        }
    }

    /* ──────────────────────── assets ── */

    /**
     * Enqueue CSS + JS only on the Content Refresh admin page.
     */
    public static function enqueue( $hook ) {
        // The hook suffix looks like "almaseo_page_almaseo-refresh-drafts".
        if ( strpos( $hook, self::SLUG ) === false ) {
            return;
        }

        $base = plugin_dir_url( dirname( __DIR__ ) );
        $ver  = defined( 'ALMASEO_VERSION' ) ? ALMASEO_VERSION : '7.0.0';

        wp_enqueue_style(
            'almaseo-refresh-drafts',
            $base . 'assets/css/refresh-drafts.css',
            array(),
            $ver
        );

        wp_enqueue_script(
            'almaseo-refresh-drafts',
            $base . 'assets/js/refresh-drafts.js',
            array( 'wp-api-fetch' ),
            $ver,
            true
        );

        wp_localize_script( 'almaseo-refresh-drafts', 'almaseoRD', array(
            'restBase'  => rest_url( 'almaseo/v1/refresh-drafts' ),
            'nonce'     => wp_create_nonce( 'wp_rest' ),
            'adminUrl'  => admin_url( 'admin.php?page=' . self::SLUG ),
            'noDrafts'  => 'Content refreshes will appear here automatically when AlmaSEO identifies opportunities to improve your posts. You can trigger a refresh from the AlmaSEO dashboard for any post.',
        ) );
    }
}
