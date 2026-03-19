<?php

namespace LaravelShopifySdk\Tests\Unit;

use Illuminate\Support\Facades\DB;
use LaravelShopifySdk\Models\Store;
use LaravelShopifySdk\Tests\TestCase;

class StoreCrudTest extends TestCase
{
    public function test_store_can_be_created_with_encrypted_token(): void
    {
        $store = Store::create([
            'shop_domain' => 'test-store.myshopify.com',
            'access_token' => 'shpat_test_token_12345',
            'mode' => Store::MODE_TOKEN,
            'status' => Store::STATUS_ACTIVE,
            'scopes' => 'read_products,write_products',
        ]);

        $this->assertDatabaseHas(config('shopify.tables.stores', 'shopify_stores'), [
            'shop_domain' => 'test-store.myshopify.com',
            'mode' => 'token',
            'status' => 'active',
        ]);

        // Token should be encrypted in database but decrypted when accessed
        $this->assertEquals('shpat_test_token_12345', $store->access_token);

        // Verify the raw database value is encrypted (not plain text)
        $rawValue = \DB::table(config('shopify.tables.stores', 'shopify_stores'))
            ->where('id', $store->id)
            ->value('access_token');

        $this->assertNotEquals('shpat_test_token_12345', $rawValue);
    }

    public function test_store_shop_domain_must_be_unique(): void
    {
        Store::create([
            'shop_domain' => 'unique-store.myshopify.com',
            'access_token' => 'token1',
            'mode' => Store::MODE_TOKEN,
            'status' => Store::STATUS_ACTIVE,
        ]);

        $this->expectException(\Illuminate\Database\QueryException::class);

        Store::create([
            'shop_domain' => 'unique-store.myshopify.com',
            'access_token' => 'token2',
            'mode' => Store::MODE_TOKEN,
            'status' => Store::STATUS_ACTIVE,
        ]);
    }

    public function test_store_can_be_marked_as_active(): void
    {
        $store = Store::create([
            'shop_domain' => 'inactive-store.myshopify.com',
            'access_token' => 'token',
            'mode' => Store::MODE_TOKEN,
            'status' => Store::STATUS_INACTIVE,
        ]);

        $this->assertFalse($store->isActive());

        $store->markAsActive();

        $this->assertTrue($store->fresh()->isActive());
        $this->assertNotNull($store->fresh()->installed_at);
    }

    public function test_store_can_be_marked_as_inactive(): void
    {
        $store = Store::create([
            'shop_domain' => 'active-store.myshopify.com',
            'access_token' => 'token',
            'mode' => Store::MODE_TOKEN,
            'status' => Store::STATUS_ACTIVE,
        ]);

        $this->assertTrue($store->isActive());

        $store->markAsInactive();

        $this->assertFalse($store->fresh()->isActive());
        $this->assertNotNull($store->fresh()->uninstalled_at);
    }

    public function test_store_mode_helpers(): void
    {
        $oauthStore = Store::create([
            'shop_domain' => 'oauth-store.myshopify.com',
            'access_token' => 'token',
            'mode' => Store::MODE_OAUTH,
            'status' => Store::STATUS_ACTIVE,
        ]);

        $tokenStore = Store::create([
            'shop_domain' => 'token-store.myshopify.com',
            'access_token' => 'token',
            'mode' => Store::MODE_TOKEN,
            'status' => Store::STATUS_ACTIVE,
        ]);

        $this->assertTrue($oauthStore->isOAuthMode());
        $this->assertFalse($oauthStore->isTokenMode());

        $this->assertTrue($tokenStore->isTokenMode());
        $this->assertFalse($tokenStore->isOAuthMode());
    }

    public function test_store_masked_token_attribute(): void
    {
        $store = Store::create([
            'shop_domain' => 'masked-store.myshopify.com',
            'access_token' => 'shpat_1234567890abcdef',
            'mode' => Store::MODE_TOKEN,
            'status' => Store::STATUS_ACTIVE,
        ]);

        $maskedToken = $store->masked_token;

        // Should show first 4 and last 4 characters
        $this->assertStringStartsWith('shpa', $maskedToken);
        $this->assertStringEndsWith('cdef', $maskedToken);
        $this->assertStringContainsString('•', $maskedToken);
    }

    public function test_store_metadata_is_cast_to_array(): void
    {
        $store = Store::create([
            'shop_domain' => 'metadata-store.myshopify.com',
            'access_token' => 'token',
            'mode' => Store::MODE_TOKEN,
            'status' => Store::STATUS_ACTIVE,
            'metadata' => ['key1' => 'value1', 'key2' => 'value2'],
        ]);

        $this->assertIsArray($store->metadata);
        $this->assertEquals('value1', $store->metadata['key1']);
        $this->assertEquals('value2', $store->metadata['key2']);
    }
}
