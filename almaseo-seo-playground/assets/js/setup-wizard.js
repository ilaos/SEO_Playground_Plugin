/**
 * AlmaSEO Setup Wizard
 *
 * Single-page wizard with step navigation, REST saves via wp.apiFetch,
 * and completion handling.
 *
 * @package AlmaSEO
 * @since   8.2.0
 */

(function () {
    'use strict';

    /* ----------------------------------------------------------------
     *  State
     * ----------------------------------------------------------------*/

    var TOTAL_STEPS  = 5;
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

        // Dashboard button on final step.
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

        // Mark wizard complete as soon as the final step is shown, so that
        // the "Deactivate Connector" and "Go to Import" links (which navigate
        // away without JS) still trigger post-onboarding admin notices.
        if (step === TOTAL_STEPS) {
            wp.apiFetch({
                path: '/almaseo/v1/wizard/complete',
                method: 'POST',
            }).catch(function () { /* best-effort */ });
        }

        // Previous button.
        $prevBtn.style.display = (step > 1 && step < TOTAL_STEPS) ? '' : 'none';

        // Footer visibility (hide on last step).
        $footer.style.display = (step === TOTAL_STEPS) ? 'none' : '';

        // Next button label.
        if (step === 1) {
            $nextBtn.innerHTML = 'Get Started &rarr;';
        } else if (step === TOTAL_STEPS - 1) {
            $nextBtn.innerHTML = 'Finish';
        } else {
            $nextBtn.innerHTML = 'Save &amp; Continue &rarr;';
        }

        // Hide skip on welcome step.
        $skipBtn.style.display = (step === 1) ? 'none' : '';
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

        // If nothing to save (e.g. welcome step), just advance.
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
     *
     *  Step mapping:
     *    1 = Welcome (no data)
     *    2 = Social Profiles
     *    3 = Search Appearance
     *    4 = Sitemap
     *    5 = Done (no data)
     * ----------------------------------------------------------------*/

    function collectStepData(step) {
        switch (step) {
            case 1:
                // Welcome — nothing to save.
                return null;
            case 2:
                return collectSocialProfiles();
            case 3:
                return collectSearchAppearance();
            case 4:
                return collectSitemap();
            default:
                return null;
        }
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

        // Core post types that should default to checked.
        var coreTypes = { post: true, page: true };

        cfg.postTypes.forEach(function (pt) {
            var isChecked;
            // Check existing settings first.
            if (pt.name === 'post' && sitemapInclude.posts !== undefined) {
                isChecked = !!sitemapInclude.posts;
            } else if (pt.name === 'page' && sitemapInclude.pages !== undefined) {
                isChecked = !!sitemapInclude.pages;
            } else if (sitemapInclude[pt.name] !== undefined) {
                isChecked = !!sitemapInclude[pt.name];
            } else {
                // No existing setting — default core types to checked, others to unchecked.
                isChecked = !!coreTypes[pt.name];
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

    // Default templates — used when no existing value is saved.
    var DEFAULTS = {
        homepage_title: '%%sitename%% %%sep%% %%tagline%%',
        homepage_desc:  '%%tagline%%',
        post_title:     '%%title%% %%sep%% %%sitename%%',
        page_title:     '%%title%% %%sep%% %%sitename%%',
    };

    function prefillExisting() {
        // Social profiles.
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

        // Search appearance — use existing values or sensible defaults.
        var sa = existing.searchAppearance || {};
        var special = sa.special || {};
        var homepage = special.homepage || {};
        var postTypes = sa.post_types || {};
        var postSettings = postTypes.post || {};
        var pageSettings = postTypes.page || {};

        setVal('wiz-homepage-title', homepage.title_template || DEFAULTS.homepage_title);
        setVal('wiz-homepage-desc', homepage.description_template || DEFAULTS.homepage_desc);
        setVal('wiz-post-title', postSettings.title_template || DEFAULTS.post_title);
        setVal('wiz-page-title', pageSettings.title_template || DEFAULTS.page_title);
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
