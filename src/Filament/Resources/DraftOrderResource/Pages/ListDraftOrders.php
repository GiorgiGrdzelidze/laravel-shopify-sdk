<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\DraftOrderResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use LaravelShopifySdk\Filament\Resources\DraftOrderResource;
use LaravelShopifySdk\Models\Core\Store;
use LaravelShopifySdk\Sync\DraftOrderSyncer;

class ListDraftOrders extends ListRecords
{
    protected static string $resource = DraftOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('sync_draft_orders')
                ->label('Sync Draft Orders')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sync Draft Orders')
                ->modalDescription('This will sync all draft orders from Shopify. Continue?')
                ->action(function () {
                    $syncer = app(DraftOrderSyncer::class);
                    $stores = Store::where('status', 'active')->get();

                    $total = 0;
                    foreach ($stores as $store) {
                        $syncRun = $syncer->sync($store);
                        $total += $syncRun->counts['total'] ?? 0;
                    }

                    Notification::make()
                        ->title('Draft Orders Synced')
                        ->body("Synced {$total} draft orders from Shopify.")
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}
