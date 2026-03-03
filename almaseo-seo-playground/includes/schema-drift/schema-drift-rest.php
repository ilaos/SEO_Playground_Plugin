<?php
/**
 * Schema Drift Monitor – REST API
 *
 * Registers endpoints under the `almaseo/v1` namespace:
 *
 *   POST   /schema-drift/push               – Dashboard pushes findings.
 *   GET    /schema-drift                     – List findings (paginated, filtered).
 *   GET    /schema-drift/stats               – Counts by severity, status, type.
 *   POST   /schema-drift/baseline            – Capture baselines for monitored posts.
 *   POST   /schema-drift/scan               – Trigger drift scan.
 *   PATCH  /schema-drift/<id>/resolve        – Mark finding as resolved.
 *   PATCH  /schema-drift/<id>/dismiss        – Mark finding as dismissed.
 *   PATCH  /schema-drift/<id>/reopen         – Re-open a finding.
 *   GET    /schema-drift/baselines           – List baseline entries.
 *   GET    /schema-drift/settings            – Get settings.
 *   POST   /schema-drift/settings            – Save settings.
 *
 * @package AlmaSEO
 * @since   7.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Schema_Drift_REST {

    const NS = 'almaseo/v1';

    /**
     * Register all routes. Hooked to `rest_api_init`.
     */
    public static function register() {

        /* ── Dashboard push (Basic Auth — NOT tier-gated) ── */
        register_rest_route( self::NS, '/schema-drift/push', array(
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
        register_rest_route( self::NS, '/schema-drift', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'list_findings' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
            'args'                => array(
                'page'       => array( 'type' => 'integer', 'default' => 1 ),
                'per_page'   => array( 'type' => 'integer', 'default' => 20 ),
                'status'     => array( 'type' => 'string',  'default' => '' ),
                'severity'   => array( 'type' => 'string',  'default' => '' ),
                'drift_type' => array( 'type' => 'string',  'default' => '' ),
                'search'     => array( 'type' => 'string',  'default' => '' ),
            ),
        ) );

        /* ── Stats ── */
        register_rest_route( self::NS, '/schema-drift/stats', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'get_stats' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Capture baselines ── */
        register_rest_route( self::NS, '/schema-drift/baseline', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'capture_baselines' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Scan for drift ── */
        register_rest_route( self::NS, '/schema-drift/scan', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'trigger_scan' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Resolve / Dismiss / Reopen ── */
        register_rest_route( self::NS, '/schema-drift/(?P<id>\d+)/resolve', array(
            'methods'             => 'PATCH',
            'callback'            => array( __CLASS__, 'resolve_finding' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        register_rest_route( self::NS, '/schema-drift/(?P<id>\d+)/dismiss', array(
            'methods'             => 'PATCH',
            'callback'            => array( __CLASS__, 'dismiss_finding' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        register_rest_route( self::NS, '/schema-drift/(?P<id>\d+)/reopen', array(
            'methods'             => 'PATCH',
            'callback'            => array( __CLASS__, 'reopen_finding' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Baselines list ── */
        register_rest_route( self::NS, '/schema-drift/baselines', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'list_baselines' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Settings ── */
        register_rest_route( self::NS, '/schema-drift/settings', array(
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
                    'auto_scan_on_update'  => array( 'type' => 'boolean', 'required' => false ),
                    'monitored_post_types' => array( 'type' => 'array',   'required' => false, 'items' => array( 'type' => 'string' ) ),
                    'scan_sample_size'     => array( 'type' => 'integer', 'required' => false ),
                ),
            ),
        ) );
    }

    /* ──────────────── Permission callbacks ── */

    public static function can_manage_pro() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }
        if ( function_exists( 'almaseo_feature_available' ) && ! almaseo_feature_available( 'schema_drift' ) ) {
            return new WP_Error( 'pro_required', 'Schema Drift Monitor requires Pro.', array( 'status' => 403 ) );
        }
        return true;
    }

    /* ──────────────── Dashboard push ── */

    /**
     * POST /schema-drift/push
     */
    public static function push_findings( WP_REST_Request $request ) {
        $findings = $request->get_param( 'findings' );

        if ( ! is_array( $findings ) || empty( $findings ) ) {
            return new WP_Error( 'invalid_payload', 'findings must be a non-empty array.', array( 'status' => 400 ) );
        }

        $allowed_types = array( 'schema_removed', 'schema_added', 'schema_modified', 'schema_error' );
        $inserted = 0;

        foreach ( $findings as $entry ) {
            if ( empty( $entry['post_id'] ) || empty( $entry['drift_type'] ) ) {
                continue;
            }

            if ( ! in_array( $entry['drift_type'], $allowed_types, true ) ) {
                continue;
            }

            $post_id = absint( $entry['post_id'] );
            $post    = get_post( $post_id );
            if ( ! $post || $post->post_status !== 'publish' ) {
                continue;
            }

            $finding = array(
                'post_id'        => $post_id,
                'url'            => isset( $entry['url'] ) ? esc_url_raw( $entry['url'] ) : get_permalink( $post_id ),
                'drift_type'     => sanitize_key( $entry['drift_type'] ),
                'schema_type'    => isset( $entry['schema_type'] ) ? sanitize_text_field( $entry['schema_type'] ) : '',
                'severity'       => isset( $entry['severity'] ) ? sanitize_key( $entry['severity'] ) : 'medium',
                'baseline_value' => isset( $entry['baseline_value'] ) ? wp_json_encode( $entry['baseline_value'] ) : null,
                'current_value'  => isset( $entry['current_value'] ) ? wp_json_encode( $entry['current_value'] ) : null,
                'diff_summary'   => isset( $entry['diff_summary'] ) ? sanitize_text_field( $entry['diff_summary'] ) : '',
                'suggestion'     => isset( $entry['suggestion'] ) ? sanitize_text_field( $entry['suggestion'] ) : '',
            );

            if ( AlmaSEO_Schema_Drift_Model::insert_finding( $finding ) ) {
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
     * GET /schema-drift
     */
    public static function list_findings( WP_REST_Request $request ) {
        $args = array(
            'page'       => absint( $request['page'] ),
            'per_page'   => min( absint( $request['per_page'] ), 100 ),
            'status'     => sanitize_key( $request['status'] ),
            'severity'   => sanitize_key( $request['severity'] ),
            'drift_type' => sanitize_key( $request['drift_type'] ),
            'search'     => sanitize_text_field( $request['search'] ),
        );

        $result = AlmaSEO_Schema_Drift_Model::get_findings( $args );
        $items  = array_map( array( __CLASS__, 'prepare_item' ), $result['items'] );

        $response = rest_ensure_response( $items );
        $response->header( 'X-WP-Total',      $result['total'] );
        $response->header( 'X-WP-TotalPages', $result['pages'] );

        return $response;
    }

    /* ──────────────── Stats ── */

    /**
     * GET /schema-drift/stats
     */
    public static function get_stats() {
        $stats = AlmaSEO_Schema_Drift_Model::get_stats();
        $stats['last_scan']          = get_option( 'almaseo_sd_last_scan', '' );
        $stats['last_baseline']      = get_option( 'almaseo_sd_last_baseline', '' );
        $stats['baseline_posts']     = AlmaSEO_Schema_Drift_Model::count_baseline_posts();
        $stats['baseline_schemas']   = AlmaSEO_Schema_Drift_Model::count_baselines();
        return rest_ensure_response( $stats );
    }

    /* ──────────────── Baseline capture ── */

    /**
     * POST /schema-drift/baseline
     */
    public static function capture_baselines() {
        $result = AlmaSEO_Schema_Drift_Engine::capture_all_baselines();
        return rest_ensure_response( $result );
    }

    /* ──────────────── Scan ── */

    /**
     * POST /schema-drift/scan
     */
    public static function trigger_scan() {
        if ( AlmaSEO_Schema_Drift_Model::count_baselines() === 0 ) {
            return new WP_Error(
                'no_baselines',
                'No baselines captured yet. Capture baselines first before scanning for drift.',
                array( 'status' => 400 )
            );
        }

        $result = AlmaSEO_Schema_Drift_Engine::scan_for_drift();
        return rest_ensure_response( $result );
    }

    /* ──────────────── Resolve / Dismiss / Reopen ── */

    public static function resolve_finding( WP_REST_Request $request ) {
        $row = AlmaSEO_Schema_Drift_Model::get_finding( absint( $request['id'] ) );
        if ( ! $row ) {
            return new WP_Error( 'not_found', 'Finding not found.', array( 'status' => 404 ) );
        }

        AlmaSEO_Schema_Drift_Model::update_finding( $row->id, array(
            'status'      => 'resolved',
            'resolved_at' => current_time( 'mysql', true ),
            'resolved_by' => get_current_user_id(),
        ) );

        return rest_ensure_response( array( 'resolved' => true ) );
    }

    public static function dismiss_finding( WP_REST_Request $request ) {
        $row = AlmaSEO_Schema_Drift_Model::get_finding( absint( $request['id'] ) );
        if ( ! $row ) {
            return new WP_Error( 'not_found', 'Finding not found.', array( 'status' => 404 ) );
        }

        AlmaSEO_Schema_Drift_Model::update_finding( $row->id, array(
            'status'      => 'dismissed',
            'resolved_at' => current_time( 'mysql', true ),
            'resolved_by' => get_current_user_id(),
        ) );

        return rest_ensure_response( array( 'dismissed' => true ) );
    }

    public static function reopen_finding( WP_REST_Request $request ) {
        $row = AlmaSEO_Schema_Drift_Model::get_finding( absint( $request['id'] ) );
        if ( ! $row ) {
            return new WP_Error( 'not_found', 'Finding not found.', array( 'status' => 404 ) );
        }

        AlmaSEO_Schema_Drift_Model::update_finding( $row->id, array(
            'status'      => 'open',
            'resolved_at' => null,
            'resolved_by' => null,
        ) );

        return rest_ensure_response( array( 'reopened' => true ) );
    }

    /* ──────────────── Baselines ── */

    /**
     * GET /schema-drift/baselines
     */
    public static function list_baselines() {
        $baselines = AlmaSEO_Schema_Drift_Model::get_all_baselines();

        $items = array_map( function ( $bl ) {
            $post = get_post( $bl->post_id );
            return array(
                'id'          => (int) $bl->id,
                'post_id'     => (int) $bl->post_id,
                'post_title'  => $post ? $post->post_title : '(deleted)',
                'url'         => $bl->url,
                'schema_type' => $bl->schema_type,
                'captured_at' => $bl->captured_at,
            );
        }, $baselines );

        return rest_ensure_response( $items );
    }

    /* ──────────────── Settings ── */

    public static function get_settings() {
        return rest_ensure_response( AlmaSEO_Schema_Drift_Engine::get_settings() );
    }

    public static function save_settings( WP_REST_Request $request ) {
        $current = AlmaSEO_Schema_Drift_Engine::get_settings();

        if ( $request->has_param( 'auto_scan_on_update' ) ) {
            $current['auto_scan_on_update'] = (bool) $request['auto_scan_on_update'];
        }

        if ( $request->has_param( 'monitored_post_types' ) ) {
            $types = $request['monitored_post_types'];
            $current['monitored_post_types'] = is_array( $types )
                ? array_values( array_map( 'sanitize_key', $types ) )
                : array( 'post', 'page' );
        }

        if ( $request->has_param( 'scan_sample_size' ) ) {
            $current['scan_sample_size'] = max( 1, min( 50, absint( $request['scan_sample_size'] ) ) );
        }

        update_option( 'almaseo_sd_settings', $current );

        return rest_ensure_response( $current );
    }

    /* ──────────────── Formatting ── */

    private static function prepare_item( $row ) {
        $post = get_post( $row->post_id );

        return array(
            'id'              => (int) $row->id,
            'post_id'         => (int) $row->post_id,
            'post_title'      => $post ? $post->post_title : '(deleted)',
            'post_edit_link'  => $post ? get_edit_post_link( $post->ID, 'raw' ) : '',
            'url'             => $row->url,
            'drift_type'      => $row->drift_type,
            'schema_type'     => $row->schema_type,
            'severity'        => $row->severity,
            'diff_summary'    => $row->diff_summary,
            'suggestion'      => $row->suggestion,
            'status'          => $row->status,
            'detected_at'     => $row->detected_at,
            'resolved_at'     => $row->resolved_at,
        );
    }
}
