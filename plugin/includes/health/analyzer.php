<?php
/**
 * AlmaSEO Health Score Feature - Signal Analyzer
 * 
 * @package AlmaSEO
 * @subpackage Health
 * @since 1.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Calculate health score for a post
 * 
 * @param int $post_id Post ID
 * @return array Score and breakdown
 */
function almaseo_health_calculate($post_id) {
    $post = get_post($post_id);
    if (!$post) {
        return array('score' => 0, 'breakdown' => array());
    }
    
    // Get weights
    $weights = almaseo_health_get_weights();
    
    // Get focus keyword
    $keyword = almaseo_health_get_keyword($post_id);
    
    // Get rendered content (Elementor-aware)
    $content = almaseo_get_rendered_content($post_id);
    $raw_content = $post->post_content;
    $plain_content = wp_strip_all_tags($content);
    
    // Initialize breakdown
    $breakdown = array();
    $total_score = 0;
    
    // Signal 1: Title present
    $breakdown['title'] = almaseo_health_check_title($post);
    if ($breakdown['title']['pass'] && isset($weights['title'])) {
        $total_score += $weights['title'];
    }
    
    // Signal 2: Meta description present
    $breakdown['meta_desc'] = almaseo_health_check_meta_desc($post_id);
    if ($breakdown['meta_desc']['pass'] && isset($weights['meta_desc'])) {
        $total_score += $weights['meta_desc'];
    }
    
    // Signal 3: H1 present and unique
    $breakdown['h1'] = almaseo_health_check_h1($content);
    if ($breakdown['h1']['pass'] && isset($weights['h1'])) {
        $total_score += $weights['h1'];
    }
    
    // Signal 4: Keyword in first 100 words
    $breakdown['kw_intro'] = almaseo_health_check_keyword_intro($plain_content, $keyword);
    if ($breakdown['kw_intro']['pass'] && isset($weights['kw_intro'])) {
        $total_score += $weights['kw_intro'];
    }
    
    // Signal 5: Internal link present
    $breakdown['internal_link'] = almaseo_health_check_internal_link($content);
    if ($breakdown['internal_link']['pass'] && isset($weights['internal_link'])) {
        $total_score += $weights['internal_link'];
    }
    
    // Signal 6: Outbound link present
    $breakdown['outbound_link'] = almaseo_health_check_outbound_link($content);
    if ($breakdown['outbound_link']['pass'] && isset($weights['outbound_link'])) {
        $total_score += $weights['outbound_link'];
    }
    
    // Signal 7: Images have alt text
    $breakdown['image_alt'] = almaseo_health_check_image_alt($content, $post_id);
    if ($breakdown['image_alt']['pass'] && isset($weights['image_alt'])) {
        $total_score += $weights['image_alt'];
    }
    
    // Signal 8: Readability
    $breakdown['readability'] = almaseo_health_check_readability($plain_content);
    if ($breakdown['readability']['pass'] && isset($weights['readability'])) {
        $total_score += $weights['readability'];
    }
    
    // Signal 9: Canonical URL
    $breakdown['canonical'] = almaseo_health_check_canonical($post_id);
    if ($breakdown['canonical']['pass'] && isset($weights['canonical'])) {
        $total_score += $weights['canonical'];
    }
    
    // Signal 10: Robots settings
    $breakdown['robots'] = almaseo_health_check_robots($post_id);
    if ($breakdown['robots']['pass'] && isset($weights['robots'])) {
        $total_score += $weights['robots'];
    }
    
    // Apply filters for custom signals
    $breakdown = apply_filters('almaseo_health_signals', $breakdown, $post_id, $content, $weights);
    
    // Recalculate score in case filters modified breakdown
    $total_score = 0;
    foreach ($breakdown as $signal => $result) {
        if ($result['pass'] && isset($weights[$signal])) {
            $total_score += $weights[$signal];
        }
    }
    
    // Ensure score is 0-100
    $total_score = max(0, min(100, $total_score));
    
    // Fire action
    do_action('almaseo_health_after_calculate', $post_id, $total_score, $breakdown);
    
    return array(
        'score' => $total_score,
        'breakdown' => $breakdown
    );
}

/**
 * Get focus keyword for analysis
 */
