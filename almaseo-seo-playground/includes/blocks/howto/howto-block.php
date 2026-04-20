<?php
/**
 * How-To Block — Server-side registration and rendering
 *
 * Registers the almaseo/howto Gutenberg block that outputs a step-by-step
 * guide together with HowTo JSON-LD schema.
 *
 * @package AlmaSEO
 * @since   8.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_HowTo_Block {

    /**
     * Hook into WordPress.
     */
    public static function init() {
        add_action( 'init', array( __CLASS__, 'register' ) );
    }

    /**
     * Register block type and associated assets.
     */
    public static function register() {
        self::enqueue_assets();

        register_block_type( 'almaseo/howto', array(
            'attributes'      => array(
                'steps'         => array(
                    'type'    => 'array',
                    'default' => array(),
                    'items'   => array(
                        'type' => 'object',
                    ),
                ),
                'totalTime'     => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'estimatedCost' => array(
                    'type'    => 'string',
                    'default' => '',
                ),
                'currency'      => array(
                    'type'    => 'string',
                    'default' => 'USD',
                ),
                'supply'        => array(
                    'type'    => 'array',
                    'default' => array(),
                    'items'   => array(
                        'type' => 'string',
                    ),
                ),
                'tool'          => array(
                    'type'    => 'array',
                    'default' => array(),
                    'items'   => array(
                        'type' => 'string',
                    ),
                ),
            ),
            'editor_script'   => 'almaseo-howto-block-editor',
            'editor_style'    => 'almaseo-howto-block-editor',
            'style'           => 'almaseo-howto-block',
            'render_callback' => array( __CLASS__, 'render' ),
        ) );
    }

    /**
     * Register (not enqueue) editor and frontend assets.
     */
    private static function enqueue_assets() {
        $version = defined( 'ALMASEO_PLUGIN_VERSION' ) ? ALMASEO_PLUGIN_VERSION : '8.4.0';
        $url     = defined( 'ALMASEO_URL' ) ? ALMASEO_URL : plugin_dir_url( __DIR__ . '/../../' );

        wp_register_script(
            'almaseo-howto-block-editor',
            $url . 'assets/js/howto-block-editor.js',
            array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
            $version,
            true
        );

        wp_register_style(
            'almaseo-howto-block-editor',
            $url . 'assets/css/howto-block-editor.css',
            array(),
            $version
        );

        wp_register_style(
            'almaseo-howto-block',
            $url . 'assets/css/howto-block.css',
            array(),
            $version
        );
    }

    /**
     * Server-side render callback.
     *
     * @param  array  $attributes Block attributes.
     * @param  string $content    Inner block content (unused).
     * @return string             HTML + JSON-LD.
     */
    public static function render( $attributes, $content ) {
        $steps          = isset( $attributes['steps'] ) ? $attributes['steps'] : array();
        $total_time     = isset( $attributes['totalTime'] ) ? trim( $attributes['totalTime'] ) : '';
        $estimated_cost = isset( $attributes['estimatedCost'] ) ? trim( $attributes['estimatedCost'] ) : '';
        $currency       = isset( $attributes['currency'] ) ? trim( $attributes['currency'] ) : 'USD';
        $supplies       = isset( $attributes['supply'] ) ? $attributes['supply'] : array();
        $tools          = isset( $attributes['tool'] ) ? $attributes['tool'] : array();

        /* Filter out empty steps (blank title). */
        $valid_steps = array();
        foreach ( $steps as $step ) {
            $title = isset( $step['title'] ) ? trim( $step['title'] ) : '';
            if ( '' !== $title ) {
                $valid_steps[] = $step;
            }
        }

        if ( empty( $valid_steps ) ) {
            return '';
        }

        /* Filter out empty supply/tool entries. */
        $supplies = array_values( array_filter( $supplies, function ( $item ) {
            return '' !== trim( $item );
        } ) );

        $tools = array_values( array_filter( $tools, function ( $item ) {
            return '' !== trim( $item );
        } ) );

        /* ── HTML output ──────────────────────────────────────────────── */
        $html = '<div class="almaseo-howto-block">';

        /* Supplies section. */
        if ( ! empty( $supplies ) ) {
            $html .= '<div class="almaseo-howto-supplies">';
            $html .= '<h4 class="almaseo-howto-section-title">' . esc_html__( 'Supplies', 'almaseo-seo-playground' ) . '</h4>';
            $html .= '<ul>';
            foreach ( $supplies as $supply ) {
                $html .= '<li>' . esc_html( $supply ) . '</li>';
            }
            $html .= '</ul></div>';
        }

        /* Tools section. */
        if ( ! empty( $tools ) ) {
            $html .= '<div class="almaseo-howto-tools">';
            $html .= '<h4 class="almaseo-howto-section-title">' . esc_html__( 'Tools', 'almaseo-seo-playground' ) . '</h4>';
            $html .= '<ul>';
            foreach ( $tools as $tool ) {
                $html .= '<li>' . esc_html( $tool ) . '</li>';
            }
            $html .= '</ul></div>';
        }

        /* Steps list. */
        $html .= '<ol class="almaseo-howto-steps">';
        $step_num = 0;

        foreach ( $valid_steps as $step ) {
            $step_num++;
            $title   = isset( $step['title'] ) ? trim( $step['title'] ) : '';
            $step_content = isset( $step['content'] ) ? trim( $step['content'] ) : '';
            $image   = isset( $step['image'] ) ? trim( $step['image'] ) : '';

            $html .= '<li class="almaseo-howto-step">';
            $html .= '<h3 class="almaseo-howto-step-title">'
                    . esc_html( sprintf(
                        /* translators: %1$d = step number, %2$s = step title */
                        __( 'Step %1$d: %2$s', 'almaseo-seo-playground' ),
                        $step_num,
                        $title
                    ) )
                    . '</h3>';

            if ( '' !== $image ) {
                $html .= '<img src="' . esc_url( $image ) . '" alt="' . esc_attr( $title ) . '" class="almaseo-howto-step-image" />';
            }

            if ( '' !== $step_content ) {
                $html .= '<div class="almaseo-howto-step-content">' . wp_kses_post( $step_content ) . '</div>';
            }

            $html .= '</li>';
        }

        $html .= '</ol>';
        $html .= '</div>';

        /* ── HowTo JSON-LD ────────────────────────────────────────────── */
        $schema_steps = array();
        $position     = 0;

        foreach ( $valid_steps as $step ) {
            $position++;
            $title        = isset( $step['title'] ) ? trim( $step['title'] ) : '';
            $step_content = isset( $step['content'] ) ? trim( $step['content'] ) : '';
            $image        = isset( $step['image'] ) ? trim( $step['image'] ) : '';

            $schema_step = array(
                '@type'    => 'HowToStep',
                'name'     => $title,
                'text'     => wp_strip_all_tags( $step_content ),
                'position' => $position,
            );

            if ( '' !== $image ) {
                $schema_step['image'] = $image;
            }

            $schema_steps[] = $schema_step;
        }

        $post_title = get_the_title();

        $schema = array(
            '@context' => 'https://schema.org',
            '@type'    => 'HowTo',
            'name'     => $post_title,
            'step'     => $schema_steps,
        );

        if ( '' !== $total_time ) {
            $schema['totalTime'] = $total_time;
        }

        if ( '' !== $estimated_cost ) {
            $schema['estimatedCost'] = array(
                '@type'    => 'MonetaryAmount',
                'currency' => '' !== $currency ? $currency : 'USD',
                'value'    => $estimated_cost,
            );
        }

        if ( ! empty( $supplies ) ) {
            $schema['supply'] = array();
            foreach ( $supplies as $supply ) {
                $schema['supply'][] = array(
                    '@type' => 'HowToSupply',
                    'name'  => $supply,
                );
            }
        }

        if ( ! empty( $tools ) ) {
            $schema['tool'] = array();
            foreach ( $tools as $tool ) {
                $schema['tool'][] = array(
                    '@type' => 'HowToTool',
                    'name'  => $tool,
                );
            }
        }

        $html .= '<script type="application/ld+json">'
                . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
                . '</script>';

        return $html;
    }
}
