<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\RoleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use LaravelShopifySdk\Filament\Resources\RoleResource;
use LaravelShopifySdk\Models\Permission;

class CreateRole extends CreateRecord
{
    protected static string $resource = RoleResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $permissions = $data['permissions'] ?? [];
        unset($data['permissions']);
        
        return $data;
    }

    protected function afterCreate(): void
    {
        $permissions = $this->data['permissions'] ?? [];
        
        if (!empty($permissions)) {
            $permissionIds = Permission::whereIn('slug', $permissions)->pluck('id')->toArray();
            $this->record->permissions()->sync($permissionIds);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
