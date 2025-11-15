<?php
/**
 * AlmaSEO Settings Import/Export
 * 
 * JSON export/import of sitemap settings
 * 
 * @package AlmaSEO
 * @since 4.12.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Settings_Porter {
    
    /**
     * Export settings as JSON
     * 
     * @return array Export data
     */
    public static function export_settings() {
        $settings = get_option('almaseo_sitemap_settings', array());
        
        // Remove sensitive or environment-specific data
        unset($settings['health']);
        
        $export = array(
            'version' => ALMASEO_PLUGIN_VERSION,
            'exported' => time(),
            'site_url' => home_url(),
            'settings' => $settings
        );
        
        /**
         * Filter export data
         * 
         * @since 4.12.0
         * @param array $export Export data
         */
        return apply_filters('almaseo_export_settings', $export);
    }
    
    /**
     * Import settings from JSON
     * 
     * @param string $json JSON data
     * @return bool|WP_Error
     */
    public static function import_settings($json) {
        // Decode JSON
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', __('Invalid JSON format', 'almaseo'));
        }
        
        // Validate structure
        if (!isset($data['settings']) || !is_array($data['settings'])) {
            return new WP_Error('invalid_format', __('Invalid export format', 'almaseo'));
        }
        
        // Check version compatibility
        if (isset($data['version'])) {
            $current_version = ALMASEO_PLUGIN_VERSION;
            $import_version = $data['version'];
            
            // Major version must match
            if (version_compare($import_version, $current_version, '<')) {
                // Allow import from older versions with warning
                Alma_Health_Log::log('import', sprintf(
                    __('Imported settings from older version %s', 'almaseo'),
                    $import_version
                ));
            }
        }
        
        // Get current settings
        $current = get_option('almaseo_sitemap_settings', array());
        
        // Preserve health data
        $health = isset($current['health']) ? $current['health'] : array();
        
        // Validate and sanitize imported settings
        $imported = self::validate_settings($data['settings']);
        
        // Merge with defaults
        $defaults = self::get_default_settings();
        $merged = wp_parse_args($imported, $defaults);
        
        // Restore health data
        $merged['health'] = $health;
        
        /**
         * Filter imported settings before saving
         * 
         * @since 4.12.0
         * @param array $merged Merged settings
         * @param array $imported Imported settings
         * @param array $current Current settings
         */
        $merged = apply_filters('almaseo_import_settings', $merged, $imported, $current);
        
        // Save settings
        update_option('almaseo_sitemap_settings', $merged);
        
        // Log import
        Alma_Health_Log::log('import', __('Settings imported successfully', 'almaseo'), array(
            'source_url' => isset($data['site_url']) ? $data['site_url'] : 'unknown',
            'exported' => isset($data['exported']) ? date('Y-m-d H:i:s', $data['exported']) : 'unknown'
        ));
        
        // Clear any caches
        self::clear_caches();
        
        return true;
    }
    
    /**
     * Validate settings structure
     * 
     * @param array $settings Settings to validate
     * @return array Validated settings
     */
    private static function validate_settings($settings) {
        $validated = array();
        
        // Validate booleans
        $bool_fields = array('enabled', 'takeover');
        foreach ($bool_fields as $field) {
            if (isset($settings[$field])) {
                $validated[$field] = (bool) $settings[$field];
            }
        }
        
        // Validate include settings
        if (isset($settings['include']) && is_array($settings['include'])) {
            $validated['include'] = array();
            
            // Post types
            if (isset($settings['include']['posts'])) {
                $validated['include']['posts'] = (bool) $settings['include']['posts'];
            }
            if (isset($settings['include']['pages'])) {
                $validated['include']['pages'] = (bool) $settings['include']['pages'];
            }
            if (isset($settings['include']['cpts'])) {
                $validated['include']['cpts'] = $settings['include']['cpts'];
            }
            
            // Taxonomies
            if (isset($settings['include']['tax']) && is_array($settings['include']['tax'])) {
                $validated['include']['tax'] = array_map('boolval', $settings['include']['tax']);
            }
            
            // Users
            if (isset($settings['include']['users'])) {
                $validated['include']['users'] = (bool) $settings['include']['users'];
            }
        }
        
        // Validate numeric fields
        if (isset($settings['links_per_sitemap'])) {
            $validated['links_per_sitemap'] = min(50000, max(1, intval($settings['links_per_sitemap'])));
        }
        
        // Validate performance settings
        if (isset($settings['perf']) && is_array($settings['perf'])) {
            $validated['perf'] = array();
            
            if (isset($settings['perf']['storage_mode'])) {
                $valid_modes = array('static', 'dynamic');
                $mode = $settings['perf']['storage_mode'];
                $validated['perf']['storage_mode'] = in_array($mode, $valid_modes) ? $mode : 'dynamic';
            }
            
            if (isset($settings['perf']['gzip'])) {
                $validated['perf']['gzip'] = (bool) $settings['perf']['gzip'];
            }
        }
        
        // Validate IndexNow settings
        if (isset($settings['indexnow']) && is_array($settings['indexnow'])) {
            $validated['indexnow'] = array();
            
            if (isset($settings['indexnow']['enabled'])) {
                $validated['indexnow']['enabled'] = (bool) $settings['indexnow']['enabled'];
            }
            
            if (isset($settings['indexnow']['key'])) {
                $validated['indexnow']['key'] = sanitize_text_field($settings['indexnow']['key']);
            }
            
            if (isset($settings['indexnow']['engines']) && is_array($settings['indexnow']['engines'])) {
                $valid_engines = array('bing', 'yandex', 'seznam', 'indexnow');
                $validated['indexnow']['engines'] = array_intersect($settings['indexnow']['engines'], $valid_engines);
            }
        }
        
        // Validate delta settings
        if (isset($settings['delta']) && is_array($settings['delta'])) {
            $validated['delta'] = array();
            
            if (isset($settings['delta']['enabled'])) {
                $validated['delta']['enabled'] = (bool) $settings['delta']['enabled'];
            }
            
            if (isset($settings['delta']['max_urls'])) {
                $validated['delta']['max_urls'] = min(1000, max(10, intval($settings['delta']['max_urls'])));
            }
            
            if (isset($settings['delta']['retention_days'])) {
                $validated['delta']['retention_days'] = min(90, max(1, intval($settings['delta']['retention_days'])));
            }
        }
        
        // Validate hreflang settings
        if (isset($settings['hreflang']) && is_array($settings['hreflang'])) {
            $validated['hreflang'] = array();
            
            if (isset($settings['hreflang']['enabled'])) {
                $validated['hreflang']['enabled'] = (bool) $settings['hreflang']['enabled'];
            }
            
            if (isset($settings['hreflang']['source'])) {
                $valid_sources = array('auto', 'manual');
                $source = $settings['hreflang']['source'];
                $validated['hreflang']['source'] = in_array($source, $valid_sources) ? $source : 'auto';
            }
        }
        
        // Validate media settings
        if (isset($settings['media']) && is_array($settings['media'])) {
            $validated['media'] = array();
            
            foreach (array('image', 'video') as $type) {
                if (isset($settings['media'][$type]) && is_array($settings['media'][$type])) {
                    $validated['media'][$type] = array();
                    
                    if (isset($settings['media'][$type]['enabled'])) {
                        $validated['media'][$type]['enabled'] = (bool) $settings['media'][$type]['enabled'];
                    }
                    
                    if (isset($settings['media'][$type]['max_per_url'])) {
                        $max = intval($settings['media'][$type]['max_per_url']);
                        $validated['media'][$type]['max_per_url'] = min(100, max(1, $max));
                    }
                }
            }
        }
        
        // Validate news settings
        if (isset($settings['news']) && is_array($settings['news'])) {
            $validated['news'] = array();
            
            if (isset($settings['news']['enabled'])) {
                $validated['news']['enabled'] = (bool) $settings['news']['enabled'];
            }
            
            if (isset($settings['news']['window_hours'])) {
                $hours = intval($settings['news']['window_hours']);
                $validated['news']['window_hours'] = min(168, max(1, $hours));
            }
            
            if (isset($settings['news']['max_items'])) {
                $max = intval($settings['news']['max_items']);
                $validated['news']['max_items'] = min(5000, max(1, $max));
            }
            
            if (isset($settings['news']['publisher_name'])) {
                $validated['news']['publisher_name'] = sanitize_text_field($settings['news']['publisher_name']);
            }
        }
        
        return $validated;
    }
    
    /**
     * Get default settings
     * 
     * @return array Default settings
     */
    private static function get_default_settings() {
        return array(
            'enabled' => true,
            'takeover' => false,
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
                'storage_mode' => 'dynamic',
                'gzip' => true
            ),
            'indexnow' => array(
                'enabled' => false,
                'key' => '',
                'engines' => array('bing', 'yandex')
            ),
            'delta' => array(
                'enabled' => false,
                'max_urls' => 500,
                'retention_days' => 14
            ),
            'hreflang' => array(
                'enabled' => false,
                'source' => 'auto'
            ),
            'media' => array(
                'image' => array(
                    'enabled' => false,
                    'max_per_url' => 20
                ),
                'video' => array(
                    'enabled' => false,
                    'max_per_url' => 10
                )
            ),
            'news' => array(
                'enabled' => false,
                'window_hours' => 48,
                'max_items' => 1000
            )
        );
    }
    
    /**
     * Clear caches after import
     */
    private static function clear_caches() {
        // Clear any transients
        delete_transient('almaseo_sitemap_cache');
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Clear object cache if available
        if (function_exists('wp_cache_flush')) {
            wp_cache_flush();
        }
        
        /**
         * Action after clearing caches
         * 
         * @since 4.12.0
         */
        do_action('almaseo_settings_imported');
    }
}