<?php
/**
 * WooCommerce Meta Fields Handler
 *
 * @package AlmaSEO
 * @subpackage WooCommerce
 * @since 6.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AlmaSEO_Woo_Meta
 * 
 * Handles SEO meta fields for WooCommerce products and taxonomies
 */
class AlmaSEO_Woo_Meta {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        // Product meta box
        add_action('add_meta_boxes', array($this, 'add_product_meta_box'));
        add_action('save_post_product', array($this, 'save_product_meta'), 10, 3);
        
        // Category/Tag term meta
        add_action('product_cat_add_form_fields', array($this, 'add_term_meta_fields'));
        add_action('product_cat_edit_form_fields', array($this, 'edit_term_meta_fields'), 10, 2);
        add_action('product_tag_add_form_fields', array($this, 'add_term_meta_fields'));
        add_action('product_tag_edit_form_fields', array($this, 'edit_term_meta_fields'), 10, 2);
        
        // Save term meta
        add_action('created_product_cat', array($this, 'save_term_meta'));
        add_action('edited_product_cat', array($this, 'save_term_meta'));
        add_action('created_product_tag', array($this, 'save_term_meta'));
        add_action('edited_product_tag', array($this, 'save_term_meta'));
        
        // Filter title and description
        add_filter('wp_title', array($this, 'filter_product_title'), 20, 2);
        add_filter('wpseo_title', array($this, 'filter_product_title'), 20, 2);
        add_action('wp_head', array($this, 'output_meta_tags'), 5);
    }
    
    /**
     * Add product meta box
     */
    public function add_product_meta_box() {
        add_meta_box(
            'almaseo_woo_meta',
            __('WooCommerce SEO Settings', 'almaseo-seo-playground'),
            array($this, 'render_product_meta_box'),
            'product',
            'normal',
            'high'
        );
    }
    
    /**
     * Render product meta box
     */
    public function render_product_meta_box($post) {
        // Get saved values
        $seo_title = get_post_meta($post->ID, '_almaseo_woo_title', true);
        $meta_desc = get_post_meta($post->ID, '_almaseo_woo_description', true);
        $og_title = get_post_meta($post->ID, '_almaseo_woo_og_title', true);
        $og_desc = get_post_meta($post->ID, '_almaseo_woo_og_description', true);
        $og_image = get_post_meta($post->ID, '_almaseo_woo_og_image', true);
        $twitter_title = get_post_meta($post->ID, '_almaseo_woo_twitter_title', true);
        $twitter_desc = get_post_meta($post->ID, '_almaseo_woo_twitter_description', true);
        $noindex = get_post_meta($post->ID, '_almaseo_woo_noindex', true);
        $nofollow = get_post_meta($post->ID, '_almaseo_woo_nofollow', true);
        
        // Nonce field
        wp_nonce_field('almaseo_woo_meta_nonce', 'almaseo_woo_meta_nonce');
        
        // Get product data for preview
        $product = wc_get_product($post->ID);
        $price = $product ? $product->get_price() : '';
        ?>
        
        <div class="almaseo-woo-meta-wrapper">
            <!-- SEO Preview -->
            <div class="almaseo-woo-preview">
                <h3><?php esc_html_e('Search Engine Preview', 'almaseo-seo-playground'); ?></h3>
                <div class="almaseo-preview-box">
                    <div class="preview-title" id="preview-title">
                        <?php echo esc_html($seo_title ?: $post->post_title); ?>
                    </div>
                    <div class="preview-url">
                        <?php echo esc_url(get_permalink($post->ID)); ?>
                    </div>
                    <div class="preview-description" id="preview-description">
                        <?php echo esc_html($meta_desc ?: wp_trim_words($post->post_excerpt ?: $post->post_content, 20)); ?>
                    </div>
                    <?php if ($price): ?>
                    <div class="preview-price">
                        <?php
                        /* translators: %s: formatted product price (HTML) */
                        echo wp_kses_post(sprintf(__('Price: %s', 'almaseo-seo-playground'), wc_price($price))); ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Basic SEO -->
            <div class="almaseo-woo-section">
                <h3><?php esc_html_e('Basic SEO', 'almaseo-seo-playground'); ?></h3>
                
                <div class="almaseo-field">
                    <label for="almaseo_woo_title">
                        <?php esc_html_e('SEO Title', 'almaseo-seo-playground'); ?>
                        <span class="almaseo-char-count" data-target="almaseo_woo_title" data-max="60">
                            <span class="count">0</span>/60
                        </span>
                    </label>
                    <input type="text" id="almaseo_woo_title" name="almaseo_woo_title" 
                           value="<?php echo esc_attr($seo_title); ?>" 
                           placeholder="<?php echo esc_attr($post->post_title); ?>" />
                    <p class="description"><?php esc_html_e('Leave empty to use product title. Recommended: 50-60 characters.', 'almaseo-seo-playground'); ?></p>
                </div>
                
                <div class="almaseo-field">
                    <label for="almaseo_woo_description">
                        <?php esc_html_e('Meta Description', 'almaseo-seo-playground'); ?>
                        <span class="almaseo-char-count" data-target="almaseo_woo_description" data-max="160">
                            <span class="count">0</span>/160
                        </span>
                    </label>
                    <textarea id="almaseo_woo_description" name="almaseo_woo_description" 
                              rows="3" placeholder="<?php esc_html_e('Brief product description for search results...', 'almaseo-seo-playground'); ?>"><?php echo esc_textarea($meta_desc); ?></textarea>
                    <p class="description"><?php esc_html_e('Recommended: 150-160 characters. Include key features and benefits.', 'almaseo-seo-playground'); ?></p>
                </div>
            </div>
            
            <!-- Social Media -->
            <div class="almaseo-woo-section">
                <h3><?php esc_html_e('Social Media', 'almaseo-seo-playground'); ?></h3>
                
                <!-- OpenGraph -->
                <h4><?php esc_html_e('Facebook/OpenGraph', 'almaseo-seo-playground'); ?></h4>
                
                <div class="almaseo-field">
                    <label for="almaseo_woo_og_title"><?php esc_html_e('OG Title', 'almaseo-seo-playground'); ?></label>
                    <input type="text" id="almaseo_woo_og_title" name="almaseo_woo_og_title" 
                           value="<?php echo esc_attr($og_title); ?>" 
                           placeholder="<?php esc_html_e('Leave empty to use SEO title', 'almaseo-seo-playground'); ?>" />
                </div>
                
                <div class="almaseo-field">
                    <label for="almaseo_woo_og_description"><?php esc_html_e('OG Description', 'almaseo-seo-playground'); ?></label>
                    <textarea id="almaseo_woo_og_description" name="almaseo_woo_og_description" 
                              rows="2"><?php echo esc_textarea($og_desc); ?></textarea>
                </div>
                
                <div class="almaseo-field">
                    <label for="almaseo_woo_og_image"><?php esc_html_e('OG Image URL', 'almaseo-seo-playground'); ?></label>
                    <input type="url" id="almaseo_woo_og_image" name="almaseo_woo_og_image" 
                           value="<?php echo esc_url($og_image); ?>" 
                           placeholder="<?php esc_html_e('Leave empty to use product image', 'almaseo-seo-playground'); ?>" />
                    <button type="button" class="button almaseo-media-upload" 
                            data-target="almaseo_woo_og_image"><?php esc_html_e('Choose Image', 'almaseo-seo-playground'); ?></button>
                </div>
                
                <!-- Twitter -->
                <h4><?php esc_html_e('Twitter/X', 'almaseo-seo-playground'); ?></h4>
                
                <div class="almaseo-field">
                    <label for="almaseo_woo_twitter_title"><?php esc_html_e('Twitter Title', 'almaseo-seo-playground'); ?></label>
                    <input type="text" id="almaseo_woo_twitter_title" name="almaseo_woo_twitter_title" 
                           value="<?php echo esc_attr($twitter_title); ?>" 
                           placeholder="<?php esc_html_e('Leave empty to use OG title', 'almaseo-seo-playground'); ?>" />
                </div>
                
                <div class="almaseo-field">
                    <label for="almaseo_woo_twitter_description"><?php esc_html_e('Twitter Description', 'almaseo-seo-playground'); ?></label>
                    <textarea id="almaseo_woo_twitter_description" name="almaseo_woo_twitter_description" 
                              rows="2"><?php echo esc_textarea($twitter_desc); ?></textarea>
                </div>
            </div>
            
            <!-- Advanced -->
            <div class="almaseo-woo-section">
                <h3><?php esc_html_e('Advanced Settings', 'almaseo-seo-playground'); ?></h3>
                
                <div class="almaseo-field almaseo-field-inline">
                    <label>
                        <input type="checkbox" name="almaseo_woo_noindex" value="1" 
                               <?php checked($noindex, '1'); ?> />
                        <?php esc_html_e('NoIndex - Prevent search engines from indexing this product', 'almaseo-seo-playground'); ?>
                    </label>
                </div>
                
                <div class="almaseo-field almaseo-field-inline">
                    <label>
                        <input type="checkbox" name="almaseo_woo_nofollow" value="1" 
                               <?php checked($nofollow, '1'); ?> />
                        <?php esc_html_e('NoFollow - Tell search engines not to follow links', 'almaseo-seo-playground'); ?>
                    </label>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Save product meta
     */
    public function save_product_meta($post_id, $post, $update) {
        // Check nonce
        if (!isset($_POST['almaseo_woo_meta_nonce']) ||
            !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['almaseo_woo_meta_nonce'])), 'almaseo_woo_meta_nonce')) {
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
        
        // Save text fields
        $text_fields = array(
            'almaseo_woo_title',
            'almaseo_woo_description',
            'almaseo_woo_og_title',
            'almaseo_woo_og_description',
            'almaseo_woo_og_image',
            'almaseo_woo_twitter_title',
            'almaseo_woo_twitter_description'
        );
        
        foreach ($text_fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field(wp_unslash($_POST[$field]));
                if ($field === 'almaseo_woo_og_image') {
                    $value = esc_url_raw(wp_unslash($_POST[$field]));
                }
                update_post_meta($post_id, '_' . $field, $value);
            }
        }
        
        // Save checkboxes
        $checkbox_fields = array('almaseo_woo_noindex', 'almaseo_woo_nofollow');
        
        foreach ($checkbox_fields as $field) {
            $value = isset($_POST[$field]) ? '1' : '';
            update_post_meta($post_id, '_' . $field, $value);
        }
    }
    
    /**
     * Add term meta fields (for add new form)
     */
    public function add_term_meta_fields($taxonomy) {
        ?>
        <div class="form-field">
            <label for="almaseo_woo_term_title"><?php esc_html_e('SEO Title', 'almaseo-seo-playground'); ?></label>
            <input type="text" name="almaseo_woo_term_title" id="almaseo_woo_term_title" />
            <p class="description"><?php esc_html_e('Custom SEO title for this category/tag', 'almaseo-seo-playground'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="almaseo_woo_term_description"><?php esc_html_e('Meta Description', 'almaseo-seo-playground'); ?></label>
            <textarea name="almaseo_woo_term_description" id="almaseo_woo_term_description" rows="3"></textarea>
            <p class="description"><?php esc_html_e('Meta description for search results', 'almaseo-seo-playground'); ?></p>
        </div>
        
        <div class="form-field">
            <label for="almaseo_woo_term_og_image"><?php esc_html_e('Social Media Image', 'almaseo-seo-playground'); ?></label>
            <input type="url" name="almaseo_woo_term_og_image" id="almaseo_woo_term_og_image" />
            <p class="description"><?php esc_html_e('Image URL for social media sharing', 'almaseo-seo-playground'); ?></p>
        </div>
        <?php
    }
    
    /**
     * Edit term meta fields (for edit form)
     */
    public function edit_term_meta_fields($term, $taxonomy) {
        // Get saved values
        $seo_title = get_term_meta($term->term_id, '_almaseo_woo_term_title', true);
        $meta_desc = get_term_meta($term->term_id, '_almaseo_woo_term_description', true);
        $og_image = get_term_meta($term->term_id, '_almaseo_woo_term_og_image', true);
        $noindex = get_term_meta($term->term_id, '_almaseo_woo_term_noindex', true);
        ?>
        
        <tr class="form-field">
            <th scope="row">
                <label for="almaseo_woo_term_title"><?php esc_html_e('SEO Title', 'almaseo-seo-playground'); ?></label>
            </th>
            <td>
                <input type="text" name="almaseo_woo_term_title" id="almaseo_woo_term_title" 
                       value="<?php echo esc_attr($seo_title); ?>" />
                <p class="description"><?php esc_html_e('Custom SEO title for this category/tag', 'almaseo-seo-playground'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="almaseo_woo_term_description"><?php esc_html_e('Meta Description', 'almaseo-seo-playground'); ?></label>
            </th>
            <td>
                <textarea name="almaseo_woo_term_description" id="almaseo_woo_term_description" 
                          rows="3"><?php echo esc_textarea($meta_desc); ?></textarea>
                <p class="description"><?php esc_html_e('Meta description for search results', 'almaseo-seo-playground'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="almaseo_woo_term_og_image"><?php esc_html_e('Social Media Image', 'almaseo-seo-playground'); ?></label>
            </th>
            <td>
                <input type="url" name="almaseo_woo_term_og_image" id="almaseo_woo_term_og_image" 
                       value="<?php echo esc_url($og_image); ?>" />
                <p class="description"><?php esc_html_e('Image URL for social media sharing', 'almaseo-seo-playground'); ?></p>
            </td>
        </tr>
        
        <tr class="form-field">
            <th scope="row">
                <label for="almaseo_woo_term_noindex"><?php esc_html_e('Search Visibility', 'almaseo-seo-playground'); ?></label>
            </th>
            <td>
                <label>
                    <input type="checkbox" name="almaseo_woo_term_noindex" value="1" 
                           <?php checked($noindex, '1'); ?> />
                    <?php esc_html_e('NoIndex - Prevent search engines from indexing this category', 'almaseo-seo-playground'); ?>
                </label>
            </td>
        </tr>
        <?php
    }
    
    /**
     * Save term meta
     */
    public function save_term_meta($term_id) {
        // Check permissions
        if (!current_user_can('edit_term', $term_id)) {
            return;
        }
        
        // Save text fields
        $fields = array(
            'almaseo_woo_term_title',
            'almaseo_woo_term_description',
            'almaseo_woo_term_og_image'
        );
        
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $value = sanitize_text_field(wp_unslash($_POST[$field]));
                if ($field === 'almaseo_woo_term_og_image') {
                    $value = esc_url_raw(wp_unslash($_POST[$field]));
                }
                update_term_meta($term_id, '_' . $field, $value);
            }
        }
        
        // Save checkbox
        $noindex = isset($_POST['almaseo_woo_term_noindex']) ? '1' : '';
        update_term_meta($term_id, '_almaseo_woo_term_noindex', $noindex);
    }
    
    /**
     * Filter product title
     */
    public function filter_product_title($title, $sep = '') {
        if (!is_singular('product') && !is_tax(array('product_cat', 'product_tag'))) {
            return $title;
        }
        
        // For single products
        if (is_singular('product')) {
            global $post;
            $custom_title = get_post_meta($post->ID, '_almaseo_woo_title', true);
            if ($custom_title) {
                return $custom_title;
            }
        }
        
        // For product categories/tags
        if (is_tax(array('product_cat', 'product_tag'))) {
            $term = get_queried_object();
            if ($term) {
                $custom_title = get_term_meta($term->term_id, '_almaseo_woo_term_title', true);
                if ($custom_title) {
                    return $custom_title;
                }
            }
        }
        
        return $title;
    }
    
    /**
     * Output meta tags
     */
    public function output_meta_tags() {
        // Only on product pages and archives
        if (!is_singular('product') && !is_tax(array('product_cat', 'product_tag'))) {
            return;
        }
        
        $meta_tags = array();
        
        // Single product
        if (is_singular('product')) {
            global $post;
            
            // Meta description
            $meta_desc = get_post_meta($post->ID, '_almaseo_woo_description', true);
            if ($meta_desc) {
                $meta_tags[] = '<meta name="description" content="' . esc_attr($meta_desc) . '" />';
            }
            
            // Robots meta
            $noindex = get_post_meta($post->ID, '_almaseo_woo_noindex', true);
            $nofollow = get_post_meta($post->ID, '_almaseo_woo_nofollow', true);
            if ($noindex || $nofollow) {
                $robots = array();
                if ($noindex) $robots[] = 'noindex';
                if ($nofollow) $robots[] = 'nofollow';
                $meta_tags[] = '<meta name="robots" content="' . esc_attr(implode(',', $robots)) . '" />';
            }
            
            // OpenGraph
            $og_title = get_post_meta($post->ID, '_almaseo_woo_og_title', true);
            $og_desc = get_post_meta($post->ID, '_almaseo_woo_og_description', true);
            $og_image = get_post_meta($post->ID, '_almaseo_woo_og_image', true);
            
            if ($og_title) {
                $meta_tags[] = '<meta property="og:title" content="' . esc_attr($og_title) . '" />';
            }
            if ($og_desc) {
                $meta_tags[] = '<meta property="og:description" content="' . esc_attr($og_desc) . '" />';
            }
            if ($og_image) {
                $meta_tags[] = '<meta property="og:image" content="' . esc_url($og_image) . '" />';
            }
            
            // Twitter
            $twitter_title = get_post_meta($post->ID, '_almaseo_woo_twitter_title', true);
            $twitter_desc = get_post_meta($post->ID, '_almaseo_woo_twitter_description', true);
            
            if ($twitter_title) {
                $meta_tags[] = '<meta name="twitter:title" content="' . esc_attr($twitter_title) . '" />';
            }
            if ($twitter_desc) {
                $meta_tags[] = '<meta name="twitter:description" content="' . esc_attr($twitter_desc) . '" />';
            }
            
            // Product specific
            $product = wc_get_product($post->ID);
            if ($product) {
                $meta_tags[] = '<meta property="og:type" content="product" />';
                $meta_tags[] = '<meta property="product:price:amount" content="' . esc_attr($product->get_price()) . '" />';
                $meta_tags[] = '<meta property="product:price:currency" content="' . esc_attr(get_woocommerce_currency()) . '" />';
                
                if ($product->is_in_stock()) {
                    $meta_tags[] = '<meta property="product:availability" content="in stock" />';
                }
            }
        }
        
        // Product archive
        if (is_tax(array('product_cat', 'product_tag'))) {
            $term = get_queried_object();
            if ($term) {
                // Meta description
                $meta_desc = get_term_meta($term->term_id, '_almaseo_woo_term_description', true);
                if ($meta_desc) {
                    $meta_tags[] = '<meta name="description" content="' . esc_attr($meta_desc) . '" />';
                }
                
                // NoIndex
                $noindex = get_term_meta($term->term_id, '_almaseo_woo_term_noindex', true);
                if ($noindex) {
                    $meta_tags[] = '<meta name="robots" content="noindex" />';
                }
                
                // OG Image
                $og_image = get_term_meta($term->term_id, '_almaseo_woo_term_og_image', true);
                if ($og_image) {
                    $meta_tags[] = '<meta property="og:image" content="' . esc_url($og_image) . '" />';
                }
            }
        }
        
        // Output meta tags
        if (!empty($meta_tags)) {
            echo "\n<!-- AlmaSEO WooCommerce Meta -->\n";
            echo implode("\n", $meta_tags) . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentional HTML meta tags output in wp_head, individual values escaped during construction
            echo "<!-- /AlmaSEO WooCommerce Meta -->\n";
        }
    }
}