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
 * via almaseo_fetch_user_tier() and synced to almaseo_license_tier.
 * When not connected, defaults to 'pro' for backward compatibility.
 *
 * Valid tier values:
 * - 'free': Free tier (basic features only)
 * - 'pro': Pro tier (includes advanced features)
 * - 'agency': Agency tier (all features + white-label, multi-site, etc.)
 *
 * @return bool True if Pro or higher tier is active, false otherwise
 */
function almaseo_is_pro_active() {
    // If site is connected, trust the server-synced tier (no generous default)
    $is_connected = (bool) get_option( 'almaseo_app_password', '' );
    if ( $is_connected ) {
        $tier = get_option( 'almaseo_license_tier', 'free' );
    } else {
        // Not connected: default to 'pro' for backward compatibility
        $tier = get_option( 'almaseo_license_tier', 'pro' );
    }

    // Pro and Agency tiers have Pro features enabled
    return in_array( $tier, array( 'pro', 'agency' ), true );
}

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
 * - 'optimization_dataforseo': DataForSEO keyword provider
 * - 'internal_links': Internal links auto-linker
 * - 'refresh_drafts': Content refresh drafts
 * - 'refresh_queue': Refresh queue autoprioritization
 * - 'date_hygiene': Date hygiene scanner
 * - 'eeat_enforcement': E-E-A-T enforcement
 * - 'gsc_monitor': GSC Monitor
 * - 'orphan_detection': Orphan page detection
 * - 'schema_drift': Schema drift monitor
 * - 'snippet_targeting': Featured snippet targeting
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
function almaseo_feature_available( $feature ) {
    // Define which features require Pro or higher
    $pro_features = array(
        'bulkmeta',               // Bulk metadata editor
        'woocommerce',            // WooCommerce SEO
        'llm_optimization',       // LLM Optimization advanced features
        'evergreen_advanced',     // Advanced evergreen filters
        'schema_advanced',        // Advanced schema options
        'optimization_dataforseo', // DataForSEO provider
        'internal_links',         // Internal links auto-linker
        'refresh_drafts',         // Content refresh drafts
        'refresh_queue',          // Refresh queue autoprioritization
        'date_hygiene',           // Date hygiene scanner
        'eeat_enforcement',       // E-E-A-T enforcement
        'gsc_monitor',            // GSC Monitor
        'orphan_detection',       // Orphan page detection
        'schema_drift',           // Schema drift monitor
        'snippet_targeting',      // Featured snippet targeting
    );

    // Check if this feature requires Pro tier
    $requires_pro = in_array( $feature, $pro_features, true );

    if ( $requires_pro ) {
        // Pro features are only available if Pro is active
        return almaseo_is_pro_active();
    }

    // Free features are always available
    return true;
}

/**
 * Get the current license tier
 *
 * Returns the current license tier value. Useful for displaying
 * tier information in the admin UI.
 *
 * @return string The current tier ('free', 'pro', or 'agency')
 */
function almaseo_get_license_tier() {
    $is_connected = (bool) get_option( 'almaseo_app_password', '' );
    // Connected sites default to 'free' (server sets actual tier via sync)
    // Unconnected sites default to 'pro' for backward compatibility
    return get_option( 'almaseo_license_tier', $is_connected ? 'free' : 'pro' );
}

/**
 * Set the license tier
 *
 * Updates the license tier option. This function should be called
 * by license activation/deactivation logic when implemented.
 *
 * @param string $tier The tier to set ('free', 'pro', or 'agency')
 * @return bool True if the option was updated successfully
 */
function almaseo_set_license_tier( $tier ) {
    // Validate tier value
    $valid_tiers = array( 'free', 'pro', 'agency' );

    if ( ! in_array( $tier, $valid_tiers, true ) ) {
        return false;
    }

    return update_option( 'almaseo_license_tier', $tier );
}

/**
 * Check if current tier is Free
 *
 * @return bool True if current tier is free
 */
function almaseo_is_free_tier() {
    return almaseo_get_license_tier() === 'free';
}

/**
 * Check if current tier is Agency
 *
 * @return bool True if current tier is agency
 */
function almaseo_is_agency_tier() {
    return almaseo_get_license_tier() === 'agency';
}

/**
 * Get a user-friendly display name for the current tier
 *
 * @return string The display name (e.g., 'Free', 'Pro', 'Agency')
 */
function almaseo_get_tier_display_name() {
    $tier = almaseo_get_license_tier();

    $display_names = array(
        'free'   => 'Free',
        'pro'    => 'Pro',
        'agency' => 'Agency',
    );

    return isset( $display_names[ $tier ] ) ? $display_names[ $tier ] : 'Unknown';
}

/**
 * Get all Pro-only feature identifiers
 *
 * Useful for generating upgrade prompts or feature comparison tables
 *
 * @return array Array of Pro feature identifiers
 */
function almaseo_get_pro_features() {
    return array(
        'bulkmeta',
        'woocommerce',
        'llm_optimization',
        'evergreen_advanced',
        'schema_advanced',
        'optimization_dataforseo',
        'internal_links',
        'refresh_drafts',
        'refresh_queue',
        'date_hygiene',
        'eeat_enforcement',
        'gsc_monitor',
        'orphan_detection',
        'schema_drift',
        'snippet_targeting',
    );
}
