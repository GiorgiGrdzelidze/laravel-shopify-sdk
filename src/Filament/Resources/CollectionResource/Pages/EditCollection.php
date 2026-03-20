<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\CollectionResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Str;
use LaravelShopifySdk\Filament\Resources\CollectionResource;
use LaravelShopifySdk\Models\Store;

class EditCollection extends EditRecord
{
    protected static string $resource = CollectionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('push_to_shopify')
                ->label('Push to Shopify')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Push to Shopify')
                ->modalDescription('This will update the collection in Shopify. Continue?')
                ->action(fn () => $this->pushToShopify()),

            Actions\Action::make('delete_from_shopify')
                ->label('Delete from Shopify')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete from Shopify')
                ->modalDescription('This will delete the collection from Shopify but keep it locally. Continue?')
                ->visible(fn () => $this->record->shopify_id !== null)
                ->action(fn () => $this->deleteFromShopify()),

            Actions\Action::make('delete_everywhere')
                ->label('Delete Everywhere')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Delete Collection Everywhere')
                ->modalDescription('This will delete the collection from both Shopify AND your local database. This action cannot be undone!')
                ->action(fn () => $this->deleteEverywhere()),

            Actions\DeleteAction::make()
                ->label('Delete Locally')
                ->modalHeading('Delete Local Collection')
                ->modalDescription('This will only delete the collection from your local database. The collection will remain in Shopify.'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Generate handle if not provided
        if (empty($data['handle'])) {
            $data['handle'] = Str::slug($data['title']);
        }

        // Handle remove image checkbox
        if (!empty($data['remove_image'])) {
            $data['image_url'] = null;
            $this->shouldRemoveImage = true;
        }
        unset($data['remove_image']);

        // If external image_url is provided, use it (priority over upload)
        // Otherwise, use uploaded image
        if (!empty($data['image_url']) && $this->isExternalUrl($data['image_url'])) {
            // External URL provided - use it, ignore upload
            unset($data['image_upload']);
        } elseif (!empty($data['image_upload'])) {
            // Use uploaded image
            $path = $data['image_upload'];
            $data['image_url'] = '/storage/' . $path;
        }
        unset($data['image_upload']);

        // Store product_ids for later use, remove from data
        $this->productIds = $data['product_ids'] ?? [];
        unset($data['product_ids']);

        return $data;
    }

    protected array $productIds = [];
    protected bool $shouldRemoveImage = false;

    protected function afterSave(): void
    {
        // Sync products to collection
        if (!empty($this->productIds)) {
            $this->record->products()->sync($this->productIds);
            $this->record->update(['products_count' => count($this->productIds)]);
        }
    }

