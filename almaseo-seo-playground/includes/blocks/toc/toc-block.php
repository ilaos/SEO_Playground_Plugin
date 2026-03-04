<?php
/**
 * AlmaSEO Table of Contents Block
 *
 * Registers the almaseo/toc Gutenberg block with server-side rendering,
 * automatic heading ID injection, and smooth-scroll frontend JS.
 *
 * @package    AlmaSEO
 * @subpackage Blocks\TOC
 * @since      8.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_TOC_Block {

    /* ==================================================================
     * Bootstrap
     * ================================================================*/

    /**
     * Hook block registration and content filter.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register' ) );
        add_filter( 'the_content', array( __CLASS__, 'inject_heading_ids' ), 5 );
    }

    /* ==================================================================
     * Block Registration
     * ================================================================*/

    /**
     * Register the almaseo/toc block and its assets.
     */
    public static function register() {

        self::enqueue_assets();

        register_block_type( 'almaseo/toc', array(
            'api_version'     => 2,
            'editor_script'   => 'almaseo-toc-block-editor',
            'editor_style'    => 'almaseo-toc-block-editor-css',
            'style'           => 'almaseo-toc-block-css',
            'script'          => 'almaseo-toc-block-frontend',
            'render_callback' => array( __CLASS__, 'render' ),
            'attributes'      => array(
                'title'         => array(
                    'type'    => 'string',
                    'default' => 'Table of Contents',
                ),
                'headingLevels' => array(
                    'type'    => 'array',
                    'default' => array( 2, 3 ),
                    'items'   => array( 'type' => 'integer' ),
                ),
                'listStyle'     => array(
                    'type'    => 'string',
                    'default' => 'ol',
                ),
                'collapsible'   => array(
                    'type'    => 'boolean',
                    'default' => false,
                ),
            ),
        ) );
    }

    /* ==================================================================
     * Asset Registration
     * ================================================================*/

    /**
     * Register (but do not enqueue) editor + frontend assets.
     *
     * WordPress will enqueue them automatically when the block is used.
     */
    public static function enqueue_assets() {

        $url = defined( 'ALMASEO_URL' ) ? ALMASEO_URL : plugin_dir_url( dirname( __DIR__, 2 ) );
        $ver = defined( 'ALMASEO_PLUGIN_VERSION' ) ? ALMASEO_PLUGIN_VERSION : '8.3.0';

        /* Editor JS */
        wp_register_script(
            'almaseo-toc-block-editor',
            $url . 'assets/js/toc-block-editor.js',
            array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
            $ver,
            true
        );

        /* Editor CSS */
        wp_register_style(
            'almaseo-toc-block-editor-css',
            $url . 'assets/css/toc-block-editor.css',
            array(),
            $ver
        );

        /* Frontend CSS */
        wp_register_style(
            'almaseo-toc-block-css',
            $url . 'assets/css/toc-block.css',
            array(),
            $ver
        );

        /* Frontend JS */
        wp_register_script(
            'almaseo-toc-block-frontend',
            $url . 'assets/js/toc-block-frontend.js',
            array(),
            $ver,
            true  // in footer
        );
    }

    /* ==================================================================
     * Server-Side Render
     * ================================================================*/

    /**
     * Render callback for the almaseo/toc block.
     *
     * Parses headings from the current post content, filters by the
     * chosen heading levels, generates slug IDs, and builds a nested
     * (hierarchical) ordered or unordered list.
     *
     * @param  array  $attributes Block attributes.
     * @param  string $content    Block inner content (unused — SSR).
     * @return string             HTML output.
     */
    public static function render( $attributes, $content ) {

        /* ── Defaults ─────────────────────────────────────────────── */
        $title          = isset( $attributes['title'] )         ? $attributes['title']         : __( 'Table of Contents', 'almaseo' );
        $heading_levels = isset( $attributes['headingLevels'] ) ? $attributes['headingLevels'] : array( 2, 3 );
        $list_style     = isset( $attributes['listStyle'] )     ? $attributes['listStyle']     : 'ol';
        $collapsible    = ! empty( $attributes['collapsible'] );

        /* Normalise heading levels to integers */
        $heading_levels = array_map( 'intval', (array) $heading_levels );

        /* ── Retrieve post content ────────────────────────────────── */
        global $post;
        $post_content = '';

        if ( $post instanceof WP_Post ) {
            $post_content = $post->post_content;
        }

        if ( empty( $post_content ) ) {
            return '';
        }

        /* ── Parse headings ───────────────────────────────────────── */
        if ( ! preg_match_all( '/<h([2-6])([^>]*)>(.*?)<\/h\1>/si', $post_content, $matches, PREG_SET_ORDER ) ) {
            return '';
        }

        /* Filter to requested levels */
        $headings = array();
        foreach ( $matches as $m ) {
            $level = (int) $m[1];
            if ( in_array( $level, $heading_levels, true ) ) {
                $text_html = $m[3]; // may contain inline HTML
                $text_raw  = wp_strip_all_tags( $text_html );
                $attrs_str = trim( $m[2] );

                /* Determine ID: use existing id attr or generate one */
                $id = '';
                if ( preg_match( '/\bid=["\']([^"\']+)["\']/i', $attrs_str, $id_match ) ) {
                    $id = $id_match[1];
                } else {
                    $id = sanitize_title( $text_raw );
                }

                $headings[] = array(
                    'level' => $level,
                    'id'    => $id,
                    'text'  => $text_raw,
                );
            }
        }

        if ( empty( $headings ) ) {
            return '';
        }

        /* ── De-duplicate IDs ─────────────────────────────────────── */
        $seen_ids = array();
        foreach ( $headings as &$h ) {
            $base = $h['id'];
            $slug = $base;
            $i    = 2;
            while ( isset( $seen_ids[ $slug ] ) ) {
                $slug = $base . '-' . $i;
                $i++;
            }
            $seen_ids[ $slug ] = true;
            $h['id'] = $slug;
        }
        unset( $h );

        /* ── Build nested list ────────────────────────────────────── */
        $tag = ( 'ul' === $list_style ) ? 'ul' : 'ol';

        $list_html = self::build_nested_list( $headings, $tag );

        /* ── Assemble wrapper ─────────────────────────────────────── */
        $title_escaped = esc_html( $title );

        if ( $collapsible ) {
            $html  = '<div class="almaseo-toc-block">';
            $html .= '<details open>';
            $html .= '<summary class="almaseo-toc-title">' . $title_escaped . '</summary>';
            $html .= $list_html;
            $html .= '</details>';
            $html .= '</div>';
        } else {
            $html  = '<div class="almaseo-toc-block">';
            $html .= '<p class="almaseo-toc-title">' . $title_escaped . '</p>';
            $html .= $list_html;
            $html .= '</div>';
        }

        return $html;
    }

    /* ==================================================================
     * Nested List Builder
     * ================================================================*/

    /**
     * Build a hierarchically nested list from a flat array of headings.
     *
     * Handles non-sequential heading levels gracefully (e.g. H2 -> H4
     * without an intervening H3 still nests correctly).
     *
     * @param  array  $headings Array of [ level, id, text ].
     * @param  string $tag      'ol' or 'ul'.
     * @return string           HTML list.
     */
    private static function build_nested_list( $headings, $tag ) {

        $html  = '';
        $stack = array(); // stack of open list levels

        $min_level = PHP_INT_MAX;
        foreach ( $headings as $h ) {
            if ( $h['level'] < $min_level ) {
                $min_level = $h['level'];
            }
        }

        $current_depth = 0;

        foreach ( $headings as $h ) {
            $desired_depth = $h['level'] - $min_level + 1;

            if ( $desired_depth > $current_depth ) {
                /* Open new sub-lists until we reach the desired depth */
                while ( $current_depth < $desired_depth ) {
                    $html .= '<' . $tag . '>';
                    $current_depth++;
                    array_push( $stack, $tag );
                }
            } elseif ( $desired_depth < $current_depth ) {
                /* Close sub-lists until we reach the desired depth */
                while ( $current_depth > $desired_depth ) {
                    $html .= '</li>';
                    $html .= '</' . array_pop( $stack ) . '>';
                    $current_depth--;
                }
                /* Close the previous item at this level */
                $html .= '</li>';
            } else {
                /* Same depth — close previous item */
                if ( $current_depth > 0 ) {
                    $html .= '</li>';
                }
            }

            $html .= '<li>';
            $html .= '<a href="#' . esc_attr( $h['id'] ) . '">' . esc_html( $h['text'] ) . '</a>';
        }

        /* Close all remaining open tags */
        while ( $current_depth > 0 ) {
            $html .= '</li>';
            $html .= '</' . array_pop( $stack ) . '>';
            $current_depth--;
        }

        return $html;
    }

    /* ==================================================================
     * Heading ID Injection
     * ================================================================*/

    /**
     * Filter on `the_content` (priority 5) that adds id attributes to
     * headings that don't already have one.
     *
     * Only runs when the current post contains the almaseo/toc block.
     * Idempotent: headings that already have an id are left alone.
     *
     * @param  string $content Post content HTML.
     * @return string          Modified content.
     */
    public static function inject_heading_ids( $content ) {

        if ( empty( $content ) ) {
            return $content;
        }

        /* Only act when the post uses our block */
        global $post;
        if ( ! $post instanceof WP_Post || ! has_block( 'almaseo/toc', $post ) ) {
            return $content;
        }

        /* Track generated IDs to handle duplicates within a single post */
        $seen_ids = array();

        $content = preg_replace_callback(
            '/<h([2-6])([^>]*)>(.*?)<\/h\1>/si',
            function ( $m ) use ( &$seen_ids ) {

                $level       = $m[1];
                $attrs       = $m[2];
                $inner_html  = $m[3];

                /* If the heading already has an id, leave it alone */
                if ( preg_match( '/\bid\s*=/i', $attrs ) ) {
                    return $m[0];
                }

                /* Generate id from text content (strip HTML tags) */
                $plain_text = wp_strip_all_tags( $inner_html );
                $id         = sanitize_title( $plain_text );

                /* De-duplicate */
                $base = $id;
                $i    = 2;
                while ( isset( $seen_ids[ $id ] ) ) {
                    $id = $base . '-' . $i;
                    $i++;
                }
                $seen_ids[ $id ] = true;

                /* Inject the id attribute */
                return '<h' . $level . ' id="' . esc_attr( $id ) . '"' . $attrs . '>' . $inner_html . '</h' . $level . '>';
            },
            $content
        );

        return $content;
    }
}
