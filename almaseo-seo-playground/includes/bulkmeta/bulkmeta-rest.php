<?php
/**
 * AlmaSEO Bulk Metadata REST API
 * 
 * @package AlmaSEO
 * @since 6.3.0
 */

namespace AlmaSEO\BulkMeta;

if (!defined('ABSPATH')) {
    exit;
}

class BulkMeta_REST {
    
    /**
     * Initialize REST endpoints
     */
    public static function init() {
        add_action('rest_api_init', array(__CLASS__, 'register_routes'));
    }
    
    /**
     * Register REST routes
     */
    public static function register_routes() {
        $namespace = 'almaseo/v1';
        
        // List posts endpoint
        register_rest_route($namespace, '/bulkmeta', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'get_posts'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
            'args' => array(
                'type' => array(
                    'type' => 'string',
                    'default' => 'post',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'status' => array(
                    'type' => 'string',
                    'default' => 'publish,draft',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'taxonomy' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'term' => array(
                    'type' => 'integer',
                    'sanitize_callback' => 'absint'
                ),
                'from' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'to' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'search' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'missing' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'page' => array(
                    'type' => 'integer',
                    'default' => 1,
                    'sanitize_callback' => 'absint'
                ),
                'per_page' => array(
                    'type' => 'integer',
                    'default' => 20,
                    'sanitize_callback' => 'absint'
                ),
                'orderby' => array(
                    'type' => 'string',
                    'default' => 'modified',
                    'enum' => array('modified', 'title', 'status'),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'order' => array(
                    'type' => 'string',
                    'default' => 'DESC',
                    'enum' => array('ASC', 'DESC'),
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        // Update post metadata endpoint
        register_rest_route($namespace, '/bulkmeta/(?P<id>\d+)', array(
            'methods' => 'PATCH',
            'callback' => array(__CLASS__, 'update_post'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'sanitize_callback' => 'absint'
                ),
                'meta_title' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'meta_description' => array(
                    'type' => 'string',
                    'sanitize_callback' => 'sanitize_textarea_field'
                )
            )
        ));
        
        // Reset post metadata endpoint
        register_rest_route($namespace, '/bulkmeta/reset/(?P<id>\d+)', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => array(__CLASS__, 'reset_post'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
            'args' => array(
                'id' => array(
                    'type' => 'integer',
                    'required' => true,
                    'sanitize_callback' => 'absint'
                )
            )
        ));
        
        // Bulk operations endpoint
        register_rest_route($namespace, '/bulkmeta/bulk', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => array(__CLASS__, 'bulk_operation'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
            'args' => array(
                'ids' => array(
                    'type' => 'array',
                    'required' => true,
                    'items' => array(
                        'type' => 'integer'
                    )
                ),
                'op' => array(
                    'type' => 'string',
                    'required' => true,
                    'enum' => array('reset', 'append', 'prepend', 'replace'),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'field' => array(
                    'type' => 'string',
                    'enum' => array('title', 'description'),
                    'sanitize_callback' => 'sanitize_text_field'
                ),
                'args' => array(
                    'type' => 'object'
                )
            )
        ));
        
        // Get post types endpoint
        register_rest_route($namespace, '/bulkmeta/types', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'get_post_types'),
            'permission_callback' => array(__CLASS__, 'check_permission')
        ));
        
        // Get taxonomies endpoint
        register_rest_route($namespace, '/bulkmeta/taxonomies', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'get_taxonomies'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
            'args' => array(
                'post_type' => array(
                    'type' => 'string',
                    'default' => 'post',
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        // Get terms endpoint
        register_rest_route($namespace, '/bulkmeta/terms', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'get_terms'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
            'args' => array(
                'taxonomy' => array(
                    'type' => 'string',
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field'
                )
            )
        ));
        
        // Auto-fill preview endpoint — shows what would be generated
        register_rest_route($namespace, '/bulkmeta/autofill/preview', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => array(__CLASS__, 'autofill_preview'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
            'args' => array(
                'ids' => array(
                    'type' => 'array',
                    'required' => true,
                    'items' => array( 'type' => 'integer' )
                ),
                'fields' => array(
                    'type' => 'array',
                    'default' => array(),
                    'items' => array( 'type' => 'string' )
                ),
                'overwrite' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'mode' => array(
                    'type' => 'string',
                    'default' => 'auto',
                    'enum' => array( 'auto', 'basic', 'ai' ),
                )
            )
        ));

        // Auto-fill apply endpoint — generates and saves metadata
        register_rest_route($namespace, '/bulkmeta/autofill', array(
            'methods' => \WP_REST_Server::CREATABLE,
            'callback' => array(__CLASS__, 'autofill_apply'),
            'permission_callback' => array(__CLASS__, 'check_permission'),
            'args' => array(
                'ids' => array(
                    'type' => 'array',
                    'required' => true,
                    'items' => array( 'type' => 'integer' )
                ),
                'fields' => array(
                    'type' => 'array',
                    'default' => array(),
                    'items' => array( 'type' => 'string' )
                ),
                'overwrite' => array(
                    'type' => 'boolean',
                    'default' => false
                ),
                'mode' => array(
                    'type' => 'string',
                    'default' => 'auto',
                    'enum' => array( 'auto', 'basic', 'ai' ),
                )
            )
        ));

        // Test endpoint for debugging
        register_rest_route($namespace, '/bulkmeta/test', array(
            'methods' => \WP_REST_Server::READABLE,
            'callback' => array(__CLASS__, 'test_endpoint'),
            'permission_callback' => array(__CLASS__, 'check_permission')
        ));
    }
    
    /**
     * Check permission for REST requests
     */
    public static function check_permission() {
        if (!almaseo_is_pro()) {
            return new \WP_Error(
                'pro_required',
                __('This feature requires AlmaSEO Pro.', 'almaseo'),
                array('status' => 403)
            );
        }
        
        return current_user_can('manage_options');
    }
    
    /**
     * Get posts endpoint
     */
    public static function get_posts($request) {
        // Parse type parameter safely with defaults
        $types = $request->get_param('type');
        $types = $types ? array_map('sanitize_key', wp_parse_list($types)) : array('post', 'page');
        
        // Parse status parameter safely with defaults
        $status = $request->get_param('status');
        $status = $status ? array_map('sanitize_key', wp_parse_list($status)) : array('publish', 'draft');
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BulkMeta REST: Types: ' . implode(',', $types) . ', Status: ' . implode(',', $status));
        }
        
        // Build WP_Query args directly
        $args = array(
            'post_type' => $types,
            'post_status' => $status,
            'posts_per_page' => max(1, (int) $request->get_param('per_page') ?: 20),
            'paged' => max(1, (int) $request->get_param('page') ?: 1),
            'orderby' => $request->get_param('orderby') ?: 'modified',
            'order' => $request->get_param('order') ?: 'DESC',
            'no_found_rows' => false, // Important: we need pagination info
            'fields' => 'ids' // Get IDs only for performance
        );
        
        // Handle search
        $search = $request->get_param('search');
        if (!empty($search)) {
            $args['s'] = $search;
        }
        
        // Handle taxonomy/term filter
        $taxonomy = $request->get_param('taxonomy');
        $term = $request->get_param('term');
        if (!empty($taxonomy) && !empty($term)) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $taxonomy,
                    'field' => 'term_id',
                    'terms' => $term
                )
            );
        }
        
        // Handle date range
        $from = $request->get_param('from');
        $to = $request->get_param('to');
        if (!empty($from) || !empty($to)) {
            $date_query = array();
            if (!empty($from)) {
                $date_query['after'] = $from;
            }
            if (!empty($to)) {
                $date_query['before'] = $to;
            }
            $args['date_query'] = array($date_query);
        }
        
        // Handle missing metadata filter
        // A post is "missing" if BOTH the primary AND fallback meta keys are empty/absent.
        // We use a nested meta_query: (title primary empty AND title fallback empty) OR (desc primary empty AND desc fallback empty)
        $missing = $request->get_param('missing');
        if (!empty($missing)) {
            $args['meta_query'] = array(
                'relation' => 'OR',
                // Title missing: both _almaseo_meta_title AND _almaseo_title are empty/absent
                array(
                    'relation' => 'AND',
                    array(
                        'relation' => 'OR',
                        array( 'key' => '_almaseo_meta_title', 'compare' => 'NOT EXISTS' ),
                        array( 'key' => '_almaseo_meta_title', 'value' => '', 'compare' => '=' ),
                    ),
                    array(
                        'relation' => 'OR',
                        array( 'key' => '_almaseo_title', 'compare' => 'NOT EXISTS' ),
                        array( 'key' => '_almaseo_title', 'value' => '', 'compare' => '=' ),
                    ),
                ),
                // Description missing: both _almaseo_meta_description AND _almaseo_desc are empty/absent
                array(
                    'relation' => 'AND',
                    array(
                        'relation' => 'OR',
                        array( 'key' => '_almaseo_meta_description', 'compare' => 'NOT EXISTS' ),
                        array( 'key' => '_almaseo_meta_description', 'value' => '', 'compare' => '=' ),
                    ),
                    array(
                        'relation' => 'OR',
                        array( 'key' => '_almaseo_desc', 'compare' => 'NOT EXISTS' ),
                        array( 'key' => '_almaseo_desc', 'value' => '', 'compare' => '=' ),
                    ),
                ),
            );
        }
        
        // Execute query
        $query = new \WP_Query($args);
        
        // Debug logging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BulkMeta REST: Query found ' . $query->found_posts . ' posts');
            error_log('BulkMeta REST: Post IDs: ' . implode(', ', $query->posts));
        }
        
        // Build response items using array_map for cleaner code
        $items = array_map(function($post_id) {
            // Get metadata - check both possible meta keys
            $t = (string) get_post_meta($post_id, '_almaseo_meta_title', true);
            if (empty($t)) {
                $t = (string) get_post_meta($post_id, '_almaseo_title', true);
            }
            
            $d = (string) get_post_meta($post_id, '_almaseo_meta_description', true);
            if (empty($d)) {
                $d = (string) get_post_meta($post_id, '_almaseo_desc', true);
            }
            
            // Get post object for additional data
            $post = get_post($post_id);
            $post_type_obj = get_post_type_object(get_post_type($post_id));
            
            return array(
                'id' => $post_id,
                'title' => get_the_title($post_id),
                'type' => get_post_type($post_id),
                'type_label' => $post_type_obj ? $post_type_obj->labels->singular_name : get_post_type($post_id),
                'status' => get_post_status($post_id),
                'updated' => get_post_modified_time('c', true, $post_id),
                'seo_title' => $t,
                'meta_title' => $t, // Alias for compatibility
                'meta_desc' => $d,
                'meta_description' => $d, // Alias for compatibility  
                'title_chars' => mb_strlen(wp_strip_all_tags($t)),
                'desc_chars' => mb_strlen(wp_strip_all_tags($d)),
                'title_fallback' => empty($t) ? $post->post_title : '',
                'desc_fallback' => empty($d) ? wp_trim_words(strip_shortcodes($post->post_content ?? ''), 30, '...') : '',
                'edit_link' => get_edit_post_link($post_id, 'raw'),
                'view_link' => get_permalink($post_id)
            );
        }, $query->posts);
        
        // Debug final response
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BulkMeta REST: Returning ' . count($items) . ' items');
        }
        
        // Return items directly as array with headers for pagination
        $response = rest_ensure_response($items);
        $response->header('X-WP-Total', (int) $query->found_posts);
        $response->header('X-WP-TotalPages', (int) $query->max_num_pages);
        
        return $response;
    }
    
