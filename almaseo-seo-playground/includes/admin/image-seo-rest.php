<?php
/**
 * AlmaSEO Image SEO — Dashboard REST API
 *
 * Push endpoint for dashboard to send AI-generated alt text suggestions.
 *
 * @package AlmaSEO
 * @since   8.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Image_SEO_REST {

    const NS = 'almaseo/v1';

    /**
     * Register REST routes.
     */
    public static function init() {
        add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
    }

    /**
     * Register REST push endpoint.
     */
    public static function register_routes() {
        register_rest_route( self::NS, '/image-seo/push', array(
            'methods'             => WP_REST_Server::CREATABLE,
            'callback'            => array( __CLASS__, 'push_suggestions' ),
            'permission_callback' => 'almaseo_api_auth_check',
            'args'                => array(
                'post_id' => array( 'type' => 'integer', 'required' => true ),
                'images'  => array( 'type' => 'array',   'required' => true, 'items' => array( 'type' => 'object' ) ),
            ),
        ) );
    }

    /**
     * Handle dashboard push of AI image alt text suggestions.
     */
    public static function push_suggestions( WP_REST_Request $request ) {
        $post_id = absint( $request->get_param( 'post_id' ) );
        $images  = $request->get_param( 'images' );

        $post = get_post( $post_id );
        if ( ! $post ) {
            return new WP_Error( 'invalid_post', 'Post not found.', array( 'status' => 404 ) );
        }

        if ( ! is_array( $images ) || empty( $images ) ) {
            return new WP_Error( 'invalid_payload', 'images must be a non-empty array.', array( 'status' => 400 ) );
        }

        $sanitized = array();
        foreach ( $images as $img ) {
            if ( empty( $img['attachment_id'] ) && empty( $img['src'] ) ) {
                continue;
            }
            $entry = array(
                'suggested_alt'   => sanitize_text_field( $img['suggested_alt'] ?? '' ),
                'suggested_title' => sanitize_text_field( $img['suggested_title'] ?? '' ),
                'confidence'      => isset( $img['confidence'] ) ? max( 0, min( 1, (float) $img['confidence'] ) ) : 0.5,
                'is_decorative'   => ! empty( $img['is_decorative'] ),
            );
            if ( ! empty( $img['attachment_id'] ) ) {
                $entry['attachment_id'] = absint( $img['attachment_id'] );
            }
            if ( ! empty( $img['src'] ) ) {
                $entry['src'] = esc_url_raw( $img['src'] );
            }
            $sanitized[] = $entry;
        }

        if ( empty( $sanitized ) ) {
            return new WP_Error( 'no_valid_images', 'No valid image suggestions.', array( 'status' => 400 ) );
        }

        update_post_meta( $post_id, '_almaseo_image_seo_dashboard', wp_json_encode( $sanitized ) );

        return rest_ensure_response( array(
            'success' => true,
            'count'   => count( $sanitized ),
        ) );
    }

    /**
     * Get dashboard suggestions for a post's images.
     *
     * @param int $post_id Post ID.
     * @return array|null Array of image suggestions or null.
     */
    public static function get_suggestions( $post_id ) {
        $raw = get_post_meta( $post_id, '_almaseo_image_seo_dashboard', true );
        if ( ! $raw ) {
            return null;
        }
        $data = json_decode( $raw, true );
        return is_array( $data ) ? $data : null;
    }

    /**
     * Find a dashboard suggestion for a specific image source URL.
     *
     * @param int    $post_id Post ID.
     * @param string $src     Image source URL.
     * @return array|null Suggestion array or null.
     */
    public static function find_suggestion_by_src( $post_id, $src ) {
        $suggestions = self::get_suggestions( $post_id );
        if ( ! $suggestions ) {
            return null;
        }
        $src_basename = basename( parse_url( $src, PHP_URL_PATH ) );
        foreach ( $suggestions as $s ) {
            if ( ! empty( $s['src'] ) && basename( parse_url( $s['src'], PHP_URL_PATH ) ) === $src_basename ) {
                return $s;
            }
        }
        return null;
    }
}
