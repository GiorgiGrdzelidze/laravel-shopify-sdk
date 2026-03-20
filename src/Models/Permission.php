<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Permission extends Model
{
    protected $table = 'shopify_permissions';

    protected $fillable = [
        'name',
        'slug',
        'group',
        'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(
            Role::class,
            'shopify_role_permissions',
            'permission_id',
            'role_id'
        )->withTimestamps();
    }

    public static function findBySlug(string $slug): ?self
    {
        return static::where('slug', $slug)->first();
    }

    public static function getGrouped(): array
    {
        return static::all()
            ->groupBy('group')
            ->map(fn ($permissions) => $permissions->pluck('name', 'slug')->toArray())
            ->toArray();
    }
}
