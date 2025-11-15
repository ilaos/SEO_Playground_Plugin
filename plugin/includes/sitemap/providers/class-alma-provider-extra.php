<?php
/**
 * AlmaSEO Additional URLs Sitemap Provider
 * 
 * Provides manually added URLs for sitemap inclusion
 * 
 * @package AlmaSEO
 * @since 4.7.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class Alma_Provider_Extra {
    
    /**
     * Settings
     */
    private $settings;
    
    /**
     * Constructor
     */
    public function __construct($settings) {
        $this->settings = $settings;
    }
    
    /**
     * Get maximum number of pages
     */
    public function get_max_pages() {
        $total = $this->get_total_urls();
        $per_page = $this->settings['links_per_sitemap'];
        return (int) ceil($total / $per_page);
    }
    
    /**
     * Get total URLs count
     */
    private function get_total_urls() {
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_additional_urls';
        
        // Check if table exists
        $table_check = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($table_check !== $table) {
            return 0;
        }
        
        return $wpdb->get_var("SELECT COUNT(*) FROM `{$wpdb->prefix}almaseo_additional_urls` WHERE active = 1");
    }
    
    /**
     * Get URLs for a specific page
     */
    public function get_urls($page_num) {
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_additional_urls';
        
        // Check if table exists
        $table_check = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($table_check !== $table) {
            return array();
        }
        
        $per_page = $this->settings['links_per_sitemap'];
        $offset = ($page_num - 1) * $per_page;
        
        $results = $wpdb->get_results($wpdb->prepare("
            SELECT url, priority, changefreq, lastmod
            FROM `{$wpdb->prefix}almaseo_additional_urls`
            WHERE active = 1
            ORDER BY priority DESC, url ASC
            LIMIT %d OFFSET %d
        ", $per_page, $offset));
        
        if (empty($results)) {
            return array();
        }
        
        $urls = array();
        foreach ($results as $row) {
            $url_data = array(
                'loc' => $row->url
            );
            
            if (!empty($row->lastmod)) {
                $url_data['lastmod'] = mysql2date('c', $row->lastmod, false);
            }
            
            if (!empty($row->changefreq)) {
                $url_data['changefreq'] = $row->changefreq;
            }
            
            if ($row->priority !== null && $row->priority != 0.5) {
                $url_data['priority'] = number_format($row->priority, 1);
            }
            
            $urls[] = $url_data;
        }
        
        return $urls;
    }
    
    /**
     * Get sitemap entry
     */
    public function get_sitemap_entry() {
        $total = $this->get_total_urls();
        
        if ($total === 0) {
            return false;
        }
        
        return array(
            'name' => 'additional',
            'lastmod' => $this->get_last_modified()
        );
    }
    
    /**
     * Get last modified date
     */
    private function get_last_modified() {
        global $wpdb;
        $table = $wpdb->prefix . 'almaseo_additional_urls';
        
        $table_check = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $table));
        if ($table_check !== $table) {
            return null;
        }
        
        $lastmod = $wpdb->get_var("
            SELECT MAX(GREATEST(created_at, COALESCE(updated_at, created_at)))
            FROM `{$wpdb->prefix}almaseo_additional_urls`
            WHERE active = 1
        ");
        
        return $lastmod ? mysql2date('c', $lastmod, false) : null;
    }
}

/**
 * Additional URLs Storage Manager
 */
class Alma_Additional_URLs_Storage {
    
    /**
     * Table name
     */
    private static $table = null;
    
    /**
     * Get table name
     */
    public static function get_table_name() {
        global $wpdb;
        if (self::$table === null) {
            self::$table = $wpdb->prefix . 'almaseo_additional_urls';
        }
        return self::$table;
    }
    
    /**
     * Create database table
     */
    public static function create_table() {
        global $wpdb;
        
        $table_name = self::get_table_name();
        $charset_collate = $wpdb->get_charset_collate();
        
        $sql = "CREATE TABLE $table_name (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            url varchar(2048) NOT NULL,
            priority decimal(2,1) DEFAULT 0.5,
            changefreq varchar(20) DEFAULT NULL,
            lastmod datetime DEFAULT NULL,
            active tinyint(1) DEFAULT 1,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY url (url(191)),
            KEY active (active),
            KEY priority (priority)
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }
    
    /**
     * Add a URL
     */
    public static function add_url($url, $priority = 0.5, $changefreq = null, $lastmod = null) {
        global $wpdb;
        
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return new WP_Error('invalid_url', __('Invalid URL', 'almaseo'));
        }
        
        // Validate priority
        $priority = floatval($priority);
        if ($priority < 0 || $priority > 1) {
            $priority = 0.5;
        }
        
        // Validate changefreq
        $valid_changefreq = array('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never');
        if ($changefreq && !in_array($changefreq, $valid_changefreq)) {
            $changefreq = null;
        }
        
        // Format lastmod
        if ($lastmod) {
            $lastmod = date('Y-m-d H:i:s', strtotime($lastmod));
        }
        
        $result = $wpdb->insert(
            self::get_table_name(),
            array(
                'url' => $url,
                'priority' => $priority,
                'changefreq' => $changefreq,
                'lastmod' => $lastmod,
                'active' => 1
            ),
            array('%s', '%f', '%s', '%s', '%d')
        );
        
        if ($result === false) {
            return new WP_Error('db_error', $wpdb->last_error);
        }
        
