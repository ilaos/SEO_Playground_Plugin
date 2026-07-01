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
        // AIOSEO: Do NOT hook into their internal filters (aioseo_schema_output,
        // aioseo_schema_graphs, etc.) — returning empty arrays crashes their
        // Helpers.php which expects specific data structures. The output buffer
        // approach (template_redirect → scrub_schema) safely strips their JSON-LD
        // from the final HTML without interfering with their internals.

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
        
        // AMP: skip scrubbing only when "AMP Compatibility" is enabled
        // (Schema Control → AMP Compatibility). Default is enabled.
        if ($this->is_amp_request()) {
            $schema_control = get_option('almaseo_schema_control', array());
            if (!empty($schema_control['amp_compatibility'])) {
                return false;
            }
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
        } elseif (isset($_GET['amp']) || isset($_GET['amphtml'])) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- front-end isset() check for AMP query vars; changes no state
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
        // Delegate to analyze_html so live scrubbing and the dry-run preview
        // share identical keep/remove logic (AlmaSEO marker + whitelist).
        $result = $this->analyze_html($html, false);

        // Record the action if logging is enabled.
        $this->log_schema_action($result);

        return $result['html'];
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

        // Schema types the user opted to keep (Schema Control → Whitelist).
        $whitelist = $this->get_whitelist();

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

                // Keep whitelisted standalone blocks (Schema Control → Whitelist
                // Options). A block is kept only when *every* type it declares is
                // whitelisted, so a mixed @graph (e.g. Yoast) is still removed.
                $block_types = $this->extract_schema_types($json_content);
                if ($this->is_whitelisted($block_types, $whitelist)) {
                    $type_label = implode(', ', $block_types);
                    $result['kept_blocks'][] = array(
                        'reason' => 'Whitelisted: ' . $type_label,
                        'type' => $type_label,
                        'snippet' => substr($json_content, 0, 200)
                    );
                    $result['kept_count']++;
                    $result['kept_types'][] = $type_label;
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
     * Extract every schema @type declared in a JSON-LD block as a flat array.
     *
     * Handles a direct @type, an array @type, and an @graph of nodes.
     *
     * @param string $json_content Raw JSON-LD string.
     * @return array Unique list of type strings.
     */
    private function extract_schema_types($json_content) {
        $data = @json_decode($json_content, true);
        if (!is_array($data)) {
            return array();
        }

        $types = array();

        if (isset($data['@graph']) && is_array($data['@graph'])) {
            foreach ($data['@graph'] as $node) {
                if (is_array($node) && isset($node['@type'])) {
                    foreach ((array) $node['@type'] as $t) {
                        $types[] = $t;
                    }
                }
            }
        }

        if (isset($data['@type'])) {
            foreach ((array) $data['@type'] as $t) {
                $types[] = $t;
            }
        }

        return array_values(array_unique(array_filter($types)));
    }

    /**
     * Get the list of schema types the user opted to keep.
     *
     * Driven by Schema Control → Whitelist Options.
     *
     * @return array
     */
    private function get_whitelist() {
        $whitelist = array();
        $schema_control = get_option('almaseo_schema_control', array());

        if (!empty($schema_control['keep_breadcrumbs'])) {
            $whitelist[] = 'BreadcrumbList';
        }
        if (!empty($schema_control['keep_product'])) {
            $whitelist[] = 'Product';
        }

        /**
         * Filter the schema type whitelist.
         *
         * @param array $whitelist Schema types to keep.
         */
        return apply_filters('almaseo_schema_whitelist', $whitelist);
    }

    /**
     * Decide whether a block is fully whitelisted.
     *
     * Returns true only when the block declares at least one type and every
     * declared type is on the whitelist — so a mixed @graph is still removed.
     *
     * @param array $block_types Types declared by the block.
     * @param array $whitelist   Allowed types.
     * @return bool
     */
    private function is_whitelisted($block_types, $whitelist) {
        if (empty($block_types) || empty($whitelist)) {
            return false;
        }
        foreach ($block_types as $type) {
            if (!in_array($type, $whitelist, true)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Append a scrub action to the schema log.
     *
     * No-op unless logging is enabled in Schema Control. The log is trimmed to
     * the configured entry limit.
     *
     * @param array $result Result array from analyze_html().
     */
    private function log_schema_action($result) {
        $schema_control = get_option('almaseo_schema_control', array());

        if (empty($schema_control['enable_logging'])) {
            return;
        }

        $log = get_option('almaseo_schema_log', array());
        if (!is_array($log)) {
            $log = array();
        }

        $log_limit = isset($schema_control['log_limit']) ? max(1, intval($schema_control['log_limit'])) : 50;

        $log[] = array(
            'time'          => time(),
            'url'           => home_url(add_query_arg(array())),
            'removed_count' => isset($result['removed_count']) ? (int) $result['removed_count'] : 0,
            'kept_count'    => isset($result['kept_count']) ? (int) $result['kept_count'] : 0,
            'kept_types'    => isset($result['kept_types']) ? array_values(array_unique($result['kept_types'])) : array(),
        );

        if (count($log) > $log_limit) {
            $log = array_slice($log, -$log_limit);
        }

        update_option('almaseo_schema_log', $log);
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