<?php
/**
 * AlmaSEO Metadata History - UI Components
 * 
 * @package AlmaSEO
 * @subpackage History
 * @since 6.8.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render metadata history card
 */
function almaseo_history_render_card($post) {
    // Get snapshots
    $snapshots = almaseo_history_get_snapshots($post->ID, 20);
    
    ?>
    <div class="almaseo-history-card">
        <div class="history-card-header">
            <h3><?php esc_html_e('📜 Metadata History', 'almaseo-seo-playground'); ?></h3>
            <button type="button" class="button button-secondary" id="almaseo-create-snapshot" data-post-id="<?php echo esc_attr($post->ID); ?>">
                <span class="dashicons dashicons-camera"></span>
                <?php esc_html_e('Create Snapshot Now', 'almaseo-seo-playground'); ?>
            </button>
        </div>
        
        <!-- Help Text -->
        <div class="history-help-text">
            <p class="description">
                <strong><?php esc_html_e('What is this?', 'almaseo-seo-playground'); ?></strong> 
                <?php esc_html_e('Automatic version control for your SEO metadata. Every time you change the SEO title, meta description, focus keyword, or schema, a snapshot is saved automatically.', 'almaseo-seo-playground'); ?>
            </p>
            <div class="history-features">
                <span class="history-feature">
                    <span class="dashicons dashicons-backup"></span>
                    <?php esc_html_e('Restore any previous version instantly', 'almaseo-seo-playground'); ?>
                </span>
                <span class="history-feature">
                    <span class="dashicons dashicons-editor-code"></span>
                    <?php esc_html_e('Compare changes between versions', 'almaseo-seo-playground'); ?>
                </span>
                <span class="history-feature">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('Last 20 versions kept automatically', 'almaseo-seo-playground'); ?>
                </span>
            </div>
            <p class="history-tip">
                <strong><?php esc_html_e('💡 Tip:', 'almaseo-seo-playground'); ?></strong>
                <?php esc_html_e('Use "Create Snapshot Now" before making major changes to save a checkpoint you can return to.', 'almaseo-seo-playground'); ?>
            </p>
        </div>
        
        <?php if (empty($snapshots)): ?>
        <div class="history-empty-state">
            <div class="empty-state-icon">📝</div>
            <h4><?php esc_html_e('No History Yet', 'almaseo-seo-playground'); ?></h4>
            <p><?php esc_html_e('Your SEO metadata changes will appear here automatically when you:', 'almaseo-seo-playground'); ?></p>
            <ul class="history-triggers">
                <li><?php esc_html_e('✓ Update the SEO title', 'almaseo-seo-playground'); ?></li>
                <li><?php esc_html_e('✓ Modify the meta description', 'almaseo-seo-playground'); ?></li>
                <li><?php esc_html_e('✓ Change the focus keyword', 'almaseo-seo-playground'); ?></li>
                <li><?php esc_html_e('✓ Edit schema markup', 'almaseo-seo-playground'); ?></li>
            </ul>
            <p class="description"><?php esc_html_e('Start editing your SEO fields above and save the post to create your first history snapshot.', 'almaseo-seo-playground'); ?></p>
        </div>
        <?php else: ?>
        
        <!-- Version List Header -->
        <div class="history-list-header">
            <h4><?php esc_html_e('Version History', 'almaseo-seo-playground'); ?></h4>
            <p class="description">
                <?php 
                printf(
                    /* translators: %1$d: number of versions, %2$s: plural "s" or empty */
                    __('Showing %1$d version%2$s. Click "Compare" to see differences or "Restore" to revert changes.', 'almaseo-seo-playground'),
                    count($snapshots),
                    count($snapshots) > 1 ? 's' : ''
                );
                ?>
            </p>
        </div>
        <div class="history-list">
            <?php
            $prev_snapshot = null;
            foreach ($snapshots as $snapshot):
                $user = get_userdata($snapshot->user_id);
                $user_name = $user ? $user->display_name : __('System', 'almaseo-seo-playground');
                $avatar = get_avatar($snapshot->user_id ?: 0, 32);
                
                // Get changed fields
                $changed_fields = array();
                if ($prev_snapshot) {
                    $changed_fields = almaseo_history_get_changed_fields($prev_snapshot, $snapshot);
                }
                
                // Format time
                $time_diff = human_time_diff(strtotime($snapshot->created_at . ' UTC'), current_time('U'));
                
                // Source badge color and label
                $source_class = 'source-' . $snapshot->source;
                $source_labels = array(
                    'auto' => __('Auto-saved', 'almaseo-seo-playground'),
                    'manual' => __('Manual snapshot', 'almaseo-seo-playground'),
                    'restore' => __('Restored', 'almaseo-seo-playground'),
                    'import' => __('Imported', 'almaseo-seo-playground')
                );
                $source_label = isset($source_labels[$snapshot->source]) ? $source_labels[$snapshot->source] : $snapshot->source;
                ?>
                <div class="history-item" data-version-id="<?php echo esc_attr($snapshot->id); ?>" data-version="<?php echo esc_attr($snapshot->version); ?>">
                    <div class="history-item-header">
                        <div class="history-meta">
                            <?php echo wp_kses_post($avatar); ?>
                            <div class="history-info">
                                <div class="history-version">
                                    <strong>v<?php echo esc_html($snapshot->version); ?></strong>
                                    <span class="history-time" title="<?php echo esc_attr($snapshot->created_at); ?> UTC">
                                        • <?php echo esc_html($time_diff); ?> <?php esc_html_e('ago', 'almaseo-seo-playground'); ?>
                                    </span>
                                    <span class="history-user">• <?php echo esc_html($user_name); ?></span>
                                    <span class="history-source <?php echo esc_attr($source_class); ?>" title="<?php esc_attr_e('How this version was created', 'almaseo-seo-playground'); ?>">
                                        <?php echo esc_html($source_label); ?>
                                    </span>
                                </div>
                                
                                <?php if (!empty($changed_fields)): ?>
                                <div class="history-changes">
                                    <?php foreach ($changed_fields as $field): ?>
                                    <span class="change-badge" title="<?php echo esc_attr(almaseo_history_format_field_name($field) . ' changed'); ?>">
                                        <?php echo esc_html(almaseo_history_format_field_name($field)); ?>
                                    </span>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="history-actions">
                            <button type="button" class="button button-small history-compare" 
                                    data-version="<?php echo esc_attr($snapshot->version); ?>"
                                    title="<?php esc_attr_e('View side-by-side comparison of changes between this version and another', 'almaseo-seo-playground'); ?>">
                                <span class="dashicons dashicons-editor-code"></span>
                                <?php esc_html_e('Compare', 'almaseo-seo-playground'); ?>
                            </button>
                            <button type="button" class="button button-small history-restore" 
                                    data-version-id="<?php echo esc_attr($snapshot->id); ?>"
                                    title="<?php esc_attr_e('Replace current SEO metadata with this version (creates a new restore point)', 'almaseo-seo-playground'); ?>">
                                <span class="dashicons dashicons-backup"></span>
                                <?php esc_html_e('Restore', 'almaseo-seo-playground'); ?>
                            </button>
                            <div class="history-more-menu">
                                <button type="button" class="button button-small history-more-btn">
                                    <span class="dashicons dashicons-ellipsis"></span>
                                </button>
                                <div class="history-dropdown" style="display: none;">
                                    <a href="#" class="history-copy-json" data-version-id="<?php echo esc_attr($snapshot->id); ?>">
                                        <?php esc_html_e('Copy JSON', 'almaseo-seo-playground'); ?>
                                    </a>
                                    <a href="#" class="history-delete" data-version-id="<?php echo esc_attr($snapshot->id); ?>">
                                        <?php esc_html_e('Delete Version', 'almaseo-seo-playground'); ?>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                $prev_snapshot = $snapshot;
            endforeach;
            ?>
        </div>
        <?php endif; ?>
        
        <!-- Hidden data for JavaScript -->
        <input type="hidden" id="almaseo-history-post-id" value="<?php echo esc_attr($post->ID); ?>">
    </div>
    
    <!-- Compare Drawer -->
    <div id="almaseo-history-drawer" class="history-drawer" style="display: none;">
        <div class="drawer-overlay"></div>
        <div class="drawer-panel">
            <div class="drawer-header">
                <h3><?php esc_html_e('Compare Versions', 'almaseo-seo-playground'); ?></h3>
                <button type="button" class="drawer-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            
            <div class="drawer-selectors">
                <p class="description">
                    <?php esc_html_e('Select two versions to compare their differences. Red shows what was removed, green shows what was added.', 'almaseo-seo-playground'); ?>
                </p>
                <div class="selector-controls">
                    <div class="selector-group">
                        <label><?php esc_html_e('From (Old):', 'almaseo-seo-playground'); ?></label>
                        <select id="compare-from-version" title="<?php esc_attr_e('Select the older version to compare from', 'almaseo-seo-playground'); ?>">
                            <?php foreach ($snapshots as $snapshot): ?>
                            <option value="<?php echo esc_attr($snapshot->version); ?>">
                                v<?php echo esc_html($snapshot->version); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="button" class="swap-versions" title="<?php esc_attr_e('Swap the two versions', 'almaseo-seo-playground'); ?>">
                        <span class="dashicons dashicons-leftright"></span>
                    </button>
                    
                    <div class="selector-group">
                        <label><?php esc_html_e('To (New):', 'almaseo-seo-playground'); ?></label>
                        <select id="compare-to-version" title="<?php esc_attr_e('Select the newer version to compare to', 'almaseo-seo-playground'); ?>">
                            <?php foreach ($snapshots as $snapshot): ?>
                            <option value="<?php echo esc_attr($snapshot->version); ?>">
                                v<?php echo esc_html($snapshot->version); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            
            <div class="drawer-content">
                <div class="compare-loading" style="display: none;">
                    <span class="spinner is-active"></span>
                    <?php esc_html_e('Loading comparison...', 'almaseo-seo-playground'); ?>
                </div>
                
                <div class="compare-results" id="compare-results">
                    <!-- Comparison results will be loaded here -->
                </div>
            </div>
            
            <div class="drawer-footer">
                <button type="button" class="button button-primary" id="restore-to-version">
                    <?php esc_html_e('Restore To Version', 'almaseo-seo-playground'); ?>
                </button>
                <button type="button" class="button drawer-close">
                    <?php esc_html_e('Close', 'almaseo-seo-playground'); ?>
                </button>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Add history card to Notes & History tab
 */
add_action('almaseo_notes_history_tab_content', 'almaseo_history_render_card');

/**
 * Render comparison field
 */
function almaseo_history_render_compare_field($field_name, $from_value, $to_value) {
    $changed = ($from_value !== $to_value);
    $field_class = $changed ? 'field-changed' : 'field-unchanged';
    
    ?>
    <div class="compare-field <?php echo esc_attr($field_class); ?>">
        <h4><?php echo esc_html(almaseo_history_format_field_name($field_name)); ?></h4>
        
        <div class="compare-columns">
            <div class="compare-column from-column">
                <div class="column-header"><?php esc_html_e('From (Old)', 'almaseo-seo-playground'); ?></div>
                <div class="column-content">
                    <?php if ($field_name === 'schema_json'): ?>
                    <div class="schema-preview">
                        <pre><?php echo esc_html(almaseo_history_format_schema($from_value)); ?></pre>
                    </div>
                    <?php else: ?>
                    <div class="field-value">
                        <?php echo !empty($from_value) ? esc_html($from_value) : '<em>' . __('(empty)', 'almaseo-seo-playground') . '</em>'; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="compare-column to-column">
                <div class="column-header"><?php esc_html_e('To (New)', 'almaseo-seo-playground'); ?></div>
                <div class="column-content">
                    <?php if ($field_name === 'schema_json'): ?>
                    <div class="schema-preview">
                        <pre><?php echo esc_html(almaseo_history_format_schema($to_value)); ?></pre>
                    </div>
                    <?php else: ?>
                    <div class="field-value">
                        <?php echo !empty($to_value) ? esc_html($to_value) : '<em>' . __('(empty)', 'almaseo-seo-playground') . '</em>'; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <?php if ($changed && $field_name !== 'schema_json'): ?>
        <div class="field-diff">
            <?php
            // Simple inline diff visualization
            $diff = almaseo_history_generate_diff($from_value, $to_value);
            if ($diff['type'] === 'simple'):
            ?>
            <div class="diff-simple">
                <del><?php echo esc_html($from_value); ?></del>
                <ins><?php echo esc_html($to_value); ?></ins>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
    <?php
}