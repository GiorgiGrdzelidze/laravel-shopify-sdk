<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\DiscountResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Filament\Resources\DiscountResource;
use LaravelShopifySdk\Models\ShopifyLog;

class EditDiscount extends EditRecord
{
    protected static string $resource = DiscountResource::class;

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
                ->modalDescription('This will create the discount in Shopify. Continue?')
                ->action(fn () => $this->createInShopify()),

            Actions\Action::make('update_in_shopify')
                ->label('Update in Shopify')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => !empty($this->record->shopify_id))
                ->requiresConfirmation()
                ->modalHeading('Update in Shopify')
                ->modalDescription('This will update the discount in Shopify with local changes. Continue?')
                ->action(fn () => $this->updateInShopify()),

            Actions\DeleteAction::make(),
        ];
    }

    protected function createInShopify(): void
    {
        $discount = $this->record;
        $store = $discount->store;

        if (!$store) {
            Notification::make()
                ->title('Error')
                ->body('No store associated with this discount.')
                ->danger()
                ->send();
            return;
        }

        $graphql = app(GraphQLClient::class);

        try {
            $mutation = <<<GQL
            mutation discountCodeBasicCreate(\$basicCodeDiscount: DiscountCodeBasicInput!) {
                discountCodeBasicCreate(basicCodeDiscount: \$basicCodeDiscount) {
                    codeDiscountNode {
                        id
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
            GQL;

            $variables = [
                'basicCodeDiscount' => [
                    'title' => $discount->title,
                    'code' => $discount->title,
                    'startsAt' => $discount->starts_at?->toIso8601String() ?? now()->toIso8601String(),
                    'endsAt' => $discount->ends_at?->toIso8601String(),
                    'usageLimit' => $discount->usage_limit,
                    'appliesOncePerCustomer' => $discount->once_per_customer ?? false,
                    'customerGets' => $this->buildCustomerGets($discount),
                    'customerSelection' => [
                        'all' => true,
                    ],
                ],
            ];

            $response = $graphql->query($store, $mutation, $variables);

            if (!empty($response['data']['discountCodeBasicCreate']['userErrors'])) {
                $errors = collect($response['data']['discountCodeBasicCreate']['userErrors'])
                    ->pluck('message')
                    ->join(', ');
                throw new \Exception($errors);
            }

            $shopifyId = $response['data']['discountCodeBasicCreate']['codeDiscountNode']['id'] ?? null;

            if ($shopifyId) {
                $discount->update(['shopify_id' => $shopifyId]);

                ShopifyLog::success(
                    'create',
                    'Discount',
                    $shopifyId,
                    "Discount '{$discount->title}' created in Shopify",
                    null,
                    $store->id
                );

                Notification::make()
                    ->title('Pushed to Shopify')
                    ->body('Discount has been created in Shopify.')
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

    protected function updateInShopify(): void
    {
        $discount = $this->record;
        $store = $discount->store;

        if (!$store) {
            Notification::make()
                ->title('Error')
                ->body('No store associated with this discount.')
                ->danger()
                ->send();
            return;
        }

        $graphql = app(GraphQLClient::class);

        try {
            // For updating, we only update the fields that can be changed
            // Title and dates can be updated via discountCodeBasicUpdate
            $mutation = <<<GQL
            mutation discountCodeBasicUpdate(\$id: ID!, \$basicCodeDiscount: DiscountCodeBasicInput!) {
                discountCodeBasicUpdate(id: \$id, basicCodeDiscount: \$basicCodeDiscount) {
                    codeDiscountNode {
                        id
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
            GQL;

            $variables = [
                'id' => $discount->shopify_id,
                'basicCodeDiscount' => [
                    'title' => $discount->title,
                    'startsAt' => $discount->starts_at?->toIso8601String(),
                    'endsAt' => $discount->ends_at?->toIso8601String(),
                    'usageLimit' => $discount->usage_limit,
                    'appliesOncePerCustomer' => $discount->once_per_customer ?? false,
                    'customerGets' => $this->buildCustomerGets($discount),
                    'customerSelection' => [
                        'all' => true,
                    ],
                ],
            ];

            $response = $graphql->query($store, $mutation, $variables);

            if (!empty($response['data']['discountCodeBasicUpdate']['userErrors'])) {
                $errors = collect($response['data']['discountCodeBasicUpdate']['userErrors'])
                    ->pluck('message')
                    ->join(', ');
                throw new \Exception($errors);
            }

            ShopifyLog::success(
                'update',
                'Discount',
                $discount->shopify_id,
                "Discount '{$discount->title}' updated in Shopify",
                null,
                $store->id
            );

            Notification::make()
                ->title('Updated in Shopify')
                ->body('Discount has been updated in Shopify.')
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

    protected function buildCustomerGets($discount): array
    {
        $value = [];

        if ($discount->value_type === 'percentage') {
            $value['percentage'] = (float) $discount->value / 100;
        } else {
            $value['discountAmount'] = [
                'amount' => (string) $discount->value,
                'appliesOnEachItem' => $discount->allocation_method === 'each',
            ];
        }

        return [
            'value' => $value,
            'items' => [
                'all' => true,
            ],
        ];
    }
}
