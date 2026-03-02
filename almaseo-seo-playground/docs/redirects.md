# Redirect Manager

Create and manage 301/302 HTTP redirects with hit tracking, loop detection, and bulk operations.

---

## How It Works

When a visitor requests a URL that matches a redirect source, AlmaSEO intercepts the request and sends the appropriate HTTP redirect (301 permanent or 302 temporary) to the target URL. Hits are tracked asynchronously so redirects execute instantly.

---

## Creating Redirects

### Manual Creation

1. Go to **AlmaSEO > Redirects**
2. Click **Add Redirect**
3. Enter the source path (must start with `/`)
4. Enter the target URL or path
5. Choose redirect type (301 or 302)
6. Click **Save**

### From 404 Logs

When reviewing 404 errors, click **Create Redirect** on any entry to pre-fill a redirect from that broken URL.

### Validation

AlmaSEO validates every redirect before saving:

- Source must start with `/`
- Source must be unique (no duplicate sources)
- Target must be a valid URL or path
- Loop detection prevents source === target

---

## Redirect Types

| Type | Use Case |
|------|----------|
| **301 (Permanent)** | Page moved permanently. Search engines transfer link equity to the new URL. Use for most cases. |
| **302 (Temporary)** | Page temporarily at a different location. Search engines keep indexing the original URL. Use for A/B tests or maintenance. |

---

## Hit Tracking

Every time a redirect fires, AlmaSEO records a hit. The redirects table shows:

- **Hits** — Total number of times the redirect was triggered
- **Last Hit** — When the redirect was last used

Hit recording is asynchronous (via WordPress cron) to avoid slowing down the redirect response.

---

## Performance

Redirects are cached in a WordPress transient with a 1-hour TTL. This means:

- First request after cache expiry hits the database
- Subsequent requests use the cached redirect map
- Adding or editing a redirect clears the cache immediately

Redirect execution hooks into `template_redirect` at priority 1 — before WordPress loads any template files.

---

## Managing Redirects

### Filtering & Search

Use the search box to filter redirects by source or target URL.

### Toggle Enable/Disable

Click the toggle button to temporarily disable a redirect without deleting it. Disabled redirects are saved but not executed.

### Bulk Operations

Select multiple redirects and use bulk actions:

- **Enable** — Activate selected redirects
- **Disable** — Deactivate selected redirects
- **Delete** — Permanently remove selected redirects

### Testing

Use the **Test** feature to verify a redirect works correctly before saving. This checks the source path, validates the target, and confirms no loop exists.

---

## Tier

**Pro only** — Redirect Manager requires a Pro or Agency license.

---

## Summary

- Create 301/302 redirects with source path and target URL
- Automatic loop detection and validation
- Hit tracking with async recording for zero performance impact
- Cached redirect map for fast execution
- Bulk enable/disable/delete operations
- Direct integration with 404 logs for one-click redirect creation
