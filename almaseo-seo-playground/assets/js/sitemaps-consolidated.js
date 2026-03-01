/**
 * AlmaSEO Sitemaps Admin JavaScript
 * 
 * Handles UI interactions and AJAX operations
 */

(function($) {
    'use strict';
    
    // Settings object
    let settings = window.almaseoSitemaps.settings || {};
    let hasChanges = false;
    
    /**
     * Show toast notification
     */
    function showToast(message, type = 'success') {
        const $toast = $('#almaseo-toast');
        $toast.removeClass('success error').addClass(type);
        $toast.html('<span class="dashicons dashicons-' + 
            (type === 'success' ? 'yes' : 'warning') + '"></span>' + message);
        $toast.addClass('show');
        
        setTimeout(function() {
            $toast.removeClass('show');
        }, 3000);
    }
    
    /**
     * Save settings via AJAX
     */
    function saveSettings(showNotification = true) {
        const data = {
            action: 'almaseo_save_sitemap_settings',
            nonce: almaseoSitemaps.nonce,
            enabled: $('#master-enable').prop('checked'),
            include: {
                posts: $('.sitemap-type[data-type="posts"]').prop('checked'),
                pages: $('.sitemap-type[data-type="pages"]').prop('checked'),
                cpts: $('.sitemap-type[data-type="cpts"]').prop('checked'),
                tax: {
                    category: $('.sitemap-type[data-type="category"]').prop('checked'),
                    post_tag: $('.sitemap-type[data-type="post_tag"]').prop('checked')
                },
                users: $('.sitemap-type[data-type="users"]').prop('checked')
            },
            links_per_sitemap: $('#links-per-sitemap').val()
        };
        
        if (showNotification) {
            showToast(almaseoSitemaps.i18n.saving);
        }
        
        $.post(almaseoSitemaps.ajaxUrl, data, function(response) {
            if (response.success) {
                settings = response.data.settings;
                hasChanges = false;
                $('.almaseo-save-all').fadeOut();
                
                if (showNotification) {
                    showToast(response.data.message, 'success');
                }
                
                // Update enabled chip
                updateEnabledChip(settings.enabled);
            } else {
                showToast(response.data || almaseoSitemaps.i18n.error, 'error');
            }
        });
    }
    
    /**
     * Update enabled/disabled chip
     */
    function updateEnabledChip(enabled) {
        const $chip = $('.almaseo-chip').first();
        if (enabled) {
            $chip.removeClass('almaseo-chip-error').addClass('almaseo-chip-success');
            $chip.text(almaseoSitemaps.i18n.enabled || 'Enabled');
        } else {
            $chip.removeClass('almaseo-chip-success').addClass('almaseo-chip-error');
            $chip.text(almaseoSitemaps.i18n.disabled || 'Disabled');
        }
    }
    
    /**
     * Mark settings as changed
     */
    function markChanged() {
        if (!hasChanges) {
            hasChanges = true;
            $('.almaseo-save-all').fadeIn();
        }
        // Auto-save after 1 second of no changes
        clearTimeout(window.autoSaveTimeout);
        window.autoSaveTimeout = setTimeout(function() {
            saveSettings(true);
        }, 1000);
    }
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        
        // Master enable toggle
        $('#master-enable').on('change', function() {
            const enabled = $(this).prop('checked');
            $('.almaseo-sitemap-types').toggleClass('disabled', !enabled);
            if (!enabled) {
                $('.sitemap-type').prop('disabled', true);
            } else {
                $('.sitemap-type').prop('disabled', false);
            }
            markChanged();
        });
        
        // Sitemap type toggles
        $('.sitemap-type').on('change', function() {
            markChanged();
        });
        
        // Links per sitemap
        $('#links-per-sitemap').on('change', function() {
            let val = parseInt($(this).val());
            if (val < 1) val = 1;
            if (val > 50000) val = 50000;
            $(this).val(val);
            markChanged();
        });
        
        // Open sitemap button
        $('#open-sitemap').on('click', function() {
            window.open(almaseoSitemaps.sitemapUrl, '_blank');
        });
        
        // Copy URL button
        $('#copy-url').on('click', function() {
            const $btn = $(this);
            const $input = $('#sitemap-url');
            
            // Select and copy
            $input.select();
            document.execCommand('copy');
            
            // Update button text
            const originalText = $btn.find('.button-text').text();
            $btn.find('.button-text').text(almaseoSitemaps.i18n.copied);
            $btn.addClass('success');
            
            // Reset after 2 seconds
            setTimeout(function() {
                $btn.find('.button-text').text(originalText);
                $btn.removeClass('success');
            }, 2000);
        });
        
        // Recalculate button
        $('#recalculate').on('click', function() {
            const $btn = $(this);
            const originalHtml = $btn.html();
            
            // Show loading state
            $btn.html('<span class="dashicons dashicons-update spinning"></span> ' + 
                     '<span class="button-text">' + almaseoSitemaps.i18n.recalculating + '</span>');
            $btn.prop('disabled', true);
            
            $.post(almaseoSitemaps.ajaxUrl, {
                action: 'almaseo_recalculate_sitemap',
                nonce: almaseoSitemaps.nonce
            }, function(response) {
                if (response.success) {
                    // Update UI
                    $btn.html('<span class="dashicons dashicons-yes"></span> ' +
                             '<span class="button-text">' + almaseoSitemaps.i18n.recalculated + '</span>');
                    
                    // Update chips
                    $('.almaseo-chip:contains("Last built")').html(
                        almaseoSitemaps.i18n.lastBuilt + ' <strong>' + response.data.last_built + '</strong>'
                    );
                    $('.almaseo-chip:contains("Files")').html(
                        almaseoSitemaps.i18n.files + ' <strong>' + response.data.stats.files + '</strong>'
                    );
                    $('.almaseo-chip:contains("URLs")').html(
                        almaseoSitemaps.i18n.urls + ' <strong>' + response.data.stats.urls + '</strong>'
                    );
                    
                    // Reset button after 2 seconds
                    setTimeout(function() {
                        $btn.html(originalHtml);
                        $btn.prop('disabled', false);
                    }, 2000);
                } else {
                    showToast(almaseoSitemaps.i18n.error, 'error');
                    $btn.html(originalHtml);
                    $btn.prop('disabled', false);
                }
            });
        });
        
        // Save all button
        $('.almaseo-save-all').on('click', function() {
            saveSettings(true);
        });
        
        // Tool buttons (Phase 2 placeholder)
        $('.almaseo-tool-button').on('click', function(e) {
            e.preventDefault();
            // These will be activated in Phase 2
            return false;
        });
        
        // Add spinning animation for dashicons
        const style = document.createElement('style');
        style.textContent = `
            @keyframes dashicons-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .dashicons.spinning {
                animation: dashicons-spin 1s linear infinite;
            }
        `;
        document.head.appendChild(style);
        
        // Handle keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                if (hasChanges) {
                    saveSettings(true);
                }
            }
        });
        
        // Prevent navigation if there are unsaved changes
        $(window).on('beforeunload', function() {
            if (hasChanges) {
                return 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        
        // Initialize tooltips for disabled elements
        $('[data-tooltip]').each(function() {
            const $this = $(this);
            $this.attr('title', $this.data('tooltip'));
        });
        
        // Handle responsive menu
        if ($(window).width() < 768) {
            $('.almaseo-button-group').addClass('responsive');
        }
        
        $(window).on('resize', function() {
            if ($(window).width() < 768) {
                $('.almaseo-button-group').addClass('responsive');
            } else {
                $('.almaseo-button-group').removeClass('responsive');
            }
        });
    });
    
})(jQuery);/**
 * AlmaSEO Auto-Updates JavaScript
 * 
 * Handles update channel switching and manual checks
 * 
 * @package AlmaSEO
 * @since 5.0.0
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        /**
         * Handle update channel change
         */
        $('#update-channel').on('change', function() {
            const channel = $(this).val();
            const $select = $(this);
            
            $select.prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'almaseo_save_update_channel',
                channel: channel,
                nonce: almaseoSitemaps.nonce
            }, function(response) {
                if (response.success) {
                    showToast(response.data.message, 'success');
                    
                    // Trigger background check with new channel
                    setTimeout(function() {
                        checkForUpdates(true); // Silent check
                    }, 1000);
                } else {
                    showToast(response.data || 'Failed to save channel', 'error');
                    // Revert selection
                    $select.val($select.data('original'));
                }
                
                $select.prop('disabled', false);
            }).fail(function() {
                showToast('Network error', 'error');
                $select.prop('disabled', false);
                $select.val($select.data('original'));
            });
        });
        
        // Store original value
        $('#update-channel').data('original', $('#update-channel').val());
        
        /**
         * Handle manual update check
         */
        $('#check-updates-btn').on('click', function() {
            checkForUpdates(false);
        });
        
        /**
         * Check for updates
         */
        function checkForUpdates(silent) {
            const $btn = $('#check-updates-btn');
            const originalText = $btn.html();
            
            if (!silent) {
                $btn.prop('disabled', true);
                $btn.html('<span class="dashicons dashicons-update spin"></span> Checking...');
            }
            
            $.post(ajaxurl, {
                action: 'almaseo_check_updates_now',
                nonce: almaseoSitemaps.nonce
            }, function(response) {
                if (response.success) {
                    updateDisplayInfo(response.data);
                    
                    if (!silent) {
                        if (response.data.found) {
                            showToast('Update available: v' + response.data.version, 'success');
                            
                            // Show update button
                            if (response.data.version !== response.data.current) {
                                showUpdatePrompt(response.data);
                            }
                        } else {
                            showToast('You have the latest version', 'info');
                        }
                    }
                } else {
                    if (!silent) {
                        showToast(response.data || 'Update check failed', 'error');
                    }
                }
                
                if (!silent) {
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                }
            }).fail(function() {
                if (!silent) {
                    showToast('Network error during update check', 'error');
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                }
            });
        }
        
        /**
         * Update display info
         */
        function updateDisplayInfo(data) {
            const $info = $('.almaseo-update-info');
            
            // Update last check time
            if (data.checked_at) {
                const checkTime = new Date(data.checked_at * 1000);
                const timeAgo = getTimeAgo(checkTime);
                
                // Update or add last check line
                let $lastCheck = $info.find('p:contains("Last Check")');
                if ($lastCheck.length === 0) {
                    $info.append('<p><strong>Last Check:</strong> <span class="last-check-time"></span></p>');
                    $lastCheck = $info.find('.last-check-time');
                } else {
                    $lastCheck = $lastCheck.find('span').length ? 
                        $lastCheck.find('span') : 
                        $lastCheck;
                }
                $lastCheck.text(timeAgo);
            }
            
            // Update latest version
            if (data.version && data.found) {
                let $latestVersion = $info.find('p:contains("Latest Version")');
                
                if ($latestVersion.length === 0) {
                    $info.append('<p><strong>Latest Version:</strong> <span class="latest-version"></span></p>');
                    $latestVersion = $info.find('.latest-version');
                } else {
                    $latestVersion = $latestVersion.find('span.latest-version').length ? 
                        $latestVersion.find('span.latest-version') : 
                        $latestVersion;
                }
                
                $latestVersion.html(data.version);
                
                // Add update badge if newer
                if (data.version !== data.current) {
                    if (!$latestVersion.next('.almaseo-badge').length) {
                        $latestVersion.after(' <span class="almaseo-badge almaseo-badge-warning">Update Available</span>');
                    }
                }
            }
        }
        
        /**
         * Show update prompt
         */
        function showUpdatePrompt(data) {
            // Check if WordPress update UI already shows it
            if ($('.plugin-update-tr[data-plugin*="almaseo"]').length) {
                return; // WordPress will handle it
            }
            
            // Create custom update notice
            const $notice = $('<div class="notice notice-info almaseo-update-notice">' +
                '<p><strong>AlmaSEO Update Available!</strong></p>' +
                '<p>Version ' + data.version + ' is available. ' +
                '<a href="' + (data.info_url || '#') + '" target="_blank">View details</a> | ' +
                '<a href="plugins.php">Go to Plugins page to update</a></p>' +
                '</div>');
            
            $('.almaseo-header').after($notice);
            
            setTimeout(function() {
                $notice.slideUp();
            }, 10000);
        }
        
        /**
         * Get time ago string
         */
        function getTimeAgo(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            
            let interval = Math.floor(seconds / 31536000);
            if (interval > 1) return interval + ' years ago';
            if (interval === 1) return '1 year ago';
            
            interval = Math.floor(seconds / 2592000);
            if (interval > 1) return interval + ' months ago';
            if (interval === 1) return '1 month ago';
            
            interval = Math.floor(seconds / 86400);
            if (interval > 1) return interval + ' days ago';
            if (interval === 1) return '1 day ago';
            
            interval = Math.floor(seconds / 3600);
            if (interval > 1) return interval + ' hours ago';
            if (interval === 1) return '1 hour ago';
            
            interval = Math.floor(seconds / 60);
            if (interval > 1) return interval + ' minutes ago';
            if (interval === 1) return '1 minute ago';
            
            return 'Just now';
        }
        
        /**
         * Toast notification
         */
        function showToast(message, type) {
            const $toast = $('<div class="almaseo-toast almaseo-toast-' + type + '">' +
                '<span class="dashicons dashicons-' + 
                (type === 'success' ? 'yes' : type === 'error' ? 'warning' : 'info') + 
                '"></span> ' + message + '</div>');
            
            $('body').append($toast);
            
            setTimeout(function() {
                $toast.addClass('show');
            }, 100);
            
            setTimeout(function() {
                $toast.removeClass('show');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 3000);
        }
        
        // Check for updates on page load if last check > 24h
        const lastCheckTime = $('#check-updates-btn').data('last-check');
        if (lastCheckTime && (Date.now() / 1000 - lastCheckTime) > 86400) {
            setTimeout(function() {
                checkForUpdates(true); // Silent check
            }, 5000);
        }
    });
    
})(jQuery);/**
 * AlmaSEO Sitemaps Phase 2 JavaScript Updates
 * 
 * @package AlmaSEO
 */

