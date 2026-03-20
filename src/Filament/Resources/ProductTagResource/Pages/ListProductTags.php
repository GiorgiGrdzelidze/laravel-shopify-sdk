<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\ProductTagResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use LaravelShopifySdk\Filament\Resources\ProductTagResource;
use LaravelShopifySdk\Models\Core\Product;
use LaravelShopifySdk\Models\Core\ProductTag;
use LaravelShopifySdk\Models\Core\Store;

class ListProductTags extends ListRecords
{
    protected static string $resource = ProductTagResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),

            Actions\Action::make('sync_from_products')
                ->label('Sync from Products')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Sync Product Tags')
                ->modalDescription('This will extract all unique tags from your products and create entries for them. Continue?')
                ->action(function () {
                    $stores = Store::where('status', 'active')->get();
                    $totalCreated = 0;

                    foreach ($stores as $store) {
                        $products = Product::where('store_id', $store->id)
                            ->whereNotNull('payload')
                            ->get();

                        $allTags = collect();

                        foreach ($products as $product) {
                            $tags = $product->payload['tags'] ?? [];
                            if (is_array($tags)) {
                                $allTags = $allTags->merge($tags);
                            } elseif (is_string($tags) && !empty($tags)) {
                                $allTags = $allTags->merge(explode(', ', $tags));
                            }
                        }

                        $uniqueTags = $allTags->unique()->filter()->values();

                        foreach ($uniqueTags as $tagName) {
                            $tag = ProductTag::firstOrCreate(
                                ['store_id' => $store->id, 'name' => trim($tagName)],
                                ['products_count' => 0]
                            );

                            if ($tag->wasRecentlyCreated) {
                                $totalCreated++;
                            }

                            $tag->updateProductsCount();
                        }
                    }

                    Notification::make()
                        ->title('Product Tags Synced')
                        ->body("Created {$totalCreated} new product tag(s).")
                        ->success()
                        ->send();

                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}
