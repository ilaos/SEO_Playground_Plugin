<?php
/**
 * AlmaSEO Sitemap Helper Functions
 * 
 * @package AlmaSEO
 * @since 5.3.4
 */

if (!defined('ABSPATH')) {
    exit;
}

// Per-function guards. Previously a single top-of-file bail checked only
// almaseo_get_index_url() — if that singular was already declared (junction
// path duplicate, stale opcache, Connector coexistence) the file returned
// before declaring almaseo_get_index_urls() below, fatalling on the Sitemaps
// admin page. Each function now guards itself.
if (!function_exists('almaseo_get_index_url')) {
    function almaseo_get_index_url() {
        $settings = get_option('almaseo_sitemap_settings', []);
        $takeover = !empty($settings['takeover']);

        return [
            'primary' => $takeover ? home_url('/sitemap.xml') : home_url('/sitemap_index.xml'),
            'direct' => home_url('/sitemap_index.xml'),
            'takeover' => $takeover
        ];
    }
}

/**
 * Get sitemap index URLs (alternative name)
 * 
 * @return array {
 *     @type string $primary  The primary URL to use (respects takeover)
 *     @type string $direct   The direct AlmaSEO sitemap URL
 *     @type bool   $takeover Whether takeover mode is active
 *     @type bool   $enabled  Whether sitemaps are enabled
 * }
 */
if (!function_exists('almaseo_get_index_urls')) {
    function almaseo_get_index_urls(): array {
        $s = get_option('almaseo_sitemap_settings', []);
        $take = !empty($s['takeover']);
        $home = rtrim(home_url('/'), '/');
        
        return [
            'primary'  => $home . ($take ? '/sitemap.xml' : '/almaseo-sitemap.xml'),
            'direct'   => $home . '/almaseo-sitemap.xml',
            'takeover' => $take,
            'enabled'  => !empty($s['enabled']),
        ];
    }
}

if (!function_exists('almaseo_sitemaps_enabled')) {
    function almaseo_sitemaps_enabled() {
        $settings = get_option('almaseo_sitemap_settings', []);
        return !empty($settings['enabled']);
    }
}

if (!function_exists('almaseo_get_indexnow_key')) {
    function almaseo_get_indexnow_key() {
        $settings = get_option('almaseo_sitemap_settings', []);
        $key = $settings['indexnow']['key'] ?? '';
        return !empty($key) ? $key : false;
    }
}

if (!function_exists('almaseo_get_build_stats')) {
    function almaseo_get_build_stats() {
        $s = get_option('almaseo_sitemap_settings', []);
        $h = $s['health']['last_build_stats'] ?? [];
        return [
            'files' => (int)($h['files'] ?? 0),
            'urls'  => (int)($h['urls'] ?? 0),
            'last_built' => (int)($h['finished'] ?? 0),
            'mode'  => !empty($s['perf']['storage_mode']) && $s['perf']['storage_mode'] === 'dynamic' ? 'Dynamic' : 'Static',
        ];
    }
}

if (!function_exists('almaseo_get_health_summary')) {
    function almaseo_get_health_summary() {
        $h = (get_option('almaseo_sitemap_settings', [])['health'] ?? []);
        return [
            'validated_at' => (int)($h['last_validation']['timestamp'] ?? 0),
            'conflicts'    => (int)($h['conflicts']['totals']['issues'] ?? 0),
            'indexnow_at'  => (int)($h['indexnow_last_submit']['timestamp'] ?? 0),
            'notes'        => array_filter([
                empty(get_option('almaseo_sitemap_settings', [])['enabled']) ? 'Sitemaps are currently disabled' : '',
                empty(get_option('almaseo_sitemap_settings', [])['indexnow']['key'] ?? '') ? 'IndexNow key not configured' : '',
            ]),
        ];
    }
}

if (!function_exists('almaseo_is_building')) {
    function almaseo_is_building() {
        return !empty(get_transient('almaseo_sitemap_build_lock'));
    }
}

if (!function_exists('almaseo_get_all_sitemap_urls')) {
    function almaseo_get_all_sitemap_urls() {
        $settings = get_option('almaseo_sitemap_settings', []);
        $url_info = almaseo_get_index_url();
        $urls = [];

        $urls[] = $url_info['primary'];

        if (!empty($settings['include']['posts'])) {
            $urls[] = home_url('/post-sitemap.xml');
        }
        if (!empty($settings['include']['pages'])) {
            $urls[] = home_url('/page-sitemap.xml');
        }

        if (!empty($settings['include']['cpts']) && is_array($settings['include']['cpts'])) {
            foreach ($settings['include']['cpts'] as $cpt => $enabled) {
                if ($enabled) {
                    $urls[] = home_url('/' . $cpt . '-sitemap.xml');
                }
            }
        }

        if (!empty($settings['include']['tax']) && is_array($settings['include']['tax'])) {
            foreach ($settings['include']['tax'] as $tax => $enabled) {
                if ($enabled) {
                    $urls[] = home_url('/' . $tax . '-sitemap.xml');
                }
            }
        }

        if (!empty($settings['include']['users'])) {
            $urls[] = home_url('/author-sitemap.xml');
        }
        if (!empty($settings['delta']['enabled'])) {
            $urls[] = home_url('/sitemap-delta.xml');
        }
        if (!empty($settings['media']['images'])) {
            $urls[] = home_url('/sitemap-image.xml');
        }
        if (!empty($settings['media']['videos'])) {
            $urls[] = home_url('/sitemap-video.xml');
        }
        if (!empty($settings['news']['enabled'])) {
            $urls[] = home_url('/news-sitemap.xml');
        }

        return $urls;
    }
}