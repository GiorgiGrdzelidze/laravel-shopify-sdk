<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\ProductResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use LaravelShopifySdk\Filament\Resources\ProductResource;

class ViewProduct extends ViewRecord
{
    protected static string $resource = ProductResource::class;
}
