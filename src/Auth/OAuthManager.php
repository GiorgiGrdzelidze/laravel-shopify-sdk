<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Auth;

use LaravelShopifySdk\Exceptions\OAuthException;
use LaravelShopifySdk\Models\Store;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * OAuth Manager
 *
 * Handles Shopify OAuth Authorization Code Grant flow.
 * Manages authorization URL generation, callback verification, and token exchange.
 *
 * @package LaravelShopifySdk\Auth
 */
class OAuthManager
{
    public function __construct(
        protected StoreRepository $storeRepository,
        protected HmacValidator $hmacValidator
    ) {}

    /**
     * Generate OAuth authorization URL.
     *
     * @param string $shopDomain
     * @param string|null $state
     * @return string
     */
    public function getAuthorizationUrl(string $shopDomain, ?string $state = null): string
    {
        $shopDomain = $this->sanitizeShopDomain($shopDomain);
        $clientId = config('shopify.oauth.client_id');
        $scopes = config('shopify.oauth.scopes');
        $redirectUri = $this->getRedirectUri();

        $params = [
            'client_id' => $clientId,
            'scope' => $scopes,
            'redirect_uri' => $redirectUri,
        ];

        if ($state) {
            $params['state'] = $state;
        }

        $query = http_build_query($params);

        return "https://{$shopDomain}/admin/oauth/authorize?{$query}";
    }

    /**
     * Handle OAuth callback and exchange code for access token.
     *
     * @param array<string, mixed> $params
     * @return Store
     * @throws OAuthException
     */
    public function handleCallback(array $params): Store
    {
        if (!$this->validateCallback($params)) {
            Log::warning('OAuth callback HMAC validation failed', [
                'shop' => $params['shop'] ?? 'unknown',
            ]);
            throw new OAuthException('Invalid HMAC signature');
        }

        $shopDomain = $this->sanitizeShopDomain($params['shop']);
        $code = $params['code'];

        $accessToken = $this->exchangeCodeForToken($shopDomain, $code);

        $store = $this->storeRepository->createOrUpdate(
            $shopDomain,
            $accessToken,
            config('shopify.oauth.scopes')
        );

        Log::info('OAuth installation completed', [
            'shop_domain' => $shopDomain,
            'store_id' => $store->id,
        ]);

        return $store;
    }

    /**
     * Validate OAuth callback parameters.
     *
     * @param array<string, mixed> $params
     * @return bool
     */
    public function validateCallback(array $params): bool
    {
        $secret = config('shopify.oauth.client_secret');

        if (!$secret) {
            throw new OAuthException('Client secret not configured');
        }

        return $this->hmacValidator->validateOAuthCallback($params, $secret);
    }

    /**
     * Exchange authorization code for access token.
     *
     * @param string $shopDomain
     * @param string $code
     * @return string
     * @throws OAuthException
     */
    protected function exchangeCodeForToken(string $shopDomain, string $code): string
    {
        $clientId = config('shopify.oauth.client_id');
        $clientSecret = config('shopify.oauth.client_secret');

        $response = Http::timeout(30)
            ->post("https://{$shopDomain}/admin/oauth/access_token", [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'code' => $code,
            ]);

        if (!$response->successful()) {
            Log::error('Failed to exchange OAuth code for token', [
                'shop_domain' => $shopDomain,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);
            throw new OAuthException('Failed to exchange code for access token');
        }

        $data = $response->json();

        if (!isset($data['access_token'])) {
            throw new OAuthException('Access token not found in response');
        }

        return $data['access_token'];
    }

    /**
     * Sanitize shop domain.
     *
     * @param string $shopDomain
     * @return string
     */
    protected function sanitizeShopDomain(string $shopDomain): string
    {
        $shopDomain = trim(strtolower($shopDomain));
        $shopDomain = preg_replace('/^https?:\/\//', '', $shopDomain);
        $shopDomain = preg_replace('/\/$/', '', $shopDomain);

        if (!str_ends_with($shopDomain, '.myshopify.com')) {
            $shopDomain = $shopDomain . '.myshopify.com';
        }

        return $shopDomain;
    }

    /**
     * Get OAuth redirect URI.
     *
     * @return string
     */
    protected function getRedirectUri(): string
    {
        $configUri = config('shopify.oauth.redirect_uri');

        if (filter_var($configUri, FILTER_VALIDATE_URL)) {
            return $configUri;
        }

        return url($configUri);
    }
}
