<?php
/**
 * Date Hygiene Scanner – Admin Page Template
 *
 * Renders the findings table and scan controls.
 * Data is loaded client-side via the REST API.
 *
 * @package AlmaSEO
 * @since   7.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap almaseo-dh-wrap">

    <h1 class="almaseo-dh-title"><?php esc_html_e( 'Date Hygiene Scanner', 'almaseo-seo-playground' ); ?></h1>

    <!-- Feature Intro -->
    <div class="almaseo-dh-intro">
        <p class="almaseo-dh-intro-lead">
            <?php esc_html_e( 'Find stale years, outdated prices, and time-decay problems hiding in your content.', 'almaseo-seo-playground' ); ?>
        </p>

        <div class="almaseo-dh-how-it-works">
            <h3><?php esc_html_e( 'How it works', 'almaseo-seo-playground' ); ?></h3>
            <ol>
                <li><?php esc_html_e( 'Click Scan Now to analyze all published content for time-sensitive references.', 'almaseo-seo-playground' ); ?></li>
                <li><?php esc_html_e( 'Review flagged passages — each shows the detected value, surrounding context, and a suggested fix.', 'almaseo-seo-playground' ); ?></li>
                <li><?php esc_html_e( 'Resolve findings as you update content, or dismiss false positives.', 'almaseo-seo-playground' ); ?></li>
            </ol>
        </div>

        <p class="almaseo-dh-intro-note">
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e( 'Date Hygiene complements the Evergreen module: Evergreen tells you when a post is aging, Date Hygiene tells you exactly which passages to fix.', 'almaseo-seo-playground' ); ?>
        </p>
    </div>

    <!-- Summary Cards -->
    <div class="almaseo-dh-stats" id="almaseo-dh-stats">
        <div class="almaseo-dh-stat-card">
            <span class="dashicons dashicons-search"></span>
            <div class="almaseo-dh-stat-value" id="almaseo-dh-total">—</div>
            <div class="almaseo-dh-stat-label"><?php esc_html_e( 'Total Findings', 'almaseo-seo-playground' ); ?></div>
        </div>
        <div class="almaseo-dh-stat-card almaseo-dh-stat-high">
            <span class="dashicons dashicons-warning"></span>
            <div class="almaseo-dh-stat-value" id="almaseo-dh-high">—</div>
            <div class="almaseo-dh-stat-label"><?php esc_html_e( 'High Severity', 'almaseo-seo-playground' ); ?></div>
        </div>
        <div class="almaseo-dh-stat-card almaseo-dh-stat-medium">
            <span class="dashicons dashicons-info"></span>
            <div class="almaseo-dh-stat-value" id="almaseo-dh-medium">—</div>
            <div class="almaseo-dh-stat-label"><?php esc_html_e( 'Medium Severity', 'almaseo-seo-playground' ); ?></div>
        </div>
        <div class="almaseo-dh-stat-card almaseo-dh-stat-low">
            <span class="dashicons dashicons-marker"></span>
            <div class="almaseo-dh-stat-value" id="almaseo-dh-low">—</div>
            <div class="almaseo-dh-stat-label"><?php esc_html_e( 'Low Severity', 'almaseo-seo-playground' ); ?></div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="almaseo-dh-actions">
        <button id="almaseo-dh-scan" class="button button-primary">
            <span class="dashicons dashicons-search"></span>
            <?php esc_html_e( 'Scan Now', 'almaseo-seo-playground' ); ?>
        </button>

        <select id="almaseo-dh-status-filter" class="almaseo-dh-select">
            <option value=""><?php esc_html_e( 'All statuses', 'almaseo-seo-playground' ); ?></option>
            <option value="open"><?php esc_html_e( 'Open', 'almaseo-seo-playground' ); ?></option>
            <option value="resolved"><?php esc_html_e( 'Resolved', 'almaseo-seo-playground' ); ?></option>
            <option value="dismissed"><?php esc_html_e( 'Dismissed', 'almaseo-seo-playground' ); ?></option>
        </select>

        <select id="almaseo-dh-severity-filter" class="almaseo-dh-select">
            <option value=""><?php esc_html_e( 'All severities', 'almaseo-seo-playground' ); ?></option>
            <option value="high"><?php esc_html_e( 'High', 'almaseo-seo-playground' ); ?></option>
            <option value="medium"><?php esc_html_e( 'Medium', 'almaseo-seo-playground' ); ?></option>
            <option value="low"><?php esc_html_e( 'Low', 'almaseo-seo-playground' ); ?></option>
        </select>

        <select id="almaseo-dh-type-filter" class="almaseo-dh-select">
            <option value=""><?php esc_html_e( 'All types', 'almaseo-seo-playground' ); ?></option>
            <option value="stale_year"><?php esc_html_e( 'Stale Year', 'almaseo-seo-playground' ); ?></option>
            <option value="dated_phrase"><?php esc_html_e( 'Dated Phrase', 'almaseo-seo-playground' ); ?></option>
            <option value="superlative_year"><?php esc_html_e( 'Superlative Year', 'almaseo-seo-playground' ); ?></option>
            <option value="price_reference"><?php esc_html_e( 'Price Reference', 'almaseo-seo-playground' ); ?></option>
            <option value="regulation_mention"><?php esc_html_e( 'Regulation Mention', 'almaseo-seo-playground' ); ?></option>
        </select>

        <span class="almaseo-dh-last-scan" id="almaseo-dh-last-scan"></span>
    </div>

    <!-- Table -->
    <table class="widefat striped almaseo-dh-table" id="almaseo-dh-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Post', 'almaseo-seo-playground' ); ?></th>
                <th class="almaseo-dh-col-context"><?php esc_html_e( 'Finding', 'almaseo-seo-playground' ); ?></th>
                <th><?php esc_html_e( 'Detected', 'almaseo-seo-playground' ); ?></th>
                <th><?php esc_html_e( 'Severity', 'almaseo-seo-playground' ); ?></th>
                <th><?php esc_html_e( 'Status', 'almaseo-seo-playground' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'almaseo-seo-playground' ); ?></th>
            </tr>
        </thead>
        <tbody id="almaseo-dh-tbody">
            <tr><td colspan="6"><?php esc_html_e( 'Loading...', 'almaseo-seo-playground' ); ?></td></tr>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="almaseo-dh-pagination" id="almaseo-dh-pagination"></div>

    <!-- Settings Panel -->
    <details class="almaseo-dh-settings" id="almaseo-dh-settings">
        <summary class="almaseo-dh-settings-toggle">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php esc_html_e( 'Scan Settings', 'almaseo-seo-playground' ); ?>
        </summary>
        <div class="almaseo-dh-settings-body">
            <p class="almaseo-dh-settings-desc">
                <?php esc_html_e( 'Configure how the scanner detects stale content. After changing settings, run a new scan to apply them.', 'almaseo-seo-playground' ); ?>
            </p>
            <div class="almaseo-dh-setting-row">
                <label for="almaseo-dh-s-posttypes"><?php esc_html_e( 'Post Types to Scan', 'almaseo-seo-playground' ); ?></label>
                <input type="text" id="almaseo-dh-s-posttypes" value="post,page,product" style="width:300px;">
                <span class="almaseo-dh-setting-help"><?php esc_html_e( 'Comma-separated post types to include in scans. Default: post, page, product.', 'almaseo-seo-playground' ); ?></span>
            </div>
            <div class="almaseo-dh-setting-row">
                <label for="almaseo-dh-s-threshold"><?php esc_html_e( 'Stale Year Threshold', 'almaseo-seo-playground' ); ?></label>
                <input type="number" id="almaseo-dh-s-threshold" min="1" max="10" value="2">
                <span class="almaseo-dh-setting-help"><?php esc_html_e( 'Years older than (current year - threshold) are flagged.', 'almaseo-seo-playground' ); ?></span>
            </div>
            <div class="almaseo-dh-setting-row">
                <label>
                    <input type="checkbox" id="almaseo-dh-s-prices" checked>
                    <?php esc_html_e( 'Scan for price references', 'almaseo-seo-playground' ); ?>
                </label>
            </div>
            <div class="almaseo-dh-setting-row">
                <label>
                    <input type="checkbox" id="almaseo-dh-s-regulations" checked>
                    <?php esc_html_e( 'Scan for regulation mentions', 'almaseo-seo-playground' ); ?>
                </label>
            </div>
            <button id="almaseo-dh-save-settings" class="button button-primary"><?php esc_html_e( 'Save Settings', 'almaseo-seo-playground' ); ?></button>
            <span id="almaseo-dh-settings-status" class="almaseo-dh-settings-status"></span>
        </div>
    </details>

</div>
