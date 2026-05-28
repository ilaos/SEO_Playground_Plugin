# Evergreen AI Freshness — Server-Side Build Plan

Companion to [`EVERGREEN_AI_FRESHNESS_CONTRACT.md`](EVERGREEN_AI_FRESHNESS_CONTRACT.md).
The plugin side has been shipped (v1.16.0). This document is the implementation
plan for the **dashboard / server side** that calls the contract endpoint.

All server paths in this doc are relative to `/root/FULLY WORKING WITH MULTIPLE SITE OPTIONS/`
on the production box (also the SSH dev environment).

## What we reuse vs. what we build

| Concern | Status | Where |
|---------|--------|-------|
| Auth to a connected WP site (headers + URL format) | ✅ Exists | `dashboard.py::test_connection_with_fallbacks(site)` |
| Enumerate published posts on a WP site | ✅ Exists | `utils/content_similarity.py::_fetch_existing_content(site_id)` — already paginates `/wp-json/wp/v2/posts` and `/wp-json/wp/v2/pages`, includes 5-minute file cache |
| Detect which plugin is installed (Playground vs Connector) | ✅ Exists | `dashboard.py::get_connector_plugin_status(site_id)` — returns `playground_active`, `connector_active`, `installed_plugin_type` |
| Tracked download links for Playground / Connector zips | ✅ Exists | `/api/plugin-download/<plugin_type>` at `dashboard.py:10491` |
| Sidebar tab-switching pattern in client profile | ✅ Exists | `templates/client_profile.html` — every existing `data-tab="…"` link |
| LLM client | ✅ Exists | OpenAI Python SDK already in use across `dashboard.py`; API key in `~/.bashrc` (loaded via `start_with_openai.sh`) |
| **LLM staleness-detection prompt + structured output** | ❌ New | `utils/freshness_analyzer.py` |
| **POST to plugin's `/wp-json/almaseo/v1/evergreen-freshness/push`** | ❌ New | Same module |
| Flask route to trigger the analysis | ❌ New | `dashboard.py` |
| Background job orchestration (avoid blocking the HTTP request) | ❌ New | Decide approach — see Open Design Decisions |
| UI tab + status polling | ❌ New | `templates/client_profile.html` + small JS |

## Files to create

### 1. `utils/wp_post_fetcher.py` (NEW — refactor of existing helper)

Extract `_fetch_existing_content` from `utils/content_similarity.py` into a
shared module so both content-similarity and freshness use the same code path.
Keep `_fetch_existing_content` as a thin wrapper that delegates.

```python
"""
Shared WordPress post fetcher.

Used by:
- utils/content_similarity.py (Dotty's Recommendations)
- utils/freshness_analyzer.py (AI Freshness)

Auth, caching, and pagination live here once.
"""
def fetch_site_posts(site_id, *, include_content=False, post_types=None,
                    cache_ttl=300, max_posts=None):
    """
    Return [{id, title, slug, url, modified, content?}] for a site.

    Args:
        site_id: sites.id row
        include_content: if True, hit the per-post endpoint to get rendered
            content (freshness needs this; similarity does not)
        post_types: ['post', 'page'] by default; can include 'product' etc.
        cache_ttl: seconds; reuses the existing /tmp/almaseo_similar_* cache
            layout but with a distinct key per (site_id, include_content)
        max_posts: optional safety cap (e.g. 100 for cost control)
    """
    ...
```

**Migration:** keep the existing `_fetch_existing_content` signature; have it
call `fetch_site_posts(site_id, include_content=False, ...)`. No behavior
change for content-similarity, but freshness can request the heavier content
payload.

### 2. `utils/freshness_analyzer.py` (NEW — the core feature)

