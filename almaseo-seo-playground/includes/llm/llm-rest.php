<?php
/**
 * AlmaSEO LLM Optimization REST API
 *
 * Provides REST endpoints for LLM optimization analysis.
 * Connects to AlmaSEO SaaS when available, falls back to local heuristics otherwise.
 *
 * @package AlmaSEO
 * @subpackage LLM
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register LLM Analysis REST endpoint
 */
add_action('rest_api_init', function() {
    register_rest_route(
        'almaseo/v1',
        '/llm-analysis',
        array(
            'methods'             => 'GET',
            'callback'            => 'almaseo_llm_analysis_handler',
            'permission_callback' => function(WP_REST_Request $request) {
                return current_user_can('edit_post', (int) $request->get_param('post_id'));
            },
            'args' => array(
                'post_id' => array(
                    'required' => true,
                    'validate_callback' => function ( $val ) { return is_numeric( $val ); },
                    'sanitize_callback' => 'absint',
                ),
                'style' => array(
                    'required' => false,
                    'default' => 'concise',
                    'validate_callback' => function($value) {
                        return in_array($value, array('concise', 'detailed', 'business', 'technical', 'creative', 'academic', 'qa', 'ai_answer'), true);
                    },
                    'sanitize_callback' => 'sanitize_text_field',
                ),
                'include_sections' => array(
                    'required' => false,
                    'default' => false,
                    'sanitize_callback' => 'rest_sanitize_boolean',
                ),
            ),
        )
    );
});

/**
 * LLM Analysis REST endpoint handler
 *
 * @param WP_REST_Request $request The REST request
 * @return WP_REST_Response The analysis response
 */
function almaseo_llm_analysis_handler(WP_REST_Request $request) {
    $post_id = (int) $request->get_param('post_id');
    $style = $request->get_param('style');
    $include_sections = $request->get_param('include_sections');

    // Get post data
    $post = get_post($post_id);
    if (!$post) {
        return new WP_REST_Response(array(
            'error' => 'Post not found',
        ), 404);
    }

    // Check if connected to AlmaSEO SaaS
    $is_connected = function_exists('seo_playground_is_alma_connected')
        ? seo_playground_is_alma_connected()
        : false;

    // Gather post data
    $post_data = almaseo_llm_gather_post_data($post_id, $post);

    // If connected, try to get analysis from AlmaSEO SaaS
    if ($is_connected) {
        $remote_analysis = almaseo_llm_fetch_remote_analysis($post_id, $post_data, $style, $include_sections);

        if ($remote_analysis && !is_wp_error($remote_analysis)) {
            return new WP_REST_Response(array_merge(
                array('connected' => true),
                $remote_analysis
            ), 200);
        }
    }

    // Fall back to local analysis
    $local_analysis = almaseo_llm_generate_local_analysis($post_id, $post_data, $style, $include_sections);

    return new WP_REST_Response(array_merge(
        array('connected' => $is_connected),
        $local_analysis
    ), 200);
}

/**
 * Gather post data for LLM analysis
 *
 * @param int $post_id Post ID
 * @param WP_Post $post Post object
 * @return array Post data
 */
function almaseo_llm_gather_post_data($post_id, $post) {
    // Get SEO meta fields
    $seo_title = get_post_meta($post_id, '_seo_playground_title', true);
    $meta_description = get_post_meta($post_id, '_seo_playground_description', true);
    $schema_type = get_post_meta($post_id, '_seo_playground_schema_type', true);
    $keyword_suggestions = get_post_meta($post_id, '_seo_playground_keyword_suggestions', true);

    // Strip shortcodes before analysis to prevent [shortcode] text from
    // polluting word counts, entity extraction, and paragraph analysis
    $clean_html = strip_shortcodes($post->post_content);

    // Get content
    $content = wp_strip_all_tags($clean_html);
    $word_count = str_word_count($content);

    // Get headings
    preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/i', $clean_html, $matches);
    $heading_count = count($matches[0]);

    return array(
        'post_id'          => $post_id,
        'title'            => $post->post_title,
        'content'          => $content,
        'raw_html'         => $clean_html,
        'word_count'       => $word_count,
        'heading_count'    => $heading_count,
        'seo_title'        => $seo_title,
        'meta_description' => $meta_description,
        'schema_type'      => $schema_type,
        'keyword_suggestions' => $keyword_suggestions,
        'permalink'        => get_permalink($post_id),
    );
}

/**
 * Fetch LLM analysis from AlmaSEO SaaS
 *
 * @param int $post_id Post ID
 * @param array $post_data Post data
 * @param string $style Summary style
 * @param bool $include_sections Whether to include section-level analysis
 * @return array|WP_Error Analysis data or error
 */
function almaseo_llm_fetch_remote_analysis($post_id, $post_data, $style = 'concise', $include_sections = false) {
    // Get API key
    $api_key = get_option('almaseo_api_key', '');
    if (!$api_key) {
        return new WP_Error('no_api_key', 'AlmaSEO API key not found');
    }

    // Prepare request payload
    $payload = array(
        'post_id'          => $post_id,
        'title'            => $post_data['title'],
        'content'          => substr($post_data['content'], 0, 5000), // Limit content size
        'meta_description' => $post_data['meta_description'],
        'schema_type'      => $post_data['schema_type'],
        'url'              => $post_data['permalink'],
        'style'            => $style,
        'include_sections' => $include_sections,
    );

    // Make API request
    $response = wp_remote_post('https://app.almaseo.com/api/v1/llm/analyze', array(
        'headers' => array(
            'Authorization' => 'Bearer ' . $api_key,
            'Content-Type'  => 'application/json',
        ),
        'body'    => json_encode($payload),
        'timeout' => 30,
    ));

    // Check for errors
    if (is_wp_error($response)) {
        return $response;
    }

    $status_code = wp_remote_retrieve_response_code($response);
    if ($status_code !== 200) {
        return new WP_Error('api_error', 'AlmaSEO API returned status ' . $status_code);
    }

    // Parse response
    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data) {
        return new WP_Error('parse_error', 'Could not parse API response');
    }

    return $data;
}

