<?php
/**
 * AlmaSEO DataForSEO Coming Soon UI
 *
 * Provides UI components for the DataForSEO keyword provider that is not yet implemented.
 * Shows a professional "Coming Soon" panel with licensing-aware CTAs.
 *
 * @package AlmaSEO
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Render the DataForSEO "Coming Soon" panel
 *
 * This function displays a professional coming soon screen with:
 * - Gold "COMING SOON" diagonal ribbon
 * - Feature description and roadmap
 * - Tier-aware CTA (Upgrade for Free tier, Included badge for Pro tier)
 *
 * @return string HTML output for the coming soon panel
 */
function almaseo_render_dataforseo_coming_soon() {
    // Check current tier
    $is_pro = almaseo_feature_available( 'optimization_dataforseo' );
    $tier_display = almaseo_get_tier_display_name();

    // Enqueue dashicons if not already enqueued
    wp_enqueue_style( 'dashicons' );

    // Output the coming soon styles
    almaseo_dataforseo_coming_soon_styles();

    // Start output buffering
    ob_start();
    ?>
    <div class="almaseo-coming-soon-wrapper">
        <!-- Gold "Coming Soon" Ribbon -->
        <div class="almaseo-coming-soon-ribbon">
            <span>COMING SOON</span>
        </div>

        <div class="almaseo-coming-soon-panel">
            <div class="almaseo-coming-soon-inner">
                <!-- Icon -->
                <div class="almaseo-coming-soon-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>

                <!-- Header -->
                <h2>DataForSEO Keyword Intelligence</h2>
                <p class="almaseo-coming-soon-subtitle">Advanced SERP Analytics – Coming Soon</p>

                <?php if ( $is_pro ) : ?>
                    <!-- Pro Tier Badge -->
                    <div class="almaseo-pro-included-badge">
                        <span class="dashicons dashicons-yes-alt"></span>
                        Included in Your Pro Plan
                    </div>
                <?php endif; ?>

                <!-- Feature Description -->
                <div class="almaseo-coming-soon-description">
                    <p>
                        We're building powerful keyword intelligence features powered by DataForSEO's
                        industry-leading SERP data. This premium integration will bring professional-grade
                        SEO insights directly to your WordPress editor.
                    </p>
                </div>

                <!-- Upcoming Features List -->
                <div class="almaseo-coming-soon-features">
                    <h3>What's Coming:</h3>
                    <ul>
                        <li>
                            <span class="dashicons dashicons-chart-area"></span>
                            <strong>Advanced SERP Metrics</strong>
                            <span class="feature-desc">Real-time search volume, trends, and seasonality data</span>
                        </li>
                        <li>
                            <span class="dashicons dashicons-groups"></span>
                            <strong>Competitor Analysis</strong>
                            <span class="feature-desc">See who's ranking and analyze their strategies</span>
                        </li>
                        <li>
                            <span class="dashicons dashicons-analytics"></span>
                            <strong>Keyword Difficulty Scoring</strong>
                            <span class="feature-desc">Accurate difficulty metrics to find quick wins</span>
                        </li>
                        <li>
                            <span class="dashicons dashicons-search"></span>
                            <strong>Live SERP Snapshot Extraction</strong>
                            <span class="feature-desc">Capture and analyze live search results instantly</span>
                        </li>
                        <li>
                            <span class="dashicons dashicons-money-alt"></span>
                            <strong>CPC & Ad Intelligence</strong>
                            <span class="feature-desc">Cost-per-click data and advertising insights</span>
                        </li>
                        <li>
                            <span class="dashicons dashicons-location"></span>
                            <strong>Local & International Data</strong>
                            <span class="feature-desc">Target specific countries, cities, and languages</span>
                        </li>
                    </ul>
                </div>

                <!-- Roadmap Info -->
                <div class="almaseo-coming-soon-roadmap">
                    <p>
                        <strong>Development Status:</strong> Active development in progress<br>
                        <strong>Expected Release:</strong> Q2 2025
                    </p>
                </div>

                <!-- CTA Buttons -->
                <div class="almaseo-coming-soon-actions">
                    <?php if ( ! $is_pro ) : ?>
                        <!-- Free Tier: Upgrade CTA -->
                        <a href="https://almaseo.com/pro#dataforseo" class="button button-primary button-hero" target="_blank">
                            <span class="dashicons dashicons-unlock"></span>
                            Upgrade to Pro for Early Access
                        </a>
                    <?php else : ?>
                        <!-- Pro Tier: Already Included -->
                        <div class="almaseo-pro-waiting-message">
                            <span class="dashicons dashicons-info"></span>
                            You'll get automatic access when this feature launches. No additional purchase needed!
                        </div>
                    <?php endif; ?>

                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-playground' ) ); ?>" class="button button-secondary">
                        <span class="dashicons dashicons-arrow-left-alt"></span>
                        Back to Overview
                    </a>
                </div>

                <!-- Footer -->
                <div class="almaseo-coming-soon-footer">
                    <p>
                        <small>
                            Want to be notified when this launches?
                            <a href="https://almaseo.com/notify" target="_blank">Join our notification list</a>
                        </small>
                    </p>
                    <?php if ( ! $is_pro ) : ?>
                        <p>
                            <small>
                                Already have a Pro license?
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=almaseo-settings' ) ); ?>">Activate it here</a>
                            </small>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php

    return ob_get_clean();
}

