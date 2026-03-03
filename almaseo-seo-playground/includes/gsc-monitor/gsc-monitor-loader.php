<?php
/**
 * GSC Monitor – Module Loader / Bootstrap
 *
 * Pulls in every file for the module and wires up the
 * activation hook, REST routes, and admin controller.
 *
 * @package AlmaSEO
 * @since   7.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── Include module files ── */
$gsc_dir = __DIR__ . '/';

require_once $gsc_dir . 'gsc-monitor-install.php';
require_once $gsc_dir . 'gsc-monitor-model.php';
require_once $gsc_dir . 'gsc-monitor-engine.php';
require_once $gsc_dir . 'gsc-monitor-rest.php';
require_once $gsc_dir . 'gsc-monitor-controller.php';

/* ── Activation: create DB table ── */
register_activation_hook(
    dirname( __DIR__, 2 ) . '/almaseo-seo-playground.php',
    'almaseo_gsc_monitor_install'
);

/* ── Also run install check on admin_init (handles upgrades) ── */
add_action( 'admin_init', function () {
    $installed = get_option( 'almaseo_gsc_db_version', '0' );
    if ( version_compare( $installed, '1.0.0', '<' ) ) {
        almaseo_gsc_monitor_install();
    }
} );

/* ── REST API ── */
add_action( 'rest_api_init', array( 'AlmaSEO_GSC_Monitor_REST', 'register' ) );

/* ── Admin UI ── */
AlmaSEO_GSC_Monitor_Controller::init();
