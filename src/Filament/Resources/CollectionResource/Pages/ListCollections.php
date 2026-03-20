<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\CollectionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use LaravelShopifySdk\Filament\Resources\CollectionResource;
use LaravelShopifySdk\Sync\CollectionSyncer;
use Filament\Notifications\Notification;

class ListCollections extends ListRecords
{
    protected static string $resource = CollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('sync_collections')
                ->label('Sync Collections')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sync Collections')
                ->modalDescription('This will sync all collections from Shopify. Continue?')
                ->action(function () {
                    $syncer = app(CollectionSyncer::class);
                    $stores = \LaravelShopifySdk\Models\Store::where('status', 'active')->get();

                    $totalCollections = 0;
                    foreach ($stores as $store) {
                        $syncRun = $syncer->sync($store);
                        $totalCollections += $syncRun->counts['total'] ?? 0;
                    }

                    Notification::make()
                        ->title('Collections Synced')
                        ->body("Synced {$totalCollections} collections from Shopify.")
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}
