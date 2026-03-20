<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources;

use Filament\Actions\ViewAction;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use LaravelShopifySdk\Filament\NavigationGroup;
use LaravelShopifySdk\Models\Orders\Fulfillment;
use LaravelShopifySdk\Models\Core\Store;

class FulfillmentResource extends Resource
{
    protected static ?string $model = Fulfillment::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-truck';

    protected static ?string $navigationLabel = 'Fulfillments';

    protected static ?int $navigationSort = 26;

    public static function getNavigationGroup(): ?string
    {
        return NavigationGroup::Operations->value;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Fulfillment')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('order.name')
                    ->label('Order')
                    ->searchable()
                    ->url(fn ($record) => $record->order_id ? OrderResource::getUrl('view', ['record' => $record->order_id]) : null),

                TextColumn::make('store.shop_domain')
                    ->label('Store')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'pending' => 'warning',
                        'open' => 'info',
                        'cancelled', 'failure', 'error' => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('tracking_company')
                    ->label('Carrier')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('tracking_number')
                    ->label('Tracking #')
                    ->searchable()
                    ->copyable()
                    ->url(fn ($record) => $record->tracking_url, shouldOpenInNewTab: true),

                TextColumn::make('shipment_status')
                    ->label('Shipment')
                    ->badge()
                    ->formatStateUsing(fn (?string $state) => $state ? ucwords(str_replace('_', ' ', $state)) : '-'),

                TextColumn::make('location.name')
                    ->label('Location')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('store_id')
                    ->label('Store')
                    ->options(Store::pluck('shop_domain', 'id')->toArray()),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'pending' => 'Pending',
                        'open' => 'Open',
                        'success' => 'Success',
                        'cancelled' => 'Cancelled',
                        'error' => 'Error',
                        'failure' => 'Failure',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \LaravelShopifySdk\Filament\Resources\FulfillmentResource\Pages\ListFulfillments::route('/'),
        ];
    }
}
