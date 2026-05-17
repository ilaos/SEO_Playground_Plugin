/**
 * AlmaSEO Sitemaps Admin JavaScript
 * 
 * Handles UI interactions and AJAX operations
 */

(function($) {
    'use strict';
    
    // Settings object
    let settings = window.almaseoSitemaps.settings || {};
    let hasChanges = false;

    /**
     * Show toast notification.
     *
     * Targets #almaseo-toast-container — the same container the tabs bundle
     * uses, and the only one actually rendered by sitemaps-screen-v2.php.
     * Previous code targeted #almaseo-toast which doesn't exist, so save
     * success/error notifications were silently dropped.
     */
    function showToast(message, type) {
        type = type || 'success';
        let container = document.getElementById('almaseo-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'almaseo-toast-container';
            container.setAttribute('aria-live', 'polite');
            container.setAttribute('aria-atomic', 'true');
            document.body.appendChild(container);
        }
        const toast = document.createElement('div');
        toast.className = 'almaseo-toast almaseo-toast-' + type;
        const icon = type === 'success' ? 'yes' : (type === 'error' ? 'warning' : 'info');
        toast.innerHTML = '<span class="dashicons dashicons-' + icon + '"></span> ' +
            $('<span>').text(message).html();
        container.appendChild(toast);
        setTimeout(function() {
            toast.style.opacity = '0';
            setTimeout(function() { toast.remove(); }, 300);
        }, 3000);
    }

    /**
     * Run a button-driven AJAX action with consistent UX:
     * disable + spinner while in flight, success/error toast, and always
     * restore the button at the end. Returns the jqXHR so callers can chain.
     *
     * Options:
     *   loadingText    — replaces button label while in flight
     *   successMessage — shown on success when handler didn't return one
     *   onSuccess(data)→ optional string|void: callback for custom DOM work.
     *                    May return a string to override the success toast.
     *   onError(resp)  — optional callback when response.success is false.
     */
    function runAction($btn, action, extraData, opts) {
        opts = opts || {};
        if (!$btn || $btn.length === 0) return null;
        const origHtml = $btn.html();
        const loadingText = opts.loadingText
            || (almaseoSitemaps.i18n && almaseoSitemaps.i18n.processing)
            || 'Processing…';

        $btn.prop('disabled', true).html(
            '<span class="spinner is-active" style="vertical-align:middle;margin:0 6px 0 0;float:none;"></span>'
            + $('<span>').text(loadingText).html()
        );

        return $.post(
            almaseoSitemaps.ajaxUrl,
            $.extend({
                action: 'almaseo_' + action,
                nonce: almaseoSitemaps.nonce
            }, extraData || {})
        )
        .done(function(response) {
            if (response && response.success) {
                let msg;
                if (typeof opts.onSuccess === 'function') {
                    msg = opts.onSuccess(response.data || {});
                }
                if (!msg) {
                    msg = (response.data && response.data.message)
                        || opts.successMessage
                        || (almaseoSitemaps.i18n && almaseoSitemaps.i18n.success)
                        || 'Done';
                }
                showToast(msg, 'success');
            } else {
                const msg = (response && response.data && response.data.message)
                    || (almaseoSitemaps.i18n && almaseoSitemaps.i18n.error)
                    || 'Action failed';
                showToast(msg, 'error');
                if (typeof opts.onError === 'function') opts.onError(response);
            }
        })
        .fail(function() {
            showToast(
                (almaseoSitemaps.i18n && almaseoSitemaps.i18n.network) || 'Network error',
                'error'
            );
        })
        .always(function() {
            $btn.prop('disabled', false).html(origHtml);
        });
    }

    /**
     * Trigger a browser download from an AJAX response. The export_* PHP
     * handlers all return `{ content, filename }` (some also `mime_type`).
     * Rather than scattering Blob/URL.createObjectURL plumbing across each
     * button handler, route everything through this.
     */
    function runDownload($btn, action, extraData, opts) {
        opts = opts || {};
        return runAction($btn, action, extraData || {}, {
            loadingText: opts.loadingText || 'Preparing download…',
            onSuccess: function(d) {
                const content  = d.content || d.csv || d.json || '';
                const filename = d.filename || opts.fallbackFilename || 'download.txt';
                const mime     = d.mime_type || opts.mime || 'text/plain;charset=utf-8';
                try {
                    const blob = new Blob([content], { type: mime });
                    const url  = URL.createObjectURL(blob);
                    const a    = document.createElement('a');
                    a.href = url;
                    a.download = filename;
                    document.body.appendChild(a);
                    a.click();
                    a.remove();
                    setTimeout(function() { URL.revokeObjectURL(url); }, 1500);
                } catch (e) {
                    return 'Download failed: ' + e.message;
                }
                return 'Downloaded ' + filename;
            }
        });
    }

    /**
     * Render a small "Saving…" / "Saved" status indicator next to the
     * Sitemaps screen title. The auto-save flow is intentional, but with
     * no visible feedback users don't trust their changes are persisting.
     * This pill makes the auto-save observable without adding a
     * misleading explicit Save button.
     */
    function setSaveStatus(state, message) {
        let $pill = $('#almaseo-save-status');
        if ($pill.length === 0) {
            const $anchor = $('.alma-header .quick-actions, .alma-header').first();
            if ($anchor.length === 0) return;
            $pill = $('<span id="almaseo-save-status" class="alma-save-status" aria-live="polite"></span>');
            $anchor.prepend($pill);
        }
        $pill.removeClass('is-saving is-saved is-error').addClass('is-' + state);
        $pill.text(message);
        if (state === 'saved') {
            clearTimeout(window.almaseoSaveStatusTimeout);
            window.almaseoSaveStatusTimeout = setTimeout(function() {
                $pill.fadeOut(400, function() { $pill.text('').show(); });
            }, 2000);
        } else {
            $pill.show();
        }
    }

    /**
     * Build the base payload from Types & Rules controls.
     *
     * Tabs in this panel are lazy-loaded: controls only exist in the DOM
     * after the user opens their tab. Each `read*` helper below returns
     * null when its controls are missing, and saveSettings() only includes
     * sections that returned a payload — so saving from one tab never
     * clobbers settings for a tab the user never opened (the PHP handler
     * preserves `$existing[$section]` when a key is absent from POST).
     */
    function readTypesPayload() {
        if ($('#master-enable').length === 0) return null;

        return {
            enabled: $('#master-enable').prop('checked') ? 1 : 0,
            include: {
                posts: $('.sitemap-type[data-type="posts"]').prop('checked') ? 1 : 0,
                pages: $('.sitemap-type[data-type="pages"]').prop('checked') ? 1 : 0,
                cpts:  $('.sitemap-type[data-type="cpts"]').prop('checked') ? 1 : 0,
                tax: {
                    category: $('.sitemap-type[data-type="category"]').prop('checked') ? 1 : 0,
                    post_tag: $('.sitemap-type[data-type="post_tag"]').prop('checked') ? 1 : 0
                },
                users: $('.sitemap-type[data-type="users"]').prop('checked') ? 1 : 0
            },
            links_per_sitemap: $('#links-per-sitemap').val(),
            perf: {
                storage_mode: $('input[name="storage_mode"]:checked').val() || 'static',
                gzip: $('#enable-gzip').prop('checked') ? 1 : 0
            },
            exclude: {
                taxonomies:       $('#exclude-taxonomies').val() || [],
                authors:          $('#exclude-authors').val() || [],
                older_than_years: parseInt($('#exclude-older-than').val() || '0', 10)
            }
        };
    }

    function readMediaPayload() {
        if ($('#media-image-enabled').length === 0 &&
            $('#media-video-enabled').length === 0) return null;
        return {
            image: {
                enabled:     $('#media-image-enabled').prop('checked') ? 1 : 0,
                max_per_url: parseInt($('#media-image-max').val() || '20', 10),
                dedupe_cdn:  $('#media-image-dedupe').prop('checked') ? 1 : 0
            },
            video: {
                enabled:      $('#media-video-enabled').prop('checked') ? 1 : 0,
                max_per_url:  parseInt($('#media-video-max').val() || '10', 10),
                oembed_cache: $('#media-video-oembed').prop('checked') ? 1 : 0
            }
        };
    }

    function readNewsPayload() {
        if ($('#news-enabled').length === 0) return null;
        const postTypes  = $('input[name="news[post_types][]"]:checked')
            .map(function() { return this.value; }).get();
        const categories = $('input[name="news[categories][]"]:checked')
            .map(function() { return parseInt(this.value, 10); }).get();
        const genres     = $('input[name="news[genres][]"]:checked')
            .map(function() { return this.value; }).get();

        return {
            enabled:         $('#news-enabled').prop('checked') ? 1 : 0,
            publisher_name:  $('#news-publisher').val() || '',
            language:        $('#news-language').val() || 'en',
            post_types:      postTypes,
            categories:      categories,
            genres:          genres,
            keywords_source: $('input[name="news[keywords_source]"]:checked').val() || 'tags',
            manual_keywords: $('#news-manual-keywords').val() || '',
            window_hours:    parseInt($('#news-window').val() || '48', 10),
            max_items:       parseInt($('#news-max-items').val() || '1000', 10)
        };
    }

    function readHreflangPayload() {
        if ($('#hreflang-enabled').length === 0) return null;
        const locales = {};
        $('.hreflang-locale-map').each(function() {
            const $i = $(this);
            const loc = $i.data('locale');
            if (loc) locales[loc] = $i.val();
        });
        return {
            enabled:       $('#hreflang-enabled').prop('checked') ? 1 : 0,
            source:        $('#hreflang-source').val() || 'auto',
            default:       $('#hreflang-default').val() || '',
            x_default_url: $('#hreflang-x-default').val() || '',
            locales:       locales
        };
    }

    function readDeltaPayload() {
        if ($('#delta-enabled').length === 0) return null;
        return {
            enabled:        $('#delta-enabled').prop('checked') ? 1 : 0,
            max_urls:       parseInt($('#delta-max-urls').val() || '500', 10),
            retention_days: parseInt($('#delta-retention').val() || '14', 10)
        };
    }

    function readIndexNowPayload() {
        if ($('#indexnow-enabled').length === 0) return null;
        return {
            enabled:  $('#indexnow-enabled').prop('checked') ? 1 : 0,
            key:      $('#indexnow-key').val() || '',
            endpoint: $('#indexnow-endpoint').val() || 'https://api.indexnow.org/indexnow'
        };
    }

    /**
     * Save settings via AJAX. Each section payload is included only when
     * its tab is currently loaded — see the read* helpers above for why.
     */
    function saveSettings(showNotification) {
        if (showNotification === undefined) showNotification = true;

        const data = $.extend(
            { action: 'almaseo_save_settings', nonce: almaseoSitemaps.nonce },
            readTypesPayload() || {}
        );

        const media    = readMediaPayload();
        const news     = readNewsPayload();
        const hreflang = readHreflangPayload();
        const delta    = readDeltaPayload();
        const indexnow = readIndexNowPayload();
        if (media)    data.media    = media;
        if (news)     data.news     = news;
        if (hreflang) data.hreflang = hreflang;
        if (delta)    data.delta    = delta;
        if (indexnow) data.indexnow = indexnow;

        if (showNotification) {
            setSaveStatus('saving', almaseoSitemaps.i18n.saving || 'Saving…');
        }

        $.post(almaseoSitemaps.ajaxUrl, data, function(response) {
            if (response && response.success) {
                settings = response.data.settings;
                hasChanges = false;
                $('.almaseo-save-all').fadeOut();

                if (showNotification) {
                    setSaveStatus('saved', almaseoSitemaps.i18n.saved || 'Saved');
                }

                updateEnabledChip(settings.enabled);
            } else {
                const errMsg = (response && response.data && response.data.message)
                    ? response.data.message
                    : (response && typeof response.data === 'string' ? response.data : almaseoSitemaps.i18n.error);
                setSaveStatus('error', errMsg || 'Save failed');
                showToast(errMsg, 'error');
            }
        }).fail(function() {
            setSaveStatus('error', almaseoSitemaps.i18n.error || 'Save failed');
            showToast(almaseoSitemaps.i18n.error || 'Save failed', 'error');
        });
    }
    
    /**
     * Update enabled/disabled chip
     */
    function updateEnabledChip(enabled) {
        const $chip = $('.almaseo-chip').first();
        if (enabled) {
            $chip.removeClass('almaseo-chip-error').addClass('almaseo-chip-success');
            $chip.text(almaseoSitemaps.i18n.enabled || 'Enabled');
        } else {
            $chip.removeClass('almaseo-chip-success').addClass('almaseo-chip-error');
            $chip.text(almaseoSitemaps.i18n.disabled || 'Disabled');
        }
    }
    
    /**
     * Mark settings as changed and schedule a debounced auto-save.
     *
     * The previous fadeIn of `.almaseo-save-all` was dead — that class
     * is never rendered by any tab partial. Save status is now visible
     * through the #almaseo-save-status pill set inside saveSettings().
     */
    function markChanged() {
        hasChanges = true;
        clearTimeout(window.autoSaveTimeout);
        window.autoSaveTimeout = setTimeout(function() {
            saveSettings(true);
        }, 1000);
    }
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {

        // Every change handler below is delegated on `document` so it survives
        // the tab lazy-loader replacing panel HTML. Previously these were
        // direct bindings at document.ready, which meant they only worked if
        // the user landed on the panel with that tab already rendered
        // server-side (e.g. ?tab=types); switching tabs in-session left the
        // controls unwired and saves silently dropped.

        // --- Types & Rules tab ---
        $(document).on('change', '#master-enable', function() {
            const enabled = $(this).prop('checked');
            $('.almaseo-sitemap-types').toggleClass('disabled', !enabled);
            $('.sitemap-type').prop('disabled', !enabled);
            markChanged();
        });
        $(document).on('change', '.sitemap-type', markChanged);
        $(document).on('change', '#links-per-sitemap', function() {
            let val = parseInt($(this).val(), 10);
            if (isNaN(val) || val < 1) val = 1;
            if (val > 50000) val = 50000;
            $(this).val(val);
            markChanged();
        });
        $(document).on('change', 'input[name="storage_mode"]', markChanged);
        $(document).on('change', '#enable-gzip', markChanged);

        // Exclude filters — the 1.13.8 changelog claimed full save coverage
        // for these, but only the saveSettings payload was updated; the
        // change listeners were never added, so the dropdowns never fired
        // an auto-save. Wire them now.
        $(document).on('change', '#exclude-taxonomies, #exclude-authors, #exclude-older-than', markChanged);

        // --- Media tab ---
        $(document).on(
            'change',
            '#media-image-enabled, #media-image-max, #media-image-dedupe, ' +
            '#media-video-enabled, #media-video-max, #media-video-oembed',
            markChanged
        );

        // --- News tab ---
        $(document).on(
            'change',
            '#news-enabled, #news-publisher, #news-language, ' +
            '#news-window, #news-max-items, #news-manual-keywords, ' +
            'input[name="news[post_types][]"], ' +
            'input[name="news[categories][]"], ' +
            'input[name="news[genres][]"]',
            markChanged
        );
        // Manual-keywords source radio: toggle the visibility of the manual
        // input group AND mark dirty. Previously inline CSS controlled the
        // initial state but no JS reacted to changes, so flipping the radio
        // appeared to do nothing.
        $(document).on('change', 'input[name="news[keywords_source]"]', function() {
            const isManual = $(this).val() === 'manual';
            $('#news-manual-keywords-group').css('display', isManual ? '' : 'none');
            markChanged();
        });

        // --- International (hreflang) tab ---
        $(document).on(
            'change',
            '#hreflang-enabled, #hreflang-source, #hreflang-default, #hreflang-x-default, .hreflang-locale-map',
            markChanged
        );

        // --- Change Detection (delta) tab ---
        $(document).on('change', '#delta-enabled, #delta-max-urls, #delta-retention', markChanged);

        // --- Change tab: IndexNow ---
        $(document).on('change', '#indexnow-enabled, #indexnow-key, #indexnow-endpoint', markChanged);
        // Keep "Ping All URLs" enablement in sync with the toggle — the tab
        // partial isn't re-rendered after a debounced auto-save.
        $(document).on('change', '#indexnow-enabled', function() {
            $('#ping-all-indexnow').prop('disabled', !$(this).prop('checked'));
        });

        // --- Action buttons: tabs that were previously unwired ---
        // All of these have real PHP handlers (see handle_* methods in
        // class-sitemap-ajax-handlers.php). The buttons rendered fine but
        // clicking did nothing because no JS bound to them.

        // Media tab
        $(document).on('click', '#scan-media', function() {
            runAction($(this), 'scan_media', {}, {
                loadingText: 'Scanning…',
                onSuccess: function(d) {
                    if (d && (d.images >= 0 || d.videos >= 0)) {
                        return 'Scan complete — ' + (d.images || 0) + ' images, ' + (d.videos || 0) + ' videos';
                    }
                }
            });
        });
        $(document).on('click', '#validate-media', function() {
            runAction($(this), 'validate_media', {}, {
                loadingText: 'Validating…',
                onSuccess: function(d) {
                    const issues = (d && d.issues) || [];
                    return issues.length === 0
                        ? 'Media looks good — no issues found'
                        : issues.length + ' media issue(s) found';
                }
            });
        });
        $(document).on('click', '#rebuild-media', function() {
            runAction($(this), 'rebuild_media', {}, {
                loadingText: 'Rebuilding…',
                successMessage: 'Media sitemaps rebuilt'
            });
        });

        // News tab
        $(document).on('click', '#validate-news', function() {
            runAction($(this), 'validate_news', {}, {
                loadingText: 'Validating…',
                onSuccess: function(d) {
                    return (d && d.ok)
                        ? 'News sitemap is valid'
                        : ((d && d.issues && d.issues.length) ? d.issues.length + ' issue(s) found' : 'News validation complete');
                }
            });
        });
        $(document).on('click', '#rebuild-news', function() {
            runAction($(this), 'rebuild_news', {}, {
                loadingText: 'Rebuilding…',
                successMessage: 'News sitemap rebuilt'
            });
        });

        // International (hreflang) tab
        $(document).on('click', '#validate-hreflang', function() {
            runAction($(this), 'validate_hreflang', {}, {
                loadingText: 'Validating…',
                onSuccess: function(d) {
                    return (d && d.ok) ? 'Hreflang is valid' : 'Hreflang issues detected';
                }
            });
        });

        // Validate sitemap — real now, not faked. Wires both the Overview
        // tab's Quick Tool button AND the Health & Scan tab's button. The
        // backend runs the full Alma_Sitemap_Validator suite.
        $(document).on('click', '#validate-sitemap, #validate-all', function() {
            runAction($(this), 'validate_sitemap', {}, {
                loadingText: 'Validating…',
                onSuccess: function(d) {
                    // The handler returns an aggregate `message` and detailed
                    // `results` — let it set the toast text by default.
                    return d && d.message;
                }
            });
        });

        // --- Health & Scan tab: conflict scanner, snapshots, log management ---
        $(document).on('click', '#scan-conflicts-btn, #rescan-conflicts', function() {
            runAction($(this), 'start_scan', {}, {
                loadingText: 'Starting scan…',
                successMessage: 'Conflict scan started — refresh in a moment to see results'
            });
        });
        $(document).on('click', '#view-conflicts-btn', function() {
            const $table = $('#conflicts-table');
            $table.toggle();
            // Lazy-fetch the rows the first time the table is shown.
            if ($table.is(':visible') && $table.data('loaded') !== true) {
                $.post(almaseoSitemaps.ajaxUrl, {
                    action: 'almaseo_get_scan_results',
                    nonce: almaseoSitemaps.nonce
                }, function(r) {
                    if (r && r.success && r.data && r.data.conflicts) {
                        const $tb = $('#conflicts-tbody').empty();
                        r.data.conflicts.forEach(function(c) {
                            $tb.append(
                                $('<tr>')
                                    .append($('<td>').text(c.type || ''))
                                    .append($('<td>').text(c.description || ''))
                                    .append($('<td>').text(c.plugin || c.theme || ''))
                                    .append($('<td>').text(c.impact || ''))
                            );
                        });
                        $table.data('loaded', true);
                    }
                });
            }
        });
        $(document).on('click', '#create-snapshot-btn', function() {
            const name = window.prompt('Snapshot name:', 'snapshot-' + new Date().toISOString().slice(0, 10));
            if (!name) return;
            runAction($(this), 'create_snapshot', { name: name }, {
                loadingText: 'Snapshotting…'
            });
        });
        $(document).on('click', '#compare-snapshots-btn', function() {
            runAction($(this), 'compare_snapshots', {}, {
                loadingText: 'Comparing…',
                onSuccess: function(d) {
                    if (d && d.summary) {
                        return 'Diff: +' + (d.summary.added || 0)
                            + ' / -' + (d.summary.removed || 0)
                            + ' / ~' + (d.summary.changed || 0);
                    }
                }
            });
        });
        $(document).on('click', '#clear-logs-btn', function() {
            if (!window.confirm('Clear all health log entries? This cannot be undone.')) return;
            runAction($(this), 'clear_logs', {}, {
                loadingText: 'Clearing…',
                successMessage: 'Health logs cleared'
            }).done(function(r) {
                if (r && r.success) {
                    $('#health-log-tbody').empty();
                }
            });
        });
        $(document).on('click', '#refresh-logs-btn', function() {
            // Cheap path: reload the page. A focused log-fetch endpoint would
            // be nicer but doesn't exist yet, and avoiding scope creep here.
            window.location.reload();
        });

        // --- Export buttons across all tabs (consistent download flow) ---
        $(document).on('click', '#export-conflicts-csv', function() {
            runDownload($(this), 'export_conflicts', {}, {
                mime: 'text/csv;charset=utf-8',
                fallbackFilename: 'almaseo-conflicts.csv'
            });
        });
        $(document).on('click', '#export-diff-csv', function() {
            runDownload($(this), 'export_diff', {}, {
                mime: 'text/csv;charset=utf-8',
                fallbackFilename: 'almaseo-diff.csv'
            });
        });
        $(document).on('click', '#export-hreflang-issues', function() {
            runDownload($(this), 'export_hreflang_issues', {}, {
                mime: 'text/csv;charset=utf-8',
                fallbackFilename: 'almaseo-hreflang-issues.csv'
            });
        });
        $(document).on('click', '#export-logs-btn', function() {
            runDownload($(this), 'export_logs', {}, {
                mime: 'text/csv;charset=utf-8',
                fallbackFilename: 'almaseo-logs.csv'
            });
        });
        $(document).on('click', '#export-settings-btn', function() {
            runDownload($(this), 'export_settings', {}, {
                mime: 'application/json',
                fallbackFilename: 'almaseo-sitemap-settings.json'
            });
        });
        $(document).on('click', '#export-csv-btn', function() {
            // Types & Rules → Additional URLs → Export CSV
            runDownload($(this), 'export_csv', {}, {
                mime: 'text/csv;charset=utf-8',
                fallbackFilename: 'almaseo-additional-urls.csv'
            });
        });

        // --- Types & Rules → Additional URLs → Add URL ---
        // Minimal prompt-based UX. The Alma_Provider_Extra::add_url() handler
        // still accepts priority as a parameter (the DB column hangs around
        // for backward compat) — we just no longer ask for it, since the
        // sitemap writer dropped <priority> from the output.
        $(document).on('click', '#add-url-btn', function() {
            const url = window.prompt('URL to add to sitemap:', '');
            if (!url) return;
            const changefreq = window.prompt(
                'Change frequency (always, hourly, daily, weekly, monthly, yearly, never):',
                'weekly'
            );
            if (changefreq === null) return;
            runAction($(this), 'add_url', {
                url: url,
                changefreq: changefreq
            }, {
                loadingText: 'Adding…',
                successMessage: 'URL added'
            }).done(function(r) {
                if (r && r.success) {
                    setTimeout(function() { window.location.reload(); }, 600);
                }
            });
        });

        // --- Updates & I/O tab: misc helpers ---
        $(document).on('click', '#copy-all-urls-btn', function() {
            const $btn = $(this);
            runAction($btn, 'copy_all_urls', {}, {
                loadingText: 'Loading…',
                onSuccess: function(d) {
                    if (!d || !d.urls || !d.urls.length) return;
                    const text = d.urls.join('\n');
                    const $ta = $('#sitemap-urls-list textarea').val(text);
                    $('#sitemap-urls-list').show();
                    try {
                        $ta.select();
                        document.execCommand('copy');
                        return 'Copied ' + d.urls.length + ' URLs to clipboard';
                    } catch (e) {
                        return 'URLs listed below — copy manually';
                    }
                }
            });
        });
        $(document).on('click', '#copy-shortcode-btn', function() {
            const $btn = $(this);
            const $input = $('#generated-shortcode');
            $input.select();
            try {
                document.execCommand('copy');
                showToast('Shortcode copied', 'success');
            } catch (e) {
                showToast('Copy failed — please copy manually', 'error');
            }
        });
        // Live-update the generated shortcode as the user tweaks the builder.
        $(document).on('change', '.almaseo-shortcode-builder input[type="checkbox"], #shortcode-columns', function() {
            const types = $('.almaseo-shortcode-builder input[type="checkbox"]:checked')
                .map(function() { return this.value; }).get();
            const cols = parseInt($('#shortcode-columns').val(), 10) || 2;
            const sc = '[almaseo_html_sitemap types="' + types.join(',') + '" columns="' + cols + '"]';
            $('#generated-shortcode').val(sc);
        });

        // --- Clear All Additional URLs (Types & Rules) ---
        $(document).on('click', '#clear-all-urls-btn', function() {
            if (!window.confirm('Remove all additional URLs? This cannot be undone.')) return;
            runAction($(this), 'clear_all_urls', {}, {
                loadingText: 'Clearing…'
            }).done(function(r) {
                if (r && r.success) {
                    setTimeout(function() { window.location.reload(); }, 600);
                }
            });
        });

        // --- Updates & I/O: System information copy ---
        $(document).on('click', '#copy-system-info', function() {
            const lines = [];
            $('.almaseo-info-item').each(function() {
                const label = $(this).find('strong').text().trim();
                const value = $(this).clone().children('strong').remove().end().text().trim();
                if (label) lines.push(label + ' ' + value);
            });
            // Include active plugins detail block if present
            $('.almaseo-plugin-item').each(function() {
                const name = $(this).find('strong').text().trim();
                const ver  = $(this).find('.version').text().trim();
                if (name) lines.push('Plugin: ' + name + (ver ? ' (' + ver + ')' : ''));
            });
            if (lines.length === 0) {
                showToast('Nothing to copy', 'error');
                return;
            }
            const text = lines.join('\n');
            const $temp = $('<textarea>').css({ position: 'fixed', top: '-9999px' }).val(text);
            $('body').append($temp);
            $temp.select();
            try {
                document.execCommand('copy');
                showToast('System info copied (' + lines.length + ' lines)', 'success');
            } catch (e) {
                showToast('Copy failed — please copy manually', 'error');
            }
            $temp.remove();
        });

        // --- Updates & I/O: Bulk operations ---
        $(document).on('click', '#validate-all-sitemaps', function() {
            // Same backend as the header Validate buttons — runs the full
            // Alma_Sitemap_Validator suite which already covers every
            // provider sitemap.
            runAction($(this), 'validate_sitemap', {}, {
                loadingText: 'Validating…',
                onSuccess: function(d) { return d && d.message; }
            });
        });
        $(document).on('click', '#rebuild-all-sitemaps', function() {
            // rebuild_static rebuilds the index plus every provider's child
            // sitemaps in one pass — there's no notion of "some sitemaps"
            // so the "all" semantics are accurate.
            runAction($(this), 'rebuild_static', {}, {
                loadingText: 'Rebuilding all sitemaps…',
                successMessage: 'All sitemaps rebuilt'
            });
        });
        $(document).on('click', '#ping-all-search-engines', function() {
            runAction($(this), 'ping_search_engines', {}, {
                loadingText: 'Pinging…'
            });
        });

        // --- Robots.txt preview / copy / download ---
        $(document).on('click', '#preview-robots-btn', function() {
            const $btn = $(this);
            runAction($btn, 'preview_robots', {}, {
                loadingText: 'Loading…',
                onSuccess: function(d) {
                    const preview = (d && d.preview) || '';
                    $('#robots-preview pre.almaseo-code-preview').text(preview);
                    $('#robots-preview').show().data('lines', (d && d.sitemap_lines) || []);
                    return 'robots.txt loaded';
                }
            });
        });
        $(document).on('click', '#copy-robots-entries', function() {
            const lines = $('#robots-preview').data('lines') || [];
            if (!lines.length) {
                showToast('Open Preview first', 'error');
                return;
            }
            const $temp = $('<textarea>').css({ position: 'fixed', top: '-9999px' }).val(lines.join('\n'));
            $('body').append($temp);
            $temp.select();
            try {
                document.execCommand('copy');
                showToast('Sitemap lines copied (' + lines.length + ')', 'success');
            } catch (e) {
                showToast('Copy failed', 'error');
            }
            $temp.remove();
        });
        $(document).on('click', '#download-robots', function() {
            const preview = $('#robots-preview pre.almaseo-code-preview').text();
            if (!preview) {
                showToast('Open Preview first', 'error');
                return;
            }
            const blob = new Blob([preview], { type: 'text/plain;charset=utf-8' });
            const url = URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url; a.download = 'robots.txt';
            document.body.appendChild(a); a.click(); a.remove();
            setTimeout(function() { URL.revokeObjectURL(url); }, 1500);
            showToast('robots.txt downloaded', 'success');
        });

        // --- Auto-update toggles persistence ---
        function saveAutoUpdateSettings() {
            $.post(almaseoSitemaps.ajaxUrl, {
                action: 'almaseo_save_auto_update_settings',
                nonce: almaseoSitemaps.nonce,
                enabled: $('#auto-updates-enabled').prop('checked') ? 1 : 0,
                beta:    $('#auto-updates-beta').prop('checked') ? 1 : 0
            }, function(r) {
                if (r && r.success) {
                    showToast(r.data.message || 'Saved', 'success');
                } else {
                    showToast((r && r.data && r.data.message) || 'Save failed', 'error');
                }
            }).fail(function() {
                showToast('Network error', 'error');
            });
        }
        $(document).on('change', '#auto-updates-enabled, #auto-updates-beta', saveAutoUpdateSettings);

        // --- Import Settings flow (Updates & I/O tab) ---
        // The drop zone is rendered statically in updates-io.php with a hidden
        // <input type="file" id="import-settings-file" accept=".json">. We
        // own all the click + drop wiring here. State held in a module-scoped
        // `pendingImport` var: { content, filename }.
        let pendingImport = null;
        function setImportFileInfo(file) {
            $('#import-file-name').text(file.name);
            $('#import-file-size').text('(' + Math.ceil(file.size / 1024) + ' KB)');
            $('#import-file-info').show();
            $('#import-options').show();
            $('#import-confirm-btn').prop('disabled', false);
        }
        function readImportFile(file) {
            if (!file) return;
            const reader = new FileReader();
            reader.onload = function(e) {
                pendingImport = { content: e.target.result, filename: file.name };
                setImportFileInfo(file);
            };
            reader.onerror = function() {
                showToast('Failed to read file', 'error');
            };
            reader.readAsText(file);
        }
        $(document).on('click', '#import-settings-btn, #import-drop-zone', function(e) {
            // Avoid double-firing when the drop zone is clicked and bubbles
            // up to the button or vice versa.
            if (e.target.id === 'import-confirm-btn') return;
            $('#import-settings-file').trigger('click');
        });
        $(document).on('change', '#import-settings-file', function() {
            const f = this.files && this.files[0];
            if (f) readImportFile(f);
        });
        $(document).on('dragover', '#import-drop-zone', function(e) {
            e.preventDefault();
            $(this).addClass('is-dragover');
        });
        $(document).on('dragleave drop', '#import-drop-zone', function(e) {
            $(this).removeClass('is-dragover');
        });
        $(document).on('drop', '#import-drop-zone', function(e) {
            e.preventDefault();
            const dt = e.originalEvent && e.originalEvent.dataTransfer;
            const f = dt && dt.files && dt.files[0];
            if (f) readImportFile(f);
        });
        $(document).on('click', '#import-confirm-btn', function() {
            if (!pendingImport) {
                showToast('Choose a settings file first', 'error');
                return;
            }
            const merge = $('#import-merge-settings').prop('checked') ? 1 : 0;
            const backup = $('#import-create-backup').prop('checked') ? 1 : 0;
            // If user wants a pre-import backup, trigger settings export first.
            const proceed = function() {
                runAction($('#import-confirm-btn'), 'import_settings', {
                    settings: pendingImport.content,
                    merge: merge
                }, {
                    loadingText: 'Importing…',
                    successMessage: 'Settings imported — reloading'
                }).done(function(r) {
                    if (r && r.success) {
                        setTimeout(function() { window.location.reload(); }, 900);
                    }
                });
            };
            if (backup) {
                runDownload($('#import-confirm-btn'), 'export_settings', {}, {
                    mime: 'application/json',
                    fallbackFilename: 'almaseo-settings-backup.json'
                }).always(proceed);
            } else {
                proceed();
            }
        });

        // --- Import CSV (Types & Rules → Additional URLs) ---
        // The partial only has a button — we synthesize the file input.
        let csvFileInput = null;
        function getCsvFileInput() {
            if (csvFileInput) return csvFileInput;
            csvFileInput = $('<input type="file" accept=".csv,text/csv" style="display:none;">')
                .appendTo('body')
                .on('change', function() {
                    const f = this.files && this.files[0];
                    if (!f) return;
                    const reader = new FileReader();
                    reader.onload = function(e) {
                        runAction($('#import-csv-btn'), 'import_csv', {
                            csv: e.target.result
                        }, {
                            loadingText: 'Importing CSV…',
                            onSuccess: function(d) {
                                const n = (d && d.imported) || 0;
                                return n + ' URL(s) imported';
                            }
                        }).done(function(r) {
                            if (r && r.success) {
                                setTimeout(function() { window.location.reload(); }, 800);
                            }
                        });
                    };
                    reader.readAsText(f);
                    // Reset so re-selecting the same file fires change again.
                    this.value = '';
                });
            return csvFileInput;
        }
        $(document).on('click', '#import-csv-btn', function() {
            getCsvFileInput().trigger('click');
        });

        // Open/Copy buttons in tabs — pure DOM, no AJAX needed
        $(document).on('click', '#open-image-sitemap, #open-video-sitemap, #open-news-sitemap, #open-delta', function() {
            const $input = $(this).closest('.almaseo-input-group').find('input[type="text"]').first();
            const url = $input.val();
            if (url) window.open(url, '_blank');
        });
        $(document).on('click', '#copy-image-url, #copy-video-url, #copy-news-url, #copy-delta-url', function() {
            const $btn = $(this);
            const $input = $btn.closest('.almaseo-input-group').find('input[type="text"]').first();
            if (!$input.val()) return;
            $input.select();
            try {
                document.execCommand('copy');
                showToast('URL copied to clipboard', 'success');
            } catch (e) {
                showToast('Copy failed — please copy manually', 'error');
            }
        });
        
        // Open sitemap button
        $('#open-sitemap').on('click', function() {
            window.open(almaseoSitemaps.sitemapUrl, '_blank');
        });
        
        // Copy URL button
        $('#copy-url').on('click', function() {
            const $btn = $(this);
            const $input = $('#sitemap-url');
            
            // Select and copy
            $input.select();
            document.execCommand('copy');
            
            // Update button text
            const originalText = $btn.find('.button-text').text();
            $btn.find('.button-text').text(almaseoSitemaps.i18n.copied);
            $btn.addClass('success');
            
            // Reset after 2 seconds
            setTimeout(function() {
                $btn.find('.button-text').text(originalText);
                $btn.removeClass('success');
            }, 2000);
        });
        
        // Recalculate button
        $('#recalculate').on('click', function() {
            const $btn = $(this);
            const originalHtml = $btn.html();
            
            // Show loading state
            $btn.html('<span class="dashicons dashicons-update spinning"></span> ' + 
                     '<span class="button-text">' + almaseoSitemaps.i18n.recalculating + '</span>');
            $btn.prop('disabled', true);
            
            $.post(almaseoSitemaps.ajaxUrl, {
                action: 'almaseo_recalculate',
                nonce: almaseoSitemaps.nonce
            }, function(response) {
                if (response.success) {
                    $btn.html('<span class="dashicons dashicons-yes"></span> ' +
                             '<span class="button-text">' + almaseoSitemaps.i18n.recalculated + '</span>');
                    // handle_recalculate only clears the stat transients — it
                    // returns no stats payload. Reload so the server-rendered
                    // chips show the freshly recomputed values. (Previously
                    // this read response.data.stats.files, which is undefined,
                    // throwing a TypeError that left the button stuck.)
                    setTimeout(function() { window.location.reload(); }, 600);
                } else {
                    showToast(almaseoSitemaps.i18n.error, 'error');
                    $btn.html(originalHtml);
                    $btn.prop('disabled', false);
                }
            });
        });
        
        // Save all button
        $('.almaseo-save-all').on('click', function() {
            saveSettings(true);
        });
        
        // Tool buttons (Phase 2 placeholder)
        $('.almaseo-tool-button').on('click', function(e) {
            e.preventDefault();
            // These will be activated in Phase 2
            return false;
        });
        
        // Add spinning animation for dashicons
        const style = document.createElement('style');
        style.textContent = `
            @keyframes dashicons-spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            .dashicons.spinning {
                animation: dashicons-spin 1s linear infinite;
            }
        `;
        document.head.appendChild(style);
        
        // Handle keyboard shortcuts
        $(document).on('keydown', function(e) {
            // Ctrl/Cmd + S to save
            if ((e.ctrlKey || e.metaKey) && e.key === 's') {
                e.preventDefault();
                if (hasChanges) {
                    saveSettings(true);
                }
            }
        });
        
        // Prevent navigation if there are unsaved changes
        $(window).on('beforeunload', function() {
            if (hasChanges) {
                return 'You have unsaved changes. Are you sure you want to leave?';
            }
        });
        
        // Initialize tooltips for disabled elements
        $('[data-tooltip]').each(function() {
            const $this = $(this);
            $this.attr('title', $this.data('tooltip'));
        });
        
        // Handle responsive menu
        if ($(window).width() < 768) {
            $('.almaseo-button-group').addClass('responsive');
        }
        
        $(window).on('resize', function() {
            if ($(window).width() < 768) {
                $('.almaseo-button-group').addClass('responsive');
            } else {
                $('.almaseo-button-group').removeClass('responsive');
            }
        });
    });
    
})(jQuery);/**
 * AlmaSEO Auto-Updates JavaScript
 * 
 * Handles update channel switching and manual checks
 * 
 * @package AlmaSEO
 * @since 5.0.0
 */

