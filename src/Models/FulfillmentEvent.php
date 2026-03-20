<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FulfillmentEvent extends Model
{
    protected $table = 'shopify_fulfillment_events';

    protected $fillable = [
        'fulfillment_id',
        'shopify_id',
        'status',
        'message',
        'city',
        'province',
        'country',
        'zip',
        'latitude',
        'longitude',
        'happened_at',
    ];

    protected $casts = [
        'fulfillment_id' => 'integer',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
        'happened_at' => 'datetime',
    ];

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(Fulfillment::class);
    }

    public function getLocationAttribute(): string
    {
        $parts = array_filter([
            $this->city,
            $this->province,
            $this->country,
        ]);
        
        return implode(', ', $parts);
    }
}
