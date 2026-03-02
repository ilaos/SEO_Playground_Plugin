<?php
/**
 * Refresh Drafts – REST API
 *
 * Registers the following endpoints under the `almaseo/v1` namespace:
 *
 *   GET    /refresh-drafts              – List drafts (paginated).
 *   POST   /refresh-drafts              – Create a new draft.
 *   GET    /refresh-drafts/<id>         – Get a single draft.
 *   POST   /refresh-drafts/<id>/review  – Submit section decisions & apply.
 *   DELETE /refresh-drafts/<id>         – Discard a draft.
 *
 * @package AlmaSEO
 * @since   7.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Refresh_Drafts_REST {

    const NAMESPACE = 'almaseo/v1';

    /**
     * Register routes.  Hooked to `rest_api_init`.
     */
    public static function register() {

        /* ── Collection ── */
        register_rest_route( self::NAMESPACE, '/refresh-drafts', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'list_drafts' ),
                'permission_callback' => array( __CLASS__, 'can_manage' ),
                'args'                => array(
                    'status'  => array( 'type' => 'string',  'default' => '' ),
                    'post_id' => array( 'type' => 'integer', 'default' => 0 ),
                    'page'    => array( 'type' => 'integer', 'default' => 1 ),
                    'per_page' => array( 'type' => 'integer', 'default' => 20 ),
                ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( __CLASS__, 'create_draft' ),
                'permission_callback' => array( __CLASS__, 'can_manage' ),
                'args'                => array(
                    'post_id'          => array( 'type' => 'integer', 'required' => true ),
                    'proposed_content' => array( 'type' => 'string',  'required' => true ),
                    'trigger_source'   => array( 'type' => 'string',  'default'  => 'manual' ),
                ),
            ),
        ) );

        /* ── Single ── */
        register_rest_route( self::NAMESPACE, '/refresh-drafts/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_draft' ),
                'permission_callback' => array( __CLASS__, 'can_manage' ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( __CLASS__, 'delete_draft' ),
                'permission_callback' => array( __CLASS__, 'can_manage' ),
            ),
        ) );

        /* ── Review / Apply ── */
        register_rest_route( self::NAMESPACE, '/refresh-drafts/(?P<id>\d+)/review', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'review_draft' ),
            'permission_callback' => array( __CLASS__, 'can_manage' ),
            'args'                => array(
                'decisions' => array(
                    'type'     => 'object',
                    'required' => true,
                    'description' => 'Map of section key => "accept" | "reject".',
                ),
            ),
        ) );
    }

    /* ────────────────────── permission ── */

    public static function can_manage() {
        return current_user_can( 'edit_posts' );
    }

    /* ────────────────────── callbacks ── */

    /**
     * GET /refresh-drafts
     */
    public static function list_drafts( WP_REST_Request $request ) {
        $per_page = min( absint( $request['per_page'] ), 100 );
        $page     = max( 1, absint( $request['page'] ) );

        $args = array(
            'limit'  => $per_page,
            'offset' => ( $page - 1 ) * $per_page,
        );

        if ( $request['status'] ) {
            $args['status'] = $request['status'];
        }
        if ( $request['post_id'] ) {
            $args['post_id'] = $request['post_id'];
        }

        $rows  = AlmaSEO_Refresh_Draft_Model::list_drafts( $args );
        $total = AlmaSEO_Refresh_Draft_Model::count( $request['status'] ?: null );

        $items = array_map( array( __CLASS__, 'prepare_item' ), $rows );

        $response = rest_ensure_response( $items );
        $response->header( 'X-WP-Total',      $total );
        $response->header( 'X-WP-TotalPages', ceil( $total / $per_page ) );

        return $response;
    }

    /**
     * POST /refresh-drafts
     */
    public static function create_draft( WP_REST_Request $request ) {
        $post_id  = absint( $request['post_id'] );
        $post     = get_post( $post_id );

        if ( ! $post || ! in_array( $post->post_status, array( 'publish', 'draft' ), true ) ) {
            return new WP_Error( 'invalid_post', 'Post not found or not editable.', array( 'status' => 404 ) );
        }

        $original = $post->post_content;
        $proposed = $request['proposed_content'];
        $trigger  = sanitize_key( $request['trigger_source'] );

        $sections = AlmaSEO_Refresh_Engine::diff( $original, $proposed );
        $draft_id = AlmaSEO_Refresh_Draft_Model::create( $post_id, $sections, $trigger );

        if ( ! $draft_id ) {
            return new WP_Error( 'db_error', 'Could not save draft.', array( 'status' => 500 ) );
        }

        return rest_ensure_response( self::prepare_item( AlmaSEO_Refresh_Draft_Model::get( $draft_id ) ) );
    }

    /**
     * GET /refresh-drafts/<id>
     */
    public static function get_draft( WP_REST_Request $request ) {
        $row = AlmaSEO_Refresh_Draft_Model::get( absint( $request['id'] ) );

        if ( ! $row ) {
            return new WP_Error( 'not_found', 'Draft not found.', array( 'status' => 404 ) );
        }

        return rest_ensure_response( self::prepare_item( $row ) );
    }

    /**
     * POST /refresh-drafts/<id>/review
     */
    public static function review_draft( WP_REST_Request $request ) {
        $row = AlmaSEO_Refresh_Draft_Model::get( absint( $request['id'] ) );

        if ( ! $row ) {
            return new WP_Error( 'not_found', 'Draft not found.', array( 'status' => 404 ) );
        }

        if ( $row->status === 'applied' ) {
            return new WP_Error( 'already_applied', 'This draft has already been applied.', array( 'status' => 400 ) );
        }

        // Decode stored sections.
        $sections  = json_decode( $row->sections_json, true );
        $decisions = (array) $request['decisions'];

        // Apply decisions.
        foreach ( $sections as &$sec ) {
            if ( isset( $decisions[ $sec['key'] ] ) ) {
                $sec['decision'] = in_array( $decisions[ $sec['key'] ], array( 'accept', 'reject' ), true )
                    ? $decisions[ $sec['key'] ]
                    : 'reject';
            }
        }
        unset( $sec );

        // Merge into final HTML.
        $merged = AlmaSEO_Refresh_Engine::merge( $sections );

        // Update the post.
        $post_update = wp_update_post( array(
            'ID'           => $row->post_id,
            'post_content' => $merged,
        ), true );

        if ( is_wp_error( $post_update ) ) {
            return $post_update;
        }

        // Persist to our table.
        AlmaSEO_Refresh_Draft_Model::update( $row->id, array(
            'status'         => 'applied',
            'sections_json'  => wp_json_encode( $sections, JSON_UNESCAPED_UNICODE ),
            'merged_content' => $merged,
            'reviewed_at'    => current_time( 'mysql', true ),
            'reviewed_by'    => get_current_user_id(),
        ) );

        return rest_ensure_response( self::prepare_item( AlmaSEO_Refresh_Draft_Model::get( $row->id ) ) );
    }

    /**
     * DELETE /refresh-drafts/<id>
     */
    public static function delete_draft( WP_REST_Request $request ) {
        $row = AlmaSEO_Refresh_Draft_Model::get( absint( $request['id'] ) );

        if ( ! $row ) {
            return new WP_Error( 'not_found', 'Draft not found.', array( 'status' => 404 ) );
        }

        AlmaSEO_Refresh_Draft_Model::update( $row->id, array( 'status' => 'dismissed' ) );

        return rest_ensure_response( array( 'deleted' => true ) );
    }

    /* ────────────────────── formatting ── */

    /**
     * Shape a DB row for the REST response.
     */
    private static function prepare_item( $row ) {
        $post  = get_post( $row->post_id );
        $sections = json_decode( $row->sections_json, true );

        $changed_count = 0;
        if ( is_array( $sections ) ) {
            foreach ( $sections as $s ) {
                if ( ! empty( $s['changed'] ) ) {
                    $changed_count++;
                }
            }
        }

        return array(
            'id'             => (int) $row->id,
            'post_id'        => (int) $row->post_id,
            'post_title'     => $post ? $post->post_title : '(deleted)',
            'post_edit_link' => $post ? get_edit_post_link( $post->ID, 'raw' ) : '',
            'status'         => $row->status,
            'trigger_source' => $row->trigger_source,
            'sections'       => $sections,
            'section_count'  => is_array( $sections ) ? count( $sections ) : 0,
            'changed_count'  => $changed_count,
            'created_at'     => $row->created_at,
            'reviewed_at'    => $row->reviewed_at,
            'reviewed_by'    => (int) $row->reviewed_by,
        );
    }
}
