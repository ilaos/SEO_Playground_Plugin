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
     * Resolution order:
     *   1. If the cached business profile is filled and the title is a
     *      placeholder ("Home", "Front Page", etc.) or this is the front
     *      page, build a profile-driven title:
     *        "{primary service} in {primary area} | {business name}"
     *   2. Otherwise enhance the literal post_title with year/power word
     *      decoration (Plan B), but only when the base title has enough
     *      meaningful words. Power-word selection is deterministic per
     *      post so re-runs are stable.
     *
     * @param \WP_Post $post The post object.
     * @return string Generated title (50-60 chars target).
     */
    public static function generate_title( $post ) {
        $raw_title = trim( $post->post_title ?? '' );
        $site      = get_bloginfo( 'name' );

        // ── Plan A: profile-driven build for placeholder/homepage titles ──
        $profile        = self::profile_data();
        $is_placeholder = self::is_placeholder_title( $raw_title, $post );

        if ( $is_placeholder && ! empty( $profile ) ) {
            $built = self::build_from_profile( $profile, $site );
            if ( ! empty( $built ) ) {
                return self::clean_title( $built );
            }
        }

        // ── Plan B: title is a placeholder (or empty) and no profile cached.
        // Pull a base from post_content rather than literally decorating
        // "Home". The page tagline is also a reasonable seed for the front
        // page when content is thin.
        if ( $is_placeholder || empty( $raw_title ) ) {
            $derived = self::derive_base_from_content( $post );
            if ( empty( $derived ) ) {
                $tagline = trim( (string) get_bloginfo( 'description' ) );
                if ( $tagline !== '' ) {
                    $derived = $tagline;
                }
            }
            if ( ! empty( $derived ) ) {
                $raw_title = $derived;
            } elseif ( empty( $raw_title ) ) {
                // Truly nothing to work with — let the caller fall back to
                // the WP-rendered title rather than emit a stub.
                return '';
            }
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

        // Step 4: Build the enhanced title
        // Strategy: only add elements if there's room. Never force additions
        // that would make the title too long or nonsensical.
        $len = mb_strlen( $title );
        $enhanced = $title;

        // If title is already 50-60 chars and has a power word, it's good as-is
        if ( $len >= 50 && $len <= 60 && $has_power ) {
            return self::clean_title( $enhanced );
        }

        // Don't decorate with stock power words / years on placeholder-ish
        // titles — that's where "Comprehensive Home in 2026" came from. The
        // title needs at least 2 meaningful (non-stopword) tokens of length
        // ≥3 before we'll pad it.
        $can_decorate = self::has_meaningful_tokens( $title, 2 );

        // Only try enhancements if there's room (title under ~45 chars)
        if ( $can_decorate && $len < 45 ) {
            $year = gmdate( 'Y' );
            // Try adding year if not present
            if ( ! preg_match( '/20\d{2}/', $enhanced ) ) {
                $with_year = $enhanced . ' in ' . $year;
                if ( mb_strlen( $with_year ) <= 60 ) {
                    $enhanced = $with_year;
                }
            }

            // Add a power word if none present and there's still room.
            // Selection is deterministic per post — re-running the generator
            // on the same post returns the same title.
            if ( ! $has_power && mb_strlen( $enhanced ) < 50 ) {
                $contextual_powers = array( 'essential', 'complete', 'proven', 'expert', 'comprehensive' );
                $idx   = abs( crc32( (string) ( $post->ID ?? 0 ) ) ) % count( $contextual_powers );
                $power = $contextual_powers[ $idx ];

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
                $enhanced = self::truncate_at_word( $title, 60 );
            }
        }

        return self::clean_title( $enhanced );
    }

    /**
     * Read the cached business profile (services, service areas, etc.)
     * pulled from the AlmaSEO dashboard. Returns empty array when unavailable.
     */
    private static function profile_data() {
        if ( class_exists( '\\AlmaSEO\\Connection\\Site_Profile' ) ) {
            return \AlmaSEO\Connection\Site_Profile::profile_data();
        }
        return array();
    }

    /**
     * Whether a title is generic enough that we should ignore it and pull
     * from the profile instead. Catches WP defaults ("Home", "Sample Page")
     * and sites where the front page is set via Settings → Reading.
     */
    private static function is_placeholder_title( $title, $post ) {
        $is_front = false;
        if ( ! empty( $post->ID ) && (int) get_option( 'page_on_front' ) === (int) $post->ID ) {
            $is_front = true;
        }

        if ( $is_front ) {
            return true;
        }

        $needle = strtolower( trim( $title ) );
        if ( $needle === '' ) {
            return true;
        }

        $placeholders = array( 'home', 'homepage', 'front page', 'welcome', 'untitled', 'sample page', 'main', 'index' );
        return in_array( $needle, $placeholders, true );
    }

    /**
     * Build a title from cached profile data:
     *   "{primary service} in {primary area} | {business name}"
     * Falls back gracefully when fields are missing.
     */
    private static function build_from_profile( $profile, $site ) {
        $business = ! empty( $profile['business_name'] ) ? $profile['business_name'] : $site;
        $services = isset( $profile['services'] ) && is_array( $profile['services'] ) ? $profile['services'] : array();
        $areas    = isset( $profile['service_areas'] ) && is_array( $profile['service_areas'] ) ? $profile['service_areas'] : array();

        $primary_service = ! empty( $services ) ? ucwords( (string) $services[0] ) : '';
        $primary_area    = ! empty( $areas ) ? (string) $areas[0] : '';
        if ( $primary_area === '' && ! empty( $profile['city'] ) ) {
            $primary_area = $profile['state']
                ? $profile['city'] . ', ' . $profile['state']
                : $profile['city'];
        }

        $candidates = array();
        if ( $primary_service && $primary_area && $business ) {
            $candidates[] = $primary_service . ' in ' . $primary_area . ' | ' . $business;
        }
        if ( $primary_service && $business ) {
            $candidates[] = $primary_service . ' | ' . $business;
        }
        if ( $primary_area && $business ) {
            $candidates[] = $business . ' — ' . $primary_area;
        }
        if ( $business && ! empty( $profile['slogan'] ) ) {
            $candidates[] = $business . ' | ' . $profile['slogan'];
        }
        if ( $business ) {
            $candidates[] = $business;
        }

        // Pick the first candidate that fits within 65 chars; otherwise the
        // longest one trimmed at a word boundary.
        foreach ( $candidates as $cand ) {
            if ( mb_strlen( $cand ) <= 65 ) {
                return $cand;
            }
        }

        return ! empty( $candidates ) ? self::truncate_at_word( $candidates[0], 60 ) : '';
    }

    /**
     * Pull a candidate title from the first paragraph of post_content when
     * the post has no usable post_title and there's no profile to fall
     * back to. Prefers the first *complete sentence* if it fits in the
     * title length budget — that beats truncating mid-sentence.
     */
    private static function derive_base_from_content( $post ) {
        $content = $post->post_content ?? '';
        if ( empty( $content ) ) {
            return '';
        }
        $first = self::extract_first_paragraph( $content );
        $first = trim( preg_replace( '/\s+/', ' ', $first ) );
        if ( $first === '' ) {
            return '';
        }

        // Try the first complete sentence — preferred over a truncated
        // fragment like "We cover the entire" hanging off mid-clause.
        if ( preg_match( '/^(.+?[.!?])(?:\s|$)/u', $first, $m ) ) {
            $sentence = trim( $m[1] );
            $len = mb_strlen( $sentence );
            if ( $len >= 20 && $len <= 60 ) {
                // Drop the trailing period — titles read cleaner without it.
                return rtrim( $sentence, '.' );
            }
        }

        // Otherwise truncate at a word boundary and trim trailing
        // articles/conjunctions so we don't end with "the entire".
        if ( mb_strlen( $first ) > 60 ) {
            $first = self::truncate_at_word( $first, 55 );
        }
        $first = preg_replace(
            '/\s+(the|a|an|and|or|but|in|on|at|to|for|of|with|by|from)$/i',
            '',
            $first
        );
        return trim( $first );
    }

    /**
     * Whether a candidate has at least $min meaningful (non-stopword,
     * length ≥3) tokens. Used to gate stock decoration so we don't end up
     * with nonsense like "Comprehensive Home in 2026".
     */
    private static function has_meaningful_tokens( $title, $min ) {
        $stops = array(
            'the','a','an','and','or','but','in','on','at','to','for',
            'of','with','by','from','is','are','was','how','what','why',
            'when','where','who','which','your','our','my','this','that',
            'home','page','welcome','main','index',
        );
        $words = preg_split( '/\s+/', strtolower( $title ) );
        $kept  = 0;
        foreach ( (array) $words as $w ) {
            $w = preg_replace( '/[^a-z0-9]/', '', $w );
            if ( mb_strlen( $w ) >= 3 && ! in_array( $w, $stops, true ) ) {
                $kept++;
                if ( $kept >= $min ) {
                    return true;
                }
            }
        }
        return false;
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

        // Profile-aware path: for placeholder/homepage posts, prefer about_us
        // from the cached business profile over a generic "Explore everything
        // about Home." string.
        $profile = self::profile_data();
        $is_placeholder = self::is_placeholder_title( $post->post_title ?? '', $post );

        if ( $is_placeholder && ! empty( $profile['about_us'] ) ) {
            $base_text = $profile['about_us'];
        } elseif ( ! empty( $excerpt ) ) {
            $base_text = wp_strip_all_tags( $excerpt );
        } else {
            $base_text = self::extract_first_paragraph( $content );
        }

        // Clean up
        $base_text = preg_replace( '/\s+/', ' ', trim( $base_text ) );

        if ( empty( $base_text ) ) {
            // Final fallback — even here, prefer profile context over a
            // bare "Explore everything about Home." sentence.
            if ( ! empty( $profile['business_name'] ) && ! empty( $profile['services'] ) ) {
                $service = ucfirst( (string) $profile['services'][0] );
                $area    = ! empty( $profile['service_areas'] ) ? $profile['service_areas'][0] : ( $profile['city'] ?? '' );
                $base_text = $area
                    ? "{$profile['business_name']} provides {$service} in {$area}."
                    : "{$profile['business_name']} provides {$service}.";
            } else {
                $base_text = "Explore everything about {$topic}.";
            }
        }

        // Target 150-160 characters
        $desc = $base_text;

        if ( mb_strlen( $desc ) > 160 ) {
            $desc = self::truncate_at_word( $desc, 160 );
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
            if ( mb_strlen( $desc ) > 160 ) {
                $desc = self::truncate_at_word( $desc, 160 );
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
     * Truncate text at a word boundary — no ellipsis.
     * A clean shorter string is better than one Google truncates.
     */
    private static function truncate_at_word( $text, $max_length ) {
        if ( mb_strlen( $text ) <= $max_length ) {
            return $text;
        }

        $truncated = mb_substr( $text, 0, $max_length + 1 );
        $last_space = mb_strrpos( $truncated, ' ' );

        if ( $last_space && $last_space > $max_length * 0.6 ) {
            return rtrim( mb_substr( $truncated, 0, $last_space ), '.,;:!?-' );
        }

        return mb_substr( $text, 0, $max_length );
    }
}

/**
 * AJAX handler for single-post auto-fill from the metabox.
 * Registered on init so it's available on post edit screens.
 */
function almaseo_ajax_autofill_field() {
    check_ajax_referer( 'almaseo_nonce', 'nonce' );

    $post_id = isset( $_POST['post_id'] ) ? intval( wp_unslash( $_POST['post_id'] ) ) : 0;
    $field   = isset( $_POST['field'] ) ? sanitize_text_field( wp_unslash( $_POST['field'] ) ) : '';
    $mode    = isset( $_POST['mode'] ) ? sanitize_text_field( wp_unslash( $_POST['mode'] ) ) : 'auto';

    // Live field values from the editor: generation should reflect what the user
    // just typed, not only what is saved in post meta (the focus keyword, title
    // and description aren't persisted until the post is saved). Each is optional.
    $overrides = array();
    if ( isset( $_POST['focus_keyword'] ) ) {
        $overrides['focus_keyword'] = sanitize_text_field( wp_unslash( $_POST['focus_keyword'] ) );
    }
    if ( isset( $_POST['current_title'] ) ) {
        $overrides['current_title'] = sanitize_text_field( wp_unslash( $_POST['current_title'] ) );
    }
    if ( isset( $_POST['current_description'] ) ) {
        $overrides['current_desc'] = sanitize_textarea_field( wp_unslash( $_POST['current_description'] ) );
    }

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

    // Lazy-fetch the cached site profile if the cache is empty/stale. This
    // covers upgrades where the password was already saved before this
    // version landed (the update_option hook only fires on re-save).
    $profile_ready = false;
    $profile_fields_present = array();
    if ( class_exists( '\\AlmaSEO\\Connection\\Site_Profile' ) ) {
        $profile_ready = \AlmaSEO\Connection\Site_Profile::ensure_fresh();
        if ( $profile_ready ) {
            $p = \AlmaSEO\Connection\Site_Profile::profile_data();
            foreach ( array( 'business_name', 'industry_type', 'about_us', 'slogan' ) as $f ) {
                if ( ! empty( $p[ $f ] ) ) {
                    $profile_fields_present[] = $f;
                }
            }
            if ( ! empty( $p['services'] ) ) {
                $profile_fields_present[] = 'services';
            }
            if ( ! empty( $p['service_areas'] ) ) {
                $profile_fields_present[] = 'service_areas';
            }
        }
    }

    // Try AlmaSEO-powered generation when the user asked for it ('ai'/'auto').
    // AI_Autofill_Generator::is_available() gates this on the AlmaSEO connection;
    // local generation always runs as the free fallback below.
    if ( $mode !== 'basic' ) {
        require_once __DIR__ . '/ai-autofill-generator.php';
        if ( AI_Autofill_Generator::is_available() ) {
            $ai_result = AI_Autofill_Generator::generate_single( $post_id, $field, $overrides );
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

    // Resolution path — surfaced in the UI badge so the user can see which
    // generator ran and whether the business profile was reachable.
    if ( $ai_used ) {
        $resolution = $profile_ready ? 'ai_with_profile' : 'ai';
    } elseif ( $mode !== 'basic' ) {
        // Local generation ran; an AlmaSEO connection adds profile-aware generation.
        $resolution = 'local_connect_offer';
    } else {
        $resolution = $profile_ready ? 'local_with_profile' : 'local';
    }

    $field_map = array(
        'title'       => 'meta_title',
        'description' => 'meta_description',
        'keyword'     => 'focus_keyword',
    );

    if ( ! empty( $field ) && isset( $field_map[ $field ] ) ) {
        $key   = $field_map[ $field ];
        $value = isset( $generated[ $key ] ) ? $generated[ $key ] : '';
        $response = array(
            'value'                  => $value,
            'field'                  => $field,
            'ai'                     => $ai_used,
            'resolution'             => $resolution,
            'profile_fields_present' => $profile_fields_present,
        );
        if ( ! empty( $profile_suggestions ) ) {
            $response['profile_suggestions'] = $profile_suggestions;
        }
        wp_send_json_success( $response );
    }

    // Return all fields
    $generated['ai']                     = $ai_used;
    $generated['resolution']             = $resolution;
    $generated['profile_fields_present'] = $profile_fields_present;
    if ( ! empty( $profile_suggestions ) ) {
        $generated['profile_suggestions'] = $profile_suggestions;
    }
    wp_send_json_success( $generated );
}
add_action( 'wp_ajax_almaseo_autofill_field', __NAMESPACE__ . '\\almaseo_ajax_autofill_field' );
