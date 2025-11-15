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
        PRIMARY KEY (id),
        KEY path_ignored (path, is_ignored),
        KEY last_seen (last_seen),
        KEY hits (hits)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Store database version
    update_option('almaseo_404_db_version', '1.0');
}

/**
 * Check and update database if needed
 */
function almaseo_check_404_db() {
    $current_version = get_option('almaseo_404_db_version', '0');
    
    if (version_compare($current_version, '1.0', '<')) {
        almaseo_install_404_table();
    }
}

/**
 * Uninstall 404 tracking table
 */
function almaseo_uninstall_404_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'almaseo_404_log';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    
    delete_option('almaseo_404_db_version');
    delete_transient('almaseo_404_stats');
    delete_transient('almaseo_404_top_referrer');
}