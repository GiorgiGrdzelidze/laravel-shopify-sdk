<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\CustomerResource\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use LaravelShopifySdk\Auth\StoreRepository;
use LaravelShopifySdk\Filament\Resources\CustomerResource;
use LaravelShopifySdk\Filament\Widgets\CustomerStatsWidget;
use LaravelShopifySdk\Sync\SyncRunner;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('syncCustomers')
                ->label('Sync Customers')
                ->icon('heroicon-o-arrow-path')
                ->color('primary')
                ->action(function () {
                    $repository = app(StoreRepository::class);
                    $runner = app(SyncRunner::class);

                    $stores = $repository->getActiveStores();
                    $totalCustomers = 0;

                    foreach ($stores as $store) {
                        $syncRun = $runner->syncCustomers($store);
                        $totalCustomers += $syncRun->counts['customers'] ?? 0;
                    }

                    Notification::make()
                        ->title('Customers Synced')
                        ->body("Synced {$totalCustomers} customers from Shopify.")
                        ->success()
                        ->send();
                })
                ->requiresConfirmation()
                ->modalHeading('Sync Customers')
                ->modalDescription('This will sync all customers from Shopify. This may take a few minutes.')
                ->modalSubmitActionLabel('Start Sync'),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            CustomerStatsWidget::class,
        ];
    }
}
