<?php
/**
 * AlmaSEO Redirects Admin Page
 * 
 * @package AlmaSEO
 * @subpackage Redirects
 * @since 6.1.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Check permissions
if (!current_user_can('manage_options')) {
    wp_die(esc_html__('You do not have sufficient permissions to access this page.', 'almaseo-seo-playground'));
}
?>

<div class="wrap almaseo-redirects-wrap">
    <h1 class="wp-heading-inline">
        <?php esc_html_e('Redirect Manager', 'almaseo-seo-playground'); ?>
        <button class="page-title-action" id="add-new-redirect">
            <?php esc_html_e('Add New', 'almaseo-seo-playground'); ?>
        </button>
    </h1>
    
    <?php
    // Inline help text
    if (function_exists('almaseo_render_help')) {
        almaseo_render_help(
            __('Redirects send visitors and search engines from old URLs to new ones. Use 301 for permanent changes, 302 for temporary.', 'almaseo-seo-playground'),
            __('Pro Tip: Test redirects in an incognito window to avoid caching issues.', 'almaseo-seo-playground')
        );
    }
    ?>
    
    <!-- Filter Bar -->
    <div class="tablenav top">
        <div class="alignleft actions">
            <select id="bulk-action-selector">
                <option value=""><?php esc_html_e('Bulk Actions', 'almaseo-seo-playground'); ?></option>
                <option value="enable"><?php esc_html_e('Enable', 'almaseo-seo-playground'); ?></option>
                <option value="disable"><?php esc_html_e('Disable', 'almaseo-seo-playground'); ?></option>
                <option value="delete"><?php esc_html_e('Delete', 'almaseo-seo-playground'); ?></option>
            </select>
            <button class="button action" id="do-bulk-action"><?php esc_html_e('Apply', 'almaseo-seo-playground'); ?></button>
        </div>
        
        <div class="alignright">
            <input type="search" id="redirect-search" placeholder="<?php esc_attr_e('Search redirects...', 'almaseo-seo-playground'); ?>" />
            <button class="button" id="search-redirects"><?php esc_html_e('Search', 'almaseo-seo-playground'); ?></button>
        </div>
    </div>
    
    <!-- Redirects Table -->
    <table class="wp-list-table widefat fixed striped" id="redirects-table">
        <thead>
            <tr>
                <td class="manage-column column-cb check-column">
                    <input type="checkbox" id="select-all-redirects" />
                </td>
                <th class="manage-column column-source"><?php esc_html_e('Source', 'almaseo-seo-playground'); ?></th>
                <th class="manage-column column-target"><?php esc_html_e('Target', 'almaseo-seo-playground'); ?></th>
                <th class="manage-column column-status"><?php esc_html_e('Status', 'almaseo-seo-playground'); ?></th>
                <th class="manage-column column-enabled"><?php esc_html_e('Enabled', 'almaseo-seo-playground'); ?></th>
                <th class="manage-column column-hits"><?php esc_html_e('Hits', 'almaseo-seo-playground'); ?></th>
                <th class="manage-column column-last-hit"><?php esc_html_e('Last Hit', 'almaseo-seo-playground'); ?></th>
                <th class="manage-column column-actions"><?php esc_html_e('Actions', 'almaseo-seo-playground'); ?></th>
            </tr>
        </thead>
        <tbody id="redirects-list">
            <tr>
                <td colspan="8" class="loading-message">
                    <?php esc_html_e('Loading redirects...', 'almaseo-seo-playground'); ?>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td class="manage-column column-cb check-column">
                    <input type="checkbox" />
                </td>
                <th class="manage-column"><?php esc_html_e('Source', 'almaseo-seo-playground'); ?></th>
                <th class="manage-column"><?php esc_html_e('Target', 'almaseo-seo-playground'); ?></th>
                <th class="manage-column"><?php esc_html_e('Status', 'almaseo-seo-playground'); ?></th>
                <th class="manage-column"><?php esc_html_e('Enabled', 'almaseo-seo-playground'); ?></th>
                <th class="manage-column"><?php esc_html_e('Hits', 'almaseo-seo-playground'); ?></th>
                <th class="manage-column"><?php esc_html_e('Last Hit', 'almaseo-seo-playground'); ?></th>
                <th class="manage-column"><?php esc_html_e('Actions', 'almaseo-seo-playground'); ?></th>
            </tr>
        </tfoot>
    </table>
    
    <!-- Pagination -->
    <div class="tablenav bottom">
        <div class="tablenav-pages" id="redirects-pagination">
            <!-- Pagination will be added here via JavaScript -->
        </div>
    </div>
</div>

<!-- Add/Edit Modal -->
<div id="redirect-modal" class="almaseo-modal" style="display: none;">
    <div class="almaseo-modal-content">
        <div class="almaseo-modal-header">
            <h2 id="modal-title"><?php esc_html_e('Add New Redirect', 'almaseo-seo-playground'); ?></h2>
            <button class="almaseo-modal-close">&times;</button>
        </div>
        
        <div class="almaseo-modal-body">
            <form id="redirect-form">
                <input type="hidden" id="redirect-id" value="" />
                
                <div class="form-field">
                    <label for="redirect-source">
                        <?php esc_html_e('Source Path', 'almaseo-seo-playground'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="redirect-source" name="source" placeholder="/old-page" required />
                    <p class="description">
                        <?php esc_html_e('The path to redirect from. Must start with /', 'almaseo-seo-playground'); ?>
                    </p>
                </div>
                
                <div class="form-field">
                    <label for="redirect-target">
                        <?php esc_html_e('Target URL', 'almaseo-seo-playground'); ?>
                        <span class="required">*</span>
                    </label>
                    <input type="text" id="redirect-target" name="target" placeholder="/new-page or https://example.com/page" required />
                    <p class="description">
                        <?php esc_html_e('Where to redirect to. Can be a relative path or absolute URL.', 'almaseo-seo-playground'); ?>
                    </p>
                </div>
                
                <div class="form-field">
                    <label><?php esc_html_e('Redirect Type', 'almaseo-seo-playground'); ?></label>
                    <div class="radio-group">
                        <label>
                            <input type="radio" name="status" value="301" checked />
                            <strong>301 - <?php esc_html_e('Permanent', 'almaseo-seo-playground'); ?></strong>
                            <span class="description"><?php esc_html_e('(SEO-friendly, passes link equity)', 'almaseo-seo-playground'); ?></span>
                        </label>
                        <label>
                            <input type="radio" name="status" value="302" />
                            <strong>302 - <?php esc_html_e('Temporary', 'almaseo-seo-playground'); ?></strong>
                            <span class="description"><?php esc_html_e('(For temporary changes)', 'almaseo-seo-playground'); ?></span>
                        </label>
                    </div>
                </div>
                
                <div class="form-field">
                    <label>
                        <input type="checkbox" id="redirect-enabled" name="is_enabled" value="1" checked />
                        <?php esc_html_e('Enable this redirect', 'almaseo-seo-playground'); ?>
                    </label>
                </div>
                
                <div class="form-errors" id="form-errors" style="display: none;"></div>
            </form>
        </div>
        
        <div class="almaseo-modal-footer">
            <button type="button" class="button button-secondary" id="cancel-redirect">
                <?php esc_html_e('Cancel', 'almaseo-seo-playground'); ?>
            </button>
            <button type="button" class="button button-primary" id="save-redirect">
                <?php esc_html_e('Save Redirect', 'almaseo-seo-playground'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Test Modal -->
<div id="test-modal" class="almaseo-modal" style="display: none;">
    <div class="almaseo-modal-content almaseo-modal-small">
        <div class="almaseo-modal-header">
            <h2><?php esc_html_e('Test Redirect', 'almaseo-seo-playground'); ?></h2>
            <button class="almaseo-modal-close">&times;</button>
        </div>
        
        <div class="almaseo-modal-body">
            <form id="test-form">
                <div class="form-field">
                    <label for="test-path">
                        <?php esc_html_e('Enter a path to test:', 'almaseo-seo-playground'); ?>
                    </label>
                    <input type="text" id="test-path" placeholder="/example-path" />
                </div>
                
                <div id="test-result" style="display: none;"></div>
            </form>
        </div>
        
        <div class="almaseo-modal-footer">
            <button type="button" class="button button-secondary almaseo-modal-close">
                <?php esc_html_e('Close', 'almaseo-seo-playground'); ?>
            </button>
            <button type="button" class="button button-primary" id="run-test">
                <?php esc_html_e('Test', 'almaseo-seo-playground'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Stats Summary (Pro Feature Highlight) -->
<div class="almaseo-redirects-stats">
    <h3><?php esc_html_e('Redirect Statistics', 'almaseo-seo-playground'); ?></h3>
    <div class="stats-grid">
        <div class="stat-box">
            <span class="stat-number" id="total-redirects">0</span>
            <span class="stat-label"><?php esc_html_e('Total Redirects', 'almaseo-seo-playground'); ?></span>
        </div>
        <div class="stat-box">
            <span class="stat-number" id="active-redirects">0</span>
            <span class="stat-label"><?php esc_html_e('Active', 'almaseo-seo-playground'); ?></span>
        </div>
        <div class="stat-box">
            <span class="stat-number" id="total-hits">0</span>
            <span class="stat-label"><?php esc_html_e('Total Hits', 'almaseo-seo-playground'); ?></span>
        </div>
        <div class="stat-box">
            <span class="stat-number" id="redirects-today">0</span>
            <span class="stat-label"><?php esc_html_e('Hits Today', 'almaseo-seo-playground'); ?></span>
        </div>
    </div>
</div>