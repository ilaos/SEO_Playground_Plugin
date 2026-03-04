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
        $mapped = array();

        foreach ( self::$meta_map as $yoast_key => $almaseo_key ) {
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
