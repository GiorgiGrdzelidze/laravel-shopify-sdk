<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Clients;

use Illuminate\Support\Facades\Cache;

/**
 * Rate Limiter
 *
 * Manages rate limiting for Shopify API requests (REST and GraphQL).
 * Tracks request costs and implements intelligent throttling to prevent 429 errors.
 *
 * @package LaravelShopifySdk\Clients
 */
class RateLimiter
{
    protected string $type;

    public function __construct(string $type = 'rest')
    {
        $this->type = $type;
    }

    /**
     * Throttle request if necessary.
     *
     * @param string $shopDomain
     * @return void
     */
    public function throttle(string $shopDomain): void
    {
        if ($this->type === 'graphql') {
            $this->throttleGraphQL($shopDomain);
        } else {
            $this->throttleRest($shopDomain);
        }
    }

    /**
     * Update rate limit state.
     *
     * @param string $shopDomain
     * @param array<string, mixed> $state
     * @return void
     */
    public function updateState(string $shopDomain, array $state): void
    {
        $key = $this->getCacheKey($shopDomain);
        Cache::put($key, $state, now()->addMinutes(5));
    }

    /**
     * Throttle GraphQL requests based on cost.
     *
     * @param string $shopDomain
     * @return void
     */
    protected function throttleGraphQL(string $shopDomain): void
    {
        $state = $this->getState($shopDomain);

        if (!$state) {
            return;
        }

        $available = $state['available'] ?? 1000;
        $throttleThreshold = config('shopify.rate_limits.graphql.throttle_on_cost', 800);

        if ($available < $throttleThreshold) {
            $restoreRate = $state['restore_rate'] ?? 50;
            $needed = $throttleThreshold - $available;
            $waitSeconds = ceil($needed / $restoreRate);

            if ($waitSeconds > 0) {
                sleep((int) min($waitSeconds, 10));
            }
        }
    }

    /**
     * Throttle REST requests based on bucket.
     *
     * @param string $shopDomain
     * @return void
     */
    protected function throttleRest(string $shopDomain): void
    {
        $state = $this->getState($shopDomain);

        if (!$state) {
            return;
        }

        $current = $state['current'] ?? 0;
        $max = $state['max'] ?? 40;

        if ($current >= $max - 5) {
            $leakRate = config('shopify.rate_limits.rest.leak_rate', 2);
            $waitSeconds = ceil(($current - ($max - 5)) / $leakRate);

            if ($waitSeconds > 0) {
                sleep((int) min($waitSeconds, 10));
            }
        }
    }

    /**
     * Get current rate limit state.
     *
     * @param string $shopDomain
     * @return array<string, mixed>|null
     */
    protected function getState(string $shopDomain): ?array
    {
        $key = $this->getCacheKey($shopDomain);
        return Cache::get($key);
    }

    /**
     * Get cache key for shop domain.
     *
     * @param string $shopDomain
     * @return string
     */
    protected function getCacheKey(string $shopDomain): string
    {
        return "shopify_rate_limit_{$this->type}_{$shopDomain}";
    }
}
