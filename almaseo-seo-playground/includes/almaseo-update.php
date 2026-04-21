<?php
/**
 * AlmaSEO Auto-Update System
 * 
 * Self-hosted update mechanism for private distribution
 * 
 * @package AlmaSEO
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

use YahnisElsts\PluginUpdateChecker\v5\PucFactory;

/**
 * AlmaSEO Update Manager
 */
class AlmaSEO_Update_Manager {
    
    /**
     * Instance
     */
    private static $instance = null;
    
    /**
     * Update checker instance
     */
    private $updateChecker = null;
    
    /**
     * Update settings
     */
    private $settings = array();
    
    /**
     * API endpoint base
     */
    const API_BASE = 'https://api.almaseo.com/updates/';
    
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
        // Don't load in CLI skip-plugins mode
        if (defined('WP_CLI') && WP_CLI && defined('WP_CLI_SKIP_PLUGINS') && WP_CLI_SKIP_PLUGINS) {
            return;
        }
        
        // Load settings
        $this->settings = get_option('almaseo_update_settings', array(
            'channel' => 'stable',  // Default to stable for production
            'last_check' => 0,
            'last_found' => null
        ));
        
        // Initialize update checker after plugins loaded
        add_action('plugins_loaded', array($this, 'init_update_checker'), 5);
        
        // Register AJAX handlers
        add_action('wp_ajax_almaseo_check_updates_now', array($this, 'ajax_check_updates'));
        add_action('wp_ajax_almaseo_save_update_channel', array($this, 'ajax_save_channel'));
        add_action('wp_ajax_almaseo_dismiss_update_notice', array($this, 'ajax_dismiss_update_notice'));
        
        // Schedule cron
        add_action('almaseo_updates_daily_check', array($this, 'cron_check_updates'));
        
        if (!wp_next_scheduled('almaseo_updates_daily_check')) {
            wp_schedule_event(time(), 'daily', 'almaseo_updates_daily_check');
        }
        
