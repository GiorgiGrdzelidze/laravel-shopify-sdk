<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\MetafieldResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Filament\Resources\MetafieldResource;
use LaravelShopifySdk\Models\Sync\ShopifyLog;

class EditMetafield extends EditRecord
{
    protected static string $resource = MetafieldResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('push_to_shopify')
                ->label('Push to Shopify')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('success')
                ->visible(fn () => empty($this->record->shopify_id))
                ->requiresConfirmation()
                ->modalHeading('Push to Shopify')
                ->modalDescription('This will create the metafield in Shopify. Continue?')
                ->action(fn () => $this->createOrUpdateInShopify()),

            Actions\Action::make('update_in_shopify')
                ->label('Update in Shopify')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => !empty($this->record->shopify_id))
                ->requiresConfirmation()
                ->modalHeading('Update in Shopify')
                ->modalDescription('This will update the metafield in Shopify with local changes. Continue?')
                ->action(fn () => $this->createOrUpdateInShopify()),

            Actions\Action::make('delete_from_shopify')
                ->label('Delete from Shopify')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->visible(fn () => $this->record->shopify_id !== null)
                ->requiresConfirmation()
                ->modalHeading('Delete from Shopify')
                ->modalDescription('This will delete the metafield from Shopify but keep it locally. Continue?')
                ->action(fn () => $this->deleteFromShopify()),

            Actions\DeleteAction::make(),
        ];
    }

    protected function createOrUpdateInShopify(): void
    {
        $metafield = $this->record;
        $store = $metafield->store;

        if (!$store) {
            Notification::make()
                ->title('Error')
                ->body('No store associated with this metafield.')
                ->danger()
                ->send();
            return;
        }

        if (!$metafield->owner_id) {
            Notification::make()
                ->title('Error')
                ->body('Metafield must have an owner ID (Shopify GID).')
                ->danger()
                ->send();
            return;
        }

        $graphql = app(GraphQLClient::class);

        $mutation = <<<GQL
        mutation metafieldsSet(\$metafields: [MetafieldsSetInput!]!) {
            metafieldsSet(metafields: \$metafields) {
                metafields {
                    id
                    namespace
                    key
                    value
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        try {
            $variables = [
                'metafields' => [
                    [
                        'ownerId' => $metafield->owner_id,
                        'namespace' => $metafield->namespace,
                        'key' => $metafield->key,
                        'value' => $metafield->value,
                        'type' => $metafield->type,
                    ],
                ],
            ];

            $response = $graphql->query($store, $mutation, $variables);

            if (!empty($response['data']['metafieldsSet']['userErrors'])) {
                $errors = collect($response['data']['metafieldsSet']['userErrors'])
                    ->pluck('message')
                    ->join(', ');
                throw new \Exception($errors);
            }

            $result = $response['data']['metafieldsSet']['metafields'][0] ?? null;

            if ($result) {
                $metafield->update(['shopify_id' => $result['id']]);

                ShopifyLog::success(
                    'update',
                    'Metafield',
                    $result['id'],
                    "Metafield '{$metafield->namespace}.{$metafield->key}' pushed to Shopify",
                    null,
                    $store->id
                );

                Notification::make()
                    ->title('Pushed to Shopify')
                    ->body('Metafield has been synced to Shopify.')
                    ->success()
                    ->send();
            }

        } catch (\Exception $e) {
            Notification::make()
                ->title('Shopify Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function deleteFromShopify(): void
    {
        $metafield = $this->record;
        $store = $metafield->store;
        $graphql = app(GraphQLClient::class);

        $mutation = <<<GQL
        mutation metafieldDelete(\$input: MetafieldDeleteInput!) {
            metafieldDelete(input: \$input) {
                deletedId
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
                    'id' => $metafield->shopify_id,
                ],
            ]);

            if (!empty($response['data']['metafieldDelete']['userErrors'])) {
                $errors = collect($response['data']['metafieldDelete']['userErrors'])
                    ->pluck('message')
                    ->join(', ');
                throw new \Exception($errors);
            }

            ShopifyLog::success(
                'delete',
                'Metafield',
                $metafield->shopify_id,
                "Metafield '{$metafield->namespace}.{$metafield->key}' deleted from Shopify",
                null,
                $store->id
            );

            $metafield->update(['shopify_id' => null]);

            Notification::make()
                ->title('Deleted from Shopify')
                ->body('Metafield has been deleted from Shopify. Local copy retained.')
                ->success()
                ->send();

        } catch (\Exception $e) {
            Notification::make()
                ->title('Shopify Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
