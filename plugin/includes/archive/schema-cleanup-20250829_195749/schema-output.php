<?php
/**
 * AlmaSEO Direct Schema Output
 * Simple, direct schema output that definitely works
 * 
 * @package AlmaSEO
 * @version 2.9.1
 */

if (!defined('ABSPATH')) {
    exit;
}

// Hook directly into wp_head with high priority
add_action('wp_head', 'almaseo_output_schema_jsonld', 2);

/**
 * Output schema JSON-LD
 */
if (!function_exists('almaseo_output_schema_jsonld')) {
function almaseo_output_schema_jsonld() {
    // Static guard to prevent duplicate output
    static $schema_rendered = false;
    if ($schema_rendered) {
        return;
    }
    
    // Check if we should output schema
    if (!almaseo_should_output_schema()) {
        return;
    }
    
    // Get schema data
    $schema_data = almaseo_get_schema_data();
    if (empty($schema_data)) {
        return;
    }
    
    // Mark as rendered
    $schema_rendered = true;
    
    // Output the JSON-LD
    $json_output = wp_json_encode($schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
    echo "\n<!-- AlmaSEO Schema Start -->\n";
    echo '<script type="application/ld+json" id="almaseo-jsonld" data-almaseo="1">' . "\n";
    echo $json_output . "\n";
    echo '</script>' . "\n";
    echo "<!-- AlmaSEO Schema End -->\n\n";
}
} // End if function_exists

/**
 * Check if schema should be output
 */
if (!function_exists('almaseo_should_output_schema')) {
function almaseo_should_output_schema() {
    // Skip on admin and feeds
    if (is_admin() || is_feed()) {
        return false;
    }
    
    // Skip on AMP
    if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
        return false;
    }
    
    // Skip on archives and search
    if (is_archive() || is_search()) {
        return false;
    }
    
    // Skip on blog home that isn't static page
    if (is_home() && !is_front_page()) {
        return false;
    }
    
    // Output on singular posts, pages, and static front page
    return is_singular('post') || is_page() || (is_front_page() && get_option('show_on_front') === 'page');
}
} // End if function_exists

/**
 * Get schema data
 */
if (!function_exists('almaseo_get_schema_data')) {
function almaseo_get_schema_data() {
    global $post;
    
    // Get post ID
    $post_id = 0;
    if (is_singular() && $post) {
        $post_id = $post->ID;
    } elseif (is_front_page() && get_option('show_on_front') === 'page') {
        $post_id = get_option('page_on_front');
    }
    
    if (!$post_id) {
        return null;
    }
    
    $post_obj = get_post($post_id);
    if (!$post_obj) {
        return null;
    }
    
    // Get per-post toggle settings
    $include_author = get_post_meta($post_id, '_almaseo_schema_include_author', true);
    $include_image = get_post_meta($post_id, '_almaseo_schema_include_image', true);
    $include_publisher = get_post_meta($post_id, '_almaseo_schema_include_publisher', true);
    
    // Default to true if not set
    if ($include_author === '') $include_author = true;
    if ($include_image === '') $include_image = true;
    if ($include_publisher === '') $include_publisher = true;
    
    // Get schema type (default to BlogPosting)
    $schema_type = get_post_meta($post_id, '_almaseo_schema_type', true);
    if (empty($schema_type)) {
        $schema_type = 'BlogPosting';
    }
    
    // For product post type, skip unless explicitly set
    if (get_post_type($post_id) === 'product' && $schema_type === 'BlogPosting') {
        // Don't output BlogPosting schema for products to avoid conflicts with WooCommerce
        return null;
    }
    
    // Build schema
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => $schema_type
    );
    
    // URL and canonical
    $canonical = almaseo_get_canonical_url($post_id);
    $schema['url'] = $canonical;
    $schema['mainEntityOfPage'] = array(
        '@type' => 'WebPage',
        '@id' => $canonical . '#webpage'
    );
    
    // Title
    $title = get_post_meta($post_id, '_almaseo_title', true);
    if (empty($title)) {
        $title = $post_obj->post_title;
    }
    $schema['headline'] = almaseo_clean_text($title);
    
    // Description
    $description = get_post_meta($post_id, '_almaseo_description', true);
    if (empty($description)) {
        $description = has_excerpt($post_id) ? get_the_excerpt($post_obj) : wp_trim_words($post_obj->post_content, 30);
    }
    $schema['description'] = almaseo_clean_text($description);
    
    // Author (only if toggle is on)
    if ($include_author) {
        $author_id = $post_obj->post_author;
        $author_data = get_userdata($author_id);
        
        // Check if author archives are disabled
        $author_url = get_author_posts_url($author_id);
        if (!get_option('author_base', true)) {
            // Author archives disabled, use homepage as fallback
            $author_url = home_url('/');
        }
        
        $schema['author'] = array(
            '@type' => 'Person',
            'name' => $author_data ? $author_data->display_name : get_bloginfo('name'),
            'url' => $author_url
        );
    }
    
    // Dates
    $schema['datePublished'] = get_the_date('c', $post_obj);
    $schema['dateModified'] = get_the_modified_date('c', $post_obj);
    
    // Image (only if toggle is on)
    if ($include_image) {
        $image_url = almaseo_get_schema_image($post_id, $post_obj);
        if ($image_url) {
            $schema['image'] = $image_url;
        }
    }
    
    // Publisher (only if toggle is on)
    if ($include_publisher) {
        $schema['publisher'] = array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url('/'),
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => almaseo_get_site_logo()
            )
        );
    }
    
    // Categories
    if (is_singular('post')) {
        $categories = get_the_category($post_id);
        if (!empty($categories)) {
            $schema['articleSection'] = $categories[0]->name;
        }
    }
    
    // Keywords from tags
    if (is_singular('post')) {
        $tags = get_the_tags($post_id);
        if (!empty($tags)) {
            $keywords = array();
            foreach ($tags as $tag) {
                $keywords[] = $tag->name;
            }
            $schema['keywords'] = implode(', ', $keywords);
        }
    }
    
    return apply_filters('almaseo_schema_data', $schema, $post_id);
}
} // End if function_exists

