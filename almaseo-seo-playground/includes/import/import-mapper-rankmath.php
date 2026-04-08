<?php
/**
 * AlmaSEO Import Mapper — Rank Math
 *
 * Maps Rank Math SEO meta keys to AlmaSEO meta keys.
 *
 * @package AlmaSEO
 * @since   8.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Import_Mapper_RankMath {

    /**
     * Rank Math default title templates (after conversion to AlmaSEO format).
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
     * Convert Rank Math %single-percent% tags to AlmaSEO %%double%% format.
     *
     * @param string $text Raw Rank Math value.
     * @return string Value with tags normalised to AlmaSEO format.
     */
    public static function convert_tags( $text ) {
        static $replacements = null;
        if ( $replacements === null ) {
            $replacements = array(
                '%title%'              => '%%title%%',
                '%sitename%'           => '%%sitename%%',
                '%sitedesc%'           => '%%sitetagline%%',
                '%sep%'                => '%%sep%%',
                '%excerpt%'            => '%%excerpt%%',
                '%date%'               => '%%date%%',
                '%modified%'           => '%%modified%%',
                '%name%'               => '%%author%%',
                '%category%'           => '%%category%%',
                '%tag%'                => '%%tag%%',
                '%term%'               => '%%term_title%%',
                '%term_description%'   => '%%term_description%%',
                '%search_query%'       => '%%searchphrase%%',
                '%page%'               => '%%page%%',
                '%count%'              => '%%pagetotal%%',
                '%id%'                 => '%%id%%',
                '%currentyear%'        => '%%currentyear%%',
                '%focuskw%'            => '%%focuskeyword%%',
                '%primary_taxonomy_terms%' => '%%category%%',
            );
        }
        return str_replace( array_keys( $replacements ), array_values( $replacements ), $text );
    }

    /**
     * Check whether a normalised value is just a default template.
     */
    public static function is_default_template( $normalised ) {
        $clean = strtolower( trim( preg_replace( '/\s+/', ' ', $normalised ) ) );
        return in_array( $clean, self::$default_templates, true );
    }

    /**
     * Resolve %%tags%% in a value to actual text using the post context.
     */
    public static function resolve_for_post( $value, $post_id ) {
        if ( strpos( $value, '%%' ) === false ) {
            return $value;
        }
        $post = get_post( $post_id );
        if ( ! $post || ! class_exists( 'AlmaSEO_Smart_Tags' ) ) {
            return $value;
        }
        return AlmaSEO_Smart_Tags::replace( $value, array( 'post' => $post ) );
    }

    /**
     * Direct meta key mapping.
     */
    private static $meta_map = array(
        'rank_math_title'               => '_almaseo_title',
        'rank_math_description'         => '_almaseo_description',
        'rank_math_focus_keyword'       => '_almaseo_focus_keyword',
        'rank_math_canonical_url'       => '_almaseo_canonical_url',
        'rank_math_facebook_title'      => '_almaseo_og_title',
        'rank_math_facebook_description' => '_almaseo_og_description',
        'rank_math_facebook_image'      => '_almaseo_og_image',
        'rank_math_twitter_title'       => '_almaseo_twitter_title',
        'rank_math_twitter_description' => '_almaseo_twitter_description',
    );

    /**
     * Get a batch of post IDs with Rank Math data.
     *
     * @param int $offset Offset.
     * @param int $limit  Batch size.
     * @return array Rows.
     */
    public static function get_batch( $offset, $limit ) {
        global $wpdb;

        $post_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT post_id FROM {$wpdb->postmeta}
             WHERE meta_key IN ('rank_math_title', 'rank_math_description')
             AND meta_value != ''
             ORDER BY post_id ASC
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        ) );

        $rows = array();
        foreach ( $post_ids as $post_id ) {
            $row = array( 'post_id' => (int) $post_id );

            foreach ( array_keys( self::$meta_map ) as $rm_key ) {
                $row[ $rm_key ] = get_post_meta( $post_id, $rm_key, true );
            }

            // Special: robots (serialized array).
            $row['rank_math_robots'] = get_post_meta( $post_id, 'rank_math_robots', true );

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Map a single row to AlmaSEO meta keys.
     *
     * @param array $row Raw data.
     * @return array AlmaSEO meta key => value.
     */
    public static function map_row( $row ) {
        $mapped  = array();
        $post_id = isset( $row['post_id'] ) ? (int) $row['post_id'] : 0;

        // Text fields that may contain Rank Math template variables.
        $text_keys = array(
            'rank_math_title'               => '_almaseo_title',
            'rank_math_description'         => '_almaseo_description',
            'rank_math_facebook_title'      => '_almaseo_og_title',
            'rank_math_facebook_description' => '_almaseo_og_description',
            'rank_math_twitter_title'       => '_almaseo_twitter_title',
            'rank_math_twitter_description' => '_almaseo_twitter_description',
        );

        // Non-template fields — copy as-is.
        $plain_keys = array(
            'rank_math_focus_keyword'       => '_almaseo_focus_keyword',
            'rank_math_canonical_url'       => '_almaseo_canonical_url',
            'rank_math_facebook_image'      => '_almaseo_og_image',
        );

        foreach ( $text_keys as $rm_key => $almaseo_key ) {
            if ( empty( $row[ $rm_key ] ) ) {
                continue;
            }

            $raw = $row[ $rm_key ];

            // 1. Convert %single% tags to %%double%% AlmaSEO format.
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

        foreach ( $plain_keys as $rm_key => $almaseo_key ) {
            if ( ! empty( $row[ $rm_key ] ) ) {
                $mapped[ $almaseo_key ] = sanitize_text_field( $row[ $rm_key ] );
            }
        }

        // Robots: parse serialized array or comma-separated string.
        if ( ! empty( $row['rank_math_robots'] ) ) {
            $robots = $row['rank_math_robots'];

            if ( is_string( $robots ) ) {
                $robots = maybe_unserialize( $robots );
            }
            if ( is_string( $robots ) ) {
                $robots = array_map( 'trim', explode( ',', $robots ) );
            }

            if ( is_array( $robots ) ) {
                if ( in_array( 'noindex', $robots, true ) ) {
                    $mapped['_almaseo_robots_index'] = 'noindex';
                }
                if ( in_array( 'nofollow', $robots, true ) ) {
                    $mapped['_almaseo_robots_follow'] = 'nofollow';
                }
                if ( in_array( 'noarchive', $robots, true ) ) {
                    $mapped['_almaseo_robots_archive'] = 'noarchive';
                }
                if ( in_array( 'nosnippet', $robots, true ) ) {
                    $mapped['_almaseo_robots_snippet'] = 'nosnippet';
                }
                if ( in_array( 'noimageindex', $robots, true ) ) {
                    $mapped['_almaseo_robots_imageindex'] = 'noimageindex';
                }
            }
        }

        return $mapped;
    }
}
