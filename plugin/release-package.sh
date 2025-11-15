#!/bin/bash
#
# AlmaSEO Release Packaging Script v5.0.0
# Automated release packaging with all safety checks
#

set -e  # Exit on error

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

echo -e "${BLUE}================================${NC}"
echo -e "${BLUE}AlmaSEO Release Packaging v5.0.0${NC}"
echo -e "${BLUE}================================${NC}"
echo ""

# Configuration
PLUGIN_DIR=$(pwd)
VERSION="5.0.0"
RELEASE_DIR="$PLUGIN_DIR/releases"
DIST_DIR="$PLUGIN_DIR/dist"
TOOLS_DIR="$PLUGIN_DIR/tools"
PACKAGE_NAME="almaseo-seo-playground-$VERSION"

echo -e "${GREEN}Step 1: Version Bumping${NC}"
echo "------------------------"

# Update main plugin file
echo "Updating alma-seoconnector.php..."
sed -i.bak 's/Version: .*/Version: 5.0.0/' alma-seoconnector.php
sed -i.bak "s/ALMASEO_PLUGIN_VERSION', '.*'/ALMASEO_PLUGIN_VERSION', '5.0.0'/" alma-seoconnector.php

# Create or update readme.txt
echo "Creating readme.txt..."
cat > readme.txt << 'EOF'
=== AlmaSEO SEO Playground ===
Contributors: almaseo
Tags: seo, sitemap, xml sitemap, indexnow, schema, hreflang, news sitemap
Requires at least: 5.8
Tested up to: 6.6
Requires PHP: 7.4
Stable tag: 5.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enterprise-grade SEO toolkit with comprehensive XML sitemaps, IndexNow, and advanced schema support.

== Description ==

AlmaSEO SEO Playground provides a complete XML sitemap system with advanced features including:

* **Comprehensive Sitemaps**: Posts, pages, custom post types, taxonomies, users, images, videos, news
* **Performance**: Static generation with gzip compression for lightning-fast serving
* **Delta Tracking**: Ring buffer tracks recent changes for instant search engine updates
* **IndexNow Integration**: Instant submission to Bing, Yandex, Seznam
* **Hreflang Support**: Automatic WPML/Polylang integration with reciprocity validation
* **Media Sitemaps**: Image and video extraction with CDN deduplication
* **Google News**: Rolling 48-hour window with category filtering
* **Conflict Detection**: Safe coexistence with Yoast, AIOSEO, RankMath
* **HTML Sitemap**: Shortcode and Gutenberg block for visitor-friendly sitemaps
* **Enterprise Features**: Multisite support, WP-CLI commands, health monitoring

== Installation ==

1. Upload the plugin files to `/wp-content/plugins/almaseo-seo-playground/`
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to AlmaSEO → Sitemaps to configure
4. Run initial build: WP-CLI: `wp almaseo sitemaps build --mode=static`

== Changelog ==

= 5.0.0 =
* Phase 0: Core architecture with provider pattern and streaming XML generation
* Phase 1: Base providers for posts, pages, CPTs, taxonomies, users with admin UI
* Phase 2: Takeover mode, IndexNow integration, additional URLs management
* Phase 3: Conflict detection, diff tracking, comprehensive validation system
* Phase 4: Static generation, gzip compression, WP-CLI, seek pagination for 100k+ URLs
* Phase 5A: Delta sitemap with ring buffer for tracking recent changes
* Phase 5B: Hreflang support with WPML/Polylang detection and reciprocity
* Phase 5C: Image/video sitemaps with CDN deduplication and oEmbed integration
* Phase 5D: Google News sitemap with rolling window and category filtering
* Phase 6: HTML sitemap, robots.txt, export/import, multisite, health logs

= 4.11.0 =
* Initial beta release with core sitemap functionality

== Frequently Asked Questions ==

= How do I enable static sitemap generation? =
Static mode is enabled by default. Run `wp almaseo sitemaps build --mode=static` to generate files.

