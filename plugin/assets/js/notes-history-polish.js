/**
 * AlmaSEO Notes & History Tab Polish JavaScript
 * Version: 1.0.0
 * Description: Enhanced functionality for Notes & History tab with accessibility support
 */

(function($) {
    'use strict';

    // Notes storage
    let seoNotes = [];
    let filteredNotes = [];
    let currentEditingNote = null;

    // Wait for DOM ready
    $(document).ready(function() {
        // Initialize Notes & History when tab is shown
        initializeNotesHistory();
        
        // Re-initialize when tab is clicked
        $(document).on('click', '.almaseo-tab-btn[data-tab="notes-history"]', function() {
            setTimeout(initializeNotesHistory, 100);
        });
    });

    /**
     * Initialize all Notes & History functionality
     */
    function initializeNotesHistory() {
        initializeSEONotes();
        initializePostHistory();
        initializeSearch();
        initializeSorting();
        initializeAccessibility();
        loadNotes();
        loadPostHistory();
    }

    /**
     * SEO Notes Panel
     */
    function initializeSEONotes() {
        const $panel = $('.seo-notes-panel');
        if (!$panel.length) return;

        const $textarea = $('#seo-note-textarea');
        const $addBtn = $('#add-note-btn');
        const $clearBtn = $('#clear-note-btn');
        const $charCount = $('#note-char-count');
        const $charCounter = $('.note-char-count');
        const maxChars = 1000;

        // Character counter
        $textarea.on('input', function() {
            const length = $(this).val().length;
            $charCount.text(length);
            
            // Update counter color
            $charCounter.removeClass('warning error');
            if (length > maxChars) {
                $charCounter.addClass('error');
            } else if (length > maxChars * 0.9) {
                $charCounter.addClass('warning');
            }
            
            // Enable/disable add button
            $addBtn.prop('disabled', length === 0 || length > maxChars);
        });

        // Add note button
        $addBtn.on('click', function() {
            const noteText = $textarea.val().trim();
            if (noteText && noteText.length <= maxChars) {
                if (currentEditingNote) {
                    updateNote(currentEditingNote, noteText);
                } else {
                    addNote(noteText);
                }
            }
        });

        // Clear button
        $clearBtn.on('click', function() {
            $textarea.val('').trigger('input');
            currentEditingNote = null;
            $addBtn.html('<span aria-hidden="true">➕</span> Add Note');
        });

        // Note item interactions
        $(document).on('click', '.note-item-header', function(e) {
            if (!$(e.target).hasClass('note-action-btn')) {
                const $item = $(this).closest('.note-item');
                $item.toggleClass('expanded');
                
                // Announce state change
                const isExpanded = $item.hasClass('expanded');
                announceToScreenReader(isExpanded ? 'Note expanded' : 'Note collapsed');
            }
        });

        // Edit note button
        $(document).on('click', '.note-edit-btn', function(e) {
            e.stopPropagation();
            const $item = $(this).closest('.note-item');
            const noteId = $item.data('note-id');
            const note = seoNotes.find(n => n.id === noteId);
            
            if (note) {
                $textarea.val(note.text).trigger('input');
                currentEditingNote = noteId;
                $addBtn.html('<span aria-hidden="true">✏️</span> Update Note');
                
                // Scroll to textarea
                $('html, body').animate({
                    scrollTop: $textarea.offset().top - 100
                }, 500);
                
                $textarea.focus();
            }
        });

        // Delete note button
        $(document).on('click', '.note-delete-btn', function(e) {
            e.stopPropagation();
            if (confirm('Are you sure you want to delete this note?')) {
                const $item = $(this).closest('.note-item');
                const noteId = $item.data('note-id');
                deleteNote(noteId);
            }
        });

        // Enter key to submit
        $textarea.on('keydown', function(e) {
            if (e.ctrlKey && e.key === 'Enter') {
                $addBtn.click();
            }
        });
    }

    /**
     * Add a new note
     */
    function addNote(text) {
        const note = {
            id: 'note_' + Date.now(),
            text: text,
            timestamp: new Date().toISOString(),
            author: getCurrentUser(),
            postId: getPostId()
        };

        seoNotes.unshift(note);
        saveNotes();
        renderNotes();
        
        // Clear textarea
        $('#seo-note-textarea').val('').trigger('input');
        
        // Show success message
        showMessage('success', 'Note added successfully');
        
        // Announce to screen readers
        announceToScreenReader('Note has been added');
    }

    /**
     * Update existing note
     */
    function updateNote(noteId, text) {
        const noteIndex = seoNotes.findIndex(n => n.id === noteId);
        if (noteIndex !== -1) {
            seoNotes[noteIndex].text = text;
            seoNotes[noteIndex].timestamp = new Date().toISOString();
            seoNotes[noteIndex].edited = true;
            
            saveNotes();
            renderNotes();
            
            // Reset editing state
            currentEditingNote = null;
            $('#seo-note-textarea').val('').trigger('input');
            $('#add-note-btn').html('<span aria-hidden="true">➕</span> Add Note');
            
            // Show success message
            showMessage('success', 'Note updated successfully');
            
            // Announce to screen readers
            announceToScreenReader('Note has been updated');
        }
    }

    /**
     * Delete a note
     */
    function deleteNote(noteId) {
        seoNotes = seoNotes.filter(n => n.id !== noteId);
        saveNotes();
        renderNotes();
        
        // Show success message
        showMessage('success', 'Note deleted successfully');
        
        // Announce to screen readers
        announceToScreenReader('Note has been deleted');
    }

    /**
     * Render notes list
     */
    function renderNotes() {
        const $container = $('#notes-list-container');
        
        if (filteredNotes.length === 0 && seoNotes.length === 0) {
            // Show empty state
            $container.html(`
                <div class="notes-empty-state">
                    <div class="notes-empty-icon" aria-hidden="true">📝</div>
                    <div class="notes-empty-title">No notes yet</div>
                    <div class="notes-empty-description">Start by adding your first SEO note above</div>
                </div>
            `);
        } else if (filteredNotes.length === 0) {
            // No matches for search
            $container.html(`
                <div class="notes-empty-state">
                    <div class="notes-empty-icon" aria-hidden="true">🔍</div>
                    <div class="notes-empty-title">No matching notes</div>
                    <div class="notes-empty-description">Try adjusting your search terms</div>
                </div>
            `);
        } else {
            // Render notes
            const notesHtml = filteredNotes.map(note => {
                const preview = note.text.substring(0, 150);
                const needsExpansion = note.text.length > 150;
                const timeAgo = formatTimeAgo(note.timestamp);
                
                return `
                    <div class="note-item" data-note-id="${note.id}" role="article" aria-label="Note from ${timeAgo}">
                        <div class="note-item-header" role="button" aria-expanded="false" tabindex="0">
                            <div class="note-item-meta">
                                <div class="note-item-timestamp" title="${formatFullDate(note.timestamp)}">
                                    ${timeAgo} ${note.edited ? '(edited)' : ''}
                                </div>
                                ${note.author ? `<div class="note-item-author">By ${note.author}</div>` : ''}
                                <div class="note-item-preview">
                                    ${escapeHtml(preview)}${needsExpansion ? '...' : ''}
                                    ${needsExpansion ? '<span class="note-expand-indicator" aria-hidden="true">▼</span>' : ''}
                                </div>
                            </div>
                            <div class="note-item-actions">
                                <button class="note-action-btn note-edit-btn" aria-label="Edit note">
                                    <span aria-hidden="true">✏️</span> Edit
                                </button>
                                <button class="note-action-btn delete note-delete-btn" aria-label="Delete note">
                                    <span aria-hidden="true">🗑️</span> Delete
                                </button>
                            </div>
                        </div>
                        ${needsExpansion ? `
                            <div class="note-item-content" aria-hidden="true">
                                <div class="note-full-text">${escapeHtml(note.text)}</div>
                            </div>
                        ` : ''}
                    </div>
                `;
            }).join('');
            
            $container.html(notesHtml);
        }
    }

    /**
     * Search functionality
     */
    function initializeSearch() {
        const $searchInput = $('#notes-search-input');
        
        $searchInput.on('input', debounce(function() {
            const searchTerm = $(this).val().toLowerCase();
            
            if (searchTerm) {
                filteredNotes = seoNotes.filter(note => 
                    note.text.toLowerCase().includes(searchTerm) ||
                    (note.author && note.author.toLowerCase().includes(searchTerm))
                );
            } else {
                filteredNotes = [...seoNotes];
            }
            
            renderNotes();
        }, 300));
    }

    /**
     * Sorting functionality
     */
    function initializeSorting() {
        const $sortSelect = $('#notes-sort-select');
        
        $sortSelect.on('change', function() {
            const sortBy = $(this).val();
            
            switch(sortBy) {
                case 'newest':
                    filteredNotes.sort((a, b) => new Date(b.timestamp) - new Date(a.timestamp));
                    break;
                case 'oldest':
                    filteredNotes.sort((a, b) => new Date(a.timestamp) - new Date(b.timestamp));
                    break;
                case 'author':
                    filteredNotes.sort((a, b) => (a.author || '').localeCompare(b.author || ''));
                    break;
            }
            
            renderNotes();
        });
    }

    /**
     * Post History Tracker
     */
    function initializePostHistory() {
        const $panel = $('.history-tracker-panel');
        if (!$panel.length) return;

        // Date filter
        $('#history-date-filter').on('change', function() {
            filterHistory();
        });

        // User filter
        $('#history-user-filter').on('change', function() {
            filterHistory();
        });
    }

    /**
     * Load post history
     */
    function loadPostHistory() {
        const $tbody = $('#history-table-tbody');
        
        // Show loading
        $tbody.html(`
            <tr>
                <td colspan="4" style="text-align: center;">
                    <div class="notes-loading">
                        <div class="notes-spinner"></div>
                        <div class="notes-loading-text">Loading history...</div>
                    </div>
                </td>
            </tr>
        `);

        // Simulate loading history (replace with actual AJAX call)
        setTimeout(() => {
            const history = getPostHistory();
            
            if (history.length === 0) {
                $tbody.html(`
                    <tr>
                        <td colspan="4">
                            <div class="history-empty-state">
                                <div class="history-empty-icon" aria-hidden="true">📜</div>
                                <div class="history-empty-title">No edit history yet</div>
                                <div class="history-empty-description">Changes to this post will appear here</div>
                            </div>
                        </td>
                    </tr>
                `);
            } else {
                renderHistory(history);
            }
        }, 1000);
    }

    /**
     * Render post history
     */
    function renderHistory(history) {
        const $tbody = $('#history-table-tbody');
        
        const historyHtml = history.map(entry => {
            const timeAgo = formatTimeAgo(entry.timestamp);
            const fullDate = formatFullDate(entry.timestamp);
            
            return `
                <tr>
                    <td>
                        <div class="history-timestamp" title="${fullDate}">
                            ${timeAgo}
                            <span class="history-timestamp-tooltip">${fullDate}</span>
                        </div>
                    </td>
                    <td>
                        <span class="history-editor-badge">${entry.editor}</span>
                    </td>
                    <td>
                        <div class="history-summary" title="${escapeHtml(entry.summary)}">
                            ${escapeHtml(entry.summary)}
                        </div>
                    </td>
                    <td>
                        <button class="note-action-btn" onclick="viewHistoryDiff('${entry.id}')" aria-label="View changes">
                            <span aria-hidden="true">👁️</span> View
                        </button>
                    </td>
                </tr>
            `;
        }).join('');
        
        $tbody.html(historyHtml);
    }

    /**
     * Filter history
     */
    function filterHistory() {
        const dateFilter = $('#history-date-filter').val();
        const userFilter = $('#history-user-filter').val();
        
        let history = getPostHistory();
        
        // Apply date filter
        if (dateFilter) {
            const filterDate = new Date(dateFilter);
            history = history.filter(entry => {
                const entryDate = new Date(entry.timestamp);
                return entryDate >= filterDate;
            });
        }
        
        // Apply user filter
        if (userFilter && userFilter !== 'all') {
            history = history.filter(entry => entry.editor === userFilter);
        }
        
        renderHistory(history);
    }

    /**
     * Save notes to localStorage/database
     */
    function saveNotes() {
        const postId = getPostId();
        const key = `almaseo_notes_${postId}`;
        
        // Save to localStorage (replace with AJAX call)
        localStorage.setItem(key, JSON.stringify(seoNotes));
        
        // Also save to database via AJAX
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'almaseo_save_notes',
                post_id: postId,
                notes: JSON.stringify(seoNotes),
                nonce: $('#almaseo_nonce').val()
            },
            success: function(response) {
                console.log('Notes saved to database');
            }
        });
    }

    /**
     * Load notes from storage
     */
    function loadNotes() {
        const postId = getPostId();
        const key = `almaseo_notes_${postId}`;
        
        // Show loading state
        $('#notes-list-container').html(`
            <div class="notes-loading">
                <div class="notes-spinner"></div>
                <div class="notes-loading-text">Loading notes...</div>
            </div>
        `);
        
        // Load from localStorage first
        const savedNotes = localStorage.getItem(key);
        if (savedNotes) {
            seoNotes = JSON.parse(savedNotes);
            filteredNotes = [...seoNotes];
            renderNotes();
        }
        
        // Then sync with database
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'almaseo_get_notes',
                post_id: postId,
                nonce: $('#almaseo_nonce').val()
            },
            success: function(response) {
                if (response.success && response.data) {
                    seoNotes = response.data;
                    filteredNotes = [...seoNotes];
                } else {
                    // No notes exist - set empty arrays
                    seoNotes = [];
                    filteredNotes = [];
                }
                renderNotes();
            },
            error: function() {
                // On error, show empty state if no localStorage data
                if (!savedNotes) {
                    seoNotes = [];
                    filteredNotes = [];
                    renderNotes();
                }
            }
        });
    }

    /**
     * Get post history (mock data - replace with actual AJAX)
     */
    function getPostHistory() {
        // This would normally come from the database
        return [
            {
                id: 'rev_1',
                timestamp: new Date(Date.now() - 86400000).toISOString(),
                editor: 'John Doe',
                summary: 'Updated SEO title and meta description'
            },
            {
                id: 'rev_2',
                timestamp: new Date(Date.now() - 172800000).toISOString(),
                editor: 'Jane Smith',
                summary: 'Added schema markup and internal links'
            },
            {
                id: 'rev_3',
                timestamp: new Date(Date.now() - 604800000).toISOString(),
                editor: 'John Doe',
                summary: 'Initial post creation with basic SEO setup'
            }
        ];
    }

    /**
     * Accessibility Features
     */
    function initializeAccessibility() {
        // Keyboard navigation for expandable notes
        $(document).on('keypress', '.note-item-header[role="button"]', function(e) {
            if (e.which === 13 || e.which === 32) { // Enter or Space
                e.preventDefault();
                $(this).click();
            }
        });

        // Focus management
        $('.almaseo-tab-btn[data-tab="notes-history"]').on('click', function() {
            setTimeout(() => {
                $('#tab-notes-history').find('input, textarea, select, button').first().focus();
            }, 300);
        });

        // ARIA live regions
        setupAriaLiveRegions();
    }

    /**
     * Setup ARIA live regions
     */
    function setupAriaLiveRegions() {
        if (!$('#notes-history-announcer').length) {
            $('body').append('<div id="notes-history-announcer" class="sr-only" aria-live="polite" aria-atomic="true"></div>');
        }
    }

    /**
     * Announce to screen readers
     */
    function announceToScreenReader(message) {
        const $announcer = $('#notes-history-announcer');
        $announcer.text(message);
        
        setTimeout(() => {
            $announcer.text('');
        }, 1000);
    }

    /**
     * Helper Functions
     */
    
    /**
     * Format time ago
     */
    function formatTimeAgo(timestamp) {
        const date = new Date(timestamp);
        const now = new Date();
        const seconds = Math.floor((now - date) / 1000);
        
        if (seconds < 60) {
            return 'Just now';
        } else if (seconds < 3600) {
            const minutes = Math.floor(seconds / 60);
            return `${minutes} minute${minutes !== 1 ? 's' : ''} ago`;
        } else if (seconds < 86400) {
            const hours = Math.floor(seconds / 3600);
            return `${hours} hour${hours !== 1 ? 's' : ''} ago`;
        } else if (seconds < 604800) {
            const days = Math.floor(seconds / 86400);
            return `${days} day${days !== 1 ? 's' : ''} ago`;
        } else if (seconds < 2592000) {
            const weeks = Math.floor(seconds / 604800);
            return `${weeks} week${weeks !== 1 ? 's' : ''} ago`;
        } else if (seconds < 31536000) {
            const months = Math.floor(seconds / 2592000);
            return `${months} month${months !== 1 ? 's' : ''} ago`;
        } else {
            const years = Math.floor(seconds / 31536000);
            return `${years} year${years !== 1 ? 's' : ''} ago`;
        }
    }

    /**
     * Format full date
     */
    function formatFullDate(timestamp) {
        const date = new Date(timestamp);
        const options = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        };
        return date.toLocaleDateString('en-US', options);
    }

    /**
     * Get current user
     */
    function getCurrentUser() {
        // Get from WordPress user data
        return $('#almaseo-current-user').val() || 'Unknown';
    }

    /**
     * Get post ID
     */
    function getPostId() {
        return $('#post_ID').val() || '0';
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
        return text.replace(/[&<>"']/g, m => map[m]);
    }

    /**
     * Show message
     */
    function showMessage(type, message) {
        const $messageContainer = $('#notes-message-container');
        
        const messageHtml = `
            <div class="note-message ${type}" role="alert">
                <span aria-hidden="true">${type === 'success' ? '✅' : '⚠️'}</span>
                <span>${message}</span>
            </div>
        `;
        
        $messageContainer.html(messageHtml);
        
        // Auto-hide after 3 seconds
        setTimeout(() => {
            $messageContainer.find('.note-message').fadeOut(300, function() {
                $(this).remove();
            });
        }, 3000);
    }

    /**
     * Debounce function
     */
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    /**
     * View history diff (placeholder)
     */
    window.viewHistoryDiff = function(revisionId) {
        console.log('Viewing diff for revision:', revisionId);
        // Implement diff viewer modal or redirect
        alert('Diff viewer coming soon for revision: ' + revisionId);
    };

})(jQuery);