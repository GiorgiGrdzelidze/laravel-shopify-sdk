<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\CollectionResource\RelationManagers;

use Filament\Actions\AttachAction;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use LaravelShopifySdk\Models\Product;

class ProductsRelationManager extends RelationManager
{
    protected static string $relationship = 'products';

    protected static ?string $title = 'Products';

    protected static string|\BackedEnum|null $icon = 'heroicon-o-cube';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                ImageColumn::make('featured_image_url')
                    ->label('')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl('https://placehold.co/40x40/e5e7eb/9ca3af?text=No'),

                TextColumn::make('title')
                    ->label('Product')
                    ->searchable()
                    ->sortable()
                    ->limit(50),

                TextColumn::make('vendor')
                    ->label('Vendor')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'ACTIVE' => 'success',
                        'DRAFT' => 'warning',
                        'ARCHIVED' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('pivot.position')
                    ->label('Position')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([])
            ->headerActions([
                AttachAction::make()
                    ->label('Add Products')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(function () {
                        $collection = $this->getOwnerRecord();
                        return Product::where('store_id', $collection->store_id)
                            ->whereNotIn('id', $collection->products()->pluck('shopify_products.id'));
                    })
                    ->recordSelectSearchColumns(['title', 'vendor', 'handle'])
                    ->multiple()
                    ->after(function () {
                        $this->updateProductsCount();
                    }),
            ])
            ->actions([
                DetachAction::make()
                    ->label('Remove')
                    ->after(function () {
                        $this->updateProductsCount();
                    }),
            ])
            ->bulkActions([
                DetachBulkAction::make()
                    ->label('Remove Selected')
                    ->after(function () {
                        $this->updateProductsCount();
                    }),
            ])
            ->defaultSort('title');
    }

    protected function updateProductsCount(): void
    {
        $collection = $this->getOwnerRecord();
        $collection->update([
            'products_count' => $collection->products()->count(),
        ]);
    }
}
