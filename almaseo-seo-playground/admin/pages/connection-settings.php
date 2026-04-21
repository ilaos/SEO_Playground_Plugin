<?php
/**
 * AlmaSEO Connection Settings Page
 *
 * Renders the connection/settings page for linking the site to AlmaSEO Dashboard.
 * Handles password generation, manual import, connection testing, and disconnection.
 *
 * @package AlmaSEO
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Settings page HTML - Completely redesigned for smooth UX
if (!function_exists('almaseo_connector_settings_page')) {
function almaseo_connector_settings_page() {
    if (!current_user_can('manage_options')) return;

    $current_user = wp_get_current_user();
    $username = $current_user->user_login;
    $connection_status = almaseo_get_connection_status();
    $comprehensive_status = almaseo_get_comprehensive_connection_status();
    $app_password = get_option('almaseo_app_password', '');

    // Check for dashboard sync success
    if ($sync_success = get_transient('almaseo_dashboard_sync_success')) {
        echo '<div class="notice notice-success is-dismissible">';
        echo '<p><strong>✅ Site Automatically Connected!</strong></p>';
        echo '<p>Your site was found in the AlmaSEO Dashboard (Site ID: ' . esc_html($sync_success['site_id']) . ').</p>';
        echo '<p>Using existing Application Password: ' . esc_html($sync_success['password_name']) . ' for user: ' . esc_html($sync_success['username']) . '</p>';
        echo '</div>';
        delete_transient('almaseo_dashboard_sync_success');
    }

    // Check if password import is needed
    if ($needs_import = get_transient('almaseo_needs_password_import')) {
        echo '<div class="notice notice-warning">';
        echo '<p><strong>🔑 Password Import Required</strong></p>';
        echo '<p>Your site is registered in AlmaSEO Dashboard (Site ID: ' . esc_html($needs_import['site_id']) . ') but the Application Password needs to be imported.</p>';
        echo '<p><a href="#import-connection" class="button button-primary">Import Connection Details</a></p>';
        echo '</div>';
    }

    // Handle import connection
    if (isset($_POST['import_connection']) && check_admin_referer('almaseo_import_connection')) {
        $import_site_id = isset($_POST['import_site_id']) ? sanitize_text_field($_POST['import_site_id']) : '';
        $import_app_password = isset($_POST['import_app_password']) ? sanitize_text_field($_POST['import_app_password']) : '';
        $import_username = isset($_POST['import_username']) ? sanitize_text_field($_POST['import_username']) : '';

        $import_result = almaseo_import_connection_details($import_site_id, $import_app_password, $import_username);

        if ($import_result['success']) {
            echo '<div class="notice notice-success is-dismissible">';
            echo '<p><strong>✅ Connection Imported Successfully!</strong></p>';
            echo '<p>Site ID: ' . esc_html($import_result['site_id']) . ' | Username: ' . esc_html($import_result['username']) . '</p>';
            echo '<p>All AlmaSEO SEO Playground features are now unlocked!</p>';
            echo '</div>';

            // Refresh connection status
            $connection_status = almaseo_get_connection_status();
            $comprehensive_status = almaseo_get_comprehensive_connection_status();
            $app_password = get_option('almaseo_app_password', '');
        } else {
            echo '<div class="notice notice-error is-dismissible">';
            echo '<p><strong>❌ Import Failed</strong></p>';
            echo '<p>' . esc_html($import_result['message']) . '</p>';
            echo '</div>';
        }
    }

    // Handle manual password saving with improved validation
    if (isset($_POST['save_manual_password']) && check_admin_referer('almaseo_manual_password')) {
        $manual_password = isset($_POST['manual_app_password']) ? sanitize_text_field($_POST['manual_app_password']) : '';

        // Clean up password (remove spaces that WordPress adds)
        $cleaned_password = str_replace(' ', '', $manual_password ?? '');

        // Validate password format
        if (strlen($cleaned_password) >= 20 && preg_match('/^[A-Za-z0-9]+$/', $cleaned_password)) {
            update_option('almaseo_app_password', $cleaned_password);
            update_option('almaseo_connected_user', $username);
            update_option('almaseo_connected_date', current_time('mysql'));
            $app_password = $cleaned_password;

            echo '<div class="notice notice-success almaseo-success-notice" style="padding: 15px; border-left: 4px solid #46b450;">';
            echo '<h3 style="margin-top: 0;">🎉 Perfect! Connection Password Saved Successfully</h3>';
            echo '<p>Great job! Your WordPress plugin is now configured and ready to connect.</p>';
            echo '<p><strong>Next step:</strong> Test the connection using the button below to verify everything is working.</p>';
            echo '</div>';

            // Auto-scroll to test button
            echo '<script>setTimeout(function() {
                var testBtn = document.getElementById("almaseo-test-connection");
                if (testBtn) testBtn.scrollIntoView({behavior: "smooth", block: "center"});
            }, 500);</script>';
        } else {
            echo '<div class="notice notice-error" style="padding: 15px; border-left: 4px solid #dc3232;">';
            echo '<p><strong>Invalid Application Password</strong></p>';
            echo '<p>Please ensure you copied the entire password from WordPress. It should be in format: <code>xxxx xxxx xxxx xxxx xxxx xxxx</code></p>';
            echo '</div>';
        }
    }
    $generated_password = '';
    $generation_error = '';

    if (isset($_POST['generate_password']) && check_admin_referer('almaseo_generate_password')) {
        $app_passwords_check = almaseo_check_app_passwords_available();
        if (is_wp_error($app_passwords_check)) {
            $generation_error = $app_passwords_check->get_error_message();
        } else if (function_exists('wp_generate_application_password')) {
            // First clean up any existing AlmaSEO passwords to ensure only one exists
            almaseo_cleanup_old_passwords($current_user->ID);

            $label = 'AlmaSEO Connection ' . wp_date('Y-m-d H:i:s');
            list($new_password, $item) = wp_generate_application_password($current_user->ID, $label);

            if ($new_password) {
                $generated_password = $new_password;
                update_option('almaseo_app_password', $new_password);
                update_option('almaseo_connected_user', $username);
                update_option('almaseo_connected_date', current_time('mysql'));
                echo '<div class="notice notice-success almaseo-success-notice">';
                echo '<h3>🎉 Excellent! Connection Password Generated Successfully</h3>';
                echo '<p>Perfect! Your WordPress plugin is now configured and ready to connect.</p>';
                echo '</div>';
            } else {
                $generation_error = 'Failed to generate Application Password. Please try again.';
            }
        }
    }

    // Handle disconnection
    if (isset($_POST['almaseo_disconnect']) && check_admin_referer('almaseo_disconnect')) {
        almaseo_disconnect_site();
        $connection_status = almaseo_get_connection_status();
        $app_password = '';
        echo '<div class="notice notice-success"><p>Successfully disconnected from AlmaSEO.</p></div>';
    }

    echo '<div class="almaseo-container">';

    // Header with prominent logo
    $logo_url = plugins_url('almaseo-logo.png', ALMASEO_PLUGIN_FILE);
    echo '<div class="almaseo-header">';
    echo '<div class="header-content">';
    echo '<div class="header-text">';
    echo '<h1>SEO Playground by AlmaSEO - Connection Settings</h1>';
    echo '<p>Connect your WordPress site to AlmaSEO AI for automated content creation and deeper LLM optimization analysis</p>';
    echo '<p style="color: rgba(255,255,255,0.9); font-size: 13px; margin: 8px 0 0;">';
    esc_html_e('Connect to AlmaSEO Dashboard to unlock AI tools, deeper LLM optimization analysis (more accurate summaries, entities, and trust signals), and enhanced data sources. Core features work without it.', 'almaseo-seo-playground');
    echo '</p>';
    echo '</div>';
    echo '<div class="header-logo">';
    echo '<img src="' . esc_url($logo_url) . '" alt="AlmaSEO Assistant" class="almaseo-character">';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    if ($connection_status['connected'] && $app_password) {
        // Connected State
        echo '<div class="almaseo-connected-state">';
        echo '<div class="status-badge connected">';
        echo '<i class="dashicons dashicons-yes-alt"></i>';
        echo '<span>Connected to AlmaSEO</span>';
        echo '</div>';

        echo '<div class="connection-details">';
        echo '<div class="detail-item">';
        echo '<strong>Site:</strong> ' . esc_html(get_bloginfo('name'));
        echo '</div>';

        // Show Site ID if available
        if ($comprehensive_status['site_id']) {
            echo '<div class="detail-item">';
            echo '<strong>Site ID:</strong> <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">' . esc_html($comprehensive_status['site_id']) . '</code>';
            echo '</div>';
        }

        echo '<div class="detail-item">';
        echo '<strong>Connected User:</strong> ' . esc_html($username ?: 'Unknown');
        echo '</div>';

        // Show connection type
        $connection_type_display = array(
            'dashboard_initiated' => '🌐 Dashboard',
            'plugin_initiated' => '🔌 Plugin',
            'imported' => '📥 Imported',
            'unknown' => '❓ Unknown'
        );
        $conn_type = $comprehensive_status['connection_type'];
        echo '<div class="detail-item">';
        echo '<strong>Connection Type:</strong> ' . esc_html($connection_type_display[$conn_type] ?? $connection_type_display['unknown']);
        echo '</div>';

        // Show connection status icons
        echo '<div class="detail-item">';
        echo '<strong>Status:</strong> ';
        echo '<span style="margin-right: 10px;">Plugin ' . esc_html($comprehensive_status['plugin_connected'] ? '✅' : '❌') . '</span>';
        echo '<span>Dashboard ' . esc_html($comprehensive_status['dashboard_connected'] ? '✅' : '❌') . '</span>';
        echo '</div>';

        echo '<div class="detail-item">';
        echo '<strong>Connected:</strong> ' . esc_html($connection_status['connected_date'] ?? 'Unknown');
        echo '</div>';

        // Show detected passwords if any
        if (isset($comprehensive_status['detected_passwords']) && $comprehensive_status['detected_passwords'] > 0) {
            echo '<div class="detail-item" style="background: #fff3cd; padding: 5px; border-radius: 3px; margin-top: 5px;">';
            echo '<strong>⚠️ Note:</strong> Found ' . intval($comprehensive_status['detected_passwords']) . ' AlmaSEO password(s) for users: ' . esc_html(implode(', ', $comprehensive_status['detected_users']));
            echo '</div>';
        }

        echo '</div>';

        echo '<div class="connected-actions" style="margin-top: 20px; padding: 15px; background: #f0f8ff; border-radius: 4px;">';
        echo '<button type="button" class="button button-secondary test-connection-btn" id="almaseo-test-connection" style="height: 40px; padding: 0 20px; font-size: 14px;">';
        echo '<span class="dashicons dashicons-update" style="margin-right: 5px; line-height: 28px; vertical-align: middle;"></span>';
        echo 'Test Connection to AlmaSEO API</button>';
        echo '<span class="connection-test-result" id="test-result" style="display: inline-block; margin-left: 15px; font-size: 14px;"></span>';
        echo '<div class="connection-status-indicator" style="margin-top: 10px; font-size: 13px; color: #666;">';
        echo '<span class="dashicons dashicons-info" style="color: #0073aa;"></span>';
        echo ' Click to verify your connection with the AlmaSEO API server';
        echo '</div>';
        echo '</div>';

        echo '<form method="post" class="disconnect-form">';
        wp_nonce_field('almaseo_disconnect');
        echo '<button type="submit" name="almaseo_disconnect" value="1" class="button button-link-delete" onclick="return confirm(\'Are you sure you want to disconnect from AlmaSEO? This will remove API access.\')">Disconnect Site</button>';
        echo '</form>';

        echo '</div>';

        // Schema Settings Section (always shown)
        echo '</div>'; // Close connected state div
    } // Close if connected

    if (!$connection_status['connected'] || !$app_password) {
        // Setup Wizard State
        echo '<div class="almaseo-setup-wizard">';

        if ($generation_error) {
            echo '<div class="notice notice-error"><p>' . esc_html($generation_error ?: 'An error occurred') . '</p></div>';

            // Add fallback instructions for hosting limitations
            if (strpos($generation_error ?? '', 'not available') !== false) {
                echo '<div class="almaseo-fallback-instructions">';
                echo '<h4>🔧 Alternative Setup Method</h4>';
                echo '<p>Your hosting provider may have disabled automatic Application Password generation. No problem! You can create one manually:</p>';

                echo '<div class="manual-steps-container">';
                echo '<div class="manual-steps">';
                echo '<div class="step-instruction">';
                echo '<div class="step-number-small">1</div>';
                echo '<div class="step-content">';
                echo '<strong>Open WordPress Users Page</strong>';
                echo '<p>Click the button below to open Users → Your Profile in a new tab:</p>';
                echo '<a href="' . esc_url(admin_url('profile.php')) . '" target="_blank" class="button button-secondary">';
                echo '<i class="dashicons dashicons-admin-users"></i> Open Your Profile';
                echo '</a>';
                echo '</div>';
                echo '</div>';

                echo '<div class="step-instruction">';
                echo '<div class="step-number-small">2</div>';
                echo '<div class="step-content">';
                echo '<strong>Create Application Password</strong>';
                echo '<p>In the new tab, scroll down to <strong>"Application Passwords"</strong> section and:</p>';
                echo '<ul>';
                echo '<li>Enter <code>AlmaSEO Connection</code> as the name</li>';
                echo '<li>Click <strong>"Add New Application Password"</strong></li>';
                echo '<li>Copy the generated password (it looks like: <code>abcd efgh ijkl mnop</code>)</li>';
                echo '</ul>';
                echo '</div>';
                echo '</div>';

                echo '<div class="step-instruction">';
                echo '<div class="step-number-small">3</div>';
                echo '<div class="step-content">';
                echo '<strong>Return and Paste Below</strong>';
                echo '<p>Come back to this tab and paste your password in the form below:</p>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
                echo '</div>';

                echo '<div class="manual-password-section">';
                echo '<h5><i class="dashicons dashicons-admin-network"></i> Paste Your Application Password Here:</h5>';
                echo '<form method="post">';
                wp_nonce_field('almaseo_manual_password');
                echo '<div class="password-input-group">';
                echo '<input type="text" name="manual_app_password" placeholder="Paste your Application Password here (e.g., abcd efgh ijkl mnop)" class="connection-detail-input" style="width: 100%; margin: 10px 0;">';
                echo '<button type="submit" name="save_manual_password" class="button button-primary button-large">Save Connection Password</button>';
                echo '</div>';
                echo '</form>';
                echo '<div class="help-note">';
                echo '<i class="dashicons dashicons-info"></i>';
                echo '<small>The password will be 16 characters with spaces (like: abcd efgh ijkl mnop). Paste the entire thing including spaces.</small>';
                echo '</div>';
                echo '</div>';
                echo '</div>';
            }
        }

        // Step 1: Generate Password
        echo '<div class="setup-step ' . esc_attr(!$app_password && !$generated_password ? 'active' : ($app_password || $generated_password ? 'completed' : '')) . '">';
        echo '<div class="step-header">';
        echo '<div class="step-number">1</div>';
        echo '<h3>Generate Connection Password</h3>';
        echo '</div>';

        if (!$app_password && !$generated_password) {
            // Only show the generate button if we haven't already tried and failed
            if (empty($generation_error)) {
                echo '<p>Create a secure Application Password for AlmaSEO to connect to your site.</p>';
                echo '<form method="post">';
                wp_nonce_field('almaseo_generate_password');
                echo '<button type="submit" name="generate_password" class="button button-primary button-large">Generate Password Now</button>';
                echo '</form>';
            } else {
                // If generation failed, don't show the button again
                echo '<div class="step-completed error">';
                echo '<i class="dashicons dashicons-warning"></i>';
                echo '<span>Automatic generation not available on your hosting</span>';
                echo '</div>';
                echo '<p>Please use the manual method below instead.</p>';
            }
        } else {
            echo '<div class="step-completed">';
            echo '<i class="dashicons dashicons-yes-alt"></i>';
            echo '<span>Password generated successfully!</span>';
            echo '</div>';
        }
        echo '</div>';

        // Step 2: Copy Details (only show if password exists)
        if ($app_password || $generated_password) {
            $display_password = $generated_password ?: $app_password;

            echo '<div class="setup-step active">';
            echo '<div class="step-header">';
            echo '<div class="step-number">2</div>';
            echo '<h3>Copy Your Connection Details</h3>';
            echo '</div>';

            echo '<p>Copy these details to paste into your AlmaSEO dashboard:</p>';

            echo '<div class="connection-details-box">';
            echo '<div class="detail-row">';
            echo '<label>Site URL:</label>';
            echo '<div class="detail-value">';
            echo '<input type="text" readonly value="' . esc_attr(get_site_url()) . '" class="connection-detail-input">';
            echo '<button type="button" class="copy-btn" data-copy="' . esc_attr(get_site_url()) . '">Copy</button>';
            echo '</div>';
            echo '</div>';

            echo '<div class="detail-row">';
            echo '<label>Username:</label>';
            echo '<div class="detail-value">';
            echo '<input type="text" readonly value="' . esc_attr($username) . '" class="connection-detail-input">';
            echo '<button type="button" class="copy-btn" data-copy="' . esc_attr($username) . '">Copy</button>';
            echo '</div>';
            echo '</div>';

            echo '<div class="detail-row">';
            echo '<label>Application Password:</label>';
            echo '<div class="detail-value">';
            echo '<input type="password" readonly value="' . esc_attr($display_password) . '" class="connection-detail-input">';
            echo '<button type="button" class="copy-btn" data-copy="' . esc_attr($display_password) . '">Copy</button>';
            echo '</div>';
            echo '</div>';

            echo '<div class="copy-all-section">';
            echo '<button type="button" class="button button-primary copy-all-btn">Copy All Details</button>';
            echo '<span class="copy-feedback"></span>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            echo '<div class="final-success-section">';
            echo '<div class="success-celebration">';
            echo '<div class="success-icon">✅</div>';
            echo '<h3>Plugin Configuration Complete!</h3>';
            echo '<p>Now return to your AlmaSEO onboarding to continue the setup process.</p>';
            echo '</div>';

            echo '<div class="next-steps-container">';
            echo '<h4>📋 Next Step:</h4>';

            echo '<div class="next-step-item onboarding-return">';
            echo '<div class="next-step-number">→</div>';
            echo '<div class="next-step-content">';
            echo '<strong>Return to AlmaSEO Onboarding</strong>';
            echo '<p>Go back to your AlmaSEO setup tab in your browser and click <strong>"Plugin Installed"</strong> to continue.</p>';
            echo '<div class="onboarding-reminder">';
            echo '<div class="reminder-icon">💡</div>';
            echo '<div class="reminder-text">';
            echo '<p><strong>Can\'t find the AlmaSEO tab?</strong> Look for the browser tab where you started adding your site. It should show the 3-step setup process with "Install Plugin" highlighted.</p>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            // Simple connection details for reference
            echo '<div class="setup-step completed reference-step">';
            echo '<div class="step-header">';
            echo '<div class="step-number">✓</div>';
            echo '<h3>Your Connection Details (For Reference)</h3>';
            echo '</div>';

            echo '<p>These details are now stored securely. The onboarding process will use them automatically:</p>';
        }

        echo '</div>'; // End setup wizard
    }

    // Help Section
    echo '<div class="almaseo-help-section">';
    echo '<button type="button" class="help-toggle button button-secondary">Need Help?</button>';
    echo '<div class="help-content" style="display: none;">';
    echo '<h4>Troubleshooting</h4>';
    echo '<ul>';
    echo '<li><strong>Password generation fails:</strong> Ensure you\'re using WordPress 5.6 or higher</li>';
    echo '<li><strong>Connection issues:</strong> Verify your site is accessible from the internet</li>';
    echo '<li><strong>Permission errors:</strong> Make sure you have administrator privileges</li>';
    echo '<li><strong>Still having problems?</strong> Contact <a href="mailto:support@almaseo.com">support@almaseo.com</a></li>';
    echo '</ul>';
    echo '</div>';
    echo '</div>';

    echo '</div>'; // End container

    // Add styles and scripts (keeping the existing CSS from the original)
    echo '<style>
    .almaseo-container {
        max-width: 800px;
        margin: 20px 0;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }

    .almaseo-header {
        background: linear-gradient(135deg, #4CAF50, #45a049);
        color: white;
        padding: 30px;
        border-radius: 8px;
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 30px;
    }

    .header-text {
        flex: 1;
    }

    .almaseo-header h1 {
        margin: 0 0 20px 0;
        font-size: 32px;
        font-weight: 700;
        line-height: 1.4;
        letter-spacing: -0.5px;
    }

    .almaseo-header p {
        margin: 0;
        opacity: 0.95;
        font-size: 16px;
        line-height: 1.6;
        padding-top: 5px;
    }

    .header-logo {
        flex-shrink: 0;
    }

    .almaseo-character {
        width: 240px;
        height: 240px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        padding: 20px;
        transition: transform 0.3s ease;
        filter: drop-shadow(0 4px 12px rgba(0,0,0,0.2));
    }

    .almaseo-character:hover {
        transform: scale(1.05) rotate(2deg);
    }

    .almaseo-connected-state {
        background: #f0f8f0;
        border: 2px solid #4CAF50;
        border-radius: 8px;
        padding: 30px;
        text-align: center;
    }

    .status-badge {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 20px;
        border-radius: 25px;
        font-weight: 600;
        font-size: 16px;
        margin-bottom: 20px;
    }

    .status-badge.connected {
        background: #4CAF50;
        color: white;
    }

    .connection-details {
        background: white;
        padding: 20px;
        border-radius: 6px;
        margin: 20px 0;
        text-align: left;
    }

    .detail-item {
        margin-bottom: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }

    .detail-item:last-child {
        border-bottom: none;
    }

    .connected-actions {
        margin: 20px 0;
    }

    .connection-test-result {
        margin-left: 15px;
        font-weight: 600;
    }

    .disconnect-form {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
    }

    .almaseo-setup-wizard {
        background: white;
        border: 1px solid #ddd;
        border-radius: 8px;
        overflow: hidden;
    }

    .setup-step {
        padding: 30px;
        border-bottom: 1px solid #eee;
        position: relative;
    }

    .setup-step:last-child {
        border-bottom: none;
    }

    .setup-step.active {
        background: #fafafa;
    }

    .setup-step.completed {
        background: #f0f8f0;
    }

    .step-header {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 15px;
    }

    .step-number {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background: #4CAF50;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        font-size: 18px;
    }

    .setup-step:not(.active):not(.completed) .step-number {
        background: #ccc;
    }

    .step-header h3 {
        margin: 0;
        font-size: 20px;
        color: #333;
    }

    .step-completed {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #4CAF50;
        font-weight: 600;
    }

    .step-completed.error {
        color: #e74c3c;
        background: #ffeaea;
        padding: 12px;
        border-radius: 6px;
        border-left: 4px solid #e74c3c;
    }

    .connection-details-box {
        background: #f8f9fa;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 20px;
        margin: 15px 0;
    }

    .detail-row {
        display: flex;
        align-items: center;
        margin-bottom: 15px;
    }

    .detail-row:last-child {
        margin-bottom: 0;
    }

    .detail-row label {
        width: 150px;
        font-weight: 600;
        color: #555;
    }

    .detail-value {
        flex: 1;
        display: flex;
        gap: 10px;
    }

    .connection-detail-input {
        flex: 1;
        padding: 8px 12px;
        border: 1px solid #ddd;
        border-radius: 4px;
        background: white;
        font-family: monospace;
    }

    .copy-btn {
        background: #4CAF50;
        color: white;
        border: none;
        padding: 8px 15px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 12px;
        font-weight: 600;
    }

    .copy-btn:hover {
        background: #45a049;
    }

    .copy-all-section {
        text-align: center;
        margin-top: 20px;
        padding-top: 20px;
        border-top: 1px solid #ddd;
    }

    .copy-feedback {
        margin-left: 15px;
        font-weight: 600;
        color: #4CAF50;
    }

    .almaseo-instructions {
        background: #e8f4fd;
        border-left: 4px solid #2196F3;
        padding: 20px;
        margin: 15px 0;
        border-radius: 0 6px 6px 0;
    }

    .almaseo-instructions ol {
        margin: 0;
        padding-left: 20px;
    }

    .almaseo-instructions li {
        margin-bottom: 8px;
        line-height: 1.5;
    }

    .final-action {
        text-align: center;
        margin-top: 20px;
    }

    .final-action .button-large {
        padding: 15px 30px;
        font-size: 16px;
        height: auto;
    }

    .almaseo-help-section {
        margin-top: 30px;
        text-align: center;
    }

    .help-content {
        background: #f9f9f9;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 20px;
        margin-top: 15px;
        text-align: left;
    }

    .help-content h4 {
        margin-top: 0;
        color: #333;
    }

    .help-content ul {
        margin: 0;
    }

    .almaseo-fallback-instructions {
        background: #e8f4fd;
        border: 1px solid #2196F3;
        border-radius: 8px;
        padding: 25px;
        margin: 20px 0;
    }

    .almaseo-fallback-instructions h4 {
        color: #1976D2;
        margin-top: 0;
    }

    .almaseo-fallback-instructions ol {
        padding-left: 20px;
    }

    .almaseo-fallback-instructions li {
        margin-bottom: 8px;
        line-height: 1.5;
    }

    .manual-password-section {
        background: white;
        padding: 20px;
        border-radius: 6px;
        margin-top: 20px;
        border: 1px solid #ddd;
    }

    .manual-password-section h5 {
        margin-top: 0;
        color: #333;
    }

    .manual-steps-container {
        margin: 20px 0;
    }

    .manual-steps {
        display: flex;
        flex-direction: column;
        gap: 20px;
        margin-bottom: 25px;
    }

    .step-instruction {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        padding: 15px;
        background: rgba(255, 255, 255, 0.7);
        border-radius: 8px;
        border-left: 4px solid #2196F3;
    }

    .step-number-small {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #2196F3;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        flex-shrink: 0;
        margin-top: 5px;
    }

    .step-content {
        flex: 1;
    }

    .step-content strong {
        color: #1976D2;
        font-size: 16px;
    }

    .step-content p {
        margin: 5px 0 10px 0;
    }

    .step-content ul {
        margin: 0;
        padding-left: 20px;
    }

    .step-content li {
        margin-bottom: 5px;
    }

    .password-input-group {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .help-note {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 10px;
        padding: 10px;
        background: #fff3cd;
        border: 1px solid #ffeaa7;
        border-radius: 4px;
        color: #856404;
    }

    .almaseo-success-notice {
        background: linear-gradient(135deg, #d4edda, #c3e6cb) !important;
        border: 2px solid #28a745 !important;
        border-radius: 8px !important;
        padding: 20px !important;
        margin: 20px 0 !important;
    }

    .almaseo-success-notice h3 {
        margin: 0 0 10px 0 !important;
        color: #155724 !important;
        font-size: 20px !important;
    }

    .almaseo-success-notice p {
        margin: 0 !important;
        color: #155724 !important;
        font-size: 16px !important;
    }

    .final-success-section {
        background: #f8fff8;
        border: 2px solid #28a745;
        border-radius: 12px;
        padding: 30px;
        margin: 20px 0;
    }

    .success-celebration {
        text-align: center;
        margin-bottom: 30px;
        padding-bottom: 20px;
        border-bottom: 1px solid #e0e0e0;
    }

    .success-icon {
        font-size: 48px;
        margin-bottom: 24px;
    }

    .success-celebration h3 {
        color: #155724;
        margin: 0 0 10px 0;
        font-size: 24px;
    }

    .success-celebration p {
        color: #155724;
        font-size: 16px;
        margin: 0;
    }

    .next-steps-container {
        margin-top: 20px;
    }

    .next-steps-container h4 {
        color: #155724;
        margin-bottom: 20px;
        font-size: 18px;
    }

    .next-step-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 25px;
        padding: 15px;
        background: rgba(255, 255, 255, 0.8);
        border-radius: 8px;
        border-left: 4px solid #28a745;
    }

    .next-step-number {
        width: 30px;
        height: 30px;
        border-radius: 50%;
        background: #28a745;
        color: white;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        flex-shrink: 0;
        margin-top: 5px;
    }

    .next-step-content {
        flex: 1;
    }

    .next-step-content strong {
        color: #155724;
        font-size: 16px;
    }

    .next-step-content p {
        margin: 5px 0 10px 0;
        color: #155724;
    }

    .next-step-btn {
        margin-top: 10px;
    }

    .connection-preview {
        background: white;
        border: 1px solid #ddd;
        border-radius: 6px;
        padding: 15px;
        margin-top: 10px;
    }

    .preview-item {
        margin-bottom: 8px;
        font-family: monospace;
        font-size: 14px;
        color: #333;
    }

    .preview-item:last-child {
        margin-bottom: 0;
    }

    .final-step {
        background: #f0f8f0;
        border: 2px solid #28a745;
    }

    @media (max-width: 768px) {
        .step-instruction {
            flex-direction: column;
            text-align: center;
        }

        .step-number-small {
            margin: 0 auto 10px auto;
        }

        .next-step-item {
            flex-direction: column;
            text-align: center;
        }

        .next-step-number {
            margin: 0 auto 10px auto;
        }
    }

    @media (max-width: 768px) {
        .header-content {
            flex-direction: column;
            text-align: center;
        }

        .almaseo-character {
            width: 180px;
            height: 180px;
        }

        .detail-row {
            flex-direction: column;
            align-items: stretch;
        }

        .detail-row label {
            width: auto;
            margin-bottom: 5px;
        }
    }
    </style>';

    // Import Connection Details Section
    echo '<div id="import-connection" class="almaseo-import-section" style="margin-top: 40px; padding: 20px; background: #f8f9fa; border: 1px solid #e0e0e0; border-radius: 5px; display: ' . esc_attr($comprehensive_status['plugin_connected'] ? 'none' : 'block') . ';">';
    echo '<h3 style="margin-top: 0;">📥 Import Connection from Dashboard</h3>';
    echo '<p>If your site is already registered in the AlmaSEO Dashboard, you can import the connection details here.</p>';

    echo '<form method="post" id="import-connection-form">';
    wp_nonce_field('almaseo_import_connection');

    echo '<table class="form-table">';
    echo '<tr>';
    echo '<th scope="row"><label for="import_site_id">Site ID</label></th>';
    echo '<td>';
    echo '<input type="text" id="import_site_id" name="import_site_id" value="' . esc_attr($comprehensive_status['site_id']) . '" class="regular-text" placeholder="e.g., 2049" />';
    echo '<p class="description">The Site ID from your AlmaSEO Dashboard</p>';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<th scope="row"><label for="import_app_password">Application Password</label></th>';
    echo '<td>';
    echo '<input type="text" id="import_app_password" name="import_app_password" class="regular-text" placeholder="xxxx xxxx xxxx xxxx xxxx xxxx" />';
    echo '<p class="description">The Application Password (with or without spaces)</p>';
    echo '</td>';
    echo '</tr>';

    echo '<tr>';
    echo '<th scope="row"><label for="import_username">WordPress Username (Optional)</label></th>';
    echo '<td>';
    echo '<input type="text" id="import_username" name="import_username" value="' . esc_attr($username) . '" class="regular-text" />';
    echo '<p class="description">Leave blank to auto-detect</p>';
    echo '</td>';
    echo '</tr>';
    echo '</table>';

    echo '<p class="submit">';
    echo '<button type="submit" name="import_connection" class="button button-primary">Import Connection</button>';
    echo '<button type="button" id="check-dashboard" class="button button-secondary" style="margin-left: 10px;">Check Dashboard Registration</button>';
    echo '</p>';
    echo '</form>';

    echo '<div id="import-result" style="margin-top: 15px; display: none;"></div>';
    echo '</div>';

    // Diagnostic Mode Section (only visible when WP_DEBUG is true)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        echo '<div class="almaseo-diagnostic-section" style="margin-top: 40px; padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px;">';
        echo '<h3 style="margin-top: 0;">🔧 Diagnostic Mode (Debug)</h3>';
        echo '<details>';
        echo '<summary style="cursor: pointer; font-weight: bold;">Connection Debug Information</summary>';
        echo '<pre style="background: white; padding: 15px; border-radius: 3px; overflow-x: auto; margin-top: 10px;">';
        echo 'Comprehensive Status:' . PHP_EOL;
        echo esc_html(print_r($comprehensive_status, true));
        echo PHP_EOL . 'Basic Connection Status:' . PHP_EOL;
        echo esc_html(print_r($connection_status, true));
        echo PHP_EOL . 'Stored Options:' . PHP_EOL;
        echo 'almaseo_dashboard_site_id: ' . esc_html(get_option('almaseo_dashboard_site_id', 'not set')) . PHP_EOL;
        echo 'almaseo_connection_type: ' . esc_html(get_option('almaseo_connection_type', 'not set')) . PHP_EOL;
        echo 'almaseo_dashboard_synced: ' . esc_html(get_option('almaseo_dashboard_synced') ? 'true' : 'false') . PHP_EOL;
        echo 'almaseo_dashboard_registered: ' . esc_html(get_option('almaseo_dashboard_registered') ? 'true' : 'false') . PHP_EOL;
        echo '</pre>';
        echo '</details>';
        echo '</div>';
    }

    echo '<script>
    jQuery(document).ready(function($) {
        // Handle import connection form
        $("#import-connection-form").on("submit", function(e) {
            e.preventDefault();

            const siteId = $("#import_site_id").val();
            const appPassword = $("#import_app_password").val();
            const username = $("#import_username").val();

            if (!siteId || !appPassword) {
                $("#import-result").html("<div class=\"notice notice-error\"><p>Please provide both Site ID and Application Password.</p></div>").show();
                return;
            }

            // Show loading
            $("#import-result").html("<div class=\"notice notice-info\"><p>Importing connection details...</p></div>").show();

            // Submit form
            this.submit();
        });

        // Check dashboard registration
        $("#check-dashboard").on("click", function() {
            const btn = $(this);
            btn.prop("disabled", true).text("Checking...");

            $.post(ajaxurl, {
                action: "almaseo_check_dashboard",
                nonce: "' . esc_js(wp_create_nonce('almaseo_nonce')) . '"
            }, function(response) {
                if (response.success) {
                    let message = response.data.registered ?
                        "✅ Site found in dashboard (Site ID: " + response.data.site_id + ")" :
                        "❌ Site not found in dashboard";
                    $("#import-result").html("<div class=\"notice notice-info\"><p>" + message + "</p></div>").show();

                    if (response.data.site_id) {
                        $("#import_site_id").val(response.data.site_id);
                    }
                } else {
                    $("#import-result").html("<div class=\"notice notice-error\"><p>Check failed: " + response.data.message + "</p></div>").show();
                }
            }).always(function() {
                btn.prop("disabled", false).text("Check Dashboard Registration");
            });
        });

        // Copy functionality
        $(".copy-btn").on("click", function() {
            const btn = $(this);
            const text = btn.data("copy");
            navigator.clipboard.writeText(text).then(function() {
                const originalText = btn.text();
                btn.text("Copied!");
                setTimeout(() => btn.text(originalText), 2000);
            });
        });

        // Copy all details
        $(".copy-all-btn").on("click", function() {
            const siteUrl = $(".connection-detail-input").eq(0).val();
            const username = $(".connection-detail-input").eq(1).val();
            const password = $(".connection-detail-input").eq(2).val();

            const allDetails = `Site URL: ${siteUrl}\nUsername: ${username}\nApplication Password: ${password}`;

            navigator.clipboard.writeText(allDetails).then(function() {
                $(".copy-feedback").text("All details copied to clipboard!").show();
                setTimeout(() => $(".copy-feedback").fadeOut(), 3000);
            });
        });

        // Help toggle
        $(".help-toggle").on("click", function() {
            $(".help-content").slideToggle();
        });

        // Test connection
        $(".test-connection-btn").on("click", function() {
            const btn = $(this);
            const result = $(".connection-test-result");

            btn.prop("disabled", true).text("Testing...");
            result.empty();

            $.post(ajaxurl, {
                action: "almaseo_test_connection"
            }).done(function(response) {
                if (response.success) {
                    result.html("<span style=\"color: #4CAF50;\">✓ Connection successful</span>");
                } else {
                    result.html("<span style=\"color: #e74c3c;\">✗ " + (response.data.message || "Connection failed") + "</span>");
                }
            }).fail(function() {
                result.html("<span style=\"color: #e74c3c;\">✗ Connection test failed</span>");
            }).always(function() {
                btn.prop("disabled", false).text("Test Connection");
            });
        });
    });
    </script>';
}
} // end function_exists guard: almaseo_connector_settings_page
