# 404 Error Tracker

Capture, analyze, and manage 404 errors on your WordPress site. Identify broken links, track referrers, and convert frequent 404s into redirects.

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

The most powerful use of the 404 tracker is converting broken URLs into redirects:

1. Review your 404 log and identify high-hit broken URLs
2. Click **Create Redirect** on a 404 entry
3. The redirect form opens with the source path pre-filled
4. Enter the correct target URL
5. Save — visitors hitting that broken URL will now be redirected

---

## Tier

**Hybrid** — Basic 404 log viewing is available to all users. Advanced analytics, bulk operations, and filtering require Pro.

- **Free**: View 404 log entries
- **Pro**: Advanced filtering, bulk actions, referrer analysis, date range filtering

---

## Summary

- Automatically captures 404 errors with referrer, user agent, and hit counts
- Groups identical paths with running hit counter
- Filters out static assets and bot probes automatically
- GDPR-safe IP handling (disabled by default)
- One-click redirect creation from any 404 entry
- Bulk ignore/delete operations for log management
- Summary statistics with top referrer tracking
