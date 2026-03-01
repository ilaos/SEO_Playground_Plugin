# AlmaSEO Security - Remaining Items

These items were identified during the security audit but deferred because they require architectural decisions or are low-risk edge cases.

## Medium Priority

- [ ] **M11 - Encrypt stored application password**
  - File: `almaseo-seo-playground.php`
  - Currently `almaseo_app_password` is stored as plain text in `wp_options`
  - Needs: encryption/decryption helper using `wp_salt()` or similar, migration for existing installs

- [ ] **M16 - Cap 404 log table growth**
  - File: `includes/404/404-capture.php`
  - The `almaseo_404_logs` table can grow unbounded on high-traffic sites
  - Needs: scheduled cron job to prune old entries (e.g., keep last 90 days or max 10,000 rows)

- [ ] **M22 - Detect redirect chains and multi-hop loops**
  - File: `includes/redirects/redirects-runtime.php`
  - Current loop detection only checks direct A→A loops, not A→B→A chains
  - Needs: walk the redirect table on save to detect chains up to N hops deep

## Low Priority

- [ ] **M4 - Rate limiter race condition**
  - File: `includes/security.php` (`almaseo_check_rate_limit()`)
  - Transient-based counter has a small TOCTOU race window under high concurrency
  - Acceptable for current scale; would need atomic increment (e.g., Redis or custom DB table) to fully fix

- [ ] **M12 - Content length check inaccuracy**
  - File: `admin/pages/overview.php`
  - `strlen(get_the_content())` includes HTML tags, shortcodes, and blocks in the character count
  - Could use `wp_strip_all_tags(strip_shortcodes(get_the_content()))` for a more accurate word/character count

- [ ] **M19 - Robots.txt directive validation**
  - File: `includes/robots/robots-ajax.php`
  - No validation that user-entered directives follow valid robots.txt syntax
  - Could add a UI warning or server-side lint for common mistakes (e.g., missing `/` in paths)

- [ ] **M28 - Schema types beyond Article not rendered**
  - File: `includes/schema-clean.php`
  - Only `Article` / `BlogPosting` schema types produce output; other types (Product, Recipe, Event, etc.) are selectable but have no renderer
  - Needs: individual render functions per schema type, or remove unsupported types from the dropdown

- [ ] **M29 - Unescaped `$extra_style` in health UI**
  - File: `includes/health/ui.php`
  - `$extra_style` is interpolated into an HTML `style` attribute without `esc_attr()`
  - Low risk because the value is internally generated, not user-supplied
  - Fix: wrap with `esc_attr()` for defense-in-depth

- [ ] **M30 - Multisite robots.txt overwrite risk**
  - File: `includes/robots/robots-ajax.php`
  - On multisite, saving robots.txt could overwrite rules set by the network admin
  - Needs: multisite-aware check (`is_multisite()`) and either block edits or scope to subsite
