/**
 * Date Hygiene Scanner – Admin JavaScript
 *
 * Handles: loading findings table, summary stats, scanning,
 * resolve/dismiss/reopen actions, pagination, filtering, and settings.
 *
 * @package AlmaSEO
 * @since   7.3.0
 */

/* global almaseoDH, wp */
(function () {
    'use strict';

    if (typeof almaseoDH === 'undefined') return;

    var cfg = almaseoDH;

    /* ─── DOM refs ─── */
    var tbody          = document.getElementById('almaseo-dh-tbody');
    var pagination     = document.getElementById('almaseo-dh-pagination');
    var statusFilter   = document.getElementById('almaseo-dh-status-filter');
    var severityFilter = document.getElementById('almaseo-dh-severity-filter');
    var typeFilter     = document.getElementById('almaseo-dh-type-filter');
    var scanBtn        = document.getElementById('almaseo-dh-scan');
    var table          = document.getElementById('almaseo-dh-table');
    var lastScanEl     = document.getElementById('almaseo-dh-last-scan');

    /* Stats */
    var statTotal  = document.getElementById('almaseo-dh-total');
    var statHigh   = document.getElementById('almaseo-dh-high');
    var statMedium = document.getElementById('almaseo-dh-medium');
    var statLow    = document.getElementById('almaseo-dh-low');

    /* Settings */
    var saveBtn     = document.getElementById('almaseo-dh-save-settings');
    var settingsMsg = document.getElementById('almaseo-dh-settings-status');

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

            var wrap = tbody.closest('.almaseo-dh-wrap');
            // Remove existing empty state.
            var prev = wrap.querySelector('.almaseo-dh-empty');
            if (prev) prev.remove();

            var empty = document.createElement('div');
            empty.className = 'almaseo-dh-empty';
            empty.innerHTML =
                '<div class="almaseo-dh-empty-icon"><span class="dashicons dashicons-calendar-alt"></span></div>' +
                '<h3>No findings yet</h3>' +
                '<p>' + esc(cfg.noFindings) + '</p>' +
                '<button class="button button-primary" id="almaseo-dh-empty-scan">' +
                    '<span class="dashicons dashicons-search" style="vertical-align:middle;margin-right:4px;font-size:16px;width:16px;height:16px;"></span>' +
                    'Scan Now' +
                '</button>';
            wrap.appendChild(empty);

            var emptyBtn = document.getElementById('almaseo-dh-empty-scan');
            if (emptyBtn) {
                emptyBtn.addEventListener('click', function () { handleScan(); });
            }
            return;
        }

        // Make sure table is visible.
        if (table) table.style.display = '';

        // Remove any existing empty state.
        var existing = document.querySelector('.almaseo-dh-empty');
        if (existing) existing.remove();

        var html = '';
        items.forEach(function (item) {
            html += '<tr>';

            // Post column.
            html += '<td><a href="' + esc(item.post_edit_link || '#') + '">' + esc(item.post_title) + '</a>';
            if (item.location) {
                html += '<span class="almaseo-dh-location">' + esc(item.location) + '</span>';
            }
            html += '</td>';

            // Finding / context column.
            html += '<td class="almaseo-dh-col-context">';
            html += '<span class="almaseo-dh-type-pill">' + formatFindingType(item.finding_type) + '</span><br>';
            html += '<span class="almaseo-dh-context">' + highlightValue(item.context_snippet, item.detected_value) + '</span>';
            if (item.suggestion) {
                html += '<span class="almaseo-dh-suggestion">' + esc(item.suggestion) + '</span>';
            }
            html += '</td>';

            // Detected value.
            html += '<td><span class="almaseo-dh-detected">' + esc(item.detected_value) + '</span></td>';

            // Severity.
            html += '<td><span class="almaseo-dh-severity almaseo-dh-severity-' + esc(item.severity) + '">' + esc(item.severity) + '</span></td>';

            // Status.
            html += '<td><span class="almaseo-dh-status-' + esc(item.status) + '">' + capitalize(item.status) + '</span></td>';

            // Actions.
            html += '<td>';
            if (item.status === 'open') {
                html += '<button class="button button-small almaseo-dh-resolve" data-id="' + item.id + '">Resolve</button> ';
                html += '<button class="button button-small almaseo-dh-dismiss" data-id="' + item.id + '">Dismiss</button> ';
            } else {
                html += '<button class="button button-small almaseo-dh-reopen" data-id="' + item.id + '">Reopen</button> ';
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

    /* ── Highlight detected value in context ── */
    function highlightValue(context, value) {
        if (!context || !value) return esc(context || '');
        var escaped = esc(context);
        var escapedVal = esc(value);
        // Replace first occurrence with mark tag.
        var idx = escaped.indexOf(escapedVal);
        if (idx !== -1) {
            return escaped.substring(0, idx) +
                   '<mark>' + escapedVal + '</mark>' +
                   escaped.substring(idx + escapedVal.length);
        }
        return escaped;
    }

    /* ── Format finding type for display ── */
    function formatFindingType(type) {
        var labels = {
            stale_year: 'Stale Year',
            dated_phrase: 'Dated Phrase',
            superlative_year: 'Superlative Year',
            price_reference: 'Price Reference',
            regulation_mention: 'Regulation'
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
        }).then(function (data) {
            if (scanBtn) {
                scanBtn.disabled = false;
                scanBtn.innerHTML = '<span class="dashicons dashicons-search"></span> Scan Now';
            }

            // Remove empty state if present.
            var empty = document.querySelector('.almaseo-dh-empty');
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
            var postTypes   = document.getElementById('almaseo-dh-s-posttypes');
            var threshold   = document.getElementById('almaseo-dh-s-threshold');
            var prices      = document.getElementById('almaseo-dh-s-prices');
            var regulations = document.getElementById('almaseo-dh-s-regulations');
            if (postTypes)   postTypes.value = (data.scan_post_types || []).join(', ');
            if (threshold)   threshold.value = data.stale_threshold;
            if (prices)      prices.checked = !!data.scan_prices;
            if (regulations) regulations.checked = !!data.scan_regulations;
        });
    }

    function saveSettings() {
        var postTypes   = document.getElementById('almaseo-dh-s-posttypes').value || 'post,page,product';
        var threshold   = parseInt(document.getElementById('almaseo-dh-s-threshold').value, 10) || 2;
        var prices      = document.getElementById('almaseo-dh-s-prices').checked;
        var regulations = document.getElementById('almaseo-dh-s-regulations').checked;

        if (threshold < 1) threshold = 1;

        if (saveBtn) saveBtn.disabled = true;

        wp.apiFetch({
            url: cfg.restBase + '/settings',
            method: 'POST',
            data: {
                scan_post_types: postTypes,
                stale_threshold: threshold,
                scan_prices: prices,
                scan_regulations: regulations,
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
            var btn = e.target.closest('.almaseo-dh-resolve');
            if (btn) { handleAction(btn.dataset.id, 'resolve'); return; }

            btn = e.target.closest('.almaseo-dh-dismiss');
            if (btn) { handleAction(btn.dataset.id, 'dismiss'); return; }

            btn = e.target.closest('.almaseo-dh-reopen');
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
