/**
 * AlmaSEO Sitemaps Tabbed Interface V3 - With Arrow Keys & Live Stats
 * 
 * @package AlmaSEO
 */

// STEP 6: Ensure jQuery is loaded before running
jQuery(function($) {
    'use strict';
    
    // Helper functions
    const qs = (s, c = document) => c.querySelector(s);
    const qsa = (s, c = document) => Array.from(c.querySelectorAll(s));
    
    // Get boot state
    const state = (window.__ALMA_BOOT && window.__ALMA_BOOT.loaded) || {};
    const ajaxUrl = (window.__ALMA_BOOT && window.__ALMA_BOOT.ajaxUrl) || window.ajaxurl;
    const nonce = (window.__ALMA_BOOT && window.__ALMA_BOOT.nonce) || '';
    
    // Get container
    const container = qs('#almaseo-admin');
    if (!container) return;
    
    // Get tabs and panels
    const tabsNav = qs('[data-tabs-container]', container);
    const tabs = qsa('[role="tab"]', container);
    const panels = qsa('.alma-tabpanel', container);
    let currentTabIndex = tabs.findIndex(t => t.classList.contains('active'));
    
    /**
     * Show a specific tab
     */
    function show(tabKey) {
        // Update ARIA states
        tabs.forEach((t, index) => {
            const isActive = t.dataset.tab === tabKey;
            t.setAttribute('aria-selected', isActive ? 'true' : 'false');
            t.setAttribute('tabindex', isActive ? '0' : '-1');
            t.classList.toggle('active', isActive);
            if (isActive) currentTabIndex = index;
        });
        
        // Show/hide panels (keep DOM, just toggle visibility)
        panels.forEach(p => {
            p.hidden = (p.dataset.tab !== tabKey);
        });
        
        // Update URL without reload
        const url = new URL(window.location.href);
        url.searchParams.set('tab', tabKey);
        history.replaceState(null, '', url.toString());
        
        // Lazy-load content if not already loaded
        if (!state[tabKey]) {
            loadTabContent(tabKey);
        }
    }
    
    /**
     * Lazy load tab content
     */
    function loadTabContent(tabKey) {
        const panel = qs('#alma-panel-' + tabKey);
        if (!panel) return;
        
        // Check if there's a placeholder
        const placeholder = qs('.alma-tab-placeholder', panel);
        if (!placeholder) return; // Already loaded
        
        // Show loading skeleton
        placeholder.innerHTML = '<div class="alma-skel"></div>';
        
        // Fetch content via AJAX
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'almaseo_load_tab',
                tab: tabKey,
                _ajax_nonce: nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            // Check if response was successful
            if (data.success && data.data && data.data.content) {
                // Replace placeholder with actual content
                panel.innerHTML = data.data.content;
                state[tabKey] = true;
                
                // Trigger event for any tab-specific JS initialization
                document.dispatchEvent(new CustomEvent('almaseo:tab:loaded', {
                    detail: { tab: tabKey }
                }));
            } else {
                // Handle error response
                const errorMsg = data.data && data.data.message ? data.data.message : 'Failed to load tab content';
                panel.innerHTML = '<div class="alma-empty">' + errorMsg + '</div>';
            }
        })
        .catch(error => {
            console.error('Failed to load tab:', error);
            panel.innerHTML = '<div class="alma-empty">Failed to load tab content. Please refresh the page.</div>';
        });
    }
    
    /**
     * Handle arrow key navigation
     */
    function handleKeyNavigation(e) {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(e.key)) return;
        
        e.preventDefault();
        let newIndex = currentTabIndex;
        
        switch (e.key) {
            case 'ArrowLeft':
                newIndex = currentTabIndex > 0 ? currentTabIndex - 1 : tabs.length - 1;
                break;
            case 'ArrowRight':
                newIndex = currentTabIndex < tabs.length - 1 ? currentTabIndex + 1 : 0;
                break;
            case 'Home':
                newIndex = 0;
                break;
            case 'End':
                newIndex = tabs.length - 1;
                break;
        }
        
        if (newIndex !== currentTabIndex) {
            const newTab = tabs[newIndex];
            newTab.focus();
            newTab.click();
        }
    }
    
    /**
     * Handle tab clicks
     */
    function handleTabClick(e) {
        const tab = e.currentTarget;
        if (!tab.dataset.tab) return;
        
        show(tab.dataset.tab);
    }
    
    /**
     * Copy to clipboard handler
     */
    function handleCopyClick(e) {
        const button = e.currentTarget;
        const url = button.dataset.url;
        if (!url) return;
        
        // Create temporary input
        const temp = document.createElement('input');
        document.body.appendChild(temp);
        temp.value = url;
        temp.select();
        document.execCommand('copy');
        document.body.removeChild(temp);
        
        // Visual feedback
        const orig = button.innerHTML;
        button.innerHTML = '<span class="dashicons dashicons-yes"></span>';
        button.style.color = '#10b981';
        
        setTimeout(() => {
            button.innerHTML = orig;
            button.style.color = '';
        }, 1500);
    }
    
    /**
     * Update live stats
     */
    function updateLiveStats() {
        // Check for build lock
        fetch(ajaxUrl + '?action=almaseo_check_build_lock&_wpnonce=' + nonce)
            .then(response => response.json())
            .then(data => {
                const isBuilding = data.data && data.data.locked;
                
                // Update spinner visibility
                const spinner = qs('.build-spinner');
                if (spinner) {
                    spinner.style.display = isBuilding ? 'inline-block' : 'none';
                }
                
                // If building, check again in 2 seconds
                if (isBuilding) {
                    setTimeout(updateLiveStats, 2000);
                }
            });
        
        // Fetch live stats
        fetch(ajaxUrl + '?action=almaseo_get_live_stats&_wpnonce=' + nonce)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.data) {
                    // Update file count
                    const filesChip = qs('[data-live-stat="files"]');
                    if (filesChip) {
                        const number = qs('.stat-number', filesChip);
                        if (number) number.textContent = data.data.files || '0';
                    }
                    
                    // Update URL count
                    const urlsChip = qs('[data-live-stat="urls"]');
                    if (urlsChip) {
                        const number = qs('.stat-number', urlsChip);
                        if (number) number.textContent = data.data.urls || '0';
                    }
                }
            })
            .catch(error => {
                console.error('Failed to update stats:', error);
            });
    }
    
    /**
     * Handle rebuild button
     */
    function handleRebuild(e) {
        const button = e.currentTarget;
        const origText = button.innerHTML;
        
        button.disabled = true;
        button.innerHTML = '<span class="spinner is-active" style="margin-right: 4px;"></span> Building...';
        
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded'
            },
            body: new URLSearchParams({
                action: 'almaseo_rebuild_static',
                _ajax_nonce: nonce
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show success toast
                showToast('Sitemap rebuilt successfully', 'success');
                // Update stats
                setTimeout(updateLiveStats, 1000);
            } else {
                showToast(data.data || 'Failed to rebuild sitemap', 'error');
            }
        })
        .catch(error => {
            console.error('Rebuild failed:', error);
            showToast('Failed to rebuild sitemap', 'error');
        })
        .finally(() => {
            button.disabled = false;
            button.innerHTML = origText;
        });
    }
    
    /**
     * Show toast notification
     */
    function showToast(message, type = 'info') {
        let container = qs('#almaseo-toast-container');
        if (!container) {
            container = document.createElement('div');
            container.id = 'almaseo-toast-container';
            document.body.appendChild(container);
        }
        
        const toast = document.createElement('div');
        toast.className = 'almaseo-toast almaseo-toast-' + type;
        
        const icon = type === 'success' ? 'yes' : (type === 'error' ? 'warning' : 'info');
        toast.innerHTML = `
            <span class="dashicons dashicons-${icon}"></span>
            <span>${message}</span>
        `;
        
        container.appendChild(toast);
        
        // Auto-remove after 3 seconds
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
    
    /**
     * Initialize
     */
    function init() {
        // Bind tab click handlers
        tabs.forEach(tab => {
            tab.addEventListener('click', handleTabClick);
            tab.addEventListener('keydown', handleKeyNavigation);
        });
        
        // Set initial tabindex
        tabs.forEach((tab, index) => {
            tab.setAttribute('tabindex', index === currentTabIndex ? '0' : '-1');
        });
        
        // Bind copy URL button
        const copyBtn = qs('#copy-sitemap-url');
        if (copyBtn) {
            copyBtn.addEventListener('click', handleCopyClick);
        }
        
        // Bind rebuild button
        const rebuildBtn = qs('#rebuild-sitemaps');
        if (rebuildBtn) {
            rebuildBtn.addEventListener('click', handleRebuild);
        }
        
        // Initialize live stats
        updateLiveStats();
        
        // Update stats every 30 seconds
        setInterval(updateLiveStats, 30000);
        
        // Check current tab from URL
        const url = new URL(window.location.href);
        const tabParam = url.searchParams.get('tab');
        
        if (tabParam && tabs.some(t => t.dataset.tab === tabParam)) {
            show(tabParam);
        }
        
        // Handle browser back/forward
        window.addEventListener('popstate', () => {
            const url = new URL(window.location.href);
            const tabParam = url.searchParams.get('tab');
            if (tabParam && tabs.some(t => t.dataset.tab === tabParam)) {
                show(tabParam);
            }
        });
    }
    
    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
    
}); // End jQuery wrapper