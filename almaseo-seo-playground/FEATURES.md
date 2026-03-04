# AlmaSEO SEO Playground - Features

## Quick Reference — All Free Features

AlmaSEO Playground ships **39 free features** covering everything Yoast, Rank Math, and AIOSEO offer for free — combined. Features marked with **AI Enhanced** gain additional capabilities when connected to the AlmaSEO cloud dashboard.

| # | Feature | Key File(s) | UI Location |
|---|---------|-------------|-------------|
| 1 | SEO Meta Box (Title, Description, SERP Preview) | `metabox-callback.php` | Post/Page editor |
| 2 | Focus Keywords | `metabox-callback.php` | Post editor, SEO Page Health tab |
| 3 | SEO Health Score (10 signals) | `includes/health/analyzer.php` | Post editor |
| 4 | Schema Markup (Article, WebPage, Organization, BreadcrumbList) | `includes/schema-clean.php`, `includes/schema/` | Frontend JSON-LD |
| 5 | XML Sitemaps (Posts, Pages, CPT, Taxonomy, Author) | `includes/sitemap/` | `/sitemap.xml` |
| 6 | Image Sitemaps | `includes/sitemap/providers/` | Embedded in XML sitemaps |
| 7 | Video Sitemaps | `includes/sitemap/providers/class-alma-provider-video.php` | Separate sitemap |
| 8 | News Sitemaps | `includes/sitemap/providers/class-alma-provider-news.php` | `/sitemap-news.xml` |
| 9 | HTML Sitemap (shortcode + block) | `includes/sitemap/` | `[almaseo_html_sitemap]` |
| 10 | IndexNow (Bing/Yandex instant indexing) | `includes/sitemap/class-alma-indexnow.php` | Automatic on publish |
| 11 | Robots.txt Editor | `admin/pages/robots-txt.php` | SEO Playground → Robots.txt |
| 12 | Evergreen Content Management | `includes/evergreen/` | SEO Playground → Evergreen |
| 13 | Breadcrumbs Module | `includes/breadcrumbs/` | Theme/shortcode |
| 14 | Open Graph & Twitter Cards | `metabox-callback.php` | Post editor, Schema & Meta tab |
| 15 | Meta Robots (noindex, nofollow, noarchive) | `metabox-callback.php` | Post editor |
| 16 | Canonical URL Management | `metabox-callback.php` | Post editor |
| 17 | SEO Notes & History | `includes/history/` | Post editor, Notes & History tab |
| 18 | Search Appearance (Title/Description Templates) | `includes/search-appearance/` | SEO Playground → Search Appearance |
| 19 | Import/Migration (Yoast, Rank Math, AIOSEO) | `includes/import/` | SEO Playground → Import SEO |
| 20 | Role/Access Manager | `includes/admin/role-manager.php` | Settings page |
| 21 | Setup Wizard | `includes/admin/setup-wizard.php` | First-run wizard |
| 22 | Webmaster Verification Codes | `includes/admin/verification-codes.php` | Settings page |
| 23 | RSS Feed Controls | `includes/admin/rss-controls.php` | Settings page |
| 24 | FAQ Block (FAQPage schema) | `includes/blocks/faq/` | Block editor |
| 25 | LLMs.txt Management | `includes/llms-txt/` | SEO Playground → LLMs.txt |
| 26 | TOC Block (Table of Contents) | `includes/blocks/toc/` | Block editor |
| 27 | Crawl Optimization | `includes/admin/crawl-optimization.php` | Settings page |
| 28 | .htaccess Editor | `includes/admin/htaccess-editor.php` | SEO Playground → .htaccess |
| 29 | Cornerstone Content | `includes/admin/cornerstone-content.php` | Posts/Pages list + editor |
| 30 | Breadcrumbs Gutenberg Block | `includes/blocks/breadcrumbs/` | Block editor |
| 31 | How-To Block (HowTo schema) | `includes/blocks/howto/` | Block editor |
| 32 | Link Attributes (nofollow/sponsored/ugc) | `includes/blocks/link-attributes.php` | Block editor link toolbar |
| 33 | Image SEO (auto alt/title) | `includes/admin/image-seo.php` | Settings page + frontend |
| 34 | Headline Analyzer | `includes/health/headline-analyzer.php` | Post editor, SEO Page Health tab |
| 35 | Enhanced Readability Analysis | `includes/health/readability.php` | Post editor, SEO Page Health tab |
| 36 | Visual Social Previews (Facebook + Twitter) | `metabox-callback.php` | Post editor, Schema & Meta tab |
| 37 | Google Keyword Suggestions | `includes/admin/keyword-suggestions.php` | Post editor, focus keyword field |
| 38 | Google Analytics Integration (GA4) | `includes/analytics/` | Settings page + frontend |
| 39 | Local Business Schema (193 subtypes) | `includes/schema/local-business-types.php` | Post editor, Schema & Meta tab |