        // Admin notices
        add_action('admin_notices', array($this, 'maybe_show_update_notice'));
    }
    
    /**
     * Initialize update checker
     */
    public function init_update_checker() {
        // Skip if already initialized
        if ($this->updateChecker !== null) {
            return;
        }
        
        // Load Plugin Update Checker v5
        $puc_file = ALMASEO_PLUGIN_DIR . 'vendor/plugin-update-checker/plugin-update-checker.php';
        if (!file_exists($puc_file)) {
            return;
        }

        require_once $puc_file;

        // Get channel
        $channel = $this->get_channel();

        // Build endpoint URL
        $endpoint = self::API_BASE . 'almaseo-sitemap.json?channel=' . $channel;

        // Add cache buster for manual checks
        if (defined('DOING_AJAX') && DOING_AJAX) {
            $endpoint .= '&t=' . time();
        }

        // Initialize checker (PUC v5 API)
        try {
            $this->updateChecker = PucFactory::buildUpdateChecker(
                $endpoint,
                ALMASEO_PLUGIN_FILE,
                'almaseo-seo-playground'
            );
            
            // Add metadata filter — enrich update info with readme.txt sections
            $this->updateChecker->addResultFilter(function($info) {
                if ($info) {
                    $info->author = 'AlmaSEO';
                    $info->author_homepage = 'https://almaseo.com';

                    // Add icons if not present
                    if (empty($info->icons)) {
                        $info->icons = array(
                            '2x' => ALMASEO_PLUGIN_URL . 'assets/images/icon-256x256.png',
                            '1x' => ALMASEO_PLUGIN_URL . 'assets/images/icon-128x128.png'
                        );
                    }

                    // Parse local readme.txt to populate "View Details" modal
                    $readme_file = ALMASEO_PATH . 'readme.txt';
                    if (file_exists($readme_file) && class_exists('PucReadmeParser')) {
                        $parser = new \PucReadmeParser();
                        $readme = $parser->parse_readme($readme_file);
                        if (!empty($readme) && !empty($readme['sections'])) {
                            // Merge readme sections (description, changelog, faq, etc.)
                            $info->sections = array_merge(
                                isset($info->sections) ? (array) $info->sections : array(),
                                $readme['sections']
                            );
                        }
                        // Pull additional metadata from readme if not already set
                        if (!empty($readme['requires_at_least']) && empty($info->requires)) {
                            $info->requires = $readme['requires_at_least'];
                        }
                        if (!empty($readme['tested_up_to']) && empty($info->tested)) {
                            $info->tested = $readme['tested_up_to'];
                        }
                        if (!empty($readme['requires_php']) && empty($info->requires_php)) {
                            $info->requires_php = $readme['requires_php'];
                        }
                    }
                }
                return $info;
            });
            
        } catch (Exception $e) {
            error_log('AlmaSEO Update Manager: ' . $e->getMessage());
        }
    }
    
    /**
     * Get current channel
     */
    public function get_channel() {
        $channel = $this->settings['channel'] ?? 'stable';
        return in_array($channel, array('stable', 'beta'), true) ? $channel : 'stable';
    }
    
    /**
     * Set channel
     */
    public function set_channel($channel) {
        if (!in_array($channel, array('stable', 'beta'), true)) {
            return false;
        }
        
        $this->settings['channel'] = $channel;
        update_option('almaseo_update_settings', $this->settings);
        
        // Reinitialize checker with new channel
        $this->updateChecker = null;
        $this->init_update_checker();
        
        return true;
    }
    
    /**
     * Check for updates now
     */
    public function check_for_updates() {
        if (!$this->updateChecker) {
            $this->init_update_checker();
        }
        
        if (!$this->updateChecker) {
            return false;
        }
        
        // Force check
        $update = $this->updateChecker->checkForUpdates();
        
        // Update state
        $this->settings['last_check'] = time();
        $this->settings['last_found'] = $update ? $update->new_version : null;
        update_option('almaseo_update_settings', $this->settings);
        
        return $update;
    }
    
    /**
     * AJAX: Check updates now
     */
    public function ajax_check_updates() {
        // Check nonce
        if (!check_ajax_referer('almaseo_updates', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        // Check for updates
        $update = $this->check_for_updates();
        
        if ($update) {
            wp_send_json_success(array(
                'found' => true,
                'version' => $update->new_version,
                'current' => ALMASEO_PLUGIN_VERSION,
                'download_url' => $update->package ?? '',
                'info_url' => $update->url ?? '',
                'checked_at' => time()
            ));
        } else {
            wp_send_json_success(array(
                'found' => false,
                'current' => ALMASEO_PLUGIN_VERSION,
                'checked_at' => time()
            ));
        }
    }
    
    /**
     * AJAX: Save channel
     */
    public function ajax_save_channel() {
        // Check nonce
        if (!check_ajax_referer('almaseo_updates', 'nonce', false)) {
            wp_send_json_error('Invalid nonce');
        }
        
        // Check capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $channel = sanitize_text_field($_POST['channel'] ?? 'stable');
        
        if ($this->set_channel($channel)) {
            // Trigger background check with new channel
            $this->check_for_updates();
            
            wp_send_json_success(array(
                'channel' => $channel,
                /* translators: %s: update channel name (e.g. Stable, Beta) */
                'message' => sprintf(__('Update channel changed to %s', 'almaseo-seo-playground'), ucfirst($channel))
            ));
        } else {
            wp_send_json_error('Invalid channel');
        }
    }
    
    /**
     * AJAX: Dismiss update notice
     */
    public function ajax_dismiss_update_notice() {
        check_ajax_referer('almaseo_dismiss');

        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        set_transient('almaseo_update_notice_dismissed', true, 7 * DAY_IN_SECONDS);
        wp_send_json_success();
    }

    /**
     * Cron: Daily update check
     */
    public function cron_check_updates() {
        // Skip if checked recently
        $last_check = $this->settings['last_check'] ?? 0;
        if (time() - $last_check < 12 * HOUR_IN_SECONDS) {
            return;
        }
        
        $this->check_for_updates();
    }
    
    /**
     * Maybe show update notice
     */
    public function maybe_show_update_notice() {
        // Only on AlmaSEO pages
        $screen = get_current_screen();
        if (!$screen || strpos($screen->id, 'almaseo-seo-playground') === false) {
            return;
        }
        
        // Check if update check is overdue
        $last_check = $this->settings['last_check'] ?? 0;
        if (time() - $last_check > 72 * HOUR_IN_SECONDS) {
            $dismissed = get_user_meta(get_current_user_id(), 'almaseo_dismissed_update_notice', true);
            
            if ($dismissed != date('Y-m-d')) {
                ?>
                <div class="notice notice-info is-dismissible" id="almaseo-update-notice">
                    <p>
                        <?php esc_html_e('AlmaSEO auto-update check is overdue.', 'almaseo-seo-playground'); ?>
                        <a href="#" class="almaseo-check-updates-now">
                            <?php esc_html_e('Check now', 'almaseo-seo-playground'); ?>
                        </a>
                    </p>
                </div>
                <script>
                jQuery(document).ready(function($) {
                    $('#almaseo-update-notice').on('click', '.notice-dismiss', function() {
                        $.post(ajaxurl, {
                            action: 'almaseo_dismiss_update_notice',
                            _ajax_nonce: <?php echo wp_json_encode(wp_create_nonce('almaseo_dismiss')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentional JSON output ?>
                        });
                    });
                    
                    $('.almaseo-check-updates-now').on('click', function(e) {
                        e.preventDefault();
                        $(this).text('Checking...');
                        
                        $.post(ajaxurl, {
                            action: 'almaseo_check_updates_now',
                            nonce: <?php echo wp_json_encode(wp_create_nonce('almaseo_updates')); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Intentional JSON output ?>
                        }, function(response) {
                            if (response.success) {
                                if (response.data.found) {
                                    $('#almaseo-update-notice').empty().append($('<p>').text('Update available: v' + response.data.version));
                                } else {
                                    $('#almaseo-update-notice').fadeOut();
                                }
                            }
                        });
                    });
                });
                </script>
                <?php
            }
        }
    }
    
    /**
     * Get settings for display
     */
    public function get_settings() {
        return $this->settings;
    }
    
    /**
     * Get last check time
     */
    public function get_last_check_time() {
        return $this->settings['last_check'] ?? 0;
    }
    
    /**
     * Get last found version
     */
    public function get_last_found_version() {
        return $this->settings['last_found'] ?? null;
    }
}

// Initialize
AlmaSEO_Update_Manager::get_instance();