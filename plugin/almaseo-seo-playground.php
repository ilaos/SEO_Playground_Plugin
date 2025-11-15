<?php
/*
Plugin Name: AlmaSEO SEO Playground
Plugin URI: https://almaseo.com/
Description: Professional SEO optimization plugin with AI-powered content generation, comprehensive keyword analysis, schema markup, and real-time SEO insights. Features 5 polished tabs for complete SEO management.
Version: 6.5.0
Author: AlmaSEO
Author URI: https://almaseo.com/
License: GPL2
Text Domain: almaseo
Requires at least: 5.6
Requires PHP: 7.4
Tested up to: 6.6
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// STANDARDIZED Plugin constants - defined once at plugin load
define('ALMASEO_MAIN_FILE', __FILE__);
define('ALMASEO_PATH', plugin_dir_path(ALMASEO_MAIN_FILE));
define('ALMASEO_URL', plugin_dir_url(ALMASEO_MAIN_FILE));
define('ALMASEO_PLUGIN_VERSION', '6.5.0');
define('ALMASEO_VERSION', '6.5.0'); // For compatibility
define('ALMASEO_API_NAMESPACE', 'almaseo/v1');

// Legacy constants for backwards compatibility (to be removed later)
define('ALMASEO_PLUGIN_URL', ALMASEO_URL);
define('ALMASEO_PLUGIN_DIR', ALMASEO_PATH);
define('ALMASEO_PLUGIN_FILE', ALMASEO_MAIN_FILE);

// Include schema implementation (clean version to avoid conflicts)
if (file_exists(ALMASEO_PLUGIN_DIR . 'includes/schema-clean.php')) {
    require_once ALMASEO_PLUGIN_DIR . 'includes/schema-clean.php';
}

// Include schema image fallback chain
if (file_exists(ALMASEO_PLUGIN_DIR . 'includes/schema-image-fallback.php')) {
    require_once ALMASEO_PLUGIN_DIR . 'includes/schema-image-fallback.php';
}

// Include safe schema scrubber for AIOSEO compatibility
if (file_exists(ALMASEO_PLUGIN_DIR . 'includes/schema-scrubber-safe.php')) {
    require_once ALMASEO_PLUGIN_DIR . 'includes/schema-scrubber-safe.php';
} elseif (file_exists(ALMASEO_PLUGIN_DIR . 'includes/schema-scrubber.php')) {
    // Fallback to standard scrubber if safe not available
    require_once ALMASEO_PLUGIN_DIR . 'includes/schema-scrubber.php';
}

// Include settings page
if (file_exists(ALMASEO_PLUGIN_DIR . 'includes/admin/settings.php')) {
    require_once ALMASEO_PLUGIN_DIR . 'includes/admin/settings.php';
}

// Include WP-CLI commands if available
if (defined('WP_CLI') && WP_CLI) {
    if (file_exists(ALMASEO_PLUGIN_DIR . 'includes/cli/schema-command.php')) {
        require_once ALMASEO_PLUGIN_DIR . 'includes/cli/schema-command.php';
    }
}

// Include meta and social tags handler
if (file_exists(plugin_dir_path(__FILE__) . 'includes/meta-social-handler.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/meta-social-handler.php';
}

// Include schema meta registration for Gutenberg
if (file_exists(plugin_dir_path(__FILE__) . 'includes/schema-meta-registration.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/schema-meta-registration.php';
}

// Include Evergreen feature (using minimal safe loader to prevent crashes)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/evergreen/evergreen-loader-minimal-safe.php')) {
    // Only load if not during activation
    if (!isset($_GET['action']) || $_GET['action'] !== 'activate') {
        require_once plugin_dir_path(__FILE__) . 'includes/evergreen/evergreen-loader-minimal-safe.php';
    }
} elseif (file_exists(plugin_dir_path(__FILE__) . 'includes/evergreen/evergreen-loader-safe.php')) {
    // Fallback to safe loader if minimal not available
    if (!isset($_GET['action']) || $_GET['action'] !== 'activate') {
        require_once plugin_dir_path(__FILE__) . 'includes/evergreen/evergreen-loader-safe.php';
    }
}

// Skip loading heavy features during activation to prevent memory issues
$is_activating = isset($_GET['action']) && $_GET['action'] === 'activate';

// Include Health Score feature
if (!$is_activating && file_exists(plugin_dir_path(__FILE__) . 'includes/health/health-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/health/health-loader.php';
}

// Include Metadata History feature
if (!$is_activating && file_exists(plugin_dir_path(__FILE__) . 'includes/history/history-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/history/history-loader.php';
}

// Include Post-Writing Optimization feature v1.2
if (!$is_activating && file_exists(plugin_dir_path(__FILE__) . 'includes/optimization/optimization-loader-v12.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/optimization/optimization-loader-v12.php';
}

// Include WooCommerce SEO features (Pro tier only)
if (!$is_activating && file_exists(plugin_dir_path(__FILE__) . 'includes/woo/woo-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/woo/woo-loader.php';
}

// Include new Sitemap module (Phase 0)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/sitemap/class-alma-sitemap-manager.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/sitemap/helpers.php';
    require_once plugin_dir_path(__FILE__) . 'includes/sitemap/class-alma-sitemap-manager.php';
    
    // Initialize sitemap manager
    add_action('init', function() {
        Alma_Sitemap_Manager::get_instance();
    }, 0);
    
    // Register activation/deactivation hooks
    register_activation_hook(__FILE__, array('Alma_Sitemap_Manager', 'activate'));
    register_deactivation_hook(__FILE__, array('Alma_Sitemap_Manager', 'deactivate'));
}

// Include Admin UI Helpers (v6.0.2+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/admin/ui-helpers.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin/ui-helpers.php';
}

// Include Robots.txt Editor (v6.0.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/robots/robots-controller.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/robots/robots-controller.php';
    require_once plugin_dir_path(__FILE__) . 'includes/robots/robots-ajax.php';
}

// Include Redirects Manager module (v6.1.0+) - Pro feature
if (file_exists(plugin_dir_path(__FILE__) . 'includes/redirects/redirects-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/redirects/redirects-loader.php';
}

// Include 404 Tracker module (v6.2.0+) - Pro feature
if (file_exists(plugin_dir_path(__FILE__) . 'includes/404/404-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/404/404-loader.php';
}

// Include Bulk Metadata Editor module (v6.3.0+) - Pro feature
if (file_exists(plugin_dir_path(__FILE__) . 'includes/bulkmeta/bulkmeta-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/bulkmeta/bulkmeta-loader.php';
}

// Ensure almaseo_is_pro function exists as fallback
if (!function_exists('almaseo_is_pro')) {
    function almaseo_is_pro() {
        // Default to false if function not yet defined
        return false;
    }
}

// Initialize auto-update system (v5.0.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/almaseo-update.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/almaseo-update.php';
}

// Dequeue legacy styles from old plugin versions
add_action('admin_enqueue_scripts', function($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) return;
    
    $maybe_legacy = [
        'almaseo-connector-admin',
        'almaseo-connector-seo-playground',
        'almaseo-connector-v1-admin',
        'almaseo-seo-playground-legacy',
        'seo-playground-final-admin',
        'seo-playground-standalone-admin',
    ];
    
    foreach ($maybe_legacy as $h) {
        if (wp_style_is($h, 'enqueued')) {
            wp_dequeue_style($h);
            wp_deregister_style($h);
        }
    }
}, 998);

// Enqueue admin hardening CSS with very high priority to override legacy styles
add_action('admin_enqueue_scripts', function($hook) {
    if (!in_array($hook, ['post.php', 'post-new.php'], true)) return;
    
    wp_enqueue_style(
        'almaseo-admin-hardening',
        plugin_dir_url(__FILE__) . 'assets/css/admin-hardening.css',
        [],
        defined('ALMASEO_PLUGIN_VERSION') ? ALMASEO_PLUGIN_VERSION : time()
    );
}, 999);

// Admin notice for multiple AlmaSEO installations
add_action('admin_notices', function() {
    if (!current_user_can('activate_plugins')) return;
    
    $plugin_dir = WP_PLUGIN_DIR ?? ABSPATH . 'wp-content/plugins';
    $dirs = array_filter(glob($plugin_dir . '/*'), 'is_dir');
    $conflicts = array_filter($dirs, function($d) {
        return preg_match('/almaseo-connector|almaseo-connector-v1\.1|seo-playground-(final|standalone)/', basename($d));
    });
    
    if ($conflicts) {
        echo '<div class="notice notice-warning is-dismissible"><p><strong>AlmaSEO:</strong> Multiple legacy plugin folders detected. For best results, keep only <em>almaseo-seo-playground-v4</em> active and remove older versions to avoid CSS conflicts (duplicate icons/blue headers).</p></div>';
    }
});

// Content refresh reminder cron handler
if (!function_exists('almaseo_handle_content_refresh_reminder')) {
    function almaseo_handle_content_refresh_reminder($post_id) {
        $post = get_post($post_id);
    if (!$post) {
        return;
    }
    
    // Check if email reminder is enabled
    $send_email = get_post_meta($post_id, '_almaseo_update_reminder_email', true);
    
    // Get post age
    $post_modified = get_post_modified_time('U', false, $post_id);
    $current_time = current_time('timestamp');
    $days_since_update = floor(($current_time - $post_modified) / (60 * 60 * 24));
    
    // Store admin notice
    $notice_data = array(
        'post_id' => $post_id,
        'post_title' => $post->post_title,
        'days_old' => $days_since_update,
        'edit_link' => get_edit_post_link($post_id) . '#tab-seo-overview'
    );
    
    // Store notice for display (site option for all admins to see)
    $existing_notices = get_option('almaseo_content_reminders', array());
    $existing_notices[$post_id] = $notice_data;
    update_option('almaseo_content_reminders', $existing_notices);
    
    // Send email if enabled
    if ($send_email) {
        $admin_email = get_option('admin_email');
        $subject = sprintf('[%s] Content Update Reminder: %s', get_bloginfo('name'), $post->post_title);
        $message = sprintf(
            "This is a reminder that the following content is due for a refresh:\n\n" .
            "Post: %s\n" .
            "Last updated: %d days ago\n" .
            "Edit URL: %s\n\n" .
            "This reminder was set in AlmaSEO SEO Playground.",
            $post->post_title,
            $days_since_update,
            get_edit_post_link($post_id, 'email')
        );
        
        wp_mail($admin_email, $subject, $message);
    }
    
    // Clear the scheduled meta
    delete_post_meta($post_id, '_almaseo_update_reminder_scheduled');
    }
}
add_action('almaseo_content_refresh_reminder', 'almaseo_handle_content_refresh_reminder');

// Display search engine visibility warning globally
if (!function_exists('almaseo_display_search_engine_warning')) {
    function almaseo_display_search_engine_warning() {
    // Check if search engines are discouraged
    if (get_option('blog_public') != '0') {
        return;
    }
    
    // Check if user has permanently dismissed
    $user_id = get_current_user_id();
    if (get_user_meta($user_id, 'almaseo_dismiss_search_warning_permanent', true)) {
        return;
    }
    
    // Check if temporarily dismissed (24 hours)
    $dismissed_time = get_user_meta($user_id, 'almaseo_dismiss_search_warning_temp', true);
    if ($dismissed_time && (time() - $dismissed_time < 86400)) {
        return;
    }
    
    // Also add a sticky bar at the very top
    ?>
    <div id="almaseo-sticky-warning" style="position: fixed; top: 32px; left: 0; right: 0; z-index: 99999; background: #dc3232; color: white; padding: 10px 20px; text-align: center; box-shadow: 0 2px 8px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: center;">
        <strong style="margin-right: 10px;">🚨 AlmaSEO Alert: Your site is HIDDEN from Google!</strong>
        <a href="<?php echo admin_url('options-reading.php'); ?>" class="button button-small" style="background: white; color: #dc3232; border: none; margin: 0 10px;">Fix Now</a>
        <button type="button" onclick="jQuery('#almaseo-sticky-warning').slideUp();" style="background: transparent; border: none; color: white; cursor: pointer; padding: 0 5px;">✕</button>
    </div>
    <script>
    jQuery(document).ready(function($) {
        // Adjust admin bar spacing
        if ($('#almaseo-sticky-warning').length) {
            $('#wpadminbar').css('top', '42px');
            $('html').css('margin-top', '74px');
        }
    });
    </script>
    <?php
    
    ?>
    <div class="notice notice-error almaseo-search-engine-warning" style="position: relative; padding: 20px; border-left: 4px solid #dc3232; background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.15);">
        <div style="display: flex; align-items: center;">
            <div style="margin-right: 15px;">
                <img src="<?php echo plugin_dir_url(__FILE__); ?>almaseo-logo.png" alt="AlmaSEO" style="height: 40px; width: auto;">
            </div>
            <div style="flex: 1;">
                <h3 style="margin: 0 0 5px 0; color: #dc3232; font-size: 18px;">
                    🚨 CRITICAL SEO ALERT from AlmaSEO
                </h3>
                <p style="margin: 0 0 10px 0; font-size: 14px; color: #444;">
                    <strong>Your website is HIDDEN from search engines!</strong> 
                    WordPress is currently set to "Discourage search engines from indexing this site". 
                    This means your content will NOT appear in Google, Bing, or any other search results.
                </p>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="<?php echo admin_url('options-reading.php'); ?>" class="button button-primary" style="background: #dc3232; border-color: #aa2020;">
                        Fix This Now →
                    </a>
                    <button type="button" class="button almaseo-dismiss-temp" data-nonce="<?php echo wp_create_nonce('almaseo_dismiss_warning'); ?>">
                        Dismiss for 24 hours
                    </button>
                    <button type="button" class="button-link almaseo-dismiss-permanent" data-nonce="<?php echo wp_create_nonce('almaseo_dismiss_warning'); ?>" style="color: #999;">
                        Never show again
                    </button>
                </div>
            </div>
        </div>
    </div>
    <script>
    jQuery(document).ready(function($) {
        $('.almaseo-dismiss-temp').on('click', function() {
            var nonce = $(this).data('nonce');
            var $notice = $(this).closest('.almaseo-search-engine-warning');
            $.post(ajaxurl, {
                action: 'almaseo_dismiss_search_warning',
                type: 'temp',
                nonce: nonce
            }, function() {
                $notice.fadeOut();
            });
        });
        
        $('.almaseo-dismiss-permanent').on('click', function() {
            if (confirm('Are you sure? This warning helps prevent your site from being invisible to search engines.')) {
                var nonce = $(this).data('nonce');
                var $notice = $(this).closest('.almaseo-search-engine-warning');
                $.post(ajaxurl, {
                    action: 'almaseo_dismiss_search_warning',
                    type: 'permanent',
                    nonce: nonce
                }, function() {
                    $notice.fadeOut();
                });
            }
        });
    });
    </script>
    <?php
    }
}
add_action('admin_notices', 'almaseo_display_search_engine_warning');

// AJAX handler for dismissing search engine warning
if (!function_exists('almaseo_ajax_dismiss_search_warning')) {
    function almaseo_ajax_dismiss_search_warning() {
    check_ajax_referer('almaseo_dismiss_warning', 'nonce');
    
    $user_id = get_current_user_id();
    $type = isset($_POST['type']) ? $_POST['type'] : 'temp';
    
    if ($type === 'permanent') {
        update_user_meta($user_id, 'almaseo_dismiss_search_warning_permanent', true);
    } else {
        update_user_meta($user_id, 'almaseo_dismiss_search_warning_temp', time());
    }
    
    wp_die();
    }
}
add_action('wp_ajax_almaseo_dismiss_search_warning', 'almaseo_ajax_dismiss_search_warning');

// Display content reminder admin notices
if (!function_exists('almaseo_display_content_reminders')) {
    function almaseo_display_content_reminders() {
    if (!current_user_can('edit_posts')) {
        return;
    }
    
    $reminders = get_option('almaseo_content_reminders', array());
    if (empty($reminders)) {
        return;
    }
    
    foreach ($reminders as $post_id => $reminder) {
        ?>
        <div class="notice notice-info is-dismissible almaseo-content-reminder" data-post-id="<?php echo esc_attr($post_id); ?>">
            <p>
                <strong>📝 Content Update Reminder:</strong> 
                "<?php echo esc_html($reminder['post_title']); ?>" is due for a refresh 
                (last updated <?php echo esc_html($reminder['days_old']); ?> days ago).
                <a href="<?php echo esc_url($reminder['edit_link']); ?>" class="button button-small" style="margin-left: 10px;">
                    Open AlmaSEO SEO Playground →
                </a>
            </p>
        </div>
        <?php
    }
    }
}
add_action('admin_notices', 'almaseo_display_content_reminders');

// AJAX handler to dismiss content reminders
if (!function_exists('almaseo_ajax_dismiss_content_reminder')) {
    function almaseo_ajax_dismiss_content_reminder() {
    if (!current_user_can('edit_posts')) {
        wp_die();
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if ($post_id) {
        $reminders = get_option('almaseo_content_reminders', array());
        unset($reminders[$post_id]);
        update_option('almaseo_content_reminders', $reminders);
    }
    
    wp_die();
    }
}
add_action('wp_ajax_almaseo_dismiss_content_reminder', 'almaseo_ajax_dismiss_content_reminder');

// AJAX handler to cancel a scheduled reminder
if (!function_exists('almaseo_ajax_cancel_reminder')) {
    function almaseo_ajax_cancel_reminder() {
    if (!current_user_can('edit_posts')) {
        wp_send_json_error('Insufficient permissions');
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) {
        wp_send_json_error('Invalid post ID');
    }
    
    // Clear the reminder
    delete_post_meta($post_id, '_almaseo_update_reminder_enabled');
    delete_post_meta($post_id, '_almaseo_update_reminder_email');
    delete_post_meta($post_id, '_almaseo_update_reminder_scheduled');
    delete_post_meta($post_id, '_almaseo_update_reminder_days');
    
    // Clear scheduled event
    wp_clear_scheduled_hook('almaseo_content_refresh_reminder', array($post_id));
    
    wp_send_json_success('Reminder cancelled');
    }
}
add_action('wp_ajax_almaseo_cancel_reminder', 'almaseo_ajax_cancel_reminder');

// Add CORS headers for AlmaSEO endpoints
add_action('rest_api_init', function() {
    // Add CORS support for AlmaSEO endpoints
    add_filter('rest_pre_serve_request', 'almaseo_add_cors_headers', 10, 4);
});

function almaseo_add_cors_headers($served, $result, $request, $server) {
    // Only add CORS headers for AlmaSEO endpoints
    $route = $request->get_route();
    // Fix: Add null check for $route before using strpos()
    if (!empty($route) && strpos($route, '/almaseo/v1/') !== false) {
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');
        header('Access-Control-Allow-Credentials: true');
        
        // Handle preflight OPTIONS requests
        if ($request->get_method() === 'OPTIONS') {
            header('Access-Control-Max-Age: 86400');
            status_header(200);
            exit();
        }
    }
    return $served;
}

// Check if Application Passwords are available
function almaseo_check_app_passwords_available() {
    if (!function_exists('wp_generate_application_password')) {
        return new WP_Error(
            'app_passwords_not_available',
            'Application Passwords feature is not available. Please ensure you are using WordPress 5.6 or higher.',
            array('status' => 400)
        );
    }
    return true;
}

// REST API endpoint registration - UPDATED
add_action('rest_api_init', function () {
    // Generate application password endpoint
    register_rest_route(ALMASEO_API_NAMESPACE, '/generate-app-password', array(
        'methods'  => 'POST',
        'callback' => 'almaseo_generate_app_password',
        'permission_callback' => 'almaseo_permission_check',
        'args' => array(
            'secret' => array(
                'required' => true,
                'type'     => 'string',
            ),
            'nonce' => array(
                'required' => true,
                'type'     => 'string',
            ),
        ),
    ));

    // AUTO-CONNECT endpoint for onboarding (TEMPORARY) - Updated with CORS support
    register_rest_route(ALMASEO_API_NAMESPACE, '/auto-connect', array(
        'methods'  => array('GET', 'POST', 'OPTIONS'),
        'callback' => 'almaseo_auto_connect',
        'permission_callback' => '__return_true',
    ));

    // Verify connection and capabilities endpoint
    register_rest_route(ALMASEO_API_NAMESPACE, '/verify-connection', array(
        'methods'  => 'GET',
        'callback' => 'almaseo_verify_connection',
        'permission_callback' => 'almaseo_api_auth_check',
    ));

    // Get site capabilities endpoint
    register_rest_route(ALMASEO_API_NAMESPACE, '/site-capabilities', array(
        'methods'  => 'GET',
        'callback' => 'almaseo_get_site_capabilities',
        'permission_callback' => 'almaseo_api_auth_check',
    ));

    // Reserved endpoints for future features
    register_rest_route(ALMASEO_API_NAMESPACE, '/publish-post', array(
        'methods'  => 'POST',
        'callback' => 'almaseo_reserved_endpoint',
        'permission_callback' => 'almaseo_api_auth_check',
    ));

    register_rest_route(ALMASEO_API_NAMESPACE, '/update-meta', array(
        'methods'  => 'POST',
        'callback' => 'almaseo_reserved_endpoint',
        'permission_callback' => 'almaseo_api_auth_check',
    ));

    register_rest_route(ALMASEO_API_NAMESPACE, '/submit-to-search-console', array(
        'methods'  => 'POST',
        'callback' => 'almaseo_reserved_endpoint',
        'permission_callback' => 'almaseo_api_auth_check',
    ));
});

// Permission check: must be admin and secret must match
function almaseo_permission_check( $request ) {
    // Get the AlmaSEO secret (wp-config.php overrides DB)
    $secret = almaseo_get_secret();

    // Verify secret
    $provided_secret = $request->get_param('secret');
    if ( $provided_secret !== $secret ) {
        return new WP_Error('invalid_secret', 'Invalid secret key.', array('status' => 403));
    }

    // Verify nonce
    $nonce = $request->get_param('nonce');
    if (!wp_verify_nonce($nonce, 'almaseo_generate_password')) {
        return new WP_Error('invalid_nonce', 'Invalid security token.', array('status' => 403));
    }

    // Check user is logged in and is admin
    if ( ! is_user_logged_in() ) {
        return new WP_Error('not_logged_in', 'You must be logged in.', array('status' => 401));
    }
    if ( ! current_user_can('administrator') ) {
        return new WP_Error('not_admin', 'You must be an administrator.', array('status' => 403));
    }

    // Check if Application Passwords are available
    $app_passwords_check = almaseo_check_app_passwords_available();
    if (is_wp_error($app_passwords_check)) {
        return $app_passwords_check;
    }

    return true;
}

// Main callback: generate and return application password
// Removed auto-generation logic due to hosting limitations (GoDaddy Managed WordPress). Manual Application Password setup is now required.
function almaseo_generate_app_password( $request ) {
    $user = wp_get_current_user();
    if ( ! $user || ! $user->exists() ) {
        return new WP_Error('no_user', 'Could not determine user.', array('status' => 400));
    }

    // Check if function exists before using
    if (!function_exists('wp_generate_application_password')) {
        return new WP_Error('app_passwords_not_available', 'Application Passwords feature is not available. Please ensure you are using WordPress 5.6 or higher.', array('status' => 400));
    }
    
    // Generate a new application password
    $label = 'AlmaSEO AI ' . date('Y-m-d H:i:s');
    list($new_password, $item) = wp_generate_application_password($user->ID, $label);

    if ( empty($new_password) ) {
        return new WP_Error('generation_failed', 'Failed to generate application password.', array('status' => 500));
    }

    // Return the password and necessary information for AlmaSEO
    return array(
        'success' => true,
        'username' => $user->user_login,
        'application_password' => $new_password,
        'site_info' => array(
            'site_url' => get_site_url(),
            'rest_api_url' => rest_url(),
            'plugin_version' => ALMASEO_PLUGIN_VERSION,
            'wordpress_version' => get_bloginfo('version'),
        ),
        'note' => 'Store this password securely. It will not be shown again.',
    );
}

// API Authentication check for AlmaSEO backend
function almaseo_api_auth_check($request) {
    $auth_header = $request->get_header('Authorization');
    if (!$auth_header) {
        return new WP_Error('no_auth', 'No authorization header.', array('status' => 401));
    }

    // Check if it's a Basic Auth header
    if (!empty($auth_header) && strpos($auth_header, 'Basic ') === 0) {
        $auth_data = base64_decode(substr($auth_header, 6));
        list($username, $password) = explode(':', $auth_data);
        
        // Check if application password authentication is available
        if (!function_exists('wp_authenticate_application_password')) {
            // Fallback to regular authentication for older WordPress versions
            $user = wp_authenticate($username, $password);
        } else {
            // Verify the application password
            $user = wp_authenticate_application_password(null, $username, $password);
        }
        
        if (is_wp_error($user)) {
            return new WP_Error('invalid_auth', 'Invalid credentials.', array('status' => 401));
        }
        
        // Check if the password was generated by our plugin
        if (!class_exists('WP_Application_Passwords')) {
            // For older WordPress versions, just allow the authentication
            $app_passwords = array();
        } else {
            $app_passwords = WP_Application_Passwords::get_user_application_passwords($user->ID);
        }
        $is_almaseo_password = false;
        foreach ($app_passwords as $app_password) {
            if (strpos($app_password['name'] ?? '', 'AlmaSEO AI') === 0) {
                $is_almaseo_password = true;
                break;
            }
        }
        
        if (!$is_almaseo_password) {
            return new WP_Error('invalid_auth', 'Invalid application password.', array('status' => 401));
        }
        
        return true;
    }
    
    return new WP_Error('invalid_auth', 'Invalid authorization method.', array('status' => 401));
}

// Verify connection endpoint callback
function almaseo_verify_connection($request) {
    return array(
        'success' => true,
        'message' => 'Connection verified successfully',
        'site_info' => array(
            'site_name' => get_bloginfo('name'),
            'site_url' => get_site_url(),
            'rest_api_url' => rest_url(),
            'plugin_version' => ALMASEO_PLUGIN_VERSION,
            'wordpress_version' => get_bloginfo('version'),
        )
    );
}

// Get site capabilities endpoint callback
function almaseo_get_site_capabilities($request) {
    $app_passwords_check = almaseo_check_app_passwords_available();
    $app_passwords_available = !is_wp_error($app_passwords_check);

    return array(
        'success' => true,
        'capabilities' => array(
            'post_types' => get_post_types(array('public' => true), 'names'),
            'taxonomies' => get_taxonomies(array('public' => true), 'names'),
            'features' => array(
                'application_passwords' => $app_passwords_available,
                'rest_api' => true,
                'custom_fields' => true,
            ),
            'rest_endpoints' => array(
                'posts' => rest_url('wp/v2/posts'),
                'pages' => rest_url('wp/v2/pages'),
                'media' => rest_url('wp/v2/media'),
                'categories' => rest_url('wp/v2/categories'),
                'tags' => rest_url('wp/v2/tags'),
            )
        )
    );
}

// Reserved endpoint callback for future features
function almaseo_reserved_endpoint($request) {
    return new WP_Error(
        'endpoint_not_implemented',
        'This endpoint is reserved for future use.',
        array('status' => 501)
    );
}

// --- SETTINGS PAGE & SECRET MANAGEMENT ---

// On activation, auto-generate secret if not present
register_activation_hook(__FILE__, 'almaseo_generate_secret_on_activation');
function almaseo_generate_secret_on_activation() {
    if (!get_option('almaseo_secret') && !defined('ALMASEO_SECRET')) {
        $secret = wp_generate_password(32, true, true);
        add_option('almaseo_secret', $secret);
    }
}

// Get the AlmaSEO secret (wp-config.php overrides DB)
function almaseo_get_secret() {
    if (defined('ALMASEO_SECRET')) {
        return ALMASEO_SECRET;
    }
    return get_option('almaseo_secret', '');
}

// Add main menu and submenus
add_action('admin_menu', function() {
    // Add main menu item in sidebar
    add_menu_page(
        'SEO Playground by AlmaSEO', // Page title
        'AlmaSEO SEO Playground', // Menu title (shorter for sidebar)
        'manage_options',
        'seo-playground', // Menu slug
        'seo_playground_render_overview_page', // Function
        'dashicons-search', // Icon (magnifying glass for SEO)
        30 // Position (after Pages, before Comments)
    );
    
    // Add Overview as first submenu (replaces the main menu link)
    add_submenu_page(
        'seo-playground',
        'AlmaSEO SEO Playground Overview',
        'Overview',
        'manage_options',
        'seo-playground', // Same slug as parent to replace it
        'seo_playground_render_overview_page'
    );
    
    // Add Connection Settings submenu
    add_submenu_page(
        'seo-playground',
        'Connection Settings - SEO Playground by AlmaSEO',
        'Connection',
        'manage_options',
        'seo-playground-connection',
        'almaseo_connector_settings_page'
    );
    
    // Add WooCommerce SEO submenu (Pro feature)
    if (class_exists('WooCommerce') && function_exists('almaseo_is_pro') && almaseo_is_pro()) {
        add_submenu_page(
            'seo-playground',
            'WooCommerce SEO - SEO Playground by AlmaSEO',
            'WooCommerce SEO',
            'manage_options',
            'seo-playground-woocommerce',
            'almaseo_woocommerce_settings_page'
        );
    }
    
    // Add welcome page - hidden from menu
    global $wp_version;
    $parent_slug = version_compare($wp_version, '5.3', '>=') ? null : 'admin.php';
    
    add_submenu_page(
        $parent_slug, // Hidden page
        'Welcome to SEO Playground by AlmaSEO', // Page title
        '', // Menu title (empty since it's hidden)
        'manage_options',
        'almaseo-welcome',
        'almaseo_welcome_screen_page'
    );
    
    // Keep the old settings page URL working for backwards compatibility
    add_options_page(
        'AlmaSEO SEO Playground Connection',
        'AlmaSEO SEO Playground',
        'manage_options',
        'almaseo-connector',
        'almaseo_connector_settings_page'
    );
});

// Welcome Screen Page
function almaseo_welcome_screen_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    ?>
    <div class="wrap" style="max-width: 900px; margin: 40px auto;">
        <style>
            .almaseo-welcome-container {
                background: white;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                padding: 40px;
                margin-top: 20px;
            }
            .almaseo-welcome-header {
                text-align: center;
                padding-bottom: 30px;
                border-bottom: 2px solid #f0f0f0;
                margin-bottom: 40px;
            }
            .almaseo-welcome-title {
                font-size: 36px;
                color: #23282d;
                margin: 0 0 15px 0;
                font-weight: 600;
            }
            .almaseo-welcome-subtitle {
                font-size: 18px;
                color: #666;
                margin: 0;
            }
            .almaseo-features-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 25px;
                margin: 40px 0;
            }
            .almaseo-feature-card {
                padding: 25px;
                background: #f9f9f9;
                border-radius: 8px;
                border-left: 4px solid #667eea;
            }
            .almaseo-feature-title {
                font-size: 16px;
                font-weight: 600;
                color: #23282d;
                margin: 0 0 10px 0;
                display: flex;
                align-items: center;
            }
            .almaseo-feature-title .dashicons {
                color: #667eea;
                margin-right: 8px;
            }
            .almaseo-feature-description {
                font-size: 14px;
                color: #666;
                margin: 0;
                line-height: 1.6;
            }
            .almaseo-features-checklist {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                margin: 30px 0;
                padding: 30px;
                background: #f7f8fc;
                border-radius: 8px;
            }
            .almaseo-feature-item {
                display: flex;
                align-items: flex-start;
                font-size: 15px;
                color: #555;
            }
            .almaseo-feature-item .dashicons {
                color: #46b450;
                margin-right: 10px;
                flex-shrink: 0;
                margin-top: 2px;
            }
            .almaseo-cta-section {
                text-align: center;
                padding: 40px 0 20px;
                border-top: 2px solid #f0f0f0;
                margin-top: 40px;
            }
            .almaseo-cta-buttons {
                display: flex;
                gap: 15px;
                justify-content: center;
                margin-top: 25px;
            }
            .almaseo-btn-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 15px 35px;
                font-size: 16px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                transition: transform 0.2s;
            }
            .almaseo-btn-primary:hover {
                transform: translateY(-2px);
                color: white;
                text-decoration: none;
            }
            .almaseo-btn-secondary {
                background: white;
                color: #667eea;
                padding: 15px 35px;
                font-size: 16px;
                border: 2px solid #667eea;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                transition: all 0.2s;
            }
            .almaseo-btn-secondary:hover {
                background: #667eea;
                color: white;
                text-decoration: none;
            }
            .almaseo-ai-badge {
                display: inline-block;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                margin-left: 8px;
            }
        </style>
        
        <div class="almaseo-welcome-container">
            <div class="almaseo-welcome-header">
                <h1 class="almaseo-welcome-title">🎯 Welcome to SEO Playground by AlmaSEO</h1>
                <p class="almaseo-welcome-subtitle">Your AI-powered WordPress SEO optimization toolkit is ready to transform your content</p>
            </div>
            
            <div style="padding: 20px; background: #f0f8ff; border-radius: 8px; margin-bottom: 30px;">
                <p style="margin: 0; font-size: 16px; color: #0073aa;">
                    <strong>🚀 Getting Started:</strong> Connect to AlmaSEO to unlock AI-powered features that will help you create SEO-optimized content in minutes, not hours.
                </p>
            </div>
            
            <h2 style="font-size: 24px; margin-bottom: 25px; color: #23282d;">✨ Key Features</h2>
            
            <div class="almaseo-features-checklist">
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>AI Meta Titles & Descriptions</strong> - Generate optimized metadata instantly</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Focus Keyword Suggestions</strong> - AI-powered keyword recommendations</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Post Intelligence</strong> - AI analysis of your content quality</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Keyword Intelligence</strong> - Deep keyword insights and difficulty</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Schema Analyzer</strong> - Structured data optimization</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Meta Health Score</strong> - Real-time SEO scoring</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Search Console Keywords</strong> - Real GSC data integration</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Content Aging Monitor</strong> - Track and refresh old content</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>SEO Notes</strong> - Private notes for optimization strategy</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>AI Rewrite Assistant</strong> - Optimize existing content</span>
                </div>
            </div>
            
            <div class="almaseo-features-grid">
                <div class="almaseo-feature-card">
                    <h3 class="almaseo-feature-title">
                        <span class="dashicons dashicons-admin-settings"></span>
                        Step 1: Connect to AlmaSEO
                    </h3>
                    <p class="almaseo-feature-description">
                        Click "Connect to AlmaSEO" below to link your site and enable all AI-powered features. The connection process takes less than a minute.
                    </p>
                </div>
                
                <div class="almaseo-feature-card">
                    <h3 class="almaseo-feature-title">
                        <span class="dashicons dashicons-edit"></span>
                        Step 2: Create or Edit Content
                    </h3>
                    <p class="almaseo-feature-description">
                        Open any post or page and find the "AlmaSEO SEO Playground" meta box. This is where all the SEO magic happens.
                    </p>
                </div>
                
                <div class="almaseo-feature-card">
                    <h3 class="almaseo-feature-title">
                        <span class="dashicons dashicons-superhero-alt"></span>
                        Step 3: Use AI Features
                    </h3>
                    <p class="almaseo-feature-description">
                        Click the AI generation buttons to create optimized titles, descriptions, and get keyword suggestions powered by AlmaSEO.
                    </p>
                </div>
            </div>
            
            <div class="almaseo-cta-section">
                <h2 style="font-size: 24px; margin-bottom: 10px;">Ready to supercharge your SEO?</h2>
                <p style="color: #666; font-size: 16px;">Choose an action to get started:</p>
                
                <div class="almaseo-cta-buttons">
                    <a href="<?php echo admin_url('admin.php?page=seo-playground-connection'); ?>" class="almaseo-btn-primary">
                        🔗 Connect to AlmaSEO
                    </a>
                    <a href="<?php echo admin_url('post-new.php'); ?>" class="almaseo-btn-secondary">
                        ✍️ Create New Post
                    </a>
                </div>
                
                <p style="margin-top: 20px; color: #999; font-size: 14px;">
                    Need help? Visit our <a href="https://almaseo.com/docs" target="_blank">documentation</a> or <a href="https://almaseo.com/support" target="_blank">contact support</a>.
                </p>
            </div>
        </div>
    </div>
    <?php
}

// Settings page HTML - Completely redesigned for smooth UX
function almaseo_connector_settings_page() {
    if (!current_user_can('manage_options')) return;
    
    $current_user = wp_get_current_user();
    $username = $current_user->user_login;
    $connection_status = almaseo_get_connection_status();
    $comprehensive_status = almaseo_get_comprehensive_connection_status();
    $app_password = get_option('almaseo_app_password', '');
    
    // Check for dashboard sync success
    if ($sync_success = get_transient('almaseo_dashboard_sync_success')) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>✅ Site Automatically Connected!</strong></p>';
        echo '<p>Your site was found in the AlmaSEO Dashboard (Site ID: ' . esc_html($sync_success['site_id']) . ').</p>';
        echo '<p>Using existing Application Password: ' . esc_html($sync_success['password_name']) . ' for user: ' . esc_html($sync_success['username']) . '</p>';
        echo '</div>';
        delete_transient('almaseo_dashboard_sync_success');
    }
    
    // Check if password import is needed
    if ($needs_import = get_transient('almaseo_needs_password_import')) {
        echo '<div class="notice notice-warning">';
        echo '<p><strong>🔑 Password Import Required</strong></p>';
        echo '<p>Your site is registered in AlmaSEO Dashboard (Site ID: ' . esc_html($needs_import['site_id']) . ') but the Application Password needs to be imported.</p>';
        echo '<p><a href="#import-connection" class="button button-primary">Import Connection Details</a></p>';
        echo '</div>';
    }
    
    // Handle import connection
    if (isset($_POST['import_connection']) && check_admin_referer('almaseo_import_connection')) {
        $import_site_id = isset($_POST['import_site_id']) ? sanitize_text_field($_POST['import_site_id']) : '';
        $import_app_password = isset($_POST['import_app_password']) ? sanitize_text_field($_POST['import_app_password']) : '';
        $import_username = isset($_POST['import_username']) ? sanitize_text_field($_POST['import_username']) : '';
        
        $import_result = almaseo_import_connection_details($import_site_id, $import_app_password, $import_username);
        
        if ($import_result['success']) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>✅ Connection Imported Successfully!</strong></p>';
            echo '<p>Site ID: ' . esc_html($import_result['site_id']) . ' | Username: ' . esc_html($import_result['username']) . '</p>';
            echo '<p>All AlmaSEO SEO Playground features are now unlocked!</p>';
            echo '</div>';
            
            // Refresh connection status
            $connection_status = almaseo_get_connection_status();
            $comprehensive_status = almaseo_get_comprehensive_connection_status();
            $app_password = get_option('almaseo_app_password', '');
        } else {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>❌ Import Failed</strong></p>';
            echo '<p>' . esc_html($import_result['message']) . '</p>';
            echo '</div>';
        }
    }
    
    // Handle manual password saving with improved validation
    if (isset($_POST['save_manual_password']) && check_admin_referer('almaseo_manual_password')) {
        $manual_password = isset($_POST['manual_app_password']) ? sanitize_text_field($_POST['manual_app_password']) : '';
        
        // Clean up password (remove spaces that WordPress adds)
        $cleaned_password = str_replace(' ', '', $manual_password ?? '');
        
        // Validate password format
        if (strlen($cleaned_password) >= 20 && preg_match('/^[A-Za-z0-9]+$/', $cleaned_password)) {
            update_option('almaseo_app_password', $cleaned_password);
            update_option('almaseo_connected_user', $username);
            update_option('almaseo_connected_date', current_time('mysql'));
            $app_password = $cleaned_password;
            
            echo '<div class="notice notice-success almaseo-success-notice" style="padding: 15px; border-left: 4px solid #46b450;">';
            echo '<h3 style="margin-top: 0;">🎉 Perfect! Connection Password Saved Successfully</h3>';
            echo '<p>Great job! Your WordPress plugin is now configured and ready to connect.</p>';
            echo '<p><strong>Next step:</strong> Test the connection using the button below to verify everything is working.</p>';
            echo '</div>';
            
            // Auto-scroll to test button
            echo '<script>setTimeout(function() { 
                var testBtn = document.getElementById("almaseo-test-connection");
                if (testBtn) testBtn.scrollIntoView({behavior: "smooth", block: "center"});
            }, 500);</script>';
        } else {
            echo '<div class="notice notice-error" style="padding: 15px; border-left: 4px solid #dc3232;">';
            echo '<p><strong>Invalid Application Password</strong></p>';
            echo '<p>Please ensure you copied the entire password from WordPress. It should be in format: <code>xxxx xxxx xxxx xxxx xxxx xxxx</code></p>';
            echo '</div>';
        }
    }
    $generated_password = '';
    $generation_error = '';
    
    if (isset($_POST['generate_password']) && check_admin_referer('almaseo_generate_password')) {
        $app_passwords_check = almaseo_check_app_passwords_available();
        if (is_wp_error($app_passwords_check)) {
            $generation_error = $app_passwords_check->get_error_message();
        } else if (function_exists('wp_generate_application_password')) {
            // First clean up any existing AlmaSEO passwords to ensure only one exists
            almaseo_cleanup_old_passwords($current_user->ID);
            
            $label = 'AlmaSEO Connection ' . date('Y-m-d H:i:s');
            list($new_password, $item) = wp_generate_application_password($current_user->ID, $label);
            
            if ($new_password) {
                $generated_password = $new_password;
                update_option('almaseo_app_password', $new_password);
                update_option('almaseo_connected_user', $username);
                update_option('almaseo_connected_date', current_time('mysql'));
                echo '<div class="notice notice-success almaseo-success-notice">';
                echo '<h3>🎉 Excellent! Connection Password Generated Successfully</h3>';
                echo '<p>Perfect! Your WordPress plugin is now configured and ready to connect.</p>';
                echo '</div>';
            } else {
                $generation_error = 'Failed to generate Application Password. Please try again.';
            }
        }
    }
    
    // Handle disconnection
    if (isset($_POST['almaseo_disconnect']) && check_admin_referer('almaseo_disconnect')) {
        almaseo_disconnect_site();
        $connection_status = almaseo_get_connection_status();
        $app_password = '';
        echo '<div class="notice notice-success"><p>Successfully disconnected from AlmaSEO.</p></div>';
    }

    echo '<div class="almaseo-container">';
    
    // Header with prominent logo
    $logo_url = plugins_url('almaseo-logo.png', __FILE__);
    echo '<div class="almaseo-header">';
    echo '<div class="header-content">';
    echo '<div class="header-text">';
    echo '<h1>SEO Playground by AlmaSEO - Connection Settings</h1>';
    echo '<p>Connect your WordPress site to AlmaSEO AI for automated content creation</p>';
    // Add help text for Connection
    if (function_exists('almaseo_render_help')) {
        almaseo_render_help(
            __('Connect to AlmaSEO Dashboard to unlock AI tools and enhanced data sources. Core features work without it.', 'almaseo')
        );
    }
    echo '</div>';
    echo '<div class="header-logo">';
    echo '<img src="' . esc_url($logo_url) . '" alt="AlmaSEO Assistant" class="almaseo-character">';
    echo '</div>';
    echo '</div>';
    echo '</div>';
    
    if ($connection_status['connected'] && $app_password) {
        // Connected State
        echo '<div class="almaseo-connected-state">';
        echo '<div class="status-badge connected">';
        echo '<i class="dashicons dashicons-yes-alt"></i>';
        echo '<span>Connected to AlmaSEO</span>';
        echo '</div>';
        
        echo '<div class="connection-details">';
        echo '<div class="detail-item">';
        echo '<strong>Site:</strong> ' . esc_html(get_bloginfo('name'));
        echo '</div>';
        
        // Show Site ID if available
        if ($comprehensive_status['site_id']) {
            echo '<div class="detail-item">';
            echo '<strong>Site ID:</strong> <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">' . esc_html($comprehensive_status['site_id']) . '</code>';
            echo '</div>';
        }
        
        echo '<div class="detail-item">';
        echo '<strong>Connected User:</strong> ' . esc_html($username ?: 'Unknown');
        echo '</div>';
        
        // Show connection type
        $connection_type_display = array(
            'dashboard_initiated' => '🌐 Dashboard',
            'plugin_initiated' => '🔌 Plugin',
            'imported' => '📥 Imported',
            'unknown' => '❓ Unknown'
        );
        $conn_type = $comprehensive_status['connection_type'];
        echo '<div class="detail-item">';
        echo '<strong>Connection Type:</strong> ' . ($connection_type_display[$conn_type] ?? $connection_type_display['unknown']);
        echo '</div>';
        
        // Show connection status icons
        echo '<div class="detail-item">';
        echo '<strong>Status:</strong> ';
        echo '<span style="margin-right: 10px;">Plugin ' . ($comprehensive_status['plugin_connected'] ? '✅' : '❌') . '</span>';
        echo '<span>Dashboard ' . ($comprehensive_status['dashboard_connected'] ? '✅' : '❌') . '</span>';
        echo '</div>';
        
        echo '<div class="detail-item">';
        echo '<strong>Connected:</strong> ' . esc_html($connection_status['connected_date'] ?? 'Unknown');
        echo '</div>';
        
        // Show detected passwords if any
        if (isset($comprehensive_status['detected_passwords']) && $comprehensive_status['detected_passwords'] > 0) {
            echo '<div class="detail-item" style="background: #fff3cd; padding: 5px; border-radius: 3px; margin-top: 5px;">';
            echo '<strong>⚠️ Note:</strong> Found ' . $comprehensive_status['detected_passwords'] . ' AlmaSEO password(s) for users: ' . implode(', ', $comprehensive_status['detected_users']);
            echo '</div>';
        }
        
        echo '</div>';
        
        echo '<div class="connected-actions" style="margin-top: 20px; padding: 15px; background: #f0f8ff; border-radius: 4px;">';
        echo '<button type="button" class="button button-secondary test-connection-btn" id="almaseo-test-connection" style="height: 40px; padding: 0 20px; font-size: 14px;">';
        echo '<span class="dashicons dashicons-update" style="margin-right: 5px; line-height: 28px; vertical-align: middle;"></span>';
        echo 'Test Connection to AlmaSEO API</button>';
        echo '<span class="connection-test-result" id="test-result" style="display: inline-block; margin-left: 15px; font-size: 14px;"></span>';
        echo '<div class="connection-status-indicator" style="margin-top: 10px; font-size: 13px; color: #666;">';
        echo '<span class="dashicons dashicons-info" style="color: #0073aa;"></span>';
        echo ' Click to verify your connection with the AlmaSEO API server';
        echo '</div>';
        echo '</div>';
        
        echo '<form method="post" class="disconnect-form">';
        wp_nonce_field('almaseo_disconnect');
        echo '<button type="submit" name="almaseo_disconnect" value="1" class="button button-link-delete" onclick="return confirm(\'Are you sure you want to disconnect from AlmaSEO? This will remove API access.\')">Disconnect Site</button>';
        echo '</form>';
        
        echo '</div>';
        
        // Schema Settings Section (always shown)
        echo '</div>'; // Close connected state div
    } // Close if connected
    
    if (!$connection_status['connected'] || !$app_password) {
        // Setup Wizard State
        echo '<div class="almaseo-setup-wizard">';
        
        if ($generation_error) {
            echo '<div class="notice notice-error"><p>' . esc_html($generation_error ?: 'An error occurred') . '</p></div>';
            
            // Add fallback instructions for hosting limitations
            if (strpos($generation_error ?? '', 'not available') !== false) {
                echo '<div class="almaseo-fallback-instructions">';
                echo '<h4>🔧 Alternative Setup Method</h4>';
                echo '<p>Your hosting provider may have disabled automatic Application Password generation. No problem! You can create one manually:</p>';
                
                echo '<div class="manual-steps-container">';
                echo '<div class="manual-steps">';
                echo '<div class="step-instruction">';
                echo '<div class="step-number-small">1</div>';
                echo '<div class="step-content">';
                echo '<strong>Open WordPress Users Page</strong>';
                echo '<p>Click the button below to open Users → Your Profile in a new tab:</p>';
                echo '<a href="' . admin_url('profile.php') . '" target="_blank" class="button button-secondary">';
                echo '<i class="dashicons dashicons-admin-users"></i> Open Your Profile';
                echo '</a>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="step-instruction">';
                echo '<div class="step-number-small">2</div>';
                echo '<div class="step-content">';
                echo '<strong>Create Application Password</strong>';
                echo '<p>In the new tab, scroll down to <strong>"Application Passwords"</strong> section and:</p>';
                echo '<ul>';
                echo '<li>Enter <code>AlmaSEO Connection</code> as the name</li>';
                echo '<li>Click <strong>"Add New Application Password"</strong></li>';
                echo '<li>Copy the generated password (it looks like: <code>abcd efgh ijkl mnop</code>)</li>';
                echo '</ul>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="step-instruction">';
                echo '<div class="step-number-small">3</div>';
                echo '<div class="step-content">';
                echo '<strong>Return and Paste Below</strong>';
                echo '<p>Come back to this tab and paste your password in the form below:</p>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                
                echo '<div class="manual-password-section">';
                echo '<h5><i class="dashicons dashicons-admin-network"></i> Paste Your Application Password Here:</h5>';
                echo '<form method="post">';
                wp_nonce_field('almaseo_manual_password');
                echo '<div class="password-input-group">';
                echo '<input type="text" name="manual_app_password" placeholder="Paste your Application Password here (e.g., abcd efgh ijkl mnop)" class="connection-detail-input" style="width: 100%; margin: 10px 0;">';
                echo '<button type="submit" name="save_manual_password" class="button button-primary button-large">Save Connection Password</button>';
                echo '</div>';
                echo '</form>';
                echo '<div class="help-note">';
                echo '<i class="dashicons dashicons-info"></i>';
                echo '<small>The password will be 16 characters with spaces (like: abcd efgh ijkl mnop). Paste the entire thing including spaces.</small>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        }
        
        // Step 1: Generate Password
        echo '<div class="setup-step ' . (!$app_password && !$generated_password ? 'active' : ($app_password || $generated_password ? 'completed' : '')) . '">';
        echo '<div class="step-header">';
        echo '<div class="step-number">1</div>';
        echo '<h3>Generate Connection Password</h3>';
        echo '</div>';
        
        if (!$app_password && !$generated_password) {
            // Only show the generate button if we haven't already tried and failed
            if (empty($generation_error)) {
                echo '<p>Create a secure Application Password for AlmaSEO to connect to your site.</p>';
                echo '<form method="post">';
                wp_nonce_field('almaseo_generate_password');
                echo '<button type="submit" name="generate_password" class="button button-primary button-large">Generate Password Now</button>';
                echo '</form>';
            } else {
                // If generation failed, don't show the button again
                echo '<div class="step-completed error">';
                echo '<i class="dashicons dashicons-warning"></i>';
                echo '<span>Automatic generation not available on your hosting</span>';
                echo '</div>';
                echo '<p>Please use the manual method below instead.</p>';
            }
        } else {
            echo '<div class="step-completed">';
            echo '<i class="dashicons dashicons-yes-alt"></i>';
            echo '<span>Password generated successfully!</span>';
            echo '</div>';
        }
        echo '</div>';
        
        // Step 2: Copy Details (only show if password exists)
        if ($app_password || $generated_password) {
            $display_password = $generated_password ?: $app_password;
            
            echo '<div class="setup-step active">';
            echo '<div class="step-header">';
            echo '<div class="step-number">2</div>';
            echo '<h3>Copy Your Connection Details</h3>';
            echo '</div>';
            
            echo '<p>Copy these details to paste into your AlmaSEO dashboard:</p>';
            
            echo '<div class="connection-details-box">';
            echo '<div class="detail-row">';
            echo '<label>Site URL:</label>';
            echo '<div class="detail-value">';
            echo '<input type="text" readonly value="' . esc_attr(get_site_url()) . '" class="connection-detail-input">';
            echo '<button type="button" class="copy-btn" data-copy="' . esc_attr(get_site_url()) . '">Copy</button>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="detail-row">';
            echo '<label>Username:</label>';
            echo '<div class="detail-value">';
            echo '<input type="text" readonly value="' . esc_attr($username) . '" class="connection-detail-input">';
            echo '<button type="button" class="copy-btn" data-copy="' . esc_attr($username) . '">Copy</button>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="detail-row">';
            echo '<label>Application Password:</label>';
            echo '<div class="detail-value">';
            echo '<input type="text" readonly value="' . esc_attr($display_password) . '" class="connection-detail-input">';
            echo '<button type="button" class="copy-btn" data-copy="' . esc_attr($display_password) . '">Copy</button>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="copy-all-section">';
            echo '<button type="button" class="button button-primary copy-all-btn">Copy All Details</button>';
            echo '<span class="copy-feedback"></span>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            echo '<div class="final-success-section">';
            echo '<div class="success-celebration">';
            echo '<div class="success-icon">✅</div>';
            echo '<h3>Plugin Configuration Complete!</h3>';
            echo '<p>Now return to your AlmaSEO onboarding to continue the setup process.</p>';
            echo '</div>';
            
            echo '<div class="next-steps-container">';
            echo '<h4>📋 Next Step:</h4>';
            
            echo '<div class="next-step-item onboarding-return">';
            echo '<div class="next-step-number">→</div>';
            echo '<div class="next-step-content">';
            echo '<strong>Return to AlmaSEO Onboarding</strong>';
            echo '<p>Go back to your AlmaSEO setup tab in your browser and click <strong>"Plugin Installed"</strong> to continue.</p>';
            echo '<div class="onboarding-reminder">';
            echo '<div class="reminder-icon">💡</div>';
            echo '<div class="reminder-text">';
            echo '<p><strong>Can\'t find the AlmaSEO tab?</strong> Look for the browser tab where you started adding your site. It should show the 3-step setup process with "Install Plugin" highlighted.</p>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            
            // Simple connection details for reference
            echo '<div class="setup-step completed reference-step">';
            echo '<div class="step-header">';
            echo '<div class="step-number">✓</div>';
            echo '<h3>Your Connection Details (For Reference)</h3>';
            echo '</div>';
            
            echo '<p>These details are now stored securely. The onboarding process will use them automatically:</p>';
        }
        
        echo '</div>'; // End setup wizard
    }
    
    // Help Section
    echo '<div class="almaseo-help-section">';
    echo '<button type="button" class="help-toggle button button-secondary">Need Help?</button>';
    echo '<div class="help-content" style="display: none;">';
    echo '<h4>Troubleshooting</h4>';
    echo '<ul>';
    echo '<li><strong>Password generation fails:</strong> Ensure you\'re using WordPress 5.6 or higher</li>';
    echo '<li><strong>Connection issues:</strong> Verify your site is accessible from the internet</li>';
    echo '<li><strong>Permission errors:</strong> Make sure you have administrator privileges</li>';
    echo '<li><strong>Still having problems?</strong> Contact <a href="mailto:support@almaseo.com">support@almaseo.com</a></li>';
    echo '</ul>';
    echo '</div>';
    echo '</div>';
    
    echo '</div>'; // End container
    
    // Add styles and scripts (keeping the existing CSS from the original)
    echo '<style>
    .almaseo-container {
        max-width: 800px;
        margin: 20px 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    
    .almaseo-header {
        background: linear-gradient(135deg, #4CAF50, #45a049);
        color: white;
        padding: 30px;
        border-radius: 8px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }
    
    .header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 30px;
    }
    
    .header-text {
        flex: 1;
    }
    
    .almaseo-header h1 {
        margin: 0 0 20px 0;
        font-size: 32px;
        font-weight: 700;
        line-height: 1.4;
        letter-spacing: -0.5px;
    }
    
    .almaseo-header p {
        margin: 0;
        opacity: 0.95;
        font-size: 16px;
        line-height: 1.6;
        padding-top: 5px;
    }
    
    .header-logo {
        flex-shrink: 0;
    }
    
    .almaseo-character {
        width: 240px;
        height: 240px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        padding: 20px;
        transition: transform 0.3s ease;
        filter: drop-shadow(0 4px 12px rgba(0,0,0,0.2));
    }
    
    .almaseo-character:hover {
        transform: scale(1.05) rotate(2deg);
    }
    
    .almaseo-connected-state {
        background: #f0f8f0;
        border: 2px solid #4CAF50;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
    }
    
    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 20px;
        border-radius: 25px;
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 20px;
    }
    
    .status-badge.connected {
        background: #4CAF50;
        color: white;
    }
    
    .connection-details {
        background: white;
        padding: 20px;
        border-radius: 6px;
        margin: 20px 0;
        text-align: left;
    }
    
    .detail-item {
        margin-bottom: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }
    
    .detail-item:last-child {
        border-bottom: none;
    }
    
    .connected-actions {
        margin: 20px 0;
    }
    
    .connection-test-result {
        margin-left: 15px;
        font-weight: 600;
    }
    
    .disconnect-form {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
    }
    
    .almaseo-setup-wizard {
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
    }
    
    .setup-step {
        padding: 30px;
        border-bottom: 1px solid #eee;
        position: relative;
    }
    
    .setup-step:last-child {
        border-bottom: none;
    }
    
    .setup-step.active {
        background: #fafafa;
    }
    
    .setup-step.completed {
        background: #f0f8f0;
    }
    
    .step-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
    }
    
    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #4CAF50;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
    }
    
    .setup-step:not(.active):not(.completed) .step-number {
        background: #ccc;
    }
    
    .step-header h3 {
        margin: 0;
        font-size: 20px;
        color: #333;
    }
    
    .step-completed {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #4CAF50;
        font-weight: 600;
    }
    
    .step-completed.error {
        color: #e74c3c;
        background: #ffeaea;
        padding: 12px;
        border-radius: 6px;
        border-left: 4px solid #e74c3c;
    }
    
    .connection-details-box {
        background: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 20px;
        margin: 15px 0;
    }
    
    .detail-row {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }
    
    .detail-row:last-child {
        margin-bottom: 0;
    }
    
    .detail-row label {
        width: 150px;
        font-weight: 600;
        color: #555;
    }
    
    .detail-value {
        flex: 1;
        display: flex;
        gap: 10px;
    }
    
    .connection-detail-input {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: white;
        font-family: monospace;
    }
    
    .copy-btn {
        background: #4CAF50;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
    }
    
    .copy-btn:hover {
        background: #45a049;
    }
    
    .copy-all-section {
        text-align: center;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
    }
    
    .copy-feedback {
        margin-left: 15px;
        font-weight: 600;
        color: #4CAF50;
    }
    
    .almaseo-instructions {
        background: #e8f4fd;
        border-left: 4px solid #2196F3;
        padding: 20px;
        margin: 15px 0;
        border-radius: 0 6px 6px 0;
    }
    
    .almaseo-instructions ol {
        margin: 0;
        padding-left: 20px;
    }
    
    .almaseo-instructions li {
        margin-bottom: 8px;
        line-height: 1.5;
    }
    
    .final-action {
        text-align: center;
        margin-top: 20px;
    }
    
    .final-action .button-large {
        padding: 15px 30px;
        font-size: 16px;
        height: auto;
    }
    
    .almaseo-help-section {
        margin-top: 30px;
        text-align: center;
    }
    
    .help-content {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 20px;
        margin-top: 15px;
        text-align: left;
    }
    
    .help-content h4 {
        margin-top: 0;
        color: #333;
    }
    
    .help-content ul {
        margin: 0;
    }
    
    .almaseo-fallback-instructions {
        background: #e8f4fd;
        border: 1px solid #2196F3;
        border-radius: 8px;
        padding: 25px;
        margin: 20px 0;
    }
    
    .almaseo-fallback-instructions h4 {
        color: #1976D2;
        margin-top: 0;
    }
    
    .almaseo-fallback-instructions ol {
        padding-left: 20px;
    }
    
    .almaseo-fallback-instructions li {
        margin-bottom: 8px;
        line-height: 1.5;
    }
    
    .manual-password-section {
        background: white;
        padding: 20px;
        border-radius: 6px;
        margin-top: 20px;
        border: 1px solid #ddd;
    }
    
    .manual-password-section h5 {
        margin-top: 0;
        color: #333;
    }
    
    .manual-steps-container {
        margin: 20px 0;
    }
    
    .manual-steps {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-bottom: 25px;
    }
    
    .step-instruction {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        padding: 15px;
        background: rgba(255, 255, 255, 0.7);
        border-radius: 8px;
        border-left: 4px solid #2196F3;
    }
    
    .step-number-small {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #2196F3;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        flex-shrink: 0;
        margin-top: 5px;
    }
    
    .step-content {
        flex: 1;
    }
    
    .step-content strong {
        color: #1976D2;
        font-size: 16px;
    }
    
    .step-content p {
        margin: 5px 0 10px 0;
    }
    
    .step-content ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .step-content li {
        margin-bottom: 5px;
    }
    
    .password-input-group {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    .help-note {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 10px;
        padding: 10px;
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 4px;
        color: #856404;
    }
    
    .almaseo-success-notice {
        background: linear-gradient(135deg, #d4edda, #c3e6cb) !important;
        border: 2px solid #28a745 !important;
        border-radius: 8px !important;
        padding: 20px !important;
        margin: 20px 0 !important;
    }
    
    .almaseo-success-notice h3 {
        margin: 0 0 10px 0 !important;
        color: #155724 !important;
        font-size: 20px !important;
    }
    
    .almaseo-success-notice p {
        margin: 0 !important;
        color: #155724 !important;
        font-size: 16px !important;
    }
    
    .final-success-section {
        background: #f8fff8;
        border: 2px solid #28a745;
        border-radius: 12px;
        padding: 30px;
        margin: 20px 0;
    }
    
    .success-celebration {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e0e0e0;
    }
    
    .success-icon {
        font-size: 48px;
        margin-bottom: 24px;
    }
    
    .success-celebration h3 {
        color: #155724;
        margin: 0 0 10px 0;
        font-size: 24px;
    }
    
    .success-celebration p {
        color: #155724;
        font-size: 16px;
        margin: 0;
    }
    
    .next-steps-container {
        margin-top: 20px;
    }
    
    .next-steps-container h4 {
        color: #155724;
        margin-bottom: 20px;
        font-size: 18px;
    }
    
    .next-step-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 25px;
        padding: 15px;
        background: rgba(255, 255, 255, 0.8);
        border-radius: 8px;
        border-left: 4px solid #28a745;
    }
    
    .next-step-number {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #28a745;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        flex-shrink: 0;
        margin-top: 5px;
    }
    
    .next-step-content {
        flex: 1;
    }
    
    .next-step-content strong {
        color: #155724;
        font-size: 16px;
    }
    
    .next-step-content p {
        margin: 5px 0 10px 0;
        color: #155724;
    }
    
    .next-step-btn {
        margin-top: 10px;
    }
    
    .connection-preview {
        background: white;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 15px;
        margin-top: 10px;
    }
    
    .preview-item {
        margin-bottom: 8px;
        font-family: monospace;
        font-size: 14px;
        color: #333;
    }
    
    .preview-item:last-child {
        margin-bottom: 0;
    }
    
    .final-step {
        background: #f0f8f0;
        border: 2px solid #28a745;
    }
    
    @media (max-width: 768px) {
        .step-instruction {
            flex-direction: column;
            text-align: center;
        }
        
        .step-number-small {
            margin: 0 auto 10px auto;
        }
        
        .next-step-item {
            flex-direction: column;
            text-align: center;
        }
        
        .next-step-number {
            margin: 0 auto 10px auto;
        }
    }
    
    @media (max-width: 768px) {
        .header-content {
            flex-direction: column;
            text-align: center;
        }
        
        .almaseo-character {
            width: 180px;
            height: 180px;
        }
        
        .detail-row {
            flex-direction: column;
            align-items: stretch;
        }
        
        .detail-row label {
            width: auto;
            margin-bottom: 5px;
        }
    }
    </style>';
    
    // Import Connection Details Section
    echo '<div id="import-connection" class="almaseo-import-section" style="margin-top: 40px; padding: 20px; background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 5px; display: ' . ($comprehensive_status['plugin_connected'] ? 'none' : 'block') . ';">';
    echo '<h3 style="margin-top: 0;">📥 Import Connection from Dashboard</h3>';
    echo '<p>If your site is already registered in the AlmaSEO Dashboard, you can import the connection details here.</p>';
    
    echo '<form method="post" id="import-connection-form">';
    wp_nonce_field('almaseo_import_connection');
    
    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row"><label for="import_site_id">Site ID</label></th>';
    echo '<td>';
    echo '<input type="text" id="import_site_id" name="import_site_id" value="' . esc_attr($comprehensive_status['site_id']) . '" class="regular-text" placeholder="e.g., 2049" />';
    echo '<p class="description">The Site ID from your AlmaSEO Dashboard</p>';
    echo '</td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<th scope="row"><label for="import_app_password">Application Password</label></th>';
    echo '<td>';
    echo '<input type="text" id="import_app_password" name="import_app_password" class="regular-text" placeholder="xxxx xxxx xxxx xxxx xxxx xxxx" />';
    echo '<p class="description">The Application Password (with or without spaces)</p>';
    echo '</td>';
    echo '</tr>';
    
    echo '<tr>';
    echo '<th scope="row"><label for="import_username">WordPress Username (Optional)</label></th>';
    echo '<td>';
    echo '<input type="text" id="import_username" name="import_username" value="' . esc_attr($username) . '" class="regular-text" />';
    echo '<p class="description">Leave blank to auto-detect</p>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    
    echo '<p class="submit">';
    echo '<button type="submit" name="import_connection" class="button button-primary">Import Connection</button>';
    echo '<button type="button" id="check-dashboard" class="button button-secondary" style="margin-left: 10px;">Check Dashboard Registration</button>';
    echo '</p>';
    echo '</form>';
    
    echo '<div id="import-result" style="margin-top: 15px; display: none;"></div>';
    echo '</div>';
    
    // Diagnostic Mode Section (only visible when WP_DEBUG is true)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        echo '<div class="almaseo-diagnostic-section" style="margin-top: 40px; padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px;">';
        echo '<h3 style="margin-top: 0;">🔧 Diagnostic Mode (Debug)</h3>';
        echo '<details>';
        echo '<summary style="cursor: pointer; font-weight: bold;">Connection Debug Information</summary>';
        echo '<pre style="background: white; padding: 15px; border-radius: 3px; overflow-x: auto; margin-top: 10px;">';
        echo 'Comprehensive Status:' . PHP_EOL;
        print_r($comprehensive_status);
        echo PHP_EOL . 'Basic Connection Status:' . PHP_EOL;
        print_r($connection_status);
        echo PHP_EOL . 'Stored Options:' . PHP_EOL;
        echo 'almaseo_dashboard_site_id: ' . get_option('almaseo_dashboard_site_id', 'not set') . PHP_EOL;
        echo 'almaseo_connection_type: ' . get_option('almaseo_connection_type', 'not set') . PHP_EOL;
        echo 'almaseo_dashboard_synced: ' . (get_option('almaseo_dashboard_synced') ? 'true' : 'false') . PHP_EOL;
        echo 'almaseo_dashboard_registered: ' . (get_option('almaseo_dashboard_registered') ? 'true' : 'false') . PHP_EOL;
        echo '</pre>';
        echo '</details>';
        echo '</div>';
    }
    
    echo '<script>
    jQuery(document).ready(function($) {
        // Handle import connection form
        $("#import-connection-form").on("submit", function(e) {
            e.preventDefault();
            
            const siteId = $("#import_site_id").val();
            const appPassword = $("#import_app_password").val();
            const username = $("#import_username").val();
            
            if (!siteId || !appPassword) {
                $("#import-result").html("<div class=\"notice notice-error\"><p>Please provide both Site ID and Application Password.</p></div>").show();
                return;
            }
            
            // Show loading
            $("#import-result").html("<div class=\"notice notice-info\"><p>Importing connection details...</p></div>").show();
            
            // Submit form
            this.submit();
        });
        
        // Check dashboard registration
        $("#check-dashboard").on("click", function() {
            const btn = $(this);
            btn.prop("disabled", true).text("Checking...");
            
            $.post(ajaxurl, {
                action: "almaseo_check_dashboard",
                nonce: "' . wp_create_nonce('almaseo_nonce') . '"
            }, function(response) {
                if (response.success) {
                    let message = response.data.registered ? 
                        "✅ Site found in dashboard (Site ID: " + response.data.site_id + ")" : 
                        "❌ Site not found in dashboard";
                    $("#import-result").html("<div class=\"notice notice-info\"><p>" + message + "</p></div>").show();
                    
                    if (response.data.site_id) {
                        $("#import_site_id").val(response.data.site_id);
                    }
                } else {
                    $("#import-result").html("<div class=\"notice notice-error\"><p>Check failed: " + response.data.message + "</p></div>").show();
                }
            }).always(function() {
                btn.prop("disabled", false).text("Check Dashboard Registration");
            });
        });
        
        // Copy functionality
        $(".copy-btn").on("click", function() {
            const text = $(this).data("copy");
            navigator.clipboard.writeText(text).then(function() {
                const btn = $(event.target);
                const originalText = btn.text();
                btn.text("Copied!");
                setTimeout(() => btn.text(originalText), 2000);
            });
        });
        
        // Copy all details
        $(".copy-all-btn").on("click", function() {
            const siteUrl = $(".connection-detail-input").eq(0).val();
            const username = $(".connection-detail-input").eq(1).val();
            const password = $(".connection-detail-input").eq(2).val();
            
            const allDetails = `Site URL: ${siteUrl}\nUsername: ${username}\nApplication Password: ${password}`;
            
            navigator.clipboard.writeText(allDetails).then(function() {
                $(".copy-feedback").text("All details copied to clipboard!").show();
                setTimeout(() => $(".copy-feedback").fadeOut(), 3000);
            });
        });
        
        // Help toggle
        $(".help-toggle").on("click", function() {
            $(".help-content").slideToggle();
        });
        
        // Test connection
        $(".test-connection-btn").on("click", function() {
            const btn = $(this);
            const result = $(".connection-test-result");
            
            btn.prop("disabled", true).text("Testing...");
            result.empty();
            
            $.post(ajaxurl, {
                action: "almaseo_test_connection"
            }).done(function(response) {
                if (response.success) {
                    result.html("<span style=\"color: #4CAF50;\">✓ Connection successful</span>");
                } else {
                    result.html("<span style=\"color: #e74c3c;\">✗ " + (response.data.message || "Connection failed") + "</span>");
                }
            }).fail(function() {
                result.html("<span style=\"color: #e74c3c;\">✗ Connection test failed</span>");
            }).always(function() {
                btn.prop("disabled", false).text("Test Connection");
            });
        });
    });
    </script>';
}

// --- SITE DISCOVERY AND CONNECTION SYNC FUNCTIONS ---

/**
 * Check if site is already registered in AlmaSEO Dashboard
 * @return array Registration status and details
 */
