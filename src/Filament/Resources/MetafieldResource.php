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
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use LaravelShopifySdk\Filament\NavigationGroup;
use LaravelShopifySdk\Models\Marketing\Metafield;
use LaravelShopifySdk\Models\Marketing\MetafieldDefinition;
use LaravelShopifySdk\Models\Core\Store;

class MetafieldResource extends Resource
{
    protected static ?string $model = Metafield::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-code-bracket';

    protected static ?string $navigationLabel = 'Metafields';

    protected static ?int $navigationSort = 15;

    public static function getNavigationGroup(): ?string
    {
        return NavigationGroup::Shopify->value;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Metafield')
                    ->schema([
                        Select::make('store_id')
                            ->label('Store')
                            ->options(Store::where('status', 'active')->pluck('shop_domain', 'id')->toArray())
                            ->required()
                            ->searchable(),

                        TextInput::make('namespace')
                            ->label('Namespace')
                            ->required()
                            ->maxLength(255)
                            ->helperText('e.g., custom, my_fields'),

                        TextInput::make('key')
                            ->label('Key')
                            ->required()
                            ->maxLength(255)
                            ->helperText('e.g., color, size, material'),

                        Select::make('type')
                            ->label('Type')
                            ->options(MetafieldDefinition::getMetafieldTypes())
                            ->required()
                            ->searchable(),

                        Select::make('owner_type')
                            ->label('Owner Type')
                            ->options(MetafieldDefinition::getOwnerTypes())
                            ->required(),

                        TextInput::make('owner_id')
                            ->label('Owner ID')
                            ->required()
                            ->helperText('Shopify GID of the owner'),

                        Textarea::make('value')
                            ->label('Value')
                            ->required()
                            ->rows(3)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('full_key')
                    ->label('Key')
                    ->searchable(['namespace', 'key'])
                    ->sortable(),

                TextColumn::make('store.shop_domain')
                    ->label('Store')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('owner_type')
                    ->label('Owner')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => MetafieldDefinition::getOwnerTypes()[$state] ?? $state),

                TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('value')
                    ->label('Value')
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->value),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('store_id')
                    ->label('Store')
                    ->options(Store::pluck('shop_domain', 'id')->toArray()),

                SelectFilter::make('owner_type')
                    ->label('Owner Type')
                    ->options(MetafieldDefinition::getOwnerTypes()),

                SelectFilter::make('type')
                    ->label('Type')
                    ->options(MetafieldDefinition::getMetafieldTypes()),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('updated_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \LaravelShopifySdk\Filament\Resources\MetafieldResource\Pages\ListMetafields::route('/'),
            'create' => \LaravelShopifySdk\Filament\Resources\MetafieldResource\Pages\CreateMetafield::route('/create'),
            'edit' => \LaravelShopifySdk\Filament\Resources\MetafieldResource\Pages\EditMetafield::route('/{record}/edit'),
        ];
    }
}
