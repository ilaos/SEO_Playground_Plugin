<?php
/**
 * AlmaSEO Documentation & Help — Admin Page Template
 *
 * Displays all 39 free features organized into 6 categories
 * with collapsible sections, search, and table of contents.
 *
 * @package AlmaSEO
 * @since   8.6.0
 */

if ( ! defined( 'ABSPATH' ) ) exit;
?>

<div class="wrap almaseo-docs-wrap">

    <h1><?php esc_html_e( 'Documentation & Help', 'almaseo-seo-playground' ); ?></h1>

    <!-- ── Intro ── -->
    <div class="almaseo-docs-intro">
        <p class="almaseo-docs-intro-lead">
            <?php esc_html_e( 'AlmaSEO SEO Playground ships 39 free features — everything Yoast, Rank Math, and AIOSEO offer for free, combined in one plugin.', 'almaseo-seo-playground' ); ?>
        </p>
        <p class="almaseo-docs-intro-desc">
            <?php esc_html_e( 'Browse the categories below to learn what each feature does and where to find it. Use the search bar to quickly locate any feature. Features marked "AI Enhanced" gain additional capabilities when connected to the AlmaSEO cloud dashboard.', 'almaseo-seo-playground' ); ?>
        </p>
    </div>

    <!-- ── Search ── -->
    <div class="almaseo-docs-search">
        <span class="dashicons dashicons-search"></span>
        <input type="text" id="almaseo-docs-search-input" placeholder="<?php esc_attr_e( 'Search features...', 'almaseo-seo-playground' ); ?>" autocomplete="off" />
        <div class="almaseo-docs-result-count" id="almaseo-docs-result-count"></div>
    </div>

    <!-- ── No Results ── -->
    <div class="almaseo-docs-no-results" id="almaseo-docs-no-results">
        <?php esc_html_e( 'No features match your search.', 'almaseo-seo-playground' ); ?>
    </div>

    <!-- ── Table of Contents ── -->
    <div class="almaseo-docs-toc" id="almaseo-docs-toc">
        <h2><?php esc_html_e( 'Contents', 'almaseo-seo-playground' ); ?></h2>
        <ol>
            <li><a href="#cat-page-optimization"><?php esc_html_e( 'Page Optimization', 'almaseo-seo-playground' ); ?></a> <span class="almaseo-docs-toc-count">(9)</span></li>
            <li><a href="#cat-schema"><?php esc_html_e( 'Schema & Structured Data', 'almaseo-seo-playground' ); ?></a> <span class="almaseo-docs-toc-count">(5)</span></li>
            <li><a href="#cat-technical"><?php esc_html_e( 'Technical SEO', 'almaseo-seo-playground' ); ?></a> <span class="almaseo-docs-toc-count">(9)</span></li>
            <li><a href="#cat-content"><?php esc_html_e( 'Content Tools', 'almaseo-seo-playground' ); ?></a> <span class="almaseo-docs-toc-count">(7)</span></li>
            <li><a href="#cat-management"><?php esc_html_e( 'Site Management', 'almaseo-seo-playground' ); ?></a> <span class="almaseo-docs-toc-count">(7)</span></li>
            <li><a href="#cat-blocks"><?php esc_html_e( 'Block Editor Enhancements', 'almaseo-seo-playground' ); ?></a> <span class="almaseo-docs-toc-count">(2)</span></li>
            <li><a href="#cat-dashboard"><?php esc_html_e( 'AI-Powered Dashboard Enhancements', 'almaseo-seo-playground' ); ?></a></li>
            <li><a href="#cat-pro"><?php esc_html_e( 'Pro Features', 'almaseo-seo-playground' ); ?></a></li>
        </ol>
    </div>


    <!-- ════════════════════════════════════════════════════════════ -->
    <!-- CATEGORY 1: Page Optimization (9 features)                  -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div class="almaseo-docs-category" id="cat-page-optimization">
        <div class="almaseo-docs-cat-header" data-target="cat-page-optimization-body">
            <span class="dashicons dashicons-arrow-down-alt2"></span>
            <h2><?php esc_html_e( 'Page Optimization', 'almaseo-seo-playground' ); ?></h2>
            <span class="almaseo-docs-cat-count">9</span>
        </div>
        <div class="almaseo-docs-cat-body" id="cat-page-optimization-body">

            <!-- 1. SEO Meta Box -->
            <div class="almaseo-docs-feature" data-search="seo meta box title description serp preview meta tags">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'SEO Meta Box', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Post / Page Editor', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'A 7-tab optimization panel that appears below the post editor. Your central hub for managing all on-page SEO settings.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Meta title with character counter (60 characters recommended)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Meta description with length indicator (150-160 characters)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Google SERP preview showing how your page appears in search results (desktop and mobile)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Tabs: SEO Page Health, Search Console, Schema & Meta, AI Tools, LLM Optimization, Notes & History', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 2. Focus Keywords -->
            <div class="almaseo-docs-feature" data-search="focus keyword primary keyword target keyword">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Focus Keywords', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Post Editor — SEO Page Health Tab', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Set a primary focus keyword for each post or page. The SEO health score analyzes keyword density, placement in title, description, headings, and content.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Keyword density and placement analysis', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Checks keyword presence in title, description, URL, first paragraph, and headings', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Integrates with Google Keyword Suggestions for autocomplete', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 3. SEO Health Score -->
            <div class="almaseo-docs-feature" data-search="seo health score signals analysis grading scoring">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'SEO Health Score', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Post Editor — SEO Page Health Tab', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Real-time SEO scoring from 0 to 100 based on 10 weighted signals. Shows pass/fail indicators for each signal so you know exactly what to fix.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( '10 signals: title length, description length, keyword presence, content length, heading structure, image alt tags, internal links, readability, and more', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Configurable signal weights', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Recalculate on demand with a single click', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 4. Open Graph & Twitter Cards -->
            <div class="almaseo-docs-feature" data-search="open graph og twitter cards social media sharing facebook">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Open Graph & Twitter Cards', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Post Editor — Schema & Meta Tab', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Control how your content appears when shared on Facebook, Twitter, LinkedIn, and other social platforms. Set custom titles, descriptions, and images for social sharing.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Per-post Open Graph title, description, and image', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Twitter Card type selection (summary, large image)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Falls back to SEO title/description when social fields are empty', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 5. Meta Robots -->
            <div class="almaseo-docs-feature" data-search="meta robots noindex nofollow noarchive robots directives">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Meta Robots Controls', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Post Editor — Schema & Meta Tab', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Control how search engines index and follow links on individual posts and pages.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'noindex — prevent search engines from indexing this page', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'nofollow — prevent search engines from following links on this page', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'noarchive — prevent search engines from caching this page', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 6. Canonical URL -->
            <div class="almaseo-docs-feature" data-search="canonical url duplicate content rel canonical">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Canonical URL Management', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Post Editor — Schema & Meta Tab', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Set a custom canonical URL to tell search engines which version of a page is the original. Prevents duplicate content issues when the same content is accessible at multiple URLs.', 'almaseo-seo-playground' ); ?></p>
            </div>

            <!-- 7. Headline Analyzer -->
            <div class="almaseo-docs-feature" data-search="headline analyzer title score power words emotional ctr">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Headline Analyzer', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Post Editor — SEO Page Health Tab', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-ai"><?php esc_html_e( 'Free + AI Enhanced', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Real-time headline scoring from 0-100 with instant feedback as you type. Analyzes the characteristics that make headlines perform well in search results and social media.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Word count analysis (optimal: 6-13 words)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Power word and emotional word detection', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Number and question format bonuses', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Character length optimization', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'AI Enhanced: CTR prediction, emotional impact analysis, competitor headline comparison, AI rewrite suggestions', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 8. Readability -->
            <div class="almaseo-docs-feature" data-search="readability flesch score passive voice transition words subheading sentence paragraph">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Enhanced Readability Analysis', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Post Editor — SEO Page Health Tab', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-ai"><?php esc_html_e( 'Free + AI Enhanced', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Comprehensive readability breakdown that helps you write content your audience can easily understand. Provides specific, actionable feedback on writing quality.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Flesch Reading Ease score with grade level', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Passive voice percentage detection', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Transition word usage analysis', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Subheading distribution check (one per 300 words)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Sentence length analysis (flags sentences over 20 words)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'AI Enhanced: SERP competitor readability benchmarks, per-paragraph improvement suggestions', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 9. Visual Social Previews -->
            <div class="almaseo-docs-feature" data-search="visual social previews facebook twitter card mockup preview">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Visual Social Previews', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Post Editor — Schema & Meta Tab', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'See exactly how your content will look when shared on Facebook and Twitter. Live-updating visual card mockups update as you edit your OG and Twitter meta fields.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Facebook card preview (1200x630 format with title, description, URL, site name)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Twitter card preview (large image format with @handle)', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

        </div>
    </div>


    <!-- ════════════════════════════════════════════════════════════ -->
    <!-- CATEGORY 2: Schema & Structured Data (5 features)           -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div class="almaseo-docs-category" id="cat-schema">
        <div class="almaseo-docs-cat-header" data-target="cat-schema-body">
            <span class="dashicons dashicons-arrow-down-alt2"></span>
            <h2><?php esc_html_e( 'Schema & Structured Data', 'almaseo-seo-playground' ); ?></h2>
            <span class="almaseo-docs-cat-count">5</span>
        </div>
        <div class="almaseo-docs-cat-body" id="cat-schema-body">

            <!-- 1. Schema Markup -->
            <div class="almaseo-docs-feature" data-search="schema markup json-ld structured data article webpage organization breadcrumblist">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Schema Markup', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Frontend JSON-LD', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Automatically generates Schema.org structured data in JSON-LD format. Helps search engines understand your content and can enable rich results like review stars, FAQ dropdowns, and breadcrumbs.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Article schema for blog posts (author, dates, images)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'WebPage schema for static pages', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Organization schema (site-wide)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'BreadcrumbList schema for navigation breadcrumbs', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 2. Local Business Schema -->
            <div class="almaseo-docs-feature" data-search="local business schema 193 subtypes address phone hours opening restaurant store">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Local Business Schema (193 Subtypes)', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Post Editor — Schema & Meta Tab', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Complete LocalBusiness structured data for any type of local business. Select from 193 business types organized in 18 categories (Automotive, Food & Drink, Health & Medical, etc.).', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Full address fields (street, city, state, postal code, country)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Contact info: phone, email', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Geo coordinates (latitude, longitude)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Opening hours with per-day time pickers (Monday-Sunday)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Price range, area served, payment methods accepted', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 3. FAQ Block -->
            <div class="almaseo-docs-feature" data-search="faq block faqpage schema accordion questions answers gutenberg">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'FAQ Block (FAQPage Schema)', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Block Editor', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'A Gutenberg block for creating FAQ sections with automatic FAQPage schema markup. Eligible for Google\'s FAQ rich results, which can dramatically increase your search result size.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Accordion-style FAQ entries', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Add/remove question-answer pairs', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Automatic FAQPage JSON-LD output', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 4. How-To Block -->
            <div class="almaseo-docs-feature" data-search="how-to block howto schema steps instructions recipe tutorial gutenberg">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'How-To Block (HowTo Schema)', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Block Editor', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'A Gutenberg block for step-by-step instructions with automatic HowTo schema. Eligible for Google\'s How-To rich results with step-by-step previews in search.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Step-by-step instructions with optional images per step', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Total time and estimated cost fields', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Tool and supply lists', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Automatic HowTo JSON-LD output', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 5. Breadcrumbs Block -->
            <div class="almaseo-docs-feature" data-search="breadcrumbs block gutenberg navigation breadcrumblist schema">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Breadcrumbs Gutenberg Block', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Block Editor', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Add breadcrumb navigation anywhere in your content with a Gutenberg block. Includes Schema.org BreadcrumbList markup for rich results.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Customizable separator character', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Home link toggle', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'BreadcrumbList JSON-LD schema output', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

        </div>
    </div>


    <!-- ════════════════════════════════════════════════════════════ -->
    <!-- CATEGORY 3: Technical SEO (9 features)                      -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div class="almaseo-docs-category" id="cat-technical">
        <div class="almaseo-docs-cat-header" data-target="cat-technical-body">
            <span class="dashicons dashicons-arrow-down-alt2"></span>
            <h2><?php esc_html_e( 'Technical SEO', 'almaseo-seo-playground' ); ?></h2>
            <span class="almaseo-docs-cat-count">9</span>
        </div>
        <div class="almaseo-docs-cat-body" id="cat-technical-body">

            <!-- 1. XML Sitemaps -->
            <div class="almaseo-docs-feature" data-search="xml sitemap sitemap_index posts pages taxonomy author custom post type">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'XML Sitemaps', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'SEO Playground → Sitemaps', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Comprehensive XML sitemaps for posts, pages, custom post types, taxonomies, and authors. Helps search engines discover and crawl all your important content.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Paginated sitemaps with configurable links per sitemap', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Dynamic and static generation modes', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Delta tracking for recent changes', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Hreflang support for WPML/Polylang multilingual sites', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Conflict detection with Yoast, AIOSEO, RankMath sitemaps', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 2. Image Sitemaps -->
            <div class="almaseo-docs-feature" data-search="image sitemap images media">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Image Sitemaps', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Embedded in XML Sitemaps', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Automatic image extraction from post content and inclusion in XML sitemaps. Helps Google discover and index your images for Google Images search.', 'almaseo-seo-playground' ); ?></p>
            </div>

            <!-- 3. Video Sitemaps -->
            <div class="almaseo-docs-feature" data-search="video sitemap youtube vimeo oembed">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Video Sitemaps', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Separate Sitemap', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Detects YouTube and Vimeo videos embedded in your content via oEmbed and generates a dedicated video sitemap. Helps your videos appear in Google Video search results.', 'almaseo-seo-playground' ); ?></p>
            </div>

            <!-- 4. News Sitemaps -->
            <div class="almaseo-docs-feature" data-search="news sitemap google news publisher 48 hours">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'News Sitemaps', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'SEO Playground → Sitemaps', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Google News compatible sitemap with a rolling 48-hour window. Configure publisher information, news categories, and genres for Google News inclusion.', 'almaseo-seo-playground' ); ?></p>
            </div>

            <!-- 5. HTML Sitemap -->
            <div class="almaseo-docs-feature" data-search="html sitemap shortcode visitor-friendly">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'HTML Sitemap', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( '[almaseo_html_sitemap] Shortcode', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'A visitor-friendly HTML sitemap that lists all your pages and posts. Add it to any page with a shortcode. Configurable columns, depth, and display options.', 'almaseo-seo-playground' ); ?></p>
            </div>

            <!-- 6. IndexNow -->
            <div class="almaseo-docs-feature" data-search="indexnow instant indexing bing yandex seznam ping">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'IndexNow (Instant Indexing)', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Automatic on Publish', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Instantly notifies Bing, Yandex, and Seznam when you publish or update content. Gets your new and updated content indexed faster than waiting for crawlers to discover changes.', 'almaseo-seo-playground' ); ?></p>
            </div>

            <!-- 7. Robots.txt Editor -->
            <div class="almaseo-docs-feature" data-search="robots.txt editor virtual physical crawl directives">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Robots.txt Editor', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'SEO Playground → Robots.txt', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Edit your robots.txt file to control which parts of your site search engines can crawl. Virtual mode serves the file through WordPress without creating a physical file.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Virtual mode (recommended) or physical file mode', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Syntax-highlighted editor with live preview', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Insert sensible defaults with one click', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 8. Crawl Optimization -->
            <div class="almaseo-docs-feature" data-search="crawl optimization wp_head cleanup rsd wlw xmlrpc emoji jquery migrate">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Crawl Optimization', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Settings Page', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Remove unnecessary tags and scripts from your site\'s HTML head to reduce page bloat and improve crawl efficiency.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Remove RSD/WLW links, WordPress version meta tag, shortlinks', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Disable REST API link, oEmbed discovery links, XML-RPC', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Remove feed links, emoji scripts/styles, jQuery Migrate', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 9. .htaccess Editor -->
            <div class="almaseo-docs-feature" data-search="htaccess editor apache server configuration rewrite rules">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( '.htaccess Editor', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'SEO Playground → .htaccess', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Edit your .htaccess file directly from WordPress admin. Includes automatic backup before saving and one-click restore if something goes wrong.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Syntax-highlighted code editor', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Read-only mode when file is not writable', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Automatic backup before every save', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

        </div>
    </div>


    <!-- ════════════════════════════════════════════════════════════ -->
    <!-- CATEGORY 4: Content Tools (7 features)                      -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div class="almaseo-docs-category" id="cat-content">
        <div class="almaseo-docs-cat-header" data-target="cat-content-body">
            <span class="dashicons dashicons-arrow-down-alt2"></span>
            <h2><?php esc_html_e( 'Content Tools', 'almaseo-seo-playground' ); ?></h2>
            <span class="almaseo-docs-cat-count">7</span>
        </div>
        <div class="almaseo-docs-cat-body" id="cat-content-body">

            <!-- 1. Evergreen Content -->
            <div class="almaseo-docs-feature" data-search="evergreen content health monitoring stale watch fresh aging">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Evergreen Content Management', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'SEO Playground → Evergreen', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Monitor the health and freshness of your content over time. Automatically categorizes posts as Evergreen (healthy), Watch (needs attention), or Stale (outdated).', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Health trend charts (4/8/12 week views)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'At-risk content table with sorting', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Batch analysis (up to 100 posts)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Export to CSV/PDF', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 2. Breadcrumbs Module -->
            <div class="almaseo-docs-feature" data-search="breadcrumbs navigation trail theme shortcode">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Breadcrumbs Module', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Theme / Shortcode', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Add breadcrumb navigation to your theme. Can be integrated via PHP function call in your theme templates or via the Breadcrumbs Gutenberg block.', 'almaseo-seo-playground' ); ?></p>
            </div>

            <!-- 3. Search Appearance -->
            <div class="almaseo-docs-feature" data-search="search appearance templates title description smart tags default seo">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Search Appearance (Title/Description Templates)', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'SEO Playground → Search Appearance', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Set default title and meta description templates for every content type. Uses smart tags that automatically fill in post-specific data.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Smart tags: %%title%%, %%sitename%%, %%sep%%, %%excerpt%%, %%date%%, %%category%%, %%tag%%, %%author%%, %%page%%', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Templates for: Posts, Pages, Categories, Tags, Author Archives, Date Archives, Search Results, 404 Page', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Live preview with smart tag resolution', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 4. Cornerstone Content -->
            <div class="almaseo-docs-feature" data-search="cornerstone content pillar important star marker">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Cornerstone Content', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Posts/Pages List + Editor', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-ai"><?php esc_html_e( 'Free + AI Enhanced', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Mark your most important content as "cornerstone" so you can focus your internal linking and optimization efforts on the pages that matter most.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Star icon column in posts/pages list table', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Quick Edit checkbox support', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Sortable and filterable (Cornerstone Only / Non-Cornerstone)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'AI Enhanced: Dashboard auto-suggests cornerstone posts based on traffic, backlinks, word count, and internal link analysis', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 5. Image SEO -->
            <div class="almaseo-docs-feature" data-search="image seo alt text title attribute automatic missing accessibility">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Image SEO (Auto Alt/Title)', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Settings Page + Frontend', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-ai"><?php esc_html_e( 'Free + AI Enhanced', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Automatically adds missing alt text and title attributes to images throughout your site. Uses customizable templates with smart tags.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Template tags: %%filename%%, %%sitename%%, %%post_title%%', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Strip file extension from filename when generating text', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Optional override of existing alt text', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'AI Enhanced: Context-aware AI-generated alt text with confidence scores and decorative image detection', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 6. RSS Feed Controls -->
            <div class="almaseo-docs-feature" data-search="rss feed controls before after content attribution copyright">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'RSS Feed Controls', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Settings Page', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Add custom content before and after RSS feed items. Useful for adding attribution links, copyright notices, or calls-to-action to your syndicated content.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Smart tags: %%post_link%%, %%blog_link%%, %%blog_name%%, %%post_title%%', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 7. LLMs.txt -->
            <div class="almaseo-docs-feature" data-search="llms.txt ai crawlers large language models guidance permissions">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'LLMs.txt Management', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'SEO Playground → LLMs.txt', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Create and manage a /llms.txt file that provides guidance to AI crawlers about how your content should be used by large language models. Similar to robots.txt but for AI.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Template with sensible defaults', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Virtual file served via WordPress rewrite (no physical file)', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

        </div>
    </div>


    <!-- ════════════════════════════════════════════════════════════ -->
    <!-- CATEGORY 5: Site Management (7 features)                    -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div class="almaseo-docs-category" id="cat-management">
        <div class="almaseo-docs-cat-header" data-target="cat-management-body">
            <span class="dashicons dashicons-arrow-down-alt2"></span>
            <h2><?php esc_html_e( 'Site Management', 'almaseo-seo-playground' ); ?></h2>
            <span class="almaseo-docs-cat-count">7</span>
        </div>
        <div class="almaseo-docs-cat-body" id="cat-management-body">

            <!-- 1. Notes & History -->
            <div class="almaseo-docs-feature" data-search="notes history changelog meta changes restore version">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'SEO Notes & History', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Post Editor — Notes & History Tab', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Keep track of all SEO meta changes with a full change log. Add notes to posts for your SEO strategy. Restore any previous version with one click.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'All meta field changes logged with timestamps and user attribution', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'One-click restoration to any previous version', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'SEO notes per post (up to 1000 characters)', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 2. Import/Migration -->
            <div class="almaseo-docs-feature" data-search="import migration yoast rank math aioseo migrate transfer switch">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Import / Migration', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'SEO Playground → Import SEO', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Switch from Yoast SEO, Rank Math, or AIOSEO without losing any SEO data. One-click migration imports all your existing meta titles, descriptions, keywords, and settings.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Imports: meta titles, descriptions, focus keywords, robots meta, Open Graph, schema settings', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Dry-run mode previews what will be imported before committing', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Batch processing for large sites', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 3. Role Manager -->
            <div class="almaseo-docs-feature" data-search="role manager access control permissions capabilities user roles editor author">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Role / Access Manager', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Settings Page', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Control which WordPress user roles can access SEO features. Configure metabox visibility, settings access, and admin menu access per role.', 'almaseo-seo-playground' ); ?></p>
            </div>

            <!-- 4. Setup Wizard -->
            <div class="almaseo-docs-feature" data-search="setup wizard first run guided configuration onboarding">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Setup Wizard', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'First Activation / SEO Playground Menu', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Step-by-step guided configuration that runs on first activation. Helps you set up your site type, search appearance defaults, sitemaps, social profiles, and verification codes.', 'almaseo-seo-playground' ); ?></p>
            </div>

            <!-- 5. Verification Codes -->
            <div class="almaseo-docs-feature" data-search="verification codes webmaster google bing yandex pinterest baidu search console">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Webmaster Verification Codes', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Settings Page', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Add search engine verification meta tags without editing theme files. Verify your site with Google Search Console, Bing, Yandex, Pinterest, and Baidu.', 'almaseo-seo-playground' ); ?></p>
            </div>

            <!-- 6. Google Keyword Suggestions -->
            <div class="almaseo-docs-feature" data-search="keyword suggestions google suggest autocomplete search queries">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Google Keyword Suggestions', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Post Editor — Focus Keyword Field', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-ai"><?php esc_html_e( 'Free + AI Enhanced', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'As you type your focus keyword, get real-time suggestions from Google\'s autocomplete API. Shows what people are actually searching for related to your topic.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Debounced input with arrow key navigation', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( '1-hour server-side caching for performance', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'AI Enhanced: AI keyword insights panel with search volume, competition, intent classification, and trend direction', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 7. Google Analytics -->
            <div class="almaseo-docs-feature" data-search="google analytics ga4 gtag tracking measurement id">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Google Analytics Integration (GA4)', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Settings Page + Frontend', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Add Google Analytics GA4 tracking to your site without editing theme files. Enter your Measurement ID and the tracking code is automatically inserted.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'GA4 Measurement ID validation (G-XXXXXXX format)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Option to exclude logged-in administrators from tracking', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Optional outbound link click tracking', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

        </div>
    </div>


    <!-- ════════════════════════════════════════════════════════════ -->
    <!-- CATEGORY 6: Block Editor Enhancements (2 features)          -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div class="almaseo-docs-category" id="cat-blocks">
        <div class="almaseo-docs-cat-header" data-target="cat-blocks-body">
            <span class="dashicons dashicons-arrow-down-alt2"></span>
            <h2><?php esc_html_e( 'Block Editor Enhancements', 'almaseo-seo-playground' ); ?></h2>
            <span class="almaseo-docs-cat-count">2</span>
        </div>
        <div class="almaseo-docs-cat-body" id="cat-blocks-body">

            <!-- 1. TOC Block -->
            <div class="almaseo-docs-feature" data-search="toc table of contents block heading anchor links navigation">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Table of Contents Block', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Block Editor', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Auto-generated table of contents from your heading structure. Adds smooth-scroll anchor links so readers can jump to any section.', 'almaseo-seo-playground' ); ?></p>
                <ul class="almaseo-docs-feature-details">
                    <li><?php esc_html_e( 'Configurable heading levels (H2-H6)', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Collapsible/expandable display', 'almaseo-seo-playground' ); ?></li>
                    <li><?php esc_html_e( 'Numbered or bulleted list style', 'almaseo-seo-playground' ); ?></li>
                </ul>
            </div>

            <!-- 2. Link Attributes -->
            <div class="almaseo-docs-feature" data-search="link attributes nofollow sponsored ugc rel attribute block editor toolbar">
                <div class="almaseo-docs-feature-header">
                    <h3><?php esc_html_e( 'Link Attributes (nofollow / sponsored / ugc)', 'almaseo-seo-playground' ); ?></h3>
                    <span class="almaseo-docs-location"><?php esc_html_e( 'Block Editor — Link Toolbar', 'almaseo-seo-playground' ); ?></span>
                    <span class="almaseo-docs-tier almaseo-docs-tier-free"><?php esc_html_e( 'Free', 'almaseo-seo-playground' ); ?></span>
                </div>
                <p class="almaseo-docs-feature-desc"><?php esc_html_e( 'Adds nofollow, sponsored, and ugc toggle buttons to the block editor link popover. Easily mark affiliate links as sponsored or user-generated content links as ugc.', 'almaseo-seo-playground' ); ?></p>
            </div>

        </div>
    </div>


    <!-- ════════════════════════════════════════════════════════════ -->
    <!-- AI Dashboard Enhancements (informational section)           -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div class="almaseo-docs-info-section" id="cat-dashboard">
        <h2><?php esc_html_e( 'AI-Powered Dashboard Enhancements', 'almaseo-seo-playground' ); ?></h2>
        <p><?php esc_html_e( 'When connected to the AlmaSEO cloud dashboard, 5 free features gain AI capabilities that no other SEO plugin offers. All features work fully without the dashboard — the AI layer is a free bonus for connected sites.', 'almaseo-seo-playground' ); ?></p>
        <table class="almaseo-docs-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Feature', 'almaseo-seo-playground' ); ?></th>
                    <th><?php esc_html_e( 'AI Enhancement', 'almaseo-seo-playground' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php esc_html_e( 'Keyword Suggestions', 'almaseo-seo-playground' ); ?></td>
                    <td><?php esc_html_e( 'AI keywords with search volume, competition level, search intent classification, and trend direction', 'almaseo-seo-playground' ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Headline Analyzer', 'almaseo-seo-playground' ); ?></td>
                    <td><?php esc_html_e( 'CTR prediction, emotional impact analysis, competitor headline comparison, AI rewrite suggestions', 'almaseo-seo-playground' ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Readability Analysis', 'almaseo-seo-playground' ); ?></td>
                    <td><?php esc_html_e( 'SERP competitor readability benchmarks, per-paragraph AI improvement suggestions', 'almaseo-seo-playground' ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Image SEO', 'almaseo-seo-playground' ); ?></td>
                    <td><?php esc_html_e( 'Context-aware AI-generated alt text with confidence scores and decorative image detection', 'almaseo-seo-playground' ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Cornerstone Content', 'almaseo-seo-playground' ); ?></td>
                    <td><?php esc_html_e( 'Auto-suggest cornerstone posts based on traffic, backlinks, word count, and internal link analysis', 'almaseo-seo-playground' ); ?></td>
                </tr>
            </tbody>
        </table>
    </div>


    <!-- ════════════════════════════════════════════════════════════ -->
    <!-- Pro Features (informational section)                        -->
    <!-- ════════════════════════════════════════════════════════════ -->
    <div class="almaseo-docs-info-section" id="cat-pro">
        <h2><?php esc_html_e( 'Pro Features', 'almaseo-seo-playground' ); ?></h2>
        <p><?php esc_html_e( 'For teams needing advanced automation and intelligence. Pro features require a license.', 'almaseo-seo-playground' ); ?></p>
        <table class="almaseo-docs-table">
            <thead>
                <tr>
                    <th><?php esc_html_e( 'Feature', 'almaseo-seo-playground' ); ?></th>
                    <th><?php esc_html_e( 'Description', 'almaseo-seo-playground' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php esc_html_e( 'Internal Links', 'almaseo-seo-playground' ); ?></td>
                    <td><?php esc_html_e( 'Automated internal link insertion with guardrails (max links, no duplicates, anchor consistency)', 'almaseo-seo-playground' ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Content Refresh Drafts', 'almaseo-seo-playground' ); ?></td>
                    <td><?php esc_html_e( 'AI-powered content refresh with side-by-side diff review and selective merge', 'almaseo-seo-playground' ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Refresh Queue', 'almaseo-seo-playground' ); ?></td>
                    <td><?php esc_html_e( 'Priority-ranked content update queue based on business value, traffic decline, conversion intent', 'almaseo-seo-playground' ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Date Hygiene Scanner', 'almaseo-seo-playground' ); ?></td>
                    <td><?php esc_html_e( 'Passage-level stale content detection (old years, dated phrases, outdated prices)', 'almaseo-seo-playground' ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'E-E-A-T Enforcement', 'almaseo-seo-playground' ); ?></td>
                    <td><?php esc_html_e( 'Scan for trust signal gaps: missing author, bio, credentials, citations, review attribution', 'almaseo-seo-playground' ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'GSC Monitor', 'almaseo-seo-playground' ); ?></td>
                    <td><?php esc_html_e( 'Track indexation drift, rich result changes, and Google snippet rewrites', 'almaseo-seo-playground' ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( '404 Intelligence', 'almaseo-seo-playground' ); ?></td>
                    <td><?php esc_html_e( 'Smart redirect suggestions, spike detection, impact scoring, redirect chain detection', 'almaseo-seo-playground' ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Orphan Page Detection', 'almaseo-seo-playground' ); ?></td>
                    <td><?php esc_html_e( 'Find pages with zero internal links pointing to them, cluster analysis, hub candidate identification', 'almaseo-seo-playground' ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Schema Drift Monitor', 'almaseo-seo-playground' ); ?></td>
                    <td><?php esc_html_e( 'Detect when structured data changes unexpectedly after plugin/theme updates', 'almaseo-seo-playground' ); ?></td>
                </tr>
                <tr>
                    <td><?php esc_html_e( 'Featured Snippet Targeting', 'almaseo-seo-playground' ); ?></td>
                    <td><?php esc_html_e( 'Win Google\'s "position zero" with draft editor, live preview, and one-click content insertion', 'almaseo-seo-playground' ); ?></td>
                </tr>
            </tbody>
        </table>
    </div>

</div><!-- .almaseo-docs-wrap -->


<!-- ── Inline JavaScript: Accordion, Search, TOC Navigation ── -->
<script>
(function() {
    'use strict';

    // ── Accordion Toggle ──
    var headers = document.querySelectorAll('.almaseo-docs-cat-header');
    headers.forEach(function(header) {
        header.addEventListener('click', function() {
            var targetId = this.getAttribute('data-target');
            var body = document.getElementById(targetId);
            if (!body) return;

            var isCollapsed = this.classList.contains('collapsed');
            if (isCollapsed) {
                body.style.display = 'block';
                this.classList.remove('collapsed');
            } else {
                body.style.display = 'none';
                this.classList.add('collapsed');
            }
        });
    });

    // ── Search Filter ──
    var searchInput = document.getElementById('almaseo-docs-search-input');
    var resultCount = document.getElementById('almaseo-docs-result-count');
    var noResults = document.getElementById('almaseo-docs-no-results');
    var toc = document.getElementById('almaseo-docs-toc');
    var categories = document.querySelectorAll('.almaseo-docs-category');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var query = this.value.toLowerCase().trim();

            if (!query) {
                // Reset: show everything
                categories.forEach(function(cat) {
                    cat.style.display = '';
                    var body = cat.querySelector('.almaseo-docs-cat-body');
                    var header = cat.querySelector('.almaseo-docs-cat-header');
                    if (body) body.style.display = '';
                    if (header) header.classList.remove('collapsed');
                    var features = cat.querySelectorAll('.almaseo-docs-feature');
                    features.forEach(function(f) { f.style.display = ''; });
                });
                resultCount.style.display = 'none';
                noResults.style.display = 'none';
                toc.style.display = '';
                return;
            }

            toc.style.display = 'none';
            var totalVisible = 0;

            categories.forEach(function(cat) {
                var features = cat.querySelectorAll('.almaseo-docs-feature');
                var catVisible = 0;

                features.forEach(function(feature) {
                    var searchData = (feature.getAttribute('data-search') || '') + ' ' +
                                    (feature.textContent || '').toLowerCase();
                    if (searchData.indexOf(query) !== -1) {
                        feature.style.display = '';
                        catVisible++;
                    } else {
                        feature.style.display = 'none';
                    }
                });

                if (catVisible > 0) {
                    cat.style.display = '';
                    var body = cat.querySelector('.almaseo-docs-cat-body');
                    var header = cat.querySelector('.almaseo-docs-cat-header');
                    if (body) body.style.display = 'block';
                    if (header) header.classList.remove('collapsed');
                } else {
                    cat.style.display = 'none';
                }

                totalVisible += catVisible;
            });

            if (totalVisible > 0) {
                resultCount.textContent = totalVisible + ' feature' + (totalVisible !== 1 ? 's' : '') + ' found';
                resultCount.style.display = 'block';
                noResults.style.display = 'none';
            } else {
                resultCount.style.display = 'none';
                noResults.style.display = 'block';
            }
        });
    }

    // ── TOC Navigation ──
    var tocLinks = document.querySelectorAll('.almaseo-docs-toc a');
    tocLinks.forEach(function(link) {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            var targetId = this.getAttribute('href').substring(1);
            var target = document.getElementById(targetId);
            if (!target) return;

            // Expand if collapsed
            var header = target.querySelector('.almaseo-docs-cat-header');
            var body = target.querySelector('.almaseo-docs-cat-body');
            if (header && header.classList.contains('collapsed') && body) {
                body.style.display = 'block';
                header.classList.remove('collapsed');
            }

            // Scroll into view
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
    });
})();
</script>
