<?php
/**
 * AlmaSEO Robots.txt AJAX Handlers
 * 
 * @package AlmaSEO
 * @since 6.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler for saving robots.txt settings
 */
add_action('wp_ajax_almaseo_robots_save', 'almaseo_ajax_robots_save');
function almaseo_ajax_robots_save() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'almaseo_robots_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'almaseo')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions.', 'almaseo')));
    }
    
    $controller = AlmaSEO_Robots_Controller::get_instance();
    
    // Get and sanitize input
    $content = isset($_POST['content']) ? $controller->sanitize_robots_content($_POST['content']) : '';
    $mode = isset($_POST['mode']) ? $controller->sanitize_robots_mode($_POST['mode']) : 'virtual';
    
    // Save mode
    update_option('almaseo_robots_mode', $mode);
    
    // Handle based on mode
    if ($mode === 'virtual') {
        // Save to option
        update_option('almaseo_robots_content', $content);
        
        // Check if physical file exists
        $warning = '';
        if ($controller->physical_file_exists()) {
            $warning = __('Warning: A physical robots.txt file exists. WordPress will serve the physical file instead of your virtual content. Consider switching to Physical mode or removing the file.', 'almaseo');
        }
        
        wp_send_json_success(array(
            'message' => __('Virtual robots.txt saved successfully.', 'almaseo'),
            'warning' => $warning
        ));
        
    } else {
        // Try to write physical file
        $result = $controller->write_physical_file($content);
        
        if (is_wp_error($result)) {
            // Fall back to virtual mode
            update_option('almaseo_robots_mode', 'virtual');
            update_option('almaseo_robots_content', $content);
            
            wp_send_json_error(array(
                'message' => sprintf(
                    __('Could not write physical file: %s. Saved in virtual mode instead.', 'almaseo'),
                    $result->get_error_message()
                ),
                'fallback' => true,
                'mode' => 'virtual'
            ));
        } else {
            // Also save to option as backup
            update_option('almaseo_robots_content', $content);
            
            wp_send_json_success(array(
                'message' => __('Physical robots.txt file saved successfully.', 'almaseo')
            ));
        }
    }
}

/**
 * AJAX handler for testing robots.txt output
 */
add_action('wp_ajax_almaseo_robots_test', 'almaseo_ajax_robots_test');
function almaseo_ajax_robots_test() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'almaseo_robots_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'almaseo')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions.', 'almaseo')));
    }
    
    $controller = AlmaSEO_Robots_Controller::get_instance();
    
    // Get current output
    $output = $controller->get_current_output();
    
    // Get current mode and status
    $mode = get_option('almaseo_robots_mode', 'virtual');
    $physical_exists = $controller->physical_file_exists();
    
    // Prepare status info
    $status = array();
    
    if ($physical_exists && $mode === 'virtual') {
        $status[] = __('⚠️ Physical file exists and will be served (virtual mode ignored)', 'almaseo');
    } elseif ($physical_exists && $mode === 'file') {
        $status[] = __('✅ Serving physical robots.txt file', 'almaseo');
    } elseif (!$physical_exists && $mode === 'virtual') {
        $status[] = __('✅ Serving virtual robots.txt content', 'almaseo');
    } elseif (!$physical_exists && $mode === 'file') {
        $status[] = __('⚠️ Physical mode selected but no file exists (using WordPress default)', 'almaseo');
    }
    
    // Add URL info
    $robots_url = home_url('/robots.txt');
    $status[] = sprintf(__('URL: %s', 'almaseo'), $robots_url);
    
    wp_send_json_success(array(
        'output' => $output,
        'status' => $status,
        'mode' => $mode,
        'physical_exists' => $physical_exists,
        'url' => $robots_url
    ));
}

/**
 * AJAX handler for getting default content
 */
add_action('wp_ajax_almaseo_robots_get_default', 'almaseo_ajax_robots_get_default');
function almaseo_ajax_robots_get_default() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'almaseo_robots_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'almaseo')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions.', 'almaseo')));
    }
    
    $controller = AlmaSEO_Robots_Controller::get_instance();
    
    $type = isset($_POST['type']) ? $_POST['type'] : 'almaseo';
    
    if ($type === 'wordpress') {
        $content = $controller->get_wp_default();
    } else {
        $content = $controller->get_default_content();
    }
    
    wp_send_json_success(array(
        'content' => $content
    ));
}

/**
 * AJAX handler for checking file status
 */
add_action('wp_ajax_almaseo_robots_check_status', 'almaseo_ajax_robots_check_status');
function almaseo_ajax_robots_check_status() {
    // Check nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'almaseo_robots_nonce')) {
        wp_send_json_error(array('message' => __('Security check failed.', 'almaseo')));
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        wp_send_json_error(array('message' => __('Insufficient permissions.', 'almaseo')));
    }
    
    $controller = AlmaSEO_Robots_Controller::get_instance();
    
    $status = array(
        'physical_exists' => $controller->physical_file_exists(),
        'is_writable' => $controller->is_file_writable(),
        'file_path' => $controller->get_robots_file_path(),
        'mode' => get_option('almaseo_robots_mode', 'virtual')
    );
    
    // Add physical file content if it exists
    if ($status['physical_exists']) {
        $status['physical_content'] = $controller->read_physical_file();
    }
    
    wp_send_json_success($status);
}