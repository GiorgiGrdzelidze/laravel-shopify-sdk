<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Placeholder;
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
use LaravelShopifySdk\Filament\Resources\OrderResource\Pages;
use LaravelShopifySdk\Models\Order;
use LaravelShopifySdk\Models\Store;
use BackedEnum;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|\BackedEnum|null $navigationIcon = NavigationIcon::OutlinedShoppingCart;

    protected static \UnitEnum|string|null $navigationGroup = NavigationGroup::Shopify;

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        $isSandboxMode = config('shopify.filament.testing_crud_enabled', false);

        return $schema
            ->components([
                Section::make('Sandbox Mode')
                    ->schema([
                        Placeholder::make('sandbox_warning')
                            ->label('')
                            ->content('⚠️ SANDBOX MODE: Changes here are local only and do NOT sync to Shopify.'),
                    ])
                    ->icon('heroicon-o-exclamation-triangle')
                    ->iconColor('warning')
                    ->visible($isSandboxMode)
                    ->columnSpanFull(),

                Section::make('Order Details')
                    ->description('Basic order information')
                    ->icon('heroicon-o-shopping-cart')
                    ->schema([
                        Select::make('store_id')
                            ->label('Store')
                            ->relationship('store', 'shop_domain')
                            ->required()
                            ->searchable()
                            ->preload()
                            ->native(false)
                            ->visible($isSandboxMode)
                            ->columnSpanFull(),
                        TextInput::make('name')
                            ->label('Order Name')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-document-text')
                            ->columnSpan(['default' => 'full', 'md' => 2]),
                        TextInput::make('order_number')
                            ->label('Order Number')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-hashtag')
                            ->columnSpan(['default' => 'full', 'md' => 2]),
                        TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-envelope')
                            ->columnSpan(['default' => 'full', 'md' => 2]),
                        TextInput::make('total_price')
                            ->label('Total Price')
                            ->numeric()
                            ->prefix('$')
                            ->columnSpan(['default' => 'full', 'md' => 1]),
                        TextInput::make('currency')
                            ->maxLength(10)
                            ->default('USD')
                            ->columnSpan(['default' => 'full', 'md' => 1]),
                    ])
                    ->columns(['default' => 1, 'md' => 4])
                    ->columnSpanFull(),

                Section::make('Order Status')
                    ->description('Payment and fulfillment status')
                    ->icon('heroicon-o-clipboard-document-check')
                    ->schema([
                        Select::make('financial_status')
                            ->label('Payment Status')
                            ->options([
                                'PENDING' => 'Pending',
                                'AUTHORIZED' => 'Authorized',
                                'PAID' => 'Paid',
                                'PARTIALLY_PAID' => 'Partially Paid',
                                'REFUNDED' => 'Refunded',
                                'VOIDED' => 'Voided',
                                'PARTIALLY_REFUNDED' => 'Partially Refunded',
                            ])
                            ->native(false)
                            ->columnSpan(['default' => 'full', 'md' => 1]),
                        Select::make('fulfillment_status')
                            ->label('Fulfillment Status')
                            ->options([
                                'FULFILLED' => 'Fulfilled',
                                'PARTIAL' => 'Partial',
                                'UNFULFILLED' => 'Unfulfilled',
                            ])
                            ->native(false)
                            ->columnSpan(['default' => 'full', 'md' => 1]),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpanFull(),

                Section::make('Raw Data')
                    ->description('JSON payload from Shopify')
                    ->icon('heroicon-o-code-bracket')
                    ->schema([
                        Textarea::make('payload')
                            ->label('JSON Payload')
                            ->rows(12)
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT) : $state)
                            ->dehydrateStateUsing(fn ($state) => is_string($state) ? json_decode($state, true) : $state),
                    ])
                    ->collapsed()
                    ->collapsible()
                    ->visible($isSandboxMode)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $isSandboxMode = config('shopify.filament.testing_crud_enabled', false);

        return $table
            ->header(view('shopify::filament.components.order-summary-header'))
            ->columns([
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->copyable(),
                Tables\Columns\TextColumn::make('store.shop_domain')
                    ->label('Store')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->visible(fn () => Store::count() > 1),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->copyable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-envelope')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->money(fn (Order $record) => $record->store?->currency ?? $record->currency ?? 'USD')
                    ->sortable()
                    ->weight('semibold'),
                Tables\Columns\TextColumn::make('financial_status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst(strtolower(str_replace('_', ' ', $state ?? ''))))
                    ->color(fn ($state): string => match (strtoupper($state ?? '')) {
                        'PAID' => 'success',
                        'PENDING', 'AUTHORIZED', 'PARTIALLY_PAID' => 'warning',
                        'REFUNDED', 'VOIDED', 'PARTIALLY_REFUNDED' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('fulfillment_status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst(strtolower($state ?? '')))
                    ->color(fn ($state): string => match (strtoupper($state ?? '')) {
                        'FULFILLED' => 'success',
                        'PARTIAL' => 'warning',
                        'UNFULFILLED' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('line_items_count')
                    ->counts('lineItems')
                    ->label('Items')
                    ->sortable(),
                Tables\Columns\TextColumn::make('processed_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('store_id')
                    ->label('Store')
                    ->relationship('store', 'shop_domain')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => Store::count() > 1),
                Tables\Filters\SelectFilter::make('financial_status')
                    ->options([
                        'PENDING' => 'Pending',
                        'AUTHORIZED' => 'Authorized',
                        'PAID' => 'Paid',
                        'PARTIALLY_PAID' => 'Partially Paid',
                        'REFUNDED' => 'Refunded',
                        'VOIDED' => 'Voided',
                        'PARTIALLY_REFUNDED' => 'Partially Refunded',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('fulfillment_status')
                    ->options([
                        'FULFILLED' => 'Fulfilled',
                        'PARTIAL' => 'Partial',
                        'UNFULFILLED' => 'Unfulfilled',
                    ])
                    ->multiple(),
                Tables\Filters\Filter::make('processed_at')
                    ->form([
                        DatePicker::make('processed_from')
                            ->label('From'),
                        DatePicker::make('processed_until')
                            ->label('Until'),
                    ])
                    ->columns(2)
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['processed_from'], fn ($q, $date) => $q->whereDate('processed_at', '>=', $date))
                            ->when($data['processed_until'], fn ($q, $date) => $q->whereDate('processed_at', '<=', $date));
                    }),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make()
                    ->visible($isSandboxMode),
                DeleteAction::make()
                    ->visible($isSandboxMode),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible($isSandboxMode),
                ]),
            ])
            ->defaultSort('processed_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            OrderResource\RelationManagers\LineItemsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        $isSandboxMode = config('shopify.filament.testing_crud_enabled', false);

        $pages = [
            'index' => Pages\ListOrders::route('/'),
            'view' => Pages\ViewOrder::route('/{record}'),
        ];

        if ($isSandboxMode) {
            $pages['create'] = Pages\CreateOrder::route('/create');
            $pages['edit'] = Pages\EditOrder::route('/{record}/edit');
        }

        return $pages;
    }
}
