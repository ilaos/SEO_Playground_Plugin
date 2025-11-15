<?php
/**
 * Overview Tab - Polished Version
 * 
 * @package AlmaSEO
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get settings and stats
$settings = get_option('almaseo_sitemap_settings', []);
$enabled = !empty($settings['enabled']);
$generation_mode = $settings['generation_mode'] ?? 'static';
$build_stats = $settings['health']['last_build_stats'] ?? [];

// Calculate stats
$total_files = isset($build_stats['files']) ? $build_stats['files'] : 0;
$total_urls = isset($build_stats['urls']) ? $build_stats['urls'] : 0;
$last_built = isset($build_stats['finished']) ? $build_stats['finished'] : 0;

?>

<div class="almaseo-overview-tab">
    
    <!-- Compact Stats Card -->
    <div class="almaseo-card almaseo-stats-card">
        <h2><?php _e('Sitemap Statistics', 'almaseo'); ?></h2>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-icon dashicons dashicons-media-text"></span>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($total_files); ?></div>
                    <div class="stat-label"><?php _e('Files', 'almaseo'); ?></div>
                </div>
            </div>
            <div class="stat-item">
                <span class="stat-icon dashicons dashicons-admin-links"></span>
                <div class="stat-content">
                    <div class="stat-value"><?php echo number_format($total_urls); ?></div>
                    <div class="stat-label"><?php _e('URLs', 'almaseo'); ?></div>
                </div>
            </div>
            <div class="stat-item">
                <span class="stat-icon dashicons dashicons-clock"></span>
                <div class="stat-content">
                    <div class="stat-value">
                        <?php 
                        if ($last_built) {
                            echo human_time_diff($last_built) . ' ' . __('ago', 'almaseo');
                        } else {
                            _e('Never', 'almaseo');
                        }
                        ?>
                    </div>
                    <div class="stat-label"><?php _e('Last Built', 'almaseo'); ?></div>
                </div>
            </div>
            <div class="stat-item">
                <span class="stat-icon dashicons dashicons-admin-settings"></span>
                <div class="stat-content">
                    <div class="stat-value"><?php echo ucfirst($generation_mode); ?></div>
                    <div class="stat-label"><?php _e('Mode', 'almaseo'); ?></div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Status -->
    <div class="almaseo-card">
        <h2><?php _e('Quick Status', 'almaseo'); ?></h2>
        
        <div class="status-grid">
            <div class="status-item">
                <span class="status-label"><?php _e('Sitemap Status:', 'almaseo'); ?></span>
                <span class="status-value <?php echo $enabled ? 'status-enabled' : 'status-disabled'; ?>">
                    <?php echo $enabled ? __('Enabled', 'almaseo') : __('Disabled', 'almaseo'); ?>
                </span>
            </div>
            
            <div class="status-item">
                <span class="status-label"><?php _e('Primary URL:', 'almaseo'); ?></span>
                <span class="status-value">
                    <code><?php echo esc_url(home_url('/sitemap_index.xml')); ?></code>
                </span>
            </div>
            
            <?php if (!empty($settings['include']['posts'])): ?>
            <div class="status-item">
                <span class="status-label"><?php _e('Posts Included:', 'almaseo'); ?></span>
                <span class="status-value"><?php _e('Yes', 'almaseo'); ?></span>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($settings['include']['pages'])): ?>
            <div class="status-item">
                <span class="status-label"><?php _e('Pages Included:', 'almaseo'); ?></span>
                <span class="status-value"><?php _e('Yes', 'almaseo'); ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Health Summary -->
    <div class="almaseo-card">
        <h2><?php _e('Health Summary', 'almaseo'); ?></h2>
        
        <?php
        $health_issues = [];
        
        // Check for common issues
        if (!$enabled) {
            $health_issues[] = [
                'type' => 'warning',
                'message' => __('Sitemaps are currently disabled', 'almaseo')
            ];
        }
        
        if ($last_built && (time() - $last_built) > 604800) { // 7 days
            $health_issues[] = [
                'type' => 'info',
                'message' => __('Sitemaps haven\'t been rebuilt in over a week', 'almaseo')
            ];
        }
        
        if ($total_urls > 50000) {
            $health_issues[] = [
                'type' => 'info',
                'message' => sprintf(__('Large sitemap detected (%s URLs)', 'almaseo'), number_format($total_urls))
            ];
        }
        ?>
        
        <?php if (empty($health_issues)): ?>
            <div class="health-status health-good">
                <span class="dashicons dashicons-yes-alt"></span>
                <span><?php _e('All systems operational', 'almaseo'); ?></span>
            </div>
        <?php else: ?>
            <?php foreach ($health_issues as $issue): ?>
                <div class="health-status health-<?php echo esc_attr($issue['type']); ?>">
                    <span class="dashicons dashicons-<?php echo $issue['type'] === 'warning' ? 'warning' : 'info'; ?>"></span>
                    <span><?php echo esc_html($issue['message']); ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <!-- Robots.txt Preview (Collapsible) -->
    <div class="almaseo-card">
        <div class="card-header-collapsible">
            <h2><?php _e('Robots.txt Preview', 'almaseo'); ?></h2>
            <button type="button" class="toggle-collapse" aria-expanded="false">
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </button>
        </div>
        
        <div class="collapsible-content" style="display:none;">
            <?php
            $robots_content = @file_get_contents(ABSPATH . 'robots.txt');
            if (!$robots_content) {
                // Virtual robots.txt
                $robots_content = "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n\n";
                $robots_content .= "Sitemap: " . home_url('/sitemap_index.xml');
            }
            ?>
            <pre class="robots-preview"><?php echo esc_html($robots_content); ?></pre>
            
            <?php if (strpos($robots_content, 'sitemap') !== false || strpos($robots_content, 'Sitemap') !== false): ?>
                <div class="notice notice-success inline">
                    <p><?php _e('✓ Sitemap reference found in robots.txt', 'almaseo'); ?></p>
                </div>
            <?php else: ?>
                <div class="notice notice-warning inline">
                    <p><?php _e('⚠ No sitemap reference found in robots.txt', 'almaseo'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Tools -->
    <div class="almaseo-card">
        <h2><?php _e('Quick Tools', 'almaseo'); ?></h2>
        
        <div class="quick-tools">
            <button type="button" class="button" id="validate-sitemap">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Validate Sitemap', 'almaseo'); ?>
            </button>
            
            <button type="button" class="button" id="submit-to-google">
                <span class="dashicons dashicons-google"></span>
                <?php _e('Submit to Google', 'almaseo'); ?>
            </button>
            
            <button type="button" class="button" id="index-now">
                <span class="dashicons dashicons-update"></span>
                <?php _e('IndexNow Ping', 'almaseo'); ?>
            </button>
            
            <button type="button" class="button" id="clear-cache">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Clear Cache', 'almaseo'); ?>
            </button>
        </div>
        
        <p class="description">
            <?php _e('Use these tools to validate your sitemap structure, submit to search engines, or trigger immediate indexing.', 'almaseo'); ?>
        </p>
    </div>
    
</div>

<style>
/* Stats Card */
.almaseo-stats-card .stats-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 20px;
    margin-top: 20px;
}

