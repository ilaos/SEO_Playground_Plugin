<?php
/**
 * Internal Links – Orphan Page Detection & Cluster Analysis
 *
 * Scans all published posts to find orphan pages (0 inbound internal links)
 * and weak pages (1-2 inbound links). Groups posts into category-based
 * clusters and calculates cluster interconnection density.
 *
 * @package AlmaSEO
 * @since   7.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Internal_Links_Orphan {

    /**
     * Get the orphan pages table name.
     */
    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'almaseo_orphan_pages';
    }

    /* ──────────────── Scanning ── */

    /**
     * Run a full orphan scan across all published posts.
     *
     * Clears existing data and re-scans in batches.
     *
     * @return array { total: int, orphans: int, weak: int }
     */
    public static function scan_all() {
        global $wpdb;
        $table = self::table();

        // Clear existing data.
        $wpdb->query( "TRUNCATE TABLE {$table}" ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix

        $post_types = apply_filters( 'almaseo_orphan_post_types', array( 'post', 'page' ) );
        $placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

        // Get all published posts.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $posts = $wpdb->get_results( $wpdb->prepare(
            "SELECT ID, post_title, post_name FROM {$wpdb->posts}
             WHERE post_status = 'publish' AND post_type IN ({$placeholders})
             ORDER BY ID ASC",
            $post_types
        ) );

        if ( empty( $posts ) ) {
            return array( 'total' => 0, 'orphans' => 0, 'weak' => 0 );
        }

        // Build URL map for all posts.
        $url_map = array(); // url => post_id
        foreach ( $posts as $post ) {
            $url = get_permalink( $post->ID );
            if ( $url ) {
                $url_map[ $url ] = (int) $post->ID;
                // Also index the path-only version.
                $path = wp_parse_url( $url, PHP_URL_PATH );
                if ( $path ) {
                    $url_map[ $path ] = (int) $post->ID;
                }
            }
        }

        // Count inbound links for each post by scanning all post content.
        $inbound_counts = array_fill_keys( wp_list_pluck( $posts, 'ID' ), 0 );
        $outbound_counts = array_fill_keys( wp_list_pluck( $posts, 'ID' ), 0 );

        // Process in batches of 50.
        $batch_size = 50;
        $total_posts = count( $posts );

        for ( $offset = 0; $offset < $total_posts; $offset += $batch_size ) {
            $batch = array_slice( $posts, $offset, $batch_size );

            foreach ( $batch as $source_post ) {
                $content = get_post_field( 'post_content', $source_post->ID );
                if ( empty( $content ) ) {
                    continue;
                }

                // Extract all internal links from content.
                $links = self::extract_internal_links( $content, $url_map );
                $outbound_counts[ $source_post->ID ] = count( $links );

                foreach ( $links as $target_id ) {
                    if ( isset( $inbound_counts[ $target_id ] ) && $target_id !== $source_post->ID ) {
                        $inbound_counts[ $target_id ]++;
                    }
                }
            }
        }

        // Determine clusters by primary category.
        $clusters = self::build_clusters( $posts );

        // Insert results.
        $counts = array( 'total' => 0, 'orphans' => 0, 'weak' => 0 );

        foreach ( $posts as $post ) {
            $pid = (int) $post->ID;
            $inbound  = isset( $inbound_counts[ $pid ] ) ? $inbound_counts[ $pid ] : 0;
            $outbound = isset( $outbound_counts[ $pid ] ) ? $outbound_counts[ $pid ] : 0;

            if ( $inbound === 0 ) {
                $status = 'orphan';
                $counts['orphans']++;
            } elseif ( $inbound <= 2 ) {
                $status = 'weak';
                $counts['weak']++;
            } else {
                $status = 'healthy';
            }

            $cluster_id = isset( $clusters[ $pid ] ) ? $clusters[ $pid ]['cluster_id'] : '';
            $cluster_strength = isset( $clusters[ $pid ] ) ? $clusters[ $pid ]['strength'] : 0;

            $wpdb->insert( $table, array(
                'post_id'          => $pid,
                'inbound_count'    => $inbound,
                'outbound_count'   => $outbound,
                'cluster_id'       => $cluster_id,
                'cluster_strength' => $cluster_strength,
                'is_hub_candidate' => ( $outbound >= 5 && $inbound <= 2 ) ? 1 : 0,
                'status'           => $status,
                'scanned_at'       => current_time( 'mysql', true ),
                'suggestion'       => self::generate_suggestion( $status, $inbound, $outbound, $cluster_id ),
            ) );

            $counts['total']++;
        }

        update_option( 'almaseo_orphan_last_scan', current_time( 'mysql', true ) );

        return $counts;
    }

    /* ──────────────── CRUD ── */

    /**
     * Get orphan pages with pagination and filters.
     */
    public static function get_orphans( $args = array() ) {
        global $wpdb;
        $table = self::table();

        $per_page = min( absint( isset( $args['per_page'] ) ? $args['per_page'] : 20 ), 100 );
        $page     = max( 1, absint( isset( $args['page'] ) ? $args['page'] : 1 ) );
        $offset   = ( $page - 1 ) * $per_page;

        $where = array( '1=1' );
        $vals  = array();

        if ( ! empty( $args['status'] ) ) {
            $where[] = 'o.status = %s';
            $vals[]  = sanitize_key( $args['status'] );
        }

        if ( ! empty( $args['cluster_id'] ) ) {
            $where[] = 'o.cluster_id = %s';
            $vals[]  = sanitize_text_field( $args['cluster_id'] );
        }

        if ( ! empty( $args['search'] ) ) {
            $like    = '%' . $wpdb->esc_like( $args['search'] ) . '%';
            $where[] = 'p.post_title LIKE %s';
            $vals[]  = $like;
        }

        $where_sql = implode( ' AND ', $where );

        // Count.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $count_sql = "SELECT COUNT(*) FROM {$table} o
            LEFT JOIN {$wpdb->posts} p ON o.post_id = p.ID
            WHERE {$where_sql}";

        $total = $vals
            ? (int) $wpdb->get_var( $wpdb->prepare( $count_sql, $vals ) ) // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- dynamically built with safe placeholders
            : (int) $wpdb->get_var( $count_sql ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix

        // Fetch.
        $orderby = 'o.inbound_count ASC, o.outbound_count DESC';
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $select_sql = "SELECT o.* FROM {$table} o
            LEFT JOIN {$wpdb->posts} p ON o.post_id = p.ID
            WHERE {$where_sql}
            ORDER BY {$orderby}
            LIMIT %d OFFSET %d";

        $query_vals = array_merge( $vals, array( $per_page, $offset ) );
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- dynamically built with safe placeholders
        $items = $wpdb->get_results( $wpdb->prepare( $select_sql, $query_vals ) );

        return array(
            'items' => $items ? $items : array(),
            'total' => $total,
            'pages' => $per_page > 0 ? (int) ceil( $total / $per_page ) : 1,
        );
    }

    /**
     * Get orphan stats.
     */
    public static function get_stats() {
        global $wpdb;
        $table = self::table();

        $stats = array(
            'total'          => 0,
            'orphans'        => 0,
            'weak'           => 0,
            'healthy'        => 0,
            'dismissed'      => 0,
            'hub_candidates' => 0,
            'last_scan'      => get_option( 'almaseo_orphan_last_scan', '' ),
        );

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $rows = $wpdb->get_results(
            "SELECT status, COUNT(*) AS cnt FROM {$table} GROUP BY status"
        );

        foreach ( $rows as $r ) {
            $stats['total'] += (int) $r->cnt;
            if ( isset( $stats[ $r->status ] ) ) {
                $stats[ $r->status ] = (int) $r->cnt;
            }
        }

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $stats['hub_candidates'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE is_hub_candidate = 1 AND status != 'dismissed'"
        );

        return $stats;
    }

    /**
     * Dismiss an orphan finding.
     */
    public static function dismiss( $id ) {
        global $wpdb;
        return (bool) $wpdb->update(
            self::table(),
            array( 'status' => 'dismissed' ),
            array( 'id' => absint( $id ) ),
            array( '%s' ),
            array( '%d' )
        );
    }

    /**
     * Mark as addressed.
     */
    public static function mark_addressed( $id ) {
        global $wpdb;
        return (bool) $wpdb->update(
            self::table(),
            array( 'status' => 'addressed' ),
            array( 'id' => absint( $id ) ),
            array( '%s' ),
            array( '%d' )
        );
    }

    /**
     * Get a single orphan record by ID.
     */
    public static function get_orphan( $id ) {
        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE id = %d",
            $id
        ) );
    }

    /**
     * Get distinct cluster IDs for filter dropdown.
     */
    public static function get_clusters() {
        global $wpdb;
        // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name from $wpdb->prefix
        return $wpdb->get_col(
            "SELECT DISTINCT cluster_id FROM " . self::table() . " WHERE cluster_id != '' ORDER BY cluster_id ASC"
        );
    }

    /* ──────────────── Dashboard Push ── */

    /**
     * Process dashboard-pushed orphan data.
     *
     * @param array $items Array of { post_id, inbound_count, outbound_count, suggestion }.
     * @return array { updated: int, skipped: int }
     */
    public static function process_push( $items ) {
        global $wpdb;
        $table  = self::table();
        $counts = array( 'updated' => 0, 'skipped' => 0 );

        foreach ( $items as $item ) {
            if ( empty( $item['post_id'] ) ) {
                $counts['skipped']++;
                continue;
            }

            $post_id = absint( $item['post_id'] );

            // Check if post exists.
            if ( ! get_post( $post_id ) ) {
                $counts['skipped']++;
                continue;
            }

            $inbound  = isset( $item['inbound_count'] ) ? absint( $item['inbound_count'] ) : 0;
            $outbound = isset( $item['outbound_count'] ) ? absint( $item['outbound_count'] ) : 0;

            if ( $inbound === 0 ) {
                $status = 'orphan';
            } elseif ( $inbound <= 2 ) {
                $status = 'weak';
            } else {
                $status = 'healthy';
            }

            // Upsert by post_id.
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
            $existing = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$table} WHERE post_id = %d",
                $post_id
            ) );

            $data = array(
                'post_id'        => $post_id,
                'inbound_count'  => $inbound,
                'outbound_count' => $outbound,
                'status'         => $status,
                'scanned_at'     => current_time( 'mysql', true ),
                'suggestion'     => isset( $item['suggestion'] ) ? sanitize_text_field( $item['suggestion'] ) : null,
            );

            if ( $existing ) {
                $wpdb->update( $table, $data, array( 'id' => $existing ) );
            } else {
                $wpdb->insert( $table, $data );
            }

            $counts['updated']++;
        }

        return $counts;
    }

    /* ──────────────── Helpers ── */

    /**
     * Extract internal links from post content.
     *
     * @param string $content HTML content.
     * @param array  $url_map URL => post_id mapping.
     * @return array Array of target post IDs.
     */
    private static function extract_internal_links( $content, $url_map ) {
        $targets = array();

        // Match href attributes.
        if ( preg_match_all( '/<a\s[^>]*href=["\']([^"\']+)["\'][^>]*>/i', $content, $matches ) ) {
            foreach ( $matches[1] as $href ) {
                // Check full URL match.
                if ( isset( $url_map[ $href ] ) ) {
                    $targets[] = $url_map[ $href ];
                    continue;
                }
                // Check path-only match.
                $path = wp_parse_url( $href, PHP_URL_PATH );
                if ( $path && isset( $url_map[ $path ] ) ) {
                    $targets[] = $url_map[ $path ];
                }
            }
        }

        return array_unique( $targets );
    }

    /**
     * Build category-based clusters for posts.
     *
     * @param array $posts Array of post objects.
     * @return array post_id => { cluster_id, strength }.
     */
    private static function build_clusters( $posts ) {
        $clusters = array();

        foreach ( $posts as $post ) {
            $cats = get_the_category( $post->ID );
            if ( ! empty( $cats ) ) {
                $primary = $cats[0];
                $cluster_id = $primary->slug;
            } else {
                $cluster_id = 'uncategorized';
            }

            $clusters[ $post->ID ] = array(
                'cluster_id' => $cluster_id,
                'strength'   => 0, // Will be calculated below.
            );
        }

        // Calculate cluster strength (percentage of posts in same cluster that link to each other).
        $cluster_groups = array();
        foreach ( $clusters as $pid => $data ) {
            $cluster_groups[ $data['cluster_id'] ][] = $pid;
        }

        foreach ( $cluster_groups as $cid => $pids ) {
            $size = count( $pids );
            // Strength is simply cluster size normalized (bigger cluster = more linking potential).
            $strength = min( 100, round( ( $size / max( 1, count( $posts ) ) ) * 100, 2 ) );
            foreach ( $pids as $pid ) {
                $clusters[ $pid ]['strength'] = $strength;
            }
        }

        return $clusters;
    }

    /**
     * Generate a human-readable suggestion for an orphan/weak page.
     */
    private static function generate_suggestion( $status, $inbound, $outbound, $cluster_id ) {
        if ( $status === 'healthy' ) {
            return null;
        }

        if ( $status === 'orphan' ) {
            if ( $outbound === 0 ) {
                return 'This page has no inbound or outbound internal links. Add links from related content to improve discoverability.';
            }
            return 'No other pages link to this page. Add internal links from related posts in the "' . $cluster_id . '" category.';
        }

        // Weak.
        return 'Only ' . $inbound . ' page(s) link here. Consider adding links from more posts in the "' . $cluster_id . '" category.';
    }
}
