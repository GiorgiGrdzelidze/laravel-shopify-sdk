<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Traits;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use LaravelShopifySdk\Models\Access\Permission;
use LaravelShopifySdk\Models\Access\Role;
use LaravelShopifySdk\Models\Core\Store;

trait HasShopifyRoles
{
    public function shopifyRoles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'shopify_user_roles',
            'user_id',
            'role_id'
        )->withTimestamps();
    }

    public function shopifyStores(): BelongsToMany
    {
        return $this->belongsToMany(
            Store::class,
            'shopify_user_stores',
            'user_id',
            'store_id'
        )->withTimestamps();
    }

    public function hasShopifyRole(string $role): bool
    {
        return $this->shopifyRoles()->where('slug', $role)->exists();
    }

    public function hasAnyShopifyRole(array $roles): bool
    {
        return $this->shopifyRoles()->whereIn('slug', $roles)->exists();
    }

    public function hasShopifyPermission(string $permission): bool
    {
        // Super admin has all permissions
        if ($this->hasShopifyRole('super-admin')) {
            return true;
        }

        return $this->shopifyRoles()
            ->whereHas('permissions', fn ($q) => $q->where('slug', $permission))
            ->exists();
    }

    public function hasAnyShopifyPermission(array $permissions): bool
    {
        if ($this->hasShopifyRole('super-admin')) {
            return true;
        }

        return $this->shopifyRoles()
            ->whereHas('permissions', fn ($q) => $q->whereIn('slug', $permissions))
            ->exists();
    }

    public function hasAllShopifyPermissions(array $permissions): bool
    {
        if ($this->hasShopifyRole('super-admin')) {
            return true;
        }

        foreach ($permissions as $permission) {
            if (!$this->hasShopifyPermission($permission)) {
                return false;
            }
        }

        return true;
    }

    public function assignShopifyRole(string|Role $role): void
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->firstOrFail();
        }

        $this->shopifyRoles()->syncWithoutDetaching([$role->id]);
    }

    public function removeShopifyRole(string|Role $role): void
    {
        if (is_string($role)) {
            $role = Role::where('slug', $role)->first();
            if (!$role) return;
        }

        $this->shopifyRoles()->detach($role->id);
    }

    public function syncShopifyRoles(array $roles): void
    {
        $roleIds = Role::whereIn('slug', $roles)->pluck('id')->toArray();
        $this->shopifyRoles()->sync($roleIds);
    }

    public function canAccessStore(int|Store $store): bool
    {
        // Super admin can access all stores
        if ($this->hasShopifyRole('super-admin')) {
            return true;
        }

        $storeId = $store instanceof Store ? $store->id : $store;

        // If user has no store restrictions, they can access all
        if ($this->shopifyStores()->count() === 0) {
            return true;
        }

        return $this->shopifyStores()->where('shopify_stores.id', $storeId)->exists();
    }

    public function getShopifyPermissions(): array
    {
        if ($this->hasShopifyRole('super-admin')) {
            return Permission::pluck('slug')->toArray();
        }

        return $this->shopifyRoles()
            ->with('permissions')
            ->get()
            ->flatMap(fn ($role) => $role->permissions->pluck('slug'))
            ->unique()
            ->values()
            ->toArray();
    }
}
