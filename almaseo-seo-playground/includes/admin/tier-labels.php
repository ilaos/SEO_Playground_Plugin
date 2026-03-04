<?php
/**
 * AlmaSEO Tier Labels — Lightweight Feature Tier Registry
 *
 * Establishes which features belong to Free vs Pro tiers.
 * Adds visual badges to the admin sidebar menu. No access blocking —
 * just labeling for now. Real feature gating can read from this
 * registry later when needed.
 *
 * @package AlmaSEO
 * @since   8.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AlmaSEO_Tier_Labels {

    /**
     * Tier registry: menu slug => 'free' | 'pro'
     *
     * Slugs not listed here get no badge (core infrastructure pages
     * like Overview, Connection, Settings, Documentation).
     */
    private static $tiers = array(
        // ── Free Features ──
        'almaseo-evergreen'          => 'free',
        'almaseo-search-appearance'  => 'free',
        'almaseo-import'             => 'free',
        'almaseo-robots'             => 'free',
        'almaseo-htaccess'           => 'free',
        'almaseo-llms-txt'           => 'free',
        'almaseo-redirects'          => 'free',
        'almaseo-404-logs'           => 'free',

        // ── Pro Features ──
        'almaseo-bulk-meta'          => 'pro',
        'almaseo-internal-links'     => 'pro',
        'almaseo-orphan-pages'       => 'pro',
        'almaseo-refresh-drafts'     => 'pro',
        'almaseo-refresh-queue'      => 'pro',
        'almaseo-date-hygiene'       => 'pro',
        'almaseo-eeat'               => 'pro',
        'almaseo-gsc-monitor'        => 'pro',
        'almaseo-schema-drift'       => 'pro',
        'almaseo-snippet-targets'    => 'pro',
        'seo-playground-woocommerce' => 'pro',
    );

    /**
     * Non-menu features — for reference by other systems (Documentation page, etc.)
     * These don't appear in the sidebar but belong to a tier.
     */
    private static $feature_tiers = array(
        // ── Free (in metabox / settings / blocks) ──
        'headline_analyzer'      => 'free',
        'readability'            => 'free',
        'visual_social_previews' => 'free',
        'keyword_suggestions'    => 'free',
        'image_seo'              => 'free',
        'cornerstone_content'    => 'free',
        'crawl_optimization'     => 'free',
        'verification_codes'     => 'free',
        'rss_controls'           => 'free',
        'role_manager'           => 'free',
        'google_analytics'       => 'free',
        'local_business_schema'  => 'free',
        'setup_wizard'           => 'free',
        'faq_block'              => 'free',
        'toc_block'              => 'free',
        'howto_block'            => 'free',
        'breadcrumbs_block'      => 'free',
        'link_attributes'        => 'free',
        'seo_meta_box'           => 'free',
        'focus_keywords'         => 'free',
        'health_score'           => 'free',
        'open_graph'             => 'free',
        'meta_robots'            => 'free',
        'canonical_url'          => 'free',
        'xml_sitemaps'           => 'free',
        'image_sitemaps'         => 'free',
        'video_sitemaps'         => 'free',
        'news_sitemaps'          => 'free',
        'html_sitemap'           => 'free',
        'indexnow'               => 'free',
        'breadcrumbs_module'     => 'free',
        'notes_history'          => 'free',

        // ── Pro (non-menu) ──
        'internal_links_auto'    => 'pro',
        'refresh_drafts'         => 'pro',
        'refresh_queue'          => 'pro',
        'date_hygiene'           => 'pro',
        'bulkmeta'               => 'pro',
    );

    /**
     * Initialize hooks.
     */
    public static function init() {
        add_action( 'admin_menu', array( __CLASS__, 'add_sidebar_badges' ), 999 );
        add_action( 'admin_head', array( __CLASS__, 'badge_css' ) );
    }

    /**
     * Get the tier for a menu slug.
     *
     * @param  string $slug  Menu slug or feature identifier.
     * @return string|null   'free', 'pro', or null if not registered.
     */
    public static function get_tier( $slug ) {
        if ( isset( self::$tiers[ $slug ] ) ) {
            return self::$tiers[ $slug ];
        }
        if ( isset( self::$feature_tiers[ $slug ] ) ) {
            return self::$feature_tiers[ $slug ];
        }
        return null;
    }

    /**
     * Check if a feature is free.
     */
    public static function is_free( $slug ) {
        return self::get_tier( $slug ) === 'free';
    }

    /**
     * Check if a feature is pro.
     */
    public static function is_pro( $slug ) {
        return self::get_tier( $slug ) === 'pro';
    }

    /**
     * Append tier badges to admin sidebar menu labels.
     */
    public static function add_sidebar_badges() {
        global $submenu;

        if ( ! isset( $submenu['seo-playground'] ) ) {
            return;
        }

        foreach ( $submenu['seo-playground'] as &$item ) {
            $slug = $item[2];

            if ( ! isset( self::$tiers[ $slug ] ) ) {
                continue; // No badge for unlisted items (Overview, Connection, Settings, Documentation)
            }

            $tier = self::$tiers[ $slug ];

            if ( 'pro' === $tier ) {
                $item[0] .= ' <span class="almaseo-tier-badge almaseo-tier-pro">PRO</span>';
            } else {
                $item[0] .= ' <span class="almaseo-tier-badge almaseo-tier-free">FREE</span>';
            }
        }
    }

    /**
     * Output CSS for sidebar badges.
     */
    public static function badge_css() {
        ?>
        <style>
            /* ── Tier Badges (Sidebar) ── */
            .almaseo-tier-badge {
                display: inline-block;
                font-size: 9px;
                font-weight: 600;
                text-transform: uppercase;
                letter-spacing: 0.5px;
                padding: 1px 5px;
                border-radius: 3px;
                line-height: 1.4;
                vertical-align: middle;
                margin-left: 4px;
            }

            .almaseo-tier-free {
                background: rgba(0, 163, 42, 0.15);
                color: #00a32a;
            }

            .almaseo-tier-pro {
                background: rgba(107, 33, 168, 0.15);
                color: #6b21a8;
            }

            /* Adjust for collapsed sidebar */
            .folded .almaseo-tier-badge {
                display: none;
            }

            /* Hover state in sidebar */
            #adminmenu a:hover .almaseo-tier-badge {
                opacity: 0.9;
            }

            /* Current page highlight */
            #adminmenu .current .almaseo-tier-badge,
            #adminmenu .wp-has-current-submenu .almaseo-tier-badge {
                opacity: 1;
            }
        </style>
        <?php
    }
}
