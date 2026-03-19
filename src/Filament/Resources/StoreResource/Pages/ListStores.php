<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\StoreResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use LaravelShopifySdk\Filament\Resources\StoreResource;

class ListStores extends ListRecords
{
    protected static string $resource = StoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
