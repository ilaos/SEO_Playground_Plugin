<?php
/**
 * AlmaSEO Site Profile Cache
 *
 * Pulls the connected site's business profile from the AlmaSEO dashboard
 * and caches it locally so meta-generation (both AI and local fallback)
 * can produce profile-aware titles and descriptions even when the API
 * is unreachable.
 *
 * Mirrors the GSC pattern: plugin holds Basic Auth credentials, dashboard
 * does the privileged DB lookup and returns normalized JSON.
 *
 * @package AlmaSEO
 * @since 1.10.0
 */

namespace AlmaSEO\Connection;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Site_Profile {

	const OPTION_KEY     = 'almaseo_site_profile';
	const FETCHED_AT_KEY = 'almaseo_site_profile_fetched_at';
	const CRON_HOOK      = 'almaseo_site_profile_refresh';
	const REFRESH_TTL    = DAY_IN_SECONDS;
	const ENDPOINT       = 'https://api.almaseo.com/api/plugin/site-profile';

	public static function init() {
		add_action( 'init', array( __CLASS__, 'maybe_schedule_cron' ) );
		add_action( self::CRON_HOOK, array( __CLASS__, 'fetch' ) );
		add_action( 'wp_ajax_almaseo_refresh_site_profile', array( __CLASS__, 'ajax_refresh' ) );

		// Pull on first connection or when credentials change.
		add_action( 'update_option_almaseo_app_password', array( __CLASS__, 'fetch' ) );
		add_action( 'add_option_almaseo_app_password', array( __CLASS__, 'fetch' ) );
	}

