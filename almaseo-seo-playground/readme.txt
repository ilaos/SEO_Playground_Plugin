=== AlmaSEO SEO Playground ===
Contributors: almaseo
Tags: seo, schema, sitemap, meta, ai
Requires at least: 5.6
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.13.3
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional SEO optimization plugin with Alma-powered content generation, comprehensive keyword analysis, schema markup, and real-time SEO insights.

== Description ==

AlmaSEO SEO Playground is a complete SEO toolkit for WordPress that combines free competitive-parity features with optional Alma-powered enhancements.

**Free Features:**

* SEO Health Score with 10 weighted signals
* Meta title, description, and focus keyword editor
* SERP preview (Google-style)
* XML, News, Video, Image, and HTML sitemaps
* Schema markup (JSON-LD) with 193 Local Business types
* Search Appearance templates with smart tags
* Breadcrumbs (shortcode + Gutenberg block)
* FAQ, How-To, and Table of Contents blocks
* Headline Analyzer and Readability checks
* Cornerstone Content management
* Google Analytics integration
* Import from Yoast, Rank Math, and AIOSEO (5-step wizard)
* Crawl optimization and robots.txt editor
* 404 error tracking with spike alerts
* Redirect manager with trash-to-redirect
* Role-based access control
* RSS feed controls
* Webmaster verification codes
* LLMs.txt management
* IndexNow support
* Link attributes (nofollow/sponsored/ugc)
* Image SEO (auto alt/title templates)
* Setup wizard for first-run
* Evergreen content tracker

**Pro Features:**

* Bulk Metadata Editor
* WooCommerce SEO
* Internal Links Auto-Linker
* Content Refresh Drafts (side-by-side diff)
* Refresh Queue Autoprioritization
* Date Hygiene Scanner
* Advanced Schema Options
* Advanced Evergreen Features
* LLM Optimization
* E-E-A-T Enforcement
* GSC Monitor
* Orphan Page Detection
* Schema Drift Monitor
* Featured Snippet Targeting
* DataForSEO Integration

**Alma-Enhanced (when connected to AlmaSEO dashboard):**

* Alma Keyword Suggestions
* Alma Headline Rewrites with CTR predictions
* Alma Readability Benchmarks
* Alma Image Alt Text generation
* Alma Cornerstone Content detection

== Installation ==

1. Upload the `almaseo-seo-playground` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Follow the Setup Wizard to configure your site
4. Optionally connect to the AlmaSEO dashboard for Alma-enhanced features

== Frequently Asked Questions ==

= Does this plugin work with WooCommerce? =

Yes. The Pro tier includes full WooCommerce SEO support including product schema, category optimization, and product sitemap integration.

= Can I import from Yoast, Rank Math, or AIOSEO? =

Yes. The Import & Migrate wizard (SEO Playground > Import & Migrate) supports all three in a 5-step process: post meta, taxonomy terms, global settings, redirects, and a verification report.

= Do I need the AlmaSEO dashboard connection? =

No. All local features work without any connection. The dashboard connection adds optional Alma-powered enhancements on top of the free local analysis.

= Can I use this alongside other SEO plugins? =

Yes. The plugin includes conflict detection for 8 major SEO plugins and shows a dismissible warning with a link to the Import tool so you can migrate your data.

== Changelog ==

= 1.13.3 =
* Fix: Sitemaps admin page no longer fatals with "Call to undefined function almaseo_get_index_urls()". The sitemap helpers file used a single top-of-file bail that checked only the singular `almaseo_get_index_url` — if that singular got declared first by anything else (Connector plugin coexistence, opcache, duplicate include path), the file returned before declaring the plural variant the Sitemaps screen actually calls. Replaced with per-function `function_exists()` guards on all eight helpers in the file so each one protects itself independently.

= 1.13.2 =
* Branding: User-facing copy across the plugin no longer leans on "AI" terminology. Plugin-owned features (autofill, rewrites, alt text, freshness scoring, refresh suggestions) are branded "Alma" or "Alma-powered". External LLM systems (ChatGPT, Gemini, Google AI Overviews) are referred to as "LLMs" instead of "AI" where the original phrasing was generic. Touched ~50 strings across the metabox panel, Welcome page, Documentation page, Bulk Meta page, Connection Settings, settings, Refresh Queue, Refresh Drafts, llms.txt editor, evergreen dashboard, image SEO settings, health UI, tier management modals, and the readme.
* Prep: Registered new `meta_autogen` Pro feature flag for the per-post Generate Title / Generate Description button. Wiring sits behind the same tier-default cutover used by other gated features — currently passes for everyone, locks automatically when the license-helper default flips from 'pro' to 'free'. Adds a `local_locked` resolution badge ("Generated locally — Upgrade to AlmaSEO Pro for profile-aware generation") that fires only after that cutover.
* Fix: The "Test Connection to AlmaSEO API" button on the Connection Settings page now actually round-trips. Previously the AJAX call did not include the security nonce that the backend handler required, so every click returned "Security check failed" — a latent bug that pre-dated the JWT auth work but never surfaced because the dashboard's `/api/v1/ping` endpoint did not exist either. The endpoint is now live (server-side, no plugin update needed for that part) and the JS sends the nonce, so the button reports the real connection state.

