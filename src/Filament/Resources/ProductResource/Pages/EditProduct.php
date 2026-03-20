<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\ProductResource\Pages;

use Filament\Actions;
use Filament\Actions\Action as FormAction;
use Filament\Schemas\Components\Actions as FormActions;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\View;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Filament\Resources\ProductResource;
use LaravelShopifySdk\Models\Product;
use LaravelShopifySdk\Services\ProductService;

class EditProduct extends EditRecord
{
    protected static string $resource = ProductResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('pull_from_shopify')
                ->label('Pull from Shopify')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Pull from Shopify')
                ->modalDescription('This will overwrite local data with the latest data from Shopify. Any unsaved changes will be lost.')
                ->modalSubmitActionLabel('Yes, Pull Latest')
                ->visible(fn () => !str_starts_with($this->record->shopify_id, 'local_'))
                ->action(function () {
                    try {
                        $service = new ProductService(new GraphQLClient());
                        $service->fetch($this->record);

                        Notification::make()
                            ->title('Product Synced')
                            ->body('Product data has been updated from Shopify.')
                            ->success()
                            ->send();

                        $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Sync Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->components([
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
                            ->columnSpan(1),

                        Select::make('status')
                            ->label('Status')
                            ->options([
                                'DRAFT' => 'Draft',
                                'ACTIVE' => 'Active',
                                'ARCHIVED' => 'Archived',
                            ])
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

                // Current Images
                Section::make('Current Images')
                    ->icon('heroicon-o-photo')
                    ->description('Click on images to mark them for removal.')
                    ->schema([
                        Placeholder::make('images_with_checkboxes')
                            ->label('')
                            ->content(function (?Product $record) {
                                if (!$record) return 'No images';
                                $images = $record->payload['images']['edges'] ?? [];
                                if (empty($images)) {
                                    return new \Illuminate\Support\HtmlString('<p style="color: #9ca3af;">No images uploaded yet.</p>');
                                }

                                $formId = 'image-removal-' . $record->id;

                                $html = '<style>
                                    .image-card { transition: all 0.2s; border-radius: 8px; border: 2px solid #e5e7eb; overflow: hidden; background: white; }
                                    .image-card:hover { border-color: #9ca3af; }
                                    .image-card.selected { border-color: #dc2626; background: #fef2f2; }
                                    .image-card.selected img { opacity: 0.5; }
                                    .image-card-inner { display: flex; align-items: center; gap: 8px; padding: 8px; cursor: pointer; }
                                    .image-card-inner input[type="checkbox"] { width: 18px; height: 18px; cursor: pointer; accent-color: #dc2626; flex-shrink: 0; }
                                    .image-card-inner span { font-size: 12px; color: #374151; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
                                </style>
                                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 12px;">';

                                foreach ($images as $index => $edge) {
                                    $image = $edge['node'];
                                    $url = $image['url'] ?? '';
                                    $alt = htmlspecialchars($image['altText'] ?? 'Image ' . ($index + 1));
                                    $id = htmlspecialchars($image['id'] ?? '');
                                    $cardId = 'card-' . $index . '-' . $record->id;

                                    $html .= '<div class="image-card" id="' . $cardId . '">
                                        <img src="' . htmlspecialchars($url) . '" alt="' . $alt . '" style="width: 100%; height: 120px; object-fit: contain; display: block; background: #f9fafb;" />
                                        <label class="image-card-inner" onclick="document.getElementById(\'' . $cardId . '\').classList.toggle(\'selected\')">
                                            <input type="checkbox" name="images_to_remove_hidden[]" value="' . $id . '" onchange="updateHiddenField()">
                                            <span>' . $alt . '</span>
                                        </label>
                                    </div>';
                                }

                                $html .= '</div>
                                <p style="font-size: 12px; color: #dc2626; margin-top: 12px;">⚠️ Checked images will be deleted from Shopify when you click "Push Changes".</p>
                                <script>
                                    function updateHiddenField() {
                                        const checkboxes = document.querySelectorAll(\'input[name="images_to_remove_hidden[]"]:checked\');
                                        const ids = Array.from(checkboxes).map(cb => cb.value);
                                        const hiddenInput = document.querySelector(\'input[name="data[images_to_remove]"]\');
                                        if (hiddenInput) {
                                            hiddenInput.value = JSON.stringify(ids);
                                        }
                                    }
                                </script>';

                                return new \Illuminate\Support\HtmlString($html);
                            })
                            ->columnSpanFull(),

                        \Filament\Forms\Components\Hidden::make('images_to_remove')
                            ->default('[]'),

                        FormActions::make([
                            FormAction::make('remove_selected_images')
                                ->label('Remove Selected Images')
                                ->icon('heroicon-o-trash')
                                ->color('danger')
                                ->requiresConfirmation()
                                ->modalHeading('Remove Selected Images')
                                ->modalDescription('Are you sure you want to permanently delete the selected images from Shopify? This action cannot be undone.')
                                ->modalSubmitActionLabel('Yes, Remove Images')
                                ->action(function ($get, $set, $record) {
                                    $imagesToRemove = $get('images_to_remove');
                                    if (is_string($imagesToRemove)) {
                                        $imageIds = json_decode($imagesToRemove, true) ?? [];
                                    } else {
                                        $imageIds = is_array($imagesToRemove) ? $imagesToRemove : [];
                                    }

                                    if (empty($imageIds)) {
                                        Notification::make()
                                            ->title('No Images Selected')
                                            ->body('Please select at least one image to remove.')
                                            ->warning()
                                            ->send();
                                        return;
                                    }

                                    try {
                                        $service = new ProductService(new GraphQLClient());
                                        $removedCount = 0;

                                        foreach ($imageIds as $imageId) {
                                            try {
                                                $service->deleteImage($record, $imageId);
                                                $removedCount++;
                                            } catch (\Exception $e) {
                                                // Continue with other images
                                            }
                                        }

                                        // Refresh product from Shopify
                                        $service->fetch($record);

                                        // Clear the selection
                                        $set('images_to_remove', '[]');

                                        Notification::make()
                                            ->title('Images Removed')
                                            ->body("Successfully removed {$removedCount} image(s) from Shopify.")
                                            ->success()
                                            ->send();

                                        // Redirect to refresh the page
                                        redirect(request()->header('Referer'));
                                    } catch (\Exception $e) {
                                        Notification::make()
                                            ->title('Error')
                                            ->body($e->getMessage())
                                            ->danger()
                                            ->send();
                                    }
                                }),
                        ])->columnSpanFull(),
                    ])
                    ->collapsible()
                    ->visible(fn (?Product $record) => $record && !str_starts_with($record->shopify_id, 'local_') && !empty($record->payload['images']['edges']))
                    ->columnSpanFull(),

                // Add New Images
                Section::make('Add New Images')
                    ->icon('heroicon-o-plus-circle')
                    ->description('Upload new images or add via URL.')
                    ->schema([
                        FileUpload::make('new_uploaded_images')
                            ->label('Upload Images')
                            ->image()
                            ->multiple()
                            ->maxFiles(10)
                            ->maxSize(5120)
                            ->disk('public')
                            ->directory('shopify-uploads')
                            ->visibility('public')
                            ->imagePreviewHeight('100')
                            ->panelLayout('grid')
                            ->reorderable()
                            ->columnSpanFull(),

                        Repeater::make('new_image_urls')
                            ->label('Or Add Image URLs')
                            ->schema([
                                TextInput::make('url')
                                    ->label('Image URL')
                                    ->url()
                                    ->required()
                                    ->placeholder('https://example.com/image.jpg')
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
                            ->defaultItems(0)
                            ->columnSpanFull(),
                    ])
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
                            ->columnSpanFull(),

                        Textarea::make('seo_description')
                            ->label('Meta Description')
                            ->rows(3)
                            ->maxLength(160)
                            ->columnSpanFull(),
                    ])
                    ->collapsed()
                    ->collapsible()
                    ->columnSpanFull(),
            ])
            ->columns(2);
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $payload = $data['payload'] ?? [];

        // Extract description from payload
        $data['description_html'] = $payload['descriptionHtml'] ?? '';

        // Extract SEO from payload
        $data['seo_title'] = $payload['seo']['title'] ?? '';
        $data['seo_description'] = $payload['seo']['description'] ?? '';

        // Initialize empty arrays for new images
        $data['new_uploaded_images'] = [];
        $data['new_image_urls'] = [];
        $data['images_to_remove'] = [];

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $payload = $this->record->payload ?? [];

        // Update payload with new description
        $payload['descriptionHtml'] = $data['description_html'] ?? '';

        // Update SEO
        $payload['seo'] = [
            'title' => $data['seo_title'] ?? null,
            'description' => $data['seo_description'] ?? null,
        ];

        // Store new images info in payload for processing when pushing to Shopify
        $newMedia = [];

        // Handle uploaded images
        if (!empty($data['new_uploaded_images'])) {
            foreach ($data['new_uploaded_images'] as $path) {
                $publicUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($path);
                if (!str_starts_with($publicUrl, 'http')) {
                    $publicUrl = config('app.url') . $publicUrl;
                }
                $newMedia[] = [
                    'originalSource' => $publicUrl,
                    'alt' => '',
                    'mediaContentType' => 'IMAGE',
                    'localPath' => $path,
                ];
            }
        }

        // Handle URL-based images
        if (!empty($data['new_image_urls'])) {
            foreach ($data['new_image_urls'] as $item) {
                if (!empty($item['url'])) {
                    $newMedia[] = [
                        'originalSource' => $item['url'],
                        'alt' => $item['alt'] ?? '',
                        'mediaContentType' => 'IMAGE',
                    ];
                }
            }
        }

        // Store pending changes in payload
        $payload['pendingMedia'] = $newMedia;
        // Parse JSON from hidden field (images selected for removal)
        $imagesToRemove = $data['images_to_remove'] ?? '[]';
        if (is_string($imagesToRemove)) {
            $payload['imagesToRemove'] = json_decode($imagesToRemove, true) ?? [];
        } else {
            $payload['imagesToRemove'] = is_array($imagesToRemove) ? $imagesToRemove : [];
        }

        $data['payload'] = $payload;

        // Clean up temporary fields
        unset(
            $data['description_html'],
            $data['new_uploaded_images'],
            $data['new_image_urls'],
            $data['images_to_remove'],
            $data['seo_title'],
            $data['seo_description']
        );

        return $data;
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        // If this is a Shopify product (not local), offer to push changes
        if (!str_starts_with($record->shopify_id, 'local_')) {
            $payload = $record->payload;
            $hasChanges = !empty($payload['pendingMedia']) || !empty($payload['imagesToRemove']);

            if ($hasChanges) {
                Notification::make()
                    ->title('Changes Saved Locally')
                    ->body('Use "Push Changes" action to sync images with Shopify.')
                    ->info()
                    ->send();
            }
        }
    }
}
