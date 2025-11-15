<?php
/**
 * International Tab - Hreflang UI
 *
 * @package AlmaSEO
 * @since 4.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize hreflang settings if not set
if (!isset($settings['hreflang'])) {
    $settings['hreflang'] = array(
        'enabled' => false,
        'source' => 'auto',
        'default' => '',
        'x_default_url' => '',
        'mappings' => array()
    );
}

// Get hreflang stats
$hreflang = null;
$hreflang_stats = array(
    'plugin' => null,
    'languages_count' => 0,
    'languages' => array(),
    'validation' => array()
);

try {
    if (class_exists('Alma_Hreflang')) {
        $hreflang = new Alma_Hreflang($settings['hreflang']);
        $hreflang_stats = $hreflang->get_stats();
    }
} catch (Exception $e) {
    // Handle gracefully if hreflang class is not available
}

$validation = $hreflang_stats['validation'] ?? array();
?>

<!-- Hreflang Configuration -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('Hreflang Configuration', 'almaseo'); ?></h2>
        <div class="almaseo-chips">
            <span class="almaseo-chip <?php echo $settings['hreflang']['enabled'] ? 'almaseo-chip-success' : ''; ?>">
                <?php echo $settings['hreflang']['enabled'] ? __('Active', 'almaseo') : __('Inactive', 'almaseo'); ?>
            </span>
            <?php if ($hreflang_stats['plugin']): ?>
            <span class="almaseo-chip">
                <?php echo ucfirst($hreflang_stats['plugin']); ?>
            </span>
            <?php endif; ?>
            <span class="almaseo-chip">
                <?php echo sprintf(__('%d Locales', 'almaseo'), $hreflang_stats['languages_count']); ?>
            </span>
            <?php if (!empty($validation['validated_at'])): ?>
            <span class="almaseo-chip <?php echo $validation['ok'] ? 'almaseo-chip-success' : 'almaseo-chip-warning'; ?>">
                <?php echo $validation['ok'] ? __('Valid', 'almaseo') : sprintf(__('%d Issues', 'almaseo'), $validation['missing_pairs'] + $validation['orphans']); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-form-group">
            <label class="almaseo-toggle-item">
                <input type="checkbox" id="hreflang-enabled" <?php checked($settings['hreflang']['enabled']); ?>>
                <span><?php _e('Enable Hreflang', 'almaseo'); ?></span>
                <small><?php _e('Add language alternates to sitemaps', 'almaseo'); ?></small>
            </label>
        </div>
        
        <?php if (!$hreflang_stats['plugin']): ?>
        <div class="almaseo-info-box almaseo-info-warning">
            <p>
                <span class="dashicons dashicons-warning"></span>
                <?php _e('No multilingual plugin detected. Install WPML, Polylang, or configure manually.', 'almaseo'); ?>
            </p>
        </div>
        <?php endif; ?>
        
        <div class="almaseo-form-row">
            <div class="almaseo-form-group">
                <label><?php _e('Source:', 'almaseo'); ?></label>
                <select id="hreflang-source" class="almaseo-input">
                    <option value="auto" <?php selected($settings['hreflang']['source'], 'auto'); ?>>
                        <?php _e('Auto (WPML/Polylang)', 'almaseo'); ?>
                    </option>
                    <option value="manual" <?php selected($settings['hreflang']['source'], 'manual'); ?>>
                        <?php _e('Manual', 'almaseo'); ?>
                    </option>
                </select>
                <p class="description"><?php _e('Choose how to detect languages and locale mappings', 'almaseo'); ?></p>
            </div>
            <div class="almaseo-form-group">
                <label><?php _e('Default Locale:', 'almaseo'); ?></label>
                <select id="hreflang-default" class="almaseo-input">
                    <option value=""><?php _e('None', 'almaseo'); ?></option>
                    <?php foreach ($hreflang_stats['languages'] as $lang): ?>
                    <option value="<?php echo esc_attr($lang['hreflang']); ?>" <?php selected($settings['hreflang']['default'], $lang['hreflang']); ?>>
                        <?php echo esc_html($lang['name'] . ' (' . $lang['hreflang'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('The primary language for your site', 'almaseo'); ?></p>
            </div>
        </div>
        
        <?php if ($hreflang_stats['languages_count'] > 0): ?>
        <div class="almaseo-form-group">
            <label><?php _e('Locale Mappings:', 'almaseo'); ?></label>
            <p class="description"><?php _e('Map your site locales to proper hreflang codes (ISO 639-1 or ISO 639-1-ISO 3166-1)', 'almaseo'); ?></p>
            <div class="almaseo-table-wrapper">
                <table class="almaseo-table">
                    <thead>
                        <tr>
                            <th><?php _e('Detected Locale', 'almaseo'); ?></th>
                            <th><?php _e('Hreflang Code', 'almaseo'); ?></th>
                            <th><?php _e('Language', 'almaseo'); ?></th>
                            <th><?php _e('Valid', 'almaseo'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($hreflang_stats['languages'] as $lang): ?>
                        <tr>
                            <td><code><?php echo esc_html($lang['locale']); ?></code></td>
                            <td>
                                <input type="text" 
                                       class="hreflang-locale-map almaseo-input almaseo-input-small" 
                                       data-locale="<?php echo esc_attr($lang['locale']); ?>"
                                       value="<?php echo esc_attr($lang['hreflang']); ?>"
                                       placeholder="en-US">
                            </td>
                            <td><?php echo esc_html($lang['name']); ?></td>
                            <td>
                                <span class="almaseo-validation-icon" data-locale="<?php echo esc_attr($lang['locale']); ?>">
                                    <span class="dashicons dashicons-yes-alt almaseo-text-success" style="display: none;"></span>
                                    <span class="dashicons dashicons-warning almaseo-text-warning" style="display: none;"></span>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="almaseo-empty-state">
            <span class="dashicons dashicons-translation"></span>
            <p><?php _e('No languages detected', 'almaseo'); ?></p>
            <p class="description"><?php _e('Configure your multilingual plugin or add languages manually', 'almaseo'); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="almaseo-form-group">
            <label for="hreflang-x-default"><?php _e('x-default URL (optional):', 'almaseo'); ?></label>
            <input type="url" id="hreflang-x-default" 
                   value="<?php echo esc_url($settings['hreflang']['x_default_url']); ?>"
                   placeholder="https://example.com"
                   class="almaseo-input">
            <p class="description"><?php _e('Custom URL for x-default. Leave empty to use default locale URL.', 'almaseo'); ?></p>
        </div>
        
        <div class="almaseo-button-group">
            <button type="button" class="button button-primary" id="validate-hreflang">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Validate Hreflang', 'almaseo'); ?>
            </button>
            <?php if (isset($settings['perf']['storage_mode']) && $settings['perf']['storage_mode'] === 'static'): ?>
            <button type="button" class="button almaseo-button-secondary" id="rebuild-with-hreflang">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Rebuild Sitemaps', 'almaseo'); ?>
            </button>
            <?php endif; ?>
            <?php if (!empty($validation['missing_pairs']) || !empty($validation['orphans'])): ?>
            <button type="button" class="button" id="export-hreflang-issues">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export Issues CSV', 'almaseo'); ?>
            </button>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($validation)): ?>
        <div class="almaseo-info-box <?php echo $validation['ok'] ? 'almaseo-info-success' : 'almaseo-info-warning'; ?>">
            <p>
                <strong><?php _e('Last validation:', 'almaseo'); ?></strong> 
                <?php echo human_time_diff($validation['validated_at']); ?> <?php _e('ago', 'almaseo'); ?>
            </p>
            <?php if (!$validation['ok']): ?>
            <ul>
                <?php if ($validation['missing_pairs'] > 0): ?>
                <li><?php echo sprintf(__('%d missing language pairs', 'almaseo'), $validation['missing_pairs']); ?></li>
                <?php endif; ?>
                <?php if ($validation['orphans'] > 0): ?>
                <li><?php echo sprintf(__('%d orphan links', 'almaseo'), $validation['orphans']); ?></li>
                <?php endif; ?>
                <?php if ($validation['mismatch'] > 0): ?>
                <li><?php echo sprintf(__('%d invalid codes', 'almaseo'), $validation['mismatch']); ?></li>
                <?php endif; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Hreflang Best Practices -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('Best Practices', 'almaseo'); ?></h2>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-tips-grid">
            <div class="almaseo-tip">
                <h4><?php _e('Use Standard Codes', 'almaseo'); ?></h4>
                <p><?php _e('Use ISO 639-1 language codes (en, fr, de) or combine with ISO 3166-1 country codes (en-US, fr-CA)', 'almaseo'); ?></p>
            </div>
            <div class="almaseo-tip">
                <h4><?php _e('Bidirectional Links', 'almaseo'); ?></h4>
                <p><?php _e('Every page should link to all its language alternatives, including itself', 'almaseo'); ?></p>
            </div>
            <div class="almaseo-tip">
                <h4><?php _e('x-default Usage', 'almaseo'); ?></h4>
                <p><?php _e('Use x-default for language selection pages or when serving different content based on user location', 'almaseo'); ?></p>
            </div>
            <div class="almaseo-tip">
                <h4><?php _e('Content Matching', 'almaseo'); ?></h4>
                <p><?php _e('Only link pages with substantially the same content in different languages', 'almaseo'); ?></p>
            </div>
        </div>
    </div>
</div>