<?php
/**
 * AlmaSEO Tag Manager
 *
 * Lets admins inject arbitrary HTML/JS/CSS into the site's <head>,
 * right after <body>, and just before </body>. Intended for analytics
 * snippets, pixel tags, verification scripts, and similar third-party code.
 *
 * Standalone admin page under "SEO Playground → Tag Manager".
 *
 * @package AlmaSEO
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Tag_Manager {

    const OPTION_KEY    = 'almaseo_tag_manager';
    const OPTION_GROUP  = 'almaseo_tag_manager_settings';
    const SLUG          = 'almaseo-tag-manager';

    public static function init() {
        add_action( 'admin_menu',  array( __CLASS__, 'register_menu' ), 50 );
        add_action( 'admin_init',  array( __CLASS__, 'register_setting' ) );

        add_action( 'wp_head',      array( __CLASS__, 'output_head' ),      100 );
        add_action( 'wp_body_open', array( __CLASS__, 'output_body_open' ),   1 );
        add_action( 'wp_footer',    array( __CLASS__, 'output_footer' ),    100 );
    }

    public static function get_defaults() {
        return array(
            'enable_head'        => true,
            'enable_body_open'   => true,
            'enable_footer'      => true,
            'disable_for_admins' => false,
            'head_code'          => '',
            'body_open_code'     => '',
            'footer_code'        => '',
        );
    }

    public static function get_settings() {
        $stored = get_option( self::OPTION_KEY, array() );
        if ( ! is_array( $stored ) ) {
            $stored = array();
        }
        return wp_parse_args( $stored, self::get_defaults() );
    }

    /* ------------------------------------------------------------------ */
    /*  Admin menu + setting registration                                  */
    /* ------------------------------------------------------------------ */

    public static function register_menu() {
        add_submenu_page(
            'seo-playground',
            __( 'Tag Manager - AlmaSEO', 'almaseo-seo-playground' ),
            __( 'Tag Manager', 'almaseo-seo-playground' ),
            'manage_options',
            self::SLUG,
            array( __CLASS__, 'render_admin_page' )
        );
    }

    public static function register_setting() {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_KEY,
            array(
                'type'              => 'array',
                'default'           => self::get_defaults(),
                'sanitize_callback' => array( __CLASS__, 'sanitize' ),
            )
        );
    }

    /* ------------------------------------------------------------------ */
    /*  Frontend output                                                    */
    /* ------------------------------------------------------------------ */

    private static function should_skip() {
        if ( is_admin() ) {
            return true;
        }
        if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
            return true;
        }
        if ( defined( 'REST_REQUEST' ) && REST_REQUEST ) {
            return true;
        }
        if ( function_exists( 'wp_is_json_request' ) && wp_is_json_request() ) {
            return true;
        }

        $s = self::get_settings();
        if ( ! empty( $s['disable_for_admins'] ) && is_user_logged_in() && current_user_can( 'manage_options' ) ) {
            return true;
        }

        return false;
    }

    public static function output_head() {
        if ( self::should_skip() ) {
            return;
        }
        $s = self::get_settings();
        if ( empty( $s['enable_head'] ) || '' === trim( (string) $s['head_code'] ) ) {
            return;
        }
        echo "\n<!-- AlmaSEO Tag Manager: head (start) -->\n";
        echo $s['head_code']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw code by design
        echo "\n<!-- AlmaSEO Tag Manager: head (end) -->\n";
    }

    public static function output_body_open() {
        if ( self::should_skip() ) {
            return;
        }
        $s = self::get_settings();
        if ( empty( $s['enable_body_open'] ) || '' === trim( (string) $s['body_open_code'] ) ) {
            return;
        }
        echo "\n<!-- AlmaSEO Tag Manager: body open (start) -->\n";
        echo $s['body_open_code']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw code by design
        echo "\n<!-- AlmaSEO Tag Manager: body open (end) -->\n";
    }

    public static function output_footer() {
        if ( self::should_skip() ) {
            return;
        }
        $s = self::get_settings();
        if ( empty( $s['enable_footer'] ) || '' === trim( (string) $s['footer_code'] ) ) {
            return;
        }
        echo "\n<!-- AlmaSEO Tag Manager: footer (start) -->\n";
        echo $s['footer_code']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- raw code by design
        echo "\n<!-- AlmaSEO Tag Manager: footer (end) -->\n";
    }

    /**
     * Sanitize Tag Manager settings.
     *
     * We intentionally do NOT strip tags from the code fields — the whole point
     * is to allow <script>/<style>/<iframe> snippets. Save is gated on
     * manage_options; wp_unslash neutralizes WordPress magic-quote escaping.
     */
    public static function sanitize( $input ) {
        $defaults = self::get_defaults();

        if ( ! current_user_can( 'manage_options' ) ) {
            return get_option( self::OPTION_KEY, $defaults );
        }

        if ( ! is_array( $input ) ) {
            return $defaults;
        }

        $clean = array();
        $clean['enable_head']        = ! empty( $input['enable_head'] );
        $clean['enable_body_open']   = ! empty( $input['enable_body_open'] );
        $clean['enable_footer']      = ! empty( $input['enable_footer'] );
        $clean['disable_for_admins'] = ! empty( $input['disable_for_admins'] );

        $clean['head_code']      = isset( $input['head_code'] )      ? trim( wp_unslash( (string) $input['head_code'] ) )      : '';
        $clean['body_open_code'] = isset( $input['body_open_code'] ) ? trim( wp_unslash( (string) $input['body_open_code'] ) ) : '';
        $clean['footer_code']    = isset( $input['footer_code'] )    ? trim( wp_unslash( (string) $input['footer_code'] ) )    : '';

        return $clean;
    }

    /* ------------------------------------------------------------------ */
    /*  Render the standalone admin page                                   */
    /* ------------------------------------------------------------------ */

    public static function render_admin_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'almaseo-seo-playground' ) );
        }

        $s = self::get_settings();

        // This is a custom admin page (admin.php?page=), so WordPress' automatic
        // "Settings saved." notice — which only renders on real options-*.php
        // screens — never appears. Queue and render it ourselves so saving the
        // form gives visible confirmation.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only flag set by options.php redirect
        if ( ! empty( $_GET['settings-updated'] ) ) {
            add_settings_error(
                self::OPTION_GROUP,
                'almaseo_tag_manager_saved',
                __( 'Tag Manager settings saved.', 'almaseo-seo-playground' ),
                'updated'
            );
        }
        ?>
        <div class="wrap almaseo-tag-manager-wrap">
            <h1><?php esc_html_e( 'Tag Manager', 'almaseo-seo-playground' ); ?></h1>
            <?php settings_errors( self::OPTION_GROUP ); ?>

            <p class="description" style="max-width:780px;">
                <?php esc_html_e( 'Inject custom code into your site\'s frontend. Paste full <script>, <style>, <noscript>, or <meta> tags — anything you enter here is output verbatim. Useful for analytics, pixels, verification scripts, chat widgets, and other third-party snippets.', 'almaseo-seo-playground' ); ?>
            </p>

            <form method="post" action="options.php">
                <?php settings_fields( self::OPTION_GROUP ); ?>

                <table class="form-table">
                    <tr>
                        <th scope="row"><?php esc_html_e( 'Header Code', 'almaseo-seo-playground' ); ?></th>
                        <td>
                            <label style="display:block; margin-bottom:6px;">
                                <input type="checkbox" name="almaseo_tag_manager[enable_head]" value="1" <?php checked( ! empty( $s['enable_head'] ) ); ?> />
                                <?php esc_html_e( 'Output in <head>', 'almaseo-seo-playground' ); ?>
                                <span style="color:#646970; font-style:italic;"><?php esc_html_e( '— uncheck to disable this field (your code stays saved)', 'almaseo-seo-playground' ); ?></span>
                            </label>
                            <textarea name="almaseo_tag_manager[head_code]" rows="10" class="large-text code" spellcheck="false" placeholder="<?php esc_attr_e( '<!-- e.g. Google Tag Manager, analytics scripts, verification meta tags -->', 'almaseo-seo-playground' ); ?>"><?php echo esc_textarea( $s['head_code'] ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'Injected inside the <head> tag on every frontend page (priority 100).', 'almaseo-seo-playground' ); ?></p>
                            <p class="description" style="margin-top:6px; padding:8px 12px; background:#fcf9e8; border-left:4px solid #dba617;">
                                <strong><?php esc_html_e( 'Adding a Google Analytics tag?', 'almaseo-seo-playground' ); ?></strong>
                                <?php
                                printf(
                                    /* translators: %s: link to the AlmaSEO Settings page */
                                    esc_html__( 'You don\'t need to paste it here. Use the dedicated Google Analytics section on the %s instead — just enter your GA4 Measurement ID and the snippet is handled for you.', 'almaseo-seo-playground' ),
                                    '<a href="' . esc_url( admin_url( 'admin.php?page=almaseo-settings' ) ) . '">' . esc_html__( 'Settings page', 'almaseo-seo-playground' ) . '</a>'
                                );
                                ?>
                            </p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Body Open Code', 'almaseo-seo-playground' ); ?></th>
                        <td>
                            <label style="display:block; margin-bottom:6px;">
                                <input type="checkbox" name="almaseo_tag_manager[enable_body_open]" value="1" <?php checked( ! empty( $s['enable_body_open'] ) ); ?> />
                                <?php esc_html_e( 'Output immediately after <body>', 'almaseo-seo-playground' ); ?>
                                <span style="color:#646970; font-style:italic;"><?php esc_html_e( '— uncheck to disable this field (your code stays saved)', 'almaseo-seo-playground' ); ?></span>
                            </label>
                            <textarea name="almaseo_tag_manager[body_open_code]" rows="10" class="large-text code" spellcheck="false" placeholder="<?php esc_attr_e( '<!-- e.g. GTM <noscript> fallback iframe -->', 'almaseo-seo-playground' ); ?>"><?php echo esc_textarea( $s['body_open_code'] ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'Injected at the top of <body>. Requires your theme to call wp_body_open() — most modern themes do.', 'almaseo-seo-playground' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Footer Code', 'almaseo-seo-playground' ); ?></th>
                        <td>
                            <label style="display:block; margin-bottom:6px;">
                                <input type="checkbox" name="almaseo_tag_manager[enable_footer]" value="1" <?php checked( ! empty( $s['enable_footer'] ) ); ?> />
                                <?php esc_html_e( 'Output before </body>', 'almaseo-seo-playground' ); ?>
                                <span style="color:#646970; font-style:italic;"><?php esc_html_e( '— uncheck to disable this field (your code stays saved)', 'almaseo-seo-playground' ); ?></span>
                            </label>
                            <textarea name="almaseo_tag_manager[footer_code]" rows="10" class="large-text code" spellcheck="false" placeholder="<?php esc_attr_e( '<!-- e.g. chat widget, deferred analytics -->', 'almaseo-seo-playground' ); ?>"><?php echo esc_textarea( $s['footer_code'] ); ?></textarea>
                            <p class="description"><?php esc_html_e( 'Injected just before the closing </body> tag (priority 100).', 'almaseo-seo-playground' ); ?></p>
                        </td>
                    </tr>

                    <tr>
                        <th scope="row"><?php esc_html_e( 'Logged-in Admins', 'almaseo-seo-playground' ); ?></th>
                        <td>
                            <label>
                                <input type="checkbox" name="almaseo_tag_manager[disable_for_admins]" value="1" <?php checked( ! empty( $s['disable_for_admins'] ) ); ?> />
                                <?php esc_html_e( 'Skip tag output for logged-in administrators (useful to keep analytics clean during testing).', 'almaseo-seo-playground' ); ?>
                            </label>
                        </td>
                    </tr>
                </table>

                <?php submit_button( __( 'Save Tag Manager Settings', 'almaseo-seo-playground' ) ); ?>
            </form>

            <div class="almaseo-tag-manager-notes" style="margin-top:24px; padding:16px 20px; background:#f6f7f7; border-left:4px solid #2271b1; max-width:780px;">
                <p style="margin:0 0 8px 0;"><strong><?php esc_html_e( 'Tips', 'almaseo-seo-playground' ); ?></strong></p>
                <ul style="margin:0 0 0 20px; padding:0;">
                    <li><?php esc_html_e( 'Each injected block is wrapped in HTML comments (e.g. "AlmaSEO Tag Manager: head") so you can find it in view-source.', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Code is never output on admin, REST, AJAX, or JSON requests — only on actual frontend page loads.', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Code is output verbatim with no escaping, by design. Only administrators can edit this page.', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>
        </div>
        <?php
    }
}
