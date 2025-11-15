<?php
/**
 * AlmaSEO Enhanced Schema Scrubber
 * 
 * Bulletproof exclusive schema mode with proactive plugin disabling
 * 
 * @package AlmaSEO
 * @since 2.1.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Enhanced Schema Scrubber Class
 */
class AlmaSEO_Schema_Scrubber_Enhanced {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Buffer started flag
     */
    private $buffer_started = false;
    
    /**
     * Schema emitted flag
     */
    private static $schema_emitted = false;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Only run on front-end, skip admin/REST/cron/CLI
        if ($this->should_skip_request()) {
            return;
        }
        
        // Check if exclusive mode is enabled
        if (!$this->is_exclusive_mode_enabled()) {
            return;
        }
        
        // Proactively disable known schema emitters
        $this->disable_plugin_schemas();
        
        // Hook into template_redirect for output buffering
        add_action('template_redirect', array($this, 'maybe_start_buffer'), 1);
        
        // Hook into wp_head with very late priority for scrubbing
        add_action('wp_head', array($this, 'late_head_scrub'), 999999);
        
        // Ensure our schema has proper markers
        add_filter('almaseo_schema_output', array($this, 'add_schema_markers'), 99);
    }
    
    /**
     * Check if request should be skipped entirely
     */
    private function should_skip_request() {
        // Skip admin, AJAX, cron, CLI
        if (is_admin() || wp_doing_ajax() || wp_doing_cron() || (defined('WP_CLI') && WP_CLI)) {
            return true;
        }
        
        // Skip REST requests
        if (defined('REST_REQUEST') && REST_REQUEST) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Proactively disable known schema emitters
     */
    private function disable_plugin_schemas() {
        // Yoast SEO
        add_filter('wpseo_json_ld_output', '__return_false', 999);
        add_filter('wpseo_schema_graph_enabled', '__return_false', 999);
        add_filter('disable_wpseo_json_ld_search', '__return_true', 999);
        add_filter('wpseo_schema_article', '__return_false', 999);
        add_filter('wpseo_schema_webpage', '__return_false', 999);
        
        // Rank Math
        add_filter('rank_math/json_ld/enabled', '__return_false', 999);
        add_filter('rank_math/snippet/rich_snippet_enable', '__return_false', 999);
        add_action('wp_head', function() {
            if (class_exists('RankMath\\Schema\\JsonLD')) {
                remove_all_actions('rank_math/json_ld');
                remove_all_actions('rank_math/head');
            }
        }, 1);
        
        // All in One SEO - Return empty array instead of false to prevent crashes
        add_filter('aioseo_schema_disable', '__return_true', 999);
        add_filter('aioseo_schema_output', function() { return array(); }, 999);
        add_filter('aioseo_schema_graphs', function() { return array(); }, 999);
        
        // Remove AIOSEO schema action safely
        if (function_exists('aioseo')) {
            add_action('wp_head', function() {
                if (isset(aioseo()->schema) && is_object(aioseo()->schema)) {
                    remove_action('wp_head', array(aioseo()->schema, 'output'), 30);
                }
            }, 1);
        }
        
        // SEOPress
        add_filter('seopress_schemas_jsonld_output', '__return_false', 999);
        add_filter('seopress_pro_schemas_jsonld_output', '__return_false', 999);
        
        // Schema Pro
        add_filter('wp_schema_pro_schema_enabled', '__return_false', 999);
        add_filter('bsf_markup_enabled', '__return_false', 999);
        
        // WooCommerce
        add_filter('woocommerce_structured_data_product', '__return_false', 999);
        add_filter('woocommerce_structured_data_type', '__return_false', 999);
        add_filter('woocommerce_structured_data_review', '__return_false', 999);
        
        // Elementor
        add_filter('elementor/frontend/schema/enabled', '__return_false', 999);
        
        // Divi
        add_filter('et_builder_schema_enabled', '__return_false', 999);
        
        // AMP plugin
        add_filter('amp_post_template_metadata', '__return_false', 999);
        add_filter('amp_schemaorg_metadata', '__return_false', 999);
        
        // Generic theme/plugin hooks
        remove_all_actions('wp_head', 'schema_output', 10);
        remove_all_actions('wp_head', 'output_schema', 10);
        remove_all_actions('wp_head', 'print_schema', 10);
        remove_all_actions('wp_head', 'jsonld_output', 10);
        
        // Remove common theme schema functions
        add_action('after_setup_theme', function() {
            remove_action('wp_head', 'theme_schema_output', 10);
            remove_action('wp_head', 'custom_schema_markup', 10);
        }, 999);
    }
    
    /**
     * Check if exclusive schema mode is enabled
     */
    private function is_exclusive_mode_enabled() {
        /**
         * Filter whether exclusive schema mode is enabled
         * 
         * @param bool $enabled Whether exclusive schema mode is enabled
         */
        return apply_filters('almaseo_exclusive_schema_enabled', get_option('almaseo_exclusive_schema_enabled', false));
    }
    
    /**
     * Check if current request should emit/scrub schema
     */
    private function should_emit_schema() {
        // Must be main query
        if (!is_main_query()) {
            return false;
        }
        
        // Skip feeds, embeds, REST
        if (is_feed() || is_embed() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return false;
        }
        
        // Skip AMP
        if ($this->is_amp_request()) {
            return false;
        }
        
        // Check context
        $context = array(
            'is_singular' => is_singular(),
            'is_home' => is_home(),
            'is_front_page' => is_front_page(),
            'is_archive' => is_archive(),
            'post_type' => get_post_type()
        );
        
        /**
         * Filter whether schema should be emitted
         * 
         * @param bool $should_emit Whether to emit schema
         * @param array $context Current page context
         */
        $should_emit = apply_filters('almaseo_schema_should_emit', true, $context);
        
        // Default: emit on singular and front page
        if (!$should_emit) {
            return false;
        }
        
        // Homepage logic
        if (is_front_page()) {
            // Static front page - emit if it's an actual page
            if (is_page()) {
                return true;
            }
            // Posts index - skip for now
            return false;
        }
        
        return is_singular();
    }
    
    /**
     * Check if this is an AMP request
     */
    private function is_amp_request() {
        /**
         * Filter whether current request is an AMP page
         * 
         * @param bool $is_amp Whether this is an AMP request
         */
        $is_amp = false;
        
        // Check common AMP indicators
        if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
            $is_amp = true;
        } elseif (function_exists('ampforwp_is_amp_endpoint') && ampforwp_is_amp_endpoint()) {
            $is_amp = true;
        } elseif (function_exists('amp_is_request') && amp_is_request()) {
            $is_amp = true;
        } elseif (isset($_GET['amp']) || isset($_GET['amphtml'])) {
            $is_amp = true;
        } elseif (preg_match('/\/amp\/?$/', $_SERVER['REQUEST_URI'])) {
            $is_amp = true;
        }
        
        return apply_filters('almaseo_is_amp_request', $is_amp);
    }
    
    /**
     * Maybe start output buffer
     */
    public function maybe_start_buffer() {
        if (!$this->should_emit_schema()) {
            return;
        }
        
        if (!$this->buffer_started) {
            ob_start(array($this, 'scrub_schema'));
            $this->buffer_started = true;
        }
    }
    
    /**
     * Late head scrubbing for any stragglers
     */
    public function late_head_scrub() {
        if (!$this->should_emit_schema()) {
            return;
        }
        
        // Use output buffer to catch anything added very late
        ob_start();
        add_action('wp_head', function() {
            $late_content = ob_get_clean();
            echo $this->scrub_schema_from_string($late_content);
        }, 1000000);
    }
    
    /**
     * Scrub schema from HTML
     */
    public function scrub_schema($html) {
        // Scrub the HTML
        $scrubbed = $this->scrub_schema_from_string($html);
        
        // Log if debug mode
        if (defined('WP_DEBUG') && WP_DEBUG) {
            $original_count = substr_count($html, 'type="application/ld+json"');
            $final_count = substr_count($scrubbed, 'type="application/ld+json"');
            $removed = $original_count - $final_count;
            
            if ($removed > 0) {
                error_log(sprintf(
                    '[AlmaSEO] Exclusive Schema scrubbed %d blocks on URL %s',
                    $removed,
                    home_url(add_query_arg(array()))
                ));
            }
        }
        
        /**
         * Filter the scrubbed HTML
         * 
         * @param string $html The scrubbed HTML
         */
        return apply_filters('almaseo_schema_scrub_html', $scrubbed);
    }
    
    /**
     * Scrub schema from a string
     */
    private function scrub_schema_from_string($content) {
        // Conservative regex to match JSON-LD scripts
        // Handles minified, multiline, and whitespace variants
        $pattern = '/<script[^>]*type\s*=\s*["\']application\/ld\+json["\'][^>]*>.*?<\/script>/is';
        
        // Find all JSON-LD blocks
        if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $full_tag = $match[0];
                
                // Keep our AlmaSEO block
                if (strpos($full_tag, 'id="almaseo-jsonld"') !== false || 
                    strpos($full_tag, 'data-almaseo="1"') !== false) {
                    continue;
                }
                
                // Remove all others
                $content = str_replace($full_tag, '', $content);
            }
        }
        
        return $content;
    }
    
    /**
     * Add markers to our schema output
     */
    public function add_schema_markers($schema) {
        // Markers are added in schema-clean.php
        return $schema;
    }
    
    /**
     * Check if schema has been emitted
     */
    public static function has_schema_emitted() {
        return self::$schema_emitted;
    }
    
    /**
     * Mark schema as emitted
     */
    public static function mark_schema_emitted() {
        self::$schema_emitted = true;
    }
}

// Initialize
AlmaSEO_Schema_Scrubber_Enhanced::get_instance();