<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\OrderResource\Pages;

use Filament\Resources\Pages\ListRecords;
use LaravelShopifySdk\Filament\Resources\OrderResource;
use LaravelShopifySdk\Filament\Widgets\OrderStatsWidget;

class ListOrders extends ListRecords
{
    protected static string $resource = OrderResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            OrderStatsWidget::class,
        ];
    }
}
