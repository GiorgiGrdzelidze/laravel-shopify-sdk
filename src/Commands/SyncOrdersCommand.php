<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Commands;

use LaravelShopifySdk\Auth\StoreRepository;
use LaravelShopifySdk\Sync\SyncRunner;
use Illuminate\Console\Command;

/**
 * Sync Orders Command
 *
 * Syncs orders from Shopify stores with date range filtering.
 *
 * @package LaravelShopifySdk\Commands
 */
class SyncOrdersCommand extends Command
{
    protected $signature = 'shopify:sync:orders
                            {--store= : Shop domain to sync}
                            {--since= : Sync orders updated since this date}
                            {--date-from= : Sync orders created from this date}
                            {--date-to= : Sync orders created until this date}
                            {--dry-run : Show what would be synced without syncing}
                            {--queue : Dispatch sync as a job}';

    protected $description = 'Sync orders from Shopify';

    public function handle(StoreRepository $repository, SyncRunner $runner): int
    {
        $storeDomain = $this->option('store');
        $since = $this->option('since');
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');
        $dryRun = $this->option('dry-run');

        $stores = $storeDomain
            ? collect([$repository->findByDomain($storeDomain)])->filter()
            : $repository->getActiveStores();

        if ($stores->isEmpty()) {
            $this->error('No stores found to sync.');
            return self::FAILURE;
        }

        $this->info("Syncing orders for {$stores->count()} store(s)...");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be synced');
        }

        $options = array_filter([
            'since' => $since,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
        ]);

        foreach ($stores as $store) {
            $this->info("Syncing orders for: {$store->shop_domain}");

            if ($dryRun) {
                $this->line("  Would sync with options: " . json_encode($options));
                continue;
            }

            try {
                $syncRun = $runner->syncOrders($store, $options);

                $this->info("  ✓ Synced {$syncRun->counts['orders']} orders, {$syncRun->counts['line_items']} line items");
                $this->line("  Duration: {$syncRun->duration_ms}ms");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$e->getMessage()}");
            }
        }

        return self::SUCCESS;
    }
}
