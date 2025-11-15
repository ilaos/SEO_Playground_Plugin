<?php
/**
 * AlmaSEO Health Score Feature - UI Components
 * 
 * @package AlmaSEO
 * @subpackage Health
 * @since 1.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Add health score meta box
 * DISABLED: Now integrated into unified SEO Health tab
 */
function almaseo_health_add_meta_box() {
    // Meta box disabled - functionality moved to unified SEO Health tab
    return;
    
    /*
    $post_types = array('post', 'page');
    
    foreach ($post_types as $post_type) {
        add_meta_box(
            'almaseo_health_score',
            __('🏥 SEO Health Score', 'almaseo'),
            'almaseo_health_meta_box_callback',
            $post_type,
            'normal',
            'high'
        );
    }
    */
}
// Disabled - now using unified tab
// add_action('add_meta_boxes', 'almaseo_health_add_meta_box', 15);

/**
 * Render health score meta box
 */
function almaseo_health_meta_box_callback($post) {
    // Get saved data
    $score = get_post_meta($post->ID, ALMASEO_HEALTH_SCORE_META, true);
    $breakdown_json = get_post_meta($post->ID, ALMASEO_HEALTH_BREAKDOWN_META, true);
    $updated_at = get_post_meta($post->ID, ALMASEO_HEALTH_UPDATED_META, true);
    
    // Calculate if not exists
    if ($score === '' || $breakdown_json === '') {
        $result = almaseo_health_calculate($post->ID);
        $score = $result['score'];
        $breakdown = $result['breakdown'];
        
        // Save for next time
        update_post_meta($post->ID, ALMASEO_HEALTH_SCORE_META, $score);
        update_post_meta($post->ID, ALMASEO_HEALTH_BREAKDOWN_META, json_encode($breakdown));
        update_post_meta($post->ID, ALMASEO_HEALTH_UPDATED_META, current_time('timestamp'));
        $updated_at = current_time('timestamp');
    } else {
        $breakdown = json_decode($breakdown_json, true);
    }
    
    // Get weights and labels
    $weights = almaseo_health_get_weights();
    $labels = almaseo_health_get_signal_labels();
    $help = almaseo_health_get_signal_help();
    
    // Determine color based on score
    $color_class = 'red';
    $color_hex = '#d63638';
    if ($score >= 80) {
        $color_class = 'green';
        $color_hex = '#00a32a';
    } elseif ($score >= 50) {
        $color_class = 'yellow';
        $color_hex = '#dba617';
    }
    
    // Check if search engines are discouraged
    $search_engines_discouraged = (get_option('blog_public') == '0');
    
    ?>
    <div class="almaseo-health-container" data-post-id="<?php echo esc_attr($post->ID); ?>">
        
        <?php if ($search_engines_discouraged): ?>
        <!-- Critical Warning Banner -->
        <div class="almaseo-critical-warning" style="background: #dc3232; color: white; padding: 15px; margin-bottom: 20px; border-radius: 4px; display: flex; align-items: center;">
            <span class="dashicons dashicons-warning" style="font-size: 24px; margin-right: 10px;"></span>
            <div style="flex: 1;">
                <strong style="font-size: 14px; display: block; margin-bottom: 5px;">⚠️ CRITICAL: Search Engines Are Blocked!</strong>
                <span style="font-size: 13px; opacity: 0.95;">
                    WordPress is set to "Discourage search engines from indexing this site". 
                    Your content will NOT appear in Google or other search results.
                </span>
            </div>
            <a href="<?php echo admin_url('options-reading.php'); ?>" class="button button-secondary" style="margin-left: 15px; background: white; color: #dc3232; border: none;">
                Fix Now →
            </a>
        </div>
        <?php endif; ?>
        
        <!-- Gauge Section -->
        <div class="almaseo-health-gauge-section">
            <div class="almaseo-health-gauge-wrapper" style="cursor: help;">
                <canvas id="almaseo-health-gauge" width="200" height="200"></canvas>
                <div class="almaseo-health-score-text <?php echo esc_attr($color_class); ?>">
                    <span class="score-number"><?php echo esc_html($score); ?></span>
                    <span class="score-label">/100</span>
                    <span class="score-info" style="font-size: 11px; display: block; margin-top: 5px; opacity: 0.7;">ⓘ hover for details</span>
                </div>
            </div>
            
            <div class="almaseo-health-gauge-info">
                <h3><?php _e('Overall SEO Health', 'almaseo'); ?></h3>
                <p class="health-status health-<?php echo esc_attr($color_class); ?>">
                    <?php
                    if ($score >= 80) {
                        _e('Excellent! Your content is well-optimized.', 'almaseo');
                    } elseif ($score >= 50) {
                        _e('Good, but there\'s room for improvement.', 'almaseo');
                    } else {
                        _e('Needs attention. Follow the suggestions below.', 'almaseo');
                    }
                    ?>
                </p>
            </div>
        </div>
        
        <!-- Signal Bars Section -->
        <div class="almaseo-health-signals">
            <h4><?php _e('SEO Signal Analysis', 'almaseo'); ?></h4>
            
            <?php 
            // Debug: Check if breakdown is empty
            if (empty($breakdown)) {
                echo '<p style="color: red;">Debug: Breakdown array is empty. Recalculating...</p>';
                $result = almaseo_health_calculate($post->ID);
                $breakdown = $result['breakdown'];
                update_post_meta($post->ID, ALMASEO_HEALTH_BREAKDOWN_META, json_encode($breakdown));
            }
            ?>
            
            <?php foreach ($breakdown as $signal => $result): 
                if (!isset($weights[$signal])) continue;
                
                $weight = $weights[$signal];
                $label = isset($labels[$signal]) ? $labels[$signal] : ucfirst($signal);
                $help_text = isset($help[$signal]) ? $help[$signal] : '';
                $icon = $result['pass'] ? '✅' : '❌';
                $status_class = $result['pass'] ? 'pass' : 'fail';
            ?>
            <div class="almaseo-health-signal <?php echo esc_attr($status_class); ?>">
                <div class="signal-header">
                    <span class="signal-icon"><?php echo $icon; ?></span>
                    <span class="signal-label">
                        <?php echo esc_html($label); ?>
                        <span class="signal-weight" title="<?php printf(esc_attr__('Worth %d points out of 100', 'almaseo'), $weight); ?>">(<?php echo esc_html($weight); ?> pts)</span>
                    </span>
                    <?php if (!$result['pass']): ?>
                    <button type="button" class="signal-fix-btn" data-signal="<?php echo esc_attr($signal); ?>">
                        <?php _e('Fix', 'almaseo'); ?> →
                    </button>
                    <?php endif; ?>
                </div>
                
                <div class="signal-bar-wrapper">
                    <div class="signal-bar">
                        <div class="signal-bar-fill <?php echo esc_attr($status_class); ?>" 
                             style="width: <?php echo $result['pass'] ? '100' : '0'; ?>%"></div>
                    </div>
                </div>
                
                <div class="signal-note">
                    <?php echo esc_html($result['note']); ?>
                    <?php if ($help_text): ?>
                    <span class="signal-help" title="<?php echo esc_attr($help_text); ?>">ⓘ</span>
                    <?php endif; ?>
                </div>
                
                <!-- Quick fix helpers -->
                <?php if (!$result['pass']): ?>
                <div class="signal-fix-helper" id="fix-<?php echo esc_attr($signal); ?>" style="display: none;">
                    <?php
                    switch ($signal) {
                        case 'title':
                            ?>
                            <p><?php _e('Add a compelling title for your content.', 'almaseo'); ?></p>
                            <button type="button" class="button focus-field" data-field="title">
                                <?php _e('Edit Title', 'almaseo'); ?>
                            </button>
                            <?php
                            break;
                            
                        case 'meta_desc':
                            ?>
                            <p><?php _e('Add a meta description to improve click-through rates.', 'almaseo'); ?></p>
                            <button type="button" class="button focus-field" data-field="almaseo_seo_description">
                                <?php _e('Edit Meta Description', 'almaseo'); ?>
                            </button>
                            <button type="button" class="button draft-meta-desc">
                                <?php _e('Draft from First Paragraph', 'almaseo'); ?>
                            </button>
                            <?php
                            break;
                            
                        case 'h1':
                            ?>
                            <p><?php _e('Ensure you have exactly one H1 heading in your content.', 'almaseo'); ?></p>
                            <p class="description"><?php _e('Use Heading 1 format for your main title.', 'almaseo'); ?></p>
                            <?php
                            break;
                            
                        case 'kw_intro':
                            ?>
                            <p><?php _e('Include your focus keyword in the first 100 words.', 'almaseo'); ?></p>
                            <button type="button" class="button focus-field" data-field="almaseo_focus_keyword">
                                <?php _e('Set Focus Keyword', 'almaseo'); ?>
                            </button>
                            <?php
                            break;
                            
                        case 'internal_link':
                            ?>
                            <p><?php _e('Add links to other pages on your site.', 'almaseo'); ?></p>
                            <p class="description"><?php _e('Example: Link to related articles or pages.', 'almaseo'); ?></p>
                            <?php
                            break;
                            
                        case 'outbound_link':
                            ?>
                            <p><?php _e('Add links to authoritative external sources.', 'almaseo'); ?></p>
                            <p class="description"><?php _e('Example: Link to studies, references, or resources.', 'almaseo'); ?></p>
                            <?php
                            break;
                            
                        case 'image_alt':
                            ?>
                            <p><?php _e('Add descriptive alt text to your images.', 'almaseo'); ?></p>
                            <?php if (has_post_thumbnail($post->ID)): ?>
                            <button type="button" class="button open-media-modal">
                                <?php _e('Edit Featured Image Alt Text', 'almaseo'); ?>
                            </button>
                            <?php else: ?>
                            <p class="description"><?php _e('Add alt text when inserting images.', 'almaseo'); ?></p>
                            <?php endif; ?>
                            <?php
                            break;
                            
                        case 'readability':
                            ?>
                            <p><?php _e('Improve readability with shorter sentences and paragraphs.', 'almaseo'); ?></p>
                            <p class="description"><?php _e('Aim for sentences under 24 words and paragraphs under 150 words.', 'almaseo'); ?></p>
                            <?php
                            break;
                            
                        case 'canonical':
                            ?>
                            <p><?php _e('Set a canonical URL or leave empty for default.', 'almaseo'); ?></p>
                            <button type="button" class="button focus-field" data-field="almaseo_canonical_url">
                                <?php _e('Edit Canonical URL', 'almaseo'); ?>
                            </button>
                            <?php
                            break;
                            
                        case 'robots':
                            ?>
                            <p><?php _e('Check your robots meta settings.', 'almaseo'); ?></p>
                            <button type="button" class="button jump-to-robots">
                                <?php _e('Edit Robots Settings', 'almaseo'); ?>
                            </button>
                            <?php
                            break;
                    }
                    ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Google Search Preview -->
        <?php almaseo_health_render_search_preview($post->ID); ?>
        
        <!-- Keyword Suggestions Section -->
        <div class="almaseo-keyword-suggestions">
            <h4><?php _e('Keyword Suggestions', 'almaseo'); ?></h4>
            <?php
            // Check if connected to AlmaSEO Dashboard
            $api_key = get_option('almaseo_api_key');
            if (!empty($api_key)):
            ?>
                <div class="keyword-suggestions-content">
                    <p class="description"><?php _e('🔗 Connected to AlmaSEO Dashboard - Live keyword intelligence available', 'almaseo'); ?></p>
                    <div id="keyword-suggestions-list">
                        <!-- Populated via AJAX from AlmaSEO API -->
                        <span class="spinner"></span> <?php _e('Loading keyword suggestions...', 'almaseo'); ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="keyword-suggestions-placeholder">
                    <p class="description"><?php _e('📝 Currently using local content analysis for keyword extraction', 'almaseo'); ?></p>
                    <p><?php _e('Connect to AlmaSEO Dashboard to unlock:', 'almaseo'); ?></p>
                    <ul>
                        <li><?php _e('✨ Live keyword suggestions from search data', 'almaseo'); ?></li>
                        <li><?php _e('📊 Search volume and competition metrics', 'almaseo'); ?></li>
                        <li><?php _e('🎯 Related keyword opportunities', 'almaseo'); ?></li>
                    </ul>
                    <a href="<?php echo admin_url('admin.php?page=almaseo-settings'); ?>" class="button button-primary">
                        <?php _e('Connect AlmaSEO Dashboard', 'almaseo'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Footer Actions -->
        <div class="almaseo-health-footer">
            <button type="button" class="button button-primary" id="almaseo-health-recalculate">
                <span class="dashicons dashicons-update" style="vertical-align: middle;"></span>
                <?php _e('Recalculate', 'almaseo'); ?>
            </button>
            
            <span class="almaseo-health-updated">
                <?php if ($updated_at): ?>
                    <?php _e('Last updated', 'almaseo'); ?>: 
                    <span class="updated-time">
                        <?php echo human_time_diff($updated_at, current_time('timestamp')); ?> 
                        <?php _e('ago', 'almaseo'); ?>
                    </span>
                <?php else: ?>
                    <?php _e('Not yet calculated', 'almaseo'); ?>
                <?php endif; ?>
            </span>
        </div>
        
        <!-- Hidden data for JS -->
        <input type="hidden" id="almaseo-health-score-value" value="<?php echo esc_attr($score); ?>">
        <input type="hidden" id="almaseo-health-color-hex" value="<?php echo esc_attr($color_hex); ?>">
    </div>
    <?php
}

/**
 * Get score explanation text for tooltip
 */
function almaseo_health_get_score_explanation($weights) {
    $explanation = __('SEO Health Score Breakdown:', 'almaseo') . "\n\n";
    $labels = almaseo_health_get_signal_labels();
    
    foreach ($weights as $signal => $weight) {
        $label = isset($labels[$signal]) ? $labels[$signal] : ucfirst($signal);
        $explanation .= sprintf("• %s: %d points\n", $label, $weight);
    }
    
    $explanation .= "\n" . __('Total: 100 points. Score is weighted by importance of each signal.', 'almaseo');
    
    return $explanation;
}

/**
 * Render Google Search Preview component
 */
function almaseo_health_render_search_preview($post_id) {
    $post = get_post($post_id);
    
    // Get title
    $title = get_post_meta($post_id, '_almaseo_title', true);
    if (empty($title)) {
        $title = $post->post_title;
    }
    
    // Get meta description
    $description = get_post_meta($post_id, '_almaseo_description', true);
    if (empty($description)) {
        $description = $post->post_excerpt;
    }
    if (empty($description)) {
        $content = wp_strip_all_tags($post->post_content);
        $description = wp_trim_words($content, 25);
    }
    
    // Truncate for Google SERP display
    $title_truncated = almaseo_truncate_for_serp($title, 60);
    $desc_truncated = almaseo_truncate_for_serp($description, 160);
    
    // Get URL
    $url = get_permalink($post_id);
    $url_display = str_replace(array('http://', 'https://'), '', $url);
    
    ?>
    <div class="almaseo-search-preview">
        <h4><?php _e('Google Search Preview', 'almaseo'); ?></h4>
        <div class="serp-preview">
            <div class="serp-url"><?php echo esc_html($url_display); ?></div>
            <div class="serp-title"><?php echo esc_html($title_truncated); ?></div>
            <div class="serp-description"><?php echo esc_html($desc_truncated); ?></div>
        </div>
    </div>
    <?php
}

/**
 * Truncate text for SERP display
 */
function almaseo_truncate_for_serp($text, $max_length) {
    if (strlen($text) <= $max_length) {
        return $text;
    }
    
    // Truncate at word boundary
    $truncated = substr($text, 0, $max_length - 3);
    $last_space = strrpos($truncated, ' ');
    
    if ($last_space !== false) {
        $truncated = substr($truncated, 0, $last_space);
    }
    
    return $truncated . '...';
}