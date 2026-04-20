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
        <h2><?php esc_html_e('Sitemap Validation', 'almaseo-seo-playground'); ?></h2>
        <div class="almaseo-button-group">
            <button type="button" class="button button-primary" id="validate-sitemap">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e('Validate Sitemap', 'almaseo-seo-playground'); ?>
            </button>
            <button type="button" class="button almaseo-button-secondary" id="validate-all">
                <span class="dashicons dashicons-portfolio"></span>
                <?php esc_html_e('Validate All', 'almaseo-seo-playground'); ?>
            </button>
        </div>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-info-box almaseo-info-default">
            <p>
                <span class="dashicons dashicons-shield-alt"></span>
                <?php esc_html_e('Validation checks your sitemap structure, XML syntax, URL accessibility, and compliance with sitemap protocols.', 'almaseo-seo-playground'); ?>
            </p>
        </div>
        
        <!-- Validation Results Container -->
        <div id="validation-results" style="display:none;">
            <div class="almaseo-validation-progress">
                <div class="almaseo-progress-bar">
                    <div class="almaseo-progress-fill" style="width: 0%"></div>
                </div>
                <p id="validation-status"><?php esc_html_e('Starting validation...', 'almaseo-seo-playground'); ?></p>
            </div>
            
            <div class="almaseo-validation-summary" style="display:none;">
                <div class="almaseo-stat-grid">
                    <div class="almaseo-stat">
                        <div class="almaseo-stat-value almaseo-text-success" id="valid-urls">0</div>
                        <div class="almaseo-stat-label"><?php esc_html_e('Valid URLs', 'almaseo-seo-playground'); ?></div>
                    </div>
                    <div class="almaseo-stat">
                        <div class="almaseo-stat-value almaseo-text-warning" id="warning-urls">0</div>
                        <div class="almaseo-stat-label"><?php esc_html_e('Warnings', 'almaseo-seo-playground'); ?></div>
                    </div>
                    <div class="almaseo-stat">
                        <div class="almaseo-stat-value almaseo-text-danger" id="error-urls">0</div>
                        <div class="almaseo-stat-label"><?php esc_html_e('Errors', 'almaseo-seo-playground'); ?></div>
                    </div>
                </div>
            </div>
            
            <!-- Validation Issues Table -->
            <div id="validation-issues" style="display:none;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('URL', 'almaseo-seo-playground'); ?></th>
                            <th><?php esc_html_e('Issue', 'almaseo-seo-playground'); ?></th>
                            <th><?php esc_html_e('Severity', 'almaseo-seo-playground'); ?></th>
                            <th><?php esc_html_e('Details', 'almaseo-seo-playground'); ?></th>
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
                <h4><?php esc_html_e('XML Structure', 'almaseo-seo-playground'); ?></h4>
                <p><?php esc_html_e('Checks for proper XML formatting and required elements', 'almaseo-seo-playground'); ?></p>
            </div>
            <div class="almaseo-tip">
                <h4><?php esc_html_e('URL Accessibility', 'almaseo-seo-playground'); ?></h4>
                <p><?php esc_html_e('Verifies that URLs return proper HTTP status codes', 'almaseo-seo-playground'); ?></p>
            </div>
            <div class="almaseo-tip">
                <h4><?php esc_html_e('Protocol Compliance', 'almaseo-seo-playground'); ?></h4>
                <p><?php esc_html_e('Ensures compliance with sitemap protocol standards', 'almaseo-seo-playground'); ?></p>
            </div>
        </div>
    </div>
</div>

