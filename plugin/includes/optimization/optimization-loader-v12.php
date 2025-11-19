<?php
/**
 * AlmaSEO Post-Writing Optimization Feature v1.2
 * 
 * Enhanced with GSC real positions, provider fallback, and UI polish
 */

if (!defined('ABSPATH')) {
    exit;
}

// Load provider interfaces and implementations
require_once __DIR__ . '/KeywordProviderInterface.php';
require_once __DIR__ . '/StubProvider.php';
require_once __DIR__ . '/GSCProvider-v12.php';
require_once __DIR__ . '/DataForSEOProvider.php';

// Load coming soon UI for unimplemented providers
require_once __DIR__ . '/coming-soon-ui.php';

use AlmaSEO\Optimization\KeywordProviderInterface;
use AlmaSEO\Optimization\StubProvider;
use AlmaSEO\Optimization\GSCProvider;
use AlmaSEO\Optimization\DataForSEOProvider;

class AlmaSEO_Optimization_V12 {
    
    private static $instance = null;
    private $provider = null;
    private $fallback_provider = null;
    private $provider_fallback = false;
    private $rate_limiter = [];
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        add_action('init', [$this, 'init']);
        add_action('enqueue_block_editor_assets', [$this, 'enqueue_editor_assets']);
        add_action('rest_api_init', [$this, 'register_rest_routes']);
        add_action('admin_menu', [$this, 'add_admin_menu']);
    }
    
    public function init() {
        // Register all post meta fields
        $this->register_post_meta_fields();
        
        // Initialize the provider
        $this->initializeProvider();
    }
    
    private function register_post_meta_fields() {
        // Quick wins data
        register_post_meta('', '_almaseo_quickwins', [
            'type' => 'object',
            'single' => true,
            'show_in_rest' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'keywords' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'keyword' => ['type' => 'string'],
                                    'position' => ['type' => 'number'], // Changed to number for floats
                                    'volume' => ['type' => 'integer'],
                                    'difficulty' => ['type' => 'integer'],
                                    'cpc' => ['type' => 'number'],
                                    'clicks' => ['type' => 'integer'],
                                    'impressions' => ['type' => 'integer'],
                                    'ctr' => ['type' => 'number'],
                                ]
                            ]
                        ],
                        'last_updated' => ['type' => 'string'],
                        'provider' => ['type' => 'string'],
                    ]
                ]
            ],
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
        
        // Keyword suggestions
        register_post_meta('', '_almaseo_keywordsuggestions', [
            'type' => 'object',
            'single' => true,
            'show_in_rest' => [
                'schema' => [
                    'type' => 'object',
                    'properties' => [
                        'suggestions' => [
                            'type' => 'array',
                            'items' => [
                                'type' => 'object',
                                'properties' => [
                                    'keyword' => ['type' => 'string'],
                                    'relevance' => ['type' => 'number'],
                                ]
                            ]
                        ],
                        'last_updated' => ['type' => 'string']
                    ]
                ]
            ],
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
        
        // Focus keyword
        register_post_meta('', '_almaseo_focus_keyword', [
            'type' => 'string',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
        
        // Pinned keywords
        register_post_meta('', '_almaseo_pinned_keywords', [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => [
                'schema' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ]
            ],
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
        
        // Ignored keywords
        register_post_meta('', '_almaseo_ignored_keywords', [
            'type' => 'array',
            'single' => true,
            'show_in_rest' => [
                'schema' => [
                    'type' => 'array',
                    'items' => ['type' => 'string']
                ]
            ],
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
        
        // Cache timestamp
        register_post_meta('', '_almaseo_kw_cached_at', [
            'type' => 'integer',
            'single' => true,
            'show_in_rest' => true,
            'auth_callback' => function() {
                return current_user_can('edit_posts');
            }
        ]);
    }
    
    private function initializeProvider() {
        $provider_id = get_option('almaseo_keyword_provider', 'stub');
        
        // Always initialize stub as fallback
        $this->fallback_provider = new StubProvider();
        
        switch ($provider_id) {
            case 'gsc':
                $this->provider = new GSCProvider();
                // Check if GSC is actually configured
                if (!$this->provider->isConfigured()) {
                    $this->provider_fallback = true;
                    $this->provider = $this->fallback_provider;
                }
                break;
                
            case 'dataforseo':
                $this->provider = new DataForSEOProvider();
                if (!$this->provider->isConfigured()) {
                    $this->provider_fallback = true;
                    $this->provider = $this->fallback_provider;
                }
                break;
                
            default:
                $this->provider = new StubProvider();
                break;
        }
    }
    
    public function enqueue_editor_assets() {
        global $post;
        if (!$post) return;
        
        // Enqueue the consolidated optimization sidebar script (includes v1.2 fixes for WP 6.6+)
        wp_enqueue_script(
            'almaseo-optimization-sidebar',
            ALMASEO_PLUGIN_URL . 'assets/js/optimization-sidebar-consolidated.js',
            ['wp-plugins', 'wp-editor', 'wp-edit-post', 'wp-element', 'wp-components', 'wp-data', 'wp-api-fetch', 'wp-compose'],
            '1.2.1',
            true
        );
        
        // Enqueue the consolidated optimization styles
        wp_enqueue_style(
            'almaseo-optimization-sidebar',
            ALMASEO_PLUGIN_URL . 'assets/css/optimization-sidebar-consolidated.css',
            [],
            '1.2.0'
        );
        
        // Localize script with necessary data
        wp_localize_script('almaseo-optimization-sidebar', 'almaseoOptimization', [
            'apiUrl' => rest_url('almaseo/v1/'),
            'nonce' => wp_create_nonce('wp_rest'),
            'postId' => $post->ID,
            'pluginUrl' => ALMASEO_PLUGIN_URL,
            'provider' => $this->provider->getName(),
            'providerFallback' => $this->provider_fallback,
        ]);
    }
    
    public function register_rest_routes() {
        // Refresh optimization data
        register_rest_route('almaseo/v1', '/optimization/refresh', [
            'methods' => 'POST',
            'callback' => [$this, 'refresh_optimization_data'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'args' => [
                'post_id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
            ],
        ]);
        
        // Set focus keyword
        register_rest_route('almaseo/v1', '/optimization/set-focus-keyword', [
            'methods' => 'POST',
            'callback' => [$this, 'set_focus_keyword'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'args' => [
                'post_id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
                'keyword' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_string($param) && !empty($param);
                    }
                ],
            ],
        ]);
        
        // Pin keyword
        register_rest_route('almaseo/v1', '/optimization/pin-keyword', [
            'methods' => 'POST',
            'callback' => [$this, 'pin_keyword'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'args' => [
                'post_id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
                'keyword' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_string($param) && !empty($param);
                    }
                ],
            ],
        ]);
        
        // Ignore keyword
        register_rest_route('almaseo/v1', '/optimization/ignore-keyword', [
            'methods' => 'POST',
            'callback' => [$this, 'ignore_keyword'],
            'permission_callback' => function() {
                return current_user_can('edit_posts');
            },
            'args' => [
                'post_id' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_numeric($param);
                    }
                ],
                'keyword' => [
                    'required' => true,
                    'validate_callback' => function($param) {
                        return is_string($param) && !empty($param);
                    }
                ],
            ],
        ]);
    }
    
    public function refresh_optimization_data($request) {
        $post_id = $request->get_param('post_id');
        $post = get_post($post_id);
        
        if (!$post) {
            return new WP_Error('post_not_found', 'Post not found', ['status' => 404]);
        }
        
        // Check rate limiting
        if (!$this->checkRateLimit()) {
            return new WP_Error('rate_limit', 'Rate limit exceeded. Please wait before refreshing again.', ['status' => 429]);
        }
        
        // Re-check provider configuration on each refresh
        $this->initializeProvider();
        
        // Extract keywords from title and content
        $title = $post->post_title;
        $content = $post->post_content;
        
        // Generate Quick Wins data using provider
        $result = $this->generate_quick_wins($title, $content, $post_id);

        // Check if provider returned "coming soon" status
        if (isset($result['coming_soon']) && $result['coming_soon'] === true) {
            // Return coming soon response with HTML
            return [
                'success' => true,
                'coming_soon' => true,
                'html' => almaseo_render_dataforseo_coming_soon(),
                'provider' => $result['provider'] ?? 'dataforseo',
                'message' => $result['message'] ?? 'This feature is coming soon.',
            ];
        }

        $quick_wins = $result['data'];
        $provider_notice = $result['notice'];
        $provider_fallback = $result['fallback'];

        update_post_meta($post_id, '_almaseo_quickwins', $quick_wins);

        // Generate Keyword Suggestions
        $suggestions = $this->generate_keyword_suggestions($title, $content);
        update_post_meta($post_id, '_almaseo_keywordsuggestions', $suggestions);

        // Update cache timestamp
        $cached_at = time();
        update_post_meta($post_id, '_almaseo_kw_cached_at', $cached_at);

        return [
            'success' => true,
            'quick_wins' => $quick_wins,
            'suggestions' => $suggestions,
            'provider_notice' => $provider_notice,
            'provider_fallback' => $provider_fallback,
            'provider' => $quick_wins['provider'] ?? 'stub',
            'cached_at' => $cached_at,
        ];
    }
    
    private function checkRateLimit() {
        // Simple rate limiting using transients
        $user_id = get_current_user_id();
        $transient_key = 'almaseo_rate_limit_' . $user_id;
        $requests = get_transient($transient_key);
        
        if ($requests === false) {
            set_transient($transient_key, 1, 600); // 10 minutes
            return true;
        }
        
        if ($requests >= 50) { // Max 50 requests per 10 minutes
            return false;
        }
        
        set_transient($transient_key, $requests + 1, 600);
        return true;
    }
    
    private function generate_quick_wins($title, $content, $post_id) {
        // Enhanced keyword extraction
        $keywords = $this->extract_enhanced_keywords($title, $content);
        
        // Limit to 15 keywords
        $keywords = array_slice($keywords, 0, 15);
        
        // Get metrics from provider
        $options = [
            'country' => get_option('almaseo_target_country', 'US'),
            'language' => get_option('almaseo_target_language', 'en'),
            'post_id' => $post_id,
        ];
        
        $metrics = [];
        $provider_notice = null;
        $provider_fallback = false;
        $actual_provider_id = $this->provider->getId();
        
        if (!empty($keywords)) {
            // Try to get metrics from provider
            try {
                $provider_metrics = $this->provider->fetchMetrics($keywords, $options);

                // Check if provider returned "coming soon" status
                if (isset($provider_metrics['coming_soon']) && $provider_metrics['coming_soon'] === true) {
                    // Return coming soon status with special handling
                    return [
                        'coming_soon' => true,
                        'provider' => $provider_metrics['provider'] ?? 'unknown',
                        'message' => $provider_metrics['message'] ?? 'This feature is coming soon.',
                        'data' => [
                            'keywords' => [],
                            'last_updated' => current_time('mysql'),
                            'provider' => $this->provider->getId(),
                        ],
                        'notice' => null,
                        'fallback' => false,
                    ];
                }

                // Map provider metrics to our format
                foreach ($provider_metrics as $metric) {
                    $metrics[] = [
                        'keyword' => $metric['term'],
                        'position' => $metric['position'],
                        'volume' => $metric['volume'],
                        'difficulty' => $metric['kd'],
                        'cpc' => $metric['cpc'] ?? null,
                        'clicks' => $metric['clicks'] ?? null,
                        'impressions' => $metric['impressions'] ?? null,
                        'ctr' => $metric['ctr'] ?? null,
                    ];
                }
            } catch (\Exception $e) {
                // Provider failed, use fallback
                $configured_provider = get_option('almaseo_keyword_provider', 'stub');
                
                if ($configured_provider !== 'stub') {
                    // We tried a real provider but it failed
                    $provider_notice = 'Live positions unavailable (GSC not configured). Showing sample estimates.';
                    $provider_fallback = true;
                    $actual_provider_id = 'stub';
                    
                    // Use stub provider as fallback
                    $stub_metrics = $this->fallback_provider->fetchMetrics($keywords, $options);
                    foreach ($stub_metrics as $metric) {
                        $metrics[] = [
                            'keyword' => $metric['term'],
                            'position' => $metric['position'],
                            'volume' => $metric['volume'],
                            'difficulty' => $metric['kd'],
                            'cpc' => $metric['cpc'] ?? null,
                        ];
                    }
                }
            }
        }
        
        return [
            'data' => [
                'keywords' => $metrics,
                'last_updated' => current_time('mysql'),
                'provider' => $actual_provider_id,
            ],
            'notice' => $provider_notice,
            'fallback' => $provider_fallback,
        ];
    }
    
    private function generate_keyword_suggestions($title, $content) {
        // Enhanced suggestion generation
        $suggestions = [];
        
        // Extract main keywords from content
        $content_text = strip_tags($content);
        $main_keywords = $this->extract_enhanced_keywords($title . ' ' . $content_text);
        
        // Generate variations and related terms
        $suggestion_candidates = $this->generate_keyword_variations($main_keywords);
        
        // Limit to 3 suggestions
        $suggestion_candidates = array_slice($suggestion_candidates, 0, 3);
        
        foreach ($suggestion_candidates as $candidate) {
            $suggestions[] = [
                'keyword' => $candidate,
                'relevance' => $this->calculate_relevance($candidate, $content_text),
            ];
        }
        
        return [
            'suggestions' => $suggestions,
            'last_updated' => current_time('mysql'),
        ];
    }
    
    private function extract_enhanced_keywords($title, $content) {
        $keywords = [];
        
        // Extract from title
        $title_keywords = $this->extract_keywords_from_text($title);
        
        // Extract from H1-H3 tags
        preg_match_all('/<h[1-3][^>]*>(.*?)<\/h[1-3]>/i', $content, $heading_matches);
        foreach ($heading_matches[1] as $heading) {
            $heading_keywords = $this->extract_keywords_from_text(strip_tags($heading));
            $title_keywords = array_merge($title_keywords, $heading_keywords);
        }
        
        // Extract frequent nouns and bigrams from body
        $body_text = strip_tags($content);
        $body_keywords = $this->extract_frequent_terms($body_text);
        
        // Combine and deduplicate
        $all_keywords = array_merge($title_keywords, $body_keywords);
        $all_keywords = array_unique($all_keywords);
        
        // Sort by frequency/importance
        $keyword_scores = [];
        foreach ($all_keywords as $keyword) {
            $score = 0;
            if (stripos($title, $keyword) !== false) $score += 10;
            if (preg_match('/<h1[^>]*>.*?' . preg_quote($keyword, '/') . '.*?<\/h1>/i', $content)) $score += 8;
            if (preg_match('/<h2[^>]*>.*?' . preg_quote($keyword, '/') . '.*?<\/h2>/i', $content)) $score += 5;
            $score += substr_count(strtolower($body_text), strtolower($keyword));
            $keyword_scores[$keyword] = $score;
        }
        
        arsort($keyword_scores);
        return array_keys($keyword_scores);
    }
    
    private function extract_frequent_terms($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        $words = explode(' ', $text);
        
        // Filter stop words
        $stop_words = $this->get_stop_words();
        $words = array_filter($words, function($word) use ($stop_words) {
            return strlen($word) > 2 && !in_array($word, $stop_words);
        });
        
        // Count word frequency
        $word_counts = array_count_values($words);
        arsort($word_counts);
        
        // Get top frequent words
        $frequent_words = array_slice(array_keys($word_counts), 0, 10);
        
        // Generate bigrams
        $bigrams = [];
        $words = explode(' ', $text);
        for ($i = 0; $i < count($words) - 1; $i++) {
            if (!in_array($words[$i], $stop_words) && !in_array($words[$i + 1], $stop_words)) {
                $bigram = $words[$i] . ' ' . $words[$i + 1];
                if (strlen($bigram) > 5) {
                    $bigrams[] = $bigram;
                }
            }
        }
        
        // Count bigram frequency
        $bigram_counts = array_count_values($bigrams);
        arsort($bigram_counts);
        $frequent_bigrams = array_slice(array_keys($bigram_counts), 0, 5);
        
        return array_merge($frequent_words, $frequent_bigrams);
    }
    
    private function extract_keywords_from_text($text) {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z0-9\s]/', '', $text);
        $words = explode(' ', $text);
        
        $stop_words = $this->get_stop_words();
        
        $keywords = array_filter($words, function($word) use ($stop_words) {
            return strlen($word) > 2 && !in_array($word, $stop_words);
        });
        
        return array_values($keywords);
    }
    
    private function get_stop_words() {
        return [
            'the', 'is', 'at', 'which', 'on', 'a', 'an', 'as', 'are', 'was', 'were', 
            'been', 'be', 'have', 'has', 'had', 'do', 'does', 'did', 'will', 'would', 
            'could', 'should', 'may', 'might', 'must', 'can', 'could', 'to', 'of', 
            'in', 'for', 'with', 'by', 'from', 'about', 'into', 'through', 'during', 
            'before', 'after', 'above', 'below', 'between', 'under', 'again', 'further', 
            'then', 'once', 'that', 'this', 'these', 'those', 'what', 'who', 'where', 
            'when', 'why', 'how', 'all', 'both', 'each', 'few', 'more', 'most', 'other', 
            'some', 'such', 'no', 'nor', 'not', 'only', 'own', 'same', 'so', 'than', 
            'too', 'very', 'just', 'but', 'and', 'or', 'if', 'while', 'because', 'since',
            'although', 'though', 'however', 'therefore', 'thus', 'hence', 'moreover',
            'furthermore', 'nevertheless', 'nonetheless', 'meanwhile', 'otherwise'
        ];
    }
    
    private function generate_keyword_variations($keywords) {
        $variations = [];
        
        foreach ($keywords as $keyword) {
            // Skip if already have enough variations
            if (count($variations) >= 10) break;
            
            // Add plural/singular variations
            if (substr($keyword, -1) === 's' && strlen($keyword) > 4) {
                $variations[] = substr($keyword, 0, -1);
            } elseif (substr($keyword, -1) !== 's') {
                $variations[] = $keyword . 's';
            }
            
            // Add common prefixes
            $prefixes = ['best', 'top', 'how to', 'guide', 'tips'];
            foreach ($prefixes as $prefix) {
                if (count($variations) >= 10) break;
                $variations[] = $prefix . ' ' . $keyword;
            }
            
            // Add common suffixes
            $suffixes = ['guide', 'tips', 'strategies', 'techniques'];
            foreach ($suffixes as $suffix) {
                if (count($variations) >= 10) break;
                $variations[] = $keyword . ' ' . $suffix;
            }
        }
        
        // Remove duplicates and original keywords
        $variations = array_unique($variations);
        $variations = array_diff($variations, $keywords);
        
        return array_values($variations);
    }
    
    private function calculate_relevance($keyword, $content) {
        // Simple relevance calculation based on semantic similarity
        $content_lower = strtolower($content);
        $keyword_lower = strtolower($keyword);
        
        // Check exact matches
        $exact_matches = substr_count($content_lower, $keyword_lower);
        
        // Check partial matches
        $keyword_parts = explode(' ', $keyword_lower);
        $partial_matches = 0;
        foreach ($keyword_parts as $part) {
            if (strlen($part) > 3) {
                $partial_matches += substr_count($content_lower, $part);
            }
        }
        
        // Calculate relevance score (0-1)
        $relevance = min(1, ($exact_matches * 0.3 + $partial_matches * 0.1) / 10);
        
        // Ensure minimum relevance of 0.3 for suggested keywords
        return max(0.3, $relevance);
    }
    
    public function set_focus_keyword($request) {
        $post_id = $request->get_param('post_id');
        $keyword = $request->get_param('keyword');
        
        update_post_meta($post_id, '_almaseo_focus_keyword', $keyword);
        
        return ['success' => true, 'focus_keyword' => $keyword];
    }
    
    public function pin_keyword($request) {
        $post_id = $request->get_param('post_id');
        $keyword = $request->get_param('keyword');
        
        $pinned = get_post_meta($post_id, '_almaseo_pinned_keywords', true);
        if (!is_array($pinned)) {
            $pinned = [];
        }
        
        if (!in_array($keyword, $pinned)) {
            $pinned[] = $keyword;
            update_post_meta($post_id, '_almaseo_pinned_keywords', $pinned);
        }
        
        // Remove from ignored if it was there
        $ignored = get_post_meta($post_id, '_almaseo_ignored_keywords', true);
        if (is_array($ignored)) {
            $ignored = array_diff($ignored, [$keyword]);
            update_post_meta($post_id, '_almaseo_ignored_keywords', $ignored);
        }
        
        return ['success' => true, 'pinned_keywords' => $pinned];
    }
    
    public function ignore_keyword($request) {
        $post_id = $request->get_param('post_id');
        $keyword = $request->get_param('keyword');
        
        $ignored = get_post_meta($post_id, '_almaseo_ignored_keywords', true);
        if (!is_array($ignored)) {
            $ignored = [];
        }
        
        if (!in_array($keyword, $ignored)) {
            $ignored[] = $keyword;
            update_post_meta($post_id, '_almaseo_ignored_keywords', $ignored);
        }
        
        // Remove from pinned if it was there
        $pinned = get_post_meta($post_id, '_almaseo_pinned_keywords', true);
        if (is_array($pinned)) {
            $pinned = array_diff($pinned, [$keyword]);
            update_post_meta($post_id, '_almaseo_pinned_keywords', $pinned);
        }
        
        return ['success' => true, 'ignored_keywords' => $ignored];
    }
    
    public function add_admin_menu() {
        // Add settings page under AlmaSEO menu
        add_submenu_page(
            'almaseo-settings', // Parent slug (if AlmaSEO menu exists)
            'Optimization Settings',
            'Optimization',
            'manage_options',
            'almaseo-optimization',
            [$this, 'render_settings_page']
        );
    }
    
    public function render_settings_page() {
        // Will be implemented in settings-optimization.php
        include __DIR__ . '/../admin/settings-optimization.php';
    }
}

// Initialize the optimization feature v1.2
AlmaSEO_Optimization_V12::get_instance();