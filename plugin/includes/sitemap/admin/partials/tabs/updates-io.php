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
        <h2><?php _e('Auto-Updates', 'almaseo'); ?></h2>
        <div class="almaseo-chips">
            <span class="almaseo-chip">
                <?php _e('Channel:', 'almaseo'); ?> 
                <strong><?php echo ucfirst($current_channel); ?></strong>
            </span>
            <?php if ($last_found && version_compare($last_found, ALMASEO_PLUGIN_VERSION, '>')): ?>
            <span class="almaseo-chip almaseo-chip-warning">
                <?php _e('Update Available', 'almaseo'); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-form-group">
            <label for="update-channel"><?php _e('Update Channel:', 'almaseo'); ?></label>
            <select id="update-channel" class="almaseo-select">
                <option value="stable" <?php selected($current_channel, 'stable'); ?>><?php _e('Stable', 'almaseo'); ?></option>
                <option value="beta" <?php selected($current_channel, 'beta'); ?>><?php _e('Beta', 'almaseo'); ?></option>
            </select>
            <p class="description"><?php _e('Choose between stable releases or beta versions with latest features', 'almaseo'); ?></p>
        </div>
        
        <div class="almaseo-update-info">
            <div class="almaseo-stat-grid">
                <div class="almaseo-stat">
                    <div class="almaseo-stat-value"><?php echo ALMASEO_PLUGIN_VERSION; ?></div>
                    <div class="almaseo-stat-label"><?php _e('Current Version', 'almaseo'); ?></div>
                </div>
                <?php if ($last_found): ?>
                <div class="almaseo-stat">
                    <div class="almaseo-stat-value <?php echo version_compare($last_found, ALMASEO_PLUGIN_VERSION, '>') ? 'almaseo-text-warning' : 'almaseo-text-success'; ?>">
                        <?php echo esc_html($last_found); ?>
                    </div>
                    <div class="almaseo-stat-label"><?php _e('Latest Version', 'almaseo'); ?></div>
                </div>
                <?php endif; ?>
                <?php if ($last_check > 0): ?>
                <div class="almaseo-stat">
                    <div class="almaseo-stat-value"><?php echo human_time_diff($last_check); ?></div>
                    <div class="almaseo-stat-label"><?php _e('Last Check', 'almaseo'); ?></div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="almaseo-button-group">
            <button type="button" class="button button-primary" id="check-updates-btn">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Check for Updates Now', 'almaseo'); ?>
            </button>
            <?php if ($last_found && version_compare($last_found, ALMASEO_PLUGIN_VERSION, '>')): ?>
            <button type="button" class="button button-secondary" id="install-update-btn">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Install Update', 'almaseo'); ?>
            </button>
            <?php endif; ?>
        </div>
        
        <!-- Update Status -->
        <div id="update-status" style="display:none;">
            <div class="almaseo-progress-bar">
                <div class="almaseo-progress-fill" style="width: 0%"></div>
            </div>
            <p id="update-status-text"><?php _e('Checking for updates...', 'almaseo'); ?></p>
        </div>
        
        <!-- Auto-Update Settings -->
        <div class="almaseo-form-section">
            <h3><?php _e('Automatic Updates', 'almaseo'); ?></h3>
            <div class="almaseo-form-group">
                <label class="almaseo-toggle-item">
                    <input type="checkbox" id="auto-updates-enabled" 
                           <?php checked(get_option('almaseo_auto_updates_enabled', false)); ?>>
                    <span><?php _e('Enable Automatic Updates', 'almaseo'); ?></span>
                    <small><?php _e('Automatically install updates when available', 'almaseo'); ?></small>
                </label>
            </div>
            <div class="almaseo-form-group">
                <label class="almaseo-toggle-item">
                    <input type="checkbox" id="auto-updates-beta" 
                           <?php checked(get_option('almaseo_auto_updates_beta', false)); ?>>
                    <span><?php _e('Include Beta Versions', 'almaseo'); ?></span>
                    <small><?php _e('Also auto-update to beta releases (not recommended for production)', 'almaseo'); ?></small>
                </label>
            </div>
        </div>
    </div>
</div>

