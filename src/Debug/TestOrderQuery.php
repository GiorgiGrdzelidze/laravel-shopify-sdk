<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Debug;

use LaravelShopifySdk\Clients\ShopifyClient;
use LaravelShopifySdk\Models\Store;

class TestOrderQuery
{
    public static function test()
    {
        $store = Store::first();
        if (!$store) {
            return ['error' => 'No store found'];
        }

        $client = app(ShopifyClient::class);
        
        // Test simple query
        $query = <<<'GQL'
        query {
          orders(first: 5) {
            edges {
              node {
                id
                name
                orderNumber
              }
            }
          }
        }
        GQL;

        try {
            $response = $client->graphql($store)->query($store, $query);
            return $response;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage()];
        }
    }
}
