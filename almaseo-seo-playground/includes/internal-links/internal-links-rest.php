<?php
/**
 * AlmaSEO Internal Links REST API
 *
 * Provides 10 REST endpoints for managing internal link rules:
 *   1. GET    /internal-links          - List rules (paginated, searchable)
 *   2. POST   /internal-links          - Create rule
 *   3. GET    /internal-links/{id}     - Get single rule
 *   4. PUT    /internal-links/{id}     - Update rule
 *   5. DELETE /internal-links/{id}     - Delete rule
 *   6. PATCH  /internal-links/{id}/toggle - Toggle enabled
 *   7. POST   /internal-links/bulk     - Bulk delete/enable/disable
 *   8. GET    /internal-links/stats    - Summary statistics
 *   9. POST   /internal-links/preview  - Preview link insertion on a post
 *  10. GET    /internal-links/settings - Get/update global settings
 *
 * @package AlmaSEO
 * @subpackage InternalLinks
 * @since 6.6.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Internal_Links_REST {

    /**
     * REST namespace
     */
    const NAMESPACE_V1 = 'almaseo/v1';

    /**
     * Register all REST routes
     */
    public function register_routes() {

        // 1 & 2 - List / Create
        register_rest_route( self::NAMESPACE_V1, '/internal-links', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_links' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'page'       => array( 'default' => 1,          'sanitize_callback' => 'absint' ),
                    'per_page'   => array( 'default' => 20,         'sanitize_callback' => 'absint',
                        'validate_callback' => function ( $v ) { return $v > 0 && $v <= 100; },
                    ),
                    'search'     => array( 'default' => '',         'sanitize_callback' => 'sanitize_text_field' ),
                    'orderby'    => array( 'default' => 'priority', 'sanitize_callback' => 'sanitize_key' ),
                    'order'      => array( 'default' => 'ASC',
                        'sanitize_callback' => function ( $v ) {
                            return in_array( strtoupper( $v ), array( 'ASC', 'DESC' ), true ) ? strtoupper( $v ) : 'ASC';
                        },
                    ),
                    'is_enabled' => array( 'default' => null, 'sanitize_callback' => function ( $v ) {
                        return ( $v === null || $v === '' ) ? null : absint( $v );
                    }),
                ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'create_link' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'keyword'        => array( 'required' => true,  'sanitize_callback' => 'sanitize_text_field' ),
                    'target_url'     => array( 'required' => true,  'sanitize_callback' => 'esc_url_raw' ),
                    'target_post_id' => array( 'default'  => null,  'sanitize_callback' => 'absint' ),
                    'match_type'     => array( 'default'  => 'exact', 'sanitize_callback' => 'sanitize_key' ),
                    'case_sensitive' => array( 'default'  => 0,     'sanitize_callback' => 'absint' ),
                    'max_per_post'   => array( 'default'  => 1,     'sanitize_callback' => 'absint' ),
                    'max_per_page'   => array( 'default'  => 3,     'sanitize_callback' => 'absint' ),
                    'nofollow'       => array( 'default'  => 0,     'sanitize_callback' => 'absint' ),
                    'new_tab'        => array( 'default'  => 0,     'sanitize_callback' => 'absint' ),
                    'is_enabled'     => array( 'default'  => 1,     'sanitize_callback' => 'absint' ),
                    'post_types'     => array( 'default'  => 'post,page', 'sanitize_callback' => 'sanitize_text_field' ),
                    'exclude_ids'    => array( 'default'  => '',    'sanitize_callback' => 'sanitize_text_field' ),
                    'priority'       => array( 'default'  => 10,    'sanitize_callback' => 'absint' ),
                ),
            ),
        ) );

        // 3, 4 & 5 - Get / Update / Delete single rule
        register_rest_route( self::NAMESPACE_V1, '/internal-links/(?P<id>\d+)', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_link' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'id' => array( 'validate_callback' => function ( $p ) { return is_numeric( $p ); } ),
                ),
            ),
            array(
                'methods'             => WP_REST_Server::EDITABLE,
                'callback'            => array( $this, 'update_link' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'id'             => array( 'validate_callback' => function ( $p ) { return is_numeric( $p ); } ),
                    'keyword'        => array( 'sanitize_callback' => 'sanitize_text_field' ),
                    'target_url'     => array( 'sanitize_callback' => 'esc_url_raw' ),
                    'target_post_id' => array( 'sanitize_callback' => 'absint' ),
                    'match_type'     => array( 'sanitize_callback' => 'sanitize_key' ),
                    'case_sensitive' => array( 'sanitize_callback' => 'absint' ),
                    'max_per_post'   => array( 'sanitize_callback' => 'absint' ),
                    'max_per_page'   => array( 'sanitize_callback' => 'absint' ),
                    'nofollow'       => array( 'sanitize_callback' => 'absint' ),
                    'new_tab'        => array( 'sanitize_callback' => 'absint' ),
                    'is_enabled'     => array( 'sanitize_callback' => 'absint' ),
                    'post_types'     => array( 'sanitize_callback' => 'sanitize_text_field' ),
                    'exclude_ids'    => array( 'sanitize_callback' => 'sanitize_text_field' ),
                    'priority'       => array( 'sanitize_callback' => 'absint' ),
                ),
            ),
            array(
                'methods'             => WP_REST_Server::DELETABLE,
                'callback'            => array( $this, 'delete_link' ),
                'permission_callback' => array( $this, 'check_permission' ),
                'args'                => array(
                    'id' => array( 'validate_callback' => function ( $p ) { return is_numeric( $p ); } ),
                ),
            ),
        ) );

        // 6 - Toggle enabled
        register_rest_route( self::NAMESPACE_V1, '/internal-links/(?P<id>\d+)/toggle', array(
            'methods'             => 'PATCH',
            'callback'            => array( $this, 'toggle_link' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'id' => array( 'validate_callback' => function ( $p ) { return is_numeric( $p ); } ),
            ),
        ) );

        // 7 - Bulk actions
        register_rest_route( self::NAMESPACE_V1, '/internal-links/bulk', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'bulk_action' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'action' => array( 'required' => true, 'sanitize_callback' => 'sanitize_key' ),
                'ids'    => array( 'required' => true, 'validate_callback' => function ( $p ) { return is_array( $p ); } ),
            ),
        ) );

        // 8 - Statistics
        register_rest_route( self::NAMESPACE_V1, '/internal-links/stats', array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => array( $this, 'get_stats' ),
            'permission_callback' => array( $this, 'check_permission' ),
        ) );

        // 9 - Preview
        register_rest_route( self::NAMESPACE_V1, '/internal-links/preview', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( $this, 'preview_links' ),
            'permission_callback' => array( $this, 'check_permission' ),
            'args'                => array(
                'post_id' => array( 'required' => true, 'sanitize_callback' => 'absint' ),
            ),
        ) );

        // 10 - Global settings (GET & POST)
        register_rest_route( self::NAMESPACE_V1, '/internal-links/settings', array(
            array(
                'methods'             => WP_REST_Server::READABLE,
                'callback'            => array( $this, 'get_settings' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
            array(
                'methods'             => WP_REST_Server::CREATABLE,
                'callback'            => array( $this, 'save_settings' ),
                'permission_callback' => array( $this, 'check_permission' ),
            ),
        ) );
    }

    /* ------------------------------------------------------------------
     * Permission
     * ----------------------------------------------------------------*/

    public function check_permission() {
        return current_user_can( 'manage_options' );
    }

    /* ------------------------------------------------------------------
     * 1. GET /internal-links
     * ----------------------------------------------------------------*/

    public function get_links( $request ) {
        require_once plugin_dir_path( __FILE__ ) . 'internal-links-model.php';

        $args = array(
            'page'       => $request->get_param( 'page' ),
            'per_page'   => $request->get_param( 'per_page' ),
            'search'     => $request->get_param( 'search' ),
            'orderby'    => $request->get_param( 'orderby' ),
            'order'      => $request->get_param( 'order' ),
            'is_enabled' => $request->get_param( 'is_enabled' ),
        );

        $result = AlmaSEO_Internal_Links_Model::get_links( $args );

        return new WP_REST_Response( $result, 200 );
    }

    /* ------------------------------------------------------------------
     * 2. POST /internal-links
     * ----------------------------------------------------------------*/

    public function create_link( $request ) {
        require_once plugin_dir_path( __FILE__ ) . 'internal-links-model.php';

        $keyword = $request->get_param( 'keyword' );
        if ( empty( $keyword ) ) {
            return new WP_Error( 'missing_keyword', __( 'Keyword is required.', 'almaseo' ), array( 'status' => 400 ) );
        }

        $target_url = $request->get_param( 'target_url' );
        if ( empty( $target_url ) ) {
            return new WP_Error( 'missing_target', __( 'Target URL is required.', 'almaseo' ), array( 'status' => 400 ) );
        }

        // Check for duplicate keyword
        if ( AlmaSEO_Internal_Links_Model::keyword_exists( $keyword ) ) {
            return new WP_Error( 'duplicate_keyword', __( 'A rule with this keyword already exists.', 'almaseo' ), array( 'status' => 409 ) );
        }

        $data = array(
            'keyword'        => $keyword,
            'target_url'     => $target_url,
            'target_post_id' => $request->get_param( 'target_post_id' ),
            'match_type'     => $request->get_param( 'match_type' ),
            'case_sensitive' => $request->get_param( 'case_sensitive' ),
            'max_per_post'   => $request->get_param( 'max_per_post' ),
            'max_per_page'   => $request->get_param( 'max_per_page' ),
            'nofollow'       => $request->get_param( 'nofollow' ),
            'new_tab'        => $request->get_param( 'new_tab' ),
            'is_enabled'     => $request->get_param( 'is_enabled' ),
            'post_types'     => $request->get_param( 'post_types' ),
            'exclude_ids'    => $request->get_param( 'exclude_ids' ),
            'priority'       => $request->get_param( 'priority' ),
        );

        $id = AlmaSEO_Internal_Links_Model::create_link( $data );

        if ( ! $id ) {
            return new WP_Error( 'create_failed', __( 'Failed to create link rule.', 'almaseo' ), array( 'status' => 500 ) );
        }

        $link = AlmaSEO_Internal_Links_Model::get_link( $id );

        return new WP_REST_Response( $link, 201 );
    }

    /* ------------------------------------------------------------------
     * 3. GET /internal-links/{id}
     * ----------------------------------------------------------------*/

    public function get_link( $request ) {
        require_once plugin_dir_path( __FILE__ ) . 'internal-links-model.php';

        $id   = $request->get_param( 'id' );
        $link = AlmaSEO_Internal_Links_Model::get_link( $id );

        if ( ! $link ) {
            return new WP_Error( 'not_found', __( 'Link rule not found.', 'almaseo' ), array( 'status' => 404 ) );
        }

        return new WP_REST_Response( $link, 200 );
    }

    /* ------------------------------------------------------------------
     * 4. PUT /internal-links/{id}
     * ----------------------------------------------------------------*/

    public function update_link( $request ) {
        require_once plugin_dir_path( __FILE__ ) . 'internal-links-model.php';

        $id       = $request->get_param( 'id' );
        $existing = AlmaSEO_Internal_Links_Model::get_link( $id );

        if ( ! $existing ) {
            return new WP_Error( 'not_found', __( 'Link rule not found.', 'almaseo' ), array( 'status' => 404 ) );
        }

        $data = array();
        $fields = array(
            'keyword', 'target_url', 'target_post_id', 'match_type',
            'case_sensitive', 'max_per_post', 'max_per_page', 'nofollow',
            'new_tab', 'is_enabled', 'post_types', 'exclude_ids', 'priority',
        );

        foreach ( $fields as $field ) {
            if ( $request->has_param( $field ) ) {
                $data[ $field ] = $request->get_param( $field );
            }
        }

        // Check duplicate keyword (if keyword is being changed)
        if ( isset( $data['keyword'] ) && $data['keyword'] !== $existing['keyword'] ) {
            if ( AlmaSEO_Internal_Links_Model::keyword_exists( $data['keyword'], $id ) ) {
                return new WP_Error( 'duplicate_keyword', __( 'A rule with this keyword already exists.', 'almaseo' ), array( 'status' => 409 ) );
            }
        }

        if ( ! empty( $data ) ) {
            $result = AlmaSEO_Internal_Links_Model::update_link( $id, $data );
            if ( ! $result ) {
                return new WP_Error( 'update_failed', __( 'Failed to update link rule.', 'almaseo' ), array( 'status' => 500 ) );
            }
        }

        $link = AlmaSEO_Internal_Links_Model::get_link( $id );

        return new WP_REST_Response( $link, 200 );
    }

    /* ------------------------------------------------------------------
     * 5. DELETE /internal-links/{id}
     * ----------------------------------------------------------------*/

    public function delete_link( $request ) {
        require_once plugin_dir_path( __FILE__ ) . 'internal-links-model.php';

        $id       = $request->get_param( 'id' );
        $existing = AlmaSEO_Internal_Links_Model::get_link( $id );

        if ( ! $existing ) {
            return new WP_Error( 'not_found', __( 'Link rule not found.', 'almaseo' ), array( 'status' => 404 ) );
        }

        $result = AlmaSEO_Internal_Links_Model::delete_link( $id );

        if ( ! $result ) {
            return new WP_Error( 'delete_failed', __( 'Failed to delete link rule.', 'almaseo' ), array( 'status' => 500 ) );
        }

        return new WP_REST_Response( array( 'message' => __( 'Link rule deleted successfully.', 'almaseo' ) ), 200 );
    }

    /* ------------------------------------------------------------------
     * 6. PATCH /internal-links/{id}/toggle
     * ----------------------------------------------------------------*/

    public function toggle_link( $request ) {
        require_once plugin_dir_path( __FILE__ ) . 'internal-links-model.php';

        $id       = $request->get_param( 'id' );
        $existing = AlmaSEO_Internal_Links_Model::get_link( $id );

        if ( ! $existing ) {
            return new WP_Error( 'not_found', __( 'Link rule not found.', 'almaseo' ), array( 'status' => 404 ) );
        }

        $result = AlmaSEO_Internal_Links_Model::toggle_link( $id );

        if ( ! $result ) {
            return new WP_Error( 'toggle_failed', __( 'Failed to toggle link rule.', 'almaseo' ), array( 'status' => 500 ) );
        }

        $link = AlmaSEO_Internal_Links_Model::get_link( $id );

        return new WP_REST_Response( $link, 200 );
    }

    /* ------------------------------------------------------------------
     * 7. POST /internal-links/bulk
     * ----------------------------------------------------------------*/

    public function bulk_action( $request ) {
        require_once plugin_dir_path( __FILE__ ) . 'internal-links-model.php';

        $action = $request->get_param( 'action' );
        $ids    = $request->get_param( 'ids' );

        if ( empty( $ids ) ) {
            return new WP_Error( 'no_ids', __( 'No rules selected.', 'almaseo' ), array( 'status' => 400 ) );
        }

        $valid_actions = array( 'delete', 'enable', 'disable' );
        if ( ! in_array( $action, $valid_actions, true ) ) {
            return new WP_Error( 'invalid_action', __( 'Invalid bulk action.', 'almaseo' ), array( 'status' => 400 ) );
        }

        $success = 0;
        $failed  = 0;

        foreach ( $ids as $id ) {
            $id = absint( $id );

            switch ( $action ) {
                case 'delete':
                    if ( AlmaSEO_Internal_Links_Model::delete_link( $id ) ) {
                        $success++;
                    } else {
                        $failed++;
                    }
                    break;

                case 'enable':
                    if ( AlmaSEO_Internal_Links_Model::update_link( $id, array( 'is_enabled' => 1 ) ) ) {
                        $success++;
                    } else {
                        $failed++;
                    }
                    break;

                case 'disable':
                    if ( AlmaSEO_Internal_Links_Model::update_link( $id, array( 'is_enabled' => 0 ) ) ) {
                        $success++;
                    } else {
                        $failed++;
                    }
                    break;
            }
        }

        return new WP_REST_Response( array(
            'message' => sprintf( __( '%d rules processed, %d failed.', 'almaseo' ), $success, $failed ),
            'success' => $success,
            'failed'  => $failed,
        ), 200 );
    }

    /* ------------------------------------------------------------------
     * 8. GET /internal-links/stats
     * ----------------------------------------------------------------*/

    public function get_stats( $request ) {
        require_once plugin_dir_path( __FILE__ ) . 'internal-links-model.php';

        $stats = AlmaSEO_Internal_Links_Model::get_stats();

        return new WP_REST_Response( $stats, 200 );
    }

    /* ------------------------------------------------------------------
     * 9. POST /internal-links/preview
     * ----------------------------------------------------------------*/

    public function preview_links( $request ) {
        require_once plugin_dir_path( __FILE__ ) . 'internal-links-model.php';
        require_once plugin_dir_path( __FILE__ ) . 'internal-links-engine.php';

        $post_id = $request->get_param( 'post_id' );
        $post    = get_post( $post_id );

        if ( ! $post ) {
            return new WP_Error( 'not_found', __( 'Post not found.', 'almaseo' ), array( 'status' => 404 ) );
        }

        // Get original content
        $original = apply_filters( 'the_content', $post->post_content );

        // Process with engine
        $processed = AlmaSEO_Internal_Links_Engine::process_content( $post->post_content );
        $processed = apply_filters( 'the_content', $processed );

        // Count inserted links
        preg_match_all( '/class="almaseo-auto-link"/', $processed, $matches );
        $count = count( $matches[0] );

        return new WP_REST_Response( array(
            'post_id'   => $post_id,
            'title'     => $post->post_title,
            'count'     => $count,
            'preview'   => $processed,
            'original'  => $original,
        ), 200 );
    }

    /* ------------------------------------------------------------------
     * 10. GET & POST /internal-links/settings
     * ----------------------------------------------------------------*/

    public function get_settings( $request ) {
        $defaults = array(
            'enabled'              => true,
            'max_links_per_post'   => 10,
            'skip_headings'        => true,
            'skip_images'          => true,
            'skip_first_paragraph' => false,
            'exclude_post_ids'     => '',
        );

        $saved    = get_option( 'almaseo_internal_links_settings', array() );
        $settings = wp_parse_args( $saved, $defaults );

        return new WP_REST_Response( $settings, 200 );
    }

    public function save_settings( $request ) {
        $allowed = array(
            'enabled', 'max_links_per_post', 'skip_headings',
            'skip_images', 'skip_first_paragraph', 'exclude_post_ids',
        );

        $settings = get_option( 'almaseo_internal_links_settings', array() );

        foreach ( $allowed as $key ) {
            if ( $request->has_param( $key ) ) {
                $value = $request->get_param( $key );

                // Sanitize
                if ( in_array( $key, array( 'enabled', 'skip_headings', 'skip_images', 'skip_first_paragraph' ), true ) ) {
                    $settings[ $key ] = (bool) $value;
                } elseif ( $key === 'max_links_per_post' ) {
                    $settings[ $key ] = max( 1, absint( $value ) );
                } elseif ( $key === 'exclude_post_ids' ) {
                    // Comma-separated integers
                    $ids              = array_map( 'absint', explode( ',', $value ) );
                    $ids              = array_filter( $ids );
                    $settings[ $key ] = implode( ',', $ids );
                }
            }
        }

        update_option( 'almaseo_internal_links_settings', $settings );

        // Clear engine cache
        AlmaSEO_Internal_Links_Model::clear_cache();

        return new WP_REST_Response( $settings, 200 );
    }
}
