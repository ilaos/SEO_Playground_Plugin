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

/**
 * Check if Pro features are enabled
 *
 * This is a backward compatibility wrapper that uses the centralized license helper.
 * The bulk metadata feature requires Pro or higher tier.
 *
 * Note: This function may also be used by other modules that check for Pro status.
 */
if (!function_exists('almaseo_is_pro')) {
    function almaseo_is_pro() {
        // Use centralized license helper
        // This checks if Pro or Agency tier is active
        return almaseo_is_pro_active();
    }
}

// Load controller and REST API
require_once plugin_dir_path(__FILE__) . 'bulkmeta-controller.php';
require_once plugin_dir_path(__FILE__) . 'bulkmeta-rest.php';