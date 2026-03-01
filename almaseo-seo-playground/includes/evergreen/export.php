<?php
/**
 * AlmaSEO Evergreen - Export Functionality
 * 
 * @package AlmaSEO
 * @subpackage Evergreen
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Initialize export handlers
 */
function almaseo_eg_init_export() {
    add_action('wp_ajax_almaseo_eg_export_csv', 'almaseo_eg_handle_csv_export');
    add_action('wp_ajax_almaseo_eg_export_pdf', 'almaseo_eg_handle_pdf_export');
}
add_action('init', 'almaseo_eg_init_export');

/**
 * Handle CSV export
 */
function almaseo_eg_handle_csv_export() {
    // Check nonce and permissions
    check_ajax_referer('almaseo_eg_ajax', 'nonce');
    
    if (!current_user_can('read')) {
        wp_die('Unauthorized');
    }
    
    // Get filters from request
    $post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : 'all';
    $status_filter = isset($_GET['status_filter']) ? sanitize_key($_GET['status_filter']) : 'all';
    
    // Get all posts (no pagination for export)
    $posts_data = almaseo_eg_get_export_data($post_type, $status_filter);
    
    // Set headers for CSV download
    $filename = 'evergreen-content-' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Add BOM for Excel UTF-8 compatibility
    echo "\xEF\xBB\xBF";
    
    // Open output stream
    $output = fopen('php://output', 'w');
    
    // Write headers
    fputcsv($output, array(
        __('Post Title', 'almaseo'),
        __('Post Type', 'almaseo'),
        __('Status', 'almaseo'),
        __('Last Updated', 'almaseo'),
        __('Days Since Update', 'almaseo'),
        __('90-Day Traffic Trend (%)', 'almaseo'),
        __('Current Clicks (90d)', 'almaseo'),
        __('Previous Clicks (90d)', 'almaseo'),
        __('URL', 'almaseo'),
        __('Edit URL', 'almaseo')
    ));
    
    // Write data rows
    foreach ($posts_data as $post) {
        fputcsv($output, array(
            $post['title'],
            $post['post_type'],
            ucfirst($post['status']),
            $post['last_updated'],
            $post['days_ago'],
            $post['trend'] !== null ? $post['trend'] . '%' : '—',
            $post['clicks_current'],
            $post['clicks_previous'],
            $post['url'],
            $post['edit_url']
        ));
    }
    
    fclose($output);
    exit;
}

/**
 * Handle PDF export (HTML for print)
 */
