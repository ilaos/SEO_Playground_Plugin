/**
 * AlmaSEO Setup Wizard
 *
 * Single-page wizard with step navigation, REST saves via wp.apiFetch,
 * import detection, and completion handling.
 *
 * @package AlmaSEO
 * @since   8.2.0
 */

(function () {
    'use strict';

    /* ----------------------------------------------------------------
     *  State
     * ----------------------------------------------------------------*/

    var TOTAL_STEPS  = 7;
    var currentStep  = 1;
    var cfg          = window.almaseoWizard || {};
    var existing     = cfg.existing || {};
    var saving       = false;

    /* ----------------------------------------------------------------
     *  DOM references (resolved after DOMContentLoaded)
     * ----------------------------------------------------------------*/

    var $panels, $indicators, $prevBtn, $nextBtn, $skipBtn, $footer, $toast;

    /* ----------------------------------------------------------------
     *  Init
     * ----------------------------------------------------------------*/

    document.addEventListener('DOMContentLoaded', function () {
        $panels     = document.querySelectorAll('.almaseo-wizard-panel');
        $indicators = document.querySelectorAll('.almaseo-wizard-step-indicator');
        $prevBtn    = document.getElementById('wiz-prev');
        $nextBtn    = document.getElementById('wiz-next');
        $skipBtn    = document.getElementById('wiz-skip');
        $footer     = document.getElementById('wiz-footer');
        $toast      = document.getElementById('wiz-toast');

        // Populate dynamic UI.
        buildSeparatorPicker();
        buildSitemapCheckboxes();
        prefillExisting();

        // Bind navigation.
        $nextBtn.addEventListener('click', onNext);
        $prevBtn.addEventListener('click', onPrev);
        $skipBtn.addEventListener('click', onSkip);

        // Dashboard button on step 7.
        var dashBtn = document.getElementById('wiz-go-dashboard');
        if (dashBtn) {
            dashBtn.addEventListener('click', function (e) {
                e.preventDefault();
                completeWizard(cfg.dashboardPage);
            });
        }

        // Set initial UI.
        showStep(1);
    });

    /* ----------------------------------------------------------------
     *  Navigation
     * ----------------------------------------------------------------*/

    function showStep(step) {
        currentStep = step;

        // Panels.
        $panels.forEach(function (p) {
            p.style.display = (parseInt(p.dataset.step, 10) === step) ? '' : 'none';
        });

        // Indicators.
        $indicators.forEach(function (ind) {
            var s = parseInt(ind.dataset.step, 10);
            ind.classList.remove('is-active', 'is-done');
            if (s < step) {
                ind.classList.add('is-done');
            } else if (s === step) {
                ind.classList.add('is-active');
            }
        });

        // Previous button.
        $prevBtn.style.display = (step > 1 && step < TOTAL_STEPS) ? '' : 'none';

        // Footer visibility (hide on last step).
        $footer.style.display = (step === TOTAL_STEPS) ? 'none' : '';

        // Next button label.
        if (step === TOTAL_STEPS - 1) {
            $nextBtn.innerHTML = cfg.strings && cfg.strings.saved ? 'Finish' : 'Finish';
        } else {
            $nextBtn.innerHTML = 'Save &amp; Continue &rarr;';
        }

        // Trigger import detection when step 6 becomes visible.
        if (step === 6) {
            detectImportSources();
        }
    }

    function onPrev() {
        if (currentStep > 1) {
            showStep(currentStep - 1);
        }
    }

    function onSkip() {
        if (currentStep < TOTAL_STEPS) {
            showStep(currentStep + 1);
        }
    }

    function onNext() {
        if (saving) return;

        var data = collectStepData(currentStep);

        // If nothing to save (e.g. import step), just advance.
        if (data === null) {
            advanceOrFinish();
            return;
        }

        saveStep(currentStep, data, function () {
            advanceOrFinish();
        });
    }

    function advanceOrFinish() {
        if (currentStep < TOTAL_STEPS) {
            showStep(currentStep + 1);
        }
    }

    /* ----------------------------------------------------------------
     *  Data Collection
     * ----------------------------------------------------------------*/

    function collectStepData(step) {
        switch (step) {
            case 1:
                return collectSiteType();
            case 2:
                return collectSocialProfiles();
            case 3:
                return collectSearchAppearance();
            case 4:
                return collectSitemap();
            case 5:
                return collectVerification();
            case 6:
                // Import step — detection only, nothing to save.
                return null;
            default:
                return null;
        }
    }

    function collectSiteType() {
        var checked = document.querySelector('input[name="site_type"]:checked');
        return checked ? { site_type: checked.value } : null;
    }

    function collectSocialProfiles() {
        return {
            org_name:  val('wiz-org-name'),
            logo_url:  val('wiz-logo-url'),
            facebook:  val('wiz-facebook'),
            twitter:   val('wiz-twitter'),
            instagram: val('wiz-instagram'),
            linkedin:  val('wiz-linkedin'),
            youtube:   val('wiz-youtube'),
            pinterest: val('wiz-pinterest'),
        };
    }

    function collectSearchAppearance() {
        var selectedSep = document.querySelector('.almaseo-wizard-sep-option.is-selected');
        return {
            separator:           selectedSep ? selectedSep.dataset.sep : '-',
            homepage_title:      val('wiz-homepage-title'),
            homepage_description: val('wiz-homepage-desc'),
            post_title:          val('wiz-post-title'),
            page_title:          val('wiz-page-title'),
        };
    }

    function collectSitemap() {
        var enabled = document.getElementById('wiz-sitemap-enabled');
        var ptCheckboxes = document.querySelectorAll('#wiz-sitemap-types input[type="checkbox"]');
        var postTypes = {};
        ptCheckboxes.forEach(function (cb) {
            postTypes[cb.value] = cb.checked;
        });
        return {
            enabled: enabled ? enabled.checked : true,
            post_types: postTypes,
        };
    }

    function collectVerification() {
        return {
            google:    val('wiz-verify-google'),
            bing:      val('wiz-verify-bing'),
            pinterest: val('wiz-verify-pinterest'),
            yandex:    val('wiz-verify-yandex'),
            baidu:     val('wiz-verify-baidu'),
        };
    }

    /* ----------------------------------------------------------------
     *  REST helpers
     * ----------------------------------------------------------------*/

    function saveStep(step, data, onSuccess) {
        if (!data) {
            if (onSuccess) onSuccess();
            return;
        }

        saving = true;
        $nextBtn.disabled = true;
        $nextBtn.textContent = cfg.strings.saving || 'Saving...';

        wp.apiFetch({
            path: '/almaseo/v1/wizard/save-step',
            method: 'POST',
            data: { step: step, data: data },
        }).then(function () {
            saving = false;
            $nextBtn.disabled = false;
            toast(cfg.strings.saved || 'Saved!');
            if (onSuccess) onSuccess();
        }).catch(function (err) {
            saving = false;
            $nextBtn.disabled = false;
            $nextBtn.innerHTML = 'Save &amp; Continue &rarr;';
            toast(cfg.strings.error || 'Error saving.', true);
            console.error('AlmaSEO wizard save error:', err);
        });
    }

    function completeWizard(redirectUrl) {
        wp.apiFetch({
            path: '/almaseo/v1/wizard/complete',
            method: 'POST',
        }).then(function () {
            window.location.href = redirectUrl;
        }).catch(function () {
            // Still redirect even if the complete call fails.
            window.location.href = redirectUrl;
        });
    }

    /* ----------------------------------------------------------------
     *  Import Detection (Step 6)
     * ----------------------------------------------------------------*/

    var importDetected = false;

    function detectImportSources() {
        if (importDetected) return;
        importDetected = true;

        var container = document.getElementById('wiz-import-results');
        container.innerHTML = '<p class="almaseo-wizard-detecting">' + (cfg.strings.detecting || 'Detecting...') + '</p>';

        wp.apiFetch({
            path: '/almaseo/v1/import/detect',
            method: 'GET',
        }).then(function (sources) {
            renderImportSources(container, sources);
        }).catch(function () {
            container.innerHTML = '<p class="almaseo-wizard-no-sources">' + (cfg.strings.error || 'Could not detect sources.') + '</p>';
        });
    }

    function renderImportSources(container, sources) {
        // sources is an array of { slug, name, available, meta_count, post_count } objects.
        var available = [];
        if (Array.isArray(sources)) {
            available = sources.filter(function (s) { return s.available; });
        }

        if (available.length === 0) {
            container.innerHTML = '<div class="almaseo-wizard-no-sources">' + (cfg.strings.noSources || 'No SEO plugins detected.') + '</div>';
            return;
        }

        var html = '<div class="almaseo-wizard-import-cards">';
        available.forEach(function (source) {
            var count = source.meta_count || source.post_count || 0;
            html += '<div class="almaseo-wizard-import-card">';
            html +=   '<div class="almaseo-wizard-import-card-info">';
            html +=     '<strong>' + escHtml(source.name) + '</strong>';
            if (count > 0) {
                html += '<span>' + count + ' items available</span>';
            }
            html +=   '</div>';
            html +=   '<a href="' + escAttr(cfg.importPage) + '" target="_blank" class="almaseo-wizard-import-btn">Import</a>';
            html += '</div>';
        });
        html += '</div>';

        container.innerHTML = html;
    }

    /* ----------------------------------------------------------------
     *  Separator Picker Builder
     * ----------------------------------------------------------------*/

    function buildSeparatorPicker() {
        var picker = document.getElementById('wiz-separator-picker');
        if (!picker || !cfg.separators) return;

        var currentSep = (existing.searchAppearance && existing.searchAppearance.separator) || '-';

        cfg.separators.forEach(function (sep) {
            var el = document.createElement('button');
            el.type = 'button';
            el.className = 'almaseo-wizard-sep-option';
            el.dataset.sep = sep;
            el.textContent = sep;
            el.setAttribute('title', sep);

            if (sep === currentSep) {
                el.classList.add('is-selected');
            }

            el.addEventListener('click', function () {
                picker.querySelectorAll('.almaseo-wizard-sep-option').forEach(function (btn) {
                    btn.classList.remove('is-selected');
                });
                el.classList.add('is-selected');
            });

            picker.appendChild(el);
        });
    }

    /* ----------------------------------------------------------------
     *  Sitemap Checkboxes Builder
     * ----------------------------------------------------------------*/

    function buildSitemapCheckboxes() {
        var wrap = document.getElementById('wiz-sitemap-types');
        if (!wrap || !cfg.postTypes) return;

        var sitemapInclude = (existing.sitemap && existing.sitemap.include) || {};

        cfg.postTypes.forEach(function (pt) {
            var isChecked = true; // default enabled
            // Check existing settings.
            if (pt.name === 'post' && sitemapInclude.posts !== undefined) {
                isChecked = !!sitemapInclude.posts;
            } else if (pt.name === 'page' && sitemapInclude.pages !== undefined) {
                isChecked = !!sitemapInclude.pages;
            } else if (sitemapInclude[pt.name] !== undefined) {
                isChecked = !!sitemapInclude[pt.name];
            }

            var label = document.createElement('label');
            var cb = document.createElement('input');
            cb.type = 'checkbox';
            cb.value = pt.name;
            cb.checked = isChecked;
            var span = document.createElement('span');
            span.textContent = pt.label;
            label.appendChild(cb);
            label.appendChild(span);
            wrap.appendChild(label);
        });

        // Toggle visibility based on enabled checkbox.
        var enabledCb = document.getElementById('wiz-sitemap-enabled');
        var typesWrap = document.getElementById('wiz-sitemap-types-wrap');
        if (enabledCb && typesWrap) {
            function toggleTypes() {
                typesWrap.style.opacity = enabledCb.checked ? '1' : '0.4';
                typesWrap.style.pointerEvents = enabledCb.checked ? '' : 'none';
            }
            enabledCb.addEventListener('change', toggleTypes);

            // Set initial from existing.
            if (existing.sitemap && existing.sitemap.enabled !== undefined) {
                enabledCb.checked = !!existing.sitemap.enabled;
            }
            toggleTypes();
        }
    }

    /* ----------------------------------------------------------------
     *  Pre-fill existing settings
     * ----------------------------------------------------------------*/

    function prefillExisting() {
        // Step 1: site type.
        if (existing.siteType) {
            var radio = document.querySelector('input[name="site_type"][value="' + existing.siteType + '"]');
            if (radio) radio.checked = true;
        }

        // Step 2: social profiles.
        var schema = existing.schema || {};
        setVal('wiz-org-name', schema.site_name || '');
        setVal('wiz-logo-url', schema.site_logo_url || '');
        var social = schema.site_social_profiles || {};
        setVal('wiz-facebook', social.facebook || '');
        setVal('wiz-twitter', social.twitter || '');
        setVal('wiz-instagram', social.instagram || '');
        setVal('wiz-linkedin', social.linkedin || '');
        setVal('wiz-youtube', social.youtube || '');
        setVal('wiz-pinterest', social.pinterest || '');

        // Step 3: search appearance.
        var sa = existing.searchAppearance || {};
        var special = sa.special || {};
        var homepage = special.homepage || {};
        var postTypes = sa.post_types || {};
        var postSettings = postTypes.post || {};
        var pageSettings = postTypes.page || {};

        setVal('wiz-homepage-title', homepage.title_template || '');
        setVal('wiz-homepage-desc', homepage.description_template || '');
        setVal('wiz-post-title', postSettings.title_template || '');
        setVal('wiz-page-title', pageSettings.title_template || '');

        // Step 5: verification codes.
        var codes = existing.verification || {};
        setVal('wiz-verify-google', codes.google || '');
        setVal('wiz-verify-bing', codes.bing || '');
        setVal('wiz-verify-pinterest', codes.pinterest || '');
        setVal('wiz-verify-yandex', codes.yandex || '');
        setVal('wiz-verify-baidu', codes.baidu || '');
    }

    /* ----------------------------------------------------------------
     *  Toast
     * ----------------------------------------------------------------*/

    var toastTimer;

    function toast(msg, isError) {
        $toast.textContent = msg;
        $toast.classList.toggle('is-error', !!isError);
        $toast.classList.add('is-visible');

        clearTimeout(toastTimer);
        toastTimer = setTimeout(function () {
            $toast.classList.remove('is-visible');
        }, 2500);
    }

    /* ----------------------------------------------------------------
     *  Utility
     * ----------------------------------------------------------------*/

    function val(id) {
        var el = document.getElementById(id);
        return el ? el.value.trim() : '';
    }

    function setVal(id, value) {
        var el = document.getElementById(id);
        if (el) el.value = value;
    }

    function escHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    function escAttr(str) {
        return str.replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/'/g, '&#39;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
    }

})();
