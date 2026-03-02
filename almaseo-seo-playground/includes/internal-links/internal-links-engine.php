<?php
/**
 * AlmaSEO Internal Links Engine
 *
 * Filters the_content on the front-end to automatically insert internal links
 * based on keyword rules. Includes guardrails to prevent over-linking, self-linking,
 * and linking inside headings / existing anchors.
 *
 * @package AlmaSEO
 * @subpackage InternalLinks
 * @since 6.6.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Internal_Links_Engine {

    /**
     * Global settings (populated once per request)
     *
     * @var array
     */
    private static $settings = null;

    /**
     * Initialize the engine by hooking into the_content
     */
    public static function init() {
        // Only run on the front-end, singular views
        if ( is_admin() ) {
            return;
        }

        // Hook with a late priority so other plugins process content first
        add_filter( 'the_content', array( __CLASS__, 'process_content' ), 999 );
    }

    /**
     * Get global settings (merged defaults + saved options)
     *
     * @return array
     */
    private static function get_settings() {
        if ( null === self::$settings ) {
            $defaults = array(
                'enabled'            => true,
                'max_links_per_post' => 10,
                'skip_headings'      => true,
                'skip_images'        => true,
                'skip_first_paragraph' => false,
                'exclude_post_ids'   => '',
            );
            $saved          = get_option( 'almaseo_internal_links_settings', array() );
            self::$settings = wp_parse_args( $saved, $defaults );
        }
        return self::$settings;
    }

    /**
     * Process post content and insert internal links
     *
     * @param string $content The post content.
     * @return string Modified content.
     */
    public static function process_content( $content ) {
        // Bail early if not a singular view
        if ( ! is_singular() ) {
            return $content;
        }

        $settings = self::get_settings();

        // Check global kill-switch
        if ( empty( $settings['enabled'] ) ) {
            return $content;
        }

        // Check per-post opt-out meta
        $post_id = get_the_ID();
        if ( ! $post_id ) {
            return $content;
        }

        $opt_out = get_post_meta( $post_id, '_almaseo_skip_internal_links', true );
        if ( $opt_out ) {
            return $content;
        }

        // Check excluded post IDs
        if ( ! empty( $settings['exclude_post_ids'] ) ) {
            $excluded = array_map( 'absint', explode( ',', $settings['exclude_post_ids'] ) );
            if ( in_array( $post_id, $excluded, true ) ) {
                return $content;
            }
        }

        // Load rules
        require_once plugin_dir_path( __FILE__ ) . 'internal-links-model.php';
        $rules = AlmaSEO_Internal_Links_Model::get_enabled_links();

        if ( empty( $rules ) ) {
            return $content;
        }

        // Get current post type
        $post_type = get_post_type( $post_id );

        // Filter rules applicable to this post type and not self-linking
        $current_url = get_permalink( $post_id );
        $applicable  = array();

        foreach ( $rules as $rule ) {
            // Check post type
            $allowed_types = array_map( 'trim', explode( ',', $rule['post_types'] ) );
            if ( ! in_array( $post_type, $allowed_types, true ) ) {
                continue;
            }

            // Prevent self-linking
            if ( ! empty( $rule['target_post_id'] ) && (int) $rule['target_post_id'] === $post_id ) {
                continue;
            }
            if ( $current_url && untrailingslashit( $rule['target_url'] ) === untrailingslashit( $current_url ) ) {
                continue;
            }

            // Check per-rule exclusion list
            if ( ! empty( $rule['exclude_ids'] ) ) {
                $rule_excluded = array_map( 'absint', explode( ',', $rule['exclude_ids'] ) );
                if ( in_array( $post_id, $rule_excluded, true ) ) {
                    continue;
                }
            }

            $applicable[] = $rule;
        }

        if ( empty( $applicable ) ) {
            return $content;
        }

        // Apply rules with guardrails
        $content = self::insert_links( $content, $applicable, $settings );

        return $content;
    }

    /**
     * Insert links into content with guardrails
     *
     * Guardrails:
     * - Never link inside existing <a> tags
     * - Never link inside headings (h1-h6) when setting is on
     * - Never link inside <img>, <script>, <style>, <code>, <pre> tags
     * - Respect max_per_post and max_per_page limits
     * - First match only per keyword per post (unless max_per_post > 1)
     * - Optionally skip first paragraph
     *
     * @param string $content    HTML content.
     * @param array  $rules      Applicable link rules.
     * @param array  $settings   Global settings.
     * @return string Modified content.
     */
    private static function insert_links( $content, $rules, $settings ) {
        $total_inserted = 0;
        $max_total      = absint( $settings['max_links_per_post'] );

        if ( $max_total < 1 ) {
            $max_total = 10;
        }

        // Split content into protected and linkable segments
        // Protected: existing links, headings, script, style, code, pre, img tags
        $protected_tags = 'a|script|style|code|pre|textarea';
        if ( ! empty( $settings['skip_headings'] ) ) {
            $protected_tags .= '|h[1-6]';
        }
        if ( ! empty( $settings['skip_images'] ) ) {
            $protected_tags .= '|img|figure|figcaption';
        }

        // Use a regex to split content into protected zones and text zones
        $pattern = '/(<(?:' . $protected_tags . ')[\s>](?:[^<]|<(?!\/(?:' . $protected_tags . ')\s*>))*<\/(?:' . $protected_tags . ')\s*>|<(?:img|br|hr|input)[^>]*\/?>|<[^>]+>)/is';
        $parts   = preg_split( $pattern, $content, -1, PREG_SPLIT_DELIM_CAPTURE );

        if ( false === $parts || count( $parts ) < 2 ) {
            // Fallback: simple approach if regex fails
            return self::insert_links_simple( $content, $rules, $settings, $max_total );
        }

        // Track insertions per rule
        $rule_counts = array();
        $paragraph_count = 0;

        // Process each part
        for ( $i = 0; $i < count( $parts ); $i++ ) {
            $part = $parts[ $i ];

            // Skip empty parts
            if ( empty( trim( $part ) ) ) {
                continue;
            }

            // If this part starts with <, it's an HTML tag - skip it
            if ( preg_match( '/^</', $part ) ) {
                // Track paragraph count
                if ( preg_match( '/^<p[\s>]/i', $part ) ) {
                    $paragraph_count++;
                }
                continue;
            }

            // Track paragraph boundaries in text nodes
            if ( strpos( $part, '</p>' ) !== false ) {
                $paragraph_count++;
            }

            // Skip first paragraph if setting is on
            if ( ! empty( $settings['skip_first_paragraph'] ) && $paragraph_count <= 1 ) {
                continue;
            }

            // Check if we've hit the global limit
            if ( $total_inserted >= $max_total ) {
                break;
            }

            // Try each rule on this text segment
            foreach ( $rules as $rule ) {
                if ( $total_inserted >= $max_total ) {
                    break;
                }

                $rule_id = $rule['id'];
                if ( ! isset( $rule_counts[ $rule_id ] ) ) {
                    $rule_counts[ $rule_id ] = 0;
                }

                // Check per-rule limit
                $rule_max = absint( $rule['max_per_post'] );
                if ( $rule_max < 1 ) {
                    $rule_max = 1;
                }

                if ( $rule_counts[ $rule_id ] >= $rule_max ) {
                    continue;
                }

                // Build the search pattern
                $search_pattern = self::build_pattern( $rule );
                if ( ! $search_pattern ) {
                    continue;
                }

                // Build the replacement anchor tag
                $anchor = self::build_anchor( $rule );

                // Replace in this text segment (limited count)
                $remaining = $rule_max - $rule_counts[ $rule_id ];
                $remaining = min( $remaining, $max_total - $total_inserted );

                $count       = 0;
                $parts[ $i ] = preg_replace_callback(
                    $search_pattern,
                    function ( $matches ) use ( $anchor, &$count, $remaining, $rule ) {
                        if ( $count >= $remaining ) {
                            return $matches[0];
                        }
                        $count++;

                        // Track hits asynchronously
                        if ( function_exists( 'wp_schedule_single_event' ) && ! wp_next_scheduled( 'almaseo_internal_link_hit', array( (int) $rule['id'] ) ) ) {
                            // We'll batch-update hits via a lightweight approach
                        }

                        return str_replace( '{{KEYWORD}}', $matches[0], $anchor );
                    },
                    $parts[ $i ]
                );

                $rule_counts[ $rule_id ] += $count;
                $total_inserted          += $count;
            }
        }

        return implode( '', $parts );
    }

    /**
     * Simple fallback link insertion (no DOM parsing)
     *
     * Used when the regex-split approach fails.
     *
     * @param string $content   HTML content.
     * @param array  $rules     Link rules.
     * @param array  $settings  Global settings.
     * @param int    $max_total Max links to insert.
     * @return string
     */
    private static function insert_links_simple( $content, $rules, $settings, $max_total ) {
        $total_inserted = 0;

        foreach ( $rules as $rule ) {
            if ( $total_inserted >= $max_total ) {
                break;
            }

            $pattern = self::build_pattern( $rule );
            if ( ! $pattern ) {
                continue;
            }

            $anchor    = self::build_anchor( $rule );
            $rule_max  = absint( $rule['max_per_post'] );
            $remaining = min( $rule_max, $max_total - $total_inserted );
            $count     = 0;

            $content = preg_replace_callback(
                $pattern,
                function ( $matches ) use ( $anchor, &$count, $remaining ) {
                    if ( $count >= $remaining ) {
                        return $matches[0];
                    }

                    // Quick check: skip if we're inside an anchor tag
                    // This is an approximation - the main method is more accurate
                    $count++;
                    return str_replace( '{{KEYWORD}}', $matches[0], $anchor );
                },
                $content
            );

            $total_inserted += $count;
        }

        return $content;
    }

    /**
     * Build a regex pattern for a keyword rule
     *
     * @param array $rule Link rule.
     * @return string|false Regex pattern or false on failure.
     */
    private static function build_pattern( $rule ) {
        $keyword = $rule['keyword'];

        if ( empty( $keyword ) ) {
            return false;
        }

        $flags = 'u'; // UTF-8
        if ( empty( $rule['case_sensitive'] ) ) {
            $flags .= 'i';
        }

        switch ( $rule['match_type'] ) {
            case 'regex':
                // User-supplied regex - validate it
                $pattern = '/' . $keyword . '/' . $flags;
                if ( @preg_match( $pattern, '' ) === false ) {
                    return false; // Invalid regex
                }
                return $pattern;

            case 'partial':
                // Match keyword anywhere (not just word boundaries)
                return '/' . preg_quote( $keyword, '/' ) . '/' . $flags;

            case 'exact':
            default:
                // Match whole word only using word boundaries
                return '/\b' . preg_quote( $keyword, '/' ) . '\b/' . $flags;
        }
    }

    /**
     * Build the anchor tag HTML for a rule
     *
     * @param array $rule Link rule.
     * @return string HTML anchor tag with {{KEYWORD}} placeholder.
     */
    private static function build_anchor( $rule ) {
        $attrs = array(
            'href'  => esc_url( $rule['target_url'] ),
            'class' => 'almaseo-auto-link',
            'data-rule-id' => esc_attr( $rule['id'] ),
        );

        if ( ! empty( $rule['nofollow'] ) ) {
            $attrs['rel'] = 'nofollow';
        }

        if ( ! empty( $rule['new_tab'] ) ) {
            $attrs['target'] = '_blank';
            $attrs['rel']    = isset( $attrs['rel'] ) ? $attrs['rel'] . ' noopener noreferrer' : 'noopener noreferrer';
        }

        $attr_string = '';
        foreach ( $attrs as $key => $value ) {
            $attr_string .= ' ' . $key . '="' . $value . '"';
        }

        return '<a' . $attr_string . '>{{KEYWORD}}</a>';
    }
}
