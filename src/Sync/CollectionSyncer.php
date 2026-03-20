<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Sync;

use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Models\Collection;
use LaravelShopifySdk\Models\Product;
use LaravelShopifySdk\Models\Store;
use LaravelShopifySdk\Models\SyncRun;

class CollectionSyncer
{
    public function __construct(
        protected GraphQLClient $graphql
    ) {}

    public function sync(Store $store, array $options = []): SyncRun
    {
        $syncRun = SyncRun::create([
            'store_id' => $store->id,
            'entity' => 'collections',
            'status' => 'running',
            'started_at' => now(),
        ]);

        $startTime = microtime(true);
        $counts = ['created' => 0, 'updated' => 0, 'total' => 0];
        $errors = [];

        try {
            $cursor = null;
            $hasNextPage = true;

            while ($hasNextPage) {
                $query = $this->buildQuery();
                $variables = ['cursor' => $cursor];
                $response = $this->graphql->query($store, $query, $variables);

                $collections = $response['data']['collections']['edges'] ?? [];
                $pageInfo = $response['data']['collections']['pageInfo'] ?? [];

                foreach ($collections as $edge) {
                    $node = $edge['node'];
                    $result = $this->upsertCollection($store, $node);
                    $counts[$result]++;
                    $counts['total']++;

                    // Sync products in collection
                    $this->syncCollectionProducts($store, $node);
                }

                $hasNextPage = $pageInfo['hasNextPage'] ?? false;
                $cursor = $pageInfo['endCursor'] ?? null;
            }

            $syncRun->update([
                'status' => 'completed',
                'finished_at' => now(),
                'duration_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'counts' => $counts,
            ]);
        } catch (\Exception $e) {
            $errors[] = $e->getMessage();
            $syncRun->update([
                'status' => 'failed',
                'finished_at' => now(),
                'duration_ms' => (int) ((microtime(true) - $startTime) * 1000),
                'counts' => $counts,
                'errors' => $errors,
            ]);
        }

        return $syncRun;
    }

    protected function buildQuery(): string
    {
        return <<<GQL
        query(\$cursor: String) {
            collections(first: 50, after: \$cursor) {
                edges {
                    node {
                        id
                        title
                        handle
                        descriptionHtml
                        updatedAt
                        image {
                            url
                        }
                        sortOrder
                        productsCount {
                            count
                        }
                        ruleSet {
                            appliedDisjunctively
                            rules {
                                column
                                relation
                                condition
                            }
                        }
                        products(first: 100) {
                            edges {
                                node {
                                    id
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

    protected function upsertCollection(Store $store, array $node): string
    {
        $existing = Collection::where('store_id', $store->id)
            ->where('shopify_id', $node['id'])
            ->first();

        // Determine collection type: if ruleSet exists with rules, it's a smart collection
        $ruleSet = $node['ruleSet'] ?? null;
        $isSmartCollection = $ruleSet && !empty($ruleSet['rules']);
        $collectionType = $isSmartCollection ? 'smart' : 'custom';

        $data = [
            'store_id' => $store->id,
            'shopify_id' => $node['id'],
            'title' => $node['title'],
            'handle' => $node['handle'] ?? null,
            'description' => strip_tags($node['descriptionHtml'] ?? ''),
            'description_html' => $node['descriptionHtml'] ?? null,
            'image_url' => $node['image']['url'] ?? null,
            'collection_type' => $collectionType,
            'rules' => $ruleSet['rules'] ?? null,
            'sort_order' => $node['sortOrder'] ?? null,
            'products_count' => $node['productsCount']['count'] ?? 0,
            'published_at' => isset($node['updatedAt']) ? \Carbon\Carbon::parse($node['updatedAt']) : null,
            'payload' => $node,
        ];

        if ($existing) {
            $existing->update($data);
            return 'updated';
        }

        Collection::create($data);
        return 'created';
    }

    protected function syncCollectionProducts(Store $store, array $node): void
    {
        $collection = Collection::where('store_id', $store->id)
            ->where('shopify_id', $node['id'])
            ->first();

        if (!$collection) {
            return;
        }

        $productIds = [];
        $position = 0;

        foreach ($node['products']['edges'] ?? [] as $edge) {
            $shopifyProductId = $edge['node']['id'];
            $product = Product::where('store_id', $store->id)
                ->where('shopify_id', $shopifyProductId)
                ->first();

            if ($product) {
                $productIds[$product->id] = ['position' => $position++];
            }
        }

        $collection->products()->sync($productIds);
    }
}
