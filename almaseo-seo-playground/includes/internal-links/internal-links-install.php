<?php
/**
 * AlmaSEO Internal Links Database Installation
 *
 * Creates and manages the internal_links table for storing link rules.
 *
 * @package AlmaSEO
 * @subpackage InternalLinks
 * @since 6.6.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Create or update the internal links table
 *
 * Table stores link rules: keyword -> target URL mapping with guardrail settings.
 *
 * @return void
 */
function almaseo_install_internal_links_table() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'almaseo_internal_links';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id              BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        keyword         VARCHAR(255) NOT NULL,
        target_url      TEXT NOT NULL,
        target_post_id  BIGINT(20) UNSIGNED DEFAULT NULL,
        match_type      VARCHAR(20) NOT NULL DEFAULT 'exact',
        case_sensitive  TINYINT(1) NOT NULL DEFAULT 0,
        max_per_post    SMALLINT(5) UNSIGNED NOT NULL DEFAULT 1,
        max_per_page    SMALLINT(5) UNSIGNED NOT NULL DEFAULT 3,
        nofollow        TINYINT(1) NOT NULL DEFAULT 0,
        new_tab         TINYINT(1) NOT NULL DEFAULT 0,
        is_enabled      TINYINT(1) NOT NULL DEFAULT 1,
        post_types      VARCHAR(255) NOT NULL DEFAULT 'post,page',
        exclude_ids     TEXT DEFAULT NULL,
        priority        SMALLINT(5) UNSIGNED NOT NULL DEFAULT 10,
        hits            BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
        created_at      DATETIME NOT NULL,
        updated_at      DATETIME NOT NULL,
        PRIMARY KEY (id),
        KEY keyword (keyword(191)),
        KEY is_enabled (is_enabled),
        KEY priority (priority)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    // Store the database version
    update_option( 'almaseo_internal_links_db_version', '1.0.0' );
}

/**
 * Check and update database if needed
 *
 * @return void
 */
function almaseo_check_internal_links_db() {
    $installed_version = get_option( 'almaseo_internal_links_db_version', '0' );

    if ( version_compare( $installed_version, '1.0.0', '<' ) ) {
        almaseo_install_internal_links_table();
    }
}

/**
 * Create or update the orphan pages table (v7.7.0+)
 */
function almaseo_install_orphan_pages_table() {
    global $wpdb;

    $table_name      = $wpdb->prefix . 'almaseo_orphan_pages';
    $charset_collate = $wpdb->get_charset_collate();

    $sql = "CREATE TABLE $table_name (
        id               BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
        post_id          BIGINT(20) UNSIGNED NOT NULL,
        inbound_count    INT UNSIGNED NOT NULL DEFAULT 0,
        outbound_count   INT UNSIGNED NOT NULL DEFAULT 0,
        cluster_id       VARCHAR(100) DEFAULT '',
        cluster_strength DECIMAL(5,2) DEFAULT 0,
        is_hub_candidate TINYINT(1) DEFAULT 0,
        status           VARCHAR(20) NOT NULL DEFAULT 'orphan',
        scanned_at       DATETIME DEFAULT NULL,
        suggestion       TEXT DEFAULT NULL,
        PRIMARY KEY (id),
        UNIQUE KEY post_id (post_id),
        KEY status (status),
        KEY cluster_id (cluster_id),
        KEY inbound_count (inbound_count)
    ) $charset_collate;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta( $sql );

    update_option( 'almaseo_orphan_pages_db_version', '1.0.0' );
}

/**
 * Check orphan pages DB version
 */
function almaseo_check_orphan_pages_db() {
    $installed = get_option( 'almaseo_orphan_pages_db_version', '0' );
    if ( version_compare( $installed, '1.0.0', '<' ) ) {
        almaseo_install_orphan_pages_table();
    }
}

add_action( 'admin_init', 'almaseo_check_orphan_pages_db' );

/**
 * Drop the internal links table (for uninstall)
 *
 * @return void
 */
function almaseo_uninstall_internal_links_table() {
    global $wpdb;

    $table_name = $wpdb->prefix . 'almaseo_internal_links';
    $wpdb->query( "DROP TABLE IF EXISTS $table_name" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix

    delete_option( 'almaseo_internal_links_db_version' );
    delete_transient( 'almaseo_internal_links_cache' );
}

// Hook into plugin activation
add_action( 'admin_init', 'almaseo_check_internal_links_db' );
