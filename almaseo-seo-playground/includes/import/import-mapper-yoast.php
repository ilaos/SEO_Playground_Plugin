<?php
/**
 * AlmaSEO Import Mapper — Yoast SEO
 *
 * Maps Yoast SEO meta keys to AlmaSEO meta keys.
 *
 * @package AlmaSEO
 * @since   8.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Import_Mapper_Yoast {

    /**
     * Yoast default title templates that should be skipped during per-post
     * import.  These are factory defaults — importing them would just
     * override AlmaSEO's own default template with an equivalent one.
     *
     * Compared after converting Yoast-specific tags to AlmaSEO format and
     * normalising whitespace/case.
     */
    private static $default_templates = array(
        '%%title%% %%sep%% %%sitename%%',
        '%%title%%',
        '%%sitename%% %%sep%% %%sitetagline%%',
        '%%sitename%%',
        '%%term_title%% %%sep%% %%sitename%%',
        '%%term_title%%',
        '%%excerpt%%',
    );

    /**
     * Convert Yoast-specific template variables to AlmaSEO smart tags.
     * Most map 1:1, but a few differ (%%sitedesc%% → %%sitetagline%%, etc.).
     *
     * @param string $text Raw Yoast value.
     * @return string Value with tags normalised to AlmaSEO format.
     */
    public static function convert_tags( $text ) {
        static $replacements = null;
        if ( $replacements === null ) {
            $replacements = array(
                '%%sitedesc%%'          => '%%sitetagline%%',
                '%%excerpt_only%%'      => '%%excerpt%%',
                '%%name%%'              => '%%author%%',
                '%%primary_category%%'  => '%%category%%',
                '%%post_year%%'         => '%%year%%',
            );
        }
        return str_replace( array_keys( $replacements ), array_values( $replacements ), $text );
    }

    /**
     * Check whether a normalised value is just a default template.
     *
     * @param string $normalised Value after convert_tags().
     * @return bool
     */
    public static function is_default_template( $normalised ) {
        $clean = strtolower( trim( preg_replace( '/\s+/', ' ', $normalised ) ) );
        return in_array( $clean, self::$default_templates, true );
    }

    /**
     * Resolve %%tags%% in a value to actual text using the post context.
     * Returns the final resolved string, or empty if nothing usable remains.
     *
     * @param string $value   Value with %%tags%%.
     * @param int    $post_id Post ID for context.
     * @return string Resolved text.
     */
    public static function resolve_for_post( $value, $post_id ) {
        if ( strpos( $value, '%%' ) === false ) {
            return $value; // No tags — plain text.
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            return $value;
        }

        if ( class_exists( 'AlmaSEO_Smart_Tags' ) ) {
            return AlmaSEO_Smart_Tags::replace( $value, array( 'post' => $post ) );
        }

        return $value;
    }

    /**
     * Meta key mapping: Yoast key => AlmaSEO key.
     */
    private static $meta_map = array(
        '_yoast_wpseo_title'                  => '_almaseo_title',
        '_yoast_wpseo_metadesc'               => '_almaseo_description',
        '_yoast_wpseo_focuskw'                => '_almaseo_focus_keyword',
        '_yoast_wpseo_canonical'              => '_almaseo_canonical_url',
        '_yoast_wpseo_opengraph-title'        => '_almaseo_og_title',
        '_yoast_wpseo_opengraph-description'  => '_almaseo_og_description',
        '_yoast_wpseo_opengraph-image'        => '_almaseo_og_image',
        '_yoast_wpseo_twitter-title'          => '_almaseo_twitter_title',
        '_yoast_wpseo_twitter-description'    => '_almaseo_twitter_description',
    );

    /**
     * Get a batch of post IDs with Yoast data.
     *
     * @param int $offset Offset.
     * @param int $limit  Batch size.
     * @return array Rows with post_id and all meta values.
     */
    public static function get_batch( $offset, $limit ) {
        global $wpdb;

        $post_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta}
             WHERE meta_key IN ('_yoast_wpseo_title', '_yoast_wpseo_metadesc')
             AND meta_value != ''
             ORDER BY post_id ASC
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        ) );

        $rows = array();
        foreach ( $post_ids as $post_id ) {
            $row = array( 'post_id' => (int) $post_id );

            foreach ( array_keys( self::$meta_map ) as $yoast_key ) {
                $row[ $yoast_key ] = get_post_meta( $post_id, $yoast_key, true );
            }

            // Special: robots.
            $row['_yoast_wpseo_meta-robots-noindex']  = get_post_meta( $post_id, '_yoast_wpseo_meta-robots-noindex', true );
            $row['_yoast_wpseo_meta-robots-nofollow'] = get_post_meta( $post_id, '_yoast_wpseo_meta-robots-nofollow', true );

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Map a single row of Yoast data to AlmaSEO meta keys.
     *
     * @param array $row Raw data row.
     * @return array AlmaSEO meta key => value.
     */
    public static function map_row( $row ) {
        $mapped  = array();
        $post_id = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;

        // Text fields that may contain Yoast template variables.
        $text_keys = array(
            '_yoast_wpseo_title'                 => '_almaseo_title',
            '_yoast_wpseo_metadesc'              => '_almaseo_description',
            '_yoast_wpseo_opengraph-title'       => '_almaseo_og_title',
            '_yoast_wpseo_opengraph-description' => '_almaseo_og_description',
            '_yoast_wpseo_twitter-title'          => '_almaseo_twitter_title',
            '_yoast_wpseo_twitter-description'    => '_almaseo_twitter_description',
        );

        // URL / non-template fields — copy as-is.
        $url_keys = array(
            '_yoast_wpseo_focuskw'               => '_almaseo_focus_keyword',
            '_yoast_wpseo_canonical'              => '_almaseo_canonical_url',
            '_yoast_wpseo_opengraph-image'        => '_almaseo_og_image',
        );

        foreach ( $text_keys as $yoast_key => $almaseo_key ) {
            if ( empty( $row[ $yoast_key ] ) ) {
                continue;
            }

            $raw = $row[ $yoast_key ];

            // 1. Normalise Yoast-specific tags to AlmaSEO format.
            $converted = self::convert_tags( $raw );

            // 2. Skip if the value is just a default template.
            if ( self::is_default_template( $converted ) ) {
                continue;
            }

            // 3. Resolve %%tags%% to actual post values.
            if ( $post_id > 0 ) {
                $resolved = self::resolve_for_post( $converted, $post_id );
            } else {
                $resolved = $converted;
            }

            // 4. Only import if we have usable text.
            $resolved = trim( $resolved );
            if ( ! empty( $resolved ) ) {
                $mapped[ $almaseo_key ] = sanitize_text_field( $resolved );
            }
        }

        foreach ( $url_keys as $yoast_key => $almaseo_key ) {
            if ( ! empty( $row[ $yoast_key ] ) ) {
                $mapped[ $almaseo_key ] = sanitize_text_field( $row[ $yoast_key ] );
            }
        }

        // Robots: noindex.
        if ( isset( $row['_yoast_wpseo_meta-robots-noindex'] ) ) {
            $val = $row['_yoast_wpseo_meta-robots-noindex'];
            if ( $val === '1' || $val === 1 ) {
                $mapped['_almaseo_robots_index'] = 'noindex';
            }
        }

        // Robots: nofollow.
        if ( isset( $row['_yoast_wpseo_meta-robots-nofollow'] ) ) {
            $val = $row['_yoast_wpseo_meta-robots-nofollow'];
            if ( $val === '1' || $val === 1 ) {
                $mapped['_almaseo_robots_follow'] = 'nofollow';
            }
        }

        return $mapped;
    }
}
