<?php
/**
 * AlmaSEO LLMs.txt Controller
 *
 * Manages the llms.txt file that guides how large language models interact
 * with site content. Supports virtual (dynamic) and file (static) modes.
 * Mirrors the robots.txt editor pattern.
 *
 * @package AlmaSEO
 * @since   8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_LLMS_Txt_Controller {

    private static $instance = null;

    const SLUG = 'almaseo-llms-txt';

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ), 16 );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        add_action( 'wp_ajax_almaseo_llms_txt_save', array( $this, 'ajax_save' ) );
        add_action( 'wp_ajax_almaseo_llms_txt_generate', array( $this, 'ajax_generate' ) );

        // Intercept /llms.txt requests.
        add_action( 'parse_request', array( $this, 'intercept_request' ) );
    }

    /**
     * Register admin submenu.
     */
    public function add_admin_menu() {
        add_submenu_page(
            'seo-playground',
            'LLMs.txt Editor - AlmaSEO',
            'LLMs.txt',
            'manage_options',
            self::SLUG,
            array( $this, 'render_admin_page' )
        );
    }

    /**
     * Enqueue admin assets.
     */
    public function enqueue_assets( $hook ) {
        if ( strpos( $hook, self::SLUG ) === false ) {
            return;
        }

        wp_enqueue_style(
            'almaseo-llms-txt-editor',
            ALMASEO_URL . 'assets/css/llms-txt-editor.css',
            array(),
            ALMASEO_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'almaseo-llms-txt-editor',
            ALMASEO_URL . 'assets/js/llms-txt-editor.js',
            array( 'jquery' ),
            ALMASEO_PLUGIN_VERSION,
            true
        );

        wp_localize_script( 'almaseo-llms-txt-editor', 'almaseoLlmsTxt', array(
            'ajaxurl' => admin_url( 'admin-ajax.php' ),
            'nonce'   => wp_create_nonce( 'almaseo_llms_txt_nonce' ),
            'siteUrl' => home_url( '/llms.txt' ),
            'strings' => array(
                'saving'         => __( 'Saving...', 'almaseo-seo-playground' ),
                'saved'          => __( 'Settings saved successfully!', 'almaseo-seo-playground' ),
                'error'          => __( 'An error occurred. Please try again.', 'almaseo-seo-playground' ),
                'generating'     => __( 'Generating...', 'almaseo-seo-playground' ),
                'generated'      => __( 'Content generated! Review and save.', 'almaseo-seo-playground' ),
                'confirmReplace' => __( 'This will replace current content. Continue?', 'almaseo-seo-playground' ),
            ),
        ) );
    }

    /**
     * Render admin page.
     */
    public function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'Insufficient permissions.', 'almaseo-seo-playground' ) );
        }

        require_once ALMASEO_PATH . 'admin/pages/llms-txt-editor.php';
    }

    /**
     * Intercept /llms.txt requests and serve content.
     */
    public function intercept_request( $wp ) {
        if ( ! isset( $_SERVER['REQUEST_URI'] ) ) {
            return;
        }

        $path = wp_parse_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ), PHP_URL_PATH );
        if ( $path !== '/llms.txt' && $path !== '/llms-full.txt' ) {
            return;
        }

        $mode = get_option( 'almaseo_llms_txt_mode', 'virtual' );
        if ( $mode !== 'virtual' ) {
            return;
        }

        $content = get_option( 'almaseo_llms_txt_content', '' );
        if ( empty( $content ) ) {
            $content = self::get_default_content();
        }

        header( 'Content-Type: text/plain; charset=utf-8' );
        header( 'X-Robots-Tag: noindex' );
        echo $content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- plain text file
        exit;
    }

    /**
     * AJAX: Save llms.txt content and mode.
     */
    public function ajax_save() {
        check_ajax_referer( 'almaseo_llms_txt_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'almaseo-seo-playground' ) ) );
        }

        $content = isset( $_POST['content'] ) ? sanitize_textarea_field( wp_unslash( $_POST['content'] ) ) : '';
        $mode    = isset( $_POST['mode'] ) ? sanitize_key( $_POST['mode'] ) : 'virtual';

        if ( ! in_array( $mode, array( 'virtual', 'file', 'disabled' ), true ) ) {
            $mode = 'virtual';
        }

        update_option( 'almaseo_llms_txt_content', $content );
        update_option( 'almaseo_llms_txt_mode', $mode );

        wp_send_json_success( array( 'message' => __( 'Saved.', 'almaseo-seo-playground' ) ) );
    }

    /**
     * AJAX: Auto-generate llms.txt content.
     */
    public function ajax_generate() {
        check_ajax_referer( 'almaseo_llms_txt_nonce', 'nonce' );

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_send_json_error( array( 'message' => __( 'Permission denied.', 'almaseo-seo-playground' ) ) );
        }

        if ( ! class_exists( 'AlmaSEO_LLMS_Txt_Generator' ) ) {
            require_once __DIR__ . '/llms-txt-generator.php';
        }

        $content = AlmaSEO_LLMS_Txt_Generator::generate();
        wp_send_json_success( array( 'content' => $content ) );
    }

    /**
     * Get default llms.txt content.
     *
     * @return string
     */
    public static function get_default_content() {
        $lines = array(
            '# ' . get_bloginfo( 'name' ),
            '',
            '> ' . get_bloginfo( 'description' ),
            '',
            'URL: ' . home_url( '/' ),
            '',
            '# This file provides information to help LLMs understand this site.',
            '# Use the auto-generate feature in AlmaSEO to populate this file.',
        );

        return implode( "\n", $lines );
    }
}
