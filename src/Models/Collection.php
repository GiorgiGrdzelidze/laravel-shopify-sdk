<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Collection extends Model
{
    protected $table = 'shopify_collections';

    protected $fillable = [
        'store_id',
        'shopify_id',
        'title',
        'handle',
        'description',
        'description_html',
        'image_url',
        'collection_type',
        'rules',
        'sort_order',
        'products_count',
        'published_at',
        'payload',
    ];

    protected $casts = [
        'rules' => 'array',
        'payload' => 'array',
        'products_count' => 'integer',
        'published_at' => 'datetime',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function products(): BelongsToMany
    {
        return $this->belongsToMany(
            Product::class,
            'shopify_collection_products',
            'collection_id',
            'product_id'
        )->withPivot('position')->withTimestamps()->orderBy('position');
    }

    public function scopeSmart($query)
    {
        return $query->where('collection_type', 'smart');
    }

    public function scopeCustom($query)
    {
        return $query->where('collection_type', 'custom');
    }

    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at');
    }

    public function isSmartCollection(): bool
    {
        return $this->collection_type === 'smart';
    }

    public function isCustomCollection(): bool
    {
        return $this->collection_type === 'custom';
    }

    public function getShopifyAdminUrl(): string
    {
        $numericId = str_replace('gid://shopify/Collection/', '', $this->shopify_id);
        return "https://{$this->store->shop_domain}/admin/collections/{$numericId}";
    }
}
