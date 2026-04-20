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
        <h2><?php esc_html_e('Delta Sitemap', 'almaseo-seo-playground'); ?></h2>
        <div class="almaseo-chips">
            <span class="almaseo-chip <?php echo esc_attr($settings['delta']['enabled'] ? 'almaseo-chip-success' : ''); ?>">
                <?php echo $settings['delta']['enabled'] ? esc_html__('Active', 'almaseo-seo-playground') : esc_html__('Inactive', 'almaseo-seo-playground'); ?>
            </span>
            <span class="almaseo-chip">
                <?php
                /* translators: %d: number of URLs in the delta sitemap */
                echo esc_html(sprintf(__('%d URLs', 'almaseo-seo-playground'), $delta_stats['count'])); ?>
            </span>
            <?php if (!empty($delta_health['time'])): ?>
            <span class="almaseo-chip">
                <?php esc_html_e('Last ping:', 'almaseo-seo-playground'); ?> 
                <?php echo esc_html(human_time_diff($delta_health['time'])); ?> <?php esc_html_e('ago', 'almaseo-seo-playground'); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-form-group">
            <label class="almaseo-toggle-item">
                <input type="checkbox" id="delta-enabled" <?php checked($settings['delta']['enabled']); ?>>
                <span><?php esc_html_e('Enable Delta Sitemap', 'almaseo-seo-playground'); ?></span>
                <small><?php esc_html_e('Track recently changed URLs for faster indexing', 'almaseo-seo-playground'); ?></small>
            </label>
        </div>
        
        <div class="almaseo-info-box almaseo-info-default">
            <p>
                <span class="dashicons dashicons-info"></span>
                <?php esc_html_e('Delta sitemaps contain only recently modified URLs, making it easier for search engines to discover fresh content quickly.', 'almaseo-seo-playground'); ?>
            </p>
        </div>
        
        <div class="almaseo-form-row">
            <div class="almaseo-form-group">
                <label for="delta-max-urls"><?php esc_html_e('Max URLs:', 'almaseo-seo-playground'); ?></label>
                <input type="number" id="delta-max-urls" 
                       value="<?php echo esc_attr($settings['delta']['max_urls']); ?>"
                       min="50" max="2000" class="almaseo-input almaseo-input-small">
                <p class="description"><?php esc_html_e('Ring buffer size (50-2000)', 'almaseo-seo-playground'); ?></p>
            </div>
            <div class="almaseo-form-group">
                <label for="delta-retention"><?php esc_html_e('Retention Days:', 'almaseo-seo-playground'); ?></label>
                <input type="number" id="delta-retention" 
                       value="<?php echo esc_attr($settings['delta']['retention_days']); ?>"
                       min="1" max="90" class="almaseo-input almaseo-input-small">
                <p class="description"><?php esc_html_e('Keep URLs for (1-90 days)', 'almaseo-seo-playground'); ?></p>
            </div>
        </div>
        
        <div class="almaseo-sitemap-url">
            <label><?php esc_html_e('Delta Sitemap URL:', 'almaseo-seo-playground'); ?></label>
            <div class="almaseo-input-group">
                <input type="text" readonly value="<?php echo esc_url(home_url('/almaseo-sitemap-delta.xml')); ?>" class="almaseo-input" id="delta-url">
                <div class="almaseo-button-group">
                    <button type="button" class="button almaseo-button-secondary" id="open-delta">
                        <span class="dashicons dashicons-external"></span>
                        <?php esc_html_e('Open Delta', 'almaseo-seo-playground'); ?>
                    </button>
                    <button type="button" class="button almaseo-button-secondary" id="copy-delta-url">
                        <span class="dashicons dashicons-clipboard"></span>
                        <?php esc_html_e('Copy URL', 'almaseo-seo-playground'); ?>
                    </button>
                </div>
            </div>
        </div>
        
        <div class="almaseo-button-group">
            <button type="button" class="button button-primary" id="force-ping" <?php echo empty($delta_stats['count']) ? 'disabled' : ''; ?>>
                <span class="dashicons dashicons-megaphone"></span>
                <?php esc_html_e('Force Ping', 'almaseo-seo-playground'); ?>
            </button>
            <button type="button" class="button" id="purge-old">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Purge Old', 'almaseo-seo-playground'); ?>
            </button>
        </div>
        
        <?php if ($delta_stats['count'] > 0): ?>
        <div class="almaseo-info-box">
            <p><strong><?php esc_html_e('Current Status:', 'almaseo-seo-playground'); ?></strong></p>
            <p><?php
            /* translators: %1$d: current number of URLs in the buffer, %2$d: maximum buffer capacity */
            echo esc_html(sprintf(__('Ring buffer: %1$d of %2$d URLs', 'almaseo-seo-playground'), $delta_stats['count'], $delta_stats['max'])); ?></p>
            <?php if ($delta_stats['oldest']): ?>
            <p><?php
            /* translators: %s: human-readable time difference */
            echo esc_html(sprintf(__('Oldest entry: %s', 'almaseo-seo-playground'), human_time_diff($delta_stats['oldest']))); ?> <?php esc_html_e('ago', 'almaseo-seo-playground'); ?></p>
            <?php endif; ?>
            <?php if (!empty($delta_health['success'])): ?>
            <p class="almaseo-text-success"><?php
            /* translators: %d: number of URLs submitted in the last ping */
            echo esc_html(sprintf(__('Last ping: %d URLs submitted successfully', 'almaseo-seo-playground'), $delta_health['count'])); ?></p>
            <?php elseif (isset($delta_health['success']) && !$delta_health['success']): ?>
            <p class="almaseo-text-danger"><?php esc_html_e('Last ping failed', 'almaseo-seo-playground'); ?></p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="almaseo-empty-state">
            <span class="dashicons dashicons-update-alt"></span>
            <p><?php esc_html_e('No changes tracked yet', 'almaseo-seo-playground'); ?></p>
            <p class="description"><?php esc_html_e('URLs will appear here as content is modified', 'almaseo-seo-playground'); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- IndexNow Integration -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php esc_html_e('IndexNow Integration', 'almaseo-seo-playground'); ?></h2>
        <div class="almaseo-chips">
            <?php 
            $indexnow_enabled = get_option('almaseo_indexnow_enabled', false);
            $indexnow_key = get_option('almaseo_indexnow_key', '');
            ?>
            <span class="almaseo-chip <?php echo esc_attr($indexnow_enabled ? 'almaseo-chip-success' : ''); ?>">
                <?php echo $indexnow_enabled ? esc_html__('Active', 'almaseo-seo-playground') : esc_html__('Inactive', 'almaseo-seo-playground'); ?>
            </span>
            <?php if ($indexnow_key): ?>
            <span class="almaseo-chip">
                <?php esc_html_e('Key Set', 'almaseo-seo-playground'); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-form-group">
            <label class="almaseo-toggle-item">
                <input type="checkbox" id="indexnow-enabled" <?php checked($indexnow_enabled); ?>>
                <span><?php esc_html_e('Enable IndexNow', 'almaseo-seo-playground'); ?></span>
                <small><?php esc_html_e('Automatically notify search engines when content changes', 'almaseo-seo-playground'); ?></small>
            </label>
        </div>
        
        <div class="almaseo-info-box almaseo-info-default">
            <p>
                <span class="dashicons dashicons-info"></span>
                <?php esc_html_e('IndexNow is a protocol that allows search engines to be notified when URLs are updated. Supported by Bing, Yandex, and others.', 'almaseo-seo-playground'); ?>
            </p>
        </div>
        
        <div class="almaseo-form-group">
            <label for="indexnow-key"><?php esc_html_e('IndexNow Key:', 'almaseo-seo-playground'); ?></label>
            <input type="text" id="indexnow-key" 
                   value="<?php echo esc_attr($indexnow_key); ?>"
                   class="almaseo-input"
                   placeholder="<?php esc_html_e('Auto-generated or custom key', 'almaseo-seo-playground'); ?>">
            <p class="description"><?php esc_html_e('Leave empty to auto-generate a key. Must be 8-128 characters.', 'almaseo-seo-playground'); ?></p>
        </div>
        
        <div class="almaseo-form-row">
            <div class="almaseo-form-group">
                <label for="indexnow-endpoint"><?php esc_html_e('Endpoint:', 'almaseo-seo-playground'); ?></label>
                <select id="indexnow-endpoint" class="almaseo-input">
                    <option value="bing" <?php selected(get_option('almaseo_indexnow_endpoint', 'bing'), 'bing'); ?>>
                        <?php esc_html_e('Bing (api.indexnow.org)', 'almaseo-seo-playground'); ?>
                    </option>
                    <option value="yandex" <?php selected(get_option('almaseo_indexnow_endpoint', 'bing'), 'yandex'); ?>>
                        <?php esc_html_e('Yandex (yandex.com)', 'almaseo-seo-playground'); ?>
                    </option>
                </select>
            </div>
            <div class="almaseo-form-group">
                <label for="indexnow-batch-size"><?php esc_html_e('Batch Size:', 'almaseo-seo-playground'); ?></label>
                <input type="number" id="indexnow-batch-size" 
                       value="<?php echo esc_attr(get_option('almaseo_indexnow_batch_size', 100)); ?>"
                       min="1" max="10000" class="almaseo-input almaseo-input-small">
                <p class="description"><?php esc_html_e('URLs per request (1-10000)', 'almaseo-seo-playground'); ?></p>
            </div>
        </div>
        
        <div class="almaseo-button-group">
            <button type="button" class="button button-primary" id="test-indexnow">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e('Test IndexNow', 'almaseo-seo-playground'); ?>
            </button>
            <button type="button" class="button" id="generate-indexnow-key">
                <span class="dashicons dashicons-admin-network"></span>
                <?php esc_html_e('Generate Key', 'almaseo-seo-playground'); ?>
            </button>
            <button type="button" class="button" id="ping-all-indexnow" <?php echo !$indexnow_enabled ? 'disabled' : ''; ?>>
                <span class="dashicons dashicons-megaphone"></span>
                <?php esc_html_e('Ping All URLs', 'almaseo-seo-playground'); ?>
            </button>
        </div>
        
        <?php if ($indexnow_key): ?>
        <div class="almaseo-info-box">
            <p><strong><?php esc_html_e('Key File URL:', 'almaseo-seo-playground'); ?></strong></p>
            <p>
                <code><?php echo esc_url(home_url('/' . $indexnow_key . '.txt')); ?></code>
                <button type="button" class="button-link" id="copy-key-url">
                    <span class="dashicons dashicons-clipboard"></span>
                </button>
            </p>
            <p class="description"><?php esc_html_e('This file must be accessible for IndexNow verification', 'almaseo-seo-playground'); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Change Tracking History -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php esc_html_e('Change Tracking History', 'almaseo-seo-playground'); ?></h2>
        <div class="almaseo-button-group">
            <button type="button" class="button almaseo-button-secondary" id="export-delta-history">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Export History', 'almaseo-seo-playground'); ?>
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
                        <th><?php esc_html_e('URL', 'almaseo-seo-playground'); ?></th>
                        <th><?php esc_html_e('Change Type', 'almaseo-seo-playground'); ?></th>
                        <th><?php esc_html_e('Modified', 'almaseo-seo-playground'); ?></th>
                        <th><?php esc_html_e('Status', 'almaseo-seo-playground'); ?></th>
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
                        <td><?php echo esc_html(human_time_diff($change['modified']) . ' ' . __('ago', 'almaseo-seo-playground')); ?></td>
                        <td>
                            <?php if ($change['pinged']): ?>
                            <span class="almaseo-text-success">
                                <span class="dashicons dashicons-yes-alt"></span>
                                <?php esc_html_e('Notified', 'almaseo-seo-playground'); ?>
                            </span>
                            <?php else: ?>
                            <span class="almaseo-text-muted">
                                <?php esc_html_e('Pending', 'almaseo-seo-playground'); ?>
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
            <p><?php esc_html_e('No recent changes tracked', 'almaseo-seo-playground'); ?></p>
            <p class="description"><?php esc_html_e('Recent content modifications will appear here', 'almaseo-seo-playground'); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>