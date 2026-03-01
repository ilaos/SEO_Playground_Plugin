<?php
/**
 * AlmaSEO Meta & Social Tags Handler
 * Handles meta robots, canonical, OG, and Twitter tags with proper fallbacks
 * 
 * @package AlmaSEO
 * @version 2.9.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AlmaSEO_Meta_Social_Handler {
    
    private static $instance = null;
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        // Output meta tags in head
        add_action('wp_head', array($this, 'output_meta_tags'), 1);
        
        // Save meta fields
        add_action('save_post', array($this, 'save_meta_fields'));
        
        // Add admin scripts for validation
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    /**
     * Output all meta tags
     */
    public function output_meta_tags() {
        if (!is_singular() && !is_front_page()) {
            return;
        }
        
        global $post;
        $post_id = is_singular() ? $post->ID : get_option('page_on_front');
        
        if (!$post_id) {
            return;
        }
        
        // Output meta robots
        $this->output_meta_robots($post_id);
        
        // Output canonical URL
        $this->output_canonical($post_id);
        
        // Output Open Graph tags
        $this->output_open_graph($post_id);
        
        // Output Twitter Card tags
        $this->output_twitter_card($post_id);
    }
    
    /**
     * Output meta robots tag with mutual exclusivity
     */
    private function output_meta_robots($post_id) {
        $robots_parts = array();
        
        // Index/NoIndex (mutually exclusive)
        $index = get_post_meta($post_id, '_almaseo_robots_index', true);
        if ($index === 'noindex') {
            $robots_parts[] = 'noindex';
        } else {
            $robots_parts[] = 'index';
        }
        
        // Follow/NoFollow (mutually exclusive)
        $follow = get_post_meta($post_id, '_almaseo_robots_follow', true);
        if ($follow === 'nofollow') {
            $robots_parts[] = 'nofollow';
        } else {
            $robots_parts[] = 'follow';
        }
        
        // Independent directives - only add if explicitly set to no
        if (get_post_meta($post_id, '_almaseo_robots_archive', true) === 'noarchive') {
            $robots_parts[] = 'noarchive';
        }
        
        if (get_post_meta($post_id, '_almaseo_robots_snippet', true) === 'nosnippet') {
            $robots_parts[] = 'nosnippet';
        }
        
        if (get_post_meta($post_id, '_almaseo_robots_imageindex', true) === 'noimageindex') {
            $robots_parts[] = 'noimageindex';
        }
        
        if (get_post_meta($post_id, '_almaseo_robots_translate', true) === 'notranslate') {
            $robots_parts[] = 'notranslate';
        }
        
        // Only output non-default values
        $default = array('index', 'follow');
        $non_default = array_diff($robots_parts, $default);
        
        if (!empty($non_default) || count($robots_parts) > 2) {
            $robots_content = implode(', ', $robots_parts);
            echo '<meta name="robots" content="' . esc_attr($robots_content) . '" />' . "\n";
        }
    }
    
    /**
     * Output canonical URL with validation
     */
    private function output_canonical($post_id) {
        global $page;
        
        // Get custom canonical
        $canonical = get_post_meta($post_id, '_almaseo_canonical_url', true);
        
        if (empty($canonical)) {
            // Use default
            $canonical = get_permalink($post_id);
            
            // Handle paginated posts
            if (is_singular() && $page > 1) {
                $canonical = trailingslashit($canonical) . $page . '/';
            }
        } else {
            // Validate custom canonical
            $canonical = $this->validate_canonical_url($canonical, $post_id);
        }
        
        // Apply filter
        $canonical = apply_filters('almaseo_canonical_url', $canonical, $post_id);
        
        if (!empty($canonical)) {
            echo '<link rel="canonical" href="' . esc_url($canonical) . '" />' . "\n";
        }
    }
    
    /**
     * Validate canonical URL
     */
    private function validate_canonical_url($url, $post_id) {
        // Must be absolute
        if (strpos($url, 'http') !== 0) {
            return get_permalink($post_id);
        }
        
        // Remove spaces
        $url = str_replace(' ', '', $url);
        
        // Strip fragment unless intentional
        if (strpos($url, '#') !== false && !get_post_meta($post_id, '_almaseo_canonical_keep_fragment', true)) {
            $url = strtok($url, '#');
        }
        
        return esc_url($url);
    }
    
    /**
     * Output Open Graph tags with fallbacks
     */
    private function output_open_graph($post_id) {
        $post_obj = get_post($post_id);
        if (!$post_obj) {
            return;
        }
        
        // OG Type
        echo '<meta property="og:type" content="article" />' . "\n";
        
        // OG Title (fallback to SEO title, then post title)
        $og_title = get_post_meta($post_id, '_almaseo_og_title', true);
        if (empty($og_title)) {
            $og_title = get_post_meta($post_id, '_almaseo_title', true);
        }
        if (empty($og_title)) {
            $og_title = $post_obj->post_title;
        }
        if (!empty($og_title)) {
            $og_title = $this->sanitize_social_text($og_title, 70);
            echo '<meta property="og:title" content="' . esc_attr($og_title) . '" />' . "\n";
        }
        
        // OG Description (fallback to meta description, then excerpt)
        $og_desc = get_post_meta($post_id, '_almaseo_og_description', true);
        if (empty($og_desc)) {
            $og_desc = get_post_meta($post_id, '_almaseo_description', true);
        }
        if (empty($og_desc)) {
            $og_desc = get_the_excerpt($post_obj);
        }
        if (!empty($og_desc)) {
            $og_desc = $this->sanitize_social_text($og_desc, 200);
            echo '<meta property="og:description" content="' . esc_attr($og_desc) . '" />' . "\n";
        }
        
        // OG URL
        $og_url = $this->get_canonical_url($post_id);
        echo '<meta property="og:url" content="' . esc_url($og_url) . '" />' . "\n";
        
        // OG Image (use schema handler's image chain)
        $og_image = $this->get_social_image($post_id);
        if (!empty($og_image)) {
            echo '<meta property="og:image" content="' . esc_url($og_image) . '" />' . "\n";
            
            // Try to get image dimensions
            $image_id = attachment_url_to_postid($og_image);
            if ($image_id) {
                $image_meta = wp_get_attachment_metadata($image_id);
                if ($image_meta) {
                    echo '<meta property="og:image:width" content="' . esc_attr($image_meta['width']) . '" />' . "\n";
                    echo '<meta property="og:image:height" content="' . esc_attr($image_meta['height']) . '" />' . "\n";
                }
            }
        }
        
        // OG Site Name
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";
        
        // OG Locale
        echo '<meta property="og:locale" content="' . esc_attr(get_locale()) . '" />' . "\n";
    }
    
    /**
     * Output Twitter Card tags
     */
    private function output_twitter_card($post_id) {
        // Twitter Card Type
        $twitter_card = get_post_meta($post_id, '_almaseo_twitter_card', true);
        $image = $this->get_social_image($post_id);
        
        // Auto-adjust card type based on image availability
        if (empty($twitter_card)) {
            $twitter_card = !empty($image) ? 'summary_large_image' : 'summary';
        } elseif ($twitter_card === 'summary_large_image' && empty($image)) {
            // Downgrade if no image available
            $twitter_card = 'summary';
        }
        
        echo '<meta name="twitter:card" content="' . esc_attr($twitter_card) . '" />' . "\n";
        
        // Twitter Title (fallback to OG title)
        $twitter_title = get_post_meta($post_id, '_almaseo_twitter_title', true);
        if (empty($twitter_title)) {
            $twitter_title = get_post_meta($post_id, '_almaseo_og_title', true);
        }
        if (empty($twitter_title)) {
            $twitter_title = get_post_meta($post_id, '_almaseo_title', true);
        }
        if (empty($twitter_title)) {
            $twitter_title = get_the_title($post_id);
        }
        if (!empty($twitter_title)) {
            $twitter_title = $this->sanitize_social_text($twitter_title, 70);
            echo '<meta name="twitter:title" content="' . esc_attr($twitter_title) . '" />' . "\n";
        }
        
        // Twitter Description (fallback to OG description)
        $twitter_desc = get_post_meta($post_id, '_almaseo_twitter_description', true);
        if (empty($twitter_desc)) {
            $twitter_desc = get_post_meta($post_id, '_almaseo_og_description', true);
        }
        if (empty($twitter_desc)) {
            $twitter_desc = get_post_meta($post_id, '_almaseo_description', true);
        }
        if (!empty($twitter_desc)) {
            $twitter_desc = $this->sanitize_social_text($twitter_desc, 200);
            echo '<meta name="twitter:description" content="' . esc_attr($twitter_desc) . '" />' . "\n";
        }
        
        // Twitter Image
        if (!empty($image)) {
            echo '<meta name="twitter:image" content="' . esc_url($image) . '" />' . "\n";
        }
        
        // Twitter Site (if configured)
        $twitter_site = get_option('almaseo_twitter_site');
        if (!empty($twitter_site)) {
            echo '<meta name="twitter:site" content="' . esc_attr($twitter_site) . '" />' . "\n";
        }
    }
    
    /**
     * Get social image using schema handler's fallback chain
     */
    private function get_social_image($post_id) {
        // Try OG image first
        $og_image = get_post_meta($post_id, '_almaseo_og_image', true);
        if (!empty($og_image) && $this->is_valid_image($og_image)) {
            return $this->ensure_absolute_url($og_image);
        }
        
        // Try featured image
        if (has_post_thumbnail($post_id)) {
            $image_url = get_the_post_thumbnail_url($post_id, 'full');
            if (!empty($image_url) && $this->is_valid_image($image_url)) {
                return $image_url;
            }
        }
        
        // Try first content image
        $post = get_post($post_id);
        if ($post) {
            $first_image = $this->get_first_content_image($post->post_content);
            if (!empty($first_image) && $this->is_valid_image($first_image)) {
                return $this->ensure_absolute_url($first_image);
            }
        }
        
        // Try site icon
        $site_icon_url = get_site_icon_url(512);
        if (!empty($site_icon_url) && $this->is_valid_image($site_icon_url)) {
            return $site_icon_url;
        }
        
        // Try custom logo
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
            if (!empty($logo_url) && $this->is_valid_image($logo_url)) {
                return $logo_url;
            }
        }
        
        // Plugin default
        return plugin_dir_url(dirname(__FILE__)) . 'assets/img/default-schema.jpg';
    }
    
    /**
     * Extract first image from content
     */
    private function get_first_content_image($content) {
        preg_match('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches);
        return !empty($matches[1]) ? $matches[1] : '';
    }
    
    /**
     * Check if image URL is valid (no SVG)
     */
    private function is_valid_image($url) {
        return !empty($url) && !preg_match('/\.svg$/i', $url) && strpos($url, 'data:') !== 0;
    }
    
    /**
     * Ensure URL is absolute
     */
    private function ensure_absolute_url($url) {
        if (strpos($url, 'http') === 0) {
            return $url;
        }
        if (strpos($url, '//') === 0) {
            return (is_ssl() ? 'https:' : 'http:') . $url;
        }
        if (strpos($url, '/') === 0) {
            return home_url($url);
        }
        return home_url('/' . $url);
    }
    
    /**
     * Get canonical URL for post
     */
    private function get_canonical_url($post_id) {
        $canonical = get_post_meta($post_id, '_almaseo_canonical_url', true);
        if (empty($canonical)) {
            $canonical = get_permalink($post_id);
        }
        return apply_filters('almaseo_canonical_url', $canonical, $post_id);
    }
    
    /**
     * Sanitize text for social tags
     */
    private function sanitize_social_text($text, $max_length = 200) {
        // Strip HTML
        $text = wp_strip_all_tags($text);
        
        // Decode entities
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, get_bloginfo('charset'));
        
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);
        $text = trim($text);
        
        // Truncate safely (multibyte aware)
        if (mb_strlen($text) > $max_length) {
            $text = mb_substr($text, 0, $max_length - 3) . '...';
        }
        
        return $text;
    }
    
    /**
     * Save meta fields with validation
     */
    public function save_meta_fields($post_id) {
        // Check nonce
        if (!isset($_POST['almaseo_meta_nonce']) || !wp_verify_nonce($_POST['almaseo_meta_nonce'], 'almaseo_save_meta')) {
            return;
        }
        
        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Skip autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Save meta robots with proper mutual exclusivity
        // Index/NoIndex pair - exactly one must be active
        if (isset($_POST['almaseo_robots_noindex']) && $_POST['almaseo_robots_noindex']) {
            update_post_meta($post_id, '_almaseo_robots_index', 'noindex');
        } else {
            // Default to index if noindex is not checked
            update_post_meta($post_id, '_almaseo_robots_index', '');
        }
        
        // Follow/NoFollow pair - exactly one must be active
        if (isset($_POST['almaseo_robots_nofollow']) && $_POST['almaseo_robots_nofollow']) {
            update_post_meta($post_id, '_almaseo_robots_follow', 'nofollow');
        } else {
            // Default to follow if nofollow is not checked
            update_post_meta($post_id, '_almaseo_robots_follow', '');
        }
        
        // Save independent directives
        $directives = array('archive', 'snippet', 'imageindex', 'translate');
        foreach ($directives as $directive) {
            $key = 'almaseo_robots_' . $directive;
            $meta_key = '_almaseo_robots_' . $directive;
            
            if (isset($_POST[$key])) {
                update_post_meta($post_id, $meta_key, $_POST[$key] ? '' : 'no' . $directive);
            } else {
                update_post_meta($post_id, $meta_key, 'no' . $directive);
            }
        }
        
        // Save canonical URL (non-blocking - save exactly what user entered)
        if (isset($_POST['almaseo_canonical_url'])) {
            $canonical = sanitize_text_field($_POST['almaseo_canonical_url']);
            // Save exactly what the user entered, even if not absolute or cross-domain
            update_post_meta($post_id, '_almaseo_canonical_url', $canonical);
        }
        
        // Save social fields with sanitization
        $social_fields = array(
            'almaseo_og_title',
            'almaseo_og_description',
            'almaseo_og_image',
            'almaseo_twitter_card',
            'almaseo_twitter_title',
            'almaseo_twitter_description'
        );
        
        foreach ($social_fields as $field) {
            if (isset($_POST[$field])) {
                $value = $_POST[$field];
                
                // Special handling for images
                if (strpos($field, '_image') !== false) {
                    $value = esc_url_raw($value);
                } elseif (strpos($field, '_card') !== false) {
                    $value = sanitize_text_field($value);
                } else {
                    // Text fields - strip HTML but preserve text
                    $value = wp_strip_all_tags($value);
                }
                
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
    }
    
    /**
     * Enqueue admin scripts for validation
     */
    public function enqueue_admin_scripts($hook) {
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        // Localize strings for JavaScript
        wp_localize_script('almaseo-admin', 'almaseoMeta', array(
            'canonicalWarning' => __('This URL has a different domain than your site. This is unusual but allowed.', 'almaseo'),
            'canonicalError' => __('Please enter a valid absolute URL starting with http:// or https://', 'almaseo'),
            'imageDowngrade' => __('No image available. Card type changed to summary.', 'almaseo'),
            'charCount' => __('characters', 'almaseo'),
            'charWarning' => __('Recommended maximum: %d characters', 'almaseo')
        ));
    }
}

// Initialize the handler
add_action('init', function() {
    AlmaSEO_Meta_Social_Handler::get_instance();
}, 0);