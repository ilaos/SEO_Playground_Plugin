/**
 * Refresh Queue – Admin JavaScript
 *
 * Handles: loading the queue table, summary stats, recalculation,
 * skip/restore actions, pagination, filtering, and settings.
 *
 * @package AlmaSEO
 * @since   7.2.0
 */

/* global almaseoRQ, wp */
(function () {
    'use strict';

    if (typeof almaseoRQ === 'undefined') return;

    var cfg = almaseoRQ;

    /* ─── DOM refs ─── */
    var tbody         = document.getElementById('almaseo-rq-tbody');
    var pagination    = document.getElementById('almaseo-rq-pagination');
    var statusFilter  = document.getElementById('almaseo-rq-status-filter');
    var tierFilter    = document.getElementById('almaseo-rq-tier-filter');
    var recalcBtn     = document.getElementById('almaseo-rq-recalculate');
    var table         = document.getElementById('almaseo-rq-table');

    /* Stats */
    var statTotal  = document.getElementById('almaseo-rq-total');
    var statHigh   = document.getElementById('almaseo-rq-high');
    var statMedium = document.getElementById('almaseo-rq-medium');
    var statLow    = document.getElementById('almaseo-rq-low');

    /* Settings */
    var settingsPanel = document.getElementById('almaseo-rq-settings');
    var saveBtn       = document.getElementById('almaseo-rq-save-settings');
    var settingsMsg   = document.getElementById('almaseo-rq-settings-status');

    var currentPage = 1;

    if (!tbody) return;

    /* ============================================================
     *  STATS
     * ============================================================ */
    function loadStats() {
        wp.apiFetch({ url: cfg.restBase + '/stats' }).then(function (data) {
            if (statTotal)  statTotal.textContent  = data.total_queued;
            if (statHigh)   statHigh.textContent   = data.high;
            if (statMedium) statMedium.textContent  = data.medium;
            if (statLow)    statLow.textContent    = data.low;
        });
    }

    /* ============================================================
     *  QUEUE TABLE
     * ============================================================ */
    function loadQueue(page) {
        currentPage = page || 1;
        var status = statusFilter ? statusFilter.value : '';
        var tier   = tierFilter ? tierFilter.value : '';

        var url = cfg.restBase + '?page=' + currentPage + '&per_page=20';
        if (status) url += '&status=' + status;
        if (tier)   url += '&priority_tier=' + tier;

        tbody.innerHTML = '<tr><td colspan="8">Loading&hellip;</td></tr>';

        wp.apiFetch({ url: url, parse: false }).then(function (res) {
            var totalPages = parseInt(res.headers.get('X-WP-TotalPages'), 10) || 1;
            return res.json().then(function (items) {
                renderTable(items, totalPages);
            });
        }).catch(function () {
            tbody.innerHTML = '<tr><td colspan="8">Error loading queue.</td></tr>';
        });
    }

    function renderTable(items, totalPages) {
        if (!items.length) {
            tbody.innerHTML = '';
            if (table) table.style.display = 'none';

            var wrap = tbody.closest('.almaseo-rq-wrap');
            var empty = document.createElement('div');
            empty.className = 'almaseo-rq-empty';
            empty.innerHTML =
                '<div class="almaseo-rq-empty-icon"><span class="dashicons dashicons-sort"></span></div>' +
                '<h3>No posts scored yet</h3>' +
                '<p>' + esc(cfg.noItems) + '</p>' +
                '<button class="button button-primary" id="almaseo-rq-empty-recalc">' +
                    '<span class="dashicons dashicons-update" style="vertical-align:middle;margin-right:4px;font-size:16px;width:16px;height:16px;"></span>' +
                    'Recalculate Now' +
                '</button>';
            wrap.appendChild(empty);

            var emptyBtn = document.getElementById('almaseo-rq-empty-recalc');
            if (emptyBtn) {
                emptyBtn.addEventListener('click', function () { handleRecalculate(); });
            }
            return;
        }

        // Make sure table is visible.
        if (table) table.style.display = '';

        // Remove any existing empty state.
        var existing = document.querySelector('.almaseo-rq-empty');
        if (existing) existing.remove();

        var html = '';
        items.forEach(function (item) {
            html += '<tr>';
            html += '<td><a href="' + esc(item.post_edit_link || '#') + '">' + esc(item.post_title) + '</a>';
            if (item.post_type && item.post_type !== 'post') {
                html += ' <small style="color:#888">(' + esc(item.post_type) + ')</small>';
            }
            html += '</td>';

            // Priority column: score + tier badge.
            html += '<td class="almaseo-rq-col-score">';
            html += '<span class="almaseo-rq-score">' + item.priority_score.toFixed(0) + '</span> ';
            html += '<span class="almaseo-rq-tier almaseo-rq-tier-' + esc(item.priority_tier) + '">' + esc(item.priority_tier) + '</span>';
            if (item.reason) {
                html += '<span class="almaseo-rq-reason">' + esc(item.reason) + '</span>';
            }
            html += '</td>';

            // Signal columns.
            html += '<td class="almaseo-rq-col-signal">' + signalBar(item.business_value) + '</td>';
            html += '<td class="almaseo-rq-col-signal">' + signalBar(item.traffic_decline) + '</td>';
            html += '<td class="almaseo-rq-col-signal">' + signalBar(item.conversion_intent) + '</td>';
            html += '<td class="almaseo-rq-col-signal">' + signalBar(item.opportunity_size) + '</td>';

            // Status.
            html += '<td class="almaseo-rq-status">' + esc(item.status) + '</td>';

            // Actions.
            html += '<td>';
            if (item.status === 'queued') {
                html += '<button class="button button-small almaseo-rq-skip" data-id="' + item.id + '">Skip</button> ';
            } else if (item.status === 'skipped') {
                html += '<button class="button button-small almaseo-rq-restore" data-id="' + item.id + '">Restore</button> ';
            }
            if (item.has_refresh_draft) {
                html += '<a class="button button-small" href="' + esc(cfg.refreshDraftsUrl) + '">Review Draft</a>';
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

    /* ── Signal bar helper ── */
    function signalBar(val) {
        val = Math.round(val);
        var cls = val >= 70 ? 'high' : (val >= 40 ? 'mid' : 'low');
        return '<span class="almaseo-rq-signal-bar"><span class="almaseo-rq-signal-fill almaseo-rq-signal-fill-' + cls + '" style="width:' + val + '%"></span></span>' +
               '<span class="almaseo-rq-signal-val">' + val + '</span>';
    }

    /* ============================================================
     *  RECALCULATE
     * ============================================================ */
    function handleRecalculate() {
        if (recalcBtn) {
            recalcBtn.disabled = true;
            recalcBtn.innerHTML = '<span class="dashicons dashicons-update" style="animation:rotation 1s linear infinite"></span> Recalculating\u2026';
        }

        wp.apiFetch({
            url: cfg.restBase + '/recalculate',
            method: 'POST',
        }).then(function (data) {
            if (recalcBtn) {
                recalcBtn.disabled = false;
                recalcBtn.innerHTML = '<span class="dashicons dashicons-update"></span> Recalculate';
            }

            // Remove empty state if present.
            var empty = document.querySelector('.almaseo-rq-empty');
            if (empty) empty.remove();
            if (table) table.style.display = '';

            loadStats();
            loadQueue(1);
        }).catch(function () {
            if (recalcBtn) {
                recalcBtn.disabled = false;
                recalcBtn.innerHTML = '<span class="dashicons dashicons-update"></span> Recalculate';
            }
            alert('Recalculation failed. Please try again.');
        });
    }

    /* ============================================================
     *  SKIP / RESTORE
     * ============================================================ */
    function handleAction(id, action) {
        wp.apiFetch({
            url: cfg.restBase + '/' + id + '/' + action,
            method: 'PATCH',
        }).then(function () {
            loadStats();
            loadQueue(currentPage);
        });
    }

    /* ============================================================
     *  SETTINGS
     * ============================================================ */
    function loadSettings() {
        wp.apiFetch({ url: cfg.restBase + '/settings' }).then(function (data) {
            var bv = document.getElementById('almaseo-rq-w-bv');
            var td = document.getElementById('almaseo-rq-w-td');
            var ci = document.getElementById('almaseo-rq-w-ci');
            var os = document.getElementById('almaseo-rq-w-os');
            if (bv) bv.value = Math.round(data.business_value);
            if (td) td.value = Math.round(data.traffic_decline);
            if (ci) ci.value = Math.round(data.conversion_intent);
            if (os) os.value = Math.round(data.opportunity_size);
        });
    }

    function saveSettings() {
        var bv = parseInt(document.getElementById('almaseo-rq-w-bv').value, 10) || 0;
        var td = parseInt(document.getElementById('almaseo-rq-w-td').value, 10) || 0;
        var ci = parseInt(document.getElementById('almaseo-rq-w-ci').value, 10) || 0;
        var os = parseInt(document.getElementById('almaseo-rq-w-os').value, 10) || 0;

        if (bv + td + ci + os <= 0) {
            alert('At least one weight must be greater than zero.');
            return;
        }

        if (saveBtn) saveBtn.disabled = true;

        wp.apiFetch({
            url: cfg.restBase + '/settings',
            method: 'POST',
            data: {
                business_value: bv,
                traffic_decline: td,
                conversion_intent: ci,
                opportunity_size: os,
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
    if (statusFilter) statusFilter.addEventListener('change', function () { loadQueue(1); });
    if (tierFilter)   tierFilter.addEventListener('change', function () { loadQueue(1); });
    if (recalcBtn)    recalcBtn.addEventListener('click', handleRecalculate);
    if (saveBtn)      saveBtn.addEventListener('click', saveSettings);

    if (pagination) {
        pagination.addEventListener('click', function (e) {
            if (e.target.dataset.page) loadQueue(parseInt(e.target.dataset.page, 10));
        });
    }

    // Delegated click for skip/restore buttons.
    if (tbody) {
        tbody.addEventListener('click', function (e) {
            var btn = e.target.closest('.almaseo-rq-skip');
            if (btn) { handleAction(btn.dataset.id, 'skip'); return; }

            btn = e.target.closest('.almaseo-rq-restore');
            if (btn) { handleAction(btn.dataset.id, 'restore'); return; }
        });
    }

    /* ============================================================
     *  INIT
     * ============================================================ */
    loadStats();
    loadQueue(1);
    loadSettings();

    /* ─── Utility ─── */
    function esc(str) {
        if (!str) return '';
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    }
})();
