<?php
/**
 * News Tab - News settings UI
 *
 * @package AlmaSEO
 * @since 4.5.0
 */

if (!defined('ABSPATH')) {
    exit;
}

// Initialize news settings if not set
if (!isset($settings['news'])) {
    $settings['news'] = array(
        'enabled' => false,
        'publisher_name' => get_bloginfo('name'),
        'language' => 'en',
        'post_types' => array('post'),
        'categories' => array(),
        'window_hours' => 48,
        'max_items' => 1000,
        'genres' => array(),
        'keywords_source' => 'tags',
        'manual_keywords' => ''
    );
}

$news_enabled = $settings['news']['enabled'] ?? false;

// Get news stats
$news_provider = null;
$news_stats = array();
$news_health = $settings['health']['news'] ?? array();

try {
    if ($news_enabled && class_exists('Alma_Sitemap_Manager')) {
        $news_provider = Alma_Sitemap_Manager::get_instance()->get_provider('news');
        $news_stats = $news_provider ? $news_provider->get_stats() : array();
    }
} catch (Exception $e) {
    // Handle gracefully if news class is not available
}
?>

<!-- News Sitemap -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('News Sitemap', 'almaseo'); ?></h2>
        <div class="almaseo-chips">
            <span class="almaseo-chip <?php echo $news_enabled ? 'almaseo-chip-success' : ''; ?>">
                <?php echo $news_enabled ? __('Active', 'almaseo') : __('Inactive', 'almaseo'); ?>
            </span>
            <?php if ($news_enabled && !empty($news_stats['items'])): ?>
            <span class="almaseo-chip">
                <?php echo sprintf(__('%d Items', 'almaseo'), $news_stats['items']); ?>
            </span>
            <?php endif; ?>
            <span class="almaseo-chip">
                <?php echo sprintf(__('%dh Window', 'almaseo'), $settings['news']['window_hours'] ?? 48); ?>
            </span>
            <?php if (!empty($news_stats['last_build'])): ?>
            <span class="almaseo-chip">
                <?php _e('Built:', 'almaseo'); ?> <?php echo human_time_diff($news_stats['last_build']); ?> <?php _e('ago', 'almaseo'); ?>
            </span>
            <?php endif; ?>
            <?php if (!empty($news_health['validated_at'])): ?>
            <span class="almaseo-chip <?php echo $news_health['ok'] ? 'almaseo-chip-success' : 'almaseo-chip-warning'; ?>">
                <?php echo $news_health['ok'] ? __('Valid', 'almaseo') : sprintf(__('%d Issues', 'almaseo'), count($news_health['issues'])); ?>
            </span>
            <?php endif; ?>
        </div>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-form-group">
            <label class="almaseo-toggle-item">
                <input type="checkbox" id="news-enabled" name="news[enabled]" <?php checked($news_enabled); ?>>
                <span><?php _e('Enable News Sitemap', 'almaseo'); ?></span>
                <small><?php _e('Google News sitemap with rolling window', 'almaseo'); ?></small>
            </label>
        </div>
        
        <div class="almaseo-info-box almaseo-info-default">
            <p>
                <span class="dashicons dashicons-rss"></span>
                <?php _e('News sitemaps help Google discover and index your latest news content quickly. Only articles published within the specified time window are included.', 'almaseo'); ?>
            </p>
        </div>
        
        <div class="news-settings <?php echo !$news_enabled ? 'disabled' : ''; ?>">
            <!-- Publisher Settings -->
            <div class="almaseo-form-section">
                <h3><?php _e('Publisher Information', 'almaseo'); ?></h3>
                <div class="almaseo-form-row">
                    <div class="almaseo-form-group">
                        <label for="news-publisher"><?php _e('Publisher Name:', 'almaseo'); ?></label>
                        <input type="text" id="news-publisher" name="news[publisher_name]" 
                               value="<?php echo esc_attr($settings['news']['publisher_name'] ?? get_bloginfo('name')); ?>"
                               class="almaseo-input">
                        <p class="description"><?php _e('Your organization\'s name as it appears in Google News', 'almaseo'); ?></p>
                    </div>
                    <div class="almaseo-form-group">
                        <label for="news-language"><?php _e('Language:', 'almaseo'); ?></label>
                        <select id="news-language" name="news[language]" class="almaseo-input">
                            <?php
                            $languages = array(
                                'en' => 'English',
                                'es' => 'Spanish',
                                'fr' => 'French',
                                'de' => 'German',
                                'it' => 'Italian',
                                'pt' => 'Portuguese',
                                'nl' => 'Dutch',
                                'ru' => 'Russian',
                                'ja' => 'Japanese',
                                'zh' => 'Chinese'
                            );
                            $current_lang = $settings['news']['language'] ?? 'en';
                            foreach ($languages as $code => $name): ?>
                            <option value="<?php echo esc_attr($code); ?>" <?php selected($current_lang, $code); ?>>
                                <?php echo esc_html($name); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description"><?php _e('Primary language of your news content', 'almaseo'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Content Filters -->
            <div class="almaseo-form-section">
                <h3><?php _e('Content Filters', 'almaseo'); ?></h3>
                <div class="almaseo-form-row">
                    <div class="almaseo-form-group">
                        <label><?php _e('Post Types:', 'almaseo'); ?></label>
                        <div class="almaseo-checkbox-group">
                            <?php
                            $post_types = get_post_types(array('public' => true), 'objects');
                            $selected_types = $settings['news']['post_types'] ?? array('post');
                            foreach ($post_types as $pt):
                                if ($pt->name === 'attachment') continue;
                            ?>
                            <label>
                                <input type="checkbox" name="news[post_types][]" 
                                       value="<?php echo esc_attr($pt->name); ?>"
                                       <?php checked(in_array($pt->name, $selected_types)); ?>>
                                <?php echo esc_html($pt->label); ?>
                            </label>
                            <?php endforeach; ?>
                        </div>
                        <p class="description"><?php _e('Select which post types should be included in the news sitemap', 'almaseo'); ?></p>
                    </div>
                    <div class="almaseo-form-group">
                        <label><?php _e('Categories (optional):', 'almaseo'); ?></label>
                        <div class="almaseo-checkbox-group" style="max-height: 150px; overflow-y: auto;">
                            <?php
                            $categories = get_categories(array('hide_empty' => false));
                            $selected_cats = $settings['news']['categories'] ?? array();
                            
                            if (empty($categories)): ?>
                                <p class="description"><?php _e('No categories available', 'almaseo'); ?></p>
                            <?php else:
                                foreach ($categories as $cat):
                            ?>
                            <label>
                                <input type="checkbox" name="news[categories][]" 
                                       value="<?php echo esc_attr($cat->term_id); ?>"
                                       <?php checked(in_array($cat->term_id, $selected_cats)); ?>>
                                <?php echo esc_html($cat->name); ?> (<?php echo $cat->count; ?>)
                            </label>
                            <?php 
                                endforeach;
                            endif; ?>
                        </div>
                        <p class="description"><?php _e('Leave unchecked to include all categories', 'almaseo'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Window and Limits -->
            <div class="almaseo-form-section">
                <h3><?php _e('Time Window & Limits', 'almaseo'); ?></h3>
                <div class="almaseo-form-row">
                    <div class="almaseo-form-group">
                        <label for="news-window"><?php _e('Window Hours:', 'almaseo'); ?></label>
                        <input type="number" id="news-window" name="news[window_hours]" 
                               value="<?php echo esc_attr($settings['news']['window_hours'] ?? 48); ?>"
                               min="1" max="168" class="almaseo-input almaseo-input-small">
                        <p class="description"><?php _e('Rolling window in hours (48 = 2 days, Google recommends 2-3 days)', 'almaseo'); ?></p>
                    </div>
                    <div class="almaseo-form-group">
                        <label for="news-max-items"><?php _e('Max Items:', 'almaseo'); ?></label>
                        <input type="number" id="news-max-items" name="news[max_items]" 
                               value="<?php echo esc_attr($settings['news']['max_items'] ?? 1000); ?>"
                               min="1" max="5000" class="almaseo-input almaseo-input-small">
                        <p class="description"><?php _e('Maximum news items (Google recommends 1000)', 'almaseo'); ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Genres -->
            <div class="almaseo-form-section">
                <h3><?php _e('Content Classification', 'almaseo'); ?></h3>
                <div class="almaseo-form-group">
                    <label><?php _e('Genres (optional):', 'almaseo'); ?></label>
                    <div class="almaseo-checkbox-group">
                        <?php
                        $genres = array(
                            'Blog' => __('Blog', 'almaseo'),
                            'PressRelease' => __('Press Release', 'almaseo'),
                            'Opinion' => __('Opinion', 'almaseo'),
                            'OpEd' => __('Op-Ed', 'almaseo'),
                            'Satire' => __('Satire', 'almaseo'),
                            'UserGenerated' => __('User Generated', 'almaseo')
                        );
                        $selected_genres = $settings['news']['genres'] ?? array();
                        foreach ($genres as $value => $label):
                        ?>
                        <label>
                            <input type="checkbox" name="news[genres][]" 
                                   value="<?php echo esc_attr($value); ?>"
                                   <?php checked(in_array($value, $selected_genres)); ?>>
                            <?php echo esc_html($label); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                    <p class="description"><?php _e('Help Google understand your content type', 'almaseo'); ?></p>
                </div>
                
                <!-- Keywords -->
                <div class="almaseo-form-group">
                    <label><?php _e('Keywords Source:', 'almaseo'); ?></label>
                    <div class="almaseo-radio-group">
                        <label>
                            <input type="radio" name="news[keywords_source]" value="tags" 
                                   <?php checked($settings['news']['keywords_source'] ?? 'tags', 'tags'); ?>>
                            <?php _e('Use Post Tags', 'almaseo'); ?>
                        </label>
                        <label>
                            <input type="radio" name="news[keywords_source]" value="manual" 
                                   <?php checked($settings['news']['keywords_source'] ?? 'tags', 'manual'); ?>>
                            <?php _e('Manual Keywords', 'almaseo'); ?>
                        </label>
                    </div>
                    <div id="news-manual-keywords-group" style="<?php echo ($settings['news']['keywords_source'] ?? 'tags') === 'manual' ? '' : 'display:none;'; ?>">
                        <label for="news-manual-keywords"><?php _e('Manual Keywords:', 'almaseo'); ?></label>
                        <input type="text" id="news-manual-keywords" name="news[manual_keywords]" 
                               value="<?php echo esc_attr($settings['news']['manual_keywords'] ?? ''); ?>"
                               placeholder="<?php _e('keyword1, keyword2, keyword3', 'almaseo'); ?>"
                               class="almaseo-input">
                        <p class="description"><?php _e('Comma-separated keywords (max 10)', 'almaseo'); ?></p>
                    </div>
                </div>
            </div>
            
            <?php if ($news_enabled): ?>
            <div class="almaseo-form-group">
                <label><?php _e('News Sitemap URL:', 'almaseo'); ?></label>
                <div class="almaseo-input-group">
                    <input type="text" readonly value="<?php echo esc_url(home_url('/almaseo-sitemap-news-1.xml')); ?>" class="almaseo-input">
                    <div class="almaseo-button-group">
                        <button type="button" class="button almaseo-button-secondary" id="open-news-sitemap">
                            <span class="dashicons dashicons-external"></span>
                            <?php _e('View', 'almaseo'); ?>
                        </button>
                        <button type="button" class="button almaseo-button-secondary" id="copy-news-url">
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php _e('Copy', 'almaseo'); ?>
                        </button>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <!-- News Actions -->
        <div class="almaseo-button-group">
            <button type="button" class="button button-primary" id="validate-news">
                <span class="dashicons dashicons-yes-alt"></span>
                <?php _e('Validate News', 'almaseo'); ?>
            </button>
            <?php if (isset($settings['perf']['storage_mode']) && $settings['perf']['storage_mode'] === 'static'): ?>
            <button type="button" class="button almaseo-button-secondary" id="rebuild-news">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Rebuild News', 'almaseo'); ?>
            </button>
            <?php endif; ?>
            <button type="button" class="button" id="preview-news-feed">
                <span class="dashicons dashicons-visibility"></span>
                <?php _e('Preview Feed', 'almaseo'); ?>
            </button>
        </div>
        
        <!-- News Validation Results -->
        <?php if (!empty($news_health)): ?>
        <div class="almaseo-info-box <?php echo $news_health['ok'] ? 'almaseo-info-success' : 'almaseo-info-warning'; ?>">
            <p>
                <strong><?php _e('Last validation:', 'almaseo'); ?></strong> 
                <?php echo human_time_diff($news_health['validated_at']); ?> <?php _e('ago', 'almaseo'); ?>
            </p>
            <?php if (!empty($news_health['samples'])): ?>
            <p><strong><?php _e('Sample articles:', 'almaseo'); ?></strong></p>
            <ul>
                <?php foreach (array_slice($news_health['samples'], 0, 3) as $sample): ?>
                <li>
                    <strong><?php echo esc_html($sample['title']); ?></strong><br>
                    <small><?php echo esc_html($sample['date']); ?></small>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php endif; ?>
            <?php if (!$news_health['ok'] && !empty($news_health['issues'])): ?>
            <p><strong><?php _e('Issues found:', 'almaseo'); ?></strong></p>
            <ul>
                <?php foreach (array_slice($news_health['issues'], 0, 5) as $issue): ?>
                <li><?php echo esc_html($issue); ?></li>
                <?php endforeach; ?>
                <?php if (count($news_health['issues']) > 5): ?>
                <li><?php echo sprintf(__('... and %d more', 'almaseo'), count($news_health['issues']) - 5); ?></li>
                <?php endif; ?>
            </ul>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <div class="almaseo-empty-state">
            <span class="dashicons dashicons-rss"></span>
            <p><?php _e('No validation performed yet', 'almaseo'); ?></p>
            <p class="description"><?php _e('Run validation to check your news sitemap configuration', 'almaseo'); ?></p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- News Sitemap Guidelines -->
<div class="almaseo-card">
    <div class="almaseo-card-header">
        <h2><?php _e('Google News Guidelines', 'almaseo'); ?></h2>
    </div>
    <div class="almaseo-card-body">
        <div class="almaseo-tips-grid">
            <div class="almaseo-tip">
                <h4><?php _e('Content Requirements', 'almaseo'); ?></h4>
                <ul>
                    <li><?php _e('Original, high-quality journalism', 'almaseo'); ?></li>
                    <li><?php _e('Clear publication dates', 'almaseo'); ?></li>
                    <li><?php _e('Author bylines', 'almaseo'); ?></li>
                </ul>
            </div>
            <div class="almaseo-tip">
                <h4><?php _e('Technical Requirements', 'almaseo'); ?></h4>
                <ul>
                    <li><?php _e('Unique URLs for each article', 'almaseo'); ?></li>
                    <li><?php _e('Proper HTML structure', 'almaseo'); ?></li>
                    <li><?php _e('Mobile-friendly pages', 'almaseo'); ?></li>
                </ul>
            </div>
            <div class="almaseo-tip">
                <h4><?php _e('Best Practices', 'almaseo'); ?></h4>
                <ul>
                    <li><?php _e('Publish regularly (daily preferred)', 'almaseo'); ?></li>
                    <li><?php _e('Use consistent publisher information', 'almaseo'); ?></li>
                    <li><?php _e('Follow Google News content policies', 'almaseo'); ?></li>
                </ul>
            </div>
            <div class="almaseo-tip">
                <h4><?php _e('Submission', 'almaseo'); ?></h4>
                <ul>
                    <li><?php _e('Submit to Google News Publisher Center', 'almaseo'); ?></li>
                    <li><?php _e('Add sitemap to Google Search Console', 'almaseo'); ?></li>
                    <li><?php _e('Monitor performance and errors', 'almaseo'); ?></li>
                </ul>
            </div>
        </div>
        
        <div class="almaseo-info-box almaseo-info-warning">
            <p>
                <span class="dashicons dashicons-warning"></span>
                <strong><?php _e('Important:', 'almaseo'); ?></strong>
                <?php _e('Google News has strict requirements. Your site must be approved by Google News before appearing in Google News search results.', 'almaseo'); ?>
                <a href="https://support.google.com/news/publisher-center/" target="_blank" class="almaseo-external-link">
                    <?php _e('Learn more about Google News requirements', 'almaseo'); ?>
                    <span class="dashicons dashicons-external"></span>
                </a>
            </p>
        </div>
    </div>
</div>