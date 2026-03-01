<?php
/**
 * Health & Scan Tab - Validate button/results, Conflict Scanner table, Diff report, Health Log viewer
 *
 * @package AlmaSEO
 * @since 4.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get conflict scanner status
$scan_status = array('status' => 'idle', 'checked' => 0, 'total' => 0, 'issues' => 0, 'progress' => 0);
try {
    if (class_exists('Alma_Sitemap_Conflicts')) {
        $scan_status = Alma_Sitemap_Conflicts::get_status();
    }
} catch (Exception $e) {
    // Handle gracefully
}

// Get diff summary
$diff_summary = array();
try {
    if (class_exists('Alma_Sitemap_Diff')) {
        $diff_summary = Alma_Sitemap_Diff::get_summary();
    }
} catch (Exception $e) {
    // Handle gracefully
}

// Get health logs
$logs = array();
$log_stats = array('total' => 0, 'last_24h' => 0);
try {
    if (class_exists('Alma_Health_Log')) {
        $logs = Alma_Health_Log::get_logs('', 20);
        $log_stats = Alma_Health_Log::get_stats();
    }
} catch (Exception $e) {
    // Handle gracefully
}
?>

<!-- Sitemap Validation -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('Sitemap Validation', 'almaseo'); ?></h2>
        <div class="almaseo-button-group">
            <button type="button" class="button button-primary" id="validate-sitemap">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Validate Sitemap', 'almaseo'); ?>
            </button>
            <button type="button" class="button almaseo-button-secondary" id="validate-all">
                <span class="dashicons dashicons-portfolio"></span>
                <?php _e('Validate All', 'almaseo'); ?>
            </button>
        </div>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-info-box almaseo-info-default">
            <p>
                <span class="dashicons dashicons-shield-alt"></span>
                <?php _e('Validation checks your sitemap structure, XML syntax, URL accessibility, and compliance with sitemap protocols.', 'almaseo'); ?>
            </p>
        </div>
        
        <!-- Validation Results Container -->
        <div id="validation-results" style="display:none;">
            <div class="almaseo-validation-progress">
                <div class="almaseo-progress-bar">
                    <div class="almaseo-progress-fill" style="width: 0%"></div>
                </div>
                <p id="validation-status"><?php _e('Starting validation...', 'almaseo'); ?></p>
            </div>
            
            <div class="almaseo-validation-summary" style="display:none;">
                <div class="almaseo-stat-grid">
                    <div class="almaseo-stat">
                        <div class="almaseo-stat-value almaseo-text-success" id="valid-urls">0</div>
                        <div class="almaseo-stat-label"><?php _e('Valid URLs', 'almaseo'); ?></div>
                    </div>
                    <div class="almaseo-stat">
                        <div class="almaseo-stat-value almaseo-text-warning" id="warning-urls">0</div>
                        <div class="almaseo-stat-label"><?php _e('Warnings', 'almaseo'); ?></div>
                    </div>
                    <div class="almaseo-stat">
                        <div class="almaseo-stat-value almaseo-text-danger" id="error-urls">0</div>
                        <div class="almaseo-stat-label"><?php _e('Errors', 'almaseo'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Validation Issues Table -->
            <div id="validation-issues" style="display:none;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('URL', 'almaseo'); ?></th>
                            <th><?php _e('Issue', 'almaseo'); ?></th>
                            <th><?php _e('Severity', 'almaseo'); ?></th>
                            <th><?php _e('Details', 'almaseo'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="validation-issues-tbody">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Validation Tips -->
        <div class="almaseo-tips-grid">
            <div class="almaseo-tip">
                <h4><?php _e('XML Structure', 'almaseo'); ?></h4>
                <p><?php _e('Checks for proper XML formatting and required elements', 'almaseo'); ?></p>
            </div>
            <div class="almaseo-tip">
                <h4><?php _e('URL Accessibility', 'almaseo'); ?></h4>
                <p><?php _e('Verifies that URLs return proper HTTP status codes', 'almaseo'); ?></p>
            </div>
            <div class="almaseo-tip">
                <h4><?php _e('Protocol Compliance', 'almaseo'); ?></h4>
                <p><?php _e('Ensures compliance with sitemap protocol standards', 'almaseo'); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="almaseo-two-column">
    <!-- Conflict Scanner -->
    <div class="almaseo-card">
        <div class="almaseo-card-header">
            <h2><?php _e('Conflict Scanner', 'almaseo'); ?></h2>
            <div class="almaseo-button-group">
                <button type="button" class="button button-secondary" id="scan-conflicts-btn">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('Start Scan', 'almaseo'); ?>
                </button>
            </div>
        </div>
        <div class="almaseo-card-body">
            <?php if ($scan_status['status'] === 'idle') : ?>
                <div class="almaseo-empty-state">
                    <span class="dashicons dashicons-shield"></span>
                    <p><?php _e('No scan performed yet', 'almaseo'); ?></p>
                    <p class="description"><?php _e('Detect conflicting plugins and configuration issues', 'almaseo'); ?></p>
                </div>
            <?php elseif ($scan_status['status'] === 'running') : ?>
                <div class="almaseo-scan-progress">
                    <div class="almaseo-progress-bar">
                        <div class="almaseo-progress-fill" style="width: <?php echo $scan_status['progress']; ?>%"></div>
                    </div>
                    <p><?php printf(__('Scanning: %d/%d URLs checked', 'almaseo'), $scan_status['checked'], $scan_status['total']); ?></p>
                </div>
            <?php else : ?>
                <div class="almaseo-scan-results">
                    <div class="almaseo-stat-grid">
                        <div class="almaseo-stat">
                            <div class="almaseo-stat-value"><?php echo $scan_status['checked']; ?></div>
                            <div class="almaseo-stat-label"><?php _e('URLs Checked', 'almaseo'); ?></div>
                        </div>
                        <div class="almaseo-stat">
                            <div class="almaseo-stat-value <?php echo $scan_status['issues'] > 0 ? 'almaseo-text-warning' : 'almaseo-text-success'; ?>">
                                <?php echo $scan_status['issues']; ?>
                            </div>
                            <div class="almaseo-stat-label"><?php _e('Issues Found', 'almaseo'); ?></div>
                        </div>
                    </div>
                    
                    <div class="almaseo-button-group">
                        <?php if ($scan_status['issues'] > 0) : ?>
                        <button type="button" class="button" id="view-conflicts-btn">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php _e('View Details', 'almaseo'); ?>
                        </button>
                        <button type="button" class="button" id="export-conflicts-csv">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Export CSV', 'almaseo'); ?>
                        </button>
                        <?php endif; ?>
                        <button type="button" class="button" id="rescan-conflicts">
                            <span class="dashicons dashicons-update"></span>
                            <?php _e('Rescan', 'almaseo'); ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Conflict Results Table -->
            <div id="conflicts-table" style="display:none;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Issue Type', 'almaseo'); ?></th>
                            <th><?php _e('Description', 'almaseo'); ?></th>
                            <th><?php _e('Plugin/Theme', 'almaseo'); ?></th>
                            <th><?php _e('Impact', 'almaseo'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="conflicts-tbody">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Change Tracking / Diff Report -->
    <div class="almaseo-card">
        <div class="almaseo-card-header">
            <h2><?php _e('Change Tracking', 'almaseo'); ?></h2>
            <div class="almaseo-button-group">
                <button type="button" class="button button-secondary" id="create-snapshot-btn">
                    <span class="dashicons dashicons-camera"></span>
                    <?php _e('Snapshot', 'almaseo'); ?>
                </button>
            </div>
        </div>
        <div class="almaseo-card-body">
            <?php if (empty($diff_summary)) : ?>
                <div class="almaseo-empty-state">
                    <span class="dashicons dashicons-chart-line"></span>
                    <p><?php _e('No snapshots created yet', 'almaseo'); ?></p>
                    <p class="description"><?php _e('Create snapshots to track changes over time', 'almaseo'); ?></p>
                </div>
            <?php else : ?>
                <div class="almaseo-diff-summary">
                    <div class="almaseo-stat-grid">
                        <div class="almaseo-stat">
                            <div class="almaseo-stat-value almaseo-text-success">+<?php echo $diff_summary['added']; ?></div>
                            <div class="almaseo-stat-label"><?php _e('Added', 'almaseo'); ?></div>
                        </div>
                        <div class="almaseo-stat">
                            <div class="almaseo-stat-value almaseo-text-danger">-<?php echo $diff_summary['removed']; ?></div>
                            <div class="almaseo-stat-label"><?php _e('Removed', 'almaseo'); ?></div>
                        </div>
                        <div class="almaseo-stat">
                            <div class="almaseo-stat-value almaseo-text-info">~<?php echo $diff_summary['changed']; ?></div>
                            <div class="almaseo-stat-label"><?php _e('Changed', 'almaseo'); ?></div>
                        </div>
                    </div>
                    <div class="almaseo-button-group">
                        <button type="button" class="button" id="compare-snapshots-btn">
                            <span class="dashicons dashicons-analytics"></span>
                            <?php _e('Compare', 'almaseo'); ?>
                        </button>
                        <?php if ($diff_summary['added'] + $diff_summary['removed'] + $diff_summary['changed'] > 0) : ?>
                        <button type="button" class="button" id="export-diff-csv">
                            <span class="dashicons dashicons-download"></span>
                            <?php _e('Export Report', 'almaseo'); ?>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Diff Details Table -->
            <div id="diff-details" style="display:none;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('URL', 'almaseo'); ?></th>
                            <th><?php _e('Change Type', 'almaseo'); ?></th>
                            <th><?php _e('Date', 'almaseo'); ?></th>
                            <th><?php _e('Details', 'almaseo'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="diff-details-tbody">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Health Log -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('Health Log', 'almaseo'); ?></h2>
        <div class="almaseo-button-group">
            <button type="button" class="button almaseo-button-secondary" id="export-logs-btn">
                <span class="dashicons dashicons-download"></span>
                <?php _e('Export CSV', 'almaseo'); ?>
            </button>
            <button type="button" class="button" id="clear-logs-btn">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Clear Logs', 'almaseo'); ?>
            </button>
            <button type="button" class="button" id="refresh-logs-btn">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Refresh', 'almaseo'); ?>
            </button>
        </div>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-log-stats">
            <div class="almaseo-stat-row">
                <span class="almaseo-stat-item">
                    <strong><?php echo number_format($log_stats['total']); ?></strong>
                    <?php _e('events total', 'almaseo'); ?>
                </span>
                <span class="almaseo-stat-item">
                    <strong><?php echo number_format($log_stats['last_24h']); ?></strong>
                    <?php _e('in last 24h', 'almaseo'); ?>
                </span>
            </div>
        </div>
        
        <?php if (!empty($logs)): ?>
        <div class="almaseo-log-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php _e('Time', 'almaseo'); ?></th>
                        <th><?php _e('Type', 'almaseo'); ?></th>
                        <th><?php _e('Message', 'almaseo'); ?></th>
                        <th><?php _e('Context', 'almaseo'); ?></th>
                    </tr>
                </thead>
                <tbody id="health-log-tbody">
                    <?php foreach ($logs as $log): ?>
                    <tr class="almaseo-log-entry">
                        <td>
                            <small><?php echo human_time_diff($log['timestamp']); ?> <?php _e('ago', 'almaseo'); ?></small>
                            <br>
                            <code><?php echo date('Y-m-d H:i:s', $log['timestamp']); ?></code>
                        </td>
                        <td>
                            <span class="almaseo-log-type almaseo-log-<?php echo esc_attr($log['type']); ?>">
                                <?php echo esc_html(ucfirst($log['type'])); ?>
                            </span>
                        </td>
                        <td><?php echo esc_html($log['message']); ?></td>
                        <td>
                            <?php if (!empty($log['context'])): ?>
                            <details>
                                <summary><?php _e('View Details', 'almaseo'); ?></summary>
                                <pre class="almaseo-log-context"><?php echo esc_html(json_encode($log['context'], JSON_PRETTY_PRINT)); ?></pre>
                            </details>
                            <?php else: ?>
                            <span class="almaseo-text-muted">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if (count($logs) >= 20): ?>
            <div class="almaseo-load-more">
                <button type="button" class="button" id="load-more-logs">
                    <?php _e('Load More Logs', 'almaseo'); ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="almaseo-empty-state">
            <span class="dashicons dashicons-admin-page"></span>
            <p><?php _e('No events logged yet', 'almaseo'); ?></p>
            <p class="description"><?php _e('Sitemap operations and issues will be logged here', 'almaseo'); ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Log Filtering -->
        <div class="almaseo-log-filters">
            <div class="almaseo-form-row">
                <div class="almaseo-form-group">
                    <label for="log-type-filter"><?php _e('Filter by Type:', 'almaseo'); ?></label>
                    <select id="log-type-filter" class="almaseo-select">
                        <option value=""><?php _e('All Types', 'almaseo'); ?></option>
                        <option value="error"><?php _e('Errors', 'almaseo'); ?></option>
                        <option value="warning"><?php _e('Warnings', 'almaseo'); ?></option>
                        <option value="info"><?php _e('Info', 'almaseo'); ?></option>
                        <option value="success"><?php _e('Success', 'almaseo'); ?></option>
                    </select>
                </div>
                <div class="almaseo-form-group">
                    <label for="log-date-filter"><?php _e('Filter by Date:', 'almaseo'); ?></label>
                    <select id="log-date-filter" class="almaseo-select">
                        <option value=""><?php _e('All Time', 'almaseo'); ?></option>
                        <option value="today"><?php _e('Today', 'almaseo'); ?></option>
                        <option value="week"><?php _e('Last 7 Days', 'almaseo'); ?></option>
                        <option value="month"><?php _e('Last 30 Days', 'almaseo'); ?></option>
                    </select>
                </div>
                <div class="almaseo-form-group">
                    <button type="button" class="button" id="apply-log-filters">
                        <?php _e('Apply Filters', 'almaseo'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>