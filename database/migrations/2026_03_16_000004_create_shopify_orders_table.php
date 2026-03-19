<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('shopify.tables.orders', 'shopify_orders'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained(config('shopify.tables.stores', 'shopify_stores'))->onDelete('cascade');
            $table->string('shopify_id')->index();
            $table->string('name')->nullable()->index();
            $table->string('order_number')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('financial_status')->nullable()->index();
            $table->string('fulfillment_status')->nullable()->index();
            $table->decimal('total_price', 10, 2)->nullable();
            $table->string('currency', 3)->nullable();
            $table->json('payload');
            $table->timestamp('processed_at')->nullable()->index();
            $table->timestamp('shopify_updated_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_id']);
            $table->index(['store_id', 'financial_status']);
            $table->index(['store_id', 'processed_at']);
            $table->index(['store_id', 'shopify_updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('shopify.tables.orders', 'shopify_orders'));
    }
};
