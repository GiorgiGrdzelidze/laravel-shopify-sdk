<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use LaravelShopifySdk\Filament\NavigationGroup;
use LaravelShopifySdk\Filament\Resources\RoleResource\Pages;
use LaravelShopifySdk\Filament\Traits\HasShopifyPermissions;
use LaravelShopifySdk\Models\Permission;
use LaravelShopifySdk\Models\Role;

class RoleResource extends Resource
{
    use HasShopifyPermissions;

    protected static ?string $model = Role::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    protected static \UnitEnum|string|null $navigationGroup = NavigationGroup::AccessControl;

    protected static ?int $navigationSort = 10;

    protected static function getPermissionPrefix(): string
    {
        return 'settings.roles';
    }

    public static function canViewAny(): bool
    {
        return static::checkPermission('settings.roles');
    }

    public static function canCreate(): bool
    {
        return static::checkPermission('settings.roles');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->components([
                Section::make('Role Information')
                    ->icon('heroicon-o-shield-check')
                    ->schema([
                        TextInput::make('name')
                            ->label('Role Name')
                            ->required()
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) =>
                                $set('slug', Str::slug($state))
                            )
                            ->columnSpan(1),

                        TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true)
                            ->columnSpan(1),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->columnSpanFull(),

                        Checkbox::make('is_default')
                            ->label('Default role for new users')
                            ->helperText('New users will automatically be assigned this role')
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Permissions')
                    ->icon('heroicon-o-key')
                    ->description('Select the permissions for this role')
                    ->schema(function () {
                        $grouped = Permission::getGrouped();
                        $components = [];

                        foreach ($grouped as $group => $permissions) {
                            $components[] = CheckboxList::make('permissions')
                                ->label(ucfirst($group ?: 'General'))
                                ->options($permissions)
                                ->columns(2)
                                ->bulkToggleable()
                                ->columnSpanFull();
                        }

                        if (empty($components)) {
                            $components[] = CheckboxList::make('permissions')
                                ->label('Permissions')
                                ->options(Permission::pluck('name', 'slug')->toArray())
                                ->columns(2)
                                ->bulkToggleable()
                                ->columnSpanFull();
                        }

                        return $components;
                    })
                    ->collapsible()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Role')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable()
                    ->color('gray'),

                TextColumn::make('permissions_count')
                    ->label('Permissions')
                    ->counts('permissions')
                    ->badge()
                    ->color('info'),

                TextColumn::make('users_count')
                    ->label('Users')
                    ->counts('users')
                    ->badge()
                    ->color('success'),

                IconColumn::make('is_default')
                    ->label('Default')
                    ->boolean(),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                EditAction::make(),
                DeleteAction::make()
                    ->before(function (Role $record) {
                        if ($record->slug === 'super-admin') {
                            Notification::make()
                                ->title('Cannot Delete')
                                ->body('The Super Admin role cannot be deleted.')
                                ->danger()
                                ->send();
                            return false;
                        }
                    }),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}
