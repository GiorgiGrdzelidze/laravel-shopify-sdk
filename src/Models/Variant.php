<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelShopifySdk\Models\InventoryLevel;

/**
 * Shopify Variant Model
 *
 * Represents a product variant with pricing and inventory data.
 *
 * @package LaravelShopifySdk\Models
 *
 * @property int $id
 * @property int $store_id
 * @property int $product_id
 * @property string $shopify_id
 * @property string|null $sku
 * @property string|null $barcode
 * @property string|null $price
 * @property string|null $inventory_item_id
 * @property array $payload
 * @property \Illuminate\Support\Carbon|null $shopify_updated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Variant extends Model
{
    protected $fillable = [
        'store_id',
        'product_id',
        'shopify_id',
        'sku',
        'barcode',
        'price',
        'inventory_item_id',
        'payload',
        'shopify_updated_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'shopify_updated_at' => 'datetime',
    ];

    protected $appends = [
        'title',
        'image_url',
        'compare_at_price',
        'inventory_quantity',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('shopify.tables.variants', 'shopify_variants');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the variant's image URL from payload
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->payload['image']['url'] ?? null;
    }

    /**
     * Get the variant's title from payload
     */
    public function getTitleAttribute(): ?string
    {
        return $this->payload['title'] ?? $this->payload['displayName'] ?? null;
    }

    /**
     * Get the variant's compare at price from payload
     */
    public function getCompareAtPriceAttribute(): ?string
    {
        return $this->payload['compareAtPrice'] ?? null;
    }

    /**
     * Get the variant's inventory quantity from payload or inventory_levels table
     * Falls back to summing inventory levels if direct quantity not available
     */
    public function getInventoryQuantityAttribute(): ?int
    {
        // First try direct inventoryQuantity from payload
        if (isset($this->payload['inventoryQuantity'])) {
            return (int) $this->payload['inventoryQuantity'];
        }

        // Try to sum from inventory levels in payload (inventoryItem)
        $inventoryLevels = $this->payload['inventoryItem']['inventoryLevels']['edges'] ?? [];
        if (!empty($inventoryLevels)) {
            $total = 0;
            foreach ($inventoryLevels as $edge) {
                $quantities = $edge['node']['quantities'] ?? [];
                foreach ($quantities as $qty) {
                    $name = $qty['name'] ?? 'available';
                    if ($name === 'available') {
                        $total += (int) ($qty['quantity'] ?? 0);
                    }
                }
            }
            return $total;
        }

        // Fall back to inventory_levels table if we have inventory_item_id
        if ($this->inventory_item_id) {
            $total = InventoryLevel::where('store_id', $this->store_id)
                ->where('inventory_item_id', $this->inventory_item_id)
                ->sum('available');

            return $total > 0 ? (int) $total : null;
        }

        return null;
    }

    /**
     * Get the variant's weight from payload
     */
    public function getWeightAttribute(): ?float
    {
        return $this->payload['weight'] ?? null;
    }

    /**
     * Get the variant's weight unit from payload
     */
    public function getWeightUnitAttribute(): ?string
    {
        return $this->payload['weightUnit'] ?? null;
    }

    /**
     * Check if variant requires shipping from payload
     */
    public function getRequiresShippingAttribute(): ?bool
    {
        return $this->payload['requiresShipping'] ?? null;
    }

    /**
     * Check if variant is taxable from payload
     */
    public function getTaxableAttribute(): ?bool
    {
        return $this->payload['taxable'] ?? null;
    }
}
