<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\StoreResource\Pages;

use Filament\Resources\Pages\ViewRecord;
use LaravelShopifySdk\Filament\Resources\StoreResource;
use LaravelShopifySdk\Filament\Widgets\StoreOverviewWidget;

class ViewStore extends ViewRecord
{
    protected static string $resource = StoreResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            StoreOverviewWidget::class,
        ];
    }
}
