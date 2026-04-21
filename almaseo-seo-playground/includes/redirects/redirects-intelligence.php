<?php
/**
 * Redirects Intelligence – Chain Detection & Impact Tracking
 *
 * Detects redirect chains (A→B→C) and stores traffic recovery data
 * pushed from the AlmaSEO dashboard.
 *
 * @package AlmaSEO
 * @since   7.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Redirects_Intelligence {

    /**
     * Detect redirect chains.
     *
     * A chain exists when the target of redirect A matches the source
     * of redirect B (A→B→C). These should be consolidated to A→C.
     *
     * @return array Array of chain reports.
     */
    public static function detect_chains() {
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_redirects';

        // Get all enabled redirects.
        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $redirects = $wpdb->get_results(
            "SELECT id, source, target, status, hits FROM {$table} WHERE is_enabled = 1",
            ARRAY_A
        );

        if ( empty( $redirects ) ) {
            return array();
        }

        // Index by source for quick lookup.
        $by_source = array();
        foreach ( $redirects as $r ) {
            $by_source[ $r['source'] ] = $r;
        }

        $chains = array();

        foreach ( $redirects as $r ) {
            // Normalize the target to a path for comparison.
            $target_path = self::url_to_path( $r['target'] );
            if ( ! $target_path ) {
                continue;
            }

            // Check if target matches another redirect's source.
            if ( isset( $by_source[ $target_path ] ) ) {
                $next = $by_source[ $target_path ];

                // Follow the chain to find the final destination.
                $chain = array(
                    array( 'id' => (int) $r['id'], 'source' => $r['source'], 'target' => $r['target'], 'status' => (int) $r['status'] ),
                    array( 'id' => (int) $next['id'], 'source' => $next['source'], 'target' => $next['target'], 'status' => (int) $next['status'] ),
                );

                $final_target = $next['target'];
                $visited = array( $r['source'], $next['source'] );
                $depth = 2;

                // Follow further links (avoid infinite loops).
                while ( $depth < 10 ) {
                    $next_path = self::url_to_path( $final_target );
                    if ( ! $next_path || in_array( $next_path, $visited, true ) ) {
                        break;
                    }
                    if ( ! isset( $by_source[ $next_path ] ) ) {
                        break;
                    }

                    $visited[] = $next_path;
                    $link = $by_source[ $next_path ];
                    $chain[] = array( 'id' => (int) $link['id'], 'source' => $link['source'], 'target' => $link['target'], 'status' => (int) $link['status'] );
                    $final_target = $link['target'];
                    $depth++;
                }

                $chains[] = array(
                    'chain_length'   => count( $chain ),
                    'hops'           => $chain,
                    'first_source'   => $r['source'],
                    'final_target'   => $final_target,
                    'suggested_fix'  => sprintf(
                        'Consolidate %s → %s (currently %d hops)',
                        $r['source'],
                        $final_target,
                        count( $chain )
                    ),
                );
            }
        }

        // Deduplicate: only keep chains starting from the earliest hop.
        $seen_sources = array();
        $unique_chains = array();
        foreach ( $chains as $c ) {
            if ( in_array( $c['first_source'], $seen_sources, true ) ) {
                continue;
            }
            $seen_sources[] = $c['first_source'];
            // Also mark intermediate sources as seen.
            foreach ( $c['hops'] as $hop ) {
                $seen_sources[] = $hop['source'];
            }
            $unique_chains[] = $c;
        }

        return $unique_chains;
    }

    /**
     * Store dashboard-pushed traffic recovery data for a redirect.
     *
     * @param int   $id   Redirect ID.
     * @param array $data { traffic_before: int, traffic_after: int }.
     * @return bool
     */
    public static function update_traffic_data( $id, $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_redirects';

        $update = array();
        $format = array();

        if ( isset( $data['traffic_before'] ) ) {
            $update['traffic_before'] = absint( $data['traffic_before'] );
            $format[] = '%d';
        }
        if ( isset( $data['traffic_after'] ) ) {
            $update['traffic_after'] = absint( $data['traffic_after'] );
            $format[] = '%d';
        }

        // Calculate recovery percentage if both values present.
        $traffic_before = isset( $update['traffic_before'] ) ? $update['traffic_before'] : null;
        $traffic_after  = isset( $update['traffic_after'] ) ? $update['traffic_after'] : null;

        if ( $traffic_before === null || $traffic_after === null ) {
            // Read existing values.
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
            $row = $wpdb->get_row( $wpdb->prepare(
                "SELECT traffic_before, traffic_after FROM {$table} WHERE id = %d",
                $id
            ) );
            if ( $row ) {
                if ( $traffic_before === null ) $traffic_before = (int) $row->traffic_before;
                if ( $traffic_after === null )  $traffic_after  = (int) $row->traffic_after;
            }
        }

        if ( $traffic_before > 0 && $traffic_after !== null ) {
            $update['recovery_pct'] = round( ( $traffic_after / $traffic_before ) * 100, 2 );
            $format[] = '%f';
        }

        if ( empty( $update ) ) {
            return false;
        }

        return (bool) $wpdb->update( $table, $update, array( 'id' => absint( $id ) ), $format, array( '%d' ) );
    }

    /**
     * Bulk update traffic data from dashboard push.
     *
     * @param array $items Array of { source, traffic_before, traffic_after }.
     * @return array { updated: int, skipped: int }.
     */
    public static function process_traffic_push( $items ) {
        global $wpdb;
        $table  = $wpdb->prefix . 'almaseo_redirects';
        $counts = array( 'updated' => 0, 'skipped' => 0 );

        foreach ( $items as $item ) {
            if ( empty( $item['source'] ) ) {
                $counts['skipped']++;
                continue;
            }

            $source = AlmaSEO_Redirects_Model::normalize_path( $item['source'] );
            if ( ! $source ) {
                $counts['skipped']++;
                continue;
            }

            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
            $row = $wpdb->get_row( $wpdb->prepare(
                "SELECT id FROM {$table} WHERE source = %s LIMIT 1",
                $source
            ) );

            if ( ! $row ) {
                $counts['skipped']++;
                continue;
            }

            if ( self::update_traffic_data( $row->id, $item ) ) {
                $counts['updated']++;
            } else {
                $counts['skipped']++;
            }
        }

        return $counts;
    }

    /**
     * Get redirects with traffic recovery data for reporting.
     *
     * @param int $limit Max results.
     * @return array
     */
    public static function get_recovery_report( $limit = 20 ) {
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_redirects';

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        return $wpdb->get_results( $wpdb->prepare(
            "SELECT id, source, target, status, hits, traffic_before, traffic_after, recovery_pct
             FROM {$table}
             WHERE is_enabled = 1 AND traffic_before > 0
             ORDER BY recovery_pct ASC
             LIMIT %d",
            $limit
        ), ARRAY_A );
    }

    /* ── Internal Helpers ── */

    /**
     * Convert a full URL or path to a normalized path.
     */
    private static function url_to_path( $url ) {
        // If it's already a path starting with /.
        if ( substr( $url, 0, 1 ) === '/' ) {
            return rtrim( $url, '/' ) ?: '/';
        }

        // Parse full URL.
        $parsed = wp_parse_url( $url );
        if ( isset( $parsed['path'] ) ) {
            $path = $parsed['path'];
            return rtrim( $path, '/' ) ?: '/';
        }

        return null;
    }
}