/**
 * Generate local LLM analysis (fallback when not connected)
 *
 * @param int $post_id Post ID
 * @param array $post_data Post data
 * @param string $style Summary style
 * @param bool $include_sections Whether to include section-level analysis
 * @return array Analysis data
 */
function almaseo_llm_generate_local_analysis($post_id, $post_data, $style = 'concise', $include_sections = false) {
    // Extract summary based on style
    $summary = almaseo_llm_generate_summary($post_data['content'], $style);

    // Extract entities with confidence scores
    $entities = almaseo_llm_extract_entities_with_confidence($post_data['content']);

    // Detect ambiguities (very basic)
    $ambiguities = almaseo_llm_detect_ambiguities($post_data['content']);

    // Consistency issues (basic detection)
    $consistency_issues = almaseo_llm_detect_consistency_issues($post_data['content']);

    // Schema hint
    $schema_hint = almaseo_llm_generate_schema_hint($post_data);

    // Evergreen hint (Pro)
    $evergreen_hint = almaseo_llm_generate_evergreen_hint($post_data);

    // Cluster suggestions (basic - get related posts)
    $cluster_suggestions = almaseo_llm_get_cluster_suggestions($post_id, $post_data);

    // --- Tier 1 Content Structure Analysis ---
    $raw_html = isset($post_data['raw_html']) ? $post_data['raw_html'] : '';
    $heading_hierarchy   = almaseo_llm_analyze_heading_hierarchy($raw_html);
    $paragraph_analysis  = almaseo_llm_analyze_paragraphs($raw_html);
    $answer_positioning  = almaseo_llm_analyze_answer_positioning($raw_html);
    $faq_structure       = almaseo_llm_detect_faq_structure($raw_html, $heading_hierarchy);
    $formatting          = almaseo_llm_analyze_formatting($raw_html);
    $summary_detection   = almaseo_llm_detect_summary_tldr($raw_html);

    // Bundle structure data for scoring
    $structure = array(
        'heading_hierarchy'  => $heading_hierarchy,
        'paragraph_analysis' => $paragraph_analysis,
        'answer_positioning' => $answer_positioning,
        'faq_structure'      => $faq_structure,
        'formatting'         => $formatting,
        'summary_detection'  => $summary_detection,
    );

    // Calculate scores (revised to use structure data)
    $llm_score = almaseo_llm_calculate_local_score($post_id, $post_data, $structure);
    $answerability_score = almaseo_llm_calculate_answerability($post_data, $structure);

    // Available summary styles with tier info
    $summary_styles = array(
        array('value' => 'concise', 'label' => 'Concise', 'tier' => 'free'),
        array('value' => 'detailed', 'label' => 'Detailed', 'tier' => 'free'),
        array('value' => 'business', 'label' => 'Business', 'tier' => 'free'),
        array('value' => 'technical', 'label' => 'Technical', 'tier' => 'free'),
        array('value' => 'creative', 'label' => 'Creative', 'tier' => 'free'),
        array('value' => 'academic', 'label' => 'Academic', 'tier' => 'free'),
        array('value' => 'qa', 'label' => 'Q&A Format', 'tier' => 'pro'),
        array('value' => 'ai_answer', 'label' => 'AI Answer', 'tier' => 'pro'),
    );

    $response = array(
        'summary'             => $summary,
        'entities'            => $entities,
        'ambiguities'         => $ambiguities,
        'consistency_issues'  => $consistency_issues,
        'llm_score'           => $llm_score,
        'answerability_score' => $answerability_score,
        'schema_hint'         => $schema_hint,
        'evergreen_hint'      => $evergreen_hint,
        'cluster_suggestions' => $cluster_suggestions,
        'summary_styles'      => $summary_styles,
        // Tier 1 structure analysis
        'heading_hierarchy'   => $heading_hierarchy,
        'paragraph_analysis'  => $paragraph_analysis,
        'answer_positioning'  => $answer_positioning,
        'faq_structure'       => $faq_structure,
        'formatting'          => $formatting,
        'summary_detection'   => $summary_detection,
    );

    // Add section-level analysis if requested
    if ($include_sections) {
        $response['sections'] = almaseo_llm_parse_sections($post_id, $post_data);
    }

    return $response;
}

/**
 * Generate summary based on style
 *
 * @param string $content Content text
 * @param string $style Summary style
 * @return string Generated summary
 */
function almaseo_llm_generate_summary($content, $style = 'concise') {
    $paragraphs = explode("\n\n", $content);
    $first_paragraph = !empty($paragraphs[0]) ? $paragraphs[0] : '';

    switch ($style) {
        case 'concise':
            return wp_trim_words($first_paragraph, 30);

        case 'detailed':
            return wp_trim_words($content, 100);

        case 'business':
            return wp_trim_words($first_paragraph, 40) . ' [Business-focused summary]';

        case 'technical':
            return wp_trim_words($first_paragraph, 40) . ' [Technical summary]';

        case 'creative':
            return wp_trim_words($first_paragraph, 40) . ' [Creative narrative]';

        case 'academic':
            return wp_trim_words($first_paragraph, 50) . ' [Academic summary]';

        case 'qa':
            // Pro feature - Q&A format
            return 'Q: What is this about? A: ' . wp_trim_words($first_paragraph, 40);

        case 'ai_answer':
            // Pro feature - AI answer format
            return 'AI Answer: ' . wp_trim_words($content, 60);

        default:
            return wp_trim_words($first_paragraph, 30);
    }
}