/**
 * Get canonical URL
 */
if (!function_exists('almaseo_get_canonical_url')) {
function almaseo_get_canonical_url($post_id) {
    $canonical = get_post_meta($post_id, '_almaseo_canonical_url', true);
    if (empty($canonical)) {
        $canonical = get_permalink($post_id);
    }
    return esc_url($canonical);
}
} // End if function_exists

/**
 * Get schema image
 */
if (!function_exists('almaseo_get_schema_image')) {
function almaseo_get_schema_image($post_id, $post_obj) {
    // Try OG image
    $og_image = get_post_meta($post_id, '_almaseo_og_image', true);
    if (!empty($og_image)) {
        return esc_url($og_image);
    }
    
    // Try featured image
    if (has_post_thumbnail($post_id)) {
        $image_url = get_the_post_thumbnail_url($post_id, 'full');
        if ($image_url) {
            return esc_url($image_url);
        }
    }
    
    // Try first content image
    if (preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $post_obj->post_content, $matches)) {
        if (!empty($matches[1])) {
            return esc_url($matches[1]);
        }
    }
    
    // Fallback to site logo
    return almaseo_get_site_logo();
}
} // End if function_exists

/**
 * Get site logo
 */
if (!function_exists('almaseo_get_site_logo')) {
function almaseo_get_site_logo() {
    // Try custom logo
    $custom_logo_id = get_theme_mod('custom_logo');
    if ($custom_logo_id) {
        $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
        if ($logo_url) {
            return esc_url($logo_url);
        }
    }
    
    // Try site icon
    $site_icon_url = get_site_icon_url(512);
    if ($site_icon_url) {
        return esc_url($site_icon_url);
    }
    
    // Default
    return plugin_dir_url(dirname(__FILE__)) . 'assets/img/default-schema.jpg';
}
} // End if function_exists

/**
 * Clean text for schema
 */
if (!function_exists('almaseo_clean_text')) {
function almaseo_clean_text($text) {
    $text = wp_strip_all_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, get_bloginfo('charset'));
    $text = preg_replace('/\s+/', ' ', $text);
    return trim($text);
}
} // End if function_exists

/**
 * Handle Exclusive Schema Mode
 */
if (get_option('almaseo_exclusive_schema_mode', false)) {
    // Disable other plugin schemas
    add_action('init', 'almaseo_disable_other_schemas', 1);
    
    // Start output buffer to remove remaining schemas
    add_action('template_redirect', 'almaseo_start_schema_buffer', 1);
}

/**
 * Disable other plugin schemas
 */
if (!function_exists('almaseo_disable_other_schemas')) {
function almaseo_disable_other_schemas() {
    // Skip on AMP
    if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
        return;
    }
    
    // Yoast
    add_filter('wpseo_json_ld_output', '__return_false', 999);
    add_filter('wpseo_schema_graph', '__return_false', 999);
    add_filter('wpseo_schema_graph_pieces', '__return_empty_array', 999);
    
    // Rank Math
    add_filter('rank_math/json_ld', '__return_false', 999);
    add_filter('rank_math/json_ld/enabled', '__return_false', 999);
    
    // SEOPress
    add_filter('seopress_schemas_enabled', '__return_false', 999);
    
    // AIOSEO
    add_filter('aioseo_schema_disable', '__return_true', 999);
    add_filter('aioseo_schema_output', '__return_false', 999);
}
} // End if function_exists

/**
 * Start buffer for schema removal
 */
if (!function_exists('almaseo_start_schema_buffer')) {
function almaseo_start_schema_buffer() {
    if (is_admin() || is_feed() || (function_exists('is_amp_endpoint') && is_amp_endpoint())) {
        return;
    }
    
    ob_start('almaseo_clean_other_schemas');
}
} // End if function_exists

/**
 * Remove other JSON-LD blocks
 */
if (!function_exists('almaseo_clean_other_schemas')) {
function almaseo_clean_other_schemas($html) {
    // Keep only our schema
    $pattern = '/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>.*?<\/script>/is';
    
    preg_match_all($pattern, $html, $matches);
    
    if (!empty($matches[0])) {
        foreach ($matches[0] as $match) {
            // Keep our schema
            if (strpos($match, 'id="almaseo-jsonld"') !== false && 
                strpos($match, 'data-almaseo="1"') !== false) {
                continue;
            }
            
            // Remove others
            $html = str_replace($match, '', $html);
        }
    }
    
    return $html;
}
} // End if function_exists