<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\CustomerResource\Pages;

use Filament\Resources\Pages\ListRecords;
use LaravelShopifySdk\Filament\Resources\CustomerResource;
use LaravelShopifySdk\Filament\Widgets\CustomerStatsWidget;

class ListCustomers extends ListRecords
{
    protected static string $resource = CustomerResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CustomerStatsWidget::class,
        ];
    }
}
