<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\ProductResource\Pages;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use LaravelShopifySdk\Auth\StoreRepository;
use LaravelShopifySdk\Filament\Resources\ProductResource;
use LaravelShopifySdk\Filament\Widgets\ProductStatsWidget;
use LaravelShopifySdk\Sync\SyncRunner;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->label('Create Product')
                ->icon('heroicon-o-plus'),

            Action::make('syncProducts')
                ->label('Sync Products')
                ->icon('heroicon-o-cube')
                ->color('primary')
                ->action(function () {
                    $repository = app(StoreRepository::class);
                    $runner = app(SyncRunner::class);

                    $stores = $repository->getActiveStores();
                    $totalProducts = 0;

                    foreach ($stores as $store) {
                        $syncRun = $runner->syncProducts($store);
                        $totalProducts += $syncRun->counts['products'] ?? 0;
                    }

                    Notification::make()
                        ->title('Products Synced')
                        ->body("Synced {$totalProducts} products from Shopify.")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Sync Products')
                ->modalDescription('This will sync all products and variants from Shopify.')
                ->modalSubmitActionLabel('Start Sync'),

            Action::make('syncInventory')
                ->label('Sync Inventory')
                ->icon('heroicon-o-archive-box')
                ->color('success')
                ->action(function () {
                    $repository = app(StoreRepository::class);
                    $runner = app(SyncRunner::class);

                    $stores = $repository->getActiveStores();
                    $totalLevels = 0;

                    foreach ($stores as $store) {
                        $syncRun = $runner->syncInventory($store);
                        $totalLevels += $syncRun->counts['inventory_levels'] ?? 0;
                    }

                    Notification::make()
                        ->title('Inventory Synced')
                        ->body("Synced {$totalLevels} inventory levels from Shopify.")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Sync Inventory')
                ->modalDescription('This will sync inventory levels for all locations.')
                ->modalSubmitActionLabel('Start Sync'),

            Action::make('syncAll')
                ->label('Sync All')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(function () {
                    $repository = app(StoreRepository::class);
                    $runner = app(SyncRunner::class);

                    $stores = $repository->getActiveStores();
                    $totalProducts = 0;
                    $totalLevels = 0;

                    foreach ($stores as $store) {
                        $productRun = $runner->syncProducts($store);
                        $totalProducts += $productRun->counts['products'] ?? 0;

                        $inventoryRun = $runner->syncInventory($store);
                        $totalLevels += $inventoryRun->counts['inventory_levels'] ?? 0;
                    }

                    Notification::make()
                        ->title('Full Sync Complete')
                        ->body("Synced {$totalProducts} products and {$totalLevels} inventory levels.")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Sync Products & Inventory')
                ->modalDescription('This will sync all products and inventory from Shopify. This may take several minutes.')
                ->modalSubmitActionLabel('Start Full Sync'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ProductStatsWidget::class,
        ];
    }
}
