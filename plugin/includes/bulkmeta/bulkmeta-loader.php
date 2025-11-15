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
 */
if (!function_exists('almaseo_is_pro')) {
    function almaseo_is_pro() {
        // Check for Pro license or tier
        $license = get_option('almaseo_license_key');
        $tier = get_option('almaseo_tier', 'free');
        
        // For development/testing, check for a special option
        if (defined('ALMASEO_DEV_MODE') && ALMASEO_DEV_MODE) {
            return true;
        }
        
        // Check if tier is pro or enterprise
        if (in_array($tier, array('pro', 'enterprise', 'agency'), true)) {
            return true;
        }
        
        // Check for valid license
        if (!empty($license)) {
            $license_data = get_option('almaseo_license_data');
            if ($license_data && isset($license_data['status']) && $license_data['status'] === 'valid') {
                return true;
            }
        }
        
        // Default to Pro for now (for testing)
        return true;
    }
}

// Load controller and REST API
require_once plugin_dir_path(__FILE__) . 'bulkmeta-controller.php';
require_once plugin_dir_path(__FILE__) . 'bulkmeta-rest.php';