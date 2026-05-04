=== AlmaSEO SEO Playground ===
Contributors: almaseo
Tags: seo, schema, sitemap, meta, ai
Requires at least: 5.6
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.9.0
License: GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Professional SEO optimization plugin with AI-powered content generation, comprehensive keyword analysis, schema markup, and real-time SEO insights.

== Description ==

AlmaSEO SEO Playground is a complete SEO toolkit for WordPress that combines free competitive-parity features with optional AI-powered enhancements.

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

**AI-Enhanced (when connected to AlmaSEO dashboard):**

* AI Keyword Suggestions
* AI Headline Rewrites with CTR predictions
* AI Readability Benchmarks
* AI Image Alt Text generation
* AI Cornerstone Content detection

== Installation ==

1. Upload the `almaseo-seo-playground` folder to `/wp-content/plugins/`
2. Activate the plugin through the Plugins menu
3. Follow the Setup Wizard to configure your site
4. Optionally connect to the AlmaSEO dashboard for AI features

== Frequently Asked Questions ==

= Does this plugin work with WooCommerce? =

Yes. The Pro tier includes full WooCommerce SEO support including product schema, category optimization, and product sitemap integration.

= Can I import from Yoast, Rank Math, or AIOSEO? =

Yes. The Import & Migrate wizard (SEO Playground > Import & Migrate) supports all three in a 5-step process: post meta, taxonomy terms, global settings, redirects, and a verification report.

= Do I need the AlmaSEO dashboard connection? =

No. All local features work without any connection. The dashboard connection adds optional AI-powered enhancements on top of the free local analysis.

= Can I use this alongside other SEO plugins? =

Yes. The plugin includes conflict detection for 8 major SEO plugins and shows a dismissible warning with a link to the Import tool so you can migrate your data.

== Changelog ==

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
