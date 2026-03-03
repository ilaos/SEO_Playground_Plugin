<?php
/**
 * Featured Snippet Targeting – Data Model
 *
 * Static CRUD class for the snippet_targets table.
 *
 * @package AlmaSEO
 * @since   7.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Snippet_Targets_Model {

    /* ── helpers ── */

    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'almaseo_snippet_targets';
    }

    /* ================================================================
     *  READ
     * ================================================================ */

    /**
     * Paginated list with optional filters.
     *
     * @param array $args {
     *   @type int    $page           Page number (default 1).
     *   @type int    $per_page       Items per page (default 20, max 100).
     *   @type string $status         Filter by status.
     *   @type string $snippet_format Filter by format.
     *   @type string $search         Search by query or post title.
     *   @type string $orderby        Column to sort by.
     *   @type string $order          ASC or DESC.
     * }
     * @return array { items: array, total: int, pages: int }
     */
    public static function get_targets( $args = array() ) {
        global $wpdb;
        $table = self::table();

        $per_page = min( absint( isset( $args['per_page'] ) ? $args['per_page'] : 20 ), 100 );
        $page     = max( 1, absint( isset( $args['page'] ) ? $args['page'] : 1 ) );
        $offset   = ( $page - 1 ) * $per_page;

        $where = array( '1=1' );
        $vals  = array();

        if ( ! empty( $args['status'] ) ) {
            $where[] = 't.status = %s';
            $vals[]  = sanitize_key( $args['status'] );
        }

        if ( ! empty( $args['snippet_format'] ) ) {
            $where[] = 't.snippet_format = %s';
            $vals[]  = sanitize_key( $args['snippet_format'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where[] = '(t.query LIKE %s OR p.post_title LIKE %s)';
            $vals[]  = $like;
            $vals[]  = $like;
        }

        $where_sql = implode( ' AND ', $where );

        // Validate orderby.
        $allowed_orderby = array(
            'created_at', 'status', 'current_position', 'search_volume', 'snippet_format',
        );
        $orderby = 'created_at';
        if ( ! empty( $args['orderby'] ) && in_array( $args['orderby'], $allowed_orderby, true ) ) {
            $orderby = $args['orderby'];
        }
        $order = ( ! empty( $args['order'] ) && strtoupper( $args['order'] ) === 'ASC' ) ? 'ASC' : 'DESC';

        // Count.
        $count_sql = "SELECT COUNT(*) FROM {$table} t
            LEFT JOIN {$wpdb->posts} p ON t.post_id = p.ID
            WHERE {$where_sql}";

        $total = $vals
            ? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $vals ) )
            : (int) $wpdb->get_var( $count_sql );

        // Fetch.
        $select_sql = "SELECT t.* FROM {$table} t
            LEFT JOIN {$wpdb->posts} p ON t.post_id = p.ID
            WHERE {$where_sql}
            ORDER BY t.{$orderby} {$order}
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
     * Single target by ID.
     */
    public static function get_target( $id ) {
        global $wpdb;
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE id = %d", $id
        ) );
    }

    /**
     * All targets for a specific post.
     */
    public static function get_targets_for_post( $post_id ) {
        global $wpdb;
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE post_id = %d ORDER BY created_at DESC",
            $post_id
        ) );
    }

    /* ================================================================
     *  WRITE
     * ================================================================ */

    /**
     * Insert a single target.
     *
     * @return int|false Insert ID or false.
     */
    public static function insert_target( $data ) {
        global $wpdb;

        $row = array(
            'post_id'          => absint( $data['post_id'] ),
            'query'            => sanitize_text_field( $data['query'] ),
            'snippet_format'   => sanitize_key( isset( $data['snippet_format'] ) ? $data['snippet_format'] : 'paragraph' ),
            'current_position' => isset( $data['current_position'] ) ? absint( $data['current_position'] ) : null,
            'search_volume'    => isset( $data['search_volume'] ) ? absint( $data['search_volume'] ) : null,
            'draft_content'    => isset( $data['draft_content'] ) ? wp_kses_post( $data['draft_content'] ) : null,
            'status'           => 'opportunity',
            'source'           => sanitize_key( isset( $data['source'] ) ? $data['source'] : 'dashboard' ),
            'created_at'       => current_time( 'mysql', true ),
        );

        $wpdb->insert( self::table(), $row );
        return $wpdb->insert_id ? (int) $wpdb->insert_id : false;
    }

    /**
     * Partial update by ID.
     */
    public static function update_target( $id, $data ) {
        global $wpdb;

        $allowed = array(
            'draft_content', 'original_section', 'content_hash',
            'status', 'applied_at', 'applied_by', 'reviewed_at', 'reviewed_by',
            'snippet_format', 'current_position', 'search_volume',
        );
        $update = array();
        $format = array();

        foreach ( $allowed as $col ) {
            if ( array_key_exists( $col, $data ) ) {
                $update[ $col ] = $data[ $col ];
                if ( in_array( $col, array( 'applied_by', 'reviewed_by', 'current_position', 'search_volume' ), true ) ) {
                    $format[] = '%d';
                } else {
                    $format[] = '%s';
                }
            }
        }

        if ( empty( $update ) ) {
            return false;
        }

        return (bool) $wpdb->update( self::table(), $update, array( 'id' => absint( $id ) ), $format, array( '%d' ) );
    }

    /**
     * Delete a target.
     */
    public static function delete_target( $id ) {
        global $wpdb;
        return $wpdb->delete( self::table(), array( 'id' => absint( $id ) ), array( '%d' ) );
    }

    /* ================================================================
     *  STATS
     * ================================================================ */

    /**
     * Counts grouped by status and format.
     */
    public static function get_stats() {
        global $wpdb;
        $table = self::table();

        $stats = array(
            'total'       => 0,
            'opportunity' => 0,
            'draft'       => 0,
            'approved'    => 0,
            'applied'     => 0,
            'rejected'    => 0,
            'won'         => 0,
            'lost'        => 0,
            'expired'     => 0,
            'by_format'   => array(),
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

        // By format (non-rejected/expired only).
        $format_rows = $wpdb->get_results(
            "SELECT snippet_format, COUNT(*) AS cnt FROM {$table}
             WHERE status NOT IN ('rejected', 'expired')
             GROUP BY snippet_format"
        );
        foreach ( $format_rows as $r ) {
            $stats['by_format'][ $r->snippet_format ] = (int) $r->cnt;
        }

        return $stats;
    }
}