// STEP 6: Wrap in jQuery to ensure $ is available
jQuery(function($) {
    'use strict';

    /**
     * Validate sitemap
     */
    function validateSitemap() {
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_validate_sitemap',
            nonce: almaseoSitemaps.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
                
                // Update validation display
                if (response.data.issues && response.data.issues.length > 0) {
                    let issuesHtml = '<ul>';
                    response.data.issues.forEach(function(issue) {
                        issuesHtml += '<li>' + issue + '</li>';
                    });
                    issuesHtml += '</ul>';
                    $('#validation-results').html(issuesHtml);
                } else {
                    $('#validation-results').html('<p class="success">No issues found!</p>');
                }
            } else {
                showNotice(response.data || 'Validation failed', 'error');
            }
        });
    }

    /**
     * Submit to search engines
     */
    function submitToSearchEngines() {
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_submit_sitemap',
            nonce: almaseoSitemaps.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
            } else {
                showNotice(response.data || 'Submission failed', 'error');
            }
        });
    }

    /**
     * IndexNow submission
     */
    function submitIndexNow(urls) {
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_indexnow_submit',
            nonce: almaseoSitemaps.nonce,
            urls: urls
        })
        .done(function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
                
                // Update IndexNow stats
                if (response.data.stats) {
                    $('#indexnow-submissions').text(response.data.stats.total);
                    $('#indexnow-last-submit').text(response.data.stats.last_submit);
                }
            } else {
                showNotice(response.data || 'IndexNow submission failed', 'error');
            }
        });
    }

    /**
     * Build static sitemaps
     */
    function buildStaticSitemaps() {
        const $button = $('#rebuild-static');
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('Building...');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_build_static_sitemaps',
            nonce: almaseoSitemaps.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
                
                // Update stats display
                if (response.data.stats) {
                    $('#total-files').text(response.data.stats.files);
                    $('#total-urls').text(response.data.stats.urls);
                    $('#last-built').text(response.data.stats.last_built);
                }
            } else {
                showNotice(response.data || 'Build failed', 'error');
            }
        })
        .always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    }

    /**
     * Export/Import settings
     */
    function exportSettings() {
        window.location.href = almaseoSitemaps.ajaxUrl + '?action=almaseo_export_settings&nonce=' + almaseoSitemaps.nonce;
    }

    function importSettings() {
        const fileInput = $('<input type="file" accept=".json">');
        fileInput.on('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const settings = JSON.parse(e.target.result);
                    
                    $.post(almaseoSitemaps.ajaxUrl, {
                        action: 'almaseo_import_settings',
                        nonce: almaseoSitemaps.nonce,
                        settings: JSON.stringify(settings)
                    })
                    .done(function(response) {
                        if (response.success) {
                            showNotice('Settings imported successfully', 'success');
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showNotice(response.data || 'Import failed', 'error');
                        }
                    });
                } catch (error) {
                    showNotice('Invalid settings file', 'error');
                }
            };
            reader.readAsText(file);
        });
        fileInput.trigger('click');
    }

    /**
     * Helper functions
     */
    function showNotice(message, type) {
        const $notice = $('<div>')
            .addClass('notice notice-' + type + ' is-dismissible')
            .html('<p>' + message + '</p>');
        
        $('.wrap > h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    function generateRandomKey(length) {
        const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        let key = '';
        for (let i = 0; i < length; i++) {
            key += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return key;
    }

    /**
     * Initialize handlers
     */
    $(document).ready(function() {
        // Generate IndexNow key
        $('#generate-indexnow-key').on('click', function() {
            const key = generateRandomKey(32);
            $('#indexnow-key').val(key);
        });
        
        // Verify IndexNow key
        $('#verify-indexnow-key').on('click', function() {
            const key = $('#indexnow-key').val();
            if (!key) {
                showNotice('Please enter an IndexNow key', 'error');
                return;
            }
            
            $.post(almaseoSitemaps.ajaxUrl, {
                action: 'almaseo_verify_indexnow',
                nonce: almaseoSitemaps.nonce,
                key: key
            })
            .done(function(response) {
                if (response.success) {
                    showNotice('IndexNow key verified successfully', 'success');
                } else {
                    showNotice(response.data || 'Verification failed', 'error');
                }
            });
        });
        
        // Attach button handlers
        $('#validate-sitemap').on('click', validateSitemap);
        $('#submit-to-search-engines').on('click', submitToSearchEngines);
        $('#rebuild-static').on('click', buildStaticSitemaps);
        $('#export-settings').on('click', exportSettings);
        $('#import-settings').on('click', importSettings);
        
        // IndexNow bulk submit
        $('#indexnow-bulk-submit').on('click', function() {
            const urls = $('#indexnow-urls').val().split('\n').filter(url => url.trim());
            if (urls.length === 0) {
                showNotice('Please enter at least one URL', 'error');
                return;
            }
            submitIndexNow(urls);
        });
    });

}); // End jQuery wrapper/**
 * AlmaSEO Sitemaps Phase 3 JavaScript
 * 
 * Additional URLs management, conflict scanning, and diff reporting
 * 
 * @package AlmaSEO
 * @since 4.7.0
 */

