# AlmaSEO SEO Playground Plugin

## Project Overview
- **Plugin Version:** 8.5.0
- **Plugin Source:** `almaseo-seo-playground/` (root of this repo)
- **Main Plugin File:** `almaseo-seo-playground/almaseo-seo-playground.php`

## Live Testing Workflow
A Windows junction connects the plugin source directly to the Local by Flywheel WordPress site:

- **Source:** `C:\Users\ishla\Desktop\SEO ACTUAL PLAYGROUND PLUGIN\SEO_Playground_Plugin\almaseo-seo-playground\`
- **WP Plugins Dir:** `C:\Users\ishla\Local Sites\my-playground\app\public\wp-content\plugins\almaseo-seo-playground` (junction)
- **PHP Binary:** `C:\Users\ishla\AppData\Roaming\Local\lightning-services\php-8.2.27+1\bin\win64\php.exe`

**Every file edit is instantly live.** The user just refreshes their browser to see changes — no zip, upload, or reinstall needed.

## Local WordPress Site
- **Platform:** Local by Flywheel
- **Site Path:** `C:\Users\ishla\Local Sites\my-playground\app\public\`
- **WP Admin:** Accessible via Local app
- **Debug:** `WP_DEBUG=true`, `WP_DEBUG_LOG=true`, `WP_DEBUG_DISPLAY=false`

## Connector Plugin Coexistence
Both the AlmaSEO Connector and SEO Playground can be active simultaneously:
- All shared constants guarded with `!defined()`
- All shared functions guarded with `function_exists()`
- Smart admin notice with one-click "Deactivate Connector" button
- Connection data preserved via shared `wp_options` keys
- Connector source: `C:\Users\ishla\Desktop\Luma SEO older scripts\FULLY WORKING WITH MULTIPLE SITE OPTIONS\almaseo-connector\`
- Connector version: 1.5.0 (with matching guards)

## Key Files

| Purpose | File |
|---------|------|
| Main plugin file | `almaseo-seo-playground.php` |
| Page optimization panel (7 tabs) | `admin/partials/metabox-callback.php` |
| Health score calculation | `includes/health/analyzer.php` |
| Health signal weights | `includes/health/weights.php` |
| Sitemap functionality | `includes/sitemap/` |
| Evergreen content | `includes/evergreen/` |
| Admin settings | `includes/admin/settings.php` |
| License/tier gating | `includes/license/license-helper.php` |
| Locked feature UI | `includes/license/locked-ui.php` |
| LLM analysis REST | `includes/llm/llm-rest.php` |
| Redirects module | `includes/redirects/` |
| 404 tracking module | `includes/404/` |
| Bulk meta editor | `includes/bulkmeta/` |
| **Internal links module** | **`includes/internal-links/`** |
| **Refresh drafts module** | **`includes/refresh-drafts/`** |
| **Refresh queue module** | **`includes/refresh-queue/`** |
| **Date hygiene module** | **`includes/date-hygiene/`** |
| AJAX handlers | `includes/ajax/seo-playground-ajax.php` |
| Post save handler | `includes/admin/post-save-handler.php` |
| Connection settings page | `admin/pages/connection-settings.php` |
| Overview dashboard | `admin/pages/overview.php` |
| **Search appearance** | **`includes/search-appearance/`** |
| **Import/migration** | **`includes/import/`** |
| **Role manager** | **`includes/admin/role-manager.php`** |
| **Setup wizard** | **`includes/admin/setup-wizard.php`** |
| **Verification codes** | **`includes/admin/verification-codes.php`** |
| **RSS controls** | **`includes/admin/rss-controls.php`** |
| **Crawl optimization** | **`includes/admin/crawl-optimization.php`** |
| **Image SEO** | **`includes/admin/image-seo.php`** |
| **Cornerstone content** | **`includes/admin/cornerstone-content.php`** |
| **.htaccess editor** | **`includes/admin/htaccess-editor.php`** |
| **Keyword suggestions** | **`includes/admin/keyword-suggestions.php`** |
| **Headline analyzer** | **`includes/health/headline-analyzer.php`** |
| **Readability analysis** | **`includes/health/readability.php`** |
| **Google Analytics** | **`includes/analytics/`** |
| **Local Business types** | **`includes/schema/local-business-types.php`** |
| **LLMs.txt** | **`includes/llms-txt/`** |
| **FAQ block** | **`includes/blocks/faq/`** |
| **TOC block** | **`includes/blocks/toc/`** |
| **How-To block** | **`includes/blocks/howto/`** |
| **Breadcrumbs block** | **`includes/blocks/breadcrumbs/`** |
| **Link attributes block** | **`includes/blocks/link-attributes.php`** |
| **KW suggestions REST (AI)** | **`includes/admin/keyword-suggestions-rest.php`** |
| **Image SEO REST (AI)** | **`includes/admin/image-seo-rest.php`** |
| **Cornerstone REST (AI)** | **`includes/admin/cornerstone-rest.php`** |
| **Headline analyzer REST (AI)** | **`includes/health/headline-analyzer-rest.php`** |
| **Readability REST (AI)** | **`includes/health/readability-rest.php`** |

## Internal Links Module (v7.0.0)

### Feature: Automated Internal Link Insertion (Guardrailed)

**Tier**: Hybrid — Free users see suggestions and approve/reject, Pro users can auto-insert/undo.

**Data flow**: AlmaSEO dashboard pushes recommendations → plugin stores in DB → admin reviews on dedicated page → Pro users apply with one click.

### Files

| File | Purpose |
|------|---------|
| `includes/internal-links/internal-links-loader.php` | Module bootstrap |
| `includes/internal-links/internal-links-install.php` | DB table creation (`wp_almaseo_internal_links`) |
| `includes/internal-links/internal-links-model.php` | CRUD + guardrail queries |
| `includes/internal-links/internal-links-engine.php` | Content insertion/removal with guardrails |
| `includes/internal-links/internal-links-rest.php` | REST API (10 endpoints) |
| `includes/internal-links/internal-links-controller.php` | Admin menu, assets, localization |
| `admin/pages/internal-links.php` | Admin page template |
| `assets/css/internal-links.css` | Admin page styles |
| `assets/js/internal-links.js` | Admin page JavaScript (SPA-like) |

### Database Table: `wp_almaseo_internal_links`

| Column | Type | Purpose |
|--------|------|---------|
| source_post_id | BIGINT | Post that should contain the link |
| target_post_id | BIGINT | Post being linked to (optional) |
| target_url | TEXT | Full URL of link target |
| anchor_text | VARCHAR(255) | Text to wrap with the link |
| suggested_context | TEXT | Paragraph snippet to locate insertion point |
| confidence_score | DECIMAL(5,2) | 0.00–1.00 confidence from dashboard |
| reason | TEXT | Why this link was recommended |
| status | VARCHAR(20) | pending/approved/applied/rejected/removed |
| content_hash | VARCHAR(64) | MD5 of post_content before insertion (for undo) |
| original_paragraph | TEXT | Original paragraph text (for undo) |

### REST API Endpoints

**Dashboard push** (auth: Basic Auth via app password):
- `POST /wp-json/almaseo/v1/internal-links/push` — Receive batch recommendations

**Admin UI** (auth: `manage_options` capability):
- `GET /internal-links` — List (paginated, filtered)
- `GET /internal-links/stats` — Status counts
- `GET /internal-links/post/{id}` — Per-post suggestions
- `PATCH /internal-links/{id}/approve` — Approve
- `PATCH /internal-links/{id}/reject` — Reject
- `POST /internal-links/{id}/apply` — Insert link (Pro)
- `POST /internal-links/{id}/undo` — Remove link (Pro)
- `POST /internal-links/bulk` — Bulk approve/reject/apply

### Guardrails (in `internal-links-engine.php`)
1. **Max links per post** — Configurable via `almaseo_internal_links_max_per_post` option (default 5)
2. **No duplicate targets** — Same URL can't be linked twice from one post
3. **No linking inside existing links** — Won't wrap text that's already in an `<a>` tag
4. **No linking inside headings** — Won't link text inside `<h1>`–`<h6>`
5. **Anchor-target consistency** — Same anchor text can't point to different URLs across the site

### Pro Feature Gate
- Feature identifier: `internal_links_auto`
- Registered in `license-helper.php` `$pro_features` array
- Apply and Undo endpoints check `almaseo_feature_available('internal_links_auto')`
- Free users see a Pro upgrade modal when clicking Apply/Undo buttons

## Content Refresh Drafts Module (v7.1.0)

### Feature: Diff-based Refresh Drafts (Side-by-Side Changes)

**Tier**: Pro only — entire feature gated behind `almaseo_feature_available('refresh_drafts')`.

**Data flow**: AlmaSEO dashboard pushes AI-generated content refresh → plugin splits at headings, computes word-level diffs → admin reviews side-by-side → accepts/rejects each section → applies accepted sections to live post (WordPress revision created automatically).

### Files

| File | Purpose |
|------|---------|
| `includes/refresh-drafts/refresh-drafts-loader.php` | Module bootstrap |
| `includes/refresh-drafts/refresh-drafts-install.php` | DB table creation (`wp_almaseo_refresh_drafts`) |
| `includes/refresh-drafts/refresh-drafts-model.php` | CRUD, section status updates, bulk updates, status counts |
| `includes/refresh-drafts/refresh-drafts-engine.php` | Section splitting, heading alignment, word-level LCS diff, selective merge, content drift detection |
| `includes/refresh-drafts/refresh-drafts-rest.php` | REST API (9 endpoints) |
| `includes/refresh-drafts/refresh-drafts-controller.php` | Admin menu "Content Refresh" (priority 23), assets, Pro gate |
| `admin/pages/refresh-drafts.php` | List page template |
| `admin/pages/refresh-drafts-review.php` | Review page template (side-by-side diff) |
| `assets/css/refresh-drafts.css` | Admin page styles (diff highlighting, section cards, responsive) |
| `assets/js/refresh-drafts.js` | Admin page JavaScript (list + review pages) |

### Database Table: `wp_almaseo_refresh_drafts`

| Column | Type | Purpose |
|--------|------|---------|
| post_id | BIGINT (UNIQUE) | One active draft per post |
| draft_content | LONGTEXT | Full proposed content from dashboard |
| sections_json | LONGTEXT | JSON array of section diffs with per-section status |
| content_hash | VARCHAR(64) | MD5 of live post_content at push time (drift detection) |
| status | VARCHAR(30) | pending/reviewing/applied/rejected/expired |
| source | VARCHAR(30) | Origin of the draft (default: 'dashboard') |
| created_by | BIGINT | User who triggered the push |
| applied_by | BIGINT | User who applied the changes |
| applied_at | DATETIME | When changes were merged |

### REST API Endpoints

**Dashboard push** (auth: Basic Auth via app password — not tier-gated):
- `POST /wp-json/almaseo/v1/refresh-drafts/push` — Receive content refresh draft

**Admin UI** (auth: `manage_options` + Pro gate):
- `GET /refresh-drafts` — List drafts (paginated, filtered)
- `GET /refresh-drafts/stats` — Status counts
- `GET /refresh-drafts/{id}` — Full draft with sections + drift check
- `PATCH /refresh-drafts/{id}/sections/{index}` — Accept/reject one section
- `PATCH /refresh-drafts/{id}/sections/bulk` — Accept all / reject all
- `POST /refresh-drafts/{id}/apply` — Merge accepted sections into post
- `POST /refresh-drafts/{id}/reject` — Reject entire draft
- `DELETE /refresh-drafts/{id}` — Delete draft

### Engine Details (`refresh-drafts-engine.php`)

**Section splitting**: Content split at `<h2>`, `<h3>`, `<h4>` boundaries. Content before the first heading = intro section.

**Heading alignment**: Three passes — (1) exact heading match, (2) fuzzy match via `similar_text()` >60% threshold, (3) unmatched sections marked as "added" or "removed".

**Word-level diff**: LCS algorithm for content <500 tokens, greedy lookahead approach for longer content. Produces `<del>`/`<ins>` HTML.

**Selective merge**: Only accepted sections use draft content. Rejected/pending sections keep live content. `wp_update_post()` auto-creates WordPress revision.

**Content drift detection**: MD5 hash of `post_content` at push time vs current content. Warning shown on review page if content has been edited since draft was pushed.

### Pro Feature Gate
- Feature identifier: `refresh_drafts`
- Registered in `license-helper.php` `$pro_features` array
- All admin REST endpoints check `almaseo_feature_available('refresh_drafts')`
- Dashboard push endpoint is NOT tier-gated (dashboard can always push)
- Free users see locked feature screen via `almaseo_render_feature_locked('refresh_drafts')`

## Refresh Queue Autoprioritization Module (v7.2.0)

### Feature: Priority-ranked Content Refresh Queue

**Tier**: Pro only — entire feature gated behind `almaseo_feature_available('refresh_queue')`.

**Data flow**: Plugin calculates 4 signal scores locally (business value, traffic decline, conversion intent, opportunity size) using evergreen/health data. Dashboard can optionally push richer scores via REST. Composite priority score ranks all published posts.

### Files

| File | Purpose |
|------|---------|
| `includes/refresh-queue/refresh-queue-loader.php` | Module bootstrap |
| `includes/refresh-queue/refresh-queue-install.php` | DB table creation (`wp_almaseo_refresh_queue`) |
| `includes/refresh-queue/refresh-queue-model.php` | CRUD, stats, upsert, prune orphaned |
| `includes/refresh-queue/refresh-queue-engine.php` | Signal calculation, composite scoring, batch recalculation |
| `includes/refresh-queue/refresh-queue-rest.php` | REST API (8 endpoints) |
| `includes/refresh-queue/refresh-queue-controller.php` | Admin menu "Refresh Queue" (priority 35), assets, Pro gate |
| `admin/pages/refresh-queue.php` | Admin page template (stats cards, table, settings panel) |
| `assets/css/refresh-queue.css` | Admin page styles (priority badges, signal bars, responsive) |
| `assets/js/refresh-queue.js` | Admin page JavaScript (queue table, recalculate, skip/restore, settings) |

### Database Table: `wp_almaseo_refresh_queue`

| Column | Type | Purpose |
|--------|------|---------|
| post_id | BIGINT (UNIQUE) | One entry per post |
| priority_score | DECIMAL(5,2) | Composite weighted score (0-100) |
| business_value | DECIMAL(5,2) | Signal: business importance (0-100) |
| traffic_decline | DECIMAL(5,2) | Signal: traffic drop severity (0-100) |
| conversion_intent | DECIMAL(5,2) | Signal: conversion potential (0-100) |
| opportunity_size | DECIMAL(5,2) | Signal: improvement potential (0-100) |
| priority_tier | VARCHAR(10) | high (>=70) / medium (50-69) / low (<50) |
| status | VARCHAR(20) | queued/skipped/refreshed |

### REST API Endpoints

**Dashboard push** (auth: Basic Auth via app password — NOT tier-gated):
- `POST /wp-json/almaseo/v1/refresh-queue/push` — Receive batch signal scores

**Admin UI** (auth: `manage_options` + Pro gate):
- `GET /refresh-queue` — List queue (paginated, sorted, filtered)
- `GET /refresh-queue/stats` — Counts by tier + status
- `POST /refresh-queue/recalculate` — Trigger full recalculation
- `PATCH /refresh-queue/{id}/skip` — Skip a post
- `PATCH /refresh-queue/{id}/restore` — Un-skip a post
- `GET /refresh-queue/settings` — Get weight settings
- `POST /refresh-queue/settings` — Save weight settings

### Scoring Engine (`refresh-queue-engine.php`)

**Four signals** (each 0-100): business_value, traffic_decline, conversion_intent, opportunity_size.
**Local fallbacks**: post type scoring, evergreen traffic data, inverted health score, URL path analysis.
**Dashboard overrides**: `_almaseo_rq_{signal_name}` post meta.
**Composite**: Weighted sum with configurable weights (default: 25/30/20/25).
**Batch**: Processes all published posts in batches of 50, preserves skipped status.

## Date Hygiene Scanner Module (v7.3.0)

### Feature: Passage-Level Stale Content Detection

**Tier**: Pro only — entire feature gated behind `almaseo_feature_available('date_hygiene')`.

**Scan trigger**: Manual only — user clicks "Scan Now".

**Data flow**: User triggers scan → engine scans all published posts with regex patterns → findings stored in DB with context + suggestions → admin reviews/resolves/dismisses on dedicated page. Dashboard can also push NLP-detected findings.

### Files

| File | Purpose |
|------|---------|
| `includes/date-hygiene/date-hygiene-loader.php` | Module bootstrap |
| `includes/date-hygiene/date-hygiene-install.php` | DB table creation (`wp_almaseo_date_hygiene`) |
| `includes/date-hygiene/date-hygiene-model.php` | CRUD, stats, batch insert |
| `includes/date-hygiene/date-hygiene-engine.php` | 5 detection methods, batch scanning |
| `includes/date-hygiene/date-hygiene-rest.php` | REST API (9 endpoints) |
| `includes/date-hygiene/date-hygiene-controller.php` | Admin menu "Date Hygiene" (priority 38), assets, Pro gate |
| `admin/pages/date-hygiene.php` | Admin page template |
| `assets/css/date-hygiene.css` | Admin page styles |
| `assets/js/date-hygiene.js` | Admin page JavaScript |
| `docs/date-hygiene.md` | Documentation |

### Detection Methods

| Type | Pattern | Severity |
|------|---------|----------|
| `stale_year` | Year 2+ years behind current | high (title) / medium (body) |
| `dated_phrase` | "as of", "updated" + old date | high |
| `superlative_year` | "best of", "top" + old year | high (title) / medium (body) |
| `price_reference` | Currency amounts | medium |
| `regulation_mention` | Regulation name + year | low |

### Database Table: `wp_almaseo_date_hygiene`

| Column | Type | Purpose |
|--------|------|---------|
| post_id | BIGINT | Post containing the finding |
| finding_type | VARCHAR(30) | One of 5 detection types |
| severity | VARCHAR(10) | high, medium, low |
| detected_value | VARCHAR(255) | The flagged value ("2022", "$29/month") |
| context_snippet | TEXT | ~100 chars surrounding context |
| suggestion | TEXT | Suggested fix |
| location | VARCHAR(20) | title or body |
| status | VARCHAR(20) | open, resolved, dismissed |

### REST API Endpoints

**Dashboard push** (auth: Basic Auth via app password — NOT tier-gated):
- `POST /wp-json/almaseo/v1/date-hygiene/push` — Receive NLP-detected findings

**Admin UI** (auth: `manage_options` + Pro gate):
- `GET /date-hygiene` — List findings (paginated, filtered)
- `GET /date-hygiene/stats` — Counts by severity + status + type
- `POST /date-hygiene/scan` — Trigger full-site scan
- `PATCH /date-hygiene/{id}/resolve` — Mark as resolved
- `PATCH /date-hygiene/{id}/dismiss` — Mark as dismissed
- `PATCH /date-hygiene/{id}/reopen` — Re-open finding
- `GET /date-hygiene/settings` — Get scan settings
- `POST /date-hygiene/settings` — Save scan settings

### Configurable Settings (option: `almaseo_dh_settings`)

- `stale_threshold`: Years behind current year to flag (default: 2)
- `scan_prices`: Toggle price reference detection (default: true)
- `scan_regulations`: Toggle regulation mention detection (default: true)

## Competitive Parity Features (v8.0.0–v8.2.0)

39 free features matching everything Yoast, Rank Math, and AIOSEO offer for free — combined. All use the settings hook pattern (`almaseo_settings_sections`) or block registration.

### Settings-based Features (in `includes/admin/`)
- **Crawl Optimization** (`crawl-optimization.php`) — wp_head cleanup toggles, option: `almaseo_crawl_optimization`
- **Image SEO** (`image-seo.php`) — auto alt/title with templates, option: `almaseo_image_seo_settings`
- **Cornerstone Content** (`cornerstone-content.php`) — star column, Quick Edit, sortable/filterable, meta: `_almaseo_is_cornerstone`
- **.htaccess Editor** (`htaccess-editor.php` + `admin/pages/htaccess-editor.php`) — editor with backup/restore
- **Keyword Suggestions** (`keyword-suggestions.php`) — Google Suggest AJAX proxy, transient cached
- **Role Manager** (`role-manager.php`) — per-role access control
- **Setup Wizard** (`setup-wizard.php`) — guided first-run
- **Verification Codes** (`verification-codes.php`) — webmaster meta tags
- **RSS Controls** (`rss-controls.php`) — before/after feed content

### Health-based Features (in `includes/health/`)
- **Headline Analyzer** (`headline-analyzer.php`) — client-side scoring in metabox
- **Readability** (`readability.php`) — Flesch, passive voice, transitions, subheadings

### Analytics Module (in `includes/analytics/`)
- **Loader** (`analytics-loader.php`) — bootstraps settings + tracking
- **Settings** (`analytics-settings.php`) — GA4 config, option: `almaseo_analytics_settings`
- **Tracking** (`analytics-tracking.php`) — gtag.js output on wp_head

### Schema Extension
- **Local Business Types** (`includes/schema/local-business-types.php`) — 193 subtypes in 18 categories
- Conditional fields in metabox (Schema & Meta tab), saved in `post-save-handler.php`
- Full JSON-LD output in `schema-advanced-output.php` (`almaseo_build_localbusiness_node()`)

### Block Editor Features (in `includes/blocks/`)
- **FAQ Block** (`faq/`) — FAQPage schema
- **TOC Block** (`toc/`) — auto-generated table of contents
- **How-To Block** (`howto/`) — HowTo schema
- **Breadcrumbs Block** (`breadcrumbs/`) — BreadcrumbList schema
- **Link Attributes** (`link-attributes.php`) — nofollow/sponsored/ugc toggles

### Other Modules
- **Search Appearance** (`includes/search-appearance/`) — title/description templates with smart tags
- **Import** (`includes/import/`) — Yoast, Rank Math, AIOSEO migration
- **LLMs.txt** (`includes/llms-txt/`) — AI crawler guidance file

## Dashboard Enhancement Layer (v8.5.0)

5 free features enhanced with AI when connected to AlmaSEO cloud dashboard. All use the push/store/display pattern.

### Architecture
```
Dashboard → POST /wp-json/almaseo/v1/{feature}/push (Basic Auth)
         → Plugin stores in post meta: _almaseo_{feature}_dashboard
         → Metabox UI shows "AI Enhanced" badge when data available

