# SEO Health Score

Real-time content quality assessment with 10 weighted signals. Every post gets a 0-100 health score that identifies exactly what to improve for better search performance.

---

## How It Works

When you edit a post, AlmaSEO analyzes the content against 10 SEO signals. Each signal earns points toward a total score of 100. The score updates live as you write — no need to save and reload.

---

## Signals & Weights

| Signal | Points | What It Checks |
|--------|--------|---------------|
| **Title** | 20 | Meta title is set, contains focus keyword, within character limits |
| **Meta Description** | 15 | Description is set, contains focus keyword, within character limits |
| **H1 Heading** | 10 | Page has exactly one H1, contains focus keyword |
| **Keyword in Introduction** | 10 | Focus keyword appears in the first paragraph |
| **Internal Link** | 10 | Content contains at least one internal link |
| **Outbound Link** | 10 | Content contains at least one external link |
| **Image Alt Text** | 10 | Images have descriptive alt attributes |
| **Readability** | 10 | Average sentence length ≤ 24 words or average paragraph ≤ 150 words |
| **Canonical URL** | 3 | Canonical URL is properly configured |
| **Robots Settings** | 2 | No conflicting robots directives |

---

## Score Ranges

| Score | Rating | Meaning |
|-------|--------|---------|
| 80-100 | Excellent | Well-optimized content |
| 60-79 | Good | Minor improvements possible |
| 40-59 | Fair | Several signals need attention |
| 0-39 | Poor | Significant optimization needed |

---

## Focus Keyword

The health score is most useful when a focus keyword is set. If no focus keyword is specified, AlmaSEO auto-derives one from the post title.

The focus keyword is checked in:
- Meta title (keyword presence)
- Meta description (keyword presence)
- H1 heading (keyword presence)
- First paragraph (keyword presence)

---

## Live Updates

The health score recalculates in real time as you edit:

- Change the title → score updates
- Add a meta description → score updates
- Insert an internal link → score updates

No page refresh needed. The score breakdown panel shows exactly which signals pass and which need work.

---

## SERP Preview

Alongside the health score, AlmaSEO shows a Google SERP preview — how your post will appear in search results with the current title and description. This helps you craft compelling titles within the character limits.

---

## Auto-Draft Meta Description

Click the auto-draft button to generate a meta description from your post content. AlmaSEO extracts the most relevant excerpt and formats it within the recommended 160-character limit.

---

## Keyword Suggestions

When connected to AlmaSEO, you can fetch keyword suggestions based on your content. These help you choose a focus keyword that aligns with what people actually search for.

---

## Elementor Support

AlmaSEO detects Elementor-built content and renders it through the `the_content` filter before analysis, so health scores are accurate even for page-builder content.

---

## Tier

**Free** — Health score is available to all users. It's the default tab in the post editor optimization panel.

---

## Summary

- 10 weighted signals totaling 100 points
- Real-time score updates as you edit
- Focus keyword auto-derivation from title
- SERP preview for title/description optimization
- Auto-draft meta description
- Elementor content rendering support
- Post meta storage for fast retrieval across the site
