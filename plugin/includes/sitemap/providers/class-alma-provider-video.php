<?php
/**
 * AlmaSEO Video Sitemap Provider
 * 
 * Extracts and provides video URLs for sitemaps
 * 
 * @package AlmaSEO
 * @since 4.10.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Provider_Video {
    
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
        $this->media_settings = $settings['media']['video'] ?? array(
            'enabled' => true,
            'max_per_url' => 5,
            'fetch_oembed' => true,
            'providers' => array('youtube', 'vimeo', 'self_hosted')
        );
        
        // Hook to clear cache when posts are updated
        add_action('save_post', array($this, 'clear_cache'));
        add_action('delete_post', array($this, 'clear_cache'));
        add_action('transition_post_status', array($this, 'clear_cache'));
    }
    
    /**
     * Clear video posts cache
     */
    public function clear_cache() {
        global $wpdb;
        
        // Clear all video post caches
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '%almaseo_video_posts_%'"
        );
    }
    
    /**
     * Get maximum number of pages
     */
    public function get_max_pages() {
        if (!$this->media_settings['enabled']) {
            return 0;
        }
        
        $total = $this->get_total_urls_with_videos();
        $per_page = $this->settings['links_per_sitemap'];
        return (int) ceil($total / $per_page);
    }
    
    /**
     * Get total URLs with videos
     */
    private function get_total_urls_with_videos() {
        global $wpdb;
        
        // Try to get from cache first
        $cache_key = 'almaseo_video_total_count';
        $count = get_transient($cache_key);
        
        if (false === $count) {
            // Count posts with potential videos
            $count = $wpdb->get_var("
                SELECT COUNT(DISTINCT p.ID)
                FROM {$wpdb->posts} p
                WHERE p.post_status = 'publish'
                AND p.post_type IN ('post', 'page')
                AND (
                    p.post_content LIKE '%youtube.com%'
                    OR p.post_content LIKE '%youtu.be%'
                    OR p.post_content LIKE '%vimeo.com%'
                    OR p.post_content LIKE '%wp:video%'
                    OR p.post_content LIKE '%[video%'
                    OR EXISTS (
                        SELECT 1 FROM {$wpdb->posts} a
                        WHERE a.post_parent = p.ID
                        AND a.post_type = 'attachment'
                        AND a.post_mime_type LIKE 'video/%'
                    )
                )
            ");
            
            // Cache for 2 hours
            set_transient($cache_key, $count, 2 * HOUR_IN_SECONDS);
        }
        
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
        
        // Create cache key for this query
        $cache_key = 'almaseo_video_posts_' . md5($page . '_' . $per_page);
        $posts = get_transient($cache_key);
        
        if (false === $posts) {
            // Get posts with potential videos
            $posts = $wpdb->get_results($wpdb->prepare("
                SELECT DISTINCT p.ID, p.post_modified_gmt, p.post_content, p.post_title
                FROM {$wpdb->posts} p
                WHERE p.post_status = 'publish'
                AND p.post_type IN ('post', 'page')
                AND (
                    p.post_content LIKE '%youtube.com%'
                    OR p.post_content LIKE '%youtu.be%'
                    OR p.post_content LIKE '%vimeo.com%'
                    OR p.post_content LIKE '%wp:video%'
                    OR p.post_content LIKE '%[video%'
                    OR EXISTS (
                        SELECT 1 FROM {$wpdb->posts} a
                        WHERE a.post_parent = p.ID
                        AND a.post_type = 'attachment'
                        AND a.post_mime_type LIKE 'video/%'
                    )
                )
                ORDER BY p.post_modified_gmt DESC
                LIMIT %d OFFSET %d
            ", $per_page, $offset));
            
            // Cache for 1 hour
            set_transient($cache_key, $posts, HOUR_IN_SECONDS);
        }
        
        $urls = array();
        
        foreach ($posts as $post) {
            $videos = $this->extract_videos_from_post($post);
            
            if (!empty($videos)) {
                $url_data = array(
                    'loc' => get_permalink($post->ID),
                    'lastmod' => $post->post_modified_gmt,
                    'videos' => $videos
                );
                
                $urls[] = $url_data;
            }
        }
        
        return $urls;
    }
    
    /**
     * Extract videos from a post
     */
    public function extract_videos_from_post($post) {
        $videos = array();
        $seen_urls = array();
        $max_videos = $this->media_settings['max_per_url'];
        
        // 1. YouTube videos
        if (in_array('youtube', $this->media_settings['providers'])) {
            $youtube_videos = $this->extract_youtube_videos($post->post_content, $post);
            foreach ($youtube_videos as $video) {
                if (count($videos) >= $max_videos) break;
                $key = $video['player_loc'] ?? $video['content_loc'] ?? '';
                if ($key && !isset($seen_urls[$key])) {
                    $videos[] = $video;
                    $seen_urls[$key] = true;
                }
            }
        }
        
        // 2. Vimeo videos
        if (in_array('vimeo', $this->media_settings['providers'])) {
            $vimeo_videos = $this->extract_vimeo_videos($post->post_content, $post);
            foreach ($vimeo_videos as $video) {
                if (count($videos) >= $max_videos) break;
                $key = $video['player_loc'] ?? $video['content_loc'] ?? '';
                if ($key && !isset($seen_urls[$key])) {
                    $videos[] = $video;
                    $seen_urls[$key] = true;
                }
            }
        }
        
        // 3. Self-hosted videos
        if (in_array('self_hosted', $this->media_settings['providers'])) {
            $self_videos = $this->extract_self_hosted_videos($post);
            foreach ($self_videos as $video) {
                if (count($videos) >= $max_videos) break;
                $key = $video['content_loc'] ?? '';
                if ($key && !isset($seen_urls[$key])) {
                    $videos[] = $video;
                    $seen_urls[$key] = true;
                }
            }
        }
        
        // 4. Video blocks/shortcodes
        $block_videos = $this->extract_video_blocks($post->post_content, $post);
        foreach ($block_videos as $video) {
            if (count($videos) >= $max_videos) break;
            $key = $video['player_loc'] ?? $video['content_loc'] ?? '';
            if ($key && !isset($seen_urls[$key])) {
                $videos[] = $video;
                $seen_urls[$key] = true;
            }
        }
        
        return array_slice($videos, 0, $max_videos);
    }
    
    /**
     * Extract YouTube videos
     */
    private function extract_youtube_videos($content, $post) {
        $videos = array();
        
        // YouTube URL patterns
        $patterns = array(
            '/(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([a-zA-Z0-9_-]{11})/i',
            '/youtube\.com\/v\/([a-zA-Z0-9_-]{11})/i'
        );
        
        $found_ids = array();
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $video_id) {
                    if (!isset($found_ids[$video_id])) {
                        $found_ids[$video_id] = true;
                    }
                }
            }
        }
        
        foreach (array_keys($found_ids) as $video_id) {
            $video_data = $this->get_youtube_video_data($video_id, $post);
            if ($video_data) {
                $videos[] = $video_data;
            }
        }
        
        return $videos;
    }
    
    /**
     * Get YouTube video data
     */
    private function get_youtube_video_data($video_id, $post) {
        $data = array(
            'thumbnail_loc' => 'https://img.youtube.com/vi/' . $video_id . '/maxresdefault.jpg',
            'player_loc' => 'https://www.youtube.com/embed/' . $video_id,
            'player_loc_allow_embed' => 'yes',
            'family_friendly' => 'yes',
            'publication_date' => get_the_date('c', $post->ID)
        );
        
        // Try oEmbed for title and description
        if ($this->media_settings['fetch_oembed']) {
            $oembed_url = 'https://www.youtube.com/watch?v=' . $video_id;
            $oembed_data = wp_oembed_get($oembed_url);
            
            if ($oembed_data) {
                // Extract title from oEmbed HTML
                if (preg_match('/title="([^"]+)"/', $oembed_data, $title_match)) {
                    $data['title'] = html_entity_decode($title_match[1], ENT_QUOTES | ENT_HTML5);
                } else {
                    $data['title'] = $post->post_title . ' - Video';
                }
                
                $data['description'] = wp_trim_words($post->post_title, 20);
            } else {
                $data['title'] = $post->post_title . ' - Video';
                $data['description'] = wp_trim_words($post->post_title, 20);
            }
        } else {
            $data['title'] = $post->post_title . ' - Video';
            $data['description'] = wp_trim_words($post->post_title, 20);
        }
        
        // Fallback thumbnail URLs
        if (!$this->url_exists($data['thumbnail_loc'])) {
            $data['thumbnail_loc'] = 'https://img.youtube.com/vi/' . $video_id . '/hqdefault.jpg';
        }
        
        return $data;
    }
    
    /**
     * Extract Vimeo videos
     */
    private function extract_vimeo_videos($content, $post) {
        $videos = array();
        
        // Vimeo URL patterns
        $patterns = array(
            '/vimeo\.com\/(\d+)/i',
            '/player\.vimeo\.com\/video\/(\d+)/i'
        );
        
        $found_ids = array();
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $video_id) {
                    if (!isset($found_ids[$video_id])) {
                        $found_ids[$video_id] = true;
                    }
                }
            }
        }
        
        foreach (array_keys($found_ids) as $video_id) {
            $video_data = $this->get_vimeo_video_data($video_id, $post);
            if ($video_data) {
                $videos[] = $video_data;
            }
        }
        
        return $videos;
    }
    
    /**
     * Get Vimeo video data
     */
    private function get_vimeo_video_data($video_id, $post) {
        $data = array(
            'player_loc' => 'https://player.vimeo.com/video/' . $video_id,
            'player_loc_allow_embed' => 'yes',
            'family_friendly' => 'yes',
            'publication_date' => get_the_date('c', $post->ID)
        );
        
        // Try oEmbed for metadata
        if ($this->media_settings['fetch_oembed']) {
            $oembed_url = 'https://vimeo.com/' . $video_id;
            $oembed_response = wp_remote_get('https://vimeo.com/api/oembed.json?url=' . urlencode($oembed_url));
            
            if (!is_wp_error($oembed_response)) {
                $oembed_data = json_decode(wp_remote_retrieve_body($oembed_response), true);
                
                if ($oembed_data) {
                    $data['title'] = $oembed_data['title'] ?? ($post->post_title . ' - Video');
                    $data['description'] = $oembed_data['description'] ?? wp_trim_words($post->post_title, 20);
                    $data['thumbnail_loc'] = $oembed_data['thumbnail_url'] ?? '';
                    
                    if (isset($oembed_data['duration'])) {
                        $data['duration'] = min(28800, max(1, $oembed_data['duration']));
                    }
                }
            }
        }
        
        // Fallbacks
        if (!isset($data['title'])) {
            $data['title'] = $post->post_title . ' - Video';
        }
        if (!isset($data['description'])) {
            $data['description'] = wp_trim_words($post->post_title, 20);
        }
        if (!isset($data['thumbnail_loc']) || !$data['thumbnail_loc']) {
            // Try to get featured image as fallback
            $thumbnail_id = get_post_thumbnail_id($post->ID);
            if ($thumbnail_id) {
                $data['thumbnail_loc'] = wp_get_attachment_url($thumbnail_id);
            }
        }
        
        // Must have required fields
        if (empty($data['thumbnail_loc'])) {
            return null;
        }
        
        return $data;
    }
    
    /**
     * Extract self-hosted videos
     */
    private function extract_self_hosted_videos($post) {
        global $wpdb;
        
        $videos = array();
        
        // Get video attachments
        $attachments = $wpdb->get_results($wpdb->prepare("
            SELECT ID, post_title, post_excerpt, post_mime_type, guid
            FROM {$wpdb->posts}
            WHERE post_parent = %d
            AND post_type = 'attachment'
            AND post_mime_type LIKE 'video/%%'
        ", $post->ID));
        
        foreach ($attachments as $attachment) {
            $video_data = $this->get_self_hosted_video_data($attachment, $post);
            if ($video_data) {
                $videos[] = $video_data;
            }
        }
        
        return $videos;
    }
    
    /**
     * Get self-hosted video data
     */
    private function get_self_hosted_video_data($attachment, $post) {
        $video_url = wp_get_attachment_url($attachment->ID);
        if (!$video_url) {
            return null;
        }
        
        $data = array(
            'content_loc' => $video_url,
            'title' => $attachment->post_title ?: ($post->post_title . ' - Video'),
            'description' => $attachment->post_excerpt ?: wp_trim_words($post->post_title, 20),
            'family_friendly' => 'yes',
            'publication_date' => get_the_date('c', $post->ID)
        );
        
        // Get video metadata
        $metadata = wp_get_attachment_metadata($attachment->ID);
        if ($metadata) {
            if (isset($metadata['length'])) {
                $data['duration'] = min(28800, max(1, $metadata['length']));
            } elseif (isset($metadata['length_formatted'])) {
                // Convert formatted time to seconds
                $parts = explode(':', $metadata['length_formatted']);
                $duration = 0;
                if (count($parts) == 3) {
                    $duration = $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
                } elseif (count($parts) == 2) {
                    $duration = $parts[0] * 60 + $parts[1];
                }
                if ($duration > 0) {
                    $data['duration'] = min(28800, $duration);
                }
            }
        }
        
        // Get thumbnail
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        if ($thumbnail_id) {
            $data['thumbnail_loc'] = wp_get_attachment_url($thumbnail_id);
        } else {
            // Try to get video thumbnail from metadata
            if (isset($metadata['image']['src'])) {
                $data['thumbnail_loc'] = $metadata['image']['src'];
            }
        }
        
        // Must have thumbnail
        if (empty($data['thumbnail_loc'])) {
            return null;
        }
        
        return $data;
    }
    
    /**
     * Extract video blocks and shortcodes
     */
    private function extract_video_blocks($content, $post) {
        $videos = array();
        
        // WordPress video block
        if (preg_match_all('/<!-- wp:video.*?(\{.*?\}).*?-->/s', $content, $matches)) {
            foreach ($matches[1] as $json) {
                $data = json_decode($json, true);
                if (isset($data['id'])) {
                    $attachment = get_post($data['id']);
                    if ($attachment) {
                        $video_data = $this->get_self_hosted_video_data($attachment, $post);
                        if ($video_data) {
                            $videos[] = $video_data;
                        }
                    }
                } elseif (isset($data['src'])) {
                    // External video URL
                    if (strpos($data['src'], 'youtube') !== false || strpos($data['src'], 'youtu.be') !== false) {
                        preg_match('/(?:v=|\/)([\w-]{11})/', $data['src'], $id_match);
                        if ($id_match) {
                            $video_data = $this->get_youtube_video_data($id_match[1], $post);
                            if ($video_data) {
                                $videos[] = $video_data;
                            }
                        }
                    } elseif (strpos($data['src'], 'vimeo') !== false) {
                        preg_match('/(\d+)/', $data['src'], $id_match);
                        if ($id_match) {
                            $video_data = $this->get_vimeo_video_data($id_match[1], $post);
                            if ($video_data) {
                                $videos[] = $video_data;
                            }
                        }
                    }
                }
            }
        }
        
        // Video shortcode
        if (preg_match_all('/\[video[^\]]*\]/i', $content, $matches)) {
            foreach ($matches[0] as $shortcode) {
                $atts = shortcode_parse_atts($shortcode);
                if (isset($atts['src']) || isset($atts['mp4']) || isset($atts['webm'])) {
                    $src = $atts['src'] ?? $atts['mp4'] ?? $atts['webm'] ?? '';
                    if ($src) {
                        // Create basic video data
                        $video_data = array(
                            'content_loc' => $src,
                            'title' => $post->post_title . ' - Video',
                            'description' => wp_trim_words($post->post_title, 20),
                            'family_friendly' => 'yes',
                            'publication_date' => get_the_date('c', $post->ID)
                        );
                        
                        // Add thumbnail if available
                        $thumbnail_id = get_post_thumbnail_id($post->ID);
                        if ($thumbnail_id) {
                            $video_data['thumbnail_loc'] = wp_get_attachment_url($thumbnail_id);
                            $videos[] = $video_data;
                        }
                    }
                }
            }
        }
        
        return $videos;
    }
    
    /**
     * Check if URL exists (lightweight HEAD request)
     */
    private function url_exists($url) {
        $response = wp_remote_head($url, array('timeout' => 2));
        if (is_wp_error($response)) {
            return false;
        }
        
        $code = wp_remote_retrieve_response_code($response);
        return $code >= 200 && $code < 400;
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
                WHERE p.post_status = 'publish'
                AND p.post_type IN ('post', 'page')
                AND (
                    p.post_content LIKE '%youtube.com%'
                    OR p.post_content LIKE '%youtu.be%'
                    OR p.post_content LIKE '%vimeo.com%'
                    OR p.post_content LIKE '%wp:video%'
                    OR p.post_content LIKE '%[video%'
                    OR EXISTS (
                        SELECT 1 FROM {$wpdb->posts} a
                        WHERE a.post_parent = p.ID
                        AND a.post_type = 'attachment'
                        AND a.post_mime_type LIKE 'video/%'
                    )
                )
                ORDER BY p.post_modified_gmt DESC
                LIMIT %d OFFSET %d
            ) as subset
        ", $per_page, $offset));
        
        return $last_modified ? date('c', strtotime($last_modified)) : null;
    }
    
    /**
     * Check if provider supports videos
     */
    public function supports_videos() {
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
            WHERE p.post_status = 'publish'
            AND p.post_type IN ('post', 'page')
            AND p.ID > %d
            AND (
                p.post_content LIKE '%youtube.com%'
                OR p.post_content LIKE '%youtu.be%'
                OR p.post_content LIKE '%vimeo.com%'
                OR p.post_content LIKE '%wp:video%'
                OR p.post_content LIKE '%[video%'
                OR EXISTS (
                    SELECT 1 FROM {$wpdb->posts} a
                    WHERE a.post_parent = p.ID
                    AND a.post_type = 'attachment'
                    AND a.post_mime_type LIKE 'video/%'
                )
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
        $videos = $this->extract_videos_from_post($post);
        
        if (empty($videos)) {
            return null;
        }
        
        return array(
            'loc' => get_permalink($post->ID),
            'lastmod' => $post->post_modified_gmt,
            'videos' => $videos
        );
    }
}