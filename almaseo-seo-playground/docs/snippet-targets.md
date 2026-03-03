# Featured Snippet Targeting

Win the answer box at the top of Google search results by optimizing your content for specific queries.

---

## What Is a Featured Snippet?

A featured snippet is the highlighted answer box that appears at the very top of Google search results — above all regular results, sometimes called "position zero." It pulls content directly from a web page and displays it prominently.

Featured snippets come in four formats:

| Format | What It Looks Like |
|--------|-------------------|
| **Paragraph** | A short block of text directly answering the query |
| **List** | A numbered or bulleted list of steps or items |
| **Table** | A comparison or data table |
| **Definition** | A brief, dictionary-style definition |

If your page already ranks on page 1 for a query, you're a strong candidate for the featured snippet — you just need content formatted the way Google expects.

---

## Connection Required

Snippet opportunities are identified by the AlmaSEO dashboard using your Google Search Console data. It looks for queries where your pages rank in the top 10 but someone else currently holds the featured snippet.

**For opportunities to appear, you need:**

1. Your site connected to the AlmaSEO dashboard (app password set up)
2. Your Google Search Console property linked in the AlmaSEO dashboard
3. The dashboard's analysis to have identified snippet opportunities for your queries

Without this connection, the table will be empty. You can connect your site from **SEO Playground → Settings**.

---

## How to Use It

### Step 1: Review Opportunities

Each opportunity shows:

- **Query** — the search term you're targeting
- **Page** — which of your pages ranks for this query
- **Format** — the recommended snippet format (paragraph, list, table, or definition)
- **Position** — your current ranking position
- **Volume** — monthly search volume for this query

### Step 2: Draft Snippet Content

Click the **Draft** button on an opportunity to open the editor. You'll see:

- The target query and recommended format
- A prompt hint with guidance on how to structure your content for that format
- A text editor where you write the snippet-optimized HTML
- A live preview showing how the content will look

**Tips for writing snippet content:**

- **Paragraph**: Write a clear, concise answer in 40–60 words. Start with the query's answer directly.
- **List**: Use an ordered or unordered list with 3–8 items. Keep each item to one line.
- **Table**: Use a simple HTML table with clear headers and 3–5 rows.
- **Definition**: Start with "X is..." and keep it to 1–2 sentences.

### Step 3: Approve and Apply

Once your draft is ready:

1. Click **Approve** to mark it as ready
2. Click **Apply** to insert the content into your post

When you apply, the snippet content is inserted after the first heading in your post. A WordPress revision is created automatically, so you can always roll back from the post editor.

### Step 4: Track Results

After applying, monitor the opportunity's status:

- **Applied** — content is live, waiting for Google to pick it up
- **Won** — you captured the featured snippet (update via dashboard)
- **Lost** — you didn't win this time (you can revise and try again)

---

## Undoing Applied Content

Click the **Undo** button on any applied snippet to remove it cleanly from your post. The snippet content is tracked with invisible markers, so the Undo button removes exactly what was inserted without affecting the rest of your content.

Your original post content is never overwritten or modified — the snippet content sits alongside it.

---

## Status Flow

Opportunities move through these statuses:

```
Opportunity → Draft → Approved → Applied → Won / Lost / Expired
```

| Status | Meaning |
|--------|---------|
| **Opportunity** | Identified by dashboard, no draft yet |
| **Draft** | You've written snippet content |
| **Approved** | Draft reviewed and ready to apply |
| **Applied** | Content inserted into the post |
| **Won** | You captured the featured snippet |
| **Lost** | Google chose a different page for the snippet |
| **Rejected** | You decided not to pursue this opportunity |
| **Expired** | The opportunity is no longer relevant |

---

## Summary

- Targets queries where you rank on page 1 but don't hold the featured snippet
- Opportunities are identified by the AlmaSEO dashboard via Search Console data
- Draft snippet-optimized content in four formats: paragraph, list, table, definition
- Apply content to posts with one click — WordPress revision created automatically
- Undo cleanly at any time without affecting your original content
- Track results: won, lost, or expired
- Requires AlmaSEO dashboard connection with Search Console linked
