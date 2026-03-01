/**
 * AlmaSEO Health Score - Stable JavaScript Implementation
 * 
 * @package AlmaSEO
 * @since 4.2.4
 */

(function($) {
    'use strict';
    
    // Global initialization guard
    if (window.almaseoHealthInitialized) {
        console.log('[AlmaSEO Health] Already initialized, skipping...');
        return;
    }
    window.almaseoHealthInitialized = true;
    
    // Debug logging
    const DEBUG = window.ALMASEO_DEV_DEBUG || false;
    function debugLog(message, data) {
        if (DEBUG) {
            console.log('[AlmaSEO Health] ' + message, data || '');
        }
    }
    
    // Request management
    let currentRequest = null;
    let requestToken = 0;
    
    /**
     * Setup custom tooltip for gauge
     */
    function setupGaugeTooltip() {
        // Create tooltip element if it doesn't exist
        if (!$('#almaseo-gauge-tooltip').length) {
            $('body').append(`
                <div id="almaseo-gauge-tooltip" style="
                    position: absolute;
                    background: rgba(0, 0, 0, 0.9);
                    color: white;
                    padding: 10px 12px;
                    border-radius: 4px;
                    font-size: 12px;
                    line-height: 1.5;
                    white-space: pre-line;
                    max-width: 280px;
                    pointer-events: none;
                    z-index: 100000;
                    display: none;
                    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
                ">
                    <strong>SEO Health Score Breakdown:</strong>
                    
                    • Title: 20 points
                    • Meta Description: 15 points
                    • H1 Heading: 10 points
                    • Keyword in Introduction: 10 points
                    • Internal Link: 10 points
                    • Outbound Link: 10 points
                    • Image Alt Text: 10 points
                    • Readability: 10 points
                    • Canonical URL: 3 points
                    • Robots Settings: 2 points
                    
                    <em>Total: 100 points (weighted by importance)</em>
                </div>
            `);
        }
        
        // Add hover handlers to the gauge wrapper
        $(document).on('mouseenter', '.almaseo-health-gauge-wrapper', function(e) {
            const $tooltip = $('#almaseo-gauge-tooltip');
            $tooltip.css({
                left: e.pageX + 15,
                top: e.pageY - 10
            }).fadeIn(200);
        });
        
        $(document).on('mousemove', '.almaseo-health-gauge-wrapper', function(e) {
            const $tooltip = $('#almaseo-gauge-tooltip');
            $tooltip.css({
                left: e.pageX + 15,
                top: e.pageY - 10
            });
        });
        
        $(document).on('mouseleave', '.almaseo-health-gauge-wrapper', function() {
            $('#almaseo-gauge-tooltip').fadeOut(200);
        });
    }
    
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
     * Stable health refresh - single endpoint, complete re-render
     */
    function refreshHealthPanel(metaFields) {
        const $container = $('.almaseo-health-container');
        if (!$container.length) {
            debugLog('Health container not found');
            return;
        }
        
        const postId = $container.data('post-id');
        const thisToken = ++requestToken;
        
        debugLog('Starting refresh', { token: thisToken, postId: postId });
        
        // Cancel previous request
        if (currentRequest && currentRequest.abort) {
            currentRequest.abort();
            debugLog('Cancelled previous request');
        }
        
        // Show loading state
        showLoadingState();
        
        // Make request
        currentRequest = $.ajax({
            url: almaseoHealth.ajaxurl,
            type: 'POST',
            data: {
                action: 'almaseo_health_refresh',
                post_id: postId,
                meta_fields: metaFields || {},
                nonce: almaseoHealth.nonce,
                token: thisToken
            },
            success: function(response) {
                // Ignore stale responses
                if (thisToken !== requestToken) {
                    debugLog('Ignoring stale response', { responseToken: thisToken, currentToken: requestToken });
                    return;
                }
                
                if (response.success) {
                    renderHealthPanel(response.data);
                    hideLoadingState();
                    debugLog('Refresh successful', response.data);
                } else {
                    showError('Failed to refresh health checks: ' + (response.data || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                // Ignore aborted requests
                if (status === 'abort') {
                    debugLog('Request aborted');
                    return;
                }
                
                // Ignore stale responses
                if (thisToken !== requestToken) {
                    return;
                }
                
                showError('Could not refresh checks. Please try the Recalculate button.');
                console.error('[AlmaSEO Health] AJAX Error:', { status: status, error: error, response: xhr.responseText });
            },
            complete: function() {
                currentRequest = null;
            }
        });
    }
    
    /**
     * Render health panel from data - idempotent
     */
    function renderHealthPanel(data) {
        // Update score
        $('.score-number').text(data.score);
        
        // Update color
        let colorClass = 'poor';
        let colorHex = '#d63638';
        if (data.score >= 80) {
            colorClass = 'excellent';
            colorHex = '#00a32a';
        } else if (data.score >= 50) {
            colorClass = 'good';
            colorHex = '#dba617';
        }
        
        $('.almaseo-health-score-text').removeClass('poor good excellent').addClass(colorClass);
        $('.health-status').removeClass('health-poor health-good health-excellent').addClass('health-' + colorClass);
        
        // Redraw gauge
        drawGauge(data.score, colorHex);
        
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
        
        // Render signals checklist
        renderSignalsChecklist(data.breakdown);
        
        // Update counters
        let passCount = 0;
        let failCount = 0;
        $.each(data.breakdown, function(signal, result) {
            if (result.pass) {
                passCount++;
            } else {
                failCount++;
            }
        });
        
        $('.health-stat-item:contains("Passed") strong').text(passCount);
        $('.health-stat-item:contains("Issues") strong').text(failCount);
        
        // Update SERP preview if available
        if (data.serp_preview) {
            $('.serp-title').text(data.serp_preview.title);
            $('.serp-description').text(data.serp_preview.description);
            $('.serp-url').text(data.serp_preview.url_display || data.serp_preview.url);
        }
        
        // Update timestamp
        if (data.updated_at) {
            $('.updated-time').text(data.updated_at);
        }
        
        // Clear unsaved banner
        $('.almaseo-unsaved-banner').fadeOut();
    }
    
    /**
     * Render signals checklist - ensures container always exists
     */
    function renderSignalsChecklist(breakdown) {
        let $signalsContainer = $('.almaseo-health-signals');
        
        // Ensure container exists
        if (!$signalsContainer.length) {
            debugLog('Signals container missing, creating...');
            const $healthContainer = $('.almaseo-health-container');
            $signalsContainer = $('<div class="almaseo-health-signals"></div>');
            $healthContainer.find('.almaseo-health-gauge-section').after($signalsContainer);
        }
        
        // Always keep header
        if (!$signalsContainer.find('h4').length) {
            $signalsContainer.prepend('<h4>SEO Signal Analysis</h4>');
        }
        
        // Get signal labels and weights from PHP (if available)
        const weights = {
            'title': 20,
            'meta_desc': 15,
            'h1': 10,
            'kw_intro': 10,
            'internal_link': 10,
            'outbound_link': 10,
            'image_alt': 10,
            'readability': 10,
            'canonical': 3,
            'robots': 2
        };
        
        const labels = {
            'title': 'Title',
            'meta_desc': 'Meta Description',
            'h1': 'H1 Heading',
            'kw_intro': 'Keyword in Introduction',
            'internal_link': 'Internal Link',
            'outbound_link': 'Outbound Link',
            'image_alt': 'Image Alt Text',
            'readability': 'Readability',
            'canonical': 'Canonical URL',
            'robots': 'Robots Settings'
        };
        
        // Update or create each signal
        $.each(breakdown, function(signal, result) {
            let $signal = $signalsContainer.find('.almaseo-health-signal[data-signal="' + signal + '"]');
            
            if (!$signal.length) {
                // Create new signal element if it doesn't exist
                const weight = weights[signal] || 0;
                const label = labels[signal] || signal;
                const icon = result.pass ? '✅' : '❌';
                const statusClass = result.pass ? 'pass' : 'fail';
                
                const signalHtml = `
                    <div class="almaseo-health-signal ${statusClass}" data-signal="${signal}">
                        <div class="signal-header">
                            <span class="signal-icon">${icon}</span>
                            <span class="signal-label">
                                ${label}
                                <span class="signal-weight" title="Worth ${weight} points out of 100">(${weight} pts)</span>
                            </span>
                            ${!result.pass ? '<button type="button" class="signal-fix-btn" data-signal="' + signal + '">Fix →</button>' : ''}
                        </div>
                        
                        <div class="signal-bar-wrapper">
                            <div class="signal-bar">
                                <div class="signal-bar-fill ${statusClass}" style="width: ${result.pass ? '100' : '0'}%"></div>
                            </div>
                        </div>
                        
                        <div class="signal-note">
                            ${result.note || ''}
                        </div>
                    </div>
                `;
                
                $signalsContainer.append(signalHtml);
            } else {
                // Update existing signal
                $signal.find('.signal-icon').text(result.pass ? '✅' : '❌');
                $signal.removeClass('pass fail').addClass(result.pass ? 'pass' : 'fail');
                
                const $bar = $signal.find('.signal-bar-fill');
                if ($bar.length) {
                    $bar.removeClass('pass fail')
                        .addClass(result.pass ? 'pass' : 'fail')
                        .css('width', result.pass ? '100%' : '0%');
                }
                
                const $note = $signal.find('.signal-note');
                if ($note.length) {
                    $note.html(result.note || '');
                }
                
                // Show/hide fix button
                const $fixBtn = $signal.find('.signal-fix-btn');
                if (result.pass) {
                    $fixBtn.hide();
                    $signal.find('.signal-fix-helper').hide();
                } else {
                    if (!$fixBtn.length) {
                        // Add fix button if missing
                        $signal.find('.signal-header').append('<button type="button" class="signal-fix-btn" data-signal="' + signal + '">Fix →</button>');
                    } else {
                        $fixBtn.show();
                    }
                }
            }
        });
        
        // Ensure container is visible
        $signalsContainer.show();
    }
    
    /**
     * Show loading state
     */
    function showLoadingState() {
        $('.almaseo-health-container').addClass('loading');
        $('#almaseo-health-recalculate').prop('disabled', true);
    }
    
    /**
     * Hide loading state
     */
    function hideLoadingState() {
        $('.almaseo-health-container').removeClass('loading');
        $('#almaseo-health-recalculate').prop('disabled', false);
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        hideLoadingState();
        
        // Remove existing error
        $('.almaseo-health-error').remove();
        
        // Add error banner
        const $error = $('<div class="almaseo-health-error notice notice-error" style="margin: 10px 0; padding: 10px;">' +
                        '<p>' + message + '</p></div>');
        $('.almaseo-health-container').prepend($error);
        
        // Auto-hide after 5 seconds
        setTimeout(function() {
            $error.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        debugLog('Initializing...');
        
        // Check for health container
        if (!$('.almaseo-health-container').length) {
            debugLog('No health container found, skipping init');
            return;
        }
        
        // Draw initial gauge
        const score = parseInt($('.score-number').first().text()) || 0;
        let color = '#d63638';
        if (score >= 80) {
            color = '#00a32a';
        } else if (score >= 50) {
            color = '#dba617';
        }
        drawGauge(score, color);
        
        // Add unsaved changes banner if not exists
        if (!$('.almaseo-unsaved-banner').length) {
            $('.almaseo-health-container').prepend(
                '<div class="almaseo-unsaved-banner hidden" style="background: #fff3cd; border: 1px solid #ffc107; padding: 10px; margin-bottom: 15px; border-radius: 4px;">' +
                '<span style="color: #856404;">⚠️ Changes not saved—click Update to keep them</span>' +
                '</div>'
            );
        }
        
        // Add custom tooltip for gauge (since title attribute is not working)
        setupGaugeTooltip();
        
        // Monitor field changes with debounce
        let updateTimer;
        $(document).on('input change', '#almaseo_seo_title, #almaseo_seo_description, #almaseo_focus_keyword', function() {
            clearTimeout(updateTimer);
            $('.almaseo-unsaved-banner').removeClass('hidden').fadeIn();
            
            updateTimer = setTimeout(function() {
                const metaFields = {
                    title: $('#almaseo_seo_title').val(),
                    description: $('#almaseo_seo_description').val(),
                    focus_keyword: $('#almaseo_focus_keyword').val()
                };
                refreshHealthPanel(metaFields);
            }, 800);
        });
        
        // Handle recalculate button
        $(document).on('click', '#almaseo-health-recalculate', function(e) {
            e.preventDefault();
            debugLog('Recalculate clicked');
            refreshHealthPanel();
        });
        
        // Hook into WordPress save events
        if (window.wp && window.wp.data) {
            let wasSaving = false;
            wp.data.subscribe(function() {
                const editor = wp.data.select('core/editor');
                if (editor) {
                    const isSaving = editor.isSavingPost();
                    const isAutosaving = editor.isAutosavingPost();
                    
                    if (wasSaving && !isSaving && !isAutosaving) {
                        // Post just finished saving
                        debugLog('Post saved, refreshing health panel');
                        setTimeout(function() {
                            refreshHealthPanel();
                        }, 500);
                    }
                    wasSaving = isSaving;
                }
            });
        }
        
        // Handle Go to Field buttons
        $(document).on('click', '.focus-field', function() {
            const field = $(this).data('field');
            const $field = $('#' + field);
            
            if ($field.length) {
                // Scroll and focus
                $('html, body').animate({
                    scrollTop: $field.offset().top - 100
                }, 500, function() {
                    $field.focus().select();
                    // Add highlight
                    $field.css('box-shadow', '0 0 5px rgba(0, 115, 170, 0.5)');
                    setTimeout(function() {
                        $field.css('box-shadow', '');
                    }, 2000);
                });
            }
        });
        
        // Handle fix button toggles
        $(document).on('click', '.signal-fix-btn', function() {
            const signal = $(this).data('signal');
            const $helper = $('#fix-' + signal);
            
            if ($helper.is(':visible')) {
                $helper.slideUp(200);
            } else {
                $('.signal-fix-helper').slideUp(200);
                $helper.slideDown(200);
            }
        });
        
        // Load keyword suggestions
        function loadKeywordSuggestions() {
            const $container = $('#keyword-suggestions-list');
            if (!$container.length) return;
            
            const postId = $('.almaseo-health-container').data('post-id');
            
            $.post(almaseoHealth.ajaxurl, {
                action: 'almaseo_get_keyword_suggestions',
                post_id: postId,
                nonce: almaseoHealth.nonce
            })
            .done(function(response) {
                if (response.success && response.data.keywords) {
                    let html = '<div class="keyword-chips">';
                    response.data.keywords.forEach(function(keyword) {
                        html += '<span class="keyword-chip">' + keyword.term;
                        if (keyword.volume) {
                            html += ' <small>(' + keyword.volume + ')</small>';
                        }
                        html += '</span> ';
                    });
                    html += '</div>';
                    $container.html(html);
                } else if (!response.data || !response.data.is_connected) {
                    $container.html('<p class="description">Connect to AlmaSEO to unlock live keyword suggestions</p>');
                }
            })
            .fail(function() {
                $container.html('<p class="description">Connect to AlmaSEO to unlock live keyword suggestions</p>');
            });
        }
        
        loadKeywordSuggestions();
        
        debugLog('Initialization complete');
    });
    
    // Cleanup on unload
    $(window).on('beforeunload', function() {
        if (currentRequest && currentRequest.abort) {
            currentRequest.abort();
        }
        window.almaseoHealthInitialized = false;
        debugLog('Cleanup complete');
    });
    
})(jQuery);