function almaseo_eg_handle_pdf_export() {
    // Check nonce and permissions
    check_ajax_referer('almaseo_eg_ajax', 'nonce');
    
    if (!current_user_can('read')) {
        wp_die('Unauthorized');
    }
    
    // Get filters
    $post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : 'all';
    $status_filter = isset($_GET['status_filter']) ? sanitize_key($_GET['status_filter']) : 'all';
    $date_range = isset($_GET['date_range']) ? intval($_GET['date_range']) : 12;
    
    // Get data
    $stats = almaseo_eg_get_dashboard_stats($post_type);
    $weekly_data = almaseo_eg_get_weekly_snapshots($date_range);
    $posts_data = almaseo_eg_get_export_data($post_type, $status_filter, 50); // Top 50 for PDF
    
    // Calculate percentages
    $total = max(1, $stats['total']);
    $evergreen_pct = round(($stats['evergreen'] / $total) * 100, 1);
    $watch_pct = round(($stats['watch'] / $total) * 100, 1);
    $stale_pct = round(($stats['stale'] / $total) * 100, 1);
    
    // Generate HTML for print
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo('charset'); ?>">
        <title><?php _e('Evergreen Content Report', 'almaseo'); ?> - <?php echo date('Y-m-d'); ?></title>
        <style>
            * {
                margin: 0;
                padding: 0;
                box-sizing: border-box;
            }
            
            body {
                font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                font-size: 14px;
                line-height: 1.6;
                color: #333;
                padding: 20px;
                max-width: 1200px;
                margin: 0 auto;
            }
            
            h1 {
                font-size: 24px;
                margin-bottom: 10px;
                color: #23282d;
            }
            
            .report-date {
                color: #666;
                margin-bottom: 30px;
            }
            
            .stats-grid {
                display: grid;
                grid-template-columns: repeat(4, 1fr);
                gap: 20px;
                margin-bottom: 40px;
            }
            
            .stat-card {
                border: 1px solid #ddd;
                border-radius: 8px;
                padding: 20px;
                text-align: center;
                background: #fff;
            }
            
            .stat-card.evergreen {
                border-color: #10b981;
                background: #f0fdf4;
            }
            
            .stat-card.watch {
                border-color: #f59e0b;
                background: #fffbeb;
            }
            
            .stat-card.stale {
                border-color: #ef4444;
                background: #fef2f2;
            }
            
            .stat-number {
                font-size: 32px;
                font-weight: bold;
                margin: 10px 0;
            }
            
            .stat-label {
                font-size: 14px;
                color: #666;
            }
            
            .stat-percentage {
                font-size: 18px;
                color: #333;
                margin-top: 5px;
            }
            
            .chart-section {
                margin-bottom: 40px;
                page-break-inside: avoid;
            }
            
            .chart-section h2 {
                font-size: 20px;
                margin-bottom: 20px;
            }
            
            .chart-container {
                background: #f9fafb;
                padding: 20px;
                border-radius: 8px;
                min-height: 300px;
            }
            
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                page-break-inside: auto;
            }
            
            th, td {
                padding: 10px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            
            th {
                background: #f3f4f6;
                font-weight: 600;
            }
            
            tr:nth-child(even) {
                background: #f9fafb;
            }
            
            .status-pill {
                display: inline-block;
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 500;
            }
            
            .status-evergreen {
                background: #d4edda;
                color: #155724;
            }
            
            .status-watch {
                background: #fff3cd;
                color: #856404;
            }
            
            .status-stale {
                background: #f8d7da;
                color: #721c24;
            }
            
            .status-unanalyzed {
                background: #e2e3e5;
                color: #383d41;
            }
            
            .trend-up {
                color: #10b981;
            }
            
            .trend-down {
                color: #ef4444;
            }
            
            @media print {
                body {
                    padding: 0;
                }
                
                .chart-section {
                    page-break-after: always;
                }
                
                table {
                    page-break-inside: auto;
                }
                
                tr {
                    page-break-inside: avoid;
                    page-break-after: auto;
                }
            }
        </style>
        <script>
            window.onload = function() {
                // Auto-trigger print dialog
                window.print();
            };
        </script>
    </head>
    <body>
        <h1><?php _e('Evergreen Content Report', 'almaseo'); ?></h1>
        <div class="report-date">
            <?php echo sprintf(__('Generated: %s', 'almaseo'), date_i18n(get_option('date_format') . ' ' . get_option('time_format'))); ?>
        </div>
        
        <div class="stats-grid">
            <div class="stat-card evergreen">
                <div class="stat-label">🟢 <?php _e('Evergreen', 'almaseo'); ?></div>
                <div class="stat-number"><?php echo esc_html($stats['evergreen']); ?></div>
                <div class="stat-percentage"><?php echo esc_html($evergreen_pct); ?>%</div>
            </div>
            
            <div class="stat-card watch">
                <div class="stat-label">🟡 <?php _e('Watch', 'almaseo'); ?></div>
                <div class="stat-number"><?php echo esc_html($stats['watch']); ?></div>
                <div class="stat-percentage"><?php echo esc_html($watch_pct); ?>%</div>
            </div>
            
            <div class="stat-card stale">
                <div class="stat-label">🔴 <?php _e('Stale', 'almaseo'); ?></div>
                <div class="stat-number"><?php echo esc_html($stats['stale']); ?></div>
                <div class="stat-percentage"><?php echo esc_html($stale_pct); ?>%</div>
            </div>
            
            <div class="stat-card">
                <div class="stat-label">⚪ <?php _e('Unanalyzed', 'almaseo'); ?></div>
                <div class="stat-number"><?php echo esc_html($stats['unanalyzed']); ?></div>
                <div class="stat-percentage">—</div>
            </div>
        </div>
        
        <div class="chart-section">
            <h2><?php _e('Trend Over Time', 'almaseo'); ?></h2>
            <div class="chart-container">
                <canvas id="trend-chart" width="800" height="300"></canvas>
            </div>
        </div>
        
        <h2><?php _e('Content Details', 'almaseo'); ?></h2>
        <table>
            <thead>
                <tr>
                    <th><?php _e('Post Title', 'almaseo'); ?></th>
                    <th><?php _e('Status', 'almaseo'); ?></th>
                    <th><?php _e('Last Updated', 'almaseo'); ?></th>
                    <th><?php _e('90d Trend', 'almaseo'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts_data as $post): ?>
                <tr>
                    <td><?php echo esc_html($post['title']); ?></td>
                    <td>
                        <span class="status-pill status-<?php echo esc_attr($post['status']); ?>">
                            <?php echo ucfirst($post['status']); ?>
                        </span>
                    </td>
                    <td><?php echo esc_html($post['days_ago']); ?> <?php _e('days ago', 'almaseo'); ?></td>
                    <td>
                        <?php if ($post['trend'] !== null): ?>
                            <span class="<?php echo $post['trend'] < 0 ? 'trend-down' : 'trend-up'; ?>">
                                <?php echo $post['trend'] >= 0 ? '+' : ''; ?><?php echo esc_html($post['trend']); ?>%
                            </span>
                        <?php else: ?>
                            —
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <script>
            // Simple chart rendering
            const canvas = document.getElementById('trend-chart');
            const ctx = canvas.getContext('2d');
            const weeklyData = <?php echo json_encode($weekly_data); ?>;
            
            // Basic line chart implementation
            if (weeklyData.length > 0) {
                const width = canvas.width;
                const height = canvas.height;
                const padding = 40;
                const chartWidth = width - (padding * 2);
                const chartHeight = height - (padding * 2);
                
                ctx.strokeStyle = '#ddd';
                ctx.lineWidth = 1;
                ctx.strokeRect(padding, padding, chartWidth, chartHeight);
                
                // Draw data lines
                const colors = {
                    evergreen: '#10b981',
                    watch: '#f59e0b',
                    stale: '#ef4444'
                };
                
                ['evergreen', 'watch', 'stale'].forEach(status => {
                    ctx.strokeStyle = colors[status];
                    ctx.lineWidth = 2;
                    ctx.beginPath();
                    
                    weeklyData.forEach((week, index) => {
                        const x = padding + (index / (weeklyData.length - 1)) * chartWidth;
                        const y = padding + chartHeight - ((week[status] / week.total) * chartHeight);
                        
                        if (index === 0) {
                            ctx.moveTo(x, y);
                        } else {
                            ctx.lineTo(x, y);
                        }
                    });
                    
                    ctx.stroke();
                });
            }
        </script>
    </body>
    </html>
    <?php
    exit;
}

