# AlmaSEO SEO Playground

![Version](https://img.shields.io/badge/version-5.9.2-blue.svg)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-green.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-purple.svg)
![License](https://img.shields.io/badge/license-GPL2-red.svg)

Professional SEO optimization plugin with AI-powered content generation, comprehensive keyword analysis, schema markup, and real-time SEO insights. Features enterprise-grade XML sitemaps with advanced functionality.

## 🎯 Key Features

### Evergreen Content Management
- **Content Health Monitoring**: Track content freshness and performance
- **Automatic Status Detection**: Categorizes content as Evergreen, Watch, or Stale
- **Health Trend Charts**: Visual representation of content health over time
- **Batch Analysis**: Process multiple posts at once with admin-post handler
- **Performance Caching**: 12-hour cached data with daily refresh
- **GSC Integration**: Optional Google Search Console data integration
- **Quick Actions**: Analyze individual posts or bulk operations
- **Export Options**: CSV and PDF export capabilities

### XML Sitemaps System
- **Comprehensive Coverage**: Posts, pages, custom post types, taxonomies, users, images, videos, news
- **Performance Optimized**: Static generation with gzip compression for lightning-fast serving
- **Delta Tracking**: Ring buffer tracks recent changes for instant search engine updates
- **IndexNow Integration**: Instant submission to Bing, Yandex, Seznam
- **Conflict Detection**: Safe coexistence with Yoast, AIOSEO, RankMath

### International Support
- **Hreflang Support**: Automatic WPML/Polylang integration with reciprocity validation
- **Multi-language Sitemaps**: Proper language-specific URL handling
- **X-default Support**: Configurable default language settings

### Media Handling
- **Image Sitemaps**: Automatic image extraction from content
- **Video Sitemaps**: oEmbed and custom video detection
- **CDN Deduplication**: Smart CDN URL handling to prevent duplicates

### News Sitemaps
- **Google News**: Rolling 48-hour window with category filtering
- **Publisher Settings**: Configurable publisher information
- **Genre Support**: News genres and keywords configuration

### Advanced Features
- **Multisite Support**: Network-wide or per-site configuration
- **WP-CLI Commands**: Command-line interface for automation
- **Health Monitoring**: Built-in validation and conflict detection
- **HTML Sitemap**: Shortcode and Gutenberg block for visitor-friendly sitemaps

## 📋 Installation

### From WordPress Admin
1. Upload the plugin files to `/wp-content/plugins/almaseo-seo-playground/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to **SEO Playground → Sitemaps** to configure

### Via WP-CLI
```bash
wp plugin install almaseo-seo-playground.zip --activate
wp almaseo sitemaps build --mode=static
```

## 🚀 Quick Start

### Enable Sitemaps
1. Go to **SEO Playground → Sitemaps**
2. Click the **Enable Sitemaps** button in the Overview tab
3. Configure your preferred settings in the Types & Rules tab
4. Click **Rebuild Sitemaps** to generate

### Access Your Sitemaps
- Main sitemap: `https://yoursite.com/sitemap_index.xml`
- Alternative: `https://yoursite.com/sitemap.xml` (if takeover mode enabled)

## ⚙️ Configuration

### Types & Rules Tab
- Select which content types to include
- Set links per sitemap (1-50000, default: 1000)
- Choose between Static or Dynamic generation
- Enable/disable gzip compression

### International Tab
- Configure hreflang settings
- Set default language
- Validate language pairs
- Manual language mapping support

### Change Detection Tab
- Enable delta sitemap for recent changes
- Configure retention period
- Set maximum URLs to track
- View recent changes log

### Media Tab
- Enable/disable image sitemaps
- Enable/disable video sitemaps
- Configure max media items per URL
- CDN deduplication settings

### News Tab
- Enable Google News sitemap
- Set publisher information
- Configure news window (default: 48 hours)
- Select news categories and genres

## 🛠️ Advanced Usage

### WP-CLI Commands

```bash
# Build sitemaps
wp almaseo sitemaps build --mode=static

# Validate sitemaps
wp almaseo sitemaps validate

# Clear cache
wp almaseo sitemaps clear-cache

# Submit to IndexNow
wp almaseo sitemaps indexnow
```

### Hooks & Filters

```php
// Modify sitemap URLs before output
add_filter('almaseo_sitemap_urls', function($urls, $type) {
    // Custom logic
    return $urls;
}, 10, 2);

// Add custom sitemap provider
add_filter('almaseo_sitemap_providers', function($providers) {
    $providers['custom'] = 'My_Custom_Provider';
    return $providers;
});

// Modify sitemap settings
add_filter('almaseo_sitemap_settings', function($settings) {
    $settings['links_per_sitemap'] = 5000;
    return $settings;
});
```

### HTML Sitemap Shortcode

```
[almaseo_html_sitemap 
    columns="3" 
    depth="2" 
    exclude="private,draft"
    show_dates="true"
    show_excerpts="false"]
```

## 📊 Health & Monitoring

The Health & Scan tab provides:
- Sitemap validation status
- Conflict detection with other SEO plugins
- URL accessibility checks
- Performance metrics
- Error logging

## 🔧 Troubleshooting

### Common Issues

**Sitemaps not generating:**
- Check file permissions (uploads directory must be writable)
- Verify no conflicts with other sitemap plugins
- Check PHP memory limit (minimum 128MB recommended)

**404 errors on sitemap URLs:**
- Flush permalinks: Settings → Permalinks → Save
- Check .htaccess rules
- Verify no conflicting rewrite rules

**Large site performance:**
- Use static generation mode
- Enable gzip compression
- Increase PHP max_execution_time
- Use WP-CLI for rebuilding

## 🤝 Compatibility

### Tested With
- WordPress 5.8 - 6.6
- PHP 7.4 - 8.3
- MySQL 5.7+ / MariaDB 10.3+

### Compatible Plugins
- WPML / Polylang
- WooCommerce
- Custom Post Type UI
- ACF (Advanced Custom Fields)
- Most caching plugins

### Known Conflicts
- Other sitemap plugins should be deactivated
- Some security plugins may block sitemap generation

## 📝 Changelog

See [CHANGELOG.md](CHANGELOG.md) for detailed version history.

## 📄 License

This plugin is licensed under GPL v2 or later.

## 🆘 Support

- Documentation: [GitHub Wiki](https://github.com/almaseo/seo-playground/wiki)
- Issues: [GitHub Issues](https://github.com/almaseo/seo-playground/issues)
- Website: [AlmaSEO.com](https://almaseo.com)

## 👥 Credits

Developed by AlmaSEO Team

---

**Current Version:** 5.9.2  
**Last Updated:** August 30, 2025