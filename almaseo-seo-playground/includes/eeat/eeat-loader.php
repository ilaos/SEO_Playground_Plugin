<?php
/**
 * E-E-A-T Enforcement – Module Loader / Bootstrap
 *
 * Pulls in every file for the module and wires up the
 * activation hook, REST routes, admin controller, and
 * health score integration.
 *
 * @package AlmaSEO
 * @since   7.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── Include module files ── */
$eeat_dir = __DIR__ . '/';

require_once $eeat_dir . 'eeat-install.php';
require_once $eeat_dir . 'eeat-model.php';
require_once $eeat_dir . 'eeat-engine.php';
require_once $eeat_dir . 'eeat-rest.php';
require_once $eeat_dir . 'eeat-controller.php';

/* ── Activation: create DB table ── */
register_activation_hook(
    dirname( __DIR__, 2 ) . '/almaseo-seo-playground.php',
    'almaseo_eeat_install'
);

/* ── Also run install check on admin_init (handles upgrades
      and the case where the plugin was already active). ── */
add_action( 'admin_init', function () {
    $installed = get_option( 'almaseo_eeat_db_version', '0' );
    if ( version_compare( $installed, '1.0.0', '<' ) ) {
        almaseo_eeat_install();
    }
} );

/* ── REST API ── */
add_action( 'rest_api_init', array( 'AlmaSEO_EEAT_REST', 'register' ) );

/* ── Admin UI ── */
AlmaSEO_EEAT_Controller::init();

/* ── Health Score Integration (opt-in via settings weight > 0) ── */
add_filter( 'almaseo_health_weights', array( 'AlmaSEO_EEAT_Engine', 'add_health_weight' ) );
add_filter( 'almaseo_health_signals', array( 'AlmaSEO_EEAT_Engine', 'add_health_signal' ), 10, 4 );