(function($) {
    'use strict';
    
    $(document).ready(function() {
        
        /**
         * Handle update channel change
         */
        $('#update-channel').on('change', function() {
            const channel = $(this).val();
            const $select = $(this);
            
            $select.prop('disabled', true);
            
            $.post(ajaxurl, {
                action: 'almaseo_save_update_channel',
                channel: channel,
                nonce: almaseoSitemaps.nonce
            }, function(response) {
                if (response.success) {
                    showToast(response.data.message, 'success');
                    
                    // Trigger background check with new channel
                    setTimeout(function() {
                        checkForUpdates(true); // Silent check
                    }, 1000);
                } else {
                    showToast(response.data || 'Failed to save channel', 'error');
                    // Revert selection
                    $select.val($select.data('original'));
                }
                
                $select.prop('disabled', false);
            }).fail(function() {
                showToast('Network error', 'error');
                $select.prop('disabled', false);
                $select.val($select.data('original'));
            });
        });
        
        // Store original value
        $('#update-channel').data('original', $('#update-channel').val());
        
        /**
         * Handle manual update check
         */
        $('#check-updates-btn').on('click', function() {
            checkForUpdates(false);
        });
        
        /**
         * Check for updates
         */
        function checkForUpdates(silent) {
            const $btn = $('#check-updates-btn');
            const originalText = $btn.html();
            
            if (!silent) {
                $btn.prop('disabled', true);
                $btn.html('<span class="dashicons dashicons-update spin"></span> Checking...');
            }
            
            $.post(ajaxurl, {
                action: 'almaseo_check_updates_now',
                nonce: almaseoSitemaps.nonce
            }, function(response) {
                if (response.success) {
                    updateDisplayInfo(response.data);
                    
                    if (!silent) {
                        if (response.data.found) {
                            showToast('Update available: v' + response.data.version, 'success');
                            
                            // Show update button
                            if (response.data.version !== response.data.current) {
                                showUpdatePrompt(response.data);
                            }
                        } else {
                            showToast('You have the latest version', 'info');
                        }
                    }
                } else {
                    if (!silent) {
                        showToast(response.data || 'Update check failed', 'error');
                    }
                }
                
                if (!silent) {
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                }
            }).fail(function() {
                if (!silent) {
                    showToast('Network error during update check', 'error');
                    $btn.prop('disabled', false);
                    $btn.html(originalText);
                }
            });
        }
        
        /**
         * Update display info
         */
        function updateDisplayInfo(data) {
            const $info = $('.almaseo-update-info');
            
            // Update last check time
            if (data.checked_at) {
                const checkTime = new Date(data.checked_at * 1000);
                const timeAgo = getTimeAgo(checkTime);
                
                // Update or add last check line
                let $lastCheck = $info.find('p:contains("Last Check")');
                if ($lastCheck.length === 0) {
                    $info.append('<p><strong>Last Check:</strong> <span class="last-check-time"></span></p>');
                    $lastCheck = $info.find('.last-check-time');
                } else {
                    $lastCheck = $lastCheck.find('span').length ? 
                        $lastCheck.find('span') : 
                        $lastCheck;
                }
                $lastCheck.text(timeAgo);
            }
            
            // Update latest version
            if (data.version && data.found) {
                let $latestVersion = $info.find('p:contains("Latest Version")');
                
                if ($latestVersion.length === 0) {
                    $info.append('<p><strong>Latest Version:</strong> <span class="latest-version"></span></p>');
                    $latestVersion = $info.find('.latest-version');
                } else {
                    $latestVersion = $latestVersion.find('span.latest-version').length ? 
                        $latestVersion.find('span.latest-version') : 
                        $latestVersion;
                }
                
                $latestVersion.html(data.version);
                
                // Add update badge if newer
                if (data.version !== data.current) {
                    if (!$latestVersion.next('.almaseo-badge').length) {
                        $latestVersion.after(' <span class="almaseo-badge almaseo-badge-warning">Update Available</span>');
                    }
                }
            }
        }
        
        /**
         * Show update prompt
         */
        function showUpdatePrompt(data) {
            // Check if WordPress update UI already shows it
            if ($('.plugin-update-tr[data-plugin*="almaseo"]').length) {
                return; // WordPress will handle it
            }
            
            // Create custom update notice
            const $notice = $('<div class="notice notice-info almaseo-update-notice">' +
                '<p><strong>AlmaSEO Update Available!</strong></p>' +
                '<p>Version ' + data.version + ' is available. ' +
                '<a href="' + (data.info_url || '#') + '" target="_blank">View details</a> | ' +
                '<a href="plugins.php">Go to Plugins page to update</a></p>' +
                '</div>');
            
            $('.almaseo-header').after($notice);
            
            setTimeout(function() {
                $notice.slideUp();
            }, 10000);
        }
        
        /**
         * Get time ago string
         */
        function getTimeAgo(date) {
            const seconds = Math.floor((new Date() - date) / 1000);
            
            let interval = Math.floor(seconds / 31536000);
            if (interval > 1) return interval + ' years ago';
            if (interval === 1) return '1 year ago';
            
            interval = Math.floor(seconds / 2592000);
            if (interval > 1) return interval + ' months ago';
            if (interval === 1) return '1 month ago';
            
            interval = Math.floor(seconds / 86400);
            if (interval > 1) return interval + ' days ago';
            if (interval === 1) return '1 day ago';
            
            interval = Math.floor(seconds / 3600);
            if (interval > 1) return interval + ' hours ago';
            if (interval === 1) return '1 hour ago';
            
            interval = Math.floor(seconds / 60);
            if (interval > 1) return interval + ' minutes ago';
            if (interval === 1) return '1 minute ago';
            
            return 'Just now';
        }
        
        /**
         * Toast notification
         */
        function showToast(message, type) {
            const $toast = $('<div class="almaseo-toast almaseo-toast-' + type + '">' +
                '<span class="dashicons dashicons-' + 
                (type === 'success' ? 'yes' : type === 'error' ? 'warning' : 'info') + 
                '"></span> ' + message + '</div>');
            
            $('body').append($toast);
            
            setTimeout(function() {
                $toast.addClass('show');
            }, 100);
            
            setTimeout(function() {
                $toast.removeClass('show');
                setTimeout(function() {
                    $toast.remove();
                }, 300);
            }, 3000);
        }
        
        // Check for updates on page load if last check > 24h
        const lastCheckTime = $('#check-updates-btn').data('last-check');
        if (lastCheckTime && (Date.now() / 1000 - lastCheckTime) > 86400) {
            setTimeout(function() {
                checkForUpdates(true); // Silent check
            }, 5000);
        }
    });
    
})(jQuery);/**
 * AlmaSEO Sitemaps Phase 2 JavaScript Updates
 * 
 * @package AlmaSEO
 */

