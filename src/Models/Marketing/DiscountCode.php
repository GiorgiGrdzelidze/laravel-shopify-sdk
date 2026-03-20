<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DiscountCode extends Model
{
    protected $table = 'shopify_discount_codes';

    protected $fillable = [
        'store_id',
        'price_rule_id',
        'shopify_id',
        'code',
        'usage_count',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'price_rule_id' => 'integer',
        'usage_count' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function priceRule(): BelongsTo
    {
        return $this->belongsTo(Discount::class, 'price_rule_id');
    }

    public function discount(): BelongsTo
    {
        return $this->priceRule();
    }
}
