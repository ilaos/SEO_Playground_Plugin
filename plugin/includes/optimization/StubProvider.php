<?php
/**
 * AlmaSEO Stub Keyword Provider
 * 
 * Provides stubbed/sample keyword metrics for development and testing
 * @package AlmaSEO\Optimization
 */

namespace AlmaSEO\Optimization;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Stub implementation of keyword provider
 */
class StubProvider implements KeywordProviderInterface {
    
    /**
     * Fetch stubbed metrics for keywords
     */
    public function fetchMetrics(array $keywords, array $options = []): array {
        $results = [];
        
        foreach ($keywords as $keyword) {
            // Generate consistent but random-looking metrics based on keyword
            $seed = crc32($keyword);
            srand($seed);
            
            $results[] = [
                'term' => $keyword,
                'position' => rand(5, 20),
                'volume' => round(rand(100, 10000) / 10) * 10, // Round to nearest 10
                'kd' => rand(20, 80),
                'cpc' => round(rand(50, 500) / 100, 2), // $0.50 to $5.00
                'trend' => $this->generateTrendData(),
            ];
        }
        
        // Reset random seed
        srand();
        
        return $results;
    }
    
    /**
     * Generate sample trend data
     */
    private function generateTrendData(): array {
        $trend = [];
        $base = rand(500, 2000);
        
        for ($i = 0; $i < 12; $i++) {
            $variation = rand(-30, 30) / 100; // -30% to +30% variation
            $trend[] = round($base * (1 + $variation));
        }
        
        return $trend;
    }
    
    /**
     * Stub provider is always configured
     */
    public function isConfigured(): bool {
        return true;
    }
    
    /**
     * Get provider name
     */
    public function getName(): string {
        return 'Sample Data (Stub)';
    }
    
    /**
     * Get provider ID
     */
    public function getId(): string {
        return 'stub';
    }
    
    /**
     * Get rate limits
     */
    public function getRateLimits(): array {
        return [
            'requests_per_minute' => 1000, // Unlimited for stub
            'keywords_per_request' => 100,
            'daily_limit' => null,
        ];
    }
    
    /**
     * Caching not necessary for stub data
     */
    public function shouldCache(): bool {
        return false;
    }
    
    /**
     * Short cache duration for stub
     */
    public function getCacheDuration(): int {
        return 300; // 5 minutes
    }
}