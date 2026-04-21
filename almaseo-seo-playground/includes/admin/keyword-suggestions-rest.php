<?php
/**
 * AlmaSEO Keyword Suggestions — Dashboard REST API
 *
 * Push endpoint for dashboard to send AI keyword recommendations.
 * AJAX endpoint for real-time keyword analysis via dashboard API.
 *
 * @package AlmaSEO
 * @since   8.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Keyword_Suggestions_REST {

    const NS = 'almaseo/v1';

    /**
     * Register REST routes and AJAX handler.
     */
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
        add_action( 'wp_ajax_almaseo_keyword_ai_suggest', array( __CLASS__, 'handle_ai_suggest' ) );
    }

    /**
     * Register REST push endpoint.
     */
    public static function register_routes() {
        register_rest_route( self::NS, '/keyword-suggestions/push', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'push_keywords' ),
            'permission_callback' => 'almaseo_api_auth_check',
            'args'                => array(
                'post_id'  => array( 'type' => 'integer', 'required' => true ),
                'keywords' => array( 'type' => 'array',   'required' => true, 'items' => array( 'type' => 'object' ) ),
            ),
        ) );
    }

    /**
     * Handle dashboard push of AI keyword recommendations.
     */
    public static function push_keywords( WP_REST_Request $request ) {
        $post_id  = absint( $request->get_param( 'post_id' ) );
        $keywords = $request->get_param( 'keywords' );

        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_Error( 'invalid_post', 'Post not found.', array( 'status' => 404 ) );
        }

        if ( ! is_array( $keywords ) || empty( $keywords ) ) {
            return new WP_Error( 'invalid_payload', 'keywords must be a non-empty array.', array( 'status' => 400 ) );
        }

        $sanitized = array();
        $valid_intents     = array( 'informational', 'transactional', 'navigational', 'commercial' );
        $valid_competition = array( 'low', 'medium', 'high' );
        $valid_trends      = array( 'up', 'stable', 'down' );

        foreach ( $keywords as $kw ) {
            if ( empty( $kw['keyword'] ) ) {
                continue;
            }
            $sanitized[] = array(
                'keyword'     => sanitize_text_field( $kw['keyword'] ),
                'volume'      => isset( $kw['volume'] ) ? absint( $kw['volume'] ) : 0,
                'competition' => isset( $kw['competition'] ) && in_array( $kw['competition'], $valid_competition, true ) ? $kw['competition'] : 'medium',
                'intent'      => isset( $kw['intent'] ) && in_array( $kw['intent'], $valid_intents, true ) ? $kw['intent'] : 'informational',
                'relevance'   => isset( $kw['relevance'] ) ? max( 0, min( 1, (float) $kw['relevance'] ) ) : 0.5,
                'trend'       => isset( $kw['trend'] ) && in_array( $kw['trend'], $valid_trends, true ) ? $kw['trend'] : 'stable',
            );
        }

        if ( empty( $sanitized ) ) {
            return new WP_Error( 'no_valid_keywords', 'No valid keywords in payload.', array( 'status' => 400 ) );
        }

        update_post_meta( $post_id, '_almaseo_kw_suggestions', wp_json_encode( $sanitized ) );

        return rest_ensure_response( array(
            'success' => true,
            'count'   => count( $sanitized ),
        ) );
    }

    /**
     * AJAX handler — request real-time AI keyword suggestions from dashboard.
     */
    public static function handle_ai_suggest() {
        check_ajax_referer( 'almaseo_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
        }

        $query       = isset( $_GET['q'] ) ? sanitize_text_field( wp_unslash( $_GET['q'] ) ) : '';
        $post_id     = isset( $_GET['post_id'] ) ? absint( wp_unslash( $_GET['post_id'] ) ) : 0;

        if ( mb_strlen( $query ) < 2 ) {
            wp_send_json_success( array( 'keywords' => array() ) );
        }

        // Check transient cache
        $cache_key = 'almaseo_kw_dash_' . md5( $query . '_' . $post_id );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            wp_send_json_success( array( 'keywords' => $cached ) );
        }

        // Check if connected to dashboard
        $api_key = get_option( 'almaseo_api_key', '' );
        if ( empty( $api_key ) ) {
            // Not connected — return stored push data if available
            if ( $post_id ) {
                $stored = get_post_meta( $post_id, '_almaseo_kw_suggestions', true );
                if ( $stored ) {
                    $decoded = json_decode( $stored, true );
                    if ( is_array( $decoded ) ) {
                        wp_send_json_success( array( 'keywords' => $decoded, 'source' => 'stored' ) );
                    }
                }
            }
            wp_send_json_success( array( 'keywords' => array() ) );
        }

        // Request from dashboard API
        $post_title = $post_id ? get_the_title( $post_id ) : '';
        $focus_kw   = $post_id ? get_post_meta( $post_id, '_almaseo_focus_keyword', true ) : '';

        $response = wp_remote_post( 'https://app.almaseo.com/api/v1/keyword-suggestions/analyze', array(
            'timeout' => 8,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'query'         => $query,
                'post_id'       => $post_id,
                'post_title'    => $post_title,
                'focus_keyword' => $focus_kw,
            ) ),
        ) );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            // Fallback to stored data
            if ( $post_id ) {
                $stored = get_post_meta( $post_id, '_almaseo_kw_suggestions', true );
                if ( $stored ) {
                    $decoded = json_decode( $stored, true );
                    if ( is_array( $decoded ) ) {
                        wp_send_json_success( array( 'keywords' => $decoded, 'source' => 'stored' ) );
                    }
                }
            }
            wp_send_json_success( array( 'keywords' => array() ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        $keywords = isset( $body['keywords'] ) && is_array( $body['keywords'] ) ? $body['keywords'] : array();

        if ( ! empty( $keywords ) ) {
            set_transient( $cache_key, $keywords, HOUR_IN_SECONDS );
            // Also store for the post
            if ( $post_id ) {
                update_post_meta( $post_id, '_almaseo_kw_suggestions', wp_json_encode( $keywords ) );
            }
        }

        wp_send_json_success( array( 'keywords' => $keywords, 'source' => 'dashboard' ) );
    }
}
