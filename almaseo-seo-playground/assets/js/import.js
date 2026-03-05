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

    var config  = window.almaseoImport || {};
    var strings = config.strings || {};

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

    /* ── Detect post meta ── */
    detect();

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
        var html = '<div class="almaseo-source-cards">';
        var labels = { yoast: 'Yoast SEO', rankmath: 'Rank Math', aioseo: 'All in One SEO' };

        for (var key in data) {
            if (!data.hasOwnProperty(key)) continue;
            var src = data[key];
            var cls = src.available ? '' : ' unavailable';
            var activeLabel = src.plugin_active ? ' <span class="almaseo-badge-active">Active</span>' : '';
            html += '<div class="almaseo-source-card' + cls + '">';
            html += '<h3>' + esc(labels[key] || key) + activeLabel + '</h3>';
            html += '<span class="count">' + (src.record_count || 0) + '</span>';
            html += '<span class="label">' + (src.available ? 'posts with data' : 'not detected') + '</span>';
            html += '</div>';

            if (src.available) {
                available.push({ key: key, label: labels[key] || key, count: src.record_count || 0 });
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
            opt.textContent = src.label + ' (' + src.count + ' posts)';
            $sourceSelect.appendChild(opt);
        });

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

    wp.apiFetch({ path: 'almaseo/v1/import/detect-terms' }).then(function (data) {
        termDetected = data;
        renderDetectedCards(data, $termSources, $termSelect, $termControls, $termStartBtn);
    }).catch(function () {
        $termSources.innerHTML = '<p class="almaseo-import-nodata">Could not detect taxonomy data.</p>';
    });

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

    wp.apiFetch({ path: 'almaseo/v1/import/detect-settings' }).then(function (data) {
        renderDetectedCards(data, $settingsSources, $settingsSelect, $settingsControls, $settingsStartBtn);
    }).catch(function () {
        $settingsSources.innerHTML = '<p class="almaseo-import-nodata">Could not detect global settings.</p>';
    });

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

    wp.apiFetch({ path: 'almaseo/v1/import/detect-redirects' }).then(function (data) {
        redirectsDetected = data;
        renderDetectedCards(data, $redirectsSources, $redirectsSelect, $redirectsControls, $redirectsStartBtn);
    }).catch(function () {
        $redirectsSources.innerHTML = '<p class="almaseo-import-nodata">Could not detect redirect data.</p>';
    });

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
            totals.processed += result.processed || 0;
            totals.imported  += result.imported  || 0;
            totals.skipped   += result.skipped   || 0;

            var pct = totalCount > 0 ? Math.min(Math.round((totals.processed / totalCount) * 100), 100) : 50;
            $bar.style.width = pct + '%';
            $status.textContent = 'Importing... ' + pct + '% (' + totals.imported + ' imported, ' + totals.skipped + ' skipped)';

            if ($stats) renderStats(totals);

            if (result.done) {
                $bar.style.width = '100%';
                $status.innerHTML = '<span class="almaseo-import-done">Done! ' +
                    totals.imported + ' imported, ' + totals.skipped + ' skipped.</span>';
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
            var cls = src.available ? '' : ' unavailable';
            html += '<div class="almaseo-source-card' + cls + '">';
            html += '<h3>' + esc(src.name || key) + '</h3>';
            html += '<span class="count">' + (src.record_count || 0) + '</span>';
            html += '<span class="label">' + (src.available ? 'records found' : 'not detected') + '</span>';
            html += '</div>';

            if (src.available) {
                available.push({ key: key, name: src.name || key, count: src.record_count || 0 });
            }
        }

        html += '</div>';
        $container.innerHTML = html;

        if (available.length === 0) {
            $container.innerHTML += '<div class="almaseo-import-nodata">No data detected for this step.</div>';
            return;
        }

        available.forEach(function (src) {
            var opt = document.createElement('option');
            opt.value = src.key;
            opt.textContent = src.name + ' (' + src.count + ' records)';
            $select.appendChild(opt);
        });

        $controlsDiv.style.display = '';
    }

    function renderStats(totals) {
        if (!$stats) return;
        $stats.innerHTML =
            '<div class="almaseo-import-stat processed">' +
                '<span class="number">' + totals.processed + '</span>' +
                '<span class="label">' + esc(strings.processed || 'Processed') + '</span>' +
            '</div>' +
            '<div class="almaseo-import-stat imported">' +
                '<span class="number">' + totals.imported + '</span>' +
                '<span class="label">' + esc(strings.imported || 'Imported') + '</span>' +
            '</div>' +
            '<div class="almaseo-import-stat skipped">' +
                '<span class="number">' + totals.skipped + '</span>' +
                '<span class="label">' + esc(strings.skipped || 'Skipped') + '</span>' +
            '</div>';
    }

    function esc(str) {
        if (!str) return '';
        var el = document.createElement('span');
        el.textContent = str;
        return el.innerHTML;
    }

})();
