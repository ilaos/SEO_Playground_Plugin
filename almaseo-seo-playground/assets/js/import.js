/**
 * AlmaSEO Import / Migration Admin JS
 *
 * Handles all 5 migration steps: post meta, term meta, global settings,
 * redirects, and verification.
 *
 * @package AlmaSEO
 * @since   8.1.0
 * @updated 8.7.0
 */
(function () {
    'use strict';

    var config       = window.almaseoImport || {};
    var strings      = config.strings || {};
    var savedStatus  = config.importStatus || {};

    /* ================================================================
       Completion state helpers
       ================================================================ */

    /**
     * Render a "completed" banner inside a section, hiding the controls.
     *
     * @param {string}      stepKey   Key in savedStatus (posts, terms, settings, redirects, verify).
     * @param {HTMLElement}  section   The .almaseo-import-section wrapper.
     * @param {Array}        hideEls  Elements to hide when completed.
     */
    function renderCompleted(stepKey, section, hideEls) {
        var info = savedStatus[stepKey];
        if (!info || !info.completed) return false;

        hideEls.forEach(function (el) { if (el) el.style.display = 'none'; });

        var labels = { yoast: 'Yoast SEO', rankmath: 'Rank Math', aioseo: 'All in One SEO' };
        var sourceName = labels[info.source] || info.source || '';

        var html = '<div class="almaseo-import-completed">';
        html += '<span class="dashicons dashicons-yes-alt" style="color:#46b450;font-size:20px;"></span> ';
        html += '<strong>Completed</strong>';
        if (sourceName) html += ' &mdash; imported from ' + esc(sourceName);

        var parts = [];
        if (info.imported) parts.push(info.imported + ' fields imported');
        if (info.skipped) parts.push(info.skipped + ' skipped');
        if (info.not_found) parts.push(info.not_found + ' not found');
        if (info.empty) parts.push(info.empty + ' empty');
        if (parts.length) html += ' (' + parts.join(', ') + ')';

        if (info.date) html += ' <small style="color:#888;">on ' + esc(info.date) + '</small>';
        html += ' <button type="button" class="button button-small almaseo-import-reset-btn" data-step="' + stepKey + '" style="margin-left:12px;">Re-import</button>';
        html += '</div>';

        section.insertAdjacentHTML('beforeend', html);
        return true;
    }

    /** Bind reset buttons (delegated). */
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.almaseo-import-reset-btn');
        if (!btn) return;
        var step = btn.getAttribute('data-step');
        if (!confirm('Reset this step and re-import?')) return;

        btn.disabled = true;
        btn.textContent = 'Resetting...';

        wp.apiFetch({
            path: 'almaseo/v1/import/reset-step',
            method: 'POST',
            data: { step: step }
        }).then(function () {
            window.location.reload();
        }).catch(function () {
            btn.disabled = false;
            btn.textContent = 'Re-import';
            alert('Failed to reset step.');
        });
    });

    /* ================================================================
       STEP 1: Post Meta (existing)
       ================================================================ */

    var $sources      = document.getElementById('almaseo-import-sources');
    var $controls     = document.getElementById('almaseo-import-controls');
    var $sourceSelect = document.getElementById('almaseo-import-source');
    var $overwrite    = document.getElementById('almaseo-import-overwrite');
    var $previewWrap  = document.getElementById('almaseo-import-preview');
    var $previewBody  = document.querySelector('#almaseo-import-preview-table tbody');
    var $previewBtn   = document.getElementById('almaseo-import-preview-btn');
    var $startBtn     = document.getElementById('almaseo-import-start-btn');
    var $progress     = document.getElementById('almaseo-import-progress');
    var $bar          = document.getElementById('almaseo-import-bar');
    var $status       = document.getElementById('almaseo-import-status');
    var $stats        = document.getElementById('almaseo-import-stats');

    var detected   = {};
    var totalPosts = 0;

    /* ── Check saved state first, then detect ── */
    var postsSection = document.getElementById('almaseo-import-section-posts');
    var postsCompleted = renderCompleted('posts', postsSection, [$sources, $controls, $progress]);
    if (!postsCompleted) detect();

    function detect() {
        wp.apiFetch({ path: 'almaseo/v1/import/detect' }).then(function (data) {
            detected = data;
            renderSources(data);
        }).catch(function () {
            $sources.innerHTML = '<p class="almaseo-import-error">' + esc(strings.error || 'Error') + '</p>';
        });
    }

    function renderSources(data) {
        var available = [];
        var meta = data._meta || {};
        var html = '';
        var labels = { yoast: 'Yoast SEO', rankmath: 'Rank Math', aioseo: 'All in One SEO' };

        // Recommendation banner when active + legacy sources coexist.
        if (meta.has_recommendation && meta.message) {
            html += '<div class="almaseo-import-recommendation">';
            html += '<strong>Recommendation:</strong> ' + esc(meta.message);
            html += '</div>';
        }

        html += '<div class="almaseo-source-cards">';

        for (var key in data) {
            if (!data.hasOwnProperty(key) || key === '_meta') continue;
            var src = data[key];
            var isActive = !!src.plugin_active;
            var hasData = !!src.available;

            // Only show sources that have data or are actively installed.
            if (!hasData && !isActive) continue;

            var cls = hasData ? (isActive ? ' active' : ' legacy') : ' unavailable';
            var badge = '';
            var label = '';
            var overlapNote = '';

            if (hasData && isActive) {
                badge = ' <span class="almaseo-badge almaseo-badge-active">Active</span>';
                label = (src.record_count || 0) + ' posts available';
            } else if (hasData && !isActive) {
                badge = ' <span class="almaseo-badge almaseo-badge-legacy">Legacy data</span>';
                label = (src.record_count || 0) + ' posts with legacy data';

                // Show overlap info if available.
                if (src.overlap) {
                    var parts = [];
                    for (var otherKey in src.overlap) {
                        if (!src.overlap.hasOwnProperty(otherKey)) continue;
                        parts.push(src.overlap[otherKey] + ' overlap with ' + esc(labels[otherKey] || otherKey));
                    }
                    if (parts.length > 0) {
                        overlapNote = '<span class="almaseo-overlap-note">' + parts.join(', ') + '</span>';
                    }
                }
            } else {
                // Plugin is active but has 0 records — show it but note no data.
                label = 'Installed but no custom data found';
            }

            html += '<div class="almaseo-source-card' + cls + '">';
            html += '<h3>' + esc(labels[key] || key) + badge + '</h3>';
            html += '<span class="count">' + (src.record_count || 0) + '</span>';
            html += '<span class="label">' + label + '</span>';
            if (overlapNote) {
                html += overlapNote;
            }
            html += '</div>';

            if (hasData) {
                var suffix = isActive ? ' posts)' : ' posts, legacy)';
                available.push({ key: key, label: labels[key] || key, count: src.record_count || 0, active: isActive, suffix: suffix });
            }
        }

        html += '</div>';
        $sources.innerHTML = html;

        if (available.length === 0) {
            $sources.innerHTML += '<div class="almaseo-import-nodata">' + esc(strings.noData || 'No SEO data from other plugins detected.') + '</div>';
            return;
        }

        available.forEach(function (src) {
            var opt = document.createElement('option');
            opt.value = src.key;
            opt.textContent = src.label + ' (' + src.count + src.suffix;
            $sourceSelect.appendChild(opt);
        });

        // Auto-select if exactly one active source exists.
        var activeSources = available.filter(function (s) { return s.active; });
        if (activeSources.length === 1) {
            $sourceSelect.value = activeSources[0].key;
            $startBtn.disabled = false;
        }

        $controls.style.display = '';
    }

    $sourceSelect.addEventListener('change', function () {
        $previewWrap.style.display = 'none';
        $previewBody.innerHTML = '';
        $startBtn.disabled = !this.value;
    });

    $previewBtn.addEventListener('click', function () {
        var source = $sourceSelect.value;
        if (!source) return;

        $previewBtn.disabled = true;
        $previewBtn.textContent = strings.detecting || 'Loading...';

        wp.apiFetch({ path: 'almaseo/v1/import/preview?source=' + source + '&limit=5' }).then(function (rows) {
            $previewBody.innerHTML = '';

            rows.forEach(function (row) {
                var tr = document.createElement('tr');
                var statusClass = 'status-new';
                var statusText = 'New';

                if (row.has_existing) {
                    statusClass = $overwrite.checked ? 'status-overwrite' : 'status-skip';
                    statusText  = $overwrite.checked ? 'Overwrite' : 'Skip';
                }

                tr.innerHTML =
                    '<td>' + esc(row.post_title) + ' <small>(#' + row.post_id + ')</small></td>' +
                    '<td>' + esc(row.source_title || '\u2014') + '</td>' +
                    '<td>' + esc(row.current_title || '\u2014') + '</td>' +
                    '<td><span class="' + statusClass + '">' + statusText + '</span></td>';

                $previewBody.appendChild(tr);
            });

            $previewWrap.style.display = '';
            $previewBtn.disabled = false;
            $previewBtn.textContent = 'Preview';
        }).catch(function () {
            $previewBtn.disabled = false;
            $previewBtn.textContent = 'Preview';
            alert(strings.error || 'Error loading preview.');
        });
    });

    $startBtn.addEventListener('click', function () {
        var source = $sourceSelect.value;
        if (!source) return;
        if (!confirm(strings.confirmStart || 'Start import? This cannot be undone.')) return;

        totalPosts = (detected[source] && detected[source].record_count) ? detected[source].record_count : 0;

        $controls.style.display = 'none';
        $progress.style.display = '';
        $status.textContent = strings.importing || 'Importing...';
        $bar.style.width = '0%';
        $stats.innerHTML = '';

        runBatch('almaseo/v1/import/batch', source, 0, { processed: 0, imported: 0, skipped: 0 },
            totalPosts, $bar, $status, $stats, $progress);
    });

    /* ================================================================
       STEP 2: Taxonomy Term Meta
       ================================================================ */

    var $termSources  = document.getElementById('almaseo-term-sources');
    var $termControls = document.getElementById('almaseo-term-controls');
    var $termSelect   = document.getElementById('almaseo-term-source');
    var $termOverwrite = document.getElementById('almaseo-term-overwrite');
    var $termStartBtn = document.getElementById('almaseo-term-start-btn');
    var $termProgress = document.getElementById('almaseo-term-progress');
    var $termBar      = document.getElementById('almaseo-term-bar');
    var $termStatus   = document.getElementById('almaseo-term-status');

    var termDetected = {};

    var termsSection = document.getElementById('almaseo-import-section-terms');
    var termsCompleted = renderCompleted('terms', termsSection, [$termSources, $termControls, $termProgress]);
    if (!termsCompleted) {
        wp.apiFetch({ path: 'almaseo/v1/import/detect-terms' }).then(function (data) {
            termDetected = data;
            renderDetectedCards(data, $termSources, $termSelect, $termControls, $termStartBtn);
        }).catch(function () {
            $termSources.innerHTML = '<p class="almaseo-import-nodata">Could not detect taxonomy data.</p>';
        });
    }

    $termSelect.addEventListener('change', function () {
        $termStartBtn.disabled = !this.value;
    });

    $termStartBtn.addEventListener('click', function () {
        var source = $termSelect.value;
        if (!source) return;
        if (!confirm('Import taxonomy term meta from ' + source + '?')) return;

        var total = (termDetected[source] && termDetected[source].record_count) || 0;
        $termControls.style.display = 'none';
        $termProgress.style.display = '';

        runBatch('almaseo/v1/import/terms/batch', source, 0, { processed: 0, imported: 0, skipped: 0 },
            total, $termBar, $termStatus, null, $termProgress);
    });

    /* ================================================================
       STEP 3: Global Settings
       ================================================================ */

    var $settingsSources  = document.getElementById('almaseo-settings-sources');
    var $settingsControls = document.getElementById('almaseo-settings-controls');
    var $settingsSelect   = document.getElementById('almaseo-settings-source');
    var $settingsOverwrite = document.getElementById('almaseo-settings-overwrite');
    var $settingsStartBtn = document.getElementById('almaseo-settings-start-btn');
    var $settingsResult   = document.getElementById('almaseo-settings-result');

    var settingsSection = document.getElementById('almaseo-import-section-settings');
    var settingsCompleted = renderCompleted('settings', settingsSection, [$settingsSources, $settingsControls, $settingsResult]);
    if (!settingsCompleted) {
        wp.apiFetch({ path: 'almaseo/v1/import/detect-settings' }).then(function (data) {
            renderDetectedCards(data, $settingsSources, $settingsSelect, $settingsControls, $settingsStartBtn);
        }).catch(function () {
            $settingsSources.innerHTML = '<p class="almaseo-import-nodata">Could not detect global settings.</p>';
        });
    }

    $settingsSelect.addEventListener('change', function () {
        $settingsStartBtn.disabled = !this.value;
    });

    $settingsStartBtn.addEventListener('click', function () {
        var source = $settingsSelect.value;
        if (!source) return;
        if (!confirm('Import global settings from ' + source + '?')) return;

        $settingsStartBtn.disabled = true;
        $settingsStartBtn.textContent = 'Importing...';

        wp.apiFetch({
            path: 'almaseo/v1/import/settings',
            method: 'POST',
            data: { source: source, overwrite: $settingsOverwrite.checked }
        }).then(function (result) {
            $settingsControls.style.display = 'none';
            $settingsResult.style.display = '';
            $settingsResult.innerHTML =
                '<div class="notice notice-success" style="padding:12px;">' +
                '<strong>Settings imported.</strong> ' +
                result.imported + ' settings imported, ' + result.skipped + ' skipped.' +
                '</div>';
        }).catch(function (err) {
            $settingsStartBtn.disabled = false;
            $settingsStartBtn.textContent = 'Import Settings';
            alert('Error: ' + (err.message || 'Unknown error'));
        });
    });

    /* ================================================================
       STEP 4: Redirects
       ================================================================ */

    var $redirectsSources  = document.getElementById('almaseo-redirects-sources');
    var $redirectsControls = document.getElementById('almaseo-redirects-controls');
    var $redirectsSelect   = document.getElementById('almaseo-redirects-source');
    var $redirectsOverwrite = document.getElementById('almaseo-redirects-overwrite');
    var $redirectsStartBtn = document.getElementById('almaseo-redirects-start-btn');
    var $redirectsProgress = document.getElementById('almaseo-redirects-progress');
    var $redirectsBar      = document.getElementById('almaseo-redirects-bar');
    var $redirectsStatus   = document.getElementById('almaseo-redirects-status');

    var redirectsDetected = {};

    var redirectsSection = document.getElementById('almaseo-import-section-redirects');
    var redirectsCompleted = renderCompleted('redirects', redirectsSection, [$redirectsSources, $redirectsControls, $redirectsProgress]);
    if (!redirectsCompleted) {
        wp.apiFetch({ path: 'almaseo/v1/import/detect-redirects' }).then(function (data) {
            redirectsDetected = data;
            renderDetectedCards(data, $redirectsSources, $redirectsSelect, $redirectsControls, $redirectsStartBtn);
        }).catch(function () {
            $redirectsSources.innerHTML = '<p class="almaseo-import-nodata">Could not detect redirect data.</p>';
        });
    }

    $redirectsSelect.addEventListener('change', function () {
        $redirectsStartBtn.disabled = !this.value;
    });

    $redirectsStartBtn.addEventListener('click', function () {
        var source = $redirectsSelect.value;
        if (!source) return;
        if (!confirm('Import redirects from ' + source + '?')) return;

        var total = (redirectsDetected[source] && redirectsDetected[source].record_count) || 0;
        $redirectsControls.style.display = 'none';
        $redirectsProgress.style.display = '';

        runBatch('almaseo/v1/import/redirects/batch', source, 0, { processed: 0, imported: 0, skipped: 0 },
            total, $redirectsBar, $redirectsStatus, null, $redirectsProgress);
    });

    /* ================================================================
       STEP 5: Verification
       ================================================================ */

    var $verifyBtn     = document.getElementById('almaseo-verify-btn');
    var $verifyLoading = document.getElementById('almaseo-verify-loading');
    var $verifyReport  = document.getElementById('almaseo-verify-report');

    var verifySection = document.getElementById('almaseo-import-section-verify');
    var verifyCompleted = renderCompleted('verify', verifySection, [$verifyBtn.parentNode]);
    if (verifyCompleted) {
        // Still allow re-running verify — the reset button handles it.
    }

    $verifyBtn.addEventListener('click', function () {
        $verifyBtn.style.display = 'none';
        $verifyLoading.style.display = '';

        wp.apiFetch({ path: 'almaseo/v1/import/verify' }).then(function (report) {
            $verifyLoading.style.display = 'none';
            $verifyReport.style.display = '';
            renderVerifyReport(report);
        }).catch(function (err) {
            $verifyLoading.style.display = 'none';
            $verifyBtn.style.display = '';
            alert('Verification failed: ' + (err.message || 'Unknown error'));
        });
    });

    function renderVerifyReport(report) {
        var html = '';

        // Summary cards
        html += '<div class="almaseo-verify-summary">';
        html += summaryCard('Posts Scanned', report.total_scanned);
        html += summaryCard('With SEO Title', report.posts_with_title);
        html += summaryCard('With Description', report.posts_with_desc);
        html += summaryCard('With Focus Keyword', report.posts_with_keyword);
        html += '</div>';

        var issues = report.issues || {};
        var totalIssues = issues.total || 0;

        if (totalIssues === 0) {
            html += '<div class="notice notice-success" style="padding:12px;margin-top:16px;">' +
                '<strong>No issues found.</strong> Your migrated data looks clean.' +
                '</div>';
        } else {
            html += '<div class="notice notice-warning" style="padding:12px;margin-top:16px;">' +
                '<strong>' + totalIssues + ' potential issue(s) found.</strong> Review below.' +
                '</div>';
        }

        // Unresolved template variables
        if (report.unresolved_templates && report.unresolved_templates.length > 0) {
            html += '<h3>Unresolved Template Variables (' + report.unresolved_templates.length + ')</h3>';
            html += '<p class="description">These posts contain template variables from the old plugin that were not converted. Edit each post to replace them with actual text.</p>';
            html += '<table class="widefat striped"><thead><tr><th>Post</th><th>Field</th><th>Value</th><th>Tags Found</th></tr></thead><tbody>';
            report.unresolved_templates.forEach(function (item) {
                var tags = item.tags.map(function (t) { return t.tag; }).join(', ');
                html += '<tr>';
                html += '<td><a href="post.php?post=' + item.post_id + '&action=edit" target="_blank">' + esc(item.post_title) + '</a></td>';
                html += '<td>' + esc(item.field) + '</td>';
                html += '<td><code>' + esc(item.value) + '</code></td>';
                html += '<td><code>' + esc(tags) + '</code></td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
        }

        // Missing descriptions
        if (report.empty_descriptions && report.empty_descriptions.length > 0) {
            html += '<h3>Missing Meta Descriptions (' + report.empty_descriptions.length + ')</h3>';
            html += '<p class="description">These posts have an SEO title but no meta description. Consider adding one.</p>';
            html += '<table class="widefat striped"><thead><tr><th>Post</th><th>Action</th></tr></thead><tbody>';
            report.empty_descriptions.slice(0, 50).forEach(function (item) {
                html += '<tr>';
                html += '<td><a href="post.php?post=' + item.post_id + '&action=edit" target="_blank">' + esc(item.post_title) + '</a></td>';
                html += '<td><a href="post.php?post=' + item.post_id + '&action=edit" target="_blank">Edit</a></td>';
                html += '</tr>';
            });
            if (report.empty_descriptions.length > 50) {
                html += '<tr><td colspan="2"><em>...and ' + (report.empty_descriptions.length - 50) + ' more</em></td></tr>';
            }
            html += '</tbody></table>';
        }

        // Duplicate titles
        if (report.duplicate_titles && report.duplicate_titles.length > 0) {
            html += '<h3>Duplicate SEO Titles (' + report.duplicate_titles.length + ' groups)</h3>';
            html += '<p class="description">These posts share identical SEO titles, which can hurt search rankings.</p>';
            html += '<table class="widefat striped"><thead><tr><th>Title</th><th>Posts</th></tr></thead><tbody>';
            report.duplicate_titles.forEach(function (group) {
                html += '<tr>';
                html += '<td><code>' + esc(group.title) + '</code></td>';
                html += '<td>' + group.post_ids.map(function (id) {
                    return '<a href="post.php?post=' + id + '&action=edit" target="_blank">#' + id + '</a>';
                }).join(', ') + '</td>';
                html += '</tr>';
            });
            html += '</tbody></table>';
        }

        $verifyReport.innerHTML = html;
    }

    function summaryCard(label, value) {
        return '<div class="almaseo-verify-card">' +
            '<span class="number">' + (value || 0) + '</span>' +
            '<span class="label">' + esc(label) + '</span>' +
            '</div>';
    }

    /* ================================================================
       Shared helpers
       ================================================================ */

    /**
     * Generic batch runner used by post meta, term meta, and redirects.
     */
    function runBatch(endpoint, source, offset, totals, totalCount, $bar, $status, $stats, $progressWrap) {
        var overwriteCheckbox = null;
        if (endpoint.indexOf('terms') > -1)      overwriteCheckbox = $termOverwrite;
        else if (endpoint.indexOf('redirects') > -1) overwriteCheckbox = $redirectsOverwrite;
        else                                         overwriteCheckbox = $overwrite;

        wp.apiFetch({
            path: endpoint,
            method: 'POST',
            data: {
                source:    source,
                offset:    offset,
                overwrite: overwriteCheckbox ? overwriteCheckbox.checked : false
            }
        }).then(function (result) {
            totals.processed  += result.processed || 0;
            totals.imported   += result.imported  || 0;
            totals.skipped    += result.skipped   || 0;
            totals.not_found  = (totals.not_found || 0) + (result.not_found || 0);
            totals.empty      = (totals.empty || 0) + (result.empty || 0);

            var pct = totalCount > 0 ? Math.min(Math.round((totals.processed / totalCount) * 100), 100) : 50;
            $bar.style.width = pct + '%';
            $status.textContent = 'Importing... ' + pct + '% (' + totals.processed + ' posts, ' + totals.imported + ' fields imported)';

            if ($stats) renderStats(totals);

            if (result.done) {
                $bar.style.width = '100%';
                var doneMsg = 'Done! ' + totals.processed + ' posts processed — ' + totals.imported + ' fields imported, ' + totals.skipped + ' skipped.';
                if (totals.not_found > 0) {
                    doneMsg += ' ' + totals.not_found + ' post(s) skipped — no longer exists.';
                }
                if (totals.empty > 0) {
                    doneMsg += ' ' + totals.empty + ' record(s) had no usable SEO data.';
                }
                $status.innerHTML = '<span class="almaseo-import-done">' + doneMsg + '</span>';
                return;
            }

            runBatch(endpoint, source, result.offset, totals, totalCount, $bar, $status, $stats, $progressWrap);
        }).catch(function (err) {
            $status.innerHTML = '<span class="almaseo-import-error">Error: ' + esc(err.message || 'Unknown') + '</span>';
        });
    }

    /**
     * Render detection cards for any step (terms, settings, redirects).
     */
    function renderDetectedCards(data, $container, $select, $controlsDiv, $startBtn) {
        var available = [];
        var html = '<div class="almaseo-source-cards">';

        for (var key in data) {
            if (!data.hasOwnProperty(key)) continue;
            var src = data[key];
            var isActive = !!src.plugin_active;
            var hasData = !!src.available;

            // Only show sources that have data or are actively installed.
            if (!hasData && !isActive) continue;

            var cls = hasData ? (isActive ? ' active' : ' legacy') : ' unavailable';
            var badge = '';
            var label = '';
            var countVal = src.record_count || 0;

            var hasCount = typeof src.record_count === 'number' && src.record_count > 0;

            if (hasData && isActive) {
                badge = ' <span class="almaseo-badge almaseo-badge-active">Active</span>';
                label = hasCount ? countVal + ' records available' : 'Settings available';
            } else if (hasData && !isActive) {
                badge = ' <span class="almaseo-badge almaseo-badge-legacy">Legacy data</span>';
                label = hasCount ? countVal + ' records with legacy data' : 'Legacy settings found';
            } else {
                // Plugin is active but has 0 records.
                label = 'Installed but no custom data found';
            }

            html += '<div class="almaseo-source-card' + cls + '">';
            html += '<h3>' + esc(src.name || key) + badge + '</h3>';
            if (hasCount) {
                html += '<span class="count">' + countVal + '</span>';
            }
            html += '<span class="label">' + label + '</span>';
            html += '</div>';

            if (hasData) {
                var suffix = hasCount ? (isActive ? ' records)' : ' records, legacy)') : (isActive ? ')' : ', legacy)');
                var displayCount = hasCount ? countVal + ' ' : '';
                available.push({ key: key, name: src.name || key, count: displayCount, active: isActive, suffix: suffix });
            }
        }

        html += '</div>';
        $container.innerHTML = html;

        if (available.length === 0) {
            $container.innerHTML += '<div class="almaseo-import-nodata">No data detected for this step. This means none of the supported SEO plugins (Yoast SEO, Rank Math, All in One SEO) have stored data of this type on your site, or the plugin has already been removed.</div>';
            return;
        }

        available.forEach(function (src) {
            var opt = document.createElement('option');
            opt.value = src.key;
            opt.textContent = src.name + ' (' + src.count + src.suffix;
            $select.appendChild(opt);
        });

        // Auto-select if exactly one active source exists.
        var activeSources = available.filter(function (s) { return s.active; });
        if (activeSources.length === 1) {
            $select.value = activeSources[0].key;
            $startBtn.disabled = false;
        }

        $controlsDiv.style.display = '';
    }

    function renderStats(totals) {
        if (!$stats) return;
        $stats.innerHTML =
            '<div class="almaseo-import-stat processed">' +
                '<span class="number">' + totals.processed + '</span>' +
                '<span class="label">' + esc(strings.processed || 'Posts Processed') + '</span>' +
            '</div>' +
            '<div class="almaseo-import-stat imported">' +
                '<span class="number">' + totals.imported + '</span>' +
                '<span class="label">' + esc(strings.imported || 'Fields Imported') + '</span>' +
            '</div>' +
            '<div class="almaseo-import-stat skipped">' +
                '<span class="number">' + totals.skipped + '</span>' +
                '<span class="label">' + esc(strings.skipped || 'Fields Skipped') + '</span>' +
            '</div>';
    }

    function esc(str) {
        if (!str) return '';
        var el = document.createElement('span');
        el.textContent = str;
        return el.innerHTML;
    }

})();
