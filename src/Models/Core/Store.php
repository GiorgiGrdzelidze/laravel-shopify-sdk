<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models\Core;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LaravelShopifySdk\Models\Orders\Order;
use LaravelShopifySdk\Models\Sync\SyncRun;
use LaravelShopifySdk\Models\Sync\WebhookEvent;

/**
 * Shopify Store Model
 *
 * Represents a connected Shopify store with OAuth credentials.
 * Stores encrypted access tokens and manages store lifecycle.
 *
 * @package LaravelShopifySdk\Models
 *
 * @property int $id
 * @property string $shop_domain
 * @property string $access_token
 * @property string|null $scopes
 * @property string $status
 * @property \Illuminate\Support\Carbon|null $installed_at
 * @property \Illuminate\Support\Carbon|null $uninstalled_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Store extends Model
{
    use HasFactory;

    public const MODE_OAUTH = 'oauth';
    public const MODE_TOKEN = 'token';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_UNINSTALLED = 'uninstalled';

    protected $fillable = [
        'shop_domain',
        'access_token',
        'mode',
        'currency',
        'custom_domain',
        'scopes',
        'metadata',
        'status',
        'installed_at',
        'uninstalled_at',
    ];

    protected $hidden = [
        'access_token',
    ];

    protected $casts = [
        'installed_at' => 'datetime',
        'uninstalled_at' => 'datetime',
        'access_token' => 'encrypted',
        'metadata' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('shopify.tables.stores', 'shopify_stores');
    }

    /**
     * Check if store is active.
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    /**
     * Mark store as active.
     *
     * @return void
     */
    public function markAsActive(): void
    {
        $this->update([
            'status' => 'active',
            'installed_at' => now(),
            'uninstalled_at' => null,
        ]);
    }

    /**
     * Mark store as inactive.
     *
     * @return void
     */
    public function markAsInactive(): void
    {
        $this->update([
            'status' => self::STATUS_INACTIVE,
            'uninstalled_at' => now(),
        ]);
    }

    /**
     * Check if store uses OAuth mode.
     *
     * @return bool
     */
    public function isOAuthMode(): bool
    {
        return $this->mode === self::MODE_OAUTH;
    }

    /**
     * Check if store uses token mode.
     *
     * @return bool
     */
    public function isTokenMode(): bool
    {
        return $this->mode === self::MODE_TOKEN;
    }

    /**
     * Get the masked access token for display.
     *
     * @return string
     */
    public function getMaskedTokenAttribute(): string
    {
        if (empty($this->access_token)) {
            return '—';
        }
        $token = $this->access_token;
        $length = strlen($token);
        if ($length <= 8) {
            return str_repeat('•', $length);
        }
        return substr($token, 0, 4) . str_repeat('•', $length - 8) . substr($token, -4);
    }

    /**
     * Get the public-facing URL for this store.
     * Uses custom_domain if set, otherwise falls back to shop_domain.
     *
     * @return string
     */
    public function getPublicUrl(): string
    {
        if (!empty($this->custom_domain)) {
            $domain = rtrim($this->custom_domain, '/');
            // Ensure https:// prefix
            if (!str_starts_with($domain, 'http://') && !str_starts_with($domain, 'https://')) {
                $domain = 'https://' . $domain;
            }
            return $domain;
        }

        return 'https://' . $this->shop_domain;
    }

    /**
     * Get the product URL on the public website.
     *
     * @param string $handle
     * @return string
     */
    public function getProductUrl(string $handle): string
    {
        return $this->getPublicUrl() . '/products/' . $handle;
    }

    /**
     * Get products for this store.
     *
     * @return HasMany
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class, 'store_id');
    }

    /**
     * Get orders for this store.
     *
     * @return HasMany
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'store_id');
    }

    /**
     * Get customers for this store.
     *
     * @return HasMany
     */
    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class, 'store_id');
    }

    /**
     * Get webhook events for this store.
     *
     * @return HasMany
     */
    public function webhookEvents(): HasMany
    {
        return $this->hasMany(WebhookEvent::class, 'store_id');
    }

    /**
     * Get sync runs for this store.
     *
     * @return HasMany
     */
    public function syncRuns(): HasMany
    {
        return $this->hasMany(SyncRun::class, 'store_id');
    }

    /**
     * Get locations for this store.
     *
     * @return HasMany
     */
    public function locations(): HasMany
    {
        return $this->hasMany(Location::class, 'store_id');
    }
}