    /**
     * Update post metadata
     */
    public static function update_post($request) {
        $post_id = $request->get_param('id');
        $data = array();
        
        if ($request->has_param('meta_title')) {
            $data['meta_title'] = $request->get_param('meta_title');
        }
        
        if ($request->has_param('meta_description')) {
            $data['meta_description'] = $request->get_param('meta_description');
        }
        
        if (empty($data)) {
            return new \WP_Error(
                'no_data',
                __('No data provided.', 'almaseo'),
                array('status' => 400)
            );
        }
        
        $result = BulkMeta_Controller::update_post_meta($post_id, $data);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return new \WP_REST_Response($result, 200);
    }
    
    /**
     * Reset post metadata
     */
    public static function reset_post($request) {
        $post_id = $request->get_param('id');
        $result = BulkMeta_Controller::reset_post_meta($post_id);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return new \WP_REST_Response($result, 200);
    }
    
    /**
     * Bulk operation handler
     */
    public static function bulk_operation($request) {
        $data = array(
            'ids' => $request->get_param('ids'),
            'op' => $request->get_param('op'),
            'field' => $request->get_param('field'),
            'args' => $request->get_param('args')
        );
        
        $result = BulkMeta_Controller::bulk_operation($data);
        
        if (is_wp_error($result)) {
            return $result;
        }
        
        return new \WP_REST_Response($result, 200);
    }
    