function almaseo_check_dashboard_registration() {
    $site_url = get_site_url();
    $site_domain = parse_url($site_url, PHP_URL_HOST);
    
    // Log discovery attempt if debug mode
    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log('[AlmaSEO] Checking dashboard registration for domain: ' . $site_domain);
    }
    
    // Call dashboard API to check if site exists
    $api_url = 'https://api.almaseo.com/api/site-discovery';
    $response = wp_remote_post($api_url, array(
        'timeout' => 15,
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'body' => json_encode(array(
            'domain' => $site_domain,
            'site_url' => $site_url,
            'plugin_version' => ALMASEO_PLUGIN_VERSION
        ))
    ));
    
    if (is_wp_error($response)) {
        // Log error if debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[AlmaSEO] Dashboard check failed: ' . $response->get_error_message());
        }
        return array(
            'registered' => false,
            'error' => $response->get_error_message()
        );
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code === 200) {
        $data = json_decode($response_body, true);
        
        // Store dashboard info if site is registered
        if ($data && isset($data['registered']) && $data['registered']) {
            update_option('almaseo_dashboard_site_id', $data['site_id']);
            update_option('almaseo_dashboard_registered', true);
            update_option('almaseo_dashboard_check_date', current_time('mysql'));
            
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('[AlmaSEO] Site found in dashboard: Site ID ' . $data['site_id']);
            }
            
            return $data;
        }
    }
    
    return array(
        'registered' => false,
        'response_code' => $response_code
    );
}

/**
 * Auto-detect existing AlmaSEO Application Passwords
 * @return array|false Found password details or false
 */
