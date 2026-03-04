<?php
/**
 * AlmaSEO Webmaster Verification Codes
 *
 * Outputs verification meta tags for Google Search Console, Bing Webmaster
 * Tools, Pinterest, Yandex, and Baidu.
 *
 * @package AlmaSEO
 * @since   8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Verification_Codes {

    const OPTION_KEY = 'almaseo_verification_codes';

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'wp_head', array( __CLASS__, 'output_verification_tags' ), 2 );
    }

    /**
     * Get stored verification codes.
     *
     * @return array
     */
    public static function get_codes() {
        return get_option( self::OPTION_KEY, self::get_defaults() );
    }

    /**
     * Default verification codes (all empty).
     *
     * @return array
     */
    public static function get_defaults() {
        return array(
            'google'    => '',
            'bing'      => '',
            'pinterest' => '',
            'yandex'    => '',
            'baidu'     => '',
        );
    }

    /**
     * Get the display labels for each verification service.
     *
     * @return array
     */
    public static function get_labels() {
        return array(
            'google'    => __( 'Google Search Console', 'almaseo' ),
            'bing'      => __( 'Bing Webmaster Tools', 'almaseo' ),
            'pinterest' => __( 'Pinterest', 'almaseo' ),
            'yandex'    => __( 'Yandex Webmaster', 'almaseo' ),
            'baidu'     => __( 'Baidu Webmaster', 'almaseo' ),
        );
    }

    /**
     * Map service keys to HTML meta name attributes.
     *
     * @return array
     */
    private static function get_meta_names() {
        return array(
            'google'    => 'google-site-verification',
            'bing'      => 'msvalidate.01',
            'pinterest' => 'p:domain_verify',
            'yandex'    => 'yandex-verification',
            'baidu'     => 'baidu-site-verification',
        );
    }

    /**
     * Output verification meta tags in <head>.
     */
    public static function output_verification_tags() {
        $codes      = self::get_codes();
        $meta_names = self::get_meta_names();

        foreach ( $meta_names as $key => $name ) {
            if ( ! empty( $codes[ $key ] ) ) {
                $content = self::extract_content( $codes[ $key ] );
                if ( ! empty( $content ) ) {
                    echo '<meta name="' . esc_attr( $name ) . '" content="' . esc_attr( $content ) . '" />' . "\n";
                }
            }
        }
    }

    /**
     * Extract the content value from a verification code.
     * Users may paste the full <meta> tag or just the content value.
     *
     * @param string $input Raw input.
     * @return string Content value only.
     */
    private static function extract_content( $input ) {
        $input = trim( $input );

        // If it looks like a full meta tag, extract the content attribute.
        if ( preg_match( '/content=["\']([^"\']+)["\']/', $input, $matches ) ) {
            return sanitize_text_field( $matches[1] );
        }

        // Otherwise treat as raw content value.
        return sanitize_text_field( $input );
    }

    /**
     * Sanitize verification codes input.
     *
     * @param array $input Raw input.
     * @return array Sanitized.
     */
    public static function sanitize( $input ) {
        if ( ! is_array( $input ) ) {
            return self::get_defaults();
        }

        $sanitized = array();
        foreach ( array_keys( self::get_defaults() ) as $key ) {
            $sanitized[ $key ] = isset( $input[ $key ] ) ? sanitize_text_field( $input[ $key ] ) : '';
        }

        return $sanitized;
    }
}
