<?php
/**
 * AlmaSEO Breadcrumbs Builder
 *
 * Builds breadcrumb trails for all WordPress contexts.
 *
 * @package AlmaSEO
 * @subpackage Breadcrumbs
 * @since 7.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class AlmaSEO_Breadcrumbs_Builder
 *
 * Constructs breadcrumb trail arrays for various page types.
 */
class AlmaSEO_Breadcrumbs_Builder {

    /**
     * Build breadcrumb trail for current context
     *
     * @return array Array of breadcrumb items with 'text', 'url', 'is_current' keys
     */
    public static function build() {
        $settings    = AlmaSEO_Breadcrumbs_Loader::get_settings();
        $breadcrumbs = array();

        // Don't show on homepage unless configured
        if (is_front_page() && !$settings['show_on_home']) {
            return array();
        }

        // Home crumb (always first)
        $breadcrumbs[] = array(
            'text'       => $settings['home_text'],
            'url'        => home_url('/'),
            'is_current' => is_front_page(),
        );

        // Build trail based on context
        if (is_singular()) {
            $breadcrumbs = self::build_singular($breadcrumbs, $settings);
        } elseif (is_category()) {
            $breadcrumbs = self::build_category($breadcrumbs, $settings);
        } elseif (is_tag()) {
            $breadcrumbs = self::build_tag($breadcrumbs, $settings);
        } elseif (is_tax()) {
            $breadcrumbs = self::build_taxonomy($breadcrumbs, $settings);
        } elseif (is_post_type_archive()) {
            $breadcrumbs = self::build_post_type_archive($breadcrumbs, $settings);
        } elseif (is_author()) {
            $breadcrumbs = self::build_author($breadcrumbs, $settings);
        } elseif (is_date()) {
            $breadcrumbs = self::build_date($breadcrumbs, $settings);
        } elseif (is_search()) {
            $breadcrumbs = self::build_search($breadcrumbs, $settings);
        } elseif (is_404()) {
            $breadcrumbs = self::build_404($breadcrumbs, $settings);
        } elseif (is_home() && !is_front_page()) {
            // Blog page (posts page) when not front page
            $breadcrumbs = self::build_blog_home($breadcrumbs, $settings);
        }

        // Mark last item as current
        if (!empty($breadcrumbs)) {
            $last_index                          = count($breadcrumbs) - 1;
            $breadcrumbs[$last_index]['is_current'] = true;
        }

        /**
         * Filter the breadcrumb trail
         *
         * @param array $breadcrumbs Array of breadcrumb items
         */
        return apply_filters('almaseo_breadcrumbs_trail', $breadcrumbs);
    }

    /**
     * Build trail for singular posts/pages
     *
     * @param array $breadcrumbs Current breadcrumbs
     * @param array $settings    Settings
     * @return array Updated breadcrumbs
     */
    private static function build_singular($breadcrumbs, $settings) {
        global $post;

        if (!$post) {
            return $breadcrumbs;
        }

        if ($post->post_type === 'post') {
            // Blog page (if set as posts page)
            $blog_page_id = get_option('page_for_posts');
            if ($blog_page_id) {
                $breadcrumbs[] = array(
                    'text'       => get_the_title($blog_page_id),
                    'url'        => get_permalink($blog_page_id),
                    'is_current' => false,
                );
            }

            // Category hierarchy
            $breadcrumbs = self::add_category_crumbs($breadcrumbs, $post->ID, $settings);

        } elseif ($post->post_type === 'page') {
            // Parent pages (hierarchical)
            $breadcrumbs = self::add_page_ancestors($breadcrumbs, $post);

        } else {
            // Custom post type
            $breadcrumbs = self::add_cpt_crumbs($breadcrumbs, $post, $settings);
        }

        // Current page/post
        if ($settings['show_current']) {
            $breadcrumbs[] = array(
                'text'       => get_the_title($post->ID),
                'url'        => get_permalink($post->ID),
                'is_current' => true,
            );
        }

        return $breadcrumbs;
    }

    /**
     * Add category hierarchy for posts
     *
     * @param array $breadcrumbs Current breadcrumbs
     * @param int   $post_id     Post ID
     * @param array $settings    Settings
     * @return array Updated breadcrumbs
     */
    private static function add_category_crumbs($breadcrumbs, $post_id, $settings) {
        $categories = get_the_category($post_id);

        if (empty($categories)) {
            return $breadcrumbs;
        }

        // Select category based on settings
        $selected_cat = self::select_category($categories, $settings['category_selection'], $post_id);

        if (!$selected_cat) {
            return $breadcrumbs;
        }

        // Get category ancestors (parents)
        $ancestors = get_ancestors($selected_cat->term_id, 'category');

        if (!empty($ancestors)) {
            $ancestors = array_reverse($ancestors);
            foreach ($ancestors as $ancestor_id) {
                $ancestor = get_term($ancestor_id, 'category');
                if ($ancestor && !is_wp_error($ancestor)) {
                    $breadcrumbs[] = array(
                        'text'       => $ancestor->name,
                        'url'        => get_category_link($ancestor->term_id),
                        'is_current' => false,
                    );
                }
            }
        }

        // Add selected category
        $breadcrumbs[] = array(
            'text'       => $selected_cat->name,
            'url'        => get_category_link($selected_cat->term_id),
            'is_current' => false,
        );

        return $breadcrumbs;
    }

