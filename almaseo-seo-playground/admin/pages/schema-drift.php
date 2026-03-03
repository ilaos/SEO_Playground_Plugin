<?php
/**
 * Schema Drift Monitor – Admin Page Template
 *
 * Two-phase workflow:
 *   1. Capture baseline schemas from live pages.
 *   2. Scan for drift against the stored baseline.
 *
 * @package AlmaSEO
 * @since   7.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap almaseo-sd-wrap">

    <h1 class="almaseo-sd-title"><?php esc_html_e( 'Schema Drift Monitor', 'almaseo' ); ?></h1>

    <!-- Feature Intro -->
    <div class="almaseo-sd-intro">
        <p class="almaseo-sd-intro-lead">
            <?php esc_html_e( 'Your pages contain hidden code called "structured data" (or schema) that tells Google what your content is about — things like article titles, author names, product prices, and review ratings. When this code is correct, Google can show rich results (star ratings, FAQ dropdowns, recipe cards, etc.) in search. When it breaks or disappears, you can lose those rich results and the traffic that comes with them.', 'almaseo' ); ?>
        </p>
        <p class="almaseo-sd-intro-lead">
            <?php esc_html_e( 'The problem: plugin updates, theme changes, or settings tweaks can silently break your structured data without you noticing. Schema Drift Monitor watches for exactly that.', 'almaseo' ); ?>
        </p>

        <div class="almaseo-sd-how-it-works">
            <h3><?php esc_html_e( 'How to use it', 'almaseo' ); ?></h3>
            <ol>
                <li><?php esc_html_e( 'Click "Capture Baseline" — this saves a snapshot of the structured data currently on your pages. Think of it as taking a "before" photo. Do this when everything looks correct.', 'almaseo' ); ?></li>
                <li><?php esc_html_e( 'After a plugin update, theme change, or any time you want to check — click "Scan for Drift". This loads your pages again and compares the current structured data against the saved snapshot.', 'almaseo' ); ?></li>
                <li><?php esc_html_e( 'If anything changed, you will see it listed below: schemas that were removed (high severity), schemas that were modified, or new schemas that appeared unexpectedly. You can then investigate and fix the issue before it affects your search rankings.', 'almaseo' ); ?></li>
            </ol>
        </div>

        <p class="almaseo-sd-intro-note">
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e( 'Tip: If "Auto-scan on update" is enabled in Settings below, Schema Drift Monitor will automatically check for changes after every plugin or theme update — no manual scanning needed.', 'almaseo' ); ?>
        </p>
    </div>

    <!-- Summary Cards -->
    <div class="almaseo-sd-stats" id="almaseo-sd-stats">
        <div class="almaseo-sd-stat-card almaseo-sd-stat-baseline">
            <span class="dashicons dashicons-database"></span>
            <div class="almaseo-sd-stat-value" id="almaseo-sd-baseline-posts">—</div>
            <div class="almaseo-sd-stat-label"><?php esc_html_e( 'Baselined Posts', 'almaseo' ); ?></div>
        </div>
        <div class="almaseo-sd-stat-card almaseo-sd-stat-schemas">
            <span class="dashicons dashicons-editor-code"></span>
            <div class="almaseo-sd-stat-value" id="almaseo-sd-baseline-schemas">—</div>
            <div class="almaseo-sd-stat-label"><?php esc_html_e( 'Schema Types', 'almaseo' ); ?></div>
        </div>
        <div class="almaseo-sd-stat-card almaseo-sd-stat-open">
            <span class="dashicons dashicons-warning"></span>
            <div class="almaseo-sd-stat-value" id="almaseo-sd-open">—</div>
            <div class="almaseo-sd-stat-label"><?php esc_html_e( 'Open Findings', 'almaseo' ); ?></div>
        </div>
        <div class="almaseo-sd-stat-card almaseo-sd-stat-high">
            <span class="dashicons dashicons-dismiss"></span>
            <div class="almaseo-sd-stat-value" id="almaseo-sd-high">—</div>
            <div class="almaseo-sd-stat-label"><?php esc_html_e( 'High Severity', 'almaseo' ); ?></div>
        </div>
        <div class="almaseo-sd-stat-card almaseo-sd-stat-resolved">
            <span class="dashicons dashicons-yes-alt"></span>
            <div class="almaseo-sd-stat-value" id="almaseo-sd-resolved">—</div>
            <div class="almaseo-sd-stat-label"><?php esc_html_e( 'Resolved', 'almaseo' ); ?></div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="almaseo-sd-actions">
        <button id="almaseo-sd-capture" class="button button-secondary">
            <span class="dashicons dashicons-database"></span>
            <?php esc_html_e( 'Capture Baseline', 'almaseo' ); ?>
        </button>

        <button id="almaseo-sd-scan" class="button button-primary">
            <span class="dashicons dashicons-search"></span>
            <?php esc_html_e( 'Scan for Drift', 'almaseo' ); ?>
        </button>

        <select id="almaseo-sd-status-filter" class="almaseo-sd-select">
            <option value=""><?php esc_html_e( 'All statuses', 'almaseo' ); ?></option>
            <option value="open"><?php esc_html_e( 'Open', 'almaseo' ); ?></option>
            <option value="resolved"><?php esc_html_e( 'Resolved', 'almaseo' ); ?></option>
            <option value="dismissed"><?php esc_html_e( 'Dismissed', 'almaseo' ); ?></option>
        </select>

        <select id="almaseo-sd-severity-filter" class="almaseo-sd-select">
            <option value=""><?php esc_html_e( 'All severities', 'almaseo' ); ?></option>
            <option value="high"><?php esc_html_e( 'High', 'almaseo' ); ?></option>
            <option value="medium"><?php esc_html_e( 'Medium', 'almaseo' ); ?></option>
            <option value="low"><?php esc_html_e( 'Low', 'almaseo' ); ?></option>
        </select>

        <select id="almaseo-sd-type-filter" class="almaseo-sd-select">
            <option value=""><?php esc_html_e( 'All types', 'almaseo' ); ?></option>
            <option value="schema_removed"><?php esc_html_e( 'Removed', 'almaseo' ); ?></option>
            <option value="schema_modified"><?php esc_html_e( 'Modified', 'almaseo' ); ?></option>
            <option value="schema_added"><?php esc_html_e( 'Added', 'almaseo' ); ?></option>
            <option value="schema_error"><?php esc_html_e( 'Error', 'almaseo' ); ?></option>
        </select>

        <input type="text" id="almaseo-sd-search" class="almaseo-sd-search" placeholder="<?php esc_attr_e( 'Search by title...', 'almaseo' ); ?>">

        <span class="almaseo-sd-last-scan" id="almaseo-sd-last-scan"></span>
    </div>

    <!-- Table -->
    <table class="widefat striped almaseo-sd-table" id="almaseo-sd-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Page', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Drift Type', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Schema Type', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Severity', 'almaseo' ); ?></th>
                <th class="almaseo-sd-col-summary"><?php esc_html_e( 'Summary', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Status', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Detected', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'almaseo' ); ?></th>
            </tr>
        </thead>
        <tbody id="almaseo-sd-tbody">
            <tr><td colspan="8"><?php esc_html_e( 'Loading...', 'almaseo' ); ?></td></tr>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="almaseo-sd-pagination" id="almaseo-sd-pagination"></div>

    <!-- Settings Panel -->
    <details class="almaseo-sd-settings-panel" id="almaseo-sd-settings-panel">
        <summary><?php esc_html_e( 'Settings', 'almaseo' ); ?></summary>
        <div class="almaseo-sd-settings-inner">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="almaseo-sd-auto-scan"><?php esc_html_e( 'Auto-scan on update', 'almaseo' ); ?></label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="almaseo-sd-auto-scan" value="1">
                            <?php esc_html_e( 'Automatically scan for drift after plugin or theme updates', 'almaseo' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="almaseo-sd-post-types"><?php esc_html_e( 'Monitored post types', 'almaseo' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="almaseo-sd-post-types" class="regular-text" placeholder="post, page, product">
                        <p class="description"><?php esc_html_e( 'Comma-separated list of post types to monitor.', 'almaseo' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="almaseo-sd-sample-size"><?php esc_html_e( 'Sample size per type', 'almaseo' ); ?></label>
                    </th>
                    <td>
                        <input type="number" id="almaseo-sd-sample-size" class="small-text" min="1" max="50" value="3">
                        <p class="description"><?php esc_html_e( 'Number of random posts per type to include in baseline (1-50).', 'almaseo' ); ?></p>
                    </td>
                </tr>
            </table>
            <p>
                <button id="almaseo-sd-save-settings" class="button button-primary"><?php esc_html_e( 'Save Settings', 'almaseo' ); ?></button>
                <span id="almaseo-sd-settings-status" class="almaseo-sd-settings-status"></span>
            </p>
        </div>
    </details>

</div>
