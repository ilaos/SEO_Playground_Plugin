<?php
/**
 * AlmaSEO Front-End Meta Tag Rendering
 *
 * Handles document title filtering, meta robots, canonical URLs,
 * Open Graph, Twitter Cards, and JSON-LD schema output on the front-end.
 *
 * @package AlmaSEO
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Filter the document title
add_filter('document_title_parts', 'almaseo_filter_document_title', 10);
function almaseo_filter_document_title($title) {
    if (is_singular()) {
        global $post;
        if (!$post) return $title;
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
        if (!$post) return $title;
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
    if (!$post) return;
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
