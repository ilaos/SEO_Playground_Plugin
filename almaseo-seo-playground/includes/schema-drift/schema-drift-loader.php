<?php
/**
 * Schema Drift Monitor – Module Loader / Bootstrap
 *
 * Pulls in every file for the module and wires up the
 * activation hook, REST routes, and admin controller.
 *
 * @package AlmaSEO
 * @since   7.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── Include module files ── */
$sd_dir = __DIR__ . '/';

require_once $sd_dir . 'schema-drift-install.php';
require_once $sd_dir . 'schema-drift-model.php';
require_once $sd_dir . 'schema-drift-engine.php';
require_once $sd_dir . 'schema-drift-rest.php';
require_once $sd_dir . 'schema-drift-controller.php';

/* ── Activation: create DB tables ── */
register_activation_hook(
    dirname( __DIR__, 2 ) . '/almaseo-seo-playground.php',
    'almaseo_schema_drift_install'
);

/* ── Also run install check on admin_init (handles upgrades
      and the case where the plugin was already active). ── */
add_action( 'admin_init', function () {
    $installed = get_option( 'almaseo_sd_db_version', '0' );
    if ( version_compare( $installed, '1.0.0', '<' ) ) {
        almaseo_schema_drift_install();
    }
} );

/* ── REST API ── */
add_action( 'rest_api_init', array( 'AlmaSEO_Schema_Drift_REST', 'register' ) );

/* ── Admin UI ── */
AlmaSEO_Schema_Drift_Controller::init();

/* ── Auto-scan triggers ── */
add_action( 'upgrader_process_complete', array( 'AlmaSEO_Schema_Drift_Engine', 'schedule_auto_scan' ), 10, 0 );
add_action( 'switch_theme', array( 'AlmaSEO_Schema_Drift_Engine', 'schedule_auto_scan' ), 10, 0 );
add_action( 'almaseo_schema_drift_auto_scan', array( 'AlmaSEO_Schema_Drift_Engine', 'run_auto_scan' ) );
