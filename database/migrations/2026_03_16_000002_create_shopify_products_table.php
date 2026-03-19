<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('shopify.tables.products', 'shopify_products'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained(config('shopify.tables.stores', 'shopify_stores'))->onDelete('cascade');
            $table->string('shopify_id')->index();
            $table->string('title')->nullable();
            $table->string('handle')->nullable()->index();
            $table->string('status')->nullable()->index();
            $table->string('vendor')->nullable()->index();
            $table->string('product_type')->nullable()->index();
            $table->json('payload');
            $table->timestamp('shopify_updated_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_id']);
            $table->index(['store_id', 'status']);
            $table->index(['store_id', 'shopify_updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('shopify.tables.products', 'shopify_products'));
    }
};
