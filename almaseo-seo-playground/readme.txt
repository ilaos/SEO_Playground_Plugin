=== AlmaSEO SEO Playground ===
Contributors: almaseo
Tags: seo, schema, sitemap, meta, ai
Requires at least: 5.6
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 1.18.0
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

= 1.18.0 =
**Author identity enhancements (E-E-A-T)**

* New: Added an **Author Photo URL** field to the WordPress user profile (Users → Edit → "AlmaSEO Author Details"). The author Person schema now uses this photo when set, and only falls back to the Gravatar/avatar when it is blank — so a real author photo no longer depends on Gravatar.
* New: The **Person** schema type panel now includes step-by-step guidance explaining where each value comes from (name from the page title, URL from the permalink) and which fields carry the strongest E-E-A-T signals.
* New: Added a **"Populate from user"** dropdown to the Person schema panel. Pick any WordPress user and one click fills the given/family name, job title, email, photo, and verified profile links (Same As) from their profile — reusing the same author details that power the linked-author schema. All fields stay editable afterward.

= 1.17.1 =
**Fix: Schema & Meta tab now fully readable in dark admin color schemes**

* Fix: In the Modern and Midnight WordPress admin color schemes, several sections of the Schema & Meta tab (the Person/Organization/Product/Event/Recipe/LocalBusiness detail panels, the "Also describe this page as" picker, the info and warning notices, and the Schema Markup Output card) kept light backgrounds while their text was rendered light — making them hard to read. These sections now darken to match the Meta Robots card, with readable light text throughout. The Facebook/X social share previews are intentionally kept light so they still resemble those platforms.

= 1.17.0 =
**Linked author (Person) schema for E-E-A-T — parity with Yoast/Rank Math**

* New: Article/BlogPosting/NewsArticle schema now emits a rich, linked author `Person` entity instead of a name-only object. The author node carries a stable `@id`, the author-archive URL, the WordPress bio (`description`), the avatar (`image`), `jobTitle`, `sameAs` profile links, and `worksFor` referencing your site's brand Organization. In advanced (multi-node) schema the article's `author` becomes an `@id` reference to a single shared top-level Person node — the connected-graph shape Google prefers — while standalone output keeps a valid inline author. Works on the free tier.
* New: Two optional fields on every WordPress user profile (Users → Profile) — **Job Title** and **Profile / Social URLs** — feed the author schema's `jobTitle` and `sameAs`. Everything else (name, bio, avatar, archive URL) is pulled from the existing WordPress profile automatically, so no data is entered twice.
* Dark mode: Removed OS-driven dark-mode styling (`prefers-color-scheme`) from the admin panels. WordPress admin does not follow the operating system's dark setting, so on dark-mode machines this had been forcing light text onto light backgrounds — unreadable in the Schema & Meta tab and others. The admin now renders in its native color scheme; an explicit dark theme tied to the WordPress admin color scheme is unaffected.

= 1.16.2 =
* Fix: When the AlmaSEO dashboard pushes a content-freshness analysis for a post (via the `/evergreen-freshness/push` REST endpoint), the post now also has its basic Evergreen/Watch/Stale grade calculated immediately. Previously the dashboard analysis only set the AI-freshness findings meta and the post still read as "Unanalyzed" in the Evergreen page's status cards, because that count keys on a different meta key. This meant a fresh dashboard analysis of e.g. 71 posts could still leave the plugin showing 51 of them as Unanalyzed. The grade calculation is fast (post-age math + GSC click meta reads, no LLM calls), so it adds only a few milliseconds per pushed item.

= 1.16.1 =
**Fix: SEO title and meta description fields no longer truncate or wipe on save**

* Fix: The metabox SEO Title and Meta Description fields were hard-truncated server-side (60 and 160 characters, byte-based). Titles longer than the limit were cut mid-word, and titles containing multibyte characters (em-dashes, curly quotes, accented letters, emoji) could land the cut inside a character — producing invalid UTF-8, which caused the database write to silently fail and the field to appear to revert to its previous value on refresh. Both fields now save whatever the user types; the live character counter in the metabox is the advisory signal (yellow past 50/140, red past 58/155) and remains untouched.

= 1.16.0 =
**Evergreen tab — full audit, twelve fixes, plain-language help**

* New: Friendly intro panel at the top of the Evergreen page explaining what the three status colors mean (🟢 Evergreen, 🟡 Watch, 🔴 Stale), when you'd actually need to do something here, how status is decided, the difference between editing a post and clicking "Mark as Refreshed", and whether the feature needs the AlmaSEO dashboard or works on its own.
* Fix: Consolidated the two divergent evergreen scoring functions into one. Previously, a recalculation triggered by cron could return a different status than the same post analyzed via the editor "Analyze Now" button, because each path applied a different rule set.
* Fix: The Advanced Insights panel's "Alma Freshness Score" was actually storing a staleness score (higher = needs refresh more), but the UI labeled it as freshness — which was the opposite of what users intuitively read. Renamed to "Refresh Priority" throughout with explicit "higher = more in need of refresh" copy.
* Fix: Removed the random demo data that used to populate the Health Trend chart on brand-new installs. The chart now shows an honest empty state ("No history yet — click Analyze All Posts above") instead of fake numbers that made it look like content health data already existed.
* Fix: Evergreen asset versions (CSS/JS) now bump with each plugin release. Previously they were locked to hardcoded values like '2.5.0' and '4.2.0', so browser cache never invalidated when the plugin updated.
* Fix: Bulk REST operations (`/evergreen/bulk`) now cap each call at 500 posts. The previous unlimited fetch could OOM or hit gateway timeouts on large sites.
* Fix: Bulk REST operations now filter by per-post edit capability, so editors on multi-author sites can no longer wipe evergreen data on posts they don't own.
* Fix: The `mark_refreshed` and `bulk_reset` REST endpoints now use the canonical `ALMASEO_EG_META_STATUS` constant from `constants.php`. Previously, an inline fallback in `rest-api.php` could define the constant to a different value than the rest of the module, so these endpoints could write to (or fail to clear) a stale meta key under unusual load orderings.
* Fix: The Health Trend chart's i18n strings (Analyzing…, Analyze Now, Refreshed!, etc.) now load correctly. The previous duplicate asset enqueue had a later `wp_localize_script` call overwrite the i18n strings table with a smaller config, so JS read `undefined` for those labels.
* Fix: Basic stats and the Pro Advanced Insights now query the same canonical set of post types via a new `almaseo_eg_get_supported_post_types()` helper. Previously, basic stats counted only post + page while Advanced counted post + page + product, so totals diverged on WooCommerce sites.
* Fix: Tightened the dashboard render capability from `read` to `manage_options` to match the submenu's registered capability.
* Fix: Removed a dead `add_action('almaseo_eg_weekly', ...)` hook in dashboard.php — no code was scheduling that event, so the callback never fired. The real weekly snapshot is taken by the cron after `run_weekly_recalculation`.
* Cleanup: Deleted `evergreen-panel-consolidated.js` (24KB, never enqueued) and the orphan `wp_ajax_almaseo_save_panel_state` AJAX handler it depended on (the handler had a nonce-name mismatch and would have failed every request even if the JS had loaded).

**Search Appearance — newbie-friendly intro**

