<?php
/**
 * Refresh Drafts – Engine
 *
 * Responsible for:
 *  1. Splitting post content into heading-delimited sections.
 *  2. Generating a section-level diff between the live post
 *     and new (AI-suggested or manual) content.
 *  3. Merging accepted / rejected section decisions back into
 *     a single HTML string ready to save.
 *
 * @package AlmaSEO
 * @since   7.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Refresh_Engine {

    /**
     * Regex used to split content on heading tags (h2-h4).
     * Captures the heading so it stays attached to each section.
     */
    const HEADING_PATTERN = '/(<h[2-4][^>]*>.*?<\/h[2-4]>)/is';

    /* ──────────────────────── 1. SPLIT ── */

    /**
     * Split HTML content into sections.
     *
     * Each section is an associative array:
     *   [ 'heading' => string|null, 'body' => string ]
     *
     * The first section (before the first heading) will have heading = null.
     *
     * @param  string $html Raw post_content HTML.
     * @return array
     */
    public static function split( $html ) {
        $parts    = preg_split( self::HEADING_PATTERN, $html, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY );
        $sections = array();
        $current  = array( 'heading' => null, 'body' => '' );

        foreach ( $parts as $part ) {
            if ( preg_match( '/^<h[2-4][^>]*>/i', $part ) ) {
                // Push the previous section.
                $sections[] = $current;
                $current    = array( 'heading' => $part, 'body' => '' );
            } else {
                $current['body'] .= $part;
            }
        }

        // Push the last section.
        $sections[] = $current;

        // Remove completely empty lead section.
        if ( empty( trim( $sections[0]['body'] ) ) && $sections[0]['heading'] === null ) {
            array_shift( $sections );
        }

        // Re-index and attach stable IDs.
        $indexed = array();
        foreach ( array_values( $sections ) as $i => $sec ) {
            $sec['id']  = $i;
            $sec['key'] = md5( ( $sec['heading'] ?? '' ) . '::' . $i );
            $indexed[]  = $sec;
        }

        return $indexed;
    }

    /* ──────────────────────── 2. DIFF ── */

    /**
     * Build a section-level diff between original and proposed content.
     *
     * For every section in the proposed version, the diff entry contains:
     *   - id, key, heading
     *   - old_body   (from original)
     *   - new_body   (from proposed)
     *   - changed    (bool)
     *   - decision   ('pending' | 'accept' | 'reject')
     *
     * Sections are matched by index. If the proposed version has extra
     * sections they are flagged as additions; if fewer, the missing
     * originals are flagged as removals.
     *
     * @param  string $original_html Live post content.
     * @param  string $proposed_html New / AI-generated content.
     * @return array  Array of diff-section entries.
     */
    public static function diff( $original_html, $proposed_html ) {
        $old = self::split( $original_html );
        $new = self::split( $proposed_html );

        $max  = max( count( $old ), count( $new ) );
        $diff = array();

        for ( $i = 0; $i < $max; $i++ ) {
            $o = isset( $old[ $i ] ) ? $old[ $i ] : null;
            $n = isset( $new[ $i ] ) ? $new[ $i ] : null;

            $entry = array(
                'id'       => $i,
                'key'      => $n ? $n['key'] : $o['key'],
                'heading'  => $n ? $n['heading'] : $o['heading'],
                'old_body' => $o ? $o['body'] : '',
                'new_body' => $n ? $n['body'] : '',
                'changed'  => true,
                'decision' => 'pending',
            );

            // Mark unchanged when both exist and match.
            if ( $o && $n && self::normalise( $o['body'] ) === self::normalise( $n['body'] )
                 && self::normalise( (string) $o['heading'] ) === self::normalise( (string) $n['heading'] ) ) {
                $entry['changed'] = false;
            }

            $diff[] = $entry;
        }

        return $diff;
    }

    /* ──────────────────────── 3. MERGE ── */

    /**
     * Merge reviewed sections back into a single HTML string.
     *
     * For each section the caller supplies a decision:
     *   'accept' -> use new_body
     *   'reject' -> use old_body
     *
     * Sections still set to 'pending' default to 'reject' (safe).
     *
     * @param  array $sections Array of diff-section entries with decisions.
     * @return string  Merged HTML.
     */
    public static function merge( array $sections ) {
        $html = '';

        foreach ( $sections as $sec ) {
            $decision = isset( $sec['decision'] ) ? $sec['decision'] : 'reject';
            $use_new  = ( $decision === 'accept' );

            if ( ! empty( $sec['heading'] ) ) {
                $html .= $sec['heading'] . "\n";
            }

            $html .= $use_new ? $sec['new_body'] : $sec['old_body'];
        }

        return $html;
    }

    /* ──────────────────────── helpers ── */

    /**
     * Normalise HTML for comparison: strip extra whitespace,
     * collapse tags, lowercase.
     *
     * @param  string $html
     * @return string
     */
    private static function normalise( $html ) {
        $html = wp_strip_all_tags( $html );
        $html = preg_replace( '/\s+/', ' ', $html );
        return strtolower( trim( $html ) );
    }
}
