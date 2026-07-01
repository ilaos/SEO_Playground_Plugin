<?php
/**
 * Date Hygiene Scanner – Scanning Engine
 *
 * Core detection logic for finding stale years, dated phrases,
 * superlative year references, price references, and regulation mentions.
 *
 * @package AlmaSEO
 * @since   7.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Date_Hygiene_Engine {

    /**
     * Default scan settings.
     */
    const DEFAULT_SETTINGS = array(
        'stale_threshold'    => 2,
        'scan_prices'        => true,
        'scan_regulations'   => true,
        'scan_post_types'    => array( 'post', 'page', 'product' ),
    );

    /* ──────────────────────── Settings ── */

    /**
     * Get scan settings.
     *
     * @return array
     */
    public static function get_settings() {
        $saved = get_option( 'almaseo_dh_settings', array() );
        return wp_parse_args( $saved, self::DEFAULT_SETTINGS );
    }

    /**
     * Get the stale-year cutoff.
     *
     * Years at or below this value are flagged as stale.
     * E.g. in 2026 with threshold=2, cutoff = 2024.
     *
     * @return int
     */
    public static function get_stale_year_cutoff() {
        $settings  = self::get_settings();
        $threshold = max( 1, (int) $settings['stale_threshold'] );
        return (int) gmdate( 'Y' ) - $threshold;
    }

    /* ──────────────────────── Single Post Scan ── */

    /**
     * Scan a single post for date-hygiene findings.
     *
     * @param int $post_id
     * @return array Array of finding arrays ready for model insert.
     */
    public static function scan_post( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return array();
        }

        $settings = self::get_settings();
        $findings = array();

        $title = $post->post_title;
        $body  = wp_strip_all_tags( $post->post_content );

        // Scan title.
        $findings = array_merge( $findings, self::detect_stale_years( $title, 'title', $post_id ) );
        $findings = array_merge( $findings, self::detect_dated_phrases( $title, 'title', $post_id ) );
        $findings = array_merge( $findings, self::detect_superlative_years( $title, 'title', $post_id ) );

        if ( $settings['scan_prices'] ) {
            $findings = array_merge( $findings, self::detect_price_references( $title, 'title', $post_id ) );
        }

        if ( $settings['scan_regulations'] ) {
            $findings = array_merge( $findings, self::detect_regulation_mentions( $title, 'title', $post_id ) );
        }

        // Scan body.
        $findings = array_merge( $findings, self::detect_stale_years( $body, 'body', $post_id ) );
        $findings = array_merge( $findings, self::detect_dated_phrases( $body, 'body', $post_id ) );
        $findings = array_merge( $findings, self::detect_superlative_years( $body, 'body', $post_id ) );

        if ( $settings['scan_prices'] ) {
            $findings = array_merge( $findings, self::detect_price_references( $body, 'body', $post_id ) );
        }

        if ( $settings['scan_regulations'] ) {
            $findings = array_merge( $findings, self::detect_regulation_mentions( $body, 'body', $post_id ) );
        }

        // Deduplicate: same detected_value + location should only appear once.
        $seen    = array();
        $unique  = array();
        foreach ( $findings as $f ) {
            $key = $f['finding_type'] . '|' . $f['detected_value'] . '|' . $f['location'];
            if ( ! isset( $seen[ $key ] ) ) {
                $seen[ $key ] = true;
                $unique[]     = $f;
            }
        }

        return $unique;
    }

    /* ──────────────────────── Detector: Stale Years ── */

    /**
     * Detect standalone stale year references.
     *
     * @param string $text    Text to scan.
     * @param string $location 'title' or 'body'.
     * @param int    $post_id Post ID for the finding.
     * @return array
     */
    public static function detect_stale_years( $text, $location, $post_id ) {
        $cutoff   = self::get_stale_year_cutoff();
        $findings = array();

        if ( ! preg_match_all( '/\b((?:19|20)\d{2})\b/', $text, $matches, PREG_OFFSET_CAPTURE ) ) {
            return $findings;
        }

        foreach ( $matches[1] as $match ) {
            $year   = (int) $match[0];
            $offset = (int) $match[1];

            if ( $year > $cutoff || $year < 1990 ) {
                continue;
            }

            $context  = self::extract_context( $text, $offset, strlen( $match[0] ) );
            $severity = ( $location === 'title' ) ? 'high' : 'medium';

            $findings[] = array(
                'post_id'        => $post_id,
                'finding_type'   => 'stale_year',
                'severity'       => $severity,
                'detected_value' => (string) $year,
                'context_snippet' => $context,
                'suggestion'     => "Consider updating '{$year}' to the current year, or use evergreen phrasing like 'recently' or 'currently'.",
                'location'       => $location,
            );
        }

        return $findings;
    }

    /* ──────────────────────── Detector: Dated Phrases ── */

    /**
     * Detect explicit freshness claims with old dates.
     *
     * Matches: "as of January 2022", "updated March 2023", "last reviewed 2022", etc.
     *
     * @param string $text
     * @param string $location
     * @param int    $post_id
     * @return array
     */
    public static function detect_dated_phrases( $text, $location, $post_id ) {
        $cutoff   = self::get_stale_year_cutoff();
        $findings = array();

        $pattern = '/\b(as of|since|starting|effective|updated?|current as of|published|written in|last (?:updated|reviewed|checked))\s+(?:\w+\s+)?(?:(?:jan|feb|mar|apr|may|jun|jul|aug|sep|oct|nov|dec)\w*\s+)?((?:19|20)\d{2})\b/i';

        if ( ! preg_match_all( $pattern, $text, $matches, PREG_OFFSET_CAPTURE ) ) {
            return $findings;
        }

        foreach ( $matches[0] as $i => $full_match ) {
            $year   = (int) $matches[2][ $i ][0];
            $offset = (int) $full_match[1];
            $phrase = $full_match[0];

            if ( $year > $cutoff ) {
                continue;
            }

            $context = self::extract_context( $text, $offset, strlen( $phrase ) );

            $findings[] = array(
                'post_id'        => $post_id,
                'finding_type'   => 'dated_phrase',
                'severity'       => 'high',
                'detected_value' => $phrase,
                'context_snippet' => $context,
                'suggestion'     => 'This phrase makes an explicit freshness claim. Update the date or remove the time reference.',
                'location'       => $location,
            );
        }

        return $findings;
    }

    /* ──────────────────────── Detector: Superlative Years ── */

    /**
     * Detect listicle-style year references.
     *
     * Matches: "Best SEO Tools for 2023", "Top 10 Picks of 2022", etc.
     *
     * @param string $text
     * @param string $location
     * @param int    $post_id
     * @return array
     */
    public static function detect_superlative_years( $text, $location, $post_id ) {
        $cutoff   = self::get_stale_year_cutoff();
        $findings = array();

        $pattern = '/\b(best|top|guide|review|comparison|picks|rated|recommended|list)\s+(?:(?:\d+\s+)?(?:of|for|in)\s+)(20\d{2})\b/i';

        if ( ! preg_match_all( $pattern, $text, $matches, PREG_OFFSET_CAPTURE ) ) {
            return $findings;
        }

        foreach ( $matches[0] as $i => $full_match ) {
            $year   = (int) $matches[2][ $i ][0];
            $offset = (int) $full_match[1];
            $phrase = $full_match[0];

            if ( $year > $cutoff ) {
                continue;
            }

            $context  = self::extract_context( $text, $offset, strlen( $phrase ) );
            $severity = ( $location === 'title' ) ? 'high' : 'medium';

            $findings[] = array(
                'post_id'        => $post_id,
                'finding_type'   => 'superlative_year',
                'severity'       => $severity,
                'detected_value' => $phrase,
                'context_snippet' => $context,
                'suggestion'     => 'Listicle-style year reference. Update to the current year or remove it for an evergreen title.',
                'location'       => $location,
            );
        }

        return $findings;
    }

    /* ──────────────────────── Detector: Price References ── */

    /**
     * Detect hardcoded price references.
     *
     * @param string $text
     * @param string $location
     * @param int    $post_id
     * @return array
     */
    public static function detect_price_references( $text, $location, $post_id ) {
        $findings = array();

        $pattern = '/(?:\$|€|£|USD\s?|EUR\s?|GBP\s?)[\d,]+(?:\.\d{2})?(?:\s*[-–—]\s*(?:\$|€|£|USD\s?|EUR\s?|GBP\s?)?[\d,]+(?:\.\d{2})?)?/';

        if ( ! preg_match_all( $pattern, $text, $matches, PREG_OFFSET_CAPTURE ) ) {
            return $findings;
        }

        foreach ( $matches[0] as $match ) {
            $value  = $match[0];
            $offset = (int) $match[1];

            $context = self::extract_context( $text, $offset, strlen( $value ) );

            $findings[] = array(
                'post_id'        => $post_id,
                'finding_type'   => 'price_reference',
                'severity'       => 'medium',
                'detected_value' => $value,
                'context_snippet' => $context,
                'suggestion'     => 'Verify this price is still accurate. Consider linking to an official pricing page instead of hardcoding values.',
                'location'       => $location,
            );
        }

        return $findings;
    }

    /* ──────────────────────── Detector: Regulation Mentions ── */

    /**
     * Detect regulation names paired with year references.
     *
     * Only flags when a known regulation name appears near a year.
     *
     * @param string $text
     * @param string $location
     * @param int    $post_id
     * @return array
     */
    public static function detect_regulation_mentions( $text, $location, $post_id ) {
        $findings = array();

        $regulations = array(
            'GDPR', 'CCPA', 'CPRA', 'HIPAA', 'PCI DSS', 'PCI-DSS',
            'SOX', 'ADA', 'FERPA', 'COPPA', 'SOC 2', 'SOC2',
            'ISO 27001', 'NIST', 'FISMA', 'GLBA',
        );

        $reg_pattern = implode( '|', array_map( 'preg_quote', $regulations ) );

        // Match regulation name followed by a year within ~80 chars.
        $pattern = '/\b(' . $reg_pattern . ')\b.{0,80}?\b((?:19|20)\d{2})\b/i';

        if ( ! preg_match_all( $pattern, $text, $matches, PREG_OFFSET_CAPTURE ) ) {
            return $findings;
        }

        foreach ( $matches[0] as $i => $full_match ) {
            $regulation = $matches[1][ $i ][0];
            $year       = $matches[2][ $i ][0];
            $offset     = (int) $full_match[1];
            $phrase     = $regulation . ' (' . $year . ')';

            $context = self::extract_context( $text, $offset, strlen( $full_match[0] ) );

            $findings[] = array(
                'post_id'        => $post_id,
                'finding_type'   => 'regulation_mention',
                'severity'       => 'low',
                'detected_value' => $phrase,
                'context_snippet' => $context,
                'suggestion'     => 'Regulation reference paired with a year. Verify the referenced version is still current.',
                'location'       => $location,
            );
        }

        return $findings;
    }

    /* ──────────────────────── Full Site Scan ── */

    /**
     * Scan all published posts.
     *
     * Clears open findings first (preserving resolved/dismissed),
     * then processes in batches of 50.
     *
     * @return array { posts_scanned: int, findings_count: int }
     */
    /**
     * Configured post types to scan (with defaults).
     *
     * @return string[]
     */
    private static function scan_post_types() {
        $settings = self::get_settings();
        return ! empty( $settings['scan_post_types'] ) ? $settings['scan_post_types'] : array( 'post', 'page', 'product' );
    }

    /**
     * Count of published, scannable posts.
     *
     * @param string[] $post_types
     * @return int
     */
    private static function count_scannable( $post_types ) {
        global $wpdb;
        $placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- aggregate COUNT over the core posts table; runs per scan trigger
        return (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ({$placeholders})", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare -- {$placeholders} is a list of %s tokens supplied via $post_types; the query is prepared
            $post_types
        ) );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    /**
     * One ordered page of scannable post IDs.
     *
     * @param string[] $post_types
     * @param int      $limit
     * @param int      $offset
     * @return int[]
     */
    private static function scannable_ids( $post_types, $limit, $offset ) {
        global $wpdb;
        $placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );
        // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- paged ID lookup over the core posts table; runs per scan batch
        return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber -- $post_types is merged into a single array replacement arg that WPDB expands to match {$placeholders}; count is correct at runtime
            "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ({$placeholders}) ORDER BY ID LIMIT %d OFFSET %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- {$placeholders} is a list of %s tokens supplied via $post_types; the query is prepared
            array_merge( $post_types, array( (int) $limit, (int) $offset ) )
        ) ) );
        // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    }

    /**
     * Scan ONE batch of posts, starting at $offset.
     *
     * Drives the chunked, progress-reporting "Scan Now" from the admin UI: the
     * client calls this repeatedly with the returned next_offset until `done`,
     * so no single request scans the whole (possibly huge) site and the browser
     * fetch never times out.
     *
     * Important ordering vs the old single-pass scan: open findings are cleared
     * ONCE on the first batch (offset 0), and the last-scan timestamp is written
     * ONCE on the final batch — otherwise chunking would wipe earlier batches or
     * stamp the time prematurely.
     *
     * @param int $offset     Starting offset into the ordered post list.
     * @param int $batch_size Posts to scan this call (clamped 1–200).
     * @return array { total, processed, scanned, findings, next_offset, done }
     */
    public static function scan_batch( $offset = 0, $batch_size = 100 ) {
        @set_time_limit( 0 );       // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Squiz.PHP.DiscouragedFunctions.Discouraged -- extend limit for a long scan batch; best-effort
        @ignore_user_abort( true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

        $offset     = max( 0, (int) $offset );
        $batch_size = min( 200, max( 1, (int) $batch_size ) );

        $post_types = self::scan_post_types();
        $total      = self::count_scannable( $post_types );

        // First batch only: clear existing OPEN findings so the re-scan starts
        // clean. Resolved/dismissed survive (re-insertion is blocked by the
        // dismissed-key filter below).
        if ( $offset === 0 ) {
            AlmaSEO_Date_Hygiene_Model::clear_open();
        }

        // Dismissed/resolved keys are stable during a scan (the scan only
        // inserts 'open' rows), so re-reading per batch is safe.
        $dismissed_keys = AlmaSEO_Date_Hygiene_Model::get_dismissed_keys();

        $post_ids       = self::scannable_ids( $post_types, $batch_size, $offset );
        $processed      = 0;
        $findings_count = 0;

        foreach ( $post_ids as $pid ) {
            $findings = self::scan_post( (int) $pid );

            if ( ! empty( $findings ) ) {
                // detected_value must be sanitized the same way the model stores
                // it (sanitize_text_field collapses whitespace), or multi-word
                // findings won't match their stored key and a dismissed finding
                // would reappear after every re-scan.
                $findings = array_filter( $findings, function ( $f ) use ( $dismissed_keys ) {
                    $key = $f['post_id'] . ':' . $f['finding_type'] . ':' . sanitize_text_field( $f['detected_value'] );
                    return ! isset( $dismissed_keys[ $key ] );
                } );

                if ( ! empty( $findings ) ) {
                    $findings_count += AlmaSEO_Date_Hygiene_Model::insert_batch( $findings );
                }
            }

            $processed++;
        }

        $next_offset = $offset + $processed;
        $done = ( count( $post_ids ) < $batch_size ) || ( $next_offset >= $total );

        if ( $done ) {
            update_option( 'almaseo_dh_last_scan', current_time( 'mysql', true ) );
        }

        return array(
            'total'       => $total,
            'processed'   => $processed,
            'scanned'     => $next_offset,
            'findings'    => $findings_count,
            'next_offset' => $next_offset,
            'done'        => $done,
        );
    }

    /**
     * Full-site scan in one synchronous pass.
     *
     * Kept for programmatic/cron callers; the admin UI uses the chunked
     * scan_batch() instead. Delegates to scan_batch() so the clear-once and
     * timestamp-on-done semantics stay in one place.
     *
     * @return array { posts_scanned, findings_count }
     */
    public static function scan_all() {
        @set_time_limit( 0 );       // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, Squiz.PHP.DiscouragedFunctions.Discouraged -- extend limit for a full-site scan; best-effort
        @ignore_user_abort( true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged

        $offset   = 0;
        $scanned  = 0;
        $findings = 0;

        do {
            $r        = self::scan_batch( $offset, 50 );
            $scanned  = $r['scanned'];
            $findings += $r['findings'];
            $offset   = $r['next_offset'];
        } while ( ! $r['done'] );

        return array(
            'posts_scanned'  => $scanned,
            'findings_count' => $findings,
        );
    }

    /* ──────────────────────── Helpers ── */

    /**
     * Extract surrounding context for a match.
     *
     * Returns ~100 characters centered on the match position.
     *
     * @param string $text   Full text.
     * @param int    $offset Match start position.
     * @param int    $length Match length.
     * @return string
     */
    private static function extract_context( $text, $offset, $length ) {
        $context_radius = 50;
        $start = max( 0, $offset - $context_radius );
        $end   = min( strlen( $text ), $offset + $length + $context_radius );

        $snippet = substr( $text, $start, $end - $start );

        // Add ellipsis if truncated.
        if ( $start > 0 ) {
            $snippet = '...' . $snippet;
        }
        if ( $end < strlen( $text ) ) {
            $snippet = $snippet . '...';
        }

        return $snippet;
    }
}
