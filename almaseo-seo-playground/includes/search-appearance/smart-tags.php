<?php
/**
 * AlmaSEO Smart Tags Parser
 *
 * Replaces %%tag%% placeholders in title/description templates with dynamic values.
 * Reusable by Search Appearance, Import, and Setup Wizard modules.
 *
 * @package AlmaSEO
 * @since   8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Smart_Tags {

    /**
     * Replace smart tags in a template string.
     *
     * @param string $template Template with %%tag%% placeholders.
     * @param array  $context  Optional contextual overrides.
     * @return string Resolved string.
     */
    public static function replace( $template, $context = array() ) {
        if ( empty( $template ) ) {
            return '';
        }

        // Quick bail if no tags present.
        if ( strpos( $template, '%%' ) === false ) {
            return $template;
        }

        $replacements = self::build_replacements( $context );

        // Replace all known tags.
        $result = str_replace(
            array_keys( $replacements ),
            array_values( $replacements ),
            $template
        );

        // Strip any remaining unresolved tags.
        $result = preg_replace( '/%%[a-z_]+%%/', '', $result );

        // Clean up double spaces left by removed tags.
        $result = preg_replace( '/\s{2,}/', ' ', trim( $result ) );

        return $result;
    }

    /**
     * Build the full replacement map for the current context.
     *
     * @param array $context Overrides: post, term, author, separator, etc.
     * @return array Tag => value pairs.
     */
    private static function build_replacements( $context = array() ) {
        $sep       = self::get_separator( $context );
        $post      = isset( $context['post'] ) ? $context['post'] : self::get_current_post();
        $term      = isset( $context['term'] ) ? $context['term'] : self::get_current_term();
        $author_id = isset( $context['author_id'] ) ? $context['author_id'] : self::get_current_author_id( $post );

        $map = array(
            '%%sep%%'              => $sep,
            '%%sitename%%'         => get_bloginfo( 'name' ),
            '%%sitetagline%%'      => get_bloginfo( 'description' ),
            '%%currentyear%%'      => gmdate( 'Y' ),
            '%%currentmonth%%'     => gmdate( 'F' ),
            '%%currentdate%%'      => gmdate( get_option( 'date_format' ) ),
        );

        // Post-related tags.
        if ( $post ) {
            $map['%%title%%']            = $post->post_title;
            $map['%%excerpt%%']          = self::get_excerpt( $post );
            $map['%%date%%']             = get_the_date( '', $post );
            $map['%%modified%%']         = get_the_modified_date( '', $post );
            $map['%%author%%']           = get_the_author_meta( 'display_name', $post->post_author );
            $map['%%author_first_name%%'] = get_the_author_meta( 'first_name', $post->post_author );
            $map['%%author_last_name%%'] = get_the_author_meta( 'last_name', $post->post_author );
            $map['%%focuskeyword%%']     = get_post_meta( $post->ID, '_almaseo_focus_keyword', true );
            $map['%%id%%']               = $post->ID;

            // Category / tag.
            $map['%%category%%']         = self::get_first_term( $post->ID, 'category' );
            $map['%%tag%%']              = self::get_first_term( $post->ID, 'post_tag' );
            $map['%%primary_category%%'] = self::get_primary_category( $post->ID );

            // Post type labels.
            $pt_obj = get_post_type_object( $post->post_type );
            if ( $pt_obj ) {
                $map['%%pt_single%%'] = $pt_obj->labels->singular_name;
                $map['%%pt_plural%%'] = $pt_obj->labels->name;
            }
        }

        // Author-specific tags (for author archives).
        if ( $author_id ) {
            $map['%%author%%']           = get_the_author_meta( 'display_name', $author_id );
            $map['%%author_first_name%%'] = get_the_author_meta( 'first_name', $author_id );
            $map['%%author_last_name%%'] = get_the_author_meta( 'last_name', $author_id );
        }

        // Term/taxonomy tags.
        if ( $term ) {
            $map['%%term_title%%']       = $term->name;
            $map['%%term_description%%'] = $term->description;
            $map['%%title%%']            = $term->name; // %%title%% falls back to term name on archives.
        }

        // Search query.
        if ( is_search() || isset( $context['searchphrase'] ) ) {
            $map['%%searchphrase%%'] = isset( $context['searchphrase'] )
                ? $context['searchphrase']
                : get_search_query();
        }

        // Pagination.
        $paged = max( 1, get_query_var( 'paged' ) );
        $map['%%pagenumber%%'] = $paged;
        $map['%%pagetotal%%']  = isset( $GLOBALS['wp_query'] ) ? max( 1, $GLOBALS['wp_query']->max_num_pages ) : 1;

        // Page number suffix (e.g., " - Page 2") — only on page 2+.
        $map['%%page%%'] = $paged > 1
            ? sprintf( ' - %s %d', __( 'Page', 'almaseo' ), $paged )
            : '';

        // Context overrides (highest priority).
        if ( isset( $context['title'] ) ) {
            $map['%%title%%'] = $context['title'];
        }

        return $map;
    }

    /**
     * Get separator from context or settings.
     */
    private static function get_separator( $context ) {
        if ( isset( $context['separator'] ) ) {
            return $context['separator'];
        }
        $settings = get_option( 'almaseo_search_appearance', array() );
        return isset( $settings['separator'] ) ? $settings['separator'] : '-';
    }

    /**
     * Get current post object.
     */
    private static function get_current_post() {
        if ( is_singular() ) {
            global $post;
            return $post;
        }
        return null;
    }

    /**
     * Get current term object.
     */
    private static function get_current_term() {
        if ( is_category() || is_tag() || is_tax() ) {
            return get_queried_object();
        }
        return null;
    }

    /**
     * Get current author ID.
     */
    private static function get_current_author_id( $post = null ) {
        if ( is_author() ) {
            $obj = get_queried_object();
            return $obj ? $obj->ID : 0;
        }
        if ( $post ) {
            return $post->post_author;
        }
        return 0;
    }

    /**
     * Get post excerpt, auto-generating if empty.
     */
    private static function get_excerpt( $post, $length = 160 ) {
        $excerpt = $post->post_excerpt;
        if ( empty( $excerpt ) ) {
            $excerpt = wp_strip_all_tags( strip_shortcodes( $post->post_content ) );
        }
        if ( strlen( $excerpt ) > $length ) {
            $excerpt = substr( $excerpt, 0, $length );
            $last_space = strrpos( $excerpt, ' ' );
            if ( $last_space !== false ) {
                $excerpt = substr( $excerpt, 0, $last_space );
            }
        }
        return trim( $excerpt );
    }

    /**
     * Get first term name for a taxonomy.
     */
    private static function get_first_term( $post_id, $taxonomy ) {
        $terms = get_the_terms( $post_id, $taxonomy );
        if ( is_array( $terms ) && ! empty( $terms ) ) {
            return $terms[0]->name;
        }
        return '';
    }

    /**
     * Get primary category (checks Yoast/Rank Math primary cat, then first cat).
     */
    private static function get_primary_category( $post_id ) {
        // Check Yoast primary category.
        $yoast_primary = get_post_meta( $post_id, '_yoast_wpseo_primary_category', true );
        if ( $yoast_primary ) {
            $term = get_term( (int) $yoast_primary, 'category' );
            if ( $term && ! is_wp_error( $term ) ) {
                return $term->name;
            }
        }

        // Check Rank Math primary category.
        $rm_primary = get_post_meta( $post_id, 'rank_math_primary_category', true );
        if ( $rm_primary ) {
            $term = get_term( (int) $rm_primary, 'category' );
            if ( $term && ! is_wp_error( $term ) ) {
                return $term->name;
            }
        }

        // Fall back to first category.
        return self::get_first_term( $post_id, 'category' );
    }

    /**
     * Get all available smart tags with descriptions for the admin UI reference.
     *
     * @return array Tag => description pairs.
     */
    public static function get_available_tags() {
        return array(
            '%%title%%'              => __( 'Post/page title, or term name on archives', 'almaseo' ),
            '%%sitename%%'           => __( 'Site name', 'almaseo' ),
            '%%sitetagline%%'        => __( 'Site tagline/description', 'almaseo' ),
            '%%sep%%'                => __( 'Separator character (configured in settings)', 'almaseo' ),
            '%%excerpt%%'            => __( 'Post excerpt (auto-generated if empty, max 160 chars)', 'almaseo' ),
            '%%primary_category%%'   => __( 'Primary category name', 'almaseo' ),
            '%%category%%'           => __( 'First category name', 'almaseo' ),
            '%%tag%%'                => __( 'First tag name', 'almaseo' ),
            '%%date%%'               => __( 'Post published date', 'almaseo' ),
            '%%modified%%'           => __( 'Post last modified date', 'almaseo' ),
            '%%author%%'             => __( 'Author display name', 'almaseo' ),
            '%%author_first_name%%'  => __( 'Author first name', 'almaseo' ),
            '%%author_last_name%%'   => __( 'Author last name', 'almaseo' ),
            '%%searchphrase%%'       => __( 'Search query (search results page only)', 'almaseo' ),
            '%%pagenumber%%'         => __( 'Current page number', 'almaseo' ),
            '%%pagetotal%%'          => __( 'Total number of pages', 'almaseo' ),
            '%%page%%'               => __( 'Page suffix (e.g., " - Page 2", empty on page 1)', 'almaseo' ),
            '%%pt_single%%'          => __( 'Post type singular name', 'almaseo' ),
            '%%pt_plural%%'          => __( 'Post type plural name', 'almaseo' ),
            '%%term_title%%'         => __( 'Term/taxonomy name (on archive pages)', 'almaseo' ),
            '%%term_description%%'   => __( 'Term description', 'almaseo' ),
            '%%currentyear%%'        => __( 'Current year (4 digits)', 'almaseo' ),
            '%%currentmonth%%'       => __( 'Current month name', 'almaseo' ),
            '%%focuskeyword%%'       => __( 'Focus keyword from SEO settings', 'almaseo' ),
            '%%id%%'                 => __( 'Post/page ID', 'almaseo' ),
        );
    }
}