```python
"""
AI Freshness analyzer for the Evergreen plugin contract.

Per the contract (EVERGREEN_AI_FRESHNESS_CONTRACT.md), this module:
1. Fetches posts from a WP site (via wp_post_fetcher).
2. Sends each post's content to OpenAI with a staleness-detection prompt.
3. Coerces the LLM output into the contract's JSON schema.
4. POSTs the batch back to the site's plugin push endpoint.
"""

import hashlib
import json
import logging
import os
import requests
from openai import OpenAI

from utils.wp_post_fetcher import fetch_site_posts

CONTRACT_PATH = "/wp-json/almaseo/v1/evergreen-freshness/push"
BATCH_SIZE = 50   # contract allows 200, but smaller batches give better UX feedback
OPENAI_MODEL = "gpt-4o-mini"  # cost-controlled default; bump for higher quality

SYSTEM_PROMPT = """You audit WordPress posts for staleness. Identify passages \
that are likely outdated — old statistics, deprecated technology references, \
stale year/price mentions, advice that no longer applies in {current_year}. \
You output strict JSON matching the given schema."""

USER_PROMPT_TEMPLATE = """Current year: {current_year}
Post title: {title}
Post URL: {url}
Post last modified: {modified}

Post content (HTML stripped):
\"\"\"
{content_excerpt}
\"\"\"

Identify up to 10 specific passages that are likely out of date. Score each \
finding's severity: 'high' if it materially misleads the reader, 'medium' if \
it's noticeably dated but harmless, 'low' if it's a minor anachronism.

Give an overall staleness score (0-100): 0 = totally fresh, 100 = should be \
rewritten end-to-end. A post with no stale signals returns score 0 and \
findings: []."""

# OpenAI structured-output schema matching the contract's items[] shape
FRESHNESS_SCHEMA = {
    "name": "freshness_analysis",
    "schema": {
        "type": "object",
        "properties": {
            "score": {"type": "integer", "minimum": 0, "maximum": 100},
            "summary": {"type": "string"},
            "findings": {
                "type": "array",
                "maxItems": 10,
                "items": {
                    "type": "object",
                    "properties": {
                        "severity": {"type": "string", "enum": ["high", "medium", "low"]},
                        "excerpt":    {"type": "string"},
                        "issue":      {"type": "string"},
                        "suggestion": {"type": "string"},
                    },
                    "required": ["severity", "excerpt", "issue", "suggestion"],
                },
            },
        },
        "required": ["score", "summary", "findings"],
    },
    "strict": True,
}


def analyze_post(post, client=None):
    """
    Run the LLM staleness check on a single post.

    Returns the contract item dict (post_id, score, summary, content_hash,
    findings) — ready to be appended to the push batch.
    """
    ...


def push_results(site_url, headers, items):
    """
    POST items[] to the site's plugin push endpoint. Returns the parsed
    response or raises on transport/HTTP error.
    """
    url = site_url.rstrip("/") + CONTRACT_PATH
    resp = requests.post(url, json={"items": items}, headers=headers, timeout=30)
    resp.raise_for_status()
    return resp.json()


def run_for_site(site_id, *, max_posts=None, progress_cb=None):
    """
    Full pipeline: fetch → analyze → push.

    Returns a summary dict {analyzed, pushed, skipped, errors, elapsed_seconds}.
    """
    ...
```

### 3. `templates/partials/freshness_tab.html` (NEW — optional)

If you prefer not to inline the new tab block in `client_profile.html`, factor
it out here. Otherwise inline is fine — match the pattern of the other Content
Hub tabs which are inline.

## Files to modify

### `dashboard.py` — add two routes and the Content-Hub-aware site context

**Around the other `/api/...` routes (near `dashboard.py:22941` where
`/api/similar-content` lives):**

```python
@app.route("/api/freshness/analyze", methods=["POST"])
@login_required
def freshness_analyze():
    """
    Kick off an AI Freshness analysis for a site.

    Body: {"site_id": int, "max_posts": int (optional)}
    Returns: {"job_id": str} for polling, OR the full summary if we go sync.
    """
    site_id = request.get_json().get("site_id")
    # 1. Verify site belongs to current_user.
    # 2. Verify get_connector_plugin_status(site_id)['playground_active']
    #    — if not, return 400 with a clear message ("Requires SEO Playground").
    # 3. Kick off freshness_analyzer.run_for_site(site_id) — see Open Design
    #    Decisions for sync vs. background.
    # 4. Return job_id or summary.


@app.route("/api/freshness/status/<job_id>", methods=["GET"])
@login_required
def freshness_status(job_id):
    """Poll endpoint for background runs."""
    ...
```

