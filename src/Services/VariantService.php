<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Services;

use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Exceptions\ShopifyApiException;
use LaravelShopifySdk\Models\ShopifyLog;
use LaravelShopifySdk\Models\Variant;
use Illuminate\Support\Facades\Log;

/**
 * Variant Service
 *
 * Handles variant CRUD operations with Shopify Admin API.
 *
 * @package LaravelShopifySdk\Services
 */
class VariantService
{
    public function __construct(
        protected GraphQLClient $graphqlClient
    ) {}

    /**
     * Update a variant on Shopify.
     *
     * @param Variant $variant
     * @param array $data
     * @return array
     * @throws ShopifyApiException
     */
    public function update(Variant $variant, array $data): array
    {
        $store = $variant->store;

        $mutation = <<<'GQL'
        mutation productVariantUpdate($input: ProductVariantInput!) {
            productVariantUpdate(input: $input) {
                productVariant {
                    id
                    title
                    sku
                    barcode
                    price
                    compareAtPrice
                    inventoryQuantity
                    updatedAt
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $input = [
            'id' => $variant->shopify_id,
        ];

        if (isset($data['price'])) {
            $input['price'] = (string) $data['price'];
        }
        if (isset($data['compareAtPrice'])) {
            $input['compareAtPrice'] = $data['compareAtPrice'] ? (string) $data['compareAtPrice'] : null;
        }
        if (isset($data['sku'])) {
            $input['sku'] = $data['sku'];
        }
        if (isset($data['barcode'])) {
            $input['barcode'] = $data['barcode'];
        }

        $response = $this->graphqlClient->query($store, $mutation, ['input' => $input]);

        if (!empty($response['data']['productVariantUpdate']['userErrors'])) {
            $errors = $response['data']['productVariantUpdate']['userErrors'];
            $errorMessages = array_map(fn($e) => $e['message'], $errors);
            throw new ShopifyApiException('Variant update failed: ' . implode(', ', $errorMessages));
        }

        $updatedVariant = $response['data']['productVariantUpdate']['productVariant'] ?? null;

        if ($updatedVariant) {
            $variant->update([
                'sku' => $updatedVariant['sku'],
                'barcode' => $updatedVariant['barcode'] ?? $variant->barcode,
                'price' => $updatedVariant['price'],
                'shopify_updated_at' => $updatedVariant['updatedAt'],
            ]);

            Log::info('Variant updated on Shopify', [
                'variant_id' => $variant->id,
                'shopify_id' => $variant->shopify_id,
            ]);
        }

        return $response;
    }

    /**
     * Fetch a single variant from Shopify and update local record.
     *
     * @param Variant $variant
     * @return Variant
     * @throws ShopifyApiException
     */
    public function fetch(Variant $variant): Variant
    {
        $store = $variant->product?->store ?? $variant->store;

        if (!$store) {
            throw new ShopifyApiException('Store not found for variant');
        }

        $query = <<<'GQL'
        query getVariant($id: ID!) {
            productVariant(id: $id) {
                id
                title
                displayName
                sku
                barcode
                price
                compareAtPrice
                updatedAt
                image {
                    id
                    url
                    altText
                }
                inventoryItem {
                    id
                    inventoryLevels(first: 10) {
                        edges {
                            node {
                                quantities(names: ["available"]) {
                                    quantity
                                }
                            }
                        }
                    }
                }
            }
        }
        GQL;

        $response = $this->graphqlClient->query($store, $query, ['id' => $variant->shopify_id]);

        $shopifyVariant = $response['data']['productVariant'] ?? null;

        if (!$shopifyVariant) {
            throw new ShopifyApiException('Variant not found on Shopify');
        }

        $variant->update([
            'sku' => $shopifyVariant['sku'],
            'barcode' => $shopifyVariant['barcode'],
            'price' => $shopifyVariant['price'],
            'inventory_item_id' => $shopifyVariant['inventoryItem']['id'] ?? $variant->inventory_item_id,
            'payload' => $shopifyVariant,
            'shopify_updated_at' => $shopifyVariant['updatedAt'],
        ]);

        return $variant->fresh();
    }

    /**
     * Update inventory quantity for a variant.
     *
     * @param Variant $variant
     * @param int $quantity
     * @param string|null $locationId
     * @return array
     * @throws ShopifyApiException
     */
    public function updateInventory(Variant $variant, int $quantity, ?string $locationId = null): array
    {
        $store = $variant->store;

        if (!$variant->inventory_item_id) {
            throw new ShopifyApiException('Variant has no inventory item ID');
        }

        // First, get the location if not provided
        if (!$locationId) {
            $locationQuery = <<<'GQL'
            query {
                locations(first: 1) {
                    edges {
                        node {
                            id
                        }
                    }
                }
            }
            GQL;

            $locationResponse = $this->graphqlClient->query($store, $locationQuery);
            $locationId = $locationResponse['data']['locations']['edges'][0]['node']['id'] ?? null;

            if (!$locationId) {
                throw new ShopifyApiException('No location found for inventory update');
            }
        }

        $mutation = <<<'GQL'
        mutation inventorySetQuantities($input: InventorySetQuantitiesInput!) {
            inventorySetQuantities(input: $input) {
                inventoryAdjustmentGroup {
                    createdAt
                    reason
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $response = $this->graphqlClient->query($store, $mutation, [
            'input' => [
                'name' => 'available',
                'reason' => 'correction',
                'ignoreCompareQuantity' => true,
                'quantities' => [
                    [
                        'inventoryItemId' => $variant->inventory_item_id,
                        'locationId' => $locationId,
                        'quantity' => $quantity,
                    ],
                ],
            ],
        ]);

        if (!empty($response['data']['inventorySetQuantities']['userErrors'])) {
            $errors = $response['data']['inventorySetQuantities']['userErrors'];
            $errorMessages = array_map(fn($e) => $e['message'], $errors);
            throw new ShopifyApiException('Inventory update failed: ' . implode(', ', $errorMessages));
        }

        Log::info('Variant inventory updated on Shopify', [
            'variant_id' => $variant->id,
            'shopify_id' => $variant->shopify_id,
            'quantity' => $quantity,
        ]);

        ShopifyLog::success(
            'inventory_update',
            'Variant',
            $variant->shopify_id,
            "Inventory updated to {$quantity}",
            ['location_id' => $locationId, 'quantity' => $quantity],
            $store->id
        );

        return $response;
    }

    /**
     * Get inventory levels for a variant across all locations.
     *
     * @param Variant $variant
     * @return array
     * @throws ShopifyApiException
     */
    public function getInventoryLevels(Variant $variant): array
    {
        $store = $variant->product?->store ?? $variant->store;

        if (!$store) {
            return [];
        }

        // If we don't have inventory_item_id, fetch it from Shopify using variant ID
        $inventoryItemId = $variant->inventory_item_id;

        if (!$inventoryItemId) {
            // Fetch variant with inventory item from Shopify
            $variantQuery = <<<'GQL'
            query getVariant($id: ID!) {
                productVariant(id: $id) {
                    id
                    inventoryItem {
                        id
                        inventoryLevels(first: 50) {
                            edges {
                                node {
                                    id
                                    quantities(names: ["available"]) {
                                        name
                                        quantity
                                    }
                                    location {
                                        id
                                        name
                                        isActive
                                    }
                                }
                            }
                        }
                    }
                }
            }
            GQL;

            $response = $this->graphqlClient->query($store, $variantQuery, [
                'id' => $variant->shopify_id,
            ]);

            $inventoryItem = $response['data']['productVariant']['inventoryItem'] ?? null;

            if (!$inventoryItem) {
                return [];
            }

            // Update the variant with the inventory_item_id for future use
            $variant->update(['inventory_item_id' => $inventoryItem['id']]);

            $levels = [];
            $edges = $inventoryItem['inventoryLevels']['edges'] ?? [];

            foreach ($edges as $edge) {
                $node = $edge['node'];
                $available = 0;
                foreach ($node['quantities'] ?? [] as $qty) {
                    if ($qty['name'] === 'available') {
                        $available = $qty['quantity'] ?? 0;
                        break;
                    }
                }
                $levels[] = [
                    'id' => $node['id'],
                    'available' => $available,
                    'location_id' => $node['location']['id'] ?? null,
                    'location_name' => $node['location']['name'] ?? 'Unknown',
                    'is_active' => $node['location']['isActive'] ?? false,
                ];
            }

            return $levels;
        }

        // Use inventory item ID directly
        $query = <<<'GQL'
        query getInventoryLevels($inventoryItemId: ID!) {
            inventoryItem(id: $inventoryItemId) {
                id
                inventoryLevels(first: 50) {
                    edges {
                        node {
                            id
                            quantities(names: ["available"]) {
                                name
                                quantity
                            }
                            location {
                                id
                                name
                                isActive
                            }
                        }
                    }
                }
            }
        }
        GQL;

        $response = $this->graphqlClient->query($store, $query, [
            'inventoryItemId' => $inventoryItemId,
        ]);

        $levels = [];
        $edges = $response['data']['inventoryItem']['inventoryLevels']['edges'] ?? [];

        foreach ($edges as $edge) {
            $node = $edge['node'];
            $available = 0;
            foreach ($node['quantities'] ?? [] as $qty) {
                if ($qty['name'] === 'available') {
                    $available = $qty['quantity'] ?? 0;
                    break;
                }
            }
            $levels[] = [
                'id' => $node['id'],
                'available' => $available,
                'location_id' => $node['location']['id'] ?? null,
                'location_name' => $node['location']['name'] ?? 'Unknown',
                'is_active' => $node['location']['isActive'] ?? false,
            ];
        }

        return $levels;
    }

    /**
     * Get all locations for a store with full details.
     *
     * @param \LaravelShopifySdk\Models\Store $store
     * @param bool $syncToDatabase Whether to sync locations to local database
     * @return array
     * @throws ShopifyApiException
     */
    public function getLocations(\LaravelShopifySdk\Models\Store $store, bool $syncToDatabase = false): array
    {
        $query = <<<'GQL'
        query {
            locations(first: 50) {
                edges {
                    node {
                        id
                        name
                        isActive
                        fulfillsOnlineOrders
                        hasActiveInventory
                        hasUnfulfilledOrders
                        shipsInventory
                        address {
                            address1
                            address2
                            city
                            province
                            provinceCode
                            country
                            countryCode
                            zip
                            phone
                        }
                        createdAt
                        updatedAt
                    }
                }
            }
        }
        GQL;

        $response = $this->graphqlClient->query($store, $query);

        $locations = [];
        $edges = $response['data']['locations']['edges'] ?? [];

        foreach ($edges as $edge) {
            $node = $edge['node'];
            $address = $node['address'] ?? [];

            $location = [
                'id' => $node['id'],
                'name' => $node['name'] ?? 'Unknown',
                'is_active' => $node['isActive'] ?? false,
                'fulfills_online_orders' => $node['fulfillsOnlineOrders'] ?? false,
                'has_active_inventory' => $node['hasActiveInventory'] ?? false,
                'has_unfulfilled_orders' => $node['hasUnfulfilledOrders'] ?? false,
                'ships_inventory' => $node['shipsInventory'] ?? false,
                'address' => [
                    'address1' => $address['address1'] ?? null,
                    'address2' => $address['address2'] ?? null,
                    'city' => $address['city'] ?? null,
                    'province' => $address['province'] ?? null,
                    'province_code' => $address['provinceCode'] ?? null,
                    'country' => $address['country'] ?? null,
                    'country_code' => $address['countryCode'] ?? null,
                    'zip' => $address['zip'] ?? null,
                    'phone' => $address['phone'] ?? null,
                ],
                'created_at' => $node['createdAt'] ?? null,
                'updated_at' => $node['updatedAt'] ?? null,
            ];

            $locations[] = $location;

            // Sync to local database if requested
            if ($syncToDatabase) {
                \LaravelShopifySdk\Models\Location::updateOrCreate(
                    [
                        'store_id' => $store->id,
                        'shopify_id' => $node['id'],
                    ],
                    [
                        'name' => $node['name'] ?? null,
                        'is_active' => $node['isActive'] ?? false,
                        'payload' => $node,
                    ]
                );
            }
        }

        return $locations;
    }

    /**
     * Sync all locations from Shopify to local database.
     *
     * @param \LaravelShopifySdk\Models\Store $store
     * @return int Number of locations synced
     * @throws ShopifyApiException
     */
    public function syncLocations(\LaravelShopifySdk\Models\Store $store): int
    {
        $locations = $this->getLocations($store, true);

        Log::info('Locations synced from Shopify', [
            'store_id' => $store->id,
            'count' => count($locations),
        ]);

        return count($locations);
    }
}
