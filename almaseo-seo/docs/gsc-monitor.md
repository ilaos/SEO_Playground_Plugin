# GSC Monitor

Track indexation drift, rich result changes, and snippet rewrites detected from your Google Search Console data.

---

## What It Does

Google can silently drop pages from its index, remove your rich results (star ratings, FAQ boxes, etc.), or rewrite your titles and descriptions in search results. These changes happen on Google's side and are invisible to on-page analysis tools.

GSC Monitor watches for these external visibility changes and alerts you when something shifts, so you can investigate and respond before it impacts your traffic.

---

## Connection Required

GSC Monitor does **not** scan your site locally. All data comes from the AlmaSEO dashboard, which analyzes your Google Search Console data and pushes findings to your WordPress site automatically.

**For findings to appear, you need:**

1. Your site connected to the AlmaSEO dashboard (app password set up)
2. Your Google Search Console property linked in the AlmaSEO dashboard
3. The dashboard's GSC analysis to have run and detected changes

Without this connection, the GSC Monitor page will be empty. You can connect your site from **SEO Playground → Settings**.

---

## Finding Types

GSC Monitor organizes findings into three tabs:

### Indexation Tab

Detects changes in how Google indexes your pages.

| Subtype | Meaning |
|---------|---------|
| **Not Indexed** | A page that was indexed is no longer in Google's index |
| **Excluded Spike** | An unusual increase in pages being excluded from indexing |
| **Coverage Drop** | A significant decrease in the number of indexed pages |

### Rich Results Tab

Detects changes to structured data features in search results.

| Subtype | Meaning |
|---------|---------|
| **Lost** | A rich result (stars, FAQ, recipe card, etc.) that was showing is no longer appearing |
| **Gained** | A new rich result appeared (usually good news) |
| **Degraded** | A rich result is still showing but with fewer features than before |

### Snippets Tab

Detects when Google rewrites your titles or descriptions in search results.

| Subtype | Meaning |
|---------|---------|
| **Title Rewrite** | Google is showing a different title than the one you set |
| **Description Rewrite** | Google is showing a different meta description than yours |

---

## Actions

Each finding has action buttons:

- **Resolve** — Mark as handled after you've investigated or fixed the issue
- **Dismiss** — Mark as not actionable (e.g., a page you intentionally removed)
- **Reopen** — Move a resolved or dismissed finding back to open status

### Bulk Actions

Select multiple findings using the checkboxes and use the bulk action dropdown to resolve or dismiss them all at once.

---

## Monitor Settings

Open the **Monitor Settings** panel to configure:

### Indexation Alert Threshold

Default: **5**. Minimum number of pages affected before an indexation finding is created. Prevents noise from single-page fluctuations.

### Snippet Alert Threshold

Default: **100**. Minimum impressions a page needs before snippet rewrites are flagged. Low-traffic pages often have unstable snippets that aren't worth investigating.

### Auto-dismiss After (Days)

Default: **0** (disabled). Open findings older than this number of days are automatically dismissed. Useful for keeping the worklist focused on recent issues.

---

## Deduplication

When the dashboard pushes a finding that matches an existing open finding (same URL, type, and subtype), the existing finding is updated instead of creating a duplicate. This means the "Last Seen" date always reflects the most recent detection.

If a previously resolved or dismissed finding recurs, a new finding is created so you're alerted again.

---

## Summary

- Monitors Google Search Console for indexation drops, rich result losses, and snippet rewrites
- All data comes from the AlmaSEO dashboard — no local scanning
- Requires site connection to AlmaSEO + linked Search Console property
- Organized into three tabs for easy triage
- Bulk actions for efficient worklist management
- Configurable alert thresholds to reduce noise
- Smart deduplication prevents duplicate findings
