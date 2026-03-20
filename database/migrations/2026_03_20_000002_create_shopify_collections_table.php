<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_collections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('shopify_stores')->onDelete('cascade');
            $table->string('shopify_id')->index();
            $table->string('title');
            $table->string('handle')->nullable();
            $table->text('description')->nullable();
            $table->text('description_html')->nullable();
            $table->string('image_url')->nullable();
            $table->enum('collection_type', ['smart', 'custom'])->default('custom');
            $table->json('rules')->nullable();
            $table->string('sort_order')->nullable();
            $table->integer('products_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'shopify_id']);
        });

        Schema::create('shopify_collection_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('shopify_collections')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('shopify_products')->onDelete('cascade');
            $table->integer('position')->default(0);
            $table->timestamps();

            $table->unique(['collection_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_collection_products');
        Schema::dropIfExists('shopify_collections');
    }
};
