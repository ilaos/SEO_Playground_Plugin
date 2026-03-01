/**
 * AlmaSEO 404 Logs - JavaScript
 * 
 * @package AlmaSEO
 * @since 6.2.0
 */

jQuery(function($) {
    'use strict';
    
    var currentPage = 1;
    var loading = false;
    
    /**
     * Load logs with filters
     */
    function loadLogs(page) {
        if (loading) return;
        loading = true;
        
        $('#404-loading').show();
        
        var params = {
            page: page || 1,
            per_page: 20,
            search: $('#search-input').val(),
            ignored: $('#ignored-filter').val(),
            from: $('#date-from').val(),
            to: $('#date-to').val()
        };
        
        // Remove empty params
        Object.keys(params).forEach(key => {
            if (params[key] === '' || params[key] === null) {
                delete params[key];
            }
        });
        
        $.ajax({
            url: almaseo404.apiUrl,
            method: 'GET',
            data: params,
            headers: {
                'X-WP-Nonce': almaseo404.nonce
            },
            success: function(response) {
                renderTable(response.items);
                renderPagination(response.pages, page || 1);
                currentPage = page || 1;
            },
            error: function() {
                alert(almaseo404.strings.error);
            },
            complete: function() {
                $('#404-loading').hide();
                loading = false;
            }
        });
    }
    
    /**
     * Render table rows
     */
    function renderTable(items) {
        var tbody = $('#404-logs-tbody');
        tbody.empty();
        
        if (items.length === 0) {
            tbody.append('<tr><td colspan="8" class="no-items">No 404 errors found.</td></tr>');
            return;
        }
        
        items.forEach(function(log) {
            var row = $('<tr>').attr('data-id', log.id).addClass(log.is_ignored == 1 ? 'ignored' : '');
            
            // Checkbox
            row.append('<th scope="row" class="check-column"><input type="checkbox" class="log-checkbox" value="' + log.id + '" /></th>');
            
            // Path
            var pathCell = '<td class="column-path"><strong>' + escapeHtml(log.path) + '</strong>';
            if (log.is_ignored == 1) {
                pathCell += ' <span class="ignored-badge">Ignored</span>';
            }
            pathCell += '</td>';
            row.append(pathCell);
            
            // Query
            var queryCell = '<td class="column-query">';
            if (log.query) {
                var displayQuery = log.query.length > 50 ? log.query.substr(0, 50) + '...' : log.query;
                queryCell += '<code>' + escapeHtml(displayQuery) + '</code>';
            } else {
                queryCell += '<span class="no-data">—</span>';
            }
            queryCell += '</td>';
            row.append(queryCell);
            
            // Hits
            row.append('<td class="column-hits"><span class="hit-count">' + formatNumber(log.hits) + '</span></td>');
            
            // Last seen
            row.append('<td class="column-last-seen">' + formatDate(log.last_seen) + '</td>');
            
            // Referrer
            var referrerCell = '<td class="column-referrer">';
            if (log.referrer) {
                referrerCell += '<span title="' + escapeHtml(log.referrer) + '">' + escapeHtml(log.referrer_domain || 'Unknown') + '</span>';
            } else {
                referrerCell += '<span class="no-data">Direct</span>';
            }
            referrerCell += '</td>';
            row.append(referrerCell);
            
            // User Agent
            var uaCell = '<td class="column-user-agent">';
            if (log.user_agent) {
                uaCell += '<span title="' + escapeHtml(log.user_agent) + '">' + escapeHtml(log.user_agent_display || log.user_agent.substr(0, 50)) + '</span>';
            } else {
                uaCell += '<span class="no-data">—</span>';
            }
            uaCell += '</td>';
            row.append(uaCell);
            
            // Actions
            var actionsCell = '<td class="column-actions">';
            actionsCell += '<button type="button" class="button button-small create-redirect" data-id="' + log.id + '">Create Redirect</button> ';
            
            if (log.is_ignored == 1) {
                actionsCell += '<button type="button" class="button button-small unignore-log" data-id="' + log.id + '">Unignore</button> ';
            } else {
                actionsCell += '<button type="button" class="button button-small ignore-log" data-id="' + log.id + '">Ignore</button> ';
            }
            
            actionsCell += '<button type="button" class="button button-small button-link-delete delete-log" data-id="' + log.id + '">Delete</button>';
            actionsCell += '</td>';
            row.append(actionsCell);
            
            tbody.append(row);
        });
    }
    
    /**
     * Render pagination
     */
    function renderPagination(totalPages, currentPage) {
        var container = $('#pagination-links');
        container.empty();
        
        if (totalPages <= 1) return;
        
        // Previous
        if (currentPage > 1) {
            container.append('<a class="prev-page button" href="#" data-page="' + (currentPage - 1) + '">‹</a> ');
        }
        
        // Page numbers
        var startPage = Math.max(1, currentPage - 2);
        var endPage = Math.min(totalPages, currentPage + 2);
        
        if (startPage > 1) {
            container.append('<a class="button" href="#" data-page="1">1</a> ');
            if (startPage > 2) {
                container.append('<span class="dots">...</span> ');
            }
        }
        
        for (var i = startPage; i <= endPage; i++) {
            if (i === currentPage) {
                container.append('<span class="button current">' + i + '</span> ');
            } else {
                container.append('<a class="button" href="#" data-page="' + i + '">' + i + '</a> ');
            }
        }
        
        if (endPage < totalPages) {
            if (endPage < totalPages - 1) {
                container.append('<span class="dots">...</span> ');
            }
            container.append('<a class="button" href="#" data-page="' + totalPages + '">' + totalPages + '</a> ');
        }
        
        // Next
        if (currentPage < totalPages) {
            container.append('<a class="next-page button" href="#" data-page="' + (currentPage + 1) + '">›</a>');
        }
    }
    
    /**
     * Create redirect
     */
    function createRedirect(id) {
        $.ajax({
            url: almaseo404.apiUrl + '/' + id + '/to-redirect',
            method: 'POST',
            headers: {
                'X-WP-Nonce': almaseo404.nonce
            },
            success: function(data) {
                // Redirect to Redirect Manager with prefilled data
                var url = almaseo404.redirectUrl + '&action=add&source=' + encodeURIComponent(data.source);
                window.location.href = url;
            },
            error: function() {
                alert(almaseo404.strings.error);
            }
        });
    }
    
    /**
     * Toggle ignore status
     */
    function toggleIgnore(id, ignore) {
        var action = ignore ? 'ignore' : 'unignore';
        
        $.ajax({
            url: almaseo404.apiUrl + '/' + id + '/' + action,
            method: 'POST',
            headers: {
                'X-WP-Nonce': almaseo404.nonce
            },
            success: function() {
                loadLogs(currentPage);
            },
            error: function() {
                alert(almaseo404.strings.error);
            }
        });
    }
    
    /**
     * Delete log
     */
    function deleteLog(id) {
        if (!confirm(almaseo404.strings.confirmDelete)) {
            return;
        }
        
        $.ajax({
            url: almaseo404.apiUrl + '/' + id,
            method: 'DELETE',
            headers: {
                'X-WP-Nonce': almaseo404.nonce
            },
            success: function() {
                loadLogs(currentPage);
            },
            error: function() {
                alert(almaseo404.strings.error);
            }
        });
    }
    
    /**
     * Bulk action
     */
    function performBulkAction() {
        var action = $('#bulk-action-selector').val();
        if (!action) {
            alert('Please select an action');
            return;
        }
        
        var ids = [];
        $('.log-checkbox:checked').each(function() {
            ids.push($(this).val());
        });
        
        if (ids.length === 0) {
            alert(almaseo404.strings.noSelection);
            return;
        }
        
        if (action === 'delete' && !confirm(almaseo404.strings.confirmBulkDelete)) {
            return;
        }
        
        $.ajax({
            url: almaseo404.apiUrl + '/bulk',
            method: 'POST',
            headers: {
                'X-WP-Nonce': almaseo404.nonce
            },
            data: JSON.stringify({
                action: action,
                ids: ids
            }),
            contentType: 'application/json',
            success: function() {
                loadLogs(currentPage);
                $('#select-all, #select-all-bottom').prop('checked', false);
            },
            error: function() {
                alert(almaseo404.strings.error);
            }
        });
    }
    
    /**
     * Utility: Escape HTML
     */
    function escapeHtml(text) {
        if (!text) return '';
        var map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }
    
    /**
     * Utility: Format number
     */
    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
    }
    
    /**
     * Utility: Format date
     */
    function formatDate(dateStr) {
        var date = new Date(dateStr);
        var now = new Date();
        var diff = Math.floor((now - date) / 1000);
        
        if (diff < 60) return 'Just now';
        if (diff < 3600) return Math.floor(diff / 60) + ' minutes ago';
        if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
        if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';
        
        return date.toLocaleDateString() + '<br><small>' + dateStr + '</small>';
    }
    
    // Event handlers
    $('#apply-filters').on('click', function() {
        loadLogs(1);
    });
    
    $('#clear-filters').on('click', function() {
        $('#search-input').val('');
        $('#ignored-filter').val('');
        $('#date-from').val('');
        $('#date-to').val('');
        loadLogs(1);
    });
    
    $('#do-bulk-action').on('click', performBulkAction);
    
    // Select all checkboxes
    $('#select-all, #select-all-bottom').on('change', function() {
        var checked = $(this).prop('checked');
        $('.log-checkbox').prop('checked', checked);
        $('#select-all, #select-all-bottom').prop('checked', checked);
    });
    
    // Pagination clicks
    $(document).on('click', '#pagination-links a', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        loadLogs(page);
    });
    
    // Row actions
    $(document).on('click', '.create-redirect', function() {
        createRedirect($(this).data('id'));
    });
    
    $(document).on('click', '.ignore-log', function() {
        toggleIgnore($(this).data('id'), true);
    });
    
    $(document).on('click', '.unignore-log', function() {
        toggleIgnore($(this).data('id'), false);
    });
    
    $(document).on('click', '.delete-log', function() {
        deleteLog($(this).data('id'));
    });
    
    // Enter key on search
    $('#search-input').on('keypress', function(e) {
        if (e.which === 13) {
            loadLogs(1);
        }
    });
});