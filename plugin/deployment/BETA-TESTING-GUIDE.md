# AlmaSEO v5.0.0 - Closed Beta Testing Guide

## 🚀 Welcome Beta Testers!

Thank you for participating in the AlmaSEO v5.0.0 closed beta. This guide will walk you through the preflight checks, installation, and testing procedures.

---

## 📋 Preflight Checklist

### 1. Server Requirements

Before installation, verify your server meets these requirements:

```
✓ PHP Version: ≥ 7.4
✓ WordPress: ≥ 6.3
✓ Memory Limit: ≥ 256M (recommended)
✓ XML Extensions: php-xml, php-xmlwriter
✓ Gzip Support: php-gzip (optional but recommended)
```

**Quick Check via WP-CLI:**
```bash
wp almaseo preflight --category=server_requirements
```

### 2. Folder Permissions

Ensure these directories are writable:

```
/wp-content/uploads/almaseo/
/wp-content/uploads/almaseo/sitemaps/
/wp-content/uploads/almaseo/cache/
/wp-content/uploads/almaseo/logs/
```

**Auto-create with proper permissions:**
```bash
mkdir -p wp-content/uploads/almaseo/{sitemaps,cache,logs}
chmod 755 wp-content/uploads/almaseo
chmod 755 wp-content/uploads/almaseo/*
```

### 3. Cache Plugin Configuration

If you're using any of these cache plugins, exclude sitemap URLs:

#### WP Rocket
- Go to Settings → WP Rocket → Advanced Rules
- Add to "Never Cache URLs": `*sitemap*.xml*`

#### LiteSpeed Cache
- Go to LiteSpeed Cache → Cache → Excludes
- Add to "Do Not Cache URIs": `/.*sitemap.*\.xml.*/`

#### Cloudflare
- Create Page Rule: `*yoursite.com/*sitemap*.xml*`
- Setting: Cache Level → Bypass

#### W3 Total Cache
- Go to Performance → Page Cache
- Add to "Never cache": `sitemap\.xml`

### 4. Cron Configuration

Verify cron is working:

```bash
# Check if WP-Cron is disabled
wp config get DISABLE_WP_CRON

# If disabled, ensure system cron is configured:
crontab -l | grep wp-cron.php
```

**Add system cron if needed:**
```bash
*/5 * * * * cd /path/to/wordpress && php wp-cron.php
```

---

## 📦 Installation

### Step 1: Upload and Activate

1. Download `almaseo-seo-playground-v5.0.0.zip`
2. Go to Plugins → Add New → Upload Plugin
3. Choose the ZIP file and click "Install Now"
4. Activate the plugin

### Step 2: Flush Permalinks

**Critical:** After activation, immediately flush permalinks:

1. Go to Settings → Permalinks
2. Click "Save Changes" (no need to change anything)

Or via WP-CLI:
```bash
wp rewrite flush
```

### Step 3: Verify Safe Defaults

Go to **AlmaSEO → Sitemaps** and confirm:

- ✅ **Takeover Mode:** OFF (for beta testing)
- ✅ **Storage Mode:** Static
- ✅ **Gzip Compression:** ON
- ✅ **IndexNow:** OFF (configure later if needed)

---

## 🧪 Testing Procedures

### Test 1: Static Build Generation

1. Go to **AlmaSEO → Sitemaps**
2. Click **"Build Static Sitemap"**
3. Wait for completion message
4. Verify files created:

```bash
ls -la wp-content/uploads/almaseo/sitemaps/
# Should see: sitemap-index.xml, sitemap-posts-1.xml, etc.
```

### Test 2: Sitemap Validation

1. Visit: `https://yoursite.com/almaseo-sitemap.xml`
2. Verify XML loads correctly
3. Check for proper structure:
   - Index file with sub-sitemaps
   - Each sub-sitemap has URLs with lastmod dates

**Validate with WP-CLI:**
```bash
wp almaseo sitemaps validate
```

### Test 3: Robots.txt Verification

1. Visit: `https://yoursite.com/robots.txt`
2. Look for single sitemap entry:
```
Sitemap: https://yoursite.com/almaseo-sitemap.xml
```
3. Ensure NO duplicate sitemap entries

### Test 4: Conflict Detection

If you have Yoast/RankMath/AIOSEO installed:

1. Keep **Takeover Mode OFF**
2. Verify warning message appears in admin
3. Check both sitemaps work:
   - AlmaSEO: `/almaseo-sitemap.xml`
   - Other plugin: `/sitemap.xml` or `/sitemap_index.xml`

