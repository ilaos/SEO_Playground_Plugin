<?php
/**
 * AlmaSEO Search Appearance Settings
 *
 * Option registration, defaults, and sanitization for title/description
 * templates and per-content-type search visibility settings.
 *
 * @package AlmaSEO
 * @since   8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Search_Appearance_Settings {

    const OPTION_KEY = 'almaseo_search_appearance';

    /**
     * Register the option with WordPress.
     */
    public static function register() {
        register_setting( 'almaseo_search_appearance', self::OPTION_KEY, array(
            'type'              => 'array',
            'sanitize_callback' => array( __CLASS__, 'sanitize' ),
            'default'           => self::get_defaults(),
        ) );
    }

    /**
     * Get all settings, merged with defaults.
     *
     * @return array
     */
    public static function get_settings() {
        $saved    = get_option( self::OPTION_KEY, array() );
        $defaults = self::get_defaults();
        return self::deep_merge( $defaults, $saved );
    }

    /**
     * Get the separator character.
     *
     * @return string
     */
    public static function get_separator() {
        $settings = self::get_settings();
        return isset( $settings['separator'] ) ? $settings['separator'] : '-';
    }

    /**
     * Get default settings.
     *
     * @return array
     */
    public static function get_defaults() {
        return array(
            'separator'   => '-',
            'post_types'  => array(
                'post' => array(
                    'title_template'       => '%%title%% %%sep%% %%sitename%%',
                    'description_template' => '%%excerpt%%',
                    'noindex'              => false,
                ),
                'page' => array(
                    'title_template'       => '%%title%% %%sep%% %%sitename%%',
                    'description_template' => '%%excerpt%%',
                    'noindex'              => false,
                ),
            ),
            'taxonomies'  => array(
                'category' => array(
                    'title_template'       => '%%term_title%% %%sep%% %%sitename%%',
                    'description_template' => '%%term_description%%',
                    'noindex'              => false,
                ),
                'post_tag' => array(
                    'title_template'       => '%%term_title%% %%sep%% %%sitename%%',
                    'description_template' => '%%term_description%%',
                    'noindex'              => false,
                ),
            ),
            'archives'    => array(
                'author' => array(
                    'title_template'       => '%%author%% %%sep%% %%sitename%%',
                    'description_template' => '',
                    'noindex'              => false,
                ),
                'date'   => array(
                    'title_template'       => '%%date%% %%sep%% %%sitename%%',
                    'description_template' => '',
                    'noindex'              => true,
                ),
            ),
            'special'     => array(
                'search'    => array(
                    'title_template' => '%%searchphrase%% %%sep%% %%sitename%%',
                    'noindex'        => true,
                ),
                'error_404' => array(
                    'title_template' => 'Page Not Found %%sep%% %%sitename%%',
                ),
                'homepage'  => array(
                    'title_template'       => '%%sitename%% %%sep%% %%sitetagline%%',
                    'description_template' => '',
                ),
            ),
            'attachments' => array(
                'redirect_to_parent' => true,
                'noindex'            => true,
            ),
        );
    }

    /**
     * Get settings for a specific post type, merged with defaults.
     *
     * @param string $post_type Post type slug.
     * @return array
     */
    public static function get_post_type_settings( $post_type ) {
        $settings  = self::get_settings();
        $pt_default = array(
            'title_template'       => '%%title%% %%sep%% %%sitename%%',
            'description_template' => '%%excerpt%%',
            'noindex'              => false,
        );

        if ( isset( $settings['post_types'][ $post_type ] ) ) {
            return array_merge( $pt_default, $settings['post_types'][ $post_type ] );
        }

        return $pt_default;
    }

    /**
     * Get settings for a specific taxonomy, merged with defaults.
     *
     * @param string $taxonomy Taxonomy slug.
     * @return array
     */
    public static function get_taxonomy_settings( $taxonomy ) {
        $settings   = self::get_settings();
        $tax_default = array(
            'title_template'       => '%%term_title%% %%sep%% %%sitename%%',
            'description_template' => '%%term_description%%',
            'noindex'              => false,
        );

        if ( isset( $settings['taxonomies'][ $taxonomy ] ) ) {
            return array_merge( $tax_default, $settings['taxonomies'][ $taxonomy ] );
        }

        return $tax_default;
    }

    /**
     * Get all public post types for the admin UI.
     *
     * @return array Post type objects keyed by slug.
     */
    public static function get_public_post_types() {
        $types = get_post_types( array( 'public' => true ), 'objects' );
        unset( $types['attachment'] ); // Attachments handled separately.
        return $types;
    }

    /**
     * Get all public taxonomies for the admin UI.
     *
     * @return array Taxonomy objects keyed by slug.
     */
    public static function get_public_taxonomies() {
        return get_taxonomies( array( 'public' => true, 'show_ui' => true ), 'objects' );
    }

    /**
     * Available separator characters.
     *
     * @return array
     */
    public static function get_separator_options() {
        return array(
            '-'  => '-',
            '|'  => '|',
            '>'  => '>',
            '~'  => '~',
            '/'  => '/',
            '*'  => '*',
            "\xE2\x80\x93" => "\xE2\x80\x93", // en-dash
            "\xE2\x80\x94" => "\xE2\x80\x94", // em-dash
            "\xE2\x80\xA2" => "\xE2\x80\xA2", // bullet
            "\xC2\xAB"     => "\xC2\xAB",     // left guillemet
            "\xC2\xBB"     => "\xC2\xBB",     // right guillemet
        );
    }

    /**
     * Sanitize settings input.
     *
     * @param array $input Raw input.
     * @return array Sanitized settings.
     */
    public static function sanitize( $input ) {
        if ( ! is_array( $input ) ) {
            return self::get_defaults();
        }

        $sanitized = array();

        // Separator.
        $valid_seps = array_keys( self::get_separator_options() );
        $sanitized['separator'] = isset( $input['separator'] ) && in_array( $input['separator'], $valid_seps, true )
            ? $input['separator']
            : '-';

        // Post types.
        $sanitized['post_types'] = array();
        if ( isset( $input['post_types'] ) && is_array( $input['post_types'] ) ) {
            foreach ( $input['post_types'] as $pt => $settings ) {
                $pt = sanitize_key( $pt );
                $sanitized['post_types'][ $pt ] = self::sanitize_content_type_settings( $settings );
            }
        }

        // Taxonomies.
        $sanitized['taxonomies'] = array();
        if ( isset( $input['taxonomies'] ) && is_array( $input['taxonomies'] ) ) {
            foreach ( $input['taxonomies'] as $tax => $settings ) {
                $tax = sanitize_key( $tax );
                $sanitized['taxonomies'][ $tax ] = self::sanitize_content_type_settings( $settings );
            }
        }

        // Archives.
        $sanitized['archives'] = array();
        if ( isset( $input['archives'] ) && is_array( $input['archives'] ) ) {
            foreach ( array( 'author', 'date' ) as $archive_type ) {
                if ( isset( $input['archives'][ $archive_type ] ) ) {
                    $sanitized['archives'][ $archive_type ] = self::sanitize_content_type_settings(
                        $input['archives'][ $archive_type ]
                    );
                }
            }
        }

        // Special pages.
        $sanitized['special'] = array();
        if ( isset( $input['special'] ) && is_array( $input['special'] ) ) {
            foreach ( array( 'search', 'error_404', 'homepage' ) as $page_type ) {
                if ( isset( $input['special'][ $page_type ] ) ) {
                    $sanitized['special'][ $page_type ] = self::sanitize_content_type_settings(
                        $input['special'][ $page_type ]
                    );
                }
            }
        }

        // Attachments.
        $sanitized['attachments'] = array(
            'redirect_to_parent' => ! empty( $input['attachments']['redirect_to_parent'] ),
            'noindex'            => ! empty( $input['attachments']['noindex'] ),
        );

        return $sanitized;
    }

    /**
     * Sanitize a single content type's settings block.
     *
     * @param array $settings Raw settings.
     * @return array Sanitized.
     */
    private static function sanitize_content_type_settings( $settings ) {
        if ( ! is_array( $settings ) ) {
            return array();
        }

        $sanitized = array();

        if ( isset( $settings['title_template'] ) ) {
            $sanitized['title_template'] = sanitize_text_field( $settings['title_template'] );
        }
        if ( isset( $settings['description_template'] ) ) {
            $sanitized['description_template'] = sanitize_text_field( $settings['description_template'] );
        }
        if ( array_key_exists( 'noindex', $settings ) ) {
            $sanitized['noindex'] = ! empty( $settings['noindex'] );
        }

        return $sanitized;
    }

    /**
     * Deep merge two arrays (second overwrites first).
     *
     * @param array $defaults Default values.
     * @param array $saved    Saved values.
     * @return array Merged.
     */
    private static function deep_merge( $defaults, $saved ) {
        $merged = $defaults;
        foreach ( $saved as $key => $value ) {
            if ( is_array( $value ) && isset( $merged[ $key ] ) && is_array( $merged[ $key ] ) ) {
                $merged[ $key ] = self::deep_merge( $merged[ $key ], $value );
            } else {
                $merged[ $key ] = $value;
            }
        }
        return $merged;
    }
}
