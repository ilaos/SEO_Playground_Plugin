# E-E-A-T Enforcement

Scan your content for missing trust signals that Google uses to evaluate quality: author attribution, biographical info, credentials, citation sources, and review attribution.

---

## What Is E-E-A-T?

E-E-A-T stands for **Experience, Expertise, Authoritativeness, and Trustworthiness**. It's a set of criteria Google's quality raters use to evaluate content. Pages that demonstrate strong E-E-A-T tend to rank higher, especially in sensitive topics like health, finance, and legal advice (called "YMYL" — Your Money or Your Life).

The E-E-A-T Enforcement scanner checks your published content for common trust signal gaps that can quietly hurt your rankings.

---

## How It Works

Click the **Scan Now** button and the scanner checks every published post, page, and product for six types of trust signal problems:

### 1. Missing Author

Posts attributed to generic usernames like "admin", "editor", or "webmaster" instead of a real person. Search engines and readers alike trust content from named, identifiable authors.

### 2. Missing Bio

The post's author has no biographical description in their WordPress profile. Author bios help establish expertise and build reader trust.

### 3. Missing Author Schema

The post doesn't include structured data (schema markup) identifying the author. Author schema helps Google connect your content to a known person, which can improve how your pages appear in search results.

### 4. Missing Credentials

The author's bio doesn't mention any professional credentials, qualifications, or experience indicators. This matters most for YMYL content where readers need to know the author is qualified.

### 5. No Citation Sources

The post contains zero outbound links to external sources. Linking to authoritative references strengthens your content's credibility and helps Google verify the information you're presenting.

### 6. Missing Review Attribution

The post doesn't include any "reviewed by" or "fact-checked by" attribution. This check is **off by default** and can be enabled in settings — it's most relevant for YMYL content like medical or financial advice.

---

## What You Need

- **Pro tier** required
- **No external connections needed** — the scanner reads your WordPress database directly
- Click "Scan Now" to run. No API keys, no Search Console connection.

The AlmaSEO dashboard can optionally push more sophisticated NLP-detected findings, but the local scanner works completely on its own.

---

## Severity Levels

| Severity | Meaning | Examples |
|----------|---------|----------|
| **High** | Strong negative trust signal | Missing author, missing bio (YMYL category) |
| **Medium** | Moderate trust gap | Missing bio (non-YMYL), missing credentials |
| **Low** | Worth reviewing | No citation sources, missing review date |

Posts in YMYL categories (configurable in settings) receive higher severity ratings for the same findings.

---

## Actions

Each finding has action buttons:

- **Resolve** — Mark as fixed after you've updated the content. Stays resolved across future scans.
- **Dismiss** — Mark as a false positive or intentionally ignored. Stays dismissed across future scans.
- **Reopen** — Move a resolved or dismissed finding back to open status.
- **Edit Post** — Jump directly to the post editor to fix the issue.

---

## Scan Settings

Open the **Scan Settings** panel to configure:

### Post Types to Scan

Default: **post, page, product**. Only these post types are scanned. Add custom post types as a comma-separated list if needed. This prevents Elementor templates, WooCommerce orders, and other non-content types from cluttering your results.

### Generic Usernames

Default: **admin, editor, webmaster**. Posts by authors with these usernames are flagged as "missing author." Add any other generic usernames your site uses.

### Check for Citation Sources

Toggle on/off. When enabled, posts with zero outbound links are flagged.

### Check for Review Attribution

Toggle on/off. When enabled, posts without "reviewed by" or "fact-checked by" text are flagged. Only applies to posts in YMYL categories.

### YMYL Categories

Comma-separated category slugs (e.g., "health, finance, legal"). Posts in these categories receive stricter checks and higher severity ratings.

### Health Score Weight

Default: **0** (disabled). Set to 1–20 to include E-E-A-T as a signal in your per-post health score. Other health signal weights auto-adjust to accommodate.

---

## Finding Statuses

| Status | Meaning |
|--------|---------|
| **Open** | Needs review — this is an actionable finding |
| **Resolved** | User fixed it — the content has been updated |
| **Dismissed** | User marked it as not an issue |

Resolved and dismissed findings are preserved when you re-scan. Only open findings are cleared and regenerated.

---

## Summary

- Scans all published content for 6 types of trust signal gaps
- Works locally with no external connections — just click Scan Now
- Findings include specific details, severity, and suggestions
- YMYL categories get stricter checks
- Configurable post types prevent irrelevant content from being scanned
- Resolved and dismissed findings survive re-scans
- Optional health score integration
