<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Models\Sync;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LaravelShopifySdk\Models\Core\Store;

/**
 * Shopify Sync Run Model
 *
 * Tracks synchronization runs with metrics and error logging.
 *
 * @package LaravelShopifySdk\Models
 *
 * @property int $id
 * @property int $store_id
 * @property string $entity
 * @property array|null $params
 * @property array|null $counts
 * @property array|null $errors
 * @property int|null $duration_ms
 * @property \Illuminate\Support\Carbon|null $started_at
 * @property \Illuminate\Support\Carbon|null $finished_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class SyncRun extends Model
{
    protected $fillable = [
        'store_id',
        'entity',
        'params',
        'counts',
        'errors',
        'duration_ms',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'params' => 'array',
        'counts' => 'array',
        'errors' => 'array',
        'duration_ms' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = config('shopify.tables.sync_runs', 'shopify_sync_runs');
    }

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class, 'store_id');
    }
}
