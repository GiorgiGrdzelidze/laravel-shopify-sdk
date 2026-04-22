<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use LaravelShopifySdk\Models\Core\Store;
use LaravelShopifySdk\Models\Sync\SyncRun;

/**
 * Dispatched when an entity sync fails.
 */
class SyncFailed
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Store $store,
        public readonly string $entity,
        public readonly SyncRun $syncRun,
        public readonly \Throwable $exception,
        public readonly int $durationMs,
    ) {}
}
