<?php
/**
 * AlmaSEO Search Appearance Admin Page
 *
 * Tabbed settings page for title/description templates, per-content-type
 * noindex defaults, separator picker, and attachment handling.
 *
 * @package AlmaSEO
 * @since   8.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$settings    = AlmaSEO_Search_Appearance_Settings::get_settings();
$post_types  = AlmaSEO_Search_Appearance_Settings::get_public_post_types();
$taxonomies  = AlmaSEO_Search_Appearance_Settings::get_public_taxonomies();
$separators  = AlmaSEO_Search_Appearance_Settings::get_separator_options();
$smart_tags  = AlmaSEO_Smart_Tags::get_available_tags();
?>
<div class="wrap almaseo-sa-wrap">
    <h1><?php esc_html_e( 'Search Appearance', 'almaseo-seo-playground' ); ?></h1>
    <p class="description"><?php esc_html_e( 'Configure how your content appears in search engines. Set default title and description templates for each content type, and control search visibility.', 'almaseo-seo-playground' ); ?></p>

    <div class="almaseo-sa-notice" id="almaseo-sa-notice" style="display:none;"></div>

    <nav class="almaseo-sa-tabs">
        <a href="#" class="almaseo-sa-tab active" data-tab="content-types"><?php esc_html_e( 'Content Types', 'almaseo-seo-playground' ); ?></a>
        <a href="#" class="almaseo-sa-tab" data-tab="taxonomies"><?php esc_html_e( 'Taxonomies', 'almaseo-seo-playground' ); ?></a>
        <a href="#" class="almaseo-sa-tab" data-tab="archives"><?php esc_html_e( 'Archives', 'almaseo-seo-playground' ); ?></a>
        <a href="#" class="almaseo-sa-tab" data-tab="special"><?php esc_html_e( 'Special Pages', 'almaseo-seo-playground' ); ?></a>
        <a href="#" class="almaseo-sa-tab" data-tab="separator"><?php esc_html_e( 'Separator', 'almaseo-seo-playground' ); ?></a>
    </nav>

    <!-- Content Types Tab -->
    <div class="almaseo-sa-panel active" id="panel-content-types">
        <h2><?php esc_html_e( 'Post Types', 'almaseo-seo-playground' ); ?></h2>
        <?php foreach ( $post_types as $pt_slug => $pt_obj ) :
            $pt_settings = isset( $settings['post_types'][ $pt_slug ] )
                ? $settings['post_types'][ $pt_slug ]
                : AlmaSEO_Search_Appearance_Settings::get_post_type_settings( $pt_slug );
        ?>
        <div class="almaseo-sa-card">
            <h3><?php echo esc_html( $pt_obj->labels->name ); ?> <code><?php echo esc_html( $pt_slug ); ?></code></h3>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'SEO Title', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <input type="text" class="large-text almaseo-sa-input"
                               data-path="post_types.<?php echo esc_attr( $pt_slug ); ?>.title_template"
                               value="<?php echo esc_attr( $pt_settings['title_template'] ); ?>"
                               placeholder="%%title%% %%sep%% %%sitename%%" />
                        <p class="description"><?php esc_html_e( 'Use smart tags like %%title%%, %%sep%%, %%sitename%%.', 'almaseo-seo-playground' ); ?>
                            <a href="#" class="almaseo-sa-tags-ref"><?php esc_html_e( 'View all tags', 'almaseo-seo-playground' ); ?></a>
                        </p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Meta Description', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <input type="text" class="large-text almaseo-sa-input"
                               data-path="post_types.<?php echo esc_attr( $pt_slug ); ?>.description_template"
                               value="<?php echo esc_attr( $pt_settings['description_template'] ); ?>"
                               placeholder="%%excerpt%%" />
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Show in Search Results', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" class="almaseo-sa-input"
                                   data-path="post_types.<?php echo esc_attr( $pt_slug ); ?>.noindex"
                                   data-type="noindex"
                                   <?php checked( ! empty( $pt_settings['noindex'] ) ); ?> />
                            <?php esc_html_e( 'Noindex this post type (prevent indexing by default)', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        <?php endforeach; ?>

        <!-- Attachments -->
        <div class="almaseo-sa-card">
            <h3><?php esc_html_e( 'Attachments / Media', 'almaseo-seo-playground' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'Redirect to Parent', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" class="almaseo-sa-input"
                                   data-path="attachments.redirect_to_parent"
                                   data-type="bool"
                                   <?php checked( ! empty( $settings['attachments']['redirect_to_parent'] ) ); ?> />
                            <?php esc_html_e( 'Redirect attachment pages to parent post (301 redirect)', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Noindex', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" class="almaseo-sa-input"
                                   data-path="attachments.noindex"
                                   data-type="bool"
                                   <?php checked( ! empty( $settings['attachments']['noindex'] ) ); ?> />
                            <?php esc_html_e( 'Prevent attachment pages from being indexed', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Taxonomies Tab -->
    <div class="almaseo-sa-panel" id="panel-taxonomies">
        <h2><?php esc_html_e( 'Taxonomies', 'almaseo-seo-playground' ); ?></h2>
        <?php foreach ( $taxonomies as $tax_slug => $tax_obj ) :
            $tax_settings = isset( $settings['taxonomies'][ $tax_slug ] )
                ? $settings['taxonomies'][ $tax_slug ]
                : AlmaSEO_Search_Appearance_Settings::get_taxonomy_settings( $tax_slug );
        ?>
        <div class="almaseo-sa-card">
            <h3><?php echo esc_html( $tax_obj->labels->name ); ?> <code><?php echo esc_html( $tax_slug ); ?></code></h3>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'SEO Title', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <input type="text" class="large-text almaseo-sa-input"
                               data-path="taxonomies.<?php echo esc_attr( $tax_slug ); ?>.title_template"
                               value="<?php echo esc_attr( $tax_settings['title_template'] ); ?>"
                               placeholder="%%term_title%% %%sep%% %%sitename%%" />
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Meta Description', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <input type="text" class="large-text almaseo-sa-input"
                               data-path="taxonomies.<?php echo esc_attr( $tax_slug ); ?>.description_template"
                               value="<?php echo esc_attr( $tax_settings['description_template'] ); ?>"
                               placeholder="%%term_description%%" />
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Show in Search Results', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" class="almaseo-sa-input"
                                   data-path="taxonomies.<?php echo esc_attr( $tax_slug ); ?>.noindex"
                                   data-type="noindex"
                                   <?php checked( ! empty( $tax_settings['noindex'] ) ); ?> />
                            <?php esc_html_e( 'Noindex this taxonomy (prevent indexing by default)', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Archives Tab -->
    <div class="almaseo-sa-panel" id="panel-archives">
        <h2><?php esc_html_e( 'Archives', 'almaseo-seo-playground' ); ?></h2>

        <!-- Author Archives -->
        <div class="almaseo-sa-card">
            <h3><?php esc_html_e( 'Author Archives', 'almaseo-seo-playground' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'SEO Title', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <input type="text" class="large-text almaseo-sa-input"
                               data-path="archives.author.title_template"
                               value="<?php echo esc_attr( $settings['archives']['author']['title_template'] ); ?>"
                               placeholder="%%author%% %%sep%% %%sitename%%" />
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Meta Description', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <input type="text" class="large-text almaseo-sa-input"
                               data-path="archives.author.description_template"
                               value="<?php echo esc_attr( isset( $settings['archives']['author']['description_template'] ) ? $settings['archives']['author']['description_template'] : '' ); ?>" />
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Show in Search Results', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" class="almaseo-sa-input"
                                   data-path="archives.author.noindex"
                                   data-type="noindex"
                                   <?php checked( ! empty( $settings['archives']['author']['noindex'] ) ); ?> />
                            <?php esc_html_e( 'Noindex author archives', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Date Archives -->
        <div class="almaseo-sa-card">
            <h3><?php esc_html_e( 'Date Archives', 'almaseo-seo-playground' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'SEO Title', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <input type="text" class="large-text almaseo-sa-input"
                               data-path="archives.date.title_template"
                               value="<?php echo esc_attr( $settings['archives']['date']['title_template'] ); ?>"
                               placeholder="%%date%% %%sep%% %%sitename%%" />
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Show in Search Results', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" class="almaseo-sa-input"
                                   data-path="archives.date.noindex"
                                   data-type="noindex"
                                   <?php checked( ! empty( $settings['archives']['date']['noindex'] ) ); ?> />
                            <?php esc_html_e( 'Noindex date archives (recommended)', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Special Pages Tab -->
    <div class="almaseo-sa-panel" id="panel-special">
        <h2><?php esc_html_e( 'Special Pages', 'almaseo-seo-playground' ); ?></h2>

        <!-- Homepage -->
        <div class="almaseo-sa-card">
            <h3><?php esc_html_e( 'Homepage', 'almaseo-seo-playground' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'SEO Title', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <input type="text" class="large-text almaseo-sa-input"
                               data-path="special.homepage.title_template"
                               value="<?php echo esc_attr( $settings['special']['homepage']['title_template'] ); ?>"
                               placeholder="%%sitename%% %%sep%% %%sitetagline%%" />
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Meta Description', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <textarea class="large-text almaseo-sa-input" rows="3"
                                  data-path="special.homepage.description_template"
                                  placeholder="<?php esc_attr_e( 'Enter a meta description for your homepage', 'almaseo-seo-playground' ); ?>"><?php echo esc_textarea( isset( $settings['special']['homepage']['description_template'] ) ? $settings['special']['homepage']['description_template'] : '' ); ?></textarea>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Search Results -->
        <div class="almaseo-sa-card">
            <h3><?php esc_html_e( 'Search Results', 'almaseo-seo-playground' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'SEO Title', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <input type="text" class="large-text almaseo-sa-input"
                               data-path="special.search.title_template"
                               value="<?php echo esc_attr( $settings['special']['search']['title_template'] ); ?>"
                               placeholder="%%searchphrase%% %%sep%% %%sitename%%" />
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Noindex', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" class="almaseo-sa-input"
                                   data-path="special.search.noindex"
                                   data-type="noindex"
                                   <?php checked( ! empty( $settings['special']['search']['noindex'] ) ); ?> />
                            <?php esc_html_e( 'Noindex search results pages (recommended)', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 404 -->
        <div class="almaseo-sa-card">
            <h3><?php esc_html_e( '404 Page', 'almaseo-seo-playground' ); ?></h3>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'SEO Title', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <input type="text" class="large-text almaseo-sa-input"
                               data-path="special.error_404.title_template"
                               value="<?php echo esc_attr( $settings['special']['error_404']['title_template'] ); ?>"
                               placeholder="Page Not Found %%sep%% %%sitename%%" />
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Separator Tab -->
    <div class="almaseo-sa-panel" id="panel-separator">
        <h2><?php esc_html_e( 'Title Separator', 'almaseo-seo-playground' ); ?></h2>
        <p class="description"><?php esc_html_e( 'Choose the separator character used in the %%sep%% smart tag.', 'almaseo-seo-playground' ); ?></p>

        <div class="almaseo-sa-separator-picker">
            <?php foreach ( $separators as $sep_val => $sep_display ) : ?>
                <label class="almaseo-sa-sep-option <?php echo $settings['separator'] === $sep_val ? 'active' : ''; ?>">
                    <input type="radio" name="separator" value="<?php echo esc_attr( $sep_val ); ?>"
                           <?php checked( $settings['separator'], $sep_val ); ?> />
                    <span><?php echo esc_html( $sep_display ); ?></span>
                </label>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="almaseo-sa-actions">
        <button type="button" class="button button-primary" id="almaseo-sa-save">
            <?php esc_html_e( 'Save Changes', 'almaseo-seo-playground' ); ?>
        </button>
        <span class="spinner" id="almaseo-sa-spinner"></span>
    </div>

    <!-- Smart Tags Reference Modal -->
    <div class="almaseo-sa-modal" id="almaseo-sa-tags-modal" style="display:none;">
        <div class="almaseo-sa-modal-content">
            <div class="almaseo-sa-modal-header">
                <h3><?php esc_html_e( 'Available Smart Tags', 'almaseo-seo-playground' ); ?></h3>
                <button type="button" class="almaseo-sa-modal-close">&times;</button>
            </div>
            <div class="almaseo-sa-modal-body">
                <table class="widefat striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e( 'Tag', 'almaseo-seo-playground' ); ?></th>
                            <th><?php esc_html_e( 'Description', 'almaseo-seo-playground' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $smart_tags as $tag => $desc ) : ?>
                        <tr>
                            <td><code><?php echo esc_html( $tag ); ?></code></td>
                            <td><?php echo esc_html( $desc ); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
