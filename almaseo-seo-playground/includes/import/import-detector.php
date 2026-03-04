<?php
/**
 * AlmaSEO Import Detector
 *
 * Detects available SEO data from competitor plugins (Yoast, Rank Math, AIOSEO)
 * even if those plugins are deactivated — checks meta keys and tables directly.
 *
 * @package AlmaSEO
 * @since   8.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Import_Detector {

    /**
     * Detect all available data sources.
     *
     * @return array Keyed by source slug.
     */
    public static function detect_all() {
        return array(
            'yoast'    => self::detect_yoast(),
            'rankmath' => self::detect_rankmath(),
            'aioseo'   => self::detect_aioseo(),
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
}
