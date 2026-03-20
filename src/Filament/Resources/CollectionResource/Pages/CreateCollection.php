<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\CollectionResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Str;
use LaravelShopifySdk\Filament\Resources\CollectionResource;
use LaravelShopifySdk\Models\Store;

class CreateCollection extends CreateRecord
{
    protected static string $resource = CollectionResource::class;

    protected array $productIds = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Generate handle if not provided
        if (empty($data['handle'])) {
            $data['handle'] = Str::slug($data['title']);
        }

        // Handle uploaded image - convert to public URL
        if (!empty($data['image_upload'])) {
            $path = $data['image_upload'];
            $data['image_url'] = '/storage/' . $path;
        }
        unset($data['image_upload']);

        // Store product_ids for afterCreate
        $this->productIds = $data['product_ids'] ?? [];
        unset($data['product_ids']);

        // Set default collection type
        $data['collection_type'] = 'custom';
        $data['products_count'] = count($this->productIds);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Sync products to collection
        if (!empty($this->productIds)) {
            $this->record->products()->sync($this->productIds);
        }
    }

    protected function pushToShopify(): void
    {
        $collection = $this->record;
        $store = Store::find($collection->store_id);

        if (!$store) {
            Notification::make()
                ->title('Store not found')
                ->danger()
                ->send();
            return;
        }

        $graphql = app(\LaravelShopifySdk\Clients\GraphQLClient::class);

        $mutation = <<<GQL
        mutation collectionCreate(\$input: CollectionInput!) {
            collectionCreate(input: \$input) {
                collection {
                    id
                    title
                    handle
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $variables = [
            'input' => [
                'title' => $collection->title,
                'handle' => $collection->handle,
                'descriptionHtml' => $collection->description_html ?? $collection->description ?? '',
            ],
        ];

        // Add image if provided
        if ($collection->image_url) {
            $variables['input']['image'] = [
                'src' => $collection->image_url,
            ];
        }

        try {
            $response = $graphql->query($store, $mutation, $variables);

            if (!empty($response['data']['collectionCreate']['userErrors'])) {
                $errors = collect($response['data']['collectionCreate']['userErrors'])
                    ->pluck('message')
                    ->join(', ');

                Notification::make()
                    ->title('Shopify Error')
                    ->body($errors)
                    ->danger()
                    ->send();
                return;
            }

            $shopifyId = $response['data']['collectionCreate']['collection']['id'] ?? null;

            if ($shopifyId) {
                $collection->update(['shopify_id' => $shopifyId]);

                Notification::make()
                    ->title('Collection Created')
                    ->body('Collection pushed to Shopify successfully!')
                    ->success()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        // Redirect to edit page so user can use "Push to Shopify" action
        return $this->getResource()::getUrl('edit', ['record' => $this->record]);
    }
}
