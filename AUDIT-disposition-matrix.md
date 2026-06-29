# WP.org Feature Disposition Matrix — AlmaSEO SEO Playground

Read-only audit, 2026-06-28. Product decision: **Path 1 — plugin fully free & standalone; AlmaSEO SaaS/dashboard is the paid product.** No code changed in this pass.

Companion docs: [`COMPLIANCE.md`](COMPLIANCE.md) (the rules), [`AUDIT-wporg-compliance.md`](AUDIT-wporg-compliance.md) (the gating/lock surface).

## Disposition legend
- **A — Free Plugin Core:** runs locally, no AlmaSEO needed. Action: remove gates/lock language, make fully available.
- **B — Free Plugin + AlmaSEO Enhancement:** local control works free; dashboard optionally enriches. Action: keep free control, remove gate, add *additive* text only. Wording: *"Works locally in WordPress. Connect AlmaSEO to add [X]."*
- **C — Dashboard/SaaS Feature:** only exists because of the AlmaSEO server. Action: remove the real engine from wp.org source if present; leave opt-in connection + disclosure + poster/link.
- **D — Remove/Defer:** half-built / risky / would slow submission.

Approved additive wording; **banned**: "Unlock", "Upgrade to use", "Pro only", "Premium feature", "Disabled until connected", "Limit reached".

---

## FORMERLY-GATED FEATURE ENGINES

### 1. Bulk Metadata Editor — `bulkmeta`
- **Files:** `includes/bulkmeta/*`, `admin/pages/bulk-meta.php`, `autofill-generator.php`
- **Current:** Full admin page + REST + local autofill. Menu/page gated; lock screen if tier flips; REST 403-gated. Currently works (tier=pro default).
- **Visible locked/broken UI?** Yes (lock screen path) · **Gated pro logic in source?** Yes
- **Runs fully local?** Yes — local PHP autofill engine, zero network · **Calls AlmaSEO?** No (local path) · **Other external?** No · **Credentials?** No · **Storage:** postmeta only
- **Disposition: A** · **Explanation needed:** none · **Risk: low**
- **Action:** Remove gate (`bulkmeta-controller` lock-screen branch + `bulkmeta-rest` 403). Ship the editor free.

### 2. AI Autofill (profile-aware generation) — `meta_autogen`
- **Files:** `includes/bulkmeta/ai-autofill-generator.php`, `autofill-generator.php:686`
- **Current:** Local generator always produces titles/descriptions; when connected + tier, calls `api.almaseo.com/api/plugin/ai-autofill` for profile-aware output. Gate adds a `local_locked` upsell badge.
- **Visible locked/broken UI?** No (button works locally) · **Gated pro logic in source?** Yes (the gate/badge)
- **Runs fully local?** Yes (heuristic) / **partial** for profile-aware (needs dashboard) · **Calls AlmaSEO?** Yes, optional · **Storage:** postmeta
- **Disposition: B** · **Explanation needed:** additive note · **Risk: low**
- **Action:** Remove gate + `local_locked` naming. Keep local generation free; add *"Generated locally. Connect AlmaSEO for profile-aware titles & descriptions."*

### 3. Advanced Schema output — `schema_advanced`
- **Files:** `includes/schema/schema-advanced-output.php` (early `return` at ~22), `local-business-types.php`; metabox schema dropdown (`metabox-callback.php:~2477`)
- **Current:** ~1000 LOC of JSON-LD builders (FAQ, HowTo, LocalBusiness, Service, Event, Recipe, Article…) suppressed by gate; 9 schema-type dropdown options rendered `disabled` + 🔒.
- **Visible locked/broken UI?** **Yes (disabled dropdown options)** · **Gated pro logic in source?** Yes
- **Runs fully local?** Yes — builds from postmeta, no network · **Calls AlmaSEO?** No · **Storage:** postmeta
- **Disposition: A** · **Explanation needed:** none · **Risk: medium** (visible broken control + self-contradictory: FAQ/HowTo *blocks* already ship free)
- **Action:** Remove early-return gate; remove `disabled/is-locked/🔒` from options. Ship all schema types free.

