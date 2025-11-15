<?php
/**
 * AlmaSEO Google Search Console Provider v1.2
 * 
 * Fetches real position data from GSC for the current post URL
 * @package AlmaSEO\Optimization
 */

namespace AlmaSEO\Optimization;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Google Search Console implementation with real API calls
 */
class GSCProvider implements KeywordProviderInterface {
    
    private $credentials;
    private $site_url;
    private $api_base = 'https://www.googleapis.com/webmasters/v3';
    
    public function __construct() {
        // Check for AlmaSEO connection first
        $this->credentials = $this->getAlmaSEOCredentials();
        $this->site_url = $this->getSiteUrl();
    }
    
    /**
     * Fetch real metrics from Google Search Console
     */
    public function fetchMetrics(array $keywords, array $options = []): array {
        if (!$this->isConfigured()) {
            throw new \Exception('GSC not configured');
        }
        
        // Get the current post URL
        $post_id = $options['post_id'] ?? get_the_ID();
        if (!$post_id) {
            return $this->returnEmptyMetrics($keywords);
        }
        
        $post_url = get_permalink($post_id);
        if (!$post_url) {
            return $this->returnEmptyMetrics($keywords);
        }
        
        // Fetch GSC data for this URL
        try {
            $gsc_data = $this->fetchGSCData($post_url);
            return $this->mapGSCDataToKeywords($keywords, $gsc_data);
        } catch (\Exception $e) {
            // Log error but don't break the flow
            error_log('AlmaSEO GSC Error: ' . $e->getMessage());
            return $this->returnEmptyMetrics($keywords);
        }
    }
    
    /**
     * Fetch data from GSC API for a specific URL
     */
    private function fetchGSCData($page_url) {
        $access_token = $this->getAccessToken();
        if (!$access_token) {
            throw new \Exception('No access token available');
        }
        
        // Prepare the request
        $site_url_encoded = urlencode($this->site_url);
        $endpoint = $this->api_base . '/sites/' . $site_url_encoded . '/searchAnalytics/query';
        
        // Calculate date range (last 28 days)
        $end_date = date('Y-m-d', strtotime('-1 day'));
        $start_date = date('Y-m-d', strtotime('-28 days'));
        
        // Build request body
        $request_body = [
            'startDate' => $start_date,
            'endDate' => $end_date,
            'dimensions' => ['query'],
            'dimensionFilterGroups' => [
                [
                    'filters' => [
                        [
                            'dimension' => 'page',
                            'operator' => 'equals',
                            'expression' => $page_url
                        ]
                    ]
                ]
            ],
            'rowLimit' => 100,
            'startRow' => 0
        ];
        
        // Make the API request
        $response = wp_remote_post($endpoint, [
            'headers' => [
                'Authorization' => 'Bearer ' . $access_token,
                'Content-Type' => 'application/json',
            ],
            'body' => json_encode($request_body),
            'timeout' => 30,
        ]);
        
        if (is_wp_error($response)) {
            throw new \Exception('GSC API request failed: ' . $response->get_error_message());
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['error'])) {
            throw new \Exception('GSC API error: ' . ($data['error']['message'] ?? 'Unknown error'));
        }
        