---

## Core SEO Features

### SEO Meta Box (Page Optimization Panel)

**Location:** Post/Page editor (7 tabs)

**Tabs:**
1. **SEO Page Health** — Meta title (60 char counter), meta description (150-160 chars), focus keyword, Google SERP preview (desktop/mobile), keyword suggestions, headline analyzer
2. **Search Console** — GSC data when connected
3. **Schema & Meta** — Schema type selection, Open Graph, Twitter Cards, meta robots, canonical URL, Local Business fields, visual social previews
4. **AI Tools** — AI-powered content suggestions
5. **LLM Optimization** — LLM-specific meta tags
6. **Notes & History** — SEO notes per post, meta change history with one-click restore
7. **Unlock AI Features** — Shown only when not connected to AlmaSEO dashboard

### SEO Health Score

**Location:** Post Editor → SEO Page Health Tab

- Real-time SEO scoring (0-100) based on 10 weighted signals
- Signal analysis with pass/fail indicators
- Configurable weights in `includes/health/weights.php`
- Recalculate on demand

### Focus Keywords

- Set primary focus keyword per post
- Google Suggest autocomplete dropdown (debounced, arrow key navigation)
- Keyword density and placement analysis in health score

---

## Search Appearance (Templates)

**Location:** SEO Playground → Search Appearance

- Title and meta description templates for every content type
- Smart tags: `%%title%%`, `%%sitename%%`, `%%sep%%`, `%%excerpt%%`, `%%date%%`, `%%category%%`, `%%tag%%`, `%%author%%`, `%%page%%`
- Per post type: Posts, Pages, Categories, Tags, Author Archives, Date Archives, Search Results, 404 Page
- Live preview with smart tag resolution
- Settings saved in `almaseo_search_appearance` option

---

## Headline Analyzer

**Location:** Post Editor → SEO Page Health Tab (below title field)

- Client-side real-time scoring (0-100) with color-coded badge
- Analyzes: word count (optimal 6-13), power words, emotional words, numbers, question format, character length
- Instant feedback as you type
- **AI Enhanced**: When connected, shows CTR prediction, emotional impact analysis, competitor headlines, and AI rewrite suggestions

---

## Enhanced Readability Analysis

**Location:** Post Editor → SEO Page Health Tab (readability breakdown)

- Flesch Reading Ease score with grade level
- Passive voice percentage detection
- Transition word usage analysis
- Subheading distribution check (one per 300 words)
- Sentence length analysis (flags >20 word sentences)
- Paragraph length check (flags >150 word paragraphs)
- **AI Enhanced**: When connected, shows SERP competitor readability benchmarks, per-paragraph AI improvement suggestions

---

## Visual Social Previews

**Location:** Post Editor → Schema & Meta Tab

- Facebook card preview (1200x630 format with title, description, URL, site name)
- Twitter card preview (large image format with @handle)
- Live preview updates as you edit OG/Twitter meta fields
- Desktop and mobile preview modes

---

