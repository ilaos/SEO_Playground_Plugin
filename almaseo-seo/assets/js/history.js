/**
 * AlmaSEO Metadata History - JavaScript
 * 
 * @package AlmaSEO
 * @since 6.8.2
 */

(function($) {
    'use strict';
    
    let currentPostId = 0;
    let compareData = null;
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        currentPostId = $('#almaseo-history-post-id').val();
        
        if (!currentPostId) {
            return; // No history container on this page
        }
        
        // Create snapshot button
        $('#almaseo-create-snapshot').on('click', function() {
            const $btn = $(this);
            
            $btn.prop('disabled', true);
            $btn.find('.dashicons').addClass('spin');
            $btn.append('<span class="loading-text"> ' + almaseoHistory.i18n.creating_snapshot + '</span>');
            
            $.post(almaseoHistory.ajaxurl, {
                action: 'almaseo_meta_history_snapshot',
                post_id: currentPostId,
                nonce: almaseoHistory.nonce
            })
            .done(function(response) {
                if (response.success) {
                    $btn.find('.loading-text').text(' ' + almaseoHistory.i18n.snapshot_created);
                    
                    // Reload the page to show new snapshot
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert(response.data || almaseoHistory.i18n.no_changes);
                    $btn.find('.loading-text').remove();
                    $btn.prop('disabled', false);
                    $btn.find('.dashicons').removeClass('spin');
                }
            })
            .fail(function() {
                alert(almaseoHistory.i18n.error);
                $btn.find('.loading-text').remove();
                $btn.prop('disabled', false);
                $btn.find('.dashicons').removeClass('spin');
            });
        });
        
        // Compare button
        $('.history-compare').on('click', function() {
            const version = $(this).data('version');
            openCompareDrawer(version);
        });
        
        // Restore button
        $('.history-restore').on('click', function() {
            const $btn = $(this);
            const versionId = $btn.data('version-id');
            
            if (!confirm(almaseoHistory.i18n.restore_confirm)) {
                return;
            }
            
            $btn.prop('disabled', true);
            $btn.text(almaseoHistory.i18n.restoring);
            
            $.post(almaseoHistory.ajaxurl, {
                action: 'almaseo_meta_history_restore',
                post_id: currentPostId,
                version_id: versionId,
                nonce: almaseoHistory.nonce
            })
            .done(function(response) {
                if (response.success) {
                    $btn.text(almaseoHistory.i18n.restored);
                    
                    // Update current fields in the editor
                    if (response.data.current_fields) {
                        $('#almaseo_seo_title').val(response.data.current_fields.seo_title || '');
                        $('#almaseo_seo_description').val(response.data.current_fields.seo_description || '');
                        $('#almaseo_focus_keyword').val(response.data.current_fields.focus_keyword || '');
                        
                        // Trigger change events
                        $('#almaseo_seo_title, #almaseo_seo_description, #almaseo_focus_keyword').trigger('change');
                    }
                    
                    // Reload after a moment
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    alert(response.data || almaseoHistory.i18n.error);
                    $btn.prop('disabled', false);
                    $btn.text('Restore');
                }
            })
            .fail(function() {
                alert(almaseoHistory.i18n.error);
                $btn.prop('disabled', false);
                $btn.text('Restore');
            });
        });
        
        // More menu toggle
        $('.history-more-btn').on('click', function(e) {
            e.stopPropagation();
            const $dropdown = $(this).siblings('.history-dropdown');
            
            // Hide all other dropdowns
            $('.history-dropdown').not($dropdown).hide();
            
            // Toggle this dropdown
            $dropdown.toggle();
        });
        
        // Close dropdowns on click outside
        $(document).on('click', function() {
            $('.history-dropdown').hide();
        });
        
        // Copy JSON
        $('.history-copy-json').on('click', function(e) {
            e.preventDefault();
            const versionId = $(this).data('version-id');
            
            // Get the snapshot data via AJAX
            $.post(almaseoHistory.ajaxurl, {
                action: 'almaseo_meta_history_compare',
                post_id: currentPostId,
                from_version: versionId,
                to_version: versionId,
                nonce: almaseoHistory.nonce
            })
            .done(function(response) {
                if (response.success && response.data) {
                    const json = JSON.stringify(response.data.from.fields, null, 2);
                    copyToClipboard(json);
                    alert('JSON copied to clipboard');
                }
            });
        });
        
        // Delete version
        $('.history-delete').on('click', function(e) {
            e.preventDefault();
            const versionId = $(this).data('version-id');
            
            if (!confirm(almaseoHistory.i18n.delete_confirm)) {
                return;
            }
            
            $.post(almaseoHistory.ajaxurl, {
                action: 'almaseo_meta_history_delete',
                post_id: currentPostId,
                version_id: versionId,
                nonce: almaseoHistory.nonce
            })
            .done(function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert(response.data || almaseoHistory.i18n.error);
                }
            });
        });
        
        // Drawer close buttons
        $('.drawer-close, .drawer-overlay').on('click', function() {
            closeCompareDrawer();
        });
        
        // Swap versions button
        $('.swap-versions').on('click', function() {
            const fromVal = $('#compare-from-version').val();
            const toVal = $('#compare-to-version').val();
            
            $('#compare-from-version').val(toVal);
            $('#compare-to-version').val(fromVal);
            
            loadComparison();
        });
        
        // Version selector change
        $('#compare-from-version, #compare-to-version').on('change', function() {
            loadComparison();
        });
        
        // Restore to version (from drawer)
        $('#restore-to-version').on('click', function() {
            const toVersion = $('#compare-to-version').val();
            const $restoreBtn = $('.history-item[data-version="' + toVersion + '"] .history-restore');
            
            if ($restoreBtn.length) {
                closeCompareDrawer();
                $restoreBtn.click();
            }
        });
        
        // Add spinning animation
        if (!document.getElementById('history-spin-style')) {
            const style = document.createElement('style');
            style.id = 'history-spin-style';
            style.textContent = `
                @keyframes spin {
                    from { transform: rotate(0deg); }
                    to { transform: rotate(360deg); }
                }
                .dashicons.spin {
                    animation: spin 1s linear infinite;
                    display: inline-block;
                }
            `;
            document.head.appendChild(style);
        }
    });
    
    /**
     * Open compare drawer
     */
    function openCompareDrawer(version) {
        const $drawer = $('#almaseo-history-drawer');
        
        // Set initial versions
        const prevVersion = version - 1;
        if (prevVersion > 0) {
            $('#compare-from-version').val(prevVersion);
        }
        $('#compare-to-version').val(version);
        
        // Show drawer
        $drawer.fadeIn(200);
        
        // Load comparison
        loadComparison();
    }
    
    /**
     * Close compare drawer
     */
    function closeCompareDrawer() {
        $('#almaseo-history-drawer').fadeOut(200);
    }
    
    /**
     * Load comparison
     */
    function loadComparison() {
        const fromVersion = $('#compare-from-version').val();
        const toVersion = $('#compare-to-version').val();
        
        if (!fromVersion || !toVersion) {
            return;
        }
        
        $('.compare-loading').show();
        $('.compare-results').hide();
        
        $.post(almaseoHistory.ajaxurl, {
            action: 'almaseo_meta_history_compare',
            post_id: currentPostId,
            from_version: fromVersion,
            to_version: toVersion,
            nonce: almaseoHistory.nonce
        })
        .done(function(response) {
            if (response.success && response.data) {
                compareData = response.data;
                renderComparison(compareData);
            } else {
                $('.compare-results').html('<p>Failed to load comparison</p>');
            }
        })
        .always(function() {
            $('.compare-loading').hide();
            $('.compare-results').show();
        });
    }
    
    /**
     * Render comparison results
     */
    function renderComparison(data) {
        const fields = ['seo_title', 'seo_description', 'focus_keyword', 'schema_json'];
        let html = '';
        
        fields.forEach(function(field) {
            const fromValue = data.from.fields[field] || '';
            const toValue = data.to.fields[field] || '';
            const changed = fromValue !== toValue;
            
            html += '<div class="compare-field ' + (changed ? 'field-changed' : 'field-unchanged') + '">';
            html += '<h4>' + formatFieldName(field) + '</h4>';
            
            html += '<div class="compare-columns">';
            
            // From column
            html += '<div class="compare-column from-column">';
            html += '<div class="column-header">From (v' + data.from.version + ')</div>';
            html += '<div class="column-content">';
            
            if (field === 'schema_json') {
                html += '<div class="schema-preview"><pre>' + formatSchema(fromValue) + '</pre></div>';
            } else {
                html += '<div class="field-value">' + (fromValue || '<em>(empty)</em>') + '</div>';
            }
            
            html += '</div></div>';
            
            // To column
            html += '<div class="compare-column to-column">';
            html += '<div class="column-header">To (v' + data.to.version + ')</div>';
            html += '<div class="column-content">';
            
            if (field === 'schema_json') {
                html += '<div class="schema-preview"><pre>' + formatSchema(toValue) + '</pre></div>';
            } else {
                html += '<div class="field-value">' + (toValue || '<em>(empty)</em>') + '</div>';
            }
            
            html += '</div></div>';
            
            html += '</div>'; // compare-columns
            
            // Simple diff for non-schema fields
            if (changed && field !== 'schema_json' && fromValue && toValue) {
                html += '<div class="field-diff">';
                html += '<div class="diff-simple">';
                html += '<del>' + escapeHtml(fromValue) + '</del>';
                html += '<ins>' + escapeHtml(toValue) + '</ins>';
                html += '</div></div>';
            }
            
            html += '</div>'; // compare-field
        });
        
        $('#compare-results').html(html);
    }
    
    /**
     * Format field name for display
     */
    function formatFieldName(field) {
        const names = {
            'seo_title': 'SEO Title',
            'seo_description': 'Meta Description',
            'focus_keyword': 'Focus Keyword',
            'schema_json': 'Schema JSON'
        };
        
        return names[field] || field;
    }
    
    /**
     * Format schema JSON
     */
    function formatSchema(json) {
        if (!json) {
            return '';
        }
        
        try {
            const parsed = JSON.parse(json);
            return JSON.stringify(parsed, null, 2);
        } catch (e) {
            return json;
        }
    }
    
    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    /**
     * Copy to clipboard
     */
    function copyToClipboard(text) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }
    
})(jQuery);