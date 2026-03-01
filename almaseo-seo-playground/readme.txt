=== AlmaSEO SEO Playground ===
Contributors: almaseo
Tags: seo, sitemap, xml sitemap, indexnow, schema, hreflang, news sitemap, bulk editing, metadata
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 6.5.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise-grade SEO toolkit with WooCommerce SEO, comprehensive XML sitemaps, IndexNow, advanced schema support, and bulk metadata editing.

== Description ==

AlmaSEO SEO Playground provides a complete SEO solution with WooCommerce optimization and advanced sitemap features:

**NEW: WooCommerce SEO (Pro)**
* **Product Schema**: Rich snippets with pricing, stock, reviews
* **Enhanced Breadcrumbs**: Schema-enabled navigation for better UX
* **Product Sitemaps**: Automatic inclusion with image support
* **Meta Control**: SEO titles, descriptions, OpenGraph for products & categories

**Advanced Sitemap System**
* **Comprehensive Sitemaps**: Posts, pages, custom post types, taxonomies, users, images, videos, news
* **Performance**: Static generation with gzip compression for lightning-fast serving
* **Delta Tracking**: Ring buffer tracks recent changes for instant search engine updates
* **IndexNow Integration**: Instant submission to Bing, Yandex, Seznam
* **Hreflang Support**: Automatic WPML/Polylang integration with reciprocity validation
* **Media Sitemaps**: Image and video extraction with CDN deduplication
* **Google News**: Rolling 48-hour window with category filtering
* **Conflict Detection**: Safe coexistence with Yoast, AIOSEO, RankMath
* **HTML Sitemap**: Shortcode and Gutenberg block for visitor-friendly sitemaps
* **Enterprise Features**: Multisite support, WP-CLI commands, health monitoring

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/almaseo-seo-playground/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to AlmaSEO → Sitemaps to configure
4. Run initial build: WP-CLI: `wp almaseo sitemaps build --mode=static`

== Changelog ==

= 6.5.0 =
* NEW: Complete WooCommerce SEO suite for Pro users
* ADDED: Product schema markup with pricing, stock, and review data
* ADDED: Enhanced breadcrumbs with BreadcrumbList schema
* ADDED: WooCommerce products in XML sitemaps with image support
* ADDED: SEO meta fields for products and categories
* ADDED: OpenGraph and Twitter Card support for products
* ADDED: NoIndex/NoFollow controls for products
* ADDED: Customizable breadcrumb separators and text
* ADDED: Priority adjustment for featured/sale products in sitemaps
* ADDED: Support for variable products with AggregateOffer schema
* ADDED: WooCommerce SEO settings page with comprehensive options
* IMPROVED: Integration with existing AlmaSEO infrastructure

= 6.4.7 =
* FIXED: Critical overlap issue where filters covered table headers
* IMPROVED: Added 50px top margin to table wrapper for proper separation
* IMPROVED: Increased bottom margin of filter nav to 40px
* ADDED: Clear div with 20px height between filters and bulk actions
* IMPROVED: Added z-index layering to prevent overlap issues
* FIXED: Filter section now properly contained with clearfix

= 6.4.6 =
* REMOVED: Pro badge from Bulk Metadata Editor header
* FIXED: Major spacing issues between filter sections
* IMPROVED: Added 20px padding to filters section with background color
* IMPROVED: Increased margins around bulk actions wrapper (30px top, 25px bottom)
* IMPROVED: Added proper padding to all form controls (8px 12px)
* IMPROVED: Search box now has search icon and better padding
* IMPROVED: Table wrapper margins increased for better separation
* IMPROVED: Description text now has bottom border and proper spacing

= 6.4.5 =
* IMPROVED: Refined CSS for gauges and badges with better spacing and alignment
* IMPROVED: Optimized tooltip positioning and styling for better visibility
* IMPROVED: Enhanced responsive grid layout for filters with multiple breakpoints
* FIXED: Badge margin and padding for cleaner visual hierarchy
* FIXED: Gauge bar width and color adjustments for better contrast
* IMPROVED: Form controls now take full width in grid cells for consistency