    /**
     * Select which category to use for posts with multiple categories
     *
     * @param array  $categories Categories
     * @param string $method     Selection method
     * @param int    $post_id    Post ID
     * @return WP_Term|null Selected category
     */
    private static function select_category($categories, $method, $post_id) {
        if (empty($categories)) {
            return null;
        }

        switch ($method) {
            case 'deepest':
                // Find category with most ancestors (deepest in hierarchy)
                $deepest   = null;
                $max_depth = -1;
                foreach ($categories as $cat) {
                    $depth = count(get_ancestors($cat->term_id, 'category'));
                    if ($depth > $max_depth) {
                        $max_depth = $depth;
                        $deepest   = $cat;
                    }
                }
                return $deepest;

            case 'primary':
                // Check for Yoast SEO primary category
                if (class_exists('WPSEO_Primary_Term')) {
                    $primary_term = new WPSEO_Primary_Term('category', $post_id);
                    $primary_id   = $primary_term->get_primary_term();
                    if ($primary_id) {
                        $primary = get_term($primary_id, 'category');
                        if ($primary && !is_wp_error($primary)) {
                            return $primary;
                        }
                    }
                }

                // Check for Rank Math primary category
                $rank_math_primary = get_post_meta($post_id, 'rank_math_primary_category', true);
                if ($rank_math_primary) {
                    $primary = get_term((int) $rank_math_primary, 'category');
                    if ($primary && !is_wp_error($primary)) {
                        return $primary;
                    }
                }

                // Fall through to first
                // no break

            case 'first':
            default:
                return $categories[0];
        }
    }

    /**
     * Add parent page hierarchy
     *
     * @param array   $breadcrumbs Current breadcrumbs
     * @param WP_Post $post        Current post
     * @return array Updated breadcrumbs
     */
    private static function add_page_ancestors($breadcrumbs, $post) {
        $ancestors = get_post_ancestors($post->ID);

        if (!empty($ancestors)) {
            $ancestors = array_reverse($ancestors);
            foreach ($ancestors as $ancestor_id) {
                $breadcrumbs[] = array(
                    'text'       => get_the_title($ancestor_id),
                    'url'        => get_permalink($ancestor_id),
                    'is_current' => false,
                );
            }
        }

        return $breadcrumbs;
    }

    /**
     * Add custom post type archive and taxonomy crumbs
     *
     * @param array   $breadcrumbs Current breadcrumbs
     * @param WP_Post $post        Current post
     * @param array   $settings    Settings
     * @return array Updated breadcrumbs
     */
    private static function add_cpt_crumbs($breadcrumbs, $post, $settings) {
        $post_type_obj = get_post_type_object($post->post_type);

        if (!$post_type_obj) {
            return $breadcrumbs;
        }

        // Add post type archive link (if exists and setting enabled)
        if ($settings['show_post_type_archive'] && $post_type_obj->has_archive) {
            $breadcrumbs[] = array(
                'text'       => $post_type_obj->labels->name,
                'url'        => get_post_type_archive_link($post->post_type),
                'is_current' => false,
            );
        }

        // Check for hierarchical taxonomies attached to this CPT
        $taxonomies = get_object_taxonomies($post->post_type, 'objects');
        foreach ($taxonomies as $tax) {
            if ($tax->hierarchical && $tax->public) {
                $terms = get_the_terms($post->ID, $tax->name);
                if (!empty($terms) && !is_wp_error($terms)) {
                    $primary_term = $terms[0];

                    // Get term ancestors
                    $ancestors = get_ancestors($primary_term->term_id, $tax->name);
                    if (!empty($ancestors)) {
                        $ancestors = array_reverse($ancestors);
                        foreach ($ancestors as $ancestor_id) {
                            $ancestor = get_term($ancestor_id, $tax->name);
                            if ($ancestor && !is_wp_error($ancestor)) {
                                $breadcrumbs[] = array(
                                    'text'       => $ancestor->name,
                                    'url'        => get_term_link($ancestor),
                                    'is_current' => false,
                                );
                            }
                        }
                    }

                    $breadcrumbs[] = array(
                        'text'       => $primary_term->name,
                        'url'        => get_term_link($primary_term),
                        'is_current' => false,
                    );
                    break; // Use first hierarchical taxonomy found
                }
            }
        }

        return $breadcrumbs;
    }