* New: Top-of-page intro panel matching the Evergreen pattern. Answers "What does this page do?" with the polarity of templates and per-post overrides, "Do I need to change anything?" (reassurance: defaults work for most sites), and three expandable sections explaining when to change something, how this interacts with per-post SEO fields, and why Google may still show old titles after edits. Smart-tags modal also notes that tags work inside per-post SEO fields, and the Search Engine Visibility help text explicitly says per-post robots settings override these defaults.

**Schema & Meta**

* New: `EntertainmentBusiness` added to the Local Business type picker, in the Entertainment category. The closest schema.org LocalBusiness subtype for bands, DJs, performers, and other event-entertainment services.

= 1.15.10 =
* Fix: Search Appearance &mdash; the "Hide this post type from search engines (noindex)" checkbox now actually applies a `noindex` robots tag to every published post of that type. Previously the setting only affected the post-type archive page; individual posts were still indexed.
* Fix: Search Appearance &mdash; per-post-type Meta Description templates (e.g. `%%excerpt%% %%sep%% %%sitename%%`) are now used on single posts/pages when no manual description has been entered. Previously the templates only worked on archive pages; single posts fell straight back to the first 30 words of post content. Open Graph and Twitter descriptions inherit the same chain.
* Fix: Search Appearance &mdash; the `%%pt_single%%` and `%%pt_plural%%` smart tags now resolve on post-type archive titles and descriptions (previously rendered as empty strings).
* Fix: Search Appearance &mdash; the Schema Preview (Dry-Run) tool on the Settings page no longer fails with "error running preview". The handler was calling a class name that the plugin no longer loads; it now uses the active scrubber.
* Improvement: Search Appearance UI &mdash; replaced the misleading "Show in Search Results" row header (which sat above a noindex checkbox) with "Search Engine Visibility" plus plain-language copy. Added a Meta Description input to Date Archives and full Meta Description + Search Engine Visibility inputs to the 404 page (404 defaults to noindex on fresh installs).
* Improvement: Search Appearance UI &mdash; every tab and card now has a short plain-English explanation of what it controls and when you would change it, including a "What is a smart tag?" intro inside the smart-tags reference modal.

= 1.15.9 =
* Improvement: Plugin icon now appears on the WordPress "Plugins" and "Updates" screens (replaces the generic placeholder). Added the `icons` field to the update metadata served from `api.almaseo.com/updates/` and shipped the icon assets with the plugin.

= 1.15.8 =
* Fix: LocalBusiness opening-hours schema now uses valid `dayOfWeek` values. The structured-data validator on schema.org was flagging entries like `"dayOfWeek": "Tu"` as invalid — those two-letter abbreviations are only valid in the legacy `openingHours` text format, not inside `OpeningHoursSpecification`. The plugin now emits the full day names (`"Monday"`, `"Tuesday"`, ...), which both schema.org's validator and Google's Rich Results test accept.

= 1.15.7 =
* Fix: SEO Playground meta box now loads its full CSS and JavaScript on custom post types that have been enabled under Settings → SEO Panel Visibility (e.g. Avada Portfolio, Avada FAQs, Events). The 1.15.5 release made the meta box appear on those screens, but the asset enqueue was still hardcoded to posts and pages only, so the panel rendered unstyled / partially broken on CPT edit screens. Posts and pages were unaffected.

= 1.15.6 =
* Improvement: Settings page now renders each section as a collapsible accordion that starts closed, so the full set of options fits in one scroll-less view. Click a section heading (or press Enter/Space while it is focused) to expand. Use the "Expand all" / "Collapse all" links at the top of the page to act on every section at once.
* Improvement: The Save Changes button now sits in a styled, sticky footer that pins to the bottom of the viewport while the form is on screen. It scrolls away naturally once you reach the Schema Preview / Schema Action Log tools below.
* Improvement: Unsaved-changes safeguards. Editing any field shows an "Unsaved changes" indicator next to the Save Changes button and a red dot next to the affected section header (so collapsed sections still surface that something is pending). Navigating away with unsaved edits triggers the browser's native "Are you sure?" dialog; clicking Save Changes saves cleanly without the warning.

= 1.15.5 =
* New: "SEO Panel Visibility" section in Settings lets you choose which post types show the SEO Playground meta box. Previously hardcoded to posts and pages only, so theme/plugin custom post types — including Avada Portfolio, Avada FAQs, Events, and others — got no SEO panel at all. By default the panel now appears on every public custom post type (WooCommerce products are excluded because they have their own dedicated SEO panel).
* Improvement: Schema & Meta tab now shows a small reminder under the Schema Type dropdown pointing users to set a Featured Image or fill the OG Image URL. This avoids Google Rich Results Test flagging "Missing field 'image' (optional)" as a non-critical warning on LocalBusiness, Article, Product, Event, and other image-bearing schema types.

= 1.15.4 =
* Fix: Roles & Permissions now works. The per-role "can edit SEO fields" toggles previously had no effect — every role that could edit posts could also edit SEO fields. The Role Manager is now authoritative: roles that are not enabled do not see the SEO metabox and cannot save SEO meta. Administrators and Editors are enabled by default; Authors and Contributors are not (admins can change this on the Settings page).
* Fix: Crawl Optimization — the oEmbed, REST API, and shortlink head-link toggles are now authoritative. Those tags were previously removed unconditionally regardless of the toggle state; the toggles now genuinely control their own output.
* Fix: Image SEO — removed the non-functional "Strip File Extension" option (the extension was always stripped from generated alt/title text, which is the correct behavior).
* Improvement: Image SEO no longer adds a title attribute to images by default (the title attribute on images is discouraged for accessibility). Existing configurations are unchanged; users can still set a title format.
* Improvement: Google Analytics — the "Exclude Logged-in Users" option is now labelled "Exclude Administrators" to match what it actually does.
* Improvement: RSS Feed settings now show a description for each available smart tag.

= 1.15.3 =
* Fix: The "GSC Analysis Window (days)" setting in Evergreen Advanced now actually applies. The traffic-trend calculation previously always used a fixed 90-day window regardless of the configured value; it now fetches Search Console data for the window you set.
* Fix: The Evergreen Advanced "Medium Risk Threshold" can no longer be saved higher than the "High Risk Threshold." That combination silently made the "medium" risk tier unreachable; Medium is now clamped to at most High on save.

= 1.15.2 =
* Fix: Exclusive Schema Mode no longer strips AlmaSEO's own structured data. Every AlmaSEO JSON-LD emitter (advanced schema graph, breadcrumbs, FAQ, How-To, WooCommerce, meta tags) now carries an identifying marker so the scrubber never removes it.
* Fix: The "Keep BreadcrumbList" and "Keep Product" whitelist options now actually work — the live scrubber previously ignored them. Whitelisted standalone schema blocks are preserved while mixed third-party graphs are still removed.
* Fix: Schema action logging and the Schema Action Log panel now populate when Exclusive Schema Mode runs.
* Fix: The AMP Compatibility setting is now honored by the active scrubber.
* Fix: Per-post schema type is read reliably for posts saved by older versions; the advanced emitter falls back to the merged schema-type key.
* Fix: The Knowledge Graph node now falls back to the WordPress site name when the Name field is left blank, instead of silently emitting nothing.
* Fix: Advanced and basic schema emitters now consistently honor the legacy per-post "disable schema" key.
* Improvement: Safer defaults for Schema Control — breadcrumb and product schema are whitelisted by default so enabling Exclusive Schema Mode never costs rich results.
* Improvement: Clearer Schema Control wording explaining what is automatic versus optional.
* Improvement: Tag Manager fields show a hint that unchecking a location disables it without losing the saved code, plus a notice pointing Google Analytics users to the dedicated Settings section.