## Google Keyword Suggestions

**Location:** Post Editor → Focus Keyword Field

- Autocomplete dropdown powered by Google Suggest API
- Debounced input (300ms) with XHR requests
- Arrow key navigation and Enter to select
- Server-side AJAX proxy with 1-hour transient caching
- **AI Enhanced**: When connected, shows AI keyword insights panel with search volume, competition level, search intent classification, and trend direction

---

## Schema Markup

### Standard Schema Types
- **Article** — Blog posts with author, dates, images
- **WebPage** — Static pages
- **Organization** — Site-wide organization data
- **BreadcrumbList** — Navigation breadcrumbs

### Local Business Schema (193 Subtypes)

**Location:** Post Editor → Schema & Meta Tab (conditional fields)

When "LocalBusiness" is selected as schema type:
- Subtype dropdown with 193 types organized in 18 categories (Automotive, Entertainment, Financial, Food & Drink, Health & Medical, etc.)
- Business details: phone, email, price range, area served
- Address: street, city, state, postal code, country
- Geo coordinates: latitude and longitude
- Payment methods accepted
- Opening hours: per-day time pickers (Monday-Sunday)
- Full JSON-LD output with PostalAddress, GeoCoordinates, OpeningHoursSpecification

---

## Image SEO

**Location:** Settings Page → Image SEO Section + Frontend

- Automatically adds missing alt text and title attributes to images
- Template-based generation with smart tags: `%%filename%%`, `%%sitename%%`, `%%post_title%%`
- Strip file extension from filename when generating
- Optional override of existing alt text
- Applies to both `the_content` filter and `wp_get_attachment_image_attributes`
- **AI Enhanced**: When connected, prefers AI-generated context-aware alt text over template-based generation. Dashboard pushes per-image suggestions with confidence scores and decorative image detection.

---

## Cornerstone Content

**Location:** Posts/Pages List Table + Post Editor

- Star icon column in posts/pages list (after Title column)
- Toggle cornerstone status via Quick Edit checkbox
- Sortable and filterable column (Cornerstone Only / Non-Cornerstone filter dropdown)
- **AI Enhanced**: When connected, shows half-star "AI Suggested" indicator for posts the dashboard recommends as cornerstone. Metabox notice with score and one-click "Mark as Cornerstone" button.

---

## Crawl Optimization

**Location:** Settings Page → Crawl Optimization Section

- Remove RSD/WLW links from `<head>`
- Remove WordPress version meta tag
- Remove shortlink from `<head>`
- Disable REST API link in `<head>`
- Remove oEmbed discovery links
- Disable XML-RPC
- Remove feed links from `<head>`
- Remove emoji scripts and styles
- Remove jQuery Migrate
- All toggles independent, saved in `almaseo_crawl_optimization` option

---

## .htaccess Editor

**Location:** SEO Playground → .htaccess Editor

- Syntax-highlighted code editor for `.htaccess` file
- Read-only mode when file isn't writable
- Automatic backup before saving
- One-click restore from backup
- Security: nonce-protected, `manage_options` capability required

---

## Link Attributes (Block Editor)

**Location:** Block Editor → Link Toolbar

- Adds nofollow, sponsored, and ugc `rel` attribute toggles to the block editor link popover
- Works on all blocks that support links
- Toggles stored as standard `rel` attribute values

---

## Google Analytics Integration (GA4)

**Location:** Settings Page → Google Analytics Section + Frontend

- GA4 measurement ID input (validates `G-XXXXXXX` format)
- gtag.js snippet inserted on `wp_head` at priority 1
- Option to exclude logged-in administrators from tracking
- Option to anonymize IP addresses
- Optional outbound link click tracking
- Settings saved in `almaseo_analytics_settings` option

---

## Sitemaps

**Location:** SEO Playground → Sitemaps

