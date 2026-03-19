<?php
/**
 * AlmaSEO Import Detector
 *
 * Detects available SEO data from competitor plugins (Yoast, Rank Math, AIOSEO)
 * even if those plugins are deactivated — checks meta keys and tables directly.
 * Also computes overlap between sources so the UI can guide users.
 *
 * @package AlmaSEO
 * @since   8.1.0
 * @updated 8.8.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Import_Detector {

    /**
     * Detect all available data sources with overlap analysis.
     *
     * @return array Keyed by source slug, plus '_meta' with recommendation.
     */
    public static function detect_all() {
        global $wpdb;

        $yoast    = self::detect_yoast();
        $rankmath = self::detect_rankmath();
        $aioseo   = self::detect_aioseo();

        // Compute overlap between sources that have data.
        $sources_with_data = array();
        if ( $yoast['available'] )    $sources_with_data['yoast']    = $yoast;
        if ( $rankmath['available'] ) $sources_with_data['rankmath'] = $rankmath;
        if ( $aioseo['available'] )   $sources_with_data['aioseo']   = $aioseo;

        if ( count( $sources_with_data ) > 1 ) {
            $overlap = self::compute_overlap( $wpdb, array_keys( $sources_with_data ) );
            foreach ( $overlap as $key => $info ) {
                if ( $key === 'yoast' && isset( $info ) )       $yoast['overlap']    = $info;
                if ( $key === 'rankmath' && isset( $info ) )    $rankmath['overlap']  = $info;
                if ( $key === 'aioseo' && isset( $info ) )      $aioseo['overlap']    = $info;
            }
        }

        // Build recommendation metadata.
        $meta = self::build_recommendation( $yoast, $rankmath, $aioseo );

        return array(
            'yoast'    => $yoast,
            'rankmath' => $rankmath,
            'aioseo'   => $aioseo,
            '_meta'    => $meta,
        );
    }

    /**
     * Detect Yoast SEO data.
     */
    private static function detect_yoast() {
        global $wpdb;

        $count = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
             WHERE meta_key IN ('_yoast_wpseo_title', '_yoast_wpseo_metadesc')
             AND meta_value != ''"
        );

        return array(
            'name'           => 'Yoast SEO',
            'available'      => $count > 0,
            'plugin_active'  => defined( 'WPSEO_VERSION' ),
            'plugin_version' => defined( 'WPSEO_VERSION' ) ? WPSEO_VERSION : null,
            'record_count'   => $count,
        );
    }

    /**
     * Detect Rank Math SEO data.
     */
    private static function detect_rankmath() {
        global $wpdb;

        $count = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT post_id) FROM {$wpdb->postmeta}
             WHERE meta_key IN ('rank_math_title', 'rank_math_description')
             AND meta_value != ''"
        );

        return array(
            'name'           => 'Rank Math',
            'available'      => $count > 0,
            'plugin_active'  => class_exists( 'RankMath' ),
            'record_count'   => $count,
        );
    }

    /**
     * Detect All in One SEO data.
     */
    private static function detect_aioseo() {
        global $wpdb;

        $table = $wpdb->prefix . 'aioseo_posts';
        $table_exists = (bool) $wpdb->get_var(
            $wpdb->prepare( "SHOW TABLES LIKE %s", $table )
        );

        $count = 0;
        if ( $table_exists ) {
            $count = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM `{$table}` WHERE (title != '' AND title IS NOT NULL) OR (description != '' AND description IS NOT NULL)"
            );
        }

        return array(
            'name'           => 'All in One SEO',
            'available'      => $count > 0,
            'plugin_active'  => defined( 'AIOSEO_VERSION' ),
            'table_exists'   => $table_exists,
            'record_count'   => $count,
        );
    }

    /**
     * Compute overlap: for each source, how many of its posts also exist in other sources.
     *
     * @param wpdb  $wpdb    WordPress database object.
     * @param array $sources Array of source slugs that have data.
     * @return array Keyed by source slug, each containing overlap details.
     */
    private static function compute_overlap( $wpdb, $sources ) {
        $result = array();

        // Build post ID subqueries for each source.
        $subqueries = array();

        if ( in_array( 'yoast', $sources, true ) ) {
            $subqueries['yoast'] = "SELECT DISTINCT post_id FROM {$wpdb->postmeta}
                WHERE meta_key IN ('_yoast_wpseo_title', '_yoast_wpseo_metadesc') AND meta_value != ''";
        }

        if ( in_array( 'rankmath', $sources, true ) ) {
            $subqueries['rankmath'] = "SELECT DISTINCT post_id FROM {$wpdb->postmeta}
                WHERE meta_key IN ('rank_math_title', 'rank_math_description') AND meta_value != ''";
        }

        if ( in_array( 'aioseo', $sources, true ) ) {
            $table = $wpdb->prefix . 'aioseo_posts';
            $subqueries['aioseo'] = "SELECT DISTINCT post_id FROM `{$table}`
                WHERE (title != '' AND title IS NOT NULL) OR (description != '' AND description IS NOT NULL)";
        }

        // For each source, count how many of its posts overlap with each other source.
        foreach ( $subqueries as $src_key => $src_query ) {
            $overlap_with = array();
            foreach ( $subqueries as $other_key => $other_query ) {
                if ( $src_key === $other_key ) continue;

                $overlap_count = (int) $wpdb->get_var(
                    "SELECT COUNT(*) FROM ({$src_query}) AS src
                     INNER JOIN ({$other_query}) AS other ON src.post_id = other.post_id"
                );

                if ( $overlap_count > 0 ) {
                    $overlap_with[ $other_key ] = $overlap_count;
                }
            }
            if ( ! empty( $overlap_with ) ) {
                $result[ $src_key ] = $overlap_with;
            }
        }

        return $result;
    }

    /**
     * Build a recommendation based on which sources are active and have data.
     *
     * @return array Recommendation metadata.
     */
    private static function build_recommendation( $yoast, $rankmath, $aioseo ) {
        $active_sources = array();
        $legacy_sources = array();

        foreach ( array( 'yoast' => $yoast, 'rankmath' => $rankmath, 'aioseo' => $aioseo ) as $key => $src ) {
            if ( ! $src['available'] ) continue;
            if ( $src['plugin_active'] ) {
                $active_sources[ $key ] = $src;
            } else {
                $legacy_sources[ $key ] = $src;
            }
        }

        $meta = array(
            'has_recommendation' => false,
            'recommended_source' => null,
            'message'            => '',
        );

        // If exactly one active source and legacy sources exist, recommend the active one.
        if ( count( $active_sources ) === 1 && count( $legacy_sources ) > 0 ) {
            $active_key  = key( $active_sources );
            $active_name = $active_sources[ $active_key ]['name'];
            $legacy_names = array();
            foreach ( $legacy_sources as $ls ) {
                $legacy_names[] = $ls['name'];
            }

            $meta['has_recommendation'] = true;
            $meta['recommended_source'] = $active_key;
            $meta['message'] = sprintf(
                '%s is your active SEO plugin. We recommend importing from %s. The %s data is likely outdated and can be cleaned up after migration.',
                $active_name,
                $active_name,
                implode( ' and ', $legacy_names )
            );
        }

        return $meta;
    }
}
