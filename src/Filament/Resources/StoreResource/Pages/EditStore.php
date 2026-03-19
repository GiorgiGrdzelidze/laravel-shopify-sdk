<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\StoreResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use LaravelShopifySdk\Filament\Resources\StoreResource;

class EditStore extends EditRecord
{
    protected static string $resource = StoreResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
