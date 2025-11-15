<?php
/**
 * AlmaSEO Evergreen Feature - Admin Interface
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}


/**
 * Add admin menu pages
 */
function almaseo_eg_admin_menu() {
    // Add single submenu under AlmaSEO
    add_submenu_page(
        'seo-playground',
        __('Evergreen Content', 'almaseo'),
        __('Evergreen', 'almaseo'),
        'manage_options',
        'almaseo-evergreen',
        'almaseo_eg_admin_page'
    );
}
// Use priority 12 to appear after Overview (10) and SEO Optimization (11)
add_action('admin_menu', 'almaseo_eg_admin_menu', 12);


/**
 * Handle redirect from old settings page
 */
function almaseo_eg_handle_redirects() {
    if (is_admin() && isset($_GET['page']) && $_GET['page'] === 'almaseo-evergreen-settings') {
        wp_redirect(admin_url('admin.php?page=almaseo-evergreen#settings'), 301);
        exit;
    }
}
add_action('admin_init', 'almaseo_eg_handle_redirects');

/**
 * Combined Evergreen admin page - now renders the dashboard
 */
function almaseo_eg_admin_page() {
    // Simply render the dashboard page content
    if (function_exists('almaseo_eg_render_dashboard')) {
        almaseo_eg_render_dashboard();
        return;
    }
    
    // Fallback to simple message if dashboard function not available
    ?>
    <div class="wrap">
        <h1><?php _e('Evergreen Content Health', 'almaseo'); ?></h1>
        <p><?php _e('Dashboard function not available. Please check your installation.', 'almaseo'); ?></p>
    </div>
    <?php
}

/**
 * AJAX handler for saving panel state
 */
function almaseo_save_panel_state() {
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'wp_rest')) {
        wp_die('Security check failed');
    }
    
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : get_current_user_id();
    $panel = isset($_POST['panel']) ? sanitize_key($_POST['panel']) : '';
    $state = isset($_POST['state']) ? sanitize_key($_POST['state']) : 'open';
    
    if ($user_id && $panel) {
        update_user_meta($user_id, '_almaseo_sidebar_state_' . $panel, $state);
        wp_send_json_success(array('saved' => true));
    } else {
        wp_send_json_error(array('message' => 'Invalid parameters'));
    }
}
add_action('wp_ajax_almaseo_save_panel_state', 'almaseo_save_panel_state');

/**
 * Enqueue Evergreen assets only on the Evergreen dashboard
 */
add_action('admin_enqueue_scripts', function ($hook) {
    // Handle both top-level and submenu slugs
    $page = isset($_GET['page']) ? sanitize_key($_GET['page']) : '';
    if ($page !== 'almaseo-evergreen') {
        return; // not our screen
    }

    // Make sure jQuery is available
    wp_enqueue_script('jquery');

    // Build URLs safely from main plugin file
    $base_url = plugin_dir_url( ALMASEO_PLUGIN_FILE ); // define ALMASEO_PLUGIN_FILE in your main plugin file if not already
    $ver      = defined('ALMASEO_VERSION') ? ALMASEO_VERSION : '5.9.2';

    // Styles (optional if already enqueued)
    wp_enqueue_style(
        'almaseo-evergreen-css',
        $base_url . 'assets/css/evergreen.css',
        array(),
        $ver
    );

    // Chart/UX logic
    wp_enqueue_script(
        'almaseo-evergreen',
        $base_url . 'assets/js/evergreen.js',
        array('jquery'),
        $ver,
        true
    );

    // Localized config (used elsewhere, harmless here)
    wp_localize_script('almaseo-evergreen', 'almaseoEvergreen', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('almaseo_eg_ajax'),
    ));

    // Kick the chart after script loads (avoids CSP inline issues)
    wp_add_inline_script(
        'almaseo-evergreen',
        'window.almaseoRenderTrendChart && window.almaseoRenderTrendChart();'
    );
}, 20);