function almaseo_auto_detect_app_passwords() {
    if (!class_exists('WP_Application_Passwords')) {
        return false;
    }
    
    $found_passwords = array();
    
    // Check all admin users for AlmaSEO passwords
    $users = get_users(array('role' => 'administrator'));
    
    foreach ($users as $user) {
        $app_passwords = WP_Application_Passwords::get_user_application_passwords($user->ID);
        
        foreach ($app_passwords as $app_password) {
            // Check for various AlmaSEO password patterns
            if (preg_match('/AlmaSEO|alma.?seo|SEO.?Playground/i', $app_password['name'] ?? '')) {
                $found_passwords[] = array(
                    'user_id' => $user->ID,
                    'username' => $user->user_login,
                    'password_name' => $app_password['name'],
                    'created' => $app_password['created'],
                    'uuid' => $app_password['uuid']
                );
                
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('[AlmaSEO] Found existing password: ' . $app_password['name'] . ' for user: ' . $user->user_login);
                }
            }
        }
    }
    
    return !empty($found_passwords) ? $found_passwords : false;
}

/**
 * Sync connection from dashboard if site is pre-registered
 */
function almaseo_sync_from_dashboard() {
    // Check dashboard registration
    $dashboard_status = almaseo_check_dashboard_registration();
    
    if ($dashboard_status['registered']) {
        // Auto-detect existing passwords
        $existing_passwords = almaseo_auto_detect_app_passwords();
        
        if ($existing_passwords) {
            // Use the first found password
            $password_info = $existing_passwords[0];
            
            // Store connection details
            update_option('almaseo_connected_user', $password_info['username']);
            update_option('almaseo_dashboard_synced', true);
            update_option('almaseo_connection_type', 'dashboard_initiated');
            
            // Set transient for admin notice
            set_transient('almaseo_dashboard_sync_success', array(
                'site_id' => $dashboard_status['site_id'],
                'username' => $password_info['username'],
                'password_name' => $password_info['password_name']
            ), 60);
            
            return true;
        } elseif ($dashboard_status['application_password_exists']) {
            // Password exists in dashboard but not locally - prompt for import
            set_transient('almaseo_needs_password_import', array(
                'site_id' => $dashboard_status['site_id']
            ), 3600);
        }
    }
    
    return false;
}

/**
 * Import connection details manually
 */
function almaseo_import_connection_details($site_id, $app_password, $username = null) {
    // Validate inputs
    if (empty($site_id) || empty($app_password)) {
        return array(
            'success' => false,
            'message' => 'Site ID and Application Password are required'
        );
    }
    
    // Clean the password
    $cleaned_password = str_replace(' ', '', $app_password);
    
    // If no username provided, try to detect from existing passwords
    if (!$username) {
        $existing_passwords = almaseo_auto_detect_app_passwords();
        if ($existing_passwords) {
            $username = $existing_passwords[0]['username'];
        } else {
            $username = wp_get_current_user()->user_login;
        }
    }
    
    // Store connection details
    update_option('almaseo_dashboard_site_id', $site_id);
    update_option('almaseo_app_password', $cleaned_password);
    update_option('almaseo_connected_user', $username);
    update_option('almaseo_connected_date', current_time('mysql'));
    update_option('almaseo_connection_type', 'imported');
    update_option('almaseo_dashboard_synced', true);
    
    // Test the connection
    $test_result = almaseo_test_imported_connection($username, $cleaned_password);
    
    if ($test_result['success']) {
        return array(
            'success' => true,
            'message' => 'Connection imported successfully',
            'site_id' => $site_id,
            'username' => $username
        );
    } else {
        // Rollback on failure
        delete_option('almaseo_dashboard_site_id');
        delete_option('almaseo_app_password');
        delete_option('almaseo_connection_type');
        
        return array(
            'success' => false,
            'message' => 'Connection test failed: ' . $test_result['message']
        );
    }
}

/**
 * Test imported connection credentials
 */
function almaseo_test_imported_connection($username, $password) {
    $api_url = 'https://api.almaseo.com/api/v1/verify';
    
    $response = wp_remote_post($api_url, array(
        'timeout' => 15,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($username . ':' . $password)
        ),
        'body' => json_encode(array(
            'site_url' => get_site_url(),
            'site_id' => get_option('almaseo_dashboard_site_id', '')
        ))
    ));
    
    if (is_wp_error($response)) {
        return array(
            'success' => false,
            'message' => $response->get_error_message()
        );
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    
    if ($response_code === 200) {
        return array('success' => true);
    } else {
        return array(
            'success' => false,
            'message' => 'Invalid credentials or site mismatch'
        );
    }
}

/**
 * Get comprehensive connection status
 */
function almaseo_get_comprehensive_connection_status() {
    $status = array(
        'plugin_connected' => false,
        'dashboard_connected' => false,
        'site_id' => get_option('almaseo_dashboard_site_id', ''),
        'connection_type' => get_option('almaseo_connection_type', 'unknown'),
        'username' => get_option('almaseo_connected_user', ''),
        'connected_date' => get_option('almaseo_connected_date', ''),
        'dashboard_synced' => get_option('almaseo_dashboard_synced', false),
        'app_password_exists' => false
    );
    
    // Check plugin connection
    $basic_status = almaseo_get_connection_status();
    $status['plugin_connected'] = $basic_status['connected'];
    
    // Check for app password
    $app_password = get_option('almaseo_app_password', '');
    $status['app_password_exists'] = !empty($app_password);
    
    // Check dashboard connection
    if ($status['site_id']) {
        $status['dashboard_connected'] = true;
    }
    
    // Detect existing passwords
    $existing_passwords = almaseo_auto_detect_app_passwords();
    if ($existing_passwords) {
        $status['detected_passwords'] = count($existing_passwords);
        $status['detected_users'] = array_unique(array_column($existing_passwords, 'username'));
    }
    
    return $status;
}

// --- REDIRECT TO SETTINGS PAGE AFTER ACTIVATION ---
register_activation_hook(__FILE__, 'almaseo_set_activation_redirect');
function almaseo_set_activation_redirect() {
    add_option('almaseo_do_activation_redirect', true);
    // Set transient for welcome screen
    set_transient('almaseo_show_welcome_screen', true, 30);
    
    // Check for existing dashboard connection on activation
    almaseo_sync_from_dashboard();
    
    // Attempt automatic connection on activation
    $current_user_id = get_current_user_id();
    if ($current_user_id) {
        $user = get_user_by('ID', $current_user_id);
        if ($user && user_can($user, 'manage_options')) {
            $existing_password = get_option('almaseo_app_password', '');
            
            // Only generate if no password exists
            if (!$existing_password && function_exists('wp_is_application_passwords_available') && function_exists('wp_generate_application_password')) {
                if (wp_is_application_passwords_available()) {
                    $label = 'AlmaSEO Auto-Connect ' . date('Y-m-d');
                    $new_password = wp_generate_application_password($user->ID, array(
                        'name' => $label,
                        'app_id' => 'almaseo-seo-playground'
                    ));
                    
                    if ($new_password && is_array($new_password)) {
                        update_option('almaseo_app_password', $new_password[0]);
                        update_option('almaseo_connected_user', $user->user_login);
                        update_option('almaseo_connected_date', current_time('mysql'));
                        update_option('almaseo_auto_connected', true);
                    }
                }
            }
        }
    }
}

add_action('admin_init', function() {
    if (get_option('almaseo_do_activation_redirect', false)) {
        delete_option('almaseo_do_activation_redirect');
        if (!isset($_GET['activate-multi'])) {
            // Redirect to welcome screen instead of settings
            wp_safe_redirect(admin_url('admin.php?page=almaseo-welcome'));
            exit;
        }
    }
});

// --- WELCOME NOTICE WITH GET STARTED LINK ---
add_action('admin_notices', function() {
    // Show welcome screen only on activation
    if (get_transient('almaseo_show_welcome_screen')) {
        delete_transient('almaseo_show_welcome_screen');
        ?>
        <div class="notice notice-success is-dismissible" style="padding: 20px; border-left: 4px solid #667eea;">
            <h2 style="margin-top: 0; color: #23282d;">
                🎯 Welcome to SEO Playground by AlmaSEO!
            </h2>
            
            <p style="font-size: 16px; color: #555; margin: 15px 0;">
                Your AI-powered SEO optimization toolkit is ready to use.
            </p>
            
            <div style="margin: 20px 0;">
                <h3 style="color: #23282d; margin-bottom: 10px;">✨ What's Included:</h3>
                <ul style="list-style: disc; margin-left: 25px; color: #666;">
                    <li>AI-powered title and description generation</li>
                    <li>Intelligent keyword suggestions and analysis</li>
                    <li>Real-time SEO scoring and recommendations</li>
                    <li>Content intelligence and optimization tools</li>
                    <li>Schema markup and meta health analysis</li>
                </ul>
            </div>
            
            <div style="margin: 20px 0;">
                <h3 style="color: #23282d; margin-bottom: 10px;">🚀 Quick Start:</h3>
                <ol style="margin-left: 25px; color: #666;">
                    <li>Connect to AlmaSEO to enable AI features</li>
                    <li>Edit any post or page</li>
                    <li>Find the "AlmaSEO SEO Playground" meta box</li>
                    <li>Use AI to generate optimized content</li>
                </ol>
            </div>
            
            <div style="margin-top: 20px;">
                <a href="<?php echo admin_url('admin.php?page=seo-playground-connection'); ?>" class="button button-primary button-hero">
                    Connect to AlmaSEO
                </a>
                <a href="<?php echo admin_url('post-new.php'); ?>" class="button button-secondary button-hero" style="margin-left: 10px;">
                    Create New Post
                </a>
            </div>
        </div>
        <?php
    } elseif (!get_user_meta(get_current_user_id(), 'almaseo_connector_dismissed_notice', true)) {
        // Show simple connection reminder if not connected
        $is_connected = get_option('almaseo_app_password') ? true : false;
        if (!$is_connected) {
            $settings_url = admin_url('admin.php?page=seo-playground-connection');
            echo '<div class="notice notice-info is-dismissible almaseo-welcome-notice" style="border-left:4px solid #667eea;padding:12px;">'
                .'<strong>AlmaSEO SEO Playground:</strong> '
                .'<a href="' . esc_url($settings_url) . '" style="color:#667eea;font-weight:bold;">Connect to AlmaSEO</a> to unlock AI-powered SEO features.'
                .'</div>';
        }
    }
});

add_action('admin_enqueue_scripts', function() {
    // Dismiss notice via AJAX
    echo '<script>jQuery(document).on("click", ".almaseo-welcome-notice .notice-dismiss", function(){
        jQuery.post(ajaxurl, {action: "almaseo_dismiss_notice"});
    });</script>';
});
add_action('wp_ajax_almaseo_dismiss_notice', function() {
    update_user_meta(get_current_user_id(), 'almaseo_connector_dismissed_notice', 1);
    wp_die();
});

// --- HELPER FUNCTIONS FOR CONNECTION STATUS & DISCONNECT ---
function almaseo_get_connection_status() {
    $connected_user = get_option('almaseo_connected_user', '');
    $connected_date = get_option('almaseo_connected_date', '');
    
    // Check if synced from dashboard
    if (get_option('almaseo_dashboard_synced')) {
        $app_password = get_option('almaseo_app_password', '');
        if ($app_password || $connected_user) {
            return array(
                'connected' => true,
                'connected_user' => $connected_user,
                'connected_date' => $connected_date ? date('M j, Y', strtotime($connected_date)) : 'Recently',
                'site_url' => get_site_url(),
                'connection_type' => get_option('almaseo_connection_type', 'dashboard_initiated')
            );
        }
    }
    
    // If no stored connection data, check if any AlmaSEO app passwords exist
    if (!$connected_user && class_exists('WP_Application_Passwords')) {
        // Check all users for AlmaSEO application passwords
        $users = get_users(array('role' => 'administrator'));
        foreach ($users as $user) {
            $app_passwords = WP_Application_Passwords::get_user_application_passwords($user->ID);
            foreach ($app_passwords as $app_password) {
                if (strpos($app_password['name'] ?? '', 'AlmaSEO AI') === 0) {
                    // Found a AlmaSEO password, update our records
                    update_option('almaseo_connected_user', $user->user_login);
                    update_option('almaseo_connected_date', date('Y-m-d H:i:s', $app_password['created']));
                    $connected_user = $user->user_login;
                    $connected_date = date('Y-m-d H:i:s', $app_password['created']);
                    break 2; // Break both loops
                }
            }
        }
    }
    
    // Check if we have any AlmaSEO application passwords
    if ($connected_user) {
        $user = get_user_by('login', $connected_user);
        if ($user) {
            $has_almaseo_password = false;
            
            if (class_exists('WP_Application_Passwords')) {
                $app_passwords = WP_Application_Passwords::get_user_application_passwords($user->ID);
                foreach ($app_passwords as $app_password) {
                    if (strpos($app_password['name'] ?? '', 'AlmaSEO AI') === 0) {
                        $has_almaseo_password = true;
                        break;
                    }
                }
            } else {
                // For older WordPress versions, assume connected if we have stored credentials
                $has_almaseo_password = true;
            }
            
            if ($has_almaseo_password) {
                return array(
                    'connected' => true,
                    'connected_user' => $connected_user,
                    'connected_date' => $connected_date ? date('M j, Y', strtotime($connected_date)) : 'Recently',
                    'site_url' => get_site_url()
                );
            }
        }
    }
    
    return array(
        'connected' => false,
        'connected_user' => null,
        'connected_date' => null,
        'site_url' => get_site_url()
    );
}

// Helper function to clean up old AlmaSEO passwords
function almaseo_cleanup_old_passwords($user_id) {
    if (!class_exists('WP_Application_Passwords')) {
        return;
    }
    
    $app_passwords = WP_Application_Passwords::get_user_application_passwords($user_id);
    if ($app_passwords) {
        foreach ($app_passwords as $app_password) {
            // Remove any password with AlmaSEO in the name
            if (stripos($app_password['name'], 'AlmaSEO') !== false) {
                WP_Application_Passwords::delete_application_password($user_id, $app_password['uuid']);
            }
        }
    }
}

function almaseo_disconnect_site() {
    $connected_user = get_option('almaseo_connected_user', '');
    
    if ($connected_user) {
        $user = get_user_by('login', $connected_user);
        if ($user) {
            // Delete all AlmaSEO application passwords
            almaseo_cleanup_old_passwords($user->ID);
        }
    }
    
    // Clear connection data
    delete_option('almaseo_connected_user');
    delete_option('almaseo_connected_date');
    delete_option('almaseo_app_password');
}

// AJAX handler to get connection status
add_action('wp_ajax_almaseo_get_status', function() {
    if (!check_ajax_referer('almaseo_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }
    
    $status = almaseo_get_connection_status();
    wp_send_json_success($status);
});

// AJAX handler for checking dashboard registration
add_action('wp_ajax_almaseo_check_dashboard', function() {
    // Check nonce for security
    if (!check_ajax_referer('almaseo_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }
    
    $dashboard_status = almaseo_check_dashboard_registration();
    
    if ($dashboard_status['registered']) {
        wp_send_json_success(array(
            'registered' => true,
            'site_id' => $dashboard_status['site_id'],
            'connected' => $dashboard_status['connected'] ?? false,
            'application_password_exists' => $dashboard_status['application_password_exists'] ?? false
        ));
    } else {
        if (isset($dashboard_status['error'])) {
            wp_send_json_error(array('message' => $dashboard_status['error']));
        } else {
            wp_send_json_success(array('registered' => false));
        }
    }
});

add_action('wp_ajax_almaseo_test_connection', function() {
    // Check nonce for security
    if (!check_ajax_referer('almaseo_nonce', 'nonce', false)) {
        wp_send_json_error(array('message' => 'Security check failed'));
        return;
    }
    
    $status = almaseo_get_connection_status();
    
    if (!$status['connected']) {
        wp_send_json_error(array('message' => 'Site is not connected to AlmaSEO'));
        return;
    }
    
    // Get stored credentials
    $app_password = get_option('almaseo_app_password', '');
    $username = get_option('almaseo_connected_user', '');
    
    if (!$app_password || !$username) {
        wp_send_json_error(array('message' => 'Connection credentials not found'));
        return;
    }
    
    // Test connection with AlmaSEO API ping endpoint
    $api_url = 'https://api.almaseo.com/api/v1/ping';
    
    $response = wp_remote_post($api_url, array(
        'timeout' => 15,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($username . ':' . $app_password)
        ),
        'body' => json_encode(array(
            'site_url' => get_site_url(),
            'plugin_version' => ALMASEO_PLUGIN_VERSION
        ))
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array(
            'message' => 'Connection test failed: ' . $response->get_error_message()
        ));
        return;
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code === 200) {
        $data = json_decode($response_body, true);
        wp_send_json_success(array(
            'message' => 'Connection successful!',
            'details' => $data
        ));
    } else {
        wp_send_json_error(array(
            'message' => 'Connection test failed',
            'code' => $response_code,
            'response' => $response_body
        ));
    }
});

// AUTO-CONNECT function for onboarding
function almaseo_auto_connect($request) {
    // Handle OPTIONS preflight request
    if ($request->get_method() === 'OPTIONS') {
        return new WP_REST_Response(null, 200);
    }
    
    $app_password = get_option('almaseo_app_password', '');
    $username = get_option('almaseo_connected_user', ''); // Fetch the stored username

    if (!$app_password || !$username) {
        return new WP_Error('no_app_password', 'No Application Password or username has been set in the plugin settings.', array('status' => 400));
    }

    $response = array(
        'success' => true,
        'username' => $username,
        'application_password' => $app_password,
    );

    return rest_ensure_response($response);
}

// Register settings for manual app password
// NOTE: We are using a manually generated Application Password stored in the plugin settings.
// This approach is required for hosting environments like GoDaddy Managed WordPress that disable application password auto-generation.
function almaseo_connector_register_settings() {
    register_setting('almaseo_settings_group', 'almaseo_app_password');
    register_setting('almaseo_settings_group', 'almaseo_exclusive_schema_mode');
}
add_action('admin_init', 'almaseo_connector_register_settings');

// ========================================
// TIER DETECTION AND MANAGEMENT
// ========================================

/**
 * Fetch user tier and limits from AlmaSEO dashboard
 * @return array User tier information
 */
function almaseo_fetch_user_tier() {
    // Check if connected first
    if (!seo_playground_is_alma_connected()) {
        return array(
            'tier' => 'unconnected',
            'limits' => array(),
            'error' => 'Not connected to AlmaSEO'
        );
    }
    
    // Get stored credentials
    $app_password = get_option('almaseo_app_password', '');
    $username = get_option('almaseo_connected_user', '');
    $site_url = get_site_url();
    
    if (!$app_password || !$username) {
        return array(
            'tier' => 'unconnected',
            'limits' => array(),
            'error' => 'Missing credentials'
        );
    }
    
    // Check if we have cached tier data (valid for 1 hour)
    $cached_tier = get_transient('almaseo_user_tier_data');
    if ($cached_tier !== false) {
        return $cached_tier;
    }
    
    // Fetch tier information from AlmaSEO API
    $api_url = 'https://api.almaseo.com/api/plugin/connection-status';
    
    $response = wp_remote_post($api_url, array(
        'timeout' => 15,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode($username . ':' . $app_password)
        ),
        'body' => json_encode(array(
            'site_url' => $site_url,
            'plugin_version' => ALMASEO_PLUGIN_VERSION
        ))
    ));
    
    if (is_wp_error($response)) {
        // Log error and return default tier
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[AlmaSEO] Tier fetch failed: ' . $response->get_error_message());
        }
        return array(
            'tier' => 'free',
            'limits' => array(
                'monthly_articles' => 0,
                'ai_generations' => 0
            ),
            'error' => $response->get_error_message()
        );
    }
    
    $response_code = wp_remote_retrieve_response_code($response);
    $response_body = wp_remote_retrieve_body($response);
    
    if ($response_code === 200) {
        $data = json_decode($response_body, true);
        
        // Structure the tier data
        $tier_data = array(
            'tier' => isset($data['tier']) ? strtolower($data['tier']) : 'free',
            'limits' => array(
                'monthly_articles' => isset($data['limits']['monthly_articles']) ? intval($data['limits']['monthly_articles']) : 0,
                'ai_generations' => isset($data['limits']['ai_generations']) ? intval($data['limits']['ai_generations']) : 0,
                'remaining_articles' => isset($data['remaining']['articles']) ? intval($data['remaining']['articles']) : 0,
                'remaining_generations' => isset($data['remaining']['generations']) ? intval($data['remaining']['generations']) : 0,
            ),
            'usage' => array(
                'articles_used' => isset($data['usage']['articles']) ? intval($data['usage']['articles']) : 0,
                'generations_used' => isset($data['usage']['generations']) ? intval($data['usage']['generations']) : 0,
            ),
            'reset_date' => isset($data['reset_date']) ? $data['reset_date'] : '',
            'fetched_at' => current_time('timestamp')
        );
        
        // Cache the tier data for 1 hour
        set_transient('almaseo_user_tier_data', $tier_data, HOUR_IN_SECONDS);
        
        // Also store in options for persistent access
        update_option('almaseo_user_tier', $tier_data['tier']);
        update_option('almaseo_tier_limits', $tier_data['limits']);
        update_option('almaseo_tier_usage', $tier_data['usage']);
        
        return $tier_data;
    } else {
        // API returned error, use default tier
        return array(
            'tier' => 'free',
            'limits' => array(
                'monthly_articles' => 0,
                'ai_generations' => 0
            ),
            'error' => 'API returned status code: ' . $response_code
        );
    }
}

/**
 * Get current user tier (with caching)
 * @return string User tier: 'unconnected', 'free', 'pro', 'max'
 */
function almaseo_get_user_tier() {
    // Quick check from options first
    $stored_tier = get_option('almaseo_user_tier', false);
    
    if ($stored_tier === false || empty($stored_tier)) {
        // Fetch fresh tier data
        $tier_data = almaseo_fetch_user_tier();
        return $tier_data['tier'];
    }
    
    return $stored_tier;
}

/**
 * Check if user has access to AI features
 * @return bool
 */
function almaseo_can_use_ai_features() {
    $tier = almaseo_get_user_tier();
    return in_array($tier, array('pro', 'max'));
}

/**
 * Get remaining AI generations for current month
 * @return array
 */
function almaseo_get_remaining_generations() {
    $tier_data = almaseo_fetch_user_tier();
    
    if ($tier_data['tier'] === 'max') {
        return array(
            'remaining' => 'unlimited',
            'total' => 'unlimited',
            'used' => $tier_data['usage']['generations_used'] ?? 0
        );
    }
    
    return array(
        'remaining' => $tier_data['limits']['remaining_generations'] ?? 0,
        'total' => $tier_data['limits']['ai_generations'] ?? 0,
        'used' => $tier_data['usage']['generations_used'] ?? 0
    );
}

/**
 * Track AI generation usage
 * @param string $type Type of generation (title, description, rewrite)
 * @return bool
 */
function almaseo_track_ai_usage($type) {
    // Get current usage
    $usage = get_option('almaseo_tier_usage', array(
        'generations_used' => 0,
        'articles_used' => 0
    ));
    
    // Increment generation count
    $usage['generations_used'] = ($usage['generations_used'] ?? 0) + 1;
    
    // If it's a full article rewrite, increment article count too
    if ($type === 'article_rewrite') {
        $usage['articles_used'] = ($usage['articles_used'] ?? 0) + 1;
    }
    
    // Save updated usage
    update_option('almaseo_tier_usage', $usage);
    
    // Also track in user meta for reporting
    $current_user_id = get_current_user_id();
    if ($current_user_id) {
        $user_usage = get_user_meta($current_user_id, 'almaseo_ai_usage_' . date('Y_m'), true);
        if (!is_array($user_usage)) {
            $user_usage = array();
        }
        
        $user_usage[$type] = ($user_usage[$type] ?? 0) + 1;
        $user_usage['total'] = ($user_usage['total'] ?? 0) + 1;
        $user_usage['last_used'] = current_time('timestamp');
        
        update_user_meta($current_user_id, 'almaseo_ai_usage_' . date('Y_m'), $user_usage);
    }
    
    // Clear tier cache to force refresh on next check
    delete_transient('almaseo_user_tier_data');
    
    return true;
}

/**
 * Refresh tier data on connection
 */
add_action('almaseo_connection_established', function() {
    // Clear any cached tier data
    delete_transient('almaseo_user_tier_data');
    
    // Fetch fresh tier information
    almaseo_fetch_user_tier();
});

// ========================================
// SEO PLAYGROUND FUNCTIONALITY
// ========================================

// Reusable function to check if AlmaSEO is connected
function seo_playground_is_alma_connected() {
    $connection_status = almaseo_get_connection_status();
    return $connection_status['connected'];
}

// Add SEO Playground meta box
function almaseo_add_seo_playground_meta_box() {
    $post_types = array('post', 'page');
    
    foreach ($post_types as $post_type) {
        // Defensive: remove any legacy box registered earlier with same ID
        remove_meta_box('almaseo_seo_playground', $post_type, 'normal');
        remove_meta_box('almaseo_seo_playground', $post_type, 'side');
        remove_meta_box('almaseo_seo_playground', $post_type, 'advanced');
        
        // Add our meta box to main column only
        add_meta_box(
            'almaseo_seo_playground',
            __('SEO Playground by AlmaSEO', 'almaseo'), // Single emoji in title, no duplicate
            'almaseo_seo_playground_meta_box_callback',
            $post_type,
            'normal', // Main column, not 'side'
            'high'
        );
    }
}
add_action('add_meta_boxes', 'almaseo_add_seo_playground_meta_box', 20); // Priority 20 to run after defaults

// Enqueue SEO Playground styles and scripts
function almaseo_enqueue_seo_playground_styles() {
    $screen = get_current_screen();
    
    // Enqueue admin help CSS on AlmaSEO screens
    if (function_exists('almaseo_is_admin_screen') && almaseo_is_admin_screen()) {
        wp_enqueue_style(
            'almaseo-admin-help',
            plugin_dir_url(__FILE__) . 'assets/css/admin-help.css',
            array(),
            ALMASEO_PLUGIN_VERSION
        );
    }
    
    // Enqueue admin connection scripts and styles on settings page
    if ($screen && $screen->id === 'settings_page_almaseo-connector') {
        // Enqueue CSS
        wp_enqueue_style(
            'almaseo-admin-connection',
            plugin_dir_url(__FILE__) . 'assets/css/admin-connection.css',
            array(),
            ALMASEO_PLUGIN_VERSION
        );
        
        // Enqueue JavaScript
        wp_enqueue_script(
            'almaseo-admin-connection',
            plugin_dir_url(__FILE__) . 'assets/js/admin-connection.js',
            array('jquery'),
            ALMASEO_PLUGIN_VERSION,
            true
        );
        
        wp_localize_script('almaseo-admin-connection', 'almaseoAdmin', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('almaseo_nonce')
        ));
    }
    
    // Include security functions
    require_once plugin_dir_path(__FILE__) . 'includes/security.php';
    
    // Enqueue SEO Playground scripts on post/page edit screens
    if ($screen && in_array($screen->post_type, array('post', 'page'))) {
        // Check user capability
        if (!current_user_can('edit_posts')) {
            return;
        }
        
        // Determine if we should use minified files (check if they exist first)
        $suffix = '';
        $use_combined = false;
        
        // Check if minified combined file exists
        $combined_css = plugin_dir_path(__FILE__) . 'assets/css/seo-playground-all.min.css';
        $combined_js = plugin_dir_path(__FILE__) . 'assets/js/seo-playground-all.min.js';
        
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            // Production mode - try to use minified if available
            if (file_exists($combined_css) && file_exists($combined_js)) {
                $use_combined = true;
            }
        }
        
        if ($use_combined) {
            // Production: Use combined minified files
            wp_enqueue_style(
                'almaseo-seo-playground-all',
                plugin_dir_url(__FILE__) . 'assets/css/seo-playground-all.min.css',
                array(),
                ALMASEO_PLUGIN_VERSION
            );
            
            wp_enqueue_script(
                'almaseo-seo-playground-all',
                plugin_dir_url(__FILE__) . 'assets/js/seo-playground-all.min.js',
                array('jquery'),
                ALMASEO_PLUGIN_VERSION,
                true
            );
        } else {
            // Development: Use individual files for easier debugging
            // Consolidated styles first
            wp_enqueue_style(
                'almaseo-seo-playground-consolidated',
                plugin_dir_url(__FILE__) . 'assets/css/seo-playground-consolidated.css',
                array(),
                ALMASEO_PLUGIN_VERSION
            );
            
            // Main SEO Playground styles
            wp_enqueue_style(
                'almaseo-seo-playground',
                plugin_dir_url(__FILE__) . 'assets/css/seo-playground.css',
                array('almaseo-seo-playground-consolidated'),
                ALMASEO_PLUGIN_VERSION
            );
            
            // Tab-specific styles (only load what's needed)
            $tab_styles = array(
                'health', // Health and SERP preview styles
                'unified-tabs', // Unified tab styles with emojis
                'unified-health', // Unified health score styles
                'seo-playground-tabs',
                'seo-overview-polish',
                'seo-overview-improved', // New improved SEO Overview CSS
                'search-console-polish',
                'search-console-placeholder', // Search Console placeholder CSS
                'schema-meta-tab', // Schema & Meta tab CSS
                'ai-tools-polish',
                'notes-history-polish',
                'new-features', // New features CSS
                'unlock-features', // Unlock Features tab CSS
                'unlock-features-updated', // Updated Unlock Features CSS
                'tier-system' // Tier system CSS
            );
            
            foreach ($tab_styles as $style) {
                wp_enqueue_style(
                    'almaseo-' . $style,
                    plugin_dir_url(__FILE__) . 'assets/css/' . $style . '.css',
                    array('almaseo-seo-playground'),
                    ALMASEO_PLUGIN_VERSION
                );
            }
            
            // Consolidated JavaScript first
            wp_enqueue_script(
                'almaseo-seo-playground-consolidated',
                plugin_dir_url(__FILE__) . 'assets/js/seo-playground-consolidated.js',
                array('jquery'),
                ALMASEO_PLUGIN_VERSION,
                true
            );
            
            // Main JavaScript
            wp_enqueue_script(
                'almaseo-seo-playground',
                plugin_dir_url(__FILE__) . 'assets/js/seo-playground.js',
                array('jquery', 'almaseo-seo-playground-consolidated'),
                ALMASEO_PLUGIN_VERSION,
                true
            );
            
            // Tab-specific scripts
            $tab_scripts = array(
                'seo-playground-tabs',
                'unified-health', // Unified health score JavaScript
                'seo-overview-polish',
                'seo-overview-improved', // New improved SEO Overview JS
                'search-console-polish',
                'schema-meta-tab', // Schema & Meta tab JavaScript
                'ai-tools-polish',
                'notes-history-polish',
                'new-features', // New features JavaScript
                'unlock-features', // Unlock Features tab JavaScript
                'tier-management' // Tier management JavaScript
            );
            
            foreach ($tab_scripts as $script) {
                wp_enqueue_script(
                    'almaseo-' . $script,
                    plugin_dir_url(__FILE__) . 'assets/js/' . $script . '.js',
                    array('jquery', 'almaseo-seo-playground'),
                    ALMASEO_PLUGIN_VERSION,
                    true
                );
            }
        }
        
        // Localize script with connection status and other data
        wp_localize_script('almaseo-seo-playground', 'seoPlaygroundData', array(
            'almaConnected' => seo_playground_is_alma_connected(),
            'connectionUrl' => admin_url('admin.php?page=seo-playground-connection'),
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('seo_playground_nonce'),
            'apiKey' => get_option('almaseo_app_password', ''), // API key for AlmaSEO requests
            'siteUrl' => get_site_url(),
            'postId' => get_the_ID() ?: 0,
            'strings' => array(
                'unlockMessage' => '🔒 Unlock AI suggestions and automated insights by connecting to your AlmaSEO account.',
                'connectButton' => 'Connect to AlmaSEO',
                'connectedMessage' => '✅ Connected to AlmaSEO - AI features are available!'
            )
        ));
    }
}
add_action('admin_enqueue_scripts', 'almaseo_enqueue_seo_playground_styles');