function almaseo_health_get_keyword($post_id) {
    // First try to get saved focus keyword
    $keyword = get_post_meta($post_id, ALMASEO_FOCUS_KEYWORD_META, true);
    
    if (empty($keyword)) {
        // Derive from title
        $post = get_post($post_id);
        $title = $post->post_title;
        
        // Tokenize
        $words = str_word_count(strtolower($title), 1);
        
        // Remove stop words
        $stop_words = array('a', 'an', 'and', 'are', 'as', 'at', 'be', 'by', 'for', 
                           'from', 'has', 'he', 'in', 'is', 'it', 'its', 'of', 'on', 
                           'that', 'the', 'to', 'was', 'will', 'with');
        $words = array_diff($words, $stop_words);
        
        // Light stemming (remove common suffixes)
        $stems = array();
        foreach ($words as $word) {
            $stem = preg_replace('/(ing|ed|s)$/', '', $word);
            if (strlen($stem) > 2) {
                $stems[] = $stem;
            }
        }
        
        // Keep top 2-3 unique stems
        $stems = array_unique($stems);
        $keyword = implode(' ', array_slice($stems, 0, 3));
    }
    
    return apply_filters('almaseo_health_keyword', $keyword, $post_id);
}

/**
 * Check if title is present
 */
function almaseo_health_check_title($post) {
    $title = $post->post_title;
    
    if (!empty($title) && strlen($title) > 0) {
        return array(
            'pass' => true,
            'note' => __('Title is present', 'almaseo')
        );
    }
    
    return array(
        'pass' => false,
        'note' => __('Missing title', 'almaseo')
    );
}

/**
 * Check if meta description is present
 */
function almaseo_health_check_meta_desc($post_id) {
    // Check our meta field (correct key: _almaseo_description)
    $meta_desc = get_post_meta($post_id, '_almaseo_description', true);
    
    if (empty($meta_desc)) {
        // Check post excerpt
        $post = get_post($post_id);
        $meta_desc = $post->post_excerpt;
    }
    
    if (!empty($meta_desc) && strlen($meta_desc) > 0) {
        $length = strlen($meta_desc);
        if ($length < 120) {
            return array(
                'pass' => false,
                'note' => __('Meta description too short (< 120 chars)', 'almaseo')
            );
        } elseif ($length > 160) {
            return array(
                'pass' => true,
                'note' => __('Meta description may be truncated (> 160 chars)', 'almaseo')
            );
        }
        return array(
            'pass' => true,
            'note' => __('Meta description is present', 'almaseo')
        );
    }
    
    return array(
        'pass' => false,
        'note' => __('Missing meta description', 'almaseo')
    );
}

/**
 * Check H1 presence and uniqueness
 */
function almaseo_health_check_h1($content) {
    // Parse HTML content for accurate H1 detection
    $parsed = almaseo_parse_html_content($content);
    $h1_count = count($parsed['h1_tags']);
    
    if ($h1_count == 0) {
        return array(
            'pass' => false,
            'note' => __('No H1 heading found', 'almaseo')
        );
    } elseif ($h1_count == 1) {
        return array(
            'pass' => true,
            'note' => sprintf(__('H1 found: "%s"', 'almaseo'), wp_trim_words($parsed['h1_tags'][0], 10))
        );
    } else {
        return array(
            'pass' => false,
            'note' => sprintf(__('Multiple H1 tags found (%d)', 'almaseo'), $h1_count)
        );
    }
}

/**
 * Check keyword in first 100 words
 */
function almaseo_health_check_keyword_intro($plain_content, $keyword) {
    if (empty($keyword)) {
        return array(
            'pass' => false,
            'note' => __('No focus keyword set', 'almaseo')
        );
    }
    
    // Get first 100 words
    $words = str_word_count($plain_content, 1);
    $word_count = count($words);
    
    if ($word_count < 100) {
        return array(
            'pass' => false,
            'note' => __('Content too short to assess; aim for 100+ words', 'almaseo')
        );
    }
    
    $first_100 = implode(' ', array_slice($words, 0, 100));
    $first_100_lower = strtolower($first_100);
    
    // Check for keyword stems
    $keyword_parts = explode(' ', strtolower($keyword));
    foreach ($keyword_parts as $part) {
        if (strlen($part) > 2 && strpos($first_100_lower, $part) !== false) {
            return array(
                'pass' => true,
                'note' => __('Keyword found in introduction', 'almaseo')
            );
        }
    }
    
    return array(
        'pass' => false,
        'note' => __('Keyword not found in first 100 words', 'almaseo')
    );
}

/**
 * Check for internal links
 */
