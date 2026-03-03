<?php
/**
 * 404 Intelligence – Smart Redirect Suggestions & Spike Detection
 *
 * Provides URL-similarity-based redirect suggestions for 404 paths
 * and detects traffic spikes (sudden surges of 404 errors).
 *
 * @package AlmaSEO
 * @since   7.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_404_Intelligence {

    /**
     * Get smart redirect suggestions for a 404 path.
     *
     * Uses slug similarity, keyword matching, and category heuristics
     * to suggest the best redirect target.
     *
     * @param string $path The 404 path (e.g. /old-post-slug).
     * @param int    $limit Max suggestions to return.
     * @return array Array of suggestions [ { post_id, url, title, score, reason } ].
     */
    public static function get_suggestions( $path, $limit = 5 ) {
        $candidates = array();

        // Extract slug keywords from the path.
        $slug = self::extract_slug( $path );
        $keywords = self::slug_to_keywords( $slug );

        if ( empty( $keywords ) ) {
            return array();
        }

        // Method 1: Slug similarity against published post slugs.
        $slug_matches = self::match_by_slug( $slug, $keywords );
        foreach ( $slug_matches as $m ) {
            $key = 'post_' . $m['post_id'];
            if ( ! isset( $candidates[ $key ] ) || $candidates[ $key ]['score'] < $m['score'] ) {
                $candidates[ $key ] = $m;
            }
        }

        // Method 2: Title keyword matching.
        $title_matches = self::match_by_title( $keywords );
        foreach ( $title_matches as $m ) {
            $key = 'post_' . $m['post_id'];
            if ( ! isset( $candidates[ $key ] ) ) {
                $candidates[ $key ] = $m;
            } elseif ( $candidates[ $key ]['score'] < $m['score'] ) {
                $candidates[ $key ]['score']  = $m['score'];
                $candidates[ $key ]['reason'] = $m['reason'];
            }
        }

        // Sort by score descending.
        usort( $candidates, function ( $a, $b ) {
            return $b['score'] <=> $a['score'];
        } );

        return array_slice( $candidates, 0, $limit );
    }

    /**
     * Detect 404 spikes: paths where 24h hits > 3x the 7-day daily average.
     *
     * @return array Array of spike records.
     */
    public static function detect_spikes() {
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_404_log';

        $now    = current_time( 'mysql' );
        $day1   = gmdate( 'Y-m-d H:i:s', strtotime( '-1 day', strtotime( $now ) ) );
        $day7   = gmdate( 'Y-m-d H:i:s', strtotime( '-7 days', strtotime( $now ) ) );

        // Get paths with 24h activity.
        $recent = $wpdb->get_results( $wpdb->prepare(
            "SELECT path, SUM(hits) AS hits_24h
             FROM {$table}
             WHERE last_seen >= %s AND is_ignored = 0
             GROUP BY path
             HAVING hits_24h >= 3",
            $day1
        ) );

        if ( empty( $recent ) ) {
            return array();
        }

        $spikes = array();

        foreach ( $recent as $row ) {
            // Get 7-day total for this path.
            $total_7d = (int) $wpdb->get_var( $wpdb->prepare(
                "SELECT SUM(hits) FROM {$table} WHERE path = %s AND last_seen >= %s AND is_ignored = 0",
                $row->path, $day7
            ) );

            $daily_avg = $total_7d / 7;

            if ( $daily_avg > 0 && (int) $row->hits_24h > ( 3 * $daily_avg ) ) {
                $spikes[] = array(
                    'path'      => $row->path,
                    'hits_24h'  => (int) $row->hits_24h,
                    'daily_avg' => round( $daily_avg, 1 ),
                    'spike_ratio' => round( (int) $row->hits_24h / $daily_avg, 1 ),
                );
            }
        }

        // Sort by spike ratio descending.
        usort( $spikes, function ( $a, $b ) {
            return $b['spike_ratio'] <=> $a['spike_ratio'];
        } );

        return array_slice( $spikes, 0, 20 );
    }

    /**
     * Store dashboard-pushed impact data for a 404 entry.
     *
     * @param int   $id   Log entry ID.
     * @param array $data Impact data: impact_score, impressions, clicks, suggested_target.
     * @return bool
     */
    public static function update_impact_data( $id, $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_404_log';

        $update = array();
        $format = array();

        if ( isset( $data['impact_score'] ) ) {
            $update['impact_score'] = floatval( $data['impact_score'] );
            $format[] = '%f';
        }
        if ( isset( $data['impressions'] ) ) {
            $update['impressions'] = absint( $data['impressions'] );
            $format[] = '%d';
        }
        if ( isset( $data['clicks'] ) ) {
            $update['clicks'] = absint( $data['clicks'] );
            $format[] = '%d';
        }
        if ( isset( $data['suggested_target'] ) ) {
            $update['suggested_target'] = esc_url_raw( $data['suggested_target'] );
            $format[] = '%s';
        }

        if ( empty( $update ) ) {
            return false;
        }

        return (bool) $wpdb->update( $table, $update, array( 'id' => absint( $id ) ), $format, array( '%d' ) );
    }

    /**
     * Bulk update impact data from dashboard push.
     *
     * @param array $items Array of { path, impact_score, impressions, clicks, suggested_target }.
     * @return array { updated: int, skipped: int }.
     */
    public static function process_impact_push( $items ) {
        global $wpdb;
        $table   = $wpdb->prefix . 'almaseo_404_log';
        $counts  = array( 'updated' => 0, 'skipped' => 0 );

        foreach ( $items as $item ) {
            if ( empty( $item['path'] ) ) {
                $counts['skipped']++;
                continue;
            }

            $path = sanitize_text_field( $item['path'] );

            // Find the log entry by path.
            $row = $wpdb->get_row( $wpdb->prepare(
                "SELECT id FROM {$table} WHERE path = %s ORDER BY last_seen DESC LIMIT 1",
                $path
            ) );

            if ( ! $row ) {
                $counts['skipped']++;
                continue;
            }

            if ( self::update_impact_data( $row->id, $item ) ) {
                // Also flag as spike if provided.
                if ( ! empty( $item['spike_flag'] ) ) {
                    $wpdb->update(
                        $table,
                        array( 'spike_flag' => 1 ),
                        array( 'id' => $row->id ),
                        array( '%d' ),
                        array( '%d' )
                    );
                }
                $counts['updated']++;
            } else {
                $counts['skipped']++;
            }
        }

        return $counts;
    }

    /* ── Internal Helpers ── */

    /**
     * Extract the final slug segment from a URL path.
     */
    private static function extract_slug( $path ) {
        $path = trim( $path, '/' );
        $segments = explode( '/', $path );
        $slug = end( $segments );
        // Remove file extension if present.
        $slug = preg_replace( '/\.[a-z0-9]+$/i', '', $slug );
        // Remove query params.
        $slug = strtok( $slug, '?' );
        return $slug;
    }

    /**
     * Split a slug into individual keywords.
     */
    private static function slug_to_keywords( $slug ) {
        $slug = str_replace( array( '-', '_', '+', '%20' ), ' ', $slug );
        $words = preg_split( '/\s+/', strtolower( $slug ) );
        // Filter out very short words and common noise.
        $stop = array( 'the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'had', 'her', 'was', 'one', 'our', 'out', 'has', 'from', 'with' );
        $words = array_filter( $words, function ( $w ) use ( $stop ) {
            return strlen( $w ) > 2 && ! in_array( $w, $stop, true );
        } );
        return array_values( $words );
    }

    /**
     * Match by slug similarity against published post slugs.
     */
    private static function match_by_slug( $slug, $keywords ) {
        global $wpdb;
        $matches = array();

        // Build LIKE conditions for each keyword.
        $like_clauses = array();
        $vals = array();
        foreach ( $keywords as $kw ) {
            $like_clauses[] = 'post_name LIKE %s';
            $vals[] = '%' . $wpdb->esc_like( $kw ) . '%';
        }

        if ( empty( $like_clauses ) ) {
            return array();
        }

        $where = implode( ' OR ', $like_clauses );

        $posts = $wpdb->get_results( $wpdb->prepare(
            "SELECT ID, post_title, post_name FROM {$wpdb->posts}
             WHERE post_status = 'publish' AND post_type IN ('post', 'page') AND ({$where})
             LIMIT 20",
            $vals
        ) );

        foreach ( $posts as $post ) {
            $pct = 0;
            similar_text( strtolower( $slug ), strtolower( $post->post_name ), $pct );
            if ( $pct >= 40 ) {
                $matches[] = array(
                    'post_id' => (int) $post->ID,
                    'url'     => get_permalink( $post->ID ),
                    'title'   => $post->post_title,
                    'score'   => round( $pct, 1 ),
                    'reason'  => sprintf( 'Slug similarity: %d%%', round( $pct ) ),
                );
            }
        }

        return $matches;
    }

    /**
     * Match by title keyword overlap.
     */
    private static function match_by_title( $keywords ) {
        global $wpdb;
        $matches = array();

        $like_clauses = array();
        $vals = array();
        foreach ( $keywords as $kw ) {
            $like_clauses[] = 'post_title LIKE %s';
            $vals[] = '%' . $wpdb->esc_like( $kw ) . '%';
        }

        if ( empty( $like_clauses ) ) {
            return array();
        }

        $where = implode( ' OR ', $like_clauses );

        $posts = $wpdb->get_results( $wpdb->prepare(
            "SELECT ID, post_title, post_name FROM {$wpdb->posts}
             WHERE post_status = 'publish' AND post_type IN ('post', 'page') AND ({$where})
             LIMIT 20",
            $vals
        ) );

        foreach ( $posts as $post ) {
            // Count how many keywords appear in the title.
            $title_lower = strtolower( $post->post_title );
            $matched_count = 0;
            foreach ( $keywords as $kw ) {
                if ( strpos( $title_lower, $kw ) !== false ) {
                    $matched_count++;
                }
            }

            $score = count( $keywords ) > 0 ? ( $matched_count / count( $keywords ) ) * 100 : 0;
            if ( $score >= 30 ) {
                $matches[] = array(
                    'post_id' => (int) $post->ID,
                    'url'     => get_permalink( $post->ID ),
                    'title'   => $post->post_title,
                    'score'   => round( $score, 1 ),
                    'reason'  => sprintf( 'Title keyword match: %d/%d keywords', $matched_count, count( $keywords ) ),
                );
            }
        }

        return $matches;
    }
}