= 6.3.9 =
* FIXED: JavaScript now normalizes all response shapes (array, object with posts/items/data)
* FIXED: PHP safely parses type/status parameters with wp_parse_list and sanitize_key
* FIXED: Default to both posts AND pages when no type specified
* IMPROVED: Response always returns items as direct array for simpler handling
* IMPROVED: Clear all loading indicators and properly remove almaseo-loading class
* IMPROVED: More robust error handling with console logging
* FIXED: Properly handle empty responses and always clear overlay

= 6.3.8 =
* IMPROVED: Table layout now uses WordPress core styles (wp-list-table widefat fixed striped)
* ADDED: Responsive table wrapper with horizontal scrolling for mobile devices
* ADDED: Smooth iOS scrolling support with -webkit-overflow-scrolling
* FIXED: Table column widths optimized for better content display
* IMPROVED: Mobile responsive breakpoints at 1200px and 782px
* IMPROVED: Full-width table display on mobile with horizontal scroll
* UPDATED: Minimum table width ensures readable content on all devices

= 6.3.7 =
* FIXED: Bulk Metadata Editor "Loading posts..." stuck issue - REST API now returns data properly
* FIXED: REST endpoint now directly queries WP_Query with proper headers (X-WP-Total, X-WP-TotalPages)
* FIXED: Meta key compatibility - checks both _almaseo_meta_title and _almaseo_title for backwards compatibility
* ADDED: Test endpoint at /almaseo/v1/bulkmeta/test for debugging API connectivity
* ADDED: Debug logging throughout REST endpoint when WP_DEBUG is enabled
* IMPROVED: REST response always returns valid structure with posts array, total, and pages
* IMPROVED: Direct WP_Query implementation for better performance with 'fields' => 'ids'

= 6.3.6 =
* FIXED: Bulk Metadata Editor overlay trapping users on JavaScript errors
* FIXED: REST API responses now include safe string casting and character counts
* IMPROVED: Replaced all jQuery AJAX calls with wp.apiFetch for consistency
* IMPROVED: Added CSS fallback for overlay (hidden by default, shows only with body class)
* IMPROVED: Better error handling with try/catch/finally blocks that always hide overlay
* IMPROVED: Defensive JavaScript with guards against missing API response fields
* SECURITY: Enhanced REST nonce middleware setup in admin enqueue

= 6.3.5 =
* FIXED: Bulk Meta Editor stuck on "Loading posts..." due to incorrect admin hook
* FIXED: Script enqueuing now uses correct hook (seo-playground_page_almaseo-bulk-meta)
* ADDED: wp-api-fetch support with proper nonce middleware for REST API calls
* ADDED: Visible error display area in Bulk Meta Editor interface
* IMPROVED: JavaScript initialization with configuration validation
* IMPROVED: Dual API support (wp.apiFetch with jQuery AJAX fallback)
* IMPROVED: Enhanced error reporting in both console and UI
* IMPROVED: Debug logging for easier troubleshooting

= 6.3.2 =
* FIXED: Bulk Metadata Editor "Loading posts..." infinite loading issue
* FIXED: Replaced wp.apiFetch with jQuery AJAX for better WordPress compatibility
* IMPROVED: Enhanced error handling with detailed console logging for debugging
* IMPROVED: Better API response validation and user feedback
* IMPROVED: Added proper nonce verification to all AJAX requests
* IMPROVED: More reliable REST API communication for bulk operations

= 6.3.1 =
* FIXED: Bulk Metadata Editor admin page file path issue
* FIXED: Undefined property warning for post_parent in sitemap pages provider
* FIXED: Undefined array key 'include' warning in sitemap CPTs provider  
* FIXED: Fatal error with undefined method Alma_Sitemap_Writer::generate()
* ADDED: Fallback generate_with_pages method for sitemap generation
* IMPROVED: Error handling and null value checks throughout sitemap system
* SECURITY: Enhanced validation for plugin activation and settings

