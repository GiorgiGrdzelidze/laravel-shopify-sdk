<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\ProductTypeResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaravelShopifySdk\Filament\Resources\ProductTypeResource;
use LaravelShopifySdk\Models\Core\Store;

class CreateProductType extends CreateRecord
{
    protected static string $resource = ProductTypeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Auto-fill store_id if only one store exists
        if (empty($data['store_id'])) {
            $store = Store::where('status', 'active')->first();
            if ($store) {
                $data['store_id'] = $store->id;
            }
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
