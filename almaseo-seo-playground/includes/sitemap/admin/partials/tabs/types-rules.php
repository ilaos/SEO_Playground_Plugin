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
        <h2><?php _e('Sitemap Types', 'almaseo-seo-playground'); ?></h2>
        <div class="almaseo-master-toggle">
            <label class="almaseo-switch">
                <input type="checkbox" id="master-enable" <?php checked($settings['enabled']); ?>>
                <span class="almaseo-switch-slider"></span>
            </label>
            <span><?php _e('Enable Sitemaps', 'almaseo-seo-playground'); ?></span>
        </div>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-sitemap-types">
            <div class="almaseo-toggle-group">
                <label class="almaseo-toggle-item">
                    <input type="checkbox" class="sitemap-type" data-type="posts" 
                           <?php checked($settings['include']['posts']); ?>>
                    <span><?php _e('Posts', 'almaseo-seo-playground'); ?></span>
                </label>
                <label class="almaseo-toggle-item">
                    <input type="checkbox" class="sitemap-type" data-type="pages"
                           <?php checked($settings['include']['pages']); ?>>
                    <span><?php _e('Pages', 'almaseo-seo-playground'); ?></span>
                </label>
                <label class="almaseo-toggle-item">
                    <input type="checkbox" class="sitemap-type" data-type="cpts"
                           <?php checked($settings['include']['cpts'] === 'all'); ?>>
                    <span><?php _e('Custom Post Types', 'almaseo-seo-playground'); ?></span>
                    <small><?php _e('(All public)', 'almaseo-seo-playground'); ?></small>
                </label>
                <label class="almaseo-toggle-item">
                    <input type="checkbox" class="sitemap-type" data-type="category"
                           data-taxonomy="true"
                           <?php checked($settings['include']['tax']['category']); ?>>
                    <span><?php _e('Categories', 'almaseo-seo-playground'); ?></span>
                </label>
                <label class="almaseo-toggle-item">
                    <input type="checkbox" class="sitemap-type" data-type="post_tag"
                           data-taxonomy="true"
                           <?php checked($settings['include']['tax']['post_tag']); ?>>
                    <span><?php _e('Tags', 'almaseo-seo-playground'); ?></span>
                </label>
                <label class="almaseo-toggle-item">
                    <input type="checkbox" class="sitemap-type" data-type="users"
                           <?php checked($settings['include']['users']); ?>>
                    <span><?php _e('Author Archives', 'almaseo-seo-playground'); ?></span>
                    <small><?php _e('Include author/user archive pages', 'almaseo-seo-playground'); ?></small>
                </label>
            </div>
        </div>
    </div>
</div>

