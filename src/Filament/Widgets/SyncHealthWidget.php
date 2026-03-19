<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use LaravelShopifySdk\Models\Store;
use LaravelShopifySdk\Models\SyncRun;

class SyncHealthWidget extends BaseWidget
{
    protected static ?string $heading = 'Sync Health';

    protected static ?int $sort = 2;

    protected int|string|array $columnSpan = 'full';

    protected static ?string $pollingInterval = '60s';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                SyncRun::query()
                    ->select('shopify_sync_runs.*')
                    ->join(
                        \DB::raw('(SELECT store_id, entity, MAX(id) as max_id FROM ' . config('shopify.tables.sync_runs', 'shopify_sync_runs') . ' GROUP BY store_id, entity) as latest'),
                        function ($join) {
                            $join->on('shopify_sync_runs.id', '=', 'latest.max_id');
                        }
                    )
                    ->with('store')
            )
            ->columns([
                Tables\Columns\TextColumn::make('store.shop_domain')
                    ->label('Store')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('entity')
                    ->label('Entity')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucfirst($state))
                    ->color(fn (string $state): string => match ($state) {
                        'products' => 'info',
                        'orders' => 'warning',
                        'customers' => 'success',
                        'inventory' => 'gray',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'completed'))
                    ->color(fn (?string $state): string => match ($state) {
                        'running' => 'warning',
                        'failed' => 'danger',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('counts')
                    ->label('Records')
                    ->formatStateUsing(function ($state) {
                        if (empty($state) || !is_array($state)) {
                            return '—';
                        }
                        return collect($state)
                            ->map(fn ($count, $key) => "{$count} " . str($key)->singular()->when($count !== 1, fn ($s) => $s->plural()))
                            ->join(', ');
                    }),
                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return '—';
                        }
                        if ($state < 1000) {
                            return "{$state}ms";
                        }
                        $seconds = round($state / 1000, 1);
                        if ($seconds < 60) {
                            return "{$seconds}s";
                        }
                        $minutes = round($seconds / 60, 1);
                        return "{$minutes}m";
                    })
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('errors')
                    ->label('Errors')
                    ->formatStateUsing(function ($state) {
                        if (empty($state)) {
                            return '✓';
                        }
                        $errors = is_array($state) ? $state : json_decode($state, true);
                        return count($errors) . ' error(s)';
                    })
                    ->color(fn ($state) => empty($state) ? 'success' : 'danger')
                    ->badge(),
                Tables\Columns\TextColumn::make('finished_at')
                    ->label('Last Sync')
                    ->dateTime()
                    ->sortable()
                    ->description(fn (SyncRun $record) => $record->finished_at?->diffForHumans()),
            ])
            ->defaultSort('finished_at', 'desc')
            ->paginated(false);
    }

    public static function canView(): bool
    {
        return config('shopify.filament.widgets.sync_health', true);
    }
}