= 6.3.0 =
* NEW: Bulk Metadata Editor (Pro feature) - Edit SEO titles and descriptions for multiple posts at once
* ADDED: Inline editing with click-to-edit cells and autosave functionality
* ADDED: Advanced filters for post type, status, taxonomy, date range, and "missing only"
* ADDED: Character and pixel width counters with visual warnings for optimal lengths
* ADDED: Bulk operations: Reset, Append, Prepend, and Find/Replace across selected posts
* ADDED: REST API endpoints for all bulk metadata operations
* ADDED: Real-time search across post titles and metadata content
* ADDED: Smart placeholders for bulk operations ({site}, {category}, {year})
* ADDED: Pagination with customizable items per page
* ADDED: Progress indicators and toast notifications for all operations
* IMPROVED: Performance optimization with server-side pagination and indexed queries
* IMPROVED: Accessibility with proper ARIA labels and keyboard navigation
* SECURITY: Nonce verification and capability checks on all write operations

= 6.2.0 =
* NEW: 404 Tracker/Log Viewer (Pro feature) - Complete 404 error tracking and management system
* ADDED: Custom database table for storing 404 errors with hit tracking
* ADDED: Admin UI for viewing and managing 404 logs with search/filter capabilities
* ADDED: Automatic 404 capture on front-end with ignore rules for static assets
* ADDED: REST API endpoints for 404 log CRUD operations
* ADDED: One-click "Create Redirect" from 404 logs to Redirect Manager
* ADDED: Statistics dashboard showing 7-day trends and top referrers
* ADDED: Bulk actions for ignore/unignore/delete multiple 404 logs
* ADDED: Smart filtering to exclude bot noise and common scan paths
* ADDED: Real-time hit counter and last seen tracking
* ADDED: Referrer domain extraction and user agent display
* IMPROVED: Integration between 404 Tracker and Redirect Manager
* SECURITY: Nonce verification on all write operations
* SECURITY: Capability checks require manage_options permission
* SECURITY: Input sanitization and output escaping throughout

= 6.1.3 =
* FIXED: Redirects submenu not appearing in admin sidebar
* FIXED: Changed parent menu from 'almaseo-dashboard' to 'seo-playground'
* FIXED: Updated hook suffix for asset loading to match correct parent
* IMPROVED: Menu registration now uses correct parent slug for all submenus

= 6.1.2 =
* CRITICAL FIX: Wrapped all global functions in function_exists() checks to prevent fatal errors
* FIXED: Cannot redeclare function errors when multiple versions installed
* FIXED: Proper function wrapping for almaseo_handle_content_refresh_reminder
* FIXED: Proper function wrapping for almaseo_display_search_engine_warning
* FIXED: Proper function wrapping for all AJAX handlers
* IMPROVED: Plugin can now coexist with older versions during migration

= 6.1.1 =
* FIXED: Redirects submenu not appearing in admin menu
* FIXED: Pro feature check preventing menu registration
* IMPROVED: Simplified menu loading for admin users with manage_options capability

= 6.1.0 =
* NEW: Redirect Manager (Pro feature) - Complete 301/302 redirect management system
* ADDED: Custom database table for storing redirects with hit tracking
* ADDED: Admin UI for creating, editing, and managing redirects
* ADDED: REST API endpoints for redirect CRUD operations
* ADDED: Front-end runtime redirect handler with early hook priority
* ADDED: Source path normalization and validation
* ADDED: Target URL/path validation with loop prevention
* ADDED: Hit counter and last hit tracking for analytics
* ADDED: Bulk actions for enable/disable/delete redirects
* ADDED: Quick test feature to verify redirect paths
* ADDED: Statistics dashboard showing total redirects, active count, and hits
* ADDED: Enable/disable toggle for individual redirects
* ADDED: Search and pagination for redirect management
* ADDED: Export-ready data structure (CSV export in future release)
* IMPROVED: Performance with database indexing on source and is_enabled columns
* IMPROVED: Caching of enabled redirects with transient API
* SECURITY: Nonce verification on all write operations
* SECURITY: Capability checks require manage_options permission
* SECURITY: Input sanitization and output escaping throughout