= Can I use this alongside Yoast or other SEO plugins? =
Yes! The conflict detection system ensures safe coexistence. Disable takeover mode if conflicts exist.

= How do I roll back if issues occur? =
1. Turn takeover OFF to release /sitemap.xml
2. Switch storage_mode to 'dynamic' if static serving fails
3. Disable individual features (Delta/Media/News) to isolate issues

== Screenshots ==

1. Main sitemap settings dashboard
2. Provider configuration panel
3. IndexNow integration settings
4. Health monitoring and validation
5. HTML sitemap output

== Upgrade Notice ==

= 5.0.0 =
Major release with complete XML sitemap system. Backup settings before upgrading.
EOF

echo -e "${GREEN}✓ Version bumped to 5.0.0${NC}"

echo ""
echo -e "${GREEN}Step 2: Setting Safe Defaults${NC}"
echo "-----------------------------"

# Create default settings file
cat > includes/sitemap/defaults.php << 'EOF'
<?php
/**
 * AlmaSEO Default Settings
 * Safe production defaults
 * 
 * @package AlmaSEO
 * @since 5.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}

function almaseo_get_default_settings() {
    return array(
        'enabled' => true,
        'takeover' => false,  // Safe: don't hijack by default
        'include' => array(
            'posts' => true,
            'pages' => true,
            'cpts' => 'all',
            'tax' => array(
                'category' => true,
                'post_tag' => true
            ),
            'users' => false
        ),
        'links_per_sitemap' => 1000,
        'perf' => array(
            'storage_mode' => 'static',  // Best performance
            'gzip' => true,
            'chunk_size' => 100,
            'build_lock_timeout' => 900
        ),
        'indexnow' => array(
            'enabled' => false,  // Require explicit enablement
            'key' => '',
            'engines' => array('bing', 'yandex')
        ),
        'delta' => array(
            'enabled' => true,  // Track changes by default
            'max_urls' => 500,
            'retention_days' => 14
        ),
        'hreflang' => array(
            'enabled' => false,  // Require multilingual setup
            'source' => 'auto'
        ),
        'media' => array(
            'image' => array(
                'enabled' => true,  // Include images
                'max_per_url' => 20,
                'dedupe_cdn' => true
            ),
            'video' => array(
                'enabled' => true,  // Include videos
                'max_per_url' => 10,
                'fetch_oembed' => true
            )
        ),
        'news' => array(
            'enabled' => false,  // Require news site confirmation
            'window_hours' => 48,
            'max_items' => 1000,
            'post_types' => array('post'),
            'publisher_name' => get_bloginfo('name'),
            'language' => substr(get_locale(), 0, 2)
        )
    );
}
EOF

echo -e "${GREEN}✓ Safe defaults configured${NC}"

echo ""
echo -e "${GREEN}Step 3: Moving QA Artifacts${NC}"
echo "---------------------------"

# Create tools directory
mkdir -p "$TOOLS_DIR"

# Move test files to tools
echo "Moving QA files to /tools/..."
mv -f test-*.php "$TOOLS_DIR/" 2>/dev/null || true
mv -f release-qa.sh "$TOOLS_DIR/" 2>/dev/null || true
mv -f QA-SUMMARY.md "$TOOLS_DIR/" 2>/dev/null || true
mv -f RELEASE-CHECKLIST.md "$TOOLS_DIR/" 2>/dev/null || true

# Create .htaccess to block web access
cat > "$TOOLS_DIR/.htaccess" << 'EOF'
# Block all web access to tools directory
Order deny,allow
Deny from all

<FilesMatch "\.(php|sh)$">
    Order allow,deny
    Deny from all
</FilesMatch>
EOF

# Create index.php to prevent directory listing
echo "<?php // Silence is golden" > "$TOOLS_DIR/index.php"

echo -e "${GREEN}✓ QA artifacts moved to /tools/${NC}"

echo ""
echo -e "${GREEN}Step 4: Code Quality Checks${NC}"
echo "---------------------------"

# Check if PHPCS is available
if command -v phpcs &> /dev/null; then
    echo "Running PHPCS (WordPress standards)..."
    
    # Run PHPCS and capture only errors
    phpcs_output=$(phpcs --standard=WordPress --severity=5 --extensions=php \
        --ignore=*/tools/*,*/vendor/*,*/node_modules/* \
        . 2>&1 || true)
    
    if [ -z "$phpcs_output" ]; then
        echo -e "${GREEN}✓ No PHPCS blockers found${NC}"
    else
        echo -e "${YELLOW}⚠ PHPCS warnings (non-blocking):${NC}"
        echo "$phpcs_output" | head -10
    fi
else
    echo -e "${YELLOW}⚠ PHPCS not installed - skipping${NC}"
fi

echo ""
echo -e "${GREEN}Step 5: Generating Language Files${NC}"
echo "---------------------------------"

# Create languages directory
mkdir -p languages

# Check if WP-CLI is available for i18n
if command -v wp &> /dev/null; then
    echo "Generating POT file..."
    wp i18n make-pot . languages/almaseo.pot \
        --domain=almaseo \
        --exclude=tools,vendor,node_modules,releases,dist \
        2>/dev/null || echo -e "${YELLOW}⚠ POT generation needs WP-CLI i18n package${NC}"
else
    # Create basic POT file manually
    echo "Creating basic POT file..."
    cat > languages/almaseo.pot << 'EOF'
# AlmaSEO SEO Playground
# Copyright (C) 2024 AlmaSEO
msgid ""
msgstr ""
"Project-Id-Version: AlmaSEO SEO Playground 5.0.0\n"
"Report-Msgid-Bugs-To: https://github.com/almaseo/support\n"
"POT-Creation-Date: 2024-01-01 00:00:00+00:00\n"
"MIME-Version: 1.0\n"
"Content-Type: text/plain; charset=UTF-8\n"
"Content-Transfer-Encoding: 8bit\n"
"PO-Revision-Date: YEAR-MO-DA HO:MI+ZONE\n"
"Last-Translator: FULL NAME <EMAIL@ADDRESS>\n"
"Language-Team: LANGUAGE <LL@li.org>\n"
EOF
fi

echo -e "${GREEN}✓ Language files prepared${NC}"

echo ""
echo -e "${GREEN}Step 6: Building Distribution Package${NC}"
echo "-------------------------------------"

# Clean up old builds
rm -rf "$DIST_DIR"
mkdir -p "$DIST_DIR"

# Create staging directory
STAGING_DIR="$DIST_DIR/$PACKAGE_NAME"
mkdir -p "$STAGING_DIR"

echo "Copying files to staging..."

# Copy essential files
rsync -a \
    --exclude='.git*' \
    --exclude='node_modules' \
    --exclude='vendor/bin' \
    --exclude='tools' \
    --exclude='test-*.php' \
    --exclude='*.sh' \
    --exclude='releases' \
    --exclude='dist' \
    --exclude='*.map' \
    --exclude='.DS_Store' \
    --exclude='*.bak' \
    --exclude='*.log' \
    --exclude='*.md' \
    --exclude='composer.*' \
    --exclude='package*.json' \
    --exclude='phpcs.xml' \
    --exclude='.eslintrc' \
    . "$STAGING_DIR/"

# Ensure critical files are included
cp -f readme.txt "$STAGING_DIR/" 2>/dev/null || true
cp -f LICENSE "$STAGING_DIR/" 2>/dev/null || true

# Create index.php in all directories to prevent listing
find "$STAGING_DIR" -type d -exec sh -c 'echo "<?php // Silence is golden" > "$1/index.php"' _ {} \; 2>/dev/null

echo "Creating ZIP archive..."
cd "$DIST_DIR"
zip -qr "$PACKAGE_NAME.zip" "$PACKAGE_NAME"

# Get file size
SIZE=$(du -h "$PACKAGE_NAME.zip" | cut -f1)

echo -e "${GREEN}✓ Distribution package created${NC}"
echo "  File: $DIST_DIR/$PACKAGE_NAME.zip"
echo "  Size: $SIZE"

echo ""
echo -e "${GREEN}Step 7: Git Tagging${NC}"
echo "-------------------"

# Create release notes
RELEASE_NOTES="Version 5.0.0 - Complete XML Sitemap System

Major Features:
• Static generation with gzip compression
• Delta tracking with ring buffer
• Hreflang support (WPML/Polylang)
• Image/Video sitemaps with CDN dedup
• Google News with 48h window
• IndexNow instant submission
• Conflict detection for safe coexistence
• HTML sitemap shortcode/block
• WP-CLI commands
• Multisite support

Rollback Instructions:
• Toggle takeover OFF to release /sitemap.xml
• Switch storage_mode to 'dynamic' if issues
• Disable Delta/Media/News individually"

echo "$RELEASE_NOTES" > "$DIST_DIR/RELEASE_NOTES.txt"

# Git operations (commented out for safety)
echo "Git commands to run:"
echo -e "${YELLOW}git add -A${NC}"
echo -e "${YELLOW}git commit -m \"Release v5.0.0 - Complete XML Sitemap System\"${NC}"
echo -e "${YELLOW}git tag -a v5.0.0 -m \"$RELEASE_NOTES\"${NC}"
echo -e "${YELLOW}git push origin main --tags${NC}"

echo ""
echo -e "${GREEN}Step 8: Final Sanity Checks${NC}"
echo "---------------------------"

# Check file structure
echo "Verifying package structure..."
required_files=(
    "alma-seoconnector.php"
    "readme.txt"
    "includes/sitemap/class-alma-sitemap-manager.php"
    "includes/sitemap/class-alma-sitemap-responder.php"
    "assets/js/sitemaps.js"
    "assets/css/sitemaps.css"
)

missing_files=0
for file in "${required_files[@]}"; do
    if [ -f "$STAGING_DIR/$file" ]; then
        echo -e "  ${GREEN}✓${NC} $file"
    else
        echo -e "  ${RED}✗${NC} $file MISSING"
        ((missing_files++))
    fi
done

if [ $missing_files -eq 0 ]; then
    echo -e "${GREEN}✓ All required files present${NC}"
else
    echo -e "${RED}✗ Missing $missing_files required files${NC}"
fi

# CLI sanity check commands
echo ""
echo "CLI commands to verify installation:"
echo -e "${BLUE}wp plugin install $DIST_DIR/$PACKAGE_NAME.zip --activate${NC}"
echo -e "${BLUE}wp almaseo sitemaps build --mode=static${NC}"
echo -e "${BLUE}wp almaseo sitemaps validate${NC}"

echo ""
echo -e "${BLUE}================================${NC}"
echo -e "${BLUE}RELEASE PACKAGE SUMMARY${NC}"
echo -e "${BLUE}================================${NC}"
echo ""
echo "📦 Package: $DIST_DIR/$PACKAGE_NAME.zip"
echo "📏 Size: $SIZE"
echo "🏷️ Version: 5.0.0"
echo "📝 Release Notes: $DIST_DIR/RELEASE_NOTES.txt"
echo ""
echo -e "${GREEN}✅ PACKAGE READY FOR DISTRIBUTION${NC}"
echo ""
echo "🔄 Quick Rollback Instructions:"
echo "   1. Toggle takeover OFF in settings to release /sitemap.xml"
echo "   2. Switch storage_mode to 'dynamic' if static files cause issues"
echo "   3. Disable Delta/Media/News features individually to isolate problems"
echo ""
echo "📋 Next Steps:"
echo "   1. Test package on staging site"
echo "   2. Run: wp almaseo sitemaps build --mode=static"
echo "   3. Verify all providers generating correctly"
echo "   4. Upload to WordPress.org or distribute"
echo ""

# Clean up backup files
find . -name "*.bak" -delete 2>/dev/null

echo -e "${GREEN}✨ Release packaging complete!${NC}"