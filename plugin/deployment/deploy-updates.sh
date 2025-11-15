#!/bin/bash
#
# AlmaSEO Update Deployment Script
# Deploy update JSONs and ZIPs to api.almaseo.com
#

set -e

# Configuration
API_SERVER="api.almaseo.com"
API_PATH="/var/www/api/updates"
SSH_USER="deploy"
LOCAL_DIR="$(dirname "$0")"

# Colors
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

echo "================================"
echo "AlmaSEO Update Deployment"
echo "================================"
echo ""

# Function to deploy files
deploy_file() {
    local file=$1
    local remote_path=$2
    
    echo -n "Deploying $file... "
    
    if [ -f "$file" ]; then
        scp "$file" "$SSH_USER@$API_SERVER:$remote_path/" && echo -e "${GREEN}✓${NC}" || echo -e "${RED}✗${NC}"
    else
        echo -e "${RED}File not found${NC}"
        return 1
    fi
}

# Check for required files
echo "Checking files..."

STABLE_JSON="$LOCAL_DIR/stable.json"
BETA_JSON="$LOCAL_DIR/beta.json"
STABLE_ZIP="$LOCAL_DIR/../dist/almaseo-seo-playground-5.0.0.zip"
BETA_ZIP="$LOCAL_DIR/../dist/almaseo-seo-playground-5.0.1-beta.1.zip"

if [ ! -f "$STABLE_JSON" ]; then
    echo -e "${RED}✗ stable.json not found${NC}"
    exit 1
fi

if [ ! -f "$BETA_JSON" ]; then
    echo -e "${RED}✗ beta.json not found${NC}"
    exit 1
fi

if [ ! -f "$STABLE_ZIP" ]; then
    echo -e "${YELLOW}⚠ Stable ZIP not found. Creating...${NC}"
    cd "$LOCAL_DIR/.."
    ./release-package.sh
    STABLE_ZIP="$LOCAL_DIR/../dist/almaseo-seo-playground-5.0.0.zip"
fi

if [ ! -f "$BETA_ZIP" ]; then
    echo -e "${YELLOW}⚠ Beta ZIP not found. Creating...${NC}"
    # Create beta version
    cd "$LOCAL_DIR/.."
    
    # Bump version for beta
    sed -i.bak 's/Version: 5.0.0/Version: 5.0.1-beta.1/' alma-seoconnector.php
    sed -i.bak "s/'5.0.0'/'5.0.1-beta.1'/g" alma-seoconnector.php
    
    # Build beta package
    rm -rf dist/almaseo-seo-playground-5.0.1-beta.1*
    mkdir -p dist/almaseo-seo-playground-5.0.1-beta.1
    
    rsync -a \
        --exclude='.git*' \
        --exclude='deployment' \
        --exclude='tools' \
        --exclude='dist' \
        --exclude='*.bak' \
        --exclude='*.log' \
        . dist/almaseo-seo-playground-5.0.1-beta.1/
    
    cd dist
    zip -qr almaseo-seo-playground-5.0.1-beta.1.zip almaseo-seo-playground-5.0.1-beta.1
    
    # Restore version
    cd ..
    mv alma-seoconnector.php.bak alma-seoconnector.php
    
    BETA_ZIP="$LOCAL_DIR/../dist/almaseo-seo-playground-5.0.1-beta.1.zip"
fi

echo -e "${GREEN}✓ All files present${NC}"
echo ""

# Deploy files
echo "Deploying to $API_SERVER..."

deploy_file "$STABLE_JSON" "$API_PATH"
deploy_file "$BETA_JSON" "$API_PATH"
deploy_file "$STABLE_ZIP" "$API_PATH"
deploy_file "$BETA_ZIP" "$API_PATH"

echo ""
echo "Setting up endpoint routing..."