<div class="almaseo-two-column">
    <!-- Conflict Scanner -->
    <div class="almaseo-card">
        <div class="almaseo-card-header">
            <h2><?php esc_html_e('Conflict Scanner', 'almaseo-seo-playground'); ?></h2>
            <div class="almaseo-button-group">
                <button type="button" class="button button-secondary" id="scan-conflicts-btn">
                    <span class="dashicons dashicons-search"></span>
                    <?php esc_html_e('Start Scan', 'almaseo-seo-playground'); ?>
                </button>
            </div>
        </div>
        <div class="almaseo-card-body">
            <?php if ($scan_status['status'] === 'idle') : ?>
                <div class="almaseo-empty-state">
                    <span class="dashicons dashicons-shield"></span>
                    <p><?php esc_html_e('No scan performed yet', 'almaseo-seo-playground'); ?></p>
                    <p class="description"><?php esc_html_e('Detect conflicting plugins and configuration issues', 'almaseo-seo-playground'); ?></p>
                </div>
            <?php elseif ($scan_status['status'] === 'running') : ?>
                <div class="almaseo-scan-progress">
                    <div class="almaseo-progress-bar">
                        <div class="almaseo-progress-fill" style="width: <?php echo $scan_status['progress']; ?>%"></div>
                    </div>
                    <p><?php
                    /* translators: %1$d: number of URLs checked so far, %2$d: total URLs to check */
                    printf(__('Scanning: %1$d/%2$d URLs checked', 'almaseo-seo-playground'), $scan_status['checked'], $scan_status['total']); ?></p>
                </div>
            <?php else : ?>
                <div class="almaseo-scan-results">
                    <div class="almaseo-stat-grid">
                        <div class="almaseo-stat">
                            <div class="almaseo-stat-value"><?php echo $scan_status['checked']; ?></div>
                            <div class="almaseo-stat-label"><?php esc_html_e('URLs Checked', 'almaseo-seo-playground'); ?></div>
                        </div>
                        <div class="almaseo-stat">
                            <div class="almaseo-stat-value <?php echo $scan_status['issues'] > 0 ? 'almaseo-text-warning' : 'almaseo-text-success'; ?>">
                                <?php echo $scan_status['issues']; ?>
                            </div>
                            <div class="almaseo-stat-label"><?php esc_html_e('Issues Found', 'almaseo-seo-playground'); ?></div>
                        </div>
                    </div>
                    
                    <div class="almaseo-button-group">
                        <?php if ($scan_status['issues'] > 0) : ?>
                        <button type="button" class="button" id="view-conflicts-btn">
                            <span class="dashicons dashicons-visibility"></span>
                            <?php esc_html_e('View Details', 'almaseo-seo-playground'); ?>
                        </button>
                        <button type="button" class="button" id="export-conflicts-csv">
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e('Export CSV', 'almaseo-seo-playground'); ?>
                        </button>
                        <?php endif; ?>
                        <button type="button" class="button" id="rescan-conflicts">
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e('Rescan', 'almaseo-seo-playground'); ?>
                        </button>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Conflict Results Table -->
            <div id="conflicts-table" style="display:none;">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Issue Type', 'almaseo-seo-playground'); ?></th>
                            <th><?php esc_html_e('Description', 'almaseo-seo-playground'); ?></th>
                            <th><?php esc_html_e('Plugin/Theme', 'almaseo-seo-playground'); ?></th>
                            <th><?php esc_html_e('Impact', 'almaseo-seo-playground'); ?></th>
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
            <h2><?php esc_html_e('Change Tracking', 'almaseo-seo-playground'); ?></h2>
            <div class="almaseo-button-group">
                <button type="button" class="button button-secondary" id="create-snapshot-btn">
                    <span class="dashicons dashicons-camera"></span>
                    <?php esc_html_e('Snapshot', 'almaseo-seo-playground'); ?>
                </button>
            </div>
        </div>
        <div class="almaseo-card-body">
            <?php if (empty($diff_summary)) : ?>
                <div class="almaseo-empty-state">
                    <span class="dashicons dashicons-chart-line"></span>
                    <p><?php esc_html_e('No snapshots created yet', 'almaseo-seo-playground'); ?></p>
                    <p class="description"><?php esc_html_e('Create snapshots to track changes over time', 'almaseo-seo-playground'); ?></p>
                </div>
            <?php else : ?>
                <div class="almaseo-diff-summary">
                    <div class="almaseo-stat-grid">
                        <div class="almaseo-stat">
                            <div class="almaseo-stat-value almaseo-text-success">+<?php echo $diff_summary['added']; ?></div>
                            <div class="almaseo-stat-label"><?php esc_html_e('Added', 'almaseo-seo-playground'); ?></div>
                        </div>
                        <div class="almaseo-stat">
                            <div class="almaseo-stat-value almaseo-text-danger">-<?php echo $diff_summary['removed']; ?></div>
                            <div class="almaseo-stat-label"><?php esc_html_e('Removed', 'almaseo-seo-playground'); ?></div>
                        </div>
                        <div class="almaseo-stat">
                            <div class="almaseo-stat-value almaseo-text-info">~<?php echo $diff_summary['changed']; ?></div>
                            <div class="almaseo-stat-label"><?php esc_html_e('Changed', 'almaseo-seo-playground'); ?></div>
                        </div>
                    </div>
                    <div class="almaseo-button-group">
                        <button type="button" class="button" id="compare-snapshots-btn">
                            <span class="dashicons dashicons-analytics"></span>
                            <?php esc_html_e('Compare', 'almaseo-seo-playground'); ?>
                        </button>
                        <?php if ($diff_summary['added'] + $diff_summary['removed'] + $diff_summary['changed'] > 0) : ?>
                        <button type="button" class="button" id="export-diff-csv">
                            <span class="dashicons dashicons-download"></span>
                            <?php esc_html_e('Export Report', 'almaseo-seo-playground'); ?>
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
                            <th><?php esc_html_e('URL', 'almaseo-seo-playground'); ?></th>
                            <th><?php esc_html_e('Change Type', 'almaseo-seo-playground'); ?></th>
                            <th><?php esc_html_e('Date', 'almaseo-seo-playground'); ?></th>
                            <th><?php esc_html_e('Details', 'almaseo-seo-playground'); ?></th>
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
        <h2><?php esc_html_e('Health Log', 'almaseo-seo-playground'); ?></h2>
        <div class="almaseo-button-group">
            <button type="button" class="button almaseo-button-secondary" id="export-logs-btn">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Export CSV', 'almaseo-seo-playground'); ?>
            </button>
            <button type="button" class="button" id="clear-logs-btn">
                <span class="dashicons dashicons-trash"></span>
                <?php esc_html_e('Clear Logs', 'almaseo-seo-playground'); ?>
            </button>
            <button type="button" class="button" id="refresh-logs-btn">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Refresh', 'almaseo-seo-playground'); ?>
            </button>
        </div>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-log-stats">
            <div class="almaseo-stat-row">
                <span class="almaseo-stat-item">
                    <strong><?php echo number_format($log_stats['total']); ?></strong>
                    <?php esc_html_e('events total', 'almaseo-seo-playground'); ?>
                </span>
                <span class="almaseo-stat-item">
                    <strong><?php echo number_format($log_stats['last_24h']); ?></strong>
                    <?php esc_html_e('in last 24h', 'almaseo-seo-playground'); ?>
                </span>
            </div>
        </div>
        
        <?php if (!empty($logs)): ?>
        <div class="almaseo-log-list">
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Time', 'almaseo-seo-playground'); ?></th>
                        <th><?php esc_html_e('Type', 'almaseo-seo-playground'); ?></th>
                        <th><?php esc_html_e('Message', 'almaseo-seo-playground'); ?></th>
                        <th><?php esc_html_e('Context', 'almaseo-seo-playground'); ?></th>
                    </tr>
                </thead>
                <tbody id="health-log-tbody">
                    <?php foreach ($logs as $log): ?>
                    <tr class="almaseo-log-entry">
                        <td>
                            <small><?php echo human_time_diff($log['timestamp']); ?> <?php esc_html_e('ago', 'almaseo-seo-playground'); ?></small>
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
                                <summary><?php esc_html_e('View Details', 'almaseo-seo-playground'); ?></summary>
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
                    <?php esc_html_e('Load More Logs', 'almaseo-seo-playground'); ?>
                </button>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="almaseo-empty-state">
            <span class="dashicons dashicons-admin-page"></span>
            <p><?php esc_html_e('No events logged yet', 'almaseo-seo-playground'); ?></p>
            <p class="description"><?php esc_html_e('Sitemap operations and issues will be logged here', 'almaseo-seo-playground'); ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Log Filtering -->
        <div class="almaseo-log-filters">
            <div class="almaseo-form-row">
                <div class="almaseo-form-group">
                    <label for="log-type-filter"><?php esc_html_e('Filter by Type:', 'almaseo-seo-playground'); ?></label>
                    <select id="log-type-filter" class="almaseo-select">
                        <option value=""><?php esc_html_e('All Types', 'almaseo-seo-playground'); ?></option>
                        <option value="error"><?php esc_html_e('Errors', 'almaseo-seo-playground'); ?></option>
                        <option value="warning"><?php esc_html_e('Warnings', 'almaseo-seo-playground'); ?></option>
                        <option value="info"><?php esc_html_e('Info', 'almaseo-seo-playground'); ?></option>
                        <option value="success"><?php esc_html_e('Success', 'almaseo-seo-playground'); ?></option>
                    </select>
                </div>
                <div class="almaseo-form-group">
                    <label for="log-date-filter"><?php esc_html_e('Filter by Date:', 'almaseo-seo-playground'); ?></label>
                    <select id="log-date-filter" class="almaseo-select">
                        <option value=""><?php esc_html_e('All Time', 'almaseo-seo-playground'); ?></option>
                        <option value="today"><?php esc_html_e('Today', 'almaseo-seo-playground'); ?></option>
                        <option value="week"><?php esc_html_e('Last 7 Days', 'almaseo-seo-playground'); ?></option>
                        <option value="month"><?php esc_html_e('Last 30 Days', 'almaseo-seo-playground'); ?></option>
                    </select>
                </div>
                <div class="almaseo-form-group">
                    <button type="button" class="button" id="apply-log-filters">
                        <?php esc_html_e('Apply Filters', 'almaseo-seo-playground'); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>