Plugin   → POST https://app.almaseo.com/api/v1/{feature}/analyze (Bearer token)
         → Real-time request on user interaction
         → Cached in transient (1-hour TTL)
```

### REST Files
| File | Feature | Push Endpoint |
|------|---------|---------------|
| `includes/admin/keyword-suggestions-rest.php` | AI Keywords | `/almaseo/v1/keyword-suggestions/push` |
| `includes/health/headline-analyzer-rest.php` | AI Headlines | `/almaseo/v1/headline-analyzer/push` |
| `includes/health/readability-rest.php` | AI Readability | `/almaseo/v1/readability/push` |
| `includes/admin/image-seo-rest.php` | AI Image SEO | `/almaseo/v1/image-seo/push` |
| `includes/admin/cornerstone-rest.php` | AI Cornerstone | `/almaseo/v1/cornerstone/push` |

### Post Meta Keys
- `_almaseo_kw_suggestions` — AI keyword data (JSON)
- `_almaseo_headline_dashboard` — AI headline analysis (JSON)
- `_almaseo_readability_dashboard` — AI readability benchmarks (JSON)
- `_almaseo_image_seo_dashboard` — AI image alt text suggestions (JSON)
- `_almaseo_cornerstone_suggested` — boolean flag
- `_almaseo_cornerstone_score` — 0-100 confidence
- `_almaseo_cornerstone_reason` — text explanation
- `_almaseo_cornerstone_metrics` — JSON (traffic, backlinks, word_count, internal_links)

### Key Principle
Free local analysis always works. Dashboard data overlays/enhances when available. Connected users see "AI Powered" badges. No feature breaks if dashboard is offline. Connection check: `seo_playground_is_alma_connected()`.

## Tab Order (Page Optimization Panel)

1. SEO Page Health (default) - meta title, description, focus keyword, SERP preview
2. Search Console
3. Schema & Meta
4. AI Tools
5. LLM Optimization
6. Notes & History
7. Unlock AI Features (conditional - only when not connected)

## Module Pattern
All feature modules follow the same structure (used by redirects, 404, bulkmeta, internal-links, refresh-drafts, refresh-queue, date-hygiene):
```
includes/{module}/
  {module}-loader.php      → Bootstrap, load dependencies, init hook
  {module}-install.php     → dbDelta table creation, version check
  {module}-model.php       → Static class with CRUD methods
  {module}-controller.php  → Admin menu, asset enqueue, REST init
  {module}-rest.php        → REST API route registration
```
Admin page template in `admin/pages/`, CSS/JS in `assets/css/` and `assets/js/`.
