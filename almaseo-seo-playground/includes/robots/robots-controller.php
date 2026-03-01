<?php
/**
 * AlmaSEO Robots.txt Controller
 * 
 * Manages robots.txt functionality with virtual and physical file modes
 * 
 * @package AlmaSEO
 * @since 6.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class AlmaSEO_Robots_Controller {
    
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
        $this->init();
    }
    
    /**
     * Initialize
     */
    private function init() {
        // Register settings
        add_action('admin_init', array($this, 'register_settings'));
        
        // Add admin menu
        add_action('admin_menu', array($this, 'add_admin_menu'), 15);
        
        // Enqueue assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_assets'));
        
        // Filter robots.txt output
        add_filter('robots_txt', array($this, 'filter_robots_txt'), 10, 2);
    }
    
    /**
     * Register settings
     */
    public function register_settings() {
        register_setting('almaseo_robots', 'almaseo_robots_content', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_robots_content'),
            'default' => ''
        ));
        
        register_setting('almaseo_robots', 'almaseo_robots_mode', array(
            'type' => 'string',
            'sanitize_callback' => array($this, 'sanitize_robots_mode'),
            'default' => 'virtual'
        ));
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'seo-playground',
            'Robots.txt Editor - AlmaSEO',
            'Robots.txt',
            'manage_options',
            'almaseo-robots',
            array($this, 'render_admin_page')
        );
    }
    
    /**
     * Enqueue assets
     */
    public function enqueue_assets($hook) {
        if ('seo-playground_page_almaseo-robots' !== $hook) {
            return;
        }
        
        // CSS
        wp_enqueue_style(
            'almaseo-robots-editor',
            ALMASEO_URL . 'assets/css/robots-editor.css',
            array(),
            ALMASEO_PLUGIN_VERSION
        );
        
        // JavaScript
        wp_enqueue_script(
            'almaseo-robots-editor',
            ALMASEO_URL . 'assets/js/robots-editor.js',
            array('jquery'),
            ALMASEO_PLUGIN_VERSION,
            true
        );
        
        // Localize script
        wp_localize_script('almaseo-robots-editor', 'almaseoRobots', array(
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('almaseo_robots_nonce'),
            'strings' => array(
                'saving' => __('Saving...', 'almaseo'),
                'saved' => __('Settings saved successfully!', 'almaseo'),
                'error' => __('An error occurred. Please try again.', 'almaseo'),
                'testing' => __('Testing output...', 'almaseo'),
                'confirmReset' => __('Are you sure you want to reset to WordPress default?', 'almaseo'),
                'fileWarning' => __('Warning: A physical robots.txt file exists. WordPress will serve it regardless of virtual mode settings.', 'almaseo')
            )
        ));
    }
    
    /**
     * Render admin page
     */
    public function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'almaseo'));
        }
        
        require_once ALMASEO_PATH . 'admin/pages/robots-editor.php';
    }
    
    /**
     * Filter robots.txt output
     */
    public function filter_robots_txt($output, $public) {
        $mode = get_option('almaseo_robots_mode', 'virtual');
        
        // If physical mode or file exists, don't filter
        if ($mode === 'file' || $this->physical_file_exists()) {
            return $output;
        }
        
        // In virtual mode, return our custom content
        $custom_content = get_option('almaseo_robots_content', '');
        if (!empty($custom_content)) {
            return $custom_content;
        }
        
        return $output;
    }
    
    /**
     * Check if physical robots.txt exists
     */
    public function physical_file_exists() {
        $file_path = $this->get_robots_file_path();
        return file_exists($file_path);
    }
    
    /**
     * Get robots.txt file path
     */
    public function get_robots_file_path() {
        return trailingslashit(ABSPATH) . 'robots.txt';
    }
    
    /**
     * Check if file is writable
     */
    public function is_file_writable() {
        $file_path = $this->get_robots_file_path();
        
        if (file_exists($file_path)) {
            return is_writable($file_path);
        }
        
        // Check if directory is writable for new file
        return is_writable(ABSPATH);
    }
    
    /**
     * Read physical file content
     */
    public function read_physical_file() {
        $file_path = $this->get_robots_file_path();
        
        if (!file_exists($file_path)) {
            return '';
        }
        
        return file_get_contents($file_path);
    }
    
    /**
     * Write physical file
     */
    public function write_physical_file($content) {
        if (!$this->is_file_writable()) {
            return new WP_Error('not_writable', __('The robots.txt file is not writable.', 'almaseo'));
        }
        
        global $wp_filesystem;
        
        if (!function_exists('WP_Filesystem')) {
            require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        
        $creds = request_filesystem_credentials('', '', false, ABSPATH, null);
        
        if (!WP_Filesystem($creds)) {
            return new WP_Error('filesystem_error', __('Could not access filesystem.', 'almaseo'));
        }
        
        $file_path = $this->get_robots_file_path();
        $result = $wp_filesystem->put_contents($file_path, $content, FS_CHMOD_FILE);
        
        if (!$result) {
            return new WP_Error('write_failed', __('Failed to write robots.txt file.', 'almaseo'));
        }
        
        return true;
    }
    
    /**
     * Get default robots.txt content
     */
    public function get_default_content() {
        return "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n\nSitemap: " . home_url('/sitemap.xml');
    }
    
    /**
     * Get WordPress default robots.txt
     */
    public function get_wp_default() {
        // Get what WP would generate without our filter
        remove_filter('robots_txt', array($this, 'filter_robots_txt'), 10);
        
        ob_start();
        do_robots();
        $default = ob_get_clean();
        
        add_filter('robots_txt', array($this, 'filter_robots_txt'), 10, 2);
        
        return $default;
    }
    
    /**
     * Sanitize robots content
     */
    public function sanitize_robots_content($content) {
        // Remove PHP tags and other dangerous content
        $content = str_replace(array('<?php', '<?', '?>', '<%', '%>'), '', $content);
        
        // Normalize line endings
        $content = str_replace("\r\n", "\n", $content);
        $content = str_replace("\r", "\n", $content);
        
        // Limit line length
        $lines = explode("\n", $content);
        $sanitized_lines = array();
        
        foreach ($lines as $line) {
            // Limit line length to prevent abuse
            if (strlen($line) > 500) {
                $line = substr($line, 0, 500);
            }
            $sanitized_lines[] = $line;
        }
        
        return implode("\n", $sanitized_lines);
    }
    
    /**
     * Sanitize robots mode
     */
    public function sanitize_robots_mode($mode) {
        return in_array($mode, array('virtual', 'file'), true) ? $mode : 'virtual';
    }
    
    /**
     * Get current robots.txt output
     */
    public function get_current_output() {
        $mode = get_option('almaseo_robots_mode', 'virtual');
        
        // If physical file exists and we're not in file mode
        if ($this->physical_file_exists() && $mode !== 'file') {
            return $this->read_physical_file();
        }
        
        // Get the filtered output
        ob_start();
        do_robots();
        $output = ob_get_clean();
        
        return $output;
    }
}

// Initialize
AlmaSEO_Robots_Controller::get_instance();