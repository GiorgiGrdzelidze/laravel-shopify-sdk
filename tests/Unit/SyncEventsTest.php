<?php

namespace LaravelShopifySdk\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use LaravelShopifySdk\Events\SyncCompleted;
use LaravelShopifySdk\Events\SyncFailed;
use LaravelShopifySdk\Models\Core\Store;
use LaravelShopifySdk\Tests\TestCase;

class SyncEventsTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_completed_event_has_correct_properties(): void
    {
        $store = Store::create([
            'shop_domain' => 'test.myshopify.com',
            'access_token' => 'token',
            'mode' => 'token',
            'status' => 'active',
        ]);

        $syncRun = \LaravelShopifySdk\Models\Sync\SyncRun::create([
            'store_id' => $store->id,
            'entity' => 'products',
            'started_at' => now(),
        ]);

        $event = new SyncCompleted(
            store: $store,
            entity: 'products',
            syncRun: $syncRun,
            counts: ['created' => 10, 'updated' => 5],
            durationMs: 1500,
        );

        $this->assertEquals('products', $event->entity);
        $this->assertEquals(10, $event->counts['created']);
        $this->assertEquals(5, $event->counts['updated']);
        $this->assertEquals(1500, $event->durationMs);
        $this->assertEquals($store->id, $event->store->id);
        $this->assertEquals($syncRun->id, $event->syncRun->id);
    }

    public function test_sync_failed_event_has_correct_properties(): void
    {
        $store = Store::create([
            'shop_domain' => 'test.myshopify.com',
            'access_token' => 'token',
            'mode' => 'token',
            'status' => 'active',
        ]);

        $syncRun = \LaravelShopifySdk\Models\Sync\SyncRun::create([
            'store_id' => $store->id,
            'entity' => 'orders',
            'started_at' => now(),
        ]);

        $exception = new \RuntimeException('API timeout');

        $event = new SyncFailed(
            store: $store,
            entity: 'orders',
            syncRun: $syncRun,
            exception: $exception,
            durationMs: 30000,
        );

        $this->assertEquals('orders', $event->entity);
        $this->assertEquals('API timeout', $event->exception->getMessage());
        $this->assertEquals(30000, $event->durationMs);
    }

    public function test_sync_completed_event_is_dispatchable(): void
    {
        Event::fake([SyncCompleted::class]);

        $store = Store::create([
            'shop_domain' => 'test.myshopify.com',
            'access_token' => 'token',
            'mode' => 'token',
            'status' => 'active',
        ]);

        $syncRun = \LaravelShopifySdk\Models\Sync\SyncRun::create([
            'store_id' => $store->id,
            'entity' => 'products',
            'started_at' => now(),
        ]);

        SyncCompleted::dispatch($store, 'products', $syncRun, ['created' => 5], 1000);

        Event::assertDispatched(SyncCompleted::class, function ($event) {
            return $event->entity === 'products' && $event->counts['created'] === 5;
        });
    }

    public function test_sync_failed_event_is_dispatchable(): void
    {
        Event::fake([SyncFailed::class]);

        $store = Store::create([
            'shop_domain' => 'test.myshopify.com',
            'access_token' => 'token',
            'mode' => 'token',
            'status' => 'active',
        ]);

        $syncRun = \LaravelShopifySdk\Models\Sync\SyncRun::create([
            'store_id' => $store->id,
            'entity' => 'orders',
            'started_at' => now(),
        ]);

        $exception = new \RuntimeException('fail');

        SyncFailed::dispatch($store, 'orders', $syncRun, $exception, 500);

        Event::assertDispatched(SyncFailed::class, function ($event) {
            return $event->entity === 'orders';
        });
    }
}
