# Future Features — In-Flight & Archived

A running log of features that are part-built, paused, or deliberately
re-architected, with enough detail that a future engineer (or future-you)
can pick them up without re-discovering the context.

---

## Featured Snippet & PAA Targeting — re-routed to dashboard-only

**Status:** Plugin-side feature **removed in 1.14.3**. To be rebuilt as an
all-dashboard feature when prioritized.

### What we built

| Side | Where | What |
|---|---|---|
| Plugin | `includes/snippet-targets/` (now removed) | DB table `wp_almaseo_snippet_targets`, REST receiver `POST /almaseo/v1/snippet-targets/push`, admin page with filter dropdowns + per-row Draft/Approve/Apply/Undo, per-post insertion engine with hidden markers + clean undo, Pro feature gate (`snippet_targeting`) |
| Plugin | `includes/snippet-targets/snippet-targets-rest.php` (now removed) | URL → `post_id` resolution via `url_to_postid()` (added 1.14.2) so the dashboard could send page URLs directly |
| Dashboard | `producers/snippet_targets_producer.py` | Full producer pipeline: GSC pull → page-1 candidate filter → SerpAPI per-query → opportunity assembly → push to plugin. Returns rich `RunResult` (counts + opportunity list + plugin response) |
| Dashboard | `integrations/gsc_per_site.py` | Per-site GSC analytics fetcher using stored OAuth (refresh-on-expire), feeds the producer |
| Dashboard | `utils/serpapi_client.py` | Extended `SerpAPIClient.fetch_serp_features()` returning answer_box + People-Also-Ask + organic top-10. Includes `_normalize_answer_box_type()` mapping SerpAPI's labels to paragraph/list/table/definition |
| Dashboard | `routes/snippet_targets_routes.py` | Blueprint at `/site/<id>/snippet-targets` with page + JSON list + JSON trigger endpoints. Per-site auth gated by `sites.user_id` |
| Dashboard | `templates/snippet_targets_runs.html` | Per-site runs page with Run-now form, threshold tuners, dry-run toggle, runs history table, auto-refresh polling |
| Dashboard | `automation_scheduler.py` | `AutomationScheduler.run_snippet_targets_for_all_sites()` registered for daily 04:00 UTC sweep, iterates every site with a selected GSC property |
| Dashboard | DB | `snippet_target_runs` log table (lazy-created on first run): per-run counts, status, dry-run flag, settings JSON, plugin response, error |

### Why we're rebuilding it

Live test on cobbcountylaw.com (May 14): the producer surfaced **76 real PAA
opportunities** end-to-end (4,226 GSC rows → 28 page-1 candidates → 76 unique
PAA opportunities pushed). The mechanics work.

But UX-wise the split between dashboard (research) and plugin (drafting +
applying) doesn't match how operators actually want to work:

1. **Research is a poor fit for a WP admin panel.** A bare table of 76 questions
   with format hints is data, not insight. Operators want SERP screenshots,
   competitor URLs, search-volume trends, intent classification — all of which
   want a wide-screen, cross-site dashboard view.
2. **Drafting belongs with the AI writer.** AlmaSEO's dashboard already has a
   content writer. Asking operators to hand-write 76 snippet answers in a
   plugin textarea is a non-starter; piping each opportunity through the AI
   writer (which already handles short-form, format-aware prompts) is the
   natural fit.
3. **Applying to a post is the only WP-native step.** And the user noted that
   even that doesn't *need* a custom plugin path — the operator can copy the
   AI-drafted answer into the WP block editor themselves. Auto-insertion is a
   convenience, not a requirement.
4. **Discoverability gap.** The plugin panel was buried in a submenu nobody
   navigates to; the dashboard had zero surface for it. A feature nobody finds
   is no feature.

### What's left to ship the dashboard-only version

In rough build order. Each stage is independently shippable.

#### A. Persist opportunities on the dashboard (so we stop relying on the plugin)

- Add `snippet_target_opportunities` table to `post_schedule.db`:
  ```
  id, run_id (FK), site_id, page_url, query, snippet_format,
  serp_feature ('featured_snippet'|'people_also_ask'),
  current_position, search_volume, current_snippet_link,
  status ('new'|'drafting'|'drafted'|'applied'|'won'|'rejected'|'expired'),
  draft_content, applied_at, applied_by_user_id,
  created_at, updated_at
  ```
- Update `producers/snippet_targets_producer.py` to write to that table
  instead of POSTing to the plugin. `_push_to_plugin()` is still in the file
  but currently bypassed (the plugin endpoint no longer exists post-1.14.3).