    /**
     * Get post types
     */
    public static function get_post_types($request) {
        $types = BulkMeta_Controller::get_post_types();
        return new \WP_REST_Response($types, 200);
    }
    
    /**
     * Get taxonomies
     */
    public static function get_taxonomies($request) {
        $post_type = $request->get_param('post_type');
        $taxonomies = BulkMeta_Controller::get_taxonomies($post_type);
        return new \WP_REST_Response($taxonomies, 200);
    }
    
    /**
     * Get terms
     */
    public static function get_terms($request) {
        $taxonomy = $request->get_param('taxonomy');
        
        $terms = get_terms(array(
            'taxonomy' => $taxonomy,
            'hide_empty' => false,
            'orderby' => 'name',
            'order' => 'ASC'
        ));
        
        if (is_wp_error($terms)) {
            return $terms;
        }
        
        $term_list = array();
        foreach ($terms as $term) {
            $term_list[] = array(
                'id' => $term->term_id,
                'name' => $term->name,
                'count' => $term->count
            );
        }
        
        return new \WP_REST_Response($term_list, 200);
    }
    
    /**
     * Determine whether to use AI mode based on the request mode parameter.
     *
     * @param string $mode 'auto', 'basic', or 'ai'
     * @return bool Whether to attempt AI generation.
     */
    private static function should_use_ai( $mode ) {
        if ( $mode === 'basic' ) {
            return false;
        }
        if ( $mode === 'ai' ) {
            return true;
        }
        // 'auto': use AI if connected
        require_once __DIR__ . '/ai-autofill-generator.php';
        return AI_Autofill_Generator::is_available();
    }

