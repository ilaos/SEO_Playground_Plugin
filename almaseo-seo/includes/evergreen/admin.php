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
        __('Evergreen Content', 'almaseo-seo-playground'),
        __('Evergreen', 'almaseo-seo-playground'),
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
    if (is_admin() && isset($_GET['page']) && sanitize_text_field(wp_unslash($_GET['page'])) === 'almaseo-evergreen-settings') { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        wp_safe_redirect(admin_url('admin.php?page=almaseo-evergreen#settings'), 301);
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
        <h1><?php esc_html_e('Evergreen Content Health', 'almaseo-seo-playground'); ?></h1>
        <p><?php esc_html_e('Dashboard function not available. Please check your installation.', 'almaseo-seo-playground'); ?></p>
    </div>
    <?php
}

// The almaseo_save_panel_state AJAX handler used to live here, paired with a
// caller in evergreen-panel-consolidated.js. That JS file was never enqueued
// (the editor cascade in the loader uses enhanced-v2 → enhanced → minimal),
// and the handler additionally verified against the 'wp_rest' nonce while the
// localized data carried an 'almaseo_eg_ajax' nonce, so even if the JS had
// loaded the request would have failed security_check. Removed; if per-panel
// open/closed state ever becomes a feature again, register it via the REST
// API alongside the other evergreen routes instead of as an ad-hoc AJAX action.

// Evergreen dashboard CSS/JS + localized config (ajaxurl, nonce, i18n) are
// enqueued by evergreen-loader-minimal-safe.php — its admin_enqueue_scripts
// hook already covers `?page=almaseo-evergreen`. The previous admin.php block
// here re-registered the same handle at a later priority and overwrote the
// loader's wp_localize_script() data, dropping the i18n strings table that
// evergreen.js reads (e.g. almaseoEvergreen.i18n.analyzing). Removed; the
// chart self-kicks on DOM ready (assets/js/evergreen.js:709).