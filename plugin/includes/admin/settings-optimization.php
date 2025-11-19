<?php
/**
 * AlmaSEO Optimization Settings Page
 */

if (!defined('ABSPATH')) {
    exit;
}

// Handle form submission
if (isset($_POST['almaseo_save_optimization_settings'])) {
    check_admin_referer('almaseo_optimization_settings');
    
    // Update provider setting
    if (isset($_POST['almaseo_keyword_provider'])) {
        update_option('almaseo_keyword_provider', sanitize_text_field($_POST['almaseo_keyword_provider']));
    }
    
    // Update country and language settings
    if (isset($_POST['almaseo_target_country'])) {
        update_option('almaseo_target_country', sanitize_text_field($_POST['almaseo_target_country']));
    }
    
    if (isset($_POST['almaseo_target_language'])) {
        update_option('almaseo_target_language', sanitize_text_field($_POST['almaseo_target_language']));
    }
    
    // Update GSC credentials if provided
    if ($_POST['almaseo_keyword_provider'] === 'gsc') {
        if (isset($_POST['almaseo_gsc_site_url'])) {
            update_option('almaseo_gsc_site_url', esc_url_raw($_POST['almaseo_gsc_site_url']));
        }
        // Note: OAuth credentials would be handled separately
    }
    
    // Update DataForSEO credentials if provided
    if ($_POST['almaseo_keyword_provider'] === 'dataforseo') {
        if (isset($_POST['almaseo_dataforseo_login'])) {
            update_option('almaseo_dataforseo_login', sanitize_text_field($_POST['almaseo_dataforseo_login']));
        }
        if (isset($_POST['almaseo_dataforseo_password'])) {
            update_option('almaseo_dataforseo_password', sanitize_text_field($_POST['almaseo_dataforseo_password']));
        }
    }
    
    echo '<div class="notice notice-success"><p>Settings saved successfully!</p></div>';
}

// Get current settings
$current_provider = get_option('almaseo_keyword_provider', 'stub');
$target_country = get_option('almaseo_target_country', 'US');
$target_language = get_option('almaseo_target_language', 'en');
$gsc_site_url = get_option('almaseo_gsc_site_url', '');
$dataforseo_login = get_option('almaseo_dataforseo_login', '');

// Countries list (ISO 3166-1 alpha-2)
$countries = [
    'US' => 'United States',
    'GB' => 'United Kingdom',
    'CA' => 'Canada',
    'AU' => 'Australia',
    'DE' => 'Germany',
    'FR' => 'France',
    'ES' => 'Spain',
    'IT' => 'Italy',
    'NL' => 'Netherlands',
    'BR' => 'Brazil',
    'MX' => 'Mexico',
    'IN' => 'India',
    'JP' => 'Japan',
    'CN' => 'China',
    'KR' => 'South Korea',
    'RU' => 'Russia',
    'SE' => 'Sweden',
    'NO' => 'Norway',
    'DK' => 'Denmark',
    'FI' => 'Finland',
];

// Languages list
$languages = [
    'en' => 'English',
    'es' => 'Spanish',
    'fr' => 'French',
    'de' => 'German',
    'it' => 'Italian',
    'pt' => 'Portuguese',
    'nl' => 'Dutch',
    'ru' => 'Russian',
    'ja' => 'Japanese',
    'zh' => 'Chinese',
    'ko' => 'Korean',
    'ar' => 'Arabic',
    'hi' => 'Hindi',
    'sv' => 'Swedish',
    'no' => 'Norwegian',
    'da' => 'Danish',
    'fi' => 'Finnish',
];
?>

