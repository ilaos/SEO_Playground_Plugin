<?php
/**
 * AlmaSEO Schema Final Implementation - Fixed Order Version
 * 
 * @package AlmaSEO
 * @version 3.0.1
 */

if (!defined('ABSPATH')) {
    exit;
}

// =============================================================================
// DECLARE THE FUNCTION FIRST - BEFORE ANY HOOKS!
// =============================================================================

/**
 * Output schema JSON-LD with complete diagnostics
 */
if (!function_exists('almaseo_output_schema_jsonld')) {
function almaseo_output_schema_jsonld() {
    // IMMEDIATE OUTPUT - proves function is called
    echo "<!-- AlmaSEO Generator: START -->\n";
    
    // Log start with URL
    $current_url = home_url(add_query_arg(array()));
    error_log('[AlmaSEO] generator start url=' . $current_url);
    
    // Debug all conditionals
    echo "<!-- AlmaSEO Debug: is_admin=" . (is_admin() ? 'true' : 'false') . " -->\n";
    echo "<!-- AlmaSEO Debug: is_feed=" . (is_feed() ? 'true' : 'false') . " -->\n";
    echo "<!-- AlmaSEO Debug: is_archive=" . (is_archive() ? 'true' : 'false') . " -->\n";
    echo "<!-- AlmaSEO Debug: is_search=" . (is_search() ? 'true' : 'false') . " -->\n";
    echo "<!-- AlmaSEO Debug: is_home=" . (is_home() ? 'true' : 'false') . " -->\n";
    echo "<!-- AlmaSEO Debug: is_front_page=" . (is_front_page() ? 'true' : 'false') . " -->\n";
    echo "<!-- AlmaSEO Debug: is_singular('post')=" . (is_singular('post') ? 'true' : 'false') . " -->\n";
    echo "<!-- AlmaSEO Debug: is_page=" . (is_page() ? 'true' : 'false') . " -->\n";
    echo "<!-- AlmaSEO Debug: is_single=" . (is_single() ? 'true' : 'false') . " -->\n";
    echo "<!-- AlmaSEO Debug: is_singular=" . (is_singular() ? 'true' : 'false') . " -->\n";
    
    // Check if we're in admin area (should never happen in wp_head)
    if (is_admin()) {
        $reason = 'is_admin';
        echo "<!-- AlmaSEO Generator: SKIP reason={$reason} -->\n";
        error_log('[AlmaSEO] Generator skipped: ' . $reason);
        return;
    }
    
    // Check if feed
    if (is_feed()) {
        $reason = 'is_feed';
        echo "<!-- AlmaSEO Generator: SKIP reason={$reason} -->\n";
        error_log('[AlmaSEO] Generator skipped: ' . $reason);
        return;
    }
    
    // Check for AMP
    if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
        $reason = 'is_amp';
        echo "<!-- AlmaSEO Generator: SKIP reason={$reason} -->\n";
        error_log('[AlmaSEO] Generator skipped: ' . $reason);
        return;
    }
    
    // Check if archive
    if (is_archive()) {
        $reason = 'is_archive';
        echo "<!-- AlmaSEO Generator: SKIP reason={$reason} -->\n";
        error_log('[AlmaSEO] Generator skipped: ' . $reason);
        return;
    }
    
    // Check if search
    if (is_search()) {
        $reason = 'is_search';
        echo "<!-- AlmaSEO Generator: SKIP reason={$reason} -->\n";
        error_log('[AlmaSEO] Generator skipped: ' . $reason);
        return;
    }
    
    // Check if blog home (posts index) that's not front page
    if (is_home() && !is_front_page()) {
        $reason = 'is_posts_index';
        echo "<!-- AlmaSEO Generator: SKIP reason={$reason} -->\n";
        error_log('[AlmaSEO] Generator skipped: ' . $reason);
        return;
    }
    
    // Check page type - must be post, page, or front page
    $is_valid = is_singular('post') || is_page() || is_front_page();
    
    if (!$is_valid) {
        $reason = 'not_valid_page_type';
        echo "<!-- AlmaSEO Generator: SKIP reason={$reason} -->\n";
        error_log('[AlmaSEO] Generator skipped: ' . $reason);
        return;
    }
    
    // Get post data
    global $post;
    $post_id = 0;
    
    if (is_singular() && isset($post->ID)) {
        $post_id = $post->ID;
    } elseif (is_front_page()) {
        $post_id = get_option('page_on_front');
        if (!$post_id) {
            // If no static front page, try to get latest post
            $latest = get_posts(array('numberposts' => 1));
            if (!empty($latest)) {
                $post_id = $latest[0]->ID;
            }
        }
    }
    
    if (!$post_id) {
        $reason = 'no_post_id';
        echo "<!-- AlmaSEO Generator: SKIP reason={$reason} -->\n";
        error_log('[AlmaSEO] Generator skipped: ' . $reason);
        return;
    }
    
    $post_obj = get_post($post_id);
    if (!$post_obj) {
        $reason = 'no_post_object';
        echo "<!-- AlmaSEO Generator: SKIP reason={$reason} -->\n";
        error_log('[AlmaSEO] Generator skipped: ' . $reason);
        return;
    }
    
    // Build schema data
    $schema = almaseo_build_schema_data($post_id, $post_obj);
    
    if (empty($schema)) {
        $reason = 'empty_schema';
        echo "<!-- AlmaSEO Generator: SKIP reason={$reason} -->\n";
        error_log('[AlmaSEO] Generator skipped: ' . $reason);
        return;
    }
    
    // Output the schema with EXACT format required
    echo "<!-- AlmaSEO Generator: OK -->\n";
    echo "<!-- AlmaSEO Schema Markup -->\n";
    echo '<script type="application/ld+json" id="almaseo-jsonld" data-almaseo="1">';
    echo wp_json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    echo '</script>' . "\n";
    echo "<!-- /AlmaSEO Schema Markup -->\n";
    
    error_log('[AlmaSEO] generator ok - output complete');
}
} // End if function_exists
error_log('[AlmaSEO] function almaseo_output_schema_jsonld declared');

