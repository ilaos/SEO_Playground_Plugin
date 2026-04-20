<?php
/**
 * Refresh Drafts – Admin List Page Template
 *
 * Renders the "Content Refresh" overview table.
 * Data is loaded client-side via the REST API.
 *
 * @package AlmaSEO
 * @since   7.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap almaseo-rd-wrap">

    <h1 class="almaseo-rd-title"><?php esc_html_e( 'Content Refresh', 'almaseo-seo-playground' ); ?></h1>

    <!-- Feature Intro -->
    <div class="almaseo-rd-intro">
        <p class="almaseo-rd-intro-lead">
            <?php esc_html_e( 'Keep your content fresh without rewriting entire posts. Review AI-suggested improvements section by section, keep what you like, and update your live page in one click.', 'almaseo-seo-playground' ); ?>
        </p>

        <div class="almaseo-rd-how-it-works">
            <h3><?php esc_html_e( 'How it works', 'almaseo-seo-playground' ); ?></h3>
            <ol>
                <li><?php esc_html_e( 'AlmaSEO scans your published content and identifies sections that could be improved, updated, or expanded.', 'almaseo-seo-playground' ); ?></li>
                <li><?php esc_html_e( 'A proposed rewrite appears here as a refresh draft. You review each section side by side — your current version vs. the suggested version.', 'almaseo-seo-playground' ); ?></li>
                <li><?php esc_html_e( 'Accept the sections you like, reject the rest, and apply. WordPress saves a revision automatically so you can always roll back.', 'almaseo-seo-playground' ); ?></li>
            </ol>
        </div>

        <p class="almaseo-rd-intro-note">
            <span class="dashicons dashicons-backup"></span>
            <?php esc_html_e( 'Every change creates a WordPress revision. Your original content is always one click away if you change your mind.', 'almaseo-seo-playground' ); ?>
        </p>
    </div>

    <!-- Filter bar -->
    <div class="almaseo-rd-filters">
        <select id="almaseo-rd-status-filter" class="almaseo-rd-select">
            <option value=""><?php esc_html_e( 'All statuses', 'almaseo-seo-playground' ); ?></option>
            <option value="pending"><?php esc_html_e( 'Pending review', 'almaseo-seo-playground' ); ?></option>
            <option value="applied"><?php esc_html_e( 'Applied', 'almaseo-seo-playground' ); ?></option>
            <option value="dismissed"><?php esc_html_e( 'Dismissed', 'almaseo-seo-playground' ); ?></option>
        </select>
        <button id="almaseo-rd-refresh-btn" class="button"><?php esc_html_e( 'Refresh', 'almaseo-seo-playground' ); ?></button>
    </div>

    <!-- Table (populated by JS) -->
    <table class="widefat striped almaseo-rd-table" id="almaseo-rd-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Post', 'almaseo-seo-playground' ); ?></th>
                <th><?php esc_html_e( 'Sections changed', 'almaseo-seo-playground' ); ?></th>
                <th><?php esc_html_e( 'Source', 'almaseo-seo-playground' ); ?></th>
                <th><?php esc_html_e( 'Status', 'almaseo-seo-playground' ); ?></th>
                <th><?php esc_html_e( 'Created', 'almaseo-seo-playground' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'almaseo-seo-playground' ); ?></th>
            </tr>
        </thead>
        <tbody id="almaseo-rd-tbody">
            <tr><td colspan="6"><?php esc_html_e( 'Loading...', 'almaseo-seo-playground' ); ?></td></tr>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="almaseo-rd-pagination" id="almaseo-rd-pagination"></div>
</div>
