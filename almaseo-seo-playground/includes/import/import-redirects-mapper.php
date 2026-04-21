<?php
/**
 * AlmaSEO Import Redirects Mapper
 *
 * Imports redirect rules from Rank Math, Yoast Premium, AIOSEO,
 * and the Redirection plugin into AlmaSEO's redirects table.
 *
 * @package AlmaSEO
 * @since   8.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Import_Redirects_Mapper {

    const BATCH_SIZE = 100;

    /**
     * Detect available redirect data per source.
     *
     * @return array Keyed by source slug.
     */
    public static function detect_all() {
        global $wpdb;

        // Rank Math redirects table.
        $rm_table  = $wpdb->prefix . 'rank_math_redirections';
        $rm_exists = (bool) $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $rm_table ) );
        $rm_count  = $rm_exists ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$rm_table}`" ) : 0; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix

        // Yoast Premium stores redirects in wp_options as a serialized array.
        $yoast_redirects = get_option( 'wpseo-premium-redirects-base', array() );
        $yoast_count     = is_array( $yoast_redirects ) ? count( $yoast_redirects ) : 0;

        // AIOSEO redirects table (Pro feature).
        $aioseo_table  = $wpdb->prefix . 'aioseo_redirects';
        $aioseo_exists = (bool) $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $aioseo_table ) );
        $aioseo_count  = $aioseo_exists ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$aioseo_table}`" ) : 0; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix

        // Redirection plugin table.
        $redir_table  = $wpdb->prefix . 'redirection_items';
        $redir_exists = (bool) $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $redir_table ) );
        $redir_count  = $redir_exists ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$redir_table}`" ) : 0; // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix

        return array(
            'rankmath'    => array( 'name' => 'Rank Math', 'available' => $rm_count > 0, 'plugin_active' => class_exists( 'RankMath' ), 'record_count' => $rm_count ),
            'yoast'       => array( 'name' => 'Yoast SEO Premium', 'available' => $yoast_count > 0, 'plugin_active' => defined( 'WPSEO_VERSION' ), 'record_count' => $yoast_count ),
            'aioseo'      => array( 'name' => 'All in One SEO', 'available' => $aioseo_count > 0, 'plugin_active' => defined( 'AIOSEO_VERSION' ), 'record_count' => $aioseo_count ),
            'redirection' => array( 'name' => 'Redirection Plugin', 'available' => $redir_count > 0, 'plugin_active' => defined( 'REDIRECTION_VERSION' ), 'record_count' => $redir_count ),
        );
    }

    /**
     * Process one batch of redirect imports.
     *
     * @param string $source    'rankmath', 'yoast', 'aioseo', or 'redirection'.
     * @param int    $offset    Current offset.
     * @param bool   $overwrite Whether to overwrite existing AlmaSEO redirects with same source URL.
     * @return array|WP_Error Result.
     */
    public static function process_batch( $source, $offset = 0, $overwrite = false ) {
        $method = 'get_batch_' . $source;
        if ( ! method_exists( __CLASS__, $method ) ) {
            return new WP_Error( 'invalid_source', __( 'Unknown redirect source.', 'almaseo-seo-playground' ) );
        }

        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_redirects';

        // Verify our redirects table exists.
        $our_table_exists = (bool) $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) );
        if ( ! $our_table_exists ) {
            return new WP_Error( 'table_missing', __( 'AlmaSEO redirects table does not exist. Please visit the Redirects page first.', 'almaseo-seo-playground' ) );
        }

        $rows = self::$method( $offset, self::BATCH_SIZE );

        $imported  = 0;
        $skipped   = 0;
        $processed = 0;

        $now = current_time( 'mysql' );

        foreach ( $rows as $row ) {
            $processed++;

            $source_url = trim( $row['source'] );
            $target_url = trim( $row['target'] );
            $status     = (int) $row['status'];

            if ( empty( $source_url ) ) {
                continue;
            }

            // Normalize: ensure source is a relative path.
            $source_url = self::normalize_source( $source_url );
            if ( empty( $source_url ) ) {
                continue;
            }

            // Valid redirect status codes.
            if ( ! in_array( $status, array( 301, 302, 307, 308, 410, 451 ), true ) ) {
                $status = 301;
            }

            // Check for existing redirect with same source.
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM `{$table}` WHERE source = %s LIMIT 1",
                $source_url
            ) );

            if ( $existing ) {
                if ( $overwrite ) {
                    $wpdb->update(
                        $table,
                        array(
                            'target'     => $target_url,
                            'status'     => $status,
                            'is_enabled' => 1,
                            'updated_at' => $now,
                        ),
                        array( 'id' => $existing ),
                        array( '%s', '%d', '%d', '%s' ),
                        array( '%d' )
                    );
                    $imported++;
                } else {
                    $skipped++;
                }
            } else {
                $wpdb->insert(
                    $table,
                    array(
                        'source'     => $source_url,
                        'target'     => $target_url,
                        'status'     => $status,
                        'is_enabled' => 1,
                        'hits'       => 0,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ),
                    array( '%s', '%s', '%d', '%d', '%d', '%s', '%s' )
                );
                $imported++;
            }
        }

        $done = count( $rows ) < self::BATCH_SIZE;

        return array(
            'processed' => $processed,
            'imported'  => $imported,
            'skipped'   => $skipped,
            'offset'    => $offset + $processed,
            'done'      => $done,
        );
    }

    /* ------------------------------------------------------------------
     *  Rank Math: rank_math_redirections + rank_math_redirections_cache
     * ----------------------------------------------------------------*/

    private static function get_batch_rankmath( $offset, $limit ) {
        global $wpdb;

        $table = $wpdb->prefix . 'rank_math_redirections';
        if ( ! (bool) $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) ) {
            return array();
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT sources, url_to, header_code FROM `{$table}`
             ORDER BY id ASC
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        ), ARRAY_A );

        $rows = array();
        foreach ( $results as $r ) {
            // Rank Math stores sources as serialized array of {pattern, comparison}.
            $sources = maybe_unserialize( $r['sources'] );
            if ( ! is_array( $sources ) ) {
                continue;
            }
            foreach ( $sources as $src ) {
                if ( isset( $src['pattern'] ) && ( ! isset( $src['comparison'] ) || $src['comparison'] === 'exact' ) ) {
                    $rows[] = array(
                        'source' => $src['pattern'],
                        'target' => $r['url_to'],
                        'status' => (int) $r['header_code'],
                    );
                }
            }
        }

        return $rows;
    }

    /* ------------------------------------------------------------------
     *  Yoast Premium: wpseo-premium-redirects-base option
     * ----------------------------------------------------------------*/

    private static function get_batch_yoast( $offset, $limit ) {
        $redirects = get_option( 'wpseo-premium-redirects-base', array() );
        if ( ! is_array( $redirects ) ) {
            return array();
        }

        $rows = array();
        foreach ( $redirects as $source => $data ) {
            $rows[] = array(
                'source' => $source,
                'target' => isset( $data['url'] ) ? $data['url'] : '',
                'status' => isset( $data['type'] ) ? (int) $data['type'] : 301,
            );
        }

        return array_slice( $rows, $offset, $limit );
    }

    /* ------------------------------------------------------------------
     *  Redirection plugin: redirection_items table
     * ----------------------------------------------------------------*/

    private static function get_batch_redirection( $offset, $limit ) {
        global $wpdb;

        $table = $wpdb->prefix . 'redirection_items';
        if ( ! (bool) $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) ) {
            return array();
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT url AS source, action_data AS target, action_code AS status
             FROM `{$table}`
             WHERE action_type = 'url'
             ORDER BY id ASC
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        ), ARRAY_A );

        return is_array( $results ) ? $results : array();
    }

    /* ------------------------------------------------------------------
     *  AIOSEO: aioseo_redirects table (Pro feature)
     * ----------------------------------------------------------------*/

    private static function get_batch_aioseo( $offset, $limit ) {
        global $wpdb;

        $table = $wpdb->prefix . 'aioseo_redirects';
        if ( ! (bool) $wpdb->get_var( $wpdb->prepare( "SHOW TABLES LIKE %s", $table ) ) ) {
            return array();
        }

        // AIOSEO stores source_url (relative path) and target_url (full or relative).
        // The `type` column holds the HTTP status code (301, 302, etc.).
        // The `enabled` column indicates whether the redirect is active.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT source_url, target_url, type
             FROM `{$table}`
             WHERE enabled = 1
             ORDER BY id ASC
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        ), ARRAY_A );

        if ( ! is_array( $results ) ) {
            return array();
        }

        $rows = array();
        foreach ( $results as $r ) {
            $rows[] = array(
                'source' => $r['source_url'],
                'target' => $r['target_url'],
                'status' => (int) $r['type'],
            );
        }

        return $rows;
    }

    /* ------------------------------------------------------------------
     *  Helpers
     * ----------------------------------------------------------------*/

    /**
     * Normalize a source URL to a relative path.
     *
     * @param string $url Source URL or path.
     * @return string Relative path starting with /.
     */
    private static function normalize_source( $url ) {
        // If it's a full URL, extract the path.
        if ( strpos( $url, 'http' ) === 0 ) {
            $parsed = wp_parse_url( $url );
            $url    = isset( $parsed['path'] ) ? $parsed['path'] : '';
        }

        // Ensure leading slash.
        $url = '/' . ltrim( $url, '/' );

        // Remove trailing slash (except for root).
        if ( $url !== '/' ) {
            $url = rtrim( $url, '/' );
        }

        return $url;
    }
}
