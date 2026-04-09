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
     * @return bool
     */
    public static function is_available() {
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
    public static function generate_batch( array $post_ids ) {
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

            $posts_payload[] = array(
                'post_id'   => $pid,
                'title'     => $post->post_title,
                'content'   => $content,
                'post_type' => $post->post_type,
                'excerpt'   => $post->post_excerpt ?: '',
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
    public static function generate_single( $post_id, $field = '' ) {
        $batch = self::generate_batch( array( $post_id ) );
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

    /**
     * Apply AI-generated metadata to a post, falling back to local generation.
     *
     * @param int   $post_id   The post ID.
     * @param array $fields    Optional specific fields to fill.
     * @param bool  $overwrite Whether to overwrite existing values.
     * @return array The generated/existing values for each field.
     */
    public static function apply( $post_id, $fields = array(), $overwrite = false ) {
        require_once __DIR__ . '/autofill-generator.php';

        // Try AI first
        $ai_result = self::generate_single( $post_id );

        if ( ! $ai_result ) {
            // Fall back to local generator
            return Autofill_Generator::apply( $post_id, $fields, $overwrite );
        }

        // Apply AI results using the same meta key map as Autofill_Generator
        $meta_map = array(
            'meta_title'       => array( '_almaseo_title', '_almaseo_meta_title' ),
            'meta_description' => array( '_almaseo_description', '_almaseo_meta_description' ),
            'focus_keyword'    => array( '_almaseo_focus_keyword' ),
            'og_title'         => array( '_almaseo_og_title' ),
            'og_description'   => array( '_almaseo_og_description' ),
        );

        $result = array();
        foreach ( $meta_map as $key => $meta_keys ) {
            if ( ! empty( $fields ) && ! in_array( $key, $fields, true ) ) {
                // Read existing value
                foreach ( $meta_keys as $mk ) {
                    $val = (string) get_post_meta( $post_id, $mk, true );
                    if ( ! empty( $val ) ) {
                        $result[ $key ] = $val;
                        break;
                    }
                }
                if ( ! isset( $result[ $key ] ) ) {
                    $result[ $key ] = '';
                }
                continue;
            }

            $current = '';
            foreach ( $meta_keys as $mk ) {
                $val = (string) get_post_meta( $post_id, $mk, true );
                if ( ! empty( $val ) ) {
                    $current = $val;
                    break;
                }
            }

            if ( $overwrite || empty( $current ) ) {
                $value = isset( $ai_result[ $key ] ) ? $ai_result[ $key ] : '';
                if ( ! empty( $value ) ) {
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
}
