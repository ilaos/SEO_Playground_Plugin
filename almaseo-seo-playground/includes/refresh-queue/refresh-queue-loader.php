<?php
/**
 * Refresh Queue – Module Loader / Bootstrap
 *
 * Pulls in every file for the module and wires up the
 * activation hook, REST routes, and admin controller.
 *
 * @package AlmaSEO
 * @since   7.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── Include module files ── */
$rq_dir = __DIR__ . '/';

require_once $rq_dir . 'refresh-queue-install.php';
require_once $rq_dir . 'refresh-queue-model.php';
require_once $rq_dir . 'refresh-queue-engine.php';
require_once $rq_dir . 'refresh-queue-rest.php';
require_once $rq_dir . 'refresh-queue-controller.php';

/* ── Activation: create DB table ── */
register_activation_hook(
    dirname( __DIR__, 2 ) . '/almaseo-seo-playground.php',
    'almaseo_refresh_queue_install'
);

/* ── Also run install check on admin_init (handles upgrades
      and the case where the plugin was already active). ── */
add_action( 'admin_init', function () {
    $installed = get_option( 'almaseo_rq_db_version', '0' );
    if ( version_compare( $installed, '1.0.0', '<' ) ) {
        almaseo_refresh_queue_install();
    }
} );

/* ── REST API ── */
add_action( 'rest_api_init', array( 'AlmaSEO_Refresh_Queue_REST', 'register' ) );

/* ── Admin UI ── */
AlmaSEO_Refresh_Queue_Controller::init();
