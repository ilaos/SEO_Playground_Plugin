<?php
/**
 * AlmaSEO Breadcrumbs Block
 *
 * Registers the almaseo/breadcrumbs Gutenberg block with server-side rendering.
 * Delegates to AlmaSEO_Breadcrumbs_Renderer for actual HTML + Schema output.
 *
 * @package    AlmaSEO
 * @subpackage Blocks\Breadcrumbs
 * @since      8.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Breadcrumbs_Block {

    /* ==================================================================
     * Bootstrap
     * ================================================================*/

    /**
     * Hook block registration into WordPress init.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register' ) );
    }

    /* ==================================================================
     * Block Registration
     * ================================================================*/

    /**
     * Register the almaseo/breadcrumbs block and its editor assets.
     */
    public static function register() {
        if ( ! function_exists( 'register_block_type' ) ) {
            return;
        }

        self::enqueue_assets();

        register_block_type( 'almaseo/breadcrumbs', array(
            'api_version'     => 2,
            'attributes'      => array(
                'separator'   => array(
                    'type'    => 'string',
                    'default' => '>',
                ),
                'homeText'    => array(
                    'type'    => 'string',
                    'default' => 'Home',
                ),
                'showCurrent' => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
                'showSchema'  => array(
                    'type'    => 'boolean',
                    'default' => true,
                ),
            ),
            'editor_script'   => 'almaseo-breadcrumbs-block-editor',
            'editor_style'    => 'almaseo-breadcrumbs-block-editor-css',
            'render_callback' => array( __CLASS__, 'render' ),
        ) );
    }

    /* ==================================================================
     * Asset Registration
     * ================================================================*/

    /**
     * Register (but do not enqueue) editor assets.
     *
     * WordPress enqueues them automatically when the block is used.
     */
    private static function enqueue_assets() {
        $url = defined( 'ALMASEO_URL' ) ? ALMASEO_URL : plugin_dir_url( dirname( __DIR__, 2 ) );
        $ver = defined( 'ALMASEO_PLUGIN_VERSION' ) ? ALMASEO_PLUGIN_VERSION : '8.4.0';

        /* Editor JS */
        wp_register_script(
            'almaseo-breadcrumbs-block-editor',
            $url . 'assets/js/breadcrumbs-block-editor.js',
            array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
            $ver,
            true
        );

        /* Editor CSS */
        wp_register_style(
            'almaseo-breadcrumbs-block-editor-css',
            $url . 'assets/css/breadcrumbs-block-editor.css',
            array(),
            $ver
        );
    }

    /* ==================================================================
     * Server-Side Render
     * ================================================================*/

    /**
     * Render callback for the almaseo/breadcrumbs block.
     *
     * Delegates to AlmaSEO_Breadcrumbs_Renderer which handles the full
     * breadcrumb trail, HTML output, and optional JSON-LD schema markup.
     *
     * @param  array  $attributes Block attributes.
     * @param  string $content    Block inner content (unused — SSR).
     * @return string             HTML output.
     */
    public static function render( $attributes, $content = '' ) {
        if ( ! class_exists( 'AlmaSEO_Breadcrumbs_Renderer' ) ) {
            return '';
        }

        return AlmaSEO_Breadcrumbs_Renderer::render( array(
            'separator'    => isset( $attributes['separator'] )   ? $attributes['separator']   : '>',
            'home_text'    => isset( $attributes['homeText'] )    ? $attributes['homeText']    : 'Home',
            'show_current' => ! empty( $attributes['showCurrent'] ) ? 'yes' : 'no',
            'schema'       => ! empty( $attributes['showSchema'] )  ? 'yes' : 'no',
        ) );
    }
}
