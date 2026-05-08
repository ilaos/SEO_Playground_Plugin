<?php
/**
 * AlmaSEO Welcome Screen Page
 *
 * Renders the welcome/onboarding page shown after plugin activation.
 *
 * @package AlmaSEO
 * @since 6.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

if (!function_exists('almaseo_welcome_screen_page')) {
function almaseo_welcome_screen_page() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'almaseo-seo-playground'));
    }
    ?>
    <div class="wrap" style="max-width: 900px; margin: 40px auto;">
        <style>
            .almaseo-welcome-container {
                background: white;
                border-radius: 10px;
                box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                padding: 40px;
                margin-top: 20px;
            }
            .almaseo-welcome-header {
                text-align: center;
                padding-bottom: 30px;
                border-bottom: 2px solid #f0f0f0;
                margin-bottom: 40px;
            }
            .almaseo-welcome-title {
                font-size: 36px;
                color: #23282d;
                margin: 0 0 15px 0;
                font-weight: 600;
            }
            .almaseo-welcome-subtitle {
                font-size: 18px;
                color: #666;
                margin: 0;
            }
            .almaseo-features-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 25px;
                margin: 40px 0;
            }
            .almaseo-feature-card {
                padding: 25px;
                background: #f9f9f9;
                border-radius: 8px;
                border-left: 4px solid #667eea;
            }
            .almaseo-feature-title {
                font-size: 16px;
                font-weight: 600;
                color: #23282d;
                margin: 0 0 10px 0;
                display: flex;
                align-items: center;
            }
            .almaseo-feature-title .dashicons {
                color: #667eea;
                margin-right: 8px;
            }
            .almaseo-feature-description {
                font-size: 14px;
                color: #666;
                margin: 0;
                line-height: 1.6;
            }
            .almaseo-features-checklist {
                display: grid;
                grid-template-columns: repeat(2, 1fr);
                gap: 15px;
                margin: 30px 0;
                padding: 30px;
                background: #f7f8fc;
                border-radius: 8px;
            }
            .almaseo-feature-item {
                display: flex;
                align-items: flex-start;
                font-size: 15px;
                color: #555;
            }
            .almaseo-feature-item .dashicons {
                color: #46b450;
                margin-right: 10px;
                flex-shrink: 0;
                margin-top: 2px;
            }
            .almaseo-cta-section {
                text-align: center;
                padding: 40px 0 20px;
                border-top: 2px solid #f0f0f0;
                margin-top: 40px;
            }
            .almaseo-cta-buttons {
                display: flex;
                gap: 15px;
                justify-content: center;
                margin-top: 25px;
            }
            .almaseo-btn-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 15px 35px;
                font-size: 16px;
                border: none;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                transition: transform 0.2s;
            }
            .almaseo-btn-primary:hover {
                transform: translateY(-2px);
                color: white;
                text-decoration: none;
            }
            .almaseo-btn-secondary {
                background: white;
                color: #667eea;
                padding: 15px 35px;
                font-size: 16px;
                border: 2px solid #667eea;
                border-radius: 5px;
                cursor: pointer;
                text-decoration: none;
                display: inline-block;
                transition: all 0.2s;
            }
            .almaseo-btn-secondary:hover {
                background: #667eea;
                color: white;
                text-decoration: none;
            }
            .almaseo-ai-badge {
                display: inline-block;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 11px;
                font-weight: 600;
                text-transform: uppercase;
                margin-left: 8px;
            }
        </style>

        <div class="almaseo-welcome-container">
            <div class="almaseo-welcome-header">
                <h1 class="almaseo-welcome-title">🎯 Welcome to SEO Playground by AlmaSEO</h1>
                <p class="almaseo-welcome-subtitle">Your Alma-powered WordPress SEO optimization toolkit is ready to transform your content</p>
            </div>

            <div style="padding: 20px; background: #f0f8ff; border-radius: 8px; margin-bottom: 30px;">
                <p style="margin: 0; font-size: 16px; color: #0073aa;">
                    <strong>🚀 Getting Started:</strong> Connect to AlmaSEO to unlock AI-powered features that will help you create SEO-optimized content in minutes, not hours.
                </p>
            </div>

            <h2 style="font-size: 24px; margin-bottom: 25px; color: #23282d;">✨ Key Features</h2>

            <div class="almaseo-features-checklist">
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>AI Meta Titles & Descriptions</strong> - Generate optimized metadata instantly</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Focus Keyword Suggestions</strong> - AI-powered keyword recommendations</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Post Intelligence</strong> - AI analysis of your content quality</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Keyword Intelligence</strong> - Deep keyword insights and difficulty</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Schema Analyzer</strong> - Structured data optimization</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Meta Health Score</strong> - Real-time SEO scoring</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Search Console Keywords</strong> - Real GSC data integration</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Content Aging Monitor</strong> - Track and refresh old content</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>SEO Notes</strong> - Private notes for optimization strategy</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>AI Rewrite Assistant</strong> - Optimize existing content</span>
                </div>
            </div>

            <?php
            // Detect what other AlmaSEO/SEO plugins are active so Step 2 can be specific.
            $connector_active = function_exists( 'almaseo_detect_active_connector' )
                ? almaseo_detect_active_connector()
                : false;
            $seo_conflicts = function_exists( 'almaseo_detect_conflicting_seo_plugins' )
                ? almaseo_detect_conflicting_seo_plugins()
                : array();
            $plugins_page_url = admin_url( 'plugins.php' );
            ?>

            <div class="almaseo-features-grid">
                <div class="almaseo-feature-card">
                    <h3 class="almaseo-feature-title">
                        <span class="dashicons dashicons-admin-settings"></span>
                        Step 1: Connect to AlmaSEO
                    </h3>
                    <p class="almaseo-feature-description">
                        Click "Connect to AlmaSEO" below to link your site and enable all Alma-powered features. The connection process takes less than a minute.
                    </p>
                </div>

                <div class="almaseo-feature-card">
                    <h3 class="almaseo-feature-title">
                        <span class="dashicons dashicons-dismiss"></span>
                        Step 2: Deactivate Other Plugins
                    </h3>
                    <p class="almaseo-feature-description">
                        <?php if ( $connector_active && ! empty( $seo_conflicts ) ) : ?>
                            We detected the AlmaSEO Connector and another SEO plugin (<?php echo esc_html( implode( ', ', $seo_conflicts ) ); ?>). SEO Playground replaces both — deactivate them on your <a href="<?php echo esc_url( $plugins_page_url ); ?>">Plugins page</a> to avoid conflicts.
                        <?php elseif ( $connector_active ) : ?>
                            We detected the AlmaSEO Connector plugin. SEO Playground includes everything the Connector does plus a full SEO toolkit, so deactivate the Connector on your <a href="<?php echo esc_url( $plugins_page_url ); ?>">Plugins page</a>. Your connection settings are preserved.
                        <?php elseif ( ! empty( $seo_conflicts ) ) : ?>
                            We detected another SEO plugin (<?php echo esc_html( implode( ', ', $seo_conflicts ) ); ?>). To avoid conflicts, deactivate it on your <a href="<?php echo esc_url( $plugins_page_url ); ?>">Plugins page</a> — Step 4 below will help you bring your existing SEO data over first.
                        <?php else : ?>
                            If you have the AlmaSEO Connector plugin or another SEO plugin (Yoast, Rank Math, AIOSEO, etc.) active, deactivate it on your <a href="<?php echo esc_url( $plugins_page_url ); ?>">Plugins page</a>. SEO Playground replaces them. You can skip this step if neither is installed.
                        <?php endif; ?>
                    </p>
                </div>

                <div class="almaseo-feature-card">
                    <h3 class="almaseo-feature-title">
                        <span class="dashicons dashicons-admin-tools"></span>
                        Step 3: Run the Setup Wizard
                    </h3>
                    <p class="almaseo-feature-description">
                        Configure social profiles, search appearance templates, and your sitemap in a quick guided flow. <a href="<?php echo esc_url( admin_url( 'admin.php?page=almaseo-setup-wizard' ) ); ?>">Open the Setup Wizard</a>.
                    </p>
                </div>

                <div class="almaseo-feature-card">
                    <h3 class="almaseo-feature-title">
                        <span class="dashicons dashicons-migrate"></span>
                        Step 4: Import Your SEO Data
                    </h3>
                    <p class="almaseo-feature-description">
                        Replacing Yoast, Rank Math, or AIOSEO? Use the <a href="<?php echo esc_url( admin_url( 'admin.php?page=almaseo-import' ) ); ?>">Import &amp; Migrate</a> tool to bring your titles, descriptions, redirects, and term metadata across in 5 steps.
                    </p>
                </div>
            </div>

            <div class="almaseo-cta-section">
                <h2 style="font-size: 24px; margin-bottom: 10px;">Ready to supercharge your SEO?</h2>
                <p style="color: #666; font-size: 16px;">Choose an action to get started:</p>

                <div class="almaseo-cta-buttons">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=seo-playground-connection')); ?>" class="almaseo-btn-primary">
                        🔗 Connect to AlmaSEO
                    </a>
                </div>

                <p style="margin-top: 20px; color: #999; font-size: 14px;">
                    Need help? Visit our <a href="https://docs.almaseo.com/" target="_blank" rel="noopener">documentation</a> or <a href="https://webstuffguylabs.com/support/" target="_blank" rel="noopener">contact support</a>.
                </p>
            </div>
        </div>
    </div>
    <?php
}
} // end function_exists guard: almaseo_welcome_screen_page
