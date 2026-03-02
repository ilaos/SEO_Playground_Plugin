<?php
/**
 * Refresh Queue – Data Model
 *
 * Static CRUD class for the refresh-queue table.
 *
 * @package AlmaSEO
 * @since   7.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Refresh_Queue_Model {

    /* ── helpers ── */

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'almaseo_refresh_queue';
    }

    /* ── READ ── */

    /**
     * Paginated list with optional filters.
     *
     * @param array $args {
     *   @type int    $page          Page number (default 1).
     *   @type int    $per_page      Items per page (default 20, max 100).
     *   @type string $status        Filter by status (queued|skipped|refreshed).
     *   @type string $priority_tier Filter by tier (high|medium|low).
     *   @type string $search        Search by post title.
     *   @type string $orderby       Column to sort by (default priority_score).
     *   @type string $order         ASC or DESC (default DESC).
     * }
     * @return array { items: array, total: int, pages: int }
     */
    public static function get_items( $args = array() ) {
        global $wpdb;
        $table = self::table();

        $per_page = min( absint( isset( $args['per_page'] ) ? $args['per_page'] : 20 ), 100 );
        $page     = max( 1, absint( isset( $args['page'] ) ? $args['page'] : 1 ) );
        $offset   = ( $page - 1 ) * $per_page;

        $where = array( '1=1' );
        $vals  = array();

        if ( ! empty( $args['status'] ) ) {
            $where[] = 'q.status = %s';
            $vals[]  = sanitize_key( $args['status'] );
        }

        if ( ! empty( $args['priority_tier'] ) ) {
            $where[] = 'q.priority_tier = %s';
            $vals[]  = sanitize_key( $args['priority_tier'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where[] = 'p.post_title LIKE %s';
            $vals[]  = $like;
        }

        $where_sql = implode( ' AND ', $where );

        // Validate orderby.
        $allowed_orderby = array(
            'priority_score', 'business_value', 'traffic_decline',
            'conversion_intent', 'opportunity_size', 'calculated_at',
        );
        $orderby = 'priority_score';
        if ( ! empty( $args['orderby'] ) && in_array( $args['orderby'], $allowed_orderby, true ) ) {
            $orderby = $args['orderby'];
        }
        $order = ( ! empty( $args['order'] ) && strtoupper( $args['order'] ) === 'ASC' ) ? 'ASC' : 'DESC';

        // Count.
        $count_sql = "SELECT COUNT(*) FROM {$table} q
            LEFT JOIN {$wpdb->posts} p ON q.post_id = p.ID
            WHERE {$where_sql}";

        $total = $vals
            ? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $vals ) )
            : (int) $wpdb->get_var( $count_sql );

        // Fetch.
        $select_sql = "SELECT q.* FROM {$table} q
            LEFT JOIN {$wpdb->posts} p ON q.post_id = p.ID
            WHERE {$where_sql}
            ORDER BY q.{$orderby} {$order}
            LIMIT %d OFFSET %d";

        $query_vals   = array_merge( $vals, array( $per_page, $offset ) );
        $items        = $wpdb->get_results( $wpdb->prepare( $select_sql, $query_vals ) );

        return array(
            'items' => $items ? $items : array(),
            'total' => $total,
            'pages' => $per_page > 0 ? (int) ceil( $total / $per_page ) : 1,
        );
    }

    /**
     * Single item by ID.
     */
    public static function get_item( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE id = %d", $id
        ) );
    }

    /**
     * Lookup by post ID.
     */
    public static function get_by_post_id( $post_id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE post_id = %d", $post_id
        ) );
    }

    /**
     * Top N by priority score (queued only).
     */
    public static function get_top( $limit = 10 ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE status = 'queued' ORDER BY priority_score DESC LIMIT %d",
            $limit
        ) );
    }

    /* ── WRITE ── */

    /**
     * Insert or update by post_id.
     *
     * During recalculation, preserves status=skipped for existing entries.
     *
     * @param array $data Must include post_id.
     * @return int|false Row ID or false on failure.
     */
    public static function upsert( $data ) {
        global $wpdb;
        $table = self::table();

        $post_id = absint( $data['post_id'] );
        if ( ! $post_id ) {
            return false;
        }

        $existing = self::get_by_post_id( $post_id );

        $row = array(
            'post_id'           => $post_id,
            'priority_score'    => isset( $data['priority_score'] )    ? (float) $data['priority_score']    : 0,
            'business_value'    => isset( $data['business_value'] )    ? (float) $data['business_value']    : 0,
            'traffic_decline'   => isset( $data['traffic_decline'] )   ? (float) $data['traffic_decline']   : 0,
            'conversion_intent' => isset( $data['conversion_intent'] ) ? (float) $data['conversion_intent'] : 0,
            'opportunity_size'  => isset( $data['opportunity_size'] )  ? (float) $data['opportunity_size']  : 0,
            'priority_tier'     => isset( $data['priority_tier'] )     ? sanitize_key( $data['priority_tier'] ) : 'low',
            'reason'            => isset( $data['reason'] )            ? sanitize_text_field( $data['reason'] ) : null,
            'source'            => isset( $data['source'] )            ? sanitize_key( $data['source'] )      : 'auto',
            'calculated_at'     => current_time( 'mysql', true ),
        );

        $format = array( '%d', '%f', '%f', '%f', '%f', '%f', '%s', '%s', '%s', '%s', '%s' );

        if ( $existing ) {
            // Preserve status if already skipped.
            if ( $existing->status === 'skipped' && ! isset( $data['force_status'] ) ) {
                // Don't overwrite status.
            } else {
                $row['status'] = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'queued';
            }

            $wpdb->update( $table, $row, array( 'id' => $existing->id ), null, array( '%d' ) );
            return (int) $existing->id;
        }

        $row['status'] = isset( $data['status'] ) ? sanitize_key( $data['status'] ) : 'queued';
        $wpdb->insert( $table, $row );
        return $wpdb->insert_id ? (int) $wpdb->insert_id : false;
    }

    /**
     * Partial update by ID.
     */
    public static function update_item( $id, $data ) {
        global $wpdb;

        $allowed = array(
            'status', 'priority_tier', 'reason', 'refreshed_at',
        );

        $update = array();
        $format = array();

        foreach ( $allowed as $col ) {
            if ( isset( $data[ $col ] ) ) {
                $update[ $col ] = $data[ $col ];
                $format[]       = '%s';
            }
        }

        if ( empty( $update ) ) {
            return false;
        }

        return (bool) $wpdb->update( self::table(), $update, array( 'id' => $id ), $format, array( '%d' ) );
    }

    /**
     * Hard delete.
     */
    public static function delete_item( $id ) {
        global $wpdb;
        return (bool) $wpdb->delete( self::table(), array( 'id' => absint( $id ) ), array( '%d' ) );
    }

    /**
     * Delete all rows.
     */
    public static function clear_queue() {
        global $wpdb;
        return $wpdb->query( "TRUNCATE TABLE " . self::table() );
    }

    /**
     * Remove entries for posts that no longer exist.
     */
    public static function prune_orphaned() {
        global $wpdb;
        $table = self::table();
        return $wpdb->query(
            "DELETE q FROM {$table} q
             LEFT JOIN {$wpdb->posts} p ON q.post_id = p.ID
             WHERE p.ID IS NULL"
        );
    }

    /* ── STATS ── */

    /**
     * Counts grouped by priority tier (queued only) + totals.
     *
     * @return array { high: int, medium: int, low: int, total_queued: int, total_skipped: int }
     */
    public static function get_stats() {
        global $wpdb;
        $table = self::table();

        $rows = $wpdb->get_results(
            "SELECT priority_tier, status, COUNT(*) AS cnt FROM {$table} GROUP BY priority_tier, status"
        );

        $stats = array(
            'high'          => 0,
            'medium'        => 0,
            'low'           => 0,
            'total_queued'  => 0,
            'total_skipped' => 0,
            'total_refreshed' => 0,
        );

        foreach ( $rows as $r ) {
            if ( $r->status === 'queued' ) {
                $stats['total_queued'] += (int) $r->cnt;
                if ( isset( $stats[ $r->priority_tier ] ) ) {
                    $stats[ $r->priority_tier ] += (int) $r->cnt;
                }
            } elseif ( $r->status === 'skipped' ) {
                $stats['total_skipped'] += (int) $r->cnt;
            } elseif ( $r->status === 'refreshed' ) {
                $stats['total_refreshed'] += (int) $r->cnt;
            }
        }

        return $stats;
    }
}
