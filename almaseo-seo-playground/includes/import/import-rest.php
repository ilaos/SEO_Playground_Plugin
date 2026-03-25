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
                    'validate_callback' => function ( $val ) { return is_numeric( $val ); },
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
                    'validate_callback' => function ( $val ) { return is_numeric( $val ); },
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
                'offset'    => array( 'default' => 0, 'validate_callback' => function ( $val ) { return is_numeric( $val ); } ),
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
                'offset'    => array( 'default' => 0, 'validate_callback' => function ( $val ) { return is_numeric( $val ); } ),
                'overwrite' => array( 'default' => false ),
            ),
        ) );

        // --- Reset step status ---
        register_rest_route( 'almaseo/v1', '/import/reset-step', array(
            'methods'             => 'POST',
            'callback'            => array( __CLASS__, 'reset_step' ),
            'permission_callback' => function () {
                return current_user_can( 'manage_options' );
            },
            'args'                => array(
                'step' => array(
                    'required'          => true,
                    'validate_callback' => function ( $val ) {
                        return in_array( $val, array( 'posts', 'terms', 'settings', 'redirects', 'verify' ), true );
                    },
                ),
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
                'limit' => array( 'default' => 0, 'validate_callback' => function ( $val ) { return is_numeric( $val ); } ),
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

        // Persist completion state.
        $status = get_option( 'almaseo_import_status', array() );
        $status['settings'] = array(
            'completed' => true,
            'source'    => $source,
            'imported'  => isset( $result['imported'] ) ? $result['imported'] : 0,
            'skipped'   => isset( $result['skipped'] ) ? $result['skipped'] : 0,
            'date'      => current_time( 'mysql' ),
        );
        update_option( 'almaseo_import_status', $status, false );

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

        // Accumulate batch totals and persist on completion.
        $status  = get_option( 'almaseo_import_status', array() );
        $prev    = ( $offset === 0 ) ? array() : ( isset( $status['terms'] ) ? $status['terms'] : array() );
        $r_imp   = ( isset( $prev['_running_imported'] ) ? $prev['_running_imported'] : 0 ) + ( isset( $result['imported'] ) ? $result['imported'] : 0 );
        $r_skip  = ( isset( $prev['_running_skipped'] ) ? $prev['_running_skipped'] : 0 ) + ( isset( $result['skipped'] ) ? $result['skipped'] : 0 );
        $r_nf    = ( isset( $prev['_running_not_found'] ) ? $prev['_running_not_found'] : 0 ) + ( isset( $result['not_found'] ) ? $result['not_found'] : 0 );
        $r_empty = ( isset( $prev['_running_empty'] ) ? $prev['_running_empty'] : 0 ) + ( isset( $result['empty'] ) ? $result['empty'] : 0 );

        if ( ! empty( $result['done'] ) ) {
            $status['terms'] = array(
                'completed' => true,
                'source'    => $source,
                'imported'  => $r_imp,
                'skipped'   => $r_skip,
                'not_found' => $r_nf,
                'empty'     => $r_empty,
                'date'      => current_time( 'mysql' ),
            );
        } else {
            $status['terms'] = array(
                'completed'          => false,
                'source'             => $source,
                '_running_imported'  => $r_imp,
                '_running_skipped'   => $r_skip,
                '_running_not_found' => $r_nf,
                '_running_empty'     => $r_empty,
            );
        }
        update_option( 'almaseo_import_status', $status, false );

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

        // Accumulate batch totals and persist on completion.
        $status  = get_option( 'almaseo_import_status', array() );
        $prev    = ( $offset === 0 ) ? array() : ( isset( $status['redirects'] ) ? $status['redirects'] : array() );
        $r_imp   = ( isset( $prev['_running_imported'] ) ? $prev['_running_imported'] : 0 ) + ( isset( $result['imported'] ) ? $result['imported'] : 0 );
        $r_skip  = ( isset( $prev['_running_skipped'] ) ? $prev['_running_skipped'] : 0 ) + ( isset( $result['skipped'] ) ? $result['skipped'] : 0 );

        if ( ! empty( $result['done'] ) ) {
            $status['redirects'] = array(
                'completed' => true,
                'source'    => $source,
                'imported'  => $r_imp,
                'skipped'   => $r_skip,
                'date'      => current_time( 'mysql' ),
            );
        } else {
            $status['redirects'] = array(
                'completed'         => false,
                'source'            => $source,
                '_running_imported' => $r_imp,
                '_running_skipped'  => $r_skip,
            );
        }
        update_option( 'almaseo_import_status', $status, false );

        return new WP_REST_Response( $result, 200 );
    }

    /* ------------------------------------------------------------------
     *  Reset Step
     * ----------------------------------------------------------------*/

    /**
     * Reset a step's completion status so the user can re-import.
     */
    public static function reset_step( WP_REST_Request $request ) {
        $step   = $request->get_param( 'step' );
        $status = get_option( 'almaseo_import_status', array() );

        if ( isset( $status[ $step ] ) ) {
            unset( $status[ $step ] );
            update_option( 'almaseo_import_status', $status, false );
        }

        return new WP_REST_Response( array( 'reset' => true, 'step' => $step ), 200 );
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

        // Persist completion state.
        $status = get_option( 'almaseo_import_status', array() );
        $status['verify'] = array(
            'completed'     => true,
            'total_scanned' => isset( $report['total_scanned'] ) ? $report['total_scanned'] : 0,
            'issues'        => isset( $report['issues']['total'] ) ? $report['issues']['total'] : 0,
            'date'          => current_time( 'mysql' ),
        );
        update_option( 'almaseo_import_status', $status, false );

        return new WP_REST_Response( $report, 200 );
    }
}