    /**
     * Auto-fill preview — returns what would be generated without saving.
     */
    public static function autofill_preview( $request ) {
        require_once __DIR__ . '/autofill-generator.php';

        $ids       = array_map( 'intval', $request->get_param( 'ids' ) );
        $fields    = $request->get_param( 'fields' ) ?: array();
        $overwrite = (bool) $request->get_param( 'overwrite' );
        $mode      = $request->get_param( 'mode' ) ?: 'auto';
        $use_ai    = self::should_use_ai( $mode );
        $previews  = array();

        // If AI mode, batch-fetch AI results for all IDs at once
        $ai_results = null;
        if ( $use_ai ) {
            require_once __DIR__ . '/ai-autofill-generator.php';
            $ai_results = AI_Autofill_Generator::generate_batch( $ids );
        }

        foreach ( $ids as $post_id ) {
            $post = get_post( $post_id );
            if ( ! $post ) {
                continue;
            }

            // Use AI result for this post, or fall back to local
            if ( $ai_results && isset( $ai_results[ $post_id ] ) ) {
                $generated = $ai_results[ $post_id ];
            } else {
                $generated = Autofill_Generator::generate_all( $post );
            }

            $current = array(
                'meta_title'       => (string) get_post_meta( $post_id, '_almaseo_meta_title', true ),
                'meta_description' => (string) get_post_meta( $post_id, '_almaseo_meta_description', true ),
                'focus_keyword'    => (string) get_post_meta( $post_id, '_almaseo_focus_keyword', true ),
                'og_title'         => (string) get_post_meta( $post_id, '_almaseo_og_title', true ),
                'og_description'   => (string) get_post_meta( $post_id, '_almaseo_og_description', true ),
            );

            $preview = array(
                'id'    => $post_id,
                'title' => $post->post_title,
                'ai'    => ( $ai_results && isset( $ai_results[ $post_id ] ) ),
            );

            foreach ( $generated as $key => $value ) {
                if ( ! empty( $fields ) && ! in_array( $key, $fields, true ) ) {
                    continue;
                }
                $will_fill = $overwrite || empty( $current[ $key ] );
                $preview[ $key ] = array(
                    'current'   => $current[ $key ],
                    'generated' => $value,
                    'will_fill' => $will_fill,
                );
            }

            $previews[] = $preview;
        }

        return rest_ensure_response( $previews );
    }

