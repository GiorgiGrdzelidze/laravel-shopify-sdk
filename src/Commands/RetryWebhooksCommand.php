<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Commands;

use LaravelShopifySdk\Jobs\ProcessWebhookJob;
use LaravelShopifySdk\Models\Sync\WebhookEvent;
use Illuminate\Console\Command;

class RetryWebhooksCommand extends Command
{
    protected $signature = 'shopify:webhooks:retry
                            {--store= : Only retry for this shop domain}
                            {--topic= : Only retry this webhook topic}
                            {--limit=50 : Max events to retry}
                            {--dry-run : Show what would be retried without dispatching}';

    protected $description = 'Retry failed webhook events';

    public function handle(): int
    {
        $query = WebhookEvent::where('status', 'failed');

        if ($store = $this->option('store')) {
            $query->where('shop_domain', $store);
        }

        if ($topic = $this->option('topic')) {
            $query->where('topic', $topic);
        }

        $events = $query->oldest()->limit((int) $this->option('limit'))->get();

        if ($events->isEmpty()) {
            $this->info('No failed webhook events found.');
            return self::SUCCESS;
        }

        $this->info("Found {$events->count()} failed event(s) to retry.");

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN — no events will be dispatched.');
            $this->table(
                ['ID', 'Topic', 'Shop', 'Failed At'],
                $events->map(fn ($e) => [$e->id, $e->topic, $e->shop_domain, $e->updated_at])->toArray()
            );
            return self::SUCCESS;
        }

        $retried = 0;

        foreach ($events as $event) {
            $event->update(['status' => 'pending', 'error' => null]);
            ProcessWebhookJob::dispatch($event);
            $retried++;

            $this->line("  Retrying #{$event->id} [{$event->topic}] for {$event->shop_domain}");
        }

        $this->info("Dispatched {$retried} event(s) for reprocessing.");

        return self::SUCCESS;
    }
}
