<?php
/**
 * Advanced Schema Output
 *
 * Generates advanced schema markup including Knowledge Graph, BreadcrumbList,
 * and advanced schema types (FAQPage, HowTo, Service, LocalBusiness, etc.)
 *
 * @package AlmaSEO
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main entry point for advanced schema output
 */
function almaseo_output_advanced_schema() {
    // Guard: Only Pro users
    if (!almaseo_feature_available('schema_advanced')) {
        return;
    }

    // Only run on singular pages (not admin, archives, etc.)
    if (is_admin() || !is_singular()) {
        return;
    }

    $post = get_queried_object();
    if (!$post instanceof WP_Post) {
        return;
    }

    // Check if advanced schema is disabled for this post
    if (get_post_meta($post->ID, '_almaseo_schema_disable', true)) {
        return;
    }

    // Get advanced schema settings
    $settings = get_option('almaseo_schema_advanced_settings', array(
        'enabled' => false,
        'site_represents' => 'organization',
        'site_name' => '',
        'site_logo_url' => '',
        'site_social_profiles' => array(),
        'default_schema_by_post_type' => array(),
        'enable_breadcrumbs' => false
    ));

    // Check if advanced schema is enabled globally
    if (!$settings['enabled']) {
        return;
    }

    // Build the @graph array
    $graph = array();

    // 1. Knowledge Graph node
    $kg_node = almaseo_build_knowledge_graph_node($settings);
    if ($kg_node) {
        $graph[] = $kg_node;
    }

    // 2. BreadcrumbList node
    if ($settings['enable_breadcrumbs']) {
        $breadcrumb_node = almaseo_build_breadcrumb_node($post);
        if ($breadcrumb_node) {
            $graph[] = $breadcrumb_node;
        }
    }

    // 3. Primary schema type node
    $primary_node = almaseo_build_primary_schema_node($post, $settings);
    if ($primary_node) {
        $graph[] = $primary_node;
    }

    // Output JSON-LD if we have any nodes
    if (!empty($graph)) {
        $data = array(
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        );

        echo '<script type="application/ld+json">';
        echo wp_json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        echo '</script>' . "\n";
    }
}

/**
 * Build Knowledge Graph node (Organization or Person)
 *
 * @param array $settings Advanced schema settings
 * @return array|null Knowledge Graph node or null if not configured
 */
function almaseo_build_knowledge_graph_node($settings) {
    // Require at least a name
    if (empty($settings['site_name'])) {
        return null;
    }

    $type = ($settings['site_represents'] === 'person') ? 'Person' : 'Organization';

    $node = array(
        '@type' => $type,
        '@id' => home_url('/#identity'),
        'name' => $settings['site_name'],
        'url' => home_url('/'),
    );

    // Add logo if provided
    if (!empty($settings['site_logo_url'])) {
        $node['logo'] = array(
            '@type' => 'ImageObject',
            'url' => $settings['site_logo_url'],
        );

        // For Organization, also add image property
        if ($type === 'Organization') {
            $node['image'] = $settings['site_logo_url'];
        }
    }

    // Add social profiles if provided
    if (!empty($settings['site_social_profiles']) && is_array($settings['site_social_profiles'])) {
        $node['sameAs'] = $settings['site_social_profiles'];
    }

    return $node;
}

/**
 * Build BreadcrumbList node
 *
 * @param WP_Post $post Current post
 * @return array|null BreadcrumbList node or null if not applicable
 */
function almaseo_build_breadcrumb_node($post) {
    $items = array();
    $position = 1;

    // 1. Homepage
    $items[] = array(
        '@type' => 'ListItem',
        'position' => $position++,
        'name' => get_bloginfo('name'),
        'item' => home_url('/'),
    );

    // 2. Handle different post types
    if ($post->post_type === 'post') {
        // Blog page if set
        $blog_page_id = get_option('page_for_posts');
        if ($blog_page_id) {
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => get_the_title($blog_page_id),
                'item' => get_permalink($blog_page_id),
            );
        }

        // Primary category
        $categories = get_the_category($post->ID);
        if (!empty($categories)) {
            $primary_cat = $categories[0];
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $primary_cat->name,
                'item' => get_category_link($primary_cat->term_id),
            );
        }
    } elseif ($post->post_type === 'page') {
        // Parent pages (if hierarchical)
        $ancestors = array_reverse(get_post_ancestors($post->ID));
        foreach ($ancestors as $ancestor_id) {
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => get_the_title($ancestor_id),
                'item' => get_permalink($ancestor_id),
            );
        }
    } elseif ($post->post_type === 'product' && function_exists('wc_get_product')) {
        // WooCommerce product
        $shop_page_id = get_option('woocommerce_shop_page_id');
        if ($shop_page_id) {
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => get_the_title($shop_page_id),
                'item' => get_permalink($shop_page_id),
            );
        }

        // Primary product category
        $terms = get_the_terms($post->ID, 'product_cat');
        if (!empty($terms) && !is_wp_error($terms)) {
            $primary_term = $terms[0];
            $items[] = array(
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $primary_term->name,
                'item' => get_term_link($primary_term),
            );
        }
    }

    // 3. Current page/post
    $items[] = array(
        '@type' => 'ListItem',
        'position' => $position,
        'name' => get_the_title($post->ID),
        'item' => get_permalink($post->ID),
    );

    return array(
        '@type' => 'BreadcrumbList',
        '@id' => get_permalink($post->ID) . '#breadcrumb',
        'itemListElement' => $items,
    );
}

