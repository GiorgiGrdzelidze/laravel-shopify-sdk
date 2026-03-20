<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Sync;

use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Models\Discount;
use LaravelShopifySdk\Models\DiscountCode;
use LaravelShopifySdk\Models\Store;
use LaravelShopifySdk\Models\SyncRun;

class DiscountSyncer
{
    public function __construct(
        protected GraphQLClient $graphql
    ) {}

    public function sync(Store $store, array $options = []): SyncRun
    {
        $syncRun = SyncRun::create([
            'store_id' => $store->id,
            'entity' => 'discounts',
            'started_at' => now(),
        ]);

        try {
            $counts = $this->syncDiscounts($store);

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

    protected function syncDiscounts(Store $store): array
    {
        $counts = ['total' => 0, 'created' => 0, 'updated' => 0, 'codes' => 0];
        $cursor = null;

        do {
            $afterClause = $cursor ? ', after: "' . $cursor . '"' : '';
            $query = <<<GQL
            {
                discountNodes(first: 50{$afterClause}) {
                    edges {
                        node {
                            id
                            discount {
                                ... on DiscountCodeBasic {
                                    title
                                    status
                                    startsAt
                                    endsAt
                                    usageLimit
                                    appliesOncePerCustomer
                                    codes(first: 10) {
                                        edges {
                                            node {
                                                id
                                                code
                                            }
                                        }
                                    }
                                    customerGets {
                                        value {
                                            ... on DiscountPercentage {
                                                percentage
                                            }
                                            ... on DiscountAmount {
                                                amount {
                                                    amount
                                                }
                                            }
                                        }
                                    }
                                }
                                ... on DiscountCodeFreeShipping {
                                    title
                                    status
                                    startsAt
                                    endsAt
                                    codes(first: 10) {
                                        edges {
                                            node {
                                                id
                                                code
                                            }
                                        }
                                    }
                                }
                                ... on DiscountAutomaticBasic {
                                    title
                                    status
                                    startsAt
                                    endsAt
                                    customerGets {
                                        value {
                                            ... on DiscountPercentage {
                                                percentage
                                            }
                                            ... on DiscountAmount {
                                                amount {
                                                    amount
                                                }
                                            }
                                        }
                                    }
                                }
                                ... on DiscountAutomaticFreeShipping {
                                    title
                                    status
                                    startsAt
                                    endsAt
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

            $response = $this->graphql->query($store, $query);

            $edges = $response['data']['discountNodes']['edges'] ?? [];
            $pageInfo = $response['data']['discountNodes']['pageInfo'] ?? [];

            foreach ($edges as $edge) {
                $node = $edge['node'];
                $discount = $node['discount'] ?? null;

                if (!$discount || empty($discount['title'])) {
                    continue;
                }

                $this->upsertDiscount($store, $node['id'], $discount, $counts);
            }

            $cursor = $pageInfo['endCursor'] ?? null;
        } while ($pageInfo['hasNextPage'] ?? false);

        return $counts;
    }

    protected function upsertDiscount(Store $store, string $shopifyId, array $data, array &$counts): void
    {
        $valueType = 'percentage';
        $value = 0;

        $customerGets = $data['customerGets']['value'] ?? [];
        if (isset($customerGets['percentage'])) {
            $valueType = 'percentage';
            $value = $customerGets['percentage'] * 100;
        } elseif (isset($customerGets['amount']['amount'])) {
            $valueType = 'fixed_amount';
            $value = (float) $customerGets['amount']['amount'];
        }

        $discount = Discount::updateOrCreate(
            [
                'store_id' => $store->id,
                'shopify_id' => $shopifyId,
            ],
            [
                'title' => $data['title'] ?? 'Untitled',
                'target_type' => 'line_item',
                'target_selection' => 'all',
                'allocation_method' => 'across',
                'value_type' => $valueType,
                'value' => $value,
                'once_per_customer' => $data['appliesOncePerCustomer'] ?? false,
                'usage_limit' => $data['usageLimit'] ?? null,
                'customer_selection' => 'all',
                'starts_at' => isset($data['startsAt']) ? \Carbon\Carbon::parse($data['startsAt']) : null,
                'ends_at' => isset($data['endsAt']) ? \Carbon\Carbon::parse($data['endsAt']) : null,
            ]
        );

        $counts['total']++;
        $counts[$discount->wasRecentlyCreated ? 'created' : 'updated']++;

        // Sync discount codes
        $codes = $data['codes']['edges'] ?? [];
        foreach ($codes as $codeEdge) {
            $codeData = $codeEdge['node'];
            DiscountCode::updateOrCreate(
                [
                    'store_id' => $store->id,
                    'shopify_id' => $codeData['id'],
                ],
                [
                    'price_rule_id' => $discount->id,
                    'code' => $codeData['code'],
                    'usage_count' => $codeData['usageCount'] ?? 0,
                ]
            );
            $counts['codes']++;
        }
    }
}
