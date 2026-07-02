/**
 * AlmaSEO Connection Interface JavaScript
 * Handles connection testing and UI feedback
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        
        // Test Connection Button Handler
        $('#almaseo-test-connection').on('click', function() {
            var button = $(this);
            var resultSpan = $('#test-result');
            
            // Disable button and show loading state
            button.prop('disabled', true);
            button.addClass('updating-message');
            button.text('Testing...');
            resultSpan.html('<span class="spinner is-active" style="float:none;margin:0 5px;"></span>');
            
            // Send AJAX request to test connection
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'almaseo_test_connection',
                    nonce: almaseoAdmin.nonce
                },
                success: function(response) {
                    button.prop('disabled', false);
                    button.removeClass('updating-message');
                    button.text('Test Connection');
                    
                    if (response.success) {
                        resultSpan.html('<span style="color:#46b450;margin-left:10px;">✓ ' + response.data.message + '</span>');
                        
                        // Auto-hide success message after 5 seconds
                        setTimeout(function() {
                            resultSpan.fadeOut(function() {
                                resultSpan.html('').show();
                            });
                        }, 5000);
                    } else {
                        resultSpan.html('<span style="color:#dc3232;margin-left:10px;">✗ ' + response.data.message + '</span>');
                    }
                },
                error: function() {
                    button.prop('disabled', false);
                    button.removeClass('updating-message');
                    button.text('Test Connection');
                    resultSpan.html('<span style="color:#dc3232;margin-left:10px;">✗ Network error. Please try again.</span>');
                }
            });
        });
        
        // Auto-generate password button enhancement
        $('.generate-password-btn').on('click', function() {
            var button = $(this);
            button.addClass('updating-message');
            button.prop('disabled', true);
        });
        
        // Manual password field validation
        $('#manual_app_password').on('input', function() {
            var value = $(this).val();
            var feedback = $('#password-feedback');
            
            if (!feedback.length) {
                $(this).after('<div id="password-feedback" style="margin-top:5px;"></div>');
                feedback = $('#password-feedback');
            }
            
            if (value.length < 24) {
                feedback.html('<span style="color:#dc3232;">Password must be at least 24 characters</span>');
            } else if (!/^[A-Za-z0-9 ]+$/.test(value)) {
                feedback.html('<span style="color:#dc3232;">Password should only contain letters, numbers, and spaces</span>');
            } else {
                feedback.html('<span style="color:#46b450;">✓ Valid password format</span>');
            }
        });
        
        // Copy password functionality
        $(document).on('click', '.copy-password-btn', function() {
            var passwordField = $(this).siblings('input[type="text"]');
            if (passwordField.length) {
                passwordField.select();
                document.execCommand('copy');
                
                var originalText = $(this).text();
                $(this).text('Copied!');
                
                setTimeout(function() {
                    $(this).text(originalText);
                }.bind(this), 2000);
            }
        });
        
        // Connection status indicator
        function updateConnectionStatus() {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'almaseo_get_status',
                    nonce: almaseoAdmin.nonce
                },
                success: function(response) {
                    if (response.success && response.data.connected) {
                        $('.connection-status-indicator').html('<span class="dashicons dashicons-yes-alt" style="color:#46b450;"></span> Connected');
                    } else {
                        $('.connection-status-indicator').html('<span class="dashicons dashicons-warning" style="color:#ffb900;"></span> Not Connected');
                    }
                }
            });
        }
        
        // Update status every 30 seconds if on settings page
        if ($('.almaseo-container').length) {
            updateConnectionStatus();
            setInterval(updateConnectionStatus, 30000);
        }
        
        // Smooth scroll to connection setup
        $('.setup-connection-btn').on('click', function(e) {
            e.preventDefault();
            $('html, body').animate({
                scrollTop: $('#connection-setup').offset().top - 50
            }, 500);
        });
        
        // Show/hide advanced options
        $('.toggle-advanced').on('click', function(e) {
            e.preventDefault();
            $('.advanced-options').slideToggle();
            $(this).find('.dashicons').toggleClass('dashicons-arrow-down dashicons-arrow-up');
        });
        
    });

})(jQuery);