### Sitemap Types
- Post/Page Sitemaps (paginated)
- Category/Tag Sitemaps
- Author Sitemaps
- Custom Post Type Sitemaps
- Image Sitemaps (embedded in post sitemaps)
- Video Sitemaps (YouTube/Vimeo detection)
- News Sitemaps (Google News 48-hour window)
- HTML Sitemaps (`[almaseo_html_sitemap]` shortcode)

### Advanced Features
- Dynamic & Static generation modes
- Delta Tracking (recent changes ring buffer)
- IndexNow Integration (Bing/Yandex/Seznam instant indexing)
- Hreflang Support (WPML/Polylang integration)
- Conflict Detection (Yoast/AIOSEO/RankMath safe coexistence)
- Robots.txt automatic integration

---

## Gutenberg Blocks

### FAQ Block (FAQPage Schema)
- Accordion-style FAQ entries
- Automatic FAQPage JSON-LD schema output
- Add/remove question-answer pairs
- Styled output with accessible markup

### Table of Contents Block
- Auto-generated from heading structure
- Configurable heading levels (H2-H6)
- Smooth scroll anchor links
- Collapsible/expandable
- Numbered or bulleted list style

### How-To Block (HowTo Schema)
- Step-by-step instructions with optional images
- Automatic HowTo JSON-LD schema output
- Total time, estimated cost fields
- Tool and supply lists

### Breadcrumbs Block
- Gutenberg block for breadcrumb navigation
- Customizable separator
- Schema.org BreadcrumbList markup
- Home link toggle

---

## Import / Migration

**Location:** SEO Playground → Import SEO

- Import from **Yoast SEO**: meta titles, descriptions, focus keywords, robots meta, Open Graph, schema settings
- Import from **Rank Math**: same fields plus Rank Math-specific schema and redirect data
- Import from **AIOSEO**: same fields plus AIOSEO-specific OG and Twitter data
- Dry-run mode shows what will be imported before committing
- Batch processing for large sites
- Post-import summary with counts

---

## Setup Wizard

**Location:** First activation + SEO Playground → Run Setup Wizard

- Step-by-step guided configuration
- Site type selection (blog, business, ecommerce, portfolio, etc.)
- Search appearance defaults based on site type
- Sitemap configuration
- Social profile URLs
- Webmaster verification code entry
- Optional AlmaSEO dashboard connection

---

## Role / Access Manager

**Location:** Settings Page → Role Manager Section

- Control which WordPress roles can access SEO features
- Per-role toggles: metabox access, settings access, admin menu visibility
- Defaults: Administrator (full), Editor (metabox only), Author (metabox only)

---

## Webmaster Verification Codes

**Location:** Settings Page → Verification Codes Section

- Google Search Console verification meta tag
- Bing Webmaster Tools verification meta tag
- Yandex Webmaster verification meta tag
- Pinterest verification meta tag
- Baidu Webmaster verification meta tag
- Tags output in `<head>` only on the homepage

---

## RSS Feed Controls

**Location:** Settings Page → RSS Controls Section

- Add custom content before RSS feed items
- Add custom content after RSS feed items
- Smart tags: `%%post_link%%`, `%%blog_link%%`, `%%blog_name%%`, `%%post_title%%`
- Useful for attribution links and copyright notices

---

## LLMs.txt Management

**Location:** SEO Playground → LLMs.txt

- Edit and serve a `/llms.txt` file for AI crawler guidance
- Template with sensible defaults
- Controls what content AI models can access/reference
- Virtual file served via WordPress rewrite (no physical file needed)

---

## Robots.txt Editor

**Location:** SEO Playground → Robots.txt

- Virtual mode (recommended): serves via WordPress filter
- Physical file mode: direct file editing
- Syntax-highlighted editor
- Live preview of served output
- Insert defaults button
- Input sanitization (removes PHP tags)

---

## Evergreen Content Management

**Location:** SEO Playground → Evergreen

- Content health monitoring by age, updates, and traffic
- Status categories: Evergreen, Watch, Stale
- Health trend charts (4/8/12 week views)
- At-risk content table (sortable)
- Batch analysis (up to 100 posts)
- Export to CSV/PDF
- Daily background cron refresh