/**
 * Output inline CSS for coming soon screens
 *
 * This function outputs the CSS needed for the coming soon UI including
 * the gold diagonal ribbon and panel styling.
 */
function almaseo_dataforseo_coming_soon_styles() {
    static $styles_printed = false;

    // Only print styles once per page load
    if ( $styles_printed ) {
        return;
    }

    $styles_printed = true;

    ?>
    <style>
        /* Coming Soon Wrapper */
        .almaseo-coming-soon-wrapper {
            position: relative;
            margin: 20px 0;
            overflow: hidden;
        }

        /* Gold Diagonal Ribbon */
        .almaseo-coming-soon-ribbon {
            position: absolute;
            top: 15px;
            right: -35px;
            z-index: 10;
            width: 200px;
            text-align: center;
            transform: rotate(45deg);
            background: linear-gradient(135deg, #f5a623 0%, #f7b731 100%);
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
        }

        .almaseo-coming-soon-ribbon span {
            display: block;
            padding: 8px 0;
            color: #fff;
            font-weight: 700;
            font-size: 11px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
        }

        /* Panel Container */
        .almaseo-coming-soon-panel {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 600px;
            padding: 40px 20px;
            text-align: center;
            border: 2px solid #e8e8e8;
            background: #fafafa;
            background: linear-gradient(135deg, #fafafa 0%, #f5f5f5 100%);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .almaseo-coming-soon-inner {
            max-width: 700px;
            padding: 20px;
        }

        /* Icon */
        .almaseo-coming-soon-icon {
            margin-bottom: 20px;
        }

        .almaseo-coming-soon-icon .dashicons {
            font-size: 80px;
            width: 80px;
            height: 80px;
            color: #f7b731;
            opacity: 0.9;
        }

        /* Header */
        .almaseo-coming-soon-panel h2 {
            font-size: 32px;
            margin: 0 0 10px;
            color: #333;
            font-weight: 600;
        }

        .almaseo-coming-soon-subtitle {
            font-size: 18px;
            color: #666;
            margin-bottom: 25px;
            font-weight: 500;
        }

        /* Pro Included Badge */
        .almaseo-pro-included-badge {
            display: inline-block;
            padding: 10px 20px;
            margin: 15px 0 25px;
            background: #46b450;
            color: #fff;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
        }

        .almaseo-pro-included-badge .dashicons {
            font-size: 18px;
            width: 18px;
            height: 18px;
            vertical-align: middle;
            margin-right: 5px;
        }

        /* Description */
        .almaseo-coming-soon-description {
            margin: 25px 0;
            padding: 20px;
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            text-align: left;
        }

        .almaseo-coming-soon-description p {
            margin: 0;
            font-size: 15px;
            line-height: 1.7;
            color: #555;
        }

        /* Features List */
        .almaseo-coming-soon-features {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            margin: 25px 0;
            text-align: left;
        }

        .almaseo-coming-soon-features h3 {
            margin: 0 0 20px;
            font-size: 18px;
            color: #333;
            text-align: center;
        }

        .almaseo-coming-soon-features ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .almaseo-coming-soon-features li {
            padding: 12px 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: flex-start;
            gap: 12px;
        }

        .almaseo-coming-soon-features li:last-child {
            border-bottom: none;
        }

        .almaseo-coming-soon-features li .dashicons {
            color: #f7b731;
            font-size: 22px;
            width: 22px;
            height: 22px;
            flex-shrink: 0;
            margin-top: 2px;
        }

        .almaseo-coming-soon-features li strong {
            display: block;
            color: #333;
            font-size: 14px;
            margin-bottom: 3px;
        }

        .almaseo-coming-soon-features li .feature-desc {
            display: block;
            color: #666;
            font-size: 13px;
            line-height: 1.5;
        }

        /* Roadmap Info */
        .almaseo-coming-soon-roadmap {
            background: #fff9e6;
            border: 1px solid #f7b731;
            border-radius: 8px;
            padding: 15px;
            margin: 25px 0;
        }

        .almaseo-coming-soon-roadmap p {
            margin: 0;
            font-size: 14px;
            color: #666;
            line-height: 1.8;
        }

        .almaseo-coming-soon-roadmap strong {
            color: #333;
        }

        /* Actions */
        .almaseo-coming-soon-actions {
            margin: 30px 0 20px;
        }

        .almaseo-coming-soon-actions .button {
            margin: 5px;
        }

        .almaseo-coming-soon-actions .button-hero {
            padding: 12px 30px;
            height: auto;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .almaseo-coming-soon-actions .button-primary {
            background: #f7b731;
            border-color: #f7b731;
            box-shadow: 0 2px 4px rgba(247, 183, 49, 0.3);
        }

        .almaseo-coming-soon-actions .button-primary:hover {
            background: #f5a623;
            border-color: #f5a623;
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(247, 183, 49, 0.4);
        }

        .almaseo-coming-soon-actions .button .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }

        /* Pro Waiting Message */
        .almaseo-pro-waiting-message {
            display: inline-block;
            padding: 15px 25px;
            background: #e7f5e9;
            border: 1px solid #46b450;
            border-radius: 6px;
            color: #2c662d;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 15px;
        }

        .almaseo-pro-waiting-message .dashicons {
            color: #46b450;
            vertical-align: middle;
            margin-right: 5px;
        }

        /* Footer */
        .almaseo-coming-soon-footer {
            margin-top: 25px;
            padding-top: 20px;
            border-top: 1px solid #e0e0e0;
        }

        .almaseo-coming-soon-footer p {
            margin: 8px 0;
            color: #666;
        }

        .almaseo-coming-soon-footer a {
            color: #f7b731;
            text-decoration: none;
            font-weight: 500;
        }

        .almaseo-coming-soon-footer a:hover {
            text-decoration: underline;
            color: #f5a623;
        }

        /* Responsive */
        @media (max-width: 782px) {
            .almaseo-coming-soon-panel h2 {
                font-size: 26px;
            }

            .almaseo-coming-soon-subtitle {
                font-size: 16px;
            }

            .almaseo-coming-soon-actions .button {
                display: block;
                margin: 10px auto;
                max-width: 300px;
            }

            .almaseo-coming-soon-ribbon {
                top: 12px;
                right: -40px;
            }
        }
    </style>
    <?php
}
