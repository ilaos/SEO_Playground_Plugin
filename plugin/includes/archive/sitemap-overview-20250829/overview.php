<?php
/**
 * Overview Tab - Status cards, Build stats, Health summary, robots.txt preview, Quick Tools
 *
 * @package AlmaSEO
 * @since 4.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get data for the overview
$settings = get_option('almaseo_sitemap_settings', []);
$stats = almaseo_get_build_stats();
$health = almaseo_get_health_summary();
$build_lock = get_option('almaseo_sitemaps_build_lock');
$is_building = $build_lock && isset($build_lock['expires']) && $build_lock['expires'] > time();
?>

<!-- Overview Card -->
<div class="almaseo-card almaseo-overview-card">
    <div class="almaseo-card-header">
        <h2><?php _e('Sitemap Overview', 'almaseo'); ?></h2>
        <div class="almaseo-chips">
            <span class="almaseo-chip <?php echo !empty($settings['enabled']) ? 'almaseo-chip-success' : 'almaseo-chip-error'; ?>">
                <?php echo !empty($settings['enabled']) ? __('Enabled', 'almaseo') : __('Disabled', 'almaseo'); ?>
            </span>
            <span class="almaseo-chip">
                <?php _e('Mode:', 'almaseo'); ?> 
                <strong><?php echo $stats['mode']; ?></strong>
            </span>
            <?php if ($is_building): ?>
            <span class="almaseo-chip almaseo-chip-warning">
                <span class="dashicons dashicons-update spin"></span>
                <?php _e('Building...', 'almaseo'); ?>
            </span>
            <?php elseif ($stats['last_built']): ?>
            <span class="almaseo-chip">
                <?php _e('Last built:', 'almaseo'); ?> 
                <strong><?php echo human_time_diff($stats['last_built']) . ' ' . __('ago', 'almaseo'); ?></strong>
            </span>
            <?php endif; ?>
            <span class="almaseo-chip">
                <?php _e('Files:', 'almaseo'); ?> 
                <strong><?php echo $stats['files']; ?></strong>
            </span>
            <span class="almaseo-chip">
                <?php _e('URLs:', 'almaseo'); ?> 
                <strong><?php echo $stats['urls']; ?></strong>
            </span>
        </div>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-sitemap-url">
            <label><?php _e('Sitemap Index URL:', 'almaseo'); ?></label>
            <div class="almaseo-input-group">
                <input type="text" readonly value="<?php echo esc_url(home_url('/almaseo-sitemap.xml')); ?>" class="almaseo-input" id="sitemap-url">
                <div class="almaseo-button-group">
                    <button type="button" class="button almaseo-button-secondary" id="open-sitemap">
                        <span class="dashicons dashicons-external"></span>
                        <?php _e('Open Sitemap', 'almaseo'); ?>
                    </button>
                    <button type="button" class="button almaseo-button-secondary" id="copy-url">
                        <span class="dashicons dashicons-clipboard"></span>
                        <span class="button-text"><?php _e('Copy URL', 'almaseo'); ?></span>
                    </button>
                    <?php if (($settings['perf']['storage_mode'] ?? 'static') === 'static'): ?>
                    <button type="button" class="button almaseo-button-primary" id="rebuild-static" <?php echo $is_building ? 'disabled' : ''; ?>>
                        <span class="dashicons dashicons-update"></span>
                        <span class="button-text"><?php echo $is_building ? __('Building...', 'almaseo') : __('Rebuild (Static)', 'almaseo'); ?></span>
                    </button>
                    <?php else: ?>
                    <button type="button" class="button almaseo-button-primary" id="recalculate">
                        <span class="dashicons dashicons-update"></span>
                        <span class="button-text"><?php _e('Recalculate', 'almaseo'); ?></span>
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Statistics Section -->
<?php if (empty($settings['enabled'])): ?>
<div class="almaseo-card">
    <div class="almaseo-card-body">
        <div class="almaseo-info-box almaseo-info-warning">
            <p>
                <span class="dashicons dashicons-warning"></span>
                <?php _e('Sitemaps are currently disabled.', 'almaseo'); ?>
                <a href="<?php echo esc_url(add_query_arg('tab', 'settings')); ?>" class="button button-primary" style="margin-left: 10px;">
                    <?php _e('Enable Sitemaps', 'almaseo'); ?>
                </a>
            </p>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('Sitemap Statistics', 'almaseo'); ?></h2>
    </div>
    <div class="almaseo-card-body">
        <div class="alma-metrics">
            <div class="metric">
                <span class="dashicons dashicons-portfolio"></span>
                <div class="num"><?php echo $stats['files']; ?></div>
                <div class="label"><?php _e('Files', 'almaseo'); ?></div>
            </div>
            <div class="metric">
                <span class="dashicons dashicons-admin-links"></span>
                <div class="num"><?php echo $stats['urls']; ?></div>
                <div class="label"><?php _e('URLs', 'almaseo'); ?></div>
            </div>
            <div class="metric">
                <span class="dashicons dashicons-clock"></span>
                <div class="num"><?php echo $stats['last_built'] ? human_time_diff($stats['last_built'], time()) . ' ' . __('ago', 'almaseo') : __('Never', 'almaseo'); ?></div>
                <div class="label"><?php _e('Last Built', 'almaseo'); ?></div>
            </div>
            <div class="metric">
                <span class="dashicons dashicons-database"></span>
                <div class="num"><?php echo $stats['mode']; ?></div>
                <div class="label"><?php _e('Mode', 'almaseo'); ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Health Summary -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('Health Summary', 'almaseo'); ?></h2>
    </div>
    <div class="almaseo-card-body">
        <div class="alma-healthchips">
            <a href="<?php echo esc_url(add_query_arg('tab', 'health-scan')); ?>" class="hchip <?php echo $health['validated_at'] ? 'ok' : 'muted'; ?>">
                <span class="dot"></span>
                <span class="title"><?php _e('Last Validate', 'almaseo'); ?></span>
                <span class="value"><?php echo $health['validated_at'] ? human_time_diff($health['validated_at'], time()) . ' ' . __('ago', 'almaseo') : __('Never', 'almaseo'); ?></span>
            </a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'health-scan')); ?>" class="hchip <?php echo $health['conflicts'] ? 'warn' : 'ok'; ?>">
                <span class="dot"></span>
                <span class="title"><?php _e('Conflicts', 'almaseo'); ?></span>
                <span class="value"><?php echo (int)$health['conflicts']; ?></span>
            </a>
            <a href="<?php echo esc_url(add_query_arg('tab', 'change')); ?>" class="hchip <?php echo $health['indexnow_at'] ? 'ok' : 'muted'; ?>">
                <span class="dot"></span>
                <span class="title"><?php _e('Last IndexNow', 'almaseo'); ?></span>
                <span class="value"><?php echo $health['indexnow_at'] ? human_time_diff($health['indexnow_at'], time()) . ' ' . __('ago', 'almaseo') : __('Never', 'almaseo'); ?></span>
            </a>
        </div>
        <?php if (!empty($health['notes'])): ?>
        <div class="alma-notes">
            <?php foreach($health['notes'] as $n): ?>
                <div class="note"><?php echo esc_html($n); ?></div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="almaseo-two-column">
    <!-- Quick Tools -->
    <div class="almaseo-card">
        <div class="almaseo-card-header">
            <h2><?php _e('Quick Tools', 'almaseo'); ?></h2>
        </div>
        <div class="almaseo-card-body">
            <div class="almaseo-tools-grid">
                <button type="button" class="button almaseo-tool-button" data-tooltip="<?php _e('Validate sitemap structure and content', 'almaseo'); ?>">
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Validate', 'almaseo'); ?>
                </button>
                <button type="button" class="button almaseo-tool-button" data-tooltip="<?php _e('Submit sitemap to Google Search Console', 'almaseo'); ?>">
                    <span class="dashicons dashicons-google"></span>
                    <?php _e('Submit to Google', 'almaseo'); ?>
                </button>
                <button type="button" class="button almaseo-tool-button" data-tooltip="<?php _e('Notify search engines via IndexNow', 'almaseo'); ?>">
                    <span class="dashicons dashicons-search"></span>
                    <?php _e('IndexNow', 'almaseo'); ?>
                </button>
                <button type="button" class="button almaseo-tool-button" data-tooltip="<?php _e('Force regenerate all sitemap files', 'almaseo'); ?>">
                    <span class="dashicons dashicons-backup"></span>
                    <?php _e('Regenerate', 'almaseo'); ?>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Robots.txt Preview -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('Robots.txt Preview', 'almaseo'); ?></h2>
        <div class="almaseo-button-group">
            <button type="button" class="button almaseo-button-secondary" id="open-robots">
                <span class="dashicons dashicons-external"></span>
                <?php _e('View robots.txt', 'almaseo'); ?>
            </button>
        </div>
    </div>
    <div class="almaseo-card-body">
        <?php 
        $robots_content = '';
        $robots_path = ABSPATH . 'robots.txt';
        
        if (file_exists($robots_path)) {
            $robots_content = file_get_contents($robots_path);
        }
        
        if (empty($robots_content)) {
            $robots_content = "# No robots.txt file found\n# Default WordPress robots.txt would be generated";
        }
        
        // Check if sitemap reference exists
        $sitemap_url = home_url('/almaseo-sitemap.xml');
        $has_sitemap_ref = strpos($robots_content, $sitemap_url) !== false;
        ?>
        
        <div class="almaseo-robots-preview">
            <pre class="almaseo-code-block"><?php echo esc_html($robots_content); ?></pre>
            
            <?php if (!$has_sitemap_ref && !empty($settings['enabled'])): ?>
            <div class="almaseo-info-box almaseo-info-warning">
                <p>
                    <span class="dashicons dashicons-warning"></span>
                    <?php _e('Your robots.txt doesn\'t reference the sitemap. Consider adding:', 'almaseo'); ?>
                </p>
                <code>Sitemap: <?php echo esc_url($sitemap_url); ?></code>
            </div>
            <?php elseif ($has_sitemap_ref): ?>
            <div class="almaseo-info-box almaseo-info-success">
                <p>
                    <span class="dashicons dashicons-yes-alt"></span>
                    <?php _e('Sitemap is properly referenced in robots.txt', 'almaseo'); ?>
                </p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>