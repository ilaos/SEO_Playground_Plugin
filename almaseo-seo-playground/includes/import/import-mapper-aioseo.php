<?php
/**
 * AlmaSEO Import Mapper — All in One SEO (AIOSEO)
 *
 * Maps AIOSEO data from the custom aioseo_posts table to AlmaSEO meta keys.
 *
 * @package AlmaSEO
 * @since   8.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Import_Mapper_AIOSEO {

    /**
     * AIOSEO default title templates that should be skipped during per-post
     * import.  These are the factory defaults AIOSEO writes to every row in
     * aioseo_posts even when the user never customised anything.
     *
     * We normalise before comparing (lowercase, collapse whitespace) so minor
     * formatting differences don't cause mismatches.
     */
    private static $default_templates = array(
        // Post title defaults.
        '#post_title #separator_sa #site_title',
        '#post_title',
        // Site/homepage defaults.
        '#site_title #separator_sa #tagline',
        '#site_title',
        // Taxonomy/archive defaults.
        '#taxonomy_title #separator_sa #site_title',
        '#taxonomy_title',
        '#archive_title #separator_sa #site_title',
        // Description defaults (excerpt-based).
        '#post_excerpt',
        '#taxonomy_description',
    );

    /**
     * Convert AIOSEO hash-style template variables to AlmaSEO %%tag%% format.
     *
     * This is the authoritative tag map used by both the per-post mapper and
     * the term mapper.  The settings mapper has its own private copy — if you
     * add a tag here, add it there too.
     *
     * @param string $text Raw AIOSEO value.
     * @return string Value with tags converted to AlmaSEO format.
     */
    public static function convert_tags( $text ) {
        static $replacements = null;

        if ( $replacements === null ) {
            $replacements = array(
                '#post_title'           => '%%title%%',
                '#site_title'           => '%%sitename%%',
                '#tagline'              => '%%sitetagline%%',
                '#separator_sa'         => '%%sep%%',
                '#post_excerpt'         => '%%excerpt%%',
                '#post_date'            => '%%date%%',
                '#post_modified_date'   => '%%modified%%',
                '#author_name'          => '%%author%%',
                '#author_first_name'    => '%%author_first_name%%',
                '#author_last_name'     => '%%author_last_name%%',
                '#taxonomy_title'       => '%%term_title%%',
                '#taxonomy_description' => '%%term_description%%',
                '#search_term'          => '%%searchphrase%%',
                '#post_id'              => '%%id%%',
                '#current_year'         => '%%currentyear%%',
                '#current_month'        => '%%currentmonth%%',
                '#current_date'         => '%%currentdate%%',
                '#category'             => '%%category%%',
                '#page_number'          => '%%pagenumber%%',
            );
        }

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $text );
    }

    /**
     * Check whether a raw AIOSEO value is just a default template (i.e. the
     * user never customised it).  Returns true if the value should be skipped.
     *
     * @param string $raw_value Value straight from the aioseo_posts table.
     * @return bool
     */
    public static function is_default_template( $raw_value ) {
        $normalised = strtolower( trim( preg_replace( '/\s+/', ' ', $raw_value ) ) );
        return in_array( $normalised, self::$default_templates, true );
    }

    /**
     * Get a batch of rows from the aioseo_posts table.
     *
     * @param int $offset Offset.
     * @param int $limit  Batch size.
     * @return array Rows.
     */
    public static function get_batch( $offset, $limit ) {
        global $wpdb;

        $table = $wpdb->prefix . 'aioseo_posts';

        // Verify table exists.
        $table_exists = $wpdb->get_var(
            $wpdb->prepare( "SHOW TABLES LIKE %s", $table )
        );

        if ( ! $table_exists ) {
            return array();
        }

        $columns = array(
            'post_id', 'title', 'description', 'keyphrases',
            'canonical_url',
            'robots_noindex', 'robots_nofollow', 'robots_noarchive', 'robots_nosnippet',
            'og_title', 'og_description', 'og_image_custom_url',
            'twitter_title', 'twitter_description',
        );

        // Check which columns actually exist (AIOSEO versions vary).
        $actual_columns = $wpdb->get_col( "DESCRIBE `{$table}`", 0 );
        $select_columns = array_intersect( $columns, $actual_columns );

        if ( empty( $select_columns ) ) {
            return array();
        }

        $select = implode( ', ', array_map( function ( $col ) {
            return '`' . $col . '`';
        }, $select_columns ) );

        return $wpdb->get_results( $wpdb->prepare(
            "SELECT {$select} FROM `{$table}`
             WHERE (title != '' AND title IS NOT NULL)
                OR (description != '' AND description IS NOT NULL)
             ORDER BY post_id ASC
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        ), ARRAY_A );
    }

    /**
     * Map a single AIOSEO row to AlmaSEO meta keys.
     *
     * @param array $row Raw table row.
     * @return array AlmaSEO meta key => value.
     */
    public static function map_row( $row ) {
        $mapped = array();

        // Text fields that may contain AIOSEO template variables.
        $text_map = array(
            'title'               => '_almaseo_title',
            'description'         => '_almaseo_description',
            'og_title'            => '_almaseo_og_title',
            'og_description'      => '_almaseo_og_description',
            'twitter_title'       => '_almaseo_twitter_title',
            'twitter_description' => '_almaseo_twitter_description',
        );

        // URL fields — no template conversion (URLs never contain tags).
        $url_map = array(
            'canonical_url'       => '_almaseo_canonical_url',
            'og_image_custom_url' => '_almaseo_og_image',
        );

        foreach ( $text_map as $aioseo_col => $almaseo_key ) {
            if ( empty( $row[ $aioseo_col ] ) ) {
                continue;
            }

            $raw = $row[ $aioseo_col ];

            // Skip values that are just AIOSEO's factory default template —
            // the user never customised this field, so importing it would
            // overwrite AlmaSEO's own default with a redundant template.
            if ( self::is_default_template( $raw ) ) {
                continue;
            }

            // Convert any remaining AIOSEO #hash tags to AlmaSEO %%tags%%.
            // Mixed values like "Pain and Aging #separator_sa #site_title"
            // become "Pain and Aging %%sep%% %%sitename%%".
            $converted = self::convert_tags( $raw );

            $mapped[ $almaseo_key ] = sanitize_text_field( $converted );
        }

        foreach ( $url_map as $aioseo_col => $almaseo_key ) {
            if ( ! empty( $row[ $aioseo_col ] ) ) {
                $mapped[ $almaseo_key ] = sanitize_text_field( $row[ $aioseo_col ] );
            }
        }

        // Keyphrases: stored as JSON like {"focus":{"keyphrase":"example",...}}.
        if ( ! empty( $row['keyphrases'] ) ) {
            $kp = json_decode( $row['keyphrases'], true );
            if ( is_array( $kp ) && isset( $kp['focus']['keyphrase'] ) && ! empty( $kp['focus']['keyphrase'] ) ) {
                $mapped['_almaseo_focus_keyword'] = sanitize_text_field( $kp['focus']['keyphrase'] );
            }
        }

        // Robots flags (boolean columns: 1 = set).
        $robots_map = array(
            'robots_noindex'   => array( '_almaseo_robots_index', 'noindex' ),
            'robots_nofollow'  => array( '_almaseo_robots_follow', 'nofollow' ),
            'robots_noarchive' => array( '_almaseo_robots_archive', 'noarchive' ),
            'robots_nosnippet' => array( '_almaseo_robots_snippet', 'nosnippet' ),
        );

        foreach ( $robots_map as $col => $target ) {
            if ( isset( $row[ $col ] ) && ( $row[ $col ] === '1' || $row[ $col ] === 1 || $row[ $col ] === true ) ) {
                $mapped[ $target[0] ] = $target[1];
            }
        }

        return $mapped;
    }
}
