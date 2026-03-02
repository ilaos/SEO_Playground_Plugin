<?php
/**
 * Date Hygiene Scanner – REST API
 *
 * Registers endpoints under the `almaseo/v1` namespace:
 *
 *   POST   /date-hygiene/push                – Dashboard pushes NLP findings.
 *   GET    /date-hygiene                     – List findings (paginated, filtered).
 *   GET    /date-hygiene/stats               – Counts by severity, status, type.
 *   POST   /date-hygiene/scan                – Trigger full-site scan.
 *   PATCH  /date-hygiene/<id>/resolve        – Mark finding as resolved.
 *   PATCH  /date-hygiene/<id>/dismiss        – Mark finding as dismissed.
 *   PATCH  /date-hygiene/<id>/reopen         – Re-open a resolved/dismissed finding.
 *   GET    /date-hygiene/settings            – Get scan settings.
 *   POST   /date-hygiene/settings            – Save scan settings.
 *
 * @package AlmaSEO
 * @since   7.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Date_Hygiene_REST {

    const NS = 'almaseo/v1';

    /**
     * Register all routes. Hooked to `rest_api_init`.
     */
    public static function register() {

        /* ── Dashboard push (Basic Auth — NOT tier-gated) ── */
        register_rest_route( self::NS, '/date-hygiene/push', array(
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
        register_rest_route( self::NS, '/date-hygiene', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'list_findings' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
            'args'                => array(
                'page'         => array( 'type' => 'integer', 'default' => 1 ),
                'per_page'     => array( 'type' => 'integer', 'default' => 20 ),
                'status'       => array( 'type' => 'string',  'default' => '' ),
                'severity'     => array( 'type' => 'string',  'default' => '' ),
                'finding_type' => array( 'type' => 'string',  'default' => '' ),
                'post_id'      => array( 'type' => 'integer', 'default' => 0 ),
                'search'       => array( 'type' => 'string',  'default' => '' ),
                'orderby'      => array( 'type' => 'string',  'default' => 'severity' ),
                'order'        => array( 'type' => 'string',  'default' => 'DESC' ),
            ),
        ) );

        /* ── Stats ── */
        register_rest_route( self::NS, '/date-hygiene/stats', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'get_stats' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Scan ── */
        register_rest_route( self::NS, '/date-hygiene/scan', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'trigger_scan' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Resolve / Dismiss / Reopen ── */
        register_rest_route( self::NS, '/date-hygiene/(?P<id>\d+)/resolve', array(
            'methods'             => 'PATCH',
            'callback'            => array( __CLASS__, 'resolve_finding' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        register_rest_route( self::NS, '/date-hygiene/(?P<id>\d+)/dismiss', array(
            'methods'             => 'PATCH',
            'callback'            => array( __CLASS__, 'dismiss_finding' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        register_rest_route( self::NS, '/date-hygiene/(?P<id>\d+)/reopen', array(
            'methods'             => 'PATCH',
            'callback'            => array( __CLASS__, 'reopen_finding' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Settings ── */
        register_rest_route( self::NS, '/date-hygiene/settings', array(
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
                    'stale_threshold'  => array( 'type' => 'integer', 'required' => false ),
                    'scan_prices'      => array( 'type' => 'boolean', 'required' => false ),
                    'scan_regulations' => array( 'type' => 'boolean', 'required' => false ),
                ),
            ),
        ) );
    }

    /* ──────────────── Permission callbacks ── */

    public static function can_manage_pro() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }
        if ( function_exists( 'almaseo_feature_available' ) && ! almaseo_feature_available( 'date_hygiene' ) ) {
            return new WP_Error( 'pro_required', 'Date Hygiene Scanner requires Pro.', array( 'status' => 403 ) );
        }
        return true;
    }

    /* ──────────────── Dashboard push ── */

    /**
     * POST /date-hygiene/push
     *
     * Receive NLP-detected findings from the AlmaSEO dashboard.
     */
    public static function push_findings( WP_REST_Request $request ) {
        $findings = $request->get_param( 'findings' );

        if ( ! is_array( $findings ) || empty( $findings ) ) {
            return new WP_Error( 'invalid_payload', 'findings must be a non-empty array.', array( 'status' => 400 ) );
        }

        $allowed_types = array( 'stale_year', 'dated_phrase', 'superlative_year', 'price_reference', 'regulation_mention' );
        $inserted = 0;

        foreach ( $findings as $entry ) {
            if ( empty( $entry['post_id'] ) || empty( $entry['finding_type'] ) || empty( $entry['detected_value'] ) ) {
                continue;
            }

            $post_id = absint( $entry['post_id'] );
            $post    = get_post( $post_id );
            if ( ! $post || $post->post_status !== 'publish' ) {
                continue;
            }

            if ( ! in_array( $entry['finding_type'], $allowed_types, true ) ) {
                continue;
            }

            $finding = array(
                'post_id'        => $post_id,
                'finding_type'   => sanitize_key( $entry['finding_type'] ),
                'severity'       => isset( $entry['severity'] ) ? sanitize_key( $entry['severity'] ) : 'medium',
                'detected_value' => sanitize_text_field( $entry['detected_value'] ),
                'context_snippet' => isset( $entry['context_snippet'] ) ? wp_kses_post( $entry['context_snippet'] ) : '',
                'suggestion'     => isset( $entry['suggestion'] ) ? sanitize_text_field( $entry['suggestion'] ) : '',
                'location'       => isset( $entry['location'] ) ? sanitize_key( $entry['location'] ) : 'body',
            );

            if ( AlmaSEO_Date_Hygiene_Model::insert_finding( $finding ) ) {
                $inserted++;
            }
        }

        return rest_ensure_response( array(
            'inserted' => $inserted,
            'total'    => count( $findings ),
        ) );
    }

    /* ──────────────── List ── */

    /**
     * GET /date-hygiene
     */
    public static function list_findings( WP_REST_Request $request ) {
        $args = array(
            'page'         => absint( $request['page'] ),
            'per_page'     => min( absint( $request['per_page'] ), 100 ),
            'status'       => sanitize_key( $request['status'] ),
            'severity'     => sanitize_key( $request['severity'] ),
            'finding_type' => sanitize_key( $request['finding_type'] ),
            'post_id'      => absint( $request['post_id'] ),
            'search'       => sanitize_text_field( $request['search'] ),
            'orderby'      => sanitize_key( $request['orderby'] ),
            'order'        => strtoupper( sanitize_key( $request['order'] ) ),
        );

        $result = AlmaSEO_Date_Hygiene_Model::get_findings( $args );
        $items  = array_map( array( __CLASS__, 'prepare_item' ), $result['items'] );

        $response = rest_ensure_response( $items );
        $response->header( 'X-WP-Total',      $result['total'] );
        $response->header( 'X-WP-TotalPages', $result['pages'] );

        return $response;
    }

    /* ──────────────── Stats ── */

    /**
     * GET /date-hygiene/stats
     */
    public static function get_stats() {
        $stats = AlmaSEO_Date_Hygiene_Model::get_stats();
        $stats['last_scan'] = get_option( 'almaseo_dh_last_scan', '' );
        return rest_ensure_response( $stats );
    }

    /* ──────────────── Scan ── */

    /**
     * POST /date-hygiene/scan
     */
    public static function trigger_scan() {
        $result = AlmaSEO_Date_Hygiene_Engine::scan_all();
        return rest_ensure_response( $result );
    }

    /* ──────────────── Resolve / Dismiss / Reopen ── */

    /**
     * PATCH /date-hygiene/{id}/resolve
     */
    public static function resolve_finding( WP_REST_Request $request ) {
        $row = AlmaSEO_Date_Hygiene_Model::get_finding( absint( $request['id'] ) );

        if ( ! $row ) {
            return new WP_Error( 'not_found', 'Finding not found.', array( 'status' => 404 ) );
        }

        AlmaSEO_Date_Hygiene_Model::update_finding( $row->id, array(
            'status'      => 'resolved',
            'resolved_at' => current_time( 'mysql', true ),
            'resolved_by' => get_current_user_id(),
        ) );

        return rest_ensure_response( array( 'resolved' => true ) );
    }

    /**
     * PATCH /date-hygiene/{id}/dismiss
     */
    public static function dismiss_finding( WP_REST_Request $request ) {
        $row = AlmaSEO_Date_Hygiene_Model::get_finding( absint( $request['id'] ) );

        if ( ! $row ) {
            return new WP_Error( 'not_found', 'Finding not found.', array( 'status' => 404 ) );
        }

        AlmaSEO_Date_Hygiene_Model::update_finding( $row->id, array(
            'status'      => 'dismissed',
            'resolved_at' => current_time( 'mysql', true ),
            'resolved_by' => get_current_user_id(),
        ) );

        return rest_ensure_response( array( 'dismissed' => true ) );
    }

    /**
     * PATCH /date-hygiene/{id}/reopen
     */
    public static function reopen_finding( WP_REST_Request $request ) {
        $row = AlmaSEO_Date_Hygiene_Model::get_finding( absint( $request['id'] ) );

        if ( ! $row ) {
            return new WP_Error( 'not_found', 'Finding not found.', array( 'status' => 404 ) );
        }

        AlmaSEO_Date_Hygiene_Model::update_finding( $row->id, array(
            'status'      => 'open',
            'resolved_at' => null,
            'resolved_by' => null,
        ) );

        return rest_ensure_response( array( 'reopened' => true ) );
    }

    /* ──────────────── Settings ── */

    /**
     * GET /date-hygiene/settings
     */
    public static function get_settings() {
        return rest_ensure_response( AlmaSEO_Date_Hygiene_Engine::get_settings() );
    }

    /**
     * POST /date-hygiene/settings
     */
    public static function save_settings( WP_REST_Request $request ) {
        $current = AlmaSEO_Date_Hygiene_Engine::get_settings();

        if ( $request->has_param( 'stale_threshold' ) ) {
            $current['stale_threshold'] = max( 1, absint( $request['stale_threshold'] ) );
        }

        if ( $request->has_param( 'scan_prices' ) ) {
            $current['scan_prices'] = (bool) $request['scan_prices'];
        }

        if ( $request->has_param( 'scan_regulations' ) ) {
            $current['scan_regulations'] = (bool) $request['scan_regulations'];
        }

        update_option( 'almaseo_dh_settings', $current );

        return rest_ensure_response( $current );
    }

    /* ──────────────── Formatting ── */

    /**
     * Shape a DB row for the REST response.
     */
    private static function prepare_item( $row ) {
        $post = get_post( $row->post_id );

        return array(
            'id'              => (int) $row->id,
            'post_id'         => (int) $row->post_id,
            'post_title'      => $post ? $post->post_title : '(deleted)',
            'post_edit_link'  => $post ? get_edit_post_link( $post->ID, 'raw' ) : '',
            'post_type'       => $post ? $post->post_type : '',
            'finding_type'    => $row->finding_type,
            'severity'        => $row->severity,
            'detected_value'  => $row->detected_value,
            'context_snippet' => $row->context_snippet,
            'suggestion'      => $row->suggestion,
            'location'        => $row->location,
            'status'          => $row->status,
            'scanned_at'      => $row->scanned_at,
            'resolved_at'     => $row->resolved_at,
        );
    }
}
