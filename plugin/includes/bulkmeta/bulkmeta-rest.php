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
        $missing = $request->get_param('missing');
        if (!empty($missing)) {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'key' => '_almaseo_meta_title',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_almaseo_meta_title',
                    'value' => '',
                    'compare' => '='
                ),
                array(
                    'key' => '_almaseo_meta_description',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_almaseo_meta_description',
                    'value' => '',
                    'compare' => '='
                )
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