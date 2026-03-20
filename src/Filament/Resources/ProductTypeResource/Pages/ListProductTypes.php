<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\ProductTypeResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use LaravelShopifySdk\Filament\Resources\ProductTypeResource;
use LaravelShopifySdk\Models\Core\Product;
use LaravelShopifySdk\Models\Core\ProductType;
use LaravelShopifySdk\Models\Core\Store;

class ListProductTypes extends ListRecords
{
    protected static string $resource = ProductTypeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('sync_from_products')
                ->label('Sync from Products')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sync Product Types')
                ->modalDescription('This will extract all unique product types from your products and create entries for them. Continue?')
                ->action(function () {
                    $stores = Store::where('status', 'active')->get();
                    $totalCreated = 0;

                    foreach ($stores as $store) {
                        $types = Product::where('store_id', $store->id)
                            ->whereNotNull('product_type')
                            ->where('product_type', '!=', '')
                            ->distinct()
                            ->pluck('product_type');

                        foreach ($types as $typeName) {
                            $type = ProductType::firstOrCreate(
                                ['store_id' => $store->id, 'name' => $typeName],
                                ['products_count' => 0]
                            );

                            if ($type->wasRecentlyCreated) {
                                $totalCreated++;
                            }

                            $type->updateProductsCount();
                        }
                    }

                    Notification::make()
                        ->title('Product Types Synced')
                        ->body("Created {$totalCreated} new product type(s).")
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}
