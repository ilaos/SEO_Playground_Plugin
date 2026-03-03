# Changelog

All notable changes to AlmaSEO SEO Playground will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [7.9.0] - 2026-03-02

### Added
- **Featured Snippet Targeting** (Pro) — new module to win Google's "position zero" answer box
- Targets queries where you rank on page 1 but don't hold the featured snippet
- Four snippet formats: paragraph, list, table, definition
- Draft editor with live preview and format-specific prompt hints
- One-click apply inserts content into posts (WordPress revision created automatically)
- Clean undo removes snippet content without affecting original post
- Status tracking: opportunity → draft → approved → applied → won/lost/expired
- Connection Required notice with dynamic "Connect to AlmaSEO" button
- Beginner-friendly instructions explaining what featured snippets are
- Requires AlmaSEO dashboard connection with Google Search Console linked

## [7.8.0] - 2026-03-01

### Added
- **Schema Drift Monitor** (Pro) — detect when structured data changes unexpectedly
- Captures baseline snapshot of JSON-LD structured data on representative pages
- Scans for drift: schemas removed, modified, added, or errored
- Auto-scans after plugin and theme updates (configurable, 30s delay)
- Configurable monitored post types and sample size per type (1–50)
- Works locally via `wp_remote_get()` — no external APIs needed
- Severity levels: high (removed), medium (modified/error), low (added)
- Resolve, dismiss, and reopen actions per finding
- Beginner-friendly admin instructions with "before photo" analogy

## [7.7.0] - 2026-02-28

### Added
- **Orphan Page Detection** (Pro) — find pages with zero internal links pointing to them
- Classifies pages as orphan (0 links), weak (1–2), or addressed (3+)
- Cluster analysis groups pages by category to identify poorly connected topics
- Hub candidate identification for cornerstone content
- Integrates with Internal Links module for one-click fixes
- Auto-generated internal link rule suggestions for orphan pages
- Works locally — no external connections needed

## [7.6.0] - 2026-02-27

### Added
- **404 Intelligence enhancements** — smart analytics on top of the 404 log
- Smart redirect suggestions: slug similarity and title keyword matching against published posts
- Spike detection: flags 404 paths with 3x surge over 7-day average
- Impact scoring: dashboard-pushed search impressions and clicks for prioritization
- Redirect chain detection: finds A→B→C chains and suggests consolidation
- Renamed sidebar menu from "404 Logs" to "404 Intelligence"

## [7.5.0] - 2026-02-26

### Added
- **GSC Monitor** (Pro) — track indexation drift, rich result changes, and snippet rewrites
- Three tabs: Indexation, Rich Results, Snippets with per-tab stats
- Finding types: not_indexed, excluded_spike, coverage_drop, rich result lost/gained/degraded, title/description rewrite
- Bulk actions for efficient worklist management (resolve, dismiss, reopen)
- Smart deduplication prevents duplicate findings on push
- Configurable alert thresholds and auto-dismiss
- Connection Required warning box with dynamic "Connect to AlmaSEO" button
- Requires AlmaSEO dashboard connection with Google Search Console linked

## [7.4.0] - 2026-02-25

### Added
- **E-E-A-T Enforcement** (Pro) — scan published content for trust signal gaps
- Six detection methods: missing author, missing bio, missing author schema, missing credentials, no citation sources, missing review attribution
- YMYL category support for stricter checks on sensitive content
- Configurable post types, generic usernames, and scan toggles
- Optional health score integration (0–20 weight)
- Works locally — no external connections needed
- Resolved/dismissed findings survive re-scans (dedup by post_id + finding_type)
- Configurable "Post Types to Scan" setting (default: post, page, product)

## [7.3.0] - 2026-02-24

### Added
- **Date Hygiene Scanner** (Pro) — passage-level stale content detection
- Five detection methods: stale years, dated phrases, superlative years, price references, regulation mentions
- Configurable stale threshold, price scanning, regulation scanning
- Dashboard push endpoint for NLP-detected findings
- Configurable "Post Types to Scan" setting (default: post, page, product)
- Resolved/dismissed findings survive re-scans

## [7.2.0] - 2026-02-23

