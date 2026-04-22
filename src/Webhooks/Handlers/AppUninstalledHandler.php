<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Webhooks\Handlers;

use LaravelShopifySdk\Auth\StoreRepository;
use LaravelShopifySdk\Models\Sync\WebhookEvent;
use LaravelShopifySdk\Webhooks\WebhookHandlerInterface;

class AppUninstalledHandler implements WebhookHandlerInterface
{
    public function __construct(
        protected StoreRepository $storeRepository
    ) {}

    public function handle(WebhookEvent $event): void
    {
        $this->storeRepository->markAsUninstalled($event->shop_domain);
    }
}
