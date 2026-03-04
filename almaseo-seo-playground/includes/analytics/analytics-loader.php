<?php
/**
 * AlmaSEO Google Analytics Integration — Loader
 *
 * Bootstraps the analytics module: settings UI + frontend tracking snippet.
 *
 * @package AlmaSEO
 * @since   8.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

require_once dirname( __FILE__ ) . '/analytics-settings.php';
require_once dirname( __FILE__ ) . '/analytics-tracking.php';

AlmaSEO_Analytics_Settings::init();
AlmaSEO_Analytics_Tracking::init();
