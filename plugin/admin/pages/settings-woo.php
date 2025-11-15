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
        echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'almaseo') . '</p></div>';
        // Reload settings
        $settings = AlmaSEO_Woo_Loader::get_settings();
    }
}
?>

<div class="wrap almaseo-woo-settings">
    <h1><?php _e('WooCommerce SEO Settings', 'almaseo'); ?></h1>
    
    <div class="almaseo-pro-badge">
        <span class="dashicons dashicons-awards"></span>
        <?php _e('Pro Feature', 'almaseo'); ?>
    </div>
    
    <p class="description">
        <?php _e('Optimize your WooCommerce store for search engines with advanced SEO features.', 'almaseo'); ?>
    </p>
    
    <form method="post" action="">
        <?php wp_nonce_field('save_almaseo_woo_settings', 'almaseo_woo_settings_nonce'); ?>
        
        <!-- Schema Settings -->
        <h2 class="title"><?php _e('Product Schema', 'almaseo'); ?></h2>
        <p><?php _e('Configure how product structured data appears in search results.', 'almaseo'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Product Schema', 'almaseo'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_product_schema" value="1" 
                               <?php checked($settings['enable_product_schema'], true); ?> />
                        <?php _e('Add Product schema markup to product pages', 'almaseo'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Helps search engines understand your products and can enable rich snippets.', 'almaseo'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Schema Options', 'almaseo'); ?></th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="show_price_in_schema" value="1" 
                                   <?php checked($settings['show_price_in_schema'], true); ?> />
                            <?php _e('Include price in schema', 'almaseo'); ?>
                        </label>
                        <br>
                        
                        <label>
                            <input type="checkbox" name="show_stock_in_schema" value="1" 
                                   <?php checked($settings['show_stock_in_schema'], true); ?> />
                            <?php _e('Include stock status in schema', 'almaseo'); ?>
                        </label>
                        <br>
                        
                        <label>
                            <input type="checkbox" name="show_reviews_in_schema" value="1" 
                                   <?php checked($settings['show_reviews_in_schema'], true); ?> />
                            <?php _e('Include reviews and ratings in schema', 'almaseo'); ?>
                        </label>
                    </fieldset>
                </td>
            </tr>
        </table>
        
        <!-- Breadcrumbs Settings -->
        <h2 class="title"><?php _e('Breadcrumbs', 'almaseo'); ?></h2>
        <p><?php _e('Enhanced breadcrumb navigation with schema markup.', 'almaseo'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Enable Breadcrumbs', 'almaseo'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_breadcrumbs" value="1" 
                               <?php checked($settings['enable_breadcrumbs'], true); ?> />
                        <?php _e('Replace WooCommerce breadcrumbs with enhanced version', 'almaseo'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Adds BreadcrumbList schema markup for better SEO.', 'almaseo'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="breadcrumb_separator"><?php _e('Separator', 'almaseo'); ?></label>
                </th>
                <td>
                    <input type="text" id="breadcrumb_separator" name="breadcrumb_separator" 
                           value="<?php echo esc_attr($settings['breadcrumb_separator']); ?>" 
                           class="small-text" />
                    <p class="description">
                        <?php _e('Character(s) to separate breadcrumb items.', 'almaseo'); ?>
                    </p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="breadcrumb_home_text"><?php _e('Home Text', 'almaseo'); ?></label>
                </th>
                <td>
                    <input type="text" id="breadcrumb_home_text" name="breadcrumb_home_text" 
                           value="<?php echo esc_attr($settings['breadcrumb_home_text']); ?>" 
                           class="regular-text" />
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="breadcrumb_shop_text"><?php _e('Shop Text', 'almaseo'); ?></label>
                </th>
                <td>
                    <input type="text" id="breadcrumb_shop_text" name="breadcrumb_shop_text" 
                           value="<?php echo esc_attr($settings['breadcrumb_shop_text']); ?>" 
                           class="regular-text" />
                </td>
            </tr>
        </table>
        
        <!-- Sitemap Settings -->
        <h2 class="title"><?php _e('XML Sitemap', 'almaseo'); ?></h2>
        <p><?php _e('Include WooCommerce products in your XML sitemap.', 'almaseo'); ?></p>
        
        <table class="form-table">
            <tr>
                <th scope="row"><?php _e('Product Sitemap', 'almaseo'); ?></th>
                <td>
                    <label>
                        <input type="checkbox" name="enable_product_sitemap" value="1" 
                               <?php checked($settings['enable_product_sitemap'], true); ?> />
                        <?php _e('Include products in XML sitemap', 'almaseo'); ?>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Product Settings', 'almaseo'); ?></th>
                <td>
                    <label for="product_sitemap_priority">
                        <?php _e('Priority:', 'almaseo'); ?>
                        <select id="product_sitemap_priority" name="product_sitemap_priority">
                            <?php
                            $priorities = array('0.1', '0.2', '0.3', '0.4', '0.5', '0.6', '0.7', '0.8', '0.9', '1.0');
                            foreach ($priorities as $priority) {
                                echo '<option value="' . $priority . '"' . selected($settings['product_sitemap_priority'], $priority, false) . '>' . $priority . '</option>';
                            }
                            ?>
                        </select>
                    </label>
                    
                    <label for="product_sitemap_changefreq">
                        <?php _e('Change Frequency:', 'almaseo'); ?>
                        <select id="product_sitemap_changefreq" name="product_sitemap_changefreq">
                            <?php
                            $frequencies = array(
                                'always' => __('Always', 'almaseo'),
                                'hourly' => __('Hourly', 'almaseo'),
                                'daily' => __('Daily', 'almaseo'),
                                'weekly' => __('Weekly', 'almaseo'),
                                'monthly' => __('Monthly', 'almaseo'),
                                'yearly' => __('Yearly', 'almaseo'),
                                'never' => __('Never', 'almaseo')
                            );
                            foreach ($frequencies as $value => $label) {
                                echo '<option value="' . $value . '"' . selected($settings['product_sitemap_changefreq'], $value, false) . '>' . $label . '</option>';
                            }
                            ?>
                        </select>
                    </label>
                </td>
            </tr>
            
            <tr>
                <th scope="row"><?php _e('Category Settings', 'almaseo'); ?></th>
                <td>
                    <label for="category_sitemap_priority">
                        <?php _e('Priority:', 'almaseo'); ?>
                        <select id="category_sitemap_priority" name="category_sitemap_priority">
                            <?php
                            foreach ($priorities as $priority) {
                                echo '<option value="' . $priority . '"' . selected($settings['category_sitemap_priority'], $priority, false) . '>' . $priority . '</option>';
                            }
                            ?>
                        </select>
                    </label>
                    
                    <label for="category_sitemap_changefreq">
                        <?php _e('Change Frequency:', 'almaseo'); ?>
                        <select id="category_sitemap_changefreq" name="category_sitemap_changefreq">
                            <?php
                            foreach ($frequencies as $value => $label) {
                                echo '<option value="' . $value . '"' . selected($settings['category_sitemap_changefreq'], $value, false) . '>' . $label . '</option>';
                            }
                            ?>
                        </select>
                    </label>
                </td>
            </tr>
        </table>
        
        <!-- Shortcodes Reference -->
        <h2 class="title"><?php _e('Available Shortcodes', 'almaseo'); ?></h2>
        <div class="almaseo-shortcodes-reference">
            <table class="widefat">
                <thead>
                    <tr>
                        <th><?php _e('Shortcode', 'almaseo'); ?></th>
                        <th><?php _e('Description', 'almaseo'); ?></th>
                        <th><?php _e('Parameters', 'almaseo'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><code>[almaseo_woo_breadcrumbs]</code></td>
                        <td><?php _e('Display WooCommerce breadcrumbs', 'almaseo'); ?></td>
                        <td>
                            <code>separator</code> - <?php _e('Custom separator', 'almaseo'); ?><br>
                            <code>home_text</code> - <?php _e('Home link text', 'almaseo'); ?><br>
                            <code>shop_text</code> - <?php _e('Shop link text', 'almaseo'); ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <?php submit_button(__('Save Settings', 'almaseo')); ?>
    </form>
    
    <!-- Help Section -->
    <div class="almaseo-help-section">
        <h2><?php _e('Need Help?', 'almaseo'); ?></h2>
        <p>
            <?php _e('Check out our', 'almaseo'); ?> 
            <a href="https://almaseo.com/docs/woocommerce-seo" target="_blank">
                <?php _e('WooCommerce SEO documentation', 'almaseo'); ?>
            </a> 
            <?php _e('for detailed guides and best practices.', 'almaseo'); ?>
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