### Test 5: IndexNow Setup (Optional)

1. Go to **AlmaSEO → Sitemaps → IndexNow**
2. Click **"Generate Key"**
3. Copy the key
4. Test key file: `https://yoursite.com/{key}.txt`
5. Enable IndexNow
6. Click **"Submit Now"** for manual test

### Test 6: Performance Testing

Compare static vs dynamic performance:

```bash
# Test static (should be fast)
time curl -s https://yoursite.com/almaseo-sitemap.xml > /dev/null

# Test dynamic (slower)
time curl -s https://yoursite.com/almaseo-sitemap.xml?mode=dynamic > /dev/null
```

### Test 7: Advanced Features (If Applicable)

#### Hreflang Support
- Only enable if using Polylang/WPML
- Check sitemap includes hreflang tags

#### News Sitemap
- Only for Google News approved sites
- Visit: `/news-sitemap.xml`

#### Media Attachments
- Enable to include images in sitemap
- Verify image URLs appear

---

## 🔍 Troubleshooting

### Common Issues and Solutions

#### 1. 404 Error on Sitemap URL

**Solution:** Flush permalinks again
```bash
wp rewrite flush
```

#### 2. Sitemap Not Updating

**Solution:** Rebuild static sitemap
```bash
wp almaseo sitemaps build --mode=static
```

#### 3. Permission Errors

**Solution:** Fix directory permissions
```bash
chmod -R 755 wp-content/uploads/almaseo/
chown -R www-data:www-data wp-content/uploads/almaseo/
```

#### 4. Memory Errors

**Solution:** Increase PHP memory limit
```php
// In wp-config.php
define('WP_MEMORY_LIMIT', '256M');
```

#### 5. Cache Interference

**Solution:** Clear all caches
```bash
wp cache flush
wp almaseo cache clear
```

---

## 📊 Automated Testing

### Run Full Test Suite

```bash
wp almaseo test
```

### Run Specific Tests

```bash
# Test only sitemaps
wp almaseo test --suite=sitemaps

# Test only conflicts
wp almaseo test --suite=conflicts
```

### Generate Test Report

```bash
wp almaseo test --format=json > test-report.json
```

---

## 📝 Reporting Issues

When reporting issues, please include:

1. **Preflight Report:**
```bash
wp almaseo preflight > preflight.txt
```

2. **Test Results:**
```bash
wp almaseo test > test-results.txt
```

3. **Debug Information:**
- PHP Version
- WordPress Version
- Active Plugins List
- Error messages (if any)

4. **Steps to Reproduce:**
- Detailed steps that led to the issue
- Expected behavior
- Actual behavior

---

## ✅ Beta Testing Checklist

Complete these tests and report results:

- [ ] Preflight checks pass
- [ ] Plugin activates without errors
- [ ] Static sitemap builds successfully
- [ ] Sitemap URL is accessible
- [ ] XML validates correctly
- [ ] Robots.txt has single sitemap entry
- [ ] No conflicts with SEO plugins (if present)
- [ ] Cache exclusions working
- [ ] Gzip compression creates .gz files
- [ ] Performance improvement confirmed
- [ ] Cron jobs scheduled
- [ ] Memory usage acceptable
- [ ] No PHP errors in logs

### Optional Features:
- [ ] IndexNow key file accessible
- [ ] Manual IndexNow submission works
- [ ] Hreflang tags present (if multilingual)
- [ ] News sitemap accessible (if enabled)
- [ ] Media URLs included (if enabled)
- [ ] Auto-updates check connectivity

---

## 🚨 Critical Notes

1. **DO NOT** enable Takeover Mode in production during beta
2. **DO NOT** delete existing sitemap plugins yet
3. **ALWAYS** keep backups before testing
4. **MONITOR** error logs during testing
5. **REPORT** any unexpected behavior immediately

---

## 📞 Support

For beta support:
- Slack: #almaseo-beta
- Email: beta@almaseo.com
- Include your beta tester ID in all communications

---

## 🎯 Quick Commands Reference

```bash
# Preflight check
wp almaseo preflight

# Build sitemap
wp almaseo sitemaps build --mode=static

# Validate sitemap
wp almaseo sitemaps validate

# Run tests
wp almaseo test

# Clear cache
wp almaseo cache clear

# Check status
wp almaseo status

# View recent logs
wp almaseo logs --lines=50
```

---

Thank you for helping us make AlmaSEO v5.0.0 the best it can be! 🚀