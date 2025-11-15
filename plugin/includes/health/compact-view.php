<?php
/**
 * AlmaSEO Health Score - Compact View for SEO Health Tab
 * 
 * @package AlmaSEO
 * @subpackage Health
 * @since 1.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render compact health score view
 */
function almaseo_health_render_compact_view($post) {
    // Get score from Health Analyzer (single source of truth)
    $health_score = get_post_meta($post->ID, ALMASEO_HEALTH_SCORE_META, true);
    $health_breakdown_json = get_post_meta($post->ID, ALMASEO_HEALTH_BREAKDOWN_META, true);
    $health_updated = get_post_meta($post->ID, ALMASEO_HEALTH_UPDATED_META, true);
    
    // If no score exists, calculate it
    if ($health_score === '' || $health_breakdown_json === '') {
        if (function_exists('almaseo_health_calculate')) {
            $result = almaseo_health_calculate($post->ID);
            $health_score = $result['score'];
            $health_breakdown = $result['breakdown'];
            
            // Save for next time
            update_post_meta($post->ID, ALMASEO_HEALTH_SCORE_META, $health_score);
            update_post_meta($post->ID, ALMASEO_HEALTH_BREAKDOWN_META, json_encode($health_breakdown));
            update_post_meta($post->ID, ALMASEO_HEALTH_UPDATED_META, current_time('timestamp'));
        } else {
            // Fallback if health analyzer not loaded
            $health_score = 0;
            $health_breakdown = array();
        }
    } else {
        $health_breakdown = json_decode($health_breakdown_json, true);
    }
    
    // Determine score color
    $score_class = 'poor';
    $color_hex = '#d63638';
    if ($health_score >= 80) {
        $score_class = 'excellent';
        $color_hex = '#00a32a';
    } elseif ($health_score >= 50) {
        $score_class = 'good';
        $color_hex = '#dba617';
    }
    
    ?>
    <div class="almaseo-health-compact" data-post-id="<?php echo esc_attr($post->ID); ?>">
        <div class="health-compact-container">
            <!-- Mini Gauge -->
            <div class="health-compact-gauge">
                <canvas id="almaseo-health-gauge-compact" width="120" height="120"></canvas>
                <div class="health-compact-score <?php echo esc_attr($score_class); ?>">
                    <span class="score-value"><?php echo esc_html($health_score); ?></span>
                    <span class="score-max">/100</span>
                </div>
            </div>
            
            <!-- Health Summary -->
            <div class="health-compact-summary">
                <h3><?php _e('Overall SEO Health', 'almaseo'); ?></h3>
                <p class="health-status-text health-<?php echo esc_attr($score_class); ?>">
                    <?php
                    if ($health_score >= 80) {
                        _e('Excellent! Your content is well-optimized.', 'almaseo');
                    } elseif ($health_score >= 50) {
                        _e('Good, but there\'s room for improvement.', 'almaseo');
                    } else {
                        _e('Needs attention. Check the analyzer below.', 'almaseo');
                    }
                    ?>
                </p>
                <button type="button" class="button button-secondary" id="almaseo-open-full-analyzer">
                    <?php _e('Open Full Health Analyzer', 'almaseo'); ?> ↓
                </button>
            </div>
            
            <!-- Quick Stats -->
            <div class="health-compact-stats">
                <?php
                $pass_count = 0;
                $fail_count = 0;
                foreach ($health_breakdown as $signal => $result) {
                    if ($result['pass']) {
                        $pass_count++;
                    } else {
                        $fail_count++;
                    }
                }
                ?>
                <div class="health-stat">
                    <span class="stat-icon">✅</span>
                    <span class="stat-value"><?php echo $pass_count; ?></span>
                    <span class="stat-label"><?php _e('Passed', 'almaseo'); ?></span>
                </div>
                <div class="health-stat">
                    <span class="stat-icon">❌</span>
                    <span class="stat-value"><?php echo $fail_count; ?></span>
                    <span class="stat-label"><?php _e('Issues', 'almaseo'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Hidden data for JS -->
        <input type="hidden" id="almaseo-health-compact-score" value="<?php echo esc_attr($health_score); ?>">
        <input type="hidden" id="almaseo-health-compact-color" value="<?php echo esc_attr($color_hex); ?>">
    </div>
    
    <script>
    jQuery(document).ready(function($) {
        // Draw compact gauge
        function drawCompactGauge(score, color) {
            const canvas = document.getElementById('almaseo-health-gauge-compact');
            if (!canvas) return;
            
            const ctx = canvas.getContext('2d');
            const centerX = canvas.width / 2;
            const centerY = canvas.height / 2;
            const radius = 45;
            
            // Clear canvas
            ctx.clearRect(0, 0, canvas.width, canvas.height);
            
            // Draw background arc
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius, Math.PI * 0.7, Math.PI * 2.3, false);
            ctx.lineWidth = 12;
            ctx.strokeStyle = '#e0e0e0';
            ctx.stroke();
            
            // Draw score arc
            const scoreAngle = (score / 100) * Math.PI * 1.6;
            ctx.beginPath();
            ctx.arc(centerX, centerY, radius, Math.PI * 0.7, Math.PI * 0.7 + scoreAngle, false);
            ctx.lineWidth = 12;
            ctx.strokeStyle = color;
            ctx.lineCap = 'round';
            ctx.stroke();
        }
        
        // Initialize compact gauge
        const score = $('#almaseo-health-compact-score').val();
        const color = $('#almaseo-health-compact-color').val();
        if (score && color) {
            drawCompactGauge(parseInt(score), color);
        }
        
        // Open full analyzer
        $('#almaseo-open-full-analyzer').on('click', function() {
            const $analyzer = $('#almaseo_health_score');
            if ($analyzer.length) {
                // Scroll to full analyzer
                $('html, body').animate({
                    scrollTop: $analyzer.offset().top - 50
                }, 500);
                
                // Open the meta box if closed
                if ($analyzer.hasClass('closed')) {
                    $analyzer.find('.handlediv').click();
                }
            }
        });
        
        // Sync with full analyzer recalculate
        $(document).on('health-score-updated', function(e, data) {
            // Update compact view
            $('#almaseo-health-compact-score').val(data.score);
            $('.health-compact-score .score-value').text(data.score);
            
            // Update color class
            let scoreClass = 'poor';
            let colorHex = '#d63638';
            if (data.score >= 80) {
                scoreClass = 'excellent';
                colorHex = '#00a32a';
            } else if (data.score >= 50) {
                scoreClass = 'good';
                colorHex = '#dba617';
            }
            
            $('.health-compact-score').removeClass('poor good excellent').addClass(scoreClass);
            $('.health-status-text').removeClass('health-poor health-good health-excellent').addClass('health-' + scoreClass);
            
            // Update status text
            let statusText = '';
            if (data.score >= 80) {
                statusText = 'Excellent! Your content is well-optimized.';
            } else if (data.score >= 50) {
                statusText = 'Good, but there\'s room for improvement.';
            } else {
                statusText = 'Needs attention. Check the analyzer below.';
            }
            $('.health-status-text').text(statusText);
            
            // Redraw gauge
            drawCompactGauge(data.score, colorHex);
            
            // Update stats
            let passCount = 0;
            let failCount = 0;
            $.each(data.breakdown, function(signal, result) {
                if (result.pass) {
                    passCount++;
                } else {
                    failCount++;
                }
            });
            $('.health-stat').eq(0).find('.stat-value').text(passCount);
            $('.health-stat').eq(1).find('.stat-value').text(failCount);
        });
    });
    </script>
    <?php
}