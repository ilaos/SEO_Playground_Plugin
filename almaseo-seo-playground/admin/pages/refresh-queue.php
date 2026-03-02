<?php
/**
 * Refresh Queue – Admin Page Template
 *
 * Renders the prioritized refresh queue table.
 * Data is loaded client-side via the REST API.
 *
 * @package AlmaSEO
 * @since   7.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap almaseo-rq-wrap">

    <h1 class="almaseo-rq-title"><?php esc_html_e( 'Refresh Queue', 'almaseo' ); ?></h1>

    <!-- Feature Intro -->
    <div class="almaseo-rq-intro">
        <p class="almaseo-rq-intro-lead">
            <?php esc_html_e( 'Focus your refresh work on the pages that matter most. AlmaSEO scores every published page by business value, traffic trends, conversion potential, and opportunity size — then ranks them so you know exactly what to update next.', 'almaseo' ); ?>
        </p>

        <div class="almaseo-rq-how-it-works">
            <h3><?php esc_html_e( 'How it works', 'almaseo' ); ?></h3>
            <ol>
                <li><?php esc_html_e( 'Click Recalculate to score all your published content across four signals: business value, traffic decline, conversion intent, and opportunity size.', 'almaseo' ); ?></li>
                <li><?php esc_html_e( 'Posts are ranked by a weighted priority score. High-priority pages appear at the top — these are your best candidates for a content refresh.', 'almaseo' ); ?></li>
                <li><?php esc_html_e( 'Work through the queue: skip pages you don\'t want to refresh, or send high-priority ones to Content Refresh for an AI-assisted rewrite.', 'almaseo' ); ?></li>
            </ol>
        </div>

        <p class="almaseo-rq-intro-note">
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e( 'Signal weights are adjustable. Open Settings below the table to emphasize the signals that matter most to your business.', 'almaseo' ); ?>
        </p>
    </div>

    <!-- Summary Cards -->
    <div class="almaseo-rq-stats" id="almaseo-rq-stats">
        <div class="almaseo-rq-stat-card">
            <span class="dashicons dashicons-list-view"></span>
            <div class="almaseo-rq-stat-value" id="almaseo-rq-total">—</div>
            <div class="almaseo-rq-stat-label"><?php esc_html_e( 'Total Queued', 'almaseo' ); ?></div>
        </div>
        <div class="almaseo-rq-stat-card almaseo-rq-stat-high">
            <span class="dashicons dashicons-flag"></span>
            <div class="almaseo-rq-stat-value" id="almaseo-rq-high">—</div>
            <div class="almaseo-rq-stat-label"><?php esc_html_e( 'High Priority', 'almaseo' ); ?></div>
        </div>
        <div class="almaseo-rq-stat-card almaseo-rq-stat-medium">
            <span class="dashicons dashicons-marker"></span>
            <div class="almaseo-rq-stat-value" id="almaseo-rq-medium">—</div>
            <div class="almaseo-rq-stat-label"><?php esc_html_e( 'Medium Priority', 'almaseo' ); ?></div>
        </div>
        <div class="almaseo-rq-stat-card almaseo-rq-stat-low">
            <span class="dashicons dashicons-minus"></span>
            <div class="almaseo-rq-stat-value" id="almaseo-rq-low">—</div>
            <div class="almaseo-rq-stat-label"><?php esc_html_e( 'Low Priority', 'almaseo' ); ?></div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="almaseo-rq-actions">
        <button id="almaseo-rq-recalculate" class="button button-primary">
            <span class="dashicons dashicons-update"></span>
            <?php esc_html_e( 'Recalculate', 'almaseo' ); ?>
        </button>

        <select id="almaseo-rq-status-filter" class="almaseo-rq-select">
            <option value=""><?php esc_html_e( 'All statuses', 'almaseo' ); ?></option>
            <option value="queued"><?php esc_html_e( 'Queued', 'almaseo' ); ?></option>
            <option value="skipped"><?php esc_html_e( 'Skipped', 'almaseo' ); ?></option>
            <option value="refreshed"><?php esc_html_e( 'Refreshed', 'almaseo' ); ?></option>
        </select>

        <select id="almaseo-rq-tier-filter" class="almaseo-rq-select">
            <option value=""><?php esc_html_e( 'All priorities', 'almaseo' ); ?></option>
            <option value="high"><?php esc_html_e( 'High', 'almaseo' ); ?></option>
            <option value="medium"><?php esc_html_e( 'Medium', 'almaseo' ); ?></option>
            <option value="low"><?php esc_html_e( 'Low', 'almaseo' ); ?></option>
        </select>
    </div>

    <!-- Table -->
    <table class="widefat striped almaseo-rq-table" id="almaseo-rq-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Post', 'almaseo' ); ?></th>
                <th class="almaseo-rq-col-score"><?php esc_html_e( 'Priority', 'almaseo' ); ?></th>
                <th class="almaseo-rq-col-signal"><?php esc_html_e( 'Business', 'almaseo' ); ?></th>
                <th class="almaseo-rq-col-signal"><?php esc_html_e( 'Traffic', 'almaseo' ); ?></th>
                <th class="almaseo-rq-col-signal"><?php esc_html_e( 'Intent', 'almaseo' ); ?></th>
                <th class="almaseo-rq-col-signal"><?php esc_html_e( 'Opportunity', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Status', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'almaseo' ); ?></th>
            </tr>
        </thead>
        <tbody id="almaseo-rq-tbody">
            <tr><td colspan="8"><?php esc_html_e( 'Loading...', 'almaseo' ); ?></td></tr>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="almaseo-rq-pagination" id="almaseo-rq-pagination"></div>

    <!-- Settings Panel -->
    <details class="almaseo-rq-settings" id="almaseo-rq-settings">
        <summary class="almaseo-rq-settings-toggle">
            <span class="dashicons dashicons-admin-generic"></span>
            <?php esc_html_e( 'Signal Weights', 'almaseo' ); ?>
        </summary>
        <div class="almaseo-rq-settings-body">
            <p class="almaseo-rq-settings-desc">
                <?php esc_html_e( 'Adjust how much each signal contributes to the priority score. Weights are normalized — they don\'t need to add up to exactly 100.', 'almaseo' ); ?>
            </p>
            <div class="almaseo-rq-weight-row">
                <label><?php esc_html_e( 'Business Value', 'almaseo' ); ?></label>
                <input type="number" id="almaseo-rq-w-bv" min="0" max="100" value="25">
            </div>
            <div class="almaseo-rq-weight-row">
                <label><?php esc_html_e( 'Traffic Decline', 'almaseo' ); ?></label>
                <input type="number" id="almaseo-rq-w-td" min="0" max="100" value="30">
            </div>
            <div class="almaseo-rq-weight-row">
                <label><?php esc_html_e( 'Conversion Intent', 'almaseo' ); ?></label>
                <input type="number" id="almaseo-rq-w-ci" min="0" max="100" value="20">
            </div>
            <div class="almaseo-rq-weight-row">
                <label><?php esc_html_e( 'Opportunity Size', 'almaseo' ); ?></label>
                <input type="number" id="almaseo-rq-w-os" min="0" max="100" value="25">
            </div>
            <button id="almaseo-rq-save-settings" class="button button-primary"><?php esc_html_e( 'Save Weights', 'almaseo' ); ?></button>
            <span id="almaseo-rq-settings-status" class="almaseo-rq-settings-status"></span>
        </div>
    </details>

</div>
