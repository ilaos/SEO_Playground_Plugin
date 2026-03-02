<?php
/**
 * Refresh Drafts – Database Installation
 *
 * Creates the `{prefix}_almaseo_refresh_drafts` table on activation
 * and handles schema upgrades via a stored DB version option.
 *
 * @package AlmaSEO
 * @since   7.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create / upgrade the refresh-drafts table.
 */
function almaseo_refresh_drafts_install() {
    global $wpdb;

    $table   = $wpdb->prefix . 'almaseo_refresh_drafts';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id         BIGINT UNSIGNED NOT NULL,
        status          VARCHAR(20)     NOT NULL DEFAULT 'pending',
        sections_json   LONGTEXT        NOT NULL,
        merged_content  LONGTEXT        DEFAULT NULL,
        trigger_source  VARCHAR(40)     NOT NULL DEFAULT 'manual',
        created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        reviewed_at     DATETIME        DEFAULT NULL,
        reviewed_by     BIGINT UNSIGNED DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY post_id   (post_id),
        KEY status    (status),
        KEY created   (created_at)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'almaseo_rd_db_version', '1.0.0' );
}
