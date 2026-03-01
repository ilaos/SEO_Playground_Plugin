/**
 * AlmaSEO Redirects JavaScript
 * @since 6.1.0
 */

(function($) {
    'use strict';
    
    // State management
    let currentPage = 1;
    let totalPages = 1;
    let searchTerm = '';
    let selectedIds = [];
    
    // Initialize on document ready
    $(document).ready(function() {
        loadRedirects();
        bindEvents();
        updateStats();
    });
    
    /**
     * Bind event handlers
     */
    function bindEvents() {
        // Add new redirect
        $('#add-new-redirect').on('click', function() {
            openModal('add');
        });
        
        // Save redirect
        $('#save-redirect').on('click', saveRedirect);
        
        // Cancel/Close modal
        $('#cancel-redirect, .almaseo-modal-close').on('click', closeModal);
        
        // Search
        $('#search-redirects').on('click', function() {
            searchTerm = $('#redirect-search').val();
            currentPage = 1;
            loadRedirects();
        });
        
        $('#redirect-search').on('keypress', function(e) {
            if (e.which === 13) {
                $('#search-redirects').click();
                return false;
            }
        });
        
        // Bulk actions
        $('#do-bulk-action').on('click', doBulkAction);
        
        // Select all checkbox
        $('#select-all-redirects').on('change', function() {
            $('.redirect-checkbox').prop('checked', $(this).prop('checked'));
            updateSelectedIds();
        });
        
        // Test redirect
        $('#run-test').on('click', testRedirect);
        
        // Form validation
        $('#redirect-source').on('blur', function() {
            validateSource($(this).val());
        });
        
        $('#redirect-target').on('blur', function() {
            validateTarget($(this).val());
        });
    }
    
    /**
     * Load redirects from API
     */
    function loadRedirects() {
        const $tbody = $('#redirects-list');
        $tbody.html('<tr><td colspan="8" class="loading-message"><span class="spinner"></span>Loading...</td></tr>');
        
        $.ajax({
            url: almaseoRedirects.apiUrl,
            method: 'GET',
            data: {
                page: currentPage,
                per_page: 20,
                search: searchTerm
            },
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', almaseoRedirects.nonce);
            },
            success: function(response) {
                renderRedirects(response);
                renderPagination(response);
            },
            error: function(xhr) {
                $tbody.html('<tr><td colspan="8" class="error-message">' + almaseoRedirects.strings.error + '</td></tr>');
            }
        });
    }
    
    /**
     * Render redirects table
     */
    function renderRedirects(data) {
        const $tbody = $('#redirects-list');
        
        if (!data.items || data.items.length === 0) {
            $tbody.html('<tr><td colspan="8">No redirects found.</td></tr>');
            return;
        }
        
        let html = '';
        
        data.items.forEach(function(redirect) {
            const enabledIcon = redirect.is_enabled == 1 
                ? '<span class="dashicons dashicons-yes enabled-yes"></span>' 
                : '<span class="dashicons dashicons-no enabled-no"></span>';
            
            const statusBadge = '<span class="status-badge status-' + redirect.status + '">' + redirect.status + '</span>';
            
            const lastHit = redirect.last_hit ? formatDate(redirect.last_hit) : 'Never';
            
            html += '<tr data-id="' + redirect.id + '">';
            html += '<th scope="row" class="check-column"><input type="checkbox" class="redirect-checkbox" value="' + redirect.id + '" /></th>';
            html += '<td class="column-source">' + escapeHtml(redirect.source) + '</td>';
            html += '<td class="column-target">' + escapeHtml(redirect.target) + '</td>';
            html += '<td class="column-status">' + statusBadge + '</td>';
            html += '<td class="column-enabled"><button class="enabled-toggle button-link" data-id="' + redirect.id + '">' + enabledIcon + '</button></td>';
            html += '<td class="column-hits">' + redirect.hits + '</td>';
            html += '<td class="column-last-hit">' + lastHit + '</td>';
            html += '<td class="column-actions">';
            html += '<div class="redirect-actions">';
            html += '<button class="button button-small edit-redirect" data-id="' + redirect.id + '">Edit</button> ';
            html += '<button class="button button-small test-redirect" data-source="' + escapeHtml(redirect.source) + '">Test</button> ';
            html += '<button class="button button-small button-link-delete delete-redirect" data-id="' + redirect.id + '">Delete</button>';
            html += '</div>';
            html += '</td>';
            html += '</tr>';
        });
        
        $tbody.html(html);
        
        // Bind row-level events
        bindRowEvents();
    }
    
    /**
     * Bind events for table rows
     */
    function bindRowEvents() {
        // Edit redirect
        $('.edit-redirect').on('click', function() {
            const id = $(this).data('id');
            loadRedirectForEdit(id);
        });
        
        // Delete redirect
        $('.delete-redirect').on('click', function() {
            const id = $(this).data('id');
            if (confirm(almaseoRedirects.strings.confirmDelete)) {
                deleteRedirect(id);
            }
        });
        
        // Toggle enabled
        $('.enabled-toggle').on('click', function() {
            const id = $(this).data('id');
            toggleRedirect(id);
        });
        
        // Test redirect
        $('.test-redirect').on('click', function() {
            const source = $(this).data('source');
            $('#test-path').val(source);
            $('#test-modal').show();
        });
        
        // Checkbox selection
        $('.redirect-checkbox').on('change', updateSelectedIds);
    }
    
    /**
     * Render pagination
     */
    function renderPagination(data) {
        const $pagination = $('#redirects-pagination');
        
        if (data.pages <= 1) {
            $pagination.html('');
            return;
        }
        
        totalPages = data.pages;
        
        let html = '<span class="displaying-num">' + data.total + ' items</span>';
        html += '<span class="pagination-links">';
        
        // Previous button
        if (currentPage > 1) {
            html += '<a class="prev-page button" href="#" data-page="' + (currentPage - 1) + '">‹</a>';
        } else {
            html += '<span class="prev-page button disabled">‹</span>';
        }
        
        // Page input
        html += ' <span class="paging-input">';
        html += '<input class="current-page" type="text" value="' + currentPage + '" size="2" /> of ';
        html += '<span class="total-pages">' + totalPages + '</span>';
        html += '</span> ';
        
        // Next button
        if (currentPage < totalPages) {
            html += '<a class="next-page button" href="#" data-page="' + (currentPage + 1) + '">›</a>';
        } else {
            html += '<span class="next-page button disabled">›</span>';
        }
        
        html += '</span>';
        
        $pagination.html(html);
        
        // Bind pagination events
        $('.prev-page:not(.disabled), .next-page:not(.disabled)').on('click', function(e) {
            e.preventDefault();
            currentPage = $(this).data('page');
            loadRedirects();
        });
        
        $('.current-page').on('keypress', function(e) {
            if (e.which === 13) {
                const page = parseInt($(this).val());
                if (page > 0 && page <= totalPages) {
                    currentPage = page;
                    loadRedirects();
                }
                return false;
            }
        });
    }
    
    /**
     * Open modal for add/edit
     */
    function openModal(mode, redirect) {
        const $modal = $('#redirect-modal');
        const $title = $('#modal-title');
        const $form = $('#redirect-form')[0];
        
        // Reset form
        $form.reset();
        $('#redirect-id').val('');
        $('#form-errors').hide().html('');
        
        if (mode === 'edit' && redirect) {
            $title.text('Edit Redirect');
            $('#redirect-id').val(redirect.id);
            $('#redirect-source').val(redirect.source);
            $('#redirect-target').val(redirect.target);
            $('input[name="status"][value="' + redirect.status + '"]').prop('checked', true);
            $('#redirect-enabled').prop('checked', redirect.is_enabled == 1);
        } else {
            $title.text('Add New Redirect');
            $('#redirect-enabled').prop('checked', true);
        }
        
        $modal.show();
    }
    
    /**
     * Close modal
     */
    function closeModal() {
        $('.almaseo-modal').hide();
    }
    
    /**
     * Save redirect (add or update)
     */
    function saveRedirect() {
        const id = $('#redirect-id').val();
        const data = {
            source: $('#redirect-source').val().trim(),
            target: $('#redirect-target').val().trim(),
            status: $('input[name="status"]:checked').val(),
            is_enabled: $('#redirect-enabled').is(':checked') ? 1 : 0
        };
        
        // Client-side validation
        const sourceError = validateSource(data.source);
        const targetError = validateTarget(data.target);
        
        if (sourceError || targetError) {
            showFormErrors([sourceError, targetError].filter(Boolean));
            return;
        }
        
        const method = id ? 'PUT' : 'POST';
        const url = id ? almaseoRedirects.apiUrl + '/' + id : almaseoRedirects.apiUrl;
        
        $.ajax({
            url: url,
            method: method,
            data: JSON.stringify(data),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', almaseoRedirects.nonce);
            },
            success: function(response) {
                closeModal();
                loadRedirects();
                updateStats();
                showNotice('success', almaseoRedirects.strings.success);
            },
            error: function(xhr) {
                const response = xhr.responseJSON;
                if (response && response.message) {
                    showFormErrors([response.message]);
                } else {
                    showFormErrors([almaseoRedirects.strings.error]);
                }
            }
        });
    }
    
    /**
     * Load redirect for editing
     */
    function loadRedirectForEdit(id) {
        $.ajax({
            url: almaseoRedirects.apiUrl + '/' + id,
            method: 'GET',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', almaseoRedirects.nonce);
            },
            success: function(redirect) {
                openModal('edit', redirect);
            },
            error: function(xhr) {
                showNotice('error', almaseoRedirects.strings.error);
            }
        });
    }
    
    /**
     * Delete redirect
     */
    function deleteRedirect(id) {
        $.ajax({
            url: almaseoRedirects.apiUrl + '/' + id,
            method: 'DELETE',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', almaseoRedirects.nonce);
            },
            success: function(response) {
                loadRedirects();
                updateStats();
                showNotice('success', response.message || almaseoRedirects.strings.success);
            },
            error: function(xhr) {
                showNotice('error', almaseoRedirects.strings.error);
            }
        });
    }
    
    /**
     * Toggle redirect enabled status
     */
    function toggleRedirect(id) {
        $.ajax({
            url: almaseoRedirects.apiUrl + '/' + id + '/toggle',
            method: 'PATCH',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', almaseoRedirects.nonce);
            },
            success: function(response) {
                loadRedirects();
                updateStats();
            },
            error: function(xhr) {
                showNotice('error', almaseoRedirects.strings.error);
            }
        });
    }
    
    /**
     * Do bulk action
     */
    function doBulkAction() {
        const action = $('#bulk-action-selector').val();
        
        if (!action) {
            return;
        }
        
        updateSelectedIds();
        
        if (selectedIds.length === 0) {
            showNotice('error', 'No redirects selected.');
            return;
        }
        
        if (action === 'delete' && !confirm(almaseoRedirects.strings.confirmBulkDelete)) {
            return;
        }
        
        $.ajax({
            url: almaseoRedirects.apiUrl + '/bulk',
            method: 'POST',
            data: JSON.stringify({
                action: action,
                ids: selectedIds
            }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', almaseoRedirects.nonce);
            },
            success: function(response) {
                loadRedirects();
                updateStats();
                showNotice('success', response.message);
                selectedIds = [];
                $('#select-all-redirects').prop('checked', false);
            },
            error: function(xhr) {
                showNotice('error', almaseoRedirects.strings.error);
            }
        });
    }
    
    /**
     * Test redirect
     */
    function testRedirect() {
        const path = $('#test-path').val().trim();
        
        if (!path) {
            return;
        }
        
        $.ajax({
            url: almaseoRedirects.apiUrl + '/test',
            method: 'POST',
            data: JSON.stringify({ source: path }),
            contentType: 'application/json',
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', almaseoRedirects.nonce);
            },
            success: function(response) {
                const $result = $('#test-result');
                
                if (response.found) {
                    $result.removeClass('error info').addClass('success');
                    $result.html('<strong>Match found!</strong><br>' + response.message);
                } else {
                    $result.removeClass('success error').addClass('info');
                    $result.html(response.message);
                }
                
                $result.show();
            },
            error: function(xhr) {
                $('#test-result')
                    .removeClass('success info')
                    .addClass('error')
                    .html(almaseoRedirects.strings.error)
                    .show();
            }
        });
    }
    
    /**
     * Update statistics
     */
    function updateStats() {
        $.ajax({
            url: almaseoRedirects.apiUrl,
            method: 'GET',
            data: { per_page: 1000 }, // Get all for stats
            beforeSend: function(xhr) {
                xhr.setRequestHeader('X-WP-Nonce', almaseoRedirects.nonce);
            },
            success: function(response) {
                if (response.items) {
                    const total = response.items.length;
                    const active = response.items.filter(r => r.is_enabled == 1).length;
                    const totalHits = response.items.reduce((sum, r) => sum + parseInt(r.hits), 0);
                    
                    // Calculate today's hits (would need last_hit date comparison)
                    const today = new Date().toISOString().split('T')[0];
                    const todayHits = response.items.filter(r => 
                        r.last_hit && r.last_hit.startsWith(today)
                    ).length;
                    
                    $('#total-redirects').text(total);
                    $('#active-redirects').text(active);
                    $('#total-hits').text(totalHits);
                    $('#redirects-today').text(todayHits);
                }
            }
        });
    }
    
    /**
     * Update selected IDs
     */
    function updateSelectedIds() {
        selectedIds = [];
        $('.redirect-checkbox:checked').each(function() {
            selectedIds.push($(this).val());
        });
    }
    
    /**
     * Validate source path
     */
    function validateSource(source) {
        if (!source) {
            return almaseoRedirects.strings.invalidSource;
        }
        
        if (!source.startsWith('/')) {
            return almaseoRedirects.strings.invalidSource;
        }
        
        return null;
    }
    
    /**
     * Validate target URL/path
     */
    function validateTarget(target) {
        if (!target) {
            return almaseoRedirects.strings.invalidTarget;
        }
        
        // Check if it's a valid URL
        if (target.startsWith('http://') || target.startsWith('https://')) {
            try {
                new URL(target);
                return null;
            } catch (e) {
                return almaseoRedirects.strings.invalidTarget;
            }
        }
        
        // Otherwise must be a path starting with /
        if (!target.startsWith('/')) {
            return almaseoRedirects.strings.invalidTarget;
        }
        
        return null;
    }
    
    /**
     * Show form errors
     */
    function showFormErrors(errors) {
        const $errors = $('#form-errors');
        let html = '<ul>';
        
        errors.forEach(function(error) {
            html += '<li>' + escapeHtml(error) + '</li>';
        });
        
        html += '</ul>';
        
        $errors.html(html).show();
    }
    
    /**
     * Show notice
     */
    function showNotice(type, message) {
        const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
        $('.wrap').prepend($notice);
        
        setTimeout(function() {
            $notice.fadeOut(function() {
                $(this).remove();
            });
        }, 5000);
    }
    
    /**
     * Format date
     */
    function formatDate(dateString) {
        const date = new Date(dateString);
        const now = new Date();
        const diff = Math.floor((now - date) / 1000); // seconds
        
        if (diff < 60) {
            return 'Just now';
        } else if (diff < 3600) {
            return Math.floor(diff / 60) + ' min ago';
        } else if (diff < 86400) {
            return Math.floor(diff / 3600) + ' hours ago';
        } else if (diff < 604800) {
            return Math.floor(diff / 86400) + ' days ago';
        } else {
            return date.toLocaleDateString();
        }
    }
    
    /**
     * Escape HTML
     */
    function escapeHtml(text) {
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) {
            return map[m];
        });
    }
    
})(jQuery);