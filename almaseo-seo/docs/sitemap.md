# Sitemap Management

Multi-format sitemap generation with XML, News, Image, Video, and Hreflang support. Performance-optimized with static file caching and search engine submission.

---

## How It Works

AlmaSEO generates XML sitemaps that tell search engines which pages to crawl. Sitemaps are built from multiple providers (posts, pages, taxonomies, etc.) and served as an index file that links to individual sitemap files.

---

## Sitemap Types

| Type | Purpose |
|------|---------|
| **XML Sitemap** | Standard sitemap listing all pages with last-modified dates |
| **News Sitemap** | Recent articles for Google News (configurable time window) |
| **Image Sitemap** | Images with captions and alt text for image search |
| **Video Sitemap** | Embedded videos for video search |
| **Hreflang** | Language/region variants for multilingual sites |
| **Delta Sitemap** | Recently changed URLs for rapid indexing |

---

## Providers

Sitemaps are assembled from multiple content providers:

| Provider | Content |
|----------|---------|
| **Posts** | Published blog posts |
| **Pages** | Published static pages |
| **Custom Post Types** | Any registered public CPT |
| **Taxonomies** | Category, tag, and custom taxonomy archives |
| **Users** | Author archive pages |
| **Images** | Image attachments with metadata |
| **Videos** | Embedded video content |
| **News** | Recent posts for Google News |
| **Delta** | URLs changed in the last N days |

---

## Performance

### Storage Modes

| Mode | How It Works | Best For |
|------|-------------|----------|
| **Static** (default) | Writes sitemap files to disk, serves directly via web server | Production sites — fastest |
| **Dynamic** | Generates sitemap on each request via PHP | Development or restricted file systems |

### Optimization

- **Gzip compression** — Reduces file size for faster delivery
- **Chunked building** — Large sitemaps built in configurable chunks
- **Build lock** — Prevents concurrent builds from conflicting
- **Links per sitemap** — Maximum 1,000 URLs per file (configurable)

---

## Delta Sitemap

The delta sitemap tracks recently changed content for rapid indexing:

- **Retention** — Configurable number of days (default: 14)
- **Max URLs** — Configurable limit (default: 500)
- **Automatic** — Updates when posts are published or modified

Search engines can poll the delta sitemap frequently to discover new and updated content.

---

## Search Engine Submission

### IndexNow

AlmaSEO supports IndexNow for instant notification to Bing and Yandex when content changes:

- **API key** — Enter your IndexNow key in settings
- **Automatic submission** — Notify search engines on publish/update
- **Submission log** — Track which URLs were submitted and when

---

## News Sitemap

For news publishers:

- **Time window** — Include articles from the last N hours (default: 48)
- **Max items** — Limit the number of articles
- **Language** — Set the publication language
- **Publisher name** — Your news organization name

Google News requires articles to be less than 2 days old to appear in the news sitemap.

---

## Hreflang Support

For multilingual sites, AlmaSEO can generate hreflang tags that tell search engines which language version to show:

- Configure language/region pairs
- Link translated versions of the same content
- Supports `x-default` for fallback language

---

## Admin Page

Go to **AlmaSEO > Sitemaps** to manage your sitemaps. The settings page has multiple tabs:

1. **Overview** — Build status, last update, URL counts
2. **Types & Rules** — Which post types and taxonomies to include
3. **Health Scan** — Content health analysis across sitemap entries
4. **Changes** — Delta sitemap recent changes
5. **Media** — Image and video sitemap settings
6. **News** — News sitemap configuration
7. **International** — Hreflang settings
8. **Updates I/O** — IndexNow submission status and history

---

## Conflict Detection

AlmaSEO detects other SEO plugins that may generate competing sitemaps and warns you to avoid conflicts.

---

## Tier

**Free** — Basic XML sitemaps are available to all users.

---

## Summary

- Multi-format sitemaps: XML, News, Image, Video, Hreflang, Delta
- 9 content providers covering all WordPress content types
- Static file caching for maximum performance
- Gzip compression and chunked builds
- Delta sitemap for rapid indexing of changes
- IndexNow integration for Bing/Yandex instant submission
- News sitemap for Google News publishers
- Hreflang support for multilingual sites
- Conflict detection with other SEO plugins
