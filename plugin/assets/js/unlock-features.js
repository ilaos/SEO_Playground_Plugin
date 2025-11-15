/**
 * AlmaSEO SEO Playground - Unlock Features JavaScript
 * Version: 2.4.0
 * Description: Interactive functionality for the Unlock AI Features tab
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // ====================================
        // Auto-select Unlock tab for new users
        // ====================================
        function checkAndShowUnlockTab() {
            // Only if not connected
            if (!window.seoPlaygroundData || !window.seoPlaygroundData.almaConnected) {
                // Check if user has seen the unlock tab before
                const hasSeenUnlock = localStorage.getItem('almaseo_seen_unlock_tab');
                
                if (!hasSeenUnlock) {
                    // Auto-switch to unlock tab on first view
                    setTimeout(function() {
                        const $unlockTab = $('.almaseo-tab-btn[data-tab="unlock-features"]');
                        if ($unlockTab.length && !$unlockTab.hasClass('active')) {
                            $unlockTab.trigger('click');
                            
                            // Mark as seen
                            localStorage.setItem('almaseo_seen_unlock_tab', 'true');
                            
                            // Add attention animation
                            $unlockTab.addClass('pulse-attention');
                            setTimeout(function() {
                                $unlockTab.removeClass('pulse-attention');
                            }, 3000);
                        }
                    }, 1500);
                }
            }
        }
        
        // Run check on page load
        checkAndShowUnlockTab();
        
        // ====================================
        // Animated Counter for Trust Indicators
        // ====================================
        function animateCounters() {
            $('.trust-number').each(function() {
                const $this = $(this);
                const target = $this.text();
                
                // Check if it's a number
                const numMatch = target.match(/[\d,]+/);
                if (numMatch) {
                    const finalNumber = parseInt(numMatch[0].replace(/,/g, ''));
                    const suffix = target.replace(numMatch[0], '');
                    
                    // Animate from 0 to target
                    $({ count: 0 }).animate({
                        count: finalNumber
                    }, {
                        duration: 2000,
                        easing: 'swing',
                        step: function() {
                            const currentNum = Math.ceil(this.count);
                            const formatted = currentNum.toLocaleString();
                            $this.text(formatted + suffix);
                        },
                        complete: function() {
                            $this.text(target); // Ensure final value is exact
                        }
                    });
                }
            });
        }
        
        // Trigger animation when unlock tab is shown
        $(document).on('click', '.almaseo-tab-btn[data-tab="unlock-features"]', function() {
            setTimeout(animateCounters, 300);
        });
        
        // ====================================
        // Floating Cards Animation Enhancement
        // ====================================
        function enhanceFloatingCards() {
            $('.floating-card').each(function() {
                const $card = $(this);
                
                $card.on('mouseenter', function() {
                    $(this).css({
                        'animation-play-state': 'paused',
                        'transform': 'scale(1.1)',
                        'z-index': '10'
                    });
                });
                
                $card.on('mouseleave', function() {
                    $(this).css({
                        'animation-play-state': 'running',
                        'transform': 'scale(1)',
                        'z-index': 'auto'
                    });
                });
            });
        }
        
        enhanceFloatingCards();
        
        // ====================================
        // Lock Icon Tooltips
        // ====================================
        $('.lock-icon').each(function() {
            const $icon = $(this);
            const message = $icon.attr('title');
            
            $icon.on('mouseenter', function(e) {
                const tooltip = $('<div>', {
                    class: 'lock-tooltip',
                    text: message
                });
                
                $('body').append(tooltip);
                
                const offset = $icon.offset();
                tooltip.css({
                    position: 'absolute',
                    top: offset.top - tooltip.outerHeight() - 10,
                    left: offset.left - (tooltip.outerWidth() / 2) + ($icon.outerWidth() / 2),
                    background: '#333',
                    color: 'white',
                    padding: '8px 12px',
                    borderRadius: '6px',
                    fontSize: '12px',
                    zIndex: 10000,
                    whiteSpace: 'nowrap',
                    opacity: 0,
                    transition: 'opacity 0.3s'
                });
                
                setTimeout(function() {
                    tooltip.css('opacity', 1);
                }, 10);
            });
            
            $icon.on('mouseleave', function() {
                $('.lock-tooltip').fadeOut(200, function() {
                    $(this).remove();
                });
            });
        });
        
        // ====================================
        // Feature Card Hover Effects
        // ====================================
        $('.locked-feature-card').each(function() {
            const $card = $(this);
            
            $card.on('mouseenter', function() {
                // Shake the lock icon
                $(this).find('.lock-icon').addClass('shake');
                
                // Add glow effect
                $(this).addClass('glow');
            });
            
            $card.on('mouseleave', function() {
                $(this).find('.lock-icon').removeClass('shake');
                $(this).removeClass('glow');
            });
        });
        
        // Add shake animation CSS
        if (!$('#unlock-animations').length) {
            $('head').append(`
                <style id="unlock-animations">
                    @keyframes shake {
                        0%, 100% { transform: rotate(0deg); }
                        25% { transform: rotate(-10deg); }
                        75% { transform: rotate(10deg); }
                    }
                    
                    .shake {
                        animation: shake 0.5s ease-in-out;
                    }
                    
                    .glow {
                        box-shadow: 0 0 30px rgba(102, 126, 234, 0.3) !important;
                    }
                    
                    .pulse-attention {
                        animation: pulse-glow 1s ease-in-out 3;
                    }
                    
                    @keyframes pulse-glow {
                        0%, 100% { 
                            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
                        }
                        50% { 
                            box-shadow: 0 0 20px rgba(102, 126, 234, 0.5);
                        }
                    }
                </style>
            `);
        }
        
        // ====================================
        // CTA Click Tracking
        // ====================================
        $('.hero-cta-primary, .footer-cta-primary').on('click', function(e) {
            // Track the click (you can send this to analytics)
            const source = $(this).closest('.unlock-hero-section').length ? 'hero' : 'footer';
            
            console.log('AlmaSEO Unlock: Connect clicked from', source);
            
            // Add loading state
            const $btn = $(this);
            const originalText = $btn.html();
            $btn.html('<span class="spinner">⏳</span> Redirecting...');
            
            // Allow default action to proceed
            setTimeout(function() {
                $btn.html(originalText);
            }, 3000);
        });
        
        // ====================================
        // Testimonial Rotation (if multiple)
        // ====================================
        function rotateTestimonials() {
            // This is placeholder for future multiple testimonials
            // Currently we have one, but structure supports multiple
        }
        
        // ====================================
        // Connection Status Check
        // ====================================
        function checkConnectionStatus() {
            // Periodically check if user has connected
            $.ajax({
                url: ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'seo_playground_check_connection',
                    nonce: $('#almaseo_nonce').val()
                },
                success: function(response) {
                    if (response.success && response.data.connected) {
                        // User has connected! Hide the unlock tab
                        $('.almaseo-tab-btn[data-tab="unlock-features"]').fadeOut(500);
                        
                        // Show success message
                        showConnectionSuccess();
                        
                        // Switch to SEO Overview tab
                        $('.almaseo-tab-btn[data-tab="seo-overview"]').trigger('click');
                    }
                }
            });
        }
        
        // Check every 30 seconds if on unlock tab
        setInterval(function() {
            if ($('#tab-unlock-features').is(':visible')) {
                checkConnectionStatus();
            }
        }, 30000);
        
        // ====================================
        // Success Message
        // ====================================
        function showConnectionSuccess() {
            const successMessage = $('<div>', {
                class: 'almaseo-connection-success',
                html: `
                    <div class="success-icon">🎉</div>
                    <div class="success-text">
                        <h3>Successfully Connected!</h3>
                        <p>All AI features are now unlocked. Let's optimize your content!</p>
                    </div>
                `
            });
            
            $('body').append(successMessage);
            
            successMessage.css({
                position: 'fixed',
                top: '50%',
                left: '50%',
                transform: 'translate(-50%, -50%)',
                background: 'white',
                padding: '40px',
                borderRadius: '16px',
                boxShadow: '0 20px 60px rgba(0,0,0,0.3)',
                zIndex: 100000,
                textAlign: 'center',
                opacity: 0,
                transition: 'opacity 0.5s'
            });
            
            setTimeout(function() {
                successMessage.css('opacity', 1);
            }, 100);
            
            setTimeout(function() {
                successMessage.fadeOut(500, function() {
                    $(this).remove();
                });
            }, 3000);
        }
        
        // ====================================
        // Smooth Scroll for Internal Links
        // ====================================
        $('.unlock-container a[href^="#"]').on('click', function(e) {
            const target = $(this.getAttribute('href'));
            if (target.length) {
                e.preventDefault();
                const $tabContent = $('.almaseo-tab-content');
                const scrollTop = target.offset().top - $tabContent.offset().top + $tabContent.scrollTop();
                
                $tabContent.animate({
                    scrollTop: scrollTop - 20
                }, 500);
            }
        });
        
        // ====================================
        // Parallax Effect for Hero
        // ====================================
        $('#tab-unlock-features').on('scroll', function() {
            const scrolled = $(this).scrollTop();
            const parallaxSpeed = 0.5;
            
            $('.unlock-hero-section').css({
                'transform': 'translateY(' + (scrolled * parallaxSpeed) + 'px)'
            });
        });
        
        // ====================================
        // Feature Request Link
        // ====================================
        $(document).on('click', '.locked-feature-card', function(e) {
            if (!$(e.target).is('a')) {
                // Highlight the connect button
                $('.hero-cta-primary').addClass('pulse-attention');
                
                // Scroll to hero
                $('#tab-unlock-features').animate({
                    scrollTop: 0
                }, 500);
                
                setTimeout(function() {
                    $('.hero-cta-primary').removeClass('pulse-attention');
                }, 3000);
            }
        });
        
        // ====================================
        // Initialize on Tab Switch
        // ====================================
        $(document).on('almaseo:tab:switched', function(e, tabName) {
            if (tabName === 'unlock-features') {
                // Re-initialize animations
                setTimeout(function() {
                    animateCounters();
                    enhanceFloatingCards();
                }, 100);
            }
        });
        
    });
    
})(jQuery);