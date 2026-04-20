<?php
/**
 * Orphan Pages – Admin Page Template
 *
 * Renders the orphan detection results and scan controls.
 * Data is loaded client-side via the REST API.
 *
 * @package AlmaSEO
 * @since   7.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap almaseo-orphan-wrap">

    <h1 class="almaseo-orphan-title"><?php esc_html_e( 'Orphan Pages', 'almaseo-seo-playground' ); ?></h1>

    <!-- Feature Intro -->
    <div class="almaseo-orphan-intro">
        <p class="almaseo-orphan-intro-lead">
            <?php esc_html_e( 'Find pages with no internal links pointing to them. Orphan pages are invisible to search engine crawlers that rely on link discovery.', 'almaseo-seo-playground' ); ?>
        </p>

        <div class="almaseo-orphan-how-it-works">
            <h3><?php esc_html_e( 'How it works', 'almaseo-seo-playground' ); ?></h3>
            <ol>
                <li><?php esc_html_e( 'Click Scan Now to analyze all published posts and pages for internal link connectivity.', 'almaseo-seo-playground' ); ?></li>
                <li><?php esc_html_e( 'Review orphan pages (0 inbound links) and weak pages (1-2 inbound links).', 'almaseo-seo-playground' ); ?></li>
                <li><?php esc_html_e( 'Add internal links from related content or use the Internal Links module to create linking rules.', 'almaseo-seo-playground' ); ?></li>
            </ol>
        </div>

        <p class="almaseo-orphan-intro-note">
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e( 'Hub candidates are pages with many outbound links but few inbound links — they could serve as cornerstone/pillar pages if properly linked.', 'almaseo-seo-playground' ); ?>
        </p>
    </div>

    <!-- Summary Cards -->
    <div class="almaseo-orphan-stats" id="almaseo-orphan-stats">
        <div class="almaseo-orphan-stat-card almaseo-orphan-stat-orphan">
            <span class="dashicons dashicons-warning"></span>
            <div class="almaseo-orphan-stat-value" id="almaseo-orphan-count-orphans">—</div>
            <div class="almaseo-orphan-stat-label"><?php esc_html_e( 'Orphans', 'almaseo-seo-playground' ); ?></div>
        </div>
        <div class="almaseo-orphan-stat-card almaseo-orphan-stat-weak">
            <span class="dashicons dashicons-info"></span>
            <div class="almaseo-orphan-stat-value" id="almaseo-orphan-count-weak">—</div>
            <div class="almaseo-orphan-stat-label"><?php esc_html_e( 'Weak', 'almaseo-seo-playground' ); ?></div>
        </div>
        <div class="almaseo-orphan-stat-card">
            <span class="dashicons dashicons-yes-alt"></span>
            <div class="almaseo-orphan-stat-value" id="almaseo-orphan-count-healthy">—</div>
            <div class="almaseo-orphan-stat-label"><?php esc_html_e( 'Healthy', 'almaseo-seo-playground' ); ?></div>
        </div>
        <div class="almaseo-orphan-stat-card">
            <span class="dashicons dashicons-admin-links"></span>
            <div class="almaseo-orphan-stat-value" id="almaseo-orphan-count-hubs">—</div>
            <div class="almaseo-orphan-stat-label"><?php esc_html_e( 'Hub Candidates', 'almaseo-seo-playground' ); ?></div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="almaseo-orphan-actions">
        <button id="almaseo-orphan-scan" class="button button-primary">
            <span class="dashicons dashicons-search"></span>
            <?php esc_html_e( 'Scan Now', 'almaseo-seo-playground' ); ?>
        </button>

        <select id="almaseo-orphan-status-filter" class="almaseo-orphan-select">
            <option value=""><?php esc_html_e( 'All statuses', 'almaseo-seo-playground' ); ?></option>
            <option value="orphan"><?php esc_html_e( 'Orphan', 'almaseo-seo-playground' ); ?></option>
            <option value="weak"><?php esc_html_e( 'Weak', 'almaseo-seo-playground' ); ?></option>
            <option value="healthy"><?php esc_html_e( 'Healthy', 'almaseo-seo-playground' ); ?></option>
            <option value="addressed"><?php esc_html_e( 'Addressed', 'almaseo-seo-playground' ); ?></option>
            <option value="dismissed"><?php esc_html_e( 'Dismissed', 'almaseo-seo-playground' ); ?></option>
        </select>

        <select id="almaseo-orphan-cluster-filter" class="almaseo-orphan-select">
            <option value=""><?php esc_html_e( 'All clusters', 'almaseo-seo-playground' ); ?></option>
            <!-- Populated dynamically -->
        </select>

        <input type="text" id="almaseo-orphan-search" class="almaseo-orphan-search" placeholder="<?php esc_attr_e( 'Search by title...', 'almaseo-seo-playground' ); ?>">

        <span class="almaseo-orphan-last-scan" id="almaseo-orphan-last-scan"></span>
    </div>

    <!-- Table -->
    <table class="widefat striped almaseo-orphan-table" id="almaseo-orphan-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Page', 'almaseo-seo-playground' ); ?></th>
                <th class="almaseo-orphan-col-num"><?php esc_html_e( 'Inbound', 'almaseo-seo-playground' ); ?></th>
                <th class="almaseo-orphan-col-num"><?php esc_html_e( 'Outbound', 'almaseo-seo-playground' ); ?></th>
                <th><?php esc_html_e( 'Cluster', 'almaseo-seo-playground' ); ?></th>
                <th><?php esc_html_e( 'Status', 'almaseo-seo-playground' ); ?></th>
                <th class="almaseo-orphan-col-suggestion"><?php esc_html_e( 'Suggestion', 'almaseo-seo-playground' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'almaseo-seo-playground' ); ?></th>
            </tr>
        </thead>
        <tbody id="almaseo-orphan-tbody">
            <tr><td colspan="7"><?php esc_html_e( 'Loading...', 'almaseo-seo-playground' ); ?></td></tr>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="almaseo-orphan-pagination" id="almaseo-orphan-pagination"></div>

</div>
