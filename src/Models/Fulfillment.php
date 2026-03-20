<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Fulfillment extends Model
{
    protected $table = 'shopify_fulfillments';

    protected $fillable = [
        'store_id',
        'shopify_id',
        'order_id',
        'order_shopify_id',
        'status',
        'name',
        'tracking_company',
        'tracking_number',
        'tracking_numbers',
        'tracking_urls',
        'line_items',
        'location_id',
        'shipment_status',
        'created_at_shopify',
        'updated_at_shopify',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'order_id' => 'integer',
        'location_id' => 'integer',
        'tracking_numbers' => 'array',
        'tracking_urls' => 'array',
        'line_items' => 'array',
        'created_at_shopify' => 'datetime',
        'updated_at_shopify' => 'datetime',
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

    public function events(): HasMany
    {
        return $this->hasMany(FulfillmentEvent::class);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isSuccess(): bool
    {
        return $this->status === 'success';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function getTrackingUrlAttribute(): ?string
    {
        $urls = $this->tracking_urls ?? [];
        return $urls[0] ?? null;
    }
}
