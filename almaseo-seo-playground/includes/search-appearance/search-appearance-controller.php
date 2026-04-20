<?php
/**
 * AlmaSEO Search Appearance Controller
 *
 * Admin menu registration, asset enqueue, and AJAX save handler.
 *
 * @package AlmaSEO
 * @since   8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Search_Appearance_Controller {

    const SLUG = 'almaseo-search-appearance';

    /**
     * Initialize controller hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ), 12 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'wp_ajax_almaseo_save_search_appearance', array( __CLASS__, 'ajax_save' ) );
    }

    /**
     * Register admin submenu page.
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'seo-playground',
            __( 'Search Appearance', 'almaseo-seo-playground' ),
            __( 'Search Appearance', 'almaseo-seo-playground' ),
            'manage_options',
            self::SLUG,
            array( __CLASS__, 'render_page' )
        );
    }

    /**
     * Enqueue admin assets.
     *
     * @param string $hook Current admin page hook.
     */
    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, self::SLUG ) === false ) {
            return;
        }

        wp_enqueue_style(
            'almaseo-search-appearance',
            ALMASEO_URL . 'assets/css/search-appearance.css',
            array(),
            ALMASEO_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'almaseo-search-appearance',
            ALMASEO_URL . 'assets/js/search-appearance.js',
            array( 'jquery' ),
            ALMASEO_PLUGIN_VERSION,
            true
        );

        wp_localize_script( 'almaseo-search-appearance', 'almaseoSA', array(
            'ajaxurl'    => admin_url( 'admin-ajax.php' ),
            'nonce'      => wp_create_nonce( 'almaseo_search_appearance_nonce' ),
            'smart_tags' => AlmaSEO_Smart_Tags::get_available_tags(),
            'separators' => AlmaSEO_Search_Appearance_Settings::get_separator_options(),
            'strings'    => array(
                'saving' => __( 'Saving...', 'almaseo-seo-playground' ),
                'saved'  => __( 'Settings saved successfully!', 'almaseo-seo-playground' ),
                'error'  => __( 'An error occurred. Please try again.', 'almaseo-seo-playground' ),
            ),
        ) );
    }

    /**
     * Render admin page.
     */
    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions.', 'almaseo-seo-playground' ) );
        }

        require_once ALMASEO_PATH . 'admin/pages/search-appearance.php';
    }

    /**
     * AJAX save handler.
     */
    public static function ajax_save() {
        check_ajax_referer( 'almaseo_search_appearance_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'almaseo-seo-playground' ) ) );
        }

        $raw = isset( $_POST['settings'] ) ? wp_unslash( $_POST['settings'] ) : '{}';
        $input = json_decode( $raw, true );

        if ( ! is_array( $input ) ) {
            wp_send_json_error( array( 'message' => __( 'Invalid data format.', 'almaseo-seo-playground' ) ) );
        }

        $sanitized = AlmaSEO_Search_Appearance_Settings::sanitize( $input );
        update_option( AlmaSEO_Search_Appearance_Settings::OPTION_KEY, $sanitized );

        wp_send_json_success( array( 'message' => __( 'Settings saved.', 'almaseo-seo-playground' ) ) );
    }
}
