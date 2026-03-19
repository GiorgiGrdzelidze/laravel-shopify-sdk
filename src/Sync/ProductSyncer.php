<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Sync;

use LaravelShopifySdk\Clients\ShopifyClient;
use LaravelShopifySdk\Models\Product;
use LaravelShopifySdk\Models\ShopifyLog;
use LaravelShopifySdk\Models\Store;
use LaravelShopifySdk\Models\Variant;

/**
 * Product Syncer
 *
 * Syncs products and variants from Shopify using GraphQL cursor pagination.
 * Handles incremental updates and full payload storage.
 *
 * @package LaravelShopifySdk\Sync
 */
class ProductSyncer implements EntitySyncerInterface
{
    public function __construct(
        protected ShopifyClient $client
    ) {}

    public function sync(Store $store, array $options = []): array
    {
        $since = $options['since'] ?? null;
        $productCount = 0;
        $variantCount = 0;

        $query = $this->buildQuery($since);

        $this->client->graphql($store)->paginate(
            $store,
            $query,
            ['cursor' => null],
            function ($response) use ($store, &$productCount, &$variantCount) {
                $products = $response['data']['products']['edges'] ?? [];

                foreach ($products as $edge) {
                    $node = $edge['node'];

                    $product = Product::updateOrCreate(
                        [
                            'store_id' => $store->id,
                            'shopify_id' => $node['id'],
                        ],
                        [
                            'title' => $node['title'] ?? null,
                            'handle' => $node['handle'] ?? null,
                            'status' => $node['status'] ?? null,
                            'vendor' => $node['vendor'] ?? null,
                            'product_type' => $node['productType'] ?? null,
                            'payload' => $node,
                            'shopify_updated_at' => $node['updatedAt'] ?? null,
                        ]
                    );

                    $productCount++;

                    if (isset($node['variants']['edges'])) {
                        foreach ($node['variants']['edges'] as $variantEdge) {
                            $variantNode = $variantEdge['node'];

                            Variant::updateOrCreate(
                                [
                                    'store_id' => $store->id,
                                    'shopify_id' => $variantNode['id'],
                                ],
                                [
                                    'product_id' => $product->id,
                                    'sku' => $variantNode['sku'] ?? null,
                                    'barcode' => $variantNode['barcode'] ?? null,
                                    'price' => $variantNode['price'] ?? null,
                                    'inventory_item_id' => $variantNode['inventoryItem']['id'] ?? null,
                                    'payload' => $variantNode,
                                    'shopify_updated_at' => $variantNode['updatedAt'] ?? null,
                                ]
                            );

                            $variantCount++;
                        }
                    }
                }
            }
        );

        ShopifyLog::success(
            'sync',
            'Product',
            null,
            "Synced {$productCount} products and {$variantCount} variants",
            ['products' => $productCount, 'variants' => $variantCount],
            $store->id
        );

        return [
            'products' => $productCount,
            'variants' => $variantCount,
        ];
    }

    protected function buildQuery(?string $since): string
    {
        $updatedAtFilter = $since ? ", query: \"updated_at:>'{$since}'\"" : '';

        return <<<GQL
        query(\$cursor: String) {
          products(first: 25, after: \$cursor{$updatedAtFilter}) {
            edges {
              node {
                id
                title
                handle
                status
                vendor
                productType
                description
                tags
                createdAt
                updatedAt
                featuredImage {
                  id
                  url
                  altText
                }
                variants(first: 50) {
                  edges {
                    node {
                      id
                      title
                      sku
                      barcode
                      price
                      compareAtPrice
                      createdAt
                      updatedAt
                      inventoryItem {
                        id
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