= 1.15.1 =
* Improvement: The LocalBusiness "Fill from AlmaSEO" button now also fills opening hours. When the client's Google Business Profile is connected on the AlmaSEO dashboard, the button populates the per-day open/close time pickers — sourced from the live Google Business Profile weekly hours — alongside the address, phone, email, Google Maps URL, and service areas. Saturday/Sunday are left blank when the business is closed those days. Hours still require review before saving like the rest of the fields. (Geo coordinates and price range remain manual.)

= 1.15.0 =
* New: LocalBusiness schema — "Fill from AlmaSEO" button. When the site is connected to the AlmaSEO dashboard, the LocalBusiness panel (Schema & Meta tab) shows a one-click button that populates the business address, city, state, ZIP, phone, email, Google Business Profile / Maps URL, and service areas from the client's dashboard business profile — so you no longer re-type details that already live on the dashboard. The values are filled into the fields for review; nothing is saved until you click Update. Uses the business profile the plugin already syncs from the dashboard (no new connection needed). Opening hours, geo coordinates, and price range are not yet auto-filled — that is planned for a follow-up that sources structured hours from the Google Business Profile integration.

= 1.14.7 =
* Fix: Structured data for non-Article schema types (LocalBusiness, Product, Event, Recipe, Person, Organization, etc.) was invalid. The always-on legacy schema emitter stamped the chosen `@type` onto an Article-shaped body — so, for example, a page set to LocalBusiness emitted `headline`, `author`, and `publisher` but had no `name`, `address`, `telephone`, or `priceRange`. Validators flagged this as "Unnamed item" plus missing required fields, even when those fields were filled in and saved. The emitter now builds the node through the shared type-aware builder, so the JSON-LD body matches the selected type and includes the per-type detail fields (address, phone, price range, opening hours, Google Business Profile URL, and so on). It also defers to the Advanced Schema system when that is enabled, so the two never both emit and collide. Unknown types fall back to a valid basic Article.

= 1.14.6 =
* New: Schema & Meta tab — LocalBusiness schema now has a "Google Business Profile / Maps URL" field. The URL is emitted in the LocalBusiness JSON-LD `sameAs` array, which helps Google connect the page's business entity to your Google Business Profile listing. Available for every LocalBusiness subtype (restaurant, clinic, real-estate office, etc.).
* Improvement: The schema validator links (Google Rich Results Test, Schema.org validator) are now a prominent callout card instead of two easy-to-miss grey buttons, with a clear reminder that the validators fetch the live published URL — so changes must be saved/published before testing. Before publish, the notice explains the validators need a public URL.
* Fix: The schema JSON-LD preview panel no longer hardcodes the label "Article" — it now shows the actual schema type being previewed (LocalBusiness, Product, Event, etc.). When the live preview can't be generated, it shows an honest "save and use the validators" message instead of fabricating a misleading Article sample.

= 1.14.5 =
* Fix: Sitemap pages (`/sitemap.xml` and the child sitemaps) showed a blank page in the browser. Every sitemap file references an XSL stylesheet at `/sitemap.xsl` via an `xml-stylesheet` instruction, but that stylesheet was never served — the URL returned the site's HTML 404 page, so the browser's XSLT transform failed and rendered nothing. (This was masked until 1.14.4, when the sitemap stopped downloading and started rendering in-browser.) The plugin now serves `/sitemap.xsl` — a clean, human-readable styled view of the sitemap, the same approach Yoast, Rank Math, AIOSEO, and WordPress core use. Existing sites are fixed as soon as they update; no sitemap rebuild is required. The dynamic (non-static) sitemap output now emits the stylesheet reference too, so both storage modes render consistently.

= 1.14.4 =
* Fix: Sitemap URLs (e.g. `/sitemap.xml`) triggered a file download in the browser instead of displaying. When gzip is enabled and a static `.gz` file exists, the responder served it with both `Content-Type: application/gzip` and `Content-Encoding: gzip` — contradictory headers. `Content-Encoding: gzip` makes the browser transparently decompress the body, so the `Content-Type` must describe the *decompressed* payload (`application/xml`); `application/gzip` made the browser treat it as a downloadable archive. `send_headers()` now distinguishes transparently-compressed XML (`application/xml` + `Content-Encoding: gzip`) from an explicit `.xml.gz` download (`application/gzip`, no `Content-Encoding`).
* Fix: IndexNow Integration in the Sitemaps → Change tab is now fully wired up. Previously the Enable toggle, Key, and Endpoint inputs read from standalone `almaseo_indexnow_*` options that nothing ever wrote, while the runtime (`Alma_IndexNow`) read a different location (`almaseo_sitemap_settings['indexnow']`) — so changing anything in the UI had no effect. All three now save through the panel's auto-save to the single canonical location the runtime uses. The Endpoint dropdown stores real API URLs; the dead "Batch Size" field (the submitter never used it) was removed. "Generate Key" now persists the key; "Test IndexNow" submits the sitemap index as a connectivity check; "Ping All URLs" submits the queue of changed URLs. Saving a key also writes the `<key>.txt` verification file to the site root.
* Fix: "Ping All URLs" / search-engine ping was calling a non-existent `Alma_IndexNow::submit_batch()` method, which would fatal on click. It now calls the real `submit()` API.
* Fix: Saving any sitemap setting wiped the IndexNow configuration. `handle_save_settings()` rebuilt the settings array without an `indexnow` branch, and `update_option()` replaces the whole option — so the runtime's IndexNow config was destroyed on every save. It is now saved/preserved like every other section.
* Fix: "Copy All URLs" emitted a PHP 8 warning and silently omitted custom-post-type and taxonomy sitemaps. It looped over the string `'all'` as if it were a list and read a `taxonomies` settings key that is never written (the real key is `tax`). CPTs and taxonomies are each served as one combined sitemap (`sitemap-cpts-1.xml` / `sitemap-tax-1.xml`); the URL list now reflects that. The same wrong keys were fixed in the `almaseo_get_all_sitemap_urls()` helper, along with its incorrect `media.images` / `media.videos` keys.
* Fix: The Overview tab's "Recalculate" button threw a JavaScript error — it read a `stats` object the handler never returns — which left the button stuck disabled. It now reloads to show the freshly recomputed statistics.
* Fix: Removed a duplicate click handler on the "Copy All URLs" button that double-fired the request and wrote "undefined" into the URL list textarea.
* Fix: News-sitemap cron refresh referenced an undefined variable when the news provider wasn't registered, producing a PHP notice.

= 1.14.3 =
* Removed: Featured Snippet Targeting (the "Snippet Targets" admin page and its REST endpoints under `/almaseo/v1/snippet-targets/*`). After building the dashboard-side producer end-to-end (1.14.0–1.14.2 + dashboard work), it became clear that snippet research, drafting, and publishing all belong on the AlmaSEO dashboard alongside the AI content writer — not split between dashboard and plugin. The plugin-side panel was a poor fit for the workflow (research really wants a dashboard surface; drafting really wants the AI writer that lives on the dashboard). The Pro feature flag `snippet_targeting`, the menu item, the locked-feature card, and all module files have been removed. The `wp_almaseo_snippet_targets` database table is intentionally left in place — it's harmless and avoids destroying any draft content from sites that ran the feature before this release. See `docs/FUTURE_FEATURES.md` for the architectural decision and what's left to ship the dashboard-only version.

