# Content Refresh

Keep your published content fresh and accurate without rewriting entire posts. Review AI-suggested improvements section by section, keep what works, and update your live page in one click.

---

## How It Works

AlmaSEO monitors your published content and identifies posts that could benefit from updates — outdated information, thin sections, missing context, or opportunities to improve clarity and depth.

When improvements are identified, AlmaSEO generates a **refresh draft**: a proposed rewrite of specific sections of your post. The draft appears on the Content Refresh page in your WordPress admin.

You review the changes section by section, with your current content on the left and the suggested version on the right. You decide what to keep and what to reject. When you're ready, you apply the accepted changes to your live post.

**Your original content is always protected.** Every time changes are applied, WordPress automatically creates a revision. If you don't like the result, you can restore the previous version from the Revisions screen — standard WordPress functionality.

---

## What Gets Refreshed

Content Refresh works with any post type — blog posts, pages, WooCommerce products, or custom post types. AlmaSEO splits your content at heading boundaries (h2, h3, h4) and treats each section independently. This means:

- The intro paragraph before your first heading is one section
- Each heading and the content below it is another section
- You can accept changes to one section while keeping others exactly as they are

Sections that haven't changed between your current content and the proposed version are marked as "unchanged" and dimmed. You only need to review the sections where changes are being suggested.

---

## The Review Process

### Step 1: Open a refresh draft

From the Content Refresh list page, click **Review** on any pending draft. This opens the side-by-side review page.

### Step 2: Compare each section

Each changed section shows two columns:

- **Current** (left, red tint) — your live content as it exists now
- **Proposed** (right, green tint) — the suggested replacement

Read through both versions and decide if the proposed change is an improvement.

### Step 3: Accept or reject

Each changed section has a checkbox. Check it to accept the proposed version, uncheck it to keep your current version. You can also use:

- **Accept all** — check every section at once
- **Reject all** — uncheck every section at once

### Step 4: Apply

Click **Apply selected changes**. AlmaSEO merges your decisions:

- Accepted sections use the proposed content
- Rejected sections keep your current content
- Unchanged sections are left as-is

The merged result is saved to your post via `wp_update_post()`, which automatically creates a WordPress revision.

---

## Content Drift Detection

If you edit a post after a refresh draft was pushed but before you review it, the content has "drifted" from what AlmaSEO originally analyzed. The review page will show a warning if this happens.

This doesn't block you from reviewing — it's just a heads-up that the side-by-side comparison may not perfectly reflect your current content, since you've made changes since the draft was created.

---

## Safety and Rollback

Content Refresh is designed to be safe:

- **WordPress revisions** are created automatically every time changes are applied. You can restore any previous version from the standard WordPress Revisions screen.
- **Section-level control** means you never have to accept an all-or-nothing rewrite. Keep the sections you're happy with and only update what needs changing.
- **Dismiss option** lets you reject an entire draft without applying any changes. The draft is marked as dismissed and no content is modified.

---

## Statuses

Each refresh draft has one of these statuses:

| Status | Meaning |
|---|---|
| **Pending** | Waiting for your review. No changes have been made to the post. |
| **Applied** | You reviewed and applied changes. The post has been updated. |
| **Dismissed** | You chose to reject the entire draft. No changes were made. |

---

## Tips

**Review promptly.** The longer a draft sits, the more likely your content will drift from what was analyzed. Reviewing within a few days gives you the most accurate comparison.

**Use section-level decisions.** Don't feel pressure to accept everything. It's common to accept 2 out of 5 sections and reject the rest. The feature is designed for selective updates.

**Check the result.** After applying changes, visit the post on the front end to verify everything looks right. If something seems off, use WordPress Revisions to roll back.

**Drafts are one per post.** Only one active refresh draft can exist per post at a time. A new push from the dashboard replaces any existing pending draft for that post.

---

## Summary

- AlmaSEO identifies content that could be improved and pushes refresh drafts to your site
- You review changes section by section with a side-by-side comparison
- Accept the sections you like, reject the rest
- Apply with one click — WordPress saves a revision automatically
- Works with any post type: posts, pages, products, custom types
- Your original content is always protected and recoverable
