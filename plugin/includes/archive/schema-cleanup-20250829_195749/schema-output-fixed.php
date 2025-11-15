<?php
/**
 * AlmaSEO Schema Output - Fixed Version
 * Direct, simple schema output with debug capability
 * 
 * @package AlmaSEO
 * @version 2.9.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Hook directly into wp_head - this WILL execute
add_action('wp_head', 'almaseo_output_schema_jsonld', 2);

/**
 * Output schema JSON-LD with debug markers
 */
if (!function_exists('almaseo_output_schema_jsonld')) {
function almaseo_output_schema_jsonld() {
    // Debug marker to prove hook runs
    echo "<!-- AlmaSEO Hook Start -->\n";
    
    try {
        // Static guard
        static $schema_rendered = false;
        if ($schema_rendered) {
            echo "<!-- AlmaSEO: Already rendered -->\n";
            echo "<!-- AlmaSEO Hook End -->\n";
            return;
        }
        
        // Check conditions
        if (!almaseo_should_output_schema()) {
            echo "<!-- AlmaSEO: Conditions not met for output -->\n";
            echo "<!-- AlmaSEO Hook End -->\n";
            return;
        }
        
        // Get schema data
        $schema_data = almaseo_get_schema_data();
        if (empty($schema_data)) {
            echo "<!-- AlmaSEO: No schema data generated -->\n";
            echo "<!-- AlmaSEO Hook End -->\n";
            return;
        }
        
        // Mark as rendered
        $schema_rendered = true;
        
        // Output the schema
        $json_output = wp_json_encode($schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        
        echo "<!-- AlmaSEO Schema Start -->\n";
        echo '<script type="application/ld+json" id="almaseo-jsonld" data-almaseo="1">' . "\n";
        echo $json_output . "\n";
        echo '</script>' . "\n";
        echo "<!-- AlmaSEO Schema End -->\n";
        
    } catch (Exception $e) {
        error_log('[AlmaSEO Schema] Error: ' . $e->getMessage());
        echo "<!-- AlmaSEO Error: " . esc_html($e->getMessage()) . " -->\n";
    }
    
    echo "<!-- AlmaSEO Hook End -->\n";
}
} // End if function_exists

/**
 * Check if schema should output
 */
if (!function_exists('almaseo_should_output_schema')) {
function almaseo_should_output_schema() {
    // Never on admin or feeds
    if (is_admin() || is_feed()) {
        return false;
    }
    
    // Skip AMP only if really AMP
    if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
        return false;
    }
    
    // Skip archives and search
    if (is_archive() || is_search()) {
        return false;
    }
    
    // Skip blog home (posts index) that's not front page
    if (is_home() && !is_front_page()) {
        return false;
    }
    
    // OUTPUT on these pages
    $should_output = is_singular('post') || is_page() || is_front_page();
    
    return $should_output;
}
} // End if function_exists

/**
 * Get schema data - simplified
 */
