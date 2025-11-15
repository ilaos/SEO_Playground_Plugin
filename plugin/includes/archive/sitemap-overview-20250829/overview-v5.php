<?php
/**
 * Overview Tab - Polished with URL Logic
 * 
 * @package AlmaSEO
 */

if (!defined('ABSPATH')) {
    exit;
}

// Use new helper function
$urls = almaseo_get_index_urls();
$primary_url = esc_url($urls['primary']);
$direct_url = esc_url($urls['direct']);
$enabled = $urls['enabled'];
$takeover = $urls['takeover'];

// Get additional stats
$build_stats = almaseo_get_build_stats();
$indexnow_key = almaseo_get_indexnow_key();

// Get settings for additional data
$settings = get_option('almaseo_sitemap_settings', []);
$generation_mode = $settings['generation_mode'] ?? 'static';

// Health data
$last_validate = get_option('almaseo_last_validate_time', 0);
$conflict_count = get_option('almaseo_conflict_count', 0);
$last_indexnow = get_option('almaseo_last_indexnow_time', 0);

?>

<div class="almaseo-overview-tab">
    
    <?php if (!$enabled): ?>
    <!-- Enable Sitemaps CTA -->
    <div class="almaseo-card almaseo-cta-card">
        <div class="cta-content">
            <span class="dashicons dashicons-warning cta-icon"></span>
            <div class="cta-text">
                <h3><?php _e('Sitemaps are currently disabled', 'almaseo'); ?></h3>
                <p><?php _e('Enable sitemaps to help search engines discover and index your content.', 'almaseo'); ?></p>
            </div>
            <button type="button" class="button button-primary button-hero" id="enable-sitemaps-cta">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Enable Sitemaps', 'almaseo'); ?>
            </button>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Compact Stats Card -->
    <div class="almaseo-card almaseo-stats-card">
        <h2><?php _e('Sitemap Statistics', 'almaseo'); ?></h2>
        <div class="stats-grid">
            <div class="stat-item">
                <span class="stat-icon dashicons dashicons-media-text"></span>
                <div class="stat-content">
                    <div class="stat-value" data-stat="files"><?php echo number_format($build_stats['files']); ?></div>
                    <div class="stat-label"><?php _e('Files', 'almaseo'); ?></div>
                </div>
            </div>
            <div class="stat-item">
                <span class="stat-icon dashicons dashicons-admin-links"></span>
                <div class="stat-content">
                    <div class="stat-value" data-stat="urls"><?php echo number_format($build_stats['urls']); ?></div>
                    <div class="stat-label"><?php _e('URLs', 'almaseo'); ?></div>
                </div>
            </div>
            <div class="stat-item">
                <span class="stat-icon dashicons dashicons-clock"></span>
                <div class="stat-content">
                    <div class="stat-value" data-stat="last-built">
                        <?php 
                        if ($build_stats['last_built']) {
                            echo human_time_diff($build_stats['last_built']) . ' ' . __('ago', 'almaseo');
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
                <div class="status-value status-url">
                    <code id="primary-sitemap-url"><?php echo $primary_url; ?></code>
                    <button type="button" class="button-link copy-url-btn" 
                            data-url="<?php echo esc_attr($primary_url); ?>" 
                            title="<?php esc_attr_e('Copy URL', 'almaseo'); ?>"
                            <?php echo !$enabled ? 'disabled aria-disabled="true"' : ''; ?>>
                        <span class="dashicons dashicons-clipboard"></span>
                    </button>
                </div>
            </div>
            
            <?php if ($takeover): ?>
            <div class="status-item">
                <span class="status-label"><?php _e('Direct URL:', 'almaseo'); ?></span>
                <div class="status-value status-url">
                    <code><?php echo $direct_url; ?></code>
                    <span class="status-badge status-takeover"><?php _e('Takeover Active', 'almaseo'); ?></span>
                </div>
            </div>
            <?php endif; ?>
            
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
    
    <!-- Enhanced Health Summary -->
    <div class="almaseo-card">
        <h2><?php _e('Health Summary', 'almaseo'); ?></h2>
        
        <div class="health-chips">
            <a href="<?php echo admin_url('admin.php?page=almaseo-sitemaps&tab=health'); ?>" class="health-chip">
                <span class="dashicons dashicons-yes-alt"></span>
                <div class="chip-content">
                    <div class="chip-label"><?php _e('Last Validate', 'almaseo'); ?></div>
                    <div class="chip-value">
                        <?php 
                        if ($last_validate) {
                            echo human_time_diff($last_validate) . ' ' . __('ago', 'almaseo');
                        } else {
                            _e('Never', 'almaseo');
                        }
                        ?>
                    </div>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=almaseo-sitemaps&tab=health'); ?>" class="health-chip <?php echo $conflict_count > 0 ? 'has-issues' : ''; ?>">
                <span class="dashicons dashicons-<?php echo $conflict_count > 0 ? 'warning' : 'yes-alt'; ?>"></span>
                <div class="chip-content">
                    <div class="chip-label"><?php _e('Conflicts', 'almaseo'); ?></div>
                    <div class="chip-value">
                        <?php echo $conflict_count > 0 ? sprintf(_n('%d found', '%d found', $conflict_count, 'almaseo'), $conflict_count) : __('None', 'almaseo'); ?>
                    </div>
                </div>
            </a>
            
            <a href="<?php echo admin_url('admin.php?page=almaseo-sitemaps&tab=updates'); ?>" class="health-chip">
                <span class="dashicons dashicons-update"></span>
                <div class="chip-content">
                    <div class="chip-label"><?php _e('Last IndexNow', 'almaseo'); ?></div>
                    <div class="chip-value">
                        <?php 
                        if ($last_indexnow) {
                            echo human_time_diff($last_indexnow) . ' ' . __('ago', 'almaseo');
                        } else {
                            _e('Never', 'almaseo');
                        }
                        ?>
                    </div>
                </div>
            </a>
        </div>
        
        <?php
        $health_issues = [];
        
        // Check for common issues
        if (!$enabled) {
            $health_issues[] = [
                'type' => 'warning',
                'message' => __('Sitemaps are currently disabled', 'almaseo')
            ];
        }
        
        if ($build_stats['last_built'] && (time() - $build_stats['last_built']) > 604800) { // 7 days
            $health_issues[] = [
                'type' => 'info',
                'message' => __('Sitemaps haven\'t been rebuilt in over a week', 'almaseo')
            ];
        }
        
        if ($build_stats['urls'] > 50000) {
            $health_issues[] = [
                'type' => 'info',
                'message' => sprintf(__('Large sitemap detected (%s URLs)', 'almaseo'), number_format($build_stats['urls']))
            ];
        }
        
        if (!$indexnow_key) {
            $health_issues[] = [
                'type' => 'info',
                'message' => __('IndexNow key not configured - instant indexing disabled', 'almaseo')
            ];
        }
        ?>
        
        <?php if (!empty($health_issues)): ?>
            <div class="health-issues">
                <?php foreach ($health_issues as $issue): ?>
                    <div class="health-status health-<?php echo esc_attr($issue['type']); ?>">
                        <span class="dashicons dashicons-<?php echo $issue['type'] === 'warning' ? 'warning' : 'info'; ?>"></span>
                        <span><?php echo esc_html($issue['message']); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="health-status health-good">
                <span class="dashicons dashicons-yes-alt"></span>
                <span><?php _e('All systems operational', 'almaseo'); ?></span>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Robots.txt Preview (Collapsed by default) -->
    <div class="almaseo-card">
        <div class="card-header-collapsible">
            <h2><?php _e('Robots.txt Preview', 'almaseo'); ?></h2>
            <button type="button" class="toggle-collapse" aria-expanded="false" aria-label="<?php esc_attr_e('Toggle robots.txt preview', 'almaseo'); ?>">
                <span class="dashicons dashicons-arrow-down-alt2"></span>
            </button>
        </div>
        
        <div class="collapsible-content" style="display:none;" aria-hidden="true">
            <?php if ($enabled): ?>
                <?php
                $robots_content = @file_get_contents(ABSPATH . 'robots.txt');
                if (!$robots_content) {
                    // Virtual robots.txt
                    $robots_content = "User-agent: *\nDisallow: /wp-admin/\nAllow: /wp-admin/admin-ajax.php\n\n";
                    $robots_content .= "Sitemap: " . $primary_url;
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
            <?php else: ?>
                <div class="robots-disabled">
                    <p><?php _e('Enable sitemaps to preview robots.txt configuration', 'almaseo'); ?></p>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Quick Tools -->
    <div class="almaseo-card">
        <h2><?php _e('Quick Tools', 'almaseo'); ?></h2>
        
        <div class="quick-tools">
            <button type="button" class="button" id="validate-sitemap" 
                    <?php echo !$enabled ? 'disabled aria-disabled="true" title="' . esc_attr__('Enable sitemaps first', 'almaseo') . '"' : ''; ?>>
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Validate', 'almaseo'); ?>
            </button>
            
            <button type="button" class="button" id="submit-to-search-console">
                <span class="dashicons dashicons-google"></span>
                <?php _e('Search Console', 'almaseo'); ?>
            </button>
            
            <?php if ($indexnow_key): ?>
            <button type="button" class="button" id="ping-indexnow" 
                    <?php echo !$enabled ? 'disabled aria-disabled="true"' : ''; ?> 
                    title="<?php esc_attr_e('Instantly notify search engines of content changes', 'almaseo'); ?>">
                <span class="dashicons dashicons-megaphone"></span>
                <?php _e('IndexNow', 'almaseo'); ?>
            </button>
            <?php else: ?>
            <button type="button" class="button" disabled aria-disabled="true" 
                    title="<?php esc_attr_e('Configure IndexNow key in Updates & I/O tab first', 'almaseo'); ?>">
                <span class="dashicons dashicons-megaphone"></span>
                <?php _e('IndexNow', 'almaseo'); ?> <span class="dashicons dashicons-lock" style="font-size: 12px; margin-left: 4px;"></span>
            </button>
            <?php endif; ?>
            
            <button type="button" class="button" id="clear-cache" 
                    <?php echo !$enabled ? 'disabled aria-disabled="true"' : ''; ?>>
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Clear Cache', 'almaseo'); ?>
            </button>
            
            <button type="button" class="button" id="copy-all-urls" 
                    <?php echo !$enabled ? 'disabled aria-disabled="true"' : ''; ?>>
                <span class="dashicons dashicons-admin-page"></span>
                <?php _e('Copy All URLs', 'almaseo'); ?>
            </button>
        </div>
        
        <p class="description">
            <?php if (!$enabled): ?>
                <?php _e('Enable sitemaps to use these tools.', 'almaseo'); ?>
            <?php elseif (!$indexnow_key): ?>
                <?php _e('Configure IndexNow in the Updates & I/O tab for instant search engine notifications.', 'almaseo'); ?>
            <?php else: ?>
                <?php _e('Use these tools to validate your sitemap structure, submit to search engines, or manage sitemap content.', 'almaseo'); ?>
            <?php endif; ?>
        </p>
    </div>
    
</div>

<style>
/* Additional styles for takeover badge */
.status-badge.status-takeover {
    display: inline-block;
    padding: 2px 8px;
    background: #fbbf24;
    color: #78350f;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    border-radius: 4px;
    margin-left: 8px;
}

/* Disabled button styles */
.button[disabled], .button[aria-disabled="true"] {
    opacity: 0.5;
    cursor: not-allowed !important;
}

/* Rest of styles inherited from previous versions */
</style>

<script>
// Inherit all JavaScript from v4 with proper disabled state handling
jQuery(document).ready(function($) {
    // ... (same JavaScript as v4)
});
</script>