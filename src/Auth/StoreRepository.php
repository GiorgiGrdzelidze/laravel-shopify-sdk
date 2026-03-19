<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Auth;

use LaravelShopifySdk\Models\Store;
use Illuminate\Support\Collection;

/**
 * Store Repository
 *
 * Manages store data access and provides single-store mode fallback.
 * Handles encrypted token storage and multi-store/single-store mode switching.
 *
 * @package LaravelShopifySdk\Auth
 */
class StoreRepository
{
    /**
     * Find store by shop domain.
     *
     * @param string $shopDomain
     * @return Store|null
     */
    public function findByDomain(string $shopDomain): ?Store
    {
        if ($this->isSingleStoreMode()) {
            return $this->getSingleStoreInstance($shopDomain);
        }

        return Store::where('shop_domain', $shopDomain)->first();
    }

    /**
     * Create or update store.
     *
     * @param string $shopDomain
     * @param string $accessToken
     * @param string|null $scopes
     * @return Store
     */
    public function createOrUpdate(string $shopDomain, string $accessToken, ?string $scopes = null): Store
    {
        if ($this->isSingleStoreMode()) {
            return $this->getSingleStoreInstance($shopDomain);
        }

        return Store::updateOrCreate(
            ['shop_domain' => $shopDomain],
            [
                'access_token' => $accessToken,
                'scopes' => $scopes,
                'status' => 'active',
                'installed_at' => now(),
                'uninstalled_at' => null,
            ]
        );
    }

    /**
     * Get all active stores.
     *
     * @return Collection<int, Store>
     */
    public function getActiveStores(): Collection
    {
        if ($this->isSingleStoreMode()) {
            $store = $this->getSingleStoreInstance();
            return $store ? collect([$store]) : collect();
        }

        return Store::where('status', 'active')->get();
    }

    /**
     * Mark store as uninstalled.
     *
     * @param string $shopDomain
     * @return void
     */
    public function markAsUninstalled(string $shopDomain): void
    {
        if ($this->isSingleStoreMode()) {
            return;
        }

        $store = $this->findByDomain($shopDomain);
        if ($store) {
            $store->markAsInactive();
        }
    }

    /**
     * Check if running in single-store mode.
     *
     * @return bool
     */
    protected function isSingleStoreMode(): bool
    {
        return config('shopify.single_store.enabled', false);
    }

    /**
     * Get virtual store instance for single-store mode.
     *
     * @param string|null $shopDomain
     * @return Store|null
     */
    protected function getSingleStoreInstance(?string $shopDomain = null): ?Store
    {
        $configDomain = config('shopify.single_store.shop_domain');
        $accessToken = config('shopify.single_store.access_token');

        if (!$configDomain || !$accessToken) {
            return null;
        }

        $store = new Store();
        $store->id = 1;
        $store->shop_domain = $configDomain;
        $store->access_token = $accessToken;
        $store->status = 'active';
        $store->scopes = config('shopify.oauth.scopes');
        $store->installed_at = now();
        $store->exists = true;

        return $store;
    }
}
