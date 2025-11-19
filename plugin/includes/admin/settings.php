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
        add_action('admin_menu', array($this, 'add_settings_page'), 30);
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
            __('AlmaSEO Settings', 'almaseo'),
            __('Settings', 'almaseo'),
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
                'keep_breadcrumbs' => false,
                'keep_product' => false,
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
                'default_schema_by_post_type' => array(),
                'enable_breadcrumbs' => false
            ),
            'sanitize_callback' => array($this, 'sanitize_schema_advanced_settings')
        ));
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
            $valid_types = array('', 'Article', 'BlogPosting', 'NewsArticle', 'FAQPage', 'HowTo', 'Service', 'LocalBusiness');
            foreach ($input['default_schema_by_post_type'] as $post_type => $schema_type) {
                if (in_array($schema_type, $valid_types, true)) {
                    $sanitized['default_schema_by_post_type'][sanitize_key($post_type)] = $schema_type;
                }
            }
        }

        $sanitized['enable_breadcrumbs'] = isset($input['enable_breadcrumbs']) ? (bool) $input['enable_breadcrumbs'] : false;

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
        if ($hook !== 'seo-playground_page_almaseo-settings') {
            return;
        }
        
        wp_enqueue_style(
            'almaseo-settings',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/settings.css',
            array(),
            '2.0.0'
        );
        
        wp_enqueue_script(
            'almaseo-settings',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/settings.js',
            array('jquery'),
            '2.0.0',
            true
        );
        
        wp_localize_script('almaseo-settings', 'almaseoSettings', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('almaseo_settings'),
            'homeUrl' => home_url(),
            'i18n' => array(
                'preview_loading' => __('Running preview...', 'almaseo'),
                'preview_error' => __('Error running preview', 'almaseo'),
                'clear_log_confirm' => __('Are you sure you want to clear the schema log?', 'almaseo'),
                'log_cleared' => __('Schema log cleared', 'almaseo')
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
                <p><?php _e('Schema settings have been moved from the Connection page to this Settings page.', 'almaseo'); ?></p>
            </div>
            <?php
        }
        
        $exclusive_schema = get_option('almaseo_exclusive_schema_enabled', false);
        $schema_control = get_option('almaseo_schema_control', array(
            'keep_breadcrumbs' => false,
            'keep_product' => false,
            'amp_compatibility' => true,
            'enable_logging' => true,
            'log_limit' => 50
        ));
        
        ?>
        <div class="wrap almaseo-settings-wrap">
            <h1><?php _e('AlmaSEO Settings', 'almaseo'); ?></h1>
            <p class="description" style="margin-bottom: 20px; font-size: 14px;">
                <?php _e('AlmaSEO is built for both search engines and AI models (LLMs), with a dedicated LLM Optimization panel in the editor.', 'almaseo'); ?>
            </p>

            <form method="post" action="options.php">
                <?php settings_fields('almaseo_settings'); ?>
                
                <!-- Schema Control Section -->
                <div class="almaseo-settings-section">
                    <h2><?php _e('Schema Control', 'almaseo'); ?></h2>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php _e('Exclusive Schema Mode', 'almaseo'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           name="almaseo_exclusive_schema_enabled" 
                                           value="1" 
                                           <?php checked($exclusive_schema, true); ?>>
                                    <?php _e('Enable Exclusive Schema Mode', 'almaseo'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('When enabled, AlmaSEO removes other JSON-LD blocks so only one structured data block remains.', 'almaseo'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr class="schema-sub-options" <?php echo !$exclusive_schema ? 'style="display:none;"' : ''; ?>>
                            <th scope="row"><?php _e('Whitelist Options', 'almaseo'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" 
                                               name="almaseo_schema_control[keep_breadcrumbs]" 
                                               value="1" 
                                               <?php checked($schema_control['keep_breadcrumbs'], true); ?>>
                                        <?php _e('Keep BreadcrumbList schema', 'almaseo'); ?>
                                    </label>
                                    <br>
                                    <label>
                                        <input type="checkbox" 
                                               name="almaseo_schema_control[keep_product]" 
                                               value="1" 
                                               <?php checked($schema_control['keep_product'], true); ?>>
                                        <?php _e('Keep Product schema', 'almaseo'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        
                        <tr class="schema-sub-options" <?php echo !$exclusive_schema ? 'style="display:none;"' : ''; ?>>
                            <th scope="row"><?php _e('AMP Compatibility', 'almaseo'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           name="almaseo_schema_control[amp_compatibility]" 
                                           value="1" 
                                           <?php checked($schema_control['amp_compatibility'], true); ?>>
                                    <?php _e('Skip scrubbing on AMP pages', 'almaseo'); ?>
                                </label>
                                <p class="description">
                                    <?php _e('When enabled, AMP pages will not have their schema modified.', 'almaseo'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr class="schema-sub-options" <?php echo !$exclusive_schema ? 'style="display:none;"' : ''; ?>>
                            <th scope="row"><?php _e('Logging', 'almaseo'); ?></th>
                            <td>
                                <label>
                                    <input type="checkbox" 
                                           name="almaseo_schema_control[enable_logging]" 
                                           value="1" 
                                           <?php checked($schema_control['enable_logging'], true); ?>>
                                    <?php _e('Enable schema action logging', 'almaseo'); ?>
                                </label>
                                <br>
                                <label>
                                    <?php _e('Keep last', 'almaseo'); ?>
                                    <input type="number" 
                                           name="almaseo_schema_control[log_limit]" 
                                           value="<?php echo esc_attr($schema_control['log_limit']); ?>" 
                                           min="10" 
                                           max="500" 
                                           style="width: 80px;">
                                    <?php _e('log entries', 'almaseo'); ?>
                                </label>
                            </td>
                        </tr>
                    </table>
                </div>

                <!-- Advanced Schema Section (Pro) -->
                <div class="almaseo-settings-section" style="margin-top: 30px;">
                    <h2><?php _e('Advanced Schema (Pro)', 'almaseo'); ?></h2>

                    <?php if (!almaseo_feature_available('schema_advanced')): ?>
                        <!-- Free Tier: Lock Card -->
                        <div style="background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border: 2px dashed #cbd5e1; border-radius: 8px; padding: 30px; text-align: center;">
                            <div style="margin-bottom: 16px;">
                                <span class="dashicons dashicons-lock" style="font-size: 48px; width: 48px; height: 48px; color: #94a3b8;"></span>
                            </div>
                            <h3 style="margin: 0 0 8px 0;"><?php _e('Advanced Schema Features', 'almaseo'); ?></h3>
                            <p style="color: #64748b; margin-bottom: 20px;">
                                <?php _e('Unlock Knowledge Graph, BreadcrumbList, FAQPage, HowTo, and advanced schema types to make your content stand out in search results.', 'almaseo'); ?>
                            </p>
                            <a href="https://almaseo.com/pricing" target="_blank" class="button button-primary" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
                                <?php _e('Upgrade to Pro', 'almaseo'); ?>
                            </a>
                        </div>
                    <?php else: ?>
                        <!-- Pro Tier: Full Controls -->
                        <?php
                        $adv_settings = get_option('almaseo_schema_advanced_settings', array(
                            'enabled' => false,
                            'site_represents' => 'organization',
                            'site_name' => '',
                            'site_logo_url' => '',
                            'site_social_profiles' => array(),
                            'default_schema_by_post_type' => array(),
                            'enable_breadcrumbs' => false
                        ));
                        ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php _e('Enable Advanced Schema', 'almaseo'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="almaseo_schema_advanced_settings[enabled]"
                                               value="1"
                                               <?php checked($adv_settings['enabled'], true); ?>>
                                        <?php _e('Enable Advanced Schema Features', 'almaseo'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('When enabled, AlmaSEO will output richer structured data like Knowledge Graph, Breadcrumbs, and advanced type mappings.', 'almaseo'); ?>
                                    </p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php _e('Knowledge Graph', 'almaseo'); ?></th>
                                <td>
                                    <fieldset>
                                        <p><strong><?php _e('This site represents:', 'almaseo'); ?></strong></p>
                                        <label>
                                            <input type="radio"
                                                   name="almaseo_schema_advanced_settings[site_represents]"
                                                   value="organization"
                                                   <?php checked($adv_settings['site_represents'], 'organization'); ?>>
                                            <?php _e('Organization', 'almaseo'); ?>
                                        </label>
                                        <br>
                                        <label>
                                            <input type="radio"
                                                   name="almaseo_schema_advanced_settings[site_represents]"
                                                   value="person"
                                                   <?php checked($adv_settings['site_represents'], 'person'); ?>>
                                            <?php _e('Person', 'almaseo'); ?>
                                        </label>

                                        <p style="margin-top: 15px;">
                                            <label for="almaseo_site_name"><?php _e('Name:', 'almaseo'); ?></label><br>
                                            <input type="text"
                                                   id="almaseo_site_name"
                                                   name="almaseo_schema_advanced_settings[site_name]"
                                                   value="<?php echo esc_attr($adv_settings['site_name']); ?>"
                                                   class="regular-text"
                                                   placeholder="<?php echo esc_attr(get_bloginfo('name')); ?>">
                                        </p>

                                        <p>
                                            <label for="almaseo_site_logo_url"><?php _e('Logo URL:', 'almaseo'); ?></label><br>
                                            <input type="url"
                                                   id="almaseo_site_logo_url"
                                                   name="almaseo_schema_advanced_settings[site_logo_url]"
                                                   value="<?php echo esc_attr($adv_settings['site_logo_url']); ?>"
                                                   class="regular-text"
                                                   placeholder="https://example.com/logo.png">
                                        </p>

                                        <p>
                                            <label for="almaseo_social_profiles"><?php _e('Social Profiles (one per line):', 'almaseo'); ?></label><br>
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
                                <th scope="row"><?php _e('Default Schema Types', 'almaseo'); ?></th>
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
                                                <option value=""><?php _e('Use default (Article/WebPage)', 'almaseo'); ?></option>
                                                <option value="Article" <?php selected($current, 'Article'); ?>>Article</option>
                                                <option value="BlogPosting" <?php selected($current, 'BlogPosting'); ?>>BlogPosting</option>
                                                <option value="NewsArticle" <?php selected($current, 'NewsArticle'); ?>>NewsArticle</option>
                                                <option value="FAQPage" <?php selected($current, 'FAQPage'); ?>>FAQPage</option>
                                                <option value="HowTo" <?php selected($current, 'HowTo'); ?>>HowTo</option>
                                                <option value="Service" <?php selected($current, 'Service'); ?>>Service</option>
                                                <option value="LocalBusiness" <?php selected($current, 'LocalBusiness'); ?>>LocalBusiness</option>
                                            </select>
                                        </p>
                                    <?php endforeach; ?>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php _e('Breadcrumbs', 'almaseo'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="almaseo_schema_advanced_settings[enable_breadcrumbs]"
                                               value="1"
                                               <?php checked($adv_settings['enable_breadcrumbs'], true); ?>>
                                        <?php _e('Enable BreadcrumbList schema', 'almaseo'); ?>
                                    </label>
                                    <p class="description">
                                        <?php _e('Outputs a BreadcrumbList schema for posts and pages based on your site\'s hierarchy.', 'almaseo'); ?>
                                    </p>
                                </td>
                            </tr>
                        </table>
                    <?php endif; ?>
                </div>

                <?php submit_button(); ?>
            </form>
            
            <!-- Preview Tool -->
            <div class="almaseo-preview-section" <?php echo !$exclusive_schema ? 'style="display:none;"' : ''; ?>>
                <h2><?php _e('Schema Preview (Dry-run)', 'almaseo'); ?></h2>
                <p class="description">
                    <?php _e('Test what schema blocks would be removed from a URL without actually modifying it.', 'almaseo'); ?>
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
                        <?php _e('Run Preview', 'almaseo'); ?>
                    </button>
                </div>
                
                <div id="preview-results" style="display:none;">
                    <div class="preview-loading">
                        <span class="spinner is-active"></span>
                        <?php _e('Analyzing schema blocks...', 'almaseo'); ?>
                    </div>
                    <div class="preview-content"></div>
                </div>
            </div>
            
            <!-- Schema Log -->
            <?php if ($exclusive_schema && $schema_control['enable_logging']): ?>
            <div class="almaseo-log-section">
                <h2>
                    <?php _e('Schema Action Log', 'almaseo'); ?>
                    <button type="button" 
                            id="clear-schema-log" 
                            class="button button-small">
                        <?php _e('Clear Log', 'almaseo'); ?>
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
            echo '<p>' . __('No schema actions logged yet.', 'almaseo') . '</p>';
            return;
        }
        
        ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e('Time', 'almaseo'); ?></th>
                    <th><?php _e('URL', 'almaseo'); ?></th>
                    <th><?php _e('Removed', 'almaseo'); ?></th>
                    <th><?php _e('Kept', 'almaseo'); ?></th>
                    <th><?php _e('Kept Types', 'almaseo'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (array_reverse($log) as $entry): ?>
                <tr>
                    <td><?php echo esc_html(date_i18n('Y-m-d H:i:s', $entry['time'])); ?></td>
                    <td>
                        <a href="<?php echo esc_url($entry['url']); ?>" target="_blank">
                            <?php echo esc_html(parse_url($entry['url'], PHP_URL_PATH) ?: '/'); ?>
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
        
        $url = isset($_POST['url']) ? esc_url_raw($_POST['url']) : '';
        
        if (empty($url)) {
            wp_send_json_error(array('message' => __('Invalid URL', 'almaseo')));
        }
        
        // Fetch the page HTML
        $response = wp_remote_get($url, array(
            'timeout' => 10,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => sprintf(__('Failed to fetch URL: %s', 'almaseo'), $response->get_error_message())
            ));
        }
        
        $html = wp_remote_retrieve_body($response);
        
        if (empty($html)) {
            wp_send_json_error(array('message' => __('Empty response from URL', 'almaseo')));
        }
        
        // Run the scrubber in dry-run mode
        $scrubber = AlmaSEO_Schema_Scrubber::get_instance();
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
        
        wp_send_json_success(array('message' => __('Schema log cleared', 'almaseo')));
    }
}

// Initialize
AlmaSEO_Settings::get_instance();