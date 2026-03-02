<?php
/**
 * Refresh Queue – REST API
 *
 * Registers endpoints under the `almaseo/v1` namespace:
 *
 *   POST   /refresh-queue/push             – Dashboard pushes signal scores.
 *   GET    /refresh-queue                   – List queue (paginated, sorted).
 *   GET    /refresh-queue/stats             – Counts by tier / status.
 *   POST   /refresh-queue/recalculate       – Trigger full recalculation.
 *   PATCH  /refresh-queue/<id>/skip         – Skip a post.
 *   PATCH  /refresh-queue/<id>/restore      – Un-skip a post.
 *   GET    /refresh-queue/settings          – Get weight settings.
 *   POST   /refresh-queue/settings          – Save weight settings.
 *
 * @package AlmaSEO
 * @since   7.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Refresh_Queue_REST {

    const NS = 'almaseo/v1';

    /**
     * Register all routes. Hooked to `rest_api_init`.
     */
    public static function register() {

        /* ── Dashboard push (Basic Auth — NOT tier-gated) ── */
        register_rest_route( self::NS, '/refresh-queue/push', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'push_signals' ),
            'permission_callback' => 'almaseo_api_auth_check',
            'args'                => array(
                'signals' => array(
                    'type'     => 'array',
                    'required' => true,
                    'items'    => array( 'type' => 'object' ),
                ),
            ),
        ) );

        /* ── List queue ── */
        register_rest_route( self::NS, '/refresh-queue', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'list_queue' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
            'args'                => array(
                'page'          => array( 'type' => 'integer', 'default' => 1 ),
                'per_page'      => array( 'type' => 'integer', 'default' => 20 ),
                'status'        => array( 'type' => 'string',  'default' => '' ),
                'priority_tier' => array( 'type' => 'string',  'default' => '' ),
                'search'        => array( 'type' => 'string',  'default' => '' ),
                'orderby'       => array( 'type' => 'string',  'default' => 'priority_score' ),
                'order'         => array( 'type' => 'string',  'default' => 'DESC' ),
            ),
        ) );

        /* ── Stats ── */
        register_rest_route( self::NS, '/refresh-queue/stats', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'get_stats' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Recalculate ── */
        register_rest_route( self::NS, '/refresh-queue/recalculate', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'recalculate' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Skip / Restore ── */
        register_rest_route( self::NS, '/refresh-queue/(?P<id>\d+)/skip', array(
            'methods'             => 'PATCH',
            'callback'            => array( __CLASS__, 'skip_item' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        register_rest_route( self::NS, '/refresh-queue/(?P<id>\d+)/restore', array(
            'methods'             => 'PATCH',
            'callback'            => array( __CLASS__, 'restore_item' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Settings ── */
        register_rest_route( self::NS, '/refresh-queue/settings', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_settings' ),
                'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( __CLASS__, 'save_settings' ),
                'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
                'args'                => array(
                    'business_value'    => array( 'type' => 'integer', 'required' => true ),
                    'traffic_decline'   => array( 'type' => 'integer', 'required' => true ),
                    'conversion_intent' => array( 'type' => 'integer', 'required' => true ),
                    'opportunity_size'  => array( 'type' => 'integer', 'required' => true ),
                ),
            ),
        ) );
    }

    /* ──────────────── Permission callbacks ── */

    public static function can_manage_pro() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }
        if ( function_exists( 'almaseo_feature_available' ) && ! almaseo_feature_available( 'refresh_queue' ) ) {
            return new WP_Error( 'pro_required', 'Refresh Queue requires Pro.', array( 'status' => 403 ) );
        }
        return true;
    }

    /* ──────────────── Dashboard push ── */

    /**
     * POST /refresh-queue/push
     *
     * Receive signal scores from the AlmaSEO dashboard.
     * Stores each signal as post meta, then recalculates those posts.
     */
    public static function push_signals( WP_REST_Request $request ) {
        $signals = $request->get_param( 'signals' );

        if ( ! is_array( $signals ) || empty( $signals ) ) {
            return new WP_Error( 'invalid_payload', 'signals must be a non-empty array.', array( 'status' => 400 ) );
        }

        $allowed_keys = array( 'business_value', 'traffic_decline', 'conversion_intent', 'opportunity_size' );
        $updated = 0;

        foreach ( $signals as $entry ) {
            if ( empty( $entry['post_id'] ) ) {
                continue;
            }

            $post_id = absint( $entry['post_id'] );
            $post    = get_post( $post_id );

            if ( ! $post || $post->post_status !== 'publish' ) {
                continue;
            }

            // Store each signal as post meta.
            foreach ( $allowed_keys as $key ) {
                if ( isset( $entry[ $key ] ) ) {
                    $val = min( 100, max( 0, (float) $entry[ $key ] ) );
                    update_post_meta( $post_id, '_almaseo_rq_' . $key, $val );
                }
            }

            // Recalculate this post.
            AlmaSEO_Refresh_Queue_Engine::recalculate_single( $post_id );
            $updated++;
        }

        return rest_ensure_response( array(
            'updated' => $updated,
            'total'   => count( $signals ),
        ) );
    }

    /* ──────────────── List ── */

    /**
     * GET /refresh-queue
     */
    public static function list_queue( WP_REST_Request $request ) {
        $args = array(
            'page'          => absint( $request['page'] ),
            'per_page'      => min( absint( $request['per_page'] ), 100 ),
            'status'        => sanitize_key( $request['status'] ),
            'priority_tier' => sanitize_key( $request['priority_tier'] ),
            'search'        => sanitize_text_field( $request['search'] ),
            'orderby'       => sanitize_key( $request['orderby'] ),
            'order'         => strtoupper( sanitize_key( $request['order'] ) ),
        );

        $result = AlmaSEO_Refresh_Queue_Model::get_items( $args );
        $items  = array_map( array( __CLASS__, 'prepare_item' ), $result['items'] );

        $response = rest_ensure_response( $items );
        $response->header( 'X-WP-Total',      $result['total'] );
        $response->header( 'X-WP-TotalPages', $result['pages'] );

        return $response;
    }

    /* ──────────────── Stats ── */

    /**
     * GET /refresh-queue/stats
     */
    public static function get_stats() {
        return rest_ensure_response( AlmaSEO_Refresh_Queue_Model::get_stats() );
    }

    /* ──────────────── Recalculate ── */

    /**
     * POST /refresh-queue/recalculate
     */
    public static function recalculate() {
        $count = AlmaSEO_Refresh_Queue_Engine::recalculate_all();
        return rest_ensure_response( array( 'scored' => $count ) );
    }

    /* ──────────────── Skip / Restore ── */

    /**
     * PATCH /refresh-queue/{id}/skip
     */
    public static function skip_item( WP_REST_Request $request ) {
        $row = AlmaSEO_Refresh_Queue_Model::get_item( absint( $request['id'] ) );

        if ( ! $row ) {
            return new WP_Error( 'not_found', 'Queue entry not found.', array( 'status' => 404 ) );
        }

        AlmaSEO_Refresh_Queue_Model::update_item( $row->id, array( 'status' => 'skipped' ) );
        return rest_ensure_response( array( 'skipped' => true ) );
    }

    /**
     * PATCH /refresh-queue/{id}/restore
     */
    public static function restore_item( WP_REST_Request $request ) {
        $row = AlmaSEO_Refresh_Queue_Model::get_item( absint( $request['id'] ) );

        if ( ! $row ) {
            return new WP_Error( 'not_found', 'Queue entry not found.', array( 'status' => 404 ) );
        }

        AlmaSEO_Refresh_Queue_Model::update_item( $row->id, array( 'status' => 'queued' ) );
        return rest_ensure_response( array( 'restored' => true ) );
    }

    /* ──────────────── Settings ── */

    /**
     * GET /refresh-queue/settings
     */
    public static function get_settings() {
        return rest_ensure_response( AlmaSEO_Refresh_Queue_Engine::get_weights() );
    }

    /**
     * POST /refresh-queue/settings
     */
    public static function save_settings( WP_REST_Request $request ) {
        $weights = array(
            'business_value'    => absint( $request['business_value'] ),
            'traffic_decline'   => absint( $request['traffic_decline'] ),
            'conversion_intent' => absint( $request['conversion_intent'] ),
            'opportunity_size'  => absint( $request['opportunity_size'] ),
        );

        $total = array_sum( $weights );
        if ( $total <= 0 ) {
            return new WP_Error( 'invalid_weights', 'Weights must sum to a positive number.', array( 'status' => 400 ) );
        }

        update_option( 'almaseo_rq_settings', $weights );

        return rest_ensure_response( AlmaSEO_Refresh_Queue_Engine::get_weights() );
    }

    /* ──────────────── Formatting ── */

    /**
     * Shape a DB row for the REST response.
     */
    private static function prepare_item( $row ) {
        $post = get_post( $row->post_id );

        // Check if a pending refresh draft exists for this post.
        $has_draft = false;
        if ( class_exists( 'AlmaSEO_Refresh_Draft_Model' ) && method_exists( 'AlmaSEO_Refresh_Draft_Model', 'list_drafts' ) ) {
            $drafts = AlmaSEO_Refresh_Draft_Model::list_drafts( array(
                'post_id' => $row->post_id,
                'status'  => 'pending',
                'limit'   => 1,
            ) );
            $has_draft = ! empty( $drafts );
        }

        return array(
            'id'                => (int) $row->id,
            'post_id'           => (int) $row->post_id,
            'post_title'        => $post ? $post->post_title : '(deleted)',
            'post_edit_link'    => $post ? get_edit_post_link( $post->ID, 'raw' ) : '',
            'post_type'         => $post ? $post->post_type : '',
            'priority_score'    => (float) $row->priority_score,
            'business_value'    => (float) $row->business_value,
            'traffic_decline'   => (float) $row->traffic_decline,
            'conversion_intent' => (float) $row->conversion_intent,
            'opportunity_size'  => (float) $row->opportunity_size,
            'priority_tier'     => $row->priority_tier,
            'status'            => $row->status,
            'reason'            => $row->reason,
            'source'            => $row->source,
            'calculated_at'     => $row->calculated_at,
            'has_refresh_draft' => $has_draft,
        );
    }
}
