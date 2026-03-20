<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Filament\Resources\DraftOrderResource\Pages;

use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Filament\Resources\DraftOrderResource;
use LaravelShopifySdk\Models\Sync\ShopifyLog;

class EditDraftOrder extends EditRecord
{
    protected static string $resource = DraftOrderResource::class;

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
                ->modalDescription('This will create the draft order in Shopify. Continue?')
                ->action(fn () => $this->createInShopify()),

            Actions\Action::make('update_in_shopify')
                ->label('Update in Shopify')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->visible(fn () => !empty($this->record->shopify_id) && $this->record->status !== 'completed')
                ->requiresConfirmation()
                ->modalHeading('Update in Shopify')
                ->modalDescription('This will update the draft order in Shopify with local changes. Continue?')
                ->action(fn () => $this->updateInShopify()),

            Actions\Action::make('complete_draft')
                ->label('Complete Order')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->visible(fn () => $this->record->shopify_id && $this->record->status !== 'completed')
                ->requiresConfirmation()
                ->modalHeading('Complete Draft Order')
                ->modalDescription('This will convert the draft order to a real order in Shopify. This action cannot be undone.')
                ->action(fn () => $this->completeDraftOrder()),

            Actions\Action::make('send_invoice')
                ->label('Send Invoice')
                ->icon('heroicon-o-envelope')
                ->color('info')
                ->visible(fn () => $this->record->shopify_id && $this->record->status === 'open')
                ->requiresConfirmation()
                ->modalHeading('Send Invoice')
                ->modalDescription('This will send an invoice email to the customer.')
                ->action(fn () => $this->sendInvoice()),

            Actions\DeleteAction::make(),
        ];
    }

    protected function createInShopify(): void
    {
        $draftOrder = $this->record;
        $store = $draftOrder->store;

        if (!$store) {
            Notification::make()
                ->title('Error')
                ->body('No store associated with this draft order.')
                ->danger()
                ->send();
            return;
        }

        $graphql = app(GraphQLClient::class);

        try {
            $mutation = <<<GQL
            mutation draftOrderCreate(\$input: DraftOrderInput!) {
                draftOrderCreate(input: \$input) {
                    draftOrder {
                        id
                        name
                        invoiceUrl
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
            GQL;

            $input = [
                'note' => $draftOrder->note,
                'email' => $draftOrder->email,
                'phone' => $draftOrder->phone,
                'taxExempt' => $draftOrder->tax_exempt ?? false,
            ];

            // Add line items if they exist
            if (!empty($draftOrder->line_items)) {
                $input['lineItems'] = array_map(function ($item) {
                    return [
                        'title' => $item['title'] ?? 'Item',
                        'quantity' => $item['quantity'] ?? 1,
                        'originalUnitPrice' => (string) ($item['price'] ?? '0.00'),
                    ];
                }, $draftOrder->line_items);
            }

            $response = $graphql->query($store, $mutation, ['input' => $input]);

            if (!empty($response['data']['draftOrderCreate']['userErrors'])) {
                $errors = collect($response['data']['draftOrderCreate']['userErrors'])
                    ->pluck('message')
                    ->join(', ');
                throw new \Exception($errors);
            }

            $result = $response['data']['draftOrderCreate']['draftOrder'] ?? null;

            if ($result) {
                $draftOrder->update([
                    'shopify_id' => $result['id'],
                    'name' => $result['name'],
                    'invoice_url' => $result['invoiceUrl'],
                ]);

                ShopifyLog::success(
                    'create',
                    'DraftOrder',
                    $result['id'],
                    "Draft order '{$result['name']}' created in Shopify",
                    null,
                    $store->id
                );

                Notification::make()
                    ->title('Pushed to Shopify')
                    ->body('Draft order has been created in Shopify.')
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
        $draftOrder = $this->record;
        $store = $draftOrder->store;

        if (!$store) {
            Notification::make()
                ->title('Error')
                ->body('No store associated with this draft order.')
                ->danger()
                ->send();
            return;
        }

        $graphql = app(GraphQLClient::class);

        try {
            $mutation = <<<GQL
            mutation draftOrderUpdate(\$id: ID!, \$input: DraftOrderInput!) {
                draftOrderUpdate(id: \$id, input: \$input) {
                    draftOrder {
                        id
                        name
                    }
                    userErrors {
                        field
                        message
                    }
                }
            }
            GQL;

            $input = [
                'note' => $draftOrder->note,
                'email' => $draftOrder->email,
                'phone' => $draftOrder->phone,
            ];

            $response = $graphql->query($store, $mutation, [
                'id' => $draftOrder->shopify_id,
                'input' => $input,
            ]);

            if (!empty($response['data']['draftOrderUpdate']['userErrors'])) {
                $errors = collect($response['data']['draftOrderUpdate']['userErrors'])
                    ->pluck('message')
                    ->join(', ');
                throw new \Exception($errors);
            }

            ShopifyLog::success(
                'update',
                'DraftOrder',
                $draftOrder->shopify_id,
                "Draft order '{$draftOrder->name}' updated in Shopify",
                null,
                $store->id
            );

            Notification::make()
                ->title('Updated in Shopify')
                ->body('Draft order has been updated in Shopify.')
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

    protected function completeDraftOrder(): void
    {
        $draftOrder = $this->record;
        $store = $draftOrder->store;
        $graphql = app(GraphQLClient::class);

        $mutation = <<<GQL
        mutation draftOrderComplete(\$id: ID!) {
            draftOrderComplete(id: \$id) {
                draftOrder {
                    id
                    order {
                        id
                        name
                    }
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        try {
            $response = $graphql->query($store, $mutation, ['id' => $draftOrder->shopify_id]);

            if (!empty($response['data']['draftOrderComplete']['userErrors'])) {
                $errors = collect($response['data']['draftOrderComplete']['userErrors'])
                    ->pluck('message')
                    ->join(', ');
                throw new \Exception($errors);
            }

            $order = $response['data']['draftOrderComplete']['draftOrder']['order'] ?? null;

            $draftOrder->update([
                'status' => 'completed',
                'completed_at' => now(),
            ]);

            ShopifyLog::success(
                'update',
                'DraftOrder',
                $draftOrder->shopify_id,
                "Draft order completed, converted to order " . ($order['name'] ?? ''),
                null,
                $store->id
            );

            Notification::make()
                ->title('Order Completed')
                ->body('Draft order has been converted to order ' . ($order['name'] ?? ''))
                ->success()
                ->send();

            $this->redirect(static::getResource()::getUrl('index'));

        } catch (\Exception $e) {
            Notification::make()
                ->title('Shopify Error')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function sendInvoice(): void
    {
        $draftOrder = $this->record;
        $store = $draftOrder->store;
        $graphql = app(GraphQLClient::class);

        $mutation = <<<GQL
        mutation draftOrderInvoiceSend(\$id: ID!) {
            draftOrderInvoiceSend(id: \$id) {
                draftOrder {
                    id
                    invoiceSentAt
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        try {
            $response = $graphql->query($store, $mutation, ['id' => $draftOrder->shopify_id]);

            if (!empty($response['data']['draftOrderInvoiceSend']['userErrors'])) {
                $errors = collect($response['data']['draftOrderInvoiceSend']['userErrors'])
                    ->pluck('message')
                    ->join(', ');
                throw new \Exception($errors);
            }

            $draftOrder->update([
                'status' => 'invoice_sent',
                'invoice_sent_at' => now(),
            ]);

            Notification::make()
                ->title('Invoice Sent')
                ->body('Invoice has been sent to the customer.')
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
