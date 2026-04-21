<?php
/**
 * AlmaSEO Auto-Fill Metadata Generator
 *
 * Generates SEO titles and meta descriptions from post content using
 * the same scoring criteria as the Headline Analyzer. No LLM/API needed —
 * all generation is done locally with PHP.
 *
 * @package AlmaSEO
 * @since 8.10.0
 */

namespace AlmaSEO\BulkMeta;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Autofill_Generator {

    /**
     * Power words that improve headline scores.
     * Mirrors AlmaSEO_Headline_Analyzer::$power_words.
     */
    private static $power_words = array(
        'essential', 'proven', 'complete', 'ultimate', 'comprehensive',
        'best', 'top', 'expert', 'vital', 'crucial', 'definitive',
        'simple', 'easy', 'fast', 'quick', 'free', 'new', 'must',
        'boost', 'transform', 'unlock',
    );

    /**
     * Emotional words that improve headline scores.
     * Subset from AlmaSEO_Headline_Analyzer::$emotional_words — professional-safe.
     */
    private static $emotional_words = array(
        'surprising', 'inspiring', 'remarkable', 'brilliant', 'stunning',
        'incredible', 'amazing', 'fantastic', 'wonderful', 'extraordinary',
        'beautiful', 'exciting',
    );

    /**
     * Generate SEO title from post data.
     *
     * Strategy: Start from the ACTUAL post title and enhance it for SEO,
     * rather than replacing it with a generic template. This preserves
     * the page's real meaning while improving headline score.
     *
     * @param \WP_Post $post The post object.
     * @return string Generated title (50-60 chars target).
     */
    public static function generate_title( $post ) {
        $raw_title = trim( $post->post_title ?? '' );
        $year      = gmdate( 'Y' );
        $site      = get_bloginfo( 'name' );

        if ( empty( $raw_title ) ) {
            return '';
        }

        // Step 1: Clean the title (remove existing site name suffixes, pipes, dashes)
        $title = preg_replace( '/\s*[\|\-–—]\s*' . preg_quote( $site, '/' ) . '\s*$/i', '', $raw_title );
        $title = trim( $title );

        // Step 2: Check if title already contains a power word
        $title_lower = strtolower( $title );
        $title_words = preg_split( '/\s+/', $title_lower );
        $has_power   = false;
        foreach ( self::$power_words as $pw ) {
            if ( in_array( $pw, $title_words, true ) ) {
                $has_power = true;
                break;
            }
        }

        // Step 3: Check if title already contains a number
        $has_number = (bool) preg_match( '/\d/', $title );

        // Step 4: Build the enhanced title
        // Strategy: only add elements if there's room. Never force additions
        // that would make the title too long or nonsensical.
        $len = mb_strlen( $title );
        $enhanced = $title;

        // If title is already 50-60 chars and has a power word, it's good as-is
        if ( $len >= 50 && $len <= 60 && $has_power ) {
            return self::clean_title( $enhanced );
        }

        // Only try enhancements if there's room (title under ~45 chars)
        if ( $len < 45 ) {
            // Try adding year if not present
            if ( ! preg_match( '/20\d{2}/', $enhanced ) ) {
                $with_year = $enhanced . ' in ' . $year;
                if ( mb_strlen( $with_year ) <= 60 ) {
                    $enhanced = $with_year;
                }
            }

            // Add a power word if none present and there's still room
            if ( ! $has_power && mb_strlen( $enhanced ) < 50 ) {
                $contextual_powers = array( 'essential', 'complete', 'proven', 'expert', 'comprehensive' );
                $power = $contextual_powers[ array_rand( $contextual_powers ) ];

                $with_power = ucfirst( $power ) . ' ' . $enhanced;
                if ( mb_strlen( $with_power ) <= 60 ) {
                    $enhanced = $with_power;
                }
            }
        }

        // If still too short, append site name with pipe
        if ( mb_strlen( $enhanced ) < 45 && ! empty( $site ) ) {
            $with_site = $enhanced . ' | ' . $site;
            if ( mb_strlen( $with_site ) <= 65 ) {
                $enhanced = $with_site;
            }
        }

        // If original title was already long (45+ chars), just add site name if it fits
        if ( $enhanced === $title && $len >= 45 && $len < 55 && ! empty( $site ) ) {
            $with_site = $title . ' | ' . $site;
            if ( mb_strlen( $with_site ) <= 65 ) {
                $enhanced = $with_site;
            }
        }

        // If too long, trim intelligently
        if ( mb_strlen( $enhanced ) > 65 ) {
            $simple = $title . ' | ' . $site;
            if ( mb_strlen( $simple ) <= 65 ) {
                $enhanced = $simple;
            } else if ( $len <= 65 ) {
                // Original title fits on its own
                $enhanced = $title;
            } else {
                $enhanced = self::truncate_at_word( $title, 57 ) . '...';
            }
        }

        return self::clean_title( $enhanced );
    }

