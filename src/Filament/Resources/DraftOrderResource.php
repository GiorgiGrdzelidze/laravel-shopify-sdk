<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
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
use LaravelShopifySdk\Models\Customer;
use LaravelShopifySdk\Models\DraftOrder;
use LaravelShopifySdk\Models\Store;

class DraftOrderResource extends Resource
{
    protected static ?string $model = DraftOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-text';

    protected static ?string $navigationLabel = 'Draft Orders';

    protected static ?int $navigationSort = 25;

    public static function getNavigationGroup(): ?string
    {
        return NavigationGroup::Operations->value;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Customer')
                    ->schema([
                        Select::make('store_id')
                            ->label('Store')
                            ->options(Store::where('status', 'active')->pluck('shop_domain', 'id')->toArray())
                            ->required()
                            ->searchable(),

                        Select::make('customer_id')
                            ->label('Customer')
                            ->options(function ($get) {
                                $storeId = $get('store_id');
                                if (!$storeId) {
                                    return [];
                                }
                                return Customer::where('store_id', $storeId)
                                    ->whereNotNull('email')
                                    ->pluck('email', 'id')
                                    ->toArray();
                            })
                            ->searchable()
                            ->nullable(),

                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->nullable(),

                        TextInput::make('phone')
                            ->label('Phone')
                            ->tel()
                            ->nullable(),
                    ])
                    ->columns(2),

                Section::make('Order Details')
                    ->schema([
                        Textarea::make('note')
                            ->label('Note')
                            ->rows(3)
                            ->columnSpanFull(),

                        Select::make('currency')
                            ->label('Currency')
                            ->options([
                                'USD' => 'USD',
                                'EUR' => 'EUR',
                                'GBP' => 'GBP',
                                'CAD' => 'CAD',
                                'AUD' => 'AUD',
                            ])
                            ->default('USD'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Draft')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('store.shop_domain')
                    ->label('Store')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('customer.email')
                    ->label('Customer')
                    ->searchable()
                    ->default('No customer'),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'open' => 'warning',
                        'invoice_sent' => 'info',
                        'completed' => 'success',
                        default => 'gray',
                    }),

                TextColumn::make('line_items_count')
                    ->label('Items')
                    ->badge(),

                TextColumn::make('total_price')
                    ->label('Total')
                    ->money(fn ($record) => $record->currency)
                    ->sortable(),

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
                        'open' => 'Open',
                        'invoice_sent' => 'Invoice Sent',
                        'completed' => 'Completed',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                Action::make('send_invoice')
                    ->label('Send Invoice')
                    ->icon('heroicon-o-envelope')
                    ->color('info')
                    ->visible(fn (DraftOrder $record) => $record->isOpen())
                    ->requiresConfirmation(),
                Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn (DraftOrder $record) => !$record->isCompleted())
                    ->requiresConfirmation(),
                DeleteAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => \LaravelShopifySdk\Filament\Resources\DraftOrderResource\Pages\ListDraftOrders::route('/'),
            'create' => \LaravelShopifySdk\Filament\Resources\DraftOrderResource\Pages\CreateDraftOrder::route('/create'),
            'edit' => \LaravelShopifySdk\Filament\Resources\DraftOrderResource\Pages\EditDraftOrder::route('/{record}/edit'),
        ];
    }
}
