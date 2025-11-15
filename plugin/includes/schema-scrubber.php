<?php
/**
 * AlmaSEO Schema Scrubber
 * 
 * Handles exclusive schema mode and JSON-LD scrubbing
 * 
 * @package AlmaSEO
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Schema Scrubber Class
 */
class AlmaSEO_Schema_Scrubber {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Buffer started flag
     */
    private $buffer_started = false;
    
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
        if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
            return;
        }
        
        // Hook into template_redirect to start output buffering
        add_action('template_redirect', array($this, 'maybe_start_buffer'), 1);
        
        // Ensure our schema has proper markers
        add_filter('almaseo_schema_output', array($this, 'add_schema_markers'), 99);
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
     * Check if current request should be scrubbed
     */
    private function should_scrub() {
        // Check if exclusive mode is enabled
        if (!$this->is_exclusive_mode_enabled()) {
            return false;
        }
        
        // Only scrub on appropriate pages
        if (!is_singular() && !is_home() && !is_front_page()) {
            return false;
        }
        
        // Skip feeds, REST, etc.
        if (is_feed() || (defined('REST_REQUEST') && REST_REQUEST)) {
            return false;
        }
        
        // Check AMP
        if ($this->is_amp_request()) {
            $schema_control = get_option('almaseo_schema_control', array());
            if (!empty($schema_control['amp_compatibility'])) {
                return false;
            }
        }
        
        return true;
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
        if (!$this->should_scrub()) {
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
        // Analyze and scrub the HTML
        $result = $this->analyze_html($html, false);
        
        // Log the action if enabled
        $this->log_schema_action($result);
        
        /**
         * Filter the scrubbed HTML
         * 
         * @param string $html The scrubbed HTML
         * @param array $result The scrubbing result data
         */
        return apply_filters('almaseo_schema_scrub_html', $result['html'], $result);
    }
    
    /**
     * Analyze HTML for schema blocks
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
        
        // Get whitelist
        $whitelist = $this->get_whitelist();
        
        // Find all JSON-LD blocks using regex
        $pattern = '/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>(.*?)<\/script>/is';
        
        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER)) {
            $result['total_found'] = count($matches);
            
            foreach ($matches as $match) {
                $full_tag = $match[0];
                $json_content = $match[1];
                
                // Check if this is our AlmaSEO block
                if ($this->is_almaseo_block($full_tag)) {
                    $result['kept_blocks'][] = array(
                        'reason' => 'AlmaSEO marker',
                        'type' => $this->extract_schema_type($json_content),
                        'snippet' => substr($json_content, 0, 200)
                    );
                    $result['kept_count']++;
                    $result['kept_types'][] = $this->extract_schema_type($json_content);
                    continue;
                }
                
                // Check if this type is whitelisted
                $schema_type = $this->extract_schema_type($json_content);
                if (in_array($schema_type, $whitelist)) {
                    $result['kept_blocks'][] = array(
                        'reason' => 'Whitelisted type: ' . $schema_type,
                        'type' => $schema_type,
                        'snippet' => substr($json_content, 0, 200)
                    );
                    $result['kept_count']++;
                    $result['kept_types'][] = $schema_type;
                    continue;
                }
                
                // This block should be removed
                $result['removed_blocks'][] = array(
                    'type' => $schema_type,
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
     * Check if a script tag is our AlmaSEO block
     */
    private function is_almaseo_block($tag) {
        return (strpos($tag, 'id="almaseo-jsonld"') !== false || 
                strpos($tag, 'data-almaseo="1"') !== false);
    }
    
    /**
     * Extract schema type from JSON content
     */
    private function extract_schema_type($json) {
        // Try to decode JSON
        $data = @json_decode($json, true);
        
        if (!$data) {
            return 'Unknown';
        }
        
        // Handle @graph
        if (isset($data['@graph']) && is_array($data['@graph'])) {
            $types = array();
            foreach ($data['@graph'] as $item) {
                if (isset($item['@type'])) {
                    $types[] = is_array($item['@type']) ? implode(',', $item['@type']) : $item['@type'];
                }
            }
            return implode(' + ', $types);
        }
        
        // Handle direct @type
        if (isset($data['@type'])) {
            return is_array($data['@type']) ? implode(',', $data['@type']) : $data['@type'];
        }
        
        return 'Unknown';
    }
    
    /**
     * Detect schema source from tag attributes
     */
    private function detect_schema_source($tag) {
        if (strpos($tag, 'yoast') !== false) {
            return 'Yoast SEO';
        }
        if (strpos($tag, 'rank-math') !== false || strpos($tag, 'rankmath') !== false) {
            return 'Rank Math';
        }
        if (strpos($tag, 'aioseo') !== false || strpos($tag, 'all-in-one-seo') !== false) {
            return 'All in One SEO';
        }
        if (strpos($tag, 'seopress') !== false) {
            return 'SEOPress';
        }
        if (strpos($tag, 'schema-pro') !== false) {
            return 'Schema Pro';
        }
        if (strpos($tag, 'wp-schema') !== false) {
            return 'WP Schema';
        }
        
        return 'Unknown/Theme';
    }
    
    /**
     * Get whitelist of schema types to keep
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
         * Filter the schema type whitelist
         * 
         * @param array $whitelist Array of schema types to keep
         */
        return apply_filters('almaseo_schema_whitelist', $whitelist);
    }
    
    /**
     * Log schema action
     */
    private function log_schema_action($result) {
        $schema_control = get_option('almaseo_schema_control', array());
        
        if (empty($schema_control['enable_logging'])) {
            return;
        }
        
        $log = get_option('almaseo_schema_log', array());
        $log_limit = isset($schema_control['log_limit']) ? intval($schema_control['log_limit']) : 50;
        
        // Add new entry
        $log[] = array(
            'time' => time(),
            'url' => home_url(add_query_arg(array())),
            'removed_count' => $result['removed_count'],
            'kept_count' => $result['kept_count'],
            'kept_types' => array_unique($result['kept_types'])
        );
        
        // Trim to limit
        if (count($log) > $log_limit) {
            $log = array_slice($log, -$log_limit);
        }
        
        update_option('almaseo_schema_log', $log);
    }
    
    /**
     * Add markers to our schema output
     */
    public function add_schema_markers($schema) {
        // Schema will be output with proper markers in schema-clean.php
        return $schema;
    }
}

// Initialize
AlmaSEO_Schema_Scrubber::get_instance();