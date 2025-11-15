# AlmaSEO SEO Playground - Feature Inventory

**Plugin Version:** 5.9.2  
**Audit Date:** August 31, 2025

## Complete Feature Table

| Area | Feature | File(s) | UI Location | Free/Pro | Depends On | Notes |
|------|---------|---------|-------------|----------|------------|-------|
| **Admin Screens** | Main Menu | `alma-seoconnector.php:697` | WP Admin Sidebar → SEO Playground | Free | - | Dashicons-search icon, position 30 |
| **Admin Screens** | Overview Dashboard | `alma-seoconnector.php:708` | SEO Playground → Overview | Free | - | Default landing page |
| **Admin Screens** | Connection Settings | `alma-seoconnector.php:718` | SEO Playground → Connection | Free | - | API connection to AlmaSEO dashboard |
| **Admin Screens** | Welcome Screen | `alma-seoconnector.php:731` | Hidden page (almaseo-welcome) | Free | - | Onboarding screen, no menu item |
| **Admin Screens** | Sitemap Admin | `includes/sitemap/admin/class-sitemap-admin-page.php:38` | SEO Playground → Sitemaps | Free | - | Comprehensive sitemap management |
| **Admin Screens** | Optimization Settings | `includes/optimization/optimization-loader-v12.php:705` | SEO Playground → Post Optimization | Free | GSC/DataForSEO | Keyword intelligence features |
| **Admin Screens** | Evergreen Dashboard | `includes/evergreen/admin.php:20`, `includes/evergreen/evergreen-ui.php:216` | SEO Playground → Evergreen | Free | - | Content freshness tracking |
| **Settings** | Sitemap Settings | `almaseo_sitemap_settings` option | Sitemaps tab | Free | - | Controls all sitemap generation |
| **Settings** | Evergreen Settings | `almaseo_eg_settings` option | Evergreen tab | Free | - | Thresholds for content aging |
| **Settings** | IndexNow Settings | `almaseo_indexnow_enabled`, `almaseo_indexnow_key` | Sitemaps → IndexNow | Free | - | Instant indexing to Bing/Yandex |
| **Settings** | Update Channel | `almaseo_update_channel` option | Connection Settings | Free | - | Stable/Beta update channels |
| **Settings** | Connection Secret | `almaseo_secret` option | Connection Settings | Free | - | API authentication token |
| **Metadata** | SEO Meta Box | `alma-seoconnector.php:3049` | Post/Page editor | Free | - | Title, description, keywords, robots |
| **Metadata** | Schema Meta Box | `includes/schema-clean.php:138` | Post/Page editor | Free | - | Schema.org markup configuration |
| **Metadata** | Social Meta Tags | `includes/meta-social-handler.php` | Frontend `<head>` | Free | - | Open Graph, Twitter Cards |
| **Metadata** | Evergreen Meta Box | `includes/evergreen/metabox.php:18` | Post/Page editor | Free | - | Content freshness status |
| **Metadata** | History Tracking | `includes/history/history-capture.php` | Post editor sidebar | Free | - | Version history for meta changes |
| **Sitemaps** | XML Sitemap Generation | `includes/sitemap/class-alma-sitemap-manager.php` | `/sitemap.xml` | Free | - | Dynamic and static modes |
| **Sitemaps** | Post/Page Sitemaps | `includes/sitemap/providers/class-alma-provider-posts.php` | `/sitemap-posts-*.xml` | Free | - | Paginated post sitemaps |
| **Sitemaps** | Custom Post Types | `includes/sitemap/providers/class-alma-provider-cpts.php` | `/sitemap-{cpt}-*.xml` | Free | - | Support for all CPTs |
| **Sitemaps** | Taxonomy Sitemaps | `includes/sitemap/providers/class-alma-provider-tax.php` | `/sitemap-tax-*.xml` | Free | - | Categories, tags, custom taxonomies |
| **Sitemaps** | User/Author Sitemaps | `includes/sitemap/providers/class-alma-provider-users.php` | `/sitemap-users-*.xml` | Free | - | Author archive pages |
| **Sitemaps** | Image Sitemaps | `includes/sitemap/providers/class-alma-provider-image.php` | Embedded in post sitemaps | Free | - | Extracts images from content |
| **Sitemaps** | Video Sitemaps | `includes/sitemap/providers/class-alma-provider-video.php` | `/sitemap-video-*.xml` | Free | - | YouTube/Vimeo detection |
| **Sitemaps** | News Sitemap | `includes/sitemap/providers/class-alma-provider-news.php` | `/sitemap-news.xml` | Free | - | 48-hour rolling window |
| **Sitemaps** | Delta Tracking | `includes/sitemap/providers/class-alma-provider-delta.php` | `/sitemap-delta.xml` | Free | - | Ring buffer for recent changes |
| **Sitemaps** | HTML Sitemap | `includes/sitemap/class-alma-html-sitemap.php` | `[almaseo_html_sitemap]` shortcode | Free | - | Frontend visitor sitemap |
| **Sitemaps** | Hreflang Support | `includes/sitemap/class-alma-hreflang.php` | International tab | Free | WPML/Polylang | Multi-language support |
| **Sitemaps** | Conflict Detection | `includes/sitemap/class-alma-sitemap-conflicts.php` | Sitemaps → Overview | Free | - | Detects Yoast/AIOSEO conflicts |
| **Schema** | Schema Output | `includes/schema-clean.php` | Frontend JSON-LD | Free | - | Article, WebPage, Organization |
| **Schema** | Schema Scrubber | `includes/schema-scrubber-safe.php` | Frontend | Free | - | Removes duplicate schemas |
| **Schema** | Image Fallback | `includes/schema-image-fallback.php` | Schema generation | Free | - | Ensures valid image URLs |
| **Health/Score** | SEO Health Score | `includes/health/analyzer.php` | Post editor | Free | - | Real-time SEO scoring |
| **Health/Score** | Health Dashboard | `includes/health/ui.php` | Health tab | Free | - | Site-wide health metrics |
| **Health/Score** | Weighted Scoring | `includes/health/weights.php` | Score calculation | Free | - | Customizable score weights |
| **History/Versioning** | Meta History | `includes/history/history-capture.php` | Notes & History tab | Free | - | Tracks all meta changes |
| **History/Versioning** | History Restore | `includes/history/history-restore.php` | History UI | Free | - | Rollback to previous versions |
| **History/Versioning** | History UI | `includes/history/history-ui.php` | Post editor tab | Free | - | Visual diff comparison |
| **Keyword Tools** | Keyword Intelligence | `utils/keyword_intelligence.py` | AI Content tab | Unknown | AlmaSEO API | Requires connection |
| **Keyword Tools** | Quick Wins | `includes/optimization/optimization-loader-v12.php:55` | Post Optimization | Free | GSC API | Shows ranking opportunities |
| **Keyword Tools** | Keyword Suggestions | `includes/optimization/optimization-loader-v12.php:89` | Post sidebar | Free | GSC/DataForSEO | Related keyword ideas |
| **Keyword Tools** | GSC Integration | `includes/optimization/GSCProvider-v12.php` | Backend provider | Free | Google OAuth | Real position data |
| **Evergreen** | Content Aging | `includes/evergreen/scoring.php` | Evergreen dashboard | Free | - | Tracks content freshness |
| **Evergreen** | Traffic Monitoring | `includes/evergreen/gsc-integration.php` | Evergreen metabox | Free | GSC API | 90-day traffic trends |
| **Evergreen** | Weekly Digest | `includes/evergreen/dashboard.php` | Email/Dashboard | Free | - | Content performance reports |
| **Evergreen** | Bulk Export | `includes/evergreen/export.php` | Evergreen dashboard | Free | - | CSV export functionality |
| **Evergreen** | Widget | `includes/evergreen/widget.php` | WP Dashboard | Free | - | At-a-glance stats |
| **Import/Integrations** | Settings Import/Export | `includes/sitemap/class-alma-settings-porter.php` | Sitemaps → Settings | Free | - | Backup/restore configuration |
| **Import/Integrations** | AIOSEO Compatibility | `includes/schema-scrubber-safe.php` | Auto-detect | Free | - | Prevents schema conflicts |
| **Import/Integrations** | Yoast Compatibility | Conflict detection | Sitemaps tab | Free | - | Sitemap takeover option |
| **Import/Integrations** | RankMath Compatibility | Conflict detection | Sitemaps tab | Free | - | Coexistence mode |
| **REST & AJAX** | Generate App Password | `alma-seoconnector.php:448` | `/wp-json/almaseo/v1/generate-app-password` | Free | - | API authentication |
| **REST & AJAX** | Auto Connect | `alma-seoconnector.php:465` | `/wp-json/almaseo/v1/auto-connect` | Free | - | Dashboard connection |
| **REST & AJAX** | Verify Connection | `alma-seoconnector.php:472` | `/wp-json/almaseo/v1/verify-connection` | Free | - | Connection status check |
| **REST & AJAX** | Publish Post | `alma-seoconnector.php:486` | `/wp-json/almaseo/v1/publish-post` | Free | AlmaSEO API | Remote publishing |
| **REST & AJAX** | Update Meta | `alma-seoconnector.php:492` | `/wp-json/almaseo/v1/update-meta` | Free | - | Meta field updates |
| **REST & AJAX** | Check Connection | `alma-seoconnector.php:6771` | `wp_ajax_seo_playground_check_connection` | Free | - | Connection validation |
| **REST & AJAX** | Reoptimize Check | `alma-seoconnector.php:6860` | `wp_ajax_seo_playground_reoptimize_check` | Free | AlmaSEO API | Content analysis |
| **REST & AJAX** | AI Rewrite | `alma-seoconnector.php:6977` | `wp_ajax_seo_playground_rewrite` | Unknown | AlmaSEO API | AI content generation |
| **REST & AJAX** | Generate Brief | `alma-seoconnector.php:7052` | `wp_ajax_seo_playground_generate_brief` | Unknown | AlmaSEO API | Content brief generation |
| **REST & AJAX** | Dismiss Notices | `alma-seoconnector.php:322,367,384` | Various AJAX handlers | Free | - | Notice management |
| **REST & AJAX** | Sitemap Operations | `includes/sitemap/admin/class-sitemap-ajax-handlers.php` | 20+ AJAX endpoints | Free | - | All sitemap AJAX ops |
| **REST & AJAX** | Evergreen API | `includes/evergreen/rest-api.php` | `/wp-json/almaseo/v1/evergreen/*` | Free | - | Evergreen data endpoints |
| **Cron/Background Jobs** | Content Refresh Reminder | `alma-seoconnector.php:172` | `almaseo_content_refresh_reminder` | Free | - | Scheduled content updates |
| **Cron/Background Jobs** | Sitemap Rebuild | `includes/sitemap/class-alma-sitemap-manager.php:193` | Daily cron | Free | - | Automatic sitemap updates |
| **Cron/Background Jobs** | News Refresh | `includes/sitemap/class-alma-sitemap-manager.php:199` | Hourly cron | Free | - | News sitemap updates |
| **Cron/Background Jobs** | Delta Ping | `includes/sitemap/providers/class-alma-provider-delta.php:477` | Dynamic scheduling | Free | - | Search engine notifications |
| **Cron/Background Jobs** | Evergreen Weekly | `includes/evergreen/cron.php:53` | Weekly analysis | Free | - | Content aging checks |
| **Cron/Background Jobs** | Update Check | `includes/almaseo-update.php:77` | Daily cron | Free | - | Plugin update checks |
| **Blocks/Shortcodes** | HTML Sitemap Shortcode | `includes/sitemap/class-alma-html-sitemap.php:22` | `[almaseo_html_sitemap]` | Free | - | Frontend sitemap display |
| **Blocks/Shortcodes** | HTML Sitemap Block | `includes/sitemap/class-alma-html-sitemap.php:437` | Gutenberg block | Free | - | Block editor support |
| **UI/UX Extras** | Search Engine Warning | `alma-seoconnector.php:223` | Admin notices | Free | - | Warns if site hidden from Google |
| **UI/UX Extras** | Legacy Plugin Detection | `alma-seoconnector.php:157` | Admin notices | Free | - | Detects old plugin versions |
| **UI/UX Extras** | Admin Hardening CSS | `alma-seoconnector.php:145` | All admin pages | Free | - | Override legacy styles |
| **UI/UX Extras** | Tabbed Interfaces | Multiple JS files | All plugin screens | Free | - | Consistent tab navigation |
| **UI/UX Extras** | Chart Visualizations | `assets/js/evergreen.js` | Evergreen dashboard | Free | - | Health trend charts |
| **UI/UX Extras** | Tooltips | Various JS files | Throughout UI | Free | - | Contextual help |
| **UI/UX Extras** | Progress Indicators | `assets/js/sitemaps-tabs-consolidated.js` | Sitemap operations | Free | - | Real-time progress bars |
| **UI/UX Extras** | Keyboard Shortcuts | `assets/js/schema-meta-tab.js` | Post editor | Free | - | Quick actions |
| **UI/UX Extras** | Dark Mode Support | CSS files | User preference | Free | - | Follows WP admin theme |
| **UI/UX Extras** | Responsive Tables | CSS files | All data tables | Free | - | Mobile-friendly views |

