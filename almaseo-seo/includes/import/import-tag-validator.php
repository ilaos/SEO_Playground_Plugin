<?php
/**
 * AlmaSEO Template Tag Validator
 *
 * Centralised helper that detects foreign SEO plugin template tokens in
 * stored post meta.  Used by the rendering layer, health scorer, and
 * migration verifier so that bad imported values never block global
 * template fallback or produce literal "#post_title" in HTML output.
 *
 * @package AlmaSEO
 * @since   1.0.2
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Tag_Validator {

    /**
     * AlmaSEO's own valid smart-tag tokens (%%tag%% format).
     * Kept in sync with AlmaSEO_Smart_Tags::get_available_tags().
     */
    private static $valid_almaseo_tags = array(
        '%%title%%', '%%sitename%%', '%%sitetagline%%', '%%sep%%',
        '%%excerpt%%', '%%date%%', '%%modified%%', '%%author%%',
        '%%author_first_name%%', '%%author_last_name%%',
        '%%category%%', '%%tag%%', '%%primary_category%%',
        '%%term_title%%', '%%term_description%%',
        '%%searchphrase%%', '%%page%%', '%%pagetotal%%', '%%pagenumber%%',
        '%%id%%', '%%year%%', '%%currentyear%%', '%%currentdate%%',
        '%%currentmonth%%', '%%currentday%%',
        '%%focuskeyword%%', '%%pt_single%%', '%%pt_plural%%',
    );

    /**
     * Patterns that match foreign SEO plugin template variables.
     *
     * These are the same patterns the migration verifier uses.
     */
    private static $foreign_patterns = array(
        'aioseo'   => '/#[a-z_]+/',            // #post_title, #separator_sa …
        'yoast'    => '/%%[a-z_]+%%/',          // %%title%%, %%sep%% …
        'rankmath' => '/%[a-z_]+%/',            // %title%, %sep% …
    );

    /**
     * Check whether a stored SEO value contains foreign template tokens
     * that AlmaSEO cannot resolve at runtime.
     *
     * Returns TRUE if the value is tainted — i.e. it should NOT be used
     * as a literal custom override.
     *
     * @param string $value Stored meta value.
     * @return bool
     */
    public static function contains_foreign_tokens( $value ) {
        if ( ! is_string( $value ) || $value === '' ) {
            return false;
        }

        // 1. AIOSEO hash-tags: always foreign.
        if ( preg_match( self::$foreign_patterns['aioseo'], $value ) ) {
            return true;
        }

        // 2. Yoast-style %%tag%% — only foreign if NOT a valid AlmaSEO tag.
        if ( preg_match_all( self::$foreign_patterns['yoast'], $value, $matches ) ) {
            foreach ( $matches[0] as $tag ) {
                if ( ! in_array( $tag, self::$valid_almaseo_tags, true ) ) {
                    return true;
                }
            }
        }

        // 3. Rank Math single-percent %tag% — but only match tokens that
        //    are NOT part of a valid %%tag%% (which was already handled).
        //    We look for %word% that is not preceded or followed by %.
        if ( preg_match_all( '/(?<!%)%([a-z_]+)%(?!%)/', $value, $matches ) ) {
            // Any single-percent token is foreign.
            return true;
        }

        return false;
    }

    /**
     * Return a cleaned version of the value, or empty string if the value
     * is entirely template tokens (no real custom text).
     *
     * If the value is a mix of real text + foreign tokens, this attempts
     * AIOSEO conversion first (since that's the most common case).  If
     * foreign tokens remain after conversion, the value is treated as
     * invalid and an empty string is returned.
     *
     * @param string $value Stored meta value.
     * @return string Cleaned value, or '' if invalid.
     */
    public static function sanitize_seo_value( $value ) {
        if ( ! is_string( $value ) || $value === '' ) {
            return '';
        }

        // Fast path: no template tokens at all → value is clean.
        if ( ! self::contains_foreign_tokens( $value ) ) {
            return $value;
        }

        // Try AIOSEO conversion (the most common imported format).
        if ( class_exists( 'AlmaSEO_Import_Mapper_AIOSEO' ) ) {
            // Skip if entire value is a known AIOSEO default template.
            if ( AlmaSEO_Import_Mapper_AIOSEO::is_default_template( $value ) ) {
                return '';
            }
            $converted = AlmaSEO_Import_Mapper_AIOSEO::convert_tags( $value );

            // After conversion, re-check: if no foreign tokens remain the
            // value is now valid AlmaSEO template syntax.
            if ( ! self::contains_foreign_tokens( $converted ) ) {
                return $converted;
            }
        }

        // Still has foreign tokens after best-effort conversion → invalid.
        return '';
    }

    /**
     * Check whether a stored value counts as a "real" SEO value that is
     * present and usable — i.e. it is non-empty AND free of unresolvable
     * foreign template tokens.
     *
     * Use this instead of `! empty( $value )` in health checks and
     * scorecard logic.
     *
     * @param string $value Stored meta value.
     * @return bool
     */
    public static function is_usable_value( $value ) {
        if ( empty( $value ) ) {
            return false;
        }
        return ! self::contains_foreign_tokens( $value );
    }
}
