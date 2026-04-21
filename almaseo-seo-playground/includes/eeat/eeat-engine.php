<?php
/**
 * E-E-A-T Enforcement – Scanning Engine
 *
 * Core detection logic for trust signal gaps: missing author,
 * missing bio, missing author schema, missing credentials,
 * no citation sources, and missing review attribution.
 *
 * Also integrates with the Health Score system via filter hooks.
 *
 * @package AlmaSEO
 * @since   7.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_EEAT_Engine {

    /**
     * Default settings.
     */
    const DEFAULT_SETTINGS = array(
        'generic_usernames' => 'admin,editor,webmaster',
        'check_sources'     => true,
        'check_review_date' => false,
        'ymyl_categories'   => '',
        'health_weight'     => 0,
        'scan_post_types'   => array( 'post', 'page', 'product' ),
    );

    /* ──────────────────────── Settings ── */

    /**
     * Get scan settings.
     *
     * @return array
     */
    public static function get_settings() {
        $saved = get_option( 'almaseo_eeat_settings', array() );
        return wp_parse_args( $saved, self::DEFAULT_SETTINGS );
    }

    /**
     * Get generic usernames as array.
     *
     * @return array
     */
    private static function get_generic_usernames() {
        $settings = self::get_settings();
        $raw = is_array( $settings['generic_usernames'] )
            ? implode( ',', $settings['generic_usernames'] )
            : $settings['generic_usernames'];
        return array_filter( array_map( 'trim', explode( ',', strtolower( $raw ) ) ) );
    }

    /**
     * Get YMYL category slugs as array.
     *
     * @return array
     */
    private static function get_ymyl_categories() {
        $settings = self::get_settings();
        $raw = is_array( $settings['ymyl_categories'] )
            ? implode( ',', $settings['ymyl_categories'] )
            : $settings['ymyl_categories'];
        return array_filter( array_map( 'trim', explode( ',', strtolower( $raw ) ) ) );
    }

    /* ──────────────────────── Single Post Scan ── */

    /**
     * Scan a single post for E-E-A-T findings.
     *
     * @param int $post_id
     * @return array Array of finding arrays ready for model insert.
     */
    public static function scan_post( $post_id ) {
        $post = get_post( $post_id );
        if ( ! $post ) {
            return array();
        }

        $settings = self::get_settings();
        $findings = array();

        // Detect missing / generic author.
        $findings = array_merge( $findings, self::detect_missing_author( $post ) );

        // Detect missing bio.
        $findings = array_merge( $findings, self::detect_missing_bio( $post ) );

        // Detect missing author schema.
        $findings = array_merge( $findings, self::detect_missing_author_schema( $post ) );

        // Detect missing credentials in bio.
        $findings = array_merge( $findings, self::detect_missing_credentials( $post ) );

        // Detect no outbound citation sources.
        if ( $settings['check_sources'] ) {
            $findings = array_merge( $findings, self::detect_no_sources( $post ) );
        }

        // Detect missing review/fact-check attribution.
        if ( $settings['check_review_date'] ) {
            $findings = array_merge( $findings, self::detect_missing_review_date( $post ) );
        }

        return $findings;
    }

    /* ──────────────────────── Detector: Missing Author ── */

    /**
     * Check if post has no author or uses a generic username.
     *
     * @param WP_Post $post
     * @return array
     */
    public static function detect_missing_author( $post ) {
        $findings = array();
        $author_id = (int) $post->post_author;

        if ( $author_id === 0 ) {
            $findings[] = array(
                'post_id'         => $post->ID,
                'finding_type'    => 'missing_author',
                'severity'        => 'high',
                'detected_value'  => 'No author assigned',
                'context_snippet' => 'Post "' . self::truncate( $post->post_title, 60 ) . '" has no author.',
                'suggestion'      => 'Assign a real person as the author. Google values content with clear authorship.',
                'location'        => 'meta',
            );
            return $findings;
        }

        $author = get_userdata( $author_id );
        if ( ! $author ) {
            return $findings;
        }

        $username  = strtolower( $author->user_login );
        $generics  = self::get_generic_usernames();

        if ( in_array( $username, $generics, true ) ) {
            $findings[] = array(
                'post_id'         => $post->ID,
                'finding_type'    => 'missing_author',
                'severity'        => 'high',
                'detected_value'  => $author->user_login,
                'context_snippet' => 'Post authored by generic account "' . $author->user_login . '".',
                'suggestion'      => 'Replace with a named author who has expertise in this topic.',
                'location'        => 'meta',
            );
        }

        return $findings;
    }

    /* ──────────────────────── Detector: Missing Bio ── */

    /**
     * Check if the post's author has a biographical description.
     *
     * @param WP_Post $post
     * @return array
     */
    public static function detect_missing_bio( $post ) {
        $findings  = array();
        $author_id = (int) $post->post_author;

        if ( $author_id === 0 ) {
            return $findings; // Already flagged by missing_author.
        }

        $bio = get_the_author_meta( 'description', $author_id );

        if ( empty( trim( $bio ) ) ) {
            $author = get_userdata( $author_id );
            $name   = $author ? $author->display_name : 'Author #' . $author_id;

            $findings[] = array(
                'post_id'         => $post->ID,
                'finding_type'    => 'missing_bio',
                'severity'        => 'medium',
                'detected_value'  => $name,
                'context_snippet' => 'Author "' . $name . '" has no biographical description.',
                'suggestion'      => 'Add a bio highlighting expertise, experience, and credentials relevant to this content.',
                'location'        => 'meta',
            );
        }

        return $findings;
    }

    /* ──────────────────────── Detector: Missing Author Schema ── */

    /**
     * Check if post has Person schema markup for the author.
     *
     * Checks:
     * 1. Post meta `_almaseo_schema_type` for Person type.
     * 2. JSON-LD in post content for `"@type":"Person"`.
     * 3. Plugin-generated schema (looks for author in Article schema).
     *
     * @param WP_Post $post
     * @return array
     */
    public static function detect_missing_author_schema( $post ) {
        $findings = array();

        // Check plugin schema meta.
        $schema_type = get_post_meta( $post->ID, '_almaseo_schema_type', true );
        if ( $schema_type && stripos( $schema_type, 'person' ) !== false ) {
            return $findings; // Has Person schema.
        }

        // Check for JSON-LD Person in content.
        $content = $post->post_content;
        if ( preg_match( '/"@type"\s*:\s*"Person"/i', $content ) ) {
            return $findings;
        }

        // Check for author in Article schema meta.
        $schema_data = get_post_meta( $post->ID, '_almaseo_schema_data', true );
        if ( ! empty( $schema_data ) ) {
            $decoded = is_string( $schema_data ) ? json_decode( $schema_data, true ) : $schema_data;
            if ( is_array( $decoded ) && ! empty( $decoded['author'] ) ) {
                return $findings; // Article schema includes author.
            }
        }

        $findings[] = array(
            'post_id'         => $post->ID,
            'finding_type'    => 'missing_author_schema',
            'severity'        => 'medium',
            'detected_value'  => 'No Person schema',
            'context_snippet' => 'Post "' . self::truncate( $post->post_title, 60 ) . '" has no author/Person schema markup.',
            'suggestion'      => 'Add Person structured data for the author, or enable Article schema with author properties.',
            'location'        => 'meta',
        );

        return $findings;
    }

    /* ──────────────────────── Detector: Missing Credentials ── */

    /**
     * Check if the author bio contains credential indicators.
     *
     * Looks for: degrees (MD, PhD, CPA, etc.), job titles,
     * "years of experience", "certified", "licensed".
     *
     * @param WP_Post $post
     * @return array
     */
    public static function detect_missing_credentials( $post ) {
        $findings  = array();
        $author_id = (int) $post->post_author;

        if ( $author_id === 0 ) {
            return $findings;
        }

        $bio = get_the_author_meta( 'description', $author_id );

        // If no bio at all, skip — already flagged by missing_bio.
        if ( empty( trim( $bio ) ) ) {
            return $findings;
        }

        // Credential patterns.
        $credential_patterns = array(
            // Degrees (with comma or space prefix to avoid false positives like "AMD").
            '/(?:,\s*|\b)(?:MD|M\.D\.|DO|D\.O\.|PhD|Ph\.D\.|JD|J\.D\.|MBA|M\.B\.A\.|CPA|C\.P\.A\.|RN|R\.N\.|LPN|NP|PA-C|DDS|DMD|PharmD|EdD|PsyD|DVM|MSW|LCSW|LPC|LMFT)\b/',
            // Certification keywords.
            '/\b(?:certified|licensed|accredited|board[- ]certified|registered)\b/i',
            // Experience indicators.
            '/\b\d+\+?\s*years?\s+(?:of\s+)?experience\b/i',
            // Job title patterns.
            '/\b(?:Director|Manager|Specialist|Analyst|Consultant|Professor|Doctor|Engineer|Architect|Researcher|Editor|Journalist|Attorney|Lawyer|Nurse|Pharmacist|Therapist)\b/i',
            // "Expert in" / "specializes in".
            '/\b(?:expert\s+in|specializ(?:es|ing)\s+in|authority\s+on)\b/i',
        );

        $has_credentials = false;
        foreach ( $credential_patterns as $pattern ) {
            if ( preg_match( $pattern, $bio ) ) {
                $has_credentials = true;
                break;
            }
        }

        if ( ! $has_credentials ) {
            $author   = get_userdata( $author_id );
            $name     = $author ? $author->display_name : 'Author #' . $author_id;
            $severity = self::is_ymyl_post( $post->ID ) ? 'high' : 'low';

            $findings[] = array(
                'post_id'         => $post->ID,
                'finding_type'    => 'missing_credentials',
                'severity'        => $severity,
                'detected_value'  => $name,
                'context_snippet' => 'Author bio: "' . self::truncate( $bio, 80 ) . '"',
                'suggestion'      => 'Add credentials, qualifications, or experience indicators to the author bio.',
                'location'        => 'meta',
            );
        }

        return $findings;
    }

    /* ──────────────────────── Detector: No Sources ── */

    /**
     * Check if post content has no outbound citation links.
     *
     * Uses the existing content parser if available.
     *
     * @param WP_Post $post
     * @return array
     */
    public static function detect_no_sources( $post ) {
        $findings = array();
        $content  = $post->post_content;

        // Use the plugin's existing HTML parser if available.
        if ( function_exists( 'almaseo_parse_html_content' ) ) {
            $parsed = almaseo_parse_html_content( $content );
            $external_links = isset( $parsed['external_links'] ) ? $parsed['external_links'] : array();
        } else {
            // Fallback: simple regex for external links.
            $site_host = wp_parse_url( home_url(), PHP_URL_HOST );
            $external_links = array();

            if ( preg_match_all( '/<a\s[^>]*href=["\']?(https?:\/\/[^"\'>\s]+)/i', $content, $matches ) ) {
                foreach ( $matches[1] as $url ) {
                    $link_host = wp_parse_url( $url, PHP_URL_HOST );
                    if ( $link_host && $link_host !== $site_host ) {
                        $external_links[] = $url;
                    }
                }
            }
        }

        if ( empty( $external_links ) ) {
            $severity = self::is_ymyl_post( $post->ID ) ? 'high' : 'medium';

            $findings[] = array(
                'post_id'         => $post->ID,
                'finding_type'    => 'no_sources',
                'severity'        => $severity,
                'detected_value'  => '0 outbound links',
                'context_snippet' => 'Post "' . self::truncate( $post->post_title, 60 ) . '" has no external citation links.',
                'suggestion'      => 'Add links to authoritative sources that support your claims. This builds trust and helps with E-E-A-T.',
                'location'        => 'body',
            );
        }

        return $findings;
    }

    /* ──────────────────────── Detector: Missing Review Date ── */

    /**
     * Check if content lacks review/fact-check attribution.
     *
     * Looks for patterns like "Reviewed by", "Medically reviewed",
     * "Fact-checked by", "Verified by".
     *
     * @param WP_Post $post
     * @return array
     */
    public static function detect_missing_review_date( $post ) {
        $findings = array();
        $content  = $post->post_content;
        $title    = $post->post_title;
        $text     = $title . ' ' . wp_strip_all_tags( $content );

        $review_patterns = array(
            '/\b(?:reviewed|fact[- ]checked|verified|medically\s+reviewed|clinically\s+reviewed|editorially\s+reviewed)\s+by\b/i',
            '/\b(?:peer[- ]reviewed|expert[- ]reviewed)\b/i',
        );

        $has_review = false;
        foreach ( $review_patterns as $pattern ) {
            if ( preg_match( $pattern, $text ) ) {
                $has_review = true;
                break;
            }
        }

        if ( ! $has_review && self::is_ymyl_post( $post->ID ) ) {
            $findings[] = array(
                'post_id'         => $post->ID,
                'finding_type'    => 'missing_review_date',
                'severity'        => 'medium',
                'detected_value'  => 'No review attribution',
                'context_snippet' => 'YMYL post "' . self::truncate( $post->post_title, 60 ) . '" has no review/fact-check attribution.',
                'suggestion'      => 'Add a "Reviewed by [Expert Name]" attribution for YMYL content to signal editorial oversight.',
                'location'        => 'body',
            );
        }

        return $findings;
    }

    /* ──────────────────────── Full Site Scan ── */

    /**
     * Scan all published posts.
     *
     * Clears open findings first (preserving resolved/dismissed),
     * then processes in batches of 50.
     *
     * @return array { posts_scanned: int, findings_count: int }
     */
    public static function scan_all() {
        global $wpdb;

        $settings     = self::get_settings();
        $post_types   = ! empty( $settings['scan_post_types'] ) ? $settings['scan_post_types'] : array( 'post', 'page', 'product' );
        $placeholders = implode( ', ', array_fill( 0, count( $post_types ), '%s' ) );

        // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
        $total = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ({$placeholders})",
            $post_types
        ) );

        // Collect dismissed/resolved keys so we can skip re-inserting them.
        $dismissed_keys = AlmaSEO_EEAT_Model::get_dismissed_keys();

        // Clear only open findings — resolved/dismissed survive the re-scan.
        AlmaSEO_EEAT_Model::clear_open();

        $batch_size     = 50;
        $posts_scanned  = 0;
        $findings_count = 0;

        for ( $batch_offset = 0; $batch_offset < $total; $batch_offset += $batch_size ) {
            // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- table name from $wpdb->prefix
            $post_ids = $wpdb->get_col( $wpdb->prepare(
                "SELECT ID FROM {$wpdb->posts} WHERE post_status = 'publish' AND post_type IN ({$placeholders}) ORDER BY ID LIMIT %d OFFSET %d",
                array_merge( $post_types, array( $batch_size, $batch_offset ) )
            ) );

            foreach ( $post_ids as $pid ) {
                $findings = self::scan_post( (int) $pid );

                if ( ! empty( $findings ) ) {
                    // Filter out findings that were previously dismissed/resolved.
                    $findings = array_filter( $findings, function ( $f ) use ( $dismissed_keys ) {
                        $key = $f['post_id'] . ':' . $f['finding_type'];
                        return ! isset( $dismissed_keys[ $key ] );
                    } );

                    if ( ! empty( $findings ) ) {
                        $findings_count += AlmaSEO_EEAT_Model::insert_batch( $findings );
                    }
                }

                $posts_scanned++;
            }
        }

        // Store last scan timestamp.
        update_option( 'almaseo_eeat_last_scan', current_time( 'mysql', true ) );

        return array(
            'posts_scanned'  => $posts_scanned,
            'findings_count' => $findings_count,
        );
    }

    /* ──────────────────────── Health Score Integration ── */

    /**
     * Add E-E-A-T weight to health signal weights.
     *
     * Hooked to `almaseo_health_weights` filter.
     *
     * @param array $weights Signal weights.
     * @return array
     */
    public static function add_health_weight( $weights ) {
        $settings = self::get_settings();
        $weight   = (int) $settings['health_weight'];

        if ( $weight > 0 ) {
            $weights['eeat'] = $weight;
        }

        return $weights;
    }

    /**
     * Add E-E-A-T signal to health breakdown.
     *
     * Hooked to `almaseo_health_signals` filter.
     * Performs a lightweight check: author exists, has bio, has outbound links.
     *
     * @param array  $breakdown Existing signal breakdown.
     * @param int    $post_id   Post ID.
     * @param string $content   Rendered content.
     * @param array  $weights   Signal weights.
     * @return array
     */
    public static function add_health_signal( $breakdown, $post_id, $content, $weights ) {
        // Only add if weight is configured.
        if ( ! isset( $weights['eeat'] ) || $weights['eeat'] <= 0 ) {
            return $breakdown;
        }

        $post = get_post( $post_id );
        if ( ! $post ) {
            $breakdown['eeat'] = array( 'pass' => false, 'note' => 'Post not found.' );
            return $breakdown;
        }

        $checks_passed = 0;
        $checks_total  = 3;
        $notes         = array();

        // Check 1: Non-generic author.
        $author_id = (int) $post->post_author;
        if ( $author_id > 0 ) {
            $author   = get_userdata( $author_id );
            $generics = self::get_generic_usernames();
            if ( $author && ! in_array( strtolower( $author->user_login ), $generics, true ) ) {
                $checks_passed++;
            } else {
                $notes[] = 'generic author';
            }
        } else {
            $notes[] = 'no author';
        }

        // Check 2: Author has bio.
        if ( $author_id > 0 && ! empty( trim( get_the_author_meta( 'description', $author_id ) ) ) ) {
            $checks_passed++;
        } else {
            $notes[] = 'no bio';
        }

        // Check 3: Has outbound links.
        $site_host = wp_parse_url( home_url(), PHP_URL_HOST );
        $has_external = false;
        if ( preg_match_all( '/<a\s[^>]*href=["\']?(https?:\/\/[^"\'>\s]+)/i', $content, $matches ) ) {
            foreach ( $matches[1] as $url ) {
                $link_host = wp_parse_url( $url, PHP_URL_HOST );
                if ( $link_host && $link_host !== $site_host ) {
                    $has_external = true;
                    break;
                }
            }
        }
        if ( $has_external ) {
            $checks_passed++;
        } else {
            $notes[] = 'no citations';
        }

        $pass = ( $checks_passed >= 2 ); // Pass if at least 2 of 3.
        $note = $pass
            ? 'E-E-A-T signals present.'
            : 'Missing: ' . implode( ', ', $notes ) . '.';

        $breakdown['eeat'] = array( 'pass' => $pass, 'note' => $note );

        return $breakdown;
    }

    /* ──────────────────────── Helpers ── */

    /**
     * Check if a post is in a YMYL category.
     *
     * @param int $post_id
     * @return bool
     */
    private static function is_ymyl_post( $post_id ) {
        $ymyl_cats = self::get_ymyl_categories();
        if ( empty( $ymyl_cats ) ) {
            return false;
        }

        $post_cats = wp_get_post_categories( $post_id, array( 'fields' => 'slugs' ) );
        if ( ! is_array( $post_cats ) ) {
            return false;
        }

        $post_cat_slugs = array_map( 'strtolower', $post_cats );
        return ! empty( array_intersect( $ymyl_cats, $post_cat_slugs ) );
    }

    /**
     * Truncate a string to a max length.
     *
     * @param string $str
     * @param int    $max
     * @return string
     */
    private static function truncate( $str, $max = 80 ) {
        if ( strlen( $str ) <= $max ) {
            return $str;
        }
        return substr( $str, 0, $max ) . '...';
    }
}