<div class="wrap">
    <h1>
        <span style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                     -webkit-background-clip: text; 
                     -webkit-text-fill-color: transparent;
                     background-clip: text;">
            AlmaSEO
        </span> 
        Optimization Settings
    </h1>
    
    <p class="description">
        Configure keyword data providers and optimization settings for the Post-Writing Optimization feature.
    </p>
    
    <form method="post" action="">
        <?php wp_nonce_field('almaseo_optimization_settings'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="almaseo_keyword_provider">Keyword Data Source</label>
                </th>
                <td>
                    <select name="almaseo_keyword_provider" id="almaseo_keyword_provider" class="regular-text">
                        <option value="stub" <?php selected($current_provider, 'stub'); ?>>
                            Sample Data (Default)
                        </option>
                        <option value="gsc" <?php selected($current_provider, 'gsc'); ?>>
                            Google Search Console (Beta)
                        </option>
                        <option value="dataforseo" <?php selected($current_provider, 'dataforseo'); ?> disabled>
                            DataForSEO (Coming Soon) 🔜
                        </option>
                    </select>
                    <p class="description">
                        Select the data source for keyword metrics. Sample Data requires no configuration.
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="almaseo_target_country">Target Country</label>
                </th>
                <td>
                    <select name="almaseo_target_country" id="almaseo_target_country" class="regular-text">
                        <?php foreach ($countries as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($target_country, $code); ?>>
                                <?php echo esc_html($name); ?> (<?php echo esc_html($code); ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        Country for localized keyword data (used with DataForSEO).
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="almaseo_target_language">Target Language</label>
                </th>
                <td>
                    <select name="almaseo_target_language" id="almaseo_target_language" class="regular-text">
                        <?php foreach ($languages as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($target_language, $code); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        Language for keyword analysis and suggestions.
                    </p>
                </td>
            </tr>
        </table>
        
        <!-- Google Search Console Settings -->
        <div id="gsc-settings" style="<?php echo $current_provider === 'gsc' ? '' : 'display:none;'; ?>">
            <h2>Google Search Console Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="almaseo_gsc_site_url">Site URL</label>
                    </th>
                    <td>
                        <input type="url" 
                               name="almaseo_gsc_site_url" 
                               id="almaseo_gsc_site_url" 
                               value="<?php echo esc_attr($gsc_site_url); ?>" 
                               class="regular-text" 
                               placeholder="https://example.com">
                        <p class="description">
                            Your site URL as configured in Google Search Console.
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">Authentication</th>
                    <td>
                        <p class="description">
                            Google Search Console integration requires OAuth authentication. 
                            This will be available in a future update.
                        </p>
                        <button type="button" class="button" disabled>
                            Connect to Google Search Console
                        </button>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- DataForSEO Settings - Coming Soon -->
        <div id="dataforseo-settings" style="<?php echo $current_provider === 'dataforseo' ? '' : 'display:none;'; ?>">
            <div style="background: #fff9e6; border: 2px solid #f7b731; border-radius: 8px; padding: 20px; margin: 20px 0; position: relative; overflow: hidden;">
                <!-- Gold Coming Soon Badge -->
                <div style="position: absolute; top: 15px; right: -35px; width: 200px; text-align: center; transform: rotate(45deg); background: linear-gradient(135deg, #f5a623 0%, #f7b731 100%); box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2); z-index: 1;">
                    <span style="display: block; padding: 8px 0; color: #fff; font-weight: 700; font-size: 11px; letter-spacing: 1.5px; text-transform: uppercase; text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);">COMING SOON</span>
                </div>

                <h2 style="margin-top: 0; display: flex; align-items: center; gap: 10px;">
                    <span class="dashicons dashicons-chart-line" style="font-size: 32px; color: #f7b731;"></span>
                    DataForSEO Keyword Intelligence
                </h2>

                <p style="font-size: 15px; line-height: 1.7; color: #555;">
                    We're building powerful keyword intelligence features powered by DataForSEO's industry-leading SERP data.
                    This premium integration will bring professional-grade SEO insights directly to your WordPress editor.
                </p>

                <div style="background: #fff; border: 1px solid #e0e0e0; border-radius: 6px; padding: 15px; margin: 20px 0;">
                    <h3 style="margin-top: 0; font-size: 16px;">Coming Features:</h3>
                    <ul style="margin: 10px 0; padding-left: 25px; line-height: 1.8;">
                        <li><strong>Advanced SERP Metrics</strong> - Real-time search volume and trends</li>
                        <li><strong>Competitor Analysis</strong> - See who's ranking and analyze strategies</li>
                        <li><strong>Keyword Difficulty</strong> - Accurate difficulty metrics for quick wins</li>
                        <li><strong>Live SERP Snapshots</strong> - Capture and analyze search results</li>
                        <li><strong>CPC & Ad Intelligence</strong> - Cost-per-click and advertising data</li>
                        <li><strong>International Data</strong> - Target specific countries and languages</li>
                    </ul>
                </div>

                <div style="background: #f0f9ff; border: 1px solid #3b82f6; border-radius: 6px; padding: 15px; margin: 20px 0;">
                    <p style="margin: 0; font-size: 14px; color: #1e40af;">
                        <strong>📅 Development Status:</strong> Active development in progress<br>
                        <strong>🚀 Expected Release:</strong> Q2 2025
                    </p>
                </div>

                <?php if (almaseo_is_pro_active()): ?>
                    <div style="background: #e7f5e9; border: 1px solid #46b450; border-radius: 6px; padding: 15px; text-align: center;">
                        <span class="dashicons dashicons-yes-alt" style="color: #46b450; font-size: 24px; vertical-align: middle;"></span>
                        <strong style="color: #2c662d; font-size: 15px;">Included in Your Pro Plan</strong>
                        <p style="margin: 10px 0 0; color: #2c662d; font-size: 14px;">
                            You'll get automatic access when this feature launches. No additional purchase needed!
                        </p>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; margin-top: 20px;">
                        <a href="https://almaseo.com/pro#dataforseo" class="button button-primary button-large" target="_blank" style="background: #f7b731; border-color: #f7b731; text-shadow: none; box-shadow: 0 2px 4px rgba(247, 183, 49, 0.3);">
                            <span class="dashicons dashicons-unlock" style="vertical-align: middle;"></span>
                            Upgrade to Pro for Early Access
                        </a>
                    </div>
                <?php endif; ?>

                <p style="margin-top: 20px; font-size: 13px; color: #666; text-align: center;">
                    Want to be notified when this launches?
                    <a href="https://almaseo.com/notify" target="_blank" style="color: #f7b731; font-weight: 500;">Join our notification list</a>
                </p>
            </div>
        </div>
        
        <!-- Information Box -->
        <div class="notice notice-info" style="margin-top: 20px;">
            <p>
                <strong>About Data Providers:</strong>
            </p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><strong>Sample Data:</strong> Uses generated data for testing. No API required.</li>
                <li><strong>Google Search Console:</strong> Provides real ranking data from your Search Console account. Limited to your own site's keywords.</li>
                <li><strong>DataForSEO (Coming Soon):</strong> Advanced SERP analytics with search volume, keyword difficulty, competitor analysis, and more. Expected Q2 2025.</li>
            </ul>
        </div>
        
        <!-- Rate Limits Information -->
        <div class="notice notice-warning" style="margin-top: 20px;">
            <p>
                <strong>Rate Limits:</strong>
            </p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li>Maximum 50 keyword refreshes per 10 minutes per user</li>
                <li>Google Search Console: 10 requests per minute</li>
                <li>DataForSEO: 50 requests per minute (depends on your plan)</li>
            </ul>
        </div>
        
        <p class="submit">
            <input type="submit" 
                   name="almaseo_save_optimization_settings" 
                   class="button button-primary" 
                   value="Save Settings">
        </p>
    </form>
</div>

<script>
jQuery(document).ready(function($) {
    // Show/hide provider-specific settings
    $('#almaseo_keyword_provider').on('change', function() {
        var provider = $(this).val();
        
        // Hide all provider settings
        $('#gsc-settings, #dataforseo-settings').hide();
        
        // Show selected provider settings
        if (provider === 'gsc') {
            $('#gsc-settings').show();
        } else if (provider === 'dataforseo') {
            $('#dataforseo-settings').show();
        }
    });
});
</script>

<style>
.form-table th {
    width: 200px;
}
.notice ul {
    margin-top: 5px;
}
.notice li {
    margin-bottom: 5px;
}
</style>