// =============================================================================
// NOW REGISTER THE HOOKS - AFTER FUNCTION IS DECLARED
// =============================================================================

// DEFINITIVE PROOF THIS FILE LOADS - Add to wp_head immediately
if (!is_admin()) {
    add_action('wp_head', function() {
        echo "<!-- AlmaSEO PROOF: schema-final.php LOADED -->\n";
        echo "<!-- AlmaSEO PROOF: wp_head hook is FIRING -->\n";
    }, 1);
}

// Log that we're registering the hook
error_log('[AlmaSEO] schema-final.php LOADED - registering almaseo_output_schema_jsonld hook at priority 2');

// Ensure proper callback resolution
$cb = 'almaseo_output_schema_jsonld';

// Main schema output hook - priority 2 as required
add_action('wp_head', $cb, 2);

// Debug: prove registration and resolution
error_log('[AlmaSEO] Callback: ' . $cb);
error_log('[AlmaSEO] Function exists at registration: ' . (function_exists($cb) ? 'YES' : 'NO'));
error_log('[AlmaSEO] has_action wp_head: ' . (int)has_action('wp_head', $cb));

// Force-call wrapper to prove execution path
add_action('wp_head', function() use ($cb) {
    echo "<!-- AlmaSEO FORCE CALL WRAPPER -->\n";
    if (is_callable($cb)) {
        echo "<!-- AlmaSEO: Callback IS callable, calling now -->\n";
        call_user_func($cb);
    } else {
        echo "<!-- AlmaSEO ERROR: callback not callable -->\n";
        echo "<!-- AlmaSEO DEBUG: callback = " . var_export($cb, true) . " -->\n";
    }
}, 4);

// Also try init hook registration as backup
add_action('init', function() use ($cb) {
    if (!has_action('wp_head', $cb)) {
        add_action('wp_head', $cb, 2);
        error_log('[AlmaSEO] Re-registered hook in init');
    }
    // Double-check registration
    error_log('[AlmaSEO] Init check - has_action: ' . (int)has_action('wp_head', $cb));
});

// Try a simple direct test
add_action('wp_head', function() {
    echo "<!-- AlmaSEO TEST: Direct anonymous function at priority 3 works -->\n";
}, 3);

// Add another hook to check if wp_head fires
add_action('wp', function() {
    if (!is_admin()) {
        // This runs before template loads
        add_action('wp_footer', function() {
            if (!did_action('wp_head')) {
                echo "<!-- AlmaSEO WARNING: wp_head() was never called by theme! -->\n";
            } else {
                echo "<!-- AlmaSEO: wp_head() was called " . did_action('wp_head') . " time(s) -->\n";
            }
        });
    }
});

// Also add a late check to see if our function exists
add_action('wp_head', function() {
    if (function_exists('almaseo_output_schema_jsonld')) {
        echo "<!-- AlmaSEO PROOF: almaseo_output_schema_jsonld function EXISTS -->\n";
    } else {
        echo "<!-- AlmaSEO ERROR: almaseo_output_schema_jsonld function NOT FOUND -->\n";
    }
}, 999);

// =============================================================================
// HELPER FUNCTIONS
// =============================================================================

/**
 * Build schema data array
 */
