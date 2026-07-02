# Schema Markup

Structured data generation for search engines. AlmaSEO outputs schema.org JSON-LD in `@graph` format with automatic content type detection, Knowledge Graph support, and per-post overrides.

---

## How It Works

AlmaSEO automatically generates JSON-LD structured data for every page on your site. The markup is injected into the `<head>` section and follows the `@graph` pattern recommended by Google, linking related entities with `@id` references.

---

## Schema Types

### Basic (Free)

| Type | Auto-Detection |
|------|---------------|
| **Article** | Standard blog posts |
| **BlogPosting** | Posts categorized as blog content |
| **NewsArticle** | Posts tagged or categorized as news |
| **WebPage** | Static pages |

### Advanced (Pro)

| Type | Auto-Detection |
|------|---------------|
| **FAQPage** | Pages with headings ending in `?` (auto-extracts Q&A pairs) |
| **HowTo** | Pages with ordered or unordered lists (auto-extracts steps) |
| **Service** | Service-type pages |
| **LocalBusiness** | Business location pages |
| **Product** | WooCommerce products (via WooCommerce SEO module) |

---

## Automatic Content Detection

### FAQ Detection

AlmaSEO scans your content for headings (H2, H3, H4) that end with a question mark. Each question heading and the paragraph content below it becomes a Q&A pair in the FAQPage schema.

Example:
```
## What is SEO?
SEO stands for Search Engine Optimization...
```
This automatically generates a `Question` + `AcceptedAnswer` in the schema.

### HowTo Detection

AlmaSEO detects `<ol>` or `<ul>` lists in your content and converts them into HowTo steps in the schema.

---

## Knowledge Graph

The Knowledge Graph node represents your site's identity to search engines:

- **Organization** or **Person** — Choose what your site represents
- **Site Name** — Your business or personal brand name
- **Logo** — URL to your logo image
- **Social Profiles** — Links to your social media pages

This information appears in Google's Knowledge Panel when your brand is searched.

### Configuration

Go to **AlmaSEO > Settings** to configure:

- Site represents: Organization or Person
- Site name
- Logo URL
- Social profile URLs (Facebook, Twitter/X, LinkedIn, Instagram, YouTube, etc.)

---

## Per-Post Controls

On the post edit screen (Schema & Meta tab), you can:

- **Override schema type** — Choose a different primary type for this post
- **Toggle FAQPage** — Enable/disable FAQ schema detection
- **Toggle HowTo** — Enable/disable HowTo schema detection
- **Disable schema** — Turn off all schema output for this specific post

---

## Default Schema by Post Type

Configure which schema type is used by default for each post type:

| Post Type | Default |
|-----------|---------|
| Posts | Article or BlogPosting |
| Pages | WebPage |
| Products | Product (via WooCommerce module) |
| Custom Post Types | Configurable |

---

## Graph Structure

AlmaSEO outputs a single `@graph` array containing interconnected nodes:

```json
{
  "@context": "https://schema.org",
  "@graph": [
    { "@type": "WebSite", "@id": "https://example.com/#website", ... },
    { "@type": "WebPage", "@id": "https://example.com/post/#webpage", ... },
    { "@type": "Article", "@id": "https://example.com/post/#article", ... },
    { "@type": "Organization", "@id": "https://example.com/#organization", ... }
  ]
}
```

Nodes reference each other via `@id` anchors, creating a connected graph that search engines can navigate.

---

## Image Handling

- **Featured image** — Automatically included as the article's primary image
- **OG image fallback** — If no featured image is set, falls back to the Open Graph image
- **Image dimensions** — Width and height are included when available

---

## Tier

- **Free**: Basic schema types (Article, BlogPosting, NewsArticle, WebPage)
- **Pro**: Advanced types (FAQPage, HowTo, Service, LocalBusiness), Knowledge Graph, per-post type overrides

---

## Summary

- Automatic JSON-LD generation in `@graph` format
- Content-aware type detection (FAQ questions, HowTo steps)
- Knowledge Graph with logo and social profiles
- Per-post schema type overrides and toggles
- Default schema mapping by post type
- Featured image and OG fallback handling
- Connected graph structure with `@id` references
