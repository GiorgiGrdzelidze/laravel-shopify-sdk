<?php

namespace LaravelShopifySdk\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelShopifySdk\Auth\StoreRepository;
use LaravelShopifySdk\Models\Store;
use LaravelShopifySdk\Tests\TestCase;

class StoreRepositoryTest extends TestCase
{
    use RefreshDatabase;

    protected StoreRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new StoreRepository();
    }

    public function test_creates_store(): void
    {
        $store = $this->repository->createOrUpdate(
            'test-shop.myshopify.com',
            'test-token',
            'read_products,write_products'
        );

        $this->assertInstanceOf(Store::class, $store);
        $this->assertEquals('test-shop.myshopify.com', $store->shop_domain);
        $this->assertEquals('active', $store->status);
        $this->assertNotNull($store->installed_at);
    }

    public function test_updates_existing_store(): void
    {
        $store = $this->repository->createOrUpdate(
            'test-shop.myshopify.com',
            'old-token',
            'read_products'
        );

        $updatedStore = $this->repository->createOrUpdate(
            'test-shop.myshopify.com',
            'new-token',
            'read_products,write_products'
        );

        $this->assertEquals($store->id, $updatedStore->id);
        // Access through model accessor which handles decryption
        $this->assertEquals('new-token', $updatedStore->access_token);
    }

    public function test_finds_store_by_domain(): void
    {
        $this->repository->createOrUpdate(
            'test-shop.myshopify.com',
            'test-token'
        );

        $found = $this->repository->findByDomain('test-shop.myshopify.com');

        $this->assertInstanceOf(Store::class, $found);
        $this->assertEquals('test-shop.myshopify.com', $found->shop_domain);
    }

    public function test_gets_active_stores(): void
    {
        $this->repository->createOrUpdate('shop1.myshopify.com', 'token1');
        $this->repository->createOrUpdate('shop2.myshopify.com', 'token2');

        $inactive = $this->repository->createOrUpdate('shop3.myshopify.com', 'token3');
        $inactive->markAsInactive();

        $activeStores = $this->repository->getActiveStores();

        $this->assertCount(2, $activeStores);
    }

    public function test_marks_store_as_uninstalled(): void
    {
        $this->repository->createOrUpdate('test-shop.myshopify.com', 'test-token');

        $this->repository->markAsUninstalled('test-shop.myshopify.com');

        $store = $this->repository->findByDomain('test-shop.myshopify.com');
        $this->assertEquals('inactive', $store->status);
        $this->assertNotNull($store->uninstalled_at);
    }

    public function test_token_is_encrypted(): void
    {
        $plainToken = 'my-secret-token';
        $store = $this->repository->createOrUpdate(
            'test-shop.myshopify.com',
            $plainToken
        );

        // Verify raw database value is encrypted (not plain text)
        $rawToken = $store->getAttributes()['access_token'];
        $this->assertNotEquals($plainToken, $rawToken);
        $this->assertStringContainsString('eyJpdiI6', $rawToken); // Laravel encryption format

        // Verify model accessor decrypts correctly
        $this->assertEquals($plainToken, $store->access_token);
    }
}
