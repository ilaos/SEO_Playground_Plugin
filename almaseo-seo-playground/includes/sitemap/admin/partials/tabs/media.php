<?php
/**
 * Media Tab - Image + Video sections with scan/validate
 *
 * @package AlmaSEO
 * @since 4.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize media settings if not set
if (!isset($settings['media'])) {
    $settings['media'] = array(
        'image' => array(
            'enabled' => false,
            'max_per_url' => 20,
            'dedupe_cdn' => true
        ),
        'video' => array(
            'enabled' => false,
            'max_per_url' => 10,
            'oembed_cache' => true
        )
    );
}

$image_enabled = $settings['media']['image']['enabled'] ?? false;
$video_enabled = $settings['media']['video']['enabled'] ?? false;
$media_active = $image_enabled || $video_enabled;

// Get media stats
$media_stats = array(
    'images' => array('total' => 0, 'valid' => 0),
    'videos' => array('total' => 0, 'valid' => 0)
);

try {
    if (class_exists('Alma_Provider_Media')) {
        $media_stats = Alma_Provider_Media::get_stats();
    }
} catch (Exception $e) {
    // Handle gracefully if media class is not available
}
?>

<!-- Media Sitemaps Overview -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php esc_html_e('Media Sitemaps', 'almaseo-seo-playground'); ?></h2>
        <div class="almaseo-chips">
            <span class="almaseo-chip <?php echo esc_attr($media_active ? 'almaseo-chip-success' : ''); ?>">
                <?php echo $media_active ? esc_html__('Active', 'almaseo-seo-playground') : esc_html__('Inactive', 'almaseo-seo-playground'); ?>
            </span>
            <?php if ($image_enabled): ?>
            <span class="almaseo-chip almaseo-chip-info">
                <?php esc_html_e('Images', 'almaseo-seo-playground'); ?>
            </span>
            <?php endif; ?>
            <?php if ($video_enabled): ?>
            <span class="almaseo-chip almaseo-chip-info">
                <?php esc_html_e('Videos', 'almaseo-seo-playground'); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-info-box almaseo-info-default">
            <p>
                <span class="dashicons dashicons-format-image"></span>
                <?php esc_html_e('Media sitemaps help search engines discover and index images and videos on your site, improving visibility in image and video search results.', 'almaseo-seo-playground'); ?>
            </p>
        </div>
    </div>
</div>

<div class="almaseo-two-column">
    <!-- Image Sitemap -->
    <div class="almaseo-card">
        <div class="almaseo-card-header">
            <h2><?php esc_html_e('Image Sitemap', 'almaseo-seo-playground'); ?></h2>
            <div class="almaseo-chips">
                <span class="almaseo-chip <?php echo esc_attr($image_enabled ? 'almaseo-chip-success' : ''); ?>">
                    <?php echo $image_enabled ? esc_html__('Enabled', 'almaseo-seo-playground') : esc_html__('Disabled', 'almaseo-seo-playground'); ?>
                </span>
                <?php if ($media_stats['images']['total'] > 0): ?>
                <span class="almaseo-chip">
                    <?php
                    /* translators: %d: number of images in the sitemap */
                    echo esc_html(sprintf(__('%d Images', 'almaseo-seo-playground'), $media_stats['images']['total'])); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="almaseo-card-body">
            <div class="almaseo-form-group">
                <label class="almaseo-toggle-item">
                    <input type="checkbox" id="media-image-enabled" name="media[image][enabled]" <?php checked($image_enabled); ?>>
                    <span><?php esc_html_e('Enable Image Sitemap', 'almaseo-seo-playground'); ?></span>
                    <small><?php esc_html_e('Include images from posts, pages, and galleries', 'almaseo-seo-playground'); ?></small>
                </label>
            </div>
            
            <div class="almaseo-form-row image-settings <?php echo esc_attr(!$image_enabled ? 'disabled' : ''); ?>">
                <div class="almaseo-form-group">
                    <label for="media-image-max"><?php esc_html_e('Max Images per URL:', 'almaseo-seo-playground'); ?></label>
                    <input type="number" id="media-image-max" name="media[image][max_per_url]" 
                           value="<?php echo esc_attr($settings['media']['image']['max_per_url'] ?? 20); ?>"
                           min="1" max="100" class="almaseo-input almaseo-input-small">
                    <p class="description"><?php esc_html_e('Limit images per page (1-100)', 'almaseo-seo-playground'); ?></p>
                </div>
                <div class="almaseo-form-group">
                    <label class="almaseo-toggle-item">
                        <input type="checkbox" id="media-image-dedupe" name="media[image][dedupe_cdn]" 
                               <?php checked($settings['media']['image']['dedupe_cdn'] ?? true); ?>>
                        <span><?php esc_html_e('Deduplicate CDN URLs', 'almaseo-seo-playground'); ?></span>
                        <small><?php esc_html_e('Remove duplicate URLs from CDN services', 'almaseo-seo-playground'); ?></small>
                    </label>
                </div>
            </div>
            
            <?php if ($image_enabled): ?>
            <div class="almaseo-form-group">
                <label><?php esc_html_e('Image Sitemap URL:', 'almaseo-seo-playground'); ?></label>
                <div class="almaseo-input-group">
                    <input type="text" readonly value="<?php echo esc_url(home_url('/almaseo-sitemap-image-1.xml')); ?>" class="almaseo-input">
                    <div class="almaseo-button-group">
                        <button type="button" class="button almaseo-button-secondary" id="open-image-sitemap">
                            <span class="dashicons dashicons-external"></span>
                            <?php esc_html_e('View', 'almaseo-seo-playground'); ?>
                        </button>
                        <button type="button" class="button almaseo-button-secondary" id="copy-image-url">
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php esc_html_e('Copy', 'almaseo-seo-playground'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Image Stats -->
            <?php if ($media_stats['images']['total'] > 0): ?>
            <div class="almaseo-stat-grid">
                <div class="almaseo-stat">
                    <div class="almaseo-stat-value"><?php echo esc_html(number_format($media_stats['images']['total'])); ?></div>
                    <div class="almaseo-stat-label"><?php esc_html_e('Total Images', 'almaseo-seo-playground'); ?></div>
                </div>
                <div class="almaseo-stat">
                    <div class="almaseo-stat-value almaseo-text-success"><?php echo esc_html(number_format($media_stats['images']['valid'])); ?></div>
                    <div class="almaseo-stat-label"><?php esc_html_e('Valid Images', 'almaseo-seo-playground'); ?></div>
                </div>
            </div>
            <?php else: ?>
            <div class="almaseo-empty-state">
                <span class="dashicons dashicons-format-image"></span>
                <p><?php esc_html_e('No images scanned yet', 'almaseo-seo-playground'); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Video Sitemap -->
    <div class="almaseo-card">
        <div class="almaseo-card-header">
            <h2><?php esc_html_e('Video Sitemap', 'almaseo-seo-playground'); ?></h2>
            <div class="almaseo-chips">
                <span class="almaseo-chip <?php echo esc_attr($video_enabled ? 'almaseo-chip-success' : ''); ?>">
                    <?php echo $video_enabled ? esc_html__('Enabled', 'almaseo-seo-playground') : esc_html__('Disabled', 'almaseo-seo-playground'); ?>
                </span>
                <?php if ($media_stats['videos']['total'] > 0): ?>
                <span class="almaseo-chip">
                    <?php
                    /* translators: %d: number of videos in the sitemap */
                    echo esc_html(sprintf(__('%d Videos', 'almaseo-seo-playground'), $media_stats['videos']['total'])); ?>
                </span>
                <?php endif; ?>
            </div>
        </div>
        <div class="almaseo-card-body">
            <div class="almaseo-form-group">
                <label class="almaseo-toggle-item">
                    <input type="checkbox" id="media-video-enabled" name="media[video][enabled]" <?php checked($video_enabled); ?>>
                    <span><?php esc_html_e('Enable Video Sitemap', 'almaseo-seo-playground'); ?></span>
                    <small><?php esc_html_e('Include YouTube, Vimeo, and self-hosted videos', 'almaseo-seo-playground'); ?></small>
                </label>
            </div>
            
            <div class="almaseo-form-row video-settings <?php echo esc_attr(!$video_enabled ? 'disabled' : ''); ?>">
                <div class="almaseo-form-group">
                    <label for="media-video-max"><?php esc_html_e('Max Videos per URL:', 'almaseo-seo-playground'); ?></label>
                    <input type="number" id="media-video-max" name="media[video][max_per_url]" 
                           value="<?php echo esc_attr($settings['media']['video']['max_per_url'] ?? 10); ?>"
                           min="1" max="50" class="almaseo-input almaseo-input-small">
                    <p class="description"><?php esc_html_e('Limit videos per page (1-50)', 'almaseo-seo-playground'); ?></p>
                </div>
                <div class="almaseo-form-group">
                    <label class="almaseo-toggle-item">
                        <input type="checkbox" id="media-video-oembed" name="media[video][oembed_cache]" 
                               <?php checked($settings['media']['video']['oembed_cache'] ?? true); ?>>
                        <span><?php esc_html_e('Cache oEmbed Data', 'almaseo-seo-playground'); ?></span>
                        <small><?php esc_html_e('Improve performance by caching video metadata', 'almaseo-seo-playground'); ?></small>
                    </label>
                </div>
            </div>
            
            <?php if ($video_enabled): ?>
            <div class="almaseo-form-group">
                <label><?php esc_html_e('Video Sitemap URL:', 'almaseo-seo-playground'); ?></label>
                <div class="almaseo-input-group">
                    <input type="text" readonly value="<?php echo esc_url(home_url('/almaseo-sitemap-video-1.xml')); ?>" class="almaseo-input">
                    <div class="almaseo-button-group">
                        <button type="button" class="button almaseo-button-secondary" id="open-video-sitemap">
                            <span class="dashicons dashicons-external"></span>
                            <?php esc_html_e('View', 'almaseo-seo-playground'); ?>
                        </button>
                        <button type="button" class="button almaseo-button-secondary" id="copy-video-url">
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php esc_html_e('Copy', 'almaseo-seo-playground'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Video Stats -->
            <?php if ($media_stats['videos']['total'] > 0): ?>
            <div class="almaseo-stat-grid">
                <div class="almaseo-stat">
                    <div class="almaseo-stat-value"><?php echo esc_html(number_format($media_stats['videos']['total'])); ?></div>
                    <div class="almaseo-stat-label"><?php esc_html_e('Total Videos', 'almaseo-seo-playground'); ?></div>
                </div>
                <div class="almaseo-stat">
                    <div class="almaseo-stat-value almaseo-text-success"><?php echo esc_html(number_format($media_stats['videos']['valid'])); ?></div>
                    <div class="almaseo-stat-label"><?php esc_html_e('Valid Videos', 'almaseo-seo-playground'); ?></div>
                </div>
            </div>
            <?php else: ?>
            <div class="almaseo-empty-state">
                <span class="dashicons dashicons-format-video"></span>
                <p><?php esc_html_e('No videos scanned yet', 'almaseo-seo-playground'); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Media Actions -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php esc_html_e('Media Actions', 'almaseo-seo-playground'); ?></h2>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-button-group">
            <button type="button" class="button button-primary" id="scan-media">
                <span class="dashicons dashicons-search"></span>
                <?php esc_html_e('Scan Media', 'almaseo-seo-playground'); ?>
            </button>
            <button type="button" class="button almaseo-button-secondary" id="validate-media">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php esc_html_e('Validate Media', 'almaseo-seo-playground'); ?>
            </button>
            <?php if (isset($settings['perf']['storage_mode']) && $settings['perf']['storage_mode'] === 'static'): ?>
            <button type="button" class="button almaseo-button-secondary" id="rebuild-media">
                <span class="dashicons dashicons-update"></span>
                <?php esc_html_e('Rebuild Media Sitemaps', 'almaseo-seo-playground'); ?>
            </button>
            <?php endif; ?>
            <button type="button" class="button" id="export-media-report">
                <span class="dashicons dashicons-download"></span>
                <?php esc_html_e('Export Report', 'almaseo-seo-playground'); ?>
            </button>
        </div>
        
        <!-- Media Scan Results -->
        <div id="media-stats" class="almaseo-info-box" style="display:none;">
            <p><?php esc_html_e('Scanning media...', 'almaseo-seo-playground'); ?></p>
        </div>
        
        <!-- Media Validation Results -->
        <div id="media-validation-results" style="display:none;">
            <div class="almaseo-results-table">
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Media', 'almaseo-seo-playground'); ?></th>
                            <th><?php esc_html_e('Type', 'almaseo-seo-playground'); ?></th>
                            <th><?php esc_html_e('Status', 'almaseo-seo-playground'); ?></th>
                            <th><?php esc_html_e('Issues', 'almaseo-seo-playground'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="media-validation-tbody">
                        <!-- Populated via JS -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Media Best Practices -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php esc_html_e('Best Practices', 'almaseo-seo-playground'); ?></h2>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-tips-grid">
            <div class="almaseo-tip">
                <h4><?php esc_html_e('Image Optimization', 'almaseo-seo-playground'); ?></h4>
                <p><?php esc_html_e('Use descriptive filenames, alt text, and captions. Provide high-resolution images when possible.', 'almaseo-seo-playground'); ?></p>
            </div>
            <div class="almaseo-tip">
                <h4><?php esc_html_e('Video Metadata', 'almaseo-seo-playground'); ?></h4>
                <p><?php esc_html_e('Include video titles, descriptions, thumbnails, and duration. Use structured data for better results.', 'almaseo-seo-playground'); ?></p>
            </div>
            <div class="almaseo-tip">
                <h4><?php esc_html_e('File Formats', 'almaseo-seo-playground'); ?></h4>
                <p><?php esc_html_e('Use standard formats (JPEG, PNG, WebP for images; MP4, WebM for videos) for best compatibility.', 'almaseo-seo-playground'); ?></p>
            </div>
            <div class="almaseo-tip">
                <h4><?php esc_html_e('Performance', 'almaseo-seo-playground'); ?></h4>
                <p><?php esc_html_e('Optimize file sizes and use CDN services. Consider lazy loading for better page performance.', 'almaseo-seo-playground'); ?></p>
            </div>
        </div>
    </div>
</div>