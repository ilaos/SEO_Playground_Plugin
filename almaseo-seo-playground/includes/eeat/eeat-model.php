<?php
/**
 * E-E-A-T Enforcement – Data Model
 *
 * Static CRUD class for the eeat_findings table.
 *
 * @package AlmaSEO
 * @since   7.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_EEAT_Model {

    /* ── helpers ── */

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'almaseo_eeat_findings';
    }

    /* ── READ ── */

    /**
     * Paginated list with optional filters.
     *
     * @param array $args {
     *   @type int    $page          Page number (default 1).
     *   @type int    $per_page      Items per page (default 20, max 100).
     *   @type string $status        Filter by status (open|resolved|dismissed).
     *   @type string $severity      Filter by severity (high|medium|low).
     *   @type string $finding_type  Filter by finding type.
     *   @type int    $post_id       Filter by post ID.
     *   @type string $search        Search by post title.
     *   @type string $orderby       Column to sort by.
     *   @type string $order         ASC or DESC (default DESC).
     * }
     * @return array { items: array, total: int, pages: int }
     */
    public static function get_findings( $args = array() ) {
        global $wpdb;
        $table = self::table();

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

        if ( ! empty( $args['finding_type'] ) ) {
            $where[] = 'f.finding_type = %s';
            $vals[]  = sanitize_key( $args['finding_type'] );
        }

        if ( ! empty( $args['post_id'] ) ) {
            $where[] = 'f.post_id = %d';
            $vals[]  = absint( $args['post_id'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where[] = 'p.post_title LIKE %s';
            $vals[]  = $like;
        }

        $where_sql = implode( ' AND ', $where );

        // Validate orderby.
        $allowed_orderby = array(
            'severity', 'scanned_at', 'finding_type', 'status', 'post_id',
        );
        $orderby = 'scanned_at';
        if ( ! empty( $args['orderby'] ) && in_array( $args['orderby'], $allowed_orderby, true ) ) {
            $orderby = $args['orderby'];
        }
        $order = ( ! empty( $args['order'] ) && strtoupper( $args['order'] ) === 'ASC' ) ? 'ASC' : 'DESC';

        // Custom sort: severity DESC maps high > medium > low.
        $order_clause = "f.{$orderby} {$order}";
        if ( $orderby === 'severity' ) {
            $order_clause = "FIELD(f.severity, 'high', 'medium', 'low') " . ( $order === 'DESC' ? 'ASC' : 'DESC' ) . ", f.scanned_at DESC";
        }

        // Count.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $count_sql = "SELECT COUNT(*) FROM {$table} f
            LEFT JOIN {$wpdb->posts} p ON f.post_id = p.ID
            WHERE {$where_sql}";

        $total = $vals
            ? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $vals ) ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- dynamically built with safe placeholders
            : (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix

        // Fetch.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $select_sql = "SELECT f.* FROM {$table} f
            LEFT JOIN {$wpdb->posts} p ON f.post_id = p.ID
            WHERE {$where_sql}
            ORDER BY {$order_clause}
            LIMIT %d OFFSET %d";

        $query_vals = array_merge( $vals, array( $per_page, $offset ) );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- dynamically built with safe placeholders
        $items      = $wpdb->get_results( $wpdb->prepare( $select_sql, $query_vals ) );

        return array(
            'items' => $items ? $items : array(),
            'total' => $total,
            'pages' => $per_page > 0 ? (int) ceil( $total / $per_page ) : 1,
        );
    }

    /**
     * Single finding by ID.
     */
    public static function get_finding( $id ) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE id = %d", $id
        ) );
    }

    /**
     * All findings for a specific post.
     */
    public static function get_findings_for_post( $post_id ) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE post_id = %d ORDER BY FIELD(severity, 'high', 'medium', 'low'), scanned_at DESC",
            $post_id
        ) );
    }

    /* ── WRITE ── */

    /**
     * Insert a single finding.
     *
     * @param array $data Finding data.
     * @return int|false Insert ID or false.
     */
    public static function insert_finding( $data ) {
        global $wpdb;

        $row = array(
            'post_id'         => absint( $data['post_id'] ),
            'finding_type'    => sanitize_key( $data['finding_type'] ),
            'severity'        => sanitize_key( $data['severity'] ),
            'detected_value'  => sanitize_text_field( $data['detected_value'] ),
            'context_snippet' => wp_kses_post( $data['context_snippet'] ),
            'suggestion'      => isset( $data['suggestion'] ) ? sanitize_text_field( $data['suggestion'] ) : null,
            'location'        => isset( $data['location'] ) ? sanitize_key( $data['location'] ) : 'body',
            'status'          => 'open',
            'scanned_at'      => current_time( 'mysql', true ),
        );

        $wpdb->insert( self::table(), $row );
        return $wpdb->insert_id ? (int) $wpdb->insert_id : false;
    }

    /**
     * Bulk insert findings.
     *
     * @param array $findings Array of finding arrays.
     * @return int Number of inserted rows.
     */
    public static function insert_batch( $findings ) {
        $inserted = 0;
        foreach ( $findings as $finding ) {
            if ( self::insert_finding( $finding ) ) {
                $inserted++;
            }
        }
        return $inserted;
    }

    /**
     * Partial update by ID.
     *
     * @param int   $id   Finding ID.
     * @param array $data Fields to update.
     * @return bool
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

        return (bool) $wpdb->update( self::table(), $update, array( 'id' => absint( $id ) ), $format, array( '%d' ) );
    }

    /**
     * Delete all findings for a post.
     */
    public static function delete_findings_for_post( $post_id ) {
        global $wpdb;
        return $wpdb->delete( self::table(), array( 'post_id' => absint( $post_id ) ), array( '%d' ) );
    }

    /**
     * Clear all findings.
     */
    public static function clear_all() {
        global $wpdb;
        return $wpdb->query( "TRUNCATE TABLE " . self::table() ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix
    }

    /**
     * Clear only open findings (preserves resolved/dismissed).
     */
    public static function clear_open() {
        global $wpdb;
        return $wpdb->query( "DELETE FROM " . self::table() . " WHERE status = 'open'" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix
    }

    /**
     * Get keys for all resolved/dismissed findings.
     *
     * Returns an associative array keyed by "post_id:finding_type"
     * so the scan engine can skip re-inserting dismissed findings.
     *
     * @return array Keyed by "post_id:finding_type" => true.
     */
    public static function get_dismissed_keys() {
        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix
        $rows = $wpdb->get_results(
            "SELECT post_id, finding_type FROM " . self::table() . " WHERE status IN ('resolved', 'dismissed')"
        );
        $keys = array();
        foreach ( $rows as $r ) {
            $keys[ $r->post_id . ':' . $r->finding_type ] = true;
        }
        return $keys;
    }

    /* ── STATS ── */

    /**
     * Counts grouped by severity, status, and finding_type.
     *
     * @return array
     */
    public static function get_stats() {
        global $wpdb;
        $table = self::table();

        $stats = array(
            'total'     => 0,
            'open'      => 0,
            'resolved'  => 0,
            'dismissed' => 0,
            'high'      => 0,
            'medium'    => 0,
            'low'       => 0,
            'by_type'   => array(),
        );

        // By status.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $status_rows = $wpdb->get_results(
            "SELECT status, COUNT(*) AS cnt FROM {$table} GROUP BY status"
        );
        foreach ( $status_rows as $r ) {
            $stats['total'] += (int) $r->cnt;
            if ( isset( $stats[ $r->status ] ) ) {
                $stats[ $r->status ] = (int) $r->cnt;
            }
        }

        // By severity (open only — these are actionable).
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $sev_rows = $wpdb->get_results(
            "SELECT severity, COUNT(*) AS cnt FROM {$table} WHERE status = 'open' GROUP BY severity"
        );
        foreach ( $sev_rows as $r ) {
            if ( isset( $stats[ $r->severity ] ) ) {
                $stats[ $r->severity ] = (int) $r->cnt;
            }
        }

        // By finding type.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $type_rows = $wpdb->get_results(
            "SELECT finding_type, COUNT(*) AS cnt FROM {$table} WHERE status = 'open' GROUP BY finding_type"
        );
        foreach ( $type_rows as $r ) {
            $stats['by_type'][ $r->finding_type ] = (int) $r->cnt;
        }

        return $stats;
    }
}
