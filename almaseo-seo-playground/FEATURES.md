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

## E-E-A-T Enforcement (Pro)

**Location:** SEO Playground → E-E-A-T

- Scans all published content for 6 types of trust signal gaps
- Detects: missing author, missing bio, missing author schema, missing credentials, no citation sources, missing review attribution
- YMYL category support for stricter checks on sensitive content
- Configurable post types, generic usernames, and scan toggles
- Optional health score integration (0–20 weight)
- Works locally — no external connections needed
- Resolved/dismissed findings survive re-scans

---

## GSC Monitor (Pro)

**Location:** SEO Playground → GSC Monitor

- Tracks indexation drift, rich result changes, and snippet rewrites
- Three tabs: Indexation, Rich Results, Snippets
- Bulk actions for efficient worklist management
- Configurable alert thresholds and auto-dismiss
- Smart deduplication prevents duplicate findings
- **Requires AlmaSEO dashboard connection + Google Search Console**

---

## 404 Intelligence (Pro Enhancement)

**Location:** SEO Playground → 404 Intelligence

Enhancements to the 404 tracking module:

- **Smart Redirect Suggestions** — matches 404 paths to existing posts via slug similarity and title keyword matching
- **Spike Detection** — flags 404 paths with 3x surge over 7-day average
- **Impact Scoring** — dashboard-pushed search impressions and clicks for prioritization
- **Redirect Chain Detection** — finds A→B→C redirect chains and suggests consolidation

---

## Orphan Page Detection (Pro)

**Location:** SEO Playground → Orphan Pages

- Finds pages with zero internal links pointing to them
- Classifies as orphan (0 links), weak (1–2), or addressed (3+)
- Cluster analysis groups pages by category to identify poorly connected topics
- Hub candidate identification for cornerstone content
- Integrates with Internal Links module for one-click fixes
- Works locally — no external connections needed

---

## Schema Drift Monitor (Pro)

**Location:** SEO Playground → Schema Drift

- Captures baseline snapshot of JSON-LD structured data
- Scans for drift: schemas removed, modified, added, or errored
- Auto-scans after plugin and theme updates (configurable)
- Configurable monitored post types and sample size
- Works locally — fetches your own pages via HTTP
- No external APIs needed

---

## Featured Snippet Targeting (Pro)

**Location:** SEO Playground → Snippet Targets

- Targets queries where you rank page 1 but don't hold the featured snippet
- Four snippet formats: paragraph, list, table, definition
- Draft editor with live preview and format-specific prompt hints
- One-click apply inserts content into posts (WordPress revision created)
- Clean undo removes snippet content without affecting original post
- Status tracking: opportunity → draft → approved → applied → won/lost
- **Requires AlmaSEO dashboard connection + Google Search Console**

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