(function($) {
    'use strict';
    
    /**
     * Phase 3: Additional URLs Management
     */
    
    // Add URL button
    $('#add-url-btn').on('click', function() {
        $('#add-url-modal').fadeIn();
        $('#new-url').focus();
    });
    
    // Save new URL
    $('#save-new-url').on('click', function() {
        const $btn = $(this);
        const url = $('#new-url').val();
        const priority = $('#new-priority').val();
        const changefreq = $('#new-changefreq').val();
        
        if (!url) {
            showToast('Please enter a URL', 'error');
            return;
        }
        
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_add_additional_url',
            nonce: almaseoSitemaps.nonce,
            url: url,
            priority: priority,
            changefreq: changefreq
        }, function(response) {
            if (response.success) {
                showToast(response.data.message, 'success');
                $('#add-url-modal').fadeOut();
                $('#new-url').val('');
                $('#new-priority').val('0.5');
                $('#new-changefreq').val('');
                
                // Reload page to show new URL
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(response.data || 'Failed to add URL', 'error');
            }
            
            $btn.prop('disabled', false);
        });
    });
    
    // Import CSV button
    $('#import-csv-btn').on('click', function() {
        $('#import-csv-modal').fadeIn();
    });
    
    // Import CSV confirm
    $('#import-csv-confirm').on('click', function() {
        const $btn = $(this);
        const csv = $('#csv-content').val();
        
        if (!csv.trim()) {
            showToast('Please enter CSV content', 'error');
            return;
        }
        
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_import_urls_csv',
            nonce: almaseoSitemaps.nonce,
            csv: csv
        }, function(response) {
            if (response.success) {
                showToast(response.data.message, 'success');
                
                if (response.data.errors && response.data.errors.length > 0) {
                    console.warn('Import errors:', response.data.errors);
                }
                
                $('#import-csv-modal').fadeOut();
                $('#csv-content').val('');
                
                // Reload page
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(response.data || 'Import failed', 'error');
            }
            
            $btn.prop('disabled', false);
        });
    });
    
    // Export CSV button
    $('#export-csv-btn').on('click', function() {
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_export_urls_csv',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                downloadCSV(response.data.csv, response.data.filename);
            } else {
                showToast('Export failed', 'error');
            }
        });
    });
    
    /**
     * Phase 3: Conflict Scanner
     */
    
    let scanCheckInterval = null;
    
    // Start scan button
    $('#scan-conflicts-btn').on('click', function() {
        const $btn = $(this);
        const originalHtml = $btn.html();
        
        $btn.html('<span class="dashicons dashicons-update spinning"></span> Starting...');
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_start_conflict_scan',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                showToast('Scan started', 'success');
                
                // Start checking status
                scanCheckInterval = setInterval(checkScanStatus, 2000);
            } else {
                showToast(response.data || 'Failed to start scan', 'error');
                $btn.html(originalHtml);
                $btn.prop('disabled', false);
            }
        });
    });
    
    // Check scan status
    function checkScanStatus() {
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_get_conflict_status',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                const status = response.data;
                
                if (status.status === 'running') {
                    // Update progress display
                    updateScanProgress(status);
                } else if (status.status === 'complete') {
                    // Scan complete
                    clearInterval(scanCheckInterval);
                    showToast('Scan complete', 'success');
                    updateScanResults(status);
                    
                    // Re-enable button
                    $('#scan-conflicts-btn').html('<span class="dashicons dashicons-search"></span> Start Scan');
                    $('#scan-conflicts-btn').prop('disabled', false);
                }
            }
        });
    }
    
    // Update scan progress display
    function updateScanProgress(status) {
        const $container = $('#scan-conflicts-btn').closest('.almaseo-card').find('.almaseo-card-body');
        
        let html = `<div class="almaseo-scan-progress">
            <div class="almaseo-progress-bar">
                <div class="almaseo-progress-fill" style="width: ${status.progress}%"></div>
            </div>
            <p>Scanning: ${status.checked}/${status.total} URLs checked (${status.issues} issues found)</p>
        </div>`;
        
        $container.html(html);
    }
    
    // Update scan results display
    function updateScanResults(status) {
        const $container = $('#scan-conflicts-btn').closest('.almaseo-card').find('.almaseo-card-body');
        
        let html = `<div class="almaseo-scan-results">
            <div class="almaseo-stat-grid">
                <div class="almaseo-stat">
                    <div class="almaseo-stat-value">${status.checked}</div>
                    <div class="almaseo-stat-label">URLs Checked</div>
                </div>
                <div class="almaseo-stat">
                    <div class="almaseo-stat-value almaseo-text-warning">${status.issues}</div>
                    <div class="almaseo-stat-label">Issues Found</div>
                </div>
            </div>`;
        
        if (status.issues > 0) {
            html += `<button type="button" class="button" id="view-conflicts-btn">View Details</button>`;
        }
        
        html += `</div>`;
        
        $container.html(html);
        
        // Bind view details button
        $('#view-conflicts-btn').on('click', viewConflictDetails);
    }
    
    // View conflict details
    function viewConflictDetails() {
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_get_conflict_results',
            nonce: almaseoSitemaps.nonce,
            filter: 'all',
            page: 1
        }, function(response) {
            if (response.success) {
                displayConflictModal(response.data);
            }
        });
    }
    
    // Display conflict modal
    function displayConflictModal(data) {
        let html = '<table class="widefat">';
        html += '<thead><tr><th>URL</th><th>Issues</th><th>Status</th><th>Details</th></tr></thead>';
        html += '<tbody>';
        
        data.items.forEach(function(item) {
            const issueLabels = {
                'http_404': '404 Not Found',
                'http_redirect': 'Redirect',
                'http_5xx': 'Server Error',
                'robots_block': 'Blocked by robots.txt',
                'noindex': 'Has noindex',
                'canonical_mismatch': 'Canonical mismatch'
            };
            
            const issues = item.issues.map(i => issueLabels[i] || i).join(', ');
            
            html += '<tr>';
            html += `<td>${item.url}</td>`;
            html += `<td>${issues}</td>`;
            html += `<td>${item.http || '-'}</td>`;
            html += `<td>${item.detail || '-'}</td>`;
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        
        $('#conflicts-list').html(html);
        $('#conflicts-modal').fadeIn();
    }
    
    // Export conflicts CSV
    $('#export-conflicts-csv').on('click', function() {
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_export_conflicts_csv',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                downloadCSV(response.data.csv, response.data.filename);
            }
        });
    });
    
    /**
     * Phase 3: Diff Report
     */
    
    // Create snapshot button
    $('#create-snapshot-btn').on('click', function() {
        const $btn = $(this);
        const originalHtml = $btn.html();
        
        $btn.html('<span class="dashicons dashicons-update spinning"></span> Creating...');
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_create_snapshot',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                showToast(response.data.message, 'success');
                
                // Update display
                const info = `Snapshot created: ${response.data.urls} URLs (${formatBytes(response.data.size)})`;
                showToast(info, 'info');
                
                // Reload to show new snapshot
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(response.data || 'Failed to create snapshot', 'error');
            }
            
            $btn.html(originalHtml);
            $btn.prop('disabled', false);
        });
    });
    
    // Compare snapshots button
    $('#compare-snapshots-btn').on('click', function() {
        const $btn = $(this);
        const originalHtml = $btn.html();
        
        $btn.html('<span class="dashicons dashicons-update spinning"></span> Comparing...');
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_compare_snapshots',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                displayDiffModal(response.data.summary);
            } else {
                showToast(response.data || 'Comparison failed', 'error');
            }
            
            $btn.html(originalHtml);
            $btn.prop('disabled', false);
        });
    });
    
    // Display diff modal
    function displayDiffModal(summary) {
        // Set up tabs
        $('.almaseo-tab').on('click', function() {
            $('.almaseo-tab').removeClass('active');
            $(this).addClass('active');
            
            const tab = $(this).data('tab');
            displayDiffTab(tab, summary);
        });
        
        // Show first tab
        displayDiffTab('added', summary);
        
        $('#diff-modal').fadeIn();
    }
    
    // Display diff tab content
    function displayDiffTab(tab, summary) {
        let html = '';
        let items = [];
        
        if (tab === 'added' && summary.sample_added) {
            items = summary.sample_added;
        } else if (tab === 'removed' && summary.sample_removed) {
            items = summary.sample_removed;
        } else if (tab === 'changed' && summary.sample_changed) {
            items = summary.sample_changed;
        }
        
        if (items.length > 0) {
            html = '<ul class="almaseo-url-list">';
            items.forEach(function(item) {
                if (tab === 'changed') {
                    html += `<li>${item.url}<br>
                        <small>Old: ${item.old_lastmod || 'none'} → New: ${item.new_lastmod || 'none'}</small>
                    </li>`;
                } else {
                    html += `<li>${item.url}`;
                    if (item.lastmod) {
                        html += `<br><small>Last modified: ${item.lastmod}</small>`;
                    }
                    html += `</li>`;
                }
            });
            html += '</ul>';
            
            if (items.length === 5) {
                html += '<p class="description">Showing first 5 items. Export CSV for full list.</p>';
            }
        } else {
            html = '<p>No ' + tab + ' URLs</p>';
        }
        
        $('#diff-content').html(html);
    }
    
    // Export diff CSV
    $('#export-diff-csv, #export-diff-btn').on('click', function() {
        // Determine which type to export
        const activeTab = $('.almaseo-tab.active').data('tab') || 'added';
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_export_diff_csv',
            nonce: almaseoSitemaps.nonce,
            type: activeTab
        }, function(response) {
            if (response.success) {
                downloadCSV(response.data.csv, response.data.filename);
            } else {
                showToast(response.data || 'Export failed', 'error');
            }
        });
    });
    
    /**
     * Utility Functions
     */
    
    // Download CSV
    function downloadCSV(content, filename) {
        const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    // Format bytes
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    // Show toast notification
    function showToast(message, type = 'info') {
        const $toast = $('#almaseo-toast');
        $toast.removeClass('success error warning info').addClass(type);
        $toast.text(message).addClass('show');
        
        setTimeout(function() {
            $toast.removeClass('show');
        }, 3000);
    }
    
})(jQuery);/**
 * AlmaSEO Sitemaps Phase 4 JavaScript
 * 
 * Static generation, performance controls, and build management
 * 
 * @package AlmaSEO
 * @since 4.7.0
 */