/**
 * Extract entities with confidence scores
 *
 * @param string $content Content text
 * @return array Array of entities with confidence
 */
function almaseo_llm_extract_entities_with_confidence($content) {
    $entities = array();

    // Extract capitalized words (simple entity detection)
    preg_match_all('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*\b/', $content, $matches);

    if (!empty($matches[0])) {
        $word_counts = array_count_values($matches[0]);
        arsort($word_counts);
        $words = array_slice(array_keys($word_counts), 0, 10); // Limit to 10 entities

        foreach ($words as $word) {
            // Calculate confidence based on frequency and length
            $frequency = $word_counts[$word];
            $word_length = strlen($word);

            // Confidence: base 50 + (frequency * 10) + (length bonus)
            $confidence = min(95, 50 + ($frequency * 10) + ($word_length > 10 ? 15 : 0));

            $entities[] = array(
                'name' => $word,
                'type' => 'Entity',
                'confidence' => $confidence,
            );
        }
    }

    return $entities;
}

/**
 * Extract basic entities from content (deprecated, use almaseo_llm_extract_entities_with_confidence)
 *
 * @param string $content Content text
 * @return array Array of entities
 */
function almaseo_llm_extract_basic_entities($content) {
    $entities = array();

    // Extract capitalized words (simple entity detection)
    preg_match_all('/\b[A-Z][a-z]+(?:\s+[A-Z][a-z]+)*\b/', $content, $matches);

    if (!empty($matches[0])) {
        $words = array_unique($matches[0]);
        $words = array_slice($words, 0, 10); // Limit to 10 entities

        foreach ($words as $word) {
            $entities[] = array(
                'name' => $word,
                'type' => 'Entity',
            );
        }
    }

    return $entities;
}

/**
 * Calculate local LLM optimization score
 *
 * @param int $post_id Post ID
 * @param array $post_data Post data
 * @return int Score 0-100
 */
function almaseo_llm_calculate_local_score($post_id, $post_data, $structure = array()) {
    $score = 0;

    // Word count (max 20 points)
    if ($post_data['word_count'] >= 1000) {
        $score += 20;
    } elseif ($post_data['word_count'] >= 500) {
        $score += 15;
    } elseif ($post_data['word_count'] >= 300) {
        $score += 10;
    }

    // Heading hierarchy (max 15 points)
    if (!empty($structure['heading_hierarchy'])) {
        $hh = $structure['heading_hierarchy'];
        if ($hh['hierarchy_valid']) $score += 10;
        if (empty($hh['vague_headings'])) $score += 5;
    } elseif ($post_data['heading_count'] >= 3) {
        $score += 10; // Fallback for old callers without structure
    }

    // SEO meta (max 15 points)
    if (!empty($post_data['seo_title'])) $score += 5;
    if (!empty($post_data['meta_description'])) $score += 5;
    if (!empty($post_data['keyword_suggestions'])) $score += 5;

    // Schema (max 10 points)
    if (!empty($post_data['schema_type']) && $post_data['schema_type'] !== 'none') {
        $score += 10;
    }

    // Formatting (max 10 points)
    if (!empty($structure['formatting'])) {
        $score += min(10, intval($structure['formatting']['formatting_score'] / 10));
    } elseif (preg_match('/<[ou]l[\s>]/i', isset($post_data['raw_html']) ? $post_data['raw_html'] : '')) {
        $score += 5;
    }

    // Paragraph structure (max 10 points)
    if (!empty($structure['paragraph_analysis'])) {
        $pa = $structure['paragraph_analysis'];
        if (!$pa['wall_of_text_detected']) $score += 5;
        if ($pa['avg_words_per_paragraph'] >= 30 && $pa['avg_words_per_paragraph'] <= 150) $score += 5;
    }

    // Answer positioning (max 10 points)
    if (!empty($structure['answer_positioning'])) {
        $ap = $structure['answer_positioning'];
        if (!$ap['starts_with_fluff']) $score += 5;
        if ($ap['has_direct_answer']) $score += 5;
    }

    // Summary/FAQ bonus (max 10 points)
    if (!empty($structure['summary_detection'])) {
        $sd = $structure['summary_detection'];
        if ($sd['has_tldr'] || $sd['has_key_takeaways']) $score += 5;
    }
    if (!empty($structure['faq_structure']) && $structure['faq_structure']['has_faq_structure']) {
        $score += 5;
    }

    return min(100, $score);
}

/**
 * Calculate answerability score
 *
 * @param array $post_data Post data
 * @return int Score 0-100
 */
function almaseo_llm_calculate_answerability($post_data, $structure = array()) {
    $score = 0;

    // Word count adequacy (max 20 points)
    if ($post_data['word_count'] >= 800) {
        $score += 20;
    } elseif ($post_data['word_count'] >= 400) {
        $score += 15;
    } elseif ($post_data['word_count'] >= 200) {
        $score += 10;
    }

    // Heading structure (max 15 points)
    if ($post_data['heading_count'] >= 4) {
        $score += 10;
    } elseif ($post_data['heading_count'] >= 2) {
        $score += 7;
    } elseif ($post_data['heading_count'] >= 1) {
        $score += 3;
    }
    if (!empty($structure['heading_hierarchy']) && $structure['heading_hierarchy']['question_heading_count'] > 0) {
        $score += 5;
    }

    // Meta description (max 10 points)
    if (!empty($post_data['meta_description'])) {
        $score += 10;
    }

    // Title clarity (max 10 points)
    if (!empty($post_data['title']) && strlen($post_data['title']) >= 10) {
        $score += 10;
    }

    // Answer positioning (max 15 points)
    if (!empty($structure['answer_positioning'])) {
        $ap = $structure['answer_positioning'];
        if (!$ap['starts_with_fluff']) $score += 5;
        if ($ap['has_direct_answer']) $score += 10;
    }

    // FAQ structure (max 15 points)
    if (!empty($structure['faq_structure'])) {
        $faq = $structure['faq_structure'];
        if ($faq['has_faq_structure']) $score += 10;
        if ($faq['question_heading_ratio'] > 0.3) $score += 5;
    }

    // Formatting (max 10 points)
    if (!empty($structure['formatting'])) {
        $fmt = $structure['formatting'];
        if ($fmt['has_lists']) $score += 5;
        if ($fmt['has_tables']) $score += 5;
    }

    // Summary/TL;DR (max 5 points)
    if (!empty($structure['summary_detection'])) {
        $sd = $structure['summary_detection'];
        if ($sd['has_tldr'] || $sd['has_key_takeaways'] || $sd['has_table_of_contents']) $score += 5;
    }

    return min(100, $score);
}