= 1.13.1 =
* Fix: The "Test Connection to AlmaSEO API" button on the Connection Settings page no longer returns "Security check failed". The button's AJAX call wasn't sending a nonce, but the backend handler required one — so the button has been broken on every install since it was introduced. With this fix, clicking it will actually round-trip credentials to the AlmaSEO API and report the real status (or the real error message), which is what makes it useful as a diagnostic for sites where AI autofill or the Search Console panel report authentication problems.

= 1.13.0 =
* Fix: JWT-host sites (WP Engine and other managed hosts that disable WP Application Passwords) were inbound-connectable but couldn't outbound-authenticate to the dashboard. The plugin's outbound calls (AI autofill, site profile, GSC page data) all read `almaseo_app_password` for Basic Auth, but the JWT path never wrote to that option — so Generate Title fell back to local generation, the Search Console panel showed "AlmaSEO connection not detected", and any other dashboard-dependent feature silently no-op'd. New `almaseo_get_active_jwt()` helper persists the JWT to the same options the app-password path uses, with caching so repeated calls don't churn out fresh tokens that desync from the dashboard's stored copy.
* Fix: The Connection Settings page's stale-password detection no longer wipes JWT credentials. Previously it deleted `almaseo_app_password` whenever no matching `WP_Application_Passwords` record existed — but JWT-host sites have no app-password records by design, so the JWT was being deleted on every page load.
* Fix: Plugin's REST API JWT recognition now also accepts a JWT in the Basic Auth password slot (in addition to the `X-AlmaSEO-Token` header). This enables the dashboard's auto-heal probe — which authenticates via Basic Auth — to succeed on JWT-host sites and re-sync the stored credential automatically.

= 1.12.0 =
* Fix: Profile cache now lazy-fetches on the first Generate Title/Description click after upgrade, instead of waiting for a daily cron tick or a credential re-save. Previously, plugin upgrades didn't re-pull the profile, so 1.10.0/1.11.0 users with already-saved credentials saw a blank cache and the generator fell back to content-only output.
* Feat: New resolution badge appears next to the generated value showing exactly which path ran — "Generated by AlmaSEO AI (using your profile)", "Generated by AlmaSEO AI", "Generated locally using your profile", or "Generated locally (no profile cached)". Hover for the list of profile fields the plugin had cached.
* Fix: When the local fallback derives a title from post content, it now prefers the first complete sentence (e.g. "Sell your car without the hassle") over a word-boundary cut that left fragments like "We cover the entire" hanging mid-clause. Strips trailing articles/conjunctions on truncated bases.

= 1.11.0 =
* Repackage of 1.10.0 with a corrected release zip. The 1.10.0 archive was built with PowerShell's `Compress-Archive`, which writes non-standard backslash path separators. Linux WordPress installs rejected the entries and showed "Plugin file does not exist" on activation. No code changes between 1.10.0 and 1.11.0 — only the packaging.

= 1.10.0 =
* Feat: Auto-Generate Title/Description is now profile-aware. The plugin pulls your business profile (services, service areas, business name, about_us) from the AlmaSEO dashboard and caches it locally so generated titles use real, specific terms — e.g. "Car Buying in Vallejo | Your Car's Not Junk" instead of "Comprehensive Home in 2026". Profile refreshes daily via wp-cron and on connect.
* Feat: New patience banner appears above the SEO fields during Generate Title/Description so the user knows what's happening behind the spinner.
* Fix: Local fallback no longer decorates placeholder titles ("Home", "Front Page", "Sample Page", "Welcome", etc.) with stock power words. Power-word selection is now deterministic per post — re-running on the same post produces the same result.
* Fix: When the post title is empty or a placeholder and no profile is cached, the local generator now derives a base title from the first paragraph of `post_content` or the site tagline (`bloginfo('description')`) instead of falling back to generic decoration.
* Fix: `generate_description` for placeholder/homepage posts now prefers `about_us` from the cached profile over a bare "Explore everything about Home." string.
* Internal: New dashboard endpoint `GET /api/plugin/site-profile` returns normalized profile data with server-side address scrubbing (filters out scraped nav-menu pollution found in some `street_address` rows).

