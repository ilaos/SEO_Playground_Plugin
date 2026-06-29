# WP.org Compliance Audit — Feature-by-Feature Classification

Snapshot: 2026-06-28. Audited against [`COMPLIANCE.md`](COMPLIANCE.md). No code changed yet.

## Read this first: there are TWO axes, not one

A feature can be clean on one axis and a violation on the other. Every finding
below is judged on both:

- **UI axis** — what the user *sees*. Violation = a visible control that looks
  operable but is disabled / locked / reverts / quota'd. *Hiding a control
  entirely is NOT a violation* — only visible-but-broken controls are.
- **Source axis** — what the reviewer *reads*. Violation = real premium logic
  shipped in the free codebase behind `if ( ! almaseo_feature_available(...) )`.
  Invisible gated code still fails this axis. The fix is to **remove the code to a
  separate premium plugin**, not to hide it better.

Two consequences that the cleanup hinges on:

1. **A lock screen is a compliant *poster* only after the real feature code is
   gone.** Today every lock screen sits in front of a full working engine that
   ships in free → that combination is textbook trialware. The lock screen
   becomes legitimate *only* once the engine is extracted.
2. **`almaseo_is_pro_active()` defaults everyone to `'pro'`** (`license-helper.php:37`),
   so at runtime nothing is actually locked right now — every feature works. But
   the gating architecture + server-driven tier sync is itself the **Serviceware
   red flag**: functionality designed to switch off based on a remote
   service/payment. The reviewer flags the source, not the current runtime.

## Per-gated-feature disposition: two valid endings

For each Pro-gated feature, exactly one of these makes it compliant:

- **SHIP FREE** — delete the gate. Code stays, works for everyone. Best when the
  feature is table-stakes / competitors give it free / it has no real premium
  producer behind it.
- **EXTRACT** — delete the module from the free plugin entirely; leave a poster
  (or the `do_action`/`has_action` seam from COMPLIANCE.md). Real code ships only
  in a separate premium plugin. Best for genuinely differentiated,
  dashboard-powered features.

---

## A. UI DANGER ZONE — visible broken controls (fix regardless of product decision)

These are disabled/locked controls the user can see. They must change no matter
what you decide about the underlying feature.

| # | Control | Location | Problem | Fix |
|---|---------|----------|---------|-----|
| A1 | Schema Type dropdown — 9 options (FAQPage, HowTo, LocalBusiness, Service, Person, Organization, Product, Event, Recipe) rendered `disabled aria-disabled="true" class="is-locked"` + 🔒 | `metabox-callback.php:~2477-2497` | Disabled dropdown options that imply selectability. Especially contradictory — FAQ/HowTo blocks already ship FREE. | Remove disabled options. Either ship the types free, or drop them from the select and put a plain-text "Members get more schema types — Learn more" line below it. |
| A2 | LLM Summary dropdown — "Q&A Format" / "LLM Answer" options `disabled` + "(Pro)" label | `metabox-callback.php:~263-264` | Disabled options implying selectability. | Remove disabled options; replace with a text note + link, or ship free. |
| A3 | Multi-schema checkbox grid wrapped in `if ($multi_schema_unlocked)` | `metabox-callback.php:~2560-2617` | On the UI axis this is *hidden*, so not a visible-broken-control violation. But it gates a real feature in source (see B). | No UI fix needed; resolve on source axis (B6). |
| A4 | DataForSEO `<option ... disabled>DataForSEO (Coming Soon) 🔜</option>` | `settings-optimization.php:~133-135` | Borderline. It's an unreleased feature (not locked-behind-payment), but a disabled `<option>` still reads as crippleware to a reviewer. | Low risk. Safest: replace with plain text "DataForSEO — coming soon" instead of a disabled selectable option. |

> **Correction to the sub-audits:** one sweep flagged "silently hiding the
> multi-schema checkboxes" as a transparency violation. It is **not** — hiding a
> control is compliant on the UI axis. The only live issue there is the gated code
> in source (B6).