/**
 * Detect ambiguities in content
 *
 * @param string $content Content text
 * @return array Array of ambiguities
 */
function almaseo_llm_detect_ambiguities($content) {
    $ambiguities = array();

    // Look for vague pronouns
    $vague_patterns = array(
        '/\b(it|this|that|these|those)\s+(?:changed|affected|helped|improved)/i' => 'Unclear referent',
        '/\bmany\s+(?:people|users|customers)/i' => 'Vague quantity',
        '/\bsome\s+(?:people|users|experts)/i' => 'Vague quantity',
    );

    foreach ($vague_patterns as $pattern => $note) {
        if (preg_match($pattern, $content, $match, PREG_OFFSET_CAPTURE)) {
            $excerpt = substr($content, max(0, $match[0][1] - 20), 60);
            $ambiguities[] = array(
                'excerpt' => '...' . trim($excerpt) . '...',
                'note'    => $note,
            );
        }
    }

    return array_slice($ambiguities, 0, 3); // Limit to 3
}

/**
 * Detect consistency issues
 *
 * @param string $content Content text
 * @return array Array of consistency issues
 */
function almaseo_llm_detect_consistency_issues($content) {
    $issues = array();

    // Look for conflicting numbers/percentages
    if (preg_match_all('/(\d+)%/', $content, $matches)) {
        $percentages = array_unique($matches[1]);
        if (count($percentages) > 3) {
            $issues[] = array(
                'excerpt' => 'Multiple different percentages found',
                'note'    => 'Verify consistency of statistics',
            );
        }
    }

    return array_slice($issues, 0, 3); // Limit to 3
}

/**
 * Generate schema hint
 *
 * @param array $post_data Post data
 * @return string Schema hint
 */
function almaseo_llm_generate_schema_hint($post_data) {
    $hints = array();

    // Check if Advanced Schema is available and enabled
    if (function_exists('almaseo_feature_available') && almaseo_feature_available('schema_advanced')) {
        $adv_settings = get_option('almaseo_schema_advanced_settings', array('enabled' => false));

        if (!empty($adv_settings['enabled'])) {
            // Check what advanced schema features are active
            $post_id = $post_data['post_id'];
            $primary_type = get_post_meta($post_id, '_almaseo_schema_primary_type', true);

            if (!empty($primary_type)) {
                $hints[] = 'Advanced Schema enabled: ' . $primary_type . ' on this post';
            } elseif (get_post_meta($post_id, '_almaseo_schema_is_faqpage', true)) {
                $hints[] = 'Advanced Schema enabled: FAQPage on this post';
            } elseif (get_post_meta($post_id, '_almaseo_schema_is_howto', true)) {
                $hints[] = 'Advanced Schema enabled: HowTo on this post';
            }

            // Check general breadcrumbs feature
            $breadcrumb_settings = get_option('almaseo_breadcrumbs_settings', array('enabled' => false, 'schema_output' => true));
            if (!empty($breadcrumb_settings['enabled']) && !empty($breadcrumb_settings['schema_output'])) {
                $hints[] = 'BreadcrumbList schema available via shortcode';
            }

            if (!empty($hints)) {
                return implode('. ', $hints);
            }
        }
    }

    // If no schema, suggest based on content
    if (empty($post_data['schema_type']) || $post_data['schema_type'] === 'none') {
        // Check for FAQ-style content
        if (preg_match_all('/<h[2-6][^>]*>.*?\?.*?<\/h[2-6]>/i', get_post($post_data['post_id'])->post_content) >= 2) {
            return 'Consider adding FAQ schema - detected question-style headings';
        }

        // Check for how-to content
        if (preg_match('/\bhow\s+to\b/i', $post_data['title'])) {
            return 'Consider adding HowTo schema for step-by-step content';
        }

        return 'Consider adding Article schema for better LLM understanding';
    }

    return '';
}

/**
 * Generate evergreen hint (Pro)
 *
 * @param array $post_data Post data
 * @return string Evergreen hint
 * @since 6.5.0
 */
