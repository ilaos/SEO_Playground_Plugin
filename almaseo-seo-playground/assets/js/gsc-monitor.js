/**
 * GSC Monitor – Admin JavaScript
 *
 * Handles: tabbed navigation, per-tab data loading, findings table,
 * summary stats, bulk actions, resolve/dismiss/reopen, pagination,
 * filtering, search, and settings.
 *
 * @package AlmaSEO
 * @since   7.5.0
 */

/* global almaseoGSC, wp */
(function () {
    'use strict';

    if (typeof almaseoGSC === 'undefined') return;

    var cfg = almaseoGSC;

    /* ── Subtype options per finding type ── */
    var SUBTYPES = {
        indexation_drift: [
            { value: 'not_indexed', label: 'Not Indexed' },
            { value: 'excluded_spike', label: 'Excluded Spike' },
            { value: 'coverage_drop', label: 'Coverage Drop' }
        ],
        rich_result_loss: [
            { value: 'lost', label: 'Lost' },
            { value: 'gained', label: 'Gained' },
            { value: 'degraded', label: 'Degraded' }
        ],
        snippet_rewrite: [
            { value: 'title_rewrite', label: 'Title Rewrite' },
            { value: 'description_rewrite', label: 'Description Rewrite' }
        ]
    };

    /* ─── DOM refs ─── */
    var tbody          = document.getElementById('almaseo-gsc-tbody');
    var pagination     = document.getElementById('almaseo-gsc-pagination');
    var statusFilter   = document.getElementById('almaseo-gsc-status-filter');
    var severityFilter = document.getElementById('almaseo-gsc-severity-filter');
    var subtypeFilter  = document.getElementById('almaseo-gsc-subtype-filter');
    var searchInput    = document.getElementById('almaseo-gsc-search');
    var table          = document.getElementById('almaseo-gsc-table');
    var selectAllCb    = document.getElementById('almaseo-gsc-select-all');
    var bulkAction     = document.getElementById('almaseo-gsc-bulk-action');
    var bulkApply      = document.getElementById('almaseo-gsc-bulk-apply');

    /* Stats */
    var statOpen     = document.getElementById('almaseo-gsc-open');
    var statHigh     = document.getElementById('almaseo-gsc-high');
    var statMedium   = document.getElementById('almaseo-gsc-medium');
    var statLow      = document.getElementById('almaseo-gsc-low');
    var statResolved = document.getElementById('almaseo-gsc-resolved');

    /* Settings */
    var saveBtn     = document.getElementById('almaseo-gsc-save-settings');
    var settingsMsg = document.getElementById('almaseo-gsc-settings-status');

    var currentPage = 1;
    var activeType  = 'indexation_drift';
    var searchTimer = null;

    if (!tbody) return;

    /* ============================================================
     *  TABS
     * ============================================================ */
    var tabButtons = document.querySelectorAll('.almaseo-gsc-tab');

    function switchTab(type) {
        activeType = type;
        currentPage = 1;

        // Update active class.
        for (var i = 0; i < tabButtons.length; i++) {
            tabButtons[i].classList.toggle('active', tabButtons[i].getAttribute('data-type') === type);
        }

        // Update subtype filter options.
        updateSubtypeOptions(type);

        // Reset filters.
        if (statusFilter) statusFilter.value = '';
        if (severityFilter) severityFilter.value = '';
        if (subtypeFilter) subtypeFilter.value = '';
        if (searchInput) searchInput.value = '';

        loadStats();
        loadFindings(1);
    }

    function updateSubtypeOptions(type) {
        if (!subtypeFilter) return;
        var opts = SUBTYPES[type] || [];
        var html = '<option value="">All subtypes</option>';
        opts.forEach(function (o) {
            html += '<option value="' + o.value + '">' + esc(o.label) + '</option>';
        });
        subtypeFilter.innerHTML = html;
    }

    // Wire up tab clicks.
    for (var t = 0; t < tabButtons.length; t++) {
        tabButtons[t].addEventListener('click', function () {
            switchTab(this.getAttribute('data-type'));
        });
    }

    /* ============================================================
     *  TAB COUNTS
     * ============================================================ */
    function loadTabCounts() {
        var types = ['indexation_drift', 'rich_result_loss', 'snippet_rewrite'];
        types.forEach(function (type) {
            wp.apiFetch({ url: cfg.restBase + '/stats?finding_type=' + type }).then(function (data) {
                var el = document.getElementById('almaseo-gsc-tab-count-' + type);
                if (el) el.textContent = data.open || '';
            });
        });
    }

    /* ============================================================
     *  STATS (per active tab)
     * ============================================================ */
    function loadStats() {
        wp.apiFetch({ url: cfg.restBase + '/stats?finding_type=' + activeType }).then(function (data) {
            if (statOpen)     statOpen.textContent     = data.open;
            if (statHigh)     statHigh.textContent     = data.high;
            if (statMedium)   statMedium.textContent   = data.medium;
            if (statLow)      statLow.textContent      = data.low;
            if (statResolved) statResolved.textContent  = data.resolved;
        });
    }

    /* ============================================================
     *  FINDINGS TABLE
     * ============================================================ */
    function loadFindings(page) {
        currentPage = page || 1;
        var status   = statusFilter ? statusFilter.value : '';
        var severity = severityFilter ? severityFilter.value : '';
        var subtype  = subtypeFilter ? subtypeFilter.value : '';
        var search   = searchInput ? searchInput.value.trim() : '';

        var url = cfg.restBase + '?page=' + currentPage + '&per_page=20&finding_type=' + activeType;
        if (status)   url += '&status=' + encodeURIComponent(status);
        if (severity) url += '&severity=' + encodeURIComponent(severity);
        if (subtype)  url += '&subtype=' + encodeURIComponent(subtype);
        if (search)   url += '&search=' + encodeURIComponent(search);

        tbody.innerHTML = '<tr><td colspan="8">Loading&hellip;</td></tr>';

        wp.apiFetch({ url: url, parse: false }).then(function (res) {
            var totalPages = parseInt(res.headers.get('X-WP-TotalPages'), 10) || 1;
            return res.json().then(function (items) {
                renderTable(items, totalPages);
            });
        }).catch(function () {
            tbody.innerHTML = '<tr><td colspan="8">Error loading findings.</td></tr>';
        });
    }

    function renderTable(items, totalPages) {
        // Uncheck select-all.
        if (selectAllCb) selectAllCb.checked = false;

        if (!items.length) {
            tbody.innerHTML = '';
            if (table) table.style.display = 'none';

            var wrap = tbody.closest('.almaseo-gsc-wrap');
            var prev = wrap.querySelector('.almaseo-gsc-empty');
            if (prev) prev.remove();

            var empty = document.createElement('div');
            empty.className = 'almaseo-gsc-empty';
            empty.innerHTML =
                '<div class="almaseo-gsc-empty-icon"><span class="dashicons dashicons-chart-area"></span></div>' +
                '<h3>No findings</h3>' +
                '<p>' + esc(cfg.noFindings) + '</p>';
            wrap.appendChild(empty);
            if (pagination) pagination.innerHTML = '';
            return;
        }

        if (table) table.style.display = '';
        var existing = document.querySelector('.almaseo-gsc-empty');
        if (existing) existing.remove();

        var html = '';
        items.forEach(function (item) {
            html += '<tr>';

            // Checkbox.
            html += '<td class="almaseo-gsc-col-cb"><input type="checkbox" class="almaseo-gsc-cb" value="' + item.id + '"></td>';

            // Page column.
            html += '<td>';
            if (item.post_title && item.post_edit_link) {
                html += '<a href="' + esc(item.post_edit_link) + '">' + esc(item.post_title) + '</a>';
            } else if (item.post_title) {
                html += esc(item.post_title);
            }
            html += '<span class="almaseo-gsc-url">' + esc(item.url) + '</span>';
            html += '</td>';

            // Subtype.
            html += '<td><span class="almaseo-gsc-subtype-badge almaseo-gsc-subtype-' + esc(item.subtype) + '">' + esc(item.subtype_label) + '</span></td>';

            // Details.
            html += '<td class="almaseo-gsc-col-detail">';
            if (item.detected_value) {
                html += '<div class="almaseo-gsc-detail-detected">' + esc(item.detected_value) + '</div>';
            }
            if (item.expected_value) {
                html += '<div class="almaseo-gsc-detail-expected"><strong>Expected:</strong> ' + esc(item.expected_value) + '</div>';
            }
            if (item.suggestion) {
                html += '<span class="almaseo-gsc-suggestion">' + esc(item.suggestion) + '</span>';
            }
            html += '</td>';

            // Severity.
            html += '<td><span class="almaseo-gsc-severity almaseo-gsc-severity-' + esc(item.severity) + '">' + esc(item.severity) + '</span></td>';

            // Last seen.
            html += '<td><span class="almaseo-gsc-date">' + formatDate(item.last_seen) + '</span></td>';

            // Status.
            html += '<td><span class="almaseo-gsc-status-' + esc(item.status) + '">' + capitalize(item.status) + '</span></td>';

            // Actions.
            html += '<td>';
            if (item.status === 'open') {
                html += '<button class="button button-small almaseo-gsc-resolve" data-id="' + item.id + '">Resolve</button> ';
                html += '<button class="button button-small almaseo-gsc-dismiss" data-id="' + item.id + '">Dismiss</button> ';
            } else {
                html += '<button class="button button-small almaseo-gsc-reopen" data-id="' + item.id + '">Reopen</button> ';
            }
            if (item.permalink) {
                html += '<a class="button button-small" href="' + esc(item.permalink) + '" target="_blank">View</a>';
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
     *  RESOLVE / DISMISS / REOPEN
     * ============================================================ */
    function handleAction(id, action) {
        wp.apiFetch({
            url: cfg.restBase + '/' + id + '/' + action,
            method: 'PATCH',
        }).then(function () {
            loadStats();
            loadTabCounts();
            loadFindings(currentPage);
        });
    }

    /* ============================================================
     *  BULK ACTIONS
     * ============================================================ */
    function getSelectedIds() {
        var cbs = document.querySelectorAll('.almaseo-gsc-cb:checked');
        var ids = [];
        for (var i = 0; i < cbs.length; i++) {
            ids.push(parseInt(cbs[i].value, 10));
        }
        return ids;
    }

    function handleBulk() {
        if (!bulkAction) return;
        var action = bulkAction.value;
        var ids = getSelectedIds();

        if (!action || !ids.length) return;

        if (bulkApply) bulkApply.disabled = true;

        wp.apiFetch({
            url: cfg.restBase + '/bulk',
            method: 'POST',
            data: { action: action, ids: ids },
        }).then(function () {
            if (bulkApply) bulkApply.disabled = false;
            if (bulkAction) bulkAction.value = '';
            loadStats();
            loadTabCounts();
            loadFindings(currentPage);
        }).catch(function () {
            if (bulkApply) bulkApply.disabled = false;
            alert('Bulk action failed.');
        });
    }

    /* ============================================================
     *  SETTINGS
     * ============================================================ */
    function loadSettings() {
        wp.apiFetch({ url: cfg.restBase + '/settings' }).then(function (data) {
            var indexation  = document.getElementById('almaseo-gsc-s-indexation');
            var snippet     = document.getElementById('almaseo-gsc-s-snippet');
            var autodismiss = document.getElementById('almaseo-gsc-s-autodismiss');
            if (indexation)  indexation.value  = data.alert_threshold_indexation;
            if (snippet)     snippet.value     = data.alert_threshold_snippet;
            if (autodismiss) autodismiss.value  = data.auto_dismiss_days;
        });
    }

    function saveSettings() {
        var indexation  = parseInt(document.getElementById('almaseo-gsc-s-indexation').value, 10) || 5;
        var snippet     = parseInt(document.getElementById('almaseo-gsc-s-snippet').value, 10) || 100;
        var autodismiss = parseInt(document.getElementById('almaseo-gsc-s-autodismiss').value, 10) || 0;

        if (indexation < 1) indexation = 1;
        if (snippet < 1) snippet = 1;
        if (autodismiss < 0) autodismiss = 0;

        if (saveBtn) saveBtn.disabled = true;

        wp.apiFetch({
            url: cfg.restBase + '/settings',
            method: 'POST',
            data: {
                alert_threshold_indexation: indexation,
                alert_threshold_snippet: snippet,
                auto_dismiss_days: autodismiss,
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
    if (subtypeFilter)  subtypeFilter.addEventListener('change', function () { loadFindings(1); });
    if (saveBtn)        saveBtn.addEventListener('click', saveSettings);
    if (bulkApply)      bulkApply.addEventListener('click', handleBulk);

    // Search with debounce.
    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { loadFindings(1); }, 400);
        });
    }

    // Select all checkbox.
    if (selectAllCb) {
        selectAllCb.addEventListener('change', function () {
            var cbs = document.querySelectorAll('.almaseo-gsc-cb');
            for (var i = 0; i < cbs.length; i++) {
                cbs[i].checked = selectAllCb.checked;
            }
        });
    }

    // Pagination.
    if (pagination) {
        pagination.addEventListener('click', function (e) {
            if (e.target.dataset.page) loadFindings(parseInt(e.target.dataset.page, 10));
        });
    }

    // Delegated click for resolve/dismiss/reopen buttons.
    if (tbody) {
        tbody.addEventListener('click', function (e) {
            var btn = e.target.closest('.almaseo-gsc-resolve');
            if (btn) { handleAction(btn.dataset.id, 'resolve'); return; }

            btn = e.target.closest('.almaseo-gsc-dismiss');
            if (btn) { handleAction(btn.dataset.id, 'dismiss'); return; }

            btn = e.target.closest('.almaseo-gsc-reopen');
            if (btn) { handleAction(btn.dataset.id, 'reopen'); return; }
        });
    }

    /* ============================================================
     *  INIT
     * ============================================================ */
    updateSubtypeOptions(activeType);
    loadTabCounts();
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
        if (!dateStr) return '—';
        var d = new Date(dateStr + 'Z');
        if (isNaN(d.getTime())) return dateStr;
        return d.toLocaleDateString(undefined, { year: 'numeric', month: 'short', day: 'numeric' });
    }
})();