### Added
- **Refresh Queue Autoprioritization** (Pro) — priority-ranked content refresh queue
- Four signal scores: business value, traffic decline, conversion intent, opportunity size
- Local fallback scoring using evergreen/health data
- Dashboard can push richer signal scores via REST
- Configurable signal weights (default: 25/30/20/25)
- Batch recalculation with skip/restore per post

## [7.1.0] - 2026-02-22

### Added
- **Content Refresh Drafts** (Pro) — diff-based side-by-side content refresh review
- Section splitting at heading boundaries with heading alignment
- Word-level LCS diff with `<del>`/`<ins>` highlighting
- Accept/reject per section, bulk accept/reject all
- Content drift detection (MD5 hash comparison)
- Selective merge into live post with WordPress revision

## [7.0.0] - 2026-02-21

### Added
- **Internal Links** (Hybrid Free/Pro) — automated internal link insertion with guardrails
- Dashboard pushes recommendations, admin reviews on dedicated page
- Five guardrails: max links per post, no duplicate targets, no linking inside existing links or headings, anchor-target consistency
- Pro users can auto-insert and undo with one click
- Free users see suggestions and approve/reject

---

## [5.9.2] - 2025-08-30

### Fixed
- **Evergreen**: Implemented admin-post.php handler for "Analyze All Posts" button
- **Evergreen**: Fixed jQuery loading issues completely
- **Evergreen**: Added proper server-side form handling without JavaScript dependency
- **Evergreen**: Improved success notifications and redirect flow
- **Evergreen**: Enhanced cache clearing after analysis

## [5.9.1] - 2025-08-30

### Fixed
- **Evergreen**: Fixed "Analyze All Posts" functionality
- **Evergreen**: Added better error handling and debugging messages
- **Evergreen**: Fixed query to specifically target unanalyzed posts
- **Evergreen**: Improved batch processing with error collection
- **Evergreen**: Enhanced user feedback during analysis

## [5.9.0] - 2025-08-30

### Added
- **Evergreen**: Comprehensive caching system with 12-hour TTL
- **Evergreen**: Daily background cron job for cache refresh
- **Evergreen**: "Last updated" timestamp display
- **Evergreen**: Processing indicators and spinners
- **Evergreen**: Detailed warnings about batch limits

### Fixed
- **Evergreen**: Resolved jQuery "not defined" errors
- **Evergreen**: Fixed inline script execution issues
- **Evergreen**: Improved button organization and UX

## [5.8.9] - 2025-08-30

### Fixed
- **Evergreen**: Fixed jQuery loading issues in dashboard
- **Evergreen**: Moved all inline JavaScript to external files
- **Evergreen**: Proper script enqueueing with jQuery dependency
- **Evergreen**: Exposed chart function to window scope

## [5.6.0] - 2025-08-29

### 🎉 Milestone Release - All Systems Operational

#### Added
- Comprehensive README.md documentation
- Detailed FEATURES.md with 200+ features documented
- Complete CHANGELOG.md for version tracking

#### Fixed
- All sitemap tabs now fully functional
- Complete UI restoration with proper CSS and JavaScript
- All critical PHP errors resolved
- All visual styling issues corrected

#### Summary
This release represents full restoration of all sitemap functionality with complete documentation.

## [5.5.9] - 2025-08-29

### Fixed
- **CRITICAL**: Fixed Change Detection tab fatal error
- Fixed undefined method `Alma_Provider_Delta::get_recent_changes()`
- Added proper method existence checks before calling
- Fixed undefined array key warnings for storage_mode
- Change Detection tab now loads successfully

## [5.5.8] - 2025-08-29

### Fixed
- Fixed Change Detection tab failing to load
- Added proper class loading for Delta provider
- Improved error handling for missing provider classes
- All sitemap tabs now load successfully

## [5.5.7] - 2025-08-29

### Changed
- Consolidated stable release for testing
- Includes all fixes from 5.5.1 through 5.5.6

## [5.5.6] - 2025-08-29

### Fixed
- **CRITICAL**: Fixed tabs showing raw HTML instead of rendered content
- Fixed JavaScript to properly parse JSON response from WordPress AJAX
- Changed response handling from `text()` to `json()` in tab loader
- All sitemap tabs now display properly formatted UI

## [5.5.5] - 2025-08-29

