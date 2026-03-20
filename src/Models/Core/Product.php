<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Shopify Product Model
 *
 * Represents a Shopify product with variants and full JSON payload.
 *
 * @package LaravelShopifySdk\Models
 *
 * @property int $id
 * @property int $store_id
 * @property string $shopify_id
 * @property string|null $title
 * @property string|null $handle
 * @property string|null $status
 * @property string|null $vendor
 * @property string|null $product_type
 * @property array $payload
 * @property \Illuminate\Support\Carbon|null $shopify_updated_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Product extends Model
{
    protected $fillable = [
        'store_id',
        'shopify_id',
        'title',
        'handle',
        'status',
        'vendor',
        'product_type',
        'images',
        'featured_image_url',
        'payload',
        'shopify_updated_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'images' => 'array',
        'shopify_updated_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('shopify.tables.products', 'shopify_products');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(Variant::class, 'product_id');
    }

    /**
     * Get the product's featured image URL from payload
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->payload['featuredImage']['url'] ?? null;
    }

    /**
     * Get the product's description from payload
     */
    public function getDescriptionAttribute(): ?string
    {
        return $this->payload['descriptionHtml'] ?? $this->payload['description'] ?? null;
    }

    /**
     * Get all product images from payload
     */
    public function getImagesAttribute(): array
    {
        $images = [];

        // Shopify format (synced products with full images array)
        if (isset($this->payload['images']['edges'])) {
            foreach ($this->payload['images']['edges'] as $edge) {
                $images[] = $edge['node'];
            }
        }

        // Fallback to featuredImage if no images array (reduced sync query)
        if (empty($images) && isset($this->payload['featuredImage']['url'])) {
            $images[] = [
                'id' => $this->payload['featuredImage']['id'] ?? null,
                'url' => $this->payload['featuredImage']['url'],
                'altText' => $this->payload['featuredImage']['altText'] ?? '',
            ];
        }

        // Local product format - media array
        if (isset($this->payload['media']) && is_array($this->payload['media'])) {
            foreach ($this->payload['media'] as $media) {
                if (!empty($media['originalSource'])) {
                    $images[] = [
                        'url' => $media['originalSource'],
                        'altText' => $media['alt'] ?? '',
                    ];
                }
            }
        }

        // Pending media (uploaded but not yet pushed to Shopify)
        if (isset($this->payload['pendingMedia']) && is_array($this->payload['pendingMedia'])) {
            foreach ($this->payload['pendingMedia'] as $media) {
                if (!empty($media['originalSource'])) {
                    $images[] = [
                        'url' => $media['originalSource'],
                        'altText' => $media['alt'] ?? '',
                        'pending' => true,
                    ];
                }
            }
        }

        return $images;
    }
}