// SEO Playground meta box callback
function almaseo_seo_playground_meta_box_callback($post) {
    // Add nonce for security
    wp_nonce_field('almaseo_seo_playground_nonce', 'almaseo_seo_playground_nonce');
    
    // Get existing values - check new canonical keys first, then legacy
    $seo_title = get_post_meta($post->ID, '_almaseo_title', true);
    if (empty($seo_title)) {
        $seo_title = get_post_meta($post->ID, '_seo_playground_title', true) ?: '';
    }
    
    $seo_description = get_post_meta($post->ID, '_almaseo_description', true);
    if (empty($seo_description)) {
        $seo_description = get_post_meta($post->ID, '_seo_playground_description', true) ?: '';
    }
    
    $seo_focus_keyword = get_post_meta($post->ID, '_almaseo_focus_keyword', true);
    if (empty($seo_focus_keyword)) {
        $seo_focus_keyword = get_post_meta($post->ID, '_seo_playground_focus_keyword', true) ?: '';
    }
    $seo_notes = get_post_meta($post->ID, '_seo_playground_notes', true) ?: '';
    $seo_schema_type = get_post_meta($post->ID, '_seo_playground_schema_type', true) ?: '';
    
    // Check connection status and tier
    $is_connected = seo_playground_is_alma_connected();
    $user_tier = almaseo_get_user_tier();
    $can_use_ai = almaseo_can_use_ai_features();
    $generations_info = almaseo_get_remaining_generations();
    
    // Get motivational quote
    $quotes = array(
        "Great content is the foundation of great SEO.",
        "Every word counts in the digital landscape.",
        "Optimize for users, not just search engines.",
        "Quality content creates lasting impact.",
        "SEO is a marathon, not a sprint."
    );
    $current_quote = $quotes[array_rand($quotes)];
    
    ?>
    <div class="almaseo-seo-playground">
        <div class="almaseo-seo-header">
            <div class="almaseo-quote">
                <em>"<?php echo esc_html($current_quote); ?>"</em>
            </div>
        </div>
        
        <!-- Tab Navigation Bar -->
        <div class="almaseo-tab-navigation" id="almaseo-tab-navigation">
            <div class="almaseo-tab-scroll-wrapper">
                <button class="almaseo-tab-btn active" data-tab="seo-overview">
                    <span class="tab-icon">🩺</span>
                    <span class="tab-label">SEO Health</span>
                </button>
                <button class="almaseo-tab-btn" data-tab="search-console">
                    <span class="tab-icon">📈</span>
                    <span class="tab-label">Search Console</span>
                </button>
                <button class="almaseo-tab-btn" data-tab="schema-meta">
                    <span class="tab-icon">🧩</span>
                    <span class="tab-label">Schema & Meta</span>
                </button>
                <button class="almaseo-tab-btn" data-tab="ai-tools">
                    <span class="tab-icon">🤖</span>
                    <span class="tab-label">AI Tools</span>
                </button>
                <button class="almaseo-tab-btn" data-tab="notes-history">
                    <span class="tab-icon">🗒️</span>
                    <span class="tab-label">Notes & History</span>
                </button>
                <?php if (!$is_connected): ?>
                <button class="almaseo-tab-btn almaseo-unlock-tab" data-tab="unlock-features">
                    <span class="tab-icon">🔒</span>
                    <span class="tab-label">Unlock AI Features</span>
                    <span class="tab-badge">NEW</span>
                </button>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Re-Optimization Alert Section -->
        <?php if ($is_connected): ?>
        <div class="almaseo-reoptimize-section" id="almaseo-reoptimize-section" style="display: none;">
            <div class="almaseo-field-group">
                <div class="reoptimize-alert" id="reoptimize-alert">
                    <div class="reoptimize-loading" id="reoptimize-loading">
                        <div class="reoptimize-loading-spinner"></div>
                        <div class="reoptimize-loading-text">Analyzing post for re-optimization opportunities...</div>
                    </div>
                    
                    <div class="reoptimize-content" id="reoptimize-content" style="display: none;">
                        <div class="reoptimize-header">
                            <div class="reoptimize-icon">📉</div>
                            <div class="reoptimize-title">This post may benefit from re-optimization</div>
                        </div>
                        <div class="reoptimize-reason" id="reoptimize-reason">
                            <!-- Reason will be populated by JavaScript -->
                        </div>
                        <div class="reoptimize-suggestions" id="reoptimize-suggestions">
                            <!-- Suggestions will be populated by JavaScript -->
                        </div>
                        <div class="reoptimize-actions">
                            <button type="button" class="reoptimize-btn" id="start-reoptimization" disabled>
                                Start AI Re-Optimization
                            </button>
                            <span class="reoptimize-note">(Coming soon)</span>
                        </div>
                    </div>
                    
                    <div class="reoptimize-error" id="reoptimize-error" style="display: none;">
                        <div class="error-icon">⚠️</div>
                        <div class="error-text">Could not check re-optimization status</div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Tab Content Areas -->
        <div class="almaseo-tab-content" id="almaseo-tab-content">
        
        <!-- SEO Health Tab (Unified) -->
        <div class="almaseo-tab-panel active" id="tab-seo-overview">
        
        <?php
        // Check if search engines are discouraged and show warning
        if (get_option('blog_public') == '0') {
            ?>
            <div class="almaseo-search-engine-tab-warning" style="background: linear-gradient(135deg, #dc3232, #c92c2c); color: white; padding: 15px; margin: -20px -20px 20px -20px; border-radius: 4px 4px 0 0; display: flex; align-items: center; box-shadow: 0 2px 8px rgba(220,50,50,0.3);">
                <span class="dashicons dashicons-warning" style="font-size: 24px; margin-right: 12px;"></span>
                <div style="flex: 1;">
                    <strong style="font-size: 15px; display: block; margin-bottom: 3px;">🚨 CRITICAL: Your Site is Hidden from Search Engines!</strong>
                    <span style="font-size: 13px; opacity: 0.95;">WordPress "Discourage search engines" is enabled. Your content will NOT appear in Google! This affects ALL posts and pages.</span>
                </div>
                <a href="<?php echo admin_url('options-reading.php'); ?>" class="button" style="background: white; color: #dc3232; border: none; font-weight: bold;">Fix Immediately →</a>
            </div>
            <?php
        }
        
        // Get health score data
        $health_score = get_post_meta($post->ID, '_almaseo_health_score', true);
        $health_breakdown_json = get_post_meta($post->ID, '_almaseo_health_breakdown', true);
        $health_updated = get_post_meta($post->ID, '_almaseo_health_updated_at', true);
        
        // If no score exists, calculate it
        if ($health_score === '' || $health_breakdown_json === '') {
            if (function_exists('almaseo_health_calculate')) {
                $result = almaseo_health_calculate($post->ID);
                $health_score = $result['score'];
                $health_breakdown = $result['breakdown'];
                
                // Save for next time
                update_post_meta($post->ID, '_almaseo_health_score', $health_score);
                update_post_meta($post->ID, '_almaseo_health_breakdown', json_encode($health_breakdown));
                update_post_meta($post->ID, '_almaseo_health_updated_at', current_time('timestamp'));
            } else {
                $health_score = 0;
                $health_breakdown = array();
            }
        } else {
            $health_breakdown = json_decode($health_breakdown_json, true);
        }
        
        // Count passes/fails
        $pass_count = 0;
        $fail_count = 0;
        foreach ($health_breakdown as $signal => $result) {
            if ($result['pass']) {
                $pass_count++;
            } else {
                $fail_count++;
            }
        }
        
        // Determine score color
        $score_class = 'poor';
        $color_hex = '#d63638';
        if ($health_score >= 80) {
            $score_class = 'excellent';
            $color_hex = '#00a32a';
        } elseif ($health_score >= 50) {
            $score_class = 'good';
            $color_hex = '#dba617';
        }
        ?>
        
        <!-- Top Section: Health Summary -->
        <div class="almaseo-health-summary">
            <div class="health-summary-container">
                <!-- Radial Gauge -->
                <div class="health-gauge-wrapper">
                    <canvas id="almaseo-health-gauge" width="200" height="200"></canvas>
                    <div class="health-score-text <?php echo esc_attr($score_class); ?>">
                        <span class="score-number"><?php echo esc_html($health_score); ?></span>
                        <span class="score-label">SEO Score</span>
                    </div>
                </div>
                
                <!-- Stats & Status -->
                <div class="health-info">
                    <h3><?php _e('Overall SEO Health', 'almaseo'); ?></h3>
                    <p class="health-status health-<?php echo esc_attr($score_class); ?>">
                        <?php
                        if ($health_score >= 80) {
                            _e('Excellent! Your content is well-optimized.', 'almaseo');
                        } elseif ($health_score >= 50) {
                            _e('Good, but there\'s room for improvement.', 'almaseo');
                        } else {
                            _e('Needs attention. Follow the suggestions below.', 'almaseo');
                        }
                        ?>
                    </p>
                    <div class="health-stats">
                        <span class="health-stat">
                            <span class="stat-icon">✅</span>
                            <strong><?php echo $pass_count; ?></strong> Passed
                        </span>
                        <span class="health-stat">
                            <span class="stat-icon">❌</span>
                            <strong><?php echo $fail_count; ?></strong> Issues
                        </span>
                    </div>
                    <button type="button" class="button button-primary" id="almaseo-health-recalculate" data-post-id="<?php echo esc_attr($post->ID); ?>">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Recalculate', 'almaseo'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Middle Section: Signal Breakdown -->
        <div class="almaseo-health-signals">
            <h4><?php _e('SEO Signal Analysis', 'almaseo'); ?></h4>
            
            <?php
            // Show critical warning if search engines are blocked
            if (get_option('blog_public') == '0') {
                ?>
                <div class="almaseo-signal-critical-warning" style="background: #dc3232; color: white; padding: 12px; margin-bottom: 15px; border-radius: 4px; display: flex; align-items: center;">
                    <span class="dashicons dashicons-warning" style="font-size: 20px; margin-right: 10px;"></span>
                    <div style="flex: 1;">
                        <strong>⚠️ Critical Issue Detected!</strong>
                        <span style="margin-left: 8px; opacity: 0.9; font-size: 13px;">
                            Search engines are blocked. This overrides all other SEO efforts!
                        </span>
                    </div>
                </div>
                <?php
            }
            
            // Debug: Check if breakdown is empty
            if (empty($health_breakdown)) {
                echo '<p style="color: orange; font-style: italic;">Note: Recalculating health signals...</p>';
                // Force recalculation
                if (function_exists('almaseo_health_calculate')) {
                    $result = almaseo_health_calculate($post->ID);
                    $health_breakdown = $result['breakdown'];
                    $health_score = $result['score'];
                    update_post_meta($post->ID, '_almaseo_health_score', $health_score);
                    update_post_meta($post->ID, '_almaseo_health_breakdown', json_encode($health_breakdown));
                    update_post_meta($post->ID, '_almaseo_health_updated_at', current_time('timestamp'));
                }
            }
            
            // Signal labels and helpers
            $signal_labels = array(
                'title' => 'Meta Title',
                'meta_desc' => 'Meta Description',
                'h1' => 'H1 Heading',
                'kw_intro' => 'Keyword in First 100 Words',
                'internal_link' => 'Internal Links',
                'outbound_link' => 'Outbound Links',
                'image_alt' => 'Image Alt Text',
                'readability' => 'Readability',
                'canonical' => 'Canonical URL',
                'robots' => 'Robots Meta'
            );
            
            // Brief descriptions for each signal
            $signal_descriptions = array(
                'title' => 'The clickable headline shown in search results',
                'meta_desc' => 'The summary text displayed under your title in search results',
                'h1' => 'The main heading that tells visitors and search engines what the page is about',
                'kw_intro' => 'Having your target keyword early shows relevance to search engines',
                'internal_link' => 'Links to other pages on your site to help navigation and SEO',
                'outbound_link' => 'Links to external sites that provide value and credibility',
                'image_alt' => 'Text descriptions for images to improve accessibility and SEO',
                'readability' => 'How easy your content is to read and understand',
                'canonical' => 'Tells search engines the preferred URL for this content',
                'robots' => 'Controls whether search engines can index and follow this page'
            );
            
            $signal_fields = array(
                'title' => 'almaseo_seo_title',
                'meta_desc' => 'almaseo_seo_description',
                'kw_intro' => 'almaseo_focus_keyword',
                'canonical' => 'almaseo_canonical_url'
            );
            
            foreach ($health_breakdown as $signal => $result):
                $label = isset($signal_labels[$signal]) ? $signal_labels[$signal] : ucfirst(str_replace('_', ' ', $signal));
                $description = isset($signal_descriptions[$signal]) ? $signal_descriptions[$signal] : '';
                $icon = $result['pass'] ? '✅' : '❌';
                $status_class = $result['pass'] ? 'pass' : 'fail';
                $field_id = isset($signal_fields[$signal]) ? $signal_fields[$signal] : '';
                
                // Highlight robots signal if search engines are blocked
                $extra_class = '';
                $extra_style = '';
                if ($signal === 'robots' && !$result['pass'] && strpos($result['note'], 'Discourage search engines') !== false) {
                    $extra_class = ' robots-critical';
                    $extra_style = ' style="border: 2px solid #dc3232; background: #fff5f5;"';
                }
            ?>
            <div class="almaseo-health-signal <?php echo esc_attr($status_class . $extra_class); ?>"<?php echo $extra_style; ?>>
                <div class="signal-header">
                    <span class="signal-icon"><?php echo $icon; ?></span>
                    <span class="signal-label">
                        <?php echo esc_html($label); ?>
                        <?php if ($description): ?>
                        <span style="color: #666; font-weight: normal; font-size: 12px; margin-left: 8px;">
                            — <?php echo esc_html($description); ?>
                        </span>
                        <?php endif; ?>
                    </span>
                    <?php if (!$result['pass'] && $field_id): ?>
                    <button type="button" class="signal-goto-btn" data-field="<?php echo esc_attr($field_id); ?>">
                        <?php _e('Go to Field', 'almaseo'); ?> ↓
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="signal-bar-wrapper">
                    <div class="signal-bar">
                        <div class="signal-bar-fill <?php echo esc_attr($status_class); ?>" 
                             style="width: <?php echo $result['pass'] ? '100' : '0'; ?>%"></div>
                    </div>
                </div>
                
                <div class="signal-note">
                    <?php echo esc_html($result['note']); ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Bottom Section: Editor Fields -->
        <div class="almaseo-seo-fields">
            <h4><?php _e('SEO Editor', 'almaseo'); ?></h4>
            <div class="almaseo-field-group">
                <label for="almaseo_seo_title">
                    SEO Title
                    <span class="label-helper">(Google recommends ≤ 60 characters. Use strong keywords naturally — <a href="https://developers.google.com/search/docs/appearance/title-link?hl=en&sjid=17527460305754617133-NA&visit_id=638911458818141308-2932973546&rd=1" target="_blank" rel="noopener">Google Guidelines</a>)</span>
                </label>
                <input type="text" 
                       id="almaseo_seo_title" 
                       name="almaseo_seo_title" 
                       value="<?php echo esc_attr($seo_title ?? ''); ?>" 
                       placeholder="<?php esc_attr_e('Enter a clear, keyword-rich title (max 60 characters)', 'almaseo'); ?>"
                       class="almaseo-input" />
                <div class="almaseo-char-count" data-field="title">
                    <span id="title-count" class="<?php echo strlen($seo_title ?? '') <= 60 ? 'good' : (strlen($seo_title ?? '') <= 70 ? 'caution' : 'too-long'); ?>"><?php echo strlen($seo_title ?? ''); ?></span>/60
                </div>
                <div class="almaseo-char-bar" data-field="title" aria-label="Title character usage">
                    <?php 
                    $title_len = strlen($seo_title ?? '');
                    $title_percent = min(100, ($title_len / 70) * 100); // Max at 70 chars
                    $bar_class = $title_len <= 60 ? 'good' : ($title_len <= 70 ? 'caution' : 'too-long');
                    ?>
                    <div class="char-bar-track">
                        <div class="char-bar-fill <?php echo $bar_class; ?>" style="width: <?php echo $title_percent; ?>%"></div>
                        <div class="char-bar-marker good-zone" style="left: 85.7%" aria-label="60 character mark"></div>
                    </div>
                </div>
                <p class="field-subtext" style="margin: 5px 0 0 0; color: #666; font-size: 12px; font-style: italic;">
                    <?php _e('Or let AlmaSEO generate optimized titles automatically → <a href="' . admin_url('admin.php?page=seo-playground-connection') . '">Upgrade</a>', 'almaseo'); ?>
                </p>
            </div>
            
            <div class="almaseo-field-group">
                <label for="almaseo_seo_description">
                    Meta Description
                    <span class="label-helper">(Aim for 150–160 characters. Write for humans first; clarity beats keyword stuffing.)</span>
                </label>
                <textarea id="almaseo_seo_description" 
                          name="almaseo_seo_description" 
                          placeholder="<?php esc_attr_e('Write a compelling, keyword-focused description (150–160 characters)', 'almaseo'); ?>"
                          class="almaseo-textarea"><?php echo esc_textarea($seo_description ?? ''); ?></textarea>
                <div class="almaseo-char-count" data-field="description">
                    <?php 
                    $desc_len = strlen($seo_description ?? '');
                    $desc_class = ($desc_len >= 150 && $desc_len <= 160) ? 'good' : 
                                  (($desc_len >= 120 && $desc_len < 150) || ($desc_len > 160 && $desc_len <= 180) ? 'caution' : 'too-long');
                    ?>
                    <span id="description-count" class="<?php echo $desc_class; ?>"><?php echo $desc_len; ?></span>/160
                </div>
                <div class="almaseo-char-bar" data-field="description" aria-label="Description character usage">
                    <?php 
                    $desc_percent = min(100, ($desc_len / 180) * 100); // Max at 180 chars
                    $bar_class = ($desc_len >= 150 && $desc_len <= 160) ? 'good' : 
                                 (($desc_len >= 120 && $desc_len < 150) || ($desc_len > 160 && $desc_len <= 180) ? 'caution' : 
                                 ($desc_len < 120 ? 'too-short' : 'too-long'));
                    ?>
                    <div class="char-bar-track">
                        <div class="char-bar-fill <?php echo $bar_class; ?>" style="width: <?php echo $desc_percent; ?>%"></div>
                        <div class="char-bar-marker caution-start" style="left: 66.7%" aria-label="120 character mark"></div>
                        <div class="char-bar-marker good-start" style="left: 83.3%" aria-label="150 character mark"></div>
                        <div class="char-bar-marker good-end" style="left: 88.9%" aria-label="160 character mark"></div>
                    </div>
                </div>
                <p class="field-subtext" style="margin: 5px 0 0 0; color: #666; font-size: 12px; font-style: italic;">
                    <?php _e('Or use AlmaSEO AI to auto-create optimized meta descriptions → <a href="' . admin_url('admin.php?page=seo-playground-connection') . '">Upgrade</a>', 'almaseo'); ?>
                </p>
            </div>
            
            <div class="almaseo-field-group">
                <label for="almaseo_focus_keyword">
                    Focus Keyword
                    <span class="label-helper">(Choose a realistic target that matches search intent.)</span>
                </label>
                <input type="text" 
                       id="almaseo_focus_keyword" 
                       name="almaseo_focus_keyword" 
                       value="<?php echo esc_attr($seo_focus_keyword ?? ''); ?>" 
                       placeholder="<?php esc_attr_e('Choose a keyword or phrase that matches search intent', 'almaseo'); ?>"
                       class="almaseo-input" />
                <p class="field-subtext" style="margin: 5px 0 0 0; color: #666; font-size: 12px; font-style: italic;">
                    <?php _e('Or unlock smart keyword suggestions and intent detection → <a href="' . admin_url('admin.php?page=seo-playground-connection') . '">Upgrade</a>', 'almaseo'); ?>
                </p>
            </div>
            
            <!-- Google SERP Preview -->
            <div class="almaseo-serp-preview">
                <div class="serp-preview-header">
                    <h4>
                        Google Search Preview
                        <span class="serp-info-tooltip" title="This preview is approximate. Actual Google search results may look different." style="cursor: help; margin-left: 5px; color: #999;">ⓘ</span>
                    </h4>
                    <?php
                    // Add help text for SERP preview
                    if (function_exists('almaseo_render_help')) {
                        almaseo_render_help(
                            __('This simulates how your result may appear in Google. Aim for concise, clear titles and descriptions.', 'almaseo'),
                            __('Titles typically truncate around ~580px; descriptions around ~920px on desktop.', 'almaseo')
                        );
                    }
                    ?>
                    <div class="serp-toggle">
                        <button type="button" class="serp-toggle-btn active" data-view="desktop">Desktop</button>
                        <button type="button" class="serp-toggle-btn" data-view="mobile">Mobile</button>
                    </div>
                </div>
                <div class="serp-preview-container desktop-view">
                    <div class="serp-result">
                        <div class="serp-title" id="serp-preview-title">
                            <?php echo !empty($seo_title) ? esc_html($seo_title) : esc_html(get_the_title($post->ID)); ?>
                        </div>
                        <div class="serp-url">
                            <?php 
                            $site_url = parse_url(get_site_url());
                            $post_slug = $post->post_name ?: 'sample-post';
                            echo esc_html($site_url['host']) . ' › ' . esc_html($post_slug);
                            ?>
                        </div>
                        <div class="serp-description" id="serp-preview-description">
                            <?php echo !empty($seo_description) ? esc_html($seo_description) : esc_html(wp_trim_words($post->post_content, 20)); ?>
                        </div>
                    </div>
                </div>
                <p class="serp-caption">Preview is approximate and for guidance only.</p>
            </div>
            
            <!-- Static Keyword Intelligence -->
            <div class="almaseo-keyword-suggestions">
                <label>📊 Keyword Suggestions</label>
                <div class="keyword-suggestions-box">
                    <?php
                    // Basic keyword extraction from content (non-AI)
                    $content_text = strip_tags($post->post_content);
                    $words = str_word_count(strtolower($content_text), 1);
                    $word_freq = array_count_values($words);
                    
                    // Filter out common stop words
                    $stop_words = ['the', 'is', 'at', 'which', 'on', 'a', 'an', 'as', 'are', 'was', 'were', 'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 'could', 'should', 'may', 'might', 'must', 'shall', 'to', 'of', 'in', 'for', 'with', 'by', 'from', 'about', 'into', 'through', 'during', 'before', 'after', 'above', 'below', 'up', 'down', 'out', 'off', 'over', 'under', 'again', 'further', 'then', 'once', 'and', 'or', 'but', 'if', 'because', 'as', 'until', 'while', 'of', 'at', 'by', 'for', 'with', 'about', 'against', 'between', 'into', 'through', 'during', 'before', 'after', 'above', 'below', 'to', 'from', 'up', 'down', 'in', 'out', 'on', 'off', 'over', 'under', 'again', 'further', 'then', 'once'];
                    
                    foreach ($stop_words as $stop_word) {
                        unset($word_freq[$stop_word]);
                    }
                    
                    // Remove short words
                    $word_freq = array_filter($word_freq, function($key) {
                        return strlen($key) > 3;
                    }, ARRAY_FILTER_USE_KEY);
                    
                    // Sort by frequency
                    arsort($word_freq);
                    $top_keywords = array_slice($word_freq, 0, 8, true);
                    
                    if (empty($top_keywords)) {
                        echo '<p class="no-suggestions">Add more content to see keyword suggestions</p>';
                    } else {
                        echo '<div class="keyword-chips">';
                        foreach ($top_keywords as $keyword => $count) {
                            echo '<span class="keyword-chip" data-keyword="' . esc_attr($keyword) . '">';
                            echo esc_html($keyword) . ' (' . $count . ')';
                            echo '</span>';
                        }
                        echo '</div>';
                        echo '<p class="suggestion-hint">Click a keyword to use it as your focus keyword. Frequency shown in parentheses.</p>';
                    }
                    
                    if (!$is_connected) {
                        echo '<div class="ai-upsell-note">';
                        echo __('Unlock competitor insights, trending keywords, and search intent detection with AlmaSEO AI', 'almaseo');
                        echo ' → <a href="' . admin_url('admin.php?page=seo-playground-connection') . '">' . __('Connect Now', 'almaseo') . '</a>';
                        echo '</div>';
                    }
                    ?>
                </div>
            </div>
            
            </div>
            <!-- End of basic fields -->
            
            <!-- Post Intelligence Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-post-intelligence-section" id="almaseo-post-intelligence-section">
                <div class="almaseo-field-group" role="region" aria-labelledby="post-intelligence-heading">
                    <label for="almaseo_post_intelligence" class="almaseo-post-intelligence-label" id="post-intelligence-heading">
                        <span aria-hidden="true">🧠</span>
                        <span>Post Intelligence</span>
                        <span class="post-intelligence-tooltip" role="tooltip" aria-label="Get a quick AI-powered summary of your post's topic and tone">ⓘ</span>
                    </label>
                    
                    <div class="post-intelligence-container">
                        <div class="post-intelligence-textarea-container">
                            <textarea id="almaseo_post_intelligence" 
                                      name="almaseo_post_intelligence" 
                                      placeholder="Click 'Refresh Summary' to get an AI-powered insight about your post..."
                                      class="almaseo-textarea post-intelligence-textarea"
                                      readonly></textarea>
                        </div>
                        
                        <div class="post-intelligence-controls">
                            <button type="button" class="post-intelligence-btn" id="refresh-post-intelligence">
                                🔄 Refresh Summary
                            </button>
                        </div>
                        
                        <div class="post-intelligence-loading" id="post-intelligence-loading" style="display: none;">
                            <div class="post-intelligence-loading-spinner"></div>
                            <div class="post-intelligence-loading-text">Analyzing your post content...</div>
                        </div>
                        
                        <div class="post-intelligence-error" id="post-intelligence-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text" id="post-intelligence-error-text"></div>
                        </div>
                        
                        <div class="post-intelligence-tooltip" id="post-intelligence-tooltip" style="display: none;">
                            <div class="tooltip-icon">💡</div>
                            <div class="tooltip-text">Note: Post Intelligence is not auto-inserted into page builder editors. Copy manually if needed.</div>
                        </div>
                        
                        <div class="post-intelligence-timestamp" id="post-intelligence-timestamp" style="display: none;">
                            <small>Last updated: <span id="post-intelligence-last-updated"></span></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Keyword Intelligence Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-keyword-intelligence-section" id="almaseo-keyword-intelligence-section">
                <div class="almaseo-field-group">
                    <label for="almaseo_keyword_intelligence" class="almaseo-keyword-intelligence-label">
                        🔍 Keyword Intelligence (AI Powered)
                        <span class="keyword-intelligence-tooltip" title="Shows search intent, difficulty, and keyword tips">ⓘ</span>
                    </label>
                    
                    <div class="keyword-intelligence-container">
                        <!-- Empty State -->
                        <div class="keyword-intelligence-empty" id="keyword-intelligence-empty">
                            <div class="empty-icon">🔍</div>
                            <div class="empty-text">Enter a Focus Keyword above to get AI-powered keyword intelligence</div>
                            <div class="empty-hint">This will show search intent, difficulty, related terms, and pro tips</div>
                        </div>
                        
                        <!-- Intelligence Content -->
                        <div class="keyword-intelligence-content" id="keyword-intelligence-content" style="display: none;">
                            <div class="intelligence-field">
                                <label class="intelligence-field-label">Search Intent</label>
                                <div class="intelligence-field-value" id="keyword-intent">-</div>
                            </div>
                            
                            <div class="intelligence-field">
                                <label class="intelligence-field-label">Difficulty Estimate</label>
                                <div class="intelligence-field-value" id="keyword-difficulty">-</div>
                            </div>
                            
                            <div class="intelligence-field">
                                <label class="intelligence-field-label">Related Terms</label>
                                <div class="intelligence-field-value" id="keyword-related-terms">-</div>
                            </div>
                            
                            <div class="intelligence-field">
                                <label class="intelligence-field-label">Pro Tip</label>
                                <div class="intelligence-field-value" id="keyword-tip">-</div>
                            </div>
                        </div>
                        
                        <div class="keyword-intelligence-controls">
                            <button type="button" class="keyword-intelligence-btn" id="refresh-keyword-intelligence" disabled>
                                🔄 Refresh Keyword Intelligence
                            </button>
                        </div>
                        
                        <div class="keyword-intelligence-loading" id="keyword-intelligence-loading" style="display: none;">
                            <div class="keyword-intelligence-loading-spinner"></div>
                            <div class="keyword-intelligence-loading-text">Analyzing keyword intelligence...</div>
                        </div>
                        
                        <div class="keyword-intelligence-error" id="keyword-intelligence-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text" id="keyword-intelligence-error-text"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Meta Health Panel -->
            <?php if ($is_connected): ?>
            <div class="almaseo-meta-health-section" id="almaseo-meta-health-section">
                <div class="almaseo-field-group" role="region" aria-labelledby="meta-health-heading">
                    <label class="almaseo-meta-health-label" id="meta-health-heading">
                        <span aria-hidden="true">🧬</span>
                        <span>Meta Health Score</span>
                        <span class="meta-health-tooltip" role="tooltip" aria-label="Real-time AI analysis of your meta tags and SEO health">ⓘ</span>
                    </label>
                    
                    <div class="meta-health-container" aria-live="polite">
                        <!-- Score Display -->
                        <div class="meta-health-content" id="meta-health-content">
                            <div class="meta-score-circle" role="img" aria-label="Meta health score">
                                <div class="score-number" id="meta-score-number">--</div>
                                <div class="score-label">Score</div>
                            </div>
                            
                            <div class="meta-health-feedback">
                                <div class="feedback-text" id="meta-health-feedback-text">
                                    Click "Analyze Metadata" to get your SEO health score and recommendations.
                                </div>
                            </div>
                            
                            <div class="meta-health-controls">
                                <button type="button" class="meta-health-btn" id="analyze-metadata" aria-label="Analyze metadata for SEO health">
                                    <span aria-hidden="true">🔄</span> Analyze Metadata
                                </button>
                            </div>
                        </div>
                        
                        <!-- Loading State -->
                        <div class="meta-health-loading" id="meta-health-loading" style="display: none;" aria-hidden="true">
                            <div class="meta-health-loading-spinner"></div>
                            <div class="meta-health-loading-text">Analyzing your metadata...</div>
                        </div>
                        
                        <!-- Timestamp -->
                        <div class="meta-health-timestamp" id="meta-health-timestamp" style="display: none;">
                            <small>Last analyzed: <span id="meta-health-last-analyzed"></span></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Focus Keyword Suggestions placeholder -->
            <?php if ($is_connected): ?>
            <div class="almaseo-focus-suggestions-section" id="almaseo-focus-suggestions-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-focus-suggestions-label">
                        💡 Focus Keyword Suggestions
                        <span class="focus-suggestions-tooltip" title="AI-powered keyword recommendations">ⓘ</span>
                    </label>
                    
                    <div class="focus-suggestions-container">
                        <!-- Focus keyword suggestions will be populated by JavaScript -->
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
        </div>
        <!-- End SEO Overview Tab -->
        
        <script>
        jQuery(document).ready(function($) {
            // Draw Health Gauge
            function drawHealthGauge(score) {
                const canvas = document.getElementById('almaseo-health-gauge');
                if (!canvas) return;
                
                const ctx = canvas.getContext('2d');
                const centerX = canvas.width / 2;
                const centerY = canvas.height / 2;
                const radius = 80;
                
                // Determine color based on score
                let color = '#d63638'; // red
                if (score >= 80) {
                    color = '#00a32a'; // green
                } else if (score >= 50) {
                    color = '#dba617'; // yellow
                }
                
                // Clear canvas
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                // Draw background arc
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, Math.PI * 0.7, Math.PI * 2.3, false);
                ctx.lineWidth = 20;
                ctx.strokeStyle = '#e0e0e0';
                ctx.stroke();
                
                // Draw score arc
                const scoreAngle = (score / 100) * Math.PI * 1.6;
                ctx.beginPath();
                ctx.arc(centerX, centerY, radius, Math.PI * 0.7, Math.PI * 0.7 + scoreAngle, false);
                ctx.lineWidth = 20;
                ctx.strokeStyle = color;
                ctx.lineCap = 'round';
                ctx.stroke();
                
                // Draw tick marks
                ctx.strokeStyle = '#999';
                ctx.lineWidth = 1;
                for (let i = 0; i <= 10; i++) {
                    const angle = Math.PI * 0.7 + (i / 10) * Math.PI * 1.6;
                    const x1 = centerX + Math.cos(angle) * (radius - 10);
                    const y1 = centerY + Math.sin(angle) * (radius - 10);
                    const x2 = centerX + Math.cos(angle) * (radius + 10);
                    const y2 = centerY + Math.sin(angle) * (radius + 10);
                    
                    ctx.beginPath();
                    ctx.moveTo(x1, y1);
                    ctx.lineTo(x2, y2);
                    ctx.stroke();
                }
            }
            
            // Initialize gauge
            const score = parseInt($('.health-score-text .score-number').text()) || <?php echo intval($health_score); ?>;
            drawHealthGauge(score);
            
            // Setup custom tooltip for gauge
            function setupGaugeTooltip() {
                // Create tooltip element if it doesn't exist
                if (!$('#almaseo-gauge-tooltip').length) {
                    $('body').append(`
                        <div id="almaseo-gauge-tooltip" style="
                            position: absolute;
                            background: rgba(0, 0, 0, 0.9);
                            color: white;
                            padding: 10px 12px;
                            border-radius: 4px;
                            font-size: 12px;
                            line-height: 1.5;
                            white-space: pre-line;
                            max-width: 280px;
                            pointer-events: none;
                            z-index: 100000;
                            display: none;
                            box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                        ">
                            <strong>SEO Health Score Breakdown:</strong>
                            
                            • Title: 20 points
                            • Meta Description: 15 points
                            • H1 Heading: 10 points
                            • Keyword in Introduction: 10 points
                            • Internal Link: 10 points
                            • Outbound Link: 10 points
                            • Image Alt Text: 10 points
                            • Readability: 10 points
                            • Canonical URL: 3 points
                            • Robots Settings: 2 points
                            
                            <em>Total: 100 points (weighted by importance)</em>
                        </div>
                    `);
                }
                
                // Add hover handlers to the gauge wrapper
                $('.health-gauge-wrapper').on('mouseenter', function(e) {
                    const $tooltip = $('#almaseo-gauge-tooltip');
                    $tooltip.css({
                        left: e.pageX + 15,
                        top: e.pageY - 10
                    }).fadeIn(200);
                });
                
                $('.health-gauge-wrapper').on('mousemove', function(e) {
                    const $tooltip = $('#almaseo-gauge-tooltip');
                    $tooltip.css({
                        left: e.pageX + 15,
                        top: e.pageY - 10
                    });
                });
                
                $('.health-gauge-wrapper').on('mouseleave', function() {
                    $('#almaseo-gauge-tooltip').fadeOut(200);
                });
            }
            
            // Initialize tooltip
            setupGaugeTooltip();
            
            // Handle recalculate button
            $('#almaseo-health-recalculate').on('click', function(e) {
                e.preventDefault();
                const $btn = $(this);
                const postId = $btn.data('post-id') || <?php echo $post->ID; ?>;
                
                $btn.prop('disabled', true).find('.dashicons').addClass('spin');
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'almaseo_health_recalculate',
                        post_id: postId,
                        nonce: '<?php echo wp_create_nonce('almaseo_health_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            // Update score display
                            $('.score-number').text(response.data.score);
                            
                            // Redraw gauge
                            drawHealthGauge(response.data.score);
                            
                            // Update signals
                            if (response.data.breakdown) {
                                $.each(response.data.breakdown, function(signal, result) {
                                    const $signal = $('.almaseo-health-signal').filter(function() {
                                        return $(this).find('.signal-label').text().toLowerCase().includes(signal.replace('_', ' '));
                                    });
                                    
                                    if ($signal.length) {
                                        // Update icon
                                        $signal.find('.signal-icon').text(result.pass ? '✅' : '❌');
                                        
                                        // Update classes
                                        $signal.removeClass('pass fail').addClass(result.pass ? 'pass' : 'fail');
                                        
                                        // Update bar
                                        $signal.find('.signal-bar-fill')
                                            .removeClass('pass fail')
                                            .addClass(result.pass ? 'pass' : 'fail')
                                            .css('width', result.pass ? '100%' : '0%');
                                        
                                        // Update note
                                        $signal.find('.signal-note').text(result.note);
                                        
                                        // Show/hide Go to Field button
                                        if (result.pass) {
                                            $signal.find('.signal-goto-btn').hide();
                                        } else {
                                            $signal.find('.signal-goto-btn').show();
                                        }
                                    }
                                });
                                
                                // Update pass/fail counts
                                let passCount = 0, failCount = 0;
                                $.each(response.data.breakdown, function(signal, result) {
                                    if (result.pass) passCount++;
                                    else failCount++;
                                });
                                
                                $('.health-stat').first().find('strong').text(passCount);
                                $('.health-stat').last().find('strong').text(failCount);
                            }
                        }
                    },
                    complete: function() {
                        $btn.prop('disabled', false).find('.dashicons').removeClass('spin');
                    }
                });
            });
            
            // Handle Go to Field buttons
            $('.signal-goto-btn').on('click', function() {
                const fieldId = $(this).data('field');
                const $field = $('#' + fieldId);
                
                if ($field.length) {
                    // Scroll to field
                    $('html, body').animate({
                        scrollTop: $field.offset().top - 100
                    }, 500, function() {
                        // Focus the field
                        $field.focus().addClass('field-highlight');
                        
                        // Remove highlight after 2 seconds
                        setTimeout(function() {
                            $field.removeClass('field-highlight');
                        }, 2000);
                    });
                }
            });
        });
        </script>
        
        <style>
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .dashicons.spin {
            animation: spin 1s linear infinite;
        }
        .field-highlight {
            background-color: #fffbcc !important;
            border-color: #dba617 !important;
            box-shadow: 0 0 5px rgba(219, 166, 23, 0.5) !important;
            transition: all 0.3s ease;
        }
        </style>
        
        <!-- Search Console Tab -->
        <div class="almaseo-tab-panel" id="tab-search-console">
            <!-- Tab Header -->
            <div class="almaseo-search-console-header">
                <h2 class="almaseo-search-console-title">Google Search Console</h2>
                <p class="almaseo-search-console-subtitle">Performance metrics from Google Search Console (coming soon).</p>
            </div>
            
            <!-- Date Range Selector (Disabled) -->
            <div class="almaseo-search-console-controls">
                <div class="almaseo-date-range-wrapper">
                    <select class="almaseo-date-range-selector" disabled aria-label="Date range selector">
                        <option value="28" selected>Last 28 days</option>
                        <option value="7">Last 7 days</option>
                        <option value="90">Last 90 days</option>
                    </select>
                </div>
            </div>
            
            <!-- Placeholder Card -->
            <div class="almaseo-search-console-placeholder">
                <div class="almaseo-placeholder-card">
                    <div class="almaseo-placeholder-icon">
                        <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 14c1.49-1.46 3-3.21 3-5.5A5.5 5.5 0 0 0 16.5 3c-1.76 0-3 .5-4.5 2-1.5-1.5-2.74-2-4.5-2A5.5 5.5 0 0 0 2 8.5c0 2.3 1.5 4.05 3 5.5Z"/>
                            <path d="M12 5L8 21l4-7 4 7-4-16"/>
                        </svg>
                    </div>
                    <h3 class="almaseo-placeholder-heading">Connect to AlmaSEO Dashboard</h3>
                    <p class="almaseo-placeholder-body">
                        To view Google Search Console data here, you'll connect your site from the AlmaSEO Dashboard (feature coming soon).
                    </p>
                    <button type="button" class="almaseo-placeholder-button" disabled>
                        Coming Soon
                    </button>
                    <p class="almaseo-placeholder-note">
                        OAuth connection will be enabled in a future update.
                    </p>
                </div>
            </div>
            
            <!-- Schema Markup Section -->
            <div class="almaseo-schema-section">
                <div class="almaseo-field-group">
                    <label for="almaseo_schema_type" class="almaseo-schema-label">
                        📊 Schema Markup
                    </label>
                    
                    <!-- AI Schema Suggestion -->
                    <?php if ($is_connected && empty($seo_schema_type)): ?>
                    <div class="schema-suggestion-container" id="schema-suggestion-container">
                        <div class="schema-suggestion-loading" id="schema-suggestion-loading">
                            <div class="suggestion-loading-spinner"></div>
                            <div class="suggestion-loading-text">Analyzing content for schema type...</div>
                        </div>
                        <div class="schema-suggestion-content" id="schema-suggestion-content" style="display: none;">
                            <div class="suggestion-icon">💡</div>
                            <div class="suggestion-text">
                                <strong>Suggested Schema Type:</strong> <span id="suggested-schema-type"></span>
                            </div>
                            <div class="suggestion-action">
                                <button type="button" class="use-suggestion-btn" id="use-schema-suggestion">
                                    Use This
                                </button>
                            </div>
                        </div>
                        <div class="schema-suggestion-error" id="schema-suggestion-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text">Could not fetch schema suggestion</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <select id="almaseo_schema_type" 
                            name="almaseo_schema_type" 
                            class="almaseo-schema-select">
                        <option value="">None (default)</option>
                        <option value="Article" <?php selected($seo_schema_type, 'Article'); ?>>Article</option>
                        <option value="BlogPosting" <?php selected($seo_schema_type, 'BlogPosting'); ?>>BlogPosting</option>
                        <option value="NewsArticle" <?php selected($seo_schema_type, 'NewsArticle'); ?>>NewsArticle</option>
                        <option value="Product" <?php selected($seo_schema_type, 'Product'); ?>>Product</option>
                        <option value="Event" <?php selected($seo_schema_type, 'Event'); ?>>Event</option>
                        <option value="FAQPage" <?php selected($seo_schema_type, 'FAQPage'); ?>>FAQPage</option>
                        <option value="HowTo" <?php selected($seo_schema_type, 'HowTo'); ?>>HowTo</option>
                        <option value="LocalBusiness" <?php selected($seo_schema_type, 'LocalBusiness'); ?>>LocalBusiness</option>
                    </select>
                    
                    <?php if (!$is_connected): ?>
                    <div class="schema-advanced-notice">
                        <div class="notice-icon">🔒</div>
                        <div class="notice-text">
                            <strong>Advanced schema types</strong> (FAQPage, HowTo, LocalBusiness) are available with AlmaSEO connection.
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <!-- Schema Preview -->
                    <div class="schema-preview-container" id="schema-preview-container" style="display: none;">
                        <div class="schema-preview-header">
                            <strong>JSON-LD Preview</strong>
                            <span class="schema-preview-note">This will be automatically generated for your content</span>
                        </div>
                        <div class="schema-preview-content" id="schema-preview-content">
                            <!-- Preview content will be populated by JavaScript -->
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Schema Analyzer Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-schema-analyzer-section" id="almaseo-schema-analyzer-section" style="display: none;">
                <div class="almaseo-field-group">
                    <label class="almaseo-schema-analyzer-label">
                        🧪 Schema Analyzer (AI Powered)
                        <span class="schema-analyzer-tooltip" title="AI-powered analysis of your schema markup implementation">ⓘ</span>
                    </label>
                    
                    <div class="schema-analyzer-container">
                        <!-- Empty State -->
                        <div class="schema-analyzer-empty" id="schema-analyzer-empty">
                            <div class="empty-icon">🧪</div>
                            <div class="empty-text">Please select a Schema Type to begin.</div>
                            <div class="empty-hint">Choose a schema type above to get AI-powered analysis</div>
                        </div>
                        
                        <!-- Analysis Content -->
                        <div class="schema-analyzer-content" id="schema-analyzer-content" style="display: none;">
                            <div class="schema-analysis-info-box">
                                <div class="analysis-icon">🧠</div>
                                <div class="analysis-text" id="schema-analysis-text">
                                    <!-- Analysis text will be populated by JavaScript -->
                                </div>
                            </div>
                            
                            <div class="schema-analyzer-controls">
                                <button type="button" class="schema-analyzer-btn" id="refresh-schema-analysis">
                                    🔄 Reanalyze Schema
                                </button>
                            </div>
                            
                            <div class="schema-analyzer-timestamp" id="schema-analyzer-timestamp">
                                <!-- Timestamp will be populated by JavaScript -->
                            </div>
                        </div>
                        
                        <div class="schema-analyzer-loading" id="schema-analyzer-loading" style="display: none;">
                            <div class="schema-analyzer-loading-spinner"></div>
                            <div class="schema-analyzer-loading-text">Analyzing...</div>
                        </div>
                        
                        <div class="schema-analyzer-error" id="schema-analyzer-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text" id="schema-analyzer-error-text"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Meta Health Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-meta-health-section" id="almaseo-meta-health-section" style="display: none;">
                <div class="almaseo-field-group">
                    <label class="almaseo-meta-health-label">
                        🧬 Meta Health (AI Feedback)
                        <span class="meta-health-tooltip" title="Get AI feedback on your SEO title and meta description to improve clicks and clarity">ⓘ</span>
                    </label>
                    
                    <div class="meta-health-container">
                        <!-- Empty State -->
                        <div class="meta-health-empty" id="meta-health-empty">
                            <div class="empty-icon">🧬</div>
                            <div class="empty-text">Please fill in both SEO Title and Meta Description to begin.</div>
                            <div class="empty-hint">Add your SEO title and meta description above to get AI-powered feedback</div>
                        </div>
                        
                        <!-- Analysis Content -->
                        <div class="meta-health-content" id="meta-health-content" style="display: none;">
                            <div class="meta-health-score-block">
                                <div class="meta-score-circle" id="meta-score-circle">
                                    <div class="score-number" id="meta-score-number">0</div>
                                    <div class="score-label">Meta Score</div>
                                </div>
                            </div>
                            
                            <div class="meta-health-feedback">
                                <div class="feedback-text" id="meta-health-feedback-text">
                                    <!-- Feedback text will be populated by JavaScript -->
                                </div>
                            </div>
                            
                            <div class="meta-health-controls">
                                <button type="button" class="meta-health-btn" id="analyze-metadata">
                                    🔄 Analyze Metadata
                                </button>
                            </div>
                            
                            <div class="meta-health-timestamp" id="meta-health-timestamp">
                                <!-- Timestamp will be populated by JavaScript -->
                            </div>
                        </div>
                        
                        <div class="meta-health-loading" id="meta-health-loading" style="display: none;">
                            <div class="meta-health-loading-spinner"></div>
                            <div class="meta-health-loading-text">Analyzing metadata...</div>
                        </div>
                        
                        <div class="meta-health-error" id="meta-health-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text" id="meta-health-error-text"></div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Content Aging Section (Consolidated) -->
            <div class="almaseo-content-aging-section" id="almaseo-content-aging-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-content-aging-label">
                        📆 Content Aging
                        <span class="content-aging-tooltip" title="Track content age and set refresh reminders for SEO freshness">ⓘ</span>
                    </label>
                    
                    <div class="content-aging-container">
                        <?php 
                        $post_date = get_the_date('U', $post->ID);
                        $post_modified = get_post_modified_time('U', false, $post->ID);
                        $current_time = current_time('timestamp');
                        $days_old = floor(($current_time - $post_date) / (60 * 60 * 24));
                        $days_since_update = floor(($current_time - $post_modified) / (60 * 60 * 24));
                        
                        // Format age display
                        if ($days_old === 0) {
                            $age_text = 'Published Today';
                        } elseif ($days_old === 1) {
                            $age_text = '1 Day Old';
                        } elseif ($days_old < 30) {
                            $age_text = $days_old . ' Days Old';
                        } elseif ($days_old < 365) {
                            $months = floor($days_old / 30);
                            $age_text = $months . ' Month' . ($months !== 1 ? 's' : '') . ' Old';
                        } else {
                            $years = floor($days_old / 365);
                            $age_text = $years . ' Year' . ($years !== 1 ? 's' : '') . ' Old';
                        }
                        
                        // Get reminder settings
                        $reminder_days = get_post_meta($post->ID, '_almaseo_update_reminder_days', true) ?: '';
                        $reminder_enabled = get_post_meta($post->ID, '_almaseo_update_reminder_enabled', true) ?: '';
                        $reminder_email = get_post_meta($post->ID, '_almaseo_update_reminder_email', true) ?: '';
                        $scheduled_time = get_post_meta($post->ID, '_almaseo_update_reminder_scheduled', true) ?: '';
                        $admin_email = get_option('admin_email');
                        ?>
                        
                        <div class="content-aging-content">
                            <!-- Age Display -->
                            <div class="content-age-display">
                                <div class="age-text">
                                    <strong><?php echo esc_html($age_text); ?></strong>
                                </div>
                                <div class="age-subtitle">
                                    Published: <?php echo get_the_date('F j, Y', $post->ID); ?>
                                </div>
                            </div>
                            
                            <!-- Mark as Refreshed Button -->
                            <div class="content-aging-controls">
                                <button type="button" class="content-aging-btn" id="mark-as-refreshed">
                                    🔁 Mark as Refreshed
                                </button>
                            </div>
                            
                            <!-- Update Reminder Settings -->
                            <div class="update-reminder-section">
                                <div class="reminder-toggle">
                                    <label>
                                        <input type="checkbox" 
                                               id="almaseo_update_reminder_enabled" 
                                               name="almaseo_update_reminder_enabled"
                                               value="1" 
                                               <?php checked($reminder_enabled, '1'); ?> />
                                        Set content update reminder after 
                                        <input type="number" 
                                               id="almaseo_update_reminder_days" 
                                               name="almaseo_update_reminder_days"
                                               value="<?php echo esc_attr($reminder_days ?: '90'); ?>" 
                                               min="1" 
                                               max="365"
                                               style="width: 60px;" /> days
                                    </label>
                                </div>
                                
                                <p class="description" style="margin: 8px 0 0 0; color: #666; font-size: 13px;">
                                    <?php _e('We\'ll remind you via an admin notice on this site.', 'almaseo'); ?>
                                    <?php if ($reminder_email): ?>
                                        <br><?php echo sprintf(__('Also emailing %s', 'almaseo'), '<strong>' . esc_html($admin_email) . '</strong>'); ?>
                                    <?php endif; ?>
                                </p>
                                
                                <?php if ($reminder_enabled && $scheduled_time): ?>
                                <div class="reminder-scheduled-pill" style="margin: 10px 0; display: inline-block; background: #e8f4f8; color: #0073aa; padding: 5px 12px; border-radius: 15px; font-size: 13px;">
                                    <?php 
                                    $scheduled_date = date_i18n(get_option('date_format'), $scheduled_time);
                                    echo sprintf(__('Reminder set for %s', 'almaseo'), $scheduled_date); 
                                    ?>
                                    <a href="#" class="cancel-reminder" data-post-id="<?php echo $post->ID; ?>" style="margin-left: 8px; color: #d63638; text-decoration: none;">
                                        <?php _e('Cancel', 'almaseo'); ?>
                                    </a>
                                </div>
                                <?php endif; ?>
                                
                                <div style="margin: 10px 0 0 0;">
                                    <label>
                                        <input type="checkbox" 
                                               id="almaseo_update_reminder_email" 
                                               name="almaseo_update_reminder_email"
                                               value="1" 
                                               <?php checked($reminder_email, '1'); ?> />
                                        <?php echo sprintf(__('Also email me at %s', 'almaseo'), '<strong>' . esc_html($admin_email) . '</strong>'); ?>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- AI Keyword Suggestions Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-ai-keywords-section" id="almaseo-ai-keywords-section" style="display: none;">
                <div class="almaseo-field-group">
                    <label class="almaseo-ai-label">
                        🎯 Keyword Suggestions (Powered by AlmaSEO AI)
                    </label>
                    
                    <?php if ($is_connected): ?>
                    <!-- Connected State: Show AI keyword suggestions placeholder -->
                    <div class="almaseo-ai-keywords-connected">
                        <div class="ai-keywords-message">
                            Your AI assistant will suggest keywords based on your content and audience.
                        </div>
                        <div class="ai-keywords-placeholder">
                            <div class="keyword-item">
                                <span class="keyword-text">marketing tips</span>
                                <span class="keyword-score">95%</span>
                            </div>
                            <div class="keyword-item">
                                <span class="keyword-text">seo checklist</span>
                                <span class="keyword-score">87%</span>
                            </div>
                            <div class="keyword-item">
                                <span class="keyword-text">wordpress plugin</span>
                                <span class="keyword-score">82%</span>
                            </div>
                        </div>
                        <div class="ai-keywords-note">
                            <em>Real AI suggestions will appear here based on your content analysis.</em>
                        </div>
                    </div>
                    <?php else: ?>
                    <!-- Disconnected State: Show locked message -->
                    <div class="almaseo-ai-keywords-locked">
                        <div class="ai-keywords-locked-message">
                            🔒 Connect to AlmaSEO to unlock intelligent keyword suggestions tailored to your content.
                        </div>
                        <div class="ai-keywords-locked-action">
                            <a href="<?php echo esc_url(admin_url('admin.php?page=seo-playground-connection')); ?>" class="button button-primary">
                                Connect to AlmaSEO
                            </a>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Internal Link Suggestions Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-internal-links-section" id="almaseo-internal-links-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-internal-links-label">
                        🔗 Internal Link Suggestions (Powered by AlmaSEO)
                    </label>
                    
                    <div class="internal-links-container">
                        <div class="internal-links-loading" id="internal-links-loading">
                            <div class="links-loading-spinner"></div>
                            <div class="links-loading-text">Analyzing your content for internal link opportunities...</div>
                        </div>
                        
                        <div class="internal-links-content" id="internal-links-content" style="display: none;">
                            <div class="internal-links-list" id="internal-links-list">
                                <!-- Links will be populated by JavaScript -->
                            </div>
                        </div>
                        
                        <div class="internal-links-error" id="internal-links-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text">Could not fetch internal link suggestions</div>
                        </div>
                        
                        <div class="internal-links-no-results" id="internal-links-no-results" style="display: none;">
                            <div class="no-results-icon">🔍</div>
                            <div class="no-results-text">No strong internal link suggestions were found for this post.</div>
                            <div class="no-results-hint">Try adding more content or specific topics to your post.</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- AI Rewrite Assistant Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-rewrite-section" id="almaseo-rewrite-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-rewrite-label">
                        ✍️ AI Rewrite Assistant
                    </label>
                    
                    <div class="rewrite-container">
                        <div class="rewrite-input-area">
                            <textarea id="rewrite-input" 
                                      class="almaseo-textarea rewrite-textarea" 
                                      placeholder="Paste content to rewrite..."
                                      rows="4"></textarea>
                            
                            <div class="rewrite-controls">
                                <select id="rewrite-type" class="rewrite-type-select">
                                    <option value="paragraph">Paragraph</option>
                                    <option value="title">SEO Title</option>
                                    <option value="description">Meta Description</option>
                                </select>
                                
                                <button type="button" class="rewrite-btn" id="rewrite-submit">
                                    🔁 Rewrite with AlmaSEO
                                </button>
                            </div>
                        </div>
                        
                        <div class="rewrite-loading" id="rewrite-loading" style="display: none;">
                            <div class="rewrite-loading-spinner"></div>
                            <div class="rewrite-loading-text">Rewriting your content...</div>
                        </div>
                        
                        <div class="rewrite-result" id="rewrite-result" style="display: none;">
                            <div class="rewrite-result-header">
                                <strong>Rewritten Content:</strong>
                            </div>
                            <div class="rewrite-result-content" id="rewrite-result-content">
                                <!-- Rewritten content will be populated by JavaScript -->
                            </div>
                            <div class="rewrite-result-actions">
                                <button type="button" class="rewrite-action-btn copy-rewrite-btn" id="copy-rewrite">
                                    📋 Copy
                                </button>
                                <button type="button" class="rewrite-action-btn replace-rewrite-btn" id="replace-rewrite">
                                    ↩️ Replace Input
                                </button>
                                <button type="button" class="rewrite-action-btn clear-rewrite-btn" id="clear-rewrite">
                                    ❌ Clear
                                </button>
                            </div>
                        </div>
                        
                        <div class="rewrite-error" id="rewrite-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text">Could not rewrite content</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Content Brief (AI-Powered) Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-content-brief-section" id="almaseo-content-brief-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-content-brief-label">
                        🧠 Content Brief (AI-Powered)
                    </label>
                    
                    <div class="content-brief-container">
                        <div class="content-brief-textarea-container">
                            <textarea id="content-brief-textarea" 
                                      class="almaseo-textarea content-brief-textarea" 
                                      placeholder="Your AI-generated content brief will appear here..."
                                      rows="8"
                                      readonly></textarea>
                        </div>
                        
                        <div class="content-brief-controls">
                            <button type="button" class="content-brief-btn" id="generate-content-brief">
                                🪄 Generate Brief
                            </button>
                            
                            <div class="content-brief-loading" id="content-brief-loading" style="display: none;">
                                <div class="content-brief-loading-spinner"></div>
                                <div class="content-brief-loading-text">Generating content brief...</div>
                            </div>
                        </div>
                        
                        <div class="content-brief-error" id="content-brief-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text">Could not generate content brief</div>
                        </div>
                        
                        <div class="content-brief-tooltip" id="content-brief-tooltip" style="display: none;">
                            <div class="tooltip-icon">💡</div>
                            <div class="tooltip-text">Note: Content Brief is not auto-inserted into page builder editors. Copy manually if needed.</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- AI FAQ Generator Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-faq-generator-section" id="almaseo-faq-generator-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-faq-generator-label">
                        ❓ AI FAQ Generator
                    </label>
                    
                    <div class="faq-generator-container">
                        <div class="faq-generator-textarea-container">
                            <textarea id="faq-generator-textarea" 
                                      class="almaseo-textarea faq-generator-textarea" 
                                      placeholder="Your AI-generated FAQs will appear here..."
                                      rows="10"
                                      readonly></textarea>
                        </div>
                        
                        <div class="faq-generator-controls">
                            <button type="button" class="faq-generator-btn" id="generate-faqs">
                                ✨ Generate FAQs
                            </button>
                            
                            <div class="faq-generator-loading" id="faq-generator-loading" style="display: none;">
                                <div class="faq-generator-loading-spinner"></div>
                                <div class="faq-generator-loading-text">Generating FAQs...</div>
                            </div>
                        </div>
                        
                        <div class="faq-generator-error" id="faq-generator-error" style="display: none;">
                            <div class="error-icon">⚠️</div>
                            <div class="error-text">Could not generate FAQs</div>
                        </div>
                        
                        <div class="faq-generator-tooltip" id="faq-generator-tooltip" style="display: none;">
                            <div class="tooltip-icon">💡</div>
                            <div class="tooltip-text">Note: FAQs are not auto-inserted into page builder editors. Copy manually if needed.</div>
                        </div>
                        
                        <div class="faq-generator-validation" id="faq-generator-validation" style="display: none;">
                            <div class="validation-icon">📝</div>
                            <div class="validation-text">Content must be at least 100 words to generate meaningful FAQs.</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Saved Prompts & Snippets Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-saved-snippets-section" id="almaseo-saved-snippets-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-saved-snippets-label">
                        💾 Saved Prompts & Snippets
                    </label>
                    
                    <div class="saved-snippets-container">
                        <div class="saved-snippets-header">
                            <button type="button" class="new-snippet-btn" id="new-snippet-btn">
                                + New Snippet
                            </button>
                        </div>
                        
                        <div class="saved-snippets-list" id="saved-snippets-list">
                            <!-- Snippets will be populated by JavaScript -->
                        </div>
                        
                        <div class="saved-snippets-empty" id="saved-snippets-empty">
                            <div class="empty-icon">📝</div>
                            <div class="empty-text">No saved snippets yet</div>
                            <div class="empty-hint">Create your first snippet to get started</div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- SEO Scorecard Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-scorecard-section" id="almaseo-scorecard-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-scorecard-label">
                        ✅ SEO Scorecard & Publish Assistant
                    </label>
                    
                    <div class="scorecard-container">
                        <!-- SEO Confidence Score Indicator -->
                        <div class="seo-confidence-score" id="seo-confidence-score" role="region" aria-label="SEO Confidence Score">
                            <div class="confidence-score-title" id="confidence-score-title">SEO Confidence Score</div>
                            <div class="confidence-score-ring" role="img" aria-labelledby="confidence-score-title">
                                <svg class="confidence-ring" width="120" height="120" viewBox="0 0 120 120" aria-hidden="true">
                                    <circle class="confidence-ring-bg" cx="60" cy="60" r="54" stroke-width="8" fill="none"/>
                                    <circle class="confidence-ring-progress" cx="60" cy="60" r="54" stroke-width="8" fill="none" 
                                            stroke-dasharray="339.292" stroke-dashoffset="339.292" 
                                            transform="rotate(-90 60 60)"/>
                                </svg>
                                <div class="confidence-score-percentage" id="confidence-score-percentage" aria-live="polite">0%</div>
                            </div>
                            <div class="confidence-score-label" id="confidence-score-label" aria-live="polite">Analyzing...</div>
                        </div>
                        
                        <div class="scorecard-header" id="scorecard-header">
                            <div class="scorecard-summary">
                                <span class="scorecard-title">Publish Checklist</span>
                                <span class="scorecard-status" id="scorecard-status">Analyzing...</span>
                            </div>
                        </div>
                        
                        <div class="scorecard-checklist" id="scorecard-checklist">
                            <div class="scorecard-item" data-check="seo-title">
                                <div class="scorecard-icon" id="seo-title-icon">⏳</div>
                                <div class="scorecard-text">
                                    <span class="scorecard-label">SEO Title</span>
                                    <span class="scorecard-tooltip">Check if SEO title field is filled</span>
                                </div>
                            </div>
                            
                            <div class="scorecard-item" data-check="meta-description">
                                <div class="scorecard-icon" id="meta-description-icon">⏳</div>
                                <div class="scorecard-text">
                                    <span class="scorecard-label">Meta Description</span>
                                    <span class="scorecard-tooltip">Check if meta description field is filled</span>
                                </div>
                            </div>
                            
                            <div class="scorecard-item" data-check="focus-keywords">
                                <div class="scorecard-icon" id="focus-keywords-icon">⏳</div>
                                <div class="scorecard-text">
                                    <span class="scorecard-label">Focus Keyword(s)</span>
                                    <span class="scorecard-tooltip">Check if keyword suggestions exist or field has text</span>
                                </div>
                            </div>
                            
                            <div class="scorecard-item" data-check="internal-links">
                                <div class="scorecard-icon" id="internal-links-icon">⏳</div>
                                <div class="scorecard-text">
                                    <span class="scorecard-label">Internal Links</span>
                                    <span class="scorecard-tooltip">At least 1 Alma-inserted internal link</span>
                                </div>
                            </div>
                            
                            <div class="scorecard-item" data-check="schema-type">
                                <div class="scorecard-icon" id="schema-type-icon">⏳</div>
                                <div class="scorecard-text">
                                    <span class="scorecard-label">Schema Type</span>
                                    <span class="scorecard-tooltip">Any schema other than "None" selected</span>
                                </div>
                            </div>
                            
                            <div class="scorecard-item" data-check="ai-rewrite">
                                <div class="scorecard-icon" id="ai-rewrite-icon">⏳</div>
                                <div class="scorecard-text">
                                    <span class="scorecard-label">AI Rewrite</span>
                                    <span class="scorecard-tooltip">Optional: Check if user has submitted rewrite</span>
                                </div>
                            </div>
                            
                            <div class="scorecard-item" data-check="content-length">
                                <div class="scorecard-icon" id="content-length-icon">⏳</div>
                                <div class="scorecard-text">
                                    <span class="scorecard-label">Content Length</span>
                                    <span class="scorecard-tooltip">Warn if content < 300 words</span>
                                </div>
                            </div>
                            
                            <div class="scorecard-item" data-check="reoptimization">
                                <div class="scorecard-icon" id="reoptimization-icon">⏳</div>
                                <div class="scorecard-text">
                                    <span class="scorecard-label">Reoptimization Alert</span>
                                    <span class="scorecard-tooltip">Show if reoptimize flag is TRUE</span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="scorecard-footer" id="scorecard-footer" style="display: none;">
                            <div class="scorecard-passed" id="scorecard-passed" style="display: none;">
                                <div class="passed-icon">🎉</div>
                                <div class="passed-text">Publish Checklist Passed!</div>
                                <div class="passed-subtext">Your post is ready for publication.</div>
                            </div>
                            
                            <div class="scorecard-warning" id="scorecard-warning" style="display: none;">
                                <div class="warning-icon">⚠️</div>
                                <div class="warning-text">Some improvements recommended</div>
                                <div class="warning-subtext">Consider addressing the items above before publishing.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- SEO Playground Tips Section -->
            <?php if ($is_connected): ?>
            <div class="almaseo-seo-tips-section" id="almaseo-seo-tips-section">
                <div class="almaseo-field-group">
                    <label class="almaseo-seo-tips-label" for="seo-tips-toggle">
                        💡 AlmaSEO SEO Playground Tips (From Alma)
                        <span class="tips-tooltip" title="Helpful SEO tips to improve your workflow and results.">ⓘ</span>
                    </label>
                    
                    <div class="seo-tips-container">
                        <div class="seo-tips-header">
                            <button type="button" class="seo-tips-toggle" id="seo-tips-toggle" aria-expanded="false">
                                <span class="toggle-icon">▼</span>
                                <span class="toggle-text">Show Tips</span>
                            </button>
                        </div>
                        
                        <div class="seo-tips-content" id="seo-tips-content" style="display: none;">
                            <div class="seo-tips-display">
                                <div class="tip-content" id="tip-content">
                                    <!-- Tip content will be populated by JavaScript -->
                                </div>
                            </div>
                            
                            <div class="seo-tips-controls">
                                <button type="button" class="shuffle-tip-btn" id="shuffle-tip-btn">
                                    🔀 Shuffle Tip
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <!-- End Search Console Tab -->
        
        <!-- Schema & Meta Tab -->
        <div class="almaseo-tab-panel" id="tab-schema-meta">
            <!-- Tab Header -->
            <div class="almaseo-schema-meta-header">
                <h2 class="almaseo-schema-meta-title">Schema & Meta</h2>
                <p class="almaseo-schema-meta-subtitle">Control how search engines and social networks understand this page.</p>
                <?php
                // Add help text for Schema & Meta
                if (function_exists('almaseo_render_help')) {
                    almaseo_render_help(
                        __('Schema helps search engines understand your content type (e.g., Article, Product). Add types that match the page.', 'almaseo'),
                        __('Avoid duplicate schema from multiple plugins on the same page.', 'almaseo')
                    );
                }
                ?>
            </div>
            
            <!-- Connection Status Row -->
            <?php 
            $is_connected = seo_playground_is_alma_connected();
            if (!$is_connected): ?>
            <div class="almaseo-connection-notice" style="margin: 20px 0; padding: 15px; background: #f0f6fc; border-left: 3px solid #2271b1; border-radius: 3px; display: flex; align-items: center; justify-content: space-between;">
                <div>
                    <strong style="color: #0c5460;">Connect to AlmaSEO to unlock advanced schema types and dashboard presets.</strong>
                    <p style="margin: 5px 0 0 0; color: #666; font-size: 13px;">Free features like Article schema and meta robots are always available.</p>
                </div>
                <a href="#tab-unlock-features" class="button button-secondary almaseo-tab-link" style="white-space: nowrap;">Connect Now →</a>
            </div>
            <?php else: 
                $last_sync = get_option('almaseo_last_sync', '');
                $sync_text = $last_sync ? human_time_diff(strtotime($last_sync)) . ' ago' : 'Never';
            ?>
            <div class="almaseo-connection-status" style="margin: 20px 0; padding: 10px 15px; background: #d4edda; border: 1px solid #c3e6cb; border-radius: 20px; display: inline-block; color: #155724; font-size: 13px;">
                ✓ Connected to AlmaSEO • Last sync: <?php echo esc_html($sync_text); ?>
            </div>
            <?php endif; ?>
            
            <!-- Meta Robots Card -->
            <div class="almaseo-card">
                <div class="almaseo-card-header">
                    <h3 class="almaseo-card-title">
                        <span class="card-icon">🤖</span>
                        Meta Robots
                    </h3>
                    <p class="almaseo-card-description">Control how search engines crawl and index this page</p>
                </div>
                <div class="almaseo-card-body">
                    <div class="almaseo-meta-robots-grid">
                        <!-- Index/NoIndex Pair (Mutually Exclusive) -->
                        <div class="meta-robots-option">
                            <label class="toggle-label">
                                <input type="checkbox" 
                                       id="almaseo_robots_index" 
                                       name="almaseo_robots_index" 
                                       value="1" 
                                       <?php checked(get_post_meta($post->ID, '_almaseo_robots_index', true) !== 'noindex'); ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-text">Index</span>
                            </label>
                            <p class="option-description">Allow search engines to show this page in search results</p>
                        </div>
                        
                        <div class="meta-robots-option">
                            <label class="toggle-label">
                                <input type="checkbox" 
                                       id="almaseo_robots_noindex" 
                                       name="almaseo_robots_noindex" 
                                       value="1" 
                                       <?php checked(get_post_meta($post->ID, '_almaseo_robots_index', true) === 'noindex'); ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-text">NoIndex</span>
                            </label>
                            <p class="option-description">Hide this page from search results</p>
                        </div>
                        
                        <!-- Follow/NoFollow Pair (Mutually Exclusive) -->
                        <div class="meta-robots-option">
                            <label class="toggle-label">
                                <input type="checkbox" 
                                       id="almaseo_robots_follow" 
                                       name="almaseo_robots_follow" 
                                       value="1" 
                                       <?php checked(get_post_meta($post->ID, '_almaseo_robots_follow', true) !== 'nofollow'); ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-text">Follow</span>
                            </label>
                            <p class="option-description">Allow search engines to follow links on this page</p>
                        </div>
                        
                        <div class="meta-robots-option">
                            <label class="toggle-label">
                                <input type="checkbox" 
                                       id="almaseo_robots_nofollow" 
                                       name="almaseo_robots_nofollow" 
                                       value="1" 
                                       <?php checked(get_post_meta($post->ID, '_almaseo_robots_follow', true) === 'nofollow'); ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-text">NoFollow</span>
                            </label>
                            <p class="option-description">Tell search engines not to follow links</p>
                        </div>
                        
                        <div class="meta-robots-option">
                            <label class="toggle-label">
                                <input type="checkbox" 
                                       id="almaseo_robots_archive" 
                                       name="almaseo_robots_archive" 
                                       value="1" 
                                       <?php checked(get_post_meta($post->ID, '_almaseo_robots_archive', true) !== 'noarchive'); ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-text">Archive</span>
                            </label>
                            <p class="option-description">Allow search engines to show cached versions</p>
                        </div>
                        
                        <div class="meta-robots-option">
                            <label class="toggle-label">
                                <input type="checkbox" 
                                       id="almaseo_robots_snippet" 
                                       name="almaseo_robots_snippet" 
                                       value="1" 
                                       <?php checked(get_post_meta($post->ID, '_almaseo_robots_snippet', true) !== 'nosnippet'); ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-text">Snippet</span>
                            </label>
                            <p class="option-description">Allow search engines to show text snippets</p>
                        </div>
                        
                        <div class="meta-robots-option">
                            <label class="toggle-label">
                                <input type="checkbox" 
                                       id="almaseo_robots_imageindex" 
                                       name="almaseo_robots_imageindex" 
                                       value="1" 
                                       <?php checked(get_post_meta($post->ID, '_almaseo_robots_imageindex', true) !== 'noimageindex'); ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-text">Image Index</span>
                            </label>
                            <p class="option-description">Allow images to appear in search results</p>
                        </div>
                        
                        <div class="meta-robots-option">
                            <label class="toggle-label">
                                <input type="checkbox" 
                                       id="almaseo_robots_translate" 
                                       name="almaseo_robots_translate" 
                                       value="1" 
                                       <?php checked(get_post_meta($post->ID, '_almaseo_robots_translate', true) !== 'notranslate'); ?>>
                                <span class="toggle-slider"></span>
                                <span class="toggle-text">Translate</span>
                            </label>
                            <p class="option-description">Allow translation services to translate this page</p>
                        </div>
                    </div>
                    
                    <div class="meta-robots-preview">
                        <h4>Preview:</h4>
                        <code id="meta-robots-preview-code">&lt;meta name="robots" content="index, follow" /&gt;</code>
                    </div>
                </div>
            </div>
            
            <!-- Canonical URL Card -->
            <div class="almaseo-card">
                <div class="almaseo-card-header">
                    <h3 class="almaseo-card-title">
                        <span class="card-icon">🔗</span>
                        Canonical URL
                    </h3>
                    <p class="almaseo-card-description">Specify the preferred version of this page to prevent duplicate content issues</p>
                </div>
                <div class="almaseo-card-body">
                    <div class="almaseo-field-group">
                        <label for="almaseo_canonical_url">Canonical URL</label>
                        <input type="url" 
                               id="almaseo_canonical_url" 
                               name="almaseo_canonical_url" 
                               value="<?php echo esc_attr(get_post_meta($post->ID, '_almaseo_canonical_url', true) ?: get_permalink($post->ID)); ?>" 
                               placeholder="<?php echo esc_attr(get_permalink($post->ID)); ?>"
                               class="almaseo-input">
                        <p class="field-hint">Leave empty to use the default permalink. Only change if this content exists at another URL.</p>
                    </div>
                </div>
            </div>
            
            <!-- Schema Markup Card -->
            <div class="almaseo-card">
                <div class="almaseo-card-header">
                    <h3 class="almaseo-card-title">
                        <span class="card-icon">📋</span>
                        Schema Markup
                    </h3>
                    <p class="almaseo-card-description">Add structured data to help search engines understand your content</p>
                </div>
                <div class="almaseo-card-body">
                    <div class="almaseo-field-group">
                        <label for="almaseo_schema_type">Schema Type</label>
                        <select id="almaseo_schema_type" 
                                name="almaseo_schema_type" 
                                class="almaseo-select">
                            <?php 
                            $current_schema = get_post_meta($post->ID, '_almaseo_schema_type', true) ?: 'Article';
                            ?>
                            <?php 
                            $is_connected = seo_playground_is_alma_connected();
                            $schema_options = array(
                                array('value' => 'Article', 'label' => 'Article (BlogPosting) (Free)', 'locked' => false),
                                array('value' => 'FAQPage', 'label' => 'FAQPage', 'locked' => !$is_connected),
                                array('value' => 'HowTo', 'label' => 'HowTo', 'locked' => !$is_connected),
                                array('value' => 'LocalBusiness', 'label' => 'LocalBusiness', 'locked' => !$is_connected)
                            );
                            
                            foreach ($schema_options as $option): ?>
                                <option value="<?php echo esc_attr($option['value']); ?>" 
                                        <?php selected($current_schema, $option['value']); ?>
                                        <?php echo $option['locked'] ? 'disabled aria-disabled="true" class="is-locked"' : ''; ?>
                                        data-locked="<?php echo $option['locked'] ? '1' : '0'; ?>">
                                    <?php echo esc_html($option['label']); ?><?php echo $option['locked'] ? ' 🔒' : ''; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        
                        <!-- Smart fallback hints for Article schema -->
                        <?php if ($current_schema === 'Article'): ?>
                        <div class="schema-fallback-hint" style="margin-top: 10px; padding: 10px; background: #f0f6fc; border-left: 3px solid #2271b1; border-radius: 3px;">
                            <small style="color: #2c3338;">
                                <strong>Auto-fill enabled:</strong> Headline and description will auto-fill from your SEO title and description if left blank. 
                                The image will auto-use your OG image or featured image.
                            </small>
                        </div>
                        <?php endif; ?>
                        
                        <!-- Locked schema upsell notice -->
                        <div id="schema-locked-notice" style="display: none; margin-top: 10px; padding: 10px; background: #fff8e5; border-left: 3px solid #dba617; border-radius: 3px;">
                            <small style="color: #2c3338;">
                                Advanced schema types are available with an AlmaSEO connection. 
                                <a href="#tab-unlock-features" class="almaseo-tab-link">Connect now →</a>
                            </small>
                        </div>
                    </div>
                    
                    <!-- Collapsible Schema Preview -->
                    <div class="almaseo-collapsible" style="margin-top: 20px;">
                        <button type="button" class="almaseo-collapsible-toggle" data-target="schema-jsonld-preview" style="width: 100%; text-align: left; padding: 10px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 3px; cursor: pointer;">
                            <span class="dashicons dashicons-arrow-down-alt2" style="margin-right: 5px;"></span>
                            Preview: Article JSON-LD
                        </button>
                        <div id="schema-jsonld-preview" class="almaseo-collapsible-content" style="display: none; margin-top: 10px; padding: 15px; background: #2c3338; border-radius: 3px;">
                            <div style="position: relative;">
                                <button type="button" class="copy-json-btn" style="position: absolute; top: 5px; right: 5px; padding: 5px 10px; background: #2271b1; color: white; border: none; border-radius: 3px; cursor: pointer; font-size: 12px;">Copy JSON</button>
                                <pre id="schema-json-preview" style="color: #50fa7b; font-family: 'Courier New', monospace; font-size: 12px; overflow-x: auto; margin: 0;">Loading preview...</pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Social Metadata Card -->
            <div class="almaseo-card">
                <div class="almaseo-card-header">
                    <h3 class="almaseo-card-title">
                        <span class="card-icon">📱</span>
                        Social Metadata
                    </h3>
                    <p class="almaseo-card-description">Control how your content appears when shared on social networks</p>
                </div>
                <div class="almaseo-card-body">
                    <!-- Open Graph Section -->
                    <div class="social-section">
                        <h4 class="social-section-title">
                            <span style="display: inline-flex; align-items: center; gap: 8px;">
                                <svg style="width: 20px; height: 20px; fill: #1877f2;" viewBox="0 0 24 24">
                                    <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                                </svg>
                                <svg style="width: 20px; height: 20px; fill: #0077b5;" viewBox="0 0 24 24">
                                    <path d="M20.447 20.452h-3.554v-5.569c0-1.328-.027-3.037-1.852-3.037-1.853 0-2.136 1.445-2.136 2.939v5.667H9.351V9h3.414v1.561h.046c.477-.9 1.637-1.85 3.37-1.85 3.601 0 4.267 2.37 4.267 5.455v6.286zM5.337 7.433c-1.144 0-2.063-.926-2.063-2.065 0-1.138.92-2.063 2.063-2.063 1.14 0 2.064.925 2.064 2.063 0 1.139-.925 2.065-2.064 2.065zm1.782 13.019H3.555V9h3.564v11.452zM22.225 0H1.771C.792 0 0 .774 0 1.729v20.542C0 23.227.792 24 1.771 24h20.451C23.2 24 24 23.227 24 22.271V1.729C24 .774 23.2 0 22.222 0h.003z"/>
                                </svg>
                                Open Graph
                            </span>
                        </h4>
                        
                        <div class="almaseo-field-group">
                            <label for="almaseo_og_title">OG Title</label>
                            <input type="text" 
                                   id="almaseo_og_title" 
                                   name="almaseo_og_title" 
                                   value="<?php echo esc_attr(get_post_meta($post->ID, '_almaseo_og_title', true)); ?>" 
                                   placeholder="<?php echo esc_attr($seo_title ?: get_the_title($post->ID)); ?>"
                                   class="almaseo-input">
                            <p class="field-hint">Leave empty to use SEO title</p>
                        </div>
                        
                        <div class="almaseo-field-group">
                            <label for="almaseo_og_description">OG Description</label>
                            <textarea id="almaseo_og_description" 
                                      name="almaseo_og_description" 
                                      placeholder="<?php echo esc_attr($seo_description ?: wp_trim_words($post->post_content, 30)); ?>"
                                      class="almaseo-textarea"><?php echo esc_textarea(get_post_meta($post->ID, '_almaseo_og_description', true)); ?></textarea>
                            <p class="field-hint">Leave empty to use meta description</p>
                        </div>
                        
                        <div class="almaseo-field-group">
                            <label for="almaseo_og_image">OG Image URL</label>
                            <input type="url" 
                                   id="almaseo_og_image" 
                                   name="almaseo_og_image" 
                                   value="<?php echo esc_attr(get_post_meta($post->ID, '_almaseo_og_image', true)); ?>" 
                                   placeholder="<?php echo esc_attr(get_the_post_thumbnail_url($post->ID, 'large')); ?>"
                                   class="almaseo-input">
                            <p class="field-hint">Recommended: 1200x630px. Leave empty to use the featured image.</p>
                        </div>
                    </div>
                    
                    <!-- Twitter Section -->
                    <div class="social-section">
                        <h4 class="social-section-title">
                            <span style="display: inline-flex; align-items: center; gap: 8px;">
                                <svg style="width: 20px; height: 20px;" viewBox="0 0 24 24">
                                    <path fill="#000" d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                                </svg>
                                Twitter Card
                            </span>
                        </h4>
                        
                        <div class="almaseo-field-group">
                            <label for="almaseo_twitter_card">Card Type</label>
                            <select id="almaseo_twitter_card" 
                                    name="almaseo_twitter_card" 
                                    class="almaseo-select"
                                    data-has-featured-image="<?php echo has_post_thumbnail($post->ID) ? 'true' : 'false'; ?>">
                                <?php 
                                $twitter_card = get_post_meta($post->ID, '_almaseo_twitter_card', true) ?: 'summary_large_image';
                                ?>
                                <option value="summary" <?php selected($twitter_card, 'summary'); ?>>Summary</option>
                                <option value="summary_large_image" <?php selected($twitter_card, 'summary_large_image'); ?>>Summary with Large Image</option>
                            </select>
                        </div>
                        
                        <div class="almaseo-field-group">
                            <label for="almaseo_twitter_title">Twitter Title</label>
                            <input type="text" 
                                   id="almaseo_twitter_title" 
                                   name="almaseo_twitter_title" 
                                   value="<?php echo esc_attr(get_post_meta($post->ID, '_almaseo_twitter_title', true)); ?>" 
                                   placeholder="<?php echo esc_attr($seo_title ?: get_the_title($post->ID)); ?>"
                                   class="almaseo-input">
                            <p class="field-hint">Leave empty to use OG title or SEO title</p>
                        </div>
                        
                        <div class="almaseo-field-group">
                            <label for="almaseo_twitter_description">Twitter Description</label>
                            <textarea id="almaseo_twitter_description" 
                                      name="almaseo_twitter_description" 
                                      placeholder="<?php echo esc_attr($seo_description ?: wp_trim_words($post->post_content, 30)); ?>"
                                      class="almaseo-textarea"><?php echo esc_textarea(get_post_meta($post->ID, '_almaseo_twitter_description', true)); ?></textarea>
                            <p class="field-hint">Leave empty to use OG description or meta description</p>
                        </div>
                    </div>
                    
                    <!-- Collapsible Social Preview -->
                    <div class="almaseo-collapsible" style="margin-top: 20px;">
                        <button type="button" class="almaseo-collapsible-toggle" data-target="social-preview-content" style="width: 100%; text-align: left; padding: 10px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 3px; cursor: pointer;">
                            <span class="dashicons dashicons-arrow-down-alt2" style="margin-right: 5px;"></span>
                            Preview: Social Tags
                        </button>
                        <div id="social-preview-content" class="almaseo-collapsible-content" style="display: none; margin-top: 10px; padding: 15px; background: #f6f7f7; border: 1px solid #c3c4c7; border-radius: 3px;">
                            <div id="social-tags-preview" style="font-family: 'Courier New', monospace; font-size: 13px; line-height: 1.6;">
                                <div style="color: #646970;">Loading preview...</div>
                            </div>
                            <p style="margin-top: 10px; font-size: 12px; color: #646970; font-style: italic;">Note: Previews are approximate.</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Historical Metadata Tracker -->
            <div class="almaseo-metadata-history">
                <div class="history-header">
                    <h3>📜 Metadata History</h3>
                    <button type="button" class="history-toggle-btn" id="toggle-metadata-history">
                        <span class="toggle-icon">▼</span>
                        <span class="toggle-text">Show History</span>
                    </button>
                </div>
                
                <div class="metadata-history-content" id="metadata-history-content" style="display: none;">
                    <?php
                    // Get historical metadata
                    $metadata_history = get_post_meta($post->ID, '_almaseo_metadata_history', true);
                    if (!is_array($metadata_history)) {
                        $metadata_history = [];
                    }
                    
                    // Sort by timestamp (newest first)
                    usort($metadata_history, function($a, $b) {
                        return $b['timestamp'] - $a['timestamp'];
                    });
                    
                    if (empty($metadata_history)) {
                        echo '<p class="no-history">No metadata history available yet. Changes will be tracked when you update SEO fields.</p>';
                    } else {
                        echo '<div class="history-timeline">';
                        foreach ($metadata_history as $index => $entry) {
                            $date = date('F j, Y g:i A', $entry['timestamp']);
                            ?>
                            <div class="history-entry" data-index="<?php echo $index; ?>">
                                <div class="history-date">
                                    <span class="date-icon">📅</span>
                                    <?php echo esc_html($date); ?>
                                </div>
                                
                                <div class="history-fields">
                                    <?php if (!empty($entry['title'])): ?>
                                    <div class="history-field">
                                        <strong>Title:</strong> 
                                        <span class="field-value"><?php echo esc_html($entry['title']); ?></span>
                                        <button type="button" 
                                                class="restore-btn" 
                                                data-field="title" 
                                                data-value="<?php echo esc_attr($entry['title']); ?>"
                                                title="Restore this title">
                                            ↩️
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($entry['description'])): ?>
                                    <div class="history-field">
                                        <strong>Description:</strong> 
                                        <span class="field-value"><?php echo esc_html($entry['description']); ?></span>
                                        <button type="button" 
                                                class="restore-btn" 
                                                data-field="description" 
                                                data-value="<?php echo esc_attr($entry['description']); ?>"
                                                title="Restore this description">
                                            ↩️
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($entry['keyword'])): ?>
                                    <div class="history-field">
                                        <strong>Focus Keyword:</strong> 
                                        <span class="field-value"><?php echo esc_html($entry['keyword']); ?></span>
                                        <button type="button" 
                                                class="restore-btn" 
                                                data-field="keyword" 
                                                data-value="<?php echo esc_attr($entry['keyword']); ?>"
                                                title="Restore this keyword">
                                            ↩️
                                        </button>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (!empty($entry['user'])): ?>
                                    <div class="history-meta">
                                        <small>Changed by: <?php echo esc_html($entry['user']); ?></small>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="history-actions">
                                    <button type="button" 
                                            class="restore-all-btn" 
                                            data-index="<?php echo $index; ?>"
                                            title="Restore all fields from this version">
                                        Restore All
                                    </button>
                                </div>
                            </div>
                            <?php
                        }
                        echo '</div>';
                        
                        // Limit history to last 20 entries to prevent bloat
                        if (count($metadata_history) > 20) {
                            $metadata_history = array_slice($metadata_history, 0, 20);
                            update_post_meta($post->ID, '_almaseo_metadata_history', $metadata_history);
                        }
                    }
                    ?>
                </div>
            </div>
            
            <!-- Schema Type Selector -->
            <div class="almaseo-field-group">
                <label for="almaseo_schema_type">Schema Type</label>
                <select id="almaseo_schema_type" name="almaseo_schema_type" class="almaseo-select">
                    <option value="">None</option>
                    <option value="Article" <?php selected($seo_schema_type, 'Article'); ?>>Article</option>
                    <option value="BlogPosting" <?php selected($seo_schema_type, 'BlogPosting'); ?>>Blog Post</option>
                    <option value="NewsArticle" <?php selected($seo_schema_type, 'NewsArticle'); ?>>News Article</option>
                    <option value="Product" <?php selected($seo_schema_type, 'Product'); ?>>Product</option>
                    <option value="Recipe" <?php selected($seo_schema_type, 'Recipe'); ?>>Recipe</option>
                    <option value="Review" <?php selected($seo_schema_type, 'Review'); ?>>Review</option>
                    <option value="FAQPage" <?php selected($seo_schema_type, 'FAQPage'); ?>>FAQ Page</option>
                    <option value="HowTo" <?php selected($seo_schema_type, 'HowTo'); ?>>How-To Guide</option>
                </select>
            </div>
            
            <!-- Troubleshooting Schema Issues Help Section -->
            <div class="almaseo-card" style="margin-top: 20px; background: #f8f9fa; border: 1px solid #e0e0e0;">
                <div class="almaseo-card-header" style="background: linear-gradient(135deg, #fff8e5 0%, #fffef5 100%); border-bottom: 1px solid #f0f0f1;">
                    <h3 class="almaseo-card-title">
                        <span class="card-icon">❓</span>
                        Troubleshooting Schema Issues
                    </h3>
                </div>
                <div class="almaseo-card-body">
                    <!-- Schema Markup Output Section -->
                    <div style="margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid #e0e0e0;">
                        <h4 style="font-size: 15px; margin-bottom: 12px; color: #1d2327; font-weight: 600;">📋 Schema Markup Output</h4>
                        
                        <div style="margin-bottom: 15px;">
                            <h5 style="font-size: 13px; margin-bottom: 8px; color: #2c3338; font-weight: 600;">Missing or Empty Schema Block</h5>
                            <p style="font-size: 13px; color: #646970; margin-bottom: 8px;">
                                If you enable Exclusive Schema Mode, AlmaSEO will suppress ALL schema markup (including its own) to prevent duplicate JSON-LD from appearing. 
                                In this mode you may still see debug comments such as:
                            </p>
                            <ul style="font-size: 12px; color: #8c8f94; margin-left: 20px; font-family: monospace;">
                                <li>&lt;!-- AlmaSEO Generator: START --&gt;</li>
                                <li>&lt;!-- AlmaSEO Generator: OK --&gt;</li>
                            </ul>
                            <p style="font-size: 13px; color: #646970; margin-top: 8px;">
                                But you will NOT see a <code style="background: #f0f0f1; padding: 2px 4px; border-radius: 2px; font-size: 12px;">&lt;script type="application/ld+json"&gt;</code> block. 
                                This is expected behavior when Exclusive Mode is enabled.
                            </p>
                        </div>
                        
                        <div>
                            <h5 style="font-size: 13px; margin-bottom: 8px; color: #2c3338; font-weight: 600;">"Missing Field" Warnings in Rich Results Test</h5>
                            <p style="font-size: 13px; color: #646970; margin-bottom: 8px;">
                                Sometimes Google's Rich Results tool shows "missing field" warnings. These may come from:
                            </p>
                            <ul style="font-size: 13px; color: #646970; margin-left: 20px;">
                                <li>Other active SEO/Schema plugins</li>
                                <li>Your theme's built-in structured data</li>
                                <li>Third-party scripts (reviews, events, etc.)</li>
                            </ul>
                            <p style="font-size: 13px; color: #646970; margin-top: 8px;">
                                AlmaSEO does not generate these warnings itself. If Exclusive Mode is OFF, check for overlapping plugins 
                                (e.g., Yoast, AIOSEO, RankMath). If Exclusive Mode is ON, AlmaSEO's schema is completely removed, 
                                so any remaining warnings originate elsewhere.
                            </p>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <h4 style="font-size: 14px; margin-bottom: 8px; color: #1d2327;">Duplicate Schema Warnings</h4>
                        <p style="font-size: 13px; color: #646970; margin-bottom: 10px;">
                            Duplicate schema markup can come from multiple sources:
                        </p>
                        <ul style="font-size: 13px; color: #646970; margin-left: 20px;">
                            <li>Your WordPress theme may include built-in schema</li>
                            <li>WordPress core adds basic schema on certain page types</li>
                            <li>Other SEO plugins may output competing schema markup</li>
                        </ul>
                        <div style="padding: 8px; background: #fff3cd; border-left: 3px solid #ffc107; margin-top: 10px; border-radius: 3px;">
                            <p style="font-size: 13px; color: #856404; margin: 0;">
                                <strong>⚠️ Important:</strong> Deactivated plugins can STILL cause schema conflicts. 
                                Other SEO plugins must be completely removed/deleted, not just deactivated, to fully eliminate conflicts.
                            </p>
                        </div>
                    </div>
                    
                    <div style="margin-bottom: 15px;">
                        <h4 style="font-size: 14px; margin-bottom: 8px; color: #1d2327;">"Missing Field" Warnings</h4>
                        <p style="font-size: 13px; color: #646970;">
                            Non-critical "missing field" warnings in validation tools are often caused by schema from external sources 
                            (themes, plugins, including deactivated but not removed SEO plugins). 
                            These are typically NOT errors in AlmaSEO's schema output.
                        </p>
                    </div>
                    
                    <div style="margin-bottom: 15px; padding: 12px; background: #e8f5e9; border-radius: 4px;">
                        <h4 style="font-size: 14px; margin-bottom: 8px; color: #1d2327;">
                            ✅ Solution: Exclusive Schema Mode
                        </h4>
                        <p style="font-size: 13px; color: #646970; margin-bottom: 10px;">
                            AlmaSEO's Exclusive Schema Mode helps prevent these issues by removing competing schema markup from other plugins and themes, 
                            ensuring only AlmaSEO's complete, validated schema appears.
                        </p>
                        <?php 
                        // Get exclusive mode setting
                        $exclusive_mode = get_option('almaseo_exclusive_schema_enabled', false);
                        if (!$exclusive_mode): 
                        ?>
                        <p style="font-size: 13px; color: #667eea; font-weight: 500;">
                            → Enable Exclusive Schema Mode in the <a href="<?php echo admin_url('admin.php?page=almaseo-settings'); ?>">Settings</a>
                        </p>
                        <?php else: ?>
                        <p style="font-size: 13px; color: #00a32a; font-weight: 500;">
                            ✓ Exclusive Schema Mode is currently ENABLED
                        </p>
                        <?php endif; ?>
                    </div>
                    
                    <div style="padding: 12px; background: #f0f8ff; border-radius: 4px;">
                        <h4 style="font-size: 14px; margin-bottom: 8px; color: #1d2327;">🔍 Verification Tools</h4>
                        <p style="font-size: 13px; color: #646970; margin-bottom: 8px;">
                            Always verify your schema output using:
                        </p>
                        <ul style="font-size: 13px; margin-left: 20px;">
                            <li><a href="https://search.google.com/test/rich-results" target="_blank" style="color: #2271b1;">Google's Rich Results Test</a> - Test how Google sees your schema</li>
                            <li><a href="https://validator.schema.org/" target="_blank" style="color: #2271b1;">Schema.org Validator</a> - Validate schema syntax</li>
                            <li>View page source and search for <code style="background: #f0f0f1; padding: 2px 4px; border-radius: 2px;">&lt;script type="application/ld+json"</code></li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <!-- End Schema & Meta Tab -->
        
        <!-- AI Tools Tab -->
        <div class="almaseo-tab-panel" id="tab-ai-tools">
            <?php if (!$is_connected || $user_tier === 'free'): ?>
            <!-- Upsell Screen for Unconnected/Free Users -->
            <div class="almaseo-ai-upsell-screen">
                <div class="upsell-hero">
                    <h2>🚀 Unlock AI-Powered SEO Tools</h2>
                    <p class="upsell-subtitle">
                        <?php if (!$is_connected): ?>
                        Connect to AlmaSEO to access powerful AI features that will transform your content optimization workflow.
                        <?php else: ?>
                        You're on the <strong>Free tier</strong>. Upgrade to Pro or Max to unlock AI-powered features that will transform your content optimization.
                        <?php endif; ?>
                    </p>
                </div>
                
                <div class="locked-features-grid">
                    <div class="locked-feature">
                        <div class="feature-icon">🔒</div>
                        <h3>✏️ AI Rewrite</h3>
                        <p>Instantly rewrite and improve your content with AI that understands SEO best practices.</p>
                        <span class="feature-badge">Premium Feature</span>
                    </div>
                    
                    <div class="locked-feature">
                        <div class="feature-icon">🔒</div>
                        <h3>💡 AI Title Generator</h3>
                        <p>Generate compelling, SEO-optimized titles that drive clicks and rankings.</p>
                        <span class="feature-badge">Premium Feature</span>
                    </div>
                    
                    <div class="locked-feature">
                        <div class="feature-icon">🔒</div>
                        <h3>📝 AI Description Generator</h3>
                        <p>Create perfect meta descriptions that improve CTR and search visibility.</p>
                        <span class="feature-badge">Premium Feature</span>
                    </div>
                    
                    <div class="locked-feature">
                        <div class="feature-icon">🔒</div>
                        <h3>🎯 Smart Schema Suggestions</h3>
                        <p>Get AI-powered schema markup recommendations based on your content.</p>
                        <span class="feature-badge">Premium Feature</span>
                    </div>
                    
                    <div class="locked-feature">
                        <div class="feature-icon">🔒</div>
                        <h3>🔍 Keyword Boost</h3>
                        <p>Advanced keyword research with competition analysis and search intent detection.</p>
                        <span class="feature-badge">Premium Feature</span>
                    </div>
                    
                    <div class="locked-feature">
                        <div class="feature-icon">🔒</div>
                        <h3>📊 Content Intelligence</h3>
                        <p>Deep content analysis with readability scores and optimization suggestions.</p>
                        <span class="feature-badge">Premium Feature</span>
                    </div>
                </div>
                
                <div class="upsell-benefits">
                    <h3>✨ Why Connect to AlmaSEO?</h3>
                    <ul class="benefits-list">
                        <li>✅ <strong>Free Trial Available</strong> - Try all features risk-free</li>
                        <li>✅ <strong>Instant Setup</strong> - Connect in less than 60 seconds</li>
                        <li>✅ <strong>No Credit Card Required</strong> - Start optimizing immediately</li>
                        <li>✅ <strong>Unlimited AI Generations</strong> - No usage limits during trial</li>
                        <li>✅ <strong>Priority Support</strong> - Get help when you need it</li>
                    </ul>
                </div>
                
                <div class="upsell-cta-section">
                    <div class="cta-buttons">
                        <a href="<?php echo admin_url('admin.php?page=seo-playground-connection'); ?>" class="cta-btn-primary">
                            🔓 Connect to AlmaSEO (Free Trial)
                        </a>
                        <a href="https://almaseo.com/pricing?utm_source=plugin&utm_medium=ai_tools&utm_campaign=upsell" target="_blank" class="cta-btn-secondary">
                            💡 Learn More About Pricing
                        </a>
                    </div>
                    
                    <div class="already-connected">
                        <p>🧠 Already connected? <a href="<?php echo admin_url('admin.php?page=seo-playground-connection'); ?>">Check your connection status</a></p>
                    </div>
                </div>
                
                <div class="upsell-testimonial">
                    <blockquote>
                        "AlmaSEO's AI tools saved me hours of work. The content suggestions are spot-on and my rankings have improved significantly!"
                        <cite>- Sarah M., Content Manager</cite>
                    </blockquote>
                </div>
            </div>
            <?php elseif ($can_use_ai): ?>
            <!-- AI Rewrite Assistant Panel for Pro/Max Users -->
            <?php if ($user_tier === 'pro'): ?>
            <div class="ai-usage-indicator">
                <span class="usage-label">AI Generations Remaining:</span>
                <span class="usage-count"><?php echo $generations_info['remaining']; ?>/<?php echo $generations_info['total']; ?></span>
                <div class="usage-bar">
                    <div class="usage-progress" style="width: <?php echo ($generations_info['remaining'] / $generations_info['total']) * 100; ?>%"></div>
                </div>
            </div>
            <?php endif; ?>
            
            <div class="ai-rewrite-panel">
                <div class="almaseo-field-group" role="region" aria-labelledby="ai-rewrite-heading">
                    <div class="panel-header">
                        <div class="panel-title" id="ai-rewrite-heading">
                            <span class="panel-icon" aria-hidden="true">✍️</span>
                            <span>AI Rewrite Assistant</span>
                            <span class="panel-tooltip" role="tooltip" aria-label="Rewrite your content with AI for better engagement">ⓘ</span>
                        </div>
                    </div>
                    
                    <div class="ai-rewrite-input-section">
                        <textarea id="ai-rewrite-input" 
                                  class="ai-rewrite-textarea" 
                                  placeholder="Paste or type content you want to rewrite..."
                                  aria-label="Content to rewrite"
                                  rows="5"></textarea>
                        
                        <div class="ai-rewrite-controls">
                            <select id="ai-rewrite-tone" class="ai-rewrite-tone-select" aria-label="Select rewrite tone">
                                <option value="professional">Professional</option>
                                <option value="casual">Casual</option>
                                <option value="friendly">Friendly</option>
                                <option value="formal">Formal</option>
                                <option value="creative">Creative</option>
                            </select>
                            
                            <button type="button" class="ai-rewrite-btn" id="ai-rewrite-submit" aria-label="Rewrite content">
                                <span class="btn-icon" aria-hidden="true">🔄</span>
                                <span>Rewrite with AI</span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="ai-rewrite-output-section" id="ai-rewrite-output-section">
                        <div class="ai-rewrite-output-label">
                            <span aria-hidden="true">✨</span>
                            <span>Rewritten Content</span>
                        </div>
                        <div class="ai-rewrite-output">
                            <div class="ai-rewrite-output-text" id="ai-rewrite-output-text" aria-live="polite"></div>
                        </div>
                        <div class="ai-rewrite-output-actions">
                            <button type="button" class="ai-output-action-btn" id="ai-copy-rewrite" aria-label="Copy rewritten content">
                                <span aria-hidden="true">📋</span> Copy
                            </button>
                            <button type="button" class="ai-output-action-btn primary" id="ai-apply-rewrite" aria-label="Apply rewritten content">
                                <span aria-hidden="true">✔️</span> Apply
                            </button>
                            <button type="button" class="ai-output-action-btn" id="ai-regenerate-rewrite" aria-label="Regenerate content">
                                <span aria-hidden="true">🔄</span> Regenerate
                            </button>
                            <button type="button" class="ai-output-action-btn" id="ai-clear-rewrite" aria-label="Clear results">
                                <span aria-hidden="true">❌</span> Clear
                            </button>
                        </div>
                    </div>
                    
                    <div class="ai-rewrite-loading" style="display: none;" aria-hidden="true">
                        <div class="ai-rewrite-spinner"></div>
                        <div class="ai-rewrite-loading-text">Rewriting your content...</div>
                    </div>
                    
                    <div class="ai-rewrite-error" style="display: none;" role="alert">
                        <span class="ai-rewrite-error-icon" aria-hidden="true">⚠️</span>
                        <span class="ai-rewrite-error-text"></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Title Generator Panel -->
            <?php if ($is_connected): ?>
            <div class="ai-title-generator-panel">
                <div class="almaseo-field-group" role="region" aria-labelledby="ai-title-heading">
                    <div class="panel-header">
                        <div class="panel-title" id="ai-title-heading">
                            <span class="panel-icon" aria-hidden="true">📝</span>
                            <span>AI Title Generator</span>
                            <span class="panel-tooltip" role="tooltip" aria-label="Generate compelling SEO titles">ⓘ</span>
                        </div>
                    </div>
                    
                    <div class="ai-title-input-section">
                        <input type="text" 
                               id="ai-title-context" 
                               class="ai-title-context-input" 
                               placeholder="Enter topic or keywords (optional - will use post content if empty)"
                               aria-label="Title generation context">
                        
                        <button type="button" class="ai-title-generate-btn" id="ai-generate-titles" aria-label="Generate title suggestions">
                            <span aria-hidden="true">✨</span>
                            <span>Generate Titles</span>
                        </button>
                    </div>
                    
                    <div class="ai-title-suggestions" id="ai-title-suggestions">
                        <div class="ai-title-suggestions-label">Generated Titles</div>
                        <div class="ai-title-suggestions-list" id="ai-title-suggestions-list" role="list">
                            <!-- Title suggestions will be populated here -->
                        </div>
                    </div>
                    
                    <div class="ai-title-loading" style="display: none;" aria-hidden="true">
                        <div class="ai-rewrite-spinner"></div>
                        <div class="ai-rewrite-loading-text">Generating title suggestions...</div>
                    </div>
                    
                    <div class="ai-title-error" style="display: none;" role="alert">
                        <span aria-hidden="true">⚠️</span>
                        <span class="error-text"></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Meta Description Generator Panel -->
            <?php if ($is_connected): ?>
            <div class="ai-meta-generator-panel">
                <div class="almaseo-field-group" role="region" aria-labelledby="ai-meta-heading">
                    <div class="panel-header">
                        <div class="panel-title" id="ai-meta-heading">
                            <span class="panel-icon" aria-hidden="true">📄</span>
                            <span>AI Meta Description Generator</span>
                            <span class="panel-tooltip" role="tooltip" aria-label="Generate optimized meta descriptions">ⓘ</span>
                        </div>
                    </div>
                    
                    <div class="ai-meta-input-section">
                        <input type="text" 
                               id="ai-meta-keywords" 
                               class="ai-meta-keywords-input" 
                               placeholder="Target keywords (optional)"
                               aria-label="Target keywords for meta description">
                        
                        <button type="button" class="ai-meta-generate-btn" id="ai-generate-meta" aria-label="Generate meta description">
                            <span aria-hidden="true">✨</span>
                            <span>Generate Meta Description</span>
                        </button>
                    </div>
                    
                    <div class="ai-meta-output" id="ai-meta-output">
                        <div class="ai-meta-output-text" id="ai-meta-output-text" aria-live="polite"></div>
                        <div class="ai-meta-char-count">
                            <span class="ai-meta-char-indicator"></span>
                            <span>Characters: <span id="ai-meta-char-count">0</span>/160</span>
                        </div>
                        <button type="button" class="ai-output-action-btn primary" id="ai-apply-meta" aria-label="Apply meta description">
                            <span aria-hidden="true">✔️</span> Apply to Meta Description
                        </button>
                    </div>
                    
                    <div class="ai-meta-loading" style="display: none;" aria-hidden="true">
                        <div class="ai-rewrite-spinner"></div>
                        <div class="ai-rewrite-loading-text">Generating meta description...</div>
                    </div>
                    
                    <div class="ai-meta-error" style="display: none;" role="alert">
                        <span aria-hidden="true">⚠️</span>
                        <span class="error-text"></span>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- AI Suggestions Panel -->
            <?php if ($is_connected): ?>
            <div class="ai-suggestions-panel">
                <div class="almaseo-field-group" role="region" aria-labelledby="ai-suggestions-heading">
                    <div class="panel-header">
                        <div class="panel-title" id="ai-suggestions-heading">
                            <span class="panel-icon" aria-hidden="true">💡</span>
                            <span>AI SEO Suggestions</span>
                            <span class="panel-tooltip" role="tooltip" aria-label="Get AI-powered SEO recommendations">ⓘ</span>
                        </div>
                        <button type="button" class="ai-refresh-btn" id="ai-refresh-suggestions" aria-label="Refresh suggestions">
                            <span aria-hidden="true">🔄</span> Refresh
                        </button>
                    </div>
                    
                    <div class="ai-suggestions-grid" id="ai-suggestions-grid" role="list">
                        <!-- AI suggestions will be populated here -->
                    </div>
                    
                    <div class="ai-suggestions-loading" style="display: none;" aria-hidden="true">
                        <div class="ai-rewrite-spinner"></div>
                        <div class="ai-rewrite-loading-text">Analyzing your content...</div>
                    </div>
                    
                    <div class="ai-empty-state" id="ai-suggestions-empty" style="display: none;">
                        <div class="ai-empty-icon" aria-hidden="true">🤖</div>
                        <div class="ai-empty-title">No Suggestions Yet</div>
                        <div class="ai-empty-description">Add more content to get AI-powered SEO suggestions</div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Usage Indicator -->
            <div class="ai-usage-indicator" role="status" aria-label="Content usage statistics">
                <div class="ai-word-count">
                    <span aria-hidden="true">📊</span>
                    Words: <span class="ai-word-count-value">0</span>
                </div>
                <div class="ai-token-usage">
                    <span aria-hidden="true">🎯</span>
                    Tokens: <span class="ai-token-count-value">0</span>
                    <div class="ai-usage-bar">
                        <div class="ai-usage-bar-fill" style="width: 0%"></div>
                    </div>
                </div>
            </div>
        </div>
        <!-- End AI Tools Tab -->
        
        <!-- Notes & History Tab -->
        <div class="almaseo-tab-panel" id="tab-notes-history">
            <!-- Search & Filter Bar -->
            <div class="notes-toolbar">
                <div class="notes-search-container">
                    <input type="text" 
                           id="notes-search-input" 
                           class="notes-search-input" 
                           placeholder="Search notes..."
                           aria-label="Search notes">
                    <span class="notes-search-icon" aria-hidden="true">🔍</span>
                </div>
                <div class="notes-sort-container">
                    <select id="notes-sort-select" class="notes-sort-select" aria-label="Sort notes">
                        <option value="newest">Newest First</option>
                        <option value="oldest">Oldest First</option>
                        <option value="author">By Author</option>
                    </select>
                </div>
            </div>
            
            <!-- Message Container -->
            <div id="notes-message-container"></div>
            
            <!-- SEO Notes Panel -->
            <div class="seo-notes-panel">
                <div class="almaseo-field-group" role="region" aria-labelledby="notes-heading">
                    <div class="panel-header">
                        <div class="panel-title" id="notes-heading">
                            <span class="panel-icon" aria-hidden="true">📝</span>
                            <span>SEO Notes</span>
                            <span class="panel-tooltip" role="tooltip" aria-label="Private notes for SEO strategies and reminders">ⓘ</span>
                        </div>
                    </div>
                    
                    <!-- Note Input Section -->
                    <div class="note-input-section">
                        <textarea id="seo-note-textarea" 
                                  class="note-textarea" 
                                  placeholder="Add your SEO notes, strategies, competitor analysis, or keyword rankings..."
                                  aria-label="SEO note content"
                                  rows="5"
                                  maxlength="1000"></textarea>
                        
                        <div class="note-char-counter">
                            <span class="note-char-count">
                                <span id="note-char-count">0</span>/1000 characters
                            </span>
                            <span class="note-hint">Press Ctrl+Enter to save</span>
                        </div>
                        
                        <div class="note-controls">
                            <button type="button" class="add-note-btn" id="add-note-btn" aria-label="Add note">
                                <span aria-hidden="true">➕</span> Add Note
                            </button>
                            <button type="button" class="clear-note-btn" id="clear-note-btn" aria-label="Clear note">
                                <span aria-hidden="true">🗑️</span> Clear
                            </button>
                        </div>
                    </div>
                    
                    <!-- Notes List -->
                    <div class="notes-list-container" id="notes-list-container" role="list" aria-label="SEO notes list">
                        <!-- Notes will be populated here by JavaScript -->
                    </div>
                </div>
            </div>
            
            <!-- Post History Tracker Panel -->
            <?php if ($is_connected): ?>
            <div class="history-tracker-panel">
                <div class="almaseo-field-group" role="region" aria-labelledby="history-heading">
                    <div class="panel-header">
                        <div class="panel-title" id="history-heading">
                            <span class="panel-icon" aria-hidden="true">📜</span>
                            <span>Post History Tracker</span>
                            <span class="panel-tooltip" role="tooltip" aria-label="Track all edits and changes to this post">ⓘ</span>
                        </div>
                    </div>
                    
                    <!-- History Filters -->
                    <div class="history-filters">
                        <input type="date" 
                               id="history-date-filter" 
                               class="history-filter-input" 
                               aria-label="Filter by date">
                        
                        <select id="history-user-filter" class="history-filter-select" aria-label="Filter by user">
                            <option value="all">All Users</option>
                            <?php
                            // Get users who can edit posts
                            $users = get_users(array(
                                'capability' => 'edit_posts'
                            ));
                            foreach ($users as $user) {
                                echo '<option value="' . esc_attr($user->display_name) . '">' . esc_html($user->display_name) . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                    
                    <!-- History Table -->
                    <div class="history-table-container">
                        <table class="history-table" role="table" aria-label="Post edit history">
                            <thead>
                                <tr>
                                    <th scope="col">Date/Time</th>
                                    <th scope="col">Editor</th>
                                    <th scope="col">Summary</th>
                                    <th scope="col">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="history-table-tbody">
                                <!-- History entries will be populated by JavaScript -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Metadata History Card -->
            <?php
            // Render metadata history card if available
            if (function_exists('almaseo_history_render_card')) {
                almaseo_history_render_card($post);
            }
            ?>
            
            <!-- Hidden fields for data -->
            <input type="hidden" id="almaseo-current-user" value="<?php echo esc_attr(wp_get_current_user()->display_name); ?>">
            <input type="hidden" id="almaseo_nonce" value="<?php echo wp_create_nonce('almaseo_nonce'); ?>">
            <input type="hidden" id="almaseo-can-edit" value="<?php echo current_user_can('edit_posts') ? 'true' : 'false'; ?>">
        </div>
        <!-- End Notes & History Tab -->
        
        <?php if (!$is_connected): ?>
        <!-- Unlock AI Features Tab -->
        <div class="almaseo-tab-panel" id="tab-unlock-features">
            <div class="almaseo-unlock-container">
                
                <!-- Hero Header Section -->
                <div class="unlock-hero-section">
                    <div class="hero-content">
                        <div class="hero-text">
                            <h1 class="hero-title">
                                <span class="gradient-text">Supercharge Your Website with AlmaSEO Dashboard + Plugin</span>
                            </h1>
                            <p class="hero-description">
                                AlmaSEO isn't just an AI toolkit — it's your SEO command center. Write high-authority blog posts, landing pages, and articles with a click, and publish them instantly to WordPress. Then manage, optimize, and track everything from your AlmaSEO dashboard.
                            </p>
                            <div class="hero-cta-group">
                                <a href="<?php echo admin_url('admin.php?page=seo-playground-connection'); ?>" class="hero-cta-primary">
                                    <span class="cta-icon">⚡</span>
                                    Connect My Site
                                    <span class="cta-arrow">→</span>
                                </a>
                                <a href="https://almaseo.com/features?utm_source=plugin&utm_medium=unlock_tab&utm_campaign=upsell" target="_blank" class="hero-cta-secondary">
                                    <span class="cta-icon">💡</span>
                                    Learn More
                                </a>
                            </div>
                        </div>
                        <div class="hero-illustration">
                            <div class="illustration-wrapper">
                                <div class="floating-card card-1">
                                    <span class="card-icon">🚀</span>
                                    <span class="card-text">10x Faster</span>
                                </div>
                                <div class="floating-card card-2">
                                    <span class="card-icon">📈</span>
                                    <span class="card-text">+250% CTR</span>
                                </div>
                                <div class="floating-card card-3">
                                    <span class="card-icon">⭐</span>
                                    <span class="card-text">Page #1</span>
                                </div>
                                <div class="central-graphic">
                                    <div class="orbit-ring"></div>
                                    <div class="orbit-ring ring-2"></div>
                                    <div class="orbit-ring ring-3"></div>
                                    <div class="center-logo">🧠</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 2x3 Locked Features Grid -->
                <div class="unlock-features-section">
                    <h2 class="section-title">Premium AI Features Waiting for You</h2>
                    
                    <!-- Dashboard Superpowers Row -->
                    <div class="row-label">Dashboard Superpowers</div>
                    <div class="locked-features-grid">
                        
                        <!-- Card 1: Automated Article Writer -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">✍️</span>
                            </div>
                            <h3 class="feature-title">Automated Article Writer</h3>
                            <p class="feature-benefit">Publish SEO-driven blog posts and landing pages instantly — written for authority, optimized for ranking, delivered straight to WordPress.</p>
                            <div class="feature-stats">
                                <span class="stat-item">High authority</span>
                                <span class="stat-item">One-click publish</span>
                            </div>
                        </div>
                        
                        <!-- Card 2: SEO Profile & Brand Voice -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">🎯</span>
                            </div>
                            <h3 class="feature-title">SEO Profile & Brand Voice</h3>
                            <p class="feature-benefit">Tell AlmaSEO your tone, keywords, competitors, and brand details once — it will apply them consistently across all content.</p>
                            <div class="feature-stats">
                                <span class="stat-item">Consistent</span>
                                <span class="stat-item">On-brand</span>
                            </div>
                        </div>
                        
                        <!-- Card 3: Multi-Site Dashboard -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">🌐</span>
                            </div>
                            <h3 class="feature-title">Multi-Site Dashboard</h3>
                            <p class="feature-benefit">Control multiple WordPress sites from one dashboard. Centralized publishing, tracking, and reporting at scale.</p>
                            <div class="feature-stats">
                                <span class="stat-item">Multi-site</span>
                                <span class="stat-item">Scalable</span>
                            </div>
                        </div>
                        
                        <!-- Card 4: Evergreen Tracking & Reporting -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">📊</span>
                            </div>
                            <h3 class="feature-title">Evergreen Tracking & Reporting</h3>
                            <p class="feature-benefit">Track content freshness, SEO health, and rankings across your sites. AlmaSEO keeps your strategy up to date automatically.</p>
                            <div class="feature-stats">
                                <span class="stat-item">Fresh</span>
                                <span class="stat-item">Data-driven</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Plugin Enhancements Row -->
                    <div class="row-label">Plugin Enhancements</div>
                    <div class="locked-features-grid locked-features-grid-plugin">
                        
                        <!-- Card A: Smart Title Generator -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">💡</span>
                            </div>
                            <h3 class="feature-title">Smart Title Generator</h3>
                            <p class="feature-benefit">Create click-worthy titles that attract readers and boost SEO performance.</p>
                            <div class="feature-stats">
                                <span class="stat-item">High CTR</span>
                                <span class="stat-item">Keyword rich</span>
                            </div>
                        </div>
                        
                        <!-- Card B: Meta Description AI -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">📝</span>
                            </div>
                            <h3 class="feature-title">Meta Description AI</h3>
                            <p class="feature-benefit">Generate compelling descriptions that increase clicks and improve search visibility.</p>
                            <div class="feature-stats">
                                <span class="stat-item">Perfect length</span>
                                <span class="stat-item">Engaging</span>
                            </div>
                        </div>
                        
                        <!-- Card C: Schema Generator -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">🔗</span>
                            </div>
                            <h3 class="feature-title">Schema Generator</h3>
                            <p class="feature-benefit">Add structured data markup automatically to stand out in search results.</p>
                            <div class="feature-stats">
                                <span class="stat-item">Rich snippets</span>
                                <span class="stat-item">Stand out</span>
                            </div>
                        </div>
                        
                        <!-- Card D: Keyword Research Pro -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">🎯</span>
                            </div>
                            <h3 class="feature-title">Keyword Research Pro</h3>
                            <p class="feature-benefit">Discover untapped keywords with high potential and low competition.</p>
                            <div class="feature-stats">
                                <span class="stat-item">Deep analysis</span>
                                <span class="stat-item">Trend data</span>
                            </div>
                        </div>
                        
                        <!-- Card E: Content Intelligence -->
                        <div class="locked-feature-card">
                            <div class="feature-lock-indicator">
                                <span class="lock-icon" title="Requires AlmaSEO Connection">🔒</span>
                            </div>
                            <div class="feature-icon-wrapper">
                                <span class="feature-icon">🧠</span>
                            </div>
                            <h3 class="feature-title">Content Intelligence</h3>
                            <p class="feature-benefit">Get real-time SEO analysis with actionable recommendations as you optimize content.</p>
                            <div class="feature-stats">
                                <span class="stat-item">Smart tips</span>
                                <span class="stat-item">Live scoring</span>
                            </div>
                        </div>
                        
                    </div>
                </div>
                
                <!-- Two Halves of the Same Engine Explainer -->
                <div class="unlock-explainer-section">
                    <h3 class="explainer-title">Two Halves of the Same Engine</h3>
                    <p class="explainer-text">
                        The AlmaSEO dashboard is your SEO mission control. The WordPress plugin is your on-site assistant. Together, they automate content creation, optimization, and publishing — so you can focus on strategy instead of manual work.
                    </p>
                </div>
                
                <!-- What You Get Section -->
                <div class="unlock-benefits-section">
                    <h2 class="section-title">What You Get with AlmaSEO</h2>
                    <div class="benefits-grid">
                        <div class="benefit-item">
                            <div class="benefit-icon">🚀</div>
                            <div class="benefit-content">
                                <h3>Instant AI Access</h3>
                                <p>Connect in 60 seconds and start optimizing immediately. No complex setup, no technical knowledge required.</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon">♾️</div>
                            <div class="benefit-content">
                                <h3>Unlimited Generations</h3>
                                <p>Generate as many titles, descriptions, and rewrites as you need. No usage limits during your trial period.</p>
                            </div>
                        </div>
                        <div class="benefit-item">
                            <div class="benefit-icon">🎯</div>
                            <div class="benefit-content">
                                <h3>Proven Results</h3>
                                <p>Join 10,000+ websites seeing average ranking improvements of 3-5 positions within 30 days.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Testimonial Section -->
                <div class="unlock-testimonial-section">
                    <div class="testimonial-card">
                        <div class="testimonial-quote">
                            <span class="quote-mark">"</span>
                            <p class="testimonial-text">
                                AlmaSEO transformed our content strategy completely. We went from page 5 to the first page 
                                for our main keywords in just 6 weeks. The AI tools save us hours every day and the results 
                                speak for themselves - our organic traffic is up 312%!
                            </p>
                        </div>
                        <div class="testimonial-author">
                            <div class="author-avatar">
                                <span>JD</span>
                            </div>
                            <div class="author-info">
                                <div class="author-name">Jennifer Davis</div>
                                <div class="author-title">Marketing Director, TechStartup Inc.</div>
                                <div class="author-rating">
                                    ⭐⭐⭐⭐⭐
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="trust-indicators">
                        <div class="trust-item">
                            <span class="trust-number">10,000+</span>
                            <span class="trust-label">Active Users</span>
                        </div>
                        <div class="trust-item">
                            <span class="trust-number">4.9/5</span>
                            <span class="trust-label">Average Rating</span>
                        </div>
                        <div class="trust-item">
                            <span class="trust-number">24/7</span>
                            <span class="trust-label">Support</span>
                        </div>
                    </div>
                </div>
                
                <!-- Footer Section -->
                <div class="unlock-footer-section">
                    <div class="footer-card">
                        <div class="footer-icon">🔑</div>
                        <div class="footer-content">
                            <h3>Already have an AlmaSEO account?</h3>
                            <p>If you've already signed up for AlmaSEO, simply connect your site to start using all premium features immediately.</p>
                            <div class="footer-cta-group">
                                <a href="<?php echo admin_url('admin.php?page=seo-playground-connection'); ?>" class="footer-cta-primary">
                                    Connect Existing Account
                                </a>
                                <a href="https://almaseo.com/login?utm_source=plugin&utm_medium=unlock_footer" target="_blank" class="footer-cta-secondary">
                                    Login to Dashboard
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="footer-links">
                        <a href="https://almaseo.com/pricing?utm_source=plugin" target="_blank">View Pricing</a>
                        <span class="separator">•</span>
                        <a href="https://almaseo.com/docs?utm_source=plugin" target="_blank">Documentation</a>
                        <span class="separator">•</span>
                        <a href="https://almaseo.com/support?utm_source=plugin" target="_blank">Get Support</a>
                    </div>
                </div>
                
            </div>
        </div>
        <!-- End Unlock AI Features Tab -->
        <?php endif; ?>
        
        </div>
        <!-- End Tab Content -->
        
        <!-- Version Footer -->
        <div class="almaseo-version-footer" style="text-align: right; padding: 10px 20px; color: #999; font-size: 12px; border-top: 1px solid #e0e0e0; margin-top: 20px;">
            AlmaSEO SEO Playground v<?php echo ALMASEO_PLUGIN_VERSION; ?>
        </div>
    </div>
    <!-- End almaseo-seo-playground -->
    
    <script>
    jQuery(document).ready(function($) {
        // Tab Navigation
        $('.almaseo-tab-btn').on('click', function() {
            var targetTab = $(this).data('tab');
            
            // Update active button
            $('.almaseo-tab-btn').removeClass('active');
            $(this).addClass('active');
            
            // Update active panel with fade animation
            $('.almaseo-tab-panel').removeClass('active').fadeOut(200, function() {
                $('#tab-' + targetTab).fadeIn(200).addClass('active');
            });
            
            // Save tab preference
            if (typeof(Storage) !== "undefined") {
                localStorage.setItem('almaseo_active_tab', targetTab);
            }
        });
        
        // Restore last active tab
        if (typeof(Storage) !== "undefined") {
            var lastTab = localStorage.getItem('almaseo_active_tab');
            if (lastTab && $('.almaseo-tab-btn[data-tab="' + lastTab + '"]').length) {
                $('.almaseo-tab-btn[data-tab="' + lastTab + '"]').trigger('click');
            }
        }
        
        // Character count for SEO title
        $('#almaseo_seo_title').on('input', function() {
            var count = $(this).val().length;
            var countSpan = $('#title-count');
            countSpan.text(count);
            
            countSpan.removeClass('warning danger');
            if (count > 50) countSpan.addClass('warning');
            if (count > 58) countSpan.addClass('danger');
        });
        
        // Character count for meta description
        $('#almaseo_seo_description').on('input', function() {
            var count = $(this).val().length;
            var countSpan = $('#description-count');
            countSpan.text(count);
            
            countSpan.removeClass('warning danger');
            if (count > 140) countSpan.addClass('warning');
            if (count > 155) countSpan.addClass('danger');
        });
    });
    </script>
    
    <!-- Saved Snippets Modals -->
    <div id="snippet-modal" class="almaseo-modal">
        <div class="almaseo-modal-content">
            <div class="almaseo-modal-header">
                <h3 id="snippet-modal-title">Create New Snippet</h3>
                <span class="almaseo-modal-close">&times;</span>
            </div>
            <div class="almaseo-modal-body">
                <form id="snippet-form">
                    <div class="almaseo-field-group">
                        <label for="snippet-title">Snippet Title</label>
                        <input type="text" id="snippet-title" name="snippet-title" class="almaseo-input" placeholder="Enter a descriptive title..." required />
                    </div>
                    <div class="almaseo-field-group">
                        <label for="snippet-content">Snippet Content</label>
                        <textarea id="snippet-content" name="snippet-content" class="almaseo-textarea" placeholder="Enter your snippet content..." rows="6" required></textarea>
                    </div>
                    <div class="almaseo-modal-actions">
                        <button type="button" class="almaseo-modal-btn almaseo-modal-cancel">Cancel</button>
                        <button type="submit" class="almaseo-modal-btn almaseo-modal-save">Save Snippet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div id="delete-snippet-modal" class="almaseo-modal">
        <div class="almaseo-modal-content">
            <div class="almaseo-modal-header">
                <h3>Delete Snippet</h3>
                <span class="almaseo-modal-close">&times;</span>
            </div>
            <div class="almaseo-modal-body">
                <p>Are you sure you want to delete this snippet?</p>
                <p><strong id="delete-snippet-title"></strong></p>
                <div class="almaseo-modal-actions">
                    <button type="button" class="almaseo-modal-btn almaseo-modal-cancel">Cancel</button>
                    <button type="button" class="almaseo-modal-btn almaseo-modal-delete">Delete</button>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Save SEO Playground data
function almaseo_save_seo_playground_meta($post_id) {
    // Check if nonce is valid
    if (!isset($_POST['almaseo_seo_playground_nonce']) || 
        !wp_verify_nonce($_POST['almaseo_seo_playground_nonce'], 'almaseo_seo_playground_nonce')) {
        return;
    }
    
    // Check if user has permissions
    if (!current_user_can('edit_post', $post_id)) {
        return;
    }
    
    // Check if not an autosave
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    
    // Get old values for history tracking - check canonical keys first
    $old_title = get_post_meta($post_id, '_almaseo_title', true);
    if (empty($old_title)) {
        $old_title = get_post_meta($post_id, '_seo_playground_title', true);
    }
    
    $old_description = get_post_meta($post_id, '_almaseo_description', true);
    if (empty($old_description)) {
        $old_description = get_post_meta($post_id, '_seo_playground_description', true);
    }
    
    $old_keyword = get_post_meta($post_id, '_almaseo_focus_keyword', true);
    if (empty($old_keyword)) {
        $old_keyword = get_post_meta($post_id, '_seo_playground_focus_keyword', true);
    }
    
    // Get new values
    $new_title = isset($_POST['almaseo_seo_title']) ? sanitize_text_field($_POST['almaseo_seo_title']) : '';
    $new_description = isset($_POST['almaseo_seo_description']) ? sanitize_textarea_field($_POST['almaseo_seo_description']) : '';
    $new_keyword = isset($_POST['almaseo_focus_keyword']) ? sanitize_text_field($_POST['almaseo_focus_keyword']) : '';
    
    // Track metadata changes in history
    $has_changes = false;
    if ($old_title != $new_title || $old_description != $new_description || $old_keyword != $new_keyword) {
        $has_changes = true;
        
        // Get existing history
        $metadata_history = get_post_meta($post_id, '_almaseo_metadata_history', true);
        if (!is_array($metadata_history)) {
            $metadata_history = [];
        }
        
        // Add new history entry
        $current_user = wp_get_current_user();
        $history_entry = [
            'timestamp' => current_time('timestamp'),
            'title' => $new_title,
            'description' => $new_description,
            'keyword' => $new_keyword,
            'user' => $current_user->display_name
        ];
        
        // Add to beginning of array (newest first)
        array_unshift($metadata_history, $history_entry);
        
        // Limit to 20 entries
        if (count($metadata_history) > 20) {
            $metadata_history = array_slice($metadata_history, 0, 20);
        }
        
        // Save history
        update_post_meta($post_id, '_almaseo_metadata_history', $metadata_history);
    }
    
    // Save to canonical meta keys
    update_post_meta($post_id, '_almaseo_title', $new_title);
    update_post_meta($post_id, '_almaseo_description', $new_description);
    update_post_meta($post_id, '_almaseo_focus_keyword', $new_keyword);
    
    // Delete legacy keys if they exist (migration complete)
    delete_post_meta($post_id, '_seo_playground_title');
    delete_post_meta($post_id, '_seo_playground_description');
    delete_post_meta($post_id, '_seo_playground_focus_keyword');
    
    // Save sticky notes
    if (isset($_POST['almaseo_seo_notes'])) {
        update_post_meta($post_id, '_seo_playground_notes', sanitize_textarea_field($_POST['almaseo_seo_notes']));
    }
    
    // Save Schema & Meta tab fields
    
    // Meta Robots settings
    $robots_index = isset($_POST['almaseo_robots_index']) ? 'index' : 'noindex';
    $robots_follow = isset($_POST['almaseo_robots_follow']) ? 'follow' : 'nofollow';
    $robots_archive = isset($_POST['almaseo_robots_archive']) ? 'archive' : 'noarchive';
    $robots_snippet = isset($_POST['almaseo_robots_snippet']) ? 'snippet' : 'nosnippet';
    $robots_imageindex = isset($_POST['almaseo_robots_imageindex']) ? 'imageindex' : 'noimageindex';
    $robots_translate = isset($_POST['almaseo_robots_translate']) ? 'translate' : 'notranslate';
    
    update_post_meta($post_id, '_almaseo_robots_index', $robots_index);
    update_post_meta($post_id, '_almaseo_robots_follow', $robots_follow);
    update_post_meta($post_id, '_almaseo_robots_archive', $robots_archive);
    update_post_meta($post_id, '_almaseo_robots_snippet', $robots_snippet);
    update_post_meta($post_id, '_almaseo_robots_imageindex', $robots_imageindex);
    update_post_meta($post_id, '_almaseo_robots_translate', $robots_translate);
    
    // Canonical URL
    if (isset($_POST['almaseo_canonical_url'])) {
        update_post_meta($post_id, '_almaseo_canonical_url', esc_url_raw($_POST['almaseo_canonical_url']));
    }
    
    // Schema Type
    if (isset($_POST['almaseo_schema_type'])) {
        update_post_meta($post_id, '_almaseo_schema_type', sanitize_text_field($_POST['almaseo_schema_type']));
        update_post_meta($post_id, '_seo_playground_schema_type', sanitize_text_field($_POST['almaseo_schema_type'])); // Legacy
    }
    
    // Article Author (for Article schema)
    if (isset($_POST['almaseo_article_author'])) {
        update_post_meta($post_id, '_almaseo_article_author', sanitize_text_field($_POST['almaseo_article_author']));
    }
    
    // Open Graph metadata
    if (isset($_POST['almaseo_og_title'])) {
        update_post_meta($post_id, '_almaseo_og_title', sanitize_text_field($_POST['almaseo_og_title']));
    }
    if (isset($_POST['almaseo_og_description'])) {
        update_post_meta($post_id, '_almaseo_og_description', sanitize_textarea_field($_POST['almaseo_og_description']));
    }
    if (isset($_POST['almaseo_og_image'])) {
        update_post_meta($post_id, '_almaseo_og_image', esc_url_raw($_POST['almaseo_og_image']));
    }
    
    // Twitter Card metadata
    if (isset($_POST['almaseo_twitter_card'])) {
        update_post_meta($post_id, '_almaseo_twitter_card', sanitize_text_field($_POST['almaseo_twitter_card']));
    }
    if (isset($_POST['almaseo_twitter_title'])) {
        update_post_meta($post_id, '_almaseo_twitter_title', sanitize_text_field($_POST['almaseo_twitter_title']));
    }
    if (isset($_POST['almaseo_twitter_description'])) {
        update_post_meta($post_id, '_almaseo_twitter_description', sanitize_textarea_field($_POST['almaseo_twitter_description']));
    }
    
    // Save update reminder settings
    if (isset($_POST['almaseo_update_reminder_enabled'])) {
        update_post_meta($post_id, '_almaseo_update_reminder_enabled', '1');
        
        // Save email preference
        if (isset($_POST['almaseo_update_reminder_email'])) {
            update_post_meta($post_id, '_almaseo_update_reminder_email', '1');
        } else {
            delete_post_meta($post_id, '_almaseo_update_reminder_email');
        }
        
        if (isset($_POST['almaseo_update_reminder_days'])) {
            $reminder_days = intval($_POST['almaseo_update_reminder_days']);
            if ($reminder_days > 0 && $reminder_days <= 365) {
                update_post_meta($post_id, '_almaseo_update_reminder_days', $reminder_days);
                
                // Schedule the reminder cron event
                $scheduled_time = current_time('timestamp') + ($reminder_days * DAY_IN_SECONDS);
                
                // Clear any existing reminder for this post
                wp_clear_scheduled_hook('almaseo_content_refresh_reminder', array($post_id));
                
                // Schedule new reminder
                wp_schedule_single_event($scheduled_time, 'almaseo_content_refresh_reminder', array($post_id));
                
                // Save the scheduled time for display
                update_post_meta($post_id, '_almaseo_update_reminder_scheduled', $scheduled_time);
            }
        }
    } else {
        // Clear reminder if unchecked
        delete_post_meta($post_id, '_almaseo_update_reminder_enabled');
        delete_post_meta($post_id, '_almaseo_update_reminder_email');
        delete_post_meta($post_id, '_almaseo_update_reminder_scheduled');
        
        // Clear scheduled event
        wp_clear_scheduled_hook('almaseo_content_refresh_reminder', array($post_id));
    }
}
add_action('save_post', 'almaseo_save_seo_playground_meta');

// Remove conflicting schema and meta tags from WordPress core and themes
add_action('init', function() {
    // Remove WordPress core meta tags that might conflict
    remove_action('wp_head', 'wp_oembed_add_host_js');
    remove_action('wp_head', 'wp_oembed_add_discovery_links');
    remove_action('wp_head', 'rel_canonical');
    remove_action('wp_head', 'rest_output_link_wp_head');
    remove_action('wp_head', 'wp_shortlink_wp_head');
    
    // Disable Yoast JSON-LD if present
    add_filter('wpseo_json_ld_output', '__return_false', 99);
    add_filter('wpseo_schema_graph_enabled', '__return_false', 99);
    
    // Disable Rank Math schema if present
    add_filter('rank_math/json_ld', '__return_false', 99);
    
    // Disable All in One SEO schema if present
    add_filter('aioseo_schema_output', '__return_false', 99);
    
    // Disable SEOPress schema if present
    add_filter('seopress_schemas_jsonld_output', '__return_false', 99);
    
    // Disable The SEO Framework schema if present
    add_filter('the_seo_framework_ldjson_scripts', '__return_false', 99);
    
    // Remove any theme-added schema
    add_filter('wp_head', function() {
        global $wp_filter;
        if (isset($wp_filter['wp_head'])) {
            foreach ($wp_filter['wp_head'] as $priority => $hooks) {
                foreach ($hooks as $key => $hook) {
                    // Remove any hooks that might output schema
                    if (strpos($key, 'schema') !== false || strpos($key, 'json_ld') !== false || strpos($key, 'structured_data') !== false) {
                        remove_action('wp_head', $key, $priority);
                    }
                }
            }
        }
    }, 1);
});

// Output BlogPosting JSON-LD for Article schema type on front-end
// DISABLED - Using schema-final.php instead for proper Exclusive Mode support
/* Commented out to prevent duplicate schema output
add_action('wp_head', function() {
    // Check if we've already output schema (prevent duplicates)
    static $done = false;
    if ($done) return;
    
    // Only output on singular posts, pages, and front page
    if (!is_singular('post') && !is_page() && !is_front_page()) return;
    
    // Get post ID (for front page, get the page ID)
    $post_id = is_front_page() && !is_home() ? get_option('page_on_front') : get_the_ID();
    if (!$post_id) return;
    
    // Get schema type - default to Article for all pages
    $schema_type = get_post_meta($post_id, '_almaseo_schema_type', true) ?: 'Article';
    
    // Only output for Article type (free schema)
    if ($schema_type !== 'Article') return;
    
    // Get post/page data
    $post = get_post($post_id);
    if (!$post) return;
    
    // HEADLINE: SEO Title → post title
    $seo_title = get_post_meta($post_id, '_almaseo_title', true);
    $headline = $seo_title ?: get_the_title($post_id);
    
    // DESCRIPTION: SEO Meta Description → excerpt → first 160 chars (with entity cleaning)
    $desc = get_post_meta($post_id, '_almaseo_description', true);
    if (!$desc) {
        $excerpt = wp_strip_all_tags(get_the_excerpt($post_id));
        if (!$excerpt) {
            $content = wp_strip_all_tags(get_post_field('post_content', $post_id));
            $excerpt = mb_substr(trim($content), 0, 160);
        }
        $desc = $excerpt;
    }
    
    // If still no description, use site description as last resort
    if (!$desc) {
        $desc = get_bloginfo('description');
    }
    
    // Decode any HTML entities that might be present (like &hellip; or &rsquo;)
    $desc = html_entity_decode(wp_specialchars_decode($desc, ENT_QUOTES), ENT_QUOTES, get_bloginfo('charset'));
    
    // IMAGE: Full fallback chain - OG → Featured → Site Icon → Custom Logo → Plugin Logo
    $image_url = '';
    
    // 1. Try OG Image
    $og_image = get_post_meta($post_id, '_almaseo_og_image', true);
    if ($og_image) {
        $image_url = $og_image;
    }
    
    // 2. Try Featured Image
    if (!$image_url && has_post_thumbnail($post_id)) {
        $image_url = get_the_post_thumbnail_url($post_id, 'full');
    }
    
    // 3. Try Site Icon
    if (!$image_url) {
        $site_icon = get_site_icon_url('full');
        if ($site_icon) {
            $image_url = $site_icon;
        }
    }
    
    // 4. Try Theme Custom Logo
    if (!$image_url) {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_data = wp_get_attachment_image_src($custom_logo_id, 'full');
            if ($logo_data) {
                $image_url = $logo_data[0];
            }
        }
    }
    
    // 5. Final fallback to plugin logo
    if (!$image_url) {
        $image_url = plugin_dir_url(__FILE__) . 'almaseo-logo.png';
    }
    
    // CANONICAL URL
    $canonical = get_post_meta($post_id, '_almaseo_canonical_url', true);
    if (!$canonical) {
        $canonical = is_front_page() ? home_url('/') : get_permalink($post_id);
    }
    
    // DATES
    $date_published = get_the_date('c', $post_id);
    $date_modified = get_the_modified_date('c', $post_id);
    
    // AUTHOR with URL - ensure both name and url are always present
    $author_id = $post->post_author;
    $author_name = get_the_author_meta('display_name', $author_id);
    if (!$author_name) {
        $author_name = get_bloginfo('name'); // Fallback to site name
    }
    
    // Author URL: Prefer author archive, fallback to homepage
    $author_url = get_author_posts_url($author_id);
    if (!$author_url || empty($author_url)) {
        $author_url = home_url('/');
    }
    
    // PUBLISHER with logo
    $publisher_logo = get_site_icon_url('full');
    if (!$publisher_logo) {
        // Try custom logo
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_data = wp_get_attachment_image_src($custom_logo_id, 'full');
            if ($logo_data) {
                $publisher_logo = $logo_data[0];
            }
        }
    }
    if (!$publisher_logo) {
        $publisher_logo = plugin_dir_url(__FILE__) . 'almaseo-logo.png';
    }
    
    // Build JSON-LD data - Always use BlogPosting (not Article)
    $json_ld = array(
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'mainEntityOfPage' => $canonical,
        'headline' => $headline,
        'description' => $desc,
        'datePublished' => $date_published,
        'dateModified' => $date_modified,
        'author' => array(
            '@type' => 'Person',
            'name' => $author_name,
            'url' => $author_url
        ),
        'publisher' => array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => $publisher_logo
            )
        ),
        'image' => $image_url
    );
    
    // Mark as done to prevent any duplicate outputs
    $done = true;
    
    // Safe output with proper JSON encoding - ONLY ONE JSON-LD block
    echo "\n<!-- AlmaSEO Schema Markup -->\n";
    echo '<script type="application/ld+json">' . wp_json_encode($json_ld, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) . '</script>';
    echo "\n<!-- /AlmaSEO Schema Markup -->\n";
}, 100); // High priority to ensure it runs after theme functions
End of commented out schema output */

// AJAX handler for checking connection status
add_action('wp_ajax_seo_playground_check_connection', 'seo_playground_ajax_check_connection');
function seo_playground_ajax_check_connection() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_send_json_error(array('message' => 'Invalid security token'));
    }
    
    // Check connection status
    $is_connected = seo_playground_is_alma_connected();
    
    wp_send_json_success(array(
        'connected' => $is_connected,
        'message' => $is_connected ? 'Connected to AlmaSEO' : 'Not connected to AlmaSEO'
    ));
}

