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
    // Single source of truth — localized from PHP (BulkMeta_Controller::enqueue_assets).
    const LIMITS = (window.AlmaBulkMeta && window.AlmaBulkMeta.limits) || {
        title_chars: 65, title_pixels: 580, desc_chars: 160, desc_pixels: 920
    };
    const TITLE_MAX = LIMITS.title_pixels;
    const DESC_MAX  = LIMITS.desc_pixels;
    const TITLE_CHAR_MAX = LIMITS.title_chars;
    const DESC_CHAR_MAX  = LIMITS.desc_chars;
    const AI_AVAILABLE = !!(window.AlmaBulkMeta && window.AlmaBulkMeta.aiAvailable);

    // Hard ceiling for "Auto-Fill Entire Site" — refuses to run rather than
    // silently truncate. Sites above this should fall back to "Auto-Fill All
    // Empty" or filtered selections.
    const ENTIRE_SITE_MAX = 5000;

    // === Helpers ===
    function stripShortcodes(s) {
        return String(s||'').replace(/\[.*?\]/g,'').replace(/<[^>]+>/g,'').trim();
    }

    // HTML-escape user-supplied strings before interpolating into innerHTML.
    function escHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Same, used for attribute values. (escHtml already escapes quotes.)
    const escAttr = escHtml;

    // Only allow http(s) and protocol-relative URLs. Anything else (javascript:,
    // data:, vbscript:) becomes '#'. Defensive against malformed REST data.
    function safeUrl(u) {
        const s = String(u == null ? '' : u).trim();
        if (s === '') return '#';
        if (/^(https?:)?\/\//i.test(s)) return s;
        if (s.charAt(0) === '/' || s.charAt(0) === '?' || s.charAt(0) === '#') return s;
        return '#';
    }

    // Relative-time formatter for the Updated column. Falls back to the raw
    // ISO string in the title attribute so hover gives the exact timestamp.
    function relativeTime(iso) {
        if (!iso) return '';
        const t = Date.parse(iso);
        if (isNaN(t)) return '';
        const diff = Math.round((Date.now() - t) / 1000); // seconds
        const abs = Math.abs(diff);
        const units = [
            [60, 'second', 1],
            [3600, 'minute', 60],
            [86400, 'hour', 3600],
            [604800, 'day', 86400],
            [2592000, 'week', 604800],
            [31536000, 'month', 2592000],
            [Infinity, 'year', 31536000],
        ];
        for (const [limit, name, factor] of units) {
            if (abs < limit) {
                const n = Math.max(1, Math.floor(abs / factor));
                const suffix = n === 1 ? name : name + 's';
                return diff >= 0 ? `${n} ${suffix} ago` : `in ${n} ${suffix}`;
            }
        }
        return iso;
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
            
            // Create full table structure — matches the PHP template's thead
            // so column headers and row cells align.
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
        }
        
        return tbody;
    }
    
    const BulkMetaEditor = {
        currentPage: 1,
        totalPages: 1,
        totalItems: 0,
        selectedIds: new Set(),
        saveTimers: {},
        pixelCanvas: null,
        pixelContext: null,

        /**
         * Initialize
         */
        init: function() {
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
         * Fetch posts and parse pagination headers.
         *
         * wp.apiFetch by default unwraps the body; pass parse:false so we can
         * read X-WP-Total / X-WP-TotalPages — the previous code looked for
         * `rows.pages` on an Array (always undefined), so pagination was
         * effectively broken.
         */
        async fetchPosts(params) {
            const qs = new URLSearchParams(params).toString();
            const response = await wp.apiFetch({
                path: `/almaseo/v1/bulkmeta?${qs}`,
                parse: false,
            });
            const total      = parseInt(response.headers.get('X-WP-Total') || '0', 10);
            const totalPages = parseInt(response.headers.get('X-WP-TotalPages') || '1', 10);
            const items      = await response.json();
            return {
                items: Array.isArray(items) ? items : [],
                total,
                totalPages,
            };
        },

        /**
         * Initial load
         */
        async initialLoad() {
            try {
                const result = await this.fetchPosts({
                    type: 'post,page',
                    status: 'publish,draft',
                    page: 1,
                    per_page: 20,
                });

                this.totalPages = result.totalPages;
                this.totalItems = result.total;
                this.renderRows(result.items);

                document.querySelector('[data-loading]')?.remove();
                document.querySelector('.almaseo-empty')?.classList.toggle('hidden', result.items.length !== 0);
            } catch (e) {
                console.error('BulkMeta load failed', e);
                showError(`BulkMeta load failed: ${e?.message || e}`);
            } finally {
                hideOverlay();
                document.body.classList.remove('almaseo-loading');
            }
        },
        
        /**
         * Build a single row's HTML — used by initial render and by
         * refresh-in-place after a save.
         */
        rowHtml: function(r) {
            const seoTitle = String(r.seo_title || r.meta_title || '');
            const metaDesc = String(r.meta_desc || r.meta_description || '');
            const cleanTitle = stripShortcodes(seoTitle);
            const cleanDesc  = stripShortcodes(metaDesc);
            const tpx = pxWidth(cleanTitle, '16px Arial');
            const dpx = pxWidth(cleanDesc, '14px Arial');

            const tGauge = gauge(tpx, TITLE_MAX, 'Title pixel width vs. SERP max');
            const dGauge = gauge(dpx, DESC_MAX, 'Description pixel width vs. SERP max');

            let verdict = 'good';
            if (tpx > TITLE_MAX + 20 || dpx > DESC_MAX + 40) verdict = 'risk';
            else if (tpx < TITLE_MAX * 0.55 || dpx < DESC_MAX * 0.40) verdict = 'warn';

            const clarity = clarityFlag(metaDesc);
            if (clarity === 'wordy' && verdict === 'good') verdict = 'warn';

            const vBadge = badge(
                verdict,
                verdict === 'good' ? 'Length + clarity look solid for SERP'
                : verdict === 'warn' ? 'Short/wordy or near cutoff—tune it'
                : 'Likely to truncate—tighten wording'
            );

            const dupeKey = hashNorm(seoTitle + '|' + metaDesc);
            const titleLen = safeLen(seoTitle);
            const descLen  = safeLen(metaDesc);
            const updatedIso = String(r.updated || '');
            const updatedDisplay = relativeTime(updatedIso);

            return `
                <tr data-id="${escAttr(r.id)}" data-hash="${escAttr(dupeKey)}">
                    <th scope="row" class="check-column">
                        <input type="checkbox" class="post-checkbox" value="${escAttr(r.id)}">
                    </th>
                    <td class="column-title">
                        <strong>${escHtml(r.title)}</strong>
                        <div class="row-actions">
                            <a href="${escAttr(safeUrl(r.edit_link))}" target="_blank">Edit</a> |
                            <a href="${escAttr(safeUrl(r.view_link))}" target="_blank">View</a>
                        </div>
                    </td>
                    <td class="column-type">${escHtml(r.type_label || r.type || '')}</td>
                    <td class="column-status">${escHtml(r.status || '')}</td>
                    <td class="column-seo-title editable-cell" data-field="title" data-id="${escAttr(r.id)}">
                        <div class="field-display">
                            <div class="meta-field" title="${escAttr(seoTitle)}">${escHtml(seoTitle)}</div>
                            <div class="signals">${tGauge}${vBadge}</div>
                        </div>
                        <div class="field-edit" style="display:none;">
                            <input type="text" class="meta-title-input regular-text"
                                   value="${escAttr(seoTitle)}"
                                   placeholder="${escAttr(r.title_fallback || '')}">
                            <div class="char-counter ${titleLen > TITLE_CHAR_MAX ? 'warning' : ''}">
                                <span class="char-count">${titleLen}</span>/${TITLE_CHAR_MAX} characters
                            </div>
                        </div>
                        <button class="edit-button button-link">Edit</button>
                    </td>
                    <td class="column-meta-desc editable-cell" data-field="description" data-id="${escAttr(r.id)}">
                        <div class="field-display">
                            <div class="meta-field" title="${escAttr(metaDesc)}">${escHtml(metaDesc)}</div>
                            <div class="signals">${dGauge}${
                                clarity === 'crisp' ? badge('good', 'Concise wording')
                                : clarity === 'wordy' ? badge('warn', 'High stop-words / repetition')
                                : ''
                            }</div>
                        </div>
                        <div class="field-edit" style="display:none;">
                            <textarea class="meta-description-input regular-text" rows="3"
                                      placeholder="${escAttr(r.desc_fallback || '')}">${escHtml(metaDesc)}</textarea>
                            <div class="char-counter ${descLen > DESC_CHAR_MAX ? 'warning' : ''}">
                                <span class="char-count">${descLen}</span>/${DESC_CHAR_MAX} characters
                            </div>
                        </div>
                        <button class="edit-button button-link">Edit</button>
                    </td>
                    <td class="column-updated">
                        <span title="${escAttr(updatedIso)}">${escHtml(updatedDisplay)}</span>
                    </td>
                    <td class="column-actions">
                        <button class="reset-meta button-link" data-id="${escAttr(r.id)}">Reset</button>
                    </td>
                </tr>`;
        },

        /**
         * Render all rows. `rows` is a plain Array of row objects.
         */
        renderRows: function(rows) {
            rows = Array.isArray(rows) ? rows : [];

            const tbody = getOrCreateTbody();
            if (!tbody) return;

            document.querySelector('[data-loading]')?.remove();

            if (rows.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8">No posts found</td></tr>';
                document.querySelector('.almaseo-empty')?.classList.toggle('hidden', false);
                this.renderPagination();
                const totalEl = document.getElementById('total-items');
                if (totalEl) totalEl.textContent = this.totalItems || 0;
                return;
            }

            const dupeMap = {};
            const html = rows.map(r => {
                const key = hashNorm(String(r.seo_title || r.meta_title || '') + '|' + String(r.meta_desc || r.meta_description || ''));
                dupeMap[key] = (dupeMap[key] || 0) + 1;
                return this.rowHtml(r);
            }).join('');

            tbody.innerHTML = html;

            // Mark duplicates after render.
            tbody.querySelectorAll('tr').forEach(tr => {
                const h = tr.dataset?.hash || '';
                if (dupeMap[h] > 1) {
                    tr.querySelector('.column-seo-title .signals')
                        ?.insertAdjacentHTML('beforeend', badge('info', 'Duplicate title/description detected'));
                }
            });

            this.renderPagination();

            const totalEl = document.getElementById('total-items');
            if (totalEl) totalEl.textContent = this.totalItems || rows.length;

            document.querySelector('.almaseo-empty')?.classList.toggle('hidden', rows.length !== 0);
        },

        /**
         * Replace a single row from server payload, without reloading the page.
         */
        replaceRow: function(r) {
            if (!r || !r.id) return;
            const tbody = getOrCreateTbody();
            if (!tbody) return;
            const tr = tbody.querySelector(`tr[data-id="${CSS.escape(String(r.id))}"]`);
            if (!tr) return;
            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = this.rowHtml(r).trim();
            const newTr = wrapper.firstElementChild;
            if (newTr) tr.replaceWith(newTr);
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
            
            // Select all checkbox — ticks every visible row, then surfaces
            // the "select all N matching" banner when there are more pages.
            $('#select-all').on('change', function() {
                const checked = $(this).prop('checked');
                $('.post-checkbox').prop('checked', checked);
                self.updateSelectedCount();
                if (checked) {
                    self.maybeShowMatchAllBanner();
                } else {
                    self.clearMatchingSelection();
                }
            });
            
            // Delegate events for dynamic content. Manually toggling any
            // row exits the "select all matching" mode — if the user is
            // refining the selection, they probably no longer want every
            // matching item across pages.
            $(document).on('change', '.post-checkbox', function() {
                self.updateSelectedCount();
                self.clearMatchingSelection();
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
         * Load posts (re-fetch the current page with filters applied).
         * Clears any "select all matching" mode since pagination/filter
         * changes invalidate the previous match count.
         */
        async loadPosts() {
            this.clearMatchingSelection();
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
                per_page: 20,
            };
            Object.keys(params).forEach(k => { if (!params[k]) delete params[k]; });

            try {
                showOverlay();
                const result = await this.fetchPosts(params);
                this.totalPages = result.totalPages;
                this.totalItems = result.total;
                this.renderRows(result.items);
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
         * Save field. The PATCH endpoint returns the row's fresh payload,
         * which we use to refresh the single row in place — preserves
         * scroll position and avoids re-fetching the whole page.
         */
        async saveField($cell) {
            const id = $cell.data('id');
            const field = $cell.data('field');
            const value = $cell.find(field === 'title' ? '.meta-title-input' : '.meta-description-input').val();

            // Update displayed text immediately for snappy feel.
            $cell.find('.meta-field').text(value || $cell.find('input, textarea').attr('placeholder') || '');
            $cell.find('.field-display').show();
            $cell.find('.field-edit').hide();

            const key = id + '_' + field;
            if (this.saveTimers[key]) clearTimeout(this.saveTimers[key]);

            this.saveTimers[key] = setTimeout(async () => {
                try {
                    const data = field === 'title'
                        ? { meta_title: value }
                        : { meta_description: value };

                    const updated = await wp.apiFetch({
                        path: `/almaseo/v1/bulkmeta/${id}`,
                        method: 'PATCH',
                        data: data,
                    });

                    // Refresh row from server payload so gauges/badges/dupe
                    // marker stay in sync with the actual saved value.
                    if (updated && updated.id) {
                        this.replaceRow(updated);
                    }
                    this.showToast('Saved!', 'success');
                } catch (error) {
                    showError(`Failed to save: ${error?.message || 'Unknown error'}`);
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
         * Apply bulk action.
         *
         * Two modes:
         *   - per-page (default): operates on currently-checked rows only.
         *   - select-all-matching: operates on every post matching the
         *     current filter set, server-side. The mode is toggled by the
         *     "Select all N matching" banner that surfaces when the user
         *     ticks the header checkbox on a multi-page result.
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

            if (!this.selectAllMatching && selectedIds.length === 0) {
                showError('Please select at least one post');
                return;
            }

            // Op args
            const opArgs = {};
            if (action === 'append' || action === 'prepend') {
                opArgs.text = $('#bulk-text').val();
            } else if (action === 'replace') {
                opArgs.find    = $('#bulk-find').val();
                opArgs.replace = $('#bulk-replace').val();
            }

            const totalMatching = this.totalItems || selectedIds.length;
            const useMatchingMode = !!this.selectAllMatching;
            const visibleSelected = selectedIds.length;

            // Confirm copy — spell out the gap when the user's selection is
            // narrower than what their filters actually match.
            let confirmMsg;
            if (useMatchingMode) {
                confirmMsg = `Apply ${action} to all ${totalMatching} matching posts across every page? This cannot be undone.`;
            } else if (visibleSelected < totalMatching) {
                confirmMsg = `You've selected ${visibleSelected} of ${totalMatching} posts matching your filters.\n\nOnly the ${visibleSelected} on this page will be updated. Pages 2+ will NOT be touched.\n\nUse the "Select all ${totalMatching} matching" banner above the table to operate on every match.\n\nContinue with just these ${visibleSelected}?`;
            } else {
                confirmMsg = `Apply ${action} to ${visibleSelected} posts?`;
            }
            if (!confirm(confirmMsg)) return;

            try {
                showOverlay();
                let result;

                if (useMatchingMode) {
                    // Server-side bulk-all — filter spec + op, one round-trip.
                    const data = {
                        op: action,
                        field: $('#bulk-field').val(),
                        args: opArgs,
                        filters: this.getCurrentFilterParams(),
                    };
                    result = await wp.apiFetch({
                        path: '/almaseo/v1/bulkmeta/bulk-all',
                        method: 'POST',
                        data: data,
                    });
                } else {
                    const data = {
                        ids: selectedIds,
                        op: action,
                        field: $('#bulk-field').val(),
                        args: opArgs,
                    };
                    result = await wp.apiFetch({
                        path: '/almaseo/v1/bulkmeta/bulk',
                        method: 'POST',
                        data: data,
                    });
                }

                let msg = `Bulk operation completed: ${result.success || 0} succeeded`;
                if (result.failed)  msg += `, ${result.failed} failed`;
                if (result.skipped) msg += `, ${result.skipped} skipped`;

                if (!useMatchingMode) {
                    const remaining = totalMatching - visibleSelected;
                    if (remaining > 0) {
                        msg += `. ${remaining} more items match your filters but weren't on this page — use the "Select all matching" banner to do the rest.`;
                    }
                }

                this.showToast(msg, 'success');
                this.clearMatchingSelection();
                this.loadPosts();
            } catch (error) {
                showError(`Bulk operation failed: ${error?.message || 'Unknown error'}`);
            } finally {
                hideOverlay();
            }
        },

        /**
         * Build a filter-spec object reflecting the current table state.
         * Mirrors the shape loadPosts() sends to the list endpoint and is
         * sent to /bulkmeta/bulk-all so the server-side query matches what
         * the user sees on screen.
         */
        getCurrentFilterParams: function() {
            const params = {
                type:     $('#post-type-filter').val() || 'post,page',
                status:   $('#status-filter').val() || 'publish,draft',
                taxonomy: $('#taxonomy-filter').val() || '',
                term:     $('#term-filter').val() || '',
                from:     $('#date-from').val() || '',
                to:       $('#date-to').val() || '',
                search:   $('#search-box').val() || '',
                missing:  $('#missing-only').prop('checked'),
            };
            Object.keys(params).forEach(k => {
                if (params[k] === '' || params[k] === false) delete params[k];
            });
            return params;
        },

        clearMatchingSelection: function() {
            this.selectAllMatching = false;
            $('#almaseo-match-all-banner').remove();
        },

        /**
         * After the user ticks the header checkbox, surface a banner
         * letting them widen the selection to every match across pages
         * (the "Select all 47 matching" affordance core WordPress uses on
         * the Posts screen). Only shown when totalItems > visible count.
         */
        maybeShowMatchAllBanner: function() {
            const visible = $('.post-checkbox').length;
            const total   = this.totalItems || 0;
            if (total <= visible) return;

            // Remove any previous instance so the counts stay accurate as
            // pages/filters change.
            $('#almaseo-match-all-banner').remove();

            const self = this;
            const banner = $(`
                <div id="almaseo-match-all-banner" class="notice notice-info" style="margin: 6px 0; padding: 10px 14px; display: flex; align-items: center; gap: 10px;">
                    <span class="dashicons dashicons-info" style="color: #2271b1;"></span>
                    <span>All <strong>${visible}</strong> on this page are selected. </span>
                    <button type="button" class="button button-small button-link" id="almaseo-match-all-activate">Select all ${total} matching items across pages</button>
                </div>
            `);
            banner.on('click', '#almaseo-match-all-activate', function() {
                self.selectAllMatching = true;
                banner.html(`
                    <span class="dashicons dashicons-yes-alt" style="color: #00a32a;"></span>
                    <span>All <strong>${total}</strong> matching items selected across every page. </span>
                    <button type="button" class="button button-small button-link" id="almaseo-match-all-clear">Clear selection</button>
                `);
            });
            banner.on('click', '#almaseo-match-all-clear', function() {
                $('#select-all').prop('checked', false);
                $('.post-checkbox').prop('checked', false);
                self.updateSelectedCount();
                self.clearMatchingSelection();
            });

            $('.bulk-actions-wrapper').show().before(banner);
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
    
    // ===================================================================
    // Auto-Fill Module
    // ===================================================================
    const AutoFill = {
        pendingIds: [],

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            const self = this;

            // Auto-Fill Selected — overwrites checked rows
            $('#autofill-selected').on('click', function() {
                const ids = self.getSelectedIds();
                if (ids.length === 0) {
                    BulkMetaEditor.showToast('Please select at least one post', 'error');
                    return;
                }
                if (!confirm(`Regenerate metadata for ${ids.length} selected post(s)? This will overwrite existing metadata.`)) {
                    return;
                }
                self.runAutofill(ids, true);
            });

            // Auto-Fill All Empty — only fills posts with missing metadata
            $('#autofill-all-empty').on('click', function() {
                if (!confirm('This will scan the entire site and fill metadata only for posts/pages that have empty fields. Existing metadata will NOT be changed. Continue?')) {
                    return;
                }
                self.autofillAllEmpty();
            });

            // Auto-Fill Entire Site — overwrites everything
            $('#autofill-entire-site').on('click', function() {
                if (!confirm('⚠️ This will REGENERATE metadata for EVERY post and page on your site, overwriting all existing titles and descriptions.\n\nThis cannot be undone. Are you sure?')) {
                    return;
                }
                // Double confirm for safety
                if (!confirm('Final confirmation: Overwrite ALL metadata on the entire site?')) {
                    return;
                }
                self.autofillEntireSite();
            });

            // Preview — shows what would be generated for selected posts
            $('#autofill-preview').on('click', function() {
                const ids = self.getSelectedIds();
                if (ids.length === 0) {
                    BulkMetaEditor.showToast('Please select at least one post to preview', 'error');
                    return;
                }
                self.showPreview(ids);
            });

            // Modal close
            $(document).on('click', '.autofill-modal-close', function() {
                $('#autofill-preview-modal').hide();
            });

            // Confirm apply from preview modal
            $('#autofill-confirm-apply').on('click', function() {
                if (self.pendingIds.length > 0) {
                    $('#autofill-preview-modal').hide();
                    self.runAutofill(self.pendingIds, true);
                }
            });
        },

        getSelectedIds: function() {
            const ids = [];
            $('.post-checkbox:checked').each(function() {
                ids.push(parseInt($(this).val(), 10));
            });
            return ids;
        },

        /**
         * Resolved autofill mode: user's selection in #autofill-mode, falling
         * back to 'auto' (server decides Alma vs Basic based on connection).
         */
        getMode: function() {
            const $sel = $('#autofill-mode');
            const v = $sel.length ? String($sel.val() || '').trim() : '';
            if (v === 'basic' || v === 'ai' || v === 'auto') return v;
            return 'auto';
        },

        /** True when the resolved mode will hit the Alma API. */
        modeUsesAi: function() {
            const m = this.getMode();
            if (m === 'basic') return false;
            if (m === 'ai') return true;
            return AI_AVAILABLE; // 'auto'
        },

        async runAutofill(ids, overwrite = false) {
            const $status = $('#autofill-status');
            const useAi = this.modeUsesAi();
            const modeLabel = useAi ? 'Alma-generating' : 'Generating';

            $status.html('<span class="dashicons dashicons-update" style="animation:rotation 1s linear infinite;font-size:14px;vertical-align:middle;"></span> ' + modeLabel + ' metadata...').css('color', '#2271b1');
            $('#autofill-selected, #autofill-all-empty, #autofill-preview, #autofill-entire-site').prop('disabled', true);

            try {
                const result = await wp.apiFetch({
                    path: '/almaseo/v1/bulkmeta/autofill',
                    method: 'POST',
                    data: {
                        ids: ids,
                        fields: [],
                        overwrite: overwrite,
                        mode: this.getMode(),
                    },
                });

                const aiTag = result.ai_used ? ' (Alma)' : '';
                const msg = `Auto-filled ${result.success} post(s)${aiTag}` +
                    (result.skipped ? `, ${result.skipped} skipped` : '') +
                    (result.failed ? `, ${result.failed} failed` : '');

                this.showResultBanner(msg, 'success');
                $status.html('<span class="dashicons dashicons-yes-alt" style="color:#00a32a;font-size:14px;vertical-align:middle;"></span> ' + escHtml(msg)).css('color', '#00a32a');

                BulkMetaEditor.loadPosts();
            } catch (error) {
                const msg = `Auto-fill failed: ${error?.message || 'Unknown error'}`;
                this.showResultBanner(msg, 'error');
                $status.html('<span class="dashicons dashicons-warning" style="color:#d63638;font-size:14px;vertical-align:middle;"></span> ' + escHtml(msg)).css('color', '#d63638');
            } finally {
                $('#autofill-selected, #autofill-all-empty, #autofill-preview, #autofill-entire-site').prop('disabled', false);
            }
        },

        /**
         * Walk all posts/pages via the bulk-meta list endpoint. Reads the
         * X-WP-Total/TotalPages headers so the loop terminates correctly
         * instead of relying on a hardcoded page cap. `predicate(row)` is
         * called per row; rows for which it returns true are collected.
         */
        async collectIds(predicate, onProgress) {
            const ids = [];
            let scanned = 0;
            let page = 1;
            let knownTotalPages = null;

            while (true) {
                const result = await BulkMetaEditor.fetchPosts({
                    per_page: 100,
                    page: page,
                    type: 'post,page',
                    status: 'publish,draft',
                });
                if (knownTotalPages === null) knownTotalPages = result.totalPages || 1;
                if (result.items.length === 0) break;

                scanned += result.items.length;
                result.items.forEach(r => {
                    if (predicate(r)) ids.push(r.id);
                });
                if (typeof onProgress === 'function') onProgress(scanned, result.total);

                if (page >= knownTotalPages) break;
                page++;
            }
            return { ids, scanned };
        },

        /**
         * Process a list of post IDs through the autofill endpoint in batches.
         * Updates the status bar as batches complete.
         */
        async processBatches(allIds, overwrite, $status, statusPrefix) {
            const useAi = this.modeUsesAi();
            const batchSize = useAi ? 10 : 20;
            const totalBatches = Math.ceil(allIds.length / batchSize);
            const mode = this.getMode();
            let totalSuccess = 0, totalFailed = 0, totalSkipped = 0, aiWasUsed = false;

            for (let i = 0; i < allIds.length; i += batchSize) {
                const batch = allIds.slice(i, i + batchSize);
                const batchNum = Math.floor(i / batchSize) + 1;
                $status.html(`<span class="dashicons dashicons-update" style="animation:rotation 1s linear infinite;font-size:14px;vertical-align:middle;"></span> ${escHtml(statusPrefix)} batch ${batchNum} of ${totalBatches} (${i + batch.length}/${allIds.length} posts)...`);

                const result = await wp.apiFetch({
                    path: '/almaseo/v1/bulkmeta/autofill',
                    method: 'POST',
                    data: { ids: batch, fields: [], overwrite, mode },
                });

                totalSuccess += result.success || 0;
                totalFailed  += result.failed  || 0;
                totalSkipped += result.skipped || 0;
                if (result.ai_used) aiWasUsed = true;
            }
            return { totalSuccess, totalFailed, totalSkipped, aiWasUsed };
        },

        async autofillAllEmpty() {
            const $status = $('#autofill-status');
            const allBtns = '#autofill-all-empty, #autofill-selected, #autofill-preview, #autofill-entire-site';
            $(allBtns).prop('disabled', true);
            $status.html('<span class="dashicons dashicons-update" style="animation:rotation 1s linear infinite;font-size:14px;vertical-align:middle;"></span> Scanning for posts with empty metadata...').css('color', '#2271b1');

            try {
                const { ids: allIds, scanned } = await this.collectIds(
                    r => !!(r.title_fallback) || (r.title_chars === 0) || !!(r.desc_fallback) || (r.desc_chars === 0),
                    (s) => $status.html(`<span class="dashicons dashicons-update" style="animation:rotation 1s linear infinite;font-size:14px;vertical-align:middle;"></span> Scanned ${s} posts...`)
                );

                if (allIds.length === 0) {
                    this.showResultBanner(`Scanned ${scanned} posts — all already have metadata!`, 'success');
                    $status.html('<span class="dashicons dashicons-yes-alt" style="color:#00a32a;font-size:14px;vertical-align:middle;"></span> All posts already have metadata.').css('color', '#00a32a');
                    return;
                }

                $status.html(`<span class="dashicons dashicons-update" style="animation:rotation 1s linear infinite;font-size:14px;vertical-align:middle;"></span> Found ${allIds.length} post(s) with empty metadata. Generating...`);
                const { totalSuccess, totalFailed, aiWasUsed } = await this.processBatches(allIds, false, $status, 'Processing');

                const aiTag = aiWasUsed ? ' (Alma)' : '';
                const msg = `Done! Auto-filled ${totalSuccess} post(s)${aiTag}` + (totalFailed ? `, ${totalFailed} failed` : '');
                this.showResultBanner(msg, 'success');
                $status.html('<span class="dashicons dashicons-yes-alt" style="color:#00a32a;font-size:14px;vertical-align:middle;"></span> ' + escHtml(msg)).css('color', '#00a32a');
                BulkMetaEditor.loadPosts();
            } catch (error) {
                const msg = `Auto-fill failed: ${error?.message || 'Unknown error'}`;
                this.showResultBanner(msg, 'error');
                $status.html('<span class="dashicons dashicons-warning" style="color:#d63638;font-size:14px;vertical-align:middle;"></span> ' + escHtml(msg)).css('color', '#d63638');
            } finally {
                $(allBtns).prop('disabled', false);
            }
        },

        async autofillEntireSite() {
            const $status = $('#autofill-status');
            const allBtns = '#autofill-all-empty, #autofill-selected, #autofill-preview, #autofill-entire-site';
            $(allBtns).prop('disabled', true);
            $status.html('<span class="dashicons dashicons-update" style="animation:rotation 1s linear infinite;font-size:14px;vertical-align:middle;"></span> Collecting all posts and pages...').css('color', '#2271b1');

            try {
                const { ids: allIds } = await this.collectIds(
                    () => true,
                    (s) => $status.html(`<span class="dashicons dashicons-update" style="animation:rotation 1s linear infinite;font-size:14px;vertical-align:middle;"></span> Found ${s} posts so far...`)
                );

                if (allIds.length === 0) {
                    this.showResultBanner('No posts or pages found on the site.', 'error');
                    $status.text('No posts found.');
                    return;
                }

                if (allIds.length > ENTIRE_SITE_MAX) {
                    const msg = `Refusing to run: site has ${allIds.length} posts/pages, above the safety ceiling of ${ENTIRE_SITE_MAX}. Use "Auto-Fill All Empty" or filter the table and use "Auto-Fill Selected" instead.`;
                    this.showResultBanner(msg, 'error');
                    $status.html('<span class="dashicons dashicons-warning" style="color:#d63638;font-size:14px;vertical-align:middle;"></span> ' + escHtml(`Site too large (${allIds.length} > ${ENTIRE_SITE_MAX}). Aborted.`)).css('color', '#d63638');
                    return;
                }

                $status.html(`<span class="dashicons dashicons-update" style="animation:rotation 1s linear infinite;font-size:14px;vertical-align:middle;"></span> Regenerating metadata for ${allIds.length} posts...`);
                const { totalSuccess, totalFailed, aiWasUsed } = await this.processBatches(allIds, true, $status, 'Processing');

                const aiTag = aiWasUsed ? ' (Alma)' : '';
                const msg = `Done! Regenerated metadata for ${totalSuccess} post(s)${aiTag}` + (totalFailed ? `, ${totalFailed} failed` : '');
                this.showResultBanner(msg, 'success');
                $status.html('<span class="dashicons dashicons-yes-alt" style="color:#00a32a;font-size:14px;vertical-align:middle;"></span> ' + escHtml(msg)).css('color', '#00a32a');
                BulkMetaEditor.loadPosts();
            } catch (error) {
                const msg = `Auto-fill failed: ${error?.message || 'Unknown error'}`;
                this.showResultBanner(msg, 'error');
                $status.html('<span class="dashicons dashicons-warning" style="color:#d63638;font-size:14px;vertical-align:middle;"></span> ' + escHtml(msg)).css('color', '#d63638');
            } finally {
                $(allBtns).prop('disabled', false);
            }
        },

        showResultBanner(message, type) {
            // Remove any previous banner
            $('#autofill-result-banner').remove();

            const isSuccess = type === 'success';
            const icon = isSuccess ? 'dashicons-yes-alt' : 'dashicons-warning';
            const bgColor = isSuccess ? '#edfaef' : '#fcf0f1';
            const borderColor = isSuccess ? '#00a32a' : '#d63638';
            const textColor = isSuccess ? '#00450a' : '#8a1116';

            const banner = $(`
                <div id="autofill-result-banner" style="
                    margin: 12px 0;
                    padding: 12px 18px;
                    background: ${bgColor};
                    border: 1px solid ${borderColor};
                    border-left: 4px solid ${borderColor};
                    border-radius: 4px;
                    color: ${textColor};
                    font-size: 14px;
                    font-weight: 500;
                    display: flex;
                    align-items: center;
                    gap: 8px;
                ">
                    <span class="dashicons ${icon}" style="font-size: 20px; color: ${borderColor};"></span>
                    <span>${escHtml(message)}</span>
                    <button type="button" class="autofill-banner-close" style="margin-left: auto; background: none; border: none; cursor: pointer; color: ${textColor}; font-size: 18px; padding: 0 4px;">&times;</button>
                </div>
            `);
            banner.on('click', '.autofill-banner-close', function() { banner.remove(); });

            // Insert before the table
            $('.almaseo-bulkmeta-wrapper').before(banner);

            // Auto-remove after 15 seconds
            setTimeout(() => banner.fadeOut(400, function() { $(this).remove(); }), 15000);
        },

        async showPreview(ids) {
            this.pendingIds = ids;
            const overwrite = $('#autofill-overwrite').prop('checked');
            const $modal = $('#autofill-preview-modal');
            const $body = $('#autofill-preview-body');

            $body.html('<p>Loading preview...</p>');
            $modal.show();

            try {
                const previews = await wp.apiFetch({
                    path: '/almaseo/v1/bulkmeta/autofill/preview',
                    method: 'POST',
                    data: {
                        ids: ids,
                        fields: [],
                        overwrite: overwrite,
                        mode: this.getMode(),
                    },
                });

                if (!previews || previews.length === 0) {
                    $body.html('<p>No posts to preview.</p>');
                    return;
                }

                let html = '<table class="wp-list-table widefat fixed striped" style="font-size:13px;">';
                html += '<thead><tr>';
                html += '<th style="width:20%;">Post</th>';
                html += '<th style="width:25%;">Field</th>';
                html += '<th style="width:25%;">Current</th>';
                html += '<th style="width:25%;">Generated</th>';
                html += '<th style="width:5%;">Action</th>';
                html += '</tr></thead><tbody>';

                previews.forEach(p => {
                    const fields = ['meta_title', 'meta_description', 'focus_keyword', 'og_title', 'og_description'];
                    const labels = {
                        meta_title: 'SEO Title',
                        meta_description: 'Meta Description',
                        focus_keyword: 'Focus Keyword',
                        og_title: 'OG Title',
                        og_description: 'OG Description'
                    };

                    let firstRow = true;
                    fields.forEach(f => {
                        if (!p[f]) return;
                        const d = p[f];
                        const actionIcon = d.will_fill
                            ? '<span class="dashicons dashicons-yes-alt" style="color:#00a32a;" title="Will be filled"></span>'
                            : '<span class="dashicons dashicons-minus" style="color:#999;" title="Already has value — will skip"></span>';
                        const currentStyle = d.current ? '' : 'color:#d63638;font-style:italic;';
                        const currentText = d.current ? escHtml(d.current) : '(empty)';

                        html += '<tr>';
                        if (firstRow) {
                            html += `<td rowspan="${fields.filter(ff => p[ff]).length}"><strong>${escHtml(p.title)}</strong></td>`;
                            firstRow = false;
                        }
                        html += `<td>${escHtml(labels[f] || f)}</td>`;
                        html += `<td style="${currentStyle}">${currentText}</td>`;
                        html += `<td style="color:#2271b1;">${escHtml(d.generated)}</td>`;
                        html += `<td>${actionIcon}</td>`;
                        html += '</tr>';
                    });
                });

                html += '</tbody></table>';
                $body.html(html);

            } catch (error) {
                const safeMsg = escHtml(error?.message || 'Unknown error');
                $body.html(`<p class="notice notice-error" style="padding:10px;">Preview failed: ${safeMsg}</p>`);
            }
        }
    };

    // Initialize when DOM is ready
    if (typeof jQuery !== 'undefined') {
        jQuery(function($) {
            if ($('.almaseo-bulk-meta').length > 0 || $('#bulkmeta-table').length > 0 || $('#almaseo-bulkmeta').length > 0) {
                BulkMetaEditor.init();
                AutoFill.init();
            }
        });
    } else {
        console.error('jQuery is not loaded - BulkMeta cannot initialize');
    }
    
})(typeof jQuery !== 'undefined' ? jQuery : null);