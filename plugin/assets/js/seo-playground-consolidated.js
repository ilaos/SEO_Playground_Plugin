/**
 * AlmaSEO SEO Playground - Consolidated JavaScript
 * Version: 2.1.0
 * Description: Shared utilities and core functionality for SEO Playground
 */

(function($, window, document) {
    'use strict';

    // Namespace for AlmaSEO
    window.AlmaSEO = window.AlmaSEO || {};

    /**
     * Core Utilities Module
     */
    AlmaSEO.Utils = {
        /**
         * Debounce function for performance
         */
        debounce: function(func, wait) {
            let timeout;
            return function executedFunction(...args) {
                const later = () => {
                    clearTimeout(timeout);
                    func(...args);
                };
                clearTimeout(timeout);
                timeout = setTimeout(later, wait);
            };
        },

        /**
         * Throttle function for rate limiting
         */
        throttle: function(func, limit) {
            let inThrottle;
            return function(...args) {
                if (!inThrottle) {
                    func.apply(this, args);
                    inThrottle = true;
                    setTimeout(() => inThrottle = false, limit);
                }
            };
        },

        /**
         * Escape HTML to prevent XSS
         */
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, m => map[m]);
        },

        /**
         * Sanitize input
         */
        sanitizeInput: function(input) {
            return input.replace(/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi, '')
                       .replace(/on\w+="[^"]*"/g, '')
                       .replace(/on\w+='[^']*'/g, '');
        },

        /**
         * Format time ago
         */
        formatTimeAgo: function(timestamp) {
            const date = new Date(timestamp);
            const now = new Date();
            const seconds = Math.floor((now - date) / 1000);
            
            const intervals = {
                year: 31536000,
                month: 2592000,
                week: 604800,
                day: 86400,
                hour: 3600,
                minute: 60
            };
            
            if (seconds < 60) return 'Just now';
            
            for (const [unit, secondsInUnit] of Object.entries(intervals)) {
                const interval = Math.floor(seconds / secondsInUnit);
                if (interval >= 1) {
                    return `${interval} ${unit}${interval !== 1 ? 's' : ''} ago`;
                }
            }
            
            return 'Just now';
        },

        /**
         * Copy to clipboard
         */
        copyToClipboard: function(text) {
            return new Promise((resolve, reject) => {
                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(text).then(resolve).catch(reject);
                } else {
                    // Fallback for older browsers
                    const $temp = $('<textarea>');
                    $('body').append($temp);
                    $temp.val(text).select();
                    
                    try {
                        document.execCommand('copy');
                        resolve();
                    } catch (err) {
                        reject(err);
                    } finally {
                        $temp.remove();
                    }
                }
            });
        },

        /**
         * Get WordPress editor content
         */
        getEditorContent: function() {
            // Gutenberg
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                return wp.data.select('core/editor').getEditedPostContent();
            }
            // Classic Editor with TinyMCE
            else if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
                return tinyMCE.activeEditor.getContent({ format: 'text' });
            }
            // Fallback to textarea
            else if ($('#content').length) {
                return $('#content').val();
            }
            return '';
        },

        /**
         * Show message/notification
         */
        showMessage: function(type, message, container) {
            const $container = container ? $(container) : $('#almaseo-message-container');
            
            const icons = {
                success: '✅',
                error: '⚠️',
                warning: '⚠️',
                info: 'ℹ️'
            };
            
            const messageHtml = `
                <div class="almaseo-message almaseo-message-${type}" role="alert">
                    <span aria-hidden="true">${icons[type] || ''}</span>
                    <span>${this.escapeHtml(message)}</span>
                </div>
            `;
            
            $container.html(messageHtml);
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                $container.find('.almaseo-message').fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
        },

        /**
         * AJAX request with nonce
         */
        ajaxRequest: function(action, data, successCallback, errorCallback) {
            const nonce = $('#almaseo_nonce').val() || $('#_wpnonce').val();
            
            if (!nonce) {
                console.error('AlmaSEO: Security nonce not found');
                if (errorCallback) errorCallback('Security verification failed');
                return;
            }
            
            const requestData = {
                action: action,
                nonce: nonce,
                ...data
            };
            
            $.ajax({
                url: ajaxurl || '/wp-admin/admin-ajax.php',
                type: 'POST',
                data: requestData,
                success: function(response) {
                    if (response.success) {
                        if (successCallback) successCallback(response.data);
                    } else {
                        if (errorCallback) errorCallback(response.data || 'Request failed');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AlmaSEO AJAX Error:', error);
                    if (errorCallback) errorCallback(error);
                }
            });
        },

        /**
         * Check if user has capability
         */
        canEditPosts: function() {
            return $('#almaseo-can-edit').val() === 'true' || 
                   $('.almaseo-seo-playground').data('can-edit') === true;
        }
    };

    /**
     * Accessibility Module
     */
    AlmaSEO.Accessibility = {
        /**
         * Initialize ARIA live regions
         */
        initLiveRegions: function() {
            if (!$('#almaseo-announcer').length) {
                $('body').append('<div id="almaseo-announcer" class="sr-only" aria-live="polite" aria-atomic="true"></div>');
            }
        },

        /**
         * Announce to screen readers
         */
        announce: function(message) {
            const $announcer = $('#almaseo-announcer');
            if ($announcer.length) {
                $announcer.text(message);
                setTimeout(() => $announcer.text(''), 1000);
            }
        },

        /**
         * Setup keyboard navigation
         */
        setupKeyboardNav: function() {
            // Tab navigation with arrow keys
            $(document).on('keydown', '.almaseo-tab-btn', function(e) {
                const $tabs = $('.almaseo-tab-btn');
                const currentIndex = $tabs.index(this);
                
                switch(e.key) {
                    case 'ArrowLeft':
                        e.preventDefault();
                        const prevIndex = currentIndex > 0 ? currentIndex - 1 : $tabs.length - 1;
                        $tabs.eq(prevIndex).focus().click();
                        break;
                    case 'ArrowRight':
                        e.preventDefault();
                        const nextIndex = currentIndex < $tabs.length - 1 ? currentIndex + 1 : 0;
                        $tabs.eq(nextIndex).focus().click();
                        break;
                }
            });

            // Enter/Space activation
            $(document).on('keypress', '[role="button"]', function(e) {
                if (e.which === 13 || e.which === 32) {
                    e.preventDefault();
                    $(this).click();
                }
            });
        },

        /**
         * Manage focus trap for modals
         */
        trapFocus: function(element) {
            const focusableElements = element.find('a, button, input, textarea, select, [tabindex]:not([tabindex="-1"])');
            const firstFocusable = focusableElements.first();
            const lastFocusable = focusableElements.last();
            
            element.on('keydown', function(e) {
                if (e.key === 'Tab') {
                    if (e.shiftKey) {
                        if (document.activeElement === firstFocusable[0]) {
                            e.preventDefault();
                            lastFocusable.focus();
                        }
                    } else {
                        if (document.activeElement === lastFocusable[0]) {
                            e.preventDefault();
                            firstFocusable.focus();
                        }
                    }
                }
            });
            
            firstFocusable.focus();
        }
    };

    /**
     * Storage Module
     */
    AlmaSEO.Storage = {
        /**
         * Save to localStorage with expiry
         */
        set: function(key, value, expiryMinutes) {
            const item = {
                value: value,
                expiry: expiryMinutes ? new Date().getTime() + (expiryMinutes * 60000) : null
            };
            localStorage.setItem('almaseo_' + key, JSON.stringify(item));
        },

        /**
         * Get from localStorage
         */
        get: function(key) {
            const itemStr = localStorage.getItem('almaseo_' + key);
            if (!itemStr) return null;
            
            try {
                const item = JSON.parse(itemStr);
                if (item.expiry && new Date().getTime() > item.expiry) {
                    localStorage.removeItem('almaseo_' + key);
                    return null;
                }
                return item.value;
            } catch (e) {
                return null;
            }
        },

        /**
         * Remove from localStorage
         */
        remove: function(key) {
            localStorage.removeItem('almaseo_' + key);
        },

        /**
         * Clear all AlmaSEO storage
         */
        clear: function() {
            Object.keys(localStorage).forEach(key => {
                if (key.startsWith('almaseo_')) {
                    localStorage.removeItem(key);
                }
            });
        }
    };

    /**
     * Validation Module
     */
    AlmaSEO.Validation = {
        /**
         * Validate URL
         */
        isValidUrl: function(url) {
            try {
                new URL(url);
                return true;
            } catch (e) {
                return false;
            }
        },

        /**
         * Validate email
         */
        isValidEmail: function(email) {
            const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            return re.test(email);
        },

        /**
         * Check character limits
         */
        checkCharLimit: function(text, min, max) {
            const length = text.length;
            return {
                valid: length >= min && length <= max,
                length: length,
                remaining: max - length
            };
        },

        /**
         * Validate SEO title
         */
        validateSEOTitle: function(title) {
            const result = this.checkCharLimit(title, 10, 60);
            result.warning = result.length > 55;
            return result;
        },

        /**
         * Validate meta description
         */
        validateMetaDescription: function(description) {
            const result = this.checkCharLimit(description, 50, 160);
            result.warning = result.length > 155;
            return result;
        }
    };

    /**
     * Editor Compatibility Module
     */
    AlmaSEO.EditorCompat = {
        /**
         * Detect current editor
         */
        detectEditor: function() {
            if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
                return 'gutenberg';
            } else if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
                return 'classic';
            } else if ($('#content').length) {
                return 'text';
            }
            return 'unknown';
        },

        /**
         * Initialize based on editor
         */
        init: function() {
            const editor = this.detectEditor();
            
            switch(editor) {
                case 'gutenberg':
                    this.initGutenberg();
                    break;
                case 'classic':
                    this.initClassic();
                    break;
                case 'text':
                    this.initText();
                    break;
            }
        },

        /**
         * Initialize for Gutenberg
         */
        initGutenberg: function() {
            // Wait for Gutenberg to be ready
            wp.domReady(() => {
                // Add SEO Playground after editor
                const checkInterval = setInterval(() => {
                    if ($('.edit-post-layout__content').length) {
                        clearInterval(checkInterval);
                        AlmaSEO.init();
                    }
                }, 500);
            });
        },

        /**
         * Initialize for Classic Editor
         */
        initClassic: function() {
            // Wait for TinyMCE
            if (typeof tinyMCE !== 'undefined') {
                tinyMCE.on('AddEditor', function() {
                    AlmaSEO.init();
                });
            } else {
                $(document).ready(() => AlmaSEO.init());
            }
        },

        /**
         * Initialize for Text Editor
         */
        initText: function() {
            $(document).ready(() => AlmaSEO.init());
        }
    };

    /**
     * Main Initialization
     */
    AlmaSEO.init = function() {
        // Check user capabilities
        if (!this.Utils.canEditPosts()) {
            console.warn('AlmaSEO: User lacks edit_posts capability');
            return;
        }

        // Initialize modules
        this.Accessibility.initLiveRegions();
        this.Accessibility.setupKeyboardNav();

        // Setup global error handler
        window.addEventListener('error', function(e) {
            if (e.filename && e.filename.includes('almaseo')) {
                console.error('AlmaSEO Error:', e.message);
                AlmaSEO.Utils.showMessage('error', 'An error occurred. Please refresh the page.');
            }
        });

        // Initialize tab system
        this.initTabs();

        // Setup auto-save
        this.setupAutoSave();

        // Check for dark mode
        this.checkDarkMode();
    };

    /**
     * Initialize tab system
     */
    AlmaSEO.initTabs = function() {
        $('.almaseo-tab-btn').on('click', function() {
            const targetTab = $(this).data('tab');
            
            // Update active states
            $('.almaseo-tab-btn').removeClass('active').attr('aria-selected', 'false');
            $(this).addClass('active').attr('aria-selected', 'true');
            
            // Switch panels
            $('.almaseo-tab-panel').removeClass('active').attr('aria-hidden', 'true');
            $('#tab-' + targetTab).addClass('active').attr('aria-hidden', 'false');
            
            // Save preference
            AlmaSEO.Storage.set('active_tab', targetTab);
            
            // Trigger custom event
            $(document).trigger('almaseo:tab:switched', [targetTab]);
            
            // Announce to screen readers
            AlmaSEO.Accessibility.announce('Switched to ' + targetTab.replace('-', ' ') + ' tab');
        });

        // Restore last tab
        const lastTab = AlmaSEO.Storage.get('active_tab');
        if (lastTab) {
            $('.almaseo-tab-btn[data-tab="' + lastTab + '"]').click();
        }
    };

    /**
     * Setup auto-save functionality
     */
    AlmaSEO.setupAutoSave = function() {
        const autoSaveFields = ['#almaseo_seo_title', '#almaseo_meta_description', '#seo-note-textarea'];
        
        autoSaveFields.forEach(selector => {
            $(selector).on('input', AlmaSEO.Utils.debounce(function() {
                const $field = $(this);
                const value = $field.val();
                const fieldId = $field.attr('id');
                
                // Save to localStorage
                AlmaSEO.Storage.set('autosave_' + fieldId, value, 60);
                
                // Show save indicator
                $field.addClass('saving');
                setTimeout(() => $field.removeClass('saving'), 1000);
            }, 1000));
        });

        // Restore auto-saved content
        autoSaveFields.forEach(selector => {
            const $field = $(selector);
            const fieldId = $field.attr('id');
            const savedValue = AlmaSEO.Storage.get('autosave_' + fieldId);
            
            if (savedValue && !$field.val()) {
                $field.val(savedValue);
                AlmaSEO.Utils.showMessage('info', 'Auto-saved content restored');
            }
        });
    };

    /**
     * Check and apply dark mode
     */
    AlmaSEO.checkDarkMode = function() {
        const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
        const userPreference = AlmaSEO.Storage.get('dark_mode');
        
        if (userPreference !== null) {
            $('body').toggleClass('almaseo-dark-mode', userPreference);
        } else if (prefersDark) {
            $('body').addClass('almaseo-dark-mode');
        }

        // Listen for changes
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', (e) => {
            if (AlmaSEO.Storage.get('dark_mode') === null) {
                $('body').toggleClass('almaseo-dark-mode', e.matches);
            }
        });
    };

    // Initialize when ready
    $(document).ready(function() {
        AlmaSEO.EditorCompat.init();
    });

})(jQuery, window, document);