// AJAX handler for re-optimization check
function seo_playground_ajax_reoptimize_check() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get post data
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
    $site_url = isset($_POST['site_url']) ? esc_url_raw($_POST['site_url']) : '';
    
    // Validate required data
    if (!$post_id || !$title || !$content || !$site_url) {
        wp_send_json_error(array('message' => 'Missing required data'));
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_app_password', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'post_id' => $post_id,
        'title' => $title,
        'content' => $content,
        'site_url' => $site_url
    );
    
    // Make API request to AlmaSEO
    $response = wp_remote_post('https://app.almaseo.com/api/v1/posts/reoptimize-check', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30,
        'data_format' => 'body'
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'API request failed: ' . $response->get_error_message()));
        return;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status_code !== 200) {
        wp_send_json_error(array('message' => 'API request failed with status: ' . $status_code));
        return;
    }
    
    $data = json_decode($body, true);
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from API'));
        return;
    }
    
    // Return the re-optimization data
    wp_send_json_success($data);
}
add_action('wp_ajax_seo_playground_reoptimize_check', 'seo_playground_ajax_reoptimize_check');

// AJAX handler for AI rewrite
function seo_playground_ajax_rewrite() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Check tier and usage limits
    if (!almaseo_can_use_ai_features()) {
        wp_send_json_error(array(
            'message' => 'AI features require Pro or Max tier. Please upgrade to access AI tools.',
            'tier' => almaseo_get_user_tier()
        ));
        return;
    }
    
    // Check remaining generations for Pro tier
    $user_tier = almaseo_get_user_tier();
    if ($user_tier === 'pro') {
        $generations = almaseo_get_remaining_generations();
        if ($generations['remaining'] <= 0) {
            wp_send_json_error(array(
                'message' => 'You have reached your monthly AI generation limit. Please upgrade to Max for unlimited access.',
                'remaining' => 0
            ));
            return;
        }
    }
    
    // Get rewrite data
    $input = isset($_POST['input']) ? sanitize_textarea_field($_POST['input']) : '';
    $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $site_url = isset($_POST['site_url']) ? esc_url_raw($_POST['site_url']) : '';
    
    // Validate required data
    if (empty($input) || empty($type) || !$post_id || !$site_url) {
        wp_send_json_error(array('message' => 'Missing required data'));
        return;
    }
    
    // Validate type
    $valid_types = array('paragraph', 'title', 'description');
    if (!in_array($type, $valid_types)) {
        wp_send_json_error(array('message' => 'Invalid rewrite type'));
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_app_password', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'input' => $input,
        'type' => $type,
        'post_id' => $post_id,
        'site_url' => $site_url
    );
    
    // Make API request to AlmaSEO
    $response = wp_remote_post('https://app.almaseo.com/api/v1/rewrite', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30,
        'data_format' => 'body'
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'API request failed: ' . $response->get_error_message()));
        return;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status_code !== 200) {
        wp_send_json_error(array('message' => 'API request failed with status: ' . $status_code));
        return;
    }
    
    $data = json_decode($body, true);
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from API'));
        return;
    }
    
    // Track usage
    almaseo_track_ai_usage('content_rewrite');
    
    // Get updated generation info
    $generations = almaseo_get_remaining_generations();
    
    // Return the rewritten content with usage info
    wp_send_json_success(array(
        'rewritten' => $data['rewritten'] ?? $data,
        'usage' => array(
            'remaining' => $generations['remaining'],
            'total' => $generations['total'],
            'tier' => $user_tier
        )
    ));
}
add_action('wp_ajax_seo_playground_rewrite', 'seo_playground_ajax_rewrite');

