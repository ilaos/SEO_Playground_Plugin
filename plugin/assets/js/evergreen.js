/**
 * AlmaSEO Evergreen - AJAX and Interactive Features
 * 
 * @package AlmaSEO
 * @since 1.0.0
 */

(function($) {
    'use strict';

    // State mapping
    const stateLabels = {
        'evergreen': '🟢 Evergreen',
        'watch': '🟡 Watch',
        'stale': '🔴 Stale'
    };

    const stateColors = {
        'evergreen': '#10b981',
        'watch': '#f59e0b',
        'stale': '#ef4444'
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        initRefreshButtons();
        initMarkRefreshedButtons();
        initTooltips();
        initBulkActions();
        initQuickActions();
    });

    /**
     * Initialize refresh buttons
     */
    function initRefreshButtons() {
        $(document).on('click', '.almaseo-eg-refresh', function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const postId = $btn.data('post-id');
            const $row = $btn.closest('tr');
            const $statusCell = $row.find('.column-evergreen .almaseo-eg-pill');
            
            // Update UI
            $btn.prop('disabled', true);
            const originalText = $btn.text();
            $btn.text('Refreshing...');
            $statusCell.addClass('almaseo-eg-loading');
            
            // AJAX request
            $.ajax({
                url: almaseoEvergreen.ajaxurl,
                type: 'POST',
                data: {
                    action: 'almaseo_eg_refresh',
                    post_id: postId,
                    nonce: almaseoEvergreen.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update status pill
                        updateStatusPill($statusCell, response.data);
                        
                        // Show success message
                        showNotice('Post refreshed successfully', 'success');
                        
                        // Reset button
                        $btn.text('✓ Refreshed');
                        setTimeout(function() {
                            $btn.text(originalText).prop('disabled', false);
                        }, 2000);
                    } else {
                        showNotice('Failed to refresh: ' + (response.data || 'Unknown error'), 'error');
                        $btn.text(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    showNotice('Network error occurred', 'error');
                    $btn.text(originalText).prop('disabled', false);
                },
                complete: function() {
                    $statusCell.removeClass('almaseo-eg-loading');
                }
            });
        });
    }

    /**
     * Initialize mark as refreshed buttons
     */
    function initMarkRefreshedButtons() {
        $(document).on('click', '.almaseo-eg-mark-refreshed', function(e) {
            e.preventDefault();
            
            const $btn = $(this);
            const postId = $btn.data('post-id');
            
            // Confirm action
            if (!confirm('Mark this post as refreshed? This will reset its status to Evergreen and update the modified date.')) {
                return;
            }
            
            // Update UI
            $btn.prop('disabled', true);
            const originalText = $btn.html();
            $btn.html('<span class="spinner is-active"></span> Updating...');
            
            // AJAX request
            $.ajax({
                url: almaseoEvergreen.ajaxurl,
                type: 'POST',
                data: {
                    action: 'almaseo_eg_mark_refreshed',
                    post_id: postId,
                    nonce: almaseoEvergreen.nonce
                },
                success: function(response) {
                    if (response.success) {
                        // Update status in UI if on post list
                        const $row = $('tr#post-' + postId);
                        if ($row.length) {
                            const $statusCell = $row.find('.column-evergreen .almaseo-eg-pill');
                            updateStatusPill($statusCell, {
                                state: 'evergreen',
                                state_label: 'Evergreen',
                                state_color: '#10b981',
                                days_since_update: 0,
                                traffic_drop: 0
                            });
                        }
                        
                        // Update timestamp in sidebar if present
                        updateSidebarTimestamp('just now');
                        
                        // Show success message
                        showNotice('Post marked as refreshed', 'success');
                        
                        // Update button
                        $btn.html('✅ Marked as Refreshed');
                        
                        // Don't reload page anymore - we update inline
                        if ($btn.closest('#almaseo_evergreen').length) {
                            // Update the status badge in sidebar
                            updateSidebarStatus('evergreen');
                            setTimeout(function() {
                                $btn.html(originalText).prop('disabled', false);
                            }, 2000);
                        } else {
                            setTimeout(function() {
                                $btn.html(originalText).prop('disabled', false);
                            }, 2000);
                        }
                    } else {
                        showNotice('Failed to mark as refreshed: ' + (response.data || 'Unknown error'), 'error');
                        $btn.html(originalText).prop('disabled', false);
                    }
                },
                error: function() {
                    showNotice('Network error occurred', 'error');
                    $btn.html(originalText).prop('disabled', false);
                }
            });
        });
    }

    /**
     * Initialize tooltips
     */
    function initTooltips() {
        // Custom tooltip implementation
        $(document).on('mouseenter focus', '[data-eg-tooltip]', function() {
            const $elem = $(this);
            const tooltipText = $elem.attr('data-eg-tooltip');
            
            if (!tooltipText) return;
            
            // Remove any existing tooltip
            $('.almaseo-eg-tooltip').remove();
            
            // Create tooltip
            const $tooltip = $('<div class="almaseo-eg-tooltip">' + tooltipText + '</div>');
            $('body').append($tooltip);
            
            // Position tooltip
            const offset = $elem.offset();
            const elemWidth = $elem.outerWidth();
            const elemHeight = $elem.outerHeight();
            const tooltipWidth = $tooltip.outerWidth();
            const tooltipHeight = $tooltip.outerHeight();
            
            let top = offset.top - tooltipHeight - 8;
            let left = offset.left + (elemWidth - tooltipWidth) / 2;
            
            // Adjust if tooltip goes off screen
            if (top < $(window).scrollTop()) {
                top = offset.top + elemHeight + 8;
                $tooltip.addClass('tooltip-bottom');
            }
            
            if (left < 0) {
                left = 10;
            } else if (left + tooltipWidth > $(window).width()) {
                left = $(window).width() - tooltipWidth - 10;
            }
            
            $tooltip.css({
                top: top + 'px',
                left: left + 'px'
            }).addClass('visible');
        });
        
        $(document).on('mouseleave blur', '[data-eg-tooltip]', function() {
            $('.almaseo-eg-tooltip').remove();
        });
    }

    /**
     * Initialize bulk actions
     */
    function initBulkActions() {
        // Add bulk action option
        if ($('#bulk-action-selector-top').length) {
            $('#bulk-action-selector-top, #bulk-action-selector-bottom').each(function() {
                $(this).append('<option value="refresh_evergreen">Refresh Evergreen Status</option>');
            });
        }
        
        // Handle bulk action
        $('#doaction, #doaction2').on('click', function(e) {
            const action = $(this).prev('select').val();
            
            if (action === 'refresh_evergreen') {
                e.preventDefault();
                
                const postIds = [];
                $('tbody input[name="post[]"]:checked').each(function() {
                    postIds.push($(this).val());
                });
                
                if (postIds.length === 0) {
                    alert('Please select at least one post to refresh.');
                    return;
                }
                
                if (!confirm('Refresh Evergreen status for ' + postIds.length + ' selected posts?')) {
                    return;
                }
                
                bulkRefreshPosts(postIds);
            }
        });
    }

    /**
     * Bulk refresh posts
     */
    function bulkRefreshPosts(postIds) {
        const $notice = $('<div class="notice notice-info"><p>Refreshing ' + postIds.length + ' posts... <span class="spinner is-active"></span></p></div>');
        $('.wp-header-end').after($notice);
        
        let completed = 0;
        let errors = 0;
        
        // Process posts in batches
        const batchSize = 5;
        const batches = [];
        
        for (let i = 0; i < postIds.length; i += batchSize) {
            batches.push(postIds.slice(i, i + batchSize));
        }
        
        function processBatch(index) {
            if (index >= batches.length) {
                // All done
                $notice.removeClass('notice-info').addClass(errors > 0 ? 'notice-warning' : 'notice-success');
                $notice.find('p').html('Refresh complete! ' + completed + ' posts updated' + 
                    (errors > 0 ? ', ' + errors + ' errors' : '') + '.');
                
                // Reload page after delay
                setTimeout(function() {
                    location.reload();
                }, 2000);
                return;
            }
            
            const batch = batches[index];
            const promises = [];
            
            batch.forEach(function(postId) {
                const promise = $.ajax({
                    url: almaseoEvergreen.ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'almaseo_eg_refresh',
                        post_id: postId,
                        nonce: almaseoEvergreen.nonce
                    }
                }).done(function(response) {
                    if (response.success) {
                        completed++;
                        
                        // Update UI
                        const $row = $('#post-' + postId);
                        if ($row.length) {
                            const $statusCell = $row.find('.column-evergreen .almaseo-eg-pill');
                            updateStatusPill($statusCell, response.data);
                        }
                    } else {
                        errors++;
                    }
                }).fail(function() {
                    errors++;
                });
                
                promises.push(promise);
            });
            
            // Wait for batch to complete
            $.when.apply($, promises).always(function() {
                $notice.find('p').html('Refreshing posts... (' + (completed + errors) + '/' + postIds.length + ') <span class="spinner is-active"></span>');
                processBatch(index + 1);
            });
        }
        
        processBatch(0);
    }

    /**
     * Initialize quick action buttons
     */
    function initQuickActions() {
        // Analyze button handler
        $(document).on('click', '.almaseo-eg-analyze', function(e) {
            e.preventDefault();
            var button = $(this);
            var postId = button.data('post-id');
            
            button.prop('disabled', true).text('Analyzing...');
            
            $.post(almaseoEvergreen.ajaxurl, {
                action: 'almaseo_eg_quick_analyze',
                post_id: postId,
                nonce: almaseoEvergreen.nonce
            }, function(response) {
                if (response.success) {
                    // Update the row with new data
                    var row = button.closest('tr');
                    row.find('.column-status').html(response.data.status_html);
                    row.find('.column-updated').html(response.data.days_ago + ' days ago');
                    if (response.data.trend !== null) {
                        var trendClass = response.data.trend < 0 ? 'trend-down' : 'trend-up';
                        var trendPrefix = response.data.trend >= 0 ? '+' : '';
                        row.find('.column-trend').html('<span class="trend-value ' + trendClass + '">' + trendPrefix + response.data.trend + '%</span>');
                    }
                    button.text('✓ Analyzed');
                    setTimeout(function() {
                        button.prop('disabled', false).text('Analyze');
                    }, 2000);
                } else {
                    button.prop('disabled', false).text('Error - Try Again');
                }
            }).fail(function() {
                button.prop('disabled', false).text('Error - Try Again');
            });
        });
        
        // Mark as refreshed button handler (if stale posts exist)
        $(document).on('click', '.almaseo-eg-mark-refreshed', function(e) {
            e.preventDefault();
            var button = $(this);
            var postId = button.data('post-id');
            
            button.prop('disabled', true).text('Updating...');
            
            $.post(almaseoEvergreen.ajaxurl, {
                action: 'almaseo_eg_mark_refreshed',
                post_id: postId,
                nonce: almaseoEvergreen.nonce
            }, function(response) {
                if (response.success) {
                    button.text('✓ Updated');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    button.prop('disabled', false).text('Error - Try Again');
                }
            }).fail(function() {
                button.prop('disabled', false).text('Error - Try Again');
            });
        });
    }

    /**
     * Update status pill
     */
    function updateStatusPill($pill, data) {
        // Update class
        $pill.removeClass('almaseo-eg-evergreen almaseo-eg-watch almaseo-eg-stale almaseo-eg-unknown');
        $pill.addClass('almaseo-eg-' + data.state);
        
        // Update text
        $pill.html(stateLabels[data.state] || '⚪ Unknown');
        
        // Update tooltip
        let tooltip = 'Last updated: ' + (data.days_since_update || 0) + ' days ago';
        if (data.traffic_drop !== null && data.traffic_drop !== undefined) {
            tooltip += ' • Trend: ' + (data.traffic_drop >= 0 ? '+' : '') + data.traffic_drop + '%';
        }
        $pill.attr('data-eg-tooltip', tooltip);
        
        // Add animation
        $pill.addClass('almaseo-eg-updated');
        setTimeout(function() {
            $pill.removeClass('almaseo-eg-updated');
        }, 1000);
    }

    /**
     * Show admin notice
     */
    function showNotice(message, type) {
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        
        if ($('.wp-header-end').length) {
            $('.wp-header-end').after($notice);
        } else {
            $('#wpbody-content').prepend($notice);
        }
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
        
        // Make dismissible
        $notice.on('click', '.notice-dismiss', function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }

    /**
     * Format relative time
     */
    function formatRelativeTime(days) {
        if (days === 0) {
            return 'today';
        } else if (days === 1) {
            return '1 day ago';
        } else if (days < 7) {
            return days + ' days ago';
        } else if (days < 30) {
            return Math.floor(days / 7) + ' weeks ago';
        } else if (days < 365) {
            return Math.floor(days / 30) + ' months ago';
        } else {
            return Math.floor(days / 365) + ' years ago';
        }
    }
    
    /**
     * Update sidebar timestamp
     */
    function updateSidebarTimestamp(timeText) {
        const $timestamp = $('#almaseo-eg-last-checked');
        if ($timestamp.length) {
            if (timeText === 'just now') {
                $timestamp.text(almaseoEvergreen.i18n.last_recalculated_just_now || 'Last recalculated: just now');
            } else {
                $timestamp.text('Last recalculated: ' + timeText + ' ago');
            }
            
            // Add a fade effect to show it updated
            $timestamp.fadeOut(100).fadeIn(300);
        }
    }
    
    /**
     * Update sidebar status badge
     */
    function updateSidebarStatus(newState) {
        const $metabox = $('#almaseo_evergreen');
        if (!$metabox.length) return;
        
        // Find the status badge
        const $statusBadge = $metabox.find('span[style*="border-radius: 20px"]').first();
        if (!$statusBadge.length) return;
        
        // Update badge based on state
        switch(newState) {
            case 'evergreen':
                $statusBadge
                    .css({
                        'background': '#d4edda',
                        'color': '#155724'
                    })
                    .html('🟢 ' + (almaseoEvergreen.i18n.evergreen || 'Evergreen'));
                break;
            case 'watch':
                $statusBadge
                    .css({
                        'background': '#fff3cd',
                        'color': '#856404'
                    })
                    .html('🟡 ' + (almaseoEvergreen.i18n.watch || 'Watch'));
                break;
            case 'stale':
                $statusBadge
                    .css({
                        'background': '#f8d7da',
                        'color': '#721c24'
                    })
                    .html('🔴 ' + (almaseoEvergreen.i18n.stale || 'Stale'));
                break;
        }
        
        // Add animation
        $statusBadge.addClass('almaseo-eg-updated');
        setTimeout(function() {
            $statusBadge.removeClass('almaseo-eg-updated');
        }, 1000);
    }
    
    /**
     * Handle Analyze Now button updates
     */
    $(document).on('click', '#almaseo-eg-analyze-now', function() {
        const $btn = $(this);
        const postId = $btn.data('post-id');
        
        $btn.prop('disabled', true).text(almaseoEvergreen.i18n.analyzing || 'Analyzing...');
        
        $.post(almaseoEvergreen.ajaxurl, {
            action: 'almaseo_eg_analyze_post',
            post_id: postId,
            nonce: almaseoEvergreen.nonce
        }, function(response) {
            if (response.success) {
                // Update timestamp
                updateSidebarTimestamp('just now');
                
                // Show success
                $btn.text('✅ ' + (almaseoEvergreen.i18n.analysis_complete || 'Analysis Complete!'));
                
                // Note: Status update would require more data from the response
                // For now, we'll rely on the existing reload behavior
                setTimeout(function() {
                    location.reload();
                }, 1000);
            } else {
                $btn.prop('disabled', false).text('🔄 ' + (almaseoEvergreen.i18n.analyze_now || 'Analyze Now'));
                alert(response.data || almaseoEvergreen.i18n.error_analyzing || 'Error analyzing post');
            }
        }).fail(function() {
            $btn.prop('disabled', false).text('🔄 ' + (almaseoEvergreen.i18n.analyze_now || 'Analyze Now'));
            alert(almaseoEvergreen.i18n.network_error || 'Network error occurred');
        });
    });
    
    /**
     * Render Evergreen Trend Chart
     * Moved from inline script to avoid CSP issues
     */
    function almaseoRenderTrendChart() {
        var chartContainer = document.getElementById('almaseo-eg-trend-chart');
        if (!chartContainer) {
            return;
        }
        
        // Get the data directly from the attribute
        var weeklyDataAttr = chartContainer.getAttribute('data-weekly') || '';
        var weeklyData;
        try {
            // browsers normally give decoded values, but just in case:
            weeklyData = JSON.parse(weeklyDataAttr);
        } catch (e) {
            try {
                weeklyData = JSON.parse(weeklyDataAttr.replace(/&quot;/g, '"').replace(/&#039;/g, "'"));
            } catch (e2) {
                console.error('Failed to parse chart data:', e2);
                chartContainer.textContent = 'No trend data available yet.';
                return;
            }
        }
        
        // bail out visibly if the array is empty or everything is zero
        if (!Array.isArray(weeklyData) || weeklyData.length === 0 ||
            weeklyData.every(w => ((w.evergreen||0)+(w.watch||0)+(w.stale||0)+(w.unanalyzed||0)) === 0)) {
            chartContainer.textContent = 'No trend data available yet. Click "Analyze All Posts" above to analyze your content.';
            return;
        }
        
        if (weeklyData && weeklyData.length > 0) {
            // Clear the container using DOM methods
            while (chartContainer.firstChild) {
                chartContainer.removeChild(chartContainer.firstChild);
            }
            
            // Find max value for scaling
            var maxVal = 0;
            weeklyData.forEach(function(week) {
                var total = (week.evergreen || 0) + (week.watch || 0) + (week.stale || 0);
                if (total > maxVal) maxVal = total;
            });
            
            // If all data is zero, show the unanalyzed posts as a bar
            if (maxVal === 0) {
                weeklyData.forEach(function(week) {
                    if (week.unanalyzed > 0) {
                        maxVal = Math.max(maxVal, week.unanalyzed);
                    }
                });
            }
            
            // Create chart container using DOM methods
            var chartWrapper = document.createElement('div');
            chartWrapper.className = 'almaseo-eg-simple-chart';
            
            var chartBars = document.createElement('div');
            chartBars.className = 'chart-bars';
            chartBars.style.cssText = 'display: flex; align-items: flex-end; height: 200px; gap: 10px; padding: 20px 0;';
            
            weeklyData.forEach(function(week, index) {
                var evergreenHeight = maxVal > 0 ? ((week.evergreen || 0) / maxVal * 100) : 0;
                var watchHeight = maxVal > 0 ? ((week.watch || 0) / maxVal * 100) : 0;
                var staleHeight = maxVal > 0 ? ((week.stale || 0) / maxVal * 100) : 0;
                var totalHeight = evergreenHeight + watchHeight + staleHeight;
                
                var barGroup = document.createElement('div');
                barGroup.className = 'chart-bar-group';
                barGroup.style.cssText = 'flex: 1; display: flex; flex-direction: column; align-items: center; height: 100%;';
                
                var barContainer = document.createElement('div');
                barContainer.className = 'chart-bar';
                barContainer.style.cssText = 'width: 100%; display: flex; flex-direction: column-reverse; justify-content: flex-start; height: 100%;';
                
                // Calculate actual pixel heights based on 200px total height
                var pixelMultiplier = totalHeight > 0 ? (200 / 100) : 0;
                
                if (staleHeight > 0) {
                    var staleBar = document.createElement('div');
                    staleBar.className = 'bar-segment bar-segment-stale';
                    staleBar.style.cssText = 'background: #d63638; height: ' + (staleHeight * pixelMultiplier) + 'px;';
                    staleBar.title = 'Stale: ' + (week.stale || 0);
                    barContainer.appendChild(staleBar);
                }
                if (watchHeight > 0) {
                    var watchBar = document.createElement('div');
                    watchBar.className = 'bar-segment bar-segment-watch';
                    watchBar.style.cssText = 'background: #dba617; height: ' + (watchHeight * pixelMultiplier) + 'px;';
                    watchBar.title = 'Watch: ' + (week.watch || 0);
                    barContainer.appendChild(watchBar);
                }
                if (evergreenHeight > 0) {
                    var evergreenBar = document.createElement('div');
                    evergreenBar.className = 'bar-segment bar-segment-evergreen';
                    evergreenBar.style.cssText = 'background: #00a32a; height: ' + (evergreenHeight * pixelMultiplier) + 'px;';
                    evergreenBar.title = 'Evergreen: ' + (week.evergreen || 0);
                    barContainer.appendChild(evergreenBar);
                }
                
                // Show unanalyzed posts as gray bars if no analyzed posts
                if (maxVal > 0 && evergreenHeight === 0 && watchHeight === 0 && staleHeight === 0 && week.unanalyzed > 0) {
                    var unanalyzedHeight = (week.unanalyzed / maxVal * 100);
                    var unanalyzedBar = document.createElement('div');
                    unanalyzedBar.className = 'bar-segment bar-segment-unanalyzed';
                    unanalyzedBar.style.cssText = 'background: #888; height: ' + (unanalyzedHeight * pixelMultiplier) + 'px;';
                    unanalyzedBar.title = 'Unanalyzed: ' + (week.unanalyzed || 0);
                    barContainer.appendChild(unanalyzedBar);
                }
                
                barGroup.appendChild(barContainer);
                
                // Add week label
                var weekLabel = document.createElement('div');
                weekLabel.className = 'chart-week-label';
                weekLabel.style.cssText = 'margin-top: 8px; font-size: 11px; color: #666;';
                weekLabel.textContent = 'W' + (index + 1);
                barGroup.appendChild(weekLabel);
                
                chartBars.appendChild(barGroup);
            });
            
            chartWrapper.appendChild(chartBars);
            chartContainer.appendChild(chartWrapper);
        } else {
            // No data available - use DOM methods for this too
            while (chartContainer.firstChild) {
                chartContainer.removeChild(chartContainer.firstChild);
            }
            var noDataDiv = document.createElement('div');
            noDataDiv.style.cssText = 'padding: 40px; text-align: center; color: #666;';
            noDataDiv.textContent = 'No trend data available yet. Click "Analyze All Posts" above to analyze your content.';
            chartContainer.appendChild(noDataDiv);
        }
    }
    
    // Expose chart function to window for external calls
    window.almaseoRenderTrendChart = almaseoRenderTrendChart;
    
    // Call chart render on document ready
    $(function() {
        almaseoRenderTrendChart();
    });

})(jQuery);