<?php
/**
 * GSC Monitor – REST API
 *
 * Registers endpoints under the `almaseo/v1` namespace:
 *
 *   POST   /gsc-monitor/push            – Dashboard pushes GSC findings.
 *   GET    /gsc-monitor                  – List findings (paginated, filtered).
 *   GET    /gsc-monitor/stats            – Counts by severity, status.
 *   GET    /gsc-monitor/post/<id>        – Findings for a specific post.
 *   PATCH  /gsc-monitor/<id>/resolve     – Mark finding as resolved.
 *   PATCH  /gsc-monitor/<id>/dismiss     – Mark finding as dismissed.
 *   PATCH  /gsc-monitor/<id>/reopen      – Re-open a finding.
 *   POST   /gsc-monitor/bulk             – Bulk resolve/dismiss.
 *   GET    /gsc-monitor/settings         – Get settings.
 *   POST   /gsc-monitor/settings         – Save settings.
 *
 * @package AlmaSEO
 * @since   7.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_GSC_Monitor_REST {

    const NS = 'almaseo/v1';

    /**
     * Register all routes.
     */
    public static function register() {

        /* ── Dashboard push (Basic Auth — NOT tier-gated) ── */
        register_rest_route( self::NS, '/gsc-monitor/push', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'push_findings' ),
            'permission_callback' => 'almaseo_api_auth_check',
            'args'                => array(
                'findings' => array(
                    'type'     => 'array',
                    'required' => true,
                    'items'    => array( 'type' => 'object' ),
                ),
            ),
        ) );

        /* ── List findings ── */
        register_rest_route( self::NS, '/gsc-monitor', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'list_findings' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
            'args'                => array(
                'page'         => array( 'type' => 'integer', 'default' => 1 ),
                'per_page'     => array( 'type' => 'integer', 'default' => 20 ),
                'status'       => array( 'type' => 'string',  'default' => '' ),
                'severity'     => array( 'type' => 'string',  'default' => '' ),
                'finding_type' => array( 'type' => 'string',  'default' => '' ),
                'subtype'      => array( 'type' => 'string',  'default' => '' ),
                'post_id'      => array( 'type' => 'integer', 'default' => 0 ),
                'search'       => array( 'type' => 'string',  'default' => '' ),
                'orderby'      => array( 'type' => 'string',  'default' => 'last_seen' ),
                'order'        => array( 'type' => 'string',  'default' => 'DESC' ),
            ),
        ) );

        /* ── Stats ── */
        register_rest_route( self::NS, '/gsc-monitor/stats', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'get_stats' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
            'args'                => array(
                'finding_type' => array( 'type' => 'string', 'default' => '' ),
            ),
        ) );

        /* ── Post findings ── */
        register_rest_route( self::NS, '/gsc-monitor/post/(?P<id>\d+)', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'get_post_findings' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Resolve / Dismiss / Reopen ── */
        register_rest_route( self::NS, '/gsc-monitor/(?P<id>\d+)/resolve', array(
            'methods'             => 'PATCH',
            'callback'            => array( __CLASS__, 'resolve_finding' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        register_rest_route( self::NS, '/gsc-monitor/(?P<id>\d+)/dismiss', array(
            'methods'             => 'PATCH',
            'callback'            => array( __CLASS__, 'dismiss_finding' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        register_rest_route( self::NS, '/gsc-monitor/(?P<id>\d+)/reopen', array(
            'methods'             => 'PATCH',
            'callback'            => array( __CLASS__, 'reopen_finding' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Bulk ── */
        register_rest_route( self::NS, '/gsc-monitor/bulk', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'bulk_action' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
            'args'                => array(
                'action' => array( 'type' => 'string',  'required' => true ),
                'ids'    => array( 'type' => 'array',   'required' => true, 'items' => array( 'type' => 'integer' ) ),
            ),
        ) );

        /* ── Settings ── */
        register_rest_route( self::NS, '/gsc-monitor/settings', array(
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
                    'alert_threshold_indexation' => array( 'type' => 'integer', 'required' => false ),
                    'alert_threshold_snippet'    => array( 'type' => 'integer', 'required' => false ),
                    'auto_dismiss_days'          => array( 'type' => 'integer', 'required' => false ),
                ),
            ),
        ) );
    }

    /* ──────────────── Permission ── */

    public static function can_manage_pro() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }
        if ( function_exists( 'almaseo_feature_available' ) && ! almaseo_feature_available( 'gsc_monitor' ) ) {
            return new WP_Error( 'pro_required', 'GSC Monitor requires Pro.', array( 'status' => 403 ) );
        }
        return true;
    }

    /* ──────────────── Push ── */

    public static function push_findings( WP_REST_Request $request ) {
        $findings = $request->get_param( 'findings' );

        if ( ! is_array( $findings ) || empty( $findings ) ) {
            return new WP_Error( 'invalid_payload', 'findings must be a non-empty array.', array( 'status' => 400 ) );
        }

        $counts = AlmaSEO_GSC_Monitor_Engine::process_push_batch( $findings );

        // Run auto-dismiss after push.
        AlmaSEO_GSC_Monitor_Engine::maybe_auto_dismiss();

        return rest_ensure_response( $counts );
    }

    /* ──────────────── List ── */

    public static function list_findings( WP_REST_Request $request ) {
        $args = array(
            'page'         => absint( $request['page'] ),
            'per_page'     => min( absint( $request['per_page'] ), 100 ),
            'status'       => sanitize_key( $request['status'] ),
            'severity'     => sanitize_key( $request['severity'] ),
            'finding_type' => sanitize_key( $request['finding_type'] ),
            'subtype'      => sanitize_key( $request['subtype'] ),
            'post_id'      => absint( $request['post_id'] ),
            'search'       => sanitize_text_field( $request['search'] ),
            'orderby'      => sanitize_key( $request['orderby'] ),
            'order'        => strtoupper( sanitize_key( $request['order'] ) ),
        );

        $result = AlmaSEO_GSC_Monitor_Model::get_findings( $args );
        $items  = array_map( array( __CLASS__, 'prepare_item' ), $result['items'] );

        $response = rest_ensure_response( $items );
        $response->header( 'X-WP-Total',      $result['total'] );
        $response->header( 'X-WP-TotalPages', $result['pages'] );

        return $response;
    }

    /* ──────────────── Stats ── */

    public static function get_stats( WP_REST_Request $request ) {
        $finding_type = sanitize_key( $request->get_param( 'finding_type' ) );
        return rest_ensure_response( AlmaSEO_GSC_Monitor_Model::get_stats( $finding_type ) );
    }

    /* ──────────────── Post findings ── */

    public static function get_post_findings( WP_REST_Request $request ) {
        $post_id  = absint( $request['id'] );
        $findings = AlmaSEO_GSC_Monitor_Model::get_findings_for_post( $post_id );
        return rest_ensure_response( array_map( array( __CLASS__, 'prepare_item' ), $findings ) );
    }

    /* ──────────────── Resolve / Dismiss / Reopen ── */

    public static function resolve_finding( WP_REST_Request $request ) {
        $row = AlmaSEO_GSC_Monitor_Model::get_finding( absint( $request['id'] ) );
        if ( ! $row ) {
            return new WP_Error( 'not_found', 'Finding not found.', array( 'status' => 404 ) );
        }

        AlmaSEO_GSC_Monitor_Model::update_finding( $row->id, array(
            'status'      => 'resolved',
            'resolved_at' => current_time( 'mysql', true ),
            'resolved_by' => get_current_user_id(),
        ) );

        return rest_ensure_response( array( 'resolved' => true ) );
    }

    public static function dismiss_finding( WP_REST_Request $request ) {
        $row = AlmaSEO_GSC_Monitor_Model::get_finding( absint( $request['id'] ) );
        if ( ! $row ) {
            return new WP_Error( 'not_found', 'Finding not found.', array( 'status' => 404 ) );
        }

        AlmaSEO_GSC_Monitor_Model::update_finding( $row->id, array(
            'status'      => 'dismissed',
            'resolved_at' => current_time( 'mysql', true ),
            'resolved_by' => get_current_user_id(),
        ) );

        return rest_ensure_response( array( 'dismissed' => true ) );
    }

    public static function reopen_finding( WP_REST_Request $request ) {
        $row = AlmaSEO_GSC_Monitor_Model::get_finding( absint( $request['id'] ) );
        if ( ! $row ) {
            return new WP_Error( 'not_found', 'Finding not found.', array( 'status' => 404 ) );
        }

        AlmaSEO_GSC_Monitor_Model::update_finding( $row->id, array(
            'status'      => 'open',
            'resolved_at' => null,
            'resolved_by' => null,
        ) );

        return rest_ensure_response( array( 'reopened' => true ) );
    }

    /* ──────────────── Bulk ── */

    public static function bulk_action( WP_REST_Request $request ) {
        $action = sanitize_key( $request['action'] );
        $ids    = array_map( 'absint', $request['ids'] );

        if ( empty( $ids ) ) {
            return new WP_Error( 'invalid_ids', 'No IDs provided.', array( 'status' => 400 ) );
        }

        $data = array();
        switch ( $action ) {
            case 'resolve':
                $data = array(
                    'status'      => 'resolved',
                    'resolved_at' => current_time( 'mysql', true ),
                    'resolved_by' => get_current_user_id(),
                );
                break;
            case 'dismiss':
                $data = array(
                    'status'      => 'dismissed',
                    'resolved_at' => current_time( 'mysql', true ),
                    'resolved_by' => get_current_user_id(),
                );
                break;
            default:
                return new WP_Error( 'invalid_action', 'Action must be resolve or dismiss.', array( 'status' => 400 ) );
        }

        $updated = AlmaSEO_GSC_Monitor_Model::bulk_update( $ids, $data );

        return rest_ensure_response( array( 'updated' => $updated ) );
    }

    /* ──────────────── Settings ── */

    public static function get_settings() {
        return rest_ensure_response( AlmaSEO_GSC_Monitor_Engine::get_settings() );
    }

    public static function save_settings( WP_REST_Request $request ) {
        $current = AlmaSEO_GSC_Monitor_Engine::get_settings();

        if ( $request->has_param( 'alert_threshold_indexation' ) ) {
            $current['alert_threshold_indexation'] = max( 1, absint( $request['alert_threshold_indexation'] ) );
        }

        if ( $request->has_param( 'alert_threshold_snippet' ) ) {
            $current['alert_threshold_snippet'] = max( 1, absint( $request['alert_threshold_snippet'] ) );
        }

        if ( $request->has_param( 'auto_dismiss_days' ) ) {
            $current['auto_dismiss_days'] = max( 0, absint( $request['auto_dismiss_days'] ) );
        }

        update_option( 'almaseo_gsc_settings', $current );

        return rest_ensure_response( $current );
    }

    /* ──────────────── Formatting ── */

    private static function prepare_item( $row ) {
        $post = $row->post_id ? get_post( $row->post_id ) : null;

        $context_data = $row->context_data;
        if ( is_string( $context_data ) ) {
            $decoded = json_decode( $context_data, true );
            $context_data = is_array( $decoded ) ? $decoded : array();
        }

        return array(
            'id'              => (int) $row->id,
            'post_id'         => $row->post_id ? (int) $row->post_id : null,
            'post_title'      => $post ? $post->post_title : null,
            'post_edit_link'  => $post ? get_edit_post_link( $post->ID, 'raw' ) : null,
            'permalink'       => $post ? get_permalink( $post->ID ) : null,
            'url'             => $row->url,
            'finding_type'    => $row->finding_type,
            'subtype'         => $row->subtype,
            'type_label'      => AlmaSEO_GSC_Monitor_Engine::get_type_label( $row->finding_type ),
            'subtype_label'   => AlmaSEO_GSC_Monitor_Engine::get_subtype_label( $row->subtype ),
            'severity'        => $row->severity,
            'detected_value'  => $row->detected_value,
            'expected_value'  => $row->expected_value,
            'context_data'    => $context_data,
            'suggestion'      => $row->suggestion,
            'status'          => $row->status,
            'first_seen'      => $row->first_seen,
            'last_seen'       => $row->last_seen,
            'resolved_at'     => $row->resolved_at,
        );
    }
}
