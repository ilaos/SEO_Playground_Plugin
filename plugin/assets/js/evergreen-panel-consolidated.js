/**
 * AlmaSEO Evergreen Panel - Enhanced Version v2 with Better Error Handling
 * 
 * @package AlmaSEO
 * @since 4.2.1
 */

(function(wp, jQuery) {
    'use strict';
    
    const { registerPlugin } = wp.plugins;
    const { PluginDocumentSettingPanel } = wp.editor || wp.editPost || {};
    const { Fragment, createElement: el, useState, useEffect } = wp.element;
    const { __ } = wp.i18n;
    const { Button, Spinner, Notice } = wp.components;
    const { useSelect, useDispatch } = wp.data;
    const { apiFetch } = wp;
    
    // Check if we have the required component
    if (!PluginDocumentSettingPanel) {
        console.error('[AlmaSEO] PluginDocumentSettingPanel not available');
        return;
    }
    
    /**
     * Format relative time
     */
    const formatRelativeTime = (timestamp) => {
        if (!timestamp) return __('Not yet analyzed', 'almaseo');
        
        const date = new Date(timestamp * 1000);
        const now = new Date();
        const diffMs = now - date;
        const diffSecs = Math.floor(diffMs / 1000);
        const diffMins = Math.floor(diffSecs / 60);
        const diffHours = Math.floor(diffMins / 60);
        const diffDays = Math.floor(diffHours / 24);
        
        if (diffSecs < 60) {
            return __('Just now', 'almaseo');
        } else if (diffMins < 60) {
            return sprintf(__('%d minute%s ago', 'almaseo'), diffMins, diffMins !== 1 ? 's' : '');
        } else if (diffHours < 24) {
            return sprintf(__('%d hour%s ago', 'almaseo'), diffHours, diffHours !== 1 ? 's' : '');
        } else if (diffDays < 30) {
            return sprintf(__('%d day%s ago', 'almaseo'), diffDays, diffDays !== 1 ? 's' : '');
        } else {
            return date.toLocaleDateString();
        }
    };
    
    // Helper for sprintf-like functionality
    const sprintf = (str, ...args) => {
        return str.replace(/%[sd]/g, (match) => {
            const arg = args.shift();
            return arg !== undefined ? arg : match;
        });
    };
    
    /**
     * Enhanced Evergreen Panel Component
     */
    const EvergreenPanel = () => {
        const [status, setStatus] = useState('unknown');
        const [lastCalculated, setLastCalculated] = useState(null);
        const [isAnalyzing, setIsAnalyzing] = useState(false);
        const [isMarkingRefreshed, setIsMarkingRefreshed] = useState(false);
        const [notice, setNotice] = useState(null);
        const [loading, setLoading] = useState(true);
        const [autoAnalyze, setAutoAnalyze] = useState(() => {
            // Get saved preference or default to true
            const saved = localStorage.getItem('almaseo_evergreen_auto_analyze');
            return saved !== 'false';
        });
        
        const { postId, postType, postStatus, isSaving, isAutosaving } = useSelect((select) => {
            const editor = select('core/editor');
            return {
                postId: editor.getCurrentPostId(),
                postType: editor.getCurrentPostType(),
                postStatus: editor.getEditedPostAttribute('status'),
                isSaving: editor.isSavingPost(),
                isAutosaving: editor.isAutosavingPost()
            };
        }, []);
        
        const { editPost } = useDispatch('core/editor');
        
        // Load initial status
        useEffect(() => {
            if (!postId || postId === 0) {
                setLoading(false);
                return;
            }
            
            loadStatus();
            
            // Set up periodic refresh for relative times
            const interval = setInterval(() => {
                // Force re-render to update relative time
                setLastCalculated(prev => prev ? {...prev} : prev);
            }, 60000); // Update every minute
            
            return () => clearInterval(interval);
        }, [postId]);
        
        // Auto-analyze on save if enabled
        useEffect(() => {
            if (!isSaving && !isAutosaving && autoAnalyze && postId) {
                // Check if we just finished saving
                const wasSaving = localStorage.getItem('almaseo_evergreen_was_saving');
                if (wasSaving === 'true') {
                    localStorage.removeItem('almaseo_evergreen_was_saving');
                    // Trigger auto-analyze after save
                    setTimeout(() => {
                        console.log('[AlmaSEO] Auto-analyzing after save');
                        handleAnalyzeNow();
                    }, 500);
                }
            } else if (isSaving && !isAutosaving) {
                // Mark that we're saving
                localStorage.setItem('almaseo_evergreen_was_saving', 'true');
            }
        }, [isSaving, isAutosaving, autoAnalyze, postId]);
        
        /**
         * Load status from server
         */
        const loadStatus = async () => {
            if (!postId || postId === 0) {
                setLoading(false);
                setStatus('unknown');
                return;
            }
            
            setLoading(true);
            
            try {
                console.log('[AlmaSEO] Loading status for post:', postId);
                const response = await apiFetch({
                    path: `/almaseo/v1/evergreen/status/${postId}`,
                    method: 'GET',
                    headers: {
                        'X-WP-Nonce': almaseoEvergreenSettings.nonce
                    }
                });
                
                console.log('[AlmaSEO] Status response:', response);
                
                if (response && response.success) {
                    setStatus(response.data.status || 'unknown');
                    setLastCalculated(response.data.last_calculated);
                } else {
                    setStatus('unknown');
                }
            } catch (err) {
                console.error('[AlmaSEO] Error loading evergreen status:', err);
                setStatus('unknown');
            } finally {
                setLoading(false);
            }
        };
        
        /**
         * Analyze Now - Recalculate status
         */
        const handleAnalyzeNow = async () => {
            if (!postId || postId === 0) {
                setNotice({
                    type: 'error',
                    message: __('Please save the post first before analyzing', 'almaseo')
                });
                return;
            }
            
            setIsAnalyzing(true);
            setNotice(null);
            
            try {
                console.log('[AlmaSEO] Analyzing post:', postId);
                
                const response = await apiFetch({
                    path: `/almaseo/v1/evergreen/recalculate/${postId}`,
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': almaseoEvergreenSettings.nonce
                    }
                });
                
                console.log('[AlmaSEO] Analyze response:', response);
                
                if (response && response.success) {
                    // Update local state immediately
                    const newStatus = response.data.status || 'evergreen';
                    const newTimestamp = Math.floor(Date.now() / 1000);
                    
                    setStatus(newStatus);
                    setLastCalculated(newTimestamp);
                    
                    setNotice({
                        type: 'success',
                        message: __('Evergreen status analyzed successfully!', 'almaseo')
                    });
                    
                    // Update post meta
                    editPost({
                        meta: {
                            '_almaseo_evergreen_status': newStatus,
                            '_almaseo_evergreen_last_calculated': newTimestamp
                        }
                    });
                    
                    // Clear notice after 3 seconds
                    setTimeout(() => setNotice(null), 3000);
                } else {
                    const errorMsg = response?.message || __('Failed to analyze status', 'almaseo');
                    console.error('[AlmaSEO] Analyze failed:', errorMsg);
                    setNotice({
                        type: 'error',
                        message: errorMsg
                    });
                }
            } catch (err) {
                console.error('[AlmaSEO] Error analyzing status:', err);
                const errorMsg = err?.message || __('Failed to analyze status. Check console for details.', 'almaseo');
                setNotice({
                    type: 'error',
                    message: errorMsg
                });
            } finally {
                setIsAnalyzing(false);
            }
        };
        
        /**
         * Mark Refreshed - Update content freshness
         */
        const handleMarkRefreshed = async () => {
            if (!postId || postId === 0) {
                setNotice({
                    type: 'error',
                    message: __('Please save the post first', 'almaseo')
                });
                return;
            }
            
            setIsMarkingRefreshed(true);
            setNotice(null);
            
            try {
                console.log('[AlmaSEO] Marking post as refreshed:', postId);
                
                const response = await apiFetch({
                    path: `/almaseo/v1/evergreen/mark-refreshed/${postId}`,
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-WP-Nonce': almaseoEvergreenSettings.nonce
                    }
                });
                
                console.log('[AlmaSEO] Mark refreshed response:', response);
                
                if (response && response.success) {
                    // Update local state immediately
                    const newTimestamp = Math.floor(Date.now() / 1000);
                    
                    setStatus('evergreen');
                    setLastCalculated(newTimestamp);
                    
                    setNotice({
                        type: 'success',
                        message: __('Content marked as refreshed!', 'almaseo')
                    });
                    
                    // Update post meta
                    editPost({
                        meta: {
                            '_almaseo_evergreen_status': 'evergreen',
                            '_almaseo_evergreen_last_calculated': newTimestamp,
                            '_almaseo_evergreen_refreshed': newTimestamp
                        }
                    });
                    
                    // Clear notice after 3 seconds
                    setTimeout(() => setNotice(null), 3000);
                } else {
                    const errorMsg = response?.message || __('Failed to mark as refreshed', 'almaseo');
                    console.error('[AlmaSEO] Mark refreshed failed:', errorMsg);
                    setNotice({
                        type: 'error',
                        message: errorMsg
                    });
                }
            } catch (err) {
                console.error('[AlmaSEO] Error marking refreshed:', err);
                const errorMsg = err?.message || __('Failed to mark as refreshed. Check console for details.', 'almaseo');
                setNotice({
                    type: 'error',
                    message: errorMsg
                });
            } finally {
                setIsMarkingRefreshed(false);
            }
        };
        
        /**
         * Get status color
         */
        const getStatusColor = (status) => {
            switch(status) {
                case 'evergreen':
                    return '#28a745';
                case 'watch':
                    return '#ffc107';
                case 'stale':
                    return '#dc3545';
                default:
                    return '#6c757d';
            }
        };
        
        /**
         * Get status label
         */
        const getStatusLabel = (status) => {
            switch(status) {
                case 'evergreen':
                    return __('Evergreen', 'almaseo');
                case 'watch':
                    return __('Watch', 'almaseo');
                case 'stale':
                    return __('Stale', 'almaseo');
                default:
                    return __('Not Analyzed', 'almaseo');
            }
        };
        
        /**
         * Get status icon
         */
        const getStatusIcon = (status) => {
            switch(status) {
                case 'evergreen':
                    return '🟢';
                case 'watch':
                    return '🟡';
                case 'stale':
                    return '🔴';
                default:
                    return '⚪';
            }
        };
        
        if (loading) {
            return el(
                'div',
                { style: { padding: '12px', textAlign: 'center' } },
                el(Spinner),
                el('p', { style: { marginTop: '8px', fontSize: '12px' } }, __('Loading status...', 'almaseo'))
            );
        }
        
        // For new unsaved posts
        if (!postId || postId === 0) {
            return el(
                'div',
                { style: { padding: '12px', textAlign: 'center', color: '#666' } },
                el('p', { style: { fontSize: '13px' } }, 
                    __('Save the post to enable Evergreen tracking', 'almaseo')
                )
            );
        }
        
        return el(
            Fragment,
            null,
            // Notice
            notice && el(
                Notice,
                {
                    status: notice.type,
                    isDismissible: true,
                    onRemove: () => setNotice(null),
                    style: { marginBottom: '12px' }
                },
                notice.message
            ),
            
            // Status Card with live updates
            el('div', { 
                style: { 
                    padding: '12px',
                    backgroundColor: '#f8f9fa',
                    borderRadius: '4px',
                    marginBottom: '12px',
                    border: `2px solid ${getStatusColor(status)}`,
                    transition: 'all 0.3s ease'
                }
            },
                el('div', { 
                    style: { 
                        display: 'flex',
                        alignItems: 'center',
                        gap: '8px',
                        marginBottom: '8px'
                    }
                },
                    el('span', { 
                        style: { 
                            fontSize: '20px',
                            transition: 'transform 0.3s ease',
                            display: 'inline-block'
                        }
                    }, getStatusIcon(status)),
                    el('span', { 
                        style: { 
                            fontSize: '14px',
                            fontWeight: 'bold',
                            color: getStatusColor(status),
                            transition: 'color 0.3s ease'
                        }
                    }, getStatusLabel(status))
                ),
                
                // Timestamp with tooltip - updates live
                el('div', { 
                    style: { 
                        fontSize: '12px',
                        color: '#666',
                        marginTop: '4px',
                        cursor: 'help'
                    },
                    title: __('This shows when Evergreen status was last recalculated.', 'almaseo')
                },
                    __('Last recalculated: ', 'almaseo'),
                    el('strong', { 
                        style: { transition: 'opacity 0.3s ease' }
                    }, formatRelativeTime(lastCalculated))
                )
            ),
            
            // Quick Actions
            el('div', { 
                style: { 
                    display: 'flex',
                    gap: '8px',
                    marginBottom: '12px'
                }
            },
                el(Button, {
                    variant: 'primary',
                    size: 'small',
                    onClick: handleAnalyzeNow,
                    disabled: isAnalyzing || isMarkingRefreshed || isSaving,
                    'aria-label': __('Analyze Evergreen status now', 'almaseo'),
                    style: { flex: 1 }
                },
                    isAnalyzing ? el(
                        Fragment,
                        null,
                        el(Spinner, { style: { marginRight: '4px' } }),
                        __('Analyzing...', 'almaseo')
                    ) : __('Analyze Now', 'almaseo')
                ),
                
                el(Button, {
                    variant: 'secondary',
                    size: 'small',
                    onClick: handleMarkRefreshed,
                    disabled: isAnalyzing || isMarkingRefreshed || isSaving,
                    'aria-label': __('Mark content as refreshed', 'almaseo'),
                    style: { flex: 1 }
                },
                    isMarkingRefreshed ? el(
                        Fragment,
                        null,
                        el(Spinner, { style: { marginRight: '4px' } }),
                        __('Updating...', 'almaseo')
                    ) : __('Mark Refreshed', 'almaseo')
                )
            ),
            
            // Auto-analyze toggle
            el('div', { 
                style: { 
                    padding: '10px',
                    backgroundColor: '#f0f0f1',
                    borderRadius: '4px',
                    marginBottom: '12px'
                }
            },
                el('label', {
                    style: {
                        display: 'flex',
                        alignItems: 'center',
                        gap: '8px',
                        cursor: 'pointer',
                        fontSize: '13px'
                    }
                },
                    el('input', {
                        type: 'checkbox',
                        checked: autoAnalyze,
                        onChange: (e) => {
                            const newValue = e.target.checked;
                            setAutoAnalyze(newValue);
                            localStorage.setItem('almaseo_evergreen_auto_analyze', newValue ? 'true' : 'false');
                        },
                        style: { cursor: 'pointer' }
                    }),
                    __('Auto-analyze on Update/Publish', 'almaseo')
                ),
                el('p', {
                    style: {
                        margin: '4px 0 0 24px',
                        fontSize: '11px',
                        color: '#666',
                        fontStyle: 'italic'
                    }
                }, __('Automatically recalculate status when you save', 'almaseo'))
            ),
            
            // Info Box
            el('div', { 
                style: { 
                    padding: '8px',
                    backgroundColor: '#e9ecef',
                    borderRadius: '4px',
                    fontSize: '12px',
                    lineHeight: '1.5',
                    marginBottom: '12px'
                }
            },
                status === 'evergreen' && el('p', { style: { margin: 0 } }, 
                    __('Content is fresh and performing well.', 'almaseo')
                ),
                status === 'watch' && el('p', { style: { margin: 0 } }, 
                    __('Content may need updating soon. Monitor for declining traffic.', 'almaseo')
                ),
                status === 'stale' && el('p', { style: { margin: 0 } }, 
                    __('Content needs attention. Consider updating or refreshing.', 'almaseo')
                ),
                status === 'unknown' && el('p', { style: { margin: 0 } }, 
                    __('Analyze to determine content freshness status.', 'almaseo')
                )
            ),
            
            // Dashboard Link
            el('div', { style: { textAlign: 'center' } },
                el('a', { 
                    href: '/wp-admin/admin.php?page=almaseo-evergreen',
                    className: 'components-button is-link',
                    style: { fontSize: '12px', textDecoration: 'none' }
                }, __('View Full Dashboard →', 'almaseo'))
            )
        );
    };
    
    /**
     * Register the plugin with state persistence
     */
    registerPlugin('almaseo-evergreen-panel-enhanced-v2', {
        render: function() {
            const postType = wp.data.select('core/editor').getCurrentPostType();
            const currentUser = wp.data.select('core').getCurrentUser();
            const userId = currentUser ? currentUser.id : 0;
            
            // Only show for posts and pages
            if (!['post', 'page'].includes(postType)) {
                return null;
            }
            
            // Get saved panel state from localStorage
            const [isPanelOpen, setIsPanelOpen] = useState(() => {
                if (userId) {
                    const savedState = localStorage.getItem(`almaseo_panel_evergreen_${userId}`);
                    return savedState !== 'closed';
                }
                return true; // Default to open
            });
            
            // Save panel state when toggled
            const handleToggle = (isOpen) => {
                console.log('[AlmaSEO] Panel toggled:', isOpen);
                setIsPanelOpen(isOpen);
                
                if (userId) {
                    localStorage.setItem(
                        `almaseo_panel_evergreen_${userId}`,
                        isOpen ? 'open' : 'closed'
                    );
                    
                    // Also save to server via AJAX if available
                    if (window.jQuery && window.ajaxurl) {
                        jQuery.post(ajaxurl, {
                            action: 'almaseo_save_panel_state',
                            panel: 'evergreen',
                            state: isOpen ? 'open' : 'closed',
                            user_id: userId,
                            nonce: almaseoEvergreenSettings.nonce
                        });
                    }
                }
            };
            
            return el(
                PluginDocumentSettingPanel,
                {
                    name: 'almaseo-evergreen-enhanced',
                    title: '🌱 ' + __('Evergreen Tracker', 'almaseo'),
                    className: 'almaseo-evergreen-panel-enhanced',
                    opened: isPanelOpen,
                    onToggle: handleToggle,
                    icon: null // Icon is in the title
                },
                el(EvergreenPanel)
            );
        }
    });
    
    console.log('[AlmaSEO] Evergreen panel v2 registered');
    
})(window.wp, window.jQuery);