### Fixed
- Fixed missing CSS styles for health summary chips section
- Added proper styling for health validation, conflicts, and IndexNow status chips
- Improved health chip hover effects and responsive design
- Complete fix for all health summary visual issues

## [5.5.4] - 2025-08-29

### Changed
- Consolidated release with all recent fixes
- Includes CSS fixes for statistics and health sections
- Includes critical PHP fatal error fixes for tab navigation
- Stable release for production use

## [5.5.3] - 2025-08-29

### Fixed
- **CRITICAL**: Fixed PHP fatal errors when switching between sitemap tabs
- Fixed "Using $this when not in object context" errors in tab partials
- Updated AJAX handlers to properly pass settings to tab templates
- Replaced all `$this->settings` with `$settings` in tab files
- Improved tab loading stability and error handling

## [5.5.2] - 2025-08-29

### Fixed
- Added missing CSS styles for sitemap statistics section
- Added missing CSS styles for health summary section
- Fixed statistics grid layout and responsive design
- Added proper styling for status badges and health indicators
- Improved visual hierarchy in overview tab

## [5.5.1] - 2025-08-29

### Fixed
- Fixed CSS/JS asset loading for sitemap admin interface
- Corrected menu registration to use proper parent slug
- Updated asset references to use consolidated files
- Fixed hook suffix check for proper asset enqueueing
- Improved sitemap admin page initialization

## [5.5.0] - 2025-08-29

### Major Cleanup Release

#### Removed
- 33 duplicate and debug files (406KB reduction)
- 8 duplicate schema implementations
- 6 versions of evergreen loaders (reduced to 2)
- Duplicate optimization system versions
- 5 versions of sitemap overview tabs (reduced to 2)
- All debug and test files from production

#### Fixed
- All duplicate function definition issues
- Function loading conflicts
- File loading order issues

#### Added
- Safety guards to prevent future function conflicts
- Proper file consolidation structure

#### Changed
- Streamlined codebase for better performance
- Cleaner loading with no redundant code

## [5.4.6] - 2025-08-28

### Security
- Fixed SQL injection vulnerability in sitemap admin page
- Fixed unprepared SQL queries using proper `$wpdb->prepare()` statements
- Removed problematic phase2-updates.php documentation file
- Improved database query security for image/video counting
- Enhanced protection against SQL injection attacks

## [5.4.5] - 2025-08-28

### Fixed
- Consolidated duplicate JS/CSS files
- Removed redundant asset files
- Improved loading performance

## [5.4.4] - 2025-08-28

### Fixed
- Fixed duplicate function definition errors
- Resolved function conflicts

## [5.4.3] - 2025-08-27

### Fixed
- Standardized all plugin constants properly
- Defined ALMASEO_MAIN_FILE, ALMASEO_PATH, ALMASEO_URL as primary constants
- Replaced all ALMASEO_PLUGIN_DIR with ALMASEO_PATH throughout
- Replaced all ALMASEO_PLUGIN_URL with ALMASEO_URL throughout
- Added proper guards: `is_admin()` check and `$_GET['page']` validation
- Fixed fatal error from undefined constants
- Proper cache busting with `filemtime()`

## [5.4.2] - 2025-08-27

### Fixed
- **HOTFIX**: Fixed fatal error - undefined constant ALMASEO_PLUGIN_PATH

## [5.4.1] - 2025-08-27

### Fixed
- Initial bug fixes and stabilization

## [5.4.0] - 2025-08-26

### Added
- Complete sitemap system overhaul
- New tabbed interface for sitemap management
- IndexNow integration
- Delta sitemap support
- News sitemap functionality
- International hreflang support
- Media sitemaps (image and video)
- Health monitoring system

## [5.0.0] - 2025-08-25

### Added
- Initial release of AlmaSEO SEO Playground
- Core SEO functionality
- Basic sitemap generation
- Schema markup support
- Meta tag management
- Social media integration

---

## Version Numbering

- **Major (X.0.0)**: Breaking changes or major feature additions
- **Minor (5.X.0)**: New features, substantial improvements
- **Patch (5.5.X)**: Bug fixes, security updates, minor improvements

## Support

For issues or questions, please visit:
- GitHub Issues: https://github.com/almaseo/seo-playground/issues
- Documentation: https://github.com/almaseo/seo-playground/wiki
- Website: https://almaseo.com