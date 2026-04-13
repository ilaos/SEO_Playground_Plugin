<?php
/*
Plugin Name: AlmaSEO SEO Playground
Plugin URI: https://almaseo.com/
Description: Professional SEO optimization plugin with AI-powered content generation, comprehensive keyword analysis, schema markup, and real-time SEO insights. Features 5 polished tabs for complete SEO management.
Version: 1.6.7
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

// --- Prevent duplicate loading if another version of this plugin is active ---
// Check both the constant (for v8.9.18+ coexistence) and a canary class
// (for older versions that don't set the constant).
if ( defined( 'ALMASEO_PLAYGROUND_LOADED' ) || class_exists( 'AlmaSEO_Schema_Scrubber_Safe' ) ) {
    return;
}
define( 'ALMASEO_PLAYGROUND_LOADED', true );

// --- AIOSEO / third-party SEO plugin coexistence (v8.9.12) ---
// When a conflicting SEO plugin is active and this is a frontend request,
// bail out completely — only define constants needed for activation hooks.
// This prevents any code in our plugin from interfering with AIOSEO's
// schema generation which crashes at Helpers.php:86.
// Detect REST requests early — REST_REQUEST constant isn't defined until parse_request,
// which is after plugins load. Check the URL instead.
$almaseo_is_rest = ( defined( 'REST_REQUEST' ) && REST_REQUEST )
    || ( isset( $_SERVER['REQUEST_URI'] ) && ( strpos( $_SERVER['REQUEST_URI'], '/wp-json/' ) !== false || strpos( $_SERVER['REQUEST_URI'], '?rest_route=' ) !== false ) );

if ( ! is_admin() && ! wp_doing_ajax() && ! wp_doing_cron() && ! $almaseo_is_rest && ! ( defined( 'WP_CLI' ) && WP_CLI ) ) {
    $almaseo_active_plugins = (array) get_option( 'active_plugins', array() );
    $almaseo_seo_conflict   = false;
    foreach ( $almaseo_active_plugins as $almaseo_p ) {
        if ( strpos( $almaseo_p, 'all-in-one-seo-pack' ) !== false
            || strpos( $almaseo_p, 'wordpress-seo' ) !== false
            || strpos( $almaseo_p, 'seo-by-rank-math' ) !== false
            || strpos( $almaseo_p, 'wp-seopress' ) !== false
            || strpos( $almaseo_p, 'autodescription' ) !== false ) {
            $almaseo_seo_conflict = true;
            break;
        }
    }
    if ( $almaseo_seo_conflict ) {
        // Define only the bare minimum constants, then stop loading.
        if ( ! defined( 'ALMASEO_PLUGIN_VERSION' ) ) define( 'ALMASEO_PLUGIN_VERSION', '1.6.7' );
        if ( ! defined( 'ALMASEO_PATH' ) )           define( 'ALMASEO_PATH', plugin_dir_path( __FILE__ ) );
        if ( ! defined( 'ALMASEO_URL' ) )            define( 'ALMASEO_URL', plugin_dir_url( __FILE__ ) );
        if ( ! defined( 'ALMASEO_MAIN_FILE' ) )      define( 'ALMASEO_MAIN_FILE', __FILE__ );
        return; // <-- Stop loading the entire plugin on frontend
    }
}

// STANDARDIZED Plugin constants - guarded for safe coexistence with AlmaSEO Connector
if (!defined('ALMASEO_MAIN_FILE'))       define('ALMASEO_MAIN_FILE', __FILE__);
if (!defined('ALMASEO_PATH'))            define('ALMASEO_PATH', plugin_dir_path(__FILE__));
if (!defined('ALMASEO_URL'))             define('ALMASEO_URL', plugin_dir_url(__FILE__));
if (!defined('ALMASEO_PLUGIN_VERSION'))  define('ALMASEO_PLUGIN_VERSION', '1.6.5');
if (!defined('ALMASEO_VERSION'))         define('ALMASEO_VERSION', '6.5.0');
if (!defined('ALMASEO_API_NAMESPACE'))   define('ALMASEO_API_NAMESPACE', 'almaseo/v1');
if (!defined('ALMASEO_API_BASE_URL'))    define('ALMASEO_API_BASE_URL', 'https://app.almaseo.com/api/v1');

// Legacy constants for backwards compatibility
if (!defined('ALMASEO_PLUGIN_URL'))  define('ALMASEO_PLUGIN_URL', ALMASEO_URL);
if (!defined('ALMASEO_PLUGIN_DIR'))  define('ALMASEO_PLUGIN_DIR', ALMASEO_PATH);
if (!defined('ALMASEO_PLUGIN_FILE')) define('ALMASEO_PLUGIN_FILE', ALMASEO_MAIN_FILE);

// --- Suppress the Connector plugin's "Welcome" banner ---
// The Connector's welcome notice is an anonymous function we can't unhook,
// but it checks this user meta before showing. Setting it silences the banner.
add_action( 'admin_init', function () {
    if ( function_exists( 'almaseo_detect_active_connector' ) && almaseo_detect_active_connector() ) {
        if ( ! get_user_meta( get_current_user_id(), 'almaseo_connector_dismissed_notice', true ) ) {
            update_user_meta( get_current_user_id(), 'almaseo_connector_dismissed_notice', 1 );
        }
    }
} );

// Note: third-party SEO plugin coexistence is handled by the early return
// at the top of this file. If we reach this point, no conflicting SEO
// plugin is active on the frontend (or this is an admin/AJAX/REST request).

// Include License & Tier Helper (centralized license checking)
// This MUST be loaded early before any feature modules that check licensing
if (file_exists(plugin_dir_path(__FILE__) . 'includes/license/license-helper.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/license/license-helper.php';
}

// Include Locked Feature UI Helper (for Pro feature upsells)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/license/locked-ui.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/license/locked-ui.php';
}

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

// Include Advanced Schema (Pro feature)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/schema/schema-advanced-output.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/schema/schema-advanced-output.php';
    add_action('wp_head', 'almaseo_output_advanced_schema', 40);
}

// Skip loading heavy features during activation to prevent memory issues
$is_activating = isset($_GET['action']) && sanitize_key($_GET['action']) === 'activate';

// Include Evergreen feature (using minimal safe loader to prevent crashes)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/evergreen/evergreen-loader-minimal-safe.php')) {
    // Only load if not during activation
    if (!$is_activating) {
        require_once plugin_dir_path(__FILE__) . 'includes/evergreen/evergreen-loader-minimal-safe.php';
    }
} elseif (file_exists(plugin_dir_path(__FILE__) . 'includes/evergreen/evergreen-loader-safe.php')) {
    // Fallback to safe loader if minimal not available
    if (!$is_activating) {
        require_once plugin_dir_path(__FILE__) . 'includes/evergreen/evergreen-loader-safe.php';
    }
}

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

// Include LLM Optimization REST API (v6.5.0+)
if (!$is_activating && file_exists(plugin_dir_path(__FILE__) . 'includes/llm/llm-rest.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/llm/llm-rest.php';
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

// Include Security Functions (v6.0.2+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/security.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/security.php';
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

// Include Breadcrumbs feature (v7.0.0+) - Free feature
if (file_exists(plugin_dir_path(__FILE__) . 'includes/breadcrumbs/breadcrumbs-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/breadcrumbs/breadcrumbs-loader.php';
}

// Include Internal Links module (v7.0.0+) - Hybrid (Free: suggestions, Pro: auto-insert)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/internal-links/internal-links-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/internal-links/internal-links-loader.php';
}

// Include Content Refresh Drafts module (v7.1.0+) - Pro feature
if (file_exists(plugin_dir_path(__FILE__) . 'includes/refresh-drafts/refresh-drafts-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/refresh-drafts/refresh-drafts-loader.php';
}

// Include Refresh Queue module (v7.2.0+) - Pro feature
if (file_exists(plugin_dir_path(__FILE__) . 'includes/refresh-queue/refresh-queue-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/refresh-queue/refresh-queue-loader.php';
}

// Include Date Hygiene Scanner module (v7.3.0+) - Pro feature
if (file_exists(plugin_dir_path(__FILE__) . 'includes/date-hygiene/date-hygiene-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/date-hygiene/date-hygiene-loader.php';
}

// Include E-E-A-T Enforcement module (v7.4.0+) - Pro feature
if (file_exists(plugin_dir_path(__FILE__) . 'includes/eeat/eeat-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/eeat/eeat-loader.php';
}

// Include GSC Monitor module (v7.5.0+) - Pro feature
if (file_exists(plugin_dir_path(__FILE__) . 'includes/gsc-monitor/gsc-monitor-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/gsc-monitor/gsc-monitor-loader.php';
}

// Include Schema Drift Monitor module (v7.8.0+) - Pro feature
if (file_exists(plugin_dir_path(__FILE__) . 'includes/schema-drift/schema-drift-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/schema-drift/schema-drift-loader.php';
}

// Include Featured Snippet Targeting module (v7.9.0+) - Pro feature
if (file_exists(plugin_dir_path(__FILE__) . 'includes/snippet-targets/snippet-targets-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/snippet-targets/snippet-targets-loader.php';
}

// Include Role Manager (v8.0.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/admin/role-manager.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin/role-manager.php';
    AlmaSEO_Role_Manager::init();
}

// Include Webmaster Verification Codes (v8.0.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/admin/verification-codes.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin/verification-codes.php';
    AlmaSEO_Verification_Codes::init();
}

// Include RSS Feed Controls (v8.0.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/admin/rss-controls.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin/rss-controls.php';
    AlmaSEO_RSS_Controls::init();
}

// Include LLMs.txt Management (v8.0.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/llms-txt/llms-txt-controller.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/llms-txt/llms-txt-controller.php';
    require_once plugin_dir_path(__FILE__) . 'includes/llms-txt/llms-txt-generator.php';
    AlmaSEO_LLMS_Txt_Controller::get_instance();
}

// Include Search Appearance module (v8.0.0+) - Title templates, smart tags, per-type settings
if (file_exists(plugin_dir_path(__FILE__) . 'includes/search-appearance/search-appearance-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/search-appearance/search-appearance-loader.php';
}

// Include Crawl Optimization (v8.4.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/admin/crawl-optimization.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin/crawl-optimization.php';
    AlmaSEO_Crawl_Optimization::init();
}

// Include Cornerstone Content (v8.4.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/admin/cornerstone-content.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin/cornerstone-content.php';
    AlmaSEO_Cornerstone_Content::init();
}

// Include Cornerstone REST (Dashboard Enhanced) (v8.5.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/admin/cornerstone-rest.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin/cornerstone-rest.php';
    AlmaSEO_Cornerstone_REST::init();
}

// Include Image SEO (v8.4.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/admin/image-seo.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin/image-seo.php';
    AlmaSEO_Image_SEO::init();
}

// Include .htaccess Editor (v8.4.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/admin/htaccess-editor.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin/htaccess-editor.php';
    AlmaSEO_Htaccess_Editor::get_instance();
}

// Include Link Attributes for Block Editor (v8.4.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/blocks/link-attributes.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/blocks/link-attributes.php';
    AlmaSEO_Link_Attributes::init();
}

// Include Image SEO REST (Dashboard Enhanced) (v8.5.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/admin/image-seo-rest.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin/image-seo-rest.php';
    AlmaSEO_Image_SEO_REST::init();
}

// Include Google Keyword Suggestions (v8.5.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/admin/keyword-suggestions.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin/keyword-suggestions.php';
    AlmaSEO_Keyword_Suggestions::init();
}

// Include Keyword Suggestions REST (Dashboard Enhanced) (v8.5.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/admin/keyword-suggestions-rest.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin/keyword-suggestions-rest.php';
    AlmaSEO_Keyword_Suggestions_REST::init();
}

// Include Google Analytics Integration (v8.5.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/analytics/analytics-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/analytics/analytics-loader.php';
}

// Include Local Business Schema Types (v8.5.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/schema/local-business-types.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/schema/local-business-types.php';
}

// Include Tag Validator (used by rendering layer + import module for foreign token detection)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/import/import-tag-validator.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/import/import-tag-validator.php';
}

// Include AIOSEO mapper early so the validator can call convert_tags() on frontend
if (file_exists(plugin_dir_path(__FILE__) . 'includes/import/import-mapper-aioseo.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/import/import-mapper-aioseo.php';
}

// Include Import/Migration module (v8.1.0+) - Import from Yoast, Rank Math, AIOSEO
if (file_exists(plugin_dir_path(__FILE__) . 'includes/import/import-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/import/import-loader.php';
}

// Include Setup Wizard (v8.2.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/admin/setup-wizard.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin/setup-wizard.php';
    AlmaSEO_Setup_Wizard::init();
}

// Include Gutenberg Blocks (v8.3.0+) - FAQ, Table of Contents
if (file_exists(plugin_dir_path(__FILE__) . 'includes/blocks/blocks-loader.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/blocks/blocks-loader.php';
}

// Include Documentation & Help page (v8.6.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/admin/documentation.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin/documentation.php';
    AlmaSEO_Documentation::init();
}

// Include Tier Labels — visual Free/Pro badges in sidebar (v8.6.0+)
if (file_exists(plugin_dir_path(__FILE__) . 'includes/admin/tier-labels.php')) {
    require_once plugin_dir_path(__FILE__) . 'includes/admin/tier-labels.php';
    AlmaSEO_Tier_Labels::init();
}

// Ensure almaseo_is_pro function exists as fallback
// This should rarely be reached since bulkmeta-loader.php defines it first
if (!function_exists('almaseo_is_pro')) {
    function almaseo_is_pro() {
        // Use centralized license helper as fallback
        // This ensures consistent behavior across all modules
        return almaseo_is_pro_active();
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

// --- CONNECTOR COEXISTENCE: Detect active AlmaSEO Connector and offer upgrade ---

/**
 * Detect if the AlmaSEO Connector plugin is active.
 * Returns the plugin basename (e.g. 'almaseo-connector/alma-seoconnector.php') or false.
 */
