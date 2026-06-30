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

    <?php
    // A silently auto-generated app password with no dashboard linkage is NOT a
    // connection (see almaseo_get_connection_status()), so this agrees with the
    // rest of the UI: a free / no-account user is treated as not connected.
    $is_connected = function_exists( 'seo_playground_is_alma_connected' )
        ? seo_playground_is_alma_connected()
        : (bool) get_option( 'almaseo_app_password', '' );
    ?>

    <h1 class="almaseo-gsc-title"><?php esc_html_e( 'GSC Monitor', 'almaseo-seo-playground' ); ?></h1>

    <!-- Feature Intro -->
    <div class="almaseo-gsc-intro">
        <p class="almaseo-gsc-intro-lead">
            <?php esc_html_e( 'Track indexation drift, rich result changes, and snippet rewrites detected from your Google Search Console data.', 'almaseo-seo-playground' ); ?>
        </p>

        <div class="almaseo-gsc-how-it-works">
            <h3><?php esc_html_e( 'How it works', 'almaseo-seo-playground' ); ?></h3>
            <ol>
                <li><?php esc_html_e( 'The AlmaSEO dashboard monitors your GSC data for indexation changes, rich result losses, and title/description rewrites.', 'almaseo-seo-playground' ); ?></li>
                <li><?php esc_html_e( 'Findings are pushed automatically to your site and organized by type in the tabs below.', 'almaseo-seo-playground' ); ?></li>
                <li><?php esc_html_e( 'Review findings, resolve issues as you fix them, or dismiss false positives.', 'almaseo-seo-playground' ); ?></li>
            </ol>
        </div>

        <p class="almaseo-gsc-intro-note">
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e( 'GSC Monitor complements the Health Score and Evergreen modules: it catches external visibility changes that on-page analysis cannot detect.', 'almaseo-seo-playground' ); ?>
        </p>
    </div>

    <?php if ( ! $is_connected ) : ?>

    <!-- Free / not-connected: calm sign-up CTA in place of the empty, non-functional monitor UI -->
    <div class="almaseo-gsc-connect-card" style="max-width:620px;margin:24px 0;padding:32px;background:#fff;border:1px solid #dcdcde;border-left:4px solid #4f46e5;border-radius:8px;text-align:center;">
        <span class="dashicons dashicons-chart-line" style="font-size:48px;width:48px;height:48px;color:#4f46e5;"></span>
        <h2 style="margin:12px 0 8px;font-size:20px;"><?php esc_html_e( 'Monitor your Google Search visibility', 'almaseo-seo-playground' ); ?></h2>
        <p style="color:#555;font-size:14px;max-width:480px;margin:0 auto 20px;line-height:1.6;">
            <?php esc_html_e( 'GSC Monitor watches your Google Search Console data for indexation drops, lost rich results, and Google rewriting your titles & descriptions — then flags them here so you can act. Connect your site to AlmaSEO to turn it on.', 'almaseo-seo-playground' ); ?>
        </p>
        <a href="https://app.almaseo.com/register" target="_blank" rel="noopener" class="button button-primary button-hero"><?php esc_html_e( 'Sign up free', 'almaseo-seo-playground' ); ?> &rarr;</a>
        <p style="margin:14px 0 0;font-size:13px;color:#666;">
            <?php esc_html_e( 'Already have an account?', 'almaseo-seo-playground' ); ?>
            <a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-playground-connection' ) ); ?>"><?php esc_html_e( 'Connect it', 'almaseo-seo-playground' ); ?></a>
        </p>
    </div>

    <?php else : ?>

    <!-- Tabs -->
    <div class="almaseo-gsc-tabs" id="almaseo-gsc-tabs">
        <button class="almaseo-gsc-tab active" data-type="indexation_drift">
            <span class="dashicons dashicons-admin-site-alt3"></span>
            <?php esc_html_e( 'Indexation', 'almaseo-seo-playground' ); ?>
            <span class="almaseo-gsc-tab-count" id="almaseo-gsc-tab-count-indexation_drift"></span>
        </button>
        <button class="almaseo-gsc-tab" data-type="rich_result_loss">
            <span class="dashicons dashicons-star-filled"></span>
            <?php esc_html_e( 'Rich Results', 'almaseo-seo-playground' ); ?>
            <span class="almaseo-gsc-tab-count" id="almaseo-gsc-tab-count-rich_result_loss"></span>
        </button>
        <button class="almaseo-gsc-tab" data-type="snippet_rewrite">
            <span class="dashicons dashicons-editor-paste-text"></span>
            <?php esc_html_e( 'Snippets', 'almaseo-seo-playground' ); ?>
            <span class="almaseo-gsc-tab-count" id="almaseo-gsc-tab-count-snippet_rewrite"></span>
        </button>
    </div>

    <!-- Summary Cards -->
    <div class="almaseo-gsc-stats" id="almaseo-gsc-stats">
        <div class="almaseo-gsc-stat-card">
            <span class="dashicons dashicons-visibility"></span>
            <div class="almaseo-gsc-stat-value" id="almaseo-gsc-open">—</div>
            <div class="almaseo-gsc-stat-label"><?php esc_html_e( 'Open', 'almaseo-seo-playground' ); ?></div>
        </div>
        <div class="almaseo-gsc-stat-card almaseo-gsc-stat-high">
            <span class="dashicons dashicons-warning"></span>
            <div class="almaseo-gsc-stat-value" id="almaseo-gsc-high">—</div>
            <div class="almaseo-gsc-stat-label"><?php esc_html_e( 'High Severity', 'almaseo-seo-playground' ); ?></div>
        </div>
        <div class="almaseo-gsc-stat-card almaseo-gsc-stat-medium">
            <span class="dashicons dashicons-info"></span>
            <div class="almaseo-gsc-stat-value" id="almaseo-gsc-medium">—</div>
            <div class="almaseo-gsc-stat-label"><?php esc_html_e( 'Medium Severity', 'almaseo-seo-playground' ); ?></div>
        </div>
        <div class="almaseo-gsc-stat-card almaseo-gsc-stat-low">
            <span class="dashicons dashicons-marker"></span>
            <div class="almaseo-gsc-stat-value" id="almaseo-gsc-low">—</div>
            <div class="almaseo-gsc-stat-label"><?php esc_html_e( 'Low Severity', 'almaseo-seo-playground' ); ?></div>
        </div>
        <div class="almaseo-gsc-stat-card">
            <span class="dashicons dashicons-yes-alt"></span>
            <div class="almaseo-gsc-stat-value" id="almaseo-gsc-resolved">—</div>
            <div class="almaseo-gsc-stat-label"><?php esc_html_e( 'Resolved', 'almaseo-seo-playground' ); ?></div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="almaseo-gsc-actions">
        <select id="almaseo-gsc-status-filter" class="almaseo-gsc-select">
            <option value=""><?php esc_html_e( 'All statuses', 'almaseo-seo-playground' ); ?></option>
            <option value="open"><?php esc_html_e( 'Open', 'almaseo-seo-playground' ); ?></option>
            <option value="resolved"><?php esc_html_e( 'Resolved', 'almaseo-seo-playground' ); ?></option>
            <option value="dismissed"><?php esc_html_e( 'Dismissed', 'almaseo-seo-playground' ); ?></option>
        </select>

        <select id="almaseo-gsc-severity-filter" class="almaseo-gsc-select">
            <option value=""><?php esc_html_e( 'All severities', 'almaseo-seo-playground' ); ?></option>
            <option value="high"><?php esc_html_e( 'High', 'almaseo-seo-playground' ); ?></option>
            <option value="medium"><?php esc_html_e( 'Medium', 'almaseo-seo-playground' ); ?></option>
            <option value="low"><?php esc_html_e( 'Low', 'almaseo-seo-playground' ); ?></option>
        </select>

        <select id="almaseo-gsc-subtype-filter" class="almaseo-gsc-select">
            <option value=""><?php esc_html_e( 'All subtypes', 'almaseo-seo-playground' ); ?></option>
            <!-- Populated dynamically per tab -->
        </select>

        <input type="text" id="almaseo-gsc-search" class="almaseo-gsc-search" placeholder="<?php esc_attr_e( 'Search URL or title...', 'almaseo-seo-playground' ); ?>">

        <!-- Bulk actions -->
        <select id="almaseo-gsc-bulk-action" class="almaseo-gsc-select">
            <option value=""><?php esc_html_e( 'Bulk actions', 'almaseo-seo-playground' ); ?></option>
            <option value="resolve"><?php esc_html_e( 'Resolve selected', 'almaseo-seo-playground' ); ?></option>
            <option value="dismiss"><?php esc_html_e( 'Dismiss selected', 'almaseo-seo-playground' ); ?></option>
        </select>
        <button id="almaseo-gsc-bulk-apply" class="button"><?php esc_html_e( 'Apply', 'almaseo-seo-playground' ); ?></button>
    </div>

    <!-- Table -->
    <table class="widefat striped almaseo-gsc-table" id="almaseo-gsc-table">
        <thead>
            <tr>
                <th class="almaseo-gsc-col-cb"><input type="checkbox" id="almaseo-gsc-select-all"></th>
                <th><?php esc_html_e( 'Page', 'almaseo-seo-playground' ); ?></th>
                <th><?php esc_html_e( 'Subtype', 'almaseo-seo-playground' ); ?></th>
                <th class="almaseo-gsc-col-detail"><?php esc_html_e( 'Details', 'almaseo-seo-playground' ); ?></th>
                <th><?php esc_html_e( 'Severity', 'almaseo-seo-playground' ); ?></th>
                <th><?php esc_html_e( 'Last Seen', 'almaseo-seo-playground' ); ?></th>
                <th><?php esc_html_e( 'Status', 'almaseo-seo-playground' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'almaseo-seo-playground' ); ?></th>
            </tr>
        </thead>
        <tbody id="almaseo-gsc-tbody">
            <tr><td colspan="8"><?php esc_html_e( 'Loading...', 'almaseo-seo-playground' ); ?></td></tr>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="almaseo-gsc-pagination" id="almaseo-gsc-pagination"></div>

    <!-- Settings Panel -->
    <details class="almaseo-gsc-settings" id="almaseo-gsc-settings">
        <summary class="almaseo-gsc-settings-toggle">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php esc_html_e( 'Monitor Settings', 'almaseo-seo-playground' ); ?>
        </summary>
        <div class="almaseo-gsc-settings-body">
            <p class="almaseo-gsc-settings-desc">
                <?php esc_html_e( 'Configure alert thresholds and auto-dismiss behavior for GSC findings.', 'almaseo-seo-playground' ); ?>
            </p>
            <div class="almaseo-gsc-setting-row">
                <label for="almaseo-gsc-s-indexation"><?php esc_html_e( 'Indexation Alert Threshold', 'almaseo-seo-playground' ); ?></label>
                <input type="number" id="almaseo-gsc-s-indexation" min="1" max="100" value="5">
                <span class="almaseo-gsc-setting-help"><?php esc_html_e( 'Minimum number of pages affected before alerting.', 'almaseo-seo-playground' ); ?></span>
            </div>
            <div class="almaseo-gsc-setting-row">
                <label for="almaseo-gsc-s-snippet"><?php esc_html_e( 'Snippet Alert Threshold', 'almaseo-seo-playground' ); ?></label>
                <input type="number" id="almaseo-gsc-s-snippet" min="1" max="1000" value="100">
                <span class="almaseo-gsc-setting-help"><?php esc_html_e( 'Minimum impressions before flagging snippet rewrites.', 'almaseo-seo-playground' ); ?></span>
            </div>
            <div class="almaseo-gsc-setting-row">
                <label for="almaseo-gsc-s-autodismiss"><?php esc_html_e( 'Auto-dismiss after (days)', 'almaseo-seo-playground' ); ?></label>
                <input type="number" id="almaseo-gsc-s-autodismiss" min="0" max="365" value="0">
                <span class="almaseo-gsc-setting-help"><?php esc_html_e( 'Set to 0 to disable. Open findings older than this are auto-dismissed.', 'almaseo-seo-playground' ); ?></span>
            </div>
            <button id="almaseo-gsc-save-settings" class="button button-primary"><?php esc_html_e( 'Save Settings', 'almaseo-seo-playground' ); ?></button>
            <span id="almaseo-gsc-settings-status" class="almaseo-gsc-settings-status"></span>
        </div>
    </details>

    <?php endif; ?>

</div>
