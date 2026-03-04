<?php
/**
 * AlmaSEO LLMs.txt Auto-Generator
 *
 * Generates llms.txt content from published site content.
 *
 * @package AlmaSEO
 * @since   8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_LLMS_Txt_Generator {

    /**
     * Generate llms.txt content from site data.
     *
     * @param array $options Optional overrides: include_post_types, max_entries.
     * @return string Generated content.
     */
    public static function generate( $options = array() ) {
        $max_entries = isset( $options['max_entries'] ) ? (int) $options['max_entries'] : 50;
        $post_types  = isset( $options['include_post_types'] )
            ? $options['include_post_types']
            : array( 'page', 'post' );

        $lines = array();

        // Header.
        $lines[] = '# ' . get_bloginfo( 'name' );
        $lines[] = '';
        $lines[] = '> ' . get_bloginfo( 'description' );
        $lines[] = '';

        // Site info.
        $lines[] = 'URL: ' . home_url( '/' );
        $lines[] = '';

        // Content sections.
        foreach ( $post_types as $pt ) {
            $pt_obj = get_post_type_object( $pt );
            if ( ! $pt_obj ) {
                continue;
            }

            $posts = get_posts( array(
                'post_type'      => $pt,
                'post_status'    => 'publish',
                'posts_per_page' => $max_entries,
                'orderby'        => 'date',
                'order'          => 'DESC',
            ) );

            if ( empty( $posts ) ) {
                continue;
            }

            $lines[] = '## ' . $pt_obj->labels->name;
            $lines[] = '';

            foreach ( $posts as $post ) {
                $desc = get_post_meta( $post->ID, '_almaseo_description', true );
                if ( empty( $desc ) ) {
                    $desc = wp_trim_words( wp_strip_all_tags( $post->post_content ), 20, '...' );
                }
                $desc = str_replace( array( "\n", "\r" ), ' ', $desc );

                $lines[] = '- [' . $post->post_title . '](' . get_permalink( $post ) . '): ' . $desc;
            }

            $lines[] = '';
        }

        return implode( "\n", $lines );
    }
}
