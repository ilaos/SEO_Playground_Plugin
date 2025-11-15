<?php
/**
 * AlmaSEO Redirects REST API
 * 
 * @package AlmaSEO
 * @subpackage Redirects
 * @since 6.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AlmaSEO_Redirects_REST {
    
    /**
     * REST namespace
     */
    const NAMESPACE = 'almaseo/v1';
    
    /**
     * Register REST routes
     */
    public function register_routes() {
        // List/Create redirects
        register_rest_route(self::NAMESPACE, '/redirects', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_redirects'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => array(
                    'page' => array(
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                    ),
                    'per_page' => array(
                        'default' => 20,
                        'sanitize_callback' => 'absint',
                    ),
                    'search' => array(
                        'default' => '',
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'orderby' => array(
                        'default' => 'created_at',
                        'sanitize_callback' => 'sanitize_key',
                    ),
                    'order' => array(
                        'default' => 'DESC',
                        'sanitize_callback' => function($value) {
                            return in_array(strtoupper($value), array('ASC', 'DESC')) ? strtoupper($value) : 'DESC';
                        }
                    ),
                ),
            ),
            array(
                'methods' => WP_REST_Server::CREATABLE,
                'callback' => array($this, 'create_redirect'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => array(
                    'source' => array(
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'target' => array(
                        'required' => true,
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'status' => array(
                        'default' => 301,
                        'sanitize_callback' => 'absint',
                    ),
                    'is_enabled' => array(
                        'default' => 1,
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ),
        ));
        
        // Get/Update/Delete single redirect
        register_rest_route(self::NAMESPACE, '/redirects/(?P<id>\d+)', array(
            array(
                'methods' => WP_REST_Server::READABLE,
                'callback' => array($this, 'get_redirect'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ),
                ),
            ),
            array(
                'methods' => WP_REST_Server::EDITABLE,
                'callback' => array($this, 'update_redirect'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ),
                    'source' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'target' => array(
                        'sanitize_callback' => 'sanitize_text_field',
                    ),
                    'status' => array(
                        'sanitize_callback' => 'absint',
                    ),
                    'is_enabled' => array(
                        'sanitize_callback' => 'absint',
                    ),
                ),
            ),
            array(
                'methods' => WP_REST_Server::DELETABLE,
                'callback' => array($this, 'delete_redirect'),
                'permission_callback' => array($this, 'check_permission'),
                'args' => array(
                    'id' => array(
                        'validate_callback' => function($param) {
                            return is_numeric($param);
                        }
                    ),
                ),
            ),
        ));
        
        // Toggle redirect
        register_rest_route(self::NAMESPACE, '/redirects/(?P<id>\d+)/toggle', array(
            'methods' => 'PATCH',
            'callback' => array($this, 'toggle_redirect'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'id' => array(
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ),
            ),
        ));
        
        // Bulk actions
        register_rest_route(self::NAMESPACE, '/redirects/bulk', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'bulk_action'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'action' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_key',
                ),
                'ids' => array(
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_array($param);
                    }
                ),
            ),
        ));
        
        // Test redirect
        register_rest_route(self::NAMESPACE, '/redirects/test', array(
            'methods' => WP_REST_Server::CREATABLE,
            'callback' => array($this, 'test_redirect'),
            'permission_callback' => array($this, 'check_permission'),
            'args' => array(
                'source' => array(
                    'required' => true,
                    'sanitize_callback' => 'sanitize_text_field',
                ),
            ),
        ));
    }
    
    /**
     * Check permission for REST requests
     */
    public function check_permission() {
        return current_user_can('manage_options');
    }
    
    /**
     * Get redirects list
     */
    public function get_redirects($request) {
        require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
        
        $args = array(
            'page' => $request->get_param('page'),
            'per_page' => $request->get_param('per_page'),
            'search' => $request->get_param('search'),
            'orderby' => $request->get_param('orderby'),
            'order' => $request->get_param('order'),
        );
        
        $result = AlmaSEO_Redirects_Model::get_redirects($args);
        
        return new WP_REST_Response($result, 200);
    }
    
    /**
     * Get single redirect
     */
    public function get_redirect($request) {
        require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
        
        $id = $request->get_param('id');
        $redirect = AlmaSEO_Redirects_Model::get_redirect($id);
        
        if (!$redirect) {
            return new WP_Error('not_found', __('Redirect not found.', 'almaseo'), array('status' => 404));
        }
        
        return new WP_REST_Response($redirect, 200);
    }
    
    /**
     * Create new redirect
     */
    public function create_redirect($request) {
        require_once plugin_dir_path(__FILE__) . 'redirects-controller.php';
        
        $data = array(
            'source' => $request->get_param('source'),
            'target' => $request->get_param('target'),
            'status' => $request->get_param('status'),
            'is_enabled' => $request->get_param('is_enabled'),
        );
        
        // Validate data
        $validation = AlmaSEO_Redirects_Controller::validate_redirect_data($data);
        
        if (is_wp_error($validation)) {
            return $validation;
        }
        
        require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
        
        $id = AlmaSEO_Redirects_Model::create_redirect($data);
        
        if (!$id) {
            return new WP_Error('create_failed', __('Failed to create redirect.', 'almaseo'), array('status' => 500));
        }
        
        $redirect = AlmaSEO_Redirects_Model::get_redirect($id);
        
        return new WP_REST_Response($redirect, 201);
    }
    
    /**
     * Update redirect
     */
    public function update_redirect($request) {
        require_once plugin_dir_path(__FILE__) . 'redirects-controller.php';
        
        $id = $request->get_param('id');
        
        // Check if redirect exists
        require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
        $existing = AlmaSEO_Redirects_Model::get_redirect($id);
        
        if (!$existing) {
            return new WP_Error('not_found', __('Redirect not found.', 'almaseo'), array('status' => 404));
        }
        
        $data = array();
        
        // Only include provided fields
        foreach (array('source', 'target', 'status', 'is_enabled') as $field) {
            if ($request->has_param($field)) {
                $data[$field] = $request->get_param($field);
            }
        }
        
        if (!empty($data)) {
            // Validate data
            $validation = AlmaSEO_Redirects_Controller::validate_redirect_data($data, $id);
            
            if (is_wp_error($validation)) {
                return $validation;
            }
            
            $result = AlmaSEO_Redirects_Model::update_redirect($id, $data);
            
            if (!$result) {
                return new WP_Error('update_failed', __('Failed to update redirect.', 'almaseo'), array('status' => 500));
            }
        }
        
        $redirect = AlmaSEO_Redirects_Model::get_redirect($id);
        
        return new WP_REST_Response($redirect, 200);
    }
    
    /**
     * Delete redirect
     */
    public function delete_redirect($request) {
        require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
        
        $id = $request->get_param('id');
        
        // Check if redirect exists
        $existing = AlmaSEO_Redirects_Model::get_redirect($id);
        
        if (!$existing) {
            return new WP_Error('not_found', __('Redirect not found.', 'almaseo'), array('status' => 404));
        }
        
        $result = AlmaSEO_Redirects_Model::delete_redirect($id);
        
        if (!$result) {
            return new WP_Error('delete_failed', __('Failed to delete redirect.', 'almaseo'), array('status' => 500));
        }
        
        return new WP_REST_Response(array('message' => __('Redirect deleted successfully.', 'almaseo')), 200);
    }
    
    /**
     * Toggle redirect enabled status
     */
    public function toggle_redirect($request) {
        require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
        
        $id = $request->get_param('id');
        
        // Check if redirect exists
        $existing = AlmaSEO_Redirects_Model::get_redirect($id);
        
        if (!$existing) {
            return new WP_Error('not_found', __('Redirect not found.', 'almaseo'), array('status' => 404));
        }
        
        $result = AlmaSEO_Redirects_Model::toggle_redirect($id);
        
        if (!$result) {
            return new WP_Error('toggle_failed', __('Failed to toggle redirect status.', 'almaseo'), array('status' => 500));
        }
        
        $redirect = AlmaSEO_Redirects_Model::get_redirect($id);
        
        return new WP_REST_Response($redirect, 200);
    }
    
    /**
     * Bulk action handler
     */
    public function bulk_action($request) {
        require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
        
        $action = $request->get_param('action');
        $ids = $request->get_param('ids');
        
        if (empty($ids)) {
            return new WP_Error('no_ids', __('No redirects selected.', 'almaseo'), array('status' => 400));
        }
        
        $success = 0;
        $failed = 0;
        
        foreach ($ids as $id) {
            $id = absint($id);
            
            switch ($action) {
                case 'delete':
                    if (AlmaSEO_Redirects_Model::delete_redirect($id)) {
                        $success++;
                    } else {
                        $failed++;
                    }
                    break;
                    
                case 'enable':
                    if (AlmaSEO_Redirects_Model::update_redirect($id, array('is_enabled' => 1))) {
                        $success++;
                    } else {
                        $failed++;
                    }
                    break;
                    
                case 'disable':
                    if (AlmaSEO_Redirects_Model::update_redirect($id, array('is_enabled' => 0))) {
                        $success++;
                    } else {
                        $failed++;
                    }
                    break;
                    
                default:
                    return new WP_Error('invalid_action', __('Invalid bulk action.', 'almaseo'), array('status' => 400));
            }
        }
        
        return new WP_REST_Response(array(
            'message' => sprintf(__('%d redirects processed successfully, %d failed.', 'almaseo'), $success, $failed),
            'success' => $success,
            'failed' => $failed
        ), 200);
    }
    
    /**
     * Test a redirect
     */
    public function test_redirect($request) {
        require_once plugin_dir_path(__FILE__) . 'redirects-model.php';
        
        $source = $request->get_param('source');
        $normalized = AlmaSEO_Redirects_Model::normalize_path($source);
        
        if (!$normalized) {
            return new WP_Error('invalid_source', __('Invalid source path.', 'almaseo'), array('status' => 400));
        }
        
        $redirect = AlmaSEO_Redirects_Model::get_redirect_by_source($normalized);
        
        if (!$redirect) {
            return new WP_REST_Response(array(
                'found' => false,
                'message' => __('No redirect found for this path.', 'almaseo')
            ), 200);
        }
        
        // Build target URL
        $target = $redirect['target'];
        if (!filter_var($target, FILTER_VALIDATE_URL)) {
            $target = home_url($target);
        }
        
        return new WP_REST_Response(array(
            'found' => true,
            'redirect' => $redirect,
            'target_url' => $target,
            'message' => sprintf(__('Would redirect to: %s with status %d', 'almaseo'), $target, $redirect['status'])
        ), 200);
    }
}