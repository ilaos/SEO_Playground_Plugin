<?php
/**
 * Types & Rules Tab - "Sitemap Types", "Sitemap Rules", "Performance", "Additional URLs" table
 *
 * @package AlmaSEO
 * @since 4.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get additional URLs count
$additional_count = 0;
if (class_exists('Alma_Additional_URLs_Storage')) {
    $additional_count = Alma_Additional_URLs_Storage::get_count();
}
?>

<!-- Sitemap Types -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('Sitemap Types', 'almaseo'); ?></h2>
        <div class="almaseo-master-toggle">
            <label class="almaseo-switch">
                <input type="checkbox" id="master-enable" <?php checked($settings['enabled']); ?>>
                <span class="almaseo-switch-slider"></span>
            </label>
            <span><?php _e('Enable Sitemaps', 'almaseo'); ?></span>
        </div>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-sitemap-types">
            <div class="almaseo-toggle-group">
                <label class="almaseo-toggle-item">
                    <input type="checkbox" class="sitemap-type" data-type="posts" 
                           <?php checked($settings['include']['posts']); ?>>
                    <span><?php _e('Posts', 'almaseo'); ?></span>
                </label>
                <label class="almaseo-toggle-item">
                    <input type="checkbox" class="sitemap-type" data-type="pages"
                           <?php checked($settings['include']['pages']); ?>>
                    <span><?php _e('Pages', 'almaseo'); ?></span>
                </label>
                <label class="almaseo-toggle-item">
                    <input type="checkbox" class="sitemap-type" data-type="cpts"
                           <?php checked($settings['include']['cpts'] === 'all'); ?>>
                    <span><?php _e('Custom Post Types', 'almaseo'); ?></span>
                    <small><?php _e('(All public)', 'almaseo'); ?></small>
                </label>
                <label class="almaseo-toggle-item">
                    <input type="checkbox" class="sitemap-type" data-type="category"
                           data-taxonomy="true"
                           <?php checked($settings['include']['tax']['category']); ?>>
                    <span><?php _e('Categories', 'almaseo'); ?></span>
                </label>
                <label class="almaseo-toggle-item">
                    <input type="checkbox" class="sitemap-type" data-type="post_tag"
                           data-taxonomy="true"
                           <?php checked($settings['include']['tax']['post_tag']); ?>>
                    <span><?php _e('Tags', 'almaseo'); ?></span>
                </label>
                <label class="almaseo-toggle-item">
                    <input type="checkbox" class="sitemap-type" data-type="users"
                           <?php checked($settings['include']['users']); ?>>
                    <span><?php _e('Users', 'almaseo'); ?></span>
                    <small><?php _e('(Authors)', 'almaseo'); ?></small>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- Rules -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('Sitemap Rules', 'almaseo'); ?></h2>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-form-group">
            <label for="links-per-sitemap"><?php _e('Links per sitemap:', 'almaseo'); ?></label>
            <input type="number" id="links-per-sitemap" 
                   value="<?php echo esc_attr($settings['links_per_sitemap']); ?>"
                   min="1" max="50000" class="almaseo-input almaseo-input-small">
            <p class="description"><?php _e('Number of URLs per sitemap file (1-50000, default: 1000)', 'almaseo'); ?></p>
        </div>
        
        <!-- Performance Settings -->
        <div class="almaseo-form-group">
            <h3><?php _e('Performance', 'almaseo'); ?></h3>
            <div class="almaseo-toggle-group">
                <label class="almaseo-toggle-item">
                    <input type="radio" name="storage_mode" value="static" 
                           <?php checked($settings['perf']['storage_mode'], 'static'); ?>>
                    <span><?php _e('Static Mode', 'almaseo'); ?></span>
                    <small><?php _e('Pre-generate files for best performance', 'almaseo'); ?></small>
                </label>
                <label class="almaseo-toggle-item">
                    <input type="radio" name="storage_mode" value="dynamic" 
                           <?php checked($settings['perf']['storage_mode'], 'dynamic'); ?>>
                    <span><?php _e('Dynamic Mode', 'almaseo'); ?></span>
                    <small><?php _e('Generate on-demand', 'almaseo'); ?></small>
                </label>
            </div>
            <label class="almaseo-toggle-item" style="margin-top: 10px;">
                <input type="checkbox" id="enable-gzip" 
                       <?php checked($settings['perf']['gzip']); ?>>
                <span><?php _e('Enable Gzip Compression', 'almaseo'); ?></span>
                <small><?php _e('Reduce bandwidth usage', 'almaseo'); ?></small>
            </label>
        </div>
        
        <div class="almaseo-disabled-section">
            <h3><?php _e('Advanced Rules', 'almaseo'); ?></h3>
            <div class="almaseo-placeholder-controls">
                <div class="almaseo-form-group disabled">
                    <label><?php _e('Include/Exclude by Taxonomy', 'almaseo'); ?></label>
                    <select disabled class="almaseo-input">
                        <option><?php _e('Select taxonomies...', 'almaseo'); ?></option>
                    </select>
                </div>
                <div class="almaseo-form-group disabled">
                    <label><?php _e('Include/Exclude by Author', 'almaseo'); ?></label>
                    <select disabled class="almaseo-input">
                        <option><?php _e('Select authors...', 'almaseo'); ?></option>
                    </select>
                </div>
                <div class="almaseo-form-group disabled">
                    <label><?php _e('Include/Exclude by Date', 'almaseo'); ?></label>
                    <input type="text" disabled class="almaseo-input" placeholder="<?php _e('Date range...', 'almaseo'); ?>">
                </div>
            </div>
            <p class="almaseo-helper-text">
                <span class="dashicons dashicons-info"></span>
                <?php _e('Coming in Phase 3', 'almaseo'); ?>
            </p>
        </div>
    </div>
</div>

<!-- Additional URLs -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('Additional URLs', 'almaseo'); ?></h2>
        <div class="almaseo-chips">
            <span class="almaseo-chip">
                <?php echo sprintf(__('%d URLs', 'almaseo'), $additional_count); ?>
            </span>
        </div>
    </div>
    <div class="almaseo-card-body">
        <?php if ($additional_count > 0) : ?>
            <div class="almaseo-stat-grid">
                <div class="almaseo-stat">
                    <div class="almaseo-stat-value"><?php echo number_format($additional_count); ?></div>
                    <div class="almaseo-stat-label"><?php _e('Active URLs', 'almaseo'); ?></div>
                </div>
            </div>
            <div id="additional-urls-list" class="almaseo-urls-list">
                <!-- List populated via JS -->
                <div class="almaseo-loading">
                    <span class="dashicons dashicons-update spin"></span>
                    <?php _e('Loading URLs...', 'almaseo'); ?>
                </div>
            </div>
        <?php else : ?>
            <div class="almaseo-empty-state">
                <span class="dashicons dashicons-admin-links"></span>
                <p><?php _e('No additional URLs added yet', 'almaseo'); ?></p>
                <p class="description"><?php _e('Add custom URLs to include in your sitemaps', 'almaseo'); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="almaseo-button-group">
            <button type="button" class="button button-primary" id="add-url-btn">
                <span class="dashicons dashicons-plus"></span>
                <?php _e('Add URL', 'almaseo'); ?>
            </button>
            <button type="button" class="button" id="import-csv-btn">
                <span class="dashicons dashicons-upload"></span>
                <?php _e('Import CSV', 'almaseo'); ?>
            </button>
            <?php if ($additional_count > 0) : ?>
            <button type="button" class="button" id="export-csv-btn">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export CSV', 'almaseo'); ?>
            </button>
            <button type="button" class="button" id="clear-all-urls-btn">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Clear All', 'almaseo'); ?>
            </button>
            <?php endif; ?>
        </div>
        
        <?php if ($additional_count > 0) : ?>
        <div class="almaseo-urls-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('URL', 'almaseo'); ?></th>
                        <th><?php _e('Priority', 'almaseo'); ?></th>
                        <th><?php _e('Change Frequency', 'almaseo'); ?></th>
                        <th><?php _e('Added', 'almaseo'); ?></th>
                        <th><?php _e('Actions', 'almaseo'); ?></th>
                    </tr>
                </thead>
                <tbody id="additional-urls-table-body">
                    <!-- Rows populated via JS -->
                    <tr>
                        <td colspan="5" class="almaseo-loading">
                            <span class="dashicons dashicons-update spin"></span>
                            <?php _e('Loading additional URLs...', 'almaseo'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>