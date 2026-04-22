<?php

namespace LaravelShopifySdk\Tests\Unit;

use Illuminate\Support\Facades\Http;
use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Exceptions\ShopifyApiException;
use LaravelShopifySdk\Models\Core\Store;
use LaravelShopifySdk\Tests\TestCase;

class GraphQLClientTest extends TestCase
{
    protected GraphQLClient $client;
    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new GraphQLClient();
        $this->store = Store::create([
            'shop_domain' => 'test-store.myshopify.com',
            'access_token' => 'shpat_test_token',
            'mode' => 'token',
            'status' => 'active',
        ]);
    }

    public function test_successful_query_returns_data(): void
    {
        Http::fake([
            '*/graphql.json' => Http::response([
                'data' => ['products' => ['edges' => []]],
                'extensions' => [
                    'cost' => [
                        'requestedQueryCost' => 10,
                        'actualQueryCost' => 8,
                        'throttleStatus' => [
                            'currentlyAvailable' => 900,
                            'maximumAvailable' => 1000,
                            'restoreRate' => 50,
                        ],
                    ],
                ],
            ]),
        ]);

        $result = $this->client->query($this->store, '{ products(first: 10) { edges { node { id } } } }');

        $this->assertArrayHasKey('data', $result);
        $this->assertArrayHasKey('products', $result['data']);
    }

    public function test_graphql_errors_throw_exception(): void
    {
        Http::fake([
            '*/graphql.json' => Http::response([
                'data' => null,
                'errors' => [
                    ['message' => 'Field \'nonexistent\' doesn\'t exist on type \'Product\''],
                    ['message' => 'Parse error on query'],
                ],
            ]),
        ]);

        $this->expectException(ShopifyApiException::class);
        $this->expectExceptionMessage('Field \'nonexistent\'');

        $this->client->query($this->store, '{ products { nonexistent } }');
    }

    public function test_graphql_errors_include_shop_domain(): void
    {
        Http::fake([
            '*' => Http::response([
                'errors' => [['message' => 'Some error']],
            ]),
        ]);

        try {
            $this->client->query($this->store, '{ bad }');
            $this->fail('Expected ShopifyApiException');
        } catch (ShopifyApiException $e) {
            $this->assertEquals('test-store.myshopify.com', $e->getShopDomain());
            $this->assertEquals(ShopifyApiException::ERROR_GRAPHQL, $e->getErrorType());
            $this->assertArrayHasKey('graphql_errors', $e->getContext());
        }
    }

    public function test_non_successful_response_throws_exception(): void
    {
        Http::fake([
            '*/graphql.json' => Http::response('Unauthorized', 401),
        ]);

        $this->expectException(ShopifyApiException::class);
        $this->expectExceptionMessage('GraphQL request failed: 401');

        $this->client->query($this->store, '{ products { id } }');
    }

    public function test_rate_limit_retries_and_succeeds(): void
    {
        Http::fakeSequence()
            ->push('', 429, ['Retry-After' => '0'])
            ->push([
                'data' => ['products' => []],
                'extensions' => ['cost' => [
                    'requestedQueryCost' => 5,
                    'actualQueryCost' => 5,
                    'throttleStatus' => ['currentlyAvailable' => 950, 'maximumAvailable' => 1000, 'restoreRate' => 50],
                ]],
            ], 200);

        $result = $this->client->query($this->store, '{ products { id } }');

        $this->assertArrayHasKey('data', $result);
        Http::assertSentCount(2);
    }

    public function test_rate_limit_exhausts_retries(): void
    {
        Http::fake([
            '*' => Http::response('', 429, ['Retry-After' => '0']),
        ]);

        config()->set('shopify.client.retry_times', 2);

        $this->expectException(ShopifyApiException::class);
        $this->expectExceptionMessage('Rate limit exceeded');

        $this->client->query($this->store, '{ products { id } }');
    }

    public function test_server_error_retries_with_backoff(): void
    {
        Http::fakeSequence()
            ->push('Server Error', 500)
            ->push([
                'data' => ['shop' => ['name' => 'Test']],
                'extensions' => ['cost' => [
                    'requestedQueryCost' => 1,
                    'actualQueryCost' => 1,
                    'throttleStatus' => ['currentlyAvailable' => 999, 'maximumAvailable' => 1000, 'restoreRate' => 50],
                ]],
            ], 200);

        $result = $this->client->query($this->store, '{ shop { name } }');

        $this->assertEquals('Test', $result['data']['shop']['name']);
        Http::assertSentCount(2);
    }

    public function test_uses_correct_api_version_in_url(): void
    {
        config()->set('shopify.api_version', '2026-04');

        Http::fake([
            '*/admin/api/2026-04/graphql.json' => Http::response([
                'data' => [],
                'extensions' => ['cost' => [
                    'requestedQueryCost' => 1,
                    'actualQueryCost' => 1,
                    'throttleStatus' => ['currentlyAvailable' => 999, 'maximumAvailable' => 1000, 'restoreRate' => 50],
                ]],
            ]),
        ]);

        $this->client->query($this->store, '{ shop { name } }');

        Http::assertSent(function ($request) {
            return str_contains($request->url(), '/admin/api/2026-04/graphql.json');
        });
    }

    public function test_sends_access_token_header(): void
    {
        Http::fake([
            '*/graphql.json' => Http::response([
                'data' => [],
                'extensions' => ['cost' => [
                    'requestedQueryCost' => 1,
                    'actualQueryCost' => 1,
                    'throttleStatus' => ['currentlyAvailable' => 999, 'maximumAvailable' => 1000, 'restoreRate' => 50],
                ]],
            ]),
        ]);

        $this->client->query($this->store, '{ shop { name } }');

        Http::assertSent(function ($request) {
            return $request->hasHeader('X-Shopify-Access-Token', 'shpat_test_token');
        });
    }

    public function test_paginate_follows_cursor(): void
    {
        Http::fakeSequence()
            ->push([
                'data' => [
                    'products' => [
                        'edges' => [['node' => ['id' => '1']]],
                        'pageInfo' => ['hasNextPage' => true, 'endCursor' => 'cursor_abc'],
                    ],
                ],
                'extensions' => ['cost' => [
                    'requestedQueryCost' => 5,
                    'actualQueryCost' => 5,
                    'throttleStatus' => ['currentlyAvailable' => 950, 'maximumAvailable' => 1000, 'restoreRate' => 50],
                ]],
            ])
            ->push([
                'data' => [
                    'products' => [
                        'edges' => [['node' => ['id' => '2']]],
                        'pageInfo' => ['hasNextPage' => false, 'endCursor' => null],
                    ],
                ],
                'extensions' => ['cost' => [
                    'requestedQueryCost' => 5,
                    'actualQueryCost' => 5,
                    'throttleStatus' => ['currentlyAvailable' => 945, 'maximumAvailable' => 1000, 'restoreRate' => 50],
                ]],
            ]);

        $pages = [];
        $this->client->paginate(
            $this->store,
            '{ products(first: 1, after: $cursor) { edges { node { id } } pageInfo { hasNextPage endCursor } } }',
            ['cursor' => null],
            function ($response) use (&$pages) {
                $pages[] = $response;
            }
        );

        $this->assertCount(2, $pages);
        Http::assertSentCount(2);
    }
}