/**
 * Build primary schema type node
 *
 * @param WP_Post $post Current post
 * @param array $settings Advanced schema settings
 * @return array|null Primary schema node or null if not applicable
 */
function almaseo_build_primary_schema_node($post, $settings) {
    // Determine schema type
    $schema_type = almaseo_determine_schema_type($post, $settings);

    if (!$schema_type) {
        return null;
    }

    // Build node based on type
    switch ($schema_type) {
        case 'FAQPage':
            return almaseo_build_faqpage_node($post);
        case 'HowTo':
            return almaseo_build_howto_node($post);
        case 'Service':
            return almaseo_build_service_node($post);
        case 'LocalBusiness':
            return almaseo_build_localbusiness_node($post);
        case 'Article':
        case 'BlogPosting':
        case 'NewsArticle':
        default:
            return almaseo_build_article_node($post, $schema_type);
    }
}

/**
 * Determine the schema type for a post
 *
 * @param WP_Post $post Current post
 * @param array $settings Advanced schema settings
 * @return string|null Schema type or null
 */
function almaseo_determine_schema_type($post, $settings) {
    // 1. Check per-post primary type
    $primary_type = get_post_meta($post->ID, '_almaseo_schema_primary_type', true);
    if (!empty($primary_type)) {
        return $primary_type;
    }

    // 2. Check per-post toggles
    if (get_post_meta($post->ID, '_almaseo_schema_is_faqpage', true)) {
        return 'FAQPage';
    }
    if (get_post_meta($post->ID, '_almaseo_schema_is_howto', true)) {
        return 'HowTo';
    }

    // 3. Check default for this post type
    if (isset($settings['default_schema_by_post_type'][$post->post_type])) {
        $default_type = $settings['default_schema_by_post_type'][$post->post_type];
        if (!empty($default_type)) {
            return $default_type;
        }
    }

    // 4. Fall back to Article
    return 'Article';
}

/**
 * Build Article/BlogPosting/NewsArticle node
 *
 * @param WP_Post $post Current post
 * @param string $type Schema type (Article, BlogPosting, NewsArticle)
 * @return array Article node
 */
function almaseo_build_article_node($post, $type = 'Article') {
    $seo_title = get_post_meta($post->ID, '_almaseo_title', true);
    $seo_description = get_post_meta($post->ID, '_almaseo_description', true);
    $headline = $seo_title ?: get_the_title($post->ID);
    $description = $seo_description ?: wp_trim_words(wp_strip_all_tags($post->post_content), 30);

    $node = array(
        '@type' => $type,
        '@id' => get_permalink($post->ID) . '#article',
        'headline' => $headline,
        'description' => $description,
        'url' => get_permalink($post->ID),
        'datePublished' => get_the_date('c', $post->ID),
        'dateModified' => get_the_modified_date('c', $post->ID),
    );

    // Author
    $author_name = get_the_author_meta('display_name', $post->post_author);
    $node['author'] = array(
        '@type' => 'Person',
        'name' => $author_name,
    );

    // Publisher (reference Knowledge Graph if exists)
    $site_name = get_bloginfo('name');
    $node['publisher'] = array(
        '@type' => 'Organization',
        'name' => $site_name,
        '@id' => home_url('/#identity'),
    );

    // Image (featured image or OG image)
    $og_image = get_post_meta($post->ID, '_almaseo_og_image', true);
    $featured_image = get_the_post_thumbnail_url($post->ID, 'large');
    $image_url = $og_image ?: $featured_image;

    if ($image_url) {
        $node['image'] = array(
            '@type' => 'ImageObject',
            'url' => $image_url,
        );
    }

    return $node;
}

/**
 * Build FAQPage node
 *
 * @param WP_Post $post Current post
 * @return array FAQPage node
 */
