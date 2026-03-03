<?php
/**
 * Schema Drift Monitor – Database Installation
 *
 * Creates two tables:
 *   1. `{prefix}_almaseo_schema_baseline` — stored baseline schema snapshots.
 *   2. `{prefix}_almaseo_schema_drift`   — detected drift findings.
 *
 * @package AlmaSEO
 * @since   7.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create / upgrade the schema-drift tables.
 */
function almaseo_schema_drift_install() {
    global $wpdb;

    $charset = $wpdb->get_charset_collate();

    /* ── Baseline table ── */
    $baseline = $wpdb->prefix . 'almaseo_schema_baseline';

    $sql_baseline = "CREATE TABLE {$baseline} (
        id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id         BIGINT UNSIGNED NOT NULL,
        url             TEXT            NOT NULL,
        schema_type     VARCHAR(100)    NOT NULL,
        schema_json     LONGTEXT        NOT NULL,
        captured_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY  (id),
        KEY post_id (post_id),
        KEY schema_type (schema_type)
    ) {$charset};";

    /* ── Drift findings table ── */
    $drift = $wpdb->prefix . 'almaseo_schema_drift';

    $sql_drift = "CREATE TABLE {$drift} (
        id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id         BIGINT UNSIGNED NOT NULL,
        url             TEXT            NOT NULL,
        drift_type      VARCHAR(30)     NOT NULL,
        schema_type     VARCHAR(100)    NOT NULL DEFAULT '',
        severity        VARCHAR(10)     NOT NULL DEFAULT 'medium',
        baseline_value  LONGTEXT        DEFAULT NULL,
        current_value   LONGTEXT        DEFAULT NULL,
        diff_summary    TEXT            DEFAULT NULL,
        suggestion      TEXT            DEFAULT NULL,
        status          VARCHAR(20)     NOT NULL DEFAULT 'open',
        detected_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        resolved_at     DATETIME        DEFAULT NULL,
        resolved_by     BIGINT UNSIGNED DEFAULT NULL,
        PRIMARY KEY  (id),
        KEY post_id (post_id),
        KEY drift_type (drift_type),
        KEY severity (severity),
        KEY status (status)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql_baseline );
    dbDelta( $sql_drift );

    update_option( 'almaseo_sd_db_version', '1.0.0' );
}
