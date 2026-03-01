<?php
/**
 * AlmaSEO Evergreen Feature - Minimal Safe Loader
 * 
 * Absolutely minimal loader that just includes files without instantiating classes
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 2.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}


// Don't load during activation
if (defined('WP_SANDBOX_SCRAPING') && WP_SANDBOX_SCRAPING) {
    return;
}

if (isset($_GET['action']) && $_GET['action'] === 'activate') {
    return;
}

// Load constants first
$constants_file = dirname(__FILE__) . '/constants.php';
if (file_exists($constants_file)) {
    require_once $constants_file;
}

// Load functions
$functions_file = dirname(__FILE__) . '/functions.php';
if (file_exists($functions_file)) {
    require_once $functions_file;
}

// Load scoring functions (contains almaseo_eg_get_settings)
$scoring_file = dirname(__FILE__) . '/scoring.php';
if (file_exists($scoring_file)) {
    require_once $scoring_file;
}

// Load meta functions (contains almaseo_get_post_ages, almaseo_eg_get_clicks)
$meta_file = dirname(__FILE__) . '/meta.php';
if (file_exists($meta_file)) {
    require_once $meta_file;
}

// Load scheduler functions (contains almaseo_eg_recalc_all)
$scheduler_file = dirname(__FILE__) . '/scheduler.php';
if (file_exists($scheduler_file)) {
    require_once $scheduler_file;
}

// Load admin files on plugins_loaded to ensure WordPress is ready
// Use immediate priority to run before other plugins_loaded hooks
add_action('plugins_loaded', function() {
    // Load admin functions for menu registration
    // Current file is in /includes/evergreen/, admin.php is in same directory
    $admin_file = dirname(__FILE__) . '/admin.php';
    
    if (file_exists($admin_file)) {
        require_once $admin_file;
    }
    
    // Load admin-post actions (needs to be available for admin-post.php)
    $actions_file = dirname(__FILE__) . '/actions.php';
    if (file_exists($actions_file)) {
        require_once $actions_file;
    }
    
    // Load widget functions
    $widget_file = dirname(__FILE__) . '/widget.php';
    if (file_exists($widget_file)) {
        require_once $widget_file;
    }
    
    // Load dashboard functions
    $dashboard_file = dirname(__FILE__) . '/dashboard.php';
    if (file_exists($dashboard_file)) {
        require_once $dashboard_file;
    }
}, 1); // Very early priority to ensure it runs before admin_menu

// Load REST API if it has a class
add_action('rest_api_init', function() {
    // Ensure all dependencies are loaded for REST API
    $deps = array(
        'constants.php',
        'functions.php',
        'scoring.php',
        'meta.php'
    );
    
    foreach ($deps as $dep) {
        $dep_file = dirname(__FILE__) . '/' . $dep;
        if (file_exists($dep_file)) {
            require_once $dep_file;
        }
    }
    
    $rest_file = dirname(__FILE__) . '/rest-api.php';
    if (file_exists($rest_file)) {
        require_once $rest_file;
        
        // Only instantiate if class exists
        if (class_exists('AlmaSEO_Evergreen_REST_API')) {
            AlmaSEO_Evergreen_REST_API::get_instance();
        }
    }
});

// Load and instantiate cron if it has a class
add_action('init', function() {
    $cron_file = dirname(__FILE__) . '/cron.php';
    if (file_exists($cron_file)) {
        require_once $cron_file;
        
        // Only instantiate if class exists
        if (class_exists('AlmaSEO_Evergreen_Cron')) {
            AlmaSEO_Evergreen_Cron::get_instance();
        }
    }
});

// Enqueue assets
add_action('admin_enqueue_scripts', function($hook) {
    // Only on relevant pages
    $allowed_hooks = array('edit.php', 'post.php', 'post-new.php', 'index.php');
    $is_evergreen_page = strpos($hook, 'almaseo-evergreen') !== false;
    
    if (!in_array($hook, $allowed_hooks) && !$is_evergreen_page) {
        return;
    }
    
    // Get the plugin URL correctly - we're in /includes/evergreen/, need to go up 2 levels
    $plugin_url = plugin_dir_url(dirname(dirname(__FILE__)));
    
    // Enqueue CSS if exists
    wp_enqueue_style(
        'almaseo-evergreen',
        $plugin_url . 'assets/css/evergreen.css',
        array(),
        '2.5.0'
    );
    
    // Enqueue JS for functionality
    wp_enqueue_script(
        'almaseo-evergreen',
        $plugin_url . 'assets/js/evergreen.js',
        array('jquery'),
        '2.5.0',
        true
    );
    
    // Localize script
    wp_localize_script('almaseo-evergreen', 'almaseoEvergreen', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('almaseo_eg_ajax'),
        'i18n' => array(
            'updating' => __('Updating...', 'almaseo'),
            'refreshed' => __('Refreshed!', 'almaseo'),
            'error' => __('Error', 'almaseo'),
            'analyzing' => __('Analyzing...', 'almaseo'),
            'analyze_now' => __('Analyze Now', 'almaseo'),
            'analysis_complete' => __('Analysis Complete!', 'almaseo'),
            'error_analyzing' => __('Error analyzing post', 'almaseo'),
            'network_error' => __('Network error occurred', 'almaseo'),
            'evergreen' => __('Evergreen', 'almaseo'),
            'watch' => __('Watch', 'almaseo'),
            'stale' => __('Stale', 'almaseo'),
            'last_recalculated_just_now' => __('Last recalculated: just now', 'almaseo')
        )
    ));
});

// Enqueue editor assets
add_action('enqueue_block_editor_assets', function() {
    $post_type = get_post_type();
    
    if (!in_array($post_type, array('post', 'page'))) {
        return;
    }
    
    // Get the plugin URL and path correctly - we're in /includes/evergreen/, need to go up 2 levels
    $plugin_url = plugin_dir_url(dirname(dirname(__FILE__)));
    $plugin_dir = plugin_dir_path(dirname(dirname(__FILE__)));
    
    // Enqueue sidebar CSS
    wp_enqueue_style(
        'almaseo-evergreen-sidebar',
        $plugin_url . 'assets/css/evergreen-sidebar.css',
        array(),
        '4.2.0'
    );
    
    // Enqueue Schema panel
    $schema_panel = $plugin_dir . 'assets/js/schema-panel.js';
    if (file_exists($schema_panel)) {
        wp_enqueue_script(
            'almaseo-schema-panel',
            $plugin_url . 'assets/js/schema-panel.js',
            array('wp-plugins', 'wp-edit-post', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n'),
            '4.2.0',
            true
        );
        
        wp_localize_script('almaseo-schema-panel', 'almaseoSchemaSettings', array(
            'apiRoot' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest')
        ));
    }
    
    // Use enhanced panel v2
    $enhanced_panel_v2 = $plugin_dir . 'assets/js/evergreen-panel-enhanced-v2.js';
    $enhanced_panel = $plugin_dir . 'assets/js/evergreen-panel-enhanced.js';
    
    if (file_exists($enhanced_panel_v2)) {
        wp_enqueue_script(
            'almaseo-evergreen-panel-enhanced-v2',
            $plugin_url . 'assets/js/evergreen-panel-enhanced-v2.js',
            array('wp-plugins', 'wp-edit-post', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n', 'wp-api-fetch', 'wp-compose'),
            '4.2.1',
            true
        );
        
        // Localize with proper nonce
        wp_localize_script('almaseo-evergreen-panel-enhanced-v2', 'almaseoEvergreenSettings', array(
            'apiRoot' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'supportedPostTypes' => array('post', 'page'),
            'ajaxUrl' => admin_url('admin-ajax.php')
        ));
    } elseif (file_exists($enhanced_panel)) {
        wp_enqueue_script(
            'almaseo-evergreen-panel-enhanced',
            $plugin_url . 'assets/js/evergreen-panel-enhanced.js',
            array('wp-plugins', 'wp-edit-post', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n', 'wp-api-fetch', 'wp-compose'),
            '4.2.0',
            true
        );
        
        // Localize
        wp_localize_script('almaseo-evergreen-panel-enhanced', 'almaseoEvergreenSettings', array(
            'apiRoot' => esc_url_raw(rest_url()),
            'nonce' => wp_create_nonce('wp_rest'),
            'supportedPostTypes' => array('post', 'page'),
            'ajaxUrl' => admin_url('admin-ajax.php')
        ));
    } else {
        // Fallback to simple panel
        $panel_file = $plugin_dir . 'assets/js/evergreen-panel-minimal.js';
        if (file_exists($panel_file)) {
            wp_enqueue_script(
                'almaseo-evergreen-panel-simple',
                $plugin_url . 'assets/js/evergreen-panel-minimal.js',
                array('wp-plugins', 'wp-edit-post', 'wp-editor', 'wp-element', 'wp-components', 'wp-data', 'wp-i18n'),
                '2.6.0',
                true
            );
            
            // Localize
            wp_localize_script('almaseo-evergreen-panel-simple', 'almaseoEvergreenSettings', array(
                'apiRoot' => esc_url_raw(rest_url()),
                'nonce' => wp_create_nonce('wp_rest'),
                'supportedPostTypes' => array('post', 'page')
            ));
        }
    }
});