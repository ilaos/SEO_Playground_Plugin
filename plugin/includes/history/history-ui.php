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
            <h3><?php _e('📜 Metadata History', 'almaseo'); ?></h3>
            <button type="button" class="button button-secondary" id="almaseo-create-snapshot" data-post-id="<?php echo esc_attr($post->ID); ?>">
                <span class="dashicons dashicons-camera"></span>
                <?php _e('Create Snapshot Now', 'almaseo'); ?>
            </button>
        </div>
        
        <!-- Help Text -->
        <div class="history-help-text">
            <p class="description">
                <strong><?php _e('What is this?', 'almaseo'); ?></strong> 
                <?php _e('Automatic version control for your SEO metadata. Every time you change the SEO title, meta description, focus keyword, or schema, a snapshot is saved automatically.', 'almaseo'); ?>
            </p>
            <div class="history-features">
                <span class="history-feature">
                    <span class="dashicons dashicons-backup"></span>
                    <?php _e('Restore any previous version instantly', 'almaseo'); ?>
                </span>
                <span class="history-feature">
                    <span class="dashicons dashicons-editor-code"></span>
                    <?php _e('Compare changes between versions', 'almaseo'); ?>
                </span>
                <span class="history-feature">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Last 20 versions kept automatically', 'almaseo'); ?>
                </span>
            </div>
            <p class="history-tip">
                <strong><?php _e('💡 Tip:', 'almaseo'); ?></strong>
                <?php _e('Use "Create Snapshot Now" before making major changes to save a checkpoint you can return to.', 'almaseo'); ?>
            </p>
        </div>
        
        <?php if (empty($snapshots)): ?>
        <div class="history-empty-state">
            <div class="empty-state-icon">📝</div>
            <h4><?php _e('No History Yet', 'almaseo'); ?></h4>
            <p><?php _e('Your SEO metadata changes will appear here automatically when you:', 'almaseo'); ?></p>
            <ul class="history-triggers">
                <li><?php _e('✓ Update the SEO title', 'almaseo'); ?></li>
                <li><?php _e('✓ Modify the meta description', 'almaseo'); ?></li>
                <li><?php _e('✓ Change the focus keyword', 'almaseo'); ?></li>
                <li><?php _e('✓ Edit schema markup', 'almaseo'); ?></li>
            </ul>
            <p class="description"><?php _e('Start editing your SEO fields above and save the post to create your first history snapshot.', 'almaseo'); ?></p>
        </div>
        <?php else: ?>
        
        <!-- Version List Header -->
        <div class="history-list-header">
            <h4><?php _e('Version History', 'almaseo'); ?></h4>
            <p class="description">
                <?php 
                printf(
                    __('Showing %d version%s. Click "Compare" to see differences or "Restore" to revert changes.', 'almaseo'),
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
                $user_name = $user ? $user->display_name : __('System', 'almaseo');
                $avatar = get_avatar($snapshot->user_id ?: 0, 32);
                
                // Get changed fields
                $changed_fields = array();
                if ($prev_snapshot) {
                    $changed_fields = almaseo_history_get_changed_fields($prev_snapshot, $snapshot);
                }
                
                // Format time
                $time_diff = human_time_diff(strtotime($snapshot->created_at . ' UTC'), current_time('timestamp'));
                
                // Source badge color and label
                $source_class = 'source-' . $snapshot->source;
                $source_labels = array(
                    'auto' => __('Auto-saved', 'almaseo'),
                    'manual' => __('Manual snapshot', 'almaseo'),
                    'restore' => __('Restored', 'almaseo'),
                    'import' => __('Imported', 'almaseo')
                );
                $source_label = isset($source_labels[$snapshot->source]) ? $source_labels[$snapshot->source] : $snapshot->source;
                ?>
                <div class="history-item" data-version-id="<?php echo esc_attr($snapshot->id); ?>" data-version="<?php echo esc_attr($snapshot->version); ?>">
                    <div class="history-item-header">
                        <div class="history-meta">
                            <?php echo $avatar; ?>
                            <div class="history-info">
                                <div class="history-version">
                                    <strong>v<?php echo esc_html($snapshot->version); ?></strong>
                                    <span class="history-time" title="<?php echo esc_attr($snapshot->created_at); ?> UTC">
                                        • <?php echo esc_html($time_diff); ?> <?php _e('ago', 'almaseo'); ?>
                                    </span>
                                    <span class="history-user">• <?php echo esc_html($user_name); ?></span>
                                    <span class="history-source <?php echo esc_attr($source_class); ?>" title="<?php esc_attr_e('How this version was created', 'almaseo'); ?>">
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
                                    title="<?php esc_attr_e('View side-by-side comparison of changes between this version and another', 'almaseo'); ?>">
                                <span class="dashicons dashicons-editor-code"></span>
                                <?php _e('Compare', 'almaseo'); ?>
                            </button>
                            <button type="button" class="button button-small history-restore" 
                                    data-version-id="<?php echo esc_attr($snapshot->id); ?>"
                                    title="<?php esc_attr_e('Replace current SEO metadata with this version (creates a new restore point)', 'almaseo'); ?>">
                                <span class="dashicons dashicons-backup"></span>
                                <?php _e('Restore', 'almaseo'); ?>
                            </button>
                            <div class="history-more-menu">
                                <button type="button" class="button button-small history-more-btn">
                                    <span class="dashicons dashicons-ellipsis"></span>
                                </button>
                                <div class="history-dropdown" style="display: none;">
                                    <a href="#" class="history-copy-json" data-version-id="<?php echo esc_attr($snapshot->id); ?>">
                                        <?php _e('Copy JSON', 'almaseo'); ?>
                                    </a>
                                    <a href="#" class="history-delete" data-version-id="<?php echo esc_attr($snapshot->id); ?>">
                                        <?php _e('Delete Version', 'almaseo'); ?>
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
                <h3><?php _e('Compare Versions', 'almaseo'); ?></h3>
                <button type="button" class="drawer-close">
                    <span class="dashicons dashicons-no-alt"></span>
                </button>
            </div>
            
            <div class="drawer-selectors">
                <p class="description">
                    <?php _e('Select two versions to compare their differences. Red shows what was removed, green shows what was added.', 'almaseo'); ?>
                </p>
                <div class="selector-controls">
                    <div class="selector-group">
                        <label><?php _e('From (Old):', 'almaseo'); ?></label>
                        <select id="compare-from-version" title="<?php esc_attr_e('Select the older version to compare from', 'almaseo'); ?>">
                            <?php foreach ($snapshots as $snapshot): ?>
                            <option value="<?php echo esc_attr($snapshot->version); ?>">
                                v<?php echo esc_html($snapshot->version); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="button" class="swap-versions" title="<?php esc_attr_e('Swap the two versions', 'almaseo'); ?>">
                        <span class="dashicons dashicons-leftright"></span>
                    </button>
                    
                    <div class="selector-group">
                        <label><?php _e('To (New):', 'almaseo'); ?></label>
                        <select id="compare-to-version" title="<?php esc_attr_e('Select the newer version to compare to', 'almaseo'); ?>">
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
                    <?php _e('Loading comparison...', 'almaseo'); ?>
                </div>
                
                <div class="compare-results" id="compare-results">
                    <!-- Comparison results will be loaded here -->
                </div>
            </div>
            
            <div class="drawer-footer">
                <button type="button" class="button button-primary" id="restore-to-version">
                    <?php _e('Restore To Version', 'almaseo'); ?>
                </button>
                <button type="button" class="button drawer-close">
                    <?php _e('Close', 'almaseo'); ?>
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
                <div class="column-header"><?php _e('From (Old)', 'almaseo'); ?></div>
                <div class="column-content">
                    <?php if ($field_name === 'schema_json'): ?>
                    <div class="schema-preview">
                        <pre><?php echo esc_html(almaseo_history_format_schema($from_value)); ?></pre>
                    </div>
                    <?php else: ?>
                    <div class="field-value">
                        <?php echo !empty($from_value) ? esc_html($from_value) : '<em>' . __('(empty)', 'almaseo') . '</em>'; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="compare-column to-column">
                <div class="column-header"><?php _e('To (New)', 'almaseo'); ?></div>
                <div class="column-content">
                    <?php if ($field_name === 'schema_json'): ?>
                    <div class="schema-preview">
                        <pre><?php echo esc_html(almaseo_history_format_schema($to_value)); ?></pre>
                    </div>
                    <?php else: ?>
                    <div class="field-value">
                        <?php echo !empty($to_value) ? esc_html($to_value) : '<em>' . __('(empty)', 'almaseo') . '</em>'; ?>
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