    /**
     * Build trail for category archives
     *
     * @param array $breadcrumbs Current breadcrumbs
     * @param array $settings    Settings
     * @return array Updated breadcrumbs
     */
    private static function build_category($breadcrumbs, $settings) {
        $current_cat = get_queried_object();

        if (!$current_cat) {
            return $breadcrumbs;
        }

        // Blog page (if set)
        $blog_page_id = get_option('page_for_posts');
        if ($blog_page_id) {
            $breadcrumbs[] = array(
                'text'       => get_the_title($blog_page_id),
                'url'        => get_permalink($blog_page_id),
                'is_current' => false,
            );
        }

        // Parent categories
        $ancestors = get_ancestors($current_cat->term_id, 'category');
        if (!empty($ancestors)) {
            $ancestors = array_reverse($ancestors);
            foreach ($ancestors as $ancestor_id) {
                $ancestor = get_term($ancestor_id, 'category');
                if ($ancestor && !is_wp_error($ancestor)) {
                    $breadcrumbs[] = array(
                        'text'       => $ancestor->name,
                        'url'        => get_category_link($ancestor->term_id),
                        'is_current' => false,
                    );
                }
            }
        }

        // Current category
        if ($settings['show_current']) {
            $breadcrumbs[] = array(
                'text'       => $current_cat->name,
                'url'        => get_category_link($current_cat->term_id),
                'is_current' => true,
            );
        }

        return $breadcrumbs;
    }

    /**
     * Build trail for tag archives
     *
     * @param array $breadcrumbs Current breadcrumbs
     * @param array $settings    Settings
     * @return array Updated breadcrumbs
     */
    private static function build_tag($breadcrumbs, $settings) {
        $current_tag = get_queried_object();

        if (!$current_tag) {
            return $breadcrumbs;
        }

        // Blog page (if set)
        $blog_page_id = get_option('page_for_posts');
        if ($blog_page_id) {
            $breadcrumbs[] = array(
                'text'       => get_the_title($blog_page_id),
                'url'        => get_permalink($blog_page_id),
                'is_current' => false,
            );
        }

        // Current tag
        if ($settings['show_current']) {
            $breadcrumbs[] = array(
                /* translators: %s: tag name */
                'text'       => sprintf(__('Tag: %s', 'almaseo-seo-playground'), $current_tag->name),
                'url'        => get_tag_link($current_tag->term_id),
                'is_current' => true,
            );
        }

        return $breadcrumbs;
    }

    /**
     * Build trail for custom taxonomy archives
     *
     * @param array $breadcrumbs Current breadcrumbs
     * @param array $settings    Settings
     * @return array Updated breadcrumbs
     */
    private static function build_taxonomy($breadcrumbs, $settings) {
        $term = get_queried_object();

        if (!$term || !isset($term->taxonomy)) {
            return $breadcrumbs;
        }

        $taxonomy = get_taxonomy($term->taxonomy);

        if (!$taxonomy) {
            return $breadcrumbs;
        }

        // Post type archive (if taxonomy is for a CPT)
        if (!empty($taxonomy->object_type) && $settings['show_post_type_archive']) {
            $post_type     = $taxonomy->object_type[0];
            $post_type_obj = get_post_type_object($post_type);
            if ($post_type_obj && $post_type_obj->has_archive) {
                $breadcrumbs[] = array(
                    'text'       => $post_type_obj->labels->name,
                    'url'        => get_post_type_archive_link($post_type),
                    'is_current' => false,
                );
            }
        }

        // Parent terms (if hierarchical)
        if ($taxonomy->hierarchical) {
            $ancestors = get_ancestors($term->term_id, $term->taxonomy);
            if (!empty($ancestors)) {
                $ancestors = array_reverse($ancestors);
                foreach ($ancestors as $ancestor_id) {
                    $ancestor = get_term($ancestor_id, $term->taxonomy);
                    if ($ancestor && !is_wp_error($ancestor)) {
                        $breadcrumbs[] = array(
                            'text'       => $ancestor->name,
                            'url'        => get_term_link($ancestor),
                            'is_current' => false,
                        );
                    }
                }
            }
        }

        // Current term
        if ($settings['show_current']) {
            $breadcrumbs[] = array(
                'text'       => $term->name,
                'url'        => get_term_link($term),
                'is_current' => true,
            );
        }

        return $breadcrumbs;
    }

