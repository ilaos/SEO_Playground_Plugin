/**
 * Orphan Pages – Admin JavaScript
 *
 * Handles: loading orphan data, stats, scanning, filtering,
 * dismiss actions, pagination, and cluster filter population.
 *
 * @package AlmaSEO
 * @since   7.7.0
 */

/* global almaseoOrphans, wp */
(function () {
    'use strict';

    if (typeof almaseoOrphans === 'undefined') return;

    var cfg = almaseoOrphans;

    /* ─── DOM refs ─── */
    var tbody          = document.getElementById('almaseo-orphan-tbody');
    var pagination     = document.getElementById('almaseo-orphan-pagination');
    var statusFilter   = document.getElementById('almaseo-orphan-status-filter');
    var clusterFilter  = document.getElementById('almaseo-orphan-cluster-filter');
    var searchInput    = document.getElementById('almaseo-orphan-search');
    var scanBtn        = document.getElementById('almaseo-orphan-scan');
    var table          = document.getElementById('almaseo-orphan-table');
    var lastScanEl     = document.getElementById('almaseo-orphan-last-scan');

    /* Stats */
    var statOrphans = document.getElementById('almaseo-orphan-count-orphans');
    var statWeak    = document.getElementById('almaseo-orphan-count-weak');
    var statHealthy = document.getElementById('almaseo-orphan-count-healthy');
    var statHubs    = document.getElementById('almaseo-orphan-count-hubs');

    var currentPage = 1;
    var searchTimer = null;

    if (!tbody) return;

    /* ============================================================
     *  STATS
     * ============================================================ */
    function loadStats() {
        wp.apiFetch({ url: cfg.restBase + '/stats' }).then(function (data) {
            if (statOrphans) statOrphans.textContent = data.orphans;
            if (statWeak)    statWeak.textContent    = data.weak;
            if (statHealthy) statHealthy.textContent = data.healthy;
            if (statHubs)    statHubs.textContent    = data.hub_candidates;

            if (lastScanEl && data.last_scan) {
                lastScanEl.textContent = 'Last scanned: ' + formatDate(data.last_scan);
            }
        });
    }

    /* ============================================================
     *  CLUSTER FILTER
     * ============================================================ */
    function loadClusters() {
        if (!clusterFilter) return;
        wp.apiFetch({ url: cfg.restBase + '/clusters' }).then(function (clusters) {
            var html = '<option value="">All clusters</option>';
            clusters.forEach(function (c) {
                html += '<option value="' + esc(c) + '">' + esc(c) + '</option>';
            });
            clusterFilter.innerHTML = html;
        });
    }

    /* ============================================================
     *  ORPHAN TABLE
     * ============================================================ */
    function loadOrphans(page) {
        currentPage = page || 1;
        var status  = statusFilter ? statusFilter.value : '';
        var cluster = clusterFilter ? clusterFilter.value : '';
        var search  = searchInput ? searchInput.value.trim() : '';

        var url = cfg.restBase + '?page=' + currentPage + '&per_page=20';
        if (status)  url += '&status=' + encodeURIComponent(status);
        if (cluster) url += '&cluster_id=' + encodeURIComponent(cluster);
        if (search)  url += '&search=' + encodeURIComponent(search);

        tbody.innerHTML = '<tr><td colspan="7">Loading&hellip;</td></tr>';

        wp.apiFetch({ url: url, parse: false }).then(function (res) {
            var totalPages = parseInt(res.headers.get('X-WP-TotalPages'), 10) || 1;
            return res.json().then(function (items) {
                renderTable(items, totalPages);
            });
        }).catch(function () {
            tbody.innerHTML = '<tr><td colspan="7">Error loading data.</td></tr>';
        });
    }

    function renderTable(items, totalPages) {
        if (!items.length) {
            tbody.innerHTML = '';
            if (table) table.style.display = 'none';

            var wrap = tbody.closest('.almaseo-orphan-wrap');
            var prev = wrap.querySelector('.almaseo-orphan-empty');
            if (prev) prev.remove();

            var empty = document.createElement('div');
            empty.className = 'almaseo-orphan-empty';
            empty.innerHTML =
                '<div class="almaseo-orphan-empty-icon"><span class="dashicons dashicons-admin-links"></span></div>' +
                '<h3>No results</h3>' +
                '<p>' + esc(cfg.noFindings) + '</p>' +
                '<button class="button button-primary" id="almaseo-orphan-empty-scan">' +
                    '<span class="dashicons dashicons-search" style="vertical-align:middle;margin-right:4px;font-size:16px;width:16px;height:16px;"></span>' +
                    'Scan Now' +
                '</button>';
            wrap.appendChild(empty);

            var emptyBtn = document.getElementById('almaseo-orphan-empty-scan');
            if (emptyBtn) emptyBtn.addEventListener('click', function () { handleScan(); });
            if (pagination) pagination.innerHTML = '';
            return;
        }

        if (table) table.style.display = '';
        var existing = document.querySelector('.almaseo-orphan-empty');
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
            if (item.is_hub_candidate) {
                html += '<span class="almaseo-orphan-hub">Hub candidate</span>';
            }
            html += '</td>';

            // Inbound.
            var inClass = item.inbound_count === 0 ? 'almaseo-orphan-inbound-0' : (item.inbound_count <= 2 ? 'almaseo-orphan-inbound-low' : '');
            html += '<td class="almaseo-orphan-col-num"><span class="' + inClass + '">' + item.inbound_count + '</span></td>';

            // Outbound.
            html += '<td class="almaseo-orphan-col-num">' + item.outbound_count + '</td>';

            // Cluster.
            html += '<td>';
            if (item.cluster_id) {
                html += '<span class="almaseo-orphan-cluster">' + esc(item.cluster_id) + '</span>';
            }
            html += '</td>';

            // Status.
            html += '<td><span class="almaseo-orphan-status almaseo-orphan-status-' + esc(item.status) + '">' + capitalize(item.status) + '</span></td>';

            // Suggestion.
            html += '<td class="almaseo-orphan-col-suggestion">';
            if (item.suggestion) {
                html += '<span class="almaseo-orphan-suggestion">' + esc(item.suggestion) + '</span>';
            }
            html += '</td>';

            // Actions.
            html += '<td>';
            if (item.status === 'orphan' || item.status === 'weak') {
                html += '<button class="button button-small almaseo-orphan-dismiss" data-id="' + item.id + '">Dismiss</button> ';
            }
            if (item.permalink) {
                html += '<a class="button button-small" href="' + esc(item.permalink) + '" target="_blank">View</a> ';
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

            var empty = document.querySelector('.almaseo-orphan-empty');
            if (empty) empty.remove();
            if (table) table.style.display = '';

            loadStats();
            loadClusters();
            loadOrphans(1);
        }).catch(function () {
            if (scanBtn) {
                scanBtn.disabled = false;
                scanBtn.innerHTML = '<span class="dashicons dashicons-search"></span> Scan Now';
            }
            alert('Scan failed. Please try again.');
        });
    }

    /* ============================================================
     *  DISMISS
     * ============================================================ */
    function handleDismiss(id) {
        wp.apiFetch({
            url: cfg.restBase + '/' + id + '/dismiss',
            method: 'PATCH',
        }).then(function () {
            loadStats();
            loadOrphans(currentPage);
        });
    }

    /* ============================================================
     *  EVENT HANDLERS
     * ============================================================ */
    if (statusFilter)  statusFilter.addEventListener('change', function () { loadOrphans(1); });
    if (clusterFilter) clusterFilter.addEventListener('change', function () { loadOrphans(1); });
    if (scanBtn)       scanBtn.addEventListener('click', handleScan);

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { loadOrphans(1); }, 400);
        });
    }

    if (pagination) {
        pagination.addEventListener('click', function (e) {
            if (e.target.dataset.page) loadOrphans(parseInt(e.target.dataset.page, 10));
        });
    }

    if (tbody) {
        tbody.addEventListener('click', function (e) {
            var btn = e.target.closest('.almaseo-orphan-dismiss');
            if (btn) { handleDismiss(btn.dataset.id); }
        });
    }

    /* ============================================================
     *  INIT
     * ============================================================ */
    loadStats();
    loadClusters();
    loadOrphans(1);

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
