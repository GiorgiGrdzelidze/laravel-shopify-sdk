<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Clients;

use LaravelShopifySdk\Exceptions\ShopifyApiException;
use LaravelShopifySdk\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * GraphQL Client
 *
 * Handles GraphQL API requests to Shopify Admin API with rate limiting and retries.
 * Implements cost-based throttling and cursor pagination for efficient data fetching.
 *
 * @package LaravelShopifySdk\Clients
 */
class GraphQLClient
{
    protected RateLimiter $rateLimiter;

    public function __construct()
    {
        $this->rateLimiter = new RateLimiter('graphql');
    }

    /**
     * Execute GraphQL query.
     *
     * @param Store $store
     * @param string $query
     * @param array<string, mixed> $variables
     * @return array<string, mixed>
     * @throws ShopifyApiException
     */
    public function query(Store $store, string $query, array $variables = []): array
    {
        $this->rateLimiter->throttle($store->shop_domain);

        $url = $this->buildUrl($store->shop_domain);
        $attempt = 0;
        $maxAttempts = config('shopify.client.retry_times', 3);

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $payload = ['query' => $query];
                if (!empty($variables)) {
                    $payload['variables'] = $variables;
                }

                $response = Http::withHeaders([
                    'X-Shopify-Access-Token' => $store->access_token,
                    'Content-Type' => 'application/json',
                ])
                    ->timeout(config('shopify.client.timeout', 30))
                    ->post($url, $payload);

                if ($response->status() === 429) {
                    $this->handleRateLimit($store->shop_domain, $response, $attempt, $maxAttempts);
                    continue;
                }

                if ($response->serverError()) {
                    $this->handleServerError($store->shop_domain, $response, $attempt, $maxAttempts);
                    continue;
                }

                if (!$response->successful()) {
                    throw new ShopifyApiException(
                        "GraphQL request failed: {$response->status()} - {$response->body()}"
                    );
                }

                $data = $response->json();

                $this->updateRateLimitState($store->shop_domain, $response);

                if (isset($data['errors']) && !empty($data['errors'])) {
                    Log::warning('GraphQL query returned errors', [
                        'shop_domain' => $store->shop_domain,
                        'errors' => $data['errors'],
                    ]);
                }

                return $data;

            } catch (\Exception $e) {
                if ($attempt >= $maxAttempts) {
                    throw new ShopifyApiException(
                        "GraphQL request failed after {$maxAttempts} attempts: {$e->getMessage()}",
                        0,
                        $e
                    );
                }

                $this->backoff($attempt);
            }
        }

        throw new ShopifyApiException('GraphQL request failed');
    }

    /**
     * Execute paginated GraphQL query with cursor.
     *
     * @param Store $store
     * @param string $query
     * @param array<string, mixed> $variables
     * @param callable $callback
     * @return void
     * @throws ShopifyApiException
     */
    public function paginate(Store $store, string $query, array $variables, callable $callback): void
    {
        $hasNextPage = true;
        $cursor = null;

        while ($hasNextPage) {
            $vars = array_merge($variables, ['cursor' => $cursor]);
            $response = $this->query($store, $query, $vars);

            $callback($response);

            $pageInfo = $this->extractPageInfo($response);
            $hasNextPage = $pageInfo['hasNextPage'] ?? false;
            $cursor = $pageInfo['endCursor'] ?? null;

            if (!$cursor) {
                break;
            }
        }
    }

    /**
     * Build GraphQL API URL.
     *
     * @param string $shopDomain
     * @return string
     */
    protected function buildUrl(string $shopDomain): string
    {
        $version = config('shopify.api_version', '2024-01');
        return "https://{$shopDomain}/admin/api/{$version}/graphql.json";
    }

    /**
     * Extract page info from GraphQL response.
     *
     * @param array<string, mixed> $response
     * @return array<string, mixed>
     */
    protected function extractPageInfo(array $response): array
    {
        $data = $response['data'] ?? [];

        foreach ($data as $value) {
            if (is_array($value) && isset($value['pageInfo'])) {
                return $value['pageInfo'];
            }
        }

        return [];
    }

    /**
     * Handle rate limit response.
     *
     * @param string $shopDomain
     * @param \Illuminate\Http\Client\Response $response
     * @param int $attempt
     * @param int $maxAttempts
     * @return void
     * @throws ShopifyApiException
     */
    protected function handleRateLimit(string $shopDomain, $response, int $attempt, int $maxAttempts): void
    {
        if ($attempt >= $maxAttempts) {
            throw new ShopifyApiException('Rate limit exceeded after max retries');
        }

        $retryAfter = $response->header('Retry-After') ?? 2;

        Log::warning('GraphQL rate limit hit, retrying', [
            'shop_domain' => $shopDomain,
            'retry_after' => $retryAfter,
            'attempt' => $attempt,
        ]);

        sleep((int) $retryAfter);
    }

    /**
     * Handle server error response.
     *
     * @param string $shopDomain
     * @param \Illuminate\Http\Client\Response $response
     * @param int $attempt
     * @param int $maxAttempts
     * @return void
     * @throws ShopifyApiException
     */
    protected function handleServerError(string $shopDomain, $response, int $attempt, int $maxAttempts): void
    {
        if ($attempt >= $maxAttempts) {
            throw new ShopifyApiException(
                "Server error after {$maxAttempts} attempts: {$response->status()}"
            );
        }

        Log::warning('GraphQL server error, retrying', [
            'shop_domain' => $shopDomain,
            'status' => $response->status(),
            'attempt' => $attempt,
        ]);

        $this->backoff($attempt);
    }

    /**
     * Update rate limit state from response headers.
     *
     * @param string $shopDomain
     * @param \Illuminate\Http\Client\Response $response
     * @return void
     */
    protected function updateRateLimitState(string $shopDomain, $response): void
    {
        $extensions = $response->json('extensions.cost');

        if ($extensions) {
            $this->rateLimiter->updateState($shopDomain, [
                'requested_cost' => $extensions['requestedQueryCost'] ?? 0,
                'actual_cost' => $extensions['actualQueryCost'] ?? 0,
                'available' => $extensions['throttleStatus']['currentlyAvailable'] ?? 0,
                'maximum' => $extensions['throttleStatus']['maximumAvailable'] ?? 1000,
                'restore_rate' => $extensions['throttleStatus']['restoreRate'] ?? 50,
            ]);
        }
    }

    /**
     * Exponential backoff delay.
     *
     * @param int $attempt
     * @return void
     */
    protected function backoff(int $attempt): void
    {
        $delay = min(
            config('shopify.client.retry_delay', 1000) * pow(2, $attempt - 1),
            config('shopify.client.max_backoff', 32000)
        );

        usleep($delay * 1000);
    }
}