function almaseo_llm_generate_evergreen_hint($post_data) {
    // Check if Evergreen Advanced is available
    if (!function_exists('almaseo_feature_available') || !almaseo_feature_available('evergreen_advanced')) {
        return '';
    }

    $adv_settings = get_option('almaseo_evergreen_advanced_settings', array('enabled' => false));

    if (empty($adv_settings['enabled'])) {
        return '';
    }

    $post_id = $post_data['post_id'];

    // Get advanced evergreen scores
    $refresh_score = (int) get_post_meta($post_id, '_almaseo_evergreen_refresh_score', true);
    $ai_freshness_score = (int) get_post_meta($post_id, '_almaseo_evergreen_ai_freshness_score', true);
    $risk_level = get_post_meta($post_id, '_almaseo_evergreen_risk_level', true);

    // If scores haven't been calculated yet
    if ($refresh_score === 0 && $ai_freshness_score === 0 && empty($risk_level)) {
        return 'Evergreen Advanced enabled. Run analysis to get freshness scores.';
    }

    $hints = array();

    // Risk level hint
    if ($risk_level === 'high') {
        $hints[] = 'HIGH RISK - Content urgently needs refresh (Score: ' . $refresh_score . ')';
    } elseif ($risk_level === 'medium') {
        $hints[] = 'MEDIUM RISK - Monitor this content closely (Score: ' . $refresh_score . ')';
    } elseif ($risk_level === 'low') {
        $hints[] = 'LOW RISK - Content is performing well (Score: ' . $refresh_score . ')';
    }

    // AI Freshness specific hints
    if ($ai_freshness_score >= 75) {
        $hints[] = 'AI Freshness Critical (' . $ai_freshness_score . '/100) - Content appears outdated to AI models';
    } elseif ($ai_freshness_score >= 50) {
        $hints[] = 'AI Freshness Needs Attention (' . $ai_freshness_score . '/100) - Consider updating for better AI understanding';
    } elseif ($ai_freshness_score > 0) {
        $hints[] = 'AI Freshness Good (' . $ai_freshness_score . '/100)';
    }

    // Last refresh reason (if available)
    $last_reason = get_post_meta($post_id, '_almaseo_evergreen_last_refresh_reason', true);
    if (!empty($last_reason)) {
        $hints[] = 'Last refresh: ' . $last_reason;
    }

    // Manual priority override (if set)
    $manual_priority = (int) get_post_meta($post_id, '_almaseo_evergreen_manual_priority', true);
    if ($manual_priority > 0) {
        $priority_labels = array(1 => 'Low', 2 => 'Medium', 3 => 'High');
        if (isset($priority_labels[$manual_priority])) {
            $hints[] = 'Manual Priority: ' . $priority_labels[$manual_priority];
        }
    }

    if (!empty($hints)) {
        return implode('. ', $hints);
    }

    return '';
}

/**
 * Get cluster suggestions (related posts for internal linking)
 *
 * @param int $post_id Post ID
 * @param array $post_data Post data
 * @return array Array of suggested posts
 */
function almaseo_llm_get_cluster_suggestions($post_id, $post_data) {
    $suggestions = array();

    // Get posts from same category
    $categories = wp_get_post_categories($post_id);

    if (!empty($categories)) {
        $related_posts = get_posts(array(
            'category__in'   => $categories,
            'post__not_in'   => array($post_id),
            'posts_per_page' => 3,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ));

        foreach ($related_posts as $related_post) {
            $suggestions[] = array(
                'post_id' => $related_post->ID,
                'title'   => $related_post->post_title,
                'url'     => get_permalink($related_post->ID),
            );
        }
    }

    return $suggestions;
}

/**
 * Parse content into sections with analysis
 *
 * @param int $post_id Post ID
 * @param array $post_data Post data
 * @return array Array of sections with scores and flags
 */
function almaseo_llm_parse_sections($post_id, $post_data) {
    $sections = array();
    $post = get_post($post_id);
    $full_content = $post->post_content;
    $plain_content = wp_strip_all_tags($full_content);

    // Section 1: Title
    $sections[] = array(
        'type' => 'title',
        'heading' => $post_data['title'],
        'excerpt' => substr($post_data['title'], 0, 100),
        'word_count' => str_word_count($post_data['title']),
        'clarity_score' => almaseo_llm_calculate_section_clarity($post_data['title']),
        'llm_readiness' => almaseo_llm_calculate_section_llm_readiness($post_data['title']),
        'answerability' => 0, // Title doesn't answer questions
        'flags' => almaseo_llm_detect_section_flags($post_data['title'], true),
    );

    // Section 2: Introduction (first paragraph before first heading)
    preg_match('/^(.*?)(?=<h[2-6]|$)/is', $full_content, $intro_match);
    $intro_html = isset($intro_match[1]) ? $intro_match[1] : '';
    $intro_text = wp_strip_all_tags($intro_html);

    if (!empty($intro_text)) {
        $sections[] = array(
            'type' => 'intro',
            'heading' => 'Introduction',
            'excerpt' => wp_trim_words($intro_text, 20),
            'word_count' => str_word_count($intro_text),
            'clarity_score' => almaseo_llm_calculate_section_clarity($intro_text),
            'llm_readiness' => almaseo_llm_calculate_section_llm_readiness($intro_text),
            'answerability' => almaseo_llm_calculate_section_answerability($intro_text),
            'flags' => almaseo_llm_detect_section_flags($intro_text),
        );
    }

    // Section 3+: H2 sections
    preg_match_all('/<h2[^>]*>(.*?)<\/h2>(.*?)(?=<h2|$)/is', $full_content, $h2_matches, PREG_SET_ORDER);

    foreach ($h2_matches as $h2_match) {
        $heading = wp_strip_all_tags($h2_match[1]);
        $section_content = wp_strip_all_tags($h2_match[2]);

        $sections[] = array(
            'type' => 'h2_section',
            'heading' => $heading,
            'excerpt' => wp_trim_words($section_content, 20),
            'word_count' => str_word_count($section_content),
            'clarity_score' => almaseo_llm_calculate_section_clarity($section_content),
            'llm_readiness' => almaseo_llm_calculate_section_llm_readiness($section_content),
            'answerability' => almaseo_llm_calculate_section_answerability($section_content),
            'flags' => almaseo_llm_detect_section_flags($section_content),
        );
    }

    // Lists (ul/ol)
    preg_match_all('/<[ou]l[^>]*>(.*?)<\/[ou]l>/is', $full_content, $list_matches);
    if (!empty($list_matches[0])) {
        $all_lists_text = wp_strip_all_tags(implode(' ', $list_matches[0]));

        $sections[] = array(
            'type' => 'lists',
            'heading' => 'Lists',
            'excerpt' => wp_trim_words($all_lists_text, 15),
            'word_count' => str_word_count($all_lists_text),
            'clarity_score' => almaseo_llm_calculate_section_clarity($all_lists_text),
            'llm_readiness' => 85, // Lists are generally good for LLMs
            'answerability' => 80,
            'flags' => array(),
        );
    }

    // Conclusion (last paragraph if it contains conclusion-like words)
    $paragraphs = explode("\n\n", $plain_content);
    $last_paragraph = !empty($paragraphs) ? end($paragraphs) : '';

    if (preg_match('/\b(conclusion|summary|finally|in summary|to conclude)\b/i', $last_paragraph)) {
        $sections[] = array(
            'type' => 'conclusion',
            'heading' => 'Conclusion',
            'excerpt' => wp_trim_words($last_paragraph, 20),
            'word_count' => str_word_count($last_paragraph),
            'clarity_score' => almaseo_llm_calculate_section_clarity($last_paragraph),
            'llm_readiness' => almaseo_llm_calculate_section_llm_readiness($last_paragraph),
            'answerability' => almaseo_llm_calculate_section_answerability($last_paragraph),
            'flags' => almaseo_llm_detect_section_flags($last_paragraph),
        );
    }

    return $sections;
}

