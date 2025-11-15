# AlmaSEO Closed Beta Rollout Plan

## 🚀 Launch Timeline

### Pre-Launch (Day 0)
- [x] Prepare stable.json (5.0.0)
- [x] Prepare beta.json (5.0.1-beta.1)
- [x] Create deployment scripts
- [ ] Deploy to api.almaseo.com
- [ ] Test endpoints
- [ ] Prepare beta tester communication

### Week 1: Initial Rollout (5-10 Sites)

#### Day 1-2: Deploy Infrastructure
```bash
# Deploy updates to server
./deployment/deploy-updates.sh

# Verify endpoints
curl https://api.almaseo.com/updates/almaseo-sitemap.json?channel=stable
curl https://api.almaseo.com/updates/almaseo-sitemap.json?channel=beta
```

#### Day 3-7: First Beta Sites

**Target Sites Mix:**
- 2x Small blogs (<1k pages)
- 2x Medium sites (1k-10k pages)
- 2x Large sites (10k+ pages)
- 1x WooCommerce site
- 1x Multisite installation
- 2x Different hosting providers (WPEngine, Kinsta, etc.)

**Activation per site:**
```bash
# Install 5.0.0 stable
wp plugin install https://api.almaseo.com/updates/almaseo-seo-playground-5.0.0.zip --activate

# Switch to beta channel
wp eval "update_option('almaseo_update_settings',['channel'=>'beta']);"

# Trigger update check
wp eval "AlmaSEO_Update_Manager::get_instance()->check_for_updates();"

# Update to beta
wp plugin update almaseo-seo-playground

# Verify and test
wp almaseo sitemaps build --mode=static
wp almaseo sitemaps validate
```

### Week 2: Expanded Beta (25+ Sites)

#### Day 8-10: Fix Round
Based on Week 1 feedback:
1. Analyze Health Logs
2. Review Conflict reports
3. Fix critical issues
4. Release 5.0.1-beta.2 if needed

#### Day 11-14: Scale Up
- Add 15-20 more sites
- Include edge cases:
  - Sites with Yoast/AIOSEO active
  - WPML/Polylang multilingual sites
  - High-traffic news sites
  - Sites with 100k+ images

## 📊 Monitoring Checklist

### Daily Checks
- [ ] Review update request logs
- [ ] Check for error reports
- [ ] Monitor support channels
- [ ] Verify cron execution

### Per-Site Validation
```bash
# Health check
wp almaseo sitemaps validate

# Performance check
time wp almaseo sitemaps build --mode=static

# Memory usage
wp eval "echo 'Peak memory: ' . memory_get_peak_usage(true) / 1024 / 1024 . ' MB';"

# Export logs for analysis
wp eval "Alma_Health_Log::export_csv();"
```

## 🛡️ Guardrails & Safety

### Default Settings (Safe)
```php
'takeover' => false,          // Don't hijack other plugins
'storage_mode' => 'static',   // Best performance
'indexnow.enabled' => false,  // Require explicit setup
'news.enabled' => false       // Require publisher confirmation
```

### Emergency Procedures

#### 1. Halt Beta Updates
```json
// Update beta.json to lower version
{
  "version": "5.0.0",
  "download_url": "https://api.almaseo.com/updates/almaseo-seo-playground-5.0.0.zip"
}
```

#### 2. Fast Rollback (Per Site)
```bash
# Via WP-CLI
wp eval "update_option('almaseo_sitemap_settings', array_merge(get_option('almaseo_sitemap_settings',[]), ['takeover'=>false,'perf'=>['storage_mode'=>'dynamic']]));"

# Or disable completely
wp plugin deactivate almaseo-seo-playground
```

#### 3. Hotfix Deployment
```bash
# Update version in plugin
sed -i 's/5.0.1-beta.1/5.0.1-beta.2/' alma-seoconnector.php

# Build new package
./release-package.sh

# Update beta.json
vim deployment/beta.json  # Update version and download_url

# Deploy
./deployment/deploy-updates.sh
```

## 📈 Success Metrics

### Week 1 Goals
- ✅ 5+ sites successfully running beta
- ✅ Zero critical errors
- ✅ All validators green
- ✅ No memory issues on large sites
- ✅ Successful daily cron execution

### Week 2 Goals
- ✅ 20+ sites on beta
- ✅ <5 non-critical issues
- ✅ Performance within 10% of v5.0.0
- ✅ Positive tester feedback
- ✅ All conflicts properly detected

## 📋 Data Collection

### From Each Beta Site
1. **Health Log Export**
   ```bash
   wp eval "Alma_Health_Log::export_csv();" > site-health.csv
   ```

2. **Conflict Report**
   ```bash
   wp eval "print_r(Alma_Sitemap_Conflicts::scan());"
   ```

3. **Performance Metrics**
   ```bash
   wp eval "print_r(get_option('almaseo_sitemap_settings')['health']['last_build_stats']);"
   ```

4. **System Info**
   ```bash
   wp --info
   php -v
   mysql --version
   ```

## 🎯 Exit Criteria for Stable Release

### Must Have (All Required)
- [x] 2 weeks beta period completed
- [ ] 20+ production sites tested
- [ ] Zero critical bugs
- [ ] All validators passing
- [ ] No cron execution failures
- [ ] Memory usage <256MB on 100k URL sites
- [ ] Build time <60s for 50k URLs

### Should Have (80% Required)
- [ ] Positive feedback from 90% testers
- [ ] All planned features working
- [ ] Documentation complete
- [ ] Support team trained
- [ ] Rollback procedures tested

### Nice to Have
- [ ] Performance improvements validated
- [ ] Additional language translations
- [ ] Video tutorials created

## 📧 Beta Tester Communication

### Invitation Email Template
```
Subject: AlmaSEO 5.0 Beta Testing Invitation

You're invited to test AlmaSEO 5.0.1-beta.1!

What's New:
- 30% faster sitemap generation
- Improved memory management
- Better conflict resolution

To Join Beta:
1. Install AlmaSEO 5.0.0
2. Go to AlmaSEO → Sitemaps → Tools → Updates
3. Switch Channel to "Beta"
4. Click "Check for Updates Now"

Report Issues:
- Export Health Log
- Email: beta@almaseo.com

Thank you for helping improve AlmaSEO!
```

### Weekly Update Template
```
Subject: AlmaSEO Beta Week 1 Update

Beta Testers,

Week 1 Summary:
- X sites running beta
- Y issues identified and fixed
- Z performance improvement confirmed

Next Week:
- Rolling out 5.0.1-beta.2 with fixes
- Expanding to more testers

Keep the feedback coming!
```

## 🚦 Go/No-Go Decision Points

### Week 1 Review (Day 7)
- **GO**: <3 critical issues, all fixable
- **PAUSE**: 3-5 critical issues, need investigation
- **NO-GO**: >5 critical issues or data loss

### Week 2 Review (Day 14)
- **GO TO STABLE**: All exit criteria met
- **EXTEND BETA**: Minor issues remain
- **ROLLBACK**: Major issues discovered

## 📞 Emergency Contacts

- **Lead Developer**: [Contact]
- **DevOps**: [Contact]
- **Support Lead**: [Contact]
- **Beta Coordinator**: beta@almaseo.com

---

**Current Status**: Ready for deployment
**Next Action**: Deploy to api.almaseo.com and begin Week 1