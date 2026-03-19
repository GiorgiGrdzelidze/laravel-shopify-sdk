<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\OrderResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use LaravelShopifySdk\Auth\StoreRepository;
use LaravelShopifySdk\Filament\Resources\OrderResource;
use LaravelShopifySdk\Filament\Widgets\OrderStatsWidget;
use LaravelShopifySdk\Sync\SyncRunner;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncOrders')
                ->label('Sync Orders')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function () {
                    $repository = app(StoreRepository::class);
                    $runner = app(SyncRunner::class);

                    $stores = $repository->getActiveStores();
                    $totalOrders = 0;

                    foreach ($stores as $store) {
                        $syncRun = $runner->syncOrders($store);
                        $totalOrders += $syncRun->counts['orders'] ?? 0;
                    }

                    Notification::make()
                        ->title('Orders Synced')
                        ->body("Synced {$totalOrders} orders from Shopify.")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Sync Orders')
                ->modalDescription('This will sync all orders from Shopify. This may take a few minutes.')
                ->modalSubmitActionLabel('Start Sync'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            OrderStatsWidget::class,
        ];
    }
}
