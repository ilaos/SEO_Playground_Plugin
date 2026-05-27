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

    <div class="almaseo-sa-intro">
        <h2><?php esc_html_e( 'What does this page do?', 'almaseo-seo-playground' ); ?></h2>
        <p>
            <?php
            echo wp_kses(
                __( 'It sets the default <strong>title</strong> (the blue link Google shows in search results) and <strong>description</strong> (the gray snippet underneath) for every page on your site at once. Instead of writing them post-by-post, you write a template here using "smart tags" like <code>%%title%%</code> &mdash; the plugin fills in the real values for each page automatically.', 'almaseo-seo-playground' ),
                array( 'strong' => array(), 'code' => array() )
            );
            ?>
        </p>
        <p>
            <?php
            echo wp_kses(
                __( '<strong>Do I need to change anything?</strong> Probably not. The defaults work well for most sites &mdash; they show your post title, a separator, and your site name in Google (e.g. <em>Hello World - My Blog</em>). The Save Changes button at the bottom only does something if you actually change something.', 'almaseo-seo-playground' ),
                array( 'strong' => array(), 'em' => array() )
            );
            ?>
        </p>

        <details>
            <summary><?php esc_html_e( 'When would I want to change something here?', 'almaseo-seo-playground' ); ?></summary>
            <ul>
                <li><?php esc_html_e( 'You want every product or article on your site to follow the same format in Google.', 'almaseo-seo-playground' ); ?></li>
                <li><?php esc_html_e( 'You want to hide certain types of pages from search engines (author archives, date archives, attachments, 404 pages).', 'almaseo-seo-playground' ); ?></li>
                <li><?php esc_html_e( 'You want a custom title and description for your homepage.', 'almaseo-seo-playground' ); ?></li>
                <li><?php esc_html_e( 'You want to change the separator character (the dash between your post title and site name).', 'almaseo-seo-playground' ); ?></li>
            </ul>
        </details>

        <details>
            <summary><?php esc_html_e( 'How does this work with the per-post SEO fields?', 'almaseo-seo-playground' ); ?></summary>
            <p><?php esc_html_e( 'Per-post settings always win over this page. For each page on your site, the plugin uses values in this order:', 'almaseo-seo-playground' ); ?></p>
            <ol>
                <li><?php echo wp_kses( __( 'Whatever you typed into the <strong>SEO Page Health</strong> box on that post\'s editor (highest priority).', 'almaseo-seo-playground' ), array( 'strong' => array() ) ); ?></li>
                <li><?php esc_html_e( 'The template on this page (used when the per-post field is blank).', 'almaseo-seo-playground' ); ?></li>
                <li><?php esc_html_e( 'WordPress defaults (used when neither has anything filled in).', 'almaseo-seo-playground' ); ?></li>
            </ol>
            <p><?php esc_html_e( 'The same applies to the "hide from search engines" (noindex) setting: if you\'ve explicitly set a robots option on a specific post, that wins for that post. Otherwise the post-type default from this page takes effect.', 'almaseo-seo-playground' ); ?></p>
            <p>
                <?php
                echo wp_kses(
                    __( 'Smart tags (<code>%%title%%</code>, <code>%%sep%%</code>, etc.) work inside the per-post SEO fields too &mdash; useful if you want a mostly-custom title that still pulls in one dynamic value.', 'almaseo-seo-playground' ),
                    array( 'code' => array() )
                );
                ?>
            </p>
        </details>

        <details>
            <summary><?php esc_html_e( 'I made changes but Google still shows the old title/description. Why?', 'almaseo-seo-playground' ); ?></summary>
            <p><?php esc_html_e( 'Google caches search results. Your changes are live on the page right away (you can view the page source and find the <title> tag to confirm), but it can take a few days to a few weeks for Google to recrawl your site and update what it shows in search results.', 'almaseo-seo-playground' ); ?></p>
        </details>
    </div>

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
        <p class="description">
            <?php esc_html_e( 'Set the default Google title and description for every post and page of each type. If you set a title/description on an individual post (in the SEO Page Health box on the editor), that one wins for that post.', 'almaseo-seo-playground' ); ?>
        </p>
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
                    <th><label><?php esc_html_e( 'Search Engine Visibility', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" class="almaseo-sa-input"
                                   data-path="post_types.<?php echo esc_attr( $pt_slug ); ?>.noindex"
                                   data-type="noindex"
                                   <?php checked( ! empty( $pt_settings['noindex'] ) ); ?> />
                            <?php esc_html_e( 'Hide this post type from search engines (noindex)', 'almaseo-seo-playground' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'When checked, every post of this type tells Google "do not list me in search results." Leave unchecked for normal content. If a specific post has its own robots setting in the SEO Page Health box on its editor, that setting wins for that post.', 'almaseo-seo-playground' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php endforeach; ?>

        <!-- Attachments -->
        <div class="almaseo-sa-card">
            <h3><?php esc_html_e( 'Attachments / Media', 'almaseo-seo-playground' ); ?></h3>
            <p class="description"><?php esc_html_e( 'WordPress creates a separate URL for every image and file you upload (an "attachment page"). These almost never have useful content, so most sites either redirect them to the post that uses the image or hide them from search. Recommended: leave both checked.', 'almaseo-seo-playground' ); ?></p>
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
        <p class="description">
            <?php esc_html_e( '"Taxonomies" means categories, tags, and similar groupings. These settings control the archive pages that list every post in a category or tag — for example, the page Google would show if someone searched for "your-site.com/category/recipes/".', 'almaseo-seo-playground' ); ?>
        </p>
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
                    <th><label><?php esc_html_e( 'Search Engine Visibility', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" class="almaseo-sa-input"
                                   data-path="taxonomies.<?php echo esc_attr( $tax_slug ); ?>.noindex"
                                   data-type="noindex"
                                   <?php checked( ! empty( $tax_settings['noindex'] ) ); ?> />
                            <?php esc_html_e( 'Hide these archive pages from search engines (noindex)', 'almaseo-seo-playground' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Applies to the archive page listing posts under a term (e.g. /category/recipes/). Individual posts in the term are unaffected.', 'almaseo-seo-playground' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- Archives Tab -->
    <div class="almaseo-sa-panel" id="panel-archives">
        <h2><?php esc_html_e( 'Archives', 'almaseo-seo-playground' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'Archives are auto-generated list pages. Author archives list every post by one writer (/author/jane/). Date archives list every post from a month or year (/2024/03/). These pages can dilute your SEO if Google indexes them alongside your real content — many sites noindex date archives for that reason.', 'almaseo-seo-playground' ); ?>
        </p>

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
                        <p class="description"><?php esc_html_e( 'Tip: %%author%% becomes the writer\'s display name.', 'almaseo-seo-playground' ); ?></p>
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
                    <th><label><?php esc_html_e( 'Search Engine Visibility', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" class="almaseo-sa-input"
                                   data-path="archives.author.noindex"
                                   data-type="noindex"
                                   <?php checked( ! empty( $settings['archives']['author']['noindex'] ) ); ?> />
                            <?php esc_html_e( 'Hide author archives from search engines (noindex)', 'almaseo-seo-playground' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Recommended if you have a single author or if author pages are mostly empty.', 'almaseo-seo-playground' ); ?></p>
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
                    <th><label><?php esc_html_e( 'Meta Description', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <input type="text" class="large-text almaseo-sa-input"
                               data-path="archives.date.description_template"
                               value="<?php echo esc_attr( isset( $settings['archives']['date']['description_template'] ) ? $settings['archives']['date']['description_template'] : '' ); ?>"
                               placeholder="<?php esc_attr_e( '(usually left blank — date archives are typically hidden from Google)', 'almaseo-seo-playground' ); ?>" />
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Search Engine Visibility', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" class="almaseo-sa-input"
                                   data-path="archives.date.noindex"
                                   data-type="noindex"
                                   <?php checked( ! empty( $settings['archives']['date']['noindex'] ) ); ?> />
                            <?php esc_html_e( 'Hide date archives from search engines (noindex — recommended)', 'almaseo-seo-playground' ); ?>
                        </label>
                        <p class="description"><?php esc_html_e( 'Date archive pages usually show the same posts already on your main blog/category pages, so search engines may treat them as duplicate content.', 'almaseo-seo-playground' ); ?></p>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Special Pages Tab -->
    <div class="almaseo-sa-panel" id="panel-special">
        <h2><?php esc_html_e( 'Special Pages', 'almaseo-seo-playground' ); ?></h2>
        <p class="description">
            <?php esc_html_e( 'These are the auto-generated pages WordPress creates that aren\'t posts: your homepage, the page shown when someone uses your site search, and the page shown when a URL doesn\'t exist (404).', 'almaseo-seo-playground' ); ?>
        </p>

        <!-- Homepage -->
        <div class="almaseo-sa-card">
            <h3><?php esc_html_e( 'Homepage', 'almaseo-seo-playground' ); ?></h3>
            <p class="description"><?php esc_html_e( 'Used when your site\'s root URL is shown in search. If you set a static front page in Settings → Reading, opening that page in the editor lets you override these per-page.', 'almaseo-seo-playground' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'SEO Title', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <input type="text" class="large-text almaseo-sa-input"
                               data-path="special.homepage.title_template"
                               value="<?php echo esc_attr( $settings['special']['homepage']['title_template'] ); ?>"
                               placeholder="%%sitename%% %%sep%% %%sitetagline%%" />
                        <p class="description"><?php esc_html_e( 'Tip: %%sitename%% is your site title and %%sitetagline%% is the tagline (both set in Settings → General).', 'almaseo-seo-playground' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Meta Description', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <textarea class="large-text almaseo-sa-input" rows="3"
                                  data-path="special.homepage.description_template"
                                  placeholder="<?php esc_attr_e( 'A short sentence describing what your site is about (around 150 characters).', 'almaseo-seo-playground' ); ?>"><?php echo esc_textarea( isset( $settings['special']['homepage']['description_template'] ) ? $settings['special']['homepage']['description_template'] : '' ); ?></textarea>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Search Results -->
        <div class="almaseo-sa-card">
            <h3><?php esc_html_e( 'Search Results', 'almaseo-seo-playground' ); ?></h3>
            <p class="description"><?php esc_html_e( 'The page WordPress shows when a visitor uses your site\'s search box (e.g. yoursite.com/?s=cookies). These aren\'t useful to people arriving from Google, so most sites hide them.', 'almaseo-seo-playground' ); ?></p>
            <table class="form-table">
                <tr>
                    <th><label><?php esc_html_e( 'SEO Title', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <input type="text" class="large-text almaseo-sa-input"
                               data-path="special.search.title_template"
                               value="<?php echo esc_attr( $settings['special']['search']['title_template'] ); ?>"
                               placeholder="%%searchphrase%% %%sep%% %%sitename%%" />
                        <p class="description"><?php esc_html_e( 'Tip: %%searchphrase%% becomes whatever the visitor typed in the search box.', 'almaseo-seo-playground' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Search Engine Visibility', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" class="almaseo-sa-input"
                                   data-path="special.search.noindex"
                                   data-type="noindex"
                                   <?php checked( ! empty( $settings['special']['search']['noindex'] ) ); ?> />
                            <?php esc_html_e( 'Hide search result pages from search engines (noindex — recommended)', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>

        <!-- 404 -->
        <div class="almaseo-sa-card">
            <h3><?php esc_html_e( '404 Page', 'almaseo-seo-playground' ); ?></h3>
            <p class="description"><?php esc_html_e( 'Shown when someone visits a URL that doesn\'t exist (e.g. a broken link). Most sites hide these from Google so dead URLs don\'t end up in search results.', 'almaseo-seo-playground' ); ?></p>
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
                <tr>
                    <th><label><?php esc_html_e( 'Meta Description', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <input type="text" class="large-text almaseo-sa-input"
                               data-path="special.error_404.description_template"
                               value="<?php echo esc_attr( isset( $settings['special']['error_404']['description_template'] ) ? $settings['special']['error_404']['description_template'] : '' ); ?>"
                               placeholder="<?php esc_attr_e( '(usually left blank — 404 pages are typically hidden from Google)', 'almaseo-seo-playground' ); ?>" />
                    </td>
                </tr>
                <tr>
                    <th><label><?php esc_html_e( 'Search Engine Visibility', 'almaseo-seo-playground' ); ?></label></th>
                    <td>
                        <label>
                            <input type="checkbox" class="almaseo-sa-input"
                                   data-path="special.error_404.noindex"
                                   data-type="noindex"
                                   <?php checked( ! empty( $settings['special']['error_404']['noindex'] ) ); ?> />
                            <?php esc_html_e( 'Hide 404 pages from search engines (noindex — recommended)', 'almaseo-seo-playground' ); ?>
                        </label>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <!-- Separator Tab -->
    <div class="almaseo-sa-panel" id="panel-separator">
        <h2><?php esc_html_e( 'Title Separator', 'almaseo-seo-playground' ); ?></h2>
        <p class="description">
            <?php
            echo wp_kses(
                __( 'Pick the symbol that goes between parts of your titles. Wherever you type <code>%%sep%%</code> in a template above, this character is shown. Example: with the template <code>%%title%% %%sep%% %%sitename%%</code> and the dash selected, a post titled "Hello" on a site called "My Blog" appears in Google as <strong>Hello - My Blog</strong>.', 'almaseo-seo-playground' ),
                array( 'code' => array(), 'strong' => array() )
            );
            ?>
        </p>

        <div class="almaseo-sa-separator-picker">
            <?php foreach ( $separators as $sep_val => $sep_display ) : ?>
                <label class="almaseo-sa-sep-option <?php echo esc_attr($settings['separator'] === $sep_val ? 'active' : ''); ?>">
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
                <p class="description">
                    <?php
                    echo wp_kses(
                        __( '<strong>What is a smart tag?</strong> A placeholder you type into a title or description template &mdash; the plugin swaps it for the real value when each page is shown. For example, type <code>%%title%%</code> in the template and Google sees the actual post title. These tags also work inside the per-post SEO fields on each post\'s editor.', 'almaseo-seo-playground' ),
                        array( 'code' => array(), 'strong' => array() )
                    );
                    ?>
                </p>
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
