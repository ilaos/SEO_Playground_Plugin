# Date Hygiene Scanner

Find stale years, outdated prices, and time-decay problems hiding in your published content. AlmaSEO scans every page, flags specific passages, and suggests fixes so you know exactly what to update.

---

## How It Works

AlmaSEO scans the title and body of every published post, page, and product using five detection methods:

1. **Stale Years** — Year references that are 2+ years behind the current year (e.g., "in 2022")
2. **Dated Phrases** — Explicit freshness claims with old dates (e.g., "as of January 2022", "last updated March 2023")
3. **Superlative Years** — Listicle-style year references (e.g., "Best SEO Tools for 2023", "Top 10 Picks of 2022")
4. **Price References** — Hardcoded prices that may be outdated (e.g., "$29/month", "€199")
5. **Regulation Mentions** — Named regulations paired with year references (e.g., "GDPR (2018)")

Each finding includes:
- The exact detected value
- Surrounding context (so you can find it in the post)
- A severity level (high, medium, or low)
- A suggested fix

---

## Severity Levels

| Severity | Meaning | Examples |
|----------|---------|----------|
| **High** | Visible in search results or makes an explicit freshness claim | Stale year in title, dated phrase anywhere |
| **Medium** | Could mislead readers but less visible | Stale year in body, price references |
| **Low** | Worth reviewing but less urgent | Regulation mentions with old year |

### Title vs Body

A stale year in the post title is always **high** severity because it shows in search results. The same year in body text is **medium** severity.

---

## Using the Scanner

### Scan Now

Click the **Scan Now** button to analyze all published content. The scanner:

1. Clears any previous findings
2. Processes every published post, page, and product in batches
3. Runs all five detection methods against each post's title and body
4. Stores findings with context snippets and suggested fixes

### Filtering

Use the dropdown filters above the table to narrow the view:

- **Status**: Show only open, resolved, or dismissed findings
- **Severity**: Show only high, medium, or low severity
- **Type**: Filter by finding type (stale year, dated phrase, etc.)

### Actions

Each finding has action buttons:

- **Resolve** — Mark this finding as fixed. Use after you've updated the content.
- **Dismiss** — Mark as a false positive or not an issue. The finding won't clutter your worklist.
- **Reopen** — Move a resolved or dismissed finding back to open status.
- **Edit Post** — Jump directly to the post editor to fix the issue.

---

## Scan Settings

Open the **Scan Settings** panel below the table to configure:

### Stale Year Threshold

Default: **2 years**. Years older than `(current year - threshold)` are flagged.

In 2026 with threshold 2: years 2024 and older are flagged. The current year (2026) and previous year (2025) are never flagged.

Increase to 3 or 4 if you want to be more lenient. Decrease to 1 to flag anything from last year.

### Scan for Price References

Toggle on/off. When enabled, the scanner flags hardcoded currency values ($, €, £, USD, EUR, GBP) for manual review.

### Scan for Regulation Mentions

Toggle on/off. When enabled, the scanner flags regulation names (GDPR, CCPA, HIPAA, etc.) when they appear near a year reference.

After changing settings, click **Save Settings**, then run a new scan to apply them.

---

## Distinct from Evergreen

Date Hygiene and Evergreen are complementary features:

| | Evergreen | Date Hygiene |
|---|-----------|-------------|
| **Question** | "Is this post getting old?" | "What specific facts in this post are stale?" |
| **Level** | Post-level | Passage-level |
| **Output** | Post status (evergreen/watch/stale) | Exact quotes with suggested replacements |
| **Data** | Traffic trends, publish date | Text analysis (regex patterns) |

Use Evergreen to decide *when* to refresh. Use Date Hygiene to identify *what* to fix.

---

## Dashboard Enrichment

The AlmaSEO dashboard can push NLP-detected findings for individual posts. These are more sophisticated than local regex patterns — for example, detecting factual claims that have become outdated even without explicit year references.

Dashboard-pushed findings are stored alongside locally-detected ones and appear in the same table.

---

## Finding Statuses

| Status | Meaning |
|--------|---------|
| **Open** | Needs review — this is an actionable finding |
| **Resolved** | User fixed it — the content has been updated |
| **Dismissed** | User marked it as a false positive or not an issue |

The summary cards show counts for **open** findings only, grouped by severity. This gives you a clear picture of how much work remains.

---

## Summary

- Every published post is scanned for stale years, dated phrases, price references, and more
- Findings show exact passages with context and suggested fixes
- High-severity findings (title years, freshness claims) appear first
- Resolve findings as you update content, dismiss false positives
- Configurable stale-year threshold and optional price/regulation scanning
- Works immediately with local regex patterns; gets smarter with dashboard NLP
- Complements Evergreen: Evergreen says *when*, Date Hygiene says *what*
