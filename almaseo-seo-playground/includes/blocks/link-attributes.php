<?php
/**
 * AlmaSEO Link Attributes for Block Editor
 *
 * Adds nofollow, sponsored, and ugc rel-attribute toggles
 * to the Gutenberg rich-text link toolbar.
 *
 * @package AlmaSEO
 * @since   8.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Link_Attributes {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'enqueue_block_editor_assets', array( __CLASS__, 'enqueue' ) );
    }

    /**
     * Enqueue the editor-only script that registers the format type.
     */
    public static function enqueue() {
        wp_enqueue_script(
            'almaseo-link-attributes',
            ALMASEO_URL . 'assets/js/link-attributes-editor.js',
            array( 'wp-rich-text', 'wp-element', 'wp-components', 'wp-hooks', 'wp-compose', 'wp-block-editor', 'wp-data', 'wp-i18n' ),
            ALMASEO_PLUGIN_VERSION,
            true
        );

        wp_enqueue_style(
            'almaseo-link-attributes',
            ALMASEO_URL . 'assets/css/link-attributes-editor.css',
            array(),
            ALMASEO_PLUGIN_VERSION
        );
    }
}
