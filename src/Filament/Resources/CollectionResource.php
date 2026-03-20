<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\HtmlString;
use LaravelShopifySdk\Filament\NavigationGroup;
use LaravelShopifySdk\Filament\NavigationIcon;
use LaravelShopifySdk\Filament\Resources\CollectionResource\Pages;
use LaravelShopifySdk\Filament\Traits\HasShopifyPermissions;
use LaravelShopifySdk\Models\Core\Collection;
use LaravelShopifySdk\Models\Core\Product;
use LaravelShopifySdk\Models\Core\Store;

class CollectionResource extends Resource
{
    use HasShopifyPermissions;

    protected static ?string $model = Collection::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static \UnitEnum|string|null $navigationGroup = NavigationGroup::Shopify;

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Collections';

    protected static function getPermissionPrefix(): string
    {
        return 'collections';
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Collection Details')
                    ->icon('heroicon-o-rectangle-stack')
                    ->schema([
                        Select::make('store_id')
                            ->label('Store')
                            ->options(Store::pluck('shop_domain', 'id')->toArray())
                            ->required()
                            ->searchable()
                            ->visible(fn (?Collection $record) => !$record)
                            ->columnSpanFull(),

                        Placeholder::make('image_preview')
                            ->label('Collection Image')
                            ->content(function (?Collection $record) {
                                if (!$record || !$record->image_url) {
                                    return new HtmlString(
                                        '<div style="width: 200px; height: 200px; background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%); border-radius: 12px; display: flex; align-items: center; justify-content: center; flex-direction: column; gap: 8px;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#9ca3af" style="width: 48px; height: 48px;">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                            </svg>
                                            <span style="color: #6b7280; font-size: 14px;">No image</span>
                                        </div>'
                                    );
                                }
                                return new HtmlString(
                                    '<div style="position: relative; display: inline-block;">
                                        <img src="' . htmlspecialchars($record->image_url) . '" alt="' . htmlspecialchars($record->title) . '"
                                         style="max-width: 200px; max-height: 200px; border-radius: 12px; object-fit: cover; border: 2px solid #e5e7eb;">
                                    </div>'
                                );
                            })
                            ->visible(fn (?Collection $record) => $record !== null)
                            ->columnSpanFull(),

                        \Filament\Forms\Components\Toggle::make('remove_image')
                            ->label('Remove Image')
                            ->offIcon('heroicon-o-trash')
                            ->onIcon('heroicon-o-check')
                            ->onColor('danger')
                            ->visible(fn (?Collection $record) => $record?->image_url)
                            ->helperText('Toggle on and save to remove the image')
                            ->columnSpanFull(),

                        TextInput::make('title')
                            ->label('Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('handle')
                            ->label('Handle')
                            ->helperText('URL-friendly name (auto-generated if empty)')
                            ->maxLength(255)
                            ->columnSpan(1),

                        Select::make('collection_type')
                            ->label('Type')
                            ->options([
                                'custom' => 'Custom Collection',
                            ])
                            ->default('custom')
                            ->disabled()
                            ->helperText('Only custom collections can be created via API')
                            ->columnSpan(1),

                        TextInput::make('products_count')
                            ->label('Products')
                            ->disabled()
                            ->visible(fn (?Collection $record) => $record)
                            ->columnSpan(1),

                        Textarea::make('description')
                            ->label('Description')
                            ->rows(3)
                            ->columnSpanFull(),

                        \Filament\Forms\Components\FileUpload::make('image_upload')
                            ->label('Upload Image')
                            ->disk('public')
                            ->directory('collections')
                            ->acceptedFileTypes(['image/*'])
                            ->helperText('Upload an image file')
                            ->columnSpanFull(),

                        TextInput::make('image_url')
                            ->label('Or Image URL')
                            ->placeholder('https://example.com/image.jpg')
                            ->helperText('Enter a URL or leave empty if uploading')
                            ->columnSpanFull(),

                        Select::make('product_ids')
                            ->label('Products')
                            ->multiple()
                            ->searchable()
                            ->preload()
                            ->options(function (?Collection $record) {
                                if (!$record) {
                                    return Product::limit(100)->pluck('title', 'id')->toArray();
                                }
                                return Product::where('store_id', $record->store_id)
                                    ->limit(500)
                                    ->pluck('title', 'id')
                                    ->toArray();
                            })
                            ->default(fn (?Collection $record) => $record?->products()->pluck('shopify_products.id')->toArray() ?? [])
                            ->visible(fn (?Collection $record) => $record === null || $record->isCustomCollection())
                            ->helperText('Select products to include in this collection (custom collections only)')
                            ->columnSpanFull(),

                        Placeholder::make('shopify_link')
                            ->label('Shopify Admin')
                            ->visible(fn (?Collection $record) => $record?->shopify_id)
                            ->content(function (?Collection $record) {
                                if (!$record) return '';
                                $url = $record->getShopifyAdminUrl();
                                return new HtmlString(
                                    '<a href="' . $url . '" target="_blank" class="text-primary-600 hover:underline">
                                        Open in Shopify →
                                    </a>'
                                );
                            })
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                Section::make('Smart Collection Rules')
                    ->icon('heroicon-o-funnel')
                    ->schema([
                        Placeholder::make('rules_display')
                            ->label('')
                            ->content(function (?Collection $record) {
                                if (!$record || !$record->isSmartCollection() || empty($record->rules)) {
                                    return 'No rules defined';
                                }

                                // Human-readable column names
                                $columnLabels = [
                                    'TAG' => 'Tag',
                                    'TITLE' => 'Title',
                                    'TYPE' => 'Product Type',
                                    'VENDOR' => 'Vendor',
                                    'VARIANT_PRICE' => 'Price',
                                    'VARIANT_COMPARE_AT_PRICE' => 'Compare at Price',
                                    'VARIANT_WEIGHT' => 'Weight',
                                    'VARIANT_INVENTORY' => 'Inventory',
                                    'VARIANT_TITLE' => 'Variant Title',
                                    'IS_PRICE_REDUCED' => 'On Sale',
                                ];

                                // Human-readable relations
                                $relationLabels = [
                                    'EQUALS' => 'equals',
                                    'NOT_EQUALS' => 'does not equal',
                                    'GREATER_THAN' => 'is greater than',
                                    'LESS_THAN' => 'is less than',
                                    'STARTS_WITH' => 'starts with',
                                    'ENDS_WITH' => 'ends with',
                                    'CONTAINS' => 'contains',
                                    'NOT_CONTAINS' => 'does not contain',
                                ];

                                $html = '<div style="display: flex; flex-direction: column; gap: 8px;">';
                                foreach ($record->rules as $rule) {
                                    $column = $rule['column'] ?? 'Unknown';
                                    $relation = $rule['relation'] ?? '';
                                    $condition = $rule['condition'] ?? '';

                                    $columnLabel = $columnLabels[$column] ?? ucwords(strtolower(str_replace('_', ' ', $column)));
                                    $relationLabel = $relationLabels[$relation] ?? strtolower(str_replace('_', ' ', $relation));

                                    $html .= '<div style="display: flex; align-items: center; gap: 8px; padding: 10px 14px; background: #f3f4f6; border-radius: 8px; border-left: 3px solid #6366f1;">';
                                    $html .= '<span style="font-weight: 600; color: #374151;">' . htmlspecialchars($columnLabel) . '</span>';
                                    $html .= '<span style="color: #6b7280;">' . htmlspecialchars($relationLabel) . '</span>';
                                    $html .= '<span style="font-weight: 500; color: #4f46e5; background: #e0e7ff; padding: 2px 8px; border-radius: 4px;">' . htmlspecialchars($condition) . '</span>';
                                    $html .= '</div>';
                                }
                                $html .= '</div>';

                                return new HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (?Collection $record) => $record?->isSmartCollection())
                    ->collapsible()
                    ->columnSpanFull(),

                Section::make(fn (?Collection $record) => 'Products in Collection (' . ($record?->products_count ?? 0) . ')')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        Placeholder::make('products_list')
                            ->label('')
                            ->content(function (?Collection $record) {
                                if (!$record) return '';

                                $products = $record->products()->limit(24)->get();

                                if ($products->isEmpty()) {
                                    return new HtmlString(
                                        '<div style="text-align: center; padding: 40px; color: #6b7280;">
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width: 48px; height: 48px; margin: 0 auto 12px;">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                                            </svg>
                                            <p style="font-weight: 500;">No products in this collection</p>
                                        </div>'
                                    );
                                }

                                $html = '<div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(120px, 1fr)); gap: 16px;">';
                                foreach ($products as $product) {
                                    // Use featured_image_url or fallback to first image
                                    $image = $product->featured_image_url ?? '';
                                    if (!$image && is_array($product->images) && count($product->images) > 0) {
                                        $image = $product->images[0]['url'] ?? '';
                                    }

                                    $html .= '<div style="background: #f9fafb; border-radius: 12px; padding: 12px; text-align: center; transition: all 0.2s;">';
                                    if ($image) {
                                        $html .= '<img src="' . htmlspecialchars($image) . '" alt="' . htmlspecialchars($product->title) . '"
                                                  style="width: 100%; height: 100px; object-fit: cover; border-radius: 8px; background: #e5e7eb;">';
                                    } else {
                                        $html .= '<div style="width: 100%; height: 100px; background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%); border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#9ca3af" style="width: 32px; height: 32px;">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                    </svg>
                                                  </div>';
                                    }
                                    $html .= '<p style="font-size: 12px; font-weight: 500; margin-top: 8px; color: #374151; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">' . htmlspecialchars($product->title) . '</p>';
                                    $html .= '</div>';
                                }
                                $html .= '</div>';

                                if ($record->products_count > 24) {
                                    $remaining = $record->products_count - 24;
                                    $html .= '<div style="text-align: center; margin-top: 16px; padding: 12px; background: #f3f4f6; border-radius: 8px; color: #6b7280;">
                                                <span style="font-weight: 500;">+' . $remaining . ' more products</span> in this collection
                                              </div>';
                                }

                                return new HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn (?Collection $record) => $record !== null)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('image_url')
                    ->label('')
                    ->formatStateUsing(function (?string $state) {
                        if (!$state) {
                            return new HtmlString('<img src="https://placehold.co/50x50/e5e7eb/9ca3af?text=No" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">');
                        }
                        return new HtmlString('<img src="' . htmlspecialchars($state) . '" style="width: 50px; height: 50px; border-radius: 50%; object-fit: cover;">');
                    })
                    ->html(),

                TextColumn::make('title')
                    ->label('Collection')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Collection $record) => $record->handle),

                TextColumn::make('store.shop_domain')
                    ->label('Store')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('collection_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'smart' => 'info',
                        'custom' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => ucfirst($state)),

                TextColumn::make('products_count')
                    ->label('Products')
                    ->numeric()
                    ->sortable()
                    ->badge()
                    ->color('gray'),

                TextColumn::make('published_at')
                    ->label('Published')
                    ->dateTime('M d, Y')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M d, Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('store_id')
                    ->label('Store')
                    ->options(Store::pluck('shop_domain', 'id')->toArray())
                    ->searchable(),

                SelectFilter::make('collection_type')
                    ->label('Type')
                    ->options([
                        'smart' => 'Smart Collection',
                        'custom' => 'Custom Collection',
                    ]),
            ])
            ->actions([
                ViewAction::make(),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('title');
    }

    public static function getRelations(): array
    {
        return [
            \LaravelShopifySdk\Filament\Resources\CollectionResource\RelationManagers\ProductsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCollections::route('/'),
            'create' => Pages\CreateCollection::route('/create'),
            'view' => Pages\ViewCollection::route('/{record}'),
            'edit' => Pages\EditCollection::route('/{record}/edit'),
        ];
    }
}
