<?php
/**
 * AlmaSEO Health Score - Tab Integration
 * Replaces old scoring in SEO Health tab with new analyzer
 * 
 * @package AlmaSEO
 * @subpackage Health
 * @since 1.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Get the compact health view HTML for the SEO Health tab
 * This replaces the old scoring system in the main tab
 */
function almaseo_health_get_tab_content($post) {
    ob_start();
    
    // Get score from new Health Analyzer (single source of truth)
    $health_score = get_post_meta($post->ID, '_almaseo_health_score', true);
    $health_breakdown_json = get_post_meta($post->ID, '_almaseo_health_breakdown', true);
    $health_updated = get_post_meta($post->ID, '_almaseo_health_updated_at', true);
    
    // If no score exists, calculate it
    if ($health_score === '' || $health_breakdown_json === '') {
        if (function_exists('almaseo_health_calculate')) {
            $result = almaseo_health_calculate($post->ID);
            $health_score = $result['score'];
            $health_breakdown = $result['breakdown'];
            
            // Save for next time
            update_post_meta($post->ID, '_almaseo_health_score', $health_score);
            update_post_meta($post->ID, '_almaseo_health_breakdown', json_encode($health_breakdown));
            update_post_meta($post->ID, '_almaseo_health_updated_at', current_time('timestamp'));
        } else {
            // Fallback
            $health_score = 0;
            $health_breakdown = array();
        }
    } else {
        $health_breakdown = json_decode($health_breakdown_json, true);
    }
    
    // Determine score class and color
    if ($health_score >= 80) {
        $score_class = 'excellent';
        $color_hex = '#00a32a';
        $status_text = __('Excellent! Your content is well-optimized.', 'almaseo');
    } elseif ($health_score >= 50) {
        $score_class = 'good';
        $color_hex = '#dba617';
        $status_text = __('Good, but there\'s room for improvement.', 'almaseo');
    } else {
        $score_class = 'poor';
        $color_hex = '#d63638';
        $status_text = __('Needs attention. Check the full analyzer below.', 'almaseo');
    }
    
    // Check if search engines are discouraged
    $search_engines_discouraged = (get_option('blog_public') == '0');
    
    ?>
    <!-- SEO Health Score Compact View -->
    <div class="almaseo-overview-header">
        <?php if ($search_engines_discouraged): ?>
        <!-- Critical Warning for Search Engine Blocking -->
        <div class="almaseo-search-engine-warning" style="background: #dc3232; color: white; padding: 12px 15px; margin-bottom: 15px; border-radius: 4px; display: flex; align-items: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <span class="dashicons dashicons-warning" style="font-size: 20px; margin-right: 10px;"></span>
            <div style="flex: 1;">
                <strong style="font-size: 13px;">⚠️ Search Engines Blocked!</strong>
                <span style="font-size: 12px; margin-left: 8px; opacity: 0.9;">
                    Your site is hidden from Google and other search engines.
                </span>
            </div>
            <a href="<?php echo admin_url('options-reading.php'); ?>" class="button button-small" style="background: white; color: #dc3232; border: none; font-size: 12px;">
                Fix Now →
            </a>
        </div>
        <?php endif; ?>
        
        <div class="almaseo-seo-health-score">
            <div class="health-score-container">
                <!-- Compact Gauge -->
                <div class="health-score-circle <?php echo $score_class; ?>">
                    <div class="score-value"><?php echo $health_score; ?></div>
                    <div class="score-label">SEO Score</div>
                </div>
                
                <!-- Summary -->
                <div class="health-breakdown">
                    <h4><?php _e('Overall SEO Health', 'almaseo'); ?></h4>
                    <p class="health-status-message <?php echo $score_class; ?>">
                        <?php echo esc_html($status_text); ?>
                    </p>
                    
                    <!-- Quick Stats -->
                    <div class="health-quick-stats">
                        <?php
                        $pass_count = 0;
                        $fail_count = 0;
                        if (is_array($health_breakdown)) {
                            foreach ($health_breakdown as $signal => $result) {
                                if (isset($result['pass']) && $result['pass']) {
                                    $pass_count++;
                                } else {
                                    $fail_count++;
                                }
                            }
                        }
                        ?>
                        <span class="health-stat-item">
                            <span class="stat-icon">✅</span>
                            <strong><?php echo $pass_count; ?></strong> <?php _e('Passed', 'almaseo'); ?>
                        </span>
                        <span class="health-stat-item">
                            <span class="stat-icon">❌</span>
                            <strong><?php echo $fail_count; ?></strong> <?php _e('Issues', 'almaseo'); ?>
                        </span>
                    </div>
                    
                    <!-- Link to Full Analyzer -->
                    <div class="health-analyzer-link">
                        <a href="#almaseo_health_score" class="button button-secondary" onclick="jQuery('html, body').animate({scrollTop: jQuery('#almaseo_health_score').offset().top - 50}, 500); return false;">
                            <?php _e('Open Full Health Analyzer', 'almaseo'); ?> ↓
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <style>
    /* Override old styles with new unified design */
    .health-score-circle {
        width: 120px;
        height: 120px;
        border-radius: 50%;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        position: relative;
        background: #f0f0f1;
        flex-shrink: 0;
    }
    
    .health-score-circle.excellent {
        background: linear-gradient(135deg, #d4edda, #c3e6cb);
        border: 3px solid #00a32a;
    }
    
    .health-score-circle.good {
        background: linear-gradient(135deg, #fff3cd, #ffeaa7);
        border: 3px solid #dba617;
    }
    
    .health-score-circle.poor {
        background: linear-gradient(135deg, #f8d7da, #f5c6cb);
        border: 3px solid #d63638;
    }
    
    .health-score-circle .score-value {
        font-size: 36px;
        font-weight: 700;
        line-height: 1;
    }
    
    .health-score-circle .score-label {
        font-size: 12px;
        text-transform: uppercase;
        opacity: 0.8;
        margin-top: 5px;
    }
    
    .health-score-container {
        display: flex;
        gap: 30px;
        align-items: center;
    }
    
    .health-breakdown {
        flex: 1;
    }
    
    .health-breakdown h4 {
        margin: 0 0 10px 0;
        font-size: 18px;
    }
    
    .health-status-message {
        margin: 0 0 15px 0;
        font-size: 14px;
    }
    
    .health-status-message.excellent {
        color: #00a32a;
    }
    
    .health-status-message.good {
        color: #856404;
    }
    
    .health-status-message.poor {
        color: #d63638;
    }
    
    .health-quick-stats {
        display: flex;
        gap: 20px;
        margin: 15px 0;
    }
    
    .health-stat-item {
        display: flex;
        align-items: center;
        gap: 5px;
        padding: 8px 12px;
        background: #f8f9fa;
        border-radius: 6px;
        font-size: 14px;
    }
    
    .health-analyzer-link {
        margin-top: 15px;
    }
    </style>
    <?php
    
    return ob_get_clean();
}