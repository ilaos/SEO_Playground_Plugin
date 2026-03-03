/**
 * Featured Snippet Targeting – Admin JavaScript
 *
 * Handles: loading stats/targets, filtering, pagination,
 * draft editing modal, approve/reject/apply/undo actions.
 *
 * @package AlmaSEO
 * @since   7.9.0
 */

/* global almaseoST, wp */
(function () {
    'use strict';

    if (typeof almaseoST === 'undefined') return;

    var cfg = almaseoST;

    /* ─── DOM refs ─── */
    var tbody        = document.getElementById('almaseo-st-tbody');
    var pagination   = document.getElementById('almaseo-st-pagination');
    var statusFilter = document.getElementById('almaseo-st-status-filter');
    var formatFilter = document.getElementById('almaseo-st-format-filter');
    var searchInput  = document.getElementById('almaseo-st-search');
    var table        = document.getElementById('almaseo-st-table');

    /* Stats */
    var statOpps    = document.getElementById('almaseo-st-opportunities');
    var statDrafts  = document.getElementById('almaseo-st-drafts');
    var statApplied = document.getElementById('almaseo-st-applied');
    var statWon     = document.getElementById('almaseo-st-won');

    /* Modal */
    var modal        = document.getElementById('almaseo-st-modal');
    var modalTitle   = document.getElementById('almaseo-st-modal-title');
    var modalQuery   = document.getElementById('almaseo-st-modal-query');
    var modalFormat  = document.getElementById('almaseo-st-modal-format');
    var modalPrompt  = document.getElementById('almaseo-st-modal-prompt');
    var draftEditor  = document.getElementById('almaseo-st-draft-editor');
    var draftPreview = document.getElementById('almaseo-st-draft-preview');
    var saveDraftBtn = document.getElementById('almaseo-st-save-draft');
    var modalClose   = document.getElementById('almaseo-st-modal-close');
    var modalCancel  = document.getElementById('almaseo-st-modal-cancel');
    var modalOverlay = modal ? modal.querySelector('.almaseo-st-modal-overlay') : null;

    var currentPage    = 1;
    var searchTimer    = null;
    var editingTargetId = null;

    if (!tbody) return;

    /* ============================================================
     *  FORMAT LABELS
     * ============================================================ */
    var FORMAT_LABELS = {
        paragraph:  'Paragraph',
        list:       'List',
        table:      'Table',
        definition: 'Definition'
    };

    /* ============================================================
     *  STATS
     * ============================================================ */
    function loadStats() {
        wp.apiFetch({ url: cfg.restBase + '/stats' }).then(function (data) {
            if (statOpps)    statOpps.textContent    = data.opportunity || 0;
            if (statDrafts)  statDrafts.textContent  = (data.draft || 0) + (data.approved || 0);
            if (statApplied) statApplied.textContent = data.applied || 0;
            if (statWon)     statWon.textContent     = data.won || 0;
        });
    }

    /* ============================================================
     *  TARGETS TABLE
     * ============================================================ */
    function loadTargets(page) {
        currentPage = page || 1;
        var status = statusFilter ? statusFilter.value : '';
        var format = formatFilter ? formatFilter.value : '';
        var search = searchInput ? searchInput.value.trim() : '';

        var url = cfg.restBase + '?page=' + currentPage + '&per_page=20';
        if (status) url += '&status=' + encodeURIComponent(status);
        if (format) url += '&snippet_format=' + encodeURIComponent(format);
        if (search) url += '&search=' + encodeURIComponent(search);

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

            var wrap = tbody.closest('.almaseo-st-wrap');
            var prev = wrap.querySelector('.almaseo-st-empty');
            if (prev) prev.remove();

            var empty = document.createElement('div');
            empty.className = 'almaseo-st-empty';
            empty.innerHTML =
                '<div class="almaseo-st-empty-icon"><span class="dashicons dashicons-lightbulb"></span></div>' +
                '<h3>No snippet targets</h3>' +
                '<p>' + esc(cfg.noTargets) + '</p>';
            wrap.appendChild(empty);

            if (pagination) pagination.innerHTML = '';
            return;
        }

        if (table) table.style.display = '';
        var existing = document.querySelector('.almaseo-st-empty');
        if (existing) existing.remove();

        var html = '';
        items.forEach(function (item) {
            html += '<tr>';

            // Query.
            html += '<td><span class="almaseo-st-query">' + esc(item.query) + '</span>';
            if (item.has_draft) {
                html += '<span class="almaseo-st-has-draft" title="Has draft"></span>';
            }
            html += '</td>';

            // Page.
            html += '<td>';
            if (item.post_edit_link) {
                html += '<a href="' + esc(item.post_edit_link) + '">' + esc(item.post_title) + '</a>';
            } else {
                html += esc(item.post_title || '(Unknown)');
            }
            html += '</td>';

            // Format.
            html += '<td><span class="almaseo-st-format-badge almaseo-st-format-' + esc(item.snippet_format) + '">' +
                    esc(FORMAT_LABELS[item.snippet_format] || item.snippet_format) + '</span></td>';

            // Position.
            html += '<td class="almaseo-st-col-num">' + (item.current_position || '—') + '</td>';

            // Volume.
            html += '<td class="almaseo-st-col-num">' + (item.search_volume ? item.search_volume.toLocaleString() : '—') + '</td>';

            // Status.
            html += '<td><span class="almaseo-st-status almaseo-st-status-' + esc(item.status) + '">' +
                    capitalize(item.status) + '</span></td>';

            // Actions.
            html += '<td>';
            html += '<button class="button button-small almaseo-st-edit-draft" data-id="' + item.id + '">Draft</button> ';

            if (item.status === 'draft' || item.status === 'opportunity') {
                html += '<button class="button button-small almaseo-st-approve" data-id="' + item.id + '">Approve</button> ';
            }
            if (item.status === 'approved' || item.status === 'draft') {
                html += '<button class="button button-small button-primary almaseo-st-apply" data-id="' + item.id + '">Apply</button> ';
            }
            if (item.status === 'applied') {
                html += '<button class="button button-small almaseo-st-undo" data-id="' + item.id + '">Undo</button> ';
            }
            if (item.status !== 'rejected' && item.status !== 'won' && item.status !== 'lost') {
                html += '<button class="button button-small almaseo-st-reject" data-id="' + item.id + '">Reject</button> ';
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
     *  ACTIONS
     * ============================================================ */
    function handleAction(id, action, method) {
        wp.apiFetch({
            url: cfg.restBase + '/' + id + '/' + action,
            method: method || 'PATCH',
        }).then(function () {
            loadStats();
            loadTargets(currentPage);
        }).catch(function (err) {
            alert((err && err.message) || 'Action failed.');
        });
    }

    /* ============================================================
     *  MODAL — Draft Editor
     * ============================================================ */
    function openModal(targetId) {
        editingTargetId = targetId;

        wp.apiFetch({ url: cfg.restBase + '/' + targetId }).then(function (data) {
            if (modalTitle) modalTitle.textContent = 'Edit Draft: ' + data.query;
            if (modalQuery) modalQuery.textContent = data.query;
            if (modalFormat) {
                modalFormat.textContent = FORMAT_LABELS[data.snippet_format] || data.snippet_format;
                modalFormat.className = 'almaseo-st-format-badge almaseo-st-format-' + data.snippet_format;
            }
            if (modalPrompt && data.prompt) modalPrompt.textContent = data.prompt;
            if (draftEditor) draftEditor.value = data.draft_content || '';
            updatePreview();
            if (modal) modal.style.display = '';
        });
    }

    function closeModal() {
        if (modal) modal.style.display = 'none';
        editingTargetId = null;
    }

    function updatePreview() {
        if (!draftEditor || !draftPreview) return;
        draftPreview.innerHTML = draftEditor.value || '<em>No content yet.</em>';
    }

    function saveDraft() {
        if (!editingTargetId || !draftEditor) return;

        wp.apiFetch({
            url: cfg.restBase + '/' + editingTargetId + '/draft',
            method: 'PATCH',
            data: { draft_content: draftEditor.value },
        }).then(function () {
            closeModal();
            loadStats();
            loadTargets(currentPage);
        }).catch(function (err) {
            alert((err && err.message) || 'Save failed.');
        });
    }

    /* ============================================================
     *  EVENT HANDLERS
     * ============================================================ */
    if (statusFilter) statusFilter.addEventListener('change', function () { loadTargets(1); });
    if (formatFilter) formatFilter.addEventListener('change', function () { loadTargets(1); });

    if (searchInput) {
        searchInput.addEventListener('input', function () {
            clearTimeout(searchTimer);
            searchTimer = setTimeout(function () { loadTargets(1); }, 400);
        });
    }

    if (pagination) {
        pagination.addEventListener('click', function (e) {
            if (e.target.dataset.page) loadTargets(parseInt(e.target.dataset.page, 10));
        });
    }

    if (tbody) {
        tbody.addEventListener('click', function (e) {
            var btn = e.target.closest('button');
            if (!btn) return;

            var id = btn.dataset.id;
            if (!id) return;

            if (btn.classList.contains('almaseo-st-edit-draft'))  { openModal(id); }
            if (btn.classList.contains('almaseo-st-approve'))     { handleAction(id, 'approve'); }
            if (btn.classList.contains('almaseo-st-reject'))      { handleAction(id, 'reject'); }
            if (btn.classList.contains('almaseo-st-apply'))       { handleAction(id, 'apply', 'POST'); }
            if (btn.classList.contains('almaseo-st-undo'))        { handleAction(id, 'undo', 'POST'); }
        });
    }

    // Modal events.
    if (modalClose)   modalClose.addEventListener('click', closeModal);
    if (modalCancel)  modalCancel.addEventListener('click', closeModal);
    if (modalOverlay) modalOverlay.addEventListener('click', closeModal);
    if (saveDraftBtn) saveDraftBtn.addEventListener('click', saveDraft);

    if (draftEditor) {
        draftEditor.addEventListener('input', updatePreview);
    }

    /* ============================================================
     *  INIT
     * ============================================================ */
    loadStats();
    loadTargets(1);

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
