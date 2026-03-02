# Internal Links

Automatically link keywords to the right pages across your site — without editing a single post.

---

## How It Works

When a visitor loads one of your pages, AlmaSEO scans the content for keywords you've defined. If a match is found, that keyword is turned into a clickable link pointing to the destination you chose.

This all happens in real time as the page loads. The important thing to understand: **your original content is never changed**. The post stored in your database stays exactly as you wrote it. The link only appears in the version visitors (and search engines) see.

Think of it like a layer that sits on top of your content. The layer adds links based on your rules, but the content underneath is untouched.

---

## Why Links Are Not Saved Permanently

This is a deliberate design choice, and it's the same approach used by most professional internal linking tools. Here's why:

**No risk to your content.** Your posts, pages, and block editor markup stay exactly as they are. There's no chance of a linking rule accidentally breaking a layout, corrupting a shortcode, or interfering with Gutenberg blocks.

**Instant changes, site-wide.** When you create, edit, or delete a rule, the change takes effect immediately on every page across your site. There's no need to scan through hundreds of posts and rewrite each one.

**Easy to undo.** Delete a rule and every link it created disappears instantly. Pause all auto-linking with a single toggle. There's no leftover markup to clean up.

### Comparison

| | Auto-linking (this feature) | Permanent content editing |
|---|---|---|
| **Flexibility** | Change a rule and every page updates instantly | Must reprocess every affected post individually |
| **Reversibility** | Delete a rule and links vanish immediately | Must find and remove links from every post |
| **Content safety** | Original content is never touched | Risk of breaking layouts, shortcodes, or block markup |
| **Performance** | Small processing step when pages load (fast, cacheable) | No runtime processing needed |
| **Editor visibility** | Links don't appear in the post editor | Links are visible when editing a post |

For most sites, the flexibility and safety of auto-linking far outweigh the benefits of permanent editing.

---

## SEO Impact

Search engines crawl the fully rendered version of your pages — the same version your visitors see. They have no way to tell whether a link was typed by hand, stored in your database, or added by a content filter.

This means:

- **Link equity passes normally.** Internal links created by rules transfer PageRank between pages the same way manual links do.
- **Anchor text signals work.** Google uses the anchor text to understand what the linked page is about. Auto-linked anchor text is treated identically to manually written anchor text.
- **Crawl discovery works.** Internal links help search engines find and index pages on your site. Auto-inserted links serve this purpose just as well as manual ones.

**There is no SEO disadvantage to this approach.** The final HTML output is identical regardless of how the link got there.

The only edge case to be aware of: if you use a page caching plugin that caches content *before* WordPress content filters run, the links might not appear in cached versions. In practice, virtually all modern caching plugins (WP Rocket, LiteSpeed Cache, W3 Total Cache, WP Super Cache in default mode) cache the fully rendered output, so this is not a concern for most sites.

---

## How to Use It

### Step 1: Create a rule

Click **Add New Rule** at the top of the Internal Links page. You'll need two things:

- **Keyword or phrase** — the text to look for in your content (e.g., "garage door repair")
- **Link destination** — the page that keyword should link to (e.g., your main service page URL)

### Step 2: Choose how matching works

- **Exact match** (recommended) — only matches the keyword as a whole word. "SEO" won't match inside "SEOptimize."
- **Partial match** — matches the keyword even inside longer words. "link" would match "linking" and "backlinks."
- **Regex** — for advanced users who need pattern-based matching.

### Step 3: Set guardrails

- **Max links per post** — how many times this keyword can be linked in a single post. The default of 1 is a good starting point.
- **Priority** — when multiple rules could match in the same post, lower numbers are applied first. Use 1–5 for your most important pages.

### Step 4: Save and activate

Click **Save Rule**. The rule takes effect immediately — the next time any page loads, matching keywords will be linked. There's no publish step or delay.

---

## Settings Explained

The Settings tab controls global behavior that applies to all rules.

### Enable Auto-Linking

A master switch. When turned off, all auto-linking stops immediately across your site. Your rules are preserved — nothing is deleted. Turn it back on and everything resumes. Useful for troubleshooting or temporary pauses.

### Max Links Per Post

The maximum total number of auto-inserted links in any single post, regardless of how many rules match. This prevents over-linking, which search engines can view negatively.

A good starting point is **5–10 links per post** for most sites. Shorter posts may benefit from fewer; longer guides can handle more.

### Protected Zones

Content areas where links will never be inserted, even if a keyword matches:

- **Headings (h1–h6)** — keeps your heading structure clean and avoids confusing readers
- **Images and captions** — prevents links from breaking image layouts or interfering with figure elements
- **First paragraph** — optional; keeps your opening text link-free for a cleaner reading experience

### Exclude Pages

A list of post or page IDs that should never receive auto-links. Useful for:

- Landing pages where you want full control over every link
- Legal or policy pages that should remain link-free
- Any content where auto-linking would feel out of place

---

## Best Practices

**Start with your most important pages.** Identify the 5–10 pages you most want to strengthen with internal links (service pages, pillar content, key product pages). Create rules for those first.

**Use natural keyword phrases.** Instead of single generic words like "service," use specific phrases like "roof repair service" or "content marketing strategy." This produces more relevant, natural-feeling links.

**Keep max-per-post low.** One link per keyword per post is the safest starting point. You can always increase it later after reviewing how links appear on your pages.

**Set priorities thoughtfully.** If "SEO" and "SEO audit" could both match in the same paragraph, give the more specific phrase ("SEO audit") a lower priority number so it gets linked first.

**Review your pages after adding rules.** Visit a few posts on the front end to verify the links look natural. Check that links aren't appearing in awkward places or disrupting the reading flow.

**Don't try to link everything.** A focused set of 20–30 well-chosen rules is more effective than 200 rules trying to link every possible keyword. Quality over quantity.

---

## Example

Suppose you run a home services website and have a detailed guide at `yoursite.com/garage-door-repair`. You want every blog post that mentions "garage door repair" to link to that guide.

1. Click **Add New Rule**
2. Keyword: `garage door repair`
3. Link destination: `https://yoursite.com/garage-door-repair`
4. Match type: **Exact match**
5. Max per post: **1**
6. Priority: **5**
7. Save

Now, any blog post that contains the phrase "garage door repair" will automatically show it as a link to your guide page. If you later rename that guide or move it to a different URL, just update the one rule — every linked post updates instantly.

---

## Summary

- You control your entire internal linking structure from one place
- No need to open or edit individual posts
- Your original content is never modified
- Changes apply instantly across your entire site
- Rules can be edited, paused, or removed at any time with no cleanup
- Search engines treat these links identically to manual links
