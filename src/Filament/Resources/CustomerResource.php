<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources;

use Filament\Forms;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use LaravelShopifySdk\Filament\NavigationGroup;
use LaravelShopifySdk\Filament\NavigationIcon;
use LaravelShopifySdk\Filament\Resources\CustomerResource\Pages;
use LaravelShopifySdk\Models\Customer;
use BackedEnum;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static string|\BackedEnum|null $navigationIcon = NavigationIcon::OutlinedUsers;

    protected static \UnitEnum|string|null $navigationGroup = NavigationGroup::Shopify;

    protected static ?int $navigationSort = 4;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->schema([
                Forms\Components\TextInput::make('first_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('last_name')
                    ->maxLength(255),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->maxLength(255),
                Forms\Components\TextInput::make('phone')
                    ->tel()
                    ->maxLength(255),
                Forms\Components\TextInput::make('orders_count')
                    ->numeric(),
                Forms\Components\TextInput::make('total_spent')
                    ->numeric(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('full_name')
                    ->label('Name')
                    ->searchable(['first_name', 'last_name'])
                    ->sortable(['first_name', 'last_name'])
                    ->weight('bold')
                    ->getStateUsing(fn (Customer $record) => trim("{$record->first_name} {$record->last_name}")),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-envelope')
                    ->copyable(),
                Tables\Columns\TextColumn::make('phone')
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-phone')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('orders_count')
                    ->label('Orders')
                    ->alignCenter()
                    ->badge()
                    ->color('info')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_spent')
                    ->money(fn ($record) => $record->store?->currency ?? 'USD')
                    ->weight('semibold')
                    ->color('success')
                    ->sortable(),
                Tables\Columns\TextColumn::make('state')
                    ->badge()
                    ->colors([
                        'success' => 'enabled',
                        'danger' => 'disabled',
                        'warning' => 'invited',
                        'gray' => 'declined',
                    ])
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('shopify_updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('state')
                    ->options([
                        'enabled' => 'Enabled',
                        'disabled' => 'Disabled',
                        'invited' => 'Invited',
                        'declined' => 'Declined',
                    ])
                    ->multiple(),
            ])
            ->actions([
                // Actions can be added here if needed
            ])
            ->bulkActions([
                // Bulk actions can be added here if needed
            ])
            ->defaultSort('shopify_updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'view' => Pages\ViewCustomer::route('/{record}'),
        ];
    }
}
