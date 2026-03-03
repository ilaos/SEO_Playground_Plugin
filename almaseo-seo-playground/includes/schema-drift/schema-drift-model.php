<?php
/**
 * Schema Drift Monitor – Data Model
 *
 * Static CRUD class for the baseline and drift tables.
 *
 * @package AlmaSEO
 * @since   7.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Schema_Drift_Model {

    /* ── helpers ── */

    private static function baseline_table() {
        global $wpdb;
        return $wpdb->prefix . 'almaseo_schema_baseline';
    }

    private static function drift_table() {
        global $wpdb;
        return $wpdb->prefix . 'almaseo_schema_drift';
    }

    /* ================================================================
     *  BASELINE — READ / WRITE
     * ================================================================ */

    /**
     * Get all baseline entries for a post.
     */
    public static function get_baselines_for_post( $post_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM " . self::baseline_table() . " WHERE post_id = %d ORDER BY schema_type",
            $post_id
        ) );
    }

    /**
     * Get a baseline entry by post_id + schema_type.
     */
    public static function get_baseline( $post_id, $schema_type ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::baseline_table() . " WHERE post_id = %d AND schema_type = %s",
            $post_id,
            $schema_type
        ) );
    }

    /**
     * Get all baselines (summary).
     */
    public static function get_all_baselines() {
        global $wpdb;
        return $wpdb->get_results(
            "SELECT id, post_id, url, schema_type, captured_at FROM " . self::baseline_table() . " ORDER BY captured_at DESC"
        );
    }

    /**
     * Count baseline entries.
     */
    public static function count_baselines() {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . self::baseline_table() );
    }

    /**
     * Distinct post count with baselines.
     */
    public static function count_baseline_posts() {
        global $wpdb;
        return (int) $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM " . self::baseline_table() );
    }

    /**
     * Upsert a baseline entry.
     */
    public static function upsert_baseline( $data ) {
        global $wpdb;
        $table = self::baseline_table();

        $existing = self::get_baseline( $data['post_id'], $data['schema_type'] );

        if ( $existing ) {
            $wpdb->update(
                $table,
                array(
                    'url'         => $data['url'],
                    'schema_json' => $data['schema_json'],
                    'captured_at' => current_time( 'mysql', true ),
                ),
                array( 'id' => $existing->id ),
                array( '%s', '%s', '%s' ),
                array( '%d' )
            );
            return (int) $existing->id;
        }

        $wpdb->insert( $table, array(
            'post_id'     => absint( $data['post_id'] ),
            'url'         => $data['url'],
            'schema_type' => sanitize_text_field( $data['schema_type'] ),
            'schema_json' => $data['schema_json'],
            'captured_at' => current_time( 'mysql', true ),
        ) );

        return $wpdb->insert_id ? (int) $wpdb->insert_id : false;
    }

    /**
     * Delete all baselines for a post.
     */
    public static function delete_baselines_for_post( $post_id ) {
        global $wpdb;
        return $wpdb->delete( self::baseline_table(), array( 'post_id' => absint( $post_id ) ), array( '%d' ) );
    }

    /**
     * Clear all baselines.
     */
    public static function clear_baselines() {
        global $wpdb;
        return $wpdb->query( "TRUNCATE TABLE " . self::baseline_table() );
    }

    /* ================================================================
     *  DRIFT — READ / WRITE
     * ================================================================ */

    /**
     * Paginated list of drift findings with optional filters.
     *
     * @param array $args {
     *   @type int    $page       Page number (default 1).
     *   @type int    $per_page   Items per page (default 20, max 100).
     *   @type string $status     Filter by status.
     *   @type string $severity   Filter by severity.
     *   @type string $drift_type Filter by drift type.
     *   @type string $search     Search by post title.
     * }
     * @return array { items: array, total: int, pages: int }
     */
    public static function get_findings( $args = array() ) {
        global $wpdb;
        $table = self::drift_table();

        $per_page = min( absint( isset( $args['per_page'] ) ? $args['per_page'] : 20 ), 100 );
        $page     = max( 1, absint( isset( $args['page'] ) ? $args['page'] : 1 ) );
        $offset   = ( $page - 1 ) * $per_page;

        $where = array( '1=1' );
        $vals  = array();

        if ( ! empty( $args['status'] ) ) {
            $where[] = 'f.status = %s';
            $vals[]  = sanitize_key( $args['status'] );
        }

        if ( ! empty( $args['severity'] ) ) {
            $where[] = 'f.severity = %s';
            $vals[]  = sanitize_key( $args['severity'] );
        }

        if ( ! empty( $args['drift_type'] ) ) {
            $where[] = 'f.drift_type = %s';
            $vals[]  = sanitize_key( $args['drift_type'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where[] = 'p.post_title LIKE %s';
            $vals[]  = $like;
        }

        $where_sql = implode( ' AND ', $where );

        // Count.
        $count_sql = "SELECT COUNT(*) FROM {$table} f
            LEFT JOIN {$wpdb->posts} p ON f.post_id = p.ID
            WHERE {$where_sql}";

        $total = $vals
            ? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $vals ) )
            : (int) $wpdb->get_var( $count_sql );

        // Fetch.
        $select_sql = "SELECT f.* FROM {$table} f
            LEFT JOIN {$wpdb->posts} p ON f.post_id = p.ID
            WHERE {$where_sql}
            ORDER BY FIELD(f.severity, 'high', 'medium', 'low'), f.detected_at DESC
            LIMIT %d OFFSET %d";

        $query_vals = array_merge( $vals, array( $per_page, $offset ) );
        $items      = $wpdb->get_results( $wpdb->prepare( $select_sql, $query_vals ) );

        return array(
            'items' => $items ? $items : array(),
            'total' => $total,
            'pages' => $per_page > 0 ? (int) ceil( $total / $per_page ) : 1,
        );
    }

    /**
     * Single drift finding by ID.
     */
    public static function get_finding( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::drift_table() . " WHERE id = %d", $id
        ) );
    }

    /**
     * Insert a drift finding.
     *
     * @return int|false Insert ID or false.
     */
    public static function insert_finding( $data ) {
        global $wpdb;

        $row = array(
            'post_id'        => absint( $data['post_id'] ),
            'url'            => esc_url_raw( $data['url'] ),
            'drift_type'     => sanitize_key( $data['drift_type'] ),
            'schema_type'    => sanitize_text_field( isset( $data['schema_type'] ) ? $data['schema_type'] : '' ),
            'severity'       => sanitize_key( isset( $data['severity'] ) ? $data['severity'] : 'medium' ),
            'baseline_value' => isset( $data['baseline_value'] ) ? $data['baseline_value'] : null,
            'current_value'  => isset( $data['current_value'] ) ? $data['current_value'] : null,
            'diff_summary'   => isset( $data['diff_summary'] ) ? sanitize_text_field( $data['diff_summary'] ) : null,
            'suggestion'     => isset( $data['suggestion'] ) ? sanitize_text_field( $data['suggestion'] ) : null,
            'status'         => 'open',
            'detected_at'    => current_time( 'mysql', true ),
        );

        $wpdb->insert( self::drift_table(), $row );
        return $wpdb->insert_id ? (int) $wpdb->insert_id : false;
    }

    /**
     * Partial update by ID.
     */
    public static function update_finding( $id, $data ) {
        global $wpdb;

        $allowed = array( 'status', 'resolved_at', 'resolved_by' );
        $update  = array();
        $format  = array();

        foreach ( $allowed as $col ) {
            if ( isset( $data[ $col ] ) ) {
                $update[ $col ] = $data[ $col ];
                $format[]       = ( $col === 'resolved_by' ) ? '%d' : '%s';
            }
        }

        if ( empty( $update ) ) {
            return false;
        }

        return (bool) $wpdb->update( self::drift_table(), $update, array( 'id' => absint( $id ) ), $format, array( '%d' ) );
    }

    /**
     * Clear all open findings (used before re-scan).
     */
    public static function clear_open_findings() {
        global $wpdb;
        return $wpdb->query(
            "DELETE FROM " . self::drift_table() . " WHERE status = 'open'"
        );
    }

    /* ── STATS ── */

    /**
     * Counts grouped by severity, status, and drift type.
     */
    public static function get_stats() {
        global $wpdb;
        $table = self::drift_table();

        $stats = array(
            'total'    => 0,
            'open'     => 0,
            'resolved' => 0,
            'dismissed' => 0,
            'high'     => 0,
            'medium'   => 0,
            'low'      => 0,
            'by_type'  => array(),
        );

        // By status.
        $status_rows = $wpdb->get_results(
            "SELECT status, COUNT(*) AS cnt FROM {$table} GROUP BY status"
        );
        foreach ( $status_rows as $r ) {
            $stats['total'] += (int) $r->cnt;
            if ( isset( $stats[ $r->status ] ) ) {
                $stats[ $r->status ] = (int) $r->cnt;
            }
        }

        // By severity (open only).
        $sev_rows = $wpdb->get_results(
            "SELECT severity, COUNT(*) AS cnt FROM {$table} WHERE status = 'open' GROUP BY severity"
        );
        foreach ( $sev_rows as $r ) {
            if ( isset( $stats[ $r->severity ] ) ) {
                $stats[ $r->severity ] = (int) $r->cnt;
            }
        }

        // By drift type.
        $type_rows = $wpdb->get_results(
            "SELECT drift_type, COUNT(*) AS cnt FROM {$table} WHERE status = 'open' GROUP BY drift_type"
        );
        foreach ( $type_rows as $r ) {
            $stats['by_type'][ $r->drift_type ] = (int) $r->cnt;
        }

        return $stats;
    }
}
