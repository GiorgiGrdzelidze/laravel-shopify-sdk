<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('shopify.tables.inventory_levels', 'shopify_inventory_levels'), function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained(config('shopify.tables.stores', 'shopify_stores'))->onDelete('cascade');
            $table->string('inventory_item_id')->index();
            $table->string('location_id')->index();
            $table->integer('available')->nullable();
            $table->json('payload');
            $table->timestamp('shopify_updated_at')->nullable()->index();
            $table->timestamps();

            $table->unique(['store_id', 'inventory_item_id', 'location_id'], 'inventory_store_item_location_unique');
            $table->index(['store_id', 'shopify_updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('shopify.tables.inventory_levels', 'shopify_inventory_levels'));
    }
};
