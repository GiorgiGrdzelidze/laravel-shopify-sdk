<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LaravelShopifySdk\Models\Core\Store;

class Discount extends Model
{
    protected $table = 'shopify_price_rules';

    protected $fillable = [
        'store_id',
        'shopify_id',
        'title',
        'target_type',
        'target_selection',
        'allocation_method',
        'value_type',
        'value',
        'once_per_customer',
        'usage_limit',
        'customer_selection',
        'prerequisite_subtotal_range',
        'prerequisite_quantity_range',
        'prerequisite_shipping_price_range',
        'entitled_product_ids',
        'entitled_variant_ids',
        'entitled_collection_ids',
        'entitled_country_ids',
        'prerequisite_product_ids',
        'prerequisite_variant_ids',
        'prerequisite_collection_ids',
        'prerequisite_customer_ids',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'value' => 'decimal:2',
        'once_per_customer' => 'boolean',
        'usage_limit' => 'integer',
        'prerequisite_subtotal_range' => 'array',
        'prerequisite_quantity_range' => 'array',
        'prerequisite_shipping_price_range' => 'array',
        'entitled_product_ids' => 'array',
        'entitled_variant_ids' => 'array',
        'entitled_collection_ids' => 'array',
        'entitled_country_ids' => 'array',
        'prerequisite_product_ids' => 'array',
        'prerequisite_variant_ids' => 'array',
        'prerequisite_collection_ids' => 'array',
        'prerequisite_customer_ids' => 'array',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function discountCodes(): HasMany
    {
        return $this->hasMany(DiscountCode::class, 'price_rule_id');
    }

    public function isActive(): bool
    {
        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return false;
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return false;
        }

        return true;
    }

    public function getFormattedValueAttribute(): string
    {
        if ($this->value_type === 'percentage') {
            return "-{$this->value}%";
        }

        return "-\${$this->value}";
    }

    public function getStatusAttribute(): string
    {
        $now = now();

        if ($this->starts_at && $now->lt($this->starts_at)) {
            return 'scheduled';
        }

        if ($this->ends_at && $now->gt($this->ends_at)) {
            return 'expired';
        }

        return 'active';
    }
}
