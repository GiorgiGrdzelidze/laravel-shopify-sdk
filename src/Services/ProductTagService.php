<?php

declare(strict_types=1);

namespace LaravelShopifySdk\Services;

use LaravelShopifySdk\Clients\GraphQLClient;
use LaravelShopifySdk\Exceptions\ShopifyApiException;
use LaravelShopifySdk\Models\Core\Product;
use LaravelShopifySdk\Models\Core\ProductTag;
use LaravelShopifySdk\Models\Core\Store;
use Illuminate\Support\Facades\Log;

/**
 * Product Tag Service
 *
 * Handles product tag operations with Shopify Admin API.
 * Note: Shopify doesn't have a dedicated product tags API.
 * Product tags are managed through product updates.
 */
class ProductTagService
{
    public function __construct(
        protected GraphQLClient $graphqlClient
    ) {}

    /**
     * Update all products with a specific tag to a new tag name on Shopify.
     *
     * @param ProductTag $productTag
     * @param string|null $oldName The old tag name to replace
     * @return int Number of products updated
     * @throws ShopifyApiException
     */
    public function pushToShopify(ProductTag $productTag, ?string $oldName = null): int
    {
        $store = $productTag->store;

        $searchName = $oldName ?? $productTag->name;

        // Find all local products with this tag in their payload
        $products = Product::where('store_id', $store->id)
            ->whereNotNull('shopify_id')
            ->where('shopify_id', 'not like', 'local_%')
            ->get()
            ->filter(function ($product) use ($searchName) {
                $tags = $product->payload['tags'] ?? [];
                if (is_string($tags)) {
                    $tags = array_map('trim', explode(',', $tags));
                }
                return in_array($searchName, $tags);
            });

        $updatedCount = 0;

        foreach ($products as $product) {
            try {
                $this->updateProductTag($product, $searchName, $productTag->name);
                $updatedCount++;
            } catch (\Exception $e) {
                Log::warning('Failed to update product tag on Shopify', [
                    'product_id' => $product->id,
                    'shopify_id' => $product->shopify_id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        // Update products count
        $productTag->updateProductsCount();

        Log::info('Product tag pushed to Shopify', [
            'store_id' => $store->id,
            'tag_name' => $productTag->name,
            'products_updated' => $updatedCount,
        ]);

        return $updatedCount;
    }

    /**
     * Update a single product's tags on Shopify (replace old tag with new).
     */
    protected function updateProductTag(Product $product, string $oldTag, string $newTag): void
    {
        $store = $product->store;

        // Get current tags
        $tags = $product->payload['tags'] ?? [];
        if (is_string($tags)) {
            $tags = array_map('trim', explode(',', $tags));
        }

        // Replace old tag with new tag
        $tags = array_map(function ($tag) use ($oldTag, $newTag) {
            return $tag === $oldTag ? $newTag : $tag;
        }, $tags);

        $mutation = <<<'GQL'
        mutation productUpdate($input: ProductInput!) {
            productUpdate(input: $input) {
                product {
                    id
                    tags
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
                'tags' => $tags,
            ],
        ]);

        if (!empty($response['data']['productUpdate']['userErrors'])) {
            $errors = $response['data']['productUpdate']['userErrors'];
            throw new ShopifyApiException('Failed to update product tags: ' . $errors[0]['message']);
        }

        // Update local payload
        $payload = $product->payload;
        $payload['tags'] = $tags;
        $product->payload = $payload;
        $product->save();
    }

    /**
     * Add a tag to a product on Shopify.
     */
    public function addTagToProduct(Product $product, string $tagName): void
    {
        $store = $product->store;

        // Get current tags
        $tags = $product->payload['tags'] ?? [];
        if (is_string($tags)) {
            $tags = array_map('trim', explode(',', $tags));
        }

        // Add new tag if not exists
        if (!in_array($tagName, $tags)) {
            $tags[] = $tagName;
        }

        $mutation = <<<'GQL'
        mutation productUpdate($input: ProductInput!) {
            productUpdate(input: $input) {
                product {
                    id
                    tags
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
                'tags' => $tags,
            ],
        ]);

        if (!empty($response['data']['productUpdate']['userErrors'])) {
            $errors = $response['data']['productUpdate']['userErrors'];
            throw new ShopifyApiException('Failed to add product tag: ' . $errors[0]['message']);
        }

        // Update local payload
        $payload = $product->payload;
        $payload['tags'] = $tags;
        $product->payload = $payload;
        $product->save();
    }

    /**
     * Create a new product tag.
     */
    public function create(Store $store, string $name): ProductTag
    {
        return ProductTag::firstOrCreate(
            ['store_id' => $store->id, 'name' => $name],
            ['products_count' => 0]
        );
    }
}
