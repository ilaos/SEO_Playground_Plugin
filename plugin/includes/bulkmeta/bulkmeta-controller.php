<?php
/**
 * AlmaSEO Bulk Metadata Controller
 * 
 * @package AlmaSEO
 * @since 6.3.0
 */

namespace AlmaSEO\BulkMeta;

if (!defined('ABSPATH')) {
    exit;
}

class BulkMeta_Controller {
    
    /**
     * Initialize controller
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_menu'), 25);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }
    
    /**
     * Add submenu page
     */
    public static function add_menu() {
        if (!almaseo_is_pro()) {
            return;
        }
        
        add_submenu_page(
            'seo-playground',
            __('Bulk Metadata Editor', 'almaseo'),
            __('Bulk Metadata', 'almaseo'),
            'manage_options',
            'almaseo-bulk-meta',
            array(__CLASS__, 'render_page')
        );
    }
    
    /**
     * Enqueue assets
     */
    public static function enqueue_assets($hook) {
        // Check if we're on the bulk meta page
        if (strpos($hook, 'almaseo-bulk-meta') === false) {
            return;
        }
        
        // Log the actual hook for debugging
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log('BulkMeta enqueue on hook: ' . $hook);
        }
        
        // Ensure jQuery is loaded first (explicitly)
        wp_enqueue_script('jquery');
        
        // Ensure wp-api-fetch is loaded with its dependencies
        wp_enqueue_script('wp-api-fetch');
        
        // Add nonce middleware for wp-api-fetch - MUST be after wp-api-fetch enqueue
        wp_add_inline_script(
            'wp-api-fetch',
            'if (window.wp && window.wp.apiFetch) { wp.apiFetch.use( wp.apiFetch.createNonceMiddleware( "' . wp_create_nonce('wp_rest') . '" ) ); }',
            'after'
        );
        
        // Ensure dashicons are loaded for visual indicators
        wp_enqueue_style('dashicons');
        
        // CSS
        wp_enqueue_style(
            'almaseo-bulk-meta',
            plugins_url('assets/css/bulk-meta.css', dirname(dirname(__FILE__))),
            array('dashicons'),  // Add dashicons as dependency
            defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? time() : ALMASEO_VERSION
        );
        
        // JavaScript with jQuery and wp-api-fetch dependencies - MUST load in footer
        wp_enqueue_script(
            'almaseo-bulk-meta',
            plugins_url('assets/js/bulk-meta.js', dirname(dirname(__FILE__))),
            array('jquery', 'wp-api-fetch'), // Dependencies
            defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? time() : ALMASEO_PLUGIN_VERSION,
            true // Load in footer after all dependencies
        );
        
        // Localize script with configuration
        wp_localize_script('almaseo-bulk-meta', 'AlmaBulkMeta', array(
            'restBase' => rest_url('almaseo/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'api_url' => rest_url('almaseo/v1/bulkmeta'),
            'screenHook' => $hook,
            'strings' => array(
                'loading' => __('Loading posts...', 'almaseo'),
                'saving' => __('Saving...', 'almaseo'),
                'saved' => __('Saved!', 'almaseo'),
                'error' => __('Error saving', 'almaseo'),
                'loadError' => __('Failed to load posts', 'almaseo'),
                'confirm_reset' => __('Are you sure you want to reset these fields?', 'almaseo'),
                'confirm_bulk' => __('Apply bulk operation to selected items?', 'almaseo'),
                'processing' => __('Processing...', 'almaseo'),
                'completed' => __('Operation completed', 'almaseo'),
                'title_warning' => __('Title exceeds recommended length', 'almaseo'),
                'desc_warning' => __('Description exceeds recommended length', 'almaseo'),
            ),
            'limits' => array(
                'title_chars' => 65,
                'title_pixels' => 580,
                'desc_chars' => 160,
                'desc_pixels' => 920
            ),
            'site_name' => get_bloginfo('name')
        ));
        
        // Also localize for jQuery fallback (backward compatibility)
        wp_localize_script('almaseo-bulk-meta', 'almaseo_bulk_meta', array(
            'nonce' => wp_create_nonce('wp_rest'),
            'api_url' => rest_url('almaseo/v1/bulkmeta')
        ));
    }
    
