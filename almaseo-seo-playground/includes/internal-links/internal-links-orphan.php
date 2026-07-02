<?php
/**
 * Internal Links – Orphan Page Detection & Cluster Analysis
 *
 * Scans all published posts to find orphan pages (0 inbound internal links)
 * and weak pages (1-2 inbound links). Groups posts into category-based
 * clusters; cluster_strength is a rough size signal (cluster size as a % of
 * all posts), NOT a measure of actual interconnection density.
 *
 * @package AlmaSEO
 * @since   7.7.0
 */

// phpcs:disable PluginCheck.Security.DirectDB -- plugin's own custom tables; interpolated parts are $wpdb->prefix-derived names / built placeholder lists, not user input

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// This module queries the plugin's own custom tables / performs bulk reads that have
// no core API equivalent; results are request-scoped. The DirectDatabaseQuery
// DirectQuery/NoCaching warnings below are expected.
// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

class AlmaSEO_Internal_Links_Orphan {

    /**
     * Transient holding the in-progress scan graph (link map + accumulators)
     * between chunked AJAX steps. Holds the whole-site state mid-scan so each
     * request stays bounded; cleared when the scan completes.
     */
    const SCAN_STATE_KEY = 'almaseo_orphan_scan_state';
    const SCAN_STATE_TTL = 7200; // 2 hours — generous so a slow multi-step run can't expire mid-scan.

    /**
     * Get the orphan pages table name.
     */
    private static function table() {
        global $wpdb;
        return $wpdb->prefix . 'almaseo_orphan_pages';
    }

    /* ──────────────── Scanning ── */

    /**
     * Run a full orphan scan to completion in one call.
     *
     * Orphan detection is a WHOLE-GRAPH computation (a post is an orphan only
     * if NO other post links to it), so the inbound graph must be built from
     * every post's content before any verdict is known. To avoid a single
     * giant request that times out on large sites, the work is split into
     * three chunked phases — map → scan → write — each bounded to a batch and
     * resumable via {@see scan_step()}. This method just drives those steps to
     * completion in-process for cron / programmatic callers; the admin UI
     * calls scan_step() one batch per AJAX request instead.
     *
     * @return array { total: int, orphans: int, weak: int }
     */
    public static function scan_all() {
        $step  = self::scan_step( 'map', 0, 100 );
        $guard = 0;
        // Guard is a runaway backstop; real runs converge in ceil(posts/100)*3 steps.
        while ( empty( $step['done'] ) && $guard < 100000 ) {
            $step = self::scan_step( $step['phase'], $step['offset'], 100 );
            $guard++;
        }

        return ! empty( $step['counts'] )
            ? $step['counts']
            : array( 'total' => 0, 'orphans' => 0, 'weak' => 0 );
    }

    /**
     * Run ONE chunked step of the orphan scan and return the next cursor.
     *
     * Phases run in order, each advancing an offset through the frozen post
     * list a batch at a time:
     *   - 'map'   : freeze the candidate post list + build the URL→post_id map.
     *   - 'scan'  : read each source post's content, accumulate inbound/outbound
     *               link counts + the primary category for clustering.
     *   - 'write' : clear old rows (first batch only) + insert the verdicts.
     * The final 'write' batch stamps the last-scan time, clears the state
     * transient, and returns done=true with the verdict counts.
     *
     * @param string $phase      One of 'map' | 'scan' | 'write'.
     * @param int    $offset     Offset into the frozen post list for this phase.
     * @param int    $batch_size Posts to process this step.
     * @return array { phase, offset, total, processed, done, counts?, error? }
     */
    public static function scan_step( $phase = 'map', $offset = 0, $batch_size = 100 ) {
        $batch_size = max( 1, min( 500, (int) $batch_size ) );
        $offset     = max( 0, (int) $offset );

        if ( 'scan' === $phase ) {
            return self::step_scan( $offset, $batch_size );
        }
        if ( 'write' === $phase ) {
            return self::step_write( $offset, $batch_size );
        }
        return self::step_map( $offset, $batch_size );
    }

