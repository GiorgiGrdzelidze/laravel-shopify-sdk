<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shopify Inventory Level Model
 *
 * Represents inventory quantities at specific locations.
 *
 * @package LaravelShopifySdk\Models
 *
 * @property int $id
 * @property int $store_id
 * @property string $inventory_item_id
 * @property string $location_id
 * @property int|null $available
 * @property array $payload
 * @property \Illuminate\Support\Carbon|null $shopify_updated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class InventoryLevel extends Model
{
    protected $fillable = [
        'store_id',
        'inventory_item_id',
        'location_id',
        'available',
        'payload',
        'shopify_updated_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'available' => 'integer',
        'shopify_updated_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('shopify.tables.inventory_levels', 'shopify_inventory_levels');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
