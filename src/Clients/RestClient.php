<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Clients;

use LaravelShopifySdk\Exceptions\ShopifyApiException;
use LaravelShopifySdk\Models\Core\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * REST Client
 *
 * Handles REST API requests to Shopify Admin API with rate limiting and retries.
 * Implements bucket-based rate limiting and page-based pagination.
 *
 * @package LaravelShopifySdk\Clients
 */
class RestClient
{
    protected RateLimiter $rateLimiter;

    public function __construct()
    {
        $this->rateLimiter = new RateLimiter('rest');
    }

    /**
     * Execute GET request.
     *
     * @param Store $store
     * @param string $endpoint
     * @param array<string, mixed> $params
     * @return array<string, mixed>
     * @throws ShopifyApiException
     */
    public function get(Store $store, string $endpoint, array $params = []): array
    {
        return $this->request($store, 'GET', $endpoint, $params);
    }

    /**
     * Execute POST request.
     *
     * @param Store $store
     * @param string $endpoint
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws ShopifyApiException
     */
    public function post(Store $store, string $endpoint, array $data = []): array
    {
        return $this->request($store, 'POST', $endpoint, $data);
    }

    /**
     * Execute PUT request.
     *
     * @param Store $store
     * @param string $endpoint
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws ShopifyApiException
     */
    public function put(Store $store, string $endpoint, array $data = []): array
    {
        return $this->request($store, 'PUT', $endpoint, $data);
    }

    /**
     * Execute DELETE request.
     *
     * @param Store $store
     * @param string $endpoint
     * @return array<string, mixed>
     * @throws ShopifyApiException
     */
    public function delete(Store $store, string $endpoint): array
    {
        return $this->request($store, 'DELETE', $endpoint);
    }

    /**
     * Execute paginated GET request using Shopify's Link header cursor pagination.
     *
     * @param Store $store
     * @param string $endpoint
     * @param array<string, mixed> $params
     * @param callable $callback
     * @return void
     * @throws ShopifyApiException
     */
    public function paginate(Store $store, string $endpoint, array $params, callable $callback): void
    {
        $params['limit'] = $params['limit'] ?? 250;
        $nextUrl = null;

        do {
            if ($nextUrl) {
                // On subsequent pages, use the full URL from Link header with only limit
                $response = $this->requestRaw($store, 'GET', $nextUrl, ['limit' => $params['limit']], true);
            } else {
                $response = $this->requestRaw($store, 'GET', $this->buildUrl($store->shop_domain, $endpoint), $params);
            }

            $callback($response->json() ?? []);

            $nextUrl = $this->extractNextPageUrl($response);

        } while ($nextUrl);
    }

    /**
     * Execute HTTP request with retries and rate limiting.
     *
     * @param Store $store
     * @param string $method
     * @param string $endpoint
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     * @throws ShopifyApiException
     */
    protected function request(Store $store, string $method, string $endpoint, array $data = []): array
    {
        $this->rateLimiter->throttle($store->shop_domain);

        $url = $this->buildUrl($store->shop_domain, $endpoint);
        $attempt = 0;
        $maxAttempts = config('shopify.client.retry_times', 3);

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $request = Http::withHeaders([
                    'X-Shopify-Access-Token' => $store->access_token,
                    'Content-Type' => 'application/json',
                ])->timeout(config('shopify.client.timeout', 30));

                $response = match ($method) {
                    'GET' => $request->get($url, $data),
                    'POST' => $request->post($url, $data),
                    'PUT' => $request->put($url, $data),
                    'DELETE' => $request->delete($url),
                    default => throw new ShopifyApiException("Unsupported HTTP method: {$method}"),
                };

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
                        "REST request failed: {$response->status()} - {$response->body()}"
                    );
                }

                $this->updateRateLimitState($store->shop_domain, $response);

                return $response->json() ?? [];

            } catch (\Exception $e) {
                if ($attempt >= $maxAttempts) {
                    throw new ShopifyApiException(
                        "REST request failed after {$maxAttempts} attempts: {$e->getMessage()}",
                        0,
                        $e
                    );
                }

                $this->backoff($attempt);
            }
        }

        throw new ShopifyApiException('REST request failed');
    }

    /**
     * Build REST API URL.
     *
     * @param string $shopDomain
     * @param string $endpoint
     * @return string
     */
    protected function buildUrl(string $shopDomain, string $endpoint): string
    {
        $version = config('shopify.api_version', '2026-04');
        $endpoint = ltrim($endpoint, '/');
        return "https://{$shopDomain}/admin/api/{$version}/{$endpoint}";
    }

    /**
     * Execute HTTP request returning the full Response object (for header access).
     *
     * @param Store $store
     * @param string $method
     * @param string $url
     * @param array<string, mixed> $data
     * @param bool $isFullUrl Whether $url is already a complete URL
     * @return \Illuminate\Http\Client\Response
     * @throws ShopifyApiException
     */
    protected function requestRaw(Store $store, string $method, string $url, array $data = [], bool $isFullUrl = false): \Illuminate\Http\Client\Response
    {
        $this->rateLimiter->throttle($store->shop_domain);

        $attempt = 0;
        $maxAttempts = config('shopify.client.retry_times', 3);

        while ($attempt < $maxAttempts) {
            $attempt++;

            try {
                $request = Http::withHeaders([
                    'X-Shopify-Access-Token' => $store->access_token,
                    'Content-Type' => 'application/json',
                ])->timeout(config('shopify.client.timeout', 30));

                $response = match ($method) {
                    'GET' => $request->get($url, $data),
                    default => throw new ShopifyApiException("Unsupported method for raw request: {$method}"),
                };

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
                        "REST request failed: {$response->status()} - {$response->body()}"
                    );
                }

                $this->updateRateLimitState($store->shop_domain, $response);

                return $response;

            } catch (\Exception $e) {
                if ($attempt >= $maxAttempts) {
                    throw new ShopifyApiException(
                        "REST request failed after {$maxAttempts} attempts: {$e->getMessage()}",
                        0,
                        $e
                    );
                }

                $this->backoff($attempt);
            }
        }

        throw new ShopifyApiException('REST request failed');
    }

    /**
     * Extract the "next" page URL from the Link header.
     *
     * Shopify returns: <https://...?page_info=abc&limit=250>; rel="next"
     *
     * @param \Illuminate\Http\Client\Response $response
     * @return string|null
     */
    protected function extractNextPageUrl(\Illuminate\Http\Client\Response $response): ?string
    {
        $linkHeader = $response->header('Link');

        if (!$linkHeader) {
            return null;
        }

        // Parse Link header — may contain multiple entries separated by commas
        $links = explode(',', $linkHeader);

        foreach ($links as $link) {
            if (str_contains($link, 'rel="next"')) {
                // Extract URL from angle brackets: <URL>; rel="next"
                if (preg_match('/<([^>]+)>/', trim($link), $matches)) {
                    return $matches[1];
                }
            }
        }

        return null;
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

        Log::warning('REST rate limit hit, retrying', [
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

        Log::warning('REST server error, retrying', [
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
        $callLimit = $response->header('X-Shopify-Shop-Api-Call-Limit');

        if ($callLimit && str_contains($callLimit, '/')) {
            [$current, $max] = explode('/', $callLimit);

            $this->rateLimiter->updateState($shopDomain, [
                'current' => (int) $current,
                'max' => (int) $max,
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
