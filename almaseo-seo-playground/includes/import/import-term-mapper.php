<?php
/**
 * AlmaSEO Import Term Meta Mapper
 *
 * Imports SEO meta from categories, tags, and custom taxonomy terms
 * stored by Yoast, Rank Math, and AIOSEO.
 *
 * @package AlmaSEO
 * @since   8.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Import_Term_Mapper {

    const BATCH_SIZE = 100;

    /**
     * Detect available taxonomy term SEO data per source.
     *
     * @return array Keyed by source slug.
     */
    public static function detect_all() {
        global $wpdb;

        // Yoast stores taxonomy meta in wpseo_taxonomy_meta option.
        $yoast_meta = get_option( 'wpseo_taxonomy_meta', array() );
        $yoast_count = 0;
        if ( is_array( $yoast_meta ) ) {
            foreach ( $yoast_meta as $tax => $terms ) {
                if ( is_array( $terms ) ) {
                    $yoast_count += count( $terms );
                }
            }
        }

        // Rank Math stores term meta in termmeta table.
        $rm_count = (int) $wpdb->get_var(
            "SELECT COUNT(DISTINCT term_id) FROM {$wpdb->termmeta}
             WHERE meta_key IN ('rank_math_title', 'rank_math_description')
             AND meta_value != ''"
        );

        // AIOSEO stores term meta in termmeta table too (or a custom table).
        $aioseo_count = 0;
        $aioseo_table = $wpdb->prefix . 'aioseo_terms';
        $table_exists  = (bool) $wpdb->get_var(
            $wpdb->prepare( "SHOW TABLES LIKE %s", $aioseo_table )
        );
        if ( $table_exists ) {
            $aioseo_count = (int) $wpdb->get_var(
                "SELECT COUNT(*) FROM `{$aioseo_table}` WHERE (title != '' AND title IS NOT NULL) OR (description != '' AND description IS NOT NULL)"
            );
        }

        return array(
            'yoast'    => array( 'name' => 'Yoast SEO', 'available' => $yoast_count > 0, 'plugin_active' => defined( 'WPSEO_VERSION' ), 'record_count' => $yoast_count ),
            'rankmath' => array( 'name' => 'Rank Math', 'available' => $rm_count > 0, 'plugin_active' => class_exists( 'RankMath' ), 'record_count' => $rm_count ),
            'aioseo'   => array( 'name' => 'All in One SEO', 'available' => $aioseo_count > 0, 'plugin_active' => defined( 'AIOSEO_VERSION' ), 'record_count' => $aioseo_count ),
        );
    }

    /**
     * Process one batch of term meta imports.
     *
     * @param string $source    'yoast', 'rankmath', or 'aioseo'.
     * @param int    $offset    Current offset.
     * @param bool   $overwrite Whether to overwrite existing AlmaSEO term meta.
     * @return array|WP_Error Result.
     */
    public static function process_batch( $source, $offset = 0, $overwrite = false ) {
        $method = 'get_batch_' . $source;
        if ( ! method_exists( __CLASS__, $method ) ) {
            return new WP_Error( 'invalid_source', __( 'Unknown import source.', 'almaseo-seo-playground' ) );
        }

        $rows = self::$method( $offset, self::BATCH_SIZE );

        $imported  = 0;
        $skipped   = 0;
        $processed = 0;
        $not_found = 0;
        $empty     = 0;

        foreach ( $rows as $row ) {
            $term_id = (int) $row['term_id'];
            $processed++;

            // Verify term still exists.
            $term = get_term( $term_id );
            if ( ! $term || is_wp_error( $term ) ) {
                $not_found++;
                continue;
            }

            $meta_map = array(
                'title'       => '_almaseo_term_title',
                'description' => '_almaseo_term_description',
                'canonical'   => '_almaseo_term_canonical',
                'noindex'     => '_almaseo_term_noindex',
                'og_title'    => '_almaseo_term_og_title',
                'og_desc'     => '_almaseo_term_og_description',
            );

            // Fields that can contain template variables (not noindex/canonical).
            $template_fields = array( 'title', 'description', 'og_title', 'og_desc' );

            $row_had_data = false;
            foreach ( $meta_map as $key => $meta_key ) {
                if ( empty( $row[ $key ] ) ) {
                    continue;
                }

                $value = $row[ $key ];

                // Convert + resolve template variables for all sources.
                if ( in_array( $key, $template_fields, true ) ) {
                    if ( $source === 'aioseo' && class_exists( 'AlmaSEO_Import_Mapper_AIOSEO' ) ) {
                        if ( AlmaSEO_Import_Mapper_AIOSEO::is_default_template( $value ) ) {
                            continue;
                        }
                        $value = AlmaSEO_Import_Mapper_AIOSEO::convert_tags( $value );
                    } elseif ( $source === 'yoast' && class_exists( 'AlmaSEO_Import_Mapper_Yoast' ) ) {
                        $value = AlmaSEO_Import_Mapper_Yoast::convert_tags( $value );
                        if ( AlmaSEO_Import_Mapper_Yoast::is_default_template( $value ) ) {
                            continue;
                        }
                    } elseif ( $source === 'rankmath' && class_exists( 'AlmaSEO_Import_Mapper_RankMath' ) ) {
                        $value = AlmaSEO_Import_Mapper_RankMath::convert_tags( $value );
                        if ( AlmaSEO_Import_Mapper_RankMath::is_default_template( $value ) ) {
                            continue;
                        }
                    }

                    // Resolve %%tags%% to actual term values.
                    if ( strpos( $value, '%%' ) !== false && class_exists( 'AlmaSEO_Smart_Tags' ) ) {
                        $value = AlmaSEO_Smart_Tags::replace( $value, array( 'term' => $term ) );
                    }

                    $value = trim( $value );
                    if ( empty( $value ) ) {
                        continue;
                    }
                }

                $row_had_data = true;

                if ( ! $overwrite ) {
                    $existing = get_term_meta( $term_id, $meta_key, true );
                    if ( ! empty( $existing ) ) {
                        $skipped++;
                        continue;
                    }
                }

                update_term_meta( $term_id, $meta_key, sanitize_text_field( $value ) );
                $imported++;
            }

            if ( ! $row_had_data ) {
                $empty++;
            }
        }

        $done = count( $rows ) < self::BATCH_SIZE;

        return array(
            'processed' => $processed,
            'imported'  => $imported,
            'skipped'   => $skipped,
            'not_found' => $not_found,
            'empty'     => $empty,
            'offset'    => $offset + $processed,
            'done'      => $done,
        );
    }

    /* ------------------------------------------------------------------
     *  Yoast: term meta stored in wpseo_taxonomy_meta option
     * ----------------------------------------------------------------*/

    private static function get_batch_yoast( $offset, $limit ) {
        $all_meta = get_option( 'wpseo_taxonomy_meta', array() );
        if ( ! is_array( $all_meta ) ) {
            return array();
        }

        // Flatten into a list of rows.
        $rows = array();
        foreach ( $all_meta as $taxonomy => $terms ) {
            if ( ! is_array( $terms ) ) {
                continue;
            }
            foreach ( $terms as $term_id => $meta ) {
                if ( ! is_array( $meta ) ) {
                    continue;
                }
                $rows[] = array(
                    'term_id'     => (int) $term_id,
                    'title'       => isset( $meta['wpseo_title'] ) ? $meta['wpseo_title'] : '',
                    'description' => isset( $meta['wpseo_desc'] ) ? $meta['wpseo_desc'] : '',
                    'canonical'   => isset( $meta['wpseo_canonical'] ) ? $meta['wpseo_canonical'] : '',
                    'noindex'     => isset( $meta['wpseo_noindex'] ) && $meta['wpseo_noindex'] === 'noindex' ? '1' : '',
                    'og_title'    => isset( $meta['wpseo_opengraph-title'] ) ? $meta['wpseo_opengraph-title'] : '',
                    'og_desc'     => isset( $meta['wpseo_opengraph-description'] ) ? $meta['wpseo_opengraph-description'] : '',
                );
            }
        }

        return array_slice( $rows, $offset, $limit );
    }

    /* ------------------------------------------------------------------
     *  Rank Math: term meta in termmeta table
     * ----------------------------------------------------------------*/

    private static function get_batch_rankmath( $offset, $limit ) {
        global $wpdb;

        $term_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT DISTINCT term_id FROM {$wpdb->termmeta}
             WHERE meta_key IN ('rank_math_title', 'rank_math_description')
             AND meta_value != ''
             ORDER BY term_id ASC
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        ) );

        $rows = array();
        foreach ( $term_ids as $term_id ) {
            $rows[] = array(
                'term_id'     => (int) $term_id,
                'title'       => get_term_meta( $term_id, 'rank_math_title', true ),
                'description' => get_term_meta( $term_id, 'rank_math_description', true ),
                'canonical'   => get_term_meta( $term_id, 'rank_math_canonical_url', true ),
                'noindex'     => self::rankmath_term_has_noindex( $term_id ) ? '1' : '',
                'og_title'    => get_term_meta( $term_id, 'rank_math_facebook_title', true ),
                'og_desc'     => get_term_meta( $term_id, 'rank_math_facebook_description', true ),
            );
        }

        return $rows;
    }

    private static function rankmath_term_has_noindex( $term_id ) {
        $robots = get_term_meta( $term_id, 'rank_math_robots', true );
        if ( is_string( $robots ) ) {
            $robots = maybe_unserialize( $robots );
        }
        return is_array( $robots ) && in_array( 'noindex', $robots, true );
    }

    /* ------------------------------------------------------------------
     *  AIOSEO: term meta in aioseo_terms table
     * ----------------------------------------------------------------*/

    private static function get_batch_aioseo( $offset, $limit ) {
        global $wpdb;

        $table = $wpdb->prefix . 'aioseo_terms';
        $table_exists = (bool) $wpdb->get_var(
            $wpdb->prepare( "SHOW TABLES LIKE %s", $table )
        );

        if ( ! $table_exists ) {
            return array();
        }

        $results = $wpdb->get_results( $wpdb->prepare(
            "SELECT term_id, title, description, canonical, robots_noindex,
                    og_title, og_description
             FROM `{$table}`
             WHERE (title != '' AND title IS NOT NULL)
                OR (description != '' AND description IS NOT NULL)
             ORDER BY term_id ASC
             LIMIT %d OFFSET %d",
            $limit,
            $offset
        ), ARRAY_A );

        if ( ! $results ) {
            return array();
        }

        $rows = array();
        foreach ( $results as $row ) {
            $rows[] = array(
                'term_id'     => (int) $row['term_id'],
                'title'       => isset( $row['title'] ) ? $row['title'] : '',
                'description' => isset( $row['description'] ) ? $row['description'] : '',
                'canonical'   => isset( $row['canonical'] ) ? $row['canonical'] : '',
                'noindex'     => ( isset( $row['robots_noindex'] ) && $row['robots_noindex'] ) ? '1' : '',
                'og_title'    => isset( $row['og_title'] ) ? $row['og_title'] : '',
                'og_desc'     => isset( $row['og_description'] ) ? $row['og_description'] : '',
            );
        }

        return $rows;
    }
}
