<?php
/**
 * AlmaSEO Sitemap Diff Report
 * 
 * Tracks changes between sitemap snapshots
 * 
 * @package AlmaSEO
 * @since 4.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Sitemap_Diff {
    
    /**
     * Upload directory for snapshots
     */
    private static $upload_dir = null;
    
    /**
     * Get upload directory
     */
    private static function get_upload_dir() {
        if (self::$upload_dir === null) {
            $upload = wp_upload_dir();
            self::$upload_dir = $upload['basedir'] . '/almaseo/sitemaps';
            
            // Create directory if it doesn't exist
            if (!file_exists(self::$upload_dir)) {
                wp_mkdir_p(self::$upload_dir);
            }
        }
        
        return self::$upload_dir;
    }
    
    /**
     * Create a snapshot of current sitemap state
     */
    public static function snapshot() {
        $urls = array();
        $manager = Alma_Sitemap_Manager::get_instance();
        $providers = $manager->get_providers();
        
        // Collect all URLs with lastmod
        foreach ($providers as $provider) {
            $max_pages = $provider->get_max_pages();
            for ($page = 1; $page <= $max_pages; $page++) {
                $page_urls = $provider->get_urls($page);
                foreach ($page_urls as $url_data) {
                    $urls[] = array(
                        'url' => $url_data['loc'],
                        'lastmod' => $url_data['lastmod'] ?? null
                    );
                }
            }
        }
        
        // Include additional URLs if provider exists
        if (class_exists('Alma_Provider_Extra')) {
            $settings = get_option('almaseo_sitemap_settings', array());
            $extra_provider = new Alma_Provider_Extra($settings);
            $max_pages = $extra_provider->get_max_pages();
            for ($page = 1; $page <= $max_pages; $page++) {
                $page_urls = $extra_provider->get_urls($page);
                foreach ($page_urls as $url_data) {
                    $urls[] = array(
                        'url' => $url_data['loc'],
                        'lastmod' => $url_data['lastmod'] ?? null
                    );
                }
            }
        }
        
        // Sort for consistent ordering
        usort($urls, function($a, $b) {
            return strcmp($a['url'], $b['url']);
        });
        
        // Rotate existing snapshots
        self::rotate_snapshots();
        
        // Save new snapshot
        $dir = self::get_upload_dir();
        $json = json_encode($urls, JSON_UNESCAPED_SLASHES);
        $compressed = gzencode($json, 9);
        
        $result = file_put_contents($dir . '/snapshot.json.gz', $compressed);
        
        if ($result === false) {
            return array(
                'success' => false,
                'message' => __('Failed to save snapshot', 'almaseo')
            );
        }
        
        // Update metadata
        update_option('almaseo_sitemap_last_snapshot', array(
            'timestamp' => time(),
            'url_count' => count($urls),
            'file_size' => strlen($compressed)
        ));
        
        return array(
            'success' => true,
            'message' => __('Snapshot created', 'almaseo'),
            'urls' => count($urls),
            'size' => strlen($compressed)
        );
    }
    
    /**
     * Rotate snapshot files
     */
    private static function rotate_snapshots() {
        $dir = self::get_upload_dir();
        
        // Keep up to 5 snapshots
        for ($i = 4; $i >= 1; $i--) {
            $old = $dir . '/snapshot.' . ($i - 1) . '.json.gz';
            $new = $dir . '/snapshot.' . $i . '.json.gz';
            
            if (file_exists($old)) {
                if (file_exists($new)) {
                    unlink($new);
                }
                rename($old, $new);
            }
        }
        
        // Move current to .1
        if (file_exists($dir . '/snapshot.json.gz')) {
            rename($dir . '/snapshot.json.gz', $dir . '/snapshot.1.json.gz');
        }
    }
    
    /**
     * Compare current state with previous snapshot
     */
    public static function compare() {
        $dir = self::get_upload_dir();
        $current_file = $dir . '/snapshot.json.gz';
        $previous_file = $dir . '/snapshot.1.json.gz';
        
        // Create current snapshot if doesn't exist
        if (!file_exists($current_file)) {
            self::snapshot();
        }
        
        // Load snapshots
        $current = self::load_snapshot($current_file);
        $previous = self::load_snapshot($previous_file);
        
        if ($current === false) {
            return array(
                'success' => false,
                'message' => __('Failed to load current snapshot', 'almaseo')
            );
        }
        
        if ($previous === false) {
            // No previous snapshot, everything is "new"
            $added = $current;
            $removed = array();
            $changed = array();
        } else {
            // Build lookup maps
            $current_map = array();
            foreach ($current as $item) {
                $current_map[$item['url']] = $item['lastmod'];
            }
            
            $previous_map = array();
            foreach ($previous as $item) {
                $previous_map[$item['url']] = $item['lastmod'];
            }
            
            // Find added URLs
            $added = array();
            foreach ($current as $item) {
                if (!isset($previous_map[$item['url']])) {
                    $added[] = $item;
                }
            }
            
            // Find removed URLs
            $removed = array();
            foreach ($previous as $item) {
                if (!isset($current_map[$item['url']])) {
                    $removed[] = $item;
                }
            }
            
            // Find changed URLs
            $changed = array();
            foreach ($current as $item) {
                if (isset($previous_map[$item['url']])) {
                    $prev_lastmod = $previous_map[$item['url']];
                    if ($item['lastmod'] !== $prev_lastmod) {
                        $changed[] = array(
                            'url' => $item['url'],
                            'old_lastmod' => $prev_lastmod,
                            'new_lastmod' => $item['lastmod']
                        );
                    }
                }
            }
        }
        
        // Save CSVs
        self::save_csv($added, 'added.csv', array('URL', 'Last Modified'));
        self::save_csv($removed, 'removed.csv', array('URL', 'Last Modified'));
        self::save_csv($changed, 'changed.csv', array('URL', 'Old Last Modified', 'New Last Modified'));
        
        // Save summary
        $summary = array(
            'timestamp' => time(),
            'added' => count($added),
            'removed' => count($removed),
            'changed' => count($changed),
            'sample_added' => array_slice($added, 0, 5),
            'sample_removed' => array_slice($removed, 0, 5),
            'sample_changed' => array_slice($changed, 0, 5)
        );
        
        update_option('almaseo_sitemap_diff', $summary);
        
        return array(
            'success' => true,
            'summary' => $summary
        );
    }
    
    /**
     * Load a snapshot file
     */
    private static function load_snapshot($file) {
        if (!file_exists($file)) {
            return false;
        }
        
        $compressed = file_get_contents($file);
        if ($compressed === false) {
            return false;
        }
        
        $json = gzdecode($compressed);
        if ($json === false) {
            return false;
        }
        
        $data = json_decode($json, true);
        if ($data === null) {
            return false;
        }
        
        return $data;
    }
    
    /**
     * Save data as CSV
     */
    private static function save_csv($data, $filename, $headers) {
        $dir = self::get_upload_dir();
        $filepath = $dir . '/' . $filename;
        
        $csv = implode(',', $headers) . "\n";
        
        foreach ($data as $row) {
            $values = array();
            
            if (isset($row['url'])) {
                $values[] = '"' . str_replace('"', '""', $row['url']) . '"';
            }
            
            if (isset($row['lastmod'])) {
                $values[] = '"' . ($row['lastmod'] ?: '') . '"';
            }
            
            if (isset($row['old_lastmod'])) {
                $values[] = '"' . ($row['old_lastmod'] ?: '') . '"';
            }
            
            if (isset($row['new_lastmod'])) {
                $values[] = '"' . ($row['new_lastmod'] ?: '') . '"';
            }
            
            $csv .= implode(',', $values) . "\n";
        }
        
        file_put_contents($filepath, $csv);
    }
    
    /**
     * Get diff summary
     */
    public static function get_summary() {
        return get_option('almaseo_sitemap_diff', array());
    }
    
    /**
     * Export diff CSV
     */
    public static function export_csv($type) {
        $dir = self::get_upload_dir();
        $files = array(
            'added' => $dir . '/added.csv',
            'removed' => $dir . '/removed.csv',
            'changed' => $dir . '/changed.csv'
        );
        
        if (!isset($files[$type]) || !file_exists($files[$type])) {
            return false;
        }
        
        return file_get_contents($files[$type]);
    }
    
    /**
     * Clear old snapshots
     */
    public static function cleanup() {
        $dir = self::get_upload_dir();
        
        // Keep only last 5 snapshots
        for ($i = 6; $i <= 10; $i++) {
            $file = $dir . '/snapshot.' . $i . '.json.gz';
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
}