    /**
     * Generate meta description from post data.
     *
     * @param \WP_Post $post The post object.
     * @return string Generated description (150-160 chars target).
     */
    public static function generate_description( $post ) {
        $content = $post->post_content ?? '';
        $excerpt = $post->post_excerpt ?? '';
        $topic   = self::extract_topic( $post );

        // Start with excerpt if available, otherwise first paragraph of content
        if ( ! empty( $excerpt ) ) {
            $base_text = wp_strip_all_tags( $excerpt );
        } else {
            $base_text = self::extract_first_paragraph( $content );
        }

        // Clean up
        $base_text = preg_replace( '/\s+/', ' ', trim( $base_text ) );

        if ( empty( $base_text ) ) {
            $base_text = "Explore everything about {$topic}.";
        }

        // Target 150-160 characters
        $desc = $base_text;

        if ( mb_strlen( $desc ) > 160 ) {
            $desc = self::truncate_at_word( $desc, 157 ) . '...';
        } elseif ( mb_strlen( $desc ) < 120 ) {
            // Too short — expand with topic context
            $suffix_options = array(
                " Explore essential insights and proven strategies for {$topic}.",
                " Discover comprehensive tips and expert advice on {$topic}.",
                " Learn vital {$topic} strategies backed by expert insights.",
            );
            shuffle( $suffix_options );
            foreach ( $suffix_options as $suffix ) {
                $candidate = rtrim( $desc, '.' ) . '.' . $suffix;
                if ( mb_strlen( $candidate ) >= 150 && mb_strlen( $candidate ) <= 165 ) {
                    $desc = $candidate;
                    break;
                }
            }
            // Final trim if expansion overshot
            if ( mb_strlen( $desc ) > 165 ) {
                $desc = self::truncate_at_word( $desc, 157 ) . '...';
            }
        }

        return $desc;
    }

    /**
     * Generate both title and description, plus focus keyword and OG fields.
     *
     * @param \WP_Post $post The post object.
     * @return array Associative array of generated metadata.
     */
    public static function generate_all( $post ) {
        $title = self::generate_title( $post );
        $description = self::generate_description( $post );
        $focus_keyword = self::extract_focus_keyword( $post );

        return array(
            'meta_title'       => $title,
            'meta_description' => $description,
            'focus_keyword'    => $focus_keyword,
            'og_title'         => $title,
            'og_description'   => $description,
        );
    }

    /**
     * Apply auto-fill to a post — only fills empty fields.
     *
     * @param int   $post_id  The post ID.
     * @param array $fields   Optional — specific fields to fill. Default: all empty fields.
     * @param bool  $overwrite Whether to overwrite existing values. Default: false.
     * @return array The generated/existing values for each field.
     */
    public static function apply( $post_id, $fields = array(), $overwrite = false ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return array();
        }

        $generated = self::generate_all( $post );
        $result    = array();

