<?php
/**
 * AlmaSEO Cornerstone Content — Dashboard REST API
 *
 * Push endpoint for dashboard to suggest cornerstone content based on
 * traffic, backlinks, word count, and internal link analysis.
 *
 * @package AlmaSEO
 * @since   8.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Cornerstone_REST {

    const NS = 'almaseo/v1';

    /**
     * Register REST routes.
     */
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    /**
     * Register REST push endpoint.
     */
    public static function register_routes() {
        register_rest_route( self::NS, '/cornerstone/push', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'push_suggestions' ),
            'permission_callback' => 'almaseo_api_auth_check',
            'args'                => array(
                'suggestions' => array( 'type' => 'array', 'required' => true, 'items' => array( 'type' => 'object' ) ),
            ),
        ) );
    }

    /**
     * Handle dashboard push of cornerstone content suggestions.
     */
    public static function push_suggestions( WP_REST_Request $request ) {
        $suggestions = $request->get_param( 'suggestions' );

        if ( ! is_array( $suggestions ) || empty( $suggestions ) ) {
            return new WP_Error( 'invalid_payload', 'suggestions must be a non-empty array.', array( 'status' => 400 ) );
        }

        $processed = 0;

        foreach ( $suggestions as $s ) {
            if ( empty( $s['post_id'] ) ) {
                continue;
            }

            $post_id = absint( $s['post_id'] );
            $post    = get_post( $post_id );
            if ( ! $post || $post->post_status !== 'publish' ) {
                continue;
            }

            // Store suggestion meta
            update_post_meta( $post_id, '_almaseo_cornerstone_suggested', 1 );
            update_post_meta( $post_id, '_almaseo_cornerstone_score', isset( $s['score'] ) ? max( 0, min( 100, (float) $s['score'] ) ) : 50 );
            update_post_meta( $post_id, '_almaseo_cornerstone_reason', sanitize_text_field( $s['reason'] ?? '' ) );

            if ( ! empty( $s['metrics'] ) && is_array( $s['metrics'] ) ) {
                $metrics = array(
                    'traffic'        => absint( $s['metrics']['traffic'] ?? 0 ),
                    'backlinks'      => absint( $s['metrics']['backlinks'] ?? 0 ),
                    'word_count'     => absint( $s['metrics']['word_count'] ?? 0 ),
                    'internal_links' => absint( $s['metrics']['internal_links'] ?? 0 ),
                );
                update_post_meta( $post_id, '_almaseo_cornerstone_metrics', wp_json_encode( $metrics ) );
            }

            $processed++;
        }

        return rest_ensure_response( array(
            'success'   => true,
            'processed' => $processed,
        ) );
    }
}
