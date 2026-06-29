<?php
/**
 * AlmaSEO Settings Page
 * 
 * @package AlmaSEO
 * @since 2.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * AlmaSEO Settings Manager
 */
class AlmaSEO_Settings {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Get instance
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        add_action('admin_menu', array($this, 'add_settings_page'), 11); // Priority 11 to appear right after Connection
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_init', array($this, 'migrate_old_settings'));
        add_action('wp_ajax_almaseo_schema_preview', array($this, 'ajax_schema_preview'));
        add_action('wp_ajax_almaseo_clear_schema_log', array($this, 'ajax_clear_schema_log'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Add settings page to menu
     */
    public function add_settings_page() {
        add_submenu_page(
            'seo-playground',
            __('AlmaSEO Settings', 'almaseo-seo-playground'),
            __('Settings', 'almaseo-seo-playground'),
            'manage_options',
            'almaseo-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        // Main exclusive schema option
        register_setting('almaseo_settings', 'almaseo_exclusive_schema_enabled', array(
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ));
        
        // Schema control array option
        register_setting('almaseo_settings', 'almaseo_schema_control', array(
            'type' => 'array',
            'default' => array(
                // Whitelist breadcrumb + product schema by default: if a user
                // enables Exclusive Schema Mode without configuring this, their
                // high-value rich-result markup is preserved rather than stripped.
                'keep_breadcrumbs' => true,
                'keep_product' => true,
                'amp_compatibility' => true,
                'enable_logging' => true,
                'log_limit' => 50
            ),
            'sanitize_callback' => array($this, 'sanitize_schema_control')
        ));

        // Advanced schema settings (Pro feature)
        register_setting('almaseo_settings', 'almaseo_schema_advanced_settings', array(
            'type' => 'array',
            'default' => array(
                'enabled' => false,
                'site_represents' => 'organization',
                'site_name' => '',
                'site_logo_url' => '',
                'site_social_profiles' => array(),
                'default_schema_by_post_type' => array()
            ),
            'sanitize_callback' => array($this, 'sanitize_schema_advanced_settings')
        ));

        // Evergreen Advanced settings (Pro feature)
        register_setting('almaseo_settings', 'almaseo_evergreen_advanced_settings', array(
            'type' => 'array',
            'default' => array(
                'enabled' => false,
                'ai_freshness_weight' => 30,
                'traffic_trend_weight' => 40,
                'age_weight' => 30,
                'high_risk_threshold' => 75,
                'medium_risk_threshold' => 50,
                'stale_days_threshold' => 365,
                'gsc_window_days' => 90
            ),
            'sanitize_callback' => array($this, 'sanitize_evergreen_advanced_settings')
        ));

        // Crawl Optimization (v8.4.0)
        register_setting('almaseo_settings', 'almaseo_crawl_optimization', array(
            'sanitize_callback' => array('AlmaSEO_Crawl_Optimization', 'sanitize'),
        ));

        // Image SEO (v8.4.0)
        register_setting('almaseo_settings', 'almaseo_image_seo_settings', array(
            'sanitize_callback' => array('AlmaSEO_Image_SEO', 'sanitize'),
        ));

        // Google Analytics (v8.5.0)
        register_setting('almaseo_settings', 'almaseo_analytics_settings', array(
            'type'              => 'array',
            'default'           => AlmaSEO_Analytics_Settings::get_defaults(),
            'sanitize_callback' => array('AlmaSEO_Analytics_Settings', 'sanitize'),
        ));

        // Webmaster Verification Codes (v8.0.0)
        register_setting('almaseo_settings', 'almaseo_verification_codes', array(
            'type' => 'array',
            'default' => AlmaSEO_Verification_Codes::get_defaults(),
            'sanitize_callback' => array('AlmaSEO_Verification_Codes', 'sanitize')
        ));

        // RSS Feed Controls (v8.0.0)
        register_setting('almaseo_settings', 'almaseo_rss_settings', array(
            'type' => 'array',
            'default' => AlmaSEO_RSS_Controls::get_defaults(),
            'sanitize_callback' => array('AlmaSEO_RSS_Controls', 'sanitize')
        ));

        // Tag Manager registers its own option group via AlmaSEO_Tag_Manager::register_setting()
        // because it lives on its own admin page; no registration here.

        // Role Manager Capabilities (v8.0.0)
        register_setting('almaseo_settings', 'almaseo_role_capabilities', array(
            'type' => 'array',
            'default' => AlmaSEO_Role_Manager::get_defaults(),
            'sanitize_callback' => array('AlmaSEO_Role_Manager', 'sanitize')
        ));

        // SEO panel post-type visibility. Default is the *null sentinel* (not
        // an empty array) so almaseo_get_metabox_post_types() can distinguish
        // "user has never visited settings" (→ show on all public CPTs) from
        // "user explicitly unchecked everything" (→ stored as []).
        register_setting('almaseo_settings', 'almaseo_metabox_post_types', array(
            'type'              => 'array',
            'default'           => null,
            'sanitize_callback' => array($this, 'sanitize_metabox_post_types'),
        ));
    }

    /**
     * Sanitize the metabox post-type visibility list.
     *
     * If the form was submitted with no checkboxes checked, $input is not an
     * array — we return [] so the user's "disable everywhere" intent persists.
     * Otherwise we keep only slugs that are currently registered as public,
     * which auto-prunes CPTs whose plugin/theme was deactivated.
     */
    public function sanitize_metabox_post_types($input) {
        if (!is_array($input)) {
            return array();
        }
        $public = get_post_types(array('public' => true), 'names');
        $clean  = array();
        foreach ($input as $slug) {
            $slug = sanitize_key($slug);
            if (isset($public[$slug])) {
                $clean[] = $slug;
            }
        }
        return array_values(array_unique($clean));
    }

    /**
     * Sanitize schema control settings
     */
    public function sanitize_schema_control($input) {
        $sanitized = array();

        $sanitized['keep_breadcrumbs'] = isset($input['keep_breadcrumbs']) ? (bool) $input['keep_breadcrumbs'] : false;
        $sanitized['keep_product'] = isset($input['keep_product']) ? (bool) $input['keep_product'] : false;
        $sanitized['amp_compatibility'] = isset($input['amp_compatibility']) ? (bool) $input['amp_compatibility'] : true;
        $sanitized['enable_logging'] = isset($input['enable_logging']) ? (bool) $input['enable_logging'] : true;
        $sanitized['log_limit'] = isset($input['log_limit']) ? absint($input['log_limit']) : 50;

        return $sanitized;
    }

    /**
     * Sanitize advanced schema settings
     */
    public function sanitize_schema_advanced_settings($input) {
        $sanitized = array();

        $sanitized['enabled'] = isset($input['enabled']) ? (bool) $input['enabled'] : false;
        $sanitized['site_represents'] = isset($input['site_represents']) && in_array($input['site_represents'], array('person', 'organization')) ? $input['site_represents'] : 'organization';
        $sanitized['site_name'] = isset($input['site_name']) ? sanitize_text_field($input['site_name']) : '';
        $sanitized['site_logo_url'] = isset($input['site_logo_url']) ? esc_url_raw($input['site_logo_url']) : '';

        // Social profiles - convert from textarea (one per line) to array
        $sanitized['site_social_profiles'] = array();
        if (isset($input['site_social_profiles_raw']) && !empty($input['site_social_profiles_raw'])) {
            $lines = explode("\n", $input['site_social_profiles_raw']);
            foreach ($lines as $line) {
                $url = esc_url_raw(trim($line));
                if (!empty($url)) {
                    $sanitized['site_social_profiles'][] = $url;
                }
            }
        } elseif (isset($input['site_social_profiles']) && is_array($input['site_social_profiles'])) {
            foreach ($input['site_social_profiles'] as $profile) {
                $url = esc_url_raw(trim($profile));
                if (!empty($url)) {
                    $sanitized['site_social_profiles'][] = $url;
                }
            }
        }

        // Default schema by post type
        $sanitized['default_schema_by_post_type'] = array();
        if (isset($input['default_schema_by_post_type']) && is_array($input['default_schema_by_post_type'])) {
            $valid_types = array('', 'Article', 'BlogPosting', 'NewsArticle', 'FAQPage', 'HowTo', 'Service', 'LocalBusiness', 'MusicGroup', 'Person', 'Organization', 'Product', 'Event', 'Recipe');
            foreach ($input['default_schema_by_post_type'] as $post_type => $schema_type) {
                if (in_array($schema_type, $valid_types, true)) {
                    $sanitized['default_schema_by_post_type'][sanitize_key($post_type)] = $schema_type;
                }
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize evergreen advanced settings
     */
    public function sanitize_evergreen_advanced_settings($input) {
        $sanitized = array();

        $sanitized['enabled'] = isset($input['enabled']) ? (bool) $input['enabled'] : false;

        // Weights (must be 0-100)
        $sanitized['ai_freshness_weight'] = isset($input['ai_freshness_weight']) ? max(0, min(100, absint($input['ai_freshness_weight']))) : 30;
        $sanitized['traffic_trend_weight'] = isset($input['traffic_trend_weight']) ? max(0, min(100, absint($input['traffic_trend_weight']))) : 40;
        $sanitized['age_weight'] = isset($input['age_weight']) ? max(0, min(100, absint($input['age_weight']))) : 30;

        // Thresholds (must be 0-100)
        $sanitized['high_risk_threshold'] = isset($input['high_risk_threshold']) ? max(0, min(100, absint($input['high_risk_threshold']))) : 75;
        $sanitized['medium_risk_threshold'] = isset($input['medium_risk_threshold']) ? max(0, min(100, absint($input['medium_risk_threshold']))) : 50;

        // Medium must sit below High, otherwise the "medium" risk tier becomes
        // unreachable. Clamp it down rather than rejecting the user's input.
        if ($sanitized['medium_risk_threshold'] > $sanitized['high_risk_threshold']) {
            $sanitized['medium_risk_threshold'] = $sanitized['high_risk_threshold'];
        }

        // Stale days (must be positive)
        $sanitized['stale_days_threshold'] = isset($input['stale_days_threshold']) ? max(1, absint($input['stale_days_threshold'])) : 365;

        // GSC window (must be positive)
        $sanitized['gsc_window_days'] = isset($input['gsc_window_days']) ? max(1, absint($input['gsc_window_days'])) : 90;

        return $sanitized;
    }

    /**
     * Migrate old settings if they exist
     */
    public function migrate_old_settings() {
        // Check if migration has already been done
        if (get_option('almaseo_settings_migrated')) {
            return;
        }
        
        // Check for old exclusive schema setting in connection options
        $connection_settings = get_option('almaseo_connection_settings');
        if ($connection_settings && isset($connection_settings['exclusive_schema_enabled'])) {
            // Migrate the setting
            update_option('almaseo_exclusive_schema_enabled', $connection_settings['exclusive_schema_enabled']);
            
            // Remove from old location
            unset($connection_settings['exclusive_schema_enabled']);
            update_option('almaseo_connection_settings', $connection_settings);
            
            // Mark as migrated
            update_option('almaseo_settings_migrated', true);
            
            // Set admin notice
            set_transient('almaseo_settings_migration_notice', true, 3600);
        }
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        // Hook prefix is sanitize_title() of the parent menu's *title* —
        // currently "AlmaSEO SEO Playground" → "almaseo-seo-playground" — not the slug.
        if (strpos($hook, '_page_almaseo-settings') === false) {
            return;
        }
        
        wp_enqueue_style(
            'almaseo-settings',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/settings.css',
            array(),
            '1.15.6'
        );

        wp_enqueue_script(
            'almaseo-settings',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/settings.js',
            array('jquery'),
            '1.15.6',
            true
        );
        
        wp_localize_script('almaseo-settings', 'almaseoSettings', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('almaseo_settings'),
            'homeUrl' => home_url(),
            'i18n' => array(
                'preview_loading' => __('Running preview...', 'almaseo-seo-playground'),
                'preview_error' => __('Error running preview', 'almaseo-seo-playground'),
                'clear_log_confirm' => __('Are you sure you want to clear the schema log?', 'almaseo-seo-playground'),
                'log_cleared' => __('Schema log cleared', 'almaseo-seo-playground'),
                'unsaved_changes' => __('Unsaved changes', 'almaseo-seo-playground'),
                'leave_confirm' => __('You have unsaved changes. Are you sure you want to leave?', 'almaseo-seo-playground'),
            )
        ));
    }
    
    /**
     * Render settings page
     */
    public function render_settings_page() {
        // Show migration notice if needed
        if (get_transient('almaseo_settings_migration_notice')) {
            delete_transient('almaseo_settings_migration_notice');
            ?>
            <div class="notice notice-info is-dismissible">
                <p><?php esc_html_e('Schema settings have been moved from the Connection page to this Settings page.', 'almaseo-seo-playground'); ?></p>
            </div>
            <?php
        }
        
        $exclusive_schema = get_option('almaseo_exclusive_schema_enabled', false);
        $schema_control = get_option('almaseo_schema_control', array(
            'keep_breadcrumbs' => true,
            'keep_product' => true,
            'amp_compatibility' => true,
            'enable_logging' => true,
            'log_limit' => 50
        ));
        
        ?>
        <div class="wrap almaseo-settings-wrap">
            <h1><?php esc_html_e('AlmaSEO Settings', 'almaseo-seo-playground'); ?></h1>
            <p class="description" style="margin-bottom: 14px; font-size: 14px;">
                <?php esc_html_e('AlmaSEO is built for both search engines and large language models (LLMs), with a dedicated LLM Optimization panel in the editor.', 'almaseo-seo-playground'); ?>
            </p>

            <?php /* Accordion toolbar — wired by assets/js/settings.js. Each
                     settings section is rendered as a collapsible panel that
                     starts closed so the full settings surface fits in one
                     scroll-less view. */ ?>
            <div class="almaseo-settings-accordion-toolbar">
                <button type="button" class="almaseo-acc-link" data-acc-action="expand"><?php esc_html_e('Expand all', 'almaseo-seo-playground'); ?></button>
                <span class="almaseo-acc-sep" aria-hidden="true">|</span>
                <button type="button" class="almaseo-acc-link" data-acc-action="collapse"><?php esc_html_e('Collapse all', 'almaseo-seo-playground'); ?></button>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields('almaseo_settings'); ?>
                
                <!-- Schema Control Section -->
                <div class="almaseo-settings-section">
                    <h2><?php esc_html_e('Schema Control', 'almaseo-seo-playground'); ?></h2>
                    <p class="description" style="margin-bottom: 16px; max-width: 780px;">
                        <?php esc_html_e('AlmaSEO automatically prevents duplicate structured data from the major SEO plugins (Yoast, Rank Math, All in One SEO, SEOPress) — no setup needed. The settings below are optional fine-tuning; most sites can safely leave them at their defaults.', 'almaseo-seo-playground'); ?>
                    </p>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Exclusive Schema Mode', 'almaseo-seo-playground'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox"
                                           name="almaseo_exclusive_schema_enabled"
                                           value="1"
                                           <?php checked($exclusive_schema, true); ?>>
                                    <?php esc_html_e('Enable Exclusive Schema Mode', 'almaseo-seo-playground'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('Optional, off by default. Turn this on only if your theme or another plugin still adds its own structured data and you want AlmaSEO to be the single source. It is a deeper cleanup than the automatic protection above. Leaving it off is safe for most sites.', 'almaseo-seo-playground'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr class="schema-sub-options" <?php echo esc_attr(!$exclusive_schema ? 'style="display:none;"' : ''); ?>>
                            <th scope="row"><?php esc_html_e('Whitelist Options', 'almaseo-seo-playground'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" 
                                               name="almaseo_schema_control[keep_breadcrumbs]" 
                                               value="1" 
                                               <?php checked($schema_control['keep_breadcrumbs'], true); ?>>
                                        <?php esc_html_e('Keep BreadcrumbList schema', 'almaseo-seo-playground'); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="checkbox" 
                                               name="almaseo_schema_control[keep_product]" 
                                               value="1" 
                                               <?php checked($schema_control['keep_product'], true); ?>>
                                        <?php esc_html_e('Keep Product schema', 'almaseo-seo-playground'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr class="schema-sub-options" <?php echo esc_attr(!$exclusive_schema ? 'style="display:none;"' : ''); ?>>
                            <th scope="row"><?php esc_html_e('AMP Compatibility', 'almaseo-seo-playground'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           name="almaseo_schema_control[amp_compatibility]" 
                                           value="1" 
                                           <?php checked($schema_control['amp_compatibility'], true); ?>>
                                    <?php esc_html_e('Skip scrubbing on AMP pages', 'almaseo-seo-playground'); ?>
                                </label>
                                <p class="description">
                                    <?php esc_html_e('When enabled, AMP pages will not have their schema modified.', 'almaseo-seo-playground'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr class="schema-sub-options" <?php echo esc_attr(!$exclusive_schema ? 'style="display:none;"' : ''); ?>>
                            <th scope="row"><?php esc_html_e('Logging', 'almaseo-seo-playground'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           name="almaseo_schema_control[enable_logging]" 
                                           value="1" 
                                           <?php checked($schema_control['enable_logging'], true); ?>>
                                    <?php esc_html_e('Enable schema action logging', 'almaseo-seo-playground'); ?>
                                </label>
                                <br>
                                <label>
                                    <?php esc_html_e('Keep last', 'almaseo-seo-playground'); ?>
                                    <input type="number" 
                                           name="almaseo_schema_control[log_limit]" 
                                           value="<?php echo esc_attr($schema_control['log_limit']); ?>" 
                                           min="10" 
                                           max="500" 
                                           style="width: 80px;">
                                    <?php esc_html_e('log entries', 'almaseo-seo-playground'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Advanced Schema Section (Pro) -->
                <div id="almaseo-advanced-schema" class="almaseo-settings-section" style="margin-top: 30px;">
                    <h2><?php esc_html_e('Advanced Schema', 'almaseo-seo-playground'); ?></h2>

                    <?php if (!almaseo_feature_available('schema_advanced')): ?>

                    <?php else: ?>
                        <!-- Pro Tier: Full Controls -->
                        <?php
                        $adv_settings = get_option('almaseo_schema_advanced_settings', array(
                            'enabled' => false,
                            'site_represents' => 'organization',
                            'site_name' => '',
                            'site_logo_url' => '',
                            'site_social_profiles' => array(),
                            'default_schema_by_post_type' => array()
                        ));
                        ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('Enable Advanced Schema', 'almaseo-seo-playground'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="almaseo_schema_advanced_settings[enabled]"
                                               value="1"
                                               <?php checked($adv_settings['enabled'], true); ?>>
                                        <?php esc_html_e('Enable Advanced Schema Features', 'almaseo-seo-playground'); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('When enabled, AlmaSEO will output richer structured data like Knowledge Graph and advanced type mappings.', 'almaseo-seo-playground'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e('Knowledge Graph', 'almaseo-seo-playground'); ?></th>
                                <td>
                                    <fieldset>
                                        <p><strong><?php esc_html_e('This site represents:', 'almaseo-seo-playground'); ?></strong></p>
                                        <label>
                                            <input type="radio"
                                                   name="almaseo_schema_advanced_settings[site_represents]"
                                                   value="organization"
                                                   <?php checked($adv_settings['site_represents'], 'organization'); ?>>
                                            <?php esc_html_e('Organization', 'almaseo-seo-playground'); ?>
                                        </label>
                                        <br>
                                        <label>
                                            <input type="radio"
                                                   name="almaseo_schema_advanced_settings[site_represents]"
                                                   value="person"
                                                   <?php checked($adv_settings['site_represents'], 'person'); ?>>
                                            <?php esc_html_e('Person', 'almaseo-seo-playground'); ?>
                                        </label>

                                        <p style="margin-top: 15px;">
                                            <label for="almaseo_site_name"><?php esc_html_e('Name:', 'almaseo-seo-playground'); ?></label><br>
                                            <input type="text"
                                                   id="almaseo_site_name"
                                                   name="almaseo_schema_advanced_settings[site_name]"
                                                   value="<?php echo esc_attr($adv_settings['site_name']); ?>"
                                                   class="regular-text"
                                                   placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                                        </p>

                                        <p>
                                            <label for="almaseo_site_logo_url"><?php esc_html_e('Logo URL:', 'almaseo-seo-playground'); ?></label><br>
                                            <input type="url"
                                                   id="almaseo_site_logo_url"
                                                   name="almaseo_schema_advanced_settings[site_logo_url]"
                                                   value="<?php echo esc_attr($adv_settings['site_logo_url']); ?>"
                                                   class="regular-text"
                                                   placeholder="https://example.com/logo.png">
                                        </p>

                                        <p>
                                            <label for="almaseo_social_profiles"><?php esc_html_e('Social Profiles (one per line):', 'almaseo-seo-playground'); ?></label><br>
                                            <textarea id="almaseo_social_profiles"
                                                      name="almaseo_schema_advanced_settings[site_social_profiles_raw]"
                                                      rows="4"
                                                      class="large-text"
                                                      placeholder="https://twitter.com/yourhandle&#10;https://facebook.com/yourpage&#10;https://linkedin.com/company/yourcompany"><?php
                                                echo esc_textarea(implode("\n", $adv_settings['site_social_profiles']));
                                            ?></textarea>
                                        </p>
                                    </fieldset>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e('Default Schema Types', 'almaseo-seo-playground'); ?></th>
                                <td>
                                    <?php
                                    $post_types = get_post_types(array('public' => true), 'objects');
                                    foreach ($post_types as $pt):
                                        $current = isset($adv_settings['default_schema_by_post_type'][$pt->name]) ? $adv_settings['default_schema_by_post_type'][$pt->name] : '';
                                    ?>
                                        <p>
                                            <label for="schema_type_<?php echo esc_attr($pt->name); ?>">
                                                <strong><?php echo esc_html($pt->label); ?>:</strong>
                                            </label><br>
                                            <select id="schema_type_<?php echo esc_attr($pt->name); ?>"
                                                    name="almaseo_schema_advanced_settings[default_schema_by_post_type][<?php echo esc_attr($pt->name); ?>]">
                                                <option value=""><?php esc_html_e('Use default (Article/WebPage)', 'almaseo-seo-playground'); ?></option>
                                                <option value="Article" <?php selected($current, 'Article'); ?>>Article</option>
                                                <option value="BlogPosting" <?php selected($current, 'BlogPosting'); ?>>BlogPosting</option>
                                                <option value="NewsArticle" <?php selected($current, 'NewsArticle'); ?>>NewsArticle</option>
                                                <option value="FAQPage" <?php selected($current, 'FAQPage'); ?>>FAQPage</option>
                                                <option value="HowTo" <?php selected($current, 'HowTo'); ?>>HowTo</option>
                                                <option value="Service" <?php selected($current, 'Service'); ?>>Service</option>
                                                <option value="LocalBusiness" <?php selected($current, 'LocalBusiness'); ?>>LocalBusiness</option>
                                                <option value="MusicGroup" <?php selected($current, 'MusicGroup'); ?>>MusicGroup</option>
                                                <option value="Person" <?php selected($current, 'Person'); ?>>Person</option>
                                                <option value="Organization" <?php selected($current, 'Organization'); ?>>Organization</option>
                                                <option value="Product" <?php selected($current, 'Product'); ?>>Product</option>
                                                <option value="Event" <?php selected($current, 'Event'); ?>>Event</option>
                                                <option value="Recipe" <?php selected($current, 'Recipe'); ?>>Recipe</option>
                                            </select>
                                        </p>
                                    <?php endforeach; ?>
                                </td>
                            </tr>

                        </table>
                    <?php endif; ?>
                </div>

                <!-- Evergreen Advanced (Pro) Section -->
                <div class="almaseo-settings-section" style="margin-top: 40px; padding-top: 40px; border-top: 2px solid #e5e7eb;">
                    <h2><?php esc_html_e('Evergreen (Advanced)', 'almaseo-seo-playground'); ?></h2>
                    <p class="description" style="margin-bottom: 20px;">
                        <?php esc_html_e('Advanced content freshness analysis with Alma-powered scoring, traffic trend integration, and intelligent refresh prioritization.', 'almaseo-seo-playground'); ?>
                    </p>

                    <?php if (!almaseo_feature_available('evergreen_advanced')): ?>

                    <?php else: ?>
                        <!-- Pro Tier: Full Controls -->
                        <?php
                        $evergreen_adv_settings = get_option('almaseo_evergreen_advanced_settings', array(
                            'enabled' => false,
                            'ai_freshness_weight' => 30,
                            'traffic_trend_weight' => 40,
                            'age_weight' => 30,
                            'high_risk_threshold' => 75,
                            'medium_risk_threshold' => 50,
                            'stale_days_threshold' => 365,
                            'gsc_window_days' => 90
                        ));
                        $evergreen_adv_enabled = isset($evergreen_adv_settings['enabled']) ? $evergreen_adv_settings['enabled'] : false;
                        ?>

                        <table class="form-table" role="presentation">
                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Enable Evergreen Advanced', 'almaseo-seo-playground'); ?>
                                </th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="almaseo_evergreen_advanced_settings[enabled]"
                                               value="1"
                                               <?php checked($evergreen_adv_enabled, true); ?>>
                                        <?php esc_html_e('Enable advanced freshness scoring and prioritization', 'almaseo-seo-playground'); ?>
                                    </label>
                                    <p class="description">
                                        <?php esc_html_e('When enabled, posts will be scored based on Alma freshness, traffic trends, and content age for intelligent refresh prioritization.', 'almaseo-seo-playground'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Scoring Weights', 'almaseo-seo-playground'); ?>
                                </th>
                                <td>
                                    <div style="margin-bottom: 16px;">
                                        <label style="display: block; margin-bottom: 6px; font-weight: 500;">
                                            <?php esc_html_e('Alma Freshness Weight (%)', 'almaseo-seo-playground'); ?>
                                        </label>
                                        <input type="number"
                                               name="almaseo_evergreen_advanced_settings[ai_freshness_weight]"
                                               value="<?php echo esc_attr($evergreen_adv_settings['ai_freshness_weight']); ?>"
                                               min="0"
                                               max="100"
                                               class="small-text">
                                        <p class="description">
                                            <?php esc_html_e('Weight for Alma-detected freshness signals (0-100). Higher = more emphasis on content clarity and LLM understanding.', 'almaseo-seo-playground'); ?>
                                        </p>
                                    </div>

                                    <div style="margin-bottom: 16px;">
                                        <label style="display: block; margin-bottom: 6px; font-weight: 500;">
                                            <?php esc_html_e('Traffic Trend Weight (%)', 'almaseo-seo-playground'); ?>
                                        </label>
                                        <input type="number"
                                               name="almaseo_evergreen_advanced_settings[traffic_trend_weight]"
                                               value="<?php echo esc_attr($evergreen_adv_settings['traffic_trend_weight']); ?>"
                                               min="0"
                                               max="100"
                                               class="small-text">
                                        <p class="description">
                                            <?php esc_html_e('Weight for traffic trend changes from Google Search Console (0-100). Higher = more emphasis on performance trends.', 'almaseo-seo-playground'); ?>
                                        </p>
                                    </div>

                                    <div>
                                        <label style="display: block; margin-bottom: 6px; font-weight: 500;">
                                            <?php esc_html_e('Age Weight (%)', 'almaseo-seo-playground'); ?>
                                        </label>
                                        <input type="number"
                                               name="almaseo_evergreen_advanced_settings[age_weight]"
                                               value="<?php echo esc_attr($evergreen_adv_settings['age_weight']); ?>"
                                               min="0"
                                               max="100"
                                               class="small-text">
                                        <p class="description">
                                            <?php esc_html_e('Weight for content age since last update (0-100). Higher = older content gets higher priority for refresh.', 'almaseo-seo-playground'); ?>
                                        </p>
                                    </div>

                                    <p class="description" style="margin-top: 12px; padding: 10px; background: #f0f9ff; border-left: 3px solid #3b82f6; color: #1e40af;">
                                        <?php esc_html_e('Tip: Weights are normalized automatically. You can use any distribution that suits your content strategy.', 'almaseo-seo-playground'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Risk Thresholds', 'almaseo-seo-playground'); ?>
                                </th>
                                <td>
                                    <div style="margin-bottom: 16px;">
                                        <label style="display: block; margin-bottom: 6px; font-weight: 500;">
                                            <?php esc_html_e('High Risk Threshold (%)', 'almaseo-seo-playground'); ?>
                                        </label>
                                        <input type="number"
                                               name="almaseo_evergreen_advanced_settings[high_risk_threshold]"
                                               value="<?php echo esc_attr($evergreen_adv_settings['high_risk_threshold']); ?>"
                                               min="0"
                                               max="100"
                                               class="small-text">
                                        <p class="description">
                                            <?php esc_html_e('Posts scoring above this threshold are marked as "High Risk" and urgently need refresh (0-100).', 'almaseo-seo-playground'); ?>
                                        </p>
                                    </div>

                                    <div>
                                        <label style="display: block; margin-bottom: 6px; font-weight: 500;">
                                            <?php esc_html_e('Medium Risk Threshold (%)', 'almaseo-seo-playground'); ?>
                                        </label>
                                        <input type="number"
                                               name="almaseo_evergreen_advanced_settings[medium_risk_threshold]"
                                               value="<?php echo esc_attr($evergreen_adv_settings['medium_risk_threshold']); ?>"
                                               min="0"
                                               max="100"
                                               class="small-text">
                                        <p class="description">
                                            <?php esc_html_e('Posts scoring above this (but below high risk) are marked as "Medium Risk" and should be monitored (0-100).', 'almaseo-seo-playground'); ?>
                                        </p>
                                    </div>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row">
                                    <?php esc_html_e('Time Windows', 'almaseo-seo-playground'); ?>
                                </th>
                                <td>
                                    <div style="margin-bottom: 16px;">
                                        <label style="display: block; margin-bottom: 6px; font-weight: 500;">
                                            <?php esc_html_e('Stale Content Threshold (days)', 'almaseo-seo-playground'); ?>
                                        </label>
                                        <input type="number"
                                               name="almaseo_evergreen_advanced_settings[stale_days_threshold]"
                                               value="<?php echo esc_attr($evergreen_adv_settings['stale_days_threshold']); ?>"
                                               min="1"
                                               class="small-text">
                                        <p class="description">
                                            <?php esc_html_e('Content not updated in this many days is considered stale. Default: 365 days.', 'almaseo-seo-playground'); ?>
                                        </p>
                                    </div>

                                    <div>
                                        <label style="display: block; margin-bottom: 6px; font-weight: 500;">
                                            <?php esc_html_e('GSC Analysis Window (days)', 'almaseo-seo-playground'); ?>
                                        </label>
                                        <input type="number"
                                               name="almaseo_evergreen_advanced_settings[gsc_window_days]"
                                               value="<?php echo esc_attr($evergreen_adv_settings['gsc_window_days']); ?>"
                                               min="1"
                                               max="365"
                                               class="small-text">
                                        <p class="description">
                                            <?php esc_html_e('Number of days to analyze for traffic trend calculations. Default: 90 days. Maximum: 365 days.', 'almaseo-seo-playground'); ?>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        </table>
                    <?php endif; ?>
                </div>

                <?php
                // ── SEO Panel Visibility (decides which post types show the metabox) ──
                // Hardcoded list was 'post' + 'page' only, so the SEO panel was
                // invisible on CPTs like Avada Portfolio. This UI lets the site
                // owner pick per-CPT; default state shows every public CPT.
                $metabox_saved   = get_option('almaseo_metabox_post_types', null);
                $metabox_is_dflt = !is_array($metabox_saved);
                $metabox_checked = function_exists('almaseo_get_metabox_post_types')
                    ? almaseo_get_metabox_post_types()
                    : ($metabox_is_dflt ? array('post', 'page') : $metabox_saved);
                $metabox_all     = get_post_types(array('public' => true), 'objects');
                // WooCommerce products have their own dedicated SEO metabox
                // (includes/woo/woo-metabox.php), and attachments don't get a
                // normal editor screen — exclude both from the list.
                unset($metabox_all['attachment'], $metabox_all['product']);
                // Pin post + page to the top, then alphabetical for the rest so
                // CPTs from themes/plugins (Avada Portfolio, Events, etc.) are
                // easy to find in long lists.
                uksort($metabox_all, function($a, $b) {
                    $pin = array('post' => 0, 'page' => 1);
                    $pa  = isset($pin[$a]) ? $pin[$a] : 100;
                    $pb  = isset($pin[$b]) ? $pin[$b] : 100;
                    return ($pa === $pb) ? strcmp($a, $b) : ($pa - $pb);
                });
                ?>
                <div class="almaseo-settings-section">
                    <h2><?php esc_html_e('SEO Panel Visibility', 'almaseo-seo-playground'); ?></h2>
                    <p class="description">
                        <?php esc_html_e('Choose which post types show the SEO Playground meta box on their edit screen. Unchecking a type hides the panel from that screen. WooCommerce products have their own dedicated SEO panel and are not listed here.', 'almaseo-seo-playground'); ?>
                    </p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Show SEO panel on', 'almaseo-seo-playground'); ?></th>
                            <td>
                                <?php if (empty($metabox_all)) : ?>
                                    <p><em><?php esc_html_e('No public post types are registered on this site.', 'almaseo-seo-playground'); ?></em></p>
                                <?php else : ?>
                                    <fieldset>
                                        <?php foreach ($metabox_all as $pt_slug => $pt_obj) :
                                            $is_checked = in_array($pt_slug, $metabox_checked, true);
                                            $pt_label   = isset($pt_obj->labels->name) ? $pt_obj->labels->name : $pt_slug;
                                        ?>
                                            <label style="display:block; margin-bottom:6px;">
                                                <input type="checkbox"
                                                       name="almaseo_metabox_post_types[]"
                                                       value="<?php echo esc_attr($pt_slug); ?>"
                                                       <?php checked($is_checked); ?> />
                                                <strong><?php echo esc_html($pt_label); ?></strong>
                                                <code style="font-size:11px; color:#646970; margin-left:4px;"><?php echo esc_html($pt_slug); ?></code>
                                            </label>
                                        <?php endforeach; ?>
                                    </fieldset>
                                    <?php if ($metabox_is_dflt) : ?>
                                        <p class="description" style="margin-top:8px;">
                                            <?php esc_html_e('All public post types are enabled by default. Save this page once to lock in your selection.', 'almaseo-seo-playground'); ?>
                                        </p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>
                </div>

                <?php
                /**
                 * Hook for other modules to add settings sections
                 *
                 * @since 7.0.0
                 */
                do_action('almaseo_settings_sections');

                // ── Webmaster Verification Codes Section (v8.0.0) ──
                if (class_exists('AlmaSEO_Verification_Codes')) :
                    $verification_codes = AlmaSEO_Verification_Codes::get_codes();
                    $verification_labels = AlmaSEO_Verification_Codes::get_labels();
                ?>
                <div class="almaseo-settings-section">
                    <h2><?php esc_html_e('Webmaster Verification', 'almaseo-seo-playground'); ?></h2>
                    <p class="description"><?php esc_html_e('Paste verification codes from search engine webmaster tools. You can paste the full meta tag or just the content value.', 'almaseo-seo-playground'); ?></p>
                    <table class="form-table">
                        <?php foreach ($verification_labels as $key => $label) : ?>
                        <tr>
                            <th scope="row"><label for="verification_<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                            <td>
                                <input type="text" id="verification_<?php echo esc_attr($key); ?>"
                                       name="almaseo_verification_codes[<?php echo esc_attr($key); ?>]"
                                       value="<?php echo esc_attr($verification_codes[$key]); ?>"
                                       class="regular-text" />
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif;

                // ── RSS Feed Controls Section (v8.0.0) ──
                if (class_exists('AlmaSEO_RSS_Controls')) :
                    $rss_settings = AlmaSEO_RSS_Controls::get_settings();
                    $rss_tags = AlmaSEO_RSS_Controls::get_available_tags();
                ?>
                <div class="almaseo-settings-section">
                    <h2><?php esc_html_e('RSS Feed', 'almaseo-seo-playground'); ?></h2>
                    <p class="description"><?php esc_html_e('Add content before or after each RSS feed item to prevent content scraping.', 'almaseo-seo-playground'); ?></p>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="rss_before"><?php esc_html_e('Before Feed Content', 'almaseo-seo-playground'); ?></label></th>
                            <td>
                                <textarea id="rss_before" name="almaseo_rss_settings[before_content]" rows="3" class="large-text"><?php echo esc_textarea($rss_settings['before_content']); ?></textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="rss_after"><?php esc_html_e('After Feed Content', 'almaseo-seo-playground'); ?></label></th>
                            <td>
                                <textarea id="rss_after" name="almaseo_rss_settings[after_content]" rows="3" class="large-text"><?php echo esc_textarea($rss_settings['after_content']); ?></textarea>
                                <p class="description" style="margin-bottom: 4px;">
                                    <?php esc_html_e('Available tags:', 'almaseo-seo-playground'); ?>
                                </p>
                                <ul class="description" style="margin: 0 0 0 4px; list-style: none;">
                                    <?php foreach ($rss_tags as $tag => $desc) : ?>
                                        <li><code><?php echo esc_html($tag); ?></code> &mdash; <?php echo esc_html($desc); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </td>
                        </tr>
                    </table>
                </div>
                <?php endif;

                // ── Roles & Permissions Section (v8.0.0) ──
                if (class_exists('AlmaSEO_Role_Manager')) :
                    $role_settings = AlmaSEO_Role_Manager::get_settings();
                    $assignable_roles = AlmaSEO_Role_Manager::get_assignable_roles();
                ?>
                <div class="almaseo-settings-section">
                    <h2><?php esc_html_e('Roles & Permissions', 'almaseo-seo-playground'); ?></h2>
                    <p class="description"><?php esc_html_e('Control which user roles can access SEO editing features (metabox, bulk meta). Plugin settings pages remain admin-only.', 'almaseo-seo-playground'); ?></p>
                    <table class="form-table">
                        <?php foreach ($assignable_roles as $role_slug => $role_name) : ?>
                        <tr>
                            <th scope="row"><?php echo esc_html($role_name); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox"
                                           name="almaseo_role_capabilities[<?php echo esc_attr($role_slug); ?>]"
                                           value="1"
                                           <?php checked(!empty($role_settings[$role_slug])); ?>
                                           <?php disabled($role_slug, 'administrator'); ?> />
                                    <?php esc_html_e('Can edit SEO fields', 'almaseo-seo-playground'); ?>
                                </label>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
                <?php endif; ?>

                <?php /* Sticky form footer (added 1.15.6) — with sections
                         collapsed, the submit button was getting visually
                         orphaned between Roles & Permissions (last form
                         section) and the Schema Preview tools (which live
                         outside the form). position:sticky pins it to the
                         viewport bottom while the form is on screen, then
                         lets it scroll away once the user reaches the
                         non-form sections below. */ ?>
                <div class="almaseo-settings-form-footer">
                    <?php submit_button(); ?>
                </div>
            </form>
            
            <!-- Preview Tool -->
            <div class="almaseo-preview-section" <?php echo esc_attr(!$exclusive_schema ? 'style="display:none;"' : ''); ?>>
                <h2><?php esc_html_e('Schema Preview (Dry-run)', 'almaseo-seo-playground'); ?></h2>
                <p class="description">
                    <?php esc_html_e('Test what schema blocks would be removed from a URL without actually modifying it.', 'almaseo-seo-playground'); ?>
                </p>
                
                <div class="preview-controls">
                    <input type="url" 
                           id="preview-url" 
                           class="regular-text" 
                           placeholder="<?php echo esc_attr(home_url()); ?>" 
                           value="<?php echo esc_attr(home_url()); ?>">
                    <button type="button" 
                            id="run-preview" 
                            class="button button-secondary">
                        <?php esc_html_e('Run Preview', 'almaseo-seo-playground'); ?>
                    </button>
                </div>
                
                <div id="preview-results" style="display:none;">
                    <div class="preview-loading">
                        <span class="spinner is-active"></span>
                        <?php esc_html_e('Analyzing schema blocks...', 'almaseo-seo-playground'); ?>
                    </div>
                    <div class="preview-content"></div>
                </div>
            </div>
            
            <!-- Schema Log -->
            <?php if ($exclusive_schema && $schema_control['enable_logging']): ?>
            <div class="almaseo-log-section">
                <h2>
                    <?php esc_html_e('Schema Action Log', 'almaseo-seo-playground'); ?>
                    <button type="button" 
                            id="clear-schema-log" 
                            class="button button-small">
                        <?php esc_html_e('Clear Log', 'almaseo-seo-playground'); ?>
                    </button>
                </h2>
                
                <div id="schema-log-container">
                    <?php $this->render_schema_log(); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
        <?php
    }
    
    /**
     * Render schema log
     */
    private function render_schema_log() {
        $log = get_option('almaseo_schema_log', array());
        
        if (empty($log)) {
            echo '<p>' . esc_html__('No schema actions logged yet.', 'almaseo-seo-playground') . '</p>';
            return;
        }
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Time', 'almaseo-seo-playground'); ?></th>
                    <th><?php esc_html_e('URL', 'almaseo-seo-playground'); ?></th>
                    <th><?php esc_html_e('Removed', 'almaseo-seo-playground'); ?></th>
                    <th><?php esc_html_e('Kept', 'almaseo-seo-playground'); ?></th>
                    <th><?php esc_html_e('Kept Types', 'almaseo-seo-playground'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($log) as $entry): ?>
                <tr>
                    <td><?php echo esc_html(date_i18n('Y-m-d H:i:s', $entry['time'])); ?></td>
                    <td>
                        <a href="<?php echo esc_url($entry['url']); ?>" target="_blank">
                            <?php echo esc_html(wp_parse_url($entry['url'], PHP_URL_PATH) ?: '/'); ?>
                        </a>
                    </td>
                    <td><?php echo esc_html($entry['removed_count']); ?></td>
                    <td><?php echo esc_html($entry['kept_count']); ?></td>
                    <td><?php echo esc_html(implode(', ', $entry['kept_types'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    
    /**
     * AJAX handler for schema preview
     */
    public function ajax_schema_preview() {
        check_ajax_referer('almaseo_settings', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        $url = isset($_POST['url']) ? esc_url_raw(wp_unslash($_POST['url'])) : '';
        
        if (empty($url)) {
            wp_send_json_error(array('message' => __('Invalid URL', 'almaseo-seo-playground')));
        }
        
        // Fetch the page HTML
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                /* translators: %s: error message from URL fetch */
                'message' => sprintf(__('Failed to fetch URL: %s', 'almaseo-seo-playground'), $response->get_error_message())
            ));
        }
        
        $html = wp_remote_retrieve_body($response);
        
        if (empty($html)) {
            wp_send_json_error(array('message' => __('Empty response from URL', 'almaseo-seo-playground')));
        }
        
        // Run the scrubber in dry-run mode. The "Safe" variant is the one actually
        // loaded by the plugin bootstrap; the legacy AlmaSEO_Schema_Scrubber class
        // is only registered as a fallback and is normally absent.
        if (class_exists('AlmaSEO_Schema_Scrubber_Safe')) {
            $scrubber = AlmaSEO_Schema_Scrubber_Safe::get_instance();
        } elseif (class_exists('AlmaSEO_Schema_Scrubber')) {
            $scrubber = AlmaSEO_Schema_Scrubber::get_instance();
        } else {
            wp_send_json_error(array('message' => __('Schema scrubber not available', 'almaseo-seo-playground')));
        }
        $result = $scrubber->analyze_html($html, true);
        
        wp_send_json_success($result);
    }
    
    /**
     * AJAX handler for clearing schema log
     */
    public function ajax_clear_schema_log() {
        check_ajax_referer('almaseo_settings', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die();
        }
        
        delete_option('almaseo_schema_log');
        
        wp_send_json_success(array('message' => __('Schema log cleared', 'almaseo-seo-playground')));
    }
}

// Initialize
AlmaSEO_Settings::get_instance();