<?php
/**
 * Change Tab - Delta settings + IndexNow actions
 *
 * @package AlmaSEO
 * @since 4.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize delta settings if not set
if (!isset($settings['delta'])) {
    $settings['delta'] = array(
        'enabled' => false,
        'max_urls' => 500,
        'retention_days' => 30
    );
}

// Get delta stats
$delta_stats = array('count' => 0, 'max' => 500, 'oldest' => null);
$delta_health = $settings['health']['delta_submit'] ?? array();

// Try to load the Delta provider class if needed
$provider_file = dirname(dirname(dirname(__DIR__))) . '/providers/class-alma-provider-delta.php';
if (file_exists($provider_file) && !class_exists('Alma_Provider_Delta')) {
    require_once $provider_file;
}

try {
    if (class_exists('Alma_Provider_Delta') && method_exists('Alma_Provider_Delta', 'get_stats')) {
        $delta_stats = Alma_Provider_Delta::get_stats();
    }
} catch (Exception $e) {
    // Handle gracefully if delta class is not available
    error_log('Delta provider error: ' . $e->getMessage());
}
?>

<!-- Delta Sitemap -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('Delta Sitemap', 'almaseo'); ?></h2>
        <div class="almaseo-chips">
            <span class="almaseo-chip <?php echo $settings['delta']['enabled'] ? 'almaseo-chip-success' : ''; ?>">
                <?php echo $settings['delta']['enabled'] ? __('Active', 'almaseo') : __('Inactive', 'almaseo'); ?>
            </span>
            <span class="almaseo-chip">
                <?php echo sprintf(__('%d URLs', 'almaseo'), $delta_stats['count']); ?>
            </span>
            <?php if (!empty($delta_health['time'])): ?>
            <span class="almaseo-chip">
                <?php _e('Last ping:', 'almaseo'); ?> 
                <?php echo human_time_diff($delta_health['time']); ?> <?php _e('ago', 'almaseo'); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-form-group">
            <label class="almaseo-toggle-item">
                <input type="checkbox" id="delta-enabled" <?php checked($settings['delta']['enabled']); ?>>
                <span><?php _e('Enable Delta Sitemap', 'almaseo'); ?></span>
                <small><?php _e('Track recently changed URLs for faster indexing', 'almaseo'); ?></small>
            </label>
        </div>
        
        <div class="almaseo-info-box almaseo-info-default">
            <p>
                <span class="dashicons dashicons-info"></span>
                <?php _e('Delta sitemaps contain only recently modified URLs, making it easier for search engines to discover fresh content quickly.', 'almaseo'); ?>
            </p>
        </div>
        
        <div class="almaseo-form-row">
            <div class="almaseo-form-group">
                <label for="delta-max-urls"><?php _e('Max URLs:', 'almaseo'); ?></label>
                <input type="number" id="delta-max-urls" 
                       value="<?php echo esc_attr($settings['delta']['max_urls']); ?>"
                       min="50" max="2000" class="almaseo-input almaseo-input-small">
                <p class="description"><?php _e('Ring buffer size (50-2000)', 'almaseo'); ?></p>
            </div>
            <div class="almaseo-form-group">
                <label for="delta-retention"><?php _e('Retention Days:', 'almaseo'); ?></label>
                <input type="number" id="delta-retention" 
                       value="<?php echo esc_attr($settings['delta']['retention_days']); ?>"
                       min="1" max="90" class="almaseo-input almaseo-input-small">
                <p class="description"><?php _e('Keep URLs for (1-90 days)', 'almaseo'); ?></p>
            </div>
        </div>
        
        <div class="almaseo-sitemap-url">
            <label><?php _e('Delta Sitemap URL:', 'almaseo'); ?></label>
            <div class="almaseo-input-group">
                <input type="text" readonly value="<?php echo esc_url(home_url('/almaseo-sitemap-delta.xml')); ?>" class="almaseo-input" id="delta-url">
                <div class="almaseo-button-group">
                    <button type="button" class="button almaseo-button-secondary" id="open-delta">
                        <span class="dashicons dashicons-external"></span>
                        <?php _e('Open Delta', 'almaseo'); ?>
                    </button>
                    <button type="button" class="button almaseo-button-secondary" id="copy-delta-url">
                        <span class="dashicons dashicons-clipboard"></span>
                        <?php _e('Copy URL', 'almaseo'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="almaseo-button-group">
            <button type="button" class="button button-primary" id="force-ping" <?php echo empty($delta_stats['count']) ? 'disabled' : ''; ?>>
                <span class="dashicons dashicons-megaphone"></span>
                <?php _e('Force Ping', 'almaseo'); ?>
            </button>
            <button type="button" class="button" id="purge-old">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Purge Old', 'almaseo'); ?>
            </button>
        </div>
        
        <?php if ($delta_stats['count'] > 0): ?>
        <div class="almaseo-info-box">
            <p><strong><?php _e('Current Status:', 'almaseo'); ?></strong></p>
            <p><?php echo sprintf(__('Ring buffer: %d of %d URLs', 'almaseo'), $delta_stats['count'], $delta_stats['max']); ?></p>
            <?php if ($delta_stats['oldest']): ?>
            <p><?php echo sprintf(__('Oldest entry: %s', 'almaseo'), human_time_diff($delta_stats['oldest'])); ?> <?php _e('ago', 'almaseo'); ?></p>
            <?php endif; ?>
            <?php if (!empty($delta_health['success'])): ?>
            <p class="almaseo-text-success"><?php echo sprintf(__('Last ping: %d URLs submitted successfully', 'almaseo'), $delta_health['count']); ?></p>
            <?php elseif (isset($delta_health['success']) && !$delta_health['success']): ?>
            <p class="almaseo-text-danger"><?php _e('Last ping failed', 'almaseo'); ?></p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="almaseo-empty-state">
            <span class="dashicons dashicons-update-alt"></span>
            <p><?php _e('No changes tracked yet', 'almaseo'); ?></p>
            <p class="description"><?php _e('URLs will appear here as content is modified', 'almaseo'); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- IndexNow Integration -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('IndexNow Integration', 'almaseo'); ?></h2>
        <div class="almaseo-chips">
            <?php 
            $indexnow_enabled = get_option('almaseo_indexnow_enabled', false);
            $indexnow_key = get_option('almaseo_indexnow_key', '');
            ?>
            <span class="almaseo-chip <?php echo $indexnow_enabled ? 'almaseo-chip-success' : ''; ?>">
                <?php echo $indexnow_enabled ? __('Active', 'almaseo') : __('Inactive', 'almaseo'); ?>
            </span>
            <?php if ($indexnow_key): ?>
            <span class="almaseo-chip">
                <?php _e('Key Set', 'almaseo'); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-form-group">
            <label class="almaseo-toggle-item">
                <input type="checkbox" id="indexnow-enabled" <?php checked($indexnow_enabled); ?>>
                <span><?php _e('Enable IndexNow', 'almaseo'); ?></span>
                <small><?php _e('Automatically notify search engines when content changes', 'almaseo'); ?></small>
            </label>
        </div>
        
        <div class="almaseo-info-box almaseo-info-default">
            <p>
                <span class="dashicons dashicons-info"></span>
                <?php _e('IndexNow is a protocol that allows search engines to be notified when URLs are updated. Supported by Bing, Yandex, and others.', 'almaseo'); ?>
            </p>
        </div>
        
        <div class="almaseo-form-group">
            <label for="indexnow-key"><?php _e('IndexNow Key:', 'almaseo'); ?></label>
            <input type="text" id="indexnow-key" 
                   value="<?php echo esc_attr($indexnow_key); ?>"
                   class="almaseo-input"
                   placeholder="<?php _e('Auto-generated or custom key', 'almaseo'); ?>">
            <p class="description"><?php _e('Leave empty to auto-generate a key. Must be 8-128 characters.', 'almaseo'); ?></p>
        </div>
        
        <div class="almaseo-form-row">
            <div class="almaseo-form-group">
                <label for="indexnow-endpoint"><?php _e('Endpoint:', 'almaseo'); ?></label>
                <select id="indexnow-endpoint" class="almaseo-input">
                    <option value="bing" <?php selected(get_option('almaseo_indexnow_endpoint', 'bing'), 'bing'); ?>>
                        <?php _e('Bing (api.indexnow.org)', 'almaseo'); ?>
                    </option>
                    <option value="yandex" <?php selected(get_option('almaseo_indexnow_endpoint', 'bing'), 'yandex'); ?>>
                        <?php _e('Yandex (yandex.com)', 'almaseo'); ?>
                    </option>
                </select>
            </div>
            <div class="almaseo-form-group">
                <label for="indexnow-batch-size"><?php _e('Batch Size:', 'almaseo'); ?></label>
                <input type="number" id="indexnow-batch-size" 
                       value="<?php echo esc_attr(get_option('almaseo_indexnow_batch_size', 100)); ?>"
                       min="1" max="10000" class="almaseo-input almaseo-input-small">
                <p class="description"><?php _e('URLs per request (1-10000)', 'almaseo'); ?></p>
            </div>
        </div>
        
        <div class="almaseo-button-group">
            <button type="button" class="button button-primary" id="test-indexnow">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Test IndexNow', 'almaseo'); ?>
            </button>
            <button type="button" class="button" id="generate-indexnow-key">
                <span class="dashicons dashicons-admin-network"></span>
                <?php _e('Generate Key', 'almaseo'); ?>
            </button>
            <button type="button" class="button" id="ping-all-indexnow" <?php echo !$indexnow_enabled ? 'disabled' : ''; ?>>
                <span class="dashicons dashicons-megaphone"></span>
                <?php _e('Ping All URLs', 'almaseo'); ?>
            </button>
        </div>
        
        <?php if ($indexnow_key): ?>
        <div class="almaseo-info-box">
            <p><strong><?php _e('Key File URL:', 'almaseo'); ?></strong></p>
            <p>
                <code><?php echo esc_url(home_url('/' . $indexnow_key . '.txt')); ?></code>
                <button type="button" class="button-link" id="copy-key-url">
                    <span class="dashicons dashicons-clipboard"></span>
                </button>
            </p>
            <p class="description"><?php _e('This file must be accessible for IndexNow verification', 'almaseo'); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Change Tracking History -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('Change Tracking History', 'almaseo'); ?></h2>
        <div class="almaseo-button-group">
            <button type="button" class="button almaseo-button-secondary" id="export-delta-history">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export History', 'almaseo'); ?>
            </button>
        </div>
    </div>
    <div class="almaseo-card-body">
        <?php 
        $recent_changes = array();
        try {
            if (class_exists('Alma_Provider_Delta') && method_exists('Alma_Provider_Delta', 'get_recent_changes')) {
                $recent_changes = Alma_Provider_Delta::get_recent_changes(10);
            } else {
                // Method doesn't exist, use empty array
                $recent_changes = array();
            }
        } catch (Exception $e) {
            // Handle gracefully
            $recent_changes = array();
        }
        ?>
        
        <?php if (!empty($recent_changes)): ?>
        <div class="almaseo-changes-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('URL', 'almaseo'); ?></th>
                        <th><?php _e('Change Type', 'almaseo'); ?></th>
                        <th><?php _e('Modified', 'almaseo'); ?></th>
                        <th><?php _e('Status', 'almaseo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_changes as $change): ?>
                    <tr>
                        <td>
                            <a href="<?php echo esc_url($change['url']); ?>" target="_blank" class="almaseo-url-link">
                                <?php echo esc_html($change['url']); ?>
                                <span class="dashicons dashicons-external"></span>
                            </a>
                        </td>
                        <td>
                            <span class="almaseo-badge almaseo-badge-<?php echo esc_attr($change['type']); ?>">
                                <?php echo esc_html(ucfirst($change['type'])); ?>
                            </span>
                        </td>
                        <td><?php echo human_time_diff($change['modified']) . ' ' . __('ago', 'almaseo'); ?></td>
                        <td>
                            <?php if ($change['pinged']): ?>
                            <span class="almaseo-text-success">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php _e('Notified', 'almaseo'); ?>
                            </span>
                            <?php else: ?>
                            <span class="almaseo-text-muted">
                                <?php _e('Pending', 'almaseo'); ?>
                            </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="almaseo-empty-state">
            <span class="dashicons dashicons-clock"></span>
            <p><?php _e('No recent changes tracked', 'almaseo'); ?></p>
            <p class="description"><?php _e('Recent content modifications will appear here', 'almaseo'); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>