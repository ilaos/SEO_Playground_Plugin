/**
 * AlmaSEO SEO Overview - Polish JavaScript
 * Enhanced interactions and UX improvements
 */

(function($) {
    'use strict';

    var SEOOverviewPolish = {
        
        init: function() {
            this.initBackToTop();
            this.initPanelFocus();
            this.initTimestamps();
            this.initKeywordSuggestions();
            this.initAccessibility();
            this.initSmoothAnimations();
        },
        
        // Back to Top Button
        initBackToTop: function() {
            // Create button if it doesn't exist
            if (!$('.almaseo-back-to-top').length) {
                var backToTopBtn = $('<button/>', {
                    'class': 'almaseo-back-to-top',
                    'aria-label': 'Back to top',
                    'html': '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M7 14l5-5 5 5z"/></svg>'
                });
                
                $('body').append(backToTopBtn);
            }
            
            // Show/hide based on scroll position
            var $backToTop = $('.almaseo-back-to-top');
            var $panels = $('.almaseo-field-group');
            
            $(window).on('scroll', function() {
                if ($panels.length >= 2) {
                    var scrollTop = $(window).scrollTop();
                    var secondPanelTop = $panels.eq(1).offset().top;
                    
                    if (scrollTop > secondPanelTop) {
                        $backToTop.addClass('visible');
                    } else {
                        $backToTop.removeClass('visible');
                    }
                }
            });
            
            // Scroll to top on click
            $backToTop.on('click', function() {
                $('html, body').animate({
                    scrollTop: $('.almaseo-seo-playground').offset().top - 50
                }, 500);
            });
        },
        
        // Panel Focus Effects
        initPanelFocus: function() {
            $('.almaseo-field-group').on('focusin', function() {
                $(this).addClass('panel-focused');
            }).on('focusout', function() {
                $(this).removeClass('panel-focused');
            });
            
            // Add hover intent for better UX
            var hoverTimer;
            $('.almaseo-field-group').on('mouseenter', function() {
                var $panel = $(this);
                hoverTimer = setTimeout(function() {
                    $panel.addClass('panel-hovered');
                }, 100);
            }).on('mouseleave', function() {
                clearTimeout(hoverTimer);
                $(this).removeClass('panel-hovered');
            });
        },
        
        // Timestamp Formatting
        initTimestamps: function() {
            // Format all timestamps consistently
            function formatTimestamp(date) {
                var now = new Date();
                var diff = now - date;
                var seconds = Math.floor(diff / 1000);
                var minutes = Math.floor(seconds / 60);
                var hours = Math.floor(minutes / 60);
                var days = Math.floor(hours / 24);
                
                if (seconds < 60) {
                    return 'Just now';
                } else if (minutes < 60) {
                    return minutes + ' minute' + (minutes !== 1 ? 's' : '') + ' ago';
                } else if (hours < 24) {
                    return hours + ' hour' + (hours !== 1 ? 's' : '') + ' ago';
                } else if (days < 7) {
                    return days + ' day' + (days !== 1 ? 's' : '') + ' ago';
                } else {
                    return date.toLocaleDateString('en-US', {
                        month: 'short',
                        day: 'numeric',
                        year: date.getFullYear() !== now.getFullYear() ? 'numeric' : undefined
                    });
                }
            }
            
            // Apply to all timestamp elements
            $('[id*="-timestamp"] span').each(function() {
                var timestamp = $(this).data('timestamp');
                if (timestamp) {
                    var date = new Date(timestamp);
                    $(this).text(formatTimestamp(date));
                }
            });
        },
        
        // Focus Keyword Suggestions Interactions
        initKeywordSuggestions: function() {
            var self = this;
            
            // Mock function to generate keyword suggestions (replace with actual API call)
            function generateSuggestions() {
                var suggestions = [
                    'SEO optimization',
                    'keyword research',
                    'content strategy',
                    'search rankings',
                    'organic traffic'
                ];
                
                var $list = $('#focus-suggestions-list');
                $list.empty();
                
                suggestions.forEach(function(keyword) {
                    var $item = $('<div/>', {
                        'class': 'focus-suggestion-item',
                        'role': 'listitem',
                        'tabindex': '0',
                        'text': keyword,
                        'data-keyword': keyword
                    });
                    
                    $list.append($item);
                });
                
                $('#focus-suggestions-empty').hide();
                $('#focus-suggestions-content').fadeIn();
                
                // Update timestamp
                var now = new Date();
                $('#focus-suggestions-last-updated').data('timestamp', now.toISOString()).text('Just now');
                $('#focus-suggestions-timestamp').show();
            }
            
            // Handle suggestion click
            $(document).on('click', '.focus-suggestion-item', function() {
                var keyword = $(this).data('keyword');
                $('#almaseo_focus_keyword').val(keyword).trigger('input');
                
                // Visual feedback
                $(this).addClass('selected');
                setTimeout(function() {
                    $('.focus-suggestion-item').removeClass('selected');
                }, 1000);
                
                // Announce to screen readers
                self.announceToScreenReader('Keyword "' + keyword + '" selected');
            });
            
            // Handle refresh button
            $('#refresh-focus-suggestions').on('click', function() {
                var $btn = $(this);
                $btn.prop('disabled', true);
                
                $('#focus-suggestions-content').hide();
                $('#focus-suggestions-loading').show();
                
                setTimeout(function() {
                    generateSuggestions();
                    $('#focus-suggestions-loading').hide();
                    $btn.prop('disabled', false);
                }, 1500);
            });
            
            // Load suggestions on page load if connected
            if (window.seoPlaygroundData && window.seoPlaygroundData.almaConnected) {
                setTimeout(generateSuggestions, 1000);
            }
        },
        
        // Accessibility Improvements
        initAccessibility: function() {
            // Add ARIA live region for announcements
            if (!$('#almaseo-aria-live').length) {
                $('body').append('<div id="almaseo-aria-live" class="sr-only" aria-live="polite" aria-atomic="true"></div>');
            }
            
            // Keyboard navigation for suggestion items
            $(document).on('keydown', '.focus-suggestion-item', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    $(this).trigger('click');
                }
            });
            
            // Improve tooltip accessibility
            $('[role="tooltip"]').on('mouseenter focus', function() {
                var tooltipText = $(this).attr('aria-label');
                if (tooltipText) {
                    $(this).attr('title', tooltipText);
                }
            });
        },
        
        // Smooth Animations
        initSmoothAnimations: function() {
            // Observe panels entering viewport
            if ('IntersectionObserver' in window) {
                var observer = new IntersectionObserver(function(entries) {
                    entries.forEach(function(entry) {
                        if (entry.isIntersecting) {
                            $(entry.target).addClass('panel-visible');
                        }
                    });
                }, {
                    threshold: 0.1
                });
                
                $('.almaseo-field-group').each(function() {
                    observer.observe(this);
                });
            } else {
                // Fallback for older browsers
                $('.almaseo-field-group').addClass('panel-visible');
            }
        },
        
        // Helper: Announce to screen readers
        announceToScreenReader: function(message) {
            $('#almaseo-aria-live').text(message);
            setTimeout(function() {
                $('#almaseo-aria-live').text('');
            }, 1000);
        },
        
        // Update panel heights for consistent layout
        normalizePanelHeights: function() {
            var maxHeight = 0;
            var $panels = $('.almaseo-field-group');
            
            // Reset heights
            $panels.css('min-height', '');
            
            // Find max height
            $panels.each(function() {
                var height = $(this).outerHeight();
                if (height > maxHeight) {
                    maxHeight = height;
                }
            });
            
            // Apply consistent min-height (optional)
            // $panels.css('min-height', maxHeight);
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        // Only initialize if we're in the SEO Overview tab
        if ($('#tab-seo-overview').length) {
            SEOOverviewPolish.init();
            
            // Re-initialize when switching to SEO Overview tab
            $(document).on('almaseo:tab:switched', function(e, tabId) {
                if (tabId === 'seo-overview') {
                    SEOOverviewPolish.initTimestamps();
                    SEOOverviewPolish.normalizePanelHeights();
                }
            });
        }
    });
    
    // Handle window resize
    $(window).on('resize', debounce(function() {
        SEOOverviewPolish.normalizePanelHeights();
    }, 250));
    
    // Debounce helper
    function debounce(func, wait) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            clearTimeout(timeout);
            timeout = setTimeout(function() {
                func.apply(context, args);
            }, wait);
        };
    }

})(jQuery);