<?php
/**
 * AlmaSEO Hreflang Handler
 * 
 * Detection, mapping, and validation for hreflang alternates
 * 
 * @package AlmaSEO
 * @since 4.9.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Hreflang {
    
    /**
     * Settings
     */
    private $settings;
    
    /**
     * Detected multilingual plugin
     */
    private $ml_plugin = null;
    
    /**
     * Language cache
     */
    private $lang_cache = array();
    
    /**
     * Constructor
     */
    public function __construct($settings = null) {
        if ($settings) {
            $this->settings = $settings;
        } else {
            $s = get_option('almaseo_sitemap_settings', array());
            $this->settings = $s['hreflang'] ?? $this->get_defaults();
        }
        
        $this->detect_ml_plugin();
    }
    
    /**
     * Get default settings
     */
    private function get_defaults() {
        return array(
            'enabled' => false,
            'source' => 'auto',
            'default' => '',
            'x_default_url' => '',
            'map' => array(),
            'locales' => array()
        );
    }
    
    /**
     * Detect multilingual plugin
     */
    private function detect_ml_plugin() {
        // Check for WPML
        if (defined('ICL_SITEPRESS_VERSION')) {
            $this->ml_plugin = 'wpml';
            return;
        }
        
        // Check for Polylang
        if (function_exists('pll_current_language')) {
            $this->ml_plugin = 'polylang';
            return;
        }
        
        $this->ml_plugin = null;
    }
    
    /**
     * Get detected languages
     */
    public function get_languages() {
        if (!empty($this->lang_cache)) {
            return $this->lang_cache;
        }
        
        $languages = array();
        
        if ($this->ml_plugin === 'wpml') {
            $languages = $this->get_wpml_languages();
        } elseif ($this->ml_plugin === 'polylang') {
            $languages = $this->get_polylang_languages();
        }
        
        $this->lang_cache = $languages;
        return $languages;
    }
    
    /**
     * Get WPML languages
     */
    private function get_wpml_languages() {
        $languages = array();
        
        if (function_exists('icl_get_languages')) {
            $wpml_langs = icl_get_languages('skip_missing=0');
            
            foreach ($wpml_langs as $lang) {
                $locale = $lang['default_locale'] ?? $lang['code'];
                $hreflang = $this->locale_to_hreflang($locale);
                
                $languages[$lang['code']] = array(
                    'code' => $lang['code'],
                    'locale' => $locale,
                    'hreflang' => $hreflang,
                    'name' => $lang['native_name'] ?? $lang['code'],
                    'url' => $lang['url'] ?? '',
                    'active' => !empty($lang['active'])
                );
            }
        }
        
        return $languages;
    }
    
    /**
     * Get Polylang languages
     */
    private function get_polylang_languages() {
        $languages = array();
        
        if (function_exists('pll_languages_list')) {
            $pll_langs = pll_languages_list(array('fields' => 'locale'));
            
            foreach ($pll_langs as $locale) {
                $code = substr($locale, 0, 2); // Simple extraction
                
                if (function_exists('pll_get_language')) {
                    $lang_obj = pll_get_language($code);
                    if ($lang_obj) {
                        $hreflang = $this->locale_to_hreflang($locale);
                        
                        $languages[$code] = array(
                            'code' => $code,
                            'locale' => $locale,
                            'hreflang' => $hreflang,
                            'name' => $lang_obj->name,
                            'url' => $lang_obj->home_url ?? '',
                            'active' => ($code === pll_current_language())
                        );
                    }
                }
            }
        }
        
        return $languages;
    }
    
    /**
     * Convert locale to hreflang code
     */
    public function locale_to_hreflang($locale) {
        // Check user-defined mappings first
        if (!empty($this->settings['locales'][$locale])) {
            return $this->settings['locales'][$locale];
        }
        
        // Common mappings
        $mappings = array(
            'en_US' => 'en',
            'en_GB' => 'en-GB',
            'en_AU' => 'en-AU',
            'en_CA' => 'en-CA',
            'es_ES' => 'es',
            'es_MX' => 'es-MX',
            'pt_PT' => 'pt',
            'pt_BR' => 'pt-BR',
            'fr_FR' => 'fr',
            'fr_CA' => 'fr-CA',
            'de_DE' => 'de',
            'de_CH' => 'de-CH',
            'it_IT' => 'it',
            'nl_NL' => 'nl',
            'ru_RU' => 'ru',
            'ja' => 'ja',
            'zh_CN' => 'zh-CN',
            'zh_TW' => 'zh-TW',
            'ko_KR' => 'ko',
            'ar' => 'ar'
        );
        
        if (isset($mappings[$locale])) {
            return $mappings[$locale];
        }
        
        // Fallback: use first two letters
        return substr($locale, 0, 2);
    }
    
    /**
     * Get alternates for a post
     */
    public function get_post_alternates($post_id, $post_type = 'post') {
        if (!$this->settings['enabled']) {
            return array();
        }
        
        $alternates = array();
        
        if ($this->settings['source'] === 'manual' && !empty($this->settings['map'][$post_id])) {
            // Manual mappings
            return $this->settings['map'][$post_id];
        }
        
        // Auto detection
        if ($this->ml_plugin === 'wpml') {
            $alternates = $this->get_wpml_post_alternates($post_id, $post_type);
        } elseif ($this->ml_plugin === 'polylang') {
            $alternates = $this->get_polylang_post_alternates($post_id);
        }
        
        return $alternates;
    }
    
    /**
     * Get WPML post alternates
     */
    private function get_wpml_post_alternates($post_id, $post_type) {
        $alternates = array();
        
        if (!function_exists('wpml_object_id') || !function_exists('apply_filters')) {
            return $alternates;
        }
        
        $languages = $this->get_languages();
        
        foreach ($languages as $lang_code => $lang_data) {
            // Get translated post ID
            $translated_id = apply_filters('wpml_object_id', $post_id, $post_type, false, $lang_code);
            
            if ($translated_id) {
                $url = get_permalink($translated_id);
                if ($url) {
                    $alternates[$lang_data['hreflang']] = $url;
                }
            }
        }
        
        return $alternates;
    }
    
    /**
     * Get Polylang post alternates
     */
    private function get_polylang_post_alternates($post_id) {
        $alternates = array();
        
        if (!function_exists('pll_get_post_translations')) {
            return $alternates;
        }
        
        $translations = pll_get_post_translations($post_id);
        $languages = $this->get_languages();
        
        foreach ($translations as $lang_code => $translated_id) {
            if ($translated_id && isset($languages[$lang_code])) {
                $url = get_permalink($translated_id);
                if ($url) {
                    $alternates[$languages[$lang_code]['hreflang']] = $url;
                }
            }
        }
        
        return $alternates;
    }
    
    /**
     * Get alternates for a term
     */
    public function get_term_alternates($term_id, $taxonomy) {
        if (!$this->settings['enabled']) {
            return array();
        }
        
        $alternates = array();
        
        if ($this->settings['source'] === 'manual' && !empty($this->settings['map']['term_' . $term_id])) {
            return $this->settings['map']['term_' . $term_id];
        }
        
        if ($this->ml_plugin === 'wpml') {
            $alternates = $this->get_wpml_term_alternates($term_id, $taxonomy);
        } elseif ($this->ml_plugin === 'polylang') {
            $alternates = $this->get_polylang_term_alternates($term_id, $taxonomy);
        }
        
        return $alternates;
    }
    
    /**
     * Get WPML term alternates
     */
    private function get_wpml_term_alternates($term_id, $taxonomy) {
        $alternates = array();
        
        if (!function_exists('wpml_object_id')) {
            return $alternates;
        }
        
        $languages = $this->get_languages();
        
        foreach ($languages as $lang_code => $lang_data) {
            $translated_id = apply_filters('wpml_object_id', $term_id, $taxonomy, false, $lang_code);
            
            if ($translated_id) {
                $url = get_term_link($translated_id, $taxonomy);
                if (!is_wp_error($url)) {
                    $alternates[$lang_data['hreflang']] = $url;
                }
            }
        }
        
        return $alternates;
    }
    
    /**
     * Get Polylang term alternates
     */
    private function get_polylang_term_alternates($term_id, $taxonomy) {
        $alternates = array();
        
        if (!function_exists('pll_get_term_translations')) {
            return $alternates;
        }
        
        $translations = pll_get_term_translations($term_id);
        $languages = $this->get_languages();
        
        foreach ($translations as $lang_code => $translated_id) {
            if ($translated_id && isset($languages[$lang_code])) {
                $url = get_term_link($translated_id, $taxonomy);
                if (!is_wp_error($url)) {
                    $alternates[$languages[$lang_code]['hreflang']] = $url;
                }
            }
        }
        
        return $alternates;
    }
    
    /**
     * Build hreflang XML elements
     */
    public function build_hreflang_xml($url, $alternates) {
        if (!$this->settings['enabled'] || empty($alternates)) {
            return '';
        }
        
        $xml = '';
        
        // Always include self-referential
        $current_hreflang = $this->get_current_hreflang($url, $alternates);
        if ($current_hreflang) {
            $xml .= "\t\t" . '<xhtml:link rel="alternate" hreflang="' . esc_attr($current_hreflang) . '" href="' . esc_url($url) . '"/>' . "\n";
        }
        
        // Add all alternates
        foreach ($alternates as $hreflang => $alt_url) {
            if ($alt_url !== $url) { // Skip self if already added
                $xml .= "\t\t" . '<xhtml:link rel="alternate" hreflang="' . esc_attr($hreflang) . '" href="' . esc_url($alt_url) . '"/>' . "\n";
            }
        }
        
        // Add x-default if applicable
        if ($this->should_add_x_default($current_hreflang)) {
            $x_default_url = $this->get_x_default_url($url);
            if ($x_default_url) {
                $xml .= "\t\t" . '<xhtml:link rel="alternate" hreflang="x-default" href="' . esc_url($x_default_url) . '"/>' . "\n";
            }
        }
        
        return $xml;
    }
    
    /**
     * Get current URL's hreflang
     */
    private function get_current_hreflang($url, $alternates) {
        // Try to match URL to alternates
        foreach ($alternates as $hreflang => $alt_url) {
            if ($alt_url === $url) {
                return $hreflang;
            }
        }
        
        // Fallback to default if set
        return $this->settings['default'] ?: null;
    }
    
    /**
     * Check if x-default should be added
     */
    private function should_add_x_default($current_hreflang) {
        return !empty($this->settings['default']) && 
               $current_hreflang === $this->settings['default'];
    }
    
    /**
     * Get x-default URL
     */
    private function get_x_default_url($default_url) {
        if (!empty($this->settings['x_default_url'])) {
            return $this->settings['x_default_url'];
        }
        
        return $default_url;
    }
    
    /**
     * Validate hreflang set
     */
    public function validate_set($urls) {
        $result = array(
            'ok' => true,
            'missing_pairs' => 0,
            'orphans' => 0,
            'mismatch' => 0,
            'samples' => array()
        );
        
        if (empty($urls)) {
            return $result;
        }
        
        // Build language groups
        $groups = array();
        foreach ($urls as $url_data) {
            if (empty($url_data['alternates'])) {
                continue;
            }
            
            // Create a signature for this language group
            $langs = array_keys($url_data['alternates']);
            sort($langs);
            $signature = implode('|', $langs);
            
            if (!isset($groups[$signature])) {
                $groups[$signature] = array(
                    'languages' => $langs,
                    'urls' => array()
                );
            }
            
            $groups[$signature]['urls'][] = $url_data;
        }
        
        // Validate each group
        foreach ($groups as $signature => $group) {
            $expected_langs = $group['languages'];
            
            foreach ($group['urls'] as $url_data) {
                $url = $url_data['url'];
                $alternates = $url_data['alternates'];
                
                // Check for missing languages
                foreach ($expected_langs as $lang) {
                    if (!isset($alternates[$lang])) {
                        $result['missing_pairs']++;
                        $result['ok'] = false;
                        
                        if (count($result['samples']) < 5) {
                            $result['samples'][] = array(
                                'url' => $url,
                                'issue' => 'missing_' . $lang
                            );
                        }
                    }
                }
                
                // Check reciprocal links
                foreach ($alternates as $hreflang => $alt_url) {
                    $found_reciprocal = false;
                    
                    foreach ($group['urls'] as $other_data) {
                        if ($other_data['url'] === $alt_url) {
                            if (isset($other_data['alternates'][$hreflang]) && 
                                $other_data['alternates'][$hreflang] === $url) {
                                $found_reciprocal = true;
                                break;
                            }
                        }
                    }
                    
                    if (!$found_reciprocal) {
                        $result['orphans']++;
                        $result['ok'] = false;
                        
                        if (count($result['samples']) < 5) {
                            $result['samples'][] = array(
                                'url' => $url,
                                'issue' => 'no_reciprocal_' . $hreflang
                            );
                        }
                    }
                }
                
                // Validate hreflang codes
                foreach ($alternates as $hreflang => $alt_url) {
                    if (!$this->is_valid_hreflang($hreflang)) {
                        $result['mismatch']++;
                        $result['ok'] = false;
                        
                        if (count($result['samples']) < 5) {
                            $result['samples'][] = array(
                                'url' => $url,
                                'issue' => 'invalid_code_' . $hreflang
                            );
                        }
                    }
                }
            }
        }
        
        // Store validation results
        $this->store_validation_results($result);
        
        return $result;
    }
    
    /**
     * Check if hreflang code is valid BCP-47
     */
    private function is_valid_hreflang($code) {
        // Special case
        if ($code === 'x-default') {
            return true;
        }
        
        // Basic BCP-47 pattern: language[-script][-region]
        $pattern = '/^[a-z]{2,3}(-[A-Z][a-z]{3})?(-[A-Z]{2})?$/';
        
        return preg_match($pattern, $code) === 1;
    }
    
    /**
     * Store validation results
     */
    private function store_validation_results($result) {
        $settings = get_option('almaseo_sitemap_settings', array());
        
        if (!isset($settings['health'])) {
            $settings['health'] = array();
        }
        
        $settings['health']['hreflang'] = array(
            'validated_at' => time(),
            'ok' => $result['ok'],
            'missing_pairs' => $result['missing_pairs'],
            'orphans' => $result['orphans'],
            'mismatch' => $result['mismatch']
        );
        
        update_option('almaseo_sitemap_settings', $settings, false);
    }
    
    /**
     * Export missing pairs as CSV
     */
    public function export_missing_pairs_csv($validation_result) {
        if (empty($validation_result['samples'])) {
            return '';
        }
        
        $csv = "URL,Issue,Description\n";
        
        foreach ($validation_result['samples'] as $sample) {
            $issue = $sample['issue'];
            $description = '';
            
            if (strpos($issue, 'missing_') === 0) {
                $lang = substr($issue, 8);
                $description = "Missing alternate for language: $lang";
            } elseif (strpos($issue, 'no_reciprocal_') === 0) {
                $lang = substr($issue, 14);
                $description = "No reciprocal link from $lang version";
            } elseif (strpos($issue, 'invalid_code_') === 0) {
                $code = substr($issue, 13);
                $description = "Invalid hreflang code: $code";
            }
            
            $csv .= '"' . $sample['url'] . '","' . $issue . '","' . $description . '"' . "\n";
        }
        
        return $csv;
    }
    
    /**
     * Get stats
     */
    public function get_stats() {
        $languages = $this->get_languages();
        $settings = get_option('almaseo_sitemap_settings', array());
        $health = $settings['health']['hreflang'] ?? array();
        
        return array(
            'plugin' => $this->ml_plugin,
            'languages_count' => count($languages),
            'languages' => $languages,
            'enabled' => $this->settings['enabled'],
            'source' => $this->settings['source'],
            'default' => $this->settings['default'],
            'validation' => $health
        );
    }
}