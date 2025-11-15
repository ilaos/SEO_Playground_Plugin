<?php
/**
 * AlmaSEO Image Sitemap Provider
 * 
 * Extracts and provides image URLs for sitemaps
 * 
 * @package AlmaSEO
 * @since 4.10.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Provider_Image {
    
    /**
     * Settings
     */
    private $settings;
    
    /**
     * Media settings
     */
    private $media_settings;
    
    /**
     * Constructor
     */
    public function __construct($settings) {
        $this->settings = $settings;
        $this->media_settings = $settings['media']['image'] ?? array(
            'enabled' => true,
            'max_per_url' => 20,
            'dedupe_cdn' => true
        );
    }
    
    /**
     * Get maximum number of pages
     */
    public function get_max_pages() {
        if (!$this->media_settings['enabled']) {
            return 0;
        }
        
        $total = $this->get_total_urls_with_images();
        $per_page = $this->settings['links_per_sitemap'];
        return (int) ceil($total / $per_page);
    }
    
    /**
     * Get total URLs with images
     */
    private function get_total_urls_with_images() {
        global $wpdb;
        
        // Count posts with potential images (featured or in content)
        $count = $wpdb->get_var("
            SELECT COUNT(DISTINCT p.ID)
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
            WHERE p.post_status = 'publish'
            AND p.post_type IN ('post', 'page')
            AND (
                pm.meta_value IS NOT NULL
                OR p.post_content LIKE '%<img%'
                OR p.post_content LIKE '%wp:image%'
                OR p.post_content LIKE '%wp:gallery%'
            )
        ");
        
        return (int) $count;
    }
    
    /**
     * Get URLs for specific page
     */
    public function get_urls($page = 1) {
        global $wpdb;
        
        if (!$this->media_settings['enabled']) {
            return array();
        }
        
        $per_page = $this->settings['links_per_sitemap'];
        $offset = ($page - 1) * $per_page;
        
        // Get posts with potential images
        $posts = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT p.ID, p.post_modified_gmt, p.post_content, p.post_title
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
            WHERE p.post_status = 'publish'
            AND p.post_type IN ('post', 'page')
            AND (
                pm.meta_value IS NOT NULL
                OR p.post_content LIKE '%<img%'
                OR p.post_content LIKE '%wp:image%'
                OR p.post_content LIKE '%wp:gallery%'
            )
            ORDER BY p.post_modified_gmt DESC
            LIMIT %d OFFSET %d
        ", $per_page, $offset));
        
        $urls = array();
        
        foreach ($posts as $post) {
            $images = $this->extract_images_from_post($post);
            
            if (!empty($images)) {
                $url_data = array(
                    'loc' => get_permalink($post->ID),
                    'lastmod' => $post->post_modified_gmt,
                    'images' => $images
                );
                
                $urls[] = $url_data;
            }
        }
        
        return $urls;
    }
    
    /**
     * Extract images from a post
     */
    public function extract_images_from_post($post) {
        $images = array();
        $seen_urls = array();
        $max_images = $this->media_settings['max_per_url'];
        
        // 1. Featured image
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        if ($thumbnail_id) {
            $image_data = $this->get_attachment_image_data($thumbnail_id);
            if ($image_data && !isset($seen_urls[$image_data['loc']])) {
                $images[] = $image_data;
                $seen_urls[$image_data['loc']] = true;
            }
        }
        
        // 2. Gallery blocks
        if (strpos($post->post_content, 'wp:gallery') !== false) {
            $gallery_images = $this->extract_gallery_block_images($post->post_content);
            foreach ($gallery_images as $img) {
                if (count($images) >= $max_images) break;
                $loc = $this->normalize_image_url($img['loc']);
                if (!isset($seen_urls[$loc])) {
                    $img['loc'] = $loc;
                    $images[] = $img;
                    $seen_urls[$loc] = true;
                }
            }
        }
        
        // 3. Image blocks
        if (strpos($post->post_content, 'wp:image') !== false) {
            $block_images = $this->extract_image_block_images($post->post_content);
            foreach ($block_images as $img) {
                if (count($images) >= $max_images) break;
                $loc = $this->normalize_image_url($img['loc']);
                if (!isset($seen_urls[$loc])) {
                    $img['loc'] = $loc;
                    $images[] = $img;
                    $seen_urls[$loc] = true;
                }
            }
        }
        
        // 4. HTML img tags
        if (strpos($post->post_content, '<img') !== false) {
            $html_images = $this->extract_html_images($post->post_content);
            foreach ($html_images as $img) {
                if (count($images) >= $max_images) break;
                $loc = $this->normalize_image_url($img['loc']);
                if (!isset($seen_urls[$loc])) {
                    $img['loc'] = $loc;
                    $images[] = $img;
                    $seen_urls[$loc] = true;
                }
            }
        }
        
        // 5. ACF image fields (best effort)
        if (function_exists('get_field')) {
            $acf_images = $this->extract_acf_images($post->ID);
            foreach ($acf_images as $img) {
                if (count($images) >= $max_images) break;
                $loc = $this->normalize_image_url($img['loc']);
                if (!isset($seen_urls[$loc])) {
                    $img['loc'] = $loc;
                    $images[] = $img;
                    $seen_urls[$loc] = true;
                }
            }
        }
        
        return array_slice($images, 0, $max_images);
    }
    
    /**
     * Get attachment image data
     */
    private function get_attachment_image_data($attachment_id) {
        $url = wp_get_attachment_url($attachment_id);
        if (!$url) {
            return null;
        }
        
        $attachment = get_post($attachment_id);
        $alt = get_post_meta($attachment_id, '_wp_attachment_image_alt', true);
        $license = get_post_meta($attachment_id, '_almaseo_image_license', true);
        
        $data = array(
            'loc' => $url
        );
        
        if ($attachment->post_title) {
            $data['title'] = $attachment->post_title;
        }
        
        if ($attachment->post_excerpt) {
            $data['caption'] = $attachment->post_excerpt;
        } elseif ($alt) {
            $data['caption'] = $alt;
        }
        
        if ($license) {
            $data['license'] = $license;
        }
        
        return $data;
    }
    
    /**
     * Extract images from gallery blocks
     */
    private function extract_gallery_block_images($content) {
        $images = array();
        
        // Parse gallery blocks
        if (preg_match_all('/<!-- wp:gallery.*?(\{.*?\}).*?-->/s', $content, $matches)) {
            foreach ($matches[1] as $json) {
                $data = json_decode($json, true);
                if (isset($data['ids']) && is_array($data['ids'])) {
                    foreach ($data['ids'] as $id) {
                        $img_data = $this->get_attachment_image_data($id);
                        if ($img_data) {
                            $images[] = $img_data;
                        }
                    }
                }
            }
        }
        
        return $images;
    }
    
    /**
     * Extract images from image blocks
     */
    private function extract_image_block_images($content) {
        $images = array();
        
        // Parse image blocks
        if (preg_match_all('/<!-- wp:image.*?(\{.*?\}).*?-->/s', $content, $matches)) {
            foreach ($matches[1] as $json) {
                $data = json_decode($json, true);
                if (isset($data['id'])) {
                    $img_data = $this->get_attachment_image_data($data['id']);
                    if ($img_data) {
                        $images[] = $img_data;
                    }
                } elseif (isset($data['url'])) {
                    $images[] = array(
                        'loc' => $data['url'],
                        'title' => $data['alt'] ?? '',
                        'caption' => $data['caption'] ?? ''
                    );
                }
            }
        }
        
        return $images;
    }
    
    /**
     * Extract images from HTML
     */
    private function extract_html_images($content) {
        $images = array();
        
        // Match img tags with various src attributes
        $patterns = array(
            '/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i',
            '/<img[^>]+data-src=["\']([^"\']+)["\'][^>]*>/i',
            '/<img[^>]+data-lazy-src=["\']([^"\']+)["\'][^>]*>/i'
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $url) {
                    // Skip data URIs and non-HTTP(S)
                    if (strpos($url, 'data:') === 0 || !preg_match('/^https?:\/\//i', $url)) {
                        continue;
                    }
                    
                    // Extract alt text if available
                    $alt = '';
                    if (preg_match('/alt=["\']([^"\']*)["\']/', $matches[0][0], $alt_match)) {
                        $alt = $alt_match[1];
                    }
                    
                    $images[] = array(
                        'loc' => $url,
                        'title' => $alt,
                        'caption' => ''
                    );
                }
            }
        }
        
        return $images;
    }
    
    /**
     * Extract ACF images
     */
    private function extract_acf_images($post_id) {
        $images = array();
        
        // Common ACF image field names
        $field_names = array('image', 'images', 'gallery', 'featured_image', 'hero_image', 'banner');
        
        foreach ($field_names as $field) {
            $value = get_field($field, $post_id);
            
            if (is_array($value)) {
                // Gallery or image array
                if (isset($value['url'])) {
                    // Single image array
                    $images[] = array(
                        'loc' => $value['url'],
                        'title' => $value['title'] ?? '',
                        'caption' => $value['caption'] ?? $value['alt'] ?? ''
                    );
                } else {
                    // Multiple images
                    foreach ($value as $img) {
                        if (is_array($img) && isset($img['url'])) {
                            $images[] = array(
                                'loc' => $img['url'],
                                'title' => $img['title'] ?? '',
                                'caption' => $img['caption'] ?? $img['alt'] ?? ''
                            );
                        }
                    }
                }
            } elseif (is_numeric($value)) {
                // Attachment ID
                $img_data = $this->get_attachment_image_data($value);
                if ($img_data) {
                    $images[] = $img_data;
                }
            } elseif (is_string($value) && preg_match('/^https?:\/\//i', $value)) {
                // URL string
                $images[] = array('loc' => $value);
            }
        }
        
        return $images;
    }
    
    /**
     * Normalize image URL
     */
    private function normalize_image_url($url) {
        if (!$this->media_settings['dedupe_cdn']) {
            return $url;
        }
        
        // Parse URL
        $parsed = parse_url($url);
        if (!$parsed || !isset($parsed['query'])) {
            return $url;
        }
        
        // Parse query string
        parse_str($parsed['query'], $params);
        
        // Remove common CDN/resize params
        $remove_params = array(
            'w', 'h', 'width', 'height', 'fit', 'auto', 'quality', 
            'ixlib', 'utm_source', 'utm_medium', 'utm_campaign',
            'utm_term', 'utm_content', 'resize', 'crop'
        );
        
        foreach ($remove_params as $param) {
            unset($params[$param]);
        }
        
        // Rebuild URL
        $clean_url = $parsed['scheme'] . '://' . $parsed['host'];
        if (isset($parsed['port'])) {
            $clean_url .= ':' . $parsed['port'];
        }
        if (isset($parsed['path'])) {
            $clean_url .= $parsed['path'];
        }
        if (!empty($params)) {
            $clean_url .= '?' . http_build_query($params);
        }
        
        return $clean_url;
    }
    
    /**
     * Get last modified date for page
     */
    public function get_last_modified($page = 1) {
        global $wpdb;
        
        $per_page = $this->settings['links_per_sitemap'];
        $offset = ($page - 1) * $per_page;
        
        $last_modified = $wpdb->get_var($wpdb->prepare("
            SELECT MAX(post_modified_gmt)
            FROM (
                SELECT p.post_modified_gmt
                FROM {$wpdb->posts} p
                LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
                WHERE p.post_status = 'publish'
                AND p.post_type IN ('post', 'page')
                AND (
                    pm.meta_value IS NOT NULL
                    OR p.post_content LIKE '%<img%'
                    OR p.post_content LIKE '%wp:image%'
                    OR p.post_content LIKE '%wp:gallery%'
                )
                ORDER BY p.post_modified_gmt DESC
                LIMIT %d OFFSET %d
            ) as subset
        ", $per_page, $offset));
        
        return $last_modified ? date('c', strtotime($last_modified)) : null;
    }
    
    /**
     * Check if provider supports images
     */
    public function supports_images() {
        return true;
    }
    
    /**
     * Get items using seek pagination (for static generation)
     */
    public function get_items_seek($last_id = 0, $limit = 1000, $args = array()) {
        global $wpdb;
        
        $posts = $wpdb->get_results($wpdb->prepare("
            SELECT DISTINCT p.ID, p.post_modified_gmt, p.post_content, p.post_title
            FROM {$wpdb->posts} p
            LEFT JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id AND pm.meta_key = '_thumbnail_id'
            WHERE p.post_status = 'publish'
            AND p.post_type IN ('post', 'page')
            AND p.ID > %d
            AND (
                pm.meta_value IS NOT NULL
                OR p.post_content LIKE '%<img%'
                OR p.post_content LIKE '%wp:image%'
                OR p.post_content LIKE '%wp:gallery%'
            )
            ORDER BY p.ID ASC
            LIMIT %d
        ", $last_id, $limit));
        
        return $posts;
    }
    
    /**
     * Get URL data for a single item
     */
    public function get_url_data($post) {
        $images = $this->extract_images_from_post($post);
        
        if (empty($images)) {
            return null;
        }
        
        return array(
            'loc' => get_permalink($post->ID),
            'lastmod' => $post->post_modified_gmt,
            'images' => $images
        );
    }
}