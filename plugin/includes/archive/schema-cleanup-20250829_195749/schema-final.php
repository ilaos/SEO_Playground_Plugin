<?php
/**
 * AlmaSEO Schema Final Implementation - Bulletproof Version
 * 
 * @package AlmaSEO
 * @version 3.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

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
if (defined('__NAMESPACE__') && __NAMESPACE__) {
    $cb = function_exists(__NAMESPACE__ . '\\almaseo_output_schema_jsonld')
        ? __NAMESPACE__ . '\\almaseo_output_schema_jsonld'
        : 'almaseo_output_schema_jsonld';
}

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
    
    // URL and canonical
    $canonical = get_post_meta($post_id, '_almaseo_canonical_url', true);
    if (empty($canonical)) {
        $canonical = get_permalink($post_id);
    }
    $schema['url'] = esc_url($canonical);
    $schema['mainEntityOfPage'] = array(
        '@type' => 'WebPage',
        '@id' => esc_url($canonical) . '#webpage'
    );
    
    // Title/Headline
    $title = get_post_meta($post_id, '_almaseo_title', true);
    if (empty($title)) {
        $title = $post_obj->post_title;
    }
    if (empty($title)) {
        $title = 'Untitled';
    }
    $schema['headline'] = wp_strip_all_tags($title);
    
    // Description - clean HTML entities
    $description = get_post_meta($post_id, '_almaseo_description', true);
    if (empty($description)) {
        if (has_excerpt($post_id)) {
            $description = get_the_excerpt($post_obj);
        } else {
            $description = wp_trim_words($post_obj->post_content, 30, '...');
        }
    }
    if (empty($description)) {
        $description = 'No description available';
    }
    // Clean description of HTML entities
    $description = wp_strip_all_tags($description);
    $description = html_entity_decode($description, ENT_QUOTES | ENT_HTML5, get_bloginfo('charset'));
    $schema['description'] = $description;
    
    // Dates
    $date_published = get_the_date('c', $post_id);
    if (empty($date_published)) {
        $date_published = current_time('c');
    }
    $schema['datePublished'] = $date_published;
    
    $date_modified = get_the_modified_date('c', $post_id);
    if (empty($date_modified)) {
        $date_modified = $date_published;
    }
    $schema['dateModified'] = $date_modified;
    
    // Author with URL
    $author_id = $post_obj->post_author;
    $author_data = get_userdata($author_id);
    if ($author_data) {
        $author_url = get_author_posts_url($author_id);
        $schema['author'] = array(
            '@type' => 'Person',
            'name' => $author_data->display_name ?: 'Unknown Author',
            'url' => $author_url ?: home_url('/')
        );
    } else {
        $schema['author'] = array(
            '@type' => 'Person',
            'name' => get_bloginfo('name') ?: 'Site Author',
            'url' => home_url('/')
        );
    }
    
    // Publisher
    $site_name = get_bloginfo('name');
    if (empty($site_name)) {
        $site_name = 'Website';
    }
    $schema['publisher'] = array(
        '@type' => 'Organization',
        'name' => $site_name,
        'url' => home_url('/')
    );
    
    // Publisher logo
    $logo_url = '';
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
    }
    if (empty($logo_url)) {
        $site_icon_id = get_option('site_icon');
        if ($site_icon_id) {
            $logo_url = wp_get_attachment_image_url($site_icon_id, 'full');
        }
    }
    if (empty($logo_url)) {
        $logo_url = plugin_dir_url(dirname(__FILE__)) . 'assets/img/default-schema.jpg';
    }
    $schema['publisher']['logo'] = array(
        '@type' => 'ImageObject',
        'url' => esc_url($logo_url)
    );
    
    // Image with fallback chain
    $image_url = almaseo_get_schema_image($post_id, $post_obj);
    if ($image_url) {
        $schema['image'] = esc_url($image_url);
    }
    
    // Categories for posts
    if ($post_obj->post_type === 'post') {
        $categories = get_the_category($post_id);
        if (!empty($categories)) {
            $schema['articleSection'] = $categories[0]->name;
        }
        
        // Tags as keywords
        $tags = get_the_tags($post_id);
        if ($tags && !is_wp_error($tags)) {
            $tag_names = array();
            foreach ($tags as $tag) {
                $tag_names[] = $tag->name;
            }
            if (!empty($tag_names)) {
                $schema['keywords'] = implode(', ', $tag_names);
            }
        }
    }
    
    // Allow filtering
    return apply_filters('almaseo_schema_data', $schema, $post_id);
}
} // End if function_exists

/**
 * Get schema image with fallback chain
 */
if (!function_exists('almaseo_get_schema_image')) {
function almaseo_get_schema_image($post_id, $post_obj) {
    // 1. Try OG image meta
    $og_image = get_post_meta($post_id, '_almaseo_og_image', true);
    if (!empty($og_image) && !preg_match('/\.svg$/i', $og_image)) {
        return $og_image;
    }
    
    // 2. Try featured image
    if (has_post_thumbnail($post_id)) {
        $image_url = get_the_post_thumbnail_url($post_id, 'full');
        if (!empty($image_url) && !preg_match('/\.svg$/i', $image_url)) {
            return $image_url;
        }
    }
    
    // 3. Try first content image
    if (!empty($post_obj->post_content)) {
        preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $post_obj->post_content, $matches);
        if (!empty($matches[1]) && !preg_match('/\.svg$/i', $matches[1])) {
            $image_url = $matches[1];
            // Make absolute if relative
            if (strpos($image_url, 'http') !== 0) {
                $image_url = home_url($image_url);
            }
            return $image_url;
        }
    }
    
    // 4. Try site icon
    $site_icon_url = get_site_icon_url(512);
    if (!empty($site_icon_url) && !preg_match('/\.svg$/i', $site_icon_url)) {
        return $site_icon_url;
    }
    
    // 5. Try custom logo
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
        if (!empty($logo_url) && !preg_match('/\.svg$/i', $logo_url)) {
            return $logo_url;
        }
    }
    
    // 6. Default fallback
    return plugin_dir_url(dirname(__FILE__)) . 'assets/img/default-schema.jpg';
}
} // End if function_exists