// AJAX handler for content brief generation
function seo_playground_ajax_generate_brief() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get brief data
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $keywords = isset($_POST['keywords']) ? sanitize_text_field($_POST['keywords']) : '';
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $site_url = isset($_POST['site_url']) ? esc_url_raw($_POST['site_url']) : '';
    
    // Validate required data
    if (empty($title) || !$post_id || !$site_url) {
        wp_send_json_error(array('message' => 'Missing required data'));
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_app_password', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'title' => $title,
        'keywords' => $keywords,
        'post_id' => $post_id,
        'site_url' => $site_url
    );
    
    // Make API request to AlmaSEO
    $response = wp_remote_post('https://app.almaseo.com/api/v1/brief/suggest', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30,
        'data_format' => 'body'
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'API request failed: ' . $response->get_error_message()));
        return;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status_code !== 200) {
        wp_send_json_error(array('message' => 'API request failed with status: ' . $status_code));
        return;
    }
    
    $data = json_decode($body, true);
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from API'));
        return;
    }
    
    // Return the brief data
    wp_send_json_success($data);
}
add_action('wp_ajax_seo_playground_generate_brief', 'seo_playground_ajax_generate_brief');

// AJAX handler for FAQ generation
function seo_playground_ajax_generate_faqs() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get FAQ data with null safety
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $site_url = isset($_POST['site_url']) ? esc_url_raw($_POST['site_url']) : '';
    
    // Validate required data
    if (empty($title) || empty($content) || !$post_id || !$site_url) {
        wp_send_json_error(array('message' => 'Missing required data'));
        return;
    }
    
    // Validate content length (minimum 100 words) with null safety
    $content_text = !empty($content) ? strip_tags($content) : '';
    $word_count = str_word_count($content_text);
    if ($word_count < 100) {
        wp_send_json_error(array('message' => 'Content must be at least 100 words to generate meaningful FAQs'));
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_app_password', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'title' => $title,
        'content' => $content,
        'post_id' => $post_id,
        'site_url' => $site_url
    );
    
    // Make API request to AlmaSEO
    $response = wp_remote_post('https://app.almaseo.com/api/v1/faqs/suggest', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30,
        'data_format' => 'body'
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'API request failed: ' . $response->get_error_message()));
        return;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    $body = wp_remote_retrieve_body($response);
    
    if ($status_code !== 200) {
        wp_send_json_error(array('message' => 'API request failed with status: ' . $status_code));
        return;
    }
    
    $data = json_decode($body, true);
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from API'));
        return;
    }
    
    // Return the FAQ data
    wp_send_json_success($data);
}
add_action('wp_ajax_seo_playground_generate_faqs', 'seo_playground_ajax_generate_faqs');

