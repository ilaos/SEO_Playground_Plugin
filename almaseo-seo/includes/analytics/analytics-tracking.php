<?php
/**
 * AlmaSEO Google Analytics — Frontend Tracking
 *
 * Enqueues the GA4 gtag.js loader (async) plus its inline config in the page
 * head when a valid Measurement ID is configured.
 *
 * @package AlmaSEO
 * @since   8.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Analytics_Tracking {

    /**
     * Script handle for the gtag.js loader.
     */
    const HANDLE = 'almaseo-ga-gtag';

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'wp_enqueue_scripts', array( __CLASS__, 'enqueue_tracking_code' ), 1 );
        add_filter( 'script_loader_tag', array( __CLASS__, 'add_async_attribute' ), 10, 2 );
    }

    /**
     * Enqueue the gtag.js loader and its inline config if a measurement ID is
     * set and conditions are met.
     */
    public static function enqueue_tracking_code() {
        // Front end only (wp_enqueue_scripts already excludes admin, kept for safety).
        if ( is_admin() ) {
            return;
        }

        $settings = AlmaSEO_Analytics_Settings::get_settings();
        $mid      = trim( $settings['measurement_id'] );

        // Must have a valid-looking Measurement ID
        if ( empty( $mid ) || ! preg_match( '/^G-[A-Za-z0-9]+$/', $mid ) ) {
            return;
        }

        // Optionally exclude logged-in administrators
        if ( $settings['exclude_logged_in'] && is_user_logged_in() && current_user_can( 'manage_options' ) ) {
            return;
        }

        // Load the GA4 library (async attribute added via add_async_attribute()).
        // phpcs:disable WordPress.WP.EnqueuedResourceParameters.MissingVersion -- external Google-hosted library; its version is controlled by Google, so we intentionally pass null (no ?ver appended)
        wp_enqueue_script(
            self::HANDLE,
            'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode( $mid ),
            array(),
            null,
            false
        );
        // phpcs:enable WordPress.WP.EnqueuedResourceParameters.MissingVersion

        $config_params = array();
        if ( $settings['anonymize_ip'] ) {
            $config_params['anonymize_ip'] = true;
        }
        // Server-controlled array, no user input.
        $config_json = ! empty( $config_params ) ? ', ' . wp_json_encode( $config_params ) : '';

        $inline  = "window.dataLayer = window.dataLayer || [];\n";
        $inline .= "function gtag(){dataLayer.push(arguments);}\n";
        $inline .= "gtag('js', new Date());\n";
        $inline .= "gtag('config', '" . esc_js( $mid ) . "'" . $config_json . ");\n";

        if ( $settings['track_link_clicks'] ) {
            $inline .= "document.addEventListener('click', function(e) {\n";
            $inline .= "    var link = e.target.closest('a');\n";
            $inline .= "    if (!link) return;\n";
            $inline .= "    var href = link.getAttribute('href');\n";
            $inline .= "    if (!href) return;\n";
            $inline .= "    try {\n";
            $inline .= "        var url = new URL(href, window.location.origin);\n";
            $inline .= "        if (url.hostname !== window.location.hostname) {\n";
            $inline .= "            gtag('event', 'click', {\n";
            $inline .= "                event_category: 'outbound',\n";
            $inline .= "                event_label: href,\n";
            $inline .= "                transport_type: 'beacon'\n";
            $inline .= "            });\n";
            $inline .= "        }\n";
            $inline .= "    } catch(err) {}\n";
            $inline .= "});\n";
        }

        wp_add_inline_script( self::HANDLE, $inline, 'after' );
    }

    /**
     * Add the async attribute to the gtag.js loader tag (WP 5.6-compatible;
     * the wp_enqueue_script() 'strategy' arg requires WP 6.3+).
     *
     * @param string $tag    The full script HTML tag.
     * @param string $handle The script's registered handle.
     * @return string
     */
    public static function add_async_attribute( $tag, $handle ) {
        if ( self::HANDLE === $handle && false === strpos( $tag, ' async' ) ) {
            $tag = str_replace( ' src=', ' async src=', $tag );
        }
        return $tag;
    }
}
