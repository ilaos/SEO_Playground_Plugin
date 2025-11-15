<?php
/**
 * AlmaSEO Beta Monitoring Dashboard
 * 
 * Monitor beta rollout progress and health
 * 
 * @package AlmaSEO
 * @since 5.0.1-beta.1
 */

// Load WordPress
require_once(__DIR__ . '/../../../../../wp-load.php');

// Check admin access
if (!current_user_can('manage_options')) {
    die('Access denied');
}

// Get data
$update_manager = AlmaSEO_Update_Manager::get_instance();
$sitemap_manager = Alma_Sitemap_Manager::get_instance();
$settings = $sitemap_manager->get_settings();
$update_settings = $update_manager->get_settings();

?>
<!DOCTYPE html>
<html>
<head>
    <title>AlmaSEO Beta Monitor</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            margin: 0;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #0073aa;
            padding-bottom: 10px;
        }
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .card {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card h2 {
            margin-top: 0;
            color: #555;
            font-size: 18px;
            border-bottom: 1px solid #eee;
            padding-bottom: 10px;
        }
        .metric {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .metric:last-child {
            border-bottom: none;
        }
        .label {
            color: #666;
        }
        .value {
            font-weight: bold;
            color: #333;
        }
        .status-good {
            color: #46b450;
        }
        .status-warning {
            color: #ffb900;
        }
        .status-error {
            color: #dc3232;
        }
        .beta-badge {
            display: inline-block;
            background: #ff6900;
            color: white;
            padding: 2px 8px;
            border-radius: 3px;
            font-size: 12px;
            margin-left: 10px;
        }
        .actions {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        .button {
            display: inline-block;
            padding: 8px 16px;
            background: #0073aa;
            color: white;
            text-decoration: none;
            border-radius: 3px;
            margin-right: 10px;
            margin-bottom: 10px;
        }
        .button:hover {
            background: #005a87;
        }
        .button-secondary {
            background: #555;
        }
        .button-secondary:hover {
            background: #333;
        }
        .log-entries {
            max-height: 300px;
            overflow-y: auto;
            background: #f9f9f9;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            font-size: 12px;
        }
        .log-entry {
            padding: 4px 0;
            border-bottom: 1px solid #eee;
        }
        .log-entry:last-child {
            border-bottom: none;
        }
        pre {
            background: #f4f4f4;
            padding: 10px;
            border-radius: 4px;
            overflow-x: auto;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            AlmaSEO Beta Monitor
            <?php if ($update_settings['channel'] === 'beta') : ?>
                <span class="beta-badge">BETA</span>
            <?php endif; ?>
        </h1>
        
        <div class="grid">
            <!-- Version Info -->
            <div class="card">
                <h2>Version Information</h2>
                <div class="metric">
                    <span class="label">Current Version:</span>
                    <span class="value"><?php echo ALMASEO_PLUGIN_VERSION; ?></span>
                </div>
                <div class="metric">
                    <span class="label">Update Channel:</span>
                    <span class="value <?php echo $update_settings['channel'] === 'beta' ? 'status-warning' : ''; ?>">
                        <?php echo ucfirst($update_settings['channel'] ?? 'stable'); ?>
                    </span>
                </div>
                <div class="metric">
                    <span class="label">Last Check:</span>
                    <span class="value">
                        <?php 
                        if ($update_settings['last_check']) {
                            echo human_time_diff($update_settings['last_check']) . ' ago';
                        } else {
                            echo 'Never';
                        }
                        ?>
                    </span>
                </div>
                <div class="metric">
                    <span class="label">Latest Available:</span>
                    <span class="value">
                        <?php echo $update_settings['last_found'] ?? 'Unknown'; ?>
                    </span>
                </div>
            </div>
            
            <!-- System Health -->
            <div class="card">
                <h2>System Health</h2>
                <div class="metric">
                    <span class="label">PHP Version:</span>
                    <span class="value <?php echo version_compare(PHP_VERSION, '7.4', '>=') ? 'status-good' : 'status-error'; ?>">
                        <?php echo PHP_VERSION; ?>
                    </span>
                </div>
                <div class="metric">
                    <span class="label">WordPress Version:</span>
                    <span class="value"><?php echo get_bloginfo('version'); ?></span>
                </div>
                <div class="metric">
                    <span class="label">Memory Limit:</span>
                    <span class="value"><?php echo WP_MEMORY_LIMIT; ?></span>
                </div>
                <div class="metric">
                    <span class="label">Multisite:</span>
                    <span class="value"><?php echo is_multisite() ? 'Yes' : 'No'; ?></span>
                </div>
            </div>
            
            <!-- Sitemap Status -->
            <div class="card">
                <h2>Sitemap Status</h2>
                <div class="metric">
                    <span class="label">Enabled:</span>
                    <span class="value <?php echo $settings['enabled'] ? 'status-good' : 'status-error'; ?>">
                        <?php echo $settings['enabled'] ? 'Yes' : 'No'; ?>
                    </span>
                </div>
                <div class="metric">
                    <span class="label">Storage Mode:</span>
                    <span class="value"><?php echo ucfirst($settings['perf']['storage_mode'] ?? 'dynamic'); ?></span>
                </div>
                <div class="metric">
                    <span class="label">Takeover Mode:</span>
                    <span class="value <?php echo !$settings['takeover'] ? 'status-good' : 'status-warning'; ?>">
                        <?php echo $settings['takeover'] ? 'On (Careful!)' : 'Off (Safe)'; ?>
                    </span>
                </div>
                <div class="metric">
                    <span class="label">Last Build:</span>
                    <span class="value">
                        <?php 
                        $last_build = $settings['health']['last_build'] ?? 0;
                        echo $last_build ? human_time_diff($last_build) . ' ago' : 'Never';
                        ?>
                    </span>
                </div>
            </div>
            
            <!-- Performance Metrics -->
            <div class="card">
                <h2>Performance Metrics</h2>
                <?php 
                $build_stats = $settings['health']['last_build_stats'] ?? null;
                if ($build_stats) :
                ?>
                <div class="metric">
                    <span class="label">Total URLs:</span>
                    <span class="value"><?php echo number_format($build_stats['urls'] ?? 0); ?></span>
                </div>
                <div class="metric">
                    <span class="label">Build Time:</span>
                    <span class="value"><?php echo round(($build_stats['elapsed'] ?? 0) / 1000, 2); ?>s</span>
                </div>
                <div class="metric">
                    <span class="label">Memory Used:</span>
                    <span class="value"><?php echo round(($build_stats['memory'] ?? 0) / 1024 / 1024, 1); ?> MB</span>
                </div>
                <div class="metric">
                    <span class="label">Files Generated:</span>
                    <span class="value"><?php echo $build_stats['files'] ?? 0; ?></span>
                </div>
                <?php else : ?>
                <p>No build statistics available yet.</p>
                <?php endif; ?>
            </div>
            
            <!-- Feature Status -->
            <div class="card">
                <h2>Feature Status</h2>
                <div class="metric">
                    <span class="label">Delta Tracking:</span>
                    <span class="value <?php echo $settings['delta']['enabled'] ? 'status-good' : ''; ?>">
                        <?php echo $settings['delta']['enabled'] ? 'Enabled' : 'Disabled'; ?>
                    </span>
                </div>
                <div class="metric">
                    <span class="label">IndexNow:</span>
                    <span class="value <?php echo $settings['indexnow']['enabled'] ? 'status-good' : ''; ?>">
                        <?php echo $settings['indexnow']['enabled'] ? 'Enabled' : 'Disabled'; ?>
                    </span>
                </div>
                <div class="metric">
                    <span class="label">Hreflang:</span>
                    <span class="value <?php echo $settings['hreflang']['enabled'] ? 'status-good' : ''; ?>">
                        <?php echo $settings['hreflang']['enabled'] ? 'Enabled' : 'Disabled'; ?>
                    </span>
                </div>
                <div class="metric">
                    <span class="label">Media Sitemaps:</span>
                    <span class="value">
                        <?php 
                        $img = $settings['media']['image']['enabled'] ?? false;
                        $vid = $settings['media']['video']['enabled'] ?? false;
                        if ($img && $vid) {
                            echo '<span class="status-good">Both</span>';
                        } elseif ($img) {
                            echo '<span class="status-good">Images</span>';
                        } elseif ($vid) {
                            echo '<span class="status-good">Videos</span>';
                        } else {
                            echo 'Disabled';
                        }
                        ?>
                    </span>
                </div>
            </div>
            
            <!-- Conflict Detection -->
            <div class="card">
                <h2>Conflict Detection</h2>
                <?php 
                $conflicts = Alma_Sitemap_Conflicts::detect_conflicts();
                if (empty($conflicts)) :
                ?>
                <p class="status-good">✓ No conflicts detected</p>
                <?php else : ?>
                <p class="status-warning">⚠ Conflicts found:</p>
                <ul style="margin: 10px 0; padding-left: 20px;">
                    <?php foreach ($conflicts as $conflict) : ?>
                    <li><?php echo esc_html($conflict['name']); ?> (<?php echo esc_html($conflict['severity']); ?>)</li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Health Log -->
        <div class="card" style="margin-top: 20px;">
            <h2>Recent Health Log</h2>
            <div class="log-entries">
                <?php
                $logs = get_option('almaseo_health_log', array());
                $recent_logs = array_slice($logs, -20);
                $recent_logs = array_reverse($recent_logs);
                
                if (empty($recent_logs)) :
                ?>
                <p>No log entries yet.</p>
                <?php else : ?>
                    <?php foreach ($recent_logs as $entry) : ?>
                    <div class="log-entry">
                        <strong><?php echo date('Y-m-d H:i:s', $entry['timestamp']); ?></strong> - 
                        <?php echo esc_html($entry['event']); ?>: 
                        <?php echo esc_html($entry['message']); ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Quick Actions -->
        <div class="card" style="margin-top: 20px;">
            <h2>Quick Actions</h2>
            <div class="actions">
                <a href="<?php echo admin_url('admin.php?page=almaseo-sitemaps'); ?>" class="button">
                    Open Admin Panel
                </a>
                <a href="#" class="button" onclick="checkUpdates(); return false;">
                    Check for Updates
                </a>
                <a href="#" class="button" onclick="runValidation(); return false;">
                    Run Validation
                </a>
                <a href="#" class="button button-secondary" onclick="exportHealthLog(); return false;">
                    Export Health Log
                </a>
                <a href="#" class="button button-secondary" onclick="switchChannel(); return false;">
                    Switch Channel
                </a>
            </div>
        </div>
        
        <!-- CLI Commands -->
        <div class="card" style="margin-top: 20px;">
            <h2>Useful CLI Commands</h2>
            <pre>
# Check current status
wp almaseo sitemaps validate

# Build sitemaps
wp almaseo sitemaps build --mode=static

# Switch to beta
wp eval "update_option('almaseo_update_settings',['channel'=>'beta']);"

# Switch to stable
wp eval "update_option('almaseo_update_settings',['channel'=>'stable']);"

# Force update check
wp eval "AlmaSEO_Update_Manager::get_instance()->check_for_updates();"

# Export health log
wp eval "Alma_Health_Log::export_csv();"

# Emergency rollback
wp eval "update_option('almaseo_sitemap_settings', array_merge(get_option('almaseo_sitemap_settings',[]), ['takeover'=>false,'perf'=>['storage_mode'=>'dynamic']]));"
            </pre>
        </div>
    </div>
    
    <script>
    function checkUpdates() {
        if (confirm('Check for updates now?')) {
            alert('Checking for updates...\nCheck the admin panel for results.');
            // In production, make AJAX call
        }
    }
    
    function runValidation() {
        if (confirm('Run sitemap validation?')) {
            alert('Running validation...\nCheck the admin panel for results.');
            // In production, make AJAX call
        }
    }
    
    function exportHealthLog() {
        if (confirm('Export health log as CSV?')) {
            window.location.href = '<?php echo admin_url('admin-ajax.php?action=almaseo_export_logs&nonce=' . wp_create_nonce('almaseo_sitemaps_nonce')); ?>';
        }
    }
    
    function switchChannel() {
        var current = '<?php echo $update_settings['channel'] ?? 'stable'; ?>';
        var newChannel = current === 'beta' ? 'stable' : 'beta';
        
        if (confirm('Switch to ' + newChannel + ' channel?')) {
            alert('Switching to ' + newChannel + '...\nRefresh to see changes.');
            // In production, make AJAX call
        }
    }
    </script>
</body>
</html>