	public static function maybe_schedule_cron() {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() + HOUR_IN_SECONDS, 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Get the cached profile. Triggers a synchronous refresh if stale and the
	 * site is connected, but only when $allow_fetch is true (callers in a
	 * latency-sensitive path should pass false).
	 *
	 * @param bool $allow_fetch Whether to fetch on-the-fly if cache is stale.
	 * @return array Profile data, or empty array if unavailable.
	 */
	public static function get( $allow_fetch = false ) {
		$profile = get_option( self::OPTION_KEY, array() );

		if ( ! is_array( $profile ) ) {
			$profile = array();
		}

		if ( $allow_fetch && self::is_connected() && self::should_refresh() ) {
			$fresh = self::fetch();
			if ( ! empty( $fresh ) ) {
				$profile = $fresh;
			}
		}

		return $profile;
	}

	/**
	 * Whether the cached profile has the high-priority fields the dashboard
	 * considers essential (about_us, business_name, services).
	 */
	public static function is_filled() {
		$p = self::get( false );
		if ( empty( $p ) || empty( $p['filled'] ) ) {
			return false;
		}
		$profile = isset( $p['profile'] ) && is_array( $p['profile'] ) ? $p['profile'] : array();
		return ! empty( $profile['business_name'] ) && (
			! empty( $profile['services'] ) || ! empty( $profile['about_us'] )
		);
	}

	public static function should_refresh() {
		$fetched = (int) get_option( self::FETCHED_AT_KEY, 0 );
		return ( time() - $fetched ) > self::REFRESH_TTL;
	}

	/**
	 * Lazy-fetch entry point. Used by the autofill flow so the cache fills
	 * on first use even when the password was already saved before this
	 * version landed (the update_option hook only fires on re-save).
	 *
	 * Rate-limited so a flurry of autofill clicks against an unreachable
	 * dashboard don't hammer the API.
	 *
	 * @return bool Whether the cache is now usable (filled) for generation.
	 */
	public static function ensure_fresh() {
		if ( ! self::is_connected() ) {
			return false;
		}
		if ( self::is_filled() && ! self::should_refresh() ) {
			return true;
		}
		$last_attempt = (int) get_option( 'almaseo_site_profile_last_attempt', 0 );
		if ( ( time() - $last_attempt ) < 30 ) {
			// Recently tried; don't pile on. Caller will use whatever's cached.
			return self::is_filled();
		}
		update_option( 'almaseo_site_profile_last_attempt', time(), false );
		self::fetch();
		return self::is_filled();
	}

	public static function is_connected() {
		$user = (string) get_option( 'almaseo_connected_user', '' );
		$pass = (string) get_option( 'almaseo_app_password', '' );
		return ! empty( $user ) && ! empty( $pass );
	}

	/**
	 * Fetch the profile from the dashboard and cache it.
	 *
	 * @return array|false Stored profile, or false on failure.
	 */
	public static function fetch() {
		if ( ! self::is_connected() ) {
			return false;
		}

		$user     = (string) get_option( 'almaseo_connected_user', '' );
		$pass     = (string) get_option( 'almaseo_app_password', '' );
		$site_url = get_site_url();

		$response = wp_remote_get(
			add_query_arg( array( 'site_url' => $site_url ), self::ENDPOINT ),
			array(
				'timeout' => 15,
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $user . ':' . $pass ),
					'Accept'        => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		$status = wp_remote_retrieve_response_code( $response );
		$body   = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( $status !== 200 || ! is_array( $body ) || empty( $body['success'] ) ) {
			return false;
		}

		$payload = array(
			'filled'                => ! empty( $body['filled'] ),
			'completeness'          => isset( $body['completeness'] ) ? (float) $body['completeness'] : 0.0,
			'missing_high_priority' => isset( $body['missing_high_priority'] ) && is_array( $body['missing_high_priority'] )
				? array_map( 'sanitize_text_field', $body['missing_high_priority'] )
				: array(),
			'profile'               => self::sanitize_profile( $body['profile'] ?? array() ),
		);

		update_option( self::OPTION_KEY, $payload, false );
		update_option( self::FETCHED_AT_KEY, time(), false );

		return $payload;
	}

	private static function sanitize_profile( $raw ) {
		if ( ! is_array( $raw ) ) {
			return array();
		}

		$scalar_fields = array(
			'business_name', 'business_owner', 'industry_type',
			'about_us', 'slogan', 'brand_voice',
			'phone', 'email', 'street_address',
			'city', 'state', 'zip_code',
			'google_maps_url', 'contact_page_url',
		);

		$out = array();
		foreach ( $scalar_fields as $field ) {
			$out[ $field ] = isset( $raw[ $field ] ) ? sanitize_text_field( (string) $raw[ $field ] ) : '';
		}

		// Address can contain nav-menu garbage from earlier scrapes; treat it as
		// scalar but trust nothing — generators should validate before using.
		$list_fields = array( 'services', 'service_areas', 'awards' );
		foreach ( $list_fields as $field ) {
			$value = isset( $raw[ $field ] ) ? $raw[ $field ] : array();
			if ( is_string( $value ) ) {
				$decoded = json_decode( $value, true );
				$value   = is_array( $decoded ) ? $decoded : array_filter( array_map( 'trim', explode( ',', $value ) ) );
			}
			if ( ! is_array( $value ) ) {
				$value = array();
			}
			$out[ $field ] = array_values( array_filter( array_map( 'sanitize_text_field', $value ) ) );
		}

		// Opening hours: { day => { open, close } } with 24-hour HH:MM strings.
		// Sourced from the connected Google Business Profile (regularHours) by
		// the dashboard's /api/plugin/site-profile endpoint.
		$hours_in  = isset( $raw['opening_hours'] ) && is_array( $raw['opening_hours'] ) ? $raw['opening_hours'] : array();
		$hours_out = array();
		foreach ( array( 'monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday' ) as $day ) {
			if ( empty( $hours_in[ $day ] ) || ! is_array( $hours_in[ $day ] ) ) {
				continue;
			}
			$open  = sanitize_text_field( (string) ( $hours_in[ $day ]['open'] ?? '' ) );
			$close = sanitize_text_field( (string) ( $hours_in[ $day ]['close'] ?? '' ) );
			if ( preg_match( '/^([01]?\d|2[0-3]):[0-5]\d$/', $open ) && preg_match( '/^([01]?\d|2[0-3]):[0-5]\d$/', $close ) ) {
				$hours_out[ $day ] = array( 'open' => $open, 'close' => $close );
			}
		}
		$out['opening_hours'] = $hours_out;

		return $out;
	}

	public static function clear() {
		delete_option( self::OPTION_KEY );
		delete_option( self::FETCHED_AT_KEY );
	}

	public static function ajax_refresh() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => 'Insufficient permissions.' ), 403 );
		}
		check_ajax_referer( 'almaseo_refresh_profile', 'nonce' );

		$result = self::fetch();
		if ( ! $result ) {
			wp_send_json_error( array(
				'message' => self::is_connected()
					? __( 'Could not reach the AlmaSEO dashboard. Try again in a moment.', 'almaseo-seo-playground' )
					: __( 'This site is not connected to the AlmaSEO dashboard.', 'almaseo-seo-playground' ),
			) );
		}

		wp_send_json_success( array(
			'profile'      => $result,
			'fetched_at'   => (int) get_option( self::FETCHED_AT_KEY, 0 ),
			'is_filled'    => self::is_filled(),
		) );
	}

	/**
	 * Convenience accessor — returns the bare profile object (the data you
	 * use to template a title/description). Empty array if not cached.
	 */
	public static function profile_data() {
		$p = self::get( false );
		if ( empty( $p ) || empty( $p['profile'] ) || ! is_array( $p['profile'] ) ) {
			return array();
		}
		return $p['profile'];
	}
}

Site_Profile::init();

// Clear schedule on deactivation. Registered against the main plugin file by
// the loader in almaseo-seo-playground.php (we avoid registering here to keep
// activation/deactivation hooks centralized).
