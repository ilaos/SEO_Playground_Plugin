<?php
/**
 * AlmaSEO Connection Handoff
 *
 * Implements Scenario-4 deep-link handoff for users who already have an
 * AlmaSEO account but installed the plugin manually (not via the dashboard
 * "Add Site" flow).
 *
 * Flow:
 *   1. Wizard "Connect" click → POST /almaseo/v1/connection/handoff
 *      Plugin generates an Application Password (or JWT fallback if hosting
 *      blocks it), stores credentials in a transient keyed by an opaque
 *      single-use token (5-minute TTL), returns a redirect URL pointing at
 *      the AlmaSEO dashboard's /connect page with the token in the query.
 *
 *   2. Wizard JS opens the redirect URL in a new tab.
 *
 *   3. Dashboard's /connect page reads the token, calls back to
 *      POST /almaseo/v1/connection/finalize with `{ token: "..." }`. This
 *      endpoint is intentionally public (no nonce / no cap check) because
 *      the dashboard has no credentials yet — bearer-style auth via the
 *      single-use token is the whole point. The transient is deleted on
 *      first read so the token cannot be replayed.
 *
 *   4. Dashboard now has the App Password / JWT and starts making
 *      authenticated calls back to the plugin's existing REST API. The
 *      first such call trips almaseo_sync_from_dashboard() and flips
 *      almaseo_dashboard_synced=true (existing behavior, unchanged).
 *
 *   5. The wizard polls GET /almaseo/v1/connection/status every 3s; once
 *      `connected: true` is observed, the wizard advances.
 *
 * This module is purely additive — it does not alter the existing
 * connection-settings.php page, the activation auto-connect logic, or any
 * existing REST endpoint. The legacy copy-paste flow continues to work and
 * remains the fallback when the popup is blocked.
 *
 * @package AlmaSEO
 * @since   1.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ------------------------------------------------------------------
 *  Constants
 * ----------------------------------------------------------------*/

if ( ! defined( 'ALMASEO_HANDOFF_TTL' ) ) {
	define( 'ALMASEO_HANDOFF_TTL', 5 * MINUTE_IN_SECONDS );
}

if ( ! defined( 'ALMASEO_HANDOFF_TRANSIENT_PREFIX' ) ) {
	define( 'ALMASEO_HANDOFF_TRANSIENT_PREFIX', 'almaseo_handoff_' );
}

/**
 * Option that holds the unix-time when the wizard issued a handoff. Used
 * by /connection/status to defer "connected: true" until the dashboard
 * actually finalizes — without this gate, the status endpoint would flip
 * to connected the moment the App Password is stored locally (which happens
 * inside /handoff, before the dashboard has done anything).
 */
if ( ! defined( 'ALMASEO_HANDOFF_ACTIVE_OPT' ) ) {
	define( 'ALMASEO_HANDOFF_ACTIVE_OPT', 'almaseo_handoff_active' );
}

/* ------------------------------------------------------------------
 *  Token helpers
 * ----------------------------------------------------------------*/

if ( ! function_exists( 'almaseo_handoff_issue_token' ) ) {
	/**
	 * Issue an opaque single-use handoff token and store its credential
	 * payload in a transient.
	 *
	 * @param array $payload Must contain at least site_url, username, method,
	 *                       and either app_password or jwt.
	 * @return string The token (40 alphanumeric chars).
	 */
	function almaseo_handoff_issue_token( $payload ) {
		$token = wp_generate_password( 40, false, false );
		set_transient( ALMASEO_HANDOFF_TRANSIENT_PREFIX . $token, $payload, ALMASEO_HANDOFF_TTL );
		return $token;
	}
}

if ( ! function_exists( 'almaseo_handoff_consume_token' ) ) {
	/**
	 * Read and delete a handoff token's payload (single-use semantics).
	 *
	 * @param string $token Token from the dashboard.
	 * @return array|WP_Error Payload on success, WP_Error if missing/expired.
	 */
	function almaseo_handoff_consume_token( $token ) {
		$token = sanitize_text_field( (string) $token );
		if ( '' === $token || strlen( $token ) > 64 ) {
			return new WP_Error( 'invalid_token', 'Invalid handoff token.', array( 'status' => 400 ) );
		}

		$key     = ALMASEO_HANDOFF_TRANSIENT_PREFIX . $token;
		$payload = get_transient( $key );

		if ( false === $payload ) {
			return new WP_Error( 'token_not_found', 'Handoff token expired or already used.', array( 'status' => 404 ) );
		}

		// Single-use: delete immediately on read.
		delete_transient( $key );

		return is_array( $payload ) ? $payload : array();
	}
}

/* ------------------------------------------------------------------
 *  Credential generation
 *
 *  Mirrors the logic in admin/pages/connection-settings.php but does not
 *  emit any UI — it returns either an App Password or a JWT depending on
 *  whether the host allows Authorization headers.
 * ----------------------------------------------------------------*/