// STEP 6: Wrap in jQuery to ensure $ is available
jQuery(function($) {
    'use strict';

    /**
     * Validate sitemap
     */
    function validateSitemap() {
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_validate_sitemap',
            nonce: almaseoSitemaps.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
                
                // Update validation display
                if (response.data.issues && response.data.issues.length > 0) {
                    let issuesHtml = '<ul>';
                    response.data.issues.forEach(function(issue) {
                        issuesHtml += '<li>' + issue + '</li>';
                    });
                    issuesHtml += '</ul>';
                    $('#validation-results').html(issuesHtml);
                } else {
                    $('#validation-results').html('<p class="success">No issues found!</p>');
                }
            } else {
                showNotice(response.data || 'Validation failed', 'error');
            }
        });
    }

    /**
     * Submit to search engines
     */
    function submitToSearchEngines() {
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_submit_sitemap',
            nonce: almaseoSitemaps.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
            } else {
                showNotice(response.data || 'Submission failed', 'error');
            }
        });
    }

    /**
     * Ping IndexNow. mode 'test' submits only the sitemap index as a
     * connectivity check; any other mode submits the full queue of changed
     * URLs (Alma_IndexNow prepends the sitemap index either way).
     */
    function pingIndexNow($btn, mode) {
        const original = $btn.html();
        $btn.prop('disabled', true);
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_ping_search_engines',
            nonce: almaseoSitemaps.nonce,
            mode: mode
        }).done(function(response) {
            const ok = !!(response && response.success);
            const msg = (response && response.data && response.data.message)
                ? response.data.message
                : (ok ? 'Submitted to IndexNow' : 'IndexNow request failed');
            showNotice(msg, ok ? 'success' : 'error');
        }).fail(function() {
            showNotice('IndexNow request failed', 'error');
        }).always(function() {
            $btn.prop('disabled', false).html(original);
        });
    }

    /**
     * Build static sitemaps
     */
    function buildStaticSitemaps() {
        const $button = $('#rebuild-static');
        const originalText = $button.text();
        
        $button.prop('disabled', true).text('Building...');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_rebuild_static',
            nonce: almaseoSitemaps.nonce
        })
        .done(function(response) {
            if (response.success) {
                showNotice(response.data.message, 'success');
                
                // Update stats display
                if (response.data.stats) {
                    $('#total-files').text(response.data.stats.files);
                    $('#total-urls').text(response.data.stats.urls);
                    $('#last-built').text(response.data.stats.last_built);
                }
            } else {
                showNotice(response.data || 'Build failed', 'error');
            }
        })
        .always(function() {
            $button.prop('disabled', false).text(originalText);
        });
    }

    /**
     * Export/Import settings
     */
    function exportSettings() {
        window.location.href = almaseoSitemaps.ajaxUrl + '?action=almaseo_export_settings&nonce=' + almaseoSitemaps.nonce;
    }

    function importSettings() {
        const fileInput = $('<input type="file" accept=".json">');
        fileInput.on('change', function(e) {
            const file = e.target.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                try {
                    const settings = JSON.parse(e.target.result);
                    
                    $.post(almaseoSitemaps.ajaxUrl, {
                        action: 'almaseo_import_settings',
                        nonce: almaseoSitemaps.nonce,
                        settings: JSON.stringify(settings)
                    })
                    .done(function(response) {
                        if (response.success) {
                            showNotice('Settings imported successfully', 'success');
                            setTimeout(function() {
                                window.location.reload();
                            }, 1500);
                        } else {
                            showNotice(response.data || 'Import failed', 'error');
                        }
                    });
                } catch (error) {
                    showNotice('Invalid settings file', 'error');
                }
            };
            reader.readAsText(file);
        });
        fileInput.trigger('click');
    }

    /**
     * Helper functions
     */
    function showNotice(message, type) {
        const $notice = $('<div>')
            .addClass('notice notice-' + type + ' is-dismissible')
            .html('<p>' + message + '</p>');
        
        $('.wrap > h1').after($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }

    function generateRandomKey(length) {
        const chars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        let key = '';
        for (let i = 0; i < length; i++) {
            key += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        return key;
    }

    /**
     * Initialize handlers
     */
    $(document).ready(function() {
        // IndexNow controls — delegated on document so they survive the
        // Change tab being lazy-loaded after the page first renders.

        // Generate a key into the field and trigger an auto-save so the new
        // key persists to almaseo_sitemap_settings['indexnow'].
        $(document).on('click', '#generate-indexnow-key', function() {
            $('#indexnow-key').val(generateRandomKey(32));
            markChanged();
        });
        // Connectivity test — submit just the sitemap index.
        $(document).on('click', '#test-indexnow', function() {
            pingIndexNow($(this), 'test');
        });
        // Submit all queued changed URLs.
        $(document).on('click', '#ping-all-indexnow', function() {
            pingIndexNow($(this), 'all');
        });

        // Attach button handlers
        $('#validate-sitemap').on('click', validateSitemap);
        $('#submit-to-search-engines').on('click', submitToSearchEngines);
        $('#rebuild-static').on('click', buildStaticSitemaps);
        $('#export-settings').on('click', exportSettings);
        $('#import-settings').on('click', importSettings);
    });

}); // End jQuery wrapper