    /**
     * Render admin page
     */
    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'almaseo'));
        }
        
        require_once(dirname(dirname(dirname(__FILE__))) . '/admin/pages/bulk-meta.php');
    }
    
    /**
     * Get posts for bulk editing
     */
    public static function get_posts($args = array()) {
        $defaults = array(
            'post_type' => 'post',
            'post_status' => array('publish', 'draft'),
            'posts_per_page' => 20,
            'paged' => 1,
            'orderby' => 'modified',
            'order' => 'DESC',
            'meta_query' => array()
        );
        
        $args = wp_parse_args($args, $defaults);
        
        // Handle "missing only" filter
        if (!empty($args['missing_only'])) {
            $args['meta_query'][] = array(
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
        
        // Handle taxonomy filter
        if (!empty($args['taxonomy']) && !empty($args['term'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => $args['taxonomy'],
                    'field' => 'term_id',
                    'terms' => $args['term']
                )
            );
        }
        
        // Handle date range
        if (!empty($args['date_from']) || !empty($args['date_to'])) {
            $date_query = array();
            if (!empty($args['date_from'])) {
                $date_query['after'] = $args['date_from'];
            }
            if (!empty($args['date_to'])) {
                $date_query['before'] = $args['date_to'];
            }
            $args['date_query'] = array($date_query);
        }
        
        // Handle search
        if (!empty($args['search'])) {
            $args['s'] = $args['search'];
        }
        
        $query = new \WP_Query($args);
        
        $posts = array();
        foreach ($query->posts as $post) {
            $posts[] = self::format_post_data($post);
        }
        
        return array(
            'posts' => $posts,
            'total' => $query->found_posts,
            'pages' => $query->max_num_pages
        );
    }
    
    /**
     * Format post data for response
     */
    public static function format_post_data($post) {
        // Cast to string to ensure safe strings, never undefined
        $meta_title = (string) get_post_meta($post->ID, '_almaseo_meta_title', true);
        $meta_description = (string) get_post_meta($post->ID, '_almaseo_meta_description', true);
        
        // Get fallbacks
        $title_fallback = '';
        $desc_fallback = '';
        
        if (empty($meta_title)) {
            $title_fallback = $post->post_title ?? '';
        }
        
        if (empty($meta_description)) {
            if (!empty($post->post_excerpt)) {
                $desc_fallback = $post->post_excerpt ?? '';
            } else {
                $content = $post->post_content ?? '';
                $desc_fallback = wp_trim_words(strip_shortcodes($content), 30, '...');
            }
        }
        
        // Get post type label
        $post_type_obj = get_post_type_object($post->post_type);
        $post_type_label = $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type;
        
        // Get categories
        $categories = array();
        if ($post->post_type === 'post') {
            $cats = get_the_category($post->ID);
            foreach ($cats as $cat) {
                $categories[] = $cat->name;
            }
        }
        
        // Calculate character counts for UI (strip tags for accurate count)
        $title_chars = mb_strlen(wp_strip_all_tags($meta_title));
        $desc_chars = mb_strlen(wp_strip_all_tags($meta_description));
        
        return array(
            'id' => $post->ID,
            'title' => $post->post_title ?? '',
            'type' => $post->post_type ?? 'post',
            'type_label' => $post_type_label ?? '',
            'status' => $post->post_status ?? 'publish',
            'meta_title' => $meta_title,
            'meta_description' => $meta_description,
            'title_fallback' => $title_fallback,
            'desc_fallback' => $desc_fallback,
            'categories' => $categories,
            'updated' => $post->post_modified ?? '',
            'edit_link' => get_edit_post_link($post->ID, 'raw') ?? '',
            'view_link' => get_permalink($post->ID) ?? '',
            // Provide the fields the UI expects; never undefined:
            'title_chars' => $title_chars,
            'desc_chars' => $desc_chars
        );
    }
    
    /**
     * Update post metadata
     */
    public static function update_post_meta($post_id, $data) {
        if (!current_user_can('edit_post', $post_id)) {
            return new \WP_Error('unauthorized', __('You cannot edit this post.', 'almaseo'), array('status' => 403));
        }
        
        $updated = false;
        
        if (isset($data['meta_title'])) {
            $title = sanitize_text_field($data['meta_title']);
            if (empty($title)) {
                delete_post_meta($post_id, '_almaseo_meta_title');
            } else {
                update_post_meta($post_id, '_almaseo_meta_title', $title);
            }
            $updated = true;
        }
        
        if (isset($data['meta_description'])) {
            $description = sanitize_textarea_field($data['meta_description']);
            if (empty($description)) {
                delete_post_meta($post_id, '_almaseo_meta_description');
            } else {
                update_post_meta($post_id, '_almaseo_meta_description', $description);
            }
            $updated = true;
        }
        
        if ($updated) {
            // Update modified time
            wp_update_post(array(
                'ID' => $post_id,
                'post_modified' => current_time('mysql'),
                'post_modified_gmt' => current_time('mysql', true)
            ));
            
            return self::format_post_data(get_post($post_id));
        }
        
        return new \WP_Error('no_changes', __('No changes made.', 'almaseo'), array('status' => 400));
    }
    
    /**
     * Reset post metadata
     */
    public static function reset_post_meta($post_id) {
        if (!current_user_can('edit_post', $post_id)) {
            return new \WP_Error('unauthorized', __('You cannot edit this post.', 'almaseo'), array('status' => 403));
        }
        
        delete_post_meta($post_id, '_almaseo_meta_title');
        delete_post_meta($post_id, '_almaseo_meta_description');
        
        return self::format_post_data(get_post($post_id));
    }
    
    /**
     * Bulk operation handler
     */
    public static function bulk_operation($data) {
        if (!current_user_can('manage_options')) {
            return new \WP_Error('unauthorized', __('Insufficient permissions.', 'almaseo'), array('status' => 403));
        }
        
        $ids = isset($data['ids']) ? array_map('intval', $data['ids']) : array();
        $op = isset($data['op']) ? sanitize_text_field($data['op']) : '';
        $field = isset($data['field']) ? sanitize_text_field($data['field']) : '';
        $args = isset($data['args']) ? $data['args'] : array();
        
        if (empty($ids)) {
            return new \WP_Error('no_ids', __('No posts selected.', 'almaseo'), array('status' => 400));
        }
        
        $results = array(
            'success' => 0,
            'failed' => 0,
            'errors' => array()
        );
        
        foreach ($ids as $post_id) {
            if (!current_user_can('edit_post', $post_id)) {
                $results['failed']++;
                $results['errors'][] = sprintf(__('Cannot edit post %d', 'almaseo'), $post_id);
                continue;
            }
            
            $post = get_post($post_id);
            if (!$post) {
                $results['failed']++;
                continue;
            }
            
            switch ($op) {
                case 'reset':
                    delete_post_meta($post_id, '_almaseo_meta_title');
                    delete_post_meta($post_id, '_almaseo_meta_description');
                    $results['success']++;
                    break;
                    
                case 'append':
                case 'prepend':
                    $text = isset($args['text']) ? $args['text'] : '';
                    $text = self::process_placeholders($text, $post);
                    
                    if ($field === 'title') {
                        $current = get_post_meta($post_id, '_almaseo_meta_title', true);
                        if (empty($current)) {
                            $current = $post->post_title ?? '';
                        }
                        $current = $current ?? '';
                        $text = $text ?? '';
                        $new = ($op === 'append') ? $current . $text : $text . $current;
                        update_post_meta($post_id, '_almaseo_meta_title', sanitize_text_field($new));
                    } elseif ($field === 'description') {
                        $current = get_post_meta($post_id, '_almaseo_meta_description', true);
                        if (empty($current)) {
                            $excerpt = $post->post_excerpt ?? '';
                            $content = $post->post_content ?? '';
                            $current = !empty($excerpt) ? $excerpt : wp_trim_words($content, 30);
                        }
                        $current = $current ?? '';
                        $text = $text ?? '';
                        $new = ($op === 'append') ? $current . $text : $text . $current;
                        update_post_meta($post_id, '_almaseo_meta_description', sanitize_textarea_field($new));
                    }
                    $results['success']++;
                    break;
                    
                case 'replace':
                    $find = isset($args['find']) ? $args['find'] : '';
                    $replace = isset($args['replace']) ? $args['replace'] : '';
                    $replace = self::process_placeholders($replace, $post);
                    
                    // Ensure all values are strings
                    $find = $find ?? '';
                    $replace = $replace ?? '';
                    
                    if ($field === 'title') {
                        $current = get_post_meta($post_id, '_almaseo_meta_title', true);
                        $current = $current ?? '';
                        if (!empty($current)) {
                            $new = str_replace($find, $replace, $current);
                            update_post_meta($post_id, '_almaseo_meta_title', sanitize_text_field($new));
                            $results['success']++;
                        }
                    } elseif ($field === 'description') {
                        $current = get_post_meta($post_id, '_almaseo_meta_description', true);
                        $current = $current ?? '';
                        if (!empty($current)) {
                            $new = str_replace($find, $replace, $current);
                            update_post_meta($post_id, '_almaseo_meta_description', sanitize_textarea_field($new));
                            $results['success']++;
                        }
                    }
                    break;
            }
        }
        
        return $results;
    }
    
    /**
     * Process placeholders in text
     */
    private static function process_placeholders($text, $post) {
        $text = $text ?? '';
        
        $replacements = array(
            '{site}' => get_bloginfo('name') ?? '',
            '{year}' => date('Y'),
            '{month}' => date('F'),
            '{day}' => date('j')
        );
        
        // Add category if available
        if (($post->post_type ?? '') === 'post') {
            $categories = get_the_category($post->ID);
            if (!empty($categories)) {
                $replacements['{category}'] = $categories[0]->name ?? '';
            }
        }
        
        return str_replace(array_keys($replacements), array_values($replacements), $text);
    }
    
    /**
     * Get available post types
     */
    public static function get_post_types() {
        $post_types = get_post_types(array('public' => true), 'objects');
        $types = array();
        
        foreach ($post_types as $type) {
            if ($type->name === 'attachment') {
                continue;
            }
            $types[] = array(
                'name' => $type->name,
                'label' => $type->labels->name
            );
        }
        
        return $types;
    }
    
    /**
     * Get available taxonomies
     */
    public static function get_taxonomies($post_type = 'post') {
        $taxonomies = get_object_taxonomies($post_type, 'objects');
        $tax_list = array();
        
        foreach ($taxonomies as $tax) {
            if (!$tax->public || $tax->name === 'post_format') {
                continue;
            }
            $tax_list[] = array(
                'name' => $tax->name,
                'label' => $tax->labels->name
            );
        }
        
        return $tax_list;
    }
}

// Initialize
add_action('init', array(__NAMESPACE__ . '\\BulkMeta_Controller', 'init'));