= 1.14.2 =
* New: The Snippet Targets push endpoint (`POST /almaseo/v1/snippet-targets/push`) now accepts a `url` field in addition to `post_id`. When `post_id` is omitted, the plugin resolves the URL to a local post ID server-side via WordPress's `url_to_postid()` (with a slug-based fallback for permalink-rewrite edge cases). This is the first step in the dashboard-side Snippet Targets producer pipeline — the dashboard now only needs to send the URL of the ranking page, not its WordPress post ID. The response payload now also includes a `skipped` breakdown (`missing_query`, `unresolvable_url`, `not_published`) so the producer can surface why specific entries didn't land. Backwards-compatible: callers sending `post_id` continue to work as before.
* Internal: Snippet Targets receiver hardened for the upcoming dashboard producer. No user-visible change to the admin panel.

= 1.14.1 =
* Change: Tag Manager has moved out of the main SEO Playground settings page and into its own submenu — SEO Playground → Tag Manager. The fields, behavior, hooks, and stored option key (`almaseo_tag_manager`) are unchanged; any code you already saved continues to work. Reason: the section was tall enough that bundling it with the rest of the settings page made the page a long scroll, and "Tag Manager" is the kind of thing users expect as a top-level item.

= 1.14.0 =
* New: Tag Manager — adds a new section to the AlmaSEO settings page with three textareas for injecting custom HTML/JS into the page `<head>`, immediately after `<body>`, and just before `</body>`. Useful for Google Tag Manager, analytics scripts, pixels, verification meta tags, chat widgets, and any other third-party snippet you'd normally paste into a "header/footer scripts" plugin. Each area has its own enable toggle; output is wrapped in `<!-- AlmaSEO Tag Manager: ... -->` comments for traceability in view-source; an optional "Skip for logged-in administrators" toggle keeps your analytics clean while testing. Code is output verbatim (no escaping) — by design, so `<script>`/`<style>`/`<noscript>` tags work. Save is gated on `manage_options`. Hooks: `wp_head` (priority 100), `wp_body_open` (priority 1), `wp_footer` (priority 100). Skipped automatically on admin, REST, AJAX, and JSON requests.
* Fix: Plugin description on the WordPress Plugins page no longer mentions "5 polished tabs" — outdated copy from earlier versions when the optimization panel had a fixed tab count.

= 1.13.23 =
* Fix: The Bulk Metadata Editor's CSS file was passing the wrong constant to `wp_enqueue_style()` as the version param — `ALMASEO_VERSION`, which is hardcoded to '6.5.0' in `almaseo-seo-playground.php:66` and never gets bumped on releases. The CSS URL had been frozen at `?ver=6.5.0` forever, so browsers cached `bulk-meta.css` aggressively and refused to re-fetch it across plugin updates. The bulk-meta JS was correctly using `ALMASEO_PLUGIN_VERSION` (the live one), which is why every JS change shipped fine. Symptom: the row-height clamp added in 1.13.20 and re-shipped in 1.13.22 never visibly took effect; rows of just-autofilled posts continued to stretch to 5+ lines because the browser was running pre-1.13.20 CSS. Switched the CSS enqueue to `ALMASEO_PLUGIN_VERSION` so cache-busts work normally going forward.

= 1.13.22 =
* Fix: After running auto-fill on a batch of posts (especially in Alma mode, which produces full 60-char titles and 160-char descriptions), the just-filled rows in the Bulk Metadata Editor were noticeably taller than rows on later pagination pages that still had no metadata. The cause wasn't a layout bug — it was real content: a 160-char description wraps to 4-5 lines in a fixed-width table column, and `table-layout: fixed` stretches every cell in the row to match. Re-introduced the `.meta-field` height clamp from 1.13.20 (which was removed in 1.13.21 alongside the source-badge revert): SEO Title cells now ellipsize at one line, Meta Description cells at three lines. Full value is available on hover (browser tooltip via `title` attribute) and via the inline edit affordance (click Edit). Inline edit and value-modifying behavior are unchanged — the clamp is purely the rest-state display.

= 1.13.21 =
* Revert: Removed the per-row source badge (Alma / Local / Manual) introduced in 1.13.19. After two attempts to make it visually unobtrusive (1.13.19 used inline-flex + a dashicons icon; 1.13.20 simplified to a plain inline-block chip and added a `.meta-field` height clamp on the SEO Title / Meta Description cells), portfolio-page rows were still expanding vertically after bulk operations. The mode of who generated a row's meta is already surfaced two other ways (the "Alma-Powered Auto-Fill is Active" / "Auto-Fill Mode: Basic (Local)" header banner shows the current generator; the auto-fill success toast tags the result with `(Alma)` when the cloud responded), so the per-row badge wasn't earning its keep. Took out the `sourceBadge()` JS helper, the `.meta-field` height clamp CSS, the post-meta source tracking (`_almaseo_meta_source` writes from autofill / manual edit / bulk ops / reset), and the `meta_source` field on the REST list and PATCH/reset payloads. The legacy `_almaseo_meta_source` post meta on already-tagged rows is harmless — it just stops being read or written.

= 1.13.20 =
* Fix: After running a bulk metadata operation that touched many posts, rows on the visible pagination page suddenly looked taller with empty whitespace. Two contributing causes, both fixed: (1) the 1.13.19 source badge used `inline-flex` + a dashicons icon, which when mixed with regular inline `<strong>` post-title text can extend the surrounding line box height — replaced with a plain `inline-block` colored chip with explicit `line-height: 1.4` and no icon. (2) The SEO Title and Meta Description value cells had no height clamp, so a long meta value (especially after a bulk append/prepend) wrapped to 3-5 lines and stretched the row, leaving shorter cells in the same row looking empty. The SEO title cell now ellipsizes to one line, the meta description cell clamps to three lines, both with the full value available on hover (`title` attribute) and via inline edit. Inline edit and value-modifying behavior are unchanged — this is purely the rest-state display.

= 1.13.19 =
* New: Each row in the Bulk Metadata Editor now shows a small source badge next to the post title — "Alma" (purple) when the row's title/description was generated by AlmaSEO's AI, "Local" (grey) when it came from the offline auto-fill engine, "Manual" (amber) when the user typed it themselves (inline edit or any bulk append/prepend/replace). Lets you audit at a glance which posts on the site are running AI-generated metadata vs. local or hand-curated copy.
* Internal: Centralized the source tracking on a new `_almaseo_meta_source` post meta key (values: `ai` / `local` / `manual`) with a `BulkMeta_Controller::set_meta_source()` / `clear_meta_source()` pair. Wired from every write path — AI autofill apply, local autofill apply (only when something was actually written), per-row PATCH, bulk append/prepend/replace, and reset (which clears the flag). Surfaced in both the list endpoint payload and the PATCH/reset response so refresh-in-place stays current without a full reload.