### 4. Multi-schema (extra @graph nodes) — `schema_multi`
- **Files:** `schema-advanced-output.php:~89`; metabox checkbox grid (`metabox-callback.php:~2560`)
- **Current:** Secondary schema types stored but not emitted when gated; checkbox grid wrapped in gate (hidden, not disabled).
- **Visible locked/broken UI?** No (hidden) · **Gated pro logic in source?** Yes
- **Runs fully local?** Yes · **Calls AlmaSEO?** No · **Storage:** postmeta
- **Disposition: A** · **Explanation needed:** none · **Risk: low**
- **Action:** Remove gate; always render checkboxes + emit secondary nodes.

### 5. Internal Links auto-linker — `internal_links`
- **Files:** `includes/internal-links/*`, `admin/pages/internal-links.php`
- **Current:** Full local engine (filters `the_content`, keyword→URL rules + guardrails). Admin page lock-screened, **but REST endpoints UNGATED** (work for any `manage_options`).
- **Visible locked/broken UI?** Yes (lock screen) · **Gated pro logic in source?** Yes (controller) — REST inconsistently open
- **Runs fully local?** Yes · **Calls AlmaSEO?** No (orphan-push endpoint receives optional dashboard data) · **Storage:** custom table + postmeta
- **Disposition: A** (optionally B if you later surface dashboard orphan data) · **Explanation needed:** none · **Risk: low**
- **Action:** Remove controller lock screen. Resolves the lock-screen-vs-working-REST contradiction by making it uniformly free.

### 6. Refresh Drafts — `refresh_drafts`
- **Files:** `includes/refresh-drafts/*`
- **Current:** Local diff/merge engine (splits at h2–h4, section accept/reject, WP revisions). Admin lock-screened; **REST UNGATED** (`edit_posts` only).
- **Visible locked/broken UI?** Yes · **Gated pro logic in source?** Yes · **Runs fully local?** Yes · **Calls AlmaSEO?** No · **Storage:** postmeta
- **Disposition: A** · **Explanation needed:** none · **Risk: low**
- **Action:** Remove lock screen; ship free. (The "proposed content" can come from a user paste OR, when connected, the dashboard — additive.)

### 7. Refresh Queue — `refresh_queue`
- **Files:** `includes/refresh-queue/*`
- **Current:** Local scoring engine (business value / traffic decline / conversion / opportunity). Accepts optional dashboard signal overrides via push. Admin lock-screened; REST correctly 403-gated.
- **Visible locked/broken UI?** Yes · **Gated pro logic in source?** Yes · **Runs fully local?** Yes (dashboard pushes are overrides only) · **Calls AlmaSEO?** Receive-only · **Storage:** custom table/postmeta
- **Disposition: B** · **Explanation needed:** additive note · **Risk: low**
- **Action:** Remove gate/lock screen. Local scoring free; *"Prioritized locally. Connect AlmaSEO for traffic-trend & conversion signals."*

### 8. Date Hygiene Scanner — `date_hygiene`
- **Files:** `includes/date-hygiene/*` (536-LOC regex engine)
- **Current:** Local scan (stale years, dated phrases, prices, regulations). Lock-screened; REST 403-gated. Optional dashboard NLP push.
- **Visible locked/broken UI?** Yes · **Gated pro logic in source?** Yes · **Runs fully local?** Yes · **Calls AlmaSEO?** Receive-only (push) · **Storage:** custom table
- **Disposition: A** (B if you keep the NLP-push note) · **Explanation needed:** none/additive · **Risk: low**
- **Action:** Remove gate/lock screen; ship scanner free.

### 9. E-E-A-T Checker — `eeat_enforcement`
- **Files:** `includes/eeat/*` (local checklist engine)
- **Current:** Local scan (missing author, generic username, missing bio, author schema, sources, review date). Lock-screened; REST 403-gated. Optional dashboard intelligence push.
- **Visible locked/broken UI?** Yes · **Gated pro logic in source?** Yes · **Runs fully local?** Yes · **Calls AlmaSEO?** Receive-only · **Storage:** custom table
- **Disposition: B** (local checklist free; dashboard "intelligence" additive) · **Explanation needed:** additive note · **Risk: low**
- **Action:** Remove gate/lock screen. *"Checklist runs locally. Connect AlmaSEO for prioritized E-E-A-T recommendations."*

### 10. Schema Drift Monitor — `schema_drift`
- **Files:** `includes/schema-drift/*`
- **Current:** Local scanner — `wp_remote_get()` on the site's OWN pages (loopback) to extract JSON-LD, compares to stored baseline. Lock-screened; REST 403-gated.
- **Visible locked/broken UI?** Yes · **Gated pro logic in source?** Yes · **Runs fully local?** Yes (self-fetch only) · **Calls AlmaSEO?** No · **Storage:** custom table
- **Disposition: A** · **Explanation needed:** none · **Risk: low**
- **Action:** Remove gate/lock screen; ship free.