function almaseo_build_faqpage_node($post) {
    $seo_title = get_post_meta($post->ID, '_almaseo_title', true);
    $headline = $seo_title ?: get_the_title($post->ID);

    $node = array(
        '@type' => 'FAQPage',
        '@id' => get_permalink($post->ID) . '#faqpage',
        'headline' => $headline,
        'url' => get_permalink($post->ID),
    );

    // Try to extract Q&A pairs from content
    $qa_pairs = almaseo_extract_qa_pairs($post->post_content);

    if (!empty($qa_pairs)) {
        $node['mainEntity'] = $qa_pairs;
    }

    return $node;
}

/**
 * Extract Q&A pairs from content
 *
 * @param string $content Post content (HTML)
 * @return array Array of Question nodes
 */
function almaseo_extract_qa_pairs($content) {
    $qa_pairs = array();

    // Find headings that end with ? (questions)
    preg_match_all('/<h([2-6])[^>]*>(.*?\?[^<]*)<\/h\1>\s*<p[^>]*>(.*?)<\/p>/is', $content, $matches, PREG_SET_ORDER);

    foreach ($matches as $match) {
        $question = wp_strip_all_tags($match[2]);
        $answer = wp_strip_all_tags($match[3]);

        $qa_pairs[] = array(
            '@type' => 'Question',
            'name' => trim($question),
            'acceptedAnswer' => array(
                '@type' => 'Answer',
                'text' => trim($answer),
            ),
        );

        // Limit to 10 Q&A pairs
        if (count($qa_pairs) >= 10) {
            break;
        }
    }

    return $qa_pairs;
}

/**
 * Build HowTo node
 *
 * @param WP_Post $post Current post
 * @return array HowTo node
 */
function almaseo_build_howto_node($post) {
    $seo_title = get_post_meta($post->ID, '_almaseo_title', true);
    $seo_description = get_post_meta($post->ID, '_almaseo_description', true);
    $name = $seo_title ?: get_the_title($post->ID);
    $description = $seo_description ?: wp_trim_words(wp_strip_all_tags($post->post_content), 30);

    $node = array(
        '@type' => 'HowTo',
        '@id' => get_permalink($post->ID) . '#howto',
        'name' => $name,
        'description' => $description,
        'url' => get_permalink($post->ID),
    );

    // Try to extract steps from content (ordered/unordered lists or numbered headings)
    $steps = almaseo_extract_howto_steps($post->post_content);

    if (!empty($steps)) {
        $node['step'] = $steps;
    }

    return $node;
}

/**
 * Extract HowTo steps from content
 *
 * @param string $content Post content (HTML)
 * @return array Array of HowToStep nodes
 */
function almaseo_extract_howto_steps($content) {
    $steps = array();

    // Try to find ordered/unordered lists
    preg_match('/<[ou]l[^>]*>(.*?)<\/[ou]l>/is', $content, $list_match);

    if (!empty($list_match[1])) {
        preg_match_all('/<li[^>]*>(.*?)<\/li>/is', $list_match[1], $items);

        foreach ($items[1] as $item) {
            $text = wp_strip_all_tags($item);
            if (!empty(trim($text))) {
                $steps[] = array(
                    '@type' => 'HowToStep',
                    'text' => trim($text),
                );
            }

            // Limit to 20 steps
            if (count($steps) >= 20) {
                break;
            }
        }
    }

    return $steps;
}

/**
 * Build Service node
 *
 * @param WP_Post $post Current post
 * @return array Service node
 */
function almaseo_build_service_node($post) {
    $seo_title = get_post_meta($post->ID, '_almaseo_title', true);
    $seo_description = get_post_meta($post->ID, '_almaseo_description', true);
    $name = $seo_title ?: get_the_title($post->ID);
    $description = $seo_description ?: wp_trim_words(wp_strip_all_tags($post->post_content), 30);

    $node = array(
        '@type' => 'Service',
        '@id' => get_permalink($post->ID) . '#service',
        'name' => $name,
        'description' => $description,
        'url' => get_permalink($post->ID),
    );

    // Link to provider (Knowledge Graph if exists)
    $node['provider'] = array(
        '@type' => 'Organization',
        '@id' => home_url('/#identity'),
    );

    return $node;
}

/**
 * Build LocalBusiness node
 *
 * @param WP_Post $post Current post
 * @return array LocalBusiness node
 */
function almaseo_build_localbusiness_node($post) {
    $seo_title = get_post_meta($post->ID, '_almaseo_title', true);
    $seo_description = get_post_meta($post->ID, '_almaseo_description', true);
    $name = $seo_title ?: get_the_title($post->ID);
    $description = $seo_description ?: wp_trim_words(wp_strip_all_tags($post->post_content), 30);

    $node = array(
        '@type' => 'LocalBusiness',
        '@id' => get_permalink($post->ID) . '#business',
        'name' => $name,
        'description' => $description,
        'url' => get_permalink($post->ID),
    );

    return $node;
}
