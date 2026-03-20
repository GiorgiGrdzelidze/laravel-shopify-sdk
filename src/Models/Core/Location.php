<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shopify Location Model
 *
 * Represents a Shopify location (warehouse, store, etc.).
 *
 * @package LaravelShopifySdk\Models
 *
 * @property int $id
 * @property int $store_id
 * @property string $shopify_id
 * @property string|null $name
 * @property bool $is_active
 * @property array $payload
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Location extends Model
{
    protected $fillable = [
        'store_id',
        'shopify_id',
        'name',
        'is_active',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'is_active' => 'boolean',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('shopify.tables.locations', 'shopify_locations');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