= 1.9.4 =
* UX: Activation now always redirects to the Welcome page (was: setup wizard for fresh installs / welcome page for returning users). The Welcome page is now the single canonical onboarding landing.
* UX: Welcome page restructured into 4 steps — Connect to AlmaSEO, Deactivate Other Plugins (Connector or rival SEO plugin), Run the Setup Wizard, and Import Your SEO Data. Step 2 dynamically detects which other plugins are active and tailors its copy.
* UX: Removed the "Create New Post" button from the Welcome page CTA — Connect to AlmaSEO is now the single primary action.
* Updated documentation link to https://docs.almaseo.com/ and contact-support link to https://webstuffguylabs.com/support/. Both open in a new tab.
* Subtitle changed from "AI-powered" to "Alma-powered" on the Welcome page.

= 1.9.3 =
* Fix: WP Engine and other managed hosts that disable WP Application Passwords now show the JWT token panel directly on the Connection Settings page — no more "WordPress 5.6 or higher is required" dead-end. JWT auth doesn't depend on Application Passwords and works regardless of hosting restrictions.
* Internal: Connection Settings page now proactively checks `wp_is_application_passwords_available()` on page load. If unavailable, the JWT panel renders immediately without requiring the user to click a doomed Generate button.

= 1.9.2 =
* Feature: Uninstall now wipes only connection state (App Password, JWT secret, dashboard pairing flags, AlmaSEO-named WP Application Passwords). All settings, custom tables, post meta, and term meta are preserved — reinstall starts with a clean handshake but keeps your work.
* Internal: Added dormant `/wp-json/almaseo/v1/connection/{handoff,status,finalize}` REST endpoints for a future deep-link Connect flow. No UI consumes them yet; safe to ignore.
* Internal: Added `ALMASEO_DASHBOARD_URL` constant (defaults to `https://app.almaseo.com`).
* Fix: Sync both `ALMASEO_PLUGIN_VERSION` constant declarations to the same value (1.9.1 had drift — line 53 said 1.9.1, line 65 said 1.9.0).

= 1.9.1 =
* Fix: Meta title and description truncation now cuts back to the last complete word instead of adding "..." — prevents Google from displaying chopped titles in search results