        // Map: field key => array of meta keys to write (primary first).
        // Primary keys MUST match what the metabox + frontend renderer read:
        //   Title:       _almaseo_title       (metabox-callback.php:335, meta-tags-renderer.php:23)
        //   Description: _almaseo_description (metabox-callback.php:339, meta-tags-renderer.php:111)
        // Also writes to the Bulk Meta Editor's keys for table display consistency.
        $meta_map = array(
            'meta_title'       => array( '_almaseo_title', '_almaseo_meta_title' ),
            'meta_description' => array( '_almaseo_description', '_almaseo_meta_description' ),
            'focus_keyword'    => array( '_almaseo_focus_keyword' ),
            'og_title'         => array( '_almaseo_og_title' ),
            'og_description'   => array( '_almaseo_og_description' ),
        );

        foreach ( $meta_map as $key => $meta_keys ) {
            // Skip if not in requested fields (when fields are specified)
            if ( ! empty( $fields ) && ! in_array( $key, $fields, true ) ) {
                $result[ $key ] = self::read_meta( $post_id, $meta_keys );
                continue;
            }

            $current = self::read_meta( $post_id, $meta_keys );

            if ( $overwrite || empty( $current ) ) {
                $value = isset( $generated[ $key ] ) ? $generated[ $key ] : '';
                if ( ! empty( $value ) ) {
                    // Write to all keys so both primary and fallback are populated
                    foreach ( $meta_keys as $mk ) {
                        update_post_meta( $post_id, $mk, sanitize_text_field( $value ) );
                    }
                    $result[ $key ] = $value;
                } else {
                    $result[ $key ] = $current;
                }
            } else {
                $result[ $key ] = $current;
            }
        }

        return $result;
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Read a meta value, trying multiple keys (primary + fallbacks).
     */
    private static function read_meta( $post_id, $keys ) {
        foreach ( $keys as $k ) {
            $val = (string) get_post_meta( $post_id, $k, true );
            if ( ! empty( $val ) ) {
                return $val;
            }
        }
        return '';
    }

    /**
     * Extract a clean topic phrase from the post title.
     */
    private static function extract_topic( $post ) {
        $title = $post->post_title ?? '';
        // Remove common prefixes/suffixes
        $title = preg_replace( '/\s*[\|\-–—]\s*.{0,30}$/', '', $title );
        $title = preg_replace( '/^(how to|why|what is|the|a|an)\s+/i', '', $title );
        $title = trim( $title );

        // Cap at ~30 chars for template insertion
        if ( mb_strlen( $title ) > 30 ) {
            $title = self::truncate_at_word( $title, 28 );
        }

        return $title;
    }

    /**
     * Extract focus keyword — most prominent 1-2 word phrase from title.
     */
    private static function extract_focus_keyword( $post ) {
        $title = strtolower( $post->post_title ?? '' );
        // Remove stopwords
        $stops = array( 'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for',
                        'of', 'with', 'by', 'from', 'is', 'are', 'was', 'how', 'what', 'why',
                        'when', 'where', 'who', 'which', 'your', 'our', 'my', 'this', 'that' );
        $words = preg_split( '/\s+/', $title );
        $words = array_filter( $words, function( $w ) use ( $stops ) {
            return mb_strlen( $w ) > 2 && ! in_array( $w, $stops, true );
        });
        $words = array_values( $words );

        if ( count( $words ) >= 2 ) {
            return ucwords( $words[0] . ' ' . $words[1] );
        } elseif ( count( $words ) === 1 ) {
            return ucwords( $words[0] );
        }

        return '';
    }

    /**
     * Extract the first meaningful paragraph from HTML content.
     */
    private static function extract_first_paragraph( $html ) {
        // Strip shortcodes first
        $html = strip_shortcodes( $html );

        // Try to get first <p> tag content
        if ( preg_match( '/<p[^>]*>(.*?)<\/p>/is', $html, $m ) ) {
            $text = wp_strip_all_tags( $m[1] );
            if ( mb_strlen( $text ) > 30 ) {
                return $text;
            }
        }

        // Fallback: strip all tags and take first chunk
        $text = wp_strip_all_tags( $html );
        $text = preg_replace( '/\s+/', ' ', trim( $text ) );

        return $text;
    }

