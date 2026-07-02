<?php
/**
 * AlmaSEO RSS Feed Content Controls
 *
 * Adds configurable content before/after RSS feed items to prevent
 * content scraping and add attribution links.
 *
 * @package AlmaSEO
 * @since   8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_RSS_Controls {

    const OPTION_KEY = 'almaseo_rss_settings';

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_filter( 'the_content_feed', array( __CLASS__, 'filter_feed_content' ), 10 );
        add_filter( 'the_excerpt_rss', array( __CLASS__, 'filter_feed_content' ), 10 );
    }

    /**
     * Get stored RSS settings.
     *
     * @return array
     */
    public static function get_settings() {
        return get_option( self::OPTION_KEY, self::get_defaults() );
    }

    /**
     * Default settings.
     *
     * @return array
     */
    public static function get_defaults() {
        return array(
            'before_content' => '',
            'after_content'  => '',
        );
    }

    /**
     * Get available RSS smart tags with descriptions.
     *
     * @return array
     */
    public static function get_available_tags() {
        return array(
            '%%post_link%%'  => __( 'Linked post title (anchor tag)', 'almaseo-seo-playground' ),
            '%%post_title%%' => __( 'Post title (plain text)', 'almaseo-seo-playground' ),
            '%%post_url%%'   => __( 'Post URL', 'almaseo-seo-playground' ),
            '%%blog_link%%'  => __( 'Linked blog name (anchor tag)', 'almaseo-seo-playground' ),
            '%%blog_name%%'  => __( 'Blog name (plain text)', 'almaseo-seo-playground' ),
            '%%blog_url%%'   => __( 'Blog URL', 'almaseo-seo-playground' ),
            '%%author%%'     => __( 'Post author name', 'almaseo-seo-playground' ),
        );
    }

    /**
     * Filter RSS feed content to prepend/append custom text.
     *
     * @param string $content Feed content.
     * @return string Modified content.
     */
    public static function filter_feed_content( $content ) {
        $settings = self::get_settings();

        $before = isset( $settings['before_content'] ) ? trim( $settings['before_content'] ) : '';
        $after  = isset( $settings['after_content'] ) ? trim( $settings['after_content'] ) : '';

        if ( ! empty( $before ) ) {
            $content = '<p>' . self::replace_rss_tags( $before ) . '</p>' . "\n" . $content;
        }

        if ( ! empty( $after ) ) {
            $content = $content . "\n" . '<p>' . self::replace_rss_tags( $after ) . '</p>';
        }

        return $content;
    }

    /**
     * Replace RSS-specific smart tags.
     *
     * @param string $template Template string.
     * @return string Resolved string.
     */
    private static function replace_rss_tags( $template ) {
        global $post;

        if ( ! $post ) {
            return $template;
        }

        $replacements = array(
            '%%post_link%%'  => '<a href="' . esc_url( get_permalink( $post ) ) . '">' . esc_html( get_the_title( $post ) ) . '</a>',
            '%%post_title%%' => esc_html( get_the_title( $post ) ),
            '%%post_url%%'   => esc_url( get_permalink( $post ) ),
            '%%blog_link%%'  => '<a href="' . esc_url( home_url( '/' ) ) . '">' . esc_html( get_bloginfo( 'name' ) ) . '</a>',
            '%%blog_name%%'  => esc_html( get_bloginfo( 'name' ) ),
            '%%blog_url%%'   => esc_url( home_url( '/' ) ),
            '%%author%%'     => esc_html( get_the_author() ),
        );

        return str_replace( array_keys( $replacements ), array_values( $replacements ), $template );
    }

    /**
     * Sanitize RSS settings.
     *
     * @param array $input Raw input.
     * @return array Sanitized.
     */
    public static function sanitize( $input ) {
        if ( ! is_array( $input ) ) {
            return self::get_defaults();
        }

        return array(
            'before_content' => isset( $input['before_content'] ) ? wp_kses_post( $input['before_content'] ) : '',
            'after_content'  => isset( $input['after_content'] ) ? wp_kses_post( $input['after_content'] ) : '',
        );
    }
}