/**
 * Calculate clarity score for a section
 *
 * @param string $text Section text
 * @return int Score 0-100
 */
function almaseo_llm_calculate_section_clarity($text) {
    $score = 70; // Base score

    $word_count = str_word_count($text);

    // Penalize very short sections
    if ($word_count < 20) {
        $score -= 20;
    }

    // Penalize vague words
    if (preg_match('/\b(maybe|perhaps|possibly|might|could)\b/i', $text)) {
        $score -= 10;
    }

    // Bonus for specific numbers/data
    if (preg_match('/\b\d+\b/', $text)) {
        $score += 10;
    }

    return max(0, min(100, $score));
}

/**
 * Calculate LLM readiness score for a section
 *
 * @param string $text Section text
 * @return int Score 0-100
 */
function almaseo_llm_calculate_section_llm_readiness($text) {
    $score = 60; // Base score

    $word_count = str_word_count($text);

    // Good length
    if ($word_count >= 50 && $word_count <= 200) {
        $score += 20;
    } elseif ($word_count >= 30) {
        $score += 10;
    }

    // Has structure indicators
    if (preg_match('/\b(first|second|third|finally|additionally|furthermore)\b/i', $text)) {
        $score += 10;
    }

    // Has specific terms
    if (preg_match('/\b[A-Z][a-z]+\b/', $text)) {
        $score += 10;
    }

    return max(0, min(100, $score));
}

/**
 * Calculate answerability score for a section
 *
 * @param string $text Section text
 * @return int Score 0-100
 */
function almaseo_llm_calculate_section_answerability($text) {
    $score = 50; // Base score

    $word_count = str_word_count($text);

    // Needs substance to answer questions
    if ($word_count >= 100) {
        $score += 30;
    } elseif ($word_count >= 50) {
        $score += 20;
    } elseif ($word_count >= 25) {
        $score += 10;
    } else {
        $score -= 20;
    }

    // Has question-answer indicators
    if (preg_match('/\b(because|therefore|thus|as a result|which means)\b/i', $text)) {
        $score += 15;
    }

    // Has examples
    if (preg_match('/\b(for example|such as|like|including)\b/i', $text)) {
        $score += 10;
    }

    return max(0, min(100, $score));
}

/**
 * Detect section-level flags
 *
 * @param string $text Section text
 * @param bool $is_title Whether this is a title section
 * @return array Array of flag strings
 */
function almaseo_llm_detect_section_flags($text, $is_title = false) {
    $flags = array();

    $word_count = str_word_count($text);

    // Thin content
    if (!$is_title && $word_count < 30) {
        $flags[] = 'thin_content';
    }

    // Ambiguous pronouns
    if (preg_match('/\b(it|this|that|these|those)\s+(?:is|are|was|were|can|will)/i', $text)) {
        $flags[] = 'ambiguous_pronouns';
    }

    // Vague language
    if (preg_match('/\b(many|some|few|several|various)\b/i', $text)) {
        $flags[] = 'vague_language';
    }

    // Missing context
    if (!$is_title && $word_count > 50 && !preg_match('/\b[A-Z][a-z]+\b/', $text)) {
        $flags[] = 'missing_specifics';
    }

    // Wall of text (paragraph over 300 words)
    if (!$is_title && $word_count > 300) {
        $flags[] = 'wall_of_text';
    }

    return $flags;
}

/**
 * =========================================================================
 * Tier 1 Content Structure Analysis Functions
 * Ported from analysis/content_structure.py (AlmaSEO dashboard)
 * =========================================================================
 */

/**
 * Analyze heading hierarchy, vague headings, and question headings
 *
 * @param string $raw_html Raw post HTML content
 * @return array Heading hierarchy analysis
 */