**In the client_profile render handler** — where the template context dict is
assembled — add the plugin-status flag so the Jinja gate can render:

```python
plugin_status = get_connector_plugin_status(site_id)
context['freshness_available']     = plugin_status.get('playground_active', False)
context['freshness_plugin_state']  = plugin_status.get('installed_plugin_type')  # 'playground' | 'connector' | None
```

### `templates/client_profile.html` — sidebar item + content block

**1. Add the 4th item inside the Content Hub group** (after line 825, before
the closing `</div>` of the sidebar-group-content):

```html
<a href="javascript:void(0)" class="sidebar-item" data-tab="content-freshness">
    <i class="fas fa-clock-rotate-left"></i>
    Content Freshness
</a>
```

**2. Add the matching content section** (near line 2276 where the existing
Content Hub sections render). The Jinja gate:

```jinja2
<!-- Content Freshness Tab -->
<div class="tab-pane" id="tab-content-freshness" data-tab-id="content-freshness">
    <div class="tab-header">
        <h2>Content Freshness</h2>
        <p class="tab-description">
            Find posts that are losing rankings because they're out of date —
            old statistics, deprecated references, stale year/price mentions.
        </p>
    </div>

    {% if freshness_available %}
        {# Full UI: post list, "Analyze All" button, results table.
           Wire to /api/freshness/analyze + /api/freshness/status/<job_id>. #}
        <div class="freshness-controls">
            <button id="freshness-run-btn" class="btn btn-primary" data-site-id="{{ site.id }}">
                <i class="fas fa-magnifying-glass-chart"></i> Analyze Content Freshness
            </button>
            <span id="freshness-status-pill" class="status-pill" hidden></span>
        </div>
        <div id="freshness-results" class="freshness-results"></div>

    {% elif freshness_plugin_state == 'connector' %}
        {# Connector-only — upgrade CTA. #}
        <div class="empty-state-card warning">
            <i class="fas fa-arrow-up-right-from-square fa-2x"></i>
            <h3>Content Freshness requires the SEO Playground plugin</h3>
            <p>
                This site has the lightweight <strong>AlmaSEO Connector</strong>
                installed. Content Freshness sends LLM-generated findings to
                your WordPress site via the Playground plugin's REST endpoint —
                that endpoint isn't in the Connector.
            </p>
            <button class="btn btn-primary" onclick="document.querySelector('[data-tab=&quot;wordpress&quot;]').click()">
                Get SEO Playground →
            </button>
        </div>

    {% else %}
        {# No plugin at all — install CTA. #}
        <div class="empty-state-card">
            <i class="fas fa-plug fa-2x"></i>
            <h3>Connect WordPress first</h3>
            <p>This client doesn't have an AlmaSEO plugin installed yet.</p>
            <button class="btn btn-primary" onclick="document.querySelector('[data-tab=&quot;wordpress&quot;]').click()">
                Install SEO Playground →
            </button>
        </div>
    {% endif %}
</div>
```

**3. JS handler** — match the existing pattern in the file. A small `fetch()`
to `/api/freshness/analyze`, optimistic UI toggle, then poll
`/api/freshness/status/<job_id>` every ~5 seconds until done.

## Sequence walkthrough

```
User clicks "Analyze Content Freshness" on the Content Hub tab
   │
   ▼
POST /api/freshness/analyze {site_id: 42}
   │
   │   freshness_analyze() validates user owns site, plugin is playground_active
   │   spawns background job (see Open Design Decisions below)
   │
   ▼
Background worker:
   1. fetch_site_posts(42, include_content=True, max_posts=100)
   2. For each post (in batches of 5 for concurrent OpenAI calls):
        - analyze_post(post)  →  contract item dict
   3. push_results(site_url, headers, items[])
        → POSTs to {site.url}/wp-json/almaseo/v1/evergreen-freshness/push
        → Plugin stores under _almaseo_eg_ai_freshness post meta
   4. Mark job complete; store summary.

UI polls /api/freshness/status/<job_id> every 5s
   → on completion, renders the summary
   → user clicks through to the Evergreen tab in WP to see findings inline
```

## Open design decisions

