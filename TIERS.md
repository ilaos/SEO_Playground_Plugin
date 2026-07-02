# AlmaSEO Playground — Free vs. Pro vs. Alma-Enhanced (source of truth)

> If you are trying to figure out whether a feature is Free or Paid, read THIS file first,
> then verify against the code paths named below. Do not guess from feature names.

## The two independent gates (this is the #1 source of confusion)

There are **two separate axes**. A feature can be gated by one, both, or neither.

1. **Tier gate** — `almaseo_feature_available('<key>')` in
   `almaseo-seo-playground/includes/license/license-helper.php`
   Controls Free vs **Pro**.
2. **Connection gate** — `seo_playground_is_alma_connected()`
   Controls whether the optional **AlmaSEO dashboard** ("Alma-Enhanced") layer is active.
   This is NOT the Pro gate. A disconnected free user and a connected free user differ here.

"Alma-Enhanced" features are gated by the **connection** gate, not the tier gate.

## CRITICAL: the Pro gate is currently DORMANT

`almaseo_get_license_tier()` (license-helper.php) **defaults to `'pro'`**:

```php
return get_option( 'almaseo_license_tier', 'pro' );
// "Default all sites to 'pro' until tier enforcement is fully implemented."
```

So even though 17 features are *declared* Pro (below), `almaseo_feature_available()`
returns `true` for all of them today. The Free/Pro split is **declared but not enforced**.
If you see a "Pro" feature that isn't actually locked — that is intentional, not a bug.
Do not "fix" it by flipping the default without an explicit decision from the owner.

## Canonical Pro list (code-authoritative)

Source: the `$pro_features` array in `license-helper.php` (`almaseo_feature_available()`),
mirrored by `almaseo_get_pro_features()`. **Always read the live file** — line numbers drift.

Pro gating keys:

- `bulkmeta` — Bulk Metadata Editor
- `woocommerce` — WooCommerce SEO
- `llm_optimization` — LLM Optimization (advanced)
- `evergreen_advanced` — Advanced Evergreen filters
- `schema_advanced` — Advanced Schema Options
- `schema_multi` — Multi-schema (extra @graph nodes per page)
- `optimization_dataforseo` — DataForSEO provider
- `internal_links` — Internal Links Auto-Linker (hybrid: managing rules is free; auto front-end insertion is the Pro gate)
- `refresh_drafts` — Content Refresh Drafts
- `refresh_queue` — Refresh Queue Autoprioritization
- `date_hygiene` — Date Hygiene Scanner
- `eeat_enforcement` — E-E-A-T Enforcement
- `gsc_monitor` — GSC Monitor
- `orphan_detection` — Orphan Page Detection
- `schema_drift` — Schema Drift Monitor
- `meta_autogen` — Profile-aware title/description generation (per-post button)
- `author_entity_dashboard` — RESERVED, not yet enforced (baseline author schema is FREE)

Everything NOT in that array is Free.

## Human-readable summary

`almaseo-seo-playground/readme.txt`, lines ~17–68 — Free / Pro / Alma-Enhanced lists in
plain English. Kept in sync with the code; if it ever disagrees, the code wins.

## Per-module tier notes

`CLAUDE.md` (repo root) documents each module and its tier in its section header
(e.g. Internal Links = "Hybrid", Refresh Drafts = "Pro only").

## "Alma-Enhanced" ≠ working when connected

The dashboard "enhancement" layer (keyword suggestions, headline rewrites, readability
benchmarks, image alt text, cornerstone detection) is connection-gated. But note: a number
of these enhancement hooks are **dead scaffolding with no live dashboard producer**. A
"dashboard-enhanced" label alone does not prove a feature does anything when connected —
verify there is an actual producer. See the project memory
(`project_plugin_map_gating_audit`, `project_dead_ai_tools_handlers`).

## Quick answer to hand off

> Free vs Pro is defined in `includes/license/license-helper.php` — the `$pro_features`
> array. Human-readable version is `readme.txt` lines ~17–68. The tier currently defaults
> to `'pro'` so all Pro gates are OPEN by design, and dashboard "Alma-Enhanced" features
> are a separate connection gate, not the Pro gate.
