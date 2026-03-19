<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Split;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Schema;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Cache;
use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Filament\NavigationGroup;
use LaravelShopifySdk\Filament\NavigationIcon;
use LaravelShopifySdk\Filament\Resources\ProductResource\Pages;
use LaravelShopifySdk\Models\Product;
use LaravelShopifySdk\Models\Store;
use LaravelShopifySdk\Services\ProductService;
use BackedEnum;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    protected static string|\BackedEnum|null $navigationIcon = NavigationIcon::OutlinedCube;

    protected static \UnitEnum|string|null $navigationGroup = NavigationGroup::Shopify;

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                // Product Header with Image
                Section::make()
                    ->schema([
                        Placeholder::make('product_header')
                            ->label('')
                            ->content(function (?Product $record) {
                                if (!$record) {
                                    return '';
                                }
                                $imageUrl = $record->image_url ?? 'https://via.placeholder.com/120x120?text=No+Image';
                                $status = $record->status ?? 'DRAFT';
                                $statusColor = match (strtoupper($status)) {
                                    'ACTIVE' => 'bg-green-100 text-green-800',
                                    'ARCHIVED' => 'bg-red-100 text-red-800',
                                    default => 'bg-yellow-100 text-yellow-800',
                                };
                                $variantCount = $record->variants()->count();

                                $statusBg = match (strtoupper($status)) {
                                    'ACTIVE' => 'background: #dcfce7; color: #166534;',
                                    'ARCHIVED' => 'background: #fee2e2; color: #991b1b;',
                                    default => 'background: #fef9c3; color: #854d0e;',
                                };

                                return new \Illuminate\Support\HtmlString('
                                    <div style="display: flex; align-items: flex-start; gap: 16px;">
                                        <img src="' . $imageUrl . '" style="width: 56px; height: 56px; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb;" />
                                        <div style="flex: 1;">
                                            <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 4px;">
                                                <span style="padding: 2px 8px; font-size: 11px; font-weight: 500; border-radius: 9999px; ' . $statusBg . '">' . ucfirst(strtolower($status)) . '</span>
                                                <span style="font-size: 12px; color: #6b7280;">' . $variantCount . ' variant(s)</span>
                                            </div>
                                            <p style="font-size: 11px; color: #9ca3af; font-family: monospace; margin: 0;">' . $record->shopify_id . '</p>
                                            ' . ($record->handle ? '<p style="font-size: 12px; color: #6b7280; margin: 4px 0 0 0;">/' . $record->handle . '</p>' : '') . '
                                        </div>
                                    </div>
                                ');
                            })
                            ->columnSpanFull(),
                    ])
                    ->visible(fn (?Product $record) => $record !== null)
                    ->columnSpanFull(),

                // Basic Info
                Section::make('Basic Information')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        TextInput::make('title')
                            ->label('Product Title')
                            ->required()
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-tag')
                            ->columnSpanFull(),
                        TextInput::make('handle')
                            ->label('URL Handle')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-link')
                            ->helperText('Used in product URL: /products/{handle}')
                            ->columnSpan(['default' => 'full', 'md' => 1]),
                        Select::make('status')
                            ->options([
                                'ACTIVE' => 'Active',
                                'ARCHIVED' => 'Archived',
                                'DRAFT' => 'Draft',
                            ])
                            ->default('DRAFT')
                            ->native(false)
                            ->required()
                            ->columnSpan(['default' => 'full', 'md' => 1]),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->columnSpanFull(),

                // Organization
                Section::make('Organization')
                    ->icon('heroicon-o-folder')
                    ->schema([
                        TextInput::make('vendor')
                            ->label('Vendor')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-building-office')
                            ->columnSpan(['default' => 'full', 'md' => 1]),
                        TextInput::make('product_type')
                            ->label('Product Type')
                            ->maxLength(255)
                            ->prefixIcon('heroicon-o-squares-2x2')
                            ->columnSpan(['default' => 'full', 'md' => 1]),
                        TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Add tags...')
                            ->separator(',')
                            ->splitKeys(['Tab', ','])
                            ->columnSpanFull(),
                    ])
                    ->columns(['default' => 1, 'md' => 2])
                    ->collapsible()
                    ->columnSpanFull(),

                // Description
                Section::make('Description')
                    ->description('Product description displayed on your store')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        RichEditor::make('description_html')
                            ->label('')
                            ->toolbarButtons([
                                'bold',
                                'italic',
                                'underline',
                                'strike',
                                'h2',
                                'h3',
                                'bulletList',
                                'orderedList',
                                'link',
                                'undo',
                                'redo',
                            ])
                            ->columnSpanFull()
                            ->formatStateUsing(fn (?Product $record) => $record?->payload['descriptionHtml'] ?? $record?->payload['description'] ?? '')
                            ->dehydrated(false),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                // Media Gallery
                Section::make('Media')
                    ->description(fn (?Product $record) => count($record?->images ?? []) . ' image(s)')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Placeholder::make('images_gallery')
                            ->label('')
                            ->content(function (?Product $record) {
                                if (!$record || empty($record->images)) {
                                    return new \Illuminate\Support\HtmlString('
                                        <div style="text-align: center; padding: 2rem; color: #9ca3af;">
                                            <svg style="margin: 0 auto; height: 3rem; width: 3rem; color: #9ca3af;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                            </svg>
                                            <p style="margin-top: 0.5rem; font-size: 0.875rem;">No images available</p>
                                        </div>
                                    ');
                                }

                                $productId = $record->id;
                                $html = '<div style="display: flex; flex-wrap: wrap; gap: 12px;">';

                                foreach ($record->images as $index => $image) {
                                    $url = $image['url'] ?? '';
                                    $alt = htmlspecialchars($image['altText'] ?? 'Product image ' . ($index + 1));
                                    $mainBadge = $index === 0
                                        ? '<span style="position: absolute; top: -6px; left: -6px; background: #3b82f6; color: white; font-size: 10px; padding: 2px 6px; border-radius: 9999px; font-weight: 500; z-index: 10;">Main</span>'
                                        : '';

                                    $html .= '
                                    <div style="position: relative; width: 72px; height: 72px;">
                                        <a href="' . $url . '" target="_blank" style="display: block; width: 100%; height: 100%;">
                                            <img
                                                src="' . $url . '"
                                                alt="' . $alt . '"
                                                style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px; border: 1px solid #e5e7eb; cursor: pointer;"
                                            />
                                        </a>
                                        ' . $mainBadge . '
                                    </div>';
                                }

                                $html .= '</div>
                                <p style="font-size: 12px; color: #9ca3af; margin-top: 12px;">Click on an image to open in new tab. Use Shopify Admin to add or delete images.</p>';

                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn (?Product $record) => $record !== null)
                    ->columnSpanFull(),

                // SEO
                Section::make('Search Engine Optimization')
                    ->description('Optimize how this product appears in search results')
                    ->icon('heroicon-o-magnifying-glass')
                    ->schema([
                        TextInput::make('seo_title')
                            ->label('Page Title')
                            ->maxLength(70)
                            ->helperText(fn ($state) => strlen($state ?? '') . '/70 characters')
                            ->formatStateUsing(fn (?Product $record) => $record?->payload['seo']['title'] ?? '')
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Textarea::make('seo_description')
                            ->label('Meta Description')
                            ->rows(3)
                            ->maxLength(160)
                            ->helperText(fn ($state) => strlen($state ?? '') . '/160 characters')
                            ->formatStateUsing(fn (?Product $record) => $record?->payload['seo']['description'] ?? '')
                            ->dehydrated(false)
                            ->columnSpanFull(),
                        Placeholder::make('seo_preview')
                            ->label('Search Preview')
                            ->content(function (?Product $record) {
                                $title = $record?->payload['seo']['title'] ?? $record?->title ?? 'Product Title';
                                $description = $record?->payload['seo']['description'] ?? 'Product description will appear here...';
                                $url = $record?->store?->custom_domain
                                    ? $record->store->getProductUrl($record->handle ?? 'product')
                                    : 'https://your-store.com/products/' . ($record?->handle ?? 'product');

                                return new \Illuminate\Support\HtmlString('
                                    <div class="bg-white p-4 rounded-lg border max-w-xl">
                                        <p class="text-blue-600 text-lg hover:underline cursor-pointer">' . htmlspecialchars($title) . '</p>
                                        <p class="text-green-700 text-sm">' . htmlspecialchars($url) . '</p>
                                        <p class="text-gray-600 text-sm mt-1">' . htmlspecialchars(substr($description, 0, 160)) . '</p>
                                    </div>
                                ');
                            })
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible()
                    ->columnSpanFull(),

                // Raw Payload
                Section::make('Raw Data')
                    ->description('Complete JSON payload from Shopify API')
                    ->icon('heroicon-o-code-bracket')
                    ->schema([
                        Textarea::make('payload')
                            ->label('')
                            ->rows(25)
                            ->columnSpanFull()
                            ->formatStateUsing(fn ($state) => is_array($state) ? json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) : $state)
                            ->disabled()
                            ->extraAttributes(['class' => 'font-mono text-xs']),
                    ])
                    ->collapsed()
                    ->collapsible()
                    ->visible(fn (?Product $record) => $record !== null)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        $isSandboxMode = config('shopify.filament.testing_crud_enabled', false);

        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('store.shop_domain')
                    ->label('Store')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->visible(fn () => Store::count() > 1),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(50)
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('vendor')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('product_type')
                    ->label('Type')
                    ->searchable()
                    ->sortable()
                    ->toggleable()
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => ucfirst(strtolower($state ?? '')))
                    ->color(fn ($state): string => match (strtoupper($state ?? '')) {
                        'ACTIVE' => 'success',
                        'ARCHIVED' => 'danger',
                        'DRAFT' => 'warning',
                        default => 'gray',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('variants_count')
                    ->counts('variants')
                    ->label('Variants')
                    ->sortable(),
                Tables\Columns\TextColumn::make('shopify_updated_at')
                    ->label('Shopify Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('store_id')
                    ->label('Store')
                    ->relationship('store', 'shop_domain')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => Store::count() > 1),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'ACTIVE' => 'Active',
                        'ARCHIVED' => 'Archived',
                        'DRAFT' => 'Draft',
                    ]),
                Tables\Filters\SelectFilter::make('vendor')
                    ->options(fn () => Cache::remember(
                        'shopify_product_vendors',
                        300,
                        fn () => Product::query()
                            ->whereNotNull('vendor')
                            ->where('vendor', '!=', '')
                            ->distinct()
                            ->orderBy('vendor')
                            ->pluck('vendor', 'vendor')
                            ->toArray()
                    ))
                    ->searchable(),
                Tables\Filters\SelectFilter::make('product_type')
                    ->options(fn () => Cache::remember(
                        'shopify_product_types',
                        300,
                        fn () => Product::query()
                            ->whereNotNull('product_type')
                            ->where('product_type', '!=', '')
                            ->distinct()
                            ->orderBy('product_type')
                            ->pluck('product_type', 'product_type')
                            ->toArray()
                    ))
                    ->searchable(),
            ])
            ->actions([
                ActionGroup::make([
                    Action::make('view_on_website')
                        ->label('View on Website')
                        ->icon('heroicon-o-arrow-top-right-on-square')
                        ->color('gray')
                        ->url(fn (Product $record): ?string => $record->handle && $record->store
                            ? $record->store->getProductUrl($record->handle)
                            : null)
                        ->openUrlInNewTab()
                        ->visible(fn (Product $record): bool => filled($record->handle)),
                    Action::make('sync_from_shopify')
                        ->label('Sync from Shopify')
                        ->icon('heroicon-o-arrow-down-tray')
                        ->color('info')
                        ->requiresConfirmation()
                        ->modalHeading('Sync Product from Shopify')
                        ->modalDescription('This will fetch the latest product data from Shopify and update the local record.')
                        ->action(function (Product $record) {
                            try {
                                $service = new ProductService(new GraphQLClient());
                                $service->fetch($record);
                                Notification::make()
                                    ->title('Product Synced')
                                    ->body('Product data has been updated from Shopify.')
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
                    ViewAction::make(),
                    EditAction::make(),
                    Action::make('update_on_shopify')
                        ->label('Push to Shopify')
                        ->icon('heroicon-o-arrow-up-tray')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Push Changes to Shopify')
                        ->modalDescription('This will update the product on Shopify with the current local data.')
                        ->action(function (Product $record) {
                            try {
                                $service = new ProductService(new GraphQLClient());
                                $service->update($record, [
                                    'title' => $record->title,
                                    'handle' => $record->handle,
                                    'vendor' => $record->vendor,
                                    'productType' => $record->product_type,
                                    'status' => $record->status,
                                ]);
                                Notification::make()
                                    ->title('Product Updated')
                                    ->body('Product has been updated on Shopify.')
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
                    Action::make('delete_from_shopify')
                        ->label('Delete from Shopify')
                        ->icon('heroicon-o-trash')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->modalHeading('Delete Product from Shopify')
                        ->modalDescription('This will permanently delete the product from Shopify AND remove the local record. This action cannot be undone.')
                        ->modalSubmitActionLabel('Yes, Delete Permanently')
                        ->action(function (Product $record) {
                            try {
                                $service = new ProductService(new GraphQLClient());
                                $service->delete($record);
                                Notification::make()
                                    ->title('Product Deleted')
                                    ->body('Product has been deleted from Shopify.')
                                    ->success()
                                    ->send();
                            } catch (\Exception $e) {
                                Notification::make()
                                    ->title('Delete Failed')
                                    ->body($e->getMessage())
                                    ->danger()
                                    ->send();
                            }
                        }),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->visible($isSandboxMode),
                ]),
            ])
            ->defaultSort('shopify_updated_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            ProductResource\RelationManagers\VariantsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }
}
