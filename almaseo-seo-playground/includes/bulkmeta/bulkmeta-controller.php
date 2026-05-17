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
            __('Bulk Metadata Editor', 'almaseo-seo-playground'),
            __('Bulk Metadata', 'almaseo-seo-playground'),
            'manage_options',
            'almaseo-bulk-meta',
            array(__CLASS__, 'render_page')
        );
    }
    
    /**
     * Enqueue assets
     */
    public static function enqueue_assets($hook) {
        if (strpos($hook, 'almaseo-bulk-meta') === false) {
            return;
        }

        wp_enqueue_script('jquery');
        wp_enqueue_script('wp-api-fetch');

        // Nonce middleware for wp.apiFetch — must enqueue after wp-api-fetch.
        wp_add_inline_script(
            'wp-api-fetch',
            'if (window.wp && window.wp.apiFetch) { wp.apiFetch.use( wp.apiFetch.createNonceMiddleware( "' . wp_create_nonce('wp_rest') . '" ) ); }',
            'after'
        );

        wp_enqueue_style('dashicons');

        wp_enqueue_style(
            'almaseo-bulk-meta',
            plugins_url('assets/css/bulk-meta.css', dirname(dirname(__FILE__))),
            array('dashicons'),
            // Use the live plugin version, NOT ALMASEO_VERSION — the latter
            // is hardcoded to '6.5.0' in the main plugin file and never
            // bumped, so the CSS URL had been frozen at ?ver=6.5.0 forever
            // and browser caches refused to re-fetch this file on releases.
            defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? time() : ALMASEO_PLUGIN_VERSION
        );

        wp_enqueue_script(
            'almaseo-bulk-meta',
            plugins_url('assets/js/bulk-meta.js', dirname(dirname(__FILE__))),
            array('jquery', 'wp-api-fetch'),
            defined('SCRIPT_DEBUG') && SCRIPT_DEBUG ? time() : ALMASEO_PLUGIN_VERSION,
            true
        );

        // Single source of truth for char/pixel budgets — JS reads from
        // AlmaBulkMeta.limits, the admin page reads via the same constants.
        $ai_available = false;
        $ai_file = dirname(__DIR__) . '/bulkmeta/ai-autofill-generator.php';
        if (file_exists($ai_file)) {
            require_once $ai_file;
            $ai_available = AI_Autofill_Generator::is_available();
        }

        wp_localize_script('almaseo-bulk-meta', 'AlmaBulkMeta', array(
            'restBase'  => rest_url('almaseo/v1/'),
            'nonce'     => wp_create_nonce('wp_rest'),
            'api_url'   => rest_url('almaseo/v1/bulkmeta'),
            'aiAvailable' => $ai_available,
            'strings'   => array(
                'loading'       => __('Loading posts...', 'almaseo-seo-playground'),
                'saving'        => __('Saving...', 'almaseo-seo-playground'),
                'saved'         => __('Saved!', 'almaseo-seo-playground'),
                'error'         => __('Error saving', 'almaseo-seo-playground'),
                'loadError'     => __('Failed to load posts', 'almaseo-seo-playground'),
                'confirm_reset' => __('Are you sure you want to reset these fields?', 'almaseo-seo-playground'),
                'confirm_bulk'  => __('Apply bulk operation to selected items?', 'almaseo-seo-playground'),
                'processing'    => __('Processing...', 'almaseo-seo-playground'),
                'completed'     => __('Operation completed', 'almaseo-seo-playground'),
                'title_warning' => __('Title exceeds recommended length', 'almaseo-seo-playground'),
                'desc_warning'  => __('Description exceeds recommended length', 'almaseo-seo-playground'),
            ),
            'limits'    => array(
                'title_chars'  => 65,
                'title_pixels' => 580,
                'desc_chars'   => 160,
                'desc_pixels'  => 920,
            ),
            'site_name' => get_bloginfo('name'),
        ));
    }
    
    /**
     * Render admin page
     */
    public static function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'almaseo-seo-playground'));
        }

        // Check if bulk metadata feature is available (Pro feature)
        if ( ! almaseo_feature_available( 'bulkmeta' ) ) {
            almaseo_render_feature_locked( 'bulkmeta' );
            return;
        }

        require_once(dirname(dirname(dirname(__FILE__))) . '/admin/pages/bulk-meta.php');
    }
    
    /**
     * Update post metadata
     */
    public static function update_post_meta($post_id, $data) {
        if (!current_user_can('edit_post', $post_id)) {
            return new \WP_Error('unauthorized', __('You cannot edit this post.', 'almaseo-seo-playground'), array('status' => 403));
        }
        
        $updated = false;
        
        if (isset($data['meta_title'])) {
            $title = sanitize_text_field($data['meta_title']);
            if (empty($title)) {
                delete_post_meta($post_id, '_almaseo_title');
                delete_post_meta($post_id, '_almaseo_meta_title');
            } else {
                // Write to both the canonical key (used by metabox + frontend)
                // and the bulk-meta key (used by this table's display)
                update_post_meta($post_id, '_almaseo_title', $title);
                update_post_meta($post_id, '_almaseo_meta_title', $title);
            }
            $updated = true;
        }

        if (isset($data['meta_description'])) {
            $description = sanitize_textarea_field($data['meta_description']);
            if (empty($description)) {
                delete_post_meta($post_id, '_almaseo_description');
                delete_post_meta($post_id, '_almaseo_meta_description');
            } else {
                update_post_meta($post_id, '_almaseo_description', $description);
                update_post_meta($post_id, '_almaseo_meta_description', $description);
            }
            $updated = true;
        }
        
        if ($updated) {
            // Intentionally do NOT bump post_modified: meta-only edits should
            // not change the post's modified date (would affect sitemap
            // lastmod, feeds, and modified-orderby queries).
            return self::row_payload(get_post($post_id));
        }

        return new \WP_Error('no_changes', __('No changes made.', 'almaseo-seo-playground'), array('status' => 400));
    }

    /**
     * Reset post metadata.
     *
     * Clears BOTH key families — the canonical keys read by the metabox /
     * frontend renderer (_almaseo_title, _almaseo_description) AND the
     * bulk-meta table's display keys (_almaseo_meta_title, _almaseo_meta_description).
     * Previously only the latter pair was deleted, so resets in the bulk
     * editor left the live meta tags unchanged.
     */
    public static function reset_post_meta($post_id) {
        if (!current_user_can('edit_post', $post_id)) {
            return new \WP_Error('unauthorized', __('You cannot edit this post.', 'almaseo-seo-playground'), array('status' => 403));
        }

        delete_post_meta($post_id, '_almaseo_title');
        delete_post_meta($post_id, '_almaseo_meta_title');
        delete_post_meta($post_id, '_almaseo_description');
        delete_post_meta($post_id, '_almaseo_meta_description');

        return self::row_payload(get_post($post_id));
    }

    /**
     * Minimal row payload for save/reset responses — matches the shape the
     * bulk-meta table renderer consumes so it can refresh a single row in
     * place after a save without re-fetching the whole page.
     */
    private static function row_payload($post) {
        if (!$post) {
            return array();
        }
        $pid = (int) $post->ID;
        $t = (string) get_post_meta($pid, '_almaseo_meta_title', true);
        if ($t === '') {
            $t = (string) get_post_meta($pid, '_almaseo_title', true);
        }
        $d = (string) get_post_meta($pid, '_almaseo_meta_description', true);
        if ($d === '') {
            $d = (string) get_post_meta($pid, '_almaseo_desc', true);
        }
        $post_type_obj = get_post_type_object($post->post_type);
        return array(
            'id'               => $pid,
            'title'            => get_the_title($post),
            'type'             => $post->post_type,
            'type_label'       => $post_type_obj ? $post_type_obj->labels->singular_name : $post->post_type,
            'status'           => $post->post_status,
            'updated'          => mysql2date('c', $post->post_modified_gmt, false),
            'seo_title'        => $t,
            'meta_title'       => $t,
            'meta_desc'        => $d,
            'meta_description' => $d,
            'title_chars'      => mb_strlen(wp_strip_all_tags($t)),
            'desc_chars'       => mb_strlen(wp_strip_all_tags($d)),
            'title_fallback'   => $t === '' ? $post->post_title : '',
            'desc_fallback'    => $d === '' ? wp_trim_words(strip_shortcodes($post->post_content ?? ''), 30, '...') : '',
            'edit_link'        => get_edit_post_link($pid, 'raw'),
            'view_link'        => get_permalink($post),
        );
    }
    
    /**
     * Hard ceiling for /bulkmeta/bulk-all. Same number as the JS-side
     * "Auto-Fill Entire Site" cap — anything larger should be filtered
     * down or split into smaller selections.
     */
    const BULK_ALL_MAX = 5000;

    /**
     * Translate a normalized filter-spec array into WP_Query args.
     *
     * Shared by the list endpoint (BulkMeta_REST::get_posts) and the
     * bulk-all endpoint so the two can never disagree about what "matching
     * the current filters" means. Pagination args (page/per_page/orderby/
     * order) are NOT applied here — the caller layers them on as needed.
     *
     * @param array $params Filter spec. Recognized keys: type, status,
     *                      taxonomy, term, from, to, search, missing.
     * @return array WP_Query-shaped args.
     */
    public static function build_query_args($params) {
        $args = array();

        $types  = isset($params['type'])   ? $params['type']   : array('post', 'page');
        $status = isset($params['status']) ? $params['status'] : array('publish', 'draft');
        if (is_string($types))  { $types  = array_map('sanitize_key', wp_parse_list($types)); }
        if (is_string($status)) { $status = array_map('sanitize_key', wp_parse_list($status)); }
        $args['post_type']   = $types  ?: array('post', 'page');
        $args['post_status'] = $status ?: array('publish', 'draft');

        if (!empty($params['search'])) {
            $args['s'] = (string) $params['search'];
        }

        if (!empty($params['taxonomy']) && !empty($params['term'])) {
            $args['tax_query'] = array(
                array(
                    'taxonomy' => (string) $params['taxonomy'],
                    'field'    => 'term_id',
                    'terms'    => (int) $params['term'],
                ),
            );
        }

        if (!empty($params['from']) || !empty($params['to'])) {
            $date_query = array();
            if (!empty($params['from'])) $date_query['after']  = (string) $params['from'];
            if (!empty($params['to']))   $date_query['before'] = (string) $params['to'];
            $args['date_query'] = array($date_query);
        }

        if (!empty($params['missing'])) {
            $args['meta_query'] = array(
                'relation' => 'OR',
                array(
                    'relation' => 'AND',
                    array('relation' => 'OR',
                        array('key' => '_almaseo_meta_title', 'compare' => 'NOT EXISTS'),
                        array('key' => '_almaseo_meta_title', 'value' => '', 'compare' => '='),
                    ),
                    array('relation' => 'OR',
                        array('key' => '_almaseo_title', 'compare' => 'NOT EXISTS'),
                        array('key' => '_almaseo_title', 'value' => '', 'compare' => '='),
                    ),
                ),
                array(
                    'relation' => 'AND',
                    array('relation' => 'OR',
                        array('key' => '_almaseo_meta_description', 'compare' => 'NOT EXISTS'),
                        array('key' => '_almaseo_meta_description', 'value' => '', 'compare' => '='),
                    ),
                    array('relation' => 'OR',
                        array('key' => '_almaseo_desc', 'compare' => 'NOT EXISTS'),
                        array('key' => '_almaseo_desc', 'value' => '', 'compare' => '='),
                    ),
                ),
            );
        }

        return $args;
    }

    /**
     * Apply a single bulk op to a single post. Returns:
     *   - 'success' on a write
     *   - 'noop' when the op matched no content (e.g. find string not found)
     *   - 'fail' on permission denial or hard error
     *
     * Also writes BOTH key families (_almaseo_title + _almaseo_meta_title,
     * _almaseo_description + _almaseo_meta_description) so the metabox/
     * frontend renderer and the bulk-meta table stay in sync. Reset clears
     * both families.
     */
    private static function apply_op_to_post($op, $field, $args, $post) {
        if (!$post) return array('status' => 'fail', 'error' => 'invalid_post');
        $post_id = (int) $post->ID;
        if (!current_user_can('edit_post', $post_id)) {
            return array('status' => 'fail', 'error' => 'unauthorized');
        }

        switch ($op) {
            case 'reset':
                delete_post_meta($post_id, '_almaseo_title');
                delete_post_meta($post_id, '_almaseo_meta_title');
                delete_post_meta($post_id, '_almaseo_description');
                delete_post_meta($post_id, '_almaseo_meta_description');
                return array('status' => 'success');

            case 'append':
            case 'prepend': {
                $text = isset($args['text']) ? (string) $args['text'] : '';
                $text = (string) self::process_placeholders($text, $post);

                if ($field === 'title') {
                    $current = (string) get_post_meta($post_id, '_almaseo_meta_title', true);
                    if ($current === '') $current = (string) ($post->post_title ?? '');
                    $new = ($op === 'append') ? $current . $text : $text . $current;
                    $new = sanitize_text_field($new);
                    update_post_meta($post_id, '_almaseo_title', $new);
                    update_post_meta($post_id, '_almaseo_meta_title', $new);
                    return array('status' => 'success');
                }
                if ($field === 'description') {
                    $current = (string) get_post_meta($post_id, '_almaseo_meta_description', true);
                    if ($current === '') {
                        $excerpt = (string) ($post->post_excerpt ?? '');
                        $content = (string) ($post->post_content ?? '');
                        $current = $excerpt !== '' ? $excerpt : wp_trim_words($content, 30);
                    }
                    $new = ($op === 'append') ? $current . $text : $text . $current;
                    $new = sanitize_textarea_field($new);
                    update_post_meta($post_id, '_almaseo_description', $new);
                    update_post_meta($post_id, '_almaseo_meta_description', $new);
                    return array('status' => 'success');
                }
                return array('status' => 'fail', 'error' => 'invalid_field');
            }

            case 'replace': {
                $find    = isset($args['find']) ? (string) $args['find'] : '';
                $replace = isset($args['replace']) ? (string) $args['replace'] : '';
                $replace = (string) self::process_placeholders($replace, $post);
                if ($find === '') {
                    return array('status' => 'fail', 'error' => 'empty_find');
                }

                if ($field === 'title') {
                    $current = (string) get_post_meta($post_id, '_almaseo_meta_title', true);
                    if ($current === '') $current = (string) ($post->post_title ?? '');
                    if ($current !== '' && strpos($current, $find) !== false) {
                        $new = sanitize_text_field(str_replace($find, $replace, $current));
                        update_post_meta($post_id, '_almaseo_title', $new);
                        update_post_meta($post_id, '_almaseo_meta_title', $new);
                        return array('status' => 'success');
                    }
                    return array('status' => 'noop');
                }
                if ($field === 'description') {
                    $current = (string) get_post_meta($post_id, '_almaseo_meta_description', true);
                    if ($current === '') {
                        $excerpt = (string) ($post->post_excerpt ?? '');
                        $content = (string) ($post->post_content ?? '');
                        $current = $excerpt !== '' ? $excerpt : wp_trim_words($content, 30);
                    }
                    if ($current !== '' && strpos($current, $find) !== false) {
                        $new = sanitize_textarea_field(str_replace($find, $replace, $current));
                        update_post_meta($post_id, '_almaseo_description', $new);
                        update_post_meta($post_id, '_almaseo_meta_description', $new);
                        return array('status' => 'success');
                    }
                    return array('status' => 'noop');
                }
                return array('status' => 'fail', 'error' => 'invalid_field');
            }
        }
        return array('status' => 'fail', 'error' => 'unknown_op');
    }

    /**
     * Bulk operation handler — operates on an explicit list of post IDs.
     */
    public static function bulk_operation($data) {
        if (!current_user_can('manage_options')) {
            return new \WP_Error('unauthorized', __('Insufficient permissions.', 'almaseo-seo-playground'), array('status' => 403));
        }

        $ids   = isset($data['ids']) ? array_map('intval', (array) $data['ids']) : array();
        $op    = isset($data['op'])    ? sanitize_text_field($data['op']) : '';
        $field = isset($data['field']) ? sanitize_text_field($data['field']) : '';
        $args  = isset($data['args']) && is_array($data['args']) ? $data['args'] : array();

        if (empty($ids)) {
            return new \WP_Error('no_ids', __('No posts selected.', 'almaseo-seo-playground'), array('status' => 400));
        }

        $results = array('success' => 0, 'failed' => 0, 'skipped' => 0, 'errors' => array());

        foreach ($ids as $post_id) {
            $post = get_post($post_id);
            if (!$post) {
                $results['failed']++;
                continue;
            }
            $r = self::apply_op_to_post($op, $field, $args, $post);
            if ($r['status'] === 'success')      { $results['success']++; }
            elseif ($r['status'] === 'noop')     { $results['skipped']++; }
            else {
                $results['failed']++;
                if (!empty($r['error'])) $results['errors'][] = $r['error'];
            }
        }

        return $results;
    }

    /**
     * Bulk operation across every post matching a filter spec — the
     * "Select all N matching" path. Runs WP_Query with the same filter
     * mapping the list endpoint uses, hard-caps at BULK_ALL_MAX, then
     * fans out via apply_op_to_post().
     */
    public static function bulk_all_operation($data) {
        if (!current_user_can('manage_options')) {
            return new \WP_Error('unauthorized', __('Insufficient permissions.', 'almaseo-seo-playground'), array('status' => 403));
        }

        $op      = isset($data['op'])    ? sanitize_text_field($data['op']) : '';
        $field   = isset($data['field']) ? sanitize_text_field($data['field']) : '';
        $args    = isset($data['args']) && is_array($data['args']) ? $data['args'] : array();
        $filters = isset($data['filters']) && is_array($data['filters']) ? $data['filters'] : array();

        $allowed_ops = array('reset', 'append', 'prepend', 'replace');
        if (!in_array($op, $allowed_ops, true)) {
            return new \WP_Error('invalid_op', __('Invalid bulk operation.', 'almaseo-seo-playground'), array('status' => 400));
        }

        // Build the query, no pagination — bulk-all walks every match.
        $query_args = self::build_query_args($filters);
        $query_args['posts_per_page'] = -1;
        $query_args['no_found_rows']  = true;
        $query_args['orderby']        = 'ID';
        $query_args['order']          = 'ASC';

        // Pre-flight count check so we don't block PHP for minutes on a
        // huge site. The JS-side "Auto-Fill Entire Site" enforces the same
        // ceiling — keeping it consistent across the two destructive paths.
        $count_args = $query_args;
        $count_args['fields']         = 'ids';
        $count_args['posts_per_page'] = self::BULK_ALL_MAX + 1;
        $count_query = new \WP_Query($count_args);
        $matched = (int) count($count_query->posts);
        if ($matched > self::BULK_ALL_MAX) {
            return new \WP_Error(
                'too_many',
                sprintf(
                    /* translators: 1: matched count, 2: hard ceiling */
                    __('Refusing to run: %1$d posts match your filters, above the safety ceiling of %2$d. Filter more narrowly and try again.', 'almaseo-seo-playground'),
                    $matched,
                    self::BULK_ALL_MAX
                ),
                array('status' => 400, 'matched' => $matched, 'cap' => self::BULK_ALL_MAX)
            );
        }
        if ($matched === 0) {
            return array('success' => 0, 'failed' => 0, 'skipped' => 0, 'matched' => 0, 'errors' => array());
        }

        @set_time_limit(0);
        @ignore_user_abort(true);

        $results = array('success' => 0, 'failed' => 0, 'skipped' => 0, 'matched' => $matched, 'errors' => array());

        // Re-run with full post objects so meta cache primes.
        $query = new \WP_Query($query_args);
        foreach ($query->posts as $post) {
            $r = self::apply_op_to_post($op, $field, $args, $post);
            if ($r['status'] === 'success')      { $results['success']++; }
            elseif ($r['status'] === 'noop')     { $results['skipped']++; }
            else {
                $results['failed']++;
                if (!empty($r['error']) && count($results['errors']) < 20) {
                    $results['errors'][] = $r['error'];
                }
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
            '{year}' => gmdate('Y'),
            '{month}' => gmdate('F'),
            '{day}' => gmdate('j')
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