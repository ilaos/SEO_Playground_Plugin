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

        // Direct text mappings.
        $text_map = array(
            'title'               => '_almaseo_title',
            'description'         => '_almaseo_description',
            'canonical_url'       => '_almaseo_canonical_url',
            'og_title'            => '_almaseo_og_title',
            'og_description'      => '_almaseo_og_description',
            'og_image_custom_url' => '_almaseo_og_image',
            'twitter_title'       => '_almaseo_twitter_title',
            'twitter_description' => '_almaseo_twitter_description',
        );

        foreach ( $text_map as $aioseo_col => $almaseo_key ) {
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
