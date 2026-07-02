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
        // Canonical URL is /sitemap.xml across the board. The takeover flag is
        // retained in settings for backward-compat with stored options but no
        // longer toggles the URL — there is only one.
        $url = home_url('/sitemap.xml');
        return [
            'primary' => $url,
            'direct'  => $url,
            'takeover' => true,
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
        $url = rtrim(home_url('/'), '/') . '/sitemap.xml';

        return [
            'primary'  => $url,
            'direct'   => $url,
            'takeover' => true,
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

if (!function_exists('almaseo_format_lastmod')) {
    /**
     * Normalize an arbitrary date-ish input to W3C datetime / ISO 8601,
     * which is the format the sitemap protocol actually requires.
     *
     * The static writer was previously emitting MySQL datetime
     * ("2026-04-09 22:29:38") verbatim — space-separated, no timezone —
     * which is non-spec. The dynamic responder had a private format_date()
     * helper that did this correctly; this lifts the same logic to a shared
     * helper so both emission paths produce identical output.
     *
     * Accepts MySQL datetime, Unix timestamp (int or numeric string), or
     * any strtotime-parseable string. Returns "" if the input is empty or
     * doesn't parse, so callers can skip emission rather than write garbage.
     */
    function almaseo_format_lastmod($value) {
        if ($value === null || $value === '' || $value === false) {
            return '';
        }
        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            $ts = (int) $value;
        } else {
            $ts = strtotime((string) $value);
        }
        if (!$ts) {
            return '';
        }
        return gmdate('c', $ts);
    }
}

if (!function_exists('almaseo_get_all_sitemap_urls')) {
    function almaseo_get_all_sitemap_urls() {
        $settings = get_option('almaseo_sitemap_settings', []);
        $urls = [ home_url('/sitemap.xml') ];

        if (!empty($settings['include']['posts'])) {
            $urls[] = home_url('/sitemap-posts-1.xml');
        }
        if (!empty($settings['include']['pages'])) {
            $urls[] = home_url('/sitemap-pages-1.xml');
        }
        // CPTs and taxonomies are each emitted as one combined sitemap
        // (provider keys 'cpts' / 'tax'), not per-type files. 'cpts' is stored
        // as the string 'all' or an empty array — the old is_array() guard
        // never passed; 'tax' holds per-taxonomy booleans.
        if (!empty($settings['include']['cpts'])) {
            $urls[] = home_url('/sitemap-cpts-1.xml');
        }
        if (!empty($settings['include']['tax'])) {
            $urls[] = home_url('/sitemap-tax-1.xml');
        }
        if (!empty($settings['include']['users'])) {
            $urls[] = home_url('/sitemap-users-1.xml');
        }
        if (!empty($settings['delta']['enabled'])) {
            $urls[] = home_url('/sitemap-delta.xml');
        }
        // Media settings nest under media.image.enabled / media.video.enabled;
        // the old media.images / media.videos keys never existed.
        if (!empty($settings['media']['image']['enabled'])) {
            $urls[] = home_url('/sitemap-image-1.xml');
        }
        if (!empty($settings['media']['video']['enabled'])) {
            $urls[] = home_url('/sitemap-video-1.xml');
        }
        if (!empty($settings['news']['enabled'])) {
            $urls[] = home_url('/sitemap-news-1.xml');
        }

        return $urls;
    }
}