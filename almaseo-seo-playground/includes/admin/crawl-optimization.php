<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class AlmaSEO_Crawl_Optimization {

    const OPTION_KEY = 'almaseo_crawl_optimization';

    public static function init() {
        add_action( 'almaseo_settings_sections', array( __CLASS__, 'render_settings_section' ) );
        add_action( 'init', array( __CLASS__, 'apply_cleanups' ), 1 );
        add_action( 'template_redirect', array( __CLASS__, 'maybe_disable_feeds' ) );
    }

    public static function get_defaults() {
        return array(
            'remove_emoji'         => false,
            'remove_rsd'           => false,
            'remove_wlw'           => false,
            'remove_generator'     => false,
            'remove_shortlinks'    => false,
            'remove_rest_api'      => false,
            'remove_oembed'        => false,
            'disable_rss_global'   => false,
            'disable_rss_comments' => false,
            'disable_rss_author'   => false,
            'disable_rss_search'   => false,
            'disable_rss_tag'      => false,
            'disable_rss_category' => false,
        );
    }

    public static function get_settings() {
        return wp_parse_args( get_option( self::OPTION_KEY, array() ), self::get_defaults() );
    }

    public static function apply_cleanups() {
        if ( is_admin() ) return;
        $s = self::get_settings();

        if ( ! empty( $s['remove_emoji'] ) ) {
            remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
            remove_action( 'wp_print_styles', 'print_emoji_styles' );
            remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
            remove_action( 'admin_print_styles', 'print_emoji_styles' );
            remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
            remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
            remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );
            add_filter( 'tiny_mce_plugins', function( $plugins ) {
                return is_array( $plugins ) ? array_diff( $plugins, array( 'wpemoji' ) ) : $plugins;
            });
            add_filter( 'wp_resource_hints', function( $urls, $relation_type ) {
                if ( 'dns-prefetch' === $relation_type ) {
                    $urls = array_filter( $urls, function( $url ) {
                        return strpos( $url, 'https://s.w.org/images/core/emoji/' ) === false;
                    });
                }
                return $urls;
            }, 10, 2 );
        }
        if ( ! empty( $s['remove_rsd'] ) ) {
            remove_action( 'wp_head', 'rsd_link' );
        }
        if ( ! empty( $s['remove_wlw'] ) ) {
            remove_action( 'wp_head', 'wlw_manifest_link' );
        }
        if ( ! empty( $s['remove_generator'] ) ) {
            remove_action( 'wp_head', 'wp_generator' );
            add_filter( 'the_generator', '__return_empty_string' );
        }
        if ( ! empty( $s['remove_shortlinks'] ) ) {
            remove_action( 'wp_head', 'wp_shortlink_wp_head', 10 );
            remove_action( 'template_redirect', 'wp_shortlink_header', 11 );
        }
        if ( ! empty( $s['remove_rest_api'] ) ) {
            remove_action( 'wp_head', 'rest_output_link_wp_head', 10 );
            remove_action( 'template_redirect', 'rest_output_link_header', 11 );
        }
        if ( ! empty( $s['remove_oembed'] ) ) {
            remove_action( 'wp_head', 'wp_oembed_add_discovery_links' );
            remove_action( 'wp_head', 'wp_oembed_add_host_js' );
        }
    }

    public static function maybe_disable_feeds() {
        if ( ! is_feed() ) return;
        $s = self::get_settings();

        $should_disable = false;
        if ( ! empty( $s['disable_rss_global'] ) ) {
            $should_disable = true;
        } elseif ( ! empty( $s['disable_rss_comments'] ) && is_comment_feed() ) {
            $should_disable = true;
        } elseif ( ! empty( $s['disable_rss_author'] ) && is_author() ) {
            $should_disable = true;
        } elseif ( ! empty( $s['disable_rss_search'] ) && is_search() ) {
            $should_disable = true;
        } elseif ( ! empty( $s['disable_rss_tag'] ) && is_tag() ) {
            $should_disable = true;
        } elseif ( ! empty( $s['disable_rss_category'] ) && is_category() ) {
            $should_disable = true;
        }

        if ( $should_disable ) {
            wp_die(
                esc_html__( 'This feed has been disabled.', 'almaseo-seo-playground' ),
                esc_html__( 'Feed Disabled', 'almaseo-seo-playground' ),
                array( 'response' => 404 )
            );
        }
    }

    public static function render_settings_section() {
        $s = self::get_settings();
        ?>
        <div class="almaseo-settings-section">
            <h2><?php esc_html_e( 'Crawl Optimization', 'almaseo-seo-playground' ); ?></h2>
            <p class="description"><?php esc_html_e( 'Remove unnecessary metadata from your site to improve crawl efficiency and reduce page size.', 'almaseo-seo-playground' ); ?></p>

            <h3 style="margin-top:20px;"><?php esc_html_e( 'Head Cleanup', 'almaseo-seo-playground' ); ?></h3>
            <table class="form-table">
                <?php
                $head_toggles = array(
                    'remove_emoji'      => __( 'Remove Emoji Scripts', 'almaseo-seo-playground' ),
                    'remove_rsd'        => __( 'Remove RSD Link', 'almaseo-seo-playground' ),
                    'remove_wlw'        => __( 'Remove WLW Manifest Link', 'almaseo-seo-playground' ),
                    'remove_generator'  => __( 'Remove WordPress Generator Tag', 'almaseo-seo-playground' ),
                    'remove_shortlinks' => __( 'Remove Shortlinks', 'almaseo-seo-playground' ),
                    'remove_rest_api'   => __( 'Remove REST API Discovery Links', 'almaseo-seo-playground' ),
                    'remove_oembed'     => __( 'Remove oEmbed Discovery Links', 'almaseo-seo-playground' ),
                );
                foreach ( $head_toggles as $key => $label ) : ?>
                <tr>
                    <th scope="row"><?php echo esc_html( $label ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="almaseo_crawl_optimization[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $s[ $key ] ) ); ?> />
                            <?php esc_html_e( 'Enable', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>

            <h3 style="margin-top:20px;"><?php esc_html_e( 'Feed Controls', 'almaseo-seo-playground' ); ?></h3>
            <table class="form-table">
                <?php
                $feed_toggles = array(
                    'disable_rss_global'   => __( 'Disable Global RSS Feed', 'almaseo-seo-playground' ),
                    'disable_rss_comments' => __( 'Disable Comments Feed', 'almaseo-seo-playground' ),
                    'disable_rss_author'   => __( 'Disable Author Feeds', 'almaseo-seo-playground' ),
                    'disable_rss_search'   => __( 'Disable Search Feeds', 'almaseo-seo-playground' ),
                    'disable_rss_tag'      => __( 'Disable Tag Feeds', 'almaseo-seo-playground' ),
                    'disable_rss_category' => __( 'Disable Category Feeds', 'almaseo-seo-playground' ),
                );
                foreach ( $feed_toggles as $key => $label ) : ?>
                <tr>
                    <th scope="row"><?php echo esc_html( $label ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" name="almaseo_crawl_optimization[<?php echo esc_attr( $key ); ?>]" value="1" <?php checked( ! empty( $s[ $key ] ) ); ?> />
                            <?php esc_html_e( 'Enable', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        <?php
    }

    public static function sanitize( $input ) {
        $clean = array();
        $defaults = self::get_defaults();
        foreach ( array_keys( $defaults ) as $key ) {
            $clean[ $key ] = ! empty( $input[ $key ] );
        }
        return $clean;
    }
}
