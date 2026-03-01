# AlmaSEO SEO Playground - Features

## Quick Reference Table

| Area | Feature | Key File(s) | UI Location |
|------|---------|-------------|-------------|
| **Admin** | Main Menu | `almaseo-seo-playground.php` | WP Admin Sidebar |
| **Admin** | Sitemap Admin | `includes/sitemap/admin/` | SEO Playground → Sitemaps |
| **Admin** | Evergreen Dashboard | `includes/evergreen/` | SEO Playground → Evergreen |
| **Metadata** | SEO Meta Box | `almaseo-seo-playground.php` | Post/Page editor |
| **Metadata** | Schema Meta Box | `includes/schema-clean.php` | Post/Page editor |
| **Sitemaps** | XML Generation | `includes/sitemap/class-alma-sitemap-manager.php` | `/sitemap.xml` |
| **Sitemaps** | Post/Page Sitemaps | `includes/sitemap/providers/` | `/sitemap-posts-*.xml` |
| **Schema** | Schema Output | `includes/schema-clean.php` | Frontend JSON-LD |
| **Health** | SEO Health Score | `includes/health/analyzer.php` | Post editor |
| **History** | Meta History | `includes/history/` | Notes & History tab |

---

## Robots.txt Editor

**Location:** SEO Playground → Robots.txt

- **Virtual Mode (Recommended)**: Serves robots.txt via WordPress filter without file writes
- **Physical File Mode**: Direct file editing when write permissions available
- **Syntax-Highlighted Editor**: Monospace font with proper formatting
- **Live Preview**: Test output shows exactly what will be served
- **Insert Defaults**: One-click insertion of recommended rules
- **Input Sanitization**: Removes PHP tags and dangerous content
- **Nonce Protection**: All AJAX operations protected

---

## Evergreen Content Management

**Location:** SEO Playground → Evergreen

### Content Health Monitoring
- Automatic analysis based on age, updates, and traffic
- Status Categories: Evergreen (healthy), Watch (needs attention), Stale (outdated)
- Batch processing up to 100 posts at once

### Dashboard & Visualization
- Health Overview Cards with percentage breakdowns
- Health Trend Chart (4/8/12 week views)
- At-Risk Content Table (sortable)
- 90-Day Traffic Trends per post

### Operations
- Rebuild Statistics
- Clear & Refresh Cache
- Export to CSV/PDF
- Bulk Analysis

---

## Advanced Sitemaps

**Location:** SEO Playground → Sitemaps

### Sitemap Types
- Post/Page Sitemaps (paginated)
- Category/Tag Sitemaps
- Author Sitemaps
- Custom Post Type Sitemaps
- Image Sitemaps (embedded)
- Video Sitemaps (YouTube/Vimeo detection)
- News Sitemaps (Google News format)
- HTML Sitemaps (`[almaseo_html_sitemap]` shortcode)

### Advanced Features
- Dynamic & Static Modes
- Delta Tracking (recent changes)
- IndexNow Integration (Bing/Yandex)
- Hreflang Support (WPML/Polylang)
- Conflict Detection (Yoast/AIOSEO/RankMath)
- Robots.txt Integration

---

## SEO Metadata Management

### Post/Page SEO (SEO Page Health Tab)
- Meta Title with character counter (60 char recommended)
- Meta Description with length indicator (150-160 chars)
- Focus Keywords
- Google SERP Preview (Desktop/Mobile)
- Keyword Suggestions

### Schema Markup (Schema & Meta Tab)
- Article Schema (blog posts)
- WebPage Schema (static pages)
- Organization Schema (site-wide)
- BreadcrumbList Schema
- Schema Validation (JSON-LD)

### Meta Robots
- noindex, nofollow, noarchive controls
- Canonical URL management
- Open Graph and Twitter Cards

---

## SEO Health Score

**Location:** Post Editor → SEO Page Health Tab

- Real-time SEO scoring (0-100)
- Signal Analysis (10 signals with pass/fail)
- Weighted scoring system
- Recalculate on demand

---

## History & Notes

**Location:** Post Editor → Notes & History Tab

- All meta field changes logged
- One-click restoration to previous versions
- User attribution and timestamps
- SEO Notes per post (up to 1000 chars)
- Search and filter notes

---

## Integrations

### Third-Party Compatibility
- Yoast SEO (conflict-free)
- AIOSEO (compatible)
- RankMath (coexistence)
- WPML/Polylang (multilingual)
- WooCommerce Ready

### APIs
- Google Search Console
- IndexNow (instant indexing)
- AlmaSEO Dashboard (optional cloud features)
- WordPress REST API

---

## Technical Notes

### Cron Jobs
- Daily sitemap rebuild
- Hourly news sitemap refresh
- Weekly evergreen analysis
- Daily plugin update checks

### Known Gaps
- DataForSEO provider implemented but no configuration UI
- Some "v12" files suggest iterative development
- Heavy reliance on AlmaSEO dashboard API for AI features

### Technical Debt
- Multiple loader files for Evergreen feature (stability iterations)
- Mixed PHP namespacing
- Legacy constant definitions for backwards compatibility