    protected function pushToShopify(): void
    {
        $collection = $this->record;
        $store = $collection->store;

        if (!$store) {
            Notification::make()
                ->title('Store not found')
                ->danger()
                ->send();
            return;
        }

        // If no shopify_id, create new collection
        if (!$collection->shopify_id) {
            $this->createInShopify($store, $collection);
            return;
        }

        $graphql = app(\LaravelShopifySdk\Clients\GraphQLClient::class);

        $mutation = <<<GQL
        mutation collectionUpdate(\$input: CollectionInput!) {
            collectionUpdate(input: \$input) {
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
                'id' => $collection->shopify_id,
                'title' => $collection->title,
                'handle' => $collection->handle,
                'descriptionHtml' => $collection->description_html ?? $collection->description ?? '',
            ],
        ];

        // Add image if provided and it's a valid external URL (not local storage)
        if ($collection->image_url && $this->isExternalUrl($collection->image_url)) {
            $variables['input']['image'] = [
                'src' => $collection->image_url,
            ];
        }

        try {
            $response = $graphql->query($store, $mutation, $variables);

            if (!empty($response['data']['collectionUpdate']['userErrors'])) {
                $errors = collect($response['data']['collectionUpdate']['userErrors'])
                    ->pluck('message')
                    ->join(', ');

                Notification::make()
                    ->title('Shopify Error')
                    ->body($errors)
                    ->danger()
                    ->send();
                return;
            }

            // Check if there was an image error
            $imageError = false;
            if (!empty($response['data']['collectionUpdate']['userErrors'])) {
                foreach ($response['data']['collectionUpdate']['userErrors'] as $error) {
                    if (isset($error['field']) && in_array('image', $error['field'])) {
                        $imageError = true;
                        Notification::make()
                            ->title('Image Upload Failed')
                            ->body('The image URL could not be uploaded to Shopify. Make sure the URL is publicly accessible.')
                            ->warning()
                            ->send();
                    }
                }
            }

            // Sync products to collection in Shopify
            $this->syncProductsToShopify($store, $collection);

            if (!$imageError) {
                Notification::make()
                    ->title('Collection Updated')
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

    protected function createInShopify(Store $store, $collection): void
    {
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

        // Only send image if it's a valid external URL (not local storage)
        if ($collection->image_url && $this->isExternalUrl($collection->image_url)) {
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
                    ->title('Collection Created in Shopify')
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

    protected function syncProductsToShopify(Store $store, $collection): void
    {
        if (!$collection->shopify_id) {
            return;
        }

        $graphql = app(\LaravelShopifySdk\Clients\GraphQLClient::class);

        // Get local product Shopify IDs (what we want in the collection)
        $localProductIds = $collection->products()
            ->whereNotNull('shopify_id')
            ->pluck('shopify_id')
            ->toArray();

        // First, get current products in Shopify collection
        $currentProductIds = $this->getShopifyCollectionProducts($graphql, $store, $collection->shopify_id);

        // Calculate products to remove and add
        $toRemove = array_diff($currentProductIds, $localProductIds);
        $toAdd = array_diff($localProductIds, $currentProductIds);

        // Remove products that are no longer in the collection
        if (!empty($toRemove)) {
            $this->removeProductsFromShopifyCollection($graphql, $store, $collection->shopify_id, array_values($toRemove));
        }

        // Add new products to the collection
        if (!empty($toAdd)) {
            $this->addProductsToShopifyCollection($graphql, $store, $collection->shopify_id, array_values($toAdd));
        }
    }

    protected function getShopifyCollectionProducts($graphql, Store $store, string $collectionId): array
    {
        $query = <<<GQL
        query getCollectionProducts(\$id: ID!) {
            collection(id: \$id) {
                products(first: 250) {
                    edges {
                        node {
                            id
                        }
                    }
                }
            }
        }
        GQL;

        try {
            $response = $graphql->query($store, $query, ['id' => $collectionId]);
            $edges = $response['data']['collection']['products']['edges'] ?? [];
            return array_map(fn($edge) => $edge['node']['id'], $edges);
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function removeProductsFromShopifyCollection($graphql, Store $store, string $collectionId, array $productIds): void
    {
        $mutation = <<<GQL
        mutation collectionRemoveProducts(\$id: ID!, \$productIds: [ID!]!) {
            collectionRemoveProducts(id: \$id, productIds: \$productIds) {
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        try {
            $graphql->query($store, $mutation, [
                'id' => $collectionId,
                'productIds' => $productIds,
            ]);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to remove products from Shopify collection: ' . $e->getMessage());
        }
    }

    protected function addProductsToShopifyCollection($graphql, Store $store, string $collectionId, array $productIds): void
    {
        if (empty($productIds)) {
            return;
        }

        $mutation = <<<GQL
        mutation collectionAddProducts(\$id: ID!, \$productIds: [ID!]!) {
            collectionAddProducts(id: \$id, productIds: \$productIds) {
                collection {
                    id
                    productsCount {
                        count
                    }
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $variables = [
            'id' => $collectionId,
            'productIds' => $productIds,
        ];

        try {
            $response = $graphql->query($store, $mutation, $variables);

            if (!empty($response['data']['collectionAddProducts']['userErrors'])) {
                $errors = collect($response['data']['collectionAddProducts']['userErrors'])
                    ->pluck('message')
                    ->join(', ');

                Notification::make()
                    ->title('Product Sync Warning')
                    ->body($errors)
                    ->warning()
                    ->send();
            }
        } catch (\Exception $e) {
            // Log but don't fail the whole operation
            \Illuminate\Support\Facades\Log::warning('Failed to sync products to Shopify collection: ' . $e->getMessage());
        }
    }

    protected function isExternalUrl(?string $url): bool
    {
        if (!$url) {
            return false;
        }

        // Check if it starts with http:// or https://
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }

    protected function deleteFromShopify(): void
    {
        $collection = $this->record;
        $store = $collection->store;

        if (!$store || !$collection->shopify_id) {
            Notification::make()
                ->title('Cannot Delete')
                ->body('Collection is not synced to Shopify.')
                ->warning()
                ->send();
            return;
        }

        $graphql = app(\LaravelShopifySdk\Clients\GraphQLClient::class);

        $mutation = <<<GQL
        mutation collectionDelete(\$input: CollectionDeleteInput!) {
            collectionDelete(input: \$input) {
                deletedCollectionId
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        try {
            $response = $graphql->query($store, $mutation, [
                'input' => [
                    'id' => $collection->shopify_id,
                ],
            ]);

            if (!empty($response['data']['collectionDelete']['userErrors'])) {
                $errors = collect($response['data']['collectionDelete']['userErrors'])
                    ->pluck('message')
                    ->join(', ');

                Notification::make()
                    ->title('Shopify Error')
                    ->body($errors)
                    ->danger()
                    ->send();
                return;
            }

            // Clear shopify_id locally
            $collection->update(['shopify_id' => null]);

            Notification::make()
                ->title('Deleted from Shopify')
                ->body('Collection has been deleted from Shopify. Local copy retained.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function deleteEverywhere(): void
    {
        $collection = $this->record;
        $store = $collection->store;

        // First delete from Shopify if synced
        if ($store && $collection->shopify_id) {
            $graphql = app(\LaravelShopifySdk\Clients\GraphQLClient::class);

            $mutation = <<<GQL
            mutation collectionDelete(\$input: CollectionDeleteInput!) {
                collectionDelete(input: \$input) {
                    deletedCollectionId
                    userErrors {
                        field
                        message
                    }
                }
            }
            GQL;

            try {
                $response = $graphql->query($store, $mutation, [
                    'input' => [
                        'id' => $collection->shopify_id,
                    ],
                ]);

                if (!empty($response['data']['collectionDelete']['userErrors'])) {
                    $errors = collect($response['data']['collectionDelete']['userErrors'])
                        ->pluck('message')
                        ->join(', ');

                    Notification::make()
                        ->title('Shopify Error')
                        ->body($errors)
                        ->danger()
                        ->send();
                    return;
                }
            } catch (\Exception $e) {
                Notification::make()
                    ->title('Error deleting from Shopify')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
                return;
            }
        }

        // Delete locally
        $collection->products()->detach();
        $collection->delete();

        Notification::make()
            ->title('Collection Deleted')
            ->body('Collection has been deleted from both Shopify and local database.')
            ->success()
            ->send();

        $this->redirect($this->getResource()::getUrl('index'));
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
