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
            this.initAccordion();
            this.initUnsavedWarning();
        },

        /**
         * Two-layer unsaved-changes safeguard:
         *   1. beforeunload — native browser dialog when navigating away with
         *      a dirty form. Suppressed on intentional submit.
         *   2. Visual dirty markers — an "Unsaved changes" pill next to the
         *      Save button, plus a red dot on each section header whose
         *      child fields were touched (so collapsed sections still
         *      surface the fact that something is pending).
         *
         * Why per-section dots: users with everything collapsed can expand a
         * section, edit, then collapse — the section's content vanishes from
         * view and they lose track of which section was edited. The dot
         * survives the collapse.
         */
        initUnsavedWarning: function() {
            var $form = $('form[action="options.php"]');
            if (!$form.length) return;

            var isSubmitting = false;

            // Find or create the indicator inside the sticky footer.
            var $footer = $('.almaseo-settings-form-footer');
            var indicatorText = (window.almaseoSettings && almaseoSettings.i18n && almaseoSettings.i18n.unsaved_changes)
                ? almaseoSettings.i18n.unsaved_changes
                : 'Unsaved changes';
            var $indicator = $('<span class="almaseo-unsaved-indicator" role="status" aria-live="polite"></span>')
                .text(indicatorText);
            $footer.prepend($indicator);

            function markSectionDirty($field) {
                var $sec = $field.closest('.almaseo-settings-section');
                if (!$sec.length) return;
                if ($sec.hasClass('is-dirty')) return;
                $sec.addClass('is-dirty');
                var $h2 = $sec.children('h2').first();
                if ($h2.length && !$h2.children('.almaseo-section-dirty-dot').length) {
                    $h2.append('<span class="almaseo-section-dirty-dot" aria-hidden="true"></span>');
                }
            }

            function refreshGlobalIndicator() {
                var anyDirty = $form.find('.almaseo-settings-section.is-dirty').length > 0;
                $indicator.toggleClass('is-visible', anyDirty);
            }

            // Any user interaction with a form control marks its section.
            $form.on('input change', ':input', function() {
                markSectionDirty($(this));
                refreshGlobalIndicator();
            });

            // Submitting is intentional — clear the trip-wire for unload.
            $form.on('submit', function() {
                isSubmitting = true;
            });

            // Modern browsers ignore the message string and show a generic
            // localized dialog; returning a non-undefined value is what
            // actually triggers it.
            $(window).on('beforeunload', function(e) {
                if (isSubmitting) return;
                if (!$form.find('.almaseo-settings-section.is-dirty').length) return;
                var msg = (window.almaseoSettings && almaseoSettings.i18n && almaseoSettings.i18n.leave_confirm)
                    ? almaseoSettings.i18n.leave_confirm
                    : 'You have unsaved changes. Are you sure you want to leave?';
                e.returnValue = msg;
                return msg;
            });
        },

        /**
         * Convert every settings/preview/log section into a collapsible
         * accordion. Sections start closed. CSS handles the actual hiding
         * via .is-collapsible (mode) + .is-open (state).
         *
         * Why we attach .is-collapsible from JS instead of in PHP: if this
         * script ever fails to load, the page falls back to the old
         * always-open layout instead of showing locked-closed sections.
         */
        initAccordion: function() {
            var $sections = $('.almaseo-settings-section, .almaseo-preview-section, .almaseo-log-section');
            if (!$sections.length) return;

            $sections.each(function(i) {
                var $sec = $(this);
                var $h2  = $sec.children('h2').first();
                if (!$h2.length) return;

                $sec.addClass('is-collapsible');

                // ARIA — make the h2 announce as a button that toggles a body.
                // Body id is synthesized so screen readers can resolve the link.
                var bodyId = ($sec.attr('id') || 'almaseo-acc-' + i) + '-body';
                $h2.attr({
                    'role': 'button',
                    'tabindex': '0',
                    'aria-expanded': 'false',
                    'aria-controls': bodyId
                });
            });

            // Click on h2 toggles, except when click originated on an
            // interactive control inside the h2 (e.g. the "Clear Log"
            // button nested in the Schema Action Log header).
            $sections.on('click', '> h2', function(e) {
                if ($(e.target).closest('button, a, input, select, textarea').length) return;
                var $sec = $(this).parent();
                var open = !$sec.hasClass('is-open');
                $sec.toggleClass('is-open', open);
                $(this).attr('aria-expanded', open ? 'true' : 'false');
            });

            // Keyboard — Enter / Space on focused h2 toggles.
            $sections.on('keydown', '> h2', function(e) {
                if (e.which !== 13 && e.which !== 32) return; // Enter or Space
                e.preventDefault();
                $(this).trigger('click');
            });

            // Expand all / Collapse all toolbar.
            $(document).on('click', '.almaseo-settings-accordion-toolbar [data-acc-action]', function(e) {
                e.preventDefault();
                var action = $(this).attr('data-acc-action');
                if (action === 'expand') {
                    $sections.addClass('is-open');
                    $sections.children('h2').attr('aria-expanded', 'true');
                } else if (action === 'collapse') {
                    $sections.removeClass('is-open');
                    $sections.children('h2').attr('aria-expanded', 'false');
                }
            });
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