---

## History & Notes

**Location:** Post Editor → Notes & History Tab

- All meta field changes logged with timestamps
- One-click restoration to previous versions
- User attribution
- SEO notes per post (up to 1000 chars)
- Search and filter notes

---

## Pro Features (Require Pro License)

| Feature | Module | Location |
|---------|--------|----------|
| Internal Links (auto-insert/undo) | `includes/internal-links/` | SEO Playground → Internal Links |
| Content Refresh Drafts | `includes/refresh-drafts/` | SEO Playground → Content Refresh |
| Refresh Queue Autoprioritization | `includes/refresh-queue/` | SEO Playground → Refresh Queue |
| Date Hygiene Scanner | `includes/date-hygiene/` | SEO Playground → Date Hygiene |
| E-E-A-T Enforcement | `includes/eeat/` | SEO Playground → E-E-A-T |
| GSC Monitor | `includes/gsc-monitor/` | SEO Playground → GSC Monitor |
| 404 Intelligence | `includes/404/` | SEO Playground → 404 Intelligence |
| Orphan Page Detection | `includes/orphan-detection/` | SEO Playground → Orphan Pages |
| Schema Drift Monitor | `includes/schema-drift/` | SEO Playground → Schema Drift |
| Featured Snippet Targeting | `includes/snippet-targets/` | SEO Playground → Snippet Targets |

---

## Dashboard Enhancement Layer (AI-Powered)

When connected to the AlmaSEO cloud dashboard, 5 free features gain AI capabilities that competitors cannot match. All features work standalone without the dashboard — the AI layer is a bonus.

| Feature | Enhancement | REST Endpoint |
|---------|-------------|---------------|
| Keyword Suggestions | AI keywords with volume, competition, intent, trends | `/almaseo/v1/keyword-suggestions/push` |
| Headline Analyzer | CTR prediction, emotional impact, competitor headlines, rewrites | `/almaseo/v1/headline-analyzer/push` |
| Readability | SERP competitor benchmarks, per-paragraph AI suggestions | `/almaseo/v1/readability/push` |
| Image SEO | Context-aware AI alt text with confidence scores | `/almaseo/v1/image-seo/push` |
| Cornerstone Content | Auto-suggest cornerstone posts based on traffic/authority | `/almaseo/v1/cornerstone/push` |

**Architecture**: Dashboard pushes data via Basic Auth REST endpoints. Plugin stores in post meta (`_almaseo_{feature}_dashboard`). UI shows "AI Enhanced" badges when data is available. No feature breaks if the dashboard is offline.

---

## Integrations

### Third-Party Plugin Compatibility
- Yoast SEO (conflict-free coexistence)
- AIOSEO (compatible)
- RankMath (coexistence)
- WPML/Polylang (multilingual sitemaps)
- WooCommerce ready
- AlmaSEO Connector (shared options, one-click deactivation notice)

### APIs & External Services
- Google Search Console (via AlmaSEO dashboard)
- Google Suggest API (keyword autocomplete)
- Google Analytics (GA4 gtag.js)
- IndexNow (Bing/Yandex instant indexing)
- AlmaSEO Dashboard (AI features, optional)
- WordPress REST API (all modules)

---

## Technical Notes

### Cron Jobs
- Daily sitemap rebuild
- Hourly news sitemap refresh
- Weekly evergreen analysis
- Daily plugin update checks

### Module Pattern
All feature modules follow: `loader → install → model → engine → rest → controller`
- Admin pages in `admin/pages/`
- CSS/JS in `assets/css/` and `assets/js/`
- REST namespace: `almaseo/v1`

### Settings Architecture
- Global settings via `register_setting('almaseo_settings', ...)`
- Per-module settings via `do_action('almaseo_settings_sections')` hook
- Each settings section is a static class with `render_settings()` and `sanitize()` methods
