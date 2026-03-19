<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\OrderResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaravelShopifySdk\Filament\Resources\OrderResource;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate a placeholder Shopify ID for sandbox mode
        $data['shopify_id'] = 'sandbox_' . uniqid();
        $data['payload'] = $data['payload'] ?? [];
        $data['processed_at'] = now();
        
        return $data;
    }
}
