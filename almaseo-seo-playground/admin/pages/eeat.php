<?php
/**
 * E-E-A-T Enforcement – Admin Page Template
 *
 * Renders the findings table and scan controls.
 * Data is loaded client-side via the REST API.
 *
 * @package AlmaSEO
 * @since   7.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap almaseo-eeat-wrap">

    <h1 class="almaseo-eeat-title"><?php esc_html_e( 'E-E-A-T Enforcement', 'almaseo' ); ?></h1>

    <!-- Feature Intro -->
    <div class="almaseo-eeat-intro">
        <p class="almaseo-eeat-intro-lead">
            <?php esc_html_e( 'Scan your content for missing trust signals that Google looks for: author attribution, credentials, biographical info, and citation links.', 'almaseo' ); ?>
        </p>

        <div class="almaseo-eeat-how-it-works">
            <h3><?php esc_html_e( 'How it works', 'almaseo' ); ?></h3>
            <ol>
                <li><?php esc_html_e( 'Click Scan Now to check all published content for E-E-A-T gaps.', 'almaseo' ); ?></li>
                <li><?php esc_html_e( 'Review findings — missing authors, empty bios, no credentials, missing schema, and absent citations.', 'almaseo' ); ?></li>
                <li><?php esc_html_e( 'Fix issues to strengthen Experience, Expertise, Authoritativeness, and Trustworthiness.', 'almaseo' ); ?></li>
            </ol>
        </div>

        <p class="almaseo-eeat-intro-note">
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e( 'E-E-A-T is especially important for YMYL (Your Money or Your Life) content. Configure YMYL categories in Settings below for stricter checks.', 'almaseo' ); ?>
        </p>
    </div>

    <!-- Summary Cards -->
    <div class="almaseo-eeat-stats" id="almaseo-eeat-stats">
        <div class="almaseo-eeat-stat-card">
            <span class="dashicons dashicons-groups"></span>
            <div class="almaseo-eeat-stat-value" id="almaseo-eeat-total">—</div>
            <div class="almaseo-eeat-stat-label"><?php esc_html_e( 'Total Findings', 'almaseo' ); ?></div>
        </div>
        <div class="almaseo-eeat-stat-card almaseo-eeat-stat-high">
            <span class="dashicons dashicons-warning"></span>
            <div class="almaseo-eeat-stat-value" id="almaseo-eeat-high">—</div>
            <div class="almaseo-eeat-stat-label"><?php esc_html_e( 'High Severity', 'almaseo' ); ?></div>
        </div>
        <div class="almaseo-eeat-stat-card almaseo-eeat-stat-medium">
            <span class="dashicons dashicons-info"></span>
            <div class="almaseo-eeat-stat-value" id="almaseo-eeat-medium">—</div>
            <div class="almaseo-eeat-stat-label"><?php esc_html_e( 'Medium Severity', 'almaseo' ); ?></div>
        </div>
        <div class="almaseo-eeat-stat-card almaseo-eeat-stat-low">
            <span class="dashicons dashicons-marker"></span>
            <div class="almaseo-eeat-stat-value" id="almaseo-eeat-low">—</div>
            <div class="almaseo-eeat-stat-label"><?php esc_html_e( 'Low Severity', 'almaseo' ); ?></div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="almaseo-eeat-actions">
        <button id="almaseo-eeat-scan" class="button button-primary">
            <span class="dashicons dashicons-search"></span>
            <?php esc_html_e( 'Scan Now', 'almaseo' ); ?>
        </button>

        <select id="almaseo-eeat-status-filter" class="almaseo-eeat-select">
            <option value=""><?php esc_html_e( 'All statuses', 'almaseo' ); ?></option>
            <option value="open"><?php esc_html_e( 'Open', 'almaseo' ); ?></option>
            <option value="resolved"><?php esc_html_e( 'Resolved', 'almaseo' ); ?></option>
            <option value="dismissed"><?php esc_html_e( 'Dismissed', 'almaseo' ); ?></option>
        </select>

        <select id="almaseo-eeat-severity-filter" class="almaseo-eeat-select">
            <option value=""><?php esc_html_e( 'All severities', 'almaseo' ); ?></option>
            <option value="high"><?php esc_html_e( 'High', 'almaseo' ); ?></option>
            <option value="medium"><?php esc_html_e( 'Medium', 'almaseo' ); ?></option>
            <option value="low"><?php esc_html_e( 'Low', 'almaseo' ); ?></option>
        </select>

        <select id="almaseo-eeat-type-filter" class="almaseo-eeat-select">
            <option value=""><?php esc_html_e( 'All types', 'almaseo' ); ?></option>
            <option value="missing_author"><?php esc_html_e( 'Missing Author', 'almaseo' ); ?></option>
            <option value="missing_bio"><?php esc_html_e( 'Missing Bio', 'almaseo' ); ?></option>
            <option value="missing_author_schema"><?php esc_html_e( 'Missing Author Schema', 'almaseo' ); ?></option>
            <option value="missing_credentials"><?php esc_html_e( 'Missing Credentials', 'almaseo' ); ?></option>
            <option value="no_sources"><?php esc_html_e( 'No Sources', 'almaseo' ); ?></option>
            <option value="missing_review_date"><?php esc_html_e( 'Missing Review Date', 'almaseo' ); ?></option>
        </select>

        <span class="almaseo-eeat-last-scan" id="almaseo-eeat-last-scan"></span>
    </div>

    <!-- Table -->
    <table class="widefat striped almaseo-eeat-table" id="almaseo-eeat-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Post', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Author', 'almaseo' ); ?></th>
                <th class="almaseo-eeat-col-finding"><?php esc_html_e( 'Finding', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Severity', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Status', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'almaseo' ); ?></th>
            </tr>
        </thead>
        <tbody id="almaseo-eeat-tbody">
            <tr><td colspan="6"><?php esc_html_e( 'Loading...', 'almaseo' ); ?></td></tr>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="almaseo-eeat-pagination" id="almaseo-eeat-pagination"></div>

    <!-- Settings Panel -->
    <details class="almaseo-eeat-settings" id="almaseo-eeat-settings">
        <summary class="almaseo-eeat-settings-toggle">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php esc_html_e( 'Scan Settings', 'almaseo' ); ?>
        </summary>
        <div class="almaseo-eeat-settings-body">
            <p class="almaseo-eeat-settings-desc">
                <?php esc_html_e( 'Configure how the E-E-A-T scanner evaluates your content. After changing settings, run a new scan to apply them.', 'almaseo' ); ?>
            </p>
            <div class="almaseo-eeat-setting-row">
                <label for="almaseo-eeat-s-posttypes"><?php esc_html_e( 'Post Types to Scan', 'almaseo' ); ?></label>
                <input type="text" id="almaseo-eeat-s-posttypes" value="post,page,product" style="width:300px;">
                <span class="almaseo-eeat-setting-help"><?php esc_html_e( 'Comma-separated post types to include in scans. Default: post, page, product.', 'almaseo' ); ?></span>
            </div>
            <div class="almaseo-eeat-setting-row">
                <label for="almaseo-eeat-s-generics"><?php esc_html_e( 'Generic Usernames', 'almaseo' ); ?></label>
                <input type="text" id="almaseo-eeat-s-generics" value="admin,editor,webmaster" style="width:300px;">
                <span class="almaseo-eeat-setting-help"><?php esc_html_e( 'Comma-separated usernames treated as generic (flagged as missing author).', 'almaseo' ); ?></span>
            </div>
            <div class="almaseo-eeat-setting-row">
                <label>
                    <input type="checkbox" id="almaseo-eeat-s-sources" checked>
                    <?php esc_html_e( 'Check for citation sources (outbound links)', 'almaseo' ); ?>
                </label>
            </div>
            <div class="almaseo-eeat-setting-row">
                <label>
                    <input type="checkbox" id="almaseo-eeat-s-review">
                    <?php esc_html_e( 'Check for review/fact-check attribution (YMYL posts only)', 'almaseo' ); ?>
                </label>
            </div>
            <div class="almaseo-eeat-setting-row">
                <label for="almaseo-eeat-s-ymyl"><?php esc_html_e( 'YMYL Categories', 'almaseo' ); ?></label>
                <input type="text" id="almaseo-eeat-s-ymyl" value="" style="width:300px;" placeholder="health,finance,legal">
                <span class="almaseo-eeat-setting-help"><?php esc_html_e( 'Comma-separated category slugs. Posts in these categories get stricter checks.', 'almaseo' ); ?></span>
            </div>
            <div class="almaseo-eeat-setting-row">
                <label for="almaseo-eeat-s-weight"><?php esc_html_e( 'Health Score Weight', 'almaseo' ); ?></label>
                <input type="number" id="almaseo-eeat-s-weight" min="0" max="20" value="0" style="width:70px;">
                <span class="almaseo-eeat-setting-help"><?php esc_html_e( 'Add E-E-A-T as a health signal (0 = disabled, 1–20 = weight). Other weights auto-adjust.', 'almaseo' ); ?></span>
            </div>
            <button id="almaseo-eeat-save-settings" class="button button-primary"><?php esc_html_e( 'Save Settings', 'almaseo' ); ?></button>
            <span id="almaseo-eeat-settings-status" class="almaseo-eeat-settings-status"></span>
        </div>
    </details>

</div>
