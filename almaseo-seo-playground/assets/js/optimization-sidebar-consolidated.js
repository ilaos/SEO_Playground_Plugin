(function(wp) {
    const { registerPlugin } = wp.plugins;
    
    // WP 6.6+ compatibility: Use new APIs if available, fallback to old ones
    const PluginSidebar = wp.editor?.PluginSidebar || wp.editPost?.PluginSidebar;
    const PluginSidebarMoreMenuItem = wp.editor?.PluginSidebarMoreMenuItem || wp.editPost?.PluginSidebarMoreMenuItem;
    
    // Check if we have the required components
    if (!PluginSidebar || !PluginSidebarMoreMenuItem) {
        console.warn('AlmaSEO: Editor sidebar components not available');
        return;
    }
    
    const { Panel, PanelBody, PanelRow, Button, Spinner, Notice, SelectControl, TextControl } = wp.components;
    const { Fragment, Component, createElement, useMemo } = wp.element;
    const { withSelect, withDispatch, useSelect } = wp.data;
    const { compose } = wp.compose;
    const apiFetch = wp.apiFetch;

    class AlmaSEOOptimizationSidebar extends Component {
        constructor(props) {
            super(props);
            this.state = {
                quickWins: null,
                suggestions: null,
                pinnedKeywords: [],
                ignoredKeywords: [],
                focusKeyword: '',
                isLoading: true,
                isRefreshing: false,
                error: null,
                providerNotice: null,
                providerName: '',
                providerFallback: false,
                lastRefreshed: null,
                sortBy: 'position',
                sortOrder: 'asc',
                showFocusNudge: false,
                nudgeTimeout: null,
            };
            
            // Bind methods to ensure stable references
            this.loadOptimizationData = this.loadOptimizationData.bind(this);
            this.refreshData = this.refreshData.bind(this);
            this.pinKeyword = this.pinKeyword.bind(this);
            this.unpinKeyword = this.unpinKeyword.bind(this);
            this.ignoreKeyword = this.ignoreKeyword.bind(this);
            this.unignoreKeyword = this.unignoreKeyword.bind(this);
            this.setFocusKeyword = this.setFocusKeyword.bind(this);
        }

        componentDidMount() {
            this.loadOptimizationData();
            // Update relative time every minute
            this.timeInterval = setInterval(() => {
                this.forceUpdate();
            }, 60000);
        }

        componentWillUnmount() {
            if (this.timeInterval) {
                clearInterval(this.timeInterval);
            }
            if (this.state.nudgeTimeout) {
                clearTimeout(this.state.nudgeTimeout);
            }
        }

        componentDidUpdate(prevProps) {
            // Only reload if postId actually changed
            if (prevProps.postId !== this.props.postId && this.props.postId) {
                this.loadOptimizationData();
            }
        }

        loadOptimizationData() {
            const { postMeta } = this.props;
            
            if (!postMeta) {
                return;
            }
            
            // Load data from post meta
            const quickWins = postMeta._almaseo_quickwins || null;
            const suggestions = postMeta._almaseo_keywordsuggestions || null;
            const pinnedKeywords = postMeta._almaseo_pinned_keywords || [];
            const ignoredKeywords = postMeta._almaseo_ignored_keywords || [];
            const focusKeyword = postMeta._almaseo_focus_keyword || '';
            const lastRefreshed = postMeta._almaseo_kw_cached_at || null;

            // Get provider name from quickWins data if available
            const providerName = quickWins?.provider || 'stub';

            this.setState({
                quickWins,
                suggestions,
                pinnedKeywords,
                ignoredKeywords,
                focusKeyword,
                lastRefreshed,
                providerName: this.getProviderDisplayName(providerName),
                isLoading: false
            });

            // If no data exists, generate it
            if (!quickWins || !suggestions) {
                this.refreshData();
            }
        }

        getProviderDisplayName(providerId) {
            const names = {
                'stub': 'Sample Data',
                'gsc': 'Google Search Console',
                'dataforseo': 'DataForSEO'
            };
            return names[providerId] || 'Unknown';
        }

        getRelativeTime(timestamp) {
            if (!timestamp) return '—';
            
            const now = Date.now() / 1000;
            const diff = now - timestamp;
            
            if (diff < 60) {
                return 'just now';
            } else if (diff < 3600) {
                const mins = Math.floor(diff / 60);
                return `${mins} minute${mins > 1 ? 's' : ''} ago`;
            } else if (diff < 86400) {
                const hours = Math.floor(diff / 3600);
                return `${hours} hour${hours > 1 ? 's' : ''} ago`;
            } else {
                const days = Math.floor(diff / 86400);
                return `${days} day${days > 1 ? 's' : ''} ago`;
            }
        }

        refreshData() {
            this.setState({ isRefreshing: true, error: null, providerNotice: null });

            apiFetch({
                path: '/almaseo/v1/optimization/refresh',
                method: 'POST',
                data: {
                    post_id: this.props.postId
                }
            })
            .then(response => {
                if (response.success) {
                    this.setState({
                        quickWins: response.quick_wins,
                        suggestions: response.suggestions,
                        providerNotice: response.provider_notice || null,
                        providerName: this.getProviderDisplayName(response.quick_wins?.provider || 'stub'),
                        providerFallback: response.provider_fallback || false,
                        lastRefreshed: Date.now() / 1000,
                        isRefreshing: false
                    });
                    // Force refresh the post meta
                    if (this.props.refreshPost) {
                        this.props.refreshPost();
                    }
                }
            })
            .catch(error => {
                this.setState({
                    error: error.message || 'Failed to refresh optimization data',
                    isRefreshing: false
                });
            });
        }

        pinKeyword(keyword) {
            apiFetch({
                path: '/almaseo/v1/optimization/pin-keyword',
                method: 'POST',
                data: {
                    post_id: this.props.postId,
                    keyword: keyword
                }
            })
            .then(response => {
                if (response.success) {
                    const pinnedKeywords = [...this.state.pinnedKeywords, keyword];
                    this.setState({ pinnedKeywords });
                    
                    // Update post meta
                    this.props.editPost({
                        meta: {
                            _almaseo_pinned_keywords: pinnedKeywords
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Failed to pin keyword:', error);
            });
        }

        unpinKeyword(keyword) {
            apiFetch({
                path: '/almaseo/v1/optimization/unpin-keyword',
                method: 'POST',
                data: {
                    post_id: this.props.postId,
                    keyword: keyword
                }
            })
            .then(response => {
                if (response.success) {
                    const pinnedKeywords = this.state.pinnedKeywords.filter(k => k !== keyword);
                    this.setState({ pinnedKeywords });
                    
                    // Update post meta
                    this.props.editPost({
                        meta: {
                            _almaseo_pinned_keywords: pinnedKeywords
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Failed to unpin keyword:', error);
            });
        }

        ignoreKeyword(keyword) {
            apiFetch({
                path: '/almaseo/v1/optimization/ignore-keyword',
                method: 'POST',
                data: {
                    post_id: this.props.postId,
                    keyword: keyword
                }
            })
            .then(response => {
                if (response.success) {
                    const ignoredKeywords = [...this.state.ignoredKeywords, keyword];
                    const pinnedKeywords = this.state.pinnedKeywords.filter(k => k !== keyword);
                    
                    this.setState({ 
                        ignoredKeywords,
                        pinnedKeywords
                    });
                    
                    // Update post meta
                    this.props.editPost({
                        meta: {
                            _almaseo_ignored_keywords: ignoredKeywords,
                            _almaseo_pinned_keywords: pinnedKeywords
                        }
                    });
                }
            })
            .catch(error => {
                console.error('Failed to ignore keyword:', error);
            });
        }

        unignoreKeyword(keyword) {
            const ignoredKeywords = this.state.ignoredKeywords.filter(k => k !== keyword);
            this.setState({ ignoredKeywords });
            
            // Update post meta
            this.props.editPost({
                meta: {
                    _almaseo_ignored_keywords: ignoredKeywords
                }
            });
        }

        setFocusKeyword(keyword) {
            // Show nudge
            this.setState({ 
                showFocusNudge: true,
                nudgeTimeout: setTimeout(() => {
                    this.setState({ showFocusNudge: false });
                }, 3000)
            });

            // Update local state
            this.setState({ focusKeyword: keyword });
            
            // Update post meta
            this.props.editPost({
                meta: {
                    _almaseo_focus_keyword: keyword
                }
            });
            
            // Also update the main focus keyword field if it exists
            if (document.getElementById('almaseo_focus_keyword')) {
                document.getElementById('almaseo_focus_keyword').value = keyword;
                document.getElementById('almaseo_focus_keyword').dispatchEvent(new Event('input'));
            }
        }

        sortKeywords(keywords) {
            const { sortBy, sortOrder } = this.state;
            
            return [...keywords].sort((a, b) => {
                let aVal, bVal;
                
                if (sortBy === 'position') {
                    aVal = a.position || 999;
                    bVal = b.position || 999;
                } else if (sortBy === 'volume') {
                    aVal = a.search_volume || 0;
                    bVal = b.search_volume || 0;
                }
                
                if (sortOrder === 'asc') {
                    return aVal - bVal;
                } else {
                    return bVal - aVal;
                }
            });
        }

        renderFocusNudge() {
            const { showFocusNudge } = this.state;
            
            if (!showFocusNudge) return null;
            
            return createElement(
                'div',
                { 
                    className: 'focus-keyword-nudge',
                    style: {
                        position: 'fixed',
                        top: '60px',
                        right: '20px',
                        background: '#00a32a',
                        color: 'white',
                        padding: '10px 15px',
                        borderRadius: '4px',
                        boxShadow: '0 2px 4px rgba(0,0,0,0.2)',
                        zIndex: 10000,
                        animation: 'slideInRight 0.3s ease'
                    }
                },
                '✓ Focus keyword updated!'
            );
        }

        renderFocusKeyword() {
            const { focusKeyword } = this.state;
            
            return createElement(
                'div',
                { className: 'focus-keyword-display' },
                createElement(
                    'label',
                    { style: { display: 'block', marginBottom: '5px', fontWeight: 'bold' } },
                    'Focus Keyword:'
                ),
                createElement(
                    TextControl,
                    {
                        value: focusKeyword,
                        onChange: (value) => this.setFocusKeyword(value),
                        placeholder: 'Enter your target keyword...'
                    }
                )
            );
        }

        renderQuickWins() {
            const { quickWins, pinnedKeywords, ignoredKeywords, isLoading } = this.state;
            
            if (isLoading) {
                return createElement(Spinner);
            }
            
            if (!quickWins || !quickWins.keywords || quickWins.keywords.length === 0) {
                return createElement(
                    'p',
                    { style: { fontStyle: 'italic', color: '#666' } },
                    'No quick win keywords found. Try refreshing the data.'
                );
            }
            
            // Filter out ignored keywords and sort
            const visibleKeywords = this.sortKeywords(
                quickWins.keywords.filter(kw => !ignoredKeywords.includes(kw.keyword))
            );
            
            return createElement(
                Fragment,
                null,
                createElement(
                    'div',
                    { className: 'keywords-list' },
                    visibleKeywords.map((keyword, index) => {
                        const isPinned = pinnedKeywords.includes(keyword.keyword);
                        
                        return createElement(
                            'div',
                            { 
                                key: index,
                                className: `keyword-item ${isPinned ? 'pinned' : ''}`,
                                style: {
                                    padding: '10px',
                                    marginBottom: '10px',
                                    border: '1px solid #ddd',
                                    borderRadius: '4px',
                                    background: isPinned ? '#f0f8ff' : '#fff'
                                }
                            },
                            createElement(
                                'div',
                                { style: { display: 'flex', justifyContent: 'space-between', alignItems: 'start' } },
                                createElement(
                                    'div',
                                    { style: { flex: 1 } },
                                    createElement(
                                        'strong',
                                        { style: { display: 'block', marginBottom: '5px' } },
                                        keyword.keyword
                                    ),
                                    createElement(
                                        'div',
                                        { style: { fontSize: '12px', color: '#666' } },
                                        keyword.position && `Position: ${keyword.position} | `,
                                        keyword.search_volume && `Volume: ${keyword.search_volume} | `,
                                        keyword.impressions && `Impressions: ${keyword.impressions}`
                                    )
                                ),
                                createElement(
                                    'div',
                                    { style: { display: 'flex', gap: '5px' } },
                                    createElement(
                                        Button,
                                        {
                                            isSmall: true,
                                            variant: 'secondary',
                                            onClick: () => this.setFocusKeyword(keyword.keyword),
                                            title: 'Set as focus keyword'
                                        },
                                        '🎯'
                                    ),
                                    createElement(
                                        Button,
                                        {
                                            isSmall: true,
                                            variant: isPinned ? 'primary' : 'secondary',
                                            onClick: () => isPinned ? this.unpinKeyword(keyword.keyword) : this.pinKeyword(keyword.keyword),
                                            title: isPinned ? 'Unpin keyword' : 'Pin keyword'
                                        },
                                        isPinned ? '📌' : '📍'
                                    ),
                                    createElement(
                                        Button,
                                        {
                                            isSmall: true,
                                            variant: 'tertiary',
                                            onClick: () => this.ignoreKeyword(keyword.keyword),
                                            title: 'Ignore keyword'
                                        },
                                        '✕'
                                    )
                                )
                            )
                        );
                    })
                )
            );
        }

        renderKeywordSuggestions() {
            const { suggestions, ignoredKeywords, lastRefreshed, providerName, providerFallback } = this.state;
            
            if (!suggestions || suggestions.length === 0) {
                return createElement(
                    'p',
                    { style: { fontStyle: 'italic', color: '#666' } },
                    'No keyword suggestions available.'
                );
            }
            
            const visibleSuggestions = suggestions.filter(kw => !ignoredKeywords.includes(kw));
            
            return createElement(
                Fragment,
                null,
                lastRefreshed && createElement(
                    'div',
                    { 
                        style: { 
                            fontSize: '12px', 
                            color: '#666', 
                            marginBottom: '10px',
                            display: 'flex',
                            justifyContent: 'space-between'
                        } 
                    },
                    createElement('span', null, `Source: ${providerName}`),
                    createElement('span', null, `Updated ${this.getRelativeTime(lastRefreshed)}`)
                ),
                providerFallback && createElement(
                    Notice,
                    { 
                        status: 'info',
                        isDismissible: false
                    },
                    'Using sample data. Connect Google Search Console for real insights.'
                ),
                createElement(
                    'div',
                    { className: 'suggestions-list' },
                    visibleSuggestions.map((suggestion, index) => {
                        return createElement(
                            'div',
                            { 
                                key: index,
                                className: 'suggestion-item',
                                style: {
                                    padding: '8px',
                                    marginBottom: '5px',
                                    border: '1px solid #e0e0e0',
                                    borderRadius: '3px',
                                    background: '#fafafa',
                                    display: 'flex',
                                    justifyContent: 'space-between',
                                    alignItems: 'center'
                                }
                            },
                            createElement('span', null, suggestion),
                            createElement(
                                'div',
                                { style: { display: 'flex', gap: '5px' } },
                                createElement(
                                    Button,
                                    {
                                        isSmall: true,
                                        variant: 'secondary',
                                        onClick: () => this.setFocusKeyword(suggestion),
                                        title: 'Set as focus keyword'
                                    },
                                    '🎯'
                                ),
                                createElement(
                                    Button,
                                    {
                                        isSmall: true,
                                        variant: 'tertiary',
                                        onClick: () => this.ignoreKeyword(suggestion),
                                        title: 'Ignore suggestion'
                                    },
                                    '✕'
                                )
                            )
                        );
                    })
                )
            );
        }

        render() {
            const { 
                isLoading, 
                isRefreshing, 
                error, 
                providerNotice,
                ignoredKeywords 
            } = this.state;

            return createElement(
                Fragment,
                null,
                createElement(
                    PluginSidebarMoreMenuItem,
                    {
                        target: 'almaseo-optimization-sidebar',
                        icon: '📊'
                    },
                    'AlmaSEO Optimization'
                ),
                createElement(
                    PluginSidebar,
                    {
                        name: 'almaseo-optimization-sidebar',
                        title: 'AlmaSEO Optimization',
                        icon: '📊'
                    },
                    createElement(
                        Panel,
                        null,
                        error && createElement(
                            Notice,
                            { status: 'error', isDismissible: true },
                            error
                        ),
                        providerNotice && createElement(
                            Notice,
                            { status: 'warning', isDismissible: false },
                            providerNotice
                        ),
                        this.renderFocusNudge(),
                        createElement(
                            'div',
                            { className: 'almaseo-optimization-header' },
                            this.renderFocusKeyword(),
                            createElement(
                                Button,
                                {
                                    variant: 'secondary',
                                    onClick: () => this.refreshData(),
                                    disabled: isRefreshing,
                                    className: 'refresh-button'
                                },
                                isRefreshing ? 
                                    createElement(
                                        Fragment,
                                        null,
                                        createElement(Spinner, null),
                                        ' Refreshing...'
                                    ) : 
                                    '🔄 Refresh'
                            )
                        ),
                        createElement(
                            PanelBody,
                            { 
                                title: 'Quick Wins',
                                initialOpen: true
                            },
                            this.renderQuickWins()
                        ),
                        createElement(
                            PanelBody,
                            { 
                                title: 'Keyword Suggestions',
                                initialOpen: true
                            },
                            this.renderKeywordSuggestions()
                        ),
                        ignoredKeywords.length > 0 && createElement(
                            PanelBody,
                            { 
                                title: `Ignored Keywords (${ignoredKeywords.length})`,
                                initialOpen: false
                            },
                            createElement(
                                'ul',
                                { className: 'ignored-keywords-list' },
                                ignoredKeywords.map((keyword, index) => {
                                    return createElement(
                                        'li',
                                        { key: index },
                                        createElement('span', null, keyword),
                                        createElement(
                                            Button,
                                            {
                                                isSmall: true,
                                                variant: 'tertiary',
                                                onClick: () => this.unignoreKeyword(keyword)
                                            },
                                            'Restore'
                                        )
                                    );
                                })
                            )
                        )
                    )
                )
            );
        }
    }

    // Optimized withSelect using stable selector functions
    const AlmaSEOOptimizationSidebarWithData = compose(
        withSelect((select) => {
            const editor = select('core/editor');
            
            // Return stable object reference
            return {
                postMeta: editor?.getEditedPostAttribute('meta') || {},
                postId: editor?.getCurrentPostId() || null,
                refreshPost: editor?.refreshPost
            };
        }),
        withDispatch((dispatch) => {
            const editor = dispatch('core/editor');
            
            // Return stable object reference
            return {
                editPost: editor?.editPost || (() => {})
            };
        })
    )(AlmaSEOOptimizationSidebar);

    // Register the plugin
    if (registerPlugin) {
        registerPlugin('almaseo-optimization-sidebar', {
            render: AlmaSEOOptimizationSidebarWithData
        });
    }

})(window.wp);