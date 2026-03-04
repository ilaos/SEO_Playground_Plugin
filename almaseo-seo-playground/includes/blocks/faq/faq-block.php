<?php
/**
 * FAQ Block — Server-side registration and rendering
 *
 * Registers the almaseo/faq Gutenberg block that outputs an accessible
 * FAQ list (accordion or plain list) together with FAQPage JSON-LD schema.
 *
 * @package AlmaSEO
 * @since   8.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_FAQ_Block {

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

        register_block_type( 'almaseo/faq', array(
            'attributes'      => array(
                'questions' => array(
                    'type'    => 'array',
                    'default' => array(),
                    'items'   => array(
                        'type' => 'object',
                    ),
                ),
                'layout'   => array(
                    'type'    => 'string',
                    'default' => 'accordion',
                ),
            ),
            'editor_script'   => 'almaseo-faq-block-editor',
            'editor_style'    => 'almaseo-faq-block-editor',
            'style'           => 'almaseo-faq-block',
            'render_callback' => array( __CLASS__, 'render' ),
        ) );
    }

    /**
     * Register (not enqueue) editor and frontend assets.
     */
    private static function enqueue_assets() {
        $version = defined( 'ALMASEO_PLUGIN_VERSION' ) ? ALMASEO_PLUGIN_VERSION : '8.3.0';
        $url     = defined( 'ALMASEO_URL' ) ? ALMASEO_URL : plugin_dir_url( __DIR__ . '/../../' );

        wp_register_script(
            'almaseo-faq-block-editor',
            $url . 'assets/js/faq-block-editor.js',
            array( 'wp-blocks', 'wp-element', 'wp-block-editor', 'wp-components', 'wp-i18n' ),
            $version,
            true
        );

        wp_register_style(
            'almaseo-faq-block-editor',
            $url . 'assets/css/faq-block-editor.css',
            array(),
            $version
        );

        wp_register_style(
            'almaseo-faq-block',
            $url . 'assets/css/faq-block.css',
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
        $questions = isset( $attributes['questions'] ) ? $attributes['questions'] : array();
        $layout    = isset( $attributes['layout'] ) ? $attributes['layout'] : 'accordion';

        if ( empty( $questions ) ) {
            return '';
        }

        $layout = in_array( $layout, array( 'accordion', 'list' ), true ) ? $layout : 'accordion';

        $html = '<div class="almaseo-faq-block almaseo-faq-' . esc_attr( $layout ) . '">';

        foreach ( $questions as $item ) {
            $question = isset( $item['question'] ) ? trim( $item['question'] ) : '';
            $answer   = isset( $item['answer'] ) ? trim( $item['answer'] ) : '';

            if ( '' === $question || '' === $answer ) {
                continue;
            }

            if ( 'accordion' === $layout ) {
                $html .= '<details class="almaseo-faq-pair">';
                $html .= '<summary>' . esc_html( $question ) . '</summary>';
                $html .= '<div class="almaseo-faq-answer">' . wp_kses_post( $answer ) . '</div>';
                $html .= '</details>';
            } else {
                $html .= '<div class="almaseo-faq-item">';
                $html .= '<h3 class="almaseo-faq-question">' . esc_html( $question ) . '</h3>';
                $html .= '<div class="almaseo-faq-answer">' . wp_kses_post( $answer ) . '</div>';
                $html .= '</div>';
            }
        }

        $html .= '</div>';

        /* ── FAQPage JSON-LD ────────────────────────────────────────────── */
        $schema_entities = array();

        foreach ( $questions as $item ) {
            $question = isset( $item['question'] ) ? trim( $item['question'] ) : '';
            $answer   = isset( $item['answer'] ) ? trim( $item['answer'] ) : '';

            if ( '' === $question || '' === $answer ) {
                continue;
            }

            $schema_entities[] = array(
                '@type'          => 'Question',
                'name'           => $question,
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text'  => wp_strip_all_tags( $answer ),
                ),
            );
        }

        if ( ! empty( $schema_entities ) ) {
            $schema = array(
                '@context'   => 'https://schema.org',
                '@type'      => 'FAQPage',
                'mainEntity' => $schema_entities,
            );

            $html .= '<script type="application/ld+json">'
                    . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE )
                    . '</script>';
        }

        return $html;
    }
}
