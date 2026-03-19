<?php
/**
 * AlmaSEO Import Settings Mapper
 *
 * Maps global SEO settings (title templates, separator, noindex flags,
 * social profiles) from Yoast, Rank Math, and AIOSEO to AlmaSEO's
 * Search Appearance settings format.
 *
 * @package AlmaSEO
 * @since   8.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Import_Settings_Mapper {

    /**
     * Detect which sources have global settings available.
     *
     * @return array Keyed by source slug.
     */
    public static function detect_all() {
        return array(
            'yoast'    => self::detect_yoast_settings(),
            'rankmath' => self::detect_rankmath_settings(),
            'aioseo'   => self::detect_aioseo_settings(),
        );
    }

    /**
     * Import global settings from a source into AlmaSEO Search Appearance.
     *
     * @param string $source    'yoast', 'rankmath', or 'aioseo'.
     * @param bool   $overwrite Whether to overwrite non-default AlmaSEO settings.
     * @return array|WP_Error Import result.
     */
    public static function import( $source, $overwrite = false ) {
        $method = 'map_' . $source . '_settings';
        if ( ! method_exists( __CLASS__, $method ) ) {
            return new WP_Error( 'invalid_source', __( 'Unknown settings source.', 'almaseo' ) );
        }

        $mapped = self::$method();
        if ( empty( $mapped ) ) {
            return array( 'imported' => 0, 'skipped' => 0, 'message' => __( 'No settings found to import.', 'almaseo' ) );
        }

        $current  = get_option( 'almaseo_search_appearance', array() );
        $defaults = class_exists( 'AlmaSEO_Search_Appearance_Settings' )
            ? AlmaSEO_Search_Appearance_Settings::get_defaults()
            : array();

        $imported = 0;
        $skipped  = 0;
        $merged   = $current;

        // Merge mapped settings into current, respecting overwrite flag.
        $merged = self::merge_settings( $merged, $mapped, $defaults, $overwrite, $imported, $skipped );

        update_option( 'almaseo_search_appearance', $merged );

        return array(
            'imported' => $imported,
            'skipped'  => $skipped,
        );
    }

    /* ------------------------------------------------------------------
     *  Detection
     * ----------------------------------------------------------------*/

    private static function detect_yoast_settings() {
        $titles = get_option( 'wpseo_titles', array() );
        $social = get_option( 'wpseo_social', array() );
        return array(
            'name'          => 'Yoast SEO',
            'available'     => ! empty( $titles ),
            'plugin_active' => defined( 'WPSEO_VERSION' ),
            'has_social'    => ! empty( $social ),
        );
    }

    private static function detect_rankmath_settings() {
        $titles = get_option( 'rank-math-options-titles', array() );
        return array(
            'name'          => 'Rank Math',
            'available'     => ! empty( $titles ),
            'plugin_active' => class_exists( 'RankMath' ),
        );
    }

    private static function detect_aioseo_settings() {
        $options = get_option( 'aioseo_options', '' );
        if ( is_string( $options ) && ! empty( $options ) ) {
            $options = json_decode( $options, true );
        }
        return array(
            'name'          => 'All in One SEO',
            'available'     => is_array( $options ) && ! empty( $options ),
            'plugin_active' => defined( 'AIOSEO_VERSION' ),
        );
    }

    /* ------------------------------------------------------------------
     *  Yoast SEO → AlmaSEO
     * ----------------------------------------------------------------*/

    private static function map_yoast_settings() {
        $titles = get_option( 'wpseo_titles', array() );
        if ( empty( $titles ) ) {
            return array();
        }

        $mapped = array();

        // Separator.
        if ( ! empty( $titles['separator'] ) ) {
            $sep_map = array(
                'sc-dash'   => '-',
                'sc-ndash'  => "\xE2\x80\x93",
                'sc-mdash'  => "\xE2\x80\x94",
                'sc-pipe'   => '|',
                'sc-star'   => '*',
                'sc-tilde'  => '~',
                'sc-laquo'  => "\xC2\xAB",
                'sc-raquo'  => "\xC2\xBB",
                'sc-bull'   => "\xE2\x80\xA2",
                'sc-gt'     => '>',
            );
            $sep_key = $titles['separator'];
            if ( isset( $sep_map[ $sep_key ] ) ) {
                $mapped['separator'] = $sep_map[ $sep_key ];
            }
        }

        // Post type templates.
        $mapped['post_types'] = array();
        $public_types = get_post_types( array( 'public' => true ), 'names' );
        unset( $public_types['attachment'] );

        foreach ( $public_types as $pt ) {
            $pt_settings = array();
            if ( ! empty( $titles[ 'title-' . $pt ] ) ) {
                $pt_settings['title_template'] = self::convert_yoast_tags( $titles[ 'title-' . $pt ] );
            }
            if ( ! empty( $titles[ 'metadesc-' . $pt ] ) ) {
                $pt_settings['description_template'] = self::convert_yoast_tags( $titles[ 'metadesc-' . $pt ] );
            }
            if ( isset( $titles[ 'noindex-' . $pt ] ) ) {
                $pt_settings['noindex'] = (bool) $titles[ 'noindex-' . $pt ];
            }
            if ( ! empty( $pt_settings ) ) {
                $mapped['post_types'][ $pt ] = $pt_settings;
            }
        }

        // Taxonomy templates.
        $mapped['taxonomies'] = array();
        $public_taxes = get_taxonomies( array( 'public' => true, 'show_ui' => true ), 'names' );

        foreach ( $public_taxes as $tax ) {
            $tax_settings = array();
            if ( ! empty( $titles[ 'title-tax-' . $tax ] ) ) {
                $tax_settings['title_template'] = self::convert_yoast_tags( $titles[ 'title-tax-' . $tax ] );
            }
            if ( ! empty( $titles[ 'metadesc-tax-' . $tax ] ) ) {
                $tax_settings['description_template'] = self::convert_yoast_tags( $titles[ 'metadesc-tax-' . $tax ] );
            }
            if ( isset( $titles[ 'noindex-tax-' . $tax ] ) ) {
                $tax_settings['noindex'] = (bool) $titles[ 'noindex-tax-' . $tax ];
            }
            if ( ! empty( $tax_settings ) ) {
                $mapped['taxonomies'][ $tax ] = $tax_settings;
            }
        }

        // Special pages.
        $mapped['special'] = array();

        // Homepage.
        $home = array();
        if ( ! empty( $titles['title-home-wpseo'] ) ) {
            $home['title_template'] = self::convert_yoast_tags( $titles['title-home-wpseo'] );
        }
        if ( ! empty( $titles['metadesc-home-wpseo'] ) ) {
            $home['description_template'] = self::convert_yoast_tags( $titles['metadesc-home-wpseo'] );
        }
        if ( ! empty( $home ) ) {
            $mapped['special']['homepage'] = $home;
        }

        // Search page.
        if ( ! empty( $titles['title-search-wpseo'] ) ) {
            $mapped['special']['search'] = array(
                'title_template' => self::convert_yoast_tags( $titles['title-search-wpseo'] ),
            );
        }

        // 404 page.
        if ( ! empty( $titles['title-404-wpseo'] ) ) {
            $mapped['special']['error_404'] = array(
                'title_template' => self::convert_yoast_tags( $titles['title-404-wpseo'] ),
            );
        }

        // Archives.
        $mapped['archives'] = array();
        if ( ! empty( $titles['title-author-wpseo'] ) ) {
            $author = array( 'title_template' => self::convert_yoast_tags( $titles['title-author-wpseo'] ) );
            if ( ! empty( $titles['metadesc-author-wpseo'] ) ) {
                $author['description_template'] = self::convert_yoast_tags( $titles['metadesc-author-wpseo'] );
            }
            if ( isset( $titles['noindex-author-wpseo'] ) ) {
                $author['noindex'] = (bool) $titles['noindex-author-wpseo'];
            }
            $mapped['archives']['author'] = $author;
        }
        if ( ! empty( $titles['title-archive-wpseo'] ) ) {
            $date = array( 'title_template' => self::convert_yoast_tags( $titles['title-archive-wpseo'] ) );
            if ( isset( $titles['noindex-archive-wpseo'] ) ) {
                $date['noindex'] = (bool) $titles['noindex-archive-wpseo'];
            }
            $mapped['archives']['date'] = $date;
        }

        // Attachments.
        if ( isset( $titles['disable-attachment'] ) ) {
            $mapped['attachments'] = array(
                'redirect_to_parent' => (bool) $titles['disable-attachment'],
                'noindex'            => true,
            );
        }

        return $mapped;
    }

    /**
     * Convert Yoast template variables to AlmaSEO smart tags.
     *
     * @param string $template Yoast template string.
     * @return string AlmaSEO template string.
     */
    private static function convert_yoast_tags( $template ) {
        $replacements = array(
            '%%title%%'          => '%%title%%',
            '%%sitename%%'       => '%%sitename%%',
            '%%sitedesc%%'       => '%%sitetagline%%',
            '%%sep%%'            => '%%sep%%',
            '%%excerpt%%'        => '%%excerpt%%',
            '%%excerpt_only%%'   => '%%excerpt%%',
            '%%date%%'           => '%%date%%',
            '%%modified%%'       => '%%modified%%',
            '%%name%%'           => '%%author%%',
            '%%category%%'       => '%%category%%',
            '%%tag%%'            => '%%tag%%',
            '%%term_title%%'     => '%%term_title%%',
            '%%term_description%%' => '%%term_description%%',
            '%%searchphrase%%'   => '%%searchphrase%%',
            '%%page%%'           => '%%page%%',
            '%%pagetotal%%'      => '%%pagetotal%%',
            '%%pagenumber%%'     => '%%pagenumber%%',
            '%%primary_category%%' => '%%category%%',
            '%%id%%'             => '%%id%%',
            '%%post_year%%'      => '%%year%%',
            '%%currentyear%%'    => '%%currentyear%%',
        );

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
    }

    /* ------------------------------------------------------------------
     *  Rank Math → AlmaSEO
     * ----------------------------------------------------------------*/

    private static function map_rankmath_settings() {
        $titles = get_option( 'rank-math-options-titles', array() );
        if ( empty( $titles ) ) {
            return array();
        }

        $mapped = array();

        // Separator.
        if ( ! empty( $titles['title_separator'] ) ) {
            $mapped['separator'] = sanitize_text_field( $titles['title_separator'] );
        }

        // Post type templates.
        $mapped['post_types'] = array();
        $public_types = get_post_types( array( 'public' => true ), 'names' );
        unset( $public_types['attachment'] );

        foreach ( $public_types as $pt ) {
            $pt_settings = array();
            if ( ! empty( $titles[ 'pt_' . $pt . '_title' ] ) ) {
                $pt_settings['title_template'] = self::convert_rankmath_tags( $titles[ 'pt_' . $pt . '_title' ] );
            }
            if ( ! empty( $titles[ 'pt_' . $pt . '_description' ] ) ) {
                $pt_settings['description_template'] = self::convert_rankmath_tags( $titles[ 'pt_' . $pt . '_description' ] );
            }
            if ( isset( $titles[ 'pt_' . $pt . '_custom_robots' ] ) && $titles[ 'pt_' . $pt . '_custom_robots' ] ) {
                $robots = isset( $titles[ 'pt_' . $pt . '_robots' ] ) ? $titles[ 'pt_' . $pt . '_robots' ] : array();
                $pt_settings['noindex'] = is_array( $robots ) && in_array( 'noindex', $robots, true );
            }
            if ( ! empty( $pt_settings ) ) {
                $mapped['post_types'][ $pt ] = $pt_settings;
            }
        }

        // Taxonomy templates.
        $mapped['taxonomies'] = array();
        $public_taxes = get_taxonomies( array( 'public' => true, 'show_ui' => true ), 'names' );

        foreach ( $public_taxes as $tax ) {
            $tax_settings = array();
            if ( ! empty( $titles[ 'tax_' . $tax . '_title' ] ) ) {
                $tax_settings['title_template'] = self::convert_rankmath_tags( $titles[ 'tax_' . $tax . '_title' ] );
            }
            if ( ! empty( $titles[ 'tax_' . $tax . '_description' ] ) ) {
                $tax_settings['description_template'] = self::convert_rankmath_tags( $titles[ 'tax_' . $tax . '_description' ] );
            }
            if ( isset( $titles[ 'tax_' . $tax . '_custom_robots' ] ) && $titles[ 'tax_' . $tax . '_custom_robots' ] ) {
                $robots = isset( $titles[ 'tax_' . $tax . '_robots' ] ) ? $titles[ 'tax_' . $tax . '_robots' ] : array();
                $tax_settings['noindex'] = is_array( $robots ) && in_array( 'noindex', $robots, true );
            }
            if ( ! empty( $tax_settings ) ) {
                $mapped['taxonomies'][ $tax ] = $tax_settings;
            }
        }

        // Homepage.
        $mapped['special'] = array();
        $home = array();
        if ( ! empty( $titles['homepage_title'] ) ) {
            $home['title_template'] = self::convert_rankmath_tags( $titles['homepage_title'] );
        }
        if ( ! empty( $titles['homepage_description'] ) ) {
            $home['description_template'] = self::convert_rankmath_tags( $titles['homepage_description'] );
        }
        if ( ! empty( $home ) ) {
            $mapped['special']['homepage'] = $home;
        }

        // Author archives.
        $mapped['archives'] = array();
        if ( ! empty( $titles['author_archive_title'] ) ) {
            $mapped['archives']['author'] = array(
                'title_template' => self::convert_rankmath_tags( $titles['author_archive_title'] ),
            );
            if ( ! empty( $titles['author_archive_description'] ) ) {
                $mapped['archives']['author']['description_template'] = self::convert_rankmath_tags( $titles['author_archive_description'] );
            }
        }

        // Date archives.
        if ( ! empty( $titles['date_archive_title'] ) ) {
            $mapped['archives']['date'] = array(
                'title_template' => self::convert_rankmath_tags( $titles['date_archive_title'] ),
            );
        }

        // 404.
        if ( ! empty( $titles['404_title'] ) ) {
            $mapped['special']['error_404'] = array(
                'title_template' => self::convert_rankmath_tags( $titles['404_title'] ),
            );
        }

        // Search.
        if ( ! empty( $titles['search_title'] ) ) {
            $mapped['special']['search'] = array(
                'title_template' => self::convert_rankmath_tags( $titles['search_title'] ),
            );
        }

        return $mapped;
    }

    /**
     * Convert Rank Math template variables to AlmaSEO smart tags.
     *
     * @param string $template Rank Math template string.
     * @return string AlmaSEO template string.
     */
    private static function convert_rankmath_tags( $template ) {
        $replacements = array(
            '%title%'         => '%%title%%',
            '%sitename%'      => '%%sitename%%',
            '%sitedesc%'      => '%%sitetagline%%',
            '%sep%'           => '%%sep%%',
            '%excerpt%'       => '%%excerpt%%',
            '%date%'          => '%%date%%',
            '%modified%'      => '%%modified%%',
            '%name%'          => '%%author%%',
            '%category%'      => '%%category%%',
            '%tag%'           => '%%tag%%',
            '%term%'          => '%%term_title%%',
            '%term_description%' => '%%term_description%%',
            '%search_query%'  => '%%searchphrase%%',
            '%page%'          => '%%page%%',
            '%count%'         => '%%pagetotal%%',
            '%id%'            => '%%id%%',
            '%currentyear%'   => '%%currentyear%%',
        );

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
    }

    /* ------------------------------------------------------------------
     *  AIOSEO → AlmaSEO
     * ----------------------------------------------------------------*/

    private static function map_aioseo_settings() {
        $options_raw = get_option( 'aioseo_options', '' );
        if ( is_string( $options_raw ) ) {
            $options = json_decode( $options_raw, true );
        } else {
            $options = $options_raw;
        }

        if ( ! is_array( $options ) || empty( $options ) ) {
            return array();
        }

        $mapped = array();

        // Separator.
        if ( ! empty( $options['searchAppearance']['global']['separator'] ) ) {
            $mapped['separator'] = sanitize_text_field( $options['searchAppearance']['global']['separator'] );
        }

        // Post type templates.
        $mapped['post_types'] = array();
        $postTypes = isset( $options['searchAppearance']['postTypes'] ) ? $options['searchAppearance']['postTypes'] : array();

        foreach ( $postTypes as $pt => $settings ) {
            $pt_settings = array();
            if ( ! empty( $settings['title'] ) ) {
                $pt_settings['title_template'] = self::convert_aioseo_tags( $settings['title'] );
            }
            if ( ! empty( $settings['metaDescription'] ) ) {
                $pt_settings['description_template'] = self::convert_aioseo_tags( $settings['metaDescription'] );
            }
            if ( isset( $settings['advanced']['robotsMeta']['noindex'] ) ) {
                $pt_settings['noindex'] = (bool) $settings['advanced']['robotsMeta']['noindex'];
            }
            if ( ! empty( $pt_settings ) ) {
                $mapped['post_types'][ $pt ] = $pt_settings;
            }
        }

        // Taxonomy templates.
        $mapped['taxonomies'] = array();
        $taxonomies = isset( $options['searchAppearance']['taxonomies'] ) ? $options['searchAppearance']['taxonomies'] : array();

        foreach ( $taxonomies as $tax => $settings ) {
            $tax_settings = array();
            if ( ! empty( $settings['title'] ) ) {
                $tax_settings['title_template'] = self::convert_aioseo_tags( $settings['title'] );
            }
            if ( ! empty( $settings['metaDescription'] ) ) {
                $tax_settings['description_template'] = self::convert_aioseo_tags( $settings['metaDescription'] );
            }
            if ( isset( $settings['advanced']['robotsMeta']['noindex'] ) ) {
                $tax_settings['noindex'] = (bool) $settings['advanced']['robotsMeta']['noindex'];
            }
            if ( ! empty( $tax_settings ) ) {
                $mapped['taxonomies'][ $tax ] = $tax_settings;
            }
        }

        // Homepage.
        $mapped['special'] = array();
        $home_title = isset( $options['searchAppearance']['global']['homeTitle'] )
            ? $options['searchAppearance']['global']['homeTitle'] : '';
        $home_desc  = isset( $options['searchAppearance']['global']['homeDescription'] )
            ? $options['searchAppearance']['global']['homeDescription'] : '';

        if ( $home_title || $home_desc ) {
            $homepage = array();
            if ( $home_title ) {
                $homepage['title_template'] = self::convert_aioseo_tags( $home_title );
            }
            if ( $home_desc ) {
                $homepage['description_template'] = self::convert_aioseo_tags( $home_desc );
            }
            $mapped['special']['homepage'] = $homepage;
        }

        // Archives.
        $archives = isset( $options['searchAppearance']['archives'] ) ? $options['searchAppearance']['archives'] : array();
        $mapped['archives'] = array();

        if ( ! empty( $archives['author']['title'] ) ) {
            $mapped['archives']['author'] = array(
                'title_template' => self::convert_aioseo_tags( $archives['author']['title'] ),
            );
            if ( ! empty( $archives['author']['metaDescription'] ) ) {
                $mapped['archives']['author']['description_template'] = self::convert_aioseo_tags( $archives['author']['metaDescription'] );
            }
        }
        if ( ! empty( $archives['date']['title'] ) ) {
            $mapped['archives']['date'] = array(
                'title_template' => self::convert_aioseo_tags( $archives['date']['title'] ),
            );
        }

        return $mapped;
    }

    /**
     * Convert AIOSEO template variables to AlmaSEO smart tags.
     *
     * @param string $template AIOSEO template string.
     * @return string AlmaSEO template string.
     */
    private static function convert_aioseo_tags( $template ) {
        $replacements = array(
            '#post_title'          => '%%title%%',
            '#site_title'          => '%%sitename%%',
            '#tagline'             => '%%sitetagline%%',
            '#separator_sa'        => '%%sep%%',
            '#post_excerpt'        => '%%excerpt%%',
            '#post_date'           => '%%date%%',
            '#post_modified_date'  => '%%modified%%',
            '#author_name'         => '%%author%%',
            '#taxonomy_title'      => '%%term_title%%',
            '#taxonomy_description' => '%%term_description%%',
            '#search_term'         => '%%searchphrase%%',
            '#post_id'             => '%%id%%',
            '#current_year'        => '%%currentyear%%',
            '#category'            => '%%category%%',
        );

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
    }

    /* ------------------------------------------------------------------
     *  Settings merge helper
     * ----------------------------------------------------------------*/

    /**
     * Recursively merge mapped settings into current settings.
     *
     * @param array $current   Current AlmaSEO settings.
     * @param array $mapped    Mapped source settings.
     * @param array $defaults  AlmaSEO defaults (to detect if current was customized).
     * @param bool  $overwrite Whether to overwrite non-default values.
     * @param int   &$imported Counter for imported values.
     * @param int   &$skipped  Counter for skipped values.
     * @return array Merged settings.
     */
    private static function merge_settings( $current, $mapped, $defaults, $overwrite, &$imported, &$skipped ) {
        foreach ( $mapped as $key => $value ) {
            if ( is_array( $value ) && isset( $current[ $key ] ) && is_array( $current[ $key ] ) ) {
                $def = isset( $defaults[ $key ] ) && is_array( $defaults[ $key ] ) ? $defaults[ $key ] : array();
                $current[ $key ] = self::merge_settings( $current[ $key ], $value, $def, $overwrite, $imported, $skipped );
            } else {
                // Check if current value differs from default (i.e., user customized it).
                $current_val = isset( $current[ $key ] ) ? $current[ $key ] : null;
                $default_val = isset( $defaults[ $key ] ) ? $defaults[ $key ] : null;
                $is_customized = ( $current_val !== null && $current_val !== $default_val );

                if ( $is_customized && ! $overwrite ) {
                    $skipped++;
                } else {
                    $current[ $key ] = $value;
                    $imported++;
                }
            }
        }
        return $current;
    }
}
