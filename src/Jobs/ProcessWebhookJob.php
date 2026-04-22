<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Jobs;

use LaravelShopifySdk\Models\Sync\WebhookEvent;
use LaravelShopifySdk\Webhooks\WebhookHandlerInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public WebhookEvent $event
    ) {}

    public function handle(): void
    {
        try {
            Log::info('Processing webhook', [
                'event_id' => $this->event->id,
                'topic' => $this->event->topic,
                'shop_domain' => $this->event->shop_domain,
            ]);

            $this->dispatch_to_handler();

            $this->event->update([
                'status' => 'processed',
                'processed_at' => now(),
            ]);

        } catch (\Exception $e) {
            Log::error('Webhook processing failed', [
                'event_id' => $this->event->id,
                'error' => $e->getMessage(),
            ]);

            $this->event->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Resolve and run the handler for this webhook topic.
     *
     * Handlers are registered in config('shopify.webhooks.handlers') as:
     *   'app/uninstalled' => AppUninstalledHandler::class,
     */
    protected function dispatch_to_handler(): void
    {
        $handlers = config('shopify.webhooks.handlers', []);
        $handlerClass = $handlers[$this->event->topic] ?? null;

        if (!$handlerClass) {
            Log::debug('No handler registered for webhook topic', [
                'topic' => $this->event->topic,
            ]);
            return;
        }

        $handler = app($handlerClass);

        if (!$handler instanceof WebhookHandlerInterface) {
            throw new \InvalidArgumentException(
                "Webhook handler [{$handlerClass}] must implement WebhookHandlerInterface"
            );
        }

        $handler->handle($this->event);
    }
}