if ( ! function_exists( 'almaseo_handoff_prepare_credentials' ) ) {
	/**
	 * Generate (or reuse) credentials for the dashboard handoff.
	 *
	 * @param WP_User $user Current admin user.
	 * @return array { method: 'app_password'|'jwt', password?: string, jwt?: string }
	 */
	function almaseo_handoff_prepare_credentials( $user ) {
		$username = $user->user_login;

		// 1. If a working App Password is already stored, reuse it.
		$existing_password = get_option( 'almaseo_app_password', '' );
		if ( $existing_password && almaseo_handoff_password_works( $username, $existing_password ) ) {
			return array(
				'method'   => 'app_password',
				'password' => $existing_password,
			);
		}

		// 2. Try to generate a fresh App Password.
		if ( function_exists( 'wp_is_application_passwords_available' )
			&& wp_is_application_passwords_available()
			&& function_exists( 'wp_generate_application_password' ) ) {

			$label  = 'AlmaSEO Connection ' . wp_date( 'Y-m-d H:i:s' );
			$result = wp_generate_application_password( $user->ID, array(
				'name'   => $label,
				'app_id' => 'almaseo-seo-playground',
			) );

			if ( ! is_wp_error( $result ) && is_array( $result ) && ! empty( $result[0] ) ) {
				$new_password = $result[0];

				// Verify the password actually works via the REST API — some
				// hosts allow creation but block the Authorization header.
				if ( almaseo_handoff_password_works( $username, $new_password ) ) {
					update_option( 'almaseo_app_password', $new_password );
					update_option( 'almaseo_connected_user', $username );
					update_option( 'almaseo_connected_date', current_time( 'mysql' ) );

					return array(
						'method'   => 'app_password',
						'password' => $new_password,
					);
				}

				// Doesn't work — clean up the orphan password.
				if ( class_exists( 'WP_Application_Passwords' ) ) {
					$list = WP_Application_Passwords::get_user_application_passwords( $user->ID );
					foreach ( $list as $ap ) {
						if ( ( $ap['name'] ?? '' ) === $label ) {
							WP_Application_Passwords::delete_application_password( $user->ID, $ap['uuid'] );
							break;
						}
					}
				}
			}
		}

		// 3. Fall back to JWT (long-lived token signed with almaseo_jwt_secret).
		if ( function_exists( 'almaseo_create_jwt' ) ) {
			return array(
				'method' => 'jwt',
				'jwt'    => almaseo_create_jwt( $username ),
			);
		}

		return new WP_Error(
			'no_credentials',
			'Could not generate any connection credential. Update WordPress to 5.6+ and try again.',
			array( 'status' => 500 )
		);
	}
}

if ( ! function_exists( 'almaseo_handoff_password_works' ) ) {
	/**
	 * Verify an App Password by calling /wp/v2/users/me with Basic auth.
	 *
	 * @param string $username
	 * @param string $password
	 * @return bool
	 */
	function almaseo_handoff_password_works( $username, $password ) {
		$test_url = rest_url( 'wp/v2/users/me' );
		$response = wp_remote_get( $test_url, array(
			'headers'   => array(
				'Authorization' => 'Basic ' . base64_encode( $username . ':' . $password ),
			),
			'timeout'   => 10,
			'sslverify' => false,
		) );

		if ( is_wp_error( $response ) ) {
			return false;
		}
		return 200 === (int) wp_remote_retrieve_response_code( $response );
	}
}

/* ------------------------------------------------------------------
 *  REST API
 * ----------------------------------------------------------------*/

add_action( 'rest_api_init', 'almaseo_handoff_register_routes' );

if ( ! function_exists( 'almaseo_handoff_register_routes' ) ) {
	function almaseo_handoff_register_routes() {

		// GET /connection/status — wizard poll target.
		register_rest_route( 'almaseo/v1', '/connection/status', array(
			'methods'             => WP_REST_Server::READABLE,
			'callback'            => 'almaseo_handoff_rest_status',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		) );

		// POST /connection/handoff — wizard "Connect" click.
		register_rest_route( 'almaseo/v1', '/connection/handoff', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'almaseo_handoff_rest_create',
			'permission_callback' => function () {
				return current_user_can( 'manage_options' );
			},
		) );

		// POST /connection/finalize — dashboard exchanges token for creds.
		// Public on purpose: bearer-style auth via the single-use token.
		register_rest_route( 'almaseo/v1', '/connection/finalize', array(
			'methods'             => WP_REST_Server::CREATABLE,
			'callback'            => 'almaseo_handoff_rest_finalize',
			'permission_callback' => '__return_true',
		) );
	}
}

if ( ! function_exists( 'almaseo_handoff_rest_status' ) ) {
	/**
	 * GET /connection/status
	 *
	 * Lightweight status view used by the wizard's polling loop.
	 */
	function almaseo_handoff_rest_status() {
		$status = function_exists( 'almaseo_get_connection_status' )
			? almaseo_get_connection_status()
			: array( 'connected' => (bool) get_option( 'almaseo_app_password', '' ) );

		// Defer reporting "connected" while a handoff is in flight — the
		// App Password is stored locally as soon as /handoff runs, but the
		// dashboard hasn't actually picked it up until /finalize fires.
		$active_since = (int) get_option( ALMASEO_HANDOFF_ACTIVE_OPT, 0 );
		$in_handoff   = $active_since > 0 && ( time() - $active_since ) < ALMASEO_HANDOFF_TTL;

		return rest_ensure_response( array(
			'connected'       => ( ! $in_handoff ) && ! empty( $status['connected'] ),
			'in_handoff'      => $in_handoff,
			'connection_type' => get_option( 'almaseo_connection_type', 'unknown' ),
			'connected_user'  => $status['connected_user'] ?? '',
			'site_id'         => get_option( 'almaseo_dashboard_site_id', '' ),
		) );
	}
}

