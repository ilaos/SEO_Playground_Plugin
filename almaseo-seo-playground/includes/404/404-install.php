<?php
/**
 * AlmaSEO 404 Tracker - Database Installation
 * 
 * @package AlmaSEO
 * @subpackage 404Tracker
 * @since 6.2.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Install 404 tracking table
 */
function almaseo_install_404_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'almaseo_404_log';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        path VARCHAR(255) NOT NULL,
        query VARCHAR(255) DEFAULT NULL,
        referrer TEXT DEFAULT NULL,
        user_agent TEXT DEFAULT NULL,
        ip VARBINARY(16) DEFAULT NULL,
        hits BIGINT(20) UNSIGNED DEFAULT 1,
        first_seen DATETIME NOT NULL,
        last_seen DATETIME NOT NULL,
        is_ignored TINYINT(1) DEFAULT 0,
        impact_score DECIMAL(5,2) DEFAULT NULL,
        impressions BIGINT(20) UNSIGNED DEFAULT 0,
        clicks BIGINT(20) UNSIGNED DEFAULT 0,
        suggested_target TEXT DEFAULT NULL,
        spike_flag TINYINT(1) DEFAULT 0,
        PRIMARY KEY (id),
        KEY path_ignored (path, is_ignored),
        KEY last_seen (last_seen),
        KEY hits (hits),
        KEY impact_score (impact_score),
        KEY spike_flag (spike_flag)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);

    // Per-day hit rollup table. The main log keeps a single cumulative `hits`
    // counter per path, which cannot answer "how many 404s in the last 7 days /
    // today" — summing it over a last_seen range counts every lifetime hit.
    // This table records hits per (log row, calendar day) so range stats are
    // accurate. One row per path+query per day; upserted on each capture.
    $daily_table = $wpdb->prefix . 'almaseo_404_daily';
    $daily_sql = "CREATE TABLE $daily_table (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        log_id BIGINT(20) UNSIGNED NOT NULL,
        hit_date DATE NOT NULL,
        hits BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        PRIMARY KEY (id),
        UNIQUE KEY log_day (log_id, hit_date),
        KEY hit_date (hit_date)
    ) $charset_collate;";
    dbDelta($daily_sql);

    // Store database version
    update_option('almaseo_404_db_version', '1.2');
}

/**
 * Check and update database if needed
 */
function almaseo_check_404_db() {
    $current_version = get_option('almaseo_404_db_version', '0');

    if (version_compare($current_version, '1.2', '<')) {
        almaseo_install_404_table();
    }
}

/**
 * Uninstall 404 tracking table
 */
function almaseo_uninstall_404_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'almaseo_404_log';
    $wpdb->query("DROP TABLE IF EXISTS $table_name"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix

    $daily_table = $wpdb->prefix . 'almaseo_404_daily';
    $wpdb->query("DROP TABLE IF EXISTS $daily_table"); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix

    delete_option('almaseo_404_db_version');
    delete_transient('almaseo_404_stats');
    delete_transient('almaseo_404_top_referrer');
}