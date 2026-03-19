<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Services;

use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Exceptions\ShopifyApiException;
use LaravelShopifySdk\Models\Product;
use LaravelShopifySdk\Models\Store;
use Illuminate\Support\Facades\Log;

/**
 * Product Service
 *
 * Handles product CRUD operations with Shopify Admin API.
 *
 * @package LaravelShopifySdk\Services
 */
class ProductService
{
    public function __construct(
        protected GraphQLClient $graphqlClient
    ) {}

    /**
     * Update a product on Shopify.
     *
     * @param Product $product
     * @param array $data
     * @return array
     * @throws ShopifyApiException
     */
    public function update(Product $product, array $data): array
    {
        $store = $product->store;

        $mutation = <<<'GQL'
        mutation productUpdate($input: ProductInput!) {
            productUpdate(input: $input) {
                product {
                    id
                    title
                    handle
                    descriptionHtml
                    vendor
                    productType
                    status
                    tags
                    updatedAt
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $input = [
            'id' => $product->shopify_id,
        ];

        if (isset($data['title'])) {
            $input['title'] = $data['title'];
        }
        if (isset($data['descriptionHtml'])) {
            $input['descriptionHtml'] = $data['descriptionHtml'];
        }
        if (isset($data['vendor'])) {
            $input['vendor'] = $data['vendor'];
        }
        if (isset($data['productType'])) {
            $input['productType'] = $data['productType'];
        }
        if (isset($data['status'])) {
            $input['status'] = strtoupper($data['status']);
        }
        if (isset($data['tags'])) {
            $input['tags'] = is_array($data['tags']) ? $data['tags'] : explode(',', $data['tags']);
        }
        if (isset($data['handle'])) {
            $input['handle'] = $data['handle'];
        }

        $response = $this->graphqlClient->query($store, $mutation, ['input' => $input]);

        if (!empty($response['data']['productUpdate']['userErrors'])) {
            $errors = $response['data']['productUpdate']['userErrors'];
            $errorMessages = array_map(fn($e) => $e['message'], $errors);
            throw new ShopifyApiException('Product update failed: ' . implode(', ', $errorMessages));
        }

        $updatedProduct = $response['data']['productUpdate']['product'] ?? null;

        if ($updatedProduct) {
            $product->update([
                'title' => $updatedProduct['title'],
                'handle' => $updatedProduct['handle'],
                'vendor' => $updatedProduct['vendor'],
                'product_type' => $updatedProduct['productType'],
                'status' => $updatedProduct['status'],
                'shopify_updated_at' => $updatedProduct['updatedAt'],
            ]);

            Log::info('Product updated on Shopify', [
                'product_id' => $product->id,
                'shopify_id' => $product->shopify_id,
            ]);
        }

        return $response;
    }

    /**
     * Delete a product from Shopify.
     *
     * @param Product $product
     * @return array
     * @throws ShopifyApiException
     */
    public function delete(Product $product): array
    {
        $store = $product->store;

        $mutation = <<<'GQL'
        mutation productDelete($input: ProductDeleteInput!) {
            productDelete(input: $input) {
                deletedProductId
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $response = $this->graphqlClient->query($store, $mutation, [
            'input' => [
                'id' => $product->shopify_id,
            ],
        ]);

        if (!empty($response['data']['productDelete']['userErrors'])) {
            $errors = $response['data']['productDelete']['userErrors'];
            $errorMessages = array_map(fn($e) => $e['message'], $errors);
            throw new ShopifyApiException('Product delete failed: ' . implode(', ', $errorMessages));
        }

        Log::info('Product deleted from Shopify', [
            'product_id' => $product->id,
            'shopify_id' => $product->shopify_id,
        ]);

        // Delete local record and variants
        $product->variants()->delete();
        $product->delete();

        return $response;
    }

    /**
     * Create a new product on Shopify.
     *
     * @param Store $store
     * @param array $data
     * @return Product
     * @throws ShopifyApiException
     */
    public function create(Store $store, array $data): Product
    {
        $mutation = <<<'GQL'
        mutation productCreate($input: ProductInput!) {
            productCreate(input: $input) {
                product {
                    id
                    title
                    handle
                    descriptionHtml
                    vendor
                    productType
                    status
                    tags
                    createdAt
                    updatedAt
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $input = [
            'title' => $data['title'],
        ];

        if (isset($data['descriptionHtml'])) {
            $input['descriptionHtml'] = $data['descriptionHtml'];
        }
        if (isset($data['vendor'])) {
            $input['vendor'] = $data['vendor'];
        }
        if (isset($data['productType'])) {
            $input['productType'] = $data['productType'];
        }
        if (isset($data['status'])) {
            $input['status'] = strtoupper($data['status']);
        }
        if (isset($data['tags'])) {
            $input['tags'] = is_array($data['tags']) ? $data['tags'] : explode(',', $data['tags']);
        }
        if (isset($data['handle'])) {
            $input['handle'] = $data['handle'];
        }

        $response = $this->graphqlClient->query($store, $mutation, ['input' => $input]);

        if (!empty($response['data']['productCreate']['userErrors'])) {
            $errors = $response['data']['productCreate']['userErrors'];
            $errorMessages = array_map(fn($e) => $e['message'], $errors);
            throw new ShopifyApiException('Product create failed: ' . implode(', ', $errorMessages));
        }

        $shopifyProduct = $response['data']['productCreate']['product'] ?? null;

        if (!$shopifyProduct) {
            throw new ShopifyApiException('Product create failed: No product returned');
        }

        $product = Product::create([
            'store_id' => $store->id,
            'shopify_id' => $shopifyProduct['id'],
            'title' => $shopifyProduct['title'],
            'handle' => $shopifyProduct['handle'],
            'vendor' => $shopifyProduct['vendor'],
            'product_type' => $shopifyProduct['productType'],
            'status' => $shopifyProduct['status'],
            'payload' => $shopifyProduct,
            'shopify_updated_at' => $shopifyProduct['updatedAt'],
        ]);

        Log::info('Product created on Shopify', [
            'product_id' => $product->id,
            'shopify_id' => $product->shopify_id,
        ]);

        return $product;
    }

    /**
     * Fetch a single product from Shopify and update local record.
     *
     * @param Product $product
     * @return Product
     * @throws ShopifyApiException
     */
    public function fetch(Product $product): Product
    {
        $store = $product->store;

        $query = <<<'GQL'
        query getProduct($id: ID!) {
            product(id: $id) {
                id
                title
                handle
                description
                descriptionHtml
                vendor
                productType
                status
                tags
                createdAt
                updatedAt
                seo {
                    title
                    description
                }
                featuredImage {
                    id
                    url
                    altText
                }
                images(first: 20) {
                    edges {
                        node {
                            id
                            url
                            altText
                        }
                    }
                }
                variants(first: 100) {
                    edges {
                        node {
                            id
                            title
                            sku
                            barcode
                            price
                            compareAtPrice
                            inventoryQuantity
                            inventoryItem {
                                id
                            }
                        }
                    }
                }
            }
        }
        GQL;

        $response = $this->graphqlClient->query($store, $query, ['id' => $product->shopify_id]);

        $shopifyProduct = $response['data']['product'] ?? null;

        if (!$shopifyProduct) {
            throw new ShopifyApiException('Product not found on Shopify');
        }

        $product->update([
            'title' => $shopifyProduct['title'],
            'handle' => $shopifyProduct['handle'],
            'vendor' => $shopifyProduct['vendor'],
            'product_type' => $shopifyProduct['productType'],
            'status' => $shopifyProduct['status'],
            'payload' => $shopifyProduct,
            'shopify_updated_at' => $shopifyProduct['updatedAt'],
        ]);

        return $product->fresh();
    }

    /**
     * Delete an image from a product on Shopify.
     *
     * @param Product $product
     * @param string $imageId
     * @return array
     * @throws ShopifyApiException
     */
    public function deleteImage(Product $product, string $imageId): array
    {
        $store = $product->store;

        $mutation = <<<'GQL'
        mutation productDeleteMedia($productId: ID!, $mediaIds: [ID!]!) {
            productDeleteMedia(productId: $productId, mediaIds: $mediaIds) {
                deletedMediaIds
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $response = $this->graphqlClient->query($store, $mutation, [
            'productId' => $product->shopify_id,
            'mediaIds' => [$imageId],
        ]);

        if (!empty($response['data']['productDeleteMedia']['userErrors'])) {
            $errors = $response['data']['productDeleteMedia']['userErrors'];
            $errorMessages = array_map(fn($e) => $e['message'], $errors);
            throw new ShopifyApiException('Image delete failed: ' . implode(', ', $errorMessages));
        }

        Log::info('Product image deleted from Shopify', [
            'product_id' => $product->id,
            'image_id' => $imageId,
        ]);

        // Refresh product to get updated images
        return $this->fetch($product)->toArray();
    }
}
