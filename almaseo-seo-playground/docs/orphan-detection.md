# Orphan Page Detection

Find pages on your site that no other page links to — pages that are essentially invisible to search engines and visitors browsing your site.

---

## What Is an Orphan Page?

An orphan page is a published page that has **zero internal links pointing to it** from other pages on your site. Even though the page exists and might appear in your sitemap, search engines primarily discover and understand your content by following links between your pages.

If no other page links to a page, search engines may:

- Take longer to discover and index it
- Assign it less importance (lower PageRank)
- Rank it lower than it deserves

Visitors who browse your site through navigation and links will also never find orphan pages.

---

## How It Works

Click **Scan for Orphans** and the scanner:

1. Gets a list of all published posts, pages, and products
2. For each page, counts how many other pages on your site link to it (inbound links)
3. Also counts how many links each page sends to other pages (outbound links)
4. Classifies each page based on its inbound link count

### Classification

| Status | Inbound Links | What It Means |
|--------|---------------|---------------|
| **Orphan** | 0 | No other page links here. Invisible to site crawlers and visitors. |
| **Weak** | 1–2 | Only a few pages link here. May be undervalued by search engines. |
| **Addressed** | 3+ or manually marked | Enough links for proper discovery and ranking. |

---

## Cluster Analysis

Beyond individual orphan detection, the scanner groups pages by their categories and tags to analyze how well-connected each topic cluster is.

- **Cluster strength** — how densely pages within the same category link to each other
- **Hub candidates** — pages that could serve as cornerstone content for a topic if they received more internal links

This helps you spot structural weaknesses in your site's content architecture.

---

## What You Need

- **Pro tier** required
- **No external connections needed** — the scanner analyzes your own content locally
- Found under **SEO Playground → Orphan Pages** (part of the Internal Links module)

The AlmaSEO dashboard can optionally push additional orphan detection data, but the local scanner works independently.

---

## Actions

Each finding has action buttons:

- **Dismiss** — Mark as not an issue (e.g., a standalone landing page that intentionally has no internal links)
- **Edit Post** — Jump to the post to add internal links manually

### Auto-Generated Suggestions

When an orphan is detected, the scanner can suggest internal link rules to fix it. These suggestions use the existing Internal Links module — you can review and activate them to automatically link relevant keywords on other pages to the orphan page.

---

## Using with Internal Links

Orphan Detection is an extension of the Internal Links module. The recommended workflow:

1. **Scan for orphans** to find pages with no inbound links
2. **Review the orphan list** and dismiss any intentional orphans (landing pages, thank-you pages, etc.)
3. **Create internal link rules** for remaining orphans — either manually or using the auto-generated suggestions
4. **Re-scan** periodically to verify orphans are being addressed

---

## Summary

- Finds pages with zero internal links pointing to them
- Classifies pages as orphan (0 links), weak (1–2 links), or addressed (3+)
- Cluster analysis identifies poorly connected topic groups
- Works locally — no external connections needed
- Integrates with Internal Links module for one-click fixes
- Scan periodically to maintain a healthy site structure
