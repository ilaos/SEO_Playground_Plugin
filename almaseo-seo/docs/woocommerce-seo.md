# WooCommerce SEO

E-commerce-specific SEO features for WooCommerce stores. Product schema markup, optimized sitemaps, breadcrumb navigation, and per-product visibility controls.

---

## How It Works

When WooCommerce is active, AlmaSEO adds a dedicated SEO layer for your product catalog. This includes structured data for rich results, product sitemaps for search engines, and fine-grained control over which products appear in search results.

---

## Product Schema

AlmaSEO generates Product schema for every WooCommerce product, including:

| Property | Source |
|----------|--------|
| **Name** | Product title |
| **Description** | Product short description |
| **Price** | Current price (sale price if on sale) |
| **Currency** | Store currency |
| **Availability** | In stock / Out of stock / On backorder |
| **SKU** | Product SKU |
| **Brand** | Product brand (if set) |
| **Reviews** | Star rating and review count |
| **Images** | Product gallery images |

This schema powers Google's product rich results — showing price, availability, and ratings directly in search results.

### Toggle Options

Each schema property can be toggled on/off globally:

- `show_price_in_schema` — Include pricing (default: on)
- `show_stock_in_schema` — Include stock status (default: on)
- `show_reviews_in_schema` — Include reviews and ratings (default: on)

---

## Product Sitemaps

AlmaSEO generates a dedicated product sitemap separate from the main XML sitemap:

| Setting | Default |
|---------|---------|
| **Enable product sitemap** | On |
| **Product priority** | 0.8 |
| **Product change frequency** | Daily |
| **Category priority** | 0.6 |
| **Category change frequency** | Weekly |

Products are included in the sitemap only if they are published and not individually noindexed.

---

## Breadcrumbs

AlmaSEO generates SEO-friendly breadcrumb navigation for product pages:

```
Home / Shop / Category / Product Name
```

### Configuration

- **Separator** — Character between breadcrumb levels (default: ` / `)
- **Home text** — Label for the home link (default: "Home")
- **Shop text** — Label for the shop link (default: "Shop")
- **Enable/disable** — Toggle breadcrumbs on or off

Breadcrumbs include BreadcrumbList schema markup for rich results.

---

## Noindex Controls

Control which products and product archives appear in search results:

### Per-Product

On the product edit screen, toggle **Noindex** to exclude a specific product from search engine indexing. Useful for:

- Internal or test products
- Products only available through direct links
- Duplicate or variant products

### Global Options

| Option | Effect |
|--------|--------|
| **Noindex all products** | Exclude all products from indexing |
| **Noindex product categories** | Exclude category archives |
| **Noindex product tags** | Exclude tag archives |

When global noindex is enabled, AlmaSEO also adds matching `Disallow` rules to your robots.txt.

---

## Meta Robots

AlmaSEO injects the appropriate `<meta name="robots">` tag in the `<head>` of noindexed products and archives:

```html
<meta name="robots" content="noindex, follow">
```

This tells search engines not to index the page but to still follow links on it.

---

## Product Editor Metabox

On the WooCommerce product edit screen, AlmaSEO adds SEO fields:

- **Meta title** — Custom search result title
- **Meta description** — Custom search result description
- **Noindex toggle** — Exclude from search engines
- **Health score** — SEO quality assessment

---

## Tier

**Pro only** — WooCommerce SEO requires a Pro or Agency license.

---

## Summary

- Product schema with price, availability, reviews, and images
- Dedicated product sitemap with configurable priority and frequency
- SEO-friendly breadcrumbs with BreadcrumbList schema
- Per-product and global noindex controls
- Robots.txt integration for noindexed products
- Meta robots tag injection
- Product editor metabox for SEO fields
