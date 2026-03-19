<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\ProductResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaravelShopifySdk\Filament\Resources\ProductResource;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate a placeholder Shopify ID for sandbox mode
        $data['shopify_id'] = 'sandbox_' . uniqid();
        $data['payload'] = $data['payload'] ?? [];
        
        return $data;
    }
}