function almaseo_health_check_internal_link($content) {
    $parsed = almaseo_parse_html_content($content);
    $internal_count = 0;
    
    foreach ($parsed['links'] as $link) {
        if (!$link['is_external']) {
            $internal_count++;
        }
    }
    
    if ($internal_count > 0) {
        return array(
            'pass' => true,
            'note' => sprintf(__('%d internal link(s) found', 'almaseo'), $internal_count)
        );
    }
    
    return array(
        'pass' => false,
        'note' => __('No internal links found', 'almaseo')
    );
}

/**
 * Check for outbound links
 */
function almaseo_health_check_outbound_link($content) {
    $parsed = almaseo_parse_html_content($content);
    $outbound_count = 0;
    $nofollow_count = 0;
    
    foreach ($parsed['links'] as $link) {
        if ($link['is_external']) {
            $outbound_count++;
            if ($link['is_nofollow']) {
                $nofollow_count++;
            }
        }
    }
    
    if ($outbound_count > 0) {
        $note = sprintf(__('%d outbound link(s) found', 'almaseo'), $outbound_count);
        if ($nofollow_count > 0) {
            $note .= sprintf(' ' . __('(%d with nofollow)', 'almaseo'), $nofollow_count);
        }
        return array(
            'pass' => true,
            'note' => $note
        );
    }
    
    return array(
        'pass' => false,
        'note' => __('No outbound links found - consider adding external references', 'almaseo')
    );
}

/**
 * Check images have alt text
 */
function almaseo_health_check_image_alt($content, $post_id) {
    // Check content images
    preg_match_all('/<img[^>]+>/i', $content, $img_matches);
    
    $has_images = false;
    $has_alt = false;
    
    if (!empty($img_matches[0])) {
        $has_images = true;
        foreach ($img_matches[0] as $img_tag) {
            if (preg_match('/alt=["\']([^"\']+)["\']/', $img_tag, $alt_match)) {
                if (!empty(trim($alt_match[1]))) {
                    $has_alt = true;
                    break;
                }
            }
        }
    }
    
    // Check featured image
    if (!$has_alt && has_post_thumbnail($post_id)) {
        $has_images = true;
        $thumbnail_id = get_post_thumbnail_id($post_id);
        $alt_text = get_post_meta($thumbnail_id, '_wp_attachment_image_alt', true);
        if (!empty($alt_text)) {
            $has_alt = true;
        }
    }
    
    if (!$has_images) {
        return array(
            'pass' => true,
            'note' => __('No images to check', 'almaseo')
        );
    }
    
    if ($has_alt) {
        return array(
            'pass' => true,
            'note' => __('Images have alt text', 'almaseo')
        );
    }
    
    return array(
        'pass' => false,
        'note' => __('Images missing alt text', 'almaseo')
    );
}

/**
 * Check readability
 */
function almaseo_health_check_readability($plain_content) {
    if (empty($plain_content)) {
        return array(
            'pass' => false,
            'note' => __('No content to analyze', 'almaseo')
        );
    }
    
    // Split into sentences (multibyte safe)
    $sentences = preg_split('/[.!?]+/u', $plain_content, -1, PREG_SPLIT_NO_EMPTY);
    $sentence_count = count($sentences);
    
    // Split into paragraphs
    $paragraphs = preg_split('/\n\n+/', $plain_content, -1, PREG_SPLIT_NO_EMPTY);
    $paragraph_count = count($paragraphs);
    
    // Count words
    $word_count = str_word_count($plain_content);
    
    if ($sentence_count > 0 && $paragraph_count > 0 && $word_count > 0) {
        $avg_sentence_length = $word_count / $sentence_count;
        $avg_paragraph_length = $word_count / $paragraph_count;
        
        // Pass if avg sentence ≤ 24 words OR avg paragraph ≤ 150 words
        if ($avg_sentence_length <= 24 || $avg_paragraph_length <= 150) {
            return array(
                'pass' => true,
                'note' => __('Good readability', 'almaseo')
            );
        } else {
            return array(
                'pass' => false,
                'note' => __('Long sentences or paragraphs detected', 'almaseo')
            );
        }
    }
    
    return array(
        'pass' => false,
        'note' => __('Unable to assess readability', 'almaseo')
    );
}

/**
 * Check canonical URL
 */
