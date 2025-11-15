<?php
/**
 * Overview Tab - Fully Polished Version
 * 
 * @package AlmaSEO
 */

if (!defined('ABSPATH')) {
    exit;
}

// Get settings and stats
$settings = get_option('almaseo_sitemap_settings', []);
$enabled = !empty($settings['enabled']);
$takeover = !empty($settings['takeover']);
$generation_mode = $settings['generation_mode'] ?? 'static';
$build_stats = $settings['health']['last_build_stats'] ?? [];
$indexnow_key = $settings['indexnow']['key'] ?? '';

// Calculate stats
$total_files = isset($build_stats['files']) ? $build_stats['files'] : 0;
$total_urls = isset($build_stats['urls']) ? $build_stats['urls'] : 0;
$last_built = isset($build_stats['finished']) ? $build_stats['finished'] : 0;

// Health data
$last_validate = get_option('almaseo_last_validate_time', 0);
$conflict_count = get_option('almaseo_conflict_count', 0);
$last_indexnow = get_option('almaseo_last_indexnow_time', 0);

// Determine primary URL based on takeover state
$primary_url = $takeover ? home_url('/sitemap.xml') : home_url('/sitemap_index.xml');

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
            <button type="button" class="button button-primary" id="enable-sitemaps-cta">
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
                    <div class="stat-value" data-stat="files"><?php echo number_format($total_files); ?></div>
                    <div class="stat-label"><?php _e('Files', 'almaseo'); ?></div>
                </div>
            </div>
            <div class="stat-item">
                <span class="stat-icon dashicons dashicons-admin-links"></span>
                <div class="stat-content">
                    <div class="stat-value" data-stat="urls"><?php echo number_format($total_urls); ?></div>
                    <div class="stat-label"><?php _e('URLs', 'almaseo'); ?></div>
                </div>
            </div>
            <div class="stat-item">
                <span class="stat-icon dashicons dashicons-clock"></span>
                <div class="stat-content">
                    <div class="stat-value" data-stat="last-built">
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
                <div class="status-value status-url">
                    <code id="primary-sitemap-url"><?php echo esc_url($primary_url); ?></code>
                    <button type="button" class="button-link copy-url-btn" data-url="<?php echo esc_attr($primary_url); ?>" title="<?php esc_attr_e('Copy URL', 'almaseo'); ?>">
                        <span class="dashicons dashicons-clipboard"></span>
                    </button>
                </div>
            </div>
            
            <?php if ($takeover): ?>
            <div class="status-item">
                <span class="status-label"><?php _e('Takeover Mode:', 'almaseo'); ?></span>
                <span class="status-value status-enabled"><?php _e('Active', 'almaseo'); ?></span>
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
            <button type="button" class="health-chip" data-tab="health">
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
            </button>
            
            <button type="button" class="health-chip <?php echo $conflict_count > 0 ? 'has-issues' : ''; ?>" data-tab="health">
                <span class="dashicons dashicons-<?php echo $conflict_count > 0 ? 'warning' : 'yes-alt'; ?>"></span>
                <div class="chip-content">
                    <div class="chip-label"><?php _e('Conflicts', 'almaseo'); ?></div>
                    <div class="chip-value">
                        <?php echo $conflict_count > 0 ? sprintf(_n('%d found', '%d found', $conflict_count, 'almaseo'), $conflict_count) : __('None', 'almaseo'); ?>
                    </div>
                </div>
            </button>
            
            <button type="button" class="health-chip" data-tab="updates">
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
            </button>
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
        
        if (empty($indexnow_key)) {
            $health_issues[] = [
                'type' => 'info',
                'message' => __('IndexNow key not configured', 'almaseo')
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
        </div>
    </div>
    
    <!-- Quick Tools -->
    <div class="almaseo-card">
        <h2><?php _e('Quick Tools', 'almaseo'); ?></h2>
        
        <div class="quick-tools">
            <button type="button" class="button" id="validate-sitemap">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Validate Structure', 'almaseo'); ?>
            </button>
            
            <button type="button" class="button" id="submit-to-search-console">
                <span class="dashicons dashicons-google"></span>
                <?php _e('Open Search Console', 'almaseo'); ?>
            </button>
            
            <?php if (!empty($indexnow_key)): ?>
            <button type="button" class="button" id="ping-indexnow" title="<?php esc_attr_e('Instantly notify search engines of content changes', 'almaseo'); ?>">
                <span class="dashicons dashicons-megaphone"></span>
                <?php _e('Ping IndexNow', 'almaseo'); ?>
            </button>
            <?php else: ?>
            <button type="button" class="button" disabled title="<?php esc_attr_e('Configure IndexNow key in Updates & I/O tab', 'almaseo'); ?>">
                <span class="dashicons dashicons-megaphone"></span>
                <?php _e('Ping IndexNow', 'almaseo'); ?>
            </button>
            <?php endif; ?>
            
            <button type="button" class="button" id="clear-cache">
                <span class="dashicons dashicons-trash"></span>
                <?php _e('Clear Cache', 'almaseo'); ?>
            </button>
            
            <button type="button" class="button" id="copy-all-urls">
                <span class="dashicons dashicons-admin-page"></span>
                <?php _e('Copy All URLs', 'almaseo'); ?>
            </button>
        </div>
        
        <p class="description">
            <?php _e('Use these tools to validate your sitemap structure, submit to search engines, or manage sitemap content.', 'almaseo'); ?>
        </p>
    </div>
    
</div>

<style>
/* CTA Card */
.almaseo-cta-card {
    background: linear-gradient(135deg, #fef3c7 0%, #fed7aa 100%);
    border-color: #f59e0b;
}

.almaseo-cta-card .cta-content {
    display: flex;
    align-items: center;
    gap: 20px;
}

.almaseo-cta-card .cta-icon {
    font-size: 48px;
    color: #f59e0b;
    flex-shrink: 0;
}

.almaseo-cta-card .cta-text {
    flex: 1;
}

.almaseo-cta-card .cta-text h3 {
    margin: 0 0 8px 0;
    color: #92400e;
}

.almaseo-cta-card .cta-text p {
    margin: 0;
    color: #78350f;
}

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

.almaseo-stats-card .stat-value.loading {
    opacity: 0.5;
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

.status-value.status-url {
    display: flex;
    align-items: center;
    gap: 8px;
}

.copy-url-btn {
    padding: 0;
    margin: 0;
    border: none;
    background: none;
    color: #6b7280;
    cursor: pointer;
    transition: color 0.2s;
}

.copy-url-btn:hover {
    color: #7c3aed;
}

.copy-url-btn .dashicons {
    font-size: 16px;
    width: 16px;
    height: 16px;
}

.status-enabled {
    color: #10b981;
    font-weight: 600;
}

.status-disabled {
    color: #ef4444;
    font-weight: 600;
}

/* Health Chips */
.health-chips {
    display: flex;
    gap: 12px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.health-chip {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 12px 16px;
    background: #f9fafb;
    border: 1px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: all 0.2s;
}

.health-chip:hover {
    background: white;
    border-color: #7c3aed;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
}

.health-chip.has-issues {
    background: #fef3c7;
    border-color: #f59e0b;
}

.health-chip .dashicons {
    font-size: 20px;
    width: 20px;
    height: 20px;
}

.health-chip .chip-content {
    text-align: left;
}

.health-chip .chip-label {
    font-size: 11px;
    color: #6b7280;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.health-chip .chip-value {
    font-size: 14px;
    font-weight: 600;
    color: #1f2937;
    margin-top: 2px;
}

/* Health Issues */
.health-issues {
    margin-top: 16px;
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

.quick-tools .button[disabled] {
    opacity: 0.6;
    cursor: not-allowed;
}

/* Responsive */
@media (max-width: 768px) {
    .almaseo-stats-card .stats-grid {
        grid-template-columns: repeat(2, 1fr);
    }
    
    .health-chips {
        flex-direction: column;
    }
    
    .health-chip {
        width: 100%;
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
        $content.attr('aria-hidden', isExpanded);
        $content.slideToggle(200);
    });
    
    // Enable Sitemaps CTA
    $('#enable-sitemaps-cta').on('click', function() {
        const $button = $(this);
        $button.prop('disabled', true).html('<span class="spinner is-active"></span> Enabling...');
        
        $.post(ajaxurl, {
            action: 'almaseo_toggle_sitemaps',
            enable: true,
            _wpnonce: $('#almaseo_sitemaps_nonce').val()
        })
        .done(function(response) {
            if (response.success) {
                // Trigger rebuild
                $.post(ajaxurl, {
                    action: 'almaseo_rebuild_static',
                    _wpnonce: $('#almaseo_sitemaps_nonce').val()
                });
                
                // Update UI
                $('.almaseo-cta-card').slideUp();
                $('.status-disabled').removeClass('status-disabled').addClass('status-enabled').text('Enabled');
                
                // Update chips
                $(document).trigger('almaseo:refresh:stats');
                
                // Show success toast
                showToast('Sitemaps enabled successfully', 'success');
            }
        })
        .always(function() {
            $button.prop('disabled', false).html('<span class="dashicons dashicons-yes-alt"></span> Enable Sitemaps');
        });
    });
    
    // Copy URL functionality
    $('.copy-url-btn').on('click', function() {
        const url = $(this).data('url');
        const temp = $('<input>');
        $('body').append(temp);
        temp.val(url).select();
        document.execCommand('copy');
        temp.remove();
        
        showToast('URL copied to clipboard', 'success');
    });
    
    // Health chip navigation
    $('.health-chip').on('click', function() {
        const tab = $(this).data('tab');
        if (tab) {
            // Trigger tab switch
            $('[data-tab="' + tab + '"]').click();
        }
    });
    
    // Quick tool handlers
    $('#validate-sitemap').on('click', function() {
        $(document).trigger('almaseo:validate:sitemap');
    });
    
    $('#submit-to-search-console').on('click', function() {
        window.open('https://search.google.com/search-console/sitemaps', '_blank');
    });
    
    $('#ping-indexnow').on('click', function() {
        const $button = $(this);
        $button.prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'almaseo_force_delta_ping',
            _wpnonce: $('#almaseo_sitemaps_nonce').val()
        })
        .done(function(response) {
            if (response.success) {
                showToast('IndexNow ping sent successfully', 'success');
                // Update last IndexNow time
                $(document).trigger('almaseo:refresh:stats');
            } else {
                showToast(response.data || 'Failed to send IndexNow ping', 'error');
            }
        })
        .always(function() {
            $button.prop('disabled', false);
        });
    });
    
    $('#clear-cache').on('click', function() {
        if (confirm('Clear sitemap cache?')) {
            $(document).trigger('almaseo:cache:clear');
        }
    });
    
    $('#copy-all-urls').on('click', function() {
        const $button = $(this);
        $button.prop('disabled', true);
        
        $.post(ajaxurl, {
            action: 'almaseo_copy_all_urls',
            _wpnonce: $('#almaseo_sitemaps_nonce').val()
        })
        .done(function(response) {
            if (response.success && response.data) {
                const urls = response.data.urls || [];
                const text = urls.join('\n');
                
                const temp = $('<textarea>');
                $('body').append(temp);
                temp.val(text).select();
                document.execCommand('copy');
                temp.remove();
                
                showToast(`${urls.length} sitemap URLs copied to clipboard`, 'success');
            }
        })
        .always(function() {
            $button.prop('disabled', false);
        });
    });
    
    // Stats refresh handler
    $(document).on('almaseo:refresh:stats', function() {
        $('.stat-value').addClass('loading');
        
        $.get(ajaxurl, {
            action: 'almaseo_get_live_stats',
            _wpnonce: $('#almaseo_sitemaps_nonce').val()
        })
        .done(function(response) {
            if (response.success && response.data) {
                $('[data-stat="files"]').text(response.data.files || '0');
                $('[data-stat="urls"]').text(response.data.urls || '0');
                if (response.data.last_built) {
                    $('[data-stat="last-built"]').text(response.data.last_built);
                }
            }
        })
        .always(function() {
            $('.stat-value').removeClass('loading');
        });
    });
    
    // Check for active build
    function checkBuildStatus() {
        $.get(ajaxurl, {
            action: 'almaseo_check_build_lock',
            _wpnonce: $('#almaseo_sitemaps_nonce').val()
        })
        .done(function(response) {
            if (response.data && response.data.locked) {
                $('.stat-value').addClass('loading');
                setTimeout(checkBuildStatus, 2000);
            } else {
                $('.stat-value').removeClass('loading');
            }
        });
    }
    
    // Initial build status check
    checkBuildStatus();
    
    // Toast notification helper
    function showToast(message, type) {
        const $container = $('#almaseo-toast-container');
        const $toast = $('<div class="almaseo-toast almaseo-toast-' + type + '">' +
            '<span class="dashicons dashicons-' + (type === 'success' ? 'yes' : 'warning') + '"></span>' +
            '<span>' + message + '</span>' +
            '</div>');
        
        $container.append($toast);
        
        setTimeout(function() {
            $toast.css('opacity', '0');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }
});
</script>