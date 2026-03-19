<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Commands;

use LaravelShopifySdk\Auth\StoreRepository;
use LaravelShopifySdk\Sync\SyncRunner;
use Illuminate\Console\Command;

/**
 * Sync All Command
 *
 * Syncs all entities from Shopify stores (products, orders, customers, inventory).
 *
 * @package LaravelShopifySdk\Commands
 */
class SyncAllCommand extends Command
{
    protected $signature = 'shopify:sync:all
                            {--store= : Shop domain to sync}
                            {--since= : Sync data updated since this date}
                            {--date-from= : Sync orders created from this date}
                            {--date-to= : Sync orders created until this date}
                            {--dry-run : Show what would be synced without syncing}
                            {--queue : Dispatch sync as a job}';

    protected $description = 'Sync all data from Shopify';

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

        $this->info("Syncing all data for {$stores->count()} store(s)...");

        if ($dryRun) {
            $this->warn('DRY RUN MODE - No data will be synced');
        }

        foreach ($stores as $store) {
            $this->info("Syncing all data for: {$store->shop_domain}");
            $this->newLine();

            if ($dryRun) {
                $this->line("  Would sync products, orders, customers, and inventory");
                continue;
            }

            try {
                $this->line('  Syncing products...');
                $productRun = $runner->syncProducts($store, ['since' => $since]);
                $this->info("    ✓ {$productRun->counts['products']} products, {$productRun->counts['variants']} variants ({$productRun->duration_ms}ms)");

                $this->line('  Syncing orders...');
                $orderRun = $runner->syncOrders($store, array_filter([
                    'since' => $since,
                    'date_from' => $dateFrom,
                    'date_to' => $dateTo,
                ]));
                $this->info("    ✓ {$orderRun->counts['orders']} orders, {$orderRun->counts['line_items']} line items ({$orderRun->duration_ms}ms)");

                $this->line('  Syncing customers...');
                $customerRun = $runner->syncCustomers($store, ['since' => $since]);
                $this->info("    ✓ {$customerRun->counts['customers']} customers ({$customerRun->duration_ms}ms)");

                $this->line('  Syncing inventory...');
                $inventoryRun = $runner->syncInventory($store, ['since' => $since]);
                $this->info("    ✓ {$inventoryRun->counts['locations']} locations, {$inventoryRun->counts['inventory_levels']} inventory levels ({$inventoryRun->duration_ms}ms)");

                $this->newLine();
                $this->info("✓ Completed sync for {$store->shop_domain}");
            } catch (\Exception $e) {
                $this->error("  ✗ Failed: {$e->getMessage()}");
            }

            $this->newLine();
        }

        return self::SUCCESS;
    }
}
