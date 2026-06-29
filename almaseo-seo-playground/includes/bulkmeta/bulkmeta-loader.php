<?php
/**
 * AlmaSEO Bulk Metadata Editor - Loader
 * 
 * @package AlmaSEO
 * @since 6.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define constants
if (!defined('ALMASEO_VERSION')) {
    define('ALMASEO_VERSION', ALMASEO_PLUGIN_VERSION);
}

// Load controller, REST API, and auto-fill generator
require_once plugin_dir_path(__FILE__) . 'bulkmeta-controller.php';
require_once plugin_dir_path(__FILE__) . 'bulkmeta-rest.php';
require_once plugin_dir_path(__FILE__) . 'autofill-generator.php';