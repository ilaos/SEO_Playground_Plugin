<?php
/**
 * AlmaSEO Schema WP-CLI Commands
 * 
 * @package AlmaSEO
 * @since 2.0.0
 */

if (!defined('WP_CLI')) {
    return;
}

/**
 * Manage AlmaSEO schema functionality.
 */
class AlmaSEO_Schema_CLI_Command {
    
    /**
     * Scan a URL for schema blocks and show what would be kept/removed.
     * 
     * ## OPTIONS
     * 
     * <url>
     * : The URL to scan for schema blocks.
     * 
     * [--format=<format>]
     * : Output format (table, json, csv).
     * ---
     * default: table
     * options:
     *   - table
     *   - json
     *   - csv
     * ---
     * 
     * ## EXAMPLES
     * 
     *     # Scan homepage for schema blocks
     *     $ wp almaseo schema:scan https://example.com
     *     
     *     # Output as JSON
     *     $ wp almaseo schema:scan https://example.com --format=json
     * 
     * @when after_wp_load
     */
    public function scan($args, $assoc_args) {
        list($url) = $args;
        
        // Validate URL
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            WP_CLI::error('Invalid URL provided.');
        }
        
        WP_CLI::log('Fetching URL: ' . $url);
        
        // Fetch the page HTML
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'sslverify' => false
        ));
        
        if (is_wp_error($response)) {
            WP_CLI::error('Failed to fetch URL: ' . $response->get_error_message());
        }
        
        $html = wp_remote_retrieve_body($response);
        
        if (empty($html)) {
            WP_CLI::error('Empty response from URL.');
        }
        
        WP_CLI::log('Analyzing schema blocks...');
        
        // Analyze the HTML
        $scrubber = AlmaSEO_Schema_Scrubber::get_instance();
        $result = $scrubber->analyze_html($html, true);
        
        // Display summary
        WP_CLI::success(sprintf(
            'Found %d JSON-LD blocks: %d would be kept, %d would be removed',
            $result['total_found'],
            $result['kept_count'],
            $result['removed_count']
        ));
        
        $format = isset($assoc_args['format']) ? $assoc_args['format'] : 'table';
        
        // Display details based on format
        switch ($format) {
            case 'json':
                WP_CLI::log(json_encode($result, JSON_PRETTY_PRINT));
                break;
                
            case 'csv':
                $this->output_csv($result);
                break;
                
            default:
                $this->output_table($result);
                break;
        }
    }
    
    /**
     * Toggle exclusive schema mode.
     * 
     * ## OPTIONS
     * 
     * <status>
     * : Enable or disable exclusive schema mode (on/off).
     * 
     * ## EXAMPLES
     * 
     *     # Enable exclusive schema mode
     *     $ wp almaseo schema:exclusive on
     *     
     *     # Disable exclusive schema mode
     *     $ wp almaseo schema:exclusive off
     * 
     * @when after_wp_load
     */
    public function exclusive($args) {
        list($status) = $args;
        
        $enable = false;
        if (in_array(strtolower($status), array('on', 'enable', 'enabled', '1', 'true'))) {
            $enable = true;
        } elseif (!in_array(strtolower($status), array('off', 'disable', 'disabled', '0', 'false'))) {
            WP_CLI::error('Invalid status. Use "on" or "off".');
        }
        
        update_option('almaseo_exclusive_schema_enabled', $enable);
        
        if ($enable) {
            WP_CLI::success('Exclusive schema mode enabled.');
        } else {
            WP_CLI::success('Exclusive schema mode disabled.');
        }
    }
    
    /**
     * Show current schema settings.
     * 
     * ## EXAMPLES
     * 
     *     $ wp almaseo schema:status
     * 
     * @when after_wp_load
     */
    public function status() {
        $exclusive_enabled = get_option('almaseo_exclusive_schema_enabled', false);
        $schema_control = get_option('almaseo_schema_control', array());
        
        WP_CLI::log('Schema Settings:');
        WP_CLI::log('================');
        
        $rows = array(
            array('Setting', 'Value'),
            array('Exclusive Mode', $exclusive_enabled ? 'Enabled' : 'Disabled'),
            array('Keep Breadcrumbs', isset($schema_control['keep_breadcrumbs']) && $schema_control['keep_breadcrumbs'] ? 'Yes' : 'No'),
            array('Keep Product', isset($schema_control['keep_product']) && $schema_control['keep_product'] ? 'Yes' : 'No'),
            array('AMP Compatibility', isset($schema_control['amp_compatibility']) && $schema_control['amp_compatibility'] ? 'Yes' : 'No'),
            array('Logging Enabled', isset($schema_control['enable_logging']) && $schema_control['enable_logging'] ? 'Yes' : 'No'),
            array('Log Limit', isset($schema_control['log_limit']) ? $schema_control['log_limit'] : '50'),
        );
        
        WP_CLI\Utils\format_items('table', $rows, array('Setting', 'Value'));
    }
    
    /**
     * Show schema action log.
     * 
     * ## OPTIONS
     * 
     * [--limit=<limit>]
     * : Number of log entries to show.
     * ---
     * default: 10
     * ---
     * 
     * ## EXAMPLES
     * 
     *     $ wp almaseo schema:log
     *     $ wp almaseo schema:log --limit=20
     * 
     * @when after_wp_load
     */
    public function log($args, $assoc_args) {
        $limit = isset($assoc_args['limit']) ? intval($assoc_args['limit']) : 10;
        $log = get_option('almaseo_schema_log', array());
        
        if (empty($log)) {
            WP_CLI::log('No schema actions logged yet.');
            return;
        }
        
        // Get last N entries
        $entries = array_slice($log, -$limit);
        $entries = array_reverse($entries);
        
        $rows = array();
        foreach ($entries as $entry) {
            $rows[] = array(
                'Time' => date('Y-m-d H:i:s', $entry['time']),
                'URL' => parse_url($entry['url'], PHP_URL_PATH) ?: '/',
                'Removed' => $entry['removed_count'],
                'Kept' => $entry['kept_count'],
                'Types' => implode(', ', $entry['kept_types'])
            );
        }
        
        WP_CLI\Utils\format_items('table', $rows, array('Time', 'URL', 'Removed', 'Kept', 'Types'));
    }
    
    /**
     * Clear the schema action log.
     * 
     * ## EXAMPLES
     * 
     *     $ wp almaseo schema:clear-log
     * 
     * @when after_wp_load
     */
    public function clear_log() {
        delete_option('almaseo_schema_log');
        WP_CLI::success('Schema action log cleared.');
    }
    
    /**
     * Output results as CSV
     */
    private function output_csv($result) {
        // Header
        echo "Category,Type,Reason/Source,Snippet\n";
        
        // Kept blocks
        foreach ($result['kept_blocks'] as $block) {
            echo sprintf(
                "Kept,%s,%s,%s\n",
                $this->csv_escape($block['type']),
                $this->csv_escape($block['reason']),
                $this->csv_escape(substr($block['snippet'], 0, 100))
            );
        }
        
        // Removed blocks
        foreach ($result['removed_blocks'] as $block) {
            echo sprintf(
                "Removed,%s,%s,%s\n",
                $this->csv_escape($block['type']),
                $this->csv_escape($block['source']),
                $this->csv_escape(substr($block['snippet'], 0, 100))
            );
        }
    }
    
    /**
     * Output results as table
     */
    private function output_table($result) {
        if (!empty($result['kept_blocks'])) {
            WP_CLI::log("\nBlocks to Keep:");
            WP_CLI::log("===============");
            
            $rows = array();
            foreach ($result['kept_blocks'] as $block) {
                $rows[] = array(
                    'Type' => $block['type'],
                    'Reason' => $block['reason'],
                    'Snippet' => substr($block['snippet'], 0, 50) . '...'
                );
            }
            
            WP_CLI\Utils\format_items('table', $rows, array('Type', 'Reason', 'Snippet'));
        }
        
        if (!empty($result['removed_blocks'])) {
            WP_CLI::log("\nBlocks to Remove:");
            WP_CLI::log("=================");
            
            $rows = array();
            foreach ($result['removed_blocks'] as $block) {
                $rows[] = array(
                    'Type' => $block['type'],
                    'Source' => $block['source'],
                    'Snippet' => substr($block['snippet'], 0, 50) . '...'
                );
            }
            
            WP_CLI\Utils\format_items('table', $rows, array('Type', 'Source', 'Snippet'));
        }
        
        if (empty($result['kept_blocks']) && empty($result['removed_blocks'])) {
            WP_CLI::log('No schema blocks found.');
        }
    }
    
    /**
     * Escape value for CSV
     */
    private function csv_escape($value) {
        if (strpos($value, ',') !== false || strpos($value, '"') !== false || strpos($value, "\n") !== false) {
            return '"' . str_replace('"', '""', $value) . '"';
        }
        return $value;
    }
}

// Register the command
WP_CLI::add_command('almaseo schema', 'AlmaSEO_Schema_CLI_Command');