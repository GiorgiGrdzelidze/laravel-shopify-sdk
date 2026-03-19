<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Shopify Order Model
 *
 * Represents a Shopify order with line items and customer data.
 *
 * @package LaravelShopifySdk\Models
 *
 * @property int $id
 * @property int $store_id
 * @property string $shopify_id
 * @property string|null $name
 * @property string|null $order_number
 * @property string|null $email
 * @property string|null $financial_status
 * @property string|null $fulfillment_status
 * @property string|null $total_price
 * @property string|null $currency
 * @property array $payload
 * @property \Illuminate\Support\Carbon|null $processed_at
 * @property \Illuminate\Support\Carbon|null $shopify_updated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Order extends Model
{
    protected $fillable = [
        'store_id',
        'shopify_id',
        'name',
        'order_number',
        'email',
        'financial_status',
        'fulfillment_status',
        'total_price',
        'currency',
        'payload',
        'processed_at',
        'shopify_updated_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'processed_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('shopify.tables.orders', 'shopify_orders');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function lineItems(): HasMany
    {
        return $this->hasMany(OrderLine::class, 'order_id');
    }
}
