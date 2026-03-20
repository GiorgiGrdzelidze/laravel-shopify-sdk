<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use LaravelShopifySdk\Filament\NavigationGroup;
use LaravelShopifySdk\Filament\NavigationIcon;
use LaravelShopifySdk\Filament\Resources\ProductTypeResource\Pages;
use LaravelShopifySdk\Filament\Traits\HasShopifyPermissions;
use LaravelShopifySdk\Models\Core\ProductType;
use LaravelShopifySdk\Models\Core\Store;

class ProductTypeResource extends Resource
{
    use HasShopifyPermissions;

    protected static ?string $model = ProductType::class;

    protected static string|\BackedEnum|null $navigationIcon = NavigationIcon::OutlinedTag;

    protected static \UnitEnum|string|null $navigationGroup = NavigationGroup::Shopify;

    protected static ?int $navigationSort = 7;

    protected static ?string $navigationLabel = 'Product Types';

    protected static ?string $modelLabel = 'Product Type';

    protected static ?string $pluralModelLabel = 'Product Types';

    protected static function getPermissionPrefix(): string
    {
        return 'products';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product Type Details')
                    ->description('Manage product type information')
                    ->icon('heroicon-o-tag')
                    ->schema([
                        Select::make('store_id')
                            ->label('Store')
                            ->relationship('store', 'shop_domain')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->visible(fn () => Store::count() > 1)
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->label('Type Name')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-tag')
                            ->columnSpan(['default' => 'full', 'md' => 1]),
                        TextInput::make('slug')
                            ->label('Slug')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Auto-generated from name if left empty')
                            ->columnSpan(['default' => 'full', 'md' => 1]),
                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Type Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-tag'),
                Tables\Columns\TextColumn::make('store.shop_domain')
                    ->label('Store')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->visible(fn () => Store::count() > 1),
                Tables\Columns\TextColumn::make('slug')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('products_count')
                    ->label('Products')
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('store_id')
                    ->label('Store')
                    ->relationship('store', 'shop_domain')
                    ->visible(fn () => Store::count() > 1),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                DeleteBulkAction::make()
                    ->requiresConfirmation(),
            ])
            ->defaultSort('name');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductTypes::route('/'),
            'create' => Pages\CreateProductType::route('/create'),
            'edit' => Pages\EditProductType::route('/{record}/edit'),
        ];
    }
}