- Migrate the existing `snippet_target_runs.opportunities` JSON trail into
  this table when first introduced.

#### B. Build the dashboard "Snippet Opportunities" page

- New route: `/site/<id>/snippet-opportunities` (separate from the existing
  `/site/<id>/snippet-targets` runs page; the runs page becomes a back-end
  diagnostic).
- Per-row UX: query, parent ranking page, format hint, search volume,
  current snippet holder URL (link out), "Generate draft with AI" CTA,
  "Mark as drafted/won/rejected/expired" controls.
- Dedupe to one row per (page, query) — currently the producer is
  per-page-per-query which over-counts. Pick the best-positioned page per
  query.
- Brand-collision filter: drop PAA questions where the question text doesn't
  share keywords with the parent query (kills the
  "Ninja/Twitch" / "Willie Gary's net worth" noise we saw on cobbcountylaw).

#### C. Wire into the existing AI content writer

- "Generate draft with AI" → POST to existing writer endpoint with a small
  scoped prompt: `{format: 'paragraph'|'list'|'table'|'definition',
  question: <PAA question or parent query>, target_length: 40-60 words,
  style_guide: <site-level brand voice>}`.
- Save the writer's output back into `snippet_target_opportunities.draft_content`.
- Show before/after in the page; allow inline editing.

#### D. Publishing

The user's call: **the operator copies the answer into the page themselves.**
No auto-insertion, no plugin involvement.

- Provide a one-click "Copy answer" button + suggested heading (`<h3>` mirroring
  the question text — Google's PAA selection signal).
- Suggested next-step: "Paste this into <page URL> near the existing content
  about <closest H2/H3 already on the page>." Optional: include a deep-link
  into the WP editor with a hash anchor if discoverable.
- Track the operator manually marking the opportunity as "applied" so we can
  measure win-rate over time.

#### E. Win-tracking loop

- 7/14/30 days after status='applied', re-run SerpAPI for that query and
  check whether the source URL of the answer-box / PAA now matches the site.
- Mark the opportunity 'won' or 'lost' automatically.
- Aggregate stats in the dashboard: hit rate per site, win rate per format,
  win rate per opportunity source.

#### F. Plugin reintroduction (only if we want auto-insertion later)

If we ever want auto-insertion back, the plugin work is small and isolated:
the deleted `snippet-targets-engine.php` had the marker-based insertion +
revision-creating apply/undo logic. Re-add only that module, expose
`POST /almaseo/v1/snippet-targets/insert` taking `{post_id_or_url, html,
heading_text}`, leave everything else (research, drafting) on the dashboard.
This is V2-only — do not ship until the dashboard-only flow is validated.

### What's currently still on the dashboard server (post-removal)

These files were left in place because they're harmless and form the
foundation for the rebuild. **Do not delete:**

- `producers/__init__.py`
- `producers/snippet_targets_producer.py` (push will fail with 404 until
  we point it at the new dashboard storage — see Stage A)
- `integrations/gsc_per_site.py`
- `utils/serpapi_client.py` PAA extension + answer-box normalizer
- `routes/snippet_targets_routes.py` + `templates/snippet_targets_runs.html`
- The daily 04:00 UTC scheduler entry — **disabled in the same release** that
  removed the plugin side, so it doesn't loop on 404 forever. Re-enable when
  Stage A lands.

### What was deleted from the plugin in 1.14.3

```
includes/snippet-targets/snippet-targets-loader.php
includes/snippet-targets/snippet-targets-install.php
includes/snippet-targets/snippet-targets-model.php
includes/snippet-targets/snippet-targets-engine.php
includes/snippet-targets/snippet-targets-rest.php
includes/snippet-targets/snippet-targets-controller.php
admin/pages/snippet-targets.php
assets/css/snippet-targets.css
assets/js/snippet-targets.js
docs/snippet-targets.md
```

Plus references removed from:
- `almaseo-seo-playground.php` (loader require/init)
- `includes/license/license-helper.php` (Pro feature flag `snippet_targeting`)
- `includes/license/locked-ui.php` (locked-feature card)
- `includes/admin/tier-labels.php` (`almaseo-snippet-targets` label entry)

The `wp_almaseo_snippet_targets` table is intentionally **not dropped** —
sites that ran the feature pre-1.14.3 may have draft content there; better
to leave a few KB of unused rows than risk destroying any user's work.
