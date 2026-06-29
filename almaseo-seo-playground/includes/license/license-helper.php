<?php
/**
 * AlmaSEO License & Tier Helper
 *
 * Centralized license and feature tier checking for the AlmaSEO SEO Playground plugin.
 * This file provides a single source of truth for determining which features are available
 * based on the current license tier (Free, Pro, Agency).
 *
 * @package AlmaSEO
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Check if Pro license is currently active
 *
 * When the site is connected to AlmaSEO, the tier is validated server-side
 * WordPress.org build: tier sync removed; helpers are free no-op shims.
 * When not connected, defaults to 'pro' for backward compatibility.
 *
 * Valid tier values:
 * - 'free': Free tier (basic features only)
 * - 'pro': Pro tier (includes advanced features)
 * - 'agency': Agency tier (all features + white-label, multi-site, etc.)
 *
 * @return bool True if Pro or higher tier is active, false otherwise
 */
if (!function_exists('almaseo_is_pro_active')) {
function almaseo_is_pro_active() {
    // Until tier enforcement is fully implemented (server-side sync + gating),
    // default ALL sites to 'pro'. Once the tier sync endpoint is live and
    // almaseo_license_tier is reliably set by the server, change the connected
    // default back to 'free'.
    // WordPress.org build: single free plugin, no paid tiers. Always "active".
    // No-op shim; gate call sites are removed in the Phase 2 cleanup.
    return true;
}
} // end function_exists guard: almaseo_is_pro_active

/**
 * Check if a specific feature is available based on current license tier
 *
 * This function determines whether a given feature should be accessible
 * based on the current license tier. Each feature is mapped to a minimum
 * required tier level.
 *
 * Pro-only features (require Pro or Agency tier):
 * - 'bulkmeta': Bulk metadata editor
 * - 'woocommerce': WooCommerce SEO enhancements
 * - 'llm_optimization': LLM Optimization advanced features
 * - 'evergreen_advanced': Advanced evergreen content filters
 * - 'schema_advanced': Advanced schema markup options
 * - 'schema_multi': Multi-schema (additional @graph nodes per page)
 * - 'internal_links': Internal links auto-linker
 * - 'refresh_drafts': Content refresh drafts
 * - 'refresh_queue': Refresh queue autoprioritization
 * - 'date_hygiene': Date hygiene scanner
 * - 'eeat_enforcement': E-E-A-T enforcement
 * - 'gsc_monitor': GSC Monitor
 * - 'orphan_detection': Orphan page detection
 * - 'schema_drift': Schema drift monitor
 * - 'author_entity_dashboard': RESERVED (not yet enforced) — future dashboard-powered
 *      author enrichment (auto-fill author identity from the AlmaSEO client profile,
 *      AI-written bios, cross-site author entities). The baseline linked-author
 *      Person schema is FREE (parity with Yoast/Rank Math); only this enhanced
 *      layer is gated when it ships.
 *
 * Free tier features (available to all):
 * - Redirect manager (301/302)
 * - 404 tracking & logs
 * - Health score (all signals)
 * - Metadata history
 * - Schema markup
 * - Sitemaps (XML, image, video, news)
 * - Meta tags (title, description)
 * - Evergreen tracking (basic)
 * - Robots.txt editor
 * - Search appearance templates
 * - Import/migration
 *
 * @param string $feature The feature identifier to check
 * @return bool True if the feature is available in the current tier, false otherwise
 */
if (!function_exists('almaseo_feature_available')) {
function almaseo_feature_available( $feature ) {
    // WordPress.org build: every feature in this plugin is free.
    // No-op shim; gate call sites are removed in the Phase 2 cleanup.
    return true;
}
} // end function_exists guard: almaseo_feature_available

/**
 * Get the current license tier
 *
 * Returns the current license tier value. Useful for displaying
 * tier information in the admin UI.
 *
 * @return string The current tier ('free', 'pro', or 'agency')
 */
if (!function_exists('almaseo_get_license_tier')) {
function almaseo_get_license_tier() {
    // Default all sites to 'pro' until tier enforcement is fully implemented.
    // Once server-side tier sync is live, change default back to 'free' for connected sites.
    return get_option( 'almaseo_license_tier', 'pro' );
}
} // end function_exists guard: almaseo_get_license_tier

/**
 * Set the license tier
 *
 * Updates the license tier option. This function should be called
 * by license activation/deactivation logic when implemented.
 *
 * @param string $tier The tier to set ('free', 'pro', or 'agency')
 * @return bool True if the option was updated successfully
 */
if (!function_exists('almaseo_set_license_tier')) {
function almaseo_set_license_tier( $tier ) {
    // Validate tier value
    $valid_tiers = array( 'free', 'pro', 'agency' );

    if ( ! in_array( $tier, $valid_tiers, true ) ) {
        return false;
    }

    return update_option( 'almaseo_license_tier', $tier );
}
} // end function_exists guard: almaseo_set_license_tier

/**
 * Check if current tier is Free
 *
 * @return bool True if current tier is free
 */
if (!function_exists('almaseo_is_free_tier')) {
function almaseo_is_free_tier() {
    // WordPress.org build: no free/paid split, so never a "free tier"
    // (prevents legacy lock/unlock UI). No-op shim pending Phase 2 removal.
    return false;
}
} // end function_exists guard: almaseo_is_free_tier

/**
 * Check if current tier is Agency
 *
 * @return bool True if current tier is agency
 */
if (!function_exists('almaseo_is_agency_tier')) {
function almaseo_is_agency_tier() {
    return almaseo_get_license_tier() === 'agency';
}
} // end function_exists guard: almaseo_is_agency_tier

/**
 * Get a user-friendly display name for the current tier
 *
 * @return string The display name (e.g., 'Free', 'Pro', 'Agency')
 */
if (!function_exists('almaseo_get_tier_display_name')) {
function almaseo_get_tier_display_name() {
    $tier = almaseo_get_license_tier();

    $display_names = array(
        'free'   => 'Free',
        'pro'    => 'Pro',
        'agency' => 'Agency',
    );

    return isset( $display_names[ $tier ] ) ? $display_names[ $tier ] : 'Unknown';
}
} // end function_exists guard: almaseo_get_tier_display_name

/**
 * Get all Pro-only feature identifiers
 *
 * Useful for generating upgrade prompts or feature comparison tables
 *
 * @return array Array of Pro feature identifiers
 */
if (!function_exists('almaseo_get_pro_features')) {
function almaseo_get_pro_features() {
    return array(
        'bulkmeta',
        'woocommerce',
        'llm_optimization',
        'evergreen_advanced',
        'schema_advanced',
        'schema_multi',
        'internal_links',
        'refresh_drafts',
        'refresh_queue',
        'date_hygiene',
        'eeat_enforcement',
        'gsc_monitor',
        'orphan_detection',
        'schema_drift',
        'author_entity_dashboard', // RESERVED — see almaseo_feature_available()
    );
}
} // end function_exists guard: almaseo_get_pro_features