# Create endpoint PHP file
cat > /tmp/almaseo-sitemap.json.php << 'EOF'
<?php
/**
 * AlmaSEO Update Endpoint
 * Serves appropriate JSON based on channel parameter
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Cache-Control: max-age=3600');

$channel = isset($_GET['channel']) ? $_GET['channel'] : 'stable';

if (!in_array($channel, ['stable', 'beta'])) {
    $channel = 'stable';
}

$json_file = __DIR__ . '/' . $channel . '.json';

if (!file_exists($json_file)) {
    http_response_code(404);
    echo json_encode(['error' => 'Channel not found']);
    exit;
}

// Add request tracking
$log = date('Y-m-d H:i:s') . " | " . $_SERVER['REMOTE_ADDR'] . " | " . $channel . " | " . $_SERVER['HTTP_USER_AGENT'] . "\n";
file_put_contents(__DIR__ . '/requests.log', $log, FILE_APPEND | LOCK_EX);

// Output JSON
readfile($json_file);
EOF

echo -n "Deploying endpoint handler... "
scp /tmp/almaseo-sitemap.json.php "$SSH_USER@$API_SERVER:$API_PATH/almaseo-sitemap.json" && echo -e "${GREEN}✓${NC}" || echo -e "${RED}✗${NC}"

echo ""
echo "Creating .htaccess rules..."

cat > /tmp/htaccess_rules << 'EOF'
# AlmaSEO Update Endpoint Rules
RewriteEngine On

# Route almaseo-sitemap.json to PHP handler
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^almaseo-sitemap\.json$ almaseo-sitemap.json.php [L]

# Prevent directory listing
Options -Indexes

# Cache control for ZIPs
<FilesMatch "\.(zip)$">
    Header set Cache-Control "max-age=31536000, public"
</FilesMatch>

# Cache control for JSONs
<FilesMatch "\.(json)$">
    Header set Cache-Control "max-age=3600, public"
</FilesMatch>
EOF

echo -n "Deploying .htaccess... "
scp /tmp/htaccess_rules "$SSH_USER@$API_SERVER:$API_PATH/.htaccess" && echo -e "${GREEN}✓${NC}" || echo -e "${RED}✗${NC}"

echo ""
echo "Testing endpoints..."

# Test stable channel
echo -n "Testing stable channel... "
STABLE_TEST=$(curl -s "https://$API_SERVER/updates/almaseo-sitemap.json?channel=stable" | grep -o '"version":"[^"]*"' | cut -d'"' -f4)
if [ "$STABLE_TEST" = "5.0.0" ]; then
    echo -e "${GREEN}✓ Returns 5.0.0${NC}"
else
    echo -e "${RED}✗ Unexpected version: $STABLE_TEST${NC}"
fi

# Test beta channel
echo -n "Testing beta channel... "
BETA_TEST=$(curl -s "https://$API_SERVER/updates/almaseo-sitemap.json?channel=beta" | grep -o '"version":"[^"]*"' | cut -d'"' -f4)
if [ "$BETA_TEST" = "5.0.1-beta.1" ]; then
    echo -e "${GREEN}✓ Returns 5.0.1-beta.1${NC}"
else
    echo -e "${RED}✗ Unexpected version: $BETA_TEST${NC}"
fi

echo ""
echo "================================"
echo "Deployment Complete!"
echo "================================"
echo ""
echo "Endpoints active:"
echo "  Stable: https://$API_SERVER/updates/almaseo-sitemap.json?channel=stable"
echo "  Beta:   https://$API_SERVER/updates/almaseo-sitemap.json?channel=beta"
echo ""
echo "Downloads available:"
echo "  Stable: https://$API_SERVER/updates/almaseo-seo-playground-5.0.0.zip"
echo "  Beta:   https://$API_SERVER/updates/almaseo-seo-playground-5.0.1-beta.1.zip"
echo ""
echo "Next steps:"
echo "1. Test update on staging site"
echo "2. Switch test sites to beta channel"
echo "3. Monitor requests.log for adoption"