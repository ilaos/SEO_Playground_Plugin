<?php
/**
 * AlmaSEO Setup Wizard
 *
 * First-run wizard that walks new users through essential site configuration:
 * social profiles, search appearance, and sitemap.
 *
 * @package AlmaSEO
 * @since   8.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Setup_Wizard {

    const SLUG          = 'almaseo-setup-wizard';
    const COMPLETED_KEY = 'almaseo_setup_wizard_completed';

    /**
     * Initialize wizard hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_admin_page' ) );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
        add_action( 'rest_api_init', array( __CLASS__, 'register_rest_routes' ) );
        // Intercept the wizard page early so WordPress never renders admin
        // header/notices around our standalone HTML document.
        add_action( 'admin_init', array( __CLASS__, 'maybe_render_standalone' ) );
    }

    /**
     * If we are on the wizard page, render it standalone and exit
     * before WordPress outputs admin header / notices.
     */
    public static function maybe_render_standalone() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( ! isset( $_GET['page'] ) || $_GET['page'] !== self::SLUG ) {
            return;
        }
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions.', 'almaseo' ) );
        }
        // Enqueue assets so wp_print_styles / wp_print_scripts work in the template.
        $ver = defined( 'ALMASEO_PLUGIN_VERSION' ) ? ALMASEO_PLUGIN_VERSION : '8.9.15';
        wp_enqueue_style( 'almaseo-setup-wizard', ALMASEO_URL . 'assets/css/setup-wizard.css', array(), $ver );
        wp_enqueue_script( 'almaseo-setup-wizard', ALMASEO_URL . 'assets/js/setup-wizard.js', array( 'wp-api-fetch' ), $ver, true );
        wp_localize_script( 'almaseo-setup-wizard', 'almaseoWizard', self::get_localize_data() );

        include ALMASEO_PATH . 'admin/pages/setup-wizard.php';
        exit;
    }

    /* ------------------------------------------------------------------
     *  Admin page (hidden — no parent menu item)
     * ----------------------------------------------------------------*/

    /**
     * Register a hidden admin page for the wizard.
     */
    public static function add_admin_page() {
        add_submenu_page(
            null, // hidden — no parent
            __( 'AlmaSEO Setup Wizard', 'almaseo' ),
            __( 'Setup Wizard', 'almaseo' ),
            'manage_options',
            self::SLUG,
            array( __CLASS__, 'render_page' )
        );
    }

    /**
     * Render the wizard page.
     */
    public static function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions.', 'almaseo' ) );
        }
        include ALMASEO_PATH . 'admin/pages/setup-wizard.php';
    }

    /* ------------------------------------------------------------------
     *  Assets
     * ----------------------------------------------------------------*/

    /**
     * Enqueue CSS and JS only on the wizard page.
     *
     * @param string $hook Current admin page hook.
     */
    public static function enqueue_assets( $hook ) {
        if ( strpos( $hook, self::SLUG ) === false ) {
            return;
        }

        $ver = defined( 'ALMASEO_PLUGIN_VERSION' ) ? ALMASEO_PLUGIN_VERSION : '8.9.15';

        wp_enqueue_style(
            'almaseo-setup-wizard',
            ALMASEO_URL . 'assets/css/setup-wizard.css',
            array(),
            $ver
        );

        wp_enqueue_script(
            'almaseo-setup-wizard',
            ALMASEO_URL . 'assets/js/setup-wizard.js',
            array( 'wp-api-fetch' ),
            $ver,
            true
        );

        wp_localize_script( 'almaseo-setup-wizard', 'almaseoWizard', self::get_localize_data() );
    }

    /**
     * Build the data object passed to the JS via wp_localize_script.
     *
     * @return array
     */
    private static function get_localize_data() {
        // Existing settings -------------------------------------------------
        $schema_settings = get_option( 'almaseo_schema_advanced_settings', array() );
        $sa_settings     = get_option( 'almaseo_search_appearance', array() );
        $sitemap_settings = get_option( 'almaseo_sitemap_settings', array() );

        // Available separators ----------------------------------------------
        $separators = array(
            '-', '|', '>', '~', '/', '*',
            "\xE2\x80\x93", // en-dash
            "\xE2\x80\x94", // em-dash
            "\xE2\x80\xA2", // bullet
            "\xC2\xAB",     // left guillemet
            "\xC2\xBB",     // right guillemet
        );

        // Public post types for sitemap step --------------------------------
        $post_types = array();
        $public_types = get_post_types( array( 'public' => true ), 'objects' );
        foreach ( $public_types as $pt ) {
            if ( 'attachment' === $pt->name ) {
                continue;
            }
            $post_types[] = array(
                'name'  => $pt->name,
                'label' => $pt->label,
            );
        }

        return array(
            'restBase'      => esc_url_raw( rest_url( 'almaseo/v1' ) ),
            'nonce'         => wp_create_nonce( 'wp_rest' ),
            'dashboardPage' => admin_url( 'admin.php?page=seo-playground' ),
            'separators'    => $separators,
            'postTypes'     => $post_types,
            'existing'      => array(
                'schema'           => $schema_settings,
                'searchAppearance' => $sa_settings,
                'sitemap'          => $sitemap_settings,
            ),
            'strings'       => array(
                'saving' => __( 'Saving...', 'almaseo' ),
                'saved'  => __( 'Saved!', 'almaseo' ),
                'error'  => __( 'An error occurred. Please try again.', 'almaseo' ),
            ),
        );
    }

    /* ------------------------------------------------------------------
     *  REST API
     * ----------------------------------------------------------------*/

    /**
     * Register wizard REST routes.
     */
    public static function register_rest_routes() {
        register_rest_route( 'almaseo/v1', '/wizard/save-step', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'rest_save_step' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args' => array(
                'step' => array(
                    'required'          => true,
                    'validate_callback' => function ( $val ) {
                        return is_numeric( $val ) && (int) $val >= 1 && (int) $val <= 5;
                    },
                ),
                'data' => array(
                    'required' => true,
                ),
            ),
        ) );

        register_rest_route( 'almaseo/v1', '/wizard/complete', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'rest_complete' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ) );
    }

    /**
     * REST callback: save a single wizard step.
     *
     * Step mapping (v8.9.15+):
     *   1 = Welcome (no data)
     *   2 = Social Profiles
     *   3 = Search Appearance
     *   4 = Sitemap
     *   5 = Done (handled by /wizard/complete)
     *
     * @param WP_REST_Request $request Request object.
     * @return WP_REST_Response
     */
    public static function rest_save_step( $request ) {
        $step = (int) $request->get_param( 'step' );
        $data = $request->get_param( 'data' );

        if ( ! is_array( $data ) ) {
            return new WP_REST_Response( array( 'message' => 'Invalid data.' ), 400 );
        }

        switch ( $step ) {
            case 1:
                // Welcome — nothing to save.
                return new WP_REST_Response( array( 'success' => true ), 200 );
            case 2:
                return self::save_step_social_profiles( $data );
            case 3:
                return self::save_step_search_appearance( $data );
            case 4:
                return self::save_step_sitemap( $data );
            case 5:
                // Done step — handled by /wizard/complete.
                return new WP_REST_Response( array( 'success' => true ), 200 );
            default:
                return new WP_REST_Response( array( 'message' => 'Unknown step.' ), 400 );
        }
    }

    /**
     * REST callback: mark wizard as completed.
     *
     * @return WP_REST_Response
     */
    public static function rest_complete() {
        update_option( self::COMPLETED_KEY, true );
        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    /* ------------------------------------------------------------------
     *  Step Savers
     * ----------------------------------------------------------------*/

    /**
     * Step 2 — Social Profiles.
     * Merges into almaseo_schema_advanced_settings.
     *
     * @param array $data Submitted data.
     * @return WP_REST_Response
     */
    private static function save_step_social_profiles( $data ) {
        $existing = get_option( 'almaseo_schema_advanced_settings', array() );

        if ( isset( $data['org_name'] ) ) {
            $existing['site_name'] = sanitize_text_field( $data['org_name'] );
        }
        if ( isset( $data['logo_url'] ) ) {
            $existing['site_logo_url'] = esc_url_raw( $data['logo_url'] );
        }

        $social_keys = array( 'facebook', 'twitter', 'instagram', 'linkedin', 'youtube', 'pinterest' );
        $profiles    = array();
        foreach ( $social_keys as $key ) {
            if ( isset( $data[ $key ] ) && '' !== $data[ $key ] ) {
                $profiles[ $key ] = esc_url_raw( $data[ $key ] );
            }
        }
        $existing['site_social_profiles'] = $profiles;

        update_option( 'almaseo_schema_advanced_settings', $existing );
        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    /**
     * Step 3 — Search Appearance.
     * Merges separator and title templates into almaseo_search_appearance.
     *
     * @param array $data Submitted data.
     * @return WP_REST_Response
     */
    private static function save_step_search_appearance( $data ) {
        $existing = get_option( 'almaseo_search_appearance', array() );

        // Separator.
        if ( isset( $data['separator'] ) ) {
            $existing['separator'] = sanitize_text_field( $data['separator'] );
        }

        // Homepage title / description.
        if ( ! isset( $existing['special'] ) ) {
            $existing['special'] = array();
        }
        if ( ! isset( $existing['special']['homepage'] ) ) {
            $existing['special']['homepage'] = array();
        }
        if ( isset( $data['homepage_title'] ) ) {
            $existing['special']['homepage']['title_template'] = sanitize_text_field( $data['homepage_title'] );
        }
        if ( isset( $data['homepage_description'] ) ) {
            $existing['special']['homepage']['description_template'] = sanitize_text_field( $data['homepage_description'] );
        }

        // Post title template.
        if ( isset( $data['post_title'] ) ) {
            if ( ! isset( $existing['post_types'] ) ) {
                $existing['post_types'] = array();
            }
            if ( ! isset( $existing['post_types']['post'] ) ) {
                $existing['post_types']['post'] = array();
            }
            $existing['post_types']['post']['title_template'] = sanitize_text_field( $data['post_title'] );
        }

        // Page title template.
        if ( isset( $data['page_title'] ) ) {
            if ( ! isset( $existing['post_types'] ) ) {
                $existing['post_types'] = array();
            }
            if ( ! isset( $existing['post_types']['page'] ) ) {
                $existing['post_types']['page'] = array();
            }
            $existing['post_types']['page']['title_template'] = sanitize_text_field( $data['page_title'] );
        }

        update_option( 'almaseo_search_appearance', $existing );
        return new WP_REST_Response( array( 'success' => true ), 200 );
    }

    /**
     * Step 4 — Sitemap.
     * Merges enabled flag and post type includes into almaseo_sitemap_settings.
     *
     * @param array $data Submitted data.
     * @return WP_REST_Response
     */
    private static function save_step_sitemap( $data ) {
        $existing = get_option( 'almaseo_sitemap_settings', array() );

        if ( isset( $data['enabled'] ) ) {
            $existing['enabled'] = (bool) $data['enabled'];
        }

        if ( isset( $data['post_types'] ) && is_array( $data['post_types'] ) ) {
            if ( ! isset( $existing['include'] ) ) {
                $existing['include'] = array();
            }
            // Map checkboxes to the existing settings structure.
            foreach ( $data['post_types'] as $pt => $enabled ) {
                $pt = sanitize_key( $pt );
                if ( 'post' === $pt ) {
                    $existing['include']['posts'] = (bool) $enabled;
                } elseif ( 'page' === $pt ) {
                    $existing['include']['pages'] = (bool) $enabled;
                }
                // Custom post types stored under their key directly.
                $existing['include'][ $pt ] = (bool) $enabled;
            }
        }

        update_option( 'almaseo_sitemap_settings', $existing );
        return new WP_REST_Response( array( 'success' => true ), 200 );
    }
}
