<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use LaravelShopifySdk\Filament\NavigationGroup;
use LaravelShopifySdk\Filament\Resources\PermissionResource\Pages;
use LaravelShopifySdk\Filament\Traits\HasShopifyPermissions;
use LaravelShopifySdk\Models\Permission;

class PermissionResource extends Resource
{
    use HasShopifyPermissions;

    protected static ?string $model = Permission::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static \UnitEnum|string|null $navigationGroup = NavigationGroup::AccessControl;

    protected static ?int $navigationSort = 11;

    protected static function getPermissionPrefix(): string
    {
        return 'settings.permissions';
    }

    public static function canViewAny(): bool
    {
        return static::checkPermission('settings.permissions');
    }

    public static function canCreate(): bool
    {
        return static::checkPermission('settings.permissions');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->components([
                Section::make('Permission Information')
                    ->icon('heroicon-o-key')
                    ->schema([
                        TextInput::make('name')
                            ->label('Permission Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                                $set('slug', ($get('group') ? $get('group') . '.' : '') . Str::slug($state, '_'))
                            )
                            ->columnSpan(1),

                        Select::make('group')
                            ->label('Group')
                            ->options([
                                'stores' => 'Stores',
                                'products' => 'Products',
                                'orders' => 'Orders',
                                'customers' => 'Customers',
                                'inventory' => 'Inventory',
                                'sync' => 'Sync',
                                'settings' => 'Settings',
                            ])
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn ($state, callable $set, callable $get) =>
                                $set('slug', ($state ? $state . '.' : '') . Str::slug($get('name') ?? '', '_'))
                            )
                            ->columnSpan(1),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->helperText('Used in code to check permissions')
                            ->columnSpanFull(),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('group')
                    ->label('Group')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'stores' => 'primary',
                        'products' => 'success',
                        'orders' => 'warning',
                        'customers' => 'info',
                        'inventory' => 'danger',
                        'sync' => 'gray',
                        'settings' => 'purple',
                        default => 'gray',
                    })
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Permission')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->color('gray')
                    ->copyable(),

                TextColumn::make('roles_count')
                    ->label('Roles')
                    ->counts('roles')
                    ->badge()
                    ->color('info'),

                TextColumn::make('description')
                    ->label('Description')
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('group')
                    ->options([
                        'stores' => 'Stores',
                        'products' => 'Products',
                        'orders' => 'Orders',
                        'customers' => 'Customers',
                        'inventory' => 'Inventory',
                        'sync' => 'Sync',
                        'settings' => 'Settings',
                    ]),
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('group');
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
            'index' => Pages\ListPermissions::route('/'),
            'create' => Pages\CreatePermission::route('/create'),
            'edit' => Pages\EditPermission::route('/{record}/edit'),
        ];
    }
}
