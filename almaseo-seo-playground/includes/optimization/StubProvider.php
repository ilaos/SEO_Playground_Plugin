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
     * Mutable seed driving the deterministic stub RNG.
     *
     * @var int
     */
    private $seed = 0;

    /**
     * Deterministic pseudo-random integer in [$min, $max], driven by $this->seed.
     *
     * wp_rand() cannot be seeded, so a tiny LCG is used instead to keep the sample
     * metrics stable for a given keyword — which is the whole point of "consistent"
     * stub data. These are non-cryptographic placeholder numbers, never security-sensitive.
     */
    private function seededRand(int $min, int $max): int {
        $this->seed = ($this->seed * 1103515245 + 12345) & 0x7fffffff;
        return $min + ($this->seed % ($max - $min + 1));
    }

    /**
     * Fetch stubbed metrics for keywords
     */
    public function fetchMetrics(array $keywords, array $options = []): array {
        $results = [];

        foreach ($keywords as $keyword) {
            // Seed the deterministic RNG from the keyword so identical keywords
            // always produce identical sample metrics.
            $this->seed = crc32($keyword) & 0x7fffffff;

            $results[] = [
                'term' => $keyword,
                'position' => $this->seededRand(5, 20),
                'volume' => round($this->seededRand(100, 10000) / 10) * 10, // Round to nearest 10
                'kd' => $this->seededRand(20, 80),
                'cpc' => round($this->seededRand(50, 500) / 100, 2), // $0.50 to $5.00
                'trend' => $this->generateTrendData(),
            ];
        }

        return $results;
    }

    /**
     * Generate sample trend data
     */
    private function generateTrendData(): array {
        $trend = [];
        $base = $this->seededRand(500, 2000);

        for ($i = 0; $i < 12; $i++) {
            $variation = $this->seededRand(-30, 30) / 100; // -30% to +30% variation
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