if (!function_exists('almaseo_build_schema_data')) {
function almaseo_build_schema_data($post_id, $post_obj) {
    // Base schema structure
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting'
    );
    
    // Get meta data
    $seo_title = get_post_meta($post_id, 'almaseo_title', true);
    $seo_desc = get_post_meta($post_id, 'almaseo_description', true);
    $schema_type = get_post_meta($post_id, 'almaseo_schema_type', true);
    
    // Override schema type if specified
    if ($schema_type && in_array($schema_type, array('Article', 'NewsArticle', 'BlogPosting', 'WebPage'))) {
        $schema['@type'] = $schema_type;
    }
    
    // Build URL
    $url = get_permalink($post_id);
    $schema['url'] = $url;
    $schema['mainEntityOfPage'] = array(
        '@type' => 'WebPage',
        '@id' => $url . '#webpage'
    );
    
    // Title and description
    $schema['headline'] = $seo_title ?: $post_obj->post_title;
    
    if ($seo_desc) {
        $schema['description'] = substr($seo_desc, 0, 160);
        if (strlen($seo_desc) > 160) {
            $schema['description'] .= '...';
        }
    } else {
        $excerpt = $post_obj->post_excerpt ?: wp_trim_words($post_obj->post_content, 30);
        $schema['description'] = substr(strip_tags($excerpt), 0, 160);
    }
    
    // Dates
    $schema['datePublished'] = get_the_date('c', $post_id);
    $schema['dateModified'] = get_the_modified_date('c', $post_id);
    
    // Author
    $author_id = $post_obj->post_author;
    $author = get_userdata($author_id);
    if ($author) {
        $schema['author'] = array(
            '@type' => 'Person',
            'name' => $author->display_name,
            'url' => get_author_posts_url($author_id)
        );
    }
    
    // Image
    $og_image = get_post_meta($post_id, 'almaseo_og_image', true);
    if ($og_image) {
        $schema['image'] = $og_image;
    } elseif (has_post_thumbnail($post_id)) {
        $schema['image'] = get_the_post_thumbnail_url($post_id, 'full');
    } else {
        $schema['image'] = almaseo_get_default_schema_image();
    }
    
    // Publisher
    $site_name = get_bloginfo('name');
    $schema['publisher'] = array(
        '@type' => 'Organization',
        'name' => $site_name,
        'url' => home_url('/'),
        'logo' => array(
            '@type' => 'ImageObject',
            'url' => almaseo_get_default_schema_image()
        )
    );
    
    // Article-specific fields
    if (in_array($schema['@type'], array('Article', 'NewsArticle', 'BlogPosting'))) {
        $categories = wp_get_post_categories($post_id, array('fields' => 'names'));
        if (!empty($categories)) {
            $schema['articleSection'] = implode(', ', $categories);
        }
        
        $tags = wp_get_post_tags($post_id, array('fields' => 'names'));
        if (!empty($tags)) {
            $schema['keywords'] = implode(', ', $tags);
        }
        
        // Word count
        $word_count = str_word_count(strip_tags($post_obj->post_content));
        $schema['wordCount'] = $word_count;
    }
    
    return apply_filters('almaseo_schema_data', $schema, $post_id);
}
} // End if function_exists

/**
 * Get default schema image
 */
if (!function_exists('almaseo_get_default_schema_image')) {
function almaseo_get_default_schema_image() {
    // Check for site logo
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
        if ($logo_url) {
            return $logo_url;
        }
    }
    
    // Check for site icon
    $site_icon_id = get_option('site_icon');
    if ($site_icon_id) {
        $icon_url = wp_get_attachment_image_url($site_icon_id, 'full');
        if ($icon_url) {
            return $icon_url;
        }
    }
    
    // Fallback to plugin default
    return plugin_dir_url(dirname(__FILE__)) . 'assets/img/default-schema.jpg';
}
} // End if function_exists

// =============================================================================
// EXCLUSIVE MODE FEATURE
// =============================================================================

/**
 * Initialize AlmaSEO Exclusive Mode
 * 
 * When enabled, this disables schema output from other SEO plugins
 * to prevent duplicate or conflicting schema markup
 */
