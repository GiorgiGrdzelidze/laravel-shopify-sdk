<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Sync;

use LaravelShopifySdk\Clients\ShopifyClient;
use LaravelShopifySdk\Models\Customer;
use LaravelShopifySdk\Models\ShopifyLog;
use LaravelShopifySdk\Models\Store;

/**
 * Customer Syncer
 *
 * Syncs customers from Shopify using GraphQL cursor pagination.
 * Handles incremental updates based on updated_at timestamps.
 *
 * @package LaravelShopifySdk\Sync
 */
class CustomerSyncer implements EntitySyncerInterface
{
    public function __construct(
        protected ShopifyClient $client
    ) {}

    public function sync(Store $store, array $options = []): array
    {
        $since = $options['since'] ?? null;
        $count = 0;

        $query = $this->buildQuery($since);

        $this->client->graphql($store)->paginate(
            $store,
            $query,
            ['cursor' => null],
            function ($response) use ($store, &$count) {
                $customers = $response['data']['customers']['edges'] ?? [];

                foreach ($customers as $edge) {
                    $node = $edge['node'];

                    Customer::updateOrCreate(
                        [
                            'store_id' => $store->id,
                            'shopify_id' => $node['id'],
                        ],
                        [
                            'email' => $node['email'] ?? null,
                            'first_name' => $node['firstName'] ?? null,
                            'last_name' => $node['lastName'] ?? null,
                            'state' => $node['state'] ?? null,
                            'payload' => $node,
                            'shopify_created_at' => $node['createdAt'] ?? null,
                            'shopify_updated_at' => $node['updatedAt'] ?? null,
                        ]
                    );

                    $count++;
                }
            }
        );

        ShopifyLog::success(
            'sync',
            'Customer',
            null,
            "Synced {$count} customers",
            ['customers' => $count],
            $store->id
        );

        return ['customers' => $count];
    }

    protected function buildQuery(?string $since): string
    {
        $updatedAtFilter = $since ? ", query: \"updated_at:>'{$since}'\"" : '';

        return <<<GQL
        query(\$cursor: String) {
          customers(first: 50, after: \$cursor{$updatedAtFilter}) {
            edges {
              node {
                id
                email
                firstName
                lastName
                state
                createdAt
                updatedAt
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