(function($) {
    'use strict';
    
    /**
     * Phase 4: Static Build Management
     */
    
    // Rebuild static sitemaps
    $('#rebuild-static').on('click', function() {
        const $btn = $(this);
        const originalText = $btn.find('.button-text').text();
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        if (!confirm('This will regenerate all sitemap files. Continue?')) {
            return;
        }
        
        $btn.prop('disabled', true);
        $btn.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-update spin');
        $btn.find('.button-text').text('Building...');
        
        // Add progress indicator
        const $progress = $('<div class="almaseo-build-progress">' +
            '<div class="progress-bar"><div class="progress-fill"></div></div>' +
            '<div class="progress-text">Starting build...</div>' +
            '</div>');
        $btn.parent().after($progress);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_rebuild_static',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                showToast(response.data.message, 'success');
                
                // Update UI with new stats
                if (response.data.stats) {
                    updateBuildStats(response.data.stats);
                }
                
                // Reload page after 2 seconds to show new stats
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                showToast(response.data || 'Build failed', 'error');
                $btn.prop('disabled', false);
                $btn.find('.dashicons').removeClass('spin').addClass('dashicons-update');
                $btn.find('.button-text').text(originalText);
            }
            
            $progress.fadeOut(function() {
                $(this).remove();
            });
        }).fail(function() {
            showToast('Build failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.find('.dashicons').removeClass('spin').addClass('dashicons-update');
            $btn.find('.button-text').text(originalText);
            $progress.remove();
        });
    });
    
    /**
     * Storage mode toggle
     */
    $('input[name="storage_mode"]').on('change', function() {
        const mode = $(this).val();
        $('.almaseo-save-all').show();
        
        // Show/hide relevant controls
        if (mode === 'static') {
            $('#recalculate').hide();
            $('#rebuild-static').show();
        } else {
            $('#rebuild-static').hide();
            $('#recalculate').show();
        }
    });
    
    /**
     * Gzip toggle
     */
    $('#enable-gzip').on('change', function() {
        $('.almaseo-save-all').show();
    });
    
    /**
     * Update build stats in UI
     */
    function updateBuildStats(stats) {
        // Update chips
        $('.almaseo-chip').each(function() {
            const $chip = $(this);
            const text = $chip.text();
            
            if (text.includes('Files:')) {
                $chip.html('Files: <strong>' + stats.files + '</strong>');
            } else if (text.includes('URLs:')) {
                $chip.html('URLs: <strong>' + stats.urls + '</strong>');
            } else if (text.includes('Duration:')) {
                const duration = (stats.duration_ms / 1000).toFixed(1);
                $chip.html('Duration: <strong>' + duration + 's</strong>');
            }
        });
    }
    
    /**
     * Check build status periodically
     */
    let buildStatusInterval = null;
    
    function checkBuildStatus() {
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_get_build_status',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success && response.data) {
                if (response.data.is_building) {
                    // Show building indicator
                    if (!$('#rebuild-static').prop('disabled')) {
                        $('#rebuild-static').prop('disabled', true);
                        $('#rebuild-static .button-text').text('Building...');
                        $('#rebuild-static .dashicons').addClass('spin');
                    }
                    
                    // Start checking if not already
                    if (!buildStatusInterval) {
                        buildStatusInterval = setInterval(checkBuildStatus, 5000);
                    }
                } else {
                    // Build complete
                    if ($('#rebuild-static').prop('disabled')) {
                        $('#rebuild-static').prop('disabled', false);
                        $('#rebuild-static .button-text').text('Rebuild (Static)');
                        $('#rebuild-static .dashicons').removeClass('spin');
                        
                        // Reload to show new stats
                        location.reload();
                    }
                    
                    // Stop checking
                    if (buildStatusInterval) {
                        clearInterval(buildStatusInterval);
                        buildStatusInterval = null;
                    }
                }
            }
        });
    }
    
    // Check on page load if in static mode
    $(document).ready(function() {
        const isStatic = $('input[name="storage_mode"]:checked').val() === 'static';
        const isBuilding = $('#rebuild-static').prop('disabled');
        
        if (isStatic && isBuilding) {
            checkBuildStatus();
        }
    });
    
    /**
     * Toast notification helper
     */
    function showToast(message, type) {
        const $toast = $('<div class="almaseo-toast almaseo-toast-' + type + '">' +
            '<span class="dashicons dashicons-' + (type === 'success' ? 'yes' : 'warning') + '"></span>' +
            message + '</div>');
        
        $('body').append($toast);
        
        setTimeout(function() {
            $toast.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }
    
})(jQuery);/**
 * AlmaSEO Sitemaps Phase 5A JavaScript
 * 
 * Delta sitemap controls and management
 * 
 * @package AlmaSEO
 * @since 4.8.0
 */

