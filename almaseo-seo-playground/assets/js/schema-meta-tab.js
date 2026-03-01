/**
 * Schema & Meta Tab JavaScript
 * AlmaSEO Plugin v2.6.8
 */

jQuery(document).ready(function($) {
    // Implement mutual exclusivity for robots pairs
    function setupRobotsMutualExclusivity() {
        // Index/NoIndex pair
        $('#almaseo_robots_index').on('change', function() {
            if ($(this).is(':checked')) {
                $('#almaseo_robots_noindex').prop('checked', false);
                $(this).attr('aria-pressed', 'true');
                $('#almaseo_robots_noindex').attr('aria-pressed', 'false');
            } else {
                $('#almaseo_robots_noindex').prop('checked', true);
                $(this).attr('aria-pressed', 'false');
                $('#almaseo_robots_noindex').attr('aria-pressed', 'true');
            }
            updateMetaRobotsPreview();
        });
        
        $('#almaseo_robots_noindex').on('change', function() {
            if ($(this).is(':checked')) {
                $('#almaseo_robots_index').prop('checked', false);
                $(this).attr('aria-pressed', 'true');
                $('#almaseo_robots_index').attr('aria-pressed', 'false');
            } else {
                $('#almaseo_robots_index').prop('checked', true);
                $(this).attr('aria-pressed', 'false');
                $('#almaseo_robots_index').attr('aria-pressed', 'true');
            }
            updateMetaRobotsPreview();
        });
        
        // Follow/NoFollow pair
        $('#almaseo_robots_follow').on('change', function() {
            if ($(this).is(':checked')) {
                $('#almaseo_robots_nofollow').prop('checked', false);
                $(this).attr('aria-pressed', 'true');
                $('#almaseo_robots_nofollow').attr('aria-pressed', 'false');
            } else {
                $('#almaseo_robots_nofollow').prop('checked', true);
                $(this).attr('aria-pressed', 'false');
                $('#almaseo_robots_nofollow').attr('aria-pressed', 'true');
            }
            updateMetaRobotsPreview();
        });
        
        $('#almaseo_robots_nofollow').on('change', function() {
            if ($(this).is(':checked')) {
                $('#almaseo_robots_follow').prop('checked', false);
                $(this).attr('aria-pressed', 'true');
                $('#almaseo_robots_follow').attr('aria-pressed', 'false');
            } else {
                $('#almaseo_robots_follow').prop('checked', true);
                $(this).attr('aria-pressed', 'false');
                $('#almaseo_robots_follow').attr('aria-pressed', 'true');
            }
            updateMetaRobotsPreview();
        });
        
        // Initialize aria-pressed states
        $('#almaseo_robots_index').attr('aria-pressed', $('#almaseo_robots_index').is(':checked') ? 'true' : 'false');
        $('#almaseo_robots_noindex').attr('aria-pressed', $('#almaseo_robots_noindex').is(':checked') ? 'true' : 'false');
        $('#almaseo_robots_follow').attr('aria-pressed', $('#almaseo_robots_follow').is(':checked') ? 'true' : 'false');
        $('#almaseo_robots_nofollow').attr('aria-pressed', $('#almaseo_robots_nofollow').is(':checked') ? 'true' : 'false');
    }
    
    // Update Meta Robots preview with proper order
    function updateMetaRobotsPreview() {
        var robotsParts = [];
        
        // Index/NoIndex (exactly one must be active)
        if ($('#almaseo_robots_noindex').is(':checked')) {
            robotsParts.push('noindex');
        } else {
            robotsParts.push('index');
        }
        
        // Follow/NoFollow (exactly one must be active)
        if ($('#almaseo_robots_nofollow').is(':checked')) {
            robotsParts.push('nofollow');
        } else {
            robotsParts.push('follow');
        }
        
        // Independent directives
        var archive = $('#almaseo_robots_archive').is(':checked');
        var snippet = $('#almaseo_robots_snippet').is(':checked');
        var imageindex = $('#almaseo_robots_imageindex').is(':checked');
        var translate = $('#almaseo_robots_translate').is(':checked');
        
        if (!archive) robotsParts.push('noarchive');
        if (!snippet) robotsParts.push('nosnippet');
        if (!imageindex) robotsParts.push('noimageindex');
        if (!translate) robotsParts.push('notranslate');
        
        // Update preview with proper order
        var robotsContent = robotsParts.join(', ');
        $('#meta-robots-preview-code').text('<meta name="robots" content="' + robotsContent + '" />');
    }
    
    // Initialize robots mutual exclusivity
    setupRobotsMutualExclusivity();
    
    // Attach event listeners to independent robots checkboxes
    $('#almaseo_robots_archive, #almaseo_robots_snippet, #almaseo_robots_imageindex, #almaseo_robots_translate').on('change', updateMetaRobotsPreview);
    
    // Initialize preview on page load
    updateMetaRobotsPreview();
    
    // Collapsible toggle functionality - Fixed to work with all toggles
    $(document).on('click', '.almaseo-collapsible-toggle', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        var $button = $(this);
        var target = $button.data('target');
        var $content = $('#' + target);
        var $icon = $button.find('.dashicons');
        
        // Debug logging
        console.log('[AlmaSEO] Toggle clicked:', target, 'Visible:', $content.is(':visible'));
        
        if ($content.length === 0) {
            console.error('[AlmaSEO] Target element not found:', target);
            return;
        }
        
        if ($content.is(':visible')) {
            $content.slideUp(200);
            $icon.removeClass('dashicons-arrow-up-alt2').addClass('dashicons-arrow-down-alt2');
        } else {
            $content.slideDown(200, function() {
                // Update previews after slide completes
                if (target === 'schema-jsonld-preview' || target === 'schema-preview-content') {
                    updateSchemaPreview();
                } else if (target === 'social-preview-content') {
                    updateSocialPreview();
                }
            });
            $icon.removeClass('dashicons-arrow-down-alt2').addClass('dashicons-arrow-up-alt2');
        }
    });
    
    // Update Schema JSON-LD Preview - fetch from server and pretty-print
    function updateSchemaPreview() {
        var postId = $('#post_ID').val();
        
        if (!postId) {
            console.error('[AlmaSEO Preview] No post ID found');
            $('#schema-json-preview').text('Unable to generate preview - no post ID');
            return;
        }
        
        console.log('[AlmaSEO Preview] Loading preview for post ID:', postId);
        $('#schema-json-preview').text('Loading preview...');
        
        // Fetch schema from server (same as frontend)
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'almaseo_get_schema_preview',
                post_id: postId,
                nonce: $('#_wpnonce').val() || ''
            },
            success: function(response) {
                if (response.success && response.data.json) {
                    console.log('[AlmaSEO Preview] Successfully loaded schema from server');
                    // Pretty-print the JSON
                    try {
                        var parsed = JSON.parse(response.data.json);
                        var prettyJson = JSON.stringify(parsed, null, 2);
                        $('#schema-json-preview').text(prettyJson);
                    } catch (e) {
                        $('#schema-json-preview').text(response.data.json);
                    }
                } else {
                    console.warn('[AlmaSEO Preview] Server response failed, using local generation');
                    // Fallback to local generation
                    generateLocalPreview();
                }
            },
            error: function(xhr, status, error) {
                console.error('[AlmaSEO Preview] AJAX error:', error);
                // Fallback to local generation
                generateLocalPreview();
            }
        });
    }
    
    // Local preview generation as fallback
    function generateLocalPreview() {
        var seoTitle = $('#almaseo_title').val() || $('#title').val() || 'Post Title';
        var seoDesc = $('#almaseo_description').val() || 'Post description';
        var ogImage = $('#almaseo_og_image').val() || '';
        var canonical = window.location.origin + '/?p=' + $('#post_ID').val();
        
        var jsonLd = {
            '@context': 'https://schema.org',
            '@type': 'BlogPosting',
            'url': canonical,
            'mainEntityOfPage': {
                '@type': 'WebPage',
                '@id': canonical + '#webpage'
            },
            'headline': seoTitle,
            'description': seoDesc.substring(0, 160) + (seoDesc.length > 160 ? '...' : ''),
            'datePublished': new Date().toISOString(),
            'dateModified': new Date().toISOString(),
            'author': {
                '@type': 'Person',
                'name': 'Author Name',
                'url': window.location.origin + '/author/'
            },
            'image': ogImage || window.location.origin + '/wp-content/plugins/almaseo-seo-playground-v4/assets/img/default-schema.jpg',
            'publisher': {
                '@type': 'Organization',
                'name': 'Site Name',
                'url': window.location.origin + '/',
                'logo': {
                    '@type': 'ImageObject',
                    'url': window.location.origin + '/wp-content/plugins/almaseo-seo-playground-v4/assets/img/default-schema.jpg'
                }
            }
        };
        
        $('#schema-json-preview').text(JSON.stringify(jsonLd, null, 2));
    }
    
    // Update Social Tags Preview
    function updateSocialPreview() {
        var ogTitle = $('#almaseo_og_title').val() || $('#almaseo_og_title').attr('placeholder') || '(auto: SEO title)';
        var ogDesc = $('#almaseo_og_description').val() || $('#almaseo_og_description').attr('placeholder') || '(auto: meta description)';
        var ogImage = $('#almaseo_og_image').val() || '(auto: featured image)';
        var twitterCard = $('#almaseo_twitter_card').val();
        var twitterTitle = $('#almaseo_twitter_title').val() || '(auto: OG title)';
        var twitterDesc = $('#almaseo_twitter_description').val() || '(auto: OG description)';
        
        var preview = '';
        preview += '<div style="margin-bottom: 15px;"><strong>Open Graph:</strong></div>';
        preview += '<div style="margin-left: 20px; color: #2271b1;">';
        preview += 'og:title: <span style="color: #50575e;">' + escapeHtml(ogTitle) + '</span><br>';
        preview += 'og:description: <span style="color: #50575e;">' + escapeHtml(ogDesc.substring(0, 100)) + (ogDesc.length > 100 ? '...' : '') + '</span><br>';
        preview += 'og:image: <span style="color: #50575e;">' + escapeHtml(ogImage) + '</span><br>';
        preview += '</div>';
        
        preview += '<div style="margin-top: 15px; margin-bottom: 15px;"><strong>Twitter:</strong></div>';
        preview += '<div style="margin-left: 20px; color: #2271b1;">';
        preview += 'twitter:card: <span style="color: #50575e;">' + escapeHtml(twitterCard) + '</span><br>';
        preview += 'twitter:title: <span style="color: #50575e;">' + escapeHtml(twitterTitle) + '</span><br>';
        preview += 'twitter:description: <span style="color: #50575e;">' + escapeHtml(twitterDesc.substring(0, 100)) + (twitterDesc.length > 100 ? '...' : '') + '</span><br>';
        preview += 'twitter:image: <span style="color: #50575e;">' + escapeHtml(ogImage) + '</span><br>';
        preview += '</div>';
        
        $('#social-tags-preview').html(preview);
    }
    
    // Helper function to escape HTML
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    // Copy JSON to clipboard with pretty-print
    $(document).on('click', '.copy-json-btn', function() {
        var $preview = $('#schema-json-preview');
        var text = $preview.text();
        
        // Ensure we're copying pretty-printed JSON
        try {
            var parsed = JSON.parse(text);
            text = JSON.stringify(parsed, null, 2);
        } catch (e) {
            // Text is already formatted or invalid
        }
        
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                var $btn = $('.copy-json-btn');
                var originalText = $btn.text();
                
                // Show toast-style confirmation
                $btn.text('✓ Copied');
                $btn.css({
                    'background': '#00a32a',
                    'transform': 'scale(1.05)',
                    'transition': 'all 0.2s'
                });
                
                setTimeout(function() {
                    $btn.text(originalText);
                    $btn.css({
                        'background': '',
                        'transform': ''
                    });
                }, 2000);
            });
        } else {
            // Fallback for older browsers
            var $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            document.execCommand('copy');
            $temp.remove();
            
            var $btn = $('.copy-json-btn');
            var originalText = $btn.text();
            $btn.text('✓ Copied');
            setTimeout(function() {
                $btn.text(originalText);
            }, 2000);
        }
    });
    
    // Twitter card auto-downgrade detection with proper featured image check
    function checkTwitterCardDowngrade() {
        var cardType = $('#almaseo_twitter_card').val();
        
        // Check for images in this order: Twitter image, OG image, featured image
        var hasTwitterImage = $('#almaseo_twitter_image').val() && $('#almaseo_twitter_image').val().trim() !== '';
        var hasOGImage = $('#almaseo_og_image').val() && $('#almaseo_og_image').val().trim() !== '';
        
        // Multiple ways to check for featured image
        var hasFeaturedImage = false;
        
        // Method 1: Check the data attribute we set from PHP
        if ($('#almaseo_twitter_card').data('has-featured-image') === 'true' || $('#almaseo_twitter_card').data('has-featured-image') === true) {
            hasFeaturedImage = true;
        }
        
        // Method 2: Check the hidden input field that WordPress uses
        if (!hasFeaturedImage && $('input[name="_thumbnail_id"]').val() && $('input[name="_thumbnail_id"]').val() !== '-1' && $('input[name="_thumbnail_id"]').val() !== '0') {
            hasFeaturedImage = true;
        }
        
        // Method 3: Check if there's an image in the featured image box
        if (!hasFeaturedImage && $('#set-post-thumbnail img').length > 0) {
            hasFeaturedImage = true;
        }
        
        // Method 4: Check the postimagediv for any image
        if (!hasFeaturedImage && $('#postimagediv .inside img').length > 0) {
            hasFeaturedImage = true;
        }
        
        // Method 5: Check for Gutenberg featured image
        if (!hasFeaturedImage && $('.editor-post-featured-image img').length > 0) {
            hasFeaturedImage = true;
        }
        
        var hasImage = hasTwitterImage || hasOGImage || hasFeaturedImage;
        
        // Debug logging
        console.log('[AlmaSEO] Image check - Twitter:', hasTwitterImage, 'OG:', hasOGImage, 'Featured:', hasFeaturedImage, 'Has any:', hasImage);
        
        // Remove existing hint
        $('#twitter-card-hint').remove();
        
        // Check if Large Image is selected but no image available
        if (cardType === 'summary_large_image' && !hasImage) {
            // Show downgrade hint
            $('#almaseo_twitter_card').after(
                '<p id="twitter-card-hint" style="color: #666; font-size: 12px; margin-top: 5px; font-style: italic;">ℹ️ Downgraded to \'summary\' until an image is available.</p>'
            );
        }
    }
    
    // Update previews when fields change
    $('#almaseo_schema_type, #almaseo_title, #almaseo_description, #almaseo_og_title, #almaseo_og_description, #almaseo_og_image, #almaseo_twitter_card, #almaseo_twitter_title, #almaseo_twitter_description, #almaseo_twitter_image').on('change keyup', function() {
        if ($('#schema-preview-content').is(':visible')) {
            updateSchemaPreview();
        }
        if ($('#social-preview-content').is(':visible')) {
            updateSocialPreview();
        }
        
        // Check Twitter card downgrade
        if ($(this).attr('id') === 'almaseo_twitter_card' || 
            $(this).attr('id') === 'almaseo_og_image' || 
            $(this).attr('id') === 'almaseo_twitter_image') {
            checkTwitterCardDowngrade();
        }
    });
    
    // Check Twitter card on page load and when featured image changes
    checkTwitterCardDowngrade();
    
    // Monitor featured image changes
    $(document).on('DOMNodeInserted', '#postimagediv', function() {
        setTimeout(checkTwitterCardDowngrade, 500);
    });
    
    // Monitor when featured image is removed
    $(document).on('click', '#remove-post-thumbnail', function() {
        setTimeout(checkTwitterCardDowngrade, 500);
    });
    
    // Handle locked schema selection
    $('#almaseo_schema_type').on('change', function(e) {
        var $selected = $(this).find('option:selected');
        var $notice = $('#schema-locked-notice');
        
        if ($selected.attr('data-locked') === '1') {
            e.preventDefault();
            // Revert to previous value or Article
            var previousValue = $(this).data('previous') || 'Article';
            $(this).val(previousValue);
            
            // Show upsell notice with enhanced message
            $notice.html('<small style="color: #2c3338;">' +
                'Advanced schema types require an AlmaSEO connection. ' +
                '<a href="#tab-unlock-features" class="almaseo-tab-link">Connect now →</a>' +
                '</small>');
            $notice.slideDown();
            
            // Auto-hide after 6 seconds
            clearTimeout($(this).data('hideTimeout'));
            var hideTimeout = setTimeout(function() {
                $notice.slideUp();
            }, 6000);
            $(this).data('hideTimeout', hideTimeout);
        } else {
            // Store valid selection
            $(this).data('previous', $(this).val());
            // Hide notice if showing
            $notice.slideUp();
        }
    });
    
    // Store initial value
    $('#almaseo_schema_type').data('previous', $('#almaseo_schema_type').val());
    
    // Handle tab navigation for locked schema notice
    $(document).on('click', '.almaseo-tab-link', function(e) {
        e.preventDefault();
        var targetTab = $(this).attr('href').replace('#', '');
        $('.almaseo-tab-btn[data-tab="' + targetTab + '"]').trigger('click');
    });
    
    // Canonical URL validation with gentle warnings
    $('#almaseo_canonical_url').on('blur', function() {
        var url = $(this).val();
        var $field = $(this);
        
        // Remove existing warnings
        $field.next('.canonical-warning').remove();
        $field.css('border-color', '');
        
        if (!url) return;
        
        // Check if URL is absolute
        var isAbsolute = /^https?:\/\//i.test(url);
        
        // Check if URL is on different domain
        var currentHost = window.location.hostname;
        var isCrossDomain = false;
        
        if (isAbsolute) {
            try {
                var urlObj = new URL(url);
                isCrossDomain = urlObj.hostname !== currentHost;
            } catch (e) {
                // Invalid URL
            }
        }
        
        // Show appropriate warning
        if (!isAbsolute) {
            $field.after('<p class="canonical-warning" style="color: #dba617; font-size: 12px; margin-top: 5px; padding: 5px 8px; background: #fff8e5; border-left: 3px solid #dba617; border-radius: 2px;">ℹ️ Use an absolute URL (e.g., https://...).</p>');
            $field.css('border-color', '#dba617');
            // Add title attribute for screenreader
            $field.attr('title', 'Use an absolute URL (e.g., https://...)');
        } else if (isCrossDomain) {
            $field.after('<p class="canonical-warning" style="color: #dba617; font-size: 12px; margin-top: 5px; padding: 5px 8px; background: #fff8e5; border-left: 3px solid #dba617; border-radius: 2px;">ℹ️ Cross-domain canonical — confirm this is intended.</p>');
            $field.css('border-color', '#dba617');
            // Add title attribute for screenreader
            $field.attr('title', 'Cross-domain canonical — confirm this is intended');
        } else {
            $field.removeAttr('title');
        }
    });
    
    // Also validate on form submit
    $('#post').on('submit', function() {
        $('#almaseo_canonical_url').trigger('blur');
        // Don't block save - always return true
        return true;
    });
    
    // Helper function to validate URL
    function isValidUrl(string) {
        try {
            new URL(string);
            return true;
        } catch (_) {
            return false;
        }
    }
    
    // Schema type change handler
    $('#almaseo_schema_type').on('change', function() {
        var selectedSchema = $(this).val();
        
        // Show/hide article-specific fields
        if (selectedSchema === 'Article') {
            $('.schema-article-fields').slideDown();
        } else {
            $('.schema-article-fields').slideUp();
        }
        
        // Check if premium schema is selected when not connected
        var isPremium = $(this).find('option:selected').prop('disabled');
        if (isPremium) {
            // Reset to Article if premium is selected without access
            $(this).val('Article');
            $('.schema-article-fields').slideDown();
            
            // Show upgrade message
            if (!$('.schema-upgrade-notice').length) {
                $(this).after('<p class="schema-upgrade-notice" style="color: #d63638; margin-top: 10px;">🔒 This schema type requires AlmaSEO Pro or Max. <a href="' + seoPlaygroundData.connectionUrl + '" style="color: #667eea;">Upgrade now</a></p>');
            }
            
            setTimeout(function() {
                $('.schema-upgrade-notice').fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);
        }
    });
    
    // Image URL preview for Open Graph
    $('#almaseo_og_image').on('blur', function() {
        var imageUrl = $(this).val();
        if (imageUrl && isValidUrl(imageUrl)) {
            // Show image preview
            if (!$('#og-image-preview').length) {
                $(this).after('<div id="og-image-preview" style="margin-top: 10px;"></div>');
            }
            
            $('#og-image-preview').html('<img src="' + imageUrl + '" style="max-width: 200px; height: auto; border: 1px solid #dcdcde; border-radius: 4px;" onerror="this.style.display=\'none\'" />');
        } else {
            $('#og-image-preview').remove();
        }
    });
    
    // Character counter for text fields
    function addCharCounter(selector, maxLength, recommended) {
        $(selector).each(function() {
            var $field = $(this);
            var $counter = $('<div class="char-counter" style="text-align: right; font-size: 12px; color: #646970; margin-top: 5px;"></div>');
            $field.after($counter);
            
            function updateCounter() {
                var length = $field.val().length;
                var color = '#646970';
                
                if (recommended) {
                    if (length > recommended) {
                        color = '#d63638';
                    } else if (length > recommended * 0.8) {
                        color = '#dba617';
                    } else {
                        color = '#00a32a';
                    }
                }
                
                var text = length + ' characters';
                if (recommended) {
                    text += ' (recommended: ' + recommended + ')';
                }
                
                $counter.text(text).css('color', color);
            }
            
            $field.on('input', updateCounter);
            updateCounter();
        });
    }
    
    // Add character counters to OG and Twitter fields
    addCharCounter('#almaseo_og_title', 60, 60);
    addCharCounter('#almaseo_og_description', 160, 160);
    addCharCounter('#almaseo_twitter_title', 70, 70);
    addCharCounter('#almaseo_twitter_description', 200, 200);
    
    // Meta History toggle
    $('#toggle-metadata-history').on('click', function() {
        var $content = $('#metadata-history-content');
        var $toggleText = $(this).find('.toggle-text');
        var $toggleIcon = $(this).find('.toggle-icon');
        
        if ($content.is(':visible')) {
            $content.slideUp();
            $toggleText.text('Show History');
            $toggleIcon.text('▼');
        } else {
            $content.slideDown();
            $toggleText.text('Hide History');
            $toggleIcon.text('▲');
        }
    });
    
    // Restore field from history
    $('.restore-btn').on('click', function() {
        var field = $(this).data('field');
        var value = $(this).data('value');
        
        switch(field) {
            case 'title':
                $('#almaseo_title').val(value).trigger('change');
                break;
            case 'description':
                $('#almaseo_description').val(value).trigger('change');
                break;
            case 'keyword':
                $('#almaseo_focus_keyword').val(value).trigger('change');
                break;
        }
        
        // Visual feedback
        $(this).text('✅').addClass('restored');
        setTimeout(() => {
            $(this).text('↩️').removeClass('restored');
        }, 2000);
    });
    
    // Restore all fields from history
    $('.restore-all-btn').on('click', function() {
        var index = $(this).data('index');
        var $entry = $(this).closest('.history-entry');
        
        // Find all restore buttons in this entry and click them
        $entry.find('.restore-btn').each(function() {
            $(this).trigger('click');
        });
        
        // Visual feedback
        $(this).text('✅ Restored').prop('disabled', true);
        setTimeout(() => {
            $(this).text('Restore All').prop('disabled', false);
        }, 3000);
    });
});