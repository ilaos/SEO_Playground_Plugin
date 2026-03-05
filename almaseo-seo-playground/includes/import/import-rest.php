<?php
/**
 * AlmaSEO Import REST API
 *
 * @package AlmaSEO
 * @since   8.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Import_REST {

    /**
     * Register REST routes.
     */
    public static function register() {
        register_rest_route( 'almaseo/v1', '/import/detect', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'detect' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ) );

        register_rest_route( 'almaseo/v1', '/import/preview', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'preview' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args'                => array(
                'source' => array(
                    'required'          => true,
                    'validate_callback' => function ( $val ) {
                        return in_array( $val, array( 'yoast', 'rankmath', 'aioseo' ), true );
                    },
                ),
                'limit'  => array(
                    'default'           => 5,
                    'validate_callback' => 'is_numeric',
                ),
            ),
        ) );

        register_rest_route( 'almaseo/v1', '/import/batch', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'batch' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args'                => array(
                'source'    => array(
                    'required'          => true,
                    'validate_callback' => function ( $val ) {
                        return in_array( $val, array( 'yoast', 'rankmath', 'aioseo' ), true );
                    },
                ),
                'offset'    => array(
                    'default'           => 0,
                    'validate_callback' => 'is_numeric',
                ),
                'overwrite' => array(
                    'default' => false,
                ),
            ),
        ) );

        // --- Global Settings Import ---
        register_rest_route( 'almaseo/v1', '/import/detect-settings', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'detect_settings' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ) );

        register_rest_route( 'almaseo/v1', '/import/settings', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'import_settings' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args'                => array(
                'source'    => array(
                    'required'          => true,
                    'validate_callback' => function ( $val ) {
                        return in_array( $val, array( 'yoast', 'rankmath', 'aioseo' ), true );
                    },
                ),
                'overwrite' => array( 'default' => false ),
            ),
        ) );

        // --- Taxonomy Term Meta Import ---
        register_rest_route( 'almaseo/v1', '/import/detect-terms', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'detect_terms' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ) );

        register_rest_route( 'almaseo/v1', '/import/terms/batch', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'batch_terms' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args'                => array(
                'source'    => array(
                    'required'          => true,
                    'validate_callback' => function ( $val ) {
                        return in_array( $val, array( 'yoast', 'rankmath', 'aioseo' ), true );
                    },
                ),
                'offset'    => array( 'default' => 0, 'validate_callback' => 'is_numeric' ),
                'overwrite' => array( 'default' => false ),
            ),
        ) );

        // --- Redirect Import ---
        register_rest_route( 'almaseo/v1', '/import/detect-redirects', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'detect_redirects' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
        ) );

        register_rest_route( 'almaseo/v1', '/import/redirects/batch', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'batch_redirects' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args'                => array(
                'source'    => array(
                    'required'          => true,
                    'validate_callback' => function ( $val ) {
                        return in_array( $val, array( 'rankmath', 'yoast', 'redirection' ), true );
                    },
                ),
                'offset'    => array( 'default' => 0, 'validate_callback' => 'is_numeric' ),
                'overwrite' => array( 'default' => false ),
            ),
        ) );

        // --- Post-Import Verification ---
        register_rest_route( 'almaseo/v1', '/import/verify', array(
            'methods'             => 'GET',
            'callback'            => array( __CLASS__, 'verify' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args'                => array(
                'limit' => array( 'default' => 0, 'validate_callback' => 'is_numeric' ),
            ),
        ) );
    }

    /**
     * Detect available import sources.
     */
    public static function detect() {
        return new WP_REST_Response( AlmaSEO_Import_Detector::detect_all(), 200 );
    }

    /**
     * Preview import data.
     */
    public static function preview( WP_REST_Request $request ) {
        $source = $request->get_param( 'source' );
        $limit  = (int) $request->get_param( 'limit' );

        $data = AlmaSEO_Import_Engine::preview( $source, $limit );
        return new WP_REST_Response( $data, 200 );
    }

    /**
     * Process one batch.
     */
    public static function batch( WP_REST_Request $request ) {
        $source    = $request->get_param( 'source' );
        $offset    = (int) $request->get_param( 'offset' );
        $overwrite = (bool) $request->get_param( 'overwrite' );

        $result = AlmaSEO_Import_Engine::process_batch( $source, $offset, $overwrite );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array(
                'error'   => true,
                'message' => $result->get_error_message(),
            ), 400 );
        }

        return new WP_REST_Response( $result, 200 );
    }

    /* ------------------------------------------------------------------
     *  Global Settings
     * ----------------------------------------------------------------*/

    /**
     * Detect available global settings sources.
     */
    public static function detect_settings() {
        return new WP_REST_Response( AlmaSEO_Import_Settings_Mapper::detect_all(), 200 );
    }

    /**
     * Import global settings from a source.
     */
    public static function import_settings( WP_REST_Request $request ) {
        $source    = $request->get_param( 'source' );
        $overwrite = (bool) $request->get_param( 'overwrite' );

        $result = AlmaSEO_Import_Settings_Mapper::import( $source, $overwrite );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array(
                'error'   => true,
                'message' => $result->get_error_message(),
            ), 400 );
        }

        return new WP_REST_Response( $result, 200 );
    }

    /* ------------------------------------------------------------------
     *  Taxonomy Term Meta
     * ----------------------------------------------------------------*/

    /**
     * Detect available taxonomy term SEO data.
     */
    public static function detect_terms() {
        return new WP_REST_Response( AlmaSEO_Import_Term_Mapper::detect_all(), 200 );
    }

    /**
     * Process one batch of term meta imports.
     */
    public static function batch_terms( WP_REST_Request $request ) {
        $source    = $request->get_param( 'source' );
        $offset    = (int) $request->get_param( 'offset' );
        $overwrite = (bool) $request->get_param( 'overwrite' );

        $result = AlmaSEO_Import_Term_Mapper::process_batch( $source, $offset, $overwrite );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array(
                'error'   => true,
                'message' => $result->get_error_message(),
            ), 400 );
        }

        return new WP_REST_Response( $result, 200 );
    }

    /* ------------------------------------------------------------------
     *  Redirects
     * ----------------------------------------------------------------*/

    /**
     * Detect available redirect sources.
     */
    public static function detect_redirects() {
        return new WP_REST_Response( AlmaSEO_Import_Redirects_Mapper::detect_all(), 200 );
    }

    /**
     * Process one batch of redirect imports.
     */
    public static function batch_redirects( WP_REST_Request $request ) {
        $source    = $request->get_param( 'source' );
        $offset    = (int) $request->get_param( 'offset' );
        $overwrite = (bool) $request->get_param( 'overwrite' );

        $result = AlmaSEO_Import_Redirects_Mapper::process_batch( $source, $offset, $overwrite );

        if ( is_wp_error( $result ) ) {
            return new WP_REST_Response( array(
                'error'   => true,
                'message' => $result->get_error_message(),
            ), 400 );
        }

        return new WP_REST_Response( $result, 200 );
    }

    /* ------------------------------------------------------------------
     *  Verification
     * ----------------------------------------------------------------*/

    /**
     * Run post-import verification.
     */
    public static function verify( WP_REST_Request $request ) {
        $limit = (int) $request->get_param( 'limit' );
        $report = AlmaSEO_Import_Verifier::verify( $limit );
        return new WP_REST_Response( $report, 200 );
    }
}
