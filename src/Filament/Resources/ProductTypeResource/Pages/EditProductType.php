<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\ProductTypeResource\Pages;

use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Filament\Resources\ProductTypeResource;
use LaravelShopifySdk\Models\Core\Product;
use LaravelShopifySdk\Models\Core\ProductType;
use LaravelShopifySdk\Services\ProductTypeService;

class EditProductType extends EditRecord
{
    protected static string $resource = ProductTypeResource::class;

    protected ?string $originalName = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $this->originalName = $data['name'] ?? null;
        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('assign_to_products')
                ->label('Assign to Products')
                ->icon('heroicon-o-plus-circle')
                ->color('info')
                ->form([
                    Select::make('product_ids')
                        ->label('Select Products')
                        ->options(fn () => Product::where('store_id', $this->record->store_id)
                            ->whereNotNull('shopify_id')
                            ->where('shopify_id', 'not like', 'local_%')
                            ->pluck('title', 'id'))
                        ->multiple()
                        ->searchable()
                        ->preload()
                        ->required()
                        ->helperText('Select products to set this type on'),
                ])
                ->action(function (array $data) {
                    try {
                        $service = new ProductTypeService(new GraphQLClient());
                        $products = Product::whereIn('id', $data['product_ids'])->get();
                        $count = 0;

                        foreach ($products as $product) {
                            $service->setProductType($product, $this->record->name);
                            $count++;
                        }

                        // Update products count
                        $this->record->updateProductsCount();

                        Notification::make()
                            ->title('Type Assigned')
                            ->body("Set type on {$count} product(s) on Shopify.")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Assignment Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\Action::make('push_to_shopify')
                ->label('Rename on Shopify')
                ->icon('heroicon-o-arrow-up-tray')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Rename Type on Shopify')
                ->modalDescription('This will rename this type on all products that have it. Use this after changing the type name.')
                ->action(function () {
                    try {
                        $service = new ProductTypeService(new GraphQLClient());
                        $count = $service->pushToShopify($this->record, $this->originalName);

                        Notification::make()
                            ->title('Type Renamed')
                            ->body("Updated {$count} product(s) on Shopify.")
                            ->success()
                            ->send();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Rename Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\DeleteAction::make()
                ->requiresConfirmation(),
        ];
    }

    protected function afterSave(): void
    {
        // Update the original name after save for subsequent pushes
        $this->originalName = $this->record->name;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
