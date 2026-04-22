<?php

namespace LaravelShopifySdk\Tests\Unit;

use Illuminate\Support\Facades\Http;
use LaravelShopifySdk\Clients\RestClient;
use LaravelShopifySdk\Exceptions\ShopifyApiException;
use LaravelShopifySdk\Models\Core\Store;
use LaravelShopifySdk\Tests\TestCase;

class RestClientTest extends TestCase
{
    protected RestClient $client;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new RestClient();
        $this->store = Store::create([
            'shop_domain' => 'test-store.myshopify.com',
            'access_token' => 'shpat_test_token',
            'mode' => 'token',
            'status' => 'active',
        ]);
    }

    public function test_get_request_returns_data(): void
    {
        Http::fake([
            '*/products.json*' => Http::response([
                'products' => [['id' => 1, 'title' => 'Test']],
            ], 200, ['X-Shopify-Shop-Api-Call-Limit' => '1/40']),
        ]);

        $result = $this->client->get($this->store, 'products.json');

        $this->assertArrayHasKey('products', $result);
        $this->assertCount(1, $result['products']);
    }

    public function test_post_request_sends_data(): void
    {
        Http::fake([
            '*/products.json' => Http::response([
                'product' => ['id' => 1, 'title' => 'New'],
            ], 201, ['X-Shopify-Shop-Api-Call-Limit' => '2/40']),
        ]);

        $result = $this->client->post($this->store, 'products.json', [
            'product' => ['title' => 'New'],
        ]);

        $this->assertEquals('New', $result['product']['title']);
    }

    public function test_uses_correct_api_version(): void
    {
        config()->set('shopify.api_version', '2026-04');

        Http::fake([
            '*/admin/api/2026-04/products.json*' => Http::response(
                ['products' => []],
                200,
                ['X-Shopify-Shop-Api-Call-Limit' => '1/40']
            ),
        ]);

        $this->client->get($this->store, 'products.json');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/admin/api/2026-04/');
        });
    }

    public function test_rate_limit_retries(): void
    {
        Http::fakeSequence()
            ->push('', 429, ['Retry-After' => '0'])
            ->push(['products' => []], 200, ['X-Shopify-Shop-Api-Call-Limit' => '1/40']);

        $result = $this->client->get($this->store, 'products.json');

        $this->assertArrayHasKey('products', $result);
        Http::assertSentCount(2);
    }

    public function test_server_error_retries(): void
    {
        Http::fakeSequence()
            ->push('Internal Server Error', 500)
            ->push(['products' => []], 200, ['X-Shopify-Shop-Api-Call-Limit' => '1/40']);

        $result = $this->client->get($this->store, 'products.json');

        $this->assertArrayHasKey('products', $result);
        Http::assertSentCount(2);
    }

    public function test_non_successful_throws_exception(): void
    {
        Http::fake([
            '*/products.json*' => Http::response('Not Found', 404),
        ]);

        $this->expectException(ShopifyApiException::class);
        $this->expectExceptionMessage('REST request failed: 404');

        $this->client->get($this->store, 'products.json');
    }

    public function test_paginate_follows_link_header(): void
    {
        $page2Url = 'https://test-store.myshopify.com/admin/api/2026-04/products.json?page_info=next_cursor&limit=2';

        Http::fakeSequence()
            ->push(
                ['products' => [['id' => 1]]],
                200,
                [
                    'X-Shopify-Shop-Api-Call-Limit' => '1/40',
                    'Link' => "<{$page2Url}>; rel=\"next\"",
                ]
            )
            ->push(
                ['products' => [['id' => 2]]],
                200,
                [
                    'X-Shopify-Shop-Api-Call-Limit' => '2/40',
                ]
            );

        $pages = [];
        $this->client->paginate($this->store, 'products.json', ['limit' => 2], function ($data) use (&$pages) {
            $pages[] = $data;
        });

        $this->assertCount(2, $pages);
        $this->assertEquals(1, $pages[0]['products'][0]['id']);
        $this->assertEquals(2, $pages[1]['products'][0]['id']);
    }

    public function test_paginate_stops_without_link_header(): void
    {
        Http::fake([
            '*/products.json*' => Http::response(
                ['products' => [['id' => 1]]],
                200,
                ['X-Shopify-Shop-Api-Call-Limit' => '1/40']
            ),
        ]);

        $pages = [];
        $this->client->paginate($this->store, 'products.json', [], function ($data) use (&$pages) {
            $pages[] = $data;
        });

        $this->assertCount(1, $pages);
    }

    public function test_delete_request(): void
    {
        Http::fake([
            '*/products/123.json' => Http::response([], 200, ['X-Shopify-Shop-Api-Call-Limit' => '1/40']),
        ]);

        $result = $this->client->delete($this->store, 'products/123.json');

        $this->assertIsArray($result);
    }
}
