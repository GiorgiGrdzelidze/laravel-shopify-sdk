<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Sync;

use LaravelShopifySdk\Clients\ShopifyClient;
use LaravelShopifySdk\Models\Store;
use LaravelShopifySdk\Models\SyncRun;
use Illuminate\Support\Facades\Log;

/**
 * Sync Runner
 *
 * Orchestrates data synchronization from Shopify to local database.
 * Manages sync runs, tracks progress, and delegates to entity-specific syncers.
 *
 * @package LaravelShopifySdk\Sync
 */
class SyncRunner
{
    public function __construct(
        protected ShopifyClient $client
    ) {}

    /**
     * Sync products for a store.
     *
     * @param Store $store
     * @param array<string, mixed> $options
     * @return SyncRun
     */
    public function syncProducts(Store $store, array $options = []): SyncRun
    {
        $syncer = new ProductSyncer($this->client);
        return $this->runSync($store, 'products', $syncer, $options);
    }

    /**
     * Sync orders for a store.
     *
     * @param Store $store
     * @param array<string, mixed> $options
     * @return SyncRun
     */
    public function syncOrders(Store $store, array $options = []): SyncRun
    {
        $syncer = new OrderSyncer($this->client);
        return $this->runSync($store, 'orders', $syncer, $options);
    }

    /**
     * Sync customers for a store.
     *
     * @param Store $store
     * @param array<string, mixed> $options
     * @return SyncRun
     */
    public function syncCustomers(Store $store, array $options = []): SyncRun
    {
        $syncer = new CustomerSyncer($this->client);
        return $this->runSync($store, 'customers', $syncer, $options);
    }

    /**
     * Sync inventory for a store.
     *
     * @param Store $store
     * @param array<string, mixed> $options
     * @return SyncRun
     */
    public function syncInventory(Store $store, array $options = []): SyncRun
    {
        $syncer = new InventorySyncer($this->client);
        return $this->runSync($store, 'inventory', $syncer, $options);
    }

    /**
     * Sync collections for a store.
     *
     * @param Store $store
     * @param array<string, mixed> $options
     * @return SyncRun
     */
    public function syncCollections(Store $store, array $options = []): SyncRun
    {
        $syncer = app(CollectionSyncer::class);
        return $syncer->sync($store, $options);
    }

    /**
     * Sync discounts for a store.
     *
     * @param Store $store
     * @param array<string, mixed> $options
     * @return SyncRun
     */
    public function syncDiscounts(Store $store, array $options = []): SyncRun
    {
        $syncer = app(DiscountSyncer::class);
        return $syncer->sync($store, $options);
    }

    /**
     * Sync draft orders for a store.
     *
     * @param Store $store
     * @param array<string, mixed> $options
     * @return SyncRun
     */
    public function syncDraftOrders(Store $store, array $options = []): SyncRun
    {
        $syncer = app(DraftOrderSyncer::class);
        return $syncer->sync($store, $options);
    }

    /**
     * Sync fulfillments for a store.
     *
     * @param Store $store
     * @param array<string, mixed> $options
     * @return SyncRun
     */
    public function syncFulfillments(Store $store, array $options = []): SyncRun
    {
        $syncer = app(FulfillmentSyncer::class);
        return $syncer->sync($store, $options);
    }

    /**
     * Sync metafields for a store.
     *
     * @param Store $store
     * @param array<string, mixed> $options
     * @return SyncRun
     */
    public function syncMetafields(Store $store, array $options = []): SyncRun
    {
        $syncer = app(MetafieldSyncer::class);
        return $syncer->sync($store, $options);
    }

    /**
     * Run sync with a specific syncer.
     *
     * @param Store $store
     * @param string $entity
     * @param EntitySyncerInterface $syncer
     * @param array<string, mixed> $options
     * @return SyncRun
     */
    protected function runSync(
        Store $store,
        string $entity,
        EntitySyncerInterface $syncer,
        array $options
    ): SyncRun {
        $syncRun = SyncRun::create([
            'store_id' => $store->id,
            'entity' => $entity,
            'params' => $options,
            'started_at' => now(),
        ]);

        $startTime = microtime(true);

        try {
            Log::info("Starting {$entity} sync", [
                'store_id' => $store->id,
                'shop_domain' => $store->shop_domain,
                'sync_run_id' => $syncRun->id,
                'options' => $options,
            ]);

            $counts = $syncer->sync($store, $options);

            $duration = (int) ((microtime(true) - $startTime) * 1000);

            $syncRun->update([
                'counts' => $counts,
                'duration_ms' => $duration,
                'finished_at' => now(),
            ]);

            Log::info("Completed {$entity} sync", [
                'store_id' => $store->id,
                'sync_run_id' => $syncRun->id,
                'duration_ms' => $duration,
                'counts' => $counts,
            ]);

        } catch (\Exception $e) {
            $duration = (int) ((microtime(true) - $startTime) * 1000);

            $syncRun->update([
                'errors' => [$e->getMessage()],
                'duration_ms' => $duration,
                'finished_at' => now(),
            ]);

            Log::error("Failed {$entity} sync", [
                'store_id' => $store->id,
                'sync_run_id' => $syncRun->id,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }

        return $syncRun;
    }
}
