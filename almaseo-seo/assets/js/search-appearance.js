/**
 * AlmaSEO Search Appearance Admin JS
 *
 * Handles tab switching, settings collection, AJAX save,
 * separator picker, and smart tags reference modal.
 *
 * @since 8.0.0
 */
(function($) {
    'use strict';

    var SA = {
        init: function() {
            this.bindTabs();
            this.bindSave();
            this.bindSeparator();
            this.bindModal();
        },

        /* ── Tab switching ── */
        bindTabs: function() {
            $(document).on('click', '.almaseo-sa-tab', function(e) {
                e.preventDefault();
                var tab = $(this).data('tab');

                $('.almaseo-sa-tab').removeClass('active');
                $(this).addClass('active');

                $('.almaseo-sa-panel').removeClass('active');
                $('#panel-' + tab).addClass('active');
            });
        },

        /* ── Collect all settings from DOM ── */
        collectSettings: function() {
            var settings = {};

            // Collect text/textarea inputs.
            $('.almaseo-sa-input').each(function() {
                var $el   = $(this);
                var path  = $el.data('path');
                var type  = $el.data('type');

                if (!path) return;

                var value;
                if ($el.is(':checkbox')) {
                    if (type === 'noindex') {
                        value = $el.is(':checked');
                    } else {
                        value = $el.is(':checked');
                    }
                } else if ($el.is('textarea')) {
                    value = $el.val();
                } else {
                    value = $el.val();
                }

                SA.setNestedValue(settings, path, value);
            });

            // Collect separator.
            var sep = $('input[name="separator"]:checked').val();
            if (sep) {
                settings.separator = sep;
            }

            return settings;
        },

        /* ── Set a nested value in an object by dot path ── */
        setNestedValue: function(obj, path, value) {
            var keys = path.split('.');
            var current = obj;

            for (var i = 0; i < keys.length - 1; i++) {
                if (!current[keys[i]] || typeof current[keys[i]] !== 'object') {
                    current[keys[i]] = {};
                }
                current = current[keys[i]];
            }

            current[keys[keys.length - 1]] = value;
        },

        /* ── Save via AJAX ── */
        bindSave: function() {
            $(document).on('click', '#almaseo-sa-save', function() {
                var $btn     = $(this);
                var $spinner = $('#almaseo-sa-spinner');
                var $notice  = $('#almaseo-sa-notice');

                $btn.prop('disabled', true);
                $spinner.addClass('is-active');
                $notice.hide();

                var settings = SA.collectSettings();

                $.ajax({
                    url: almaseoSA.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'almaseo_save_search_appearance',
                        nonce: almaseoSA.nonce,
                        settings: JSON.stringify(settings)
                    },
                    success: function(response) {
                        $btn.prop('disabled', false);
                        $spinner.removeClass('is-active');

                        if (response.success) {
                            $notice.removeClass('error')
                                   .text(almaseoSA.strings.saved)
                                   .show();
                        } else {
                            $notice.addClass('error')
                                   .text(response.data && response.data.message ? response.data.message : almaseoSA.strings.error)
                                   .show();
                        }

                        // Auto-hide notice.
                        setTimeout(function() { $notice.fadeOut(); }, 3000);
                    },
                    error: function() {
                        $btn.prop('disabled', false);
                        $spinner.removeClass('is-active');
                        $notice.addClass('error')
                               .text(almaseoSA.strings.error)
                               .show();
                    }
                });
            });
        },

        /* ── Separator picker ── */
        bindSeparator: function() {
            $(document).on('change', '.almaseo-sa-sep-option input[type="radio"]', function() {
                $('.almaseo-sa-sep-option').removeClass('active');
                $(this).closest('.almaseo-sa-sep-option').addClass('active');
            });
        },

        /* ── Smart Tags Reference Modal ── */
        bindModal: function() {
            // Open modal.
            $(document).on('click', '.almaseo-sa-tags-ref', function(e) {
                e.preventDefault();
                $('#almaseo-sa-tags-modal').show();
            });

            // Close modal.
            $(document).on('click', '.almaseo-sa-modal-close', function() {
                $('#almaseo-sa-tags-modal').hide();
            });

            // Close on overlay click.
            $(document).on('click', '.almaseo-sa-modal', function(e) {
                if (e.target === this) {
                    $(this).hide();
                }
            });

            // Close on Escape.
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('#almaseo-sa-tags-modal').hide();
                }
            });
        }
    };

    $(document).ready(function() {
        SA.init();
    });

})(jQuery);
