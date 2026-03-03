<?php
/**
 * Featured Snippet Targeting – Admin Page Template
 *
 * Displays snippet targeting opportunities, draft editing,
 * and application controls.
 *
 * @package AlmaSEO
 * @since   7.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}
?>
<div class="wrap almaseo-st-wrap">

    <h1 class="almaseo-st-title"><?php esc_html_e( 'Featured Snippet Targets', 'almaseo' ); ?></h1>

    <!-- Feature Intro -->
    <div class="almaseo-st-intro">
        <p class="almaseo-st-intro-lead">
            <?php esc_html_e( 'A "featured snippet" is the answer box Google shows at the very top of search results (position zero). If your page already ranks on page 1 for a query but someone else holds the snippet, you have a real shot at winning it — you just need content formatted the way Google expects.', 'almaseo' ); ?>
        </p>
        <p class="almaseo-st-intro-lead">
            <?php esc_html_e( 'This tool helps you target those opportunities: draft snippet-optimized content in the right format (a concise paragraph, a numbered list, a comparison table, or a clear definition), then insert it into your post with one click.', 'almaseo' ); ?>
        </p>

        <div class="almaseo-st-connection-notice">
            <span class="dashicons dashicons-warning"></span>
            <div>
                <strong><?php esc_html_e( 'Connection Required', 'almaseo' ); ?></strong>
                <p><?php esc_html_e( 'Snippet opportunities are identified by the AlmaSEO dashboard using your Google Search Console data. Your site must be connected to AlmaSEO and your Search Console property must be linked in the dashboard for opportunities to appear here. Without this connection, the table below will be empty.', 'almaseo' ); ?></p>
                <?php
                $is_connected = (bool) get_option( 'almaseo_app_password', '' );
                if ( ! $is_connected ) : ?>
                    <p class="almaseo-st-connect-action">
                        <a href="<?php echo esc_url( admin_url( 'admin.php?page=almaseo-settings' ) ); ?>" class="button button-primary button-small">
                            <?php esc_html_e( 'Connect to AlmaSEO', 'almaseo' ); ?>
                        </a>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div class="almaseo-st-how-it-works">
            <h3><?php esc_html_e( 'How to use it', 'almaseo' ); ?></h3>
            <ol>
                <li><?php esc_html_e( 'The AlmaSEO dashboard analyzes your Search Console data and identifies queries where you rank on page 1 but don\'t hold the featured snippet. These appear as "Opportunities" below.', 'almaseo' ); ?></li>
                <li><?php esc_html_e( 'Click "Draft" on an opportunity to write snippet-optimized content. Each opportunity has a recommended format (paragraph, list, table, or definition) based on how Google displays the current snippet for that query.', 'almaseo' ); ?></li>
                <li><?php esc_html_e( 'When your draft is ready, click "Approve" then "Apply" to insert it into your post. A WordPress revision is saved automatically, and you can click "Undo" at any time to remove the snippet content cleanly.', 'almaseo' ); ?></li>
            </ol>
        </div>

        <p class="almaseo-st-intro-note">
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e( 'Your original content is never overwritten. Snippet content is inserted using invisible markers, so the Undo button can remove it cleanly without affecting the rest of your post.', 'almaseo' ); ?>
        </p>
    </div>

    <!-- Summary Cards -->
    <div class="almaseo-st-stats" id="almaseo-st-stats">
        <div class="almaseo-st-stat-card almaseo-st-stat-opportunities">
            <span class="dashicons dashicons-lightbulb"></span>
            <div class="almaseo-st-stat-value" id="almaseo-st-opportunities">—</div>
            <div class="almaseo-st-stat-label"><?php esc_html_e( 'Opportunities', 'almaseo' ); ?></div>
        </div>
        <div class="almaseo-st-stat-card almaseo-st-stat-drafts">
            <span class="dashicons dashicons-edit"></span>
            <div class="almaseo-st-stat-value" id="almaseo-st-drafts">—</div>
            <div class="almaseo-st-stat-label"><?php esc_html_e( 'Drafts', 'almaseo' ); ?></div>
        </div>
        <div class="almaseo-st-stat-card almaseo-st-stat-applied">
            <span class="dashicons dashicons-yes-alt"></span>
            <div class="almaseo-st-stat-value" id="almaseo-st-applied">—</div>
            <div class="almaseo-st-stat-label"><?php esc_html_e( 'Applied', 'almaseo' ); ?></div>
        </div>
        <div class="almaseo-st-stat-card almaseo-st-stat-won">
            <span class="dashicons dashicons-star-filled"></span>
            <div class="almaseo-st-stat-value" id="almaseo-st-won">—</div>
            <div class="almaseo-st-stat-label"><?php esc_html_e( 'Won', 'almaseo' ); ?></div>
        </div>
    </div>

    <!-- Action Bar -->
    <div class="almaseo-st-actions">
        <select id="almaseo-st-status-filter" class="almaseo-st-select">
            <option value=""><?php esc_html_e( 'All statuses', 'almaseo' ); ?></option>
            <option value="opportunity"><?php esc_html_e( 'Opportunity', 'almaseo' ); ?></option>
            <option value="draft"><?php esc_html_e( 'Draft', 'almaseo' ); ?></option>
            <option value="approved"><?php esc_html_e( 'Approved', 'almaseo' ); ?></option>
            <option value="applied"><?php esc_html_e( 'Applied', 'almaseo' ); ?></option>
            <option value="won"><?php esc_html_e( 'Won', 'almaseo' ); ?></option>
            <option value="lost"><?php esc_html_e( 'Lost', 'almaseo' ); ?></option>
            <option value="rejected"><?php esc_html_e( 'Rejected', 'almaseo' ); ?></option>
        </select>

        <select id="almaseo-st-format-filter" class="almaseo-st-select">
            <option value=""><?php esc_html_e( 'All formats', 'almaseo' ); ?></option>
            <option value="paragraph"><?php esc_html_e( 'Paragraph', 'almaseo' ); ?></option>
            <option value="list"><?php esc_html_e( 'List', 'almaseo' ); ?></option>
            <option value="table"><?php esc_html_e( 'Table', 'almaseo' ); ?></option>
            <option value="definition"><?php esc_html_e( 'Definition', 'almaseo' ); ?></option>
        </select>

        <input type="text" id="almaseo-st-search" class="almaseo-st-search" placeholder="<?php esc_attr_e( 'Search by query or title...', 'almaseo' ); ?>">
    </div>

    <!-- Table -->
    <table class="widefat striped almaseo-st-table" id="almaseo-st-table">
        <thead>
            <tr>
                <th><?php esc_html_e( 'Query', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Page', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Format', 'almaseo' ); ?></th>
                <th class="almaseo-st-col-num"><?php esc_html_e( 'Position', 'almaseo' ); ?></th>
                <th class="almaseo-st-col-num"><?php esc_html_e( 'Volume', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Status', 'almaseo' ); ?></th>
                <th><?php esc_html_e( 'Actions', 'almaseo' ); ?></th>
            </tr>
        </thead>
        <tbody id="almaseo-st-tbody">
            <tr><td colspan="7"><?php esc_html_e( 'Loading...', 'almaseo' ); ?></td></tr>
        </tbody>
    </table>

    <!-- Pagination -->
    <div class="almaseo-st-pagination" id="almaseo-st-pagination"></div>

    <!-- Draft Editor Modal -->
    <div id="almaseo-st-modal" class="almaseo-st-modal" style="display:none;">
        <div class="almaseo-st-modal-overlay"></div>
        <div class="almaseo-st-modal-content">
            <div class="almaseo-st-modal-header">
                <h2 id="almaseo-st-modal-title"><?php esc_html_e( 'Edit Draft', 'almaseo' ); ?></h2>
                <button class="almaseo-st-modal-close" id="almaseo-st-modal-close">&times;</button>
            </div>
            <div class="almaseo-st-modal-body">
                <div class="almaseo-st-modal-meta">
                    <span id="almaseo-st-modal-query"></span>
                    <span id="almaseo-st-modal-format" class="almaseo-st-format-badge"></span>
                </div>
                <div id="almaseo-st-modal-prompt" class="almaseo-st-prompt-hint"></div>
                <label for="almaseo-st-draft-editor"><?php esc_html_e( 'Draft Content (HTML)', 'almaseo' ); ?></label>
                <textarea id="almaseo-st-draft-editor" rows="12" class="large-text code"></textarea>
                <div id="almaseo-st-draft-preview" class="almaseo-st-draft-preview"></div>
            </div>
            <div class="almaseo-st-modal-footer">
                <button id="almaseo-st-save-draft" class="button button-primary"><?php esc_html_e( 'Save Draft', 'almaseo' ); ?></button>
                <button id="almaseo-st-modal-cancel" class="button"><?php esc_html_e( 'Cancel', 'almaseo' ); ?></button>
            </div>
        </div>
    </div>

</div>
