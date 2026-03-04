<?php
/**
 * AlmaSEO Google Keyword Suggestions
 *
 * AJAX proxy for Google Suggest API with transient caching.
 * Provides autocomplete suggestions below the focus keyword field.
 *
 * @package AlmaSEO
 * @since   8.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Keyword_Suggestions {

    /**
     * Cache TTL in seconds (1 hour).
     */
    const CACHE_TTL = HOUR_IN_SECONDS;

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'wp_ajax_almaseo_keyword_suggest', array( __CLASS__, 'handle_ajax' ) );
    }

    /**
     * AJAX handler — fetch suggestions from Google Suggest API.
     */
    public static function handle_ajax() {
        check_ajax_referer( 'almaseo_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
        }

        $query = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';

        if ( mb_strlen( $query ) < 2 ) {
            wp_send_json_success( array( 'suggestions' => array() ) );
        }

        // Check transient cache first
        $cache_key = 'almaseo_ks_' . md5( $query );
        $cached    = get_transient( $cache_key );

        if ( false !== $cached ) {
            wp_send_json_success( array( 'suggestions' => $cached ) );
        }

        // Fetch from Google Suggest API
        $url = add_query_arg(
            array(
                'client' => 'firefox',
                'q'      => rawurlencode( $query ),
            ),
            'https://suggestqueries.google.com/complete/search'
        );

        $response = wp_remote_get( $url, array(
            'timeout'    => 5,
            'user-agent' => 'Mozilla/5.0 (WordPress; AlmaSEO)',
        ) );

        if ( is_wp_error( $response ) ) {
            wp_send_json_error( array( 'message' => 'Could not fetch suggestions.' ) );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        $suggestions = array();
        if ( isset( $data[1] ) && is_array( $data[1] ) ) {
            $suggestions = array_slice( $data[1], 0, 10 );
            // Filter out the exact query itself
            $suggestions = array_values( array_filter( $suggestions, function( $s ) use ( $query ) {
                return strtolower( trim( $s ) ) !== strtolower( trim( $query ) );
            } ) );
        }

        // Cache for 1 hour
        set_transient( $cache_key, $suggestions, self::CACHE_TTL );

        wp_send_json_success( array( 'suggestions' => $suggestions ) );
    }
}
