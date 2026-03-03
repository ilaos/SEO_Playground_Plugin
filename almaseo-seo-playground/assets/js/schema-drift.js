/**
 * Schema Drift Monitor – Admin JavaScript
 *
 * Handles: loading stats/findings, baseline capture, drift scan,
 * filtering, resolve/dismiss/reopen actions, pagination, and settings.
 *
 * @package AlmaSEO
 * @since   7.8.0
 */

/* global almaseoSD, wp */
(function () {
    'use strict';

    if (typeof almaseoSD === 'undefined') return;

    var cfg = almaseoSD;

    /* ─── DOM refs ─── */
    var tbody          = document.getElementById('almaseo-sd-tbody');
    var pagination     = document.getElementById('almaseo-sd-pagination');
    var statusFilter   = document.getElementById('almaseo-sd-status-filter');
    var severityFilter = document.getElementById('almaseo-sd-severity-filter');
    var typeFilter     = document.getElementById('almaseo-sd-type-filter');
    var searchInput    = document.getElementById('almaseo-sd-search');
    var captureBtn     = document.getElementById('almaseo-sd-capture');
    var scanBtn        = document.getElementById('almaseo-sd-scan');
    var table          = document.getElementById('almaseo-sd-table');
    var lastScanEl     = document.getElementById('almaseo-sd-last-scan');

    /* Stats */
    var statBaselinePosts   = document.getElementById('almaseo-sd-baseline-posts');
    var statBaselineSchemas = document.getElementById('almaseo-sd-baseline-schemas');
    var statOpen            = document.getElementById('almaseo-sd-open');
    var statHigh            = document.getElementById('almaseo-sd-high');
    var statResolved        = document.getElementById('almaseo-sd-resolved');

    /* Settings */
    var autoScanCb     = document.getElementById('almaseo-sd-auto-scan');
    var postTypesInput = document.getElementById('almaseo-sd-post-types');
    var sampleSizeInput = document.getElementById('almaseo-sd-sample-size');
    var saveSettingsBtn = document.getElementById('almaseo-sd-save-settings');
    var settingsStatus  = document.getElementById('almaseo-sd-settings-status');

    var currentPage = 1;
    var searchTimer = null;

    if (!tbody) return;

    /* ============================================================
     *  DRIFT TYPE LABELS
     * ============================================================ */
    var DRIFT_LABELS = {
        schema_removed:  'Removed',
        schema_modified: 'Modified',
        schema_added:    'Added',
        schema_error:    'Error'
    };

    /* ============================================================
     *  STATS
     * ============================================================ */
    function loadStats() {
        wp.apiFetch({ url: cfg.restBase + '/stats' }).then(function (data) {
            if (statBaselinePosts)   statBaselinePosts.textContent   = data.baseline_posts;
            if (statBaselineSchemas) statBaselineSchemas.textContent = data.baseline_schemas;
            if (statOpen)            statOpen.textContent            = data.open;
            if (statHigh)            statHigh.textContent            = data.high;
            if (statResolved)        statResolved.textContent        = data.resolved;

            if (lastScanEl && data.last_scan) {
                lastScanEl.textContent = 'Last scan: ' + formatDate(data.last_scan);
            }
        });
    }

    /* ============================================================
     *  FINDINGS TABLE
     * ============================================================ */
    function loadFindings(page) {
        currentPage = page || 1;
        var status   = statusFilter ? statusFilter.value : '';
        var severity = severityFilter ? severityFilter.value : '';
        var driftType = typeFilter ? typeFilter.value : '';
        var search   = searchInput ? searchInput.value.trim() : '';

        var url = cfg.restBase + '?page=' + currentPage + '&per_page=20';
        if (status)    url += '&status=' + encodeURIComponent(status);
        if (severity)  url += '&severity=' + encodeURIComponent(severity);
        if (driftType) url += '&drift_type=' + encodeURIComponent(driftType);
        if (search)    url += '&search=' + encodeURIComponent(search);

        tbody.innerHTML = '<tr><td colspan="8">Loading&hellip;</td></tr>';

        wp.apiFetch({ url: url, parse: false }).then(function (res) {
            var totalPages = parseInt(res.headers.get('X-WP-TotalPages'), 10) || 1;
            return res.json().then(function (items) {
                renderTable(items, totalPages);
            });
        }).catch(function () {
            tbody.innerHTML = '<tr><td colspan="8">Error loading data.</td></tr>';
        });
    }

    function renderTable(items, totalPages) {
        if (!items.length) {
            tbody.innerHTML = '';
            if (table) table.style.display = 'none';

            var wrap = tbody.closest('.almaseo-sd-wrap');
            var prev = wrap.querySelector('.almaseo-sd-empty');
            if (prev) prev.remove();

            var empty = document.createElement('div');
            empty.className = 'almaseo-sd-empty';
            empty.innerHTML =
                '<div class="almaseo-sd-empty-icon"><span class="dashicons dashicons-editor-code"></span></div>' +
                '<h3>No findings</h3>' +
                '<p>' + esc(cfg.noFindings) + '</p>' +
                '<button class="button button-secondary" id="almaseo-sd-empty-capture">' +
                    '<span class="dashicons dashicons-database" style="vertical-align:middle;margin-right:4px;font-size:16px;width:16px;height:16px;"></span>' +
                    'Capture Baseline' +
                '</button> ' +
                '<button class="button button-primary" id="almaseo-sd-empty-scan">' +
                    '<span class="dashicons dashicons-search" style="vertical-align:middle;margin-right:4px;font-size:16px;width:16px;height:16px;"></span>' +
                    'Scan for Drift' +
                '</button>';
            wrap.appendChild(empty);

            var emptyCapture = document.getElementById('almaseo-sd-empty-capture');
            var emptyScan    = document.getElementById('almaseo-sd-empty-scan');
            if (emptyCapture) emptyCapture.addEventListener('click', function () { handleCapture(); });
            if (emptyScan)    emptyScan.addEventListener('click', function () { handleScan(); });
            if (pagination) pagination.innerHTML = '';
            return;
        }

        if (table) table.style.display = '';
        var existing = document.querySelector('.almaseo-sd-empty');
        if (existing) existing.remove();

        var html = '';
        items.forEach(function (item) {
            html += '<tr>';

            // Page.
            html += '<td>';
            if (item.post_edit_link) {
                html += '<a href="' + esc(item.post_edit_link) + '">' + esc(item.post_title) + '</a>';
            } else {
                html += esc(item.post_title || '(Unknown)');
            }
            html += '</td>';

            // Drift type.
            html += '<td><span class="almaseo-sd-drift-type almaseo-sd-drift-type-' + esc(item.drift_type) + '">' +
                    esc(DRIFT_LABELS[item.drift_type] || item.drift_type) + '</span></td>';

            // Schema type.
            html += '<td>';
            if (item.schema_type) {
                html += '<span class="almaseo-sd-schema-type">' + esc(item.schema_type) + '</span>';
            }
            html += '</td>';

            // Severity.
            html += '<td><span class="almaseo-sd-severity almaseo-sd-severity-' + esc(item.severity) + '">' +
                    capitalize(item.severity) + '</span></td>';

            // Summary.
            html += '<td class="almaseo-sd-col-summary">';
            if (item.diff_summary) {
                html += '<span class="almaseo-sd-summary">' + esc(item.diff_summary) + '</span>';
            }
            html += '</td>';

            // Status.
            html += '<td><span class="almaseo-sd-status almaseo-sd-status-' + esc(item.status) + '">' +
                    capitalize(item.status) + '</span></td>';

            // Detected.
            html += '<td>' + formatDate(item.detected_at) + '</td>';

            // Actions.
            html += '<td>';
            if (item.status === 'open') {
                html += '<button class="button button-small almaseo-sd-resolve" data-id="' + item.id + '">Resolve</button> ';
                html += '<button class="button button-small almaseo-sd-dismiss" data-id="' + item.id + '">Dismiss</button> ';
            } else {
                html += '<button class="button button-small almaseo-sd-reopen" data-id="' + item.id + '">Reopen</button> ';
            }
            if (item.post_edit_link) {
                html += '<a class="button button-small" href="' + esc(item.post_edit_link) + '">Edit</a>';
            }
            html += '</td>';

            html += '</tr>';
        });

        tbody.innerHTML = html;

        // Pagination.
        if (pagination) {
            var ph = '';
            for (var p = 1; p <= totalPages; p++) {
                var cls = p === currentPage ? 'button current' : 'button';
                ph += '<button class="' + cls + '" data-page="' + p + '">' + p + '</button>';
            }
            pagination.innerHTML = ph;
        }
    }

    /* ============================================================
     *  CAPTURE BASELINE
     * ============================================================ */
    function handleCapture() {
        if (captureBtn) {
            captureBtn.disabled = true;
            captureBtn.innerHTML = '<span class="dashicons dashicons-update" style="animation:rotation 1s linear infinite"></span> Capturing\u2026';
        }

        wp.apiFetch({
            url: cfg.restBase + '/baseline',
            method: 'POST',
        }).then(function (result) {
            if (captureBtn) {
                captureBtn.disabled = false;
                captureBtn.innerHTML = '<span class="dashicons dashicons-database"></span> Capture Baseline';
            }

            alert('Baseline captured: ' + result.schemas_captured + ' schema(s) from ' + result.posts_scanned + ' post(s).');
            loadStats();
        }).catch(function () {
            if (captureBtn) {
                captureBtn.disabled = false;
                captureBtn.innerHTML = '<span class="dashicons dashicons-database"></span> Capture Baseline';
            }
            alert('Baseline capture failed. Please try again.');
        });
    }

    /* ============================================================
     *  SCAN FOR DRIFT
     * ============================================================ */
    function handleScan() {
        if (scanBtn) {
            scanBtn.disabled = true;
            scanBtn.innerHTML = '<span class="dashicons dashicons-update" style="animation:rotation 1s linear infinite"></span> Scanning\u2026';
        }

        wp.apiFetch({
            url: cfg.restBase + '/scan',
            method: 'POST',
        }).then(function () {
            if (scanBtn) {
                scanBtn.disabled = false;
                scanBtn.innerHTML = '<span class="dashicons dashicons-search"></span> Scan for Drift';
            }

            var empty = document.querySelector('.almaseo-sd-empty');
            if (empty) empty.remove();
            if (table) table.style.display = '';

            loadStats();
            loadFindings(1);
        }).catch(function (err) {
            if (scanBtn) {
                scanBtn.disabled = false;
                scanBtn.innerHTML = '<span class="dashicons dashicons-search"></span> Scan for Drift';
            }
            var msg = (err && err.message) ? err.message : 'Scan failed. Please try again.';
            alert(msg);
        });
    }

    /* ============================================================
     *  RESOLVE / DISMISS / REOPEN
     * ============================================================ */
    function handleAction(id, action) {
        wp.apiFetch({
            url: cfg.restBase + '/' + id + '/' + action,
            method: 'PATCH',
        }).then(function () {
            loadStats();
            loadFindings(currentPage);
        });
    }

    /* ============================================================
     *  SETTINGS
     * ============================================================ */
    function loadSettings() {
        wp.apiFetch({ url: cfg.restBase + '/settings' }).then(function (data) {
            if (autoScanCb)      autoScanCb.checked = !!data.auto_scan_on_update;
            if (postTypesInput)  postTypesInput.value = (data.monitored_post_types || []).join(', ');
            if (sampleSizeInput) sampleSizeInput.value = data.scan_sample_size || 3;
        });
    }

    function saveSettings() {
        var types = postTypesInput ? postTypesInput.value.split(',').map(function (s) { return s.trim(); }).filter(Boolean) : ['post', 'page'];

        wp.apiFetch({
            url: cfg.restBase + '/settings',
            method: 'POST',
            data: {
                auto_scan_on_update:  autoScanCb ? autoScanCb.checked : true,
                monitored_post_types: types,
                scan_sample_size:     sampleSizeInput ? parseInt(sampleSizeInput.value, 10) || 3 : 3,
            },
        }).then(function () {
            if (settingsStatus) {
                settingsStatus.textContent = 'Saved!';
                setTimeout(function () { settingsStatus.textContent = ''; }, 2000);
            }
        });
    }

    /* ============================================================
     *  EVENT HANDLERS
     * ============================================================ */
    if (statusFilter)   statusFilter.addEventListener('change', function () { loadFindings(1); });
    if (severityFilter) severityFilter.addEventListener('change', function () { loadFindings(1); });
    if (typeFilter)     typeFilter.addEventListener('change', function () { loadFindings(1); });
    if (captureBtn)     captureBtn.addEventListener('click', handleCapture);
    if (scanBtn)        scanBtn.addEventListener('click', handleScan);
    if (saveSettingsBtn) saveSettingsBtn.addEventListener('click', saveSettings);

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { loadFindings(1); }, 400);
        });
    }

    if (pagination) {
        pagination.addEventListener('click', function (e) {
            if (e.target.dataset.page) loadFindings(parseInt(e.target.dataset.page, 10));
        });
    }

    if (tbody) {
        tbody.addEventListener('click', function (e) {
            var resolveBtn = e.target.closest('.almaseo-sd-resolve');
            var dismissBtn = e.target.closest('.almaseo-sd-dismiss');
            var reopenBtn  = e.target.closest('.almaseo-sd-reopen');

            if (resolveBtn) { handleAction(resolveBtn.dataset.id, 'resolve'); }
            if (dismissBtn) { handleAction(dismissBtn.dataset.id, 'dismiss'); }
            if (reopenBtn)  { handleAction(reopenBtn.dataset.id, 'reopen'); }
        });
    }

    /* ============================================================
     *  INIT
     * ============================================================ */
    loadStats();
    loadFindings(1);
    loadSettings();

    /* ─── Utilities ─── */
    function esc(str) {
        if (!str) return '';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    }

    function capitalize(str) {
        if (!str) return '';
        return str.charAt(0).toUpperCase() + str.slice(1);
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        var d = new Date(dateStr + 'Z');
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    }
})();
