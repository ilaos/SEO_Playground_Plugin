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
                        <option value="dataforseo" <?php selected($current_provider, 'dataforseo'); ?>>
                            DataForSEO (Beta)
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
        
        <!-- DataForSEO Settings -->
        <div id="dataforseo-settings" style="<?php echo $current_provider === 'dataforseo' ? '' : 'display:none;'; ?>">
            <h2>DataForSEO Settings</h2>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="almaseo_dataforseo_login">API Login</label>
                    </th>
                    <td>
                        <input type="text" 
                               name="almaseo_dataforseo_login" 
                               id="almaseo_dataforseo_login" 
                               value="<?php echo esc_attr($dataforseo_login); ?>" 
                               class="regular-text" 
                               placeholder="Your DataForSEO login">
                        <p class="description">
                            Your DataForSEO API login (email).
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="almaseo_dataforseo_password">API Password</label>
                    </th>
                    <td>
                        <input type="password" 
                               name="almaseo_dataforseo_password" 
                               id="almaseo_dataforseo_password" 
                               class="regular-text" 
                               placeholder="Your DataForSEO password">
                        <p class="description">
                            Your DataForSEO API password. 
                            <a href="https://app.dataforseo.com/register" target="_blank">Get API credentials</a>
                        </p>
                    </td>
                </tr>
            </table>
        </div>
        
        <!-- Information Box -->
        <div class="notice notice-info" style="margin-top: 20px;">
            <p>
                <strong>About Data Providers:</strong>
            </p>
            <ul style="list-style: disc; margin-left: 20px;">
                <li><strong>Sample Data:</strong> Uses generated data for testing. No API required.</li>
                <li><strong>Google Search Console:</strong> Provides real ranking data from your Search Console account. Limited to your own site's keywords.</li>
                <li><strong>DataForSEO:</strong> Comprehensive SEO metrics including search volume, keyword difficulty, and SERP features. Requires paid API account.</li>
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