(function($) {
    'use strict';
    
    /**
     * Phase 5A: Delta Sitemap Management
     */
    
    // Enable/disable delta sitemap
    $('#delta-enabled').on('change', function() {
        $('.almaseo-save-all').show();
    });
    
    // Delta settings
    $('#delta-max-urls, #delta-retention').on('change', function() {
        $('.almaseo-save-all').show();
    });
    
    // Open delta sitemap
    $('#open-delta').on('click', function() {
        window.open($('#delta-url').val(), '_blank');
    });
    
    // Copy delta URL
    $('#copy-delta-url').on('click', function() {
        const $btn = $(this);
        const $input = $('#delta-url');
        
        // Select and copy
        $input.select();
        document.execCommand('copy');
        
        // Visual feedback
        $btn.find('.dashicons').removeClass('dashicons-clipboard').addClass('dashicons-yes');
        $btn.text(almaseoSitemaps.i18n.copied);
        
        setTimeout(function() {
            $btn.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-clipboard');
            $btn.text('Copy URL');
        }, 2000);
    });
    
    // Force ping
    $('#force-ping').on('click', function() {
        const $btn = $(this);
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        if (!confirm('This will immediately submit URLs to IndexNow. Continue?')) {
            return;
        }
        
        $btn.prop('disabled', true);
        const originalText = $btn.text();
        $btn.find('.dashicons').removeClass('dashicons-megaphone').addClass('dashicons-update spin');
        $btn.contents().filter(function() {
            return this.nodeType === 3;
        }).replaceWith(' Pinging...');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_force_delta_ping',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                showToast(response.data.message, 'success');
                
                // Update UI to reflect successful ping
                const $chips = $('.almaseo-chip');
                $chips.each(function() {
                    const text = $(this).text();
                    if (text.includes('Last ping:')) {
                        $(this).html('Last ping: <strong>just now</strong>');
                    }
                });
            } else {
                showToast(response.data || 'Ping failed', 'error');
            }
            
            $btn.prop('disabled', false);
            $btn.find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-megaphone');
            $btn.contents().filter(function() {
                return this.nodeType === 3;
            }).replaceWith(' Force Ping');
        }).fail(function() {
            showToast('Ping failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-megaphone');
            $btn.contents().filter(function() {
                return this.nodeType === 3;
            }).replaceWith(' Force Ping');
        });
    });
    
    // Purge old entries
    $('#purge-old').on('click', function() {
        const $btn = $(this);
        
        if (!confirm('This will remove entries older than the retention period. Continue?')) {
            return;
        }
        
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.find('.dashicons').removeClass('dashicons-trash').addClass('dashicons-update spin');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_purge_old_delta',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                showToast(response.data.message, 'success');
                
                // Update count in UI
                $('.almaseo-chip').each(function() {
                    const text = $(this).text();
                    if (text.includes('URLs') && !text.includes('Max')) {
                        $(this).text(response.data.remaining + ' URLs');
                    }
                });
                
                // Update info box if present
                $('.almaseo-info-box p:first').text(
                    'Ring buffer: ' + response.data.remaining + ' URLs'
                );
                
                // Disable force ping if no URLs left
                if (response.data.remaining === 0) {
                    $('#force-ping').prop('disabled', true);
                }
            } else {
                showToast(response.data || 'Purge failed', 'error');
            }
            
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        }).fail(function() {
            showToast('Purge failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        });
    });
    
    /**
     * Save settings (extend existing handler)
     */
    $(document).on('almaseo-save-settings', function(e, settings) {
        // Add delta settings
        settings.delta = {
            enabled: $('#delta-enabled').is(':checked'),
            max_urls: parseInt($('#delta-max-urls').val()),
            retention_days: parseInt($('#delta-retention').val())
        };
    });
    
    /**
     * Live update of delta status
     */
    function updateDeltaStatus() {
        // This could be called periodically to refresh delta stats
        // For now, it's manual via the buttons
    }
    
    /**
     * Toast notification helper
     */
    function showToast(message, type) {
        const $toast = $('<div class="almaseo-toast almaseo-toast-' + type + '">' +
            '<span class="dashicons dashicons-' + (type === 'success' ? 'yes' : 'warning') + '"></span>' +
            message + '</div>');
        
        $('body').append($toast);
        
        setTimeout(function() {
            $toast.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }
    
})(jQuery);/**
 * AlmaSEO Sitemaps Phase 5B JavaScript
 * 
 * Hreflang controls and management
 * 
 * @package AlmaSEO
 * @since 4.9.0
 */

(function($) {
    'use strict';
    
    /**
     * Phase 5B: Hreflang Management
     */
    
    // Enable/disable hreflang
    $('#hreflang-enabled').on('change', function() {
        $('.almaseo-save-all').show();
        
        // Toggle visibility of settings
        const enabled = $(this).is(':checked');
        $('.hreflang-settings').toggleClass('disabled', !enabled);
    });
    
    // Hreflang settings changes
    $('#hreflang-source, #hreflang-default, #hreflang-x-default').on('change', function() {
        $('.almaseo-save-all').show();
    });
    
    // Locale mapping changes
    $('.hreflang-locale-map').on('change', function() {
        $('.almaseo-save-all').show();
    });
    
    // Validate hreflang
    $('#validate-hreflang').on('click', function() {
        const $btn = $(this);
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.find('.dashicons').removeClass('dashicons-yes-alt').addClass('dashicons-update spin');
        $btn.contents().filter(function() {
            return this.nodeType === 3;
        }).replaceWith(' Validating...');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_validate_hreflang',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                const result = response.data.result;
                
                if (result.ok) {
                    showToast(response.data.message, 'success');
                    
                    // Update UI to show valid status
                    updateValidationUI(result, true);
                } else {
                    showToast(response.data.message, 'warning');
                    
                    // Update UI to show issues
                    updateValidationUI(result, false);
                    
                    // Show export button if issues found
                    $('#export-hreflang-issues').show();
                }
            } else {
                showToast(response.data || 'Validation failed', 'error');
            }
            
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        }).fail(function() {
            showToast('Validation failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        });
    });
    
    // Rebuild with hreflang
    $('#rebuild-with-hreflang').on('click', function() {
        const $btn = $(this);
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        if (!confirm('This will rebuild all sitemaps with hreflang data. Continue?')) {
            return;
        }
        
        // Trigger the rebuild (reuse Phase 4 rebuild)
        $('#rebuild-static').trigger('click');
    });
    
    // Export hreflang issues
    $('#export-hreflang-issues').on('click', function() {
        const $btn = $(this);
        
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_export_hreflang_issues',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                // Download CSV
                downloadCSV(response.data.csv, response.data.filename);
                showToast('Export complete', 'success');
            } else {
                showToast(response.data || 'Export failed', 'error');
            }
            
            $btn.prop('disabled', false);
        }).fail(function() {
            showToast('Export failed - server error', 'error');
            $btn.prop('disabled', false);
        });
    });
    
    /**
     * Update validation UI
     */
    function updateValidationUI(result, isValid) {
        // Update chips
        const $validationChip = $('.almaseo-chip:contains("Valid"), .almaseo-chip:contains("Issues")');
        
        if ($validationChip.length) {
            $validationChip.removeClass('almaseo-chip-success almaseo-chip-warning');
            
            if (isValid) {
                $validationChip.addClass('almaseo-chip-success').text('Valid');
            } else {
                const issueCount = result.missing_pairs + result.orphans + result.mismatch;
                $validationChip.addClass('almaseo-chip-warning').text(issueCount + ' Issues');
            }
        }
        
        // Update or create info box
        let $infoBox = $('.almaseo-info-box.hreflang-validation');
        
        if (!$infoBox.length) {
            $infoBox = $('<div class="almaseo-info-box hreflang-validation"></div>');
            $('#validate-hreflang').parent().after($infoBox);
        }
        
        $infoBox.removeClass('almaseo-info-success almaseo-info-warning');
        $infoBox.addClass(isValid ? 'almaseo-info-success' : 'almaseo-info-warning');
        
        let html = '<p>Last validation: just now</p>';
        
        if (!isValid) {
            html += '<ul>';
            if (result.missing_pairs > 0) {
                html += '<li>' + result.missing_pairs + ' missing language pairs</li>';
            }
            if (result.orphans > 0) {
                html += '<li>' + result.orphans + ' orphan links</li>';
            }
            if (result.mismatch > 0) {
                html += '<li>' + result.mismatch + ' invalid codes</li>';
            }
            html += '</ul>';
            
            if (result.samples && result.samples.length > 0) {
                html += '<p><strong>Sample issues:</strong></p><ul class="sample-issues">';
                result.samples.slice(0, 3).forEach(function(sample) {
                    html += '<li><code>' + sample.url + '</code>: ' + sample.issue + '</li>';
                });
                html += '</ul>';
            }
        }
        
        $infoBox.html(html);
    }
    
    /**
     * Save settings (extend existing handler)
     */
    $(document).on('almaseo-save-settings', function(e, settings) {
        // Collect hreflang settings
        const locales = {};
        $('.hreflang-locale-map').each(function() {
            const locale = $(this).data('locale');
            const hreflang = $(this).val();
            if (locale && hreflang) {
                locales[locale] = hreflang;
            }
        });
        
        settings.hreflang = {
            enabled: $('#hreflang-enabled').is(':checked'),
            source: $('#hreflang-source').val(),
            default: $('#hreflang-default').val(),
            x_default_url: $('#hreflang-x-default').val(),
            locales: locales
        };
    });
    
    /**
     * Download CSV helper
     */
    function downloadCSV(csv, filename) {
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    /**
     * Toast notification helper
     */
    function showToast(message, type) {
        const $toast = $('<div class="almaseo-toast almaseo-toast-' + type + '">' +
            '<span class="dashicons dashicons-' + (type === 'success' ? 'yes' : 'warning') + '"></span>' +
            message + '</div>');
        
        $('body').append($toast);
        
        setTimeout(function() {
            $toast.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }
    
})(jQuery);/**
 * AlmaSEO Sitemaps Phase 5C JavaScript
 * 
 * Media sitemaps UI and controls
 * 
 * @package AlmaSEO
 * @since 4.10.0
 */

(function($) {
    'use strict';
    
    /**
     * Phase 5C: Media Sitemaps
     */
    
    // Enable/disable image sitemap
    $('#media-image-enabled').on('change', function() {
        $('.almaseo-save-all').show();
        
        // Toggle visibility of image settings
        const enabled = $(this).is(':checked');
        $('.image-settings').toggleClass('disabled', !enabled);
        
        // Update UI
        if (enabled) {
            $('#open-image-sitemap').parent().parent().show();
        } else {
            $('#open-image-sitemap').parent().parent().hide();
        }
    });
    
    // Enable/disable video sitemap
    $('#media-video-enabled').on('change', function() {
        $('.almaseo-save-all').show();
        
        // Toggle visibility of video settings
        const enabled = $(this).is(':checked');
        $('.video-settings').toggleClass('disabled', !enabled);
        
        // Update UI
        if (enabled) {
            $('#open-video-sitemap').parent().parent().show();
        } else {
            $('#open-video-sitemap').parent().parent().hide();
        }
    });
    
    // Media settings changes
    $('#media-image-max, #media-image-dedupe, #media-video-max, #media-video-oembed').on('change', function() {
        $('.almaseo-save-all').show();
    });
    
    // Open image sitemap
    $('#open-image-sitemap').on('click', function() {
        window.open(almaseoSitemaps.sitemapUrl.replace('-sitemap.xml', '-sitemap-image-1.xml'), '_blank');
    });
    
    // Open video sitemap
    $('#open-video-sitemap').on('click', function() {
        window.open(almaseoSitemaps.sitemapUrl.replace('-sitemap.xml', '-sitemap-video-1.xml'), '_blank');
    });
    
    // Scan media
    $('#scan-media').on('click', function() {
        const $btn = $(this);
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.find('.dashicons').removeClass('dashicons-search').addClass('dashicons-update spin');
        $btn.contents().filter(function() {
            return this.nodeType === 3;
        }).replaceWith(' Scanning...');
        
        // Show stats box
        const $stats = $('#media-stats');
        $stats.show().html('<p>Scanning media content...</p>');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_scan_media',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                const stats = response.data.stats;
                let html = '<h4>Media Scan Results</h4>';
                
                // Image stats
                if (stats.images.found > 0 || stats.images.urls_with_images > 0) {
                    html += '<div class="media-stat-section">';
                    html += '<strong>Images:</strong><br>';
                    html += stats.images.found + ' images found across ' + stats.images.urls_with_images + ' URLs<br>';
                    if (stats.images.sample_urls.length > 0) {
                        html += '<small>Sample URLs with images:</small><ul>';
                        stats.images.sample_urls.forEach(function(url) {
                            html += '<li><code>' + url + '</code></li>';
                        });
                        html += '</ul>';
                    }
                    html += '</div>';
                }
                
                // Video stats
                if (stats.videos.found > 0 || stats.videos.urls_with_videos > 0) {
                    html += '<div class="media-stat-section">';
                    html += '<strong>Videos:</strong><br>';
                    html += stats.videos.found + ' videos found across ' + stats.videos.urls_with_videos + ' URLs<br>';
                    if (stats.videos.sample_urls.length > 0) {
                        html += '<small>Sample URLs with videos:</small><ul>';
                        stats.videos.sample_urls.forEach(function(url) {
                            html += '<li><code>' + url + '</code></li>';
                        });
                        html += '</ul>';
                    }
                    html += '</div>';
                }
                
                if (stats.images.found === 0 && stats.videos.found === 0) {
                    html += '<p>No media content found. Make sure media sitemaps are enabled and your content contains images or videos.</p>';
                }
                
                $stats.removeClass('almaseo-info-warning').addClass('almaseo-info-success').html(html);
                showToast(response.data.message, 'success');
            } else {
                $stats.removeClass('almaseo-info-success').addClass('almaseo-info-warning')
                    .html('<p>Scan failed: ' + (response.data || 'Unknown error') + '</p>');
                showToast(response.data || 'Scan failed', 'error');
            }
            
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        }).fail(function() {
            $stats.removeClass('almaseo-info-success').addClass('almaseo-info-warning')
                .html('<p>Scan failed - server error</p>');
            showToast('Scan failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        });
    });
    
    // Validate media
    $('#validate-media').on('click', function() {
        const $btn = $(this);
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.find('.dashicons').removeClass('dashicons-yes-alt').addClass('dashicons-update spin');
        $btn.contents().filter(function() {
            return this.nodeType === 3;
        }).replaceWith(' Validating...');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_validate_media',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                const result = response.data.result;
                let hasIssues = false;
                let issueHtml = '<h4>Validation Results</h4>';
                
                // Image validation
                if (result.image.enabled) {
                    issueHtml += '<div class="media-validation-section">';
                    issueHtml += '<strong>Image Sitemap:</strong> ';
                    if (result.image.issues.length === 0) {
                        issueHtml += '<span style="color: green;">✓ Valid</span><br>';
                        issueHtml += result.image.count + ' images validated';
                    } else {
                        hasIssues = true;
                        issueHtml += '<span style="color: orange;">⚠ ' + result.image.issues.length + ' issues</span><br>';
                        issueHtml += '<ul>';
                        result.image.issues.slice(0, 5).forEach(function(issue) {
                            issueHtml += '<li>' + issue + '</li>';
                        });
                        if (result.image.issues.length > 5) {
                            issueHtml += '<li>... and ' + (result.image.issues.length - 5) + ' more</li>';
                        }
                        issueHtml += '</ul>';
                    }
                    issueHtml += '</div>';
                }
                
                // Video validation
                if (result.video.enabled) {
                    issueHtml += '<div class="media-validation-section">';
                    issueHtml += '<strong>Video Sitemap:</strong> ';
                    if (result.video.issues.length === 0) {
                        issueHtml += '<span style="color: green;">✓ Valid</span><br>';
                        issueHtml += result.video.count + ' videos validated';
                    } else {
                        hasIssues = true;
                        issueHtml += '<span style="color: orange;">⚠ ' + result.video.issues.length + ' issues</span><br>';
                        issueHtml += '<ul>';
                        result.video.issues.slice(0, 5).forEach(function(issue) {
                            issueHtml += '<li>' + issue + '</li>';
                        });
                        if (result.video.issues.length > 5) {
                            issueHtml += '<li>... and ' + (result.video.issues.length - 5) + ' more</li>';
                        }
                        issueHtml += '</ul>';
                    }
                    issueHtml += '</div>';
                }
                
                const $stats = $('#media-stats');
                $stats.show()
                    .removeClass('almaseo-info-warning almaseo-info-success')
                    .addClass(hasIssues ? 'almaseo-info-warning' : 'almaseo-info-success')
                    .html(issueHtml);
                
                showToast(response.data.message, hasIssues ? 'warning' : 'success');
            } else {
                showToast(response.data || 'Validation failed', 'error');
            }
            
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        }).fail(function() {
            showToast('Validation failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        });
    });
    
    // Rebuild media sitemaps
    $('#rebuild-media').on('click', function() {
        const $btn = $(this);
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        if (!confirm('This will rebuild all media sitemaps. Continue?')) {
            return;
        }
        
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-update spin');
        $btn.contents().filter(function() {
            return this.nodeType === 3;
        }).replaceWith(' Building...');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_rebuild_media',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                showToast(response.data.message, 'success');
                
                // Update stats display
                if (response.data.stats) {
                    const stats = response.data.stats;
                    let html = '<h4>Build Complete</h4>';
                    html += '<p>' + stats.files + ' files generated with ' + stats.urls + ' total URLs</p>';
                    html += '<p>Duration: ' + (stats.duration_ms / 1000).toFixed(1) + ' seconds</p>';
                    
                    $('#media-stats').show()
                        .removeClass('almaseo-info-warning')
                        .addClass('almaseo-info-success')
                        .html(html);
                }
            } else {
                showToast(response.data || 'Build failed', 'error');
            }
            
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        }).fail(function() {
            showToast('Build failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        });
    });
    
    /**
     * Save settings (extend existing handler)
     */
    $(document).on('almaseo-save-settings', function(e, settings) {
        // Collect media settings
        settings.media = {
            image: {
                enabled: $('#media-image-enabled').is(':checked'),
                max_per_url: parseInt($('#media-image-max').val()) || 20,
                dedupe_cdn: $('#media-image-dedupe').is(':checked')
            },
            video: {
                enabled: $('#media-video-enabled').is(':checked'),
                max_per_url: parseInt($('#media-video-max').val()) || 10,
                oembed_cache: $('#media-video-oembed').is(':checked')
            }
        };
    });
    
    /**
     * Toast notification helper
     */
    function showToast(message, type) {
        const $toast = $('<div class="almaseo-toast almaseo-toast-' + type + '">' +
            '<span class="dashicons dashicons-' + (type === 'success' ? 'yes' : 'warning') + '"></span>' +
            message + '</div>');
        
        $('body').append($toast);
        
        setTimeout(function() {
            $toast.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }
    
})(jQuery);/**
 * AlmaSEO Sitemaps Phase 5D JavaScript
 * 
 * News sitemap UI and controls
 * 
 * @package AlmaSEO
 * @since 4.11.0
 */

(function($) {
    'use strict';
    
    /**
     * Phase 5D: News Sitemap
     */
    
    // Enable/disable news sitemap
    $('#news-enabled').on('change', function() {
        $('.almaseo-save-all').show();
        
        // Toggle visibility of news settings
        const enabled = $(this).is(':checked');
        $('.news-settings').toggleClass('disabled', !enabled);
    });
    
    // News settings changes
    $('#news-publisher, #news-language, #news-window, #news-max-items, #news-manual-keywords').on('change', function() {
        $('.almaseo-save-all').show();
    });
    
    $('input[name="news[post_types][]"], input[name="news[categories][]"], input[name="news[genres][]"]').on('change', function() {
        $('.almaseo-save-all').show();
    });
    
    // Keywords source toggle
    $('input[name="news[keywords_source]"]').on('change', function() {
        $('.almaseo-save-all').show();
        
        if ($(this).val() === 'manual') {
            $('#news-manual-keywords-group').slideDown();
        } else {
            $('#news-manual-keywords-group').slideUp();
        }
    });
    
    // Open news sitemap
    $('#open-news-sitemap').on('click', function() {
        window.open(almaseoSitemaps.sitemapUrl.replace('-sitemap.xml', '-sitemap-news-1.xml'), '_blank');
    });
    
    // Copy news URL
    $('#copy-news-url').on('click', function() {
        const url = almaseoSitemaps.sitemapUrl.replace('-sitemap.xml', '-sitemap-news-1.xml');
        const $temp = $('<input>');
        $('body').append($temp);
        $temp.val(url).select();
        document.execCommand('copy');
        $temp.remove();
        
        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.html('<span class="dashicons dashicons-yes"></span>');
        showToast('URL copied!', 'success');
        
        setTimeout(function() {
            $btn.html(originalHtml);
        }, 2000);
    });
    
    // Validate news
    $('#validate-news').on('click', function() {
        const $btn = $(this);
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.find('.dashicons').removeClass('dashicons-yes-alt').addClass('dashicons-update spin');
        $btn.contents().filter(function() {
            return this.nodeType === 3;
        }).replaceWith(' Validating...');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_validate_news',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                const result = response.data.result;
                
                // Update chips
                updateNewsChips(result);
                
                // Update validation box
                updateNewsValidationBox(result);
                
                showToast(response.data.message, result.issues.length === 0 ? 'success' : 'warning');
            } else {
                showToast(response.data || 'Validation failed', 'error');
            }
            
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        }).fail(function() {
            showToast('Validation failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        });
    });
    
    // Rebuild news sitemap
    $('#rebuild-news').on('click', function() {
        const $btn = $(this);
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        if (!confirm('This will rebuild the news sitemap. Continue?')) {
            return;
        }
        
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-update spin');
        $btn.contents().filter(function() {
            return this.nodeType === 3;
        }).replaceWith(' Building...');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_rebuild_news',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                showToast(response.data.message, 'success');
                
                // Update last build chip
                updateNewsBuildChip();
                
                // Optionally trigger validation
                setTimeout(function() {
                    $('#validate-news').trigger('click');
                }, 1000);
            } else {
                showToast(response.data || 'Build failed', 'error');
            }
            
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        }).fail(function() {
            showToast('Build failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        });
    });
    
    /**
     * Update news chips after validation
     */
    function updateNewsChips(result) {
        const $chips = $('.almaseo-card:has(#news-enabled) .almaseo-chips');
        
        // Update items count chip
        let $itemsChip = $chips.find('.almaseo-chip:contains("Items")');
        if ($itemsChip.length === 0) {
            $itemsChip = $('<span class="almaseo-chip"></span>');
            $chips.append($itemsChip);
        }
        $itemsChip.text(result.items + ' Items');
        
        // Update validation chip
        let $validChip = $chips.find('.almaseo-chip:contains("Valid"), .almaseo-chip:contains("Issues")');
        if ($validChip.length === 0) {
            $validChip = $('<span class="almaseo-chip"></span>');
            $chips.append($validChip);
        }
        
        if (result.issues.length === 0) {
            $validChip.removeClass('almaseo-chip-warning').addClass('almaseo-chip-success').text('Valid');
        } else {
            $validChip.removeClass('almaseo-chip-success').addClass('almaseo-chip-warning').text(result.issues.length + ' Issues');
        }
    }
    
    /**
     * Update news validation info box
     */
    function updateNewsValidationBox(result) {
        let $box = $('.almaseo-card:has(#news-enabled) .almaseo-info-box');
        
        if ($box.length === 0) {
            $box = $('<div class="almaseo-info-box"></div>');
            $('#rebuild-news').parent().after($box);
        }
        
        $box.removeClass('almaseo-info-success almaseo-info-warning');
        $box.addClass(result.issues.length === 0 ? 'almaseo-info-success' : 'almaseo-info-warning');
        
        let html = '<p>Last validation: just now</p>';
        
        if (result.samples && result.samples.length > 0) {
            html += '<p>Sample articles:</p><ul>';
            result.samples.forEach(function(sample) {
                html += '<li><strong>' + sample.title + '</strong><br>';
                html += '<small>' + sample.date + '</small></li>';
            });
            html += '</ul>';
        }
        
        if (result.issues.length > 0) {
            html += '<p>Issues found:</p><ul>';
            result.issues.slice(0, 5).forEach(function(issue) {
                html += '<li>' + issue + '</li>';
            });
            if (result.issues.length > 5) {
                html += '<li>... and ' + (result.issues.length - 5) + ' more</li>';
            }
            html += '</ul>';
        }
        
        $box.html(html).show();
    }
    
    /**
     * Update build chip after rebuild
     */
    function updateNewsBuildChip() {
        const $chips = $('.almaseo-card:has(#news-enabled) .almaseo-chips');
        let $buildChip = $chips.find('.almaseo-chip:contains("Built:")');
        
        if ($buildChip.length === 0) {
            $buildChip = $('<span class="almaseo-chip"></span>');
            $chips.append($buildChip);
        }
        
        $buildChip.text('Built: just now');
    }
    
    /**
     * Save settings (extend existing handler)
     */
    $(document).on('almaseo-save-settings', function(e, settings) {
        // Collect news settings
        settings.news = {
            enabled: $('#news-enabled').is(':checked'),
            post_types: [],
            categories: [],
            publisher_name: $('#news-publisher').val(),
            language: $('#news-language').val(),
            genres: [],
            keywords_source: $('input[name="news[keywords_source]"]:checked').val(),
            manual_keywords: $('#news-manual-keywords').val(),
            max_items: parseInt($('#news-max-items').val()) || 1000,
            window_hours: parseInt($('#news-window').val()) || 48
        };
        
        // Collect post types
        $('input[name="news[post_types][]"]:checked').each(function() {
            settings.news.post_types.push($(this).val());
        });
        
        // Collect categories
        $('input[name="news[categories][]"]:checked').each(function() {
            settings.news.categories.push(parseInt($(this).val()));
        });
        
        // Collect genres
        $('input[name="news[genres][]"]:checked').each(function() {
            settings.news.genres.push($(this).val());
        });
    });
    
    /**
     * Toast notification helper
     */
    function showToast(message, type) {
        const $toast = $('<div class="almaseo-toast almaseo-toast-' + type + '">' +
            '<span class="dashicons dashicons-' + (type === 'success' ? 'yes' : 'warning') + '"></span>' +
            message + '</div>');
        
        $('body').append($toast);
        
        setTimeout(function() {
            $toast.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }
    
})(jQuery);/**
 * AlmaSEO Sitemaps Phase 6 JavaScript
 * 
 * Final polish: Export/Import, Health Log, Quick Tools
 * 
 * @package AlmaSEO
 * @since 4.12.0
 */

(function($) {
    'use strict';
    
    /**
     * Phase 6: Settings Export/Import
     */
    
    // Export settings
    $('#export-settings-btn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_export_settings',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                // Download JSON
                const blob = new Blob([response.data.json], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = response.data.filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                showToast('Settings exported', 'success');
            } else {
                showToast(response.data || 'Export failed', 'error');
            }
            
            $btn.prop('disabled', false);
        }).fail(function() {
            showToast('Export failed', 'error');
            $btn.prop('disabled', false);
        });
    });
    
    // Import settings
    $('#import-settings-btn').on('click', function() {
        $('#import-settings-file').trigger('click');
    });
    
    $('#import-settings-file').on('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const json = e.target.result;
            
            if (!confirm('This will replace your current settings. Continue?')) {
                $('#import-settings-file').val('');
                return;
            }
            
            $.post(almaseoSitemaps.ajaxUrl, {
                action: 'almaseo_import_settings',
                nonce: almaseoSitemaps.nonce,
                json: json
            }, function(response) {
                if (response.success) {
                    showToast(response.data, 'success');
                    // Reload page to reflect new settings
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showToast(response.data || 'Import failed', 'error');
                }
                
                $('#import-settings-file').val('');
            }).fail(function() {
                showToast('Import failed', 'error');
                $('#import-settings-file').val('');
            });
        };
        
        reader.readAsText(file);
    });
    
    /**
     * Phase 6: Health Log
     */
    
    // Export logs
    $('#export-logs-btn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_export_logs',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                // Download CSV
                const blob = new Blob([response.data.csv], { type: 'text/csv' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = response.data.filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                showToast('Logs exported', 'success');
            } else {
                showToast(response.data || 'No logs to export', 'error');
            }
            
            $btn.prop('disabled', false);
        }).fail(function() {
            showToast('Export failed', 'error');
            $btn.prop('disabled', false);
        });
    });
    
    // Clear logs
    $('#clear-logs-btn').on('click', function() {
        if (!confirm('Clear all health logs?')) {
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_clear_logs',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                showToast(response.data, 'success');
                // Refresh log display
                $('.almaseo-log-list').fadeOut(function() {
                    $(this).html('<p>No events logged yet.</p>').fadeIn();
                });
                $('.almaseo-log-stats span:first').text('0 events total');
                $('.almaseo-log-stats span:last').text('0 in last 24h');
            } else {
                showToast(response.data || 'Failed to clear logs', 'error');
            }
            
            $btn.prop('disabled', false);
        }).fail(function() {
            showToast('Failed to clear logs', 'error');
            $btn.prop('disabled', false);
        });
    });
    
    /**
     * Phase 6: Quick Tools
     */
    
    // Copy all sitemap URLs
    $('#copy-all-urls-btn').on('click', function() {
        const $btn = $(this);
        const $list = $('#sitemap-urls-list');
        const $textarea = $list.find('textarea');
        
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_copy_all_urls',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                $textarea.val(response.data.text);
                $list.slideDown();
                
                // Select and copy
                $textarea.select();
                document.execCommand('copy');
                
                showToast('URLs copied to clipboard', 'success');
                
                // Update button text temporarily
                const originalHtml = $btn.html();
                $btn.html('<span class="dashicons dashicons-yes"></span> Copied!');
                setTimeout(function() {
                    $btn.html(originalHtml);
                }, 2000);
            } else {
                showToast(response.data || 'Failed to get URLs', 'error');
            }
            
            $btn.prop('disabled', false);
        }).fail(function() {
            showToast('Failed to get URLs', 'error');
            $btn.prop('disabled', false);
        });
    });
    
    // Copy shortcode
    $('#copy-shortcode').on('click', function(e) {
        e.preventDefault();
        
        const shortcode = '[almaseo_html_sitemap types="posts,pages,tax" columns="2"]';
        const $temp = $('<input>');
        $('body').append($temp);
        $temp.val(shortcode).select();
        document.execCommand('copy');
        $temp.remove();
        
        const $btn = $(this);
        const originalText = $btn.text();
        $btn.text('Copied!');
        
        setTimeout(function() {
            $btn.text(originalText);
        }, 2000);
    });
    
    // Preview robots.txt
    $('#preview-robots-btn').on('click', function() {
        const $btn = $(this);
        const $preview = $('#robots-preview');
        
        if ($preview.is(':visible')) {
            $preview.slideUp();
            return;
        }
        
        $btn.prop('disabled', true);
        
        // Fetch robots.txt
        $.get('/robots.txt', function(data) {
            $preview.find('.almaseo-code-preview').text(data);
            $preview.slideDown();
            
            // Highlight sitemap lines
            const lines = data.split('\n');
            const highlightedLines = lines.map(function(line) {
                if (line.toLowerCase().indexOf('sitemap:') === 0) {
                    return '<span class="almaseo-highlight">' + line + '</span>';
                }
                return line;
            });
            $preview.find('.almaseo-code-preview').html(highlightedLines.join('\n'));
            
            $btn.prop('disabled', false);
        }).fail(function() {
            showToast('Failed to load robots.txt', 'error');
            $btn.prop('disabled', false);
        });
    });
    
    /**
     * Phase 6: Help Tooltips
     */
    
    // Initialize tooltips
    $('.almaseo-help-icon').on('mouseenter', function() {
        const $icon = $(this);
        const text = $icon.attr('title');
        
        if (!text) return;
        
        // Create tooltip
        const $tooltip = $('<div class="almaseo-tooltip">' + text + '</div>');
        $('body').append($tooltip);
        
        // Position tooltip
        const offset = $icon.offset();
        $tooltip.css({
            top: offset.top - $tooltip.outerHeight() - 5,
            left: offset.left - ($tooltip.outerWidth() / 2) + ($icon.outerWidth() / 2)
        }).fadeIn(200);
        
        $icon.data('tooltip', $tooltip);
    }).on('mouseleave', function() {
        const $icon = $(this);
        const $tooltip = $icon.data('tooltip');
        
        if ($tooltip) {
            $tooltip.fadeOut(200, function() {
                $tooltip.remove();
            });
            $icon.removeData('tooltip');
        }
    });
    
    // Add help icons to key sections
    $(document).ready(function() {
        // Add help to Delta section
        $('.almaseo-card:has(#delta-enabled) .almaseo-card-header h2').after(
            '<span class="almaseo-help-icon" title="Delta sitemaps track recent changes to help search engines discover new and updated content faster">?</span>'
        );
        
        // Add help to Hreflang section
        $('.almaseo-card:has(#hreflang-enabled) .almaseo-card-header h2').after(
            '<span class="almaseo-help-icon" title="Hreflang tags help search engines understand language and regional variations of your content">?</span>'
        );
        
        // Add help to Media section
        $('.almaseo-card:has(#media-image-enabled) .almaseo-card-header h2').after(
            '<span class="almaseo-help-icon" title="Media sitemaps help search engines discover images and videos on your site">?</span>'
        );
        
        // Add help to News section
        $('.almaseo-card:has(#news-enabled) .almaseo-card-header h2').after(
            '<span class="almaseo-help-icon" title="News sitemaps help your content appear in Google News with a rolling 48-hour window">?</span>'
        );
    });
    
    /**
     * Toast notification helper
     */
    function showToast(message, type) {
        const $toast = $('<div class="almaseo-toast almaseo-toast-' + type + '">' +
            '<span class="dashicons dashicons-' + (type === 'success' ? 'yes' : 'warning') + '"></span>' +
            message + '</div>');
        
        $('body').append($toast);
        
        setTimeout(function() {
            $toast.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }
    
})(jQuery);