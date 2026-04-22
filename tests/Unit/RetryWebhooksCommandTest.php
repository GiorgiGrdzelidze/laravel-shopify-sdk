<?php

namespace LaravelShopifySdk\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use LaravelShopifySdk\Jobs\ProcessWebhookJob;
use LaravelShopifySdk\Models\Core\Store;
use LaravelShopifySdk\Models\Sync\WebhookEvent;
use LaravelShopifySdk\Tests\TestCase;

class RetryWebhooksCommandTest extends TestCase
{
    use RefreshDatabase;

    protected Store $store;

    protected function setUp(): void
    {
        parent::setUp();
        $this->store = Store::create([
            'shop_domain' => 'test-store.myshopify.com',
            'access_token' => 'token',
            'mode' => 'token',
            'status' => 'active',
        ]);
    }

    public function test_retries_failed_events(): void
    {
        Queue::fake();

        WebhookEvent::create([
            'store_id' => $this->store->id,
            'topic' => 'orders/create',
            'shop_domain' => $this->store->shop_domain,
            'payload' => [],
            'status' => 'failed',
            'error' => 'Previous failure',
        ]);

        $this->artisan('shopify:webhooks:retry')
            ->assertSuccessful()
            ->expectsOutputToContain('Dispatched 1 event(s)');

        Queue::assertPushed(ProcessWebhookJob::class, 1);
    }

    public function test_resets_event_status_to_pending(): void
    {
        Queue::fake();

        $event = WebhookEvent::create([
            'store_id' => $this->store->id,
            'topic' => 'orders/create',
            'shop_domain' => $this->store->shop_domain,
            'payload' => [],
            'status' => 'failed',
            'error' => 'Old error',
        ]);

        $this->artisan('shopify:webhooks:retry')->assertSuccessful();

        $event->refresh();
        $this->assertEquals('pending', $event->status);
        $this->assertNull($event->error);
    }

    public function test_filters_by_store(): void
    {
        Queue::fake();

        $otherStore = Store::create([
            'shop_domain' => 'other-store.myshopify.com',
            'access_token' => 'token',
            'mode' => 'token',
            'status' => 'active',
        ]);

        WebhookEvent::create([
            'store_id' => $this->store->id,
            'topic' => 'orders/create',
            'shop_domain' => $this->store->shop_domain,
            'payload' => [],
            'status' => 'failed',
        ]);

        WebhookEvent::create([
            'store_id' => $otherStore->id,
            'topic' => 'orders/create',
            'shop_domain' => $otherStore->shop_domain,
            'payload' => [],
            'status' => 'failed',
        ]);

        $this->artisan('shopify:webhooks:retry', ['--store' => 'test-store.myshopify.com'])
            ->assertSuccessful()
            ->expectsOutputToContain('Dispatched 1 event(s)');

        Queue::assertPushed(ProcessWebhookJob::class, 1);
    }

    public function test_filters_by_topic(): void
    {
        Queue::fake();

        WebhookEvent::create([
            'store_id' => $this->store->id,
            'topic' => 'orders/create',
            'shop_domain' => $this->store->shop_domain,
            'payload' => [],
            'status' => 'failed',
        ]);

        WebhookEvent::create([
            'store_id' => $this->store->id,
            'topic' => 'products/update',
            'shop_domain' => $this->store->shop_domain,
            'payload' => [],
            'status' => 'failed',
        ]);

        $this->artisan('shopify:webhooks:retry', ['--topic' => 'products/update'])
            ->assertSuccessful()
            ->expectsOutputToContain('Dispatched 1 event(s)');
    }

    public function test_respects_limit(): void
    {
        Queue::fake();

        for ($i = 0; $i < 5; $i++) {
            WebhookEvent::create([
                'store_id' => $this->store->id,
                'topic' => 'orders/create',
                'shop_domain' => $this->store->shop_domain,
                'payload' => [],
                'status' => 'failed',
            ]);
        }

        $this->artisan('shopify:webhooks:retry', ['--limit' => 2])
            ->assertSuccessful()
            ->expectsOutputToContain('Dispatched 2 event(s)');

        Queue::assertPushed(ProcessWebhookJob::class, 2);
    }

    public function test_dry_run_doesnt_dispatch(): void
    {
        Queue::fake();

        WebhookEvent::create([
            'store_id' => $this->store->id,
            'topic' => 'orders/create',
            'shop_domain' => $this->store->shop_domain,
            'payload' => [],
            'status' => 'failed',
        ]);

        $this->artisan('shopify:webhooks:retry', ['--dry-run' => true])
            ->assertSuccessful()
            ->expectsOutputToContain('DRY RUN');

        Queue::assertNothingPushed();
    }

    public function test_no_failed_events_message(): void
    {
        $this->artisan('shopify:webhooks:retry')
            ->assertSuccessful()
            ->expectsOutputToContain('No failed webhook events found');
    }

    public function test_ignores_processed_events(): void
    {
        Queue::fake();

        WebhookEvent::create([
            'store_id' => $this->store->id,
            'topic' => 'orders/create',
            'shop_domain' => $this->store->shop_domain,
            'payload' => [],
            'status' => 'processed',
        ]);

        $this->artisan('shopify:webhooks:retry')
            ->assertSuccessful()
            ->expectsOutputToContain('No failed webhook events found');
    }
}
