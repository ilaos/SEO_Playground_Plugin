<?php
/**
 * Refresh Queue – Database Installation
 *
 * Creates the `{prefix}_almaseo_refresh_queue` table on activation
 * and handles schema upgrades via a stored DB version option.
 *
 * @package AlmaSEO
 * @since   7.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create / upgrade the refresh-queue table.
 */
function almaseo_refresh_queue_install() {
    global $wpdb;

    $table   = $wpdb->prefix . 'almaseo_refresh_queue';
    $charset = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE {$table} (
        id                BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id           BIGINT UNSIGNED NOT NULL,
        priority_score    DECIMAL(5,2)    NOT NULL DEFAULT 0,
        business_value    DECIMAL(5,2)    NOT NULL DEFAULT 0,
        traffic_decline   DECIMAL(5,2)    NOT NULL DEFAULT 0,
        conversion_intent DECIMAL(5,2)    NOT NULL DEFAULT 0,
        opportunity_size  DECIMAL(5,2)    NOT NULL DEFAULT 0,
        priority_tier     VARCHAR(10)     NOT NULL DEFAULT 'low',
        status            VARCHAR(20)     NOT NULL DEFAULT 'queued',
        reason            TEXT            DEFAULT NULL,
        source            VARCHAR(30)     NOT NULL DEFAULT 'auto',
        calculated_at     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
        refreshed_at      DATETIME        DEFAULT NULL,
        PRIMARY KEY  (id),
        UNIQUE KEY post_id (post_id),
        KEY priority_score (priority_score),
        KEY status (status),
        KEY priority_tier (priority_tier)
    ) {$charset};";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'almaseo_rq_db_version', '1.0.0' );
}
