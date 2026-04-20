<?php
/**
 * Updates & I/O Tab - Auto-updates panel, Export/Import settings, HTML sitemap shortcode helper
 *
 * @package AlmaSEO
 * @since 4.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get update manager data
$update_manager = null;
$update_settings = array('channel' => 'beta', 'last_check' => 0, 'last_found' => null);
$current_channel = 'beta';
$last_check = 0;
$last_found = null;

try {
    if (class_exists('AlmaSEO_Update_Manager')) {
        $update_manager = AlmaSEO_Update_Manager::get_instance();
        $update_settings = $update_manager->get_settings();
        $current_channel = $update_settings['channel'] ?? 'beta';
        $last_check = $update_settings['last_check'] ?? 0;
        $last_found = $update_settings['last_found'] ?? null;
    }
} catch (Exception $e) {
    // Handle gracefully if update manager is not available
}
?>

<!-- Auto-Updates -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php esc_html_e('Auto-Updates', 'almaseo-seo-playground'); ?></h2>
        <div class="almaseo-chips">
            <span class="almaseo-chip">
                <?php esc_html_e('Channel:', 'almaseo-seo-playground'); ?> 
                <strong><?php echo esc_html(ucfirst($current_channel)); ?></strong>
            </span>
            <?php if ($last_found && version_compare($last_found, ALMASEO_PLUGIN_VERSION, '>')): ?>
            <span class="almaseo-chip almaseo-chip-warning">
                <?php esc_html_e('Update Available', 'almaseo-seo-playground'); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-form-group">
            <label for="update-channel"><?php esc_html_e('Update Channel:', 'almaseo-seo-playground'); ?></label>
            <select id="update-channel" class="almaseo-select">
                <option value="stable" <?php selected($current_channel, 'stable'); ?>><?php esc_html_e('Stable', 'almaseo-seo-playground'); ?></option>
                <option value="beta" <?php selected($current_channel, 'beta'); ?>><?php esc_html_e('Beta', 'almaseo-seo-playground'); ?></option>
            </select>
            <p class="description"><?php esc_html_e('Choose between stable releases or beta versions with latest features', 'almaseo-seo-playground'); ?></p>
        </div>
        
        <div class="almaseo-update-info">
            <div class="almaseo-stat-grid">
                <div class="almaseo-stat">
                    <div class="almaseo-stat-value"><?php echo esc_html(ALMASEO_PLUGIN_VERSION); ?></div>
                    <div class="almaseo-stat-label"><?php esc_html_e('Current Version', 'almaseo-seo-playground'); ?></div>
                </div>
                <?php if ($last_found): ?>
                <div class="almaseo-stat">
                    <div class="almaseo-stat-value <?php echo version_compare($last_found, ALMASEO_PLUGIN_VERSION, '>') ? 'almaseo-text-warning' : 'almaseo-text-success'; ?>">
                        <?php echo esc_html($last_found); ?>
                    </div>
                    <div class="almaseo-stat-label"><?php esc_html_e('Latest Version', 'almaseo-seo-playground'); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($last_check > 0): ?>
                <div class="almaseo-stat">
                    <div class="almaseo-stat-value"><?php echo esc_html(human_time_diff($last_check)); ?></div>
                    <div class="almaseo-stat-label"><?php esc_html_e('Last Check', 'almaseo-seo-playground'); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="almaseo-button-group">
            <button type="button" class="button button-primary" id="check-updates-btn">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Check for Updates Now', 'almaseo-seo-playground'); ?>
            </button>
            <?php if ($last_found && version_compare($last_found, ALMASEO_PLUGIN_VERSION, '>')): ?>
            <button type="button" class="button button-secondary" id="install-update-btn">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Install Update', 'almaseo-seo-playground'); ?>
            </button>
            <?php endif; ?>
        </div>
        
        <!-- Update Status -->
        <div id="update-status" style="display:none;">
            <div class="almaseo-progress-bar">
                <div class="almaseo-progress-fill" style="width: 0%"></div>
            </div>
            <p id="update-status-text"><?php esc_html_e('Checking for updates...', 'almaseo-seo-playground'); ?></p>
        </div>
        
        <!-- Auto-Update Settings -->
        <div class="almaseo-form-section">
            <h3><?php esc_html_e('Automatic Updates', 'almaseo-seo-playground'); ?></h3>
            <div class="almaseo-form-group">
                <label class="almaseo-toggle-item">
                    <input type="checkbox" id="auto-updates-enabled" 
                           <?php checked(get_option('almaseo_auto_updates_enabled', false)); ?>>
                    <span><?php esc_html_e('Enable Automatic Updates', 'almaseo-seo-playground'); ?></span>
                    <small><?php esc_html_e('Automatically install updates when available', 'almaseo-seo-playground'); ?></small>
                </label>
            </div>
            <div class="almaseo-form-group">
                <label class="almaseo-toggle-item">
                    <input type="checkbox" id="auto-updates-beta" 
                           <?php checked(get_option('almaseo_auto_updates_beta', false)); ?>>
                    <span><?php esc_html_e('Include Beta Versions', 'almaseo-seo-playground'); ?></span>
                    <small><?php esc_html_e('Also auto-update to beta releases (not recommended for production)', 'almaseo-seo-playground'); ?></small>
                </label>
            </div>
        </div>
    </div>
</div>

<div class="almaseo-two-column">
    <!-- Export Settings -->
    <div class="almaseo-card">
        <div class="almaseo-card-header">
            <h2><?php esc_html_e('Export Settings', 'almaseo-seo-playground'); ?></h2>
        </div>
        <div class="almaseo-card-body">
            <div class="almaseo-form-group">
                <p class="description"><?php esc_html_e('Download your current sitemap settings as a JSON file for backup or migration purposes.', 'almaseo-seo-playground'); ?></p>
                
                <div class="almaseo-export-options">
                    <label class="almaseo-toggle-item">
                        <input type="checkbox" id="export-all-settings" checked>
                        <span><?php esc_html_e('All Settings', 'almaseo-seo-playground'); ?></span>
                    </label>
                    <label class="almaseo-toggle-item">
                        <input type="checkbox" id="export-types-rules">
                        <span><?php esc_html_e('Types & Rules Only', 'almaseo-seo-playground'); ?></span>
                    </label>
                    <label class="almaseo-toggle-item">
                        <input type="checkbox" id="export-media-settings">
                        <span><?php esc_html_e('Media Settings Only', 'almaseo-seo-playground'); ?></span>
                    </label>
                    <label class="almaseo-toggle-item">
                        <input type="checkbox" id="export-additional-urls">
                        <span><?php esc_html_e('Additional URLs', 'almaseo-seo-playground'); ?></span>
                    </label>
                </div>
            </div>
            
            <div class="almaseo-button-group">
                <button type="button" class="button button-primary" id="export-settings-btn">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Export Settings', 'almaseo-seo-playground'); ?>
                </button>
                <button type="button" class="button almaseo-button-secondary" id="export-logs-btn">
                    <span class="dashicons dashicons-media-text"></span>
                    <?php esc_html_e('Export Logs', 'almaseo-seo-playground'); ?>
                </button>
            </div>
            
            <!-- Export Status -->
            <div id="export-status" style="display:none;">
                <p class="almaseo-text-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span id="export-message"><?php esc_html_e('Settings exported successfully!', 'almaseo-seo-playground'); ?></span>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Import Settings -->
    <div class="almaseo-card">
        <div class="almaseo-card-header">
            <h2><?php esc_html_e('Import Settings', 'almaseo-seo-playground'); ?></h2>
        </div>
        <div class="almaseo-card-body">
            <div class="almaseo-form-group">
                <p class="description"><?php esc_html_e('Import sitemap settings from a previously exported JSON file. This will overwrite your current settings.', 'almaseo-seo-playground'); ?></p>
                
                <div class="almaseo-import-area">
                    <input type="file" id="import-settings-file" accept=".json" style="display:none;">
                    <div class="almaseo-file-drop-zone" id="import-drop-zone">
                        <span class="dashicons dashicons-upload"></span>
                        <p><?php esc_html_e('Drop JSON file here or click to browse', 'almaseo-seo-playground'); ?></p>
                        <div class="almaseo-file-info" id="import-file-info" style="display:none;">
                            <strong id="import-file-name"></strong>
                            <small id="import-file-size"></small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="almaseo-import-options" id="import-options" style="display:none;">
                <label class="almaseo-toggle-item">
                    <input type="checkbox" id="import-merge-settings" checked>
                    <span><?php esc_html_e('Merge with existing settings', 'almaseo-seo-playground'); ?></span>
                    <small><?php esc_html_e('Preserve settings not included in the import file', 'almaseo-seo-playground'); ?></small>
                </label>
                <label class="almaseo-toggle-item">
                    <input type="checkbox" id="import-create-backup">
                    <span><?php esc_html_e('Create backup before import', 'almaseo-seo-playground'); ?></span>
                    <small><?php esc_html_e('Automatically download current settings as backup', 'almaseo-seo-playground'); ?></small>
                </label>
            </div>
            
            <div class="almaseo-button-group">
                <button type="button" class="button almaseo-button-secondary" id="import-settings-btn">
                    <span class="dashicons dashicons-upload"></span>
                    <?php esc_html_e('Choose File', 'almaseo-seo-playground'); ?>
                </button>
                <button type="button" class="button button-primary" id="import-confirm-btn" disabled>
                    <span class="dashicons dashicons-yes"></span>
                    <?php esc_html_e('Import Settings', 'almaseo-seo-playground'); ?>
                </button>
            </div>
            
            <!-- Import Status -->
            <div id="import-status" style="display:none;">
                <div class="almaseo-import-progress">
                    <div class="almaseo-progress-bar">
                        <div class="almaseo-progress-fill" style="width: 0%"></div>
                    </div>
                    <p id="import-progress-text"><?php esc_html_e('Importing settings...', 'almaseo-seo-playground'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Tools & Helpers -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php esc_html_e('Quick Tools & Helpers', 'almaseo-seo-playground'); ?></h2>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-tools-grid">
            <!-- Copy Sitemap URLs -->
            <div class="almaseo-tool-item">
                <h4><?php esc_html_e('Copy Sitemap URLs', 'almaseo-seo-playground'); ?></h4>
                <p class="description"><?php esc_html_e('Copy all enabled sitemap URLs to clipboard for easy submission to search engines.', 'almaseo-seo-playground'); ?></p>
                <button type="button" class="button almaseo-button-secondary" id="copy-all-urls-btn">
                    <span class="dashicons dashicons-clipboard"></span>
                    <?php esc_html_e('Copy All URLs', 'almaseo-seo-playground'); ?>
                </button>
                <div id="sitemap-urls-list" style="display:none; margin-top:10px;">
                    <textarea class="almaseo-input" rows="8" readonly></textarea>
                </div>
            </div>
            
            <!-- HTML Sitemap Shortcode -->
            <div class="almaseo-tool-item">
                <h4><?php esc_html_e('HTML Sitemap Shortcode', 'almaseo-seo-playground'); ?></h4>
                <p class="description"><?php esc_html_e('Generate shortcode for displaying HTML sitemaps on pages.', 'almaseo-seo-playground'); ?></p>
                
                <div class="almaseo-shortcode-builder">
                    <div class="almaseo-form-row">
                        <div class="almaseo-form-group">
                            <label><?php esc_html_e('Types:', 'almaseo-seo-playground'); ?></label>
                            <div class="almaseo-checkbox-group">
                                <label><input type="checkbox" value="posts" checked> <?php esc_html_e('Posts', 'almaseo-seo-playground'); ?></label>
                                <label><input type="checkbox" value="pages" checked> <?php esc_html_e('Pages', 'almaseo-seo-playground'); ?></label>
                                <label><input type="checkbox" value="tax"> <?php esc_html_e('Taxonomies', 'almaseo-seo-playground'); ?></label>
                            </div>
                        </div>
                        <div class="almaseo-form-group">
                            <label for="shortcode-columns"><?php esc_html_e('Columns:', 'almaseo-seo-playground'); ?></label>
                            <input type="number" id="shortcode-columns" min="1" max="4" value="2" class="almaseo-input almaseo-input-small">
                        </div>
                    </div>
                    
                    <div class="almaseo-shortcode-output">
                        <input type="text" id="generated-shortcode" value="[almaseo_html_sitemap types=&quot;posts,pages&quot; columns=&quot;2&quot;]" class="almaseo-input" readonly>
                        <button type="button" class="button almaseo-button-secondary" id="copy-shortcode-btn">
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php esc_html_e('Copy', 'almaseo-seo-playground'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="almaseo-shortcode-preview">
                    <button type="button" class="button" id="preview-shortcode-btn">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php esc_html_e('Preview HTML Sitemap', 'almaseo-seo-playground'); ?>
                    </button>
                </div>
            </div>
            
            <!-- robots.txt Helper -->
            <div class="almaseo-tool-item">
                <h4><?php esc_html_e('robots.txt Integration', 'almaseo-seo-playground'); ?></h4>
                <p class="description"><?php esc_html_e('Preview and manage how your sitemaps appear in robots.txt.', 'almaseo-seo-playground'); ?></p>
                <button type="button" class="button almaseo-button-secondary" id="preview-robots-btn">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php esc_html_e('Preview robots.txt', 'almaseo-seo-playground'); ?>
                </button>
                <div id="robots-preview" style="display:none; margin-top:10px;">
                    <pre class="almaseo-code-preview"></pre>
                    <div class="almaseo-button-group">
                        <button type="button" class="button" id="copy-robots-entries">
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php esc_html_e('Copy Sitemap Lines', 'almaseo-seo-playground'); ?>
                        </button>
                        <button type="button" class="button" id="download-robots">
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e('Download robots.txt', 'almaseo-seo-playground'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Bulk Operations -->
            <div class="almaseo-tool-item">
                <h4><?php esc_html_e('Bulk Operations', 'almaseo-seo-playground'); ?></h4>
                <p class="description"><?php esc_html_e('Perform operations on multiple sitemaps at once.', 'almaseo-seo-playground'); ?></p>
                <div class="almaseo-button-group">
                    <button type="button" class="button" id="validate-all-sitemaps">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php esc_html_e('Validate All', 'almaseo-seo-playground'); ?>
                    </button>
                    <button type="button" class="button" id="rebuild-all-sitemaps">
                        <span class="dashicons dashicons-update"></span>
                        <?php esc_html_e('Rebuild All', 'almaseo-seo-playground'); ?>
                    </button>
                    <button type="button" class="button" id="ping-all-search-engines">
                        <span class="dashicons dashicons-megaphone"></span>
                        <?php esc_html_e('Ping Search Engines', 'almaseo-seo-playground'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Information -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php esc_html_e('System Information', 'almaseo-seo-playground'); ?></h2>
        <button type="button" class="button almaseo-button-secondary" id="copy-system-info">
            <span class="dashicons dashicons-clipboard"></span>
            <?php esc_html_e('Copy for Support', 'almaseo-seo-playground'); ?>
        </button>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-system-info">
            <div class="almaseo-info-grid">
                <div class="almaseo-info-item">
                    <strong><?php esc_html_e('Plugin Version:', 'almaseo-seo-playground'); ?></strong>
                    <?php echo esc_html(ALMASEO_PLUGIN_VERSION); ?>
                </div>
                <div class="almaseo-info-item">
                    <strong><?php esc_html_e('WordPress Version:', 'almaseo-seo-playground'); ?></strong>
                    <?php echo esc_html(get_bloginfo('version')); ?>
                </div>
                <div class="almaseo-info-item">
                    <strong><?php esc_html_e('PHP Version:', 'almaseo-seo-playground'); ?></strong>
                    <?php echo esc_html(PHP_VERSION); ?>
                </div>
                <div class="almaseo-info-item">
                    <strong><?php esc_html_e('Memory Limit:', 'almaseo-seo-playground'); ?></strong>
                    <?php echo esc_html(ini_get('memory_limit')); ?>
                </div>
                <div class="almaseo-info-item">
                    <strong><?php esc_html_e('Max Execution Time:', 'almaseo-seo-playground'); ?></strong>
                    <?php echo esc_html(ini_get('max_execution_time')); ?>s
                </div>
                <div class="almaseo-info-item">
                    <strong><?php esc_html_e('Server:', 'almaseo-seo-playground'); ?></strong>
                    <?php echo esc_html(isset($_SERVER['SERVER_SOFTWARE']) ? $_SERVER['SERVER_SOFTWARE'] : 'Unknown'); ?>
                </div>
            </div>
            
            <!-- Active Plugins -->
            <details class="almaseo-system-details">
                <summary><?php esc_html_e('Active Plugins', 'almaseo-seo-playground'); ?></summary>
                <div class="almaseo-plugins-list">
                    <?php
                    $active_plugins = get_option('active_plugins', array());
                    foreach ($active_plugins as $plugin) {
                        $plugin_data = get_plugin_data(WP_PLUGIN_DIR . '/' . $plugin);
                        if (!empty($plugin_data['Name'])) {
                            echo '<div class="almaseo-plugin-item">';
                            echo '<strong>' . esc_html($plugin_data['Name']) . '</strong> ';
                            echo '<span class="version">' . esc_html($plugin_data['Version']) . '</span>';
                            echo '</div>';
                        }
                    }
                    ?>
                </div>
            </details>
            
            <!-- Environment Checks -->
            <details class="almaseo-system-details">
                <summary><?php esc_html_e('Environment Checks', 'almaseo-seo-playground'); ?></summary>
                <div class="almaseo-checks-list">
                    <?php
                    $checks = array(
                        'mod_rewrite' => function_exists('apache_get_modules') ? in_array('mod_rewrite', apache_get_modules()) : true,
                        'xml_support' => extension_loaded('xml'),
                        'curl_support' => extension_loaded('curl'),
                        'file_uploads' => ini_get('file_uploads'),
                        'allow_url_fopen' => ini_get('allow_url_fopen'),
                    );
                    
                    foreach ($checks as $check => $status) {
                        $label = ucwords(str_replace('_', ' ', $check));
                        echo '<div class="almaseo-check-item">';
                        echo '<span class="dashicons dashicons-' . ($status ? 'yes-alt almaseo-text-success' : 'warning almaseo-text-warning') . '"></span>';
                        echo '<strong>' . esc_html($label) . ':</strong> ';
                        echo $status ? esc_html__('Enabled', 'almaseo-seo-playground') : esc_html__('Disabled', 'almaseo-seo-playground');
                        echo '</div>';
                    }
                    ?>
                </div>
            </details>
        </div>
    </div>
</div>