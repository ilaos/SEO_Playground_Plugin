<?php
/**
 * AlmaSEO Role/Access Manager
 *
 * Controls which WordPress user roles can access SEO editing features
 * (metabox, bulk meta). Plugin settings pages remain admin-only.
 *
 * @package AlmaSEO
 * @since   8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Role_Manager {

    const CAPABILITY  = 'almaseo_manage_seo';
    const OPTION_KEY  = 'almaseo_role_capabilities';
    const VERSION_KEY = 'almaseo_role_manager_version';

    /**
     * Initialize role manager hooks.
     */
    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'maybe_sync_capabilities' ) );
    }

    /**
     * Get configured role capabilities.
     *
     * @return array Role slug => bool.
     */
    public static function get_settings() {
        return get_option( self::OPTION_KEY, self::get_defaults() );
    }

    /**
     * Default role configuration.
     *
     * @return array
     */
    public static function get_defaults() {
        return array(
            'administrator' => true,
            'editor'        => true,
            'author'        => false,
            'contributor'   => false,
        );
    }

    /**
     * Save role capabilities and sync to WordPress roles.
     *
     * @param array $settings Role slug => bool.
     */
    public static function save_settings( $settings ) {
        $sanitized = self::sanitize( $settings );
        update_option( self::OPTION_KEY, $sanitized );
        self::sync_capabilities( $sanitized );
    }

    /**
     * Sync capabilities based on stored settings.
     * Only runs when settings change (tracked by version hash).
     *
     * @param array|null $settings Optional explicit settings.
     */
    public static function maybe_sync_capabilities() {
        $settings     = self::get_settings();
        $version_hash = md5( wp_json_encode( $settings ) );
        $stored_hash  = get_option( self::VERSION_KEY, '' );

        if ( $version_hash !== $stored_hash ) {
            self::sync_capabilities( $settings );
            update_option( self::VERSION_KEY, $version_hash );
        }
    }

    /**
     * Apply capabilities to WordPress roles.
     *
     * @param array $settings Role slug => bool.
     */
    public static function sync_capabilities( $settings = null ) {
        if ( null === $settings ) {
            $settings = self::get_settings();
        }

        // Administrator always gets the capability.
        $settings['administrator'] = true;

        foreach ( $settings as $role_slug => $has_cap ) {
            $role = get_role( $role_slug );
            if ( ! $role ) {
                continue;
            }

            if ( $has_cap ) {
                $role->add_cap( self::CAPABILITY );
            } else {
                $role->remove_cap( self::CAPABILITY );
            }
        }
    }

    /**
     * Check if a user can manage SEO.
     * Falls back to manage_options if capability hasn't been set up yet.
     *
     * @param int|null $user_id Optional user ID. Defaults to current user.
     * @return bool
     */
    public static function user_can_manage_seo( $user_id = null ) {
        if ( $user_id ) {
            return user_can( $user_id, self::CAPABILITY ) || user_can( $user_id, 'manage_options' );
        }
        return current_user_can( self::CAPABILITY ) || current_user_can( 'manage_options' );
    }

    /**
     * Get all assignable roles for the settings UI.
     *
     * @return array Role slug => display name.
     */
    public static function get_assignable_roles() {
        $wp_roles = wp_roles();
        $roles    = array();

        foreach ( $wp_roles->roles as $slug => $role_data ) {
            // Skip super-admin role if exists.
            if ( $slug === 'administrator' ) {
                $roles[ $slug ] = $role_data['name'] . ' (' . esc_html__( 'always enabled', 'almaseo-seo-playground' ) . ')';
            } else {
                $roles[ $slug ] = $role_data['name'];
            }
        }

        return $roles;
    }

    /**
     * Sanitize role capabilities input.
     *
     * @param array $input Raw input.
     * @return array Sanitized.
     */
    public static function sanitize( $input ) {
        if ( ! is_array( $input ) ) {
            return self::get_defaults();
        }

        $sanitized = array();
        $wp_roles  = wp_roles();

        foreach ( $wp_roles->roles as $slug => $role_data ) {
            $sanitized[ $slug ] = ! empty( $input[ $slug ] );
        }

        // Administrator always true.
        $sanitized['administrator'] = true;

        return $sanitized;
    }

    /**
     * Clean up capabilities on plugin deactivation.
     */
    public static function cleanup() {
        $wp_roles = wp_roles();
        foreach ( $wp_roles->roles as $slug => $role_data ) {
            $role = get_role( $slug );
            if ( $role ) {
                $role->remove_cap( self::CAPABILITY );
            }
        }
    }
}
