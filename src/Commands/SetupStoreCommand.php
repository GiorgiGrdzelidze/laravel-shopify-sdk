<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use LaravelShopifySdk\Models\Core\Store;

/**
 * Setup Store Command
 *
 * Creates or updates a store from environment credentials.
 * Supports both legacy access tokens and OAuth2 Client Credentials Grant flow.
 *
 * @package LaravelShopifySdk\Commands
 */
class SetupStoreCommand extends Command
{
    protected $signature = 'shopify:setup
                            {--domain= : Shop domain (defaults to SHOPIFY_SHOP_DOMAIN env)}
                            {--token= : Access token (defaults to SHOPIFY_ACCESS_TOKEN env)}
                            {--custom-domain= : Custom domain (e.g., lumino.ge)}
                            {--currency= : Store currency (e.g., GEL, USD)}
                            {--oauth : Use OAuth2 Client Credentials Grant to obtain access token}';

    protected $description = 'Create or update a Shopify store from environment credentials';

    public function handle(): int
    {
        $domain = $this->option('domain') ?? config('shopify.single_store.shop_domain');
        $customDomain = $this->option('custom-domain');
        $currency = $this->option('currency');
        $useOAuth = $this->option('oauth');

        if (empty($domain)) {
            $this->error('Shop domain is required. Set SHOPIFY_SHOP_DOMAIN in .env or use --domain option.');
            return self::FAILURE;
        }

        // Normalize domain
        $domain = str_replace(['https://', 'http://'], '', $domain);
        $domain = rtrim($domain, '/');

        // Get access token - either from OAuth or direct
        if ($useOAuth) {
            $token = $this->obtainAccessTokenViaOAuth($domain);
            if (!$token) {
                return self::FAILURE;
            }
        } else {
            $token = $this->option('token') ?? config('shopify.single_store.access_token');
            if (empty($token)) {
                $this->error('Access token is required. Set SHOPIFY_ACCESS_TOKEN in .env, use --token option, or use --oauth flag.');
                return self::FAILURE;
            }
        }

        // Build store data
        $storeData = [
            'access_token' => $token,
            'is_active' => true,
        ];

        if ($customDomain) {
            $storeData['custom_domain'] = str_replace(['https://', 'http://'], '', rtrim($customDomain, '/'));
        }

        if ($currency) {
            $storeData['currency'] = strtoupper($currency);
        }

        $store = Store::updateOrCreate(
            ['shop_domain' => $domain],
            $storeData
        );

        if ($store->wasRecentlyCreated) {
            $this->info("✓ Created store: {$domain}");
        } else {
            $this->info("✓ Updated store: {$domain}");
        }

        if ($customDomain) {
            $this->info("  Custom domain: {$customDomain}");
        }
        if ($currency) {
            $this->info("  Currency: {$currency}");
        }

        $this->newLine();
        $this->info('You can now sync data:');
        $this->line('  php artisan shopify:sync:all');

        return self::SUCCESS;
    }

    /**
     * Obtain access token using OAuth2 Client Credentials Grant.
     *
     * @param string $shopDomain
     * @return string|null
     */
    protected function obtainAccessTokenViaOAuth(string $shopDomain): ?string
    {
        $clientId = config('shopify.oauth.client_id');
        $clientSecret = config('shopify.oauth.client_secret');

        if (empty($clientId) || empty($clientSecret)) {
            $this->error('OAuth credentials required. Set SHOPIFY_CLIENT_ID and SHOPIFY_CLIENT_SECRET in .env');
            return null;
        }

        $this->info('Obtaining access token via Client Credentials Grant...');

        $url = "https://{$shopDomain}/admin/oauth/access_token";

        try {
            $response = Http::post($url, [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'grant_type' => 'client_credentials',
            ]);

            if ($response->successful()) {
                $data = $response->json();
                $token = $data['access_token'] ?? null;

                if ($token) {
                    $this->info('✓ Access token obtained successfully');

                    if (isset($data['expires_in'])) {
                        $this->info("  Token expires in: {$data['expires_in']} seconds");
                    }
                    if (isset($data['scope'])) {
                        $this->info("  Scopes: {$data['scope']}");
                    }

                    return $token;
                }
            }

            $this->error('Failed to obtain access token: ' . $response->body());
            return null;

        } catch (\Exception $e) {
            $this->error('OAuth request failed: ' . $e->getMessage());
            return null;
        }
    }
}
