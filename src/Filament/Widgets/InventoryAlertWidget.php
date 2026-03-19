<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Widgets;

use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\DB;
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
        $inventoryTable = config('shopify.tables.inventory_levels', 'shopify_inventory_levels');
        $variantsTable = config('shopify.tables.variants', 'shopify_variants');

        return $table
            ->query(
                Variant::query()
                    ->with(['product:id,title,status'])
                    ->whereHas('product', fn ($q) => $q->where('status', 'ACTIVE'))
                    ->whereNotNull('inventory_item_id')
                    ->select("{$variantsTable}.*")
                    ->selectSub(
                        DB::table($inventoryTable)
                            ->selectRaw('COALESCE(SUM(available), 0)')
                            ->whereColumn("{$inventoryTable}.inventory_item_id", "{$variantsTable}.inventory_item_id")
                            ->whereColumn("{$inventoryTable}.store_id", "{$variantsTable}.store_id"),
                        'total_stock'
                    )
                    ->having('total_stock', '>', 0)
                    ->orderBy('total_stock', 'desc')
            )
            ->columns([
                Tables\Columns\TextColumn::make('product.title')
                    ->label('Product')
                    ->limit(35),
                Tables\Columns\TextColumn::make('title')
                    ->label('Variant')
                    ->placeholder('Default')
                    ->limit(20),
                Tables\Columns\TextColumn::make('total_stock')
                    ->label('In Stock')
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
