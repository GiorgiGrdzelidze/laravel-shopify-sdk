<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Sync;

use LaravelShopifySdk\Clients\ShopifyClient;
use LaravelShopifySdk\Models\Order;
use LaravelShopifySdk\Models\OrderLine;
use LaravelShopifySdk\Models\ShopifyLog;
use LaravelShopifySdk\Models\Store;

/**
 * Order Syncer
 *
 * Syncs orders and line items from Shopify using GraphQL cursor pagination.
 * Supports date range filtering and incremental updates.
 *
 * @package LaravelShopifySdk\Sync
 */
class OrderSyncer implements EntitySyncerInterface
{
    public function __construct(
        protected ShopifyClient $client
    ) {}

    public function sync(Store $store, array $options = []): array
    {
        $since = $options['since'] ?? null;
        $dateFrom = $options['date_from'] ?? null;
        $dateTo = $options['date_to'] ?? null;
        $orderCount = 0;
        $lineCount = 0;

        $query = $this->buildQuery($since, $dateFrom, $dateTo);

        $this->client->graphql($store)->paginate(
            $store,
            $query,
            ['cursor' => null],
            function ($response) use ($store, &$orderCount, &$lineCount) {
                $orders = $response['data']['orders']['edges'] ?? [];

                foreach ($orders as $edge) {
                    $node = $edge['node'];

                    $order = Order::updateOrCreate(
                        [
                            'store_id' => $store->id,
                            'shopify_id' => $node['id'],
                        ],
                        [
                            'name' => $node['name'] ?? null,
                            'order_number' => $node['name'] ?? null, // Use name field (e.g., "#1001")
                            'email' => $node['email'] ?? null,
                            'financial_status' => $node['displayFinancialStatus'] ?? null,
                            'fulfillment_status' => $node['displayFulfillmentStatus'] ?? null,
                            'total_price' => $node['totalPriceSet']['shopMoney']['amount'] ?? null,
                            'currency' => $node['currencyCode'] ?? null,
                            'payload' => $node,
                            'processed_at' => $node['processedAt'] ?? null,
                            'shopify_updated_at' => $node['updatedAt'] ?? null,
                        ]
                    );

                    $orderCount++;

                    if (isset($node['lineItems']['edges'])) {
                        foreach ($node['lineItems']['edges'] as $lineEdge) {
                            $lineNode = $lineEdge['node'];

                            // Find local product and variant by Shopify IDs or SKU
                            $localProductId = null;
                            $localVariantId = null;
                            $variant = null;

                            // Try matching by Shopify variant ID first
                            if (isset($lineNode['variant']['id'])) {
                                $variant = \LaravelShopifySdk\Models\Variant::where('store_id', $store->id)
                                    ->where('shopify_id', $lineNode['variant']['id'])
                                    ->first();
                            }

                            // If no match by Shopify ID, try matching by SKU
                            if (!$variant && !empty($lineNode['sku'])) {
                                $variant = \LaravelShopifySdk\Models\Variant::where('store_id', $store->id)
                                    ->where('sku', $lineNode['sku'])
                                    ->first();
                            }

                            // Set variant and product IDs if found
                            if ($variant) {
                                $localVariantId = $variant->id;
                                $localProductId = $variant->product_id;
                            }

                            // Fallback: try matching product by Shopify ID if variant not found
                            if (!$localProductId && isset($lineNode['product']['id'])) {
                                $product = \LaravelShopifySdk\Models\Product::where('store_id', $store->id)
                                    ->where('shopify_id', $lineNode['product']['id'])
                                    ->first();
                                $localProductId = $product?->id;
                            }

                            OrderLine::updateOrCreate(
                                [
                                    'store_id' => $store->id,
                                    'shopify_id' => $lineNode['id'],
                                ],
                                [
                                    'order_id' => $order->id,
                                    'product_id' => $localProductId,
                                    'variant_id' => $localVariantId,
                                    'title' => $lineNode['title'] ?? null,
                                    'quantity' => $lineNode['quantity'] ?? null,
                                    'price' => $lineNode['originalUnitPriceSet']['shopMoney']['amount'] ?? null,
                                    'payload' => $lineNode,
                                ]
                            );

                            $lineCount++;
                        }
                    }
                }
            }
        );

        ShopifyLog::success(
            'sync',
            'Order',
            null,
            "Synced {$orderCount} orders and {$lineCount} line items",
            ['orders' => $orderCount, 'line_items' => $lineCount],
            $store->id
        );

        return [
            'orders' => $orderCount,
            'line_items' => $lineCount,
        ];
    }

    protected function buildQuery(?string $since, ?string $dateFrom, ?string $dateTo): string
    {
        $filters = [];

        // Always include status:any to fetch all orders (open, closed, cancelled, archived)
        $filters[] = "status:any";

        if ($since) {
            $filters[] = "updated_at:>'{$since}'";
        }

        if ($dateFrom) {
            $filters[] = "created_at:>='{$dateFrom}'";
        }

        if ($dateTo) {
            $filters[] = "created_at:<='{$dateTo}'";
        }

        $queryFilter = ', query: "' . implode(' AND ', $filters) . '"';

        return <<<GQL
        query(\$cursor: String) {
          orders(first: 50, after: \$cursor{$queryFilter}) {
            edges {
              node {
                id
                name
                email
                displayFinancialStatus
                displayFulfillmentStatus
                currencyCode
                processedAt
                updatedAt
                totalPriceSet {
                  shopMoney {
                    amount
                  }
                }
                lineItems(first: 100) {
                  edges {
                    node {
                      id
                      title
                      quantity
                      sku
                      originalUnitPriceSet {
                        shopMoney {
                          amount
                        }
                      }
                      product {
                        id
                      }
                      variant {
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
