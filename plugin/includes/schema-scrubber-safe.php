<?php
/**
 * AlmaSEO Safe Schema Scrubber
 * 
 * Safer implementation that works with AIOSEO without breaking it
 * 
 * @package AlmaSEO
 * @since 2.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Safe Schema Scrubber Class
 */
class AlmaSEO_Schema_Scrubber_Safe {
    
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
        // Only run on front-end
        if ($this->should_skip_request()) {
            return;
        }
        
        // Check if exclusive mode is enabled
        if (!$this->is_exclusive_mode_enabled()) {
            return;
        }
        
        // Use safer approach for AIOSEO compatibility
        $this->setup_safe_filters();
        
        // Hook into template_redirect for output buffering
        add_action('template_redirect', array($this, 'maybe_start_buffer'), 1);
    }
    
    /**
     * Check if request should be skipped
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
     * Setup safe filters that won't break other plugins
     */
    private function setup_safe_filters() {
        // For AIOSEO, we need to be more careful
        if (defined('AIOSEO_VERSION')) {
            // Hook into their schema generation to return safe empty structures
            add_filter('aioseo_schema_output', array($this, 'safe_empty_schema'), 999);
            add_filter('aioseo_schema_graphs', array($this, 'safe_empty_graphs'), 999);
            
            // Try to disable their schema module if possible
            add_filter('aioseo_schema_disable', '__return_true', 999);
            
            // Hook early to prevent their schema class from initializing
            add_action('init', function() {
                if (function_exists('aioseo') && isset(aioseo()->schema)) {
                    // Remove their wp_head action
                    remove_action('wp_head', array(aioseo()->schema, 'output'), 30);
                }
            }, 1);
        }
        
        // Yoast SEO
        add_filter('wpseo_json_ld_output', '__return_false', 999);
        add_filter('wpseo_schema_graph_enabled', '__return_false', 999);
        add_filter('disable_wpseo_json_ld_search', '__return_true', 999);
        
        // Rank Math
        add_filter('rank_math/json_ld/enabled', '__return_false', 999);
        add_filter('rank_math/snippet/rich_snippet_enable', '__return_false', 999);
        
        // SEOPress
        add_filter('seopress_schemas_jsonld_output', '__return_false', 999);
        add_filter('seopress_pro_schemas_jsonld_output', '__return_false', 999);
        
        // Schema Pro
        add_filter('wp_schema_pro_schema_enabled', '__return_false', 999);
        add_filter('bsf_markup_enabled', '__return_false', 999);
        
        // WooCommerce
        add_filter('woocommerce_structured_data_product', '__return_empty_array', 999);
        add_filter('woocommerce_structured_data_type', '__return_empty_string', 999);
        add_filter('woocommerce_structured_data_review', '__return_empty_array', 999);
    }
    
    /**
     * Return safe empty schema for AIOSEO
     */
    public function safe_empty_schema($schema) {
        // Return empty array instead of false to prevent type errors
        if (!is_array($schema)) {
            return array();
        }
        return array();
    }
    
    /**
     * Return safe empty graphs for AIOSEO
     */
    public function safe_empty_graphs($graphs) {
        // Return empty array instead of false to prevent type errors
        if (!is_array($graphs)) {
            return array();
        }
        return array();
    }
    
    /**
     * Check if exclusive schema mode is enabled
     */
    private function is_exclusive_mode_enabled() {
        return apply_filters('almaseo_exclusive_schema_enabled', get_option('almaseo_exclusive_schema_enabled', false));
    }
    
    /**
     * Check if current request should emit schema
     */
    private function should_emit_schema() {
        // Must be main query
        if (!is_main_query()) {
            return false;
        }
        
        // Skip feeds, embeds
        if (is_feed() || is_embed()) {
            return false;
        }
        
        // Skip AMP
        if ($this->is_amp_request()) {
            return false;
        }
        
        // Default: emit on singular and front page
        return is_singular() || is_front_page();
    }
    
    /**
     * Check if this is an AMP request
     */
    private function is_amp_request() {
        $is_amp = false;
        
        // Check common AMP indicators
        if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
            $is_amp = true;
        } elseif (function_exists('ampforwp_is_amp_endpoint') && ampforwp_is_amp_endpoint()) {
            $is_amp = true;
        } elseif (isset($_GET['amp']) || isset($_GET['amphtml'])) {
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
     * Scrub schema from HTML
     */
    public function scrub_schema($html) {
        // Pattern to match JSON-LD scripts
        $pattern = '/<script[^>]*type\s*=\s*["\']application\/ld\+json["\'][^>]*>.*?<\/script>/is';
        
        // Find all JSON-LD blocks
        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $full_tag = $match[0];
                
                // Keep our AlmaSEO block
                if (strpos($full_tag, 'id="almaseo-jsonld"') !== false || 
                    strpos($full_tag, 'data-almaseo="1"') !== false) {
                    continue;
                }
                
                // Remove all others
                $html = str_replace($full_tag, '', $html);
            }
        }
        
        return $html;
    }
    
    /**
     * Analyze HTML for schema markup (for dry-run preview)
     * 
     * @param string $html The HTML to analyze
     * @param bool $dry_run Whether to run in dry-run mode (don't modify)
     * @return array Analysis results
     */
    public function analyze_html($html, $dry_run = false) {
        $result = array(
            'html' => $html,
            'total_found' => 0,
            'removed_count' => 0,
            'kept_count' => 0,
            'kept_types' => array(),
            'removed_blocks' => array(),
            'kept_blocks' => array()
        );
        
        // Pattern to match JSON-LD scripts
        $pattern = '/<script[^>]*type\s*=\s*["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is';
        
        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            $result['total_found'] = count($matches);
            
            foreach ($matches as $match) {
                $full_tag = $match[0];
                $json_content = $match[1];
                
                // Check if this is our AlmaSEO block
                if (strpos($full_tag, 'id="almaseo-jsonld"') !== false || 
                    strpos($full_tag, 'data-almaseo="1"') !== false) {
                    $result['kept_blocks'][] = array(
                        'reason' => 'AlmaSEO marker',
                        'type' => $this->extract_schema_type($json_content),
                        'snippet' => substr($json_content, 0, 200)
                    );
                    $result['kept_count']++;
                    $result['kept_types'][] = $this->extract_schema_type($json_content);
                    continue;
                }
                
                // This block should be removed
                $result['removed_blocks'][] = array(
                    'type' => $this->extract_schema_type($json_content),
                    'snippet' => substr($json_content, 0, 200),
                    'source' => $this->detect_schema_source($full_tag)
                );
                $result['removed_count']++;
                
                // Remove the block if not in dry-run mode
                if (!$dry_run) {
                    $html = str_replace($full_tag, '', $html);
                }
            }
        }
        
        if (!$dry_run) {
            $result['html'] = $html;
        }
        
        return $result;
    }
    
    /**
     * Extract schema type from JSON content
     */
    private function extract_schema_type($json_content) {
        $data = @json_decode($json_content, true);
        if (!$data) {
            return 'Invalid JSON';
        }
        
        if (isset($data['@type'])) {
            return is_array($data['@type']) ? implode(', ', $data['@type']) : $data['@type'];
        }
        
        if (isset($data['@graph']) && is_array($data['@graph'])) {
            $types = array();
            foreach ($data['@graph'] as $item) {
                if (isset($item['@type'])) {
                    $types[] = is_array($item['@type']) ? implode(', ', $item['@type']) : $item['@type'];
                }
            }
            return implode(', ', array_unique($types));
        }
        
        return 'Unknown';
    }
    
    /**
     * Detect the source of schema markup
     */
    private function detect_schema_source($tag) {
        if (strpos($tag, 'yoast') !== false || strpos($tag, 'wpseo') !== false) {
            return 'Yoast SEO';
        }
        if (strpos($tag, 'aioseo') !== false) {
            return 'All in One SEO';
        }
        if (strpos($tag, 'rankmath') !== false) {
            return 'Rank Math';
        }
        if (strpos($tag, 'seopress') !== false) {
            return 'SEOPress';
        }
        if (strpos($tag, 'schema-pro') !== false) {
            return 'Schema Pro';
        }
        return 'Unknown/Theme';
    }
}

// Helper functions for filters
if (!function_exists('__return_empty_array')) {
    function __return_empty_array() {
        return array();
    }
}

if (!function_exists('__return_empty_string')) {
    function __return_empty_string() {
        return '';
    }
}

// Initialize
AlmaSEO_Schema_Scrubber_Safe::get_instance();