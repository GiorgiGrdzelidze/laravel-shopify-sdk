<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Sync;

use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Models\DraftOrder;
use LaravelShopifySdk\Models\Store;
use LaravelShopifySdk\Models\SyncRun;

class DraftOrderSyncer
{
    public function __construct(
        protected GraphQLClient $graphql
    ) {}

    public function sync(Store $store, array $options = []): SyncRun
    {
        $syncRun = SyncRun::create([
            'store_id' => $store->id,
            'entity' => 'draft_orders',
            'started_at' => now(),
        ]);

        try {
            $counts = $this->syncDraftOrders($store);

            $syncRun->update([
                'finished_at' => now(),
                'counts' => $counts,
                'duration_ms' => now()->diffInMilliseconds($syncRun->started_at),
            ]);
        } catch (\Exception $e) {
            $syncRun->update([
                'finished_at' => now(),
                'errors' => [$e->getMessage()],
                'duration_ms' => now()->diffInMilliseconds($syncRun->started_at),
            ]);
        }

        return $syncRun;
    }

    protected function syncDraftOrders(Store $store): array
    {
        $query = <<<GQL
        query getDraftOrders(\$first: Int!, \$after: String) {
            draftOrders(first: \$first, after: \$after) {
                edges {
                    node {
                        id
                        name
                        status
                        email
                        phone
                        note2
                        subtotalPrice
                        totalTax
                        totalPrice
                        currencyCode
                        taxExempt
                        taxesIncluded
                        invoiceUrl
                        invoiceSentAt
                        completedAt
                        order {
                            id
                        }
                        customer {
                            id
                        }
                        shippingAddress {
                            address1
                            address2
                            city
                            province
                            country
                            zip
                        }
                        billingAddress {
                            address1
                            address2
                            city
                            province
                            country
                            zip
                        }
                        lineItems(first: 50) {
                            edges {
                                node {
                                    id
                                    title
                                    quantity
                                    originalUnitPrice
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

        $counts = ['total' => 0, 'created' => 0, 'updated' => 0];
        $cursor = null;

        do {
            $response = $this->graphql->query($store, $query, [
                'first' => 50,
                'after' => $cursor,
            ]);

            $edges = $response['data']['draftOrders']['edges'] ?? [];
            $pageInfo = $response['data']['draftOrders']['pageInfo'] ?? [];

            foreach ($edges as $edge) {
                $this->upsertDraftOrder($store, $edge['node'], $counts);
            }

            $cursor = $pageInfo['endCursor'] ?? null;
        } while ($pageInfo['hasNextPage'] ?? false);

        return $counts;
    }

    protected function upsertDraftOrder(Store $store, array $data, array &$counts): void
    {
        $lineItems = array_map(function ($edge) {
            return [
                'id' => $edge['node']['id'],
                'title' => $edge['node']['title'],
                'quantity' => $edge['node']['quantity'],
                'price' => $edge['node']['originalUnitPrice'],
                'product_id' => $edge['node']['product']['id'] ?? null,
                'variant_id' => $edge['node']['variant']['id'] ?? null,
            ];
        }, $data['lineItems']['edges'] ?? []);

        $draftOrder = DraftOrder::updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_id' => $data['id'],
            ],
            [
                'name' => $data['name'],
                'status' => strtolower($data['status']),
                'email' => $data['email'],
                'phone' => $data['phone'],
                'note' => $data['note2'],
                'subtotal_price' => (float) $data['subtotalPrice'],
                'total_tax' => (float) $data['totalTax'],
                'total_price' => (float) $data['totalPrice'],
                'currency' => $data['currencyCode'],
                'tax_exempt' => $data['taxExempt'],
                'taxes_included' => $data['taxesIncluded'],
                'invoice_url' => $data['invoiceUrl'],
                'invoice_sent_at' => $data['invoiceSentAt'] ? \Carbon\Carbon::parse($data['invoiceSentAt']) : null,
                'completed_at' => $data['completedAt'] ? \Carbon\Carbon::parse($data['completedAt']) : null,
                'order_id' => $data['order']['id'] ?? null,
                'shipping_address' => $data['shippingAddress'],
                'billing_address' => $data['billingAddress'],
                'line_items' => $lineItems,
            ]
        );

        $counts['total']++;
        $counts[$draftOrder->wasRecentlyCreated ? 'created' : 'updated']++;
    }
}
