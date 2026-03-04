<?php
/**
 * AlmaSEO Headline Analyzer — Dashboard REST API
 *
 * Push endpoint for dashboard to send AI headline analysis.
 * AJAX endpoint for real-time headline analysis via dashboard API.
 *
 * @package AlmaSEO
 * @since   8.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Headline_Analyzer_REST {

    const NS = 'almaseo/v1';

    /**
     * Register REST routes and AJAX handler.
     */
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
        add_action( 'wp_ajax_almaseo_headline_ai_analyze', array( __CLASS__, 'handle_ai_analyze' ) );
    }

    /**
     * Register REST push endpoint.
     */
    public static function register_routes() {
        register_rest_route( self::NS, '/headline-analyzer/push', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'push_analysis' ),
            'permission_callback' => 'almaseo_api_auth_check',
            'args'                => array(
                'post_id'               => array( 'type' => 'integer', 'required' => true ),
                'headline_score'        => array( 'type' => 'integer' ),
                'ctr_potential'         => array( 'type' => 'number' ),
                'emotional_impact'      => array( 'type' => 'string' ),
                'competitor_headlines'  => array( 'type' => 'array' ),
                'rewrite_suggestions'  => array( 'type' => 'array' ),
            ),
        ) );
    }

    /**
     * Handle dashboard push of AI headline analysis.
     */
    public static function push_analysis( WP_REST_Request $request ) {
        $post_id = absint( $request->get_param( 'post_id' ) );
        $post    = get_post( $post_id );

        if ( ! $post ) {
            return new WP_Error( 'invalid_post', 'Post not found.', array( 'status' => 404 ) );
        }

        $data = array(
            'headline_score'       => min( 100, max( 0, absint( $request->get_param( 'headline_score' ) ?: 0 ) ) ),
            'ctr_potential'        => max( 0, min( 100, (float) ( $request->get_param( 'ctr_potential' ) ?: 0 ) ) ),
            'emotional_impact'     => sanitize_text_field( $request->get_param( 'emotional_impact' ) ?: '' ),
            'competitor_headlines' => array(),
            'rewrite_suggestions'  => array(),
            'updated_at'           => current_time( 'mysql', true ),
        );

        $competitors = $request->get_param( 'competitor_headlines' );
        if ( is_array( $competitors ) ) {
            foreach ( array_slice( $competitors, 0, 5 ) as $c ) {
                if ( ! empty( $c['headline'] ) ) {
                    $data['competitor_headlines'][] = array(
                        'headline' => sanitize_text_field( $c['headline'] ),
                        'ctr'      => isset( $c['ctr'] ) ? (float) $c['ctr'] : 0,
                    );
                }
            }
        }

        $rewrites = $request->get_param( 'rewrite_suggestions' );
        if ( is_array( $rewrites ) ) {
            foreach ( array_slice( $rewrites, 0, 5 ) as $r ) {
                if ( is_string( $r ) && ! empty( $r ) ) {
                    $data['rewrite_suggestions'][] = sanitize_text_field( $r );
                }
            }
        }

        update_post_meta( $post_id, '_almaseo_headline_dashboard', wp_json_encode( $data ) );

        return rest_ensure_response( array( 'success' => true ) );
    }

    /**
     * AJAX handler — request real-time AI headline analysis from dashboard.
     */
    public static function handle_ai_analyze() {
        check_ajax_referer( 'almaseo_nonce', 'nonce' );

        if ( ! current_user_can( 'edit_posts' ) ) {
            wp_send_json_error( array( 'message' => 'Unauthorized' ) );
        }

        $headline = isset( $_POST['headline'] ) ? sanitize_text_field( wp_unslash( $_POST['headline'] ) ) : '';
        $post_id  = isset( $_POST['post_id'] ) ? absint( $_POST['post_id'] ) : 0;

        if ( empty( $headline ) ) {
            wp_send_json_error( array( 'message' => 'No headline provided.' ) );
        }

        // Check transient cache
        $cache_key = 'almaseo_hl_dash_' . md5( $headline . '_' . $post_id );
        $cached    = get_transient( $cache_key );
        if ( false !== $cached ) {
            wp_send_json_success( $cached );
        }

        // Check if connected
        $api_key = get_option( 'almaseo_api_key', '' );
        if ( empty( $api_key ) ) {
            // Return stored data if available
            if ( $post_id ) {
                $stored = get_post_meta( $post_id, '_almaseo_headline_dashboard', true );
                if ( $stored ) {
                    $decoded = json_decode( $stored, true );
                    if ( is_array( $decoded ) ) {
                        wp_send_json_success( array_merge( $decoded, array( 'source' => 'stored' ) ) );
                    }
                }
            }
            wp_send_json_success( array( 'available' => false ) );
        }

        $focus_kw = $post_id ? get_post_meta( $post_id, '_almaseo_focus_keyword', true ) : '';

        $response = wp_remote_post( 'https://app.almaseo.com/api/v1/headline/analyze', array(
            'timeout' => 8,
            'headers' => array(
                'Authorization' => 'Bearer ' . $api_key,
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'headline'      => $headline,
                'focus_keyword' => $focus_kw,
                'post_id'       => $post_id,
            ) ),
        ) );

        if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
            // Fallback to stored
            if ( $post_id ) {
                $stored = get_post_meta( $post_id, '_almaseo_headline_dashboard', true );
                if ( $stored ) {
                    $decoded = json_decode( $stored, true );
                    if ( is_array( $decoded ) ) {
                        wp_send_json_success( array_merge( $decoded, array( 'source' => 'stored' ) ) );
                    }
                }
            }
            wp_send_json_success( array( 'available' => false ) );
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $body ) ) {
            wp_send_json_success( array( 'available' => false ) );
        }

        $body['source'] = 'dashboard';
        set_transient( $cache_key, $body, HOUR_IN_SECONDS );

        if ( $post_id ) {
            update_post_meta( $post_id, '_almaseo_headline_dashboard', wp_json_encode( $body ) );
        }

        wp_send_json_success( $body );
    }
}
