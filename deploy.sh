#!/bin/bash
#
# AlmaSEO SEO Playground — Deploy Script
# Usage: ./deploy.sh 1.6.5
#

set -e

VERSION="$1"
PLUGIN_DIR="/root/SEO_Playground_Plugin/almaseo-seo-playground"
UPDATES_DIR="/var/www/api.almaseo.com/updates"
ZIP_NAME="almaseo-seo-playground-v${VERSION}.zip"
JSON_FILE="${UPDATES_DIR}/almaseo-sitemap.json"

if [ -z "$VERSION" ]; then
    echo "Usage: ./deploy.sh <version>"
    echo "Example: ./deploy.sh 1.6.5"
    exit 1
fi

# Verify plugin directory exists
if [ ! -d "$PLUGIN_DIR" ]; then
    echo "ERROR: Plugin directory not found: $PLUGIN_DIR"
    exit 1
fi

echo "=== Deploying AlmaSEO SEO Playground v${VERSION} ==="

# Create zip with exclusions
echo "Creating ${ZIP_NAME}..."
cd /root/SEO_Playground_Plugin
zip -r "/tmp/${ZIP_NAME}" almaseo-seo-playground/ \
    -x "almaseo-seo-playground/.git/*" \
       "almaseo-seo-playground/node_modules/*" \
       "almaseo-seo-playground/debug-component-isolator.php" \
       "almaseo-seo-playground/debug-reload-tracer.php" \
       "almaseo-seo-playground/*.log" \
    > /dev/null

# Move zip to updates directory
echo "Copying to ${UPDATES_DIR}..."
mv "/tmp/${ZIP_NAME}" "${UPDATES_DIR}/${ZIP_NAME}"

# Update almaseo-sitemap.json
echo "Updating almaseo-sitemap.json..."
cat > "$JSON_FILE" <<EOF
{
    "name": "AlmaSEO SEO Playground",
    "version": "${VERSION}",
    "download_url": "https://api.almaseo.com/updates/${ZIP_NAME}",
    "homepage": "https://almaseo.com",
    "requires": "5.6",
    "tested": "6.6",
    "requires_php": "7.4",
    "author": "AlmaSEO",
    "sections": {
        "description": "Professional SEO optimization plugin with AI-powered content generation, comprehensive keyword analysis, schema markup, and real-time SEO insights.",
        "changelog": "<h4>${VERSION}</h4><ul><li>Latest stable release</li></ul>"
    }
}
EOF

# Confirm
ZIP_SIZE=$(du -h "${UPDATES_DIR}/${ZIP_NAME}" | cut -f1)
echo ""
echo "=== Deploy complete ==="
echo "  Version:  ${VERSION}"
echo "  Zip:      ${UPDATES_DIR}/${ZIP_NAME} (${ZIP_SIZE})"
echo "  JSON:     ${JSON_FILE}"
echo "  URL:      https://api.almaseo.com/updates/${ZIP_NAME}"
echo ""
echo "Verify: curl -sI https://api.almaseo.com/updates/${ZIP_NAME} | head -3"