if (!function_exists('almaseo_get_schema_data')) {
function almaseo_get_schema_data() {
    global $post;
    
    // Get post ID
    $post_id = 0;
    if (is_singular() && isset($post->ID)) {
        $post_id = $post->ID;
    } elseif (is_front_page()) {
        $page_on_front = get_option('page_on_front');
        if ($page_on_front) {
            $post_id = $page_on_front;
        }
    }
    
    // No post? No schema
    if (!$post_id) {
        return null;
    }
    
    $post_obj = get_post($post_id);
    if (!$post_obj) {
        return null;
    }
    
    // Build schema array
    $schema = array(
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting'
    );
    
    // URL
    $permalink = get_permalink($post_id);
    $schema['url'] = $permalink;
    $schema['mainEntityOfPage'] = array(
        '@type' => 'WebPage',
        '@id' => $permalink . '#webpage'
    );
    
    // Title
    $title = get_post_meta($post_id, '_almaseo_title', true);
    if (empty($title)) {
        $title = get_the_title($post_id);
    }
    $schema['headline'] = wp_strip_all_tags($title);
    
    // Description
    $description = get_post_meta($post_id, '_almaseo_description', true);
    if (empty($description) && has_excerpt($post_id)) {
        $description = get_the_excerpt($post_obj);
    }
    if (empty($description)) {
        $description = wp_trim_words($post_obj->post_content, 30, '...');
    }
    $schema['description'] = wp_strip_all_tags($description);
    
    // Clean description of entities
    $schema['description'] = html_entity_decode($schema['description'], ENT_QUOTES | ENT_HTML5, get_bloginfo('charset'));
    
    // Author
    $author_id = $post_obj->post_author;
    $author_data = get_userdata($author_id);
    if ($author_data) {
        $author_url = get_author_posts_url($author_id);
        $schema['author'] = array(
            '@type' => 'Person',
            'name' => $author_data->display_name,
            'url' => $author_url ?: home_url('/')
        );
    } else {
        $schema['author'] = array(
            '@type' => 'Person',
            'name' => get_bloginfo('name'),
            'url' => home_url('/')
        );
    }
    
    // Dates
    $schema['datePublished'] = get_the_date('c', $post_id);
    $schema['dateModified'] = get_the_modified_date('c', $post_id);
    
    // Image
    $image_url = '';
    
    // Try OG image
    $og_image = get_post_meta($post_id, '_almaseo_og_image', true);
    if (!empty($og_image)) {
        $image_url = $og_image;
    }
    
    // Try featured image
    if (empty($image_url) && has_post_thumbnail($post_id)) {
        $image_url = get_the_post_thumbnail_url($post_id, 'full');
    }
    
    // Try first content image
    if (empty($image_url)) {
        preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $post_obj->post_content, $matches);
        if (!empty($matches[1])) {
            $image_url = $matches[1];
        }
    }
    
    // Default image
    if (empty($image_url)) {
        $image_url = plugin_dir_url(dirname(__FILE__)) . 'assets/img/default-schema.jpg';
    }
    
    // Ensure absolute URL
    if (strpos($image_url, 'http') !== 0) {
        $image_url = home_url($image_url);
    }
    
    $schema['image'] = esc_url($image_url);
    
    // Publisher
    $schema['publisher'] = array(
        '@type' => 'Organization',
        'name' => get_bloginfo('name'),
        'url' => home_url('/'),
        'logo' => array(
            '@type' => 'ImageObject',
            'url' => $image_url // Use same image as logo fallback
        )
    );
    
    // Categories for posts
    if ($post_obj->post_type === 'post') {
        $categories = get_the_category($post_id);
        if (!empty($categories)) {
            $schema['articleSection'] = $categories[0]->name;
        }
        
        // Tags as keywords
        $tags = get_the_tags($post_id);
        if ($tags) {
            $tag_names = array();
            foreach ($tags as $tag) {
                $tag_names[] = $tag->name;
            }
            $schema['keywords'] = implode(', ', $tag_names);
        }
    }
    
    return $schema;
}
} // End if function_exists

/**
 * Exclusive Schema Mode
 */
if (get_option('almaseo_exclusive_schema_mode', false)) {
    // Disable other schemas
    add_action('init', function() {
        // Yoast
        add_filter('wpseo_json_ld_output', '__return_false', 999);
        add_filter('wpseo_schema_graph', '__return_false', 999);
        
        // Rank Math
        add_filter('rank_math/json_ld', '__return_false', 999);
        
        // Others
        add_filter('seopress_schemas_enabled', '__return_false', 999);
        add_filter('aioseo_schema_disable', '__return_true', 999);
    }, 1);
    
    // Buffer to remove remaining schemas
    add_action('template_redirect', function() {
        if (is_admin() || is_feed()) return;
        
        ob_start(function($html) {
            // Remove all JSON-LD except ours
            $pattern = '/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>.*?<\/script>/is';
            
            preg_match_all($pattern, $html, $matches);
            
            foreach ($matches[0] as $match) {
                // Keep our schema
                if (strpos($match, 'id="almaseo-jsonld"') !== false && 
                    strpos($match, 'data-almaseo="1"') !== false) {
                    continue;
                }
                
                // Remove others
                $html = str_replace($match, '', $html);
            }
            
            return $html;
        });
    }, 1);
}

/**
 * Add admin bar indicator for debugging
 */
add_action('admin_bar_menu', function($wp_admin_bar) {
    if (is_admin() || !current_user_can('manage_options')) return;
    
    $schema_active = almaseo_should_output_schema();
    
    $wp_admin_bar->add_node(array(
        'id' => 'almaseo-schema-status',
        'title' => $schema_active ? '✓ Schema Active' : '✗ Schema Inactive',
        'meta' => array(
            'title' => $schema_active ? 'AlmaSEO Schema is outputting on this page' : 'AlmaSEO Schema not outputting here'
        )
    ));
}, 100);

/**
 * AJAX handler for preview drawer
 */
add_action('wp_ajax_almaseo_get_schema_preview', function() {
    if (!current_user_can('edit_posts')) {
        wp_die();
    }
    
    $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
    if (!$post_id) {
        wp_send_json_error('No post ID');
    }
    
    // Set global post for context
    global $post;
    $post = get_post($post_id);
    setup_postdata($post);
    
    // Get schema data
    $schema_data = almaseo_get_schema_data();
    
    wp_reset_postdata();
    
    if ($schema_data) {
        wp_send_json_success(array(
            'json' => wp_json_encode($schema_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        ));
    } else {
        wp_send_json_error('Could not generate schema');
    }
});