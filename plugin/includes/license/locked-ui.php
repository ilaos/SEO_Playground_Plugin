<?php
/**
 * AlmaSEO Locked Feature UI Helper
 *
 * Provides UI components for displaying locked Pro features to Free tier users.
 * This creates an upsell opportunity while keeping menu items visible.
 *
 * @package AlmaSEO
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

/**
 * Render a locked feature screen for Pro-only features
 *
 * This function displays a professional lock screen with an upgrade CTA
 * when a Free tier user accesses a Pro-only feature page.
 *
 * @param string $feature_name Optional custom feature name to display
 * @param string $description Optional custom description text
 * @param string $upgrade_url Optional custom upgrade URL
 */
function almaseo_render_locked_feature( $feature_name = '', $description = '', $upgrade_url = '' ) {
    // Default values
    if ( empty( $feature_name ) ) {
        $feature_name = 'This Feature';
    }

    if ( empty( $description ) ) {
        $description = 'Upgrade to AlmaSEO Pro to unlock this feature and gain access to advanced SEO tools.';
    }

    if ( empty( $upgrade_url ) ) {
        $upgrade_url = 'https://almaseo.com/pro';
    }

    // Enqueue dashicons if not already enqueued
    wp_enqueue_style( 'dashicons' );

    // Inline CSS for the locked feature screen
    almaseo_locked_feature_styles();

    // Render the locked screen HTML
    ?>
    <div class="wrap almaseo-admin-page">
        <div class="almaseo-locked-feature">
            <div class="almaseo-locked-inner">
                <span class="almaseo-lock-icon dashicons dashicons-lock"></span>
                <h2><?php echo esc_html( $feature_name ); ?> is a Pro Feature</h2>
                <p class="almaseo-locked-description">
                    <?php echo esc_html( $description ); ?>
                </p>

                <div class="almaseo-locked-benefits">
                    <h3>Pro Features Include:</h3>
                    <ul>
                        <li><span class="dashicons dashicons-yes"></span> Advanced Redirect Manager with 301/302 support</li>
                        <li><span class="dashicons dashicons-yes"></span> Bulk Metadata Editor for mass updates</li>
                        <li><span class="dashicons dashicons-yes"></span> WooCommerce SEO Optimization</li>
                        <li><span class="dashicons dashicons-yes"></span> Advanced 404 Tracking & Analytics</li>
                        <li><span class="dashicons dashicons-yes"></span> Extended Metadata History (30+ days)</li>
                        <li><span class="dashicons dashicons-yes"></span> DataForSEO Keyword Intelligence</li>
                        <li><span class="dashicons dashicons-yes"></span> Priority Support & Updates</li>
                    </ul>
                </div>

                <div class="almaseo-locked-actions">
                    <a href="<?php echo esc_url( $upgrade_url ); ?>" class="button button-primary button-hero" target="_blank">
                        <span class="dashicons dashicons-unlock"></span>
                        Upgrade to Pro
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-playground' ) ); ?>" class="button button-secondary">
                        Back to Overview
                    </a>
                </div>

                <p class="almaseo-locked-footer">
                    <small>
                        Already have a Pro license?
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=almaseo-settings' ) ); ?>">Activate it here</a>
                    </small>
                </p>
            </div>
        </div>
    </div>
    <?php
}

/**
 * Output inline CSS for locked feature screens
 *
 * This function outputs the CSS needed for the locked feature UI.
 * It's called automatically by almaseo_render_locked_feature().
 */
