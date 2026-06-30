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
            <?php $is_connected = function_exists( 'seo_playground_is_alma_connected' ) && seo_playground_is_alma_connected(); ?>

            <div class="almaseo-welcome-header">
                <h1 class="almaseo-welcome-title">🎯 Welcome to SEO Playground by AlmaSEO</h1>
                <p class="almaseo-welcome-subtitle">Your complete WordPress SEO toolkit is ready &mdash; no account required.</p>
            </div>

            <?php if ( $is_connected ) : ?>
            <div style="padding: 20px; background: #ecf9f0; border-left: 4px solid #46b450; border-radius: 8px; margin-bottom: 30px;">
                <p style="margin: 0; font-size: 16px; color: #1e7e34;">
                    <strong>✅ You're connected to AlmaSEO.</strong> Everything below is active, including the AlmaSEO-powered enhancements. Head to your SEO Overview whenever you're ready.
                </p>
            </div>
            <?php else : ?>
            <div style="padding: 20px; background: #ecf9f0; border-left: 4px solid #46b450; border-radius: 8px; margin-bottom: 30px;">
                <p style="margin: 0; font-size: 16px; color: #1e7e34;">
                    <strong>✅ You're all set &mdash; for free.</strong> Every core SEO feature below works right now, with no account, sign-up, or connection. Connecting a free AlmaSEO account later is entirely optional &mdash; it just layers on a few cloud-powered extras.
                </p>
            </div>
            <?php endif; ?>

            <h2 style="font-size: 24px; margin-bottom: 25px; color: #23282d;">
                ✨ What you can do right now
                <span style="display:inline-block; background:#46b450; color:#fff; font-size:12px; font-weight:600; padding:3px 10px; border-radius:12px; vertical-align:middle; margin-left:8px; text-transform:uppercase;">Free</span>
            </h2>

            <div class="almaseo-features-checklist">
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Meta Titles &amp; Descriptions</strong> - write and preview them with a live Google SERP snippet</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Focus Keyword Analysis</strong> - readability, headline and meta health scoring</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>XML Sitemaps</strong> - posts, pages, images, plus video &amp; news</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Schema Markup</strong> - Article, FAQ, How-To, LocalBusiness, breadcrumbs</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>301 Redirects &amp; 404 Monitoring</strong> - keep your links healthy</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Robots.txt &amp; LLMs.txt Editors</strong> - control crawlers and AI bots</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Bulk Metadata Editor</strong> - edit titles and descriptions across your site</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Import from Yoast, Rank Math &amp; AIOSEO</strong> - titles, meta, redirects, term data</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Internal Linking &amp; Cornerstone Content</strong> - structure your site for search</span>
                </div>
                <div class="almaseo-feature-item">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <span><strong>Keyword Suggestions</strong> - real ideas straight from Google Suggest</span>
                </div>
            </div>

            <?php if ( ! $is_connected ) : ?>
            <h2 style="font-size: 24px; margin: 40px 0 20px; color: #23282d;">
                🔗 Want more later?
                <span style="display:inline-block; background:#667eea; color:#fff; font-size:12px; font-weight:600; padding:3px 10px; border-radius:12px; vertical-align:middle; margin-left:8px; text-transform:uppercase;">Optional</span>
            </h2>
            <div style="padding: 25px 30px; background: #f7f8fc; border-left: 4px solid #667eea; border-radius: 8px; margin-bottom: 10px;">
                <p style="margin: 0 0 18px; font-size: 14px; color: #555;">Connecting a free AlmaSEO account is optional and never required &mdash; it simply adds cloud-powered extras on top of everything above:</p>
                <div class="almaseo-features-checklist" style="background: transparent; padding: 0; margin: 0;">
                    <div class="almaseo-feature-item">
                        <span class="dashicons dashicons-cloud" style="color:#667eea;"></span>
                        <span><strong>Real Search Console Data</strong> - actual keywords, clicks &amp; impressions per page</span>
                    </div>
                    <div class="almaseo-feature-item">
                        <span class="dashicons dashicons-cloud" style="color:#667eea;"></span>
                        <span><strong>AlmaSEO-powered Keyword Research</strong> - real search volume &amp; difficulty</span>
                    </div>
                    <div class="almaseo-feature-item">
                        <span class="dashicons dashicons-cloud" style="color:#667eea;"></span>
                        <span><strong>Content Freshness Intelligence</strong> - spot and refresh aging content</span>
                    </div>
                    <div class="almaseo-feature-item">
                        <span class="dashicons dashicons-cloud" style="color:#667eea;"></span>
                        <span><strong>Multi-Site Dashboard</strong> - manage every site's SEO in one place</span>
                    </div>
                </div>
            </div>
            <?php endif; ?>

            <?php
            // Detect what other AlmaSEO/SEO plugins are active so the deactivate step can be specific.
            $connector_active = function_exists( 'almaseo_detect_active_connector' )
                ? almaseo_detect_active_connector()
                : false;
            $seo_conflicts = function_exists( 'almaseo_detect_conflicting_seo_plugins' )
                ? almaseo_detect_conflicting_seo_plugins()
                : array();
            $plugins_page_url = admin_url( 'plugins.php' );
            ?>

            <h2 style="font-size: 24px; margin: 40px 0 20px; color: #23282d;">🚀 Get started in a few quick steps</h2>

            <div class="almaseo-features-grid">
                <div class="almaseo-feature-card">
                    <h3 class="almaseo-feature-title">
                        <span class="dashicons dashicons-admin-tools"></span>
                        Step 1: Run the Setup Wizard
                    </h3>
                    <p class="almaseo-feature-description">
                        Configure your social profiles, title templates, and sitemap in a quick guided flow &mdash; about two minutes, and every step is skippable. <a href="<?php echo esc_url( admin_url( 'admin.php?page=almaseo-setup-wizard' ) ); ?>">Open the Setup Wizard</a>.
                    </p>
                </div>

                <div class="almaseo-feature-card">
                    <h3 class="almaseo-feature-title">
                        <span class="dashicons dashicons-migrate"></span>
                        Step 2: Import Your SEO Data
                    </h3>
                    <p class="almaseo-feature-description">
                        Coming from Yoast, Rank Math, or AIOSEO? Use the <a href="<?php echo esc_url( admin_url( 'admin.php?page=almaseo-import' ) ); ?>">Import &amp; Migrate</a> tool to bring your titles, descriptions, redirects, and term metadata across in 5 steps &mdash; before you switch the old plugin off.
                    </p>
                </div>

                <div class="almaseo-feature-card">
                    <h3 class="almaseo-feature-title">
                        <span class="dashicons dashicons-dismiss"></span>
                        Step 3: Deactivate Other SEO Plugins
                    </h3>
                    <p class="almaseo-feature-description">
                        <?php if ( $connector_active && ! empty( $seo_conflicts ) ) : ?>
                            We detected the AlmaSEO Connector and another SEO plugin (<?php echo esc_html( implode( ', ', $seo_conflicts ) ); ?>). SEO Playground replaces both &mdash; deactivate them on your <a href="<?php echo esc_url( $plugins_page_url ); ?>">Plugins page</a> to avoid conflicts.
                        <?php elseif ( $connector_active ) : ?>
                            We detected the AlmaSEO Connector plugin. SEO Playground includes everything the Connector does plus a full SEO toolkit, so deactivate the Connector on your <a href="<?php echo esc_url( $plugins_page_url ); ?>">Plugins page</a>. Your connection settings are preserved.
                        <?php elseif ( ! empty( $seo_conflicts ) ) : ?>
                            We detected another SEO plugin (<?php echo esc_html( implode( ', ', $seo_conflicts ) ); ?>). Run the Import step above first, then deactivate it on your <a href="<?php echo esc_url( $plugins_page_url ); ?>">Plugins page</a> to avoid duplicate meta tags.
                        <?php else : ?>
                            Running another SEO plugin (Yoast, Rank Math, AIOSEO, etc.) or the AlmaSEO Connector? Deactivate it on your <a href="<?php echo esc_url( $plugins_page_url ); ?>">Plugins page</a> &mdash; SEO Playground replaces them. You can skip this step if neither is installed.
                        <?php endif; ?>
                    </p>
                </div>

                <?php if ( ! $is_connected ) : ?>
                <div class="almaseo-feature-card">
                    <h3 class="almaseo-feature-title">
                        <span class="dashicons dashicons-cloud"></span>
                        Optional: Connect to AlmaSEO
                    </h3>
                    <p class="almaseo-feature-description">
                        Only if you want the cloud extras above. Link a free AlmaSEO account to add Search Console data, keyword research, and content-freshness intelligence. <a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-playground-connection' ) ); ?>">Connect AlmaSEO</a>.
                    </p>
                </div>
                <?php endif; ?>
            </div>

            <div class="almaseo-cta-section">
                <?php if ( $is_connected ) : ?>
                <h2 style="font-size: 24px; margin-bottom: 10px;">You're ready to go</h2>
                <p style="color: #666; font-size: 16px;">Jump into your dashboard or fine-tune your setup:</p>
                <div class="almaseo-cta-buttons">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-playground' ) ); ?>" class="almaseo-btn-primary">
                        📊 Go to SEO Overview
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=almaseo-setup-wizard' ) ); ?>" class="almaseo-btn-secondary">
                        ⚙️ Run the Setup Wizard
                    </a>
                </div>
                <?php else : ?>
                <h2 style="font-size: 24px; margin-bottom: 10px;">Ready to set up your SEO?</h2>
                <p style="color: #666; font-size: 16px;">Start with the free setup &mdash; no account needed.</p>
                <div class="almaseo-cta-buttons">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=almaseo-setup-wizard' ) ); ?>" class="almaseo-btn-primary">
                        ⚙️ Set Up Your SEO
                    </a>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-playground-connection' ) ); ?>" class="almaseo-btn-secondary">
                        🔗 Connect AlmaSEO (optional)
                    </a>
                </div>
                <?php endif; ?>

                <p style="margin-top: 20px; color: #999; font-size: 14px;">
                    Need help? Visit our <a href="https://docs.almaseo.com/" target="_blank" rel="noopener">documentation</a> or <a href="https://webstuffguylabs.com/support/" target="_blank" rel="noopener">contact support</a>.
                </p>
            </div>
        </div>
    </div>
    <?php
}
} // end function_exists guard: almaseo_welcome_screen_page
