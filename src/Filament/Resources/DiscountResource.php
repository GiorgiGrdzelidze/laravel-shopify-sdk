<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources;

use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use LaravelShopifySdk\Filament\NavigationGroup;
use LaravelShopifySdk\Models\Marketing\Discount;
use LaravelShopifySdk\Models\Core\Store;

class DiscountResource extends Resource
{
    protected static ?string $model = Discount::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-tag';

    protected static ?string $navigationLabel = 'Discounts';

    protected static ?int $navigationSort = 35;

    public static function getNavigationGroup(): ?string
    {
        return NavigationGroup::Marketing->value;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Discount Details')
                    ->schema([
                        Select::make('store_id')
                            ->label('Store')
                            ->options(Store::pluck('shop_domain', 'id')->toArray())
                            ->required()
                            ->searchable()
                            ->columnSpanFull(),

                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        Select::make('value_type')
                            ->label('Discount Type')
                            ->options([
                                'percentage' => 'Percentage',
                                'fixed_amount' => 'Fixed Amount',
                            ])
                            ->required(),

                        TextInput::make('value')
                            ->label('Value')
                            ->numeric()
                            ->required()
                            ->suffix(fn ($get) => $get('value_type') === 'percentage' ? '%' : '$'),

                        Select::make('target_type')
                            ->label('Applies To')
                            ->options([
                                'line_item' => 'Line Items',
                                'shipping_line' => 'Shipping',
                            ])
                            ->default('line_item')
                            ->required(),

                        Select::make('target_selection')
                            ->label('Target Selection')
                            ->options([
                                'all' => 'All Items',
                                'entitled' => 'Specific Items',
                            ])
                            ->default('all')
                            ->required(),

                        Select::make('customer_selection')
                            ->label('Customer Eligibility')
                            ->options([
                                'all' => 'All Customers',
                                'prerequisite' => 'Specific Customers',
                            ])
                            ->default('all')
                            ->required(),

                        Select::make('allocation_method')
                            ->label('Allocation Method')
                            ->options([
                                'across' => 'Across All Items',
                                'each' => 'Each Item',
                            ])
                            ->default('across')
                            ->required(),
                    ])
                    ->columns(2),

                Section::make('Usage Limits')
                    ->schema([
                        TextInput::make('usage_limit')
                            ->label('Total Usage Limit')
                            ->numeric()
                            ->nullable()
                            ->helperText('Leave empty for unlimited'),

                        Toggle::make('once_per_customer')
                            ->label('Limit to one use per customer'),
                    ])
                    ->columns(2),

                Section::make('Active Dates')
                    ->schema([
                        DateTimePicker::make('starts_at')
                            ->label('Start Date')
                            ->nullable(),

                        DateTimePicker::make('ends_at')
                            ->label('End Date')
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->label('Discount')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('store.shop_domain')
                    ->label('Store')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('formatted_value')
                    ->label('Value')
                    ->badge()
                    ->color('success'),

                TextColumn::make('value_type')
                    ->label('Type')
                    ->badge()
                    ->formatStateUsing(fn (string $state) => ucfirst(str_replace('_', ' ', $state))),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'active' => 'success',
                        'scheduled' => 'warning',
                        'expired' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('discountCodes_count')
                    ->label('Codes')
                    ->counts('discountCodes')
                    ->badge(),

                TextColumn::make('starts_at')
                    ->label('Starts')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('ends_at')
                    ->label('Ends')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('store_id')
                    ->label('Store')
                    ->options(Store::pluck('shop_domain', 'id')->toArray()),

                SelectFilter::make('value_type')
                    ->label('Type')
                    ->options([
                        'percentage' => 'Percentage',
                        'fixed_amount' => 'Fixed Amount',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => \LaravelShopifySdk\Filament\Resources\DiscountResource\Pages\ListDiscounts::route('/'),
            'create' => \LaravelShopifySdk\Filament\Resources\DiscountResource\Pages\CreateDiscount::route('/create'),
            'edit' => \LaravelShopifySdk\Filament\Resources\DiscountResource\Pages\EditDiscount::route('/{record}/edit'),
        ];
    }
}
