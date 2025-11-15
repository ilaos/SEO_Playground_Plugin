<?php
/**
 * AlmaSEO Default Settings
 * Safe production defaults
 * 
 * @package AlmaSEO
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

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
            'enabled' => false,  // Require multilingual setup
            'source' => 'auto'
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
                'fetch_oembed' => true
            )
        ),
        'news' => array(
            'enabled' => false,  // Require news site confirmation
            'window_hours' => 48,
            'max_items' => 1000,
            'post_types' => array('post'),
            'publisher_name' => get_bloginfo('name'),
            'language' => substr(get_locale(), 0, 2)
        )
    );
}
