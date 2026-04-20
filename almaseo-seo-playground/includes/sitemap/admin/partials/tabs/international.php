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
        <h2><?php esc_html_e('Hreflang Configuration', 'almaseo-seo-playground'); ?></h2>
        <div class="almaseo-chips">
            <span class="almaseo-chip <?php echo esc_attr($settings['hreflang']['enabled'] ? 'almaseo-chip-success' : ''); ?>">
                <?php echo $settings['hreflang']['enabled'] ? esc_html__('Active', 'almaseo-seo-playground') : esc_html__('Inactive', 'almaseo-seo-playground'); ?>
            </span>
            <?php if ($hreflang_stats['plugin']): ?>
            <span class="almaseo-chip">
                <?php echo esc_html(ucfirst($hreflang_stats['plugin'])); ?>
            </span>
            <?php endif; ?>
            <span class="almaseo-chip">
                <?php
                /* translators: %d: number of configured locales */
                echo esc_html(sprintf(__('%d Locales', 'almaseo-seo-playground'), $hreflang_stats['languages_count'])); ?>
            </span>
            <?php if (!empty($validation['validated_at'])): ?>
            <span class="almaseo-chip <?php echo esc_attr($validation['ok'] ? 'almaseo-chip-success' : 'almaseo-chip-warning'); ?>">
                <?php
                /* translators: %d: number of validation issues found */
                echo $validation['ok'] ? esc_html__('Valid', 'almaseo-seo-playground') : esc_html(sprintf(__('%d Issues', 'almaseo-seo-playground'), $validation['missing_pairs'] + $validation['orphans'])); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-form-group">
            <label class="almaseo-toggle-item">
                <input type="checkbox" id="hreflang-enabled" <?php checked($settings['hreflang']['enabled']); ?>>
                <span><?php esc_html_e('Enable Hreflang', 'almaseo-seo-playground'); ?></span>
                <small><?php esc_html_e('Add language alternates to sitemaps', 'almaseo-seo-playground'); ?></small>
            </label>
        </div>
        
        <?php if (!$hreflang_stats['plugin']): ?>
        <div class="almaseo-info-box almaseo-info-warning">
            <p>
                <span class="dashicons dashicons-warning"></span>
                <?php esc_html_e('No multilingual plugin detected. Install WPML, Polylang, or configure manually.', 'almaseo-seo-playground'); ?>
            </p>
        </div>
        <?php endif; ?>
        
        <div class="almaseo-form-row">
            <div class="almaseo-form-group">
                <label><?php esc_html_e('Source:', 'almaseo-seo-playground'); ?></label>
                <select id="hreflang-source" class="almaseo-input">
                    <option value="auto" <?php selected($settings['hreflang']['source'], 'auto'); ?>>
                        <?php esc_html_e('Auto (WPML/Polylang)', 'almaseo-seo-playground'); ?>
                    </option>
                    <option value="manual" <?php selected($settings['hreflang']['source'], 'manual'); ?>>
                        <?php esc_html_e('Manual', 'almaseo-seo-playground'); ?>
                    </option>
                </select>
                <p class="description"><?php esc_html_e('Choose how to detect languages and locale mappings', 'almaseo-seo-playground'); ?></p>
            </div>
            <div class="almaseo-form-group">
                <label><?php esc_html_e('Default Locale:', 'almaseo-seo-playground'); ?></label>
                <select id="hreflang-default" class="almaseo-input">
                    <option value=""><?php esc_html_e('None', 'almaseo-seo-playground'); ?></option>
                    <?php foreach ($hreflang_stats['languages'] as $lang): ?>
                    <option value="<?php echo esc_attr($lang['hreflang']); ?>" <?php selected($settings['hreflang']['default'], $lang['hreflang']); ?>>
                        <?php echo esc_html($lang['name'] . ' (' . $lang['hreflang'] . ')'); ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php esc_html_e('The primary language for your site', 'almaseo-seo-playground'); ?></p>
            </div>
        </div>
        
        <?php if ($hreflang_stats['languages_count'] > 0): ?>
        <div class="almaseo-form-group">
            <label><?php esc_html_e('Locale Mappings:', 'almaseo-seo-playground'); ?></label>
            <p class="description"><?php esc_html_e('Map your site locales to proper hreflang codes (ISO 639-1 or ISO 639-1-ISO 3166-1)', 'almaseo-seo-playground'); ?></p>
            <div class="almaseo-table-wrapper">
                <table class="almaseo-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Detected Locale', 'almaseo-seo-playground'); ?></th>
                            <th><?php esc_html_e('Hreflang Code', 'almaseo-seo-playground'); ?></th>
                            <th><?php esc_html_e('Language', 'almaseo-seo-playground'); ?></th>
                            <th><?php esc_html_e('Valid', 'almaseo-seo-playground'); ?></th>
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
            <p><?php esc_html_e('No languages detected', 'almaseo-seo-playground'); ?></p>
            <p class="description"><?php esc_html_e('Configure your multilingual plugin or add languages manually', 'almaseo-seo-playground'); ?></p>
        </div>
        <?php endif; ?>
        
        <div class="almaseo-form-group">
            <label for="hreflang-x-default"><?php esc_html_e('x-default URL (optional):', 'almaseo-seo-playground'); ?></label>
            <input type="url" id="hreflang-x-default" 
                   value="<?php echo esc_url($settings['hreflang']['x_default_url']); ?>"
                   placeholder="https://example.com"
                   class="almaseo-input">
            <p class="description"><?php esc_html_e('Custom URL for x-default. Leave empty to use default locale URL.', 'almaseo-seo-playground'); ?></p>
        </div>
        
        <div class="almaseo-button-group">
            <button type="button" class="button button-primary" id="validate-hreflang">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e('Validate Hreflang', 'almaseo-seo-playground'); ?>
            </button>
            <?php if (isset($settings['perf']['storage_mode']) && $settings['perf']['storage_mode'] === 'static'): ?>
            <button type="button" class="button almaseo-button-secondary" id="rebuild-with-hreflang">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Rebuild Sitemaps', 'almaseo-seo-playground'); ?>
            </button>
            <?php endif; ?>
            <?php if (!empty($validation['missing_pairs']) || !empty($validation['orphans'])): ?>
            <button type="button" class="button" id="export-hreflang-issues">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Export Issues CSV', 'almaseo-seo-playground'); ?>
            </button>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($validation)): ?>
        <div class="almaseo-info-box <?php echo esc_attr($validation['ok'] ? 'almaseo-info-success' : 'almaseo-info-warning'); ?>">
            <p>
                <strong><?php esc_html_e('Last validation:', 'almaseo-seo-playground'); ?></strong>
                <?php echo esc_html(human_time_diff($validation['validated_at'])); ?> <?php esc_html_e('ago', 'almaseo-seo-playground'); ?>
            </p>
            <?php if (!$validation['ok']): ?>
            <ul>
                <?php if ($validation['missing_pairs'] > 0): ?>
                <li><?php
                /* translators: %d: number of missing language pairs */
                echo esc_html(sprintf(__('%d missing language pairs', 'almaseo-seo-playground'), $validation['missing_pairs'])); ?></li>
                <?php endif; ?>
                <?php if ($validation['orphans'] > 0): ?>
                <li><?php
                /* translators: %d: number of orphan links */
                echo esc_html(sprintf(__('%d orphan links', 'almaseo-seo-playground'), $validation['orphans'])); ?></li>
                <?php endif; ?>
                <?php if ($validation['mismatch'] > 0): ?>
                <li><?php
                /* translators: %d: number of invalid language codes */
                echo esc_html(sprintf(__('%d invalid codes', 'almaseo-seo-playground'), $validation['mismatch'])); ?></li>
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
        <h2><?php esc_html_e('Best Practices', 'almaseo-seo-playground'); ?></h2>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-tips-grid">
            <div class="almaseo-tip">
                <h4><?php esc_html_e('Use Standard Codes', 'almaseo-seo-playground'); ?></h4>
                <p><?php esc_html_e('Use ISO 639-1 language codes (en, fr, de) or combine with ISO 3166-1 country codes (en-US, fr-CA)', 'almaseo-seo-playground'); ?></p>
            </div>
            <div class="almaseo-tip">
                <h4><?php esc_html_e('Bidirectional Links', 'almaseo-seo-playground'); ?></h4>
                <p><?php esc_html_e('Every page should link to all its language alternatives, including itself', 'almaseo-seo-playground'); ?></p>
            </div>
            <div class="almaseo-tip">
                <h4><?php esc_html_e('x-default Usage', 'almaseo-seo-playground'); ?></h4>
                <p><?php esc_html_e('Use x-default for language selection pages or when serving different content based on user location', 'almaseo-seo-playground'); ?></p>
            </div>
            <div class="almaseo-tip">
                <h4><?php esc_html_e('Content Matching', 'almaseo-seo-playground'); ?></h4>
                <p><?php esc_html_e('Only link pages with substantially the same content in different languages', 'almaseo-seo-playground'); ?></p>
            </div>
        </div>
    </div>
</div>