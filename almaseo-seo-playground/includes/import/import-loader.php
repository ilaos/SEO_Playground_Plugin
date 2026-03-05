<?php
/**
 * AlmaSEO Import Module Loader
 *
 * @package AlmaSEO
 * @since   8.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$import_dir = __DIR__ . '/';

require_once $import_dir . 'import-detector.php';
require_once $import_dir . 'import-mapper-yoast.php';
require_once $import_dir . 'import-mapper-rankmath.php';
require_once $import_dir . 'import-mapper-aioseo.php';
require_once $import_dir . 'import-engine.php';
require_once $import_dir . 'import-settings-mapper.php';
require_once $import_dir . 'import-term-mapper.php';
require_once $import_dir . 'import-redirects-mapper.php';
require_once $import_dir . 'import-verifier.php';
require_once $import_dir . 'import-rest.php';
require_once $import_dir . 'import-controller.php';

// REST API.
add_action( 'rest_api_init', array( 'AlmaSEO_Import_REST', 'register' ) );

// Admin UI.
AlmaSEO_Import_Controller::init();
