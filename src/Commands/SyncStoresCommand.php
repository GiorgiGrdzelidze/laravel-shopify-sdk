<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Commands;

use LaravelShopifySdk\Auth\StoreRepository;
use Illuminate\Console\Command;

/**
 * Sync Stores Command
 *
 * Lists all active Shopify stores.
 *
 * @package LaravelShopifySdk\Commands
 */
class SyncStoresCommand extends Command
{
    protected $signature = 'shopify:sync:stores';
    protected $description = 'List all active Shopify stores';

    public function handle(StoreRepository $repository): int
    {
        $stores = $repository->getActiveStores();

        if ($stores->isEmpty()) {
            $this->warn('No active stores found.');
            return self::SUCCESS;
        }

        $this->info("Found {$stores->count()} active store(s):");
        $this->newLine();

        $this->table(
            ['ID', 'Shop Domain', 'Status', 'Installed At'],
            $stores->map(fn($store) => [
                $store->id,
                $store->shop_domain,
                $store->status,
                $store->installed_at?->format('Y-m-d H:i:s'),
            ])
        );

        return self::SUCCESS;
    }
}
