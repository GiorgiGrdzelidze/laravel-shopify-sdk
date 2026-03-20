<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\MetafieldResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use LaravelShopifySdk\Filament\Resources\MetafieldResource;
use LaravelShopifySdk\Models\Store;
use LaravelShopifySdk\Sync\MetafieldSyncer;

class ListMetafields extends ListRecords
{
    protected static string $resource = MetafieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('sync_metafields')
                ->label('Sync Metafields')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sync Metafields')
                ->modalDescription('This will sync all product metafields from Shopify. Continue?')
                ->action(function () {
                    $syncer = app(MetafieldSyncer::class);
                    $stores = Store::where('status', 'active')->get();

                    $total = 0;
                    foreach ($stores as $store) {
                        $syncRun = $syncer->sync($store);
                        $total += $syncRun->counts['total'] ?? 0;
                    }

                    Notification::make()
                        ->title('Metafields Synced')
                        ->body("Synced {$total} metafields from Shopify.")
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}
