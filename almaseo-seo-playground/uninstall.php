<?php
/**
 * Fired when the AlmaSEO SEO Playground plugin is uninstalled (deleted from
 * the WordPress Plugins page).
 *
 * Scope: connection state ONLY. Application Passwords, JWT secret, dashboard
 * link metadata, and activation flags are removed so a fresh install starts
 * with a clean handshake against the AlmaSEO dashboard.
 *
 * Everything else is preserved: settings (search appearance, sitemap, schema,
 * etc.), custom DB tables (redirects, internal links, refresh queue, 404 log,
 * etc.), post meta, term meta, and the setup-wizard-completed flag. If the
 * user reinstalls, their work survives — only the credentials and dashboard
 * pairing reset.
 *
 * Mirrors the AlmaSEO Connector plugin's uninstall.php with the additional
 * connection-state options the Playground uses.
 *
 * @package AlmaSEO
 */

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

/* ------------------------------------------------------------------
 *  Connection / credential options
 * ----------------------------------------------------------------*/

$connection_options = array(
	// Credentials
	'almaseo_app_password',
	'almaseo_jwt_secret',
	'almaseo_secret',

	// Pairing metadata
	'almaseo_connected_user',
	'almaseo_connected_date',
	'almaseo_connection_type',
	'almaseo_auto_connected',

	// Dashboard link state (what the dashboard told us about this site)
	'almaseo_dashboard_site_id',
	'almaseo_dashboard_synced',
	'almaseo_dashboard_registered',
	'almaseo_dashboard_check_date',

	// Activation redirect flags (so reinstall re-runs the welcome flow)
	'almaseo_playground_do_activation_redirect',
	'almaseo_do_activation_redirect',

	// Wizard handoff active flag (Scenario-4 deep-link, v1.9.0+)
	'almaseo_handoff_active',
);

foreach ( $connection_options as $opt ) {
	delete_option( $opt );
}

/* ------------------------------------------------------------------
 *  Connection-related transients (handoff tokens, sync notices)
 * ----------------------------------------------------------------*/

global $wpdb;

$transient_prefixes = array(
	'_transient_almaseo_handoff_',
	'_transient_timeout_almaseo_handoff_',
	'_transient_almaseo_dashboard_sync_success',
	'_transient_timeout_almaseo_dashboard_sync_success',
	'_transient_almaseo_needs_password_import',
	'_transient_timeout_almaseo_needs_password_import',
);

foreach ( $transient_prefixes as $prefix ) {
	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
			$wpdb->esc_like( $prefix ) . '%'
		)
	);
}

/* ------------------------------------------------------------------
 *  WordPress Application Passwords created by AlmaSEO
 *
 *  We delete any password whose label starts with "AlmaSEO" so that a
 *  fresh install starts without a stale credential the dashboard no
 *  longer recognizes.
 * ----------------------------------------------------------------*/

if ( class_exists( 'WP_Application_Passwords' ) ) {
	$users = get_users( array(
		'role'   => 'administrator',
		'fields' => array( 'ID' ),
	) );

	foreach ( $users as $user ) {
		$app_passwords = WP_Application_Passwords::get_user_application_passwords( $user->ID );
		if ( ! is_array( $app_passwords ) ) {
			continue;
		}
		foreach ( $app_passwords as $ap ) {
			$name = isset( $ap['name'] ) ? (string) $ap['name'] : '';
			if ( '' !== $name && 0 === strpos( $name, 'AlmaSEO' ) ) {
				WP_Application_Passwords::delete_application_password( $user->ID, $ap['uuid'] );
			}
		}
	}
}
