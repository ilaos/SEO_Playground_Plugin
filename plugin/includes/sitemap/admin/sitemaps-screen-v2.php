<?php
/**
 * Sitemaps Tabbed Interface Controller V2 - With Persistence
 * 
 * @package AlmaSEO
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Sitemaps_Screen_V2 {
    
    private $active_tab = 'overview';
    private $tabs = [];
    
    public function __construct() {
        $this->setup_tabs();
        $this->handle_tab_request();
    }
    
    /**
     * Define available tabs
     */
    private function setup_tabs() {
        $this->tabs = [
            'overview' => [
                'title' => __('Overview', 'almaseo'),
                'icon' => 'dashicons-admin-site-alt3'
            ],
            'types' => [
                'title' => __('Types & Rules', 'almaseo'),
                'icon' => 'dashicons-admin-generic'
            ],
            'international' => [
                'title' => __('International', 'almaseo'),
                'icon' => 'dashicons-translation'
            ],
            'change' => [
                'title' => __('Change Detection', 'almaseo'),
                'icon' => 'dashicons-update'
            ],
            'media' => [
                'title' => __('Media', 'almaseo'),
                'icon' => 'dashicons-format-image'
            ],
            'news' => [
                'title' => __('News', 'almaseo'),
                'icon' => 'dashicons-megaphone'
            ],
            'health' => [
                'title' => __('Health & Scan', 'almaseo'),
                'icon' => 'dashicons-shield-alt'
            ],
            'updates' => [
                'title' => __('Updates & I/O', 'almaseo'),
                'icon' => 'dashicons-update-alt'
            ]
        ];
    }
    
    /**
     * Handle tab request
     */
    private function handle_tab_request() {
        $requested_tab = isset($_GET['tab']) ? sanitize_key($_GET['tab']) : 'overview';
        
        if (isset($this->tabs[$requested_tab])) {
            $this->active_tab = $requested_tab;
        }
    }
    
    /**
     * Render the screen
     */
    public function render() {
        // Get current settings using new helper
        $urls = almaseo_get_index_urls();
        $enabled = $urls['enabled'];
        $takeover = $urls['takeover'];
        $primary_url = $urls['primary'];
        $direct_url = $urls['direct'];
        
        $settings = get_option('almaseo_sitemap_settings', []);
        $generation_mode = $settings['generation_mode'] ?? 'static';
        
        // Get stats
        $stats = $this->get_sitemap_stats();
        
        ?>
        <div id="almaseo-admin" class="almaseo sitemaps-screen">
            
            <!-- Branded Header -->
            <header class="alma-header">
                <div class="brand">
                    <span class="logo-dot"></span>
                    <span class="brand-name">AlmaSEO</span>
                    <span class="divider">•</span>
                    <span class="screen-title"><?php _e('XML Sitemaps', 'almaseo'); ?></span>
                </div>
                <div class="quick-actions">
                    <a href="<?php echo esc_url($primary_url); ?>" target="_blank" 
                       class="button button-secondary" 
                       <?php echo !$enabled ? 'aria-disabled="true" onclick="return false;" style="opacity:0.5;cursor:not-allowed;" title="' . esc_attr__('Enable sitemaps first', 'almaseo') . '"' : ''; ?>>
                        <span class="dashicons dashicons-external"></span>
                        <?php _e('Open', 'almaseo'); ?>
                    </a>
                    <button type="button" class="button button-secondary" id="copy-sitemap-url" 
                            data-url="<?php echo esc_attr($primary_url); ?>" 
                            <?php echo !$enabled ? 'disabled aria-disabled="true" title="' . esc_attr__('Enable sitemaps first', 'almaseo') . '"' : ''; ?>>
                        <span class="dashicons dashicons-clipboard"></span>
                        <?php _e('Copy', 'almaseo'); ?>
                    </button>
                    <button type="button" class="button button-primary" id="rebuild-sitemaps" 
                            <?php echo !$enabled ? 'disabled aria-disabled="true" title="' . esc_attr__('Enable sitemaps first', 'almaseo') . '"' : ''; ?>>
                        <span class="dashicons dashicons-update"></span>
                        <?php _e('Rebuild', 'almaseo'); ?>
                    </button>
                </div>
            </header>
            
            <!-- Status Chips -->
            <div class="alma-status-bar">
                <span class="chip chip-<?php echo $enabled ? 'success' : 'muted'; ?>">
                    <?php echo $enabled ? __('Enabled', 'almaseo') : __('Disabled', 'almaseo'); ?>
                </span>
                <?php if ($takeover): ?>
                <span class="chip chip-warning">
                    <span class="dashicons dashicons-admin-site"></span>
                    <?php _e('Serving /sitemap.xml', 'almaseo'); ?>
                </span>
                <?php else: ?>
                <span class="chip chip-default">
                    <span class="dashicons dashicons-admin-site-alt3"></span>
                    <?php _e('Not taking over', 'almaseo'); ?>
                </span>
                <?php endif; ?>
                <span class="chip chip-info">
                    <?php echo $generation_mode === 'static' ? __('Static', 'almaseo') : __('Dynamic', 'almaseo'); ?>
                </span>
                <span class="chip chip-default" data-live-stat="files">
                    <span class="num"><?php echo number_format($stats['total_files']); ?></span> <?php _e('Files', 'almaseo'); ?>
                </span>
                <span class="chip chip-default" data-live-stat="urls">
                    <span class="num"><?php echo number_format($stats['total_urls']); ?></span> <?php _e('URLs', 'almaseo'); ?>
                </span>
                <?php if (!empty($stats['last_built'])): ?>
                <span class="chip chip-muted">
                    <?php _e('Built', 'almaseo'); ?> <?php echo human_time_diff(strtotime($stats['last_built'])); ?> <?php _e('ago', 'almaseo'); ?>
                </span>
                <?php endif; ?>
            </div>
            
            <!-- Tabs Navigation -->
            <nav class="alma-tabs" role="tablist" aria-label="<?php esc_attr_e('Sitemaps tabs', 'almaseo'); ?>" data-tabs-container>
                <?php foreach ($this->tabs as $tab_key => $tab): ?>
                    <button type="button"
                            class="alma-tab <?php echo ($this->active_tab === $tab_key) ? 'active' : ''; ?>"
                            role="tab"
                            id="alma-tab-<?php echo esc_attr($tab_key); ?>"
                            data-tab="<?php echo esc_attr($tab_key); ?>"
                            aria-selected="<?php echo ($this->active_tab === $tab_key) ? 'true' : 'false'; ?>"
                            aria-controls="alma-panel-<?php echo esc_attr($tab_key); ?>">
                        <span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span>
                        <span class="tab-title"><?php echo esc_html($tab['title']); ?></span>
                    </button>
                <?php endforeach; ?>
            </nav>
            
            <!-- Tab Panels (all rendered, visibility controlled by JS) -->
            <section class="alma-panels">
                <?php 
                // Render all tab panels (hidden by default except active)
                foreach ($this->tabs as $tab_key => $tab): 
                    $is_active = ($tab_key === $this->active_tab);
                ?>
                    <div id="alma-panel-<?php echo esc_attr($tab_key); ?>"
                         class="alma-tabpanel"
                         role="tabpanel"
                         aria-labelledby="alma-tab-<?php echo esc_attr($tab_key); ?>"
                         data-tab="<?php echo esc_attr($tab_key); ?>"
                         <?php echo !$is_active ? 'hidden' : ''; ?>>
                        <?php 
                        // Only load content for overview initially
                        if ($tab_key === 'overview') {
                            $this->render_tab_content($tab_key);
                        } else {
                            // Other tabs will be lazy-loaded
                            echo '<div class="alma-tab-placeholder" data-tab="' . esc_attr($tab_key) . '"></div>';
                        }
                        ?>
                    </div>
                <?php endforeach; ?>
            </section>
            
            <!-- Toast Container with aria-live -->
            <div id="almaseo-toast-container" aria-live="polite" aria-atomic="true"></div>
            
            <!-- Hidden nonce field -->
            <?php wp_nonce_field('almaseo_sitemaps_nonce', 'almaseo_sitemaps_nonce'); ?>
            
            <!-- Boot state for JS -->
            <script>
                window.__ALMA_BOOT = {
                    loaded: { overview: true },
                    nonce: '<?php echo wp_create_nonce('almaseo_sitemaps_nonce'); ?>',
                    ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>'
                };
            </script>
        </div>
        <?php
    }
    
    /**
     * Render tab content
     */
    private function render_tab_content($tab_key) {
        // Load the tab content directly
        
        $partial_file = __DIR__ . '/partials/tabs/' . $tab_key . '.php';
        if (file_exists($partial_file)) {
            include $partial_file;
        } else {
            echo '<div class="alma-empty">' . sprintf(__('Tab content not found: %s', 'almaseo'), esc_html($tab_key)) . '</div>';
        }
    }
    
    /**
     * Get sitemap statistics
     */
    private function get_sitemap_stats() {
        // Simple stats for now
        return [
            'total_files' => 0,
            'total_urls' => 0,
            'last_built' => get_option('almaseo_sitemap_last_built', null)
        ];
    }
}

// Initialize and render if called directly
if (!function_exists('almaseo_render_sitemaps_screen_v2')) {
    function almaseo_render_sitemaps_screen_v2() {
        $screen = new Alma_Sitemaps_Screen_V2();
        $screen->render();
    }
}