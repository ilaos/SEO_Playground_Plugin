<?php
/**
 * Plugin Update Checker Library Stub
 * 
 * In production, replace with YahnisElsts/plugin-update-checker v5.x
 * Download from: https://github.com/YahnisElsts/plugin-update-checker
 * 
 * @package Puc
 */

if (!class_exists('Puc_v4_Factory', false)) {
    
    /**
     * Factory class for creating update checkers
     */
    class Puc_v4_Factory {
        
        /**
         * Create an update checker instance
         * 
         * @param string $metadataUrl URL to check for updates
         * @param string $pluginFile Main plugin file path
         * @param string $slug Plugin slug
         * @return Puc_v4p13_Plugin_UpdateChecker
         */
        public static function buildUpdateChecker($metadataUrl, $pluginFile, $slug = '') {
            return new Puc_v4p13_Plugin_UpdateChecker($metadataUrl, $pluginFile, $slug);
        }
    }
    
    /**
     * Main update checker class
     */
    class Puc_v4p13_Plugin_UpdateChecker {
        
        protected $metadataUrl;
        protected $pluginFile;
        protected $slug;
        protected $updateState;
        protected $lastCheck;
        protected $update = null;
        protected $resultFilters = array();
        
        /**
         * Constructor
         */
        public function __construct($metadataUrl, $pluginFile, $slug = '') {
            $this->metadataUrl = $metadataUrl;
            $this->pluginFile = $pluginFile;
            $this->slug = $slug ?: dirname(plugin_basename($pluginFile));
            
            // Load saved state
            $this->updateState = get_option('puc_update_state_' . $this->slug, array());
            
            // Hook into WordPress update system
            add_filter('pre_set_site_transient_update_plugins', array($this, 'injectUpdate'));
            add_filter('plugins_api', array($this, 'injectInfo'), 20, 3);
            
            // Check periodically
            $this->maybeCheckForUpdates();
        }
        
        /**
         * Check for updates
         * 
         * @return object|null Update info or null
         */
        public function checkForUpdates() {
            // Fetch update info from remote
            $response = wp_remote_get($this->metadataUrl, array(
                'timeout' => 10,
                'headers' => array(
                    'Accept' => 'application/json',
                    'User-Agent' => 'AlmaSEO-UpdateChecker/1.0'
                )
            ));
            
            if (is_wp_error($response)) {
                return null;
            }
            
            $body = wp_remote_retrieve_body($response);
            $data = json_decode($body, true);
            
            if (!$data || !isset($data['version'])) {
                return null;
            }
            
            // Create update object
            $update = (object) array(
                'id' => $this->slug,
                'slug' => $this->slug,
                'plugin' => plugin_basename($this->pluginFile),
                'new_version' => $data['version'],
                'url' => $data['homepage'] ?? '',
                'package' => $data['download_url'] ?? '',
                'icons' => $data['icons'] ?? array(),
                'banners' => $data['banners'] ?? array(),
                'banners_rtl' => $data['banners_rtl'] ?? array(),
                'tested' => $data['tested'] ?? '',
                'requires_php' => $data['requires_php'] ?? '',
                'compatibility' => new stdClass(),
                'sections' => $data['sections'] ?? array()
            );
            
            // Apply filters
            foreach ($this->resultFilters as $filter) {
                $update = call_user_func($filter, $update);
            }
            
            // Cache the result
            $this->update = $update;
            $this->lastCheck = time();
            
            // Save state
            $this->updateState = array(
                'lastCheck' => $this->lastCheck,
                'checkedVersion' => $data['version'],
                'update' => $update
            );
            update_option('puc_update_state_' . $this->slug, $this->updateState);
            
            return $update;
        }
        
        /**
         * Get cached update if available
         * 
         * @return object|null
         */
        public function getUpdate() {
            if ($this->update === null && !empty($this->updateState['update'])) {
                $this->update = $this->updateState['update'];
            }
            return $this->update;
        }
        
        /**
         * Inject update into WordPress transient
         */
        public function injectUpdate($transient) {
            $update = $this->getUpdate();
            
            if ($update && version_compare($update->new_version, $this->getInstalledVersion(), '>')) {
                if (!isset($transient->response)) {
                    $transient->response = array();
                }
                $transient->response[plugin_basename($this->pluginFile)] = $update;
            }
            
            return $transient;
        }
        
        /**
         * Inject plugin info for "View details" popup
         */
        public function injectInfo($result, $action, $args) {
            if ($action !== 'plugin_information' || !isset($args->slug) || $args->slug !== $this->slug) {
                return $result;
            }
            
            $update = $this->getUpdate();
            if (!$update) {
                return $result;
            }
            
            return (object) array(
                'name' => 'AlmaSEO SEO Playground',
                'slug' => $this->slug,
                'version' => $update->new_version,
                'author' => '<a href="https://almaseo.com">AlmaSEO</a>',
                'homepage' => 'https://almaseo.com',
                'sections' => $update->sections,
                'download_link' => $update->package,
                'tested' => $update->tested,
                'requires' => $update->requires ?? '5.8',
                'requires_php' => $update->requires_php ?? '7.4',
                'last_updated' => date('Y-m-d'),
                'banners' => $update->banners ?? array()
            );
        }
        
        /**
         * Add a result filter
         */
        public function addResultFilter($callback) {
            $this->resultFilters[] = $callback;
            return $this;
        }
        
        /**
         * Maybe check for updates based on schedule
         */
        protected function maybeCheckForUpdates() {
            $lastCheck = $this->updateState['lastCheck'] ?? 0;
            $checkInterval = 12 * HOUR_IN_SECONDS; // 12 hours
            
            if (time() - $lastCheck > $checkInterval) {
                $this->checkForUpdates();
            }
        }
        
        /**
         * Get installed plugin version
         */
        protected function getInstalledVersion() {
            if (!function_exists('get_plugin_data')) {
                require_once ABSPATH . 'wp-admin/includes/plugin.php';
            }
            
            $pluginData = get_plugin_data($this->pluginFile, false, false);
            return $pluginData['Version'] ?? '0.0.0';
        }
    }
}