= 6.0.2 =
* ADDED: Inline help text system with translatable strings
* ADDED: Helper function almaseo_render_help() for consistent help text display
* ADDED: Optional tooltips for advanced information
* ADDED: Help text to Robots.txt Editor explaining virtual vs physical modes
* ADDED: Help text to Sitemaps Overview about purpose and health checks
* ADDED: Help text to Google Search Preview with optimization tips
* ADDED: Help text to Schema & Meta panel about schema types
* ADDED: Help text to Evergreen dashboard about content freshness
* ADDED: Help text to Connection page about dashboard benefits
* IMPROVED: User experience with contextual guidance
* IMPROVED: Accessibility with ARIA labels and semantic HTML
* ADDED: Dark mode support for help text styling
* ADDED: Responsive design for help text on mobile devices

= 6.0.1 =
* FIXED: Robots.txt submenu not appearing in WordPress admin sidebar
* FIXED: Google Search Preview (SERP preview) missing styles - restored blue title, green URL styling
* FIXED: Keyword suggestions chip styles missing - added hover effects and proper spacing
* IMPROVED: CSS loading logic with fallback for missing minified files
* IMPROVED: Menu registration priority for proper submenu ordering
* ADDED: Comprehensive test checklist for verifying fixes
* UPDATED: Documentation noting Robots.txt Editor menu access path

= 6.0.0 =
* NEW: Robots.txt Editor with Virtual and Physical modes
* Added safe UI for viewing and editing robots.txt 
* Virtual mode serves content via WordPress filter (no file writes)
* Physical mode writes directly to robots.txt when permitted
* Automatic fallback to virtual mode on permission errors
* Live preview shows exactly what will be served
* Insert defaults and reset to WordPress default options
* Full multisite support with per-site options
* Security: Nonces, capability checks, input sanitization

