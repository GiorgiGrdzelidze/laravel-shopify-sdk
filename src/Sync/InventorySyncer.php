<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Sync;

use LaravelShopifySdk\Clients\ShopifyClient;
use LaravelShopifySdk\Models\InventoryLevel;
use LaravelShopifySdk\Models\Location;
use LaravelShopifySdk\Models\Store;

/**
 * Inventory Syncer
 *
 * Syncs locations and inventory levels from Shopify using GraphQL cursor pagination.
 * Handles both location data and inventory level quantities.
 *
 * @package LaravelShopifySdk\Sync
 */
class InventorySyncer implements EntitySyncerInterface
{
    public function __construct(
        protected ShopifyClient $client
    ) {}

    public function sync(Store $store, array $options = []): array
    {
        $locationCount = 0;
        $inventoryCount = 0;

        $locationQuery = <<<GQL
        query(\$cursor: String) {
          locations(first: 50, after: \$cursor) {
            edges {
              node {
                id
                name
                isActive
              }
            }
            pageInfo {
              hasNextPage
              endCursor
            }
          }
        }
        GQL;

        $this->client->graphql($store)->paginate(
            $store,
            $locationQuery,
            ['cursor' => null],
            function ($response) use ($store, &$locationCount) {
                $locations = $response['data']['locations']['edges'] ?? [];

                foreach ($locations as $edge) {
                    $node = $edge['node'];

                    Location::updateOrCreate(
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

                    $locationCount++;
                }
            }
        );

        $since = $options['since'] ?? null;
        $inventoryQuery = $this->buildInventoryQuery($since);

        $this->client->graphql($store)->paginate(
            $store,
            $inventoryQuery,
            ['cursor' => null],
            function ($response) use ($store, &$inventoryCount) {
                $items = $response['data']['inventoryItems']['edges'] ?? [];

                foreach ($items as $edge) {
                    $node = $edge['node'];

                    if (isset($node['inventoryLevels']['edges'])) {
                        foreach ($node['inventoryLevels']['edges'] as $levelEdge) {
                            $levelNode = $levelEdge['node'];

                            // Extract available quantity from quantities array (2026+ API)
                            $available = null;
                            $onHand = null;
                            if (isset($levelNode['quantities'])) {
                                foreach ($levelNode['quantities'] as $qty) {
                                    if ($qty['name'] === 'available') {
                                        $available = $qty['quantity'];
                                    }
                                    if ($qty['name'] === 'on_hand') {
                                        $onHand = $qty['quantity'];
                                    }
                                }
                            }

                            InventoryLevel::updateOrCreate(
                                [
                                    'store_id' => $store->id,
                                    'inventory_item_id' => $node['id'],
                                    'location_id' => $levelNode['location']['id'] ?? null,
                                ],
                                [
                                    'available' => $available,
                                    'on_hand' => $onHand,
                                    'payload' => $levelNode,
                                    'shopify_updated_at' => $levelNode['updatedAt'] ?? null,
                                ]
                            );

                            $inventoryCount++;
                        }
                    }
                }
            }
        );

        return [
            'locations' => $locationCount,
            'inventory_levels' => $inventoryCount,
        ];
    }

    protected function buildInventoryQuery(?string $since): string
    {
        $updatedAtFilter = $since ? ", query: \"updated_at:>'{$since}'\"" : '';

        return <<<GQL
        query(\$cursor: String) {
          inventoryItems(first: 50, after: \$cursor{$updatedAtFilter}) {
            edges {
              node {
                id
                inventoryLevels(first: 10) {
                  edges {
                    node {
                      id
                      updatedAt
                      quantities(names: ["available", "on_hand"]) {
                        name
                        quantity
                      }
                      location {
                        id
                        name
                      }
                    }
                  }
                }
              }
            }
            pageInfo {
              hasNextPage
              endCursor
            }
          }
        }
        GQL;
    }
}
