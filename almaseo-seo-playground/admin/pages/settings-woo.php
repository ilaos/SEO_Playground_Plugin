<?php
/**
 * WooCommerce SEO Settings Page
 *
 * @package AlmaSEO
 * @subpackage Admin
 * @since 6.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get current settings
$settings = AlmaSEO_Woo_Loader::get_settings();

// Handle form submission
if (isset($_POST['almaseo_woo_settings_nonce']) && 
    wp_verify_nonce($_POST['almaseo_woo_settings_nonce'], 'save_almaseo_woo_settings')) {
    
    // Save settings
    $new_settings = array();
    
    // Boolean settings
    $boolean_fields = array(
        'enable_product_schema',
        'enable_breadcrumbs',
        'enable_product_sitemap',
        'show_price_in_schema',
        'show_stock_in_schema',
        'show_reviews_in_schema'
    );

    // Save global noindex settings (separate options)
    update_option('almaseo_wc_noindex_products', isset($_POST['noindex_products']) ? true : false);
    update_option('almaseo_wc_noindex_product_cats', isset($_POST['noindex_product_cats']) ? true : false);
    update_option('almaseo_wc_noindex_product_tags', isset($_POST['noindex_product_tags']) ? true : false);

    // Save priority settings (optional)
    if (isset($_POST['wc_product_priority'])) {
        update_option('almaseo_wc_product_priority', floatval($_POST['wc_product_priority']));
    }
    if (isset($_POST['wc_category_priority'])) {
        update_option('almaseo_wc_category_priority', floatval($_POST['wc_category_priority']));
    }
    
    foreach ($boolean_fields as $field) {
        $new_settings[$field] = isset($_POST[$field]) ? true : false;
    }
    
    // Text settings
    $text_fields = array(
        'product_sitemap_priority',
        'product_sitemap_changefreq',
        'category_sitemap_priority',
        'category_sitemap_changefreq',
        'breadcrumb_separator',
        'breadcrumb_home_text',
        'breadcrumb_shop_text'
    );
    
    foreach ($text_fields as $field) {
        if (isset($_POST[$field])) {
            $new_settings[$field] = sanitize_text_field($_POST[$field]);
        }
    }
    
    // Save settings
    if (AlmaSEO_Woo_Loader::save_settings($new_settings)) {
        echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully!', 'almaseo-seo-playground') . '</p></div>';
        // Reload settings
        $settings = AlmaSEO_Woo_Loader::get_settings();
    }
}
?>

<div class="wrap almaseo-woo-settings">
    <h1><?php esc_html_e('WooCommerce SEO Settings', 'almaseo-seo-playground'); ?></h1>
    
    <div class="almaseo-pro-badge">
        <span class="dashicons dashicons-awards"></span>
        <?php esc_html_e('Pro Feature', 'almaseo-seo-playground'); ?>
    </div>
    
    <p class="description">
        <?php esc_html_e('Optimize your WooCommerce store for search engines with advanced SEO features.', 'almaseo-seo-playground'); ?>
    </p>
    
    <form method="post" action="">
        <?php wp_nonce_field('save_almaseo_woo_settings', 'almaseo_woo_settings_nonce'); ?>
        
        <!-- Schema Settings -->
        <h2 class="title"><?php esc_html_e('Product Schema', 'almaseo-seo-playground'); ?></h2>
        <p><?php esc_html_e('Configure how product structured data appears in search results.', 'almaseo-seo-playground'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Enable Product Schema', 'almaseo-seo-playground'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_product_schema" value="1" 
                               <?php checked($settings['enable_product_schema'], true); ?> />
                        <?php esc_html_e('Add Product schema markup to product pages', 'almaseo-seo-playground'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Helps search engines understand your products and can enable rich snippets.', 'almaseo-seo-playground'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('Schema Options', 'almaseo-seo-playground'); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="show_price_in_schema" value="1" 
                                   <?php checked($settings['show_price_in_schema'], true); ?> />
                            <?php esc_html_e('Include price in schema', 'almaseo-seo-playground'); ?>
                        </label>
                        <br>
                        
                        <label>
                            <input type="checkbox" name="show_stock_in_schema" value="1" 
                                   <?php checked($settings['show_stock_in_schema'], true); ?> />
                            <?php esc_html_e('Include stock status in schema', 'almaseo-seo-playground'); ?>
                        </label>
                        <br>
                        
                        <label>
                            <input type="checkbox" name="show_reviews_in_schema" value="1" 
                                   <?php checked($settings['show_reviews_in_schema'], true); ?> />
                            <?php esc_html_e('Include reviews and ratings in schema', 'almaseo-seo-playground'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>
        
        <!-- Breadcrumbs Settings -->
        <h2 class="title"><?php esc_html_e('Breadcrumbs', 'almaseo-seo-playground'); ?></h2>
        <p><?php esc_html_e('Enhanced breadcrumb navigation with schema markup.', 'almaseo-seo-playground'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Enable Breadcrumbs', 'almaseo-seo-playground'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_breadcrumbs" value="1" 
                               <?php checked($settings['enable_breadcrumbs'], true); ?> />
                        <?php esc_html_e('Replace WooCommerce breadcrumbs with enhanced version', 'almaseo-seo-playground'); ?>
                    </label>
                    <p class="description">
                        <?php esc_html_e('Adds BreadcrumbList schema markup for better SEO.', 'almaseo-seo-playground'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="breadcrumb_separator"><?php esc_html_e('Separator', 'almaseo-seo-playground'); ?></label>
                </th>
                <td>
                    <input type="text" id="breadcrumb_separator" name="breadcrumb_separator" 
                           value="<?php echo esc_attr($settings['breadcrumb_separator']); ?>" 
                           class="small-text" />
                    <p class="description">
                        <?php esc_html_e('Character(s) to separate breadcrumb items.', 'almaseo-seo-playground'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="breadcrumb_home_text"><?php esc_html_e('Home Text', 'almaseo-seo-playground'); ?></label>
                </th>
                <td>
                    <input type="text" id="breadcrumb_home_text" name="breadcrumb_home_text" 
                           value="<?php echo esc_attr($settings['breadcrumb_home_text']); ?>" 
                           class="regular-text" />
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="breadcrumb_shop_text"><?php esc_html_e('Shop Text', 'almaseo-seo-playground'); ?></label>
                </th>
                <td>
                    <input type="text" id="breadcrumb_shop_text" name="breadcrumb_shop_text" 
                           value="<?php echo esc_attr($settings['breadcrumb_shop_text']); ?>" 
                           class="regular-text" />
                </td>
            </tr>
        </table>
        
        <!-- Sitemap Settings -->
        <h2 class="title"><?php esc_html_e('XML Sitemap', 'almaseo-seo-playground'); ?></h2>
        <p><?php esc_html_e('Include WooCommerce products in your XML sitemap.', 'almaseo-seo-playground'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Product Sitemap', 'almaseo-seo-playground'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_product_sitemap" value="1" 
                               <?php checked($settings['enable_product_sitemap'], true); ?> />
                        <?php esc_html_e('Include products in XML sitemap', 'almaseo-seo-playground'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('Product Settings', 'almaseo-seo-playground'); ?></th>
                <td>
                    <label for="product_sitemap_priority">
                        <?php esc_html_e('Priority:', 'almaseo-seo-playground'); ?>
                        <select id="product_sitemap_priority" name="product_sitemap_priority">
                            <?php
                            $priorities = array('0.1', '0.2', '0.3', '0.4', '0.5', '0.6', '0.7', '0.8', '0.9', '1.0');
                            foreach ($priorities as $priority) {
                                echo '<option value="' . esc_attr($priority) . '"' . selected($settings['product_sitemap_priority'], $priority, false) . '>' . esc_html($priority) . '</option>';
                            }
                            ?>
                        </select>
                    </label>
                    
                    <label for="product_sitemap_changefreq">
                        <?php esc_html_e('Change Frequency:', 'almaseo-seo-playground'); ?>
                        <select id="product_sitemap_changefreq" name="product_sitemap_changefreq">
                            <?php
                            $frequencies = array(
                                'always' => __('Always', 'almaseo-seo-playground'),
                                'hourly' => __('Hourly', 'almaseo-seo-playground'),
                                'daily' => __('Daily', 'almaseo-seo-playground'),
                                'weekly' => __('Weekly', 'almaseo-seo-playground'),
                                'monthly' => __('Monthly', 'almaseo-seo-playground'),
                                'yearly' => __('Yearly', 'almaseo-seo-playground'),
                                'never' => __('Never', 'almaseo-seo-playground')
                            );
                            foreach ($frequencies as $value => $label) {
                                echo '<option value="' . esc_attr($value) . '"' . selected($settings['product_sitemap_changefreq'], $value, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php esc_html_e('Category Settings', 'almaseo-seo-playground'); ?></th>
                <td>
                    <label for="category_sitemap_priority">
                        <?php esc_html_e('Priority:', 'almaseo-seo-playground'); ?>
                        <select id="category_sitemap_priority" name="category_sitemap_priority">
                            <?php
                            foreach ($priorities as $priority) {
                                echo '<option value="' . esc_attr($priority) . '"' . selected($settings['category_sitemap_priority'], $priority, false) . '>' . esc_html($priority) . '</option>';
                            }
                            ?>
                        </select>
                    </label>
                    
                    <label for="category_sitemap_changefreq">
                        <?php esc_html_e('Change Frequency:', 'almaseo-seo-playground'); ?>
                        <select id="category_sitemap_changefreq" name="category_sitemap_changefreq">
                            <?php
                            foreach ($frequencies as $value => $label) {
                                echo '<option value="' . esc_attr($value) . '"' . selected($settings['category_sitemap_changefreq'], $value, false) . '>' . esc_html($label) . '</option>';
                            }
                            ?>
                        </select>
                    </label>
                </td>
            </tr>
        </table>

        <!-- Product Indexing Settings -->
        <h2 class="title"><?php esc_html_e('Product Indexing', 'almaseo-seo-playground'); ?></h2>
        <p><?php esc_html_e('Control which WooCommerce content search engines can index.', 'almaseo-seo-playground'); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row"><?php esc_html_e('Global Noindex Settings', 'almaseo-seo-playground'); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="noindex_products" value="1"
                                   <?php checked(get_option('almaseo_wc_noindex_products', false), true); ?> />
                            <?php esc_html_e('Noindex all products (hide from search engines)', 'almaseo-seo-playground'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Prevents search engines from indexing all product pages. Also adds Disallow rules to robots.txt.', 'almaseo-seo-playground'); ?>
                        </p>
                        <br>

                        <label>
                            <input type="checkbox" name="noindex_product_cats" value="1"
                                   <?php checked(get_option('almaseo_wc_noindex_product_cats', false), true); ?> />
                            <?php esc_html_e('Noindex product categories', 'almaseo-seo-playground'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Prevents search engines from indexing product category archive pages.', 'almaseo-seo-playground'); ?>
                        </p>
                        <br>

                        <label>
                            <input type="checkbox" name="noindex_product_tags" value="1"
                                   <?php checked(get_option('almaseo_wc_noindex_product_tags', false), true); ?> />
                            <?php esc_html_e('Noindex product tags', 'almaseo-seo-playground'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Prevents search engines from indexing product tag archive pages.', 'almaseo-seo-playground'); ?>
                        </p>
                    </fieldset>
                </td>
            </tr>

            <tr>
                <th scope="row"><?php esc_html_e('Sitemap Priority (Optional)', 'almaseo-seo-playground'); ?></th>
                <td>
                    <label for="wc_product_priority">
                        <?php esc_html_e('Products:', 'almaseo-seo-playground'); ?>
                        <select id="wc_product_priority" name="wc_product_priority">
                            <?php
                            $product_priority = (float) get_option('almaseo_wc_product_priority', 0.6);
                            $priorities = array('0.1', '0.2', '0.3', '0.4', '0.5', '0.6', '0.7', '0.8', '0.9', '1.0');
                            foreach ($priorities as $priority) {
                                echo '<option value="' . esc_attr($priority) . '"' . selected($product_priority, (float) $priority, false) . '>' . esc_html($priority) . '</option>';
                            }
                            ?>
                        </select>
                    </label>
                    <br><br>

                    <label for="wc_category_priority">
                        <?php esc_html_e('Product Categories:', 'almaseo-seo-playground'); ?>
                        <select id="wc_category_priority" name="wc_category_priority">
                            <?php
                            $category_priority = (float) get_option('almaseo_wc_category_priority', 0.5);
                            foreach ($priorities as $priority) {
                                echo '<option value="' . esc_attr($priority) . '"' . selected($category_priority, (float) $priority, false) . '>' . esc_html($priority) . '</option>';
                            }
                            ?>
                        </select>
                    </label>

                    <p class="description">
                        <?php esc_html_e('Set priority values for products and categories in XML sitemaps (0.1 = lowest, 1.0 = highest).', 'almaseo-seo-playground'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <!-- Shortcodes Reference -->
        <h2 class="title"><?php esc_html_e('Available Shortcodes', 'almaseo-seo-playground'); ?></h2>
        <div class="almaseo-shortcodes-reference">
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Shortcode', 'almaseo-seo-playground'); ?></th>
                        <th><?php esc_html_e('Description', 'almaseo-seo-playground'); ?></th>
                        <th><?php esc_html_e('Parameters', 'almaseo-seo-playground'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[almaseo_woo_breadcrumbs]</code></td>
                        <td><?php esc_html_e('Display WooCommerce breadcrumbs', 'almaseo-seo-playground'); ?></td>
                        <td>
                            <code>separator</code> - <?php esc_html_e('Custom separator', 'almaseo-seo-playground'); ?><br>
                            <code>home_text</code> - <?php esc_html_e('Home link text', 'almaseo-seo-playground'); ?><br>
                            <code>shop_text</code> - <?php esc_html_e('Shop link text', 'almaseo-seo-playground'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php submit_button(__('Save Settings', 'almaseo-seo-playground')); ?>
    </form>
    
    <!-- Help Section -->
    <div class="almaseo-help-section">
        <h2><?php esc_html_e('Need Help?', 'almaseo-seo-playground'); ?></h2>
        <p>
            <?php esc_html_e('Check out our', 'almaseo-seo-playground'); ?> 
            <a href="https://almaseo.com/docs/woocommerce-seo" target="_blank">
                <?php esc_html_e('WooCommerce SEO documentation', 'almaseo-seo-playground'); ?>
            </a> 
            <?php esc_html_e('for detailed guides and best practices.', 'almaseo-seo-playground'); ?>
        </p>
    </div>
</div>

<style>
.almaseo-woo-settings {
    max-width: 800px;
}

.almaseo-pro-badge {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: #fff;
    padding: 5px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 600;
    margin: 10px 0;
}

.almaseo-pro-badge .dashicons {
    font-size: 16px;
}

.almaseo-shortcodes-reference {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 5px;
    margin: 15px 0;
}

.almaseo-shortcodes-reference table {
    background: #fff;
}

.almaseo-shortcodes-reference code {
    background: #f1f3f5;
    padding: 2px 6px;
    border-radius: 3px;
}

.almaseo-help-section {
    margin-top: 40px;
    padding: 20px;
    background: #f0f8ff;
    border-left: 4px solid #0073aa;
    border-radius: 4px;
}

.form-table label {
    display: inline-block;
    margin-right: 15px;
}

.form-table select {
    margin: 0 5px;
}
</style>