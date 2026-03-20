<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Http\Controllers;

use LaravelShopifySdk\Auth\StoreRepository;
use LaravelShopifySdk\Exceptions\WebhookException;
use LaravelShopifySdk\Jobs\ProcessWebhookJob;
use LaravelShopifySdk\Models\Sync\WebhookEvent;
use LaravelShopifySdk\Webhooks\WebhookVerifier;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;

/**
 * Webhook Controller
 *
 * Handles incoming Shopify webhooks with HMAC verification.
 * Stores events and dispatches async processing jobs.
 *
 * @package LaravelShopifySdk\Http\Controllers
 */
class WebhookController extends Controller
{
    public function __construct(
        protected WebhookVerifier $verifier,
        protected StoreRepository $storeRepository
    ) {}

    /**
     * Handle incoming webhook.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        try {
            if (!$this->verifier->verifyRequest($request)) {
                Log::warning('Webhook verification failed', [
                    'shop_domain' => $request->header('X-Shopify-Shop-Domain'),
                    'topic' => $request->header('X-Shopify-Topic'),
                ]);

                return response()->json(['error' => 'Invalid signature'], 401);
            }

            $shopDomain = $request->header('X-Shopify-Shop-Domain');
            $topic = $request->header('X-Shopify-Topic');
            $webhookId = $request->header('X-Shopify-Webhook-Id');
            $payload = $request->all();

            $store = $this->storeRepository->findByDomain($shopDomain);

            if (!$store) {
                Log::warning('Webhook received for unknown store', [
                    'shop_domain' => $shopDomain,
                    'topic' => $topic,
                ]);

                return response()->json(['error' => 'Store not found'], 404);
            }

            $event = $this->storeWebhookEvent($store->id, $shopDomain, $topic, $webhookId, $payload);

            if (config('shopify.webhooks.process_async', true)) {
                ProcessWebhookJob::dispatch($event)
                    ->onQueue(config('shopify.webhooks.queue', 'default'));
            } else {
                $this->processWebhook($event);
            }

            return response()->json(['success' => true], 200);

        } catch (WebhookException $e) {
            Log::error('Webhook handling failed', [
                'error' => $e->getMessage(),
                'shop_domain' => $request->header('X-Shopify-Shop-Domain'),
            ]);

            return response()->json(['error' => 'Webhook processing failed'], 500);
        }
    }

    /**
     * Store webhook event.
     *
     * @param int $storeId
     * @param string $shopDomain
     * @param string $topic
     * @param string|null $webhookId
     * @param array<string, mixed> $payload
     * @return WebhookEvent
     */
    protected function storeWebhookEvent(
        int $storeId,
        string $shopDomain,
        string $topic,
        ?string $webhookId,
        array $payload
    ): WebhookEvent {
        return WebhookEvent::firstOrCreate(
            [
                'store_id' => $storeId,
                'topic' => $topic,
                'webhook_id' => $webhookId,
            ],
            [
                'shop_domain' => $shopDomain,
                'payload' => $payload,
                'status' => 'pending',
                'received_at' => now(),
            ]
        );
    }

    /**
     * Process webhook event.
     *
     * @param WebhookEvent $event
     * @return void
     */
    protected function processWebhook(WebhookEvent $event): void
    {
        if ($event->topic === 'app/uninstalled') {
            $this->storeRepository->markAsUninstalled($event->shop_domain);
        }

        $event->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }
}
