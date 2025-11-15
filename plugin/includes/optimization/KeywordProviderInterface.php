<?php
/**
 * AlmaSEO Keyword Provider Interface
 * 
 * Defines the contract for keyword data providers
 * @package AlmaSEO\Optimization
 */

namespace AlmaSEO\Optimization;

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Interface for keyword metric providers
 */
interface KeywordProviderInterface {
    
    /**
     * Fetch metrics for a list of keywords
     * 
     * @param array $keywords Array of keyword strings to fetch metrics for
     * @param array $options Additional options like country code, language, etc.
     * @return array Array of keyword metrics with structure:
     *               [
     *                   [
     *                       'term' => 'keyword',
     *                       'position' => int|null,
     *                       'volume' => int|null,
     *                       'kd' => int|null, // Keyword difficulty 0-100
     *                       'cpc' => float|null, // Cost per click
     *                       'trend' => array|null, // Monthly trend data
     *                   ],
     *                   ...
     *               ]
     */
    public function fetchMetrics(array $keywords, array $options = []): array;
    
    /**
     * Check if the provider is properly configured
     * 
     * @return bool True if provider has all required credentials/settings
     */
    public function isConfigured(): bool;
    
    /**
     * Get provider name for display
     * 
     * @return string Human-readable provider name
     */
    public function getName(): string;
    
    /**
     * Get provider identifier
     * 
     * @return string Machine-readable provider ID
     */
    public function getId(): string;
    
    /**
     * Get rate limit info
     * 
     * @return array Rate limit information
     *               [
     *                   'requests_per_minute' => int,
     *                   'keywords_per_request' => int,
     *                   'daily_limit' => int|null,
     *               ]
     */
    public function getRateLimits(): array;
    
    /**
     * Check if caching is recommended for this provider
     * 
     * @return bool True if results should be cached
     */
    public function shouldCache(): bool;
    
    /**
     * Get cache duration in seconds
     * 
     * @return int Cache duration in seconds
     */
    public function getCacheDuration(): int;
}