<div class="almaseo-two-column">
    <!-- Export Settings -->
    <div class="almaseo-card">
        <div class="almaseo-card-header">
            <h2><?php _e('Export Settings', 'almaseo'); ?></h2>
        </div>
        <div class="almaseo-card-body">
            <div class="almaseo-form-group">
                <p class="description"><?php _e('Download your current sitemap settings as a JSON file for backup or migration purposes.', 'almaseo'); ?></p>
                
                <div class="almaseo-export-options">
                    <label class="almaseo-toggle-item">
                        <input type="checkbox" id="export-all-settings" checked>
                        <span><?php _e('All Settings', 'almaseo'); ?></span>
                    </label>
                    <label class="almaseo-toggle-item">
                        <input type="checkbox" id="export-types-rules">
                        <span><?php _e('Types & Rules Only', 'almaseo'); ?></span>
                    </label>
                    <label class="almaseo-toggle-item">
                        <input type="checkbox" id="export-media-settings">
                        <span><?php _e('Media Settings Only', 'almaseo'); ?></span>
                    </label>
                    <label class="almaseo-toggle-item">
                        <input type="checkbox" id="export-additional-urls">
                        <span><?php _e('Additional URLs', 'almaseo'); ?></span>
                    </label>
                </div>
            </div>
            
            <div class="almaseo-button-group">
                <button type="button" class="button button-primary" id="export-settings-btn">
                    <span class="dashicons dashicons-download"></span>
                    <?php _e('Export Settings', 'almaseo'); ?>
                </button>
                <button type="button" class="button almaseo-button-secondary" id="export-logs-btn">
                    <span class="dashicons dashicons-media-text"></span>
                    <?php _e('Export Logs', 'almaseo'); ?>
                </button>
            </div>
            
            <!-- Export Status -->
            <div id="export-status" style="display:none;">
                <p class="almaseo-text-success">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span id="export-message"><?php _e('Settings exported successfully!', 'almaseo'); ?></span>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Import Settings -->
    <div class="almaseo-card">
        <div class="almaseo-card-header">
            <h2><?php _e('Import Settings', 'almaseo'); ?></h2>
        </div>
        <div class="almaseo-card-body">
            <div class="almaseo-form-group">
                <p class="description"><?php _e('Import sitemap settings from a previously exported JSON file. This will overwrite your current settings.', 'almaseo'); ?></p>
                
                <div class="almaseo-import-area">
                    <input type="file" id="import-settings-file" accept=".json" style="display:none;">
                    <div class="almaseo-file-drop-zone" id="import-drop-zone">
                        <span class="dashicons dashicons-upload"></span>
                        <p><?php _e('Drop JSON file here or click to browse', 'almaseo'); ?></p>
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
                    <span><?php _e('Merge with existing settings', 'almaseo'); ?></span>
                    <small><?php _e('Preserve settings not included in the import file', 'almaseo'); ?></small>
                </label>
                <label class="almaseo-toggle-item">
                    <input type="checkbox" id="import-create-backup">
                    <span><?php _e('Create backup before import', 'almaseo'); ?></span>
                    <small><?php _e('Automatically download current settings as backup', 'almaseo'); ?></small>
                </label>
            </div>
            
            <div class="almaseo-button-group">
                <button type="button" class="button almaseo-button-secondary" id="import-settings-btn">
                    <span class="dashicons dashicons-upload"></span>
                    <?php _e('Choose File', 'almaseo'); ?>
                </button>
                <button type="button" class="button button-primary" id="import-confirm-btn" disabled>
                    <span class="dashicons dashicons-yes"></span>
                    <?php _e('Import Settings', 'almaseo'); ?>
                </button>
            </div>
            
            <!-- Import Status -->
            <div id="import-status" style="display:none;">
                <div class="almaseo-import-progress">
                    <div class="almaseo-progress-bar">
                        <div class="almaseo-progress-fill" style="width: 0%"></div>
                    </div>
                    <p id="import-progress-text"><?php _e('Importing settings...', 'almaseo'); ?></p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Tools & Helpers -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('Quick Tools & Helpers', 'almaseo'); ?></h2>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-tools-grid">
            <!-- Copy Sitemap URLs -->
            <div class="almaseo-tool-item">
                <h4><?php _e('Copy Sitemap URLs', 'almaseo'); ?></h4>
                <p class="description"><?php _e('Copy all enabled sitemap URLs to clipboard for easy submission to search engines.', 'almaseo'); ?></p>
                <button type="button" class="button almaseo-button-secondary" id="copy-all-urls-btn">
                    <span class="dashicons dashicons-clipboard"></span>
                    <?php _e('Copy All URLs', 'almaseo'); ?>
                </button>
                <div id="sitemap-urls-list" style="display:none; margin-top:10px;">
                    <textarea class="almaseo-input" rows="8" readonly></textarea>
                </div>
            </div>
            
            <!-- HTML Sitemap Shortcode -->
            <div class="almaseo-tool-item">
                <h4><?php _e('HTML Sitemap Shortcode', 'almaseo'); ?></h4>
                <p class="description"><?php _e('Generate shortcode for displaying HTML sitemaps on pages.', 'almaseo'); ?></p>
                
                <div class="almaseo-shortcode-builder">
                    <div class="almaseo-form-row">
                        <div class="almaseo-form-group">
                            <label><?php _e('Types:', 'almaseo'); ?></label>
                            <div class="almaseo-checkbox-group">
                                <label><input type="checkbox" value="posts" checked> <?php _e('Posts', 'almaseo'); ?></label>
                                <label><input type="checkbox" value="pages" checked> <?php _e('Pages', 'almaseo'); ?></label>
                                <label><input type="checkbox" value="tax"> <?php _e('Taxonomies', 'almaseo'); ?></label>
                            </div>
                        </div>
                        <div class="almaseo-form-group">
                            <label for="shortcode-columns"><?php _e('Columns:', 'almaseo'); ?></label>
                            <input type="number" id="shortcode-columns" min="1" max="4" value="2" class="almaseo-input almaseo-input-small">
                        </div>
                    </div>
                    
                    <div class="almaseo-shortcode-output">
                        <input type="text" id="generated-shortcode" value="[almaseo_html_sitemap types=&quot;posts,pages&quot; columns=&quot;2&quot;]" class="almaseo-input" readonly>
                        <button type="button" class="button almaseo-button-secondary" id="copy-shortcode-btn">
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php _e('Copy', 'almaseo'); ?>
                        </button>
                    </div>
                </div>
                
                <div class="almaseo-shortcode-preview">
                    <button type="button" class="button" id="preview-shortcode-btn">
                        <span class="dashicons dashicons-visibility"></span>
                        <?php _e('Preview HTML Sitemap', 'almaseo'); ?>
                    </button>
                </div>
            </div>
            
            <!-- robots.txt Helper -->
            <div class="almaseo-tool-item">
                <h4><?php _e('robots.txt Integration', 'almaseo'); ?></h4>
                <p class="description"><?php _e('Preview and manage how your sitemaps appear in robots.txt.', 'almaseo'); ?></p>
                <button type="button" class="button almaseo-button-secondary" id="preview-robots-btn">
                    <span class="dashicons dashicons-visibility"></span>
                    <?php _e('Preview robots.txt', 'almaseo'); ?>
                </button>
                <div id="robots-preview" style="display:none; margin-top:10px;">
                    <pre class="almaseo-code-preview"></pre>
                    <div class="almaseo-button-group">
                        <button type="button" class="button" id="copy-robots-entries">
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php _e('Copy Sitemap Lines', 'almaseo'); ?>
                        </button>
                        <button type="button" class="button" id="download-robots">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Download robots.txt', 'almaseo'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <!-- Bulk Operations -->
            <div class="almaseo-tool-item">
                <h4><?php _e('Bulk Operations', 'almaseo'); ?></h4>
                <p class="description"><?php _e('Perform operations on multiple sitemaps at once.', 'almaseo'); ?></p>
                <div class="almaseo-button-group">
                    <button type="button" class="button" id="validate-all-sitemaps">
                        <span class="dashicons dashicons-yes-alt"></span>
                        <?php _e('Validate All', 'almaseo'); ?>
                    </button>
                    <button type="button" class="button" id="rebuild-all-sitemaps">
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Rebuild All', 'almaseo'); ?>
                    </button>
                    <button type="button" class="button" id="ping-all-search-engines">
                        <span class="dashicons dashicons-megaphone"></span>
                        <?php _e('Ping Search Engines', 'almaseo'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- System Information -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('System Information', 'almaseo'); ?></h2>
        <button type="button" class="button almaseo-button-secondary" id="copy-system-info">
            <span class="dashicons dashicons-clipboard"></span>
            <?php _e('Copy for Support', 'almaseo'); ?>
        </button>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-system-info">
            <div class="almaseo-info-grid">
                <div class="almaseo-info-item">
                    <strong><?php _e('Plugin Version:', 'almaseo'); ?></strong>
                    <?php echo ALMASEO_PLUGIN_VERSION; ?>
                </div>
                <div class="almaseo-info-item">
                    <strong><?php _e('WordPress Version:', 'almaseo'); ?></strong>
                    <?php echo get_bloginfo('version'); ?>
                </div>
                <div class="almaseo-info-item">
                    <strong><?php _e('PHP Version:', 'almaseo'); ?></strong>
                    <?php echo PHP_VERSION; ?>
                </div>
                <div class="almaseo-info-item">
                    <strong><?php _e('Memory Limit:', 'almaseo'); ?></strong>
                    <?php echo ini_get('memory_limit'); ?>
                </div>
                <div class="almaseo-info-item">
                    <strong><?php _e('Max Execution Time:', 'almaseo'); ?></strong>
                    <?php echo ini_get('max_execution_time'); ?>s
                </div>
                <div class="almaseo-info-item">
                    <strong><?php _e('Server:', 'almaseo'); ?></strong>
                    <?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'; ?>
                </div>
            </div>
            
            <!-- Active Plugins -->
            <details class="almaseo-system-details">
                <summary><?php _e('Active Plugins', 'almaseo'); ?></summary>
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
                <summary><?php _e('Environment Checks', 'almaseo'); ?></summary>
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
                        echo '<strong>' . $label . ':</strong> ';
                        echo $status ? __('Enabled', 'almaseo') : __('Disabled', 'almaseo');
                        echo '</div>';
                    }
                    ?>
                </div>
            </details>
        </div>
    </div>
</div>