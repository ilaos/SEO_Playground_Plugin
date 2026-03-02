<?php
/**
 * Refresh Drafts – Data Model (CRUD)
 *
 * Thin wrapper around the custom DB table for create / read / update
 * operations on refresh-draft rows.
 *
 * @package AlmaSEO
 * @since   7.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Refresh_Draft_Model {

    /* ───────────────────────────── helpers ── */

    /**
     * Return the fully-prefixed table name.
     */
    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'almaseo_refresh_drafts';
    }

    /* ───────────────────────────── CREATE ── */

    /**
     * Insert a new refresh draft row.
     *
     * @param  int    $post_id        WP post ID.
     * @param  array  $sections       Array of section diffs.
     * @param  string $trigger_source 'manual' | 'evergreen' | 'cron'.
     * @return int|false  Inserted row ID or false on failure.
     */
    public static function create( $post_id, array $sections, $trigger_source = 'manual' ) {
        global $wpdb;

        $ok = $wpdb->insert(
            self::table(),
            array(
                'post_id'        => absint( $post_id ),
                'status'         => 'pending',
                'sections_json'  => wp_json_encode( $sections, JSON_UNESCAPED_UNICODE ),
                'trigger_source' => sanitize_key( $trigger_source ),
                'created_at'     => current_time( 'mysql', true ),
            ),
            array( '%d', '%s', '%s', '%s', '%s' )
        );

        return $ok ? (int) $wpdb->insert_id : false;
    }

    /* ───────────────────────────── READ ── */

    /**
     * Fetch a single draft by ID.
     *
     * @param  int $id Row ID.
     * @return object|null
     */
    public static function get( $id ) {
        global $wpdb;
        $table = self::table();

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
    }

    /**
     * List drafts with optional filters.
     *
     * @param  array $args {
     *     Optional filters.
     *     @type string $status  Filter by status.
     *     @type int    $post_id Filter by post.
     *     @type int    $limit   Max rows (default 40).
     *     @type int    $offset  Offset (default 0).
     * }
     * @return array  Array of row objects.
     */
    public static function list_drafts( array $args = array() ) {
        global $wpdb;
        $table = self::table();

        $where  = array( '1=1' );
        $values = array();

        if ( ! empty( $args['status'] ) ) {
            $where[]  = 'status = %s';
            $values[] = sanitize_key( $args['status'] );
        }
        if ( ! empty( $args['post_id'] ) ) {
            $where[]  = 'post_id = %d';
            $values[] = absint( $args['post_id'] );
        }

        $limit  = isset( $args['limit'] )  ? absint( $args['limit'] )  : 40;
        $offset = isset( $args['offset'] ) ? absint( $args['offset'] ) : 0;

        $sql = "SELECT * FROM {$table} WHERE " . implode( ' AND ', $where )
             . " ORDER BY created_at DESC LIMIT {$limit} OFFSET {$offset}";

        if ( $values ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
            $sql = $wpdb->prepare( $sql, $values );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
        return $wpdb->get_results( $sql );
    }

    /**
     * Count drafts (optionally filtered by status).
     *
     * @param  string|null $status Optional status filter.
     * @return int
     */
    public static function count( $status = null ) {
        global $wpdb;
        $table = self::table();

        if ( $status ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status ) );
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }

    /* ───────────────────────────── UPDATE ── */

    /**
     * Update one or more columns on a draft row.
     *
     * @param  int   $id   Row ID.
     * @param  array $data Column => value pairs.
     * @return bool
     */
    public static function update( $id, array $data ) {
        global $wpdb;

        $allowed = array(
            'status', 'sections_json', 'merged_content',
            'reviewed_at', 'reviewed_by',
        );

        $update = array();
        $format = array();

        foreach ( $data as $col => $val ) {
            if ( ! in_array( $col, $allowed, true ) ) {
                continue;
            }
            $update[ $col ] = $val;
            $format[]       = is_int( $val ) ? '%d' : '%s';
        }

        if ( empty( $update ) ) {
            return false;
        }

        return (bool) $wpdb->update(
            self::table(),
            $update,
            array( 'id' => absint( $id ) ),
            $format,
            array( '%d' )
        );
    }

    /* ───────────────────────────── DELETE ── */

    /**
     * Delete a draft row.
     *
     * @param  int $id Row ID.
     * @return bool
     */
    public static function delete( $id ) {
        global $wpdb;
        return (bool) $wpdb->delete( self::table(), array( 'id' => absint( $id ) ), array( '%d' ) );
    }
}
