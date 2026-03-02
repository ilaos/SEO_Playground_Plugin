# Refresh Queue

Automatically prioritize which content to refresh next. AlmaSEO scores every published page across four signals and ranks them so your team always knows where to focus.

---

## How It Works

AlmaSEO evaluates every published post, page, and product using four signals:

1. **Business Value** — How important is this page to your business? Product pages and money pages score higher than informational blog posts.
2. **Traffic Decline** — Is this page losing search traffic? Pages with significant drops get higher priority.
3. **Conversion Intent** — Does this page serve a conversion goal? Pricing pages, product pages, and contact pages score higher.
4. **Opportunity Size** — How much room for improvement does this page have? Pages with low SEO health scores or stale content get boosted.

Each signal produces a score from 0 to 100. These are combined into a single **priority score** using configurable weights, then classified into tiers:

| Tier | Score | Meaning |
|------|-------|---------|
| **High** | 70–100 | Refresh immediately — highest impact |
| **Medium** | 50–69 | Refresh soon — solid candidates |
| **Low** | 0–49 | Monitor — not urgent |

---

## Signals in Detail

### Business Value

Measures how commercially important a page is.

**Local calculation**: Based on post type (products score higher than blog posts) and schema type (Product, FAQ, LocalBusiness pages get a bonus).

**Dashboard enrichment**: The AlmaSEO dashboard can push a more accurate business value score based on revenue data, conversion tracking, or manual classification.

### Traffic Decline

Measures whether the page is losing search visibility.

**Local calculation**: Uses Google Search Console click data from the Evergreen module — compares the last 90 days against the previous 90 days. A 50%+ decline scores 100 (maximum urgency).

**Dashboard enrichment**: The dashboard can push a pre-computed traffic decline score with additional data sources.

### Conversion Intent

Measures how conversion-oriented the page is.

**Local calculation**: Based on post type and URL path. Product pages and URLs containing words like "pricing", "buy", "checkout", or "contact" score higher.

**Dashboard enrichment**: The dashboard can push keyword intent classifications from its analysis tools.

### Opportunity Size

Measures how much potential improvement the page has.

**Local calculation**: Inverts the SEO health score (low health = high opportunity), adds bonuses for stale or watch-status evergreen content and older pages.

**Dashboard enrichment**: The dashboard can push estimated traffic potential based on keyword gaps and ranking data.

---

## Using the Queue

### Recalculate

Click the **Recalculate** button to score all published content. This runs through every post, page, and product on your site, calculates all four signals, and updates the queue.

Recalculation preserves any posts you've already skipped — they won't be moved back to "queued" status.

### Filtering

Use the dropdown filters above the table to narrow the view:

- **Status**: Show only queued, skipped, or refreshed posts
- **Priority**: Show only high, medium, or low priority posts

### Actions

Each row in the queue has action buttons:

- **Skip** — Remove this post from the active queue. Use this for pages you don't want to refresh (e.g., an "About Us" page that rarely changes). Skipped posts stay skipped during recalculation.
- **Restore** — Move a skipped post back into the queue.
- **Review Draft** — If a content refresh draft exists for this post, click through to review and apply it.

---

## Adjusting Weights

The priority score is a weighted combination of the four signals. Default weights:

| Signal | Default Weight |
|--------|---------------|
| Business Value | 25% |
| Traffic Decline | 30% |
| Conversion Intent | 20% |
| Opportunity Size | 25% |

To change these, open the **Signal Weights** panel below the table. Adjust the numbers and click **Save Weights**. Weights are normalized automatically — they don't need to add up to exactly 100.

**When to adjust**:
- If your site is e-commerce focused, increase Business Value and Conversion Intent
- If you're focused on recovering lost traffic, increase Traffic Decline
- If you want to improve underperforming pages, increase Opportunity Size

After changing weights, click **Recalculate** to re-rank the queue with the new weights.

---

## Dashboard Enrichment

The AlmaSEO dashboard can push more accurate signal scores for individual posts. When dashboard data is available, it overrides the local calculation for that signal.

This means the queue starts useful immediately (using local data), and gets more accurate over time as the dashboard analyzes your content.

Dashboard-pushed signals are stored as post meta and persist across recalculations.

---

## Connection to Content Refresh

The Refresh Queue and Content Refresh work together:

1. **Queue** identifies *which* posts to refresh (this feature)
2. **Content Refresh** handles *how* to refresh them (AI-generated drafts with section-level review)

When a refresh draft exists for a queued post, the queue shows a "Review Draft" link for quick access.

---

## Summary

- Every published post is scored across four signals: business value, traffic decline, conversion intent, opportunity size
- Posts are ranked by a configurable weighted priority score
- High-priority posts appear at the top — refresh these first
- Skip posts you don't want to refresh; they stay skipped across recalculations
- Signal weights are adjustable to match your business priorities
- Works immediately with local data; gets smarter with dashboard enrichment
- Feeds naturally into Content Refresh for AI-assisted rewrites
