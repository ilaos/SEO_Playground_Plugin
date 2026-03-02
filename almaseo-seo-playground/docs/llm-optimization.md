# LLM Optimization Analysis

AI-powered content analysis that evaluates how well your content is structured for large language models and AI-powered search engines.

---

## How It Works

AlmaSEO analyzes your post content and provides actionable suggestions for improving its visibility in AI-powered search results (Google AI Overviews, ChatGPT, Perplexity, etc.). The analysis runs on-demand from the post editor.

---

## Analysis Methods

### Local Heuristics (Free)

When your site is not connected to AlmaSEO, the plugin uses built-in heuristic rules to analyze content structure:

- Heading hierarchy and organization
- Content completeness indicators
- Question-answer structure detection
- List and step formatting
- Content length and depth

### Remote AI Analysis (Connected)

When connected to AlmaSEO, content is sent to the analysis API for deeper evaluation:

- Natural language understanding of content quality
- Competitive gap analysis
- Entity and topic coverage
- Citation-worthiness scoring
- AI snippet optimization suggestions

Content is limited to 5,000 characters for API efficiency.

---

## Analysis Styles

Choose the analysis perspective that matches your content:

| Style | Best For |
|-------|----------|
| **Concise** | Quick overview with key action items |
| **Detailed** | Comprehensive breakdown of all factors |
| **Business** | Commercial and conversion-focused content |
| **Technical** | Developer docs, specifications, how-to guides |
| **Creative** | Blog posts, opinion pieces, storytelling |
| **Academic** | Research, whitepapers, educational content |
| **Q&A** | FAQ pages, knowledge base articles |
| **AI Answer** | Content optimized to be cited by AI assistants |

---

## Section-Level Analysis

Enable section-level analysis to get feedback on individual content sections rather than just the overall page. This breaks your content at heading boundaries and evaluates each section independently.

---

## What Gets Analyzed

The analysis considers:

| Factor | Source |
|--------|--------|
| Post title | `post_title` |
| Content body | `post_content` (up to 5,000 chars) |
| Word count | Calculated from content |
| Heading structure | Extracted H1-H6 tags |
| Meta title | `_almaseo_meta_title` |
| Meta description | `_almaseo_meta_description` |
| Focus keyword | `_almaseo_focus_keyword` |
| Schema type | `_almaseo_schema_type` |

---

## How to Use

1. Open any post in the editor
2. Go to the **AI Tools** tab in the AlmaSEO optimization panel
3. Select an analysis style
4. Click **Analyze**
5. Review the suggestions and apply them to your content

---

## Tier

- **Free**: Local heuristic analysis (available to all users)
- **Pro**: Remote AI analysis via AlmaSEO API (requires connection)

---

## Summary

- On-demand content analysis for AI/LLM visibility
- 8 analysis styles for different content types
- Local heuristic fallback when not connected
- Remote AI analysis with deeper insights when connected
- Section-level analysis option for granular feedback
- Evaluates title, content, headings, meta fields, and schema
