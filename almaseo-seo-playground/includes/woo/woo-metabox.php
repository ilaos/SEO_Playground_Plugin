<?php
/**
 * AlmaSEO WooCommerce Product SEO Metabox
 *
 * Adds custom SEO fields to WooCommerce products.
 * Pro feature only.
 *
 * @package AlmaSEO
 * @subpackage WooCommerce
 * @since 6.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AlmaSEO_WC_Metabox {

    /**
     * Initialize metabox
     */
    public static function init() {
        // Only load if Pro feature is available
        if ( ! almaseo_feature_available('woocommerce') ) {
            return;
        }

        // Only load if WooCommerce is active
        if ( ! class_exists('WooCommerce') ) {
            return;
        }

        add_action('add_meta_boxes', array(__CLASS__, 'register_metabox'));
        add_action('save_post_product', array(__CLASS__, 'save_metabox'), 10, 2);
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_assets'));
    }

    /**
     * Register the metabox
     */
    public static function register_metabox() {
        add_meta_box(
            'almaseo_wc_seo',
            __('AlmaSEO - Product SEO', 'almaseo'),
            array(__CLASS__, 'render_metabox'),
            'product',
            'normal',
            'high'
        );
    }

    /**
     * Render the metabox content
     */
    public static function render_metabox($post) {
        // Add nonce for security
        wp_nonce_field('almaseo_wc_seo_metabox', 'almaseo_wc_seo_nonce');

        // Get existing values
        $seo_title = get_post_meta($post->ID, '_almaseo_wc_seo_title', true);
        $meta_description = get_post_meta($post->ID, '_almaseo_wc_meta_description', true);
        $focus_keyword = get_post_meta($post->ID, '_almaseo_wc_focus_keyword', true);
        $noindex = get_post_meta($post->ID, '_almaseo_wc_noindex', true);
        $canonical_url = get_post_meta($post->ID, '_almaseo_wc_canonical', true);

        // Get character counts for limits
        $title_length = mb_strlen($seo_title);
        $desc_length = mb_strlen($meta_description);

        ?>
        <div class="almaseo-wc-seo-metabox">
            <style>
                .almaseo-wc-seo-metabox {
                    padding: 10px 0;
                }
                .almaseo-wc-seo-field {
                    margin-bottom: 20px;
                }
                .almaseo-wc-seo-field label {
                    display: block;
                    font-weight: 600;
                    margin-bottom: 6px;
                    color: #1d2327;
                }
                .almaseo-wc-seo-field input[type="text"],
                .almaseo-wc-seo-field textarea {
                    width: 100%;
                    max-width: 100%;
                }
                .almaseo-wc-seo-field textarea {
                    height: 80px;
                    resize: vertical;
                }
                .almaseo-wc-seo-help {
                    display: block;
                    margin-top: 5px;
                    font-size: 13px;
                    color: #646970;
                    font-style: italic;
                }
                .almaseo-wc-char-counter {
                    float: right;
                    font-size: 12px;
                    color: #646970;
                    margin-top: 3px;
                }
                .almaseo-wc-char-counter.warning {
                    color: #d63638;
                    font-weight: 600;
                }
                .almaseo-wc-char-counter.good {
                    color: #00a32a;
                }
                .almaseo-wc-checkbox-field {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                }
                .almaseo-wc-checkbox-field input[type="checkbox"] {
                    margin: 0;
                    width: 18px;
                    height: 18px;
                }
            </style>

            <!-- SEO Title -->
            <div class="almaseo-wc-seo-field">
                <label for="almaseo_wc_seo_title">
                    <?php _e('SEO Title', 'almaseo'); ?>
                    <span class="almaseo-wc-char-counter" id="title-counter">
                        <span class="count"><?php echo esc_html($title_length); ?></span> / 60
                    </span>
                </label>
                <input
                    type="text"
                    id="almaseo_wc_seo_title"
                    name="almaseo_wc_seo_title"
                    value="<?php echo esc_attr($seo_title); ?>"
                    class="widefat"
                    placeholder="<?php echo esc_attr($post->post_title); ?>"
                    maxlength="100"
                >
                <span class="almaseo-wc-seo-help">
                    <?php _e('Leave empty to use product title. Recommended: 50-60 characters.', 'almaseo'); ?>
                </span>
            </div>

            <!-- Meta Description -->
            <div class="almaseo-wc-seo-field">
                <label for="almaseo_wc_meta_description">
                    <?php _e('Meta Description', 'almaseo'); ?>
                    <span class="almaseo-wc-char-counter" id="desc-counter">
                        <span class="count"><?php echo esc_html($desc_length); ?></span> / 160
                    </span>
                </label>
                <textarea
                    id="almaseo_wc_meta_description"
                    name="almaseo_wc_meta_description"
                    class="widefat"
                    placeholder="<?php _e('Enter a compelling product description for search results...', 'almaseo'); ?>"
                    maxlength="320"
                ><?php echo esc_textarea($meta_description); ?></textarea>
                <span class="almaseo-wc-seo-help">
                    <?php _e('Recommended: 150-160 characters. This appears in search results.', 'almaseo'); ?>
                </span>
            </div>

            <!-- Focus Keyword -->
            <div class="almaseo-wc-seo-field">
                <label for="almaseo_wc_focus_keyword">
                    <?php _e('Focus Keyword', 'almaseo'); ?>
                </label>
                <input
                    type="text"
                    id="almaseo_wc_focus_keyword"
                    name="almaseo_wc_focus_keyword"
                    value="<?php echo esc_attr($focus_keyword); ?>"
                    class="widefat"
                    placeholder="<?php _e('e.g., wireless bluetooth headphones', 'almaseo'); ?>"
                >
                <span class="almaseo-wc-seo-help">
                    <?php _e('The main keyword you want this product to rank for.', 'almaseo'); ?>
                </span>
            </div>

            <!-- Noindex Toggle -->
            <div class="almaseo-wc-seo-field">
                <div class="almaseo-wc-checkbox-field">
                    <input
                        type="checkbox"
                        id="almaseo_wc_noindex"
                        name="almaseo_wc_noindex"
                        value="1"
                        <?php checked($noindex, '1'); ?>
                    >
                    <label for="almaseo_wc_noindex" style="margin: 0; font-weight: normal;">
                        <?php _e('Noindex this product (prevent search engines from indexing)', 'almaseo'); ?>
                    </label>
                </div>
                <span class="almaseo-wc-seo-help">
                    <?php _e('Check this to hide this specific product from search engines.', 'almaseo'); ?>
                </span>
            </div>

            <!-- Canonical URL -->
            <div class="almaseo-wc-seo-field">
                <label for="almaseo_wc_canonical">
                    <?php _e('Canonical URL', 'almaseo'); ?>
                </label>
                <input
                    type="url"
                    id="almaseo_wc_canonical"
                    name="almaseo_wc_canonical"
                    value="<?php echo esc_attr($canonical_url); ?>"
                    class="widefat"
                    placeholder="<?php echo esc_url(get_permalink($post->ID)); ?>"
                >
                <span class="almaseo-wc-seo-help">
                    <?php _e('Leave empty to use default URL. Set custom canonical for duplicate products.', 'almaseo'); ?>
                </span>
            </div>

            <script>
            jQuery(document).ready(function($) {
                // Character counter for title
                function updateTitleCounter() {
                    var length = $('#almaseo_wc_seo_title').val().length;
                    var counter = $('#title-counter');
                    counter.find('.count').text(length);

                    counter.removeClass('warning good');
                    if (length > 60) {
                        counter.addClass('warning');
                    } else if (length >= 50 && length <= 60) {
                        counter.addClass('good');
                    }
                }

                // Character counter for description
                function updateDescCounter() {
                    var length = $('#almaseo_wc_meta_description').val().length;
                    var counter = $('#desc-counter');
                    counter.find('.count').text(length);

                    counter.removeClass('warning good');
                    if (length > 160) {
                        counter.addClass('warning');
                    } else if (length >= 150 && length <= 160) {
                        counter.addClass('good');
                    }
                }

                $('#almaseo_wc_seo_title').on('input', updateTitleCounter);
                $('#almaseo_wc_meta_description').on('input', updateDescCounter);

                // Initial update
                updateTitleCounter();
                updateDescCounter();
            });
            </script>
        </div>
        <?php
    }

    /**
     * Save metabox data
     */
    public static function save_metabox($post_id, $post) {
        // Verify nonce
        if (!isset($_POST['almaseo_wc_seo_nonce']) ||
            !wp_verify_nonce($_POST['almaseo_wc_seo_nonce'], 'almaseo_wc_seo_metabox')) {
            return;
        }

        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }

        // Check permissions
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }

        // Save SEO Title
        if (isset($_POST['almaseo_wc_seo_title'])) {
            update_post_meta(
                $post_id,
                '_almaseo_wc_seo_title',
                sanitize_text_field($_POST['almaseo_wc_seo_title'])
            );
        }

        // Save Meta Description
        if (isset($_POST['almaseo_wc_meta_description'])) {
            update_post_meta(
                $post_id,
                '_almaseo_wc_meta_description',
                sanitize_textarea_field($_POST['almaseo_wc_meta_description'])
            );
        }

        // Save Focus Keyword
        if (isset($_POST['almaseo_wc_focus_keyword'])) {
            update_post_meta(
                $post_id,
                '_almaseo_wc_focus_keyword',
                sanitize_text_field($_POST['almaseo_wc_focus_keyword'])
            );
        }

        // Save Noindex
        if (isset($_POST['almaseo_wc_noindex'])) {
            update_post_meta($post_id, '_almaseo_wc_noindex', '1');
        } else {
            delete_post_meta($post_id, '_almaseo_wc_noindex');
        }

        // Save Canonical URL
        if (isset($_POST['almaseo_wc_canonical'])) {
            $canonical = esc_url_raw($_POST['almaseo_wc_canonical']);
            if (!empty($canonical)) {
                update_post_meta($post_id, '_almaseo_wc_canonical', $canonical);
            } else {
                delete_post_meta($post_id, '_almaseo_wc_canonical');
            }
        }
    }

    /**
     * Enqueue admin assets
     */
    public static function enqueue_assets($hook) {
        global $post_type;

        // Only load on product edit screen
        if (('post.php' === $hook || 'post-new.php' === $hook) && 'product' === $post_type) {
            wp_enqueue_script('jquery');
        }
    }
}

// Initialize
AlmaSEO_WC_Metabox::init();