1. **Sync vs. background job.** A 100-post analysis at ~3 seconds per OpenAI
   call is ~5 minutes — way too long for a single HTTP request. Options:
   - **Threading.Thread with in-memory job tracker** — simplest, fine for
     single-server. Status stored in a dict keyed by job_id; lost on restart.
   - **Background workers** — the codebase has a hint of an existing pattern
     (`templates/create_post.html.backup_before_background_workers`). Worth
     auditing before deciding. If the pattern exists, follow it.
   - **Synchronous with hard cap (~20 posts per run)** — UX compromise but no
     infrastructure. User triggers it multiple times for big sites.
   - *Recommendation: thread+job_id for v1. Promote to real background queue
     if it becomes a quality issue.*

2. **OpenAI model + cost.** `gpt-4o-mini` is ~$0.15/M input, ~$0.60/M output.
   A 1500-word post is ~2K tokens in + ~500 tokens out ≈ $0.0006/post. 100
   posts ≈ $0.06. Cheap. Bump to `gpt-4o` (~10x cost) only if mini quality
   is insufficient. Decide after a test run on real client content.

3. **Re-analysis policy.** When the user clicks "Analyze" twice in a row, do
   we re-analyze every post or skip posts whose `content_hash` matches the
   previously stored analysis? Skip for the v1 default (saves tokens); add an
   explicit "Re-analyze all" button if needed.

4. **Per-site rate limits.** Add a "last analysis at" timestamp per site;
   block runs within ~6 hours of the previous one (unless force=true). Stops
   accidental token burn from impatient clicks.

5. **Multi-site scope.** v1 is one-site-at-a-time (user clicks the button on
   each client). Multi-site batch ("analyze all my clients") is a follow-up.

6. **Scheduled / automatic runs.** Defer. v1 is manual button only. Once we
   have telemetry on cost-per-client, decide on cadence (weekly? monthly?
   on-content-change?).

## Test plan

1. **Backend unit test** — `analyze_post()` against a known-stale post (e.g.
   a post that says "in 2022 Google deprecated X"). Verify the LLM returns
   score > 50 and at least one finding with severity high|medium.

2. **End-to-end against submittal** — trigger analysis from the dashboard,
   wait for completion, then visit the Evergreen tab inside the submittal
   WP admin. The Advanced Insights "Refresh Priority" panel should show real
   data instead of zeros, and the per-post metabox should show the findings
   under the "AI" badge.

3. **Plugin gating** — temporarily deactivate Playground on submittal (leave
   Connector active); reload the Content Hub → Content Freshness tab; should
   render the upgrade CTA, not the analyze button.

4. **Drift detection** — analyze a post, then edit it and save; the
   `content_hash` mismatch should cause the plugin to fall back to the local
   heuristic and flag findings as possibly out of date in the metabox.

## Estimated effort breakdown

| Step | Estimate |
|------|----------|
| Refactor `_fetch_existing_content` → `wp_post_fetcher.py` (no behavior change for existing callers) | 30 min |
| `freshness_analyzer.py` — prompt, OpenAI call, push function | 1 hr |
| Two Flask routes + background-job tracker | 45 min |
| `client_profile.html` sidebar item + tab block + plugin gate + small JS | 45 min |
| Test on submittal end-to-end + verify drift detection | 30 min |
| **Total** | **~3.5 hr** |

## Deployment

Standard SSH broadcast:
1. `rsync -av utils/wp_post_fetcher.py utils/freshness_analyzer.py root@165.22.44.109:'/root/FULLY WORKING WITH MULTIPLE SITE OPTIONS/utils/'`
2. Apply `dashboard.py` and `client_profile.html` diffs.
3. `ssh root@165.22.44.109 'bash -ic "cd … && ./start_with_openai.sh"'` to
   reload gunicorn so the new routes + module are picked up.
4. No plugin version bump required — the plugin side has been ready since
   1.16.0. This is a dashboard-only release.

## What this is NOT

- Not a plugin change. The plugin contract endpoint and metabox UI shipped
  in v1.16.0 and are stable.
- Not a scheduled background process. Manual button only in v1.
- Not multi-site (one site per run in v1).
- Not a polished cost dashboard. Just a feature that works end-to-end with
  reasonable defaults.
