/**
 * AlmaSEO LLMs.txt Editor JS
 *
 * @since 8.0.0
 */
(function($) {
    'use strict';

    var Editor = {
        init: function() {
            this.bindSave();
            this.bindGenerate();
        },

        showNotice: function(msg, isError) {
            var $n = $('#almaseo-llms-txt-notice');
            $n.text(msg)
              .toggleClass('error', !!isError)
              .show();
            setTimeout(function() { $n.fadeOut(); }, 3000);
        },

        bindSave: function() {
            var self = this;
            $('#almaseo-llms-txt-save').on('click', function() {
                var $btn = $(this);
                var $spinner = $('#almaseo-llms-txt-spinner');

                $btn.prop('disabled', true);
                $spinner.addClass('is-active');

                $.ajax({
                    url: almaseoLlmsTxt.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'almaseo_llms_txt_save',
                        nonce: almaseoLlmsTxt.nonce,
                        content: $('#almaseo-llms-txt-content').val(),
                        mode: $('input[name="llms_txt_mode"]:checked').val()
                    },
                    success: function(response) {
                        $btn.prop('disabled', false);
                        $spinner.removeClass('is-active');
                        self.showNotice(
                            response.success ? almaseoLlmsTxt.strings.saved : (response.data && response.data.message || almaseoLlmsTxt.strings.error),
                            !response.success
                        );
                    },
                    error: function() {
                        $btn.prop('disabled', false);
                        $spinner.removeClass('is-active');
                        self.showNotice(almaseoLlmsTxt.strings.error, true);
                    }
                });
            });
        },

        bindGenerate: function() {
            var self = this;
            $('#almaseo-llms-txt-generate').on('click', function() {
                var $textarea = $('#almaseo-llms-txt-content');
                if ($textarea.val().trim() !== '' && !confirm(almaseoLlmsTxt.strings.confirmReplace)) {
                    return;
                }

                var $btn = $(this);
                var $spinner = $('#almaseo-llms-txt-spinner');

                $btn.prop('disabled', true);
                $spinner.addClass('is-active');

                $.ajax({
                    url: almaseoLlmsTxt.ajaxurl,
                    method: 'POST',
                    data: {
                        action: 'almaseo_llms_txt_generate',
                        nonce: almaseoLlmsTxt.nonce
                    },
                    success: function(response) {
                        $btn.prop('disabled', false);
                        $spinner.removeClass('is-active');

                        if (response.success && response.data.content) {
                            $textarea.val(response.data.content);
                            self.showNotice(almaseoLlmsTxt.strings.generated, false);
                        } else {
                            self.showNotice(almaseoLlmsTxt.strings.error, true);
                        }
                    },
                    error: function() {
                        $btn.prop('disabled', false);
                        $spinner.removeClass('is-active');
                        self.showNotice(almaseoLlmsTxt.strings.error, true);
                    }
                });
            });
        }
    };

    $(document).ready(function() {
        Editor.init();
    });

})(jQuery);
