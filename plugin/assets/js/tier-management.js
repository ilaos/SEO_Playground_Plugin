/**
 * AlmaSEO SEO Playground - Tier Management JavaScript
 * Version: 2.5.0
 * Description: Handles tier detection, usage tracking, and UI updates
 */

(function($) {
    'use strict';

    // Global tier state
    window.almaseoTier = {
        current: 'unconnected',
        limits: {},
        usage: {
            daily: 0,
            monthly: 0
        },
        lastCheck: null
    };

    $(document).ready(function() {
        
        // ====================================
        // Tier Detection and Initialization
        // ====================================
        function initializeTierSystem() {
            // Check tier on page load
            checkUserTier();
            
            // Set up periodic tier checking (every 30 minutes)
            setInterval(checkUserTier, 30 * 60 * 1000);
            
            // Set up usage tracking listeners
            initializeUsageTracking();
            
            // Initialize UI indicators
            updateTierUI();
        }
        
        // ====================================
        // Fetch User Tier from Server
        // ====================================
        function checkUserTier() {
            $.ajax({
                url: ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'almaseo_fetch_user_tier',
                    nonce: $('#almaseo_nonce').val()
                },
                success: function(response) {
                    if (response.success && response.data) {
                        // Update global tier state
                        window.almaseoTier.current = response.data.tier || 'unconnected';
                        window.almaseoTier.limits = response.data.limits || {};
                        window.almaseoTier.usage = response.data.usage || { daily: 0, monthly: 0 };
                        window.almaseoTier.lastCheck = Date.now();
                        
                        // Update UI
                        updateTierUI();
                        
                        // Check if we need to show warnings
                        checkUsageWarnings();
                        
                        // Store in localStorage for offline access
                        localStorage.setItem('almaseo_tier_cache', JSON.stringify(window.almaseoTier));
                    }
                },
                error: function() {
                    // Fall back to cached tier if available
                    const cached = localStorage.getItem('almaseo_tier_cache');
                    if (cached) {
                        try {
                            window.almaseoTier = JSON.parse(cached);
                            updateTierUI();
                        } catch (e) {
                            console.error('Error parsing cached tier data:', e);
                        }
                    }
                }
            });
        }
        
        // ====================================
        // Update UI Based on Tier
        // ====================================
        function updateTierUI() {
            const tier = window.almaseoTier.current;
            
            // Update tier badge
            updateTierBadge(tier);
            
            // Update AI buttons
            updateAIButtons(tier);
            
            // Update usage indicators
            updateUsageIndicators();
            
            // Show/hide locked features
            updateLockedFeatures(tier);
            
            // Update connection banner
            updateConnectionBanner(tier);
        }
        
        // ====================================
        // Tier Badge Display
        // ====================================
        function updateTierBadge(tier) {
            // Remove existing badge
            $('.almaseo-tier-badge').remove();
            
            if (tier === 'unconnected') {
                return;
            }
            
            // Create badge HTML
            const badgeHtml = `
                <span class="tier-badge tier-${tier}">
                    ${getTierLabel(tier)}
                </span>
            `;
            
            // Add to AI Tools tab button
            $('.almaseo-tab-btn[data-tab="ai-tools"]').append(badgeHtml);
            
            // Add to connection status area if exists
            if ($('.almaseo-connection-status').length) {
                $('.almaseo-connection-status').append(badgeHtml);
            }
        }
        
        // ====================================
        // AI Button State Management
        // ====================================
        function updateAIButtons(tier) {
            const $aiButtons = $('.ai-generate-btn, .ai-rewrite-btn, .ai-improve-btn');
            
            if (tier === 'unconnected') {
                // Lock all AI buttons
                $aiButtons.each(function() {
                    $(this)
                        .addClass('ai-btn-locked')
                        .attr('disabled', true)
                        .attr('title', 'Connect to AlmaSEO to unlock AI features');
                });
            } else if (tier === 'free') {
                // Check usage limits for free tier
                const usage = window.almaseoTier.usage;
                const limits = window.almaseoTier.limits;
                
                if (usage.daily >= (limits.daily_limit || 10)) {
                    $aiButtons.each(function() {
                        $(this)
                            .addClass('ai-btn-limit-reached')
                            .attr('disabled', true)
                            .attr('title', 'Daily limit reached. Upgrade to Pro for more AI generations');
                    });
                } else {
                    $aiButtons.each(function() {
                        $(this)
                            .removeClass('ai-btn-locked ai-btn-limit-reached')
                            .attr('disabled', false)
                            .addClass('ai-btn-tier-free');
                    });
                }
            } else {
                // Pro or Max tier - full access
                $aiButtons.each(function() {
                    $(this)
                        .removeClass('ai-btn-locked ai-btn-limit-reached ai-btn-tier-free')
                        .attr('disabled', false);
                });
            }
        }
        
        // ====================================
        // Usage Indicators
        // ====================================
        function updateUsageIndicators() {
            const tier = window.almaseoTier.current;
            const usage = window.almaseoTier.usage;
            const limits = window.almaseoTier.limits;
            
            // Remove existing indicators
            $('.ai-usage-indicator').remove();
            
            if (tier === 'free' || tier === 'pro') {
                const dailyLimit = limits.daily_limit || (tier === 'free' ? 10 : 100);
                const usagePercent = (usage.daily / dailyLimit) * 100;
                
                // Determine warning level
                let warningClass = '';
                if (usagePercent >= 90) {
                    warningClass = 'critical-usage';
                } else if (usagePercent >= 70) {
                    warningClass = 'low-usage';
                }
                
                const indicatorHtml = `
                    <div class="ai-usage-indicator ${warningClass}">
                        <span class="usage-label">AI Generations Today:</span>
                        <span class="usage-count">${usage.daily} / ${dailyLimit}</span>
                        <div class="usage-bar">
                            <div class="usage-progress" style="width: ${Math.min(usagePercent, 100)}%"></div>
                        </div>
                    </div>
                `;
                
                // Add to AI Tools tab
                $('#tab-ai-tools').prepend(indicatorHtml);
            }
        }
        
        // ====================================
        // Locked Features Overlay
        // ====================================
        function updateLockedFeatures(tier) {
            if (tier === 'unconnected') {
                // Add lock overlay to AI sections
                $('.ai-section').each(function() {
                    if (!$(this).find('.tier-lock-message').length) {
                        const lockMessage = `
                            <div class="tier-lock-message">
                                <div class="lock-icon">🔒</div>
                                <div class="lock-title">AI Features Locked</div>
                                <div class="lock-description">Connect to AlmaSEO to unlock powerful AI tools</div>
                                <a href="#" class="upgrade-btn" onclick="jQuery('.almaseo-tab-btn[data-tab=\\'unlock-features\\']').click(); return false;">
                                    Unlock Features →
                                </a>
                            </div>
                        `;
                        $(this).addClass('tier-locked-overlay').append(lockMessage);
                    }
                });
            } else {
                // Remove lock overlays
                $('.tier-locked-overlay').removeClass('tier-locked-overlay');
                $('.tier-lock-message').remove();
            }
        }
        
        // ====================================
        // Connection Banner
        // ====================================
        function updateConnectionBanner(tier) {
            // Remove existing banner
            $('.almaseo-connected-banner').remove();
            
            if (tier !== 'unconnected') {
                const bannerHtml = `
                    <div class="almaseo-connected-banner tier-${tier}">
                        <div class="banner-content">
                            <div class="banner-icon">${getTierIcon(tier)}</div>
                            <div class="banner-text">
                                <strong>AlmaSEO ${getTierLabel(tier)} Connected</strong>
                                <p>${getTierDescription(tier)}</p>
                            </div>
                        </div>
                        ${tier === 'free' ? '<div class="usage-warning">Using Free tier. <a href="https://almaseo.com/pricing" target="_blank">Upgrade to Pro</a> for unlimited AI generations.</div>' : ''}
                    </div>
                `;
                
                $('#tab-ai-tools').prepend(bannerHtml);
            }
        }
        
        // ====================================
        // Usage Warning Modals
        // ====================================
        function checkUsageWarnings() {
            const tier = window.almaseoTier.current;
            const usage = window.almaseoTier.usage;
            const limits = window.almaseoTier.limits;
            
            if (tier === 'free') {
                const remaining = (limits.daily_limit || 10) - usage.daily;
                
                if (remaining === 0) {
                    showUsageModal('limit-reached');
                } else if (remaining <= 2 && remaining > 0) {
                    showUsageModal('low-usage', remaining);
                }
            }
        }
        
        function showUsageModal(type, remaining = 0) {
            // Check if we've shown this modal recently
            const lastShown = localStorage.getItem(`almaseo_usage_modal_${type}`);
            const now = Date.now();
            
            if (lastShown && (now - parseInt(lastShown)) < 3600000) { // 1 hour
                return;
            }
            
            let modalContent = '';
            
            if (type === 'limit-reached') {
                modalContent = `
                    <div class="usage-modal-content">
                        <h3>Daily Limit Reached</h3>
                        <p>You've used all your AI generations for today.</p>
                        <p>Your limit will reset tomorrow, or upgrade to Pro for unlimited access.</p>
                        <div class="modal-actions">
                            <a href="https://almaseo.com/pricing" target="_blank" class="btn-upgrade">Upgrade to Pro</a>
                            <button class="btn-dismiss">OK, I'll wait</button>
                        </div>
                    </div>
                `;
            } else if (type === 'low-usage') {
                modalContent = `
                    <div class="usage-modal-content">
                        <h3>Low Usage Warning</h3>
                        <p>You have only <strong>${remaining}</strong> AI generation${remaining > 1 ? 's' : ''} left today.</p>
                        <p>Consider upgrading to Pro for unlimited AI generations.</p>
                        <div class="modal-actions">
                            <a href="https://almaseo.com/pricing" target="_blank" class="btn-upgrade">Learn More</a>
                            <button class="btn-dismiss">Got it</button>
                        </div>
                    </div>
                `;
            }
            
            // Create and show modal
            const $modal = $('<div class="almaseo-usage-modal">' + modalContent + '</div>');
            $('body').append($modal);
            
            // Style the modal
            $modal.css({
                position: 'fixed',
                top: '50%',
                left: '50%',
                transform: 'translate(-50%, -50%)',
                background: 'white',
                padding: '30px',
                borderRadius: '12px',
                boxShadow: '0 10px 40px rgba(0,0,0,0.2)',
                zIndex: 100000,
                maxWidth: '400px',
                textAlign: 'center'
            });
            
            // Add backdrop
            const $backdrop = $('<div class="modal-backdrop"></div>');
            $backdrop.css({
                position: 'fixed',
                top: 0,
                left: 0,
                right: 0,
                bottom: 0,
                background: 'rgba(0,0,0,0.5)',
                zIndex: 99999
            });
            $('body').append($backdrop);
            
            // Handle dismiss
            $modal.find('.btn-dismiss').on('click', function() {
                $modal.fadeOut(300, function() { $(this).remove(); });
                $backdrop.fadeOut(300, function() { $(this).remove(); });
                localStorage.setItem(`almaseo_usage_modal_${type}`, now.toString());
            });
            
            // Auto-dismiss after 10 seconds
            setTimeout(function() {
                if ($modal.is(':visible')) {
                    $modal.fadeOut(300, function() { $(this).remove(); });
                    $backdrop.fadeOut(300, function() { $(this).remove(); });
                }
            }, 10000);
        }
        
        // ====================================
        // Usage Tracking
        // ====================================
        function initializeUsageTracking() {
            // Track AI generation button clicks
            $(document).on('click', '.ai-generate-btn, .ai-rewrite-btn, .ai-improve-btn', function() {
                const tier = window.almaseoTier.current;
                
                if (tier !== 'unconnected' && tier !== 'max') {
                    // Increment local usage counter immediately for responsiveness
                    window.almaseoTier.usage.daily++;
                    
                    // Update UI
                    updateUsageIndicators();
                    updateAIButtons(tier);
                    
                    // Check for warnings
                    setTimeout(checkUsageWarnings, 1000);
                }
            });
            
            // Listen for successful AI generation events
            $(document).on('almaseo:ai:generated', function() {
                // Refresh tier data after generation
                setTimeout(checkUserTier, 2000);
            });
        }
        
        // ====================================
        // Helper Functions
        // ====================================
        function getTierLabel(tier) {
            const labels = {
                'free': 'Free',
                'pro': 'Pro',
                'max': 'Max',
                'unconnected': 'Not Connected'
            };
            return labels[tier] || 'Unknown';
        }
        
        function getTierIcon(tier) {
            const icons = {
                'free': '🎯',
                'pro': '⚡',
                'max': '🚀',
                'unconnected': '🔒'
            };
            return icons[tier] || '❓';
        }
        
        function getTierDescription(tier) {
            const descriptions = {
                'free': 'Limited to 10 AI generations per day',
                'pro': 'Up to 100 AI generations per day',
                'max': 'Unlimited AI generations',
                'unconnected': 'Connect to unlock AI features'
            };
            return descriptions[tier] || '';
        }
        
        // ====================================
        // Tier Upgrade Prompts
        // ====================================
        function showUpgradePrompt(context) {
            const tier = window.almaseoTier.current;
            
            if (tier === 'free') {
                const promptHtml = `
                    <div class="upgrade-prompt">
                        <h3>Unlock More AI Power</h3>
                        <p>Upgrade to Pro for 10x more AI generations and advanced features.</p>
                        <div class="cta-buttons">
                            <a href="https://almaseo.com/pricing" target="_blank" class="cta-btn cta-btn-primary">
                                Upgrade Now
                            </a>
                            <button class="cta-btn cta-btn-secondary dismiss-prompt">
                                Maybe Later
                            </button>
                        </div>
                    </div>
                `;
                
                // Add to appropriate location based on context
                if (context === 'ai-tools') {
                    $('#tab-ai-tools').append(promptHtml);
                }
                
                // Handle dismiss
                $('.dismiss-prompt').on('click', function() {
                    $(this).closest('.upgrade-prompt').fadeOut(300, function() {
                        $(this).remove();
                    });
                });
            }
        }
        
        // ====================================
        // Real-time Tier Sync
        // ====================================
        function syncTierWithDashboard() {
            // This function is called when connection status changes
            $.ajax({
                url: ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: {
                    action: 'almaseo_sync_tier',
                    nonce: $('#almaseo_nonce').val()
                },
                success: function(response) {
                    if (response.success) {
                        // Refresh tier data
                        checkUserTier();
                        
                        // Show success notification
                        showNotification('Tier synced successfully', 'success');
                    }
                }
            });
        }
        
        // ====================================
        // Notification System
        // ====================================
        function showNotification(message, type = 'info') {
            const $notification = $(`
                <div class="almaseo-notification ${type}">
                    ${message}
                </div>
            `);
            
            $notification.css({
                position: 'fixed',
                top: '20px',
                right: '20px',
                padding: '15px 20px',
                background: type === 'success' ? '#4caf50' : '#2196f3',
                color: 'white',
                borderRadius: '8px',
                boxShadow: '0 4px 12px rgba(0,0,0,0.15)',
                zIndex: 100000,
                opacity: 0,
                transition: 'opacity 0.3s'
            });
            
            $('body').append($notification);
            
            setTimeout(function() {
                $notification.css('opacity', 1);
            }, 100);
            
            setTimeout(function() {
                $notification.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
        
        // ====================================
        // Export for External Use
        // ====================================
        window.almaseoTierManager = {
            checkTier: checkUserTier,
            syncTier: syncTierWithDashboard,
            showUpgradePrompt: showUpgradePrompt,
            getCurrentTier: function() { return window.almaseoTier.current; },
            getUsage: function() { return window.almaseoTier.usage; },
            getLimits: function() { return window.almaseoTier.limits; }
        };
        
        // ====================================
        // Initialize on Load
        // ====================================
        initializeTierSystem();
        
        // Listen for connection changes
        $(document).on('almaseo:connected', function() {
            syncTierWithDashboard();
        });
        
        $(document).on('almaseo:disconnected', function() {
            window.almaseoTier.current = 'unconnected';
            updateTierUI();
        });
        
    });
    
})(jQuery);