if (!function_exists('almaseo_detect_active_connector')) {
function almaseo_detect_active_connector() {
    $active_plugins = get_option('active_plugins', array());
    foreach ($active_plugins as $plugin) {
        if (preg_match('/^almaseo-connector[^\/]*\//', $plugin)) {
            return $plugin;
        }
    }
    return false;
}
} // end function_exists guard: almaseo_detect_active_connector

// Show upgrade notice when Connector is active alongside Playground
// --- POST-ONBOARDING: DEACTIVATE CONNECTOR NOTICE (non-dismissible, state-based) ---
add_action('admin_notices', function() {
    if (!current_user_can('activate_plugins')) {
        return;
    }
    $connector_plugin = almaseo_detect_active_connector();
    if (!$connector_plugin) {
        return;
    }

    $deactivate_url = wp_nonce_url(
        admin_url('admin-post.php?action=almaseo_deactivate_connector'),
        'almaseo_deactivate_connector'
    );
    ?>
    <div class="notice notice-error almaseo-connector-upgrade-notice" style="border-left: 4px solid #d63638; background: #fff2f2; padding: 16px 20px; margin: 15px 0;">
        <p style="margin: 0 0 8px 0; font-size: 15px; font-weight: 600;">
            <span style="color: #d63638; font-size: 18px; vertical-align: middle;">&#9888;</span>
            <strong style="color: #1d2327;"> Action Required &mdash; Deactivate the Connector Plugin</strong>
        </p>
        <p style="margin: 0 0 14px 0; color: #50575e; font-size: 13px; line-height: 1.6;">
            The <strong>AlmaSEO Connector</strong> and <strong>SEO Playground</strong> are both active.
            SEO Playground already includes everything the Connector does, plus a full SEO toolkit.
            Please deactivate the Connector to avoid potential conflicts. Your connection settings will be preserved.
        </p>
        <a href="<?php echo esc_url($deactivate_url); ?>" class="button button-primary" style="background: #d63638; border-color: #b32d2e; font-size: 13px; padding: 4px 16px; height: auto;">
            <?php esc_html_e('Deactivate Connector Now', 'almaseo'); ?>
        </a>
    </div>
    <?php
});

