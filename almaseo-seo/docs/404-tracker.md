# 404 Intelligence

Capture, analyze, and manage 404 errors on your WordPress site. Smart suggestions help you find the right redirect target, spike detection alerts you to sudden breakage, and impact scoring prioritizes which 404s are actually losing traffic.

---

## How It Works

When a visitor lands on a page that doesn't exist, AlmaSEO logs the request with its referrer, user agent, and timestamp. Identical paths are grouped together with a hit counter, so you see a single entry per broken URL with the total number of times it was hit.

---

## What Gets Captured

For each 404 error, AlmaSEO records:

| Field | Description |
|-------|-------------|
| **Path** | The requested URL path (e.g., `/old-page/`) |
| **Query** | Any query string parameters |
| **Referrer** | Where the visitor came from (the linking page) |
| **User Agent** | The visitor's browser or bot identifier |
| **Hits** | Total number of requests for this path |
| **First Seen** | When this 404 was first detected |
| **Last Seen** | Most recent occurrence |

### Automatic Filtering

Not every 404 is worth tracking. AlmaSEO automatically ignores:

- **Static assets** — CSS, JS, images (png, jpg, gif, svg, ico, woff, etc.)
- **Common bot probes** — `/wp-login.php`, `/xmlrpc.php`, `/favicon.ico`, `/robots.txt`
- **WordPress internals** — Login pages, admin paths, feed URLs

This keeps your 404 log focused on genuine broken links that affect real visitors.

### GDPR-Safe IP Handling

IP tracking is **disabled by default**. When enabled, IPs are stored in packed binary format for privacy. No plaintext IPs are logged.

---

## Using the 404 Log

### Dashboard

The 404 log page shows:

- **Summary stats** — Total 404s (last 7 days), unique paths, today's count, ignored entries
- **Top referrer** — The domain sending the most 404 traffic
- **Full log table** — Sortable, searchable, paginated

### Filtering

- **Search** — Filter by URL path
- **Date range** — Show 404s from a specific time period
- **Status** — Show active or ignored entries

### Actions

Each 404 entry has action buttons:

- **Ignore** — Hide this entry from the active log (useful for known non-issues)
- **Unignore** — Restore an ignored entry
- **Delete** — Permanently remove the log entry
- **Create Redirect** — One-click redirect creation from this 404 path

### Bulk Operations

Select multiple entries and:

- **Bulk Ignore** — Hide multiple entries at once
- **Bulk Unignore** — Restore multiple entries
- **Bulk Delete** — Remove multiple entries

---

## 404-to-Redirect Workflow

The most powerful use of 404 Intelligence is converting broken URLs into redirects:

1. Review your 404 log and identify high-hit broken URLs
2. Click **Create Redirect** on a 404 entry
3. The redirect form opens with the source path pre-filled
4. Enter the correct target URL (or use a smart suggestion — see below)
5. Save — visitors hitting that broken URL will now be redirected

---

## Smart Redirect Suggestions (v7.6.0)

When you view a 404 entry, AlmaSEO can suggest the best page to redirect it to. Suggestions come from two sources:

### Local Analysis

The plugin automatically analyzes the 404 path and tries to find a matching page:

1. **Slug similarity** — compares the 404 URL's path segments against your published post slugs. For example, if `/best-seo-tools-2024/` is a 404, it checks if you have a post with a similar slug like `best-seo-tools`.
2. **Title keyword matching** — extracts keywords from the 404 path and matches them against your post titles. More keyword matches = higher confidence.

Suggestions include a confidence score and a reason explaining why the match was suggested.

### Dashboard Suggestions

The AlmaSEO dashboard can push more sophisticated NLP-based suggestions that consider content similarity beyond just URL patterns. These appear alongside local suggestions when available.

---

## Spike Detection (v7.6.0)

AlmaSEO watches for sudden increases in 404 hits that could indicate a broken link on a high-traffic page or a recent site change that broke multiple URLs.

A spike is flagged when:
- A 404 path's hits in the last 24 hours exceed **3x its 7-day daily average**

Spikes appear prominently in the 404 Intelligence view so you can investigate and fix the broken link before it affects more visitors.

---

## Impact Scoring (v7.6.0)

Not all 404s are equal. A 404 on a page that gets thousands of search impressions is far more important to fix than one that nobody visits.

When connected to the AlmaSEO dashboard, each 404 can receive:

- **Impact score** (0–100) — an overall importance rating from the dashboard
- **Impressions** — how many times the 404 URL appeared in Google search results
- **Clicks** — how many people clicked the 404 URL from Google

This lets you prioritize your redirect work: fix the high-impact 404s first.

**Note:** Impact data requires your site to be connected to the AlmaSEO dashboard with Google Search Console linked. Without this, local features (smart suggestions, spike detection) still work.

---

## Redirect Chain Detection (v7.6.0)

Over time, redirects can chain together: page A redirects to page B, which redirects to page C. Each hop slows the redirect and can lose link equity.

AlmaSEO scans your existing redirects for chains and suggests consolidation — for example, redirecting A directly to C instead of going through B. Chains up to 10 hops deep are detected.

Access chain detection from the Redirects module, which works together with 404 Intelligence.

---

## Tier

**Hybrid** — Basic 404 log viewing is available to all users. Advanced analytics, bulk operations, and filtering require Pro.

- **Free**: View 404 log entries
- **Pro**: Advanced filtering, bulk actions, referrer analysis, date range filtering, smart suggestions, spike detection, impact scoring

---

## Summary

- Automatically captures 404 errors with referrer, user agent, and hit counts
- Groups identical paths with running hit counter
- Filters out static assets and bot probes automatically
- GDPR-safe IP handling (disabled by default)
- One-click redirect creation from any 404 entry
- Bulk ignore/delete operations for log management
- Summary statistics with top referrer tracking
- **Smart redirect suggestions** — local slug/title matching plus dashboard NLP
- **Spike detection** — alerts when 404 hits surge above 3x the 7-day average
- **Impact scoring** — prioritize 404s by search impressions and clicks (requires dashboard)
- **Redirect chain detection** — find and consolidate A→B→C redirect chains
