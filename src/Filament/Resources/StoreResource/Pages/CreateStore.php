<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\StoreResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaravelShopifySdk\Filament\Resources\StoreResource;
use LaravelShopifySdk\Models\Store;

class CreateStore extends CreateRecord
{
    protected static string $resource = StoreResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set installed_at for new stores
        $data['installed_at'] = now();
        
        // Default mode to token if not set
        if (empty($data['mode'])) {
            $data['mode'] = Store::MODE_TOKEN;
        }

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
