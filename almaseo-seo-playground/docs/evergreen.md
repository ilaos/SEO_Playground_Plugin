# Evergreen Content Tracking

Monitor content freshness, track decay over time, and identify posts that need updating. AlmaSEO calculates a decay score for every post based on age, last update, and traffic trends.

---

## How It Works

AlmaSEO evaluates every published post on a daily schedule, calculating a decay score from 0 to 100. Posts are classified into freshness statuses:

| Status | Meaning |
|--------|---------|
| **Evergreen** | Content is fresh and performing well |
| **Watch** | Content is aging — may need attention soon |
| **Stale** | Content is old and likely losing value — refresh recommended |

---

## Decay Scoring

The decay score considers multiple factors:

- **Post age** — How long since the post was published
- **Last update** — How long since the post was last modified
- **Traffic trends** — Whether the post is gaining or losing search traffic (via Google Search Console data)
- **Content signals** — Date references, seasonal indicators, and freshness markers in the text

Higher decay scores mean the content is more stale and should be prioritized for a refresh.

---

## Google Search Console Integration

When your site is connected to AlmaSEO, the Evergreen module pulls click data from Google Search Console:

- **Last 90 days** vs **previous 90 days** — Compares recent traffic against historical traffic
- **Trend calculation** — Identifies whether traffic is growing, stable, or declining
- **Decline detection** — Posts with significant traffic drops get higher decay scores

This data powers both the Evergreen module and the Refresh Queue's traffic decline signal.

---

## Dashboard

The Evergreen dashboard (`AlmaSEO > Evergreen`) shows:

- **Score distribution** — How many posts are evergreen, watch, or stale
- **Top stale content** — Posts with the highest decay scores
- **Freshness timeline** — Visual overview of content age
- **Trend analysis** — 7-day and 30-day traffic windows

### Export

Export your evergreen data to CSV for external analysis or team review. The export includes post title, URL, decay score, status, last updated date, and traffic metrics.

---

## Post Editor Integration

On the post edit screen, the Evergreen module shows:

- Current decay score and freshness status
- Last update timestamp
- Traffic trend indicator (if GSC data available)
- Manual refresh trigger to recalculate the score

---

## Post List Column

The WordPress post list shows an Evergreen score column, letting you quickly scan which posts need attention without opening each one.

---

## Scheduled Scoring

A daily cron job recalculates decay scores for all published posts. This runs automatically — no manual action needed. Scores are stored as post meta (`_almaseo_evergreen_score`) for fast retrieval.

---

## Memory Management

The Evergreen module uses a safe loader pattern to avoid memory issues on large sites:

- Skips loading if less than 20MB of memory is available
- Loads components conditionally (only on relevant admin pages)
- Minimal footprint on frontend pages

---

## Tier

**Free** — Basic evergreen tracking is available to all users. Advanced filters and extended analytics are Pro features.

---

## Connection to Other Features

Evergreen data feeds into several other AlmaSEO features:

- **Refresh Queue** — Uses decay scores and traffic trends as scoring signals
- **Date Hygiene** — Complements evergreen (Evergreen = post-level age, Date Hygiene = passage-level staleness)
- **Health Score** — Content age is a factor in overall SEO health

---

## Summary

- Daily decay scoring for all published posts (0-100 scale)
- Three freshness statuses: evergreen, watch, stale
- Google Search Console traffic trend integration
- Dashboard with distribution charts, top stale content, and CSV export
- Post editor metabox and list column for quick access
- Memory-safe loader pattern for large sites
- Feeds into Refresh Queue and complements Date Hygiene
