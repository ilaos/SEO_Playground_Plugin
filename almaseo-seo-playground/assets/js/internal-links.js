/**
 * AlmaSEO Internal Links - Admin JavaScript
 *
 * Handles the Internal Links admin page:
 *   - CRUD operations via REST API
 *   - Table rendering, pagination, search
 *   - Bulk actions
 *   - Settings tab
 *   - Modal form
 *
 * @package AlmaSEO
 * @subpackage InternalLinks
 * @since 6.6.0
 */

(function ($) {
    'use strict';

    /* ================================================================
       State
       ================================================================ */

    var state = {
        page:    1,
        search:  '',
        orderby: 'priority',
        order:   'ASC',
        loading: false,
    };

    var config = window.almaseoInternalLinks || {};
    var apiUrl = config.apiUrl || '';
    var nonce  = config.nonce  || '';
    var strings = config.strings || {};

    /* ================================================================
       Helpers
       ================================================================ */

    function apiHeaders() {
        return { 'X-WP-Nonce': nonce };
    }

    function showNotice(message, type) {
        var $el = $('<div class="notice notice-' + (type || 'success') + ' is-dismissible"><p>' + message + '</p></div>');
        $('.almaseo-internal-links-wrap > h1').after($el);
        setTimeout(function () { $el.fadeOut(300, function () { $el.remove(); }); }, 4000);
    }

    function escHtml(str) {
        if (!str) return '';
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }

    /* ================================================================
       Stats
       ================================================================ */

    function loadStats() {
        $.ajax({
            url: apiUrl + '/stats',
            headers: apiHeaders(),
            method: 'GET',
            success: function (data) {
                $('#stat-total-rules').text(data.total_rules || 0);
                $('#stat-active-rules').text(data.active_rules || 0);
                $('#stat-total-hits').text(data.total_hits || 0);
                $('#stat-unique-targets').text(data.unique_targets || 0);
            },
        });
    }

    /* ================================================================
       Table Rendering
       ================================================================ */

    function loadRules() {
        if (state.loading) return;
        state.loading = true;

        var $tbody = $('#link-rules-list');
        $tbody.html('<tr><td colspan="8" class="loading-message">Loading...</td></tr>');

        $.ajax({
            url: apiUrl,
            headers: apiHeaders(),
            method: 'GET',
            data: {
                page:    state.page,
                per_page: 20,
                search:  state.search,
                orderby: state.orderby,
                order:   state.order,
            },
            success: function (data) {
                renderTable(data.items || []);
                renderPagination(data.total || 0, data.pages || 1);
                state.loading = false;
            },
            error: function () {
                $tbody.html('<tr><td colspan="8" class="no-items">Failed to load rules.</td></tr>');
                state.loading = false;
            },
        });
    }

    function renderTable(items) {
        var $tbody = $('#link-rules-list');
        $tbody.empty();

        if (!items.length) {
            $tbody.html('<tr><td colspan="8" class="no-items">No link rules yet. Click <strong>Add New Rule</strong> above to create your first one — pick a keyword and the page it should link to.</td></tr>');
            return;
        }

        $.each(items, function (i, rule) {
            var enabledIcon = parseInt(rule.is_enabled, 10)
                ? '<span class="almaseo-toggle enabled dashicons dashicons-yes-alt" title="Enabled"></span>'
                : '<span class="almaseo-toggle disabled dashicons dashicons-dismiss" title="Disabled"></span>';

            var matchClass = rule.match_type || 'exact';

            var row = '<tr data-id="' + rule.id + '">' +
                '<th scope="row" class="check-column"><input type="checkbox" class="rule-checkbox" value="' + rule.id + '" /></th>' +
                '<td class="column-keyword"><strong>' + escHtml(rule.keyword) + '</strong></td>' +
                '<td class="column-target"><span class="target-url-display"><a href="' + escHtml(rule.target_url) + '" target="_blank">' + escHtml(rule.target_url) + '</a></span></td>' +
                '<td class="column-match-type"><span class="match-badge ' + matchClass + '">' + matchClass + '</span></td>' +
                '<td class="column-priority">' + (rule.priority || 10) + '</td>' +
                '<td class="column-enabled"><span class="almaseo-toggle ' + (parseInt(rule.is_enabled, 10) ? 'enabled' : 'disabled') + '" data-id="' + rule.id + '" title="Click to toggle">' +
                    (parseInt(rule.is_enabled, 10) ? '&#10003;' : '&#10007;') +
                '</span></td>' +
                '<td class="column-hits">' + (rule.hits || 0) + '</td>' +
                '<td class="column-actions">' +
                    '<div class="row-actions">' +
                        '<button class="button button-small edit-rule" data-id="' + rule.id + '">Edit</button> ' +
                        '<button class="button button-small button-link-delete delete-rule" data-id="' + rule.id + '">Delete</button>' +
                    '</div>' +
                '</td>' +
            '</tr>';

            $tbody.append(row);
        });
    }

    function renderPagination(total, pages) {
        var $pag = $('#link-rules-pagination');
        $pag.empty();

        if (pages <= 1) return;

        $pag.append('<span class="pagination-info">' + total + ' items</span>');

        for (var p = 1; p <= pages; p++) {
            var cls = (p === state.page) ? 'button current-page' : 'button';
            $pag.append('<button class="' + cls + ' page-btn" data-page="' + p + '">' + p + '</button>');
        }
    }

    /* ================================================================
       Modal: Add / Edit
       ================================================================ */

    function openModal(rule) {
        var $modal = $('#link-rule-modal');
        var isEdit = rule && rule.id;

        $('#modal-title').text(isEdit ? 'Edit Link Rule' : 'Add New Link Rule');
        $('#rule-id').val(isEdit ? rule.id : '');
        $('#rule-keyword').val(isEdit ? rule.keyword : '');
        $('#rule-target-url').val(isEdit ? rule.target_url : '');
        $('input[name="match_type"][value="' + (isEdit ? rule.match_type : 'exact') + '"]').prop('checked', true);
        $('#rule-max-per-post').val(isEdit ? rule.max_per_post : 1);
        $('#rule-priority').val(isEdit ? rule.priority : 10);
        $('#rule-case-sensitive').prop('checked', isEdit ? parseInt(rule.case_sensitive, 10) : false);
        $('#rule-nofollow').prop('checked', isEdit ? parseInt(rule.nofollow, 10) : false);
        $('#rule-new-tab').prop('checked', isEdit ? parseInt(rule.new_tab, 10) : false);
        $('#rule-enabled').prop('checked', isEdit ? parseInt(rule.is_enabled, 10) : true);
        $('#rule-post-types').val(isEdit ? rule.post_types : 'post,page');
        $('#rule-exclude-ids').val(isEdit ? (rule.exclude_ids || '') : '');
        $('#form-errors').hide().empty();

        $modal.fadeIn(200);
        $('#rule-keyword').focus();
    }

    function closeModal() {
        $('#link-rule-modal').fadeOut(200);
    }

    function saveRule() {
        var id       = $('#rule-id').val();
        var keyword  = $('#rule-keyword').val().trim();
        var targetUrl = $('#rule-target-url').val().trim();

        if (!keyword) {
            showFormError(strings.missingKeyword || 'Keyword is required.');
            return;
        }
        if (!targetUrl) {
            showFormError(strings.missingTarget || 'Target URL is required.');
            return;
        }

        var data = {
            keyword:        keyword,
            target_url:     targetUrl,
            match_type:     $('input[name="match_type"]:checked').val(),
            max_per_post:   parseInt($('#rule-max-per-post').val(), 10) || 1,
            priority:       parseInt($('#rule-priority').val(), 10) || 10,
            case_sensitive: $('#rule-case-sensitive').is(':checked') ? 1 : 0,
            nofollow:       $('#rule-nofollow').is(':checked') ? 1 : 0,
            new_tab:        $('#rule-new-tab').is(':checked') ? 1 : 0,
            is_enabled:     $('#rule-enabled').is(':checked') ? 1 : 0,
            post_types:     $('#rule-post-types').val() || 'post,page',
            exclude_ids:    $('#rule-exclude-ids').val() || '',
        };

        var method = id ? 'PUT' : 'POST';
        var url    = id ? (apiUrl + '/' + id) : apiUrl;

        $.ajax({
            url: url,
            headers: apiHeaders(),
            method: method,
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function () {
                closeModal();
                showNotice(strings.success || 'Saved successfully.', 'success');
                loadRules();
                loadStats();
            },
            error: function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || strings.error || 'An error occurred.';
                showFormError(msg);
            },
        });
    }

    function showFormError(msg) {
        $('#form-errors').html(msg).show();
    }

    /* ================================================================
       Delete
       ================================================================ */

    function deleteRule(id) {
        if (!confirm(strings.confirmDelete || 'Delete this rule?')) return;

        $.ajax({
            url: apiUrl + '/' + id,
            headers: apiHeaders(),
            method: 'DELETE',
            success: function () {
                showNotice('Rule deleted.', 'success');
                loadRules();
                loadStats();
            },
            error: function () {
                showNotice(strings.error || 'Delete failed.', 'error');
            },
        });
    }

    /* ================================================================
       Toggle
       ================================================================ */

    function toggleRule(id) {
        $.ajax({
            url: apiUrl + '/' + id + '/toggle',
            headers: apiHeaders(),
            method: 'PATCH',
            success: function () {
                loadRules();
                loadStats();
            },
            error: function () {
                showNotice(strings.error || 'Toggle failed.', 'error');
            },
        });
    }

    /* ================================================================
       Bulk Actions
       ================================================================ */

    function doBulkAction() {
        var action = $('#bulk-action-selector').val();
        if (!action) return;

        var ids = [];
        $('.rule-checkbox:checked').each(function () {
            ids.push(parseInt($(this).val(), 10));
        });

        if (!ids.length) {
            showNotice('No rules selected.', 'warning');
            return;
        }

        if (action === 'delete' && !confirm(strings.confirmBulkDelete || 'Delete selected rules?')) {
            return;
        }

        $.ajax({
            url: apiUrl + '/bulk',
            headers: apiHeaders(),
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify({ action: action, ids: ids }),
            success: function (data) {
                showNotice(data.message || 'Done.', 'success');
                loadRules();
                loadStats();
            },
            error: function () {
                showNotice(strings.error || 'Bulk action failed.', 'error');
            },
        });
    }

    /* ================================================================
       Settings Tab
       ================================================================ */

    function loadSettings() {
        $.ajax({
            url: apiUrl + '/settings',
            headers: apiHeaders(),
            method: 'GET',
            success: function (data) {
                $('#setting-enabled').prop('checked', !!data.enabled);
                $('#setting-max-links').val(data.max_links_per_post || 10);
                $('#setting-skip-headings').prop('checked', !!data.skip_headings);
                $('#setting-skip-images').prop('checked', !!data.skip_images);
                $('#setting-skip-first-paragraph').prop('checked', !!data.skip_first_paragraph);
                $('#setting-exclude-ids').val(data.exclude_post_ids || '');
            },
        });
    }

    function saveSettings() {
        var data = {
            enabled:              $('#setting-enabled').is(':checked') ? 1 : 0,
            max_links_per_post:   parseInt($('#setting-max-links').val(), 10) || 10,
            skip_headings:        $('#setting-skip-headings').is(':checked') ? 1 : 0,
            skip_images:          $('#setting-skip-images').is(':checked') ? 1 : 0,
            skip_first_paragraph: $('#setting-skip-first-paragraph').is(':checked') ? 1 : 0,
            exclude_post_ids:     $('#setting-exclude-ids').val() || '',
        };

        var $notice = $('#settings-notice');
        $notice.hide();

        $.ajax({
            url: apiUrl + '/settings',
            headers: apiHeaders(),
            method: 'POST',
            contentType: 'application/json',
            data: JSON.stringify(data),
            success: function () {
                $notice.text(strings.saved || 'Settings saved.').removeClass('error').addClass('success').fadeIn();
                setTimeout(function () { $notice.fadeOut(); }, 3000);
            },
            error: function () {
                $notice.text(strings.error || 'Save failed.').removeClass('success').addClass('error').fadeIn();
            },
        });
    }

    /* ================================================================
       Tab Switching
       ================================================================ */

    function switchTab(tab) {
        $('.almaseo-il-tabs .nav-tab').removeClass('nav-tab-active');
        $('.almaseo-il-tabs .nav-tab[data-tab="' + tab + '"]').addClass('nav-tab-active');
        $('.almaseo-il-tab-content').hide();
        $('#tab-' + tab).show();
    }

    /* ================================================================
       Event Bindings
       ================================================================ */

    $(document).ready(function () {
        // Initial data load
        loadRules();
        loadStats();
        loadSettings();

        // Tabs
        $('.almaseo-il-tabs .nav-tab').on('click', function (e) {
            e.preventDefault();
            switchTab($(this).data('tab'));
        });

        // Add New
        $('#add-new-link-rule').on('click', function () { openModal(null); });

        // Close modal
        $(document).on('click', '.almaseo-modal-close, #cancel-rule', function () { closeModal(); });
        $(document).on('click', '.almaseo-modal', function (e) {
            if ($(e.target).hasClass('almaseo-modal')) closeModal();
        });

        // Save rule
        $('#save-rule').on('click', saveRule);

        // Edit rule
        $(document).on('click', '.edit-rule', function () {
            var id = $(this).data('id');
            $.ajax({
                url: apiUrl + '/' + id,
                headers: apiHeaders(),
                method: 'GET',
                success: function (rule) { openModal(rule); },
                error: function () { showNotice(strings.error, 'error'); },
            });
        });

        // Delete rule
        $(document).on('click', '.delete-rule', function () { deleteRule($(this).data('id')); });

        // Toggle rule
        $(document).on('click', '.almaseo-toggle', function () {
            var id = $(this).data('id');
            if (id) toggleRule(id);
        });

        // Select all
        $('#select-all-rules').on('change', function () {
            $('.rule-checkbox').prop('checked', $(this).is(':checked'));
        });

        // Bulk action
        $('#do-bulk-action').on('click', doBulkAction);

        // Search
        $('#search-links').on('click', function () {
            state.search = $('#link-search').val();
            state.page = 1;
            loadRules();
        });
        $('#link-search').on('keypress', function (e) {
            if (e.which === 13) {
                e.preventDefault();
                state.search = $(this).val();
                state.page = 1;
                loadRules();
            }
        });

        // Pagination
        $(document).on('click', '.page-btn', function () {
            state.page = parseInt($(this).data('page'), 10);
            loadRules();
        });

        // Column sort
        $(document).on('click', '#link-rules-table th.sortable', function () {
            var col = $(this).data('orderby');
            if (state.orderby === col) {
                state.order = (state.order === 'ASC') ? 'DESC' : 'ASC';
            } else {
                state.orderby = col;
                state.order = 'ASC';
            }
            state.page = 1;
            loadRules();
        });

        // Save settings
        $('#save-settings').on('click', saveSettings);

        // Escape key closes modal
        $(document).on('keyup', function (e) {
            if (e.key === 'Escape') closeModal();
        });
    });

})(jQuery);
