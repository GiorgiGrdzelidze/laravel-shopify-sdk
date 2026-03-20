<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Services;

use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Exceptions\ShopifyApiException;
use LaravelShopifySdk\Models\Core\Product;
use LaravelShopifySdk\Models\Core\ProductType;
use LaravelShopifySdk\Models\Core\Store;
use Illuminate\Support\Facades\Log;

/**
 * Product Type Service
 *
 * Handles product type operations with Shopify Admin API.
 * Note: Shopify doesn't have a dedicated product types API.
 * Product types are managed through product updates.
 */
class ProductTypeService
{
    public function __construct(
        protected GraphQLClient $graphqlClient
    ) {}

    /**
     * Update all products with a specific type to a new type name on Shopify.
     *
     * @param ProductType $productType
     * @param string $oldName The old type name to replace
     * @return int Number of products updated
     * @throws ShopifyApiException
     */
    public function pushToShopify(ProductType $productType, ?string $oldName = null): int
    {
        $store = $productType->store;

        // Find all local products with this type
        $searchName = $oldName ?? $productType->name;
        $products = Product::where('store_id', $store->id)
            ->where('product_type', $searchName)
            ->whereNotNull('shopify_id')
            ->where('shopify_id', 'not like', 'local_%')
            ->get();

        $updatedCount = 0;

        foreach ($products as $product) {
            try {
                $this->updateProductType($product, $productType->name);
                $updatedCount++;
            } catch (\Exception $e) {
                Log::warning('Failed to update product type on Shopify', [
                    'product_id' => $product->id,
                    'shopify_id' => $product->shopify_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update local products
        Product::where('store_id', $store->id)
            ->where('product_type', $searchName)
            ->update(['product_type' => $productType->name]);

        // Update products count
        $productType->updateProductsCount();

        Log::info('Product type pushed to Shopify', [
            'store_id' => $store->id,
            'type_name' => $productType->name,
            'products_updated' => $updatedCount,
        ]);

        return $updatedCount;
    }

    /**
     * Update a single product's type on Shopify.
     */
    protected function updateProductType(Product $product, string $newType): void
    {
        $store = $product->store;

        $mutation = <<<'GQL'
        mutation productUpdate($input: ProductInput!) {
            productUpdate(input: $input) {
                product {
                    id
                    productType
                }
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
                'productType' => $newType,
            ],
        ]);

        if (!empty($response['data']['productUpdate']['userErrors'])) {
            $errors = $response['data']['productUpdate']['userErrors'];
            throw new ShopifyApiException('Failed to update product type: ' . $errors[0]['message']);
        }

        // Update local record
        $product->product_type = $newType;
        $product->save();
    }

    /**
     * Set a product's type on Shopify.
     */
    public function setProductType(Product $product, string $typeName): void
    {
        $this->updateProductType($product, $typeName);
    }

    /**
     * Create a new product type by assigning it to a product.
     * Since Shopify doesn't have standalone product types,
     * we just ensure the type exists locally.
     */
    public function create(Store $store, string $name): ProductType
    {
        return ProductType::firstOrCreate(
            ['store_id' => $store->id, 'name' => $name],
            ['products_count' => 0]
        );
    }
}
