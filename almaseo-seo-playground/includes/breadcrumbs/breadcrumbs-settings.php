<?php
/**
 * AlmaSEO Breadcrumbs Settings
 *
 * Adds breadcrumbs settings section to the main Settings page.
 *
 * @package AlmaSEO
 * @subpackage Breadcrumbs
 * @since 7.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AlmaSEO_Breadcrumbs_Settings
 *
 * Handles breadcrumbs settings registration and rendering.
 */
class AlmaSEO_Breadcrumbs_Settings {

    /**
     * Singleton instance
     *
     * @var AlmaSEO_Breadcrumbs_Settings|null
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return AlmaSEO_Breadcrumbs_Settings
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
        add_action('admin_init', array($this, 'register_settings'));
        add_action('almaseo_settings_sections', array($this, 'render_settings_section'), 25);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_color_picker'));
    }

    /**
     * Enqueue color picker scripts on settings page
     */
    public function enqueue_color_picker($hook) {
        // Match the settings page hook — WordPress generates it as {parent}_page_{slug}
        // but sanitization can vary, so also check the query string as a fallback.
        $is_settings_page = (strpos($hook, 'almaseo-settings') !== false)
            || (isset($_GET['page']) && $_GET['page'] === 'almaseo-settings');
        if (!$is_settings_page) {
            return;
        }

        wp_enqueue_style('wp-color-picker');
        wp_enqueue_script('wp-color-picker');

        // Inline script to initialize color pickers and live font preview
        wp_add_inline_script('wp-color-picker', "
            jQuery(document).ready(function($) {
                $('.almaseo-color-picker').wpColorPicker();

                // Live font preview update
                var preview = document.getElementById('almaseo-breadcrumb-font-preview');
                if (preview) {
                    $('input[name=\"almaseo_breadcrumbs_settings[font_size]\"]').on('input', function() {
                        preview.style.fontSize = this.value + 'px';
                    });
                    $('select[name=\"almaseo_breadcrumbs_settings[font_weight]\"]').on('change', function() {
                        preview.style.fontWeight = this.value;
                    });
                    $('select[name=\"almaseo_breadcrumbs_settings[font_style]\"]').on('change', function() {
                        preview.style.fontStyle = this.value;
                    });
                    $('select[name=\"almaseo_breadcrumbs_settings[separator]\"]').on('change', function() {
                        var decoded = $('<textarea/>').html(this.value).text();
                        preview.querySelectorAll('.almaseo-sep-char').forEach(function(el) {
                            el.textContent = decoded;
                        });
                    });
                }
            });
        ");
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('almaseo_settings', 'almaseo_breadcrumbs_settings', array(
            'type'              => 'array',
            'default'           => array(
                'enabled'                => true,
                'separator'              => '&gt;',
                'home_text'              => __('Home', 'almaseo'),
                'show_on_home'           => false,
                'show_current'           => true,
                'include_css'            => true,
                'schema_output'          => true,
                'category_selection'     => 'primary',
                'show_post_type_archive' => true,
                'color_link'             => '#0073aa',
                'color_link_hover'       => '#005177',
                'color_text'             => '#1e1e1e',
                'color_separator'        => '#757575',
                'font_size'              => '14',
                'font_weight'            => 'normal',
                'font_style'             => 'normal',
            ),
            'sanitize_callback' => array($this, 'sanitize_settings'),
        ));
    }

    /**
     * Sanitize settings
     *
     * @param array $input Input settings
     * @return array Sanitized settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();

        $sanitized['enabled']      = isset($input['enabled']) ? (bool) $input['enabled'] : false;
        $separator = isset($input['separator']) ? wp_kses_post($input['separator']) : '>';
        $sanitized['separator']    = !empty(trim($separator)) ? $separator : '>'; // Ensure not empty
        $sanitized['home_text']    = isset($input['home_text']) ? sanitize_text_field($input['home_text']) : __('Home', 'almaseo');
        $sanitized['show_on_home'] = isset($input['show_on_home']) ? (bool) $input['show_on_home'] : false;
        $sanitized['show_current'] = isset($input['show_current']) ? (bool) $input['show_current'] : true;
        $sanitized['include_css']  = isset($input['include_css']) ? (bool) $input['include_css'] : true;
        $sanitized['schema_output'] = isset($input['schema_output']) ? (bool) $input['schema_output'] : true;
        $sanitized['show_post_type_archive'] = isset($input['show_post_type_archive']) ? (bool) $input['show_post_type_archive'] : true;

        $valid_methods = array('primary', 'deepest', 'first');
        $sanitized['category_selection'] = isset($input['category_selection']) && in_array($input['category_selection'], $valid_methods, true)
            ? $input['category_selection']
            : 'primary';

        // Color settings
        $sanitized['color_link']       = isset($input['color_link']) ? sanitize_hex_color($input['color_link']) : '#0073aa';
        $sanitized['color_link_hover'] = isset($input['color_link_hover']) ? sanitize_hex_color($input['color_link_hover']) : '#005177';
        $sanitized['color_text']       = isset($input['color_text']) ? sanitize_hex_color($input['color_text']) : '#1e1e1e';
        $sanitized['color_separator']  = isset($input['color_separator']) ? sanitize_hex_color($input['color_separator']) : '#757575';

        // Font settings
        $sanitized['font_size'] = isset($input['font_size']) ? max(10, min(24, intval($input['font_size']))) : 14;

        $valid_weights = array('normal', 'bold', '300', '500', '600', '700');
        $sanitized['font_weight'] = isset($input['font_weight']) && in_array($input['font_weight'], $valid_weights, true)
            ? $input['font_weight'] : 'normal';

        $valid_styles = array('normal', 'italic');
        $sanitized['font_style'] = isset($input['font_style']) && in_array($input['font_style'], $valid_styles, true)
            ? $input['font_style'] : 'normal';

        return $sanitized;
    }

    /**
     * Render settings section
     */
    public function render_settings_section() {
        $settings = AlmaSEO_Breadcrumbs_Loader::get_settings();
        ?>
        <div class="almaseo-settings-section" style="margin-top: 40px; padding-top: 40px; border-top: 2px solid #e5e7eb;">
            <h2><?php esc_html_e('Breadcrumbs', 'almaseo'); ?></h2>
            <p class="description" style="margin-bottom: 15px;">
                <?php esc_html_e('Configure breadcrumb navigation for your site. Use the shortcode to display breadcrumbs anywhere.', 'almaseo'); ?>
            </p>

            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e('Enable Breadcrumbs', 'almaseo'); ?></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="almaseo_breadcrumbs_settings[enabled]"
                                   value="1"
                                   <?php checked($settings['enabled'], true); ?>>
                            <?php esc_html_e('Enable breadcrumbs feature', 'almaseo'); ?>
                        </label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="almaseo_breadcrumbs_home_text"><?php esc_html_e('Home Text', 'almaseo'); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="almaseo_breadcrumbs_home_text"
                               name="almaseo_breadcrumbs_settings[home_text]"
                               value="<?php echo esc_attr($settings['home_text']); ?>"
                               class="regular-text">
                        <p class="description">
                            <?php esc_html_e('Text for the home/first breadcrumb item.', 'almaseo'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Display Options', 'almaseo'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox"
                                       name="almaseo_breadcrumbs_settings[show_current]"
                                       value="1"
                                       <?php checked($settings['show_current'], true); ?>>
                                <?php esc_html_e('Show current page in breadcrumbs', 'almaseo'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox"
                                       name="almaseo_breadcrumbs_settings[show_on_home]"
                                       value="1"
                                       <?php checked($settings['show_on_home'], true); ?>>
                                <?php esc_html_e('Show breadcrumbs on homepage', 'almaseo'); ?>
                            </label>
                            <br>
                            <label>
                                <input type="checkbox"
                                       name="almaseo_breadcrumbs_settings[show_post_type_archive]"
                                       value="1"
                                       <?php checked($settings['show_post_type_archive'], true); ?>>
                                <?php esc_html_e('Include post type archive in trail (for custom post types)', 'almaseo'); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="almaseo_breadcrumbs_category_selection"><?php esc_html_e('Category Selection', 'almaseo'); ?></label>
                    </th>
                    <td>
                        <select id="almaseo_breadcrumbs_category_selection"
                                name="almaseo_breadcrumbs_settings[category_selection]">
                            <option value="primary" <?php selected($settings['category_selection'], 'primary'); ?>>
                                <?php esc_html_e('Primary category (Yoast/Rank Math compatible)', 'almaseo'); ?>
                            </option>
                            <option value="deepest" <?php selected($settings['category_selection'], 'deepest'); ?>>
                                <?php esc_html_e('Deepest category (most specific)', 'almaseo'); ?>
                            </option>
                            <option value="first" <?php selected($settings['category_selection'], 'first'); ?>>
                                <?php esc_html_e('First category', 'almaseo'); ?>
                            </option>
                        </select>
                        <p class="description">
                            <?php esc_html_e('How to select category when a post has multiple categories.', 'almaseo'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="almaseo_breadcrumbs_separator"><?php esc_html_e('Separator', 'almaseo'); ?></label>
                    </th>
                    <td>
                        <?php
                        $separators = array(
                            '&gt;'    => '> (chevron)',
                            '&raquo;' => '» (double angle)',
                            '/'       => '/ (slash)',
                            '|'       => '| (pipe)',
                            '&rarr;'  => '→ (arrow)',
                            '&bull;'  => '• (bullet)',
                            '–'       => '– (dash)',
                        );
                        $current_sep = $settings['separator'] ?? '&gt;';
                        ?>
                        <select id="almaseo_breadcrumbs_separator"
                                name="almaseo_breadcrumbs_settings[separator]"
                                style="min-width: 200px;">
                            <?php foreach ($separators as $value => $label) : ?>
                                <option value="<?php echo esc_attr($value); ?>" <?php selected($current_sep, $value); ?>>
                                    <?php echo esc_html(html_entity_decode($label)); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">
                            <?php esc_html_e('Character displayed between breadcrumb items.', 'almaseo'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Font', 'almaseo'); ?></th>
                    <td>
                        <div style="display: flex; flex-wrap: wrap; gap: 20px; align-items: flex-end;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: 500;">
                                    <?php esc_html_e('Size (px)', 'almaseo'); ?>
                                </label>
                                <input type="number"
                                       name="almaseo_breadcrumbs_settings[font_size]"
                                       value="<?php echo esc_attr($settings['font_size'] ?? '14'); ?>"
                                       min="10" max="24" step="1"
                                       style="width: 80px;">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: 500;">
                                    <?php esc_html_e('Weight', 'almaseo'); ?>
                                </label>
                                <select name="almaseo_breadcrumbs_settings[font_weight]" style="min-width: 140px;">
                                    <option value="300" <?php selected($settings['font_weight'] ?? 'normal', '300'); ?>><?php esc_html_e('Light (300)', 'almaseo'); ?></option>
                                    <option value="normal" <?php selected($settings['font_weight'] ?? 'normal', 'normal'); ?>><?php esc_html_e('Normal (400)', 'almaseo'); ?></option>
                                    <option value="500" <?php selected($settings['font_weight'] ?? 'normal', '500'); ?>><?php esc_html_e('Medium (500)', 'almaseo'); ?></option>
                                    <option value="600" <?php selected($settings['font_weight'] ?? 'normal', '600'); ?>><?php esc_html_e('Semi-Bold (600)', 'almaseo'); ?></option>
                                    <option value="bold" <?php selected($settings['font_weight'] ?? 'normal', 'bold'); ?>><?php esc_html_e('Bold (700)', 'almaseo'); ?></option>
                                </select>
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: 500;">
                                    <?php esc_html_e('Style', 'almaseo'); ?>
                                </label>
                                <select name="almaseo_breadcrumbs_settings[font_style]" style="min-width: 120px;">
                                    <option value="normal" <?php selected($settings['font_style'] ?? 'normal', 'normal'); ?>><?php esc_html_e('Normal', 'almaseo'); ?></option>
                                    <option value="italic" <?php selected($settings['font_style'] ?? 'normal', 'italic'); ?>><?php esc_html_e('Italic', 'almaseo'); ?></option>
                                </select>
                            </div>
                        </div>
                        <!-- Live preview -->
                        <div style="margin-top: 12px; padding: 10px 14px; background: #fff; border: 1px solid #ddd; border-radius: 4px; max-width: 600px;">
                            <label style="display: block; margin-bottom: 6px; font-size: 11px; color: #999; text-transform: uppercase; letter-spacing: 0.5px;"><?php esc_html_e('Preview', 'almaseo'); ?></label>
                            <div id="almaseo-breadcrumb-font-preview" style="font-size: <?php echo esc_attr($settings['font_size'] ?? '14'); ?>px; font-weight: <?php echo esc_attr($settings['font_weight'] ?? 'normal'); ?>; font-style: <?php echo esc_attr($settings['font_style'] ?? 'normal'); ?>;">
                                <span style="color: <?php echo esc_attr($settings['color_link'] ?? '#0073aa'); ?>;">Home</span>
                                <span class="almaseo-sep-char" style="color: <?php echo esc_attr($settings['color_separator'] ?? '#757575'); ?>; margin: 0 6px;"><?php echo $current_sep; ?></span>
                                <span style="color: <?php echo esc_attr($settings['color_link'] ?? '#0073aa'); ?>;">Services</span>
                                <span class="almaseo-sep-char" style="color: <?php echo esc_attr($settings['color_separator'] ?? '#757575'); ?>; margin: 0 6px;"><?php echo $current_sep; ?></span>
                                <span style="color: <?php echo esc_attr($settings['color_text'] ?? '#1e1e1e'); ?>;">Current Page</span>
                            </div>
                        </div>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Colors', 'almaseo'); ?></th>
                    <td>
                        <div style="display: flex; flex-wrap: wrap; gap: 20px;">
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: 500;">
                                    <?php esc_html_e('Link Color', 'almaseo'); ?>
                                </label>
                                <input type="text"
                                       name="almaseo_breadcrumbs_settings[color_link]"
                                       value="<?php echo esc_attr($settings['color_link']); ?>"
                                       class="almaseo-color-picker"
                                       data-default-color="#0073aa">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: 500;">
                                    <?php esc_html_e('Link Hover Color', 'almaseo'); ?>
                                </label>
                                <input type="text"
                                       name="almaseo_breadcrumbs_settings[color_link_hover]"
                                       value="<?php echo esc_attr($settings['color_link_hover']); ?>"
                                       class="almaseo-color-picker"
                                       data-default-color="#005177">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: 500;">
                                    <?php esc_html_e('Current Page Color', 'almaseo'); ?>
                                </label>
                                <input type="text"
                                       name="almaseo_breadcrumbs_settings[color_text]"
                                       value="<?php echo esc_attr($settings['color_text']); ?>"
                                       class="almaseo-color-picker"
                                       data-default-color="#1e1e1e">
                            </div>
                            <div>
                                <label style="display: block; margin-bottom: 5px; font-weight: 500;">
                                    <?php esc_html_e('Separator Color', 'almaseo'); ?>
                                </label>
                                <input type="text"
                                       name="almaseo_breadcrumbs_settings[color_separator]"
                                       value="<?php echo esc_attr($settings['color_separator']); ?>"
                                       class="almaseo-color-picker"
                                       data-default-color="#757575">
                            </div>
                        </div>
                        <p class="description" style="margin-top: 10px;">
                            <?php esc_html_e('Click any color field to open the color picker.', 'almaseo'); ?>
                        </p>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e('Styling & Schema', 'almaseo'); ?></th>
                    <td>
                        <fieldset>
                            <label>
                                <input type="checkbox"
                                       name="almaseo_breadcrumbs_settings[include_css]"
                                       value="1"
                                       <?php checked($settings['include_css'], true); ?>>
                                <?php esc_html_e('Include default CSS styling', 'almaseo'); ?>
                            </label>
                            <p class="description" style="margin-left: 24px; margin-top: 4px;">
                                <?php esc_html_e('Disable to use your own styles. Color settings above still apply.', 'almaseo'); ?>
                            </p>
                            <br>
                            <label>
                                <input type="checkbox"
                                       name="almaseo_breadcrumbs_settings[schema_output]"
                                       value="1"
                                       <?php checked($settings['schema_output'], true); ?>>
                                <?php esc_html_e('Output BreadcrumbList JSON-LD schema', 'almaseo'); ?>
                            </label>
                            <p class="description" style="margin-left: 24px; margin-top: 4px;">
                                <?php esc_html_e('Schema is output only where the shortcode is placed.', 'almaseo'); ?>
                            </p>
                        </fieldset>
                    </td>
                </tr>
            </table>

            <!-- Usage Info Box -->
            <div style="background: #f0f6fc; border: 1px solid #c5d9ed; border-radius: 4px; padding: 15px; margin-top: 20px; max-width: 800px;">
                <h3 style="margin-top: 0; font-size: 14px; color: #1d4ed8;"><?php esc_html_e('Usage', 'almaseo'); ?></h3>

                <p style="margin-bottom: 8px;"><strong><?php esc_html_e('Shortcode:', 'almaseo'); ?></strong></p>
                <code style="display: inline-block; background: #fff; padding: 6px 12px; border-radius: 3px; font-size: 13px;">[almaseo_breadcrumbs]</code>

                <p style="margin-top: 15px; margin-bottom: 8px;"><strong><?php esc_html_e('Shortcode attributes:', 'almaseo'); ?></strong></p>
                <ul style="margin-left: 20px; list-style: disc; color: #4b5563;">
                    <li><code>separator="/"</code> &mdash; <?php esc_html_e('Custom separator', 'almaseo'); ?></li>
                    <li><code>home_text="Start"</code> &mdash; <?php esc_html_e('Custom home text', 'almaseo'); ?></li>
                    <li><code>show_current="no"</code> &mdash; <?php esc_html_e('Hide current page', 'almaseo'); ?></li>
                    <li><code>schema="no"</code> &mdash; <?php esc_html_e('Disable JSON-LD output', 'almaseo'); ?></li>
                    <li><code>class="my-class"</code> &mdash; <?php esc_html_e('Add custom CSS class', 'almaseo'); ?></li>
                </ul>

                <p style="margin-top: 15px; margin-bottom: 8px;"><strong><?php esc_html_e('PHP template tag:', 'almaseo'); ?></strong></p>
                <code style="display: inline-block; background: #fff; padding: 6px 12px; border-radius: 3px; font-size: 13px;">&lt;?php do_action('almaseo_breadcrumbs'); ?&gt;</code>
            </div>
        </div>
        <?php
    }
}

// Initialize
AlmaSEO_Breadcrumbs_Settings::get_instance();
