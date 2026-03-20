<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Metafield extends Model
{
    protected $table = 'shopify_metafields';

    protected $fillable = [
        'store_id',
        'shopify_id',
        'namespace',
        'key',
        'value',
        'type',
        'owner_type',
        'owner_id',
        'definition_id',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'definition_id' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function definition(): BelongsTo
    {
        return $this->belongsTo(MetafieldDefinition::class, 'definition_id');
    }

    public function getFullKeyAttribute(): string
    {
        return "{$this->namespace}.{$this->key}";
    }

    public function getTypedValueAttribute(): mixed
    {
        return match ($this->type) {
            'number_integer' => (int) $this->value,
            'number_decimal' => (float) $this->value,
            'boolean' => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            'json', 'list.single_line_text_field' => json_decode($this->value, true),
            default => $this->value,
        };
    }
}
