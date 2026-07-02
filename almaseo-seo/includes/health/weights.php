<?php
/**
 * AlmaSEO Health Score Feature - Weights Configuration
 * 
 * @package AlmaSEO
 * @subpackage Health
 * @since 1.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get default weights for health signals
 * 
 * @return array Signal weights (must sum to 100)
 */
function almaseo_health_default_weights() {
    return array(
        'title'          => 20,  // Title present
        'meta_desc'      => 15,  // Meta description present
        'h1'             => 10,  // H1 present and unique
        'kw_intro'       => 10,  // Keyword in first 100 words
        'internal_link'  => 10,  // Internal link present
        'outbound_link'  => 10,  // Outbound link present
        'image_alt'      => 10,  // Images have alt text
        'readability'    => 10,  // Content readability
        'canonical'      => 3,   // Canonical set
        'robots'         => 2,   // Robots settings OK
    );
}

/**
 * Get active weights with filter applied
 * 
 * @return array Active signal weights
 */
function almaseo_health_get_weights() {
    $weights = almaseo_health_default_weights();
    
    // Apply filter for customization
    $weights = apply_filters('almaseo_health_weights', $weights);
    
    // Ensure weights are valid
    $weights = array_map('intval', $weights);
    $weights = array_filter($weights, function($w) { return $w >= 0; });
    
    // Normalize if sum != 100
    $sum = array_sum($weights);
    if ($sum > 0 && $sum != 100) {
        $factor = 100 / $sum;
        foreach ($weights as $key => &$weight) {
            $weight = round($weight * $factor);
        }
    }
    
    return $weights;
}

/**
 * Get signal labels for display
 * 
 * @return array Signal labels
 */
function almaseo_health_get_signal_labels() {
    return array(
        'title'          => __('Title', 'almaseo-seo-playground'),
        'meta_desc'      => __('Meta Description', 'almaseo-seo-playground'),
        'h1'             => __('H1 Heading', 'almaseo-seo-playground'),
        'kw_intro'       => __('Keyword in Introduction', 'almaseo-seo-playground'),
        'internal_link'  => __('Internal Link', 'almaseo-seo-playground'),
        'outbound_link'  => __('Outbound Link', 'almaseo-seo-playground'),
        'image_alt'      => __('Image Alt Text', 'almaseo-seo-playground'),
        'readability'    => __('Readability', 'almaseo-seo-playground'),
        'canonical'      => __('Canonical URL', 'almaseo-seo-playground'),
        'robots'         => __('Robots Settings', 'almaseo-seo-playground'),
    );
}

/**
 * Get signal help text
 * 
 * @return array Signal help descriptions
 */
function almaseo_health_get_signal_help() {
    return array(
        'title' => __('A compelling title helps search engines and users understand your content.', 'almaseo-seo-playground'),
        'meta_desc' => __('Meta descriptions provide a summary for search engine results.', 'almaseo-seo-playground'),
        'h1' => __('One unique H1 heading provides clear structure for your content.', 'almaseo-seo-playground'),
        'kw_intro' => __('Including keywords early signals relevance to search engines.', 'almaseo-seo-playground'),
        'internal_link' => __('Internal links help distribute page authority and improve navigation.', 'almaseo-seo-playground'),
        'outbound_link' => __('Quality outbound links add credibility and context.', 'almaseo-seo-playground'),
        'image_alt' => __('Alt text improves accessibility and helps search engines understand images.', 'almaseo-seo-playground'),
        'readability' => __('Clear, concise writing improves user experience and engagement.', 'almaseo-seo-playground'),
        'canonical' => __('Canonical URLs prevent duplicate content issues.', 'almaseo-seo-playground'),
        'robots' => __('Proper robots settings ensure your content can be indexed.', 'almaseo-seo-playground'),
    );
}