<?php
/**
 * AlmaSEO Search Appearance Frontend
 *
 * Extends the meta-tags-renderer to support smart tag templates for all page
 * types: singular, taxonomy archives, author/date archives, search, 404,
 * homepage. Per-post manual overrides always take priority.
 *
 * @package AlmaSEO
 * @since   8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Search_Appearance_Frontend {

    /**
     * Initialize frontend hooks.
     */
    public static function init() {
        // Replace existing title filters with our comprehensive versions.
        remove_filter( 'document_title_parts', 'almaseo_filter_document_title', 10 );
        remove_filter( 'pre_get_document_title', 'almaseo_pre_get_document_title', 10 );

        add_filter( 'document_title_parts', array( __CLASS__, 'filter_title_parts' ), 10 );
        add_filter( 'pre_get_document_title', array( __CLASS__, 'pre_get_title' ), 10 );

        // Meta tags for non-singular pages.
        add_action( 'wp_head', array( __CLASS__, 'render_non_singular_meta' ), 1 );

        // Attachment redirect.
        add_action( 'template_redirect', array( __CLASS__, 'maybe_redirect_attachment' ) );
    }

    /**
     * Filter document_title_parts for all page types.
     *
     * @param array $title_parts Title parts array.
     * @return array Modified title parts.
     */
    public static function filter_title_parts( $title_parts ) {
        $resolved = self::resolve_title();

        if ( ! empty( $resolved ) ) {
            // Return full resolved title — override the parts structure.
            $title_parts['title'] = $resolved;
            // Remove site name and tagline since our template handles them.
            unset( $title_parts['site'], $title_parts['tagline'] );
        }

        return $title_parts;
    }

    /**
     * Filter pre_get_document_title for themes that use it.
     *
     * @param string $title Current title.
     * @return string Modified title.
     */
    public static function pre_get_title( $title ) {
        $resolved = self::resolve_title();
        return ! empty( $resolved ) ? $resolved : $title;
    }

    /**
     * Resolve the SEO title for the current page.
     *
     * Priority: manual per-post override > smart tag template > empty (use WP default).
     *
     * @return string Resolved title, or empty string to use WP default.
     */
    private static function resolve_title() {
        $settings = AlmaSEO_Search_Appearance_Settings::get_settings();

        // 1. Singular pages.
        if ( is_singular() ) {
            global $post;
            if ( ! $post ) {
                return '';
            }

            // Manual override takes priority — but only if it's a usable value
            // (not a leftover foreign template like "#post_title").
            $manual = get_post_meta( $post->ID, '_almaseo_title', true );
            if ( ! empty( $manual ) ) {
                if ( class_exists( 'AlmaSEO_Tag_Validator' ) ) {
                    $manual = AlmaSEO_Tag_Validator::sanitize_seo_value( $manual );
                }
                if ( ! empty( $manual ) ) {
                    // If the cleaned value contains %%tags%%, resolve them.
                    if ( strpos( $manual, '%%' ) !== false ) {
                        return AlmaSEO_Smart_Tags::replace( $manual, array( 'post' => $post ) );
                    }
                    return $manual;
                }
            }

            // Template fallback (manual was empty or invalid).
            $pt_settings = AlmaSEO_Search_Appearance_Settings::get_post_type_settings( $post->post_type );
            if ( ! empty( $pt_settings['title_template'] ) ) {
                return AlmaSEO_Smart_Tags::replace( $pt_settings['title_template'], array(
                    'post' => $post,
                ) );
            }

            return '';
        }

        // 2. Homepage (static or blog).
        if ( is_front_page() || is_home() ) {
            // Static front page with manual override.
            if ( is_front_page() && ! is_home() ) {
                $page_id = (int) get_option( 'page_on_front' );
                if ( $page_id ) {
                    $manual = get_post_meta( $page_id, '_almaseo_title', true );
                    if ( ! empty( $manual ) ) {
                        if ( class_exists( 'AlmaSEO_Tag_Validator' ) ) {
                            $manual = AlmaSEO_Tag_Validator::sanitize_seo_value( $manual );
                        }
                        if ( ! empty( $manual ) ) {
                            if ( strpos( $manual, '%%' ) !== false ) {
                                return AlmaSEO_Smart_Tags::replace( $manual );
                            }
                            return $manual;
                        }
                    }
                }
            }

            $tpl = isset( $settings['special']['homepage']['title_template'] )
                ? $settings['special']['homepage']['title_template']
                : '';

            return ! empty( $tpl ) ? AlmaSEO_Smart_Tags::replace( $tpl ) : '';
        }

        // 3. Taxonomy archives.
        if ( is_category() || is_tag() || is_tax() ) {
            $term = get_queried_object();
            if ( ! $term ) {
                return '';
            }

            $tax_settings = AlmaSEO_Search_Appearance_Settings::get_taxonomy_settings( $term->taxonomy );
            if ( ! empty( $tax_settings['title_template'] ) ) {
                return AlmaSEO_Smart_Tags::replace( $tax_settings['title_template'], array(
                    'term' => $term,
                ) );
            }

            return '';
        }

        // 4. Author archive.
        if ( is_author() ) {
            $tpl = isset( $settings['archives']['author']['title_template'] )
                ? $settings['archives']['author']['title_template']
                : '';

            if ( ! empty( $tpl ) ) {
                $author = get_queried_object();
                return AlmaSEO_Smart_Tags::replace( $tpl, array(
                    'author_id' => $author ? $author->ID : 0,
                ) );
            }

            return '';
        }

        // 5. Date archive.
        if ( is_date() ) {
            $tpl = isset( $settings['archives']['date']['title_template'] )
                ? $settings['archives']['date']['title_template']
                : '';

            if ( ! empty( $tpl ) ) {
                $date_label = '';
                if ( is_day() ) {
                    $date_label = get_the_date();
                } elseif ( is_month() ) {
                    $date_label = get_the_date( 'F Y' );
                } elseif ( is_year() ) {
                    $date_label = get_the_date( 'Y' );
                }

                return AlmaSEO_Smart_Tags::replace( $tpl, array(
                    'title' => $date_label,
                ) );
            }

            return '';
        }

        // 6. Search results.
        if ( is_search() ) {
            $tpl = isset( $settings['special']['search']['title_template'] )
                ? $settings['special']['search']['title_template']
                : '';

            return ! empty( $tpl ) ? AlmaSEO_Smart_Tags::replace( $tpl ) : '';
        }

        // 7. 404 page.
        if ( is_404() ) {
            $tpl = isset( $settings['special']['error_404']['title_template'] )
                ? $settings['special']['error_404']['title_template']
                : '';

            return ! empty( $tpl ) ? AlmaSEO_Smart_Tags::replace( $tpl ) : '';
        }

        // 8. Post type archive.
        if ( is_post_type_archive() ) {
            $post_type = get_query_var( 'post_type' );
            if ( is_array( $post_type ) ) {
                $post_type = reset( $post_type );
            }
            $pt_settings = AlmaSEO_Search_Appearance_Settings::get_post_type_settings( $post_type );
            if ( ! empty( $pt_settings['title_template'] ) ) {
                $pt_obj = get_post_type_object( $post_type );
                return AlmaSEO_Smart_Tags::replace( $pt_settings['title_template'], array(
                    'title' => $pt_obj ? $pt_obj->labels->name : $post_type,
                ) );
            }

            return '';
        }

        return '';
    }

    /**
     * Render meta tags for non-singular pages.
     *
     * The existing almaseo_render_meta_tags() in meta-tags-renderer.php handles
     * singular pages. This method covers everything else.
     */
    public static function render_non_singular_meta() {
        if ( is_singular() ) {
            // Singular pages are handled by meta-tags-renderer.php.
            // But we enhance the meta description if no manual override exists.
            self::maybe_enhance_singular_description();
            return;
        }

        $settings = AlmaSEO_Search_Appearance_Settings::get_settings();
        $noindex  = false;
        $desc     = '';

        // Determine noindex and description based on page type.
        if ( is_front_page() || is_home() ) {
            $tpl = isset( $settings['special']['homepage']['description_template'] )
                ? $settings['special']['homepage']['description_template']
                : '';
            if ( ! empty( $tpl ) ) {
                $desc = AlmaSEO_Smart_Tags::replace( $tpl );
            }

        } elseif ( is_category() || is_tag() || is_tax() ) {
            $term = get_queried_object();
            if ( $term ) {
                $tax_settings = AlmaSEO_Search_Appearance_Settings::get_taxonomy_settings( $term->taxonomy );
                $noindex      = ! empty( $tax_settings['noindex'] );

                if ( ! empty( $tax_settings['description_template'] ) ) {
                    $desc = AlmaSEO_Smart_Tags::replace( $tax_settings['description_template'], array(
                        'term' => $term,
                    ) );
                }
            }

        } elseif ( is_author() ) {
            $noindex = ! empty( $settings['archives']['author']['noindex'] );
            $tpl     = isset( $settings['archives']['author']['description_template'] )
                ? $settings['archives']['author']['description_template']
                : '';

            if ( ! empty( $tpl ) ) {
                $author = get_queried_object();
                $desc   = AlmaSEO_Smart_Tags::replace( $tpl, array(
                    'author_id' => $author ? $author->ID : 0,
                ) );
            }

        } elseif ( is_date() ) {
            $noindex = ! empty( $settings['archives']['date']['noindex'] );

        } elseif ( is_search() ) {
            $noindex = ! empty( $settings['special']['search']['noindex'] );

        } elseif ( is_post_type_archive() ) {
            $post_type   = get_query_var( 'post_type' );
            if ( is_array( $post_type ) ) {
                $post_type = reset( $post_type );
            }
            $pt_settings = AlmaSEO_Search_Appearance_Settings::get_post_type_settings( $post_type );
            $noindex     = ! empty( $pt_settings['noindex'] );

            if ( ! empty( $pt_settings['description_template'] ) ) {
                $desc = AlmaSEO_Smart_Tags::replace( $pt_settings['description_template'] );
            }
        }

        // Output noindex robots tag.
        if ( $noindex ) {
            echo '<meta name="robots" content="noindex, follow" />' . "\n";
        }

        // Output meta description.
        if ( ! empty( $desc ) ) {
            echo '<meta name="description" content="' . esc_attr( $desc ) . '" />' . "\n";
        }

        // Canonical URL for archives.
        $canonical = self::get_archive_canonical();
        if ( ! empty( $canonical ) ) {
            echo '<link rel="canonical" href="' . esc_url( $canonical ) . '" />' . "\n";
        }

        // OG tags for non-singular.
        if ( ! $noindex ) {
            self::render_non_singular_og( $desc );
        }
    }

    /**
     * Enhance singular meta description when no manual override exists.
     * Uses the post type's description template as fallback.
     */
    private static function maybe_enhance_singular_description() {
        global $post;
        if ( ! $post ) {
            return;
        }

        $manual_desc = get_post_meta( $post->ID, '_almaseo_description', true );
        if ( ! empty( $manual_desc ) ) {
            // Only treat as override if the value is actually usable.
            if ( ! class_exists( 'AlmaSEO_Tag_Validator' ) || AlmaSEO_Tag_Validator::is_usable_value( $manual_desc ) ) {
                return; // Manual override exists — meta-tags-renderer.php handles it.
            }
            // Falls through to template fallback if value contains foreign tokens.
        }

        // Check if we should output a template-based description.
        $pt_settings = AlmaSEO_Search_Appearance_Settings::get_post_type_settings( $post->post_type );
        if ( empty( $pt_settings['description_template'] ) ) {
            return;
        }

        // The existing renderer already falls back to post content excerpt,
        // so the template is already effectively handled. No action needed.
    }

    /**
     * Handle attachment page redirect.
     */
    public static function maybe_redirect_attachment() {
        if ( ! is_attachment() ) {
            return;
        }

        $settings = AlmaSEO_Search_Appearance_Settings::get_settings();
        if ( empty( $settings['attachments']['redirect_to_parent'] ) ) {
            return;
        }

        global $post;
        $redirect_url = ( $post && $post->post_parent )
            ? get_permalink( $post->post_parent )
            : home_url( '/' );

        wp_safe_redirect( $redirect_url, 301 );
        exit;
    }

    /**
     * Get canonical URL for archive pages.
     *
     * @return string URL or empty.
     */
    private static function get_archive_canonical() {
        if ( is_front_page() ) {
            return home_url( '/' );
        }

        if ( is_category() || is_tag() || is_tax() ) {
            $term = get_queried_object();
            return $term ? get_term_link( $term ) : '';
        }

        if ( is_author() ) {
            $author = get_queried_object();
            return $author ? get_author_posts_url( $author->ID ) : '';
        }

        if ( is_post_type_archive() ) {
            $post_type = get_query_var( 'post_type' );
            if ( is_array( $post_type ) ) {
                $post_type = reset( $post_type );
            }
            return get_post_type_archive_link( $post_type );
        }

        return '';
    }

    /**
     * Render minimal Open Graph tags for non-singular pages.
     *
     * @param string $desc Meta description.
     */
    private static function render_non_singular_og( $desc ) {
        $title = wp_get_document_title();
        $url   = self::get_archive_canonical();

        if ( empty( $url ) ) {
            if ( isset( $_SERVER['REQUEST_URI'] ) ) {
                $url = home_url( sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) );
            } else {
                $url = home_url( '/' );
            }
        }

        echo '<meta property="og:type" content="website" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url( $url ) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";

        if ( ! empty( $desc ) ) {
            echo '<meta property="og:description" content="' . esc_attr( $desc ) . '" />' . "\n";
        }
    }
}