    /**
     * Build trail for post type archives
     *
     * @param array $breadcrumbs Current breadcrumbs
     * @param array $settings    Settings
     * @return array Updated breadcrumbs
     */
    private static function build_post_type_archive($breadcrumbs, $settings) {
        $post_type_obj = get_queried_object();

        if (!$post_type_obj) {
            return $breadcrumbs;
        }

        if ($settings['show_current']) {
            $breadcrumbs[] = array(
                'text'       => $post_type_obj->labels->name,
                'url'        => get_post_type_archive_link($post_type_obj->name),
                'is_current' => true,
            );
        }

        return $breadcrumbs;
    }

    /**
     * Build trail for author archives
     *
     * @param array $breadcrumbs Current breadcrumbs
     * @param array $settings    Settings
     * @return array Updated breadcrumbs
     */
    private static function build_author($breadcrumbs, $settings) {
        $author = get_queried_object();

        // Blog page (if set)
        $blog_page_id = get_option('page_for_posts');
        if ($blog_page_id) {
            $breadcrumbs[] = array(
                'text'       => get_the_title($blog_page_id),
                'url'        => get_permalink($blog_page_id),
                'is_current' => false,
            );
        }

        if ($settings['show_current'] && $author) {
            $breadcrumbs[] = array(
                /* translators: %s: author name */
                'text'       => sprintf(__('Author: %s', 'almaseo-seo-playground'), $author->display_name),
                'url'        => get_author_posts_url($author->ID),
                'is_current' => true,
            );
        }

        return $breadcrumbs;
    }

    /**
     * Build trail for date archives
     *
     * @param array $breadcrumbs Current breadcrumbs
     * @param array $settings    Settings
     * @return array Updated breadcrumbs
     */
    private static function build_date($breadcrumbs, $settings) {
        // Blog page (if set)
        $blog_page_id = get_option('page_for_posts');
        if ($blog_page_id) {
            $breadcrumbs[] = array(
                'text'       => get_the_title($blog_page_id),
                'url'        => get_permalink($blog_page_id),
                'is_current' => false,
            );
        }

        $year  = get_query_var('year');
        $month = get_query_var('monthnum');
        $day   = get_query_var('day');

        if (is_year()) {
            if ($settings['show_current']) {
                $breadcrumbs[] = array(
                    'text'       => $year,
                    'url'        => get_year_link($year),
                    'is_current' => true,
                );
            }
        } elseif (is_month()) {
            $breadcrumbs[] = array(
                'text'       => $year,
                'url'        => get_year_link($year),
                'is_current' => false,
            );
            if ($settings['show_current']) {
                $breadcrumbs[] = array(
                    'text'       => date_i18n('F', mktime(0, 0, 0, $month, 1, $year)),
                    'url'        => get_month_link($year, $month),
                    'is_current' => true,
                );
            }
        } elseif (is_day()) {
            $breadcrumbs[] = array(
                'text'       => $year,
                'url'        => get_year_link($year),
                'is_current' => false,
            );
            $breadcrumbs[] = array(
                'text'       => date_i18n('F', mktime(0, 0, 0, $month, 1, $year)),
                'url'        => get_month_link($year, $month),
                'is_current' => false,
            );
            if ($settings['show_current']) {
                $breadcrumbs[] = array(
                    'text'       => $day,
                    'url'        => get_day_link($year, $month, $day),
                    'is_current' => true,
                );
            }
        }

        return $breadcrumbs;
    }

    /**
     * Build trail for search results
     *
     * @param array $breadcrumbs Current breadcrumbs
     * @param array $settings    Settings
     * @return array Updated breadcrumbs
     */
    private static function build_search($breadcrumbs, $settings) {
        if ($settings['show_current']) {
            $breadcrumbs[] = array(
                /* translators: %s: search query */
                'text'       => sprintf(__('Search: %s', 'almaseo-seo-playground'), get_search_query()),
                'url'        => get_search_link(),
                'is_current' => true,
            );
        }

        return $breadcrumbs;
    }

    /**
     * Build trail for 404 page
     *
     * @param array $breadcrumbs Current breadcrumbs
     * @param array $settings    Settings
     * @return array Updated breadcrumbs
     */
    private static function build_404($breadcrumbs, $settings) {
        if ($settings['show_current']) {
            $breadcrumbs[] = array(
                'text'       => __('Page Not Found', 'almaseo-seo-playground'),
                'url'        => '',
                'is_current' => true,
            );
        }

        return $breadcrumbs;
    }

    /**
     * Build trail for blog home (posts page when not front page)
     *
     * @param array $breadcrumbs Current breadcrumbs
     * @param array $settings    Settings
     * @return array Updated breadcrumbs
     */
    private static function build_blog_home($breadcrumbs, $settings) {
        $blog_page_id = get_option('page_for_posts');

        if ($blog_page_id && $settings['show_current']) {
            $breadcrumbs[] = array(
                'text'       => get_the_title($blog_page_id),
                'url'        => get_permalink($blog_page_id),
                'is_current' => true,
            );
        }

        return $breadcrumbs;
    }
}