        return $data['rows'] ?? [];
    }
    
    /**
     * Map GSC data to our keyword list
     */
    private function mapGSCDataToKeywords($keywords, $gsc_data) {
        $results = [];
        
        // Create a map of GSC data for quick lookup
        $gsc_map = [];
        foreach ($gsc_data as $row) {
            if (isset($row['keys'][0])) {
                $query = $this->normalizeKeyword($row['keys'][0]);
                $gsc_map[$query] = [
                    'position' => $row['position'] ?? null,
                    'clicks' => $row['clicks'] ?? 0,
                    'impressions' => $row['impressions'] ?? 0,
                    'ctr' => $row['ctr'] ?? 0,
                ];
            }
        }
        
        // Map to our keywords
        foreach ($keywords as $keyword) {
            $normalized = $this->normalizeKeyword($keyword);
            $metric = [
                'term' => $keyword,
                'position' => null,
                'volume' => null,  // GSC doesn't provide volume
                'kd' => null,      // GSC doesn't provide difficulty
            ];
            
            // Try exact match first
            if (isset($gsc_map[$normalized])) {
                $metric['position'] = round($gsc_map[$normalized]['position'], 1);
                $metric['clicks'] = $gsc_map[$normalized]['clicks'];
                $metric['impressions'] = $gsc_map[$normalized]['impressions'];
                $metric['ctr'] = round($gsc_map[$normalized]['ctr'] * 100, 2);
            } else {
                // Try partial match
                foreach ($gsc_map as $gsc_query => $gsc_data) {
                    if ($this->isNearMatch($normalized, $gsc_query)) {
                        $metric['position'] = round($gsc_data['position'], 1);
                        $metric['clicks'] = $gsc_data['clicks'];
                        $metric['impressions'] = $gsc_data['impressions'];
                        $metric['ctr'] = round($gsc_data['ctr'] * 100, 2);
                        break;
                    }
                }
            }
            
            $results[] = $metric;
        }
        
        return $results;
    }
    
    /**
     * Normalize keyword for matching
     */
    private function normalizeKeyword($keyword) {
        $keyword = strtolower($keyword);
        $keyword = preg_replace('/[^a-z0-9\s]/', '', $keyword);
        $keyword = preg_replace('/\s+/', ' ', $keyword);
        return trim($keyword);
    }
    
    /**
     * Check if two keywords are a near match
     */
    private function isNearMatch($keyword1, $keyword2) {
        // Check if one contains the other
        if (strpos($keyword1, $keyword2) !== false || strpos($keyword2, $keyword1) !== false) {
            return true;
        }
        
        // Check similarity
        similar_text($keyword1, $keyword2, $percent);
        return $percent > 80;
    }
    
    /**
     * Return empty metrics for all keywords
     */
    private function returnEmptyMetrics($keywords) {
        return array_map(function($keyword) {
            return [
                'term' => $keyword,
                'position' => null,
                'volume' => null,
                'kd' => null,
            ];
        }, $keywords);
    }
    
    /**
     * Get AlmaSEO GSC credentials if available
     */
    private function getAlmaSEOCredentials() {
        // Check for AlmaSEO connection
        $almaseo_connection = get_option('almaseo_connection', []);
        
        if (!empty($almaseo_connection['gsc_access_token'])) {
            return [
                'access_token' => $almaseo_connection['gsc_access_token'],
                'refresh_token' => $almaseo_connection['gsc_refresh_token'] ?? '',
                'expires_at' => $almaseo_connection['gsc_token_expires'] ?? 0,
            ];
        }
        
        // Fallback to standalone GSC credentials
        return get_option('almaseo_gsc_credentials', []);
    }
    
    /**
     * Get the site URL for GSC
     */
    private function getSiteUrl() {
        // Try AlmaSEO connection first
        $almaseo_connection = get_option('almaseo_connection', []);
        if (!empty($almaseo_connection['gsc_site_url'])) {
            return $almaseo_connection['gsc_site_url'];
        }
        
        // Fallback to configured URL
        $configured_url = get_option('almaseo_gsc_site_url', '');
        if (!empty($configured_url)) {
            return $configured_url;
        }
        
        // Default to site URL
        return trailingslashit(get_site_url());
    }
    
    /**
     * Get valid access token (refresh if needed)
     */
    private function getAccessToken() {
        if (empty($this->credentials['access_token'])) {
            return false;
        }
        
        // Check if token is expired
        if (!empty($this->credentials['expires_at']) && $this->credentials['expires_at'] < time()) {
            // Try to refresh the token
            if (!empty($this->credentials['refresh_token'])) {
                $new_token = $this->refreshAccessToken($this->credentials['refresh_token']);
                if ($new_token) {
                    return $new_token;
                }
            }
            return false;
        }
        
        return $this->credentials['access_token'];
    }
    
    /**
     * Refresh the access token
     */
    private function refreshAccessToken($refresh_token) {
        $response = wp_remote_post('https://oauth2.googleapis.com/token', [
            'body' => [
                'refresh_token' => $refresh_token,
                'client_id' => get_option('almaseo_gsc_client_id', ''),
                'client_secret' => get_option('almaseo_gsc_client_secret', ''),
                'grant_type' => 'refresh_token',
            ],
        ]);
        
        if (is_wp_error($response)) {
            return false;
        }
        
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);
        
        if (isset($data['access_token'])) {
            // Update stored credentials
            $this->credentials['access_token'] = $data['access_token'];
            $this->credentials['expires_at'] = time() + ($data['expires_in'] ?? 3600);
            
            // Save updated credentials
            $almaseo_connection = get_option('almaseo_connection', []);
            $almaseo_connection['gsc_access_token'] = $data['access_token'];
            $almaseo_connection['gsc_token_expires'] = $this->credentials['expires_at'];
            update_option('almaseo_connection', $almaseo_connection);
            
            return $data['access_token'];
        }
        
        return false;
    }
    
    /**
     * Check if GSC is configured
     */
    public function isConfigured(): bool {
        return !empty($this->credentials) && 
               !empty($this->site_url) && 
               !empty($this->credentials['access_token']);
    }
    
    /**
     * Get provider name
     */
    public function getName(): string {
        return 'Google Search Console';
    }
    
    /**
     * Get provider ID
     */
    public function getId(): string {
        return 'gsc';
    }
    
    /**
     * Get rate limits for GSC API
     */
    public function getRateLimits(): array {
        return [
            'requests_per_minute' => 10,
            'keywords_per_request' => 100,
            'daily_limit' => 1000,
        ];
    }
    
    /**
     * GSC data should be cached
     */
    public function shouldCache(): bool {
        return true;
    }
    
    /**
     * Cache GSC data for 1 hour
     */
    public function getCacheDuration(): int {
        return 3600; // 1 hour
    }
}