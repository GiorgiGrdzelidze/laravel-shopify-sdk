<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources;

use Filament\Forms\Components\DatePicker;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use LaravelShopifySdk\Filament\NavigationGroup;
use LaravelShopifySdk\Filament\NavigationIcon;
use LaravelShopifySdk\Filament\Resources\ShopifyLogResource\Pages;
use LaravelShopifySdk\Filament\Traits\HasShopifyPermissions;
use LaravelShopifySdk\Models\ShopifyLog;

class ShopifyLogResource extends Resource
{
    use HasShopifyPermissions;

    protected static ?string $model = ShopifyLog::class;

    protected static string|\BackedEnum|null $navigationIcon = NavigationIcon::OutlinedClipboardDocumentList;

    protected static \UnitEnum|string|null $navigationGroup = NavigationGroup::Reports;

    protected static ?int $navigationSort = 9;

    protected static ?string $navigationLabel = 'Activity Logs';

    protected static ?string $modelLabel = 'Activity Log';

    protected static ?string $pluralModelLabel = 'Activity Logs';

    protected static function getPermissionPrefix(): string
    {
        return 'sync.logs';
    }

    public static function canViewAny(): bool
    {
        return static::checkPermission('sync.logs');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Time')
                    ->dateTime('M d, Y H:i:s')
                    ->sortable(),
                Tables\Columns\TextColumn::make('store.shop_domain')
                    ->label('Store')
                    ->placeholder('—')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('User')
                    ->placeholder('System')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('action')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sync' => 'info',
                        'create' => 'success',
                        'update' => 'warning',
                        'delete' => 'danger',
                        'fetch' => 'gray',
                        default => 'primary',
                    })
                    ->searchable(),
                Tables\Columns\TextColumn::make('entity_type')
                    ->label('Entity')
                    ->badge()
                    ->color('gray')
                    ->searchable(),
                Tables\Columns\TextColumn::make('entity_id')
                    ->label('Entity ID')
                    ->limit(20)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'success' => 'success',
                        'error' => 'danger',
                        'info' => 'info',
                        'warning' => 'warning',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('message')
                    ->limit(50)
                    ->wrap()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->options([
                        'sync' => 'Sync',
                        'create' => 'Create',
                        'update' => 'Update',
                        'delete' => 'Delete',
                        'fetch' => 'Fetch',
                        'inventory_update' => 'Inventory Update',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('entity_type')
                    ->options([
                        'Product' => 'Product',
                        'Variant' => 'Variant',
                        'Order' => 'Order',
                        'Customer' => 'Customer',
                        'Store' => 'Store',
                        'Location' => 'Location',
                        'InventoryLevel' => 'Inventory Level',
                        'Collection' => 'Collection',
                        'Discount' => 'Discount',
                        'DraftOrder' => 'Draft Order',
                        'Fulfillment' => 'Fulfillment',
                        'Metafield' => 'Metafield',
                    ])
                    ->multiple(),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'success' => 'Success',
                        'error' => 'Error',
                        'info' => 'Info',
                        'warning' => 'Warning',
                    ])
                    ->multiple(),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        DatePicker::make('from')->label('From'),
                        DatePicker::make('until')->label('Until'),
                    ])
                    ->columns(2)
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['until'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
            ])
            ->actions([
            ])
            ->bulkActions([
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('30s');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListShopifyLogs::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return false;
    }
}
