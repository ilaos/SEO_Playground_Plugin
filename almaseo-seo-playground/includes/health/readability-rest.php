<?php
/**
 * AlmaSEO Readability — Dashboard REST API
 *
 * Push endpoint for dashboard to send competitor benchmarks and AI suggestions.
 *
 * @package AlmaSEO
 * @since   8.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Readability_REST {

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
        register_rest_route( self::NS, '/readability/push', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'push_analysis' ),
            'permission_callback' => 'almaseo_api_auth_check',
            'args'                => array(
                'post_id'                  => array( 'type' => 'integer', 'required' => true ),
                'competitor_avg_score'     => array( 'type' => 'number' ),
                'competitor_grade_level'   => array( 'type' => 'string' ),
                'paragraphs'               => array( 'type' => 'array' ),
                'overall_recommendation'   => array( 'type' => 'string' ),
            ),
        ) );
    }

    /**
     * Handle dashboard push of readability analysis with competitor benchmarks.
     */
    public static function push_analysis( WP_REST_Request $request ) {
        $post_id = absint( $request->get_param( 'post_id' ) );
        $post    = get_post( $post_id );

        if ( ! $post ) {
            return new WP_Error( 'invalid_post', 'Post not found.', array( 'status' => 404 ) );
        }

        $valid_severities = array( 'info', 'warning', 'critical' );

        $data = array(
            'competitor_avg_score'   => max( 0, min( 100, (float) ( $request->get_param( 'competitor_avg_score' ) ?: 0 ) ) ),
            'competitor_grade_level' => sanitize_text_field( $request->get_param( 'competitor_grade_level' ) ?: '' ),
            'paragraphs'             => array(),
            'overall_recommendation' => sanitize_text_field( $request->get_param( 'overall_recommendation' ) ?: '' ),
            'updated_at'             => current_time( 'mysql', true ),
        );

        $paragraphs = $request->get_param( 'paragraphs' );
        if ( is_array( $paragraphs ) ) {
            foreach ( array_slice( $paragraphs, 0, 50 ) as $p ) {
                if ( ! isset( $p['index'] ) || empty( $p['suggestion'] ) ) {
                    continue;
                }
                $data['paragraphs'][] = array(
                    'index'      => absint( $p['index'] ),
                    'suggestion' => sanitize_text_field( $p['suggestion'] ),
                    'severity'   => isset( $p['severity'] ) && in_array( $p['severity'], $valid_severities, true ) ? $p['severity'] : 'info',
                );
            }
        }

        update_post_meta( $post_id, '_almaseo_readability_dashboard', wp_json_encode( $data ) );

        return rest_ensure_response( array( 'success' => true ) );
    }
}
