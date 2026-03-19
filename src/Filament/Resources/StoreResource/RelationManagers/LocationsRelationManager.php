<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\StoreResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\ViewAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Models\Location;
use LaravelShopifySdk\Services\VariantService;

class LocationsRelationManager extends RelationManager
{
    protected static string $relationship = 'locations';

    protected static ?string $title = 'Locations';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Location Name')
                    ->searchable()
                    ->sortable()
                    ->weight('bold')
                    ->icon('heroicon-o-map-pin'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),
                Tables\Columns\TextColumn::make('address')
                    ->label('Address')
                    ->formatStateUsing(function (Location $record) {
                        $payload = $record->payload ?? [];
                        $address = $payload['address'] ?? [];
                        $parts = array_filter([
                            $address['address1'] ?? null,
                            $address['city'] ?? null,
                            $address['country'] ?? null,
                        ]);
                        return implode(', ', $parts) ?: '—';
                    })
                    ->wrap()
                    ->color('gray'),
                Tables\Columns\IconColumn::make('fulfills_online')
                    ->label('Fulfills Online')
                    ->state(fn (Location $record) => $record->payload['fulfillsOnlineOrders'] ?? false)
                    ->boolean()
                    ->trueIcon('heroicon-o-truck')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('success')
                    ->falseColor('gray'),
                Tables\Columns\TextColumn::make('shopify_id')
                    ->label('Shopify ID')
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->fontFamily('mono')
                    ->size('sm')
                    ->color('gray'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Last Synced')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status')
                    ->placeholder('All Locations')
                    ->trueLabel('Active Only')
                    ->falseLabel('Inactive Only'),
            ])
            ->headerActions([
                Action::make('sync_locations')
                    ->label('Sync Locations')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function () {
                        try {
                            $store = $this->getOwnerRecord();
                            $service = new VariantService(new GraphQLClient());
                            $count = $service->syncLocations($store);

                            Notification::make()
                                ->title('Locations Synced')
                                ->body("Successfully synced {$count} location(s) from Shopify.")
                                ->success()
                                ->send();
                        } catch (\Exception $e) {
                            Notification::make()
                                ->title('Sync Failed')
                                ->body($e->getMessage())
                                ->danger()
                                ->send();
                        }
                    }),
            ])
            ->recordActions([
                ViewAction::make()
                    ->modalHeading(fn (Location $record) => $record->name),
            ])
            ->emptyStateHeading('No Locations')
            ->emptyStateDescription('Click "Sync Locations" to fetch locations from Shopify.')
            ->emptyStateIcon('heroicon-o-map-pin')
            ->defaultSort('name', 'asc');
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make('Location Details')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        TextEntry::make('name')
                            ->label('Name')
                            ->weight('bold'),
                        TextEntry::make('shopify_id')
                            ->label('Shopify ID')
                            ->fontFamily('mono')
                            ->copyable(),
                        TextEntry::make('is_active')
                            ->label('Status')
                            ->badge()
                            ->formatStateUsing(fn ($state) => $state ? 'Active' : 'Inactive')
                            ->color(fn ($state) => $state ? 'success' : 'danger'),
                    ])
                    ->columns(3),
                Section::make('Address')
                    ->icon('heroicon-o-map')
                    ->schema([
                        TextEntry::make('address1')
                            ->label('Address Line 1')
                            ->state(fn (Location $record) => $record->payload['address']['address1'] ?? '—'),
                        TextEntry::make('address2')
                            ->label('Address Line 2')
                            ->state(fn (Location $record) => $record->payload['address']['address2'] ?? '—'),
                        TextEntry::make('city')
                            ->label('City')
                            ->state(fn (Location $record) => $record->payload['address']['city'] ?? '—'),
                        TextEntry::make('province')
                            ->label('Province/State')
                            ->state(fn (Location $record) => $record->payload['address']['province'] ?? '—'),
                        TextEntry::make('country')
                            ->label('Country')
                            ->state(fn (Location $record) => $record->payload['address']['country'] ?? '—'),
                        TextEntry::make('zip')
                            ->label('ZIP/Postal Code')
                            ->state(fn (Location $record) => $record->payload['address']['zip'] ?? '—'),
                        TextEntry::make('phone')
                            ->label('Phone')
                            ->state(fn (Location $record) => $record->payload['address']['phone'] ?? '—'),
                    ])
                    ->columns(3),
                Section::make('Capabilities')
                    ->icon('heroicon-o-cog-6-tooth')
                    ->schema([
                        TextEntry::make('fulfills_online_orders')
                            ->label('Fulfills Online Orders')
                            ->badge()
                            ->state(fn (Location $record) => $record->payload['fulfillsOnlineOrders'] ?? false)
                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                            ->color(fn ($state) => $state ? 'success' : 'gray'),
                        TextEntry::make('has_active_inventory')
                            ->label('Has Active Inventory')
                            ->badge()
                            ->state(fn (Location $record) => $record->payload['hasActiveInventory'] ?? false)
                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                            ->color(fn ($state) => $state ? 'success' : 'gray'),
                        TextEntry::make('ships_inventory')
                            ->label('Ships Inventory')
                            ->badge()
                            ->state(fn (Location $record) => $record->payload['shipsInventory'] ?? false)
                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                            ->color(fn ($state) => $state ? 'success' : 'gray'),
                        TextEntry::make('has_unfulfilled_orders')
                            ->label('Has Unfulfilled Orders')
                            ->badge()
                            ->state(fn (Location $record) => $record->payload['hasUnfulfilledOrders'] ?? false)
                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No')
                            ->color(fn ($state) => $state ? 'warning' : 'gray'),
                    ])
                    ->columns(4),
            ]);
    }
}
