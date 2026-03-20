<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\RoleResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use LaravelShopifySdk\Filament\Resources\RoleResource;
use LaravelShopifySdk\Models\Access\Permission;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['permissions'] = $this->record->permissions()->pluck('slug')->toArray();
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);
        
        return $data;
    }

    protected function afterSave(): void
    {
        $permissions = $this->data['permissions'] ?? [];
        
        $permissionIds = Permission::whereIn('slug', $permissions)->pluck('id')->toArray();
        $this->record->permissions()->sync($permissionIds);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