if (!function_exists('almaseo_init_exclusive_mode')) {
function almaseo_init_exclusive_mode() {
    // Check if exclusive mode is enabled
    $connection_data = get_option('almaseo_connection', array());
    $exclusive_mode = isset($connection_data['exclusive_mode']) ? $connection_data['exclusive_mode'] : false;
    
    if (!$exclusive_mode) {
        error_log('[AlmaSEO] Exclusive Mode: DISABLED');
        return;
    }
    
    error_log('[AlmaSEO] Exclusive Mode: ENABLED - Disabling other plugin schemas');
    
    // Disable schema from other plugins when our schema runs
    add_action('init', function() {
        
        error_log('[AlmaSEO] Disabling other plugin schemas via filters');
        
        // Yoast SEO
        add_filter('wpseo_json_ld_output', '__return_false', 999);
        add_filter('wpseo_schema_graph', '__return_false', 999);
        add_filter('wpseo_schema_graph_pieces', '__return_empty_array', 999);
        
        // Rank Math
        add_filter('rank_math/json_ld', '__return_false', 999);
        add_filter('rank_math/json_ld/enabled', '__return_false', 999);
        
        // SEOPress
        add_filter('seopress_schemas_enabled', '__return_false', 999);
        add_filter('seopress_pro_schemas_enabled', '__return_false', 999);
        
        // AIOSEO
        add_filter('aioseo_schema_disable', '__return_true', 999);
        add_filter('aioseo_schema_output', '__return_false', 999);
        
        // The SEO Framework
        add_filter('the_seo_framework_ldjson_scripts', '__return_false', 999);
        
        // Schema Pro
        add_filter('wp_schema_pro_schema_enabled', '__return_false', 999);
    }, 1);
    
    // Start output buffer VERY early to catch all output
    add_action('template_redirect', function() {
        if (is_admin() || is_feed()) {
            return;
        }
        
        ob_start(function($buffer) {
            return almaseo_clean_competitor_schemas($buffer);
        });
    }, -9999);
    
    // Ensure buffer is flushed
    add_action('shutdown', function() {
        if (ob_get_level() > 0) {
            ob_end_flush();
        }
    }, 9999);
    
    error_log('[AlmaSEO] Exclusive Mode setup complete');
}
} // End if function_exists

/**
 * Clean competitor schema markup from HTML output
 * Only runs when Exclusive Mode is enabled
 */
if (!function_exists('almaseo_clean_competitor_schemas')) {
function almaseo_clean_competitor_schemas($html) {
    if (is_admin() || is_feed()) {
        return $html;
    }
    
    error_log('[AlmaSEO] Cleaning competitor schemas from output');
    $original_length = strlen($html);
    
    // Remove Yoast schema
    $html = preg_replace('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*class=["\']yoast-schema-graph["\'][^>]*>.*?<\/script>/is', '', $html);
    
    // Remove Rank Math schema
    $html = preg_replace('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*class=["\']rank-math-schema["\'][^>]*>.*?<\/script>/is', '', $html);
    
    // Remove AIOSEO schema
    $html = preg_replace('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*class=["\']aioseo-schema["\'][^>]*>.*?<\/script>/is', '', $html);
    
    // Remove SEOPress schema
    $html = preg_replace('/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>.*?"@context"[^}]*seopress.*?<\/script>/is', '', $html);
    
    // Remove any other JSON-LD that's NOT ours (preserve data-almaseo="1")
    $html = preg_replace_callback(
        '/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is',
        function($matches) {
            // Keep our schema (has data-almaseo="1" or id="almaseo-jsonld")
            if (strpos($matches[0], 'data-almaseo="1"') !== false || 
                strpos($matches[0], 'id="almaseo-jsonld"') !== false) {
                return $matches[0];
            }
            
            // Check if it contains schema.org and looks like SEO schema
            if (strpos($matches[1], '"@context"') !== false && 
                strpos($matches[1], 'schema.org') !== false) {
                
                // Check for SEO-related types
                $seo_types = ['Article', 'BlogPosting', 'NewsArticle', 'WebSite', 'Organization', 'Person', 'WebPage'];
                foreach ($seo_types as $type) {
                    if (strpos($matches[1], '"@type":"' . $type . '"') !== false ||
                        strpos($matches[1], '"@type": "' . $type . '"') !== false) {
                        error_log('[AlmaSEO] Removed schema type: ' . $type);
                        return ''; // Remove it
                    }
                }
            }
            
            return $matches[0]; // Keep non-SEO schemas
        },
        $html
    );
    
    $new_length = strlen($html);
    $removed = $original_length - $new_length;
    if ($removed > 0) {
        error_log('[AlmaSEO] Removed ' . $removed . ' bytes of competitor schema');
    }
    
    return $html;
}
} // End if function_exists

// Initialize exclusive mode
add_action('plugins_loaded', 'almaseo_init_exclusive_mode', 5);

// Test if exclusive mode is working
add_action('wp_footer', function() {
    $connection_data = get_option('almaseo_connection', array());
    $exclusive_mode = isset($connection_data['exclusive_mode']) ? $connection_data['exclusive_mode'] : false;
    
    if ($exclusive_mode) {
        echo "<!-- AlmaSEO Exclusive Mode: ACTIVE -->\n";
    } else {
        echo "<!-- AlmaSEO Exclusive Mode: INACTIVE -->\n";
    }
});