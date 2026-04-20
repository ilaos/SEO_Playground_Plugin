<?php
/**
 * AlmaSEO Breadcrumbs Feature - Loader
 *
 * Main entry point for the general-purpose breadcrumbs feature.
 * Provides shortcode [almaseo_breadcrumbs] and template tag support.
 *
 * @package AlmaSEO
 * @subpackage Breadcrumbs
 * @since 7.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AlmaSEO_Breadcrumbs_Loader
 *
 * Singleton loader for the breadcrumbs feature.
 */
class AlmaSEO_Breadcrumbs_Loader {

    /**
     * Singleton instance
     *
     * @var AlmaSEO_Breadcrumbs_Loader|null
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return AlmaSEO_Breadcrumbs_Loader
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
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        $dir = dirname(__FILE__);

        require_once $dir . '/breadcrumbs-builder.php';
        require_once $dir . '/breadcrumbs-renderer.php';

        if (is_admin()) {
            require_once $dir . '/breadcrumbs-settings.php';
        }
    }

    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Register shortcode
        add_shortcode('almaseo_breadcrumbs', array($this, 'shortcode_handler'));

        // Register legacy breadcrumb shortcodes from other SEO plugins so migrated
        // sites render our breadcrumbs instead of showing raw shortcode text.
        // Only registers each if the original plugin hasn't already claimed it.
        $legacy_shortcodes = array(
            'aioseo_breadcrumbs',      // All in One SEO
            'wpseo_breadcrumb',        // Yoast SEO
            'rank_math_breadcrumb',    // Rank Math
            'seopress_breadcrumbs',    // SEOPress
        );
        foreach ( $legacy_shortcodes as $shortcode ) {
            if ( ! shortcode_exists( $shortcode ) ) {
                add_shortcode( $shortcode, array( $this, 'shortcode_handler' ) );
            }
        }

        // Enqueue frontend styles when needed
        add_action('wp_enqueue_scripts', array($this, 'maybe_enqueue_styles'));

        // Template tag support via action
        add_action('almaseo_breadcrumbs', array($this, 'render_breadcrumbs'));
    }

    /**
     * Shortcode handler
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public function shortcode_handler($atts) {
        $atts = shortcode_atts(array(
            'separator'    => null,
            'home_text'    => null,
            'show_current' => 'yes',
            'schema'       => 'yes',
            'class'        => '',
        ), $atts, 'almaseo_breadcrumbs');

        return AlmaSEO_Breadcrumbs_Renderer::render($atts);
    }

    /**
     * Render breadcrumbs (for template tag)
     *
     * @param array $args Optional arguments
     */
    public function render_breadcrumbs($args = array()) {
        echo AlmaSEO_Breadcrumbs_Renderer::render($args);
    }

    /**
     * Conditionally enqueue frontend styles
     */
    public function maybe_enqueue_styles() {
        $settings = self::get_settings();

        if ($settings['enabled'] && $settings['include_css']) {
            wp_enqueue_style(
                'almaseo-breadcrumbs',
                ALMASEO_URL . 'assets/css/breadcrumbs.css',
                array(),
                defined('ALMASEO_VERSION') ? ALMASEO_VERSION : '7.0.0'
            );
        }
    }

    /**
     * Get breadcrumbs settings with defaults
     *
     * @return array Settings array
     */
    public static function get_settings() {
        $defaults = array(
            'enabled'                => true,
            'separator'              => '>',
            'home_text'              => __('Home', 'almaseo-seo-playground'),
            'show_on_home'           => false,
            'show_current'           => true,
            'include_css'            => true,
            'schema_output'          => true,
            'category_selection'     => 'primary',
            'show_post_type_archive' => true,
            // Color settings
            'color_link'             => '#0073aa',
            'color_link_hover'       => '#005177',
            'color_text'             => '#1e1e1e',
            'color_separator'        => '#757575',
        );

        $settings = get_option('almaseo_breadcrumbs_settings', array());

        $merged = wp_parse_args($settings, $defaults);

        // Ensure separator is not empty (could happen from old saved data)
        if (empty(trim($merged['separator']))) {
            $merged['separator'] = '>';
        }

        return $merged;
    }

    /**
     * Save settings
     *
     * @param array $settings Settings to save
     * @return bool Success
     */
    public static function save_settings($settings) {
        $clean = array();

        $clean['enabled']                = isset($settings['enabled']) ? (bool) $settings['enabled'] : false;
        $clean['separator']              = isset($settings['separator']) ? wp_kses_post($settings['separator']) : '>';
        $clean['home_text']              = isset($settings['home_text']) ? sanitize_text_field($settings['home_text']) : __('Home', 'almaseo-seo-playground');
        $clean['show_on_home']           = isset($settings['show_on_home']) ? (bool) $settings['show_on_home'] : false;
        $clean['show_current']           = isset($settings['show_current']) ? (bool) $settings['show_current'] : true;
        $clean['include_css']            = isset($settings['include_css']) ? (bool) $settings['include_css'] : true;
        $clean['schema_output']          = isset($settings['schema_output']) ? (bool) $settings['schema_output'] : true;
        $clean['show_post_type_archive'] = isset($settings['show_post_type_archive']) ? (bool) $settings['show_post_type_archive'] : true;

        $valid_methods = array('primary', 'deepest', 'first');
        $clean['category_selection'] = isset($settings['category_selection']) && in_array($settings['category_selection'], $valid_methods, true)
            ? $settings['category_selection']
            : 'primary';

        // Color settings
        $clean['color_link']       = isset($settings['color_link']) ? sanitize_hex_color($settings['color_link']) : '#0073aa';
        $clean['color_link_hover'] = isset($settings['color_link_hover']) ? sanitize_hex_color($settings['color_link_hover']) : '#005177';
        $clean['color_text']       = isset($settings['color_text']) ? sanitize_hex_color($settings['color_text']) : '#1e1e1e';
        $clean['color_separator']  = isset($settings['color_separator']) ? sanitize_hex_color($settings['color_separator']) : '#757575';

        return update_option('almaseo_breadcrumbs_settings', $clean);
    }
}

// Initialize on plugins_loaded
add_action('plugins_loaded', function() {
    AlmaSEO_Breadcrumbs_Loader::get_instance();
}, 20);
