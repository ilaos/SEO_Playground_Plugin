<?php
/**
 * AlmaSEO Search Appearance Module Loader
 *
 * Bootstraps the Search Appearance module: smart tag parser, settings,
 * admin controller, and frontend output.
 *
 * @package AlmaSEO
 * @since   8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$sa_dir = __DIR__ . '/';

require_once $sa_dir . 'smart-tags.php';
require_once $sa_dir . 'search-appearance-settings.php';
require_once $sa_dir . 'search-appearance-controller.php';
require_once $sa_dir . 'search-appearance-frontend.php';

// Register settings.
add_action( 'admin_init', array( 'AlmaSEO_Search_Appearance_Settings', 'register' ) );

// Initialize admin controller.
AlmaSEO_Search_Appearance_Controller::init();

// Initialize frontend (only on non-admin requests).
if ( ! is_admin() ) {
    AlmaSEO_Search_Appearance_Frontend::init();
}
