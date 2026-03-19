<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

/**
 * Shopify Action Log Model
 *
 * Logs all Shopify-related actions for auditing and debugging.
 *
 * @property int $id
 * @property int|null $store_id
 * @property int|null $user_id
 * @property string $action
 * @property string $entity_type
 * @property string|null $entity_id
 * @property string $status
 * @property string|null $message
 * @property array|null $context
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class ShopifyLog extends Model
{
    protected $fillable = [
        'store_id',
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'status',
        'message',
        'context',
    ];

    protected $casts = [
        'context' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('shopify.tables.logs', 'shopify_logs');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(config('auth.providers.users.model', \App\Models\User::class), 'user_id');
    }

    public static function log(
        string $action,
        string $entityType,
        ?string $entityId = null,
        string $status = 'success',
        ?string $message = null,
        ?array $context = null,
        ?int $storeId = null
    ): self {
        return self::create([
            'store_id' => $storeId,
            'user_id' => Auth::id(),
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'status' => $status,
            'message' => $message,
            'context' => $context,
        ]);
    }

    public static function success(
        string $action,
        string $entityType,
        ?string $entityId = null,
        ?string $message = null,
        ?array $context = null,
        ?int $storeId = null
    ): self {
        return self::log($action, $entityType, $entityId, 'success', $message, $context, $storeId);
    }

    public static function error(
        string $action,
        string $entityType,
        ?string $entityId = null,
        ?string $message = null,
        ?array $context = null,
        ?int $storeId = null
    ): self {
        return self::log($action, $entityType, $entityId, 'error', $message, $context, $storeId);
    }

    public static function info(
        string $action,
        string $entityType,
        ?string $entityId = null,
        ?string $message = null,
        ?array $context = null,
        ?int $storeId = null
    ): self {
        return self::log($action, $entityType, $entityId, 'info', $message, $context, $storeId);
    }
}
