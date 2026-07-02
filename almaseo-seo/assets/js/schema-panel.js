/**
 * AlmaSEO Schema Settings Panel for Gutenberg
 * 
 * @package AlmaSEO
 * @since 4.2.0
 */

(function(wp) {
    'use strict';
    
    const { registerPlugin } = wp.plugins;
    const { PluginDocumentSettingPanel } = wp.editor || wp.editPost || {};
    const { Fragment, createElement: el, useState, useEffect } = wp.element;
    const { __ } = wp.i18n;
    const { ToggleControl, Notice, SelectControl } = wp.components;
    const { useSelect, useDispatch } = wp.data;
    
    // Check if we have the required component
    if (!PluginDocumentSettingPanel) {
        console.log('[AlmaSEO] Schema panel - PluginDocumentSettingPanel not available');
        return;
    }
    
    /**
     * Schema Settings Panel Component
     */
    const SchemaPanel = () => {
        const [schemaType, setSchemaType] = useState('BlogPosting');
        const [includeAuthor, setIncludeAuthor] = useState(true);
        const [includeImage, setIncludeImage] = useState(true);
        const [includePublisher, setIncludePublisher] = useState(true);
        const [notice, setNotice] = useState(null);
        const [hasChanges, setHasChanges] = useState(false);
        const [isSaving, setIsSaving] = useState(false);
        const [missingDataHints, setMissingDataHints] = useState({});
        
        const { postId, postType, postMeta, isSavingPost, isAutosavingPost } = useSelect((select) => {
            const editor = select('core/editor');
            return {
                postId: editor.getCurrentPostId(),
                postType: editor.getCurrentPostType(),
                postMeta: editor.getEditedPostAttribute('meta') || {},
                isSavingPost: editor.isSavingPost(),
                isAutosavingPost: editor.isAutosavingPost()
            };
        }, []);
        
        const { editPost } = useDispatch('core/editor');
        
        // Load initial settings from post meta
        useEffect(() => {
            console.log('[AlmaSEO Schema] Loading meta:', postMeta);
            if (postMeta._almaseo_schema_type) {
                setSchemaType(postMeta._almaseo_schema_type);
            }
            if (postMeta._almaseo_schema_include_author !== undefined) {
                setIncludeAuthor(postMeta._almaseo_schema_include_author);
            }
            if (postMeta._almaseo_schema_include_image !== undefined) {
                setIncludeImage(postMeta._almaseo_schema_include_image);
            }
            if (postMeta._almaseo_schema_include_publisher !== undefined) {
                setIncludePublisher(postMeta._almaseo_schema_include_publisher);
            }
            // Check for missing data on load
            checkMissingData();
        }, [postMeta]);
        
        // Monitor save status
        useEffect(() => {
            if (isSavingPost && !isAutosavingPost && hasChanges) {
                setIsSaving(true);
            } else if (!isSavingPost && isSaving) {
                setIsSaving(false);
                setHasChanges(false);
                setNotice({
                    type: 'success',
                    message: __('Schema settings saved successfully', 'almaseo')
                });
                setTimeout(() => setNotice(null), 3000);
            }
        }, [isSavingPost, isAutosavingPost, hasChanges, isSaving]);
        
        /**
         * Handle schema type change
         */
        const handleSchemaTypeChange = (newType) => {
            setSchemaType(newType);
            setHasChanges(true);
            editPost({
                meta: {
                    ...postMeta,
                    '_almaseo_schema_type': newType
                }
            });
            
            console.log('[AlmaSEO Schema] Type changed to:', newType);
            
            // Update preview immediately
            setTimeout(() => {
                updatePreviewInMainTab();
            }, 100);
        };
        
        /**
         * Update JSON-LD preview in main tab
         */
        const updatePreviewInMainTab = () => {
            // Trigger custom event to update preview in main tab
            const event = new CustomEvent('almaseo-schema-settings-changed', {
                detail: {
                    includeAuthor,
                    includeImage,
                    includePublisher,
                    schemaType
                }
            });
            document.dispatchEvent(event);
        };
        
        /**
         * Check for missing data and update hints
         */
        const checkMissingData = () => {
            const hints = {};
            
            // Check for featured image
            const hasFeaturedImage = wp.data.select('core/editor').getEditedPostAttribute('featured_media') > 0;
            if (includeImage && !hasFeaturedImage) {
                hints.image = __('No featured image found for schema.', 'almaseo');
            }
            
            // Check for author archives (simplified check)
            const authorArchivesDisabled = window.almaseoSchemaSettings?.authorArchivesDisabled;
            if (includeAuthor && authorArchivesDisabled) {
                hints.author = __('Author archive is off; using homepage as author URL.', 'almaseo');
            }
            
            // Check for publisher settings
            const hasPublisherSettings = window.almaseoSchemaSettings?.hasPublisherSettings;
            if (includePublisher && !hasPublisherSettings) {
                hints.publisher = __('Publisher details not set. Configure in AlmaSEO Settings.', 'almaseo');
            }
            
            setMissingDataHints(hints);
        };
        
        /**
         * Handle toggle changes
         */
        const handleToggleChange = (setting, value) => {
            const metaKey = `_almaseo_schema_${setting}`;
            
            switch(setting) {
                case 'include_author':
                    setIncludeAuthor(value);
                    break;
                case 'include_image':
                    setIncludeImage(value);
                    break;
                case 'include_publisher':
                    setIncludePublisher(value);
                    break;
            }
            
            setHasChanges(true);
            editPost({
                meta: {
                    ...postMeta,
                    [metaKey]: value
                }
            });
            
            console.log('[AlmaSEO Schema] Toggle changed:', setting, value);
            
            // Update preview immediately
            setTimeout(() => {
                updatePreviewInMainTab();
                checkMissingData();
            }, 100);
        };
        
        // Helper for sprintf-like functionality
        const sprintf = (str, ...args) => {
            return str.replace(/%[sd]/g, (match) => {
                const arg = args.shift();
                return arg !== undefined ? arg : match;
            });
        };
        
        return el(
            Fragment,
            null,
            // Save status indicator
            hasChanges && el(
                'div',
                {
                    style: {
                        padding: '8px 12px',
                        backgroundColor: '#fff3cd',
                        borderLeft: '4px solid #ffc107',
                        borderRadius: '4px',
                        marginBottom: '12px',
                        fontSize: '13px'
                    }
                },
                el('span', { style: { fontWeight: 'bold' } }, '⚠️ '),
                __('You have unsaved changes. Click "Update" or "Publish" to save.', 'almaseo')
            ),
            
            // Notice
            notice && el(
                Notice,
                {
                    status: notice.type,
                    isDismissible: false,
                    style: { marginBottom: '12px' }
                },
                notice.message
            ),
            
            // Schema Type Selector
            el('div', { style: { marginBottom: '16px' } },
                el(SelectControl, {
                    label: __('Schema Type', 'almaseo'),
                    value: schemaType,
                    options: [
                        { label: 'BlogPosting', value: 'BlogPosting' },
                        { label: 'Article', value: 'Article' },
                        { label: 'NewsArticle', value: 'NewsArticle' },
                        { label: 'WebPage', value: 'WebPage' },
                        { label: 'Product', value: 'Product' },
                        { label: 'Recipe', value: 'Recipe' },
                        { label: 'HowTo', value: 'HowTo' },
                        { label: 'FAQ', value: 'FAQPage' }
                    ],
                    onChange: handleSchemaTypeChange,
                    help: __('Select the most appropriate schema type for this content', 'almaseo')
                })
            ),
            
            // Schema Options
            el('div', { 
                style: { 
                    padding: '12px',
                    backgroundColor: '#f8f9fa',
                    borderRadius: '4px',
                    marginBottom: '12px'
                }
            },
                el('h4', { 
                    style: { 
                        margin: '0 0 12px 0',
                        fontSize: '13px',
                        fontWeight: 'bold'
                    }
                }, __('Schema Elements', 'almaseo')),
                
                el(ToggleControl, {
                    label: __('Include Author', 'almaseo'),
                    checked: includeAuthor,
                    onChange: (value) => handleToggleChange('include_author', value),
                    help: __('Add author information to schema', 'almaseo')
                }),
                missingDataHints.author && el('p', { 
                    style: { 
                        margin: '-8px 0 12px 0', 
                        paddingLeft: '36px',
                        fontSize: '12px', 
                        color: '#757575',
                        fontStyle: 'italic'
                    } 
                }, missingDataHints.author),
                
                el(ToggleControl, {
                    label: __('Include Featured Image', 'almaseo'),
                    checked: includeImage,
                    onChange: (value) => handleToggleChange('include_image', value),
                    help: __('Add featured image to schema', 'almaseo')
                }),
                missingDataHints.image && el('p', { 
                    style: { 
                        margin: '-8px 0 12px 0', 
                        paddingLeft: '36px',
                        fontSize: '12px', 
                        color: '#757575',
                        fontStyle: 'italic'
                    } 
                }, missingDataHints.image),
                
                el(ToggleControl, {
                    label: __('Include Publisher', 'almaseo'),
                    checked: includePublisher,
                    onChange: (value) => handleToggleChange('include_publisher', value),
                    help: __('Add site publisher information', 'almaseo')
                }),
                missingDataHints.publisher && el('p', { 
                    style: { 
                        margin: '-8px 0 12px 0', 
                        paddingLeft: '36px',
                        fontSize: '12px', 
                        color: '#757575',
                        fontStyle: 'italic'
                    } 
                }, missingDataHints.publisher)
            ),
            
            // Info Box
            el('div', { 
                style: { 
                    padding: '8px',
                    backgroundColor: '#e8f4fd',
                    borderLeft: '4px solid #2196F3',
                    borderRadius: '4px',
                    fontSize: '12px',
                    lineHeight: '1.5'
                }
            },
                el('p', { style: { margin: '0 0 4px 0', fontWeight: 'bold' } }, 
                    __('ℹ️ About Schema Markup', 'almaseo')
                ),
                el('p', { style: { margin: '0 0 8px 0' } }, 
                    __('Schema markup helps search engines understand your content better and can improve how it appears in search results.', 'almaseo')
                ),
                el('p', { style: { margin: 0, fontSize: '11px', fontStyle: 'italic' } }, 
                    __('💡 Tip: Settings auto-save when you update or publish the post.', 'almaseo')
                )
            ),
            
            // Settings Link
            el('div', { style: { marginTop: '12px', textAlign: 'center' } },
                el('a', { 
                    href: '/wp-admin/admin.php?page=almaseo-settings',
                    className: 'components-button is-link',
                    style: { fontSize: '12px', textDecoration: 'none' }
                }, __('Advanced Schema Settings →', 'almaseo'))
            )
        );
    };
    
    /**
     * Register the plugin with state persistence
     */
    registerPlugin('almaseo-schema-panel', {
        render: function() {
            const postType = wp.data.select('core/editor').getCurrentPostType();
            const userId = wp.data.select('core').getCurrentUser()?.id;
            
            // Show for all post types but be careful with products
            const supportedTypes = ['post', 'page', 'product'];
            if (!supportedTypes.includes(postType)) {
                return null;
            }
            
            // For products, only show if explicitly enabled or schema type is already set
            if (postType === 'product') {
                const postMeta = wp.data.select('core/editor').getEditedPostAttribute('meta') || {};
                const hasExplicitSchema = postMeta._almaseo_schema_type && postMeta._almaseo_schema_type !== '';
                if (!hasExplicitSchema && !window.almaseoSchemaSettings?.enableForProducts) {
                    console.log('[AlmaSEO Schema] Skipping Product post type to avoid conflicts');
                    return null;
                }
            }
            
            // Get saved panel state
            const savedState = localStorage.getItem(`almaseo_panel_schema_${userId}`);
            const [isPanelOpen, setIsPanelOpen] = useState(savedState !== 'closed');
            
            // Save panel state when toggled
            const handleToggle = (isOpen) => {
                setIsPanelOpen(isOpen);
                localStorage.setItem(
                    `almaseo_panel_schema_${userId}`,
                    isOpen ? 'open' : 'closed'
                );
            };
            
            return el(
                PluginDocumentSettingPanel,
                {
                    name: 'almaseo-schema',
                    title: '📄 ' + __('Schema Settings', 'almaseo'),
                    className: 'almaseo-schema-panel',
                    opened: isPanelOpen,
                    onToggle: handleToggle
                },
                el(SchemaPanel)
            );
        }
    });
    
})(window.wp);