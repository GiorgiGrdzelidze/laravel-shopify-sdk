<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Debug;

use LaravelShopifySdk\Clients\ShopifyClient;
use LaravelShopifySdk\Models\Core\Store;

class TestOrderRest
{
    public static function test()
    {
        $store = Store::first();
        if (!$store) {
            return ['error' => 'No store found'];
        }

        $client = app(ShopifyClient::class);
        
        try {
            // Use REST API to check orders
            $response = $client->rest($store)->get($store, 'orders.json', [
                'limit' => 5,
                'status' => 'any'
            ]);
            return $response;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()];
        }
    }
}