// AJAX handler for post intelligence
add_action('wp_ajax_seo_playground_post_insight', 'seo_playground_ajax_post_insight');
function seo_playground_ajax_post_insight() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get post data
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $keywords = isset($_POST['keywords']) ? sanitize_text_field($_POST['keywords']) : '';
    $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
    $site_url = isset($_POST['site_url']) ? esc_url_raw($_POST['site_url']) : '';
    
    // Validate required data
    if (!$post_id || !$title || !$content || !$site_url) {
        wp_send_json_error(array('message' => 'Missing required data'));
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_api_key', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'title' => $title,
        'keywords' => $keywords,
        'content' => $content,
        'post_id' => $post_id,
        'site_url' => $site_url
    );
    
    // Make API request
    $response = wp_remote_post('https://app.almaseo.com/api/v1/post-insight', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Failed to connect to AlmaSEO API: ' . $response->get_error_message()));
        return;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from AlmaSEO API'));
        return;
    }
    
    if (isset($data['error'])) {
        wp_send_json_error(array('message' => $data['error']));
        return;
    }
    
    if (!isset($data['insight']) || empty($data['insight'])) {
        wp_send_json_error(array('message' => 'No insight data received from API'));
        return;
    }
    
    // Save the insight to post meta for persistence
    update_post_meta($post_id, '_seo_playground_post_insight', sanitize_textarea_field($data['insight']));
    update_post_meta($post_id, '_seo_playground_post_insight_timestamp', current_time('mysql'));
    
    wp_send_json_success(array(
        'insight' => esc_html($data['insight']),
        'timestamp' => current_time('mysql'),
        'message' => 'Post intelligence generated successfully'
    ));
}

