<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\UserResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use LaravelShopifySdk\Filament\Resources\UserResource;
use LaravelShopifySdk\Models\Access\Role;
use LaravelShopifySdk\Models\Core\Store;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Set a random password for new users
        $data['password'] = Hash::make(str()->random(16));
        
        return $data;
    }

    protected function afterCreate(): void
    {
        // Sync roles
        $roleIds = $this->data['shopify_roles'] ?? [];
        if (!empty($roleIds) && method_exists($this->record, 'shopifyRoles')) {
            $this->record->shopifyRoles()->sync($roleIds);
        }

        // Sync stores
        $storeIds = $this->data['shopify_stores'] ?? [];
        if (!empty($storeIds) && method_exists($this->record, 'shopifyStores')) {
            $this->record->shopifyStores()->sync($storeIds);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
