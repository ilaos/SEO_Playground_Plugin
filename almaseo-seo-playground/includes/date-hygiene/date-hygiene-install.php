<?php
/**
 * Date Hygiene Scanner – Database Installation
 *
 * Creates the `{prefix}_almaseo_date_hygiene` table on activation
 * and handles schema upgrades via a stored DB version option.
 *
 * @package AlmaSEO
 * @since   7.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create / upgrade the date-hygiene table.
 */
function almaseo_date_hygiene_install() {
    global $wpdb;

    $table   = $wpdb->prefix . 'almaseo_date_hygiene';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id           BIGINT UNSIGNED NOT NULL,
        finding_type      VARCHAR(30)     NOT NULL,
        severity          VARCHAR(10)     NOT NULL DEFAULT 'medium',
        detected_value    VARCHAR(255)    NOT NULL,
        context_snippet   TEXT            NOT NULL,
        suggestion        TEXT            DEFAULT NULL,
        location          VARCHAR(20)     NOT NULL DEFAULT 'body',
        status            VARCHAR(20)     NOT NULL DEFAULT 'open',
        scanned_at        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        resolved_at       DATETIME        DEFAULT NULL,
        resolved_by       BIGINT UNSIGNED DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY post_id (post_id),
        KEY finding_type (finding_type),
        KEY severity (severity),
        KEY status (status)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'almaseo_dh_db_version', '1.0.0' );
}
