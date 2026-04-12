#!/bin/bash
#
# AlmaSEO SEO Playground — Full Release Deploy Script
# Usage: ./deploy.sh 1.6.5
#
# This script handles the COMPLETE release pipeline:
#   1. Bumps version in plugin PHP file (header + both ALMASEO_PLUGIN_VERSION constants)
#   2. Commits and pushes the plugin repo
#   3. Zips the plugin (excluding dev/debug files)
#   4. Places zip in both download locations (dashboard + PUC auto-update)
#   5. Updates almaseo-sitemap.json for PUC auto-updates
#   6. Updates LATEST_PLAYGROUND_VERSION in dashboard.py
#   7. Restarts the AlmaSEO server
#

set -e

VERSION="$1"
PLUGIN_DIR="/root/SEO_Playground_Plugin/almaseo-seo-playground"
PLUGIN_FILE="${PLUGIN_DIR}/almaseo-seo-playground.php"
UPDATES_DIR="/var/www/api.almaseo.com/updates"
DOWNLOADS_DIR="/root/FULLY WORKING WITH MULTIPLE SITE OPTIONS/static/downloads"
DASHBOARD_PY="/root/FULLY WORKING WITH MULTIPLE SITE OPTIONS/dashboard.py"
ZIP_NAME="almaseo-seo-playground-v${VERSION}.zip"
JSON_FILE="${UPDATES_DIR}/almaseo-sitemap.json"

if [ -z "$VERSION" ]; then
    echo "Usage: ./deploy.sh <version>"
    echo "Example: ./deploy.sh 1.6.5"
    exit 1
fi

# Verify paths exist
if [ ! -d "$PLUGIN_DIR" ]; then
    echo "ERROR: Plugin directory not found: $PLUGIN_DIR"
    exit 1
fi

if [ ! -f "$DASHBOARD_PY" ]; then
    echo "ERROR: dashboard.py not found: $DASHBOARD_PY"
    exit 1
fi

echo "=== Deploying AlmaSEO SEO Playground v${VERSION} ==="
echo ""

# -------------------------------------------------------
# Step 1: Bump version in plugin PHP file
# -------------------------------------------------------
echo "[1/7] Bumping version in plugin PHP file..."

# Plugin header: Version: X.Y.Z
sed -i "s/^Version: .*/Version: ${VERSION}/" "$PLUGIN_FILE"

# Both ALMASEO_PLUGIN_VERSION constants
sed -i "s/define( 'ALMASEO_PLUGIN_VERSION', '[^']*' )/define( 'ALMASEO_PLUGIN_VERSION', '${VERSION}' )/" "$PLUGIN_FILE"
sed -i "s/define('ALMASEO_PLUGIN_VERSION',  '[^']*')/define('ALMASEO_PLUGIN_VERSION',  '${VERSION}')/" "$PLUGIN_FILE"

# Verify bump worked
HEADER_VER=$(grep "^Version:" "$PLUGIN_FILE" | head -1 | awk '{print $2}')
if [ "$HEADER_VER" != "$VERSION" ]; then
    echo "ERROR: Version bump failed in plugin header (got: $HEADER_VER)"
    exit 1
fi
echo "  Plugin header: ${HEADER_VER}"
echo "  Constants updated"

# -------------------------------------------------------
# Step 2: Commit and push plugin repo
# -------------------------------------------------------
echo "[2/7] Committing and pushing plugin repo..."
cd /root/SEO_Playground_Plugin/almaseo-seo-playground
git add -A
git commit -m "chore: bump version to ${VERSION}" 2>/dev/null || echo "  (no changes to commit)"
git push origin main 2>&1 | tail -1

# -------------------------------------------------------
# Step 3: Create zip with exclusions
# -------------------------------------------------------
echo "[3/7] Creating ${ZIP_NAME}..."
cd /root/SEO_Playground_Plugin
zip -r "/tmp/${ZIP_NAME}" almaseo-seo-playground/ \
    -x "almaseo-seo-playground/.git/*" \
       "almaseo-seo-playground/node_modules/*" \
       "almaseo-seo-playground/debug-component-isolator.php" \
       "almaseo-seo-playground/debug-reload-tracer.php" \
       "almaseo-seo-playground/*.log" \
    > /dev/null

# -------------------------------------------------------
# Step 4: Place zip in both locations
# -------------------------------------------------------
echo "[4/7] Placing zip files..."

# PUC auto-update location
cp "/tmp/${ZIP_NAME}" "${UPDATES_DIR}/${ZIP_NAME}"
echo "  -> ${UPDATES_DIR}/${ZIP_NAME}"

# Dashboard download location
cp "/tmp/${ZIP_NAME}" "${DOWNLOADS_DIR}/${ZIP_NAME}"
echo "  -> ${DOWNLOADS_DIR}/${ZIP_NAME}"

# Clean up temp
rm "/tmp/${ZIP_NAME}"

# -------------------------------------------------------
# Step 5: Update almaseo-sitemap.json for PUC
# -------------------------------------------------------
echo "[5/7] Updating almaseo-sitemap.json..."
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

# -------------------------------------------------------
# Step 6: Update LATEST_PLAYGROUND_VERSION in dashboard.py
# -------------------------------------------------------
echo "[6/7] Updating dashboard.py version constant..."
sed -i "s/^LATEST_PLAYGROUND_VERSION = '.*'/LATEST_PLAYGROUND_VERSION = '${VERSION}'/" "$DASHBOARD_PY"

DASH_VER=$(grep "^LATEST_PLAYGROUND_VERSION" "$DASHBOARD_PY" | head -1)
echo "  ${DASH_VER}"

# -------------------------------------------------------
# Step 7: Restart AlmaSEO server
# -------------------------------------------------------
echo "[7/7] Restarting AlmaSEO server..."
cd "/root/FULLY WORKING WITH MULTIPLE SITE OPTIONS"
./start_with_openai.sh > /tmp/deploy_restart.log 2>&1
echo "  Server restarted"

# -------------------------------------------------------
# Summary
# -------------------------------------------------------
ZIP_SIZE=$(du -h "${UPDATES_DIR}/${ZIP_NAME}" | cut -f1)
echo ""
echo "========================================="
echo "  Deploy complete: v${VERSION}"
echo "========================================="
echo "  Plugin header:    Version: ${VERSION}"
echo "  dashboard.py:     ${DASH_VER}"
echo "  PUC JSON:         ${JSON_FILE}"
echo "  PUC zip:          ${UPDATES_DIR}/${ZIP_NAME} (${ZIP_SIZE})"
echo "  Dashboard zip:    ${DOWNLOADS_DIR}/${ZIP_NAME}"
echo "  Auto-update URL:  https://api.almaseo.com/updates/${ZIP_NAME}"
echo ""
echo "Verify endpoints:"
echo "  curl -sI https://api.almaseo.com/updates/${ZIP_NAME} | head -3"
echo "  curl -s https://api.almaseo.com/updates/almaseo-sitemap.json | head -5"
