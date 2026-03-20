<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Jobs;

use LaravelShopifySdk\Auth\StoreRepository;
use LaravelShopifySdk\Models\Sync\WebhookEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Process Webhook Job
 *
 * Asynchronously processes Shopify webhook events.
 * Handles app/uninstalled events and updates webhook status.
 *
 * @package LaravelShopifySdk\Jobs
 *
 * Processes incoming Shopify webhooks asynchronously.
 */
class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public WebhookEvent $event
    ) {}

    public function handle(StoreRepository $storeRepository): void
    {
        try {
            Log::info('Processing webhook', [
                'event_id' => $this->event->id,
                'topic' => $this->event->topic,
                'shop_domain' => $this->event->shop_domain,
            ]);

            if ($this->event->topic === 'app/uninstalled') {
                $storeRepository->markAsUninstalled($this->event->shop_domain);
            }

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
}
