<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\ProductResource\Pages;

use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use LaravelShopifySdk\Filament\Resources\ProductResource;
use LaravelShopifySdk\Models\Store;

class CreateProduct extends CreateRecord
{
    protected static string $resource = ProductResource::class;

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
                // Store Selection
                Section::make('Store')
                    ->schema([
                        Select::make('store_id')
                            ->label('Store')
                            ->options(Store::pluck('shop_domain', 'id'))
                            ->required()
                            ->default(fn () => Store::first()?->id)
                            ->native(false)
                            ->searchable()
                            ->columnSpanFull(),
                    ])
                    ->columnSpanFull(),

                // Basic Information
                Section::make('Basic Information')
                    ->icon('heroicon-o-cube')
                    ->schema([
                        TextInput::make('title')
                            ->label('Product Title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),

                        TextInput::make('handle')
                            ->label('URL Handle')
                            ->maxLength(255)
                            ->helperText('Leave empty to auto-generate from title')
                            ->columnSpan(1),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'DRAFT' => 'Draft',
                                'ACTIVE' => 'Active',
                                'ARCHIVED' => 'Archived',
                            ])
                            ->default('DRAFT')
                            ->required()
                            ->native(false)
                            ->columnSpan(1),

                        TextInput::make('vendor')
                            ->label('Vendor')
                            ->maxLength(255)
                            ->columnSpan(1),

                        TextInput::make('product_type')
                            ->label('Product Type')
                            ->maxLength(255)
                            ->columnSpan(1),

                        TagsInput::make('tags')
                            ->label('Tags')
                            ->placeholder('Add tags...')
                            ->separator(',')
                            ->splitKeys(['Tab', ','])
                            ->columnSpanFull(),

                        RichEditor::make('description_html')
                            ->label('Description')
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
                            ->columnSpanFull(),
                    ])
                    ->columns(2)
                    ->columnSpanFull(),

                // Media
                Section::make('Media')
                    ->icon('heroicon-o-photo')
                    ->description('Upload images or provide URLs. Images will be uploaded to Shopify when product is pushed.')
                    ->schema([
                        FileUpload::make('uploaded_images')
                            ->label('Upload Images')
                            ->image()
                            ->multiple()
                            ->maxFiles(10)
                            ->maxSize(5120) // 5MB
                            ->disk('public')
                            ->directory('shopify-uploads')
                            ->visibility('public')
                            ->imagePreviewHeight('100')
                            ->panelLayout('grid')
                            ->reorderable()
                            ->columnSpanFull(),

                        Repeater::make('media')
                            ->label('Or Add Image URLs')
                            ->schema([
                                TextInput::make('url')
                                    ->label('Image URL')
                                    ->url()
                                    ->required()
                                    ->placeholder('https://example.com/image.jpg')
                                    ->helperText('Must be a publicly accessible URL')
                                    ->columnSpan(2),
                                TextInput::make('alt')
                                    ->label('Alt Text')
                                    ->maxLength(255)
                                    ->placeholder('Describe the image')
                                    ->columnSpan(1),
                            ])
                            ->columns(3)
                            ->addActionLabel('Add Image URL')
                            ->reorderable()
                            ->collapsible()
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                // Variants
                Section::make('Variants')
                    ->icon('heroicon-o-squares-plus')
                    ->description('Add product variants with different options, prices, and inventory. Leave empty for a simple product.')
                    ->schema([
                        Checkbox::make('has_variants')
                            ->label('This product has multiple variants (e.g., different sizes or colors)')
                            ->reactive()
                            ->columnSpanFull(),

                        Repeater::make('variants')
                            ->label('Product Variants')
                            ->schema([
                                TextInput::make('option1')
                                    ->label('Option 1 (e.g., Size)')
                                    ->placeholder('Small, Medium, Large...')
                                    ->columnSpan(1),
                                TextInput::make('option2')
                                    ->label('Option 2 (e.g., Color)')
                                    ->placeholder('Red, Blue, Green...')
                                    ->columnSpan(1),
                                TextInput::make('option3')
                                    ->label('Option 3')
                                    ->placeholder('Optional...')
                                    ->columnSpan(1),
                                TextInput::make('price')
                                    ->label('Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->required()
                                    ->placeholder('0.00')
                                    ->columnSpan(1),
                                TextInput::make('compare_at_price')
                                    ->label('Compare at Price')
                                    ->numeric()
                                    ->prefix('$')
                                    ->placeholder('0.00')
                                    ->columnSpan(1),
                                TextInput::make('sku')
                                    ->label('SKU')
                                    ->maxLength(255)
                                    ->placeholder('SKU-001')
                                    ->columnSpan(1),
                                TextInput::make('barcode')
                                    ->label('Barcode')
                                    ->maxLength(255)
                                    ->placeholder('UPC, ISBN...')
                                    ->columnSpan(1),
                                TextInput::make('inventory_quantity')
                                    ->label('Inventory')
                                    ->numeric()
                                    ->default(0)
                                    ->columnSpan(1),
                                TextInput::make('weight')
                                    ->label('Weight')
                                    ->numeric()
                                    ->placeholder('0.0')
                                    ->columnSpan(1),
                            ])
                            ->columns(3)
                            ->addActionLabel('Add Variant')
                            ->reorderable()
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string =>
                                collect([$state['option1'] ?? null, $state['option2'] ?? null, $state['option3'] ?? null])
                                    ->filter()
                                    ->join(' / ') ?: 'New Variant'
                            )
                            ->defaultItems(0)
                            ->visible(fn (callable $get) => $get('has_variants'))
                            ->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->columnSpanFull(),

                // Default Variant Pricing (for simple products)
                Section::make('Pricing & Inventory')
                    ->icon('heroicon-o-currency-dollar')
                    ->description('Set pricing for simple products without variants.')
                    ->visible(fn (callable $get) => !$get('has_variants'))
                    ->schema([
                        TextInput::make('price')
                            ->label('Price')
                            ->numeric()
                            ->prefix('$')
                            ->placeholder('0.00')
                            ->columnSpan(1),

                        TextInput::make('compare_at_price')
                            ->label('Compare at Price')
                            ->numeric()
                            ->prefix('$')
                            ->placeholder('0.00')
                            ->helperText('Original price before discount')
                            ->columnSpan(1),

                        TextInput::make('sku')
                            ->label('SKU')
                            ->maxLength(255)
                            ->placeholder('Stock Keeping Unit')
                            ->columnSpan(1),

                        TextInput::make('barcode')
                            ->label('Barcode')
                            ->maxLength(255)
                            ->placeholder('ISBN, UPC, GTIN, etc.')
                            ->columnSpan(1),

                        Checkbox::make('track_inventory')
                            ->label('Track inventory')
                            ->default(true)
                            ->columnSpan(1),

                        Checkbox::make('requires_shipping')
                            ->label('This is a physical product')
                            ->default(true)
                            ->columnSpan(1),

                        TextInput::make('weight')
                            ->label('Weight')
                            ->numeric()
                            ->placeholder('0.0')
                            ->columnSpan(1),

                        Select::make('weight_unit')
                            ->label('Weight Unit')
                            ->options([
                                'KILOGRAMS' => 'kg',
                                'GRAMS' => 'g',
                                'POUNDS' => 'lb',
                                'OUNCES' => 'oz',
                            ])
                            ->default('KILOGRAMS')
                            ->native(false)
                            ->columnSpan(1),
                    ])
                    ->columns(2)
                    ->collapsible()
                    ->columnSpanFull(),

                // SEO
                Section::make('Search Engine Optimization')
                    ->icon('heroicon-o-magnifying-glass')
                    ->description('Customize how this product appears in search results')
                    ->schema([
                        TextInput::make('seo_title')
                            ->label('Page Title')
                            ->maxLength(70)
                            ->helperText(fn ($state) => strlen($state ?? '') . '/70 characters')
                            ->columnSpanFull(),

                        Textarea::make('seo_description')
                            ->label('Meta Description')
                            ->rows(3)
                            ->maxLength(160)
                            ->helperText(fn ($state) => strlen($state ?? '') . '/160 characters')
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible()
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate a local placeholder Shopify ID (will be replaced when pushed to Shopify)
        $data['shopify_id'] = 'local_' . uniqid();

        // Generate handle from title if not provided
        if (empty($data['handle'])) {
            $data['handle'] = \Illuminate\Support\Str::slug($data['title']);
        }

        // Build media array from both uploaded files and URL inputs
        $mediaItems = [];

        // Handle uploaded images - convert to public URLs
        if (!empty($data['uploaded_images'])) {
            foreach ($data['uploaded_images'] as $path) {
                $publicUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
                // Make URL absolute if needed
                if (!str_starts_with($publicUrl, 'http')) {
                    $publicUrl = config('app.url') . $publicUrl;
                }
                $mediaItems[] = [
                    'originalSource' => $publicUrl,
                    'alt' => '',
                    'mediaContentType' => 'IMAGE',
                    'localPath' => $path, // Keep local path for staged upload
                ];
            }
        }

        // Handle URL-based images
        if (!empty($data['media'])) {
            foreach ($data['media'] as $item) {
                if (!empty($item['url'])) {
                    $mediaItems[] = [
                        'originalSource' => $item['url'],
                        'alt' => $item['alt'] ?? '',
                        'mediaContentType' => 'IMAGE',
                    ];
                }
            }
        }

        // Build variants array
        $variants = [];
        $hasVariants = $data['has_variants'] ?? false;

        if ($hasVariants && !empty($data['variants'])) {
            foreach ($data['variants'] as $variant) {
                $variantData = [
                    'price' => $variant['price'] ?? null,
                    'compareAtPrice' => $variant['compare_at_price'] ?? null,
                    'sku' => $variant['sku'] ?? null,
                    'barcode' => $variant['barcode'] ?? null,
                    'weight' => $variant['weight'] ?? null,
                    'weightUnit' => $data['weight_unit'] ?? 'KILOGRAMS',
                    'requiresShipping' => $data['requires_shipping'] ?? true,
                    'inventoryManagement' => ($data['track_inventory'] ?? true) ? 'SHOPIFY' : null,
                    'inventoryQuantity' => (int) ($variant['inventory_quantity'] ?? 0),
                ];

                // Add options
                $options = [];
                if (!empty($variant['option1'])) $options[] = $variant['option1'];
                if (!empty($variant['option2'])) $options[] = $variant['option2'];
                if (!empty($variant['option3'])) $options[] = $variant['option3'];
                $variantData['options'] = $options;

                $variants[] = $variantData;
            }
        }

        // Build comprehensive payload for Shopify API
        $data['payload'] = [
            'title' => $data['title'],
            'handle' => $data['handle'],
            'descriptionHtml' => $data['description_html'] ?? '',
            'vendor' => $data['vendor'] ?? '',
            'productType' => $data['product_type'] ?? '',
            'tags' => $data['tags'] ?? [],
            'status' => $data['status'] ?? 'DRAFT',

            // Media (images)
            'media' => $mediaItems,

            // Variants (if has_variants is true)
            'variants' => $variants,
            'hasVariants' => $hasVariants,

            // Default variant pricing (for simple products without variants)
            'defaultVariant' => [
                'price' => $data['price'] ?? null,
                'compareAtPrice' => $data['compare_at_price'] ?? null,
                'sku' => $data['sku'] ?? null,
                'barcode' => $data['barcode'] ?? null,
                'weight' => $data['weight'] ?? null,
                'weightUnit' => $data['weight_unit'] ?? 'KILOGRAMS',
                'requiresShipping' => $data['requires_shipping'] ?? true,
                'inventoryManagement' => ($data['track_inventory'] ?? true) ? 'SHOPIFY' : null,
            ],

            // SEO
            'seo' => [
                'title' => $data['seo_title'] ?? null,
                'description' => $data['seo_description'] ?? null,
            ],
        ];

        // Clean up fields that shouldn't be stored in main columns
        unset(
            $data['description_html'],
            $data['uploaded_images'],
            $data['media'],
            $data['has_variants'],
            $data['variants'],
            $data['price'],
            $data['compare_at_price'],
            $data['sku'],
            $data['barcode'],
            $data['weight'],
            $data['weight_unit'],
            $data['track_inventory'],
            $data['requires_shipping'],
            $data['seo_title'],
            $data['seo_description']
        );

        return $data;
    }

    protected function getCreatedNotification(): ?Notification
    {
        return Notification::make()
            ->success()
            ->title('Product Created Locally')
            ->body('Product saved to database. Use "Push to Shopify" to create it on your store.');
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }
}
