<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\FulfillmentResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use LaravelShopifySdk\Filament\Resources\FulfillmentResource;
use LaravelShopifySdk\Models\Core\Store;
use LaravelShopifySdk\Sync\FulfillmentSyncer;

class ListFulfillments extends ListRecords
{
    protected static string $resource = FulfillmentResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sync_fulfillments')
                ->label('Sync Fulfillments')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sync Fulfillments')
                ->modalDescription('This will sync all fulfillments from Shopify. Continue?')
                ->action(function () {
                    $syncer = app(FulfillmentSyncer::class);
                    $stores = Store::where('status', 'active')->get();

                    $total = 0;
                    foreach ($stores as $store) {
                        $syncRun = $syncer->sync($store);
                        $total += $syncRun->counts['total'] ?? 0;
                    }

                    Notification::make()
                        ->title('Fulfillments Synced')
                        ->body("Synced {$total} fulfillments from Shopify.")
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}
