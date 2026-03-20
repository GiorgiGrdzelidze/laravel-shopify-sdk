<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\ProductTagResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaravelShopifySdk\Filament\Resources\ProductTagResource;
use LaravelShopifySdk\Models\Core\Store;

class CreateProductTag extends CreateRecord
{
    protected static string $resource = ProductTagResource::class;

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
