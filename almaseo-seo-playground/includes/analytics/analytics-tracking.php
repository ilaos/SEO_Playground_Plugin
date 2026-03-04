<?php
/**
 * AlmaSEO Google Analytics — Frontend Tracking
 *
 * Outputs GA4 gtag.js snippet in wp_head when a valid Measurement ID is configured.
 *
 * @package AlmaSEO
 * @since   8.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Analytics_Tracking {

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'wp_head', array( __CLASS__, 'output_tracking_code' ), 1 );
    }

    /**
     * Output the gtag.js snippet if a measurement ID is set and conditions are met.
     */
    public static function output_tracking_code() {
        // Don't output in admin
        if ( is_admin() ) {
            return;
        }

        $settings = AlmaSEO_Analytics_Settings::get_settings();
        $mid      = trim( $settings['measurement_id'] );

        // Must have a valid-looking Measurement ID
        if ( empty( $mid ) || ! preg_match( '/^G-[A-Za-z0-9]+$/', $mid ) ) {
            return;
        }

        // Optionally exclude logged-in administrators
        if ( $settings['exclude_logged_in'] && is_user_logged_in() && current_user_can( 'manage_options' ) ) {
            return;
        }

        $config_params = array();

        if ( $settings['anonymize_ip'] ) {
            $config_params['anonymize_ip'] = true;
        }

        $config_json = ! empty( $config_params )
            ? wp_json_encode( $config_params )
            : '{}';

        ?>
<!-- AlmaSEO Google Analytics (GA4) -->
<script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo esc_attr( $mid ); ?>"></script>
<script>
window.dataLayer = window.dataLayer || [];
function gtag(){dataLayer.push(arguments);}
gtag('js', new Date());
gtag('config', '<?php echo esc_js( $mid ); ?>'<?php echo $config_json !== '{}' ? ', ' . $config_json : ''; ?>);
<?php if ( $settings['track_link_clicks'] ) : ?>
document.addEventListener('click', function(e) {
    var link = e.target.closest('a');
    if (!link) return;
    var href = link.getAttribute('href');
    if (!href) return;
    try {
        var url = new URL(href, window.location.origin);
        if (url.hostname !== window.location.hostname) {
            gtag('event', 'click', {
                event_category: 'outbound',
                event_label: href,
                transport_type: 'beacon'
            });
        }
    } catch(err) {}
});
<?php endif; ?>
</script>
<!-- /AlmaSEO Google Analytics -->
        <?php
    }
}