= 1.13.18 =
* Fix: Bulk-actions wrapper had `style="display:none"` baked into the page template and no JS path ever toggled it visible — meaning the entire "Bulk Actions" dropdown + apply button was invisible on a fresh page load. Dropped the inline hide; the bar is now always visible under the filters with a "0 selected" counter until the user picks rows.
* Fix: "Select All" only ticked the checkboxes for the 20 rows on the current pagination page, but the bulk action POST sent only those IDs and reloaded back to page 1 — so a user filtering down to (say) 47 portfolio pages, clicking the header checkbox, and submitting Find & Replace would silently leave pages 2 and 3 untouched. The success toast even said "20 of 20 succeeded" because it had no idea pages 2+ existed.
  - Pre-submit confirm now spells out the gap when the user's checkbox selection is narrower than the filter total: "You've selected 20 of 47 posts matching your filters. Only the 20 on this page will be updated. Pages 2+ will NOT be touched. Continue with just these 20?"
  - Post-submit toast now reads "20 succeeded. 27 more items match your filters but weren't on this page — use the 'Select all matching' banner to do the rest."
* New: Server-side `POST /wp-json/almaseo/v1/bulkmeta/bulk-all` endpoint — operate on every post matching a filter spec in one round-trip instead of paging through IDs. Args: `op` (reset/append/prepend/replace), `field`, `args`, `filters` (same shape the list endpoint accepts: type, status, taxonomy, term, from, to, search, missing). Server runs the same WP_Query, applies the op via the shared `apply_op_to_post()` helper, and returns aggregate counts. Hard-capped at 5,000 matches — anything larger returns 400 with a "filter more narrowly" message rather than blocking PHP for minutes. The cap matches the existing "Auto-Fill Entire Site" ceiling so the two destructive paths behave consistently.
* New: "Select all N matching" banner — when the user ticks the header checkbox and the filter total is larger than the page (e.g. 47 matches with 20 on page 1), a banner appears between the filters and the table: "All 20 on this page are selected. [Select all 47 matching items across pages]". Clicking it flips the bulk action into matching-mode, which routes to `/bulkmeta/bulk-all` and processes every match server-side in one call. Manually unchecking any row, paginating, or changing filters cancels matching-mode and clears the banner.
* Refactor: Extracted `BulkMeta_Controller::build_query_args($filter_spec)` so the list endpoint (`GET /bulkmeta`) and `/bulkmeta/bulk-all` translate filters identically — the "missing metadata" meta_query block and tax/date filter mapping had previously lived inline in two REST handlers and were starting to drift apart. Same change for the per-post op logic: extracted `apply_op_to_post()` and reused it from both bulk handlers, which incidentally fixed a copy of the reset-only-deletes-bulk-meta-keys bug that lived in `bulk_operation`'s reset case (the canonical metabox keys `_almaseo_title` / `_almaseo_description` were left intact, so the live `<title>` / `<meta description>` still served the old values after a bulk reset).