// AJAX handler for getting existing post insight
add_action('wp_ajax_seo_playground_get_post_insight', 'seo_playground_ajax_get_post_insight');
function seo_playground_ajax_get_post_insight() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get post ID
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID'));
        return;
    }
    
    // Get existing insight from post meta
    $insight = get_post_meta($post_id, '_seo_playground_post_insight', true);
    $timestamp = get_post_meta($post_id, '_seo_playground_post_insight_timestamp', true);
    
    if ($insight) {
        wp_send_json_success(array(
            'insight' => esc_html($insight),
            'timestamp' => $timestamp ? $timestamp : current_time('mysql')
        ));
    } else {
        wp_send_json_error(array('message' => 'No existing insight found'));
    }
}

// AJAX handler for keyword intelligence
add_action('wp_ajax_seo_playground_keyword_intelligence', 'seo_playground_ajax_keyword_intelligence');
function seo_playground_ajax_keyword_intelligence() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get post data
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $keyword = isset($_POST['keyword']) ? sanitize_text_field($_POST['keyword']) : '';
    $site_url = isset($_POST['site_url']) ? esc_url_raw($_POST['site_url']) : '';
    
    // Validate required data
    if (!$post_id || !$keyword || !$site_url) {
        wp_send_json_error(array('message' => 'Missing required data'));
        return;
    }
    
    // Validate keyword length
    if (strlen($keyword) < 2 || strlen($keyword) > 100) {
        wp_send_json_error(array('message' => 'Keyword must be between 2 and 100 characters'));
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_api_key', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'keyword' => $keyword,
        'post_id' => $post_id,
        'site_url' => $site_url
    );
    
    // Make API request
    $response = wp_remote_post('https://app.almaseo.com/api/v1/keyword-insight', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Failed to connect to AlmaSEO API: ' . $response->get_error_message()));
        return;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from AlmaSEO API'));
        return;
    }
    
    if (isset($data['error'])) {
        wp_send_json_error(array('message' => $data['error']));
        return;
    }
    
    // Validate response structure
    $required_fields = array('intent', 'difficulty', 'related_terms', 'tip');
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            wp_send_json_error(array('message' => 'Missing required field: ' . $field));
            return;
        }
    }
    
    // Save the keyword intelligence to post meta for persistence
    update_post_meta($post_id, '_seo_playground_keyword_intelligence', sanitize_textarea_field(json_encode($data)));
    update_post_meta($post_id, '_seo_playground_keyword_intelligence_timestamp', current_time('mysql'));
    update_post_meta($post_id, '_seo_playground_keyword_intelligence_keyword', sanitize_text_field($keyword));
    
    wp_send_json_success(array(
        'intent' => esc_html($data['intent']),
        'difficulty' => esc_html($data['difficulty']),
        'related_terms' => esc_html($data['related_terms']),
        'tip' => esc_html($data['tip']),
        'timestamp' => current_time('mysql'),
        'message' => 'Keyword intelligence generated successfully'
    ));
}

// AJAX handler for getting existing keyword intelligence
add_action('wp_ajax_seo_playground_get_keyword_intelligence', 'seo_playground_ajax_get_keyword_intelligence');
function seo_playground_ajax_get_keyword_intelligence() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get post ID
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID'));
        return;
    }
    
    // Get existing keyword intelligence from post meta
    $intelligence_json = get_post_meta($post_id, '_seo_playground_keyword_intelligence', true);
    $timestamp = get_post_meta($post_id, '_seo_playground_keyword_intelligence_timestamp', true);
    $keyword = get_post_meta($post_id, '_seo_playground_keyword_intelligence_keyword', true);
    
    if ($intelligence_json) {
        $intelligence = json_decode($intelligence_json, true);
        if ($intelligence && is_array($intelligence)) {
            wp_send_json_success(array(
                'intelligence' => array(
                    'intent' => esc_html($intelligence['intent']),
                    'difficulty' => esc_html($intelligence['difficulty']),
                    'related_terms' => esc_html($intelligence['related_terms']),
                    'tip' => esc_html($intelligence['tip'])
                ),
                'timestamp' => $timestamp ? $timestamp : current_time('mysql'),
                'keyword' => $keyword ? esc_html($keyword) : ''
            ));
        }
    }
    
    wp_send_json_error(array('message' => 'No existing keyword intelligence found'));
}

// AJAX handler for saved snippets - Get snippets
function seo_playground_ajax_get_snippets() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get current user ID
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'User not authenticated'));
        return;
    }
    
    // Get saved snippets from user meta
    $snippets_json = get_user_meta($user_id, '_seo_playground_saved_snippets', true);
    $snippets = array();
    
    if ($snippets_json) {
        $snippets = json_decode($snippets_json, true);
        if (!is_array($snippets)) {
            $snippets = array();
        }
    }
    
    wp_send_json_success(array('snippets' => $snippets));
}
add_action('wp_ajax_seo_playground_get_snippets', 'seo_playground_ajax_get_snippets');

// AJAX handler for saved snippets - Save snippet
function seo_playground_ajax_save_snippet() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get current user ID
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'User not authenticated'));
        return;
    }
    
    // Get and validate snippet data
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
    $snippet_id = isset($_POST['snippet_id']) ? sanitize_text_field($_POST['snippet_id']) : ''; // Empty for new, existing ID for edit
    
    if (empty($title) || empty($content)) {
        wp_send_json_error(array('message' => 'Title and content are required'));
        return;
    }
    
    // Get existing snippets
    $snippets_json = get_user_meta($user_id, '_seo_playground_saved_snippets', true);
    $snippets = array();
    
    if ($snippets_json) {
        $snippets = json_decode($snippets_json, true);
        if (!is_array($snippets)) {
            $snippets = array();
        }
    }
    
    // Create new snippet or update existing
    if (empty($snippet_id)) {
        // Create new snippet
        $new_snippet = array(
            'id' => uniqid('snippet_'),
            'title' => $title,
            'content' => $content,
            'created' => current_time('mysql'),
            'updated' => current_time('mysql')
        );
        $snippets[] = $new_snippet;
    } else {
        // Update existing snippet
        $found = false;
        foreach ($snippets as &$snippet) {
            if ($snippet['id'] === $snippet_id) {
                $snippet['title'] = $title;
                $snippet['content'] = $content;
                $snippet['updated'] = current_time('mysql');
                $found = true;
                break;
            }
        }
        
        if (!$found) {
            wp_send_json_error(array('message' => 'Snippet not found'));
            return;
        }
    }
    
    // Save updated snippets
    $result = update_user_meta($user_id, '_seo_playground_saved_snippets', json_encode($snippets));
    
    if ($result === false) {
        wp_send_json_error(array('message' => 'Failed to save snippet'));
        return;
    }
    
    wp_send_json_success(array(
        'message' => 'Snippet saved successfully',
        'snippets' => $snippets
    ));
}
add_action('wp_ajax_seo_playground_save_snippet', 'seo_playground_ajax_save_snippet');

// AJAX handler for saved snippets - Delete snippet
function seo_playground_ajax_delete_snippet() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get current user ID
    $user_id = get_current_user_id();
    if (!$user_id) {
        wp_send_json_error(array('message' => 'User not authenticated'));
        return;
    }
    
    // Get snippet ID to delete
    $snippet_id = isset($_POST['snippet_id']) ? sanitize_text_field($_POST['snippet_id']) : '';
    
    if (empty($snippet_id)) {
        wp_send_json_error(array('message' => 'Snippet ID is required'));
        return;
    }
    
    // Get existing snippets
    $snippets_json = get_user_meta($user_id, '_seo_playground_saved_snippets', true);
    $snippets = array();
    
    if ($snippets_json) {
        $snippets = json_decode($snippets_json, true);
        if (!is_array($snippets)) {
            $snippets = array();
        }
    }
    
    // Remove the snippet
    $found = false;
    foreach ($snippets as $key => $snippet) {
        if ($snippet['id'] === $snippet_id) {
            unset($snippets[$key]);
            $found = true;
            break;
        }
    }
    
    if (!$found) {
        wp_send_json_error(array('message' => 'Snippet not found'));
        return;
    }
    
    // Re-index array
    $snippets = array_values($snippets);
    
    // Save updated snippets
    $result = update_user_meta($user_id, '_seo_playground_saved_snippets', json_encode($snippets));
    
    if ($result === false) {
        wp_send_json_error(array('message' => 'Failed to delete snippet'));
        return;
    }
    
    wp_send_json_success(array(
        'message' => 'Snippet deleted successfully',
        'snippets' => $snippets
    ));
}
add_action('wp_ajax_seo_playground_delete_snippet', 'seo_playground_ajax_delete_snippet');

// SEO Playground Overview Page
function seo_playground_render_overview_page() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    // Check if AlmaSEO is connected
    $is_connected = seo_playground_is_alma_connected();
    
    // Get latest 25 posts
    $posts_query = new WP_Query(array(
        'post_type' => 'post',
        'post_status' => 'publish',
        'posts_per_page' => 25,
        'orderby' => 'date',
        'order' => 'DESC'
    ));
    
    ?>
    <div class="wrap">
        <h1>SEO Playground by AlmaSEO - Overview</h1>
        
        <?php if (!$is_connected): ?>
        <div class="notice notice-warning">
            <p><strong>🔒 AlmaSEO Not Connected:</strong> Connect to AlmaSEO to see AI-powered insights and optimization suggestions.</p>
        </div>
        <?php endif; ?>
        
        <div class="almaseo-overview-container">
            <!-- Filter Controls -->
            <div class="almaseo-overview-filters">
                <select id="almaseo-status-filter" class="almaseo-filter-dropdown">
                    <option value="all">Show All Posts</option>
                    <option value="optimized">Fully Optimized</option>
                    <option value="needs-review">Needs Review</option>
                    <option value="missing-data">Missing Data</option>
                </select>
            </div>
            
            <!-- Posts Table -->
            <div class="almaseo-overview-table-container">
                <table class="almaseo-overview-table">
                    <thead>
                        <tr>
                            <th>✅ Post Title</th>
                            <th title="Based on how many SEO checks pass">📝 Status</th>
                            <th>📈 Last AI Action</th>
                            <th>🗂 Schema Type</th>
                            <th title="Out of 8 SEO best practices">🟢 Scorecard</th>
                            <th title="Click to auto-check post again for reoptimization">⚙️ Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($posts_query->have_posts()): ?>
                            <?php while ($posts_query->have_posts()): $posts_query->the_post(); 
                                $post_id = get_the_ID();
                                
                                // Get post meta data
                                $seo_title = get_post_meta($post_id, '_seo_playground_title', true);
                                $seo_description = get_post_meta($post_id, '_seo_playground_description', true);
                                $schema_type = get_post_meta($post_id, '_seo_playground_schema_type', true);
                                $keyword_suggestions = get_post_meta($post_id, '_seo_playground_keyword_suggestions', true);
                                $internal_links = get_post_meta($post_id, '_seo_playground_internal_links', true);
                                $rewrite_data = get_post_meta($post_id, '_seo_playground_rewrite', true);
                                $reoptimize_flag = get_post_meta($post_id, '_seo_playground_reoptimize_flag', true);
                                
                                // Calculate scorecard status (similar to JavaScript logic)
                                $scorecard_checks = array(
                                    'seo_title' => !empty($seo_title),
                                    'meta_description' => !empty($seo_description),
                                    'focus_keywords' => !empty($keyword_suggestions),
                                    'internal_links' => !empty($internal_links),
                                    'schema_type' => !empty($schema_type) && $schema_type !== 'none',
                                    'ai_rewrite' => !empty($rewrite_data),
                                    'content_length' => strlen(get_the_content()) >= 300,
                                    'reoptimization' => empty($reoptimize_flag)
                                );
                                
                                $passed_checks = count(array_filter($scorecard_checks));
                                $total_checks = count($scorecard_checks);
                                $scorecard_percentage = $total_checks > 0 ? round(($passed_checks / $total_checks) * 100) : 0;
                                
                                // Determine status
                                if ($passed_checks >= 6) {
                                    $status = 'Fully Optimized';
                                    $status_class = 'status-optimized';
                                } elseif ($passed_checks >= 4) {
                                    $status = 'Needs Review';
                                    $status_class = 'status-review';
                                } else {
                                    $status = 'Missing Data';
                                    $status_class = 'status-missing';
                                }
                                
                                // Determine last AI action
                                $last_ai_action = 'None';
                                if (!empty($rewrite_data)) {
                                    $last_ai_action = 'AI Rewrite';
                                } elseif (!empty($keyword_suggestions)) {
                                    $last_ai_action = 'Keyword Suggestions';
                                } elseif (!empty($seo_title) || !empty($seo_description)) {
                                    $last_ai_action = 'Metadata';
                                } elseif (!empty($schema_type) && $schema_type !== 'none') {
                                    $last_ai_action = 'Schema';
                                }
                            ?>
                            <tr class="almaseo-post-row" data-status="<?php echo esc_attr(strtolower(str_replace(' ', '-', $status ?? ''))); ?>">
                                <td>
                                    <a href="<?php echo esc_url(get_edit_post_link($post_id)); ?>" target="_blank">
                                        <?php echo esc_html(get_the_title()); ?>
                                    </a>
                                </td>
                                <td>
                                    <span class="almaseo-status-badge <?php echo esc_attr($status_class); ?>" title="<?php echo esc_attr($status); ?>">
                                        <?php echo esc_html($status); ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="almaseo-ai-action"><?php echo esc_html($last_ai_action); ?></span>
                                </td>
                                <td>
                                    <?php if (!empty($schema_type) && $schema_type !== 'none'): ?>
                                        <span class="almaseo-schema-type"><?php echo esc_html(ucfirst($schema_type)); ?></span>
                                    <?php else: ?>
                                        <span class="almaseo-schema-none">None</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="almaseo-scorecard-summary">
                                        <span class="almaseo-scorecard-score"><?php echo esc_html($passed_checks); ?>/<?php echo esc_html($total_checks); ?></span>
                                        <div class="almaseo-scorecard-bar">
                                            <div class="almaseo-scorecard-fill" style="width: <?php echo esc_attr($scorecard_percentage); ?>%"></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="almaseo-action-buttons">
                                        <button class="almaseo-action-btn almaseo-reoptimize-btn" data-post-id="<?php echo esc_attr($post_id); ?>" title="Click to auto-check post again for reoptimization">
                                            🔄 Reoptimize
                                        </button>
                                        <button class="almaseo-action-btn almaseo-rewrite-btn" data-post-id="<?php echo esc_attr($post_id); ?>">
                                            ✍️ Rewrite
                                        </button>
                                        <button class="almaseo-action-btn almaseo-view-meta-btn" data-post-id="<?php echo esc_attr($post_id); ?>">
                                            👁 View Meta
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="almaseo-no-posts">
                                    <p>No posts found.</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- View Meta Modal -->
        <div id="almaseo-view-meta-modal" class="almaseo-modal">
            <div class="almaseo-modal-content">
                <div class="almaseo-modal-header">
                    <h3>Post SEO Meta Data</h3>
                    <span class="almaseo-modal-close">&times;</span>
                </div>
                <div class="almaseo-modal-body">
                    <div id="almaseo-meta-content"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Filter functionality
        $('#almaseo-status-filter').on('change', function() {
            var filter = $(this).val();
            $('.almaseo-post-row').each(function() {
                var row = $(this);
                if (filter === 'all' || row.data('status') === filter) {
                    row.show();
                } else {
                    row.hide();
                }
            });
        });
        
        // View Meta button
        $('.almaseo-view-meta-btn').on('click', function() {
            var postId = $(this).data('post-id');
            var postTitle = $(this).closest('tr').find('td:first a').text();
            
            // Show modal with post meta data
            $('#almaseo-meta-content').html('<p>Loading meta data for: ' + postTitle + '</p>');
            $('#almaseo-view-meta-modal').show();
            
            // In a real implementation, you would fetch the meta data via AJAX
            // For now, we'll show a placeholder
            setTimeout(function() {
                $('#almaseo-meta-content').html(
                    '<div class="almaseo-meta-details">' +
                    '<h4>SEO Meta Data for: ' + postTitle + '</h4>' +
                    '<p><strong>SEO Title:</strong> <span class="meta-value">Sample SEO Title</span></p>' +
                    '<p><strong>Meta Description:</strong> <span class="meta-value">Sample meta description for this post...</span></p>' +
                    '<p><strong>Schema Type:</strong> <span class="meta-value">Article</span></p>' +
                    '<p><strong>Keywords:</strong> <span class="meta-value">keyword1, keyword2, keyword3</span></p>' +
                    '</div>'
                );
            }, 500);
        });
        
        // Close modal
        $('.almaseo-modal-close').on('click', function() {
            $('#almaseo-view-meta-modal').hide();
        });
        
        // Close modal when clicking outside
        $(window).on('click', function(e) {
            if (e.target === $('#almaseo-view-meta-modal')[0]) {
                $('#almaseo-view-meta-modal').hide();
            }
        });
        
        // Reoptimize button (placeholder for future implementation)
        $('.almaseo-reoptimize-btn').on('click', function() {
            var postId = $(this).data('post-id');
            alert('Reoptimize functionality will be implemented in a future update for post ID: ' + postId);
        });
        
        // Rewrite button (placeholder for future implementation)
        $('.almaseo-rewrite-btn').on('click', function() {
            var postId = $(this).data('post-id');
            alert('Rewrite functionality will be implemented in a future update for post ID: ' + postId);
        });
    });
    </script>
    <?php
    
    wp_reset_postdata();
}

// AJAX handler for getting Google Search Console keywords
add_action('wp_ajax_seo_playground_get_gsc_keywords', 'seo_playground_ajax_get_gsc_keywords');
function seo_playground_ajax_get_gsc_keywords() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get post data
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $permalink = isset($_POST['permalink']) ? esc_url_raw($_POST['permalink']) : '';
    $site_url = isset($_POST['site_url']) ? esc_url_raw($_POST['site_url']) : '';
    
    // Validate required data
    if (!$post_id || !$permalink || !$site_url) {
        wp_send_json_error(array('message' => 'Missing required data'));
        return;
    }
    
    // Check if we have cached data (24-hour cache)
    $cached_data = get_transient('gsc_keywords_' . $post_id);
    if ($cached_data !== false) {
        wp_send_json_success($cached_data);
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_api_key', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'permalink' => $permalink,
        'post_id' => $post_id,
        'site_url' => $site_url
    );
    
    // Make API request to AlmaSEO
    $response = wp_remote_post('https://app.almaseo.com/api/v1/gsc/keywords', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Failed to connect to AlmaSEO API: ' . $response->get_error_message()));
        return;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from AlmaSEO API'));
        return;
    }
    
    if (isset($data['error'])) {
        wp_send_json_error(array('message' => $data['error']));
        return;
    }
    
    // Validate response structure
    if (!isset($data['keywords']) || !is_array($data['keywords'])) {
        wp_send_json_error(array('message' => 'No keyword data received'));
        return;
    }
    
    // Cache the data for 24 hours
    $cache_data = array(
        'keywords' => $data['keywords'],
        'date_range' => isset($data['date_range']) ? $data['date_range'] : 'Last 28 days',
        'timestamp' => current_time('mysql'),
        'message' => 'GSC keywords loaded successfully'
    );
    
    set_transient('gsc_keywords_' . $post_id, $cache_data, 24 * HOUR_IN_SECONDS);
    
    // Also save to post meta for persistence
    update_post_meta($post_id, '_seo_playground_gsc_keywords', sanitize_textarea_field(json_encode($cache_data)));
    update_post_meta($post_id, '_seo_playground_gsc_keywords_timestamp', current_time('mysql'));
    
    wp_send_json_success($cache_data);
}

// AJAX handler for refreshing GSC keywords (clears cache)
add_action('wp_ajax_seo_playground_refresh_gsc_keywords', 'seo_playground_ajax_refresh_gsc_keywords');
function seo_playground_ajax_refresh_gsc_keywords() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Get post ID
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID'));
        return;
    }
    
    // Clear the cache
    delete_transient('gsc_keywords_' . $post_id);
    
    wp_send_json_success(array('message' => 'Cache cleared successfully'));
}

// AJAX handler for getting schema analysis
add_action('wp_ajax_seo_playground_get_schema_analysis', 'seo_playground_ajax_get_schema_analysis');
function seo_playground_ajax_get_schema_analysis() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    // Get post data
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $site_url = isset($_POST['site_url']) ? esc_url_raw($_POST['site_url']) : '';
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $content = isset($_POST['content']) ? sanitize_textarea_field($_POST['content']) : '';
    $schema_type = isset($_POST['schema_type']) ? sanitize_text_field($_POST['schema_type']) : '';
    
    // Validate required data
    if (!$post_id || !$site_url || !$schema_type || $schema_type === 'None') {
        wp_send_json_error(array('message' => 'Missing required data or invalid schema type'));
        return;
    }
    
    // Check if we have cached data (12-hour cache for schema analysis)
    $cached_data = get_transient('schema_analysis_' . $post_id . '_' . md5($schema_type));
    if ($cached_data !== false) {
        wp_send_json_success($cached_data);
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_api_key', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'post_id' => $post_id,
        'site_url' => $site_url,
        'title' => $title,
        'content' => $content,
        'schema_type' => $schema_type
    );
    
    // Make API request to AlmaSEO
    $response = wp_remote_post('https://app.almaseo.com/api/v1/schema/analyze', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Failed to connect to AlmaSEO API: ' . $response->get_error_message()));
        return;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from AlmaSEO API'));
        return;
    }
    
    if (isset($data['error'])) {
        wp_send_json_error(array('message' => $data['error']));
        return;
    }
    
    // Validate response structure
    if (!isset($data['analysis']) || empty($data['analysis'])) {
        wp_send_json_error(array('message' => 'No analysis received from API'));
        return;
    }
    
    // Cache the data for 12 hours
    $cache_data = array(
        'analysis' => $data['analysis'],
        'schema_type' => $schema_type,
        'timestamp' => current_time('mysql'),
        'message' => 'Schema analysis completed successfully'
    );
    
    set_transient('schema_analysis_' . $post_id . '_' . md5($schema_type), $cache_data, 12 * HOUR_IN_SECONDS);
    
    // Also save to post meta for persistence
    update_post_meta($post_id, '_seo_playground_schema_analysis', sanitize_textarea_field(json_encode($cache_data)));
    update_post_meta($post_id, '_seo_playground_schema_analysis_timestamp', current_time('mysql'));
    
    wp_send_json_success($cache_data);
}

// AJAX handler for refreshing schema analysis (clears cache)
add_action('wp_ajax_seo_playground_refresh_schema_analysis', 'seo_playground_ajax_refresh_schema_analysis');
function seo_playground_ajax_refresh_schema_analysis() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    // Get post ID and schema type
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $schema_type = isset($_POST['schema_type']) ? sanitize_text_field($_POST['schema_type']) : '';
    
    if (!$post_id || !$schema_type || $schema_type === 'None') {
        wp_send_json_error(array('message' => 'Invalid post ID or schema type'));
        return;
    }
    
    // Clear the cache
    delete_transient('schema_analysis_' . $post_id . '_' . md5($schema_type));
    
    wp_send_json_success(array('message' => 'Cache cleared successfully'));
}

// AJAX handler for getting meta health analysis
add_action('wp_ajax_seo_playground_get_meta_health', 'seo_playground_ajax_get_meta_health');
function seo_playground_ajax_get_meta_health() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    // Get post data
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $site_url = isset($_POST['site_url']) ? esc_url_raw($_POST['site_url']) : '';
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    $focus_keyword = isset($_POST['focus_keyword']) ? sanitize_text_field($_POST['focus_keyword']) : '';
    
    // Validate required data
    if (!$post_id || !$site_url || !$title || !$description) {
        wp_send_json_error(array('message' => 'Missing required data: title and description are required'));
        return;
    }
    
    // Check if we have cached data (12-hour cache for meta health)
    $cached_data = get_transient('meta_health_' . $post_id . '_' . md5($title . $description . $focus_keyword));
    if ($cached_data !== false) {
        wp_send_json_success($cached_data);
        return;
    }
    
    // Get API key
    $api_key = get_option('almaseo_api_key', '');
    if (!$api_key) {
        wp_send_json_error(array('message' => 'API key not found'));
        return;
    }
    
    // Prepare request data
    $request_data = array(
        'post_id' => $post_id,
        'site_url' => $site_url,
        'title' => $title,
        'description' => $description,
        'focus_keyword' => $focus_keyword
    );
    
    // Make API request to AlmaSEO
    $response = wp_remote_post('https://app.almaseo.com/api/v1/meta/analyze', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type' => 'application/json'
        ),
        'body' => json_encode($request_data),
        'timeout' => 30
    ));
    
    if (is_wp_error($response)) {
        wp_send_json_error(array('message' => 'Failed to connect to AlmaSEO API: ' . $response->get_error_message()));
        return;
    }
    
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);
    
    if (!$data) {
        wp_send_json_error(array('message' => 'Invalid response from AlmaSEO API'));
        return;
    }
    
    if (isset($data['error'])) {
        wp_send_json_error(array('message' => $data['error']));
        return;
    }
    
    // Validate response structure
    if (!isset($data['score']) || !isset($data['feedback'])) {
        wp_send_json_error(array('message' => 'Invalid response structure from API'));
        return;
    }
    
    // Validate score range
    $score = intval($data['score']);
    if ($score < 0 || $score > 100) {
        wp_send_json_error(array('message' => 'Invalid score received from API'));
        return;
    }
    
    // Cache the data for 12 hours
    $cache_data = array(
        'score' => $score,
        'feedback' => $data['feedback'],
        'title' => $title,
        'description' => $description,
        'focus_keyword' => $focus_keyword,
        'timestamp' => current_time('mysql'),
        'message' => 'Meta health analysis completed successfully'
    );
    
    set_transient('meta_health_' . $post_id . '_' . md5($title . $description . $focus_keyword), $cache_data, 12 * HOUR_IN_SECONDS);
    
    // Also save to post meta for persistence
    update_post_meta($post_id, '_seo_playground_meta_score', $score);
    update_post_meta($post_id, '_seo_playground_meta_feedback', sanitize_textarea_field($data['feedback']));
    update_post_meta($post_id, '_seo_playground_meta_timestamp', current_time('mysql'));
    
    wp_send_json_success($cache_data);
}

// AJAX handler for refreshing meta health analysis (clears cache)
add_action('wp_ajax_seo_playground_refresh_meta_health', 'seo_playground_ajax_refresh_meta_health');
function seo_playground_ajax_refresh_meta_health() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check if user is connected to AlmaSEO
    if (!seo_playground_is_alma_connected()) {
        wp_send_json_error(array('message' => 'Not connected to AlmaSEO'));
        return;
    }
    
    // Check user capabilities
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    // Get post ID and metadata
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $title = isset($_POST['title']) ? sanitize_text_field($_POST['title']) : '';
    $description = isset($_POST['description']) ? sanitize_textarea_field($_POST['description']) : '';
    $focus_keyword = isset($_POST['focus_keyword']) ? sanitize_text_field($_POST['focus_keyword']) : '';
    
    if (!$post_id || !$title || !$description) {
        wp_send_json_error(array('message' => 'Invalid post ID or missing metadata'));
        return;
    }
    
    // Clear the cache
    delete_transient('meta_health_' . $post_id . '_' . md5($title . $description . $focus_keyword));
    
    wp_send_json_success(array('message' => 'Cache cleared successfully'));
}

// AJAX handler for marking post as reoptimized
add_action('wp_ajax_seo_playground_mark_as_reoptimized', 'seo_playground_ajax_mark_as_reoptimized');
function seo_playground_ajax_mark_as_reoptimized() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check user capabilities
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    // Get post ID
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID'));
        return;
    }
    
    // Update the last refresh timestamp
    $current_time = current_time('mysql');
    update_post_meta($post_id, '_seo_playground_last_refresh', $current_time);
    
    // Also update the post modified date to reflect the refresh
    wp_update_post(array(
        'ID' => $post_id,
        'post_modified' => $current_time,
        'post_modified_gmt' => get_gmt_from_date($current_time)
    ));
    
    wp_send_json_success(array(
        'message' => 'Post marked as reoptimized successfully',
        'timestamp' => $current_time,
        'formatted_time' => date('F j, Y \a\t g:i A', strtotime($current_time))
    ));
}

// AJAX handler for saving SEO note
add_action('wp_ajax_seo_playground_save_note', 'seo_playground_ajax_save_note');
function seo_playground_ajax_save_note() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check user capabilities
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    // Get post ID and note content
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    $note_content = isset($_POST['note_content']) ? sanitize_textarea_field($_POST['note_content']) : '';
    
    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID'));
        return;
    }
    
    // Save the note content
    update_post_meta($post_id, '_seo_playground_seo_note', $note_content);
    
    // Save the timestamp
    $current_time = current_time('mysql');
    update_post_meta($post_id, '_seo_playground_seo_note_timestamp', $current_time);
    
    wp_send_json_success(array(
        'message' => 'Note saved successfully',
        'timestamp' => $current_time,
        'formatted_time' => date('F j, Y \a\t g:i A', strtotime($current_time)),
        'character_count' => strlen($note_content)
    ));
}

// AJAX handler for getting SEO note
add_action('wp_ajax_seo_playground_get_note', 'seo_playground_ajax_get_note');
function seo_playground_ajax_get_note() {
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'seo_playground_nonce')) {
        wp_die('Security check failed');
    }
    
    // Check user capabilities
    if (!current_user_can('edit_posts')) {
        wp_send_json_error(array('message' => 'Insufficient permissions'));
        return;
    }
    
    // Get post ID
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id) {
        wp_send_json_error(array('message' => 'Invalid post ID'));
        return;
    }
    
    // Get the note content and timestamp
    $note_content = get_post_meta($post_id, '_seo_playground_seo_note', true);
    $timestamp = get_post_meta($post_id, '_seo_playground_seo_note_timestamp', true);
    
    wp_send_json_success(array(
        'note_content' => $note_content,
        'timestamp' => $timestamp,
        'formatted_time' => $timestamp ? date('F j, Y \a\t g:i A', strtotime($timestamp)) : '',
        'character_count' => strlen($note_content)
    ));
}

// --- FRONT-END META TAG RENDERING ---

// Filter the document title
add_filter('document_title_parts', 'almaseo_filter_document_title', 10);
function almaseo_filter_document_title($title) {
    if (is_singular()) {
        global $post;
        $seo_title = get_post_meta($post->ID, '_almaseo_title', true);
        if (!empty($seo_title)) {
            $title['title'] = $seo_title;
        }
    }
    return $title;
}

// Also filter pre_get_document_title for themes that use it
add_filter('pre_get_document_title', 'almaseo_pre_get_document_title', 10);
function almaseo_pre_get_document_title($title) {
    if (is_singular()) {
        global $post;
        $seo_title = get_post_meta($post->ID, '_almaseo_title', true);
        if (!empty($seo_title)) {
            return $seo_title;
        }
    }
    return $title;
}

// Hook to add meta tags to the front-end
add_action('wp_head', 'almaseo_render_meta_tags', 1);
function almaseo_render_meta_tags() {
    if (!is_singular()) {
        return;
    }
    
    global $post;
    $post_id = $post->ID;
    
    // Render Meta Robots tag
    $robots_parts = array();
    
    // Get robot settings with defaults
    $index = get_post_meta($post_id, '_almaseo_robots_index', true);
    $follow = get_post_meta($post_id, '_almaseo_robots_follow', true);
    $archive = get_post_meta($post_id, '_almaseo_robots_archive', true);
    $snippet = get_post_meta($post_id, '_almaseo_robots_snippet', true);
    $imageindex = get_post_meta($post_id, '_almaseo_robots_imageindex', true);
    $translate = get_post_meta($post_id, '_almaseo_robots_translate', true);
    
    // Build robots content (only include non-default values)
    if ($index === 'noindex') $robots_parts[] = 'noindex';
    else $robots_parts[] = 'index';
    
    if ($follow === 'nofollow') $robots_parts[] = 'nofollow';
    else $robots_parts[] = 'follow';
    
    if ($archive === 'noarchive') $robots_parts[] = 'noarchive';
    if ($snippet === 'nosnippet') $robots_parts[] = 'nosnippet';
    if ($imageindex === 'noimageindex') $robots_parts[] = 'noimageindex';
    if ($translate === 'notranslate') $robots_parts[] = 'notranslate';
    
    if (!empty($robots_parts)) {
        echo '<meta name="robots" content="' . esc_attr(implode(', ', $robots_parts)) . '" />' . "\n";
    }
    
    // Render Canonical URL
    $canonical_url = get_post_meta($post_id, '_almaseo_canonical_url', true);
    if (empty($canonical_url)) {
        $canonical_url = get_permalink($post_id);
    }
    echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
    
    // Get SEO title and description for fallback
    $seo_title = get_post_meta($post_id, '_almaseo_title', true) ?: get_the_title($post_id);
    $seo_description = get_post_meta($post_id, '_almaseo_description', true) ?: wp_trim_words($post->post_content, 30);
    
    // Output standard meta description tag
    if (!empty($seo_description)) {
        echo '<meta name="description" content="' . esc_attr($seo_description) . '" />' . "\n";
    }
    
    // Render Open Graph meta tags
    $og_title = get_post_meta($post_id, '_almaseo_og_title', true) ?: $seo_title;
    $og_description = get_post_meta($post_id, '_almaseo_og_description', true) ?: $seo_description;
    $og_image = get_post_meta($post_id, '_almaseo_og_image', true) ?: get_the_post_thumbnail_url($post_id, 'large');
    
    echo '<meta property="og:type" content="article" />' . "\n";
    echo '<meta property="og:title" content="' . esc_attr($og_title) . '" />' . "\n";
    echo '<meta property="og:description" content="' . esc_attr($og_description) . '" />' . "\n";
    echo '<meta property="og:url" content="' . esc_url($canonical_url) . '" />' . "\n";
    echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";
    
    if ($og_image) {
        echo '<meta property="og:image" content="' . esc_url($og_image) . '" />' . "\n";
    }
    
    // Render Twitter Card meta tags
    $twitter_card = get_post_meta($post_id, '_almaseo_twitter_card', true) ?: 'summary_large_image';
    $twitter_title = get_post_meta($post_id, '_almaseo_twitter_title', true) ?: $og_title;
    $twitter_description = get_post_meta($post_id, '_almaseo_twitter_description', true) ?: $og_description;
    
    echo '<meta name="twitter:card" content="' . esc_attr($twitter_card) . '" />' . "\n";
    echo '<meta name="twitter:title" content="' . esc_attr($twitter_title) . '" />' . "\n";
    echo '<meta name="twitter:description" content="' . esc_attr($twitter_description) . '" />' . "\n";
    
    if ($og_image) {
        echo '<meta name="twitter:image" content="' . esc_url($og_image) . '" />' . "\n";
    }
    
    // Render JSON-LD Schema Markup
    $schema_type = get_post_meta($post_id, '_almaseo_schema_type', true) ?: 'Article';
    
    if ($schema_type === 'Article') {
        $author_name = get_post_meta($post_id, '_almaseo_article_author', true) ?: get_the_author_meta('display_name', $post->post_author);
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $seo_title,
            'description' => $seo_description,
            'author' => array(
                '@type' => 'Person',
                'name' => $author_name
            ),
            'datePublished' => get_the_date('c', $post_id),
            'dateModified' => get_the_modified_date('c', $post_id),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => get_site_icon_url()
                )
            ),
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => $canonical_url
            )
        );
        
        if ($og_image) {
            $schema['image'] = $og_image;
        }
        
        echo '<script type="application/ld+json">' . "\n";
        echo wp_json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n";
        echo '</script>' . "\n";
    }
}
// WooCommerce Settings Page
function almaseo_woocommerce_settings_page() {
    if (\!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    
    // Include the WooCommerce settings page
    $settings_file = plugin_dir_path(__FILE__) . 'admin/pages/settings-woo.php';
    if (file_exists($settings_file)) {
        include $settings_file;
    } else {
        echo '<div class="notice notice-error"><p>' . __('WooCommerce SEO settings file not found.', 'almaseo') . '</p></div>';
    }
}
