<?php
/**
 * AlmaSEO 404 Logs - Admin Page
 * 
 * @package AlmaSEO
 * @subpackage 404Tracker
 * @since 6.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load model for stats
require_once ALMASEO_PATH . 'includes/404/404-model.php';

// Get stats
$stats = AlmaSEO_404_Model::get_stats();
$top_referrer = AlmaSEO_404_Model::get_top_referrer();

// Get initial logs
$logs_data = AlmaSEO_404_Model::get_logs(array(
    'page' => isset($_GET['paged']) ? absint($_GET['paged']) : 1,
    'per_page' => 20
));

?>
<div class="wrap almaseo-404-logs">
    <h1>
        <?php _e('404 Error Logs', 'almaseo'); ?>
        <span class="help-text"><?php _e('Track and fix broken links on your site', 'almaseo'); ?></span>
    </h1>
    
    <!-- Stats Cards -->
    <div class="stats-cards">
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['total_7d']); ?></div>
            <div class="stat-label"><?php _e('404s (7 days)', 'almaseo'); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['unique_7d']); ?></div>
            <div class="stat-label"><?php _e('Unique Paths (7d)', 'almaseo'); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo esc_html($top_referrer); ?></div>
            <div class="stat-label"><?php _e('Top Referrer (7d)', 'almaseo'); ?></div>
        </div>
        <div class="stat-card">
            <div class="stat-value"><?php echo number_format($stats['today']); ?></div>
            <div class="stat-label"><?php _e("Today's 404s", 'almaseo'); ?></div>
        </div>
    </div>
    
    <!-- Inline Help -->
    <div class="inline-help">
        <span class="dashicons dashicons-info"></span>
        <?php _e('404 logs show missing URLs on your site. Fix broken links by creating redirects or restoring missing pages. Use "Ignore" to hide bot noise and unimportant paths.', 'almaseo'); ?>
    </div>
    
    <!-- Filters -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <select id="bulk-action-selector">
                <option value=""><?php _e('Bulk Actions', 'almaseo'); ?></option>
                <option value="ignore"><?php _e('Ignore', 'almaseo'); ?></option>
                <option value="unignore"><?php _e('Unignore', 'almaseo'); ?></option>
                <option value="delete"><?php _e('Delete', 'almaseo'); ?></option>
            </select>
            <button type="button" class="button" id="do-bulk-action"><?php _e('Apply', 'almaseo'); ?></button>
        </div>
        
        <div class="alignright">
            <input type="text" id="search-input" placeholder="<?php esc_attr_e('Search paths, referrers...', 'almaseo'); ?>" />
            <select id="ignored-filter">
                <option value=""><?php _e('All', 'almaseo'); ?></option>
                <option value="0"><?php _e('Active Only', 'almaseo'); ?></option>
                <option value="1"><?php _e('Ignored Only', 'almaseo'); ?></option>
            </select>
            <input type="date" id="date-from" placeholder="<?php esc_attr_e('From', 'almaseo'); ?>" />
            <input type="date" id="date-to" placeholder="<?php esc_attr_e('To', 'almaseo'); ?>" />
            <button type="button" class="button button-primary" id="apply-filters"><?php _e('Filter', 'almaseo'); ?></button>
            <button type="button" class="button" id="clear-filters"><?php _e('Clear', 'almaseo'); ?></button>
        </div>
    </div>
    
    <!-- Table -->
    <table class="wp-list-table widefat fixed striped">
        <thead>
            <tr>
                <td class="check-column">
                    <input type="checkbox" id="select-all" />
                </td>
                <th class="column-path"><?php _e('Path', 'almaseo'); ?></th>
                <th class="column-query"><?php _e('Query', 'almaseo'); ?></th>
                <th class="column-hits"><?php _e('Hits', 'almaseo'); ?></th>
                <th class="column-last-seen"><?php _e('Last Seen', 'almaseo'); ?></th>
                <th class="column-referrer"><?php _e('Referrer', 'almaseo'); ?></th>
                <th class="column-user-agent"><?php _e('User Agent', 'almaseo'); ?></th>
                <th class="column-actions"><?php _e('Actions', 'almaseo'); ?></th>
            </tr>
        </thead>
        <tbody id="404-logs-tbody">
            <?php if (!empty($logs_data['items'])): ?>
                <?php foreach ($logs_data['items'] as $log): ?>
                <tr data-id="<?php echo esc_attr($log['id']); ?>" class="<?php echo $log['is_ignored'] ? 'ignored' : ''; ?>">
                    <th scope="row" class="check-column">
                        <input type="checkbox" class="log-checkbox" value="<?php echo esc_attr($log['id']); ?>" />
                    </th>
                    <td class="column-path">
                        <strong><?php echo esc_html($log['path']); ?></strong>
                        <?php if ($log['is_ignored']): ?>
                            <span class="ignored-badge"><?php _e('Ignored', 'almaseo'); ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="column-query">
                        <?php if ($log['query']): ?>
                            <code><?php echo esc_html(substr($log['query'], 0, 50)); ?><?php echo strlen($log['query']) > 50 ? '...' : ''; ?></code>
                        <?php else: ?>
                            <span class="no-data">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="column-hits">
                        <span class="hit-count"><?php echo number_format($log['hits']); ?></span>
                    </td>
                    <td class="column-last-seen">
                        <?php echo esc_html(human_time_diff(strtotime($log['last_seen']), current_time('timestamp'))); ?> ago
                        <br>
                        <small><?php echo esc_html($log['last_seen']); ?></small>
                    </td>
                    <td class="column-referrer">
                        <?php if ($log['referrer']): ?>
                            <span title="<?php echo esc_attr($log['referrer']); ?>">
                                <?php echo esc_html($log['referrer_domain'] ?: parse_url($log['referrer'], PHP_URL_HOST)); ?>
                            </span>
                        <?php else: ?>
                            <span class="no-data">Direct</span>
                        <?php endif; ?>
                    </td>
                    <td class="column-user-agent">
                        <?php if ($log['user_agent']): ?>
                            <span title="<?php echo esc_attr($log['user_agent']); ?>">
                                <?php echo esc_html($log['user_agent_display']); ?>
                            </span>
                        <?php else: ?>
                            <span class="no-data">—</span>
                        <?php endif; ?>
                    </td>
                    <td class="column-actions">
                        <button type="button" class="button button-small create-redirect" data-id="<?php echo esc_attr($log['id']); ?>">
                            <?php _e('Create Redirect', 'almaseo'); ?>
                        </button>
                        <?php if ($log['is_ignored']): ?>
                            <button type="button" class="button button-small unignore-log" data-id="<?php echo esc_attr($log['id']); ?>">
                                <?php _e('Unignore', 'almaseo'); ?>
                            </button>
                        <?php else: ?>
                            <button type="button" class="button button-small ignore-log" data-id="<?php echo esc_attr($log['id']); ?>">
                                <?php _e('Ignore', 'almaseo'); ?>
                            </button>
                        <?php endif; ?>
                        <button type="button" class="button button-small button-link-delete delete-log" data-id="<?php echo esc_attr($log['id']); ?>">
                            <?php _e('Delete', 'almaseo'); ?>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="8" class="no-items">
                        <?php _e('No 404 errors found. Great job!', 'almaseo'); ?>
                    </td>
                </tr>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <td class="check-column">
                    <input type="checkbox" id="select-all-bottom" />
                </td>
                <th><?php _e('Path', 'almaseo'); ?></th>
                <th><?php _e('Query', 'almaseo'); ?></th>
                <th><?php _e('Hits', 'almaseo'); ?></th>
                <th><?php _e('Last Seen', 'almaseo'); ?></th>
                <th><?php _e('Referrer', 'almaseo'); ?></th>
                <th><?php _e('User Agent', 'almaseo'); ?></th>
                <th><?php _e('Actions', 'almaseo'); ?></th>
            </tr>
        </tfoot>
    </table>
    
    <!-- Pagination -->
    <div class="tablenav bottom">
        <div class="tablenav-pages">
            <span class="displaying-num">
                <?php printf(
                    _n('%s item', '%s items', $logs_data['total'], 'almaseo'),
                    number_format($logs_data['total'])
                ); ?>
            </span>
            <span class="pagination-links" id="pagination-links">
                <!-- Pagination will be rendered by JavaScript -->
            </span>
        </div>
    </div>
    
    <!-- Loading Overlay -->
    <div id="404-loading" class="loading-overlay" style="display: none;">
        <div class="spinner"></div>
    </div>
</div>

<style>
/* Inline critical styles - full styles in 404-logs.css */
.almaseo-404-logs .help-text {
    font-size: 14px;
    color: #666;
    font-weight: normal;
    margin-left: 10px;
}

.stats-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin: 20px 0;
}

.stat-card {
    background: white;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    padding: 20px;
    text-align: center;
}

.stat-value {
    font-size: 32px;
    font-weight: 600;
    color: #23282d;
}

.stat-label {
    font-size: 14px;
    color: #666;
    margin-top: 5px;
}

.inline-help {
    background: #f0f8ff;
    border-left: 4px solid #0073aa;
    padding: 12px;
    margin: 20px 0;
}

.ignored-badge {
    background: #f0f0f0;
    color: #666;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 11px;
    margin-left: 5px;
}

.no-data {
    color: #999;
}

tr.ignored {
    opacity: 0.6;
}

.loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.8);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
}

.spinner {
    border: 3px solid #f3f3f3;
    border-top: 3px solid #0073aa;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
</style>