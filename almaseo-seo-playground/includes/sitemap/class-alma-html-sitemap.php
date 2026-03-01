<?php
/**
 * AlmaSEO HTML Sitemap
 * 
 * Provides HTML sitemap via shortcode and Gutenberg block
 * 
 * @package AlmaSEO
 * @since 4.12.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_HTML_Sitemap {
    
    /**
     * Initialize
     */
    public static function init() {
        // Register shortcode
        add_shortcode('almaseo_html_sitemap', array(__CLASS__, 'render_shortcode'));
        
        // Register Gutenberg block
        add_action('init', array(__CLASS__, 'register_block'));
        
        // Add styles
        add_action('wp_enqueue_scripts', array(__CLASS__, 'enqueue_styles'));
    }
    
    /**
     * Render shortcode
     */
    public static function render_shortcode($atts) {
        $defaults = array(
            'types' => 'posts,pages',
            'columns' => 2,
            'depth' => 0,
            'sort' => 'menu_order',
            'exclude' => '',
            'max_items' => 500
        );
        
        $atts = shortcode_atts($defaults, $atts, 'almaseo_html_sitemap');
        
        // Parse types
        $types = array_map('trim', explode(',', $atts['types']));
        
        // Get sitemap settings to respect includes/excludes
        $settings = get_option('almaseo_sitemap_settings', array());
        
        ob_start();
        ?>
        <div class="almaseo-html-sitemap almaseo-columns-<?php echo esc_attr($atts['columns']); ?>">
            <?php
            foreach ($types as $type) {
                echo self::render_section($type, $atts, $settings);
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render a section of the sitemap
     */
    private static function render_section($type, $atts, $settings) {
        ob_start();
        
        switch ($type) {
            case 'posts':
                if (!empty($settings['include']['posts'])) {
                    echo self::render_posts_section($atts);
                }
                break;
                
            case 'pages':
                if (!empty($settings['include']['pages'])) {
                    echo self::render_pages_section($atts);
                }
                break;
                
            case 'cpts':
                if ($settings['include']['cpts'] === 'all' || !empty($settings['include']['cpts'])) {
                    echo self::render_cpts_section($atts);
                }
                break;
                
            case 'tax':
            case 'taxonomies':
                if (!empty($settings['include']['tax'])) {
                    echo self::render_taxonomies_section($atts, $settings);
                }
                break;
                
            case 'users':
            case 'authors':
                if (!empty($settings['include']['users'])) {
                    echo self::render_users_section($atts);
                }
                break;
        }
        
        return ob_get_clean();
    }
    
    /**
     * Render posts section
     */
    private static function render_posts_section($atts) {
        $args = array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'posts_per_page' => min(100, $atts['max_items']),
            'orderby' => $atts['sort'] === 'date' ? 'date' : 'title',
            'order' => $atts['sort'] === 'date' ? 'DESC' : 'ASC',
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_almaseo_robots_noindex',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_almaseo_robots_noindex',
                    'value' => '1',
                    'compare' => '!='
                )
            )
        );
        
        /**
         * Filter HTML sitemap posts query args
         * 
         * @since 4.12.0
         * @param array $args Query arguments
         */
        $args = apply_filters('almaseo_html_sitemap_posts_args', $args);
        
        $posts = get_posts($args);
        
        if (empty($posts)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="almaseo-sitemap-section">
            <h3><?php _e('Posts', 'almaseo'); ?></h3>
            <ul>
                <?php foreach ($posts as $post): ?>
                <li>
                    <a href="<?php echo get_permalink($post); ?>">
                        <?php echo esc_html($post->post_title); ?>
                    </a>
                    <span class="almaseo-sitemap-date">
                        <?php echo get_the_date('', $post); ?>
                    </span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render pages section
     */
    private static function render_pages_section($atts) {
        $args = array(
            'post_type' => 'page',
            'post_status' => 'publish',
            'posts_per_page' => min(100, $atts['max_items']),
            'orderby' => $atts['sort'],
            'order' => 'ASC',
            'hierarchical' => true,
            'meta_query' => array(
                'relation' => 'OR',
                array(
                    'key' => '_almaseo_robots_noindex',
                    'compare' => 'NOT EXISTS'
                ),
                array(
                    'key' => '_almaseo_robots_noindex',
                    'value' => '1',
                    'compare' => '!='
                )
            )
        );
        
        if (!empty($atts['exclude'])) {
            $args['exclude'] = array_map('intval', explode(',', $atts['exclude']));
        }
        
        /**
         * Filter HTML sitemap pages query args
         * 
         * @since 4.12.0
         * @param array $args Query arguments
         */
        $args = apply_filters('almaseo_html_sitemap_pages_args', $args);
        
        $pages = get_pages($args);
        
        if (empty($pages)) {
            return '';
        }
        
        // Build hierarchical structure
        $pages = self::build_hierarchy($pages, $atts['depth']);
        
        ob_start();
        ?>
        <div class="almaseo-sitemap-section">
            <h3><?php _e('Pages', 'almaseo'); ?></h3>
            <?php echo self::render_page_tree($pages); ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Build hierarchical structure for pages
     */
    private static function build_hierarchy($pages, $max_depth = 0) {
        $tree = array();
        $children = array();
        
        foreach ($pages as $page) {
            if ($page->post_parent == 0) {
                $tree[$page->ID] = $page;
            } else {
                if (!isset($children[$page->post_parent])) {
                    $children[$page->post_parent] = array();
                }
                $children[$page->post_parent][] = $page;
            }
        }
        
        return self::assign_children($tree, $children, 0, $max_depth);
    }
    
    /**
     * Assign children to parent pages
     */
    private static function assign_children($tree, $children, $depth = 0, $max_depth = 0) {
        if ($max_depth > 0 && $depth >= $max_depth) {
            return $tree;
        }
        
        foreach ($tree as $id => $page) {
            if (isset($children[$id])) {
                $page->children = self::assign_children(
                    array_combine(
                        array_column($children[$id], 'ID'),
                        $children[$id]
                    ),
                    $children,
                    $depth + 1,
                    $max_depth
                );
            }
        }
        
        return $tree;
    }
    
    /**
     * Render page tree
     */
    private static function render_page_tree($pages) {
        if (empty($pages)) {
            return '';
        }
        
        $html = '<ul>';
        foreach ($pages as $page) {
            $html .= '<li>';
            $html .= '<a href="' . get_permalink($page) . '">';
            $html .= esc_html($page->post_title);
            $html .= '</a>';
            
            if (!empty($page->children)) {
                $html .= self::render_page_tree($page->children);
            }
            
            $html .= '</li>';
        }
        $html .= '</ul>';
        
        return $html;
    }
    
    /**
     * Render CPTs section
     */
    private static function render_cpts_section($atts) {
        $post_types = get_post_types(array(
            'public' => true,
            '_builtin' => false
        ), 'objects');
        
        if (empty($post_types)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="almaseo-sitemap-section">
            <h3><?php _e('Custom Content', 'almaseo'); ?></h3>
            <?php
            foreach ($post_types as $post_type) {
                $posts = get_posts(array(
                    'post_type' => $post_type->name,
                    'post_status' => 'publish',
                    'posts_per_page' => min(50, $atts['max_items']),
                    'orderby' => 'title',
                    'order' => 'ASC'
                ));
                
                if (!empty($posts)) {
                    echo '<h4>' . esc_html($post_type->label) . '</h4>';
                    echo '<ul>';
                    foreach ($posts as $post) {
                        echo '<li><a href="' . get_permalink($post) . '">';
                        echo esc_html($post->post_title);
                        echo '</a></li>';
                    }
                    echo '</ul>';
                }
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render taxonomies section
     */
    private static function render_taxonomies_section($atts, $settings) {
        ob_start();
        ?>
        <div class="almaseo-sitemap-section">
            <h3><?php _e('Categories & Tags', 'almaseo'); ?></h3>
            <?php
            // Categories
            if (!empty($settings['include']['tax']['category'])) {
                $categories = get_categories(array(
                    'hide_empty' => true,
                    'number' => min(50, $atts['max_items'])
                ));
                
                if (!empty($categories)) {
                    echo '<h4>' . __('Categories', 'almaseo') . '</h4>';
                    echo '<ul>';
                    foreach ($categories as $cat) {
                        echo '<li><a href="' . get_category_link($cat) . '">';
                        echo esc_html($cat->name);
                        echo '</a> (' . $cat->count . ')</li>';
                    }
                    echo '</ul>';
                }
            }
            
            // Tags
            if (!empty($settings['include']['tax']['post_tag'])) {
                $tags = get_tags(array(
                    'hide_empty' => true,
                    'number' => min(30, $atts['max_items'])
                ));
                
                if (!empty($tags)) {
                    echo '<h4>' . __('Tags', 'almaseo') . '</h4>';
                    echo '<ul class="almaseo-tag-list">';
                    foreach ($tags as $tag) {
                        echo '<li><a href="' . get_tag_link($tag) . '">';
                        echo esc_html($tag->name);
                        echo '</a></li>';
                    }
                    echo '</ul>';
                }
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Render users/authors section
     */
    private static function render_users_section($atts) {
        $users = get_users(array(
            'who' => 'authors',
            'has_published_posts' => true,
            'number' => min(30, $atts['max_items'])
        ));
        
        if (empty($users)) {
            return '';
        }
        
        ob_start();
        ?>
        <div class="almaseo-sitemap-section">
            <h3><?php _e('Authors', 'almaseo'); ?></h3>
            <ul>
                <?php foreach ($users as $user): ?>
                <li>
                    <a href="<?php echo get_author_posts_url($user->ID); ?>">
                        <?php echo esc_html($user->display_name); ?>
                    </a>
                    <?php
                    $count = count_user_posts($user->ID);
                    if ($count > 0) {
                        echo ' (' . $count . ' ' . _n('post', 'posts', $count, 'almaseo') . ')';
                    }
                    ?>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php
        return ob_get_clean();
    }
    
    /**
     * Register Gutenberg block
     */
    public static function register_block() {
        if (!function_exists('register_block_type')) {
            return;
        }
        
        register_block_type('almaseo/html-sitemap', array(
            'editor_script' => 'almaseo-html-sitemap-block',
            'render_callback' => array(__CLASS__, 'render_block'),
            'attributes' => array(
                'types' => array(
                    'type' => 'array',
                    'default' => array('posts', 'pages')
                ),
                'columns' => array(
                    'type' => 'number',
                    'default' => 2
                ),
                'depth' => array(
                    'type' => 'number',
                    'default' => 0
                ),
                'sort' => array(
                    'type' => 'string',
                    'default' => 'menu_order'
                ),
                'maxItems' => array(
                    'type' => 'number',
                    'default' => 500
                )
            )
        ));
        
        // Register block script
        wp_register_script(
            'almaseo-html-sitemap-block',
            ALMASEO_PLUGIN_URL . 'assets/js/html-sitemap-block.js',
            array('wp-blocks', 'wp-element', 'wp-editor', 'wp-components'),
            ALMASEO_PLUGIN_VERSION
        );
    }
    
    /**
     * Render block
     */
    public static function render_block($attributes) {
        $types_string = implode(',', $attributes['types']);
        
        return self::render_shortcode(array(
            'types' => $types_string,
            'columns' => $attributes['columns'],
            'depth' => $attributes['depth'],
            'sort' => $attributes['sort'],
            'max_items' => $attributes['maxItems']
        ));
    }
    
    /**
     * Enqueue styles
     */
    public static function enqueue_styles() {
        if (has_shortcode(get_the_content(), 'almaseo_html_sitemap') || has_block('almaseo/html-sitemap')) {
            wp_enqueue_style(
                'almaseo-html-sitemap',
                ALMASEO_PLUGIN_URL . 'assets/css/html-sitemap.css',
                array(),
                ALMASEO_PLUGIN_VERSION
            );
        }
    }
}