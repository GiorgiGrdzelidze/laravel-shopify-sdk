<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\UserResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use LaravelShopifySdk\Filament\Resources\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load current roles
        if (method_exists($this->record, 'shopifyRoles')) {
            $data['shopify_roles'] = $this->record->shopifyRoles()->pluck('shopify_roles.id')->toArray();
        }

        // Load current stores
        if (method_exists($this->record, 'shopifyStores')) {
            $data['shopify_stores'] = $this->record->shopifyStores()->pluck('shopify_stores.id')->toArray();
        }

        return $data;
    }

    protected function afterSave(): void
    {
        // Sync roles
        $roleIds = $this->data['shopify_roles'] ?? [];
        if (method_exists($this->record, 'shopifyRoles')) {
            $this->record->shopifyRoles()->sync($roleIds);
        }

        // Sync stores
        $storeIds = $this->data['shopify_stores'] ?? [];
        if (method_exists($this->record, 'shopifyStores')) {
            $this->record->shopifyStores()->sync($storeIds);
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
