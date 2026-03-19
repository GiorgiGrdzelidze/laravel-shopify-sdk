<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Commands;

use LaravelShopifySdk\Auth\StoreRepository;
use LaravelShopifySdk\Sync\SyncRunner;
use Illuminate\Console\Command;

/**
 * Sync Products Command
 *
 * Syncs products from Shopify stores with optional filters.
 *
 * @package LaravelShopifySdk\Commands
 */
class SyncProductsCommand extends Command
{
    protected $signature = 'shopify:sync:products
                            {--store= : Shop domain to sync}
                            {--since= : Sync products updated since this date}
                            {--dry-run : Show what would be synced without syncing}
                            {--queue : Dispatch sync as a job}';

    protected $description = 'Sync products from Shopify';

    public function handle(StoreRepository $repository, SyncRunner $runner): int
    {
        $storeDomain = $this->option('store');
        $since = $this->option('since');
        $dryRun = $this->option('dry-run');

        $stores = $storeDomain
            ? collect([$repository->findByDomain($storeDomain)])->filter()
            : $repository->getActiveStores();

        if ($stores->isEmpty()) {
            $this->error('No stores found to sync.');
            return self::FAILURE;
        }

        $this->info("Syncing products for {$stores->count()} store(s)...");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be synced');
        }

        $options = array_filter([
            'since' => $since,
        ]);

        foreach ($stores as $store) {
            $this->info("Syncing products for: {$store->shop_domain}");

            if ($dryRun) {
                $this->line("  Would sync with options: " . json_encode($options));
                continue;
            }

            try {
                $syncRun = $runner->syncProducts($store, $options);

                $this->info("  ✓ Synced {$syncRun->counts['products']} products, {$syncRun->counts['variants']} variants");
                $this->line("  Duration: {$syncRun->duration_ms}ms");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