/**
 * AlmaSEO Sitemaps Phase 3 JavaScript
 * 
 * Additional URLs management, conflict scanning, and diff reporting
 * 
 * @package AlmaSEO
 * @since 4.7.0
 */

(function($) {
    'use strict';
    
    /**
     * Phase 3: Additional URLs Management
     */
    
    // Add URL button
    $('#add-url-btn').on('click', function() {
        $('#add-url-modal').fadeIn();
        $('#new-url').focus();
    });
    
    // Save new URL
    $('#save-new-url').on('click', function() {
        const $btn = $(this);
        const url = $('#new-url').val();
        const priority = $('#new-priority').val();
        const changefreq = $('#new-changefreq').val();
        
        if (!url) {
            showToast('Please enter a URL', 'error');
            return;
        }
        
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_add_url',
            nonce: almaseoSitemaps.nonce,
            url: url,
            priority: priority,
            changefreq: changefreq
        }, function(response) {
            if (response.success) {
                showToast(response.data.message, 'success');
                $('#add-url-modal').fadeOut();
                $('#new-url').val('');
                $('#new-priority').val('0.5');
                $('#new-changefreq').val('');
                
                // Reload page to show new URL
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(response.data || 'Failed to add URL', 'error');
            }
            
            $btn.prop('disabled', false);
        });
    });
    
    // Import CSV button
    $('#import-csv-btn').on('click', function() {
        $('#import-csv-modal').fadeIn();
    });
    
    // Import CSV confirm
    $('#import-csv-confirm').on('click', function() {
        const $btn = $(this);
        const csv = $('#csv-content').val();
        
        if (!csv.trim()) {
            showToast('Please enter CSV content', 'error');
            return;
        }
        
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_import_csv',
            nonce: almaseoSitemaps.nonce,
            csv: csv
        }, function(response) {
            if (response.success) {
                showToast(response.data.message, 'success');
                
                if (response.data.errors && response.data.errors.length > 0) {
                    console.warn('Import errors:', response.data.errors);
                }
                
                $('#import-csv-modal').fadeOut();
                $('#csv-content').val('');
                
                // Reload page
                setTimeout(() => location.reload(), 1000);
            } else {
                showToast(response.data || 'Import failed', 'error');
            }
            
            $btn.prop('disabled', false);
        });
    });
    
    // Export CSV button
    $('#export-csv-btn').on('click', function() {
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_export_csv',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                downloadCSV(response.data.csv, response.data.filename);
            } else {
                showToast('Export failed', 'error');
            }
        });
    });
    
    /**
     * Phase 3: Conflict Scanner
     */
    
    let scanCheckInterval = null;
    
    // Start scan button
    $('#scan-conflicts-btn').on('click', function() {
        const $btn = $(this);
        const originalHtml = $btn.html();
        
        $btn.html('<span class="dashicons dashicons-update spinning"></span> Starting...');
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_start_scan',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                showToast('Scan started', 'success');
                
                // Start checking status
                scanCheckInterval = setInterval(checkScanStatus, 2000);
            } else {
                showToast(response.data || 'Failed to start scan', 'error');
                $btn.html(originalHtml);
                $btn.prop('disabled', false);
            }
        });
    });
    
    // Check scan status
    function checkScanStatus() {
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_get_scan_status',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                const status = response.data;
                
                if (status.status === 'running') {
                    // Update progress display
                    updateScanProgress(status);
                } else if (status.status === 'complete') {
                    // Scan complete
                    clearInterval(scanCheckInterval);
                    showToast('Scan complete', 'success');
                    updateScanResults(status);
                    
                    // Re-enable button
                    $('#scan-conflicts-btn').html('<span class="dashicons dashicons-search"></span> Start Scan');
                    $('#scan-conflicts-btn').prop('disabled', false);
                }
            }
        });
    }
    
    // Update scan progress display
    function updateScanProgress(status) {
        const $container = $('#scan-conflicts-btn').closest('.almaseo-card').find('.almaseo-card-body');
        
        let html = `<div class="almaseo-scan-progress">
            <div class="almaseo-progress-bar">
                <div class="almaseo-progress-fill" style="width: ${status.progress}%"></div>
            </div>
            <p>Scanning: ${status.checked}/${status.total} URLs checked (${status.issues} issues found)</p>
        </div>`;
        
        $container.html(html);
    }
    
    // Update scan results display
    function updateScanResults(status) {
        const $container = $('#scan-conflicts-btn').closest('.almaseo-card').find('.almaseo-card-body');
        
        let html = `<div class="almaseo-scan-results">
            <div class="almaseo-stat-grid">
                <div class="almaseo-stat">
                    <div class="almaseo-stat-value">${status.checked}</div>
                    <div class="almaseo-stat-label">URLs Checked</div>
                </div>
                <div class="almaseo-stat">
                    <div class="almaseo-stat-value almaseo-text-warning">${status.issues}</div>
                    <div class="almaseo-stat-label">Issues Found</div>
                </div>
            </div>`;
        
        if (status.issues > 0) {
            html += `<button type="button" class="button" id="view-conflicts-btn">View Details</button>`;
        }
        
        html += `</div>`;
        
        $container.html(html);
        
        // Bind view details button
        $('#view-conflicts-btn').on('click', viewConflictDetails);
    }
    
    // View conflict details
    function viewConflictDetails() {
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_get_scan_results',
            nonce: almaseoSitemaps.nonce,
            filter: 'all',
            page: 1
        }, function(response) {
            if (response.success) {
                displayConflictModal(response.data);
            }
        });
    }
    
    // Display conflict modal
    function displayConflictModal(data) {
        let html = '<table class="widefat">';
        html += '<thead><tr><th>URL</th><th>Issues</th><th>Status</th><th>Details</th></tr></thead>';
        html += '<tbody>';
        
        data.items.forEach(function(item) {
            const issueLabels = {
                'http_404': '404 Not Found',
                'http_redirect': 'Redirect',
                'http_5xx': 'Server Error',
                'robots_block': 'Blocked by robots.txt',
                'noindex': 'Has noindex',
                'canonical_mismatch': 'Canonical mismatch'
            };
            
            const issues = item.issues.map(i => issueLabels[i] || i).join(', ');
            
            html += '<tr>';
            html += `<td>${item.url}</td>`;
            html += `<td>${issues}</td>`;
            html += `<td>${item.http || '-'}</td>`;
            html += `<td>${item.detail || '-'}</td>`;
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        
        $('#conflicts-list').html(html);
        $('#conflicts-modal').fadeIn();
    }
    
    // Export conflicts CSV
    $('#export-conflicts-csv').on('click', function() {
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_export_conflicts',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                downloadCSV(response.data.csv, response.data.filename);
            }
        });
    });
    
    /**
     * Phase 3: Diff Report
     */
    
    // Create snapshot button
    $('#create-snapshot-btn').on('click', function() {
        const $btn = $(this);
        const originalHtml = $btn.html();
        
        $btn.html('<span class="dashicons dashicons-update spinning"></span> Creating...');
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_create_snapshot',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                showToast(response.data.message, 'success');
                
                // Update display
                const info = `Snapshot created: ${response.data.urls} URLs (${formatBytes(response.data.size)})`;
                showToast(info, 'info');
                
                // Reload to show new snapshot
                setTimeout(() => location.reload(), 1500);
            } else {
                showToast(response.data || 'Failed to create snapshot', 'error');
            }
            
            $btn.html(originalHtml);
            $btn.prop('disabled', false);
        });
    });
    
    // Compare snapshots button
    $('#compare-snapshots-btn').on('click', function() {
        const $btn = $(this);
        const originalHtml = $btn.html();
        
        $btn.html('<span class="dashicons dashicons-update spinning"></span> Comparing...');
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_compare_snapshots',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                displayDiffModal(response.data.summary);
            } else {
                showToast(response.data || 'Comparison failed', 'error');
            }
            
            $btn.html(originalHtml);
            $btn.prop('disabled', false);
        });
    });
    
    // Display diff modal
    function displayDiffModal(summary) {
        // Set up tabs
        $('.almaseo-tab').on('click', function() {
            $('.almaseo-tab').removeClass('active');
            $(this).addClass('active');
            
            const tab = $(this).data('tab');
            displayDiffTab(tab, summary);
        });
        
        // Show first tab
        displayDiffTab('added', summary);
        
        $('#diff-modal').fadeIn();
    }
    
    // Display diff tab content
    function displayDiffTab(tab, summary) {
        let html = '';
        let items = [];
        
        if (tab === 'added' && summary.sample_added) {
            items = summary.sample_added;
        } else if (tab === 'removed' && summary.sample_removed) {
            items = summary.sample_removed;
        } else if (tab === 'changed' && summary.sample_changed) {
            items = summary.sample_changed;
        }
        
        if (items.length > 0) {
            html = '<ul class="almaseo-url-list">';
            items.forEach(function(item) {
                if (tab === 'changed') {
                    html += `<li>${item.url}<br>
                        <small>Old: ${item.old_lastmod || 'none'} → New: ${item.new_lastmod || 'none'}</small>
                    </li>`;
                } else {
                    html += `<li>${item.url}`;
                    if (item.lastmod) {
                        html += `<br><small>Last modified: ${item.lastmod}</small>`;
                    }
                    html += `</li>`;
                }
            });
            html += '</ul>';
            
            if (items.length === 5) {
                html += '<p class="description">Showing first 5 items. Export CSV for full list.</p>';
            }
        } else {
            html = '<p>No ' + tab + ' URLs</p>';
        }
        
        $('#diff-content').html(html);
    }
    
    // Export diff CSV
    $('#export-diff-csv, #export-diff-btn').on('click', function() {
        // Determine which type to export
        const activeTab = $('.almaseo-tab.active').data('tab') || 'added';
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_export_diff',
            nonce: almaseoSitemaps.nonce,
            type: activeTab
        }, function(response) {
            if (response.success) {
                downloadCSV(response.data.csv, response.data.filename);
            } else {
                showToast(response.data || 'Export failed', 'error');
            }
        });
    });
    
    /**
     * Utility Functions
     */
    
    // Download CSV
    function downloadCSV(content, filename) {
        const blob = new Blob([content], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    // Format bytes
    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }
    
    // Show toast notification
    function showToast(message, type = 'info') {
        const $toast = $('#almaseo-toast');
        $toast.removeClass('success error warning info').addClass(type);
        $toast.text(message).addClass('show');
        
        setTimeout(function() {
            $toast.removeClass('show');
        }, 3000);
    }
    
})(jQuery);/**
 * AlmaSEO Sitemaps Phase 4 JavaScript
 * 
 * Static generation, performance controls, and build management
 * 
 * @package AlmaSEO
 * @since 4.7.0
 */

