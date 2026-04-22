<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelShopifySdk\Models\Core\Store;
use LaravelShopifySdk\Models\Sync\SyncRun;

/**
 * Dispatched when an entity sync completes successfully.
 *
 * Listen for this event to react to synced data:
 *   Event::listen(SyncCompleted::class, function ($event) {
 *       if ($event->entity === 'products') { ... }
 *   });
 */
class SyncCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Store $store,
        public readonly string $entity,
        public readonly SyncRun $syncRun,
        public readonly array $counts,
        public readonly int $durationMs,
    ) {}
}
