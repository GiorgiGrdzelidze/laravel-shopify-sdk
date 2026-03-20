<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models\Marketing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelShopifySdk\Models\Core\Store;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetafieldDefinition extends Model
{
    protected $table = 'shopify_metafield_definitions';

    protected $fillable = [
        'store_id',
        'shopify_id',
        'namespace',
        'key',
        'name',
        'description',
        'type',
        'owner_type',
        'validations',
        'pinned',
    ];

    protected $casts = [
        'store_id' => 'integer',
        'validations' => 'array',
        'pinned' => 'boolean',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function metafields(): HasMany
    {
        return $this->hasMany(Metafield::class, 'definition_id');
    }

    public function getFullKeyAttribute(): string
    {
        return "{$this->namespace}.{$this->key}";
    }

    public static function getOwnerTypes(): array
    {
        return [
            'PRODUCT' => 'Product',
            'PRODUCTVARIANT' => 'Variant',
            'COLLECTION' => 'Collection',
            'CUSTOMER' => 'Customer',
            'ORDER' => 'Order',
            'SHOP' => 'Shop',
        ];
    }

    public static function getMetafieldTypes(): array
    {
        return [
            'single_line_text_field' => 'Single Line Text',
            'multi_line_text_field' => 'Multi Line Text',
            'number_integer' => 'Integer',
            'number_decimal' => 'Decimal',
            'boolean' => 'Boolean',
            'date' => 'Date',
            'date_time' => 'Date & Time',
            'json' => 'JSON',
            'color' => 'Color',
            'url' => 'URL',
            'money' => 'Money',
            'rating' => 'Rating',
            'rich_text_field' => 'Rich Text',
            'file_reference' => 'File',
            'list.single_line_text_field' => 'List (Text)',
        ];
    }
}
