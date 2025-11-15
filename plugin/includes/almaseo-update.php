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
            'channel' => 'beta',  // Default to beta for private rollout
            'last_check' => 0,
            'last_found' => null
        ));
        
        // Initialize update checker after plugins loaded
        add_action('plugins_loaded', array($this, 'init_update_checker'), 5);
        
        // Register AJAX handlers
        add_action('wp_ajax_almaseo_check_updates_now', array($this, 'ajax_check_updates'));
        add_action('wp_ajax_almaseo_save_update_channel', array($this, 'ajax_save_channel'));
        
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
        
        // Load Plugin Update Checker
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
        
        // Initialize checker
        try {
            $this->updateChecker = Puc_v4_Factory::buildUpdateChecker(
                $endpoint,
                ALMASEO_PLUGIN_FILE,
                'almaseo-seo-playground'
            );
            
            // Add metadata filter
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
        $channel = $this->settings['channel'] ?? 'beta';
        return in_array($channel, array('stable', 'beta'), true) ? $channel : 'beta';
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
        
        $channel = sanitize_text_field($_POST['channel'] ?? 'beta');
        
        if ($this->set_channel($channel)) {
            // Trigger background check with new channel
            $this->check_for_updates();
            
            wp_send_json_success(array(
                'channel' => $channel,
                'message' => sprintf(__('Update channel changed to %s', 'almaseo'), ucfirst($channel))
            ));
        } else {
            wp_send_json_error('Invalid channel');
        }
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
        if (!$screen || strpos($screen->id, 'almaseo') === false) {
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
                        <?php _e('AlmaSEO auto-update check is overdue.', 'almaseo'); ?>
                        <a href="#" class="almaseo-check-updates-now">
                            <?php _e('Check now', 'almaseo'); ?>
                        </a>
                    </p>
                </div>
                <script>
                jQuery(document).ready(function($) {
                    $('#almaseo-update-notice').on('click', '.notice-dismiss', function() {
                        $.post(ajaxurl, {
                            action: 'almaseo_dismiss_update_notice',
                            _ajax_nonce: '<?php echo wp_create_nonce('almaseo_dismiss'); ?>'
                        });
                    });
                    
                    $('.almaseo-check-updates-now').on('click', function(e) {
                        e.preventDefault();
                        $(this).text('Checking...');
                        
                        $.post(ajaxurl, {
                            action: 'almaseo_check_updates_now',
                            nonce: '<?php echo wp_create_nonce('almaseo_updates'); ?>'
                        }, function(response) {
                            if (response.success) {
                                if (response.data.found) {
                                    $('#almaseo-update-notice').html('<p>Update available: v' + response.data.version + '</p>');
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