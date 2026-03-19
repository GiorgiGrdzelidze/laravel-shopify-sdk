<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('shopify.tables.order_lines', 'shopify_order_lines'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained(config('shopify.tables.stores', 'shopify_stores'))->onDelete('cascade');
            $table->foreignId('order_id')->constrained(config('shopify.tables.orders', 'shopify_orders'))->onDelete('cascade');
            $table->string('shopify_id')->index();
            $table->string('product_id')->nullable()->index();
            $table->string('variant_id')->nullable()->index();
            $table->string('title')->nullable();
            $table->integer('quantity')->nullable();
            $table->decimal('price', 10, 2)->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->unique(['store_id', 'shopify_id']);
            $table->index(['store_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('shopify.tables.order_lines', 'shopify_order_lines'));
    }
};
