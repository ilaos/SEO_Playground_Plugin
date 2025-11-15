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
        'title'          => __('Title', 'almaseo'),
        'meta_desc'      => __('Meta Description', 'almaseo'),
        'h1'             => __('H1 Heading', 'almaseo'),
        'kw_intro'       => __('Keyword in Introduction', 'almaseo'),
        'internal_link'  => __('Internal Link', 'almaseo'),
        'outbound_link'  => __('Outbound Link', 'almaseo'),
        'image_alt'      => __('Image Alt Text', 'almaseo'),
        'readability'    => __('Readability', 'almaseo'),
        'canonical'      => __('Canonical URL', 'almaseo'),
        'robots'         => __('Robots Settings', 'almaseo'),
    );
}

/**
 * Get signal help text
 * 
 * @return array Signal help descriptions
 */
function almaseo_health_get_signal_help() {
    return array(
        'title' => __('A compelling title helps search engines and users understand your content.', 'almaseo'),
        'meta_desc' => __('Meta descriptions provide a summary for search engine results.', 'almaseo'),
        'h1' => __('One unique H1 heading provides clear structure for your content.', 'almaseo'),
        'kw_intro' => __('Including keywords early signals relevance to search engines.', 'almaseo'),
        'internal_link' => __('Internal links help distribute page authority and improve navigation.', 'almaseo'),
        'outbound_link' => __('Quality outbound links add credibility and context.', 'almaseo'),
        'image_alt' => __('Alt text improves accessibility and helps search engines understand images.', 'almaseo'),
        'readability' => __('Clear, concise writing improves user experience and engagement.', 'almaseo'),
        'canonical' => __('Canonical URLs prevent duplicate content issues.', 'almaseo'),
        'robots' => __('Proper robots settings ensure your content can be indexed.', 'almaseo'),
    );
}