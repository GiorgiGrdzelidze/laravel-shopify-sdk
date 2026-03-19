<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Clients;

use LaravelShopifySdk\Auth\StoreRepository;
use LaravelShopifySdk\Exceptions\ShopifyApiException;
use LaravelShopifySdk\Models\Store;

/**
 * Shopify Client
 *
 * Main client for interacting with Shopify Admin API.
 * Provides unified access to GraphQL and REST APIs with automatic store resolution.
 *
 * @package LaravelShopifySdk\Clients
 */
class ShopifyClient
{
    public function __construct(
        protected GraphQLClient $graphql,
        protected RestClient $rest,
        protected StoreRepository $storeRepository
    ) {}

    /**
     * Get GraphQL client for a store.
     *
     * @param string|Store $store
     * @return GraphQLClient
     * @throws ShopifyApiException
     */
    public function graphql(string|Store $store): GraphQLClient
    {
        $this->ensureStore($store);
        return $this->graphql;
    }

    /**
     * Get REST client for a store.
     *
     * @param string|Store $store
     * @return RestClient
     * @throws ShopifyApiException
     */
    public function rest(string|Store $store): RestClient
    {
        $this->ensureStore($store);
        return $this->rest;
    }

    /**
     * Get store instance.
     *
     * @param string|Store $store
     * @return Store
     * @throws ShopifyApiException
     */
    public function getStore(string|Store $store): Store
    {
        if ($store instanceof Store) {
            return $store;
        }

        $storeInstance = $this->storeRepository->findByDomain($store);

        if (!$storeInstance) {
            throw new ShopifyApiException("Store not found: {$store}");
        }

        return $storeInstance;
    }

    /**
     * Ensure store exists and is valid.
     *
     * @param string|Store $store
     * @return void
     * @throws ShopifyApiException
     */
    protected function ensureStore(string|Store $store): void
    {
        $this->getStore($store);
    }
}
