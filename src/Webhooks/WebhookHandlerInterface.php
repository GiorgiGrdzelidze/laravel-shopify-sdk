<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Webhooks;

use LaravelShopifySdk\Models\Sync\WebhookEvent;

/**
 * Contract for webhook topic handlers.
 *
 * Implement this interface and register in config('shopify.webhooks.handlers')
 * to handle specific webhook topics.
 */
interface WebhookHandlerInterface
{
    /**
     * Handle a webhook event.
     */
    public function handle(WebhookEvent $event): void;
}