if ( ! function_exists( 'almaseo_handoff_rest_create' ) ) {
	/**
	 * POST /connection/handoff
	 *
	 * Generates credentials, stores them under a single-use token, and
	 * returns the dashboard redirect URL plus an inline fallback payload
	 * (for the popup-blocked case).
	 */
	function almaseo_handoff_rest_create( WP_REST_Request $request ) {
		$user = wp_get_current_user();
		if ( ! $user || ! $user->exists() ) {
			return new WP_Error( 'no_user', 'No current user.', array( 'status' => 401 ) );
		}

		$creds = almaseo_handoff_prepare_credentials( $user );
		if ( is_wp_error( $creds ) ) {
			return $creds;
		}

		$site_url = get_site_url();
		$payload  = array(
			'site_url'  => $site_url,
			'username'  => $user->user_login,
			'method'    => $creds['method'],
			'issued_at' => time(),
		);
		if ( 'app_password' === $creds['method'] ) {
			$payload['app_password'] = $creds['password'];
		} else {
			$payload['jwt'] = $creds['jwt'];
		}

		$token = almaseo_handoff_issue_token( $payload );

		// Mark handoff as active so /connection/status defers "connected:true"
		// until /finalize runs.
		update_option( ALMASEO_HANDOFF_ACTIVE_OPT, time() );

		$dashboard_base = defined( 'ALMASEO_DASHBOARD_URL' )
			? ALMASEO_DASHBOARD_URL
			: 'https://app.almaseo.com';

		$handoff_url = add_query_arg(
			array(
				'site_url' => rawurlencode( $site_url ),
				'handoff'  => $token,
				'source'   => 'plugin_wizard',
			),
			trailingslashit( $dashboard_base ) . 'connect'
		);

		// Build the inline fallback (popup-blocked / dashboard-unreachable).
		$fallback = array(
			'site_url' => $site_url,
			'username' => $user->user_login,
			'method'   => $creds['method'],
		);
		if ( 'app_password' === $creds['method'] ) {
			$fallback['password'] = $creds['password'];
		} else {
			$fallback['jwt'] = $creds['jwt'];
		}

		return rest_ensure_response( array(
			'handoff_url' => $handoff_url,
			'token'       => $token,
			'expires_in'  => ALMASEO_HANDOFF_TTL,
			'method'      => $creds['method'],
			'fallback'    => $fallback,
		) );
	}
}

if ( ! function_exists( 'almaseo_handoff_rest_finalize' ) ) {
	/**
	 * POST /connection/finalize
	 *
	 * Public endpoint called by the AlmaSEO dashboard. Exchanges a
	 * single-use handoff token for credentials. Bearer-style: the token
	 * itself is the proof of authorization.
	 */
	function almaseo_handoff_rest_finalize( WP_REST_Request $request ) {
		$token = $request->get_param( 'token' );
		if ( empty( $token ) ) {
			$token = $request->get_param( 'handoff' );
		}
		if ( empty( $token ) ) {
			return new WP_Error( 'missing_token', 'Token is required.', array( 'status' => 400 ) );
		}

		$payload = almaseo_handoff_consume_token( $token );
		if ( is_wp_error( $payload ) ) {
			return $payload;
		}

		// Mirror what almaseo_sync_from_dashboard() does for dashboard-
		// initiated installs so the existing connection-status helpers
		// recognize this as a connected, dashboard-typed site.
		if ( ! get_option( 'almaseo_connection_type' ) ) {
			update_option( 'almaseo_connection_type', 'dashboard_initiated' );
		}
		update_option( 'almaseo_dashboard_synced', true );

		// Clear the handoff-active flag so /connection/status can now report
		// connected: true to the polling wizard.
		delete_option( ALMASEO_HANDOFF_ACTIVE_OPT );

		$response = array(
			'site_url'         => $payload['site_url'] ?? get_site_url(),
			'username'         => $payload['username'] ?? '',
			'method'           => $payload['method'] ?? 'app_password',
			'plugin_version'   => defined( 'ALMASEO_PLUGIN_VERSION' ) ? ALMASEO_PLUGIN_VERSION : '',
			'wordpress_version'=> get_bloginfo( 'version' ),
		);
		if ( 'app_password' === $response['method'] && isset( $payload['app_password'] ) ) {
			$response['app_password'] = $payload['app_password'];
		}
		if ( 'jwt' === $response['method'] && isset( $payload['jwt'] ) ) {
			$response['jwt'] = $payload['jwt'];
		}

		return rest_ensure_response( $response );
	}
}
