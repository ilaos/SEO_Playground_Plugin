/**
 * AlmaSEO Import / Migration Admin JS
 *
 * @package AlmaSEO
 * @since   8.1.0
 */
(function () {
    'use strict';

    var config  = window.almaseoImport || {};
    var strings = config.strings || {};

    /* ── DOM refs ── */
    var $sources     = document.getElementById('almaseo-import-sources');
    var $controls    = document.getElementById('almaseo-import-controls');
    var $sourceSelect = document.getElementById('almaseo-import-source');
    var $overwrite   = document.getElementById('almaseo-import-overwrite');
    var $previewWrap = document.getElementById('almaseo-import-preview');
    var $previewBody = document.querySelector('#almaseo-import-preview-table tbody');
    var $previewBtn  = document.getElementById('almaseo-import-preview-btn');
    var $startBtn    = document.getElementById('almaseo-import-start-btn');
    var $progress    = document.getElementById('almaseo-import-progress');
    var $bar         = document.getElementById('almaseo-import-bar');
    var $status      = document.getElementById('almaseo-import-status');
    var $stats       = document.getElementById('almaseo-import-stats');

    var detected = {};
    var totalPosts = 0;

    /* ── Init: detect sources ── */
    detect();

    function detect() {
        wp.apiFetch({ path: 'almaseo/v1/import/detect' }).then(function (data) {
            detected = data;
            renderSources(data);
        }).catch(function () {
            $sources.innerHTML = '<p class="almaseo-import-error">' + esc(strings.error) + '</p>';
        });
    }

    function renderSources(data) {
        var available = [];
        var html = '<h2>Detected SEO Data</h2><div class="almaseo-source-cards">';

        var labels = { yoast: 'Yoast SEO', rankmath: 'Rank Math', aioseo: 'All in One SEO' };

        for (var key in data) {
            if (!data.hasOwnProperty(key)) continue;
            var src = data[key];
            var cls = src.available ? '' : ' unavailable';
            html += '<div class="almaseo-source-card' + cls + '">';
            html += '<h3>' + esc(labels[key] || key) + '</h3>';
            html += '<span class="count">' + (src.count || 0) + '</span>';
            html += '<span class="label">' + (src.available ? 'posts with data' : 'not detected') + '</span>';
            html += '</div>';

            if (src.available) {
                available.push({ key: key, label: labels[key] || key, count: src.count });
            }
        }

        html += '</div>';
        $sources.innerHTML = html;

        if (available.length === 0) {
            $sources.innerHTML += '<div class="almaseo-import-nodata">' + esc(strings.noData) + '</div>';
            return;
        }

        // Populate source select.
        available.forEach(function (src) {
            var opt = document.createElement('option');
            opt.value = src.key;
            opt.textContent = src.label + ' (' + src.count + ' posts)';
            $sourceSelect.appendChild(opt);
        });

        $controls.style.display = '';
    }

    /* ── Source change ── */
    $sourceSelect.addEventListener('change', function () {
        $previewWrap.style.display = 'none';
        $previewBody.innerHTML = '';
        $startBtn.disabled = !this.value;
    });

    /* ── Preview ── */
    $previewBtn.addEventListener('click', function () {
        var source = $sourceSelect.value;
        if (!source) return;

        $previewBtn.disabled = true;
        $previewBtn.textContent = strings.detecting;

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
                    '<td>' + esc(row.source_title || '—') + '</td>' +
                    '<td>' + esc(row.current_title || '—') + '</td>' +
                    '<td><span class="' + statusClass + '">' + statusText + '</span></td>';

                $previewBody.appendChild(tr);
            });

            $previewWrap.style.display = '';
            $previewBtn.disabled = false;
            $previewBtn.textContent = 'Preview';
        }).catch(function () {
            $previewBtn.disabled = false;
            $previewBtn.textContent = 'Preview';
            alert(strings.error);
        });
    });

    /* ── Start import ── */
    $startBtn.addEventListener('click', function () {
        var source = $sourceSelect.value;
        if (!source) return;

        if (!confirm(strings.confirmStart)) return;

        // Get total count for progress.
        totalPosts = (detected[source] && detected[source].count) ? detected[source].count : 0;

        // Hide controls, show progress.
        $controls.style.display = 'none';
        $progress.style.display = '';
        $status.textContent = strings.importing;
        $bar.style.width = '0%';
        $stats.innerHTML = '';

        runBatch(source, 0, { processed: 0, imported: 0, skipped: 0 });
    });

    /* ── Batch loop ── */
    function runBatch(source, offset, totals) {
        wp.apiFetch({
            path: 'almaseo/v1/import/batch',
            method: 'POST',
            data: {
                source:    source,
                offset:    offset,
                overwrite: $overwrite.checked
            }
        }).then(function (result) {
            totals.processed += result.processed || 0;
            totals.imported  += result.imported  || 0;
            totals.skipped   += result.skipped   || 0;

            // Update progress.
            var pct = totalPosts > 0 ? Math.min(Math.round((totals.processed / totalPosts) * 100), 100) : 0;
            $bar.style.width = pct + '%';
            $status.textContent = strings.importing + ' ' + pct + '%';

            renderStats(totals);

            if (result.done) {
                $bar.style.width = '100%';
                $status.innerHTML = '<span class="almaseo-import-done">' + esc(strings.done) + '</span>';
                return;
            }

            // Next batch.
            runBatch(source, result.offset, totals);
        }).catch(function (err) {
            $status.innerHTML = '<span class="almaseo-import-error">' + esc(strings.error) + ' ' + esc(err.message || '') + '</span>';
        });
    }

    function renderStats(totals) {
        $stats.innerHTML =
            '<div class="almaseo-import-stat processed">' +
                '<span class="number">' + totals.processed + '</span>' +
                '<span class="label">' + esc(strings.processed) + '</span>' +
            '</div>' +
            '<div class="almaseo-import-stat imported">' +
                '<span class="number">' + totals.imported + '</span>' +
                '<span class="label">' + esc(strings.imported) + '</span>' +
            '</div>' +
            '<div class="almaseo-import-stat skipped">' +
                '<span class="number">' + totals.skipped + '</span>' +
                '<span class="label">' + esc(strings.skipped) + '</span>' +
            '</div>';
    }

    /* ── Helpers ── */
    function esc(str) {
        if (!str) return '';
        var el = document.createElement('span');
        el.textContent = str;
        return el.innerHTML;
    }

})();
