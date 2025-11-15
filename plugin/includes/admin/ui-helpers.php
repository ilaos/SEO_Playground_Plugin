<?php
/**
 * AlmaSEO Admin UI Helper Functions
 * 
 * @package AlmaSEO
 * @since 6.0.2
 */

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render help text with optional tooltip
 * 
 * @param string $text Main help text to display
 * @param string $tooltip Optional tooltip text for additional information
 * @return void
 */
function almaseo_render_help($text, $tooltip = '') {
    // Sanitize and translate the main text
    $help_text = esc_html($text);
    
    // Start the help paragraph
    echo '<p class="description almaseo-help">';
    echo $help_text;
    
    // Add tooltip icon if tooltip text is provided
    if (!empty($tooltip)) {
        $tooltip_text = esc_attr($tooltip);
        echo sprintf(
            ' <span class="dashicons dashicons-editor-help" aria-label="%s" title="%s" role="img"></span>',
            $tooltip_text,
            $tooltip_text
        );
    }
    
    echo '</p>';
}

/**
 * Render help text with learn more link
 * 
 * @param string $text Main help text
 * @param string $url Documentation URL
 * @param string $link_text Link text (defaults to "Learn more")
 * @return void
 */
function almaseo_render_help_with_link($text, $url, $link_text = '') {
    if (empty($link_text)) {
        $link_text = __('Learn more', 'almaseo');
    }
    
    echo '<p class="description almaseo-help">';
    echo esc_html($text);
    echo ' <a href="' . esc_url($url) . '" target="_blank" rel="noopener">';
    echo esc_html($link_text);
    echo ' <span class="dashicons dashicons-external" style="font-size: 14px; vertical-align: middle;"></span>';
    echo '</a>';
    echo '</p>';
}

/**
 * Render inline tip (shorter than help text)
 * 
 * @param string $text Tip text
 * @param string $type Type of tip: 'info', 'warning', 'success'
 * @return void
 */
function almaseo_render_tip($text, $type = 'info') {
    $allowed_types = array('info', 'warning', 'success');
    if (!in_array($type, $allowed_types)) {
        $type = 'info';
    }
    
    $icon = '';
    switch ($type) {
        case 'warning':
            $icon = 'dashicons-warning';
            break;
        case 'success':
            $icon = 'dashicons-yes-alt';
            break;
        default:
            $icon = 'dashicons-info';
    }
    
    echo '<div class="almaseo-tip almaseo-tip-' . esc_attr($type) . '">';
    echo '<span class="dashicons ' . esc_attr($icon) . '"></span> ';
    echo esc_html($text);
    echo '</div>';
}

/**
 * Check if we're on an AlmaSEO admin screen
 * 
 * @return bool
 */
function almaseo_is_admin_screen() {
    $screen = get_current_screen();
    if (!$screen) {
        return false;
    }
    
    // Check for AlmaSEO screens
    $almaseo_screens = array(
        'seo-playground',
        'almaseo-robots',
        'almaseo-sitemaps',
        'almaseo-evergreen',
        'seo-playground-connection'
    );
    
    // Check screen ID
    foreach ($almaseo_screens as $almaseo_screen) {
        if (strpos($screen->id, $almaseo_screen) !== false) {
            return true;
        }
    }
    
    // Check if we're on post/page editor with AlmaSEO meta box
    if (in_array($screen->post_type, array('post', 'page')) && $screen->base === 'post') {
        return true;
    }
    
    return false;
}