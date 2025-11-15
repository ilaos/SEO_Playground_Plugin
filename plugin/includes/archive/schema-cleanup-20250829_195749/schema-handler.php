<?php
/**
 * AlmaSEO Schema Handler - Exclusive Schema Mode & BlogPosting Output
 * 
 * @package AlmaSEO
 * @version 2.8.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AlmaSEO_Schema_Handler {
    
    private static $instance = null;
    private static $schema_rendered = false;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Initialize schema output - always runs regardless of connection
        add_action('wp_head', array($this, 'output_schema_jsonld'), 5);
        
        // Handle exclusive mode if enabled - always available
        if (get_option('almaseo_exclusive_schema_mode', false)) {
            $this->enable_exclusive_mode();
        }
    }
    
    /**
     * Output AlmaSEO JSON-LD structured data
     */
    public function output_schema_jsonld() {
        // Prevent duplicate output
        if (self::$schema_rendered) {
            return;
        }
        
        // Check if we should output schema
        if (!$this->should_output_schema()) {
            return;
        }
        
        // Get the schema data
        $schema_data = $this->get_schema_data();
        if (empty($schema_data)) {
            return;
        }
        
        // Mark as rendered
        self::$schema_rendered = true;
        
        // Output the JSON-LD
        $json_output = wp_json_encode($schema_data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        
        echo "\n" . '<script type="application/ld+json" id="almaseo-jsonld" data-almaseo="1">' . "\n";
        echo $json_output;
        echo "\n" . '</script>' . "\n";
    }
    
    /**
     * Check if schema should be output on current page
     */
    private function should_output_schema() {
        // Skip on admin, feeds, and AMP
        if (is_admin() || is_feed()) {
            return false;
        }
        
        // Skip on AMP endpoints if function exists
        if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
            return false;
        }
        
        // Output on singular posts, pages, and front page
        return is_singular('post') || is_page() || is_front_page();
    }
    
    /**
     * Get schema data for current page
     */
    private function get_schema_data() {
        global $post;
        
        // Get post ID
        $post_id = 0;
        if (is_singular() && $post) {
            $post_id = $post->ID;
        } elseif (is_front_page()) {
            $post_id = get_option('page_on_front');
            if (!$post_id) {
                $post_id = 0;
            }
        }
        
        // Get post data
        if ($post_id) {
            $post_obj = get_post($post_id);
        } else {
            $post_obj = null;
        }
        
        // Build schema data
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'BlogPosting'
        );
        
        // Get canonical URL
        $canonical = $this->get_canonical_url($post_id);
        $schema['mainEntityOfPage'] = array(
            '@type' => 'WebPage',
            '@id' => $canonical . '#almaseo-article'
        );
        $schema['url'] = $canonical;
        
        // Get title
        $title = $this->get_schema_title($post_id, $post_obj);
        if ($title) {
            $schema['headline'] = $title;
        }
        
        // Get description
        $description = $this->get_schema_description($post_id, $post_obj);
        if ($description) {
            $schema['description'] = $description;
        }
        
        // Get author
        $author = $this->get_schema_author($post_id, $post_obj);
        if ($author) {
            $schema['author'] = $author;
        }
        
        // Get dates
        if ($post_obj) {
            $schema['datePublished'] = get_the_date('c', $post_obj);
            $schema['dateModified'] = get_the_modified_date('c', $post_obj);
        } else {
            // For homepage without specific post
            $schema['datePublished'] = date('c');
            $schema['dateModified'] = date('c');
        }
        
        // Get image
        $image = $this->get_schema_image($post_id);
        if ($image) {
            $schema['image'] = $image;
        }
        
        // Get publisher
        $publisher = $this->get_schema_publisher();
        if ($publisher) {
            $schema['publisher'] = $publisher;
        }
        
        // Add article section if category exists
        if ($post_obj && is_singular('post')) {
            $categories = get_the_category($post_id);
            if (!empty($categories)) {
                $schema['articleSection'] = $categories[0]->name;
            }
        }
        
        // Add keywords if tags exist
        if ($post_obj && is_singular('post')) {
            $tags = get_the_tags($post_id);
            if (!empty($tags)) {
                $keywords = array();
                foreach ($tags as $tag) {
                    $keywords[] = $tag->name;
                }
                $schema['keywords'] = implode(', ', $keywords);
            }
        }
        
        return $schema;
    }
    
    /**
     * Get canonical URL for schema
     */
    private function get_canonical_url($post_id) {
        // Check for custom canonical
        if ($post_id) {
            $custom_canonical = get_post_meta($post_id, '_almaseo_canonical_url', true);
            if (!empty($custom_canonical)) {
                return esc_url($custom_canonical);
            }
        }
        
        // Get standard canonical
        if (is_front_page()) {
            return home_url('/');
        } elseif ($post_id) {
            return get_permalink($post_id);
        }
        
        return home_url('/');
    }
    
    /**
     * Get schema title
     */
    private function get_schema_title($post_id, $post_obj) {
        // Try custom SEO title first
        if ($post_id) {
            $seo_title = get_post_meta($post_id, '_almaseo_title', true);
            if (!empty($seo_title)) {
                return $this->clean_text($seo_title);
            }
        }
        
        // Use post title
        if ($post_obj) {
            return $this->clean_text($post_obj->post_title);
        }
        
        // Fallback to site title
        return $this->clean_text(get_bloginfo('name'));
    }
    
    /**
     * Get schema description (clean, no HTML entities)
     */
    private function get_schema_description($post_id, $post_obj) {
        $description = '';
        
        // Try custom meta description first
        if ($post_id) {
            $description = get_post_meta($post_id, '_almaseo_description', true);
        }
        
        // Try excerpt
        if (empty($description) && $post_obj) {
            $description = get_the_excerpt($post_obj);
        }
        
        // Try content
        if (empty($description) && $post_obj) {
            $content = wp_strip_all_tags($post_obj->post_content);
            if (strlen($content) > 160) {
                $content = substr($content, 0, 157) . '...';
            }
            $description = $content;
        }
        
        // Fallback to site description
        if (empty($description)) {
            $description = get_bloginfo('description');
        }
        
        // Clean the description - remove all HTML entities
        $description = wp_strip_all_tags($description);
        $description = html_entity_decode(
            wp_specialchars_decode($description, ENT_QUOTES),
            ENT_QUOTES,
            get_bloginfo('charset')
        );
        
        // Trim and normalize whitespace
        $description = preg_replace('/\s+/', ' ', $description);
        $description = trim($description);
        
        // Ensure reasonable length
        if (strlen($description) > 300) {
            $description = substr($description, 0, 297) . '...';
        }
        
        return $description;
    }
    
    /**
     * Get schema author with URL
     */
    private function get_schema_author($post_id, $post_obj) {
        $author = array(
            '@type' => 'Person'
        );
        
        // Get author ID
        $author_id = 0;
        if ($post_obj) {
            $author_id = (int) $post_obj->post_author;
        }
        
        // Get author name
        if ($author_id) {
            $author_data = get_userdata($author_id);
            if ($author_data) {
                $author['name'] = $author_data->display_name;
                // Get author URL
                $author_url = get_author_posts_url($author_id);
                if ($author_url) {
                    $author['url'] = $author_url;
                } else {
                    $author['url'] = home_url('/');
                }
            }
        }
        
        // Fallback if no author found
        if (empty($author['name'])) {
            $author['name'] = get_bloginfo('name');
            $author['url'] = home_url('/');
        }
        
        return $author;
    }
    
    /**
     * Get schema image (single string URL)
     */
    private function get_schema_image($post_id) {
        $image_url = '';
        
        // 1. Try custom OG image
        if ($post_id) {
            $og_image = get_post_meta($post_id, '_almaseo_og_image', true);
            if (!empty($og_image)) {
                return esc_url($og_image);
            }
        }
        
        // 2. Try featured image
        if ($post_id && has_post_thumbnail($post_id)) {
            $thumbnail_id = get_post_thumbnail_id($post_id);
            $image_data = wp_get_attachment_image_src($thumbnail_id, 'full');
            if (!empty($image_data[0])) {
                return esc_url($image_data[0]);
            }
        }
        
        // 3. Try site icon
        $site_icon_id = get_option('site_icon');
        if ($site_icon_id) {
            $icon_url = wp_get_attachment_image_url($site_icon_id, 'full');
            if ($icon_url) {
                return esc_url($icon_url);
            }
        }
        
        // 4. Try custom logo
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
            if ($logo_url) {
                return esc_url($logo_url);
            }
        }
        
        // 5. Use plugin default image
        if (defined('ALMASEO_PLUGIN_URL')) {
            return ALMASEO_PLUGIN_URL . 'assets/img/default-schema.jpg';
        } else {
            return plugin_dir_url(dirname(__FILE__)) . 'assets/img/default-schema.jpg';
        }
    }
    
    /**
     * Get schema publisher
     */
    private function get_schema_publisher() {
        $publisher = array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name')
        );
        
        // Add logo if available
        $logo_url = '';
        
        // Try custom logo first
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
        }
        
        // Try site icon
        if (empty($logo_url)) {
            $site_icon_id = get_option('site_icon');
            if ($site_icon_id) {
                $logo_url = wp_get_attachment_image_url($site_icon_id, 'full');
            }
        }
        
        // Use default if nothing found
        if (empty($logo_url)) {
            if (defined('ALMASEO_PLUGIN_URL')) {
                $logo_url = ALMASEO_PLUGIN_URL . 'assets/img/default-schema.jpg';
            } else {
                $logo_url = plugin_dir_url(dirname(__FILE__)) . 'assets/img/default-schema.jpg';
            }
        }
        
        if ($logo_url) {
            $publisher['logo'] = array(
                '@type' => 'ImageObject',
                'url' => esc_url($logo_url)
            );
        }
        
        return $publisher;
    }
    
    /**
     * Clean text for schema output
     */
    private function clean_text($text) {
        // Strip tags
        $text = wp_strip_all_tags($text);
        
        // Decode HTML entities
        $text = html_entity_decode(
            wp_specialchars_decode($text, ENT_QUOTES),
            ENT_QUOTES,
            get_bloginfo('charset')
        );
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        
        return trim($text);
    }
    
    /**
     * Enable Exclusive Schema Mode
     */
    private function enable_exclusive_mode() {
        // Skip on AMP
        if (function_exists('is_amp_endpoint') && is_amp_endpoint()) {
            return;
        }
        
        // Disable other plugin schemas with high priority
        add_action('init', array($this, 'disable_plugin_schemas'), 1);
        
        // Start output buffering for fallback scrubbing
        add_action('template_redirect', array($this, 'start_schema_buffer'), 1);
    }
    
    /**
     * Disable schemas from known SEO plugins
     */
    public function disable_plugin_schemas() {
        // Yoast SEO
        if (defined('WPSEO_VERSION')) {
            add_filter('wpseo_json_ld_output', '__return_false', 99);
            add_filter('wpseo_schema_graph', '__return_false', 99);
            add_filter('wpseo_schema_graph_pieces', '__return_empty_array', 99, 2);
            add_filter('disable_wpseo_json_ld_search', '__return_true', 99);
            add_filter('wpseo_json_ld_search_output', '__return_false', 99);
        }
        
        // Rank Math
        if (defined('RANK_MATH_VERSION')) {
            add_filter('rank_math/json_ld', '__return_false', 99);
            add_filter('rank_math/json_ld/enabled', '__return_false', 99);
            add_filter('rank_math/snippet/rich_snippet_enable', '__return_false', 99);
        }
        
        // SEOPress
        if (defined('SEOPRESS_VERSION')) {
            add_filter('seopress_schemas_enabled', '__return_false', 99);
            add_filter('seopress_pro_schemas_enabled', '__return_false', 99);
            add_filter('seopress_schemas_jsonld_enabled', '__return_false', 99);
        }
        
        // All in One SEO
        if (defined('AIOSEO_VERSION')) {
            add_filter('aioseo_schema_disable', '__return_true', 99);
            add_filter('aioseo_json_ld_output', '__return_false', 99);
            add_filter('aioseo_schema_output', '__return_false', 99);
        }
        
        // The SEO Framework
        if (defined('THE_SEO_FRAMEWORK_VERSION')) {
            add_filter('the_seo_framework_ldjson_scripts', '__return_false', 99);
            add_filter('the_seo_framework_receive_json_data', '__return_empty_array', 99);
        }
        
        // Schema Pro
        if (defined('BSF_AIOSRS_PRO_VER')) {
            add_filter('wp_schema_pro_schema_enabled', '__return_false', 99);
            add_filter('wp_schema_pro_output_schema_markup', '__return_false', 99);
        }
        
        // WP Product Review
        if (defined('WPPR_VERSION')) {
            add_filter('wppr_rich_snippet', '__return_false', 99);
        }
        
        // Disable WordPress core sitelinks searchbox
        add_filter('disable_wpseo_json_ld_search', '__return_true', 99);
        remove_action('wp_head', 'wp_seo_schema_output', 90);
    }
    
    /**
     * Start output buffering for schema scrubbing
     */
    public function start_schema_buffer() {
        // Skip on admin, feeds, and AMP
        if (is_admin() || is_feed() || (function_exists('is_amp_endpoint') && is_amp_endpoint())) {
            return;
        }
        
        // Only buffer on relevant pages
        if (!$this->should_output_schema()) {
            return;
        }
        
        // Start buffering
        ob_start(array($this, 'scrub_other_schemas'));
    }
    
    /**
     * Remove non-AlmaSEO JSON-LD from output
     */
    public function scrub_other_schemas($html) {
        // Pattern to match all JSON-LD scripts
        $pattern = '/<script[^>]*type=["\']application\/ld\+json["\'][^>]*>.*?<\/script>/is';
        
        // Find all JSON-LD scripts
        preg_match_all($pattern, $html, $matches);
        
        if (!empty($matches[0])) {
            foreach ($matches[0] as $match) {
                // Keep our schema (has id="almaseo-jsonld" or data-almaseo="1")
                if (strpos($match, 'id="almaseo-jsonld"') !== false || 
                    strpos($match, "id='almaseo-jsonld'") !== false ||
                    strpos($match, 'data-almaseo="1"') !== false ||
                    strpos($match, "data-almaseo='1'") !== false) {
                    continue;
                }
                
                // Remove other JSON-LD
                $html = str_replace($match, '', $html);
            }
        }
        
        return $html;
    }
}

// Initialize the schema handler
add_action('init', function() {
    AlmaSEO_Schema_Handler::get_instance();
}, 0);