<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\ShopifyLogResource\Pages;

use Filament\Resources\Pages\ListRecords;
use LaravelShopifySdk\Filament\Resources\ShopifyLogResource;

class ListShopifyLogs extends ListRecords
{
    protected static string $resource = ShopifyLogResource::class;
}
