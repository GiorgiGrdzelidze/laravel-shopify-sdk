<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models\Core;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Shopify Product Tag Model
 *
 * Represents a product tag for organizing and filtering products.
 *
 * @property int $id
 * @property int $store_id
 * @property string $name
 * @property string|null $slug
 * @property string|null $description
 * @property int $products_count
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class ProductTag extends Model
{
    protected $table = 'shopify_product_tags';

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'description',
        'products_count',
    ];

    protected $casts = [
        'products_count' => 'integer',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('name') && empty($model->slug)) {
                $model->slug = Str::slug($model->name);
            }
        });
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    /**
     * Get products with this tag.
     */
    public function products()
    {
        return Product::where('store_id', $this->store_id)
            ->whereJsonContains('payload->tags', $this->name);
    }

    /**
     * Update the products count.
     */
    public function updateProductsCount(): void
    {
        $this->products_count = $this->products()->count();
        $this->save();
    }
}
