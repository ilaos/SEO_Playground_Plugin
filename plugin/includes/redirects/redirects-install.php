<?php
/**
 * AlmaSEO Redirects Database Installation
 * 
 * @package AlmaSEO
 * @subpackage Redirects
 * @since 6.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Create or update the redirects table
 * 
 * @return void
 */
function almaseo_install_redirects_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'almaseo_redirects';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        source VARCHAR(255) NOT NULL,
        target TEXT NOT NULL,
        status SMALLINT(3) NOT NULL DEFAULT 301,
        is_enabled TINYINT(1) NOT NULL DEFAULT 1,
        hits BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        last_hit DATETIME NULL,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY source (source),
        KEY is_enabled (is_enabled),
        KEY source_enabled (source, is_enabled)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Store the database version
    update_option('almaseo_redirects_db_version', '1.0.0');
}

/**
 * Check and update database if needed
 * 
 * @return void
 */
function almaseo_check_redirects_db() {
    $installed_version = get_option('almaseo_redirects_db_version', '0');
    
    if (version_compare($installed_version, '1.0.0', '<')) {
        almaseo_install_redirects_table();
    }
}

/**
 * Drop the redirects table (for uninstall)
 * 
 * @return void
 */
function almaseo_uninstall_redirects_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'almaseo_redirects';
    $wpdb->query("DROP TABLE IF EXISTS $table_name");
    
    delete_option('almaseo_redirects_db_version');
    delete_transient('almaseo_enabled_redirects');
}

// Hook into plugin activation
add_action('admin_init', 'almaseo_check_redirects_db');