<?php
/**
 * Featured Snippet Targeting – Database Installation
 *
 * Creates the `{prefix}_almaseo_snippet_targets` table.
 *
 * @package AlmaSEO
 * @since   7.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create / upgrade the snippet-targets table.
 */
function almaseo_snippet_targets_install() {
    global $wpdb;

    $table   = $wpdb->prefix . 'almaseo_snippet_targets';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id                 BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id            BIGINT UNSIGNED NOT NULL,
        query              VARCHAR(255)    NOT NULL,
        snippet_format     VARCHAR(30)     NOT NULL DEFAULT 'paragraph',
        current_position   INT             DEFAULT NULL,
        search_volume      INT             DEFAULT NULL,
        draft_content      LONGTEXT        DEFAULT NULL,
        original_section   LONGTEXT        DEFAULT NULL,
        content_hash       VARCHAR(64)     DEFAULT NULL,
        status             VARCHAR(30)     NOT NULL DEFAULT 'opportunity',
        source             VARCHAR(30)     NOT NULL DEFAULT 'dashboard',
        created_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        applied_at         DATETIME        DEFAULT NULL,
        applied_by         BIGINT UNSIGNED DEFAULT NULL,
        reviewed_at        DATETIME        DEFAULT NULL,
        reviewed_by        BIGINT UNSIGNED DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY post_id (post_id),
        KEY status (status),
        KEY snippet_format (snippet_format),
        KEY query (query(191))
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'almaseo_st_db_version', '1.0.0' );
}
