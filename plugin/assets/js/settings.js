/**
 * AlmaSEO Settings JavaScript
 * 
 * @package AlmaSEO
 * @since 2.0.0
 */

(function($) {
    'use strict';

    var AlmaSEOSettings = {
        
        init: function() {
            this.bindEvents();
            this.initializeUI();
        },
        
        bindEvents: function() {
            // Toggle sub-options visibility
            $('input[name="almaseo_exclusive_schema_enabled"]').on('change', this.toggleSubOptions);
            
            // Preview button
            $('#run-preview').on('click', this.runPreview.bind(this));
            
            // Clear log button
            $('#clear-schema-log').on('click', this.clearLog.bind(this));
            
            // Enter key on URL field
            $('#preview-url').on('keypress', function(e) {
                if (e.which === 13) {
                    e.preventDefault();
                    $('#run-preview').click();
                }
            });
        },
        
        initializeUI: function() {
            // Initialize tooltips if needed
            if ($.fn.tooltip) {
                $('.help-tip').tooltip();
            }
        },
        
        toggleSubOptions: function() {
            var isEnabled = $(this).is(':checked');
            
            if (isEnabled) {
                $('.schema-sub-options').slideDown();
                $('.almaseo-preview-section').slideDown();
            } else {
                $('.schema-sub-options').slideUp();
                $('.almaseo-preview-section').slideUp();
            }
        },
        
        runPreview: function() {
            var url = $('#preview-url').val();
            
            if (!url) {
                url = almaseoSettings.homeUrl;
                $('#preview-url').val(url);
            }
            
            // Validate URL
            if (!this.isValidUrl(url)) {
                alert('Please enter a valid URL');
                return;
            }
            
            // Show loading state
            $('#preview-results').show();
            $('#preview-results .preview-loading').show();
            $('#preview-results .preview-content').hide();
            $('#run-preview').prop('disabled', true).text(almaseoSettings.i18n.preview_loading);
            
            // Make AJAX request
            $.ajax({
                url: almaseoSettings.ajaxurl,
                type: 'POST',
                data: {
                    action: 'almaseo_schema_preview',
                    nonce: almaseoSettings.nonce,
                    url: url
                },
                success: function(response) {
                    if (response.success) {
                        AlmaSEOSettings.displayPreviewResults(response.data);
                    } else {
                        AlmaSEOSettings.displayPreviewError(response.data.message || almaseoSettings.i18n.preview_error);
                    }
                },
                error: function() {
                    AlmaSEOSettings.displayPreviewError(almaseoSettings.i18n.preview_error);
                },
                complete: function() {
                    $('#preview-results .preview-loading').hide();
                    $('#run-preview').prop('disabled', false).text('Run Preview');
                }
            });
        },
        
        displayPreviewResults: function(data) {
            var html = '<div class="preview-summary">';
            
            // Summary stats
            html += '<div class="preview-stats">';
            html += '<div class="stat-box">';
            html += '<span class="stat-number">' + data.total_found + '</span>';
            html += '<span class="stat-label">Total JSON-LD blocks found</span>';
            html += '</div>';
            html += '<div class="stat-box kept">';
            html += '<span class="stat-number">' + data.kept_count + '</span>';
            html += '<span class="stat-label">Would be kept</span>';
            html += '</div>';
            html += '<div class="stat-box removed">';
            html += '<span class="stat-number">' + data.removed_count + '</span>';
            html += '<span class="stat-label">Would be removed</span>';
            html += '</div>';
            html += '</div>';
            
            // Kept blocks
            if (data.kept_blocks && data.kept_blocks.length > 0) {
                html += '<div class="preview-section kept-section">';
                html += '<h3>✅ Blocks to Keep</h3>';
                html += '<table class="preview-table">';
                html += '<thead><tr><th>Type</th><th>Reason</th><th>Snippet</th></tr></thead>';
                html += '<tbody>';
                
                data.kept_blocks.forEach(function(block) {
                    html += '<tr>';
                    html += '<td><strong>' + AlmaSEOSettings.escapeHtml(block.type) + '</strong></td>';
                    html += '<td>' + AlmaSEOSettings.escapeHtml(block.reason) + '</td>';
                    html += '<td><code>' + AlmaSEOSettings.escapeHtml(block.snippet.substring(0, 100)) + '...</code></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                html += '</div>';
            }
            
            // Removed blocks
            if (data.removed_blocks && data.removed_blocks.length > 0) {
                html += '<div class="preview-section removed-section">';
                html += '<h3>❌ Blocks to Remove</h3>';
                html += '<table class="preview-table">';
                html += '<thead><tr><th>Type</th><th>Source</th><th>Snippet</th></tr></thead>';
                html += '<tbody>';
                
                data.removed_blocks.forEach(function(block) {
                    html += '<tr>';
                    html += '<td><strong>' + AlmaSEOSettings.escapeHtml(block.type) + '</strong></td>';
                    html += '<td>' + AlmaSEOSettings.escapeHtml(block.source) + '</td>';
                    html += '<td><code>' + AlmaSEOSettings.escapeHtml(block.snippet.substring(0, 100)) + '...</code></td>';
                    html += '</tr>';
                });
                
                html += '</tbody></table>';
                html += '</div>';
            }
            
            // No blocks found
            if (data.total_found === 0) {
                html += '<div class="preview-notice">No JSON-LD schema blocks found on this page.</div>';
            }
            
            html += '</div>';
            
            $('#preview-results .preview-content').html(html).show();
        },
        
        displayPreviewError: function(message) {
            var html = '<div class="preview-error">';
            html += '<p>❌ ' + this.escapeHtml(message) + '</p>';
            html += '</div>';
            
            $('#preview-results .preview-content').html(html).show();
        },
        
        clearLog: function() {
            if (!confirm(almaseoSettings.i18n.clear_log_confirm)) {
                return;
            }
            
            var $button = $(this);
            $button.prop('disabled', true);
            
            $.ajax({
                url: almaseoSettings.ajaxurl,
                type: 'POST',
                data: {
                    action: 'almaseo_clear_schema_log',
                    nonce: almaseoSettings.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#schema-log-container').html('<p>No schema actions logged yet.</p>');
                        alert(almaseoSettings.i18n.log_cleared);
                    }
                },
                complete: function() {
                    $button.prop('disabled', false);
                }
            });
        },
        
        isValidUrl: function(string) {
            try {
                new URL(string);
                return true;
            } catch (_) {
                return false;
            }
        },
        
        escapeHtml: function(text) {
            var map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        AlmaSEOSettings.init();
    });
    
})(jQuery);