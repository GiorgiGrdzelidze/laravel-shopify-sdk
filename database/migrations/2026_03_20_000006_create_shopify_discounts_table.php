<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Price Rules (basis for discount codes)
        Schema::create('shopify_price_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('shopify_stores')->cascadeOnDelete();
            $table->string('shopify_id')->nullable()->index();
            $table->string('title');
            $table->string('target_type'); // line_item, shipping_line
            $table->string('target_selection'); // all, entitled
            $table->string('allocation_method'); // across, each
            $table->string('value_type'); // fixed_amount, percentage
            $table->decimal('value', 10, 2);
            $table->boolean('once_per_customer')->default(false);
            $table->integer('usage_limit')->nullable();
            $table->string('customer_selection'); // all, prerequisite
            $table->json('prerequisite_subtotal_range')->nullable();
            $table->json('prerequisite_quantity_range')->nullable();
            $table->json('prerequisite_shipping_price_range')->nullable();
            $table->json('entitled_product_ids')->nullable();
            $table->json('entitled_variant_ids')->nullable();
            $table->json('entitled_collection_ids')->nullable();
            $table->json('entitled_country_ids')->nullable();
            $table->json('prerequisite_product_ids')->nullable();
            $table->json('prerequisite_variant_ids')->nullable();
            $table->json('prerequisite_collection_ids')->nullable();
            $table->json('prerequisite_customer_ids')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_id']);
        });

        // Discount Codes
        Schema::create('shopify_discount_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('shopify_stores')->cascadeOnDelete();
            $table->foreignId('price_rule_id')->nullable()->constrained('shopify_price_rules')->cascadeOnDelete();
            $table->string('shopify_id')->nullable()->index();
            $table->string('code')->index();
            $table->integer('usage_count')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'code']);
        });

        // Automatic Discounts (GraphQL-based)
        Schema::create('shopify_automatic_discounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('shopify_stores')->cascadeOnDelete();
            $table->string('shopify_id')->nullable()->index();
            $table->string('title');
            $table->string('discount_type'); // BASIC, BXGY, FREE_SHIPPING
            $table->string('status'); // ACTIVE, EXPIRED, SCHEDULED
            $table->string('method'); // AUTOMATIC
            $table->json('customer_gets')->nullable(); // items, value
            $table->json('customer_buys')->nullable(); // for BXGY
            $table->json('minimum_requirement')->nullable();
            $table->integer('usage_count')->default(0);
            $table->boolean('combines_with_order_discounts')->default(false);
            $table->boolean('combines_with_product_discounts')->default(false);
            $table->boolean('combines_with_shipping_discounts')->default(false);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_automatic_discounts');
        Schema::dropIfExists('shopify_discount_codes');
        Schema::dropIfExists('shopify_price_rules');
    }
};
