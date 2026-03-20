<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use LaravelShopifySdk\Filament\NavigationGroup;
use LaravelShopifySdk\Filament\Resources\UserResource\Pages;
use LaravelShopifySdk\Filament\Traits\HasShopifyPermissions;
use LaravelShopifySdk\Models\Access\Role;
use LaravelShopifySdk\Models\Core\Store;

class UserResource extends Resource
{
    use HasShopifyPermissions;

    protected static ?string $model = null;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-users';

    protected static \UnitEnum|string|null $navigationGroup = NavigationGroup::AccessControl;

    protected static ?int $navigationSort = 12;

    protected static function getPermissionPrefix(): string
    {
        return 'settings.users';
    }

    public static function canViewAny(): bool
    {
        return static::checkPermission('settings.users');
    }

    public static function canCreate(): bool
    {
        return static::checkPermission('settings.users');
    }

    public static function getModel(): string
    {
        return config('auth.providers.users.model', 'App\\Models\\User');
    }

    public static function form(Schema $form): Schema
    {
        $hasTraitMethod = method_exists(app(static::getModel()), 'shopifyRoles');

        $components = [
            Section::make('User Information')
                ->icon('heroicon-o-user')
                ->schema([
                    TextInput::make('name')
                        ->label('Name')
                        ->required()
                        ->maxLength(255)
                        ->columnSpan(1),

                    TextInput::make('email')
                        ->label('Email')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true)
                        ->columnSpan(1),
                ])
                ->columns(2)
                ->columnSpanFull(),
        ];

        if ($hasTraitMethod) {
            $components[] = Section::make('Shopify Roles')
                ->icon('heroicon-o-shield-check')
                ->description('Assign roles to control access to Shopify resources')
                ->schema([
                    CheckboxList::make('shopify_roles')
                        ->label('')
                        ->options(Role::pluck('name', 'id')->toArray())
                        ->descriptions(Role::pluck('description', 'id')->toArray())
                        ->columns(2)
                        ->bulkToggleable()
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->columnSpanFull();

            $components[] = Section::make('Store Access')
                ->icon('heroicon-o-building-storefront')
                ->description('Restrict user to specific stores (leave empty for all stores)')
                ->schema([
                    CheckboxList::make('shopify_stores')
                        ->label('')
                        ->options(Store::pluck('shop_domain', 'id')->toArray())
                        ->columns(2)
                        ->bulkToggleable()
                        ->columnSpanFull(),
                ])
                ->collapsible()
                ->columnSpanFull();
        } else {
            $components[] = Section::make('Setup Required')
                ->icon('heroicon-o-exclamation-triangle')
                ->schema([
                    \Filament\Forms\Components\Placeholder::make('trait_warning')
                        ->label('')
                        ->content(new \Illuminate\Support\HtmlString(
                            '<div style="padding: 16px; background: #fef3c7; border-radius: 8px; border: 1px solid #f59e0b;">
                                <p style="font-weight: 600; color: #92400e; margin-bottom: 8px;">⚠️ HasShopifyRoles Trait Not Found</p>
                                <p style="color: #78350f; margin-bottom: 12px;">To enable role assignment, add the trait to your User model:</p>
                                <pre style="background: #1f2937; color: #f3f4f6; padding: 12px; border-radius: 6px; overflow-x: auto; font-size: 13px;">use LaravelShopifySdk\Traits\HasShopifyRoles;

class User extends Authenticatable
{
    use HasShopifyRoles;
}</pre>
                            </div>'
                        ))
                        ->columnSpanFull(),
                ])
                ->columnSpanFull();
        }

        return $form->components($components);
    }

    public static function table(Table $table): Table
    {
        $hasTraitMethod = method_exists(app(static::getModel()), 'shopifyRoles');

        $columns = [
            TextColumn::make('name')
                ->label('Name')
                ->searchable()
                ->sortable(),

            TextColumn::make('email')
                ->label('Email')
                ->searchable()
                ->sortable(),

            TextColumn::make('created_at')
                ->label('Created')
                ->dateTime()
                ->sortable()
                ->toggleable(isToggledHiddenByDefault: true),
        ];

        // Only add role column if trait is present
        if ($hasTraitMethod) {
            array_splice($columns, 2, 0, [
                TextColumn::make('shopifyRoles.name')
                    ->label('Roles')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Super Admin' => 'danger',
                        'Store Manager' => 'warning',
                        'Product Manager' => 'success',
                        'Order Manager' => 'info',
                        'Viewer' => 'gray',
                        default => 'primary',
                    })
                    ->separator(', '),
            ]);
        }

        $filters = [];
        if ($hasTraitMethod) {
            $filters[] = SelectFilter::make('role')
                ->label('Role')
                ->relationship('shopifyRoles', 'name')
                ->preload();
        }

        return $table
            ->columns($columns)
            ->filters($filters)
            ->actions([
                ViewAction::make(),
                EditAction::make(),
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
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        // Only eager load relationships if trait is present
        $model = app(static::getModel());
        if (method_exists($model, 'shopifyRoles')) {
            $query->with(['shopifyRoles', 'shopifyStores']);
        }

        return $query;
    }
}