---

## B. SOURCE DANGER ZONE — real premium logic gated inside free source

Each of these ships a complete, working implementation in the free plugin behind
a feature gate (and most front it with a lock screen). All fail the source axis.
The "Recommended disposition" is my call given the competitive-parity goal and
whether a real premium producer exists — **these are your decisions to confirm.**

| # | Feature (gate key) | What ships in free source | Current free UX | Recommended disposition | Why |
|---|--------------------|---------------------------|-----------------|-------------------------|-----|
| B1 | **Bulk Metadata Editor** (`bulkmeta`) | Full admin page + REST + autofill | Works (tier=pro) / lock screen if flipped; REST 403-gated | **SHIP FREE** | Competitors give bulk editing free; code is complete; strong free hook. |
| B2 | **WooCommerce SEO** (`woocommerce`) | Whole module: product metabox, schema, sitemap provider — class init gated | Nothing loads if flipped | **DECISION NEEDED** (lean SHIP FREE for basic product meta/schema) | Yoast WooCommerce SEO is a paid addon; RankMath gives basics free. Could split basic-free / advanced-extract. |
| B3 | **Advanced Schema output** (`schema_advanced`) | ~1000 LOC of JSON-LD builders (FAQ, HowTo, LocalBusiness, Service, Event, Recipe, Article…) behind early `return` | No advanced schema emitted if flipped | **SHIP FREE** | Competitors emit these free; plugin already ships FAQ/HowTo *blocks* free — gating the schema output is self-contradictory. |
| B4 | **Multi-schema** (`schema_multi`) | Secondary @graph node output gated | Only primary type emitted | **SHIP FREE** (with B3) | Same family as B3. |
| B5 | **Internal Links auto-linker** (`internal_links`) | Full admin page + engine. **REST endpoints are UNGATED** | Admin shows lock screen, but REST fully works for any `manage_options` user | **DECISION NEEDED** + fix the inconsistency | The lock screen is currently a *lie* — the feature works via REST. Either ship free (remove lock screen) or extract (and the ungated REST must go too). |
| B6 | **Refresh Drafts** (`refresh_drafts`) | Full admin pages + engine. **REST UNGATED** (`edit_posts` only) | Lock screen, but REST fully works | **EXTRACT** (or ship free) | Differentiated/dashboard-powered. If kept gated it must extract; the ungated REST is a second bug. |
| B7 | **Refresh Queue** (`refresh_queue`) | Full admin page + engine; REST properly 403-gated | Lock screen only | **EXTRACT** | This is the *correctly gated* module today, but "correctly gated" still fails the source axis — code must leave free. |
| B8 | **Date Hygiene Scanner** (`date_hygiene`) | Full 536-LOC engine + REST (403-gated) | Lock screen only | **EXTRACT** | Differentiated; real engine present. |
| B9 | **E-E-A-T Enforcement** (`eeat_enforcement`) | Full engine + REST (403-gated) | Lock screen only | **EXTRACT** | Differentiated. |
| B10 | **GSC Monitor** (`gsc_monitor`) | Full engine + REST (403-gated) | Lock screen only | **EXTRACT** | Dashboard-powered. |
| B11 | **Schema Drift Monitor** (`schema_drift`) | Full engine + REST (403-gated) | Lock screen only | **EXTRACT** | Differentiated. |
| B12 | **Evergreen Advanced** (`evergreen_advanced`) | 4 scoring fns return stubs (0/'low'/false) when gated; advanced-summary REST 403; advanced list filters 403 | Scores read 0, advanced filters 403 | **DECISION NEEDED** (split: basic evergreen free, AI-freshness extract) | Local heuristics should be free; dashboard-LLM freshness overlay is the genuine premium piece — extract that part. |
| B13 | **LLM Optimization card** (`llm_optimization`) | Readiness snapshot + Advanced Insights card gated in metabox | Card hidden/locked | **SHIP FREE (local part)** | CLAUDE.md says LLM optimization is local heuristics w/ optional dashboard enhancement. Local must be free; only dashboard overlay extracts. |
| B14 | **Per-post meta autogen** (`meta_autogen`) | `autofill-generator.php:686` — gate adds a `local_locked` badge | **Button still generates locally**; shows upsell badge | **SHIP FREE + reword** | Already the closest to compliant (control works, ad text shown). Just remove the gate/`local_locked` naming and reword per phrasing guidance ("Members get profile-aware generation"). |