/**
 * Handle Exclusive Schema Mode - improved scrubber
 */
if (get_option('almaseo_exclusive_schema_mode', false)) {
    error_log('[AlmaSEO] Exclusive Mode enabled - registering scrubber');
    
    // Disable other plugin schemas first
    add_action('init', function() {
        // Skip on AMP
        if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
            return;
        }
        
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
        
        // Skip AMP
        if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
            return;
        }
        
        error_log('[AlmaSEO] Starting output buffer for scrubber');
        
        ob_start(function($html) {
            error_log('[AlmaSEO] Scrubber processing HTML length=' . strlen($html));
            
            // Find all JSON-LD scripts
            $pattern = '/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>.*?<\/script>/is';
            
            preg_match_all($pattern, $html, $matches);
            
            $removed_count = 0;
            $preserved_count = 0;
            $our_schema_found = false;
            
            if (!empty($matches[0])) {
                foreach ($matches[0] as $match) {
                    // Check if this is our schema - must have EITHER id OR data attribute
                    $is_ours = false;
                    
                    // Check for id="almaseo-jsonld" (case insensitive)
                    if (preg_match('/\bid=["\']\s*almaseo-jsonld\s*["\']/i', $match)) {
                        $is_ours = true;
                        $our_schema_found = true;
                    }
                    
                    // Check for data-almaseo="1" (case insensitive)
                    if (!$is_ours && preg_match('/\bdata-almaseo=["\']\s*1\s*["\']/i', $match)) {
                        $is_ours = true;
                        $our_schema_found = true;
                    }
                    
                    if ($is_ours) {
                        // This is our schema - preserve it
                        $preserved_count++;
                        error_log('[AlmaSEO] Preserving our schema block');
                        
                        // Add preservation marker after the script
                        $replacement = $match . "\n<!-- AlmaSEO Scrubber: PRESERVED -->";
                        $html = str_replace($match, $replacement, $html);
                    } else {
                        // Remove other schemas
                        $removed_count++;
                        $html = str_replace($match, '', $html);
                    }
                }
            }
            
            // If our schema wasn't found, add warning
            if (!$our_schema_found && (is_singular('post') || is_page() || is_front_page())) {
                $html = str_replace('</head>', "<!-- AlmaSEO Scrubber: REMOVED (BUG) - our schema not found! -->\n</head>", $html);
                error_log('[AlmaSEO] WARNING: Our schema was not found in output!');
            }
            
            error_log('[AlmaSEO] scrubber preserved=' . $preserved_count . ' removed=' . $removed_count);
            
            return $html;
        });
    }, 1); // Very early priority
} else {
    error_log('[AlmaSEO] Exclusive Mode disabled');
}

/**
 * AJAX handler for schema preview in editor - with proper nonce
 */
add_action('wp_ajax_almaseo_get_schema_preview', 'almaseo_handle_schema_preview');
add_action('wp_ajax_nopriv_almaseo_get_schema_preview', 'almaseo_handle_schema_preview'); // Though not needed for editors

if (!function_exists('almaseo_handle_schema_preview')) {
function almaseo_handle_schema_preview() {
    // Check user capability
    if (!current_user_can('edit_posts')) {
        error_log('[AlmaSEO Preview] Failed: insufficient capability');
        wp_send_json_error(array('reason' => 'cap'));
        return;
    }
    
    // Verify nonce if provided
    if (isset($_POST['nonce'])) {
        if (!wp_verify_nonce($_POST['nonce'], 'almaseo_preview_nonce') && 
            !wp_verify_nonce($_POST['nonce'], 'meta-box-order')) {
            error_log('[AlmaSEO Preview] Failed: invalid nonce');
            wp_send_json_error(array('reason' => 'nonce'));
            return;
        }
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    
    if (!$post_id) {
        error_log('[AlmaSEO Preview] Failed: no post ID');
        wp_send_json_error(array('reason' => 'no_post_id'));
        return;
    }
    
    $post_obj = get_post($post_id);
    if (!$post_obj) {
        error_log('[AlmaSEO Preview] Failed: post not found');
        wp_send_json_error(array('reason' => 'post_not_found'));
        return;
    }
    
    // Build schema using same function as frontend
    $schema = almaseo_build_schema_data($post_id, $post_obj);
    
    if ($schema) {
        error_log('[AlmaSEO] preview ok for post ' . $post_id);
        wp_send_json_success(array(
            'json' => wp_json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
        ));
    } else {
        error_log('[AlmaSEO Preview] Failed: could not generate schema');
        wp_send_json_error(array('reason' => 'generation'));
    }
}
} // End if function_exists

// Add admin bar warning if wp_head is missing
add_action('admin_bar_menu', function($wp_admin_bar) {
    if (is_admin() || !current_user_can('manage_options')) {
        return;
    }
    
    // Check if we're on a valid page type
    if (!is_singular('post') && !is_page() && !is_front_page()) {
        return;
    }
    
    // This is a simple check - if our schema didn't output, warn about wp_head
    if (!did_action('wp_head')) {
        $wp_admin_bar->add_node(array(
            'id' => 'almaseo-wp-head-warning',
            'title' => '⚠️ AlmaSEO: Theme missing wp_head()',
            'meta' => array(
                'class' => 'almaseo-admin-bar-warning'
            )
        ));
    }
}, 999);