        return $wpdb->insert_id;
    }
    
    /**
     * Update a URL
     */
    public static function update_url($id, $data) {
        global $wpdb;
        
        $update_data = array();
        $update_format = array();
        
        if (isset($data['url'])) {
            if (!filter_var($data['url'], FILTER_VALIDATE_URL)) {
                return new WP_Error('invalid_url', __('Invalid URL', 'almaseo'));
            }
            $update_data['url'] = $data['url'];
            $update_format[] = '%s';
        }
        
        if (isset($data['priority'])) {
            $priority = floatval($data['priority']);
            if ($priority >= 0 && $priority <= 1) {
                $update_data['priority'] = $priority;
                $update_format[] = '%f';
            }
        }
        
        if (isset($data['changefreq'])) {
            $valid_changefreq = array('always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never');
            if (in_array($data['changefreq'], $valid_changefreq) || $data['changefreq'] === null) {
                $update_data['changefreq'] = $data['changefreq'];
                $update_format[] = '%s';
            }
        }
        
        if (isset($data['lastmod'])) {
            $update_data['lastmod'] = $data['lastmod'] ? date('Y-m-d H:i:s', strtotime($data['lastmod'])) : null;
            $update_format[] = '%s';
        }
        
        if (isset($data['active'])) {
            $update_data['active'] = $data['active'] ? 1 : 0;
            $update_format[] = '%d';
        }
        
        if (empty($update_data)) {
            return false;
        }
        
        $result = $wpdb->update(
            self::get_table_name(),
            $update_data,
            array('id' => $id),
            $update_format,
            array('%d')
        );
        
        return $result !== false;
    }
    
    /**
     * Delete a URL
     */
    public static function delete_url($id) {
        global $wpdb;
        
        return $wpdb->delete(
            self::get_table_name(),
            array('id' => $id),
            array('%d')
        );
    }
    
    /**
     * Get URLs
     */
    public static function get_urls($args = array()) {
        global $wpdb;
        
        $defaults = array(
            'active' => null,
            'orderby' => 'priority',
            'order' => 'DESC',
            'limit' => 50,
            'offset' => 0
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array();
        if ($args['active'] !== null) {
            $where[] = $wpdb->prepare('active = %d', $args['active']);
        }
        
        $where_sql = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        // Validate orderby
        $valid_orderby = array('id', 'url', 'priority', 'changefreq', 'lastmod', 'created_at');
        $orderby = in_array($args['orderby'], $valid_orderby) ? $args['orderby'] : 'priority';
        
        // Validate order
        $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
        
        // Build the query with proper escaping
        $table_name = self::get_table_name();
        $query = "SELECT * FROM `$table_name` $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";
        
        $sql = $wpdb->prepare($query, $args['limit'], $args['offset']);
        
        return $wpdb->get_results($sql);
    }
    
    /**
     * Get URL by ID
     */
    public static function get_url($id) {
        global $wpdb;
        
        $table_name = self::get_table_name();
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM `$table_name` WHERE id = %d",
            $id
        ));
    }
    
    /**
     * Import from CSV
     */
    public static function import_csv($csv_content) {
        $lines = explode("\n", $csv_content);
        $headers = str_getcsv(array_shift($lines));
        
        // Map headers
        $header_map = array();
        foreach ($headers as $i => $header) {
            $header = strtolower(trim($header));
            if (in_array($header, array('url', 'priority', 'changefreq', 'lastmod'))) {
                $header_map[$header] = $i;
            }
        }
        
        if (!isset($header_map['url'])) {
            return new WP_Error('missing_url_column', __('CSV must have a URL column', 'almaseo'));
        }
        
        $imported = 0;
        $errors = array();
        
        foreach ($lines as $line_num => $line) {
            if (empty(trim($line))) {
                continue;
            }
            
            $data = str_getcsv($line);
            
            $url = isset($data[$header_map['url']]) ? trim($data[$header_map['url']]) : '';
            if (empty($url)) {
                continue;
            }
            
            $priority = isset($header_map['priority']) && isset($data[$header_map['priority']]) 
                ? floatval($data[$header_map['priority']]) : 0.5;
            
            $changefreq = isset($header_map['changefreq']) && isset($data[$header_map['changefreq']]) 
                ? trim($data[$header_map['changefreq']]) : null;
            
            $lastmod = isset($header_map['lastmod']) && isset($data[$header_map['lastmod']]) 
                ? trim($data[$header_map['lastmod']]) : null;
            
            $result = self::add_url($url, $priority, $changefreq, $lastmod);
            
            if (is_wp_error($result)) {
                $errors[] = sprintf('Line %d: %s', $line_num + 2, $result->get_error_message());
            } else {
                $imported++;
            }
        }
        
        return array(
            'imported' => $imported,
            'errors' => $errors
        );
    }
    
    /**
     * Export to CSV
     */
    public static function export_csv() {
        $urls = self::get_urls(array('limit' => 10000));
        
        $csv = "URL,Priority,Changefreq,Lastmod\n";
        
        foreach ($urls as $url) {
            $csv .= sprintf(
                '"%s","%s","%s","%s"' . "\n",
                str_replace('"', '""', $url->url),
                $url->priority,
                $url->changefreq ?: '',
                $url->lastmod ?: ''
            );
        }
        
        return $csv;
    }
    
    /**
     * Get count
     */
    public static function get_count($active_only = true) {
        global $wpdb;
        
        $where = $active_only ? 'WHERE active = 1' : '';
        $table_name = self::get_table_name();
        
        return (int) $wpdb->get_var(
            "SELECT COUNT(*) FROM `$table_name` $where"
        );
    }
}