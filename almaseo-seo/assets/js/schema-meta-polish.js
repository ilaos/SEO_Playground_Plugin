/**
 * AlmaSEO Schema & Meta Tab - Polish JavaScript
 * Enhanced functionality for Schema and Meta panels
 */

(function($) {
    'use strict';

    var SchemaMetaManager = {
        
        currentSchema: null,
        validationStatus: 'unchecked',
        
        init: function() {
            this.bindEvents();
            this.initCharacterCounters();
            this.initSchemaPreview();
            this.initAccessibility();
            this.loadSchemaSuggestion();
        },
        
        bindEvents: function() {
            var self = this;
            
            // Schema type change
            $('#almaseo_schema_type').on('change', function() {
                self.onSchemaTypeChange($(this).val());
            });
            
            // Copy schema to clipboard
            $(document).on('click', '.schema-copy-btn', function() {
                self.copySchemaToClipboard();
            });
            
            // Validate schema
            $(document).on('click', '.schema-validate-btn', function() {
                self.validateSchema();
            });
            
            // Expand/collapse schema view
            $(document).on('click', '.schema-expand-btn', function() {
                self.toggleSchemaExpand();
            });
            
            // Use schema suggestion
            $('#use-schema-suggestion').on('click', function() {
                self.useSchemasuggestion();
            });
            
            // Analyze schema
            $('#refresh-schema-analysis').on('click', function() {
                self.analyzeSchema();
            });
            
            // Meta field changes for live preview
            $('#almaseo_seo_title, #almaseo_seo_description').on('input', function() {
                self.updateSchemaPreview();
            });
        },
        
        initCharacterCounters: function() {
            var self = this;
            
            // Title counter
            $('#almaseo_seo_title').on('input', function() {
                var length = $(this).val().length;
                var $counter = $(this).siblings('.almaseo-char-count');
                
                $counter.find('span').text(length);
                $counter.removeClass('warning danger');
                
                if (length > 50 && length <= 58) {
                    $counter.addClass('warning');
                } else if (length > 58) {
                    $counter.addClass('danger');
                }
                
                // Update ARIA description
                self.updateAriaDescription(this, length, 60);
            });
            
            // Description counter
            $('#almaseo_seo_description').on('input', function() {
                var length = $(this).val().length;
                var $counter = $(this).siblings('.almaseo-char-count');
                
                $counter.find('span').text(length);
                $counter.removeClass('warning danger');
                
                if (length > 140 && length <= 155) {
                    $counter.addClass('warning');
                } else if (length > 155) {
                    $counter.addClass('danger');
                }
                
                // Update ARIA description
                self.updateAriaDescription(this, length, 160);
            });
        },
        
        updateAriaDescription: function(element, current, max) {
            var remaining = max - current;
            var message = current + ' of ' + max + ' characters used. ';
            
            if (remaining < 0) {
                message += Math.abs(remaining) + ' characters over the limit.';
            } else if (remaining < 10) {
                message += remaining + ' characters remaining.';
            }
            
            $(element).attr('aria-describedby', $(element).attr('id') + '-description');
            
            if (!$('#' + $(element).attr('id') + '-description').length) {
                $(element).after('<span id="' + $(element).attr('id') + '-description" class="schema-sr-only"></span>');
            }
            
            $('#' + $(element).attr('id') + '-description').text(message);
        },
        
        onSchemaTypeChange: function(schemaType) {
            var self = this;
            
            if (schemaType) {
                // Show preview container
                $('#schema-preview-container').fadeIn();
                
                // Update preview
                self.updateSchemaPreview();
                
                // Show analyzer if connected
                if (window.seoPlaygroundData && window.seoPlaygroundData.almaConnected) {
                    $('#schema-analyzer-empty').hide();
                    $('#schema-analyzer-content').show();
                    self.analyzeSchema();
                }
                
                // Announce to screen readers
                self.announceToScreenReader('Schema type changed to ' + schemaType);
            } else {
                // Hide preview and analyzer
                $('#schema-preview-container').fadeOut();
                $('#schema-analyzer-content').hide();
                $('#schema-analyzer-empty').show();
            }
        },
        
        updateSchemaPreview: function() {
            var self = this;
            var schemaType = $('#almaseo_schema_type').val();
            
            if (!schemaType) return;
            
            // Generate schema JSON-LD
            var schema = self.generateSchemaJSON(schemaType);
            self.currentSchema = schema;
            
            // Display with syntax highlighting
            var formattedJSON = self.formatJSONWithHighlight(schema);
            $('#schema-preview-content').html(
                '<pre class="schema-json-display">' + formattedJSON + '</pre>'
            );
            
            // Add action buttons if not present
            if (!$('.schema-actions').length) {
                var actions = $('<div class="schema-actions">' +
                    '<button type="button" class="schema-copy-btn" aria-label="Copy schema to clipboard">Copy</button>' +
                    '<button type="button" class="schema-validate-btn" aria-label="Validate schema">Validate</button>' +
                    '<button type="button" class="schema-expand-btn" aria-label="Expand view">Expand</button>' +
                    '</div>');
                $('.schema-preview-content').append(actions);
            }
        },
        
        generateSchemaJSON: function(schemaType) {
            var title = $('#almaseo_seo_title').val() || $('input[name="post_title"]').val() || 'Untitled';
            var description = $('#almaseo_seo_description').val() || '';
            var url = window.location.href;
            var siteName = window.seoPlaygroundData ? window.seoPlaygroundData.siteUrl : '';
            
            var schema = {
                "@context": "https://schema.org",
                "@type": schemaType
            };
            
            // Common properties
            switch(schemaType) {
                case 'Article':
                case 'BlogPosting':
                case 'NewsArticle':
                    schema.headline = title;
                    schema.description = description;
                    schema.url = url;
                    schema.datePublished = new Date().toISOString();
                    schema.dateModified = new Date().toISOString();
                    schema.author = {
                        "@type": "Person",
                        "name": "Author Name"
                    };
                    schema.publisher = {
                        "@type": "Organization",
                        "name": siteName,
                        "logo": {
                            "@type": "ImageObject",
                            "url": siteName + "/logo.png"
                        }
                    };
                    break;
                    
                case 'Product':
                    schema.name = title;
                    schema.description = description;
                    schema.offers = {
                        "@type": "Offer",
                        "price": "0.00",
                        "priceCurrency": "USD"
                    };
                    break;
                    
                case 'FAQPage':
                    schema.mainEntity = [{
                        "@type": "Question",
                        "name": "Sample Question?",
                        "acceptedAnswer": {
                            "@type": "Answer",
                            "text": description
                        }
                    }];
                    break;
                    
                default:
                    schema.name = title;
                    schema.description = description;
            }
            
            return schema;
        },
        
        formatJSONWithHighlight: function(obj) {
            var json = JSON.stringify(obj, null, 2);
            
            // Syntax highlighting
            json = json.replace(/"([^"]+)":/g, '<span class="json-key">"$1":</span>');
            json = json.replace(/: "([^"]*)"/g, ': <span class="json-string">"$1"</span>');
            json = json.replace(/: (\d+)/g, ': <span class="json-number">$1</span>');
            json = json.replace(/: (true|false)/g, ': <span class="json-boolean">$1</span>');
            json = json.replace(/: null/g, ': <span class="json-null">null</span>');
            
            return json;
        },
        
        copySchemaToClipboard: function() {
            var self = this;
            
            if (!self.currentSchema) return;
            
            var json = JSON.stringify(self.currentSchema, null, 2);
            
            // Create temporary textarea
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(json).select();
            
            try {
                document.execCommand('copy');
                
                // Show success feedback
                var $feedback = $('<div class="copy-success">Copied to clipboard!</div>');
                $('.schema-preview-content').append($feedback);
                
                setTimeout(function() {
                    $feedback.remove();
                }, 2000);
                
                // Announce to screen readers
                self.announceToScreenReader('Schema copied to clipboard');
            } catch(err) {
                alert('Failed to copy schema. Please select and copy manually.');
            }
            
            $temp.remove();
        },
        
        validateSchema: function() {
            var self = this;
            
            if (!self.currentSchema) return;
            
            var $btn = $('.schema-validate-btn');
            $btn.text('Validating...').prop('disabled', true);
            
            // Show validation badge
            self.showValidationBadge('checking');
            
            // Simulate validation (replace with actual validation API)
            setTimeout(function() {
                var isValid = self.performValidation(self.currentSchema);
                
                if (isValid) {
                    self.showValidationBadge('valid');
                    self.announceToScreenReader('Schema is valid');
                } else {
                    self.showValidationBadge('invalid');
                    self.showSchemaErrors(['Missing required field: image']);
                    self.announceToScreenReader('Schema validation failed. Check errors.');
                }
                
                $btn.text('Validate').prop('disabled', false);
            }, 1000);
        },
        
        performValidation: function(schema) {
            // Basic validation rules
            if (!schema['@context'] || !schema['@type']) {
                return false;
            }
            
            // Type-specific validation
            switch(schema['@type']) {
                case 'Article':
                case 'BlogPosting':
                    return schema.headline && schema.author;
                case 'Product':
                    return schema.name && schema.offers;
                default:
                    return true;
            }
        },
        
        showValidationBadge: function(status) {
            $('.schema-validation-badge').remove();
            
            var badge = '';
            switch(status) {
                case 'valid':
                    badge = '<span class="schema-validation-badge valid">✅ Valid</span>';
                    break;
                case 'invalid':
                    badge = '<span class="schema-validation-badge invalid">❌ Invalid</span>';
                    break;
                case 'checking':
                    badge = '<span class="schema-validation-badge checking">⏳ Checking...</span>';
                    break;
            }
            
            $('.schema-preview-header strong').append(badge);
        },
        
        showSchemaErrors: function(errors) {
            var self = this;
            
            // Create error jump link
            var $jump = $('<div class="schema-error-jump">' +
                '<a href="#schema-errors">Jump to first error</a>' +
                '</div>');
            
            $jump.addClass('visible');
            $('body').append($jump);
            
            setTimeout(function() {
                $jump.removeClass('visible');
                setTimeout(function() {
                    $jump.remove();
                }, 300);
            }, 5000);
            
            // Scroll to first error on click
            $jump.find('a').on('click', function(e) {
                e.preventDefault();
                self.scrollToElement('.schema-preview-container');
                $jump.remove();
            });
        },
        
        toggleSchemaExpand: function() {
            var $content = $('.schema-preview-content');
            var $btn = $('.schema-expand-btn');
            
            if ($content.hasClass('expanded')) {
                $content.removeClass('expanded').css('max-height', '400px');
                $btn.text('Expand');
            } else {
                $content.addClass('expanded').css('max-height', 'none');
                $btn.text('Collapse');
            }
        },
        
        loadSchemaSuggestion: function() {
            var self = this;
            
            if (!window.seoPlaygroundData || !window.seoPlaygroundData.almaConnected) {
                return;
            }
            
            // Show loading
            $('#schema-suggestion-loading').show();
            $('#schema-suggestion-content').hide();
            
            // Simulate API call
            setTimeout(function() {
                var suggestedType = self.detectSchemaType();
                
                $('#suggested-schema-type').text(suggestedType);
                $('#schema-suggestion-loading').hide();
                $('#schema-suggestion-content').fadeIn();
            }, 1000);
        },
        
        detectSchemaType: function() {
            // Simple detection based on content (replace with AI detection)
            var content = $('#content').val() || '';
            var title = $('#title').val() || '';
            
            if (content.match(/\?.*\n.*answer/i) || title.match(/faq|questions/i)) {
                return 'FAQPage';
            } else if (content.match(/step \d|instructions|how to/i)) {
                return 'HowTo';
            } else if (content.match(/price|buy|purchase|\$/)) {
                return 'Product';
            } else if (title.match(/news|breaking|update/i)) {
                return 'NewsArticle';
            } else {
                return 'Article';
            }
        },
        
        useSchemasuggestion: function() {
            var suggestedType = $('#suggested-schema-type').text();
            
            if (suggestedType) {
                $('#almaseo_schema_type').val(suggestedType).trigger('change');
                $('#schema-suggestion-container').fadeOut();
                
                this.announceToScreenReader('Schema type set to ' + suggestedType);
            }
        },
        
        analyzeSchema: function() {
            var self = this;
            var schemaType = $('#almaseo_schema_type').val();
            
            if (!schemaType) return;
            
            var $btn = $('#refresh-schema-analysis');
            $btn.prop('disabled', true);
            
            // Show loading
            $('#schema-analyzer-content').hide();
            $('#schema-analyzer-loading').show();
            
            // Simulate analysis
            setTimeout(function() {
                var analysis = self.getSchemaAnalysis(schemaType);
                
                $('#schema-analysis-text').html(analysis);
                $('#schema-analyzer-timestamp').html(
                    '<small>Analyzed: <span>' + new Date().toLocaleTimeString() + '</span></small>'
                );
                
                $('#schema-analyzer-loading').hide();
                $('#schema-analyzer-content').fadeIn();
                
                $btn.prop('disabled', false);
                
                self.announceToScreenReader('Schema analysis complete');
            }, 1500);
        },
        
        getSchemaAnalysis: function(schemaType) {
            var analyses = {
                'Article': 'Article schema is perfect for blog posts and news articles. Make sure to include author information and publication dates for better search visibility.',
                'BlogPosting': 'BlogPosting schema helps search engines understand your blog content. Consider adding categories and tags for enhanced context.',
                'Product': 'Product schema enables rich snippets with pricing and availability. Add reviews and ratings for better CTR.',
                'FAQPage': 'FAQ schema can trigger FAQ rich results in search. Structure your Q&A pairs clearly for best results.',
                'HowTo': 'HowTo schema enables step-by-step rich results. Break down your instructions into clear, numbered steps.'
            };
            
            return analyses[schemaType] || 'This schema type helps search engines better understand your content structure.';
        },
        
        initAccessibility: function() {
            var self = this;
            
            // Add ARIA labels to form fields
            $('#almaseo_seo_title').attr({
                'aria-label': 'SEO Title',
                'aria-required': 'false',
                'aria-describedby': 'title-help'
            });
            
            $('#almaseo_seo_description').attr({
                'aria-label': 'Meta Description',
                'aria-required': 'false',
                'aria-describedby': 'description-help'
            });
            
            $('#almaseo_schema_type').attr({
                'aria-label': 'Schema Markup Type',
                'aria-describedby': 'schema-help'
            });
            
            // Add help text for screen readers
            if (!$('#title-help').length) {
                $('#almaseo_seo_title').after(
                    '<span id="title-help" class="schema-sr-only">Recommended 50-60 characters for optimal display in search results</span>'
                );
            }
            
            if (!$('#description-help').length) {
                $('#almaseo_seo_description').after(
                    '<span id="description-help" class="schema-sr-only">Recommended 150-160 characters for optimal display in search results</span>'
                );
            }
            
            if (!$('#schema-help').length) {
                $('#almaseo_schema_type').after(
                    '<span id="schema-help" class="schema-sr-only">Select the type of structured data that best describes your content</span>'
                );
            }
        },
        
        scrollToElement: function(selector) {
            var $element = $(selector);
            if ($element.length) {
                $('html, body').animate({
                    scrollTop: $element.offset().top - 100
                }, 500);
            }
        },
        
        announceToScreenReader: function(message) {
            if (!$('#almaseo-aria-live').length) {
                $('body').append('<div id="almaseo-aria-live" class="sr-only" aria-live="polite" aria-atomic="true"></div>');
            }
            $('#almaseo-aria-live').text(message);
            setTimeout(function() {
                $('#almaseo-aria-live').text('');
            }, 1000);
        }
    };
    
    // Initialize when DOM is ready
    $(document).ready(function() {
        // Only initialize if Schema & Meta tab exists
        if ($('#tab-schema-meta').length) {
            // Initialize when tab is activated
            $(document).on('almaseo:tab:switched', function(e, tabId) {
                if (tabId === 'schema-meta') {
                    SchemaMetaManager.init();
                }
            });
            
            // Initialize immediately if tab is already active
            if ($('#tab-schema-meta').hasClass('active')) {
                SchemaMetaManager.init();
            }
        }
    });

})(jQuery);