function almaseo_llm_analyze_heading_hierarchy($raw_html) {
    $vague_headings_list = array(
        'introduction', 'intro', 'conclusion', 'summary', 'overview',
        'more info', 'more information', 'learn more', 'read more',
        'about', 'about us', 'details', 'information', 'info',
        'welcome', 'home', 'main', 'content', 'section',
        'click here', 'continue', 'next', 'previous',
        'get started', 'start here', 'begin',
    );

    preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/is', $raw_html, $matches, PREG_SET_ORDER);

    $h1_count = 0;
    $total_headings = count($matches);
    $hierarchy_valid = true;
    $hierarchy_issues = array();
    $vague_headings = array();
    $question_headings = array();
    $prev_level = 0;

    foreach ($matches as $match) {
        $level = intval($match[1]);
        $text = trim(wp_strip_all_tags($match[2]));

        if ($level === 1) {
            $h1_count++;
        }

        // Check hierarchy: flag if level skips (e.g., H1 directly to H3)
        if ($prev_level > 0 && $level > $prev_level + 1) {
            $hierarchy_valid = false;
            $hierarchy_issues[] = 'H' . $level . ' follows H' . $prev_level . ' (skipped H' . ($prev_level + 1) . ')';
        }
        $prev_level = $level;

        // Check for vague headings
        $text_lower = strtolower($text);
        foreach ($vague_headings_list as $vague) {
            if ($text_lower === $vague || strpos($text_lower, $vague) === 0) {
                $vague_headings[] = $text;
                break;
            }
        }

        // Check for question headings
        if (preg_match('/\?\s*$/', $text) || preg_match('/^(what|how|why|when|where|who|which|can|do|does|is|are|should)\b/i', $text)) {
            $question_headings[] = $text;
        }
    }

    // Additional hierarchy checks
    if ($h1_count > 1) {
        $hierarchy_valid = false;
        $hierarchy_issues[] = 'Multiple H1 tags found (' . $h1_count . ')';
    }
    if ($h1_count === 0 && $total_headings > 0) {
        $hierarchy_valid = false;
        $hierarchy_issues[] = 'No H1 tag found';
    }

    return array(
        'h1_count'               => $h1_count,
        'total_headings'         => $total_headings,
        'hierarchy_valid'        => $hierarchy_valid,
        'hierarchy_issues'       => $hierarchy_issues,
        'vague_headings'         => $vague_headings,
        'question_headings'      => $question_headings,
        'question_heading_count' => count($question_headings),
    );
}

/**
 * Analyze paragraph structure and detect walls of text
 *
 * @param string $raw_html Raw post HTML content
 * @return array Paragraph analysis
 */
function almaseo_llm_analyze_paragraphs($raw_html) {
    preg_match_all('/<p[^>]*>(.*?)<\/p>/is', $raw_html, $matches);

    $total = 0;
    $total_words = 0;
    $too_long = 0;
    $optimal = 0;
    $too_short = 0;
    $wall_detected = false;
    $longest = 0;

    foreach ($matches[1] as $inner) {
        $text = trim(wp_strip_all_tags($inner));
        if (strlen($text) <= 20) {
            continue; // Skip UI elements, captions, etc.
        }

        $wc = str_word_count($text);
        $total++;
        $total_words += $wc;

        if ($wc > 300) {
            $wall_detected = true;
        }

        if ($wc > 150) {
            $too_long++;
        } elseif ($wc < 30) {
            $too_short++;
        } else {
            $optimal++;
        }

        if ($wc > $longest) {
            $longest = $wc;
        }
    }

    // Fallback for classic editor content without <p> tags
    if ($total === 0) {
        $plain = wp_strip_all_tags($raw_html);
        $paragraphs = preg_split('/\n\s*\n/', $plain);
        foreach ($paragraphs as $para) {
            $text = trim($para);
            if (strlen($text) <= 20) continue;
            $wc = str_word_count($text);
            $total++;
            $total_words += $wc;
            if ($wc > 300) $wall_detected = true;
            if ($wc > 150) $too_long++;
            elseif ($wc < 30) $too_short++;
            else $optimal++;
            if ($wc > $longest) $longest = $wc;
        }
    }

    return array(
        'total_paragraphs'        => $total,
        'avg_words_per_paragraph' => $total > 0 ? round($total_words / $total, 1) : 0,
        'paragraphs_too_long'     => $too_long,
        'paragraphs_optimal'      => $optimal,
        'paragraphs_too_short'    => $too_short,
        'wall_of_text_detected'   => $wall_detected,
        'longest_paragraph_words' => $longest,
    );
}

/**
 * Analyze answer positioning in the opening content
 *
 * @param string $raw_html Raw post HTML content
 * @return array Answer positioning analysis
 */
function almaseo_llm_analyze_answer_positioning($raw_html) {
    $plain = wp_strip_all_tags($raw_html);
    $words = preg_split('/\s+/', trim($plain));
    $first_100 = implode(' ', array_slice($words, 0, 100));
    $first_100_lower = strtolower($first_100);

    // Fluff patterns (test against start of content)
    $fluff_patterns = array(
        '/^welcome\s+to\b/i',
        '/^at\s+\w+\s*,?\s*(we|our)\b/i',
        '/^thank\s+you\s+for\b/i',
        '/^hello\b/i',
        '/^hi\b/i',
        '/^greetings\b/i',
        '/^we\s+are\s+(pleased|happy|excited|thrilled)\b/i',
        '/^(our|the)\s+company\b/i',
        '/^since\s+\d{4}\b/i',
        '/^for\s+(over|more\s+than)\s+\d+\s+years\b/i',
    );

    // Direct answer patterns
    $answer_patterns = array(
        '/^(the\s+)?(answer|solution|cost|price|time|result)\s+is\b/i',
        '/^(yes|no)\s*[,.]/i',
        '/^(it\s+)?(takes?|costs?|requires?)\s+/i',
        '/^\d+\s*(hours?|days?|weeks?|months?|dollars?|\$|percent|%)/i',
        '/^(you\s+)?(can|should|must|need\s+to|will)\b/i',
        '/^(this|that|it)\s+(is|means|requires)\b/i',
        '/^(there\s+are|here\s+are)\s+\d+\b/i',
        '/^(the\s+)?(best|top|most|primary|main)\b/i',
    );

    // Question patterns
    $question_patterns = array(
        '/\?\s*$/',
        '/^(what|how|why|when|where|who|which|can|do|does|is|are|should)\b/i',
    );

    $starts_with_fluff = false;
    $fluff_detected = '';
    $has_direct_answer = false;
    $starts_with_question = false;

    foreach ($fluff_patterns as $pattern) {
        if (preg_match($pattern, $first_100, $fluff_match)) {
            $starts_with_fluff = true;
            $fluff_detected = $fluff_match[0];
            break;
        }
    }

    foreach ($answer_patterns as $pattern) {
        if (preg_match($pattern, $first_100)) {
            $has_direct_answer = true;
            break;
        }
    }

    foreach ($question_patterns as $pattern) {
        if (preg_match($pattern, $first_100)) {
            $starts_with_question = true;
            break;
        }
    }

    return array(
        'starts_with_fluff'    => $starts_with_fluff,
        'fluff_detected'       => $fluff_detected,
        'has_direct_answer'    => $has_direct_answer,
        'starts_with_question' => $starts_with_question,
    );
}

