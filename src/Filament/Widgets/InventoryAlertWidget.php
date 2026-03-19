<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use LaravelShopifySdk\Models\Variant;

class InventoryAlertWidget extends BaseWidget
{
    protected static ?int $sort = 5;

    protected static bool $isDiscovered = false;

    public function getHeading(): ?string
    {
        return 'Inventory Stock';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Variant::query()
                    ->with(['product:id,title,status'])
                    ->whereHas('product', fn ($q) => $q->where('status', 'ACTIVE'))
                    ->whereRaw("JSON_EXTRACT(payload, '$.inventoryQuantity') > 0")
                    ->orderByRaw("JSON_EXTRACT(payload, '$.inventoryQuantity') DESC")
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.title')
                    ->label('Product')
                    ->limit(35),
                Tables\Columns\TextColumn::make('title')
                    ->label('Variant')
                    ->placeholder('Default')
                    ->limit(20),
                Tables\Columns\TextColumn::make('payload')
                    ->label('In Stock')
                    ->getStateUsing(fn ($record) => $record->payload['inventoryQuantity'] ?? 0)
                    ->formatStateUsing(fn ($state) => number_format((int) $state))
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        (int) $state >= 50 => 'success',
                        (int) $state >= 20 => 'info',
                        (int) $state >= 10 => 'warning',
                        default => 'danger',
                    })
                    ->alignCenter(),
            ])
            ->paginated([5, 10])
            ->defaultPaginationPageOption(5);
    }
}