    /**
     * Pick a contextual number for the title.
     */
    private static function pick_number( $post ) {
        // Use category count if available
        if ( $post->post_type === 'post' ) {
            $cats = get_the_category( $post->ID );
            if ( ! empty( $cats ) ) {
                $count = $cats[0]->count;
                if ( $count >= 3 && $count <= 15 ) {
                    return (string) $count;
                }
            }
        }

        // Use a sensible default range
        $options = array( '5', '7', '8', '10' );
        return $options[ array_rand( $options ) ];
    }

    /**
     * Pick a random word from a list.
     */
    private static function pick_word( $list ) {
        return $list[ array_rand( $list ) ];
    }

    /**
     * Clean up a generated title.
     */
    private static function clean_title( $title ) {
        $title = preg_replace( '/\s+/', ' ', $title );
        $title = preg_replace( '/\|\s*\|/', '|', $title );
        return trim( $title );
    }

    /**
     * Truncate text at a word boundary.
     */
    private static function truncate_at_word( $text, $max_length ) {
        if ( mb_strlen( $text ) <= $max_length ) {
            return $text;
        }

        $truncated = mb_substr( $text, 0, $max_length );
        $last_space = mb_strrpos( $truncated, ' ' );

        if ( $last_space && $last_space > $max_length * 0.6 ) {
            return mb_substr( $truncated, 0, $last_space );
        }

        return $truncated;
    }
}

/**
 * AJAX handler for single-post auto-fill from the metabox.
 * Registered on init so it's available on post edit screens.
 */
function almaseo_ajax_autofill_field() {
    check_ajax_referer( 'almaseo_nonce', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? intval( $_POST['post_id'] ) : 0;
    $field   = isset( $_POST['field'] ) ? sanitize_text_field( $_POST['field'] ) : '';
    $mode    = isset( $_POST['mode'] ) ? sanitize_text_field( $_POST['mode'] ) : 'auto';

    if ( ! $post_id || ! current_user_can( 'edit_post', $post_id ) ) {
        wp_send_json_error( array( 'message' => 'Invalid post or insufficient permissions.' ) );
    }

    $post = get_post( $post_id );
    if ( ! $post ) {
        wp_send_json_error( array( 'message' => 'Post not found.' ) );
    }

    $ai_used = false;
    $generated = null;

    $profile_suggestions = array();

    // Try AI if mode is 'ai' or 'auto' (and connected)
    if ( $mode !== 'basic' ) {
        require_once __DIR__ . '/ai-autofill-generator.php';
        if ( AI_Autofill_Generator::is_available() ) {
            $ai_result = AI_Autofill_Generator::generate_single( $post_id, $field );
            if ( $ai_result ) {
                // Extract profile suggestions before using as generated data
                if ( isset( $ai_result['_profile_suggestions'] ) ) {
                    $profile_suggestions = $ai_result['_profile_suggestions'];
                    unset( $ai_result['_profile_suggestions'] );
                }
                $generated = $ai_result;
                $ai_used = true;
            }
        }
    }

    // Fall back to local generation
    if ( ! $generated ) {
        $generated = Autofill_Generator::generate_all( $post );
    }

    $field_map = array(
        'title'       => 'meta_title',
        'description' => 'meta_description',
        'keyword'     => 'focus_keyword',
    );

    if ( ! empty( $field ) && isset( $field_map[ $field ] ) ) {
        $key   = $field_map[ $field ];
        $value = isset( $generated[ $key ] ) ? $generated[ $key ] : '';
        $response = array( 'value' => $value, 'field' => $field, 'ai' => $ai_used );
        if ( ! empty( $profile_suggestions ) ) {
            $response['profile_suggestions'] = $profile_suggestions;
        }
        wp_send_json_success( $response );
    }

    // Return all fields
    $generated['ai'] = $ai_used;
    if ( ! empty( $profile_suggestions ) ) {
        $generated['profile_suggestions'] = $profile_suggestions;
    }
    wp_send_json_success( $generated );
}
add_action( 'wp_ajax_almaseo_autofill_field', __NAMESPACE__ . '\\almaseo_ajax_autofill_field' );
