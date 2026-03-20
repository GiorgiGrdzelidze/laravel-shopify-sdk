<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\DiscountResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use LaravelShopifySdk\Filament\Resources\DiscountResource;
use LaravelShopifySdk\Models\Store;
use LaravelShopifySdk\Sync\DiscountSyncer;

class ListDiscounts extends ListRecords
{
    protected static string $resource = DiscountResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('sync_discounts')
                ->label('Sync Discounts')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sync Discounts')
                ->modalDescription('This will sync all discounts from Shopify. Continue?')
                ->action(function () {
                    $syncer = app(DiscountSyncer::class);
                    $stores = Store::where('status', 'active')->get();

                    $totalDiscounts = 0;
                    foreach ($stores as $store) {
                        $syncRun = $syncer->sync($store);
                        $totalDiscounts += $syncRun->counts['total'] ?? 0;
                    }

                    Notification::make()
                        ->title('Discounts Synced')
                        ->body("Synced {$totalDiscounts} discounts from Shopify.")
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}
