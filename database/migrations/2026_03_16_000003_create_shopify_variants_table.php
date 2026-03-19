<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('shopify.tables.variants', 'shopify_variants'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained(config('shopify.tables.stores', 'shopify_stores'))->onDelete('cascade');
            $table->foreignId('product_id')->constrained(config('shopify.tables.products', 'shopify_products'))->onDelete('cascade');
            $table->string('shopify_id')->index();
            $table->string('sku')->nullable()->index();
            $table->string('barcode')->nullable()->index();
            $table->decimal('price', 10, 2)->nullable();
            $table->string('inventory_item_id')->nullable()->index();
            $table->json('payload');
            $table->timestamp('shopify_updated_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_id']);
            $table->index(['store_id', 'product_id']);
            $table->index(['store_id', 'shopify_updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('shopify.tables.variants', 'shopify_variants'));
    }
};
