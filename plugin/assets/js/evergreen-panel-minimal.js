/**
 * AlmaSEO Evergreen Panel - Simplified Version
 * 
 * @package AlmaSEO
 * @since 2.6.0
 */

(function(wp) {
    'use strict';
    
    console.log('[AlmaSEO] Evergreen panel script loading...');
    
    const { registerPlugin } = wp.plugins;
    const { PluginDocumentSettingPanel } = wp.editor || wp.editPost || {};
    const { Fragment, createElement: el } = wp.element;
    const { __ } = wp.i18n;
    
    // Check if we have the required component
    if (!PluginDocumentSettingPanel) {
        console.error('[AlmaSEO] PluginDocumentSettingPanel not available');
        return;
    }
    
    // Simple status display component
    const EvergreenPanel = function() {
        const postId = wp.data.select('core/editor').getCurrentPostId();
        const postType = wp.data.select('core/editor').getCurrentPostType();
        
        console.log('[AlmaSEO] Rendering Evergreen panel for post:', postId, 'type:', postType);
        
        return el(
            Fragment,
            null,
            el('div', { 
                style: { 
                    padding: '12px',
                    backgroundColor: '#f8f9fa',
                    borderRadius: '4px',
                    marginBottom: '12px'
                }
            },
                el('p', { style: { margin: '0 0 8px 0', fontSize: '14px', fontWeight: 'bold' } }, 
                    'Content Status'
                ),
                el('div', { 
                    style: { 
                        display: 'flex',
                        alignItems: 'center',
                        gap: '8px'
                    }
                },
                    el('span', { 
                        style: { 
                            display: 'inline-block',
                            width: '12px',
                            height: '12px',
                            borderRadius: '50%',
                            backgroundColor: '#28a745'
                        }
                    }),
                    el('span', { style: { fontSize: '13px' } }, 'Evergreen')
                )
            ),
            el('div', { 
                style: { 
                    padding: '8px',
                    backgroundColor: '#e9ecef',
                    borderRadius: '4px',
                    fontSize: '12px',
                    lineHeight: '1.5'
                }
            },
                el('p', { style: { margin: 0 } }, 
                    'This feature tracks content freshness and performance. Content is automatically analyzed weekly.'
                )
            ),
            el('div', { style: { marginTop: '12px' } },
                el('a', { 
                    href: '/wp-admin/admin.php?page=almaseo-evergreen',
                    className: 'button button-secondary',
                    style: { fontSize: '12px' }
                }, 'View Dashboard')
            )
        );
    };
    
    // Register the plugin
    registerPlugin('almaseo-evergreen-panel-simple', {
        render: function() {
            const postType = wp.data.select('core/editor').getCurrentPostType();
            
            // Only show for posts and pages
            if (!['post', 'page'].includes(postType)) {
                console.log('[AlmaSEO] Evergreen panel hidden for post type:', postType);
                return null;
            }
            
            console.log('[AlmaSEO] Registering Evergreen panel for post type:', postType);
            
            return el(
                PluginDocumentSettingPanel,
                {
                    name: 'almaseo-evergreen-simple',
                    title: 'Evergreen Tracker',
                    className: 'almaseo-evergreen-panel-simple',
                    icon: el('span', { 
                        className: 'dashicons dashicons-chart-line',
                        style: { marginRight: '5px' }
                    })
                },
                el(EvergreenPanel)
            );
        }
    });
    
    console.log('[AlmaSEO] Evergreen panel registered successfully');
    
})(window.wp);