= 1.13.17 =
* Fix: Bulk Metadata Editor table — the column headers and the cells the JS rendered were in different orders and counts (7 headers vs 8 cells, plus a third dead PHP `#post-row-template` with yet another layout). Every row's cells fell under the wrong header — "Status" sat over the SEO Title cell, the Updated column had no header at all. Aligned the `<thead>` in `admin/pages/bulk-meta.php` with the renderer (8 columns: checkbox, Post Title, Type, Status, SEO Title, Meta Description, Updated, Actions) and removed the dead template block.
* Fix: Bulk Metadata Editor pagination was always stuck on page 1. The JS read `rows.pages` on the response array (always undefined), so `totalPages` always resolved to 1 and the page links never rendered. Switched the list calls to `wp.apiFetch({ parse: false })` so the JS can read `X-WP-Total` / `X-WP-TotalPages` headers the REST endpoint already returns.
* Fix: Editing a post's SEO title or meta description in the Bulk Metadata Editor was bumping `post_modified` on the post itself via `wp_update_post()`. Meta-only edits shouldn't claim the post body was touched — the side effect was sitemap `<lastmod>` rotating, feeds re-ordering, and any `orderby=modified` widgets/queries surfacing posts whose content hadn't actually changed.
* Fix: "Reset" in the bulk editor only deleted `_almaseo_meta_title` / `_almaseo_meta_description` (the table's display keys) but left `_almaseo_title` / `_almaseo_description` (the canonical keys read by the metabox + frontend meta-tag renderer) intact. After a reset the table looked empty but the live `<title>` / `<meta description>` still served the old values. Both key families are now deleted on reset, and the bulk append/prepend/replace ops write to both families on save so the renderer and the table never diverge.
* Fix: Bulk "Find & Replace" silently skipped any post whose SEO title or description was empty (it only operated on the bulk-meta key, not the post fallback). Now mirrors append/prepend: empty title falls back to `post_title`, empty description falls back to excerpt / first 30 words of content. Also added a no-op guard so an empty Find string returns a validation error instead of a spurious "success" count.
* Fix: AI Auto-Fill's connection check (`AI_Autofill_Generator::is_available()`) duplicated the app-password lookup instead of going through `seo_playground_is_alma_connected()`. Same class of drift as the 1.13.0 JWT-outbound-auth fix — JWT-host sites where the JWT lives in `almaseo_app_password` would have failed if any future change touched the helper differently from the canonical check. Routed through the shared helper.
* Fix: "Auto-Fill Entire Site" silently truncated at 5,000 posts because the paginator was hard-capped at `page > 50` × `per_page=100`. Sites above that ceiling had no idea their later content wasn't being processed. The paginator now reads `X-WP-TotalPages` to know when it's done, and the entire-site action explicitly refuses to run if the post count is above 5,000 (with a clear message pointing users to "Auto-Fill All Empty" or filtered selections) instead of silently truncating.
* Fix: Saving a row in the bulk editor no longer triggers a full table reload. The PATCH endpoint already returns the row's fresh payload — the JS now uses it to refresh the single row in place, so the character/pixel gauges, dupe markers, and badges stay in sync with the saved value without losing scroll position.
* Fix: Several JS template literals interpolated REST-supplied strings (`r.title`, `r.meta_title`, `r.meta_desc`, `r.edit_link`, `p.title`, `d.current`, `d.generated`, error messages) directly into `innerHTML` with no escaping. Added `escHtml` / `escAttr` / `safeUrl` helpers and routed every dynamic value through them — including the autofill preview modal and the result banner. URLs are now restricted to `http(s)://`, protocol-relative, root-relative, and query/fragment strings; anything else (e.g. `javascript:`) becomes `#`.
* Fix: REST list endpoint did `fields => 'ids'` then called `get_post()`, `get_post_meta()` ×4, `get_post_type_object()`, `get_post_modified_time()`, `get_edit_post_link()`, `get_permalink()` per post — ~12 extra queries per row that defeated WP's own query/meta caching. The endpoint now lets WP_Query return full `WP_Post` objects so the post + meta caches get primed once.
* Improvement: New mode toggle ("Auto (Alma)" / "Basic (Local)") in the auto-fill action bar. Disconnected sites are locked to Basic; connected sites default to Auto and can force Basic for testing or to compare local output against Alma without disconnecting.
* Improvement: "Updated" column now shows a relative timestamp ("3 hours ago") with the raw ISO string in the hover title, instead of the raw `c`-format string the REST endpoint returns.
* Cleanup: Removed dead code — `BulkMeta_Controller::get_posts()` and `::format_post_data()` (the inline REST handler reimplemented both years ago), `AI_Autofill_Generator::apply()` (never called; REST does the per-post fan-out itself), the `/bulkmeta/test` debug endpoint, scattered `error_log()` statements behind `WP_DEBUG`, and the dead `almaseo_bulk_meta` localize block. Single source of truth for character/pixel budgets — JS now reads from `AlmaBulkMeta.limits` instead of duplicating the values as constants. REST permission check unified with the page-render gate (`almaseo_feature_available('bulkmeta')`) so the UI and API can't diverge on access.
* Cleanup: Placeholder hint under bulk-action options now lists every token the handler actually supports (`{site}`, `{category}`, `{year}`, `{month}`, `{day}`) — `{month}` and `{day}` were already implemented but never documented in the UI.

= 1.13.16 =
* Fix: Bulk-trashing more than one post at a time (e.g. selecting 50+ rows from Posts/Pages and choosing "Move to Trash") could hang for minutes and end with "Error in moving the item to trash". The redirect-trash handler captured each post into a per-user transient, but each capture re-read and re-wrote the whole array, so the cost was quadratic in the number of posts being trashed — and on top of that the resulting banner tried to render one inline redirect-creation row per post (50+ rows on screen). Combined with every other plugin handler firing on `save_post` / `transition_post_status` during trash (sitemap delta, IndexNow, health score recalc, history snapshot, evergreen recalc, etc.), the request blew through `max_execution_time`. The handler now detects bulk trash via `$_REQUEST['post']` being an array of two or more IDs and bails before doing any work. Single-trash behavior is unchanged — you still get the "Create a Redirect" banner when trashing one post. Users who bulk-trash and still need redirects can add them in SEO Playground → Redirects.

= 1.13.15 =
* Fix: `<lastmod>` in sitemap XML output was emitting MySQL datetime format ("2026-04-09 22:29:38" — space-separated, no timezone offset) from the static writer. That's non-spec; the sitemap protocol requires W3C datetime / ISO 8601. Bing's validator rejects it and sitemap.org's official validator flags it as invalid. Google parses leniently so indexing wasn't blocked, but the output was technically wrong.
* Internal: Lifted the date-format logic out of `Alma_Sitemap_Responder::format_date()` (which was already correct, dynamic-mode only) into a new shared `almaseo_format_lastmod()` helper in `includes/sitemap/helpers.php`. Both the static writer (`Alma_Sitemap_Writer::write_url`) and the dynamic responder (`Alma_Sitemap_Responder::render_url_element`) now call the same helper, so the two emission paths can't drift again. Output format: `2026-04-09T22:29:38+00:00`.
* Note: For clarity — `<lastmod>` is not a Google ranking factor, but it is a crawl scheduling signal. Accurate lastmod helps search engines re-crawl changed pages sooner. The plugin sources lastmod from WordPress's `post_modified_gmt` column (set only when content actually changes), so the values reflect real edits — no artificial bumping that would risk having Google discount the sitemap's signals.

= 1.13.14 =
* Change: Removed `<priority>` from sitemap XML output. Google has ignored sitemap priority for years (Bing too), and the value the plugin emitted was always a heuristic (post type / page hierarchy / recency) rather than user-set, so it was just noise. Stripped from both emission paths — `Alma_Sitemap_Writer::write_url()` (static builds) and `Alma_Sitemap_Responder::render_url_element()` (dynamic mode). Existing rebuilt sitemaps will lose the field on the next rebuild.
* Cleanup: Add-URL prompt for Additional URLs no longer asks for a priority value — there's no point capturing it if the writer drops it. The `priority` column in `wp_almaseo_additional_urls` and the `calculate_priority()` helpers in the post / page / CPT providers are left in place (harmless dead code) so existing data is preserved and a future change can reinstate the field by flipping one if-block in each emitter.

= 1.13.13 =
* Fix: Updates & I/O tab — System Information → "Copy for Support" button serializes the visible info table (plugin/WP/PHP versions, memory limit, max exec time, server, active plugins) to clipboard. Was unwired.
* Fix: Updates & I/O tab — Bulk Operations buttons now do real work. "Validate All" routes to the same `validate_sitemap` handler the header uses (the validator already walks every provider sitemap), "Rebuild All" routes to `rebuild_static` (same — rebuilds index plus every child in one pass), and "Ping Search Engines" submits the sitemap index URL via IndexNow to Bing/Yandex/Naver depending on user config. New `handle_ping_search_engines` AJAX action distinct from the existing `force_delta_ping` so the toast text and semantics are clearer (whole sitemap vs. rolling-window delta).
* Fix: Updates & I/O tab — Quick Tools → robots.txt Preview was a no-op. New `handle_preview_robots` AJAX action wraps `Alma_Robots_Integration::get_robots_preview()`; the JS renders the preview into the existing `<pre>`, extracts the `Sitemap:` lines for copy, and offers a Download as `robots.txt` file.
* Fix: Auto-update preference checkboxes (`#auto-updates-enabled`, `#auto-updates-beta`) were rendering their saved state from `get_option()` but no code persisted changes — toggling appeared to do nothing on reload. Added `handle_save_auto_update_settings` and a `change` listener that POSTs both flags on every toggle.
* Fix: Types & Rules → Additional URLs → "Clear All" button had no backend method or click handler. Added `Alma_Additional_URLs_Storage::clear_all()` (truncates the `wp_almaseo_additional_urls` table, returns the row count it deleted) plus a `handle_clear_all_urls` AJAX action. The JS confirms the destructive action and reloads on success.
* Fix: Import Settings drop zone in Updates & I/O was fully decorative — no click handler on "Choose File", no drop listener on the zone, no FileReader, and the "Import Settings" confirm button didn't POST anything. Wired the full flow: click-to-pick or drag-and-drop a `.json` file → FileReader reads it → on confirm, POSTs to the existing `handle_import_settings` with the merge/backup toggles honored (backup is implemented by triggering an `export_settings` download right before the import runs).
* Fix: Import CSV button in Types & Rules → Additional URLs was unwired. Synthesized a hidden `<input type="file" accept=".csv">` on demand, FileReader to read text, POST to the existing `handle_import_csv`, and reload on success.

= 1.13.12 =
* Fix: Overview tab's "Validate" button was faked with `setTimeout` and a lying success toast — it never called any backend. Added a real `almaseo_validate_sitemap` AJAX handler that wraps `Alma_Sitemap_Validator::run()` (full index / urlset / media / news / conflict suite), rolls up the aggregate count, and returns either "Sitemap is valid" or "N issues found". Same handler powers the Health & Scan tab's `#validate-sitemap` and `#validate-all` buttons via event delegation.
* Fix: Six export buttons across Health & Scan / Updates & I/O / Types & Rules / International tabs (`#export-settings-btn`, `#export-logs-btn`, `#export-conflicts-csv`, `#export-diff-csv`, `#export-hreflang-issues`, `#export-csv-btn`) had real PHP handlers returning `{ content, filename }` JSON but no JS to convert that into a download. Added a `runDownload()` helper that creates a Blob and triggers a click on a synthetic `<a>` with the right filename + MIME, then wired all six.
* Fix: Health & Scan tab — conflict scanner buttons (`#scan-conflicts-btn`, `#rescan-conflicts`, `#view-conflicts-btn`), snapshot controls (`#create-snapshot-btn`, `#compare-snapshots-btn`), and log management (`#clear-logs-btn`, `#refresh-logs-btn`) all had registered PHP handlers but no JS click bindings. Wired all of them with confirmations on destructive actions (clear logs) and a `prompt()` for snapshot name.
* Fix: Updates & I/O tab — `#copy-all-urls-btn` now fetches via `almaseo_copy_all_urls` and shows the URLs in the textarea while copying to clipboard. `#copy-shortcode-btn` works. The HTML-sitemap shortcode builder updates the generated string live as the user toggles types or column count, instead of the previous static value.
* Fix: Types & Rules → Additional URLs → `#add-url-btn` now actually adds a URL via `almaseo_add_url`. Minimal prompt-based UI (URL + priority + changefreq) — a proper modal would be nicer but adding one was out of scope. Import-CSV and Clear-All buttons remain unwired; both need real file-picker / confirmation modals that don't exist yet.

= 1.13.11 =
* Fix: Six action buttons across Media / News / International tabs had real PHP AJAX handlers (`scan_media`, `validate_media`, `rebuild_media`, `validate_news`, `rebuild_news`, `validate_hreflang`) but no JS click handlers wired up — every click was a silent no-op. Added a single `runAction()` helper in `sitemaps-consolidated.js` that handles the common spinner / disable / toast pattern, then bound all six buttons (delegated, so tab-load timing doesn't matter). Open- and Copy-URL buttons inside each tab's "Sitemap URL" row are wired the same way.
* Fix: Overview tab's inline jQuery handlers (Enable / Ping IndexNow / Clear Cache / Copy All URLs) each generated their own `wp_create_nonce` at PHP render time instead of reading the localized `almaseoSitemaps.nonce`. If WP rotates the nonce mid-session the inline copies go stale while the localized one refreshes; now they read from the live JS object, with `window.__ALMA_BOOT.nonce` as a fallback.
* Fix: Overview tab read three option keys that nothing in the codebase ever writes (`almaseo_last_validate_time`, `almaseo_conflict_count`, `almaseo_last_indexnow_time`), so the health chips on the Overview always showed defaults. Repointed at `almaseo_get_health_summary()`, which reads from the actual nested path (`almaseo_sitemap_settings.health.*`) where validators, conflict scanners, and IndexNow submitters do write their state.
* Fix: Overview tab's "Mode" stat was reading `$settings['generation_mode']` (a top-level key nothing writes) and always showed the fallback "Static" regardless of the user's actual choice. Now reads `$settings['perf']['storage_mode']` — same key fix as the screen header in 1.13.10.

= 1.13.10 =
* Fix: Sitemaps header chips ("Files / URLs / Built X ago") were stuck at zero on every page load. `Alma_Sitemaps_Screen_V2::get_sitemap_stats()` returned hardcoded zeros and tried to read a `almaseo_sitemap_last_built` option that nothing in the codebase ever writes. Repointed at the same in-option path the writer's `finalize_build()` populates (`almaseo_sitemap_settings.health.last_build_stats`) via the shared `almaseo_get_build_stats()` helper. The "Static / Dynamic" chip was also reading the wrong key (`generation_mode` at top level instead of `perf.storage_mode`) and is now correct.
* Fix: Even when the live-stats AJAX endpoint did return correct numbers, the JS poller in `sitemaps-tabs-consolidated.js` couldn't apply them — the selector targeted `.stat-number` inside the chips, but the rendered markup uses `.num`. The post-rebuild update path had the same flavor of bug, looking for `[data-stat="files"]` when the chips are written as `[data-live-stat="files"]`. Both paths now target the actual DOM and refresh after every successful rebuild.
* Fix: Four entire Sitemaps tabs saved nothing. The Media (image + video), News (publisher / language / post types / categories / genres / keywords / window / max items), International (hreflang enabled / source / default / x-default / locale map), and Change Detection (delta enabled / max URLs / retention) tabs all rendered form controls with no change listeners attached, and `saveSettings()` never assembled a payload for them. Added section-readers per tab and delegated change listeners on `document` so the wiring survives the lazy-loader replacing panel HTML when the user switches tabs.
* Fix: News tab's "Keywords Source" radios (Use Post Tags / Manual Keywords) didn't reveal or hide the manual-keywords input when toggled — the partial set initial visibility via inline CSS but had no JS reacting to changes. Flipping the radio now shows/hides the input group and triggers an auto-save.
* Fix: Types & Rules → Advanced Exclusion Rules had a worse-than-claimed coverage hole. The 1.13.8 changelog stated full save coverage, but only the saveSettings() payload was updated; the change listeners on `#exclude-taxonomies`, `#exclude-authors`, and `#exclude-older-than` were never wired, so the dropdowns never fired the auto-save. Auto-save now fires on every exclude-filter change.
* Fix: `handle_save_settings()` would silently disable sitemaps whenever the user saved from any tab other than Types & Rules. The handler built `enabled`, `include`, and `links_per_sitemap` unconditionally from POST, so a missing `enabled` (because the JS wasn't sending it from a Media-only payload) was read as "user just disabled sitemaps." Each of those keys now preserves `$existing[...]` when absent from POST, matching the pattern already used for the perf / delta / hreflang / media / news / exclude sections.
* Fix: `includes/sitemap/defaults.php` defined `almaseo_get_default_settings()` but the file was never required from anywhere — the function did not exist at runtime. Loaded it alongside `helpers.php` in the main plugin bootstrap, behind a `function_exists()` guard for safe Connector coexistence. Both the screen-render and the lazy-load AJAX handler now `array_replace_recursive($defaults, $stored)` before passing `$settings` into a tab partial, which closes the same class of warning that the 1.13.8 repackage patched for `settings.include.tax` — but for every nested key the partials touch.
* Fix: Default media settings used `'fetch_oembed' => true` while the save handler and Media partial both read `'oembed_cache'`. Two different keys for the same intent — renamed the default to `oembed_cache` so a fresh install or a `Reset to Defaults` action keeps the video oEmbed cache enabled.

= 1.13.9 =
* Change: Public sitemap URL is now `/sitemap.xml` (was `/almaseo-sitemap.xml`). Child sitemaps follow the same pattern — `/sitemap-posts-1.xml`, `/sitemap-pages-1.xml`, `/sitemap-delta.xml`, etc. Rewrite rules auto-flush on first load after upgrade via a new `almaseo_sitemaps_rewrite_version` option. Old `/almaseo-sitemap*.xml` URLs no longer resolve.
* Fix: Sitemap index file was always empty (`<sitemapindex></sitemapindex>`) even though child sitemaps contained URLs. `Alma_Sitemap_Writer::get_manifest()` was reading from `current/manifest.json` — the *previous* build's manifest, and on Windows permanently empty because the finalize-step `symlink()` fails silently. Switched to returning the in-memory manifest built up during the current rebuild, so the index now lists every child sitemap that produced URLs.
* Fix: `current/` directory never populated on Windows hosts. `Alma_Sitemap_Writer::finalize_build()` used `symlink()` to promote the latest `build_<timestamp>/` snapshot, which requires admin / Developer Mode on Windows and fails silently from PHP. Replaced with a portable recursive directory copy — works on every host, no privileges required, and is also safer for shared hosting where filesystem symlink support is unreliable.
* Internal: On-disk static sitemap filenames renamed to match the URL scheme (`sitemap-{provider}-{page}.xml` and `sitemap.xml`). Old `almaseo-sitemap-*` files in `wp-content/uploads/almaseo/sitemaps/build_*/` are no longer referenced; they will be cleaned up by the existing 3-build retention sweep in `cleanup_old_builds()`.

= 1.13.8 =
* Fix: PHP warning at plugin activation — `class-alma-provider-tax.php` now uses defensive array access for missing `settings['include']['tax']` key.
* Fix: Sitemaps → Types & Rules tab now actually saves every control on the screen. The auto-save payload was missing `perf` (Static / Dynamic mode + Gzip) and `exclude` (Advanced Exclusion Rules — taxonomies, authors, older-than-years), so toggling those did nothing. Payload now includes them; the storage_mode and gzip change handlers were also calling a dead `.almaseo-save-all.show()` instead of triggering the debounced save — switched to the proper `markChanged()` path.
* Fix: Save success and error toasts were targeting `#almaseo-toast` which doesn't exist in the rendered DOM (the actual container is `#almaseo-toast-container`, used by the tabs bundle). The Types & Rules auto-save was already firing successfully but the user had no visible confirmation, which is why "no save button" felt like nothing was working. Toast helper rewritten to match the existing container, and a small "Saving… / Saved" status pill is now shown next to the header actions (Open / Copy / Rebuild) on every change so the auto-save flow is observable without adding a misleading explicit Save button.
* Fix: Advanced Exclusion Rules (taxonomies, authors, older-than-years) — the JS save and PHP `handle_save_settings` accept paths were both missing for these. The runtime side has been correctly implemented in `Alma_Provider_Posts` / `Alma_Provider_Pages` / `Alma_Provider_CPTs` for some time (`build_exclude_joins` and `build_exclude_where` apply the filters at the SQL level), so toggling the dropdowns now actually changes which URLs the sitemaps include.

= 1.13.7 =
* Fix: Sitemaps panel — foundation. The main `sitemaps-consolidated.js` bundle was throwing `TypeError: Cannot read properties of undefined` at module-load on every page view because PHP localized its data as `almaseo_sitemaps` (snake_case) while JS read `window.almaseoSitemaps` (camelCase) ~70 times. Renamed the localized variable to camelCase to match what JS expects (and to match the rest of the plugin's localize calls — `almaseoAdmin`, `almaseoWoo`, `almaseoInternalLinks`, `almaseoImport`, `almaseoHistory`, `almaseoDH`, `almaseoGSC`, `almaseoWizard`). Also reshaped the localized payload so JS field accesses (`.ajaxUrl`, `.sitemapUrl`, `.settings`, `.i18n.*`) actually find their data.
* Fix: 11 AJAX action names called from `sitemaps-consolidated.js` did not match any registered server-side handler — every Save / Add URL / Import CSV / Export CSV / Conflict Scan / Export Conflicts / Export Diff / Recalculate / Build Static button was returning WP's silent `0` response and looking like the click did nothing. Renamed JS calls to match the registered PHP handler names: `almaseo_save_sitemap_settings` → `almaseo_save_settings`, `almaseo_recalculate_sitemap` → `almaseo_recalculate`, `almaseo_build_static_sitemaps` → `almaseo_rebuild_static`, `almaseo_add_additional_url` → `almaseo_add_url`, `almaseo_import_urls_csv` → `almaseo_import_csv`, `almaseo_export_urls_csv` → `almaseo_export_csv`, `almaseo_start_conflict_scan` → `almaseo_start_scan`, `almaseo_get_conflict_status` → `almaseo_get_scan_status`, `almaseo_get_conflict_results` → `almaseo_get_scan_results`, `almaseo_export_conflicts_csv` → `almaseo_export_conflicts`, `almaseo_export_diff_csv` → `almaseo_export_diff`.
* Fix: The live-stats AJAX endpoint was reading from option keys (`almaseo_sitemap_stats`, `almaseo_sitemap_last_built`) that nothing in the codebase ever wrote, so it always returned `files: 0, urls: 0, last_built: Never` regardless of build state. Repointed at the same nested option path the writer's `finalize_build()` actually writes to (`almaseo_sitemap_settings.health.last_build_stats`). The `lazy_load` overview-stats handler and `create_snapshot` handler had the same bug and were also fixed.
* Fix: Tightened `catch (Exception $e)` to `catch (\Throwable $e)` in 6 sitemap tab partials. PHP 7+ `Error`/`TypeError`/`ParseError` extend `Throwable`, not `Exception`, so the existing catch blocks would let any "method does not exist" or "argument type mismatch" propagate up and fatal the entire page render — the same class of bug that took out the rebuild handler before 1.13.5.

= 1.13.6 =
* Fix: `assets/js/sitemaps-consolidated.js` had a bundle-seam syntax error (`}); // End jQuery wrapper/**` collapsed onto a single line, so the `//` line-comment swallowed the `/**` block-comment opener of the next file). Browsers parsed the next line's leading ` * ` as multiplication on `undefined`, the entire bundle aborted with `SyntaxError: Unexpected token '*'`, and every handler defined in that file was lost — including the silent failure mode the user was seeing on the Rebuild button.
* Fix: Sitemaps page Rebuild button now shows immediate UI feedback. The "Last Built" cell flips to "Just now" and the Files / URLs counts update from the AJAX response payload, instead of requiring a page reload to re-run the PHP renderer.
* Fix: When rebuild fails, the toast now displays the actual error message (`build_locked`, `rebuild_failed: ...`) rather than a generic "Failed to rebuild sitemap". The handler unpacks both string and object error envelopes from `wp_send_json_error`.

= 1.13.5 =
* Fix: The Sitemaps page's "Rebuild" button now actually rebuilds the sitemaps and the "Last Built" stat updates accordingly. The previous handler called a method that didn't exist (`$writer->write_provider_sitemap()`), invoked `write_index()` without its required `$sitemaps` argument, used `new Alma_Sitemap_Manager()` against a private constructor, and caught only `Exception` — so the resulting `Error` escaped, the AJAX response 500'd, and the build lock orphaned for 5 minutes. The handler now delegates to a new `Alma_Sitemap_Manager::rebuild_now()` method which runs the same proven pipeline used by the cron rebuild (`start_build` → `generate_with_seek` per provider → `write_index($sitemaps)` → `finalize_build()`), catches `Throwable` so any provider bug releases the lock instead of stranding it, and returns a proper stats payload. `finalize_build()` writes to `almaseo_sitemap_settings.health.last_build_stats` — which is the exact key `helpers.php` reads — so the Last Built timestamp now updates on every successful rebuild.

= 1.13.4 =
* Fix: Sitemaps and Settings admin pages now load with their stylesheets and scripts. The asset enqueue handler was comparing the WordPress hook suffix against the menu *slug* (`seo-playground_page_*`), but the actual hook prefix is `sanitize_title()` of the parent menu's *title* — currently `almaseo-seo-playground_page_*`. The mismatch silently dropped every CSS/JS enqueue on those two screens, leaving them rendered as unstyled HTML. Switched both checks to a substring match against the page slug suffix so a parent-title rename can't strip the assets again.

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