function almaseo_health_check_canonical($post_id) {
    $canonical = get_post_meta($post_id, '_almaseo_canonical_url', true);
    
    // Empty is OK (uses default)
    if (empty($canonical)) {
        return array(
            'pass' => true,
            'note' => __('Using default canonical URL', 'almaseo')
        );
    }
    
    // Check if valid URL
    if (filter_var($canonical, FILTER_VALIDATE_URL)) {
        return array(
            'pass' => true,
            'note' => __('Custom canonical URL set', 'almaseo')
        );
    }
    
    return array(
        'pass' => false,
        'note' => __('Invalid canonical URL', 'almaseo')
    );
}

/**
 * Check robots settings with proper precedence
 */
function almaseo_health_check_robots($post_id) {
    $reasons = array();
    $is_blocked = false;
    
    // 1. Check WordPress "Discourage search engines" setting (highest priority)
    if (get_option('blog_public') == '0') {
        $reasons[] = __('WordPress "Discourage search engines" is ON', 'almaseo');
        $is_blocked = true;
    }
    
    // 2. Check robots.txt for site-wide blocks
    $robots_txt_status = almaseo_check_robots_txt_comprehensive();
    if ($robots_txt_status['blocked']) {
        $reasons[] = $robots_txt_status['reason'];
        $is_blocked = true;
    }
    
    // 3. Check post-specific robots meta
    $robots = get_post_meta($post_id, '_almaseo_robots_meta', true);
    if (!empty($robots)) {
        if (strpos($robots, 'noindex') !== false) {
            $reasons[] = __('Post meta set to noindex', 'almaseo');
            $is_blocked = true;
        } elseif (strpos($robots, 'nofollow') !== false) {
            $reasons[] = __('Post meta set to nofollow', 'almaseo');
            // nofollow doesn't block indexing but affects link equity
        }
    }
    
    // 4. Check if specific URL is blocked by robots.txt
    if (!$is_blocked && almaseo_check_robots_txt_block($post_id)) {
        $reasons[] = __('URL blocked by robots.txt pattern', 'almaseo');
        $is_blocked = true;
    }
    
    // Return result based on findings
    if ($is_blocked) {
        return array(
            'pass' => false,
            'note' => '❌ ' . implode('; ', $reasons)
        );
    }
    
    return array(
        'pass' => true,
        'note' => __('✅ Indexable by search engines', 'almaseo')
    );
}

/**
 * Check robots.txt for comprehensive blocking
 */
function almaseo_check_robots_txt_comprehensive() {
    $result = array('blocked' => false, 'reason' => '');
    
    // Try to fetch robots.txt
    $robots_url = home_url('/robots.txt');
    $response = wp_remote_get($robots_url, array('timeout' => 5));
    
    if (is_wp_error($response)) {
        // Try direct file access as fallback
        $robots_path = ABSPATH . 'robots.txt';
        if (file_exists($robots_path)) {
            $robots_content = @file_get_contents($robots_path);
        } else {
            // Check WordPress virtual robots.txt
            ob_start();
            do_action('do_robotstxt');
            $robots_content = ob_get_clean();
        }
    } else {
        $robots_content = wp_remote_retrieve_body($response);
    }
    
    if (!empty($robots_content)) {
        // Parse for blocking rules
        $lines = explode("\n", $robots_content);
        $current_agent = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Check User-agent
            if (stripos($line, 'User-agent:') === 0) {
                $agent = trim(substr($line, 11));
                if ($agent === '*' || stripos($agent, 'Googlebot') !== false) {
                    $current_agent = $agent;
                }
            }
            
            // Check for site-wide block
            if ($current_agent && stripos($line, 'Disallow:') === 0) {
                $path = trim(substr($line, 9));
                if ($path === '/') {
                    $result['blocked'] = true;
                    $result['reason'] = __('robots.txt blocks all pages (Disallow: /)', 'almaseo');
                    break;
                }
            }
        }
    }
    
    return $result;
}

/**
 * Get rendered content with Elementor support
 */
function almaseo_get_rendered_content($post_id) {
    $content_source = 'default';
    $content = '';
    
    // Try Elementor first if available
    if (defined('ELEMENTOR_VERSION') && class_exists('\Elementor\Plugin')) {
        $elementor = \Elementor\Plugin::$instance;
        if ($elementor && $elementor->frontend && $elementor->db->is_built_with_elementor($post_id)) {
            $content = $elementor->frontend->get_builder_content_for_display($post_id);
            $content_source = 'elementor';
        }
    }
    
    // Fallback to standard content processing
    if (empty($content)) {
        $post = get_post($post_id);
        if ($post) {
            $content = apply_filters('the_content', $post->post_content);
            $content_source = 'the_content';
        }
    }
    
    // Debug logging if enabled
    if (defined('ALMASEO_DEV_DEBUG') && ALMASEO_DEV_DEBUG) {
        error_log('[AlmaSEO Health] Content source for post ' . $post_id . ': ' . $content_source);
    }
    
    return $content;
}

