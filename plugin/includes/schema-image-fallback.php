<?php
/**
 * AlmaSEO Schema Image Fallback Chain
 * 
 * Implements intelligent image fallback for schema markup
 * 
 * @package AlmaSEO
 * @since 2.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Schema Image Fallback Class
 */
class AlmaSEO_Schema_Image_Fallback {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Image cache
     */
    private $image_cache = array();
    
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
        // Hook into schema generation
        add_filter('almaseo_schema_article_image', array($this, 'get_article_image'), 10, 2);
        add_filter('almaseo_schema_webpage_image', array($this, 'get_webpage_image'), 10, 2);
        add_filter('almaseo_schema_organization_logo', array($this, 'get_organization_logo'), 10, 1);
        add_filter('almaseo_schema_person_image', array($this, 'get_person_image'), 10, 2);
    }
    
    /**
     * Get article image with fallback chain
     * 
     * @param mixed $image Current image data
     * @param int $post_id Post ID
     * @return array Image schema data
     */
    public function get_article_image($image, $post_id) {
        // Check cache
        $cache_key = 'article_' . $post_id;
        if (isset($this->image_cache[$cache_key])) {
            return $this->image_cache[$cache_key];
        }
        
        // Fallback chain for article images
        $image_url = $this->get_image_with_fallback($post_id, array(
            'featured_image',
            'first_content_image',
            'opengraph_image',
            'twitter_image',
            'author_avatar',
            'site_logo',
            'default_placeholder'
        ));
        
        if ($image_url) {
            $image_data = $this->get_image_schema($image_url);
            $this->image_cache[$cache_key] = $image_data;
            return $image_data;
        }
        
        return $image;
    }
    
    /**
     * Get webpage image with fallback chain
     * 
     * @param mixed $image Current image data
     * @param int $post_id Post ID
     * @return array Image schema data
     */
    public function get_webpage_image($image, $post_id) {
        // Check cache
        $cache_key = 'webpage_' . $post_id;
        if (isset($this->image_cache[$cache_key])) {
            return $this->image_cache[$cache_key];
        }
        
        // Fallback chain for webpage images
        $image_url = $this->get_image_with_fallback($post_id, array(
            'featured_image',
            'opengraph_image',
            'first_content_image',
            'header_image',
            'site_logo',
            'default_placeholder'
        ));
        
        if ($image_url) {
            $image_data = $this->get_image_schema($image_url);
            $this->image_cache[$cache_key] = $image_data;
            return $image_data;
        }
        
        return $image;
    }
    
    /**
     * Get organization logo with fallback chain
     * 
     * @param mixed $logo Current logo data
     * @return array Logo schema data
     */
    public function get_organization_logo($logo) {
        // Check cache
        if (isset($this->image_cache['org_logo'])) {
            return $this->image_cache['org_logo'];
        }
        
        // Try custom logo first
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'full');
            if ($logo_url) {
                $logo_data = $this->get_image_schema($logo_url);
                $this->image_cache['org_logo'] = $logo_data;
                return $logo_data;
            }
        }
        
        // Try site icon
        $site_icon_id = get_option('site_icon');
        if ($site_icon_id) {
            $icon_url = wp_get_attachment_image_url($site_icon_id, 'full');
            if ($icon_url) {
                $logo_data = $this->get_image_schema($icon_url);
                $this->image_cache['org_logo'] = $logo_data;
                return $logo_data;
            }
        }
        
        // Try header image
        $header_image = get_header_image();
        if ($header_image) {
            $logo_data = $this->get_image_schema($header_image);
            $this->image_cache['org_logo'] = $logo_data;
            return $logo_data;
        }
        
        // Generate placeholder
        $placeholder = $this->generate_text_logo();
        if ($placeholder) {
            $this->image_cache['org_logo'] = $placeholder;
            return $placeholder;
        }
        
        return $logo;
    }
    
    /**
     * Get person image with fallback chain
     * 
     * @param mixed $image Current image data
     * @param int $user_id User ID
     * @return array Image schema data
     */
    public function get_person_image($image, $user_id) {
        // Check cache
        $cache_key = 'person_' . $user_id;
        if (isset($this->image_cache[$cache_key])) {
            return $this->image_cache[$cache_key];
        }
        
        // Get avatar URL
        $avatar_url = get_avatar_url($user_id, array('size' => 512));
        
        if ($avatar_url && !strpos($avatar_url, 'gravatar.com/avatar/0')) {
            $image_data = $this->get_image_schema($avatar_url);
            $this->image_cache[$cache_key] = $image_data;
            return $image_data;
        }
        
        // Try user meta image
        $user_image = get_user_meta($user_id, 'user_image', true);
        if ($user_image) {
            $image_data = $this->get_image_schema($user_image);
            $this->image_cache[$cache_key] = $image_data;
            return $image_data;
        }
        
        return $image;
    }
    
    /**
     * Get image with fallback chain
     * 
     * @param int $post_id Post ID
     * @param array $methods Fallback methods to try
     * @return string|false Image URL or false
     */
    private function get_image_with_fallback($post_id, $methods) {
        foreach ($methods as $method) {
            $image_url = false;
            
            switch ($method) {
                case 'featured_image':
                    $image_url = $this->get_featured_image($post_id);
                    break;
                    
                case 'first_content_image':
                    $image_url = $this->get_first_content_image($post_id);
                    break;
                    
                case 'opengraph_image':
                    $image_url = $this->get_opengraph_image($post_id);
                    break;
                    
                case 'twitter_image':
                    $image_url = $this->get_twitter_image($post_id);
                    break;
                    
                case 'author_avatar':
                    $image_url = $this->get_author_avatar($post_id);
                    break;
                    
                case 'header_image':
                    $image_url = $this->get_header_image();
                    break;
                    
                case 'site_logo':
                    $image_url = $this->get_site_logo();
                    break;
                    
                case 'default_placeholder':
                    $image_url = $this->get_default_placeholder();
                    break;
            }
            
            if ($image_url && $this->is_valid_image($image_url)) {
                return $image_url;
            }
        }
        
        return false;
    }
    
    /**
     * Get featured image
     */
    private function get_featured_image($post_id) {
        if (has_post_thumbnail($post_id)) {
            return get_the_post_thumbnail_url($post_id, 'full');
        }
        return false;
    }
    
    /**
     * Get first image from content
     */
    private function get_first_content_image($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }
        
        $content = $post->post_content;
        
        // Check for images in content
        preg_match_all('/<img[^>]+src=[\'"]([^\'"]+)[\'"][^>]*>/i', $content, $matches);
        
        if (!empty($matches[1])) {
            foreach ($matches[1] as $url) {
                // Skip data URIs and external images
                if (strpos($url, 'data:') === 0) {
                    continue;
                }
                
                // Make URL absolute if relative
                if (strpos($url, 'http') !== 0) {
                    $url = home_url($url);
                }
                
                if ($this->is_valid_image($url)) {
                    return $url;
                }
            }
        }
        
        // Check for gallery shortcode
        if (has_shortcode($content, 'gallery')) {
            preg_match('/\[gallery[^\]]*ids=[\'"]([^\'"]+)[\'"]/i', $content, $gallery_match);
            if (!empty($gallery_match[1])) {
                $ids = explode(',', $gallery_match[1]);
                $first_id = intval(trim($ids[0]));
                if ($first_id) {
                    $url = wp_get_attachment_image_url($first_id, 'full');
                    if ($url) {
                        return $url;
                    }
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get OpenGraph image
     */
    private function get_opengraph_image($post_id) {
        // Check post meta for OG image
        $og_image = get_post_meta($post_id, '_og_image', true);
        if ($og_image) {
            return $og_image;
        }
        
        // Check Yoast
        $yoast_image = get_post_meta($post_id, '_yoast_wpseo_opengraph-image', true);
        if ($yoast_image) {
            return $yoast_image;
        }
        
        // Check Rank Math
        $rankmath_image = get_post_meta($post_id, 'rank_math_facebook_image', true);
        if ($rankmath_image) {
            return $rankmath_image;
        }
        
        return false;
    }
    
    /**
     * Get Twitter image
     */
    private function get_twitter_image($post_id) {
        // Check post meta for Twitter image
        $twitter_image = get_post_meta($post_id, '_twitter_image', true);
        if ($twitter_image) {
            return $twitter_image;
        }
        
        // Check Yoast
        $yoast_image = get_post_meta($post_id, '_yoast_wpseo_twitter-image', true);
        if ($yoast_image) {
            return $yoast_image;
        }
        
        // Check Rank Math
        $rankmath_image = get_post_meta($post_id, 'rank_math_twitter_image', true);
        if ($rankmath_image) {
            return $rankmath_image;
        }
        
        return false;
    }
    
    /**
     * Get author avatar
     */
    private function get_author_avatar($post_id) {
        $post = get_post($post_id);
        if (!$post) {
            return false;
        }
        
        $avatar_url = get_avatar_url($post->post_author, array('size' => 512));
        
        // Skip default gravatar
        if ($avatar_url && strpos($avatar_url, 'gravatar.com/avatar/0') === false) {
            return $avatar_url;
        }
        
        return false;
    }
    
    /**
     * Get header image
     */
    private function get_header_image() {
        $header_image = get_header_image();
        if ($header_image) {
            return $header_image;
        }
        return false;
    }
    
    /**
     * Get site logo
     */
    private function get_site_logo() {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            return wp_get_attachment_image_url($custom_logo_id, 'full');
        }
        
        $site_icon_id = get_option('site_icon');
        if ($site_icon_id) {
            return wp_get_attachment_image_url($site_icon_id, 'full');
        }
        
        return false;
    }
    
    /**
     * Get default placeholder
     */
    private function get_default_placeholder() {
        // Use a neutral placeholder image
        $placeholder_path = plugin_dir_path(dirname(__FILE__)) . 'assets/img/schema-placeholder.png';
        
        if (file_exists($placeholder_path)) {
            return plugin_dir_url(dirname(__FILE__)) . 'assets/img/schema-placeholder.png';
        }
        
        // Generate a data URI placeholder
        return $this->generate_placeholder_data_uri();
    }
    
    /**
     * Generate placeholder as data URI
     */
    private function generate_placeholder_data_uri() {
        // Create a simple 1200x630 placeholder (Open Graph size)
        $width = 1200;
        $height = 630;
        
        // SVG placeholder
        $site_name = get_bloginfo('name');
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d">
                <rect width="%d" height="%d" fill="#f0f0f0"/>
                <text x="50%%" y="50%%" text-anchor="middle" dy=".3em" 
                      font-family="system-ui, -apple-system, sans-serif" 
                      font-size="48" fill="#999">%s</text>
            </svg>',
            $width, $height, $width, $height,
            $width, $height,
            esc_html($site_name)
        );
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    /**
     * Generate text-based logo
     */
    private function generate_text_logo() {
        $site_name = get_bloginfo('name');
        
        // SVG logo with site name
        $svg = sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="600" height="60" viewBox="0 0 600 60">
                <rect width="600" height="60" fill="#2c3e50"/>
                <text x="50%%" y="50%%" text-anchor="middle" dy=".3em" 
                      font-family="system-ui, -apple-system, sans-serif" 
                      font-size="24" font-weight="bold" fill="#ffffff">%s</text>
            </svg>',
            esc_html($site_name)
        );
        
        $data_uri = 'data:image/svg+xml;base64,' . base64_encode($svg);
        
        return array(
            '@type' => 'ImageObject',
            'url' => $data_uri,
            'width' => 600,
            'height' => 60
        );
    }
    
    /**
     * Check if image URL is valid
     */
    private function is_valid_image($url) {
        if (empty($url)) {
            return false;
        }
        
        // Check if it's a data URI
        if (strpos($url, 'data:') === 0) {
            return true;
        }
        
        // Check file extension
        $valid_extensions = array('jpg', 'jpeg', 'png', 'gif', 'webp', 'svg');
        $extension = strtolower(pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION));
        
        if (!in_array($extension, $valid_extensions)) {
            return false;
        }
        
        // For local images, check if file exists
        if (strpos($url, home_url()) === 0) {
            $upload_dir = wp_upload_dir();
            if (strpos($url, $upload_dir['baseurl']) === 0) {
                $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
                return file_exists($file_path);
            }
        }
        
        return true;
    }
    
    /**
     * Get image schema data
     */
    private function get_image_schema($url) {
        $schema = array(
            '@type' => 'ImageObject',
            'url' => $url
        );
        
        // Get dimensions if possible
        $dimensions = $this->get_image_dimensions($url);
        if ($dimensions) {
            $schema['width'] = $dimensions['width'];
            $schema['height'] = $dimensions['height'];
        }
        
        // Add caption if available
        $attachment_id = attachment_url_to_postid($url);
        if ($attachment_id) {
            $caption = wp_get_attachment_caption($attachment_id);
            if ($caption) {
                $schema['caption'] = $caption;
            }
            
            $alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
            if ($alt) {
                $schema['alternateName'] = $alt;
            }
        }
        
        return $schema;
    }
    
    /**
     * Get image dimensions
     */
    private function get_image_dimensions($url) {
        // For data URIs, return default dimensions
        if (strpos($url, 'data:') === 0) {
            return array('width' => 1200, 'height' => 630);
        }
        
        // Try to get from attachment
        $attachment_id = attachment_url_to_postid($url);
        if ($attachment_id) {
            $metadata = wp_get_attachment_metadata($attachment_id);
            if ($metadata && isset($metadata['width']) && isset($metadata['height'])) {
                return array(
                    'width' => $metadata['width'],
                    'height' => $metadata['height']
                );
            }
        }
        
        // Try getimagesize for local files
        if (strpos($url, home_url()) === 0) {
            $upload_dir = wp_upload_dir();
            if (strpos($url, $upload_dir['baseurl']) === 0) {
                $file_path = str_replace($upload_dir['baseurl'], $upload_dir['basedir'], $url);
                if (file_exists($file_path)) {
                    $size = @getimagesize($file_path);
                    if ($size) {
                        return array(
                            'width' => $size[0],
                            'height' => $size[1]
                        );
                    }
                }
            }
        }
        
        // Default Open Graph dimensions
        return array('width' => 1200, 'height' => 630);
    }
}

// Initialize
AlmaSEO_Schema_Image_Fallback::get_instance();