<?php
/**
 * AlmaSEO Default Settings
 * Safe production defaults
 * 
 * @package AlmaSEO
 * @since 5.0.0
 */

// phpcs:disable WordPressVIPMinimum.Performance.WPQueryParams -- intentional exclusion parameter on a bounded query

if (!defined('ABSPATH')) {
    exit;
}

// Per-function guard for safe coexistence with the AlmaSEO Connector or any
// other plugin that may already declare this helper.
if (!function_exists('almaseo_get_default_settings')) {
function almaseo_get_default_settings() {
    return array(
        'enabled' => true,
        'takeover' => false,  // Safe: don't hijack by default
        'include' => array(
            'posts' => true,
            'pages' => true,
            'cpts' => 'all',
            'tax' => array(
                'category' => true,
                'post_tag' => true
            ),
            'users' => false
        ),
        'links_per_sitemap' => 1000,
        'perf' => array(
            'storage_mode' => 'static',  // Best performance
            'gzip' => true,
            'chunk_size' => 100,
            'build_lock_timeout' => 900
        ),
        'indexnow' => array(
            'enabled' => false,  // Require explicit enablement
            'key' => '',
            'engines' => array('bing', 'yandex')
        ),
        'delta' => array(
            'enabled' => true,  // Track changes by default
            'max_urls' => 500,
            'retention_days' => 14
        ),
        'hreflang' => array(
            'enabled'       => false,  // Require multilingual setup
            'source'        => 'auto',
            'default'       => '',
            'x_default_url' => '',
            'map'           => array(),
            'locales'       => array(),
        ),
        'media' => array(
            'image' => array(
                'enabled' => true,  // Include images
                'max_per_url' => 20,
                'dedupe_cdn' => true
            ),
            'video' => array(
                'enabled' => true,  // Include videos
                'max_per_url' => 10,
                // Key matches what the save handler and media partial read
                // (was `fetch_oembed` previously, which nothing else touched).
                'oembed_cache' => true,
            )
        ),
        'news' => array(
            'enabled' => false,  // Require news site confirmation
            'window_hours' => 48,
            'max_items' => 1000,
            'post_types' => array('post'),
            'categories' => array(),
            'genres' => array(),
            'keywords_source' => 'tags',
            'manual_keywords' => '',
            'publisher_name' => get_bloginfo('name'),
            'language' => substr(get_locale(), 0, 2)
        ),
        'exclude' => array(
            'taxonomies'       => array(),
            'authors'          => array(),
            'older_than_years' => 0,
        ),
        'health' => array(),
    );
}
} // end function_exists guard
