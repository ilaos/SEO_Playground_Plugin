<?php
/**
 * Schema Meta Field Registration
 * 
 * Registers custom meta fields for schema settings
 * 
 * @package AlmaSEO
 * @since 4.2.2
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Register schema meta fields
 */
function almaseo_register_schema_meta_fields() {
    $post_types = array('post', 'page', 'product');
    
    foreach ($post_types as $post_type) {
        // Schema type
        register_post_meta($post_type, '_almaseo_schema_type', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'string',
            'default' => 'BlogPosting',
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));
        
        // Include author toggle
        register_post_meta($post_type, '_almaseo_schema_include_author', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
            'default' => true,
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));
        
        // Include image toggle
        register_post_meta($post_type, '_almaseo_schema_include_image', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
            'default' => true,
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));
        
        // Include publisher toggle
        register_post_meta($post_type, '_almaseo_schema_include_publisher', array(
            'show_in_rest' => true,
            'single' => true,
            'type' => 'boolean',
            'default' => true,
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ));
    }
}
add_action('init', 'almaseo_register_schema_meta_fields');

/**
 * Ensure meta fields are available in REST API
 */
function almaseo_schema_rest_api_init() {
    $post_types = array('post', 'page', 'product');
    
    foreach ($post_types as $post_type) {
        register_rest_field($post_type, 'almaseo_schema_settings', array(
            'get_callback' => function($post) {
                return array(
                    'schema_type' => get_post_meta($post['id'], '_almaseo_schema_type', true) ?: 'BlogPosting',
                    'include_author' => (bool) get_post_meta($post['id'], '_almaseo_schema_include_author', true),
                    'include_image' => (bool) get_post_meta($post['id'], '_almaseo_schema_include_image', true),
                    'include_publisher' => (bool) get_post_meta($post['id'], '_almaseo_schema_include_publisher', true)
                );
            },
            'update_callback' => function($value, $post) {
                if (isset($value['schema_type'])) {
                    update_post_meta($post->ID, '_almaseo_schema_type', sanitize_text_field($value['schema_type']));
                }
                if (isset($value['include_author'])) {
                    update_post_meta($post->ID, '_almaseo_schema_include_author', (bool) $value['include_author']);
                }
                if (isset($value['include_image'])) {
                    update_post_meta($post->ID, '_almaseo_schema_include_image', (bool) $value['include_image']);
                }
                if (isset($value['include_publisher'])) {
                    update_post_meta($post->ID, '_almaseo_schema_include_publisher', (bool) $value['include_publisher']);
                }
                return true;
            },
            'schema' => array(
                'type' => 'object',
                'properties' => array(
                    'schema_type' => array('type' => 'string'),
                    'include_author' => array('type' => 'boolean'),
                    'include_image' => array('type' => 'boolean'),
                    'include_publisher' => array('type' => 'boolean')
                )
            )
        ));
    }
}
add_action('rest_api_init', 'almaseo_schema_rest_api_init');