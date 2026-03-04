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
        $mapped = array();

        foreach ( self::$meta_map as $rm_key => $almaseo_key ) {
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
