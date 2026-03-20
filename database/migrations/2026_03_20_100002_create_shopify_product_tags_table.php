<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shopify_product_tags', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('shopify_stores')->cascadeOnDelete();
            $table->string('name');
            $table->string('slug')->nullable();
            $table->text('description')->nullable();
            $table->integer('products_count')->default(0);
            $table->timestamps();

            $table->unique(['store_id', 'name']);
            $table->index('slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shopify_product_tags');
    }
};
