<?php
/**
 * AlmaSEO Connection Settings Page
 *
 * Renders the connection/settings page for linking the site to AlmaSEO Dashboard.
 * Handles password generation with REST verification, JWT fallback for restricted hosts,
 * manual import, connection testing, and disconnection.
 *
 * @package AlmaSEO
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!function_exists('almaseo_connector_settings_page')) {
function almaseo_connector_settings_page() {
    if (!current_user_can('manage_options')) return;

    $current_user = wp_get_current_user();
    $username = $current_user->user_login;
    $connection_status = almaseo_get_connection_status();
    $comprehensive_status = almaseo_get_comprehensive_connection_status();
    $app_password = get_option('almaseo_app_password', '');

    // Validate stored password — check if a real WP Application Password still exists
    $stale_password = false;
    if ($app_password && !isset($_POST['generate_password']) && !isset($_POST['save_manual_password'])) {
        if (class_exists('WP_Application_Passwords')) {
            $wp_app_passwords = WP_Application_Passwords::get_user_application_passwords($current_user->ID);
            $has_almaseo_wp_password = false;
            foreach ($wp_app_passwords as $wp_app) {
                if (strpos($wp_app['name'], 'AlmaSEO') === 0) {
                    $has_almaseo_wp_password = true;
                    break;
                }
            }
            if (!$has_almaseo_wp_password) {
                $stale_password = true;
                delete_option('almaseo_app_password');
                $app_password = '';
                $connection_status = array('connected' => false, 'connected_user' => null, 'connected_date' => null, 'site_url' => get_site_url());
            }
        }
    }

    // Handle manual password saving with improved validation
    if (isset($_POST['save_manual_password']) && check_admin_referer('almaseo_manual_password')) {
        $manual_password = isset($_POST['manual_app_password']) ? sanitize_text_field(wp_unslash($_POST['manual_app_password'])) : '';
        $cleaned_password = str_replace(' ', '', $manual_password);

        if (strlen($cleaned_password) >= 20 && preg_match('/^[A-Za-z0-9]+$/', $cleaned_password)) {
            update_option('almaseo_app_password', $cleaned_password);
            update_option('almaseo_connected_user', $username);
            update_option('almaseo_connected_date', current_time('mysql'));
            $app_password = $cleaned_password;

            echo '<div class="notice notice-success almaseo-success-notice" style="padding: 15px; border-left: 4px solid #46b450;">';
            echo '<h3 style="margin-top: 0;">Connection Password Saved Successfully</h3>';
            echo '<p>Your WordPress plugin is now configured and ready to connect.</p>';
            echo '</div>';
        } else {
            echo '<div class="notice notice-error" style="padding: 15px; border-left: 4px solid #dc3232;">';
            echo '<p><strong>Invalid Application Password</strong></p>';
            echo '<p>Please ensure you copied the entire password from WordPress. It should be in format: <code>xxxx xxxx xxxx xxxx xxxx xxxx</code></p>';
            echo '</div>';
        }
    }

    $generated_password = '';
    $generation_error = '';

    // Handle password generation with REST verification
    if (isset($_POST['generate_password']) && check_admin_referer('almaseo_generate_password')) {
        $app_passwords_check = almaseo_check_app_passwords_available();
        if (is_wp_error($app_passwords_check)) {
            $generation_error = 'wp_version';
        } else {
            almaseo_cleanup_old_passwords($current_user->ID);
            $label = 'AlmaSEO Connection ' . wp_date('Y-m-d H:i:s');

            // Try the helper function first, fall back to class method
            $gen_result = null;
            if (function_exists('wp_generate_application_password')) {
                $gen_result = wp_generate_application_password($current_user->ID, $label);
            }
            if (!$gen_result || is_wp_error($gen_result)) {
                if (class_exists('WP_Application_Passwords')) {
                    $gen_result = WP_Application_Passwords::create_new_application_password(
                        $current_user->ID,
                        array('name' => $label)
                    );
                }
            }

            if (is_wp_error($gen_result)) {
                $generation_error = 'hosting_blocked';
            } elseif (!empty($gen_result) && is_array($gen_result)) {
                $new_password = is_string($gen_result[0]) ? $gen_result[0] : null;
                if ($new_password) {
                    // Verify the password actually works via REST API
                    // Some hosts allow creation but block the Authorization header
                    $test_url = rest_url('wp/v2/users/me');
                    $test_response = wp_remote_get($test_url, array(
                        'headers' => array(
                            'Authorization' => 'Basic ' . base64_encode($username . ':' . $new_password),
                        ),
                        'timeout' => 10,
                        'sslverify' => false,
                    ));
                    $test_code = wp_remote_retrieve_response_code($test_response);

                    if ($test_code === 200) {
                        // Password works — full success
                        $generated_password = $new_password;
                        update_option('almaseo_app_password', $new_password);
                        update_option('almaseo_connected_user', $username);
                        update_option('almaseo_connected_date', current_time('mysql'));
                        echo '<div class="notice notice-success almaseo-success-notice">';
                        echo '<h3>Connection Password Generated Successfully</h3>';
                        echo '<p>Your WordPress plugin is now configured and ready to connect.</p>';
                        echo '</div>';
                    } else {
                        // Password was created but REST API auth is blocked by hosting
                        // Clean up the unusable password
                        if (class_exists('WP_Application_Passwords')) {
                            $app_passwords_list = WP_Application_Passwords::get_user_application_passwords($current_user->ID);
                            foreach ($app_passwords_list as $ap) {
                                if ($ap['name'] === $label) {
                                    WP_Application_Passwords::delete_application_password($current_user->ID, $ap['uuid']);
                                    break;
                                }
                            }
                        }
                        $generation_error = 'hosting_blocked';
                    }
                } else {
                    $generation_error = 'hosting_blocked';
                }
            } else {
                $generation_error = 'hosting_blocked';
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

    // Header
    $logo_url = plugins_url('almaseo-logo.png', ALMASEO_PLUGIN_FILE);
    echo '<div class="almaseo-header">';
    echo '<div class="header-content">';
    echo '<div class="header-text">';
    echo '<h1>SEO Playground by AlmaSEO - Connection Settings</h1>';
    echo '<p>Connect your WordPress site to AlmaSEO AI for automated content creation and deeper LLM optimization analysis</p>';
    echo '</div>';
    echo '<div class="header-logo">';
    echo '<img src="' . esc_url($logo_url) . '" alt="AlmaSEO Assistant" class="almaseo-character">';
    echo '</div>';
    echo '</div>';
    echo '</div>';

    // Show stale password warning
    if ($stale_password) {
        echo '<div class="notice notice-warning" style="margin:16px 0;padding:12px 16px;border-left:4px solid #dba617;background:#fff8e1;">';
        echo '<h3 style="margin:0 0 8px">Connection Password Expired</h3>';
        echo '<p style="margin:0 0 8px">The previously stored Application Password no longer exists in WordPress. ';
        echo 'This can happen if it was deleted from your <strong>Users &rarr; Profile</strong> page or during a plugin reinstall.</p>';
        echo '<p style="margin:0"><strong>Please generate a new password below</strong>, then update it in your AlmaSEO dashboard site settings.</p>';
        echo '</div>';
    }

    if ($connection_status['connected'] && $app_password) {
        // === CONNECTED STATE ===
        echo '<div class="almaseo-connected-state">';
        echo '<div class="status-badge connected">';
        echo '<i class="dashicons dashicons-yes-alt"></i>';
        echo '<span>Connected to AlmaSEO</span>';
        echo '</div>';

        echo '<div class="connection-details">';
        echo '<div class="detail-item">';
        echo '<strong>Site:</strong> ' . esc_html(get_bloginfo('name'));
        echo '</div>';

        if ($comprehensive_status['site_id']) {
            echo '<div class="detail-item">';
            echo '<strong>Site ID:</strong> <code style="background: #f0f0f0; padding: 2px 6px; border-radius: 3px;">' . esc_html($comprehensive_status['site_id']) . '</code>';
            echo '</div>';
        }

        echo '<div class="detail-item">';
        echo '<strong>Connected User:</strong> ' . esc_html($username ?: 'Unknown');
        echo '</div>';

        $connection_type_display = array(
            'dashboard_initiated' => 'Dashboard',
            'plugin_initiated' => 'Plugin',
            'imported' => 'Imported',
            'unknown' => 'Unknown'
        );
        $conn_type = $comprehensive_status['connection_type'];
        echo '<div class="detail-item">';
        echo '<strong>Connection Type:</strong> ' . esc_html($connection_type_display[$conn_type] ?? $connection_type_display['unknown']);
        echo '</div>';

        echo '<div class="detail-item">';
        echo '<strong>Status:</strong> ';
        echo '<span style="margin-right: 10px;">Plugin ' . ($comprehensive_status['plugin_connected'] ? '&#10003;' : '&#10007;') . '</span>';
        echo '<span>Dashboard ' . ($comprehensive_status['dashboard_connected'] ? '&#10003;' : '&#10007;') . '</span>';
        echo '</div>';

        echo '<div class="detail-item">';
        echo '<strong>Connected:</strong> ' . esc_html($connection_status['connected_date'] ?? 'Unknown');
        echo '</div>';
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

    } else {
        // === SETUP WIZARD STATE ===
        echo '<div class="almaseo-setup-wizard">';

        if ($generation_error) {
            if ($generation_error === 'hosting_blocked') {
                // ===== HOSTING/WAF BLOCKED — JWT IS THE PRIMARY PATH =====
                echo '<div style="background:#fff3cd;border:1px solid #ffc107;border-radius:8px;padding:20px;margin:20px 30px 0;">';
                echo '<h3 style="margin:0 0 8px;color:#856404;">Your hosting provider is blocking Application Passwords</h3>';
                echo '<p style="margin:0 0 16px;color:#664d03;">This is a known issue with some hosting providers (GoDaddy, SiteGround, Bluehost, and others). ';
                echo 'Their server firewall blocks the WordPress feature used for standard authentication. <strong>This is not a problem with your site or WordPress version.</strong></p>';
                echo '</div>';

                // JWT — Recommended solution
                $jwt_token = almaseo_create_jwt($username);
                echo '<div style="background:#f0f7ff;border:2px solid #2271b1;border-radius:8px;padding:20px;margin:20px 30px;">';
                echo '<div style="display:flex;align-items:center;gap:8px;margin-bottom:12px;">';
                echo '<span style="background:#2271b1;color:#fff;font-weight:700;font-size:13px;padding:2px 10px;border-radius:4px;">RECOMMENDED</span>';
                echo '<h3 style="margin:0;color:#1d4ed8;">Use JWT Token Instead</h3>';
                echo '</div>';
                echo '<p style="margin:0 0 12px;color:#374151;">The JWT Token is a secure alternative that bypasses hosting restrictions entirely. Copy the token below and paste it into your AlmaSEO dashboard when connecting this site.</p>';

                echo '<div style="background:#fff;border:1px solid #c5d9ed;border-radius:6px;padding:14px;margin-bottom:12px;">';

                echo '<div style="margin-bottom:8px;">';
                echo '<label style="font-weight:600;font-size:13px;display:block;margin-bottom:4px;">Your JWT Token:</label>';
                echo '<div style="display:flex;gap:8px;">';
                echo '<input type="text" readonly value="' . esc_attr($jwt_token) . '" class="connection-detail-input" style="flex:1;font-family:monospace;font-size:11px;padding:8px;border:1px solid #d1d5db;border-radius:4px;">';
                echo '<button type="button" class="button button-primary copy-btn" data-copy="' . esc_attr($jwt_token) . '" style="white-space:nowrap;">Copy Token</button>';
                echo '</div>';
                echo '</div>';

                echo '<div style="margin-bottom:8px;">';
                echo '<label style="font-weight:600;font-size:13px;display:block;margin-bottom:4px;">Site URL:</label>';
                echo '<div style="display:flex;gap:8px;">';
                echo '<input type="text" readonly value="' . esc_attr(get_site_url()) . '" class="connection-detail-input" style="flex:1;padding:8px;border:1px solid #d1d5db;border-radius:4px;">';
                echo '<button type="button" class="button copy-btn" data-copy="' . esc_attr(get_site_url()) . '">Copy</button>';
                echo '</div>';
                echo '</div>';

                echo '<div>';
                echo '<label style="font-weight:600;font-size:13px;display:block;margin-bottom:4px;">Username:</label>';
                echo '<div style="display:flex;gap:8px;">';
                echo '<input type="text" readonly value="' . esc_attr($username) . '" class="connection-detail-input" style="flex:1;padding:8px;border:1px solid #d1d5db;border-radius:4px;">';
                echo '<button type="button" class="button copy-btn" data-copy="' . esc_attr($username) . '">Copy</button>';
                echo '</div>';
                echo '</div>';
                echo '</div>';

                echo '<div style="background:#ecfdf5;border:1px solid #a7f3d0;border-radius:6px;padding:12px;">';
                echo '<p style="margin:0;font-size:13px;color:#065f46;"><strong>How to connect:</strong></p>';
                echo '<ol style="margin:6px 0 0;padding-left:20px;font-size:13px;color:#065f46;line-height:1.8;">';
                echo '<li>Go to your <strong>AlmaSEO dashboard</strong> at <code>api.almaseo.com</code></li>';
                echo '<li>Add your site or go to its <strong>WordPress Settings</strong></li>';
                echo '<li>Paste the <strong>JWT Token</strong> in the JWT Token field</li>';
                echo '<li>Click <strong>Save</strong></li>';
                echo '</ol>';
                echo '</div>';
                echo '</div>';

                // Manual Application Password — secondary option in collapsible
                echo '<details style="margin:0 30px 20px;border:1px solid #e5e7eb;border-radius:8px;overflow:hidden;">';
                echo '<summary style="padding:14px 16px;cursor:pointer;font-weight:600;color:#6b7280;background:#f9fafb;">Other option: Manual Application Password <span style="font-weight:400;font-size:12px;color:#9ca3af;">(may also fail on this host)</span></summary>';
                echo '<div style="padding:16px;border-top:1px solid #e5e7eb;">';
                echo '<div style="background:#fef3c7;border:1px solid #f59e0b;border-radius:6px;padding:10px 14px;margin-bottom:12px;">';
                echo '<p style="margin:0;font-size:13px;color:#92400e;"><strong>Note:</strong> If your hosting blocks automatic Application Passwords, ';
                echo 'creating one manually through Users &rarr; Profile will likely fail for the same reason. ';
                echo 'We recommend using the JWT Token above instead.</p>';
                echo '</div>';
                echo '<ol style="font-size:13px;line-height:1.8;margin:0 0 12px;padding-left:20px;">';
                echo '<li>Go to <a href="' . esc_url(admin_url('profile.php')) . '" target="_blank">Users &rarr; Your Profile</a></li>';
                echo '<li>Scroll down to "Application Passwords"</li>';
                echo '<li>Enter <code>AlmaSEO Connection</code> as the name</li>';
                echo '<li>Click "Add New Application Password"</li>';
                echo '<li>If it works, copy the password and paste it below</li>';
                echo '</ol>';
                echo '<form method="post">';
                wp_nonce_field('almaseo_manual_password');
                echo '<div style="display:flex;gap:8px;">';
                echo '<input type="text" name="manual_app_password" placeholder="Paste Application Password here (e.g., abcd efgh ijkl mnop)" class="connection-detail-input" style="flex:1;padding:8px;border:1px solid #d1d5db;border-radius:4px;">';
                echo '<button type="submit" name="save_manual_password" class="button button-primary">Save Password</button>';
                echo '</div>';
                echo '</form>';
                echo '</div>';
                echo '</details>';

            } elseif ($generation_error === 'wp_version') {
                echo '<div class="notice notice-error" style="margin:20px 30px;"><p>';
                echo '<strong>WordPress 5.6 or higher is required.</strong> ';
                echo 'Application Passwords are a feature introduced in WordPress 5.6. ';
                echo 'Please update your WordPress installation to use this feature.';
                echo '</p></div>';
            }
        }

        // Step 1: Generate Password (only show if no hosting_blocked error)
        if ($generation_error !== 'hosting_blocked') {
            echo '<div class="setup-step ' . esc_attr(!$app_password && !$generated_password ? 'active' : ($app_password || $generated_password ? 'completed' : '')) . '">';
            echo '<div class="step-header">';
            echo '<div class="step-number">1</div>';
            echo '<h3>Generate Connection Password</h3>';
            echo '</div>';

            if (!$app_password && !$generated_password) {
                if (empty($generation_error)) {
                    echo '<p>Create a secure Application Password for AlmaSEO to connect to your site.</p>';
                    echo '<form method="post">';
                    wp_nonce_field('almaseo_generate_password');
                    echo '<button type="submit" name="generate_password" class="button button-primary button-large">Generate Password Now</button>';
                    echo '</form>';
                } else {
                    echo '<div class="step-completed error">';
                    echo '<i class="dashicons dashicons-warning"></i>';
                    echo '<span>Automatic generation not available on your hosting</span>';
                    echo '</div>';
                }
            } else {
                echo '<div class="step-completed">';
                echo '<i class="dashicons dashicons-yes-alt"></i>';
                echo '<span>Password generated successfully!</span>';
                echo '</div>';
            }
            echo '</div>';
        }

        // Step 2: Copy Details (only show if password exists and auth works)
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

            // JWT Token section
            $jwt_token = almaseo_create_jwt($username);
            echo '<div class="detail-row">';
            echo '<label>JWT Token <span style="font-size:11px;color:#666;font-weight:normal">(Alternative Auth)</span>:</label>';
            echo '<div class="detail-value">';
            echo '<input type="text" readonly value="' . esc_attr($jwt_token) . '" class="connection-detail-input" style="font-size:11px;">';
            echo '<button type="button" class="copy-btn" data-copy="' . esc_attr($jwt_token) . '">Copy</button>';
            echo '</div>';
            echo '</div>';

            echo '<div style="background:#f0f7ff;border:1px solid #c5d9ed;border-radius:4px;padding:10px 14px;margin:8px 0 0;">';
            echo '<p style="margin:0;font-size:12px;color:#2271b1;"><strong>Hosting blocking Application Passwords?</strong> Use the JWT Token above instead. ';
            echo 'Paste it in the <em>JWT Token</em> field on the AlmaSEO dashboard site settings. This bypasses REST API restrictions that some hosts impose.</p>';
            echo '</div>';

            echo '<div class="copy-all-section">';
            echo '<button type="button" class="button button-primary copy-all-btn">Copy All Details</button>';
            echo '<span class="copy-feedback"></span>';
            echo '</div>';
            echo '</div>';
            echo '</div>';

            // Verify the password actually works
            $test_password = $generated_password ?: $app_password;
            $auth_works = false;
            if ($test_password && $username) {
                $test_url = rest_url('wp/v2/users/me');
                $test_response = wp_remote_get($test_url, array(
                    'headers' => array(
                        'Authorization' => 'Basic ' . base64_encode($username . ':' . $test_password),
                    ),
                    'timeout' => 10,
                    'sslverify' => false,
                ));
                if (!is_wp_error($test_response) && wp_remote_retrieve_response_code($test_response) === 200) {
                    $auth_works = true;
                }
            }

            if ($auth_works) {
                echo '<div class="final-success-section">';
                echo '<div class="success-celebration">';
                echo '<div class="success-icon">&#10003;</div>';
                echo '<h3>Plugin Configuration Complete!</h3>';
                echo '<p>Connection verified &mdash; your Application Password is working correctly.</p>';
                echo '</div>';
            } else {
                echo '<div class="final-success-section" style="border-color:#b45309;background:#fffbeb;">';
                echo '<div class="success-celebration">';
                echo '<div class="success-icon">&#9888;</div>';
                echo '<h3 style="color:#b45309">Connection Not Verified</h3>';
                echo '<p style="color:#92400e;">The Application Password could not be verified via REST API. Your hosting may be blocking Authorization headers.</p>';
                echo '<p style="color:#92400e;"><strong>Use the JWT Token above instead</strong> &mdash; paste it in the JWT Token field on the AlmaSEO dashboard.</p>';
                echo '</div>';
            }

            echo '<div class="next-steps-container">';
            echo '<h4>Next Step:</h4>';

            echo '<div class="next-step-item onboarding-return">';
            echo '<div class="next-step-number">&rarr;</div>';
            echo '<div class="next-step-content">';
            echo '<strong>Return to AlmaSEO Onboarding</strong>';
            echo '<p>Go back to your AlmaSEO setup tab in your browser and click <strong>"Plugin Installed"</strong> to continue.</p>';
            echo '<div class="onboarding-reminder">';
            echo '<div class="reminder-text">';
            echo '<p><strong>Can\'t find the AlmaSEO tab?</strong> Look for the browser tab where you started adding your site. It should show the 3-step setup process with "Install Plugin" highlighted.</p>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>';
            echo '</div>'; // end final-success-section
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
    echo '<li><strong>Hosting blocks Application Passwords:</strong> Use the JWT Token method instead</li>';
    echo '<li><strong>Connection issues:</strong> Verify your site is accessible from the internet</li>';
    echo '<li><strong>Permission errors:</strong> Make sure you have administrator privileges</li>';
    echo '<li><strong>Still having problems?</strong> Contact <a href="mailto:support@almaseo.com">support@almaseo.com</a></li>';
    echo '</ul>';
    echo '</div>';
    echo '</div>';

    echo '</div>'; // End container

    // Diagnostic Mode Section (only visible when WP_DEBUG is true)
    if (defined('WP_DEBUG') && WP_DEBUG) {
        echo '<div class="almaseo-diagnostic-section" style="margin-top: 40px; padding: 20px; background: #fff3cd; border: 1px solid #ffc107; border-radius: 5px; max-width: 800px;">';
        echo '<h3 style="margin-top: 0;">Diagnostic Mode (Debug)</h3>';
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

    // Styles
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

    .header-text { flex: 1; }

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

    .header-logo { flex-shrink: 0; }

    .almaseo-character {
        width: 240px;
        height: 240px;
        border-radius: 50%;
        background: rgba(255, 255, 255, 0.1);
        padding: 20px;
        transition: transform 0.3s ease;
        filter: drop-shadow(0 4px 12px rgba(0,0,0,0.2));
    }

    .almaseo-character:hover { transform: scale(1.05) rotate(2deg); }

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

    .status-badge.connected { background: #4CAF50; color: white; }

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

    .detail-item:last-child { border-bottom: none; }

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

    .setup-step:last-child { border-bottom: none; }
    .setup-step.active { background: #fafafa; }
    .setup-step.completed { background: #f0f8f0; }

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

    .setup-step:not(.active):not(.completed) .step-number { background: #ccc; }

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

    .detail-row:last-child { margin-bottom: 0; }

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

    .copy-btn:hover { background: #45a049; }

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

    .help-content h4 { margin-top: 0; color: #333; }
    .help-content ul { margin: 0; }

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

    .next-steps-container { margin-top: 20px; }

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

    .next-step-content { flex: 1; }

    .next-step-content strong {
        color: #155724;
        font-size: 16px;
    }

    .next-step-content p {
        margin: 5px 0 10px 0;
        color: #155724;
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

        .next-step-item {
            flex-direction: column;
            text-align: center;
        }

        .next-step-number {
            margin: 0 auto 10px auto;
        }
    }
    </style>';

    // JavaScript
    echo '<script>
    jQuery(document).ready(function($) {
        // Copy functionality
        $(".copy-btn").on("click", function() {
            var btn = $(this);
            var text = btn.data("copy");
            navigator.clipboard.writeText(text).then(function() {
                var originalText = btn.text();
                btn.text("Copied!");
                setTimeout(function() { btn.text(originalText); }, 2000);
            });
        });

        // Copy all details
        $(".copy-all-btn").on("click", function() {
            var siteUrl = $(".connection-detail-input").eq(0).val();
            var username = $(".connection-detail-input").eq(1).val();
            var password = $(".connection-detail-input").eq(2).val();

            var allDetails = "Site URL: " + siteUrl + "\nUsername: " + username + "\nApplication Password: " + password;

            navigator.clipboard.writeText(allDetails).then(function() {
                $(".copy-feedback").text("All details copied to clipboard!").show();
                setTimeout(function() { $(".copy-feedback").fadeOut(); }, 3000);
            });
        });

        // Help toggle
        $(".help-toggle").on("click", function() {
            $(".help-content").slideToggle();
        });

        // Test connection
        $(".test-connection-btn").on("click", function() {
            var btn = $(this);
            var result = $(".connection-test-result");

            btn.prop("disabled", true).text("Testing...");
            result.empty();

            $.post(ajaxurl, {
                action: "almaseo_test_connection"
            }).done(function(response) {
                if (response.success) {
                    result.html("<span style=\"color: #4CAF50;\">Connection successful</span>");
                } else {
                    result.html("<span style=\"color: #e74c3c;\">" + (response.data.message || "Connection failed") + "</span>");
                }
            }).fail(function() {
                result.html("<span style=\"color: #e74c3c;\">Connection test failed</span>");
            }).always(function() {
                btn.prop("disabled", false).text("Test Connection to AlmaSEO API");
            });
        });
    });
    </script>';
}
} // end function_exists guard: almaseo_connector_settings_page
