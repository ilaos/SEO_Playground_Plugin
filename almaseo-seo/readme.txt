=== AlmaSEO SEO Playground ===
Contributors: almaseo
Tags: seo, schema, sitemap, meta, ai
Requires at least: 5.6
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.21.12
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

= 1.21.12 =
**Code quality: WordPress Plugin Check — clean pass**

* Cleared all remaining Plugin Check warnings across the plugin with scoped, documented suppressions on the plugin's own custom-table queries and admin view files, aligned the tested-up-to headers, and trimmed the changelog to the supported length. No functional changes.

= 1.21.11 =
**Code quality: WordPress Plugin Check pass (continued)**

* Added direct-file-access protection (`ABSPATH` guard) to the three WP-CLI command files, and updated the "Tested up to" header to the current WordPress version. No functional changes.

= 1.21.10 =
**Code quality: WordPress Plugin Check pass**

* Resolved WordPress Plugin Check errors and warnings across the plugin: switched file deletion to `wp_delete_file()`, enqueued the Google Analytics gtag.js library properly instead of printing a raw script tag, added justified suppressions for the plugin's own custom-table queries, and removed two orphaned development/debug files. No functional changes.

= 1.21.9 =
**Fix: dashboard connection now generates its credential correctly**

* Connecting your site from the AlmaSEO dashboard now reliably creates the Application Password it needs. Internally, the credential generation was calling a function that does not exist in WordPress, so it silently did nothing; it now uses the correct WordPress core API. (Manual connection from the Connection settings page was already working and is unaffected.)

= 1.21.8 =
**Sign-up-first connect prompts**

* The optional "connect" prompts shown to new users now lead to a free sign-up first — you need an account before you can connect — with a clear "already have an account? connect it" path for returning users. Applies to the welcome screen and the Overview quick actions.

= 1.21.7 =
**A calmer, clearer experience for free users**

* Works great with no account. Connection status is now honest — you're shown as "Free plan" rather than flagged with a warning — and features that genuinely need an AlmaSEO connection (Search Console, Analytics, advanced schema, GSC Monitor) show a calm "here's what you'd get" message with a simple free sign-up link, instead of empty tables or errors.
* Friendlier first run: the welcome screen leads with your free toolkit and the setup wizard; connecting an account is offered as an optional extra.
* Quieter admin: removed the full-width red "hidden from Google" bar (the warning itself is kept, just calmer and unbranded), removed the sidebar PRO/FREE badges, and softened the plugin-conflict and connection notices.
* Activation no longer creates an unused application password — credentials are generated only when you actually connect.
* Housekeeping: removed placeholder marketing content and corrected an internal version constant.

= 1.21.6 =
**Dashboard connection lifecycle**

* Added automatic detection of whether your site is linked to an AlmaSEO dashboard, including disconnect/reconnect handling and a periodic connection check.

= 1.21.5 =
**One clean Keyword Suggestions panel under the focus keyword**

* Removed the separate typeahead dropdown that floated over the focus keyword field. There is now a single "Keyword Suggestions" panel directly beneath the field.
* The panel is never empty: connected sites see real Google Search Console metrics (impressions, average position) for the keywords you already rank for; everyone else gets free Google-style suggestion chips. Click any chip to set it as your focus keyword.

= 1.21.4 =
**Maintenance release**

* Internal: the plugin source is now back under version control and matches what your sites run. No functional changes from 1.21.3 — your keyword suggestions, SEO health, and schema tools all behave exactly the same.

= 1.21.3 =
**Keyword suggestions now show your real Search Console performance — and are clickable**

* Each suggestion now displays the keyword's real data from your own Google Search Console: impressions, average position, and an opportunity score. (Search "volume" is intentionally not shown — it's a paid third-party metric the plugin doesn't use; your own Search Console data is more accurate.)
* Click any suggestion to set it as your focus keyword — the list then re-seeds around that keyword, so you can explore the terms you already rank for.
* Reworded the connect prompt to describe what you actually get: keywords you already rank for, with impressions and average position.

For older releases, see the full changelog at https://almaseo.com.


== Upgrade Notice ==

= 1.6.14 =
Security hardening release. All output now properly escaped per WordPress coding standards.
