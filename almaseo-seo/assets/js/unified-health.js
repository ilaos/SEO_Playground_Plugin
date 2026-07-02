/**
 * AlmaSEO Unified Health Score - JavaScript
 * 
 * @package AlmaSEO
 * @since 6.8.2
 */

(function($) {
    'use strict';
    
    /**
     * Draw radial gauge
     */
    function drawGauge(score, color) {
        const canvas = document.getElementById('almaseo-health-gauge');
        if (!canvas) return;
        
        const ctx = canvas.getContext('2d');
        const centerX = canvas.width / 2;
        const centerY = canvas.height / 2;
        const radius = 80;
        
        // Clear canvas
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        
        // Draw background arc
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, Math.PI * 0.7, Math.PI * 2.3, false);
        ctx.lineWidth = 20;
        ctx.strokeStyle = '#e0e0e0';
        ctx.stroke();
        
        // Draw score arc
        const scoreAngle = (score / 100) * Math.PI * 1.6;
        ctx.beginPath();
        ctx.arc(centerX, centerY, radius, Math.PI * 0.7, Math.PI * 0.7 + scoreAngle, false);
        ctx.lineWidth = 20;
        ctx.strokeStyle = color;
        ctx.lineCap = 'round';
        ctx.stroke();
        
        // Draw tick marks
        ctx.strokeStyle = '#999';
        ctx.lineWidth = 1;
        for (let i = 0; i <= 10; i++) {
            const angle = Math.PI * 0.7 + (i / 10) * Math.PI * 1.6;
            const x1 = centerX + Math.cos(angle) * (radius - 10);
            const y1 = centerY + Math.sin(angle) * (radius - 10);
            const x2 = centerX + Math.cos(angle) * (radius + 10);
            const y2 = centerY + Math.sin(angle) * (radius + 10);
            
            ctx.beginPath();
            ctx.moveTo(x1, y1);
            ctx.lineTo(x2, y2);
            ctx.stroke();
        }
    }
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Draw initial gauge
        const score = parseInt($('.score-number').first().text()) || 0;
        let color = '#d63638';
        if (score >= 80) {
            color = '#00a32a';
        } else if (score >= 50) {
            color = '#dba617';
        }
        drawGauge(score, color);
        
        // Handle recalculate button
        $('#almaseo-health-recalculate').on('click', function() {
            const $btn = $(this);
            const postId = $btn.data('post-id');
            
            // Disable button and show loading
            $btn.prop('disabled', true).addClass('updating');
            $btn.find('.dashicons').addClass('spin');
            $btn.append('<span class="loading-text"> ' + (almaseoHealth?.i18n?.recalculating || 'Recalculating...') + '</span>');
            
            // AJAX request
            $.post(almaseoHealth?.ajaxurl || ajaxurl, {
                action: 'almaseo_health_recalculate',
                post_id: postId,
                nonce: almaseoHealth?.nonce || ''
            })
            .done(function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Update score display
                    $('.score-number').text(data.score);
                    
                    // Update color class
                    let colorClass = 'poor';
                    let colorHex = '#d63638';
                    if (data.score >= 80) {
                        colorClass = 'excellent';
                        colorHex = '#00a32a';
                    } else if (data.score >= 50) {
                        colorClass = 'good';
                        colorHex = '#dba617';
                    }
                    
                    $('.health-score-text').removeClass('poor good excellent').addClass(colorClass);
                    $('.health-status').removeClass('health-poor health-good health-excellent').addClass('health-' + colorClass);
                    
                    // Update status text
                    let statusText = '';
                    if (data.score >= 80) {
                        statusText = 'Excellent! Your content is well-optimized.';
                    } else if (data.score >= 50) {
                        statusText = 'Good, but there\'s room for improvement.';
                    } else {
                        statusText = 'Needs attention. Follow the suggestions below.';
                    }
                    $('.health-status').text(statusText);
                    
                    // Redraw gauge
                    drawGauge(data.score, colorHex);
                    
                    // Update pass/fail counts
                    let passCount = 0;
                    let failCount = 0;
                    
                    // Update signals
                    $.each(data.breakdown, function(signal, result) {
                        if (result.pass) {
                            passCount++;
                        } else {
                            failCount++;
                        }
                        
                        const $signal = $('.almaseo-health-signal').filter(function() {
                            return $(this).find('.signal-goto-btn').data('field')?.includes(signal) || 
                                   $(this).find('.signal-label').text().toLowerCase().includes(signal.replace('_', ' '));
                        });
                        
                        if ($signal.length) {
                            // Update icon
                            $signal.find('.signal-icon').text(result.pass ? '✅' : '❌');
                            
                            // Update classes
                            $signal.removeClass('pass fail').addClass(result.pass ? 'pass' : 'fail');
                            
                            // Update bar
                            $signal.find('.signal-bar-fill')
                                .removeClass('pass fail')
                                .addClass(result.pass ? 'pass' : 'fail')
                                .css('width', result.pass ? '100%' : '0%');
                            
                            // Update note
                            $signal.find('.signal-note').text(result.note);
                            
                            // Show/hide go to field button
                            if (result.pass) {
                                $signal.find('.signal-goto-btn').hide();
                            } else {
                                $signal.find('.signal-goto-btn').show();
                            }
                        }
                    });
                    
                    // Update stats
                    $('.health-stats .health-stat:first strong').text(passCount);
                    $('.health-stats .health-stat:last strong').text(failCount);
                    
                    // Show success message
                    $btn.find('.loading-text').text(' ' + (almaseoHealth?.i18n?.recalculated || 'Score updated!'));
                    
                    setTimeout(function() {
                        $btn.find('.loading-text').remove();
                        $btn.prop('disabled', false).removeClass('updating');
                        $btn.find('.dashicons').removeClass('spin');
                    }, 2000);
                }
            })
            .fail(function() {
                alert(almaseoHealth?.i18n?.error || 'Error calculating score');
                $btn.find('.loading-text').remove();
                $btn.prop('disabled', false).removeClass('updating');
                $btn.find('.dashicons').removeClass('spin');
            });
        });
        
        // Handle "Go to Field" buttons
        $('.signal-goto-btn').on('click', function() {
            const fieldId = $(this).data('field');
            const $field = $('#' + fieldId);
            
            if ($field.length) {
                // Smooth scroll to field
                $('html, body').animate({
                    scrollTop: $field.offset().top - 100
                }, 500, function() {
                    // Focus the field
                    $field.focus();
                    
                    // Add highlight effect
                    $field.addClass('field-highlight');
                    setTimeout(function() {
                        $field.removeClass('field-highlight');
                    }, 2000);
                });
            }
        });
        
        // Add spinning animation
        const style = document.createElement('style');
        style.textContent = `
            @keyframes spin {
                from { transform: rotate(0deg); }
                to { transform: rotate(360deg); }
            }
            .dashicons.spin {
                animation: spin 1s linear infinite;
                display: inline-block;
            }
            .field-highlight {
                box-shadow: 0 0 0 3px rgba(34, 113, 177, 0.3);
                transition: box-shadow 0.3s ease;
            }
        `;
        document.head.appendChild(style);
    });
    
})(jQuery);