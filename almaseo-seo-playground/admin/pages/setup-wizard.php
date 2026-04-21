<?php
/**
 * AlmaSEO Setup Wizard — Admin Page Template
 *
 * Standalone multi-step wizard (minimal WP admin chrome).
 *
 * @package AlmaSEO
 * @since   8.2.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php esc_html_e( 'AlmaSEO Setup Wizard', 'almaseo-seo-playground' ); ?></title>
    <?php wp_print_styles( 'almaseo-setup-wizard' ); ?>
</head>
<body class="almaseo-wizard-body">

<div class="almaseo-wizard-wrap">

    <!-- Header -->
    <header class="almaseo-wizard-header">
        <div class="almaseo-wizard-logo">
            <svg width="28" height="28" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <rect width="24" height="24" rx="6" fill="#667eea"/>
                <path d="M7 17l5-10 5 10" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                <path d="M9 13h6" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
            </svg>
            <span><?php esc_html_e( 'AlmaSEO', 'almaseo-seo-playground' ); ?></span>
        </div>
        <a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-playground' ) ); ?>" class="almaseo-wizard-exit" title="<?php esc_attr_e( 'Exit wizard', 'almaseo-seo-playground' ); ?>">&times;</a>
    </header>

    <!-- Progress -->
    <nav class="almaseo-wizard-progress" aria-label="<?php esc_attr_e( 'Wizard progress', 'almaseo-seo-playground' ); ?>">
        <?php
        $steps = array(
            1 => __( 'Welcome', 'almaseo-seo-playground' ),
            2 => __( 'Social', 'almaseo-seo-playground' ),
            3 => __( 'Titles', 'almaseo-seo-playground' ),
            4 => __( 'Sitemap', 'almaseo-seo-playground' ),
            5 => __( 'Done', 'almaseo-seo-playground' ),
        );
        foreach ( $steps as $num => $label ) : ?>
            <div class="almaseo-wizard-step-indicator" data-step="<?php echo esc_attr((int) $num); ?>">
                <span class="almaseo-wizard-step-num"><?php echo intval($num); ?></span>
                <span class="almaseo-wizard-step-label"><?php echo esc_html( $label ); ?></span>
            </div>
        <?php endforeach; ?>
    </nav>

    <!-- Step panels -->
    <main class="almaseo-wizard-main">

        <!-- Step 1: Welcome -->
        <section class="almaseo-wizard-panel" data-step="1">
            <h2><?php esc_html_e( 'Welcome to SEO Playground', 'almaseo-seo-playground' ); ?></h2>
            <p class="almaseo-wizard-desc"><?php esc_html_e( "Let's configure a few essentials to get your site optimized. This takes about 2 minutes — you can skip any step.", 'almaseo-seo-playground' ); ?></p>
        </section>

        <!-- Step 2: Social Profiles -->
        <section class="almaseo-wizard-panel" data-step="2" style="display:none;">
            <h2><?php esc_html_e( 'Social Profiles', 'almaseo-seo-playground' ); ?></h2>
            <p class="almaseo-wizard-desc"><?php esc_html_e( 'Help search engines connect your site to your brand. These appear in Knowledge Graph results.', 'almaseo-seo-playground' ); ?></p>

            <div class="almaseo-wizard-form">
                <div class="almaseo-wizard-field">
                    <label for="wiz-org-name"><?php esc_html_e( 'Organization / Site Name', 'almaseo-seo-playground' ); ?></label>
                    <input type="text" id="wiz-org-name" name="org_name" placeholder="<?php esc_attr_e( 'e.g. Acme Corp', 'almaseo-seo-playground' ); ?>">
                </div>
                <div class="almaseo-wizard-field">
                    <label for="wiz-logo-url"><?php esc_html_e( 'Logo URL', 'almaseo-seo-playground' ); ?></label>
                    <input type="url" id="wiz-logo-url" name="logo_url" placeholder="https://example.com/logo.png">
                    <p class="almaseo-wizard-field-hint">
                        <?php esc_html_e( 'Paste your logo URL, or find it in your', 'almaseo-seo-playground' ); ?>
                        <a href="<?php echo esc_url( admin_url( 'upload.php' ) ); ?>" target="_blank"><?php esc_html_e( 'Media Library', 'almaseo-seo-playground' ); ?></a>.
                    </p>
                </div>
                <hr>
                <div class="almaseo-wizard-field">
                    <label for="wiz-facebook"><?php esc_html_e( 'Facebook', 'almaseo-seo-playground' ); ?></label>
                    <input type="url" id="wiz-facebook" name="facebook" placeholder="https://facebook.com/yourpage">
                </div>
                <div class="almaseo-wizard-field">
                    <label for="wiz-twitter"><?php esc_html_e( 'Twitter / X', 'almaseo-seo-playground' ); ?></label>
                    <input type="url" id="wiz-twitter" name="twitter" placeholder="https://x.com/yourhandle">
                </div>
                <div class="almaseo-wizard-field">
                    <label for="wiz-instagram"><?php esc_html_e( 'Instagram', 'almaseo-seo-playground' ); ?></label>
                    <input type="url" id="wiz-instagram" name="instagram" placeholder="https://instagram.com/yourprofile">
                </div>
                <div class="almaseo-wizard-field">
                    <label for="wiz-linkedin"><?php esc_html_e( 'LinkedIn', 'almaseo-seo-playground' ); ?></label>
                    <input type="url" id="wiz-linkedin" name="linkedin" placeholder="https://linkedin.com/company/yourcompany">
                </div>
                <div class="almaseo-wizard-field">
                    <label for="wiz-youtube"><?php esc_html_e( 'YouTube', 'almaseo-seo-playground' ); ?></label>
                    <input type="url" id="wiz-youtube" name="youtube" placeholder="https://youtube.com/@yourchannel">
                </div>
                <div class="almaseo-wizard-field">
                    <label for="wiz-pinterest"><?php esc_html_e( 'Pinterest', 'almaseo-seo-playground' ); ?></label>
                    <input type="url" id="wiz-pinterest" name="pinterest" placeholder="https://pinterest.com/yourprofile">
                </div>
                <div class="almaseo-wizard-field">
                    <label for="wiz-tiktok"><?php esc_html_e( 'TikTok', 'almaseo-seo-playground' ); ?></label>
                    <input type="url" id="wiz-tiktok" name="tiktok" placeholder="https://tiktok.com/@yourprofile">
                </div>
            </div>
        </section>

        <!-- Step 3: Search Appearance -->
        <section class="almaseo-wizard-panel" data-step="3" style="display:none;">
            <h2><?php esc_html_e( 'Search Appearance', 'almaseo-seo-playground' ); ?></h2>
            <p class="almaseo-wizard-desc"><?php esc_html_e( 'These templates control how your pages appear in Google and other search engines. The defaults below work well for most sites — feel free to leave them as-is or customize them.', 'almaseo-seo-playground' ); ?></p>

            <div class="almaseo-wizard-form">
                <div class="almaseo-wizard-field">
                    <label><?php esc_html_e( 'Title Separator', 'almaseo-seo-playground' ); ?></label>
                    <p class="almaseo-wizard-field-hint"><?php esc_html_e( 'The character used between your page title and site name.', 'almaseo-seo-playground' ); ?></p>
                    <div class="almaseo-wizard-separator-picker" id="wiz-separator-picker">
                        <!-- Populated by JS -->
                    </div>
                </div>

                <div class="almaseo-wizard-field">
                    <label for="wiz-homepage-title"><?php esc_html_e( 'Homepage Title', 'almaseo-seo-playground' ); ?></label>
                    <input type="text" id="wiz-homepage-title" name="homepage_title">
                    <p class="almaseo-wizard-field-hint"><?php esc_html_e( 'Tags like %%sitename%% and %%tagline%% are replaced automatically with your site info.', 'almaseo-seo-playground' ); ?></p>
                </div>

                <div class="almaseo-wizard-field">
                    <label for="wiz-homepage-desc"><?php esc_html_e( 'Homepage Meta Description', 'almaseo-seo-playground' ); ?></label>
                    <textarea id="wiz-homepage-desc" name="homepage_description" rows="2"></textarea>
                </div>

                <div class="almaseo-wizard-field">
                    <label for="wiz-post-title"><?php esc_html_e( 'Post Title Template', 'almaseo-seo-playground' ); ?></label>
                    <input type="text" id="wiz-post-title" name="post_title">
                </div>

                <div class="almaseo-wizard-field">
                    <label for="wiz-page-title"><?php esc_html_e( 'Page Title Template', 'almaseo-seo-playground' ); ?></label>
                    <input type="text" id="wiz-page-title" name="page_title">
                </div>
            </div>
        </section>

        <!-- Step 4: Sitemap -->
        <section class="almaseo-wizard-panel" data-step="4" style="display:none;">
            <h2><?php esc_html_e( 'XML Sitemap', 'almaseo-seo-playground' ); ?></h2>
            <p class="almaseo-wizard-desc"><?php esc_html_e( 'An XML sitemap helps search engines discover all your pages. We recommend keeping it enabled.', 'almaseo-seo-playground' ); ?></p>

            <div class="almaseo-wizard-form">
                <div class="almaseo-wizard-field">
                    <label class="almaseo-wizard-toggle-label">
                        <input type="checkbox" id="wiz-sitemap-enabled" name="sitemap_enabled" checked>
                        <span><?php esc_html_e( 'Enable XML Sitemap', 'almaseo-seo-playground' ); ?></span>
                    </label>
                </div>

                <div class="almaseo-wizard-field" id="wiz-sitemap-types-wrap">
                    <label><?php esc_html_e( 'Include in Sitemap', 'almaseo-seo-playground' ); ?></label>
                    <p class="almaseo-wizard-field-hint"><?php esc_html_e( 'Only check the content types that belong to your site. If you see items from third-party plugins that you don\'t recognize, leave them unchecked.', 'almaseo-seo-playground' ); ?></p>
                    <div class="almaseo-wizard-checkboxes" id="wiz-sitemap-types">
                        <!-- Populated by JS from postTypes data -->
                    </div>
                </div>
            </div>
        </section>

        <!-- Step 5: Done -->
        <section class="almaseo-wizard-panel" data-step="5" style="display:none;">
            <div class="almaseo-wizard-done">
                <div class="almaseo-wizard-done-icon">&#10003;</div>
                <h2><?php esc_html_e( 'You\'re All Set!', 'almaseo-seo-playground' ); ?></h2>
                <p class="almaseo-wizard-desc"><?php esc_html_e( 'AlmaSEO SEO Playground is configured and ready to optimize your site.', 'almaseo-seo-playground' ); ?></p>

                <?php
                // Detect post-onboarding actions needed.
                $connector_active = function_exists( 'almaseo_detect_active_connector' ) ? almaseo_detect_active_connector() : false;
                $seo_conflicts    = function_exists( 'almaseo_detect_conflicting_seo_plugins' ) ? almaseo_detect_conflicting_seo_plugins() : array();

                if ( $connector_active || ! empty( $seo_conflicts ) ) : ?>
                    <div class="almaseo-wizard-done-actions" style="margin: 25px 0; text-align: left; max-width: 520px; margin-left: auto; margin-right: auto;">
                        <h3 style="font-size: 15px; color: #23282d; margin: 0 0 15px 0;"><?php esc_html_e( 'Before you go, a couple of things to take care of:', 'almaseo-seo-playground' ); ?></h3>

                        <?php if ( $connector_active ) :
                            $deactivate_url = wp_nonce_url(
                                admin_url( 'admin-post.php?action=almaseo_deactivate_connector' ),
                                'almaseo_deactivate_connector'
                            );
                        ?>
                            <div style="background: #f0f6ff; border-radius: 6px; padding: 15px; margin-bottom: 12px;">
                                <p style="margin: 0 0 10px 0; color: #1d2327;">
                                    <strong><?php esc_html_e( 'Deactivate the Connector Plugin', 'almaseo-seo-playground' ); ?></strong><br>
                                    <?php esc_html_e( 'SEO Playground replaces the Connector — it includes everything the Connector does plus a full SEO toolkit. Your connection settings will be preserved.', 'almaseo-seo-playground' ); ?>
                                </p>
                                <a href="<?php echo esc_url( $deactivate_url ); ?>" class="almaseo-wizard-btn almaseo-wizard-btn-secondary" style="font-size: 13px; padding: 8px 16px;">
                                    <?php esc_html_e( 'Deactivate Connector', 'almaseo-seo-playground' ); ?>
                                </a>
                            </div>
                        <?php endif; ?>

                        <?php if ( ! empty( $seo_conflicts ) ) :
                            $plugin_names = implode( ', ', $seo_conflicts );
                        ?>
                            <div style="background: #fef8ee; border-radius: 6px; padding: 15px; margin-bottom: 12px;">
                                <p style="margin: 0 0 10px 0; color: #1d2327;">
                                    <strong><?php esc_html_e( 'Import Your SEO Data', 'almaseo-seo-playground' ); ?></strong><br>
                                    <?php printf(
                                        /* translators: %s: name of detected SEO plugin */
                                        esc_html__( 'We detected %s. You can import your existing titles, descriptions, and keywords into AlmaSEO before deactivating it.', 'almaseo-seo-playground' ),
                                        '<strong>' . esc_html( $plugin_names ) . '</strong>'
                                    ); ?>
                                </p>
                                <a href="<?php echo esc_url( admin_url( 'admin.php?page=almaseo-import' ) ); ?>" class="almaseo-wizard-btn almaseo-wizard-btn-secondary" style="font-size: 13px; padding: 8px 16px;">
                                    <?php esc_html_e( 'Go to Import', 'almaseo-seo-playground' ); ?>
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <div class="almaseo-wizard-done-links">
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=seo-playground' ) ); ?>" class="almaseo-wizard-btn almaseo-wizard-btn-primary" id="wiz-go-dashboard">
                        <?php esc_html_e( 'Go to Dashboard', 'almaseo-seo-playground' ); ?>
                    </a>
                </div>
            </div>
        </section>

    </main>

    <!-- Navigation -->
    <footer class="almaseo-wizard-footer" id="wiz-footer">
        <div class="almaseo-wizard-footer-left">
            <button type="button" class="almaseo-wizard-btn almaseo-wizard-btn-secondary" id="wiz-prev" style="display:none;">
                &larr; <?php esc_html_e( 'Previous', 'almaseo-seo-playground' ); ?>
            </button>
        </div>
        <div class="almaseo-wizard-footer-right">
            <button type="button" class="almaseo-wizard-btn-link" id="wiz-skip">
                <?php esc_html_e( 'Skip this step', 'almaseo-seo-playground' ); ?>
            </button>
            <button type="button" class="almaseo-wizard-btn almaseo-wizard-btn-primary" id="wiz-next">
                <?php esc_html_e( 'Save & Continue', 'almaseo-seo-playground' ); ?> &rarr;
            </button>
        </div>
    </footer>

    <!-- Status toast -->
    <div class="almaseo-wizard-toast" id="wiz-toast" aria-live="polite"></div>

</div><!-- .almaseo-wizard-wrap -->

<?php wp_print_scripts( 'almaseo-setup-wizard' ); ?>
</body>
</html>
