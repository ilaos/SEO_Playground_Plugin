<?php
/**
 * AlmaSEO Import / Migration Admin Page
 *
 * Full migration wizard: post meta, taxonomy meta, global settings,
 * redirects, and post-import verification.
 *
 * @package AlmaSEO
 * @since   8.1.0
 * @updated 8.7.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap almaseo-import-wrap">
    <h1><?php esc_html_e( 'Import & Migrate SEO Data', 'almaseo-seo-playground' ); ?></h1>
    <p class="description">
        <?php esc_html_e( 'Migrate SEO data from your previous plugin into AlmaSEO. Each step scans your database for data stored by the detected plugin and copies it into AlmaSEO format. Only plugins with data on this site will appear below.', 'almaseo-seo-playground' ); ?>
    </p>
    <div class="almaseo-import-supported-note">
        <strong><?php esc_html_e( 'Currently supported plugins:', 'almaseo-seo-playground' ); ?></strong>
        <?php esc_html_e( 'Yoast SEO, Rank Math, and All in One SEO (AIOSEO). Support for additional plugins (SEOPress, The SEO Framework, and others) is coming soon.', 'almaseo-seo-playground' ); ?>
    </div>

    <!-- ============================================================
         Step 1: Post Meta (existing)
         ============================================================ -->
    <div class="almaseo-import-section" id="almaseo-import-section-posts">
        <h2>
            <span class="almaseo-import-step-num">1</span>
            <?php esc_html_e( 'Post Meta (Titles, Descriptions, Keywords, Social, Robots)', 'almaseo-seo-playground' ); ?>
        </h2>
        <p class="description">
            <?php esc_html_e( 'Import the custom SEO titles, meta descriptions, focus keywords, Open Graph data, and robots directives that your previous SEO plugin stored on each post and page. This is the core migration step.', 'almaseo-seo-playground' ); ?>
        </p>

        <div id="almaseo-import-sources" class="almaseo-import-sources">
            <p class="almaseo-import-loading">
                <span class="spinner is-active" style="float:none;"></span>
                <?php esc_html_e( 'Detecting available SEO data...', 'almaseo-seo-playground' ); ?>
            </p>
        </div>

        <div id="almaseo-import-controls" class="almaseo-import-controls" style="display:none;">
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Source', 'almaseo-seo-playground' ); ?></th>
                    <td>
                        <select id="almaseo-import-source">
                            <option value=""><?php esc_html_e( '-- Select source --', 'almaseo-seo-playground' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Overwrite Existing', 'almaseo-seo-playground' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" id="almaseo-import-overwrite" />
                            <?php esc_html_e( 'Overwrite existing AlmaSEO data (if unchecked, posts with existing data are skipped)', 'almaseo-seo-playground' ); ?>
                        </label>
                        <p class="description" style="color:#b32d2e; margin-top:4px;">
                            <?php esc_html_e( 'Re-importing? Check this box to replace previously imported data with clean, resolved values.', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
            </table>

            <!-- Preview -->
            <div id="almaseo-import-preview" style="display:none;">
                <h3><?php esc_html_e( 'Preview (first 5 records)', 'almaseo-seo-playground' ); ?></h3>
                <table class="widefat striped" id="almaseo-import-preview-table">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Post', 'almaseo-seo-playground' ); ?></th>
                            <th><?php esc_html_e( 'Source Title', 'almaseo-seo-playground' ); ?></th>
                            <th><?php esc_html_e( 'Current AlmaSEO Title', 'almaseo-seo-playground' ); ?></th>
                            <th><?php esc_html_e( 'Status', 'almaseo-seo-playground' ); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>

            <div class="almaseo-import-actions">
                <button type="button" class="button" id="almaseo-import-preview-btn">
                    <?php esc_html_e( 'Preview', 'almaseo-seo-playground' ); ?>
                </button>
                <button type="button" class="button button-primary" id="almaseo-import-start-btn" disabled>
                    <?php esc_html_e( 'Start Import', 'almaseo-seo-playground' ); ?>
                </button>
            </div>
        </div>

        <div id="almaseo-import-progress" class="almaseo-import-progress" style="display:none;">
            <div class="almaseo-import-progress-bar-wrap">
                <div class="almaseo-import-progress-bar" id="almaseo-import-bar" style="width:0%"></div>
            </div>
            <p id="almaseo-import-status"></p>
            <div id="almaseo-import-stats" class="almaseo-import-stats"></div>
        </div>
    </div>

    <!-- ============================================================
         Step 2: Taxonomy Term Meta
         ============================================================ -->
    <div class="almaseo-import-section" id="almaseo-import-section-terms">
        <h2>
            <span class="almaseo-import-step-num">2</span>
            <?php esc_html_e( 'Taxonomy Term Meta (Categories, Tags)', 'almaseo-seo-playground' ); ?>
        </h2>
        <p class="description">
            <?php esc_html_e( 'Import custom SEO titles and descriptions that were manually set on your categories, tags, and custom taxonomy terms inside the previous SEO plugin. This only covers terms where you (or the plugin) explicitly added SEO metadata — your WordPress categories and tags themselves are not affected.', 'almaseo-seo-playground' ); ?>
        </p>

        <div id="almaseo-term-sources" class="almaseo-import-sources">
            <p class="almaseo-import-loading">
                <span class="spinner is-active" style="float:none;"></span>
                <?php esc_html_e( 'Detecting taxonomy term data...', 'almaseo-seo-playground' ); ?>
            </p>
        </div>

        <div id="almaseo-term-controls" style="display:none;">
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Source', 'almaseo-seo-playground' ); ?></th>
                    <td>
                        <select id="almaseo-term-source">
                            <option value=""><?php esc_html_e( '-- Select source --', 'almaseo-seo-playground' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Overwrite Existing', 'almaseo-seo-playground' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" id="almaseo-term-overwrite" />
                            <?php esc_html_e( 'Overwrite existing AlmaSEO term data', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
            <div class="almaseo-import-actions">
                <button type="button" class="button button-primary" id="almaseo-term-start-btn" disabled>
                    <?php esc_html_e( 'Import Term Meta', 'almaseo-seo-playground' ); ?>
                </button>
            </div>
        </div>

        <div id="almaseo-term-progress" class="almaseo-import-progress" style="display:none;">
            <div class="almaseo-import-progress-bar-wrap">
                <div class="almaseo-import-progress-bar" id="almaseo-term-bar" style="width:0%"></div>
            </div>
            <p id="almaseo-term-status"></p>
        </div>
    </div>

    <!-- ============================================================
         Step 3: Global Settings (Search Appearance)
         ============================================================ -->
    <div class="almaseo-import-section" id="almaseo-import-section-settings">
        <h2>
            <span class="almaseo-import-step-num">3</span>
            <?php esc_html_e( 'Global Settings (Title Templates, Separator, Noindex Rules)', 'almaseo-seo-playground' ); ?>
        </h2>
        <p class="description">
            <?php esc_html_e( 'Import search appearance templates for your homepage, post types, taxonomies, and archives. Template variables are automatically converted to AlmaSEO format.', 'almaseo-seo-playground' ); ?>
        </p>

        <div id="almaseo-settings-sources" class="almaseo-import-sources">
            <p class="almaseo-import-loading">
                <span class="spinner is-active" style="float:none;"></span>
                <?php esc_html_e( 'Detecting global settings...', 'almaseo-seo-playground' ); ?>
            </p>
        </div>

        <div id="almaseo-settings-controls" style="display:none;">
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Source', 'almaseo-seo-playground' ); ?></th>
                    <td>
                        <select id="almaseo-settings-source">
                            <option value=""><?php esc_html_e( '-- Select source --', 'almaseo-seo-playground' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Overwrite Existing', 'almaseo-seo-playground' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" id="almaseo-settings-overwrite" />
                            <?php esc_html_e( 'Overwrite settings you have already customized in AlmaSEO', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
            <div class="almaseo-import-actions">
                <button type="button" class="button button-primary" id="almaseo-settings-start-btn" disabled>
                    <?php esc_html_e( 'Import Settings', 'almaseo-seo-playground' ); ?>
                </button>
            </div>
        </div>

        <div id="almaseo-settings-result" style="display:none;"></div>
    </div>

    <!-- ============================================================
         Step 4: Redirects
         ============================================================ -->
    <div class="almaseo-import-section" id="almaseo-import-section-redirects">
        <h2>
            <span class="almaseo-import-step-num">4</span>
            <?php esc_html_e( 'Redirects', 'almaseo-seo-playground' ); ?>
        </h2>
        <p class="description">
            <?php esc_html_e( 'Import redirect rules (301, 302, etc.) stored by the previous plugin. Only shown if the detected plugin has redirect data in its database tables.', 'almaseo-seo-playground' ); ?>
        </p>

        <div id="almaseo-redirects-sources" class="almaseo-import-sources">
            <p class="almaseo-import-loading">
                <span class="spinner is-active" style="float:none;"></span>
                <?php esc_html_e( 'Detecting redirect data...', 'almaseo-seo-playground' ); ?>
            </p>
        </div>

        <div id="almaseo-redirects-controls" style="display:none;">
            <table class="form-table">
                <tr>
                    <th><?php esc_html_e( 'Source', 'almaseo-seo-playground' ); ?></th>
                    <td>
                        <select id="almaseo-redirects-source">
                            <option value=""><?php esc_html_e( '-- Select source --', 'almaseo-seo-playground' ); ?></option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><?php esc_html_e( 'Overwrite Existing', 'almaseo-seo-playground' ); ?></th>
                    <td>
                        <label>
                            <input type="checkbox" id="almaseo-redirects-overwrite" />
                            <?php esc_html_e( 'Overwrite redirects with matching source URLs', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
            <div class="almaseo-import-actions">
                <button type="button" class="button button-primary" id="almaseo-redirects-start-btn" disabled>
                    <?php esc_html_e( 'Import Redirects', 'almaseo-seo-playground' ); ?>
                </button>
            </div>
        </div>

        <div id="almaseo-redirects-progress" class="almaseo-import-progress" style="display:none;">
            <div class="almaseo-import-progress-bar-wrap">
                <div class="almaseo-import-progress-bar" id="almaseo-redirects-bar" style="width:0%"></div>
            </div>
            <p id="almaseo-redirects-status"></p>
        </div>
    </div>

    <!-- ============================================================
         Step 5: Verification Report
         ============================================================ -->
    <div class="almaseo-import-section" id="almaseo-import-section-verify">
        <h2>
            <span class="almaseo-import-step-num">5</span>
            <?php esc_html_e( 'Migration Verification', 'almaseo-seo-playground' ); ?>
        </h2>
        <p class="description">
            <?php esc_html_e( 'Scan your imported data for potential issues: unresolved template variables, missing descriptions, and duplicate titles.', 'almaseo-seo-playground' ); ?>
        </p>

        <div class="almaseo-import-actions">
            <button type="button" class="button button-primary" id="almaseo-verify-btn">
                <?php esc_html_e( 'Run Verification', 'almaseo-seo-playground' ); ?>
            </button>
        </div>

        <div id="almaseo-verify-loading" style="display:none;">
            <span class="spinner is-active" style="float:none;"></span>
            <?php esc_html_e( 'Scanning imported data...', 'almaseo-seo-playground' ); ?>
        </div>

        <div id="almaseo-verify-report" style="display:none;">
            <!-- Populated by JS -->
        </div>
    </div>
</div>
