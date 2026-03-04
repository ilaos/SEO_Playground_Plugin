<?php
/**
 * AlmaSEO Import / Migration Admin Page
 *
 * @package AlmaSEO
 * @since   8.1.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap almaseo-import-wrap">
    <h1><?php esc_html_e( 'Import SEO Data', 'almaseo' ); ?></h1>
    <p class="description">
        <?php esc_html_e( 'Import SEO titles, descriptions, focus keywords, robots settings, and social meta from other SEO plugins. Your existing AlmaSEO data will not be overwritten unless you choose to.', 'almaseo' ); ?>
    </p>

    <!-- Detection Results -->
    <div id="almaseo-import-sources" class="almaseo-import-sources">
        <p class="almaseo-import-loading">
            <span class="spinner is-active" style="float:none;"></span>
            <?php esc_html_e( 'Detecting available SEO data...', 'almaseo' ); ?>
        </p>
    </div>

    <!-- Import Controls (shown after detection) -->
    <div id="almaseo-import-controls" class="almaseo-import-controls" style="display:none;">
        <h2><?php esc_html_e( 'Import Options', 'almaseo' ); ?></h2>

        <table class="form-table">
            <tr>
                <th><?php esc_html_e( 'Source', 'almaseo' ); ?></th>
                <td>
                    <select id="almaseo-import-source">
                        <option value=""><?php esc_html_e( '-- Select source --', 'almaseo' ); ?></option>
                    </select>
                </td>
            </tr>
            <tr>
                <th><?php esc_html_e( 'Overwrite Existing', 'almaseo' ); ?></th>
                <td>
                    <label>
                        <input type="checkbox" id="almaseo-import-overwrite" />
                        <?php esc_html_e( 'Overwrite existing AlmaSEO data (if unchecked, posts with existing data are skipped)', 'almaseo' ); ?>
                    </label>
                </td>
            </tr>
        </table>

        <!-- Preview -->
        <div id="almaseo-import-preview" style="display:none;">
            <h3><?php esc_html_e( 'Preview (first 5 records)', 'almaseo' ); ?></h3>
            <table class="widefat striped" id="almaseo-import-preview-table">
                <thead>
                    <tr>
                        <th><?php esc_html_e( 'Post', 'almaseo' ); ?></th>
                        <th><?php esc_html_e( 'Source Title', 'almaseo' ); ?></th>
                        <th><?php esc_html_e( 'Current AlmaSEO Title', 'almaseo' ); ?></th>
                        <th><?php esc_html_e( 'Status', 'almaseo' ); ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>

        <div class="almaseo-import-actions">
            <button type="button" class="button" id="almaseo-import-preview-btn">
                <?php esc_html_e( 'Preview', 'almaseo' ); ?>
            </button>
            <button type="button" class="button button-primary" id="almaseo-import-start-btn" disabled>
                <?php esc_html_e( 'Start Import', 'almaseo' ); ?>
            </button>
        </div>
    </div>

    <!-- Progress -->
    <div id="almaseo-import-progress" class="almaseo-import-progress" style="display:none;">
        <h2><?php esc_html_e( 'Import Progress', 'almaseo' ); ?></h2>
        <div class="almaseo-import-progress-bar-wrap">
            <div class="almaseo-import-progress-bar" id="almaseo-import-bar" style="width:0%"></div>
        </div>
        <p id="almaseo-import-status"></p>
        <div id="almaseo-import-stats" class="almaseo-import-stats"></div>
    </div>
</div>