= 5.8.8 =
* CRITICAL FIX: Complete chart rendering solution with multiple improvements
* Fixed data encoding using wp_json_encode() + esc_attr() for proper HTML attribute safety
* Added hardened JSON parsing with entity decoding fallback (&quot; and &#039; handling)
* Moved chart rendering from inline script to evergreen.js to avoid CSP blocking
* Added visible fallback messages when data is empty or all zeros
* Ensures numeric array indexing with array_values() for consistent JSON structure
* Chart now works even under strict Content-Security-Policy settings

= 5.8.7 =
* FIXED: Chart rendering blocked by WordPress security - replaced innerHTML with DOM manipulation
* Changed chart building to use createElement/appendChild instead of innerHTML
* This fixes the issue where WordPress/security plugins block innerHTML for XSS protection
* Chart now properly displays gray bars for unanalyzed posts (130 posts in your case)
* All DOM elements are created programmatically for maximum compatibility

= 5.8.6 =
* CRITICAL: Fixed JavaScript syntax error that prevented chart from rendering
* Fixed missing closing brace in DOMContentLoaded function
* Added gray bars to show unanalyzed posts (130 posts in your case)
* Chart now displays even when all posts are unanalyzed
* You'll see gray bars until you click "Analyze All Posts"

= 5.8.5 =
* FIXED: Chart now works! Issue was esc_attr() breaking JSON encoding
* Changed to htmlspecialchars() for proper JSON handling in data attributes
* Removed all debug output - clean production version
* Re-enabled real data retrieval from database
* Chart will display once posts are analyzed with "Analyze All Posts" button

= 5.8.4 =
* CRITICAL FIX: Replaced jQuery with vanilla JavaScript for chart rendering
* Added hardcoded test data to verify chart functionality
* Added visible debug output showing data structure
* Removed jQuery dependency that was blocking chart initialization
* Chart now works with static test data - proves rendering works

= 5.8.3 =
* Fixed jQuery loading issue causing chart not to render
* Added proper function dependency loading (scoring.php, scheduler.php)
* Ensured constants are defined when dashboard loads
* Added console debugging to track data flow
* Fixed JavaScript wrapper to wait for jQuery availability

= 5.8.2 =
* Fixed posts not being detected - added automatic analysis for unanalyzed posts
* Added "Analyze All Posts" button to dashboard for manual analysis
* Auto-analyzes first 20 posts on initial dashboard load
* Fixed chart to show real data from your 85+ posts instead of empty bars

= 5.8.1 =
* Fixed empty Health Trend chart - now displays demo data when no posts analyzed
* Improved chart data generation to always show visual feedback
* Enhanced weekly snapshot generation with fallback demo data

= 5.8.0 =
* Fixed Evergreen Health Trend chart not displaying (function loading order issue)
* Removed debug logging from REST API
* Improved error handling for weekly snapshots
* Enhanced data validation for dashboard statistics

= 5.6.0 =
* Major milestone release - All sitemap tabs fully functional
* Includes all fixes from versions 5.5.1 through 5.5.9
* Complete UI restoration with proper CSS and JavaScript
* All critical errors resolved
* Ready for production deployment

= 5.5.9 =
* CRITICAL FIX: Fixed Change Detection tab fatal error 
* Fixed undefined method Alma_Provider_Delta::get_recent_changes()
* Added proper method existence checks before calling
* Fixed undefined array key warnings for storage_mode
* Change Detection tab now loads successfully

= 5.5.8 =
* Fixed Change Detection tab failing to load
* Added proper class loading for Delta provider
* Improved error handling for missing provider classes
* All sitemap tabs now load successfully

= 5.5.7 =
* Consolidated stable release for testing
* Includes all fixes from 5.5.1 through 5.5.6
* Ready for comprehensive testing

= 5.5.6 =
* CRITICAL FIX: Fixed tabs showing raw HTML instead of rendered content
* Fixed JavaScript to properly parse JSON response from WordPress AJAX
* Changed response handling from text() to json() in tab loader
* All sitemap tabs now display properly formatted UI

= 5.5.5 =
* Fixed missing CSS styles for health summary chips section
* Added proper styling for health validation, conflicts, and IndexNow status chips
* Improved health chip hover effects and responsive design
* Complete fix for all health summary visual issues

= 5.5.4 =
* Consolidated release with all recent fixes
* Includes CSS fixes for statistics and health sections
* Includes critical PHP fatal error fixes for tab navigation
* Stable release for production use

= 5.5.3 =
* CRITICAL FIX: Fixed PHP fatal errors when switching between sitemap tabs
* Fixed "Using $this when not in object context" errors in tab partials
* Updated AJAX handlers to properly pass settings to tab templates
* Replaced all $this->settings with $settings in tab files
* Improved tab loading stability and error handling

= 5.5.2 =
* Added missing CSS styles for sitemap statistics section
* Added missing CSS styles for health summary section
* Fixed statistics grid layout and responsive design
* Added proper styling for status badges and health indicators
* Improved visual hierarchy in overview tab

= 5.5.1 =
* Fixed CSS/JS asset loading for sitemap admin interface
* Corrected menu registration to use proper parent slug
* Updated asset references to use consolidated files
* Fixed hook suffix check for proper asset enqueueing
* Improved sitemap admin page initialization

= 5.5.0 =
* Major cleanup: Removed 33 duplicate and debug files (406KB reduction)
* Fixed all duplicate function definition issues
* Consolidated schema implementations (removed 8 duplicate files)
* Streamlined evergreen loaders (6 versions reduced to 2)
* Unified optimization system (removed v1.1, kept v1.2)
* Cleaned sitemap overview tabs (5 versions reduced to 2)
* Security: Removed all debug and test files from production
* Performance: Cleaner loading with no redundant code
* Added safety guards to prevent future function conflicts

= 5.4.6 =
* Security: Fixed SQL injection vulnerability in sitemap admin page
* Fixed unprepared SQL queries using proper $wpdb->prepare() statements
* Removed problematic phase2-updates.php documentation file
* Improved database query security for image/video counting
* Enhanced protection against SQL injection attacks

= 5.4.5 =
* Consolidated duplicate JS/CSS files for better performance
* Reduced HTTP requests by combining multiple phase files
* Sitemaps: Merged phases 2-6 into single consolidated file
* Optimization: Using single consolidated version instead of multiple
* Evergreen: Consolidated panel versions into clear single files
* Moved old files to backup directories (js-old, css-old)
* Updated all file references to use consolidated versions
* No functionality changes - performance optimization only

= 5.4.4 =
* Fixed duplicate function definition errors in schema files
* Wrapped all duplicate functions with function_exists() checks
* Prevented fatal errors when multiple schema files are included
* Improved code stability and error handling
* No functionality changes - defensive programming improvements only

= 5.4.3 =
* COMPLETE FIX: Standardized all plugin constants properly
* Defined ALMASEO_MAIN_FILE, ALMASEO_PATH, ALMASEO_URL as primary constants
* Replaced all ALMASEO_PLUGIN_DIR with ALMASEO_PATH throughout
* Replaced all ALMASEO_PLUGIN_URL with ALMASEO_URL throughout
* Added proper guards: is_admin() check and $_GET['page'] validation
* Ensured ONLY sitemaps-tabs-v2.css loads (deregistered/dequeued v1)
* Fixed fatal error from undefined constants
* Proper cache busting with filemtime()
* Debug instrumentation remains for verification

= 5.4.2 =
* HOTFIX: Fixed fatal error - undefined constant ALMASEO_PLUGIN_PATH
* Changed to use correct constant ALMASEO_PLUGIN_DIR
* Fixed both enqueue_assets and debug_admin_notice methods

= 5.4.1 =
* COMPLETE REBUILD: Systematic fix for sitemap UI issues
* Consolidated all grid/chip styles into sitemaps-tabs-v2.css only
* Added debug instrumentation (console logs and admin notice)
* Fixed enqueue with high priority (20) and filemtime cache busting  
* Removed conflicting style registrations
* Added critical grid CSS directly to v2.css file
* Fixed jQuery timing - wrapped all JS in jQuery(function($){})  
* Server-side rendered Overview panel (no AJAX lazy loading)
* Dashicons explicitly enqueued as dependency

= 5.3.9 =
* Fixed CSS file loading - corrected enqueue to load sitemaps-tabs.css instead of sitemaps-tabs-v2.css
* Resolved layout issue where metrics and health chips were stacking vertically
* Restored proper grid layout for Sitemap Statistics (4 columns on desktop, 2 on mobile)
* Fixed horizontal layout for Health Summary chips

= 5.3.8 =
* Fixed server-side rendering for Overview tab (removed AJAX lazy loading)
* Corrected $this->settings references to use direct get_option() calls
* Fixed tab link references from 'health' to 'health-scan' for proper navigation
* Ensured Overview panel loads immediately without JavaScript dependency
* Improved performance by eliminating unnecessary AJAX calls for initial load

= 5.3.7 =
* Overview panel polish with new metric chips design
* Replaced Sitemap Statistics list with 4 visual metric cards (Files, URLs, Last Built, Mode)
* Replaced Health Summary list with linked status chips for quick navigation
* Added health summary helper function for centralized data access
* Enhanced CSS with responsive grid layout for metrics
* Added color-coded health status indicators (green/amber/gray dots)
* Improved "Enable Sitemaps" CTA visibility when disabled
* Added warning notes for missing configurations (IndexNow key, etc.)

= 5.3.6 =
* Added dedicated dashicons enqueue method for sitemaps page
* Enhanced helper function almaseo_get_index_urls() with array return type
* Improved CSS for dashicon font-family and chip display
* Proper spacing and alignment for header chips
* Added bold font weight for numeric values in stats

= 5.3.5 =
* UI polish patch for icons and URL logic
* Enhanced dashicon display with proper CSS fixes
* Added almaseo_get_index_urls() helper for consistent URL handling
* Improved takeover mode indicators ("Serving /sitemap.xml" vs "Not taking over")
* Fixed button states with aria-disabled and tooltips when sitemaps disabled
* Added num class for bold numbers in stats chips
* Direct URL shown in Overview when takeover active
* Proper icon opacity states for selected tabs

= 5.3.4 =
* Added almaseo_get_index_url() helper function for centralized URL logic
* Fixed header/Overview buttons to use helper and respect enabled state
* Added Takeover chip to header when takeover mode active
* Enhanced Enable Sitemaps flow with automatic rebuild queue
* Made Health chips proper anchor links to health tab
* Renamed tool buttons for clarity and gated IndexNow by API key
* Robots.txt preview collapsed by default with disabled state message
* Added aria-live region for accessible toast notifications

= 5.3.3 =
* Fixed Primary URL logic to properly reflect takeover state (/sitemap.xml when takeover enabled)
* Added Enable Sitemaps CTA with AJAX toggle and auto-rebuild when sitemaps disabled
* Renamed action buttons for clarity and gated IndexNow ping by API key configuration
* Expanded Health Summary with 3 interactive chips linking to relevant tabs
* Added Copy All Sitemap URLs button for bulk URL management
* Implemented arrow-key navigation for tabs with proper ARIA attributes
* Added live stats updates in header with spinner during build operations
* Robots.txt preview now collapsed by default for cleaner interface
* Enhanced Overview tab (v3) with all polish improvements

= 5.3.2 =
* Fixed broken dashicon display in sitemap tab navigation
* Added proper dashicons base class to tab icons
* Enhanced CSS for better dashicon rendering compatibility

= 5.3.1 =
* Enhanced Overview tab with compact stats card displaying Files, URLs, Last Built, and Mode
* Added collapsible robots.txt preview (collapsed by default)
* Removed duplicate action buttons from Overview tab (kept in sticky header only)
* Improved Quick Tools section with validation, Google submission, IndexNow, and cache clearing
* Added health summary with automatic issue detection
* Polished UI with proper dashicons integration throughout all tabs

= 5.0.0 =
* Phase 0: Core architecture with provider pattern and streaming XML generation
* Phase 1: Base providers for posts, pages, CPTs, taxonomies, users with admin UI
* Phase 2: Takeover mode, IndexNow integration, additional URLs management
* Phase 3: Conflict detection, diff tracking, comprehensive validation system
* Phase 4: Static generation, gzip compression, WP-CLI, seek pagination for 100k+ URLs
* Phase 5A: Delta sitemap with ring buffer for tracking recent changes
* Phase 5B: Hreflang support with WPML/Polylang detection and reciprocity
* Phase 5C: Image/video sitemaps with CDN deduplication and oEmbed integration
* Phase 5D: Google News sitemap with rolling window and category filtering
* Phase 6: HTML sitemap, robots.txt, export/import, multisite, health logs

= 4.11.0 =
* Initial beta release with core sitemap functionality

== Frequently Asked Questions ==

= How do I enable static sitemap generation? =
Static mode is enabled by default. Run `wp almaseo sitemaps build --mode=static` to generate files.

= Can I use this alongside Yoast or other SEO plugins? =
Yes! The conflict detection system ensures safe coexistence. Disable takeover mode if conflicts exist.

= How do I roll back if issues occur? =
1. Turn takeover OFF to release /sitemap.xml
2. Switch storage_mode to 'dynamic' if static serving fails
3. Disable individual features (Delta/Media/News) to isolate issues

== Screenshots ==

1. Main sitemap settings dashboard
2. Provider configuration panel
3. IndexNow integration settings
4. Health monitoring and validation
5. HTML sitemap output

== Upgrade Notice ==

= 5.0.0 =
Major release with complete XML sitemap system. Backup settings before upgrading.
