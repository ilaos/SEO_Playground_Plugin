<?php
/**
 * AlmaSEO Sitemap Static Writer
 * 
 * Handles static sitemap generation with streaming XML and gzip support
 * 
 * @package AlmaSEO
 * @since 4.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Sitemap_Writer {
    
    /**
     * Settings
     */
    private $settings;
    
    /**
     * Upload directory info
     */
    private $upload_dir;
    
    /**
     * Sitemap storage path
     */
    private $storage_path;
    
    /**
     * Current build directory
     */
    private $build_dir;
    
    /**
     * File handles for streaming
     */
    private $handles = array();
    
    /**
     * Current file counters
     */
    private $counters = array(
        'urls' => 0,
        'files' => 0,
        'bytes' => 0
    );
    
    /**
     * Build statistics
     */
    private $stats = array(
        'started' => 0,
        'finished' => 0,
        'duration_ms' => 0,
        'files' => 0,
        'urls' => 0,
        'by_provider' => array()
    );
    
    /**
     * Manifest data
     */
    private $manifest = array(
        'generated_at' => 0,
        'files' => array(),
        'total_urls' => 0
    );
    
    /**
     * Max URLs per file (50,000 per sitemap protocol)
     */
    const MAX_URLS_PER_FILE = 50000;
    
    /**
     * Max file size (50MB uncompressed)
     */
    const MAX_FILE_SIZE = 52428800; // 50MB
    
    /**
     * Chunk size for pagination
     */
    const CHUNK_SIZE = 1000;
    
    /**
     * Lock expiration time (15 minutes)
     */
    const LOCK_EXPIRATION = 900;
    
    /**
     * Constructor
     */
    public function __construct() {
        $this->upload_dir = wp_upload_dir();
        $this->storage_path = $this->upload_dir['basedir'] . '/almaseo/sitemaps/';
        $this->ensure_storage_directory();
        $this->settings = get_option('almaseo_sitemap_settings', array());
    }
    
    /**
     * Ensure storage directory exists with proper permissions
     */
    private function ensure_storage_directory() {
        if (!file_exists($this->storage_path)) {
            wp_mkdir_p($this->storage_path);
        }
        
        // Create .htaccess for Apache
        $htaccess = $this->storage_path . '.htaccess';
        if (!file_exists($htaccess)) {
            file_put_contents($htaccess, "Options -Indexes\n<Files *.xml>\n    Header set Content-Type \"application/xml; charset=UTF-8\"\n</Files>\n<Files *.gz>\n    Header set Content-Type \"application/gzip\"\n</Files>\n");
        }
        
        // Create web.config for IIS
        $webconfig = $this->storage_path . 'web.config';
        if (!file_exists($webconfig)) {
            $config = '<?xml version="1.0" encoding="UTF-8"?>
<configuration>
    <system.webServer>
        <staticContent>
            <mimeMap fileExtension=".xml" mimeType="application/xml; charset=UTF-8" />
            <mimeMap fileExtension=".gz" mimeType="application/gzip" />
        </staticContent>
    </system.webServer>
</configuration>';
            file_put_contents($webconfig, $config);
        }
    }
    
    /**
     * Acquire build lock
     */
    public function acquire_lock() {
        $lock = get_option('almaseo_sitemaps_build_lock');
        
        if ($lock && isset($lock['expires']) && $lock['expires'] > time()) {
            return false; // Lock is held
        }
        
        update_option('almaseo_sitemaps_build_lock', array(
            'ts' => microtime(true),
            'expires' => time() + self::LOCK_EXPIRATION
        ), false);
        
        return true;
    }
    
    /**
     * Release build lock
     */
    public function release_lock() {
        delete_option('almaseo_sitemaps_build_lock');
    }
    
    /**
     * Check if build is locked
     */
    public function is_locked() {
        $lock = get_option('almaseo_sitemaps_build_lock');
        return $lock && isset($lock['expires']) && $lock['expires'] > time();
    }
    
    /**
     * Start new build
     */
    public function start_build() {
        if (!$this->acquire_lock()) {
            return new WP_Error('build_locked', 'Build already in progress');
        }
        
        $this->stats['started'] = microtime(true);
        
        // Create timestamped build directory
        $timestamp = date('Ymd_His');
        $this->build_dir = $this->storage_path . 'build_' . $timestamp . '/';
        wp_mkdir_p($this->build_dir);
        
        // Reset counters
        $this->counters = array('urls' => 0, 'files' => 0, 'bytes' => 0);
        $this->manifest = array(
            'generated_at' => time(),
            'files' => array(),
            'total_urls' => 0
        );
        
        return true;
    }
    
    /**
     * Write sitemap index
     */
    public function write_index($sitemaps) {
        $filename = 'almaseo-sitemap.xml';
        $filepath = $this->build_dir . $filename;
        
        $handle = fopen($filepath, 'w');
        if (!$handle) {
            return false;
        }
        
        // Write XML header
        fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($handle, '<?xml-stylesheet type="text/xsl" href="' . esc_url(home_url('/almaseo-sitemap.xsl')) . '"?>' . "\n");
        fwrite($handle, '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n");
        
        // Write sitemap entries
        foreach ($sitemaps as $sitemap) {
            fwrite($handle, "\t<sitemap>\n");
            fwrite($handle, "\t\t<loc>" . esc_url($sitemap['loc']) . "</loc>\n");
            if (!empty($sitemap['lastmod'])) {
                fwrite($handle, "\t\t<lastmod>" . esc_html($sitemap['lastmod']) . "</lastmod>\n");
            }
            fwrite($handle, "\t</sitemap>\n");
        }
        
        fwrite($handle, '</sitemapindex>');
        fclose($handle);
        
        // Create gzip version
        $this->create_gzip($filepath);
        
        // Add to manifest
        $this->add_to_manifest($filename, $filepath, count($sitemaps));
        
        return true;
    }
    
    /**
     * Start writing a child sitemap
     */
    public function start_sitemap($name, $provider = null) {
        $filename = 'almaseo-sitemap-' . $name . '-1.xml';
        $filepath = $this->build_dir . $filename;
        
        $handle = fopen($filepath, 'w');
        if (!$handle) {
            return false;
        }
        
        // Store handle for streaming
        $this->handles[$name] = array(
            'handle' => $handle,
            'filepath' => $filepath,
            'filename' => $filename,
            'urls' => 0,
            'bytes' => 0,
            'part' => 1,
            'provider' => $provider
        );
        
        // Write XML header
        fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
        fwrite($handle, '<?xml-stylesheet type="text/xsl" href="' . esc_url(home_url('/almaseo-sitemap.xsl')) . '"?>' . "\n");
        fwrite($handle, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"');
        fwrite($handle, ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n");
        
        $this->handles[$name]['bytes'] = ftell($handle);
        
        return true;
    }
    
    /**
     * Write URL to sitemap (streaming)
     */
    public function write_url($name, $url_data) {
        if (!isset($this->handles[$name])) {
            return false;
        }
        
        $info = &$this->handles[$name];
        
        // Check if we need to roll to next file
        if ($info['urls'] >= self::MAX_URLS_PER_FILE || $info['bytes'] >= self::MAX_FILE_SIZE) {
            $this->close_sitemap($name);
            
            // Start new part
            $info['part']++;
            $filename = 'almaseo-sitemap-' . $name . '-' . $info['part'] . '.xml';
            $filepath = $this->build_dir . $filename;
            
            $handle = fopen($filepath, 'w');
            if (!$handle) {
                return false;
            }
            
            $info['handle'] = $handle;
            $info['filepath'] = $filepath;
            $info['filename'] = $filename;
            $info['urls'] = 0;
            $info['bytes'] = 0;
            $info['image_count'] = 0;
            $info['video_count'] = 0;
            
            // Write header for new file
            fwrite($handle, '<?xml version="1.0" encoding="UTF-8"?>' . "\n");
            fwrite($handle, '<?xml-stylesheet type="text/xsl" href="' . esc_url(home_url('/almaseo-sitemap.xsl')) . '"?>' . "\n");
            fwrite($handle, '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"');
            fwrite($handle, ' xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n");
        }
        
        $handle = $info['handle'];
        
        // Build URL entry
        $xml = "\t<url>\n";
        $xml .= "\t\t<loc>" . esc_url($url_data['loc']) . "</loc>\n";
        
        if (!empty($url_data['lastmod'])) {
            $xml .= "\t\t<lastmod>" . esc_html($url_data['lastmod']) . "</lastmod>\n";
        }
        
        if (!empty($url_data['changefreq'])) {
            $xml .= "\t\t<changefreq>" . esc_html($url_data['changefreq']) . "</changefreq>\n";
        }
        
        if (isset($url_data['priority'])) {
            $xml .= "\t\t<priority>" . number_format($url_data['priority'], 1) . "</priority>\n";
        }
        
        // Add images if present and track count
        if (!empty($url_data['images'])) {
            if (!isset($info['image_count'])) {
                $info['image_count'] = 0;
            }
            foreach ($url_data['images'] as $image) {
                $xml .= "\t\t<image:image>\n";
                $xml .= "\t\t\t<image:loc>" . esc_url($image['loc']) . "</image:loc>\n";
                if (!empty($image['title'])) {
                    $xml .= "\t\t\t<image:title>" . esc_html($image['title']) . "</image:title>\n";
                }
                if (!empty($image['caption'])) {
                    $xml .= "\t\t\t<image:caption>" . esc_html($image['caption']) . "</image:caption>\n";
                }
                $xml .= "\t\t</image:image>\n";
                $info['image_count']++;
            }
        }
        
        // Add videos if present and track count
        if (!empty($url_data['videos'])) {
            if (!isset($info['video_count'])) {
                $info['video_count'] = 0;
            }
            foreach ($url_data['videos'] as $video) {
                $info['video_count']++;
            }
        }
        
        $xml .= "\t</url>\n";
        
        // Write to file
        $written = fwrite($handle, $xml);
        
        $info['urls']++;
        $info['bytes'] += $written;
        $this->counters['urls']++;
        
        return true;
    }
    
    /**
     * Close sitemap file
     */
    public function close_sitemap($name) {
        if (!isset($this->handles[$name])) {
            return false;
        }
        
        $info = $this->handles[$name];
        $handle = $info['handle'];
        
        // Write closing tag
        fwrite($handle, '</urlset>');
        fclose($handle);
        
        // Create gzip version
        $this->create_gzip($info['filepath']);
        
        // Prepare media counts for manifest
        $media_counts = array();
        if (!empty($info['image_count'])) {
            $media_counts['images'] = $info['image_count'];
        }
        if (!empty($info['video_count'])) {
            $media_counts['videos'] = $info['video_count'];
        }
        
        // Add to manifest with media counts
        $this->add_to_manifest($info['filename'], $info['filepath'], $info['urls'], $media_counts);
        
        // Track provider stats
        if ($info['provider']) {
            if (!isset($this->stats['by_provider'][$info['provider']])) {
                $this->stats['by_provider'][$info['provider']] = array(
                    'files' => 0,
                    'urls' => 0,
                    'ms' => 0
                );
            }
            $this->stats['by_provider'][$info['provider']]['files']++;
            $this->stats['by_provider'][$info['provider']]['urls'] += $info['urls'];
        }
        
        // Don't unset if we're rolling to next part
        return true;
    }
    
    /**
     * Create gzip version of file
     */
    private function create_gzip($filepath) {
        $gzfile = $filepath . '.gz';
        
        $fp = fopen($filepath, 'rb');
        $gz = gzopen($gzfile, 'wb9'); // Maximum compression
        
        if ($fp && $gz) {
            while (!feof($fp)) {
                gzwrite($gz, fread($fp, 1024 * 512)); // 512KB chunks
            }
            fclose($fp);
            gzclose($gz);
            return true;
        }
        
        return false;
    }
    
    /**
     * Add file to manifest
     */
    private function add_to_manifest($filename, $filepath, $urls, $media_counts = array()) {
        $url = $this->upload_dir['baseurl'] . '/almaseo/sitemaps/current/' . $filename;
        
        $file_data = array(
            'url' => $url,
            'path' => $filepath,
            'urls' => $urls,
            'bytes' => filesize($filepath)
        );
        
        // Add media counts if present
        if (!empty($media_counts['images'])) {
            $file_data['images'] = $media_counts['images'];
        }
        if (!empty($media_counts['videos'])) {
            $file_data['videos'] = $media_counts['videos'];
        }
        
        $this->manifest['files'][] = $file_data;
        $this->manifest['total_urls'] += $urls;
        $this->counters['files']++;
    }
    
    /**
     * Finalize build
     */
    public function finalize_build() {
        // Close any open sitemaps
        foreach ($this->handles as $name => $info) {
            if (isset($info['handle']) && is_resource($info['handle'])) {
                $this->close_sitemap($name);
            }
        }
        
        // Calculate stats
        $this->stats['finished'] = microtime(true);
        $this->stats['duration_ms'] = round(($this->stats['finished'] - $this->stats['started']) * 1000);
        $this->stats['files'] = $this->counters['files'];
        $this->stats['urls'] = $this->counters['urls'];
        
        // Write manifest
        $manifest_path = $this->build_dir . 'manifest.json';
        file_put_contents($manifest_path, json_encode($this->manifest, JSON_PRETTY_PRINT));
        
        // Update symlink to current build
        $current_link = $this->storage_path . 'current';
        if (is_link($current_link) || file_exists($current_link)) {
            if (is_link($current_link)) {
                unlink($current_link);
            } else {
                $this->recursive_rmdir($current_link);
            }
        }
        symlink($this->build_dir, $current_link);
        
        // Clean old builds (keep last 3)
        $this->cleanup_old_builds();
        
        // Update health stats
        $settings = get_option('almaseo_sitemap_settings', array());
        if (!isset($settings['health'])) {
            $settings['health'] = array();
        }
        $settings['health']['last_build_stats'] = $this->stats;
        update_option('almaseo_sitemap_settings', $settings, false);
        
        // Release lock
        $this->release_lock();
        
        return $this->stats;
    }
    
    /**
     * Clean up old builds
     */
    private function cleanup_old_builds() {
        $builds = glob($this->storage_path . 'build_*', GLOB_ONLYDIR);
        
        if (count($builds) > 3) {
            // Sort by modification time
            usort($builds, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            // Remove old builds
            for ($i = 3; $i < count($builds); $i++) {
                $this->recursive_rmdir($builds[$i]);
            }
        }
    }
    
    /**
     * Recursively remove directory
     */
    private function recursive_rmdir($dir) {
        if (is_dir($dir)) {
            $objects = scandir($dir);
            foreach ($objects as $object) {
                if ($object != "." && $object != "..") {
                    if (is_dir($dir . "/" . $object)) {
                        $this->recursive_rmdir($dir . "/" . $object);
                    } else {
                        unlink($dir . "/" . $object);
                    }
                }
            }
            rmdir($dir);
        }
    }
    
    /**
     * Generate URLs using seek pagination
     */
    public function generate_with_seek($provider_class, $name, $args = array()) {
        if (!$this->start_sitemap($name, $provider_class)) {
            return false;
        }
        
        $provider = new $provider_class($this->settings);
        
        // Check if provider supports seek method
        if (!method_exists($provider, 'get_items_seek')) {
            // Fallback to page-based generation
            return $this->generate_with_pages($provider, $name);
        }
        
        $last_id = 0;
        $provider_start = microtime(true);
        $total_urls = 0;
        
        while (true) {
            // Get next chunk using seek
            $items = $provider->get_items_seek($last_id, self::CHUNK_SIZE, $args);
            
            if (empty($items)) {
                break;
            }
            
            foreach ($items as $item) {
                $url_data = $provider->get_url_data($item);
                if ($url_data) {
                    $this->write_url($name, $url_data);
                    $total_urls++;
                }
                
                // Update last_id for seek
                if (isset($item->ID)) {
                    $last_id = $item->ID;
                } elseif (isset($item->term_id)) {
                    $last_id = $item->term_id;
                } elseif (isset($item->ID)) {
                    $last_id = $item->ID;
                }
            }
            
            // Check if we got less than chunk size (last page)
            if (count($items) < self::CHUNK_SIZE) {
                break;
            }
        }
        
        // Track provider timing
        $provider_time = round((microtime(true) - $provider_start) * 1000);
        if (!isset($this->stats['by_provider'][$provider_class])) {
            $this->stats['by_provider'][$provider_class] = array(
                'files' => 0,
                'urls' => 0,
                'ms' => 0
            );
        }
        $this->stats['by_provider'][$provider_class]['ms'] = $provider_time;
        
        $this->close_sitemap($name);
        
        return $total_urls;
    }
    
    /**
     * Generate sitemap using page-based method (fallback)
     */
    public function generate_with_pages($provider, $name) {
        if (!$this->start_sitemap($name, get_class($provider))) {
            return false;
        }
        
        $provider_start = microtime(true);
        $total_urls = 0;
        $page = 1;
        $per_page = 1000;
        
        while (true) {
            // Get items using traditional pagination
            $items = $provider->get_urls($page);
            
            if (empty($items)) {
                break;
            }
            
            foreach ($items as $item) {
                $this->write_url($name, $item);
                $total_urls++;
            }
            
            $page++;
            
            // Safety check to prevent infinite loops
            if ($page > 1000) {
                break;
            }
        }
        
        $this->close_sitemap($name);
        
        $provider_time = microtime(true) - $provider_start;
        
        // Log completion
        if (defined('WP_CLI') && WP_CLI) {
            \WP_CLI::success(sprintf(
                'Generated %s sitemap: %d URLs in %.2fs',
                $name,
                $total_urls,
                $provider_time
            ));
        }
        
        return true;
    }
    
    /**
     * Get current manifest
     */
    public function get_manifest() {
        $manifest_path = $this->storage_path . 'current/manifest.json';
        
        if (file_exists($manifest_path)) {
            return json_decode(file_get_contents($manifest_path), true);
        }
        
        return null;
    }
    
    /**
     * Get build stats
     */
    public function get_stats() {
        return $this->stats;
    }
}