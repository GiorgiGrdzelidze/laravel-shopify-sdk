<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Commands;

use Illuminate\Console\Command;
use LaravelShopifySdk\Auth\StoreRepository;
use LaravelShopifySdk\Sync\CollectionSyncer;

class SyncCollectionsCommand extends Command
{
    protected $signature = 'shopify:sync:collections
                            {--store= : Specific store domain to sync}
                            {--dry-run : Preview without syncing}';

    protected $description = 'Sync collections from Shopify';

    public function handle(StoreRepository $repository, CollectionSyncer $syncer): int
    {
        $storeDomain = $this->option('store');
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->info('🔍 Dry run mode - no changes will be made');
        }

        $stores = $storeDomain
            ? [$repository->findByDomain($storeDomain)]
            : $repository->getActiveStores();

        if (empty($stores) || (count($stores) === 1 && $stores[0] === null)) {
            $this->error('No stores found to sync.');
            return self::FAILURE;
        }

        foreach ($stores as $store) {
            if (!$store) continue;

            $this->info("📦 Syncing collections for {$store->shop_domain}...");

            if ($dryRun) {
                $this->line('  Would sync all collections');
                continue;
            }

            $syncRun = $syncer->sync($store);

            if ($syncRun->status === 'completed') {
                $counts = $syncRun->counts ?? [];
                $this->info("  ✅ Synced {$counts['total']} collections");
                $this->line("     Created: {$counts['created']}, Updated: {$counts['updated']}");
                $this->line("     Duration: {$syncRun->duration_ms}ms");
            } else {
                $this->error("  ❌ Sync failed: " . implode(', ', $syncRun->errors ?? []));
            }
        }

        $this->newLine();
        $this->info('✅ Collection sync complete!');

        return self::SUCCESS;
    }
}