<!-- Rules -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('Sitemap Rules', 'almaseo-seo-playground'); ?></h2>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-form-group">
            <label for="links-per-sitemap"><?php _e('Links per sitemap:', 'almaseo-seo-playground'); ?></label>
            <input type="number" id="links-per-sitemap" 
                   value="<?php echo esc_attr($settings['links_per_sitemap']); ?>"
                   min="1" max="50000" class="almaseo-input almaseo-input-small">
            <p class="description"><?php _e('Number of URLs per sitemap file (1-50000, default: 1000)', 'almaseo-seo-playground'); ?></p>
        </div>
        
        <!-- Performance Settings -->
        <div class="almaseo-form-group">
            <h3><?php _e('Performance', 'almaseo-seo-playground'); ?></h3>
            <div class="almaseo-toggle-group">
                <label class="almaseo-toggle-item">
                    <input type="radio" name="storage_mode" value="static" 
                           <?php checked($settings['perf']['storage_mode'], 'static'); ?>>
                    <span><?php _e('Static Mode', 'almaseo-seo-playground'); ?></span>
                    <small><?php _e('Pre-generate files for best performance', 'almaseo-seo-playground'); ?></small>
                </label>
                <label class="almaseo-toggle-item">
                    <input type="radio" name="storage_mode" value="dynamic" 
                           <?php checked($settings['perf']['storage_mode'], 'dynamic'); ?>>
                    <span><?php _e('Dynamic Mode', 'almaseo-seo-playground'); ?></span>
                    <small><?php _e('Generate on-demand', 'almaseo-seo-playground'); ?></small>
                </label>
            </div>
            <label class="almaseo-toggle-item" style="margin-top: 10px;">
                <input type="checkbox" id="enable-gzip" 
                       <?php checked($settings['perf']['gzip']); ?>>
                <span><?php _e('Enable Gzip Compression', 'almaseo-seo-playground'); ?></span>
                <small><?php _e('Reduce bandwidth usage', 'almaseo-seo-playground'); ?></small>
            </label>
        </div>
        
        <div class="almaseo-advanced-rules-section">
            <h3><?php _e('Advanced Exclusion Rules', 'almaseo-seo-playground'); ?></h3>
            <p class="description" style="margin-bottom: 15px;">
                <?php _e('Exclude specific content from your sitemaps based on taxonomy, author, or age.', 'almaseo-seo-playground'); ?>
            </p>

            <?php
            // Get current exclusion settings
            $exclude = $settings['exclude'] ?? array();
            $excluded_terms = $exclude['taxonomies'] ?? array();
            $excluded_authors = $exclude['authors'] ?? array();
            $older_than_years = $exclude['older_than_years'] ?? 0;

            // Get all categories and tags for the dropdown
            $categories = get_terms(array(
                'taxonomy' => 'category',
                'hide_empty' => false,
            ));
            $tags = get_terms(array(
                'taxonomy' => 'post_tag',
                'hide_empty' => false,
            ));

            // Get authors with published posts
            $authors = get_users(array(
                'who' => 'authors',
                'has_published_posts' => true,
                'orderby' => 'display_name',
            ));
            ?>

            <div class="almaseo-form-group">
                <label for="exclude-taxonomies"><?php _e('Exclude by Category/Tag', 'almaseo-seo-playground'); ?></label>
                <select id="exclude-taxonomies" class="almaseo-input almaseo-select-multiple" multiple="multiple" style="min-height: 120px; width: 100%;">
                    <?php if (!empty($categories) && !is_wp_error($categories)) : ?>
                        <optgroup label="<?php esc_attr_e('Categories', 'almaseo-seo-playground'); ?>">
                            <?php foreach ($categories as $cat) : ?>
                                <option value="<?php echo esc_attr($cat->term_id); ?>" <?php selected(in_array($cat->term_id, $excluded_terms)); ?>>
                                    <?php echo esc_html($cat->name); ?> (<?php echo esc_html($cat->count); ?>)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                    <?php if (!empty($tags) && !is_wp_error($tags)) : ?>
                        <optgroup label="<?php esc_attr_e('Tags', 'almaseo-seo-playground'); ?>">
                            <?php foreach ($tags as $tag) : ?>
                                <option value="<?php echo esc_attr($tag->term_id); ?>" <?php selected(in_array($tag->term_id, $excluded_terms)); ?>>
                                    <?php echo esc_html($tag->name); ?> (<?php echo esc_html($tag->count); ?>)
                                </option>
                            <?php endforeach; ?>
                        </optgroup>
                    <?php endif; ?>
                </select>
                <p class="description"><?php _e('Posts in selected categories/tags will be excluded from the sitemap. Hold Ctrl/Cmd to select multiple.', 'almaseo-seo-playground'); ?></p>
            </div>

            <div class="almaseo-form-group">
                <label for="exclude-authors"><?php _e('Exclude by Author', 'almaseo-seo-playground'); ?></label>
                <select id="exclude-authors" class="almaseo-input almaseo-select-multiple" multiple="multiple" style="min-height: 80px; width: 100%;">
                    <?php foreach ($authors as $author) : ?>
                        <option value="<?php echo esc_attr($author->ID); ?>" <?php selected(in_array($author->ID, $excluded_authors)); ?>>
                            <?php echo esc_html($author->display_name); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="description"><?php _e('Posts by selected authors will be excluded from the sitemap.', 'almaseo-seo-playground'); ?></p>
            </div>

            <div class="almaseo-form-group">
                <label for="exclude-older-than"><?php _e('Exclude Posts Older Than', 'almaseo-seo-playground'); ?></label>
                <select id="exclude-older-than" class="almaseo-input" style="width: auto;">
                    <option value="0" <?php selected($older_than_years, 0); ?>><?php _e('No limit (include all)', 'almaseo-seo-playground'); ?></option>
                    <option value="1" <?php selected($older_than_years, 1); ?>><?php _e('1 year', 'almaseo-seo-playground'); ?></option>
                    <option value="2" <?php selected($older_than_years, 2); ?>><?php _e('2 years', 'almaseo-seo-playground'); ?></option>
                    <option value="3" <?php selected($older_than_years, 3); ?>><?php _e('3 years', 'almaseo-seo-playground'); ?></option>
                    <option value="5" <?php selected($older_than_years, 5); ?>><?php _e('5 years', 'almaseo-seo-playground'); ?></option>
                    <option value="10" <?php selected($older_than_years, 10); ?>><?php _e('10 years', 'almaseo-seo-playground'); ?></option>
                </select>
                <p class="description"><?php _e('Older posts may be less relevant. Excluding them can reduce sitemap size.', 'almaseo-seo-playground'); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Additional URLs -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('Additional URLs', 'almaseo-seo-playground'); ?></h2>
        <div class="almaseo-chips">
            <span class="almaseo-chip">
                <?php
                /* translators: %d: number of additional URLs */
                echo sprintf(__('%d URLs', 'almaseo-seo-playground'), $additional_count); ?>
            </span>
        </div>
    </div>
    <div class="almaseo-card-body">
        <?php if ($additional_count > 0) : ?>
            <div class="almaseo-stat-grid">
                <div class="almaseo-stat">
                    <div class="almaseo-stat-value"><?php echo number_format($additional_count); ?></div>
                    <div class="almaseo-stat-label"><?php _e('Active URLs', 'almaseo-seo-playground'); ?></div>
                </div>
            </div>
            <div id="additional-urls-list" class="almaseo-urls-list">
                <!-- List populated via JS -->
                <div class="almaseo-loading">
                    <span class="dashicons dashicons-update spin"></span>
                    <?php _e('Loading URLs...', 'almaseo-seo-playground'); ?>
                </div>
            </div>
        <?php else : ?>
            <div class="almaseo-empty-state">
                <span class="dashicons dashicons-admin-links"></span>
                <p><?php _e('No additional URLs added yet', 'almaseo-seo-playground'); ?></p>
                <p class="description"><?php _e('Add custom URLs to include in your sitemaps', 'almaseo-seo-playground'); ?></p>
            </div>
        <?php endif; ?>
        
        <div class="almaseo-button-group">
            <button type="button" class="button button-primary" id="add-url-btn">
                <span class="dashicons dashicons-plus"></span>
                <?php _e('Add URL', 'almaseo-seo-playground'); ?>
            </button>
            <button type="button" class="button" id="import-csv-btn">
                <span class="dashicons dashicons-upload"></span>
                <?php _e('Import CSV', 'almaseo-seo-playground'); ?>
            </button>
            <?php if ($additional_count > 0) : ?>
            <button type="button" class="button" id="export-csv-btn">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export CSV', 'almaseo-seo-playground'); ?>
            </button>
            <button type="button" class="button" id="clear-all-urls-btn">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Clear All', 'almaseo-seo-playground'); ?>
            </button>
            <?php endif; ?>
        </div>
        
        <?php if ($additional_count > 0) : ?>
        <div class="almaseo-urls-table">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('URL', 'almaseo-seo-playground'); ?></th>
                        <th><?php _e('Priority', 'almaseo-seo-playground'); ?></th>
                        <th><?php _e('Change Frequency', 'almaseo-seo-playground'); ?></th>
                        <th><?php _e('Added', 'almaseo-seo-playground'); ?></th>
                        <th><?php _e('Actions', 'almaseo-seo-playground'); ?></th>
                    </tr>
                </thead>
                <tbody id="additional-urls-table-body">
                    <!-- Rows populated via JS -->
                    <tr>
                        <td colspan="5" class="almaseo-loading">
                            <span class="dashicons dashicons-update spin"></span>
                            <?php _e('Loading additional URLs...', 'almaseo-seo-playground'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>