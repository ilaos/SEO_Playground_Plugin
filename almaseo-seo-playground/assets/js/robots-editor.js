/**
 * AlmaSEO Robots.txt Editor JavaScript
 * 
 * @package AlmaSEO
 * @since 6.0.0
 */

(function($) {
    'use strict';
    
    var AlmaSEORobotsEditor = {
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.checkInitialStatus();
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            // Mode change
            $('input[name="robots_mode"]').on('change', this.handleModeChange.bind(this));
            
            // Save button
            $('#save-robots').on('click', this.saveRobots.bind(this));
            
            // Test output button
            $('#test-output').on('click', this.testOutput.bind(this));
            
            // Insert defaults button
            $('#insert-defaults').on('click', this.insertDefaults.bind(this));
            
            // Reset to WP default button
            $('#reset-wp-default').on('click', this.resetWPDefault.bind(this));
            
            // Auto-save indicator
            var saveTimeout;
            $('#robots-content').on('input', function() {
                clearTimeout(saveTimeout);
                $('.save-message').text('').removeClass('success error');
            });
        },
        
        /**
         * Check initial status
         */
        checkInitialStatus: function() {
            var self = this;
            
            $.post(almaseoRobots.ajaxurl, {
                action: 'almaseo_robots_check_status',
                nonce: almaseoRobots.nonce
            }, function(response) {
                if (response.success && response.data) {
                    self.updateStatus(response.data);
                }
            });
        },
        
        /**
         * Handle mode change
         */
        handleModeChange: function(e) {
            var mode = $(e.target).val();
            var self = this;
            
            // Check status before changing
            $.post(almaseoRobots.ajaxurl, {
                action: 'almaseo_robots_check_status',
                nonce: almaseoRobots.nonce
            }, function(response) {
                if (response.success && response.data) {
                    if (response.data.physical_exists && mode === 'virtual') {
                        if (!confirm(almaseoRobots.strings.fileWarning + '\n\nContinue anyway?')) {
                            // Revert selection
                            $('input[name="robots_mode"][value="' + response.data.mode + '"]').prop('checked', true);
                            return;
                        }
                    }
                    
                    // Update UI based on mode
                    self.updateModeUI(mode, response.data);
                }
            });
        },
        
        /**
         * Update mode UI
         */
        updateModeUI: function(mode, status) {
            if (mode === 'file' && !status.is_writable) {
                alert('Cannot switch to Physical mode: File or directory is not writable.');
                $('input[name="robots_mode"][value="virtual"]').prop('checked', true);
                return;
            }
            
            // Update any mode-specific UI elements
            $('.mode-status strong').first().text(mode === 'virtual' ? 'Virtual' : 'Physical');
        },
        
        /**
         * Save robots.txt
         */
        saveRobots: function(e) {
            e.preventDefault();
            
            var self = this;
            var $button = $('#save-robots');
            var $spinner = $button.siblings('.spinner');
            var $message = $('.save-message');
            
            // Get values
            var content = $('#robots-content').val();
            var mode = $('input[name="robots_mode"]:checked').val();
            
            // Show loading
            $button.prop('disabled', true);
            $spinner.addClass('is-active');
            $message.text(almaseoRobots.strings.saving).removeClass('success error');
            
            // Send AJAX request
            $.post(almaseoRobots.ajaxurl, {
                action: 'almaseo_robots_save',
                nonce: almaseoRobots.nonce,
                content: content,
                mode: mode
            }, function(response) {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                
                if (response.success) {
                    $message.text(response.data.message).addClass('success');
                    
                    if (response.data.warning) {
                        $message.append('<br><span class="warning">' + response.data.warning + '</span>');
                    }
                    
                    // Update status
                    self.checkInitialStatus();
                    
                    // Auto-hide success message after 5 seconds
                    setTimeout(function() {
                        $message.fadeOut(function() {
                            $(this).text('').removeClass('success').show();
                        });
                    }, 5000);
                    
                } else {
                    $message.text(response.data.message || almaseoRobots.strings.error).addClass('error');
                    
                    // Handle fallback to virtual mode
                    if (response.data && response.data.fallback) {
                        $('input[name="robots_mode"][value="virtual"]').prop('checked', true);
                        self.updateModeUI('virtual', {is_writable: false});
                    }
                }
            }).fail(function() {
                $button.prop('disabled', false);
                $spinner.removeClass('is-active');
                $message.text(almaseoRobots.strings.error).addClass('error');
            });
        },
        
        /**
         * Test output
         */
        testOutput: function(e) {
            e.preventDefault();
            
            var $button = $('#test-output');
            var $preview = $('#preview-card');
            var $output = $('#preview-output');
            var $status = $('.preview-status');
            
            // Show loading
            $button.prop('disabled', true).text(almaseoRobots.strings.testing);
            
            // Send AJAX request
            $.post(almaseoRobots.ajaxurl, {
                action: 'almaseo_robots_test',
                nonce: almaseoRobots.nonce
            }, function(response) {
                $button.prop('disabled', false).text('Test Output');
                
                if (response.success && response.data) {
                    // Show preview card
                    $preview.slideDown();
                    
                    // Display output
                    $output.text(response.data.output);
                    
                    // Display status
                    if (response.data.status && response.data.status.length) {
                        var statusHtml = '<ul>';
                        response.data.status.forEach(function(item) {
                            statusHtml += '<li>' + item + '</li>';
                        });
                        statusHtml += '</ul>';
                        $status.html(statusHtml);
                    }
                    
                    // Scroll to preview
                    $('html, body').animate({
                        scrollTop: $preview.offset().top - 50
                    }, 500);
                }
            }).fail(function() {
                $button.prop('disabled', false).text('Test Output');
                alert(almaseoRobots.strings.error);
            });
        },
        
        /**
         * Insert defaults
         */
        insertDefaults: function(e) {
            e.preventDefault();
            
            $.post(almaseoRobots.ajaxurl, {
                action: 'almaseo_robots_get_default',
                nonce: almaseoRobots.nonce,
                type: 'almaseo'
            }, function(response) {
                if (response.success && response.data) {
                    $('#robots-content').val(response.data.content);
                    $('.save-message').text('Default content inserted. Remember to save!').addClass('info');
                }
            });
        },
        
        /**
         * Reset to WordPress default
         */
        resetWPDefault: function(e) {
            e.preventDefault();
            
            if (!confirm(almaseoRobots.strings.confirmReset)) {
                return;
            }
            
            $.post(almaseoRobots.ajaxurl, {
                action: 'almaseo_robots_get_default',
                nonce: almaseoRobots.nonce,
                type: 'wordpress'
            }, function(response) {
                if (response.success && response.data) {
                    $('#robots-content').val(response.data.content);
                    $('.save-message').text('Reset to WordPress default. Remember to save!').addClass('info');
                }
            });
        },
        
        /**
         * Update status display
         */
        updateStatus: function(status) {
            // Update status list
            var $statusList = $('.mode-status ul');
            
            $statusList.find('li').eq(0).find('strong').text(
                status.mode === 'virtual' ? 'Virtual' : 'Physical'
            );
            
            $statusList.find('li').eq(1).find('strong').text(
                status.physical_exists ? 'Yes' : 'No'
            );
            
            $statusList.find('li').eq(2).find('strong').text(
                status.is_writable ? 'Yes' : 'No'
            );
            
            // Show/hide warning cards
            if (status.physical_exists && status.mode === 'virtual') {
                $('.warning-card').show();
                if (status.physical_content) {
                    $('.physical-content').text(status.physical_content);
                }
            } else {
                $('.warning-card').hide();
            }
            
            // Update mode selector if needed
            if (!status.is_writable) {
                $('input[name="robots_mode"][value="file"]').prop('disabled', true);
                $('.mode-option').last().addClass('disabled');
            }
        }
    };
    
    // Initialize when document is ready
    $(document).ready(function() {
        AlmaSEORobotsEditor.init();
    });
    
})(jQuery);