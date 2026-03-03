/**
 * E-E-A-T Enforcement – Admin JavaScript
 *
 * Handles: loading findings table, summary stats, scanning,
 * resolve/dismiss/reopen actions, pagination, filtering, and settings.
 *
 * @package AlmaSEO
 * @since   7.4.0
 */

/* global almaseoEEAT, wp */
(function () {
    'use strict';

    if (typeof almaseoEEAT === 'undefined') return;

    var cfg = almaseoEEAT;

    /* ─── DOM refs ─── */
    var tbody          = document.getElementById('almaseo-eeat-tbody');
    var pagination     = document.getElementById('almaseo-eeat-pagination');
    var statusFilter   = document.getElementById('almaseo-eeat-status-filter');
    var severityFilter = document.getElementById('almaseo-eeat-severity-filter');
    var typeFilter     = document.getElementById('almaseo-eeat-type-filter');
    var scanBtn        = document.getElementById('almaseo-eeat-scan');
    var table          = document.getElementById('almaseo-eeat-table');
    var lastScanEl     = document.getElementById('almaseo-eeat-last-scan');

    /* Stats */
    var statTotal  = document.getElementById('almaseo-eeat-total');
    var statHigh   = document.getElementById('almaseo-eeat-high');
    var statMedium = document.getElementById('almaseo-eeat-medium');
    var statLow    = document.getElementById('almaseo-eeat-low');

    /* Settings */
    var saveBtn     = document.getElementById('almaseo-eeat-save-settings');
    var settingsMsg = document.getElementById('almaseo-eeat-settings-status');

    var currentPage = 1;

    if (!tbody) return;

    /* ============================================================
     *  STATS
     * ============================================================ */
    function loadStats() {
        wp.apiFetch({ url: cfg.restBase + '/stats' }).then(function (data) {
            if (statTotal)  statTotal.textContent  = data.open + data.resolved + data.dismissed;
            if (statHigh)   statHigh.textContent   = data.high;
            if (statMedium) statMedium.textContent  = data.medium;
            if (statLow)    statLow.textContent    = data.low;

            if (lastScanEl && data.last_scan) {
                lastScanEl.textContent = 'Last scanned: ' + data.last_scan;
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
        var type     = typeFilter ? typeFilter.value : '';

        var url = cfg.restBase + '?page=' + currentPage + '&per_page=20';
        if (status)   url += '&status=' + status;
        if (severity) url += '&severity=' + severity;
        if (type)     url += '&finding_type=' + type;

        tbody.innerHTML = '<tr><td colspan="6">Loading&hellip;</td></tr>';

        wp.apiFetch({ url: url, parse: false }).then(function (res) {
            var totalPages = parseInt(res.headers.get('X-WP-TotalPages'), 10) || 1;
            return res.json().then(function (items) {
                renderTable(items, totalPages);
            });
        }).catch(function () {
            tbody.innerHTML = '<tr><td colspan="6">Error loading findings.</td></tr>';
        });
    }

    function renderTable(items, totalPages) {
        if (!items.length) {
            tbody.innerHTML = '';
            if (table) table.style.display = 'none';

            var wrap = tbody.closest('.almaseo-eeat-wrap');
            // Remove existing empty state.
            var prev = wrap.querySelector('.almaseo-eeat-empty');
            if (prev) prev.remove();

            var empty = document.createElement('div');
            empty.className = 'almaseo-eeat-empty';
            empty.innerHTML =
                '<div class="almaseo-eeat-empty-icon"><span class="dashicons dashicons-groups"></span></div>' +
                '<h3>No findings yet</h3>' +
                '<p>' + esc(cfg.noFindings) + '</p>' +
                '<button class="button button-primary" id="almaseo-eeat-empty-scan">' +
                    '<span class="dashicons dashicons-search" style="vertical-align:middle;margin-right:4px;font-size:16px;width:16px;height:16px;"></span>' +
                    'Scan Now' +
                '</button>';
            wrap.appendChild(empty);

            var emptyBtn = document.getElementById('almaseo-eeat-empty-scan');
            if (emptyBtn) {
                emptyBtn.addEventListener('click', function () { handleScan(); });
            }
            return;
        }

        // Make sure table is visible.
        if (table) table.style.display = '';

        // Remove any existing empty state.
        var existing = document.querySelector('.almaseo-eeat-empty');
        if (existing) existing.remove();

        var html = '';
        items.forEach(function (item) {
            html += '<tr>';

            // Post column.
            html += '<td><a href="' + esc(item.post_edit_link || '#') + '">' + esc(item.post_title) + '</a></td>';

            // Author column.
            html += '<td>';
            if (item.author_edit_link) {
                html += '<a href="' + esc(item.author_edit_link) + '">' + esc(item.author_name) + '</a>';
            } else {
                html += esc(item.author_name || '—');
            }
            html += '</td>';

            // Finding column.
            html += '<td class="almaseo-eeat-col-finding">';
            html += '<span class="almaseo-eeat-type-pill">' + formatFindingType(item.finding_type) + '</span><br>';
            html += '<span class="almaseo-eeat-context">' + esc(item.context_snippet) + '</span>';
            if (item.suggestion) {
                html += '<span class="almaseo-eeat-suggestion">' + esc(item.suggestion) + '</span>';
            }
            html += '</td>';

            // Severity.
            html += '<td><span class="almaseo-eeat-severity almaseo-eeat-severity-' + esc(item.severity) + '">' + esc(item.severity) + '</span></td>';

            // Status.
            html += '<td><span class="almaseo-eeat-status-' + esc(item.status) + '">' + capitalize(item.status) + '</span></td>';

            // Actions.
            html += '<td>';
            if (item.status === 'open') {
                html += '<button class="button button-small almaseo-eeat-resolve" data-id="' + item.id + '">Resolve</button> ';
                html += '<button class="button button-small almaseo-eeat-dismiss" data-id="' + item.id + '">Dismiss</button> ';
            } else {
                html += '<button class="button button-small almaseo-eeat-reopen" data-id="' + item.id + '">Reopen</button> ';
            }
            if (item.post_edit_link) {
                html += '<a class="button button-small" href="' + esc(item.post_edit_link) + '">Edit Post</a>';
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

    /* ── Format finding type for display ── */
    function formatFindingType(type) {
        var labels = {
            missing_author: 'Missing Author',
            missing_bio: 'Missing Bio',
            missing_author_schema: 'No Author Schema',
            missing_credentials: 'No Credentials',
            no_sources: 'No Sources',
            missing_review_date: 'No Review Date'
        };
        return labels[type] || type;
    }

    /* ============================================================
     *  SCAN
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
                scanBtn.innerHTML = '<span class="dashicons dashicons-search"></span> Scan Now';
            }

            // Remove empty state if present.
            var empty = document.querySelector('.almaseo-eeat-empty');
            if (empty) empty.remove();
            if (table) table.style.display = '';

            loadStats();
            loadFindings(1);
        }).catch(function () {
            if (scanBtn) {
                scanBtn.disabled = false;
                scanBtn.innerHTML = '<span class="dashicons dashicons-search"></span> Scan Now';
            }
            alert('Scan failed. Please try again.');
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
            var postTypes = document.getElementById('almaseo-eeat-s-posttypes');
            var generics  = document.getElementById('almaseo-eeat-s-generics');
            var sources   = document.getElementById('almaseo-eeat-s-sources');
            var review    = document.getElementById('almaseo-eeat-s-review');
            var ymyl      = document.getElementById('almaseo-eeat-s-ymyl');
            var weight    = document.getElementById('almaseo-eeat-s-weight');
            if (postTypes) postTypes.value = (data.scan_post_types || []).join(', ');
            if (generics)  generics.value  = data.generic_usernames || '';
            if (sources)   sources.checked = !!data.check_sources;
            if (review)    review.checked  = !!data.check_review_date;
            if (ymyl)      ymyl.value      = data.ymyl_categories || '';
            if (weight)    weight.value    = data.health_weight || 0;
        });
    }

    function saveSettings() {
        var postTypes = document.getElementById('almaseo-eeat-s-posttypes').value || 'post,page,product';
        var generics  = document.getElementById('almaseo-eeat-s-generics').value || '';
        var sources   = document.getElementById('almaseo-eeat-s-sources').checked;
        var review    = document.getElementById('almaseo-eeat-s-review').checked;
        var ymyl      = document.getElementById('almaseo-eeat-s-ymyl').value || '';
        var weight    = parseInt(document.getElementById('almaseo-eeat-s-weight').value, 10) || 0;

        if (weight < 0) weight = 0;
        if (weight > 20) weight = 20;

        if (saveBtn) saveBtn.disabled = true;

        wp.apiFetch({
            url: cfg.restBase + '/settings',
            method: 'POST',
            data: {
                scan_post_types: postTypes,
                generic_usernames: generics,
                check_sources: sources,
                check_review_date: review,
                ymyl_categories: ymyl,
                health_weight: weight,
            },
        }).then(function () {
            if (saveBtn) saveBtn.disabled = false;
            if (settingsMsg) {
                settingsMsg.textContent = 'Saved!';
                setTimeout(function () { settingsMsg.textContent = ''; }, 2000);
            }
        }).catch(function () {
            if (saveBtn) saveBtn.disabled = false;
            alert('Failed to save settings.');
        });
    }

    /* ============================================================
     *  EVENT HANDLERS
     * ============================================================ */
    if (statusFilter)   statusFilter.addEventListener('change', function () { loadFindings(1); });
    if (severityFilter) severityFilter.addEventListener('change', function () { loadFindings(1); });
    if (typeFilter)     typeFilter.addEventListener('change', function () { loadFindings(1); });
    if (scanBtn)        scanBtn.addEventListener('click', handleScan);
    if (saveBtn)        saveBtn.addEventListener('click', saveSettings);

    if (pagination) {
        pagination.addEventListener('click', function (e) {
            if (e.target.dataset.page) loadFindings(parseInt(e.target.dataset.page, 10));
        });
    }

    // Delegated click for resolve/dismiss/reopen buttons.
    if (tbody) {
        tbody.addEventListener('click', function (e) {
            var btn = e.target.closest('.almaseo-eeat-resolve');
            if (btn) { handleAction(btn.dataset.id, 'resolve'); return; }

            btn = e.target.closest('.almaseo-eeat-dismiss');
            if (btn) { handleAction(btn.dataset.id, 'dismiss'); return; }

            btn = e.target.closest('.almaseo-eeat-reopen');
            if (btn) { handleAction(btn.dataset.id, 'reopen'); return; }
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
})();
