<?php
/**
 * GSC Monitor – Data Model
 *
 * Static CRUD class for the gsc_monitor table.
 * Supports dedup lookups for the push endpoint.
 *
 * @package AlmaSEO
 * @since   7.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_GSC_Monitor_Model {

    /* ── helpers ── */

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'almaseo_gsc_monitor';
    }

    /* ── READ ── */

    /**
     * Paginated list with optional filters.
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

        if ( ! empty( $args['subtype'] ) ) {
            $where[] = 'f.subtype = %s';
            $vals[]  = sanitize_key( $args['subtype'] );
        }

        if ( ! empty( $args['post_id'] ) ) {
            $where[] = 'f.post_id = %d';
            $vals[]  = absint( $args['post_id'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where[] = '(f.url LIKE %s OR p.post_title LIKE %s)';
            $vals[]  = $like;
            $vals[]  = $like;
        }

        $where_sql = implode( ' AND ', $where );

        // Validate orderby.
        $allowed_orderby = array( 'severity', 'last_seen', 'first_seen', 'finding_type', 'status' );
        $orderby = 'last_seen';
        if ( ! empty( $args['orderby'] ) && in_array( $args['orderby'], $allowed_orderby, true ) ) {
            $orderby = $args['orderby'];
        }
        $order = ( ! empty( $args['order'] ) && strtoupper( $args['order'] ) === 'ASC' ) ? 'ASC' : 'DESC';

        $order_clause = "f.{$orderby} {$order}";
        if ( $orderby === 'severity' ) {
            $order_clause = "FIELD(f.severity, 'high', 'medium', 'low') " . ( $order === 'DESC' ? 'ASC' : 'DESC' ) . ", f.last_seen DESC";
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
            "SELECT * FROM " . self::table() . " WHERE post_id = %d ORDER BY last_seen DESC",
            $post_id
        ) );
    }

    /**
     * Dedup lookup: find existing open finding by URL + type + subtype.
     */
    public static function find_existing( $url, $finding_type, $subtype ) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE url = %s AND finding_type = %s AND subtype = %s AND status = 'open' LIMIT 1",
            $url, $finding_type, $subtype
        ) );
    }

    /* ── WRITE ── */

    /**
     * Insert a single finding.
     */
    public static function insert_finding( $data ) {
        global $wpdb;

        $row = array(
            'post_id'        => isset( $data['post_id'] ) ? absint( $data['post_id'] ) : null,
            'url'            => esc_url_raw( $data['url'] ),
            'finding_type'   => sanitize_key( $data['finding_type'] ),
            'subtype'        => sanitize_key( $data['subtype'] ),
            'severity'       => sanitize_key( isset( $data['severity'] ) ? $data['severity'] : 'medium' ),
            'detected_value' => wp_kses_post( $data['detected_value'] ),
            'expected_value' => isset( $data['expected_value'] ) ? wp_kses_post( $data['expected_value'] ) : null,
            'context_data'   => isset( $data['context_data'] ) ? wp_json_encode( $data['context_data'] ) : null,
            'suggestion'     => isset( $data['suggestion'] ) ? sanitize_text_field( $data['suggestion'] ) : null,
            'status'         => 'open',
            'first_seen'     => current_time( 'mysql', true ),
            'last_seen'      => current_time( 'mysql', true ),
        );

        $wpdb->insert( self::table(), $row );
        return $wpdb->insert_id ? (int) $wpdb->insert_id : false;
    }

    /**
     * Partial update by ID.
     */
    public static function update_finding( $id, $data ) {
        global $wpdb;

        $allowed = array( 'status', 'resolved_at', 'resolved_by', 'last_seen', 'detected_value', 'expected_value', 'context_data', 'severity' );
        $update  = array();
        $format  = array();

        foreach ( $allowed as $col ) {
            if ( isset( $data[ $col ] ) ) {
                $update[ $col ] = $data[ $col ];
                $format[]       = in_array( $col, array( 'resolved_by' ), true ) ? '%d' : '%s';
            }
        }

        if ( empty( $update ) ) {
            return false;
        }

        return (bool) $wpdb->update( self::table(), $update, array( 'id' => absint( $id ) ), $format, array( '%d' ) );
    }

    /**
     * Bulk update status for multiple IDs.
     */
    public static function bulk_update( $ids, $data ) {
        $updated = 0;
        foreach ( $ids as $id ) {
            if ( self::update_finding( absint( $id ), $data ) ) {
                $updated++;
            }
        }
        return $updated;
    }

    /**
     * Auto-dismiss old findings.
     */
    public static function auto_dismiss_old( $days ) {
        global $wpdb;
        $cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix
        return $wpdb->query( $wpdb->prepare(
            "UPDATE " . self::table() . " SET status = 'dismissed', resolved_at = %s WHERE status = 'open' AND last_seen < %s",
            current_time( 'mysql', true ),
            $cutoff
        ) );
    }

    /* ── STATS ── */

    /**
     * Counts grouped by severity, status. Optionally filtered by finding_type.
     */
    public static function get_stats( $finding_type = '' ) {
        global $wpdb;
        $table = self::table();

        $type_where = '';
        $vals       = array();
        if ( ! empty( $finding_type ) ) {
            $type_where = ' AND finding_type = %s';
            $vals[]     = sanitize_key( $finding_type );
        }

        $stats = array(
            'total'     => 0,
            'open'      => 0,
            'resolved'  => 0,
            'dismissed' => 0,
            'high'      => 0,
            'medium'    => 0,
            'low'       => 0,
        );

        // By status.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $status_sql = "SELECT status, COUNT(*) AS cnt FROM {$table} WHERE 1=1{$type_where} GROUP BY status";
        $status_rows = $vals
            ? $wpdb->get_results( $wpdb->prepare( $status_sql, $vals ) ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- dynamically built with safe placeholders
            : $wpdb->get_results( $status_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix

        foreach ( $status_rows as $r ) {
            $stats['total'] += (int) $r->cnt;
            if ( isset( $stats[ $r->status ] ) ) {
                $stats[ $r->status ] = (int) $r->cnt;
            }
        }

        // By severity (open only).
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $sev_sql = "SELECT severity, COUNT(*) AS cnt FROM {$table} WHERE status = 'open'{$type_where} GROUP BY severity";
        $sev_rows = $vals
            ? $wpdb->get_results( $wpdb->prepare( $sev_sql, $vals ) ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- dynamically built with safe placeholders
            : $wpdb->get_results( $sev_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix

        foreach ( $sev_rows as $r ) {
            if ( isset( $stats[ $r->severity ] ) ) {
                $stats[ $r->severity ] = (int) $r->cnt;
            }
        }

        return $stats;
    }
}
