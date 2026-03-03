<?php
/**
 * GSC Monitor – Database Installation
 *
 * Creates the `{prefix}_almaseo_gsc_monitor` table on activation
 * and handles schema upgrades via a stored DB version option.
 *
 * @package AlmaSEO
 * @since   7.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create / upgrade the GSC monitor table.
 */
function almaseo_gsc_monitor_install() {
    global $wpdb;

    $table   = $wpdb->prefix . 'almaseo_gsc_monitor';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id           BIGINT UNSIGNED DEFAULT NULL,
        url               TEXT            NOT NULL,
        finding_type      VARCHAR(30)     NOT NULL,
        subtype           VARCHAR(30)     NOT NULL,
        severity          VARCHAR(10)     NOT NULL DEFAULT 'medium',
        detected_value    TEXT            NOT NULL,
        expected_value    TEXT            DEFAULT NULL,
        context_data      LONGTEXT        DEFAULT NULL,
        suggestion        TEXT            DEFAULT NULL,
        status            VARCHAR(20)     NOT NULL DEFAULT 'open',
        first_seen        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        last_seen         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        resolved_at       DATETIME        DEFAULT NULL,
        resolved_by       BIGINT UNSIGNED DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY post_id (post_id),
        KEY finding_type (finding_type),
        KEY subtype (subtype),
        KEY severity (severity),
        KEY status (status),
        KEY url_type_subtype (url(191), finding_type, subtype)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'almaseo_gsc_db_version', '1.0.0' );
}