(function($) {
    'use strict';
    
    /**
     * Phase 4: Static Build Management
     */
    
    // Rebuild static sitemaps
    $('#rebuild-static').on('click', function() {
        const $btn = $(this);
        const originalText = $btn.find('.button-text').text();
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        if (!confirm('This will regenerate all sitemap files. Continue?')) {
            return;
        }
        
        $btn.prop('disabled', true);
        $btn.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-update spin');
        $btn.find('.button-text').text('Building...');
        
        // Add progress indicator
        const $progress = $('<div class="almaseo-build-progress">' +
            '<div class="progress-bar"><div class="progress-fill"></div></div>' +
            '<div class="progress-text">Starting build...</div>' +
            '</div>');
        $btn.parent().after($progress);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_rebuild_static',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                showToast(response.data.message, 'success');
                
                // Update UI with new stats
                if (response.data.stats) {
                    updateBuildStats(response.data.stats);
                }
                
                // Reload page after 2 seconds to show new stats
                setTimeout(function() {
                    location.reload();
                }, 2000);
            } else {
                showToast(response.data || 'Build failed', 'error');
                $btn.prop('disabled', false);
                $btn.find('.dashicons').removeClass('spin').addClass('dashicons-update');
                $btn.find('.button-text').text(originalText);
            }
            
            $progress.fadeOut(function() {
                $(this).remove();
            });
        }).fail(function() {
            showToast('Build failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.find('.dashicons').removeClass('spin').addClass('dashicons-update');
            $btn.find('.button-text').text(originalText);
            $progress.remove();
        });
    });
    
    /**
     * Storage mode toggle. Replaced the dead .almaseo-save-all.show() call
     * (no element by that class is rendered anywhere) with markChanged()
     * so the auto-save debounce actually fires.
     */
    $('input[name="storage_mode"]').on('change', function() {
        const mode = $(this).val();
        if (mode === 'static') {
            $('#recalculate').hide();
            $('#rebuild-static').show();
        } else {
            $('#rebuild-static').hide();
            $('#recalculate').show();
        }
        markChanged();
    });

    /**
     * Gzip toggle.
     */
    $('#enable-gzip').on('change', function() {
        markChanged();
    });

    /**
     * Advanced exclusion rules — taxonomies, authors, older-than-years.
     * Bind change events to the auto-save debounce. The posts/pages/cpts
     * providers already apply these filters at the SQL level (see
     * Alma_Provider_Posts::build_exclude_joins / build_exclude_where);
     * what was missing was the JS save payload + PHP accept.
     */
    $('#exclude-taxonomies, #exclude-authors, #exclude-older-than').on('change', function() {
        markChanged();
    });
    
    /**
     * Update build stats in UI
     */
    function updateBuildStats(stats) {
        // Update chips
        $('.almaseo-chip').each(function() {
            const $chip = $(this);
            const text = $chip.text();
            
            if (text.includes('Files:')) {
                $chip.html('Files: <strong>' + stats.files + '</strong>');
            } else if (text.includes('URLs:')) {
                $chip.html('URLs: <strong>' + stats.urls + '</strong>');
            } else if (text.includes('Duration:')) {
                const duration = (stats.duration_ms / 1000).toFixed(1);
                $chip.html('Duration: <strong>' + duration + 's</strong>');
            }
        });
    }
    
    /**
     * Check build status periodically
     */
    let buildStatusInterval = null;
    
    function checkBuildStatus() {
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_get_build_status',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success && response.data) {
                if (response.data.is_building) {
                    // Show building indicator
                    if (!$('#rebuild-static').prop('disabled')) {
                        $('#rebuild-static').prop('disabled', true);
                        $('#rebuild-static .button-text').text('Building...');
                        $('#rebuild-static .dashicons').addClass('spin');
                    }
                    
                    // Start checking if not already
                    if (!buildStatusInterval) {
                        buildStatusInterval = setInterval(checkBuildStatus, 5000);
                    }
                } else {
                    // Build complete
                    if ($('#rebuild-static').prop('disabled')) {
                        $('#rebuild-static').prop('disabled', false);
                        $('#rebuild-static .button-text').text('Rebuild (Static)');
                        $('#rebuild-static .dashicons').removeClass('spin');
                        
                        // Reload to show new stats
                        location.reload();
                    }
                    
                    // Stop checking
                    if (buildStatusInterval) {
                        clearInterval(buildStatusInterval);
                        buildStatusInterval = null;
                    }
                }
            }
        });
    }
    
    // Check on page load if in static mode
    $(document).ready(function() {
        const isStatic = $('input[name="storage_mode"]:checked').val() === 'static';
        const isBuilding = $('#rebuild-static').prop('disabled');
        
        if (isStatic && isBuilding) {
            checkBuildStatus();
        }
    });
    
    /**
     * Toast notification helper
     */
    function showToast(message, type) {
        const $toast = $('<div class="almaseo-toast almaseo-toast-' + type + '">' +
            '<span class="dashicons dashicons-' + (type === 'success' ? 'yes' : 'warning') + '"></span>' +
            message + '</div>');
        
        $('body').append($toast);
        
        setTimeout(function() {
            $toast.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }
    
})(jQuery);/**
 * AlmaSEO Sitemaps Phase 5A JavaScript
 * 
 * Delta sitemap controls and management
 * 
 * @package AlmaSEO
 * @since 4.8.0
 */