// Handle the one-click connector deactivation
add_action('admin_post_almaseo_deactivate_connector', function() {
    if (!current_user_can('activate_plugins')) {
        wp_die(__('You do not have permission to do this.', 'almaseo'));
    }
    check_admin_referer('almaseo_deactivate_connector');

    $connector_plugin = almaseo_detect_active_connector();
    if ($connector_plugin) {
        deactivate_plugins($connector_plugin);
    }

    wp_safe_redirect(admin_url('plugins.php?deactivate=true'));
    exit;
});

// --- SEO PLUGIN CONFLICT DETECTION ---

/**
 * Detect active third-party SEO plugins that may conflict with AlmaSEO.
 *
 * Runs on every admin page load (lightweight constant/class checks).
 * Returns an array of detected plugin names, empty if none found.
 *
 * @since 8.7.0
 * @return array
 */
if (!function_exists('almaseo_detect_conflicting_seo_plugins')) {
function almaseo_detect_conflicting_seo_plugins() {
    $detected = array();

    // Yoast SEO / Yoast SEO Premium
    if ( defined( 'WPSEO_VERSION' ) ) {
        $detected[] = 'Yoast SEO';
    }

    // Rank Math
    if ( class_exists( 'RankMath' ) ) {
        $detected[] = 'Rank Math';
    }

    // All in One SEO
    if ( defined( 'AIOSEO_VERSION' ) ) {
        $detected[] = 'All in One SEO';
    }

    // SEOPress
    if ( defined( 'SEOPRESS_VERSION' ) ) {
        $detected[] = 'SEOPress';
    }

    // The SEO Framework
    if ( defined( 'THE_SEO_FRAMEWORK_VERSION' ) ) {
        $detected[] = 'The SEO Framework';
    }

    // Squirrly SEO
    if ( defined( 'SQ_VERSION' ) || class_exists( 'SQ_Classes_ObjController' ) ) {
        $detected[] = 'Squirrly SEO';
    }

    // SmartCrawl (WPMU DEV)
    if ( class_exists( 'SmartCrawl_Loader' ) || defined( 'SMARTCRAWL_VERSION' ) ) {
        $detected[] = 'SmartCrawl';
    }

    // Slim SEO
    if ( defined( 'SLIM_SEO_VER' ) ) {
        $detected[] = 'Slim SEO';
    }

    return $detected;
}
} // end function_exists guard: almaseo_detect_conflicting_seo_plugins

