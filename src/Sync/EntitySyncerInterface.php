<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Sync;

use LaravelShopifySdk\Models\Store;

/**
 * Entity Syncer Interface
 *
 * Contract for entity synchronization implementations.
 * All entity syncers must implement this interface.
 *
 * @package LaravelShopifySdk\Sync
 */
interface EntitySyncerInterface
{
    /**
     * Sync entity data from Shopify.
     *
     * @param Store $store
     * @param array<string, mixed> $options
     * @return array<string, int> Counts of synced items
     */
    public function sync(Store $store, array $options = []): array;
}
