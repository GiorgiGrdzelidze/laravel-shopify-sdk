<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Shopify Customer Model
 *
 * Represents a Shopify customer with contact and order data.
 *
 * @package LaravelShopifySdk\Models
 *
 * @property int $id
 * @property int $store_id
 * @property string $shopify_id
 * @property string|null $email
 * @property string|null $first_name
 * @property string|null $last_name
 * @property string|null $state
 * @property array $payload
 * @property \Illuminate\Support\Carbon|null $shopify_created_at
 * @property \Illuminate\Support\Carbon|null $shopify_updated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Customer extends Model
{
    protected $fillable = [
        'store_id',
        'shopify_id',
        'email',
        'first_name',
        'last_name',
        'state',
        'payload',
        'shopify_created_at',
        'shopify_updated_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'shopify_created_at' => 'datetime',
        'shopify_updated_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('shopify.tables.customers', 'shopify_customers');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function getOrdersCountAttribute(): int
    {
        return (int) ($this->payload['ordersCount'] ?? $this->payload['numberOfOrders'] ?? 0);
    }

    public function getTotalSpentAttribute(): float
    {
        $totalSpent = $this->payload['totalSpent'] ?? $this->payload['amountSpent']['amount'] ?? 0;
        return (float) $totalSpent;
    }

    public function getFullNameAttribute(): string
    {
        return trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? ''));
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->payload['phone'] ?? null;
    }
}
