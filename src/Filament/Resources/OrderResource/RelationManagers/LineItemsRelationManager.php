<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\OrderResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use LaravelShopifySdk\Models\OrderLine;

class LineItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'lineItems';

    protected static ?string $recordTitleAttribute = 'title';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Product')
                    ->searchable()
                    ->weight('semibold')
                    ->description(fn (OrderLine $record) => $record->variant?->sku ? 'SKU: ' . $record->variant->sku : null),
                Tables\Columns\TextColumn::make('variant.sku')
                    ->label('SKU')
                    ->searchable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('quantity')
                    ->label('Qty')
                    ->alignCenter()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('price')
                    ->label('Unit Price')
                    ->money(fn (OrderLine $record) => $record->order?->store?->currency ?? $record->order?->currency ?? 'USD')
                    ->alignEnd(),
                Tables\Columns\TextColumn::make('total')
                    ->label('Total')
                    ->money(fn (OrderLine $record) => $record->order?->store?->currency ?? $record->order?->currency ?? 'USD')
                    ->getStateUsing(fn (OrderLine $record) => $record->price * $record->quantity)
                    ->weight('semibold')
                    ->alignEnd(),
            ])
            ->filters([
                // Filters can be added here if needed
            ])
            ->headerActions([
                // Header actions can be added here if needed
            ])
            ->actions([
                // Row actions can be added here if needed
            ])
            ->bulkActions([
                // Bulk actions can be added here if needed
            ])
            ->defaultSort('id', 'asc');
    }
}
