# Schema Drift Monitor

Detect when the structured data on your pages changes unexpectedly — before it costs you rich results in search.

---

## What Is Structured Data?

Your pages contain hidden code called "structured data" (or schema markup) that tells Google what your content is about — things like article titles, author names, product prices, review ratings, and FAQ answers. When this code is correct, Google can show rich results in search: star ratings, FAQ dropdowns, recipe cards, product prices, and more.

These rich results can significantly increase your click-through rate. When structured data breaks or disappears, those rich results go away — and the extra traffic with them.

---

## The Problem This Solves

Plugin updates, theme changes, and settings tweaks can silently break or alter your structured data without you noticing. You might not realize something changed until you see a traffic drop weeks later.

Schema Drift Monitor catches these changes immediately by comparing your pages today against a saved snapshot from when everything was working correctly.

---

## How to Use It

### Step 1: Capture a Baseline

Click **Capture Baseline** when your structured data is correct and working as expected. This saves a snapshot of the schema markup on a sample of your pages — think of it as taking a "before" photo.

The monitor picks random pages from each post type you've configured (default: post, page, product) and stores the JSON-LD structured data it finds on each one.

### Step 2: Scan for Changes

After a plugin update, theme switch, or any time you want to check — click **Scan for Drift**. This loads those same pages again and compares the current structured data against the saved snapshot.

### Step 3: Review Findings

If anything changed, you'll see it listed in the table:

| Drift Type | Severity | What It Means |
|------------|----------|---------------|
| **Removed** | High | A schema type that existed before is now gone. You may lose a rich result. |
| **Modified** | Medium | A schema type still exists but its contents changed. Check if the change was intentional. |
| **Added** | Low | A new schema type appeared. Usually harmless, but worth verifying. |
| **Error** | Medium | The page returned an error or the schema couldn't be parsed. |

### Step 4: Fix and Resolve

Investigate high-severity findings, fix the underlying issue (e.g., revert a plugin update, restore a setting), then mark findings as resolved. Dismiss any that are intentional changes.

---

## Automatic Scanning

If **Auto-scan on update** is enabled in Settings, Schema Drift Monitor automatically checks for changes after every plugin or theme update. It waits 30 seconds after the update completes, then runs a scan. If drift is detected, findings appear in the table the next time you visit the page.

This means you don't need to remember to check manually — the monitor watches for you.

---

## Actions

Each finding has action buttons:

- **Resolve** — Mark as handled after you've investigated or fixed the issue
- **Dismiss** — Mark as an intentional change or not an issue
- **Reopen** — Move a resolved or dismissed finding back to open status

---

## Settings

Open the **Settings** panel below the table to configure:

### Auto-scan on Update

Default: **enabled**. Automatically scans for drift after plugin or theme updates. Requires a baseline to exist.

### Monitored Post Types

Default: **post, page, product**. Comma-separated list of post types to include when capturing baselines and scanning.

### Sample Size per Type

Default: **3**. How many random pages per post type are included in the baseline (1–50). Higher numbers give broader coverage but take longer to scan.

---

## What You Need

- **Pro tier** required
- **No external connections needed** — the monitor fetches your own pages locally
- No API keys, no Search Console. Just click the buttons.

---

## Important Notes

- **Capture a new baseline** after making intentional schema changes (adding a new schema plugin, changing schema settings, etc.) so the monitor knows your new setup is the correct one.
- The monitor checks your live front-end pages using HTTP requests, so it sees the same structured data that Google sees — including schema added by themes, plugins, or custom code.
- Resolved and dismissed findings are preserved when you re-scan. Only open findings are cleared and regenerated.

---

## Summary

- Saves a snapshot of your structured data when everything is working
- Detects when schemas are removed, modified, or unexpectedly added
- Auto-scans after plugin and theme updates (configurable)
- Works locally — no external APIs or connections needed
- Configurable post types and sample size
- Catch schema breakage before it costs you rich results
