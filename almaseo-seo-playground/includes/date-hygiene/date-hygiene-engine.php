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
    public static function scan_all() {
        global $wpdb;

        $settings     = self::get_settings();
        $post_types   = ! empty( $settings['scan_post_types'] ) ? $settings['scan_post_types'] : array( 'post', 'page', 'product' );
        $placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $total = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ({$placeholders})",
            $post_types
        ) );

        // Collect dismissed/resolved keys so we can skip re-inserting them.
        $dismissed_keys = AlmaSEO_Date_Hygiene_Model::get_dismissed_keys();

        // Clear only open findings — resolved/dismissed survive the re-scan.
        AlmaSEO_Date_Hygiene_Model::clear_open();

        $batch_size      = 50;
        $posts_scanned   = 0;
        $findings_count  = 0;

        for ( $batch_offset = 0; $batch_offset < $total; $batch_offset += $batch_size ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
            $post_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ({$placeholders}) ORDER BY ID LIMIT %d OFFSET %d",
                array_merge( $post_types, array( $batch_size, $batch_offset ) )
            ) );

            foreach ( $post_ids as $pid ) {
                $findings = self::scan_post( (int) $pid );

                if ( ! empty( $findings ) ) {
                    // Filter out findings that were previously dismissed/resolved.
                    $findings = array_filter( $findings, function ( $f ) use ( $dismissed_keys ) {
                        $key = $f['post_id'] . ':' . $f['finding_type'] . ':' . $f['detected_value'];
                        return ! isset( $dismissed_keys[ $key ] );
                    } );

                    if ( ! empty( $findings ) ) {
                        $findings_count += AlmaSEO_Date_Hygiene_Model::insert_batch( $findings );
                    }
                }

                $posts_scanned++;
            }
        }

        // Store last scan timestamp.
        update_option( 'almaseo_dh_last_scan', current_time( 'mysql', true ) );

        return array(
            'posts_scanned'  => $posts_scanned,
            'findings_count' => $findings_count,
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
