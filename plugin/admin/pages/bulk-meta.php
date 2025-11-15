<?php
/**
 * AlmaSEO Bulk Metadata Editor Admin Page
 * 
 * @package AlmaSEO
 * @since 6.3.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get post types
$post_types = get_post_types(array('public' => true), 'objects');
?>

<div class="wrap almaseo-bulk-meta">
    <h1>
        <?php echo esc_html__('Bulk Metadata Editor', 'almaseo'); ?>
    </h1>
    
    <p class="description">
        <?php echo esc_html__('Edit SEO titles and meta descriptions for multiple posts at once. Click on any field to edit inline.', 'almaseo'); ?>
    </p>
    
    <!-- Error display area -->
    <div id="almaseo-bulkmeta-error" class="notice notice-error" style="display:none;">
        <p></p>
    </div>
    
    <!-- Filters -->
    <div class="tablenav top">
        <div id="almaseo-filters">
            <!-- Post Type Filter -->
            <select id="post-type-filter" class="postform">
                <?php foreach ($post_types as $type) : ?>
                    <?php if ($type->name === 'attachment') continue; ?>
                    <option value="<?php echo esc_attr($type->name); ?>" <?php selected($type->name, 'post'); ?>>
                        <?php echo esc_html($type->labels->name); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <!-- Status Filter -->
            <select id="status-filter" class="postform">
                <option value=""><?php echo esc_html__('All Statuses', 'almaseo'); ?></option>
                <option value="publish"><?php echo esc_html__('Published', 'almaseo'); ?></option>
                <option value="draft"><?php echo esc_html__('Draft', 'almaseo'); ?></option>
                <option value="publish,draft" selected><?php echo esc_html__('Published & Draft', 'almaseo'); ?></option>
                <option value="pending"><?php echo esc_html__('Pending', 'almaseo'); ?></option>
                <option value="private"><?php echo esc_html__('Private', 'almaseo'); ?></option>
            </select>
            
            <!-- Taxonomy Filter -->
            <select id="taxonomy-filter" class="postform">
                <option value=""><?php echo esc_html__('All Categories', 'almaseo'); ?></option>
            </select>
            
            <!-- Term Filter -->
            <select id="term-filter" class="postform" style="display:none;">
                <option value=""><?php echo esc_html__('Select Term', 'almaseo'); ?></option>
            </select>
            
            <!-- Missing Only Toggle -->
            <label class="missing-toggle">
                <input type="checkbox" id="missing-only">
                <?php echo esc_html__('Missing metadata', 'almaseo'); ?>
            </label>
            
            <!-- Date Range -->
            <input type="date" id="date-from" placeholder="<?php echo esc_attr__('From', 'almaseo'); ?>" title="<?php echo esc_attr__('Date From', 'almaseo'); ?>">
            <input type="date" id="date-to" placeholder="<?php echo esc_attr__('To', 'almaseo'); ?>" title="<?php echo esc_attr__('Date To', 'almaseo'); ?>">
            
            <!-- Search -->
            <input type="search" id="search-box" placeholder="<?php echo esc_attr__('Search posts...', 'almaseo'); ?>">
            
            <button type="button" class="button" id="search-button">
                <?php echo esc_html__('Search', 'almaseo'); ?>
            </button>
            
            <button type="button" class="button button-primary" id="apply-filters">
                <?php echo esc_html__('Apply Filters', 'almaseo'); ?>
            </button>
        </div>
    </div>
    
    <!-- Clear float to prevent overlap -->
    <div style="clear: both; height: 20px;"></div>
    
    <!-- Bulk Actions -->
    <div class="bulk-actions-wrapper" style="display:none;">
        <div class="bulk-actions">
            <select id="bulk-action">
                <option value=""><?php echo esc_html__('Bulk Actions', 'almaseo'); ?></option>
                <option value="reset"><?php echo esc_html__('Reset Selected', 'almaseo'); ?></option>
                <option value="append"><?php echo esc_html__('Append to Titles', 'almaseo'); ?></option>
                <option value="prepend"><?php echo esc_html__('Prepend to Titles', 'almaseo'); ?></option>
                <option value="replace"><?php echo esc_html__('Find & Replace', 'almaseo'); ?></option>
            </select>
            
            <!-- Bulk Action Options -->
            <div id="bulk-options" style="display:none;">
                <select id="bulk-field">
                    <option value="title"><?php echo esc_html__('SEO Title', 'almaseo'); ?></option>
                    <option value="description"><?php echo esc_html__('Meta Description', 'almaseo'); ?></option>
                </select>
                
                <input type="text" id="bulk-text" placeholder="<?php echo esc_attr__('Text to add', 'almaseo'); ?>" style="display:none;">
                <input type="text" id="bulk-find" placeholder="<?php echo esc_attr__('Find', 'almaseo'); ?>" style="display:none;">
                <input type="text" id="bulk-replace" placeholder="<?php echo esc_attr__('Replace with', 'almaseo'); ?>" style="display:none;">
                
                <span class="bulk-help">
                    <?php echo esc_html__('Available placeholders: {site}, {category}, {year}', 'almaseo'); ?>
                </span>
            </div>
            
            <button type="button" class="button button-primary" id="apply-bulk">
                <?php echo esc_html__('Apply', 'almaseo'); ?>
            </button>
            
            <span class="selected-count">
                <span id="selected-count">0</span> <?php echo esc_html__('selected', 'almaseo'); ?>
            </span>
        </div>
    </div>
    
    <!-- Posts Table with Responsive Wrapper -->
    <div class="almaseo-bulkmeta-wrapper">
        <table class="wp-list-table widefat fixed striped posts" id="bulkmeta-table">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="select-all">
                    </td>
                    <th class="manage-column column-title column-primary">
                        <?php echo esc_html__('Post Title', 'almaseo'); ?>
                    </th>
                    <th class="manage-column column-type">
                        <?php echo esc_html__('Type', 'almaseo'); ?>
                    </th>
                    <th class="manage-column column-status">
                        <?php echo esc_html__('Status', 'almaseo'); ?>
                    </th>
                    <th class="manage-column column-seo-title">
                        <?php echo esc_html__('SEO Title', 'almaseo'); ?>
                        <span class="dashicons dashicons-info" title="<?php echo esc_attr__('Recommended: ~65 characters', 'almaseo'); ?>"></span>
                    </th>
                    <th class="manage-column column-meta-description">
                        <?php echo esc_html__('Meta Description', 'almaseo'); ?>
                        <span class="dashicons dashicons-info" title="<?php echo esc_attr__('Recommended: ~160 characters', 'almaseo'); ?>"></span>
                    </th>
                    <th class="manage-column column-actions">
                        <?php echo esc_html__('Actions', 'almaseo'); ?>
                    </th>
                </tr>
            </thead>
            <tbody id="bulkmeta-table-body">
                <tr class="no-items">
                    <td colspan="7" class="colspanchange">
                        <?php echo esc_html__('Loading posts...', 'almaseo'); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num">
                <span id="total-items">0</span> <?php echo esc_html__('items', 'almaseo'); ?>
            </span>
            <span class="pagination-links" id="pagination-links">
                <!-- Pagination will be inserted here -->
            </span>
        </div>
    </div>
    
    <!-- Toast Notifications Container -->
    <div id="toast-container"></div>
    
    <!-- Loading Overlay -->
    <div id="loading-overlay">
        <div class="spinner"></div>
    </div>
    
    <!-- Backup overlay element for compatibility -->
    <div id="almaseo-bulkmeta-overlay">
        <div class="spinner"></div>
    </div>
    
    <!-- Pixel Width Tester Canvas (hidden) -->
    <canvas id="pixel-tester" style="display:none;"></canvas>
</div>

<!-- Post Row Template -->
<script type="text/html" id="post-row-template">
    <tr data-id="{{id}}">
        <th scope="row" class="check-column">
            <input type="checkbox" class="post-checkbox" value="{{id}}">
        </th>
        <td class="column-title">
            <strong>
                <a href="{{edit_link}}" target="_blank">{{title}}</a>
            </strong>
            <div class="row-actions">
                <span class="view">
                    <a href="{{view_link}}" target="_blank"><?php echo esc_html__('View', 'almaseo'); ?></a>
                </span>
            </div>
        </td>
        <td class="column-type">
            {{type_label}}
        </td>
        <td class="column-seo-title">
            <div class="editable-cell" data-field="meta_title">
                <div class="cell-value">
                    <span class="value-text">{{meta_title_display}}</span>
                    <button class="edit-button dashicons dashicons-edit" title="<?php echo esc_attr__('Edit', 'almaseo'); ?>"></button>
                </div>
                <div class="cell-editor" style="display:none;">
                    <input type="text" class="meta-title-input" value="{{meta_title}}" 
                           placeholder="{{title_fallback}}" maxlength="200">
                    <div class="field-counter">
                        <span class="char-count">0</span> <?php echo esc_html__('chars', 'almaseo'); ?> | 
                        <span class="pixel-count">0</span> <?php echo esc_html__('px', 'almaseo'); ?>
                    </div>
                </div>
            </div>
        </td>
        <td class="column-meta-description">
            <div class="editable-cell" data-field="meta_description">
                <div class="cell-value">
                    <span class="value-text">{{meta_desc_display}}</span>
                    <button class="edit-button dashicons dashicons-edit" title="<?php echo esc_attr__('Edit', 'almaseo'); ?>"></button>
                </div>
                <div class="cell-editor" style="display:none;">
                    <textarea class="meta-description-input" rows="3" maxlength="500"
                              placeholder="{{desc_fallback}}">{{meta_description}}</textarea>
                    <div class="field-counter">
                        <span class="char-count">0</span> <?php echo esc_html__('chars', 'almaseo'); ?> | 
                        <span class="pixel-count">0</span> <?php echo esc_html__('px', 'almaseo'); ?>
                    </div>
                </div>
            </div>
        </td>
        <td class="column-status">
            <span class="post-status status-{{status}}">{{status}}</span>
        </td>
        <td class="column-updated">
            <span title="{{updated}}">{{updated_relative}}</span>
        </td>
        <td class="column-actions">
            <button class="button button-small open-editor" data-link="{{edit_link}}">
                <?php echo esc_html__('Edit', 'almaseo'); ?>
            </button>
            <button class="button button-small reset-meta" data-id="{{id}}">
                <?php echo esc_html__('Reset', 'almaseo'); ?>
            </button>
        </td>
    </tr>
</script>