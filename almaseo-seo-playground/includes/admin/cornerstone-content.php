<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AlmaSEO_Cornerstone_Content {

    const META_KEY = '_almaseo_is_cornerstone';

    public static function init() {
        // Add column to Posts list table
        add_filter( 'manage_posts_columns', array( __CLASS__, 'add_column' ) );
        add_filter( 'manage_pages_columns', array( __CLASS__, 'add_column' ) );
        add_action( 'manage_posts_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );
        add_action( 'manage_pages_custom_column', array( __CLASS__, 'render_column' ), 10, 2 );

        // Make column sortable
        add_filter( 'manage_edit-post_sortable_columns', array( __CLASS__, 'sortable_column' ) );
        add_filter( 'manage_edit-page_sortable_columns', array( __CLASS__, 'sortable_column' ) );
        add_action( 'pre_get_posts', array( __CLASS__, 'sort_by_cornerstone' ) );

        // Filter dropdown
        add_action( 'restrict_manage_posts', array( __CLASS__, 'filter_dropdown' ) );
        add_action( 'pre_get_posts', array( __CLASS__, 'filter_query' ) );

        // Quick Edit support
        add_action( 'quick_edit_custom_box', array( __CLASS__, 'quick_edit_field' ), 10, 2 );
        add_action( 'save_post', array( __CLASS__, 'save_quick_edit' ), 10, 2 );
        add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_quick_edit_script' ) );

        // Admin styles
        add_action( 'admin_head', array( __CLASS__, 'admin_css' ) );
    }

    /**
     * Add Cornerstone column to post list tables.
     */
    public static function add_column( $columns ) {
        $new_columns = array();
        foreach ( $columns as $key => $value ) {
            $new_columns[ $key ] = $value;
            if ( 'title' === $key ) {
                $new_columns['almaseo_cornerstone'] = '<span class="dashicons dashicons-star-filled" title="' . esc_attr__( 'Cornerstone', 'almaseo-seo-playground' ) . '" style="color: #dba617;"></span>';
            }
        }
        return $new_columns;
    }

    /**
     * Render the Cornerstone column value.
     */
    public static function render_column( $column, $post_id ) {
        if ( 'almaseo_cornerstone' !== $column ) return;

        $is_cornerstone = get_post_meta( $post_id, self::META_KEY, true );
        $is_suggested   = get_post_meta( $post_id, '_almaseo_cornerstone_suggested', true );
        if ( $is_cornerstone ) {
            echo '<span class="dashicons dashicons-star-filled" style="color: #dba617;" title="' . esc_attr__( 'Cornerstone Content', 'almaseo-seo-playground' ) . '"></span>';
        } elseif ( $is_suggested ) {
            $score = get_post_meta( $post_id, '_almaseo_cornerstone_score', true );
            /* translators: %s: cornerstone confidence score */
            echo '<span class="dashicons dashicons-star-half" style="color: #667eea;" title="' . esc_attr( sprintf( __( 'AI Suggested (Score: %s/100)', 'almaseo-seo-playground' ), intval( $score ) ) ) . '"></span>';
        } else {
            echo '<span class="dashicons dashicons-star-empty" style="color: #ccc;" title="' . esc_attr__( 'Not Cornerstone', 'almaseo-seo-playground' ) . '"></span>';
        }
        // Hidden value for Quick Edit JS
        echo '<span class="hidden almaseo-cornerstone-value" data-cornerstone="' . esc_attr( $is_cornerstone ? '1' : '0' ) . '"></span>';
    }

    /**
     * Make column sortable.
     */
    public static function sortable_column( $columns ) {
        $columns['almaseo_cornerstone'] = 'almaseo_cornerstone';
        return $columns;
    }

    /**
     * Handle sorting by cornerstone.
     */
    public static function sort_by_cornerstone( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) return;
        if ( 'almaseo_cornerstone' !== $query->get( 'orderby' ) ) return;

        $query->set( 'meta_key', self::META_KEY );
        $query->set( 'orderby', 'meta_value_num' );
    }

    /**
     * Add filter dropdown to Posts list.
     */
    public static function filter_dropdown( $post_type ) {
        if ( ! in_array( $post_type, array( 'post', 'page' ), true ) ) return;

        $selected = isset( $_GET['almaseo_cornerstone_filter'] ) ? sanitize_text_field( $_GET['almaseo_cornerstone_filter'] ) : '';
        ?>
        <select name="almaseo_cornerstone_filter">
            <option value=""><?php esc_html_e( 'All Posts', 'almaseo-seo-playground' ); ?></option>
            <option value="cornerstone" <?php selected( $selected, 'cornerstone' ); ?>><?php esc_html_e( 'Cornerstone Only', 'almaseo-seo-playground' ); ?></option>
            <option value="non-cornerstone" <?php selected( $selected, 'non-cornerstone' ); ?>><?php esc_html_e( 'Non-Cornerstone', 'almaseo-seo-playground' ); ?></option>
        </select>
        <?php
    }

    /**
     * Modify query based on filter selection.
     */
    public static function filter_query( $query ) {
        if ( ! is_admin() || ! $query->is_main_query() ) return;
        if ( empty( $_GET['almaseo_cornerstone_filter'] ) ) return;

        $filter = sanitize_text_field( $_GET['almaseo_cornerstone_filter'] );

        if ( 'cornerstone' === $filter ) {
            $query->set( 'meta_query', array(
                array(
                    'key'     => self::META_KEY,
                    'value'   => '1',
                    'compare' => '=',
                ),
            ) );
        } elseif ( 'non-cornerstone' === $filter ) {
            $query->set( 'meta_query', array(
                'relation' => 'OR',
                array(
                    'key'     => self::META_KEY,
                    'compare' => 'NOT EXISTS',
                ),
                array(
                    'key'     => self::META_KEY,
                    'value'   => '1',
                    'compare' => '!=',
                ),
            ) );
        }
    }

    /**
     * Add Quick Edit checkbox field.
     */
    public static function quick_edit_field( $column_name, $post_type ) {
        if ( 'almaseo_cornerstone' !== $column_name ) return;
        if ( ! in_array( $post_type, array( 'post', 'page' ), true ) ) return;
        ?>
        <fieldset class="inline-edit-col-right" style="clear: both;">
            <div class="inline-edit-col">
                <label class="inline-edit-cornerstone">
                    <input type="checkbox" name="almaseo_is_cornerstone" value="1" />
                    <span class="checkbox-title"><?php esc_html_e( 'Cornerstone Content', 'almaseo-seo-playground' ); ?></span>
                </label>
            </div>
        </fieldset>
        <?php
    }

    /**
     * Save Quick Edit cornerstone value.
     */
    public static function save_quick_edit( $post_id, $post ) {
        // Only process if this looks like a Quick Edit save (no our main nonce)
        if ( isset( $_POST['almaseo_seo_playground_nonce'] ) ) return;
        if ( ! current_user_can( 'edit_post', $post_id ) ) return;
        if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
        if ( wp_is_post_revision( $post_id ) ) return;

        // Only process for inline-edit action
        if ( ! isset( $_POST['_inline_edit'] ) ) return;
        if ( ! wp_verify_nonce( $_POST['_inline_edit'], 'inlineeditnonce' ) ) return;

        if ( isset( $_POST['almaseo_is_cornerstone'] ) ) {
            update_post_meta( $post_id, self::META_KEY, 1 );
        } else {
            delete_post_meta( $post_id, self::META_KEY );
        }
    }

    /**
     * Enqueue Quick Edit JavaScript.
     */
    public static function enqueue_quick_edit_script( $hook ) {
        if ( ! in_array( $hook, array( 'edit.php' ), true ) ) return;

        $screen = get_current_screen();
        if ( ! $screen || ! in_array( $screen->post_type, array( 'post', 'page' ), true ) ) return;

        wp_add_inline_script( 'inline-edit-post', self::get_quick_edit_js() );
    }

    /**
     * Return inline JS for Quick Edit cornerstone checkbox population.
     */
    private static function get_quick_edit_js() {
        return "
        (function($) {
            var origInlineEdit = inlineEditPost.edit;
            inlineEditPost.edit = function(id) {
                origInlineEdit.apply(this, arguments);
                var postId = 0;
                if (typeof id === 'object') {
                    postId = parseInt(this.getId(id));
                }
                if (postId > 0) {
                    var row = $('#post-' + postId);
                    var cornerstone = row.find('.almaseo-cornerstone-value').data('cornerstone');
                    var editRow = $('#edit-' + postId);
                    editRow.find('input[name=\"almaseo_is_cornerstone\"]').prop('checked', cornerstone == 1);
                }
            };
        })(jQuery);
        ";
    }

    /**
     * Add minimal admin CSS for the cornerstone column.
     */
    public static function admin_css() {
        $screen = get_current_screen();
        if ( ! $screen || ! in_array( $screen->base, array( 'edit' ), true ) ) return;
        ?>
        <style>
            .column-almaseo_cornerstone { width: 40px; text-align: center; }
            .column-almaseo_cornerstone .dashicons { font-size: 18px; width: 18px; height: 18px; }
        </style>
        <?php
    }
}
