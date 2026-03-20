<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Services;

use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Exceptions\ShopifyApiException;
use LaravelShopifySdk\Models\Core\Product;
use LaravelShopifySdk\Models\Core\Store;
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
     * Create a new product on Shopify with full support for media, options, and variants.
     *
     * @param Store $store
     * @param array $data
     * @return Product
     * @throws ShopifyApiException
     */
    public function create(Store $store, array $data): Product
    {
        $mutation = <<<'GQL'
        mutation productCreate($product: ProductCreateInput!, $media: [CreateMediaInput!]) {
            productCreate(product: $product, media: $media) {
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
                    options {
                        id
                        name
                        position
                        values
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
                                inventoryItem {
                                    id
                                }
                            }
                        }
                    }
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        // Build product input
        $productInput = [
            'title' => $data['title'],
        ];

        if (!empty($data['descriptionHtml'])) {
            $productInput['descriptionHtml'] = $data['descriptionHtml'];
        }
        if (!empty($data['vendor'])) {
            $productInput['vendor'] = $data['vendor'];
        }
        if (!empty($data['productType'])) {
            $productInput['productType'] = $data['productType'];
        }
        if (!empty($data['status'])) {
            $productInput['status'] = strtoupper($data['status']);
        }
        if (!empty($data['tags'])) {
            $productInput['tags'] = is_array($data['tags']) ? $data['tags'] : explode(',', $data['tags']);
        }
        if (!empty($data['handle'])) {
            $productInput['handle'] = $data['handle'];
        }

        // Add SEO settings
        if (!empty($data['seo']['title']) || !empty($data['seo']['description'])) {
            $productInput['seo'] = array_filter([
                'title' => $data['seo']['title'] ?? null,
                'description' => $data['seo']['description'] ?? null,
            ]);
        }

        // Build media input
        $mediaInput = [];
        if (!empty($data['media'])) {
            foreach ($data['media'] as $mediaItem) {
                if (!empty($mediaItem['originalSource'])) {
                    $mediaInput[] = [
                        'originalSource' => $mediaItem['originalSource'],
                        'alt' => $mediaItem['alt'] ?? '',
                        'mediaContentType' => $mediaItem['mediaContentType'] ?? 'IMAGE',
                    ];
                }
            }
        }

        $variables = [
            'product' => $productInput,
        ];

        if (!empty($mediaInput)) {
            $variables['media'] = $mediaInput;
        }

        $response = $this->graphqlClient->query($store, $mutation, $variables);

        if (!empty($response['data']['productCreate']['userErrors'])) {
            $errors = $response['data']['productCreate']['userErrors'];
            $errorMessages = array_map(fn($e) => $e['message'], $errors);
            throw new ShopifyApiException('Product create failed: ' . implode(', ', $errorMessages));
        }

        $shopifyProduct = $response['data']['productCreate']['product'] ?? null;

        if (!$shopifyProduct) {
            throw new ShopifyApiException('Product create failed: No product returned');
        }

        // Create local product record
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

        // Create variants if provided
        $variantsData = $data['variants'] ?? [];
        if (!empty($variantsData)) {
            $this->createVariantsBulk($store, $product, $variantsData);
            // Refresh product to get updated variants
            $this->fetch($product);
        } else {
            // Create local variant records from default variant
            $variants = $shopifyProduct['variants']['edges'] ?? [];
            foreach ($variants as $edge) {
                $variantData = $edge['node'];
                \LaravelShopifySdk\Models\Core\Variant::create([
                    'store_id' => $store->id,
                    'product_id' => $product->id,
                    'shopify_id' => $variantData['id'],
                    'sku' => $variantData['sku'] ?? null,
                    'barcode' => $variantData['barcode'] ?? null,
                    'price' => $variantData['price'] ?? null,
                    'inventory_item_id' => $variantData['inventoryItem']['id'] ?? null,
                    'payload' => $variantData,
                ]);
            }
        }

        Log::info('Product created on Shopify', [
            'product_id' => $product->id,
            'shopify_id' => $product->shopify_id,
            'variants_count' => count($variantsData) ?: 1,
        ]);

        return $product;
    }

    /**
     * Create variants in bulk for a product.
     *
     * @param Store $store
     * @param Product $product
     * @param array $variants
     * @return array
     * @throws ShopifyApiException
     */
    public function createVariantsBulk(Store $store, Product $product, array $variants): array
    {
        if (empty($variants)) {
            return [];
        }

        $mutation = <<<'GQL'
        mutation productVariantsBulkCreate($productId: ID!, $variants: [ProductVariantsBulkInput!]!) {
            productVariantsBulkCreate(productId: $productId, variants: $variants) {
                productVariants {
                    id
                    title
                    sku
                    barcode
                    price
                    compareAtPrice
                    selectedOptions {
                        name
                        value
                    }
                    inventoryItem {
                        id
                    }
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $variantInputs = [];
        foreach ($variants as $variant) {
            $variantInput = [];

            if (!empty($variant['price'])) {
                $variantInput['price'] = (string) $variant['price'];
            }
            if (!empty($variant['compareAtPrice'])) {
                $variantInput['compareAtPrice'] = (string) $variant['compareAtPrice'];
            }
            if (!empty($variant['sku'])) {
                $variantInput['sku'] = $variant['sku'];
            }
            if (!empty($variant['barcode'])) {
                $variantInput['barcode'] = $variant['barcode'];
            }

            // Add options
            if (!empty($variant['options'])) {
                $variantInput['optionValues'] = [];
                $optionNames = ['Size', 'Color', 'Material']; // Default option names
                foreach ($variant['options'] as $index => $optionValue) {
                    $variantInput['optionValues'][] = [
                        'optionName' => $optionNames[$index] ?? "Option " . ($index + 1),
                        'name' => $optionValue,
                    ];
                }
            }

            // Inventory
            if (isset($variant['inventoryQuantity'])) {
                $variantInput['inventoryQuantities'] = [
                    [
                        'availableQuantity' => (int) $variant['inventoryQuantity'],
                        'locationId' => $this->getDefaultLocationId($store),
                    ]
                ];
            }

            $variantInputs[] = $variantInput;
        }

        $response = $this->graphqlClient->query($store, $mutation, [
            'productId' => $product->shopify_id,
            'variants' => $variantInputs,
        ]);

        if (!empty($response['data']['productVariantsBulkCreate']['userErrors'])) {
            $errors = $response['data']['productVariantsBulkCreate']['userErrors'];
            $errorMessages = array_map(fn($e) => $e['message'], $errors);
            Log::warning('Variant creation errors', ['errors' => $errorMessages]);
            // Don't throw, just log - some variants may have been created
        }

        $createdVariants = $response['data']['productVariantsBulkCreate']['productVariants'] ?? [];

        // Create local variant records
        foreach ($createdVariants as $variantData) {
            \LaravelShopifySdk\Models\Core\Variant::create([
                'store_id' => $store->id,
                'product_id' => $product->id,
                'shopify_id' => $variantData['id'],
                'sku' => $variantData['sku'] ?? null,
                'barcode' => $variantData['barcode'] ?? null,
                'price' => $variantData['price'] ?? null,
                'inventory_item_id' => $variantData['inventoryItem']['id'] ?? null,
                'payload' => $variantData,
            ]);
        }

        Log::info('Variants created on Shopify', [
            'product_id' => $product->id,
            'variants_count' => count($createdVariants),
        ]);

        return $createdVariants;
    }

    /**
     * Get the default location ID for a store.
     *
     * @param Store $store
     * @return string|null
     */
    protected function getDefaultLocationId(Store $store): ?string
    {
        $location = \LaravelShopifySdk\Models\Core\Location::where('store_id', $store->id)->first();
        return $location?->shopify_id;
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

    /**
     * Add media to an existing product on Shopify.
     *
     * @param Product $product
     * @param array $media Array of media items with originalSource, alt, mediaContentType
     * @return array
     * @throws ShopifyApiException
     */
    public function addMediaToProduct(Product $product, array $media): array
    {
        if (empty($media)) {
            return [];
        }

        $store = $product->store;

        $mutation = <<<'GQL'
        mutation productCreateMedia($productId: ID!, $media: [CreateMediaInput!]!) {
            productCreateMedia(productId: $productId, media: $media) {
                media {
                    alt
                    mediaContentType
                    preview {
                        status
                    }
                }
                mediaUserErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $mediaInput = [];
        foreach ($media as $item) {
            $mediaInput[] = [
                'originalSource' => $item['originalSource'],
                'alt' => $item['alt'] ?? '',
                'mediaContentType' => $item['mediaContentType'] ?? 'IMAGE',
            ];
        }

        $response = $this->graphqlClient->query($store, $mutation, [
            'productId' => $product->shopify_id,
            'media' => $mediaInput,
        ]);

        if (!empty($response['data']['productCreateMedia']['mediaUserErrors'])) {
            $errors = $response['data']['productCreateMedia']['mediaUserErrors'];
            $errorMessages = array_map(fn($e) => $e['message'], $errors);
            throw new ShopifyApiException('Add media failed: ' . implode(', ', $errorMessages));
        }

        Log::info('Media added to product on Shopify', [
            'product_id' => $product->id,
            'media_count' => count($media),
        ]);

        return $response['data']['productCreateMedia']['media'] ?? [];
    }

    /**
     * Upload local files to Shopify using staged uploads.
     *
     * @param Store $store
     * @param array $localPaths Array of local file paths
     * @return array Array of Shopify resource URLs
     * @throws ShopifyApiException
     */
    public function uploadFilesToShopify(Store $store, array $localPaths): array
    {
        if (empty($localPaths)) {
            return [];
        }

        // Step 1: Create staged upload targets
        $mutation = <<<'GQL'
        mutation stagedUploadsCreate($input: [StagedUploadInput!]!) {
            stagedUploadsCreate(input: $input) {
                stagedTargets {
                    url
                    resourceUrl
                    parameters {
                        name
                        value
                    }
                }
                userErrors {
                    field
                    message
                }
            }
        }
        GQL;

        $uploadInputs = [];
        foreach ($localPaths as $path) {
            $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($path);
            $mimeType = mime_content_type($fullPath) ?: 'image/jpeg';
            $filename = basename($path);

            $uploadInputs[] = [
                'filename' => $filename,
                'mimeType' => $mimeType,
                'httpMethod' => 'POST',
                'resource' => 'PRODUCT_IMAGE',
            ];
        }

        $response = $this->graphqlClient->query($store, $mutation, ['input' => $uploadInputs]);

        if (!empty($response['data']['stagedUploadsCreate']['userErrors'])) {
            $errors = $response['data']['stagedUploadsCreate']['userErrors'];
            $errorMessages = array_map(fn($e) => $e['message'], $errors);
            throw new ShopifyApiException('Staged upload failed: ' . implode(', ', $errorMessages));
        }

        $stagedTargets = $response['data']['stagedUploadsCreate']['stagedTargets'] ?? [];
        $resourceUrls = [];

        // Step 2: Upload files to staged targets
        foreach ($stagedTargets as $index => $target) {
            $localPath = $localPaths[$index];
            $fullPath = \Illuminate\Support\Facades\Storage::disk('public')->path($localPath);

            // Build form data with parameters
            $formData = [];
            foreach ($target['parameters'] as $param) {
                $formData[$param['name']] = $param['value'];
            }

            // Upload file using HTTP client
            $httpResponse = \Illuminate\Support\Facades\Http::asMultipart();

            foreach ($formData as $name => $value) {
                $httpResponse = $httpResponse->attach($name, $value);
            }

            $httpResponse = $httpResponse->attach(
                'file',
                file_get_contents($fullPath),
                basename($localPath)
            )->post($target['url']);

            if (!$httpResponse->successful()) {
                Log::warning('Failed to upload file to Shopify staged target', [
                    'path' => $localPath,
                    'status' => $httpResponse->status(),
                ]);
                continue;
            }

            $resourceUrls[] = $target['resourceUrl'];
        }

        return $resourceUrls;
    }
}