(function($) {
    'use strict';
    
    /**
     * Phase 5A: Delta Sitemap Management
     */
    
    // Enable/disable delta sitemap
    $('#delta-enabled').on('change', function() {
        $('.almaseo-save-all').show();
    });
    
    // Delta settings
    $('#delta-max-urls, #delta-retention').on('change', function() {
        $('.almaseo-save-all').show();
    });
    
    // Open delta sitemap
    $('#open-delta').on('click', function() {
        window.open($('#delta-url').val(), '_blank');
    });
    
    // Copy delta URL
    $('#copy-delta-url').on('click', function() {
        const $btn = $(this);
        const $input = $('#delta-url');
        
        // Select and copy
        $input.select();
        document.execCommand('copy');
        
        // Visual feedback
        $btn.find('.dashicons').removeClass('dashicons-clipboard').addClass('dashicons-yes');
        $btn.text(almaseoSitemaps.i18n.copied);
        
        setTimeout(function() {
            $btn.find('.dashicons').removeClass('dashicons-yes').addClass('dashicons-clipboard');
            $btn.text('Copy URL');
        }, 2000);
    });
    
    // Force ping
    $('#force-ping').on('click', function() {
        const $btn = $(this);
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        if (!confirm('This will immediately submit URLs to IndexNow. Continue?')) {
            return;
        }
        
        $btn.prop('disabled', true);
        const originalText = $btn.text();
        $btn.find('.dashicons').removeClass('dashicons-megaphone').addClass('dashicons-update spin');
        $btn.contents().filter(function() {
            return this.nodeType === 3;
        }).replaceWith(' Pinging...');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_force_delta_ping',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                showToast(response.data.message, 'success');
                
                // Update UI to reflect successful ping
                const $chips = $('.almaseo-chip');
                $chips.each(function() {
                    const text = $(this).text();
                    if (text.includes('Last ping:')) {
                        $(this).html('Last ping: <strong>just now</strong>');
                    }
                });
            } else {
                showToast(response.data || 'Ping failed', 'error');
            }
            
            $btn.prop('disabled', false);
            $btn.find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-megaphone');
            $btn.contents().filter(function() {
                return this.nodeType === 3;
            }).replaceWith(' Force Ping');
        }).fail(function() {
            showToast('Ping failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.find('.dashicons').removeClass('dashicons-update spin').addClass('dashicons-megaphone');
            $btn.contents().filter(function() {
                return this.nodeType === 3;
            }).replaceWith(' Force Ping');
        });
    });
    
    // Purge old entries
    $('#purge-old').on('click', function() {
        const $btn = $(this);
        
        if (!confirm('This will remove entries older than the retention period. Continue?')) {
            return;
        }
        
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.find('.dashicons').removeClass('dashicons-trash').addClass('dashicons-update spin');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_purge_old_delta',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                showToast(response.data.message, 'success');
                
                // Update count in UI
                $('.almaseo-chip').each(function() {
                    const text = $(this).text();
                    if (text.includes('URLs') && !text.includes('Max')) {
                        $(this).text(response.data.remaining + ' URLs');
                    }
                });
                
                // Update info box if present
                $('.almaseo-info-box p:first').text(
                    'Ring buffer: ' + response.data.remaining + ' URLs'
                );
                
                // Disable force ping if no URLs left
                if (response.data.remaining === 0) {
                    $('#force-ping').prop('disabled', true);
                }
            } else {
                showToast(response.data || 'Purge failed', 'error');
            }
            
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        }).fail(function() {
            showToast('Purge failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        });
    });
    
    /**
     * Save settings (extend existing handler)
     */
    $(document).on('almaseo-save-settings', function(e, settings) {
        // Add delta settings
        settings.delta = {
            enabled: $('#delta-enabled').is(':checked'),
            max_urls: parseInt($('#delta-max-urls').val()),
            retention_days: parseInt($('#delta-retention').val())
        };
    });
    
    /**
     * Live update of delta status
     */
    function updateDeltaStatus() {
        // This could be called periodically to refresh delta stats
        // For now, it's manual via the buttons
    }
    
    /**
     * Toast notification helper
     */
    function showToast(message, type) {
        const $toast = $('<div class="almaseo-toast almaseo-toast-' + type + '">' +
            '<span class="dashicons dashicons-' + (type === 'success' ? 'yes' : 'warning') + '"></span>' +
            message + '</div>');
        
        $('body').append($toast);
        
        setTimeout(function() {
            $toast.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }
    
})(jQuery);/**
 * AlmaSEO Sitemaps Phase 5B JavaScript
 * 
 * Hreflang controls and management
 * 
 * @package AlmaSEO
 * @since 4.9.0
 */

(function($) {
    'use strict';
    
    /**
     * Phase 5B: Hreflang Management
     */
    
    // Enable/disable hreflang
    $('#hreflang-enabled').on('change', function() {
        $('.almaseo-save-all').show();
        
        // Toggle visibility of settings
        const enabled = $(this).is(':checked');
        $('.hreflang-settings').toggleClass('disabled', !enabled);
    });
    
    // Hreflang settings changes
    $('#hreflang-source, #hreflang-default, #hreflang-x-default').on('change', function() {
        $('.almaseo-save-all').show();
    });
    
    // Locale mapping changes
    $('.hreflang-locale-map').on('change', function() {
        $('.almaseo-save-all').show();
    });
    
    // Validate hreflang
    $('#validate-hreflang').on('click', function() {
        const $btn = $(this);
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.find('.dashicons').removeClass('dashicons-yes-alt').addClass('dashicons-update spin');
        $btn.contents().filter(function() {
            return this.nodeType === 3;
        }).replaceWith(' Validating...');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_validate_hreflang',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                const result = response.data.result;
                
                if (result.ok) {
                    showToast(response.data.message, 'success');
                    
                    // Update UI to show valid status
                    updateValidationUI(result, true);
                } else {
                    showToast(response.data.message, 'warning');
                    
                    // Update UI to show issues
                    updateValidationUI(result, false);
                    
                    // Show export button if issues found
                    $('#export-hreflang-issues').show();
                }
            } else {
                showToast(response.data || 'Validation failed', 'error');
            }
            
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        }).fail(function() {
            showToast('Validation failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        });
    });
    
    // Rebuild with hreflang
    $('#rebuild-with-hreflang').on('click', function() {
        const $btn = $(this);
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        if (!confirm('This will rebuild all sitemaps with hreflang data. Continue?')) {
            return;
        }
        
        // Trigger the rebuild (reuse Phase 4 rebuild)
        $('#rebuild-static').trigger('click');
    });
    
    // Export hreflang issues
    $('#export-hreflang-issues').on('click', function() {
        const $btn = $(this);
        
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_export_hreflang_issues',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                // Download CSV
                downloadCSV(response.data.csv, response.data.filename);
                showToast('Export complete', 'success');
            } else {
                showToast(response.data || 'Export failed', 'error');
            }
            
            $btn.prop('disabled', false);
        }).fail(function() {
            showToast('Export failed - server error', 'error');
            $btn.prop('disabled', false);
        });
    });
    
    /**
     * Update validation UI
     */
    function updateValidationUI(result, isValid) {
        // Update chips
        const $validationChip = $('.almaseo-chip:contains("Valid"), .almaseo-chip:contains("Issues")');
        
        if ($validationChip.length) {
            $validationChip.removeClass('almaseo-chip-success almaseo-chip-warning');
            
            if (isValid) {
                $validationChip.addClass('almaseo-chip-success').text('Valid');
            } else {
                const issueCount = result.missing_pairs + result.orphans + result.mismatch;
                $validationChip.addClass('almaseo-chip-warning').text(issueCount + ' Issues');
            }
        }
        
        // Update or create info box
        let $infoBox = $('.almaseo-info-box.hreflang-validation');
        
        if (!$infoBox.length) {
            $infoBox = $('<div class="almaseo-info-box hreflang-validation"></div>');
            $('#validate-hreflang').parent().after($infoBox);
        }
        
        $infoBox.removeClass('almaseo-info-success almaseo-info-warning');
        $infoBox.addClass(isValid ? 'almaseo-info-success' : 'almaseo-info-warning');
        
        let html = '<p>Last validation: just now</p>';
        
        if (!isValid) {
            html += '<ul>';
            if (result.missing_pairs > 0) {
                html += '<li>' + result.missing_pairs + ' missing language pairs</li>';
            }
            if (result.orphans > 0) {
                html += '<li>' + result.orphans + ' orphan links</li>';
            }
            if (result.mismatch > 0) {
                html += '<li>' + result.mismatch + ' invalid codes</li>';
            }
            html += '</ul>';
            
            if (result.samples && result.samples.length > 0) {
                html += '<p><strong>Sample issues:</strong></p><ul class="sample-issues">';
                result.samples.slice(0, 3).forEach(function(sample) {
                    html += '<li><code>' + sample.url + '</code>: ' + sample.issue + '</li>';
                });
                html += '</ul>';
            }
        }
        
        $infoBox.html(html);
    }
    
    /**
     * Save settings (extend existing handler)
     */
    $(document).on('almaseo-save-settings', function(e, settings) {
        // Collect hreflang settings
        const locales = {};
        $('.hreflang-locale-map').each(function() {
            const locale = $(this).data('locale');
            const hreflang = $(this).val();
            if (locale && hreflang) {
                locales[locale] = hreflang;
            }
        });
        
        settings.hreflang = {
            enabled: $('#hreflang-enabled').is(':checked'),
            source: $('#hreflang-source').val(),
            default: $('#hreflang-default').val(),
            x_default_url: $('#hreflang-x-default').val(),
            locales: locales
        };
    });
    
    /**
     * Download CSV helper
     */
    function downloadCSV(csv, filename) {
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        
        link.setAttribute('href', url);
        link.setAttribute('download', filename);
        link.style.visibility = 'hidden';
        
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    
    /**
     * Toast notification helper
     */
    function showToast(message, type) {
        const $toast = $('<div class="almaseo-toast almaseo-toast-' + type + '">' +
            '<span class="dashicons dashicons-' + (type === 'success' ? 'yes' : 'warning') + '"></span>' +
            message + '</div>');
        
        $('body').append($toast);
        
        setTimeout(function() {
            $toast.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }
    
})(jQuery);/**
 * AlmaSEO Sitemaps Phase 5C JavaScript
 * 
 * Media sitemaps UI and controls
 * 
 * @package AlmaSEO
 * @since 4.10.0
 */

(function($) {
    'use strict';
    
    /**
     * Phase 5C: Media Sitemaps
     */
    
    // Enable/disable image sitemap
    $('#media-image-enabled').on('change', function() {
        $('.almaseo-save-all').show();
        
        // Toggle visibility of image settings
        const enabled = $(this).is(':checked');
        $('.image-settings').toggleClass('disabled', !enabled);
        
        // Update UI
        if (enabled) {
            $('#open-image-sitemap').parent().parent().show();
        } else {
            $('#open-image-sitemap').parent().parent().hide();
        }
    });
    
    // Enable/disable video sitemap
    $('#media-video-enabled').on('change', function() {
        $('.almaseo-save-all').show();
        
        // Toggle visibility of video settings
        const enabled = $(this).is(':checked');
        $('.video-settings').toggleClass('disabled', !enabled);
        
        // Update UI
        if (enabled) {
            $('#open-video-sitemap').parent().parent().show();
        } else {
            $('#open-video-sitemap').parent().parent().hide();
        }
    });
    
    // Media settings changes
    $('#media-image-max, #media-image-dedupe, #media-video-max, #media-video-oembed').on('change', function() {
        $('.almaseo-save-all').show();
    });
    
    // Open image sitemap
    $('#open-image-sitemap').on('click', function() {
        window.open(almaseoSitemaps.sitemapUrl.replace('-sitemap.xml', '-sitemap-image-1.xml'), '_blank');
    });
    
    // Open video sitemap
    $('#open-video-sitemap').on('click', function() {
        window.open(almaseoSitemaps.sitemapUrl.replace('-sitemap.xml', '-sitemap-video-1.xml'), '_blank');
    });
    
    // Scan media
    $('#scan-media').on('click', function() {
        const $btn = $(this);
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.find('.dashicons').removeClass('dashicons-search').addClass('dashicons-update spin');
        $btn.contents().filter(function() {
            return this.nodeType === 3;
        }).replaceWith(' Scanning...');
        
        // Show stats box
        const $stats = $('#media-stats');
        $stats.show().html('<p>Scanning media content...</p>');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_scan_media',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                const stats = response.data.stats;
                let html = '<h4>Media Scan Results</h4>';
                
                // Image stats
                if (stats.images.found > 0 || stats.images.urls_with_images > 0) {
                    html += '<div class="media-stat-section">';
                    html += '<strong>Images:</strong><br>';
                    html += stats.images.found + ' images found across ' + stats.images.urls_with_images + ' URLs<br>';
                    if (stats.images.sample_urls.length > 0) {
                        html += '<small>Sample URLs with images:</small><ul>';
                        stats.images.sample_urls.forEach(function(url) {
                            html += '<li><code>' + url + '</code></li>';
                        });
                        html += '</ul>';
                    }
                    html += '</div>';
                }
                
                // Video stats
                if (stats.videos.found > 0 || stats.videos.urls_with_videos > 0) {
                    html += '<div class="media-stat-section">';
                    html += '<strong>Videos:</strong><br>';
                    html += stats.videos.found + ' videos found across ' + stats.videos.urls_with_videos + ' URLs<br>';
                    if (stats.videos.sample_urls.length > 0) {
                        html += '<small>Sample URLs with videos:</small><ul>';
                        stats.videos.sample_urls.forEach(function(url) {
                            html += '<li><code>' + url + '</code></li>';
                        });
                        html += '</ul>';
                    }
                    html += '</div>';
                }
                
                if (stats.images.found === 0 && stats.videos.found === 0) {
                    html += '<p>No media content found. Make sure media sitemaps are enabled and your content contains images or videos.</p>';
                }
                
                $stats.removeClass('almaseo-info-warning').addClass('almaseo-info-success').html(html);
                showToast(response.data.message, 'success');
            } else {
                $stats.removeClass('almaseo-info-success').addClass('almaseo-info-warning')
                    .html('<p>Scan failed: ' + (response.data || 'Unknown error') + '</p>');
                showToast(response.data || 'Scan failed', 'error');
            }
            
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        }).fail(function() {
            $stats.removeClass('almaseo-info-success').addClass('almaseo-info-warning')
                .html('<p>Scan failed - server error</p>');
            showToast('Scan failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        });
    });
    
    // Validate media
    $('#validate-media').on('click', function() {
        const $btn = $(this);
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.find('.dashicons').removeClass('dashicons-yes-alt').addClass('dashicons-update spin');
        $btn.contents().filter(function() {
            return this.nodeType === 3;
        }).replaceWith(' Validating...');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_validate_media',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                const result = response.data.result;
                let hasIssues = false;
                let issueHtml = '<h4>Validation Results</h4>';
                
                // Image validation
                if (result.image.enabled) {
                    issueHtml += '<div class="media-validation-section">';
                    issueHtml += '<strong>Image Sitemap:</strong> ';
                    if (result.image.issues.length === 0) {
                        issueHtml += '<span style="color: green;">✓ Valid</span><br>';
                        issueHtml += result.image.count + ' images validated';
                    } else {
                        hasIssues = true;
                        issueHtml += '<span style="color: orange;">⚠ ' + result.image.issues.length + ' issues</span><br>';
                        issueHtml += '<ul>';
                        result.image.issues.slice(0, 5).forEach(function(issue) {
                            issueHtml += '<li>' + issue + '</li>';
                        });
                        if (result.image.issues.length > 5) {
                            issueHtml += '<li>... and ' + (result.image.issues.length - 5) + ' more</li>';
                        }
                        issueHtml += '</ul>';
                    }
                    issueHtml += '</div>';
                }
                
                // Video validation
                if (result.video.enabled) {
                    issueHtml += '<div class="media-validation-section">';
                    issueHtml += '<strong>Video Sitemap:</strong> ';
                    if (result.video.issues.length === 0) {
                        issueHtml += '<span style="color: green;">✓ Valid</span><br>';
                        issueHtml += result.video.count + ' videos validated';
                    } else {
                        hasIssues = true;
                        issueHtml += '<span style="color: orange;">⚠ ' + result.video.issues.length + ' issues</span><br>';
                        issueHtml += '<ul>';
                        result.video.issues.slice(0, 5).forEach(function(issue) {
                            issueHtml += '<li>' + issue + '</li>';
                        });
                        if (result.video.issues.length > 5) {
                            issueHtml += '<li>... and ' + (result.video.issues.length - 5) + ' more</li>';
                        }
                        issueHtml += '</ul>';
                    }
                    issueHtml += '</div>';
                }
                
                const $stats = $('#media-stats');
                $stats.show()
                    .removeClass('almaseo-info-warning almaseo-info-success')
                    .addClass(hasIssues ? 'almaseo-info-warning' : 'almaseo-info-success')
                    .html(issueHtml);
                
                showToast(response.data.message, hasIssues ? 'warning' : 'success');
            } else {
                showToast(response.data || 'Validation failed', 'error');
            }
            
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        }).fail(function() {
            showToast('Validation failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        });
    });
    
    // Rebuild media sitemaps
    $('#rebuild-media').on('click', function() {
        const $btn = $(this);
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        if (!confirm('This will rebuild all media sitemaps. Continue?')) {
            return;
        }
        
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-update spin');
        $btn.contents().filter(function() {
            return this.nodeType === 3;
        }).replaceWith(' Building...');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_rebuild_media',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                showToast(response.data.message, 'success');
                
                // Update stats display
                if (response.data.stats) {
                    const stats = response.data.stats;
                    let html = '<h4>Build Complete</h4>';
                    html += '<p>' + stats.files + ' files generated with ' + stats.urls + ' total URLs</p>';
                    html += '<p>Duration: ' + (stats.duration_ms / 1000).toFixed(1) + ' seconds</p>';
                    
                    $('#media-stats').show()
                        .removeClass('almaseo-info-warning')
                        .addClass('almaseo-info-success')
                        .html(html);
                }
            } else {
                showToast(response.data || 'Build failed', 'error');
            }
            
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        }).fail(function() {
            showToast('Build failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        });
    });
    
    /**
     * Save settings (extend existing handler)
     */
    $(document).on('almaseo-save-settings', function(e, settings) {
        // Collect media settings
        settings.media = {
            image: {
                enabled: $('#media-image-enabled').is(':checked'),
                max_per_url: parseInt($('#media-image-max').val()) || 20,
                dedupe_cdn: $('#media-image-dedupe').is(':checked')
            },
            video: {
                enabled: $('#media-video-enabled').is(':checked'),
                max_per_url: parseInt($('#media-video-max').val()) || 10,
                oembed_cache: $('#media-video-oembed').is(':checked')
            }
        };
    });
    
    /**
     * Toast notification helper
     */
    function showToast(message, type) {
        const $toast = $('<div class="almaseo-toast almaseo-toast-' + type + '">' +
            '<span class="dashicons dashicons-' + (type === 'success' ? 'yes' : 'warning') + '"></span>' +
            message + '</div>');
        
        $('body').append($toast);
        
        setTimeout(function() {
            $toast.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }
    
})(jQuery);/**
 * AlmaSEO Sitemaps Phase 5D JavaScript
 * 
 * News sitemap UI and controls
 * 
 * @package AlmaSEO
 * @since 4.11.0
 */

(function($) {
    'use strict';
    
    /**
     * Phase 5D: News Sitemap
     */
    
    // Enable/disable news sitemap
    $('#news-enabled').on('change', function() {
        $('.almaseo-save-all').show();
        
        // Toggle visibility of news settings
        const enabled = $(this).is(':checked');
        $('.news-settings').toggleClass('disabled', !enabled);
    });
    
    // News settings changes
    $('#news-publisher, #news-language, #news-window, #news-max-items, #news-manual-keywords').on('change', function() {
        $('.almaseo-save-all').show();
    });
    
    $('input[name="news[post_types][]"], input[name="news[categories][]"], input[name="news[genres][]"]').on('change', function() {
        $('.almaseo-save-all').show();
    });
    
    // Keywords source toggle
    $('input[name="news[keywords_source]"]').on('change', function() {
        $('.almaseo-save-all').show();
        
        if ($(this).val() === 'manual') {
            $('#news-manual-keywords-group').slideDown();
        } else {
            $('#news-manual-keywords-group').slideUp();
        }
    });
    
    // Open news sitemap
    $('#open-news-sitemap').on('click', function() {
        window.open(almaseoSitemaps.sitemapUrl.replace('-sitemap.xml', '-sitemap-news-1.xml'), '_blank');
    });
    
    // Copy news URL
    $('#copy-news-url').on('click', function() {
        const url = almaseoSitemaps.sitemapUrl.replace('-sitemap.xml', '-sitemap-news-1.xml');
        const $temp = $('<input>');
        $('body').append($temp);
        $temp.val(url).select();
        document.execCommand('copy');
        $temp.remove();
        
        const $btn = $(this);
        const originalHtml = $btn.html();
        $btn.html('<span class="dashicons dashicons-yes"></span>');
        showToast('URL copied!', 'success');
        
        setTimeout(function() {
            $btn.html(originalHtml);
        }, 2000);
    });
    
    // Validate news
    $('#validate-news').on('click', function() {
        const $btn = $(this);
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.find('.dashicons').removeClass('dashicons-yes-alt').addClass('dashicons-update spin');
        $btn.contents().filter(function() {
            return this.nodeType === 3;
        }).replaceWith(' Validating...');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_validate_news',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                const result = response.data.result;
                
                // Update chips
                updateNewsChips(result);
                
                // Update validation box
                updateNewsValidationBox(result);
                
                showToast(response.data.message, result.issues.length === 0 ? 'success' : 'warning');
            } else {
                showToast(response.data || 'Validation failed', 'error');
            }
            
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        }).fail(function() {
            showToast('Validation failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        });
    });
    
    // Rebuild news sitemap
    $('#rebuild-news').on('click', function() {
        const $btn = $(this);
        
        if ($btn.prop('disabled')) {
            return;
        }
        
        if (!confirm('This will rebuild the news sitemap. Continue?')) {
            return;
        }
        
        $btn.prop('disabled', true);
        const originalHtml = $btn.html();
        $btn.find('.dashicons').removeClass('dashicons-update').addClass('dashicons-update spin');
        $btn.contents().filter(function() {
            return this.nodeType === 3;
        }).replaceWith(' Building...');
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_rebuild_news',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                showToast(response.data.message, 'success');
                
                // Update last build chip
                updateNewsBuildChip();
                
                // Optionally trigger validation
                setTimeout(function() {
                    $('#validate-news').trigger('click');
                }, 1000);
            } else {
                showToast(response.data || 'Build failed', 'error');
            }
            
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        }).fail(function() {
            showToast('Build failed - server error', 'error');
            $btn.prop('disabled', false);
            $btn.html(originalHtml);
        });
    });
    
    /**
     * Update news chips after validation
     */
    function updateNewsChips(result) {
        const $chips = $('.almaseo-card:has(#news-enabled) .almaseo-chips');
        
        // Update items count chip
        let $itemsChip = $chips.find('.almaseo-chip:contains("Items")');
        if ($itemsChip.length === 0) {
            $itemsChip = $('<span class="almaseo-chip"></span>');
            $chips.append($itemsChip);
        }
        $itemsChip.text(result.items + ' Items');
        
        // Update validation chip
        let $validChip = $chips.find('.almaseo-chip:contains("Valid"), .almaseo-chip:contains("Issues")');
        if ($validChip.length === 0) {
            $validChip = $('<span class="almaseo-chip"></span>');
            $chips.append($validChip);
        }
        
        if (result.issues.length === 0) {
            $validChip.removeClass('almaseo-chip-warning').addClass('almaseo-chip-success').text('Valid');
        } else {
            $validChip.removeClass('almaseo-chip-success').addClass('almaseo-chip-warning').text(result.issues.length + ' Issues');
        }
    }
    
    /**
     * Update news validation info box
     */
    function updateNewsValidationBox(result) {
        let $box = $('.almaseo-card:has(#news-enabled) .almaseo-info-box');
        
        if ($box.length === 0) {
            $box = $('<div class="almaseo-info-box"></div>');
            $('#rebuild-news').parent().after($box);
        }
        
        $box.removeClass('almaseo-info-success almaseo-info-warning');
        $box.addClass(result.issues.length === 0 ? 'almaseo-info-success' : 'almaseo-info-warning');
        
        let html = '<p>Last validation: just now</p>';
        
        if (result.samples && result.samples.length > 0) {
            html += '<p>Sample articles:</p><ul>';
            result.samples.forEach(function(sample) {
                html += '<li><strong>' + sample.title + '</strong><br>';
                html += '<small>' + sample.date + '</small></li>';
            });
            html += '</ul>';
        }
        
        if (result.issues.length > 0) {
            html += '<p>Issues found:</p><ul>';
            result.issues.slice(0, 5).forEach(function(issue) {
                html += '<li>' + issue + '</li>';
            });
            if (result.issues.length > 5) {
                html += '<li>... and ' + (result.issues.length - 5) + ' more</li>';
            }
            html += '</ul>';
        }
        
        $box.html(html).show();
    }
    
    /**
     * Update build chip after rebuild
     */
    function updateNewsBuildChip() {
        const $chips = $('.almaseo-card:has(#news-enabled) .almaseo-chips');
        let $buildChip = $chips.find('.almaseo-chip:contains("Built:")');
        
        if ($buildChip.length === 0) {
            $buildChip = $('<span class="almaseo-chip"></span>');
            $chips.append($buildChip);
        }
        
        $buildChip.text('Built: just now');
    }
    
    /**
     * Save settings (extend existing handler)
     */
    $(document).on('almaseo-save-settings', function(e, settings) {
        // Collect news settings
        settings.news = {
            enabled: $('#news-enabled').is(':checked'),
            post_types: [],
            categories: [],
            publisher_name: $('#news-publisher').val(),
            language: $('#news-language').val(),
            genres: [],
            keywords_source: $('input[name="news[keywords_source]"]:checked').val(),
            manual_keywords: $('#news-manual-keywords').val(),
            max_items: parseInt($('#news-max-items').val()) || 1000,
            window_hours: parseInt($('#news-window').val()) || 48
        };
        
        // Collect post types
        $('input[name="news[post_types][]"]:checked').each(function() {
            settings.news.post_types.push($(this).val());
        });
        
        // Collect categories
        $('input[name="news[categories][]"]:checked').each(function() {
            settings.news.categories.push(parseInt($(this).val()));
        });
        
        // Collect genres
        $('input[name="news[genres][]"]:checked').each(function() {
            settings.news.genres.push($(this).val());
        });
    });
    
    /**
     * Toast notification helper
     */
    function showToast(message, type) {
        const $toast = $('<div class="almaseo-toast almaseo-toast-' + type + '">' +
            '<span class="dashicons dashicons-' + (type === 'success' ? 'yes' : 'warning') + '"></span>' +
            message + '</div>');
        
        $('body').append($toast);
        
        setTimeout(function() {
            $toast.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }
    
})(jQuery);/**
 * AlmaSEO Sitemaps Phase 6 JavaScript
 * 
 * Final polish: Export/Import, Health Log, Quick Tools
 * 
 * @package AlmaSEO
 * @since 4.12.0
 */

(function($) {
    'use strict';
    
    /**
     * Phase 6: Settings Export/Import
     */
    
    // Export settings
    $('#export-settings-btn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_export_settings',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                // Download JSON
                const blob = new Blob([response.data.json], { type: 'application/json' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = response.data.filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                showToast('Settings exported', 'success');
            } else {
                showToast(response.data || 'Export failed', 'error');
            }
            
            $btn.prop('disabled', false);
        }).fail(function() {
            showToast('Export failed', 'error');
            $btn.prop('disabled', false);
        });
    });
    
    // Import settings
    $('#import-settings-btn').on('click', function() {
        $('#import-settings-file').trigger('click');
    });
    
    $('#import-settings-file').on('change', function(e) {
        const file = e.target.files[0];
        if (!file) return;
        
        const reader = new FileReader();
        reader.onload = function(e) {
            const json = e.target.result;
            
            if (!confirm('This will replace your current settings. Continue?')) {
                $('#import-settings-file').val('');
                return;
            }
            
            $.post(almaseoSitemaps.ajaxUrl, {
                action: 'almaseo_import_settings',
                nonce: almaseoSitemaps.nonce,
                json: json
            }, function(response) {
                if (response.success) {
                    showToast(response.data, 'success');
                    // Reload page to reflect new settings
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showToast(response.data || 'Import failed', 'error');
                }
                
                $('#import-settings-file').val('');
            }).fail(function() {
                showToast('Import failed', 'error');
                $('#import-settings-file').val('');
            });
        };
        
        reader.readAsText(file);
    });
    
    /**
     * Phase 6: Health Log
     */
    
    // Export logs
    $('#export-logs-btn').on('click', function() {
        const $btn = $(this);
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_export_logs',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                // Download CSV
                const blob = new Blob([response.data.csv], { type: 'text/csv' });
                const url = URL.createObjectURL(blob);
                const a = document.createElement('a');
                a.href = url;
                a.download = response.data.filename;
                document.body.appendChild(a);
                a.click();
                document.body.removeChild(a);
                URL.revokeObjectURL(url);
                
                showToast('Logs exported', 'success');
            } else {
                showToast(response.data || 'No logs to export', 'error');
            }
            
            $btn.prop('disabled', false);
        }).fail(function() {
            showToast('Export failed', 'error');
            $btn.prop('disabled', false);
        });
    });
    
    // Clear logs
    $('#clear-logs-btn').on('click', function() {
        if (!confirm('Clear all health logs?')) {
            return;
        }
        
        const $btn = $(this);
        $btn.prop('disabled', true);
        
        $.post(almaseoSitemaps.ajaxUrl, {
            action: 'almaseo_clear_logs',
            nonce: almaseoSitemaps.nonce
        }, function(response) {
            if (response.success) {
                showToast(response.data, 'success');
                // Refresh log display
                $('.almaseo-log-list').fadeOut(function() {
                    $(this).html('<p>No events logged yet.</p>').fadeIn();
                });
                $('.almaseo-log-stats span:first').text('0 events total');
                $('.almaseo-log-stats span:last').text('0 in last 24h');
            } else {
                showToast(response.data || 'Failed to clear logs', 'error');
            }
            
            $btn.prop('disabled', false);
        }).fail(function() {
            showToast('Failed to clear logs', 'error');
            $btn.prop('disabled', false);
        });
    });
    
    /**
     * Phase 6: Quick Tools
     */
    
    // NOTE: the Phase 6 "Copy all sitemap URLs" handler that lived here was
    // removed — it double-bound #copy-all-urls-btn alongside the delegated
    // handler above (~line 633) and read response.data.text, a key
    // handle_copy_all_urls never returns (it returns {urls:[...]}), so it
    // overwrote the textarea with "undefined". The delegated handler is
    // canonical.

    // Copy shortcode
    $('#copy-shortcode').on('click', function(e) {
        e.preventDefault();
        
        const shortcode = '[almaseo_html_sitemap types="posts,pages,tax" columns="2"]';
        const $temp = $('<input>');
        $('body').append($temp);
        $temp.val(shortcode).select();
        document.execCommand('copy');
        $temp.remove();
        
        const $btn = $(this);
        const originalText = $btn.text();
        $btn.text('Copied!');
        
        setTimeout(function() {
            $btn.text(originalText);
        }, 2000);
    });
    
    // Preview robots.txt
    $('#preview-robots-btn').on('click', function() {
        const $btn = $(this);
        const $preview = $('#robots-preview');
        
        if ($preview.is(':visible')) {
            $preview.slideUp();
            return;
        }
        
        $btn.prop('disabled', true);
        
        // Fetch robots.txt
        $.get('/robots.txt', function(data) {
            $preview.find('.almaseo-code-preview').text(data);
            $preview.slideDown();
            
            // Highlight sitemap lines
            const lines = data.split('\n');
            const highlightedLines = lines.map(function(line) {
                if (line.toLowerCase().indexOf('sitemap:') === 0) {
                    return '<span class="almaseo-highlight">' + line + '</span>';
                }
                return line;
            });
            $preview.find('.almaseo-code-preview').html(highlightedLines.join('\n'));
            
            $btn.prop('disabled', false);
        }).fail(function() {
            showToast('Failed to load robots.txt', 'error');
            $btn.prop('disabled', false);
        });
    });
    
    /**
     * Phase 6: Help Tooltips
     */
    
    // Initialize tooltips
    $('.almaseo-help-icon').on('mouseenter', function() {
        const $icon = $(this);
        const text = $icon.attr('title');
        
        if (!text) return;
        
        // Create tooltip
        const $tooltip = $('<div class="almaseo-tooltip">' + text + '</div>');
        $('body').append($tooltip);
        
        // Position tooltip
        const offset = $icon.offset();
        $tooltip.css({
            top: offset.top - $tooltip.outerHeight() - 5,
            left: offset.left - ($tooltip.outerWidth() / 2) + ($icon.outerWidth() / 2)
        }).fadeIn(200);
        
        $icon.data('tooltip', $tooltip);
    }).on('mouseleave', function() {
        const $icon = $(this);
        const $tooltip = $icon.data('tooltip');
        
        if ($tooltip) {
            $tooltip.fadeOut(200, function() {
                $tooltip.remove();
            });
            $icon.removeData('tooltip');
        }
    });
    
    // Add help icons to key sections
    $(document).ready(function() {
        // Add help to Delta section
        $('.almaseo-card:has(#delta-enabled) .almaseo-card-header h2').after(
            '<span class="almaseo-help-icon" title="Delta sitemaps track recent changes to help search engines discover new and updated content faster">?</span>'
        );
        
        // Add help to Hreflang section
        $('.almaseo-card:has(#hreflang-enabled) .almaseo-card-header h2').after(
            '<span class="almaseo-help-icon" title="Hreflang tags help search engines understand language and regional variations of your content">?</span>'
        );
        
        // Add help to Media section
        $('.almaseo-card:has(#media-image-enabled) .almaseo-card-header h2').after(
            '<span class="almaseo-help-icon" title="Media sitemaps help search engines discover images and videos on your site">?</span>'
        );
        
        // Add help to News section
        $('.almaseo-card:has(#news-enabled) .almaseo-card-header h2').after(
            '<span class="almaseo-help-icon" title="News sitemaps help your content appear in Google News with a rolling 48-hour window">?</span>'
        );
    });
    
    /**
     * Toast notification helper
     */
    function showToast(message, type) {
        const $toast = $('<div class="almaseo-toast almaseo-toast-' + type + '">' +
            '<span class="dashicons dashicons-' + (type === 'success' ? 'yes' : 'warning') + '"></span>' +
            message + '</div>');
        
        $('body').append($toast);
        
        setTimeout(function() {
            $toast.addClass('show');
        }, 100);
        
        setTimeout(function() {
            $toast.removeClass('show');
            setTimeout(function() {
                $toast.remove();
            }, 300);
        }, 3000);
    }
    
})(jQuery);