    /**
     * Auto-fill apply — generates and saves metadata for selected posts.
     */
    public static function autofill_apply( $request ) {
        require_once __DIR__ . '/autofill-generator.php';

        $ids       = array_map( 'intval', $request->get_param( 'ids' ) );
        $fields    = $request->get_param( 'fields' ) ?: array();
        $overwrite = (bool) $request->get_param( 'overwrite' );
        $mode      = $request->get_param( 'mode' ) ?: 'auto';
        $use_ai    = self::should_use_ai( $mode );

        // If AI mode, batch-fetch AI results for all IDs at once
        $ai_results = null;
        if ( $use_ai ) {
            require_once __DIR__ . '/ai-autofill-generator.php';
            $ai_results = AI_Autofill_Generator::generate_batch( $ids );
        }

        $results = array(
            'success'  => 0,
            'skipped'  => 0,
            'failed'   => 0,
            'ai_used'  => false,
            'details'  => array(),
        );

        if ( $ai_results ) {
            $results['ai_used'] = true;
        }

        foreach ( $ids as $post_id ) {
            if ( ! current_user_can( 'edit_post', $post_id ) ) {
                $results['failed']++;
                continue;
            }

            // If we have AI results for this post, apply them directly
            if ( $ai_results && isset( $ai_results[ $post_id ] ) ) {
                $applied = self::apply_ai_result( $post_id, $ai_results[ $post_id ], $fields, $overwrite );
            } else {
                // Fall back to local generator
                $applied = Autofill_Generator::apply( $post_id, $fields, $overwrite );
            }

            if ( ! empty( $applied ) ) {
                $results['success']++;
                $results['details'][] = array(
                    'id'     => $post_id,
                    'filled' => $applied,
                    'ai'     => ( $ai_results && isset( $ai_results[ $post_id ] ) ),
                );
            } else {
                $results['skipped']++;
            }
        }

        return rest_ensure_response( $results );
    }

    /**
     * Apply a single AI-generated result to a post's meta fields.
     *
     * @param int   $post_id    The post ID.
     * @param array $ai_data    AI-generated metadata.
     * @param array $fields     Optional specific fields to fill.
     * @param bool  $overwrite  Whether to overwrite existing values.
     * @return array Applied values.
     */
    private static function apply_ai_result( $post_id, $ai_data, $fields = array(), $overwrite = false ) {
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
                $value = isset( $ai_data[ $key ] ) ? sanitize_text_field( $ai_data[ $key ] ) : '';
                if ( ! empty( $value ) ) {
                    foreach ( $meta_keys as $mk ) {
                        update_post_meta( $post_id, $mk, $value );
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

    /**
     * Test endpoint for debugging
     */
    public static function test_endpoint($request) {
        // Simple test query to verify database access
        $test_query = new \WP_Query(array(
            'post_type' => 'post',
            'posts_per_page' => 5,
            'post_status' => 'any'
        ));
        
        $test_data = array(
            'message' => 'BulkMeta REST API is working',
            'timestamp' => current_time('c'),
            'user_can_manage' => current_user_can('manage_options'),
            'test_posts' => array(
                'found' => $test_query->found_posts,
                'sample_ids' => $test_query->posts
            ),
            'meta_keys_check' => array(
                '_almaseo_meta_title' => 'Primary meta title key',
                '_almaseo_title' => 'Fallback meta title key',
                '_almaseo_meta_description' => 'Primary meta description key',
                '_almaseo_desc' => 'Fallback meta description key'
            )
        );
        
        return rest_ensure_response($test_data);
    }
}

// Initialize
add_action('init', array(__NAMESPACE__ . '\\BulkMeta_REST', 'init'));