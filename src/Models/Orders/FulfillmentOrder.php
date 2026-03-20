<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models\Orders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelShopifySdk\Models\Core\Store;

class FulfillmentOrder extends Model
{
    protected $table = 'shopify_fulfillment_orders';

    protected $fillable = [
        'store_id',
        'shopify_id',
        'order_id',
        'order_shopify_id',
        'status',
        'request_status',
        'location_id',
        'line_items',
        'destination',
        'delivery_method',
        'fulfill_at',
        'fulfill_by',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'order_id' => 'integer',
        'location_id' => 'integer',
        'line_items' => 'array',
        'destination' => 'array',
        'delivery_method' => 'array',
        'fulfill_at' => 'datetime',
        'fulfill_by' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isClosed(): bool
    {
        return $this->status === 'closed';
    }

    public function getLineItemsCountAttribute(): int
    {
        return count($this->line_items ?? []);
    }
}
