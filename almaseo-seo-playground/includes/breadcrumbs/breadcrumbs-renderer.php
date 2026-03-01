<?php
/**
 * AlmaSEO Breadcrumbs Renderer
 *
 * Renders breadcrumb HTML and Schema.org JSON-LD.
 *
 * @package AlmaSEO
 * @subpackage Breadcrumbs
 * @since 7.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AlmaSEO_Breadcrumbs_Renderer
 *
 * Handles rendering of breadcrumb HTML and JSON-LD schema markup.
 */
class AlmaSEO_Breadcrumbs_Renderer {

    /**
     * Render breadcrumbs (HTML + optional Schema)
     *
     * @param array $args Render arguments (can override settings)
     * @return string HTML output
     */
    public static function render($args = array()) {
        $settings = AlmaSEO_Breadcrumbs_Loader::get_settings();

        // Check if enabled
        if (!$settings['enabled']) {
            return '';
        }

        // Merge args with settings
        $options = wp_parse_args($args, array(
            'separator'    => $settings['separator'],
            'home_text'    => $settings['home_text'],
            'show_current' => $settings['show_current'] ? 'yes' : 'no',
            'schema'       => $settings['schema_output'] ? 'yes' : 'no',
            'class'        => '',
        ));

        // Apply settings filter for this render
        $render_settings = $settings;
        if (!empty($args['home_text'])) {
            $render_settings['home_text'] = $args['home_text'];
        }
        if (isset($args['show_current'])) {
            $render_settings['show_current'] = ($args['show_current'] === 'yes' || $args['show_current'] === true);
        }

        // Temporarily filter settings
        $filter_callback = function($s) use ($render_settings) {
            return $render_settings;
        };
        add_filter('almaseo_breadcrumbs_settings_override', $filter_callback);

        // Build breadcrumb trail
        $breadcrumbs = AlmaSEO_Breadcrumbs_Builder::build();

        remove_filter('almaseo_breadcrumbs_settings_override', $filter_callback);

        if (empty($breadcrumbs)) {
            return '';
        }

        $output = '';

        // HTML output
        $output .= self::render_html($breadcrumbs, $options);

        // Schema output (only if requested and not filtered out)
        if ($options['schema'] === 'yes' || $options['schema'] === true) {
            /**
             * Filter to skip schema output
             *
             * @param bool $skip Whether to skip schema output
             */
            if (!apply_filters('almaseo_breadcrumbs_skip_schema', false)) {
                $output .= self::render_schema($breadcrumbs);
            }
        }

        return $output;
    }

    /**
     * Render HTML breadcrumbs
     *
     * @param array $breadcrumbs Breadcrumb items
     * @param array $options     Render options
     * @return string HTML
     */
    private static function render_html($breadcrumbs, $options) {
        $settings = AlmaSEO_Breadcrumbs_Loader::get_settings();

        // Generate unique ID for this breadcrumb instance
        $instance_id = 'almaseo-bc-' . wp_rand(1000, 9999);

        $classes = 'almaseo-breadcrumbs ' . $instance_id;
        if (!empty($options['class'])) {
            $classes .= ' ' . esc_attr($options['class']);
        }

        $separator = html_entity_decode($options['separator'], ENT_QUOTES, 'UTF-8');

        // Ensure separator is not empty - fallback to default
        $separator = trim($separator);
        if (empty($separator)) {
            $separator = '>';
        }

        // Build inline styles using color settings
        $html = self::render_inline_styles($instance_id, $settings);

        $html .= '<nav class="' . esc_attr($classes) . '" aria-label="' . esc_attr__('Breadcrumb', 'almaseo') . '">';
        $html .= '<ol class="breadcrumb-list">';

        $total    = count($breadcrumbs);
        $position = 0;

        foreach ($breadcrumbs as $index => $crumb) {
            $position++;
            $is_last = ($index === $total - 1);

            $item_class = 'breadcrumb-item';
            if ($is_last || !empty($crumb['is_current'])) {
                $item_class .= ' breadcrumb-item--current';
            }

            $html .= '<li class="' . esc_attr($item_class) . '">';

            if (!$is_last && !empty($crumb['url'])) {
                $html .= '<a href="' . esc_url($crumb['url']) . '">';
                $html .= '<span>' . esc_html($crumb['text']) . '</span>';
                $html .= '</a>';
            } else {
                $html .= '<span>' . esc_html($crumb['text']) . '</span>';
            }

            $html .= '</li>';

            // Add separator (except after last item)
            if (!$is_last) {
                $html .= '<li class="breadcrumb-separator" aria-hidden="true">' . esc_html($separator) . '</li>';
            }
        }

        $html .= '</ol>';
        $html .= '</nav>';

        return $html;
    }

    /**
     * Render JSON-LD schema
     *
     * @param array $breadcrumbs Breadcrumb items
     * @return string JSON-LD script tag
     */
    private static function render_schema($breadcrumbs) {
        $items    = array();
        $position = 0;

        foreach ($breadcrumbs as $crumb) {
            $position++;

            $item = array(
                '@type'    => 'ListItem',
                'position' => $position,
                'name'     => $crumb['text'],
            );

            if (!empty($crumb['url'])) {
                $item['item'] = $crumb['url'];
            }

            $items[] = $item;
        }

        $schema = array(
            '@context'        => 'https://schema.org',
            '@type'           => 'BreadcrumbList',
            'itemListElement' => $items,
        );

        $json = wp_json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $output  = "\n<!-- AlmaSEO Breadcrumb Schema -->\n";
        $output .= '<script type="application/ld+json">' . $json . '</script>';
        $output .= "\n<!-- /AlmaSEO Breadcrumb Schema -->\n";

        return $output;
    }

    /**
     * Get raw breadcrumb trail (for external use)
     *
     * @return array Breadcrumb items
     */
    public static function get_trail() {
        return AlmaSEO_Breadcrumbs_Builder::build();
    }

    /**
     * Render inline CSS styles for colors
     *
     * @param string $instance_id Unique instance ID for scoping
     * @param array  $settings    Breadcrumb settings
     * @return string Inline style tag
     */
    private static function render_inline_styles($instance_id, $settings) {
        $color_link       = !empty($settings['color_link']) ? $settings['color_link'] : '#0073aa';
        $color_link_hover = !empty($settings['color_link_hover']) ? $settings['color_link_hover'] : '#005177';
        $color_text       = !empty($settings['color_text']) ? $settings['color_text'] : '#1e1e1e';
        $color_separator  = !empty($settings['color_separator']) ? $settings['color_separator'] : '#757575';

        $css = "
.{$instance_id} .breadcrumb-item a {
    color: {$color_link};
}
.{$instance_id} .breadcrumb-item a:hover,
.{$instance_id} .breadcrumb-item a:focus {
    color: {$color_link_hover};
}
.{$instance_id} .breadcrumb-item--current span {
    color: {$color_text};
}
.{$instance_id} .breadcrumb-separator {
    color: {$color_separator};
}
";

        return '<style type="text/css">' . $css . '</style>';
    }
}
