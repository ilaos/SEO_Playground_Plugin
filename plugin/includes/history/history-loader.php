<?php
/**
 * AlmaSEO Metadata History - Loader and Database
 * 
 * @package AlmaSEO
 * @subpackage History
 * @since 6.8.2
 */

if (!defined('ABSPATH')) {
    exit;
}

// Define constants
define('ALMASEO_HISTORY_DB_VERSION', '1.0.0');
define('ALMASEO_HISTORY_TABLE', 'almaseo_meta_history');

/**
 * Initialize Metadata History feature
 */
class AlmaSEO_History_Loader {
    
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
        $this->check_database();
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Check and create/update database table
     */
    private function check_database() {
        $installed_version = get_option('almaseo_meta_history_db_version');
        
        if ($installed_version !== ALMASEO_HISTORY_DB_VERSION) {
            $this->create_database_table();
            update_option('almaseo_meta_history_db_version', ALMASEO_HISTORY_DB_VERSION);
        }
    }
    
    /**
     * Create database table
     */
    private function create_database_table() {
        global $wpdb;
        
        $table_name = $wpdb->prefix . ALMASEO_HISTORY_TABLE;
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            post_id BIGINT(20) UNSIGNED NOT NULL,
            version INT(11) UNSIGNED NOT NULL,
            created_at DATETIME NOT NULL,
            user_id BIGINT(20) UNSIGNED NULL,
            source VARCHAR(20) NOT NULL DEFAULT 'auto',
            snapshot_json LONGTEXT NOT NULL,
            snapshot_hash CHAR(40) NOT NULL,
            size_bytes INT(11) UNSIGNED NULL,
            PRIMARY KEY (id),
            KEY post_id (post_id),
            KEY post_version (post_id, version),
            KEY post_hash (post_id, snapshot_hash)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Load dependencies
     */
    private function load_dependencies() {
        require_once dirname(__FILE__) . '/history-capture.php';
        require_once dirname(__FILE__) . '/history-restore.php';
        
        if (is_admin()) {
            require_once dirname(__FILE__) . '/history-ui.php';
        }
    }
    
    /**
     * Initialize hooks
     */
    private function init_hooks() {
        // Capture snapshots on save
        add_action('save_post', array($this, 'capture_on_save'), 25, 2);
        
        // AJAX handlers
        add_action('wp_ajax_almaseo_meta_history_restore', array($this, 'ajax_restore'));
        add_action('wp_ajax_almaseo_meta_history_compare', array($this, 'ajax_compare'));
        add_action('wp_ajax_almaseo_meta_history_snapshot', array($this, 'ajax_create_snapshot'));
        add_action('wp_ajax_almaseo_meta_history_delete', array($this, 'ajax_delete_version'));
        
        // Enqueue assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
    }
    
    /**
     * Capture snapshot on post save
     */
    public function capture_on_save($post_id, $post) {
        // Skip autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check post type
        if (!in_array($post->post_type, array('post', 'page'))) {
            return;
        }
        
        // Skip revisions
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        // Skip if restoring (to avoid loops)
        if (defined('ALMASEO_RESTORING_HISTORY') && ALMASEO_RESTORING_HISTORY) {
            return;
        }
        
        // Capture snapshot
        if (function_exists('almaseo_history_capture_snapshot')) {
            almaseo_history_capture_snapshot($post_id, 'auto');
        }
    }
    
    /**
     * AJAX handler for restore
     */
    public function ajax_restore() {
        check_ajax_referer('almaseo_history_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $version_id = intval($_POST['version_id']);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_die('Insufficient permissions');
        }
        
        if (function_exists('almaseo_history_restore_version')) {
            $result = almaseo_history_restore_version($post_id, $version_id);
            
            if ($result) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error('Failed to restore version');
            }
        } else {
            wp_send_json_error('Restore function not found');
        }
    }
    
    /**
     * AJAX handler for compare
     */
    public function ajax_compare() {
        check_ajax_referer('almaseo_history_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $from_version = intval($_POST['from_version']);
        $to_version = intval($_POST['to_version']);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_die('Insufficient permissions');
        }
        
        if (function_exists('almaseo_history_get_compare_data')) {
            $data = almaseo_history_get_compare_data($post_id, $from_version, $to_version);
            wp_send_json_success($data);
        } else {
            wp_send_json_error('Compare function not found');
        }
    }
    
    /**
     * AJAX handler for manual snapshot
     */
    public function ajax_create_snapshot() {
        check_ajax_referer('almaseo_history_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_die('Insufficient permissions');
        }
        
        if (function_exists('almaseo_history_capture_snapshot')) {
            $version = almaseo_history_capture_snapshot($post_id, 'manual');
            
            if ($version) {
                wp_send_json_success(array(
                    'version' => $version,
                    'message' => __('Snapshot created successfully', 'almaseo')
                ));
            } else {
                wp_send_json_error('No changes detected');
            }
        } else {
            wp_send_json_error('Capture function not found');
        }
    }
    
    /**
     * AJAX handler for delete version
     */
    public function ajax_delete_version() {
        check_ajax_referer('almaseo_history_nonce', 'nonce');
        
        $post_id = intval($_POST['post_id']);
        $version_id = intval($_POST['version_id']);
        
        if (!current_user_can('edit_post', $post_id)) {
            wp_die('Insufficient permissions');
        }
        
        global $wpdb;
        $table_name = $wpdb->prefix . ALMASEO_HISTORY_TABLE;
        
        $deleted = $wpdb->delete(
            $table_name,
            array(
                'id' => $version_id,
                'post_id' => $post_id
            ),
            array('%d', '%d')
        );
        
        if ($deleted) {
            wp_send_json_success('Version deleted');
        } else {
            wp_send_json_error('Failed to delete version');
        }
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        global $post_type;
        
        // Only on post/page edit screens
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        if (!in_array($post_type, array('post', 'page'))) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'almaseo-history',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/css/history.css',
            array(),
            '6.8.2'
        );
        
        // JS
        wp_enqueue_script(
            'almaseo-history',
            plugin_dir_url(dirname(dirname(__FILE__))) . 'assets/js/history.js',
            array('jquery'),
            '6.8.2',
            true
        );
        
        // Localize
        wp_localize_script('almaseo-history', 'almaseoHistory', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('almaseo_history_nonce'),
            'i18n' => array(
                'restore_confirm' => __('Are you sure you want to restore this version?', 'almaseo'),
                'delete_confirm' => __('Are you sure you want to delete this version?', 'almaseo'),
                'restoring' => __('Restoring...', 'almaseo'),
                'restored' => __('Version restored!', 'almaseo'),
                'error' => __('An error occurred', 'almaseo'),
                'no_changes' => __('No changes detected', 'almaseo'),
                'creating_snapshot' => __('Creating snapshot...', 'almaseo'),
                'snapshot_created' => __('Snapshot created!', 'almaseo')
            )
        ));
    }
}

// Initialize
add_action('plugins_loaded', function() {
    if (class_exists('AlmaSEO_History_Loader')) {
        AlmaSEO_History_Loader::get_instance();
    }
}, 30);