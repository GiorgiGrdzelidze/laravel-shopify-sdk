<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Debug;

use LaravelShopifySdk\Clients\ShopifyClient;
use LaravelShopifySdk\Models\Core\Store;

class TestOrderGraphQL
{
    public static function test()
    {
        $store = Store::first();
        if (!$store) {
            return ['error' => 'No store found'];
        }

        $client = app(ShopifyClient::class);
        
        // Test with status:any filter
        $query = <<<'GQL'
        query($cursor: String) {
          orders(first: 5, after: $cursor, query: "status:any") {
            edges {
              node {
                id
                name
                orderNumber
              }
            }
            pageInfo {
              hasNextPage
              endCursor
            }
          }
        }
        GQL;

        try {
            $response = $client->graphql($store)->query($store, $query, ['cursor' => null]);
            return $response;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
