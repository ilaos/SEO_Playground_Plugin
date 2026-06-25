<?php
/**
 * AlmaSEO AI-Powered Metadata Generator
 *
 * Generates SEO titles, meta descriptions, and focus keywords using AI
 * via the AlmaSEO dashboard API. Falls back to local Autofill_Generator
 * when the site is not connected or the API is unavailable.
 *
 * @package AlmaSEO
 * @since 8.10.0
 */

namespace AlmaSEO\BulkMeta;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AI_Autofill_Generator {

    /**
     * Check whether AI autofill is available (site connected to AlmaSEO dashboard).
     *
     * Defers to the canonical connection helper so JWT-host sites (where
     * `almaseo_app_password` holds a JWT instead of an app password) are
     * treated as connected. The dashboard's outbound auth accepts either
     * credential via Basic Auth.
     *
     * @return bool
     */
    public static function is_available() {
        if ( function_exists( 'seo_playground_is_alma_connected' ) ) {
            if ( ! seo_playground_is_alma_connected() ) {
                return false;
            }
        }
        // Outbound calls still need a credential in the password slot.
        $user = get_option( 'almaseo_connected_user', '' );
        $pass = get_option( 'almaseo_app_password', '' );
        return ! empty( $user ) && ! empty( $pass );
    }

    /**
     * Generate AI metadata for a batch of posts.
     *
     * @param array $post_ids Array of post IDs (max 10).
     * @return array|false Array of results keyed by post_id, or false on failure.
     */
    public static function generate_batch( array $post_ids, array $overrides_by_id = array() ) {
        if ( ! self::is_available() ) {
            return false;
        }

        $post_ids = array_slice( array_map( 'intval', $post_ids ), 0, 10 );
        if ( empty( $post_ids ) ) {
            return false;
        }

        // Build posts payload
        $posts_payload = array();
        foreach ( $post_ids as $pid ) {
            $post = get_post( $pid );
            if ( ! $post ) {
                continue;
            }

            $clean_html = strip_shortcodes( $post->post_content );
            $content    = wp_strip_all_tags( $clean_html );
            // Trim to ~2000 chars at a word boundary
            if ( strlen( $content ) > 2000 ) {
                $content = substr( $content, 0, 2000 );
                $last_space = strrpos( $content, ' ' );
                if ( $last_space > 1500 ) {
                    $content = substr( $content, 0, $last_space );
                }
            }

            // ── Enrichment signals — give the AI the context a human optimiser
            // would have: the target keyword, any existing meta to refine, the
            // section headings, and the post's taxonomy terms.
            // Prefer live values passed from the editor (Option A) over saved meta.
            $ov            = isset( $overrides_by_id[ $pid ] ) ? $overrides_by_id[ $pid ] : array();
            $focus_keyword = array_key_exists( 'focus_keyword', $ov ) ? $ov['focus_keyword'] : get_post_meta( $pid, '_almaseo_focus_keyword', true );
            $current_title = array_key_exists( 'current_title', $ov ) ? $ov['current_title'] : get_post_meta( $pid, '_almaseo_title', true );
            $current_desc  = array_key_exists( 'current_desc', $ov )  ? $ov['current_desc']  : get_post_meta( $pid, '_almaseo_description', true );

            // Section headings (H1–H3) from the raw content, first 8 unique.
            $headings = array();
            if ( preg_match_all( '/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', $post->post_content, $hmatch ) ) {
                foreach ( $hmatch[1] as $h ) {
                    $h = trim( wp_strip_all_tags( $h ) );
                    if ( $h !== '' ) {
                        $headings[] = $h;
                    }
                }
            }
            $headings = array_slice( array_values( array_unique( $headings ) ), 0, 8 );

            // Categories + tags (names), first 10 unique. Pages return none.
            $terms = array();
            foreach ( array( 'category', 'post_tag' ) as $tax ) {
                $names = wp_get_post_terms( $pid, $tax, array( 'fields' => 'names' ) );
                if ( ! is_wp_error( $names ) && ! empty( $names ) ) {
                    $terms = array_merge( $terms, $names );
                }
            }
            $terms = array_slice( array_values( array_unique( $terms ) ), 0, 10 );

            $posts_payload[] = array(
                'post_id'       => $pid,
                'title'         => $post->post_title,
                'content'       => $content,
                'post_type'     => $post->post_type,
                'excerpt'       => $post->post_excerpt ?: '',
                'focus_keyword' => is_string( $focus_keyword ) ? $focus_keyword : '',
                'current_title' => is_string( $current_title ) ? $current_title : '',
                'current_desc'  => is_string( $current_desc ) ? $current_desc : '',
                'headings'      => $headings,
                'terms'         => $terms,
            );
        }

        if ( empty( $posts_payload ) ) {
            return false;
        }

        // Make API request
        $api_url  = 'https://api.almaseo.com/api/plugin/ai-autofill';
        $username = get_option( 'almaseo_connected_user', '' );
        $password = get_option( 'almaseo_app_password', '' );
        $site_url = get_site_url();

        $response = wp_remote_post( $api_url, array(
            'timeout' => 35,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
                'Content-Type'  => 'application/json',
            ),
            'body' => wp_json_encode( array(
                'site_url' => $site_url,
                'posts'    => $posts_payload,
            ) ),
        ) );

        if ( is_wp_error( $response ) ) {
            return false;
        }

        $status = wp_remote_retrieve_response_code( $response );
        if ( $status !== 200 ) {
            return false;
        }

        $body = json_decode( wp_remote_retrieve_body( $response ), true );
        if ( ! is_array( $body ) || empty( $body['success'] ) || empty( $body['results'] ) ) {
            return false;
        }

        // Key results by post_id for easy lookup
        $keyed = array();
        foreach ( $body['results'] as $r ) {
            $pid = intval( $r['post_id'] ?? 0 );
            if ( $pid ) {
                $keyed[ $pid ] = array(
                    'meta_title'       => sanitize_text_field( $r['meta_title'] ?? '' ),
                    'meta_description' => sanitize_text_field( $r['meta_description'] ?? '' ),
                    'focus_keyword'    => sanitize_text_field( $r['focus_keyword'] ?? '' ),
                    'og_title'         => sanitize_text_field( $r['og_title'] ?? '' ),
                    'og_description'   => sanitize_text_field( $r['og_description'] ?? '' ),
                );
            }
        }

        if ( empty( $keyed ) ) {
            return false;
        }

        // Attach profile suggestions if the API returned them
        if ( ! empty( $body['profile_suggestions'] ) ) {
            $keyed['_profile_suggestions'] = $body['profile_suggestions'];
        }

        return $keyed;
    }

    /**
     * Generate AI metadata for a single post.
     *
     * @param int    $post_id The post ID.
     * @param string $field   Optional specific field ('title', 'description', 'keyword').
     * @return array|false Generated metadata or false on failure.
     */
    public static function generate_single( $post_id, $field = '', $overrides = array() ) {
        $by_id = ! empty( $overrides ) ? array( (int) $post_id => $overrides ) : array();
        $batch = self::generate_batch( array( $post_id ), $by_id );
        if ( ! $batch || ! isset( $batch[ $post_id ] ) ) {
            return false;
        }

        $result = $batch[ $post_id ];

        // Carry over profile suggestions from the batch response
        if ( isset( $batch['_profile_suggestions'] ) ) {
            $result['_profile_suggestions'] = $batch['_profile_suggestions'];
        }

        return $result;
    }

}