## Gaps & Ambiguities

1. **Pro/Free Distinction**: No clear Pro-only features found in code. "Tier" system mentioned (`almaseo_fetch_user_tier()`) but implementation shows "unconnected" status only. AI features (rewrite, brief generation) may require API connection but not explicitly marked as Pro.

2. **Deprecated/Unused Code**: Large `/archived/` directory contains old implementations. Some features like WP-CLI commands (`includes/cli/schema-command.php`) referenced but file doesn't exist in active code.

3. **Feature Flags**: No traditional feature flags found. Features seem to be gated by:
   - API connection status (`almaseo_secret` option)
   - User capabilities (`manage_options`, `edit_posts`)
   - Third-party plugin detection (WPML, Polylang, Yoast)

4. **Incomplete Features**: 
   - DataForSEO provider implemented but no configuration UI found
   - Health meta box registered but commented out (`includes/health/ui.php:38`)
   - Several "v12" versions suggest iterative development

5. **External Dependencies**: Heavy reliance on AlmaSEO dashboard API for:
   - AI content generation
   - Keyword intelligence
   - User tier/limits
   - Update notifications

## Changelog Sync

### Features Claimed in README/Changelog but Not Fully Evident in Code:
- "Enterprise Features: Multisite support" - No explicit multisite code found
- "WP-CLI commands" - Referenced but implementation file missing
- "CDN deduplication" for images - Logic not clearly visible

### Features in Code but Not Highlighted in Documentation:
- Content refresh reminders with email notifications
- GSC Provider with fallback to DataForSEO
- Comprehensive AJAX operations for sitemap management (20+ endpoints)
- Schema scrubber for compatibility with other SEO plugins
- Weekly digest HTML generation for Evergreen content
- Conflict scan with background batch processing
- Settings porter for configuration backup/restore

### Version Discrepancies:
- Plugin header shows v5.9.2
- Changelog documents up to v5.8.8
- No changelog entries for 5.9.x versions

## Technical Debt Indicators

1. Multiple loader files for Evergreen feature (`evergreen-loader-safe.php`, `evergreen-loader-minimal-safe.php`) suggest stability issues
2. Activation memory issues mentioned in comments
3. Legacy constant definitions for backwards compatibility
4. Hardcoded fallbacks and safety checks throughout
5. Mixed PHP namespacing (some files use namespaces, others don't)