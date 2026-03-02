/**
 * Refresh Drafts – Admin JavaScript
 *
 * Handles:
 *  - List page: fetching drafts, rendering the table, pagination.
 *  - Review page: accept-all / reject-all, apply, dismiss.
 *
 * @package AlmaSEO
 * @since   7.0.0
 */

/* global almaseoRD, wp */
(function () {
    'use strict';

    /* ─── Guards ─── */
    if (typeof almaseoRD === 'undefined') return;

    var cfg = almaseoRD;

    /* ============================================================
     *  LIST PAGE
     * ============================================================ */
    var tbody      = document.getElementById('almaseo-rd-tbody');
    var pagination = document.getElementById('almaseo-rd-pagination');
    var filterSel  = document.getElementById('almaseo-rd-status-filter');
    var refreshBtn = document.getElementById('almaseo-rd-refresh-btn');

    if (tbody) {
        var currentPage = 1;

        function loadDrafts(page) {
            currentPage = page || 1;
            var status = filterSel ? filterSel.value : '';
            var url    = cfg.restBase + '?page=' + currentPage + '&per_page=20';
            if (status) url += '&status=' + status;

            tbody.innerHTML = '<tr><td colspan="6">Loading&hellip;</td></tr>';

            wp.apiFetch({ url: url, parse: false }).then(function (res) {
                var total      = parseInt(res.headers.get('X-WP-Total'), 10) || 0;
                var totalPages = parseInt(res.headers.get('X-WP-TotalPages'), 10) || 1;
                return res.json().then(function (items) {
                    renderTable(items, total, totalPages);
                });
            }).catch(function () {
                tbody.innerHTML = '<tr><td colspan="6">Error loading drafts.</td></tr>';
            });
        }

        function renderTable(items, total, totalPages) {
            if (!items.length) {
                tbody.innerHTML = '';
                var wrap = tbody.closest('.almaseo-rd-wrap');
                var table = document.getElementById('almaseo-rd-table');
                if (table) table.style.display = 'none';

                var empty = document.createElement('div');
                empty.className = 'almaseo-rd-empty';
                empty.innerHTML =
                    '<div class="almaseo-rd-empty-icon"><span class="dashicons dashicons-update-alt"></span></div>' +
                    '<h3>No content refreshes yet</h3>' +
                    '<p>' + esc(cfg.noDrafts) + '</p>' +
                    '<ul class="almaseo-rd-empty-steps">' +
                        '<li><span class="almaseo-rd-step-num">1</span><span>AlmaSEO analyzes your published content and identifies sections that could be improved or updated</span></li>' +
                        '<li><span class="almaseo-rd-step-num">2</span><span>A refresh draft appears here with suggested changes for you to review</span></li>' +
                        '<li><span class="almaseo-rd-step-num">3</span><span>You compare each section side by side — your current version vs. the proposed version</span></li>' +
                        '<li><span class="almaseo-rd-step-num">4</span><span>Accept the improvements you like, reject the rest, and apply with one click</span></li>' +
                    '</ul>';
                wrap.appendChild(empty);
                return;
            }

            var html = '';
            items.forEach(function (d) {
                html += '<tr>';
                html += '<td><a href="' + esc(cfg.adminUrl + '&review=' + d.id) + '">' + esc(d.post_title) + '</a></td>';
                html += '<td>' + d.changed_count + ' / ' + d.section_count + '</td>';
                html += '<td>' + esc(d.trigger_source) + '</td>';
                html += '<td class="almaseo-rd-status">' + esc(d.status) + '</td>';
                html += '<td>' + esc(d.created_at) + '</td>';
                html += '<td>';
                if (d.status === 'pending') {
                    html += '<a class="button button-small" href="' + esc(cfg.adminUrl + '&review=' + d.id) + '">Review</a>';
                } else {
                    html += '<a class="button button-small" href="' + esc(cfg.adminUrl + '&review=' + d.id) + '">View</a>';
                }
                html += '</td>';
                html += '</tr>';
            });
            tbody.innerHTML = html;

            // Pagination
            if (pagination) {
                var ph = '';
                for (var p = 1; p <= totalPages; p++) {
                    var cls = p === currentPage ? 'button current' : 'button';
                    ph += '<button class="' + cls + '" data-page="' + p + '">' + p + '</button>';
                }
                pagination.innerHTML = ph;
            }
        }

        // Events
        if (filterSel)  filterSel.addEventListener('change', function () { loadDrafts(1); });
        if (refreshBtn)  refreshBtn.addEventListener('click', function () { loadDrafts(currentPage); });
        if (pagination) {
            pagination.addEventListener('click', function (e) {
                if (e.target.dataset.page) loadDrafts(parseInt(e.target.dataset.page, 10));
            });
        }

        // Initial load
        loadDrafts(1);
    }

    /* ============================================================
     *  REVIEW PAGE
     * ============================================================ */
    var sectionsWrap = document.getElementById('almaseo-rd-sections');

    if (sectionsWrap) {
        var draftId    = sectionsWrap.dataset.draftId;
        var acceptAll  = document.getElementById('almaseo-rd-accept-all');
        var rejectAll  = document.getElementById('almaseo-rd-reject-all');
        var applyBtn   = document.getElementById('almaseo-rd-apply');
        var dismissBtn = document.getElementById('almaseo-rd-dismiss');

        if (acceptAll) {
            acceptAll.addEventListener('click', function () {
                sectionsWrap.querySelectorAll('.almaseo-rd-decision').forEach(function (cb) {
                    cb.checked = true;
                });
            });
        }

        if (rejectAll) {
            rejectAll.addEventListener('click', function () {
                sectionsWrap.querySelectorAll('.almaseo-rd-decision').forEach(function (cb) {
                    cb.checked = false;
                });
            });
        }

        if (applyBtn) {
            applyBtn.addEventListener('click', function () {
                var decisions = {};
                sectionsWrap.querySelectorAll('.almaseo-rd-section.changed').forEach(function (el) {
                    var key = el.dataset.key;
                    var cb  = el.querySelector('.almaseo-rd-decision');
                    decisions[key] = (cb && cb.checked) ? 'accept' : 'reject';
                });

                applyBtn.disabled = true;
                applyBtn.textContent = 'Applying\u2026';

                wp.apiFetch({
                    url:    cfg.restBase + '/' + draftId + '/review',
                    method: 'POST',
                    data:   { decisions: decisions },
                }).then(function () {
                    applyBtn.textContent = 'Applied!';
                    setTimeout(function () {
                        window.location.href = cfg.adminUrl;
                    }, 800);
                }).catch(function () {
                    applyBtn.disabled = false;
                    applyBtn.textContent = 'Apply selected changes';
                    alert('Something went wrong. Please try again.');
                });
            });
        }

        if (dismissBtn) {
            dismissBtn.addEventListener('click', function () {
                if (!confirm('Dismiss this draft? It will be marked as dismissed and no changes will be applied.')) return;

                wp.apiFetch({
                    url:    cfg.restBase + '/' + draftId,
                    method: 'DELETE',
                }).then(function () {
                    window.location.href = cfg.adminUrl;
                });
            });
        }
    }

    /* ─── Utility ─── */
    function esc(str) {
        var d = document.createElement('div');
        d.appendChild(document.createTextNode(str));
        return d.innerHTML;
    }
})();
