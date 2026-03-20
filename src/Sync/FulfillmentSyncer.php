<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Sync;

use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Models\Orders\Fulfillment;
use LaravelShopifySdk\Models\Orders\FulfillmentOrder;
use LaravelShopifySdk\Models\Core\Store;
use LaravelShopifySdk\Models\Sync\SyncRun;

class FulfillmentSyncer
{
    public function __construct(
        protected GraphQLClient $graphql
    ) {}

    public function sync(Store $store, array $options = []): SyncRun
    {
        $syncRun = SyncRun::create([
            'store_id' => $store->id,
            'entity' => 'fulfillments',
            'started_at' => now(),
        ]);

        try {
            $counts = $this->syncFulfillments($store);

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

    protected function syncFulfillments(Store $store): array
    {
        $query = <<<GQL
        query getOrders(\$first: Int!, \$after: String) {
            orders(first: \$first, after: \$after, query: "fulfillment_status:shipped OR fulfillment_status:partial") {
                edges {
                    node {
                        id
                        name
                        fulfillments {
                            id
                            name
                            status
                            trackingInfo {
                                company
                                number
                                url
                            }
                            createdAt
                            updatedAt
                            location {
                                id
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

            $edges = $response['data']['orders']['edges'] ?? [];
            $pageInfo = $response['data']['orders']['pageInfo'] ?? [];

            foreach ($edges as $edge) {
                $order = $edge['node'];
                foreach ($order['fulfillments'] ?? [] as $fulfillment) {
                    $this->upsertFulfillment($store, $order['id'], $fulfillment, $counts);
                }
            }

            $cursor = $pageInfo['endCursor'] ?? null;
        } while ($pageInfo['hasNextPage'] ?? false);

        return $counts;
    }

    protected function upsertFulfillment(Store $store, string $orderShopifyId, array $data, array &$counts): void
    {
        $trackingInfo = $data['trackingInfo'] ?? [];
        $trackingNumbers = array_column($trackingInfo, 'number');
        $trackingUrls = array_column($trackingInfo, 'url');

        $fulfillment = Fulfillment::updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_id' => $data['id'],
            ],
            [
                'order_shopify_id' => $orderShopifyId,
                'name' => $data['name'],
                'status' => strtolower($data['status']),
                'tracking_company' => $trackingInfo[0]['company'] ?? null,
                'tracking_number' => $trackingNumbers[0] ?? null,
                'tracking_numbers' => $trackingNumbers,
                'tracking_urls' => $trackingUrls,
                'created_at_shopify' => isset($data['createdAt']) ? \Carbon\Carbon::parse($data['createdAt']) : null,
                'updated_at_shopify' => isset($data['updatedAt']) ? \Carbon\Carbon::parse($data['updatedAt']) : null,
            ]
        );

        $counts['total']++;
        $counts[$fulfillment->wasRecentlyCreated ? 'created' : 'updated']++;
    }
}