/**
 * Detect FAQ-style content structure from question headings
 *
 * @param string $raw_html Raw post HTML content
 * @param array $heading_hierarchy Result from almaseo_llm_analyze_heading_hierarchy()
 * @return array FAQ structure analysis
 */
function almaseo_llm_detect_faq_structure($raw_html, $heading_hierarchy) {
    $question_count = $heading_hierarchy['question_heading_count'];
    $total = $heading_hierarchy['total_headings'];
    $ratio = $total > 0 ? round($question_count / $total, 2) : 0;

    return array(
        'has_faq_structure'      => $question_count >= 2,
        'question_heading_count' => $question_count,
        'question_heading_ratio' => $ratio,
        'faq_questions'          => array_slice($heading_hierarchy['question_headings'], 0, 10),
    );
}

/**
 * Analyze formatting elements (lists, tables, blockquotes)
 *
 * @param string $raw_html Raw post HTML content
 * @return array Formatting analysis with score
 */
function almaseo_llm_analyze_formatting($raw_html) {
    preg_match_all('/<ul[\s>]/i', $raw_html, $ul_matches);
    preg_match_all('/<ol[\s>]/i', $raw_html, $ol_matches);
    preg_match_all('/<li[\s>]/i', $raw_html, $li_matches);
    preg_match_all('/<blockquote[\s>]/i', $raw_html, $bq_matches);

    $ul_count = count($ul_matches[0]);
    $ol_count = count($ol_matches[0]);
    $li_count = count($li_matches[0]);
    $bq_count = count($bq_matches[0]);

    // Count tables with >1 row (exclude layout tables)
    $table_count = 0;
    preg_match_all('/<table[^>]*>(.*?)<\/table>/is', $raw_html, $table_matches);
    foreach ($table_matches[1] as $table_inner) {
        preg_match_all('/<tr[\s>]/i', $table_inner, $rows);
        if (count($rows[0]) > 1) {
            $table_count++;
        }
    }

    $has_lists = ($ul_count + $ol_count) > 0;
    $has_tables = $table_count > 0;

    // Formatting score (0-100)
    $score = 0;
    if ($has_lists) $score += 30;
    if ($li_count >= 10) $score += 30;
    elseif ($li_count >= 5) $score += 20;
    if ($has_tables) $score += 20;
    if ($bq_count >= 1) $score += 10;
    $score = min(100, $score);

    return array(
        'ul_count'         => $ul_count,
        'ol_count'         => $ol_count,
        'li_count'         => $li_count,
        'table_count'      => $table_count,
        'blockquote_count' => $bq_count,
        'has_lists'        => $has_lists,
        'has_tables'       => $has_tables,
        'formatting_score' => $score,
    );
}

/**
 * Detect TL;DR, key takeaways, and table of contents
 *
 * @param string $raw_html Raw post HTML content
 * @return array Summary/TL;DR detection
 */
function almaseo_llm_detect_summary_tldr($raw_html) {
    // Extract all headings with their position
    preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/is', $raw_html, $matches, PREG_SET_ORDER);

    $tldr_patterns = array(
        '/\btl;?dr\b/i',
        '/\bkey\s+takeaways?\b/i',
        '/\bquick\s+summary\b/i',
        '/\bin\s+short\b/i',
        '/\bbottom\s+line\b/i',
        '/\bkey\s+points?\b/i',
        '/\bquick\s+answer\b/i',
        '/\bthe\s+short\s+answer\b/i',
    );

    $toc_patterns = array(
        '/\btable\s+of\s+contents?\b/i',
        '/\bin\s+this\s+(article|post|guide)\b/i',
        '/\bwhat\s+we\'?ll\s+cover\b/i',
    );

    $has_tldr = false;
    $has_key_takeaways = false;
    $has_toc = false;
    $summary_location = 'none';
    $total = count($matches);

    foreach ($matches as $i => $match) {
        $text = trim(wp_strip_all_tags($match[2]));

        // Check TL;DR patterns
        foreach ($tldr_patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $has_tldr = true;
                if (preg_match('/takeaway|key\s+point/i', $text)) {
                    $has_key_takeaways = true;
                }
                // Determine position
                if ($total > 0) {
                    if ($i < $total / 3) {
                        $summary_location = 'top';
                    } elseif ($i > 2 * $total / 3) {
                        $summary_location = 'bottom';
                    } else {
                        $summary_location = 'middle';
                    }
                }
                break 2; // Found one, stop looking
            }
        }

        // Check TOC patterns
        foreach ($toc_patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                $has_toc = true;
                break;
            }
        }
    }

    // Also check for TOC via jump links (≥3 anchor links in content)
    if (!$has_toc) {
        preg_match_all('/href\s*=\s*["\']#[^"\']+["\']/i', $raw_html, $anchor_matches);
        if (count($anchor_matches[0]) >= 3) {
            $has_toc = true;
        }
    }

    return array(
        'has_tldr'               => $has_tldr,
        'has_key_takeaways'      => $has_key_takeaways,
        'has_table_of_contents'  => $has_toc,
        'summary_location'       => $summary_location,
    );
}