= 1.9.0 =
* Feature: Multi-schema support — a single page can now describe more than one entity (e.g. a band that's also a venue → MusicGroup + LocalBusiness). New "Also describe this page as:" checkbox row under the Schema Type dropdown; checked types open their existing field panels and emit additional nodes in the JSON-LD @graph.
* Feature: Inline LocalBusiness usage warning — appears whenever LocalBusiness is active to help users avoid misapplying it to service-area businesses (wedding bands, mobile services, etc.) that don't have a customer-visitable address.
* Feature: One-click validators — "Test in Google Rich Results" and "Validate on Schema.org" buttons in the Schema & Meta tab open the current page's URL in the respective tester. Disabled for unpublished posts.

= 1.8.0 =
* Feature: MusicGroup schema now supports `areaServed` (geographic service area) and `address` (PostalAddress) — useful for service-area acts like wedding bands and cover bands that perform across a metro region

= 1.7.3 =
* Feature: Add MusicGroup, Person, Organization, Product, Event, and Recipe schema types to the Schema & Meta tab
* Feature: Add inline save reminder above typed schema panels
* Fix: Stop self-unhook bug that was suppressing front-end JSON-LD output
* Fix: Disable duplicate "Schema Settings" sidebar panels (replaced by the unified Schema & Meta tab)
* Fix: Sync ALMASEO_PLUGIN_VERSION constant with plugin header (was reporting 1.6.21)

= 1.7.2 =
* Fix: Add 'aioseo' to allowed sources in redirect import batch endpoint — was returning 400 "Invalid parameter(s): source" when importing AIOSEO redirects

= 1.7.1 =
* Fix: Hook JWT authentication into WordPress core (determine_current_user filter) so standard WP REST API endpoints (posts, media, categories) work with JWT tokens — fixes publishing on sites behind strict firewalls (e.g. GoDaddy) that block Basic Auth
* Fix: PHP 8 compatibility — cast get_param('secret') to string before hash_equals() to prevent TypeError crash

= 1.7.0 =
* Fix: Accept any authenticated admin/editor for API metadata updates (previously required AlmaSEO-generated app password, blocking sites with manually-created credentials)

= 1.6.21 =
* Fix: Add phpcs:ignore for ~210 $wpdb table interpolation queries across 40 model/provider files
* Fix: Replace all rand() with wp_rand() across plugin (~20 instances)
* Fix: Replace unlink() with wp_delete_file() in IndexNow
* Fix: Replace is_writable() with WP_Filesystem in robots controller
* Fix: Add phpcs:ignore for fopen/fclose on php://output stream (CSV export)
* Fix: Add phpcs:ignore for error_log() behind WP_DEBUG checks
* Fix: Use absint() for $_GET['eg_analyzed'] sanitization
* Fix: Add phpcs:ignore for dynamically-built DB queries in evergreen rest-api

= 1.6.20 =
* Fix: Add wp_unslash() to all $_POST/$_GET/$_SERVER superglobal access across 44 files
* Fix: Wrap wp_verify_nonce() calls with sanitize_text_field(wp_unslash())
* Fix: Replace is_writable() with WP_Filesystem methods in htaccess editor
* Fix: Replace 6 strip_tags() calls with wp_strip_all_tags()
* Fix: Add phpcs:ignore for $wpdb table interpolation in 404-model.php
* Fix: Sanitize $_POST['content'] inline in htaccess editor

= 1.6.19 =
* Fix: Replace 56 date() calls with gmdate() for timezone safety
* Fix: Replace 25 parse_url() calls with wp_parse_url() for cross-PHP consistency
* Fix: Replace wp_redirect() with wp_safe_redirect() where applicable
* Fix: Escape CLI schema command CSV output
* Fix: Wrap paginate_links() in wp_kses_post()
* Fix: Escape $cnt, $weight, readability counts with intval()
* Fix: Escape printf __() and almaseo_eg_get_state_label() outputs properly

= 1.6.17 =
* Fix: Add phpcs:ignore for 15 wp_json_encode() calls in JSON-LD and inline script contexts
* Fix: Add phpcs:ignore for 7 HTML sitemap render methods (content escaped internally)
* Fix: Add phpcs:disable for WP_CLI output in 3 CLI files (terminal context, not HTML)
* Fix: Wrap sitemaps-screen tab fallback output in wp_kses_post()
* Fix: Escape $cnt, paginate_links, $weight, readability counts, WP_CLI exception messages
* Fix: Replace json_encode with wp_json_encode in schema CLI command

= 1.6.16 =
* Fix: Escape 60 remaining unescaped output instances across 21 files
* Fix: Wrap all ternary outputs in HTML attributes with esc_attr()
* Fix: Wrap emoji outputs with esc_html()
* Fix: Add esc_url() to remaining get_permalink/get_category_link/get_tag_link calls
* Fix: Add wp_kses_post() for render_status_pill() HTML output
* Fix: Replace json_encode() with wp_json_encode() in inline scripts
* Fix: Add phpcs:ignore for intentional XML/HTML meta output

= 1.6.15 =
* Feat: "View version details" modal now shows full description, changelog, and FAQ
* Feat: PUC now parses local readme.txt for update info — changelog auto-populates on every release
* Fix: Updated readme.txt with current version history and feature list

= 1.6.14 =
* Fix: Escape remaining unescaped output (wp_die, admin_url, echo __(), option values)
* Fix: 40 files updated for WordPress Plugin Check compliance

= 1.6.13 =
* Fix: Escape ~160 unescaped output calls across 27 files
* Fix: Add esc_url() to all URL outputs in href attributes
* Fix: Add esc_attr() to all variables in HTML attributes
* Fix: Add esc_js() to nonces in inline JavaScript
* Fix: Add wp_kses_post() for intentional HTML output

= 1.6.12 =
* Fix: Replace 993 _e() calls with esc_html_e() for proper output escaping
* Fix: Move dev markdown files out of plugin directory

= 1.6.11 =
* Fix: Add missing translators comments to 55 sprintf/printf calls
* Fix: Convert 6 strings with unordered placeholders to ordered format

= 1.6.10 =
* Fix: Text domain mismatch — replace 'almaseo' with 'almaseo-seo-playground' across 121 files
* Fix: Text Domain header corrected to match plugin slug
* Fix: Add translators comments for all _n()/__() calls with placeholders
* Fix: Missing $domain parameter in __() calls

= 1.6.9 =
* Feat: Redirect-on-trash prompt + AIOSEO redirect import support

= 1.6.8 =
* Feat: Expanded deploy.sh to handle full release pipeline

= 1.6.7 =
* Fix: Wizard completion flag now set on step 5 display, non-dismissible post-onboarding notices

= 1.6.6 =
* Feat: Replace PUC stub with real Plugin Update Checker v5.6 library

= 1.6.5 =
* Fix: Save reminder now appears at top of SEO fields

= 1.6.4 =
* Fix: Move generation status outside button + add save reminder

= 1.6.3 =
* Feat: Branded UX + headline scoring rebalance + prompt alignment

= 1.6.2 =
* Feat: Profile-aware AI autofill + rewritten prompt

= 1.6.1 =
* Feat: AI-powered metadata autofill + SEO Page Health tab cleanup

= 1.6.0 =
* Feat: Enhanced breadcrumb settings — separator picker, font controls, live preview
* Fix: Color picker not loading — hook check was too strict

== Upgrade Notice ==

= 1.6.14 =
Security hardening release. All output now properly escaped per WordPress coding standards.
