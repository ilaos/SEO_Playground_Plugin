/**
 * AlmaSEO SEO Playground - New Features JavaScript
 * Version: 2.2.1
 * Description: JavaScript for new features including metadata history, keyword suggestions, and more
 */

(function($) {
    'use strict';

    // Wait for DOM ready
    $(document).ready(function() {
        
        // ====================================
        // Metadata History Toggle
        // ====================================
        $('#toggle-metadata-history').on('click', function() {
            const $btn = $(this);
            const $content = $('#metadata-history-content');
            const $icon = $btn.find('.toggle-icon');
            const $text = $btn.find('.toggle-text');
            
            if ($content.is(':visible')) {
                $content.slideUp(300);
                $btn.removeClass('active');
                $icon.text('▼');
                $text.text('Show History');
            } else {
                $content.slideDown(300);
                $btn.addClass('active');
                $icon.text('▲');
                $text.text('Hide History');
            }
        });
        
        // ====================================
        // Restore Metadata Fields
        // ====================================
        $('.restore-btn').on('click', function() {
            const $btn = $(this);
            const field = $btn.data('field');
            const value = $btn.data('value');
            
            let targetField;
            switch(field) {
                case 'title':
                    targetField = $('#almaseo_seo_title');
                    break;
                case 'description':
                    targetField = $('#almaseo_seo_description');
                    break;
                case 'keyword':
                    targetField = $('#almaseo_focus_keyword');
                    break;
            }
            
            if (targetField) {
                // Confirm restoration
                if (confirm('Are you sure you want to restore this ' + field + '?')) {
                    targetField.val(value).trigger('input');
                    
                    // Show success message
                    showNotification('✅ ' + field.charAt(0).toUpperCase() + field.slice(1) + ' restored successfully', 'success');
                    
                    // Highlight the field briefly
                    targetField.css('background-color', '#d4edda');
                    setTimeout(function() {
                        targetField.css('background-color', '');
                    }, 2000);
                }
            }
        });
        
        // ====================================
        // Restore All Metadata
        // ====================================
        $('.restore-all-btn').on('click', function() {
            const $btn = $(this);
            const index = $btn.data('index');
            
            if (confirm('Are you sure you want to restore all fields from this version?')) {
                const $entry = $btn.closest('.history-entry');
                
                // Find all restore buttons in this entry
                $entry.find('.restore-btn').each(function() {
                    const field = $(this).data('field');
                    const value = $(this).data('value');
                    
                    switch(field) {
                        case 'title':
                            $('#almaseo_seo_title').val(value).trigger('input');
                            break;
                        case 'description':
                            $('#almaseo_seo_description').val(value).trigger('input');
                            break;
                        case 'keyword':
                            $('#almaseo_focus_keyword').val(value).trigger('input');
                            break;
                    }
                });
                
                showNotification('✅ All fields restored successfully', 'success');
                
                // Highlight all fields
                $('#almaseo_seo_title, #almaseo_seo_description, #almaseo_focus_keyword').css('background-color', '#d4edda');
                setTimeout(function() {
                    $('#almaseo_seo_title, #almaseo_seo_description, #almaseo_focus_keyword').css('background-color', '');
                }, 2000);
            }
        });
        
        // ====================================
        // Keyword Chip Click Handler
        // ====================================
        $('.keyword-chip').on('click', function() {
            const keyword = $(this).data('keyword');
            const $focusField = $('#almaseo_focus_keyword');
            
            // Set the keyword as focus keyword
            $focusField.val(keyword).trigger('input');
            
            // Scroll to focus keyword field
            $('html, body').animate({
                scrollTop: $focusField.offset().top - 100
            }, 500);
            
            // Highlight the field
            $focusField.css('background-color', '#d4edda');
            setTimeout(function() {
                $focusField.css('background-color', '');
            }, 2000);
            
            showNotification('✅ Focus keyword set to: ' + keyword, 'success');
        });
        
        // ====================================
        // Update Reminder Toggle
        // ====================================
        $('#almaseo_update_reminder_enabled').on('change', function() {
            const $daysInput = $('#almaseo_update_reminder_days');
            
            if ($(this).is(':checked')) {
                $daysInput.prop('disabled', false);
            } else {
                $daysInput.prop('disabled', true);
            }
        });
        
        // Initialize state
        if (!$('#almaseo_update_reminder_enabled').is(':checked')) {
            $('#almaseo_update_reminder_days').prop('disabled', true);
        }
        
        // ====================================
        // Real-time SEO Health Score Update
        // ====================================
        function updateSEOHealthScore() {
            let score = 0;
            const breakdown = [];
            
            // Check Title (20 points)
            const title = $('#almaseo_seo_title').val();
            if (title && title.length > 0) {
                score += 20;
                breakdown.push({status: 'pass', label: 'Meta Title Present'});
            } else {
                breakdown.push({status: 'fail', label: 'No Meta Title'});
            }
            
            // Check Description (20 points)
            const description = $('#almaseo_seo_description').val();
            if (description && description.length > 0) {
                score += 20;
                breakdown.push({status: 'pass', label: 'Meta Description Present'});
            } else {
                breakdown.push({status: 'fail', label: 'No Meta Description'});
            }
            
            // Check Focus Keyword (20 points)
            const keyword = $('#almaseo_focus_keyword').val();
            if (keyword && keyword.length > 0) {
                score += 20;
                breakdown.push({status: 'pass', label: 'Focus Keyword Set'});
            } else {
                breakdown.push({status: 'fail', label: 'No Focus Keyword'});
            }
            
            // For other checks, we'll keep the existing values from PHP
            // (since we can't check post content easily from here)
            
            // Update the score display
            const $scoreCircle = $('.health-score-circle');
            const $scoreValue = $('.score-value');
            
            // Remove all classes
            $scoreCircle.removeClass('excellent good needs-work poor');
            
            // Add appropriate class based on score
            if (score >= 60) {
                // Keep the full score from PHP if we have good basics
                return;
            } else if (score >= 40) {
                $scoreCircle.addClass('good');
            } else if (score >= 20) {
                $scoreCircle.addClass('needs-work');
            } else {
                $scoreCircle.addClass('poor');
            }
        }
        
        // Bind to input changes
        $('#almaseo_seo_title, #almaseo_seo_description, #almaseo_focus_keyword').on('input', function() {
            updateSEOHealthScore();
        });
        
        // ====================================
        // Notification Helper
        // ====================================
        function showNotification(message, type) {
            // Remove any existing notifications
            $('.almaseo-notification').remove();
            
            const notification = $('<div>', {
                class: 'almaseo-notification almaseo-notification-' + type,
                html: message
            });
            
            // Add to body
            $('body').append(notification);
            
            // Position it
            notification.css({
                position: 'fixed',
                top: '32px',
                right: '20px',
                padding: '12px 20px',
                background: type === 'success' ? '#28a745' : '#dc3545',
                color: 'white',
                borderRadius: '4px',
                zIndex: 99999,
                boxShadow: '0 2px 5px rgba(0,0,0,0.2)',
                fontSize: '14px',
                fontWeight: '500'
            });
            
            // Animate in
            notification.hide().fadeIn(300);
            
            // Auto remove after 3 seconds
            setTimeout(function() {
                notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
        
        // ====================================
        // Content Age Update (if editing old post)
        // ====================================
        function updateContentAge() {
            // This would need to be called via AJAX to get real-time data
            // For now, it's calculated server-side on page load
        }
        
        // ====================================
        // Smooth Scroll for Internal Links
        // ====================================
        $('a[href^="#"]').on('click', function(e) {
            const target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                $('html, body').animate({
                    scrollTop: target.offset().top - 50
                }, 500);
            }
        });
        
        // ====================================
        // Tooltip Initialization
        // ====================================
        $('[title]').each(function() {
            const $this = $(this);
            const title = $this.attr('title');
            
            if (title) {
                $this.removeAttr('title');
                $this.attr('data-tooltip', title);
                
                $this.on('mouseenter', function() {
                    const tooltip = $('<div>', {
                        class: 'almaseo-tooltip',
                        text: title
                    });
                    
                    $('body').append(tooltip);
                    
                    const offset = $this.offset();
                    tooltip.css({
                        position: 'absolute',
                        top: offset.top - tooltip.outerHeight() - 5,
                        left: offset.left + ($this.outerWidth() / 2) - (tooltip.outerWidth() / 2),
                        background: '#333',
                        color: 'white',
                        padding: '5px 10px',
                        borderRadius: '4px',
                        fontSize: '12px',
                        zIndex: 10000,
                        whiteSpace: 'nowrap'
                    });
                });
                
                $this.on('mouseleave', function() {
                    $('.almaseo-tooltip').remove();
                });
            }
        });
        
        // ====================================
        // Auto-save Indicator
        // ====================================
        let saveTimeout;
        $('#almaseo_seo_title, #almaseo_seo_description, #almaseo_focus_keyword, #almaseo_seo_notes').on('input', function() {
            const $field = $(this);
            
            // Clear existing timeout
            clearTimeout(saveTimeout);
            
            // Show saving indicator
            $field.addClass('saving');
            
            // Remove after delay
            saveTimeout = setTimeout(function() {
                $field.removeClass('saving');
            }, 1000);
        });
        
        // ====================================
        // Keyboard Shortcuts
        // ====================================
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + S to save (prevent default)
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                $('#publish, #save-post').click();
                showNotification('💾 Saving...', 'success');
            }
            
            // Ctrl/Cmd + H to toggle history
            if ((e.ctrlKey || e.metaKey) && e.key === 'h') {
                e.preventDefault();
                $('#toggle-metadata-history').click();
            }
        });
        
        // ====================================
        // Dark Mode Detection
        // ====================================
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            $('body').addClass('almaseo-dark-mode');
        }
        
        // Listen for changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', e => {
            if (e.matches) {
                $('body').addClass('almaseo-dark-mode');
            } else {
                $('body').removeClass('almaseo-dark-mode');
            }
        });
        
    });
    
})(jQuery);