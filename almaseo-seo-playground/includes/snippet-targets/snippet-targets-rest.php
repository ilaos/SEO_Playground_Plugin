<?php
/**
 * Featured Snippet Targeting – REST API
 *
 * Registers endpoints under the `almaseo/v1` namespace:
 *
 *   POST   /snippet-targets/push            – Dashboard pushes opportunities.
 *   GET    /snippet-targets                 – List targets (paginated, filtered).
 *   GET    /snippet-targets/stats           – Counts by status/format.
 *   GET    /snippet-targets/<id>            – Single target detail.
 *   PATCH  /snippet-targets/<id>/draft      – Save draft content.
 *   PATCH  /snippet-targets/<id>/approve    – Approve a draft.
 *   PATCH  /snippet-targets/<id>/reject     – Reject a target.
 *   POST   /snippet-targets/<id>/apply      – Insert into post content.
 *   POST   /snippet-targets/<id>/undo       – Remove from post content.
 *   PATCH  /snippet-targets/<id>/status     – Update status (won/lost/expired).
 *   DELETE /snippet-targets/<id>            – Delete a target.
 *   GET    /snippet-targets/<id>/prompt     – Get format-specific prompt.
 *
 * @package AlmaSEO
 * @since   7.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Snippet_Targets_REST {

    const NS = 'almaseo/v1';

    /**
     * Register all routes. Hooked to `rest_api_init`.
     */
    public static function register() {

        /* ── Dashboard push (Basic Auth — NOT tier-gated) ── */
        register_rest_route( self::NS, '/snippet-targets/push', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'push_targets' ),
            'permission_callback' => 'almaseo_api_auth_check',
            'args'                => array(
                'targets' => array(
                    'type'     => 'array',
                    'required' => true,
                    'items'    => array( 'type' => 'object' ),
                ),
            ),
        ) );

        /* ── List targets ── */
        register_rest_route( self::NS, '/snippet-targets', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'list_targets' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
            'args'                => array(
                'page'           => array( 'type' => 'integer', 'default' => 1 ),
                'per_page'       => array( 'type' => 'integer', 'default' => 20 ),
                'status'         => array( 'type' => 'string',  'default' => '' ),
                'snippet_format' => array( 'type' => 'string',  'default' => '' ),
                'search'         => array( 'type' => 'string',  'default' => '' ),
                'orderby'        => array( 'type' => 'string',  'default' => 'created_at' ),
                'order'          => array( 'type' => 'string',  'default' => 'DESC' ),
            ),
        ) );

        /* ── Stats ── */
        register_rest_route( self::NS, '/snippet-targets/stats', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'get_stats' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Single detail ── */
        register_rest_route( self::NS, '/snippet-targets/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( __CLASS__, 'get_target' ),
                'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( __CLASS__, 'delete_target' ),
                'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
            ),
        ) );

        /* ── Save draft content ── */
        register_rest_route( self::NS, '/snippet-targets/(?P<id>\d+)/draft', array(
            'methods'             => 'PATCH',
            'callback'            => array( __CLASS__, 'save_draft' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
            'args'                => array(
                'draft_content'  => array( 'type' => 'string', 'required' => true ),
                'snippet_format' => array( 'type' => 'string', 'required' => false ),
            ),
        ) );

        /* ── Approve ── */
        register_rest_route( self::NS, '/snippet-targets/(?P<id>\d+)/approve', array(
            'methods'             => 'PATCH',
            'callback'            => array( __CLASS__, 'approve_target' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Reject ── */
        register_rest_route( self::NS, '/snippet-targets/(?P<id>\d+)/reject', array(
            'methods'             => 'PATCH',
            'callback'            => array( __CLASS__, 'reject_target' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Apply (insert into post) ── */
        register_rest_route( self::NS, '/snippet-targets/(?P<id>\d+)/apply', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'apply_target' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Undo (remove from post) ── */
        register_rest_route( self::NS, '/snippet-targets/(?P<id>\d+)/undo', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'undo_target' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );

        /* ── Update status (won/lost/expired) ── */
        register_rest_route( self::NS, '/snippet-targets/(?P<id>\d+)/status', array(
            'methods'             => 'PATCH',
            'callback'            => array( __CLASS__, 'update_status' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
            'args'                => array(
                'status' => array( 'type' => 'string', 'required' => true ),
            ),
        ) );

        /* ── Format-specific prompt ── */
        register_rest_route( self::NS, '/snippet-targets/(?P<id>\d+)/prompt', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( __CLASS__, 'get_prompt' ),
            'permission_callback' => array( __CLASS__, 'can_manage_pro' ),
        ) );
    }

    /* ──────────────── Permission callbacks ── */

    public static function can_manage_pro() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }
        if ( function_exists( 'almaseo_feature_available' ) && ! almaseo_feature_available( 'snippet_targeting' ) ) {
            return new WP_Error( 'pro_required', 'Featured Snippet Targeting requires Pro.', array( 'status' => 403 ) );
        }
        return true;
    }

    /* ──────────────── Dashboard push ── */

    public static function push_targets( WP_REST_Request $request ) {
        $targets = $request->get_param( 'targets' );

        if ( ! is_array( $targets ) || empty( $targets ) ) {
            return new WP_Error( 'invalid_payload', 'targets must be a non-empty array.', array( 'status' => 400 ) );
        }

        $inserted = 0;

        foreach ( $targets as $entry ) {
            if ( empty( $entry['post_id'] ) || empty( $entry['query'] ) ) {
                continue;
            }

            $post_id = absint( $entry['post_id'] );
            $post    = get_post( $post_id );
            if ( ! $post || $post->post_status !== 'publish' ) {
                continue;
            }

            $data = array(
                'post_id'          => $post_id,
                'query'            => sanitize_text_field( $entry['query'] ),
                'snippet_format'   => isset( $entry['snippet_format'] ) ? sanitize_key( $entry['snippet_format'] ) : 'paragraph',
                'current_position' => isset( $entry['current_position'] ) ? absint( $entry['current_position'] ) : null,
                'search_volume'    => isset( $entry['search_volume'] ) ? absint( $entry['search_volume'] ) : null,
                'draft_content'    => isset( $entry['draft_content'] ) ? wp_kses_post( $entry['draft_content'] ) : null,
                'source'           => 'dashboard',
            );

            if ( AlmaSEO_Snippet_Targets_Model::insert_target( $data ) ) {
                $inserted++;
            }
        }

        return rest_ensure_response( array(
            'inserted' => $inserted,
            'total'    => count( $targets ),
        ) );
    }

    /* ──────────────── List ── */

    public static function list_targets( WP_REST_Request $request ) {
        $args = array(
            'page'           => absint( $request['page'] ),
            'per_page'       => min( absint( $request['per_page'] ), 100 ),
            'status'         => sanitize_key( $request['status'] ),
            'snippet_format' => sanitize_key( $request['snippet_format'] ),
            'search'         => sanitize_text_field( $request['search'] ),
            'orderby'        => sanitize_key( $request['orderby'] ),
            'order'          => strtoupper( sanitize_key( $request['order'] ) ),
        );

        $result = AlmaSEO_Snippet_Targets_Model::get_targets( $args );
        $items  = array_map( array( __CLASS__, 'prepare_item' ), $result['items'] );

        $response = rest_ensure_response( $items );
        $response->header( 'X-WP-Total',      $result['total'] );
        $response->header( 'X-WP-TotalPages', $result['pages'] );

        return $response;
    }

    /* ──────────────── Stats ── */

    public static function get_stats() {
        return rest_ensure_response( AlmaSEO_Snippet_Targets_Model::get_stats() );
    }

    /* ──────────────── Single detail ── */

    public static function get_target( WP_REST_Request $request ) {
        $target = AlmaSEO_Snippet_Targets_Model::get_target( absint( $request['id'] ) );
        if ( ! $target ) {
            return new WP_Error( 'not_found', 'Target not found.', array( 'status' => 404 ) );
        }

        $item = self::prepare_item( $target );

        // Add drift check for applied targets.
        if ( $target->status === 'applied' ) {
            $item['content_drifted'] = AlmaSEO_Snippet_Targets_Engine::has_content_drifted( $target->id );
        }

        // Add prompt hint.
        $item['prompt'] = AlmaSEO_Snippet_Targets_Engine::build_format_prompt( $target->snippet_format, $target->query );

        return rest_ensure_response( $item );
    }

    /* ──────────────── Save draft ── */

    public static function save_draft( WP_REST_Request $request ) {
        $target = AlmaSEO_Snippet_Targets_Model::get_target( absint( $request['id'] ) );
        if ( ! $target ) {
            return new WP_Error( 'not_found', 'Target not found.', array( 'status' => 404 ) );
        }

        $update = array(
            'draft_content' => wp_kses_post( $request['draft_content'] ),
            'status'        => 'draft',
            'reviewed_at'   => current_time( 'mysql', true ),
            'reviewed_by'   => get_current_user_id(),
        );

        if ( $request->has_param( 'snippet_format' ) ) {
            $format = sanitize_key( $request['snippet_format'] );
            if ( in_array( $format, AlmaSEO_Snippet_Targets_Engine::FORMATS, true ) ) {
                $update['snippet_format'] = $format;
            }
        }

        AlmaSEO_Snippet_Targets_Model::update_target( $target->id, $update );

        return rest_ensure_response( array( 'saved' => true ) );
    }

    /* ──────────────── Approve / Reject ── */

    public static function approve_target( WP_REST_Request $request ) {
        $target = AlmaSEO_Snippet_Targets_Model::get_target( absint( $request['id'] ) );
        if ( ! $target ) {
            return new WP_Error( 'not_found', 'Target not found.', array( 'status' => 404 ) );
        }

        AlmaSEO_Snippet_Targets_Model::update_target( $target->id, array(
            'status'      => 'approved',
            'reviewed_at' => current_time( 'mysql', true ),
            'reviewed_by' => get_current_user_id(),
        ) );

        return rest_ensure_response( array( 'approved' => true ) );
    }

    public static function reject_target( WP_REST_Request $request ) {
        $target = AlmaSEO_Snippet_Targets_Model::get_target( absint( $request['id'] ) );
        if ( ! $target ) {
            return new WP_Error( 'not_found', 'Target not found.', array( 'status' => 404 ) );
        }

        AlmaSEO_Snippet_Targets_Model::update_target( $target->id, array(
            'status'      => 'rejected',
            'reviewed_at' => current_time( 'mysql', true ),
            'reviewed_by' => get_current_user_id(),
        ) );

        return rest_ensure_response( array( 'rejected' => true ) );
    }

    /* ──────────────── Apply / Undo ── */

    public static function apply_target( WP_REST_Request $request ) {
        $result = AlmaSEO_Snippet_Targets_Engine::apply( absint( $request['id'] ) );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        return rest_ensure_response( $result );
    }

    public static function undo_target( WP_REST_Request $request ) {
        $result = AlmaSEO_Snippet_Targets_Engine::undo( absint( $request['id'] ) );
        if ( is_wp_error( $result ) ) {
            return $result;
        }
        return rest_ensure_response( $result );
    }

    /* ──────────────── Update status ── */

    public static function update_status( WP_REST_Request $request ) {
        $target = AlmaSEO_Snippet_Targets_Model::get_target( absint( $request['id'] ) );
        if ( ! $target ) {
            return new WP_Error( 'not_found', 'Target not found.', array( 'status' => 404 ) );
        }

        $allowed = array( 'won', 'lost', 'expired' );
        $status  = sanitize_key( $request['status'] );

        if ( ! in_array( $status, $allowed, true ) ) {
            return new WP_Error( 'invalid_status', 'Status must be won, lost, or expired.', array( 'status' => 400 ) );
        }

        AlmaSEO_Snippet_Targets_Model::update_target( $target->id, array(
            'status' => $status,
        ) );

        return rest_ensure_response( array( 'status' => $status ) );
    }

    /* ──────────────── Delete ── */

    public static function delete_target( WP_REST_Request $request ) {
        $target = AlmaSEO_Snippet_Targets_Model::get_target( absint( $request['id'] ) );
        if ( ! $target ) {
            return new WP_Error( 'not_found', 'Target not found.', array( 'status' => 404 ) );
        }

        AlmaSEO_Snippet_Targets_Model::delete_target( $target->id );

        return rest_ensure_response( array( 'deleted' => true ) );
    }

    /* ──────────────── Prompt ── */

    public static function get_prompt( WP_REST_Request $request ) {
        $target = AlmaSEO_Snippet_Targets_Model::get_target( absint( $request['id'] ) );
        if ( ! $target ) {
            return new WP_Error( 'not_found', 'Target not found.', array( 'status' => 404 ) );
        }

        $prompt = AlmaSEO_Snippet_Targets_Engine::build_format_prompt( $target->snippet_format, $target->query );

        return rest_ensure_response( array(
            'prompt'         => $prompt,
            'snippet_format' => $target->snippet_format,
            'query'          => $target->query,
        ) );
    }

    /* ──────────────── Formatting ── */

    private static function prepare_item( $row ) {
        $post = get_post( $row->post_id );

        return array(
            'id'               => (int) $row->id,
            'post_id'          => (int) $row->post_id,
            'post_title'       => $post ? $post->post_title : '(deleted)',
            'post_edit_link'   => $post ? get_edit_post_link( $post->ID, 'raw' ) : '',
            'permalink'        => $post ? get_permalink( $post->ID ) : '',
            'query'            => $row->query,
            'snippet_format'   => $row->snippet_format,
            'current_position' => $row->current_position ? (int) $row->current_position : null,
            'search_volume'    => $row->search_volume ? (int) $row->search_volume : null,
            'has_draft'        => ! empty( $row->draft_content ),
            'draft_content'    => $row->draft_content,
            'status'           => $row->status,
            'source'           => $row->source,
            'created_at'       => $row->created_at,
            'applied_at'       => $row->applied_at,
            'reviewed_at'      => $row->reviewed_at,
        );
    }
}
