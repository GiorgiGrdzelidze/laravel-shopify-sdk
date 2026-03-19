<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shopify Order Line Model
 *
 * Represents an individual line item within an order.
 *
 * @package LaravelShopifySdk\Models
 *
 * @property int $id
 * @property int $store_id
 * @property int $order_id
 * @property string $shopify_id
 * @property string|null $product_id
 * @property string|null $variant_id
 * @property string|null $title
 * @property int|null $quantity
 * @property string|null $price
 * @property array $payload
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class OrderLine extends Model
{
    protected $fillable = [
        'store_id',
        'order_id',
        'shopify_id',
        'product_id',
        'variant_id',
        'title',
        'quantity',
        'price',
        'payload',
    ];

    protected $casts = [
        'payload' => 'array',
        'quantity' => 'integer',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('shopify.tables.order_lines', 'shopify_order_lines');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(Variant::class, 'variant_id');
    }
}
