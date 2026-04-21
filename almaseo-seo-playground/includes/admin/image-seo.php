<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AlmaSEO_Image_SEO {

    const OPTION_KEY = 'almaseo_image_seo_settings';

    public static function init() {
        add_action( 'almaseo_settings_sections', array( __CLASS__, 'render_settings' ) );

        $settings = self::get_settings();
        if ( ! $settings['enabled'] ) return;

        add_filter( 'the_content', array( __CLASS__, 'fix_content_images' ), 20 );
        add_filter( 'wp_get_attachment_image_attributes', array( __CLASS__, 'fix_attachment_attributes' ), 10, 3 );
    }

    public static function get_defaults() {
        return array(
            'enabled'           => false,
            'alt_format'        => '%%filename%%',
            'title_format'      => '%%filename%% - %%sitename%%',
            'strip_extension'   => true,
            'override_existing' => false,
        );
    }

    public static function get_settings() {
        return wp_parse_args( get_option( self::OPTION_KEY, array() ), self::get_defaults() );
    }

    public static function clean_filename( $filename ) {
        $name = pathinfo( $filename, PATHINFO_FILENAME );
        $name = str_replace( array( '-', '_' ), ' ', $name );
        $name = ucwords( trim( $name ) );
        return $name;
    }

    public static function apply_format( $format, $filename, $post_title = '' ) {
        $clean = self::clean_filename( $filename );
        $replacements = array(
            '%%filename%%'   => $clean,
            '%%sitename%%'   => get_bloginfo( 'name' ),
            '%%post_title%%' => $post_title,
        );
        return str_replace( array_keys( $replacements ), array_values( $replacements ), $format );
    }

    public static function fix_content_images( $content ) {
        if ( empty( $content ) || is_admin() ) return $content;

        $settings = self::get_settings();
        $post_title = get_the_title();

        return preg_replace_callback( '/<img\b([^>]*)>/i', function( $match ) use ( $settings, $post_title ) {
            $tag = $match[0];
            $attrs = $match[1];

            // Extract src to get filename
            if ( ! preg_match( '/src=["\']([^"\']+)["\']/i', $attrs, $src_match ) ) {
                return $tag;
            }
            $filename = basename( wp_parse_url( $src_match[1], PHP_URL_PATH ) );

            // Alt attribute
            $has_alt = preg_match( '/alt=["\']([^"\']*?)["\']/i', $attrs, $alt_match );
            $alt_empty = $has_alt && trim( $alt_match[1] ) === '';

            if ( ! $has_alt || $alt_empty || $settings['override_existing'] ) {
                // Prefer dashboard AI suggestion if available
                $ai_alt = null;
                if ( class_exists( 'AlmaSEO_Image_SEO_REST' ) ) {
                    global $post;
                    $pid = is_object( $post ) ? $post->ID : get_the_ID();
                    if ( $pid ) {
                        $ai_suggestion = AlmaSEO_Image_SEO_REST::find_suggestion_by_src( $pid, $src_match[1] );
                        if ( $ai_suggestion && ! empty( $ai_suggestion['suggested_alt'] ) && empty( $ai_suggestion['is_decorative'] ) ) {
                            $ai_alt = $ai_suggestion['suggested_alt'];
                        }
                    }
                }
                $new_alt = $ai_alt ? $ai_alt : self::apply_format( $settings['alt_format'], $filename, $post_title );
                if ( $has_alt ) {
                    $tag = preg_replace( '/alt=["\'][^"\']*?["\']/i', 'alt="' . esc_attr( $new_alt ) . '"', $tag );
                } else {
                    $tag = str_replace( '<img ', '<img alt="' . esc_attr( $new_alt ) . '" ', $tag );
                }
            }

            // Title attribute
            $has_title = preg_match( '/title=["\']([^"\']*?)["\']/i', $attrs );
            if ( ! $has_title && ! empty( $settings['title_format'] ) ) {
                $new_title = self::apply_format( $settings['title_format'], $filename, $post_title );
                $tag = str_replace( '<img ', '<img title="' . esc_attr( $new_title ) . '" ', $tag );
            }

            return $tag;
        }, $content );
    }

    public static function fix_attachment_attributes( $attr, $attachment, $size ) {
        $settings = self::get_settings();
        if ( ! $settings['enabled'] ) return $attr;

        $filename = basename( get_attached_file( $attachment->ID ) );
        $post_title = '';
        $parent_id = $attachment->post_parent;
        if ( $parent_id ) {
            $post_title = get_the_title( $parent_id );
        }

        if ( empty( $attr['alt'] ) || $settings['override_existing'] ) {
            $attr['alt'] = self::apply_format( $settings['alt_format'], $filename, $post_title );
        }

        if ( empty( $attr['title'] ) && ! empty( $settings['title_format'] ) ) {
            $attr['title'] = self::apply_format( $settings['title_format'], $filename, $post_title );
        }

        return $attr;
    }

    public static function render_settings() {
        $s = self::get_settings();
        ?>
        <div class="almaseo-settings-section">
            <h2><?php esc_html_e( 'Image SEO', 'almaseo-seo-playground' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Automatically add alt text and title attributes to images that are missing them.', 'almaseo-seo-playground' ); ?></p>
            <?php if ( function_exists('seo_playground_is_alma_connected') && seo_playground_is_alma_connected() ) : ?>
            <p class="description" style="margin-top: 4px; padding: 6px 10px; background: linear-gradient(135deg, #f0f4ff, #f8f9ff); border-left: 3px solid #667eea; border-radius: 3px;">
                <strong style="background: linear-gradient(135deg, #667eea, #764ba2); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">AI Enhanced</strong> —
                <?php esc_html_e( 'Your dashboard connection provides AI-generated alt text that understands image context.', 'almaseo-seo-playground' ); ?>
            </p>
            <?php endif; ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Enable Image SEO', 'almaseo-seo-playground' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="almaseo_image_seo_settings[enabled]" value="1" <?php checked( $s['enabled'] ); ?> />
                            <?php esc_html_e( 'Automatically add missing alt and title attributes', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="img_alt_format"><?php esc_html_e( 'Alt Text Format', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <input type="text" id="img_alt_format" name="almaseo_image_seo_settings[alt_format]"
                               value="<?php echo esc_attr( $s['alt_format'] ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Tags: %%filename%%, %%sitename%%, %%post_title%%', 'almaseo-seo-playground' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="img_title_format"><?php esc_html_e( 'Title Format', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <input type="text" id="img_title_format" name="almaseo_image_seo_settings[title_format]"
                               value="<?php echo esc_attr( $s['title_format'] ); ?>" class="regular-text" />
                        <p class="description"><?php esc_html_e( 'Leave empty to skip title attribute. Tags: %%filename%%, %%sitename%%, %%post_title%%', 'almaseo-seo-playground' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Strip File Extension', 'almaseo-seo-playground' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="almaseo_image_seo_settings[strip_extension]" value="1" <?php checked( $s['strip_extension'] ); ?> />
                            <?php esc_html_e( 'Remove file extension from filename when generating text', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Override Existing', 'almaseo-seo-playground' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="almaseo_image_seo_settings[override_existing]" value="1" <?php checked( $s['override_existing'] ); ?> />
                            <?php esc_html_e( 'Replace existing alt text (not recommended)', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }

    public static function sanitize( $input ) {
        return array(
            'enabled'           => ! empty( $input['enabled'] ),
            'alt_format'        => sanitize_text_field( $input['alt_format'] ?? '%%filename%%' ),
            'title_format'      => sanitize_text_field( $input['title_format'] ?? '' ),
            'strip_extension'   => ! empty( $input['strip_extension'] ),
            'override_existing' => ! empty( $input['override_existing'] ),
        );
    }
}
