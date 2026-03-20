<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Sync;

use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Models\Metafield;
use LaravelShopifySdk\Models\Store;
use LaravelShopifySdk\Models\SyncRun;

class MetafieldSyncer
{
    public function __construct(
        protected GraphQLClient $graphql
    ) {}

    public function sync(Store $store, array $options = []): SyncRun
    {
        $syncRun = SyncRun::create([
            'store_id' => $store->id,
            'entity' => 'metafields',
            'started_at' => now(),
        ]);

        try {
            $counts = $this->syncMetafields($store, $options);

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

    protected function syncMetafields(Store $store, array $options): array
    {
        $counts = ['total' => 0, 'created' => 0, 'updated' => 0];

        // Sync product metafields
        $this->syncProductMetafields($store, $counts);

        return $counts;
    }

    protected function syncProductMetafields(Store $store, array &$counts): void
    {
        $query = <<<GQL
        query getProducts(\$first: Int!, \$after: String) {
            products(first: \$first, after: \$after) {
                edges {
                    node {
                        id
                        metafields(first: 50) {
                            edges {
                                node {
                                    id
                                    namespace
                                    key
                                    value
                                    type
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

        $cursor = null;

        do {
            $response = $this->graphql->query($store, $query, [
                'first' => 50,
                'after' => $cursor,
            ]);

            $edges = $response['data']['products']['edges'] ?? [];
            $pageInfo = $response['data']['products']['pageInfo'] ?? [];

            foreach ($edges as $edge) {
                $product = $edge['node'];
                $metafields = $product['metafields']['edges'] ?? [];

                foreach ($metafields as $mfEdge) {
                    $mf = $mfEdge['node'];
                    $this->upsertMetafield($store, 'PRODUCT', $product['id'], $mf, $counts);
                }
            }

            $cursor = $pageInfo['endCursor'] ?? null;
        } while ($pageInfo['hasNextPage'] ?? false);
    }

    protected function upsertMetafield(Store $store, string $ownerType, string $ownerId, array $data, array &$counts): void
    {
        $metafield = Metafield::updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_id' => $data['id'],
            ],
            [
                'namespace' => $data['namespace'],
                'key' => $data['key'],
                'value' => $data['value'],
                'type' => $data['type'],
                'owner_type' => $ownerType,
                'owner_id' => $ownerId,
            ]
        );

        $counts['total']++;
        $counts[$metafield->wasRecentlyCreated ? 'created' : 'updated']++;
    }
}
