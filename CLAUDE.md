# AlmaSEO SEO Playground Plugin

## Project Overview
- **Plugin Version:** 6.5.0
- **Plugin Source:** `almaseo-seo-playground/` (root of this repo)
- **Main Plugin File:** `almaseo-seo-playground/almaseo-seo-playground.php`

## Live Testing Workflow
A symlink connects the plugin source directly to the Local by Flywheel WordPress site:

- **Source:** `C:\Users\ishla\Desktop\SEO ACTUAL PLAYGROUND PLUGIN\SEO_Playground_Plugin\almaseo-seo-playground\`
- **WP Plugins Dir:** `C:\Users\ishla\Local Sites\my-playground\app\public\wp-content\plugins\almaseo-seo-playground` (symlink)

**Every file edit is instantly live.** The user just refreshes their browser to see changes — no zip, upload, or reinstall needed.

## Local WordPress Site
- **Platform:** Local by Flywheel
- **Site Path:** `C:\Users\ishla\Local Sites\my-playground\app\public\`
- **WP Admin:** Accessible via Local app

## Key Files

| Purpose | File |
|---------|------|
| Main plugin file | `almaseo-seo-playground.php` |
| Page optimization panel (7 tabs) | `almaseo-seo-playground.php` (lines 3500+) |
| Health score calculation | `includes/health/analyzer.php` |
| Sitemap functionality | `includes/sitemap/` |
| Evergreen content | `includes/evergreen/` |
| Admin settings | `includes/admin/settings.php` |
| Security items to address | `TODO-SECURITY.md` |

## Tab Order (Page Optimization Panel)

1. SEO Page Health (default) - meta title, description, focus keyword, SERP preview
2. Search Console
3. Schema & Meta
4. AI Tools
5. LLM Optimization
6. Notes & History
7. Unlock AI Features (conditional - only when not connected)