/**
 * Parse content with DOMDocument for accurate analysis
 */
function almaseo_parse_html_content($html) {
    if (empty($html)) {
        return array(
            'h1_tags' => array(),
            'links' => array(),
            'images' => array(),
            'text' => ''
        );
    }
    
    // Create DOMDocument and suppress warnings
    $dom = new DOMDocument();
    libxml_use_internal_errors(true);
    $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
    libxml_clear_errors();
    
    $result = array(
        'h1_tags' => array(),
        'links' => array(),
        'images' => array(),
        'text' => ''
    );
    
    // Get H1 tags
    $h1_elements = $dom->getElementsByTagName('h1');
    foreach ($h1_elements as $h1) {
        $result['h1_tags'][] = trim($h1->textContent);
    }
    
    // Get links
    $link_elements = $dom->getElementsByTagName('a');
    $site_host = parse_url(get_site_url(), PHP_URL_HOST);
    $site_domain = preg_replace('/^www\./', '', $site_host);
    
    foreach ($link_elements as $link) {
        $href = $link->getAttribute('href');
        $rel = $link->getAttribute('rel');
        
        // Skip non-http links
        if (strpos($href, 'mailto:') === 0 || 
            strpos($href, 'tel:') === 0 || 
            strpos($href, 'javascript:') === 0 ||
            strpos($href, '#') === 0) {
            continue;
        }
        
        // Make relative URLs absolute
        if (!preg_match('/^https?:\/\//', $href)) {
            $href = trailingslashit(get_site_url()) . ltrim($href, '/');
        }
        
        $link_host = parse_url($href, PHP_URL_HOST);
        $link_domain = preg_replace('/^www\./', '', $link_host);
        
        $link_data = array(
            'url' => $href,
            'text' => trim($link->textContent),
            'rel' => $rel,
            'is_external' => ($link_domain !== $site_domain),
            'is_nofollow' => (stripos($rel, 'nofollow') !== false)
        );
        
        $result['links'][] = $link_data;
    }
    
    // Get images
    $img_elements = $dom->getElementsByTagName('img');
    foreach ($img_elements as $img) {
        $result['images'][] = array(
            'src' => $img->getAttribute('src'),
            'alt' => $img->getAttribute('alt')
        );
    }
    
    // Get plain text
    $result['text'] = trim(strip_tags($html));
    
    return $result;
}

/**
 * Check if URL is blocked by robots.txt
 */
function almaseo_check_robots_txt_block($post_id) {
    // Get the post URL path
    $url = get_permalink($post_id);
    $site_url = get_site_url();
    $path = str_replace($site_url, '', $url);
    
    // Try to read robots.txt
    $robots_txt_path = ABSPATH . 'robots.txt';
    if (!file_exists($robots_txt_path)) {
        // Check if WordPress generates virtual robots.txt
        $robots_content = '';
        ob_start();
        do_action('do_robotstxt');
        $robots_content = ob_get_clean();
        
        if (empty($robots_content)) {
            return false; // No robots.txt to check
        }
    } else {
        $robots_content = @file_get_contents($robots_txt_path);
    }
    
    if (empty($robots_content)) {
        return false;
    }
    
    // Parse robots.txt for Disallow rules
    $lines = explode("\n", $robots_content);
    $current_agent = '';
    
    foreach ($lines as $line) {
        $line = trim($line);
        
        // Skip comments and empty lines
        if (empty($line) || strpos($line, '#') === 0) {
            continue;
        }
        
        // Check for User-agent
        if (stripos($line, 'User-agent:') === 0) {
            $agent = trim(substr($line, 11));
            if ($agent === '*' || stripos($agent, 'Googlebot') !== false) {
                $current_agent = $agent;
            } else {
                $current_agent = '';
            }
            continue;
        }
        
        // Check Disallow rules for current agent
        if ($current_agent && stripos($line, 'Disallow:') === 0) {
            $disallow_path = trim(substr($line, 9));
            if (!empty($disallow_path) && $disallow_path !== '/') {
                // Check if current path matches disallow pattern
                if (strpos($path, $disallow_path) === 0) {
                    return true; // Path is blocked
                }
            }
        }
    }
    
    return false;
}