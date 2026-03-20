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
use LaravelShopifySdk\Filament\Resources\ProductTagResource\Pages;
use LaravelShopifySdk\Filament\Traits\HasShopifyPermissions;
use LaravelShopifySdk\Models\Core\ProductTag;
use LaravelShopifySdk\Models\Core\Store;

class ProductTagResource extends Resource
{
    use HasShopifyPermissions;

    protected static ?string $model = ProductTag::class;

    protected static string|\BackedEnum|null $navigationIcon = NavigationIcon::OutlinedHashtag;

    protected static \UnitEnum|string|null $navigationGroup = NavigationGroup::Shopify;

    protected static ?int $navigationSort = 8;

    protected static ?string $navigationLabel = 'Product Tags';

    protected static ?string $modelLabel = 'Product Tag';

    protected static ?string $pluralModelLabel = 'Product Tags';

    protected static function getPermissionPrefix(): string
    {
        return 'products';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Product Tag Details')
                    ->description('Manage product tag information')
                    ->icon('heroicon-o-hashtag')
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
                            ->label('Tag Name')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-hashtag')
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
                    ->label('Tag Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-hashtag'),
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
            'index' => Pages\ListProductTags::route('/'),
            'create' => Pages\CreateProductTag::route('/create'),
            'edit' => Pages\EditProductTag::route('/{record}/edit'),
        ];
    }
}
