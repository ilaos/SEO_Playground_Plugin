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

// Check AI availability
$ai_autofill_available = false;
$autofill_ai_file = dirname(__DIR__, 2) . '/includes/bulkmeta/ai-autofill-generator.php';
if ( file_exists( $autofill_ai_file ) ) {
    require_once $autofill_ai_file;
    $ai_autofill_available = \AlmaSEO\BulkMeta\AI_Autofill_Generator::is_available();
}
?>
<script>var almaseoAutofillAi = <?php echo esc_js($ai_autofill_available ? 'true' : 'false'); ?>;</script>

<div class="wrap almaseo-bulk-meta">
    <h1>
        <?php echo esc_html__('Bulk Metadata Editor', 'almaseo-seo-playground'); ?>
    </h1>

    <p class="description">
        <?php echo esc_html__('Edit SEO titles and meta descriptions for multiple posts at once. Click on any field to edit inline.', 'almaseo-seo-playground'); ?>
    </p>

    <!-- Error display area -->
    <div id="almaseo-bulkmeta-error" class="notice notice-error" style="display:none;">
        <p></p>
    </div>

    <!-- AI vs Basic Explainer -->
    <div class="almaseo-ai-explainer" style="margin: 0 0 12px 0; border: 1px solid #c3d4e6; border-radius: 6px; overflow: hidden;">
        <button type="button" id="ai-explainer-toggle" style="
            width: 100%;
            padding: 12px 18px;
            background: <?php echo esc_attr($ai_autofill_available ? 'linear-gradient(135deg, #f0f0ff 0%, #f5f0ff 100%)' : '#f9f9f9'); ?>;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            text-align: left;
        ">
            <?php if ( $ai_autofill_available ) : ?>
                <span style="font-size: 18px;">&#10024;</span>
                <strong style="color: #5b21b6;"><?php esc_html_e('AI-Powered Auto-Fill is Active', 'almaseo-seo-playground'); ?></strong>
                <span style="background: #7c3aed; color: #fff; font-size: 10px; padding: 2px 8px; border-radius: 10px; font-weight: 600; letter-spacing: 0.5px;">PRO</span>
            <?php else : ?>
                <span class="dashicons dashicons-info-outline" style="color: #646970;"></span>
                <strong style="color: #1d2327;"><?php esc_html_e('Auto-Fill Mode: Basic (Local)', 'almaseo-seo-playground'); ?></strong>
            <?php endif; ?>
            <span id="ai-explainer-arrow" style="margin-left: auto; transition: transform 0.2s; color: #646970;">&#9660;</span>
        </button>
        <div id="ai-explainer-content" style="display: none; padding: 0 18px 18px 18px; background: #fff;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 18px; margin-top: 14px;">
                <!-- AI Mode -->
                <div style="padding: 16px; background: linear-gradient(135deg, #faf5ff 0%, #f0f0ff 100%); border: 1px solid #ddd6fe; border-radius: 8px;">
                    <h4 style="margin: 0 0 10px 0; color: #5b21b6; font-size: 14px;">
                        &#10024; <?php esc_html_e('AI Mode', 'almaseo-seo-playground'); ?>
                        <?php if ( $ai_autofill_available ) : ?>
                            <span style="background: #22c55e; color: #fff; font-size: 9px; padding: 2px 6px; border-radius: 8px; margin-left: 6px; vertical-align: middle;">ACTIVE</span>
                        <?php endif; ?>
                    </h4>
                    <ul style="margin: 0; padding: 0 0 0 18px; font-size: 12.5px; line-height: 1.8; color: #374151;">
                        <li><strong><?php esc_html_e('Reads your actual content', 'almaseo-seo-playground'); ?></strong> — <?php esc_html_e('analyzes the full page to understand context, topic, and angle', 'almaseo-seo-playground'); ?></li>
                        <li><strong><?php esc_html_e('Unique, human-quality output', 'almaseo-seo-playground'); ?></strong> — <?php esc_html_e('every title and description is different, written like a copywriter would', 'almaseo-seo-playground'); ?></li>
                        <li><strong><?php esc_html_e('Search intent matching', 'almaseo-seo-playground'); ?></strong> — <?php esc_html_e('informational pages get descriptive language, service pages get action-oriented CTAs', 'almaseo-seo-playground'); ?></li>
                        <li><strong><?php esc_html_e('Smart focus keywords', 'almaseo-seo-playground'); ?></strong> — <?php esc_html_e('identifies the real ranking opportunity from your content, not just the first two words of the title', 'almaseo-seo-playground'); ?></li>
                        <li><strong><?php esc_html_e('Character-perfect', 'almaseo-seo-playground'); ?></strong> — <?php esc_html_e('titles hit the 50-60 char sweet spot, descriptions land at 150-160 chars naturally', 'almaseo-seo-playground'); ?></li>
                    </ul>
                    <?php if ( ! $ai_autofill_available ) : ?>
                        <p style="margin: 12px 0 0 0; font-size: 12px; color: #7c3aed;">
                            <a href="<?php echo esc_url( admin_url('admin.php?page=seo-playground-connection') ); ?>" style="color: #7c3aed; text-decoration: underline;">
                                <?php esc_html_e('Connect to AlmaSEO to unlock AI mode', 'almaseo-seo-playground'); ?>
                            </a>
                        </p>
                    <?php endif; ?>
                </div>
                <!-- Basic Mode -->
                <div style="padding: 16px; background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <h4 style="margin: 0 0 10px 0; color: #374151; font-size: 14px;">
                        &#9881; <?php esc_html_e('Basic Mode (Local)', 'almaseo-seo-playground'); ?>
                        <?php if ( ! $ai_autofill_available ) : ?>
                            <span style="background: #6b7280; color: #fff; font-size: 9px; padding: 2px 6px; border-radius: 8px; margin-left: 6px; vertical-align: middle;">ACTIVE</span>
                        <?php endif; ?>
                    </h4>
                    <ul style="margin: 0; padding: 0 0 0 18px; font-size: 12.5px; line-height: 1.8; color: #6b7280;">
                        <li><?php esc_html_e('Prepends a random "power word" to your existing title', 'almaseo-seo-playground'); ?></li>
                        <li><?php esc_html_e('Grabs the first paragraph or excerpt as the description', 'almaseo-seo-playground'); ?></li>
                        <li><?php esc_html_e('Pads short descriptions with generic template phrases', 'almaseo-seo-playground'); ?></li>
                        <li><?php esc_html_e('Focus keyword = first two non-stopwords from the title', 'almaseo-seo-playground'); ?></li>
                        <li><?php esc_html_e('Instant results, no connection required — works offline', 'almaseo-seo-playground'); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
    <script>
    document.getElementById('ai-explainer-toggle').addEventListener('click', function() {
        var content = document.getElementById('ai-explainer-content');
        var arrow = document.getElementById('ai-explainer-arrow');
        if (content.style.display === 'none') {
            content.style.display = 'block';
            arrow.style.transform = 'rotate(180deg)';
        } else {
            content.style.display = 'none';
            arrow.style.transform = 'rotate(0deg)';
        }
    });
    </script>

    <!-- Auto-Fill Actions -->
    <div class="autofill-actions-wrapper" style="margin: 0 0 8px 0; padding: 14px 18px; background: <?php echo esc_attr($ai_autofill_available ? 'linear-gradient(135deg, #f0f0ff 0%, #f5f0ff 100%)' : '#f0f6fc'); ?>; border: 1px solid <?php echo esc_attr($ai_autofill_available ? '#ddd6fe' : '#c3d4e6'); ?>; border-left: 4px solid <?php echo esc_attr($ai_autofill_available ? '#7c3aed' : '#2271b1'); ?>; border-radius: 4px;">
        <div class="autofill-actions" style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
            <?php if ( $ai_autofill_available ) : ?>
                <span style="font-size: 18px;">&#10024;</span>
                <strong style="font-size: 13px; color: #5b21b6;"><?php echo esc_html__('AI Auto-Fill', 'almaseo-seo-playground'); ?></strong>
            <?php else : ?>
                <span class="dashicons dashicons-admin-generic" style="color: #2271b1; font-size: 20px; line-height: 30px;"></span>
                <strong style="font-size: 13px;"><?php echo esc_html__('Auto-Fill', 'almaseo-seo-playground'); ?></strong>
            <?php endif; ?>

            <button type="button" class="button button-primary" id="autofill-selected" title="<?php echo esc_attr__('Auto-generate metadata for checked posts only', 'almaseo-seo-playground'); ?>" <?php if ($ai_autofill_available) echo 'style="background: #7c3aed; border-color: #6d28d9;"'; ?>>
                <span class="dashicons dashicons-edit-page" style="font-size: 16px; line-height: 28px; margin-right: 2px;"></span>
                <?php echo $ai_autofill_available ? esc_html__('AI-Fill Selected', 'almaseo-seo-playground') : esc_html__('Auto-Fill Selected', 'almaseo-seo-playground'); ?>
            </button>

            <button type="button" class="button" id="autofill-all-empty" title="<?php echo esc_attr__('Scan site and fill only posts/pages with missing metadata', 'almaseo-seo-playground'); ?>">
                <span class="dashicons dashicons-welcome-write-blog" style="font-size: 16px; line-height: 28px; margin-right: 2px;"></span>
                <?php echo $ai_autofill_available ? esc_html__('AI-Fill All Empty', 'almaseo-seo-playground') : esc_html__('Auto-Fill All Empty', 'almaseo-seo-playground'); ?>
            </button>

            <button type="button" class="button" id="autofill-entire-site" title="<?php echo esc_attr__('Regenerate metadata for every post and page on the site — overwrites existing', 'almaseo-seo-playground'); ?>" style="color: #b32d2e; border-color: #b32d2e;">
                <span class="dashicons dashicons-update" style="font-size: 16px; line-height: 28px; margin-right: 2px;"></span>
                <?php echo $ai_autofill_available ? esc_html__('AI-Fill Entire Site', 'almaseo-seo-playground') : esc_html__('Auto-Fill Entire Site', 'almaseo-seo-playground'); ?>
            </button>

            <span style="color: #c3c4c7;">|</span>

            <button type="button" class="button" id="autofill-preview" title="<?php echo esc_attr__('Preview what will be generated for selected posts', 'almaseo-seo-playground'); ?>">
                <span class="dashicons dashicons-visibility" style="font-size: 16px; line-height: 28px; margin-right: 2px;"></span>
                <?php echo esc_html__('Preview', 'almaseo-seo-playground'); ?>
            </button>

            <span class="autofill-status" id="autofill-status" style="font-size: 12px; color: #646970;"></span>
        </div>
        <p class="description" style="margin: 6px 0 0 32px; font-size: 12px; color: #646970;">
            <?php if ( $ai_autofill_available ) : ?>
                <?php echo esc_html__('AI reads your content and generates unique, context-aware metadata — titles, descriptions, focus keywords, and Open Graph fields.', 'almaseo-seo-playground'); ?>
            <?php else : ?>
                <?php echo esc_html__('Generates SEO-optimized titles, descriptions, focus keywords, and Open Graph fields from your existing content.', 'almaseo-seo-playground'); ?>
            <?php endif; ?>
        </p>
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
                <option value=""><?php echo esc_html__('All Statuses', 'almaseo-seo-playground'); ?></option>
                <option value="publish"><?php echo esc_html__('Published', 'almaseo-seo-playground'); ?></option>
                <option value="draft"><?php echo esc_html__('Draft', 'almaseo-seo-playground'); ?></option>
                <option value="publish,draft" selected><?php echo esc_html__('Published & Draft', 'almaseo-seo-playground'); ?></option>
                <option value="pending"><?php echo esc_html__('Pending', 'almaseo-seo-playground'); ?></option>
                <option value="private"><?php echo esc_html__('Private', 'almaseo-seo-playground'); ?></option>
            </select>

            <!-- Taxonomy Filter -->
            <select id="taxonomy-filter" class="postform">
                <option value=""><?php echo esc_html__('All Categories', 'almaseo-seo-playground'); ?></option>
            </select>

            <!-- Term Filter -->
            <select id="term-filter" class="postform" style="display:none;">
                <option value=""><?php echo esc_html__('Select Term', 'almaseo-seo-playground'); ?></option>
            </select>

            <!-- Missing Only Toggle -->
            <label class="missing-toggle">
                <input type="checkbox" id="missing-only">
                <?php echo esc_html__('Missing metadata', 'almaseo-seo-playground'); ?>
            </label>

            <!-- Date Range -->
            <input type="date" id="date-from" placeholder="<?php echo esc_attr__('From', 'almaseo-seo-playground'); ?>" title="<?php echo esc_attr__('Date From', 'almaseo-seo-playground'); ?>">
            <input type="date" id="date-to" placeholder="<?php echo esc_attr__('To', 'almaseo-seo-playground'); ?>" title="<?php echo esc_attr__('Date To', 'almaseo-seo-playground'); ?>">

            <!-- Search -->
            <input type="search" id="search-box" placeholder="<?php echo esc_attr__('Search posts...', 'almaseo-seo-playground'); ?>">

            <button type="button" class="button" id="search-button">
                <?php echo esc_html__('Search', 'almaseo-seo-playground'); ?>
            </button>

            <button type="button" class="button button-primary" id="apply-filters">
                <?php echo esc_html__('Apply Filters', 'almaseo-seo-playground'); ?>
            </button>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="bulk-actions-wrapper" style="display:none;">
        <div class="bulk-actions">
            <select id="bulk-action">
                <option value=""><?php echo esc_html__('Bulk Actions', 'almaseo-seo-playground'); ?></option>
                <option value="reset"><?php echo esc_html__('Reset Selected', 'almaseo-seo-playground'); ?></option>
                <option value="append"><?php echo esc_html__('Append to Titles', 'almaseo-seo-playground'); ?></option>
                <option value="prepend"><?php echo esc_html__('Prepend to Titles', 'almaseo-seo-playground'); ?></option>
                <option value="replace"><?php echo esc_html__('Find & Replace', 'almaseo-seo-playground'); ?></option>
            </select>
            
            <!-- Bulk Action Options -->
            <div id="bulk-options" style="display:none;">
                <select id="bulk-field">
                    <option value="title"><?php echo esc_html__('SEO Title', 'almaseo-seo-playground'); ?></option>
                    <option value="description"><?php echo esc_html__('Meta Description', 'almaseo-seo-playground'); ?></option>
                </select>
                
                <input type="text" id="bulk-text" placeholder="<?php echo esc_attr__('Text to add', 'almaseo-seo-playground'); ?>" style="display:none;">
                <input type="text" id="bulk-find" placeholder="<?php echo esc_attr__('Find', 'almaseo-seo-playground'); ?>" style="display:none;">
                <input type="text" id="bulk-replace" placeholder="<?php echo esc_attr__('Replace with', 'almaseo-seo-playground'); ?>" style="display:none;">
                
                <span class="bulk-help">
                    <?php echo esc_html__('Available placeholders: {site}, {category}, {year}', 'almaseo-seo-playground'); ?>
                </span>
            </div>
            
            <button type="button" class="button button-primary" id="apply-bulk">
                <?php echo esc_html__('Apply', 'almaseo-seo-playground'); ?>
            </button>
            
            <span class="selected-count">
                <span id="selected-count">0</span> <?php echo esc_html__('selected', 'almaseo-seo-playground'); ?>
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
                        <?php echo esc_html__('Post Title', 'almaseo-seo-playground'); ?>
                    </th>
                    <th class="manage-column column-type">
                        <?php echo esc_html__('Type', 'almaseo-seo-playground'); ?>
                    </th>
                    <th class="manage-column column-status">
                        <?php echo esc_html__('Status', 'almaseo-seo-playground'); ?>
                    </th>
                    <th class="manage-column column-seo-title">
                        <?php echo esc_html__('SEO Title', 'almaseo-seo-playground'); ?>
                        <span class="dashicons dashicons-info" title="<?php echo esc_attr__('Recommended: ~65 characters', 'almaseo-seo-playground'); ?>"></span>
                    </th>
                    <th class="manage-column column-meta-description">
                        <?php echo esc_html__('Meta Description', 'almaseo-seo-playground'); ?>
                        <span class="dashicons dashicons-info" title="<?php echo esc_attr__('Recommended: ~160 characters', 'almaseo-seo-playground'); ?>"></span>
                    </th>
                    <th class="manage-column column-actions">
                        <?php echo esc_html__('Actions', 'almaseo-seo-playground'); ?>
                    </th>
                </tr>
            </thead>
            <tbody id="bulkmeta-table-body">
                <tr class="no-items">
                    <td colspan="7" class="colspanchange">
                        <?php echo esc_html__('Loading posts...', 'almaseo-seo-playground'); ?>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
    
    <!-- Pagination -->
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num">
                <span id="total-items">0</span> <?php echo esc_html__('items', 'almaseo-seo-playground'); ?>
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

