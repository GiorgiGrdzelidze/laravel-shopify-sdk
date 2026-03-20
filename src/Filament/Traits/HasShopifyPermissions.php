<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Traits;

use Illuminate\Database\Eloquent\Model;

trait HasShopifyPermissions
{
    protected static function checkPermission(string $permission): bool
    {
        $user = auth()->user();
        
        if (!$user) {
            return false;
        }

        // If user doesn't have the trait, allow access (backward compatibility)
        if (!method_exists($user, 'hasShopifyPermission')) {
            return true;
        }

        return $user->hasShopifyPermission($permission);
    }

    public static function canViewAny(): bool
    {
        return static::checkPermission(static::getPermissionPrefix() . '.view');
    }

    public static function canView(Model $record): bool
    {
        return static::checkPermission(static::getPermissionPrefix() . '.view');
    }

    public static function canCreate(): bool
    {
        return static::checkPermission(static::getPermissionPrefix() . '.create');
    }

    public static function canEdit(Model $record): bool
    {
        return static::checkPermission(static::getPermissionPrefix() . '.edit');
    }

    public static function canDelete(Model $record): bool
    {
        return static::checkPermission(static::getPermissionPrefix() . '.delete');
    }

    public static function canDeleteAny(): bool
    {
        return static::checkPermission(static::getPermissionPrefix() . '.delete');
    }

    protected static function getPermissionPrefix(): string
    {
        // Override in each resource
        return 'products';
    }
}