    /**
     * Phase 1 — build the URL→post_id map (and, on offset 0, freeze the
     * candidate post list that the whole scan operates on).
     */
    private static function step_map( $offset, $batch_size ) {
        if ( 0 === $offset ) {
            global $wpdb;
            $post_types   = apply_filters( 'almaseo_orphan_post_types', array( 'post', 'page' ) );
            $placeholders = implode( ',', array_fill( 0, count( $post_types ), '%s' ) );

            // Freeze the candidate post list up front so the set can't shift mid-scan.
            // $placeholders is a comma-separated list of %s tokens and $post_types supplies
            // the values, so the query IS prepared — the sniff just can't see the placeholders
            // behind the interpolation.
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare
            $ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts}
                 WHERE post_status = 'publish' AND post_type IN ({$placeholders})
                 ORDER BY ID ASC",
                $post_types
            ) );
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare

            $state = array(
                'ids'       => array_map( 'intval', (array) $ids ),
                'url_map'   => array(),
                'inbound'   => array(),
                'outbound'  => array(),
                'cats'      => array(),
                'clusters'  => array(),
                'dismissed' => array(), // post_ids the user dismissed — preserved across the re-scan (set at write offset 0).
                'counts'    => array( 'total' => 0, 'orphans' => 0, 'weak' => 0 ),
            );
        } else {
            $state = self::get_scan_state();
            if ( null === $state ) {
                return self::lost_state();
            }
        }

        $total = count( $state['ids'] );
        $slice = array_slice( $state['ids'], $offset, $batch_size );

        foreach ( $slice as $pid ) {
            $url = get_permalink( $pid );
            if ( $url ) {
                $state['url_map'][ $url ] = (int) $pid;
                // Also index a trailing-slash-insensitive path so links written
                // as /my-post still match a permalink stored as /my-post/.
                $path = wp_parse_url( $url, PHP_URL_PATH );
                if ( $path ) {
                    $state['url_map'][ self::normalize_path_key( $path ) ] = (int) $pid;
                }
            }
        }

        self::save_scan_state( $state );

        $next = $offset + count( $slice );
        $done = ( count( $slice ) < $batch_size ) || ( $next >= $total );

        return $done
            ? array( 'phase' => 'scan', 'offset' => 0, 'total' => $total, 'processed' => $total, 'done' => false )
            : array( 'phase' => 'map', 'offset' => $next, 'total' => $total, 'processed' => $next, 'done' => false );
    }

    /**
     * Phase 2 — scan each source post's content, accumulating inbound/outbound
     * link counts across the whole graph plus its primary-category cluster.
     */
    private static function step_scan( $offset, $batch_size ) {
        $state = self::get_scan_state();
        if ( null === $state ) {
            return self::lost_state();
        }

        $total = count( $state['ids'] );
        $slice = array_slice( $state['ids'], $offset, $batch_size );

        foreach ( $slice as $source_id ) {
            $source_id = (int) $source_id;

            // Record the primary category now (cheap, term-cached) so the write
            // phase can compute cluster strengths without re-querying per post.
            $cats = get_the_category( $source_id );
            $state['cats'][ $source_id ] = ( ! empty( $cats ) ) ? $cats[0]->slug : 'uncategorized';

            $content = get_post_field( 'post_content', $source_id );
            if ( empty( $content ) ) {
                $state['outbound'][ $source_id ] = 0;
                continue;
            }

            $links = self::extract_internal_links( $content, $state['url_map'] );
            $state['outbound'][ $source_id ] = count( $links );

            foreach ( $links as $target_id ) {
                $target_id = (int) $target_id;
                if ( $target_id !== $source_id ) {
                    $state['inbound'][ $target_id ] = ( isset( $state['inbound'][ $target_id ] ) ? $state['inbound'][ $target_id ] : 0 ) + 1;
                }
            }
        }

        self::save_scan_state( $state );

        $next = $offset + count( $slice );
        $done = ( count( $slice ) < $batch_size ) || ( $next >= $total );

        return $done
            ? array( 'phase' => 'write', 'offset' => 0, 'total' => $total, 'processed' => $total, 'done' => false )
            : array( 'phase' => 'scan', 'offset' => $next, 'total' => $total, 'processed' => $next, 'done' => false );
    }

    /**
     * Phase 3 — write the verdict rows. On the first batch it clears the old
     * results and computes cluster strengths from the categories collected
     * during the scan phase. The final batch stamps the last-scan time and
     * clears the scan state.
     */
    private static function step_write( $offset, $batch_size ) {
        global $wpdb;

        $state = self::get_scan_state();
        if ( null === $state ) {
            return self::lost_state();
        }

        $table = self::table();
        $total = count( $state['ids'] );
        $now   = current_time( 'mysql', true );

        if ( 0 === $offset ) {
            // Preserve findings the user explicitly dismissed so a re-scan
            // doesn't resurrect them. Capture them BEFORE clearing the table.
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
            $dismissed = $wpdb->get_col( "SELECT post_id FROM {$table} WHERE status = 'dismissed'" );
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $state['dismissed'] = array_map( 'intval', (array) $dismissed );

            // Clear prior results now that fresh data is ready. DELETE (not
            // TRUNCATE) so the scan works on locked-down hosts without DROP priv.
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
            $wpdb->query( "DELETE FROM {$table}" );
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
            $state['clusters'] = self::build_clusters_from_cats( $state['cats'], $total );
            $state['counts']   = array( 'total' => 0, 'orphans' => 0, 'weak' => 0 );
        }

        $dismissed = isset( $state['dismissed'] ) ? $state['dismissed'] : array();

        $slice = array_slice( $state['ids'], $offset, $batch_size );

        foreach ( $slice as $pid ) {
            $pid      = (int) $pid;
            $inbound  = isset( $state['inbound'][ $pid ] ) ? (int) $state['inbound'][ $pid ] : 0;
            $outbound = isset( $state['outbound'][ $pid ] ) ? (int) $state['outbound'][ $pid ] : 0;

            if ( 0 === $inbound ) {
                $status = 'orphan';
            } elseif ( $inbound <= 2 ) {
                $status = 'weak';
            } else {
                $status = 'healthy';
            }

            // Keep user-dismissed findings dismissed, and don't let them
            // re-inflate the orphan/weak counts.
            if ( in_array( $pid, $dismissed, true ) ) {
                $status = 'dismissed';
            } elseif ( 'orphan' === $status ) {
                $state['counts']['orphans']++;
            } elseif ( 'weak' === $status ) {
                $state['counts']['weak']++;
            }

            $cluster_id       = isset( $state['clusters'][ $pid ] ) ? $state['clusters'][ $pid ]['cluster_id'] : '';
            $cluster_strength = isset( $state['clusters'][ $pid ] ) ? $state['clusters'][ $pid ]['strength'] : 0;

            $wpdb->insert( $table, array(
                'post_id'          => $pid,
                'inbound_count'    => $inbound,
                'outbound_count'   => $outbound,
                'cluster_id'       => $cluster_id,
                'cluster_strength' => $cluster_strength,
                'is_hub_candidate' => ( $outbound >= 5 && $inbound <= 2 ) ? 1 : 0,
                'status'           => $status,
                'scanned_at'       => $now,
                'suggestion'       => self::generate_suggestion( $status, $inbound, $outbound, $cluster_id ),
            ) );

            $state['counts']['total']++;
        }

        $next   = $offset + count( $slice );
        $done   = ( count( $slice ) < $batch_size ) || ( $next >= $total );
        $counts = $state['counts'];

        if ( $done ) {
            update_option( 'almaseo_orphan_last_scan', $now );
            self::clear_scan_state();
            return array( 'phase' => 'done', 'offset' => $total, 'total' => $total, 'processed' => $total, 'done' => true, 'counts' => $counts );
        }

        self::save_scan_state( $state );
        return array( 'phase' => 'write', 'offset' => $next, 'total' => $total, 'processed' => $next, 'done' => false, 'counts' => $counts );
    }

    /* ──────────────── Scan state (transient) ── */

    private static function get_scan_state() {
        $state = get_transient( self::SCAN_STATE_KEY );
        return is_array( $state ) ? $state : null;
    }

    private static function save_scan_state( $state ) {
        set_transient( self::SCAN_STATE_KEY, $state, self::SCAN_STATE_TTL );
    }

    private static function clear_scan_state() {
        delete_transient( self::SCAN_STATE_KEY );
    }

    /**
     * The scan-state transient vanished mid-run (expired, or object cache was
     * flushed). Signal the caller to surface the failure and restart, rather
     * than writing a partial/empty graph.
     */
    private static function lost_state() {
        return array( 'phase' => 'done', 'offset' => 0, 'total' => 0, 'processed' => 0, 'done' => true, 'error' => 'scan_state_lost' );
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

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name derived from $wpdb->prefix, not user input
        $rows = $wpdb->get_results(
            "SELECT status, COUNT(*) AS cnt FROM {$table} GROUP BY status"
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

        foreach ( $rows as $r ) {
            $cnt             = (int) $r->cnt;
            $stats['total'] += $cnt;

            // The stored status is 'orphan' (singular); the stat card key is
            // 'orphans' (plural). Map it so the Orphans count isn't always 0.
            $key = ( 'orphan' === $r->status ) ? 'orphans' : $r->status;
            if ( isset( $stats[ $key ] ) ) {
                $stats[ $key ] = $cnt;
            }
        }

        // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name derived from $wpdb->prefix, not user input
        $stats['hub_candidates'] = (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM {$table} WHERE is_hub_candidate = 1 AND status != 'dismissed'"
        );
        // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

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
        return $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM " . self::table() . " WHERE id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name derived from $wpdb->prefix, not user input
            $id
        ) );
    }

    /**
     * Get distinct cluster IDs for filter dropdown.
     */
    public static function get_clusters() {
        global $wpdb;
        return $wpdb->get_col(
            "SELECT DISTINCT cluster_id FROM " . self::table() . " WHERE cluster_id != '' ORDER BY cluster_id ASC" // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- table name derived from $wpdb->prefix, not user input
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
            // phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name derived from $wpdb->prefix, not user input
            $existing = $wpdb->get_row( $wpdb->prepare(
                "SELECT id, status FROM {$table} WHERE post_id = %d",
                $post_id
            ) );
            // phpcs:enable WordPress.DB.PreparedSQL.InterpolatedNotPrepared

            // A dashboard re-push must not resurrect a finding the user
            // dismissed — keep it dismissed while still refreshing its counts.
            if ( $existing && 'dismissed' === $existing->status ) {
                $status = 'dismissed';
            }

            $data = array(
                'post_id'        => $post_id,
                'inbound_count'  => $inbound,
                'outbound_count' => $outbound,
                'status'         => $status,
                'scanned_at'     => current_time( 'mysql', true ),
                'suggestion'     => isset( $item['suggestion'] ) ? sanitize_text_field( $item['suggestion'] ) : null,
            );

            if ( $existing ) {
                $wpdb->update( $table, $data, array( 'id' => $existing->id ) );
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
                // Check path-only match (trailing-slash insensitive).
                $path = wp_parse_url( $href, PHP_URL_PATH );
                if ( $path ) {
                    $key = self::normalize_path_key( $path );
                    if ( isset( $url_map[ $key ] ) ) {
                        $targets[] = $url_map[ $key ];
                    }
                }
            }
        }

        return array_unique( $targets );
    }

    /**
     * Normalize a URL path for matching: strip the trailing slash, except for
     * the site root which stays '/'.
     *
     * @param string $path URL path.
     * @return string
     */
    private static function normalize_path_key( $path ) {
        if ( ! $path ) {
            return '';
        }
        return ( '/' === $path ) ? '/' : untrailingslashit( $path );
    }

    /**
     * Build category-based clusters from the per-post primary categories that
     * were collected during the scan phase.
     *
     * Strength is the cluster's size normalized against the whole site (bigger
     * cluster = more linking potential) — same heuristic as before, just fed
     * from the pre-collected map so the write phase needs no per-post category
     * lookups.
     *
     * @param array $cats        post_id => cluster_id (primary category slug).
     * @param int   $total_posts Total posts in the scan.
     * @return array post_id => { cluster_id, strength }.
     */
    private static function build_clusters_from_cats( $cats, $total_posts ) {
        $clusters = array();
        $groups   = array();

        foreach ( $cats as $pid => $cluster_id ) {
            $clusters[ $pid ] = array( 'cluster_id' => $cluster_id, 'strength' => 0 );
            $groups[ $cluster_id ][] = $pid;
        }

        foreach ( $groups as $cid => $pids ) {
            $size     = count( $pids );
            $strength = min( 100, round( ( $size / max( 1, $total_posts ) ) * 100, 2 ) );
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
// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
