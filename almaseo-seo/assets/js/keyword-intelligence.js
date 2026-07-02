/**
 * AlmaSEO Keyword Intelligence - Gutenberg Integration
 * 
 * @package AlmaSEO
 * @since 7.0.0
 */

(function(wp, $) {
    'use strict';
    
    const { registerPlugin } = wp.plugins;
    const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
    const { Panel, PanelBody, PanelRow, TextControl, Button, Spinner, Notice, SelectControl, ToggleControl } = wp.components;
    const { Component, Fragment } = wp.element;
    const { withSelect, withDispatch } = wp.data;
    const { compose } = wp.compose;
    const __ = wp.i18n.__;
    
    class KeywordIntelligence extends Component {
        constructor(props) {
            super(props);
            
            this.state = {
                keywords: [],
                searchTerm: '',
                suggestions: [],
                metrics: {},
                loading: false,
                error: null,
                countryCode: 'US',
                activeTab: 'search', // search, suggestions, trends
                trends: [],
                usage: { used: 0, limit: 1000 },
                selectedKeywords: [],
                history: {}
            };
            
            this.searchDebounce = null;
        }
        
        componentDidMount() {
            this.loadSavedKeywords();
            this.checkFeatureStatus();
        }
        
        checkFeatureStatus = () => {
            fetch('/wp-json/almaseo/v1/keyword-intelligence/status', {
                headers: {
                    'X-WP-Nonce': almaseoKI.nonce
                }
            })
            .then(res => res.json())
            .then(data => {
                if (!data.enabled) {
                    this.setState({ 
                        error: 'Keyword Intelligence is not enabled. Contact your administrator.' 
                    });
                }
            })
            .catch(err => {
                console.error('KI status check failed:', err);
            });
        };
        
        loadSavedKeywords = () => {
            const postId = this.props.postId;
            const savedKeywords = wp.data.select('core/editor').getEditedPostAttribute('meta')._almaseo_keywords || '';
            
            if (savedKeywords) {
                const keywordArray = savedKeywords.split(',').map(k => k.trim()).filter(k => k);
                this.setState({ keywords: keywordArray }, () => {
                    if (keywordArray.length > 0) {
                        this.searchKeywords(keywordArray);
                    }
                });
            }
        };
        
        searchKeywords = (keywords = null) => {
            const searchKeywords = keywords || this.state.keywords;
            
            if (searchKeywords.length === 0) {
                return;
            }
            
            this.setState({ loading: true, error: null });
            
            const siteId = almaseoKI.siteId || '1';
            
            fetch('/wp-json/almaseo/v1/keyword-intelligence/search', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': almaseoKI.nonce
                },
                body: JSON.stringify({
                    site_id: siteId,
                    keywords: searchKeywords,
                    country_code: this.state.countryCode
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    this.setState({
                        metrics: data.data || {},
                        usage: data.metadata?.usage || this.state.usage,
                        loading: false
                    });
                } else {
                    this.setState({
                        error: data.message || 'Failed to fetch keyword data',
                        loading: false
                    });
                }
            })
            .catch(err => {
                this.setState({
                    error: 'Network error. Please try again.',
                    loading: false
                });
            });
        };
        
        getSuggestions = () => {
            const { searchTerm } = this.state;
            
            if (!searchTerm) {
                return;
            }
            
            this.setState({ loading: true, error: null });
            
            const siteId = almaseoKI.siteId || '1';
            
            fetch('/wp-json/almaseo/v1/keyword-intelligence/suggestions', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': almaseoKI.nonce
                },
                body: JSON.stringify({
                    site_id: siteId,
                    seed_keyword: searchTerm,
                    country_code: this.state.countryCode,
                    limit: 30
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    this.setState({
                        suggestions: data.data || [],
                        loading: false
                    });
                } else {
                    this.setState({
                        error: data.message || 'Failed to fetch suggestions',
                        loading: false
                    });
                }
            })
            .catch(err => {
                this.setState({
                    error: 'Network error. Please try again.',
                    loading: false
                });
            });
        };
        
        getTrends = () => {
            this.setState({ loading: true, error: null });
            
            const siteId = almaseoKI.siteId || '1';
            
            fetch(`/wp-json/almaseo/v1/keyword-intelligence/trends?site_id=${siteId}&days=30`, {
                headers: {
                    'X-WP-Nonce': almaseoKI.nonce
                }
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    this.setState({
                        trends: data.data || [],
                        loading: false
                    });
                } else {
                    this.setState({
                        error: data.message || 'Failed to fetch trends',
                        loading: false
                    });
                }
            })
            .catch(err => {
                this.setState({
                    error: 'Network error. Please try again.',
                    loading: false
                });
            });
        };
        
        addKeyword = (keyword) => {
            const { keywords } = this.state;
            
            if (!keywords.includes(keyword)) {
                const newKeywords = [...keywords, keyword];
                this.setState({ keywords: newKeywords });
                
                // Save to post meta
                this.props.setPostMeta({
                    _almaseo_keywords: newKeywords.join(', ')
                });
                
                // Search metrics for new keyword
                this.searchKeywords([keyword]);
            }
        };
        
        removeKeyword = (keyword) => {
            const { keywords } = this.state;
            const newKeywords = keywords.filter(k => k !== keyword);
            
            this.setState({ keywords: newKeywords });
            
            // Save to post meta
            this.props.setPostMeta({
                _almaseo_keywords: newKeywords.join(', ')
            });
        };
        
        saveKeywordAction = (keyword, action) => {
            const postId = this.props.postId;
            const siteId = almaseoKI.siteId || '1';
            
            fetch('/wp-json/almaseo/v1/keyword-intelligence/action', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': almaseoKI.nonce
                },
                body: JSON.stringify({
                    site_id: siteId,
                    post_id: postId,
                    keyword: keyword,
                    action: action
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    // Update UI to reflect action
                    if (action === 'pin') {
                        this.addKeyword(keyword);
                    } else if (action === 'ignore') {
                        this.removeKeyword(keyword);
                    }
                }
            })
            .catch(err => {
                console.error('Failed to save keyword action:', err);
            });
        };
        
        formatVolume = (volume) => {
            if (volume >= 1000000) {
                return (volume / 1000000).toFixed(1) + 'M';
            } else if (volume >= 1000) {
                return (volume / 1000).toFixed(1) + 'K';
            }
            return volume.toString();
        };
        
        getDifficultyColor = (difficulty) => {
            if (difficulty < 30) return '#4caf50';
            if (difficulty < 60) return '#ff9800';
            return '#f44336';
        };
        
        renderSearchTab = () => {
            const { keywords, metrics, loading, error } = this.state;
            
            return (
                <Fragment>
                    <PanelRow>
                        <TextControl
                            label="Add Keywords"
                            placeholder="Enter keywords separated by commas"
                            help="Track multiple keywords for this post"
                            value={keywords.join(', ')}
                            onChange={(value) => {
                                const newKeywords = value.split(',').map(k => k.trim()).filter(k => k);
                                this.setState({ keywords: newKeywords });
                            }}
                        />
                    </PanelRow>
                    
                    <PanelRow>
                        <Button
                            isPrimary
                            onClick={() => this.searchKeywords()}
                            disabled={loading || keywords.length === 0}
                        >
                            {loading ? <Spinner /> : 'Get Metrics'}
                        </Button>
                        
                        <Button
                            isSecondary
                            onClick={() => {
                                this.props.setPostMeta({
                                    _almaseo_keywords: keywords.join(', ')
                                });
                            }}
                            disabled={keywords.length === 0}
                        >
                            Save Keywords
                        </Button>
                    </PanelRow>
                    
                    {error && (
                        <Notice status="error" isDismissible={false}>
                            {error}
                        </Notice>
                    )}
                    
                    {Object.keys(metrics).length > 0 && (
                        <div className="ki-metrics-list">
                            <h4>Keyword Metrics</h4>
                            {keywords.map(keyword => {
                                const data = metrics[keyword.toLowerCase()];
                                if (!data) return null;
                                
                                return (
                                    <div key={keyword} className="ki-metric-item">
                                        <div className="ki-metric-header">
                                            <strong>{keyword}</strong>
                                            <Button
                                                isSmall
                                                isDestructive
                                                onClick={() => this.removeKeyword(keyword)}
                                            >
                                                ×
                                            </Button>
                                        </div>
                                        <div className="ki-metric-data">
                                            <span className="ki-volume">
                                                Vol: {this.formatVolume(data.volume || 0)}
                                            </span>
                                            <span 
                                                className="ki-difficulty"
                                                style={{ color: this.getDifficultyColor(data.difficulty || 0) }}
                                            >
                                                KD: {data.difficulty || 0}
                                            </span>
                                            {data.source === 'cache' && (
                                                <span className="ki-cached">📦</span>
                                            )}
                                        </div>
                                    </div>
                                );
                            })}
                        </div>
                    )}
                </Fragment>
            );
        };
        
        renderSuggestionsTab = () => {
            const { searchTerm, suggestions, loading, error } = this.state;
            
            return (
                <Fragment>
                    <PanelRow>
                        <TextControl
                            label="Seed Keyword"
                            placeholder="Enter a keyword to get suggestions"
                            value={searchTerm}
                            onChange={(value) => this.setState({ searchTerm: value })}
                        />
                    </PanelRow>
                    
                    <PanelRow>
                        <Button
                            isPrimary
                            onClick={this.getSuggestions}
                            disabled={loading || !searchTerm}
                        >
                            {loading ? <Spinner /> : 'Get Suggestions'}
                        </Button>
                    </PanelRow>
                    
                    {error && (
                        <Notice status="error" isDismissible={false}>
                            {error}
                        </Notice>
                    )}
                    
                    {suggestions.length > 0 && (
                        <div className="ki-suggestions-list">
                            <h4>Keyword Suggestions</h4>
                            {suggestions.map((item, index) => (
                                <div key={index} className="ki-suggestion-item">
                                    <div className="ki-suggestion-data">
                                        <strong>{item.keyword}</strong>
                                        <div className="ki-suggestion-metrics">
                                            <span>Vol: {this.formatVolume(item.search_volume || 0)}</span>
                                            <span style={{ color: this.getDifficultyColor(item.keyword_difficulty || 0) }}>
                                                KD: {item.keyword_difficulty || 0}
                                            </span>
                                        </div>
                                    </div>
                                    <div className="ki-suggestion-actions">
                                        <Button
                                            isSmall
                                            isPrimary
                                            onClick={() => this.saveKeywordAction(item.keyword, 'pin')}
                                            title="Pin this keyword"
                                        >
                                            📌
                                        </Button>
                                        <Button
                                            isSmall
                                            isSecondary
                                            onClick={() => this.addKeyword(item.keyword)}
                                            title="Add to tracked keywords"
                                        >
                                            +
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </Fragment>
            );
        };
        
        renderTrendsTab = () => {
            const { trends, loading, error } = this.state;
            
            if (trends.length === 0 && !loading) {
                // Load trends on first view
                this.getTrends();
            }
            
            return (
                <Fragment>
                    <PanelRow>
                        <Button
                            isPrimary
                            onClick={this.getTrends}
                            disabled={loading}
                        >
                            {loading ? <Spinner /> : 'Refresh Trends'}
                        </Button>
                    </PanelRow>
                    
                    {error && (
                        <Notice status="error" isDismissible={false}>
                            {error}
                        </Notice>
                    )}
                    
                    {trends.length > 0 && (
                        <div className="ki-trends-list">
                            <h4>Trending Keywords</h4>
                            {trends.map((trend, index) => (
                                <div key={index} className="ki-trend-item">
                                    <div className="ki-trend-data">
                                        <strong>{trend.keyword}</strong>
                                        <div className="ki-trend-change">
                                            <span className={`ki-trend-arrow ${trend.trending}`}>
                                                {trend.trending === 'up' ? '📈' : '📉'}
                                            </span>
                                            <span className="ki-trend-percent">
                                                {trend.change_percent > 0 ? '+' : ''}{trend.change_percent}%
                                            </span>
                                        </div>
                                        <div className="ki-trend-volumes">
                                            <span>Now: {this.formatVolume(trend.current_volume)}</span>
                                            <span>Was: {this.formatVolume(trend.previous_volume)}</span>
                                        </div>
                                    </div>
                                    <Button
                                        isSmall
                                        isSecondary
                                        onClick={() => this.addKeyword(trend.keyword)}
                                    >
                                        Track
                                    </Button>
                                </div>
                            ))}
                        </div>
                    )}
                    
                    {trends.length === 0 && !loading && (
                        <Notice status="info" isDismissible={false}>
                            No trending keywords found. Start tracking keywords to see trends over time.
                        </Notice>
                    )}
                </Fragment>
            );
        };
        
        render() {
            const { activeTab, countryCode, usage } = this.state;
            
            return (
                <PluginSidebar
                    name="almaseo-keyword-intelligence"
                    title="Keyword Intelligence"
                    icon="chart-line"
                >
                    <Panel>
                        <PanelBody title="Settings" initialOpen={false}>
                            <SelectControl
                                label="Country"
                                value={countryCode}
                                options={[
                                    { label: 'United States', value: 'US' },
                                    { label: 'United Kingdom', value: 'GB' },
                                    { label: 'Canada', value: 'CA' },
                                    { label: 'Australia', value: 'AU' },
                                    { label: 'Germany', value: 'DE' },
                                    { label: 'France', value: 'FR' },
                                    { label: 'Spain', value: 'ES' },
                                    { label: 'Italy', value: 'IT' },
                                    { label: 'Netherlands', value: 'NL' },
                                    { label: 'India', value: 'IN' }
                                ]}
                                onChange={(value) => this.setState({ countryCode: value })}
                            />
                            
                            <div className="ki-usage">
                                <strong>Daily Usage:</strong> {usage.used} / {usage.limit}
                                <progress value={usage.used} max={usage.limit}></progress>
                            </div>
                        </PanelBody>
                        
                        <PanelBody title="Keywords" initialOpen={true}>
                            <div className="ki-tabs">
                                <Button
                                    isSecondary={activeTab !== 'search'}
                                    isPrimary={activeTab === 'search'}
                                    onClick={() => this.setState({ activeTab: 'search' })}
                                >
                                    Search
                                </Button>
                                <Button
                                    isSecondary={activeTab !== 'suggestions'}
                                    isPrimary={activeTab === 'suggestions'}
                                    onClick={() => this.setState({ activeTab: 'suggestions' })}
                                >
                                    Suggestions
                                </Button>
                                <Button
                                    isSecondary={activeTab !== 'trends'}
                                    isPrimary={activeTab === 'trends'}
                                    onClick={() => this.setState({ activeTab: 'trends' })}
                                >
                                    Trends
                                </Button>
                            </div>
                            
                            <div className="ki-tab-content">
                                {activeTab === 'search' && this.renderSearchTab()}
                                {activeTab === 'suggestions' && this.renderSuggestionsTab()}
                                {activeTab === 'trends' && this.renderTrendsTab()}
                            </div>
                        </PanelBody>
                    </Panel>
                </PluginSidebar>
            );
        }
    }
    
    // Connect to WordPress data
    const KeywordIntelligenceWithData = compose([
        withSelect((select) => {
            return {
                postId: select('core/editor').getCurrentPostId(),
                postMeta: select('core/editor').getEditedPostAttribute('meta')
            };
        }),
        withDispatch((dispatch) => {
            return {
                setPostMeta: (meta) => {
                    dispatch('core/editor').editPost({ meta });
                }
            };
        })
    ])(KeywordIntelligence);
    
    // Register the plugin
    registerPlugin('almaseo-keyword-intelligence', {
        render: KeywordIntelligenceWithData,
        icon: 'chart-line'
    });
    
    // Add menu item
    const KeywordIntelligenceMenuItem = () => (
        <PluginSidebarMoreMenuItem
            target="almaseo-keyword-intelligence"
            icon="chart-line"
        >
            Keyword Intelligence
        </PluginSidebarMoreMenuItem>
    );
    
    registerPlugin('almaseo-keyword-intelligence-menu', {
        render: KeywordIntelligenceMenuItem
    });
    
})(window.wp, jQuery);