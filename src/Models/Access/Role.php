<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models\Access;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Role extends Model
{
    protected $table = 'shopify_roles';

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_default',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    public function permissions(): BelongsToMany
    {
        return $this->belongsToMany(
            Permission::class,
            'shopify_role_permissions',
            'role_id',
            'permission_id'
        )->withTimestamps();
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            config('auth.providers.users.model', 'App\\Models\\User'),
            'shopify_user_roles',
            'role_id',
            'user_id'
        )->withTimestamps();
    }

    public function hasPermission(string $permission): bool
    {
        return $this->permissions()->where('slug', $permission)->exists();
    }

    public function givePermission(string|Permission $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('slug', $permission)->firstOrFail();
        }

        $this->permissions()->syncWithoutDetaching([$permission->id]);
    }

    public function revokePermission(string|Permission $permission): void
    {
        if (is_string($permission)) {
            $permission = Permission::where('slug', $permission)->first();
            if (!$permission) return;
        }

        $this->permissions()->detach($permission->id);
    }

    public function syncPermissions(array $permissions): void
    {
        $permissionIds = Permission::whereIn('slug', $permissions)->pluck('id')->toArray();
        $this->permissions()->sync($permissionIds);
    }
}
