<?php
/**
 * AlmaSEO Google Analytics — Settings
 *
 * Registers the option, renders the settings section, and provides sanitization.
 *
 * @package AlmaSEO
 * @since   8.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Analytics_Settings {

    const OPTION_KEY = 'almaseo_analytics_settings';

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'almaseo_settings_sections', array( __CLASS__, 'render_settings' ) );
    }

    /**
     * Default settings.
     */
    public static function get_defaults() {
        return array(
            'measurement_id'    => '',
            'exclude_logged_in' => true,
            'anonymize_ip'      => false,
            'track_link_clicks' => false,
        );
    }

    /**
     * Get current settings merged with defaults.
     */
    public static function get_settings() {
        return wp_parse_args( get_option( self::OPTION_KEY, array() ), self::get_defaults() );
    }

    /**
     * Sanitize callback.
     */
    public static function sanitize( $input ) {
        return array(
            'measurement_id'    => sanitize_text_field( $input['measurement_id'] ?? '' ),
            'exclude_logged_in' => ! empty( $input['exclude_logged_in'] ),
            'anonymize_ip'      => ! empty( $input['anonymize_ip'] ),
            'track_link_clicks' => ! empty( $input['track_link_clicks'] ),
        );
    }

    /**
     * Render settings section on the AlmaSEO Settings page.
     */
    public static function render_settings() {
        $s = self::get_settings();
        ?>
        <div class="almaseo-settings-section">
            <h2><?php _e( 'Google Analytics', 'almaseo-seo-playground' ); ?></h2>
            <p class="description"><?php _e( 'Add your GA4 Measurement ID to insert the Google Analytics tracking snippet on your site.', 'almaseo-seo-playground' ); ?></p>
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="almaseo_ga_measurement_id"><?php _e( 'Measurement ID', 'almaseo-seo-playground' ); ?></label>
                    </th>
                    <td>
                        <input type="text"
                               id="almaseo_ga_measurement_id"
                               name="almaseo_analytics_settings[measurement_id]"
                               value="<?php echo esc_attr( $s['measurement_id'] ); ?>"
                               class="regular-text"
                               placeholder="G-XXXXXXXXXX" />
                        <p class="description"><?php _e( 'Your GA4 Measurement ID (starts with G-). Found in Google Analytics → Admin → Data Streams.', 'almaseo-seo-playground' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Exclude Logged-in Users', 'almaseo-seo-playground' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="almaseo_analytics_settings[exclude_logged_in]"
                                   value="1"
                                   <?php checked( $s['exclude_logged_in'] ); ?> />
                            <?php _e( 'Do not track logged-in administrators', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Anonymize IP', 'almaseo-seo-playground' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="almaseo_analytics_settings[anonymize_ip]"
                                   value="1"
                                   <?php checked( $s['anonymize_ip'] ); ?> />
                            <?php _e( 'Enable IP anonymization for GDPR compliance', 'almaseo-seo-playground' ); ?>
                        </label>
                        <p class="description"><?php _e( 'Note: GA4 anonymizes IP addresses by default in most regions.', 'almaseo-seo-playground' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php _e( 'Track Outbound Link Clicks', 'almaseo-seo-playground' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox"
                                   name="almaseo_analytics_settings[track_link_clicks]"
                                   value="1"
                                   <?php checked( $s['track_link_clicks'] ); ?> />
                            <?php _e( 'Send an event when visitors click external links', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        <?php
    }
}
