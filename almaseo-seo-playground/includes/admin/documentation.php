<?php
/**
 * AlmaSEO Documentation & Help — Admin Page Controller
 *
 * Registers a "Documentation" submenu page under SEO Playground
 * that displays all free features organized by category.
 *
 * @package AlmaSEO
 * @since   8.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Documentation {

    const SLUG = 'almaseo-documentation';

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 50 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
    }

    /**
     * Register the submenu page.
     */
    public static function register_menu() {
        add_submenu_page(
            'seo-playground',
            __( 'Documentation & Help', 'almaseo-seo-playground' ),
            __( 'Documentation', 'almaseo-seo-playground' ),
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
            wp_die( __( 'Insufficient permissions.', 'almaseo-seo-playground' ) );
        }

        require_once plugin_dir_path( dirname( __DIR__ ) ) . 'admin/pages/documentation.php';
    }

    /**
     * Enqueue page-specific CSS.
     */
    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, self::SLUG ) === false ) {
            return;
        }

        $base = plugin_dir_url( dirname( __DIR__ ) );
        $ver  = defined( 'ALMASEO_PLUGIN_VERSION' ) ? ALMASEO_PLUGIN_VERSION : '8.6.0';

        wp_enqueue_style(
            'almaseo-documentation',
            $base . 'assets/css/documentation.css',
            array(),
            $ver
        );
    }
}