// --- POST-ONBOARDING: SEO PLUGIN CONFLICT NOTICE (non-dismissible, state-based) ---
//
// Shows when another SEO plugin is active alongside AlmaSEO. Guides users to
// import their data first, then deactivate the old plugin. Disappears
// automatically once no conflicting SEO plugins remain active.
add_action( 'admin_notices', function () {
    if ( ! current_user_can( 'activate_plugins' ) ) {
        return;
    }
    if ( ! get_option( 'almaseo_setup_wizard_completed' ) ) {
        return;
    }

    $conflicts = almaseo_detect_conflicting_seo_plugins();
    if ( empty( $conflicts ) ) {
        return;
    }

    $plugin_list = '<strong>' . implode( '</strong>, <strong>', array_map( 'esc_html', $conflicts ) ) . '</strong>';
    $count       = count( $conflicts );
    $plugins_url = admin_url( 'plugins.php' );
    $import_url  = admin_url( 'admin.php?page=almaseo-import' );
    ?>
    <div class="notice notice-warning almaseo-seo-conflict-notice" style="border-left-color: #f0b849; padding: 14px 16px;">
        <p style="margin: 0 0 8px 0; font-size: 14px;">
            <strong style="color: #1d2327;">&#9888; Action Required &mdash; Import Your SEO Data &amp; Deactivate <?php echo esc_html( $count === 1 ? $conflicts[0] : 'Other SEO Plugins' ); ?></strong>
        </p>
        <p style="margin: 0 0 6px 0; color: #50575e;">
            <?php
            printf(
                /* translators: %s = plugin name(s) */
                esc_html__( 'We detected %s running alongside AlmaSEO. Running two SEO plugins at the same time causes duplicate meta tags, schema conflicts, and sitemap issues.', 'almaseo' ),
                $plugin_list
            );
            ?>
        </p>
        <p style="margin: 0 0 12px 0; color: #50575e;">
            <strong><?php esc_html_e( 'Step 1:', 'almaseo' ); ?></strong> <?php esc_html_e( 'Import your existing titles, descriptions, and keywords into AlmaSEO so nothing is lost.', 'almaseo' ); ?><br>
            <strong><?php esc_html_e( 'Step 2:', 'almaseo' ); ?></strong>
            <?php
            printf(
                /* translators: %s = plugin name(s) */
                esc_html__( 'Deactivate %s once the import is complete.', 'almaseo' ),
                esc_html( $count === 1 ? $conflicts[0] : 'the other SEO plugins' )
            );
            ?>
        </p>
        <p style="margin: 0;">
            <a href="<?php echo esc_url( $import_url ); ?>" class="button button-primary" style="background: #667eea; border-color: #5a6fd6;">
                <?php esc_html_e( 'Import SEO Data', 'almaseo' ); ?>
            </a>
            <a href="<?php echo esc_url( $plugins_url ); ?>" class="button" style="margin-left: 8px;">
                <?php esc_html_e( 'Go to Plugins', 'almaseo' ); ?>
            </a>
        </p>
    </div>
    <?php
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
    $current_time = current_time('U');
    $days_since_update = floor(($current_time - $post_modified) / (60 * 60 * 24));
    
    // Store admin notice
    $notice_data = array(
        'post_id' => $post_id,
        'post_title' => $post->post_title,
        'days_old' => $days_since_update,
        'edit_link' => ($edit_link = get_edit_post_link($post_id)) ? $edit_link . '#tab-seo-overview' : ''
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
    $type = isset($_POST['type']) ? sanitize_key($_POST['type']) : 'temp';

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
    // Inline JS to persist dismissal via AJAX with nonce
    ?>
    <script>
    jQuery(document).on('click', '.almaseo-content-reminder .notice-dismiss', function() {
        var postId = jQuery(this).closest('.almaseo-content-reminder').data('post-id');
        jQuery.post(ajaxurl, {
            action: 'almaseo_dismiss_content_reminder',
            nonce: <?php echo wp_json_encode(wp_create_nonce('almaseo_dismiss_content_reminder')); ?>,
            post_id: postId
        });
    });
    </script>
    <?php
    }
}
add_action('admin_notices', 'almaseo_display_content_reminders');

// AJAX handler to dismiss content reminders
if (!function_exists('almaseo_ajax_dismiss_content_reminder')) {
    function almaseo_ajax_dismiss_content_reminder() {
    check_ajax_referer('almaseo_dismiss_content_reminder', 'nonce');

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
    check_ajax_referer('almaseo_cancel_reminder', 'nonce');

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

if (!function_exists('almaseo_add_cors_headers')) {
    function almaseo_add_cors_headers($served, $result, $request, $server) {
        // Only add CORS headers for AlmaSEO endpoints
        $route = $request->get_route();
        // Fix: Add null check for $route before using strpos()
        if (!empty($route) && strpos($route, '/almaseo/v1/') !== false) {
            // Restrict CORS to known AlmaSEO origins
            $allowed_origins = array(
                'https://almaseo.com',
                'https://www.almaseo.com',
                'https://app.almaseo.com',
                'https://api.almaseo.com',
            );
            $allowed_origins = apply_filters('almaseo_cors_allowed_origins', $allowed_origins);

            $origin = isset($_SERVER['HTTP_ORIGIN']) ? esc_url_raw($_SERVER['HTTP_ORIGIN']) : '';

            if (in_array($origin, $allowed_origins, true)) {
                header('Access-Control-Allow-Origin: ' . $origin);
                header('Access-Control-Allow-Credentials: true');
                header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
                header('Access-Control-Allow-Headers: Authorization, Content-Type, X-WP-Nonce');

                // Handle preflight OPTIONS requests
                if ($request->get_method() === 'OPTIONS') {
                    header('Access-Control-Max-Age: 86400');
                    status_header(200);
                    return true;
                }
            }
        }
        return $served;
    }
}

// Check if Application Passwords are available
if (!function_exists('almaseo_check_app_passwords_available')) {
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
        'callback' => 'almaseo_handle_update_meta',
        'permission_callback' => 'almaseo_api_auth_check',
    ));

    register_rest_route(ALMASEO_API_NAMESPACE, '/submit-to-search-console', array(
        'methods'  => 'POST',
        'callback' => 'almaseo_reserved_endpoint',
        'permission_callback' => 'almaseo_api_auth_check',
    ));

    // JWT token regeneration endpoint
    register_rest_route(ALMASEO_API_NAMESPACE, '/regenerate-jwt', array(
        'methods'  => 'POST',
        'callback' => function() {
            delete_option('almaseo_jwt_secret');
            almaseo_get_jwt_secret();
            return array('success' => true, 'message' => 'JWT secret regenerated. All previous tokens are now invalid.');
        },
        'permission_callback' => 'almaseo_api_auth_check',
    ));
});

