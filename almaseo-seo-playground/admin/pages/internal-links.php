<?php
/**
 * AlmaSEO Internal Links Admin Page
 *
 * Admin UI for managing automatic internal link rules.
 * Loaded by AlmaSEO_Internal_Links_Controller::render_admin_page().
 *
 * @package AlmaSEO
 * @subpackage InternalLinks
 * @since 6.6.0
 */

// Prevent direct access
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Check permissions
if ( ! current_user_can( 'manage_options' ) ) {
    wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'almaseo-seo-playground' ) );
}
?>

<div class="wrap almaseo-internal-links-wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e( 'Internal Links', 'almaseo-seo-playground' ); ?>
        <button class="page-title-action" id="add-new-link-rule">
            <?php esc_html_e( 'Add New Rule', 'almaseo-seo-playground' ); ?>
        </button>
    </h1>

    <!-- Feature Intro -->
    <div class="almaseo-il-intro">
        <p class="almaseo-il-intro-lead">
            <?php esc_html_e( 'Automatically link keywords to the right pages across your site — no content editing required.', 'almaseo-seo-playground' ); ?>
        </p>
        <p class="almaseo-il-intro-desc">
            <?php esc_html_e( 'When a visitor loads one of your posts, AlmaSEO scans the content for keywords you\'ve defined and turns them into links. Your original content is never modified — links appear on the live page only.', 'almaseo-seo-playground' ); ?>
        </p>

        <div class="almaseo-il-how-it-works">
            <h3><?php esc_html_e( 'How it works', 'almaseo-seo-playground' ); ?></h3>
            <ol>
                <li><?php esc_html_e( 'Create a rule — choose a keyword and the page it should link to.', 'almaseo-seo-playground' ); ?></li>
                <li><?php esc_html_e( 'When someone visits a post, AlmaSEO scans the content for that keyword.', 'almaseo-seo-playground' ); ?></li>
                <li><?php esc_html_e( 'Matching keywords are turned into links automatically, with guardrails to prevent over-linking.', 'almaseo-seo-playground' ); ?></li>
            </ol>
        </div>

        <div class="almaseo-il-why">
            <h3><?php esc_html_e( 'Why this approach', 'almaseo-seo-playground' ); ?></h3>
            <ul>
                <li><?php esc_html_e( 'Your content is never permanently changed — the original stays exactly as you wrote it.', 'almaseo-seo-playground' ); ?></li>
                <li><?php esc_html_e( 'One rule can affect hundreds of pages at once. Changes apply instantly across your entire site.', 'almaseo-seo-playground' ); ?></li>
                <li><?php esc_html_e( 'You can edit, pause, or delete any rule at any time with no cleanup needed.', 'almaseo-seo-playground' ); ?></li>
            </ul>
        </div>

        <p class="almaseo-il-how-note">
            <span class="dashicons dashicons-info"></span>
            <?php esc_html_e( 'Search engines like Google see the final page with links included, so this works exactly the same as manual linking for SEO purposes.', 'almaseo-seo-playground' ); ?>
        </p>
    </div>

    <!-- Tab Navigation -->
    <nav class="nav-tab-wrapper almaseo-il-tabs">
        <a href="#rules" class="nav-tab nav-tab-active" data-tab="rules">
            <?php esc_html_e( 'Link Rules', 'almaseo-seo-playground' ); ?>
        </a>
        <a href="#settings" class="nav-tab" data-tab="settings">
            <?php esc_html_e( 'Settings', 'almaseo-seo-playground' ); ?>
        </a>
    </nav>

    <!-- ============================================================
         TAB 1: Link Rules
         ============================================================ -->
    <div class="almaseo-il-tab-content" id="tab-rules">

        <!-- Statistics Cards -->
        <div class="almaseo-il-stats">
            <div class="stat-card">
                <span class="stat-number" id="stat-total-rules">0</span>
                <span class="stat-label"><?php esc_html_e( 'Total Rules', 'almaseo-seo-playground' ); ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-number" id="stat-active-rules">0</span>
                <span class="stat-label"><?php esc_html_e( 'Active', 'almaseo-seo-playground' ); ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-number" id="stat-total-hits">0</span>
                <span class="stat-label"><?php esc_html_e( 'Links Inserted', 'almaseo-seo-playground' ); ?></span>
            </div>
            <div class="stat-card">
                <span class="stat-number" id="stat-unique-targets">0</span>
                <span class="stat-label"><?php esc_html_e( 'Pages Linked To', 'almaseo-seo-playground' ); ?></span>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="tablenav top">
            <div class="alignleft actions">
                <select id="bulk-action-selector">
                    <option value=""><?php esc_html_e( 'Bulk Actions', 'almaseo-seo-playground' ); ?></option>
                    <option value="enable"><?php esc_html_e( 'Enable', 'almaseo-seo-playground' ); ?></option>
                    <option value="disable"><?php esc_html_e( 'Disable', 'almaseo-seo-playground' ); ?></option>
                    <option value="delete"><?php esc_html_e( 'Delete', 'almaseo-seo-playground' ); ?></option>
                </select>
                <button class="button action" id="do-bulk-action">
                    <?php esc_html_e( 'Apply', 'almaseo-seo-playground' ); ?>
                </button>
            </div>
            <div class="alignright">
                <input type="search" id="link-search" placeholder="<?php esc_attr_e( 'Search keywords or URLs...', 'almaseo-seo-playground' ); ?>" />
                <button class="button" id="search-links">
                    <?php esc_html_e( 'Search', 'almaseo-seo-playground' ); ?>
                </button>
            </div>
        </div>

        <!-- Rules Table -->
        <table class="wp-list-table widefat fixed striped" id="link-rules-table">
            <thead>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" id="select-all-rules" />
                    </td>
                    <th class="manage-column column-keyword sortable" data-orderby="keyword">
                        <?php esc_html_e( 'Keyword', 'almaseo-seo-playground' ); ?>
                    </th>
                    <th class="manage-column column-target">
                        <?php esc_html_e( 'Links To', 'almaseo-seo-playground' ); ?>
                    </th>
                    <th class="manage-column column-match-type">
                        <?php esc_html_e( 'Match', 'almaseo-seo-playground' ); ?>
                    </th>
                    <th class="manage-column column-priority sortable" data-orderby="priority">
                        <?php esc_html_e( 'Priority', 'almaseo-seo-playground' ); ?>
                    </th>
                    <th class="manage-column column-enabled">
                        <?php esc_html_e( 'Active', 'almaseo-seo-playground' ); ?>
                    </th>
                    <th class="manage-column column-hits sortable" data-orderby="hits">
                        <?php esc_html_e( 'Hits', 'almaseo-seo-playground' ); ?>
                    </th>
                    <th class="manage-column column-actions">
                        <?php esc_html_e( 'Actions', 'almaseo-seo-playground' ); ?>
                    </th>
                </tr>
            </thead>
            <tbody id="link-rules-list">
                <tr>
                    <td colspan="8" class="loading-message">
                        <?php esc_html_e( 'Loading link rules...', 'almaseo-seo-playground' ); ?>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <td class="manage-column column-cb check-column">
                        <input type="checkbox" />
                    </td>
                    <th><?php esc_html_e( 'Keyword', 'almaseo-seo-playground' ); ?></th>
                    <th><?php esc_html_e( 'Links To', 'almaseo-seo-playground' ); ?></th>
                    <th><?php esc_html_e( 'Match', 'almaseo-seo-playground' ); ?></th>
                    <th><?php esc_html_e( 'Priority', 'almaseo-seo-playground' ); ?></th>
                    <th><?php esc_html_e( 'Active', 'almaseo-seo-playground' ); ?></th>
                    <th><?php esc_html_e( 'Hits', 'almaseo-seo-playground' ); ?></th>
                    <th><?php esc_html_e( 'Actions', 'almaseo-seo-playground' ); ?></th>
                </tr>
            </tfoot>
        </table>

        <!-- Pagination -->
        <div class="tablenav bottom">
            <div class="tablenav-pages" id="link-rules-pagination"></div>
        </div>
    </div>

    <!-- ============================================================
         TAB 2: Settings
         ============================================================ -->
    <div class="almaseo-il-tab-content" id="tab-settings" style="display:none;">

        <div class="almaseo-il-settings-card">
            <h2><?php esc_html_e( 'Auto-Linking Behavior', 'almaseo-seo-playground' ); ?></h2>
            <p class="description" style="margin-top:-8px; margin-bottom:16px;">
                <?php esc_html_e( 'These settings apply globally to every link rule. Individual rules can override some of these through their own options.', 'almaseo-seo-playground' ); ?>
            </p>

            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="setting-enabled">
                            <?php esc_html_e( 'Enable Auto-Linking', 'almaseo-seo-playground' ); ?>
                        </label>
                    </th>
                    <td>
                        <label>
                            <input type="checkbox" id="setting-enabled" value="1" checked />
                            <?php esc_html_e( 'Insert links automatically when visitors view your posts', 'almaseo-seo-playground' ); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e( 'Turn this off to pause all auto-linking without deleting your rules. Useful for troubleshooting or temporary pauses.', 'almaseo-seo-playground' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="setting-max-links">
                            <?php esc_html_e( 'Max Links Per Post', 'almaseo-seo-playground' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="number" id="setting-max-links" min="1" max="50" value="10" class="small-text" />
                        <p class="description">
                            <?php esc_html_e( 'The maximum number of auto-inserted links in any single post. This prevents over-linking, which search engines can view negatively. A good starting point is 5-10 for most sites.', 'almaseo-seo-playground' ); ?>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Protected Zones', 'almaseo-seo-playground' ); ?></th>
                    <td>
                        <p class="description" style="margin-bottom:8px;">
                            <?php esc_html_e( 'Content areas where links will never be inserted, even if a keyword matches:', 'almaseo-seo-playground' ); ?>
                        </p>
                        <fieldset>
                            <label>
                                <input type="checkbox" id="setting-skip-headings" value="1" checked />
                                <?php esc_html_e( 'Headings (h1-h6) — keeps your heading structure clean', 'almaseo-seo-playground' ); ?>
                            </label><br/>
                            <label>
                                <input type="checkbox" id="setting-skip-images" value="1" checked />
                                <?php esc_html_e( 'Images and captions — avoids breaking image layouts', 'almaseo-seo-playground' ); ?>
                            </label><br/>
                            <label>
                                <input type="checkbox" id="setting-skip-first-paragraph" value="1" />
                                <?php esc_html_e( 'First paragraph — keeps your opening text link-free', 'almaseo-seo-playground' ); ?>
                            </label>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="setting-exclude-ids">
                            <?php esc_html_e( 'Exclude Pages', 'almaseo-seo-playground' ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="text" id="setting-exclude-ids" class="regular-text" placeholder="42,99,107" />
                        <p class="description">
                            <?php esc_html_e( 'Comma-separated post or page IDs that should never receive auto-links. Useful for landing pages, legal pages, or any content you want to keep link-free.', 'almaseo-seo-playground' ); ?>
                        </p>
                    </td>
                </tr>
            </table>

            <p class="submit">
                <button type="button" class="button button-primary" id="save-settings">
                    <?php esc_html_e( 'Save Settings', 'almaseo-seo-playground' ); ?>
                </button>
                <span class="spinner" id="settings-spinner"></span>
                <span class="almaseo-il-save-notice" id="settings-notice" style="display:none;"></span>
            </p>
        </div>
    </div>
</div>

<!-- ================================================================
     Add / Edit Rule Modal
     ================================================================ -->
<div id="link-rule-modal" class="almaseo-modal" style="display:none;">
    <div class="almaseo-modal-content">
        <div class="almaseo-modal-header">
            <h2 id="modal-title"><?php esc_html_e( 'Add New Link Rule', 'almaseo-seo-playground' ); ?></h2>
            <button class="almaseo-modal-close">&times;</button>
        </div>

        <div class="almaseo-modal-body">
            <form id="link-rule-form">
                <input type="hidden" id="rule-id" value="" />

                <!-- Keyword -->
                <div class="form-field">
                    <label for="rule-keyword">
                        <?php esc_html_e( 'Keyword or Phrase', 'almaseo-seo-playground' ); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="rule-keyword" name="keyword" placeholder="<?php esc_attr_e( 'e.g. SEO audit', 'almaseo-seo-playground' ); ?>" required />
                    <p class="description">
                        <?php esc_html_e( 'Whenever this word or phrase appears in a post, it will be turned into a link.', 'almaseo-seo-playground' ); ?>
                    </p>
                </div>

                <!-- Target URL -->
                <div class="form-field">
                    <label for="rule-target-url">
                        <?php esc_html_e( 'Link Destination', 'almaseo-seo-playground' ); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="url" id="rule-target-url" name="target_url" placeholder="<?php esc_attr_e( 'https://yoursite.com/seo-audit-guide', 'almaseo-seo-playground' ); ?>" required />
                    <p class="description">
                        <?php esc_html_e( 'The page the keyword should link to. Use a full URL including https://.', 'almaseo-seo-playground' ); ?>
                    </p>
                </div>

                <!-- Match Type -->
                <div class="form-field">
                    <label><?php esc_html_e( 'How to match', 'almaseo-seo-playground' ); ?></label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="match_type" value="exact" checked />
                            <strong><?php esc_html_e( 'Exact match', 'almaseo-seo-playground' ); ?></strong>
                            <span class="description"><?php esc_html_e( '— only matches the whole word, not parts of longer words', 'almaseo-seo-playground' ); ?></span>
                        </label>
                        <label>
                            <input type="radio" name="match_type" value="partial" />
                            <strong><?php esc_html_e( 'Partial match', 'almaseo-seo-playground' ); ?></strong>
                            <span class="description"><?php esc_html_e( '— matches even inside longer words (e.g. "link" matches "linking")', 'almaseo-seo-playground' ); ?></span>
                        </label>
                        <label>
                            <input type="radio" name="match_type" value="regex" />
                            <strong><?php esc_html_e( 'Regex', 'almaseo-seo-playground' ); ?></strong>
                            <span class="description"><?php esc_html_e( '— use a regular expression for advanced pattern matching', 'almaseo-seo-playground' ); ?></span>
                        </label>
                    </div>
                </div>

                <!-- Two-column row -->
                <div class="form-row form-row-two-col">
                    <div class="form-field">
                        <label for="rule-max-per-post">
                            <?php esc_html_e( 'Max links per post', 'almaseo-seo-playground' ); ?>
                        </label>
                        <input type="number" id="rule-max-per-post" name="max_per_post" min="1" max="20" value="1" class="small-text" />
                        <p class="description"><?php esc_html_e( 'How many times this keyword can be linked in a single post.', 'almaseo-seo-playground' ); ?></p>
                    </div>
                    <div class="form-field">
                        <label for="rule-priority">
                            <?php esc_html_e( 'Priority', 'almaseo-seo-playground' ); ?>
                        </label>
                        <input type="number" id="rule-priority" name="priority" min="1" max="100" value="10" class="small-text" />
                        <p class="description"><?php esc_html_e( 'Lower number = linked first. Use 1-5 for your most important pages.', 'almaseo-seo-playground' ); ?></p>
                    </div>
                </div>

                <!-- Options row -->
                <div class="form-field">
                    <label><?php esc_html_e( 'Options', 'almaseo-seo-playground' ); ?></label>
                    <fieldset class="checkbox-group">
                        <label>
                            <input type="checkbox" name="case_sensitive" id="rule-case-sensitive" value="1" />
                            <?php esc_html_e( 'Case sensitive', 'almaseo-seo-playground' ); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="nofollow" id="rule-nofollow" value="1" />
                            <?php esc_html_e( 'Add nofollow', 'almaseo-seo-playground' ); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="new_tab" id="rule-new-tab" value="1" />
                            <?php esc_html_e( 'Open in new tab', 'almaseo-seo-playground' ); ?>
                        </label>
                        <label>
                            <input type="checkbox" name="is_enabled" id="rule-enabled" value="1" checked />
                            <?php esc_html_e( 'Enabled', 'almaseo-seo-playground' ); ?>
                        </label>
                    </fieldset>
                </div>

                <!-- Post Types -->
                <div class="form-field">
                    <label for="rule-post-types">
                        <?php esc_html_e( 'Apply to post types', 'almaseo-seo-playground' ); ?>
                    </label>
                    <input type="text" id="rule-post-types" name="post_types" value="post,page" />
                    <p class="description">
                        <?php esc_html_e( 'Comma-separated. This rule will only link keywords found in these post types.', 'almaseo-seo-playground' ); ?>
                    </p>
                </div>

                <!-- Exclude IDs -->
                <div class="form-field">
                    <label for="rule-exclude-ids">
                        <?php esc_html_e( 'Exclude specific posts', 'almaseo-seo-playground' ); ?>
                    </label>
                    <input type="text" id="rule-exclude-ids" name="exclude_ids" placeholder="42,99" />
                    <p class="description">
                        <?php esc_html_e( 'Comma-separated post IDs where this rule should never apply.', 'almaseo-seo-playground' ); ?>
                    </p>
                </div>

                <!-- Errors -->
                <div class="form-errors" id="form-errors" style="display:none;"></div>
            </form>
        </div>

        <div class="almaseo-modal-footer">
            <button type="button" class="button button-secondary" id="cancel-rule">
                <?php esc_html_e( 'Cancel', 'almaseo-seo-playground' ); ?>
            </button>
            <button type="button" class="button button-primary" id="save-rule">
                <?php esc_html_e( 'Save Rule', 'almaseo-seo-playground' ); ?>
            </button>
        </div>
    </div>
</div>
