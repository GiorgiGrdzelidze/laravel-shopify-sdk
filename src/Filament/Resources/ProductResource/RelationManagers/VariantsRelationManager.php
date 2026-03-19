<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\ProductResource\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Grid;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Models\Variant;
use LaravelShopifySdk\Services\VariantService;

class VariantsRelationManager extends RelationManager
{
    protected static string $relationship = 'variants';

    protected static ?string $recordTitleAttribute = 'sku';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('')
                    ->circular()
                    ->size(40)
                    ->defaultImageUrl(fn ($record) => $record->product?->image_url),
                Tables\Columns\TextColumn::make('title')
                    ->label('Variant')
                    ->searchable()
                    ->weight('bold')
                    ->description(fn (Variant $record) => $record->sku ? "SKU: {$record->sku}" : null),
                Tables\Columns\TextColumn::make('price')
                    ->money(fn ($record) => $record->product?->store?->currency ?? 'USD')
                    ->sortable()
                    ->weight('semibold')
                    ->color('success'),
                Tables\Columns\TextColumn::make('compare_at_price')
                    ->label('Compare At')
                    ->money(fn ($record) => $record->product?->store?->currency ?? 'USD')
                    ->sortable()
                    ->color('gray')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('inventory_quantity')
                    ->label('Inventory')
                    ->formatStateUsing(fn ($state) => $state ?? 0)
                    ->alignCenter()
                    ->badge()
                    ->color(fn ($state) => match (true) {
                        $state === null => 'gray',
                        $state <= 0 => 'danger',
                        $state <= 10 => 'warning',
                        default => 'success',
                    }),
                Tables\Columns\TextColumn::make('sku')
                    ->label('SKU')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('barcode')
                    ->label('Barcode')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('shopify_updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\Filter::make('low_stock')
                    ->label('Low Stock (≤10)')
                    ->query(fn ($query) => $query->whereRaw("json_extract(payload, '$.inventoryQuantity') <= 10")),
                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn ($query) => $query->whereRaw("json_extract(payload, '$.inventoryQuantity') <= 0")),
            ])
            ->headerActions([
                Action::make('sync_all_variants')
                    ->label('Sync All from Shopify')
                    ->icon('heroicon-o-arrow-path')
                    ->color('info')
                    ->action(function () {
                        Notification::make()
                            ->title('Sync Started')
                            ->body('Variant sync will be performed during next product sync.')
                            ->info()
                            ->send();
                    }),
            ])
            ->recordActions([
                ActionGroup::make([
                    ViewAction::make()
                        ->modalHeading(fn (Variant $record) => $record->title ?: 'Variant Details'),
                    EditAction::make()
                        ->modalHeading(fn (Variant $record) => 'Edit: ' . ($record->title ?: $record->sku ?: 'Variant'))
                        ->form(fn (Variant $record) => [
                            TextInput::make('sku')
                                ->label('SKU')
                                ->maxLength(255),
                            TextInput::make('barcode')
                                ->label('Barcode')
                                ->maxLength(255),
                            TextInput::make('price')
                                ->label('Price')
                                ->numeric()
                                ->prefix($record->product?->store?->currency ?? 'USD')
                                ->required(),
                            TextInput::make('compare_at_price')
                                ->label('Compare At Price')
                                ->numeric()
                                ->prefix($record->product?->store?->currency ?? 'USD')
                                ->helperText('Original price before discount'),
                        ]),
                    Action::make('update_inventory')
                        ->label('Update Inventory')
                        ->icon('heroicon-o-archive-box')
                        ->color('warning')
                        ->modalHeading('Update Inventory')
                        ->modalDescription(fn (Variant $record) => 'Update stock for: ' . ($record->title ?: $record->sku ?: 'Variant'))
                        ->form(function (Variant $record) {
                            $service = new VariantService(new GraphQLClient());
                            $inventoryLevels = [];
                            $formFields = [];

                            try {
                                $inventoryLevels = $service->getInventoryLevels($record);
                            } catch (\Exception $e) {
                                // Fallback if API fails
                            }

                            if (empty($inventoryLevels)) {
                                $formFields[] = Placeholder::make('no_inventory')
                                    ->label('')
                                    ->content(new \Illuminate\Support\HtmlString('<p style="color: #9ca3af;">No inventory data available</p>'));
                            } else {
                                foreach ($inventoryLevels as $index => $level) {
                                    $locationName = $level['location_name'];
                                    $locationId = $level['location_id'];
                                    $currentQty = $level['available'];

                                    $formFields[] = TextInput::make("quantities.{$index}.quantity")
                                        ->label($locationName)
                                        ->numeric()
                                        ->required()
                                        ->default($currentQty)
                                        ->suffix('units')
                                        ->helperText("Current: {$currentQty}");

                                    $formFields[] = \Filament\Forms\Components\Hidden::make("quantities.{$index}.location_id")
                                        ->default($locationId);
                                }
                            }

                            return $formFields;
                        })
                        ->action(function (Variant $record, array $data) {
                            try {
                                $service = new VariantService(new GraphQLClient());
                                $quantities = $data['quantities'] ?? [];

                                foreach ($quantities as $item) {
                                    if (isset($item['location_id']) && isset($item['quantity'])) {
                                        $service->updateInventory($record, (int) $item['quantity'], $item['location_id']);
                                    }
                                }

                                Notification::make()
                                    ->title('Inventory Updated')
                                    ->body('Stock quantities have been updated on Shopify.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Inventory Update Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                    Action::make('sync_from_shopify')
                        ->label('Sync from Shopify')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->action(function (Variant $record) {
                            try {
                                $service = new VariantService(new GraphQLClient());
                                $service->fetch($record);
                                Notification::make()
                                    ->title('Variant Synced')
                                    ->body('Variant data has been updated from Shopify.')
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
                    Action::make('push_to_shopify')
                        ->label('Push to Shopify')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Push Variant to Shopify')
                        ->modalDescription('This will update the variant on Shopify with the current local data.')
                        ->action(function (Variant $record) {
                            try {
                                $service = new VariantService(new GraphQLClient());
                                $service->update($record, [
                                    'sku' => $record->sku,
                                    'barcode' => $record->barcode,
                                    'price' => $record->price,
                                    'compareAtPrice' => $record->compare_at_price,
                                ]);
                                Notification::make()
                                    ->title('Variant Updated')
                                    ->body('Variant has been updated on Shopify.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Update Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->defaultSort('id', 'asc')
            ->striped();
    }

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Section::make()
                    ->schema([
                        Grid::make(3)
                            ->schema([
                                ImageEntry::make('image_url')
                                    ->label('')
                                    ->height(150)
                                    ->defaultImageUrl(fn (Variant $record) => $record->product?->image_url)
                                    ->columnSpan(1),
                                Grid::make(1)
                                    ->schema([
                                        TextEntry::make('title')
                                            ->label('Variant Title')
                                            ->size('lg')
                                            ->weight('bold'),
                                        TextEntry::make('shopify_id')
                                            ->label('Shopify ID')
                                            ->copyable()
                                            ->color('gray'),
                                    ])
                                    ->columnSpan(2),
                            ]),
                    ])
                    ->columnSpanFull(),

                Section::make('Pricing & Inventory')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        TextEntry::make('price')
                            ->label('Price')
                            ->money(fn (Variant $record) => $record->product?->store?->currency ?? 'USD')
                            ->size('lg')
                            ->weight('bold')
                            ->color('success'),
                        TextEntry::make('compare_at_price')
                            ->label('Compare At Price')
                            ->money(fn (Variant $record) => $record->product?->store?->currency ?? 'USD')
                            ->placeholder('—'),
                        TextEntry::make('inventory_quantity')
                            ->label('Stock Quantity')
                            ->badge()
                            ->color(fn ($state) => match (true) {
                                $state === null => 'gray',
                                $state <= 0 => 'danger',
                                $state <= 10 => 'warning',
                                default => 'success',
                            }),
                    ])
                    ->columns(3),

                Section::make('Identifiers')
                    ->icon('heroicon-o-identification')
                    ->schema([
                        TextEntry::make('sku')
                            ->label('SKU')
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('barcode')
                            ->label('Barcode')
                            ->copyable()
                            ->placeholder('—'),
                        TextEntry::make('inventory_item_id')
                            ->label('Inventory Item ID')
                            ->copyable()
                            ->placeholder('—'),
                    ])
                    ->columns(3),

                Section::make('Shipping & Tax')
                    ->icon('heroicon-o-truck')
                    ->schema([
                        TextEntry::make('weight')
                            ->label('Weight')
                            ->formatStateUsing(fn (Variant $record) => $record->weight
                                ? "{$record->weight} {$record->weight_unit}"
                                : '—'),
                        TextEntry::make('requires_shipping')
                            ->label('Requires Shipping')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'gray')
                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                        TextEntry::make('taxable')
                            ->label('Taxable')
                            ->badge()
                            ->color(fn ($state) => $state ? 'success' : 'gray')
                            ->formatStateUsing(fn ($state) => $state ? 'Yes' : 'No'),
                    ])
                    ->columns(3)
                    ->collapsed()
                    ->collapsible(),

                Section::make('Timestamps')
                    ->icon('heroicon-o-clock')
                    ->schema([
                        TextEntry::make('created_at')
                            ->label('Created')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->label('Local Updated')
                            ->dateTime(),
                        TextEntry::make('shopify_updated_at')
                            ->label('Shopify Updated')
                            ->dateTime(),
                    ])
                    ->columns(3)
                    ->collapsed()
                    ->collapsible(),
            ]);
    }
}
