/**
 * WooCommerce SEO Meta Box JavaScript
 *
 * @package AlmaSEO
 * @since 6.0.0
 */

(function($) {
    'use strict';

    var AlmaSEOWooMeta = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.initCharCounters();
            this.initMediaUpload();
            this.initLivePreview();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Update preview on input
            $('#almaseo_woo_title').on('input', this.updateTitlePreview);
            $('#almaseo_woo_description').on('input', this.updateDescriptionPreview);
            
            // Character counters
            $('.almaseo-char-count').each(function() {
                var target = $(this).data('target');
                var max = $(this).data('max');
                AlmaSEOWooMeta.updateCharCount($('#' + target), $(this), max);
            });
            
            // Update counters on input
            $('#almaseo_woo_title, #almaseo_woo_description').on('input', function() {
                var $counter = $('.almaseo-char-count[data-target="' + $(this).attr('id') + '"]');
                var max = $counter.data('max');
                AlmaSEOWooMeta.updateCharCount($(this), $counter, max);
            });
        },
        
        /**
         * Initialize character counters
         */
        initCharCounters: function() {
            $('.almaseo-char-count').each(function() {
                var $counter = $(this);
                var targetId = $counter.data('target');
                var $target = $('#' + targetId);
                var max = $counter.data('max');
                
                if ($target.length) {
                    AlmaSEOWooMeta.updateCharCount($target, $counter, max);
                    
                    $target.on('input', function() {
                        AlmaSEOWooMeta.updateCharCount($(this), $counter, max);
                    });
                }
            });
        },
        
        /**
         * Update character count
         */
        updateCharCount: function($input, $counter, max) {
            var length = $input.val().length;
            var $count = $counter.find('.count');
            
            $count.text(length);
            
            // Update counter styling
            $counter.removeClass('warning good');
            
            if (length > max) {
                $counter.addClass('warning');
            } else if (length > max * 0.8) {
                $counter.addClass('good');
            }
        },
        
        /**
         * Initialize media upload
         */
        initMediaUpload: function() {
            $('.almaseo-media-upload').on('click', function(e) {
                e.preventDefault();
                
                var $button = $(this);
                var $target = $('#' + $button.data('target'));
                
                // Create media frame
                var frame = wp.media({
                    title: almaseoWoo.strings.title_placeholder || 'Select Image',
                    button: {
                        text: 'Use this image'
                    },
                    multiple: false
                });
                
                // When an image is selected
                frame.on('select', function() {
                    var attachment = frame.state().get('selection').first().toJSON();
                    $target.val(attachment.url);
                });
                
                // Open media frame
                frame.open();
            });
        },
        
        /**
         * Initialize live preview
         */
        initLivePreview: function() {
            // Title preview
            $('#almaseo_woo_title').on('input', function() {
                var value = $(this).val();
                var placeholder = $(this).attr('placeholder');
                $('#preview-title').text(value || placeholder);
            });
            
            // Description preview
            $('#almaseo_woo_description').on('input', function() {
                var value = $(this).val();
                var placeholder = almaseoWoo.strings.description_placeholder || 'Product description will appear here...';
                $('#preview-description').text(value || placeholder);
            });
            
            // Trigger initial update
            $('#almaseo_woo_title').trigger('input');
            $('#almaseo_woo_description').trigger('input');
        },
        
        /**
         * Update title preview
         */
        updateTitlePreview: function() {
            var title = $(this).val();
            var $preview = $('#preview-title');
            
            if (title) {
                $preview.text(title);
            } else {
                $preview.text($(this).attr('placeholder'));
            }
            
            // Highlight if too long
            if (title.length > 60) {
                $preview.addClass('too-long');
            } else {
                $preview.removeClass('too-long');
            }
        },
        
        /**
         * Update description preview
         */
        updateDescriptionPreview: function() {
            var description = $(this).val();
            var $preview = $('#preview-description');
            
            if (description) {
                $preview.text(description);
            } else {
                $preview.text(almaseoWoo.strings.description_placeholder);
            }
            
            // Highlight if too long
            if (description.length > 160) {
                $preview.addClass('too-long');
            } else {
                $preview.removeClass('too-long');
            }
        },
        
        /**
         * Generate meta from product data
         */
        generateMeta: function() {
            // Get product title
            var productTitle = $('#title').val();
            
            // Get product excerpt
            var productExcerpt = $('#excerpt').val();
            
            // Auto-fill if empty
            if (!$('#almaseo_woo_title').val() && productTitle) {
                $('#almaseo_woo_title').val(productTitle).trigger('input');
            }
            
            if (!$('#almaseo_woo_description').val() && productExcerpt) {
                var cleanExcerpt = productExcerpt.replace(/<[^>]*>/g, '').substring(0, 160);
                $('#almaseo_woo_description').val(cleanExcerpt).trigger('input');
            }
        },
        
        /**
         * Validate form before save
         */
        validateForm: function() {
            var errors = [];
            
            // Check title length
            var titleLength = $('#almaseo_woo_title').val().length;
            if (titleLength > 60) {
                errors.push('SEO Title is too long (max 60 characters)');
            }
            
            // Check description length
            var descLength = $('#almaseo_woo_description').val().length;
            if (descLength > 160) {
                errors.push('Meta Description is too long (max 160 characters)');
            }
            
            // Check URLs
            $('.almaseo-field input[type="url"]').each(function() {
                var url = $(this).val();
                if (url && !AlmaSEOWooMeta.isValidUrl(url)) {
                    errors.push('Invalid URL in ' + $(this).closest('.almaseo-field').find('label').first().text());
                }
            });
            
            if (errors.length > 0) {
                alert('Please fix the following issues:\n\n' + errors.join('\n'));
                return false;
            }
            
            return true;
        },
        
        /**
         * Check if URL is valid
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
         * Toggle advanced settings
         */
        toggleAdvanced: function() {
            $('.almaseo-woo-section:last-child').slideToggle();
        },
        
        /**
         * Auto-generate slug from title
         */
        generateSlug: function(title) {
            return title
                .toLowerCase()
                .replace(/[^a-z0-9\s-]/g, '')
                .replace(/\s+/g, '-')
                .replace(/-+/g, '-')
                .trim();
        },
        
        /**
         * Copy to clipboard
         */
        copyToClipboard: function(text) {
            var $temp = $('<input>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
        },
        
        /**
         * Show notification
         */
        showNotification: function(message, type) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.almaseo-woo-meta-wrapper').prepend($notice);
            
            // Auto dismiss after 3 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        AlmaSEOWooMeta.init();
        
        // Auto-generate meta on product publish
        $('#publish').on('click', function(e) {
            if ($('#almaseo_woo_title').length && !$('#almaseo_woo_title').val()) {
                AlmaSEOWooMeta.generateMeta();
            }
        });
        
        // Add tooltips
        $('.almaseo-field label').each(function() {
            var $label = $(this);
            var helpText = $label.next('.description').text();
            
            if (helpText && !$label.find('.almaseo-tooltip').length) {
                $label.append(
                    '<span class="almaseo-tooltip">' +
                    '<span class="dashicons dashicons-editor-help"></span>' +
                    '<span class="almaseo-tooltip-content">' + helpText + '</span>' +
                    '</span>'
                );
            }
        });
        
        // Sync with Yoast/RankMath if present
        if (typeof YoastSEO !== 'undefined') {
            $('#almaseo_woo_title').on('change', function() {
                YoastSEO.app.snippetPreview.data.title = $(this).val();
            });
            
            $('#almaseo_woo_description').on('change', function() {
                YoastSEO.app.snippetPreview.data.metaDesc = $(this).val();
            });
        }
    });
    
})(jQuery);