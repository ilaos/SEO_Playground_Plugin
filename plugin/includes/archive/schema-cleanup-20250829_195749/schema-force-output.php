<?php
/**
 * Force Schema Output - Bypasses all guards for testing
 */

if (!defined('ABSPATH')) {
    exit;
}

// Remove ANY other schema output hooks that might interfere
remove_all_actions('wp_head', 1);
remove_all_actions('wp_head', 2);
remove_all_actions('wp_head', 3);

// Force output with HIGHEST priority (0)
add_action('wp_head', function() {
    // Skip admin and feeds only
    if (is_admin() || is_feed()) {
        return;
    }
    
    // Get current page type for debugging
    $page_type = 'unknown';
    if (is_singular('post')) $page_type = 'post';
    elseif (is_page()) $page_type = 'page';  
    elseif (is_front_page()) $page_type = 'front_page';
    elseif (is_home()) $page_type = 'blog_home';
    elseif (is_archive()) $page_type = 'archive';
    elseif (is_search()) $page_type = 'search';
    
    echo "\n<!-- AlmaSEO Force Output Active -->\n";
    echo "<!-- Page Type Detected: {$page_type} -->\n";
    echo "<!-- is_singular: " . (is_singular() ? 'true' : 'false') . " -->\n";
    echo "<!-- is_single: " . (is_single() ? 'true' : 'false') . " -->\n";
    echo "<!-- is_page: " . (is_page() ? 'true' : 'false') . " -->\n";
    
    // Output on posts, pages, and front page ONLY
    if (!is_singular('post') && !is_page() && !is_front_page()) {
        echo "<!-- AlmaSEO: Not outputting on this page type -->\n";
        return;
    }
    
    // Get post data
    global $post;
    $post_id = 0;
    
    if (is_singular() && isset($post->ID)) {
        $post_id = $post->ID;
    } elseif (is_front_page()) {
        $post_id = get_option('page_on_front') ?: 0;
    }
    
    echo "<!-- Post ID: {$post_id} -->\n";
    
    if (!$post_id) {
        echo "<!-- AlmaSEO: No post ID found -->\n";
        return;
    }
    
    $post_obj = get_post($post_id);
    if (!$post_obj) {
        echo "<!-- AlmaSEO: No post object found -->\n";
        return;
    }
    
    // Build SIMPLE schema
    $schema = [
        '@context' => 'https://schema.org',
        '@type' => 'BlogPosting',
        'headline' => wp_strip_all_tags($post_obj->post_title ?: 'Untitled'),
        'url' => get_permalink($post_id),
        'datePublished' => get_the_date('c', $post_id),
        'dateModified' => get_the_modified_date('c', $post_id),
        'author' => [
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', $post_obj->post_author) ?: 'Unknown',
            'url' => get_author_posts_url($post_obj->post_author) ?: home_url('/')
        ],
        'publisher' => [
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url('/')
        ]
    ];
    
    // Add description
    $desc = get_post_meta($post_id, '_almaseo_description', true);
    if (empty($desc)) {
        $desc = wp_trim_words($post_obj->post_content, 30);
    }
    $schema['description'] = wp_strip_all_tags($desc ?: 'No description');
    
    // Add image
    if (has_post_thumbnail($post_id)) {
        $schema['image'] = get_the_post_thumbnail_url($post_id, 'full');
    } else {
        $schema['image'] = plugin_dir_url(dirname(__FILE__)) . 'assets/img/default-schema.jpg';
    }
    
    // OUTPUT THE SCHEMA
    echo "<!-- AlmaSEO Schema Start -->\n";
    echo '<script type="application/ld+json" id="almaseo-jsonld" data-almaseo="1">' . "\n";
    echo json_encode($schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    echo "\n</script>\n";
    echo "<!-- AlmaSEO Schema End -->\n\n";
    
}, 0); // Priority 0 = runs FIRST