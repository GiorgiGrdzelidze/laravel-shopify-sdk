<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\ProductResource\Pages;

use Filament\Resources\Pages\ListRecords;
use LaravelShopifySdk\Filament\Resources\ProductResource;
use LaravelShopifySdk\Filament\Widgets\ProductStatsWidget;

class ListProducts extends ListRecords
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            ProductStatsWidget::class,
        ];
    }
}