<!-- Auto-Fill Preview Modal -->
<div id="autofill-preview-modal" style="display:none;">
    <div class="autofill-modal-overlay" style="position:fixed;inset:0;background:rgba(0,0,0,.5);z-index:100000;display:flex;align-items:center;justify-content:center;">
        <div class="autofill-modal-content" style="background:#fff;border-radius:8px;max-width:900px;width:90%;max-height:80vh;overflow:auto;box-shadow:0 8px 32px rgba(0,0,0,.2);">
            <div style="padding:20px 24px;border-bottom:1px solid #ddd;display:flex;align-items:center;justify-content:space-between;">
                <h2 style="margin:0;font-size:18px;">
                    <span class="dashicons dashicons-visibility" style="margin-right:6px;color:#2271b1;"></span>
                    <?php echo esc_html__('Auto-Fill Preview', 'almaseo-seo-playground'); ?>
                </h2>
                <button type="button" class="autofill-modal-close" style="background:none;border:none;cursor:pointer;font-size:20px;color:#666;padding:4px;">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            <div id="autofill-preview-body" style="padding:20px 24px;">
                <p><?php echo esc_html__('Loading preview...', 'almaseo-seo-playground'); ?></p>
            </div>
            <div style="padding:16px 24px;border-top:1px solid #ddd;display:flex;gap:10px;justify-content:flex-end;">
                <button type="button" class="button autofill-modal-close">
                    <?php echo esc_html__('Cancel', 'almaseo-seo-playground'); ?>
                </button>
                <button type="button" class="button button-primary" id="autofill-confirm-apply">
                    <span class="dashicons dashicons-yes" style="font-size:16px;line-height:28px;margin-right:2px;"></span>
                    <?php echo esc_html__('Apply All', 'almaseo-seo-playground'); ?>
                </button>
            </div>
        </div>
    </div>
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
                    <a href="{{view_link}}" target="_blank"><?php echo esc_html__('View', 'almaseo-seo-playground'); ?></a>
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
                    <button class="edit-button dashicons dashicons-edit" title="<?php echo esc_attr__('Edit', 'almaseo-seo-playground'); ?>"></button>
                </div>
                <div class="cell-editor" style="display:none;">
                    <input type="text" class="meta-title-input" value="{{meta_title}}" 
                           placeholder="{{title_fallback}}" maxlength="200">
                    <div class="field-counter">
                        <span class="char-count">0</span> <?php echo esc_html__('chars', 'almaseo-seo-playground'); ?> | 
                        <span class="pixel-count">0</span> <?php echo esc_html__('px', 'almaseo-seo-playground'); ?>
                    </div>
                </div>
            </div>
        </td>
        <td class="column-meta-description">
            <div class="editable-cell" data-field="meta_description">
                <div class="cell-value">
                    <span class="value-text">{{meta_desc_display}}</span>
                    <button class="edit-button dashicons dashicons-edit" title="<?php echo esc_attr__('Edit', 'almaseo-seo-playground'); ?>"></button>
                </div>
                <div class="cell-editor" style="display:none;">
                    <textarea class="meta-description-input" rows="3" maxlength="500"
                              placeholder="{{desc_fallback}}">{{meta_description}}</textarea>
                    <div class="field-counter">
                        <span class="char-count">0</span> <?php echo esc_html__('chars', 'almaseo-seo-playground'); ?> | 
                        <span class="pixel-count">0</span> <?php echo esc_html__('px', 'almaseo-seo-playground'); ?>
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
                <?php echo esc_html__('Edit', 'almaseo-seo-playground'); ?>
            </button>
            <button class="button button-small reset-meta" data-id="{{id}}">
                <?php echo esc_html__('Reset', 'almaseo-seo-playground'); ?>
            </button>
        </td>
    </tr>
</script>