<?php

namespace LaravelShopifySdk\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use LaravelShopifySdk\Auth\StoreRepository;
use LaravelShopifySdk\Jobs\ProcessWebhookJob;
use LaravelShopifySdk\Models\Core\Store;
use LaravelShopifySdk\Models\Sync\WebhookEvent;
use LaravelShopifySdk\Tests\TestCase;
use LaravelShopifySdk\Webhooks\Handlers\AppUninstalledHandler;
use LaravelShopifySdk\Webhooks\WebhookHandlerInterface;

class WebhookHandlerTest extends TestCase
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

    public function test_app_uninstalled_handler_marks_store_inactive(): void
    {
        $event = WebhookEvent::create([
            'store_id' => $this->store->id,
            'topic' => 'app/uninstalled',
            'shop_domain' => $this->store->shop_domain,
            'payload' => ['shop_domain' => $this->store->shop_domain],
            'status' => 'pending',
        ]);

        $handler = app(AppUninstalledHandler::class);
        $handler->handle($event);

        $this->store->refresh();
        $this->assertEquals('inactive', $this->store->status);
        $this->assertNotNull($this->store->uninstalled_at);
    }

    public function test_process_webhook_job_dispatches_to_handler(): void
    {
        config()->set('shopify.webhooks.handlers', [
            'app/uninstalled' => AppUninstalledHandler::class,
        ]);

        $event = WebhookEvent::create([
            'store_id' => $this->store->id,
            'topic' => 'app/uninstalled',
            'shop_domain' => $this->store->shop_domain,
            'payload' => [],
            'status' => 'pending',
        ]);

        $job = new ProcessWebhookJob($event);
        $job->handle();

        $event->refresh();
        $this->assertEquals('processed', $event->status);
        $this->assertNotNull($event->processed_at);

        $this->store->refresh();
        $this->assertEquals('inactive', $this->store->status);
    }

    public function test_process_webhook_job_handles_no_handler(): void
    {
        config()->set('shopify.webhooks.handlers', []);

        $event = WebhookEvent::create([
            'store_id' => $this->store->id,
            'topic' => 'products/create',
            'shop_domain' => $this->store->shop_domain,
            'payload' => ['product' => ['id' => 1]],
            'status' => 'pending',
        ]);

        $job = new ProcessWebhookJob($event);
        $job->handle();

        $event->refresh();
        $this->assertEquals('processed', $event->status);
    }

    public function test_process_webhook_job_rejects_invalid_handler(): void
    {
        config()->set('shopify.webhooks.handlers', [
            'products/create' => \stdClass::class,
        ]);

        $event = WebhookEvent::create([
            'store_id' => $this->store->id,
            'topic' => 'products/create',
            'shop_domain' => $this->store->shop_domain,
            'payload' => [],
            'status' => 'pending',
        ]);

        $job = new ProcessWebhookJob($event);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must implement WebhookHandlerInterface');

        $job->handle();
    }

    public function test_failed_webhook_marks_event_as_failed(): void
    {
        // Register a handler that throws
        $this->app->bind('failing-handler', function () {
            return new class implements WebhookHandlerInterface {
                public function handle(WebhookEvent $event): void
                {
                    throw new \RuntimeException('Processing failed');
                }
            };
        });

        config()->set('shopify.webhooks.handlers', [
            'orders/create' => 'failing-handler',
        ]);

        $event = WebhookEvent::create([
            'store_id' => $this->store->id,
            'topic' => 'orders/create',
            'shop_domain' => $this->store->shop_domain,
            'payload' => [],
            'status' => 'pending',
        ]);

        $job = new ProcessWebhookJob($event);

        try {
            $job->handle();
        } catch (\RuntimeException) {
            // Expected
        }

        $event->refresh();
        $this->assertEquals('failed', $event->status);
        $this->assertEquals('Processing failed', $event->error);
    }

    public function test_webhook_handler_interface_exists(): void
    {
        $this->assertTrue(interface_exists(WebhookHandlerInterface::class));

        $reflection = new \ReflectionClass(WebhookHandlerInterface::class);
        $this->assertTrue($reflection->hasMethod('handle'));
    }
}