// Permission check: must be admin and secret must match
if (!function_exists('almaseo_permission_check')) {
    function almaseo_permission_check( $request ) {
        // Get the AlmaSEO secret (wp-config.php overrides DB)
        $secret = almaseo_get_secret();

        // Verify secret
        $provided_secret = $request->get_param('secret');
        if ( ! hash_equals( $secret, $provided_secret ) ) {
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
        if ( ! current_user_can('manage_options') ) {
            return new WP_Error('not_admin', 'You must be an administrator.', array('status' => 403));
        }

        // Check if Application Passwords are available
        $app_passwords_check = almaseo_check_app_passwords_available();
        if (is_wp_error($app_passwords_check)) {
            return $app_passwords_check;
        }

        return true;
    }
}

// Main callback: generate and return application password
if (!function_exists('almaseo_generate_app_password')) {
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
        $label = 'AlmaSEO AI ' . wp_date('Y-m-d H:i:s');
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
}

// JWT helper functions (shared with Connector — bypasses WAF restrictions on Authorization header)
if (!function_exists('almaseo_get_jwt_secret')) {
    function almaseo_get_jwt_secret() {
        $secret = get_option('almaseo_jwt_secret');
        if (!$secret) {
            $secret = wp_generate_password(64, true, true);
            add_option('almaseo_jwt_secret', $secret);
        }
        return $secret;
    }
}

if (!function_exists('almaseo_base64url_encode')) {
    function almaseo_base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}

if (!function_exists('almaseo_base64url_decode')) {
    function almaseo_base64url_decode($data) {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

if (!function_exists('almaseo_create_jwt')) {
    function almaseo_create_jwt($username, $expiry_days = 365) {
        $secret = almaseo_get_jwt_secret();

        $header = almaseo_base64url_encode(json_encode(array(
            'alg' => 'HS256',
            'typ' => 'JWT'
        )));

        $payload = almaseo_base64url_encode(json_encode(array(
            'iss' => get_site_url(),
            'sub' => $username,
            'iat' => time(),
            'exp' => time() + ($expiry_days * 86400),
            'scope' => 'almaseo_api'
        )));

        $signature = almaseo_base64url_encode(
            hash_hmac('sha256', $header . '.' . $payload, $secret, true)
        );

        return $header . '.' . $payload . '.' . $signature;
    }
}

if (!function_exists('almaseo_validate_jwt')) {
    function almaseo_validate_jwt($token) {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return new WP_Error('invalid_jwt', 'Malformed JWT token.', array('status' => 401));
        }

        list($header_b64, $payload_b64, $signature_b64) = $parts;

        // Verify signature
        $secret = almaseo_get_jwt_secret();
        $expected_signature = almaseo_base64url_encode(
            hash_hmac('sha256', $header_b64 . '.' . $payload_b64, $secret, true)
        );

        if (!hash_equals($expected_signature, $signature_b64)) {
            return new WP_Error('invalid_jwt', 'Invalid JWT signature.', array('status' => 401));
        }

        // Decode payload
        $payload = json_decode(almaseo_base64url_decode($payload_b64), true);
        if (!$payload) {
            return new WP_Error('invalid_jwt', 'Could not decode JWT payload.', array('status' => 401));
        }

        // Check expiry
        if (isset($payload['exp']) && $payload['exp'] < time()) {
            return new WP_Error('jwt_expired', 'JWT token has expired.', array('status' => 401));
        }

        // Check scope
        if (!isset($payload['scope']) || $payload['scope'] !== 'almaseo_api') {
            return new WP_Error('invalid_jwt', 'Invalid JWT scope.', array('status' => 401));
        }

        // Verify the user exists and has permissions
        $username = isset($payload['sub']) ? $payload['sub'] : '';
        if (!$username) {
            return new WP_Error('invalid_jwt', 'JWT missing subject.', array('status' => 401));
        }

        $user = get_user_by('login', $username);
        if (!$user) {
            return new WP_Error('invalid_jwt', 'JWT user not found.', array('status' => 401));
        }

        if (!user_can($user, 'edit_posts')) {
            return new WP_Error('invalid_jwt', 'JWT user lacks required permissions.', array('status' => 403));
        }

        return $username;
    }
}

if (!function_exists('almaseo_is_our_password')) {
    function almaseo_is_our_password($name) {
        return strpos($name, 'AlmaSEO AI') === 0 || strpos($name, 'AlmaSEO Connection') === 0;
    }
}

// API Authentication check for AlmaSEO backend
// Supports both Basic Auth (Application Passwords) and JWT (X-AlmaSEO-Token header)
if (!function_exists('almaseo_api_auth_check')) {
    function almaseo_api_auth_check($request) {
        // Method 1: Check for JWT token via custom header (bypasses WAF issues)
        $jwt_token = $request->get_header('X-AlmaSEO-Token');
        if ($jwt_token) {
            $result = almaseo_validate_jwt($jwt_token);
            if (is_wp_error($result)) {
                return $result;
            }
            return true;
        }

        // Method 2: Check for JWT token via query parameter (fallback)
        $jwt_param = $request->get_param('almaseo_token');
        if ($jwt_param) {
            $result = almaseo_validate_jwt($jwt_param);
            if (is_wp_error($result)) {
                return $result;
            }
            return true;
        }

        // Method 3: Traditional Basic Auth with Application Passwords
        $auth_header = $request->get_header('Authorization');
        if (!$auth_header) {
            return new WP_Error('no_auth', 'No authorization header or JWT token provided.', array('status' => 401));
        }

        if (strpos($auth_header, 'Basic ') === 0) {
            $auth_data = base64_decode(substr($auth_header, 6), true);
            if ($auth_data === false || strpos($auth_data, ':') === false) {
                return new WP_Error('invalid_auth', 'Malformed authorization header.', array('status' => 401));
            }
            list($username, $password) = explode(':', $auth_data, 2);

            // Check if application password authentication is available
            if (!function_exists('wp_authenticate_application_password')) {
                $user = wp_authenticate($username, $password);
            } else {
                $user = wp_authenticate_application_password(null, $username, $password);
            }

            if (is_wp_error($user)) {
                return new WP_Error('invalid_auth', 'Invalid credentials.', array('status' => 401));
            }

            // Check if the password was generated by our plugin
            if (!class_exists('WP_Application_Passwords')) {
                $app_passwords = array();
            } else {
                $app_passwords = WP_Application_Passwords::get_user_application_passwords($user->ID);
            }
            $is_almaseo_password = false;
            foreach ($app_passwords as $app_password) {
                if (almaseo_is_our_password($app_password['name'] ?? '')) {
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
}

// Verify connection endpoint callback
if (!function_exists('almaseo_verify_connection')) {
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
}

// Get site capabilities endpoint callback
if (!function_exists('almaseo_get_site_capabilities')) {
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
}

// Handle SEO metadata updates from the AlmaSEO dashboard
if (!function_exists('almaseo_handle_update_meta')) {
    function almaseo_handle_update_meta($request) {
        $post_id = $request->get_param('post_id');
        $meta    = $request->get_param('meta');

        if (!$post_id) {
            return new WP_Error('missing_post_id', 'Post ID is required.', array('status' => 400));
        }

        if (!$meta || !is_array($meta)) {
            return new WP_Error('missing_meta', 'Meta data is required.', array('status' => 400));
        }

        $post = get_post($post_id);
        if (!$post) {
            return new WP_Error('post_not_found', 'Post not found.', array('status' => 404));
        }

        // Map incoming field names to Playground post meta keys
        $field_map = array(
            'title'               => '_almaseo_title',
            'description'         => '_almaseo_description',
            'canonical_url'       => '_almaseo_canonical_url',
            'og_title'            => '_almaseo_og_title',
            'og_description'      => '_almaseo_og_description',
            'og_image'            => '_almaseo_og_image',
            'twitter_title'       => '_almaseo_twitter_title',
            'twitter_description' => '_almaseo_twitter_description',
            'twitter_card'        => '_almaseo_twitter_card',
        );

        $updated_fields = array();
        $errors         = array();

        foreach ($meta as $key => $value) {
            if (!isset($field_map[$key])) {
                continue;
            }

            $meta_key = $field_map[$key];

            // Sanitize based on field type
            if (strpos($key, '_image') !== false || $key === 'canonical_url') {
                $value = esc_url_raw($value);
            } elseif ($key === 'twitter_card') {
                $value = sanitize_text_field($value);
            } else {
                $value = wp_strip_all_tags($value);
            }

            $result = update_post_meta($post_id, $meta_key, $value);
            if ($result !== false) {
                $updated_fields[] = $key;
            } else {
                $errors[] = "Failed to update {$key}";
            }
        }

        // Handle robots directives if provided
        if (isset($meta['robots_noindex'])) {
            update_post_meta($post_id, '_almaseo_robots_index', $meta['robots_noindex'] ? 'noindex' : '');
            $updated_fields[] = 'robots_noindex';
        }
        if (isset($meta['robots_nofollow'])) {
            update_post_meta($post_id, '_almaseo_robots_follow', $meta['robots_nofollow'] ? 'nofollow' : '');
            $updated_fields[] = 'robots_nofollow';
        }

        return rest_ensure_response(array(
            'success'        => empty($errors),
            'method'         => 'almaseo_playground_direct',
            'updated_fields' => $updated_fields,
            'errors'         => $errors,
        ));
    }
}

// Reserved endpoint callback for future features
if (!function_exists('almaseo_reserved_endpoint')) {
    function almaseo_reserved_endpoint($request) {
        return new WP_Error(
            'endpoint_not_implemented',
            'This endpoint is reserved for future use.',
            array('status' => 501)
        );
    }
}

// --- SETTINGS PAGE & SECRET MANAGEMENT ---

// On activation, auto-generate secret if not present
if (!function_exists('almaseo_generate_secret_on_activation')) {
    function almaseo_generate_secret_on_activation() {
        if (!get_option('almaseo_secret') && !defined('ALMASEO_SECRET')) {
            $secret = wp_generate_password(32, true, true);
            add_option('almaseo_secret', $secret);
        }
    }
}
register_activation_hook(__FILE__, 'almaseo_generate_secret_on_activation');

// Get the AlmaSEO secret (wp-config.php overrides DB)
if (!function_exists('almaseo_get_secret')) {
    function almaseo_get_secret() {
        if (defined('ALMASEO_SECRET')) {
            return ALMASEO_SECRET;
        }
        return get_option('almaseo_secret', '');
    }
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
    
    // Add Setup Wizard submenu link so users can re-enter it
    add_submenu_page(
        'seo-playground',
        'Setup Wizard - SEO Playground by AlmaSEO',
        'Setup Wizard',
        'manage_options',
        'almaseo-setup-wizard',
        array( 'AlmaSEO_Setup_Wizard', 'render_page' )
    );

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
require_once plugin_dir_path(__FILE__) . 'admin/pages/welcome.php';

// Connection Settings Page
require_once plugin_dir_path(__FILE__) . 'admin/pages/connection-settings.php';

// Overview Page
require_once plugin_dir_path(__FILE__) . 'admin/pages/overview.php';

// --- SITE DISCOVERY AND CONNECTION SYNC FUNCTIONS ---

/**
 * Check if site is already registered in AlmaSEO Dashboard
 * @return array Registration status and details
 */
if (!function_exists('almaseo_check_dashboard_registration')) {
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
} // end function_exists guard: almaseo_check_dashboard_registration

/**
 * Auto-detect existing AlmaSEO Application Passwords
 * @return array|false Found password details or false
 */
if (!function_exists('almaseo_auto_detect_app_passwords')) {
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
} // end function_exists guard: almaseo_auto_detect_app_passwords

/**
 * Sync connection from dashboard if site is pre-registered
 */
if (!function_exists('almaseo_sync_from_dashboard')) {
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
} // end function_exists guard: almaseo_sync_from_dashboard

/**
 * Import connection details manually
 */
if (!function_exists('almaseo_import_connection_details')) {
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
} // end function_exists guard: almaseo_import_connection_details

/**
 * Test imported connection credentials
 */
if (!function_exists('almaseo_test_imported_connection')) {
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
} // end function_exists guard: almaseo_test_imported_connection

/**
 * Get comprehensive connection status
 */
if (!function_exists('almaseo_get_comprehensive_connection_status')) {
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
} // end function_exists guard: almaseo_get_comprehensive_connection_status

// --- REDIRECT TO SETUP WIZARD AFTER ACTIVATION ---
// Uses a Playground-specific option name so the Connector plugin's shared
// almaseo_set_activation_redirect() cannot hijack the redirect.
// Guarded to prevent fatal if two Playground versions are installed simultaneously.
if ( ! function_exists( 'almaseo_playground_set_activation_redirect' ) ) {
function almaseo_playground_set_activation_redirect() {
    add_option('almaseo_playground_do_activation_redirect', true);

    // Check for existing dashboard connection on activation
    if (function_exists('almaseo_sync_from_dashboard')) {
        almaseo_sync_from_dashboard();
    }

    // Attempt automatic connection on activation
    $current_user_id = get_current_user_id();
    if ($current_user_id) {
        $user = get_user_by('ID', $current_user_id);
        if ($user && user_can($user, 'manage_options')) {
            $existing_password = get_option('almaseo_app_password', '');

            // Only generate if no password exists
            if (!$existing_password && function_exists('wp_is_application_passwords_available') && function_exists('wp_generate_application_password')) {
                if (wp_is_application_passwords_available()) {
                    $label = 'AlmaSEO Auto-Connect ' . wp_date('Y-m-d');
                    $new_password = wp_generate_application_password($user->ID, array(
                        'name' => $label,
                        'app_id' => 'almaseo-seo-playground'
                    ));

                    if (is_wp_error($new_password)) {
                        error_log('AlmaSEO: Auto-connect failed: ' . $new_password->get_error_message());
                    } elseif ($new_password && is_array($new_password)) {
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
} // end function_exists guard: almaseo_playground_set_activation_redirect
register_activation_hook(__FILE__, 'almaseo_playground_set_activation_redirect');

add_action('admin_init', function() {
    if (get_option('almaseo_playground_do_activation_redirect', false)) {
        delete_option('almaseo_playground_do_activation_redirect');
        if (!isset($_GET['activate-multi'])) {
            if (!get_option('almaseo_setup_wizard_completed')) {
                wp_safe_redirect(admin_url('admin.php?page=almaseo-setup-wizard'));
            } else {
                wp_safe_redirect(admin_url('admin.php?page=almaseo-welcome'));
            }
            exit;
        }
    }
});

// Welcome notices removed in v8.9.5 — the setup wizard is the welcome experience.
// Connector/conflict notices appear after wizard completion (see above).

// --- HELPER FUNCTIONS FOR CONNECTION STATUS & DISCONNECT ---
if (!function_exists('almaseo_get_connection_status')) {
    function almaseo_get_connection_status() {
        $connected_user = get_option('almaseo_connected_user', '');
        $connected_date = get_option('almaseo_connected_date', '');
        $app_password = get_option('almaseo_app_password', '');

        // Primary check: if we have an app password, the site is connected.
        // The almaseo_dashboard_synced flag may not always be set (e.g. older
        // connections or dashboard-initiated installs), so we don't gate on it.
        if ($app_password) {
            return array(
                'connected' => true,
                'connected_user' => $connected_user,
                'connected_date' => $connected_date ? date('M j, Y', strtotime($connected_date)) : 'Recently',
                'site_url' => get_site_url(),
                'connection_type' => get_option('almaseo_connection_type', 'dashboard_initiated')
            );
        }

        // Secondary check: if we have a connected_user but no app_password stored
        // in our option, scan WP Application Passwords for an AlmaSEO entry
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
}

// Helper function to clean up old AlmaSEO passwords
if (!function_exists('almaseo_cleanup_old_passwords')) {
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
} // end function_exists guard: almaseo_cleanup_old_passwords

if (!function_exists('almaseo_disconnect_site')) {
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

// Register settings for manual app password
// NOTE: We are using a manually generated Application Password stored in the plugin settings.
// This approach is required for hosting environments like GoDaddy Managed WordPress that disable application password auto-generation.
if (!function_exists('almaseo_connector_register_settings')) {
    function almaseo_connector_register_settings() {
        register_setting('almaseo_settings_group', 'almaseo_app_password', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ));
        register_setting('almaseo_settings_group', 'almaseo_exclusive_schema_mode', array(
            'type' => 'string',
            'sanitize_callback' => 'sanitize_text_field',
        ));
    }
    add_action('admin_init', 'almaseo_connector_register_settings');
}

// ========================================
// TIER DETECTION AND MANAGEMENT
// ========================================

/**
 * Fetch user tier and limits from AlmaSEO dashboard
 * @return array User tier information
 */
if (!function_exists('almaseo_fetch_user_tier')) {
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
        // Log error — default to max tier for connected users when API is unreachable
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('[AlmaSEO] Tier fetch failed: ' . $response->get_error_message());
        }
        return array(
            'tier' => 'max',
            'limits' => array(
                'monthly_articles' => -1,
                'ai_generations' => -1
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
            'tier' => isset($data['tier']) ? strtolower($data['tier']) : 'max',
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
            'fetched_at' => current_time('U')
        );
        
        // Cache the tier data for 1 hour
        set_transient('almaseo_user_tier_data', $tier_data, HOUR_IN_SECONDS);

        // Also store in options for persistent access
        update_option('almaseo_user_tier', $tier_data['tier']);
        update_option('almaseo_tier_limits', $tier_data['limits']);
        update_option('almaseo_tier_usage', $tier_data['usage']);

        // Sync the license tier used for feature gating (license-helper.php)
        // Map API tiers to license tiers: 'max' → 'agency', others pass through
        $license_tier_map = array('free' => 'free', 'pro' => 'pro', 'max' => 'agency');
        $mapped_tier = isset($license_tier_map[$tier_data['tier']]) ? $license_tier_map[$tier_data['tier']] : 'free';
        update_option('almaseo_license_tier', $mapped_tier);
        
        return $tier_data;
    } else {
        // API returned non-200 — default to max tier for connected users
        return array(
            'tier' => 'max',
            'limits' => array(
                'monthly_articles' => -1,
                'ai_generations' => -1
            ),
            'error' => 'API returned status code: ' . $response_code
        );
    }
}
} // end function_exists guard: almaseo_fetch_user_tier

/**
 * Get current user tier (with caching)
 * @return string User tier: 'unconnected', 'free', 'pro', 'max'
 */
if (!function_exists('almaseo_get_user_tier')) {
function almaseo_get_user_tier() {
    // Check if we have cached tier data (set by almaseo_fetch_user_tier with 1-hour expiry)
    $cached_tier = get_transient('almaseo_user_tier_data');
    if ($cached_tier !== false && isset($cached_tier['tier'])) {
        return $cached_tier['tier'];
    }

    // No valid cache — fetch fresh tier data
    $tier_data = almaseo_fetch_user_tier();
    return $tier_data['tier'];
}
} // end function_exists guard: almaseo_get_user_tier

/**
 * Check if user has access to AI features
 * @return bool
 */
if (!function_exists('almaseo_can_use_ai_features')) {
function almaseo_can_use_ai_features() {
    $tier = almaseo_get_user_tier();
    return in_array($tier, array('pro', 'max'));
}
} // end function_exists guard: almaseo_can_use_ai_features

/**
 * Get remaining AI generations for current month
 * @return array
 */
if (!function_exists('almaseo_get_remaining_generations')) {
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
} // end function_exists guard: almaseo_get_remaining_generations

/**
 * Track AI generation usage
 * @param string $type Type of generation (title, description, rewrite)
 * @return bool
 */
if (!function_exists('almaseo_track_ai_usage')) {
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
        $user_usage['last_used'] = current_time('U');
        
        update_user_meta($current_user_id, 'almaseo_ai_usage_' . date('Y_m'), $user_usage);
    }
    
    // Clear tier cache to force refresh on next check
    delete_transient('almaseo_user_tier_data');
    
    return true;
}
} // end function_exists guard: almaseo_track_ai_usage

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
if (!function_exists('seo_playground_is_alma_connected')) {
function seo_playground_is_alma_connected() {
    $connection_status = almaseo_get_connection_status();
    return $connection_status['connected'];
}
} // end function_exists guard: seo_playground_is_alma_connected

// Add SEO Playground meta box
if (!function_exists('almaseo_add_seo_playground_meta_box')) {
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
} // end function_exists guard: almaseo_add_seo_playground_meta_box
add_action('add_meta_boxes', 'almaseo_add_seo_playground_meta_box', 20); // Priority 20 to run after defaults

// SEO Playground metabox: enqueue styles, LLM panel, and metabox callback
require_once plugin_dir_path(__FILE__) . 'admin/partials/metabox-callback.php';

// Save SEO Playground data
require_once plugin_dir_path(__FILE__) . 'includes/admin/post-save-handler.php';

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
    
    // HEADLINE: SEO Title → post title (validate against foreign template tokens)
    $seo_title = get_post_meta($post_id, '_almaseo_title', true);
    if (!empty($seo_title) && class_exists('AlmaSEO_Tag_Validator')) {
        $seo_title = AlmaSEO_Tag_Validator::sanitize_seo_value($seo_title);
    }
    $headline = !empty($seo_title) ? $seo_title : get_the_title($post_id);

    // DESCRIPTION: SEO Meta Description → excerpt → first 160 chars (with entity cleaning)
    $desc = get_post_meta($post_id, '_almaseo_description', true);
    if (!empty($desc) && class_exists('AlmaSEO_Tag_Validator')) {
        $desc = AlmaSEO_Tag_Validator::sanitize_seo_value($desc);
    }
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

// SEO Playground AJAX handlers (AI tools, insights, GSC, schema, health, notes)
require_once plugin_dir_path(__FILE__) . 'includes/ajax/seo-playground-ajax.php';

// --- FRONT-END META TAG RENDERING ---
require_once plugin_dir_path(__FILE__) . 'includes/frontend/meta-tags-renderer.php';
// WooCommerce Settings Page
if (!function_exists('almaseo_woocommerce_settings_page')) {
function almaseo_woocommerce_settings_page() {
    if (!current_user_can('manage_options')) {
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
} // end function_exists guard: almaseo_woocommerce_settings_page
