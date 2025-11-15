/**
 * AlmaSEO AI Tools Tab Polish JavaScript
 * Version: 1.0.0
 * Description: Enhanced functionality for AI Tools tab with accessibility support
 */

(function($) {
    'use strict';

    // Wait for DOM ready
    $(document).ready(function() {
        // Initialize AI Tools when tab is shown
        initializeAITools();
        
        // Re-initialize when tab is clicked
        $(document).on('click', '.almaseo-tab-btn[data-tab="ai-tools"]', function() {
            setTimeout(initializeAITools, 100);
        });
    });

    /**
     * Initialize all AI Tools functionality
     */
    function initializeAITools() {
        initializeRewriteAssistant();
        initializeTitleGenerator();
        initializeMetaGenerator();
        initializeAISuggestions();
        initializeAccessibility();
        initializeUsageIndicators();
    }

    /**
     * AI Rewrite Assistant
     */
    function initializeRewriteAssistant() {
        const $panel = $('.ai-rewrite-panel');
        if (!$panel.length) return;

        const $input = $('#ai-rewrite-input');
        const $output = $('#ai-rewrite-output');
        const $outputSection = $('.ai-rewrite-output-section');
        const $toneSelect = $('#ai-rewrite-tone');
        const $rewriteBtn = $('#ai-rewrite-submit');
        const $loading = $('.ai-rewrite-loading');
        const $error = $('.ai-rewrite-error');

        // Character counter
        $input.on('input', function() {
            const charCount = $(this).val().length;
            updateCharacterCount('rewrite', charCount);
        });

        // Rewrite button click
        $rewriteBtn.on('click', function() {
            const text = $input.val().trim();
            const tone = $toneSelect.val();

            if (!text) {
                showError($error, 'Please enter some text to rewrite');
                return;
            }

            if (text.length < 10) {
                showError($error, 'Please enter at least 10 characters');
                return;
            }

            // Start rewrite process
            rewriteContent(text, tone);
        });

        // Copy button
        $(document).on('click', '#ai-copy-rewrite', function() {
            const $btn = $(this);
            const text = $('#ai-rewrite-output-text').text();
            
            copyToClipboard(text).then(() => {
                showSuccess($btn, 'Copied!');
            });
        });

        // Apply button
        $(document).on('click', '#ai-apply-rewrite', function() {
            const text = $('#ai-rewrite-output-text').text();
            $input.val(text);
            
            // Animate success
            const $btn = $(this);
            showSuccess($btn, 'Applied!');
            
            // Trigger input event for character count
            $input.trigger('input');
        });

        // Regenerate button
        $(document).on('click', '#ai-regenerate-rewrite', function() {
            const text = $input.val().trim();
            const tone = $toneSelect.val();
            
            if (text) {
                rewriteContent(text, tone);
            }
        });

        // Clear button
        $(document).on('click', '#ai-clear-rewrite', function() {
            $outputSection.fadeOut(300);
            $input.val('').trigger('input');
        });
    }

    /**
     * Rewrite content with AI
     */
    function rewriteContent(text, tone) {
        const $loading = $('.ai-rewrite-loading');
        const $outputSection = $('.ai-rewrite-output-section');
        const $error = $('.ai-rewrite-error');
        const $btn = $('#ai-rewrite-submit');

        // Show loading state
        $loading.fadeIn(300);
        $outputSection.hide();
        $error.hide();
        $btn.prop('disabled', true).addClass('loading');

        // Simulate AI processing (replace with actual API call)
        setTimeout(() => {
            // Hide loading
            $loading.fadeOut(300);
            $btn.prop('disabled', false).removeClass('loading');

            // Simulate success (replace with actual response)
            const rewrittenText = generateRewrittenText(text, tone);
            
            // Show output
            $('#ai-rewrite-output-text').text(rewrittenText);
            $outputSection.addClass('active').fadeIn(300);
            
            // Update character count
            updateCharacterCount('rewrite-output', rewrittenText.length);
            
            // Announce to screen readers
            announceToScreenReader('Content has been rewritten successfully');
        }, 2000);
    }

    /**
     * Title Generator
     */
    function initializeTitleGenerator() {
        const $panel = $('.ai-title-generator-panel');
        if (!$panel.length) return;

        const $input = $('#ai-title-context');
        const $generateBtn = $('#ai-generate-titles');
        const $suggestions = $('.ai-title-suggestions');
        const $loading = $('.ai-title-loading');

        // Generate button click
        $generateBtn.on('click', function() {
            const context = $input.val().trim() || getPostContent();
            
            if (!context || context.length < 20) {
                showError($('.ai-title-error'), 'Please provide more context or content');
                return;
            }

            generateTitles(context);
        });

        // Use title button
        $(document).on('click', '.ai-title-use-btn', function() {
            const title = $(this).closest('.ai-title-suggestion-item').find('.ai-title-suggestion-text').text();
            
            // Apply to SEO title field
            $('#almaseo_seo_title').val(title).trigger('input');
            
            // Show success
            showSuccess($(this), 'Applied!');
            
            // Announce to screen readers
            announceToScreenReader('Title has been applied to SEO title field');
        });

        // Auto-generate from content
        $('#ai-title-auto-generate').on('click', function() {
            const content = getPostContent();
            if (content) {
                generateTitles(content);
            }
        });
    }

    /**
     * Generate title suggestions
     */
    function generateTitles(context) {
        const $loading = $('.ai-title-loading');
        const $suggestions = $('.ai-title-suggestions');
        const $suggestionsContainer = $('.ai-title-suggestions-list');
        const $btn = $('#ai-generate-titles');

        // Show loading
        $loading.fadeIn(300);
        $suggestions.hide();
        $btn.prop('disabled', true);

        // Simulate AI processing
        setTimeout(() => {
            $loading.fadeOut(300);
            $btn.prop('disabled', false);

            // Generate suggestions (replace with actual API)
            const titles = generateTitleSuggestions(context);
            
            // Clear and populate suggestions
            $suggestionsContainer.empty();
            
            titles.forEach((title, index) => {
                const $item = $(`
                    <div class="ai-title-suggestion-item" tabindex="0" role="button" aria-label="Title suggestion: ${title}">
                        <span class="ai-title-suggestion-text">${title}</span>
                        <button class="ai-title-use-btn" aria-label="Use this title">Use This</button>
                    </div>
                `);
                
                $suggestionsContainer.append($item);
            });

            $suggestions.addClass('active').fadeIn(300);
            
            // Announce to screen readers
            announceToScreenReader(`Generated ${titles.length} title suggestions`);
        }, 2000);
    }

    /**
     * Meta Description Generator
     */
    function initializeMetaGenerator() {
        const $panel = $('.ai-meta-generator-panel');
        if (!$panel.length) return;

        const $keywordsInput = $('#ai-meta-keywords');
        const $generateBtn = $('#ai-generate-meta');
        const $output = $('.ai-meta-output');
        const $loading = $('.ai-meta-loading');

        // Generate button click
        $generateBtn.on('click', function() {
            const keywords = $keywordsInput.val().trim();
            const content = getPostContent();
            
            if (!content || content.length < 50) {
                showError($('.ai-meta-error'), 'Please add more content to your post');
                return;
            }

            generateMetaDescription(content, keywords);
        });

        // Apply meta description
        $(document).on('click', '#ai-apply-meta', function() {
            const metaText = $('#ai-meta-output-text').text();
            
            // Apply to meta description field
            $('#almaseo_meta_description').val(metaText).trigger('input');
            
            // Show success
            showSuccess($(this), 'Applied!');
            
            // Announce to screen readers
            announceToScreenReader('Meta description has been applied');
        });

        // Character count monitoring
        $(document).on('DOMSubtreeModified', '#ai-meta-output-text', function() {
            const length = $(this).text().length;
            updateMetaCharCount(length);
        });
    }

    /**
     * Generate meta description
     */
    function generateMetaDescription(content, keywords) {
        const $loading = $('.ai-meta-loading');
        const $output = $('.ai-meta-output');
        const $btn = $('#ai-generate-meta');

        // Show loading
        $loading.fadeIn(300);
        $output.hide();
        $btn.prop('disabled', true);

        // Simulate AI processing
        setTimeout(() => {
            $loading.fadeOut(300);
            $btn.prop('disabled', false);

            // Generate meta description (replace with actual API)
            const metaDescription = generateMetaText(content, keywords);
            
            // Show output
            $('#ai-meta-output-text').text(metaDescription);
            updateMetaCharCount(metaDescription.length);
            $output.addClass('active').fadeIn(300);
            
            // Announce to screen readers
            announceToScreenReader('Meta description has been generated');
        }, 2000);
    }

    /**
     * AI Suggestions Panel
     */
    function initializeAISuggestions() {
        const $panel = $('.ai-suggestions-panel');
        if (!$panel.length) return;

        // Load suggestions on tab activation
        loadAISuggestions();

        // Apply suggestion
        $(document).on('click', '.ai-suggestion-apply-btn', function() {
            const $card = $(this).closest('.ai-suggestion-card');
            const type = $card.data('suggestion-type');
            const value = $card.data('suggestion-value');
            
            applySuggestion(type, value);
            
            // Show success
            showSuccess($(this), 'Applied!');
        });

        // Refresh suggestions
        $('#ai-refresh-suggestions').on('click', function() {
            loadAISuggestions();
        });
    }

    /**
     * Load AI suggestions
     */
    function loadAISuggestions() {
        const $container = $('.ai-suggestions-grid');
        const $loading = $('.ai-suggestions-loading');

        // Show loading
        $loading.fadeIn(300);
        $container.empty();

        // Simulate loading suggestions
        setTimeout(() => {
            $loading.fadeOut(300);

            // Generate suggestions (replace with actual API)
            const suggestions = generateAISuggestions();
            
            suggestions.forEach(suggestion => {
                const $card = createSuggestionCard(suggestion);
                $container.append($card);
            });

            // Announce to screen readers
            announceToScreenReader(`Loaded ${suggestions.length} AI suggestions`);
        }, 1500);
    }

    /**
     * Create suggestion card
     */
    function createSuggestionCard(suggestion) {
        return $(`
            <div class="ai-suggestion-card" data-suggestion-type="${suggestion.type}" data-suggestion-value="${suggestion.value}">
                <div class="ai-suggestion-card-title">
                    <span>${suggestion.icon}</span>
                    <span>${suggestion.title}</span>
                </div>
                <div class="ai-suggestion-card-content">${suggestion.description}</div>
                <div class="ai-suggestion-card-action">
                    <button class="ai-suggestion-apply-btn" aria-label="Apply ${suggestion.title}">
                        Apply
                    </button>
                </div>
            </div>
        `);
    }

    /**
     * Usage Indicators
     */
    function initializeUsageIndicators() {
        // Update word count
        updateWordCount();
        
        // Monitor content changes
        $('#content, #ai-rewrite-input').on('input', updateWordCount);
    }

    /**
     * Update word count
     */
    function updateWordCount() {
        const content = getPostContent();
        const wordCount = content.split(/\s+/).filter(word => word.length > 0).length;
        
        $('.ai-word-count-value').text(wordCount);
        
        // Update token estimate (rough estimate: 1 word ≈ 1.3 tokens)
        const tokenEstimate = Math.ceil(wordCount * 1.3);
        updateTokenUsage(tokenEstimate);
    }

    /**
     * Update token usage
     */
    function updateTokenUsage(tokens) {
        const maxTokens = 4000; // Example limit
        const percentage = Math.min((tokens / maxTokens) * 100, 100);
        
        $('.ai-token-count-value').text(tokens);
        $('.ai-usage-bar-fill').css('width', percentage + '%');
        
        // Change color based on usage
        if (percentage > 90) {
            $('.ai-usage-bar-fill').css('background', 'linear-gradient(90deg, #ef4444, #dc2626)');
        } else if (percentage > 70) {
            $('.ai-usage-bar-fill').css('background', 'linear-gradient(90deg, #f59e0b, #d97706)');
        }
    }

    /**
     * Accessibility Features
     */
    function initializeAccessibility() {
        // Keyboard navigation
        $('.ai-title-suggestion-item, .ai-suggestion-card').on('keypress', function(e) {
            if (e.which === 13 || e.which === 32) { // Enter or Space
                e.preventDefault();
                $(this).find('button').first().click();
            }
        });

        // Focus management
        $('.almaseo-tab-btn[data-tab="ai-tools"]').on('click', function() {
            setTimeout(() => {
                $('#tab-ai-tools').find('input, textarea, select, button').first().focus();
            }, 300);
        });

        // ARIA live regions
        setupAriaLiveRegions();
    }

    /**
     * Setup ARIA live regions
     */
    function setupAriaLiveRegions() {
        // Create live region for announcements
        if (!$('#ai-tools-announcer').length) {
            $('body').append('<div id="ai-tools-announcer" class="sr-only" aria-live="polite" aria-atomic="true"></div>');
        }
    }

    /**
     * Announce to screen readers
     */
    function announceToScreenReader(message) {
        const $announcer = $('#ai-tools-announcer');
        $announcer.text(message);
        
        // Clear after announcement
        setTimeout(() => {
            $announcer.text('');
        }, 1000);
    }

    /**
     * Helper Functions
     */
    
    /**
     * Get post content
     */
    function getPostContent() {
        // Try different editors
        if (typeof wp !== 'undefined' && wp.data && wp.data.select('core/editor')) {
            return wp.data.select('core/editor').getEditedPostContent();
        } else if (typeof tinyMCE !== 'undefined' && tinyMCE.activeEditor) {
            return tinyMCE.activeEditor.getContent({ format: 'text' });
        } else if ($('#content').length) {
            return $('#content').val();
        }
        return '';
    }

    /**
     * Copy to clipboard
     */
    function copyToClipboard(text) {
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
    }

    /**
     * Show success message
     */
    function showSuccess($element, message) {
        const originalText = $element.text();
        $element.addClass('success').text(message);
        
        setTimeout(() => {
            $element.removeClass('success').text(originalText);
        }, 2000);
    }

    /**
     * Show error message
     */
    function showError($errorElement, message) {
        $errorElement.find('.ai-rewrite-error-text, .error-text').text(message);
        $errorElement.fadeIn(300);
        
        setTimeout(() => {
            $errorElement.fadeOut(300);
        }, 5000);
    }

    /**
     * Update character count
     */
    function updateCharacterCount(type, count) {
        const $counter = $(`.ai-${type}-char-count`);
        if ($counter.length) {
            $counter.text(count);
        }
    }

    /**
     * Update meta character count with indicator
     */
    function updateMetaCharCount(length) {
        const $count = $('#ai-meta-char-count');
        const $indicator = $('.ai-meta-char-indicator');
        
        $count.text(length);
        
        // Update indicator color
        $indicator.removeClass('warning error');
        if (length > 160) {
            $indicator.addClass('error');
        } else if (length > 155) {
            $indicator.addClass('warning');
        }
    }

    /**
     * Generate rewritten text (simulation)
     */
    function generateRewrittenText(text, tone) {
        // This is a simulation - replace with actual API call
        const prefixes = {
            'professional': 'In a professional context, ',
            'casual': 'Simply put, ',
            'friendly': 'Hey there! ',
            'formal': 'It should be noted that ',
            'creative': 'Imagine this: '
        };
        
        const prefix = prefixes[tone] || '';
        return prefix + text.charAt(0).toUpperCase() + text.slice(1);
    }

    /**
     * Generate title suggestions (simulation)
     */
    function generateTitleSuggestions(context) {
        // This is a simulation - replace with actual API call
        const baseTitle = context.substring(0, 50).replace(/[^\w\s]/gi, '');
        return [
            `Ultimate Guide to ${baseTitle}`,
            `How to Master ${baseTitle} in 2024`,
            `${baseTitle}: Everything You Need to Know`,
            `The Complete ${baseTitle} Handbook`,
            `${baseTitle} Tips and Best Practices`
        ];
    }

    /**
     * Generate meta text (simulation)
     */
    function generateMetaText(content, keywords) {
        // This is a simulation - replace with actual API call
        const excerpt = content.substring(0, 150).replace(/[^\w\s]/gi, '');
        const keywordText = keywords ? ` focusing on ${keywords}` : '';
        return `Discover comprehensive insights about ${excerpt}${keywordText}. Learn more today!`;
    }

    /**
     * Generate AI suggestions (simulation)
     */
    function generateAISuggestions() {
        // This is a simulation - replace with actual API call
        return [
            {
                type: 'heading',
                value: 'Add H2 headings',
                icon: '📝',
                title: 'Improve Structure',
                description: 'Add 2-3 H2 headings to break up your content'
            },
            {
                type: 'keywords',
                value: 'seo, optimization',
                icon: '🎯',
                title: 'Target Keywords',
                description: 'Include "SEO" and "optimization" 2-3 times'
            },
            {
                type: 'links',
                value: '3',
                icon: '🔗',
                title: 'Internal Links',
                description: 'Add 3 internal links to related content'
            },
            {
                type: 'readability',
                value: 'simplify',
                icon: '📖',
                title: 'Simplify Language',
                description: 'Use shorter sentences for better readability'
            }
        ];
    }

    /**
     * Apply suggestion
     */
    function applySuggestion(type, value) {
        // Implementation depends on suggestion type
        console.log('Applying suggestion:', type, value);
        
        // Announce to screen readers
        announceToScreenReader(`Suggestion applied: ${type}`);
    }

})(jQuery);