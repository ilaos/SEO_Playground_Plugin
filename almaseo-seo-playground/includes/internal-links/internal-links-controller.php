<?php
/**
 * AlmaSEO Internal Links Controller
 *
 * Registers the admin submenu page, enqueues JS/CSS assets,
 * and wires up the REST API routes.
 *
 * @package AlmaSEO
 * @subpackage InternalLinks
 * @since 6.6.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Internal_Links_Controller {

    /**
     * Initialize the controller
     */
    public static function init() {
        // Admin menu
        add_action( 'admin_menu', array( __CLASS__, 'add_admin_menu' ), 25 );

        // Assets
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_admin_assets' ) );

        // REST API
        add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );

        // Database check on admin
        if ( is_admin() ) {
            require_once plugin_dir_path( __FILE__ ) . 'internal-links-install.php';
        }
    }

    /* ------------------------------------------------------------------
     * Admin Menu
     * ----------------------------------------------------------------*/

    /**
     * Add Internal Links submenu page under SEO Playground
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'seo-playground',
            __( 'Internal Links', 'almaseo-seo-playground' ),
            __( 'Internal Links', 'almaseo-seo-playground' ),
            'manage_options',
            'almaseo-internal-links',
            array( __CLASS__, 'render_admin_page' )
        );

        // Orphan Pages submenu (v7.7.0+).
        add_submenu_page(
            'seo-playground',
            __( 'Orphan Pages', 'almaseo-seo-playground' ),
            __( 'Orphan Pages', 'almaseo-seo-playground' ),
            'manage_options',
            'almaseo-orphan-pages',
            array( __CLASS__, 'render_orphan_page' )
        );
    }

    /**
     * Render the admin page (or locked UI if not Pro)
     */
    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(esc_html__( 'You do not have sufficient permissions to access this page.', 'almaseo-seo-playground' ) );
        }

        // Pro gate
        if ( ! almaseo_feature_available( 'internal_links' ) ) {
            if ( function_exists( 'almaseo_render_feature_locked' ) ) {
                almaseo_render_feature_locked( 'internal_links' );
            }
            return;
        }

        // Include the admin page template
        require_once plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'admin/pages/internal-links.php';
    }

    /**
     * Render the orphan pages admin page.
     */
    public static function render_orphan_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die(esc_html__( 'You do not have sufficient permissions to access this page.', 'almaseo-seo-playground' ) );
        }

        // Pro gate.
        if ( function_exists( 'almaseo_feature_available' ) && ! almaseo_feature_available( 'orphan_detection' ) ) {
            if ( function_exists( 'almaseo_render_feature_locked' ) ) {
                almaseo_render_feature_locked( 'orphan_detection' );
            }
            return;
        }

        require_once plugin_dir_path( dirname( dirname( __FILE__ ) ) ) . 'admin/pages/internal-links-orphans.php';
    }

    /* ------------------------------------------------------------------
     * Assets
     * ----------------------------------------------------------------*/

    /**
     * Enqueue admin CSS & JS (on Internal Links or Orphan Pages page)
     */
    public static function enqueue_admin_assets( $hook ) {
        // Orphan Pages assets.
        if ( strpos( $hook, 'almaseo-orphan-pages' ) !== false ) {
            self::enqueue_orphan_assets();
            return;
        }

        if ( strpos( $hook, 'almaseo-internal-links' ) === false ) {
            return;
        }

        $base_url = plugins_url( '', dirname( dirname( __FILE__ ) ) );
        $version  = defined( 'ALMASEO_PLUGIN_VERSION' ) ? ALMASEO_PLUGIN_VERSION : '6.6.0';

        // CSS
        wp_enqueue_style(
            'almaseo-internal-links',
            $base_url . '/assets/css/internal-links.css',
            array(),
            $version
        );

        // JS
        wp_enqueue_script(
            'almaseo-internal-links',
            $base_url . '/assets/js/internal-links.js',
            array( 'jquery' ),
            $version,
            true
        );

        // Localize
        wp_localize_script( 'almaseo-internal-links', 'almaseoInternalLinks', array(
            'apiUrl'  => rest_url( 'almaseo/v1/internal-links' ),
            'nonce'   => wp_create_nonce( 'wp_rest' ),
            'homeUrl' => home_url(),
            'strings' => array(
                'confirmDelete'     => __( 'Are you sure you want to delete this link rule?', 'almaseo-seo-playground' ),
                'confirmBulkDelete' => __( 'Are you sure you want to delete the selected rules?', 'almaseo-seo-playground' ),
                'error'             => __( 'An error occurred. Please try again.', 'almaseo-seo-playground' ),
                'success'           => __( 'Operation completed successfully.', 'almaseo-seo-playground' ),
                'duplicateKeyword'  => __( 'A rule with this keyword already exists.', 'almaseo-seo-playground' ),
                'missingKeyword'    => __( 'Keyword is required.', 'almaseo-seo-playground' ),
                'missingTarget'     => __( 'Target URL is required.', 'almaseo-seo-playground' ),
                'saved'             => __( 'Settings saved.', 'almaseo-seo-playground' ),
            ),
        ) );
    }

    /* ------------------------------------------------------------------
     * REST Routes
     * ----------------------------------------------------------------*/

    /**
     * Enqueue orphan pages assets.
     */
    private static function enqueue_orphan_assets() {
        $base_url = plugins_url( '', dirname( dirname( __FILE__ ) ) );
        $version  = defined( 'ALMASEO_PLUGIN_VERSION' ) ? ALMASEO_PLUGIN_VERSION : '7.7.0';

        wp_enqueue_style(
            'almaseo-orphan-pages',
            $base_url . '/assets/css/internal-links-orphans.css',
            array(),
            $version
        );

        wp_enqueue_script(
            'almaseo-orphan-pages',
            $base_url . '/assets/js/internal-links-orphans.js',
            array( 'wp-api-fetch' ),
            $version,
            true
        );

        wp_localize_script( 'almaseo-orphan-pages', 'almaseoOrphans', array(
            'restBase' => rest_url( 'almaseo/v1/internal-links/orphans' ),
            'nonce'    => wp_create_nonce( 'wp_rest' ),
            'noFindings' => __( 'No orphan pages found. Run a scan to check for pages with missing internal links.', 'almaseo-seo-playground' ),
        ) );
    }

    /* ------------------------------------------------------------------
     * REST Routes
     * ----------------------------------------------------------------*/

    /**
     * Register REST API routes
     */
    public static function register_rest_routes() {
        require_once plugin_dir_path( __FILE__ ) . 'internal-links-rest.php';
        $rest = new AlmaSEO_Internal_Links_REST();
        $rest->register_routes();

        /* ── Orphan page endpoints (v7.7.0+) ── */

        // List orphans.
        register_rest_route( 'almaseo/v1', '/internal-links/orphans', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'rest_get_orphans' ),
            'permission_callback' => array( __CLASS__, 'can_manage_orphans' ),
            'args'                => array(
                'page'       => array( 'type' => 'integer', 'default' => 1 ),
                'per_page'   => array( 'type' => 'integer', 'default' => 20 ),
                'status'     => array( 'type' => 'string',  'default' => '' ),
                'cluster_id' => array( 'type' => 'string',  'default' => '' ),
                'search'     => array( 'type' => 'string',  'default' => '' ),
            ),
        ) );

        // Orphan stats.
        register_rest_route( 'almaseo/v1', '/internal-links/orphans/stats', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'rest_orphan_stats' ),
            'permission_callback' => array( __CLASS__, 'can_manage_orphans' ),
        ) );

        // Scan for orphans.
        register_rest_route( 'almaseo/v1', '/internal-links/orphans/scan', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'rest_scan_orphans' ),
            'permission_callback' => array( __CLASS__, 'can_manage_orphans' ),
        ) );

        // Dismiss an orphan.
        register_rest_route( 'almaseo/v1', '/internal-links/orphans/(?P<id>\d+)/dismiss', array(
            'methods'             => 'PATCH',
            'callback'            => array( __CLASS__, 'rest_dismiss_orphan' ),
            'permission_callback' => array( __CLASS__, 'can_manage_orphans' ),
        ) );

        // Dashboard push orphan data.
        register_rest_route( 'almaseo/v1', '/internal-links/orphans/push', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'rest_push_orphans' ),
            'permission_callback' => 'almaseo_api_auth_check',
            'args'                => array(
                'items' => array( 'type' => 'array', 'required' => true, 'items' => array( 'type' => 'object' ) ),
            ),
        ) );

        // Get clusters.
        register_rest_route( 'almaseo/v1', '/internal-links/orphans/clusters', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'rest_get_clusters' ),
            'permission_callback' => array( __CLASS__, 'can_manage_orphans' ),
        ) );
    }

    /* ── Orphan permission check ── */

    public static function can_manage_orphans() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }
        if ( function_exists( 'almaseo_feature_available' ) && ! almaseo_feature_available( 'orphan_detection' ) ) {
            return new WP_Error( 'pro_required', 'Orphan Detection requires Pro.', array( 'status' => 403 ) );
        }
        return true;
    }

    /* ── Orphan REST callbacks ── */

    public static function rest_get_orphans( WP_REST_Request $request ) {
        $args = array(
            'page'       => absint( $request['page'] ),
            'per_page'   => min( absint( $request['per_page'] ), 100 ),
            'status'     => sanitize_key( $request['status'] ),
            'cluster_id' => sanitize_text_field( $request['cluster_id'] ),
            'search'     => sanitize_text_field( $request['search'] ),
        );

        $result = AlmaSEO_Internal_Links_Orphan::get_orphans( $args );

        // Enrich items with post data.
        $items = array_map( function ( $row ) {
            $post = get_post( $row->post_id );
            return array(
                'id'               => (int) $row->id,
                'post_id'          => (int) $row->post_id,
                'post_title'       => $post ? $post->post_title : null,
                'post_edit_link'   => $post ? get_edit_post_link( $post->ID, 'raw' ) : null,
                'permalink'        => $post ? get_permalink( $post->ID ) : null,
                'inbound_count'    => (int) $row->inbound_count,
                'outbound_count'   => (int) $row->outbound_count,
                'cluster_id'       => $row->cluster_id,
                'cluster_strength' => (float) $row->cluster_strength,
                'is_hub_candidate' => (bool) $row->is_hub_candidate,
                'status'           => $row->status,
                'scanned_at'       => $row->scanned_at,
                'suggestion'       => $row->suggestion,
            );
        }, $result['items'] );

        $response = rest_ensure_response( $items );
        $response->header( 'X-WP-Total',      $result['total'] );
        $response->header( 'X-WP-TotalPages', $result['pages'] );

        return $response;
    }

    public static function rest_orphan_stats() {
        return rest_ensure_response( AlmaSEO_Internal_Links_Orphan::get_stats() );
    }

    public static function rest_scan_orphans() {
        $counts = AlmaSEO_Internal_Links_Orphan::scan_all();
        return rest_ensure_response( $counts );
    }

    public static function rest_dismiss_orphan( WP_REST_Request $request ) {
        $row = AlmaSEO_Internal_Links_Orphan::get_orphan( absint( $request['id'] ) );
        if ( ! $row ) {
            return new WP_Error( 'not_found', 'Orphan record not found.', array( 'status' => 404 ) );
        }
        AlmaSEO_Internal_Links_Orphan::dismiss( $row->id );
        return rest_ensure_response( array( 'dismissed' => true ) );
    }

    public static function rest_push_orphans( WP_REST_Request $request ) {
        $items = $request->get_param( 'items' );
        if ( ! is_array( $items ) || empty( $items ) ) {
            return new WP_Error( 'invalid_payload', 'items must be a non-empty array.', array( 'status' => 400 ) );
        }
        $counts = AlmaSEO_Internal_Links_Orphan::process_push( $items );
        return rest_ensure_response( $counts );
    }

    public static function rest_get_clusters() {
        $clusters = AlmaSEO_Internal_Links_Orphan::get_clusters();
        return rest_ensure_response( $clusters );
    }
}
