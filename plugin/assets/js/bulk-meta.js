/**
 * AlmaSEO Bulk Metadata Editor JavaScript
 * 
 * @package AlmaSEO
 * @since 6.3.0
 */

(function($) {
    'use strict';
    
    // Exit early if jQuery is not available
    if (!$) {
        console.error('BulkMeta: jQuery is required but not loaded');
        return;
    }
    
    // === Constants ===
    const TITLE_MAX = 580;   // px
    const DESC_MAX  = 920;   // px
    
    // === Helpers ===
    function stripShortcodes(s) { 
        return String(s||'').replace(/\[.*?\]/g,'').replace(/<[^>]+>/g,'').trim(); 
    }
    
    function pxWidth(text, font='16px Arial') {
        const c = document.createElement('canvas'); 
        const ctx = c.getContext('2d');
        ctx.font = font; 
        return Math.round(ctx.measureText(String(text||'')).width);
    }
    
    function badge(type, tip) {
        const map = {
            good: {cls:'good', icon:'dashicons-yes', label:'Good'},
            warn: {cls:'warn', icon:'dashicons-warning', label:'Needs Work'},
            risk: {cls:'risk', icon:'dashicons-dismiss', label:'At Risk'},
            info: {cls:'info', icon:'dashicons-info', label:'Dupe'},
        };
        const m = map[type] || map.info;
        return `<span class="alma-badge ${m.cls}" data-tip="${tip||m.label}">
            <span class="dashicons ${m.icon}"></span><span>${m.label}</span>
        </span>`;
    }
    
    function gauge(px, max, tip) {
        const pct = Math.min(130, (px/max)*100);
        const cls = px > max ? 'over' : (pct >= 65 ? 'ideal' : (pct >= 45 ? 'ok' : 'low'));
        const num = `${px}/${max}px`;
        return `<span class="alma-gauge ${cls}" data-tip="${tip}">
            <span class="bar" style="--w:${pct}%"></span><span class="num">${num}</span>
        </span>`;
    }
    
    function clarityFlag(s) {
        s = stripShortcodes(s);
        const words = s ? s.split(/\s+/) : [];
        const uniq = new Set(words.map(w=>w.toLowerCase())).size;
        const stop = words.filter(w=>/\b(a|an|the|and|of|to|for|in|on|with|by|from)\b/i.test(w)).length;
        const ratio = words.length ? (uniq/words.length) : 1;
        if (words.length > 0 && stop/Math.max(1, words.length) > 0.55) return 'wordy';
        if (ratio >= 0.8) return 'crisp';
        return 'ok';
    }
    
    function hashNorm(s) { 
        return btoa(unescape(encodeURIComponent(stripShortcodes(s).toLowerCase()))).slice(0,12); 
    }
    
    // Wait for DOM to be ready before accessing elements
    let $errorContainer = null;
    
    // Helper function to show overlay
    function showOverlay() {
        $('body').addClass('almaseo-loading');
    }
    
    // Helper function to hide overlay
    function hideOverlay() {
        $('body').removeClass('almaseo-loading');
        $('[data-loading]').remove();
    }
    
    // Helper function to show error
    function showError(msg) {
        console.error('BulkMeta Error:', msg);
        // Get error container when needed
        const $errorCont = $('#almaseo-bulkmeta-error');
        if ($errorCont.length) {
            $errorCont.find('p').text(msg);
            $errorCont.show();
        } else {
            // Create error container if it doesn't exist
            $('.almaseo-bulk-meta, #bulkmeta-container').first().prepend(
                '<div id="almaseo-bulkmeta-error" class="notice notice-error"><p>' + msg + '</p></div>'
            );
        }
    }
    
    // Helper to safely get string length
    function safeLen(s) {
        return stripShortcodes(s).length;
    }
    
    // Get or create tbody with robust fallback
    function getOrCreateTbody() {
        // Try known selectors
        let tbody = 
            document.querySelector('#almaseo-bulkmeta-body') ||
            document.querySelector('#bulkmeta-table-body') ||
            document.querySelector('#bulkmeta-table tbody') ||
            document.querySelector('.almaseo-bulk-meta tbody') ||
            document.querySelector('#almaseo-bulkmeta table tbody') ||
            document.querySelector('#almaseo-bulkmeta tbody');
        
        // If no table/tbody, create full skeleton
        if (!tbody) {
            const host = 
                document.querySelector('#almaseo-bulkmeta') || 
                document.querySelector('.almaseo-bulk-meta') ||
                document.querySelector('.wrap.almaseo-bulk-meta') ||
                document.querySelector('#bulkmeta-container');
                
            if (!host) {
                console.error('No host container found for bulk meta table');
                return null;
            }
            
            // Find or create wrapper
            let wrapper = host.querySelector('.almaseo-bulkmeta-wrapper');
            if (!wrapper) {
                wrapper = document.createElement('div');
                wrapper.className = 'almaseo-bulkmeta-wrapper';
                host.appendChild(wrapper);
            }
            
            // Create full table structure
            wrapper.innerHTML = `
                <table class="wp-list-table widefat fixed striped" id="almaseo-bulkmeta-table">
                    <thead>
                        <tr>
                            <td id="cb" class="manage-column column-cb check-column"><input type="checkbox"></td>
                            <th class="column-title">Post Title</th>
                            <th class="column-type">Type</th>
                            <th class="column-status">Status</th>
                            <th class="column-seo-title">SEO Title</th>
                            <th class="column-meta-desc">Meta Description</th>
                            <th class="column-updated">Updated</th>
                            <th class="column-actions">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="almaseo-bulkmeta-body"></tbody>
                </table>
                <p class="almaseo-empty hidden">0 items</p>
                <div data-loading>Loading posts...</div>`;
                
            tbody = document.querySelector('#almaseo-bulkmeta-body');
            console.log('Created table structure with tbody');
        }
        
        return tbody;
    }
    
    const BulkMetaEditor = {
        currentPage: 1,
        totalPages: 1,
        selectedIds: new Set(),
        saveTimers: {},
        pixelCanvas: null,
        pixelContext: null,
        
        /**
         * Initialize
         */
        init: function() {
            console.log('BulkMeta init - Config:', window.AlmaBulkMeta);
            
            // Check for required dependencies
            if (typeof wp === 'undefined' || typeof wp.apiFetch === 'undefined') {
                showError('wp.apiFetch is not available. Please check script dependencies.');
                hideOverlay();
                return;
            }
            
            this.setupPixelMeasurement();
            this.bindEvents();
            this.initialLoad();
            this.loadTaxonomies();
        },
        
        /**
         * Initial load using wp.apiFetch only
         */
        async initialLoad() {
            try {
                console.log('Loading posts with wp.apiFetch...');
                
                const qs = new URLSearchParams({
                    type: 'post,page',  // Include both posts and pages by default
                    status: 'publish,draft',
                    page: 1,
                    per_page: 20
                }).toString();
                
                const resp = await wp.apiFetch({
                    path: `/almaseo/v1/bulkmeta?${qs}`
                });
                
                console.log('API Response:', resp);
                
                // Normalize response - handle different response shapes
                const rows = Array.isArray(resp) ? resp : (resp.posts || resp.items || resp.data || []);
                
                // Render rows with normalized data
                this.renderRows(rows);
                
                // Clear any loading indicators
                document.querySelector('[data-loading]')?.remove();
                document.querySelector('.almaseo-empty')?.classList.toggle('hidden', rows.length !== 0);
                
            } catch (e) {
                console.error('BulkMeta load failed', e);
                showError(`BulkMeta load failed: ${e?.message || e}`);
            } finally {
                hideOverlay();
                document.body.classList.remove('almaseo-loading');
            }
        },
        
        /**
         * Render rows with defensive checks
         */
        renderRows: function(rows) {
            // Accept either array directly or object with posts property
            rows = Array.isArray(rows) ? rows : (rows?.posts || []);
            
            // Get or create tbody element
            const tbody = getOrCreateTbody();
            if (!tbody) {
                console.warn('Table body still not found');
                return;
            }
            
            // Clear loading indicator first
            document.querySelector('[data-loading]')?.remove();
            
            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8">No posts found</td></tr>';
                document.querySelector('.almaseo-empty')?.classList.toggle('hidden', false);
                return;
            }
            
            // Track duplicates
            const dupeMap = {};
            
            // Build HTML for all rows with visual indicators
            tbody.innerHTML = rows.map(r => {
                const cleanTitle = stripShortcodes(r.seo_title || r.meta_title || '');
                const cleanDesc  = stripShortcodes(r.meta_desc || r.meta_description || '');
                const tpx = pxWidth(cleanTitle, '16px Arial');
                const dpx = pxWidth(cleanDesc, '14px Arial');
                
                const tGauge = gauge(tpx, TITLE_MAX, 'Title pixel width vs. SERP max');
                const dGauge = gauge(dpx, DESC_MAX, 'Description pixel width vs. SERP max');
                
                let verdict = 'good';
                if (tpx > TITLE_MAX + 20 || dpx > DESC_MAX + 40) verdict = 'risk';
                else if (tpx < TITLE_MAX * 0.55 || dpx < DESC_MAX * 0.40) verdict = 'warn';
                
                const clarity = clarityFlag(r.meta_desc || r.meta_description || '');
                if (clarity === 'wordy' && verdict === 'good') verdict = 'warn';
                
                const vBadge = badge(
                    verdict,
                    verdict === 'good' ? 'Length + clarity look solid for SERP'
                    : verdict === 'warn' ? 'Short/wordy or near cutoff—tune it'
                    : 'Likely to truncate—tighten wording'
                );
                
                // Include dupe hashing (ensure you set data-hash on <tr>)
                const key = hashNorm((r.seo_title || r.meta_title || '') + '|' + (r.meta_desc || r.meta_description || ''));
                dupeMap[key] = (dupeMap[key] || 0) + 1;
                
                return `
                    <tr data-id="${r.id}" data-hash="${key}">
                        <th scope="row" class="check-column">
                            <input type="checkbox" class="post-checkbox" value="${r.id}">
                        </th>
                        <td class="column-title">
                            <strong>${r.title || ''}</strong>
                            <div class="row-actions">
                                <a href="${r.edit_link || '#'}" target="_blank">Edit</a> | 
                                <a href="${r.view_link || '#'}" target="_blank">View</a>
                            </div>
                        </td>
                        <td class="column-type">${r.type_label || r.type || ''}</td>
                        <td class="column-status">${r.status || ''}</td>
                        <td class="column-seo-title editable-cell" data-field="title" data-id="${r.id}">
                            <div class="field-display">
                                <div class="meta-field">${r.seo_title || r.meta_title || ''}</div>
                                <div class="signals">${tGauge}${vBadge}</div>
                            </div>
                            <div class="field-edit" style="display:none;">
                                <input type="text" class="meta-title-input regular-text" 
                                       value="${r.seo_title || r.meta_title || ''}" 
                                       placeholder="${r.title_fallback || ''}">
                                <div class="char-counter ${safeLen(r.seo_title || r.meta_title || '') > 65 ? 'warning' : ''}">
                                    <span class="char-count">${safeLen(r.seo_title || r.meta_title || '')}</span>/65 characters
                                </div>
                            </div>
                            <button class="edit-button button-link">Edit</button>
                        </td>
                        <td class="column-meta-desc editable-cell" data-field="description" data-id="${r.id}">
                            <div class="field-display">
                                <div class="meta-field">${r.meta_desc || r.meta_description || ''}</div>
                                <div class="signals">${dGauge}${
                                    clarity === 'crisp' ? badge('good', 'Concise wording')
                                    : clarity === 'wordy' ? badge('warn', 'High stop-words / repetition')
                                    : ''
                                }</div>
                            </div>
                            <div class="field-edit" style="display:none;">
                                <textarea class="meta-description-input regular-text" rows="3"
                                          placeholder="${r.desc_fallback || ''}">${r.meta_desc || r.meta_description || ''}</textarea>
                                <div class="char-counter ${safeLen(r.meta_desc || r.meta_description || '') > 160 ? 'warning' : ''}">
                                    <span class="char-count">${safeLen(r.meta_desc || r.meta_description || '')}</span>/160 characters
                                </div>
                            </div>
                            <button class="edit-button button-link">Edit</button>
                        </td>
                        <td class="column-updated">${r.updated || ''}</td>
                        <td class="column-actions">
                            <button class="reset-meta button-link" data-id="${r.id}">Reset</button>
                        </td>
                    </tr>`;
            }).join('');
            
            // After rendering all rows, mark duplicates
            document.querySelectorAll('#almaseo-bulkmeta-body tr').forEach(tr => {
                const h = tr.dataset?.hash || '';
                if (dupeMap[h] > 1) {
                    tr.querySelector('.column-seo-title .signals')
                        ?.insertAdjacentHTML('beforeend', badge('info', 'Duplicate title/description detected'));
                }
            });
            
            // Update pagination
            this.totalPages = rows.pages || 1;
            this.renderPagination();
            
            // Update total items count
            const totalEl = document.getElementById('total-items');
            if (totalEl) totalEl.textContent = rows.total || rows.length || 0;
            
            // Update empty state
            document.querySelector('.almaseo-empty')?.classList.toggle('hidden', rows.length !== 0);
        },
        
        
        /**
         * Setup pixel measurement canvas
         */
        setupPixelMeasurement: function() {
            this.pixelCanvas = document.getElementById('pixel-tester');
            if (this.pixelCanvas) {
                this.pixelContext = this.pixelCanvas.getContext('2d');
                this.pixelContext.font = '14px Arial, sans-serif';
            }
        },
        
        /**
         * Bind events
         */
        bindEvents: function() {
            const self = this;
            
            // Filters
            $('#apply-filters').on('click', function() {
                self.currentPage = 1;
                self.loadPosts();
            });
            
            // Search
            $('#search-button').on('click', function() {
                self.currentPage = 1;
                self.loadPosts();
            });
            
            $('#search-box').on('keypress', function(e) {
                if (e.which === 13) {
                    self.currentPage = 1;
                    self.loadPosts();
                }
            });
            
            // Post type change
            $('#post-type-filter').on('change', function() {
                self.loadTaxonomies();
            });
            
            // Taxonomy change
            $('#taxonomy-filter').on('change', function() {
                const taxonomy = $(this).val();
                if (taxonomy) {
                    self.loadTerms(taxonomy);
                    $('#term-filter').show();
                } else {
                    $('#term-filter').hide().empty().append('<option value="">Select Term</option>');
                }
            });
            
            // Select all checkbox
            $('#select-all').on('change', function() {
                const checked = $(this).prop('checked');
                $('.post-checkbox').prop('checked', checked);
                self.updateSelectedCount();
            });
            
            // Delegate events for dynamic content
            $(document).on('change', '.post-checkbox', function() {
                self.updateSelectedCount();
            });
            
            $(document).on('click', '.edit-button', function() {
                const cell = $(this).closest('.editable-cell');
                self.startEdit(cell);
            });
            
            $(document).on('blur', '.meta-title-input, .meta-description-input', function() {
                const cell = $(this).closest('.editable-cell');
                self.saveField(cell);
            });
            
            $(document).on('input', '.meta-title-input, .meta-description-input', function() {
                self.updateCounter($(this));
            });
            
            $(document).on('click', '.reset-meta', function() {
                const id = $(this).data('id');
                self.resetPost(id);
            });
            
            // Pagination
            $(document).on('click', '.pagination-links a', function(e) {
                e.preventDefault();
                const page = $(this).data('page');
                if (page) {
                    self.currentPage = page;
                    self.loadPosts();
                }
            });
            
            // Bulk actions
            $('#bulk-action').on('change', function() {
                const action = $(this).val();
                $('#bulk-options').toggle(action !== '');
                $('#bulk-text').toggle(action === 'append' || action === 'prepend');
                $('#bulk-find, #bulk-replace').toggle(action === 'replace');
                $('#bulk-field').toggle(action !== 'reset');
            });
            
            $('#apply-bulk').on('click', function() {
                self.applyBulkAction();
            });
        },
        
        /**
         * Load posts using wp.apiFetch
         */
        async loadPosts() {
            const params = {
                type: $('#post-type-filter').val() || 'post,page',
                status: $('#status-filter').val() || 'publish,draft',
                taxonomy: $('#taxonomy-filter').val() || '',
                term: $('#term-filter').val() || '',
                from: $('#date-from').val() || '',
                to: $('#date-to').val() || '',
                search: $('#search-box').val() || '',
                missing: $('#missing-only').prop('checked') ? '1' : '',
                page: this.currentPage,
                per_page: 20
            };
            
            // Clean up empty params
            Object.keys(params).forEach(key => {
                if (!params[key]) delete params[key];
            });
            
            try {
                showOverlay();
                
                const qs = new URLSearchParams(params).toString();
                const resp = await wp.apiFetch({
                    path: `/almaseo/v1/bulkmeta?${qs}`
                });
                
                // Normalize response - handle different response shapes
                const rows = Array.isArray(resp) ? resp : (resp.posts || resp.items || resp.data || []);
                
                // Render with normalized data
                this.renderRows(rows);
                
            } catch (error) {
                console.error('Failed to load posts:', error);
                showError(`Failed to load posts: ${error?.message || 'Unknown error'}`);
            } finally {
                hideOverlay();
                document.body.classList.remove('almaseo-loading');
            }
        },
        
        /**
         * Load taxonomies using wp.apiFetch
         */
        async loadTaxonomies() {
            const postType = $('#post-type-filter').val();
            
            try {
                const taxonomies = await wp.apiFetch({
                    path: `/almaseo/v1/bulkmeta/taxonomies?post_type=${postType}`
                });
                
                const $select = $('#taxonomy-filter');
                $select.empty().append('<option value="">All Categories/Tags</option>');
                
                if (Array.isArray(taxonomies)) {
                    taxonomies.forEach(tax => {
                        $select.append(`<option value="${tax.name}">${tax.label}</option>`);
                    });
                }
                
            } catch (error) {
                console.error('Failed to load taxonomies:', error);
            }
        },
        
        /**
         * Load terms using wp.apiFetch
         */
        async loadTerms(taxonomy) {
            try {
                const terms = await wp.apiFetch({
                    path: `/almaseo/v1/bulkmeta/terms?taxonomy=${taxonomy}`
                });
                
                const $select = $('#term-filter');
                $select.empty().append('<option value="">Select Term</option>');
                
                if (Array.isArray(terms)) {
                    terms.forEach(term => {
                        $select.append(`<option value="${term.id}">${term.name} (${term.count})</option>`);
                    });
                }
                
            } catch (error) {
                console.error('Failed to load terms:', error);
            }
        },
        
        /**
         * Start editing a field
         */
        startEdit: function($cell) {
            $cell.find('.field-display').hide();
            $cell.find('.field-edit').show();
            $cell.find('input, textarea').focus().select();
        },
        
        /**
         * Save field using wp.apiFetch
         */
        async saveField($cell) {
            const id = $cell.data('id');
            const field = $cell.data('field');
            const value = $cell.find(field === 'title' ? '.meta-title-input' : '.meta-description-input').val();
            
            // Update display
            $cell.find('.meta-field').text(value || $cell.find('input, textarea').attr('placeholder'));
            $cell.find('.field-display').show();
            $cell.find('.field-edit').hide();
            
            // Clear any existing timer
            if (this.saveTimers[id + '_' + field]) {
                clearTimeout(this.saveTimers[id + '_' + field]);
            }
            
            // Debounce save
            this.saveTimers[id + '_' + field] = setTimeout(async () => {
                try {
                    const data = {};
                    if (field === 'title') {
                        data.meta_title = value;
                    } else {
                        data.meta_description = value;
                    }
                    
                    await wp.apiFetch({
                        path: `/almaseo/v1/bulkmeta/${id}`,
                        method: 'PATCH',
                        data: data
                    });
                    
                    this.showToast('Saved!', 'success');
                    
                } catch (error) {
                    showError(`Failed to save: ${error?.message || 'Unknown error'}`);
                    // Revert display on error
                    this.loadPosts();
                }
            }, 1000);
        },
        
        /**
         * Update character counter
         */
        updateCounter: function($input) {
            const value = $input.val();
            const length = safeLen(value);
            const isTitle = $input.hasClass('meta-title-input');
            const maxLength = isTitle ? 65 : 160;
            
            const $counter = $input.siblings('.char-counter');
            $counter.find('.char-count').text(length);
            $counter.toggleClass('warning', length > maxLength);
        },
        
        /**
         * Reset post metadata using wp.apiFetch
         */
        async resetPost(id) {
            if (!confirm('Reset metadata for this post?')) {
                return;
            }
            
            try {
                await wp.apiFetch({
                    path: `/almaseo/v1/bulkmeta/reset/${id}`,
                    method: 'POST'
                });
                
                this.showToast('Metadata reset!', 'success');
                this.loadPosts();
                
            } catch (error) {
                showError(`Failed to reset: ${error?.message || 'Unknown error'}`);
            }
        },
        
        /**
         * Apply bulk action using wp.apiFetch
         */
        async applyBulkAction() {
            const action = $('#bulk-action').val();
            if (!action) {
                showError('Please select a bulk action');
                return;
            }
            
            const selectedIds = [];
            $('.post-checkbox:checked').each(function() {
                selectedIds.push($(this).val());
            });
            
            if (selectedIds.length === 0) {
                showError('Please select at least one post');
                return;
            }
            
            const data = {
                ids: selectedIds,
                op: action,
                field: $('#bulk-field').val(),
                args: {}
            };
            
            if (action === 'append' || action === 'prepend') {
                data.args.text = $('#bulk-text').val();
            } else if (action === 'replace') {
                data.args.find = $('#bulk-find').val();
                data.args.replace = $('#bulk-replace').val();
            }
            
            if (!confirm(`Apply ${action} to ${selectedIds.length} posts?`)) {
                return;
            }
            
            try {
                showOverlay();
                
                const result = await wp.apiFetch({
                    path: '/almaseo/v1/bulkmeta/bulk',
                    method: 'POST',
                    data: data
                });
                
                this.showToast(`Bulk operation completed: ${result.success} succeeded, ${result.failed} failed`, 'success');
                this.loadPosts();
                
            } catch (error) {
                showError(`Bulk operation failed: ${error?.message || 'Unknown error'}`);
            } finally {
                hideOverlay();
            }
        },
        
        /**
         * Update selected count
         */
        updateSelectedCount: function() {
            const count = $('.post-checkbox:checked').length;
            $('#selected-count').text(count);
        },
        
        /**
         * Render pagination
         */
        renderPagination: function() {
            const $pagination = $('.pagination-links');
            $pagination.empty();
            
            if (this.totalPages <= 1) {
                return;
            }
            
            // Previous link
            if (this.currentPage > 1) {
                $pagination.append(
                    $('<a>', {
                        href: '#',
                        'data-page': this.currentPage - 1,
                        text: '← Previous'
                    })
                );
            }
            
            // Page numbers
            for (let i = 1; i <= Math.min(this.totalPages, 5); i++) {
                if (i === this.currentPage) {
                    $pagination.append($('<span>', {
                        class: 'current',
                        text: i
                    }));
                } else {
                    $pagination.append(
                        $('<a>', {
                            href: '#',
                            'data-page': i,
                            text: i
                        })
                    );
                }
            }
            
            // Next link
            if (this.currentPage < this.totalPages) {
                $pagination.append(
                    $('<a>', {
                        href: '#',
                        'data-page': this.currentPage + 1,
                        text: 'Next →'
                    })
                );
            }
        },
        
        /**
         * Show toast notification
         */
        showToast: function(message, type = 'info') {
            const $toast = $('<div>', {
                class: 'notice notice-' + type + ' is-dismissible',
                style: 'position: fixed; top: 50px; right: 20px; z-index: 10000;'
            }).append(
                $('<p>').text(message),
                $('<button>', {
                    type: 'button',
                    class: 'notice-dismiss',
                    click: function() {
                        $(this).parent().remove();
                    }
                })
            );
            
            $('body').append($toast);
            
            setTimeout(function() {
                $toast.fadeOut(function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };
    
    // Initialize when DOM is ready - defensive approach
    if (typeof jQuery !== 'undefined') {
        jQuery(function($) {
            // Only initialize on bulk meta page (check for multiple possible containers)
            if ($('.almaseo-bulk-meta').length > 0 || $('#bulkmeta-table').length > 0 || $('#almaseo-bulkmeta').length > 0) {
                console.log('Initializing BulkMetaEditor...');
                BulkMetaEditor.init();
            }
        });
    } else {
        console.error('jQuery is not loaded - BulkMeta cannot initialize');
    }
    
})(typeof jQuery !== 'undefined' ? jQuery : null);