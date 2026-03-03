<?php
/**
 * Featured Snippet Targeting – Module Loader / Bootstrap
 *
 * Pulls in every file for the module and wires up the
 * activation hook, REST routes, and admin controller.
 *
 * @package AlmaSEO
 * @since   7.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── Include module files ── */
$st_dir = __DIR__ . '/';

require_once $st_dir . 'snippet-targets-install.php';
require_once $st_dir . 'snippet-targets-model.php';
require_once $st_dir . 'snippet-targets-engine.php';
require_once $st_dir . 'snippet-targets-rest.php';
require_once $st_dir . 'snippet-targets-controller.php';

/* ── Activation: create DB table ── */
register_activation_hook(
    dirname( __DIR__, 2 ) . '/almaseo-seo-playground.php',
    'almaseo_snippet_targets_install'
);

/* ── Also run install check on admin_init (handles upgrades
      and the case where the plugin was already active). ── */
add_action( 'admin_init', function () {
    $installed = get_option( 'almaseo_st_db_version', '0' );
    if ( version_compare( $installed, '1.0.0', '<' ) ) {
        almaseo_snippet_targets_install();
    }
} );

/* ── REST API ── */
add_action( 'rest_api_init', array( 'AlmaSEO_Snippet_Targets_REST', 'register' ) );

/* ── Admin UI ── */
AlmaSEO_Snippet_Targets_Controller::init();
