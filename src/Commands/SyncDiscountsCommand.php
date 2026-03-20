<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Commands;

use Illuminate\Console\Command;
use LaravelShopifySdk\Models\Core\Store;
use LaravelShopifySdk\Sync\DiscountSyncer;

class SyncDiscountsCommand extends Command
{
    protected $signature = 'shopify:sync:discounts
                            {--store= : Specific store domain to sync}
                            {--dry-run : Preview without syncing}';

    protected $description = 'Sync discounts from Shopify';

    public function handle(DiscountSyncer $syncer): int
    {
        $stores = $this->getStores();

        if ($stores->isEmpty()) {
            $this->error('No active stores found.');
            return self::FAILURE;
        }

        foreach ($stores as $store) {
            $this->info("Syncing discounts for {$store->shop_domain}...");

            if ($this->option('dry-run')) {
                $this->warn('Dry run - no changes will be made.');
                continue;
            }

            $syncRun = $syncer->sync($store);

            if (empty($syncRun->errors)) {
                $counts = $syncRun->counts ?? [];
                $this->info("✓ Synced {$counts['total']} discounts ({$counts['created']} created, {$counts['updated']} updated, {$counts['codes']} codes)");
            } else {
                $this->error("✗ Failed: " . implode(', ', $syncRun->errors));
            }
        }

        return self::SUCCESS;
    }

    protected function getStores()
    {
        $query = Store::where('status', 'active');

        if ($domain = $this->option('store')) {
            $query->where('shop_domain', $domain);
        }

        return $query->get();
    }
}
