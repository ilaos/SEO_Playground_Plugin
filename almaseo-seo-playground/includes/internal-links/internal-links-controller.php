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
            __( 'Internal Links', 'almaseo' ),
            __( 'Internal Links', 'almaseo' ),
            'manage_options',
            'almaseo-internal-links',
            array( __CLASS__, 'render_admin_page' )
        );
    }

    /**
     * Render the admin page (or locked UI if not Pro)
     */
    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have sufficient permissions to access this page.', 'almaseo' ) );
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

    /* ------------------------------------------------------------------
     * Assets
     * ----------------------------------------------------------------*/

    /**
     * Enqueue admin CSS & JS (only on Internal Links page)
     */
    public static function enqueue_admin_assets( $hook ) {
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
                'confirmDelete'     => __( 'Are you sure you want to delete this link rule?', 'almaseo' ),
                'confirmBulkDelete' => __( 'Are you sure you want to delete the selected rules?', 'almaseo' ),
                'error'             => __( 'An error occurred. Please try again.', 'almaseo' ),
                'success'           => __( 'Operation completed successfully.', 'almaseo' ),
                'duplicateKeyword'  => __( 'A rule with this keyword already exists.', 'almaseo' ),
                'missingKeyword'    => __( 'Keyword is required.', 'almaseo' ),
                'missingTarget'     => __( 'Target URL is required.', 'almaseo' ),
                'saved'             => __( 'Settings saved.', 'almaseo' ),
            ),
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
    }
}
