# Evergreen AI Freshness — Dashboard → Plugin Push Contract

Handoff spec for the AlmaSEO dashboard / server agent. The plugin side
(v1.16.0) is built and live; this describes the endpoint the dashboard must
call to light the feature up.

## What the dashboard does

1. Enumerate a connected site's published posts/pages (the plugin already
   exposes post lists via its Evergreen REST API and the standard WP REST API).
2. For each post, run an LLM pass over the content to detect **semantic
   staleness** — outdated statistics, deprecated references, stale year/price
   mentions, advice that no longer holds.
3. Push the analysis back to the plugin with the endpoint below.

The plugin stores it in post meta `_almaseo_eg_ai_freshness` and overlays it on
the local freshness heuristic — when present and not drifted, the dashboard
score replaces the heuristic in `almaseo_eg_compute_ai_freshness_score()`, and
the findings render in the Evergreen metabox under an "AI" badge.

## Endpoint

```
POST https://{site}/wp-json/almaseo/v1/evergreen-freshness/push
```

- **Auth:** HTTP Basic Auth with a WordPress application password — the same
  credential used by every other AlmaSEO dashboard push endpoint
  (`permission_callback` is `almaseo_api_auth_check`).
- **Content-Type:** `application/json`
- **Batch:** up to **200 items** per request (extras are ignored).

## Request body

```json
{
  "items": [
    {
      "post_id": 123,
      "score": 78,
      "summary": "References a 2022 pricing table and the retired Page Experience signal.",
      "content_hash": "9f86d081884c7d659a2feaa0c55ad015",
      "findings": [
        {
          "severity": "high",
          "excerpt": "Plans start at $29/month...",
          "issue": "Pricing is from 2022 and no longer matches the current rate card.",
          "suggestion": "Update to current pricing or remove specific figures."
        },
        {
          "severity": "medium",
          "excerpt": "Google's Page Experience update...",
          "issue": "Page Experience was deprecated as a distinct ranking system.",
          "suggestion": "Reframe around Core Web Vitals as general guidance."
        }
      ]
    }
  ]
}
```

### Field semantics

| Field | Type | Notes |
|-------|------|-------|
| `post_id` | int | **Required.** WordPress post ID on the target site. |
| `score` | int 0–100 | **Staleness** score — *higher = more out of date / stronger refresh candidate.* Same orientation as the local heuristic it replaces. Clamped 0–100. |
| `summary` | string | One-line plain-text summary. Optional. |
| `content_hash` | string | `md5()` of the `post_content` that was analyzed. Used for drift detection (see below). Strongly recommended. If omitted, the plugin hashes the current content at push time. |
| `findings` | array | Up to **30** items (extras ignored). Optional but the main value. |
| `findings[].severity` | enum | `high` \| `medium` \| `low`. Anything else is coerced to `medium`. |
| `findings[].excerpt` | string | Short quote of the offending passage. |
| `findings[].issue` | string | What is stale and why. |
| `findings[].suggestion` | string | Recommended fix. |

All strings are sanitized server-side (`sanitize_text_field` / `sanitize_textarea_field`).

## Drift detection

The plugin compares the stored `content_hash` against `md5()` of the post's
*current* `post_content` on every read:

- **Match** → analysis is current → the dashboard `score` overlays the local
  heuristic, and the metabox shows the findings normally.
- **Mismatch** (post edited since analysis) → the overlay is skipped (scoring
  falls back to the local heuristic) and the metabox flags the findings as
  possibly out of date.

To keep an analysis "current," send the `content_hash` of exactly the content
you fed the LLM.

## Response

```json
{
  "success": true,
  "stored": 2,
  "skipped": [
    { "post_id": 999, "reason": "post_not_found" },
    { "reason": "missing_post_id" }
  ]
}
```

- `stored` — count of items written.
- `skipped` — items rejected, each with a `reason`
  (`missing_post_id` | `post_not_found`).

A malformed body (no `items` array) returns HTTP 400 `invalid_payload`.

## Re-pushing

Pushes are idempotent per post — a new push for the same `post_id` overwrites
the previous analysis. Re-analyze and re-push whenever content changes or on a
schedule.
