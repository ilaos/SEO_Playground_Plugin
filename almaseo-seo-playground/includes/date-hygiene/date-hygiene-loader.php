<?php
/**
 * Date Hygiene Scanner – Module Loader / Bootstrap
 *
 * Pulls in every file for the module and wires up the
 * activation hook, REST routes, and admin controller.
 *
 * @package AlmaSEO
 * @since   7.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* ── Include module files ── */
$dh_dir = __DIR__ . '/';

require_once $dh_dir . 'date-hygiene-install.php';
require_once $dh_dir . 'date-hygiene-model.php';
require_once $dh_dir . 'date-hygiene-engine.php';
require_once $dh_dir . 'date-hygiene-rest.php';
require_once $dh_dir . 'date-hygiene-controller.php';

/* ── Activation: create DB table ── */
register_activation_hook(
    dirname( __DIR__, 2 ) . '/almaseo-seo-playground.php',
    'almaseo_date_hygiene_install'
);

/* ── Also run install check on admin_init (handles upgrades
      and the case where the plugin was already active). ── */
add_action( 'admin_init', function () {
    $installed = get_option( 'almaseo_dh_db_version', '0' );
    if ( version_compare( $installed, '1.0.0', '<' ) ) {
        almaseo_date_hygiene_install();
    }
} );

/* ── REST API ── */
add_action( 'rest_api_init', array( 'AlmaSEO_Date_Hygiene_REST', 'register' ) );

/* ── Admin UI ── */
AlmaSEO_Date_Hygiene_Controller::init();
