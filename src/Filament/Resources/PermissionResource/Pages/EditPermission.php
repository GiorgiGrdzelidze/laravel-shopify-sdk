<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\PermissionResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use LaravelShopifySdk\Filament\Resources\PermissionResource;

class EditPermission extends EditRecord
{
    protected static string $resource = PermissionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