Features in `$pro_features` with no real free-source feature behind them (already
posters / not yet built): **`optimization_dataforseo`** (coming-soon poster — keep,
see A4) and **`author_entity_dashboard`** (RESERVED, not enforced — fine).

> Note for B7–B11: per your memory, the "dashboard enhancement" layer for ~13
> features is dead scaffolding with no live producer. If a feature has *no* real
> premium producer, "EXTRACT" effectively means "the free plugin shouldn't pretend
> it exists" — you may prefer SHIP FREE for any of these where the local engine is
> genuinely useful on its own (Date Hygiene and Schema Drift both run fully local).

---

## C. Compliant POSTERS — keep as-is (but conditional on B cleanup)

| Item | Location | Status |
|------|----------|--------|
| `almaseo_render_locked_feature()` / `almaseo_render_feature_locked()` lock screens | `locked-ui.php` | Compliant poster **only after** the gated engine behind each is extracted (B). Until then, part of a trialware combo. |
| "Locked features" promo grid (10 marketing cards) | `metabox-callback.php:~4932-5083` | Pure ad. Compliant. |
| Advanced-Schema / Evergreen lock *cards* in settings | `settings.php:~485, ~629` | Display-only ad cards. Compliant. |
| DataForSEO coming-soon panel | `optimization/coming-soon-ui.php` | Poster for unbuilt feature. Compliant (but see A4 for the disabled `<option>`). |
| Evergreen "Advanced Insights" panel conditional render | `dashboard.php:~498` | Conditionally rendered ad panel — compliant on UI axis. |

---

## D. Low-risk / acceptable

| Item | Location | Note |
|------|----------|------|
| LLM analysis schema/evergreen "hints" gated | `llm-rest.php:~608, ~663` | Optional enrichment skipped for free; minor gated code. Resolve alongside B12/B13. |
| `ai_used` flag in bulkmeta | `bulkmeta-rest.php:594` | Just a response flag, not a quota. Clean. |
| bulkmeta `limits` | `bulkmeta-controller.php:109` | Batch-size processing caps, not a paywall quota. Clean. |

**No usage quotas/counters anywhere in the plugin** — that entire violation
category is clean.

---

## The systemic decision underneath all of B

Whatever you decide per-feature, the **`license-helper.php` tier system itself**
(`almaseo_feature_available` → `almaseo_is_pro_active` → server tier sync) is the
architecture a reviewer reads as Serviceware. The end state for a compliant free
plugin is: **no `almaseo_feature_available()` gate guards anything that ships in
the free plugin.** Either the guarded code is ungated (ship free) or it's gone
(extracted). The gate function can survive only if it gates nothing.

## Suggested cleanup order (when you're ready — not done yet)

1. **A1–A4** — kill the visible disabled controls (fast, unambiguous).
2. **B1, B3, B4, B13, B14** — ship-free the table-stakes items (delete gates).
3. **B2, B5, B12** — make the split decisions (basic-free vs advanced-extract).
4. **B6–B11** — extract the differentiated modules to the premium plugin; replace
   each admin page with a poster or the hook seam; remove the ungated REST in B5/B6.
5. Reduce `license-helper.php` to only what survives; retire lock screens whose
   features were shipped free.