### 11. GSC Monitor — `gsc_monitor`  ⚠ the one genuine SaaS feature
- **Files:** `includes/gsc-monitor/*`. Engine header: *"No local scanning — all data comes from the AlmaSEO dashboard."*
- **Current:** Pure receiver — stores/displays findings pushed from dashboard. Lock-screened; REST 403-gated. No local capability whatsoever.
- **Visible locked/broken UI?** Yes (lock screen) · **Gated pro logic in source?** No real engine (it's a storage/display shell) · **Runs fully local?** **No** · **Calls AlmaSEO?** Receive-only · **Storage:** custom table
- **Disposition: C** · **Explanation needed:** poster/link OR opt-in connect panel · **Risk: medium**
- **Action:** Two compliant options: **(a)** Defer from v1 — remove the page/module from the wp.org build; or **(b)** Keep a *connect-to-enable* panel: no fake controls, just *"Connect AlmaSEO to monitor indexation & rich-result drift from Search Console."* and it populates only when connected. **Decision needed.**

### 12. Evergreen — base `evergreen` (free) + `evergreen_advanced`
- **Files:** `includes/evergreen/scoring.php`, `rest-api.php`, `dashboard.php`, `gsc-integration.php`, `freshness-rest.php`
- **Current:** Local freshness heuristics (post age, seasonal phrases). 4 advanced scoring fns return stubs (0/'low'/false) when gated; advanced-summary REST 403; advanced list filters 403. Optional GSC traffic (user OAuth direct) + dashboard freshness overlay.
- **Visible locked/broken UI?** No (panels hidden) · **Gated pro logic in source?** Yes (stub returns + 403 endpoints) · **Runs fully local?** Yes (heuristics) / partial (AI freshness needs dashboard) · **Calls AlmaSEO?** Optional (freshness push) · **Other external?** Google GSC (user OAuth, optional) · **Storage:** postmeta + table
- **Disposition: B** · **Explanation needed:** additive note · **Risk: low**
- **Action:** Remove `evergreen_advanced` gates; let local scoring + filters run free. *"Freshness scored locally. Connect AlmaSEO for AI staleness analysis."*

### 13. LLM Optimization — `llm_optimization`
- **Files:** `includes/llm/llm-rest.php`, metabox LLM tab
- **Current:** 100% local PHP heuristics (summary via `wp_trim_words`, structure/answerability scoring via DOM/regex — **no external AI call locally**). When connected, optionally calls `app.almaseo.com/api/v1/llm/analyze`, with full local fallback. Metabox card + dropdown options gated/disabled.
- **Visible locked/broken UI?** Yes (disabled "Q&A/LLM Answer" options; gated card) · **Gated pro logic in source?** Yes · **Runs fully local?** Yes · **Calls AlmaSEO?** Optional · **Other external?** No (local path makes NO AI call) · **Storage:** transient
- **Disposition: B** · **Explanation needed:** additive note · **Risk: medium** (disabled dropdown options must go)
- **Action:** Remove disabled options + card gate. Local readiness free; *"Analyzed locally. Connect AlmaSEO for server-side LLM analysis."*

### 14. WooCommerce SEO — `woocommerce`
- **Files:** `includes/woo/*` (class init gated)
- **Current:** Whole module (product metabox, product schema, sitemap provider, breadcrumbs) — all local. Gate blocks class init entirely.
- **Visible locked/broken UI?** No (module simply doesn't load) · **Gated pro logic in source?** Yes · **Runs fully local?** Yes · **Calls AlmaSEO?** No · **Storage:** postmeta/options
- **Disposition: A** · **Explanation needed:** none · **Risk: low**
- **Action:** Remove the `almaseo_feature_available('woocommerce')` init gate; load for all when WooCommerce is active.

### 15. Keyword Suggestions (Google Suggest) — free + `keyword AI` enhancement
- **Files:** `includes/admin/keyword-suggestions.php:60` (Google Suggest), `keyword-suggestions-rest.php:140` (dashboard AI)
- **Current:** Admin keyword field calls `suggestqueries.google.com` directly (1hr transient cache). Optional dashboard AI suggest with metadata fallback.
- **Visible locked/broken UI?** No · **Gated pro logic in source?** Partial (AI path) · **Runs fully local?** No — needs Google Suggest (no offline fallback) · **Calls AlmaSEO?** Optional · **Other external?** **Google Suggest** · **Credentials?** No · **Storage:** transient
- **Disposition: B** · **Explanation needed:** **disclosure required** (3rd-party Google call) · **Risk: medium** (external service needs readme disclosure)
- **Action:** Keep free; disclose Google Suggest in readme "External services". Add additive note for AlmaSEO AI keyword data.

### 16–19. Dashboard-enhanced editor helpers (all local-first)
- **Headline Analyzer** (`headline-analyzer*`): local scoring free; optional `app.almaseo.com/api/v1/headline/analyze`. **Disposition B**, additive note. External? No (local path). Risk low.
- **Readability** (`readability*`): local Flesch/passive/transitions free; optional SERP benchmarks push. **Disposition B**. Risk low.
- **Image SEO** (`image-seo*`): local alt/title templates free; optional AI alt push. **Disposition B**. Risk low.
- **Cornerstone** (`cornerstone*`): local star/marking free; optional auto-suggest push. **Disposition A/B**. Risk low.
- **Action (all):** No gates today block the local path — just confirm gates removed and add additive notes where a dashboard overlay exists.

### 20. DataForSEO — `optimization_dataforseo`
- **Files:** `includes/optimization/DataForSEOProvider.php`, `coming-soon-ui.php`, `settings-optimization.php:~133` (disabled `<option>`), creds `almaseo_dataforseo_login/password`
- **Current:** Placeholder provider (not implemented), "coming soon" poster, **disabled dropdown option**, credential option keys.
- **Visible locked/broken UI?** **Yes (disabled option)** · **Gated pro logic in source?** Stub only · **Runs fully local?** No · **Other external?** DataForSEO (not wired) · **Credentials?** Yes (stored plain) · **Storage:** options
- **Disposition: D** · **Explanation needed:** poster/link if kept · **Risk: medium**
- **Action:** Remove the disabled `<option>` and the unbuilt provider/creds from the wp.org build. Defer until it's a real, opt-in, disclosed feature.

### 21. Search Console & Analytics metabox tabs (dashboard data)
- **Files:** `metabox-callback.php` (Search Console tab, Analytics tab), `seo-playground-ajax.php` GSC/GA page-data calls
- **Current:** Tabs display per-page GSC/GA4 data fetched from `api.almaseo.com/api/plugin/gsc-page-data` & `ga-page-data` (connection required).
- **Runs fully local?** No · **Calls AlmaSEO?** Yes · **Disposition: B/C** · **Explanation needed:** poster/connect panel · **Risk: medium**
- **Action:** When not connected, show a *connect* poster (no empty/fake widgets), not a dead panel. Populate only when connected. Disclose.

### 22. GSC/GA via user-owned Google OAuth (optional, direct)
- **Files:** `includes/evergreen/gsc-integration.php`, `includes/optimization/GSCProvider-v12.php`
- **Current:** User pastes their own Google OAuth client id/secret; plugin calls `googleapis.com/webmasters` + `oauth2.googleapis.com/token` directly (not via SaaS).
- **Runs fully local?** No (Google) · **Other external?** **Google APIs** · **Credentials?** Yes (user-owned, stored plain) · **Disposition: B** · **Explanation needed:** disclosure required · **Risk: medium**
- **Action:** Directory-safe because credentials are user-owned and the call is opt-in — **but must be disclosed** in readme, and client secret storage should be documented. Keep as free, user-configured enhancement.

---

## INFRASTRUCTURE (not features — must resolve for submission)

### I-1. Tier / license architecture
- **Files:** `includes/license/license-helper.php` (`almaseo_is_pro_active`, `almaseo_feature_available`, `almaseo_license_tier`), `almaseo_user_tier`, **`almaseo_tier_limits` / `almaseo_tier_usage` (articles_used / generations_used)**, `includes/admin/tier-labels.php`
- **Finding:** Defaults everyone to `'pro'` so nothing locks at runtime — but the architecture (server-synced tier + **usage quota counters**) is the Serviceware pattern. The `tier_usage` counters are a quota mechanism in source.
- **Disposition: D (remove)** · **Risk: high (source axis)**
- **Action:** Under Path 1 there is no paid plugin tier. Remove the gate guards everywhere, retire `almaseo_feature_available` (or reduce it to a no-op that gates nothing), and **delete tier-limit/usage quota tracking**.

### I-2. Lock screens
- **Files:** `includes/license/locked-ui.php` + the ~8 controllers that render it.
- **Action:** Once each feature ships free (above), remove the lock-screen render branches. `locked-ui.php` can be deleted. (A lock screen is a legitimate poster only in front of a feature whose code is gone — none qualify under Path 1.)

### I-3. Bundled self-updater  ⚠ hard blocker
- **Files:** `includes/almaseo-update.php` (PUC → `https://api.almaseo.com/updates/almaseo-sitemap.json`), `vendor/plugin-update-checker/*`, daily cron `almaseo_updates_daily_check`.
- **Finding:** wp.org-hosted plugins update via wp.org; cannot ship a self-updater pointed at a private server.
- **Disposition: D (remove for wp.org build)** · **Risk: high**
- **Action:** Strip the PUC init + the bundled library + the update-check cron/AJAX for the directory build. (Keep it only in the self-hosted/dashboard distribution.)

### I-4. Phone-home / auto external calls (no opt-in)
- **Files:** activation `api.almaseo.com/api/site-discovery` (fires on activation, **before connect**); daily `almaseo_site_profile_refresh` cron → `api.almaseo.com/api/plugin/site-profile`; tier-sync → `connection-status`; auto secret/JWT generation on activation.
- **Disposition:** gate all behind explicit connection + **disclose** · **Risk: high**
- **Action:** Remove the on-activation site-discovery call (or make it post-connect only). Ensure every AlmaSEO call runs **only when the user has connected**, and document all of them.

### I-5. Dashboard→plugin push endpoints (~14 routes)
- **Files:** `/almaseo/v1/*/push` across cornerstone, image-seo, keyword-suggestions, date-hygiene, eeat, evergreen-freshness, gsc-monitor, headline, readability, internal-links/orphans, redirects, refresh-queue, schema-drift, 404s; plus `/update-meta`.
- **Finding:** Authenticated (`almaseo_api_auth_check`), only active when connected. Compliant as *opt-in* receivers, but they let the dashboard write post meta/options — must be disclosed.
- **Disposition: B/C infrastructure (keep, disclose)** · **Risk: medium**
- **Action:** Keep; document in readme that connecting AlmaSEO allows the dashboard to write SEO data back. No fake controls involved.

### I-6. Required readme "External services" + privacy disclosure
- **Action:** Add a readme section enumerating every external endpoint: AlmaSEO (app/api), Google (Suggest, GSC/GA OAuth, gtag), IndexNow, Bing/Google sitemap ping, Vimeo oEmbed, DataForSEO (if kept). State what's sent, when, and link privacy/TOS. **Required by wp.org for any external call.**

---

## CLEARLY-LOCAL CORE (Disposition A — confirm free, disclose any external)

| Module | External call? | Note |
|--------|----------------|------|
| Redirects, 404 Monitor | No | Local DB only. Free. |
| Sitemaps (XML/image/video/news) | IndexNow + Google/Bing ping + Vimeo oEmbed (opt-in/config) | Free; **disclose** those 3. |
| Health score / analyzer | Self-fetch robots.txt (loopback) | Free. |
| Robots.txt editor, Search Appearance, Import, LLMs.txt | No | Free. |
| Breadcrumbs, FAQ/HowTo/TOC blocks, Link attributes | No | Free (already). |
| Analytics (GA4 output) | Loads `googletagmanager.com` for site visitors | Free; user's own GA ID; **disclose** (frontend). |
| Role Manager, .htaccess editor, Verification codes, RSS controls, Crawl optimization | No | Free. |
| Image SEO, Cornerstone, Headline Analyzer, Readability | No (local path) | Free; optional dashboard overlay = additive note. |

---

## Risk roll-up
- **High:** bundled self-updater (I-3); tier/quota architecture in source (I-1); on-activation phone-home (I-4).
- **Medium:** disabled controls (schema dropdown #3, LLM options #13, DataForSEO #20); GSC Monitor SaaS-only (#11); external-service disclosure (Google Suggest #15, GSC OAuth #22, dashboard data tabs #21); GA4 frontend disclosure.
- **Low:** everything else — mostly mechanical gate removal.