.almaseo-stats-card .stat-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 16px;
    background: #f9fafb;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
}

.almaseo-stats-card .stat-icon {
    font-size: 24px;
    color: #9ca3af;
    width: 24px;
    height: 24px;
}

.almaseo-stats-card .stat-content {
    flex: 1;
}

.almaseo-stats-card .stat-value {
    font-size: 20px;
    font-weight: 600;
    color: #1f2937;
    line-height: 1;
}

.almaseo-stats-card .stat-label {
    font-size: 12px;
    color: #6b7280;
    margin-top: 4px;
}

/* Status Grid */
.status-grid {
    display: grid;
    gap: 12px;
    margin-top: 16px;
}

.status-item {
    display: flex;
    align-items: center;
    gap: 12px;
}

.status-label {
    font-weight: 500;
    color: #6b7280;
    min-width: 140px;
}

.status-value {
    color: #1f2937;
}

.status-enabled {
    color: #10b981;
    font-weight: 600;
}

.status-disabled {
    color: #ef4444;
    font-weight: 600;
}

/* Health Status */
.health-status {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 12px;
}

.health-status:last-child {
    margin-bottom: 0;
}

.health-good {
    background: #f0fdf4;
    color: #10b981;
}

.health-warning {
    background: #fef3c7;
    color: #f59e0b;
}

.health-info {
    background: #eff6ff;
    color: #3b82f6;
}

/* Collapsible Header */
.card-header-collapsible {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
}

.card-header-collapsible h2 {
    margin: 0;
}

.toggle-collapse {
    background: none;
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 4px 8px;
    cursor: pointer;
    transition: all 0.2s ease;
}

.toggle-collapse:hover {
    background: #f9fafb;
}

.toggle-collapse .dashicons {
    transition: transform 0.2s ease;
}

.toggle-collapse[aria-expanded="true"] .dashicons {
    transform: rotate(180deg);
}

/* Robots Preview */
.robots-preview {
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    padding: 16px;
    font-family: monospace;
    font-size: 13px;
    overflow-x: auto;
    margin-bottom: 16px;
}

/* Quick Tools */
.quick-tools {
    display: flex;
    gap: 12px;
    flex-wrap: wrap;
    margin-bottom: 16px;
}

.quick-tools .button {
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

/* Responsive */
@media (max-width: 768px) {
    .almaseo-stats-card .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .quick-tools {
        flex-direction: column;
    }
    
    .quick-tools .button {
        width: 100%;
        justify-content: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    // Collapsible robots preview
    $('.toggle-collapse').on('click', function() {
        const $button = $(this);
        const $content = $button.closest('.almaseo-card').find('.collapsible-content');
        const isExpanded = $button.attr('aria-expanded') === 'true';
        
        $button.attr('aria-expanded', !isExpanded);
        $content.slideToggle(200);
    });
    
    // Quick tool handlers
    $('#validate-sitemap').on('click', function() {
        // Trigger validation
        $(document).trigger('almaseo:validate:sitemap');
    });
    
    $('#submit-to-google').on('click', function() {
        window.open('https://search.google.com/search-console/sitemaps', '_blank');
    });
    
    $('#index-now').on('click', function() {
        // Trigger IndexNow ping
        $(document).trigger('almaseo:indexnow:ping');
    });
    
    $('#clear-cache').on('click', function() {
        if (confirm('Clear sitemap cache?')) {
            // Trigger cache clear
            $(document).trigger('almaseo:cache:clear');
        }
    });
});
</script>