function almaseo_locked_feature_styles() {
    static $styles_printed = false;

    // Only print styles once per page load
    if ( $styles_printed ) {
        return;
    }

    $styles_printed = true;

    ?>
    <style>
        .almaseo-locked-feature {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 500px;
            margin: 20px 0;
            text-align: center;
            border: 2px solid #e0e0e0;
            background: #fafafa;
            background: linear-gradient(135deg, #fafafa 0%, #f0f0f0 100%);
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .almaseo-locked-inner {
            max-width: 600px;
            padding: 60px 40px;
        }

        .almaseo-lock-icon {
            font-size: 80px;
            width: 80px;
            height: 80px;
            color: #999;
            margin: 0 auto 20px;
            display: block;
            opacity: 0.7;
        }

        .almaseo-locked-feature h2 {
            font-size: 28px;
            margin: 0 0 15px;
            color: #333;
            font-weight: 600;
        }

        .almaseo-locked-description {
            font-size: 16px;
            color: #666;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .almaseo-locked-benefits {
            background: #fff;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 25px;
            margin: 30px 0;
            text-align: left;
        }

        .almaseo-locked-benefits h3 {
            margin: 0 0 15px;
            font-size: 18px;
            color: #333;
            text-align: center;
        }

        .almaseo-locked-benefits ul {
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .almaseo-locked-benefits li {
            padding: 8px 0;
            color: #555;
            font-size: 14px;
            display: flex;
            align-items: center;
        }

        .almaseo-locked-benefits li .dashicons {
            color: #46b450;
            margin-right: 10px;
            font-size: 18px;
            width: 18px;
            height: 18px;
        }

        .almaseo-locked-actions {
            margin: 30px 0 20px;
        }

        .almaseo-locked-actions .button {
            margin: 0 5px;
        }

        .almaseo-locked-actions .button-hero {
            padding: 12px 30px;
            height: auto;
            font-size: 16px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .almaseo-locked-actions .button-primary {
            background: #2271b1;
            border-color: #2271b1;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .almaseo-locked-actions .button-primary:hover {
            background: #135e96;
            border-color: #135e96;
            transform: translateY(-1px);
            box-shadow: 0 3px 6px rgba(0, 0, 0, 0.15);
        }

        .almaseo-locked-actions .button .dashicons {
            font-size: 20px;
            width: 20px;
            height: 20px;
        }

        .almaseo-locked-footer {
            margin-top: 20px;
            color: #666;
        }

        .almaseo-locked-footer a {
            color: #2271b1;
            text-decoration: none;
        }

        .almaseo-locked-footer a:hover {
            text-decoration: underline;
        }

        /* Responsive adjustments */
        @media (max-width: 782px) {
            .almaseo-locked-inner {
                padding: 40px 20px;
            }

            .almaseo-locked-feature h2 {
                font-size: 24px;
            }

            .almaseo-locked-actions .button {
                display: block;
                margin: 10px 0;
                width: 100%;
            }
        }
    </style>
    <?php
}

/**
 * Get feature-specific locked screen content
 *
 * Returns customized content for specific Pro features.
 * This allows each feature to have tailored messaging.
 *
 * @param string $feature The feature identifier
 * @return array Array with 'name', 'description', and 'url' keys
 */
function almaseo_get_locked_feature_content( $feature ) {
    $content = array(
        'redirects' => array(
            'name'        => 'Redirect Manager',
            'description' => 'Create and manage 301/302 redirects, track hits, prevent 404 errors, and improve your site SEO with our advanced redirect management system.',
            'url'         => 'https://almaseo.com/pro#redirects',
        ),
        'bulkmeta' => array(
            'name'        => 'Bulk Metadata Editor',
            'description' => 'Update SEO titles, descriptions, and metadata across multiple posts at once. Save hours with batch editing capabilities.',
            'url'         => 'https://almaseo.com/pro#bulkmeta',
        ),
        'woocommerce' => array(
            'name'        => 'WooCommerce SEO',
            'description' => 'Optimize your WooCommerce store with product schema markup, optimized sitemaps, and advanced eCommerce SEO features.',
            'url'         => 'https://almaseo.com/pro#woocommerce',
        ),
        '404_advanced' => array(
            'name'        => '404 Advanced Analytics',
            'description' => 'Get detailed 404 tracking with referrer analysis, user agent tracking, and advanced filtering options.',
            'url'         => 'https://almaseo.com/pro#404',
        ),
        'evergreen_advanced' => array(
            'name'        => 'Advanced Evergreen Features',
            'description' => 'Access advanced filtering, bulk operations, and enhanced reporting for your evergreen content strategy.',
            'url'         => 'https://almaseo.com/pro#evergreen',
        ),
        'schema_advanced' => array(
            'name'        => 'Advanced Schema Options',
            'description' => 'Unlock advanced schema types, custom properties, and enhanced structured data capabilities.',
            'url'         => 'https://almaseo.com/pro#schema',
        ),
        'optimization_dataforseo' => array(
            'name'        => 'DataForSEO Integration',
            'description' => 'Access real-time keyword data, search volume, difficulty scores, and competitive analysis with DataForSEO integration.',
            'url'         => 'https://almaseo.com/pro#dataforseo',
        ),
        'health_advanced' => array(
            'name'        => 'Advanced Health Score',
            'description' => 'Get detailed health score breakdowns, historical tracking, and advanced SEO health metrics.',
            'url'         => 'https://almaseo.com/pro#health',
        ),
        'history_extended' => array(
            'name'        => 'Extended Metadata History',
            'description' => 'Store and access metadata history beyond 30 days with unlimited revision tracking and restoration.',
            'url'         => 'https://almaseo.com/pro#history',
        ),
    );

    if ( isset( $content[ $feature ] ) ) {
        return $content[ $feature ];
    }

    // Default fallback
    return array(
        'name'        => '',
        'description' => '',
        'url'         => 'https://almaseo.com/pro',
    );
}

/**
 * Render a feature-specific locked screen
 *
 * This is a convenience wrapper that automatically loads
 * feature-specific content for the locked screen.
 *
 * @param string $feature The feature identifier from almaseo_feature_available()
 */
function almaseo_render_feature_locked( $feature ) {
    $content = almaseo_get_locked_feature_content( $feature );

    almaseo_render_locked_feature(
        $content['name'],
        $content['description'],
        $content['url']
    );
}