/**
 * Get export data
 */
function almaseo_eg_get_export_data($post_type = 'all', $status_filter = 'all', $limit = -1) {
    // Build post type condition
    $post_types = ($post_type === 'all') 
        ? array('post', 'page')  // Supported post types
        : array($post_type);
    
    // Build query
    $args = array(
        'post_type' => $post_types,
        'post_status' => 'publish',
        'posts_per_page' => $limit,
        'orderby' => 'modified',
        'order' => 'ASC'
    );
    
    // Add status filter
    if ($status_filter !== 'all') {
        if ($status_filter === 'unanalyzed') {
            $args['meta_query'] = array(
                array(
                    'key' => ALMASEO_EG_META_STATUS,
                    'compare' => 'NOT EXISTS'
                )
            );
        } else {
            $args['meta_key'] = ALMASEO_EG_META_STATUS;
            $args['meta_value'] = $status_filter;
        }
    }
    
    $query = new WP_Query($args);
    $export_data = array();
    
    foreach ($query->posts as $post) {
        $status = almaseo_eg_get_status($post->ID);
        $ages = almaseo_get_post_ages($post);
        $clicks = almaseo_eg_get_clicks($post->ID);
        $trend = almaseo_compute_trend($clicks['clicks_90d'], $clicks['clicks_prev90d']);
        
        $export_data[] = array(
            'title' => $post->post_title,
            'post_type' => $post->post_type,
            'status' => $status ?: 'unanalyzed',
            'last_updated' => $post->post_modified,
            'days_ago' => $ages['updated_days'],
            'trend' => ($clicks['clicks_90d'] > 0 || $clicks['clicks_prev90d'] > 0) ? $trend : null,
            'clicks_current' => $clicks['clicks_90d'],
            'clicks_previous' => $clicks['clicks_prev90d'],
            'url' => get_permalink($post->ID),
            'edit_url' => get_edit_post_link($post->ID, 'raw')
        );
    }
    
    return $export_data;
}