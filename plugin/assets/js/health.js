/**
 * AlmaSEO Health Score - JavaScript
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
        const scoreAngle = (score / 100) * Math.PI * 1.6; // 1.6 PI = 288 degrees
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
     * Live update function
     */
    function performLiveUpdate(metaFields) {
        const postId = $('.almaseo-health-container').data('post-id');
        
        // Show unsaved changes indicator
        if (metaFields) {
            $('.almaseo-unsaved-banner').removeClass('hidden').fadeIn();
        }
        
        // Perform AJAX update
        $.post(almaseoHealth.ajaxurl, {
            action: 'almaseo_health_live_update',
            post_id: postId,
            meta_fields: metaFields || {},
            nonce: almaseoHealth.nonce
        })
        .done(function(response) {
            if (response.success) {
                updateHealthDisplay(response.data);
            }
        });
    }
    
    /**
     * Refresh health panel with cache busting and error handling
     */
    window.almaseoHealth = window.almaseoHealth || {};
    window.almaseoHealth.refreshToken = 0;
    
    window.almaseoHealth.refresh = function(options) {
        options = options || {};
        const postId = $('.almaseo-health-container').data('post-id');
        const token = ++window.almaseoHealth.refreshToken;
        
        // Clear any error banner
        $('.almaseo-error-banner').remove();
        
        // Show loading indicator
        $('.almaseo-health-container').addClass('refreshing');
        
        $.ajax({
            url: almaseoHealth.ajaxurl + '?action=almaseo_health_refresh&ts=' + Date.now(),
            method: 'POST',
            cache: false,
            headers: {
                'Cache-Control': 'no-cache, no-store, must-revalidate',
                'Pragma': 'no-cache'
            },
            data: {
                action: 'almaseo_health_refresh',
                post_id: postId,
                reason: options.reason || 'manual',
                token: token,
                nonce: almaseoHealth.nonce
            }
        })
        .done(function(response) {
            // Ignore stale responses
            if (token !== window.almaseoHealth.refreshToken) {
                return;
            }
            
            if (response.success) {
                updateHealthDisplay(response.data);
                $('.almaseo-unsaved-banner').fadeOut();
            } else {
                showInlineError('Could not refresh checks. Try Recalculate.');
            }
        })
        .fail(function(xhr) {
            // Ignore stale responses
            if (token !== window.almaseoHealth.refreshToken) {
                return;
            }
            
            showInlineError('Could not refresh checks. Try Recalculate.');
            console.log('[AlmaSEO Health] Refresh failed:', xhr.status, xhr.responseText ? xhr.responseText.substring(0, 100) : 'No response');
        })
        .always(function() {
            $('.almaseo-health-container').removeClass('refreshing');
        });
    };
    
    /**
     * Show inline error banner
     */
    function showInlineError(message) {
        // Remove any existing error banner
        $('.almaseo-error-banner').remove();
        
        // Add new error banner at top of health container
        const errorHtml = '<div class="almaseo-error-banner" style="background: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; padding: 10px; margin-bottom: 15px; border-radius: 4px;">' +
            '<span>⚠️ ' + message + '</span>' +
            '</div>';
        
        $('.almaseo-health-container').prepend(errorHtml);
        
        // Auto-hide after 10 seconds
        setTimeout(function() {
            $('.almaseo-error-banner').fadeOut();
        }, 10000);
    }
    
    /**
     * Update health display without page refresh
     */
    function updateHealthDisplay(data) {
        // Update score
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
        
        $('.almaseo-health-score-text').removeClass('poor good excellent').addClass(colorClass);
        
        // Redraw gauge
        drawGauge(data.score, colorHex);
        
        // Update signals
        let passCount = 0;
        let failCount = 0;
        
        $.each(data.breakdown, function(signal, result) {
            const $signal = $('.almaseo-health-signal').filter(function() {
                return $(this).find('.signal-fix-btn').data('signal') === signal;
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
                $signal.find('.signal-note').contents().first().replaceWith(result.note);
                
                // Show/hide fix button
                if (result.pass) {
                    $signal.find('.signal-fix-btn').hide();
                    $signal.find('.signal-fix-helper').hide();
                } else {
                    $signal.find('.signal-fix-btn').show();
                }
            }
            
            // Count passes and fails
            if (result.pass) {
                passCount++;
            } else {
                failCount++;
            }
        });
        
        // Update counters
        $('.health-stat-item:contains("Passed") strong').text(passCount);
        $('.health-stat-item:contains("Issues") strong').text(failCount);
        
        // Update SERP preview if available
        if (data.serp_preview) {
            $('.serp-title').text(data.serp_preview.title);
            $('.serp-description').text(data.serp_preview.description);
        }
        
        // Update timestamp
        $('.updated-time').text(data.updated_at);
    }
    
    /**
     * Check for search engine blocking
     */
    function checkSearchEngineBlocking() {
        // Check if we have the robots signal data
        $('.almaseo-health-signal').each(function() {
            const $signal = $(this);
            const signalType = $signal.find('.signal-fix-btn').data('signal');
            
            if (signalType === 'robots') {
                const noteText = $signal.find('.signal-note').text();
                
                // Check if WordPress discourage setting is mentioned
                if (noteText.includes('Discourage search engines') || noteText.includes('search engines are blocked')) {
                    // Add critical warning if not already present
                    if (!$('.almaseo-search-engine-critical-warning').length) {
                        const warningHtml = '<div class="almaseo-search-engine-critical-warning" style="' +
                            'background: linear-gradient(135deg, #dc3232, #c92c2c); ' +
                            'color: white; ' +
                            'padding: 15px; ' +
                            'margin: -20px -20px 20px -20px; ' +
                            'border-radius: 4px 4px 0 0; ' +
                            'display: flex; ' +
                            'align-items: center; ' +
                            'animation: pulse-red 2s infinite; ' +
                            'box-shadow: 0 2px 8px rgba(220,50,50,0.3);">' +
                            '<span class="dashicons dashicons-warning" style="font-size: 24px; margin-right: 12px; animation: shake 0.5s infinite;"></span>' +
                            '<div style="flex: 1;">' +
                            '<strong style="font-size: 15px; display: block; margin-bottom: 3px;">🚨 CRITICAL: Your Site is Hidden from Search Engines!</strong>' +
                            '<span style="font-size: 13px; opacity: 0.95;">WordPress "Discourage search engines" is enabled. Your content will NOT appear in Google!</span>' +
                            '</div>' +
                            '<a href="' + almaseoHealth.readingSettingsUrl + '" class="button" style="background: white; color: #dc3232; border: none; font-weight: bold;">Fix Immediately →</a>' +
                            '</div>';
                        
                        $('.almaseo-health-container').prepend(warningHtml);
                    }
                    
                    // Make the robots signal more prominent
                    $signal.css('border', '2px solid #dc3232').css('background', '#fff5f5');
                }
            }
        });
    }
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        // Draw initial gauge
        const score = $('#almaseo-health-score-value').val();
        const color = $('#almaseo-health-color-hex').val();
        if (score && color) {
            drawGauge(parseInt(score), color);
        }
        
        // Check for search engine blocking
        setTimeout(checkSearchEngineBlocking, 500);
        
        // Add unsaved changes banner with improved messaging
        if (!$('.almaseo-unsaved-banner').length) {
            $('.almaseo-health-container').prepend(
                '<div class="almaseo-unsaved-banner hidden" style="background: #f0f8ff; border: 1px solid #2271b1; padding: 12px 15px; margin-bottom: 15px; border-radius: 4px; display: flex; align-items: center; justify-content: space-between;">' +
                '<div style="display: flex; align-items: center;">' +
                '<span class="dashicons dashicons-info" style="color: #2271b1; margin-right: 8px; font-size: 20px;"></span>' +
                '<span style="color: #135e96; font-weight: 500;">' + (almaseoHealth.i18n.unsaved_changes_improved || 'You have unsaved changes. Click "Update" to save your SEO improvements.') + '</span>' +
                '</div>' +
                '<button type="button" class="button button-small dismiss-unsaved" style="margin-left: 15px;">Dismiss</button>' +
                '</div>'
            );
        }
        
        // Handle dismiss button
        $(document).on('click', '.dismiss-unsaved', function() {
            $('.almaseo-unsaved-banner').fadeOut();
        });
        
        // Track original values
        const originalValues = {
            title: $('#almaseo_seo_title').val() || '',
            description: $('#almaseo_seo_description').val() || '',
            focus_keyword: $('#almaseo_focus_keyword').val() || ''
        };
        
        // Monitor SEO field changes with debounce and field indicators
        let updateTimer;
        let hasChanges = false;
        
        $('#almaseo_seo_title, #almaseo_seo_description, #almaseo_focus_keyword').on('input change', function() {
            const $field = $(this);
            const fieldId = $field.attr('id');
            const fieldName = fieldId.replace('almaseo_seo_', '').replace('almaseo_', '');
            const currentValue = $field.val() || '';
            const originalValue = originalValues[fieldName] || '';
            
            // Check if value changed from original
            if (currentValue !== originalValue) {
                // Add visual indicator to the field
                $field.css('border-color', '#2271b1').css('box-shadow', '0 0 0 1px #2271b1');
                
                // Add unsaved indicator next to field label if not already there
                const $label = $field.closest('.almaseo-field-wrapper').find('label');
                if ($label.length && !$label.find('.unsaved-indicator').length) {
                    $label.append('<span class="unsaved-indicator" style="color: #2271b1; margin-left: 5px; font-size: 11px; font-weight: normal;">(unsaved)</span>');
                }
                
                hasChanges = true;
            } else {
                // Remove visual indicator if back to original
                $field.css('border-color', '').css('box-shadow', '');
                $field.closest('.almaseo-field-wrapper').find('.unsaved-indicator').remove();
                
                // Check if any fields still have changes
                hasChanges = false;
                $('#almaseo_seo_title, #almaseo_seo_description, #almaseo_focus_keyword').each(function() {
                    const id = $(this).attr('id');
                    const name = id.replace('almaseo_seo_', '').replace('almaseo_', '');
                    if (($(this).val() || '') !== (originalValues[name] || '')) {
                        hasChanges = true;
                        return false;
                    }
                });
            }
            
            clearTimeout(updateTimer);
            updateTimer = setTimeout(function() {
                const metaFields = {
                    title: $('#almaseo_seo_title').val(),
                    description: $('#almaseo_seo_description').val(),
                    focus_keyword: $('#almaseo_focus_keyword').val()
                };
                performLiveUpdate(metaFields);
            }, 800); // Debounce 800ms
        });
        
        // Hook into WordPress save events (Gutenberg)
        if (window.wp && window.wp.data && window.wp.data.select('core/editor')) {
            let wasSaving = false;
            const unsubscribe = wp.data.subscribe(function() {
                const editor = wp.data.select('core/editor');
                if (editor) {
                    const isSaving = editor.isSavingPost();
                    const isAutosaving = editor.isAutosavingPost();
                    const didSave = editor.didPostSaveRequestSucceed && editor.didPostSaveRequestSucceed();
                    
                    if (wasSaving && !isSaving && !isAutosaving && didSave) {
                        // Post just finished saving successfully
                        $('.almaseo-unsaved-banner').fadeOut();
                        
                        // Reset original values to current values
                        originalValues.title = $('#almaseo_seo_title').val() || '';
                        originalValues.description = $('#almaseo_seo_description').val() || '';
                        originalValues.focus_keyword = $('#almaseo_focus_keyword').val() || '';
                        
                        // Remove all unsaved indicators
                        $('#almaseo_seo_title, #almaseo_seo_description, #almaseo_focus_keyword').css('border-color', '').css('box-shadow', '');
                        $('.unsaved-indicator').remove();
                        
                        setTimeout(function() {
                            window.almaseoHealth.refresh({ reason: 'post-saved' });
                        }, 500);
                    }
                    wasSaving = isSaving || isAutosaving;
                }
            });
            
            // Store unsubscribe function for cleanup
            window.almaseoHealth.unsubscribe = unsubscribe;
        }
        
        // Classic Editor / Elementor - refresh on page load/show
        if (!window.wp || !window.wp.data || !window.wp.data.select('core/editor')) {
            // Initial refresh on page load
            window.almaseoHealth.refresh({ reason: 'page-load' });
            
            // Refresh when page becomes visible again
            document.addEventListener('visibilitychange', function() {
                if (document.visibilityState === 'visible') {
                    window.almaseoHealth.refresh({ reason: 'returned-visible' });
                }
            });
            
            // Also handle pageshow event for back/forward navigation
            window.addEventListener('pageshow', function(event) {
                if (event.persisted) {
                    window.almaseoHealth.refresh({ reason: 'page-show' });
                }
            });
        }
        
        // Add tooltip to score gauge
        $('.almaseo-health-score-text').attr('title', almaseoHealth.i18n.score_tooltip || 'Score calculated from 10 SEO signals');
        
        // Initialize tooltips
        if ($.fn.tooltip) {
            $('.score-tooltip, .signal-weight, .signal-help').tooltip();
        }
        
        // Handle recalculate button
        $('#almaseo-health-recalculate').on('click', function() {
            const $btn = $(this);
            const $container = $('.almaseo-health-container');
            const postId = $container.data('post-id');
            
            // Disable button and show loading
            $btn.prop('disabled', true).addClass('updating');
            $btn.find('.dashicons').addClass('spin');
            $btn.append('<span class="loading-text"> ' + almaseoHealth.i18n.recalculating + '</span>');
            
            // AJAX request
            $.post(almaseoHealth.ajaxurl, {
                action: 'almaseo_health_recalculate',
                post_id: postId,
                nonce: almaseoHealth.nonce
            })
            .done(function(response) {
                if (response.success) {
                    const data = response.data;
                    
                    // Update score display
                    $('.score-number').text(data.score);
                    
                    // Update color class
                    let colorClass = 'red';
                    let colorHex = '#d63638';
                    if (data.score >= 80) {
                        colorClass = 'green';
                        colorHex = '#00a32a';
                    } else if (data.score >= 50) {
                        colorClass = 'yellow';
                        colorHex = '#dba617';
                    }
                    
                    $('.almaseo-health-score-text').removeClass('red yellow green').addClass(colorClass);
                    $('.health-status').removeClass('health-red health-yellow health-green').addClass('health-' + colorClass);
                    
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
                    
                    // Update signals
                    $.each(data.breakdown, function(signal, result) {
                        const $signal = $('.almaseo-health-signal').filter(function() {
                            return $(this).find('.signal-fix-btn').data('signal') === signal;
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
                            $signal.find('.signal-note').contents().first().replaceWith(result.note);
                            
                            // Show/hide fix button
                            if (result.pass) {
                                $signal.find('.signal-fix-btn').hide();
                                $signal.find('.signal-fix-helper').hide();
                            } else {
                                $signal.find('.signal-fix-btn').show();
                            }
                        }
                    });
                    
                    // Update timestamp
                    $('.updated-time').text(data.updated_at);
                    
                    // Trigger event for compact view sync
                    $(document).trigger('health-score-updated', [data]);
                    
                    // Show success message
                    $btn.find('.loading-text').text(' ' + almaseoHealth.i18n.recalculated);
                    
                    setTimeout(function() {
                        $btn.find('.loading-text').remove();
                        $btn.prop('disabled', false).removeClass('updating');
                        $btn.find('.dashicons').removeClass('spin');
                    }, 2000);
                }
            })
            .fail(function(xhr) {
                // Show inline error instead of alert
                showInlineError(almaseoHealth.i18n.error || 'Could not refresh checks. Try again.');
                console.log('[AlmaSEO Health] Recalculate failed:', xhr.status, xhr.responseText ? xhr.responseText.substring(0, 100) : 'No response');
                
                $btn.find('.loading-text').remove();
                $btn.prop('disabled', false).removeClass('updating');
                $btn.find('.dashicons').removeClass('spin');
            });
        });
        
        // Handle fix buttons
        $('.signal-fix-btn').on('click', function() {
            const signal = $(this).data('signal');
            const $helper = $('#fix-' + signal);
            
            // Toggle helper visibility
            if ($helper.is(':visible')) {
                $helper.slideUp(200);
            } else {
                // Hide all other helpers
                $('.signal-fix-helper').slideUp(200);
                $helper.slideDown(200);
            }
        });
        
        // Handle focus field buttons - improved version
        $('.focus-field').on('click', function() {
            const field = $(this).data('field');
            
            if (field === 'title') {
                // Focus post title
                const $title = $('#title');
                if ($title.length) {
                    // Scroll to title
                    $('html, body').animate({
                        scrollTop: $title.offset().top - 100
                    }, 500, function() {
                        // Focus and highlight
                        $title.focus().select();
                        // Add visual indicator
                        $title.css('box-shadow', '0 0 5px rgba(0, 115, 170, 0.5)');
                        setTimeout(function() {
                            $title.css('box-shadow', '');
                        }, 2000);
                    });
                }
            } else if (field === 'almaseo_focus_keyword') {
                // Focus keyword field - might be in different tab
                const $field = $('#' + field);
                if ($field.length) {
                    // Check if field is in SEO Health tab
                    const $healthTab = $('[href="#tab-seo-health"]');
                    if ($healthTab.length && !$healthTab.hasClass('active')) {
                        $healthTab.click();
                    }
                    
                    // Scroll and focus with delay for tab switch
                    setTimeout(function() {
                        $('html, body').animate({
                            scrollTop: $field.offset().top - 100
                        }, 500, function() {
                            $field.focus().select();
                            // Visual indicator
                            $field.css('box-shadow', '0 0 5px rgba(0, 115, 170, 0.5)');
                            setTimeout(function() {
                                $field.css('box-shadow', '');
                            }, 2000);
                        });
                    }, 100);
                }
            } else {
                // Handle other SEO fields
                const $field = $('#' + field);
                if ($field.length) {
                    // Determine which tab contains the field
                    let $targetTab = null;
                    
                    // Check Basic SEO tab
                    if ($field.closest('#tab-basic-seo').length) {
                        $targetTab = $('[href="#tab-basic-seo"]');
                    }
                    // Check Schema & Meta tab
                    else if ($field.closest('#tab-schema-meta').length) {
                        $targetTab = $('[href="#tab-schema-meta"]');
                    }
                    
                    // Switch to correct tab if needed
                    if ($targetTab && $targetTab.length && !$targetTab.hasClass('active')) {
                        $targetTab.click();
                    }
                    
                    // Scroll and focus with delay for tab switch
                    setTimeout(function() {
                        $('html, body').animate({
                            scrollTop: $field.offset().top - 100
                        }, 500, function() {
                            $field.focus().select();
                            // Visual indicator
                            $field.css('box-shadow', '0 0 5px rgba(0, 115, 170, 0.5)');
                            setTimeout(function() {
                                $field.css('box-shadow', '');
                            }, 2000);
                        });
                    }, 100);
                }
            }
        });
        
        // Handle draft meta description button
        $('.draft-meta-desc').on('click', function() {
            const $btn = $(this);
            const postId = $('.almaseo-health-container').data('post-id');
            
            $btn.prop('disabled', true);
            
            $.post(almaseoHealth.ajaxurl, {
                action: 'almaseo_health_draft_meta_desc',
                post_id: postId,
                nonce: almaseoHealth.nonce
            })
            .done(function(response) {
                if (response.success && response.data.draft) {
                    // Fill meta description field
                    $('#almaseo_seo_description').val(response.data.draft);
                    
                    // Show success message
                    $btn.text(almaseoHealth.i18n.draft_copied);
                    
                    // Focus field
                    $('#almaseo_seo_description').focus();
                    
                    setTimeout(function() {
                        $btn.text('Draft from First Paragraph').prop('disabled', false);
                    }, 2000);
                }
            })
            .fail(function() {
                $btn.prop('disabled', false);
            });
        });
        
        // Handle jump to robots
        $('.jump-to-robots').on('click', function() {
            // Open Schema & Meta tab
            const $tab = $('[href="#tab-schema-meta"]');
            if ($tab.length) {
                $tab.click();
                
                // Scroll to robots section
                setTimeout(function() {
                    const $robots = $('#almaseo_robots_meta');
                    if ($robots.length) {
                        $('html, body').animate({
                            scrollTop: $robots.offset().top - 100
                        }, 500);
                    }
                }, 100);
            }
        });
        
        // Handle open media modal
        $('.open-media-modal').on('click', function() {
            if (wp && wp.media) {
                const frame = wp.media({
                    title: 'Edit Featured Image',
                    multiple: false
                });
                
                frame.on('select', function() {
                    // Featured image alt text would be edited in the media modal
                    // Just open it for now
                });
                
                frame.open();
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
                } else if (!response.data.is_connected) {
                    $container.html('<p class="description">' + almaseoHealth.i18n.connect_for_keywords + '</p>');
                } else {
                    $container.html('<p class="description">' + (response.data.message || almaseoHealth.i18n.no_keywords) + '</p>');
                }
            })
            .fail(function() {
                const apiKey = $container.data('api-key');
                if (!apiKey) {
                    $container.html('<p class="description">' + almaseoHealth.i18n.connect_for_keywords + '</p>');
                } else {
                    $container.html('<p class="error">' + almaseoHealth.i18n.keyword_error + '</p>');
                }
            });
        }
        
        // Load keyword suggestions on page load
        loadKeywordSuggestions();
        
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
            .keyword-chip {
                display: inline-block;
                background: #e0f2ff;
                border: 1px solid #0073aa;
                border-radius: 15px;
                padding: 4px 12px;
                margin: 2px;
                font-size: 13px;
            }
            .keyword-chip small {
                color: #666;
                font-size: 11px;
            }
            .almaseo-health-container.refreshing {
                opacity: 0.6;
                pointer-events: none;
            }
        `;
        document.head.appendChild(style);
    });
    
})(jQuery);