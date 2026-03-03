<?php
/**
 * GSC Monitor – Admin Page Template
 *
 * Tabbed UI: Indexation | Rich Results | Snippets.
 * Data is loaded client-side via the REST API, filtered per active tab.
 *
 * @package AlmaSEO
 * @since   7.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap almaseo-gsc-wrap">

    <h1 class="almaseo-gsc-title"><?php esc_html_e( 'GSC Monitor', 'almaseo' ); ?></h1>

    <!-- Feature Intro -->
    <div class="almaseo-gsc-intro">
        <p class="almaseo-gsc-intro-lead">
            <?php esc_html_e( 'Track indexation drift, rich result changes, and snippet rewrites detected from your Google Search Console data.', 'almaseo' ); ?>
        </p>

        <div class="almaseo-gsc-how-it-works">
            <h3><?php esc_html_e( 'How it works', 'almaseo' ); ?></h3>
            <ol>
                <li><?php esc_html_e( 'The AlmaSEO dashboard monitors your GSC data for indexation changes, rich result losses, and title/description rewrites.', 'almaseo' ); ?></li>
                <li><?php esc_html_e( 'Findings are pushed automatically to your site and organized by type in the tabs below.', 'almaseo' ); ?></li>
                <li><?php esc_html_e( 'Review findings, resolve issues as you fix them, or dismiss false positives.', 'almaseo' ); ?></li>
            </ol>
        </div>

        <div class="almaseo-gsc-connection-notice">
            <span class="dashicons dashicons-warning"></span>
            <div>
                <strong><?php esc_html_e( 'Connection Required', 'almaseo' ); ?></strong>
                <p><?php esc_html_e( 'GSC Monitor does not scan locally — all data is pushed from the AlmaSEO dashboard. For findings to appear here, your site must be connected to AlmaSEO and your Google Search Console property must be linked in the dashboard. Without this connection, the table below will remain empty.', 'almaseo' ); ?></p>
                <?php
                $is_connected = (bool) get_option( 'almaseo_app_password', '' );
                if ( ! $is_connected ) : ?>
                    <p class="almaseo-gsc-connect-action">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=almaseo-settings' ) ); ?>" class="button button-primary button-small">
                            <?php esc_html_e( 'Connect to AlmaSEO', 'almaseo' ); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <p class="almaseo-gsc-intro-note">
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e( 'GSC Monitor complements the Health Score and Evergreen modules: it catches external visibility changes that on-page analysis cannot detect.', 'almaseo' ); ?>
        </p>
    </div>

    <!-- Tabs -->
    <div class="almaseo-gsc-tabs" id="almaseo-gsc-tabs">
        <button class="almaseo-gsc-tab active" data-type="indexation_drift">
            <span class="dashicons dashicons-admin-site-alt3"></span>
            <?php esc_html_e( 'Indexation', 'almaseo' ); ?>
            <span class="almaseo-gsc-tab-count" id="almaseo-gsc-tab-count-indexation_drift"></span>
        </button>
        <button class="almaseo-gsc-tab" data-type="rich_result_loss">
            <span class="dashicons dashicons-star-filled"></span>
            <?php esc_html_e( 'Rich Results', 'almaseo' ); ?>
            <span class="almaseo-gsc-tab-count" id="almaseo-gsc-tab-count-rich_result_loss"></span>
        </button>
        <button class="almaseo-gsc-tab" data-type="snippet_rewrite">
            <span class="dashicons dashicons-editor-paste-text"></span>
            <?php esc_html_e( 'Snippets', 'almaseo' ); ?>
            <span class="almaseo-gsc-tab-count" id="almaseo-gsc-tab-count-snippet_rewrite"></span>
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="almaseo-gsc-stats" id="almaseo-gsc-stats">
        <div class="almaseo-gsc-stat-card">
            <span class="dashicons dashicons-visibility"></span>
            <div class="almaseo-gsc-stat-value" id="almaseo-gsc-open">—</div>
            <div class="almaseo-gsc-stat-label"><?php esc_html_e( 'Open', 'almaseo' ); ?></div>
        </div>
        <div class="almaseo-gsc-stat-card almaseo-gsc-stat-high">
            <span class="dashicons dashicons-warning"></span>
            <div class="almaseo-gsc-stat-value" id="almaseo-gsc-high">—</div>
            <div class="almaseo-gsc-stat-label"><?php esc_html_e( 'High Severity', 'almaseo' ); ?></div>
        </div>
        <div class="almaseo-gsc-stat-card almaseo-gsc-stat-medium">
            <span class="dashicons dashicons-info"></span>
            <div class="almaseo-gsc-stat-value" id="almaseo-gsc-medium">—</div>
            <div class="almaseo-gsc-stat-label"><?php esc_html_e( 'Medium Severity', 'almaseo' ); ?></div>
        </div>
        <div class="almaseo-gsc-stat-card almaseo-gsc-stat-low">
            <span class="dashicons dashicons-marker"></span>
            <div class="almaseo-gsc-stat-value" id="almaseo-gsc-low">—</div>
            <div class="almaseo-gsc-stat-label"><?php esc_html_e( 'Low Severity', 'almaseo' ); ?></div>
        </div>
        <div class="almaseo-gsc-stat-card">
            <span class="dashicons dashicons-yes-alt"></span>
            <div class="almaseo-gsc-stat-value" id="almaseo-gsc-resolved">—</div>
            <div class="almaseo-gsc-stat-label"><?php esc_html_e( 'Resolved', 'almaseo' ); ?></div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="almaseo-gsc-actions">
        <select id="almaseo-gsc-status-filter" class="almaseo-gsc-select">
            <option value=""><?php esc_html_e( 'All statuses', 'almaseo' ); ?></option>
            <option value="open"><?php esc_html_e( 'Open', 'almaseo' ); ?></option>
            <option value="resolved"><?php esc_html_e( 'Resolved', 'almaseo' ); ?></option>
            <option value="dismissed"><?php esc_html_e( 'Dismissed', 'almaseo' ); ?></option>
        </select>

        <select id="almaseo-gsc-severity-filter" class="almaseo-gsc-select">
            <option value=""><?php esc_html_e( 'All severities', 'almaseo' ); ?></option>
            <option value="high"><?php esc_html_e( 'High', 'almaseo' ); ?></option>
            <option value="medium"><?php esc_html_e( 'Medium', 'almaseo' ); ?></option>
            <option value="low"><?php esc_html_e( 'Low', 'almaseo' ); ?></option>
        </select>

        <select id="almaseo-gsc-subtype-filter" class="almaseo-gsc-select">
            <option value=""><?php esc_html_e( 'All subtypes', 'almaseo' ); ?></option>
            <!-- Populated dynamically per tab -->
        </select>

        <input type="text" id="almaseo-gsc-search" class="almaseo-gsc-search" placeholder="<?php esc_attr_e( 'Search URL or title...', 'almaseo' ); ?>">

        <!-- Bulk actions -->
        <select id="almaseo-gsc-bulk-action" class="almaseo-gsc-select">
            <option value=""><?php esc_html_e( 'Bulk actions', 'almaseo' ); ?></option>
            <option value="resolve"><?php esc_html_e( 'Resolve selected', 'almaseo' ); ?></option>
            <option value="dismiss"><?php esc_html_e( 'Dismiss selected', 'almaseo' ); ?></option>
        </select>
        <button id="almaseo-gsc-bulk-apply" class="button"><?php esc_html_e( 'Apply', 'almaseo' ); ?></button>
    </div>

    <!-- Table -->
    <table class="widefat striped almaseo-gsc-table" id="almaseo-gsc-table">
        <thead>
            <tr>
                <th class="almaseo-gsc-col-cb"><input type="checkbox" id="almaseo-gsc-select-all"></th>
                <th><?php esc_html_e( 'Page', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Subtype', 'almaseo' ); ?></th>
                <th class="almaseo-gsc-col-detail"><?php esc_html_e( 'Details', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Severity', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Last Seen', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Status', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'almaseo' ); ?></th>
            </tr>
        </thead>
        <tbody id="almaseo-gsc-tbody">
            <tr><td colspan="8"><?php esc_html_e( 'Loading...', 'almaseo' ); ?></td></tr>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="almaseo-gsc-pagination" id="almaseo-gsc-pagination"></div>

    <!-- Settings Panel -->
    <details class="almaseo-gsc-settings" id="almaseo-gsc-settings">
        <summary class="almaseo-gsc-settings-toggle">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php esc_html_e( 'Monitor Settings', 'almaseo' ); ?>
        </summary>
        <div class="almaseo-gsc-settings-body">
            <p class="almaseo-gsc-settings-desc">
                <?php esc_html_e( 'Configure alert thresholds and auto-dismiss behavior for GSC findings.', 'almaseo' ); ?>
            </p>
            <div class="almaseo-gsc-setting-row">
                <label for="almaseo-gsc-s-indexation"><?php esc_html_e( 'Indexation Alert Threshold', 'almaseo' ); ?></label>
                <input type="number" id="almaseo-gsc-s-indexation" min="1" max="100" value="5">
                <span class="almaseo-gsc-setting-help"><?php esc_html_e( 'Minimum number of pages affected before alerting.', 'almaseo' ); ?></span>
            </div>
            <div class="almaseo-gsc-setting-row">
                <label for="almaseo-gsc-s-snippet"><?php esc_html_e( 'Snippet Alert Threshold', 'almaseo' ); ?></label>
                <input type="number" id="almaseo-gsc-s-snippet" min="1" max="1000" value="100">
                <span class="almaseo-gsc-setting-help"><?php esc_html_e( 'Minimum impressions before flagging snippet rewrites.', 'almaseo' ); ?></span>
            </div>
            <div class="almaseo-gsc-setting-row">
                <label for="almaseo-gsc-s-autodismiss"><?php esc_html_e( 'Auto-dismiss after (days)', 'almaseo' ); ?></label>
                <input type="number" id="almaseo-gsc-s-autodismiss" min="0" max="365" value="0">
                <span class="almaseo-gsc-setting-help"><?php esc_html_e( 'Set to 0 to disable. Open findings older than this are auto-dismissed.', 'almaseo' ); ?></span>
            </div>
            <button id="almaseo-gsc-save-settings" class="button button-primary"><?php esc_html_e( 'Save Settings', 'almaseo' ); ?></button>
            <span id="almaseo-gsc-settings-status" class="almaseo-gsc-settings-status"></span>
        </div>
    </details>

</div>
